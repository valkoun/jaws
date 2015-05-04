<?php
/**
 * Error-handling support. Set and retrieve Jaws Errors, custom error message, error level and error code.
 *
 * @category   Error
 * @category   developer_feature
 * @package    Core
 * @author     Jonathan Hernandez  <ion@suavizado.com>
 * @copyright  2005-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
define('JAWS_ERROR_INFO',    0);
define('JAWS_ERROR_WARNING', 1);
define('JAWS_ERROR_ERROR',   2);
define('JAWS_ERROR_FATAL',   3);

class Jaws_Error
{
    /**
     * Error message
     *
     * @access  protected
     * @var     string
     * @see     GetMessage()
     */
    var $_Message;

    /**
     * Error code
     *
     * @access  protected
     * @var     string
     * @see     GetCode()
     */
    var $_Code;

    /**
     * The severity of the error.
     *
     * @access  protected
     * @var     string
     * @see     GetLevel()
     */
    var $_Level;

    /**
     * Constructor
     *
     * @access  public  $message Error message
     * @param   string  $code    Error code
     * @param   int     $level   The severity level of the error.
     * @access public
     */
    function Jaws_Error($message, $code = 0, $level = JAWS_ERROR_ERROR)
    {
        $this->_Message = $message;
        $this->_Code = $code;
        $this->_Level = $level;

        if (isset($GLOBALS['log'])) {
            $GLOBALS['log']->Log(JAWS_LOG_ERR, '[' . $code . ']: ' . $message, '');
		} else if (strpos($_SERVER['SCRIPT_NAME'], '/install/') === false) {			
			require_once JAWS_PATH . 'include/Jaws/Log.php';
			$GLOBALS['log'] = new Jaws_Log();
            $GLOBALS['log']->Log(JAWS_LOG_ERR, '[' . $code . ']: ' . $message, '');
			
			if (isset($GLOBALS['app']) && strpos(strtolower($message), "the property you requested could not be found.") === false && 
				strpos(strtolower($message), "the product you requested could not be found.") === false && 
				strpos(strtolower($message), "the brand you requested could not be found.") === false && 
				strpos(strtolower($message), "the category you requested could not be found.") === false && 
				strpos(strtolower($message), "the page you requested could not be found.") === false && 
				strpos(strtolower($message), "the price of this property cannot be lower than $500,000.00.") === false && 
				strpos(strtolower($message), "the form you requested could not be found.") === false &&
				strpos(strtolower($message), "can't upload file") === false) {
				// Send e-mail notification
				require_once JAWS_PATH . 'include/Jaws/Mail.php';
				$created = $GLOBALS['db']->Date();

				$full_url = '';
				if (!isset($_SERVER['FULL_URL']) || empty($_SERVER['FULL_URL'])) {
					$scheme = (isset($_SERVER["HTTPS"]) && strtolower($_SERVER["HTTPS"]) == "on") ? "https" : "http"; 
					$port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"]); 
					$full_url = $scheme."://".$_SERVER['SERVER_NAME'].$port.$_SERVER['REQUEST_URI']; 
				} else {
					$full_url = $_SERVER['FULL_URL'];
				}
				if (!empty($full_url)) {
					$full_url = str_replace(array('www.', ':80', ':443'), '', $full_url);
				}
								
				// MySQL server gone away? Delete cache files for requested gadget, sleep 5 seconds, then reload requested page
				if (strpos(strtolower($message), "mysql server has gone away") !== false) {
					$request =& Jaws_Request::getInstance();
					$fetch = array('gadget', 'action');
					$get = $request->getRaw($fetch, 'get');
					if (!$GLOBALS['app']->deleteSyntactsCacheFile(array($get['gadget']))) {
						//Jaws_Error::Fatal("Cache file couldn't be deleted");
					} else {
						sleep(5);
						require_once JAWS_PATH . 'include/Jaws/Header.php';
						Jaws_Header::Location($full_url);
					}
				}
				
				$m_message = 'Error: [' . $code . ']: ' . $message."\n";            
				$m_message .= 'Date: ' . $created."\n";            
				$m_message .= 'Page: ' . $full_url."\n";            
				if (is_object($GLOBALS['app']) && is_object($GLOBALS['app']->Session)) {
					$username = $GLOBALS['app']->Session->GetAttribute('username');
					if (isset($username) && !empty($username)) {
						$m_message .= 'User: ' . $username.' [User ID: '. $GLOBALS['app']->Session->GetAttribute('user_id').']'."\n";            
					}
				}
				$m_message .= "-------- REQUEST INFORMATION --------\n";            
				foreach ($_GET as $get_key => $get_val) {
					$m_message .= $get_key.': ' . $get_val."\n";
				}
				foreach ($_POST as $post_key => $post_val) {
					$m_message .= $post_key.': ' . $post_val."\n";
				}
				$m_message .= "-------- SERVER INFORMATION --------\n";            
				foreach ($_SERVER as $server_key => $server_val) {
					$m_message .= $server_key.': ' . $server_val."\n";
				}
				$subject = 	"Error on: ".$GLOBALS['app']->getSiteURL(). ' : [' . $code . ']';

				$domain = strtolower(str_replace(array('http://', 'https://'), '', $GLOBALS['app']->getSiteURL()));
				$recipient = $GLOBALS['app']->Registry->Get('/network/site_email');
				
				$mail = new Jaws_Mail;
				$mail->SetHeaders($recipient, $domain ." Error!", 'noreply@'.$domain, $subject);
				$mail->AddRecipient($recipient, false, false);
				$mail->SetBody($m_message, 'text');
				$mresult = $mail->send();
			}
		}
    }

    /**
     * Returns the Error message
     *
     * @access  public
     * @return  string  Error message
     */
    function GetMessage()
    {
        return $this->_Message;
    }

    /**
     * Returns the Error code
     *
     * @access  public
     * @return  string  Error code
     */
    function GetCode()
    {
        return $this->_Code;
    }

    /**
     * Returns the error level.
     *
     * @access  public
     * @return  int     The severity level.
     */
    function GetLevel()
    {
        return $this->_Level;
    }

    /**
     * Validates if an input is a error or not
     *
     * @access  public
     * @param   mixed   $input  Input to validate(can be boolean, object, numeric, etc)
     * @return  boolean True if input is a Jaws_Error, false if not.
     */
    function IsError(&$input)
    {
        return(bool)(is_object($input) &&(strtolower(get_class($input)) == 'jaws_error'));
    }

    /**
     * Prints a Fatal Error
     *
     * @access  public
     * @param   string  $message Message to print
     * @param   string  $file    File that is calling the method
     * @param   string  $line    Line where the method is being called
     */
    function Fatal($message, $file, $line)
    {
        //FIXME: And what will happen when it's being called from a WS?
        if (isset($GLOBALS['log'])) {
            $GLOBALS['log']->Log(JAWS_LOG_ERR, "[Fatal Error]\n $message  : " . __FILE__ . ':' . __LINE__);
        }

        if (defined('DEBUG_ACTIVATED') && DEBUG_ACTIVATED && $GLOBALS['logger']['method'] != 'LogToStack') {
            echo '<b style="color: #f00;"> JAWS Fatal Error:</b><br /><b>Page: </b>' .
                $file . "<br /><b>Line: </b>" . $line . "<br />" . $message;
			if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->LogStackToScreen();
            }
        } else {
            //Get content
            $content = file_get_contents(JAWS_PATH . 'gadgets/ControlPanel/templates/FatalError.html');
            $content = str_replace('{message}', $message, $content);
            echo $content;            
            exit;
        }

        exit;
    }
}