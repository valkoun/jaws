<?php
/**
 * Initiates all the whole JawsApplication stuff.
 *
 * @category   Application
 * @package    Core
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Helgi Þormar <dufuz@php.net>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2005-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
// setup proper PHP settings for development
error_reporting(E_ALL & ~E_STRICT & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', true);
if (DEBUG_ACTIVATED) {
    if ($GLOBALS['logger']['method'] != 'LogToSession') {
    //if ($GLOBALS['logger']['method'] != 'LogToSession' && $GLOBALS['logger']['method'] != 'LogToStack') {
	}
	
    // Initialize the logger
    require JAWS_PATH . 'include/Jaws/Log.php';
    $GLOBALS['log'] = new Jaws_Log();
    $GLOBALS['log']->Log(JAWS_LOG_INFO, '[Jaws Start] ' . date('M/d/Y H:i:s') . ' : ' . __FILE__ . ' : ' .  __LINE__);
}
/*
if (version_compare(PHP_VERSION, '5.1.0', '>=')) {
    date_default_timezone_set('UTC');
}
*/
// for availability Jaws_Utils methods
require_once JAWS_PATH . 'include/Jaws/Utils.php';

// get our current version number
require JAWS_PATH . 'include/Jaws/Version.php';

// Get our error bling bling going.
require JAWS_PATH . 'include/Jaws/Error.php';

if (!defined('JAWS_BASE_DATA')) {
    define('JAWS_BASE_DATA', JAWS_PATH . 'data'. DIRECTORY_SEPARATOR);
}
if (!defined('JAWS_DATA')) {
    define('JAWS_DATA', JAWS_BASE_DATA);
}

if (!defined('JAWS_WIKI')) {
    define('JAWS_WIKI', 'http://jaws-project.com/index.php/faq/');
}

if (!defined('COMPRESS_LEVEL')) {
    define('COMPRESS_LEVEL', 4);
}

if (!Jaws_Utils::is_writable(JAWS_DATA)) {
    Jaws_Error::Fatal(JAWS_DATA . ' directory needs to be web writable, please set the appropiate permissions',
                     __FILE__, __LINE__);
}

// Lets support older PHP versions so we can use spanking new functions
require JAWS_PATH . 'include/Jaws/PHPFunctions.php';

// lets setup the include_path
set_include_path('.' . PATH_SEPARATOR . JAWS_PATH . 'libraries/pear');

// Lets handle our requests
require JAWS_PATH . 'include/Jaws/Request.php';
$request =& Jaws_Request::getInstance();

// Add request filters
///FIXME these should only be added in the web bootstrappers
$request->addFilter('htmlstrip', 'strip_tags');
$request->addFilter('htmlclean', 'htmlspecialchars', array(ENT_QUOTES, 'UTF-8'));

// Connect to the database
require JAWS_PATH . 'include/Jaws/DB.php';

// for fix bug in Jaws 0.7.x
if (isset($db['charset']) && $db['charset'] == 'UTF-8') {
    $db['charset'] = '';
}

$GLOBALS['db'] =& Jaws_DB::getInstance($db);
#if (Jaws_Error::IsError($GLOBALS['db'])) {
#    Jaws_Error::Fatal('Couldn\'t connect to database', __FILE__, __LINE__);
#}

// Create application
require JAWS_PATH . 'include/Jaws.php';
$GLOBALS['app'] = new Jaws();
$GLOBALS['app']->create();

if ($GLOBALS['app']->Registry->Get('/version') != JAWS_VERSION) {
    header('Location: upgrade/index.php');
}

require_once JAWS_PATH . 'include/Jaws/InitPiwi.php';
