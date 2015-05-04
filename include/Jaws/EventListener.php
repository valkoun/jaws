<?php
/**
 * Create custom event listeners that execute
 * automatically when a shouter is fired.
 *
 * @category   Event
 * @category   developer_feature
 * @package    Core
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @copyright  2005-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class Jaws_EventListener
{
    /**
     * Creates a new listener and saves it in the DB
     *
     * @access  public
     * @param   string  $gadget  Gadget name that listens
     * @param   string  $call    Call name (call that is waiting)
     * @param   string  $method  Gadget method that will be executed
     * @return  boolean True if listener was added, otherwise returns Jaws_Error
     */
    function NewListener($gadget, $call, $method)
    {
        $params = array();
        $params['gadget'] = $gadget;
        $params['call']   = $call;
        $params['method'] = $method;

        $sql = '
            INSERT INTO [[listeners]]
                ([gadget], [event], [method])
            VALUES
                ({gadget}, {call}, {method})';

        $rs = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($rs)) {
            return new Jaws_Error(_t('GLOBAL_ERROR_EVENTS_LISTENER_NOT_ADDED'), 'CORE');
        }

        return true;
    }

    /**
     * Deletes a shouter
     *
     * @access  public
     * @param   string  $gadget  Gadget name
     * @param   string  $method  Gadget method name
     * @return  boolean True if listener was deleted, otherwise returns Jaws_Error
     */
    function DeleteListener($gadget, $method = '')
    {
        $params = array();
        $params['gadget'] = $gadget;
        $params['method'] = $method;

        $sql = 'DELETE FROM [[listeners]] WHERE [gadget] = {gadget}';
        if (!empty($method)) {
            $sql .= ' AND [method] = {method}';
        }

        $res = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($res)) {
            return new Jaws_Error(_t('GLOBAL_ERROR_EVENTS_LISTENER_NOT_DELETED'), 'CORE');
        }

        return true;
    }

    /**
     * Listens a 'shout' and acts, looking for all gadgets that are waiting for
     * this call, creating them, calling their method and deleting them
     *
     * @access  public
     * @param   string  $gadget  Gadget name
     * @param   mixed   $param   Param that is send to the listener, can be a
     *                           string, int, array, object, etc.
     * @return  boolean True if call was received/executed successfully, otherwise returns Jaws_Error
     */
    function Listen($call, $param)
    {
        $listeners = $this->GetListenersWaiting($call);
        if (Jaws_Error::IsError($listeners)) {
            return $listeners;
        }

        if (count($listeners) > 0) {
            foreach ($listeners as $listener) {
                if (Jaws_Gadget::IsGadgetInstalled($listener['gadget'])) {
                    $gadget = $GLOBALS['app']->loadGadget($listener['gadget'], 'AdminModel');
                    if (Jaws_Error::IsError($gadget)) {
                        return new Jaws_Error(_t('GLOBAL_ERROR_EVENTS_LISTENER_ERROR'), 'CORE');
                    }

                    if (method_exists($gadget, $listener['method'])) {
                        $res = $gadget->$listener['method']($param);
                        if (Jaws_Error::IsError($res)) {
                            return new Jaws_Error(_t('GLOBAL_ERROR_EVENTS_LISTENER_ERROR'), 'CORE');
                        }
                    }
                }
            }
        }

        return true;
    }

    /**
     * Get information (which gadget is listening, which call is waiting form
     * and which method is going to be executed)
     *
     * @access  public
     * @param   int     Listener's ID
     * @return  array   An array with information of a listener or Jaws_Error on failure
     */
    function GetListener($id)
    {
        $sql = '
            SELECT
                [id], [gadget], [method], [event]
            FROM [[listeners]]
            WHERE  [id] = {id}';

        $res = $GLOBALS['db']->queryAll($sql, array('id' => $id));
        if (Jaws_Error::IsError($res)) {
            return new Jaws_Error(_t('GLOBAL_ERROR_QUERY_FAILED', 'GetListeners'), 'CORE');
        }

        return $res;
    }

    /**
     * Gets a list of all listener gadgets
     *
     * @access  public
     * @return  array   An array of all listener gadgets or Jaws_Error on failure
     */
    function GetListeners($call)
    {
        $sql = '
            SELECT
                [id], [gadget], [method], [event]
            FROM [[listeners]]';

        $res = $GLOBALS['db']->queryRow($sql);
        if (Jaws_Error::IsError($res)) {
            return new Jaws_Error(_t('GLOBAL_ERROR_QUERY_FAILED', 'GetListener'), 'CORE');
        }

        return $res;
    }

    /**
     * Gets a list of all gadgets that are waiting a 'call' to act
     *
     * @access  public
     * @param   string  $gadget Gadget Name
     * @param   string  $call   Gadget Call (the one listener is waiting to 'listen').
     * @return  array   An array of all gadgets that match a call or Jaws_Error on failure
     */
    function GetListenersWaiting($call)
    {
        $sql = '
            SELECT
                [id], [gadget], [method]
            FROM [[listeners]]
            WHERE [event] = {call}';

        $res = $GLOBALS['db']->queryAll($sql, array('call' => $call));
        if (Jaws_Error::IsError($res)) {
            return new Jaws_Error(_t('GLOBAL_ERROR_QUERY_FAILED', 'GetListenersWaiting'), 'CORE');
        }

        return $res;
    }
}