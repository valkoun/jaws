<?php
include( JAWS_PATH . 'scheduler/firepjs.php');

require_once JAWS_PATH . 'include/Jaws/InitApplication.php';
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
		Jaws_Error::Fatal($correct_url->GetMessage(), __FILE__, __LINE__);
	}
}

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

// Pusher authentication
if (empty($ReqGadget) && $ReqAction == "PusherAuth") {
	require_once(JAWS_PATH . '/libraries/pusher/Pusher.php');
	$pusher = new Pusher('9237ced3ff398dc663f0', '3d2039be9ec0679b0b5a', '31697');
	echo $pusher->socket_auth($request->get('channel_name', 'post'), $request->get('socket_id', 'post'));
	exit;
}

// Custom Hooks
if (empty($ReqGadget) && $ReqAction == "CustomHook") {
	$GLOBALS['app']->LoadCustomHook();
}

// Rebuild Cache
if (empty($ReqGadget) && $ReqAction == "RebuildCache") {
	$GLOBALS['app']->RebuildJawsCache();
	exit;
}

// Include Library
if (empty($ReqGadget) && $ReqAction == "IncludeLibrary") {
	$include = $request->get('path', 'get');
	if ($include !== null) {
		$include = urldecode($include);
	}
	if (substr($include, 0, 10) == '/libraries' && file_exists(JAWS_PATH . $include)) {
		require_once JAWS_PATH . $include;
	}
	exit;
}

// Load a Session
if (empty($ReqGadget) && $ReqAction == "LoadSession") {
	$session = $request->get('s', 'get');
	$redirect = $request->get('redirect', 'get');
	if (!is_null($redirect)) {
		$redirect = str_replace($GLOBALS['app']->GetSiteURL() .'/', '', urldecode($redirect));
	} else {
		$redirect = (empty($ReqGadget) ? $GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction') : str_replace($GLOBALS['app']->GetSiteURL() .'/', '', $GLOBALS['app']->GetFullURL()));
	}
	//var_dump($session);
	$load = false;
	if ($session !== null) {
		// Load cache
		include_once JAWS_PATH . 'include/Jaws/Session/Cache.php';
		$GLOBALS['app']->Session->_cache = new Jaws_Session_Cache;
		if (strpos($session, ';') !== false && count(explode(';',$session)) >= 12) {
			// Acceptable format: session_id=d43a2f3c77e6c8e7b5fcae31696fcafb1290073264453;user_id=60;user_type=1;type=web;life_time=0;updatetime=1290073264;username=8226532;last_login=2010-11-18 04:41:04;language=;theme=;editor=;logged=1		
			$load = true;
			$session = explode(';',$session);
			$session_id = $session[0];
			$user_id = (int)$session[1];
			$user_type = (int)$session[2];
			$type = $session[3];
			$life_time = (int)$session[4];
			$updatetime = $session[5];
			$username = $session[6];
			$last_login = $session[7];
			$language = $session[8];
			$theme = $session[9];
			$editor = $session[10];
			$logged = ($session[11] == '1' ? true : false);
			// Try to restore session...
			$GLOBALS['app']->Session->_HasChanged = false;
			$GLOBALS['app']->Session->_cache->DeleteExpiredSessions();
			$GLOBALS['app']->Session->_Logged = $logged;
			
			require_once JAWS_PATH . 'include/Jaws/User.php';
			$userModel = new Jaws_User;
			$info = $userModel->GetUserInfoByID($user_id, true);
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
			$GLOBALS['app']->Session->SetAttribute('life_time', $life_time);
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
				$login = $GLOBALS['app']->Session->Login($info['username'], '', ($life_time > 0 ? true : false), true);
				if (Jaws_Error::isError($login)) {
					Jaws_Error::Fatal($login->GetMessage(), __FILE__, __LINE__);
				}
				header("HTTP/1.1 302 Moved Temporarily");
				require_once JAWS_PATH . 'include/Jaws/Header.php';
				Jaws_Header::Location($redirect);
			} else {
				Jaws_Error::Fatal("Could not synchronize session.", __FILE__, __LINE__);
			}
			/*
			} else {
				Jaws_Error::Fatal("Session hash not complete.", __FILE__, __LINE__);
			}
			*/
		}
	}
	//Jaws_Error::Fatal("Session info not supplied.", __FILE__, __LINE__);
	exit;
}

// Front-end is disabled?
if ($GLOBALS['app']->Registry->Get('/config/show_viewsite') == 'false' && $ReqGadget != 'Users' && $ReqAction != 'Ajax' && $ReqAction != 'AjaxCommonFiles' && strpos($ReqAction, 'SetGBRoot') === false && !$GLOBALS['app']->Session->IsSuperAdmin()) {
	$site_ssl_url = $GLOBALS['app']->Registry->Get('/config/site_ssl_url');
	require_once JAWS_PATH . 'include/Jaws/Header.php';
	if (!empty($site_ssl_url) && (!isset($_SERVER['HTTPS']) || strtolower($_SERVER['HTTPS']) != 'on')) {
		Jaws_Header::Location(str_replace('http://'.str_replace('http://', '', $GLOBALS['app']->GetSiteURL()), 'https://'.str_replace('https://', '', $site_ssl_url), $GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction')));
	} else {
		Jaws_Header::Location($GLOBALS['app']->GetSiteURL('/') . $GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'));
	}
	exit;
}

if (
	($ReqGadget == 'Properties' || $ReqGadget == 'Store') && 
	$ReqAction == 'Category' || $ReqAction == 'Property' || $ReqAction == 'Product'
) {
	$cache_file = $GLOBALS['app']->getSyntactsCacheFile($full_url, $ReqGadget);
	$session_property = md5($cache_file);
	$GLOBALS['app']->Session->SetAttribute($session_property, 'true');
}

//now developers can add ACL to gadget for check in frontend area
$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');

// Init layout...
$GLOBALS['app']->InstanceLayout();
$GLOBALS['app']->Layout->Load();

// Run auto-load methods before standalone actions too
$GLOBALS['app']->RunAutoload();

// Is this home page?
if (empty($ReqGadget)) {
    $index = true;
    if ($GLOBALS['app']->Registry->Get('/config/home_page')) {
		//$GLOBALS['app']->Map->Parse($GLOBALS['app']->Registry->Get('/config/home_page'));
		require_once JAWS_PATH . 'include/Jaws/Header.php';
		Jaws_Header::Location($GLOBALS['app']->Registry->Get('/config/home_page'));
	}
    exit;
	//$ReqGadget = $GLOBALS['app']->Registry->Get('/config/main_gadget');
}
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

$_IsStandAlone = (isset($_IsStandAlone) && $_IsStandAlone ? true : false);
$GLOBALS['app']->SetStandAloneMode($_IsStandAlone);

// Check password protected?
$GLOBALS['app']->IsPasswordProtected($full_url, $_IsStandAlone);

if (empty($http_error) && $_IsStandAlone) {
	$output = $goGadget->Execute();
	if (Jaws_Error::isError($output)) {
		Jaws_Error::Fatal($output->GetMessage(), __FILE__, __LINE__);
	} else {
		if (strpos($output, '7host.com') !== false) {
			$output = str_replace("<script type='text/javascript' src='http://free.7host.com/includes/adsense.js'></script>", '', $output);
			$output = str_replace('<a href="http://www.7host.com" style="color:#0000ff; font-size:12px;" target="_blank">Free Web Hosting</a>', '', $output);
			$output = str_replace('<a href="http://www.vitamincenter.it" target="_blank"><img src="http://free.7host.com/bannervc.gif" border="0"></a><br>', '', $output);
			$output = str_replace('<p align="center"><font face="Arial, Helvetica, sans-serif" size=6><a href="http://www.7host.com">7Host.com Free Web Hosting</a></font></p>', '', $output);
		}
		$output = str_replace('Error: Unable to read footer file.', '', $output);

		if (strpos($output, '<!-- 7host begin code -->') !== false) {
			$output = preg_replace('|\<\!-- 7host begin code --\>.*?\<!-- 7host end code --\>|siu','',$output);
		}		
												
		// Translate
		$session_id = $GLOBALS['app']->Session->GetAttribute('session_id');
		$params          = array();
		$params['session_id'] = $session_id;

		$sql = '
		   SELECT [language]
		   FROM [[session]]
		   WHERE [session_id] = {session_id}';

		$user_data = $GLOBALS['db']->queryRow($sql, $params);
		if (!Jaws_Error::isError($user_data) && isset($user_data['language'])) {
			if ($user_data['language'] == 'en') {
				$translate = '<!-- start_translation --><!-- end_translation -->';
			} else {
				$translate = "<!-- start_translation --><script type=\"text/javascript\" src=\"http://www.google.com/jsapi\"></script><script>function trim12(str) { var str = str.replace(/^\\s\\s*/, ''); var ws = /\\s/; var i = str.length; while (ws.test(str.charAt(--i))) {return str.slice(0, i + 1);}} function translateNode(node) {/* Note: by putting in an empty string for the source language ('en') then the translation will auto-detect the source language. *//*for (i=0;i<nodeCount;i++) {*/ google.language.translate(node.nodeValue, 'en', '".$user_data['language']."', function(result) { /* var translated = document.getElementById(\"translation\"); */ if (result.translation) {node.nodeValue = result.translation;}});/*}*/ return true;} /* Helper methods for setting/getting element text without mucking * around with multiple TextNodes. */ Element.addMethods({ getText: function(element, recursive) { \$A(element.childNodes).each(function(node) { if (node.nodeType == 3 && trim12(node.nodeValue) != '' && node.nodeName.toLowerCase().indexOf('script') == -1 && node.nodeName.toLowerCase().indexOf('object') == -1 && node.nodeName.toLowerCase().indexOf('style') == -1 && node.nodeName.toLowerCase().indexOf('link') == -1 && node.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node).nodeValue; /* text[nodeCount] = node.nodeValue; nodes[nodeCount] = node; nodeCount += 1; */ return translateNode(node); } else if (recursive && node.hasChildNodes()) { /* if (node.nodeType == 1 && node.nodeName.toLowerCase().indexOf('script') == -1 && node.nodeName.toLowerCase().indexOf('object') == -1 && node.nodeName.toLowerCase().indexOf('style') == -1 && node.nodeName.toLowerCase().indexOf('link') == -1 && node.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node).getText(true); \$(node).getText(true); */ \$A(node.childNodes).each(function(node1) { if (node1.nodeType == 3 && trim12(node1.nodeValue) != '' && node1.nodeName.toLowerCase().indexOf('script') == -1 && node1.nodeName.toLowerCase().indexOf('object') == -1 && node1.nodeName.toLowerCase().indexOf('style') == -1 && node1.nodeName.toLowerCase().indexOf('link') == -1 && node1.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node1).nodeValue; return translateNode(node1); } else if (recursive && node1.hasChildNodes()) { \$A(node1.childNodes).each(function(node2) { if (node2.nodeType == 3 && trim12(node2.nodeValue) != '' && node2.nodeName.toLowerCase().indexOf('script') == -1 && node2.nodeName.toLowerCase().indexOf('object') == -1 && node2.nodeName.toLowerCase().indexOf('style') == -1 && node2.nodeName.toLowerCase().indexOf('link') == -1 && node2.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node2).nodeValue; return translateNode(node2); } else if (recursive && node2.hasChildNodes()) { \$A(node2.childNodes).each(function(node3) { if (node3.nodeType == 3 && trim12(node3.nodeValue) != '' && node3.nodeName.toLowerCase().indexOf('script') == -1 && node3.nodeName.toLowerCase().indexOf('object') == -1 && node3.nodeName.toLowerCase().indexOf('style') == -1 && node3.nodeName.toLowerCase().indexOf('link') == -1 && node3.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node3).nodeValue; return translateNode(node3); } else if (recursive && node3.hasChildNodes()) { \$A(node3.childNodes).each(function(node4) { if (node4.nodeType == 3 && trim12(node4.nodeValue) != '' && node4.nodeName.toLowerCase().indexOf('script') == -1 && node4.nodeName.toLowerCase().indexOf('object') == -1 && node4.nodeName.toLowerCase().indexOf('style') == -1 && node4.nodeName.toLowerCase().indexOf('link') == -1 && node4.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node4).nodeValue; return translateNode(node4); } else if (recursive && node4.hasChildNodes()) { \$A(node4.childNodes).each(function(node5) { if (node5.nodeType == 3 && trim12(node5.nodeValue) != '' && node5.nodeName.toLowerCase().indexOf('script') == -1 && node5.nodeName.toLowerCase().indexOf('object') == -1 && node5.nodeName.toLowerCase().indexOf('style') == -1 && node5.nodeName.toLowerCase().indexOf('link') == -1 && node5.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node5).nodeValue; return translateNode(node5); } else if (recursive && node5.hasChildNodes()) { \$A(node5.childNodes).each(function(node6) { if (node6.nodeType == 3 && trim12(node6.nodeValue) != '' && node6.nodeName.toLowerCase().indexOf('script') == -1 && node6.nodeName.toLowerCase().indexOf('object') == -1 && node6.nodeName.toLowerCase().indexOf('style') == -1 && node6.nodeName.toLowerCase().indexOf('link') == -1 && node6.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node6).nodeValue; return translateNode(node6); } else if (recursive && node6.hasChildNodes()) { \$A(node6.childNodes).each(function(node7) { if (node7.nodeType == 3 && trim12(node7.nodeValue) != '' && node7.nodeName.toLowerCase().indexOf('script') == -1 && node7.nodeName.toLowerCase().indexOf('object') == -1 && node7.nodeName.toLowerCase().indexOf('style') == -1 && node7.nodeName.toLowerCase().indexOf('link') == -1 && node7.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node7).nodeValue; return translateNode(node7); } else if (recursive && node7.hasChildNodes()) { \$A(node7.childNodes).each(function(node8) { if (node8.nodeType == 3 && trim12(node8.nodeValue) != '' && node8.nodeName.toLowerCase().indexOf('script') == -1 && node8.nodeName.toLowerCase().indexOf('object') == -1 && node8.nodeName.toLowerCase().indexOf('style') == -1 && node8.nodeName.toLowerCase().indexOf('link') == -1 && node8.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node8).nodeValue; return translateNode(node8); } else if (recursive && node8.hasChildNodes()) { \$A(node8.childNodes).each(function(node9) { if (node9.nodeType == 3 && trim12(node9.nodeValue) != '' && node9.nodeName.toLowerCase().indexOf('script') == -1 && node9.nodeName.toLowerCase().indexOf('object') == -1 && node9.nodeName.toLowerCase().indexOf('style') == -1 && node9.nodeName.toLowerCase().indexOf('link') == -1 && node9.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node9).nodeValue; return translateNode(node9); } else if (recursive && node9.hasChildNodes()) { \$A(node9.childNodes).each(function(node10) { if (node10.nodeType == 3 && trim12(node10.nodeValue) != '' && node10.nodeName.toLowerCase().indexOf('script') == -1 && node10.nodeName.toLowerCase().indexOf('object') == -1 && node10.nodeName.toLowerCase().indexOf('style') == -1 && node10.nodeName.toLowerCase().indexOf('link') == -1 && node10.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node10).nodeValue; return translateNode(node10); } else if (recursive && node10.hasChildNodes()) { \$A(node10.childNodes).each(function(node11) { if (node11.nodeType == 3 && trim12(node11.nodeValue) != '' && node11.nodeName.toLowerCase().indexOf('script') == -1 && node11.nodeName.toLowerCase().indexOf('object') == -1 && node11.nodeName.toLowerCase().indexOf('style') == -1 && node11.nodeName.toLowerCase().indexOf('link') == -1 && node11.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node11).nodeValue; return translateNode(node11); } else if (recursive && node11.hasChildNodes()) { \$A(node11.childNodes).each(function(node12) { if (node12.nodeType == 3 && trim12(node12.nodeValue) != '' && node12.nodeName.toLowerCase().indexOf('script') == -1 && node12.nodeName.toLowerCase().indexOf('object') == -1 && node12.nodeName.toLowerCase().indexOf('style') == -1 && node12.nodeName.toLowerCase().indexOf('link') == -1 && node12.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node12).nodeValue; return translateNode(node12); } else if (recursive && node12.hasChildNodes()) { \$A(node12.childNodes).each(function(node13) { if (node13.nodeType == 3 && trim12(node13.nodeValue) != '' && node13.nodeName.toLowerCase().indexOf('script') == -1 && node13.nodeName.toLowerCase().indexOf('object') == -1 && node13.nodeName.toLowerCase().indexOf('style') == -1 && node13.nodeName.toLowerCase().indexOf('link') == -1 && node13.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node13).nodeValue; return translateNode(node13); } else if (recursive && node13.hasChildNodes()) { \$A(node13.childNodes).each(function(node14) { if (node14.nodeType == 3 && trim12(node14.nodeValue) != '' && node14.nodeName.toLowerCase().indexOf('script') == -1 && node14.nodeName.toLowerCase().indexOf('object') == -1 && node14.nodeName.toLowerCase().indexOf('style') == -1 && node14.nodeName.toLowerCase().indexOf('link') == -1 && node14.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node14).nodeValue; return translateNode(node14); } else if (recursive && node14.hasChildNodes()) { \$A(node14.childNodes).each(function(node15) { if (node15.nodeType == 3 && trim12(node15.nodeValue) != '' && node15.nodeName.toLowerCase().indexOf('script') == -1 && node15.nodeName.toLowerCase().indexOf('object') == -1 && node15.nodeName.toLowerCase().indexOf('style') == -1 && node15.nodeName.toLowerCase().indexOf('link') == -1 && node15.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node15).nodeValue; return translateNode(node15); } else if (recursive && node15.hasChildNodes()) { \$A(node15.childNodes).each(function(node16) { if (node16.nodeType == 3 && trim12(node16.nodeValue) != '' && node16.nodeName.toLowerCase().indexOf('script') == -1 && node16.nodeName.toLowerCase().indexOf('object') == -1 && node16.nodeName.toLowerCase().indexOf('style') == -1 && node16.nodeName.toLowerCase().indexOf('link') == -1 && node16.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node16).nodeValue; return translateNode(node16); } else if (recursive && node16.hasChildNodes()) { \$A(node16.childNodes).each(function(node17) { if (node17.nodeType == 3 && trim12(node17.nodeValue) != '' && node17.nodeName.toLowerCase().indexOf('script') == -1 && node17.nodeName.toLowerCase().indexOf('object') == -1 && node17.nodeName.toLowerCase().indexOf('style') == -1 && node17.nodeName.toLowerCase().indexOf('link') == -1 && node17.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node17).nodeValue; return translateNode(node17); } else if (recursive && node17.hasChildNodes()) { \$A(node17.childNodes).each(function(node18) { if (node18.nodeType == 3 && trim12(node18.nodeValue) != '' && node18.nodeName.toLowerCase().indexOf('script') == -1 && node18.nodeName.toLowerCase().indexOf('object') == -1 && node18.nodeName.toLowerCase().indexOf('style') == -1 && node18.nodeName.toLowerCase().indexOf('link') == -1 && node18.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node18).nodeValue; return translateNode(node18); } else if (recursive && node18.hasChildNodes()) { \$A(node18.childNodes).each(function(node19) { if (node19.nodeType == 3 && trim12(node19.nodeValue) != '' && node19.nodeName.toLowerCase().indexOf('script') == -1 && node19.nodeName.toLowerCase().indexOf('object') == -1 && node19.nodeName.toLowerCase().indexOf('style') == -1 && node19.nodeName.toLowerCase().indexOf('link') == -1 && node19.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node19).nodeValue; return translateNode(node19); } else if (recursive && node19.hasChildNodes()) { \$A(node19.childNodes).each(function(node20) { if (node20.nodeType == 3 && trim12(node20.nodeValue) != '' && node20.nodeName.toLowerCase().indexOf('script') == -1 && node20.nodeName.toLowerCase().indexOf('object') == -1 && node20.nodeName.toLowerCase().indexOf('style') == -1 && node20.nodeName.toLowerCase().indexOf('link') == -1 && node20.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node20).nodeValue; return translateNode(node20); } else if (recursive && node20.hasChildNodes()) { \$A(node20.childNodes).each(function(node21) { if (node21.nodeType == 3 && trim12(node21.nodeValue) != '' && node21.nodeName.toLowerCase().indexOf('script') == -1 && node21.nodeName.toLowerCase().indexOf('object') == -1 && node21.nodeName.toLowerCase().indexOf('style') == -1 && node21.nodeName.toLowerCase().indexOf('link') == -1 && node21.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node21).nodeValue; return translateNode(node21); } else if (recursive && node21.hasChildNodes()) { \$A(node21.childNodes).each(function(node22) { if (node22.nodeType == 3 && trim12(node22.nodeValue) != '' && node22.nodeName.toLowerCase().indexOf('script') == -1 && node22.nodeName.toLowerCase().indexOf('object') == -1 && node22.nodeName.toLowerCase().indexOf('style') == -1 && node22.nodeName.toLowerCase().indexOf('link') == -1 && node22.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node22).nodeValue; return translateNode(node22); } else if (recursive && node22.hasChildNodes()) { \$A(node22.childNodes).each(function(node23) { if (node23.nodeType == 3 && trim12(node23.nodeValue) != '' && node23.nodeName.toLowerCase().indexOf('script') == -1 && node23.nodeName.toLowerCase().indexOf('object') == -1 && node23.nodeName.toLowerCase().indexOf('style') == -1 && node23.nodeName.toLowerCase().indexOf('link') == -1 && node23.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node23).nodeValue; return translateNode(node23); } else if (recursive && node23.hasChildNodes()) { \$A(node23.childNodes).each(function(node24) { if (node24.nodeType == 3 && trim12(node24.nodeValue) != '' && node24.nodeName.toLowerCase().indexOf('script') == -1 && node24.nodeName.toLowerCase().indexOf('object') == -1 && node24.nodeName.toLowerCase().indexOf('style') == -1 && node24.nodeName.toLowerCase().indexOf('link') == -1 && node24.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node24).nodeValue; return translateNode(node24); } else if (recursive && node24.hasChildNodes()) { \$A(node23.childNodes).each(function(node24) { if (node24.nodeType == 3 && trim12(node24.nodeValue) != '' && node24.nodeName.toLowerCase().indexOf('script') == -1 && node24.nodeName.toLowerCase().indexOf('object') == -1 && node24.nodeName.toLowerCase().indexOf('style') == -1 && node24.nodeName.toLowerCase().indexOf('link') == -1 && node24.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node24).nodeValue; return translateNode(node24); } else if (recursive && node24.hasChildNodes()) { \$A(node24.childNodes).each(function(node25) { if (node25.nodeType == 3 && trim12(node25.nodeValue) != '' && node25.nodeName.toLowerCase().indexOf('script') == -1 && node25.nodeName.toLowerCase().indexOf('object') == -1 && node25.nodeName.toLowerCase().indexOf('style') == -1 && node25.nodeName.toLowerCase().indexOf('link') == -1 && node25.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node25).nodeValue; return translateNode(node25); } else if (recursive && node25.hasChildNodes()) { \$A(node25.childNodes).each(function(node26) { if (node26.nodeType == 3 && trim12(node26.nodeValue) != '' && node26.nodeName.toLowerCase().indexOf('script') == -1 && node26.nodeName.toLowerCase().indexOf('object') == -1 && node26.nodeName.toLowerCase().indexOf('style') == -1 && node26.nodeName.toLowerCase().indexOf('link') == -1 && node26.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node26).nodeValue; return translateNode(node26); } else if (recursive && node26.hasChildNodes()) { \$A(node26.childNodes).each(function(node27) { if (node27.nodeType == 3 && trim12(node27.nodeValue) != '' && node27.nodeName.toLowerCase().indexOf('script') == -1 && node27.nodeName.toLowerCase().indexOf('object') == -1 && node27.nodeName.toLowerCase().indexOf('style') == -1 && node27.nodeName.toLowerCase().indexOf('link') == -1 && node27.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node27).nodeValue; return translateNode(node27); } else if (recursive && node27.hasChildNodes()) { \$A(node27.childNodes).each(function(node28) { if (node28.nodeType == 3 && trim12(node28.nodeValue) != '' && node28.nodeName.toLowerCase().indexOf('script') == -1 && node28.nodeName.toLowerCase().indexOf('object') == -1 && node28.nodeName.toLowerCase().indexOf('style') == -1 && node28.nodeName.toLowerCase().indexOf('link') == -1 && node28.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node28).nodeValue; return translateNode(node28); } else if (recursive && node28.hasChildNodes()) { \$A(node28.childNodes).each(function(node29) { if (node29.nodeType == 3 && trim12(node29.nodeValue) != '' && node29.nodeName.toLowerCase().indexOf('script') == -1 && node29.nodeName.toLowerCase().indexOf('object') == -1 && node29.nodeName.toLowerCase().indexOf('style') == -1 && node29.nodeName.toLowerCase().indexOf('link') == -1 && node29.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node29).nodeValue; return translateNode(node29); } else if (recursive && node29.hasChildNodes()) { \$A(node29.childNodes).each(function(node30) { if (node30.nodeType == 3 && trim12(node30.nodeValue) != '' && node30.nodeName.toLowerCase().indexOf('script') == -1 && node30.nodeName.toLowerCase().indexOf('object') == -1 && node30.nodeName.toLowerCase().indexOf('style') == -1 && node30.nodeName.toLowerCase().indexOf('link') == -1 && node30.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node30).nodeValue; return translateNode(node30); } else if (recursive && node30.hasChildNodes()) { \$A(node30.childNodes).each(function(node31) { if (node31.nodeType == 3 && trim12(node31.nodeValue) != '' && node31.nodeName.toLowerCase().indexOf('script') == -1 && node31.nodeName.toLowerCase().indexOf('object') == -1 && node31.nodeName.toLowerCase().indexOf('style') == -1 && node31.nodeName.toLowerCase().indexOf('link') == -1 && node31.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node31).nodeValue; return translateNode(node31); } else if (recursive && node31.hasChildNodes()) { \$A(node31.childNodes).each(function(node32) { if (node32.nodeType == 3 && trim12(node32.nodeValue) != '' && node32.nodeName.toLowerCase().indexOf('script') == -1 && node32.nodeName.toLowerCase().indexOf('object') == -1 && node32.nodeName.toLowerCase().indexOf('style') == -1 && node32.nodeName.toLowerCase().indexOf('link') == -1 && node32.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node32).nodeValue; return translateNode(node32); } else if (recursive && node32.hasChildNodes()) { \$A(node32.childNodes).each(function(node33) { if (node33.nodeType == 3 && trim12(node33.nodeValue) != '' && node33.nodeName.toLowerCase().indexOf('script') == -1 && node33.nodeName.toLowerCase().indexOf('object') == -1 && node33.nodeName.toLowerCase().indexOf('style') == -1 && node33.nodeName.toLowerCase().indexOf('link') == -1 && node33.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node33).nodeValue; return translateNode(node33); } else if (recursive && node33.hasChildNodes()) { \$A(node33.childNodes).each(function(node34) { if (node34.nodeType == 3 && trim12(node34.nodeValue) != '' && node34.nodeName.toLowerCase().indexOf('script') == -1 && node34.nodeName.toLowerCase().indexOf('object') == -1 && node34.nodeName.toLowerCase().indexOf('style') == -1 && node34.nodeName.toLowerCase().indexOf('link') == -1 && node34.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node34).nodeValue; return translateNode(node34); } else if (recursive && node34.hasChildNodes()) { \$A(node34.childNodes).each(function(node35) { if (node35.nodeType == 3 && trim12(node35.nodeValue) != '' && node35.nodeName.toLowerCase().indexOf('script') == -1 && node35.nodeName.toLowerCase().indexOf('object') == -1 && node35.nodeName.toLowerCase().indexOf('style') == -1 && node35.nodeName.toLowerCase().indexOf('link') == -1 && node35.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node35).nodeValue; return translateNode(node35); } else if (recursive && node35.hasChildNodes()) { \$A(node35.childNodes).each(function(node36) { if (node36.nodeType == 3 && trim12(node36.nodeValue) != '' && node36.nodeName.toLowerCase().indexOf('script') == -1 && node36.nodeName.toLowerCase().indexOf('object') == -1 && node36.nodeName.toLowerCase().indexOf('style') == -1 && node36.nodeName.toLowerCase().indexOf('link') == -1 && node36.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node36).nodeValue; return translateNode(node36); } else if (recursive && node36.hasChildNodes()) { \$A(node36.childNodes).each(function(node37) { if (node37.nodeType == 3 && trim12(node37.nodeValue) != '' && node37.nodeName.toLowerCase().indexOf('script') == -1 && node37.nodeName.toLowerCase().indexOf('object') == -1 && node37.nodeName.toLowerCase().indexOf('style') == -1 && node37.nodeName.toLowerCase().indexOf('link') == -1 && node37.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node37).nodeValue; return translateNode(node37); } else if (recursive && node37.hasChildNodes()) { \$A(node37.childNodes).each(function(node38) { if (node38.nodeType == 3 && trim12(node38.nodeValue) != '' && node38.nodeName.toLowerCase().indexOf('script') == -1 && node38.nodeName.toLowerCase().indexOf('object') == -1 && node38.nodeName.toLowerCase().indexOf('style') == -1 && node38.nodeName.toLowerCase().indexOf('link') == -1 && node38.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node38).nodeValue; return translateNode(node38); } else if (recursive && node38.hasChildNodes()) { \$A(node38.childNodes).each(function(node39) { if (node39.nodeType == 3 && trim12(node39.nodeValue) != '' && node39.nodeName.toLowerCase().indexOf('script') == -1 && node39.nodeName.toLowerCase().indexOf('object') == -1 && node39.nodeName.toLowerCase().indexOf('style') == -1 && node39.nodeName.toLowerCase().indexOf('link') == -1 && node39.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node39).nodeValue; return translateNode(node39); } else if (recursive && node39.hasChildNodes()) { \$A(node39.childNodes).each(function(node40) { if (node40.nodeType == 3 && trim12(node40.nodeValue) != '' && node40.nodeName.toLowerCase().indexOf('script') == -1 && node40.nodeName.toLowerCase().indexOf('object') == -1 && node40.nodeName.toLowerCase().indexOf('style') == -1 && node40.nodeName.toLowerCase().indexOf('link') == -1 && node40.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node40).nodeValue; return translateNode(node40); } else if (recursive && node40.hasChildNodes()) { \$A(node40.childNodes).each(function(node41) { if (node41.nodeType == 3 && trim12(node41.nodeValue) != '' && node41.nodeName.toLowerCase().indexOf('script') == -1 && node41.nodeName.toLowerCase().indexOf('object') == -1 && node41.nodeName.toLowerCase().indexOf('style') == -1 && node41.nodeName.toLowerCase().indexOf('link') == -1 && node41.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node41).nodeValue; return translateNode(node41); } else if (recursive && node41.hasChildNodes()) { \$A(node41.childNodes).each(function(node42) { if (node42.nodeType == 3 && trim12(node42.nodeValue) != '' && node42.nodeName.toLowerCase().indexOf('script') == -1 && node42.nodeName.toLowerCase().indexOf('object') == -1 && node42.nodeName.toLowerCase().indexOf('style') == -1 && node42.nodeName.toLowerCase().indexOf('link') == -1 && node42.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node42).nodeValue; return translateNode(node42); } else if (recursive && node42.hasChildNodes()) { \$A(node42.childNodes).each(function(node43) { if (node43.nodeType == 3 && trim12(node43.nodeValue) != '' && node43.nodeName.toLowerCase().indexOf('script') == -1 && node43.nodeName.toLowerCase().indexOf('object') == -1 && node43.nodeName.toLowerCase().indexOf('style') == -1 && node43.nodeName.toLowerCase().indexOf('link') == -1 && node43.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node43).nodeValue; return translateNode(node43); } else if (recursive && node43.hasChildNodes()) { \$A(node43.childNodes).each(function(node44) { if (node44.nodeType == 3 && trim12(node44.nodeValue) != '' && node44.nodeName.toLowerCase().indexOf('script') == -1 && node44.nodeName.toLowerCase().indexOf('object') == -1 && node44.nodeName.toLowerCase().indexOf('style') == -1 && node44.nodeName.toLowerCase().indexOf('link') == -1 && node44.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node44).nodeValue; return translateNode(node44); } else if (recursive && node44.hasChildNodes()) { \$A(node44.childNodes).each(function(node45) { if (node45.nodeType == 3 && trim12(node45.nodeValue) != '' && node45.nodeName.toLowerCase().indexOf('script') == -1 && node45.nodeName.toLowerCase().indexOf('object') == -1 && node45.nodeName.toLowerCase().indexOf('style') == -1 && node45.nodeName.toLowerCase().indexOf('link') == -1 && node45.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node45).nodeValue; return translateNode(node45); } else if (recursive && node45.hasChildNodes()) { \$A(node45.childNodes).each(function(node46) { if (node46.nodeType == 3 && trim12(node46.nodeValue) != '' && node46.nodeName.toLowerCase().indexOf('script') == -1 && node46.nodeName.toLowerCase().indexOf('object') == -1 && node46.nodeName.toLowerCase().indexOf('style') == -1 && node46.nodeName.toLowerCase().indexOf('link') == -1 && node46.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node46).nodeValue; return translateNode(node46); } else if (recursive && node46.hasChildNodes()) { \$A(node46.childNodes).each(function(node47) { if (node47.nodeType == 3 && trim12(node47.nodeValue) != '' && node47.nodeName.toLowerCase().indexOf('script') == -1 && node47.nodeName.toLowerCase().indexOf('object') == -1 && node47.nodeName.toLowerCase().indexOf('style') == -1 && node47.nodeName.toLowerCase().indexOf('link') == -1 && node47.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node47).nodeValue; return translateNode(node47); } else if (recursive && node47.hasChildNodes()) { \$A(node47.childNodes).each(function(node48) { if (node48.nodeType == 3 && trim12(node48.nodeValue) != '' && node48.nodeName.toLowerCase().indexOf('script') == -1 && node48.nodeName.toLowerCase().indexOf('object') == -1 && node48.nodeName.toLowerCase().indexOf('style') == -1 && node48.nodeName.toLowerCase().indexOf('link') == -1 && node48.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node48).nodeValue; return translateNode(node48); } else if (recursive && node48.hasChildNodes()) { \$A(node48.childNodes).each(function(node49) { if (node49.nodeType == 3 && trim12(node49.nodeValue) != '' && node49.nodeName.toLowerCase().indexOf('script') == -1 && node49.nodeName.toLowerCase().indexOf('object') == -1 && node49.nodeName.toLowerCase().indexOf('style') == -1 && node49.nodeName.toLowerCase().indexOf('link') == -1 && node49.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node49).nodeValue; return translateNode(node49); } else if (recursive && node49.hasChildNodes()) { \$A(node49.childNodes).each(function(node50) { if (node50.nodeType == 3 && trim12(node50.nodeValue) != '' && node50.nodeName.toLowerCase().indexOf('script') == -1 && node50.nodeName.toLowerCase().indexOf('object') == -1 && node50.nodeName.toLowerCase().indexOf('style') == -1 && node50.nodeName.toLowerCase().indexOf('link') == -1 && node50.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node50).nodeValue; return translateNode(node50); } else if (recursive && node50.hasChildNodes()) { } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } /* if (node2.nodeType == 1 && node2.nodeName.toLowerCase().indexOf('script') == -1 && node2.nodeName.toLowerCase().indexOf('object') == -1 && node2.nodeName.toLowerCase().indexOf('style') == -1 && node2.nodeName.toLowerCase().indexOf('link') == -1 && node2.nodeName.toLowerCase().indexOf('embed') == -1) { \$(node2).getText(true); } */ }); } }); return true; } });  google.load(\"language\", \"1\"); var text = ''; var nodes = []; var nodeCount = 0; Event.observe(window, 'load', function(){ \$('container').getText(true); });</script><!-- end_translation -->";
			}
			$output = str_replace('</body>', $translate.'</body>', $output);
		}
	}
} else {
    $GLOBALS['app']->Layout->Populate($goGadget, isset($index), $http_error);
    $GLOBALS['app']->Layout->Show();
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

if (isset($output) && !empty($output)) {
	echo $output;
}
