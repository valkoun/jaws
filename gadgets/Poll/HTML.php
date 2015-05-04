<?php
/**
 * Poll Gadget
 *
 * @category   Gadget
 * @package    Poll
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2005-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class PollHTML extends Jaws_GadgetHTML
{
    /**
     * Constructor
     *
     * @access  public
     */
    function PollHTML()
    {
        $this->Init('Poll');
    }

    /**
     * Default action
     *
     * @acces  public
     * @return string  HTML result
     */
    function DefaultAction()
    {
        $this->SetTitle(_t('POLL_NAME'));
        return $this->ListOfPolls();
    }

    /**
     * Adds a new vote to an answer of a certain poll
     *
     * @access  public
     */
    function Vote()
    {
        $request =& Jaws_Request::getInstance();
        $post = $request->get(array('pid', 'answers'), 'post');

        $model = $GLOBALS['app']->LoadGadget('Poll', 'Model');
        $poll = $model->GetPoll($post['pid']);
        if (!Jaws_Error::IsError($poll) && isset($poll['id'])) {
            if ((($poll['poll_type'] == 1) || (!Jaws_Session_Web::GetCookie('poll_'.$post['pid']))) &&
                is_array($post['answers']) && count($post['answers'])>0)
            {
                Jaws_Session_Web::SetCookie('poll_'.$post['pid'], 'voted',
                            (int) $GLOBALS['app']->Registry->Get('/gadgets/Poll/cookie_period')*24*60);
                $model->AddPollVote($post['pid']);
                foreach ($post['answers'] as $aid) {
                    $model->AddAnswerVote($post['pid'], $aid);
                }
            }
        }

        require_once JAWS_PATH . 'include/Jaws/Header.php';
        Jaws_Header::Referrer();
    }

    /**
     * Calls the LastPoll action to print it
     *
     * @access  public
     * @return  string  The last poll to show to normal users
     */
    function LastPoll()
    {
        $layoutGadget = $GLOBALS['app']->LoadGadget('Poll', 'LayoutHTML');
        return $layoutGadget->LastPoll();
    }

    /**
     * Prints all the enabled polls as a layout
     *
     * @access  public
     * @return  string  HTML view of a list of polls
     */
    function ListOfPolls()
    {
        $layoutGadget = $GLOBALS['app']->LoadGadget('Poll', 'LayoutHTML');
        return $layoutGadget->ListOfPolls();
    }

    /**
     * Calls the default action to print a specific poll
     *
     * @access  public
     * @return  string  The poll to show to normal users
     */
    function ViewPoll()
    {
        $layoutGadget = $GLOBALS['app']->LoadGadget('Poll', 'LayoutHTML');
        $request =& Jaws_Request::getInstance();
        $pid = $request->get('id', 'get');
        return $layoutGadget->Display($pid);
    }

    /**
     * Look for a term and prints it
     *
     * @access  public
     * @return  string HTML content
     */
    function ViewResult()
    {
        $request =& Jaws_Request::getInstance();
        $pid = $request->get('id', 'get');

        $model = $GLOBALS['app']->LoadGadget('Poll', 'Model');
        $poll = $model->GetPoll($pid);
        if (Jaws_Error::IsError($poll) || !isset($poll['id']) || ($poll['result_view'] == 0)) {
            return '';
        }

        $tpl = new Jaws_Template('gadgets/Poll/templates/');
        $tpl->Load('Results.html');
        $tpl->SetBlock('results');
        $tpl->SetVariable('title', _t('POLL_ACTION_RESULT_TITLE'));

        $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $tpl->SetVariable('question', $xss->filter($poll['question']));
        $tpl->SetVariable('lbl_total_votes', _t('POLL_REPORTS_TOTAL_VOTES'));
        $tpl->SetVariable('total_votes', $poll['votes']);

        $answers = $model->GetPollAnswers($poll['id']);
        foreach($answers as $answer) {
            $tpl->SetBlock('results/answer');
            $tpl->SetVariable('answer', $answer['answer']);
            $percent = (($poll['votes']==0)? 0 : floor(($answer['votes']/$poll['votes'])*100));
            $tpl->SetVariable('txt_percent', _t('POLL_REPORTS_PERCENT', $percent));
            $tpl->SetVariable('percent', $percent);
            $tpl->SetVariable('votes', $answer['votes']);
            $tpl->ParseBlock('results/answer');
        }

        $tpl->ParseBlock ('results');
        return $tpl->Get();
    }
}