<?php
require_once JAWS_PATH . 'include/Jaws/InitApplication.php';
if (DEBUG_ACTIVATED) {
    // Log start time (microseconds)
    $mtime = microtime();
    $mtime = explode(' ', $mtime);
    $mtime = (double) $mtime[0] + $mtime[1];
    $tstart = $mtime;
}

$request =& Jaws_Request::getInstance();
$gadget  = $request->get('gadget', 'post');
if (is_null($gadget)) {
    $gadget = $request->get('gadget', 'get');
    $gadget = !is_null($gadget) ? $gadget : '';
}

$action = $request->get('action', 'post');
if (is_null($action)) {
    $action = $request->get('action', 'get');
    $action = !is_null($action) ? $action : '';
}

$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL'); //Only in admin area

// FIXME: Use Websites gadget for this, and XML-RPC
// Create reseller entry
if (empty($gadget) && $action == "AddReseller") {
	// create new entry in resellers.txt
	$create_reseller_line 	= false;
    $jaws_key 			= $request->get('jaws_key', 'post');
    $jaws_name 			= $request->get('jaws_name', 'post');
    $jaws_desc 			= $request->get('jaws_desc', 'post');
    $jaws_domain 		= $request->get('jaws_domain', 'post');
    $jaws_parent 		= $request->get('jaws_parent', 'post');
	$resellerString 		= '';
	if (
		file_exists(JAWS_DATA . 'resellers.txt') &&
		!empty($jaws_key) && 
		!empty($jaws_name) && 
		!empty($jaws_domain) && 
		(!empty($jaws_parent) || $jaws_parent == '0')
	) {
		$resellerString .= "\n".
			$jaws_key."\t".
			$jaws_name."\t".
			$jaws_desc."\t".
			$jaws_domain."\t".
			$jaws_domain."\t".
			"2038-11-12 00:00:00\t".
			"active\t".
			"info@example.org\t".
			date("Y-m-d H:i:s")."\t".
			$jaws_parent."\t".
			'0';

		if (Jaws_Utils::is_writable(JAWS_DATA)) {
			$create_reseller_line = file_put_contents(JAWS_DATA . 'resellers.txt', $resellerString, FILE_APPEND);
		}
	}
	if ($create_reseller_line === false) {
		Jaws_Error::Fatal("Could not add line to resellers file: ".$resellerString, __FILE__, __LINE__);
	}
}

// FIXME: Use Websites gadget for this!
// Remove reseller entry
if (empty($gadget) && $action == "RemoveReseller") {
	include_once JAWS_PATH . 'include/Jaws/Utils.php';
	// create new resellers.txt, removing relevant line
	$create_reseller_file 	= false;
    $jaws_key 			= $request->get('jaws_key', 'post');
    $jaws_domain 		= $request->get('jaws_domain', 'post');
	if (file_exists(JAWS_DATA . 'resellers.txt') && (!empty($jaws_key) || !empty($jaws_domain))) {
		$domain = '';
		$resellerString = '';
		// Get old reseller info
		$oldResellerString = file_get_contents(JAWS_DATA . 'resellers.txt');
		$reseller_info = Jaws_Utils::split2D($oldResellerString);
		foreach ($reseller_info as $reseller) {		            
			if (isset($reseller[0]) && strlen($reseller[0]) == 32) {
				$domains = explode(',',strtolower($reseller[4]));
				$domain = $domains[0];
				if (
					(!empty($jaws_key) && $reseller[0] != $jaws_key) || 
					(!empty($jaws_domain) && $domain != strtolower($jaws_domain))
				) {
					if (!empty($jaws_domain)) {
						// Domain to remove is in the old domains array? Remove it
						if (in_array(strtolower($jaws_domain), $domains)) {
							$reseller[4] = '';
							foreach ($domains as $dom) {
								$reseller[4] .= (strtolower($jaws_domain) != (!empty($reseller[4]) ? ',' : '').$dom ? $dom : '');
							}
						}
					}
					$resellerString .= "\n".
						$reseller[0]."\t".
						$reseller[1]."\t".
						$reseller[2]."\t".
						$reseller[3]."\t".
						$reseller[4]."\t".
						$reseller[5]."\t".
						$reseller[6]."\t".
						$reseller[7]."\t".
						$reseller[8]."\t".
						$reseller[9]."\t".
						$reseller[10];
				} else {
					continue;
				}
			}
		}

		if (Jaws_Utils::is_writable(JAWS_DATA)) {
			$create_reseller_file = file_put_contents(JAWS_DATA . 'resellers.txt', $resellerString);
		}
	}
	if ($create_reseller_file === false) {
		Jaws_Error::Fatal("Could not rebuild resellers file: ".$resellerString, __FILE__, __LINE__);
	}
}

// Update reseller entry
if (empty($gadget) && $action == "UpdateResellerByDomain") {
	include_once JAWS_PATH . 'include/Jaws/Utils.php';
	// create new resellers.txt, removing relevant line
	$create_reseller_file 	= false;
    $jaws_active		= $request->get('jaws_active', 'post');
    $jaws_domain 		= $request->get('jaws_domain', 'post');
	if (file_exists(JAWS_DATA . 'resellers.txt') && !empty($jaws_domain)) {
		$domain = '';
		$resellerString = '';
		// Get old reseller info
		$oldResellerString = file_get_contents(JAWS_DATA . 'resellers.txt');
		$reseller_info = Jaws_Utils::split2D($oldResellerString);
		foreach ($reseller_info as $reseller) {		            
			if (isset($reseller[0]) && strlen($reseller[0]) == 32) {
				$domains = explode(',',strtolower($reseller[4]));
				$domain = $domains[0];
				if (!empty($jaws_domain) && $domain == strtolower($jaws_domain)) {
					$resellerString .= "\n".
						$reseller[0]."\t".
						$reseller[1]."\t".
						$reseller[2]."\t".
						$reseller[3]."\t".
						$reseller[4]."\t".
						$reseller[5]."\t".
						$jaws_active."\t".
						$reseller[7]."\t".
						$reseller[8]."\t".
						$reseller[9]."\t".
						$reseller[10];
				} else {
					$resellerString .= "\n".
						$reseller[0]."\t".
						$reseller[1]."\t".
						$reseller[2]."\t".
						$reseller[3]."\t".
						$reseller[4]."\t".
						$reseller[5]."\t".
						$reseller[6]."\t".
						$reseller[7]."\t".
						$reseller[8]."\t".
						$reseller[9]."\t".
						$reseller[10];
				}
			}
		}

		if (Jaws_Utils::is_writable(JAWS_DATA)) {
			$create_reseller_file = file_put_contents(JAWS_DATA . 'resellers.txt', $resellerString);
		}
	}
	if ($create_reseller_file === false) {
		Jaws_Error::Fatal("Could not rebuild resellers file: ".$resellerString, __FILE__, __LINE__);
	}
}

// Load a Session
if (empty($gadget) && $action == "LoadSession") {
	$session = $request->get('s', 'get');
	$redirect = $request->get('redirect', 'get');
	if ($redirect !== null) {
		$redirect = urldecode($redirect);
	}
	if ($session !== null) {
		// Acceptable format: session_id=d43a2f3c77e6c8e7b5fcae31696fcafb1290073264453;user_id=60;user_type=1;type=web;life_time=0;updatetime=1290073264;username=8226532;last_login=2010-11-18 04:41:04;language=;theme=;editor=;logged=1		
		$session = explode(';',$session);
		if (is_array($session) && count($session) >= 12) {
			$session_id = $session[0];
			$user_id = (int)$session[1];
			$user_type = (int)$session[2];
			$type = $session[3];
			$life_time = $session[4];
			$updatetime = $session[5];
			$username = $session[6];
			$last_login = $session[7];
			$language = $session[8];
			$theme = $session[9];
			$editor = $session[10];
			$logged = ($session[11] == '1' ? true : false);
		
			// Try to restore session...
			$GLOBALS['app']->Session->_HasChanged = false;

			// Load cache
			include_once JAWS_PATH . 'include/Jaws/Session/Cache.php';
			$GLOBALS['app']->Session->_cache = new Jaws_Session_Cache;
			$GLOBALS['app']->Session->_cache->DeleteExpiredSessions();
			$GLOBALS['app']->Session->_Logged = $logged;
			
			require_once JAWS_PATH . 'include/Jaws/User.php';
			$userModel = new Jaws_User;
			$info = $userModel->GetInfoByID($user_id);
			if (Jaws_Error::IsError($info) || !isset($info['username'])) {
				//return false;
				Jaws_Error::Fatal("Could not get user info.", __FILE__, __LINE__);
			}

			$groups = $userModel->GetGroupsOfUser($info['id']);
			if ($groups === false) {
				//return false;
				Jaws_Error::Fatal("Could not get groups of user.", __FILE__, __LINE__);
			}
			$GLOBALS['app']->Session->_SessionID = $session_id;
			$GLOBALS['app']->Session->_Attributes = array();
			$GLOBALS['app']->Session->SetAttribute('session_id', $session_id);
			$GLOBALS['app']->Session->SetAttribute('user_id', $info['id']);
			$GLOBALS['app']->Session->SetAttribute('user_type', (int)$info['user_type']);
			$GLOBALS['app']->Session->SetAttribute('type', APP_TYPE);
			$GLOBALS['app']->Session->SetAttribute('life_time', 0);
			$GLOBALS['app']->Session->SetAttribute('updatetime', $updatetime);
			$GLOBALS['app']->Session->SetAttribute('username', $info['username']);

			$GLOBALS['app']->Session->SetAttribute('last_login', $last_login);

			$GLOBALS['app']->Session->SetAttribute('language', $language);
			$GLOBALS['app']->Session->SetAttribute('theme',    $theme);
			$GLOBALS['app']->Session->SetAttribute('editor',   $editor);
			$GLOBALS['app']->Session->SetAttribute('logged', $logged);

			$groups = array_map(create_function('$row','return $row["group_id"];'), $groups);
			$GLOBALS['app']->Session->SetAttribute('groups', $groups);
			
			if ($GLOBALS['app']->Session->Synchronize()) {
				// Update login time
				$user_id = $GLOBALS['app']->Session->GetAttribute('user_id');
				$userModel->updateLoginTime($user_id);
				$login = $GLOBALS['app']->Session->Login($info['username'], '', false, true);
				if (Jaws_Error::isError($login)) {
					Jaws_Error::Fatal($login->GetMessage(), __FILE__, __LINE__);
				}
			} else {
				Jaws_Error::Fatal("Could not synchronize session.", __FILE__, __LINE__);
			}
		} else {
			Jaws_Error::Fatal("Session hash not complete.", __FILE__, __LINE__);
		}
	} else {
		Jaws_Error::Fatal("Session info not supplied.", __FILE__, __LINE__);
	}
} else if (
	isset($gadget) && !empty($gadget) && 
	($action == 'Ajax' || $action == 'AjaxCommonFiles' || 
		($gadget == 'Users' && $action == 'UpdateManualAux') || 
		(strtolower($gadget) == 'controlpanel' && $action == 'DBBackup') ||
		(strtolower($gadget) == 'controlpanel' && $action == 'CreatePDFsOfAllURLs') ||
		(strtolower($gadget) == 'controlpanel' && $action == 'CreatePDFOfURL'))
) {
    $ReqGadget = ucfirst($gadget);
	$file = JAWS_PATH . 'gadgets/' . $ReqGadget . '/AdminHTML.php';
	if (file_exists($file)) {
		$goGadget = $GLOBALS['app']->LoadGadget($ReqGadget, 'AdminHTML');
	} else {
		$goGadget = $GLOBALS['app']->LoadGadget($ReqGadget, 'HTML');
	}
	$goGadget->SetAction($action);
	$goGadget->Execute();
	exit;
} else {
	$GLOBALS['app']->Session->OnlyAdmins();
}

// Cache-Rebuild Code
if (empty($gadget) && $action == "RebuildCache") {
	$GLOBALS['app']->RebuildJawsCache();
	exit;
}

if ($gadget == 'ControlPanel' && ($action == 'ResetLogin' || $action == 'Logout')) {
	require_once JAWS_PATH . 'include/Jaws/Header.php';
	Jaws_Header::Location($GLOBALS['app']->GetSiteURL('', false, 'http').'/' .  $GLOBALS['app']->Map->GetURLFor('Users', 'Logout'));
    exit;
}

if ($gadget == 'ControlPanel' && $action == 'HideTip') {
	Jaws_Session_Web::SetCookie($_GET['tip'], 'shown', 60*24*150);
	require_once JAWS_PATH . 'include/Jaws/Header.php';
	Jaws_Header::Location($GLOBALS['app']->GetSiteURL().'/' . BASE_SCRIPT);
    exit;
}

if (empty($gadget)) {
	// Redirect to right site.
	$GLOBALS['app']->GetCorrectURL(true);
}

if ($GLOBALS['app']->Registry->Get('/config/show_viewsite') == 'false' && !$GLOBALS['app']->Session->IsSuperAdmin()) {
	require_once JAWS_PATH . 'include/Jaws/Header.php';
	Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'));
	exit;
}

$httpAuthEnabled = $GLOBALS['app']->Registry->Get('/config/http_auth') == 'true';
if ($httpAuthEnabled) {
    require_once JAWS_PATH . 'include/Jaws/HTTPAuth.php';
    $httpAuth = new Jaws_HTTPAuth();
}

// Check for login action is requested
if (!$GLOBALS['app']->Session->Logged() &&
    (($gadget == 'ControlPanel' && $action == 'Login') ||
    ($httpAuthEnabled && isset($_SERVER['PHP_AUTH_USER']))))
{
    if ($httpAuthEnabled) {
        $httpAuth->AssignData();
        $user   = $httpAuth->getUsername();
        $passwd = $httpAuth->getPassword();
    } else {
        $user    = $request->get('username', 'post');
        $passwd  = $request->get('password', 'post');
        $crypted = $request->get('usecrypt', 'post');

        if ($GLOBALS['app']->Registry->Get('/crypt/enabled') == 'true' && isset($crypted)) {
            require_once JAWS_PATH . 'include/Jaws/Crypt.php';
            $JCrypt = new Jaws_Crypt();
            $JCrypt->Init();
            $passwd = $JCrypt->rsa->decryptBinary($JCrypt->math->int2bin($passwd), $JCrypt->pvt_key);
            if (Jaws_Error::isError($passwd)) {
                $passwd = '';
            }
        }
    }
    $remember = $request->get('remember', 'post');
    $remember = isset($remember)? true : false;
    $login = $GLOBALS['app']->Session->Login($user, $passwd, $remember);
    if (!Jaws_Error::isError($login)) {
		require_once JAWS_PATH . 'include/Jaws/Header.php';
        // Can enter to Control Panel?
        if ($GLOBALS['app']->Session->GetPermission('ControlPanel', 'default')) {
            $redirectTo = $request->get('redirect_to', 'post');
            $redirectTo = isset($redirectTo)? $redirectTo : '';
            if (substr($redirectTo, 0, 1) == '?') {
                $redirectTo = str_replace('&amp;', '&', $redirectTo);
            } else {
                $redirectTo = BASE_SCRIPT;
            }
			Jaws_Header::Location($redirectTo);
            exit;
        } else {
            $GLOBALS['app']->Session->Logout();
			Jaws_Header::Location('?msg=NOTCP');
            exit;
        }
    } else {
        $loginMsg = $login->GetMessage();
    }
}

// Init layout...
$GLOBALS['app']->InstanceLayout();

// Check for requested gadget
if (isset($gadget) && !empty($gadget)) {
    $ReqGadget = ucfirst($gadget);
    // Convert first letter to ucase to backwards compability
    if (Jaws_Gadget::IsValid($ReqGadget)) {
        $ReqAction = !empty($action) ? $action : 'Admin';
    } else {
        Jaws_Error::Fatal('Invalid requested gadget', __FILE__, __LINE__);
    }
} else {
    $ReqGadget = 'ControlPanel';
    $ReqAction = 'DefaultAction';
}

// Check for permission tu action to execute
//FIXME: I'm unsure about treat an action as a task, it could be useful
//   but I prefer not to do it. -ion
//$GLOBALS['app']->Session->CheckPermission($ReqGadget, $ReqAction);
$isAdminHtml = false;
// Temp hack
$file = JAWS_PATH . 'gadgets/' . $ReqGadget . '/AdminHTML.php';
if (file_exists($file)) {
    $goGadget = $GLOBALS['app']->LoadGadget($ReqGadget, 'AdminHTML');
    $isAdminHtml = true;
} else {
    $goGadget = $GLOBALS['app']->LoadGadget($ReqGadget, 'HTML');
}

if (Jaws_Error::IsError($goGadget)) {
    Jaws_Error::Fatal("Error loading gadget: $ReqGadget", __FILE__, __LINE__);
}

$goGadget->SetAction($ReqAction);
$action = $goGadget->GetAction();
$standAloneAdminMode = ($goGadget->isStandAloneAdmin($action) || $request->get('standalone', 'get') == '1');
$GLOBALS['app']->SetStandAloneMode($standAloneAdminMode);

// Check if we're not logged...
if (!$GLOBALS['app']->Session->Logged()) {
    //Redirect to the login screen
    if (!isset($loginMsg)) {
        $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $msg = $xss->parse($request->get('msg', 'get'));
        $loginMsg = empty($msg)? '' : _t('GLOBAL_ERROR_LOGIN_' . $msg);
    }

    if ($httpAuthEnabled) {
        $httpAuth->showLoginBox();
    } else {
		$GLOBALS['app']->Session->PushSimpleResponse('You must log-in to continue.');
		require_once JAWS_PATH . 'include/Jaws/Header.php';
		Jaws_Header::Location($GLOBALS['app']->GetSiteURL() . '/index.php?gadget=Users&action=DefaultAction'.($standAloneAdminMode ? '&standalone=1' : '').'&redirect_to='.urlencode($GLOBALS['app']->GetFullURL()));
		exit;
        $cpl = $GLOBALS['app']->LoadGadget('ControlPanel', 'AdminHTML');
        echo $cpl->ShowLoginForm($loginMsg);
    }

    if (DEBUG_ACTIVATED) {
        $GLOBALS['log']->LogStackToScreen();
    }
    exit;
}

$GLOBALS['app']->Session->CheckPermission('ControlPanel', 'default');
			
// If requested action is `stand alone' just print it
if ($standAloneAdminMode) {
    $output = $goGadget->Execute();
	if (Jaws_Error::IsError($output)) {
        Jaws_Error::Fatal($output->GetMessage(), __FILE__, __LINE__);
	}
} else {
    //$GLOBALS['app']->InstanceLayout();
    // If requested action
    if ($goGadget->IsAdmin($action)) {
        $GLOBALS['app']->Layout->LoadControlPanelHead();
        $GLOBALS['app']->Layout->Populate($goGadget, true);
        //$GLOBALS['app']->Layout->PutGadget($goGadget->GetName(), $action, 'main');
    } else {
        Jaws_Error::Fatal("Invalid operation: You can't execute requested action", __FILE__, __LINE__);
    }

    $GLOBALS['app']->Layout->LoadControlPanel($ReqGadget);
    $GLOBALS['app']->Layout->Show();
}
		
// Sync session
$GLOBALS['app']->Session->Synchronize();

if (DEBUG_ACTIVATED) {
    $client = $request->get('client', 'get');
    if ($action != 'Ajax' && $client === null) {
        // Log generation time
        $mtime = microtime();
        $mtime = explode(' ', $mtime);
        $mtime = $mtime[1] + $mtime[0];
        $totaltime = ($mtime - $tstart);
        $GLOBALS['log']->Log(JAWS_LOG_INFO, "Page was generated in {$totaltime} seconds");
        $GLOBALS['log']->Log(JAWS_LOG_INFO, '[Jaws End] ' . date("M/d/Y H:i:s") . ' : ' . __FILE__ . ' : ' .  __LINE__);
        if (function_exists('memory_get_usage')) {
            $GLOBALS['log']->Log(JAWS_LOG_INFO, 'Memory Usage: ' . round(memory_get_usage() / 1024) . ' KB');
        }
        echo '';
		//$GLOBALS['log']->LogStackToScreen();
    }
}
if (isset($output) && !empty($output)) {
	echo $output;
}
