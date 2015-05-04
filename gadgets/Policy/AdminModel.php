<?php
/**
 * Policy Gadget Admin
 *
 * @category   GadgetModel
 * @package    Policy
 * @author     Amir Mohammad Saied <amir@gluegadget.com>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2007-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
require_once JAWS_PATH . 'gadgets/Policy/Model.php';

class PolicyAdminModel extends PolicyModel
{
    /**
     * Installs the gadget
     *
     * @access    public
     * @return    boolean Returns true on a successfull attempt and Jaws Error otherwise
    */
    function InstallGadget()
    {
        $result = $this->installSchema('schema.xml');
        if (Jaws_Error::IsError($result)) {
            return $result;
        }

        // Registry keys
        $GLOBALS['app']->Registry->NewKeyEx(array('/gadgets/Policy/block_by_ip',     'true'),
                                            array('/gadgets/Policy/block_by_agent',  'true'),
                                            array('/gadgets/Policy/allow_duplicate', 'no'),
                                            array('/gadgets/Policy/filter',          'Akismet'),
                                            array('/gadgets/Policy/captcha',         'SimpleCaptcha'),
                                            array('/gadgets/Policy/obfuscator',      'DISABLED'),
                                            array('/gadgets/Policy/akismet_key',     ''),
                                            array('/gadgets/Policy/typepad_key',     '')
                                            );
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
        if (version_compare($old, '0.1.1', '<')) {
            // Registry keys
            $obfuscator = $GLOBALS['app']->Registry->Get('/gadgets/Policy/obfuscator');
            if ($obfuscator == 'HideEmail') {
                $GLOBALS['app']->Registry->Set('/gadgets/Policy/obfuscator', 'EmailEncoder');
            }

            $tables = array('complexcaptcha',
                            'mathcaptcha',
                            'simplecaptcha');
            foreach ($tables as $table) {
                $result = $GLOBALS['db']->dropTable($table);
                if (Jaws_Error::IsError($result)) {
                    // do nothing
                }
            }

            $GLOBALS['app']->Registry->DeleteKey('/gadgets/Policy/complex_captcha');
            $GLOBALS['app']->Registry->DeleteKey('/gadgets/Policy/math_captcha');
            $GLOBALS['app']->Registry->DeleteKey('/gadgets/Policy/simple_captcha');
        }

        $GLOBALS['app']->Registry->NewKey('/gadgets/Policy/typepad_key', '');
        return true;
    }

    /**
     * Get blocked IP range
     *
     * @access  public
     * @param   int     $id ID of the to-be-blocked IP range addresses
     * @return  array IP range info or Jaws_Error on failure
     */
    function GetIPRange($id)
    {
        $sql = '
            SELECT [id], [from_ip], [to_ip]
            FROM [[policy_ipblock]]
            WHERE [id] = {id}';

        $params       = array();
        $params['id'] = $id;

        $res = $GLOBALS['db']->queryRow($sql, $params);
        if (Jaws_Error::IsError($res)) {
            return new Jaws_Error($res->getMessage(), 'SQL');
        }

        if (isset($res['id'])) {
            $res['from_ip'] = long2ip($res['from_ip']);
            $res['to_ip']   = long2ip($res['to_ip']);
        }

        return $res;
    }

    /**
     * Get blocked agent
     *
     * @access  public
     * @param   int $id ID of the agent
     * @return  string agent or Jaws_Error on failure
     */
    function GetAgent($id)
    {
        $sql = '
            SELECT [id], [agent]
            FROM [[policy_agentblock]]
            WHERE [id] = {id}';

        $params       = array();
        $params['id'] = $id;

        $res = $GLOBALS['db']->queryRow($sql, $params);
        if (Jaws_Error::IsError($res)) {
            return new Jaws_Error($res->getMessage(), 'SQL');
        }

        return $res;
    }

    /**
     * Returns total of blocked IPs
     *
     * @access  public
     * @return  DB resource
     */
    function GetTotalOfBlockedIPs()
    {
        $sql = 'SELECT COUNT([id]) as total FROM [[policy_ipblock]]';
        $rs  = $GLOBALS['db']->queryOne($sql);

        return $rs;
    }

    /**
     * Returns total of blocked Agents
     *
     * @access  public
     * @return  DB Resource
     */
    function GetTotalOfBlockedAgents()
    {
        $sql = 'SELECT COUNT([id]) as total FROM [[policy_agentblock]]';
        $rs  = $GLOBALS['db']->queryOne($sql);

        return $rs;
    }

    /**
     * Retrieve all blocked IPs
     *
     * @param   mixed   $limit  Limit of data to retrieve (false by default, returns all)
     * @param   int   $offset  Data offset
     * @access  public
     * @return  array   An array contains all IP and info. and Jaws_Error on error
     */
    function GetBlockedIPs($limit = 0, $offset = null)
    {
        if (!empty($limit)) {
            $res = $GLOBALS['db']->setLimit($limit, $offset);
            if (Jaws_Error::IsError($res)) {
                return new Jaws_Error($res->getMessage(), 'SQL');
            }
        }

        $sql = '
            SELECT
                [id], [from_ip], [to_ip]
            FROM [[policy_ipblock]]
            ORDER BY [id] DESC';
        $rs = $GLOBALS['db']->queryAll($sql);
        if (Jaws_Error::IsError($rs)) {
            return new Jaws_Error($rs->getMessage(), 'SQL');
        }
        
        return $rs;
    }

    /**
     * Retrieve all blocked Agents
     *
     * @param   mixed   $limit  Limit of data to retrieve (false by default, returns all)
     * @access  public
     * @return  array   An array contains all blocked Agents
     */
    function GetBlockedAgents($limit = 0, $offset = null)
    {
        if (!empty($limit)) {
            $res = $GLOBALS['db']->setLimit($limit, $offset);
            if (Jaws_Error::IsError($res)) {
                return new Jaws_Error($res->getMessage(), 'SQL');
            }
        }

        $sql = '
            SELECT
                [id], [agent]
            FROM [[policy_agentblock]]
            ORDER BY [id] DESC';
        $rs = $GLOBALS['db']->queryAll($sql);
        if (Jaws_Error::IsError($rs)) {
            return new Jaws_Error($rs->getMessage(), 'SQL');
        }

        return $rs;
    }

    /**
     * Block IP addresses by range.
     *
     * @category  feature
     * @param   string  $from_ip 	Start range of the IP address(es) to be blocked
     * @param   string  $to_ip 	End range of the IP address(es) to be blocked
     * @access  public
     * @return  boolean True on success and Jaws_Error on errors
     */
    function AddIPRange($from_ip, $to_ip = null)
    {
        $from_ip = ip2long($from_ip);
        if ($from_ip < 0) {
            $from_ip = $from_ip + 0xffffffff + 1;
        }

        if (empty($to_ip)) {
            $to_ip = $from_ip;
        } else {
            $to_ip = ip2long($to_ip);
            if ($to_ip < 0) $to_ip = $to_ip + 0xffffffff + 1;
        }

        $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $sql = '
            INSERT INTO [[policy_ipblock]]
                ([from_ip], [to_ip])
            VALUES
                ({from_ip}, {to_ip})';

        $params = array();
        $params['from_ip'] = $from_ip;
        $params['to_ip']   = $to_ip;

        $rs = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($rs)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('POLICY_RESPONSE_IP_NOT_ADDED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('POLICY_RESPONSE_IP_NOT_ADDED', 'AddIPRange'), _t('POLICY_NAME'));
        }

        $GLOBALS['app']->Session->PushLastResponse(_t('POLICY_RESPONSE_IP_ADDED'), RESPONSE_NOTICE);

        return true;
    }

    /**
     * Edit blocked IP range
     *
     * @access  public
     * @param   int     $id ID of the to-be-blocked IP range addresses
     * @param   string  $from_ip  The to-be-blocked from IP
     * @param   string  $to_ip    The to-be-blocked to IP
     * @return  boolean True on success and Jaws_Error on errors
     */
    function EditIPRange($id, $from_ip, $to_ip = null)
    {
        $from_ip = ip2long($from_ip);
        if ($from_ip < 0) {
            $from_ip = $from_ip + 0xffffffff + 1;
        }

        if (empty($to_ip)) {
            $to_ip = $from_ip;
        } else {
            $to_ip = ip2long($to_ip);
            if ($to_ip < 0) $to_ip = $to_ip + 0xffffffff + 1;
        }

        $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $sql = '
            UPDATE [[policy_ipblock]] SET
                [from_ip] = {from_ip},
                [to_ip]   = {to_ip}
            WHERE [id] = {id}';

        $params = array();
        $params['id']      = $id;
        $params['from_ip'] = $xss->parse($from_ip);
        $params['to_ip']   = $xss->parse($to_ip);

        $res = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($res)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('POLICY_RESPONSE_IP_NOT_DELETED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('POLICY_RESPONSE_IP_NOT_DELETED', 'EditIPRange'), _t('POLICY_NAME'));
        }

        $GLOBALS['app']->Session->PushLastResponse(_t('POLICY_RESPONSE_IP_EDITED'), RESPONSE_NOTICE);
        return true;
    }

    /**
     * Unblock an IP range
     *
     * @access  public
     * @param   int $id ID of the to be unblocked IP Band
     * @return  boolean True on successfull attempts and Jaws Error otherwise
     */
    function DeleteIPRange($id)
    {
        $sql = 'DELETE FROM [[policy_ipblock]] WHERE [id] = {id}';
        $rs  = $GLOBALS['db']->query($sql, array('id' => (int)$id));
        if (Jaws_Error::IsError($rs)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('POLICY_RESPONSE_IP_NOT_DELETED'), RESPONSE_ERROR);
            return new Jaws_Error($rs->getMessage(), 'SQL');
        }

        $GLOBALS['app']->Session->PushLastResponse(_t('POLICY_RESPONSE_IP_DELETED'), RESPONSE_NOTICE);

        return true;
    }

    /**
     * Block specific user agents.
     *
     * @category  feature
     * @access  public
     * @param   string  The to-be-blocked Agent string
     * @return  True on success and Jaws error on failures
     */
    function AddAgent($agent)
    {
        $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');

        $sql = '
            INSERT INTO [[policy_agentblock]]
                ([agent])
            VALUES
                ({agent})';

        $res = $GLOBALS['db']->query($sql, array('agent' => $xss->parse($agent)));
        if (Jaws_Error::IsError($res)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('POLICY_RESPONSE_AGENT_NOT_ADDEDD'), RESPONSE_ERROR);
            return new Jaws_Error(_t('POLICY_RESPONSE_AGENT_NOT_ADDEDD', 'AddAgent'), _t('POLICY_NAME'));
        }

        $GLOBALS['app']->Session->PushLastResponse(_t('POLICY_RESPONSE_AGENT_ADDED'), RESPONSE_NOTICE);
        return true;
    }

    /**
     * Edit Blocked Agent
     *
     * @access  public
     * @param   int     $id     ID of the agent
     * @param   string  $agent  The to-be-blocked Agent string
     * @return  True on success and Jaws error on failures
     */
    function EditAgent($id, $agent)
    {
        $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');

        $sql = '
            UPDATE [[policy_agentblock]] SET
                [agent] = {agent}
            WHERE [id] = {id}';

        $params = array();
        $params['id']      = $id;
        $params['agent']   = $xss->parse($agent);

        $res = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($res)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('POLICY_RESPONSE_AGENT_NOT_EDITED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('POLICY_RESPONSE_AGENT_NOT_EDITED', 'EditAgent'), _t('POLICY_NAME'));
        }

        $GLOBALS['app']->Session->PushLastResponse(_t('POLICY_RESPONSE_AGENT_EDITED'), RESPONSE_NOTICE);
        return true;
    }

    /**
     * Unblock an Agent
     *
     * @access  public
     * @param   int $id ID of the-to-be-unblocked-agent
     * @return  boolean true on success and Jaws error on failure
     */
    function DeleteAgent($id)
    {
        $sql = 'DELETE FROM [[policy_agentblock]] WHERE [id] = {id}';
        $rs  = $GLOBALS['db']->query($sql, array('id' => (int)$id));
        if (Jaws_Error::IsError($rs)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('POLICY_RESPONSE_AGENT_NOT_DELETED'), RESPONSE_ERROR);
            return new Jaws_Error($rs->getMessage(), 'SQL');
        }

        $GLOBALS['app']->Session->PushLastResponse(_t('POLICY_RESPONSE_AGENT_DELETED'), RESPONSE_NOTICE);

        return true;
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
        $res = $GLOBALS['app']->Registry->Set('/gadgets/Policy/block_by_ip', $blockByIP ? 'true' : 'false');
        if (Jaws_Error::IsError($res)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('POLICY_RESPONSE_PROPERTIES_NOT_UPDATED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('POLICY_RESPONSE_PROPERTIES_NOT_UPDATED'), _t('POLICY_NAME'));
        }

        $GLOBALS['app']->Registry->Commit('Policy');
        $GLOBALS['app']->Session->PushLastResponse(_t('POLICY_RESPONSE_PROPERTIES_UPDATED'), RESPONSE_NOTICE);

        return true;
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
        $res = $GLOBALS['app']->Registry->Set('/gadgets/Policy/block_by_agent', $blockByAgent ? 'true' : 'false');
        if (Jaws_Error::IsError($res)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('POLICY_RESPONSE_PROPERTIES_NOT_UPDATED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('POLICY_RESPONSE_PROPERTIES_NOT_UPDATED'), _t('POLICY_NAME'));
        }

        $GLOBALS['app']->Registry->Commit('Policy');
        $GLOBALS['app']->Session->PushLastResponse(_t('POLICY_RESPONSE_PROPERTIES_UPDATED'), RESPONSE_NOTICE);

        return true;
    }

    /**
     * Manage encryption settings.
     *
     * @category  feature
     * @param   boolean 	$enabled   Enable/Disable encryption
     * @param   boolean 	$key_age   Key age
     * @param   boolean 	$key_len   Key length
     * @access  public
     * @return  boolean 	True on success and Jaws error on failure
     */
    function UpdateEncryptionSettings($enabled, $key_age, $key_len)
    {
        $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $GLOBALS['app']->Registry->Set('/crypt/enabled', ($enabled? 'true' : 'false'));
        $GLOBALS['app']->Registry->Set('/crypt/key_age', $xss->parse($key_age));
        $key_len = $xss->parse($key_len);
        if ($GLOBALS['app']->Registry->Get('/crypt/key_len') != $key_len) {
            $GLOBALS['app']->Registry->Set('/crypt/key_start_date', 0);
        }
        $GLOBALS['app']->Registry->Set('/crypt/key_len', $key_len);
        $GLOBALS['app']->Registry->Commit('core');
        $GLOBALS['app']->Session->PushLastResponse(_t('POLICY_RESPONSE_ENCRYPTION_UPDATED'), RESPONSE_NOTICE);
        return true;

    }

    /**
     * Manage anti-spam settings (deny duplicate messages, XSS filtering, captchas, obfuscation).
     *
     * @category  feature
     * @param   boolean $allow_duplicate
     * @param   boolean $filter
     * @param   boolean $captcha
     * @param   boolean $obfuscator
     * @access  public
     * @return  boolean True on success and Jaws error on failure
     */
    function UpdateAntiSpamSettings($allow_duplicate, $filter, $captcha, $obfuscator)
    {
        $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $GLOBALS['app']->Registry->Set('/gadgets/Policy/allow_duplicate', $allow_duplicate);
        $GLOBALS['app']->Registry->Set('/gadgets/Policy/filter',          $filter);
        $GLOBALS['app']->Registry->Set('/gadgets/Policy/captcha',         $captcha);
        $GLOBALS['app']->Registry->Set('/gadgets/Policy/obfuscator',      $obfuscator);
        $GLOBALS['app']->Registry->Commit('Policy');
        $GLOBALS['app']->Session->PushLastResponse(_t('POLICY_RESPONSE_ANTISPAM_UPDATED'), RESPONSE_NOTICE);
        return true;
    }

    /**
     * Advanced policies (password complexity and age, XSS levels, session lockouts and timeouts)
     *
     * @category  feature
     * @param   string  $passwd_complexity
     * @param   integer $passwd_bad_count
     * @param   integer $passwd_lockedout_time
     * @param   integer $passwd_max_age
     * @param   integer $passwd_min_length
     * @param   string  $xss_parsing_level
     * @param   integer $session_idle_timeout
     * @param   integer $session_remember_timeout
     * @access  public
     * @return  boolean True on success and Jaws error on failure
     */
    function UpdateAdvancedPolicies($passwd_complexity, $passwd_bad_count, $passwd_lockedout_time,
                                    $passwd_max_age, $passwd_min_length, $xss_parsing_level,
                                    $session_idle_timeout, $session_remember_timeout)
    {
        $GLOBALS['app']->Registry->Set('/policy/passwd_complexity',     ($passwd_complexity=='yes')? 'yes' : 'no');
        $GLOBALS['app']->Registry->Set('/policy/passwd_bad_count',      (int)$passwd_bad_count);
        $GLOBALS['app']->Registry->Set('/policy/passwd_lockedout_time', (int)$passwd_lockedout_time);
        $GLOBALS['app']->Registry->Set('/policy/passwd_max_age',        (int)$passwd_max_age);
        $GLOBALS['app']->Registry->Set('/policy/passwd_min_length',     (int)$passwd_min_length);
        $GLOBALS['app']->Registry->Set('/policy/xss_parsing_level',     ($xss_parsing_level=='paranoid')? 'paranoid' : 'normal');
        $GLOBALS['app']->Registry->Set('/policy/session_idle_timeout',     (int)$session_idle_timeout);
        $GLOBALS['app']->Registry->Set('/policy/session_remember_timeout', (int)$session_remember_timeout);
        $GLOBALS['app']->Registry->Commit('core');
        $GLOBALS['app']->Session->PushLastResponse(_t('POLICY_RESPONSE_ADVANCED_POLICIES_UPDATED'), RESPONSE_NOTICE);
        return true;
    }

    /**
     * Get filters
     *
     * @access public
     * @return array Array with the filters names.
     */
    function GetFilters()
    {
        $result = array();
        $path = JAWS_PATH . 'gadgets/Policy/filters/';
        $adr = scandir($path);
        foreach ($adr as $file) {
            if (substr($file, -4) == '.php') {
                $result[$file] = substr($file, 0, -4);
            }
        }
        sort($result);
        return $result;
    }

    /**
     * Get captchas
     *
     * @access public
     * @return array Array with the captchas names.
     */
    function GetCaptchas()
    {
        $result = array();
        $path = JAWS_PATH . 'gadgets/Policy/captchas/';
        $adr = scandir($path);
        foreach ($adr as $file) {
            if (substr($file, -4) == '.php') {
                $result[$file] = substr($file, 0, -4);
            }
        }
        sort($result);
        return $result;
    }

    /**
     * Get filters
     *
     * @access public
     * @return array Array with the obfuscators names.
     */
    function GetObfuscators()
    {
        $result = array();
        $path = JAWS_PATH . 'gadgets/Policy/obfuscators/';
        $adr = scandir($path);
        foreach ($adr as $file) {
            if (substr($file, -4) == '.php') {
                $result[$file] = substr($file, 0, -4);
            }
        }
        sort($result);
        return $result;
    }
}