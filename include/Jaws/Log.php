<?php
/**
 * Logging support. Set and retrieve entries in the log (screen, syslog, logdb, etc)
 *
 * @category   Log
 * @category   developer_feature
 * @package    Core
 * @author     Ivan Chavero <imcsk8@gluch.org.mx>
 * @author     Jorge A Gallegos <kad@gulags.org>
 * @copyright  2004-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
 
// /log/facilities/LOG_INFO/method = LogToFile
// /log/facilities/LOG_INFO/opt = /path/to/logfile.log
// /log/facilities/LOG_WARN/method = LogToScreen

define('JAWS_LOG_EMERG',       'LOG_EMERG');   /* system is unusable */
define('JAWS_LOG_ALERT',       'LOG_ALERT');   /* action must be taken immediately */
define('JAWS_LOG_CRIT',        'LOG_CRIT');    /* critical conditions */
define('JAWS_LOG_ERR',         'LOG_ERR');     /* error conditions */
define('JAWS_LOG_WARNING',     'LOG_WARNING'); /* warning conditions */
define('JAWS_LOG_NOTICE',      'LOG_NOTICE');  /* normal but significant condition */
define('JAWS_LOG_INFO',        'LOG_INFO');    /* informational */
define('JAWS_LOG_DEBUG',       'LOG_DEBUG');   /* debug-level messages */
define('Jaws_LogDefaultMethod', 'LogToStack');  /* default log method */
define('Jaws_LogDefaultOption', '');            /* default log option */

class Jaws_Log
{
    /**
     * The logger
     *
     * @var     string
     * @access  private
     */
    var $_Method;

    /**
     * The stack of messages
     *
     * @var    string
     * @access private
     * @see    GetMessageStack()
     */
    var $_MessageStack;

    /**
     * Information about the module
     *
     * @access  public
     */
    function Jaws_Log()
    {
        $this->_MessageStack = '';
    }

    /**
     * This is the only function that is called from an instance
     * it recieves the facility and identifies it on the registry
     * then takes the method and the options and execute it.
     * if the facility does not exist we use the LogToScreen method
     * and show a unknown facilty message
     *
     * @access  public
     * @param   string  $facility  How to log
     * @param   string  $msg       Message to log
     */
    function Log($facility, $msg)
    {
        $opts   = '';
        if (isset($GLOBALS['logger'])) {
            $this->_Method = $GLOBALS['logger']['method'];
            if (isset($GLOBALS['logger']['options'])) {
                $opts = $GLOBALS['logger']['options'];
            }
        }
        // We can use any method and default it to stack
        // in case there is no method record on the registry
        if (empty($this->_Method)) {
            $this->_Method = Jaws_LogDefaultMethod;
        }

        if (empty($opts)) {
            $opts = Jaws_LogDefaultOption;
        }

        $method = $this->_Method;
        $this->$method($facility, $msg, $opts);
    }

    /**
     * This function prints a variable in a human readable form to the log
     * facility specified
     *
     * @access public
     * @param  $mixed mixed Object to display
     */
    function VarDump($mixed=null)
    {
        ob_start();
        var_dump($mixed);
        $content = ob_get_contents();
        ob_end_clean();
        $this->Log(JAWS_LOG_DEBUG, "\n<pre>\n" . $content . "</pre>");
    }

    /**
     * Logs the message to a file especified on the dest parameter
     *
     * @access  public
     * @param   string  $facility  How to log
     * @param   string  $msg       Message to log
     * @param   string  $dest      File to log
     */
    function LogToFile($facility, $msg, $opts)
    {
        if (isset($opts['file'])) {
            $logfile = $opts['file'];
        } else {
            trigger_error("You need to set at least the filename for Jaws_Log::LogToFile", E_USER_ERROR);
        }

        if (isset($opts['maxlines'])) {
            $maxlines = $opts['maxlines'];
        } else {
            $maxlines = 500;
        }

        if (isset($opts['rotatelimit'])) {
            $logrotate_limit = $opts['rotatelimit'];
        } else {
            $logrotate_limit = 1;
        }

        if (file_exists($logfile)) {
			$numlines = 0;

			if ($fh = fopen($logfile, 'r')) {
				while (!feof($fh)) {
					if (fgets($fh)) {
						$numlines++;
					}
				}
			}
            //$numlines = count(file($logfile));
            if ($numlines >= $maxlines) {
                if (file_exists($logfile . '.' . $logrotate_limit)) {
                    unlink($logfile . '.' . $logrotate_limit);
                }

                for ($i = $logrotate_limit - 1; $i > 0; $i--) {
                    $new = $i + 1;
                    // This won't work if you put the unlink in the if and no else.
                    // need to put the full process logic
                    if (file_exists($logfile . '.' . $new)){
                        unlink($logfile . '.' . $new);
                        rename($logfile . '.' . $i, $logfile . '.' . $new);
                    } else{
                        rename($logfile . '.' . $i, $logfile . '.' . $new);
                    }
                }
                rename($logfile, $logfile . '.1');
            }
        }
        $fh = fopen($logfile, 'a+');
        fwrite($fh, $this->SetLogStr($facility, $msg) . "\n");
        fclose($fh);
    }

    /**
     * Logs the message to syslog
     *
     * @access  public
     * @param   string  $facility  How to log
     * @param   string  $msg       Message to log
     * @param   string  $opt       Some options
     */
    function LogToSyslog($facility, $msg, $opt)
    {
        @define_syslog_variables();
        $indent = 'Jaws_Log';
        if (isset($opt['indent'])) {
            $indent = $opt['indent'];
        }
        openlog($indent, LOG_PID | LOG_PERROR, LOG_LOCAL0);
        syslog((int)$facility, $msg);
        closelog();
    }

    /**
     * prints the message to screen
     *
     * @access  public
     * @param   string  $facility  How to log
     * @param   string  $msg       Message to log
     * @param   string  $opt       Some options
     */
    function LogToScreen($facility, $msg, $opt)
    {
        print $this->SetLogStr($facility, $msg);
    }

    /**
     * dump the messages into the FireBug extension
     *
     * @access  public
     * @param   string  $facility   How to log
     * @param   string  $msg        Message to log
     * @param   string  $opt        Some options
     */
    function LogToFirebug($facility, $msg, $opt)
    {
        switch($facility) {
            case JAWS_LOG_EMERG:
            case JAWS_LOG_ALERT:
            case JAWS_LOG_CRIT:
            case JAWS_LOG_ERR:
                $console_method = 'error';
                break;
            case JAWS_LOG_NOTICE:
            case JAWS_LOG_INFO:
                $console_method = 'info';
                break;
            case JAWS_LOG_WARNING:
                $console_method = 'warn';
                break;
            case JAWS_LOG_DEBUG:
                $console_method = 'debug';
                break;
        }

        $now = strftime('%a %b %d %T,'.$this->Milliseconds().' %Y', time());
        $msg = str_replace("\r\n", "\n", $msg);
        $msg = str_replace("\n", "\\n\\\n", $msg);
        $msg = str_replace('"', '\\"', $msg);

        $this->_MessageStack = $this->_MessageStack . "\n-" . 'console.' . $console_method . '("[' . $now . ']\n' . $msg . '");';
    }

    /**
     * prints the message to the apache error log file
     *
     * @access  public
     * @param   string  $facility  How to log
     * @param   string  $msg       Message to log
     * @param   string  $opt       Some options
     */
    function LogToApache($facility, $msg, $opt)
    {
        switch ($facility){
            case JAWS_LOG_ERR:
            case JAWS_LOG_WARNING:
                $error_level = E_USER_WARNING;
                break;
            default:
                $error_level = E_USER_NOTICE;
                break;
        }
        trigger_error($this->SetLogStr($facility, $msg), $error_level);
    }


    /**
     * put the message into a message stack
     * originally it was an array but i think that a
     * flat variable should do
     *
     * @access  public
     * @param   string  $facility  How to log
     * @param   string  $msg       Message to log
     * @param   string  $opt       Some options
     */
    function LogToStack($facility, $msg, $opt)
    {
        $this->_MessageStack = $this->_MessageStack . "\n-" . $this->SetLogStr($facility, $msg);
    }
    /**
     * put the message into a session variable
     *
     * @access  public
     * @param   string  $facility  How to log
     * @param   string  $msg       Message to log
     * @param   string  $opt       Some options
     */
    function LogToSession($facility, $msg, $opt)
    {
        $this->_MessageStack = $this->_MessageStack . "\n-" . $this->SetLogStr($facility, $msg);
		if (isset($GLOBALS['app']) && isset($GLOBALS['app']->Session)) {
			$GLOBALS['app']->Session->_Attributes['session_log'] .= $this->_MessageStack;
		}
	}

    /**
     * Get the message stack
     * whe should use it like this:
     * $this->Log(JAWS_LOG_DEBUG,$this->GetMessageStack);
     *
     * @access  public
     * @return  string the Stack of messages
     */
    function GetMessageStack()
    {
        return $this->_MessageStack;
    }

    /**
     * Formats the message to be printed.
     * appends the date and the facility to the message
     *
     * @access  private
     * @param   string  $facility  How to log
     * @param   string  $msg       Message to log
     * @return  string  The message already prepared to be logged(parsed)
     */
    function SetLogStr($facility, $msg)
    {
		$now = strftime('%a %b %d %H:%M:%S,'.$this->Milliseconds().' %Y', time());
        $log_str = '[' . $now . ']::[' . $facility . ']::' . $msg;
        return $log_str;
    }

    /**
     * getting precise log time
     *
     * @access  private
     * @return  integer milliseconds
     */
    function Milliseconds()
    {
        $result = microtime();
        $result = substr($result, 0, strpos($result, ' '));
        $result = substr($result, 2, 3);
        return $result;
    }

    /**
     * Parse the stack and give it a nice format
     *
     * @access  private
     * @return  string   a HTML with the log
     */
    function StackToTable()
    {
        $log = '<h1>Jaws Log ['.date('M/d/Y H:i:s').']</h1>' . "\n";
        $log .= '<table border="1" align="center" cellspacing="0" cellpadding="2" id="debug-table">' . "\n";

        $l = preg_split('/\n-/', $this->_MessageStack);
        foreach ($l as $line) {
            if (!empty($line)) {
                $elem = preg_split('/::/', $line);
                $log .= ' <tr>' . "\n" .
                    '  <td valign="top">' . $elem[0] . '</td>' . "\n" .
                    '  <td valign="top">' . $elem[1] . '</td>' . "\n" .
                    '  <td valign="top"><pre>' . $elem[2] . '</pre></td>' . "\n" .
                    ' </tr>' . "\n";
            }
        }

        $log .= '</table>' . "\n";

        return $log;
    }

    /**
     * Gives the stack in Firebug's favor format
     *
     * @access  private
     * @return  string  JavaScript stuff
     */
    function StackToFirebug()
    {
        print '<script type="text/javascript">';
        print "\nif (('console' in window) || ('firebug' in console)) {\n";
        $l = preg_split('/\n-/', $this->_MessageStack);
        foreach ($l as $line) {
            print $line."\n";
        }
        print "\n}\n";
        print "</script>";
    }
	
    /**
     * Empty Lines
     *
     * @access  private
     * @return  string  JavaScript stuff
     */
    function StackToEmpty()
    {
        $l = preg_split('/\n-/', $this->_MessageStack);
        print "\n<!-- \n";
        foreach ($l as $line) {
            print ".";
        }
        print "\n -->\n";
    }

    /**
     * prints the message stack to the screen
     *
     * @access  private
     * @return  string  The stack of messages
     */
    function LogStackToScreen()
    {
        switch($this->_Method) {
            case 'LogToSession':
                $log = $this->StackToEmpty();
                break;
            case 'LogToStack':
                $log = $this->StackToTable();
                break;
            case 'LogToFirebug':
                $log = $this->StackToFirebug();
                break;
        }
        print $log;
    }
}
?>
