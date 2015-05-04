<?php

if (isset($_GET['uri'])) {
	require_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'InitApplication.php';
	include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
	
	$fetch_url = false;
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
	$site_url = $GLOBALS['app']->GetSiteURL('', false, 'http');
	$site_ssl_url = $GLOBALS['app']->GetSiteURL('', false, 'https');
	foreach ($_GET as $k => $v) {
		if (strpos($k, 'uri') !== false) {
			$uri = urldecode($v);
			// Check the file type is what it says it is
			$ext = end(explode('.', strtolower($uri)));
			// file content here...
			$mime_types = array(
				// images
				'png' => 'image/png',
				'jpe' => 'image/jpeg',
				'jpeg' => 'image/jpeg',
				'pjpeg' => 'image/pjpeg',
				'jpg' => 'image/jpeg',
				'gif' => 'image/gif',
				'bmp' => 'image/bmp',
				'ico' => 'image/vnd.microsoft.icon',
				'tiff' => 'image/tiff',
				'tif' => 'image/tiff',
				'svg' => 'image/svg+xml',
				'svgz' => 'image/svg+xml',
				'jfif' => "image/pipeg", 
				'ief' => "image/ief",
				'wbmp' => "image/vnd.wap.wbmp", 
				'ras' => "image/x-cmu-raster",
				'pnm' => "image/x-x-portable-anymap",
				'pbm' => "image/x-portable-bitmap",
				'pgm' => "image/x-portable-graymap",
				'ppm' => "image/x-portable-pixmap",
				'rgb' => "image/x-rgb", 
				'xbm' => "image/x-xbitmap", 
				'xpm' => "image/x-xpixmap", 
				'xwd' => "image/x-xwindowdump",
			);
			$output = '';
			$fetch_url = false;
			if (empty($output)) {
				if (
					substr(strtolower($uri), 0, strlen(strtolower($site_url))) == $site_url || 
					substr(strtolower($uri), 0, strlen(strtolower($site_ssl_url))) == $site_ssl_url || 
					substr(strtolower($uri), 0, 26) == 'http://ajax.googleapis.com' || 
					substr(strtolower($uri), 0, 27) == 'https://ajax.googleapis.com' || 
					substr(strtolower($uri), 0, 23) == 'https://maps.google.com' || 
					substr(strtolower($uri), 0, 22) == 'http://maps.google.com'
				) {
					$uri = str_replace('https://ajax.googleapis.com', 'http://ajax.googleapis.com', $uri);
					$uri = str_replace('https://maps.google.com', 'http://maps.google.com', $uri);
					$fetch_url = true;
				}
				if ($fetch_url === true) {
					if (strpos(strtolower($uri), 'data/files/') !== false) {
						$thumb_uri = str_replace(substr($uri, strrpos($uri, '/'), strlen($uri)), '/thumb'.substr($uri, strrpos($uri, '/'), strlen($uri)), $uri);
						$medium_uri = str_replace(substr($uri, strrpos($uri, '/'), strlen($uri)), '/medium'.substr($uri, strrpos($uri, '/'), strlen($uri)), $uri);
						
						$output = '';
						$version = 'thumb_';
						$snoopy = new Snoopy($version.md5($uri));
						$snoopy->maxlength = 5000000;
						if ($snoopy->fetch($thumb_uri)) {
							if ($snoopy->status == '200' && strpos(strtolower(implode(' ',$snoopy->headers)), $mime_types[$ext]) !== false) {
								$output = $snoopy->results;
							}
						}
						if (empty($output)) {
							$version = 'medium_';
							$snoopy = new Snoopy($version.md5($uri));
							$snoopy->maxlength = 5000000;
							if ($snoopy->fetch($medium_uri)) {
								if ($snoopy->status == '200' && strpos(strtolower(implode(' ',$snoopy->headers)), $mime_types[$ext]) !== false) {
									$output = $snoopy->results;
								}
							}
						}
						if (empty($output)) {
							$version = 'image_';
							$snoopy = new Snoopy($version.md5($uri));
							$snoopy->maxlength = 5000000;
							if ($snoopy->fetch($uri)) {
								if ($snoopy->status == '200' && strpos(strtolower(implode(' ',$snoopy->headers)), $mime_types[$ext]) !== false) {
									$output = $snoopy->results;
								}
							}
						}
					}
				} else {
					Jaws_Error::Fatal("Invalid URI resource", __FILE__, __LINE__);
				}
			}
			if (!empty($output)) {
				header("Pragma: no-cache");
				header("Cache-Control: must-revalidate");
				$offset = 48 * 60 * 60;
				$expire = "Expires: " . gmdate ("D, d M Y H:i:s", time() - $offset) . " GMT";
				header($expire);

				foreach ($snoopy->headers as $header) {
					if (strpos(strtolower($header), 'content-type') !== false || strpos(strtolower($header), 'content-length') !== false) {
						header($header);
					}
				}
				echo $output;
			}
		}
	}
} else {
	Jaws_Error::Fatal("No parameters were specified", __FILE__, __LINE__);
}
