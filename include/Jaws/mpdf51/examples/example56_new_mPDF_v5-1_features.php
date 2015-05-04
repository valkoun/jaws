<?php

	//set error handling so any warnings are logged
	ini_set('error_reporting', E_ALL);
	error_reporting(E_ALL);
	ini_set('log_errors',TRUE);
	ini_set('html_errors',FALSE);
	//ini_set('error_log','/usr/local/cpanel/logs/error_log');
	ini_set('display_errors',TRUE);
	//error_log(var_export($argv, true), 0);

define('CACHING_ENABLED', false);
include("../mpdf.php");
$mpdf=new mPDF(''); 

//$mpdf->restrictColorSpace = 1;	// forces to grayscale
//==============================================================
$html = file_get_contents('example56_new_mPDF_v5-1_features_grayscale.html');
if ($_REQUEST['uri']) { 
	include_once '/usr/local/apache/htdocs/evision/include/Jaws/Snoopy.php';
	$uri = urldecode($_REQUEST['uri']);
	$uri = str_replace('&amp;', '&', $uri);
	$snoopy = new Snoopy;
	if($snoopy->fetch($uri)) {
		$html = $snoopy->results;
	}
}
//==============================================================
if ($_REQUEST['html']) { echo $html; exit; }
if ($_REQUEST['source']) { 
	$file = __FILE__;
	header("Content-Type: text/plain");
	header("Content-Length: ". filesize($file));
	header("Content-Disposition: attachment; filename='".$file."'");
	readfile($file);
	exit; 
}

//==============================================================
$mpdf->WriteHTML($html);

//==============================================================
//==============================================================
// OUTPUT
$mpdf->Output(); exit;


//==============================================================
//==============================================================
//==============================================================
//==============================================================


?>