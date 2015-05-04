<?php
/**
 * Session data cache class
 *
 * @category   Session
 * @package    Core
 * @author     Helgi Þormar Þorbjörnsson <dufuz@php.net>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2007-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class Jaws_Session_Cache extends Jaws_Session
{
    /**
     * Synchronize current session with DB
     *
     * @param   string   $session_id  Session ID
     * @return  boolean True if can sync, false otherwise
     */
    function Synchronize()
    {
        $user_id    = $GLOBALS['app']->Session->GetAttribute('user_id');
        $username   = $GLOBALS['app']->Session->GetAttribute('username');
        $session_id = $GLOBALS['app']->Session->GetAttribute('session_id');
        if (empty($session_id)) {
            return false;
        }

        $params = array();
        $params['session_id'] = $session_id;
        $sql = 'SELECT COUNT(*) FROM [[session]] WHERE [session_id] = {session_id}';
        $result = $GLOBALS['db']->queryOne($sql, $params);
        if (Jaws_Error::isError($result)) {
            return false;
        }

        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        $referrer = @parse_url($_SERVER['HTTP_REFERER']);
        if ($referrer && isset($referrer['host']) && ($referrer['host'] != $_SERVER['HTTP_HOST'])) {
            $referrer = $referrer['host'];
        } else {
            $referrer = '';
        }

        if ($result > 0) {
            // Now we sync with a previous session only if has changed
            if ($GLOBALS['app']->Session->_HasChanged) {
                require_once JAWS_PATH . 'include/Jaws/User.php';
                $userModel = new Jaws_User();
                $groups = $userModel->GetGroupsOfUser($username);
                if (Jaws_Error::IsError($groups)) {
                    $groups = array();
                }
                $GLOBALS['app']->Session->SetAttribute('groups', $groups);

                $params = array();
                $serialized = serialize($GLOBALS['app']->Session->_Attributes);
                $params['data']       = $serialized;
                $params['updatetime'] = time();
                $params['session_id'] = $session_id;
                $params['user_id']    = $user_id;
                $params['referrer']   = md5($referrer);
                $params['checksum']   = md5($user_id . $serialized . $user_agent);

                $sql = '
                    UPDATE [[session]] SET
                        [updatetime] = {updatetime},
                        [data]     = {data},
                        [referrer] = {referrer},
                        [checksum] = {checksum}
                    WHERE [session_id] = {session_id}';

                $result = $GLOBALS['db']->query($sql, $params);
                if (Jaws_Error::IsError($result)) {
                    return false;
                }

                if (isset($GLOBALS['log'])) {
                    $GLOBALS['log']->Log(JAWS_LOG_DEBUG, 'Session synchronized succesfully');
                }
            } else {
                $params = array();
                $params['updatetime'] = time();
                $params['session_id'] = $session_id;
                $params['user_id'] = $user_id;
                $sql = '
                    UPDATE [[session]] SET
                        [updatetime] = {updatetime}
                    WHERE [session_id] = {session_id}';
                $result = $GLOBALS['db']->query($sql, $params);
                if (Jaws_Error::IsError($result)) {
                    return false;
                }

                if (isset($GLOBALS['log'])) {
                    $GLOBALS['log']->Log(JAWS_LOG_DEBUG, 'Session synchronized succesfully(only modification time)');
                }
            }
        } else {
            if (empty($username)) {
                $groupsAttribute = array();
                $GLOBALS['app']->Session->SetAttribute('groups', $groupsAttribute);
            }
            //A new session, we insert it to the DB
            $params = array();
            $serialized = serialize($GLOBALS['app']->Session->_Attributes);
            $mt = $GLOBALS['app']->Session->GetAttribute('updatetime');

            $params['data']       = $serialized;
            $params['updatetime'] = $mt;
            $params['createtime'] = $mt;
            $params['longevity']  = $GLOBALS['app']->Session->GetAttribute('longevity');
            $params['session_id'] = $session_id;
            $params['app_type']   = APP_TYPE;
            $params['user_id']    = $user_id;
            $params['referrer']   = md5($referrer);
            $params['checksum']   = md5($user_id . $serialized . $user_agent);

            $sql = '
                INSERT INTO [[session]]
                    ([session_id], [user_id], [session_type], [longevity], [data],
                    [referrer], [checksum], [createtime], [updatetime])
                VALUES
                    ({session_id}, {user_id}, {app_type}, {longevity}, {data},
                    {referrer}, {checksum}, {createtime}, {updatetime})';

            $result = $GLOBALS['db']->query($sql, $params);
            if (Jaws_Error::IsError($result)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Delete a session
     *
     * @param   string  $session_id  Session ID
     * @return  boolean Success/Failure
     */
    function Delete($session_id)
    {
        $sql = 'DELETE FROM [[session]] WHERE [session_id] = {session_id}';
        $result = $GLOBALS['db']->query($sql, array('session_id' => $session_id));
        if (Jaws_Error::IsError($result)) {
            return false;
        }

        return true;
    }

    /**
     * Deletes all sessions of an user
     *
     * @param   string  $user   User's ID
     * @return  boolean Success/Failure
     */
    function DeleteUserSessions($user)
    {
        //Get the sessions ID of the user
        $sql = 'DELETE FROM [[session]] WHERE [user_id] = {user_id}';
        $sessions = $GLOBALS['db']->queryAll($sql, array('user_id' => $user));
        if (Jaws_Error::IsError($sessions)) {
            return false;
        }

        return true;
    }

    /**
     * Delete expired sessions
     */
    function DeleteExpiredSessions()
    {
        $params = array();
        $params['expired'] = time() - ($GLOBALS['app']->Registry->Get('/policy/session_idle_timeout') * 60);
        $sql = "DELETE FROM [[session]] WHERE [updatetime] < ({expired} - [longevity])";
        $result = $GLOBALS['db']->queryAll($sql, $params);
        if (Jaws_Error::IsError($result)) {
            return false;
        }

        return true;
    }

    /**
     * Returns false if user has an old session on session_user_data and true if
     * user has an old session
     *
     * @access  public
     * @return  boolean
     */
    function HasOldSession()
    {
        $params         = array();
        $params['user'] = $GLOBALS['app']->Session->GetAttribute('user_id');
        $sql = 'SELECT COUNT([user_id]) FROM [[session]] WHERE [user_id] = {user}';
        $count = $GLOBALS['db']->queryOne($sql, $params, array('integer'));
        if (Jaws_Error::isError($count)) {
            return false;
        }

        $count = (int)$count;
        if ($count === 1) {
            return true;
        }

        return false;
    }

    /**
     * Returns the session values (user_id and session_id) of a session
     *
     * @access  private
     * @param   string   $sid  Session ID
     * @return  boolean  Exists/Not exists
     */
    function GetSession($sid)
    {
        $params = array();
        $params['sid'] = $sid;

        $sql = '
            SELECT
                [session_id], [user_id], [data], [referrer], [checksum], [updatetime], [longevity]
            FROM [[session]]
            WHERE
                [session_id] = {sid}';

        $result = $GLOBALS['db']->queryRow($sql, $params);
        if (!Jaws_Error::isError($result) && isset($result['session_id'])) {
            $expired = time() - ($GLOBALS['app']->Registry->Get('/policy/session_idle_timeout') * 60);
            if ($result['updatetime'] >= ($expired - $result['longevity'])) {
                return $result;
            }
        }

        return false;
    }

    /**
     * Returns the session_user_data based on:
     *
     *  - a user_id
     *  - a session_id
     *
     * @access  public
     * @param   string  $param  User_id/Session_id
     * @param   string  $based  Based on what? (user_id, session_id)
     * @return  array   Session User data or false if doesn't exists (or error)
     */
    function GetSessionUserData($param, $based)
    {
        $params          = array();
        $params['param'] = $param;

        $sql = '
           SELECT [user_id], [data], [checksum]
           FROM [[session]]
           WHERE ';

        $sql .= $based == 'user_id' ? '[user_id] = ' : '[session_id] = ';
        $sql .= '{param}';

        $user_data = $GLOBALS['db']->queryRow($sql, $params);
        if (Jaws_Error::isError($user_data)) {
            return false;
        }

        if (!isset($user_data['data'])) {
            return false;
        }
        return $user_data;
    }
}