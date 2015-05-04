<!-- BEGIN JawsConfig --><?php
/**
 * JawsConfig.php - Configuration variables
 *
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2004-2010 Jaws Development Group
 */
// Path where Jaws is installed
define('JAWS_PATH', {jaws_path});
define('JAWS_DATA',  dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'data'. DIRECTORY_SEPARATOR);
define('SYNTACTS_DB',    '_syntacts_');
define('CACHING_ENABLED', true);
<!-- BEGIN jaws_urls -->
define('JAWS_SSL_URL', '{jaws_ssl_url}');
define('JAWS_URL', '{jaws_url}');
<!-- END jaws_urls -->
<!-- BEGIN jaws_data_urls -->
define('JAWS_DATA_SSL_URL', '{jaws_data_ssl_url}');
define('JAWS_DATA_URL', '{jaws_data_url}');
<!-- END jaws_data_urls -->

$db = array(); //DONT RENAME/DELETE THIS VARIABLE!!
/**
 * DB Configuration
 *
 * In this section you configure some params of your DB connection, such as
 * username, password, name, host and driver.
 * The prefix is optional, just make sure it has an empty value
 */
$db['driver']   = '{db_driver}';
$db['host']     = '{db_host}';
$db['port']     = '{db_port}';
$db['user']     = '{db_user}';
$db['password'] = '{db_pass}';
$db['isdba']    = '{db_isdba}';
$db['path']     = '{db_path}';
$db['name']     = '{db_name}';
$db['prefix']   = '{db_prefix}';

/**
 * Logs
 *
 * If you want to enable logging Jaws, maybe to track the errors, or to debug a good
 * idea is to configure/enable it.
 */
/**
 * Debug: true/false
 *
 * Warning: This will turn on the Debugger and will show all the error and warning messages in your
 * website, so any user that visits your site will see information that they shouldn't see
 */
define('DEBUG_ACTIVATED', false);

/**
 * Log Method
 *
 * How do you want to print/save the log?. Currently we just support:
 *
 *    LogToStack: Saves the log in an array, every time you reload the site, its created once again (DEFAULT).
 *     Example:
 *        $GLOBALS['logger']['method'] = 'LogToStack';
 *
 *    LogToFile: Logs the message to a specified file.
 *     Options:
 *      file (required): File where you want to save data, IMPORTANT. Apache needs write-access to that file
 *      maxlines (optional): How many lines will contain the file. Default = 500
 *      rotatelimit (optional): How many rotated files will be created (i.e. jaws.log.1, jaws.log.2 etc). Default = 1
 *     Example:
 *        $GLOBALS['logger']['method'] = 'LogToFile';
 *        $GLOBALS['logger']['options'] = array();
 *        $GLOBALS['logger']['options']['file'] = "/tmp/jaws.log";
 *        $GLOBALS['logger']['options']['maxlines'] = 500;
 *        $GLOBALS['logger']['options']['rotatelimit'] = 1;
 *
 *
 *    LogToSyslog: Logs the message to the syslog, you can find the log of this blog just by looking to the tag you
 *    define
 *      Options:
 *       indent: String ident is added to each message. Default: "Jaws_Log"
 *      Example:
 *        $GLOBALS['logger']['method'] = 'LogToSyslog';
 *        $GLOBALS['logger']['options'] = array();
 *        $GLOBALS['logger']['options']['indent'] = 'Jaws_Log';
 *
 *    LogToScreen: All log messages are printed to screen
 *       Example:
 *        $GLOBALS['logger']['method'] = 'LogToScreen';
 *
 *    LogToApache': Prints the message to the apache error log file
 *       Example:
 *        $GLOBALS['logger']['method'] = 'LogToApache';
 *
 *    LogToFirebug: Prints the messages into the Firebugs console (The firebug extensions is required)
 *       Example:
 *        $GLOBALS['logger']['method'] = 'LogToFirebug';
 */

//$GLOBALS['logger']['method'] = 'LogToStack';
        $GLOBALS['logger']['method'] = 'LogToFile';
        $GLOBALS['logger']['options'] = array();
        $GLOBALS['logger']['options']['file'] = JAWS_DATA ."logs/jaws.log";
        $GLOBALS['logger']['options']['maxlines'] = 20000;
        $GLOBALS['logger']['options']['rotatelimit'] = 1;

<!-- END JawsConfig -->
