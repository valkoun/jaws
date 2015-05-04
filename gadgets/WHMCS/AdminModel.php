<?php
/**
 * WHMCS Gadget
 *
 * @category   GadgetModel
 * @package    WHMCS
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2012 Alan Valkoun
 */

require_once JAWS_PATH . 'gadgets/WHMCS/Model.php';
class WHMCSAdminModel extends WHMCSModel
{
    var $_Name = 'WHMCS';
	
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

		// Registry keys
		$GLOBALS['app']->Registry->NewKey('/gadgets/WHMCS/whmcs_url', '');
		$GLOBALS['app']->Registry->NewKey('/gadgets/WHMCS/whmcs_api', '');
		$GLOBALS['app']->Registry->NewKey('/gadgets/WHMCS/whmcs_auth', '');
        
		// Events
        $GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
        $GLOBALS['app']->Shouter->NewShouter('WHMCS', 'onAddWHMCSClient');   	// trigger an action when we add
        $GLOBALS['app']->Shouter->NewShouter('WHMCS', 'onDeleteWHMCSClient');	// trigger an action when we delete
        $GLOBALS['app']->Shouter->NewShouter('WHMCS', 'onUpdateWHMCSClient');	// and when we update..

		$GLOBALS['app']->loadClass('Listener', 'Jaws_EventListener');
        $GLOBALS['app']->Listener->NewListener('WHMCS', 'onDeleteUser', 'RemoveWHMCSUser');
		
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
        $tables = array('users_whmcsclients');
        foreach ($tables as $table) {
            $result = $GLOBALS['db']->dropTable($table);
            if (Jaws_Error::IsError($result)) {
                $gName  = _t('WHMCS_NAME');
                $errMsg = _t('GLOBAL_ERROR_GADGET_NOT_UNINSTALLED', $gName);
                $GLOBALS['app']->Session->PushLastResponse($errMsg, RESPONSE_ERROR);
                return new Jaws_Error($errMsg, $gName);
            }
        }

        // Registry keys
		$GLOBALS['app']->Registry->DeleteKey('/gadgets/WHMCS/whmcs_url');
		$GLOBALS['app']->Registry->DeleteKey('/gadgets/WHMCS/whmcs_api');
		$GLOBALS['app']->Registry->DeleteKey('/gadgets/WHMCS/whmcs_auth');

        // Events
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
        $GLOBALS['app']->Shouter->DeleteShouter('WHMCS', 'onAddWHMCSClient');   		// trigger an action when we add
        $GLOBALS['app']->Shouter->DeleteShouter('WHMCS', 'onDeleteWHMCSClient');		// trigger an action when we delete
        $GLOBALS['app']->Shouter->DeleteShouter('WHMCS', 'onUpdateWHMCSClient');		// and when we update..

		$GLOBALS['app']->loadClass('Listener', 'Jaws_EventListener');
        $GLOBALS['app']->Listener->DeleteListener('WHMCS', 'RemoveWHMCSUser');
        
		return true;
    }

    /**
     * Update the gadget
     *
     * @access  public
     * @param   string  $old Current version (in registry)
     * @param   string  $new     New version (in the $gadgetInfo file)
     * @return  boolean  Success/Failure (JawsError)
     */
    function UpdateGadget($old, $new)
    {		
        $currentClean = str_replace(array('.', ' '), '', $old);
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
				
    /**
     * Creates a new client.
     *
     * @access  public
     * @param   string  $user_id			User ID
     * @return  bool    Success/failure
     */
    function AddClient($user_id, $whmcs_id, $checksum = '')
    {
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		
		$sql = "
            INSERT INTO [[users_whmcsclients]]
                ([user_id], [whmcs_id], [created], [updated], [checksum])
            VALUES
                ({user_id}, {whmcs_id}, {now}, {now}, {checksum})";
		
        $params               			= array();
        $params['user_id']         		= $xss->parse($user_id);
        $params['whmcs_id'] 			= $xss->parse($whmcs_id);
        $params['checksum'] 			= $checksum;
        $params['now']        			= $GLOBALS['db']->Date();
		
		$result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('WHMCS_ERROR_WHMCS_USER_NOT_ADDED'), RESPONSE_ERROR);
            return $result;
        }
        $newid = $GLOBALS['db']->lastInsertID('users_whmcsclients', 'id');

		if (empty($checksum)) {
			// Update checksum
			$config_key = $GLOBALS['app']->Registry->Get('/config/key');		
			$params               	= array();
			$params['id'] 			= $newid;
			$params['checksum'] 	= $newid.':'.$config_key;
			
			$sql = '
				UPDATE [[users_whmcsclients]] SET
					[checksum] = {checksum}
				WHERE [id] = {id}';

			$result = $GLOBALS['db']->query($sql, $params);
			if (Jaws_Error::IsError($result)) {
				$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
				return false;
			}
		}
		
		// Let everyone know it has been added
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onAddWHMCSClient', $newid);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		
        $GLOBALS['app']->Session->PushLastResponse(_t('WHMCS_WHMCS_USER_CREATED'), RESPONSE_NOTICE);
        return $newid;
    }

    /**
     * Updates a client.
     *
     * @access  public
     * @param   int     $id             The ID of the client to update.
     * @param   string  $user_id			User ID
     * @param   string  $whmcs_id			WHMCS ID
     * @return  boolean Success/failure
     */
    function UpdateClient($id, $user_id, $whmcs_id)
	{
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        
		$model = $GLOBALS['app']->LoadGadget('WHMCS', 'Model');
        $page = $model->GetClient($id);
        if (Jaws_Error::isError($page)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('WHMCS_ERROR_WHMCS_USER_NOT_FOUND'), RESPONSE_ERROR);
            return $page;
        }

        $sql = '
            UPDATE [[users_whmcsclients]] SET
				[user_id] = {user_id}, 
				[whmcs_id] = {whmcs_id}, 
				[updated] = {now} 
				WHERE [id] = {id}';

        $params               			= array();
        $params['id']         			= (int)$id;
        $params['type']         		= $xss->parse($user_id);
        $params['url'] 					= $xss->parse($whmcs_id);
        $params['now']        			= $GLOBALS['db']->Date();		

        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('WHMCS_ERROR_WHMCS_USER_NOT_UPDATED'), RESPONSE_ERROR);
			//$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
            return $result;
        }
		
		// Let everyone know it has been added
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onUpdateWHMCSClient', $id);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		
		$GLOBALS['app']->Session->PushLastResponse(_t('WHMCS_WHMCS_USER_UPDATED'), RESPONSE_NOTICE);
        return true;
    }

	/**
     * Delete a client
     *
     * @access  public
     * @return  boolean True if query was successful and Jaws_Error on error
     */
    function DeleteClient($id, $massive = false)
    {
		$model = $GLOBALS['app']->LoadGadget('WHMCS', 'Model');
		$parent = $model->GetClient((int)$id);
		if (Jaws_Error::IsError($parent)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('WHMCS_ERROR_WHMCS_USER_NOT_DELETED'), RESPONSE_ERROR);
			return new Jaws_Error(_t('WHMCS_ERROR_WHMCS_USER_NOT_DELETED'), _t('WHMCS_NAME'));
		}

		// Let everyone know it has been added
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onDeleteWHMCSClient', $id);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		
		if(!isset($parent['id'])) {
			$GLOBALS['app']->Session->PushLastResponse(_t('WHMCS_ERROR_WHMCS_USER_NOT_DELETED'), RESPONSE_ERROR);
			return new Jaws_Error(_t('WHMCS_ERROR_WHMCS_USER_NOT_DELETED'), _t('WHMCS_NAME'));
		} else {
			$sql = 'DELETE FROM [[users_whmcsclients]] WHERE [id] = {id}';
			$res = $GLOBALS['db']->query($sql, array('id' => $id));
			if (Jaws_Error::IsError($res)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('WHMCS_ERROR_WHMCS_USER_NOT_DELETED'), RESPONSE_ERROR);
				return new Jaws_Error(_t('WHMCS_ERROR_WHMCS_USER_NOT_DELETED'), _t('WHMCS_NAME'));
			}
		}

		if ($massive === false) {
			$GLOBALS['app']->Session->PushLastResponse(_t('WHMCS_WHMCS_USER_DELETED'), RESPONSE_NOTICE);
        }
		return true;
    }

    /**
     * Deletes a group of clients
     *
     * @access  public
     * @param   array   $pages  Array with the ids of pages
     * @return  bool    Success/failure
     */
    function MassiveDelete($pages)
    {
        if (!is_array($pages)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('WHMCS_ERROR_WHMCS_USER_NOT_MASSIVE_DELETED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('WHMCS_ERROR_WHMCS_USER_NOT_MASSIVE_DELETED'), _t('ADS_NAME'));
        }

        foreach ($pages as $page) {
            $res = $this->DeleteClient($page);
            if (Jaws_Error::IsError($res)) {
                $GLOBALS['app']->Session->PushLastResponse(_t('WHMCS_ERROR_WHMCS_USER_NOT_MASSIVE_DELETED'), RESPONSE_ERROR);
                return new Jaws_Error(_t('WHMCS_ERROR_WHMCS_USER_NOT_MASSIVE_DELETED'), _t('ADS_NAME'));
            }
        }
        $GLOBALS['app']->Session->PushLastResponse(_t('WHMCS_WHMCS_USER_MASSIVE_DELETED'), RESPONSE_NOTICE);
        return true;
    }

    /**
     * Search for clients that matches a status and/or a keyword
     * in the title or content
     *
     * @param   string  $status  Status of page(s) we want to display
     * @param   string  $search  Keyword (title/description) of pages we want to look for
     * @param   int     $offSet  Data limit
     * @access  public
     * @return  array   Array of matches
     */
    function SearchClients($status, $search, $offSet = null, $OwnerID = null)
    {
        $params = array();


        $sql = '
            SELECT [id], [user_id], [whmcs_id], [created], [updated], [checksum]
            FROM [[users_whmcsclients]]
			WHERE ([id] > 0)';
		
		/*
		if (trim($search) != '') {
            $searchdata = explode(' ', $search);
            $i = 0;
            foreach ($searchdata as $v) {
                $v = trim($v);
                $sql .= " AND ([adparentcategory_name] LIKE {textLike_".$i."} OR [adparentfast_url] LIKE {textLike_".$i."} OR [adparentdescription] LIKE {textLike_".$i."} OR [adparentrss_url] LIKE {textLike_".$i."})";
                $params['textLike_'.$i] = '%'.$v.'%';
                $i++;
            }
        }
		*/


        if (is_numeric($offSet)) {
            $limit = 10;
            $result = $GLOBALS['db']->setLimit(10, $offSet);
            if (Jaws_Error::IsError($result)) {
                return new Jaws_Error(_t('WHMCS_ERROR_WHMCS_USERS_NOT_RETRIEVED'), _t('WHMCS_NAME'));
            }
        }

        $sql.= ' ORDER BY [id] ASC';

        $types = array(
			'integer', 'integer', 'integer', 'timestamp', 'timestamp', 'text'
		);
	    
        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('WHMCS_ERROR_WHMCS_USERS_NOT_RETRIEVED'), _t('WHMCS_NAME'));
        }
        return $result;
    }
		
    /**
     * Deletes a User's WHMCS client
     *
     * @access  public
     * @param   int  $uid  User ID
     * @return  array   Response
     */
    function RemoveWHMCSUser($uid) 
    {
		$model = $GLOBALS['app']->LoadGadget('WHMCS', 'Model');
		$page = $model->GetClientByUserID($uid);
		if (!Jaws_Error::IsError($page) && isset($page['id']) && !empty($page['id'])) {
			$result = $this->DeleteClient($page['id'], true);
			if (Jaws_Error::IsError($result)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('WHMCS_ERROR_WHMCS_USER_NOT_DELETED'), RESPONSE_ERROR);
				return false;
			}
		} else {
			$GLOBALS['app']->Session->PushLastResponse(_t('WHMCS_ERROR_WHMCS_USER_NOT_DELETED'), RESPONSE_NOTICE);
			return false;
		}
		return true;
    }	

    /**
     * Save config settings
     *
     * @access  public
     * @param   string  $whmcs_url  WHMCS API URL
     * @param   string  $whmcs_api    WHMCS API username
     * @param   string  $whmcs_auth      WHMCS API Authentication key
     * @return  boolean Success/Failure
     */
    function SaveSettings(
		$whmcs_url, $whmcs_api, $whmcs_auth
	) {
		/*
		// Let everyone know
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout(
			'onBeforeUpdateWHMCSSettings', 
			array(
				'whmcs_url' => $whmcs_url, 
				'whmcs_api' => $whmcs_api, 
				'whmcs_auth' => $whmcs_auth
			)
		);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		*/

        $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');

        $whmcs_url	= $xss->parse($whmcs_url);
        $whmcs_api	= $xss->parse($whmcs_api);
        $whmcs_auth = $xss->parse($whmcs_auth);

		$res1 = $GLOBALS['app']->Registry->Set('/gadgets/WHMCS/whmcs_url', $whmcs_url);
        $res2 = $GLOBALS['app']->Registry->Set('/gadgets/WHMCS/whmcs_api', $whmcs_api);
        $res3 = $GLOBALS['app']->Registry->Set('/gadgets/WHMCS/whmcs_auth', $whmcs_auth);
		
		if ($res1 === false) {
			//return new Jaws_Error(_t('WHMCS_SETTINGS_CANT_UPDATE'), _t('WHMCS_NAME'));
			return new Jaws_Error("There was a problem updating whmcs_url", _t('WHMCS_NAME'));
		}
		if ($res2 === false) {
			//return new Jaws_Error(_t('WHMCS_SETTINGS_CANT_UPDATE'), _t('WHMCS_NAME'));
			return new Jaws_Error("There was a problem updating whmcs_api", _t('WHMCS_NAME'));
		}
		if ($res3 === false) {
			//return new Jaws_Error(_t('WHMCS_SETTINGS_CANT_UPDATE'), _t('WHMCS_NAME'));
			return new Jaws_Error("There was a problem updating whmcs_auth", _t('WHMCS_NAME'));
		}
		$GLOBALS['app']->Registry->Commit('core');
		$GLOBALS['app']->Registry->Commit('WHMCS');
		return true;
    }
}
