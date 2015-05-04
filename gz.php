<?php
if (isset($_GET['type'])) {
	require_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'InitApplication.php';
	ob_start();
	$full_url = '';
	if (!isset($_SERVER['FULL_URL']) || empty($_SERVER['FULL_URL'])) {
		$scheme = (isset($_SERVER["HTTPS"]) && strtolower($_SERVER["HTTPS"]) == "on") ? "https" : "http"; 
		$port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"]); 
		$full_url = $scheme."://".$_SERVER['SERVER_NAME'].$port.$_SERVER['REQUEST_URI']; 
	} else {
		$full_url = $_SERVER['FULL_URL'];
	}
	if (!empty($full_url)) {
		$full_url = str_replace('www.', '', $full_url);
		$full_url = str_replace(':80', '', $full_url);
		$full_url = str_replace(':443', '', $full_url);
	}

	if (CACHING_ENABLED && !empty($full_url) && file_exists(JAWS_DATA . 'cache/apps/'.md5($full_url))) {
		echo file_get_contents(JAWS_DATA . 'cache/apps/'.md5($full_url));
		//exit;
	} else {
		include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
		$site_url = $GLOBALS['app']->GetSiteURL('', false, 'http');
		$site_ssl_url = $GLOBALS['app']->GetSiteURL('', false, 'https');
		foreach ($_GET as $k => $v) {
			if (strpos($k, 'uri') !== false) {
				$uri = urldecode($v);
				$fetch_url = false;
				if (substr(strtolower($uri), 0, 5) == 'index' || substr(strtolower($uri), 0, 5) == 'admin' ) {
					$uri = $site_url . '/'. $uri;
					$fetch_url = true;
				} else if (
					substr(strtolower($uri), 0, strlen(strtolower($site_url))) == $site_url || 
					substr(strtolower($uri), 0, strlen(strtolower($site_ssl_url))) == $site_ssl_url || 
					substr(strtolower($uri), 0, 26) == 'http://ajax.googleapis.com' || 
					substr(strtolower($uri), 0, 27) == 'https://ajax.googleapis.com' || 
					substr(strtolower($uri), 0, 22) == 'http://maps.google.com'
				) {
					$fetch_url = true;
				}
				//var_dump($uri);
				if ($fetch_url === true) {
					if (
						substr(strtolower($uri), 0, strlen(strtolower($site_ssl_url))) == $site_ssl_url || 
						(strpos(strtolower($site_ssl_url), '/', 9) !== false && 
						substr(strtolower($uri), 0, strpos(strtolower($site_ssl_url), '/', 9)) == substr(strtolower($site_ssl_url), 0, strpos(strtolower($site_ssl_url), '/', 9)))
					) {
						$uri = str_replace(substr(strtolower($site_ssl_url), 0, strpos(strtolower($site_ssl_url), '/', 9)), substr(strtolower($site_url), 0, strpos(strtolower($site_url), '/', 9)), strtolower($uri)); 
					}
					$uri = str_replace(array('&amp;', '../'), array('&', ''), $uri);
					//file content here...
					$snoopy = new Snoopy;
					if($snoopy->fetch($uri)) {
						echo $snoopy->results;
					}
				}
			}
		}
	}
	header("Content-Type: text/".$_GET['type']);
	header("Content-Length: ".ob_get_length());
	header("Pragma: cache");
	header("cache-control: must-revalidate");
	//if ($cached === false) {
		$offset = 48 * 60 * 60;
		$expire = "expires: " . gmdate ("D, d M Y H:i:s", time() + $offset) . " GMT";
		header($expire);
	//}
	$cache = ob_get_contents();
	if (CACHING_ENABLED) {
		if (!empty($full_url) && file_exists(JAWS_DATA . 'cache')) {
			require_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Utils.php';
			if (Jaws_Utils::is_writable(JAWS_DATA . 'cache/apps')) {
				if (!file_put_contents(JAWS_DATA . "cache" . DIRECTORY_SEPARATOR . "apps" . DIRECTORY_SEPARATOR . md5($full_url), $cache)) {
					//Jaws_Error::Fatal("Couldn't create cache file.", __FILE__, __LINE__);
				}
			}
		}
	}
	ob_end_flush();
	exit;
} else {
	Jaws_Error::Fatal("No parameters were specified", __FILE__, __LINE__);
}
