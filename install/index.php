<?php
/**
 * Jaws Installer System
 *
 * @category   Application
 * @package    Install
 * @author     Jon Wood <jon@substance-it.co.uk>
 * @author     Helgi Þormar Þorbjörnsson <dufuz@php.net>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2005-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
/* Dummy way for developers to get the errors
 * Turn off when releasing.
 */
define('DEBUG', true);
if (DEBUG) {
    ini_set('display_errors', true);
    error_reporting(E_ALL & ~E_STRICT & ~E_NOTICE & ~E_WARNING);
}

define('CACHING_ENABLED', false);
define('JAWS_SCRIPT', 'install');
define('BASE_SCRIPT', 'install/index.php');
define('APP_TYPE',    'web');

if (!defined('JAWS_WIKI')) {
    define('JAWS_WIKI', 'http://jaws-project.com/index.php/faq/');
}

$site_url = array();
$site_url['scheme'] = (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on' ? 'https' : 'http');
$host = $_SERVER['SERVER_NAME'];
$site_url['host'] = $host;
$site_url['port'] = (isset($_SERVER["SERVER_PORT"]) && ((int)$_SERVER["SERVER_PORT"] == 80 || (int)$_SERVER["SERVER_PORT"] == 443) ? '' : (isset($_SERVER["SERVER_PORT"]) ? ':'.$_SERVER["SERVER_PORT"] : ''));
$path = strip_tags($_SERVER['PHP_SELF']);
if (false === strpos($path, BASE_SCRIPT)) {
	$path = strip_tags($_SERVER['SCRIPT_NAME']);
}
$site_url['path'] = substr($path, 0, strpos($path, BASE_SCRIPT)-1);

$jaws_site_url = $site_url['path'];
$jaws_site_url = $site_url['scheme'] . '://' . $site_url['host'] . (isset($site_url['port']) && $site_url['port'] != '' ? $site_url['port'] : '') . $jaws_site_url;

if (substr($jaws_site_url, -1) == '/') {
	$jaws_site_url = substr($jaws_site_url, 0, -1);
}

session_start();

if (isset($_GET['reset'])) {
	log_install("Resetting Installation");
    unset($_SESSION['install']);
    header('Location: index.php');
    exit;
}

if (version_compare(PHP_VERSION, '5.1.0', '>=')) {
    date_default_timezone_set('UTC');
}

define('JAWS_PATH', DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'local' . DIRECTORY_SEPARATOR . 'apache' . DIRECTORY_SEPARATOR . 'htdocs' . DIRECTORY_SEPARATOR . 'evision' . DIRECTORY_SEPARATOR);

// lets setup the include_path
set_include_path('.' . PATH_SEPARATOR . JAWS_PATH . 'libraries/pear');

// this variables currently temporary util we complete multible instance installing
define('JAWS_BASE_DATA', dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'data'. DIRECTORY_SEPARATOR);
define('JAWS_DATA', JAWS_BASE_DATA);
define('INSTALL_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR);

// Lets support older PHP versions so we can use spanking new functions
require_once JAWS_PATH . 'include/Jaws/PHPFunctions.php';

require_once JAWS_PATH . 'include/Jaws/Error.php';
require_once JAWS_PATH . 'include/Jaws/Version.php'; 
require_once JAWS_PATH . 'include/Jaws/Utils.php';
require_once JAWS_PATH . 'include/Jaws/GadgetInfo.php';

if (!isset($_SESSION['install'])) {
    $_SESSION['install'] = array('stage' => 0, 'lastStage' => array());
}

// Lets handle our requests
require JAWS_PATH . 'include/Jaws/Request.php';
$request =& Jaws_Request::getInstance();
$lang = $request->get('language', 'post');
if (isset($lang)) {
    $_SESSION['install']['language'] = urlencode($lang);
} elseif (!isset($_SESSION['install']['language'])) {
    $_SESSION['install']['language'] = 'en';
}

include_once JAWS_PATH . 'include/Jaws/Translate.php';
$GLOBALS['i10n'] = new Jaws_Translate();
if (isset($_SESSION['install']['language'])) {
    $GLOBALS['i10n']->SetLanguage($_SESSION['install']['language']);
}
$GLOBALS['i10n']->LoadTranslation('Global');
$GLOBALS['i10n']->LoadTranslation('Install');

if (!function_exists('log_install')) {
    function log_install($msg) { 
        static $log;
        if (
			!isset($_SESSION['use_log']) && 
			((!isset($data['Introduction']['skip']) || $data['Introduction']['skip'] !== '1')
			&& (!isset($_SESSION['install']['data']['Introduction']['skip']) || $_SESSION['install']['data']['Introduction']['skip'] !== '1'))
		) {
            return;
        }
        
        if (
			$_SESSION['use_log'] == 'yes' || ((isset($data['Introduction']['skip']) && $data['Introduction']['skip'] === '1')
			|| (isset($_SESSION['install']['data']['Introduction']['skip']) && $_SESSION['install']['data']['Introduction']['skip'] === '1'))
		) {
            if (!isset($log)) {
                //Enable the log
                require_once JAWS_PATH . 'include/Jaws/Log.php';
                $log = new Jaws_Log;
            }
            $logfile = JAWS_DATA .'logs/install.log';
            $log->LogToFile('LOG_DEBUG', $msg, array('file' => $logfile));
        }
    }
}

require_once 'stagelist.php';
require_once 'JawsInstaller.php';
require_once 'JawsInstallerStage.php';

$installer = new JawsInstaller();
$installer->loadStages($stages);
$stages = $installer->getStages();
$stage  = $stages[$_SESSION['install']['stage']];

$stageobj = $installer->loadStage($stage);
$stages_count = count($stages);

$_SESSION['install']['predefined'] = $predefined = $installer->hasPredefined();
$_SESSION['install']['data'] = $data = $installer->getPredefinedData();

$skip = false;
if (
    ($predefined && isset($data[$stage['file']]['skip']) && $data[$stage['file']]['skip'] === '1')
    || (isset($_SESSION['install'][$stage['file']]['skip']) && $_SESSION['install'][$stage['file']]['skip'] === '1')
) {
    $skip = true;
    // Fake a next button push
    $auto_next_step = true;
}

$go_next_step = $request->get($stage['file'] . '_complete', 'post');
// Only attempt to validate if the next button has been hit.
if (isset($go_next_step) || isset($auto_next_step)) {
    $result = $stageobj->validate();
    if (!Jaws_Error::isError($result)) {
        $result = $stageobj->run();

        if (!Jaws_Error::isError($result)) {
            if ($_SESSION['install']['stage'] < $stages_count - 1) {
                $_SESSION['install']['stage']++;
                $stageobj = $installer->loadStage($stages[$_SESSION['install']['stage']]);
            }

            $result = null;
        }
    }

    $GLOBALS['message'] = $result;
}
log_install("Total Stages: ".($stages_count-1));
log_install("Last Stage: ".$_SESSION['install']['lastStage']);
log_install("Current Stage: ".$_SESSION['install']['stage']);
// Mark the stage as having been run.
$_SESSION['install']['lastStage'] = $_SESSION['install']['stage'];

if (!isset($GLOBALS['message']) && $skip) {
    header('Location: index.php');
}

include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Template.php';
include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
$snoopy = new Snoopy;
if($snoopy->fetch(str_replace(':80', '', $jaws_site_url).'/install/templates/page.html')) {
	// Defines where the layout template should be loaded from.
	$theme = 'default';
	$direction = _t('GLOBAL_LANG_DIRECTION');
	$dir  = $direction == 'rtl' ? '.' . $direction : '';
	
	// Display the page
	$page = new Jaws_Template(JAWS_BASE_DATA . 'themes/' . $theme);
	$page->Load('layout.html', false, false);
	$page->SetBlock('layout');
	
	// Basic setup
	$page->SetVariable('BASE_URL', Jaws_Utils::getBaseURL(BASE_SCRIPT).'/install/');
	$page->SetVariable('.dir', $dir);
	$page->SetVariable('THEME', '../data/themes/' . $theme . '/');
	$page->SetVariable('site-title', 'Jaws ' . JAWS_VERSION);
	$page->SetVariable('site-name',  'Jaws ' . JAWS_VERSION);
	$page->SetVariable('site-slogan', _t('INSTALL_NEW_INSTALLATION'));
	$page->SetVariable('layout-mode', 0);
	$page->SetVariable('powered-by', 'Jaws Project');

	// Load the stylesheet
	$page->SetBlock('layout/head');
	$page->SetVariable('ELEMENT', '<link rel="stylesheet" type="text/css" href="resources/install'.$dir.'.css" />');
	$page->ParseBlock('layout/head');
	
	// Load js files
	$page->SetBlock('layout/head');
	$page->SetVariable('ELEMENT', '<script type="text/javascript" src="http://jaws-project.com/libraries/js/bigint.js"></script>');
	$page->ParseBlock('layout/head');
	$page->SetBlock('layout/head');
	$page->SetVariable('ELEMENT', '<script type="text/javascript" src="http://jaws-project.com/libraries/js/bigintmath.js"></script>');
	$page->ParseBlock('layout/head');
	$page->SetBlock('layout/head');
	$page->SetVariable('ELEMENT', '<script type="text/javascript" src="http://jaws-project.com/libraries/js/rsa.js"></script>');
	$page->ParseBlock('layout/head');
	$page->SetBlock('layout/head');
	$page->SetVariable('ELEMENT', '<script type="text/javascript" src="http://jaws-project.com/libraries/js/admin.js"></script>');
	$page->ParseBlock('layout/head');

	// Display the stage
	$page->SetBlock('layout/main');

	$tpl = new Jaws_Template();
	$tpl->LoadFromString($snoopy->results);
	$tpl->SetBlock('page');
	$tpl->SetVariable('title', $stages[$_SESSION['install']['stage']]['name']);
	$tpl->SetVariable('body',  $stageobj->display());
	$tpl->SetVariable('stage', $stages[$_SESSION['install']['stage']]['file']);
	
	if (!$predefined) {
		$tpl->SetBlock('page/stagelist');
		foreach ($stages as $key => $stage) {
		    if ($key < $_SESSION['install']['stage']) {
		        $tpl->SetBlock('page/completed_stage');
		        $tpl->SetVariable('name', $stage['name']);
		        $tpl->ParseBlock('page/completed_stage');
		    } elseif ($key == $_SESSION['install']['stage']) {
		        $tpl->SetBlock('page/current_stage');
		        $tpl->SetVariable('name', $stage['name']);
		        $tpl->ParseBlock('page/current_stage');
		    } else {
		        $tpl->SetBlock('page/stage');
		        $tpl->SetVariable('name', $stage['name']);
		        $tpl->ParseBlock('page/stage');
		    }
		}
		$tpl->ParseBlock('page/stagelist');
	} else {
		$tpl->SetBlock('page/hide_stagelist');
		$tpl->ParseBlock('page/hide_stagelist');
	}
	
	// Check if we are on the last stage, Key + 1 because an array starts with 0 :-)
	if (($_SESSION['install']['stage'] + 1) == $stages_count) {
	    // Kill of the session cookie (path cookie in FF)
	    unset($_SESSION['install']);
	}
	
	if (isset($GLOBALS['message'])) {
	    switch ($GLOBALS['message']->getLevel()) {
	        case JAWS_ERROR_INFO:
	            $type = 'info';
	            break;
	        case JAWS_ERROR_WARNING:
	            $type = 'warning';
	            break;
	        case JAWS_ERROR_ERROR:
	            $type = 'error';
	            break;
	    }
	
	    $tpl->setBlock('page/message');
	    $tpl->setVariable('text', $GLOBALS['message']->getMessage());
	    $tpl->setVariable('type', $type);
	    $tpl->parseBlock('page/message');
	}
	$tpl->ParseBlock('page');
	
	$page->SetVariable('ELEMENT', $tpl->Get());
	$page->ParseBlock('layout/main');
	$page->ParseBlock('layout');

	echo $page->Get();
}