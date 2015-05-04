<?php
/**
 * Properties Gadget
 *
 * @category   GadgetModel
 * @package    Properties
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */

ini_set("memory_limit","100M");
ini_set("post_max_size","25M");
ini_set("upload_max_filesize","2M");
ini_set("max_execution_time","5000");

require_once JAWS_PATH . 'gadgets/Properties/Model.php';
class PropertiesAdminModel extends PropertiesModel
{
    var $_Name = 'Properties';
    var $_newChecksums = array();
    var $_propCount = 1;
    var $_propTotal = 0;

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
        $GLOBALS['app']->Shouter->NewShouter('Properties', 'onAddPropertyParent');   	// trigger an action when we add a parent
        $GLOBALS['app']->Shouter->NewShouter('Properties', 'onDeletePropertyParent');	// trigger an action when we delete a parent
        $GLOBALS['app']->Shouter->NewShouter('Properties', 'onUpdatePropertyParent');	// and when we update a parent..
        $GLOBALS['app']->Shouter->NewShouter('Properties', 'onAddProperty');   			// trigger an action when we add a property
        $GLOBALS['app']->Shouter->NewShouter('Properties', 'onDeleteProperty');			// trigger an action when we delete a property
        $GLOBALS['app']->Shouter->NewShouter('Properties', 'onUpdateProperty');			// and when we update a property..
        $GLOBALS['app']->Shouter->NewShouter('Properties', 'onAddPropertyPost');   		
        $GLOBALS['app']->Shouter->NewShouter('Properties', 'onDeletePropertyPost');		
        $GLOBALS['app']->Shouter->NewShouter('Properties', 'onUpdatePropertyPost');		
        $GLOBALS['app']->Shouter->NewShouter('Properties', 'onAddPropertyAmenity');   	
        $GLOBALS['app']->Shouter->NewShouter('Properties', 'onDeletePropertyAmenity');	
        $GLOBALS['app']->Shouter->NewShouter('Properties', 'onUpdatePropertyAmenity');	
        $GLOBALS['app']->Shouter->NewShouter('Properties', 'onAddPropertyAmenityType');   	
        $GLOBALS['app']->Shouter->NewShouter('Properties', 'onDeletePropertyAmenityType');	
        $GLOBALS['app']->Shouter->NewShouter('Properties', 'onUpdatePropertyAmenityType');	

        $GLOBALS['app']->loadClass('Listener', 'Jaws_EventListener');
        $GLOBALS['app']->Listener->NewListener('Properties', 'onAddProperty', 'AddPropertyCalendar');
        $GLOBALS['app']->Listener->NewListener('Properties', 'onAddProperty', 'AddOwnedProperty');
        $GLOBALS['app']->Listener->NewListener('Properties', 'onAddProperty', 'ActivatePropertiesCategories');
        $GLOBALS['app']->Listener->NewListener('Properties', 'onUpdateProperty', 'UpdatePropertiesCategories');
        $GLOBALS['app']->Listener->NewListener('Properties', 'onUpdateProperty', 'UpdatePropertyCalendar');
        $GLOBALS['app']->Listener->NewListener('Properties', 'onDeleteProperty', 'RemoveOwnedProperty');
        $GLOBALS['app']->Listener->NewListener('Properties', 'onDeleteProperty', 'RemovePropertyCalendar');
        $GLOBALS['app']->Listener->NewListener('Properties', 'onDeleteUser', 'RemoveUserProperties');
        $GLOBALS['app']->Listener->NewListener('Properties', 'onUpdateUser', 'UpdateUserProperties');
		$GLOBALS['app']->Listener->NewListener('Properties', 'onAfterEnablingGadget', 'InsertDefaultChecksums');
		
        $GLOBALS['app']->Registry->NewKey('/gadgets/Properties/showmap', 'Y');
        $GLOBALS['app']->Registry->NewKey('/gadgets/Properties/showcalendar', 'Y');
        $GLOBALS['app']->Registry->NewKey('/gadgets/Properties/randomize', 'Y');
        $GLOBALS['app']->Registry->NewKey('/gadgets/Properties/user_post_limit', 6);
        $GLOBALS['app']->Registry->NewKey('/gadgets/Properties/user_desc_char_limit', 650);
        $GLOBALS['app']->Registry->NewKey('/gadgets/Properties/user_mask_owner_email', 'Y');
        $GLOBALS['app']->Registry->NewKey('/gadgets/Properties/user_min_price', 0);
        $GLOBALS['app']->Registry->NewKey('/gadgets/Properties/user_max_price', 0);
        $GLOBALS['app']->Registry->NewKey('/gadgets/Properties/user_status_limit', 'forsale,forrent,forlease,undercontract,sold,rented,leased');
		/*
		if (!in_array('Properties', explode(',', $GLOBALS['app']->Registry->Get('/gadgets/user_access_items')))) {
			if ($GLOBALS['app']->Registry->Get('/gadgets/user_access_items') == '') {
				$GLOBALS['app']->Registry->Set('/gadgets/user_access_items', 'Properties');
			} else {
				$GLOBALS['app']->Registry->Set('/gadgets/user_access_items', $GLOBALS['app']->Registry->Get('/gadgets/user_access_items').',Properties');
			}
		}
        */
		
		//Create Jaws_User groups
        require_once JAWS_PATH . 'include/Jaws/User.php';
        $userModel = new Jaws_User;
        $userModel->addGroup('properties_owners', false); //Don't check if it returns true or false
        $group = $userModel->GetGroupInfoByName('properties_owners');
		if (isset($group['id']) && !empty($group['id'])) {
			$GLOBALS['app']->ACL->NewKey('/ACL/groups/'.$group['id'].'/gadgets/Properties/OwnProperty', 'true');
        }
        //$userModel->addGroup('properties_users', false); //Don't check if it returns true or false
        
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
        $tables = array('resdates',
                        'resrates',
						'propertyparent',
						'property',
						'property_posts',
						'propertyamenity',
						'amenity_types',
						'property_rss_hide',
						'properties_parents');
        foreach ($tables as $table) {
            $result = $GLOBALS['db']->dropTable($table);
            if (Jaws_Error::IsError($result)) {
                $gName  = _t('PROPERTIES_NAME');
                $errMsg = _t('GLOBAL_ERROR_GADGET_NOT_UNINSTALLED', $gName);
                $GLOBALS['app']->Session->PushLastResponse($errMsg, RESPONSE_ERROR);
                return new Jaws_Error($errMsg, $gName);
            }
        }

        $GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
        $GLOBALS['app']->Shouter->DeleteShouter('Properties', 'onAddPropertyParent');   	// trigger an action when we add a parent
        $GLOBALS['app']->Shouter->DeleteShouter('Properties', 'onDeletePropertyParent');	// trigger an action when we delete a parent
        $GLOBALS['app']->Shouter->DeleteShouter('Properties', 'onUpdatePropertyParent');	// and when we update a parent..
        $GLOBALS['app']->Shouter->DeleteShouter('Properties', 'onAddProperty');   			// trigger an action when we add a property
        $GLOBALS['app']->Shouter->DeleteShouter('Properties', 'onDeleteProperty');			// trigger an action when we delete a property
        $GLOBALS['app']->Shouter->DeleteShouter('Properties', 'onUpdateProperty');			// and when we update a property..
        $GLOBALS['app']->Shouter->DeleteShouter('Properties', 'onAddPropertyPost');   		
        $GLOBALS['app']->Shouter->DeleteShouter('Properties', 'onDeletePropertyPost');		
        $GLOBALS['app']->Shouter->DeleteShouter('Properties', 'onUpdatePropertyPost');		
        $GLOBALS['app']->Shouter->DeleteShouter('Properties', 'onAddPropertyAmenity');   	
        $GLOBALS['app']->Shouter->DeleteShouter('Properties', 'onDeletePropertyAmenity');	
        $GLOBALS['app']->Shouter->DeleteShouter('Properties', 'onUpdatePropertyAmenity');	
        $GLOBALS['app']->Shouter->DeleteShouter('Properties', 'onAddPropertyAmenityType');   	
        $GLOBALS['app']->Shouter->DeleteShouter('Properties', 'onDeletePropertyAmenityType');	
        $GLOBALS['app']->Shouter->DeleteShouter('Properties', 'onUpdatePropertyAmenityType');	

        $GLOBALS['app']->loadClass('Listener', 'Jaws_EventListener');
        $GLOBALS['app']->Listener->DeleteListener('Properties', 'AddPropertyCalendar');
        $GLOBALS['app']->Listener->DeleteListener('Properties', 'UpdatePropertyCalendar');
        $GLOBALS['app']->Listener->DeleteListener('Properties', 'RemovePropertyCalendar');
        $GLOBALS['app']->Listener->DeleteListener('Properties', 'AddOwnedProperty');
        $GLOBALS['app']->Listener->DeleteListener('Properties', 'RemoveOwnedProperty');
        $GLOBALS['app']->Listener->DeleteListener('Properties', 'ActivatePropertiesCategories');
        $GLOBALS['app']->Listener->DeleteListener('Properties', 'UpdatePropertiesCategories');
        $GLOBALS['app']->Listener->DeleteListener('Properties', 'RemoveUserProperties');
        $GLOBALS['app']->Listener->DeleteListener('Properties', 'UpdateUserProperties');
		$GLOBALS['app']->Listener->DeleteListener('Properties', 'InsertDefaultChecksums');

        $GLOBALS['app']->Registry->DeleteKey('/gadgets/Properties/showmap');
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/Properties/showcalendar');
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/Properties/randomize');
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/Properties/user_post_limit');
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/Properties/user_desc_char_limit');
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/Properties/user_mask_owner_email');
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/Properties/user_min_price');
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/Properties/user_max_price');
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/Properties/user_status_limit');
		/*
		if (in_array('Properties', explode(',', $GLOBALS['app']->Registry->Get('/gadgets/user_access_items')))) {
			if ($GLOBALS['app']->Registry->Get('/gadgets/user_access_items') == 'Properties') {
				$GLOBALS['app']->Registry->Set('/gadgets/user_access_items', '');
			} else {
				$GLOBALS['app']->Registry->Set('/gadgets/user_access_items', str_replace(',Properties', '', $GLOBALS['app']->Registry->Get('/gadgets/user_access_items')));
			}
		}
		*/
		
		//Delete Jaws_User groups
        require_once JAWS_PATH . 'include/Jaws/User.php';
        $userModel = new Jaws_User;
        $group = $userModel->GetGroupInfoByName('properties_owners');
		if (isset($group['id']) && !empty($group['id'])) {
			$result = $userModel->DeleteGroup($group['id']);
			$GLOBALS['app']->ACL->DeleteKey('/ACL/groups/'.$group['id'].'/gadgets/Properties/OwnProperty');
            if (Jaws_Error::IsError($result)) {
				echo $result->getMessage();
			}
		}
        /*
		$group = $userModel->GetGroupInfoByName('properties_users');
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
     * Create property categories.
     *
     * @category 	feature
     * @param   int  $propertyparentsort_order 	Priority order
     * @param   int  $propertyparentParent 	Parent ID
     * @param   string  $propertyparentCategory_Name    The title of the property category
     * @param   string  $propertyparentDescription    The description of the property category
     * @param   string  $propertyparentImage    Image
     * @param   string  $propertyparentFeatured         (Y/N) If the property category is featured
     * @param   string  $propertyparentActive         (Y/N) If the property category is active
     * @param   int  $propertyparentOwnerID         The poster's user ID
     * @param   string  $propertyparentRss_url         RSS URL
     * @param   int  $propertyparentRegionID         Region ID (country DB table) this property category belongs to
     * @param   string  $propertyparentRss_overridecity         Force all properties in this category to have "city" of this value
     * @param   string  $propertyparentrandomize         (Y/N) Randomize property listings in this category
     * @param   string  $propertyparentchecksum         Unique ID
     * @param   boolean 	$auto 	If it's auto saved or not
     * @access  public
     * @return  bool    Success/failure
     * @TODO  Add create_menu flag
     */   
	function AddPropertyParent(
		$propertyparentsort_order, $propertyparentParent = null, $propertyparentCategory_Name, 
		$propertyparentDescription, $propertyparentImage, $propertyparentFeatured = 'N', 
		$propertyparentActive = 'Y', $propertyparentOwnerID = null, $propertyparentRss_url = '', 
		$propertyparentRegionID = null, $propertyparentRss_overridecity = '', $propertyparentrandomize = 'Y', 
		$propertyparentchecksum = '', $auto = false
	) {
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$model = $GLOBALS['app']->LoadGadget('Properties', 'Model');
		if (empty($propertyparentCategory_Name)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_INVALID_TITLE'), RESPONSE_ERROR);
			require_once JAWS_PATH . 'include/Jaws/Header.php';
			if (BASE_SCRIPT != 'index.php') {
				Jaws_Header::Location(BASE_SCRIPT . '?gadget=Properties&action=form');
			} else {
				Jaws_Header::Location('index.php?gadget=Properties&action=account_form');
			}
		}

		// If the checksum is found, don't add it.
		$pages = $model->GetPropertyParents();
		if (!Jaws_Error::IsError($pages)) {
			foreach($pages as $p) {		            
				if (!empty($propertyparentchecksum)) {
					if ($p['propertyparentchecksum'] == $propertyparentchecksum) {
						return true;
					}
				}
			}
		}
		
		// Get the fast url
		$propertyparentFast_url = strip_tags($GLOBALS['app']->UTF8->str_replace(',', '', $propertyparentCategory_Name));
        $propertyparentFast_url = $this->GetRealFastUrl(
			$propertyparentFast_url, 'propertyparent', true, 'propertyparentfast_url'
		);

		// set Parent ID to null if this uses a RegionID (from country DB table) 
		if (!empty($propertyparentRegionID) && $propertyparentRegionID != null && is_numeric($propertyparentRegionID)) {
			$region = $model->GetRegion($propertyparentRegionID);
			if (!Jaws_Error::IsError($region)) {
				$propertyparentParent = null;
				$propertyparentRegionID = $region['id'];
			}
		}
        
		$sql = "
            INSERT INTO [[propertyparent]]
                ([propertyparentparent], [propertyparentsort_order], [propertyparentcategory_name], 
				[propertyparentimage], [propertyparentdescription], [propertyparentactive], 
				[propertyparentownerid], [propertyparentcreated], [propertyparentupdated], 
				[propertyparentfeatured], [propertyparentfast_url], [propertyparentrss_url], [propertyparentregionid],
				[propertyparentrss_overridecity], [propertyparentrandomize], [propertyparentchecksum])
            VALUES
                ({propertyparentParent}, {propertyparentsort_order}, {propertyparentCategory_Name}, 
				{propertyparentImage}, {propertyparentDescription}, {propertyparentActive}, 
				{propertyparentOwnerID}, {now}, {now}, {propertyparentFeatured}, 
				{propertyparentFast_url}, {propertyparentRss_url}, {propertyparentRegionID},
				{propertyparentRss_overridecity}, {propertyparentrandomize}, {propertyparentchecksum})";
		
		$propertyparentID = $GLOBALS['db']->lastInsertID('propertyparent', 'propertyparentid');
		$propertyparentOwnerID = (!is_null($propertyparentOwnerID) ? (int)$propertyparentOwnerID : 0);
		if (!empty($propertyparentImage)) {
			$propertyparentImage = $this->cleanImagePath($propertyparentImage);
			if (
				$propertyparentOwnerID > 0 && 
				(substr(strtolower(trim($propertyparentImage)), 0, 4) == 'http' || 
				substr(strtolower(trim($propertyparentImage)), 0, 2) == '//' || 
				substr(strtolower(trim($propertyparentImage)), 0, 2) == '\\\\')
			) {
				$propertyparentImage = '';
			}
		}
		$propertyparentParent = (!is_null($propertyparentParent) ? (int)$propertyparentParent : null);
		$propertyparentDescription = strip_tags($propertyparentDescription, '<p><a><b><img><em><i><strong><div><table><tbody><thead><tr><td><span><font><hr><br><marquee><ul><li>');
		$propertyparentRss_url = (!is_null($propertyparentRss_url) && !empty($propertyparentRss_url) ? $xss->parse(strip_tags($propertyparentRss_url)) : null);
		$propertyparentRss_overridecity = (!is_null($propertyparentRss_overridecity) && !empty($propertyparentRss_overridecity) ? strip_tags($propertyparentRss_overridecity) : '');
		$propertyparentCategory_Name = strip_tags($GLOBALS['app']->UTF8->str_replace(',', '', $propertyparentCategory_Name));
	
        $params = array();
		$params['propertyparentParent'] 			= $propertyparentParent;
		$params['propertyparentsort_order'] 		= (int)$propertyparentsort_order;
		$params['propertyparentCategory_Name'] 		= $xss->parse($propertyparentCategory_Name);
		$params['propertyparentImage'] 				= $xss->parse(strip_tags($propertyparentImage));
		$params['propertyparentDescription'] 		= str_replace("\r\n", "\n", $propertyparentDescription);
		$params['propertyparentActive'] 			= $xss->parse($propertyparentActive);
		$params['propertyparentFeatured'] 			= $xss->parse($propertyparentFeatured);
		$params['propertyparentFast_url'] 			= $xss->parse($propertyparentFast_url);
		$params['propertyparentRss_url'] 			= $propertyparentRss_url;
		$params['propertyparentOwnerID'] 			= $propertyparentOwnerID;
		$params['propertyparentRegionID'] 			= $propertyparentRegionID;
		$params['propertyparentRss_overridecity'] 	= $xss->parse($propertyparentRss_overridecity);
		$params['propertyparentrandomize'] 			= $xss->parse($propertyparentrandomize);
		$params['propertyparentchecksum'] 			= $xss->parse($propertyparentchecksum);
        $params['now'] 								= $GLOBALS['db']->Date();
		
		$result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_PROPERTYPARENT_NOT_ADDED'), RESPONSE_ERROR);
            return $result;
        }
        $newid = $GLOBALS['db']->lastInsertID('propertyparent', 'propertyparentid');

		if (BASE_SCRIPT != 'index.php') {
			// add Menu Item for Page
			$visible = ($propertyparentActive == 'Y') ? 1 : 0;
			$url = $GLOBALS['app']->Map->GetURLFor('Properties', 'Category', array('id' => $xss->parse($propertyparentFast_url)));
			
			// get propertyparentParent's group id
			$parentGid = 1;
			if ($propertyparentParent > 0) {
				$sql  = 'SELECT [gid] FROM [[menus]] WHERE [id] = {id}';
				$gid = $GLOBALS['db']->queryRow($sql, array('id' => $propertyparentParent));
				if (Jaws_Error::IsError($gid)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
					return false;
				} else if (isset($gid['gid'])) {
					$parentGid = $gid['gid'];
				}
			}
			$sql  = 'SELECT [id] FROM [[menus]] WHERE [url] = {url}';
			$oid = $GLOBALS['db']->queryRow($sql, array('url' => $url));
			if (Jaws_Error::IsError($oid)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
				return false;
			} else {
				if (empty($oid['id'])) {
					// Get highest rank of current menu items
					$sql = "SELECT MAX([rank]) FROM [[menus]] WHERE [gid] = {gid} ORDER BY [rank] DESC";
					$rank = $GLOBALS['db']->queryOne($sql, array('gid' => $parentGid));
					if (Jaws_Error::IsError($rank)) {
						$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
						return false;
					}
					$menuAdmin = $GLOBALS['app']->LoadGadget('Menu', 'AdminModel');
					if (
						!$menuAdmin->InsertMenu(
							$propertyparentParent, 
							$parentGid, 
							'Properties', 
							$xss->parse(strip_tags($GLOBALS['app']->UTF8->str_replace(',', '', $propertyparentCategory_Name))), 
							$url, 
							0, 
							(int)$rank+1, 
							$visible, 
							true
						)
					) {
						$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
						return false;
					}
				} else {
					$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
					return false;
				}
			}
		}
		
		// Insert RSS Properties into property table
		if (!empty($propertyparentRss_url)) {
			$scheduler = $GLOBALS['app']->insertScheduler($GLOBALS['app']->getSiteURL() . '/index.php?gadget=Properties&action=UpdateRSSProperties&id='.$newid, 43200, strtotime("now"), 0);
		}
		if (substr($scheduler, 0, 5) == 'ERROR') {
			$GLOBALS['app']->Session->PushLastResponse("Couldn't schedule RSS import.", RESPONSE_ERROR);
			return false;
		}

		if (empty($propertyparentchecksum)) {
			// Update checksum
			$config_key = $GLOBALS['app']->Registry->Get('/config/key');		
			$params               	= array();
			$params['id'] 			= $newid;
			$params['checksum'] 	= $newid.':'.$config_key;
			
			$sql = '
				UPDATE [[propertyparent]] SET
					[propertyparentchecksum] = {checksum}
				WHERE [propertyparentid] = {id}';

			$result = $GLOBALS['db']->query($sql, $params);
			if (Jaws_Error::IsError($result)) {
				$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
				return false;
			}
		}
		
		// Let everyone know it has been added
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onAddPropertyParent', $newid);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		
        $GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_PROPERTYPARENT_CREATED'), RESPONSE_NOTICE);
        return $newid;
    }

    /**
     * Updates a property category.
     *
     * @param   int     $propertyparentID             The ID of the property category to update.
     * @param   int  $propertyparentParent 	Parent ID
     * @param   string  $propertyparentCategory_Name    The title of the property category
     * @param   int  $propertyparentsort_order 	Priority order
     * @param   string  $propertyparentDescription    The description of the property category
     * @param   string  $propertyparentImage    Image
     * @param   string  $propertyparentFeatured         (Y/N) If the property category is featured
     * @param   string  $propertyparentActive         (Y/N) If the property category is active
     * @param   string  $propertyparentRss_url         RSS URL
     * @param   int  $propertyparentRegionID         Region ID (country DB table) this property category belongs to
     * @param   string  $propertyparentRss_overridecity         Force all properties in this category to have "city" of this value
     * @param   string  $propertyparentrandomize         (Y/N) Randomize property listings in this category
     * @param   boolean 	$auto 	If it's auto saved or not
     * @access  public
     * @return  boolean Success/failure
     */
	function UpdatePropertyParent(
		$propertyparentID, $propertyparentParent, $propertyparentCategory_Name,  
		$propertyparentsort_order, $propertyparentDescription, $propertyparentImage, 
		$propertyparentFeatured, $propertyparentActive, $propertyparentRss_url,
		$propertyparentRegionID, $propertyparentRss_overridecity, $propertyparentrandomize, 
		$auto = false
	) {
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$model = $GLOBALS['app']->LoadGadget('Properties', 'Model');
		if (empty($propertyparentCategory_Name)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_INVALID_TITLE'), RESPONSE_ERROR);
			require_once JAWS_PATH . 'include/Jaws/Header.php';
			if (BASE_SCRIPT != 'index.php') {
				Jaws_Header::Location(BASE_SCRIPT . '?gadget=Properties&action=form&id='.$propertyparentID);
			} else {
				Jaws_Header::Location('index.php?gadget=Properties&action=account_form&id='.$propertyparentID);
			}
		}
        
		// Get the fast url
		$propertyparentFast_url = strip_tags($GLOBALS['app']->UTF8->str_replace(',', '', $propertyparentCategory_Name));
        $propertyparentFast_url = $this->GetRealFastUrl(
			$propertyparentFast_url, 'propertyparent', true, 'propertyparentfast_url', 'propertyparentid', $productparentID
		);
		
        //Current fast url changes?
        if ($page['propertyparentfast_url']  != $propertyparentFast_url && $auto === false) {
            $oldfast_url = $page['propertyparentfast_url'];
        }

        $page = $model->GetPropertyParent($propertyparentID);
        if (Jaws_Error::isError($page)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_PROPERTYPARENT_NOT_FOUND'), RESPONSE_ERROR);
            return $page;
        }

		if (!empty($propertyparentImage)) {
			$propertyparentImage = $this->cleanImagePath($propertyparentImage);
			if (
				$page['propertyparentownerid'] > 0 && 
				(substr(strtolower(trim($propertyparentImage)), 0, 4) == 'http' || 
				substr(strtolower(trim($propertyparentImage)), 0, 2) == '//' ||	
				substr(strtolower(trim($propertyparentImage)), 0, 2) == '\\\\')
			) {
				$propertyparentImage = '';
			}
		}

		// set Parent ID to null if this uses a RegionID (from country DB table) 
		if (!empty($propertyparentRegionID) && $propertyparentRegionID != null && is_numeric($propertyparentRegionID)) {
			$region = $model->GetRegion($propertyparentRegionID);
			if (!Jaws_Error::IsError($region)) {
				$propertyparentParent = null;
				$propertyparentRegionID = $region['id'];
			}
		}

        $sql = '
            UPDATE [[propertyparent]] SET
				';
		if (!is_null($propertyparentParent)) {
			$params['propertyparentParent'] = (int)$propertyparentParent;
			$sql .= '[propertyparentparent] = {propertyparentParent},
			';
		}
		$sql .= '[propertyparentsort_order] = {propertyparentsort_order},
				[propertyparentcategory_name] = {propertyparentCategory_Name},
				[propertyparentimage] = {propertyparentImage},
				[propertyparentdescription] = {propertyparentDescription},
				[propertyparentactive] = {propertyparentActive},
				[propertyparentupdated] = {now},
				[propertyparentfeatured] = {propertyparentFeatured},
				[propertyparentfast_url] = {propertyparentFast_url},
				[propertyparentrss_url] = {propertyparentRss_url},
				[propertyparentrss_overridecity] = {propertyparentRss_overridecity},
				[propertyparentrandomize] = {propertyparentrandomize}
			WHERE [propertyparentid] = {propertyparentID}';

		$propertyparentParent = (!is_null($propertyparentParent) ? (int)$propertyparentParent : null);
		$propertyparentDescription = strip_tags($propertyparentDescription, '<p><a><b><img><em><i><strong><div><table><tbody><thead><tr><td><span><font><hr><br><marquee><ul><li>');
		$propertyparentRss_url = (!is_null($propertyparentRss_url) && !empty($propertyparentRss_url) ? $xss->parse(strip_tags($propertyparentRss_url)) : null);
		$propertyparentRss_overridecity = (!is_null($propertyparentRss_overridecity) && !empty($propertyparentRss_overridecity) ? strip_tags($propertyparentRss_overridecity) : '');

        $params = array();
        $params['propertyparentID']         		= (int)$propertyparentID;
		$params['propertyparentParent'] 			= $propertyparentParent;
		$params['propertyparentsort_order'] 		= $propertyparentsort_order;
		$params['propertyparentCategory_Name'] 		= $xss->parse(strip_tags($GLOBALS['app']->UTF8->str_replace(',', '', $propertyparentCategory_Name)));
		$params['propertyparentImage'] 				= $xss->parse(strip_tags($propertyparentImage));
		$params['propertyparentDescription'] 		= str_replace("\r\n", "\n", $propertyparentDescription);
		$params['propertyparentActive']				= $xss->parse($propertyparentActive);
		$params['propertyparentFeatured'] 			= $xss->parse($propertyparentFeatured);
		$params['propertyparentFast_url'] 			= $xss->parse($propertyparentFast_url);
		$params['propertyparentRss_url'] 			= $propertyparentRss_url;
		$params['propertyparentRegionID'] 			= $propertyparentRegionID;
		$params['propertyparentRss_overridecity'] 	= $xss->parse($propertyparentRss_overridecity);
		$params['propertyparentrandomize']			= $xss->parse($propertyparentrandomize);
        $params['now'] 								= $GLOBALS['db']->Date();		

        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
			//$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_PROPERTYPARENT_NOT_UPDATED'), RESPONSE_ERROR);
			$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
            return $result;
        }
								
		if (BASE_SCRIPT != 'index.php') {
			// update Menu Item for Page
			$visible = ($propertyparentActive == 'Y') ? 1 : 0;
			// if old title is different, update menu item
			if (isset($oldfast_url) && !empty($oldfast_url)) {
				$old_url = $GLOBALS['app']->Map->GetURLFor('Properties', 'Category', array('id' => $oldfast_url));
			} else {
				$old_url = $GLOBALS['app']->Map->GetURLFor('Properties', 'Category', array('id' => $propertyparentFast_url));
			}
			$new_url = $GLOBALS['app']->Map->GetURLFor('Properties', 'Category', array('id' => $propertyparentFast_url));
			
			// get propertyparentParent's group id
			$parentGid = 1;
			$propertyparentParent = (!is_null($propertyparentParent) ? (int)$propertyparentParent : $page['propertyparentparent']);
			if ($propertyparentParent > 0) {
				$sql  = 'SELECT [gid] FROM [[menus]] WHERE [id] = {id}';
				$gid = $GLOBALS['db']->queryRow($sql, array('id' => $propertyparentParent));
				if (Jaws_Error::IsError($gid)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
					return false;
				} else if (isset($gid['gid'])) {
					$parentGid = $gid['gid'];
				}
			}
			$sql  = 'SELECT [id], [rank] FROM [[menus]] WHERE [url] = {url}';
			$oid = $GLOBALS['db']->queryRow($sql, array('url' => $old_url));
			if (Jaws_Error::IsError($oid)) {
				//$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
				$GLOBALS['app']->Session->PushLastResponse($oid->GetMessage(), RESPONSE_ERROR);
				return false;
			} else if (!empty($oid['id']) && isset($oid['id'])) {
				$menuAdmin = $GLOBALS['app']->LoadGadget('Menu', 'AdminModel');
				if (
					!$menuAdmin->UpdateMenu(
						$oid['id'], 
						(int)$propertyparentParent, 
						$parentGid, 
						'Properties', 
						$xss->parse(strip_tags($GLOBALS['app']->UTF8->str_replace(',', '', $propertyparentCategory_Name))), 
						$new_url, 
						0, 
						$oid['rank'], 
						$visible
					)
				) {
					//$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
					$GLOBALS['app']->Session->PushLastResponse($menuAdmin->GetMessage(), RESPONSE_ERROR);
					return false;
				}
			} else {
				// add Menu Item for Page
				$visible = ($propertyparentActive == 'Y') ? 1 : 0;
				$url = $GLOBALS['app']->Map->GetURLFor('Properties', 'Category', array('id' => $xss->parse($propertyparentFast_url)));
				
				$sql  = 'SELECT [id] FROM [[menus]] WHERE [url] = {url}';
				$oid = $GLOBALS['db']->queryRow($sql, array('url' => $url));
				if (Jaws_Error::IsError($oid)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
					return false;
				} else {
					if (empty($oid['id'])) {
						// Get highest rank of current menu items
						$sql = "SELECT MAX([rank]) FROM [[menus]] WHERE [gid] = {gid} ORDER BY [rank] DESC";
						$rank = $GLOBALS['db']->queryOne($sql, array('gid' => $parentGid));
						if (Jaws_Error::IsError($rank)) {
							$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
							return false;
						}
						$menuAdmin = $GLOBALS['app']->LoadGadget('Menu', 'AdminModel');
						if (
							!$menuAdmin->InsertMenu(
								(int)$propertyparentParent, 
								$parentGid, 
								'Properties', 
								$xss->parse(strip_tags($GLOBALS['app']->UTF8->str_replace(',', '', $propertyparentCategory_Name))), 
								$url, 
								0, 
								(int)$rank+1, 
								$visible, 
								true
							)
						) {
							$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
							return false;
						}
					} else {
						$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
						return false;
					}
				}
			}
		}

		// Insert or Update Scheduler to run given URL for updateRSSProperties every 12 hours, starting now 
		$params = array();
		$params['scriptpath'] = $GLOBALS['app']->getSiteURL() . '/index.php?gadget=Properties&action=UpdateRSSProperties&id='.(int)$propertyparentID;
		$sql = 'SELECT [id] FROM [phpjobscheduler] WHERE ([scriptpath] = {scriptpath})';
		$found = $GLOBALS['db']->queryOne($sql, $params);
		
		$scheduler = true;
		if (is_numeric($found)) {
			if (!empty($propertyparentRss_url)) {
				$scheduler = $GLOBALS['app']->updateScheduler($found, 43200, strtotime("now"), 0);
			} else {
				$scheduler = $GLOBALS['app']->deleteScheduler($found);
			}
		} else {
			// Insert RSS Properties into property table
			if (!empty($propertyparentRss_url)) {
				$scheduler = $GLOBALS['app']->insertScheduler($GLOBALS['app']->getSiteURL() . '/index.php?gadget=Properties&action=UpdateRSSProperties&id='.(int)$propertyparentID, 43200, strtotime("now"), 0);
			}
		}
		if (Jaws_Error::IsError($scheduler)) {
			return new Jaws_Error($scheduler->GetMessage(), _t('PROPERTIES_NAME'));
		}

		// Let everyone know
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onUpdatePropertyParent', $propertyparentID);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		
		if ($auto) {
            $GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_PROPERTYPARENT_AUTOUPDATED',
                                                     date('H:i:s'),
                                                     (int)$propertyparentID,
                                                     date('D, d')),
                                                  RESPONSE_NOTICE);
        } else {
            $GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_PROPERTYPARENT_UPDATED'), RESPONSE_NOTICE);
        }
        return true;
    }

	/**
     * Delete a property category
     *
     * @param 	int 	$id 	Property category ID
     * @param 	boolean 	$massive 	Is this part of a massive delete?
     * @access  public
     * @return  boolean True if query was successful and Jaws_Error on error
     */
    function DeletePropertyParent($id, $massive = false)
    {
		$model = $GLOBALS['app']->LoadGadget('Properties', 'Model');
		$parent = $model->GetPropertyParent((int)$id);
		if (Jaws_Error::IsError($parent)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_PROPERTYPARENT_NOT_DELETED'), RESPONSE_ERROR);
			return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTYPARENT_NOT_DELETED'), _t('PROPERTIES_NAME'));
		}

		if(!isset($parent['propertyparentid'])) {
			$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_PROPERTYPARENT_NOT_DELETED'), RESPONSE_ERROR);
			return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTYPARENT_NOT_DELETED'), _t('PROPERTIES_NAME'));
		} else {
			// Let everyone know
			$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
			$res = $GLOBALS['app']->Shouter->Shout('onDeletePropertyParent', $id);
			if (Jaws_Error::IsError($res) || !$res) {
				return $res;
			}
		
			$eids = $model->GetAllSubCategoriesOfParent($id);
			if (Jaws_Error::IsError($eids)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_PROPERTYPARENT_NOT_DELETED'), RESPONSE_ERROR);
				return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTYPARENT_NOT_DELETED'), _t('PROPERTIES_NAME'));
			}
			foreach ($eids as $eid) {
				$rids = $model->GetAllPropertiesOfParent($eid['propertyparentid']);
				if (Jaws_Error::IsError($rids)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_PROPERTYPARENT_NOT_DELETED'), RESPONSE_ERROR);
					return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTYPARENT_NOT_DELETED'), _t('PROPERTIES_NAME'));
				}

				foreach ($rids as $rid) {
					// Delete property
					$result = $this->DeleteProperty($rid['id'], true);
					if (Jaws_Error::IsError($result)) {
						$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_PROPERTY_NOT_DELETED'), RESPONSE_ERROR);
						return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTY_NOT_DELETED'), _t('PROPERTIES_NAME'));
					}
				}
			}
			$pids = $model->GetAllPropertiesOfParent($parent['propertyparentid']);
			if (Jaws_Error::IsError($pids)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_PROPERTYPARENT_NOT_DELETED'), RESPONSE_ERROR);
				return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTYPARENT_NOT_DELETED'), _t('PROPERTIES_NAME'));
			}

			foreach ($pids as $pid) {
				// Delete property
				$result = $this->DeleteProperty($pid['id'], true);
				if (Jaws_Error::IsError($result)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_PROPERTY_NOT_DELETED'), RESPONSE_ERROR);
					return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTY_NOT_DELETED'), _t('PROPERTIES_NAME'));
				}
			}
			
			if (!empty($parent['propertyparentrss_url'])) {
				/*
				$sql = 'DELETE FROM [[property]] WHERE [rss_url] = {rss_url}';
				$res = $GLOBALS['db']->query($sql, array('rss_url' => $parent['propertyparentrss_url']));
				if (Jaws_Error::IsError($res)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_PROPERTY_NOT_DELETED'), RESPONSE_ERROR);
					return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTY_NOT_DELETED'), _t('PROPERTIES_NAME'));
				}
				*/
				// Delete Scheduler 
				$params = array();
				$params['scriptpath'] = $GLOBALS['app']->getSiteURL() . '/index.php?gadget=Properties&action=UpdateRSSProperties&id='.$parent['propertyparentid'];
				$sql = 'SELECT [id] FROM [phpjobscheduler] WHERE ([scriptpath] = {scriptpath})';
				$found = $GLOBALS['db']->queryOne($sql, $params);
				
				if (is_numeric($found)) {
					$scheduler = $GLOBALS['app']->deleteScheduler($found);
					if (Jaws_Error::IsError($scheduler)) {
						return new Jaws_Error($scheduler->GetMessage(), _t('PROPERTIES_NAME'));
					}
				}
			}
			

			$sql = 'DELETE FROM [[propertyparent]] WHERE [propertyparentid] = {id}';
			$res = $GLOBALS['db']->query($sql, array('id' => $id));
			if (Jaws_Error::IsError($res)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_PROPERTYPARENT_NOT_DELETED'), RESPONSE_ERROR);
				return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTYPARENT_NOT_DELETED'), _t('PROPERTIES_NAME'));
			}

			
			if (BASE_SCRIPT != 'index.php') {
				// delete menu item for page
				$url = $GLOBALS['app']->Map->GetURLFor('Properties', 'Category', array('id' => $parent['propertyparentfast_url']));

				$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
				$res = $GLOBALS['app']->Shouter->Shout('onDeleteMenuItem', $url);
				if (Jaws_Error::IsError($res) || !$res) {
					return $res;
				}
			}
		}

		if ($massive === false) {
			$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_PROPERTYPARENT_DELETED'), RESPONSE_NOTICE);
        }
		return true;
    }

    /**
     * Deletes a group of property categories
     *
     * @param   array   $parents  Array with the IDs of property categories
     * @access  public
     * @return  boolean    Success/failure
     */
    function MassiveDelete($parents)
    {
        if (!is_array($parents)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_PROPERTYPARENT_NOT_MASSIVE_DELETED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTYPARENT_NOT_MASSIVE_DELETED'), _t('PROPERTIES_NAME'));
        }

        foreach ($parents as $page) {
            $res = $this->DeletePropertyParent($page);
            if (Jaws_Error::IsError($res)) {
                $GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_PROPERTYPARENT_NOT_MASSIVE_DELETED'), RESPONSE_ERROR);
                return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTYPARENT_NOT_MASSIVE_DELETED'), _t('PROPERTIES_NAME'));
            }
        }
        $GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_PROPERTYPARENT_MASSIVE_DELETED'), RESPONSE_NOTICE);
        return true;
    }

    /**
     * Creates a new property.
     *
     * @param   int  $LinkID 	ID of property category
     * @param   int  $sort_order 	The priority order
     * @param   string  $category 	Comma separated list of categories
     * @param   string  $mls      		MLS number of property
     * @param   string  $title      		The title of the post.
     * @param   string  $image   		An image to accompany the post or the gadget "layout action"
     * @param   string  $sm_description  The summary of the post.
     * @param   string  $description    	The contents of the post.
     * @param   string  $address    	Address of property
     * @param   string  $city    	City of property
     * @param   string  $region    	State/province/region of property
     * @param   string  $postal_code    	Postal code of property
     * @param   int  $country_id    	Country ID of property (country DB table)
     * @param   string  $community    	Community of property
     * @param   string  $phase    	Phase of property
     * @param   string  $lotno    	Lot number of property
     * @param   string  $price    	Purchase price of property
     * @param   string  $rentdy     Daily rent price of property
     * @param   string  $rentwk    	Weekly rent price of property
     * @param   string  $rentmo    	Monthly rent price of property
     * @param   string  $status    	Status of property (forrent/forsale/undercontract/rented/sold)
     * @param   string  $acreage    	Acreage of property
     * @param   string  $sqft    	Square footage of property
     * @param   string  $bedroom    	Number of bedrooms on property
     * @param   string  $bathroom    	Number of bathrooms on property
     * @param   string  $amenity    	Comma separated list of amenity IDs of property
     * @param   string  $i360    	Virtual Tour URL for property
     * @param   int  $maxchildno    	Maximum number of children allowed on property
     * @param   int  $maxadultno    	Maximum number of adults allowed on property
     * @param   string  $petstay    	(Y/N) Can pets stay on property?
     * @param   int  $occupancy    	Maximum occupancy on property
     * @param   int  $maxcleanno    	Maximum occupancy before cleaning fee applies to property
     * @param   int  $roomcount    	Number of rooms on property
     * @param   int  $minstay    	Minimum number of nights required to rent property
     * @param   string  $options    	Property options
     * @param   string  $item1    	Not used
     * @param   string  $item2    	Unique ID
     * @param   string  $item3    	Not used
     * @param   string  $item4    	Not used
     * @param   string  $item5    	Not used
     * @param   string  $premium    	(Y/N) Is this featured?
     * @param   string  $ShowMap    	(Y/N) Show property on map?
     * @param   string  $featured    	(Y/N) Can this property be shown on site-wide featured areas?
     * @param   int 	$OwnerID  		The poster's user ID
     * @param   string 	$Active  		(Y/N) If the post is published or not
     * @param   int 	$propertyno  		Auto-generated property number
     * @param   string 	$internal_propertyno  		Internal property number
     * @param   string 	$alink  		Hyperlink URL
     * @param   string 	$alinkTitle  		Hyperlink Title
     * @param   string 	$alinkType  		Hyperlink Type (_self/_blank/mail)
     * @param   string 	$alink2  		Hyperlink URL
     * @param   string 	$alink2Title  		Hyperlink Title
     * @param   string 	$alink2Type  		Hyperlink Type (_self/_blank/mail)
     * @param   string 	$alink3  		Hyperlink URL
     * @param   string 	$alink3Title  		Hyperlink Title
     * @param   string 	$alink3Type  		Hyperlink Type (_self/_blank/mail)
     * @param   string 	$calendar_link  		Availability calendar URL
     * @param   string 	$year  		Year built
     * @param   string 	$rss_url  		RSS URL
     * @param   string 	$agent  		Property's agent name
     * @param   string 	$agent_email  		Property's agent email address
     * @param   string 	$agent_phone  		Property's agent telephone
     * @param   string 	$agent_website  		Property's agent website
     * @param   string 	$agent_photo  		Property's agent photo URL
     * @param   string 	$broker  		Property's broker name
     * @param   string 	$broker_email  		Property's broker email
     * @param   string 	$broker_phone  		Property's broker telephone
     * @param   string 	$broker_website  		Property's broker website
     * @param   string 	$broker_logo  		Property's broker logo
     * @param   string 	$coordinates  		Comma separated list of longitude,latitude
     * @param   boolean 	$auto       		If it's auto saved or not
     * @access  public
     * @return 	ID of new property or Jaws_Error on failure
     */
    function AddProperty(
		$LinkID, $sort_order, $category, $mls, $title, $image, 
		$sm_description, $description, $address, $city, $region, $postal_code, $country_id = 1, 
		$community, $phase, $lotno, $price, $rentdy, $rentwk, $rentmo, $status, $acreage, 
		$sqft, $bedroom, $bathroom, $amenity, $i360, $maxchildno, $maxadultno, $petstay, 
		$occupancy, $maxcleanno, $roomcount, $minstay, $options, $item1, $item2, $item3, 
		$item4, $item5, $premium = 'N', $ShowMap = 'Y', $featured = 'N', $OwnerID = null, 
		$Active = 'Y', $propertyno, $internal_propertyno, $alink, $alinkTitle, $alinkType, $alink2, $alink2Title, 
		$alink2Type, $alink3, $alink3Title, $alink3Type, $calendar_link = '', $year = '', $rss_url = '', 
		$agent = '', $agent_email = '', $agent_phone = '', $agent_website = '', $agent_photo = '', $broker = '', 
		$broker_email = '', $broker_phone = '', $broker_website = '', $broker_logo = '', $coordinates = '', $auto = false
	) {
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$model = $GLOBALS['app']->LoadGadget('Properties', 'Model');
		
		$pages = $model->GetProperties();
		if (!Jaws_Error::IsError($pages)) {
			foreach($pages as $p) {		            
				if (!empty($item2) && $p['item2'] == $item2) {					            
					return true;
				}
			}
		}

		// Get the fast url
        $fast_url = !empty($title) ? $title : $address;
		$fast_url = strip_tags($GLOBALS['app']->UTF8->str_replace(',', '', $fast_url));
        $fast_url = $this->GetRealFastUrl(
			$fast_url, 'property', true
		);
		
        $sql = "
            INSERT INTO [[property]]
                ([linkid], [sort_order], [category], [mls], [title], [image], 
				[sm_description], [description], [address], [city], [region], [postal_code], [country_id], 
				[community], [phase], [lotno], [price], [rentdy], [rentwk], [rentmo], [status], [acreage], 
				[sqft], [bedroom], [bathroom], [amenity], [i360], [maxchildno], [maxadultno], [petstay], 
				[occupancy], [maxcleanno], [roomcount], [minstay], [options], [item1], [item2], [item3], 
				[item4], [item5], [premium], [showmap], [featured], [ownerid], [active], [created], [updated], 
				[fast_url], [propertyno], [internal_propertyno], [alink], [alinktitle], [alinktype], [alink2], [alink2title], 
				[alink2type], [alink3], [alink3title], [alink3type], [calendar_link], [year], [rss_url], 
				[agent], [agent_email], [agent_phone], [agent_website], [agent_photo], [broker], 
				[broker_email], [broker_phone], [broker_website], [broker_logo], [coordinates])
            VALUES
                ({LinkID}, {sort_order}, {category}, {mls}, {title}, {image}, 
				{sm_description}, {description}, {address}, {city}, {region}, {postal_code}, {country_id}, 
				{community}, {phase}, {lotno}, {price}, {rentdy}, {rentwk}, {rentmo}, {status}, {acreage}, 
				{sqft}, {bedroom}, {bathroom}, {amenity}, {i360}, {maxchildno}, {maxadultno}, {petstay}, 
				{occupancy}, {maxcleanno}, {roomcount}, {minstay}, {options}, {item1}, {item2}, {item3}, 
				{item4}, {item5}, {premium}, {ShowMap}, {featured}, {OwnerID}, {Active}, {now}, {now}, 
				{fast_url}, {propertyno}, {internal_propertyno}, {alink}, {alinkTitle}, {alinkType}, {alink2}, {alink2Title}, 
				{alink2Type}, {alink3}, {alink3Title}, {alink3Type}, {calendar_link}, {year}, {rss_url}, 
				{agent}, {agent_email}, {agent_phone}, {agent_website}, {agent_photo}, {broker}, 
				{broker_email}, {broker_phone}, {broker_website}, {broker_logo}, {coordinates})";


		if (BASE_SCRIPT != 'index.php' && $auto === false) {
			$description = strip_tags($description, '<p><a><b><em><i><strong><div><table><tbody><thead><tr><td><span><font><hr><br><marquee><ul><li>');
		} else if (trim($rss_url) != '') {
			$description = strip_tags($description, '<p><a><img><b><em><i><strong><div><table><tbody><thead><tr><td><span><font><hr><br><marquee><ul><li>');
		} else {
			if (
				!is_null($OwnerID) && 
				(strlen(strip_tags($description)) > $GLOBALS['app']->Registry->Get('/gadgets/Properties/user_desc_char_limit')) && 
				($GLOBALS['app']->Registry->Get('/gadgets/Properties/user_desc_char_limit') > 0)
			) {
				$description = substr(strip_tags($description), 0, $GLOBALS['app']->Registry->Get('/gadgets/Properties/user_desc_char_limit'));
			} else {
				$description = strip_tags($description, '<p><b><em><i><strong><div><table><tbody><thead><tr><td><span><font><hr><br><marquee><ul><li>');
			}
		}
		$OwnerID = (!is_null($OwnerID)) ? (int)$OwnerID : 0;
		if (!empty($image)) {
			$image = $this->cleanImagePath($image);
			if (
				$OwnerID > 0 && 
				(substr(strtolower(trim($image)), 0, 4) == 'http' || 
				substr(strtolower(trim($image)), 0, 2) == '//' || 
				substr(strtolower(trim($image)), 0, 2) == '\\\\')
			) {
				$image = '';
			}
		}

		// Status
		$checked = $GLOBALS['app']->Registry->Get('/gadgets/Properties/user_status_limit');
		$checked = explode(",",$checked);
		$status_types = '';
		foreach ($checked as $check) {
			$status_types .= (!empty($status_types) ? ', ' : '')._t('PROPERTIES_STATUS_'.strtoupper($check));
		}
		if ($OwnerID > 0 && (!in_array($status, $checked))) {
			return new Jaws_Error(_t('PROPERTIES_ERROR_USER_STATUS_LIMIT', $status_types), _t('PROPERTIES_NAME'));
		}
		
		// Format price
		if (!empty($price)) {
			$newstring = "";
			$array = str_split($price);
			foreach($array as $char) {
				if (($char >= '0' && $char <= '9') || $char == '.') {
					$newstring .= $char;
				}
			}
			$price = number_format($newstring, 2, '.', '');
		}
		if (
			!is_null($OwnerID) && 
			($price > number_format($GLOBALS['app']->Registry->Get('/gadgets/Properties/user_max_price'), 2, '.', '')) && 
			($GLOBALS['app']->Registry->Get('/gadgets/Properties/user_max_price') > 0)
		) {
            return new Jaws_Error(_t('PROPERTIES_ERROR_MAXPRICE_LIMIT', '$'.number_format($GLOBALS['app']->Registry->Get('/gadgets/Properties/user_max_price'), 2, '.', ',')), _t('PROPERTIES_NAME'));
		}
		if (
			!is_null($OwnerID) && 
			($price < number_format($GLOBALS['app']->Registry->Get('/gadgets/Properties/user_min_price'), 2, '.', '')) && 
			($GLOBALS['app']->Registry->Get('/gadgets/Properties/user_min_price') > 0)
		) {
            return new Jaws_Error(_t('PROPERTIES_ERROR_MINPRICE_LIMIT', '$'.number_format($GLOBALS['app']->Registry->Get('/gadgets/Properties/user_min_price'), 2, '.', ',')), _t('PROPERTIES_NAME'));
		}
		// Format bedroom
		if (!empty($bedroom)) {
			$newstring = "";
			$array = str_split($bedroom);
			foreach($array as $char) {
				if (($char >= '0' && $char <= '9') || $char == '.') {
					$newstring .= $char;
				}
			}
			$bedroom = number_format($newstring, 1, '.', '');
		} else {
			$bedroom = 0;
		}
		// Format bathroom
		if (!empty($bathroom)) {
			$newstring = "";
			$array = str_split($bathroom);
			foreach($array as $char) {
				if (($char >= '0' && $char <= '9') || $char == '.') {
					$newstring .= $char;
				}
			}
			$bathroom = number_format($newstring, 1, '.', '');
		} else {
			$bathroom = 0;
		}
		
		// Format rental rates
		if (!empty($rentdy)) {
			$newstring = "";
			$array = str_split($rentdy);
			foreach($array as $char) {
				if (($char >= '0' && $char <= '9') || $char == '.') {
					$newstring .= $char;
				}
			}
			$rentdy = number_format($newstring, 2, '.', '');
		}
		if (!empty($rentwk)) {
			$newstring = "";
			$array = str_split($rentwk);
			foreach($array as $char) {
				if (($char >= '0' && $char <= '9') || $char == '.') {
					$newstring .= $char;
				}
			}
			$rentwk = number_format($newstring, 2, '.', '');
		}
		if (!empty($rentmo)) {
			$newstring = "";
			$array = str_split($rentmo);
			foreach($array as $char) {
				if (($char >= '0' && $char <= '9') || $char == '.') {
					$newstring .= $char;
				}
			}
			$rentmo = number_format($newstring, 2, '.', '');
		}
	   
		// Try to geocode an address, if coordinates weren't given
		if ((!empty($address) || (!empty($region) && !empty($city)))) {
			// build address
			$coordinates = '';
			$address_region = '';
			$address_city = '';
			$address_address = (!empty($address) ? !empty($address) : '');
			
			$marker_address = $address_address;
			if (!empty($city)) {
				$address_city = (strpos($address_address, $city) === false ? " ".$city : '');
			}
			$marker_address .= $address_city;
			if (!empty($region)) {
				$country = $model->GetRegion((int)$region);
				if (!Jaws_Error::IsError($country)) {
					if (strpos($country['region'], " - US") !== false) {
						$country['region'] = str_replace(" - US", '', $country['region']);
					}
					if (strpos($country['region'], " - British") !== false) {
						$country['region'] = str_replace(" - British", '', $country['region']);
					}
					if (strpos($country['region'], " SAR") !== false) {
						$country['region'] = str_replace(" SAR", '', $country['region']);
					}
					if (strpos($country['region'], " - Islas Malvinas") !== false) {
						$country['region'] = str_replace(" - Islas Malvinas", '', $country['region']);
					}
					if (strpos($address_address, $country['region']) === false && strpos($address_address, $country['country_iso_code']) === false) {
						$address_region = ', '.$country['region'];
					}
				}
			}
			
			$marker_address .= $address_region;
			$key = "ABQIAAAAbHKtlYQg6w5AJ9d2_shprxRSdpoJbNiUEES6uLAQrtqeLOB2WBROsVk6Deve8XT-33SEgoVpReKw5Q";
			include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
			include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'XMLParser.php';
			// snoopy
			$snoopy = new Snoopy('Properties');
			$snoopy->agent = "Jaws";
			$geocode_url = "http://maps.google.com/maps/geo?q=".urlencode($marker_address)."&output=xml&key=".$key;
			//$geocode_url = "http://maps.google.com/maps/geo?q=Dawsonville, GA&output=xml&key=ABQIAAAAbHKtlYQg6w5AJ9d2_shprxRSdpoJbNiUEES6uLAQrtqeLOB2WBROsVk6Deve8XT-33SEgoVpReKw5Q";
			//echo '<br />Google Geocoder: '.$geocode_url;
			if($snoopy->fetch($geocode_url)) {
				$xml_content = $snoopy->results;
			
				// XML Parser
				$xml_parser = new XMLParser;
				$xml_result = $xml_parser->parse($xml_content, array("STATUS", "PLACEMARK"));
				//echo '<pre>';
				//var_dump($xml_result);
				//echo '</pre>';
				for ($i=0;$i<$xml_result[1]; $i++) {
					//$is_totalResults = false;
					if (
						$xml_result[0][0]['CODE'] == '200' && 
						isset($xml_result[0][$i]['COUNTRYNAMECODE']) && 
						isset($xml_result[0][$i]['ADMINISTRATIVEAREANAME']) && 
						isset($xml_result[0][$i]['LOCALITYNAME']) && 
						isset($xml_result[0][$i]['ADDRESS']) && 
						isset($xml_result[0][$i]['COORDINATES']) && empty($coordinates)
					) {
						if (empty($region)) {
							//$params = array();
							//$params['is_country'] = 'N';
							//$params['country_iso_code'] = $xml_result[0][$i]['ADMINISTRATIVEAREANAME'];
							$sql = "SELECT [id] FROM [[country]] WHERE ([is_country] = 'N') AND ([country_iso_code] = '".$xml_result[0][$i]['ADMINISTRATIVEAREANAME']."')";
							$country = $GLOBALS['db']->queryOne($sql);
							if (!Jaws_Error::IsError($country) && is_numeric($country)) {
								$region = $country;
							}	
						}
						//if (isset($xml_result[0][$i]['LOCALITYNAME']) && $override_city == '') {
						if (empty($city)) {
							$city = $xml_result[0][$i]['LOCALITYNAME'];
						}
						if (empty($address)) {
							$address = $xml_result[0][$i]['ADDRESS'];
						}
						if (isset($xml_result[0][$i]['POSTALCODENUMBER']) && empty($postal_code)) {
							$postal_code = $xml_result[0][$i]['POSTALCODENUMBER'];
						}
						//if (isset($xml_result[0][$i]['COORDINATES'])) {
							$coordinates = $xml_result[0][$i]['COORDINATES'];
						//}
					}
				}
			}
		}
	   
        $params               		= array();
		$params['LinkID']       	= (int)$LinkID; 
		$params['sort_order']       = (int)$sort_order; 
		$params['category']       	= $category; 
		$params['mls']       		= $xss->parse(strip_tags($mls)); 
		$params['title']       		= $xss->parse(strip_tags($title)); 
		$params['image']       		= $xss->parse(strip_tags($image)); 
		$params['sm_description']   = $xss->parse(strip_tags($sm_description)); 
		$params['description']      = str_replace("\r\n", "\n", $description);
		$params['address']       	= $xss->parse(strip_tags($address)); 
		$params['city']       		= $xss->parse(strip_tags($city)); 
		$params['region']       	= $region; 
		$params['postal_code']      = $xss->parse(strip_tags($postal_code)); 
		$params['country_id']       = (int)$country_id; 
		$params['community']       	= $xss->parse(strip_tags($community)); 
		$params['phase']       		= $xss->parse(strip_tags($phase)); 
		$params['lotno']       		= $xss->parse(strip_tags($lotno)); 
		$params['price']       		= $price; 
		$params['rentdy']       	= $rentdy; 
		$params['rentwk']       	= $rentwk; 
		$params['rentmo']       	= $rentmo; 
		$params['status']       	= $xss->parse($status); 
		$params['acreage']       	= $xss->parse(strip_tags($acreage)); 
		$params['sqft']       		= $xss->parse(strip_tags($sqft)); 
		$params['bedroom']       	= $bedroom; 
		$params['bathroom']       	= $bathroom; 
		$params['amenity']       	= $amenity; 
		$params['i360']       		= $xss->parse(strip_tags($i360)); 
		$params['maxchildno']       = (int)$maxchildno; 
		$params['maxadultno']       = (int)$maxadultno; 
		$params['petstay']       	= $xss->parse($petstay); 
		$params['occupancy']       	= (int)$occupancy; 
		$params['maxcleanno']       = (int)$maxcleanno; 
		$params['roomcount']       	= (int)$roomcount; 
		$params['minstay']       	= (int)$minstay; 
		$params['options']       	= $xss->parse(strip_tags($options)); 
		$params['item1']       		= $xss->parse($item1); 
		$params['item2']       		= $xss->parse($item2); 
		$params['item3']       		= $xss->parse($item3); 
		$params['item4']       		= $xss->parse($item4); 
		$params['item5']       		= $xss->parse($item5); 
		$params['premium']       	= $xss->parse($premium); 
		$params['ShowMap']       	= $xss->parse($ShowMap); 
		$params['featured']       	= $xss->parse($featured); 
		$params['OwnerID']       	= $OwnerID; 
		$params['Active']       	= $xss->parse($Active); 
		$params['fast_url']       	= $xss->parse($fast_url); 
		$params['propertyno']       = (int)$propertyno; 
		$params['internal_propertyno'] = $xss->parse(strip_tags($internal_propertyno)); 
		$params['alink']       		= $xss->parse($alink); 
		$params['alinkTitle']       = $xss->parse(strip_tags($alinkTitle)); 
		$params['alinkType']       	= $xss->parse($alinkType); 
		$params['alink2']       	= $xss->parse($alink2); 
		$params['alink2Title']      = $xss->parse(strip_tags($alink2Title)); 
		$params['alink2Type']       = $xss->parse($alink2Type); 
		$params['alink3']       	= $xss->parse($alink3); 
		$params['alink3Title']      = $xss->parse(strip_tags($alink3Title)); 
		$params['alink3Type']       = $xss->parse($alink3Type);
		$params['calendar_link'] 	= $xss->parse($calendar_link); 
		$params['year']       		= $xss->parse($year); 
		$params['rss_url']       	= ($OwnerID > 0 ? $rss_url : ''); 
		$params['agent']       		= $xss->parse($agent); 
		$params['agent_email']      = $xss->parse($agent_email); 
		$params['agent_phone']      = $xss->parse($agent_phone); 
		$params['agent_website']    = $xss->parse($agent_website); 
		$params['agent_photo']      = $xss->parse($agent_photo); 
		$params['broker']       	= $xss->parse($broker); 
		$params['broker_email']     = $xss->parse($broker_email); 
		$params['broker_phone']     = $xss->parse($broker_phone); 
		$params['broker_website']   = $xss->parse($broker_website); 
		$params['broker_logo']      = $xss->parse($broker_logo);
        $params['coordinates']		= $xss->parse($coordinates);
		$params['now']        		= $GLOBALS['db']->Date();

		$result = $GLOBALS['db']->query($sql, $params);
		if (Jaws_Error::IsError($result)) {
            //return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTY_NOT_ADDED'), _t('PROPERTIES_NAME'));
            return new Jaws_Error($result->GetMessage(), _t('PROPERTIES_NAME'));
        }
        $newid = $GLOBALS['db']->lastInsertID('property', 'id');
				
		if (empty($item2)) {
			// Update checksum
			$config_key = $GLOBALS['app']->Registry->Get('/config/key');		
			$params               	= array();
			$params['id'] 			= $newid;
			$params['checksum'] 	= $newid.':'.$config_key;
			
			$sql = '
				UPDATE [[property]] SET
					[item2] = {checksum}
				WHERE [id] = {id}';

			$result = $GLOBALS['db']->query($sql, $params);
			if (Jaws_Error::IsError($result)) {
				$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
				return false;
			}
		}
		
		// Let everyone know a property has been added
        $GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
        $res = $GLOBALS['app']->Shouter->Shout('onAddProperty', $newid);
        if (Jaws_Error::IsError($res) || !$res) {
			return $res;
        }
		
		if ($auto === false) {
			$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_PROPERTY_CREATED'), RESPONSE_NOTICE);
        }
		return $newid;
    }

    /**
     * Updates a property.
     *
     * @param   int     $id             The ID of the property to update.
     * @param   int  $LinkID 	ID of property category
     * @param   int  $sort_order 	The priority order
     * @param   string  $category 	Comma separated list of categories
     * @param   string  $mls      		MLS number of property
     * @param   string  $title      		The title of the post.
     * @param   string  $image   		An image to accompany the post or the gadget "layout action"
     * @param   string  $sm_description  The summary of the post.
     * @param   string  $description    	The contents of the post.
     * @param   string  $address    	Address of property
     * @param   string  $city    	City of property
     * @param   string  $region    	State/province/region of property
     * @param   string  $postal_code    	Postal code of property
     * @param   int  $country_id    	Country ID of property (country DB table)
     * @param   string  $community    	Community of property
     * @param   string  $phase    	Phase of property
     * @param   string  $lotno    	Lot number of property
     * @param   string  $price    	Purchase price of property
     * @param   string  $rentdy     Daily rent price of property
     * @param   string  $rentwk    	Weekly rent price of property
     * @param   string  $rentmo    	Monthly rent price of property
     * @param   string  $status    	Status of property (forrent/forsale/undercontract/rented/sold)
     * @param   string  $acreage    	Acreage of property
     * @param   string  $sqft    	Square footage of property
     * @param   string  $bedroom    	Number of bedrooms on property
     * @param   string  $bathroom    	Number of bathrooms on property
     * @param   string  $amenity    	Comma separated list of amenity IDs of property
     * @param   string  $i360    	Virtual Tour URL for property
     * @param   int  $maxchildno    	Maximum number of children allowed on property
     * @param   int  $maxadultno    	Maximum number of adults allowed on property
     * @param   string  $petstay    	(Y/N) Can pets stay on property?
     * @param   int  $occupancy    	Maximum occupancy on property
     * @param   int  $maxcleanno    	Maximum occupancy before cleaning fee applies to property
     * @param   int  $roomcount    	Number of rooms on property
     * @param   int  $minstay    	Minimum number of nights required to rent property
     * @param   string  $options    	Property options
     * @param   string  $item1    	Not used
     * @param   string  $item2    	Unique ID
     * @param   string  $item3    	Not used
     * @param   string  $item4    	Not used
     * @param   string  $item5    	Not used
     * @param   string  $premium    	(Y/N) Is this featured?
     * @param   string  $ShowMap    	(Y/N) Show property on map?
     * @param   string  $featured    	(Y/N) Can this property be shown on site-wide featured areas?
     * @param   string 	$Active  		(Y/N) If the post is published or not
     * @param   int 	$propertyno  		Auto-generated property number
     * @param   string 	$internal_propertyno  		Internal property number
     * @param   string 	$alink  		Hyperlink URL
     * @param   string 	$alinkTitle  		Hyperlink Title
     * @param   string 	$alinkType  		Hyperlink Type (_self/_blank/mail)
     * @param   string 	$alink2  		Hyperlink URL
     * @param   string 	$alink2Title  		Hyperlink Title
     * @param   string 	$alink2Type  		Hyperlink Type (_self/_blank/mail)
     * @param   string 	$alink3  		Hyperlink URL
     * @param   string 	$alink3Title  		Hyperlink Title
     * @param   string 	$alink3Type  		Hyperlink Type (_self/_blank/mail)
     * @param   string 	$calendar_link  		Availability calendar URL
     * @param   string 	$year  		Year built
     * @param   string 	$rss_url  		RSS URL
     * @param   string 	$agent  		Property's agent name
     * @param   string 	$agent_email  		Property's agent email address
     * @param   string 	$agent_phone  		Property's agent telephone
     * @param   string 	$agent_website  		Property's agent website
     * @param   string 	$agent_photo  		Property's agent photo URL
     * @param   string 	$broker  		Property's broker name
     * @param   string 	$broker_email  		Property's broker email
     * @param   string 	$broker_phone  		Property's broker telephone
     * @param   string 	$broker_website  		Property's broker website
     * @param   string 	$broker_logo  		Property's broker logo
     * @param   string 	$coordinates  		Comma separated list of longitude,latitude
     * @param   boolean 	$auto       		If it's auto saved or not
     * @access  public
     * @return  boolean Success/failure
     */
    function UpdateProperty(
		$id, $LinkID, $sort_order, $category, $mls, $title, $image, 
		$sm_description, $description, $address, $city, $region, $postal_code, $country_id, 
		$community, $phase, $lotno, $price, $rentdy, $rentwk, $rentmo, $status, $acreage, 
		$sqft, $bedroom, $bathroom, $amenity, $i360, $maxchildno, $maxadultno, $petstay, 
		$occupancy, $maxcleanno, $roomcount, $minstay, $options, $item1, $item2, $item3, 
		$item4, $item5, $premium, $ShowMap, $featured, 
		$Active, $propertyno, $internal_propertyno, $alink, $alinkTitle, $alinkType, $alink2, $alink2Title, 
		$alink2Type, $alink3, $alink3Title, $alink3Type, $calendar_link, $year, $rss_url, 
		$agent, $agent_email, $agent_phone, $agent_website, $agent_photo, $broker, 
		$broker_email, $broker_phone, $broker_website, $broker_logo, $coordinates, $auto = false
	) {
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$model = $GLOBALS['app']->LoadGadget('Properties', 'Model');

		// Get the fast url
        $fast_url = !empty($title) ? $title : $address;
		$fast_url = strip_tags($GLOBALS['app']->UTF8->str_replace(',', '', $fast_url));
        $fast_url = $this->GetRealFastUrl(
			$fast_url, 'property', true, 'fast_url', 'id', $id
		);
		
        $page = $model->GetProperty($id);
        if (Jaws_Error::isError($page)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_PROPERTY_NOT_FOUND'), RESPONSE_ERROR);
            return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTY_NOT_FOUND'), _t('PROPERTIES_NAME'));
        } else {
			
			$categories = explode(',', $page['category']);
			foreach ($categories as $pid) {
				if ((int)$pid != 0) {
					$properties = $model->GetAllPropertiesOfParent((int)$pid);
					if (Jaws_Error::isError($properties)) {
						$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_PROPERTIES_NOT_RETRIEVED'), RESPONSE_ERROR);
						return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTIES_NOT_RETRIEVED'), _t('PROPERTIES_NAME'));
					}
					$hasChildren = false;
					foreach ($properties as $property) {
						if (isset($property['id']) && !empty($property['id'])) {
							$hasChildren = true;
						}
					}
					
					if ($hasChildren === false) {
						$parent = $model->GetPropertyParent((int)$pid);
						if (Jaws_Error::IsError($parent)) {
							$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_PROPERTY_NOT_DELETED'), RESPONSE_ERROR);
							return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTY_NOT_DELETED'), _t('PROPERTIES_NAME'));
						}
						$sql = '
							UPDATE [[propertyparent]] SET
								[propertyparentactive] = {Active}, 
								[propertyparentupdated] = {now}
							WHERE [propertyparentid] = {id}';

						$params               		= array();
						$params['id']         		= (int)$pid;
						$params['Active']       	= 'N'; 
						$params['now']        		= $GLOBALS['db']->Date();

						$result = $GLOBALS['db']->query($sql, $params);
						if (Jaws_Error::IsError($result)) {
							$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_PROPERTYPARENT_NOT_UPDATED'), RESPONSE_ERROR);
							return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTYPARENT_NOT_UPDATED'), _t('PROPERTIES_NAME'));
						}
						
						if ($parent['propertyparentownerid'] == 0) {
							// update menu item for page, hide it
							$parentURL = '0';
							if ($parent['propertyparentparent'] > 0) {
								// get parent info
								$sql  = 'SELECT [url] FROM [[menus]] WHERE [id] = {parent}';
								$menu_res = $GLOBALS['db']->queryRow($sql, array('parent' => $parent['propertyparentparent']));
								if (Jaws_Error::IsError($menu_res)) {
									$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
									return false;
								} else {
									$parentURL = (isset($menu_res['url']) ? $menu_res['url'] : '0');
								}
							}
							
							$visible = 0;
							// if old title is different, update menu item
							$old_url = $GLOBALS['app']->Map->GetURLFor('Properties', 'Category', array('id' => $parent['propertyparentfast_url']));
							$new_url = $GLOBALS['app']->Map->GetURLFor('Properties', 'Category', array('id' => $parent['propertyparentfast_url']));

							$parentid = 0;
							// get parent menus
							if ($parentURL != '0') {
								$sql  = 'SELECT [id] FROM [[menus]] WHERE [url] = {parent}';
								$parentMenu = $GLOBALS['db']->queryRow($sql, array('parent' => $parentURL));
								if (Jaws_Error::IsError($parentMenu)) {
									$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
									return false;
								} else {
									$parentid = (isset($parentMenu['id']) ? $parentMenu['id'] : $parentid);
								}
							}
							
							$sql  = 'SELECT [id], [rank] FROM [[menus]] WHERE [url] = {url}';
							$oid = $GLOBALS['db']->queryRow($sql, array('url' => $old_url));
							if (Jaws_Error::IsError($oid)) {
								//$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
								$GLOBALS['app']->Session->PushLastResponse($oid->GetMessage(), RESPONSE_ERROR);
								return $oid;
							} else if (!empty($oid['id']) && isset($oid['id'])) {
								$menuAdmin = $GLOBALS['app']->LoadGadget('Menu', 'AdminModel');
								if (
									!$menuAdmin->UpdateMenu(
										$oid['id'], 
										$parentid, 
										1, 
										'Properties', 
										$parent['propertyparentcategory_name'], 
										$new_url, 
										0, 
										$oid['rank'], 
										$visible
									)
								) {
									//$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
									$GLOBALS['app']->Session->PushLastResponse($menuAdmin->GetMessage(), RESPONSE_ERROR);
									return $menuAdmin;
								}
							} else {
								$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_PROPERTYPARENT_NOT_UPDATED'), RESPONSE_ERROR);
								return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTYPARENT_NOT_UPDATED'), _t('PROPERTIES_NAME'));
							}
							
							/*
							$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
							$res = $GLOBALS['app']->Shouter->Shout('onDeleteMenuItem', $url);
							if (Jaws_Error::IsError($res) || !$res) {
								return $res;
							}
							*/
						}
					}
				}
			}
		
			

			if (BASE_SCRIPT != 'index.php' && $auto === false && trim($rss_url) == '') {
				$description = strip_tags($description, '<p><a><b><em><i><strong><div><table><tbody><thead><tr><td><span><font><hr><br><marquee><ul><li>');
			} else if (trim($rss_url) != '') {
				$description = strip_tags($description, '<p><a><img><b><em><i><strong><div><table><tbody><thead><tr><td><span><font><hr><br><marquee><ul><li>');
			} else {
				if (
					(strlen(strip_tags($description)) > $GLOBALS['app']->Registry->Get('/gadgets/Properties/user_desc_char_limit')) && 
					($GLOBALS['app']->Registry->Get('/gadgets/Properties/user_desc_char_limit') > 0)
				) {
					$description = substr(strip_tags($description), 0, $GLOBALS['app']->Registry->Get('/gadgets/Properties/user_desc_char_limit'));
				} else {
					$description = strip_tags($description, '<p><b><em><i><strong><div><table><tbody><thead><tr><td><span><font><hr><br><marquee><ul><li>');
				}
			}
			
			// Status
			$checked = $GLOBALS['app']->Registry->Get('/gadgets/Properties/user_status_limit');
			$checked = explode(",",$checked);
			$status_types = '';
			foreach ($checked as $check) {
				$status_types .= (!empty($status_types) ? ', ' : '')._t('PROPERTIES_STATUS_'.strtoupper($check));
			}
			if (!is_null($OwnerID) && (!in_array($status, $checked))) {
				return new Jaws_Error(_t('PROPERTIES_ERROR_USER_STATUS_LIMIT', $status_types), _t('PROPERTIES_NAME'));
			}

			// Format price
			if (!empty($price)) {
				$newstring = "";
				$array = str_split($price);
				foreach($array as $char) {
					if (($char >= '0' && $char <= '9') || $char == '.') {
						$newstring .= $char;
					}
				}
				$price = number_format($newstring, 2, '.', '');
			}
			// Price limits
			if (!is_null($OwnerID)) {
				if (
					($price > number_format($GLOBALS['app']->Registry->Get('/gadgets/Properties/user_max_price'), 2, '.', '')) && 
					($GLOBALS['app']->Registry->Get('/gadgets/Properties/user_max_price') > 0)
				) {
					return new Jaws_Error(_t('PROPERTIES_ERROR_MAXPRICE_LIMIT', '$'.number_format($GLOBALS['app']->Registry->Get('/gadgets/Properties/user_max_price'), 2, '.', ',')), _t('PROPERTIES_NAME'));
				}
				if (
					($price < number_format($GLOBALS['app']->Registry->Get('/gadgets/Properties/user_min_price'), 2, '.', '')) && 
					($GLOBALS['app']->Registry->Get('/gadgets/Properties/user_min_price') > 0)
				) {
					return new Jaws_Error(_t('PROPERTIES_ERROR_MINPRICE_LIMIT', '$'.number_format($GLOBALS['app']->Registry->Get('/gadgets/Properties/user_min_price'), 2, '.', ',')), _t('PROPERTIES_NAME'));
				}
			}
			// Format bedroom
			if (!empty($bedroom)) {
				$newstring = "";
				$array = str_split($bedroom);
				foreach($array as $char) {
					if (($char >= '0' && $char <= '9') || $char == '.') {
						$newstring .= $char;
					}
				}
				$bedroom = number_format($newstring, 1, '.', '');
			} else {
				$bedroom = 0;
			}
			// Format bathroom
			if (!empty($bathroom)) {
				$newstring = "";
				$array = str_split($bathroom);
				foreach($array as $char) {
					if (($char >= '0' && $char <= '9') || $char == '.') {
						$newstring .= $char;
					}
				}
				$bathroom = number_format($newstring, 1, '.', '');
			} else {
				$bathroom = 0;
			}

			// Format rental rates
			if (!empty($rentdy)) {
				$newstring = "";
				$array = str_split($rentdy);
				foreach($array as $char) {
					if (($char >= '0' && $char <= '9') || $char == '.') {
						$newstring .= $char;
					}
				}
				$rentdy = number_format($newstring, 2, '.', '');
			}
			if (!empty($rentwk)) {
				$newstring = "";
				$array = str_split($rentwk);
				foreach($array as $char) {
					if (($char >= '0' && $char <= '9') || $char == '.') {
						$newstring .= $char;
					}
				}
				$rentwk = number_format($newstring, 2, '.', '');
			}
			if (!empty($rentmo)) {
				$newstring = "";
				$array = str_split($rentmo);
				foreach($array as $char) {
					if (($char >= '0' && $char <= '9') || $char == '.') {
						$newstring .= $char;
					}
				}
				$rentmo = number_format($newstring, 2, '.', '');
			}

			// Try to geocode an address, if coordinates weren't given
			if (!empty($address) || (!empty($region) && !empty($city))) {
				// build address
				$coordinates = '';
				$address_region = '';
				$address_city = '';
				$address_address = (!empty($address) ? $address : '');
				
				$marker_address = $address_address;
				if (!empty($city)) {
					$address_city = (strpos($address_address, $city) === false ? " ".$city : '');
				}
				$marker_address .= $address_city;
				if (!empty($region)) {
					$country = $model->GetRegion((int)$region);
					if (!Jaws_Error::IsError($country)) {
						if (strpos($country['region'], " - US") !== false) {
							$country['region'] = str_replace(" - US", '', $country['region']);
						}
						if (strpos($country['region'], " - British") !== false) {
							$country['region'] = str_replace(" - British", '', $country['region']);
						}
						if (strpos($country['region'], " SAR") !== false) {
							$country['region'] = str_replace(" SAR", '', $country['region']);
						}
						if (strpos($country['region'], " - Islas Malvinas") !== false) {
							$country['region'] = str_replace(" - Islas Malvinas", '', $country['region']);
						}
						if (strpos($address_address, $country['region']) === false && strpos($address_address, $country['country_iso_code']) === false) {
							$address_region = ', '.$country['region'];
						}
					}
				}
				
				$marker_address .= $address_region;
				$key = "ABQIAAAAbHKtlYQg6w5AJ9d2_shprxRSdpoJbNiUEES6uLAQrtqeLOB2WBROsVk6Deve8XT-33SEgoVpReKw5Q";
				include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
				include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'XMLParser.php';
				// snoopy
				$snoopy = new Snoopy('Properties');
				$snoopy->agent = "Jaws";
				$geocode_url = "http://maps.google.com/maps/geo?q=".urlencode($marker_address)."&output=xml&key=".$key;
				//echo '<br />Google Geocoder: '.$geocode_url;
				if($snoopy->fetch($geocode_url)) {
					$xml_content = $snoopy->results;
				
					// XML Parser
					$xml_parser = new XMLParser;
					$xml_result = $xml_parser->parse($xml_content, array("STATUS", "PLACEMARK"));
					//echo '<pre>';
					//var_dump($xml_result);
					//echo '</pre>';
					for ($i=0;$i<$xml_result[1]; $i++) {
						//$is_totalResults = false;
						if (
							$xml_result[0][0]['CODE'] == '200' && 
							isset($xml_result[0][$i]['COUNTRYNAMECODE']) && 
							isset($xml_result[0][$i]['ADMINISTRATIVEAREANAME']) && 
							isset($xml_result[0][$i]['LOCALITYNAME']) && 
							isset($xml_result[0][$i]['ADDRESS']) && 
							isset($xml_result[0][$i]['COORDINATES']) && 
							empty($coordinates)
						) {
							if (empty($region)) {
							//$params = array();
							//$params['is_country'] = 'N';
							//$params['country_iso_code'] = $xml_result[0][$i]['ADMINISTRATIVEAREANAME'];
								$sql = "SELECT [id] FROM [[country]] WHERE ([is_country] = 'N') AND ([country_iso_code] = '".$xml_result[0][$i]['ADMINISTRATIVEAREANAME']."')";
								$country = $GLOBALS['db']->queryOne($sql);
								if (!Jaws_Error::IsError($country) && is_numeric($country)) {
									$region = $country;
								}	
							}
							//if (isset($xml_result[0][$i]['LOCALITYNAME']) && $override_city == '') {
							if (empty($city)) {
								$city = $xml_result[0][$i]['LOCALITYNAME'];
							}
							if (empty($address)) {
								$address = $xml_result[0][$i]['ADDRESS'];
							}
							if (isset($xml_result[0][$i]['POSTALCODENUMBER']) && empty($postal_code)) {
								$postal_code = $xml_result[0][$i]['POSTALCODENUMBER'];
							}
							//if (isset($xml_result[0][$i]['COORDINATES'])) {
								$coordinates = $xml_result[0][$i]['COORDINATES'];
							//}
						}
					}
				}
			}

			if (!empty($image)) {
				$image = $this->cleanImagePath($image);
				if (
					$page['ownerid'] > 0 && 
					(substr(strtolower(trim($image)), 0, 4) == 'http' || 
					substr(strtolower(trim($image)), 0, 2) == '//' || 
					substr(strtolower(trim($image)), 0, 2) == '\\\\')
				) {
					$image = '';
				}
			}

			$sql = '
				UPDATE [[property]] SET
					[linkid] = {LinkID}, 
					[sort_order] = {sort_order}, 
					[category] = {category}, 
					[mls] = {mls}, 
					[title] = {title}, 
					[image] = {image}, 
					[sm_description] = {sm_description}, 
					[description] = {description}, 
					[address] = {address}, 
					[city] = {city}, 
					[region] = {region}, 
					[postal_code] = {postal_code}, 
					[country_id] = {country_id}, 
					[community] = {community}, 
					[phase] = {phase}, 
					[lotno] = {lotno}, 
					[price] = {price}, 
					[rentdy] = {rentdy}, 
					[rentwk] = {rentwk}, 
					[rentmo] = {rentmo}, 
					[status] = {status}, 
					[acreage] = {acreage}, 
					[sqft] = {sqft}, 
					[bedroom] = {bedroom}, 
					[bathroom] = {bathroom}, 
					[amenity] = {amenity}, 
					[i360] = {i360}, 
					[maxchildno] = {maxchildno}, 
					[maxadultno] = {maxadultno}, 
					[petstay] = {petstay}, 
					[occupancy] = {occupancy}, 
					[maxcleanno] = {maxcleanno}, 
					[roomcount] = {roomcount}, 
					[minstay] = {minstay}, 
					[options] = {options}, 
					[item1] = {item1}, 
					[item2] = {item2}, 
					[item3] = {item3}, 
					[item4] = {item4}, 
					[item5] = {item5}, 
					[premium] = {premium}, 
					[showmap] = {ShowMap}, 
					[featured] = {featured}, 
					[active] = {Active}, 
					[fast_url] = {fast_url}, 
					[propertyno] = {propertyno}, 
					[internal_propertyno] = {internal_propertyno},
					[alink] = {alink}, 
					[alinktitle] = {alinkTitle}, 
					[alinktype]	= {alinkType}, 
					[alink2] = {alink2}, 
					[alink2title] = {alink2Title}, 
					[alink2type] = {alink2Type}, 
					[alink3] = {alink3}, 
					[alink3title] = {alink3Title}, 
					[alink3type] = {alink3Type},
					[updated] = {now},
					[calendar_link] = {calendar_link}, 
					[year] = {year}, 
					[rss_url] = {rss_url}, 
					[agent] = {agent}, 
					[agent_email] = {agent_email}, 
					[agent_phone] = {agent_phone}, 
					[agent_website] = {agent_website}, 
					[agent_photo] = {agent_photo}, 
					[broker] = {broker}, 
					[broker_email] = {broker_email}, 
					[broker_phone] = {broker_phone}, 
					[broker_website] = {broker_website}, 
					[broker_logo] = {broker_logo},
					[coordinates] = {coordinates}
				WHERE [id] = {id}';
			
			$params               		= array();
			$params['id']         		= (int)$id;
			$params['LinkID']       	= (int)$LinkID; 
			$params['sort_order']       = (int)$sort_order; 
			$params['category']       	= $category; 
			$params['mls']       		= $xss->parse(strip_tags($mls)); 
			$params['title']       		= $xss->parse(strip_tags($title)); 
			$params['image']       		= $xss->parse(strip_tags($image)); 
			$params['sm_description']   = $xss->parse(strip_tags($sm_description)); 
			$params['description']      = str_replace("\r\n", "\n", $description);
			$params['address']       	= $xss->parse(strip_tags($address)); 
			$params['city']       		= $xss->parse(strip_tags($city)); 
			$params['region']       	= $region; 
			$params['postal_code']      = $xss->parse(strip_tags($postal_code)); 
			$params['country_id']       = (int)$country_id; 
			$params['community']       	= $xss->parse(strip_tags($community)); 
			$params['phase']       		= $xss->parse(strip_tags($phase)); 
			$params['lotno']       		= $xss->parse(strip_tags($lotno)); 
			$params['price']       		= $price; 
			$params['rentdy']       	= $rentdy; 
			$params['rentwk']       	= $rentwk; 
			$params['rentmo']       	= $rentmo; 
			$params['status']       	= $xss->parse($status); 
			$params['acreage']       	= $xss->parse(strip_tags($acreage)); 
			$params['sqft']       		= $xss->parse(strip_tags(($sqft))); 
			$params['bedroom']       	= $bedroom; 
			$params['bathroom']       	= $bathroom; 
			$params['amenity']       	= $amenity; 
			$params['i360']       		= $xss->parse(strip_tags($i360)); 
			$params['maxchildno']       = (int)$maxchildno; 
			$params['maxadultno']       = (int)$maxadultno; 
			$params['petstay']       	= $xss->parse($petstay); 
			$params['occupancy']       	= (int)$occupancy; 
			$params['maxcleanno']       = (int)$maxcleanno; 
			$params['roomcount']       	= (int)$roomcount; 
			$params['minstay']       	= (int)$minstay; 
			$params['options']       	= $xss->parse(strip_tags($options)); 
			$params['item1']       		= $xss->parse($item1); 
			$params['item2']       		= $xss->parse($item2); 
			$params['item3']       		= $xss->parse($item3); 
			$params['item4']       		= $xss->parse($item4); 
			$params['item5']       		= $xss->parse($item5); 
			$params['premium']       	= $xss->parse($premium); 
			$params['ShowMap']       	= $xss->parse($ShowMap); 
			$params['featured']       	= $xss->parse($featured); 
			$params['OwnerID']       	= $OwnerID; 
			$params['Active']       	= $xss->parse($Active); 
			$params['fast_url']       	= $xss->parse($fast_url); 
			$params['propertyno']       = (int)$propertyno; 
			$params['internal_propertyno'] = $xss->parse(strip_tags($internal_propertyno)); 
			$params['alink']       		= $xss->parse($alink); 
			$params['alinkTitle']       = $xss->parse(strip_tags($alinkTitle)); 
			$params['alinkType']       	= $xss->parse($alinkType); 
			$params['alink2']       	= $xss->parse($alink2); 
			$params['alink2Title']      = $xss->parse(strip_tags($alink2Title)); 
			$params['alink2Type']       = $xss->parse($alink2Type); 
			$params['alink3']       	= $xss->parse($alink3); 
			$params['alink3Title']      = $xss->parse(strip_tags($alink3Title)); 
			$params['alink3Type']       = $xss->parse($alink3Type);
			$params['calendar_link'] 	= $xss->parse($calendar_link); 
			$params['year']       		= $xss->parse($year); 
			$params['rss_url']       	= ($OwnerID > 0 ? $rss_url : ''); 
			$params['agent']       		= $xss->parse($agent); 
			$params['agent_email']      = $xss->parse($agent_email); 
			$params['agent_phone']      = $xss->parse($agent_phone); 
			$params['agent_website']    = $xss->parse($agent_website); 
			$params['agent_photo']      = $xss->parse($agent_photo); 
			$params['broker']       	= $xss->parse($broker); 
			$params['broker_email']     = $xss->parse($broker_email); 
			$params['broker_phone']     = $xss->parse($broker_phone); 
			$params['broker_website']   = $xss->parse($broker_website); 
			$params['broker_logo']      = $xss->parse($broker_logo);
			$params['coordinates']		= $xss->parse($coordinates);
			$params['now']        		= $GLOBALS['db']->Date();

			$result = $GLOBALS['db']->query($sql, $params);
			if (Jaws_Error::IsError($result)) {
				return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTY_NOT_UPDATED'), _t('PROPERTIES_NAME'));
			}

			// Let everyone know a property has been updated
			$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
			$res = $GLOBALS['app']->Shouter->Shout('onUpdateProperty', $id);
			if (Jaws_Error::IsError($res) || !$res) {
				return $res;
			}
			
			/*
			if ($auto) {
				$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_PROPERTY_AUTOUPDATED',
														 date('H:i:s'),
														 (int)$id,
														 date('D, d')),
													  RESPONSE_NOTICE);
			} else {
			*/	
			if ($auto === false) {
				$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_PROPERTY_UPDATED'), RESPONSE_NOTICE);
			}
			return true;
		}
    }


    /**
     * Deletes a property
     *
     * @param   int     $id     The ID of the property to delete.
     * @param   boolean     $massive     Is this part of a massive delete?
     * @access  public
     * @return  bool    Success/failure
     */
    function DeleteProperty($id, $massive = false)
    {
		$model = $GLOBALS['app']->LoadGadget('Properties', 'Model');
		$page = $model->GetProperty($id);
		if (Jaws_Error::IsError($page)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_PROPERTY_NOT_DELETED'), RESPONSE_ERROR);
			return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTY_NOT_DELETED'), _t('PROPERTIES_NAME'));
		} else {
			// Let everyone know
			$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
			$res = $GLOBALS['app']->Shouter->Shout('onDeleteProperty', $id);
			if (Jaws_Error::IsError($res) || !$res) {
				return $res;
			}
					
			// Delete property posts
			$oids = $model->GetAllPostsOfProperty($id);
			if (Jaws_Error::IsError($oids)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_POST_NOT_DELETED'), RESPONSE_ERROR);
				return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTYPARENT_NOT_DELETED'), _t('PROPERTIES_NAME'));
			}
			foreach ($oids as $oid) {
				if (!$this->DeletePost($oid['id'], true)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_POST_NOT_DELETED'), RESPONSE_ERROR);
					return new Jaws_Error(_t('PROPERTIES_ERROR_POST_NOT_DELETED'), _t('PROPERTIES_NAME'));
				}
			}
			/*
			// Delete property reservation rates
			$rids = $model->GetAllResratesOfProperty($id);
			if (Jaws_Error::IsError($rids)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_RESRATE_NOT_DELETED'), RESPONSE_ERROR);
				return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTYPARENT_NOT_DELETED'), _t('PROPERTIES_NAME'));
			}
			foreach ($rids as $rid) {
				if (!$this->DeleteResrate($rid['id'])) {
					$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_RESRATE_NOT_DELETED'), RESPONSE_ERROR);
					return new Jaws_Error(_t('PROPERTIES_ERROR_RESRATE_NOT_DELETED'), _t('PROPERTIES_NAME'));
				}
			}
			*/

			$sql = "
				DELETE FROM [[properties_parents]]
					WHERE ([prop_id] = {prop_id})";
			
			$params               		= array();
			$params['prop_id']        	= $id;

			$result = $GLOBALS['db']->query($sql, $params);
			if (Jaws_Error::IsError($result)) {
				return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTYPARENT_NOT_UNLINKED'), _t('PROPERTIES_NAME'));
				//return new Jaws_Error($result->GetMessage(), _t('PROPERTIES_NAME'));
			}
			
			$categories = explode(',', $page['category']);
			foreach ($categories as $pid) {
				if ((int)$pid != 0) {
					$properties = $model->GetAllPropertiesOfParent((int)$pid);
					if (Jaws_Error::isError($properties)) {
						$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_PROPERTIES_NOT_RETRIEVED'), RESPONSE_ERROR);
						return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTIES_NOT_RETRIEVED'), _t('PROPERTIES_NAME'));
					}
					$hasChildren = false;
					foreach ($properties as $property) {
						if (isset($property['id']) && !empty($property['id'])) {
							$hasChildren = true;
						}
					}
					
					if ($hasChildren === false) {
						$parent = $model->GetPropertyParent((int)$pid);
						if (Jaws_Error::IsError($parent)) {
							$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_PROPERTY_NOT_DELETED'), RESPONSE_ERROR);
							return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTY_NOT_DELETED'), _t('PROPERTIES_NAME'));
						}
						$sql = '
							UPDATE [[propertyparent]] SET
								[propertyparentactive] = {Active}, 
								[propertyparentupdated] = {now}
							WHERE [propertyparentid] = {id}';

						$params               		= array();
						$params['id']         		= (int)$pid;
						$params['Active']       	= 'N'; 
						$params['now']        		= $GLOBALS['db']->Date();

						$result = $GLOBALS['db']->query($sql, $params);
						if (Jaws_Error::IsError($result)) {
							$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_PROPERTYPARENT_NOT_UPDATED'), RESPONSE_ERROR);
							return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTYPARENT_NOT_UPDATED'), _t('PROPERTIES_NAME'));
						}
						
						if ($parent['propertyparentownerid'] == 0) {
							// update menu item for page, hide it
							$parentURL = '0';
							if ($parent['propertyparentparent'] > 0) {
								// get parent info
								$sql  = 'SELECT [url] FROM [[menus]] WHERE [id] = {parent}';
								$menu_res = $GLOBALS['db']->queryRow($sql, array('parent' => $parent['propertyparentparent']));
								if (Jaws_Error::IsError($menu_res)) {
									$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
									return false;
								} else {
									$parentURL = (isset($menu_res['url']) ? $menu_res['url'] : '0');
								}
							}
							
							$visible = 0;
							// if old title is different, update menu item
							$old_url = $GLOBALS['app']->Map->GetURLFor('Properties', 'Category', array('id' => $parent['propertyparentfast_url']));
							$new_url = $GLOBALS['app']->Map->GetURLFor('Properties', 'Category', array('id' => $parent['propertyparentfast_url']));

							$parentid = 0;
							// get parent menus
							if ($parentURL != '0') {
								$sql  = 'SELECT [id] FROM [[menus]] WHERE [url] = {parent}';
								$parentMenu = $GLOBALS['db']->queryRow($sql, array('parent' => $parentURL));
								if (Jaws_Error::IsError($parentMenu)) {
									$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
									return false;
								} else {
									$parentid = (isset($parentMenu['id']) ? $parentMenu['id'] : $parentid);
								}
							}
							
							$sql  = 'SELECT [id], [rank] FROM [[menus]] WHERE [url] = {url}';
							$oid = $GLOBALS['db']->queryRow($sql, array('url' => $old_url));
							if (Jaws_Error::IsError($oid)) {
								//$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
								$GLOBALS['app']->Session->PushLastResponse($oid->GetMessage(), RESPONSE_ERROR);
								return $oid;
							} else if (!empty($oid['id']) && isset($oid['id'])) {
								$menuAdmin = $GLOBALS['app']->LoadGadget('Menu', 'AdminModel');
								if (
									!$menuAdmin->UpdateMenu(
										$oid['id'], 
										$parentid, 
										1, 
										'Properties', 
										$parent['propertyparentcategory_name'], 
										$new_url, 
										0, 
										$oid['rank'], 
										$visible
									)
								) {
									//$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
									$GLOBALS['app']->Session->PushLastResponse($menuAdmin->GetMessage(), RESPONSE_ERROR);
									return $menuAdmin;
								}
							} else {
								$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_PROPERTYPARENT_NOT_UPDATED'), RESPONSE_ERROR);
								return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTYPARENT_NOT_UPDATED'), _t('PROPERTIES_NAME'));
							}
							
						}
					}
				}
			}

			$sql = 'DELETE FROM [[property]] WHERE [id] = {id}';
			$result = $GLOBALS['db']->query($sql, array('id' => $id));
			if (Jaws_Error::IsError($result)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_PROPERTY_NOT_DELETED'), RESPONSE_ERROR);
				return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTY_NOT_DELETED'), _t('PROPERTIES_NAME'));
			}
			
			if ($massive === false) {
				$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_PROPERTY_DELETED'), RESPONSE_NOTICE);
			}
			return true;
		}
    }

    /**
     * Add posts and images to properties.
     *
     * @category 	feature
     * @param   int  $sort_order 	The priority order
     * @param   int  $LinkID 	ID of property this post belongs to.
     * @param   string  $title      		The title of the post.
     * @param   string  $description    	The contents of the post.
     * @param   string  $image   		An image to accompany the post or the gadget "layout action"
     * @param   int  	 $image_width     image width in pixels
     * @param   int  	 $image_height    image height in pixels
     * @param   int 	$layout  		The layout mode of the post
     * @param   string 	$active  		(Y/N) If the post is published or not
     * @param   int 	$OwnerID  		The poster's user ID
     * @param   string 	$url_type  		The URL type of post's image (imageviewer/internal/external)
     * @param   string 	$internal_url  		The internal URL of post's image
     * @param   string 	$external_url  		The external URL of post's image
     * @param   string 	$url_target  		The URL target of post's image (_self/_blank)
     * @param   string 	$checksum  		Unique ID
     * @param   boolean 	$auto       		If it's auto saved or not
     * @access  public
     * @return  mixed 	ID of new post or Jaws_Error on failure
     */
    function AddPost(
		$sort_order, $LinkID, $title, $description, $image, $image_width = 0, 
		$image_height = 0, $layout = 0, $active = 'Y', $OwnerID = null, $url_type = '', 
		$internal_url = '', $external_url = '', $url_target = '_self', $checksum = '', 
		$auto = false
	) {        
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$model = $GLOBALS['app']->LoadGadget('Properties', 'Model');
        $page = $model->GetProperty($LinkID);
        if (Jaws_Error::isError($page)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_PROPERTY_NOT_FOUND'), RESPONSE_ERROR);
            return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTY_NOT_FOUND'), _t('PROPERTIES_NAME'));
        } else {
			if (BASE_SCRIPT == 'index.php' && is_null($OwnerID)) {
				$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');
			} else {
				$OwnerID = (!is_null($OwnerID)) ? (int)$OwnerID : 0;
			}
			if (!empty($image)) {
				$image = $this->cleanImagePath($image);
				if (
					$OwnerID > 0 && 
					(substr(strtolower(trim($image)), 0, 4) == 'http' || 
					substr(strtolower(trim($image)), 0, 2) == '//' || 
					substr(strtolower(trim($image)), 0, 2) == '\\\\')
				) {
					$image = '';
				}
			}
			if (empty($page['image'])) {
				$result = $this->UpdateProperty($LinkID, $page['linkid'], $page['sort_order'], $page['category'], 
					$page['mls'], $page['title'], $image, $page['sm_description'], $page['description'], 
					$page['address'], $page['city'], $page['region'], $page['postal_code'], $page['country_id'], 
					$page['community'], $page['phase'], $page['lotno'], $page['price'], $page['rentdy'], $page['rentwk'], 
					$page['rentmo'], $page['status'], $page['acreage'], $page['sqft'], $page['bedroom'], $page['bathroom'], 
					$page['amenity'], $page['i360'], $page['maxchildno'], $page['maxadultno'], $page['petstay'], 
					$page['occupancy'], $page['maxcleanno'], $page['roomcount'], $page['minstay'], $page['options'], 
					$page['item1'], $page['item2'], $page['item3'], $page['item4'], $page['item5'], $page['premium'], 
					$page['showmap'], $page['featured'], $page['active'], $page['propertyno'], $page['internal_propertyno'], 
					$page['alink'], $page['alinktitle'], $page['alinktype'], $page['alink2'], 
					$page['alink2title'], $page['alink2type'], $page['alink3'], $page['alink3title'], $page['alink3type'], 
					$page['calendar_link'], $page['year'], $page['rss_url'], $page['agent'], $page['agent_email'], $page['agent_phone'], 
					$page['agent_website'], $page['agent_photo'], $page['broker'], $page['broker_email'], $page['broker_phone'], 
					$page['broker_website'], $page['broker_logo'], $page['coordinates']);
				if (Jaws_Error::IsError($result)) {
					$GLOBALS['app']->Session->PushLastResponse($result->getMessage(), RESPONSE_ERROR);
					return new Jaws_Error($result->getMessage(), _t('PROPERTIES_NAME'));
				} else {
					$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_POST_CREATED'), RESPONSE_NOTICE);
					return true;
				}
			} else {
				$user_post_limit = $GLOBALS['app']->Registry->Get('/gadgets/Properties/user_post_limit');
				$user_post_limit = (int)$user_post_limit;
				if ($OwnerID > 0 && $user_post_limit > 0) {
					$posts = $model->GetAllPostsOfProperty($LinkID);
					if (!Jaws_Error::IsError($posts)) {
						$i = 0;
						foreach($posts as $post) {		            
							$i++;
						}
					} else {
						$GLOBALS['app']->Session->PushLastResponse($posts->getMessage(), RESPONSE_ERROR);
						return new Jaws_Error($posts->getMessage(), _t('PROPERTIES_NAME'));
					}

					if (($i+1) >= $user_post_limit) {
						return new Jaws_Error(_t('PROPERTIES_ERROR_POST_LIMIT_REACHED'), _t('PROPERTIES_NAME'));
					}
				}
				$url = "javascript:void(0);";

				$sql = "
					INSERT INTO [[property_posts]]
						([sort_order], [linkid], [title], 
						[description], [image], [image_width], [image_height], 
						[layout], [active], [ownerid], [created], [updated],
						[url], [url_target], [checksum])
					VALUES
						({sort_order}, {LinkID}, {title}, 
						{description}, {image}, {image_width}, {image_height},
						{layout}, {Active}, {OwnerID}, {now}, {now},
						{url}, {url_target}, {checksum})";

				  
				$description = strip_tags($description, '<p><a><b><em><i><strong><div><table><tbody><thead><tr><td><span><font><hr><br>');
				$params               		= array();
				$params['sort_order']       = (int)$sort_order;
				$params['title'] 			= $xss->parse($title);
				$params['description']   	= str_replace("\r\n", "\n", $description);
				$params['image'] 			= $xss->parse($image);
				$params['image_width'] 		= (int)$image_width;
				$params['image_height'] 	= (int)$image_height;
				$params['layout'] 			= (int)$layout;
				$params['LinkID']         	= (int)$LinkID;
				$params['OwnerID']         	= $OwnerID;
				$params['Active'] 			= $xss->parse($active);
				$params['url']				= $url;
				$params['url_target']		= $xss->parse($url_target);
				$params['checksum']			= $xss->parse($checksum);
				$params['now']        		= $GLOBALS['db']->Date();

				$result = $GLOBALS['db']->query($sql, $params);
				if (Jaws_Error::IsError($result)) {
					return new Jaws_Error(_t('PROPERTIES_ERROR_POST_NOT_ADDED'), _t('PROPERTIES_NAME'));
				}
				$newid = $GLOBALS['db']->lastInsertID('property_posts', 'id');

				if (empty($checksum)) {
					// Update checksum
					$config_key = $GLOBALS['app']->Registry->Get('/config/key');		
					$params               	= array();
					$params['id'] 			= $newid;
					$params['checksum'] 	= $newid.':'.$config_key;
					
					$sql = '
						UPDATE [[property_posts]] SET
							[checksum] = {checksum}
						WHERE [id] = {id}';

					$result = $GLOBALS['db']->query($sql, $params);
					if (Jaws_Error::IsError($result)) {
						$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
						return false;
					}
				}
				
				// Let everyone know
				$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
				$res = $GLOBALS['app']->Shouter->Shout('onAddPropertyPost', $newid);
				if (Jaws_Error::IsError($res) || !$res) {
					return $res;
				}
		
				$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_POST_CREATED'), RESPONSE_NOTICE);
				return $newid;
			}
		}
    }

    /**
     * Updates a post.
     *
     * @param   int     $id             The ID of the post to update.
     * @param   int  $sort_order 	The priority order
     * @param   string  $title      		The title of the post.
     * @param   string  $description    	The contents of the post.
     * @param   string  $image   		An image to accompany the post or the gadget "layout action"
     * @param   int  	 $image_width     image width in pixels
     * @param   int  	 $image_height    image height in pixels
     * @param   int 	$layout  		The layout mode of the post
     * @param   string 	$active  		(Y/N) If the post is published or not
     * @param   string 	$url_type  		The URL type of post's image (imageviewer/internal/external)
     * @param   string 	$internal_url  		The internal URL of post's image
     * @param   string 	$external_url  		The external URL of post's image
     * @param   string 	$url_target  		The URL target of post's image (_self/_blank)
     * @param   boolean 	$auto       		If it's auto saved or not
     * @access  public
     * @return  boolean Success/failure
     */
    function UpdatePost(
		$id, $sort_order, $title, $description, $image, $image_width, $image_height, $layout, 
		$active, $url_type, $internal_url, $external_url, $url_target = '_self', $auto = false
	) {
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$model = $GLOBALS['app']->LoadGadget('Properties', 'Model');
        $page = $model->GetPost($id);
        if (Jaws_Error::isError($page)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_POST_NOT_FOUND'), RESPONSE_ERROR);
            return new Jaws_Error(_t('PROPERTIES_ERROR_POST_NOT_FOUND'), _t('PROPERTIES_NAME'));
        }

		if (!empty($image)) {
			$image = $this->cleanImagePath($image);
			if (
				$page['ownerid'] > 0 && 
				(substr(strtolower(trim($image)), 0, 4) == 'http' || 
				substr(strtolower(trim($image)), 0, 2) == '//' || 
				substr(strtolower(trim($image)), 0, 2) == '\\\\')
			) {
				$image = '';
			}
		}
		
		$url = "javascript:void(0);";
		$description = strip_tags($description, '<p><a><b><em><i><strong><div><table><tbody><thead><tr><td><span><font><hr><br><marquee><ul><li>');

		$sql = '
            UPDATE [[property_posts]] SET
				[sort_order] = {sort_order}, 
				[title] = {title}, 
				[description] = {description}, 
				[image] = {image}, 
				[image_width] = {image_width},
				[image_height] = {image_height},
				[layout] = {layout}, 
				[active] = {Active}, 
				[updated] = {now},
				[url] = {url},
				[url_target] = {url_target} 
			WHERE [id] = {id}';

        $params               	= array();
        $params['id']         	= (int)$id;
        $params['sort_order'] 	= (int)$sort_order;
        $params['title'] 		= $xss->parse($title);
		$params['description']  = str_replace("\r\n", "\n", $description);
        $params['image'] 		= $xss->parse($image);
        $params['image_width'] 	= (int)$image_width;
        $params['image_height'] = (int)$image_height;
        $params['layout'] 		= (int)$layout;
        $params['Active'] 		= $xss->parse($active);
        $params['url']			= $url;
		$params['url_target']	= $xss->parse($url_target);
        $params['now']        	= $GLOBALS['db']->Date();

        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('PROPERTIES_ERROR_POST_NOT_UPDATED'), _t('PROPERTIES_NAME'));
        }

		// Let everyone know
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onUpdatePropertyPost', $id);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		
        if ($auto) {
            $GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_POST_AUTOUPDATED',
                                                     date('H:i:s'),
                                                     (int)$id,
                                                     date('D, d')),
                                                  RESPONSE_NOTICE);
        } else {
            $GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_POST_UPDATED'), RESPONSE_NOTICE);
        }
        return true;
    }


    /**
     * Deletes a post
     *
     * @access  public
     * @param   int     $id     The ID of the post to delete.
     * @param   boolean     $massive     Is this part of a massive delete?
     * @return  boolean    Success/failure
     */
    function DeletePost($id, $massive = false)
    {
		// Let everyone know
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onDeletePropertyPost', $id);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		
        $sql = 'DELETE FROM [[property_posts]] WHERE [id] = {id}';
        $result = $GLOBALS['db']->query($sql, array('id' => $id));
        if (Jaws_Error::IsError($result)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_POST_NOT_DELETED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('PROPERTIES_ERROR_POST_NOT_DELETED'), _t('PROPERTIES_NAME'));
        }

		if ($massive === false) {
			$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_POST_DELETED'), RESPONSE_NOTICE);
        }
		return true;
    }

    /**
     * Create property amenities to assign to properties.
     *
     * @category 	feature
     * @param   string 	$feature 		The name of the amenity
     * @param   int  $typeID      	ID of the amenity type.
     * @param   string  $description      	Description of the amenity.
     * @param   int 	$OwnerID  		The poster's user ID
     * @param   string 	$Active  		(Y/N) If the post is published or not
     * @param   string 	$checksum  		Unique ID
     * @param   boolean 	$auto 	If it's auto saved or not
     * @access  public
     * @return  ID of new amenity or Jaws_Error on failure.
     */
    function AddPropertyAmenity($feature, $typeID, $description, $OwnerID = null, $Active = 'Y', $checksum = '', $auto = false)
    {
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $sql = "
            INSERT INTO [[propertyamenity]]
                ([feature], [typeid], [description], [ownerid], 
				[active], [created], [updated], [checksum])
            VALUES
                ({feature}, {typeID}, {description}, {OwnerID}, 
				{Active}, {now}, {now}, {checksum})";

		$description = strip_tags($description, '<p><a><b><em><i><strong><div><table><tbody><thead><tr><td><span><font><hr><br><marquee><ul><li>');
		$OwnerID = (!is_null($OwnerID)) ? (int)$OwnerID : 0;

        $params               		= array();
        $params['feature']      	= $xss->parse($GLOBALS['app']->UTF8->str_replace(",", "", $feature));
        $params['typeID']         	= (int)$typeID;
		$params['description']  	= str_replace("\r\n", "\n", $description);
		$params['OwnerID']         	= $OwnerID;
		$params['Active'] 			= $xss->parse($Active);
		$params['checksum'] 		= $xss->parse($checksum);
        $params['now']        		= $GLOBALS['db']->Date();

        $result = $GLOBALS['db']->query($sql, $params);
		if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTYAMENITY_NOT_ADDED'), _t('PROPERTIES_NAME'));
        }
        $newid = $GLOBALS['db']->lastInsertID('propertyamenity', 'id');

		if (empty($checksum)) {
			// Update checksum
			$config_key = $GLOBALS['app']->Registry->Get('/config/key');		
			$params               	= array();
			$params['id'] 			= $newid;
			$params['checksum'] 	= $newid.':'.$config_key;
			
			$sql = '
				UPDATE [[propertyamenity]] SET
					[checksum] = {checksum}
				WHERE [id] = {id}';

			$result = $GLOBALS['db']->query($sql, $params);
			if (Jaws_Error::IsError($result)) {
				$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
				return false;
			}
		}
				
		// Let everyone know
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onAddPropertyAmenity', $newid);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		
        $GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_PROPERTYAMENITY_CREATED'), RESPONSE_NOTICE);
        return $newid;
    }

    /**
     * Updates a amenity.
     *
     * @param   int  $id      	The ID of the amenity to update.
     * @param   string 	$feature 		The name of the amenity
     * @param   int  $typeID      	ID of the amenity type.
     * @param   string  $description      	Description of the amenity.
     * @param   string 	$Active  		(Y/N) If the post is published or not
     * @param   boolean 	$auto 	If it's auto saved or not
     * @access  public
     * @return  boolean Success/failure
     */
    function UpdatePropertyAmenity($id, $feature, $typeID, $description, $Active, $auto = false)
	{
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$model = $GLOBALS['app']->LoadGadget('Properties', 'Model');
        $page = $model->GetAmenity($id);
        if (Jaws_Error::isError($page)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_PROPERTYAMENITY_NOT_FOUND'), RESPONSE_ERROR);
            return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTYAMENITY_NOT_FOUND'), _t('PROPERTIES_NAME'));
        }

        $sql = '
            UPDATE [[propertyamenity]] SET
				[feature] = {feature}, 
				[typeid] = {typeID}, 
				[description] = {description}, 
				[active] = {Active}, 
				[updated] = {now}
			WHERE [id] = {id}';

		$description = strip_tags($description, '<p><a><b><em><i><strong><div><table><tbody><thead><tr><td><span><font><hr><br><marquee><ul><li>');

        $params               	= array();
        $params['id']         	= (int)$id;
        $params['feature']      = $xss->parse($GLOBALS['app']->UTF8->str_replace(",", "", $feature));
        $params['typeID']       = (int)$typeID;
		$params['description']  = str_replace("\r\n", "\n", $description);
		$params['Active'] 		= $xss->parse($Active);
        $params['now']        	= $GLOBALS['db']->Date();

        $result = $GLOBALS['db']->query($sql, $params);
		if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTYAMENITY_NOT_UPDATED'), _t('PROPERTIES_NAME'));
        }

		// Let everyone know
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onUpdatePropertyAmenity', $id);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		
        if ($auto) {
            $GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_PROPERTYAMENITY_AUTOUPDATED',
                                                     date('H:i:s'),
                                                     (int)$id,
                                                     date('D, d')),
                                                  RESPONSE_NOTICE);
        } else {
            $GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_PROPERTYAMENITY_UPDATED'), RESPONSE_NOTICE);
        }
        return true;
    }


    /**
     * Deletes an amenity
     *
     * @access  public
     * @param   int     $id     The ID of the amenity to delete.
     * @param   boolean     $massive     Is this part of a massive delete?
     * @return  bool    Success/failure
     */
    function DeletePropertyAmenity($id, $massive = false)
    {
		// Let everyone know
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onDeletePropertyAmenity', $id);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		
        $sql = 'DELETE FROM [[propertyamenity]] WHERE [id] = {id}';
        $result = $GLOBALS['db']->query($sql, array('id' => $id));
        if (Jaws_Error::IsError($result)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_PROPERTYAMENITY_NOT_DELETED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTYAMENITY_NOT_DELETED'), _t('PROPERTIES_NAME'));
        }

		if ($massive === false) {
			$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_PROPERTYAMENITY_DELETED'), RESPONSE_NOTICE);
        }
		return true;
    }

    /**
     * Creates a new amenity type.
     *
     * @param   string  $title      		The title of the amenity type.
     * @param   string  $description    	Description of the amenity type.
     * @param   int 	$OwnerID  		The poster's user ID
     * @param   string 	$Active  		(Y/N) If the amenity type is published or not
     * @param   string 	$checksum  		Unique ID
     * @param   boolean 	$auto 	If it's auto saved or not
     * @access  public
     * @return  ID of new amenity type or Jaws_Error on failure
     */
    function AddAmenityType($title, $description, $OwnerID = null, $Active = 'Y', $checksum = '', $auto = false)
    {
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $sql = "
            INSERT INTO [[amenity_types]]
                ([title], [description], [ownerid], [active], [created], [updated], [checksum])
            VALUES
                ({title}, {description}, {OwnerID}, {Active}, {now}, {now}, {checksum})";

		$description = strip_tags($description, '<p><a><b><em><i><strong><div><table><tbody><thead><tr><td><span><font><hr><br><marquee><ul><li>');
		$OwnerID = (!is_null($OwnerID)) ? (int)$OwnerID : 0;

        $params               		= array();
        $params['title']      		= $xss->parse($GLOBALS['app']->UTF8->str_replace(",", "", $title));
		$params['description']   	= str_replace("\r\n", "\n", $description);
		$params['OwnerID']         	= $OwnerID;
        $params['Active'] 			= $xss->parse($Active);
        $params['checksum'] 		= $xss->parse($checksum);
        $params['now']        		= $GLOBALS['db']->Date();

        $result = $GLOBALS['db']->query($sql, $params);
		if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('PROPERTIES_ERROR_AMENITYTYPE_NOT_ADDED'), _t('PROPERTIES_NAME'));
        }
        $newid = $GLOBALS['db']->lastInsertID('amenity_types', 'id');

		if (empty($checksum)) {
			// Update checksum
			$config_key = $GLOBALS['app']->Registry->Get('/config/key');		
			$params               	= array();
			$params['id'] 			= $newid;
			$params['checksum'] 	= $newid.':'.$config_key;
			
			$sql = '
				UPDATE [[amenity_types]] SET
					[checksum] = {checksum}
				WHERE [id] = {id}';

			$result = $GLOBALS['db']->query($sql, $params);
			if (Jaws_Error::IsError($result)) {
				$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
				return false;
			}
		}
				
		// Let everyone know
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onAddPropertyAmenityType', $newid);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		
        if ($auto) {
            $GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_AMENITYTYPE_AUTOCREATED',
                                                     date('H:i:s'),
                                                     (int)$newid,
                                                     date('D, d')),
                                                  RESPONSE_NOTICE);
        } else {
            $GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_AMENITYTYPE_CREATED'), RESPONSE_NOTICE);
        }
        return $newid;
    }

    /**
     * Updates an amenity type.
     *
     * @access  public
     * @param   int     $id             The ID of the amenity type to update.
     * @param   string  $title      		The title of the amenity type.
     * @param   string  $description    	Description of the amenity type.
     * @param   string 	$Active  		(Y/N) If the amenity type is published or not
     * @param   boolean 	$auto 	If it's auto saved or not
     * @return  boolean Success/failure
     */
    function UpdateAmenityType($id, $title, $description, $Active, $auto = false)
	{
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$model = $GLOBALS['app']->LoadGadget('Properties', 'Model');
        $page = $model->GetAmenityType($id);
        if (Jaws_Error::isError($page)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_AMENITYTYPE_NOT_FOUND'), RESPONSE_ERROR);
            return new Jaws_Error(_t('PROPERTIES_ERROR_AMENITYTYPE_NOT_FOUND'), _t('PROPERTIES_NAME'));
        }

        $sql = '
            UPDATE [[amenity_types]] SET
				[title] = {title}, 
				[description] = {description}, 
				[active] = {Active}, 
				[updated] = {now}
			WHERE [id] = {id}';

		$description = strip_tags($description, '<p><a><b><em><i><strong><div><table><tbody><thead><tr><td><span><font><hr><br><marquee><ul><li>');

        $params               	= array();
        $params['id']         	= (int)$id;
        $params['title']      	= $xss->parse($GLOBALS['app']->UTF8->str_replace(",", "", $title));
		$params['description']  = str_replace("\r\n", "\n", $description);
        $params['Active'] 		= $xss->parse($Active);
        $params['now']        	= $GLOBALS['db']->Date();

        $result = $GLOBALS['db']->query($sql, $params);
		if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('PROPERTIES_ERROR_AMENITYTYPE_NOT_UPDATED'), _t('PROPERTIES_NAME'));
        }

		// Let everyone know
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onUpdatePropertyAmenityType', $id);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		
        if ($auto) {
            $GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_AMENITYTYPE_AUTOUPDATED',
                                                     date('H:i:s'),
                                                     (int)$id,
                                                     date('D, d')),
                                                  RESPONSE_NOTICE);
        } else {
            $GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_AMENITYTYPE_UPDATED'), RESPONSE_NOTICE);
        }
        return true;
    }


    /**
     * Deletes an amenity type.
     *
     * @param   int     $id     The ID of the amenity to delete.
     * @param   boolean     $massive     Is this part of a massive delete?
     * @access  public
     * @return  bool    Success/failure
     */
    function DeleteAmenityType($id, $massive = false)
    {
		// Let everyone know
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onDeletePropertyAmenityType', $id);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		
		$model = $GLOBALS['app']->LoadGadget('Properties', 'Model');
		// Delete property reservation rates
		$rids = $model->GetAmenitiesOfType($id);
		if (Jaws_Error::IsError($rids)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_PROPERTYAMENITY_NOT_DELETED'), RESPONSE_ERROR);
			return new Jaws_Error(_t('PROPERTIES_ERROR_AMENITYTYPE_NOT_DELETED'), _t('PROPERTIES_NAME'));
		}
		foreach ($rids as $rid) {
			if (!$this->DeletePropertyAmenity($rid['id'], true)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_PROPERTYAMENITY_NOT_DELETED'), RESPONSE_ERROR);
				return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTYAMENITY_NOT_DELETED'), _t('PROPERTIES_NAME'));
			}
		}
        
		$sql = 'DELETE FROM [[amenity_types]] WHERE [id] = {id}';
        $result = $GLOBALS['db']->query($sql, array('id' => $id));
        if (Jaws_Error::IsError($result)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_AMENITYTYPE_NOT_DELETED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('PROPERTIES_ERROR_AMENITYTYPE_NOT_DELETED'), _t('PROPERTIES_NAME'));
        }

        if ($massive === false) {
			$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_AMENITYTYPE_DELETED'), RESPONSE_NOTICE);
        }
		return true;
    }

    /**
     * Re-sorts posts
     *
     * @param   int     $pids     ',' separated values of IDs of the posts
     * @param   string     $newsorts     ',' separated values of new sort_orders
     * @param   string     $table     Database table to perform sort on
     * @access  public
     * @return  bool    Success/failure
     */
    function SortItem($pids, $newsorts, $table = 'property')
    {
		//$model = $GLOBALS['app']->LoadGadget('Properties', 'Model');
        $ids = explode(',', $pids);
        $sorts = explode(',', $newsorts);
        $i = 0;
		if ($table != 'property_posts' && $table != 'property' && $table != 'propertyamenity' && $table != 'amenity_types') {
			$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_POST_NOT_SORTED'), RESPONSE_ERROR);
			return false;
		}
		foreach ($ids as $pid) {
			if ((int)$pid != 0) {
				$new_sort = $sorts[$i];
				$params               	= array();
				$params['pid']         	= (int)$pid;
				$params['new_sort'] 	= (int)$new_sort;
				
				$sql = '
					UPDATE [['.$table.']] SET
						[sort_order] = {new_sort} 
					WHERE [id] = {pid}';

				$result1 = $GLOBALS['db']->query($sql, $params);
				if (Jaws_Error::IsError($result1)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_POST_NOT_SORTED'), RESPONSE_ERROR);
					//$GLOBALS['app']->Session->PushLastResponse($result1->GetMessage(), RESPONSE_ERROR);
					return false;
				}
				$i++;
			}
		}
		$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_POST_UPDATED'), RESPONSE_NOTICE);
		return true;
    }

    /**
     * Search for properties that matches multiple queries
     * in the title or content
     *
     * @param   string  $status  Status of properties we want to display
     * @param   string  $search  Keyword (title/description) of properties we want to look for
     * @param   string  $bedroom  Bedrooms
     * @param   string  $bathroom  Bathrooms
     * @param   string  $category  Categories to search in
     * @param   string  $community  Communities to search for
     * @param   string  $amenities  Amenities to search for
     * @param   int     $offSet  Data limit
     * @param   int     $OwnerID  Owner's ID
     * @param   int     $pid 	Property category to search in
     * @param   string     $sortColumn 	Property db table column to sort on
     * @param   string     $sortDir 	Sort direction (ASC/DESC)
     * @param   string     $active 	(Y/N) Search active/inactive properties
     * @param   string     $country_id 	Country ID (country DB table) to search in
     * @access  public
     * @return  array   Array of matches
     * @TODO  Update with code from Store gadget
     */
    function MultipleSearchProperties(
		$status, $search, $bedroom = '', $bathroom = '', $category = '', $community = '', 
		$amenities = '', $offSet = null, $OwnerID = null, $pid = null, $sortColumn = 'sort_order', 
		$sortDir = 'ASC', $active = null, $country_id = ''
	) {
        $fields = array('sort_order', 'premium', 'price', 'created', 'community', 'featured', 'ownerid', 'title', 'created', 'updated', 'active');
        $sortColumn = strtolower($sortColumn);
        if (!in_array($sortColumn, $fields)) {
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_WARNING, _t('PROPERTIES_ERROR_UNKNOWN_COLUMN'));
            }
            $sortColumn = 'sort_order';
        }
		
        $sortDir = strtoupper($sortDir);
        if ($sortDir == 'DESC') {
            $sortDir = 'DESC';
        } else {
            $sortDir = 'ASC';
        }
        
		$result = array();
		$model = $GLOBALS['app']->LoadGadget('Properties', 'Model');
		//$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		if (!is_null($pid)) {
			$properties = $model->GetAllPropertiesOfParent((int)$pid, $sortColumn, $sortDir, $active, $OwnerID);
		} else {
			$properties = $model->GetProperties(null, $sortColumn, $sortDir, false, $OwnerID, $active);
		}
			
			/*
			echo '<pre>';
			var_dump($properties);
			echo '</pre>';
			exit;
			*/
		foreach ($properties as $property) {
			$add_property = true;
			if (trim($status) != '') {
				if ($status != $property['status']) {
					$add_property = false;
				}
			}
			if (trim($bedroom) != '') {
				if ($bedroom != $property['bedroom']) {
					$add_property = false;
				}
			}
			if (trim($bathroom) != '') {
				if ($bathroom != $property['bathroom']) {
					$add_property = false;
				}
			}
			if (trim($category) != '') {
				if (strtolower($category) != strtolower($property['category'])) {
					$add_property = false;
				}
			}
			if (trim($community) != '') {
				if (strtolower($community) != strtolower($property['community'])) {
					$add_property = false;
				}
			}
			
			if (trim($country_id) != '') {
				if ((int)$country_id != (int)$property['country_id']) {
					$add_property = false;
				}
			}
			
			if (trim($amenities) != '' || strpos($search, ' - Amenity') !== false) {
				if (strpos($search, ' - Amenity') !== false) {
					$amenities = str_replace(' - Amenity', '', $search);
				}
				$amenity_found = false;
				if (!empty($property['amenity'])) {
					$propAmenities = explode(',', $property['amenity']);
					foreach($propAmenities as $propAmenity) {		            
						$amenity = $model->GetAmenity((int)$propAmenity);
						if (!Jaws_Error::IsError($amenity) && isset($amenity['id']) && !empty($amenity['id'])) {
							$amenity_type = $model->GetAmenityType($amenity['typeid']);
							if (!Jaws_Error::IsError($amenity_type) && isset($amenity_type['id']) && !empty($amenity_type['id'])) {
								$searchamenities = explode(',', $amenities);
								foreach ($searchamenities as $a) {
									$a = trim(strtolower($a));
									if (
										strpos(trim(strtolower($amenity['feature'])), $a) !== false || 
										strpos(trim(strtolower($amenity['description'])), $a) !== false || 
										strpos(trim(strtolower($amenity_type['title'])), $a) !== false || 
										strpos(trim(strtolower($amenity_type['description'])), $a) !== false
									) {
										$amenity_found = true;
										break;
									}
								}
							}
						}
						if ($amenity_found === true) {
							break;
						}
					}
				}
				if ($amenity_found === false) {
					$add_property = false;
				}
			}

			if (trim($search) != '') {
				$search_found = false;
				// detect zip code
				if (strlen(trim($search)) == 5 && is_numeric(trim($search)) && strpos(strtolower($property['address']), trim($search).',') !== false) {
					$search_found = true;
					//break;
				} else {
					$v = strtolower($search);
						if (strpos(strtolower($property['title']), $v) !== false || strpos(trim(strtolower($property['mls'])), $v) !== false || strpos(strtolower($property['address']), $v) !== false || 
						strpos(strtolower($property['city']), $v) !== false || strpos(trim(strtolower($property['region'])), $v) !== false || strpos(trim(strtolower($property['postal_code'])), $v) !== false || 
						strpos(trim(strtolower($property['category'])), $v) !== false || strpos(trim(strtolower($property['sm_description'])), $v) !== false || 
						strpos(trim(strtolower($property['community'])), $v) !== false || 
						strpos(trim(strtolower($property['price'])), $v) !== false || strpos(trim(strtolower($property['status'])), $v) !== false || 
						strpos(trim(strtolower($property['amenity'])), $v) !== false || strpos(trim(strtolower($property['options'])), $v) !== false || 
						strpos(trim(strtolower($property['propertyno'])), $v) !== false || strpos(trim(strtolower($property['internal_propertyno'])), $v) !== false || 
						strpos(trim(strtolower($property['fast_url'])), $v) !== false || strpos(trim(strtolower($property['rentdy'])), $v) !== false || strpos(trim(strtolower($property['rentwk'])), $v) !== false || 
						strpos(trim(strtolower($property['rentmo'])), $v) !== false || strpos(trim(strtolower($property['i360'])), $v) !== false) {
							$search_found = true;
						} else if (strlen($v) > 3) {
							if (strpos(trim(strtolower($property['description'])), $v) !== false) {
								$search_found = true;
							}
						}
					if ($search_found === false) {
						$add_property = false;
					}
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
		}
		return $result;
    }

    /**
     * Search for properties that match multiple queries
     * in the title or content and return array of given key
     *
     * @param   string  $status  Status of properties we want to display
     * @param   string  $search  Keyword (title/description) of properties we want to look for
     * @param   string  $bedroom  Bedrooms
     * @param   string  $bathroom  Bathrooms
     * @param   string  $category  Categories to search in
     * @param   string  $community  Communities to search for
     * @param   string  $amenities  Amenities to search for
     * @param   int     $offSet  Data limit
     * @param   int     $OwnerID  Owner's ID
     * @param   int     $pid 	Property category to search in
     * @param   boolean     $only_titles 	If true, return only property titles, otherwise return <span>s for autocomplete
     * @param   string     $sortColumn 	Property DB table column to sort on
     * @param   string     $sortDir 	Sort direction (ASC/DESC)
     * @param   string     $return 	Property DB table column to return
     * @param   string     $links 	(Y/N) Return links
     * @access  public
     * @return  array   Array of matches
     */
    function SearchKeyWithProperties(
		$search, $status, $bedroom = '', $bathroom = '', $category = '', 
		$community = '', $amenities = '', $OwnerID = null, $pid = null, $only_titles = false, 
		$sortColumn = 'title', $sortDir = 'ASC', $return = 'title', $links = 'N'
	) {
        $return = strtolower($return);
        $fields = array('sort_order', 'premium', 'price', 'created', 'community', 'featured', 'ownerid', 'title', 'created', 'updated', 'active', 'amenity', 'city', 'country_id');
        $sortColumn = strtolower($sortColumn);
        if (!in_array($sortColumn, $fields)) {
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_WARNING, _t('PROPERTIES_ERROR_UNKNOWN_COLUMN'));
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
		$model = $GLOBALS['app']->LoadGadget('Properties', 'Model');
		//$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		if (is_null($OwnerID)) {
			$OwnerID = 0;
		}
		if (!is_null($pid)) {
			$properties = $model->GetAllPropertiesOfParent((int)$pid, $sortColumn, $sortDir);
		} else {
			if ($return == 'amenity') {
				$properties = $this->SearchAmenities($search, 'Y', null, $OwnerID, true);
			} else {
				$properties = $model->GetProperties(null, $sortColumn, $sortDir, false, $OwnerID, 'Y', $return, $search);
			}
		}
		
		if (Jaws_Error::IsError($properties)) {
			return new Jaws_Error($properties->GetMessage(), _t('PROPERTIES_NAME'));
		}
		$keys_found = array();
		//echo '<pre>';
		//var_dump($properties);
		//echo '</pre>';
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
			"example",
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
			"novel",
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
			"take",
			"taken",
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
			"versus",
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
			"zero"
		);
		foreach ($properties as $property) {
			//echo '<br />Search: '.$search.' status: '.$status.' bedroom: '.$bedroom.' bathroom: '.$bathroom.' cat: '.$category.' community: '.$community;
			//echo '<br />'.$property['title'];
			$in_title = false;
			$add_property = true;
			if ($return != 'amenity') {
				if (trim($status) != '') {
					if ($status != $property['status']) {
						$add_property = false;
					}
				}
				if (trim($bedroom) != '') {
					if ($bedroom != $property['bedroom']) {
						$add_property = false;
					}
				}
				if (trim($bathroom) != '') {
					if ($bathroom != $property['bathroom']) {
						$add_property = false;
					}
				}
				if (trim($category) != '') {
					if (strtolower($category) != strtolower($property['category'])) {
						$add_property = false;
					}
				}
				if (trim($community) != '') {
					if (strtolower($community) != strtolower($property['community'])) {
						$add_property = false;
					}
				}
			
				// detect zip code
				if (trim($search) != '' && strlen(trim($search)) == 5 && is_numeric(trim($search))) {
					if (strpos(strtolower($property['address']), trim($search).',') === false) {
						$add_property = false;
					}
				} else if (trim($search) != '' && strpos(strtolower($property['title']), strtolower(trim($search))) !== false) {
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
						if (substr(strtolower($newstring), 0, strlen(strtolower($search))) == strtolower($search) && !in_array(strtolower((string)$property[$return]), $keys_found)) {
							$keys_found[] = strtolower((string)$property[$return]);
							if (strtolower($newstring) == strtolower($property['city'])) {
								$region = $model->GetRegion($property['region']);
								if (Jaws_Error::IsError($region)) {
									//return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTY_NOT_RETRIEVED'), _t('PROPERTIES_NAME'));
									return new Jaws_Error($region->GetMessage(), _t('PROPERTIES_NAME'));
								} else {
									if (isset($region['region'])) {
										if (strpos($region['region'], " - US") !== false) {
											$region['region'] = str_replace(" - US", '', $region['region']);
										}
										if (strpos($region['region'], " - British") !== false) {
											$region['region'] = str_replace(" - British", '', $region['region']);
										}
										if (strpos($region['region'], " SAR") !== false) {
											$region['region'] = str_replace(" SAR", '', $region['region']);
										}
										if (strpos($region['region'], " - Islas Malvinas") !== false) {
											$region['region'] = str_replace(" - Islas Malvinas", '', $region['region']);
										}
										if (isset($region['parent'])) {
											$country = $model->GetRegion($region['parent']);
											if (Jaws_Error::IsError($country)) {
												//return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTY_NOT_RETRIEVED'), _t('PROPERTIES_NAME'));
												return new Jaws_Error($country->GetMessage(), _t('PROPERTIES_NAME'));
											} else {
												if (isset($country['country_iso_code']) && !empty($country['country_iso_code'])) {
													$region['region'] .= ', '.$country['country_iso_code'];
												}
											}
										}
									}
									if ($only_titles === true) {
										if ($links == 'Y') {
											$exact[] = array('<a href="'.$GLOBALS['app']->getSiteURL().'/index.php?gadget=Properties&action=Category&id=all&keyword='.$property['city'].'">'.$property['city'].(isset($region['region']) ? '<span class="informal">, '.$region['region'].'</span>' : '').'</a>');
										} else {
											$exact[] = array($property['city'].(isset($region['region']) ? '<span class="informal">, '.$region['region'].'</span>' : ''));
										}
									} else {
										if ($links == 'Y') {
											$exact[] = array('<a href="'.$GLOBALS['app']->getSiteURL().'/index.php?gadget=Properties&action=Category&id=all&keyword='.$property['city'].'">'.$property['city'].(isset($region['region']) ? ', '.$region['region'] : '').'</a>');
										} else {	
											$exact[] = array($property['city'].(isset($region['region']) ? ', '.$region['region'] : ''));
										}
									}
								}
							} else {
								if ($links == 'Y') {
									$exact[] = array('<a href="'.$GLOBALS['app']->getSiteURL().'/index.php?gadget=Properties&action=Category&id=all&keyword='.ucfirst(strtolower($newstring)).'">'.ucfirst(strtolower($newstring)).'</a>');
								} else {
									$exact[] = array(ucfirst(strtolower($newstring)));
								}
							}
						}
					  } else {
						$add_property = false;
					  }
					}
				}
				
				if (!is_null($pid)) {
					if (!is_null($OwnerID)) {
						if ((int)$OwnerID != $property['ownerid']) {
							$add_property = false;
						}
					}
				}
			}
			if ($add_property === true) {
				// Make sure this key is only added once
				if ($return == 'city') {
					if (substr(strtolower($property['city']), 0, strlen(strtolower(trim($search)))) == strtolower($search) && !in_array(strtolower($property['city']), $keys_found)) {
						if (!is_numeric(trim($search)) && trim($search) != '' && strpos(strtolower($property['city']), strtolower($search)) === false) {
						} else {
							$region = $model->GetRegion($property['region']);
							if (Jaws_Error::IsError($region)) {
								//return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTY_NOT_RETRIEVED'), _t('PROPERTIES_NAME'));
								return new Jaws_Error($region->GetMessage(), _t('PROPERTIES_NAME'));
							} else {
								if (isset($region['region'])) {
									if (strpos($region['region'], " - US") !== false) {
										$region['region'] = str_replace(" - US", '', $region['region']);
									}
									if (strpos($region['region'], " - British") !== false) {
										$region['region'] = str_replace(" - British", '', $region['region']);
									}
									if (strpos($region['region'], " SAR") !== false) {
										$region['region'] = str_replace(" SAR", '', $region['region']);
									}
									if (strpos($region['region'], " - Islas Malvinas") !== false) {
										$region['region'] = str_replace(" - Islas Malvinas", '', $region['region']);
									}
									if (isset($region['parent'])) {
										$country = $model->GetRegion($region['parent']);
										if (Jaws_Error::IsError($country)) {
											//return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTY_NOT_RETRIEVED'), _t('PROPERTIES_NAME'));
											return new Jaws_Error($country->GetMessage(), _t('PROPERTIES_NAME'));
										} else {
											if (isset($country['country_iso_code']) && !empty($country['country_iso_code'])) {
												$region['region'] .= ', '.$country['country_iso_code'];
											}
										}
									}
								}
								if ($only_titles === true) {
									if ($links == 'Y') {
										$results[] = array('<a href="'.$GLOBALS['app']->getSiteURL().'/index.php?gadget=Properties&action=Category&id=all&keyword='.$property['city'].'">'.$property['city'].(isset($region['region']) ? '<span class="informal">, '.$region['region'].'</span>' : '').'</a>');
									} else {
										$results[] = array($property['city'].(isset($region['region']) ? '<span class="informal">, '.$region['region'].'</span>' : ''));
									}
								} else {
									if ($links == 'Y') {
										$results[] = array('<a href="'.$GLOBALS['app']->getSiteURL().'/index.php?gadget=Properties&action=Category&id=all&keyword='.$property['city'].'">'.$property['city'].(isset($region['region']) ? ', '.$region['region'] : '').'</a>');
									} else {	
										$results[] = array($property['city'].(isset($region['region']) ? ', '.$region['region'] : ''));
									}
								}
								$keys_found[] = strtolower($property['city']);
								//echo 'RETURN: '.$property['city'].':'.$property['region'];
							}
						}
					}
				} else {
					if (!in_array(strtolower((string)$property[$return]), $keys_found) || count($keys_found) <= 0) {
						if ($only_titles === true) {
							if ($return == 'amenity') {
								$results[] = array($sortColumn => $property['feature'].' - Amenity');
							} else {
								$results[] = array($sortColumn => $property[$return]);
							}
						} else {
							$results[] = array($sortColumn => $property[$return]);
						}
						$keys_found[] = strtolower((string)$property[$return]);

						//echo 'RETURN: '.$property[$return];
					}
				}
			}
		}
				
		foreach($exact as $ex){
			if (!in_array($ex, $result)) {
				$result[] = $ex;
			}
		}
		foreach($results as $res){
			if (!in_array($res, $result)) {
				$result[] = $res;
			}
		}
				
		///*
		if (count($result)) {
			// Sort result array
			$subkey = 0; 
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
			//$result = $temp_array;
		}
		//*/
		return $result;
    }

    /**
     * Search for property parents that matches a status and/or a keyword
     * in the title or content
     *
     * @param   string  $status  Status of page(s) we want to display
     * @param   string  $search  Keyword (title/description) of pages we want to look for
     * @param   int     $offSet  Data limit
     * @param   int     $OwnerID  Owner's ID
     * @param   int     $pid  Property category ID to search in
     * @access  public
     * @return  array   Array of matches
     */
    function SearchPropertyParents($status, $search, $offSet = null, $OwnerID = null, $pid = null)
    {
        $params = array();


        $sql = '
            SELECT [propertyparentid], [propertyparentparent], [propertyparentsort_order], [propertyparentcategory_name], 
				[propertyparentimage], [propertyparentdescription], [propertyparentactive], 
				[propertyparentownerid], [propertyparentcreated], [propertyparentupdated], 
				[propertyparentfeatured], [propertyparentfast_url], [propertyparentrss_url], [propertyparentregionid],
				[propertyparentrss_overridecity], [propertyparentrandomize]
            FROM [[propertyparent]]
			WHERE ([propertyparentcategory_name] <> ""';

        if (trim($status) != '') {
            $sql .= ' AND [propertyparentactive] = {status}';
			$params['status'] = $status;
        }
        
		if (!is_null($OwnerID)) {
			$sql .= ' AND [propertyparentownerid] = {OwnerID}';
			$params['OwnerID'] = $OwnerID;
		}
		if (!is_null($pid)) {
			$sql .= ' AND [propertyparentparent] = {pid}';
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
                $sql .= " AND ([propertyparentcategory_name] LIKE {textLike_".$i."} OR [propertyparentfast_url] LIKE {textLike_".$i."} OR [propertyparentdescription] LIKE {textLike_".$i."} OR [propertyparentrss_url] LIKE {textLike_".$i."})";
                $params['textLike_'.$i] = '%'.$v.'%';
                $i++;
            }
        }


        if (is_numeric($offSet)) {
            $limit = 10;
            $result = $GLOBALS['db']->setLimit(10, $offSet);
            if (Jaws_Error::IsError($result)) {
                return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTYPARENTS_NOT_RETRIEVED'), _t('PROPERTIES_NAME'));
            }
        }

        $sql.= ' ORDER BY [propertyparentid] ASC';

        $types = array(
			'integer', 'integer', 'integer', 'text', 'text', 'text', 
			'text', 'integer', 'timestamp', 'timestamp', 
			'text', 'text', 'text', 'integer', 'text', 'text'
		);
	    
        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTYPARENTS_NOT_RETRIEVED'), _t('PROPERTIES_NAME'));
        }
        //limit, sort, sortDirection, offset..
        return $result;
    }

    /**
     * Search for amenities that matches a status and/or a keyword
     * in the title or content
     *
     * @param   string  $status  Status of page(s) we want to display
     * @param   string  $search  Keyword (title/description) of pages we want to look for
     * @param   int     $offSet  Data limit
     * @param   int     $OwnerID  Owner's ID
     * @param   boolean     $only_titles  If true, only return titles, otherwise return entire amenities DB info array 
     * @access  public
     * @return  array   Array of matches
     */
    function SearchAmenities($search, $status, $offSet = null, $OwnerID = null, $only_titles = false)
    {
        $params = array();


        $sql = '
            SELECT';
		
		if ($only_titles === false) {
			$sql .= ' [id], [feature], [typeid], [description], [ownerid], 
				[active], [created], [updated]';
			$types = array(
				'integer', 'text', 'integer', 'text', 'integer', 'text', 
				'timestamp', 'timestamp'
			);
		} else {
			$sql .= ' [feature]';
			$types = array(
				'text'
			);
		}
		$sql .= '
			FROM [[propertyamenity]]
			WHERE ([feature] <> ""';

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
                $sql .= " AND ([feature] LIKE {textLike_".$i."} OR [description] LIKE {textLike_".$i."})";
                $params['textLike_'.$i] = '%'.$v.'%';
                $i++;
            }
        }

        if (is_numeric($offSet)) {
            $limit = 10;
            $result = $GLOBALS['db']->setLimit(10, $offSet);
            if (Jaws_Error::IsError($result)) {
                return new Jaws_Error(_t('PROPERTIES_ERROR_AMENITIES_NOT_RETRIEVED'), _t('PROPERTIES_NAME'));
            }
        }

		if ($only_titles === false) {
			$sql.= ' ORDER BY [id] ASC';
		} else {
			$sql.= ' ORDER BY [feature] ASC';
		}

	    
        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('PROPERTIES_ERROR_AMENITIES_NOT_RETRIEVED'), _t('PROPERTIES_NAME'));
        }
        //limit, sort, sortDirection, offset..
        return $result;
    }
    
	/**
     * Search for amenity types that matches a status and/or a keyword
     * in the title or content
     *
     * @param   string  $status  Status of page(s) we want to display
     * @param   string  $search  Keyword (title/description) of pages we want to look for
     * @param   int     $offSet  Data limit
     * @param   int     $OwnerID  Owner's ID
     * @access  public
     * @return  array   Array of matches
     */
    function SearchAmenityTypes($status, $search, $offSet = null, $OwnerID = null)
    {
        $params = array();


        $sql = '
            SELECT [id], [title], [description], [ownerid] 
				[active], [created], [updated]
            FROM [[amenity_types]]
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
                return new Jaws_Error(_t('PROPERTIES_ERROR_AMENITYTYPES_NOT_RETRIEVED'), _t('PROPERTIES_NAME'));
            }
        }

        $sql.= ' ORDER BY [id] ASC';

        $types = array(
			'integer', 'text', 'text', 'integer', 'text', 
			'timestamp', 'timestamp'
		);
	    
        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('PROPERTIES_ERROR_AMENITYTYPES_NOT_RETRIEVED'), _t('PROPERTIES_NAME'));
        }
        //limit, sort, sortDirection, offset..
        return $result;
    }
    
	/**
     * Hides an RSS item
     *
     * @access  public
     * @param   int  $pid  property category ID
     * @param   string  $title  title of RSS item
     * @param   string  $published  date of RSS item
     * @param   string  $url  url of RSS item
     * @return  bool    Success/failure
     */
    function HideRss($pid, $title, $published, $url)
    {
		$sql = "
            INSERT INTO [[property_rss_hide]]
                ([linkid], [title], [published], [url])
            VALUES
                ({LinkID}, {title}, {published}, {url})";
        
		$params               		= array();
		$params['title'] 			= $title;
		$params['published'] 		= $published;
		$params['url'] 				= $url;
		$params['LinkID']         	= (int)$pid;

        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('PROPERTIES_ERROR_RSS_NOT_HIDDEN'), _t('PROPERTIES_NAME'));
            //return new Jaws_Error($result->GetMessage(), _t('PROPERTIES_NAME'));
        }

        $GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_RSS_HIDDEN'), RESPONSE_NOTICE);
        return true;
    }

    /**
     * Shows RSS item
     *
     * @access  public
     * @param   int  $pid  property category ID
     * @param   string  $title  title of RSS item
     * @param   string  $published  date of RSS item
     * @param   string  $url  url of RSS item
     * @return  bool    Success/failure
     */
    function ShowRss($pid, $title, $published, $url)
    {
        $sql = 'DELETE FROM [[property_rss_hide]] WHERE ([linkid] = {LinkID} AND [title] = {title} AND [published] = {published} AND [url] = {url})';
		$params               		= array();
		$params['title'] 			= $title;
		$params['published'] 		= $published;
		$params['url'] 				= $url;
		$params['LinkID']         	= (int)$pid;
        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_RSS_NOT_SHOWN'), RESPONSE_ERROR);
            return new Jaws_Error(_t('PROPERTIES_ERROR_RSS_NOT_SHOWN'), _t('PROPERTIES_NAME'));
        }

        $GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_RSS_SHOWN'), RESPONSE_NOTICE);
        return true;
    }
		
    /**
     * Updates properties_parents DB table when property is updated.
     *
     * @access  public
     * @param   int     $id             The ID of the property parent to update.
     * @return  boolean Success/failure
     */
    function ActivatePropertiesCategories($id)
	{
		$model = $GLOBALS['app']->LoadGadget('Properties', 'Model');
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        // Get Property info
		$page = $model->GetProperty($id);
        if (Jaws_Error::isError($page)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_PROPERTY_NOT_FOUND'), RESPONSE_ERROR);
            return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTY_NOT_FOUND'), _t('PROPERTIES_NAME'));
			
		} else {
			// Get category array, and update each category in it
			if (isset($page['id'])) {
								
				$sql = "
					DELETE FROM [[properties_parents]]
						WHERE ([prop_id] = {prop_id})";
				
				$params               		= array();
				$params['prop_id']        	= $page['id'];

				$result = $GLOBALS['db']->query($sql, $params);
				if (Jaws_Error::IsError($result)) {
					return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTYPARENT_NOT_UNLINKED'), _t('PROPERTIES_NAME'));
					//return new Jaws_Error($result->GetMessage(), _t('PROPERTIES_NAME'));
				}
				
				$categories = explode(',', $page['category']);
				
				// Insert updated records 
				foreach ($categories as $pid) {
					if ((int)$pid > 0) {
						$sql1 = "
							INSERT INTO [[properties_parents]]
								([parent_id], [prop_id], [created], [updated])
							VALUES
								({parent_id}, {prop_id}, {now}, {now})";
						
						$params1               		= array();
						$params1['prop_id']        	= $page['id'];
						$params1['parent_id']       = (int)$pid;
						$params1['now']        		= $GLOBALS['db']->Date();

						$result1 = $GLOBALS['db']->query($sql1, $params1);
						if (Jaws_Error::IsError($result1)) {
							return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTYPARENT_NOT_LINKED'), _t('PROPERTIES_NAME'));
							//return new Jaws_Error($result->GetMessage(), _t('PROPERTIES_NAME'));
						}
						
						// Update Active status
						$sql2 = '
							UPDATE [[propertyparent]] SET
								[propertyparentactive] = {Active}, 
								[propertyparentupdated] = {now}
							WHERE ([propertyparentid] = {id} AND [propertyparentactive] = {Inactive})';

						$params2               		= array();
						$params2['Active']       	= 'Y'; 
						$params2['Inactive']       	= 'N'; 
						$params2['id']         		= (int)$pid;
						$params2['now']        		= $GLOBALS['db']->Date();

						$result2 = $GLOBALS['db']->query($sql2, $params2);
						if (Jaws_Error::IsError($result2)) {
							return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTYPARENT_NOT_UPDATED'), _t('PROPERTIES_NAME'));
						}
						
						$parent = $model->GetPropertyParent((int)$pid);
						if (Jaws_Error::isError($parent)) {
							$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_PROPERTYPARENT_NOT_FOUND'), RESPONSE_ERROR);
							return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTYPARENT_NOT_FOUND'), _t('PROPERTIES_NAME'));
						}
						// Add property parent to Menu
						if ($parent['propertyparentownerid'] == 0) {
							// update Menu Item for Page
							$visible = ($parent['propertyparentactive'] == 'Y') ? 1 : 0;
							// if old title is different, update menu item
							$old_url = $GLOBALS['app']->Map->GetURLFor('Properties', 'Category', array('id' => $parent['propertyparentfast_url']));
							$new_url = $GLOBALS['app']->Map->GetURLFor('Properties', 'Category', array('id' => $parent['propertyparentfast_url']));

							$parentid = 0;
							$parentGid = 1;
							// get parent menus
							if ($parent['propertyparentparent'] > 0) {
								$sql  = 'SELECT [id],[gid] FROM [[menus]] WHERE [id] = {parent}';
								$parentMenu = $GLOBALS['db']->queryRow($sql, array('parent' => $parent['propertyparentparent']));
								if (Jaws_Error::IsError($parentMenu)) {
									$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
									return false;
								} else {
									$parentid = (isset($parentMenu['id']) ? $parentMenu['id'] : $parentid);
									$parentGid = (isset($parentMenu['gid']) ? $parentMenu['gid'] : $parentGid);
								}
							}
							
							$sql  = 'SELECT [id], [rank] FROM [[menus]] WHERE [url] = {url}';
							$oid = $GLOBALS['db']->queryRow($sql, array('url' => $old_url));
							if (Jaws_Error::IsError($oid)) {
								//$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
								$GLOBALS['app']->Session->PushLastResponse($oid->GetMessage(), RESPONSE_ERROR);
								return false;
							} else if (isset($oid['id']) && !empty($oid['id'])) {
								$menuAdmin = $GLOBALS['app']->LoadGadget('Menu', 'AdminModel');
								if (
									!$menuAdmin->UpdateMenu(
										$oid['id'], 
										$parentid, 
										1, 
										'Properties', 
										$xss->parse(strip_tags($GLOBALS['app']->UTF8->str_replace(',', '', $parent['propertyparentcategory_name']))), 
										$new_url, 
										0, 
										$oid['rank'], 
										$visible
									)
								) {
									//$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
									$GLOBALS['app']->Session->PushLastResponse($menuAdmin->GetMessage(), RESPONSE_ERROR);
									return false;
								}
							} else {
								// add Menu Item for Page								
								$visible = ($parent['propertyparentactive'] == 'Y') ? 1 : 0;
								$url = $GLOBALS['app']->Map->GetURLFor('Properties', 'Category', array('id' => $parent['propertyparentfast_url']));
												
								$sql  = 'SELECT [id] FROM [[menus]] WHERE [url] = {url}';
								$oid = $GLOBALS['db']->queryRow($sql, array('url' => $url));
								if (Jaws_Error::IsError($oid)) {
									$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
									return false;
								} else {
									if (empty($oid['id'])) {
										// Get highest rank of current menu items
										$sql = "SELECT MAX([rank]) FROM [[menus]] WHERE [gid] = {gid} ORDER BY [rank] DESC";
										$rank = $GLOBALS['db']->queryOne($sql, array('gid' => $parentGid));
										if (Jaws_Error::IsError($rank)) {
											$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
											return false;
										}
										$menuAdmin = $GLOBALS['app']->LoadGadget('Menu', 'AdminModel');
										if (
											!$menuAdmin->InsertMenu(
												$parentid, 
												$parentGid, 
												'Properties', 
												$xss->parse(strip_tags($GLOBALS['app']->UTF8->str_replace(',', '', $parent['propertyparentcategory_name']))), 
												$url, 
												0, 
												(int)$rank+1, 
												$visible, 
												true
											)
										) {
											$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
											return false;
										}
									} else {
										$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
										return false;
									}
								}
							}
						}
						$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_PROPERTYPARENT_UPDATED'), RESPONSE_NOTICE);
					}
				}
			}
		}
        return true;
    }

    /**
     * Updates properties_parents DB table when property is updated.
     *
     * @access  public
     * @param   int     $id             The ID of the property parent to update.
     * @return  boolean Success/failure
     */
    function UpdatePropertiesCategories($id)
	{
		$model = $GLOBALS['app']->LoadGadget('Properties', 'Model');
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		// Get Property info
		$page = $model->GetProperty($id);
        if (Jaws_Error::isError($page)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_PROPERTY_NOT_FOUND'), RESPONSE_ERROR);
            return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTY_NOT_FOUND'), _t('PROPERTIES_NAME'));
			
		} else {
			// Get category array, and update each category in it
			if (isset($page['id'])) {
				$sql = "
					DELETE FROM [[properties_parents]]
						WHERE ([prop_id] = {prop_id})";
				
				$params               		= array();
				$params['prop_id']        	= $page['id'];

				$result = $GLOBALS['db']->query($sql, $params);
				if (Jaws_Error::IsError($result)) {
					return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTYPARENT_NOT_UNLINKED'), _t('PROPERTIES_NAME'));
					//return new Jaws_Error($result->GetMessage(), _t('PROPERTIES_NAME'));
				}
				
				$categories = explode(',', $page['category']);
				
				// Insert updated records 
				foreach ($categories as $pid) {
					if ((int)$pid > 0) {
						$sql1 = "
							INSERT INTO [[properties_parents]]
								([parent_id], [prop_id], [created], [updated])
							VALUES
								({parent_id}, {prop_id}, {now}, {now})";
						
						MDB2::loadFile('Date');
						$params1               		= array();
						$params1['prop_id']        	= $page['id'];
						$params1['parent_id']       = (int)$pid;
						$params1['now']        		= $GLOBALS['db']->Date();

						$result1 = $GLOBALS['db']->query($sql1, $params1);
						if (Jaws_Error::IsError($result1)) {
							return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTYPARENT_NOT_LINKED'), _t('PROPERTIES_NAME'));
							//return new Jaws_Error($result->GetMessage(), _t('PROPERTIES_NAME'));
						}
						
						// Update Active status
						$sql2 = '
							UPDATE [[propertyparent]] SET
								[propertyparentactive] = {Active}, 
								[propertyparentupdated] = {now}
							WHERE ([propertyparentid] = {id} AND [propertyparentactive] = {Inactive})';

						$params2               		= array();
						$params2['Active']       	= 'Y'; 
						$params2['Inactive']       	= 'N'; 
						$params2['id']         		= (int)$pid;
						$params2['now']        		= $GLOBALS['db']->Date();

						$result2 = $GLOBALS['db']->query($sql2, $params2);
						if (Jaws_Error::IsError($result2)) {
							return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTYPARENT_NOT_UPDATED'), _t('PROPERTIES_NAME'));
						}
						
						$parent = $model->GetPropertyParent((int)$pid);
						if (Jaws_Error::isError($parent)) {
							$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_PROPERTYPARENT_NOT_FOUND'), RESPONSE_ERROR);
							return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTYPARENT_NOT_FOUND'), _t('PROPERTIES_NAME'));
						}
						// Add property parent to Menu
						if ($parent['propertyparentownerid'] == 0) {
							// update Menu Item for Page
							$visible = ($parent['propertyparentactive'] == 'Y') ? 1 : 0;
							// if old title is different, update menu item
							$old_url = $GLOBALS['app']->Map->GetURLFor('Properties', 'Category', array('id' => $parent['propertyparentfast_url']));
							$new_url = $GLOBALS['app']->Map->GetURLFor('Properties', 'Category', array('id' => $parent['propertyparentfast_url']));

							$parentid = 0;
							// get parent menus
							if ($parent['propertyparentparent'] > 0) {
								$sql  = 'SELECT [id] FROM [[menus]] WHERE [id] = {parent}';
								$parentMenu = $GLOBALS['db']->queryRow($sql, array('parent' => $parent['propertyparentparent']));
								if (Jaws_Error::IsError($parentMenu)) {
									$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
									return false;
								} else {
									$parentid = (isset($parentMenu['id']) ? $parentMenu['id'] : $parentid);
								}
							}
							
							$sql  = 'SELECT [id], [rank] FROM [[menus]] WHERE [url] = {url}';
							$oid = $GLOBALS['db']->queryRow($sql, array('url' => $old_url));
							if (Jaws_Error::IsError($oid)) {
								//$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
								$GLOBALS['app']->Session->PushLastResponse($oid->GetMessage(), RESPONSE_ERROR);
								return false;
							} else if (!empty($oid['id']) && isset($oid['id'])) {
								$menuAdmin = $GLOBALS['app']->LoadGadget('Menu', 'AdminModel');
								if (
									!$menuAdmin->UpdateMenu(
										$oid['id'], 
										$parentid, 
										1, 
										'Properties', 
										$xss->parse(strip_tags($GLOBALS['app']->UTF8->str_replace(',', '', $parent['propertyparentcategory_name']))), 
										$new_url, 
										0, 
										$oid['rank'], 
										$visible
									)
								) {
									//$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
									$GLOBALS['app']->Session->PushLastResponse($menuAdmin->GetMessage(), RESPONSE_ERROR);
									return false;
								}
							} else {
								// add Menu Item for Page								
								$visible = ($parent['propertyparentactive'] == 'Y') ? 1 : 0;
								$url = $GLOBALS['app']->Map->GetURLFor('Properties', 'Category', array('id' => $parent['propertyparentfast_url']));
																				
								$sql  = 'SELECT [id] FROM [[menus]] WHERE [url] = {url}';
								$oid = $GLOBALS['db']->queryRow($sql, array('url' => $url));
								if (Jaws_Error::IsError($oid)) {
									$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
									return false;
								} else {
									if (empty($oid['id'])) {
										// Get highest rank of current menu items
										$sql = "SELECT MAX([rank]) FROM [[menus]] WHERE [gid] = {gid} ORDER BY [rank] DESC";
										$rank = $GLOBALS['db']->queryOne($sql, array('gid' => $parentGid));
										if (Jaws_Error::IsError($rank)) {
											$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
											return false;
										}
										$menuAdmin = $GLOBALS['app']->LoadGadget('Menu', 'AdminModel');
										if (
											!$menuAdmin->InsertMenu(
												$parentid, 
												$parentGid, 
												'Properties', 
												$xss->parse(strip_tags($GLOBALS['app']->UTF8->str_replace(',', '', $parent['propertyparentcategory_name']))), 
												$url, 
												0, 
												(int)$rank+1, 
												$visible, 
												true
											)
										) {
											$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
											return false;
										}
									} else {
										$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
										return false;
									}
								}
							}
						}
						$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_PROPERTYPARENT_UPDATED'), RESPONSE_NOTICE);
					}
				}
			}
		}
        return true;
    }

	/**
     * Returns an array with all the country DB table data
     *
     * @param   int  $category  ID of the property parent
     * @param   string  $fetch_url  URL we're on
     * @param   string  $override_city  City to force all properties to have in current category
     * @param   string  $rss_url  RSS URL
     * @param   int  $OwnerID  Owner's ID
     * @param   int  $num  Current property count
     * @param   string  $user_attended 	(Y/N) Is this user-attended?
     * @access  public
     * @return  string   Response
     */
    function InsertRSSProperties($category, $fetch_url = '', $override_city = '', $rss_url = '', $OwnerID = null, $num, $user_attended = 'N')
    {		
		ignore_user_abort(true); 
        set_time_limit(0);
		echo " ";
		ob_flush();
		flush();
		//$result = array();
		$multifeed = false;
		if (trim($fetch_url) != '') {
			// Detect URL type, currently only XML (showcase) and RSS (tourbuzz, google base) are supported
			include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
			// snoopy
			$snoopy = new Snoopy('Properties');
			$snoopy->agent = "Jaws";
			
			if ($snoopy->fetch($fetch_url)) {
				require_once(JAWS_PATH . 'libraries/magpierss-0.72/rss_fetch.inc');
				$rss = fetch_rss($fetch_url);
				if ($rss) {
					$real_rss_url = (trim($rss_url) != '' ? $rss_url : $fetch_url);
					if ($this->_propCount == 1) {
						echo '<br />&nbsp;<br />'.'<b>Now importing from: '.urldecode($real_rss_url).'</b>';
					}
					ob_flush();
					flush();
					$this->_propTotal = count($rss->items);
					reset($rss->items);
					if ((isset($num) && !empty($num) || $num == 0) && $user_attended == 'Y') {
						if ($num <= $this->_propTotal) {
							sleep(1);
							echo " ";
							ob_flush();
							flush();
							$this->_propCount = ($num+1);
							$this->InsertRSSProperty($rss->items[$num], $override_city, $category, $real_rss_url, $OwnerID);
							echo '<form name="property_rss_form" id="property_rss_form" action="index.php?gadget=Properties&action=UpdateRSSProperties" method="POST">'."\n";
							echo '<input type="hidden" name="category" value="'.$category.'">'."\n";
							echo '<input type="hidden" name="fetch_url" value="'.$fetch_url.'">'."\n";
							echo '<input type="hidden" name="override_city" value="'.$override_city.'">'."\n";
							echo '<input type="hidden" name="rss_url" value="'.$rss_url.'">'."\n";
							echo '<input type="hidden" name="OwnerID" value="'.$OwnerID.'">'."\n";
							echo '<input type="hidden" name="num" value="'.($num+1).'">'."\n";
							echo '<input type="hidden" name="ua" value="'.$user_attended.'">'."\n";
							echo '</form>'."\n";
							return true;
						}
					} else {
						foreach ($rss->items as $item) {
								sleep(1);
								echo " ";
								ob_flush();
								flush();
								$this->InsertRSSProperty($item, $override_city, $category, $real_rss_url, $OwnerID);
							$this->_propCount++;
							
						}
					}
				} else {
					$GLOBALS['app']->Session->PushLastResponse("There was a problem parsing the RSS feed for: ".$fetch_url.". Please make sure the RSS feed URL is entered correctly.", RESPONSE_ERROR);
					echo '<br />'."There was a problem parsing the RSS feed for: ".$fetch_url.". Please make sure the RSS feed URL is entered correctly.";
				}
			}
		} else {
			//return new Jaws_Error("An RSS feed URL was not given.", _t('PROPERTIES_NAME'));
			echo '<br />'."A feed URL was not given.";
		}

		// Delete properties not found in RSS feed
		if ($multifeed === false) {
			$sql = '
				SELECT [id], [category], [title], [item2]
				FROM [[property]]
				WHERE ([item2] <> "")';
			
			$params = array();
			$types = array(
				'integer', 'text', 'text', 'text'
			);
			$result = $GLOBALS['db']->queryAll($sql, $params, $types);
			if (Jaws_Error::IsError($result)) {
				//return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTIES_NOT_RETRIEVED'), _t('PROPERTIES_NAME'));
				echo '<br />'."Could not find the property to delete.";
			} else {
				foreach ($result as $res) {
					if (!in_array($res['item2'], $this->_newChecksums) && (int)$category == (int)$res['category']) {
						
						$delete = $this->DeleteProperty($res['id'], true);
						if (Jaws_Error::IsError($delete)) {
							$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_PROPERTY_NOT_DELETED'), RESPONSE_ERROR);
							//return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTY_NOT_DELETED'), _t('PROPERTIES_NAME'));
							echo '<br />COULD NOT DELETE: '.$res['title'].' ::: '.$res['item2']; 
						} else {
							echo '<br />DELETED: '.$res['title'].' ::: '.$res['item2']; 
						}
					}
				}
			}
		}

		// Get the victims and initiate that body count status
		$victims = func_get_args();
		$body_count = 0;   
	   
		// Kill those damn punks
		foreach($victims as $victim) {
			unset($victim);
			if (!isset($victim)) {
				$body_count++;
			}
		}
	   
		// How many kills did Rambo tally up on this mission?
		//echo ' ::: Removed '.$body_count.' variables';
		
		echo "<h1>Feed Imported Successfully</h1>";
		return true;
    }
	
	/**
     * Inserts array of property info
     *
     * @param   array  $item  Array of property info
     * @param   string  $override_city  City to force all properties to have in current category
     * @param   int  $category  ID of property parent
     * @param   string  $rss_url  RSS URL
     * @param   int  $OwnerID  Owner's ID
     * @access  public
     * @return  string   Response
     */
    function InsertRSSProperty($item, $override_city = '', $category = 1, $rss_url = '', $OwnerID = null)
    {
		ignore_user_abort(true); 
        set_time_limit(0);
		echo " ";
		ob_flush();
		flush();
		sleep(1);
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$model = $GLOBALS['app']->LoadGadget('Properties', 'Model');
		$key = "ABQIAAAAbHKtlYQg6w5AJ9d2_shprxRSdpoJbNiUEES6uLAQrtqeLOB2WBROsVk6Deve8XT-33SEgoVpReKw5Q";
		include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
		include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'XMLParser.php';
		$is_googleBase = false;
		$total = 0;
		$rss_property_link = '';
		$rss_virtual_tour = '';
		$Active = ($OwnerID == 0 || is_null($OwnerID) ? 'Y' : 'N');
		if (isset($item['link_self']) && !empty($item['link_self'])) {
			$rss_property_self = (strpos($item['link_self'], "http://") > 6 ? str_replace('http://', '', $item['link_self']) : $item['link_self']);
		} 
		if (isset($item['link']) && !empty($item['link'])) {
			$rss_property_link = (strpos($item['link'], 'http://', 1) > 6 ? substr($item['link'], 0, strpos($item['link'], 'http://', 1)) : $item['link']);
			if (substr($rss_property_link, -1) == '"') {
				$rss_property_link = substr($rss_property_link, 0, strlen($rss_property_link)-1);
			}
			// TODO: Support more Virtual Tour formats
			if (strpos($rss_property_link, 'tour.getmytour.com') !== false) {
				$rss_virtual_tour = $rss_property_link;
			}
		}
		
		// snoopy
		$snoopy = new Snoopy('Properties');
		$snoopy->agent = "Jaws";
		
		// Parse a location, if we can't then we won't add the property
		if (isset($item['g']['longitude']) && !empty($item['g']['longitude']) && isset($item['g']['latitude']) && !empty($item['g']['latitude'])) {
			$rss_location = $item['g']['latitude'].','.$item['g']['longitude'];
		} else if (isset($item['g']['location']) && !empty($item['g']['location'])) {
			$rss_location = $item['g']['location'];
		} else if (isset($item['complete_address']) && !empty($item['complete_address'])) {
			$rss_location = $item['complete_address'];
		} else if ($snoopy->fetch($rss_property_link)) {
			echo " ";
			ob_flush();
			flush();
			//echo $snoopy->results;
			if (strpos($snoopy->results, "<meta name=\"geo.position\" content=\"") !== false) {
				$inputStr = $snoopy->results;
				$delimeterLeft = "<meta name=\"geo.position\" content=\"";
				$delimeterRight = "\" />";
				$posLeft=strpos($inputStr, $delimeterLeft);
				$posLeft+=strlen($delimeterLeft);
				$posRight=strpos($inputStr, $delimeterRight, $posLeft);
				$rss_location = str_replace(";", ",", substr($inputStr, $posLeft, $posRight-$posLeft));
				unset($inputStr);
				unset($delimeterLeft);
				unset($delimeterRight);
				unset($posLeft);
				unset($posRight);
			}
			// TourBuzz Agent Name
			if (strpos($snoopy->results, "<span id=\"customerFullName\">") !== false) {
				$inputStr = $snoopy->results;
				$delimeterLeft = "<span id=\"customerFullName\">";
				$delimeterRight = "</span>";
				$posLeft=strpos($inputStr, $delimeterLeft);
				$posLeft+=strlen($delimeterLeft);
				$posRight=strpos($inputStr, $delimeterRight, $posLeft);
				$rss_agent = substr($inputStr, $posLeft, $posRight-$posLeft);
				unset($inputStr);
				unset($delimeterLeft);
				unset($delimeterRight);
				unset($posLeft);
				unset($posRight);
			}
			// TourBuzz Agent E-mail
			if (strpos($snoopy->results, "<a id=\"customerEmail\" href=\"mailto:") !== false) {
				$inputStr = $snoopy->results;
				$delimeterLeft = "<a id=\"customerEmail\" href=\"mailto:";
				$delimeterRight = "\"";
				$posLeft=strpos($inputStr, $delimeterLeft);
				$posLeft+=strlen($delimeterLeft);
				$posRight=strpos($inputStr, $delimeterRight, $posLeft);
				$rss_email = substr($inputStr, $posLeft, $posRight-$posLeft);
				unset($inputStr);
				unset($delimeterLeft);
				unset($delimeterRight);
				unset($posLeft);
				unset($posRight);
			}
			// TourBuzz Agent Phone
			if (strpos($snoopy->results, "<span id=\"customerContactPhone\">") !== false) {
				$inputStr = $snoopy->results;
				$delimeterLeft = "<span id=\"customerContactPhone\">";
				$delimeterRight = "</span>";
				$posLeft=strpos($inputStr, $delimeterLeft);
				$posLeft+=strlen($delimeterLeft);
				$posRight=strpos($inputStr, $delimeterRight, $posLeft);
				$rss_phone = substr($inputStr, $posLeft, $posRight-$posLeft);
				unset($inputStr);
				unset($delimeterLeft);
				unset($delimeterRight);
				unset($posLeft);
				unset($posRight);
			}
			// TourBuzz Website
			if (strpos($snoopy->results, "<a id=\"customerWebSite\" href=\"") !== false) {
				$inputStr = $snoopy->results;
				$delimeterLeft = "<a id=\"customerWebSite\" href=\"";
				$delimeterRight = "\"";
				$posLeft=strpos($inputStr, $delimeterLeft);
				$posLeft+=strlen($delimeterLeft);
				$posRight=strpos($inputStr, $delimeterRight, $posLeft);
				$rss_broker = str_replace('http://', '', substr($inputStr, $posLeft, $posRight-$posLeft));
				if (strpos(strtolower($rss_broker), 'www.') !== false) {
					$rss_broker = substr($rss_broker, 4, strlen($rss_broker));
				}
				if (strpos($rss_broker, '?') !== false || strlen($rss_broker) > 50) {
					$rss_broker = '';
				}
				unset($inputStr);
				unset($delimeterLeft);
				unset($delimeterRight);
				unset($posLeft);
				unset($posRight);
			}
		}
		
		if (isset($rss_location)) {
			usleep(200000);
			// snoopy
			$snoopy = new Snoopy('Properties');
			$snoopy->agent = "Jaws";
			$address = "http://maps.google.com/maps/geo?q=".urlencode($rss_location)."&output=xml&key=".$key;
			//echo '<br />Google Geocoder: '.$address;
			if($snoopy->fetch($address)) {
				echo " ";
				ob_flush();
				flush();
				$xml_content = $snoopy->results;
			
				// XML Parser
				$xml_parser = new XMLParser;
				$xml_result = $xml_parser->parse($xml_content, array("STATUS", "PLACEMARK"));
				//echo '<pre>';
				//var_dump($xml_result);
				//echo '</pre>';
				$moreImages = array();
				$rss_id = '';
				$rss_title = '';
				$rss_published = '';
				$rss_image = '';
				$rss_location = '';
				$rss_bedrooms = 0;
				$rss_bathrooms = 0;
				$rss_mls_listing_id = '';
				$rss_year = '';
				$rss_square_feet = '';
				$rss_listing_type = '';
				$rss_property_type = '';
				$rss_price = 0;
				$rss_lot_size = '';
				if (!isset($rss_email) || empty($rss_email)) {
					$rss_email = '';
				}
				if (!isset($rss_agent) || empty($rss_agent)) {
					$rss_agent = '';
				}
				if (!isset($rss_broker) || empty($rss_broker)) {
					$rss_broker = '';
				}
				$rss_broker_phone = '';
				if (!isset($rss_phone) || empty($rss_phone)) {
					$rss_phone = '';
				}
				$rss_status = 'forsale';
				$rss_city = '';
				$rss_address = '';
				$rss_region = '';
				$rss_country_id = 999999;
				$rss_postal_code = '';
				$rss_property_author = '';
				$rss_property_author_link = '';
				$rss_property_author_type = '';
				$rss_property_alt_title = '';
				$rss_property_alt_link = '';
				$rss_property_alt_type = '';
				$rss_coordinates = '';
				for ($i=0;$i<$xml_result[1]; $i++) {
					echo " ";
					ob_flush();
					flush();
					if (
						$xml_result[0][0]['CODE'] == '200' && 
						isset($xml_result[0][$i]['COUNTRYNAMECODE']) && 
						isset($xml_result[0][$i]['ADMINISTRATIVEAREANAME']) && 
						isset($xml_result[0][$i]['LOCALITYNAME']) && 
						isset($xml_result[0][$i]['ADDRESS']) && 
						isset($xml_result[0][$i]['COORDINATES']) && 
						empty($rss_coordinates)
					) {
						$sql = "SELECT [id] FROM [[country]] WHERE ([is_country] = 'Y') AND ([country_iso_code] = {iso_code})";
						$paramsc = array();
						$paramsc['iso_code'] = $xml_result[0][$i]['COUNTRYNAMECODE'];
						$country = $GLOBALS['db']->queryOne($sql, $paramsc);
						if (!Jaws_Error::IsError($country)) {
							$rss_country_id = $country;
						}	
						/*
						echo '<br />rss_country: ';
						var_dump($rss_country_id);
						echo '</pre>';
						*/
						unset($country);
						if ($rss_country_id != 999999) {
							$params = array();
							$sql = "SELECT [id] FROM [[country]] WHERE ([is_country] = 'N') AND ([parent] = ".$rss_country_id.") AND ([country_iso_code] = {iso_code})";
							$paramsc = array();
							$paramsc['iso_code'] = $xml_result[0][$i]['ADMINISTRATIVEAREANAME'];
							$region = $GLOBALS['db']->queryOne($sql, $paramsc);
							if (!Jaws_Error::IsError($region)) {
								$rss_region = $region;
							}	
							unset($region);
						}
						if ($override_city == '') {
							$rss_city = $xml_result[0][$i]['LOCALITYNAME'];
						}
						$rss_address = $xml_result[0][$i]['ADDRESS'];
						if (isset($xml_result[0][$i]['POSTALCODENUMBER'])) {
							$rss_postal_code = $xml_result[0][$i]['POSTALCODENUMBER'];
						}
						$rss_coordinates = $xml_result[0][$i]['COORDINATES'];
					}
				}
				
				if (trim($override_city) != '') {
					$rss_city = $override_city;
				}
				if ((!empty($rss_address) || (!empty($rss_city) && !empty($rss_region)))) {
					$rss_title = $item['title'];
					//$rss_title = str_replace($rss->items['source']['title'], '', $rss_title); 
					$rss_published = (isset($item['date_timestamp']) ? $item['date_timestamp'] : (isset($item['published']) ? $item['published'] : ''));
					if (isset($item['g']) && is_array($item['g'])) {
						if ($is_googleBase === false) {
							$is_googleBase = true;
						}
						//if (is_array($item['g']['image_link'])) {
						//	foreach(
						//} else {
							$rss_image = (isset($item['g']['image_link']) ? $item['g']['image_link'] : '');
						//}
					
						$rss_id = (isset($item['g']['id']) ? $item['g']['id'] : '');
						$rss_bedrooms = (isset($item['g']['bedrooms']) ? (float)$item['g']['bedrooms'] : 0);
						$rss_bathrooms = (isset($item['g']['bathrooms']) ? (float)$item['g']['bathrooms'] : 0);
						$rss_mls_listing_id = (isset($item['g']['mls_listing_id']) ? $item['g']['mls_listing_id'] : '');
						$rss_year = (isset($item['g']['year']) ? $item['g']['year'] : '');
						$rss_square_feet = (isset($item['g']['square_feet']) ? $item['g']['square_feet'] : '');
						$rss_listing_type = (isset($item['g']['listing_type']) ? $item['g']['listing_type'] : '');
						$rss_property_type = (isset($item['g']['property_type']) ? $item['g']['property_type'] : '');
						$rss_price = (isset($item['g']['price']) ? $item['g']['price'] : 0);
						$rss_lot_size = (isset($item['g']['lot_size']) ? $item['g']['lot_size'] : '');
						$rss_email = (isset($item['g']['email']) ? $item['g']['email'] : '');
						$rss_agent = (isset($item['g']['agent']) ? $item['g']['agent'] : '');
						$rss_broker = (isset($item['g']['broker']) ? $item['g']['broker'] : '');
						$rss_broker_phone = (isset($item['g']['broker_phone']) ? $item['g']['broker_phone'] : '');
						$rss_phone = (isset($item['g']['phone']) ? $item['g']['phone'] : '');
						$rss_property_author = (isset($item['g']['author_name']) ? $item['g']['author_name'] : '');
					} else {
						$rss_image = (isset($item['image']['url']) ? $item['image']['url'] : '');
						$rss_property_author = $rss_property_link;
					}
					if (!empty($rss_property_author)) {
						$rss_property_author = 'Source of this listing: '.$rss_property_author;
						$rss_property_author_type = 'E';
					}
					if (strpos(strtolower($rss_property_type), "rent") !== false) {
						$rss_status = "forrent";
					} else if (strpos(strtolower($rss_property_type), "lease") !== false) {
						$rss_status = "forlease";
					}
					if (isset($item['description']) && !empty($item['description'])) {
						$rss_description = $item['description'];
					} else if (isset($item['content']) && !empty($item['content'])) {
						$rss_description = $item['content'];
					} else if (isset($item['atom_content']) && !empty($item['atom_content'])) {
						$rss_description = $item['atom_content'];
					} else {
						$rss_description = (isset($item['g']['summary']) ? $item['g']['summary'] : '');
					}
					if (is_array($rss_description)) {
						$new_desc = '';
						foreach ($rss_description as $desc) {
							$new_desc .= ' '.$desc;
						}
						$rss_description = $new_desc;
					}
					$rss_description = strip_tags($rss_description, '<img><br>');
					if (strpos($rss_description, 'src="/www/db_images') !== false) {
						$rss_description = str_replace('src="/www/db_images', 'src="http://tour.getmytour.com/www/db_images', $rss_description);
					}
					$rss_description = str_replace('window.onerror=function(){return true;};', '', $rss_description);
					
					// send highest sort_order
					$params = array();
					$params['category'] = $category;
					$sql = "SELECT COUNT([prop_id]) FROM [[properties_parents]] WHERE ([parent_id] = {category})";
					$max = $GLOBALS['db']->queryOne($sql, $params);
					if (Jaws_Error::IsError($max)) {
						$GLOBALS['app']->Session->PushLastResponse($max->getMessage(), RESPONSE_ERROR);
						echo '<br />'.$max->getMessage();
						//return new Jaws_Error($max->getMessage(), _t('PROPERTIES_NAME'));
					} else {
						if (!isset($max)) {
							$max = (is_numeric($max) ? $max+1 : 0);
						} else {
							$max = $max+1;
						}
					}	
					
					if (!isset($total)) {
						$sql = 'SELECT COUNT([id]) FROM [[property]]';
						$res = $GLOBALS['db']->queryOne($sql);
						$total = (is_numeric($res) ? $res+1 : 1);
					} else {
						$total++;
					}
					if (isset($rss_property_self) && !empty($rss_property_self)) {
						$prop_checksum = md5($rss_property_self);
					} else if (isset($rss_property_link) && !empty($rss_property_link)) {
						$prop_checksum = md5($rss_property_link);
					} else if ($rss_id != '') {
						$prop_checksum = md5($rss_id);
					} else if ($rss_mls_listing_id != '') {
						$prop_checksum = md5($rss_mls_listing_id);
					} else if ($rss_coordinates != '') {
						$prop_checksum = md5($rss_coordinates);
					} else {
						$prop_checksum = md5($rss_address.', '.$rss_city.', '.$rss_region.', '.$rss_postal_code.', '.$rss_country_id);
					}
					$this->_newChecksums[] = $prop_checksum;
					$params = array();
					$params['checksum'] = $prop_checksum;

					$sql = 'SELECT [id] FROM [[property]] WHERE ([item2] = {checksum})';
					$found = $GLOBALS['db']->queryOne($sql, $params);
					
					if (is_numeric($found)) {
						
						$page = $model->GetProperty($found);
						if (Jaws_Error::isError($page)) {
							$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_PROPERTY_NOT_FOUND'), RESPONSE_ERROR);
							//return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTY_NOT_FOUND'), _t('PROPERTIES_NAME'));
							echo '<br />'._t('PROPERTIES_ERROR_PROPERTY_NOT_FOUND');
						} else if (isset($page['id']) && !empty($page['id'])) {
							$params               		= array();
							$sql = '
								UPDATE [[property]] SET
									[agent] = {agent},
									[agent_email] = {agent_email},
									[agent_phone] = {agent_phone},
									[broker] = {broker},
									[updated] = {now}';
							
							// Update img src in description, or add img tag if doesn't exist
							if ((isset($page['description']) && strpos($page['description'], 'src="') !== false) || (strpos($rss_description, 'src="') !== false && strpos($page['description'], 'src="') === false)) {	
								if (strpos($rss_description, 'src="') !== false) {
									$inputStr = $rss_description;
									$delimeterLeft = 'src="';
									$delimeterRight = '"';
									$posLeft=strpos($inputStr, $delimeterLeft);
									$posLeft+=strlen($delimeterLeft);
									$posRight=strpos($inputStr, $delimeterRight, $posLeft);
									$output = substr($inputStr, $posLeft, $posRight-$posLeft);
									if (strpos($page['description'], 'src="') !== false && strpos($page['description'], $output) === false) {
										$params['description'] = preg_replace('|src=\".*?\"|siu','src="'.$output.'"',$page['description']);
										$sql .= ',
											[description] = {description}';
									} else if (strpos($page['description'], $output) === false) {
										$inputStr = $rss_description;
										$delimeterLeft = '<img';
										$delimeterRight = '>';
										$posLeft=strpos($inputStr, $delimeterLeft);
										$posLeft+=strlen($delimeterLeft);
										$posRight=strpos($inputStr, $delimeterRight, $posLeft);
										$output = substr($inputStr, $posLeft, $posRight-$posLeft);
										$params['description'] = '<img'.$output.'>'.$page['description'];
										$sql .= ',
											[description] = {description}';
									}
								}
							}
							
							// Coordinates
							if (isset($rss_coordinates) && !empty($rss_coordinates)) {
								$sql .= ',
									[coordinates] = {coordinates}';
								$params['coordinates']      = $rss_coordinates;
							}
							
							$sql .= '
								WHERE [id] = {id}';

							MDB2::loadFile('Date');
							$params['id']         		= $found;
							$params['agent']         	= $xss->parse($rss_agent);
							$params['agent_email']      = $xss->parse($rss_email);
							$params['agent_phone']      = $xss->parse($rss_phone);
							$params['broker']      		= $xss->parse($rss_broker);
							$params['now']        		= $GLOBALS['db']->Date();

							$result = $GLOBALS['db']->query($sql, $params);
							if (Jaws_Error::IsError($result)) {
								//return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTY_NOT_UPDATED'), _t('PROPERTIES_NAME'));
								echo '<br />'._t('PROPERTIES_ERROR_PROPERTY_NOT_UPDATED');
							} else {
								if (($this->_propCount-1) >= 1) {
									echo '<style>#prop_'.($this->_propCount-1).' {display: none;}</style>';
									ob_flush();
									flush();
								}
								echo '<div id="prop_'.$this->_propCount.'"><br />Updating <b>'.$this->_propCount.' of '.$this->_propTotal.'</b> '.$rss_title.' ' . memory_get_usage() . '</div>';
								ob_flush();
								flush();
							}
						}
						unset($page);
							
					} else {
						// Add the property
						$result = $this->AddProperty(0, $max, $category, $rss_mls_listing_id, $xss->parse($rss_title), $xss->parse($rss_image), 
							'', $xss->parse($rss_description), $rss_address, $rss_city, $rss_region, $rss_postal_code, $rss_country_id, 
							'', '', '', $xss->parse($rss_price), 0, 0, 0, $rss_status, $xss->parse($rss_lot_size), 
							$xss->parse($rss_square_feet), $xss->parse($rss_bedrooms), $xss->parse($rss_bathrooms), '', $xss->parse($rss_virtual_tour), '', '', '', 
							'', '', '', '', '', '', $prop_checksum, '', '', '', 'N', 'Y', 'N', $OwnerID, 
							$Active, $total, 0, $xss->parse($rss_property_author_link), $xss->parse($rss_property_author), $rss_property_author_type, $xss->parse($rss_property_alt_link), 
							$xss->parse($rss_property_alt_title), $rss_property_alt_type, '', '', '', '', $xss->parse($rss_year), $rss_url, 
							$xss->parse($rss_agent), $xss->parse($rss_email), $xss->parse($rss_phone), $rss_property_link, '', $xss->parse($rss_broker), 
							'', $xss->parse($rss_broker_phone), $rss_property_link, '', $rss_coordinates, true);
						
						if (Jaws_Error::IsError($result)) {
							$GLOBALS['app']->Session->PushLastResponse($result->getMessage(), RESPONSE_ERROR);
							return new Jaws_Error($result->getMessage(), _t('PROPERTIES_NAME'));
							echo '<br />'.$result->getMessage();
							//$output_html .= "<br />ERROR: ".$result->getMessage();
						} else {
							if (($this->_propCount-1) >= 1) {
								echo '<style>#prop_'.($this->_propCount-1).' {display: none;}</style>';
								ob_flush();
								flush();
							}
							echo '<div id="prop_'.$this->_propCount.'"><br />Importing <b>'.$this->_propCount.' of '.$this->_propTotal.'</b> '.$rss_title.' ' . memory_get_usage() . '</div>';
							ob_flush();
							flush();
						}
						unset($result);
					}
					
					$params = array();
					$params['checksum'] = $prop_checksum;
					$sql = 'SELECT [id] FROM [[property]] WHERE ([item2] = {checksum})';
					$found = $GLOBALS['db']->queryOne($sql, $params);
					if (Jaws_Error::IsError($found) || !is_numeric($found)) {
						$GLOBALS['app']->Session->PushLastResponse('Property Not Added', RESPONSE_ERROR);
						if (($this->_propCount-1) >= 1) {
							echo '<style>#prop_'.($this->_propCount-1).' {display: none;}</style>';
							ob_flush();
							flush();
						}
						echo '<div><br />Property <b>'.$this->_propCount.' of '.$this->_propTotal.'</b> Not Added</div>';
						ob_flush();
						flush();
					}
					unset($found);
					unset($max);
					
					//ob_end_flush();
					//break;
				} else {
					$GLOBALS['app']->Session->PushLastResponse('Not geocoded', RESPONSE_ERROR);
					if (($this->_propCount-1) >= 1) {
						echo '<style>#prop_'.($this->_propCount-1).' {display: none;}</style>';
						ob_flush();
						flush();
					}
					echo '<div><br />Property <b>'.$this->_propCount.' of '.$this->_propTotal.'</b> could not be Geocoded</div>';
					ob_flush();
					flush();
				}
				
				unset($xml_parser);
				unset($xml_result);		
				unset($moreImages);
				unset($rss_id);
				unset($rss_title);
				unset($rss_published);
				unset($rss_image);
				unset($rss_location);
				unset($rss_bedrooms);
				unset($rss_bathrooms);
				unset($rss_mls_listing_id);
				unset($rss_year);
				unset($rss_square_feet);
				unset($rss_listing_type);
				unset($rss_property_type);
				unset($rss_price);
				unset($rss_lot_size);
				unset($rss_email);
				unset($rss_agent);
				unset($rss_broker);
				unset($rss_broker_phone);
				unset($rss_phone);
				unset($rss_status);
				unset($rss_city);
				unset($rss_address);
				unset($rss_region);
				unset($rss_country_id);
				unset($rss_postal_code);
				unset($rss_property_author);
				unset($rss_property_author_link);
				unset($rss_property_author_type);
				unset($rss_property_alt_title);
				unset($rss_property_alt_link);
				unset($rss_property_alt_type);
				unset($rss_coordinates);
				unset($rss_description);
				unset($prop_checksum);
				unset($model);
				unset($key);
				unset($is_googleBase);
				unset($total);
				unset($rss_property_link);
				unset($rss_virtual_tour);
				unset($rss_property_self);
				unset($snoopy);
				unset($xml_content);

				
			} else {
				$GLOBALS['app']->Session->PushLastResponse($rss_location.' could not be geocoded', RESPONSE_ERROR);
				echo '<br />'.$rss_location.' could not be geocoded';
				ob_flush();
				flush();
			}
			unset($rss_location);
		} else {
			$GLOBALS['app']->Session->PushLastResponse('Location could not be parsed', RESPONSE_ERROR);
			echo '<br />'.'Location could not be parsed';
			ob_flush();
			flush();
		}

		// Get the victims and initiate that body count status
		$victims = func_get_args();
		$body_count = 0;   
	   
		// Kill those damn punks
		foreach($victims as $victim) {
			unset($victim);
			if (!isset($victim)) {
				$body_count++;
			}
		}
	   
		// How many kills did Rambo tally up on this mission?
		//echo ' ::: Removed '.$body_count.' variables';
		  
		//ob_end_clean();
		//return $GLOBALS['app']->Session->PopLastResponse();
		return true;
	}
    
	/**
     * Saves Properties settings to the registry
     *
     * @param  string 	$showmap 	(Y/N) Show properties on map by default? 
     * @param  string 	$user_post_limit 	Limits number of posts users can add to properties.
     * @param  string 	$user_desc_char_limit 	Limits number of characters users can add to property descriptions.
     * @param  string 	$user_mask_owner_email 	(Y/N) Mask user's e-mail address on properties?
     * @param  string 	$user_min_price 	Minimum property prices user can add.
     * @param  string 	$user_max_price 	Maximum property prices user can add.
     * @param  string 	$user_status_limit 	Limit statuses user properties can have.
     * @param  string 	$randomize 	(Y/N) Randomize properties in category listings by default?
     * @param  string 	$showcalendar 	(Y/N) Show availability calendars by default?
     * @access  public
     * @return  array   Response
     */
    function SaveSettings(
		$showmap, $user_post_limit, $user_desc_char_limit, $user_mask_owner_email, 
		$user_min_price, $user_max_price, $user_status_limit, $randomize, $showcalendar
	) {
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		
		$GLOBALS['app']->Registry->LoadFile('Properties');
        $GLOBALS['app']->Registry->Set('/gadgets/Properties/showmap', $xss->parse($showmap));
        $GLOBALS['app']->Registry->Set('/gadgets/Properties/showcalendar', $xss->parse($showcalendar));
        $GLOBALS['app']->Registry->Set('/gadgets/Properties/randomize', $xss->parse($randomize));
        $GLOBALS['app']->Registry->Set('/gadgets/Properties/user_post_limit', (int)$user_post_limit);
        $GLOBALS['app']->Registry->Set('/gadgets/Properties/user_desc_char_limit', (int)$user_desc_char_limit);
        $GLOBALS['app']->Registry->Set('/gadgets/Properties/user_mask_owner_email', $xss->parse($user_mask_owner_email));
		if (!empty($user_min_price)) {
			$newstring = "";
			$array = str_split($user_min_price);
			foreach($array as $char) {
				if (($char >= '0' && $char <= '9') || $char == '.') {
					$newstring .= $char;
				}
			}
			$user_min_price = number_format($newstring, 2, '.', '');
		}
        $GLOBALS['app']->Registry->Set('/gadgets/Properties/user_min_price', $user_min_price);
		
		if (!empty($user_max_price)) {
			$newstring = "";
			$array = str_split($user_max_price);
			foreach($array as $char) {
				if (($char >= '0' && $char <= '9') || $char == '.') {
					$newstring .= $char;
				}
			}
			$user_max_price = number_format($newstring, 2, '.', '');
		}
        $GLOBALS['app']->Registry->Set('/gadgets/Properties/user_max_price', $user_max_price);
		$str = ""; 
		$comma = "";
        foreach ($user_status_limit as $Key => $Value) {
			$str .= $comma.$Value;
			$comma=",";
        }

        $GLOBALS['app']->Registry->Set('/gadgets/Properties/user_status_limit', $xss->parse($str));
		$GLOBALS['app']->Registry->Commit('Properties');
		if ($GLOBALS['app']->Registry->Get('/gadgets/Properties/showmap') == $showmap && 	
			$GLOBALS['app']->Registry->Get('/gadgets/Properties/showcalendar') == $showcalendar && 
			$GLOBALS['app']->Registry->Get('/gadgets/Properties/randomize') == $randomize && 
			$GLOBALS['app']->Registry->Get('/gadgets/Properties/user_post_limit') == $user_post_limit && 
			$GLOBALS['app']->Registry->Get('/gadgets/Properties/user_desc_char_limit') == $user_desc_char_limit && 
			$GLOBALS['app']->Registry->Get('/gadgets/Properties/user_mask_owner_email') == $user_mask_owner_email && 
			$GLOBALS['app']->Registry->Get('/gadgets/Properties/user_min_price') == $user_min_price && 
			$GLOBALS['app']->Registry->Get('/gadgets/Properties/user_max_price') == $user_max_price && 
			$GLOBALS['app']->Registry->Get('/gadgets/Properties/user_status_limit') == $str) {
			$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_KEY_SAVED'), RESPONSE_NOTICE);
        } else {
			$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_KEY_NOT_SAVED'), RESPONSE_ERROR);
			return false;
		}
		return true;
    }
	
    /**
     * Adds Property Calendar when a property is added
     *
     * @access  public
     * @param   int   $id  ID of the property that was added
     * @return  bool    Success/failure
     */
    function AddPropertyCalendar($id)
    {
		$result = array();
		$hook = $GLOBALS['app']->loadHook('Properties', 'Calendar');
		if ($hook !== false) {
			if (method_exists($hook, 'AddCalendar')) {
				$result = $hook->AddCalendar($id);
			}
		}
		return $result;
	}

    /**
     * Updates Property Calendar when a property is updated
     *
     * @access  public
     * @param   int   $id  ID of the property that was updated
     * @return  bool    Success/failure
     */
    function UpdatePropertyCalendar($id)
    {
		$result = array();
		$hook = $GLOBALS['app']->loadHook('Properties', 'Calendar');
		if ($hook !== false) {
			if (method_exists($hook, 'UpdateCalendar')) {
				$result = $hook->UpdateCalendar($id);
			}
		}
		return $result;
	}

    /**
     * Removes Property Calendar when a property is deleted
     *
     * @access  public
     * @param   int   $id  ID of the property that was deleted
     * @return  bool    Success/failure
     */
    function RemovePropertyCalendar($id)
    {
		$result = array();
		$hook = $GLOBALS['app']->loadHook('Properties', 'Calendar');
		if ($hook !== false) {
			if (method_exists($hook, 'DeleteCalendar')) {
				$result = $hook->DeleteCalendar($id);
			}
		}
		return $result;
	}

    /**
     * Updates a User's Properties
     *
     * @access  public
     * @param   int  $uid  User ID
     * @return  array   Response
     */
    function UpdateUserProperties($uid) 
    {
		$model = $GLOBALS['app']->LoadGadget('Properties', 'Model');
		$pages = $model->GetPropertiesOfUserID((int)$uid);
		require_once JAWS_PATH . 'include/Jaws/User.php';
		$jUser = new Jaws_User;
		$info = $jUser->GetUserInfoById((int)$uid, true);
		if (!Jaws_Error::IsError($pages) && !Jaws_Error::IsError($info)) {
			$params           	= array();
			$params['id']     	= $info['id'];
			if (!$info['enabled']) {
				$params['Active'] = 'N';
				$params['was'] = 'Y';
				$sql = '
					UPDATE [[property]] SET
						[active] = {Active}
					WHERE ([ownerid] = {id}) AND ([active] = {was})';

				$result = $GLOBALS['db']->query($sql, $params);
				if (Jaws_Error::IsError($result)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_USER_PROPERTIES_NOT_UPDATED'), RESPONSE_ERROR);
					return false;
				}
				foreach($pages as $p) {		            
					if ($p['active'] == 'Y') {
						$sql1 = "
							DELETE FROM [[properties_parents]]
								WHERE ([prop_id] = {prop_id})";
						
						$params1               		= array();
						$params1['prop_id']        	= $p['id'];

						$result1 = $GLOBALS['db']->query($sql1, $params1);
						if (Jaws_Error::IsError($result1)) {
							$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_PROPERTYPARENT_NOT_UNLINKED'), RESPONSE_ERROR);
							return false;
						}
					}
				}
			} else {
				$params['Active'] = 'Y';
				$params['was'] = 'N';
				$sql = '
					UPDATE [[property]] SET
						[active] = {Active}
					WHERE ([ownerid] = {id}) AND ([active] = {was})';

				$result = $GLOBALS['db']->query($sql, $params);
				if (Jaws_Error::IsError($result)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_USER_PROPERTIES_NOT_UPDATED'), RESPONSE_ERROR);
					return false;
				}
				foreach($pages as $p) {		            
					if ($p['active'] == 'N') {
						$sql1 = "
							DELETE FROM [[properties_parents]]
								WHERE ([prop_id] = {prop_id})";
						
						$params1               		= array();
						$params1['prop_id']        	= $p['id'];

						$result1 = $GLOBALS['db']->query($sql1, $params1);
						if (Jaws_Error::IsError($result1)) {
							$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_PROPERTYPARENT_NOT_UNLINKED'), RESPONSE_ERROR);
							return false;
						}
						$result = $storeAdminModel->UpdatePropertiesCategories($p['id']);
						if (Jaws_Error::IsError($result)) {
							$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_PROPERTYPARENT_NOT_UNLINKED'), RESPONSE_ERROR);
							return false;
						}
					}
				}
			}
			$sql2 = '
				UPDATE [[propertyparent]] SET
					[propertyparentactive] = {Active}
				WHERE ([propertyparentownerid] = {id}) AND ([propertyparentactive] = {was})';

			$result2 = $GLOBALS['db']->query($sql2, $params);
			if (Jaws_Error::IsError($result2)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_USER_PROPERTYPARENTS_NOT_UPDATED'), RESPONSE_ERROR);
				return false;
			}
			$sql3 = '
				UPDATE [[amenity_types]] SET
					[active] = {Active}
				WHERE ([ownerid] = {id}) AND ([active] = {was})';

			$result3 = $GLOBALS['db']->query($sql3, $params);
			if (Jaws_Error::IsError($result3)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_USER_AMENITYTYPES_NOT_UPDATED'), RESPONSE_ERROR);
				return false;
			}
			$sql4 = '
				UPDATE [[propertyamenity]] SET
					[active] = {Active}
				WHERE ([ownerid] = {id}) AND ([active] = {was})';

			$result4 = $GLOBALS['db']->query($sql4, $params);
			if (Jaws_Error::IsError($result4)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_USER_AMENITIES_NOT_UPDATED'), RESPONSE_ERROR);
				return false;
			}
			$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_USER_PROPERTIES_UPDATED'), RESPONSE_NOTICE);
			return true;
		} else {
			$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_USER_PROPERTIES_NOT_UPDATED'), RESPONSE_ERROR);
			return false;
		}
    }	
		
    /**
     * Deletes a User's Properties
     *
     * @access  public
     * @param   int  $uid  User ID
     * @return  array   Response
     */
    function RemoveUserProperties($uid) 
    {
		$model = $GLOBALS['app']->LoadGadget('Properties', 'Model');
		$parents = $model->GetPropertyParentsByUserID((int)$uid);
		if (!Jaws_Error::IsError($parents)) {
			foreach ($parents as $page) {
				$result = $this->DeletePropertyParent($page['id'], true);
				if (Jaws_Error::IsError($result)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_USER_PROPERTYPARENT_NOT_DELETED'), RESPONSE_ERROR);
					return false;
				}
			}
			$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_USER_PROPERTYPARENTS_DELETED'), RESPONSE_NOTICE);
		} else {
			$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_USER_PROPERTYPARENTS_NOT_DELETED'), RESPONSE_NOTICE);
			return false;
		}
		$properties = $model->GetPropertiesOfUserID((int)$uid);
		if (!Jaws_Error::IsError($properties)) {
			foreach ($properties as $page) {
				$result = $this->DeleteProperty($page['id'], true);
				if (Jaws_Error::IsError($result)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_USER_PROPERTY_NOT_DELETED'), RESPONSE_ERROR);
					return false;
				}
			}
			$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_USER_PROPERTIES_DELETED'), RESPONSE_NOTICE);
		} else {
			$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_USER_PROPERTIES_NOT_DELETED'), RESPONSE_NOTICE);
			return false;
		}
		$amenities = $model->GetAmenityTypes(null, 'title', 'ASC', false, (int)$uid);
		if (!Jaws_Error::IsError($amenities)) {
			foreach ($amenities as $page) {
				$result = $this->DeleteAmenityType($page['id'], true);
				if (Jaws_Error::IsError($result)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_USER_AMENITYTYPE_NOT_DELETED'), RESPONSE_ERROR);
					return false;
				}
			}
			$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_USER_AMENITYTYPES_DELETED'), RESPONSE_NOTICE);
		} else {
			$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_USER_AMENITYTYPES_NOT_DELETED'), RESPONSE_NOTICE);
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
		if ($gadget == 'Properties') {
			// Update checksum
			$config_key = $GLOBALS['app']->Registry->Get('/config/key');		
			
			$model = $GLOBALS['app']->LoadGadget('Properties', 'Model');
			$parents = $model->GetPropertyParents();
			if (Jaws_Error::IsError($parents)) {
				return false;
			}
			foreach ($parents as $parent) {
				if (empty($parent['propertyparentchecksum']) || is_null($parent['propertyparentchecksum']) || strpos($parent['propertyparentchecksum'], ':') === false) {
					$params               	= array();
					$params['id'] 			= $parent['propertyparentid'];
					$params['checksum'] 	= $parent['propertyparentid'].':'.$config_key;
					
					$sql = '
						UPDATE [[propertyparent]] SET
							[propertyparentchecksum] = {checksum}
						WHERE [propertyparentid] = {id}';

					$result = $GLOBALS['db']->query($sql, $params);
					if (Jaws_Error::IsError($result)) {
						$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
						return false;
					}

					// Let everyone know it has been added
					$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
					$res = $GLOBALS['app']->Shouter->Shout('onAddPropertyParent', $parent['propertyparentid']);
					if (Jaws_Error::IsError($res) || !$res) {
						return $res;
					}
				}
			}
			$posts = $model->GetAmenityTypes();
			if (Jaws_Error::IsError($posts)) {
				return false;
			}
			foreach ($posts as $parent) {
				if (empty($parent['checksum']) || is_null($parent['checksum']) || strpos($parent['checksum'], ':') === false) {
					$params               	= array();
					$params['id'] 			= $parent['id'];
					$params['checksum'] 	= $parent['id'].':'.$config_key;
					
					$sql = '
						UPDATE [[amenity_types]] SET
							[checksum] = {checksum}
						WHERE [id] = {id}';

					$result = $GLOBALS['db']->query($sql, $params);
					if (Jaws_Error::IsError($result)) {
						$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
						return false;
					}

					// Let everyone know it has been added
					$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
					$res = $GLOBALS['app']->Shouter->Shout('onAddPropertyAmenityType', $parent['id']);
					if (Jaws_Error::IsError($res) || !$res) {
						return $res;
					}
				}
			}
			$posts = $model->GetPropertyAmenities();
			if (Jaws_Error::IsError($posts)) {
				return false;
			}
			foreach ($posts as $parent) {
				if (empty($parent['checksum']) || is_null($parent['checksum']) || strpos($parent['checksum'], ':') === false) {
					$params               	= array();
					$params['id'] 			= $parent['id'];
					$params['checksum'] 	= $parent['id'].':'.$config_key;
					
					$sql = '
						UPDATE [[propertyamenity]] SET
							[checksum] = {checksum}
						WHERE [id] = {id}';

					$result = $GLOBALS['db']->query($sql, $params);
					if (Jaws_Error::IsError($result)) {
						$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
						return false;
					}

					// Let everyone know it has been added
					$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
					$res = $GLOBALS['app']->Shouter->Shout('onAddPropertyAmenity', $parent['id']);
					if (Jaws_Error::IsError($res) || !$res) {
						return $res;
					}
				}
			}
			$posts = $model->GetProperties();
			if (Jaws_Error::IsError($posts)) {
				return false;
			}
			foreach ($posts as $parent) {
				if (empty($parent['checksum']) || is_null($parent['checksum']) || strpos($parent['checksum'], ':') === false) {
					$params               	= array();
					$params['id'] 			= $parent['id'];
					$params['checksum'] 	= $parent['id'].':'.$config_key;
					
					$sql = '
						UPDATE [[property]] SET
							[item2] = {checksum}
						WHERE [id] = {id}';

					$result = $GLOBALS['db']->query($sql, $params);
					if (Jaws_Error::IsError($result)) {
						$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
						return false;
					}

					// Let everyone know it has been added
					$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
					$res = $GLOBALS['app']->Shouter->Shout('onAddProperty', $parent['id']);
					if (Jaws_Error::IsError($res) || !$res) {
						return $res;
					}
				}
				$posts1 = $model->GetAllPostsOfProperty($parent['id']);
				if (Jaws_Error::IsError($posts1)) {
					return false;
				}
				foreach ($posts1 as $post) {
					if (empty($post['checksum']) || is_null($post['checksum']) || strpos($post['checksum'], ':') === false) {
						$params               	= array();
						$params['id'] 			= $post['id'];
						$params['checksum'] 	= $post['id'].':'.$config_key;
						
						$sql = '
							UPDATE [[property_posts]] SET
								[checksum] = {checksum}
							WHERE [id] = {id}';

						$result = $GLOBALS['db']->query($sql, $params);
						if (Jaws_Error::IsError($result)) {
							$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
							return false;
						}

						// Let everyone know it has been added
						$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
						$res = $GLOBALS['app']->Shouter->Shout('onAddPropertyPost', $post['id']);
						if (Jaws_Error::IsError($res) || !$res) {
							return $res;
						}
					}
				}
			}
		}
		return true;
    }
}