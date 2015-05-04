<?php
/**
 * Event Listeners/Shouters. Built-in and custom event management so gadgets can subscribe/broadcast when certain actions occur. 
 *
 * @category   Event
 * @category   feature
 * @package    Core
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @copyright  2005-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class Jaws_EventShouter
{
    /**
     * Creates a new shouter and saves it in the DB
     *
     * @access  public
     * @param   string  $gadget  Gadget name that shouts
     * @param   string  $call    Call name
     * @return  boolean True if shouter was added, otherwise returns Jaws_Error
     */
    function NewShouter($gadget, $call)
    {
        $params = array();
        $params['gadget'] = $gadget;
        $params['call']   = $call;

        $sql = '
            INSERT INTO [[shouters]]
                ([gadget], [event])
            VALUES
                ({gadget}, {call})';

        $rs = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($rs)) {
            return new Jaws_Error(_t('GLOBAL_ERROR_EVENTS_NOT_ADDED'), 'CORE');
        }

        return true;
    }

    /**
     * Deletes a shouter
     *
     * @access  public
     * @param   string  $gadget  Gadget name
     * @param   string  $call    Call name
     * @return  boolean True if shouter was deleted, otherwise returns Jaws_Error
     */
    function DeleteShouter($gadget, $call)
    {
        $params = array();
        $params['gadget'] = $gadget;
        $params['call']   = $call;

        $sql = '
            DELETE FROM [[shouters]]
            WHERE
                [gadget] = {gadget}
              AND
                [event] = {call}';

        $rs = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($rs)) {
            return new Jaws_Error(_t('GLOBAL_ERROR_EVENTS_NOT_DELETED'), 'CORE');
        }

        return true;
    }

    /**
     * Shouts a call to the listener object that will act inmediatly.
     *
     * @access  public
     * @param   string  $gadget  Gadget name
     * @param   mixed   $param   Param that is send to the listener, can be a
     *                           string, int, array, object, etc.
     * @return  boolean True if shouter didn't returned a Jaws_Error, otherwise returns Jaws_Error
     */
    function Shout($call, $param)
    {
        $GLOBALS['app']->loadClass('Listener', 'Jaws_EventListener');
        $res = $GLOBALS['app']->Listener->Listen($call, $param);
        if (Jaws_Error::IsError($res)) {
            return new Jaws_Error(_t('GLOBAL_ERROR_EVENTS_LISTENER_ERROR'), 'CORE');
        }
		
		// Content updated? Update the site's last_update registry key.
		if (
			substr(strtolower($call), 0, 5) == 'onadd' || 
			substr(strtolower($call), 0, 8) == 'onupdate' || 
			substr(strtolower($call), 0, 8) == 'ondelete' || 
			substr(strtolower($call), 0, 8) == 'onremove'
		) {
			if (!isset($GLOBALS['app']->Registry)) {
				$GLOBALS['app']->loadClass('Registry', 'Jaws_Registry');
			}
			$GLOBALS['app']->Registry->UpdateLastUpdate();
		}
		
		// Call custom hook?
		if (JAWS_SCRIPT != 'rest' && JAWS_SCRIPT != 'xmlrpc' && JAWS_SCRIPT != 'install') {
			if (file_exists(JAWS_DATA . 'hooks' . DIRECTORY_SEPARATOR . 'Shout.php')) {
				include_once JAWS_DATA . 'hooks' . DIRECTORY_SEPARATOR . 'Shout.php';
				$hook = new ShoutHook;
				if (method_exists($hook, $call)) {
					$res = $hook->$call($param);
					if ($res === false || Jaws_Error::IsError($res)) {
						//return $res;
						return new Jaws_Error(_t('GLOBAL_ERROR_EVENTS_LISTENER_ERROR'), 'CORE');
					} else if (isset($res['return'])) {
						return $res['return'];
					}
				}
			}
		}
        return true;
    }

    /**
     * Get information of a shouter (which gadget is shouting and the shout call)
     *
     * @access  public
     * @param   int     Shouter's ID
     * @return  array   An array with information of a shouter or Jaws_Error on failure
     */
    function GetShouter($id)
    {
        $sql = '
            SELECT
                [gadget], [event]
            FROM [[shouters]]
            WHERE [id] = {id}';

        $res = $GLOBALS['db']->queryAll($sql, array('id' => $id));
        if (Jaws_Error::IsError($res)) {
            return new Jaws_Error(_t('GLOBAL_ERROR_QUERY_FAILED', 'GetShouter'), 'CORE');
        }

        return $res;
    }

    /**
     * Gets a list of all shouter gadgets
     *
     * @access  public
     * @return  array   An array of all shouter gadgets or Jaws_Error on failure
     */
    function GetShouters()
    {
        $sql = '
            SELECT
                [id], [gadget], [event]
            FROM [[shouters]]';

        $res = $GLOBALS['db']->queryRow($sql);
        if (Jaws_Error::IsError($res)) {
            return new Jaws_Error(_t('GLOBAL_ERROR_QUERY_FAILED', 'GetShouters'), 'CORE');
        }

        return $res;
    }
}