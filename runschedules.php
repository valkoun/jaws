<?php
ini_set("memory_limit","384M");
ini_set("post_max_size","25M");
ini_set("upload_max_filesize","2M");
ini_set("max_execution_time","0");

/*
//set error handling so any warnings are logged
ini_set('error_reporting', E_ALL);
error_reporting(E_ALL);
ini_set('log_errors',FALSE);
ini_set('html_errors',FALSE);
ini_set('display_errors',TRUE);
*/

require_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'InitApplication.php';

ob_flush();
flush();  // worked without ob_flush() for me
sleep(1);

// Enter any custom script you want to schedule...

$site_id = $GLOBALS['app']->GetSiteURL();
if (empty($site_id)) {
	$site_id = $GLOBALS['app']->Registry->Get('/config/site_name');
}
if (empty($site_id)) {
	$site_id = JAWS_PATH;
}

echo "<noscript><h1>Success</h1> '".$site_id."' was backed up successfully</noscript>";
