<?php
include( JAWS_PATH . 'scheduler/firepjs.php');

require_once JAWS_PATH . 'include/Jaws/InitApplication.php';

$GLOBALS['app']->Map->Parse();
$full_url = $GLOBALS['app']->GetFullURL();

$request =& Jaws_Request::getInstance();
$ReqGadget = $request->get('gadget', 'get');
if (is_null($ReqGadget)) {
    $ReqGadget = $request->get('gadget', 'post');
}

$ReqAction = $request->get('action', 'get');
if (is_null($ReqAction)) {
    $ReqAction = $request->get('action', 'post');
}

// Init layout...
$GLOBALS['app']->InstanceLayout();
$GLOBALS['app']->Layout->Load();

//now developers can add ACL to gadget for check in frontend area
$exclude_classes = array('DB.php', 'index.php', 'mpdf51');
foreach (scandir(JAWS_PATH . 'include/Jaws') as $file) {
	if(
		$file != '.' && $file != '..' && !is_dir(JAWS_PATH . 'include/Jaws/'.$file) && 
		substr(strtolower($file), -4) == '.php' && !in_array($file, $exclude_classes)
	) {
		$GLOBALS['app']->loadClass(str_replace('.php', '', $file), str_replace('/', '_', str_replace(array(JAWS_PATH . 'include/','.php'), '', JAWS_PATH . 'include/Jaws/'.$file)));
	} else if (is_dir(JAWS_PATH . 'include/Jaws/'.$file) && !in_array($file, $exclude_classes)) {
		foreach (scandir(JAWS_PATH . 'include/Jaws/'.$file) as $file2) {
			if(
				$file2 != '.' && $file2 != '..' && !is_dir(JAWS_PATH . 'include/Jaws/'.$file.'/'.$file2) && 
				substr(strtolower($file2), -4) == '.php' && !in_array($file2, $exclude_classes)
			) {
				$GLOBALS['app']->loadClass(str_replace('.php', '', $file2), str_replace('/', '_', str_replace(array(JAWS_PATH . 'include/','.php'), '', JAWS_PATH . 'include/Jaws/'.$file.'/'.$file2)));
			}
		}
	}
}
/*
$ReqGadget = 'CustomPage';
$ReqAction = 'Page';

$http_error = $request->get('http_error', 'get');
if (!empty($ReqGadget)) {
    if (Jaws_Gadget::IsValid($ReqGadget)) {
		$goGadget = $GLOBALS['app']->LoadGadget($ReqGadget);
        if (Jaws_Error::IsError($goGadget)) {
            Jaws_Error::Fatal("Error loading gadget: $ReqGadget", __FILE__, __LINE__);
        }

        $ReqAction = empty($ReqAction)? 'DefaultAction' : $ReqAction;
        $goGadget->SetAction($ReqAction);
        $ReqAction = $goGadget->GetAction();
        $GLOBALS['app']->SetMainRequest($ReqGadget, $ReqAction);
        $_IsStandAlone = ($goGadget->isStandAlone($ReqAction) || $request->get('standalone', 'get') == '1');
    } else {
        $http_error = empty($http_error)? '404' : $http_error;
    }
}
*/
$jms = $GLOBALS['app']->LoadGadget('Jms', 'AdminModel');
$gadget_list = $jms->GetGadgetsList();
foreach ($gadget_list as $gadget) {
	if (file_exists(JAWS_PATH . 'gadgets/'.$gadget['realname'].'/Model.php')) {
		$modelGadget = $GLOBALS['app']->LoadGadget($gadget['realname'], 'Model');
		if (Jaws_Error::IsError($modelGadget)) {
			Jaws_Error::Fatal("Error loading gadget Model: ".$gadget['realname']." ".var_export($modelGadget, true), __FILE__, __LINE__);
		}
	}
	if (file_exists(JAWS_PATH . 'gadgets/'.$gadget['realname'].'/AdminModel.php')) {
		$adminModelGadget = $GLOBALS['app']->LoadGadget($gadget['realname'], 'AdminModel');
		if (Jaws_Error::IsError($adminModelGadget)) {
			Jaws_Error::Fatal("Error loading gadget AdminModel: ".$gadget['realname']." ".var_export($adminModelGadget, true), __FILE__, __LINE__);
		}
	}
	if (file_exists(JAWS_PATH . 'gadgets/'.$gadget['realname'].'/HTML.php')) {
		$htmlGadget = $GLOBALS['app']->LoadGadget($gadget['realname'], 'HTML');
		if (Jaws_Error::IsError($htmlGadget)) {
			Jaws_Error::Fatal("Error loading gadget HTML: ".$gadget['realname']." ".var_export($htmlGadget, true), __FILE__, __LINE__);
		}
	}
	if (file_exists(JAWS_PATH . 'gadgets/'.$gadget['realname'].'/AdminHTML.php')) {
		$adminHtmlGadget = $GLOBALS['app']->LoadGadget($gadget['realname'], 'AdminHTML');
		if (Jaws_Error::IsError($adminHtmlGadget)) {
			Jaws_Error::Fatal("Error loading gadget AdminHTML: ".$gadget['realname']." ".var_export($adminHtmlGadget, true), __FILE__, __LINE__);
		}
	}
	if (file_exists(JAWS_PATH . 'gadgets/'.$gadget['realname'].'/LayoutHTML.php')) {
		$layoutHtmlGadget = $GLOBALS['app']->LoadGadget($gadget['realname'], 'LayoutHTML');
		if (Jaws_Error::IsError($layoutHtmlGadget)) {
			Jaws_Error::Fatal("Error loading gadget LayoutHTML: ".$gadget['realname']." ".var_export($layoutHtmlGadget, true), __FILE__, __LINE__);
		}
	}
	if (file_exists(JAWS_PATH . 'gadgets/'.$gadget['realname'].'/Ajax.php')) {
		$ajaxGadget = $GLOBALS['app']->LoadGadget($gadget['realname'], 'Ajax');
		if (Jaws_Error::IsError($ajaxGadget)) {
			Jaws_Error::Fatal("Error loading gadget Ajax: ".$gadget['realname']." ".var_export($ajaxGadget, true), __FILE__, __LINE__);
		}
	}
	if (file_exists(JAWS_PATH . 'gadgets/'.$gadget['realname'].'/AdminAjax.php')) {
		$adminAjaxGadget = $GLOBALS['app']->LoadGadget($gadget['realname'], 'AdminAjax');
		if (Jaws_Error::IsError($adminAjaxGadget)) {
			Jaws_Error::Fatal("Error loading gadget AdminAjax: ".$gadget['realname']." ".var_export($adminAjaxGadget, true), __FILE__, __LINE__);
		}
	}
	foreach (scandir(JAWS_PATH . 'gadgets/'.$gadget['realname'].'/hooks') as $file) {
		if(
			$file != '.' && $file != '..' && 
			substr(strtolower($file), -4) == '.php'
		) {
			$hookGadget = $GLOBALS['app']->loadHook($gadget['realname'], str_replace('.php', '', $file));
		}
	}
	if (file_exists(JAWS_DATA . 'hooks' . DIRECTORY_SEPARATOR . 'Shout.php')) {
		include_once JAWS_DATA . 'hooks' . DIRECTORY_SEPARATOR . 'Shout.php';
		$hook = new ShoutHook;
	}
}

/*
$_IsStandAlone = false;
$GLOBALS['app']->SetStandAloneMode($_IsStandAlone);

if (empty($http_error) && $_IsStandAlone) {
	$output = $goGadget->Execute();
	if (Jaws_Error::isError($output)) {
		Jaws_Error::Fatal($output->GetMessage(), __FILE__, __LINE__);
	}
} else {
	$GLOBALS['app']->Layout->Populate($goGadget, isset($index), $http_error);
	$GLOBALS['app']->Layout->Show(false);
}
*/
// Sync session
$GLOBALS['app']->Session->Synchronize();