<?php

require_once JAWS_PATH . 'include/Jaws/InitApplication.php';
if (DEBUG_ACTIVATED) {
    // Log start time (microseconds)
    $mtime = microtime();
    $mtime = explode(' ', $mtime);
    $mtime = (double) $mtime[0] + $mtime[1];
    $tstart = $mtime;
}

$GLOBALS['app']->Session->OnlyAdmins();
$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL'); //Only in admin area

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

if ($gadget == 'ControlPanel' && $action == 'ResetLogin') {
    $GLOBALS['app']->Session->Logout();
    header('Location: ' . BASE_SCRIPT);
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
        $user   = $request->get('username', 'post');
        $passwd = $request->get('password', 'post');

        if ($GLOBALS['app']->Registry->Get('/crypt/enabled') == 'true') {
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
        // Can enter to Control Panel?
        if ($GLOBALS['app']->Session->GetPermission('ControlPanel', 'default')) {
            header('Location: scheduler.php');
            exit;
        } else {
            $GLOBALS['app']->Session->Logout();
            header('Location: scheduler.php?msg=NOTCP');
            exit;
        }
    } else {
        $loginMsg = $login->GetMessage();
    }
}

// Init layout...
$GLOBALS['app']->InstanceLayout();

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
        $cpl = $GLOBALS['app']->LoadGadget('ControlPanel', 'AdminHTML');
        echo $cpl->ShowLoginForm($loginMsg);
    }

    if (DEBUG_ACTIVATED) {
        $GLOBALS['log']->LogStackToScreen();
    }
    exit;
}

if ($action == "add-modify") {
	include(JAWS_PATH . "scheduler/pjsfiles/add-modify.php");
} else if ($action == "modify") {
	include(JAWS_PATH . "scheduler/pjsfiles/modify.php");
} else if ($action == "delete") {
	include(JAWS_PATH . "scheduler/pjsfiles/delete.php");
} else if ($action == "add") {
	$_GET['add'] = 1;
	include(JAWS_PATH . "scheduler/pjsfiles/index.php");
} else if ($action == "error-logs") {
	include(JAWS_PATH . "scheduler/pjsfiles/error-logs.php");
} else {
	include(JAWS_PATH . "scheduler/pjsfiles/index.php");
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
        $GLOBALS['log']->LogStackToScreen();
    }
}
