<?php
/**
 * Users and Groups platform.
 *
 * @category   GadgetInfo
 * @category   feature
 * @package    Users
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @copyright  2004-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class UsersInfo extends Jaws_GadgetInfo
{
    /**
     * Sets info about Users gadget
     *
     * @access  public
     */
    function UsersInfo()
    {
        parent::Init('Users');
        $this->GadgetName(_t('USERS_NAME'));
        $this->GadgetDescription(_t('USERS_DESCRIPTION'));
        $this->GadgetVersion('0.8.7');
        $this->Doc('gadget/Users');
        $this->SetAttribute('core_gadget', true);

        $acls = array(
            'default',
            'ManageUsers',
            'ManageGroups',
            'ManageProperties',
            'ManageMessaging',
            'ManageUserACLs',
            'ManageGroupACLs',
            'EditAccountPassword',
            'EditAccountInformation',
            'EditAccountProfile',
            'EditAccountPreferences',
        );
        $this->PopulateACLs($acls);
    }
}