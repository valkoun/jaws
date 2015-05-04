<?php
/**
 * Jaws Upgrade System
 *
 * @category   Application
 * @package    Upgrade
 * @author     Jon Wood <jon@substance-it.co.uk>
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Helgi �ormar �orbj�rnsson <dufuz@php.net>
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
    error_reporting(E_ALL);
}

define('JAWS_SCRIPT', 'upgrade');
define('BASE_SCRIPT', 'upgrade/index.php');
define('APP_TYPE',    'web');

if (!defined('JAWS_WIKI')) {
    define('JAWS_WIKI', 'http://jaws-project.com/index.php/faq/');

}

session_start();

if (isset($_GET['reset'])) {
    unset($_SESSION['upgrade']);
    header('Location: index.php');
    exit;
}

if (version_compare(PHP_VERSION, '5.1.0', '>=')) {
    date_default_timezone_set('UTC');
}

require dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'config/JawsConfig.php';
// lets setup the include_path
set_include_path('.' . PATH_SEPARATOR . JAWS_PATH . 'libraries/pear');
if (!defined('JAWS_BASE_DATA')) {
    define('JAWS_BASE_DATA', JAWS_PATH . 'data'. DIRECTORY_SEPARATOR);
}
if (!defined('JAWS_DATA')) {
    define('JAWS_DATA', JAWS_BASE_DATA);
}

define('UPGRADE_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR);

// Lets support older PHP versions so we can use spanking new functions
require_once JAWS_PATH . 'include/Jaws/PHPFunctions.php';

require_once JAWS_PATH . 'include/Jaws/Error.php';
require_once JAWS_PATH . 'include/Jaws/Version.php';
require_once JAWS_PATH . 'include/Jaws/Utils.php';
require_once JAWS_PATH . 'include/Jaws/GadgetInfo.php';

if (!isset($_SESSION['upgrade'])) {
    $_SESSION['upgrade'] = array('stage' => 0, 'lastStage' => array());
}

// Lets handle our requests
require JAWS_PATH . 'include/Jaws/Request.php';
$request =& Jaws_Request::getInstance();
$lang = $request->get('language', 'post');
if (isset($lang)) {
    $_SESSION['upgrade']['language'] = urlencode($lang);
} elseif (!isset($_SESSION['upgrade']['language'])) {
    $_SESSION['upgrade']['language'] = 'en';
}

include_once JAWS_PATH . 'include/Jaws/Translate.php';
$GLOBALS['i10n'] = new Jaws_Translate();
if (isset($_SESSION['upgrade']['language'])) {
    $GLOBALS['i10n']->SetLanguage($_SESSION['upgrade']['language']);
}
$GLOBALS['i10n']->LoadTranslation('Global');
$GLOBALS['i10n']->LoadTranslation('Upgrade');

if (!function_exists('log_upgrade')) {
    function log_upgrade($msg) { 
        static $log;
        
        if (!isset($_SESSION['use_log'])) {
            return;
        }
        
        if ($_SESSION['use_log'] == 'yes') {
            if (!isset($log)) {
                //Enable the log
                require_once JAWS_PATH . 'include/Jaws/Log.php';
                $log = new Jaws_Log;
            }
            $logfile = JAWS_DATA .'logs/upgrade.log';
            $log->LogToFile('LOG_DEBUG', $msg, array('file' => $logfile));
        }
    }
}

require_once 'stagelist.php';
require_once 'JawsUpgrader.php';
require_once 'JawsUpgraderStage.php';

$upgrader = new JawsUpgrader($db);
$upgrader->loadStages($stages);
$stages = $upgrader->getStages();
$stage = $stages[$_SESSION['upgrade']['stage']];

$stageobj = $upgrader->loadStage($stage);
$stages_count = count($stages);

$go_next_step = $request->get($stage['file'] . '_complete', 'post');
// Only attempt to validate if the next button has been hit.
if (isset($go_next_step)) {
    $result = $stageobj->validate();
    if (!Jaws_Error::isError($result)) {
        $result = $stageobj->run();

        if (!Jaws_Error::isError($result)) {
            if ($_SESSION['upgrade']['stage'] < $stages_count - 1) {
                $_SESSION['upgrade']['stage']++;
                $stageobj = $upgrader->loadStage($stages[$_SESSION['upgrade']['stage']]);
            }

            $result = null;
        }
    }

    $GLOBALS['message'] = $result;
}

// Mark the stage as having been run.
$_SESSION['upgrade']['lastStage'] = $_SESSION['upgrade']['stage'];

include_once JAWS_PATH . 'include/Jaws/Template.php';
$tpl = new Jaws_Template('templates');
$tpl->Load('page.html', false, false);
$tpl->SetBlock('page');
$tpl->SetVariable('title', $stages[$_SESSION['upgrade']['stage']]['name']);
$tpl->SetVariable('body',  $stageobj->display());
$tpl->SetVariable('stage', $stages[$_SESSION['upgrade']['stage']]['file']);

foreach ($stages as $key => $stage) {
    if ($key < $_SESSION['upgrade']['stage']) {
        $tpl->SetBlock('page/completed_stage');
        $tpl->SetVariable('name', $stage['name']);
        $tpl->ParseBlock('page/completed_stage');
    } elseif ($key == $_SESSION['upgrade']['stage']) {
        $tpl->SetBlock('page/current_stage');
        $tpl->SetVariable('name', $stage['name']);
        $tpl->ParseBlock('page/current_stage');
    } else {
        $tpl->SetBlock('page/stage');
        $tpl->SetVariable('name', $stage['name']);
        $tpl->ParseBlock('page/stage');
    }
}

// Check if we are on the last stage, Key + 1 because an array starts with 0 :-)
if (($_SESSION['upgrade']['stage'] + 1) == $stages_count) {
    // Kill of the session cookie (path cookie in FF)
    unset($_SESSION['upgrade']);
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

// Defines where the layout template should be loaded from.
$theme = 'default';
$direction = _t('GLOBAL_LANG_DIRECTION');
$dir  = $direction == 'rtl' ? '.' . $direction : '';

// Display the page
$page = new Jaws_Template(JAWS_BASE_DATA . 'themes/' . $theme);
$page->Load('layout.html', false, false);
$page->SetBlock('layout');

// Basic setup
$page->SetVariable('BASE_URL', Jaws_Utils::getBaseURL(BASE_SCRIPT).'/upgrade/');
$page->SetVariable('.dir', $dir);
$page->SetVariable('THEME', '../data/themes/' . $theme . '/');
$page->SetVariable('site-title', 'Jaws ' . JAWS_VERSION);
$page->SetVariable('site-name',  'Jaws ' . JAWS_VERSION);
$page->SetVariable('site-slogan', _t('UPGRADE_INTRO_WELCOME'));
$page->SetVariable('layout-mode', 0);
$page->SetVariable('powered-by', 'Jaws Project');

// Load the stylesheet
$page->SetBlock('layout/head');
$page->SetVariable('ELEMENT', '<link rel="stylesheet" type="text/css"  href="resources/upgrade'.$dir.'.css" />');
$page->ParseBlock('layout/head');

// Load js files
$page->SetBlock('layout/head');
$page->SetVariable('ELEMENT', '<script type="text/javascript" src="../libraries/js/bigint.js"></script>');
$page->ParseBlock('layout/head');
$page->SetBlock('layout/head');
$page->SetVariable('ELEMENT', '<script type="text/javascript" src="../libraries/js/bigintmath.js"></script>');
$page->ParseBlock('layout/head');
$page->SetBlock('layout/head');
$page->SetVariable('ELEMENT', '<script type="text/javascript" src="../libraries/js/rsa.js"></script>');
$page->ParseBlock('layout/head');

// Display the stage
$page->SetBlock('layout/main');
$page->SetVariable('ELEMENT', $tpl->Get());
$page->ParseBlock('layout/main');
$page->ParseBlock('layout');

echo $page->Get();