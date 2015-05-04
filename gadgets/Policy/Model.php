<?php
/**
 * Policy Gadget
 *
 * @category   GadgetModel
 * @package    Policy
 * @author     Amir Mohammad Saied <amir@gluegadget.com>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2007-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class PolicyModel extends Jaws_Model
{
    /**
     * Checks wheter the IP is blocked or not
     *
     * @access  public
     * @param   string  $ip IP Address
     * @return  boolean True if the IP is blocked
     */
    function IsIPBlocked($ip)
    {
        $ip_pattern = '/\b(?:\d{1,3}\.){3}\d{1,3}\b/';
        if (preg_match($ip_pattern, $ip)) {
            $ip = ip2long($ip);
            if ($ip < 0) {
                $ip = $ip + 0xffffffff + 1;
            }

            $sql = 'SELECT COUNT(*) FROM [[policy_ipblock]] WHERE {ip} BETWEEN [from_ip] AND [to_ip]';
            $rs  = $GLOBALS['db']->queryOne($sql, array('ip' => $ip));
            if ($rs > 0) return true;
        }

        return false;
    }

    /**
     * Checks wheter the Agent is blocked or not
     *
     * @access  public
     * @param   string  $agent  Agent
     * @return  boolean True if the Agent is blocked
     */
    function IsAgentBlocked($agent)
    {
        $xss   = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $agent = $xss->parse($agent);

        $sql = 'SELECT COUNT([agent]) FROM [[policy_agentblock]] WHERE [agent] = {agent}';
        $rs  = $GLOBALS['db']->queryOne($sql, array('agent' => $agent));

        if ((int)$rs == 1)
            return true;

        return false;
    }

}
?>
