<?php
/**
 * Jaws Upgrade Stage - From 0.8.13 to 0.8.14
 *
 * @category   Application
 * @package    UpgradeStage
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class Upgrader_0813To0814 extends JawsUpgraderStage
{
    /**
     * Builds the upgader page.
     *
     * @access  public
     * @return  string A block of valid XHTML to display an introduction and form.
     */
    function Display()
    {
        $tpl = new Jaws_Template(UPGRADE_PATH  . 'stages/0813To0814/templates/');
        $tpl->Load('display.html', false, false);
        $tpl->SetBlock('0813To0814');

        $tpl->setVariable('lbl_info',  _t('UPGRADE_VER_INFO', '0.8.13', '0.8.14'));
        $tpl->setVariable('lbl_notes', _t('UPGRADE_VER_NOTES'));
        $tpl->SetVariable('next',      _t('GLOBAL_NEXT'));

        $tpl->ParseBlock('0813To0814');
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
		$GLOBALS['app']->Registry->NewKey('/config/site_comment', '');
        $GLOBALS['app']->Registry->NewKey('/config/site_favicon', 'images/jaws.png');
        $GLOBALS['app']->Registry->NewKey('/config/site_author',
                                          $GLOBALS['app']->Registry->Get('/config/owner_name'));

        $from_email = $GLOBALS['app']->Registry->Get('/network/from_email');
        $GLOBALS['app']->Registry->NewKey('/network/smtp_vrfy',  empty($from_email)? 'false' : 'true');
        $GLOBALS['app']->Registry->NewKey('/network/site_email', empty($from_email)?
                                                                 $GLOBALS['app']->Registry->Get('/config/owner_email'):
                                                                 $from_email);
        $GLOBALS['app']->Registry->NewKey('/network/email_name',
                                          $GLOBALS['app']->Registry->Get('/network/from_name'));

        $GLOBALS['app']->Registry->Set('/version', JAWS_VERSION);
        $GLOBALS['app']->Registry->Set('/last_update', $timestamp);

        // Commit the changes so they get saved
        $GLOBALS['app']->Registry->commit('core');

        require_once JAWS_PATH . 'include/Jaws/URLMapping.php';
        $GLOBALS['app']->Map = new Jaws_URLMapping();

        $gadgets = array('ControlPanel', 'UrlMapper', 'Users');
        foreach ($gadgets as $gadget) {
            $result = Jaws_Gadget::UpdateGadget($gadget);
            if (Jaws_Error::IsError($result)) {
				log_upgrade("There was a problem upgrading core gadget [".$gadget."]: ".var_export($result, true));
                return new Jaws_Error(_t('UPGRADE_VER_RESPONSE_GADGET_FAILED', $gadget), 0, JAWS_ERROR_ERROR);
            }
        }

		$jms = $GLOBALS['app']->LoadGadget('Jms', 'AdminModel');
		$urlmapping = $GLOBALS['app']->LoadGadget('UrlMapper', 'AdminModel');
		$gadget_list = $jms->GetGadgetsList(null, true, true, null);
		
		//Hold.. if we dont have a selected gadget?.. like no gadgets?
		if (count($gadget_list) <= 0) {
			return false;
		} else {
	
			reset($gadget_list);
			
			foreach ($gadget_list as $gadget) {
				$urlmapping->UpdateGadgetMaps($gadget['realname']);
			}
		}
		
        log_upgrade("Cleaning previous maps cache data files - step 0.8.13->0.8.14");
        //Make sure user don't have any data/maps stuff
        $path = JAWS_DATA . 'maps';
        if (!Jaws_Utils::Delete($path, false)) {
            log_upgrade("Can't delete $path");
        }

        log_upgrade("Cleaning previous registry cache data files - step 0.8.13->0.8.14");
        //Make sure user don't have any data/cache/registry stuff
        $path = JAWS_DATA . 'cache/registry';
        if (!Jaws_Utils::Delete($path, false)) {
            log_upgrade("Can't delete $path");
        }

        log_upgrade("Cleaning previous acl cache data files - step 0.8.13->0.8.14");
        //Make sure user don't have any data/cache/acl stuff
        $path = JAWS_DATA . 'cache/acl';
        if (!Jaws_Utils::Delete($path, false)) {
            log_upgrade("Can't delete $path");
        }

        return true;
    }

}