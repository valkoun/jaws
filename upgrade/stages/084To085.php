<?php
/**
 * Jaws Upgrade Stage - From 0.8.4 to 0.8.5
 *
 * @category   Application
 * @package    UpgradeStage
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2008-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class Upgrader_084To085 extends JawsUpgraderStage
{
    /**
     * Builds the upgader page.
     *
     * @access  public
     * @return  string A block of valid XHTML to display an introduction and form.
     */
    function Display()
    {
        $tpl = new Jaws_Template(UPGRADE_PATH  . 'stages/084To085/templates/');
        $tpl->Load('display.html', false, false);
        $tpl->SetBlock('084To085');

        $tpl->setVariable('lbl_info',  _t('UPGRADE_VER_INFO', '0.8.4', '0.8.5'));
        $tpl->setVariable('lbl_notes', _t('UPGRADE_VER_NOTES'));
        $tpl->SetVariable('next',      _t('GLOBAL_NEXT'));

        $tpl->ParseBlock('084To085');
        return $tpl->Get();
    }

    /**
     * Does any actions required to finish the stage, such as DB queries.
     *
     * @access  public
     * @return  bool|Jaws_Error  Either true on success, or a Jaws_Error
     *                          containing the reason for failure.
     */
    function Run()
    {
        // Connect to database
        require_once JAWS_PATH . 'include/Jaws/DB.php';
        $GLOBALS['db'] = new Jaws_DB($_SESSION['upgrade']['Database']);
        if (Jaws_Error::IsError($GLOBALS['db'])) {
            log_upgrade("There was a problem connecting to the database, please check the details and try again");
            return new Jaws_Error(_t('UPGRADE_DB_RESPONSE_CONNECT_FAILED'), 0, JAWS_ERROR_WARNING);
        }

        // Create application
        include_once JAWS_PATH . 'include/Jaws.php';
        $GLOBALS['app'] = new Jaws();
        $GLOBALS['app']->create();
        $GLOBALS['app']->OverwriteDefaults(array('language' => $_SESSION['upgrade']['language']));
        $GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');
        include_once JAWS_PATH . 'include/Jaws/Version.php';

        // Input datas
        $timestamp = $GLOBALS['db']->Date();

        //registry keys
		$GLOBALS['app']->Registry->NewKey('/config/site_ssl_url', '');
		$GLOBALS['app']->Registry->NewKey('/config/pageconst', 12);
		$GLOBALS['app']->Registry->NewKey('/config/whmcs_url', '');
		$GLOBALS['app']->Registry->NewKey('/config/whmcs_user', '');
		$GLOBALS['app']->Registry->NewKey('/config/whmcs_password', '');
		$GLOBALS['app']->Registry->NewKey('/gadgets/user_access_items', '');
		$GLOBALS['app']->Registry->NewKey('/gadgets/require_https', '');
		
        $GLOBALS['app']->Registry->Set('/version', JAWS_VERSION);
        $GLOBALS['app']->Registry->Set('/config/browsers_flag', 'opera,firefox,ie7up,ie,safari,nav,konq,gecko,text');
        $GLOBALS['app']->Registry->Set('/last_update', $timestamp);

        // Commit the changes so they get saved
        $GLOBALS['app']->Registry->commit('core');

        $gadgets = array('ControlPanel', 'UrlMapper', 'Users');
        foreach ($gadgets as $gadget) {
            $result = Jaws_Gadget::UpdateGadget($gadget);
            if (Jaws_Error::IsError($result)) {
                log_upgrade("There was a problem upgrading core gadget: $gadget");
                return new Jaws_Error(_t('UPGRADE_VER_RESPONSE_GADGET_FAILED', $gadget), 0, JAWS_ERROR_ERROR);
            }
        }
		
        log_upgrade("Cleaning previous maps cache data files - step 0.8.4->0.8.5");
        //Make sure user don't have any data/maps stuff
        $path = JAWS_DATA . 'maps';
        if (!Jaws_Utils::Delete($path, false)) {
            log_upgrade("Can't delete $path");
        }

        log_upgrade("Cleaning previous registry cache data files - step 0.8.4->0.8.5");
        //Make sure user don't have any data/cache/registry stuff
        $path = JAWS_DATA . 'cache/registry';
        if (!Jaws_Utils::Delete($path, false)) {
            log_upgrade("Can't delete $path");
        }

        log_upgrade("Cleaning previous acl cache data files - step 0.8.4->0.8.5");
        //Make sure user don't have any data/cache/acl stuff
        $path = JAWS_DATA . 'cache/acl';
        if (!Jaws_Utils::Delete($path, false)) {
            log_upgrade("Can't delete $path");
        }

        return true;
    }
}
