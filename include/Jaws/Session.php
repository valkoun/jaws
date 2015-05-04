<?php
/**
 * Manage user session. Authenticate, login/logout, and store/retrieve custom session data.
 *
 * @category   Session
 * @category   developer_feature
 * @package    Core
 * @author     Ivan -sk8- Chavero <imcsk8@gluch.org.mx>
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2004-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
define('SESSION_ERROR_SYNC', "Can't sync session");
define('SESSION_ERROR_MULTSESSIONS', "Multiple sessions in DB");
define('SESSION_RESERVED_ATTRIBUTES', "session_id,type,user_id,updatetime,username,logged,user_type,acl");

// Responses
define('RESPONSE_WARNING', 'RESPONSE_WARNING');
define('RESPONSE_ERROR',   'RESPONSE_ERROR');
define('RESPONSE_NOTICE',  'RESPONSE_NOTICE');

class Jaws_Session
{
    /**
     * Authentication method
     * @var     string $_AuthMethod
     * @access  private
     */
    var $_AuthMethod;

    /**
     * Logged flag
     * @var     boolean $_Logged
     * @access  private
     * @see     Logged()
     */
    var $_Logged;

    /**
     * Last error message
     * @var     string $_Error
     * @access  private
     * @see     GetError()
     */
    var $_Error;

    /**
     * Attributes array
     * @var     array $_Attributes
     * @access  private
     * @see     SetAttribute(), GetAttibute()
     */
    var $_Attributes = array();

    /**
     * Changes flag
     * @var     boolean $_HasChanged
     * @access  private
     */
    var $_HasChanged;

    /**
     * session unique identifier
     * @var     string $_SessionID
     * @access  private
     */
    var $_SessionID;

    /**
     * Session is only for admins
     *
     * @access  public
     * @var     boolean
     */
    var $_OnlyAdmins = false;

    /**
     * An interface for available drivers
     *
     * @access  public
     */
    function &factory()
    {
        $SessionType = ucfirst(strtolower(APP_TYPE));
        $sessionFile = JAWS_PATH . 'include/Jaws/Session/'. $SessionType .'.php';
        if (!file_exists($sessionFile)) {
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_DEBUG, "Loading session $SessionType failed.");
            }
            return new Jaws_Error("Loading session $SessionType failed.", 'SESSION', JAWS_ERROR_ERROR);
        }

        include_once $sessionFile;
        $className = 'Jaws_Session_' . $SessionType;
        $obj = new $className();
        return $obj;
    }

    /**
     * Initializes the Session
     */
    function Init()
    {
        $this->_AuthMethod = $GLOBALS['app']->Registry->Get('/config/auth_method');
        $authFile = JAWS_PATH . 'include/Jaws/AuthScripts/' . $this->_AuthMethod . '.php';
        if (empty($this->_AuthMethod) || !file_exists($authFile)) {
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_DEBUG, $method . ' Error: ' . $authFile . ' file doesn\'t exists, using DefaultAuth');
            }
            $this->_AuthMethod = 'DefaultAuthentication';
        }

        // Try to restore session...
        $this->_HasChanged = false;

        // Load cache
        include_once JAWS_PATH . 'include/Jaws/Session/Cache.php';
        $this->_cache = new Jaws_Session_Cache;

        // Delete expired sessions
        $last_expired_file = JAWS_DATA . 'last_expired';
        $last_expired = @file_get_contents($last_expired_file);
        if ($last_expired === false || 
           ($last_expired < (time() - ($GLOBALS['app']->Registry->Get('/policy/session_idle_timeout') * 60))))
        {
            Jaws_Utils::file_put_contents($last_expired_file, time());
            $this->_cache->DeleteExpiredSessions();
        }
    }

    /**
     * Set the session type for admins
     *
     * @access  public
     */
    function OnlyAdmins()
    {
        $this->_OnlyAdmins = true;
    }

    /**
     * Gets the session mode
     *
     * @access  public
     * @return  string  Session mode
     */
    function GetMode()
    {
        return $this->_Mode;
    }

    /**
     * Login
     *
     * @param   string  $username Username
     * @param   string  $password Password
     * @param   boolean $remember Remember me
     * @param   boolean $override Admin override
     * @return  boolean True if succeed.
     */
    function Login($username, $password, $remember, $override = false)
    {
        if (isset($GLOBALS['log'])) {
            $GLOBALS['log']->Log(JAWS_LOG_DEBUG, 'LOGGIN IN');
        }

        if (($username !== '' && $password !== '') || $override === true) {
            if ($override === false) {
				require_once JAWS_PATH . 'include/Jaws/AuthScripts/' . $this->_AuthMethod . '.php';
				$authFunc = $this->_AuthMethod;
				$result = $authFunc($username, $password, $this->_OnlyAdmins);
            } else {
				$result = true;
			}
			if (!Jaws_Error::isError($result) || $override === true) {
				//if (!empty($this->_SessionID)) {
                    $this->Logout();
                //}

				$this->Create($username, $remember);
                $this->_Logged = true;

                // Update login time
                $user_id = $this->GetAttribute('user_id');
                $userModel = new Jaws_User;
                $userModel->updateLoginTime($user_id);
				
				return true;
            }

            return $result;
        }

        return new Jaws_Error(_t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    /**
     * Return session login status
     * @access  public
     */
    function Logged()
    {
		//Can non-admins be logged?
        if ($this->_OnlyAdmins === true) {
            $user_type = $this->GetAttribute('user_type');
            //Only admins eh??.. so if you are not an admin you are not logged..
            if ($user_type == 2) {
                return false;
            }
        }
        return $this->_Logged;
    }

    /**
     * Logout
     *
     * Logout from session
     * delete session from the system
     */
    function Logout()
    {
        $this->SetAttribute('logged', false);
        $this->_cache->Delete($this->_SessionID);
        $this->_SessionID = '';
        $this->_Attributes = array();
        if (isset($GLOBALS['log'])) {
            $GLOBALS['log']->Log(JAWS_LOG_DEBUG, 'Session succesfully destroyed');
        }
    }

    /**
     * Return last error message
     * @access  public
     */
    function GetError()
    {
        return $this->_Error;
    }

    /**
     * Loads Jaws Session
     *
     * @param   string $session_id Session ID
     * @return  boolean True if can load session, false if not
     */
    function Load($session_id)
    {
        if (isset($GLOBALS['log'])) {
            $GLOBALS['log']->Log(JAWS_LOG_DEBUG, 'Loading session');
        }

        $session = $this->_cache->GetSession($session_id);
        if (is_array($session)) {
            $user_agent = $_SERVER['HTTP_USER_AGENT'];
            $checksum = md5($session['user_id'] . $session['data'] . $user_agent);
            if ($checksum === $session['checksum']) {
                // check referrer of request
                $referrer = @parse_url($_SERVER['HTTP_REFERER']);
                if ($referrer && isset($referrer['host'])) {
                    $referrer = $referrer['host'];
                } else {
                    $referrer = $_SERVER['HTTP_HOST'];
                }
				
				include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
				$site_url = $GLOBALS['app']->GetSiteURL('', false, 'http');
				$site_ssl_url = $GLOBALS['app']->GetSiteURL('', false, 'https');
				
				$good_referers = array(
					strtolower($_SERVER['HTTP_HOST']),
					strtolower($_SERVER['SERVER_NAME']),
					str_replace(array('http://', 'https://'), '', strtolower($site_url)),
					str_replace(array('http://', 'https://'), '', strtolower($site_ssl_url)),
					'ajax.googleapis.com',
					'authorize.net',
					'secure.authorize.net',
					'paypal.com',
					'merchant.paypal.com',
					'sandbox.paypal.com',
					'www.sandbox.paypal.com',
					'www.paypal.com',
					'sandbox.checkout.google.com',
					'checkout.google.com',
					'google.com'
				);

                $this->_Attributes = unserialize($session['data']);
                $this->_SessionID = $session_id;
                if (!$this->GetAttribute('logged') || in_array(strtolower($referrer), $good_referers) || $session['referrer'] === md5($referrer)) {
                    if (isset($GLOBALS['log'])) {
                        $GLOBALS['log']->Log(JAWS_LOG_DEBUG, 'Session was OK');
                    }
                } else {
                    if (isset($GLOBALS['log'])) {
                        $GLOBALS['log']->Log(JAWS_LOG_DEBUG, 'Session found but referrer changed. Un-setting session, and logging out.');
                    }
					require_once JAWS_PATH . 'include/Jaws/Header.php';
					Jaws_Header::Location($GLOBALS['app']->GetSiteURL('', false, 'http').'/index.php?gadget=Users&action=Logout');
					exit;
                    /*
					Jaws_Error::Fatal('Jaws prevented execute this request for security reason<br />'.
                                      'because referrer of this session changed',
                                      __FILE__, __LINE__);
					*/
                }
            } else {
                $this->_cache->Delete($session_id);
                if (isset($GLOBALS['log'])) {
                    $GLOBALS['log']->Log(JAWS_LOG_DEBUG, 'Session found but checksum fail');
                }
            }
        } else {
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_DEBUG, 'No previous session exists');
            }
        }

        return !empty($this->_SessionID);
    }

    /**
     * Create a new session for a given username
     * @param   string  $username Username
     * @param   boolean $remember Remember me
     * @return  boolean True if can create session.
     */
    function Create($username, $remember = false)
    {
        $info = array();
        if (empty($username)) {
            $info['id']        = 0;
            $info['username']  = '';
            $info['user_type'] = 2;
            $info['nickname']     = '';
            $info['email']     = '';
            $info['url']       = '';
            $info['language']  = '';
            $info['theme']     = '';
            $info['editor']    = '';
            $info['timezone']  = null;
            $groups = array();
        } else {
            require_once JAWS_PATH . 'include/Jaws/User.php';
            $userModel = new Jaws_User;
            $info = $userModel->GetUserInfoByName($username, true, true, true);
            if (Jaws_Error::IsError($info) || !isset($info['username'])) {
                return false;
            }

            $groups = $userModel->GetGroupsOfUser($username);
            if (Jaws_Error::IsError($groups)) {
                return false;
            }
        }
        $session_id = md5(uniqid(rand(), true)) . time() . floor(microtime()*1000);
        //Don't know if session_id, user_id and modification time
        //should be on the _Attributes array since they are part of
        // the structure of session and session_user_data tables
        //session_id stays in the hash but only for knowing wich session
        //was the last to alter the session_user_data table.
        $this->_SessionID = $session_id;
        /**
         * We need to make sure Attributes is clean cause there's the possibility
         * that user was logged as another user (anonymous for example) with different
         * attributes (first Load check WebSession)
         */
        $this->_Attributes = array();
        $this->SetAttribute('logged', !empty($username));
        $this->SetAttribute('session_id', $session_id);
        $this->SetAttribute('user_id', $info['id']);
        $this->SetAttribute('user_type', (int)$info['user_type']);
        $this->SetAttribute('groups', $groups);
        $this->SetAttribute('type', APP_TYPE);
        $this->SetAttribute('longevity', $remember?
                            (int)$GLOBALS['app']->Registry->Get('/policy/session_remember_timeout')*3600 : 0);
        $this->SetAttribute('updatetime', time());
        $this->SetAttribute('username', $info['username']);

        if (isset($info['last_login'])) {
            $this->SetAttribute('last_login', $info['last_login']);
        } else {
            $this->SetAttribute('last_login', $GLOBALS['db']->Date());
        }

        //profile
        $this->SetAttribute('nickname', $info['nickname']);
        $this->SetAttribute('email', $info['email']);
        $this->SetAttribute('url',   $info['url']);

        //preferences
        $info['timezone'] = (trim($info['timezone']) == "") ? null : $info['timezone'];
        $this->SetAttribute('language', $info['language']);
        $this->SetAttribute('theme',    $info['theme']);
        $this->SetAttribute('editor',   $info['editor']);
        $this->SetAttribute('timezone', $info['timezone']);

        if ($this->Synchronize()) {
            return true;
        }

        return false;
    }

    /**
     * Reset current session
     * @return  boolean True if can reset it
     */
    function Reset()
    {
        $username = $this->GetAttribute('username');
        $this->_Attribute = array();
        $this->SetAttribute('session_id', $this->_SessionID);
        $this->SetAttribute('type', APP_TYPE);
        $this->SetAttribute('updatetime', time());
        $this->SetAttribute('username', $username);
        $this->SetAttribute('logged', false);
        $this->SetAttribute('user_type', 0);
        $this->SetAttribute('groups',   array());
        $this->SetAttribute('nickname',    '');
        $this->SetAttribute('email',    '');
        $this->SetAttribute('url',      '');
        $this->SetAttribute('language', '');
        $this->SetAttribute('theme',    '');
        $this->SetAttribute('editor',   '');
        $this->SetAttribute('timezone', null);
        if ($this->Synchronize()) {
            return true;
        }
        return false;
    }

    /**
     * Set a session attribute
     *
     * @param   string $name attribute name
     * @param   string $value attribute value
     * @return  boolean True if attribute has changed
     */
    function SetAttribute($name, $value)
    {
        if (
            !isset($this->_Attributes[$name]) ||
            (isset($this->_Attributes[$name]) && $this->_Attributes[$name] != $value)
        ) {
            if (is_array($value) && $name == 'LastResponses') {
                $this->_Attributes['LastResponses'][] = $value;
            } else {
                $this->_Attributes[$name] = $value;
            }
            $this->_HasChanged = true;
            return true;
        }

        return false;
    }

    /**
     * Get a session attribute
     *
     * @param   string $name attribute name
     * @return  string value of the attribute
     */
    function GetAttribute($name)
    {
        if (array_key_exists($name, $this->_Attributes)) {
            return $this->_Attributes[$name];
        }

        return null;
    }

    /**
     * Delete a session attribute
     *
     * @param   string $name attribute name
     * @return  boolean True if attribute has been deleted
     */
    function DeleteAttribute($name)
    {
        if (array_key_exists($name, $this->_Attributes)) {
            unset($this->_Attributes[$name]);
            $this->_HasChanged = true;
            return true;
        }

        return false;
    }

    /**
     * Get permission on a given gadget/task
     *
     * @param   string $gadget Gadget name
     * @param   string $task Task name
     * @return  boolean True if granted, else False
     */
    function GetPermission($gadget, $task)
    {
        if ($this->IsSuperAdmin() === true) {
            return true;
        }

        $groups = $this->GetAttribute('groups');
        $user = $this->GetAttribute('username');
        if (!isset($GLOBALS['app']->ACL)) {
			$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		}
		return $GLOBALS['app']->ACL->GetFullPermission($user, $groups, $gadget, $task);
    }

    /**
     * Check permission on a given gadget/task
     *
     * @param   string $gadget Gadget name
     * @param   string $task Task name
     * @param   string $errorMessage Error message to return
     * @return  boolean True if granted, else throws an Exception(Jaws_Error::Fatal)
     */
    function CheckPermission($gadget, $task, $errorMessage = '')
    {
        if ($this->GetPermission($gadget, $task)) {
            return true;
        }

        if (empty($errorMessage)) {
            $errorMessage = 'User '.$this->GetAttribute('username').
                ' don\'t have permission to execute '.$gadget.'::'.$task;
        }

        Jaws_Error::Fatal($errorMessage, __FILE__, __LINE__);
    }

    /**
     * Returns true if user is a super-admin (aka superroot)
     *
     * @access  public
     * @return  boolean
     */
    function IsSuperAdmin()
    {
        if ($this->GetAttribute('user_type') === 0) {
            return true;
        }
        return false;
    }

    /**
     * Returns true if user is an admin (or super-admin)
     *
     * @access  public
     * @return  boolean
     */
    function IsAdmin()
    {
        if ($this->GetAttribute('user_type') <= 1) {
            return true;
        }
        return false;
    }

    function Synchronize()
    {
        if (isset($GLOBALS['log'])) {
            $GLOBALS['log']->Log(JAWS_LOG_DEBUG, 'Synchronizing session');
        }

        $res = $this->_cache->Synchronize();
        if ($res) {
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_DEBUG, 'New session created');
            }
        }

        return $res;
    }

    /**
     * Push a simple response (no CSS and special data)
     *
     * @access  public
     * @param   string  $msg    Response's message
     */
    function PushSimpleResponse($msg, $resource = 'SimpleResponse')
    {
		/*
		$request =& Jaws_Request::getInstance();
		if (!$GLOBALS['app']->deleteSyntactsCacheFile(array($request->get('gadget', 'get')))) {
			Jaws_Error::Fatal("Cache file couldn't be deleted");
		}
		*/
        $this->SetAttribute($resource, $msg);
    }

    /**
     * Prints (returns) the last simple response
     *
     * @access  public
     * @param   string  $resource Resource's name
     * @param   boolean $removePoppedResource
     * @return  mixed   Last simple response
     */
    function PopSimpleResponse($resource = 'SimpleResponse', $removePoppedResource = true)
    {
        $response = $this->GetAttribute($resource);
        if ($removePoppedResource) {
            $this->DeleteAttribute($resource);
        }

        if (empty($response)) {
            return false;
        }

        return $response;
    }

    /**
     * Add the last response to the session system
     *
     * @access  public
     * @param   string  $gadget Gadget's name
     * @param   string  $msg    Response's message
     */
    function PushLastResponse($msg, $level = RESPONSE_WARNING)
    {
		/*
		$request =& Jaws_Request::getInstance();
		if (!$GLOBALS['app']->deleteSyntactsCacheFile(array($request->get('gadget', 'get')))) {
			Jaws_Error::Fatal("Cache file couldn't be deleted");
		}
		*/
        if (!defined($level)) {
            $level = RESPONSE_WARNING;
        }

        switch ($level) {
            case RESPONSE_ERROR:
                $css = 'error-message';
                break;
            case RESPONSE_NOTICE:
                $css = 'notice-message';
                break;
            case RESPONSE_WARNING:
            default:
                $css = 'warning-message';
                break;
        }

		$found = false;
        $responses = $this->GetAttribute('LastResponses');
        if ($responses !== null) {
			$count = count($this->_Attributes['LastResponses']);
			reset($this->_Attributes['LastResponses']);
			for ($i = 0; $i < $count; $i++) {
				if (substr(strtolower($this->_Attributes['LastResponses'][$i]['message']), 0, strlen($msg)) == strtolower($msg)) {
					$found = true;
					$c = (strpos($this->_Attributes['LastResponses'][$i]['message'], '(') !== false ? str_replace(array('(', ')'), '', substr($this->_Attributes['LastResponses'][$i]['message'], strpos($this->_Attributes['LastResponses'][$i]['message'], '('))) : 1);
					$this->_Attributes['LastResponses'][$i]['message'] = $msg." (".(((int)$c)+1).')';
				}
			}
		}
		
		if ($found === false) {
			$this->SetAttribute('LastResponses',
                            array(
                                  'message' => $msg,
                                  'level'   => $level,
                                  'css'     => $css
                                  )
                            );
		}
    }

    /**
     * Prints and deletes the last response of a gadget
     *
     * @access  public
     * @param   string  $gadget Gadget's name
     * @return  string  Returns the message of the last response and false if there's no response
     */
    function PopLastResponse()
    {
        $responses = $this->GetAttribute('LastResponses');
        if ($responses === null) {
            return false;
        }

        $this->DeleteAttribute('LastResponses');
        $responses = array_reverse($responses);
        if (empty($responses[0]['message'])) {
            return false;
        }

        return $responses;
    }
}
