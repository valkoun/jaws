<?php
require_once JAWS_PATH . 'include/Jaws/InitApplication.php';
require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
$GLOBALS['log']->Log(JAWS_LOG_INFO, 'Incoming REST request: '.var_export($_SERVER, true));

// Auth
if (isset($_SERVER['PHP_AUTH_USER']) && !empty($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW']) && !empty($_SERVER['PHP_AUTH_PW'])) {
	$GLOBALS['log']->Log(JAWS_LOG_INFO, 'REST authorization: '.var_export($_SERVER['PHP_AUTH_USER'], true). ":". var_export($_SERVER['PHP_AUTH_PW'], true));
	$login = $GLOBALS['app']->Session->Login($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
	if (Jaws_Error::isError($login)) {
		//Jaws_Error::Fatal($login->GetMessage(), __FILE__, __LINE__);
		return Jaws_HTTPError::Get(401, null, $login->GetMessage());
	}
}

if (DEBUG_ACTIVATED) {
    // Log start time (microseconds)
    $mtime = microtime();
    $mtime = explode(' ', $mtime);
    $mtime = (double) $mtime[0] + $mtime[1];
    $tstart = $mtime;
}

// FIXME: Store this in action hook
// Redirect to right site.
if ($onlyWWW === false || ($onlyWWW === true && strpos(strtolower($_SERVER['HTTP_HOST']), 'www.') !== false)) {
	$correct_url = $GLOBALS['app']->GetCorrectURL(false, $onlyWWW);
	if (Jaws_Error::IsError($correct_url)) {
		//Jaws_Error::Fatal($correct_url->GetMessage(), __FILE__, __LINE__);
		return Jaws_HTTPError::Get(404, null, $correct_url->GetMessage());
	}
}

//now developers can add ACL to gadget for check in frontend area
$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');

//$GLOBALS['app']->Map->Parse();
$full_url = $GLOBALS['app']->GetFullURL();

$inputJSON = file_get_contents('php://input');
$JSON = $GLOBALS['app']->UTF8->json_decode( $inputJSON, TRUE );
$_POST = array_merge($_POST, $JSON);
$GLOBALS['log']->Log(JAWS_LOG_INFO, 'REST request data: GET: '.var_export(@$_GET, true)." POST: ".var_export(@$_POST, true)." REQUEST: ".var_export(@$_REQUEST, true)." JSON: ".var_export($JSON, true));
$request->data['post'] = $_POST;

$request =& Jaws_Request::getInstance();
$ReqGadget = $request->get('gadget', 'get');
if (is_null($ReqGadget)) {
    $ReqGadget = $request->get('gadget', 'post');
}

$ReqAction = $request->get('action', 'get');
if (is_null($ReqAction)) {
    $ReqAction = $request->get('action', 'post');
}

$ReqOutput = $request->get('output', 'get');
if (is_null($ReqOutput)) {
    $ReqOutput = $request->get('output', 'post');
}

$GLOBALS['log']->Log(JAWS_LOG_INFO, 'REST method: '.var_export($ReqAction, true));

//now developers can add ACL to gadget for check in frontend area
$http_error = $request->get('http_error', 'get');
if (!empty($ReqGadget)) {
    if (Jaws_Gadget::IsValid($ReqGadget)) {
        $ReqAction = (!empty($ReqAction) ? $ReqAction : '');
				
        if ($ReqGadget == 'Users') {
			$goGadget = $GLOBALS['app']->LoadGadget($ReqGadget, 'AdminModel');
			if (Jaws_Error::IsError($goGadget)) {
				//Jaws_Error::Fatal("Error loading gadget: $ReqGadget", __FILE__, __LINE__);
				return Jaws_HTTPError::Get(404, null, "Error loading gadget: ".$ReqGadget);
			}
			if (!method_exists($goGadget, $ReqAction)) {
				require_once JAWS_PATH . 'include/Jaws/User.php';
				$goGadget = new Jaws_User();
				if (!method_exists($goGadget, $ReqAction)) {
					//Jaws_Error::Fatal("Error loading gadget: $ReqGadget", __FILE__, __LINE__);
					return Jaws_HTTPError::Get(404, null, "Error loading gadget: ".$ReqGadget);
				} else {
					$reflection_method = new ReflectionMethod('Jaws_User', $ReqAction);
				}
			} else {
				$reflection_method = new ReflectionMethod($ReqGadget.'AdminModel', $ReqAction);
			}
		} else {
			$goGadget = $GLOBALS['app']->LoadGadget($ReqGadget, 'AdminModel');
			if (Jaws_Error::IsError($goGadget)) {
				//Jaws_Error::Fatal("Error loading gadget: $ReqGadget", __FILE__, __LINE__);
				return Jaws_HTTPError::Get(404, null, "Error loading gadget: ".$ReqGadget);
			}
			if (!method_exists($goGadget, $ReqAction)) {
				//Jaws_Error::Fatal("Error loading gadget: $ReqGadget", __FILE__, __LINE__);
				return Jaws_HTTPError::Get(404, null, "Error loading gadget: ".$ReqGadget);
			} else {
				$reflection_method = new ReflectionMethod($ReqGadget.'AdminModel', $ReqAction);
			}
		}
		$reflection_params = $reflection_method->GetParameters();
        //$goGadget->SetAction($ReqAction);
        //$ReqAction = $goGadget->GetAction();
        //$GLOBALS['app']->SetMainRequest($ReqGadget, $ReqAction);
    } else {
        $http_error = empty($http_error)? '404' : $http_error;
    }
} else {
	//Jaws_Error::Fatal("Error loading gadget.", __FILE__, __LINE__);
	return Jaws_HTTPError::Get(404, null, "Error loading gadget.");
}

$GLOBALS['log']->Log(JAWS_LOG_INFO, 'REST gadget: '.var_export($goGadget, true));

/*
$permission_actions = array(
	'form_post', 'add', 'ajax', 'login', 'logout', 'edit', 
	'delete', 'massive', 'sort', 'rename', 'save', 'update', 
	'manual', 'insert', 'enable', 'purge', 'install', 'create', 
	'uninstall', 'change', 'set', 'register', 'remove', 'doregister', 
	'getuser', 'getfriend', 'getgroup', 'getallgroup', 'getacl', 
	'getallregistry', 'valid', 'install'
);
// Check default permission...
foreach ($permission_actions as $p_action) {
	if (substr(strtolower($ReqAction), 0, strlen($p_action)) == $p_action) {
*/	
		if (
			!$GLOBALS['app']->ACL->GetFullPermission(
				$GLOBALS['app']->Session->GetAttribute('username'), 
				$GLOBALS['app']->Session->GetAttribute('groups'), 
				$ReqGadget, 'default')
		) {
			return Jaws_HTTPError::Get(403);
		}
/*
		break;
	}
}
*/

$output = '';

// Custom Hooks
if (empty($ReqGadget) && $ReqAction == "CustomHook") {
	if (file_exists(JAWS_DATA . 'hooks' . DIRECTORY_SEPARATOR . 'Custom.php')) {
		include_once JAWS_DATA . 'hooks' . DIRECTORY_SEPARATOR . 'Custom.php';
		$hook = new CustomHook;
		$call = $request->get('fuseaction', 'get');
		if (method_exists($hook, $call)) {
			$res = $hook->$call();
			if ($res === false || Jaws_Error::IsError($res)) {
				//return new Jaws_Error(_t('GLOBAL_ERROR_EVENTS_LISTENER_ERROR'), 'CORE');
				return Jaws_HTTPError::Get(404, null, _t('GLOBAL_ERROR_EVENTS_LISTENER_ERROR'));
			} else if (isset($res['return'])) {
				$output = $res['return'];
			}
		}
	}
}

if (empty($output)) {
	$ref_params = array();
	foreach ($reflection_params as $param) {
		$name = $param->getName();
		$GLOBALS['log']->Log(JAWS_LOG_INFO, 'REST method param: '.var_export($name, true));
		if ($param->isOptional()) {
			$default = $param->getDefaultValue();
		}
		$ReqParam = $request->get($name, 'get');
		if ($name != 'gadget' && !is_null($ReqParam)) {
			if (is_numeric($ReqParam)) {
				$ReqParam = (int)$ReqParam;
			} else if ($ReqParam == 'null') {
				$ReqParam = null;
			} else if ($ReqParam == 'true') {
				$ReqParam = true;
			} else if ($ReqParam == 'false') {
				$ReqParam = false;
			}
			$ref_params[] = $ReqParam;
		} else {
			$ReqParam = $request->get($name, 'post');
			if (!is_null($ReqParam)) {
				if (is_numeric($ReqParam)) {
					$ReqParam = (int)$ReqParam;
				} else if ($ReqParam == 'null') {
					$ReqParam = null;
				} else if ($ReqParam == 'true') {
					$ReqParam = true;
				} else if ($ReqParam == 'false') {
					$ReqParam = false;
				}
				$ref_params[] = $ReqParam;
			} else {
				if (isset($default)) {
					$ref_params[] = $default;
				} else {
					$ref_params[] = null;
				}
			}
		}
		$GLOBALS['log']->Log(JAWS_LOG_INFO, 'REST request param: '.var_export($ReqParam, true));
	}
	$GLOBALS['log']->Log(JAWS_LOG_INFO, 'REST matched params: '.var_export($ref_params, true));
	$output = call_user_func_array(array($goGadget, $ReqAction), $ref_params);
	if (Jaws_Error::isError($output)) {
		// TODO: return specific errors
		//Jaws_Error::Fatal($output->GetMessage(), __FILE__, __LINE__);
		return Jaws_HTTPError::Get(404, null, $output->GetMessage());
	}
}

switch (strtolower($ReqOutput)) {
	case 'xml':
		require_once 'XML/Serializer.php';
		require_once 'XML/Unserializer.php';

		$options = array(
						 XML_SERIALIZER_OPTION_XML_DECL_ENABLED => true,
						 XML_SERIALIZER_OPTION_CDATA_SECTIONS => true,
						 XML_SERIALIZER_OPTION_INDENT               => '    ',
						 XML_SERIALIZER_OPTION_LINEBREAKS           => "\n",
						 XML_SERIALIZER_OPTION_SCALAR_AS_ATTRIBUTES => false,
						 XML_SERIALIZER_OPTION_RETURN_RESULT => true,
						 XML_SERIALIZER_OPTION_ROOT_NAME => 'jawsapi',
						 XML_SERIALIZER_OPTION_DEFAULT_TAG => 'item',
						 XML_SERIALIZER_OPTION_TYPEHINTS => true,
						 /*
                         XML_SERIALIZER_OPTION_ENCODE_FUNC          => 'strtoupper'
						 */
						 );

		$serializer   = new XML_Serializer($options);
		$unserializer = new XML_Unserializer();
		$unserializer->setOption('parseAttributes', true);
		$unserializer->setOption('decodeFunction', 'strtolower');
		$output = array(strtolower($ReqAction) => $output);
		$output = $serializer->serialize($output);
		header('Content-Type: text/xml; charset=utf-8');
		break;
	default:
		$output = $GLOBALS['app']->UTF8->json_encode($output);
		header('Content-Type: application/json; charset=utf-8');
		break;
}
		
// Sync session
$GLOBALS['app']->Session->Synchronize();

if (DEBUG_ACTIVATED) {
    $client = $request->get('client', 'get');
    if ($ReqAction != 'Ajax' && $client === null) {
        // Log generation time
        $mtime = microtime();
        $mtime = explode(' ', $mtime);
        $mtime = $mtime[1] + $mtime[0];
        $tend  = $mtime;
        $totaltime = ($tend - $tstart);
        $GLOBALS['log']->Log(JAWS_LOG_INFO, 'Page was generated in '. $totaltime . ' seconds');
        $GLOBALS['log']->Log(JAWS_LOG_INFO, '[Jaws End] ' . date('M/d/Y H:i:s') . ':' . __FILE__ . ':' .  __LINE__);
        if (function_exists('memory_get_usage')) {
            $GLOBALS['log']->Log(JAWS_LOG_INFO, 'Memory Usage: ' . round(memory_get_usage() / 1024) . ' KB');
        }
        echo '';
		//$GLOBALS['log']->LogStackToScreen();
    }
}

if (!empty($output)) {
	echo $output;
}
