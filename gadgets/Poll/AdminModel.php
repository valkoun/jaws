<?php
/**
 * Poll Gadget
 *
 * @category   GadgetModel
 * @package    Poll
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2005-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
require_once JAWS_PATH . 'gadgets/Poll/Model.php';

class PollAdminModel extends PollModel
{
    /**
     * Install the gadget
     *
     * @access  public
     * @return  boolean  True on success and Jaws_Error on failure
     */
    function InstallGadget()
    {
        $result = $this->installSchema('schema.xml');
        if (Jaws_Error::IsError($result)) {
            return $result;
        }

        $result = $this->installSchema('insert.xml', null, 'schema.xml', true);
        if (Jaws_Error::IsError($result)) {
            return $result;
        }

        // Registry keys.
        $GLOBALS['app']->Registry->NewKey('/gadgets/Poll/cookie_period',  '150');

        return true;
    }

    /**
     * Uninstalls the gadget
     *
     * @access  public
     * @return  boolean  Success/Failure (Jaws_Error)
     */
    function UninstallGadget()
    {
        $tables = array('poll',
                        'poll_groups',
                        'poll_answers');
        foreach ($tables as $table) {
            $result = $GLOBALS['db']->dropTable($table);
            if (Jaws_Error::IsError($result)) {
                $gName  = _t('POLL_NAME');
                $errMsg = _t('GLOBAL_ERROR_GADGET_NOT_UNINSTALLED', $gName);
                $GLOBALS['app']->Session->PushLastResponse($errMsg, RESPONSE_ERROR);
                return new Jaws_Error($errMsg, $gName);
            }
        }

        // Registry keys
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/Poll/cookie_period');

        return true;
    }

    /**
     * Update the gadget
     *
     * @access  public
     * @param   string  $old    Current version (in registry)
     * @param   string  $new    New version (in the $gadgetInfo file)
     * @return  boolean  Success/Failure (Jaws_Error)
     */
    function UpdateGadget($old, $new)
    {
        $result = $this->installSchema('schema.xml', '', "$old.xml");
        if (Jaws_Error::IsError($result)) {
            return $result;
        }

        $result = $this->installSchema('insert.xml', '', 'schema.xml', true);
        if (Jaws_Error::IsError($result)) {
            return $result;
        }

        // ACL keys
        $GLOBALS['app']->ACL->NewKey('/ACL/gadgets/Poll/ManagePolls',  'true');
        $GLOBALS['app']->ACL->NewKey('/ACL/gadgets/Poll/ManageGroups', 'true');
        $GLOBALS['app']->ACL->NewKey('/ACL/gadgets/Poll/ViewReports',  'true');
        $GLOBALS['app']->ACL->DeleteKey('/ACL/gadgets/Poll/AddPoll');
        $GLOBALS['app']->ACL->DeleteKey('/ACL/gadgets/Poll/EditPoll');
        $GLOBALS['app']->ACL->DeleteKey('/ACL/gadgets/Poll/DeletePoll');
        $GLOBALS['app']->ACL->DeleteKey('/ACL/gadgets/Poll/UpdateProperties');

        // Registry keys.
        $GLOBALS['app']->Registry->NewKey('/gadgets/Poll/cookie_period',  '150');

        return true;
    }

    /**
     * Insert a Poll
     *
     * @access  public
     * @return  array   Response (notice or error)
     */
    function InsertPoll($question, $gid, $start_time, $stop_time, $select_type, $poll_type, $result_view, $visible)
    {
        $sql = '
            INSERT INTO [[poll]]
                ([question], [gid], [start_time], [stop_time],
                 [select_type], [poll_type], [result_view], [visible])
            VALUES
                ({question}, {gid}, {start_time}, {stop_time},
                 {select_type}, {poll_type}, {result_view}, {visible})';

        $date = $GLOBALS['app']->loadDate();
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $params                = array();
        $params['question']    = $xss->parse($question);
        $params['gid']         = $gid;
        $params['start_time']  = ($date->ValidDBDate($start_time)? $start_time : null);
        $params['stop_time']   = ($date->ValidDBDate($stop_time)? $stop_time : null);
        $params['select_type'] = $select_type;
        $params['poll_type']   = $poll_type;
        $params['result_view'] = $result_view;
        $params['visible']     = $visible;

        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
            $GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
            return new Jaws_Error(_t('POLL_ERROR_POLL_NOT_ADDED'), _t('POLL_NAME'));
        }

        $GLOBALS['app']->Session->PushLastResponse(_t('POLL_POLLS_ADDED'), RESPONSE_NOTICE);
        return true;
    }

    /**
     * Updates the question of a poll
     *
     * @access  public
     * @param   string  $question Poll's Question
     * @param   int     $id       Poll's ID
     * @param   string  $visible   Poll status (visible|invisible)
     * @return  boolean True if the poll was updated and false on error
     */
    function UpdatePoll($pid, $question, $gid, $start_time, $stop_time, $select_type, $poll_type, $result_view, $visible)
    {
        $sql = '
            UPDATE [[poll]] SET
                [question]    = {question},
                [gid]         = {gid},
                [start_time]  = {start_time},
                [stop_time]   = {stop_time},
                [select_type] = {select_type},
                [poll_type]   = {poll_type},
                [result_view] = {result_view},
                [visible]     = {visible}
            WHERE [id] = {pid}';

        $date = $GLOBALS['app']->loadDate();
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $params                = array();
        $params['pid']         = (int)$pid;
        $params['question']    = $xss->parse($question);
        $params['gid']         = $gid;
        $params['start_time']  = ($date->ValidDBDate($start_time)? $start_time : null);
        $params['stop_time']   = ($date->ValidDBDate($stop_time)? $stop_time : null);
        $params['select_type'] = $select_type;
        $params['poll_type']   = $poll_type;
        $params['result_view'] = $result_view;
        $params['visible']     = $visible;

        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
            $GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
            return new Jaws_Error(_t('POLL_ERROR_POLL_NOT_UPDATED'), _t('POLL_NAME'));
        }

        $GLOBALS['app']->Session->PushLastResponse(_t('POLL_POLLS_UPDATED'), RESPONSE_NOTICE);
        return true;
    }

    /**
     * Deletes a poll
     *
     * @access  public
     * @param   int     $pid Poll's ID
     * @return  boolean True if the poll was deleted and false on error
     */
    function DeletePoll($pid)
    {
        $sql = 'DELETE FROM [[poll]] WHERE [id] = {pid}';
        $res = $GLOBALS['db']->query($sql, array('pid' => $pid));
        if (Jaws_Error::IsError($res)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('POLL_ERROR_POLL_NOT_DELETED'), _t('POLL_NAME'));
        }

        $sql = 'DELETE FROM [[poll_answers]] WHERE [pid] = {pid}';
        $res = $GLOBALS['db']->query($sql, array('pid' => $pid));
        if (Jaws_Error::IsError($res)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('POLL_ERROR_EXCEPTION_ANSWER_NOT_DELETED'), _t('POLL_NAME'));
        }

        $GLOBALS['app']->Session->PushLastResponse(_t('POLL_POLLS_DELETED'), RESPONSE_NOTICE);
        return true;
    }

    /**
     * Update a Poll Answers
     *
     * @access  public
     * @return  array   Response (notice or error)
     */
    function UpdatePollAnswers($pid, $answers)
    {
        $oldAnswers = $this->GetPollAnswers($pid);
        if (Jaws_Error::IsError($oldAnswers)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('POLL_ERROR_ANSWERS_NOT_UPDATED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('POLL_ERROR_ANSWERS_NOT_UPDATED'), _t('POLL_NAME'));
        }

        foreach ($oldAnswers as $oldAnswer) {
            $found = false;
            foreach ($answers as $newAnswer) {
                if ($oldAnswer['id'] == $newAnswer['id']) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $this->DeleteAnswer($oldAnswer['id']);
            }
        }

        //-- for adding new answers and update old answers
        foreach ($answers as $index => $newAnswer) {
            $found = false;
            foreach ($oldAnswers as $oldAnswer) {
                if ($newAnswer['id'] == $oldAnswer['id']) {
                    $found = true;
                    break;
                }
            }
            if ($found) {
                $res = $this->UpdateAnswer($newAnswer['id'], $newAnswer['answer'], $index);
                if (Jaws_Error::IsError($res)) {
                    $GLOBALS['app']->Session->PushLastResponse(_t('POLL_ERROR_ANSWER_NOT_UPDATED'), RESPONSE_ERROR);
                    return false;
                }
            } else {
                $res = $this->InsertAnswer($pid, $newAnswer['answer'], $index);
                if (Jaws_Error::IsError($res)) {
                    $GLOBALS['app']->Session->PushLastResponse(_t('POLL_ERROR_ANSWER_NOT_ADDED'), RESPONSE_ERROR);
                    return false;
                }
            }
        }

    $GLOBALS['app']->Session->PushLastResponse(_t('POLL_ANSWERS_UPDATED'), RESPONSE_NOTICE);
    return true;
    }

    /**
     * Insert a new answer
     *
     * @access  public
     * @param   int     $poll   Poll's ID
     * @param   string  $answer Answer
     * @return  boolean True if the answer was created and false on error
     */
    function InsertAnswer($pid, $answer, $rank)
    {
        $sql = '
            INSERT INTO [[poll_answers]]
                ([pid], [answer], [rank])
            VALUES
                ({pid}, {answer}, {rank})';

        $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $params           = array();
        $params['pid']    = $pid;
        $params['answer'] = $xss->parse($answer);
        $params['rank']   = (int)$rank;

        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('POLL_ERROR_ANSWER_NOT_ADDED'), _t('POLL_NAME'));
        }

        return true;
    }

    /**
     * Updates the answer
     *
     * @access  public
     * @param   string  $answer   Answer's Question
     * @param   int     $aid       Answer's ID
     * @return  boolean True if the answer was updated and false on error
     */
    function UpdateAnswer($aid, $answer, $rank)
    {
        $sql = '
            UPDATE [[poll_answers]] SET
                [answer] = {answer},
                [rank]   = {rank}
            WHERE [id] = {aid}';

        $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $params           = array();
        $params['aid']    = (int)$aid;
        $params['answer'] = $xss->parse($answer);
        $params['rank']   = (int)$rank;

        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('POLL_ERROR_ANSWER_NOT_UPDATED'), _t('POLL_NAME'));
        }

        return true;
    }

    /**
     * Deletes an answer
     *
     * @access  public
     * @param   int     $aid Answer's ID
     * @return  boolean True if the answer was deleted and false on error
     */
    function DeleteAnswer($aid)
    {
        $sql = 'DELETE FROM [[poll_answers]] WHERE [id] = {aid}';
        $result = $GLOBALS['db']->query($sql, array('aid' => $aid));
        if (Jaws_Error::IsError($result)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('POLL_ERROR_ANSWER_NOT_DELETED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('POLL_ERROR_ANSWER_NOT_DELETED'), _t('POLL_NAME'));
        }

        return true;
    }

    /**
    * Insert a poll group
    *
    * @access  public
    * @return  boolean Success/Failure (Jaws_Error)
    */
    function InsertPollGroup($title, $visible)
    {
        $sql = 'SELECT COUNT([id]) FROM [[poll_groups]] WHERE [title] = {title}';
        $gc = $GLOBALS['db']->queryOne($sql, array('title' => $title));
        if (Jaws_Error::IsError($gc)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
            return false;
        }

        if ($gc > 0) {
            $GLOBALS['app']->Session->PushLastResponse(_t('POLL_ERROR_GROUP_TITLE_DUPLICATE'), RESPONSE_ERROR);
            return false;
        }

        $sql = '
            INSERT INTO [[poll_groups]]
                ([title], [visible])
            VALUES
                ({title}, {visible})';

        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $params            = array();
        $params['title']   = $xss->parse($title);
        $params['visible'] = $visible;
        $res = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($res)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('POLL_ERROR_GROUP_NOT_ADDED'), _t('POLL_NAME'));
        }

        $GLOBALS['app']->Session->PushLastResponse(_t('POLL_GROUPS_CREATED'), RESPONSE_NOTICE);
        return true;
    }

    /**
    * Update a poll group
    * @access  public
    *
    * @return  boolean Success/Failure (Jaws_Error)
    */
    function UpdatePollGroup($gid, $title, $visible)
    {
        $sql = 'SELECT COUNT([id]) FROM [[poll_groups]] WHERE [id] != {gid} AND [title] = {title}';
        $gc = $GLOBALS['db']->queryOne($sql, array('gid' => $gid, 'title' => $title));
        if (Jaws_Error::IsError($gc)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
            return false;
        }

        if ($gc > 0) {
            $GLOBALS['app']->Session->PushLastResponse(_t('POLL_ERROR_GROUP_TITLE_DUPLICATE'), RESPONSE_ERROR);
            return false;
        }

        $sql = '
            UPDATE [[poll_groups]] SET
                [title]   = {title},
                [visible] = {visible}
            WHERE [id] = {gid}';

        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $params            = array();
        $params['gid']     = $gid;
        $params['title']   = $xss->parse($title);
        $params['visible'] = $visible;
        $res = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($res)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('POLL_ERROR_GROUP_NOT_UPDATED'), _t('POLL_NAME'));
        }

        $GLOBALS['app']->Session->PushLastResponse(_t('POLL_GROUPS_UPDATED'), RESPONSE_NOTICE);
        return true;
    }

    /**
     * Delete a poll group
     *
     * @access  public
     * @param   int     $gid The poll group that will be deleted
     * @return  boolean True if query was successful and Jaws_Error on error
     */
    function DeletePollGroup($gid)
    {
        if ($gid == 1) {
            $GLOBALS['app']->Session->PushLastResponse(_t('POLL_ERROR_GROUP_NOT_DELETED'), RESPONSE_ERROR);
            return false;
        }

        $group = $this->GetPollGroup($gid);
        if (Jaws_Error::IsError($group)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
            return false;
        }

        if(!isset($group['id'])) {
            $GLOBALS['app']->Session->PushLastResponse(_t('POLL_ERROR_GROUP_DOES_NOT_EXISTS'), RESPONSE_ERROR);
            return false;
        }

        $this->UpdateGroupsOfPolls(-1, $gid, 0);
        $sql = 'DELETE FROM [[poll_groups]] WHERE [id] = {gid}';
        $res = $GLOBALS['db']->query($sql, array('gid' => $gid));
        if (Jaws_Error::IsError($res)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('POLL_ERROR_GROUP_NOT_DELETED'), _t('POLL_NAME'));
        }

        $GLOBALS['app']->Session->PushLastResponse(_t('POLL_GROUPS_DELETED', $gid), RESPONSE_NOTICE);
        return true;
    }

    /**
     * Add a group of poll (by they ids) to a certain poll group
     *
     * @access  public
     * @param   int     $gid  PollGroup's ID
     * @param   array   $polls Array with poll id
     * @return  array   Response (notice or error)
     */
    function AddPollsToPollGroup($gid, $polls)
    {
        $AllPolls = $this->GetPolls();
        foreach ($AllPolls as $poll) {
            if ($poll['gid'] == $gid) {
                if (!in_array($poll['id'], $polls)) {
                    $this->UpdateGroupsOfPolls($poll['id'], -1, 0);
                }
            } else {
                if (in_array($poll['id'], $polls)) {
                    $this->UpdateGroupsOfPolls($poll['id'], -1, $gid);
                }
            }
        }
        $GLOBALS['app']->Session->PushLastResponse(_t('POLL_GROUPS_UPDATED_POLLS'), RESPONSE_NOTICE);
        return true;
    }

    /**
     * Adds an poll to a group
     *
     * @access  public
     * @param   int     $pid  Poll's ID
     * @param   int     $gid  PollGroup's ID
     * @param   int     $new_gid  PollGroup's ID
     * @return  boolean Returns true if poll was sucessfully added to the group, false if not
     */
    function UpdateGroupsOfPolls($pid, $gid, $new_gid)
    {
        if (($pid != -1) && ($gid != -1)) {
            $sql = '
                UPDATE [[poll]] SET
                [gid] = {new_gid}
                WHERE [[poll]].[id] = {pid} AND [[poll]].[gid] = {gid}';
        } elseif ($gid != -1) {
            $sql = '
                UPDATE [[poll]] SET
                [gid] = {new_gid}
                WHERE [[poll]].[gid] = {gid}';
        } elseif ($pid != -1) {
            $sql = '
                UPDATE [[poll]] SET
                [gid] = {new_gid}
                WHERE [id] = {pid}';
        } else {
            $sql = '
                UPDATE [[poll]] SET
                [gid] = {new_gid}';
        }

        $params = array();
        $params['pid']     = $pid;
        $params['gid']     = $gid;
        $params['new_gid'] = $new_gid;

        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
            return false;
        }

        return true;
    }
}
