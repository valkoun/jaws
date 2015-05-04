<?php
/**
 * Create and manage multiple security and anti-spam Policies.
 *
 * @category   GadgetInfo
 * @category   feature
 * @package    Policy
 * @author     Amir Mohammad Saied <amir@gluegadget.com>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2007-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class PolicyInfo extends Jaws_GadgetInfo
{
    /**
     * Set the gadget info
     *
     * @access  public
     */
    function PolicyInfo()
    {
        parent::Init('Policy');
        $this->GadgetName(_t('POLICY_NAME'));
        $this->GadgetDescription(_t('POLICY_DESCRIPTION'));
        $this->GadgetVersion('0.1.2');
        $this->Doc('gadget/Policy');
        $this->SetAttribute('core_gadget', true);

        $acls = array(
            'ManagePolicy',
            'IPBlocking',
            'ManageIPs',
            'AgentBlocking',
            'ManageAgents',
            'Encryption',
            'AntiSpam',
            'AdvancedPolicies',
        );

        $this->PopulateACLs($acls);
    }
}