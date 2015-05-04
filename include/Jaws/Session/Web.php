<?php
/**
 * Class to manage the session when user is running a web application
 *
 * @category   Session
 * @package    Core
 * @author     Ivan -sk8- Chavero <imcsk8@gluch.org.mx>
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2005-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
define('JAWS_SESSION_ID', 'JawsSession');

class Jaws_Session_Web extends Jaws_Session
{

    function Jaws_Session_Web()
    {
        parent::Init();
    }

    /**
     * Initializes the Session
     *
     * @access  public
     */
    function init()
    {
        $session = $this->GetCookie(JAWS_SESSION_ID);
        if ($session === false || !$this->Load($session)) {
            $this->Create('');
            $this->_Logged = false;
        } else {
            $this->_Logged = $this->GetAttribute('logged');
        }
    }

    /**
     * @see Jaws_Session::Logout
     *
     */
    function Logout()
    {
        $this->DestroyCookie(JAWS_SESSION_ID);
        parent::Logout();
    }

    /**
     * @see Jaws_Session::Create
     *
     * @param   string  $username Username
     * @param   boolean $remember Remember me
     * @return  boolean True if can create session.
     */
    function Create($username, $remember = false)
    {
        parent::Create($username, $remember);
        // Create cookie
        $this->SetCookie(JAWS_SESSION_ID, $this->_SessionID,
                         $remember? 60*(int)$GLOBALS['app']->Registry->Get('/policy/session_remember_timeout') : 0);
    }

    /**
     * Create a new cookie on client
     *
     * @param   string $name Cookie name
     * @param   string $value Cookie value
     * @param   string $expiration Cookie expiration minutes
     */
    function SetCookie($name, $value, $minutes = 0)
    {
        $secure  = ($GLOBALS['app']->Registry->Get('/config/cookie/secure') == 'false') ? false : true;
        $path    = $GLOBALS['app']->Registry->Get('/config/cookie/path');
        $domain  = $GLOBALS['app']->Registry->Get('/config/cookie/domain');
        $version = $GLOBALS['app']->Registry->Get('/config/cookie/version');
        $name = $name.'_'.md5($GLOBALS['app']->getSiteURL('_'.$version));
        setcookie($name, $value, ($minutes == 0)? 0 : (time() + $minutes*60), $GLOBALS['app']->getSiteURL('/', true));
    }

    /**
     * Get a cookie
     * @param   string $name Cookie name
     */
    function GetCookie($name)
    {
        $version = $GLOBALS['app']->Registry->Get('/config/cookie/version');
        $name    = $name.'_'.md5($GLOBALS['app']->getSiteURL('_'.$version));
        $request =& Jaws_Request::getInstance();
        return $request->get($name, 'cookie');
    }

    /**
     * Destroy a cookie
     * @param   string $name Cookie name
     */
    function DestroyCookie($name)
    {
        $secure  = ($GLOBALS['app']->Registry->Get('/config/cookie/secure') == 'false') ? false : true;
        $path    = $GLOBALS['app']->Registry->Get('/config/cookie/path');
        $domain  = $GLOBALS['app']->Registry->Get('/config/cookie/domain');
        $version = $GLOBALS['app']->Registry->Get('/config/cookie/version');
        $name    = $name.'_'.md5($GLOBALS['app']->getSiteURL('_'.$version));
        setcookie($name, '', time() - 36000, $GLOBALS['app']->getSiteURL('/', true));
    }

    /**
     * Check permission on a given gadget/task
     *
     * @param   string $gadget Gadget name
     * @param   string $task Task name
     * @param   string $errorMessage Error message to return
     * @return  boolean True if granted, else print HTML output telling the user he doesn't have permission
     */
    function CheckPermission($gadget, $task, $errorMessage = '')
    {
        if ($this->GetPermission($gadget, $task)) {
            return true;
        }

        $GLOBALS['app']->InstanceLayout();
        $GLOBALS['app']->Layout->LoadControlPanelHead();
        $user = $GLOBALS['app']->LoadGadget('Users', 'HTML');
        echo $user->ShowNoPermission($this->GetAttribute('username'), $gadget, $task);
        exit;
    }

}