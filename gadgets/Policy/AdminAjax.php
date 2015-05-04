<?php
/**
 * Policy Ajax API
 *
 * @category   Ajax
 * @package    Policy
 * @author     Amir Mohammad Saied <amir@gluegadget.com>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2007-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class PolicyAdminAjax extends Jaws_Ajax
{
    /**
     * Constructor
     *
     * @access  public
     */
    function PolicyAdminAjax(&$model)
    {
        $this->_Model =& $model;
    }

    /**
     * Get blocked IP range
     *
     * @access  public
     * @param   int     $id ID of the to-be-blocked IP range addresses
     * @return  array   IP range info
     */
    function GetIPRange($id)
    {
        $this->CheckSession('Policy', 'ManageIPs');
        $IPRange = $this->_Model->GetIPRange($id);
        if (Jaws_Error::IsError($IPRange)) {
            return false; //we need to handle errors on ajax
        }

        return $IPRange;
    }

    /**
     * Block an IP range
     *
     * @access  public
     * @param   string  $from_ip  The to-be-blocked from IP
     * @param   string  $to_ip    The to-be-blocked to IP
     * @return  string  Response
     */
    function AddIPRange($from_ip, $to_ip = null)
    {
        $this->CheckSession('Policy', 'ManageIPs');
        $this->_Model->AddIPRange($from_ip, $to_ip);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Edit blocked an IP range
     *
     * @access  public
     * @param   int     $id ID of the to-be-blocked IP range addresses
     * @param   string  $from_ip  The to-be-blocked from IP
     * @param   string  $to_ip    The to-be-blocked to IP
     * @return  string  Response
     */
    function EditIPRange($id, $from_ip, $to_ip)
    {
        $this->CheckSession('Policy', 'ManageIPs');
        $this->_Model->EditIPRange($id, $from_ip, $to_ip);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Delete an IP range
     * 
     * @access  public
     * @param   int $id ID of the-to-be-unblocked IP range addresses
     * @return  string  Response
     */
    function DeleteIPRange($id)
    {
        $this->CheckSession('Policy', 'ManageIPs');
        $this->_Model->DeleteIPRange($id);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Get blocked agent
     *
     * @access  public
     * @param   int $id ID of the agent
     * @return  string Agent
     */
    function GetAgent($id)
    {
        $this->CheckSession('Policy', 'ManageAgents');
        $agent = $this->_Model->GetAgent($id);
        if (Jaws_Error::IsError($agent)) {
            return false; //we need to handle errors on ajax
        }

        return $agent;
    }

    /**
     * Block an agent
     *
     * @access  public
     * @param   string  $agent   Which Agent is supposed to be blocked?
     * @return  string  Response
     */
    function AddAgent($agent)
    {
        $this->CheckSession('Policy', 'ManageAgents');
        $this->_Model->AddAgent($agent);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Block an agent
     *
     * @access  public
     * @param   int     $id     ID of the agent
     * @param   string  $agent  Which Agent is supposed to be blocked?
     * @return  string  Response
     */
    function EditAgent($id, $agent)
    {
        $this->CheckSession('Policy', 'ManageAgents');
        $this->_Model->EditAgent($id, $agent);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Unblock an agent
     *
     * @access  public
     * @param   int $id ID of the agent which is going to be unblocked
     * @return  string  Response
     */
    function DeleteAgent($id)
    {
        $this->CheckSession('Policy', 'ManageAgents');
        $this->_Model->DeleteAgent($id);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Enable BlockByIP
     *
     * @access  public
     * @param   boolean $blockByIP     Enable/Disable block by IP
     * @return  boolean True on success and Jaws error on failure
     */
    function EnableBlockByIP($blockByIP)
    {
        $this->CheckSession('Policy', 'ManageIPs');
        $this->_Model->EnableBlockByIP($blockByIP);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Enable BlockByAgent
     *
     * @access  public
     * @param   boolean $blockByAgent   Enable/Disable block by Agent
     * @return  boolean True on success and Jaws error on failure
     */
    function EnableBlockByAgent($blockByAgent)
    {
        $this->CheckSession('Policy', 'ManageAgents');
        $this->_Model->EnableBlockByAgent($blockByAgent);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Update  Encryption Settings
     *
     * @access  public
     * @param   boolean $enabled   Enable/Disable encryption
     * @param   boolean $key_age   Key age
     * @param   boolean $key_len   Key length
     * @return  boolean True on success and Jaws error on failure
     */
    function UpdateEncryptionSettings($enabled, $key_age, $key_len)
    {
        $this->CheckSession('Policy', 'Encryption');
        $this->_Model->UpdateEncryptionSettings($enabled == 'true', $key_age, $key_len);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Update AntiSpam Settings
     *
     * @access  public
     * @param   boolean $allow_duplicate
     * @param   boolean $filter
     * @param   boolean $captcha
     * @param   boolean $obfuscator
     * @return  boolean True on success and Jaws error on failure
     */
    function UpdateAntiSpamSettings($allow_duplicate, $filter, $captcha, $obfuscator)
    {
        $this->CheckSession('Policy', 'AntiSpam');
        $this->_Model->UpdateAntiSpamSettings($allow_duplicate, $filter, $captcha, $obfuscator);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Update Advanced Policies
     *
     * @access  public
     * @param   string  $passwd_complexity
     * @param   integer $passwd_bad_count
     * @param   integer $passwd_lockedout_time
     * @param   integer $passwd_max_age
     * @param   integer $passwd_min_length
     * @param   string  $xss_parsing_level
     * @param   integer $session_idle_timeout
     * @param   integer $session_remember_timeout
     * @return  boolean True on success and Jaws error on failure
     */
    function UpdateAdvancedPolicies($passwd_complexity, $passwd_bad_count, $passwd_lockedout_time,
                                    $passwd_max_age, $passwd_min_length, $xss_parsing_level,
                                    $session_idle_timeout, $session_remember_timeout)
    {
        $this->CheckSession('Policy', 'AdvancedPolicies');
        $this->_Model->UpdateAdvancedPolicies($passwd_complexity, $passwd_bad_count, $passwd_lockedout_time,
                                    $passwd_max_age, $passwd_min_length, $xss_parsing_level,
                                    $session_idle_timeout, $session_remember_timeout);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Rebuild the datagrid
     *
     * @access  public
     */
    function GetData($offset, $grid)
    {
        $this->CheckSession('Policy', 'ManagePolicy');
        $gadget = $GLOBALS['app']->LoadGadget('Policy', 'AdminHTML');
        if (!is_numeric($offset)) {
            $offset = null;
        }

        $dgData = '';
        switch ($grid) {
        case 'blocked_agents_datagrid':
            $dgData = $gadget->GetBlockedAgents($offset);
            break;
        case 'blocked_ips_datagrid':
            $dgData = $gadget->GetBlockedIPRanges($offset);
            break;
        default:
            break;
        }

        return $dgData;
    }
}