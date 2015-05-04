<?php
/**
 * Websites Gadget
 *
 * @category   GadgetModel
 * @package    Websites
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */

require_once JAWS_PATH . 'gadgets/Websites/Model.php';
class WebsitesAdminModel extends WebsitesModel
{
    var $_Name = 'Websites';
	
	/**
     * Install the gadget
     *
     * @access  public
     * @return  boolean  Success/failure
     */
    function InstallGadget()
    {

        $result = $this->installSchema('schema.xml');
        if (Jaws_Error::IsError($result)) {
            return $result;
        }
        
		if (file_exists(JAWS_PATH . 'gadgets/'.$this->_Name.'/schema/insert.xml')) {
			$variables = array();
			$variables['timestamp'] = $GLOBALS['db']->Date();

			$result = $this->installSchema('insert.xml', $variables, 'schema.xml', true);
			if (Jaws_Error::IsError($result)) {
				return $result;
			}
		}

        //registry keys.
        $GLOBALS['app']->Registry->NewKey('/gadgets/Websites/default_expiration', 30);

        $GLOBALS['app']->loadClass('Listener', 'Jaws_EventListener');
        //$GLOBALS['app']->Listener->NewListener('Websites', 'onDeleteUser', 'RemoveWebsitesSubscribable');

		//Create Jaws_User groups
        require_once JAWS_PATH . 'include/Jaws/User.php';
        $userModel = new Jaws_User;
        $userModel->addGroup('websites_owners', false); //Don't check if it returns true or false
        $group = $userModel->GetGroupInfoByName('websites_owners');
		if (isset($group['id']) && !empty($group['id'])) {
			$GLOBALS['app']->ACL->NewKey('/ACL/groups/'.$group['id'].'/gadgets/Websites/OwnWebsites', 'true');
			$GLOBALS['app']->ACL->NewKey('/ACL/groups/'.$group['id'].'/gadgets/Websites/OwnPublicWebsites', 'true');
        }
        //$userModel->addGroup('custompage_users', false); //Don't check if it returns true or false
        return true;
    }

    /**
     * Uninstall the gadget
     *
     * @access  public
     * @param   string   $gadget  Gadget name (should be the same as $this->_Name, the model name)
     * @return  boolean Success/Failure (JawsError)
     */
    function UninstallGadget()
    {
        $tables = array(
			'websites'
		);
        foreach ($tables as $table) {
            $result = $GLOBALS['db']->dropTable($table);
            if (Jaws_Error::IsError($result)) {
                $gName  = _t('WEBSITES_NAME');
                $errMsg = _t('GLOBAL_ERROR_GADGET_NOT_UNINSTALLED', $gName);
                $GLOBALS['app']->Session->PushLastResponse($errMsg, RESPONSE_ERROR);
                return new Jaws_Error($errMsg, $gName);
            }
        }

        $GLOBALS['app']->loadClass('Listener', 'Jaws_EventListener');
        //$GLOBALS['app']->Listener->DeleteListener('Websites', 'RemoveWebsitesSubscribable');
		
        //registry keys
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/Websites/default_expiration');
		
		//Delete Jaws_User groups
        require_once JAWS_PATH . 'include/Jaws/User.php';
        $userModel = new Jaws_User;
        $group = $userModel->GetGroupInfoByName('websites_owners');
		if (isset($group['id']) && !empty($group['id'])) {
			$userModel->DeleteGroup($group['id']);
			$GLOBALS['app']->ACL->DeleteKey('/ACL/groups/'.$group['id'].'/gadgets/Websites/OwnWebsites');
			$GLOBALS['app']->ACL->DeleteKey('/ACL/groups/'.$group['id'].'/gadgets/Websites/OwnPublicWebsites');
		}
        /*
		$group = $userModel->GetGroupInfoByName('custompage_users');
		if (isset($group['id']) && !empty($group['id'])) {
			$userModel->DeleteGroup($group['id']);
		}
		*/
		return true;
	}

    /**
     * Update the gadget
     *
     * @access  public
     * @param   string  $current Current version (in registry)
     * @param   string  $new     New version (in the $gadgetInfo file)
     * @return  boolean  Success/Failure (JawsError)
     */
    function UpdateGadget($current, $new)
    {
        $currentClean = str_replace(array('.', ' '), '', $current);
        $newClean     = str_replace(array('.', ' '), '', $new);

        $funcName   = 'upgradeFrom' . $currentClean;
        $scriptFile = JAWS_PATH . 'gadgets/' . $this->_Name . '/upgradeScripts/' . $funcName . '.php';
        if (file_exists($scriptFile)) {
            require_once $scriptFile;
            //Ok.. append the funcName at the start
            $funcName = $this->_Name . '_' . $funcName;
            if (function_exists($funcName)) {
                $res = $funcName();
                return $res;
            }
        }
        return true;
    }
	
	
}