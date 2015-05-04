<?php
/**
 * AJAX interface.
 *
 * @category   Ajax
 * @category   feature
 * @package    Core
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @copyright  2005-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class Jaws_Ajax
{
    /**
     * Model
     *
     * @access  private
     * @var     Jaws_Model
     */
    var $_Model;

    /**
     * Check the session permission:
     *
     *  - If user has privileges to execute the task
     *  - If session object exists
     *  - If session stills active
     *
     * @access  public
     * @param   string  $gadget  Gadget name
     * @param   string  $task    Task name
     */
    function CheckSession($gadget, $task)
    {
        $this->CheckSessionExistence();
        $this->CheckSessionLife();
        $this->CheckSessionPermission($gadget, $task);
    }

    /**
     * Get the session permission:
     *
     *  - If user has privileges to execute the task
     *  - If session object exists
     *  - If session stills active
     *
     * @access  public
     * @param   string  $gadget  Gadget name
     * @param   string  $task    Task name
     */
    function GetPermission($gadget, $task)
    {
        return (
            $this->GetSessionExistence() &&
            $this->IsSessionAlive() &&
            $this->GetSessionPermission($gadget, $task)
        );
    }

    /**
     * Check if session object exists
     *
     * @access   private
     */
    function CheckSessionExistence()
    {
        if (!isset($GLOBALS['app']->Session)) {
            trigger_error('[NOSESSION] - Session does not exists', E_USER_ERROR);
        }
    }

    /**
     * Gets the existence of the session status
     *
     * @access   private
     * @return   boolean
     */
    function GetSessionExistence()
    {
        return isset($GLOBALS['app']->Session) ? true : false;
    }

    /**
     * Check if session stills active
     *
     * @access  private
     */
    function CheckSessionLife()
    {
        if (!$GLOBALS['app']->Session->Logged()) {
            trigger_error('[NOTLOGGED] - User not logged', E_USER_ERROR);
        }
    }

    /**
     * Gets the session status
     *
     * @access  private
     * @return  boolean
     */
    function IsSessionAlive()
    {
        return $GLOBALS['app']->Session->Logged() ? true : false;
    }

    /**
     * Check permission on a gadget/task
     *
     * @access  public
     * @param   string  $gadget  Gadget name
     * @param   string  $task    Task name
     */
    function CheckSessionPermission($gadget, $task)
    {
        if (!$GLOBALS['app']->Session->GetPermission($gadget, $task)) {
            trigger_error('[NOPERMISSION] - You do not have permission to execute this task', E_USER_ERROR);
        }
    }

    /**
     * Gets the session permission status
     *
     * @access public
     * @param  string $gadget   Gadget name
     * @param  string  $task    Task name
     * @return boolean
     */
    function GetSessionPermission($gadget, $task)
    {
        return $GLOBALS['app']->Session->GetPermission($gadget, $task) ? true : false;
    }
}
?>
