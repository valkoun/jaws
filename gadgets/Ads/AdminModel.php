<?php
/**
 * Ads Gadget
 *
 * @category   GadgetModel
 * @package    Ads
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */

require_once JAWS_PATH . 'gadgets/Ads/Model.php';
class AdsAdminModel extends AdsModel
{
    var $_Name = 'Ads';
	
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

        // Events
        $GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
        $GLOBALS['app']->Shouter->NewShouter('Ads', 'onAddAdParent');   	// trigger an action when we add a parent
        $GLOBALS['app']->Shouter->NewShouter('Ads', 'onDeleteAdParent');	// trigger an action when we delete a parent
        $GLOBALS['app']->Shouter->NewShouter('Ads', 'onUpdateAdParent');	// and when we update a parent..
        $GLOBALS['app']->Shouter->NewShouter('Ads', 'onAddAd');   			// trigger an action when we add a ad
        $GLOBALS['app']->Shouter->NewShouter('Ads', 'onDeleteAd');			// trigger an action when we delete a ad
        $GLOBALS['app']->Shouter->NewShouter('Ads', 'onUpdateAd');			// and when we update a ad..
        $GLOBALS['app']->Shouter->NewShouter('Ads', 'onAddAdBrand');   			
        $GLOBALS['app']->Shouter->NewShouter('Ads', 'onDeleteAdBrand');			
        $GLOBALS['app']->Shouter->NewShouter('Ads', 'onUpdateAdBrand');			

		$GLOBALS['app']->loadClass('Listener', 'Jaws_EventListener');
        $GLOBALS['app']->Listener->NewListener('Ads', 'onDeleteUser', 'RemoveUserAds');
        $GLOBALS['app']->Listener->NewListener('Ads', 'onUpdateUser', 'UpdateUserAds');
		$GLOBALS['app']->Listener->NewListener('Ads', 'onAfterEnablingGadget', 'InsertDefaultChecksums');

		if (!in_array('Ads', explode(',', $GLOBALS['app']->Registry->Get('/gadgets/plain_editor_items')))) {
			if ($GLOBALS['app']->Registry->Get('/gadgets/plain_editor_items') == '') {
				$GLOBALS['app']->Registry->Set('/gadgets/plain_editor_items', 'Ads');
			} else {
				$GLOBALS['app']->Registry->Set('/gadgets/plain_editor_items', $GLOBALS['app']->Registry->Get('/gadgets/plain_editor_items').',Ads');
			}
		}
		/*
		if (!in_array('Ads', explode(',', $GLOBALS['app']->Registry->Get('/gadgets/user_access_items')))) {
			if ($GLOBALS['app']->Registry->Get('/gadgets/user_access_items') == '') {
				$GLOBALS['app']->Registry->Set('/gadgets/user_access_items', 'Ads');
			} else {
				$GLOBALS['app']->Registry->Set('/gadgets/user_access_items', $GLOBALS['app']->Registry->Get('/gadgets/user_access_items').',Ads');
			}
		}
		*/
		
		//Create Jaws_User groups
        require_once JAWS_PATH . 'include/Jaws/User.php';
        $userModel = new Jaws_User;
        $userModel->addGroup('ads_owners', false); //Don't check if it returns true or false
        $group = $userModel->GetGroupInfoByName('ads_owners');
		if (isset($group['id']) && !empty($group['id'])) {
			$GLOBALS['app']->ACL->NewKey('/ACL/groups/'.$group['id'].'/gadgets/Ads/OwnAds', 'true');
        }
        $userModel->addGroup('ads_users', false); //Don't check if it returns true or false

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
        $tables = array('ads',
                        'adparent',
                        'adbrand',
                        'ads_clicks',
						'ads_impressions');
        foreach ($tables as $table) {
            $result = $GLOBALS['db']->dropTable($table);
            if (Jaws_Error::IsError($result)) {
                $gName  = _t('ADS_NAME');
                $errMsg = _t('GLOBAL_ERROR_GADGET_NOT_UNINSTALLED', $gName);
                $GLOBALS['app']->Session->PushLastResponse($errMsg, RESPONSE_ERROR);
                return new Jaws_Error($errMsg, $gName);
            }
        }

        // Events
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
        $GLOBALS['app']->Shouter->DeleteShouter('Ads', 'onAddAdParent');   		// trigger an action when we add a parent
        $GLOBALS['app']->Shouter->DeleteShouter('Ads', 'onDeleteAdParent');		// trigger an action when we delete a parent
        $GLOBALS['app']->Shouter->DeleteShouter('Ads', 'onUpdateAdParent');		// and when we update a parent..
        $GLOBALS['app']->Shouter->DeleteShouter('Ads', 'onAddAd');   			// trigger an action when we add a ad
        $GLOBALS['app']->Shouter->DeleteShouter('Ads', 'onDeleteAd');			// trigger an action when we delete a ad
        $GLOBALS['app']->Shouter->DeleteShouter('Ads', 'onUpdateAd');			// and when we update a ad..
        $GLOBALS['app']->Shouter->DeleteShouter('Ads', 'onAddAdBrand');   			
        $GLOBALS['app']->Shouter->DeleteShouter('Ads', 'onDeleteAdBrand');			
        $GLOBALS['app']->Shouter->DeleteShouter('Ads', 'onUpdateAdBrand');			

		$GLOBALS['app']->loadClass('Listener', 'Jaws_EventListener');
        $GLOBALS['app']->Listener->DeleteListener('Ads', 'RemoveUserAds');
        $GLOBALS['app']->Listener->DeleteListener('Ads', 'UpdateUserAds');
		$GLOBALS['app']->Listener->DeleteListener('Ads', 'InsertDefaultChecksums');

		if (in_array('Ads', explode(',', $GLOBALS['app']->Registry->Get('/gadgets/plain_editor_items')))) {
			if ($GLOBALS['app']->Registry->Get('/gadgets/plain_editor_items') == 'Ads') {
				$GLOBALS['app']->Registry->Set('/gadgets/plain_editor_items', '');
			} else {
				$GLOBALS['app']->Registry->Set('/gadgets/plain_editor_items', str_replace(',Ads', '', $GLOBALS['app']->Registry->Get('/gadgets/plain_editor_items')));
			}
		}
		/*
		if (in_array('Ads', explode(',', $GLOBALS['app']->Registry->Get('/gadgets/user_access_items')))) {
			if ($GLOBALS['app']->Registry->Get('/gadgets/user_access_items') == 'Ads') {
				$GLOBALS['app']->Registry->Set('/gadgets/user_access_items', '');
			} else {
				$GLOBALS['app']->Registry->Set('/gadgets/user_access_items', str_replace(',Ads', '', $GLOBALS['app']->Registry->Get('/gadgets/user_access_items')));
			}
		}
		*/

		//Delete Jaws_User groups
        require_once JAWS_PATH . 'include/Jaws/User.php';
        $userModel = new Jaws_User;
        $group = $userModel->GetGroupInfoByName('ads_owners');
		if (isset($group['id']) && !empty($group['id'])) {
			$userModel->DeleteGroup($group['id']);
			$GLOBALS['app']->ACL->DeleteKey('/ACL/groups/'.$group['id'].'/gadgets/Ads/OwnAds');
		}
		$group = $userModel->GetGroupInfoByName('ads_users');
		if (isset($group['id']) && !empty($group['id'])) {
			$userModel->DeleteGroup($group['id']);
		}
        
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
        if (version_compare($old, '0.1.1', '<')) {			
			$result = $this->installSchema('schema.xml', '', '0.1.0.xml');
			if (Jaws_Error::IsError($result)) {
				return $result;
			}
		}
		
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
     * Creates a new ad.
     *
     * @access  public
     * @param   string  $type			'728' or '125'
     * @param   string  $image			link to an image or swf file
     * @param   string  $url			link to an external URL
     * @param   string  $title			title of the ad
     * @param   string  $keyword		keywords to show this ad during
     * @param   string  $sitewide       (Y/N) If the ad should be displayed sitewide
     * @param   string  $OwnerID
     * @param   string  $active         (Y/N) If the ad is published or not
     * @return  bool    Success/failure
     */
    function AddAd($LinkID, $BrandID = null, $type, $image, $url, $title = '', $keyword = '', $sitewide = 'N', 
	$OwnerID, $active, $barcode_type = '', $barcode_data = '', $description = '', $checksum = '')
    {
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		if ($keyword != '') {
			if ($sitewide == 'Y') {
				$keyword = null;
			} else {
				if (strlen($keyword) > 254) {
					$keyword = strip_tags(substr($keyword, 0, 255));
				}
			}
		}
		
		$sql = "
            INSERT INTO [[ads]]
                ([type], [image], [url], [title], [keyword], [sitewide],
				[ownerid], [active], [created], [updated], [barcode_data], [barcode_type], 
				[description], [linkid], [brandid], [checksum])
            VALUES
                ({type}, {image}, {url}, {title}, {keyword}, {sitewide},
				{OwnerID}, {Active}, {now}, {now}, {barcode_data}, {barcode_type}, 
				{description}, {LinkID}, {BrandID}, {checksum})";
		
		$OwnerID = (!is_null($OwnerID)) ? (int)$OwnerID : 0;
		if (
			!empty($image) && 
			(($OwnerID > 0 && (strtolower(substr($image, -4)) == '.htm' || strtolower(substr($image, -5)) == '.html')) || 
			(strtolower(substr($image, -4)) != '.htm' && strtolower(substr($image, -5)) != '.html' && 
			strtolower(substr($image, -4)) != '.swf' && strtolower(substr($image, -4)) != '.jpg' && 
			strtolower(substr($image, -5)) != '.jpeg' && strtolower(substr($image, -4)) != '.gif' && 
			strtolower(substr($image, -4)) != '.png'))
		) {
			$GLOBALS['app']->Session->PushLastResponse(_t('ADS_ERROR_INVALID_IMAGE'), RESPONSE_ERROR);
			require_once JAWS_PATH . 'include/Jaws/Header.php';
			if (BASE_SCRIPT != 'index.php') {
				Jaws_Header::Location(BASE_SCRIPT . '?gadget=Ads&action=A_form&linkid='.$LinkID);
			} else {
				Jaws_Header::Location('index.php?gadget=Ads&action=account_A_form&linkid='.$LinkID);
			}
			//return new Jaws_Error(_t('ADS_ERROR_INVALID_URL'), _t('ADS_NAME'));
        }
		$BrandID = (!is_null($BrandID)) ? (int)$BrandID : 0;
		if (!empty($image)) {
			$image = $this->cleanImagePath($image);
			if (
				$OwnerID > 0 && (substr(strtolower(trim($image)), 0, 4) == 'http' || 
				substr(strtolower(trim($image)), 0, 2) == '//' || 
				substr(strtolower(trim($image)), 0, 2) == '\\\\')
			) {
				$image = '';
			}
		}
        $params               			= array();
        $params['type']         		= $xss->parse($type);
        $params['url'] 					= $xss->parse(strip_tags($url));
        $params['title'] 				= $xss->parse(strip_tags($title));
        $params['sitewide'] 			= $xss->parse($sitewide);
        $params['keyword']   			= $xss->parse(strip_tags($keyword));
        $params['image'] 				= $xss->parse(strip_tags($image));
        $params['Active'] 				= $xss->parse($active);
        $params['OwnerID'] 				= $OwnerID;
        $params['barcode_data'] 		= $xss->parse($barcode_data);
        $params['barcode_type'] 		= $xss->parse($barcode_type);
        $params['description'] 			= $xss->parse(strip_tags($description));
        $params['LinkID'] 				= (int)$LinkID;
        $params['BrandID'] 				= $BrandID;
        $params['checksum'] 			= $checksum;
        $params['now']        			= $GLOBALS['db']->Date();
		
		$result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('ADS_ERROR_AD_NOT_ADDED'), RESPONSE_ERROR);
            return $result;
        }
        $newid = $GLOBALS['db']->lastInsertID('ads', 'id');

		if (empty($checksum)) {
			// Update checksum
			$config_key = $GLOBALS['app']->Registry->Get('/config/key');		
			$params               	= array();
			$params['id'] 			= $newid;
			$params['checksum'] 	= $newid.':'.$config_key;
			
			$sql = '
				UPDATE [[ads]] SET
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
		$res = $GLOBALS['app']->Shouter->Shout('onAddAd', $newid);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		
        $GLOBALS['app']->Session->PushLastResponse(_t('ADS_AD_CREATED'), RESPONSE_NOTICE);
        return $newid;
    }

    /**
     * Updates an ad.
     *
     * @access  public
     * @param   int     $id             The ID of the gallery to update.
     * @param   string  $type			'728' or '125'
     * @param   string  $image			link to an image or swf file
     * @param   string  $url			link to an external URL
     * @param   string  $title			title of the ad
     * @param   string  $keyword		keywords to show this ad during
     * @param   string  $sitewide       (Y/N) If the ad should be displayed sitewide
     * @param   string  $active         (Y/N) If the ad is published or not
     * @return  boolean Success/failure
     */
    function UpdateAd($id, $LinkID, $BrandID, $type, $image, $url, $title, $keyword, $sitewide, $active, $barcode_type, $barcode_data, $description)
	{
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		if ($keyword != '') {
			if ($sitewide == 'Y') {
				$keyword = null;
			} else {
				if (strlen($keyword) > 254) {
					$keyword = strip_tags(substr($keyword, 0, 255));
				}
			}
		}
        
		$model = $GLOBALS['app']->LoadGadget('Ads', 'Model');
        $page = $model->GetAd($id);
        if (Jaws_Error::isError($page)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('ADS_ERROR_AD_NOT_FOUND'), RESPONSE_ERROR);
            return $page;
        }

		if (
			!empty($image) && 
			(($page['ownerid'] > 0 && (strtolower(substr($image, -4)) == '.htm' || strtolower(substr($image, -5)) == '.html')) || 
			(strtolower(substr($image, -4)) != '.htm' && strtolower(substr($image, -5)) != '.html' && 
			strtolower(substr($image, -4)) != '.swf' && strtolower(substr($image, -4)) != '.jpg' && 
			strtolower(substr($image, -5)) != '.jpeg' && strtolower(substr($image, -4)) != '.gif' && 
			strtolower(substr($image, -4)) != '.png'))
		) {
			$GLOBALS['app']->Session->PushLastResponse(_t('ADS_ERROR_INVALID_IMAGE'), RESPONSE_ERROR);
			require_once JAWS_PATH . 'include/Jaws/Header.php';
			if (BASE_SCRIPT != 'index.php') {
				Jaws_Header::Location(BASE_SCRIPT . '?gadget=Ads&action=A_form&id='.$id.'&linkid='.$LinkID);
			} else {
				Jaws_Header::Location('index.php?gadget=Ads&action=account_A_form&id='.$id.'&linkid='.$LinkID);
			}
			//return new Jaws_Error(_t('ADS_ERROR_INVALID_URL'), _t('ADS_NAME'));
        }

        $sql = '
            UPDATE [[ads]] SET
				[type] = {type}, 
				[image] = {image}, 
				[url] = {url}, 
				[title] = {title}, 
				[keyword] = {keyword}, 
				[sitewide] = {sitewide}, 
				[active] = {Active}, 
				[updated] = {now}, 
				[barcode_data] = {barcode_data}, 
				[barcode_type] = {barcode_type}, 
				[description] = {description}, 
				[linkid] = {LinkID}, 
				[brandid] = {BrandID}
				WHERE [id] = {id}';

		if (!empty($image)) {
			$image = $this->cleanImagePath($image);
			if (
				$page['ownerid'] > 0 && (substr(strtolower(trim($image)), 0, 4) == 'http' || 
				substr(strtolower(trim($image)), 0, 2) == '//' || 
				substr(strtolower(trim($image)), 0, 2) == '\\\\')
			) {
				$image = '';
			}
		}
		$BrandID = (!is_null($BrandID)) ? (int)$BrandID : 0;
        $params               			= array();
        $params['id']         			= (int)$id;
        $params['type']         		= $xss->parse($type);
        $params['url'] 					= $xss->parse(strip_tags($url));
        $params['title'] 				= $xss->parse(strip_tags($title));
        $params['sitewide'] 			= $xss->parse($sitewide);
        $params['keyword']   			= $xss->parse(strip_tags($keyword));
        $params['image'] 				= $xss->parse(strip_tags($image));
        $params['Active'] 				= $xss->parse($active);
        $params['barcode_data'] 		= $xss->parse($barcode_data);
        $params['barcode_type'] 		= $xss->parse($barcode_type);
        $params['description'] 			= $xss->parse(strip_tags($description));
        $params['LinkID'] 				= (int)$LinkID;
        $params['BrandID'] 				= $BrandID;
        $params['now']        			= $GLOBALS['db']->Date();		

        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('ADS_ERROR_AD_NOT_UPDATED'), RESPONSE_ERROR);
			//$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
            return $result;
        }
		
		// Let everyone know it has been added
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onUpdateAd', $id);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		
		$GLOBALS['app']->Session->PushLastResponse(_t('ADS_AD_UPDATED'), RESPONSE_NOTICE);
        return true;
    }

	/**
     * Delete an ad
     *
     * @access  public
     * @return  boolean True if query was successful and Jaws_Error on error
     */
    function DeleteAd($id, $massive = false)
    {
		$model = $GLOBALS['app']->LoadGadget('Ads', 'Model');
		$parent = $model->GetAd((int)$id);
		if (Jaws_Error::IsError($parent)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('ADS_ERROR_AD_NOT_DELETED'), RESPONSE_ERROR);
			return new Jaws_Error(_t('ADS_ERROR_AD_NOT_DELETED'), _t('ADS_NAME'));
		}

		// Let everyone know it has been added
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onDeleteAd', $id);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		
		if(!isset($parent['id'])) {
			$GLOBALS['app']->Session->PushLastResponse(_t('ADS_ERROR_AD_NOT_DELETED'), RESPONSE_ERROR);
			return new Jaws_Error(_t('ADS_ERROR_AD_NOT_DELETED'), _t('ADS_NAME'));
		} else {
			$sql = 'DELETE FROM [[ads]] WHERE [id] = {id}';
			$res = $GLOBALS['db']->query($sql, array('id' => $id));
			if (Jaws_Error::IsError($res)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('ADS_ERROR_AD_NOT_DELETED'), RESPONSE_ERROR);
				return new Jaws_Error(_t('ADS_ERROR_AD_NOT_DELETED'), _t('ADS_NAME'));
			}
		}

		if ($massive === false) {
			$GLOBALS['app']->Session->PushLastResponse(_t('ADS_AD_DELETED'), RESPONSE_NOTICE);
        }
		return true;
    }

    /**
     * Deletes a group of ads
     *
     * @access  public
     * @param   array   $pages  Array with the ids of pages
     * @return  bool    Success/failure
     */
    function MassiveDelete($pages)
    {
        if (!is_array($pages)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('ADS_ERROR_AD_NOT_MASSIVE_DELETED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('ADS_ERROR_AD_NOT_MASSIVE_DELETED'), _t('ADS_NAME'));
        }

        foreach ($pages as $page) {
            $res = $this->DeleteAd($page);
            if (Jaws_Error::IsError($res)) {
                $GLOBALS['app']->Session->PushLastResponse(_t('ADS_ERROR_AD_NOT_MASSIVE_DELETED'), RESPONSE_ERROR);
                return new Jaws_Error(_t('ADS_ERROR_AD_NOT_MASSIVE_DELETED'), _t('ADS_NAME'));
            }
        }
        $GLOBALS['app']->Session->PushLastResponse(_t('ADS_AD_MASSIVE_DELETED'), RESPONSE_NOTICE);
        return true;
    }

    /**
     * Creates a new ad.
     *
     * @access  public
     * @param   string  $ad_id			ID of ad that was saved
     * @param   string  $status			status of the saved ad
     * @param   string  $OwnerID
     * @param   string  $description    description of saved ad
     * @return  bool    Success/failure
     */
    function AddSavedAd($ad_id, $status, $OwnerID, $description = '')
    {
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');		
		if (!is_numeric($ad_id) || (int)$ad_id == 0) {	
			$GLOBALS['app']->Session->PushLastResponse(_t('ADS_ERROR_AD_NOT_ADDED'), RESPONSE_ERROR);
			return new Jaws_Error(_t('ADS_ERROR_AD_NOT_ADDED'), _t('ADS_NAME'));
		}
		$sql = "
            INSERT INTO [[ads_subscribe]]
                ([ad_id], [status], [ownerid], [created], [updated], [description])
            VALUES
                ({ad_id}, {status}, {OwnerID}, {now}, {now}, {description})";
		
		$OwnerID = (!is_null($OwnerID)) ? (int)$OwnerID : 0;
        $params               			= array();
        $params['ad_id'] 				= (int)$ad_id;
        $params['status'] 				= $xss->parse($status);
        $params['OwnerID'] 				= $OwnerID;
        $params['description'] 			= $xss->parse(strip_tags($description));
        $params['now']        			= $GLOBALS['db']->Date();
		
		$result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('ADS_ERROR_AD_NOT_ADDED'), RESPONSE_ERROR);
            return $result;
        }
        $newid = $GLOBALS['db']->lastInsertID('ads_subscribe', 'id');

        $GLOBALS['app']->Session->PushLastResponse(_t('ADS_AD_CREATED'), RESPONSE_NOTICE);
        return $newid;
    }

    /**
     * Updates an ad.
     *
     * @access  public
     * @param   int     $id             The ID of the ad to update.
     * @param   string  $status         Status of the saved ad
     * @param   string  $description    description of the saved ad
     * @return  boolean Success/failure
     */
    function UpdateSavedAd($id, $status, $description)
	{
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        
		$model = $GLOBALS['app']->LoadGadget('Ads', 'Model');
        $page = $model->GetSavedAd($id);
        if (Jaws_Error::isError($page)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('ADS_ERROR_AD_NOT_FOUND'), RESPONSE_ERROR);
            return $page;
        }

        $sql = '
            UPDATE [[ads_subscribe]] SET
				[status] = {status}, 
				[updated] = {now}, 
				[description] = {description} 
				WHERE [id] = {id}';

        $params               			= array();
        $params['id']         			= (int)$id;
        $params['status'] 				= $xss->parse($status);
        $params['description'] 			= $xss->parse(strip_tags($description));
        $params['now']        			= $GLOBALS['db']->Date();		

        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('ADS_ERROR_AD_NOT_UPDATED'), RESPONSE_ERROR);
			//$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
            return $result;
        }
		
		$GLOBALS['app']->Session->PushLastResponse(_t('ADS_AD_UPDATED'), RESPONSE_NOTICE);
        return true;
    }

	/**
     * Delete an ad
     *
     * @access  public
     * @return  boolean True if query was successful and Jaws_Error on error
     */
    function DeleteSavedAd($cid)
    {
		$model = $GLOBALS['app']->LoadGadget('Ads', 'Model');
		$parent = $model->GetSavedAd((int)$cid);
		if (Jaws_Error::IsError($parent)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('ADS_ERROR_AD_NOT_DELETED'), RESPONSE_ERROR);
			return new Jaws_Error(_t('ADS_ERROR_AD_NOT_DELETED'), _t('ADS_NAME'));
		}

		if(!isset($parent['id'])) {
			$GLOBALS['app']->Session->PushLastResponse(_t('ADS_ERROR_AD_NOT_DELETED'), RESPONSE_ERROR);
			return new Jaws_Error(_t('ADS_ERROR_AD_NOT_DELETED'), _t('ADS_NAME'));
		} else {
			$sql = 'DELETE FROM [[ads_subscribe]] WHERE [id] = {cid}';
			$res = $GLOBALS['db']->query($sql, array('cid' => (int)$cid));
			if (Jaws_Error::IsError($res)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('ADS_ERROR_AD_NOT_DELETED'), RESPONSE_ERROR);
				return new Jaws_Error(_t('ADS_ERROR_AD_NOT_DELETED'), _t('ADS_NAME'));
			}
		}

		$GLOBALS['app']->Session->PushLastResponse(_t('ADS_AD_DELETED'), RESPONSE_NOTICE);
        return true;
    }

    /**
     * Deletes a group of ads
     *
     * @access  public
     * @param   array   $pages  Array with the ids of pages
     * @return  bool    Success/failure
     */
    function MassiveDeleteSavedAds($pages)
    {
        if (!is_array($pages)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('ADS_ERROR_AD_NOT_MASSIVE_DELETED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('ADS_ERROR_AD_NOT_MASSIVE_DELETED'), _t('ADS_NAME'));
        }

        foreach ($pages as $page) {
            $res = $this->DeleteSavedAd($page);
            if (Jaws_Error::IsError($res)) {
                $GLOBALS['app']->Session->PushLastResponse(_t('ADS_ERROR_AD_NOT_MASSIVE_DELETED'), RESPONSE_ERROR);
                return new Jaws_Error(_t('ADS_ERROR_AD_NOT_MASSIVE_DELETED'), _t('ADS_NAME'));
            }
        }
        $GLOBALS['app']->Session->PushLastResponse(_t('ADS_AD_MASSIVE_DELETED'), RESPONSE_NOTICE);
        return true;
    }

    /**
     * Create ad brands.
     *
     * @param   string  $title      		The title of the post.
     * @param   string  $description    	The contents of the post.
     * @param   string  $image   		An image to accompany the post or the gadget "layout action"
     * @param   int  	 $image_width     image width in pixels
     * @param   int  	 $image_height    image height in pixels
     * @param   integer 	$layout  		The layout mode of the post
     * @param   string 	$Active  		(Y/N) If the post is published or not
     * @param   integer 	$OwnerID  		The poster's user ID
     * @param   boolean 	$auto       		If it's auto saved or not
     * @access  public
     * @return  mixed 	ID of new brand or Jaws_Error on failure
     */
    function AddBrand($title, $description = '', $image = '', $image_width = '', $image_height = '', 
		$layout = 0, $active = 'Y', $OwnerID = null, $url_type = 'imageviewer', $internal_url = '', 
		$external_url = '', $url_target = '_self', $image_code = '', $checksum = '', $auto = false)
    {        
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$OwnerID = (!is_null($OwnerID)) ? (int)$OwnerID : 0;
		
		if ($image_code != '' && !empty($image)) {
			$image = '';
			$image_width = 0;
			$image_height = 0;
			$url_type = 'imageviewer';
		}
		
		if (
			$OwnerID == 0 && 
			$url_type == 'external' && 
			substr(strtolower(trim($external_url)), 0, 4) == 'http' && 
			strpos(strtolower(trim(urldecode($external_url))), 'javascript:') === false
		) {
			$url = $xss->parse($external_url);
		} else if ($url_type == 'internal' && !empty($internal_url) && strpos(strtolower(trim(urldecode($internal_url))), 'javascript:') === false) {
			$url = $xss->parse($internal_url);
		} else if ($url_type == 'imageviewer') {
			$url = "javascript:void(0);";
		} else {
	        $GLOBALS['app']->Session->PushLastResponse(_t('ADS_ERROR_INVALID_URL'), RESPONSE_ERROR);
            return new Jaws_Error(_t('ADS_ERROR_INVALID_URL'), _t('ADS_NAME'));
		}
		
		if (!empty($image)) {
			$image = $this->cleanImagePath($image);
			if (
				$OwnerID > 0 && 
				(substr(strtolower(trim($image)), 0, 4) == 'http' || 
				substr(strtolower(trim($image)), 0, 2) == '//' || 
				substr(strtolower(trim($image)), 0, 2) == '\\\\') || 
				strpos(strtolower(trim(urldecode($image))), 'javascript:') === true
			) {
				$image = '';
			}
		}

		$sql = "
			INSERT INTO [[adbrand]]
				([title], 
				[description], [image], [image_width], [image_height], 
				[layout], [active], [ownerid], [created], [updated],
				[url], [url_target], [image_code], [checksum])
			VALUES
				({title}, 
				{description}, {image}, {image_width}, {image_height},
				{layout}, {Active}, {OwnerID}, {now}, {now},
				{url}, {url_target}, {image_code}, {checksum})";

		  
		$description = strip_tags($description, '<p><a><b><em><i><strong><div><table><tbody><thead><tr><td><span><font><hr><br><marquee><ul><li>');
		$image_code = ($OwnerID > 0 ? htmlspecialchars($image_code) : '');
		$url = !empty($url) ? $url : '';
		$url_target = !empty($url_target) ? $url_target : '';
  
		$params               		= array();
		$params['title'] 			= $xss->parse(strip_tags($title));
		$params['description']   	= str_replace("\r\n", "\n", $description);
		$params['image'] 			= $xss->parse($image);
		$params['image_width'] 		= (int)$image_width;
		$params['image_height'] 	= (int)$image_height;
		$params['layout'] 			= (int)$layout;
		$params['OwnerID']         	= $OwnerID;
		$params['Active'] 			= $xss->parse($active);
		$params['url']				= $url;
		$params['url_target']		= $xss->parse($url_target);
		$params['image_code']		= $image_code;
		$params['checksum']			= $checksum;
		$params['now']        		= $GLOBALS['db']->Date();

		$result = $GLOBALS['db']->query($sql, $params);
		if (Jaws_Error::IsError($result)) {
			return new Jaws_Error(_t('ADS_ERROR_BRAND_NOT_ADDED'), _t('ADS_NAME'));
		}
		$newid = $GLOBALS['db']->lastInsertID('adbrand', 'id');

		if (empty($checksum)) {
			// Update checksum
			$config_key = $GLOBALS['app']->Registry->Get('/config/key');		
			$params               	= array();
			$params['id'] 			= $newid;
			$params['checksum'] 	= $newid.':'.$config_key;
			
			$sql = '
				UPDATE [[adbrand]] SET
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
		$res = $GLOBALS['app']->Shouter->Shout('onAddAdBrand', $newid);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		
		$GLOBALS['app']->Session->PushLastResponse(_t('ADS_BRAND_CREATED'), RESPONSE_NOTICE);
		return $newid;
    }

    /**
     * Updates a brand.
     *
     * @param   int     $id             The ID of the post to update.
     * @param   integer  $sort_order 	The chronological order
     * @param   string  $title      		The title of the post.
     * @param   string  $description    	The contents of the post.
     * @param   string  $image   		An image to accompany the post or the gadget "layout action"
     * @param   int  	 $image_width     image width in pixels
     * @param   int  	 $image_height    image height in pixels
     * @param   integer $layout  		The layout mode of the post
     * @param   string $Active  		(Y/N) If the post is published or not
     * @param   integer $OwnerID  		The poster's user ID
     * @param   boolean $auto       		If it's auto saved or not
     * @access  public
     * @return  boolean Success/failure
     */
    function UpdateBrand(
		$id, $title, $description, $image, $image_width, $image_height, 
		$layout, $active, $url_type = 'imageviewer', $internal_url, $external_url, 
		$url_target = '_self', $image_code, $auto = false
	) 
	{
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$model = $GLOBALS['app']->LoadGadget('Ads', 'Model');
        $page = $model->GetBrand($id);
        if (Jaws_Error::isError($page)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('ADS_ERROR_BRAND_NOT_FOUND'), RESPONSE_ERROR);
            return new Jaws_Error(_t('ADS_ERROR_BRAND_NOT_FOUND'), _t('ADS_NAME'));
        }
		
		if ($image_code != '' && !empty($image)) {
			$image = '';
			$image_width = 0;
			$image_height = 0;
			$url_type = 'imageviewer';
		}
		
		if (
			$page['ownerid'] == 0 && 
			$url_type == 'external' && 
			substr(strtolower(trim($external_url)), 0, 4) == 'http' && 
			strpos(strtolower(trim(urldecode($external_url))), 'javascript:') === false
		) {
			$url = $xss->parse($external_url);
		} else if (
			$url_type == 'internal' && 
			!empty($internal_url) && 
			strpos(strtolower(trim(urldecode($internal_url))), 'javascript:') === false
		) {
			$url = $xss->parse($internal_url);
		} else if ($url_type == 'imageviewer') {
			$url = "javascript:void(0);";
		} else {
	        $GLOBALS['app']->Session->PushLastResponse(_t('ADS_ERROR_INVALID_URL'), RESPONSE_ERROR);
            return new Jaws_Error(_t('ADS_ERROR_INVALID_URL'), _t('ADS_NAME'));
		}
		
		if (!empty($image)) {
			$image = $this->cleanImagePath($image);
			if (
				$page['ownerid'] > 0 && 
				(substr(strtolower(trim($image)), 0, 4) == 'http' || 
				substr(strtolower(trim($image)), 0, 2) == '//' || 
				substr(strtolower(trim($image)), 0, 2) == '\\\\') || 
				strpos(strtolower(trim(urldecode($image))), 'javascript:') === true
			) {
				$image = '';
			}
		}

		$sql = '
            UPDATE [[adbrand]] SET
				[title] = {title}, 
				[description] = {description}, 
				[image] = {image}, 
				[image_width] = {image_width},
				[image_height] = {image_height},
				[layout] = {layout}, 
				[active] = {Active}, 
				[updated] = {now},
				[url] = {url},
				[url_target] = {url_target}, 
				[image_code] = {image_code} 
			WHERE [id] = {id}';

		$image_code = ($page['ownerid'] > 0 ? htmlspecialchars($image_code) : '');
		$description = strip_tags($description, '<p><a><b><em><i><strong><div><table><tbody><thead><tr><td><span><font><hr><br><marquee><ul><li>');
		$url = !empty($url) ? $url : '';
		$url_target = !empty($url_target) ? $url_target : '';
       
        $params               	= array();
        $params['id']         	= (int)$id;
        $params['title'] 		= $xss->parse(strip_tags($title));
		$params['description']  = str_replace("\r\n", "\n", $description);
        $params['image'] 		= $xss->parse($image);
        $params['image_width'] 	= (int)$image_width;
        $params['image_height'] = (int)$image_height;
        $params['layout'] 		= (int)$layout;
        $params['Active'] 		= $xss->parse($active);
        $params['url']			= $url;
		$params['url_target']	= $xss->parse($url_target);
		$params['image_code']	= $image_code;
        $params['now']        	= $GLOBALS['db']->Date();

        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('ADS_ERROR_BRAND_NOT_UPDATED'), _t('ADS_NAME'));
        }

		// Let everyone know it has been added
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onUpdateAdBrand', $id);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		
        if ($auto) {
            $GLOBALS['app']->Session->PushLastResponse(_t('ADS_BRAND_AUTOUPDATED',
                                                     date('H:i:s'),
                                                     (int)$id,
                                                     date('D, d')),
                                                  RESPONSE_NOTICE);
        } else {
            $GLOBALS['app']->Session->PushLastResponse(_t('ADS_BRAND_UPDATED'), RESPONSE_NOTICE);
        }
        return true;
    }


    /**
     * Deletes a brand
     *
     * @param   int     $id     The ID of the brand to delete.
     * @param   boolean     $massive     Is this part of a massive delete?
     * @access  public
     * @return  boolean    Success/failure
     */
    function DeleteBrand($id, $massive = false)
    {
		// Let everyone know it has been added
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onDeleteAdBrand', $id);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		
        $sql = 'DELETE FROM [[adbrand]] WHERE [id] = {id}';
        $result = $GLOBALS['db']->query($sql, array('id' => $id));
        if (Jaws_Error::IsError($result)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('ADS_ERROR_BRAND_NOT_DELETED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('ADS_ERROR_BRAND_NOT_DELETED'), _t('ADS_NAME'));
        }

		if ($massive === false) {
			$GLOBALS['app']->Session->PushLastResponse(_t('ADS_BRAND_DELETED'), RESPONSE_NOTICE);
        }
		return true;
    }
	
    /**
	 * Re-sorts ads
     *
     * @param   int     $pids     ',' separated values of IDs of the posts
     * @param   string     $newsorts     ',' separated values of new sort_orders
     * @access  public
     * @return  bool    Success/failure
     * @TODO 	NOT Used, but could be for prioritizing 
     */
    function SortItem($pids, $newsorts)
    {
		$model = $GLOBALS['app']->LoadGadget('Ads', 'Model');
        $ids = explode(',', $pids);
        $sorts = explode(',', $newsorts);
        $i = 0;
		foreach ($ids as $pid) {
			if ((int)$pid != 0) {
				$new_sort = $sorts[$i];
				$params               	= array();
				$params['pid']         	= (int)$pid;
				$params['new_sort'] 	= (int)$new_sort;
				
				$sql = '
					UPDATE [[ads]] SET
						[sort_order] = {new_sort} 
					WHERE [id] = {pid}';

				$result1 = $GLOBALS['db']->query($sql, $params);
				if (Jaws_Error::IsError($result1)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('ADS_ERROR_AD_NOT_SORTED'), RESPONSE_ERROR);
					//$GLOBALS['app']->Session->PushLastResponse($result1->GetMessage(), RESPONSE_ERROR);
					return false;
				}
				$i++;
			}
		}
		$GLOBALS['app']->Session->PushLastResponse(_t('ADS_AD_UPDATED'), RESPONSE_NOTICE);
		return true;
    }

    /**
     * Search for ads that matches multiple queries
     * in the title or content
     *
     * @param   string  $status  Status of page(s) we want to display
     * @param   string  $search  Keyword (title/description) of pages we want to look for
     * @param   int     $offSet  Data limit
     * @access  public
     * @return  array   Array of matches
     */
    function MultipleSearchAds(
		$status = '720', $search, $brandid = '', $category = '', $location = '', $offSet = null, 
		$OwnerID = null, $pid = null, $sortColumn = 'created', $sortDir = 'ASC', $active = null
	)
    {
        $fields = array('type', 'keyword', 'url', 'ownerid', 'title', 'created', 'updated', 'Active', 'brandid');
        $sortColumn = strtolower($sortColumn);
        if (!in_array($sortColumn, $fields)) {
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_WARNING, _t('ADS_ERROR_UNKNOWN_COLUMN'));
            }
            $sortColumn = 'created';
        }
		
        $sortDir = strtoupper($sortDir);
        if ($sortDir == 'DESC') {
            $sortDir = 'DESC';
        } else {
            $sortDir = 'ASC';
        }
        
			$result = array();
			$model = $GLOBALS['app']->LoadGadget('Ads', 'Model');
			//$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		if (!is_null($pid)) {
			$properties = $model->GetAllAdsOfParent((int)$pid, $sortColumn, $sortDir, $active);
		} else if (trim($location) != '') {
			$properties = $model->GetAdsByLocation($location, 50, $status);
		} else if (trim($search) != '') {
			$properties = $model->GetAdsByKeyword($search, $status);
		} else {
			$properties = $model->GetAds(null, $sortColumn, $sortDir, false, $OwnerID, $active);
		}
			
		/*
		echo '<pre>';
		echo 'GetAds(null, '.$sortColumn.', '.$sortDir.', false, '.$OwnerID.', '.$active.')<br />';
		var_dump($properties);
		if (Jaws_Error::IsError($properties)) {
			echo $properties->GetMessage();
		}
		echo '</pre>';
		exit;
		*/

		if (!Jaws_Error::IsError($properties)) {
			foreach ($properties as $property) {
				$add_property = true;
				if (trim($brandid) != '') {
					if ($brandid != $property['brandid']) {
						$add_property = false;
					}
				}
				/*
				if (trim($category) != '') {
					if (strtolower($category) != strtolower($property['category'])) {
						$add_property = false;
					}
				}
				*/
				
				if (trim($search) != '') {
					$search_found = false;
					//$searchdata = explode(' ', $search);
					/**
					 * This query needs more work, not use $v straight, should be
					 * like rest of the param stuff.
					 */
					//foreach ($searchdata as $v) {
						$v = strtolower($search);
						if (strpos(strtolower($property['title']), $v) !== false || strpos(trim(strtolower($property['keyword'])), $v) !== false || 
						strpos(trim(strtolower($property['url'])), $v) !== false) {
							$search_found = true;
							//break;
						} else if (strlen($v) > 3) {
							if (strpos(trim(strtolower($property['description'])), $v) !== false) {

								$search_found = true;
								//break;
							}
						}

					  //}
					//}
					if ($search_found === false) {
						$add_property = false;
					}
				}
				if (!is_null($pid)) {
					if (!is_null($OwnerID)) {
						if ((int)$OwnerID != $property['ownerid']) {
							$add_property = false;
						}
					}
				}
				if ($add_property === true) {
					$result[] = $property;
				}
			}
		} else {
			return new Jaws_Error(_t('ADS_ERROR_ADS_NOT_RETRIEVED'), _t('ADS_NAME'));
		}
		if (count($result)) {
			// Sort result array
			$subkey = $sortColumn; 

			$temp_array = array();
			$temp_array[key($result)] = array_shift($result);
				
			foreach($result as $key => $val){
				$offset = 0;
				$found = false;
				foreach($temp_array as $tmp_key => $tmp_val)
				{
					if ($subkey == 'created') {
						$val[$subkey] = strtotime($val[$subkey]);
					}
					if(!$found and strtolower($val[$subkey]) > strtolower($tmp_val[$subkey]))
					{
						$temp_array = array_merge(    (array)array_slice($temp_array,0,$offset),
													array($key => $val),
													array_slice($temp_array,$offset)
												  );
						$found = true;
					}
					$offset++;
				}
				if(!$found) $temp_array = array_merge($temp_array, array($key => $val));
			}

			
			if ($sortDir != 'DESC' && $subkey != 'created') {
				$result = array_reverse($temp_array);
			} else {
				$result = $temp_array;
			}

			// Show sitewide ads first
			$subkey = 'sitewide'; 
			unset($temp_array);
			$temp_array = array();
			$temp_array[key($result)] = array_shift($result);

			foreach($result as $key => $val){
				$offset = 0;
				$found = false;
				foreach($temp_array as $tmp_key => $tmp_val)
				{
					if(!$found and strtolower($val[$subkey]) > strtolower($tmp_val[$subkey]))
					{
						$temp_array = array_merge(    (array)array_slice($temp_array,0,$offset),
													array($key => $val),
													array_slice($temp_array,$offset)
												  );
						$found = true;
					}
					$offset++;
				}
				if(!$found) $temp_array = array_merge($temp_array, array($key => $val));
			}
			$result = $temp_array;
		}
		return $result;
    }

    /**
     * Search for ads that match multiple queries
     * in the title or content and return array of given key
     *
     * @param   string  $status  Status of page(s) we want to display
     * @param   string  $search  Keyword (title/description) of pages we want to look for
     * @param   int     $offSet  Data limit
     * @access  public
     * @return  array   Array of matches
     */
    function SearchKeyWithAds(
		$search, $brandid = '', $category = '', $location = '', $type = '720', 
		$OwnerID = null, $pid = null, $only_titles = false, $sortColumn = 'title', 
		$sortDir = 'ASC', $return = 'title', $links = 'N'
	)
    {
        $return = strtolower($return);
        $fields = array('type', 'keyword', 'url', 'ownerid', 'title', 'created', 'updated', 'active', 'brandid');
        $sortColumn = strtolower($sortColumn);
        if (!in_array($sortColumn, $fields)) {
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_WARNING, _t('ADS_ERROR_UNKNOWN_COLUMN'));
            }
            $sortColumn = 'title';
        }
		
        $sortDir = strtoupper($sortDir);
        if ($sortDir == 'DESC') {
            $sortDir = 'DESC';
        } else {
            $sortDir = 'ASC';
        }
        
		$exact = array();
		$results = array();
		$result = array();
		$model = $GLOBALS['app']->LoadGadget('Ads', 'Model');
		//$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		if (!is_null($pid)) {
			$properties = $model->GetAllAdsOfParent((int)$pid, $sortColumn, $sortDir);
		} else {
			if ($return == 'keyword') {
				$properties = $model->GetAdsByKeyword($search, $type);
			} else if ($return == 'location') {
				$properties = $model->GetAdsByLocation($search, 50, $type);
			} else {
				$properties = $model->GetAds(null, $sortColumn, $sortDir, false, $OwnerID, 'Y', $return, $search);
			}
		}
		
		if (Jaws_Error::IsError($properties)) {
			return new Jaws_Error($properties->GetMessage(), _t('ADS_NAME'));
		}
		$keys_found = array();
		$stop_words = array(
			"&",
			"&amp;",
			"a",
			"able",
			"about",
			"above",
			"abroad",
			"according",
			"accordingly",
			"across",
			"actually",
			"adj",
			"after",
			"afterwards",
			"again",
			"against",
			"ago",
			"ahead",
			"ain't",
			"all",
			"allow",
			"allows",
			"almost",
			"alone",
			"along",
			"alongside",
			"already",
			"also",
			"although",
			"always",
			"am",
			"amid",
			"amidst",
			"among",
			"amongst",
			"an",
			"and",
			"another",
			"any",
			"anybody",
			"anyhow",
			"anyone",
			"anything",
			"anyway",
			"anyways",
			"anywhere",
			"apart",
			"appear",
			"appreciate",
			"appropriate",
			"are",
			"aren't",
			"around",
			"as",
			"a's",
			"aside",
			"ask",
			"asking",
			"associated",
			"at",
			"available",
			"away",
			"awfully",
			"b",
			"back",
			"backward",
			"backwards",
			"be",
			"became",
			"because",
			"become",
			"becomes",
			"becoming",
			"been",
			"before",
			"beforehand",
			"begin",
			"behind",
			"being",
			"believe",
			"below",
			"beside",
			"besides",
			"best",
			"better",
			"between",
			"beyond",
			"both",
			"brief",
			"but",
			"by",
			"c",
			"came",
			"can",
			"cannot",
			"cant",
			"can't",
			"caption",
			"cause",
			"causes",
			"certain",
			"certainly",
			"changes",
			"clearly",
			"c'mon",
			"co",
			"co.",
			"com",
			"come",
			"comes",
			"concerning",
			"consequently",
			"consider",
			"considering",
			"contain",
			"containing",
			"contains",
			"corresponding",
			"could",
			"couldn't",
			"course",
			"c's",
			"currently",
			"d",
			"dare",
			"daren't",
			"definitely",
			"described",
			"despite",
			"did",
			"didn't",
			"different",
			"directly",
			"do",
			"does",
			"doesn't",
			"doing",
			"done",
			"don't",
			"down",
			"downwards",
			"during",
			"e",
			"each",
			"edu",
			"eg",
			"eight",
			"eighty",
			"either",
			"else",
			"elsewhere",
			"end",
			"ending",
			"enough",
			"entirely",
			"especially",
			"et",
			"etc",
			"even",
			"ever",
			"evermore",
			"every",
			"everybody",
			"everyone",
			"everything",
			"everywhere",
			"ex",
			"exactly",
			"except",
			"f",
			"fairly",
			"far",
			"farther",
			"few",
			"fewer",
			"fifth",
			"first",
			"five",
			"followed",
			"following",
			"follows",
			"for",
			"forever",
			"former",
			"formerly",
			"forth",
			"forward",
			"found",
			"four",
			"from",
			"further",
			"furthermore",
			"g",
			"get",
			"gets",
			"getting",
			"given",
			"gives",
			"go",
			"goes",
			"going",
			"gone",
			"got",
			"gotten",
			"greetings",
			"h",
			"had",
			"hadn't",
			"half",
			"happens",
			"hardly",
			"has",
			"hasn't",
			"have",
			"haven't",
			"having",
			"he",
			"he'd",
			"he'll",
			"hello",
			"help",
			"hence",
			"her",
			"here",
			"hereafter",
			"hereby",
			"herein",
			"here's",
			"hereupon",
			"hers",
			"herself",
			"he's",
			"hi",
			"him",
			"himself",
			"his",
			"hither",
			"hopefully",
			"how",
			"howbeit",
			"however",
			"hundred",
			"i",
			"i'd",
			"ie",
			"if",
			"ignored",
			"i'll",
			"i'm",
			"immediate",
			"in",
			"inasmuch",
			"inc",
			"inc.",
			"indeed",
			"indicate",
			"indicated",
			"indicates",
			"info",
			"inner",
			"inside",
			"insofar",
			"instead",
			"into",
			"inward",
			"is",
			"isn't",
			"it",
			"it'd",
			"it'll",
			"its",
			"it's",
			"itself",
			"i've",
			"j",
			"just",
			"k",
			"keep",
			"keeps",
			"kept",
			"know",
			"known",
			"knows",
			"l",
			"last",
			"lately",
			"later",
			"latter",
			"latterly",
			"least",
			"less",
			"lest",
			"let",
			"let's",
			"like",
			"liked",
			"likely",
			"likewise",
			"little",
			"look",
			"looking",
			"looks",
			"low",
			"lower",
			"ltd",
			"m",
			"made",
			"mainly",
			"make",
			"makes",
			"many",
			"may",
			"maybe",
			"mayn't",
			"me",
			"mean",
			"meantime",
			"meanwhile",
			"merely",
			"might",
			"mightn't",
			"mine",
			"minus",
			"miss",
			"more",
			"moreover",
			"most",
			"mostly",
			"mr",
			"mrs",
			"much",
			"must",
			"mustn't",
			"my",
			"myself",
			"n",
			"name",
			"namely",
			"nd",
			"near",
			"nearly",
			"necessary",
			"need",
			"needn't",
			"needs",
			"neither",
			"never",
			"neverf",
			"neverless",
			"nevertheless",
			"new",
			"next",
			"nine",
			"ninety",
			"no",
			"nobody",
			"non",
			"none",
			"nonetheless",
			"noone",
			"no-one",
			"nor",
			"normally",
			"not",
			"nothing",
			"notwithstanding",
			"now",
			"nowhere",
			"o",
			"obviously",
			"of",
			"off",
			"often",
			"oh",
			"ok",
			"okay",
			"old",
			"on",
			"once",
			"one",
			"ones",
			"one's",
			"only",
			"onto",
			"opposite",
			"or",
			"other",
			"others",
			"otherwise",
			"ought",
			"oughtn't",
			"our",
			"ours",
			"ourselves",
			"out",
			"outside",
			"over",
			"overall",
			"own",
			"p",
			"particular",
			"particularly",
			"past",
			"per",
			"perhaps",
			"placed",
			"please",
			"plus",
			"possible",
			"presumably",
			"probably",
			"provided",
			"provides",
			"q",
			"que",
			"quite",
			"qv",
			"r",
			"rather",
			"rd",
			"re",
			"really",
			"reasonably",
			"recent",
			"recently",
			"regarding",
			"regardless",
			"regards",
			"relatively",
			"respectively",
			"right",
			"round",
			"s",
			"said",
			"same",
			"saw",
			"say",
			"saying",
			"says",
			"second",
			"secondly",
			"see",
			"seeing",
			"seem",
			"seemed",
			"seeming",
			"seems",
			"seen",
			"self",
			"selves",
			"sensible",
			"sent",
			"serious",
			"seriously",
			"seven",
			"several",
			"shall",
			"shan't",
			"she",
			"she'd",
			"she'll",
			"she's",
			"should",
			"shouldn't",
			"since",
			"six",
			"so",
			"some",
			"somebody",
			"someday",
			"somehow",
			"someone",
			"something",
			"sometime",
			"sometimes",
			"somewhat",
			"somewhere",
			"soon",
			"sorry",
			"specified",
			"specify",
			"specifying",
			"still",
			"sub",
			"such",
			"sup",
			"sure",
			"t",
			"taking",
			"tell",
			"tends",
			"th",
			"than",
			"thank",
			"thanks",
			"thanx",
			"that",
			"that'll",
			"thats",
			"that's",
			"that've",
			"the",
			"their",
			"theirs",
			"them",
			"themselves",
			"then",
			"thence",
			"there",
			"thereafter",
			"thereby",
			"there'd",
			"therefore",
			"therein",
			"there'll",
			"there're",
			"theres",
			"there's",
			"thereupon",
			"there've",
			"these",
			"they",
			"they'd",
			"they'll",
			"they're",
			"they've",
			"thing",
			"things",
			"think",
			"third",
			"thirty",
			"this",
			"thorough",
			"thoroughly",
			"those",
			"though",
			"three",
			"through",
			"throughout",
			"thru",
			"thus",
			"till",
			"to",
			"together",
			"too",
			"took",
			"toward",
			"towards",
			"tried",
			"tries",
			"truly",
			"try",
			"trying",
			"t's",
			"twice",
			"two",
			"u",
			"un",
			"under",
			"underneath",
			"undoing",
			"unfortunately",
			"unless",
			"unlike",
			"unlikely",
			"until",
			"unto",
			"up",
			"upon",
			"upwards",
			"us",
			"use",
			"used",
			"useful",
			"uses",
			"using",
			"usually",
			"v",
			"value",
			"various",
			"very",
			"via",
			"viz",
			"vs",
			"w",
			"want",
			"wants",
			"was",
			"wasn't",
			"way",
			"we",
			"we'd",
			"welcome",
			"well",
			"we'll",
			"went",
			"were",
			"we're",
			"weren't",
			"we've",
			"what",
			"whatever",
			"what'll",
			"what's",
			"what've",
			"when",
			"whence",
			"whenever",
			"where",
			"whereafter",
			"whereas",
			"whereby",
			"wherein",
			"where's",
			"whereupon",
			"wherever",
			"whether",
			"which",
			"whichever",
			"while",
			"whilst",
			"whither",
			"who",
			"who'd",
			"whoever",
			"whole",
			"who'll",
			"whom",
			"whomever",
			"who's",
			"whose",
			"why",
			"will",
			"willing",
			"wish",
			"with",
			"within",
			"without",
			"wonder",
			"won't",
			"would",
			"wouldn't",
			"x",
			"y",
			"yes",
			"yet",
			"you",
			"you'd",
			"you'll",
			"your",
			"you're",
			"yours",
			"yourself",
			"yourselves",
			"you've",
			"z",
		);
		foreach ($properties as $property) {
			//echo '<br />Search: '.$search.' status: '.$status.' bedroom: '.$bedroom.' bathroom: '.$bathroom.' cat: '.$category.' community: '.$community;
			//echo '<br />'.$property['title'];
			$in_title = false;
			$add_property = true;

			if (trim($brandid) != '') {
				if ((int)$brandid != $property['brandid']) {
					$add_property = false;
				}
			}		
			if (trim($category) != '') {
				if (strtolower($category) != strtolower($property['category'])) {
					$add_property = false;
				}
			}		
			if (trim($search) != '' && strpos(strtolower($property['title']), strtolower(trim($search))) !== false) {
				//$add_property = false;
				$searchdata = explode(' ', $property['title']);
				foreach ($searchdata as $v) {
				  if (!in_array(strtolower($v), $stop_words)) {
					$newstring = "";
					$array = str_split($v);
					foreach($array as $char) {
						if ((strtoupper($char) >= 'A' && strtoupper($char) <= 'Z')) {
							$newstring .= $char;
						} else {
							break;
						}
					}
					if (substr(strtolower($newstring), 0, strlen(strtolower($search))) == strtolower($search) && !in_array(strtolower($newstring), $keys_found)) {
						$keys_found[] = strtolower($newstring);
						if ($links == 'Y') {
							$exact[] = array('<a href="'.$GLOBALS['app']->getSiteURL().'/index.php?gadget=Ads&action=Category&id=all&keyword='.ucfirst(strtolower($newstring)).'">'.ucfirst(strtolower($newstring)).'</a>');
						} else {
							$exact[] = array(ucfirst(strtolower($newstring)));
						}
					}
				  } else {
					$add_property = false;
				  }
				}
			}

			/*
			if (trim($search) != '') {
				if ($return == 'attribute') {
					$amenity_found = false;
					$propertyAmenities = array();
					if (!empty($property['attribute'])) {
						$propAmenities = explode(',', $property['attribute']);
						foreach($propAmenities as $propAmenity) {		            
							$amenity = $model->GetAttribute((int)$propAmenity);
							if (!Jaws_Error::IsError($amenity)) {
								//$searchamenities = explode(',', $search);
								//foreach ($searchamenities as $a) {
									$a = trim(strtolower($search));
									if (strpos(trim(strtolower($amenity['feature'])), $a) !== false) {
										$propertyAmenities[] = $amenity['feature'];
										$amenity_found = true;
										//break;
									}
								//}
							}
							
							//if ($amenity_found === true) {
							//	break;
							//}
							
						}
					}
					if ($amenity_found === false) {
						$add_property = false;
					}
				} else if ($return == 'sales') {
					$sale_found = false;
					$propertySales = array();
					if (!empty($property['sales'])) {
						$propSales = explode(',', $property['sales']);
						foreach($propSales as $propSale) {		            
							$sale = $model->GetSale((int)$propSale);
							if (!Jaws_Error::IsError($sale)) {
								//$searchamenities = explode(',', $search);
								//foreach ($searchamenities as $a) {
									$a = trim(strtolower($search));
									if (strpos(trim(strtolower($sale['title'])), $a) !== false) {
										$propertySales[] = $sale['title'];
										$sale_found = true;
										//break;
									}
								//}
							}
							
							//if ($amenity_found === true) {
							//	break;
							//}
							
						}
					}
					if ($sale_found === false) {
						$add_property = false;
					}
				} else {
					$search_found = false;
					$searchdata = explode(' ', $search);
					foreach ($searchdata as $v) {
						$v = trim(strtolower($v));
						if (strpos(trim(strtolower($property[$return])), $v) !== false) {
							$search_found = true;
							//break;
						}
					}
					if ($search_found === false) {
						$add_property = false;
					}
				}
			}
			*/
			
			if (!is_null($pid)) {
				if (!is_null($OwnerID)) {
					if ((int)$OwnerID != $property['ownerid']) {
						$add_property = false;
					}
				}
			}
			if ($add_property === true) {
				// Make sure this key is only added once
				if (!in_array($property[$return], $keys_found) || count($keys_found) <= 0) {
					$results[] = array($property[$return]);
					$keys_found[] = $property[$return];
					//echo 'RETURN: '.$property[$return];
				}
			}
		}
		
		foreach($exact as $ex){
			$result[] = $ex;
		}
		foreach($results as $res){
			$result[] = $res;
		}
		///*
		if (count($result)) {
			// Sort result array
			$subkey = $sortColumn; 
			$temp_array = array();
			
			$temp_array[key($result)] = array_shift($result);

			foreach($result as $key => $val){
				$offset = 0;
				$found = false;
				foreach($temp_array as $tmp_key => $tmp_val)
				{
					if(!$found and strtolower($val[$subkey]) > strtolower($tmp_val[$subkey]))
					{
						$temp_array = array_merge(    (array)array_slice($temp_array,0,$offset),
													array($key => $val),
													array_slice($temp_array,$offset)
												  );
						$found = true;
					}
					$offset++;
				}
				if(!$found) $temp_array = array_merge($temp_array, array($key => $val));
			}

			$result = array_reverse($temp_array);
		}
		return $result;
    }

    /**
     * Search for ad parents that matches a status and/or a keyword
     * in the title or content
     *
     * @param   string  $status  Status of page(s) we want to display
     * @param   string  $search  Keyword (title/description) of pages we want to look for
     * @param   int     $offSet  Data limit
     * @access  public
     * @return  array   Array of matches
     */
    function SearchAdParents($status, $search, $offSet = null, $OwnerID = null, $pid = null)
    {
        $params = array();


        $sql = '
            SELECT [adparentid], [adparentparent], [adparentsort_order], [adparentcategory_name], 
				[adparentimage], [adparentdescription], [adparentactive], 
				[adparentownerid], [adparentcreated], [adparentupdated], 
				[adparentfeatured], [adparentfast_url], [adparentrss_url],
				[adparenturl],[adparenturl_target],[adparentimage_code]
            FROM [[adparent]]
			WHERE ([adparentcategory_name] <> ""';

		if (trim($status) != '') {
			$sql .= ' AND [adparentactive] = {status}';
			$params['status'] = $status;
		}
		if (!is_null($OwnerID)) {
			$sql .= ' AND [adparentownerid] = {OwnerID}';
			$params['OwnerID'] = $OwnerID;
		}
		if (!is_null($pid)) {
			$sql .= ' AND [adparentparent] = {pid}';
			$params['pid'] = $pid;
		}
        $sql .= ')';
		
		if (trim($search) != '') {
            $searchdata = explode(' ', $search);
            /**
             * This query needs more work, not use $v straight, should be
             * like rest of the param stuff.
             */
            $i = 0;
            foreach ($searchdata as $v) {
                $v = trim($v);
                $sql .= " AND ([adparentcategory_name] LIKE {textLike_".$i."} OR [adparentfast_url] LIKE {textLike_".$i."} OR [adparentdescription] LIKE {textLike_".$i."} OR [adparentrss_url] LIKE {textLike_".$i."})";
                $params['textLike_'.$i] = '%'.$v.'%';
                $i++;
            }
        }


        if (is_numeric($offSet)) {
            $limit = 10;
            $result = $GLOBALS['db']->setLimit(10, $offSet);
            if (Jaws_Error::IsError($result)) {
                return new Jaws_Error(_t('ADS_ERROR_ADPARENTS_NOT_RETRIEVED'), _t('ADS_NAME'));
            }
        }

        $sql.= ' ORDER BY [adparentid] ASC';

        $types = array(
			'integer', 'integer', 'integer', 'text', 'text', 'text', 
			'text', 'integer', 'timestamp', 'timestamp', 
			'text', 'text', 'text', 'text', 'text', 'text'
		);
	    
        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('ADS_ERROR_ADPARENTS_NOT_RETRIEVED'), _t('ADS_NAME'));
        }
        //limit, sort, sortDirection, offset..
		/*
		//XSS parsing
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$r = 0;
		foreach ($result as $res) {
			foreach ($res as $key => $val) {
				if (($key == 'adparentimage_code' || $key == 'adparentdescription') && $res['adparentownerid'] > 0) {
					$result[$r][$key] = $xss->parse($val);
				}	
				if ($key != 'adparentimage_code' && $key != 'adparentdescription') {
					// Types
					if (!empty($val) && is_string($val)) {
						$result[$r][$key] = $xss->parse($val);
					}
				}
			}
			$r++;
		}
		*/
        return $result;
    }

	/**
     * Search for ads that matches a status and/or a keyword
     * in the title or content
     *
     * @param   string  $status  Status of ad(s) we want to display
     * @param   string  $search  Keyword (title/description) of ads we want to look for
     * @param   int     $offSet  Data limit
     * @access  public
     * @return  array   Array of matches
     */
    function SearchAds($status, $search, $offSet = null, $OwnerID = 0, $pid = null)
    {
        $params = array();

        $sql = '
            SELECT [id], [type], [image], [url], [title], [keyword], [sitewide],
			[ownerid], [active], [created], [updated], [barcode_data], [barcode_type], 
			[description], [linkid]
            FROM [[ads]]
			WHERE (title <> ""';
	    
        if (trim($status) != '') {
			$params['status'] = $status;
            $sql .= ' AND [active] = {status}';
        }
        
		if (!is_null($pid) || trim($pid) != '') {
			$params['LinkID'] = (int)$pid;
            $sql .= ' AND [linkid] = {LinkID}';
        }
        
		$sql .= ')';
        
		$sql .= ' AND ([ownerid] = {OwnerID})';
		$params['OwnerID'] = (int)$OwnerID;

		if (trim($search) != '') {
            $searchdata = explode(' ', $search);
            /**
             * This query needs more work, not use $v straight, should be
             * like rest of the param stuff.
             */
            $i = 0;
            foreach ($searchdata as $v) {
                $v = trim($v);
                $sql .= " AND ([title] LIKE {textLike_".$i."} OR [keyword] LIKE {textLike_".$i."} OR [url] LIKE {textLike_".$i."} OR [type] LIKE {textLike_".$i."})";
                $params['textLike_'.$i] = '%'.$v.'%';
                $i++;
            }
        }

        $types = array(
			'integer', 'text', 'text', 'text', 'text', 'text', 'text', 
			'integer', 'text', 'timestamp', 'timestamp', 'text', 'text', 
			'text', 'integer'
		);

        if (is_numeric($offSet)) {
            $limit = 10;
            $result = $GLOBALS['db']->setLimit(10, $offSet);
            if (Jaws_Error::IsError($result)) {
                return new Jaws_Error(_t('ADS_ERROR_ADS_NOT_RETRIEVED'), _t('ADS_NAME'));
            }
        }

        $sql.= ' ORDER BY [id] ASC';
        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('ADS_ERROR_ADS_NOT_RETRIEVED'), _t('ADS_NAME'));
        }
        //limit, sort, sortDirection, offset..
        return $result;
    }
	
	/**
     * Search for ads that matches a status and/or a keyword
     * in the title or content
     *
     * @param   string  $status  Status of ad(s) we want to display
     * @param   string  $search  Keyword (title/description) of ads we want to look for
     * @param   int     $offSet  Data limit
     * @access  public
     * @return  array   Array of matches
     */
    function SearchSavedAds($status, $search, $offSet = null, $OwnerID = 0)
    {
        $params = array();

        $sql = '
            SELECT [id], [ad_id], [status], [ownerid], [created], [updated], [description]
            FROM [[ads_subscribe]]
			WHERE (ad_id > 0';
	    
        if (trim($status) != '') {
			$params['status'] = $status;
            $sql .= ' AND [status] = {status}';
        }
        
		$sql .= ')';
        
		$sql .= ' AND ([ownerid] = {OwnerID})';
		$params['OwnerID'] = (int)$OwnerID;

		if (trim($search) != '') {
            $searchdata = explode(' ', $search);
            /**
             * This query needs more work, not use $v straight, should be
             * like rest of the param stuff.
             */
            $i = 0;
            foreach ($searchdata as $v) {
                $v = trim($v);
                $sql .= " AND ([description] LIKE {textLike_".$i."})";
                $params['textLike_'.$i] = '%'.$v.'%';
                $i++;
            }
        }

        $types = array(
			'integer', 'integer', 'text', 'integer', 'timestamp', 'timestamp', 'text'
		);

        if (is_numeric($offSet)) {
            $limit = 10;
            $result = $GLOBALS['db']->setLimit(10, $offSet);
            if (Jaws_Error::IsError($result)) {
                return new Jaws_Error(_t('ADS_ERROR_ADS_NOT_RETRIEVED'), _t('ADS_NAME'));
            }
        }

        $sql.= ' ORDER BY [id] ASC';

        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('ADS_ERROR_ADS_NOT_RETRIEVED'), _t('ADS_NAME'));
        }
        //limit, sort, sortDirection, offset..
        return $result;
    }
	
	/**
     * Search for brands that matches a status and/or a keyword
     * in the title or content
     *
     * @param   string  $status  Status of page(s) we want to display
     * @param   string  $search  Keyword (title/description) of pages we want to look for
     * @param   int     $offSet  Data limit
     * @access  public
     * @return  array   Array of matches
     */
    function SearchBrands($status, $search, $offSet = null, $OwnerID = null)
    {
        $params = array();

        $sql = '
            SELECT [id], [title], 
				[description], [image], [image_width], [image_height], 
				[layout], [active], [ownerid], [created], [updated],
				[url], [url_target], [image_code]
            FROM [[adbrand]]
			WHERE ([title] <> ""';

        if (trim($status) != '') {
            $sql .= ' AND [active] = {status}';
			$params['status'] = $status;
        }
        $sql .= ')';
        
		if (!is_null($OwnerID)) {
			$sql .= ' AND [ownerid] = {OwnerID}';
			$params['OwnerID'] = $OwnerID;
		}
		
		if (trim($search) != '') {
            $searchdata = explode(' ', $search);
            /**
             * This query needs more work, not use $v straight, should be
             * like rest of the param stuff.
             */
            $i = 0;
            foreach ($searchdata as $v) {
                $v = trim($v);
                $sql .= " AND ([title] LIKE {textLike_".$i."} OR [description] LIKE {textLike_".$i."})";
                $params['textLike_'.$i] = '%'.$v.'%';
                $i++;
            }
        }


        if (is_numeric($offSet)) {
            $limit = 10;
            $result = $GLOBALS['db']->setLimit(10, $offSet);
            if (Jaws_Error::IsError($result)) {
                return new Jaws_Error(_t('ADS_ERROR_BRANDS_NOT_RETRIEVED'), _t('ADS_NAME'));
            }
        }

        $sql.= ' ORDER BY [id] ASC';

        $types = array(
			'integer', 'text', 
			'text', 'text', 'integer', 'integer', 'integer', 'text', 
			'integer', 'timestamp', 'timestamp', 'text', 'text', 'text'
		);
	    
        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('ADS_ERROR_BRANDS_NOT_RETRIEVED'), _t('ADS_NAME'));
        }
        //limit, sort, sortDirection, offset..
		/*
		//XSS parsing
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$r = 0;
		foreach ($result as $res) {
			foreach ($res as $key => $val) {
				if (($key == 'image_code' || $key == 'description') && $res['ownerid'] > 0) {
					$result[$r][$key] = $xss->parse($val);
				}	
				if ($key != 'image_code' && $key != 'description') {
					// Types
					if (!empty($val) && is_string($val)) {
						$result[$r][$key] = $xss->parse($val);
					}
				}
			}
			$r++;
		}
		*/
        return $result;
    }

    /**
	 * Adds click record into db table
     *
     * @param   int     $id ID of the ad
     * @access  public
     * @return  bool    Success/failure
     */
    function AddClick($id)
    {
		if (empty($id) || !isset($id)) {
			//$GLOBALS['app']->Session->PushLastResponse(_t('ADS_ERROR_ADCLICK_NOT_ADDED'), RESPONSE_ERROR);
			return false;
		}
		
		$sql = "
			INSERT INTO [[ads_clicks]]
				([ad_id], [from], [created])
			VALUES
				({ad_id}, {from}, {now})";
		
		$params         	= array();
		$params['ad_id']	= $id;
		$params['from']		= $GLOBALS['app']->GetFullURL();
		$params['now']		= $GLOBALS['db']->Date();
		
		$result = $GLOBALS['db']->query($sql, $params);
		if (Jaws_Error::IsError($result)) {
			//$GLOBALS['app']->Session->PushLastResponse(_t('ADS_ERROR_ADCLICK_NOT_ADDED'), RESPONSE_ERROR);
			return $result;
		}

		//$GLOBALS['app']->Session->PushLastResponse(_t('ADS_ADCLICK_ADDED'), RESPONSE_NOTICE);
		return true;
	}
	
    /**
     * Updates a User's Ads
     *
     * @access  public
     * @param   int  $uid  User ID
     * @return  array   Response
     */
    function UpdateUserAds($uid) 
    {
		require_once JAWS_PATH . 'include/Jaws/User.php';
		$jUser = new Jaws_User;
		$info = $jUser->GetUserInfoById((int)$uid, true);
		if (!Jaws_Error::IsError($info)) {
			$params           	= array();
			$params['id']     	= $info['id'];
			if (!$info['enabled']) {
				$params['Active'] = 'N';
				$params['was'] = 'Y';
			} else {
				$params['Active'] = 'Y';
				$params['was'] = 'N';
			}
			$sql = '
				UPDATE [[ads]] SET
					[active] = {Active}
				WHERE ([ownerid] = {id}) AND ([active] = {was})';

			$result = $GLOBALS['db']->query($sql, $params);
			if (Jaws_Error::IsError($result)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('ADS_ERROR_USER_ADS_NOT_UPDATED'), RESPONSE_ERROR);
				return false;
			}
			$sql2 = '
				UPDATE [[adparent]] SET
					[adparentactive] = {Active}
				WHERE ([adparentownerid] = {id}) AND ([adparentactive] = {was})';

			$result2 = $GLOBALS['db']->query($sql2, $params);
			if (Jaws_Error::IsError($result2)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('ADS_ERROR_USER_ADPARENTS_NOT_UPDATED'), RESPONSE_ERROR);
				return false;
			}
			$sql3 = '
				UPDATE [[adbrand]] SET
					[active] = {Active}
				WHERE ([ownerid] = {id}) AND ([active] = {was})';

			$result3 = $GLOBALS['db']->query($sql3, $params);
			if (Jaws_Error::IsError($result3)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('ADS_ERROR_USER_ADBRANDS_NOT_UPDATED'), RESPONSE_ERROR);
				return false;
			}
			$GLOBALS['app']->Session->PushLastResponse(_t('ADS_USER_ADS_UPDATED'), RESPONSE_NOTICE);
			return true;
		} else {
			$GLOBALS['app']->Session->PushLastResponse(_t('ADS_ERROR_USER_ADS_NOT_UPDATED'), RESPONSE_ERROR);
			return false;
		}
    }	
		
    /**
     * Deletes a User's Ads
     *
     * @access  public
     * @param   int  $uid  User ID
     * @return  array   Response
     */
    function RemoveUserAds($uid) 
    {
		$model = $GLOBALS['app']->LoadGadget('Ads', 'Model');
		/*
		$parents = $model->GetAdParentsOfUserID((int)$uid);
		if (!Jaws_Error::IsError($parents)) {
			foreach ($parents as $page) {
				$result = $this->DeleteAdParent($page['adparentid'], true);
				if (Jaws_Error::IsError($result)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('ADS_ERROR_USER_ADPARENT_NOT_DELETED'), RESPONSE_ERROR);
					return false;
				}
			}
			$GLOBALS['app']->Session->PushLastResponse(_t('ADS_USER_ADPARENTS_DELETED'), RESPONSE_NOTICE);
		} else {
			$GLOBALS['app']->Session->PushLastResponse(_t('ADS_ERROR_USER_ADPARENTS_NOT_DELETED'), RESPONSE_NOTICE);
			return false;
		}
		*/
		$ads = $model->GetAdsOfUserID((int)$uid);
		if (!Jaws_Error::IsError($ads)) {
			foreach ($ads as $page) {
				$result = $this->DeleteAd($page['id'], true);
				if (Jaws_Error::IsError($result)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('ADS_ERROR_USER_AD_NOT_DELETED'), RESPONSE_ERROR);
					return false;
				}
			}
			$GLOBALS['app']->Session->PushLastResponse(_t('ADS_USER_ADS_DELETED'), RESPONSE_NOTICE);
		} else {
			$GLOBALS['app']->Session->PushLastResponse(_t('ADS_ERROR_USER_ADS_NOT_DELETED'), RESPONSE_NOTICE);
			return false;
		}
		$brands = $model->GetAdBrandsOfUserID((int)$uid);
		if (!Jaws_Error::IsError($brands)) {
			foreach ($brands as $page) {
				$result = $this->DeleteBrand($page['id'], true);
				if (Jaws_Error::IsError($result)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('ADS_ERROR_USER_ADBRAND_NOT_DELETED'), RESPONSE_ERROR);
					return false;
				}
			}
			$GLOBALS['app']->Session->PushLastResponse(_t('ADS_USER_ADBRANDS_DELETED'), RESPONSE_NOTICE);
		} else {
			$GLOBALS['app']->Session->PushLastResponse(_t('ADS_ERROR_USER_ADBRANDS_NOT_DELETED'), RESPONSE_NOTICE);
			return false;
		}
		return true;
    }	

	/**
     * Inserts checksums for default (insert.xml) content
     *
     * @access  public
     * @param   string  $gadget   Gadget name from onAfterEnablingGadget shouter call
     * @return  array   Response
     */
    function InsertDefaultChecksums($gadget)
    {
		if ($gadget == 'Ads') {
			// Update checksum
			$config_key = $GLOBALS['app']->Registry->Get('/config/key');		
			
			$model = $GLOBALS['app']->LoadGadget('Ads', 'Model');
			$parents = $model->GetAdParents();
			if (Jaws_Error::IsError($parents)) {
				return false;
			}
			foreach ($parents as $parent) {
				if (empty($parent['adparentchecksum']) || is_null($parent['adparentchecksum']) || strpos($parent['adparentchecksum'], ':') === false) {
					$params               	= array();
					$params['id'] 			= $parent['adparentid'];
					$params['checksum'] 	= $parent['adparentid'].':'.$config_key;
					
					$sql = '
						UPDATE [[adparent]] SET
							[adparentchecksum] = {checksum}
						WHERE [adparentid] = {id}';

					$result = $GLOBALS['db']->query($sql, $params);
					if (Jaws_Error::IsError($result)) {
						$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
						return false;
					}

					// Let everyone know it has been added
					$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
					$res = $GLOBALS['app']->Shouter->Shout('onAddAdParent', $parent['adparentid']);
					if (Jaws_Error::IsError($res) || !$res) {
						return $res;
					}

					$posts = $model->GetAllAdsOfParent($parent['adparentid']);
					if (Jaws_Error::IsError($posts)) {
						return false;
					}
					foreach ($posts as $post) {
						if (empty($post['checksum']) || is_null($post['checksum']) || strpos($post['checksum'], ':') === false) {
							$params               	= array();
							$params['id'] 			= $post['id'];
							$params['checksum'] 	= $post['id'].':'.$config_key;
							
							$sql = '
								UPDATE [[ads]] SET
									[checksum] = {checksum}
								WHERE [id] = {id}';

							$result = $GLOBALS['db']->query($sql, $params);
							if (Jaws_Error::IsError($result)) {
								$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
								return false;
							}

							// Let everyone know it has been added
							$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
							$res = $GLOBALS['app']->Shouter->Shout('onAddAd', $post['id']);
							if (Jaws_Error::IsError($res) || !$res) {
								return $res;
							}

						}
					}
					
				}
			}
			$posts = $model->GetBrands();
			if (Jaws_Error::IsError($posts)) {
				return false;
			}
			foreach ($posts as $post) {
				if (empty($post['checksum']) || is_null($post['checksum']) || strpos($post['checksum'], ':') === false) {
					$params               	= array();
					$params['id'] 			= $post['id'];
					$params['checksum'] 	= $post['id'].':'.$config_key;
					
					$sql = '
						UPDATE [[adbrand]] SET
							[checksum] = {checksum}
						WHERE [id] = {id}';

					$result = $GLOBALS['db']->query($sql, $params);
					if (Jaws_Error::IsError($result)) {
						$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
						return false;
					}

					// Let everyone know it has been added
					$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
					$res = $GLOBALS['app']->Shouter->Shout('onAddAdBrand', $post['id']);
					if (Jaws_Error::IsError($res) || !$res) {
						return $res;
					}
				}
			}
		}
		return true;
    }
}
