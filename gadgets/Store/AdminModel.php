<?php
/**
 * Store Gadget
 *
 * @category   GadgetModel
 * @package    Store
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */

ini_set("memory_limit","100M");
ini_set("post_max_size","25M");
ini_set("upload_max_filesize","2M");
ini_set("max_execution_time","5000");

require_once JAWS_PATH . 'gadgets/Store/Model.php';
class StoreAdminModel extends StoreModel
{
    var $_Name = 'Store';
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
        $GLOBALS['app']->Shouter->NewShouter('Store', 'onAddProductParent');   	// trigger an action when we add a parent
        $GLOBALS['app']->Shouter->NewShouter('Store', 'onDeleteProductParent');	// trigger an action when we delete a parent
        $GLOBALS['app']->Shouter->NewShouter('Store', 'onUpdatePropertyParent');	// and when we update a parent..
        $GLOBALS['app']->Shouter->NewShouter('Store', 'onAddProduct');   			// trigger an action when we add a product
        $GLOBALS['app']->Shouter->NewShouter('Store', 'onDeleteProduct');			// trigger an action when we delete a product
        $GLOBALS['app']->Shouter->NewShouter('Store', 'onUpdateProduct');			// and when we update a product..
        $GLOBALS['app']->Shouter->NewShouter('Store', 'onAddProductPost');   		
        $GLOBALS['app']->Shouter->NewShouter('Store', 'onDeleteProductPost');		
        $GLOBALS['app']->Shouter->NewShouter('Store', 'onUpdateProductPost');		
        $GLOBALS['app']->Shouter->NewShouter('Store', 'onMassAddProductAttribute');   	
        $GLOBALS['app']->Shouter->NewShouter('Store', 'onMassDeleteProductAttribute');	
        $GLOBALS['app']->Shouter->NewShouter('Store', 'onMassUpdateProductAttribute');	
        $GLOBALS['app']->Shouter->NewShouter('Store', 'onAddProductAttribute');   	
        $GLOBALS['app']->Shouter->NewShouter('Store', 'onDeleteProductAttribute');	
        $GLOBALS['app']->Shouter->NewShouter('Store', 'onUpdateProductAttribute');	
        $GLOBALS['app']->Shouter->NewShouter('Store', 'onAddProductAttributeType');   	
        $GLOBALS['app']->Shouter->NewShouter('Store', 'onDeleteProductAttributeType');	
        $GLOBALS['app']->Shouter->NewShouter('Store', 'onUpdateProductAttributeType');	
        $GLOBALS['app']->Shouter->NewShouter('Store', 'onAddProductBrand');   	
        $GLOBALS['app']->Shouter->NewShouter('Store', 'onDeleteProductBrand');	
        $GLOBALS['app']->Shouter->NewShouter('Store', 'onUpdateProductBrand');	
        $GLOBALS['app']->Shouter->NewShouter('Store', 'onAddProductSale');   	
        $GLOBALS['app']->Shouter->NewShouter('Store', 'onDeleteProductSale');	
        $GLOBALS['app']->Shouter->NewShouter('Store', 'onUpdateProductSale');	

        $GLOBALS['app']->loadClass('Listener', 'Jaws_EventListener');
        $GLOBALS['app']->Listener->NewListener('Store', 'onAddProduct', 'ActivateProductsCategories');
        $GLOBALS['app']->Listener->NewListener('Store', 'onUpdateProduct', 'UpdateProductsCategories');
        $GLOBALS['app']->Listener->NewListener('Store', 'onDeleteUser', 'RemoveUserStore');
        $GLOBALS['app']->Listener->NewListener('Store', 'onUpdateUser', 'UpdateUserStore');
		$GLOBALS['app']->Listener->NewListener('Store', 'onAfterEnablingGadget', 'InsertDefaultChecksums');
        $GLOBALS['app']->Listener->NewListener('Store', 'onDeleteProduct', 'RemoveProductComments');
		
        $GLOBALS['app']->Registry->NewKey('/gadgets/Store/user_post_limit', 6);
        $GLOBALS['app']->Registry->NewKey('/gadgets/Store/user_price_limit', 0);
        $GLOBALS['app']->Registry->NewKey('/gadgets/Store/user_desc_char_limit', 650);
        $GLOBALS['app']->Registry->NewKey('/gadgets/Store/user_mask_owner_email', 'Y');
        $GLOBALS['app']->Registry->NewKey('/gadgets/Store/randomize', 'N');
        $GLOBALS['app']->Registry->NewKey('/gadgets/Store/default_display', 'list');
		/*
		if (!in_array('Store', explode(',', $GLOBALS['app']->Registry->Get('/gadgets/user_access_items')))) {
			if ($GLOBALS['app']->Registry->Get('/gadgets/user_access_items') == '') {
				$GLOBALS['app']->Registry->Set('/gadgets/user_access_items', 'Store');
			} else {
				$GLOBALS['app']->Registry->Set('/gadgets/user_access_items', $GLOBALS['app']->Registry->Get('/gadgets/user_access_items').',Store');
			}
		}
		*/
		
		//Create Jaws_User groups
        require_once JAWS_PATH . 'include/Jaws/User.php';
        $userModel = new Jaws_User;
        $userModel->addGroup('store_owners', false); //Don't check if it returns true or false
        $group = $userModel->GetGroupInfoByName('store_owners');
		if (isset($group['id']) && !empty($group['id'])) {
			$GLOBALS['app']->ACL->NewKey('/ACL/groups/'.$group['id'].'/gadgets/Store/OwnProduct', 'true');
        }
        //$userModel->addGroup('store_users', false); //Don't check if it returns true or false
        
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
        $tables = array('productbrand',
                        'sales',
						'productparent',
						'product',
						'product_posts',
						'productattribute',
						'attribute_types',
						'product_rss_hide',
						'products_parents');
        foreach ($tables as $table) {
            $result = $GLOBALS['db']->dropTable($table);
            if (Jaws_Error::IsError($result)) {
                $gName  = _t('STORE_NAME');
                $errMsg = _t('GLOBAL_ERROR_GADGET_NOT_UNINSTALLED', $gName);
                $GLOBALS['app']->Session->PushLastResponse($errMsg, RESPONSE_ERROR);
                return new Jaws_Error($errMsg, $gName);
            }
        }

        $GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
        $GLOBALS['app']->Shouter->DeleteShouter('Store', 'onAddProductParent');   		// trigger an action when we add a parent
        $GLOBALS['app']->Shouter->DeleteShouter('Store', 'onDeleteProductParent');		// trigger an action when we delete a parent
        $GLOBALS['app']->Shouter->DeleteShouter('Store', 'onUpdatePropertyParent');		// and when we update a parent..
        $GLOBALS['app']->Shouter->DeleteShouter('Store', 'onAddProduct');   			// trigger an action when we add a product
        $GLOBALS['app']->Shouter->DeleteShouter('Store', 'onDeleteProduct');			// trigger an action when we delete a product
        $GLOBALS['app']->Shouter->DeleteShouter('Store', 'onUpdateProduct');			// and when we update a product..
        $GLOBALS['app']->Shouter->DeleteShouter('Store', 'onAddProductPost');   		
        $GLOBALS['app']->Shouter->DeleteShouter('Store', 'onDeleteProductPost');		
        $GLOBALS['app']->Shouter->DeleteShouter('Store', 'onUpdateProductPost');		
        $GLOBALS['app']->Shouter->DeleteShouter('Store', 'onMassAddProductAttribute');   	
        $GLOBALS['app']->Shouter->DeleteShouter('Store', 'onMassDeleteProductAttribute');	
        $GLOBALS['app']->Shouter->DeleteShouter('Store', 'onMassUpdateProductAttribute');	
        $GLOBALS['app']->Shouter->DeleteShouter('Store', 'onAddProductAttribute');   	
        $GLOBALS['app']->Shouter->DeleteShouter('Store', 'onDeleteProductAttribute');	
        $GLOBALS['app']->Shouter->DeleteShouter('Store', 'onUpdateProductAttribute');	
        $GLOBALS['app']->Shouter->DeleteShouter('Store', 'onAddProductAttributeType');   	
        $GLOBALS['app']->Shouter->DeleteShouter('Store', 'onDeleteProductAttributeType');	
        $GLOBALS['app']->Shouter->DeleteShouter('Store', 'onUpdateProductAttributeType');	
        $GLOBALS['app']->Shouter->DeleteShouter('Store', 'onAddProductBrand');   	
        $GLOBALS['app']->Shouter->DeleteShouter('Store', 'onDeleteProductBrand');	
        $GLOBALS['app']->Shouter->DeleteShouter('Store', 'onUpdateProductBrand');	
        $GLOBALS['app']->Shouter->DeleteShouter('Store', 'onAddProductSale');   	
        $GLOBALS['app']->Shouter->DeleteShouter('Store', 'onDeleteProductSale');	
        $GLOBALS['app']->Shouter->DeleteShouter('Store', 'onUpdateProductSale');	

        $GLOBALS['app']->loadClass('Listener', 'Jaws_EventListener');
        $GLOBALS['app']->Listener->DeleteListener('Store', 'AddOwnedProduct');
        $GLOBALS['app']->Listener->DeleteListener('Store', 'RemoveOwnedProduct');
        $GLOBALS['app']->Listener->DeleteListener('Store', 'ActivateProductsCategories');
        $GLOBALS['app']->Listener->DeleteListener('Store', 'UpdateProductsCategories');
        $GLOBALS['app']->Listener->DeleteListener('Store', 'RemoveUserStore');
        $GLOBALS['app']->Listener->DeleteListener('Store', 'UpdateUserStore');
		$GLOBALS['app']->Listener->DeleteListener('Store', 'InsertDefaultChecksums');
        $GLOBALS['app']->Listener->DeleteListener('Store', 'RemoveProductComments');

        $GLOBALS['app']->Registry->DeleteKey('/gadgets/Store/user_post_limit');
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/Store/user_price_limit');
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/Store/user_desc_char_limit');
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/Store/user_mask_owner_email');
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/Store/randomize');
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/Store/default_display');
		/*
		if (in_array('Store', explode(',', $GLOBALS['app']->Registry->Get('/gadgets/user_access_items')))) {
			if ($GLOBALS['app']->Registry->Get('/gadgets/user_access_items') == 'Store') {
				$GLOBALS['app']->Registry->Set('/gadgets/user_access_items', '');
			} else {
				$GLOBALS['app']->Registry->Set('/gadgets/user_access_items', str_replace(',Store', '', $GLOBALS['app']->Registry->Get('/gadgets/user_access_items')));
			}
		}
		*/
		
		//Delete Jaws_User groups
        require_once JAWS_PATH . 'include/Jaws/User.php';
        $userModel = new Jaws_User;
        $group = $userModel->GetGroupInfoByName('store_owners');
		if (isset($group['id']) && !empty($group['id'])) {
			$userModel->DeleteGroup($group['id']);
			$GLOBALS['app']->ACL->DeleteKey('/ACL/groups/'.$group['id'].'/gadgets/Store/OwnProduct');
		}
        /*
		$group = $userModel->GetGroupInfoByName('store_users');
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
			$result = $this->installSchema('0.1.1.xml', '', '0.1.0.xml');
			if (Jaws_Error::IsError($result)) {
				return $result;
			}
		}
		
        if (version_compare($old, '0.1.2', '<')) {			
			$result = $this->installSchema('schema.xml', '', '0.1.1.xml');
			if (Jaws_Error::IsError($result)) {
				return $result;
			}
			if (!Jaws_Utils::is_writable(JAWS_DATA)) {
				return new Jaws_Error(_t('GLOBAL_ERROR_FAILED_DIRECTORY_UNWRITABLE', JAWS_DATA));
			}

			$new_dir = JAWS_DATA . 'templates' . DIRECTORY_SEPARATOR;
			if (!Jaws_Utils::mkdir($new_dir)) {
				return new Jaws_Error(_t('GLOBAL_ERROR_FAILED_CREATING_DIR', $new_dir), _t('STORE_NAME'));
			}
			$new_Store_dir = JAWS_DATA . 'templates' . DIRECTORY_SEPARATOR. 'Store' . DIRECTORY_SEPARATOR;
			if (!Jaws_Utils::mkdir($new_Store_dir)) {
				return new Jaws_Error(_t('GLOBAL_ERROR_FAILED_CREATING_DIR', $new_Store_dir), _t('STORE_NAME'));
			}
			$new_product_dir = JAWS_DATA . 'templates' . DIRECTORY_SEPARATOR. 'Store' . DIRECTORY_SEPARATOR. 'product' . DIRECTORY_SEPARATOR;
			if (!Jaws_Utils::mkdir($new_product_dir)) {
				return new Jaws_Error(_t('GLOBAL_ERROR_FAILED_CREATING_DIR', $new_product_dir), _t('STORE_NAME'));
			}
			$new_attribute_types_dir = JAWS_DATA . 'templates' . DIRECTORY_SEPARATOR. 'Store' . DIRECTORY_SEPARATOR. 'attribute_types' . DIRECTORY_SEPARATOR;
			if (!Jaws_Utils::mkdir($new_attribute_types_dir)) {
				return new Jaws_Error(_t('GLOBAL_ERROR_FAILED_CREATING_DIR', $new_attribute_types_dir), _t('STORE_NAME'));
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
     * Create product categories.
     *
     * @category 	feature
     * @param   int  $productparentsort_order 	Priority order
     * @param   int  $productparentParent 	Parent ID
     * @param   string  $productparentCategory_Name    The title of the product category
     * @param   string  $productparentDescription    The description of the product category
     * @param   string  $productparentImage    Image
     * @param   string  $productparentFeatured         (Y/N) If the product category is featured
     * @param   string  $productparentActive         (Y/N) If the product category is active
     * @param   int  $productparentOwnerID         The poster's user ID
     * @param   string  $productparentRss_url         RSS URL
     * @param   string  $productparenturl_type         URL type of product category image (imageviewer/internal/external)
     * @param   string  $productparentinternal_url 	Internal URL to link product category image
     * @param   string  $productparentexternal_url 	External URL to link product category image
     * @param   string  $productparenturl_target 	URL target of product category image (_self/_blank)
     * @param   string  $productparentimage_code 	Custom HTML code of category
     * @param   string  $productparentchecksum         Unique ID
     * @param   boolean 	$auto 	If it's auto saved or not
     * @access  public
     * @return  bool    Success/failure
     * @TODO  Add create_menu flag
     */   
	function AddProductParent(
		$productparentsort_order, $productparentParent = null, $productparentCategory_Name, 
		$productparentDescription = '', $productparentImage = '', $productparentFeatured = 'N',
		$productparentActive = 'Y', $productparentOwnerID = null, $productparentRss_url = '', 
		$productparenturl_type = 'imageviewer', $productparentinternal_url = '', 
		$productparentexternal_url = '', $productparenturl_target = '_self', 
		$productparentimage_code = '', $productparentchecksum = '', $auto = false
	) {
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$model = $GLOBALS['app']->LoadGadget('Store', 'Model');
		$request =& Jaws_Request::getInstance();
		$action = $request->get('action', 'get');
		if (empty($action)) {
			$action = $request->get('action', 'post');
		}

		// If the checksum is found, don't add it.
		$pages = $model->GetProductParents();
		if (!Jaws_Error::IsError($pages)) {
			foreach($pages as $p) {		            
				if (!empty($productparentchecksum)) {
					if ($p['productparentchecksum'] == $productparentchecksum) {
						return true;
					}
				}
			}
		}
		
		if (empty($productparentCategory_Name)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_INVALID_TITLE'), RESPONSE_ERROR);
			require_once JAWS_PATH . 'include/Jaws/Header.php';
			if (BASE_SCRIPT != 'index.php') {
				Jaws_Header::Location(BASE_SCRIPT . '?gadget=Store&action=form');
			} else {
				Jaws_Header::Location('index.php?gadget=Store&action=account_form');
			}
		}
		
		$sql = "
            INSERT INTO [[productparent]]
                ([productparentparent], [productparentsort_order], [productparentcategory_name], 
				[productparentimage], [productparentdescription], [productparentactive], 
				[productparentownerid], [productparentcreated], [productparentupdated], 
				[productparentfeatured], [productparentfast_url], [productparentrss_url],
				[productparenturl], [productparenturl_target], [productparentimage_code], [productparentchecksum])
            VALUES
                ({productparentParent}, {productparentsort_order}, {productparentCategory_Name}, 
				{productparentImage}, {productparentDescription}, {productparentActive}, 
				{productparentOwnerID}, {now}, {now}, {productparentFeatured}, 
				{productparentFast_url}, {productparentRss_url}, 
				{productparenturl}, {productparenturl_target}, {productparentimage_code}, {productparentchecksum})";
				
		$productparentOwnerID = (!is_null($productparentOwnerID) ? (int)$productparentOwnerID : 0);

		if (!empty($productparentimage_code) && !empty($productparentImage)) {
			$productparentImage = '';
			$productparenturl_type = 'imageviewer';
		}
		
		if (
			$productparentOwnerID == 0 && 
			$productparenturl_type == 'external' && 
			substr(strtolower(trim($productparentexternal_url)), 0, 4) == 'http' && 
			strpos(strtolower(trim(urldecode($productparentexternal_url))), 'javascript:') === false
		) {
			$productparenturl = $xss->parse($productparentexternal_url);
		} else if (
			$productparenturl_type == 'internal' && 
			!empty($productparentinternal_url) && 
			strpos(strtolower(trim(urldecode($productparentinternal_url))), 'javascript:') === false
		) {
			$productparenturl = $xss->parse($productparentinternal_url);
		} else if ($productparenturl_type == 'imageviewer') {
			$productparenturl = "javascript:void(0);";
		} else if (empty($productparentimage_code)) {
	        $GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_INVALID_URL'), RESPONSE_ERROR);
            return new Jaws_Error(_t('STORE_ERROR_INVALID_URL'), _t('STORE_NAME'));
		}

        $productparenturl = !empty($productparenturl) ? $productparenturl : '';
        $productparenturl_target = !empty($productparenturl_target) ? $xss->parse($productparenturl_target) : '';
		$productparentImage = $this->cleanImagePath($productparentImage);
		
		$config_key = $GLOBALS['app']->Registry->Get('/config/key');		
		if (!empty($productparentImage) && (empty($productparentchecksum) || strpos($productparentchecksum, $config_key) !== false)) {
			if (
				$productparentOwnerID > 0 && 
				(substr(strtolower(trim($productparentImage)), 0, 4) == 'http' || 
				substr(strtolower(trim($productparentImage)), 0, 2) == '//' || 
				substr(strtolower(trim($productparentImage)), 0, 2) == '\\\\')
			) {
				$productparentImage = '';
			}
		}
		
		// Get the fast url
		$productparentFast_url = strip_tags($GLOBALS['app']->UTF8->str_replace(',', '', $productparentCategory_Name));
        $productparentFast_url = $this->GetRealFastUrl(
			$productparentFast_url, 'productparent', true, 'productparentfast_url'
		);
        				
		$productparentimage_code = ($productparentOwnerID > 0 ? htmlspecialchars($productparentimage_code) : '');
		$productparentParent = (!is_null($productparentParent) ? (int)$productparentParent : 0);
		$productparentDescription = strip_tags($productparentDescription, '<p><a><b><em><i><strong><div><table><tbody><thead><tr><td><span><font><hr><br><marquee><ul><li>');
		$productparentRss_url = (!is_null($productparentRss_url) && !empty($productparentRss_url) ? $xss->parse(strip_tags($productparentRss_url)) : null);
		$productparentCategory_Name = $GLOBALS['app']->UTF8->str_replace(',', '', strip_tags($productparentCategory_Name));
		
        $params               					= array();
		$params['productparentParent'] 			= $productparentParent;
		$params['productparentsort_order'] 		= (int)$productparentsort_order;
		$params['productparentCategory_Name'] 	= $xss->parse($productparentCategory_Name);
		$params['productparentImage'] 			= $xss->parse(strip_tags($productparentImage));
		$params['productparentDescription'] 	= str_replace("\r\n", "\n", $productparentDescription);
		$params['productparentActive'] 			= $xss->parse($productparentActive);
		$params['productparentFeatured'] 		= $xss->parse($productparentFeatured);
		$params['productparentFast_url'] 		= $xss->parse($productparentFast_url);
		$params['productparentRss_url'] 		= $productparentRss_url;
		$params['productparentOwnerID'] 		= $productparentOwnerID;
        $params['productparenturl']				= $productparenturl;
		$params['productparenturl_target']		= $xss->parse($productparenturl_target);
		$params['productparentimage_code']   	= str_replace("\r\n", "\n", $productparentimage_code);
		$params['productparentchecksum']   		= $productparentchecksum;
        $params['now']	 						= $GLOBALS['db']->Date();
		
		$result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_PRODUCTPARENT_NOT_ADDED'), RESPONSE_ERROR);
            return $result;
        }
        $newid = $GLOBALS['db']->lastInsertID('productparent', 'productparentid');

/*
		if (
			(empty($productparentchecksum) || 
			strpos($productparentchecksum, $config_key) === false) && 
			(BASE_SCRIPT != 'index.php' || $action == 'UpdateRSSStore')
		) {
			// add Menu Item for Page
			$visible = ($productparentActive == 'Y') ? 1 : 0;
			$url = $GLOBALS['app']->Map->GetURLFor('Store', 'Category', array('id' => $xss->parse($productparentFast_url)));
			
			// get productparentParent's group id
			$parentGid = 1;
			if ($productparentParent > 0) {
				$sql  = 'SELECT [gid] FROM [[menus]] WHERE [id] = {id}';
				$gid = $GLOBALS['db']->queryRow($sql, array('id' => $productparentParent));
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
							$productparentParent, 
							$parentGid, 
							'Store', 
							$xss->parse(strip_tags($GLOBALS['app']->UTF8->str_replace(',', '', $productparentCategory_Name))), 
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
*/		
		if (empty($productparentchecksum)) {
			// Update checksum
			$params               	= array();
			$params['id'] 			= $newid;
			$params['checksum'] 	= $newid.':'.$config_key;
			
			$sql = '
				UPDATE [[productparent]] SET
					[productparentchecksum] = {checksum}
				WHERE [productparentid] = {id}';

			$result = $GLOBALS['db']->query($sql, $params);
			if (Jaws_Error::IsError($result)) {
				$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
				return false;
			}
		}
		
		// Let everyone know it has been added
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onAddProductParent', $newid);
		if (Jaws_Error::IsError($res) || !$res) {
			$GLOBALS['app']->Session->PushLastResponse((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_PRODUCTPARENT_NOT_ADDED')), RESPONSE_ERROR);
			return new Jaws_Error((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_PRODUCTPARENT_NOT_ADDED')), _t('STORE_NAME'));
		}
        $GLOBALS['app']->Session->PushLastResponse(_t('STORE_PRODUCTPARENT_CREATED'), RESPONSE_NOTICE);
        return $newid;
    }

    /**
     * Updates a product parent.
     *
     * @param   int     $id             The ID of the page to update.
     * @param   int  $productparentParent 	Parent ID
     * @param   string  $productparentCategory_Name    The title of the product category
     * @param   int  $productparentsort_order 	Priority order
     * @param   string  $productparentDescription    The description of the product category
     * @param   string  $productparentImage    Image
     * @param   string  $productparentFeatured         (Y/N) If the product category is featured
     * @param   string  $productparentActive         (Y/N) If the product category is active
     * @param   string  $productparentRss_url         RSS URL
     * @param   string  $productparenturl_type         URL type of product category image (imageviewer/internal/external)
     * @param   string  $productparentinternal_url 	Internal URL to link product category image
     * @param   string  $productparentexternal_url 	External URL to link product category image
     * @param   string  $productparenturl_target 	URL target of product category image (_self/_blank)
     * @param   string  $productparentimage_code 	Custom HTML code of category
     * @param   boolean 	$auto 	If it's auto saved or not
     * @access  public
     * @return  boolean Success/failure
     */
	function UpdateProductParent(
		$productparentID, $productparentParent, $productparentCategory_Name,  
		$productparentsort_order, $productparentDescription, $productparentImage, 
		$productparentFeatured, $productparentActive, $productparentRss_url, 
		$productparenturl_type, $productparentinternal_url, $productparentexternal_url, 
		$productparenturl_target = '_self', $productparentimage_code, $auto = false
	) {
		require_once JAWS_PATH . 'include/Jaws/Header.php';
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$model = $GLOBALS['app']->LoadGadget('Store', 'Model');
		$request =& Jaws_Request::getInstance();
		$action = $request->get('action', 'get');
		if (empty($action)) {
			$action = $request->get('action', 'post');
		}
		if (empty($productparentCategory_Name)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_INVALID_TITLE'), RESPONSE_ERROR);
			if (BASE_SCRIPT != 'index.php') {
				Jaws_Header::Location(BASE_SCRIPT . '?gadget=Store&action=form&id='.$productparentID);
			} else {
				Jaws_Header::Location('index.php?gadget=Store&action=account_form&id='.$productparentID);
			}
		}
        
        $page = $model->GetProductParent($productparentID);
        if (Jaws_Error::isError($page) || !isset($page['productparentid'])) {
            $GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_PRODUCTPARENT_NOT_FOUND'), RESPONSE_ERROR);
			if (BASE_SCRIPT != 'index.php') {
				Jaws_Header::Location(BASE_SCRIPT . '?gadget=Store&action=form&id='.$productparentID);
			} else {
				Jaws_Header::Location('index.php?gadget=Store&action=account_form&id='.$productparentID);
			}
        }
        
		$params = array();
		$sql = '
            UPDATE [[productparent]] SET
				';
		if (!is_null($productparentParent)) {
			$params['productparentParent'] = (int)$productparentParent;
			$sql .= '[productparentparent] = {productparentParent},
			';
		}
		$sql .= '[productparentsort_order] = {productparentsort_order},
				[productparentcategory_name] = {productparentCategory_Name},
				[productparentimage] = {productparentImage},
				[productparentdescription] = {productparentDescription},
				[productparentactive] = {productparentActive},
				[productparentupdated] = {now},
				[productparentfeatured] = {productparentFeatured},
				[productparentfast_url] = {productparentFast_url},
				[productparentrss_url] = {productparentRss_url},
				[productparenturl] = {productparenturl},
				[productparenturl_target] = {productparenturl_target},
				[productparentimage_code] = {productparentimage_code}
			WHERE [productparentid] = {productparentID}';
		
		$productparentID = $page['productparentid'];
		
		if (!empty($productparentimage_code) && !empty($productparentImage)) {
			$productparentImage = '';
			$productparenturl_type = 'imageviewer';
		}
        
		$productparenturl = !empty($productparenturl) ? $productparenturl : '';
        $productparenturl_target = !empty($productparenturl_target) ? $productparenturl_target : '';
        
		// Get the fast url
		$productparentFast_url = strip_tags($GLOBALS['app']->UTF8->str_replace(',', '', $productparentCategory_Name));
        $productparentFast_url = $this->GetRealFastUrl(
			$productparentFast_url, 'productparent', true, 'productparentfast_url', 'productparentid', $productparentID
		);
        
		//Current fast url changes?
		$oldfast_url = '';
        if ($page['productparentfast_url'] != $productparentFast_url && $auto === false) {
            $oldfast_url = $page['productparentfast_url'];
        }
		
		if (
			$page['productparentownerid'] == 0 && 
			$productparenturl_type == 'external' && 
			substr(strtolower(trim($productparentexternal_url)), 0, 4) == 'http' && 
			strpos(strtolower(trim(urldecode($productparentexternal_url))), 'javascript:') === false
		) {
			$productparenturl = $xss->parse($productparentexternal_url);
		} else if (
			$productparenturl_type == 'internal' && 
			!empty($productparentinternal_url) && 
			strpos(strtolower(trim(urldecode($productparentinternal_url))), 'javascript:') === false
		) {
			$productparenturl = $xss->parse($productparentinternal_url);
		} else if ($productparenturl_type == 'imageviewer') {
			$productparenturl = "javascript:void(0);";
		} else if (empty($productparentimage_code)) {
	        $GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_INVALID_URL'), RESPONSE_ERROR);
            return new Jaws_Error(_t('STORE_ERROR_INVALID_URL'), _t('STORE_NAME'));
		}

		$productparentImage = $this->cleanImagePath($productparentImage);
		
		$config_key = $GLOBALS['app']->Registry->Get('/config/key');		
		if (!empty($productparentImage) && strpos($page['productparentchecksum'], $config_key) !== false) {
			if (
				$page['productparentownerid'] > 0 && 
				(substr(strtolower(trim($productparentImage)), 0, 4) == 'http' || 
				substr(strtolower(trim($productparentImage)), 0, 2) == '//' || 
				substr(strtolower(trim($productparentImage)), 0, 2) == '\\\\')
			) {
				$productparentImage = '';
			}
		}

		$productparentimage_code = ($page['productparentownerid'] > 0 ? htmlspecialchars($productparentimage_code) : '');
		$productparentDescription = strip_tags($productparentDescription, '<p><a><b><em><i><strong><div><table><tbody><thead><tr><td><span><font><hr><br><marquee><ul><li>');
		$productparentRss_url = (!is_null($productparentRss_url) && !empty($productparentRss_url) ? $xss->parse(strip_tags($productparentRss_url)) : null);
		$productparentCategory_Name = $GLOBALS['app']->UTF8->str_replace(',', '', strip_tags($productparentCategory_Name));
        
        $params['productparentID']         		= $productparentID;
		$params['productparentsort_order'] 		= (int)$productparentsort_order;
		$params['productparentCategory_Name'] 	= $xss->parse($productparentCategory_Name);
		$params['productparentImage'] 			= $xss->parse(strip_tags($productparentImage));
		$params['productparentDescription'] 	= str_replace("\r\n", "\n", $productparentDescription);
		$params['productparentActive'] 			= $xss->parse($productparentActive);
		$params['productparentFeatured'] 		= $xss->parse($productparentFeatured);
		$params['productparentFast_url'] 		= $xss->parse($productparentFast_url);
		$params['productparentRss_url'] 		= $productparentRss_url;
        $params['productparenturl']				= $productparenturl;
		$params['productparenturl_target']		= $xss->parse($productparenturl_target);
		$params['productparentimage_code']   	= str_replace("\r\n", "\n", $productparentimage_code);
        $params['now'] 							= $GLOBALS['db']->Date();		

        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
			//$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_PRODUCTPARENT_NOT_UPDATED'), RESPONSE_ERROR);
			$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
            return $result;
        }
										
		if (strpos($page['productparentchecksum'], $config_key) !== false && (BASE_SCRIPT != 'index.php' || $action == 'UpdateRSSStore')) {
			// update Menu Item for Page
			$visible = ($productparentActive == 'Y') ? 1 : 0;
			// if old title is different, update menu item
			if (!empty($oldfast_url)) {
				$old_url = $GLOBALS['app']->Map->GetURLFor('Store', 'Category', array('id' => $oldfast_url));
			} else {
				$old_url = $GLOBALS['app']->Map->GetURLFor('Store', 'Category', array('id' => $xss->parse($productparentFast_url)));
			}
			$new_url = $GLOBALS['app']->Map->GetURLFor('Store', 'Category', array('id' => $xss->parse($productparentFast_url)));
			
			// get productparentParent's group id
			$parentGid = 1;
			$productparentParent = (!is_null($productparentParent) ? (int)$productparentParent : $page['productparentparent']);
			if ($productparentParent > 0) {
				$sql  = 'SELECT [gid] FROM [[menus]] WHERE [id] = {id}';
				$gid = $GLOBALS['db']->queryRow($sql, array('id' => $productparentParent));
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
						$productparentParent, 
						$parentGid, 
						'Store', 
						$xss->parse(strip_tags($GLOBALS['app']->UTF8->str_replace(',', '', $productparentCategory_Name))), 
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
/*
			} else {
				// add Menu Item for Page
				$visible = ($productparentActive == 'Y') ? 1 : 0;
				$url = $GLOBALS['app']->Map->GetURLFor('Store', 'Category', array('id' => $xss->parse($productparentFast_url)));
				
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
								$productparentParent, 
								$parentGid, 
								'Store', 
								$xss->parse(strip_tags($GLOBALS['app']->UTF8->str_replace(',', '', $productparentCategory_Name))), 
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
*/
			}
		}

		// Insert or Update Scheduler to run given URL for UpdateRSSStore every 12 hours, starting now 
		$params = array();
		$params['scriptpath'] = $GLOBALS['app']->getSiteURL() . '/index.php?gadget=Store&action=UpdateRSSStore&id='.$productparentID;
		$sql = 'SELECT [id] FROM [phpjobscheduler] WHERE ([scriptpath] = {scriptpath})';
		$found = $GLOBALS['db']->queryOne($sql, array(
			'scriptpath' => $GLOBALS['app']->getSiteURL() . '/index.php?gadget=Store&action=UpdateRSSStore&id='.$productparentID
		));
		
		$scheduler = true;
		if (is_numeric($found)) {
			if (!empty($productparentRss_url)) {
				$scheduler = $GLOBALS['app']->updateScheduler($found, 43200, strtotime("now"), 0);
			} else {
				$scheduler = $GLOBALS['app']->deleteScheduler($found);
			}
		} else {
			// Insert RSS Products into product table
			if (!empty($productparentRss_url)) {
				$scheduler = $GLOBALS['app']->insertScheduler(
					$GLOBALS['app']->getSiteURL() . '/index.php?gadget=Store&action=UpdateRSSStore&id='.$productparentID, 43200, strtotime("now"), 0
				);
			}
		}
		if (Jaws_Error::IsError($scheduler)) {
			return new Jaws_Error($scheduler->GetMessage(), _t('STORE_NAME'));
		}

		// Let everyone know it has been updated
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onUpdateProductParent', $productparentID);
		if (Jaws_Error::IsError($res) || !$res) {
			$GLOBALS['app']->Session->PushLastResponse((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_PRODUCTPARENT_NOT_UPDATED')), RESPONSE_ERROR);
			return new Jaws_Error((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_PRODUCTPARENT_NOT_UPDATED')), _t('STORE_NAME'));
		}
		
		if ($auto) {
            $GLOBALS['app']->Session->PushLastResponse(_t('STORE_PRODUCTPARENT_AUTOUPDATED',
                                                     date('H:i:s'),
                                                     (int)$id,
                                                     date('D, d')),
                                                  RESPONSE_NOTICE);
        } else {
            $GLOBALS['app']->Session->PushLastResponse(_t('STORE_PRODUCTPARENT_UPDATED'), RESPONSE_NOTICE);
        }
        return true;
    }

	/**
     * Delete a product category
     *
     * @param  int 	$id 	Product category ID
     * @param  boolean 	$massive 	Is this part of a massive delete?
     * @access  public
     * @return  boolean 	True if query was successful and Jaws_Error on error
     */
    function DeleteProductParent($id, $massive = false)
    {
		$model = $GLOBALS['app']->LoadGadget('Store', 'Model');
		$request =& Jaws_Request::getInstance();
		$action = $request->get('action', 'get');
		if (empty($action)) {
			$action = $request->get('action', 'post');
		}
		$parent = $model->GetProductParent((int)$id);
		if (Jaws_Error::IsError($parent)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_PRODUCTPARENT_NOT_DELETED'), RESPONSE_ERROR);
			return new Jaws_Error(_t('STORE_ERROR_PRODUCTPARENT_NOT_DELETED'), _t('STORE_NAME'));
		}

		if(!isset($parent['productparentid'])) {
			$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_PRODUCTPARENT_NOT_DELETED'), RESPONSE_ERROR);
			return new Jaws_Error(_t('STORE_ERROR_PRODUCTPARENT_NOT_DELETED'), _t('STORE_NAME'));
		} else {
			$eids = $model->GetAllSubCategoriesOfParent($id);
			if (Jaws_Error::IsError($eids)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_PRODUCTPARENT_NOT_DELETED'), RESPONSE_ERROR);
				return new Jaws_Error(_t('STORE_ERROR_PRODUCTPARENT_NOT_DELETED'), _t('STORE_NAME'));
			}
			foreach ($eids as $eid) {
				$rids = $model->GetAllProductsOfParent($eid['productparentid']);
				if (Jaws_Error::IsError($rids)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_PRODUCTPARENT_NOT_DELETED'), RESPONSE_ERROR);
					return new Jaws_Error(_t('STORE_ERROR_PRODUCTPARENT_NOT_DELETED'), _t('STORE_NAME'));
				}

				foreach ($rids as $rid) {
					// Delete product
					$result = $this->DeleteProduct($rid['id'], true);
					if (Jaws_Error::IsError($result)) {
						$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_PRODUCT_NOT_DELETED'), RESPONSE_ERROR);
						return new Jaws_Error(_t('STORE_ERROR_PRODUCT_NOT_DELETED'), _t('STORE_NAME'));
					}
				}
			}
			$pids = $model->GetAllProductsOfParent($parent['productparentid']);
			if (Jaws_Error::IsError($pids)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_PRODUCTPARENT_NOT_DELETED'), RESPONSE_ERROR);
				return new Jaws_Error(_t('STORE_ERROR_PRODUCTPARENT_NOT_DELETED'), _t('STORE_NAME'));
			}

			foreach ($pids as $pid) {
				// Delete product
				$result = $this->DeleteProduct($pid['id'], true);
				if (Jaws_Error::IsError($result)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_PRODUCT_NOT_DELETED'), RESPONSE_ERROR);
					return new Jaws_Error(_t('STORE_ERROR_PRODUCT_NOT_DELETED'), _t('STORE_NAME'));
				}
			}
			
			if (!empty($parent['productparentrss_url'])) {
				/*
				$sql = 'DELETE FROM [[product]] WHERE [rss_url] = {rss_url}';
				$res = $GLOBALS['db']->query($sql, array('rss_url' => $parent['productparentrss_url']));
				if (Jaws_Error::IsError($res)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_PRODUCT_NOT_DELETED'), RESPONSE_ERROR);
					return new Jaws_Error(_t('STORE_ERROR_PRODUCT_NOT_DELETED'), _t('STORE_NAME'));
				}
				*/
				// Delete Scheduler 
				$params = array();
				$params['scriptpath'] = $GLOBALS['app']->getSiteURL() . '/index.php?gadget=Store&action=UpdateRSSStore&id='.$parent['productparentid'];
				$sql = 'SELECT [id] FROM [phpjobscheduler] WHERE ([scriptpath] = {scriptpath})';
				$found = $GLOBALS['db']->queryOne($sql, $params);
				
				if (is_numeric($found)) {
					$scheduler = $GLOBALS['app']->deleteScheduler($found);
					if (Jaws_Error::IsError($scheduler)) {
						return new Jaws_Error($scheduler->GetMessage(), _t('STORE_NAME'));
					}
				}
			}
			
			// Let everyone know it has been deleted
			$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
			$res = $GLOBALS['app']->Shouter->Shout('onDeleteProductParent', $id);
			if (Jaws_Error::IsError($res) || !$res) {
				$GLOBALS['app']->Session->PushLastResponse((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_PRODUCTPARENT_NOT_DELETED')), RESPONSE_ERROR);
				return new Jaws_Error((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_PRODUCTPARENT_NOT_DELETED')), _t('STORE_NAME'));
			}


			$sql = 'DELETE FROM [[productparent]] WHERE [productparentid] = {id}';
			$res = $GLOBALS['db']->query($sql, array('id' => $id));
			if (Jaws_Error::IsError($res)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_PRODUCTPARENT_NOT_DELETED'), RESPONSE_ERROR);
				return new Jaws_Error(_t('STORE_ERROR_PRODUCTPARENT_NOT_DELETED'), _t('STORE_NAME'));
			}

			
			if (BASE_SCRIPT != 'index.php' || $action == 'UpdateRSSStore') {
				// delete menu item for page
				$url = $GLOBALS['app']->Map->GetURLFor('Store', 'Category', array('id' => $parent['productparentfast_url']));
				$sql  = 'SELECT [id], [rank] FROM [[menus]] WHERE [url] = {url}';
				$oid = $GLOBALS['db']->queryRow($sql, array('url' => $url));
				if (Jaws_Error::IsError($oid)) {
					//$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
					$GLOBALS['app']->Session->PushLastResponse($oid->GetMessage(), RESPONSE_ERROR);
					return false;
				} else if (!empty($oid['id']) && isset($oid['id'])) {
					$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
					$res = $GLOBALS['app']->Shouter->Shout('onDeleteMenuItem', $url);
					if (Jaws_Error::IsError($res) || !$res) {
						$GLOBALS['app']->Session->PushLastResponse((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_PRODUCTPARENT_DELETE_FROM_MENU')), RESPONSE_ERROR);
						return new Jaws_Error((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_PRODUCTPARENT_DELETE_FROM_MENU')), _t('STORE_NAME'));
					}
				}
			}
		}

		if ($massive === false) {
			$GLOBALS['app']->Session->PushLastResponse(_t('STORE_PRODUCTPARENT_DELETED'), RESPONSE_NOTICE);
        }
		return true;
    }

    /**
     * Deletes a group of product parents
     *
     * @param   array   $parents  Array with the IDs of product parents
     * @access  public
     * @return  bool    Success/failure
     */
    function MassiveDelete($parents)
    {
        if (!is_array($parents)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_PRODUCTPARENT_NOT_MASSIVE_DELETED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('STORE_ERROR_PRODUCTPARENT_NOT_MASSIVE_DELETED'), _t('STORE_NAME'));
        }

        foreach ($parents as $page) {
            $res = $this->DeleteProductParent($page);
            if (Jaws_Error::IsError($res)) {
                $GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_PRODUCTPARENT_NOT_MASSIVE_DELETED'), RESPONSE_ERROR);
                return new Jaws_Error(_t('STORE_ERROR_PRODUCTPARENT_NOT_MASSIVE_DELETED'), _t('STORE_NAME'));
            }
        }
        $GLOBALS['app']->Session->PushLastResponse(_t('STORE_PRODUCTPARENT_MASSIVE_DELETED'), RESPONSE_NOTICE);
        return true;
    }

    /**
     * Creates a new product.
     *
     * @param   int  $BrandID 	ID of brand
     * @param   int  $sort_order 	The priority order
     * @param   string  $category 	Comma separated list of categories
     * @param   string  $product_code      		Product code
     * @param   string  $title      		The title of the product.
     * @param   string  $image   		An image to accompany the product
     * @param   string  $sm_description  The summary of the product.
     * @param   string  $description    	The contents of the product.
     * @param   string  $weight    	Weight (in lbs.) of product
     * @param   string  $retail    	MSRP of product (strictly for comparison purposes)
     * @param   string  $price    	Purchase price of product
     * @param   string  $cost    	Internal cost of this product.
     * @param   string  $setup_fee    	Setup fee added to purchase price for this product.
     * @param   string  $unit    	Unit of measurement this product is purchased as.
     * @param   string  $recurring    	(Y/N) Is this product a recurring subscription?
     * @param   string  $inventory    	(Y/N) Use inventory management for this product?
     * @param   string  $instock    	Number of products currently in stock
     * @param   string  $lowstock    	Number that triggers out of stock message
     * @param   string  $outstockmsg    	Message to display when this product is out of stock.
     * @param   string  $outstockbuy    	(Y/N) Can this product be purchased when out of stock?
     * @param   string  $attribute    	Comma separated list of attribute IDs of product
     * @param   string  $premium    	(Y/N) Is this featured?
     * @param   string  $featured    	(Y/N) Can this product be shown on site-wide featured areas?
     * @param   int 	$OwnerID  		The poster's user ID
     * @param   string 	$Active  		(Y/N) If the product is published or not
     * @param   string 	$internal_productno  		Internal product number
     * @param   string 	$alink  		Hyperlink URL
     * @param   string 	$alinkTitle  		Hyperlink Title
     * @param   string 	$alinkType  		Hyperlink Type (_self/_blank/mail)
     * @param   string 	$alink2  		Hyperlink URL
     * @param   string 	$alink2Title  		Hyperlink Title
     * @param   string 	$alink2Type  		Hyperlink Type (_self/_blank/mail)
     * @param   string 	$alink3  		Hyperlink URL
     * @param   string 	$alink3Title  		Hyperlink Title
     * @param   string 	$alink3Type  		Hyperlink Type (_self/_blank/mail)
     * @param   string 	$rss_url  		RSS URL
     * @param   string 	$contact  		Product's contact name
     * @param   string 	$contact_email  		Product's contact email address
     * @param   string 	$contact_phone  		Product's contact telephone
     * @param   string 	$contact_website  		Product's contact website
     * @param   string 	$contact_photo  		Product's contact photo URL
     * @param   string 	$company  		Product's company name
     * @param   string 	$company_email  		Product's company email
     * @param   string 	$company_phone  		Product's company telephone
     * @param   string 	$company_website  		Product's company website
     * @param   string 	$company_logo  		Product's company logo
     * @param   string 	$subscribe_method  		Gadget method this product is tied to
     * @param   string 	$sales  		Comma separated list of sales IDs
     * @param   int 	$min_qty  		Minimum quantity to allow purchase
     * @param   string 	$checksum  		Unique ID
     * @param   boolean 	$auto       		If it's auto saved or not
     * @access  public
     * @return  ID of entered post 	    Success/failure
     */
	function AddProduct(
		$BrandID, $sort_order, $category, $product_code, $title, $image = '', 
		$sm_description = '', $description = '', $weight = '0', $retail = '0', 
		$price = '0', $cost = '0', $setup_fee = '0', $unit = '/ Each', $recurring = 'N', 
		$inventory = 'N', $instock = '1', $lowstock = '-1', 
		$outstockmsg = 'This product is sold out. Check back soon.', $outstockbuy = 'N', 
		$attribute = '', $premium = 'N', $featured = 'N', $OwnerID = null, $Active = 'N', 
		$internal_productno = '', $alink = '', $alinkTitle = '', $alinkType = '', $alink2 = '', 
		$alink2Title = '', $alink2Type = '', $alink3 = '', $alink3Title = '', $alink3Type = '', 
		$rss_url = '', $contact = '', $contact_email = '', $contact_phone = '', 
		$contact_website = '', $contact_photo = '', $company = '', $company_email = '', 
		$company_phone = '', $company_website = '', $company_logo = '', $subscribe_method = '', 
		$sales = '', $min_qty = 1, $checksum = '', $auto = false
	) {
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$model = $GLOBALS['app']->LoadGadget('Store', 'Model');
		
		// If the checksum is found, don't add it.
		$pages = $model->GetProducts();
		if (!Jaws_Error::IsError($pages)) {
			foreach($pages as $p) {		            
				if (!empty($checksum)) {
					if ($p['checksum'] == $checksum) {
						return true;
					}
				}
			}
		}

        $sql = "
            INSERT INTO [[product]]
                ([brandid], [sort_order], [category], [product_code], [title], [image], 
				[sm_description], [description], [weight], [retail], [price], [cost], 
				[setup_fee], [unit], [recurring], [inventory], [instock], 
				[lowstock], [outstockmsg], [outstockbuy], [attribute], [premium], [featured], [ownerid], 
				[active], [created], [updated], [fast_url], [internal_productno], [alink], [alinktitle], 
				[alinktype], [alink2], [alink2title], [alink2type], [alink3], [alink3title], [alink3type],
				[rss_url], [contact], [contact_email], [contact_phone], [contact_website], [contact_photo], [company], 
				[company_email], [company_phone], [company_website], [company_logo], [subscribe_method], [sales], [min_qty], [checksum])
            VALUES
                ({BrandID}, {sort_order}, {category}, {product_code}, {title}, {image}, 
				{sm_description}, {description}, {weight}, {retail}, {price}, {cost}, 
				{setup_fee}, {unit}, {recurring}, {inventory}, {instock}, 
				{lowstock}, {outstockmsg}, {outstockbuy}, {attribute}, {premium}, {featured}, {OwnerID}, 
				{Active}, {now}, {now}, {fast_url}, {internal_productno}, {alink}, {alinkTitle}, 
				{alinkType}, {alink2}, {alink2Title}, {alink2Type}, {alink3}, {alink3Title}, {alink3Type},
				{rss_url}, {contact}, {contact_email}, {contact_phone}, {contact_website}, {contact_photo}, {company}, 
				{company_email}, {company_phone}, {company_website}, {company_logo}, {subscribe_method}, {sales}, {min_qty}, {checksum})";

		// Get the fast url
        $fast_url = !empty($title) ? $title : $product_code;
		$fast_url = strip_tags($GLOBALS['app']->UTF8->str_replace(',', '', $fast_url));
        $fast_url = $this->GetRealFastUrl(
			$fast_url, 'product', true
		);
        
		if (BASE_SCRIPT != 'index.php' && $auto === false) {
			$description = strip_tags($description, '<p><a><b><em><i><strong><div><table><tbody><thead><tr><td><span><font><hr><br><marquee><ul><li>');
		} else if (trim($rss_url) != '') {
			$description = strip_tags($description, '<p><a><img><b><em><i><strong><div><table><tbody><thead><tr><td><span><font><hr><br><marquee><ul><li>');
		} else {
			if (
				!is_null($OwnerID) && 
				(strlen(strip_tags($description)) > $GLOBALS['app']->Registry->Get('/gadgets/Store/user_desc_char_limit')) && 
				($GLOBALS['app']->Registry->Get('/gadgets/Store/user_desc_char_limit') > 0)
			) {
				$description = substr(strip_tags($description), 0, $GLOBALS['app']->Registry->Get('/gadgets/Store/user_desc_char_limit'));
			} else {
				$description = strip_tags($description, '<p><b><em><i><strong><div><table><tbody><thead><tr><td><span><font><hr><br><marquee><ul><li>');
			}
		}
		
		$image = $this->cleanImagePath($image);
		$company_logo = $this->cleanImagePath($company_logo);
		$contact_photo = $this->cleanImagePath($contact_photo);
		
		$config_key = $GLOBALS['app']->Registry->Get('/config/key');		
		$OwnerID = (!is_null($OwnerID)) ? (int)$OwnerID : 0;
		if (!empty($image) && (empty($checksum) || strpos($checksum, $config_key) !== false)) {
			if (
				$OwnerID > 0 && 
				(substr(strtolower(trim($image)), 0, 4) == 'http' || 
				substr(strtolower(trim($image)), 0, 2) == '//' || 
				substr(strtolower(trim($image)), 0, 2) == '\\\\')
			) {
				$image = '';
			}
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
		// Format retail
		if (!empty($retail)) {
			$newstring = "";
			$array = str_split($retail);
			foreach($array as $char) {
				if (($char >= '0' && $char <= '9') || $char == '.') {
					$newstring .= $char;
				}
			}
			$retail = number_format($newstring, 2, '.', '');
		}
		// Format cost
		if (!empty($cost)) {
			$newstring = "";
			$array = str_split($cost);
			foreach($array as $char) {
				if (($char >= '0' && $char <= '9') || $char == '.') {
					$newstring .= $char;
				}
			}
			$cost = number_format($newstring, 2, '.', '');
		}
		// Format setup_fee
		if (!empty($setup_fee)) {
			$newstring = "";
			$array = str_split($setup_fee);
			foreach($array as $char) {
				if (($char >= '0' && $char <= '9') || $char == '.') {
					$newstring .= $char;
				}
			}
			$setup_fee = number_format($newstring, 2, '.', '');
		}
		// Format weight
		if (!empty($weight)) {
			$newstring = "";
			$array = str_split($weight);
			foreach($array as $char) {
				if (($char >= '0' && $char <= '9') || $char == '.') {
					$newstring .= $char;
				}
			}
			$weight = number_format($newstring, 2, '.', '');
		} else {
			$weight = 0;
		}
		// Format instock
		if (!empty($instock)) {
			$newstring = "";
			$array = str_split($instock);
			foreach($array as $char) {
				if (($char >= '0' && $char <= '9') || $char == '.') {
					$newstring .= $char;
				}
			}
			$instock = number_format($newstring, 0, '', '');
		} else {
			$instock = 0;
		}
		
		// Format lowstock
		if (!empty($lowstock) && $lowstock != (-1) && $lowstock != '-1') {
			$newstring = "";
			$array = str_split($lowstock);
			foreach($array as $char) {
				if (($char >= '0' && $char <= '9') || $char == '.') {
					$newstring .= $char;
				}
			}
			$lowstock = number_format($newstring, 0, '', '');
		} else {
			$lowstock = (-1);
		}
	   	   
	    
        $params               		= array();
		$params['BrandID']       	= (int)$BrandID; 
		$params['sort_order']       = (int)$sort_order; 
		$params['category']       	= $category; 
		$params['title']       		= $xss->parse(strip_tags($title)); 
		$params['image']       		= $xss->parse(strip_tags($image)); 
		$params['product_code']     = $xss->parse($GLOBALS['app']->UTF8->str_replace(' ', '', strip_tags($product_code))); 
		$params['sm_description']   = $xss->parse(strip_tags($sm_description)); 
		$params['description']      = str_replace("\r\n", "\n", $description);
		$params['price']       		= $price; 
		$params['weight']       	= $weight; 
		$params['retail']       	= $retail; 
		$params['cost']       		= $cost; 
		$params['setup_fee']       	= $setup_fee; 
		$params['unit']       		= $xss->parse($unit); 
		$params['recurring']       	= $xss->parse($recurring); 
		$params['inventory']       	= $xss->parse($inventory); 
		$params['instock']       	= $instock; 
		$params['lowstock']       	= $lowstock; 
		$params['outstockmsg']      = $xss->parse(strip_tags($outstockmsg)); 
		$params['outstockbuy']      = $xss->parse($outstockbuy); 
		$params['attribute']       	= $attribute; 
		$params['premium']       	= $xss->parse($premium); 
		$params['featured']       	= $xss->parse($featured); 
		$params['OwnerID']       	= $OwnerID; 
		$params['Active']       	= $xss->parse($Active); 
		$params['fast_url']       	= $xss->parse($fast_url); 
		$params['internal_productno'] = $xss->parse(strip_tags($internal_productno)); 
		$params['alink']       		= $xss->parse($alink); 
		$params['alinkTitle']       = $xss->parse(strip_tags($alinkTitle)); 
		$params['alinkType']       	= $xss->parse($alinkType); 
		$params['alink2']       	= $xss->parse($alink2); 
		$params['alink2Title']      = $xss->parse(strip_tags($alink2Title)); 
		$params['alink2Type']       = $xss->parse($alink2Type); 
		$params['alink3']       	= $xss->parse($alink3); 
		$params['alink3Title']      = $xss->parse(strip_tags($alink3Title)); 
		$params['alink3Type']       = $xss->parse($alink3Type);
		$params['rss_url']       	= ($OwnerID > 0 ? $rss_url : ''); 
		$params['contact']       	= $xss->parse($contact); 
		$params['contact_email']    = $xss->parse($contact_email); 
		$params['contact_phone']    = $xss->parse($contact_phone); 
		$params['contact_website']  = $xss->parse($contact_website); 
		$params['contact_photo']    = $xss->parse($contact_photo); 
		$params['company']       	= $xss->parse($company); 
		$params['company_email']    = $xss->parse($company_email); 
		$params['company_phone']    = $xss->parse($company_phone); 
		$params['company_website']  = $xss->parse($company_website); 
		$params['company_logo']     = $xss->parse($company_logo);
		$params['subscribe_method'] = $xss->parse($subscribe_method);
		$params['sales']       		= $sales; 
		$params['min_qty']       	= (int)$min_qty; 
		$params['checksum']       	= $xss->parse($checksum); 
		$params['now']        		= $GLOBALS['db']->Date();

		$result = $GLOBALS['db']->query($sql, $params);
		if (Jaws_Error::IsError($result)) {
            //return new Jaws_Error(_t('STORE_ERROR_PRODUCT_NOT_ADDED'), _t('STORE_NAME'));
            return new Jaws_Error($result->GetMessage(), _t('STORE_NAME'));
        }
        $newid = $GLOBALS['db']->lastInsertID('product', 'id');
		
		if (empty($checksum)) {
			// Update checksum
			$params               	= array();
			$params['id'] 			= $newid;
			$params['checksum'] 	= $newid.':'.$config_key;
			
			$sql = '
				UPDATE [[product]] SET
					[checksum] = {checksum}
				WHERE [id] = {id}';

			$result = $GLOBALS['db']->query($sql, $params);
			if (Jaws_Error::IsError($result)) {
				return false;
			}
		}
		
		// Let everyone know a product has been added
        $GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
        $res = $GLOBALS['app']->Shouter->Shout('onAddProduct', $newid);
        if (Jaws_Error::IsError($res) || !$res) {
			$GLOBALS['app']->Session->PushLastResponse((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_PRODUCT_NOT_ADDED')), RESPONSE_ERROR);
			return new Jaws_Error((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_PRODUCT_NOT_ADDED')), _t('STORE_NAME'));
        }
		
		if ($auto === false) {
			$GLOBALS['app']->Session->PushLastResponse(_t('STORE_PRODUCT_CREATED'), RESPONSE_NOTICE);
        }
		return $newid;
    }

    /**
     * Updates a product.
     *
     * @param   int     $id             The ID of the product to update.
     * @param   int  $BrandID 	ID of brand
     * @param   int  $sort_order 	The priority order
     * @param   string  $category 	Comma separated list of categories
     * @param   string  $product_code      		Product code
     * @param   string  $title      		The title of the product.
     * @param   string  $image   		An image to accompany the product
     * @param   string  $sm_description  The summary of the product.
     * @param   string  $description    	The contents of the product.
     * @param   string  $weight    	Weight (in lbs.) of product
     * @param   string  $retail    	MSRP of product (strictly for comparison purposes)
     * @param   string  $price    	Purchase price of product
     * @param   string  $cost    	Internal cost of this product.
     * @param   string  $setup_fee    	Setup fee added to purchase price for this product.
     * @param   string  $unit    	Unit of measurement this product is purchased as.
     * @param   string  $recurring    	(Y/N) Is this product a recurring subscription?
     * @param   string  $inventory    	(Y/N) Use inventory management for this product?
     * @param   string  $instock    	Number of products currently in stock
     * @param   string  $lowstock    	Number that triggers out of stock message
     * @param   string  $outstockmsg    	Message to display when this product is out of stock.
     * @param   string  $outstockbuy    	(Y/N) Can this product be purchased when out of stock?
     * @param   string  $attribute    	Comma separated list of attribute IDs of product
     * @param   string  $premium    	(Y/N) Is this featured?
     * @param   string  $featured    	(Y/N) Can this product be shown on site-wide featured areas?
     * @param   string 	$Active  		(Y/N) If the product is published or not
     * @param   string 	$internal_productno  		Internal product number
     * @param   string 	$alink  		Hyperlink URL
     * @param   string 	$alinkTitle  		Hyperlink Title
     * @param   string 	$alinkType  		Hyperlink Type (_self/_blank/mail)
     * @param   string 	$alink2  		Hyperlink URL
     * @param   string 	$alink2Title  		Hyperlink Title
     * @param   string 	$alink2Type  		Hyperlink Type (_self/_blank/mail)
     * @param   string 	$alink3  		Hyperlink URL
     * @param   string 	$alink3Title  		Hyperlink Title
     * @param   string 	$alink3Type  		Hyperlink Type (_self/_blank/mail)
     * @param   string 	$rss_url  		RSS URL
     * @param   string 	$contact  		Product's contact name
     * @param   string 	$contact_email  		Product's contact email address
     * @param   string 	$contact_phone  		Product's contact telephone
     * @param   string 	$contact_website  		Product's contact website
     * @param   string 	$contact_photo  		Product's contact photo URL
     * @param   string 	$company  		Product's company name
     * @param   string 	$company_email  		Product's company email
     * @param   string 	$company_phone  		Product's company telephone
     * @param   string 	$company_website  		Product's company website
     * @param   string 	$company_logo  		Product's company logo
     * @param   string 	$subscribe_method  		Gadget method this product is tied to
     * @param   string 	$sales  		Comma separated list of sales IDs
     * @param   int 	$min_qty  		Minimum quantity to allow purchase
     * @param   boolean $auto       		If it's auto saved or not
     * @access  public
     * @return  boolean Success/failure
     */
    function UpdateProduct(
		$id, $BrandID, $sort_order, $category, $product_code, $title, $image, 
		$sm_description, $description, $weight, $retail, $price, $cost, 
		$setup_fee, $unit, $recurring, $inventory, $instock, 
		$lowstock, $outstockmsg, $outstockbuy, $attribute, $premium, $featured,
		$Active, $internal_productno, $alink, $alinkTitle, 
		$alinkType, $alink2, $alink2Title, $alink2Type, $alink3, $alink3Title, $alink3Type, 
		$rss_url, $contact, $contact_email, $contact_phone, $contact_website, $contact_photo, 
		$company, $company_email, $company_phone, $company_website, $company_logo, 
		$subscribe_method, $sales, $min_qty, $auto = false
	) {
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$model = $GLOBALS['app']->LoadGadget('Store', 'Model');
		        		
        $page = $model->GetProduct($id);
        if (Jaws_Error::isError($page)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_PRODUCT_NOT_FOUND'), RESPONSE_ERROR);
            return new Jaws_Error(_t('STORE_ERROR_PRODUCT_NOT_FOUND'), _t('STORE_NAME'));
        } else {

			$sql = '
				UPDATE [[product]] SET
					[brandid] = {BrandID}, 
					[sort_order] = {sort_order}, 
					[category] = {category}, 
					[title] = {title}, 
					[image] = {image}, 
					[sm_description] = {sm_description}, 
					[description] = {description}, 
					[price] = {price}, 
					[attribute] = {attribute}, 
					[premium] = {premium}, 
					[featured] = {featured}, 
					[active] = {Active}, 
					[fast_url] = {fast_url}, 
					[internal_productno] = {internal_productno},
					[alink] = {alink}, 
					[alinktitle] = {alinkTitle}, 
					[alinktype]	= {alinkType}, 
					[alink2] = {alink2}, 
					[alink2title] = {alink2Title}, 
					[alink2type] = {alink2Type}, 
					[alink3] = {alink3}, 
					[alink3title] = {alink3Title}, 
					[alink3type] = {alink3Type},
					[product_code] = {product_code},
					[weight] = {weight}, 
					[retail] = {retail},
					[cost] = {cost}, 
					[setup_fee] = {setup_fee}, 
					[unit] = {unit}, 
					[recurring] = {recurring}, 
					[inventory] = {inventory}, 
					[instock] = {instock}, 
					[lowstock] = {lowstock}, 
					[outstockmsg] = {outstockmsg}, 
					[outstockbuy] = {outstockbuy}, 
					[rss_url] = {rss_url}, 
					[contact] = {contact}, 
					[contact_email] = {contact_email}, 
					[contact_phone] = {contact_phone}, 
					[contact_website] = {contact_website}, 
					[contact_photo] = {contact_photo}, 
					[company] = {company}, 
					[company_email] = {company_email}, 
					[company_phone] = {company_phone}, 
					[company_website] = {company_website}, 
					[company_logo] = {company_logo},
					[subscribe_method] = {subscribe_method},
					[sales] = {sales},
					[min_qty] = {min_qty},
					[updated] = {now}
				WHERE [id] = {id}';
				
			// Get the fast url
			$fast_url = !empty($title) ? $title : $product_code;
			$fast_url = strip_tags($GLOBALS['app']->UTF8->str_replace(',', '', $fast_url));
			$fast_url = $this->GetRealFastUrl(
				$fast_url, 'product', true, 'fast_url', 'id', $id
			);
			
			if (BASE_SCRIPT != 'index.php' && $auto === false) {
				$description = strip_tags($description, '<p><a><b><em><i><strong><div><table><tbody><thead><tr><td><span><font><hr><br><marquee><ul><li>');
			} else if (trim($rss_url) != '') {
				$description = strip_tags($description, '<p><a><img><b><em><i><strong><div><table><tbody><thead><tr><td><span><font><hr><br><marquee><ul><li>');
			} else {
				$description = strip_tags($description, '<p><b><em><i><strong><div><table><tbody><thead><tr><td><span><font><hr><br><marquee><ul><li>');
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
			// Format retail
			if (!empty($retail)) {
				$newstring = "";
				$array = str_split($retail);
				foreach($array as $char) {
					if (($char >= '0' && $char <= '9') || $char == '.') {
						$newstring .= $char;
					}
				}
				$retail = number_format($newstring, 2, '.', '');
			}
			// Format cost
			if (!empty($cost)) {
				$newstring = "";
				$array = str_split($cost);
				foreach($array as $char) {
					if (($char >= '0' && $char <= '9') || $char == '.') {
						$newstring .= $char;
					}
				}
				$cost = number_format($newstring, 2, '.', '');
			}
			// Format setup_fee
			if (!empty($setup_fee)) {
				$newstring = "";
				$array = str_split($setup_fee);
				foreach($array as $char) {
					if (($char >= '0' && $char <= '9') || $char == '.') {
						$newstring .= $char;
					}
				}
				$setup_fee = number_format($newstring, 2, '.', '');
			}
			// Format weight
			if (!empty($weight)) {
				$newstring = "";
				$array = str_split($weight);
				foreach($array as $char) {
					if (($char >= '0' && $char <= '9') || $char == '.') {
						$newstring .= $char;
					}
				}
				$weight = number_format($newstring, 2, '.', '');
			} else {
				$weight = 0;
			}
			// Format instock
			if (!empty($instock)) {
				$newstring = "";
				$array = str_split($instock);
				foreach($array as $char) {
					if (($char >= '0' && $char <= '9') || $char == '.') {
						$newstring .= $char;
					}
				}
				$instock = number_format($newstring, 0, '', '');
			} else {
				$instock = 0;
			}

			// Format lowstock
			if (!empty($lowstock) && $lowstock != (-1) && $lowstock != '-1') {
				$newstring = "";
				$array = str_split($lowstock);
				foreach($array as $char) {
					if (($char >= '0' && $char <= '9') || $char == '.') {
						$newstring .= $char;
					}
				}
				$lowstock = number_format($newstring, 0, '', '');
			} else {
				$lowstock = (-1);
			}
			
			$image = str_replace($GLOBALS['app']->getDataURL('', true) . 'files/', '/', $image);
			while (substr($image, 0, 2) == '//') {
				$image = substr($image, 1, strlen($image));
			}		
			$company_logo = str_replace($GLOBALS['app']->getDataURL('', true) . 'files/', '/', $company_logo);
			while (substr($company_logo, 0, 2) == '//') {
				$company_logo = substr($company_logo, 1, strlen($company_logo));
			}		
			$contact_photo = str_replace($GLOBALS['app']->getDataURL('', true) . 'files/', '/', $contact_photo);
			while (substr($contact_photo, 0, 2) == '//') {
				$contact_photo = substr($contact_photo, 1, strlen($contact_photo));
			}		
			$config_key = $GLOBALS['app']->Registry->Get('/config/key');		
			if (!empty($image) && strpos($page['checksum'], $config_key) !== false) {
				if (
					$page['ownerid'] > 0 && 
					(substr(strtolower(trim($image)), 0, 4) == 'http' || 
					substr(strtolower(trim($image)), 0, 2) == '//' || 
					substr(strtolower(trim($image)), 0, 2) == '\\\\')
				) {
					$image = '';
				}
			}
			
			$product_code = $GLOBALS['app']->UTF8->str_replace(' ', '', strip_tags($product_code)); 
			
			$params               		= array();
			$params['id']         		= (int)$id;
			$params['BrandID']       	= (int)$BrandID; 
			$params['sort_order']       = (int)$sort_order; 
			$params['category']       	= $category; 
			$params['title']       		= $xss->parse(strip_tags($title)); 
			$params['image']       		= $xss->parse(strip_tags($image)); 
			$params['product_code']     = $xss->parse($product_code); 
			$params['sm_description']   = $xss->parse(strip_tags($sm_description)); 
			$params['description']      = str_replace("\r\n", "\n", $description);
			$params['price']       		= $price; 
			$params['weight']       	= $weight; 
			$params['retail']       	= $retail; 
			$params['cost']       		= $cost; 
			$params['setup_fee']       	= $setup_fee; 
			$params['unit']       		= $xss->parse($unit); 
			$params['recurring']       	= $xss->parse($recurring); 
			$params['inventory']       	= $xss->parse($inventory); 
			$params['instock']       	= $instock; 
			$params['lowstock']       	= $lowstock; 
			$params['outstockmsg']      = $xss->parse(strip_tags($outstockmsg)); 
			$params['outstockbuy']      = $xss->parse($outstockbuy); 
			$params['attribute']       	= $attribute; 
			$params['premium']       	= $xss->parse($premium); 
			$params['featured']       	= $xss->parse($featured); 
			$params['Active']       	= $xss->parse($Active); 
			$params['fast_url']       	= $xss->parse($fast_url); 
			$params['internal_productno'] = $xss->parse(strip_tags($internal_productno)); 
			$params['alink']       		= $xss->parse($alink); 
			$params['alinkTitle']       = $xss->parse(strip_tags($alinkTitle)); 
			$params['alinkType']       	= $xss->parse($alinkType); 
			$params['alink2']       	= $xss->parse($alink2); 
			$params['alink2Title']      = $xss->parse(strip_tags($alink2Title)); 
			$params['alink2Type']       = $xss->parse($alink2Type); 
			$params['alink3']       	= $xss->parse($alink3); 
			$params['alink3Title']      = $xss->parse(strip_tags($alink3Title)); 
			$params['alink3Type']       = $xss->parse($alink3Type);
			$params['rss_url']       	= ($page['ownerid'] > 0 ? $rss_url : ''); 
			$params['contact']       	= $xss->parse($contact); 
			$params['contact_email']    = $xss->parse($contact_email); 
			$params['contact_phone']    = $xss->parse($contact_phone); 
			$params['contact_website']  = $xss->parse($contact_website); 
			$params['contact_photo']    = $xss->parse($contact_photo); 
			$params['company']       	= $xss->parse($company); 
			$params['company_email']    = $xss->parse($company_email); 
			$params['company_phone']    = $xss->parse($company_phone); 
			$params['company_website']  = $xss->parse($company_website); 
			$params['company_logo']     = $xss->parse($company_logo);
			$params['subscribe_method'] = $xss->parse($subscribe_method);
			$params['sales']       		= $sales; 
			$params['min_qty']       	= (int)$min_qty; 
			$params['now']        		= $GLOBALS['db']->Date();

			$result = $GLOBALS['db']->query($sql, $params);
			if (Jaws_Error::IsError($result)) {
				return new Jaws_Error(_t('STORE_ERROR_PRODUCT_NOT_UPDATED'), _t('STORE_NAME'));
			}
			
			// Let everyone know a product has been updated
			$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
			$res = $GLOBALS['app']->Shouter->Shout('onUpdateProduct', $id);
			if (Jaws_Error::IsError($res) || !$res) {
				$GLOBALS['app']->Session->PushLastResponse((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_PRODUCT_NOT_UPDATED')), RESPONSE_ERROR);
				return new Jaws_Error((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_PRODUCT_NOT_UPDATED')), _t('STORE_NAME'));
			}
			
			/*
			if ($auto) {
				$GLOBALS['app']->Session->PushLastResponse(_t('STORE_PRODUCT_AUTOUPDATED',
														 date('H:i:s'),
														 (int)$id,
														 date('D, d')),
													  RESPONSE_NOTICE);
			} else {
			*/	
			if ($auto === false) {
				$GLOBALS['app']->Session->PushLastResponse(_t('STORE_PRODUCT_UPDATED'), RESPONSE_NOTICE);
			}
			return true;
		}
    }


    /**
     * Deletes a product
     *
     * @param   int     $id     The ID of the product to delete.
     * @param   boolean     $massive     Is this part of a massive delete?
     * @access  public
     * @return  bool    Success/failure
     */
    function DeleteProduct($id, $massive = false)
    {
		$model = $GLOBALS['app']->LoadGadget('Store', 'Model');
		$page = $model->GetProduct($id);
		if (Jaws_Error::IsError($page)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_PRODUCT_NOT_DELETED'), RESPONSE_ERROR);
			return new Jaws_Error(_t('STORE_ERROR_PRODUCT_NOT_DELETED'), _t('STORE_NAME'));
		} else {
			// Let everyone know it has been deleted
			$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
			$res = $GLOBALS['app']->Shouter->Shout('onDeleteProduct', $id);
			if (Jaws_Error::IsError($res) || !$res) {
				$GLOBALS['app']->Session->PushLastResponse((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_PRODUCT_NOT_DELETED')), RESPONSE_ERROR);
				return new Jaws_Error((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_PRODUCT_NOT_DELETED')), _t('STORE_NAME'));
			}

			// Delete product posts
			$oids = $model->GetAllPostsOfProduct($id);
			if (Jaws_Error::IsError($oids)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_POST_NOT_DELETED'), RESPONSE_ERROR);
				return new Jaws_Error(_t('STORE_ERROR_PRODUCTPARENT_NOT_DELETED'), _t('STORE_NAME'));
			}
			foreach ($oids as $oid) {
				if (!$this->DeletePost($oid['id'], true)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_POST_NOT_DELETED'), RESPONSE_ERROR);
					return new Jaws_Error(_t('STORE_ERROR_POST_NOT_DELETED'), _t('STORE_NAME'));
				}
			}

			$sql = "
				DELETE FROM [[products_parents]]
					WHERE ([prod_id] = {prod_id})";
			
			$params               		= array();
			$params['prod_id']        	= $id;

			$result = $GLOBALS['db']->query($sql, $params);
			if (Jaws_Error::IsError($result)) {
				return new Jaws_Error(_t('STORE_ERROR_PRODUCTPARENT_NOT_UNLINKED'), _t('STORE_NAME'));
				//return new Jaws_Error($result->GetMessage(), _t('STORE_NAME'));
			}
			
			$categories = explode(',', $page['category']);
			foreach ($categories as $pid) {
				if ((int)$pid != 0) {
					$properties = $model->GetAllProductsOfParent((int)$pid);
					if (Jaws_Error::isError($properties)) {
						$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_PRODUCTS_NOT_RETRIEVED'), RESPONSE_ERROR);
						return new Jaws_Error(_t('STORE_ERROR_PRODUCTS_NOT_RETRIEVED'), _t('STORE_NAME'));
					}
					$hasChildren = false;
					foreach ($properties as $property) {
						if (isset($property['id']) && !empty($property['id'])) {
							$hasChildren = true;
						}
					}
					
					if ($hasChildren === false) {
						$parent = $model->GetProductParent((int)$pid);
						if (Jaws_Error::IsError($parent)) {
							$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_PRODUCT_NOT_DELETED'), RESPONSE_ERROR);
							return new Jaws_Error(_t('STORE_ERROR_PRODUCT_NOT_DELETED'), _t('STORE_NAME'));
						}
						$sql = '
							UPDATE [[productparent]] SET
								[productparentactive] = {Active}, 
								[productparentupdated] = {now}
							WHERE [productparentid] = {id}';

						
						$params               		= array();
						$params['id']         		= (int)$pid;
						$params['Active']       	= 'N'; 
						$params['now']        		= $GLOBALS['db']->Date();

						$result = $GLOBALS['db']->query($sql, $params);
						if (Jaws_Error::IsError($result)) {
							$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_PRODUCTPARENT_NOT_UPDATED'), RESPONSE_ERROR);
							return new Jaws_Error(_t('STORE_ERROR_PRODUCTPARENT_NOT_UPDATED'), _t('STORE_NAME'));
						}
						
						if ($parent['productparentownerid'] == 0) {
							// delete menu item for page
							$url = $GLOBALS['app']->Map->GetURLFor('Store', 'Category', array('id' => $parent['productparentfast_url']));

							$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
							$res = $GLOBALS['app']->Shouter->Shout('onDeleteMenuItem', $url);
							if (Jaws_Error::IsError($res) || !$res) {
								$GLOBALS['app']->Session->PushLastResponse((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_PRODUCTPARENT_DELETE_FROM_MENU')), RESPONSE_ERROR);
								return new Jaws_Error((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_PRODUCTPARENT_DELETE_FROM_MENU')), _t('STORE_NAME'));
							}
						}
					}
				}
			}

			$sql = 'DELETE FROM [[product]] WHERE [id] = {id}';
			$result = $GLOBALS['db']->query($sql, array('id' => $id));
			if (Jaws_Error::IsError($result)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_PRODUCT_NOT_DELETED'), RESPONSE_ERROR);
				return new Jaws_Error(_t('STORE_ERROR_PRODUCT_NOT_DELETED'), _t('STORE_NAME'));
			}
						
			if ($massive === false) {
				$GLOBALS['app']->Session->PushLastResponse(_t('STORE_PRODUCT_DELETED'), RESPONSE_NOTICE);
			}
			
			return true;
		}
    }

    /**
     * Add posts and images to products.
     *
     * @category 	feature
     * @param   int  $sort_order 	The priority order
     * @param   int  $LinkID 	ID of product this post belongs to.
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
		$sort_order, $LinkID, $title, $description, $image, $image_width = 0, $image_height = 0, 
		$layout = 0, $active = 'Y', $OwnerID = null, $url_type = '', $internal_url = '', $external_url = '', 
		$url_target = '_self', $checksum = '', $auto = false
	) {        
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$model = $GLOBALS['app']->LoadGadget('Store', 'Model');
        $LinkID = (int)$LinkID;
		$page = $model->GetProduct($LinkID);
        
		if (Jaws_Error::isError($page)) {
            //$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_PRODUCT_NOT_FOUND'), RESPONSE_ERROR);
            //return new Jaws_Error(_t('STORE_ERROR_PRODUCT_NOT_FOUND'), _t('STORE_NAME'));
            return new Jaws_Error($page->GetMessage(), _t('STORE_NAME'));
        } else {
			$pages = $model->GetAllPostsOfProduct($page['id']);
			if (!Jaws_Error::IsError($pages)) {
				foreach($pages as $p) {		            
					if (!empty($checksum)) {
						if ($p['checksum'] == $checksum) {
							return true;
						}
					}
				}
			}
			if (BASE_SCRIPT == 'index.php' && is_null($OwnerID)) {
				$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');
			} else {
				$OwnerID = (!is_null($OwnerID)) ? (int)$OwnerID : 0;
			}
			
			$image = $this->cleanImagePath($image);
			
			$config_key = $GLOBALS['app']->Registry->Get('/config/key');		
			if (!empty($image) && (empty($checksum) || strpos($checksum, $config_key) !== false)) {
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
				$result = $this->UpdateProduct($LinkID, $page['brandid'], $page['sort_order'], $page['category'], $page['product_code'], $page['title'], $xss->parse($image), 
				$page['sm_description'], $page['description'], $page['weight'], $page['retail'], $page['price'], $page['cost'], 
				$page['setup_fee'], $page['unit'], $page['recurring'], $page['inventory'], $page['instock'], 
				$page['lowstock'], $page['outstockmsg'], $page['outstockbuy'], $page['attribute'], $page['premium'], $page['featured'],
				$page['active'], $page['internal_productno'], $page['alink'], $page['alinktitle'], $page['alinktype'], $page['alink2'], 
				$page['alink2title'], $page['alink2type'], $page['alink3'], $page['alink3title'], $page['alink3type'], $page['rss_url'], 
				$page['contact'], $page['contact_email'], $page['contact_phone'], $page['contact_website'], $page['contact_photo'], $page['company'], 
				$page['company_email'], $page['company_phone'], $page['company_website'], $page['company_logo'], $page['subscribe_method'], $page['sales'], $page['min_qty']);
				if (Jaws_Error::IsError($result)) {
					$GLOBALS['app']->Session->PushLastResponse($result->getMessage(), RESPONSE_ERROR);
					return new Jaws_Error($result->getMessage(), _t('STORE_NAME'));
				} else {
					$GLOBALS['app']->Session->PushLastResponse(_t('STORE_POST_CREATED'), RESPONSE_NOTICE);
					return 'Main Image Added';
				}
			} else {
				$user_post_limit = $GLOBALS['app']->Registry->Get('/gadgets/Store/user_post_limit');
				$user_post_limit = (int)$user_post_limit;
				if (!is_null($OwnerID) && $user_post_limit > 0) {
					$posts = $model->GetAllPostsOfProduct($LinkID);
					if (!Jaws_Error::IsError($posts)) {
						$i = 0;
						foreach($posts as $post) {		            
							$i++;
						}
					} else {
						$GLOBALS['app']->Session->PushLastResponse($posts->getMessage(), RESPONSE_ERROR);
						return new Jaws_Error($posts->getMessage(), _t('STORE_NAME'));
					}

					if (($i+1) >= $user_post_limit) {
						return new Jaws_Error(_t('STORE_ERROR_POST_LIMIT_REACHED'), _t('STORE_NAME'));
					}
				}
				$url = "javascript:void(0);";

				$sql = "
					INSERT INTO [[product_posts]]
						([sort_order], [linkid], [title], 
						[description], [image], [image_width], [image_height], 
						[layout], [active], [ownerid], [created], [updated],
						[url], [url_target], [checksum])
					VALUES
						({sort_order}, {LinkID}, {title}, 
						{description}, {image}, {image_width}, {image_height},
						{layout}, {Active}, {OwnerID}, {now}, {now},
						{url}, {url_target}, {checksum})";

				  
				$description = strip_tags($description, '<p><a><b><em><i><strong><div><table><tbody><thead><tr><td><span><font><hr><br><marquee><ul><li>');
		  
				
				$params               		= array();
				$params['sort_order']       = (int)$sort_order;
				$params['title'] 			= $xss->parse(strip_tags($title));
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
				$params['checksum']		= $xss->parse($checksum);
				$params['now']        		= $GLOBALS['db']->Date();

				$result = $GLOBALS['db']->query($sql, $params);
				if (Jaws_Error::IsError($result)) {
					return new Jaws_Error(_t('STORE_ERROR_POST_NOT_ADDED'), _t('STORE_NAME'));
				}
				$newid = $GLOBALS['db']->lastInsertID('product_posts', 'id');

				if (empty($checksum)) {
					// Update checksum
					$params               	= array();
					$params['id'] 			= $newid;
					$params['checksum'] 	= $newid.':'.$config_key;
					
					$sql = '
						UPDATE [[product_posts]] SET
							[checksum] = {checksum}
						WHERE [id] = {id}';

					$result = $GLOBALS['db']->query($sql, $params);
					if (Jaws_Error::IsError($result)) {
						return false;
					}
				}
				
				// Let everyone know it has been added
				$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
				$res = $GLOBALS['app']->Shouter->Shout('onAddProductPost', $newid);
				if (Jaws_Error::IsError($res) || !$res) {
					$GLOBALS['app']->Session->PushLastResponse((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_POST_NOT_ADDED')), RESPONSE_ERROR);
					return new Jaws_Error((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_POST_NOT_ADDED')), _t('STORE_NAME'));
				}
				
				$GLOBALS['app']->Session->PushLastResponse(_t('STORE_POST_CREATED'), RESPONSE_NOTICE);
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
     * @return  boolean 	Success/failure
     */
    function UpdatePost($id, $sort_order, $title, $description, $image, $image_width, $image_height, 
		$layout, $active, $url_type, $internal_url, $external_url, $url_target = '_self', $auto = false)
	{
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$model = $GLOBALS['app']->LoadGadget('Store', 'Model');
        $page = $model->GetPost($id);
        if (Jaws_Error::isError($page)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_POST_NOT_FOUND'), RESPONSE_ERROR);
            return new Jaws_Error(_t('STORE_ERROR_POST_NOT_FOUND'), _t('STORE_NAME'));
        }

		$image = $this->cleanImagePath($image);
		
		$config_key = $GLOBALS['app']->Registry->Get('/config/key');		
		if (!empty($image) && strpos($page['checksum'], $config_key) !== false) {
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

		$sql = '
            UPDATE [[product_posts]] SET
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

		$description = strip_tags($description, '<p><a><b><em><i><strong><div><table><tbody><thead><tr><td><span><font><hr><br><marquee><ul><li>');
		
        $params               	= array();
        $params['id']         	= (int)$id;
        $params['sort_order'] 	= (int)$sort_order;
        $params['title'] 		= $xss->parse(strip_tags($title));
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
            return new Jaws_Error(_t('STORE_ERROR_POST_NOT_UPDATED'), _t('STORE_NAME'));
        }

		// Let everyone know it has been updated
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onUpdateProductPost', $id);
		if (Jaws_Error::IsError($res) || !$res) {
			$GLOBALS['app']->Session->PushLastResponse((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_POST_NOT_UPDATED')), RESPONSE_ERROR);
			return new Jaws_Error((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_POST_NOT_UPDATED')), _t('STORE_NAME'));
		}
				
        if ($auto) {
            $GLOBALS['app']->Session->PushLastResponse(_t('STORE_POST_AUTOUPDATED',
                                                     date('H:i:s'),
                                                     (int)$id,
                                                     date('D, d')),
                                                  RESPONSE_NOTICE);
        } else {
            $GLOBALS['app']->Session->PushLastResponse(_t('STORE_POST_UPDATED'), RESPONSE_NOTICE);
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
		// Let everyone know it has been deleted
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onDeleteProductPost', $id);
		if (Jaws_Error::IsError($res) || !$res) {
			$GLOBALS['app']->Session->PushLastResponse((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_POST_NOT_DELETED')), RESPONSE_ERROR);
			return new Jaws_Error((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_POST_NOT_DELETED')), _t('STORE_NAME'));
		}
				
        $sql = 'DELETE FROM [[product_posts]] WHERE [id] = {id}';
        $result = $GLOBALS['db']->query($sql, array('id' => $id));
        if (Jaws_Error::IsError($result)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_POST_NOT_DELETED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('STORE_ERROR_POST_NOT_DELETED'), _t('STORE_NAME'));
        }

		if ($massive === false) {
			$GLOBALS['app']->Session->PushLastResponse(_t('STORE_POST_DELETED'), RESPONSE_NOTICE);
        }
		return true;
    }

    /**
     * Create product attributes to assign to products.
     *
     * @category 	feature
     * @param   int  $sort_order 		The priority order
     * @param   string  $feature 		The name of the attribute
     * @param   int  $typeID      	ID of the attribute type this attribute belongs to.
     * @param   string 	$description  		Description of attribute
     * @param   string 	$add_amount  		Amount this attribute adds to product purchase price
     * @param   string 	$add_percent  		Percentage this attribute adds to product purchase price
     * @param   string 	$newprice  		Amount this attribute makes the product purchase price
     * @param   int 	$OwnerID  		The poster's user ID
     * @param   string 	$Active  		(Y/N) If the post is published or not
     * @param   string 	$checksum  		Unique ID
     * @param   boolean 	$auto       		If it's auto saved or not
     * @param   boolean 	$massive       		If it's part of a massive query
     * @access  public
     * @return  mixed 	ID of new attribute or Jaws_Error on failure
     */
    function AddProductAttribute(
		$sort_order, $feature, $typeID, $description = '', $add_amount = '', $add_percent = '', 
		$newprice = '', $OwnerID = null, $Active = 'Y', $checksum = '', $auto = false, $massive = false
	) {
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$model = $GLOBALS['app']->LoadGadget('Store', 'Model');
		$pages = $model->GetProductAttributes();
		if (!Jaws_Error::IsError($pages)) {
			foreach($pages as $p) {		            
				if (!empty($checksum)) {
					if ($p['checksum'] == $checksum) {
						return true;
					}
				}
			}
		}
        $sql = "
            INSERT INTO [[productattribute]]
                ([sort_order], [feature], [typeid], [description], [add_amount], 
				[add_percent], [newprice], [ownerid], 
				[active], [created], [updated], [checksum])
            VALUES
                ({sort_order}, {feature}, {typeID}, {description}, {add_amount}, 
				{add_percent}, {newprice}, {OwnerID}, 
				{Active}, {now}, {now}, {checksum})";

		$description = strip_tags($description, '<p><a><b><em><i><strong><div><table><tbody><thead><tr><td><span><font><hr><br><marquee><ul><li>');
		$OwnerID = (!is_null($OwnerID)) ? (int)$OwnerID : 0;

		// Format add_amount
		if (!empty($add_amount)) {
			$newstring = "";
			$array = str_split($add_amount);
			foreach($array as $char) {
				if (($char >= '0' && $char <= '9') || $char == '.') {
					$newstring .= $char;
				}
			}
			$add_amount = number_format($newstring, 2, '.', '');
		}
		
		// Format newprice
		if (!empty($newprice)) {
			$newstring = "";
			$array = str_split($newprice);
			foreach($array as $char) {
				if (($char >= '0' && $char <= '9') || $char == '.') {
					$newstring .= $char;
				}
			}
			$newprice = number_format($newstring, 2, '.', '');
		}

        
        $params               		= array();
        $params['sort_order']   	= (int)$sort_order;
        $params['feature']      	= $xss->parse(strip_tags($GLOBALS['app']->UTF8->str_replace(',', '', $feature)));
        $params['typeID']         	= (int)$typeID;
		$params['description']  	= str_replace("\r\n", "\n", $description);
		$params['add_amount']       = $add_amount;
		$params['add_percent']      = (int)$add_percent;
		$params['newprice']      	= $newprice;
		$params['OwnerID']         	= $OwnerID;
		$params['Active'] 			= $xss->parse($Active);
		$params['checksum'] 		= $xss->parse($checksum);
        $params['now']        		= $GLOBALS['db']->Date();

        $result = $GLOBALS['db']->query($sql, $params);
		if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('STORE_ERROR_PRODUCTATTRIBUTE_NOT_ADDED'), _t('STORE_NAME'));
        }
        $newid = $GLOBALS['db']->lastInsertID('productattribute', 'id');
		//echo '<br />StoreAdminModel->AddAttribute: newid: '.var_export($newid, true);

		if (empty($checksum)) {
			// Update checksum
			$config_key = $GLOBALS['app']->Registry->Get('/config/key');		
			$params               	= array();
			$params['id'] 			= $newid;
			$params['checksum'] 	= $newid.':'.$config_key;
			
			$sql = '
				UPDATE [[productattribute]] SET
					[checksum] = {checksum}
				WHERE [id] = {id}';

			$result = $GLOBALS['db']->query($sql, $params);
			if (Jaws_Error::IsError($result)) {
				return false;
			}
		}
		
		// Let everyone know it has been added
		if ($massive === false) {
			$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
			$res = $GLOBALS['app']->Shouter->Shout('onAddProductAttribute', $newid);
			if (Jaws_Error::IsError($res) || !$res) {
				$GLOBALS['app']->Session->PushLastResponse((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_PRODUCTATTRIBUTE_NOT_ADDED')), RESPONSE_ERROR);
				return new Jaws_Error((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_PRODUCTATTRIBUTE_NOT_ADDED')), _t('STORE_NAME'));
			}
			//echo '<br />StoreAdminModel->AddAttribute->Shout->onAddProductAttribute: res: '.var_export($res, true);
		}
		
        $GLOBALS['app']->Session->PushLastResponse(_t('STORE_PRODUCTATTRIBUTE_CREATED'), RESPONSE_NOTICE);
        return $newid;
    }

    /**
     * Updates a product attribute.
     *
     * @param   int     $id             The ID of the post to update.
     * @param   string  $feature 		The name of the attribute
     * @param   int  $typeID      	ID of the attribute type this attribute belongs to.
     * @param   string 	$description  		Description of attribute
     * @param   string 	$add_amount  		Amount this attribute adds to product purchase price
     * @param   string 	$add_percent  		Percentage this attribute adds to product purchase price
     * @param   string 	$newprice  		Amount this attribute makes the product purchase price
     * @param   string 	$Active  		(Y/N) If the post is published or not
     * @param   boolean 	$auto       		If it's auto saved or not
     * @param   boolean 	$massive       		If it's part of a massive query
     * @access  public
     * @return  boolean 	Success/failure
     */
    function UpdateProductAttribute(
		$id, $feature, $typeID, $description, $add_amount, $add_percent, $newprice, 
		$Active, $auto = false, $massive = false
	) {
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$model = $GLOBALS['app']->LoadGadget('Store', 'Model');
        $page = $model->GetAttribute($id);
        if (Jaws_Error::isError($page)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_PRODUCTATTRIBUTE_NOT_FOUND'), RESPONSE_ERROR);
            return new Jaws_Error(_t('STORE_ERROR_PRODUCTATTRIBUTE_NOT_FOUND'), _t('STORE_NAME'));
        }

        $sql = '
            UPDATE [[productattribute]] SET
				[feature] = {feature}, 
				[typeid] = {typeID}, 
				[description] = {description}, 
				[add_amount] = {add_amount},
				[add_percent] = {add_percent},
				[newprice] = {newprice},
				[active] = {Active}, 
				[updated] = {now}
			WHERE [id] = {id}';

		$description = strip_tags($description, '<p><a><b><em><i><strong><div><table><tbody><thead><tr><td><span><font><hr><br><marquee><ul><li>');

		// Format add_amount
		if (!empty($add_amount)) {
			$newstring = "";
			$array = str_split($add_amount);
			foreach($array as $char) {
				if (($char >= '0' && $char <= '9') || $char == '.') {
					$newstring .= $char;
				}
			}
			$add_amount = number_format($newstring, 2, '.', '');
		}
		
		// Format newprice
		if (!empty($newprice)) {
			$newstring = "";
			$array = str_split($newprice);
			foreach($array as $char) {
				if (($char >= '0' && $char <= '9') || $char == '.') {
					$newstring .= $char;
				}
			}
			$newprice = number_format($newstring, 2, '.', '');
		}

        
        $params               	= array();
        $params['id']         	= (int)$id;
        //$params['sort_order']   = $sort_order;
        $params['feature']      = $xss->parse(strip_tags($GLOBALS['app']->UTF8->str_replace(',', '', $feature)));
        $params['typeID']       = (int)$typeID;
		$params['description']  = str_replace("\r\n", "\n", $description);
		$params['add_amount']   = $add_amount;
		$params['add_percent']  = (int)$add_percent;
		$params['newprice']    	= $newprice;
		$params['Active'] 		= $xss->parse($Active);
        $params['now']        	= $GLOBALS['db']->Date();

        $result = $GLOBALS['db']->query($sql, $params);
		if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('STORE_ERROR_PRODUCTATTRIBUTE_NOT_UPDATED'), _t('STORE_NAME'));
        }

		// Let everyone know it has been updated
		if ($massive === false) {
			$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
			$res = $GLOBALS['app']->Shouter->Shout('onUpdateProductAttribute', $id);
			if (Jaws_Error::IsError($res) || !$res) {
				$GLOBALS['app']->Session->PushLastResponse((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_PRODUCTATTRIBUTE_NOT_UPDATED')), RESPONSE_ERROR);
				return new Jaws_Error((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_PRODUCTATTRIBUTE_NOT_UPDATED')), _t('STORE_NAME'));
			}
		}
        if ($auto) {
            $GLOBALS['app']->Session->PushLastResponse(_t('STORE_PRODUCTATTRIBUTE_AUTOUPDATED',
                                                     date('H:i:s'),
                                                     (int)$id,
                                                     date('D, d')),
                                                  RESPONSE_NOTICE);
        } else {
            $GLOBALS['app']->Session->PushLastResponse(_t('STORE_PRODUCTATTRIBUTE_UPDATED'), RESPONSE_NOTICE);
        }
        return true;
    }


    /**
     * Deletes a product attribute
     *
     * @param   int     $id     The ID of the product attribute to delete.
     * @param   boolean     $massive     Is this part of a massive delete?
     * @access  public
     * @return  bool    Success/failure
     */
    function DeleteProductAttribute($id, $massive = false)
    {
		if ($massive === false) {
			// Let everyone know it has been deleted
			$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
			$res = $GLOBALS['app']->Shouter->Shout('onDeleteProductAttribute', $id);
			if (Jaws_Error::IsError($res) || !$res) {
				$GLOBALS['app']->Session->PushLastResponse((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_PRODUCTATTRIBUTE_NOT_DELETED')), RESPONSE_ERROR);
				return new Jaws_Error((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_PRODUCTATTRIBUTE_NOT_DELETED')), _t('STORE_NAME'));
			}
		}
        $sql = 'DELETE FROM [[productattribute]] WHERE [id] = {id}';
        $result = $GLOBALS['db']->query($sql, array('id' => $id));
        if (Jaws_Error::IsError($result)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_PRODUCTATTRIBUTE_NOT_DELETED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('STORE_ERROR_PRODUCTATTRIBUTE_NOT_DELETED'), _t('STORE_NAME'));
        }

		if ($massive === false) {
			$GLOBALS['app']->Session->PushLastResponse(_t('STORE_PRODUCTATTRIBUTE_DELETED'), RESPONSE_NOTICE);
        }
		return true;
    }

    /**
     * Creates a new attribute type.
     *
     * @param   string  $title      		The title of the attribute type.
     * @param   string  $description    	The contents of the attribute type.
     * @param   string  $itype  Attribute type (TextBox/TextArea/HiddenField/RadioBtn/CheckBox/SelectBox)
     * @param   string 	$required  		(Y/N) Require the attribute type to be selected/filled out?
     * @param   int 	$OwnerID  		The poster's user ID
     * @param   string 	$Active  		(Y/N) If the post is published or not
     * @param   string 	$checksum  		Unique ID
     * @param   boolean 	$auto       		If it's auto saved or not
     * @access  public
     * @return  mixed  ID of new attribute type or Jaws_Error on failure
     */
    function AddAttributeType(
		$title, $description = '', $itype = 'TextBox', $required = 'N', $OwnerID = null, 
		$Active = 'Y', $checksum = '', $auto = false
	) {
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$model = $GLOBALS['app']->LoadGadget('Store', 'Model');
		$pages = $model->GetAttributeTypes();
		if (!Jaws_Error::IsError($pages)) {
			foreach($pages as $p) {		            
				if (!empty($checksum)) {
					if ($p['checksum'] == $checksum) {
						return true;
					}
				}
			}
		}
        $sql = "
            INSERT INTO [[attribute_types]]
                ([title], [description], [itype], [required], 
				[ownerid], [active], [created], [updated], [checksum])
            VALUES
                ({title}, {description}, {itype}, {required}, 
				{OwnerID}, {Active}, {now}, {now}, {checksum})";

		$description = strip_tags($description, '<p><a><b><em><i><strong><div><table><tbody><thead><tr><td><span><font><hr><br><marquee><ul><li>');
		$OwnerID = (!is_null($OwnerID)) ? (int)$OwnerID : 0;
       
		$params               		= array();
        $params['title']      		= $xss->parse(strip_tags($GLOBALS['app']->UTF8->str_replace(",", "", $title)));
		$params['description']   	= str_replace("\r\n", "\n", $description);
		$params['itype']         	= $xss->parse($itype);
		$params['required']        	= $xss->parse($required);
		$params['OwnerID']         	= $OwnerID;
        $params['Active'] 			= $xss->parse($Active);
        $params['checksum'] 		= $xss->parse($checksum);
        $params['now']        		= $GLOBALS['db']->Date();

        $result = $GLOBALS['db']->query($sql, $params);
		if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('STORE_ERROR_ATTRIBUTETYPE_NOT_ADDED'), _t('STORE_NAME'));
        }
        $newid = $GLOBALS['db']->lastInsertID('attribute_types', 'id');
		//echo '<br />StoreAdminModel->AddAttributeType: newid: '.var_export($newid, true);

		
		if (empty($checksum)) {
			// Update checksum
			$config_key = $GLOBALS['app']->Registry->Get('/config/key');		
			$params2               	= array();
			$params2['id'] 			= $newid;
			$params2['checksum'] 	= $newid.':'.$config_key;
			
			$sql = '
				UPDATE [[attribute_types]] SET
					[checksum] = {checksum}
				WHERE [id] = {id}';

			$result = $GLOBALS['db']->query($sql, $params2);
			if (Jaws_Error::IsError($result)) {
				return false;
			}
		}
		
		// Let everyone know it has been added
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onAddProductAttributeType', $newid);
		if (Jaws_Error::IsError($res) || !$res) {
			$GLOBALS['app']->Session->PushLastResponse((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_ATTRIBUTETYPE_NOT_ADDED')), RESPONSE_ERROR);
			return new Jaws_Error((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_ATTRIBUTETYPE_NOT_ADDED')), _t('STORE_NAME'));
		}
		//echo '<br />StoreAdminModel->AddAttributeType->Shout->onAddProductAttributeType: res: '.var_export($res, true);
		
        // Add default attribute if this has no children
		// So we can link it to a product properly
		if ($itype == 'TextBox' || $itype == 'TextArea' || $itype == 'Normal' || $itype == 'HiddenField') {
			$result = $this->AddProductAttribute(0, $params['title'], $newid, '', 0, 0, 0, $OwnerID, 'Y', '', true);
			//echo '<br />StoreAdminModel->AddAttributeType->AddProductAttribute: result: '.var_export($result, true);
		} 
		//exit;
		
		$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ATTRIBUTETYPE_CREATED'), RESPONSE_NOTICE);
        return $newid;
    }

    /**
     * Updates an attribute type.
     *
     * @param   int     $id             The ID of the attribute type to update.
     * @param   string  $title      		The title of the attribute type.
     * @param   string  $description    	The contents of the attribute type.
     * @param   string  $itype  Attribute type (TextBox/TextArea/HiddenField/RadioBtn/CheckBox/SelectBox)
     * @param   string 	$required  		(Y/N) Require the attribute type to be selected/filled out?
     * @param   string 	$Active  		(Y/N) If the attribute type is published or not
     * @param   boolean 	$auto       		If it's auto saved or not
     * @access  public
     * @return  boolean Success/failure
     */
    function UpdateAttributeType($id, $title, $description, $itype, $required, $Active, $auto = false)
	{
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$model = $GLOBALS['app']->LoadGadget('Store', 'Model');
        $page = $model->GetAttributeType($id);
        if (Jaws_Error::isError($page)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_ATTRIBUTETYPE_NOT_FOUND'), RESPONSE_ERROR);
            return new Jaws_Error(_t('STORE_ERROR_ATTRIBUTETYPE_NOT_FOUND'), _t('STORE_NAME'));
        }

        $sql = '
            UPDATE [[attribute_types]] SET
				[title] = {title}, 
				[description] = {description}, 
				[itype] = {itype}, 
				[required] = {required}, 
				[active] = {Active}, 
				[updated] = {now}
			WHERE [id] = {id}';

		$description = strip_tags($description, '<p><a><b><em><i><strong><div><table><tbody><thead><tr><td><span><font><hr><br><marquee><ul><li>');
        
        $params               		= array();
        $params['id']         		= (int)$id;
        $params['title']      		= $xss->parse(strip_tags($GLOBALS['app']->UTF8->str_replace(",", "", $title)));
		$params['description']   	= str_replace("\r\n", "\n", $description);
		$params['itype']         	= $xss->parse($itype);
		$params['required']        	= $xss->parse($required);
        $params['Active'] 			= $xss->parse($Active);
        $params['now']        		= $GLOBALS['db']->Date();

        $result = $GLOBALS['db']->query($sql, $params);
		if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('STORE_ERROR_ATTRIBUTETYPE_NOT_UPDATED'), _t('STORE_NAME'));
        }

        // Update default attribute, so we can link it to a product properly
		if ($itype == 'TextBox' || $itype == 'TextArea' || $itype == 'Normal' || $itype == 'HiddenField') {
			$attributes = $model->GetAttributesOfType((int)$id);
			if (Jaws_Error::isError($attributes) || !isset($attributes[0]['id'])) {
				$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_PRODUCTATTRIBUTE_NOT_FOUND'), RESPONSE_ERROR);
				return new Jaws_Error(_t('STORE_ERROR_PRODUCTATTRIBUTE_NOT_FOUND'), _t('STORE_NAME'));
			} else {
				$this->UpdateProductAttribute($attributes[0]['id'], $params['title'], $id, '', 0, 0, 0, 'Y', true);
			}
		} 
		
		// Let everyone know it has been updated
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onUpdateProductAttributeType', $id);
		if (Jaws_Error::IsError($res) || !$res) {
			$GLOBALS['app']->Session->PushLastResponse((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_ATTRIBUTETYPE_NOT_UPDATED')), RESPONSE_ERROR);
			return new Jaws_Error((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_ATTRIBUTETYPE_NOT_UPDATED')), _t('STORE_NAME'));
		}
		
        if ($auto) {
            $GLOBALS['app']->Session->PushLastResponse(_t('STORE_ATTRIBUTETYPE_AUTOUPDATED',
                                                     date('H:i:s'),
                                                     (int)$id,
                                                     date('D, d')),
                                                  RESPONSE_NOTICE);
        } else {
            $GLOBALS['app']->Session->PushLastResponse(_t('STORE_ATTRIBUTETYPE_UPDATED'), RESPONSE_NOTICE);
        }
        return true;
    }


    /**
     * Deletes an attribute type
     *
     * @param   int     $id     The ID of the page to delete.
     * @param   boolean     $massive     Is this part of a massive delete?
     * @access  public
     * @return  bool    Success/failure
     */
    function DeleteAttributeType($id, $massive = false)
    {
		// Let everyone know it has been deleted
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onDeleteProductAttributeType', $id);
		if (Jaws_Error::IsError($res) || !$res) {
			$GLOBALS['app']->Session->PushLastResponse((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_ATTRIBUTETYPE_NOT_DELETED')), RESPONSE_ERROR);
			return new Jaws_Error((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_ATTRIBUTETYPE_NOT_DELETED')), _t('STORE_NAME'));
		}
		
		$model = $GLOBALS['app']->LoadGadget('Store', 'Model');
		// Delete product attributes
		$rids = $model->GetAttributesOfType($id);
		if (Jaws_Error::IsError($rids)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_ATTRIBUTETYPE_NOT_DELETED'), RESPONSE_ERROR);
			return new Jaws_Error(_t('STORE_ERROR_ATTRIBUTETYPE_NOT_DELETED'), _t('STORE_NAME'));
		}
		$mass_delete = array();
		foreach ($rids as $rid) {
			if (!$this->DeleteProductAttribute($rid['id'], true)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_PRODUCTATTRIBUTE_NOT_DELETED'), RESPONSE_ERROR);
				return new Jaws_Error(_t('STORE_ERROR_PRODUCTATTRIBUTE_NOT_DELETED'), _t('STORE_NAME'));
			}
			array_push($mass_delete, $rid['id']);
		}
		$res = $GLOBALS['app']->Shouter->Shout('onMassDeleteProductAttribute', $mass_delete);
		if (Jaws_Error::IsError($res) || !$res) {
			$GLOBALS['app']->Session->PushLastResponse((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('GLOBAL_ERROR_EVENTS_LISTENER_ERROR')), RESPONSE_ERROR);
			return new Jaws_Error((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('GLOBAL_ERROR_EVENTS_LISTENER_ERROR')), _t('STORE_NAME'));
		}
        
		$sql = 'DELETE FROM [[attribute_types]] WHERE [id] = {id}';
        $result = $GLOBALS['db']->query($sql, array('id' => $id));
        if (Jaws_Error::IsError($result)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_ATTRIBUTETYPE_NOT_DELETED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('STORE_ERROR_ATTRIBUTETYPE_NOT_DELETED'), _t('STORE_NAME'));
        }

        if ($massive === false) {
			$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ATTRIBUTETYPE_DELETED'), RESPONSE_NOTICE);
        }
		return true;
    }

    /**
     * Create product sales and assign to products.
     *
     * @category 	feature
     * @param   integer  $title 		The name of the sale
     * @param   datetime  $startdate 		Start date of the sale
     * @param   datetime  $enddate 		End date of the sale
     * @param   string  $description      	Description of the sale
     * @param   string 	$discount_amount  		Amount this sale subtracts from product purchase price
     * @param   string 	$discount_percent  		Percentage this sale subtracts from product purchase price
     * @param   string 	$discount_newprice  		Amount this sale makes the product purchase price
     * @param   string  $coupon_code      	Coupon code of sale.
     * @param   string 	$featured  		(Y/N) If the sale is featured or not
     * @param   integer 	$OwnerID  		The poster's user ID
     * @param   string 	$Active  		(Y/N) If the post is published or not
     * @param   string 	$checksum  		Unique ID
     * @param   boolean 	$auto       		If it's auto saved or not
     * @access  public
     * @return  mixed 	ID of new sale or Jaws_Error on failure
     */
    function AddSale(
		$title, $startdate = null, $enddate = null, $description = '', $discount_amount = 0, 
		$discount_percent = 0, $discount_newprice = 0, $coupon_code = '', $featured = 'N',
		$OwnerID = null, $Active = 'Y', $checksum = '', $auto = false
	) {
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$model = $GLOBALS['app']->LoadGadget('Store', 'Model');
		$pages = $model->GetSales();
		if (!Jaws_Error::IsError($pages)) {
			foreach($pages as $p) {		            
				if (!empty($checksum)) {
					if ($p['checksum'] == $checksum) {
						return true;
					}
				}
			}
		}
        $sql = "
            INSERT INTO [[sales]]
                ([title], [startdate], [enddate], [description], [discount_amount], 
				[discount_percent], [discount_newprice], [coupon_code], 
				[featured], [ownerid], [active], [created], [updated], [checksum])
            VALUES
                ({title}, {startdate}, {enddate}, {description}, {discount_amount}, 
				{discount_percent}, {discount_newprice}, {coupon_code}, 
				{featured}, {OwnerID}, {Active}, {now}, {now}, {checksum})";

		$description = strip_tags($description, '<p><a><b><em><i><strong><div><table><tbody><thead><tr><td><span><font><hr><br><marquee><ul><li>');
		$OwnerID = (!is_null($OwnerID)) ? (int)$OwnerID : 0;

		// Format discount_amount
		if (!empty($discount_amount) && $discount_amount > 0) {
			$newstring = "";
			$array = str_split($discount_amount);
			foreach($array as $char) {
				if (($char >= '0' && $char <= '9') || $char == '.') {
					$newstring .= $char;
				}
			}
			$discount_amount = number_format($newstring, 2, '.', '');
		}
		
		// Format discount_newprice
		if (!empty($discount_newprice) && $discount_newprice > 0) {
			$newstring = "";
			$array = str_split($discount_newprice);
			foreach($array as $char) {
				if (($char >= '0' && $char <= '9') || $char == '.') {
					$newstring .= $char;
				}
			}
			$discount_newprice = number_format($newstring, 2, '.', '');
		}
		
		// Format discount_percent
		if (!empty($discount_percent) && $discount_percent > 0) {
			$newstring = "";
			$array = str_split($discount_percent);
			foreach($array as $char) {
				if (($char >= '0' && $char <= '9') || $char == '.') {
					$newstring .= $char;
				}
			}
			$discount_percent = number_format($newstring, 2, '.', '');
		}

        
        $params               		= array();
        $params['title']      		= $xss->parse(strip_tags($GLOBALS['app']->UTF8->str_replace(',', '', $title)));
        $params['startdate'] 		= $GLOBALS['db']->Date(strtotime($startdate));
        $params['enddate'] 			= $GLOBALS['db']->Date(strtotime($enddate));
		$params['description']  	= str_replace("\r\n", "\n", $description);
		$params['discount_amount']  = $discount_amount;
		$params['discount_percent'] = $discount_percent;
		$params['discount_newprice'] = $discount_newprice;
		$params['coupon_code'] 		= $xss->parse(strip_tags($coupon_code));
		$params['featured'] 		= $xss->parse($featured);
		$params['OwnerID']         	= $OwnerID;
		$params['Active'] 			= $xss->parse($Active);
		$params['checksum'] 		= $xss->parse($checksum);
        $params['now']        		= $GLOBALS['db']->Date();

        $result = $GLOBALS['db']->query($sql, $params);
		if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('STORE_ERROR_SALE_NOT_ADDED'), _t('STORE_NAME'));
        }
        $newid = $GLOBALS['db']->lastInsertID('sales', 'id');

		if (empty($checksum)) {
			// Update checksum
			$config_key = $GLOBALS['app']->Registry->Get('/config/key');		
			$params               	= array();
			$params['id'] 			= $newid;
			$params['checksum'] 	= $newid.':'.$config_key;
			
			$sql = '
				UPDATE [[sales]] SET
					[checksum] = {checksum}
				WHERE [id] = {id}';

			$result = $GLOBALS['db']->query($sql, $params);
			if (Jaws_Error::IsError($result)) {
				return false;
			}
		}
		
		// Let everyone know it has been added
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onAddProductSale', $newid);
		if (Jaws_Error::IsError($res) || !$res) {
			$GLOBALS['app']->Session->PushLastResponse((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_SALE_NOT_ADDED')), RESPONSE_ERROR);
			return new Jaws_Error((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_SALE_NOT_ADDED')), _t('STORE_NAME'));
		}
		
        $GLOBALS['app']->Session->PushLastResponse(_t('STORE_SALE_CREATED'), RESPONSE_NOTICE);
        return $newid;
    }

    /**
     * Updates a sale.
     *
     * @param   int     $id             The ID of the sale to update.
     * @param   integer  $title 		The name of the sale
     * @param   datetime  $startdate 		Start date of the sale
     * @param   datetime  $enddate 		End date of the sale
     * @param   string  $description      	Description of the sale
     * @param   string 	$discount_amount  		Amount this sale subtracts from product purchase price
     * @param   string 	$discount_percent  		Percentage this sale subtracts from product purchase price
     * @param   string 	$discount_newprice  		Amount this sale makes the product purchase price
     * @param   string  $coupon_code      	Coupon code of sale.
     * @param   string 	$featured  		(Y/N) If the sale is featured or not
     * @param   string 	$Active  		(Y/N) If the post is published or not
     * @param   boolean 	$auto       		If it's auto saved or not
     * @access  public
     * @return  boolean Success/failure
     */
    function UpdateSale(
		$id, $title, $startdate, $enddate, $description, $discount_amount, 
		$discount_percent, $discount_newprice, $coupon_code, $featured, $Active, $auto = false
	) {
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$model = $GLOBALS['app']->LoadGadget('Store', 'Model');
        $page = $model->GetSale($id);
        if (Jaws_Error::isError($page)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_SALE_NOT_FOUND'), RESPONSE_ERROR);
            return new Jaws_Error(_t('STORE_ERROR_SALE_NOT_FOUND'), _t('STORE_NAME'));
        }

        $sql = '
            UPDATE [[sales]] SET
				[title] = {title}, 
				[startdate] = {startdate}, 
				[enddate] = {enddate}, 
				[description] = {description}, 
				[discount_amount] = {discount_amount},
				[discount_percent] = {discount_percent},
				[discount_newprice] = {discount_newprice},
				[coupon_code] = {coupon_code},
				[featured] = {featured},
				[active] = {Active}, 
				[updated] = {now}
			WHERE [id] = {id}';

		$description = strip_tags($description, '<p><a><b><em><i><strong><div><table><tbody><thead><tr><td><span><font><hr><br><marquee><ul><li>');

		// Format discount_amount
		if (!empty($discount_amount)) {
			$newstring = "";
			$array = str_split($discount_amount);
			foreach($array as $char) {
				if (($char >= '0' && $char <= '9') || $char == '.') {
					$newstring .= $char;
				}
			}
			$discount_amount = number_format($newstring, 2, '.', '');
		}
		
		// Format discount_newprice
		if (!empty($discount_newprice)) {
			$newstring = "";
			$array = str_split($discount_newprice);
			foreach($array as $char) {
				if (($char >= '0' && $char <= '9') || $char == '.') {
					$newstring .= $char;
				}
			}
			$discount_newprice = number_format($newstring, 2, '.', '');
		}

		// Format discount_percent
		if (!empty($discount_percent) && $discount_percent > 0) {
			$newstring = "";
			$array = str_split($discount_percent);
			foreach($array as $char) {
				if (($char >= '0' && $char <= '9') || $char == '.') {
					$newstring .= $char;
				}
			}
			$discount_percent = number_format($newstring, 2, '.', '');
		}

        
        $params               		= array();
        $params['id']         		= (int)$id;
        $params['title']      		= $xss->parse(strip_tags($GLOBALS['app']->UTF8->str_replace(',', '', $title)));
        $params['startdate'] 		= $GLOBALS['db']->Date(strtotime($startdate));
        $params['enddate'] 			= $GLOBALS['db']->Date(strtotime($enddate));
		$params['description']  	= str_replace("\r\n", "\n", $description);
		$params['discount_amount']  = $discount_amount;
		$params['discount_percent'] = $discount_percent;
		$params['discount_newprice'] = $discount_newprice;
		$params['coupon_code'] 		= $xss->parse(strip_tags($coupon_code));
		$params['featured'] 		= $xss->parse($featured);
		$params['Active'] 			= $xss->parse($Active);
        $params['now']        		= $GLOBALS['db']->Date();

        $result = $GLOBALS['db']->query($sql, $params);
		if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('STORE_ERROR_SALE_NOT_UPDATED'), _t('STORE_NAME'));
        }

		// Let everyone know it has been updated
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onUpdateProductSale', $id);
		if (Jaws_Error::IsError($res) || !$res) {
			$GLOBALS['app']->Session->PushLastResponse((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_SALE_NOT_UPDATED')), RESPONSE_ERROR);
			return new Jaws_Error((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_SALE_NOT_UPDATED')), _t('STORE_NAME'));
		}
		
        if ($auto) {
            $GLOBALS['app']->Session->PushLastResponse(_t('STORE_SALE_AUTOUPDATED',
                                                     date('H:i:s'),
                                                     (int)$id,
                                                     date('D, d')),
                                                  RESPONSE_NOTICE);
        } else {
            $GLOBALS['app']->Session->PushLastResponse(_t('STORE_SALE_UPDATED'), RESPONSE_NOTICE);
        }
        return true;
    }


    /**
     * Deletes a sale
     *
     * @param   int     $id     The ID of the sale to delete.
     * @param   boolean     $massive     Is this part of a massive delete?
     * @access  public
     * @return  bool    Success/failure
     */
    function DeleteSale($id, $massive = false)
    {
		// Let everyone know it has been deleted
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onDeleteProductSale', $id);
		if (Jaws_Error::IsError($res) || !$res) {
			$GLOBALS['app']->Session->PushLastResponse((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_SALE_NOT_DELETED')), RESPONSE_ERROR);
			return new Jaws_Error((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_SALE_NOT_DELETED')), _t('STORE_NAME'));
		}
		
        $sql = 'DELETE FROM [[sales]] WHERE [id] = {id}';
        $result = $GLOBALS['db']->query($sql, array('id' => $id));
        if (Jaws_Error::IsError($result)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_SALE_NOT_DELETED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('STORE_ERROR_SALE_NOT_DELETED'), _t('STORE_NAME'));
        }

		if ($massive === false) {
			$GLOBALS['app']->Session->PushLastResponse(_t('STORE_SALE_DELETED'), RESPONSE_NOTICE);
        }
		return true;
    }


    /**
     * Create product brands and assign to products.
     *
     * @category 	feature
     * @param   string  $title      		The title of the brand.
     * @param   string  $description    	Description of the brand.
     * @param   string  $image   		An image to accompany the brand
     * @param   int  	 $image_width     Image width in pixels
     * @param   int  	 $image_height    Image height in pixels
     * @param   int 	$layout  		The layout mode of the brand
     * @param   string 	$active  		(Y/N) If the brand is published or not
     * @param   int 	$OwnerID  		The poster's user ID
     * @param   string 	$url_type  		The URL type of brand's image (imageviewer/internal/external)
     * @param   string 	$internal_url  		The internal URL of brand's image
     * @param   string 	$external_url  		The external URL of brand's image
     * @param   string 	$url_target  		The URL target of brand's image (_self/_blank)
     * @param   string 	$image_code  		Custom HTML code
     * @param   string 	$checksum  		Unique ID
     * @param   boolean 	$auto       		If it's auto saved or not
     * @access  public
     * @return  mixed 	ID of new brand or Jaws_Error on failure
     */
    function AddBrand(
		$title, $description = '', $image = '', $image_width = '', $image_height = '', 
		$layout = 0, $active = 'Y', $OwnerID = null, $url_type = 'imageviewer', $internal_url = '', 
		$external_url = '', $url_target = '_self', $image_code = '', $checksum = '', $auto = false
	) {        
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$model = $GLOBALS['app']->LoadGadget('Store', 'Model');
		$pages = $model->GetBrands();
		if (!Jaws_Error::IsError($pages)) {
			foreach($pages as $p) {		            
				if (!empty($checksum)) {
					if ($p['checksum'] == $checksum) {
						return true;
					}
				}
			}
		}
		$OwnerID = (!is_null($OwnerID)) ? (int)$OwnerID : 0;
		if (
			$OwnerID == 0 && 
			$url_type == 'external' && substr(strtolower(trim($external_url)), 0, 4) == 'http' && 
			strpos(strtolower(trim(urldecode($external_url))), 'javascript:') === false
		) {
			$url = $xss->parse($external_url);
		} else if ($url_type == 'internal' && !empty($internal_url) && strpos(strtolower(trim(urldecode($internal_url))), 'javascript:') === false) {
			$url = $xss->parse($internal_url);
		} else if ($url_type == 'imageviewer') {
			$url = "javascript:void(0);";
		} else {
	        $GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_INVALID_URL'), RESPONSE_ERROR);
            return new Jaws_Error(_t('STORE_ERROR_INVALID_URL'), _t('STORE_NAME'));
		}

		$image = $this->cleanImagePath($image);
		
		$config_key = $GLOBALS['app']->Registry->Get('/config/key');		
		if (!empty($image) && (empty($checksum) || strpos($checksum, $config_key) !== false)) {
			if (
				$OwnerID > 0 && 
				(substr(strtolower(trim($image)), 0, 4) == 'http' || 
				substr(strtolower(trim($image)), 0, 2) == '//' || 
				substr(strtolower(trim($image)), 0, 2) == '\\\\')
			) {
				$image = '';
			}
		}

		$sql = "
			INSERT INTO [[productbrand]]
				([title], [description], [image], [image_width], [image_height], 
				[layout], [active], [ownerid], [created], [updated],
				[url], [url_target], [image_code], [checksum])
			VALUES
				({title}, 
				{description}, {image}, {image_width}, {image_height},
				{layout}, {Active}, {OwnerID}, {now}, {now},
				{url}, {url_target}, {image_code}, {checksum})";

		  
		if ($image_code != '' && !empty($image)) {
			$image = '';
			$image_width = 0;
			$image_height = 0;
		}
		
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
		$params['checksum']			= $xss->parse($checksum);
		$params['now']        		= $GLOBALS['db']->Date();

		$result = $GLOBALS['db']->query($sql, $params);
		if (Jaws_Error::IsError($result)) {
			return new Jaws_Error(_t('STORE_ERROR_BRAND_NOT_ADDED'), _t('STORE_NAME'));
		}
		$newid = $GLOBALS['db']->lastInsertID('productbrand', 'id');

		if (empty($checksum)) {
			// Update checksum
			$params               	= array();
			$params['id'] 			= $newid;
			$params['checksum'] 	= $newid.':'.$config_key;
			
			$sql = '
				UPDATE [[productbrand]] SET
					[checksum] = {checksum}
				WHERE [id] = {id}';

			$result = $GLOBALS['db']->query($sql, $params);
			if (Jaws_Error::IsError($result)) {
				return false;
			}
		}
		
		// Let everyone know it has been added
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onAddProductBrand', $newid);
		if (Jaws_Error::IsError($res) || !$res) {
			$GLOBALS['app']->Session->PushLastResponse((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_BRAND_NOT_ADDED')), RESPONSE_ERROR);
			return new Jaws_Error((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_BRAND_NOT_ADDED')), _t('STORE_NAME'));
		}
		
		$GLOBALS['app']->Session->PushLastResponse(_t('STORE_BRAND_CREATED'), RESPONSE_NOTICE);
		return $newid;
    }

    /**
     * Updates a brand.
     *
     * @param   int     $id             The ID of the brand to update.
     * @param   string  $title      		The title of the brand.
     * @param   string  $description    	Description of the brand.
     * @param   string  $image   		An image to accompany the brand
     * @param   int  	 $image_width     Image width in pixels
     * @param   int  	 $image_height    Image height in pixels
     * @param   int 	$layout  		The layout mode of the brand
     * @param   string 	$active  		(Y/N) If the brand is published or not
     * @param   string 	$url_type  		The URL type of brand's image (imageviewer/internal/external)
     * @param   string 	$internal_url  		The internal URL of brand's image
     * @param   string 	$external_url  		The external URL of brand's image
     * @param   string 	$url_target  		The URL target of brand's image (_self/_blank)
     * @param   string 	$image_code  		Custom HTML code
     * @param   boolean 	$auto       		If it's auto saved or not
     * @access  public
     * @return  boolean Success/failure
     */
    function UpdateBrand(
		$id, $title, $description, $image, $image_width, $image_height, 
		$layout, $active, $url_type, $internal_url, $external_url, 
		$url_target, $image_code, $auto = false
	) {
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$model = $GLOBALS['app']->LoadGadget('Store', 'Model');
        $page = $model->GetBrand($id);
        if (Jaws_Error::isError($page)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_BRAND_NOT_FOUND'), RESPONSE_ERROR);
            return new Jaws_Error(_t('STORE_ERROR_BRAND_NOT_FOUND'), _t('STORE_NAME'));
        }
		
		$image = $this->cleanImagePath($image);
		
		$config_key = $GLOBALS['app']->Registry->Get('/config/key');		
		if (!empty($image) && strpos($page['checksum'], $config_key) !== false) {
			if (
				$page['ownerid'] > 0 && 
				(substr(strtolower(trim($image)), 0, 4) == 'http' || 
				substr(strtolower(trim($image)), 0, 2) == '//' || 
				substr(strtolower(trim($image)), 0, 2) == '\\\\')
			) {
				$image = '';
			}
		}

		if (
			$page['ownerid'] == 0 && $url_type == 'external' && 
			substr(strtolower(trim($external_url)), 0, 4) == 'http' && 
			strpos(strtolower(trim(urldecode($external_url))), 'javascript:') === false
		) {
			$url = $xss->parse($external_url);
		} else if ($url_type == 'internal' && !empty($internal_url) && strpos(strtolower(trim(urldecode($internal_url))), 'javascript:') === false) {
			$url = $xss->parse($internal_url);
		} else if ($url_type == 'imageviewer') {
			$url = "javascript:void(0);";
		} else {
	        $GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_INVALID_URL'), RESPONSE_ERROR);
            return new Jaws_Error(_t('STORE_ERROR_INVALID_URL'), _t('STORE_NAME'));
		}

		$sql = '
            UPDATE [[productbrand]] SET
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

		if ($image_code != '' && !empty($image)) {
			$image = '';
			$image_width = 0;
			$image_height = 0;
		}
		
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
            return new Jaws_Error(_t('STORE_ERROR_BRAND_NOT_UPDATED'), _t('STORE_NAME'));
        }

		// Let everyone know it has been updated
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onUpdateProductBrand', $id);
		if (Jaws_Error::IsError($res) || !$res) {
			$GLOBALS['app']->Session->PushLastResponse((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_BRAND_NOT_UPDATED')), RESPONSE_ERROR);
			return new Jaws_Error((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_BRAND_NOT_UPDATED')), _t('STORE_NAME'));
		}
		
        if ($auto) {
            $GLOBALS['app']->Session->PushLastResponse(_t('STORE_BRAND_AUTOUPDATED',
                                                     date('H:i:s'),
                                                     (int)$id,
                                                     date('D, d')),
                                                  RESPONSE_NOTICE);
        } else {
            $GLOBALS['app']->Session->PushLastResponse(_t('STORE_BRAND_UPDATED'), RESPONSE_NOTICE);
        }
        return true;
    }


    /**
     * Deletes a brand
     *
     * @param   int     $id     The ID of the brand to delete.
     * @param   boolean     $massive     Is this part of a massive delete?
     * @access  public
     * @return  bool    Success/failure
     */
    function DeleteBrand($id, $massive = false)
    {
		// Let everyone know it has been deleted
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onDeleteProductBrand', $id);
		if (Jaws_Error::IsError($res) || !$res) {
            $GLOBALS['app']->Session->PushLastResponse((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_BRAND_NOT_DELETED')), RESPONSE_ERROR);
            return new Jaws_Error((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_BRAND_NOT_DELETED')), _t('STORE_NAME'));
		}
		
        $sql = 'DELETE FROM [[productbrand]] WHERE [id] = {id}';
        $result = $GLOBALS['db']->query($sql, array('id' => $id));
        if (Jaws_Error::IsError($result)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_BRAND_NOT_DELETED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('STORE_ERROR_BRAND_NOT_DELETED'), _t('STORE_NAME'));
        }

		if ($massive === false) {
			$GLOBALS['app']->Session->PushLastResponse(_t('STORE_BRAND_DELETED'), RESPONSE_NOTICE);
        }
		return true;
    }

    /**
     * Re-sorts posts
     *
     * @param   int     $pids     ',' separated values of IDs of the posts
     * @param   string     $newsorts     ',' separated values of new sort_orders
     * @param   string     $table     DB table to sort on
     * @access  public
     * @return  bool    Success/failure
     */
    function SortItem($pids, $newsorts, $table = 'product')
    {
		//$model = $GLOBALS['app']->LoadGadget('Store', 'Model');
        $ids = explode(',', $pids);
        $sorts = explode(',', $newsorts);
        $i = 0;
		if ($table != 'product_posts' && $table != 'product' && $table != 'productattribute' && $table != 'attribute_types') {
			$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_POST_NOT_SORTED'), RESPONSE_ERROR);
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
					$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_POST_NOT_SORTED'), RESPONSE_ERROR);
					//$GLOBALS['app']->Session->PushLastResponse($result1->GetMessage(), RESPONSE_ERROR);
					return false;
				}
				$i++;
			}
		}
		$GLOBALS['app']->Session->PushLastResponse(_t('STORE_POST_UPDATED'), RESPONSE_NOTICE);
		return true;
    }

    /**
     * Search for products that matches multiple queries
     *
     * @param   string  $search  Keyword (title/description) of products we want to look for
     * @param   string  $brandid  Brand ID products we want to display
     * @param   string  $sales  Comma separated sales IDs to match against
     * @param   string  $category  Comma separated product category IDs to match against
     * @param   string  $attributes  Comma separated attribute IDs to match against
     * @param   string  $group  Group ID
     * @param   string  $OwnerID  Owner's ID
     * @param   int     $pid 	Product category to search in
     * @param   string     $sortColumn 	Product db table column to sort on
     * @param   string     $sortDir 	Sort direction (ASC/DESC)
     * @param   string     $active 	(Y/N) Search active/inactive products
     * @param   int     $limit  Data limit
     * @param   int     $offSet  Data offset
     * @param   string     $random_seed 	Seed for randomization
     * @access  public
     * @return  array   Array of matches
     */
    function MultipleSearchProducts(
		$search, $brandid = '', $sales = '', $category = '', $attributes = '', $group = '', $OwnerID = '', 
		$pid = null, $sortColumn = 'sort_order', $sortDir = 'ASC', $active = null, $limit = null, 
		$offSet = null, $random_seed = '', $recursive = false
	) {
        $fields = array(
			'sort_order', 'premium', 'price', 'brandid', 'featured', 'ownerid', 
			'title', 'created', 'updated', 'active', 'attribute'
		);
        $sortColumn = strtolower($sortColumn);
        if (!in_array($sortColumn, $fields)) {
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_WARNING, _t('STORE_ERROR_UNKNOWN_COLUMN'));
            }
            $sortColumn = 'sort_order';
        }

        $sortDir = strtoupper($sortDir);
        if ($sortDir == 'DESC') {
            $sortDir = 'DESC';
        } else {
            $sortDir = 'ASC';
        }
        
		$search = (is_null($search) ? '' : $search);
        $category = (is_null($category) ? '' : $category);
        $OwnerID = (empty($OwnerID) || trim($OwnerID) == '' ? null : $OwnerID);
		
		$result = array();
		$model = $GLOBALS['app']->LoadGadget('Store', 'Model');
		//$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		if (!is_null($pid)) {
			$result = $model->GetAllProductsOfParent(
				(int)$pid, $sortColumn, $sortDir, $active, $OwnerID, $search, $brandid, 
				$sales, $category, $attributes, $limit, $offSet, $random_seed, $recursive
			);
		} else if (!is_null($group) && !empty($group)) {
			$result = $model->GetStoreOfGroup(
				(int)$group, $sortColumn, $sortDir, $active, $OwnerID, $search, $brandid, 
				$sales, $category, $attributes, $limit, $offSet, $random_seed
			);
		} else {
			$result = $model->GetProducts(
				$limit, $sortColumn, $sortDir, $offSet, $OwnerID, $active, null, $search, null, 
				$brandid, $sales, $category, $attributes, $random_seed, $recursive
			);
		}
			
		return $result;
    }

    /**
     * Get total of search for products that matches multiple queries
     *
     * @param   string  $search  Keyword (title/description) of products we want to look for
     * @param   string  $brandid  Brand ID products we want to display
     * @param   string  $sales  Comma separated sales IDs to match against
     * @param   string  $category  Comma separated product category IDs to match against
     * @param   string  $attributes  Comma separated attribute IDs to match against
     * @param   string  $group  Group ID
     * @param   string  $OwnerID  Owner's ID
     * @param   int     $pid 	Product category to search in
     * @param   string     $active 	(Y/N) Search active/inactive products
     * @access  public
     * @return  array   Array of matches
     */
    function GetTotalOfMultipleSearchProducts(
		$search, $brandid = '', $sales = '', $category = '', $attributes = '', $group = '', $OwnerID = '', 
		$pid = null, $active = null, $recursive = false
	) {        
		$search = (is_null($search) ? '' : $search);
        $category = (is_null($category) ? '' : $category);
        $OwnerID = (empty($OwnerID) || trim($OwnerID) == '' ? null : $OwnerID);
				
		$result = array();
		$model = $GLOBALS['app']->LoadGadget('Store', 'Model');
		//$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		if (!is_null($pid)) {
			$result = $model->GetTotalOfProductsOfParent(
				(int)$pid, 'sort_order', 'ASC', $active, $OwnerID, $search, $brandid, 
				$sales, $category, $attributes, $recursive
			);
		} else if (!is_null($group) && trim($group) != '') {
			$result = $model->GetTotalOfStoreOfGroup(
				(int)$group, 'sort_order', 'ASC', $active, $OwnerID, $search, $brandid, 
				$sales, $category, $attributes
			);
		} else {
			$result = $model->GetTotalOfProducts(
				'sort_order', 'ASC', $OwnerID, $active, null, $search, null, 
				$brandid, $sales, $category, $attributes
			);
		}
			
		return $result;
	}
	
    /**
     * Search for products that match multiple queries
     * in the title or content and return array of given key
     *
     * @param   string  $search  Keyword (title/description) of products we want to look for
     * @param   string  $brandid  Brand ID products we want to display
     * @param   string  $sales  Comma separated sales IDs to match against
     * @param   string  $category  Comma separated product category IDs to match against
     * @param   string  $attributes  Comma separated attribute IDs to match against
     * @param   string  $OwnerID  Owner's ID
     * @param   int     $pid 	Product category to search in
     * @param   boolean     $only_titles 	If true return only titles, otherwise return <span>s for autocomplete
     * @param   string     $sortColumn 	Product db table column to sort on
     * @param   string     $sortDir 	Sort direction (ASC/DESC)
     * @param   string     $return 	Product db table column to return
     * @param   string     $links 	(Y/N) Return links?
     * @access  public
     * @return  array   Array of matches
     */
    function SearchKeyWithProducts(
		$search, $brandid = '', $sales = '', $category = '', $attributes = '', $OwnerID = null, 
		$pid = null, $only_titles = false, $sortColumn = 'title', $sortDir = 'ASC', 
		$return = 'title', $links = 'N'
	) {
        $return = strtolower($return);
        $fields = array('sort_order', 'premium', 'price', 'featured', 'ownerid', 'title', 
		'created', 'updated', 'active', 'attribute', 'brandid', 'sales');
        $sortColumn = strtolower($sortColumn);
        if (!in_array($sortColumn, $fields)) {
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_WARNING, _t('STORE_ERROR_UNKNOWN_COLUMN'));
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
		$model = $GLOBALS['app']->LoadGadget('Store', 'Model');
		//$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		if (is_null($OwnerID)) {
			$OwnerID = 0;
		}
		if (!is_null($pid)) {
			$properties = $model->GetAllProductsOfParent((int)$pid, $sortColumn, $sortDir);
		} else {
			if ($return == 'attribute') {
				$properties = $this->SearchAttributes($search, 'Y', null, $OwnerID, true);
			} else if ($return == 'sales') {
				$properties = $this->SearchSales('Y', $search, null, $OwnerID);
			} else {
				$properties = $model->GetProducts(null, $sortColumn, $sortDir, false, $OwnerID, 'Y', $return, $search);
			}
		}
		
		if (Jaws_Error::IsError($properties)) {
			return new Jaws_Error($properties->GetMessage(), _t('STORE_NAME'));
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
			if ($property['active'] == 'Y') {
				if ($return != 'attribute' && $return != 'sales') {

					if (trim($brandid) != '') {
						if ($brandid != $property['brandid']) {
							$add_property = false;
						}
					}
					if (trim($category) != '') {
						if (strtolower($category) != strtolower($property['category'])) {
							$add_property = false;
						}
					}		
					if (trim($search) != '' && strpos(strtolower($property['title']), strtolower(trim($search))) !== false) {
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
								if ($links == 'Y') {
									$exact[] = array($sortColumn => '<a href="'.$GLOBALS['app']->getSiteURL().'/index.php?gadget=Store&action=Category&id=all&keyword='.ucfirst(strtolower($newstring)).'">'.ucfirst(strtolower($newstring)).'</a>');
								} else {
									$exact[] = array($sortColumn => ucfirst(strtolower($newstring)));
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
					if (!in_array(strtolower((string)$property[$return]), $keys_found) || count($keys_found) <= 0) {
						if ($only_titles === true) {
							if ($return == 'attribute') {
								$results[] = array($sortColumn => $property['feature'].' - Attribute');
							} else if ($return == 'sales') {
								$results[] = array($sortColumn => $property['title'].' - Sale');
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
			$result[] = $ex;
		}
		foreach($results as $res){
			$result[] = $res;
		}
		
		if (count($result)) {
			// Sort result array
			$subkey = $sortColumn; 
			$temp_array = array();
			
			$temp_array[key($result)] = array_shift($result);

			foreach($result as $key => $val) {
				$offset = 0;
				$found = false;
				foreach($temp_array as $tmp_key => $tmp_val) {
					if(!$found and strtolower($val[$subkey]) > strtolower($tmp_val[$subkey])) {
						$temp_array = array_merge(
							(array)array_slice($temp_array,0,$offset),
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
     * Search for product parents that matches a status and/or a keyword
     * in the title or content
     *
     * @param   string  $status  Status of product parents we want to display
     * @param   string  $search  Keyword (title/description) of product parents we want to look for
     * @param   int     $offSet  Data limit
     * @param   int     $OwnerID  Owner's ID
     * @param   int     $pid  Product parent ID to search in
     * @access  public
     * @return  array   Array of matches
     */
    function SearchProductParents($status, $search, $offSet = null, $OwnerID = null, $pid = null)
    {
        $params = array();


        $sql = '
            SELECT [productparentid], [productparentparent], [productparentsort_order], [productparentcategory_name], 
				[productparentimage], [productparentdescription], [productparentactive], 
				[productparentownerid], [productparentcreated], [productparentupdated], 
				[productparentfeatured], [productparentfast_url], [productparentrss_url],
				[productparenturl],[productparenturl_target],[productparentimage_code],
				[productparentchecksum]
            FROM [[productparent]]
			WHERE ([productparentcategory_name] <> ""';

		if (trim($status) != '') {
			$sql .= ' AND [productparentactive] = {status}';
			$params['status'] = $status;
		}
		if (!is_null($OwnerID)) {
			$sql .= ' AND [productparentownerid] = {OwnerID}';
			$params['OwnerID'] = $OwnerID;
		}
		if (!is_null($pid)) {
			$sql .= ' AND [productparentparent] = {pid}';
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
                $sql .= " AND ([productparentcategory_name] LIKE {textLike_".$i."} OR [productparentfast_url] LIKE {textLike_".$i."} OR [productparentdescription] LIKE {textLike_".$i."} OR [productparentrss_url] LIKE {textLike_".$i."})";
                $params['textLike_'.$i] = '%'.$v.'%';
                $i++;
            }
        }


        if (is_numeric($offSet)) {
            $limit = 10;
            $result = $GLOBALS['db']->setLimit(10, $offSet);
            if (Jaws_Error::IsError($result)) {
                return new Jaws_Error(_t('STORE_ERROR_PRODUCTPARENTS_NOT_RETRIEVED'), _t('STORE_NAME'));
            }
        }

        $sql.= ' ORDER BY [productparentid] ASC';

        $types = array(
			'integer', 'integer', 'integer', 'text', 'text', 'text', 
			'text', 'integer', 'timestamp', 'timestamp', 
			'text', 'text', 'text', 'text', 'text', 'text', 'text'
		);
	    
        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('STORE_ERROR_PRODUCTPARENTS_NOT_RETRIEVED'), _t('STORE_NAME'));
        }
        //limit, sort, sortDirection, offset..
        return $result;
    }

    /**
     * Search for attributes that matches a status and/or a keyword
     * in the title or content
     *
     * @param   string  $status  Status of attributes we want to display
     * @param   string  $search  Keyword (title/description) of attributes we want to look for
     * @param   int     $offSet  Data limit
     * @param   int     $OwnerID  Owner's ID
     * @param   boolean     $only_titles  If true only return titles, otherwise return entire attribute DB info array
     * @param   boolean     $include_templates  If true also search templates, otherwise only attributes in the DB
     * @access  public
     * @return  array   Array of matches
     */
    function SearchAttributes($search, $status, $offSet = null, $OwnerID = null, $only_titles = false, $include_templates = true)
    {
        $result = array();
		if ($status != 'template') {
			$params = array();
			$sql = '
				SELECT';
			
			if ($only_titles === false) {
				$sql .= ' [id], [sort_order], [feature], [typeid], [description], [add_amount], 
					[add_percent], [newprice], [ownerid], 
					[active], [created], [updated], [checksum]';
				$types = array(
				'integer', 'integer', 'text', 'integer', 'text', 'decimal', 
				'integer', 'decimal', 'integer', 'text', 
				'timestamp', 'timestamp', 'text'
				);
			} else {
				$sql .= ' [feature]';
				$types = array(
					'text'
				);
			}
			$sql .= '
				FROM [[productattribute]]
				WHERE ([feature] <> ""';

			if (trim($status) != '') {
				$sql .= ' AND [typeid] = {status}';
				$params['status'] = (int)$status;
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
					return new Jaws_Error(_t('STORE_ERROR_PRODUCTATTRIBUTES_NOT_RETRIEVED'), _t('STORE_NAME'));
				}
			}

			if ($only_titles === false) {
				$sql.= ' ORDER BY [typeid] ASC, [sort_order] ASC';
			} else {
				$sql.= ' ORDER BY [feature] ASC';
			}

			
			$result = $GLOBALS['db']->queryAll($sql, $params, $types);
			if (Jaws_Error::IsError($result)) {
				return new Jaws_Error(_t('STORE_ERROR_PRODUCTATTTRIBUTES_NOT_RETRIEVED'), _t('STORE_NAME'));
			}
        }
		//limit, sort, sortDirection, offset..
		if ($status == 'template' || (empty($status) && $include_templates === true)) {
			$count_result = count($result);
			reset($result);
			if (is_null($offSet) || ($count_result < 10)) {
				$tpl_offSet = null;
				$tpl_limit = null;
				if (!is_null($offSet)) {
					$tpl_offSet = 0;
					$tpl_limit = (10-$count_result);
					if ($count_result == 0) {
						$pages = $this->SearchAttributes($search, $status, null, $OwnerID, false, false);
						$total = (Jaws_Error::IsError($pages) ? 0 : count($pages));
						$tpl_offSet = floor((($offSet-$total) / 10));
						$tpl_limit = null;
					}
				}
				$templates = $this->SearchTemplates('attribute_types', $search, $tpl_offSet, $tpl_limit);
				if (!Jaws_Error::IsError($templates)) {
					$tpls = array();
					foreach ($templates as $tpl) {
						if ($only_titles === false) {
							$tpls[] = array(
								'id' => 'templates/Store/attribute_types/'.$tpl['filename'],
								'typeid' => 'template',
								'feature' => "Template: ".$tpl['filename'],
								'description' => $tpl['description'],
								'add_amount' => '',
								'add_percent' => '',
								'newprice' => '',
								'ownerid' => '0',
								'active' => 'Y',
								'created' => $tpl['date'],
								'updated' => $tpl['date']
							);
						} else {
							$tpls[] = array(
								'feature' => "Template: ".$tpl['filename']
							);
						}
					}
					$result = array_merge($result, $tpls);
				}
			}
		}
		return $result;
    }
    
	/**
     * Search for attribute types that matches a status and/or a keyword
     * in the title or content
     *
     * @param   string  $status  Status of page(s) we want to display
     * @param   string  $search  Keyword (title/description) of pages we want to look for
     * @param   int     $offSet  Data limit
     * @param   int     $OwnerID  Owner's ID
     * @access  public
     * @return  array   Array of matches
     */
    function SearchAttributeTypes($status, $search, $offSet = null, $OwnerID = null)
    {
        $params = array();


        $sql = '
            SELECT [id], [title], [description], [itype], [required], 
				[ownerid], [active], [created], [updated], [checksum]
            FROM [[attribute_types]]
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
                return new Jaws_Error(_t('STORE_ERROR_ATTRIBUTETYPES_NOT_RETRIEVED'), _t('STORE_NAME'));
            }
        }

        $sql.= ' ORDER BY [id] ASC';

        $types = array(
			'integer', 'text', 'text', 'text', 'text', 
			'integer', 'text', 'timestamp', 'timestamp', 'text'
		);
	    
        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('STORE_ERROR_ATTRIBUTETYPES_NOT_RETRIEVED'), _t('STORE_NAME'));
        }
        //limit, sort, sortDirection, offset..

        return $result;
    }
    
	/**
     * Search for sales that matches a status and/or a keyword
     * in the title or content
     *
     * @param   string  $status  Status of page(s) we want to display
     * @param   string  $search  Keyword (title/description) of pages we want to look for
     * @param   int     $offSet  Data limit
     * @param   int     $OwnerID  Owner's ID
     * @access  public
     * @return  array   Array of matches
     */
    function SearchSales($status, $search, $offSet = null, $OwnerID = null)
    {
        $params = array();

        $sql = '
            SELECT [id], [title], [startdate], [enddate], [description], [min_qty], [discount_amount], 
				[discount_percent], [discount_newprice], [coupon_code], 
				[featured], [ownerid], [active], [created], [updated], [checksum]
            FROM [[sales]]
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
                return new Jaws_Error(_t('STORE_ERROR_SALES_NOT_RETRIEVED'), _t('STORE_NAME'));
            }
        }

        $sql.= ' ORDER BY [discount_amount] DESC, [discount_percent] DESC, [discount_newprice] DESC, [id] ASC';

        $types = array(
			'integer', 'text', 'timestamp', 'timestamp', 'text', 'integer', 'decimal',
			'integer', 'decimal', 'text', 'text', 'integer', 'text', 
			'timestamp', 'timestamp', 'text'
		);
	    
        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('STORE_ERROR_SALES_NOT_RETRIEVED'), _t('STORE_NAME'));
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
     * @param   int     $OwnerID  Owner's ID
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
				[url], [url_target], [image_code], [checksum]
            FROM [[productbrand]]
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
                return new Jaws_Error(_t('STORE_ERROR_BRANDS_NOT_RETRIEVED'), _t('STORE_NAME'));
            }
        }

        $sql.= ' ORDER BY [id] ASC';

        $types = array(
			'integer', 'text', 
			'text', 'text', 'integer', 'integer', 'integer', 'text', 
			'integer', 'timestamp', 'timestamp', 'text', 'text', 'text', 'text'
		);
	    
        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('STORE_ERROR_BRANDS_NOT_RETRIEVED'), _t('STORE_NAME'));
        }
        //limit, sort, sortDirection, offset..
        return $result;
    }

    /**
     * Search for templates that match a keyword
     * in the title or content
     *
     * @param   string  $type 	  Type of template to search for (product/attribute_types)
     * @param   string  $search  Keyword (title/description) of templates we want to look for
     * @param   int     $offSet  Data offset
     * @param   int     $limit  Data limit
     * @access  public
     * @return  array   Array of matches
     */
    function SearchTemplates($type = 'product', $search, $offSet = null, $limit = null)
    {
		$fileBrowserModel = $GLOBALS['app']->LoadGadget('FileBrowser', 'Model');
		$root_dir = JAWS_DATA . 'templates/Store/'.$type.'/';
		$templates = $fileBrowserModel->ReadDir('/', (is_numeric($offSet) ? (!is_null($limit) ? $limit : 10) : 0), (is_numeric($offSet) ? $offSet : 0), $root_dir);		
		if (Jaws_Error::IsError($templates)) {
			return array();
		}
		$res = array();
		foreach ($templates as $tpl) {
			// TODO: load template Info.php file
			if ($tpl['is_dir'] === true) {
				$res[] = $tpl;
			}
		}
		$result = array();
		if (trim($search) != '') {
            $searchdata = explode(' ', $search);
            foreach ($searchdata as $v) {
                $v = trim($v);
                foreach ($res as $r) {
					if (strpos(strtolower($r['filename']), strtolower(trim($v))) !== false) {
						$result[] = $r;
					}
				}
            }
        } else {
			foreach ($res as $r) {
				$result[] = $r;
			}
		}
        
		return $result;
    }

	/**
     * Hides an RSS item
     *
     * @param   int  $pid  product ID
     * @param   string  $title 	title of RSS item
     * @param   string  $published  date of RSS item
     * @param   string  $url  url of RSS item
     * @access  public
     * @return  boolean    Success/failure
     */
    function HideRss($pid, $title, $published, $url)
    {
		$sql = "
            INSERT INTO [[product_rss_hide]]
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
            return new Jaws_Error(_t('STORE_ERROR_RSS_NOT_HIDDEN'), _t('STORE_NAME'));
            //return new Jaws_Error($result->GetMessage(), _t('STORE_NAME'));
        }

        $GLOBALS['app']->Session->PushLastResponse(_t('STORE_RSS_HIDDEN'), RESPONSE_NOTICE);
        return true;
    }

    /**
     * Shows RSS item
     *
     * @param   int  $pid  product ID
     * @param   string  $title  title of RSS item
     * @param   string  $published  date of RSS item
     * @param   string  $url  url of RSS item
     * @access  public
     * @return  boolean    Success/failure
     */
    function ShowRss($pid, $title, $published, $url)
    {
        $sql = 'DELETE FROM [[product_rss_hide]] WHERE ([linkid] = {LinkID} AND [title] = {title} AND [published] = {published} AND [url] = {url})';
		$params               		= array();
		$params['title'] 			= $title;
		$params['published'] 		= $published;
		$params['url'] 				= $url;
		$params['LinkID']         	= (int)$pid;
        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_RSS_NOT_SHOWN'), RESPONSE_ERROR);
            return new Jaws_Error(_t('STORE_ERROR_RSS_NOT_SHOWN'), _t('STORE_NAME'));
        }

        $GLOBALS['app']->Session->PushLastResponse(_t('STORE_RSS_SHOWN'), RESPONSE_NOTICE);
        return true;
    }
		
    /**
     * Updates properties_parents DB table when product is updated.
     *
     * @param   int     $id             The ID of the product parent to update.
     * @access  public
     * @return  boolean Success/failure
     */
    function ActivateProductsCategories($id)
	{
		$model = $GLOBALS['app']->LoadGadget('Store', 'Model');
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        // Get Product info
		$page = $model->GetProduct($id);
        if (Jaws_Error::isError($page)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_PRODUCT_NOT_FOUND'), RESPONSE_ERROR);
            return new Jaws_Error(_t('STORE_ERROR_PRODUCT_NOT_FOUND'), _t('STORE_NAME'));
			
		} else {
			// Get category array, and update each category in it
			if (isset($page['id'])) {
				$GLOBALS['db']->dbc->loadModule('Function', null, true);
				$sql = "
					DELETE FROM [[products_parents]]
						WHERE ([prod_id] = {prod_id})";
				
				$params               		= array();
				$params['prod_id']        	= $page['id'];

				$result = $GLOBALS['db']->query($sql, $params);
				if (Jaws_Error::IsError($result)) {
					return new Jaws_Error(_t('STORE_ERROR_PRODUCTPARENT_NOT_UNLINKED'), _t('STORE_NAME'));
					//return new Jaws_Error($result->GetMessage(), _t('STORE_NAME'));
				}
				
				$categories = explode(',', $page['category']);
				
				// Insert updated records 
				foreach ($categories as $pid) {
					if ((int)$pid != 0) {
						$sql1 = "
							INSERT INTO [[products_parents]]
								([parent_id], [prod_id], [created], [updated])
							VALUES
								({parent_id}, {prod_id}, {now}, {now})";
						
						
						$params1               		= array();
						$params1['prod_id']        	= $page['id'];
						$params1['parent_id']       = (int)$pid;
						$params1['now']        		= $GLOBALS['db']->Date();

						$result1 = $GLOBALS['db']->query($sql1, $params1);
						if (Jaws_Error::IsError($result1)) {
							return new Jaws_Error(_t('STORE_ERROR_PRODUCTPARENT_NOT_LINKED'), _t('STORE_NAME'));
							//return new Jaws_Error($result->GetMessage(), _t('STORE_NAME'));
						}
						
						// Update Active status
						$sql2 = '
							UPDATE [[productparent]] SET
								[productparentactive] = {Active}, 
								[productparentupdated] = {now}
							WHERE ([productparentid] = {id} AND [productparentactive] = {Inactive})';

						$params2               		= array();
						$params2['Active']       	= 'Y'; 
						$params2['Inactive']       	= 'N'; 
						$params2['id']         		= (int)$pid;
						$params2['now']        		= $GLOBALS['db']->Date();

						$result2 = $GLOBALS['db']->query($sql2, $params2);
						if (Jaws_Error::IsError($result2)) {
							return new Jaws_Error(_t('STORE_ERROR_PRODUCTPARENT_NOT_UPDATED'), _t('STORE_NAME'));
						}
						
						$parent = $model->GetProductParent((int)$pid);
						if (Jaws_Error::isError($parent)) {
							$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_PRODUCTPARENT_NOT_FOUND'), RESPONSE_ERROR);
							return new Jaws_Error(_t('STORE_ERROR_PRODUCTPARENT_NOT_FOUND'), _t('STORE_NAME'));
						}
						// Add product parent to Menu
						$config_key = $GLOBALS['app']->Registry->Get('/config/key');		
						if ((int)$parent['productparentownerid'] == 0 && strpos($parent['productparentchecksum'], $config_key) !== false) {
							// update Menu Item for Page							
							$visible = ($parent['productparentactive'] == 'Y') ? 1 : 0;
							// if old title is different, update menu item
							$old_url = $GLOBALS['app']->Map->GetURLFor('Store', 'Category', array('id' => $parent['productparentfast_url']));
							$new_url = $GLOBALS['app']->Map->GetURLFor('Store', 'Category', array('id' => $parent['productparentfast_url']));

							$parentid = 0;
							$parentGid = 1;
							// get parent menus
							if ((int)$parent['productparentparent'] > 0) {
								$sql  = 'SELECT [id],[gid] FROM [[menus]] WHERE [id] = {parent}';
								$parentMenu = $GLOBALS['db']->queryRow($sql, array('parent' => (int)$parent['productparentparent']));
								if (Jaws_Error::IsError($parentMenu)) {
									$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
									return false;
								} else {
									$parentid = (isset($parentMenu['id']) ? (int)$parentMenu['id'] : $parentid);
									$parentGid = (isset($parentMenu['gid']) ? (int)$parentMenu['gid'] : $parentGid);
								}
							}
							
							$url = $GLOBALS['db']->dbc->function->lower('[url]');
							$sql  = "SELECT [id], [rank] FROM [[menus]] WHERE $url LIKE {url}";
							$oid = $GLOBALS['db']->queryRow($sql, array('url' => '%'.strtolower($old_url)));
							if (Jaws_Error::IsError($oid)) {
								//$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
								$GLOBALS['app']->Session->PushLastResponse($oid->GetMessage(), RESPONSE_ERROR);
								return false;
							} else if (!empty($oid['id']) && isset($oid['id'])) {
								$menuAdmin = $GLOBALS['app']->LoadGadget('Menu', 'AdminModel');
								if (!$menuAdmin->UpdateMenu($oid['id'], $parentid, $parentGid, 'Store', $xss->parse(strip_tags($GLOBALS['app']->UTF8->str_replace(',', '', $parent['productparentcategory_name']))), $new_url, 0, (int)$oid['rank'], $visible)) {
									//$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
									$GLOBALS['app']->Session->PushLastResponse($menuAdmin->GetMessage(), RESPONSE_ERROR);
									return false;
								}
/*
							} else {
								// add Menu Item for Page								
								$visible = ($parent['productparentactive'] == 'Y') ? 1 : 0;
								$url1 = $GLOBALS['app']->Map->GetURLFor('Store', 'Category', array('id' => $parent['productparentfast_url']));
																				
								$url = $GLOBALS['db']->dbc->function->lower('[url]');
								$sql  = "SELECT [id] FROM [[menus]] WHERE $url LIKE {url}";
								$oid = $GLOBALS['db']->queryRow($sql, array('url' => '%'.strtolower($url1)));
								if (Jaws_Error::IsError($oid)) {
									$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
									return false;
								} else {
									if (empty($oid['id'])) {
										// Get highest rank of current menu items
										$sql = "SELECT [rank] FROM [[menus]] WHERE ([gid] = {gid}) ORDER BY [rank] DESC LIMIT 1";
										$rank = $GLOBALS['db']->queryOne($sql, array('gid' => $parentGid));
										if (Jaws_Error::IsError($rank)) {
											$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
											return false;
										}
										$menuAdmin = $GLOBALS['app']->LoadGadget('Menu', 'AdminModel');
										if (!$menuAdmin->InsertMenu($parentid, $parentGid, 'Store', $xss->parse(strip_tags($GLOBALS['app']->UTF8->str_replace(',', '', $parent['productparentcategory_name']))), $url, 0, (int)$rank+1, $visible, true)) {
											$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
											return false;
										}
									} else {
										$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
										return false;
									}
								}
*/
							}
						}
						$GLOBALS['app']->Session->PushLastResponse(_t('STORE_PRODUCTPARENT_UPDATED'), RESPONSE_NOTICE);
					}
				}
			}
		}
        return true;
    }

    /**
     * Updates properties_parents DB table when product is updated.
     *
     * @param   int     $id             The ID of the product parent to update.
     * @access  public
     * @return  boolean Success/failure
     */
    function UpdateProductsCategories($id)
	{
		$model = $GLOBALS['app']->LoadGadget('Store', 'Model');
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		// Get Product info
		$page = $model->GetProduct($id);
        if (Jaws_Error::isError($page)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_PRODUCT_NOT_FOUND'), RESPONSE_ERROR);
            return new Jaws_Error(_t('STORE_ERROR_PRODUCT_NOT_FOUND'), _t('STORE_NAME'));
			
		} else {
			$sql = "
				DELETE FROM [[products_parents]]
					WHERE ([prod_id] = {prod_id})";
			
			$params               		= array();
			$params['prod_id']        	= $id;

			$result = $GLOBALS['db']->query($sql, $params);
			if (Jaws_Error::IsError($result)) {
				return new Jaws_Error(_t('STORE_ERROR_PRODUCTPARENT_NOT_UNLINKED'), _t('STORE_NAME'));
				//return new Jaws_Error($result->GetMessage(), _t('STORE_NAME'));
			}
			
			$categories = explode(',', $page['category']);
			foreach ($categories as $pid) {
				if ((int)$pid != 0) {
					$properties = $model->GetAllProductsOfParent((int)$pid);
					if (Jaws_Error::isError($properties)) {
						$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_PRODUCTS_NOT_RETRIEVED'), RESPONSE_ERROR);
						return new Jaws_Error(_t('STORE_ERROR_PRODUCTS_NOT_RETRIEVED'), _t('STORE_NAME'));
					}
					$hasChildren = false;
					foreach ($properties as $property) {
						if (isset($property['id']) && !empty($property['id'])) {
							$hasChildren = true;
							break;
						}
					}
					
					if ($hasChildren === false) {
						$parent = $model->GetProductParent((int)$pid);
						if (Jaws_Error::IsError($parent)) {
							$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_PRODUCT_NOT_DELETED'), RESPONSE_ERROR);
							return new Jaws_Error(_t('STORE_ERROR_PRODUCT_NOT_DELETED'), _t('STORE_NAME'));
						}
						$sql = '
							UPDATE [[productparent]] SET
								[productparentactive] = {Active}, 
								[productparentupdated] = {now}
							WHERE [productparentid] = {id}';

						
						$params               		= array();
						$params['id']         		= (int)$pid;
						$params['Active']       	= 'N'; 
						$params['now']        		= $GLOBALS['db']->Date();

						$result = $GLOBALS['db']->query($sql, $params);
						if (Jaws_Error::IsError($result)) {
							$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_PRODUCTPARENT_NOT_UPDATED'), RESPONSE_ERROR);
							return new Jaws_Error(_t('STORE_ERROR_PRODUCTPARENT_NOT_UPDATED'), _t('STORE_NAME'));
						}
						
						if ((int)$parent['productparentownerid'] == 0) {
							// delete menu item for page
							$url = $GLOBALS['app']->Map->GetURLFor('Store', 'Category', array('id' => $parent['productparentfast_url']));

							$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
							$res = $GLOBALS['app']->Shouter->Shout('onDeleteMenuItem', $url);
							if (Jaws_Error::IsError($res) || !$res) {
								$GLOBALS['app']->Session->PushLastResponse((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_PRODUCTPARENT_DELETE_FROM_MENU')), RESPONSE_ERROR);
								return new Jaws_Error((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_PRODUCTPARENT_DELETE_FROM_MENU')), _t('STORE_NAME'));
							}
						}
					}
				}
			}
			
			// Get category array, and update each category in it
			if (isset($page['id'])) {
				$categories = explode(',', $page['category']);
				$GLOBALS['db']->dbc->loadModule('Function', null, true);
				
				// Insert updated records 
				foreach ($categories as $pid) {
					if ((int)$pid != 0) {
						$sql1 = "
							INSERT INTO [[products_parents]]
								([parent_id], [prod_id], [created], [updated])
							VALUES
								({parent_id}, {prod_id}, {now}, {now})";
						
						
						$params1               		= array();
						$params1['prod_id']        	= $page['id'];
						$params1['parent_id']       = (int)$pid;
						$params1['now']        		= $GLOBALS['db']->Date();

						$result1 = $GLOBALS['db']->query($sql1, $params1);
						if (Jaws_Error::IsError($result1)) {
							return new Jaws_Error(_t('STORE_ERROR_PRODUCTPARENT_NOT_LINKED'), _t('STORE_NAME'));
							//return new Jaws_Error($result->GetMessage(), _t('STORE_NAME'));
						}
						
						// Update Active status
						$sql2 = '
							UPDATE [[productparent]] SET
								[productparentactive] = {Active}, 
								[productparentupdated] = {now}
							WHERE ([productparentid] = {id} AND [productparentactive] = {Inactive})';

						$params2               		= array();
						$params2['Active']       	= 'Y'; 
						$params2['Inactive']       	= 'N'; 
						$params2['id']         		= (int)$pid;
						$params2['now']        		= $GLOBALS['db']->Date();

						$result2 = $GLOBALS['db']->query($sql2, $params2);
						if (Jaws_Error::IsError($result2)) {
							return new Jaws_Error(_t('STORE_ERROR_PRODUCTPARENT_NOT_UPDATED'), _t('STORE_NAME'));
						}
						
						$parent = $model->GetProductParent((int)$pid);
						if (Jaws_Error::isError($parent)) {
							$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_PRODUCTPARENT_NOT_FOUND'), RESPONSE_ERROR);
							return new Jaws_Error(_t('STORE_ERROR_PRODUCTPARENT_NOT_FOUND'), _t('STORE_NAME'));
						}
						// Add product parent to Menu
						$config_key = $GLOBALS['app']->Registry->Get('/config/key');		
						if ((int)$parent['productparentownerid'] == 0 && strpos($parent['productparentchecksum'], $config_key) !== false) {
							// update Menu Item for Page							
							$visible = ($parent['productparentactive'] == 'Y') ? 1 : 0;
							// if old title is different, update menu item
							$old_url = $GLOBALS['app']->Map->GetURLFor('Store', 'Category', array('id' => $parent['productparentfast_url']));
							$new_url = $GLOBALS['app']->Map->GetURLFor('Store', 'Category', array('id' => $parent['productparentfast_url']));

							$parentid = 0;
							$parentGid = 1;

							// get parent menus
							if ((int)$parent['productparentparent'] > 0) {
								$sql  = 'SELECT [id],[gid] FROM [[menus]] WHERE [id] = {parent}';
								$parentMenu = $GLOBALS['db']->queryRow($sql, array('parent' => (int)$parent['productparentparent']));
								if (Jaws_Error::IsError($parentMenu)) {
									$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
									return false;
								} else {
									$parentid = (isset($parentMenu['id']) ? (int)$parentMenu['id'] : $parentid);
									$parentGid = (isset($parentMenu['gid']) ? (int)$parentMenu['gid'] : $parentGid);
								}
							}
							
							$url = $GLOBALS['db']->dbc->function->lower('[url]');
							$sql  = "SELECT [id], [rank] FROM [[menus]] WHERE $url LIKE {url}";
							$oid = $GLOBALS['db']->queryRow($sql, array('url' => '%'.strtolower($old_url)));
							if (Jaws_Error::IsError($oid)) {
								//$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
								$GLOBALS['app']->Session->PushLastResponse($oid->GetMessage(), RESPONSE_ERROR);
								return false;
							} else if (!empty($oid['id']) && isset($oid['id'])) {
								$menuAdmin = $GLOBALS['app']->LoadGadget('Menu', 'AdminModel');
								if (!$menuAdmin->UpdateMenu($oid['id'], $parentid, $parentGid, 'Store', $xss->parse(strip_tags($GLOBALS['app']->UTF8->str_replace(',', '', $parent['productparentcategory_name']))), $new_url, 0, (int)$oid['rank'], $visible)) {
									//$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
									$GLOBALS['app']->Session->PushLastResponse($menuAdmin->GetMessage(), RESPONSE_ERROR);
									return false;
								}
/*
							} else {
								// add Menu Item for Page								
								$visible = ($parent['productparentactive'] == 'Y') ? 1 : 0;
								$url = $GLOBALS['db']->dbc->function->lower('[url]');
								$url1 = $GLOBALS['app']->Map->GetURLFor('Store', 'Category', array('id' => $parent['productparentfast_url']));
																				
								$sql  = "SELECT [id] FROM [[menus]] WHERE $url LIKE {url}";
								$oid = $GLOBALS['db']->queryRow($sql, array('url' => '%'.strtolower($url1)));
								if (Jaws_Error::IsError($oid)) {
									$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
									return false;
								} else {
									if (empty($oid['id'])) {
										// Get highest rank of current menu items
										$sql = "SELECT [rank] FROM [[menus]] WHERE ([gid] = {gid}) ORDER BY [rank] DESC LIMIT 1";
										$rank = $GLOBALS['db']->queryOne($sql, array('gid' => $parentGid));
										if (Jaws_Error::IsError($rank)) {
											$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
											return false;
										}
										$menuAdmin = $GLOBALS['app']->LoadGadget('Menu', 'AdminModel');
										if (!$menuAdmin->InsertMenu($parentid, $parentGid, 'Store', $xss->parse(strip_tags($GLOBALS['app']->UTF8->str_replace(',', '', $parent['productparentcategory_name']))), $url1, 0, ((int)$rank+1), $visible, true)) {
											$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
											return false;
										}
									} else {
										$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
										return false;
									}
								}
*/
							}
						}
						$GLOBALS['app']->Session->PushLastResponse(_t('STORE_PRODUCTPARENT_UPDATED'), RESPONSE_NOTICE);
					}
				}
			}
		}
        return true;
    }

	/**
     * Import products from RSS feed.
     *
     * @param   int  $category  ID of the property parent
     * @param   string  $fetch_url  URL we're on
     * @param   string  $rss_url  RSS URL
     * @param   int  $OwnerID  Owner's ID
     * @param   int  $num  Current property count
     * @param   string  $user_attended 	(Y/N) Is this user-attended?
     * @access  public
     * @return  string   Response
     */
    function InsertRSSStore($category, $fetch_url = '', $rss_url = '', $OwnerID = null, $num, $user_attended = 'N')
    {		
		/*
		ignore_user_abort(true); 
        set_time_limit(0);
		echo " ";
		ob_flush();
		flush();

		//$result = array();
		$multifeed = false;
		if (trim($fetch_url) != '') {
			//echo '<br />RSS URL: '.$fetch_url;
			require_once(JAWS_PATH . 'libraries/magpierss-0.72/rss_fetch.inc');
			$rss = fetch_rss($fetch_url);
			if ($rss) {
				$real_rss_url = (trim($rss_url) != '' ? $rss_url : $fetch_url);
				if ($this->_propCount == 1) {
					echo '<br />&nbsp;<br />'.'<b>Now importing from: '.urldecode($real_rss_url).'</b>';
				}
				ob_flush();
				flush();
				//echo '<pre>';
				//var_dump($rss);
				//echo '</pre>';
				//$date = $GLOBALS['app']->loadDate();
				//$is_googleBase = false;
				$this->_propTotal = count($rss->items);
				reset($rss->items);
				if ((isset($num) && !empty($num) || $num == 0) && $user_attended == 'Y') {
					if ($num <= $this->_propTotal) {
						sleep(1);
						echo " ";
						ob_flush();
						flush();
						$this->_propCount = ($num+1);
						$this->InsertRSSProduct($rss->items[$num], $category, $real_rss_url, $OwnerID);
						if ($user_attended == 'Y') {
							echo '<form name="product_rss_form" id="product_rss_form" action="index.php?gadget=Store&action=UpdateRSSStore" method="POST">'."\n";
							echo '<input type="hidden" name="category" value="'.$category.'">'."\n";
							echo '<input type="hidden" name="fetch_url" value="'.$fetch_url.'">'."\n";
							echo '<input type="hidden" name="rss_url" value="'.$rss_url.'">'."\n";
							echo '<input type="hidden" name="OwnerID" value="'.$OwnerID.'">'."\n";
							echo '<input type="hidden" name="num" value="'.($num+1).'">'."\n";
							echo '<input type="hidden" name="ua" value="'.$user_attended.'">'."\n";
							echo '</form>'."\n";
							return true;
						}
					}
				} else {
					foreach ($rss->items as $item) {
						//if ($this->_propCount < 100) {
							sleep(1);
							echo " ";
							ob_flush();
							flush();
							$this->InsertRSSProduct($item, $category, $real_rss_url, $OwnerID);
						//} else {
						//	break;
						//}

						//if (isset($item['g']) && is_array($item['g'])) {
						//	if ($is_googleBase === false) {
						//		$is_googleBase = true;
						//		//break;
						//	}
						//}
						
						$this->_propCount++;
						
						// TODO: if user attended, submit form 
						// with rss->items array to HTML->UpdateRSSStore
					}
				}
				//$result = $rss->items;

				//if (isset($rss->channel["link_next"]) && !empty($rss->channel["link_next"])) {
				//	$multifeed = true;
				//	//$output_html .= '<br />&nbsp;<br />Multi-part RSS feed: '.$rss->channel['link_next'];
				//	//$output_html .= '<br />Real RSS: '.$real_rss_url;
				//	//$result[] = array('next_category' => $category, 'next_fetch_url' => $rss->channel['link_next'], 'next_override_city' => $override_city, 'next_rss_url' => $real_rss_url, 'next_ownerid' => (is_null($OwnerID) ? 'null' : $OwnerID));
				//	echo '<br />&nbsp;<br />'.'<b>Multi-part RSS feed. Now importing from: '.urldecode($rss->channel['link_next']).'</b>';
				//	$this->InsertRSSStore($category, $rss->channel['link_next'], $override_city, $real_rss_url, $OwnerID);
				//	//$output_html .= "<div id=\"insert".md5($rss->channel["link_next"])."\"></div><script>new Ajax.Updater('insert".md5($rss->channel["link_next"])."', 'admin.php?gadget=Store&action=InsertRSSStore&category=".(int)$category."&fetch_url=".urlencode($rss->channel['link_next'])."&override_city=".urlencode($override_city)."&rss_url=".urlencode($real_rss_url)."&OwnerID=".$OwnerID."', { method: 'post' });</script>";
				//}
				
				//var_dump($rss);
				//var_dump($result);
			} else {
				$GLOBALS['app']->Session->PushLastResponse("There was a problem parsing the RSS feed for: ".$fetch_url.". Please make sure the RSS feed URL is entered correctly.", RESPONSE_ERROR);
				//return new Jaws_Error("There was a problem parsing the RSS feed for: ".$fetch_url.". Please make sure the RSS feed URL is entered correctly.", _t('STORE_NAME'));
				echo '<br />'."There was a problem parsing the RSS feed for: ".$fetch_url.". Please make sure the RSS feed URL is entered correctly.";
				//return new Jaws_Error("There was a problem parsing the RSS feed for: ".$fetch_url.". Please make sure the RSS feed URL is entered correctly.", _t('STORE_NAME'));
				//$output_html .= "<br />ERROR: There was a problem parsing the RSS feed for: ".$fetch_url.". Please make sure the RSS feed URL is entered correctly.";
			}
			//echo $rss_html.'</table>';
		} else {
			//return new Jaws_Error("An RSS feed URL was not given.", _t('STORE_NAME'));
			echo '<br />'."An RSS feed URL was not given.";
		}

		// Delete properties not found in RSS feed
		if ($multifeed === false) {
			$sql = '
				SELECT [id], [category], [title], [item2]
				FROM [[product]]
				WHERE ([title] <> "")';
			
			$params = array();
			$types = array(
				'integer', 'text', 'text'
			);
			$result = $GLOBALS['db']->queryAll($sql, $params, $types);
			if (Jaws_Error::IsError($result)) {
				//return new Jaws_Error(_t('STORE_ERROR_PRODUCTS_NOT_RETRIEVED'), _t('STORE_NAME'));
				echo '<br />'."Could not find the product to delete.";
			} else {
				foreach ($result as $res) {
					if (!in_array($res['item2'], $this->_newChecksums) && (int)$category == (int)$res['category']) {
						
						$delete = $this->DeleteProduct($res['id'], true);
						if (Jaws_Error::IsError($delete)) {
							$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_PRODUCT_NOT_DELETED'), RESPONSE_ERROR);
							//return new Jaws_Error(_t('STORE_ERROR_PRODUCT_NOT_DELETED'), _t('STORE_NAME'));
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
		
		//return $result;
		//echo "<script type=\"text/javascript\">location.href='" . BASE_SCRIPT . "';</script>";
		//echo "<noscript><h1>Feed Imported Successfully</h1><a href=\"" . BASE_SCRIPT . "\">Click Here to Continue</a> if your browser does not redirect automatically.</noscript>";
		echo "<h1>Feed Imported Successfully</h1>";
		*/
		
		return true;
    }
	
	/**
     * Inserts array of product info
     *
     * @param   array  $item  Array of product info
     * @param   int  $category  ID of product parent
     * @param   string  $rss_url  RSS URL
     * @param   int  $OwnerID  Owner's ID
     * @access  public
     * @return  string   Response
     */
    function InsertRSSProduct($item, $category = 1, $rss_url = '', $OwnerID = null)
    {
		/*
		ignore_user_abort(true); 
        set_time_limit(0);
		echo " ";
		ob_flush();
		flush();
		sleep(1);
		$model = $GLOBALS['app']->LoadGadget('Store', 'Model');

		$key = "ABQIAAAAbHKtlYQg6w5AJ9d2_shprxRSdpoJbNiUEES6uLAQrtqeLOB2WBROsVk6Deve8XT-33SEgoVpReKw5Q";
		include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
		include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'XMLParser.php';
		$is_googleBase = false;
		$total = 0;
		$rss_property_link = '';
		$rss_virtual_tour = '';
		if (isset($item['link_self']) && !empty($item['link_self'])) {
			$rss_property_self = (strpos($item['link_self'], "http://") > 6 ? str_replace('http://', '', $item['link_self']) : $item['link_self']);
		} 
		if (isset($item['link']) && !empty($item['link'])) {
			$rss_property_link = (strpos($item['link'], 'http://', 1) > 6 ? substr($item['link'], 0, strpos($item['link'], 'http://', 1)) : $item['link']);
			if (substr($rss_property_link, -1) == '"') {
				$rss_property_link = substr($rss_property_link, 0, strlen($rss_property_link)-1);
			}
			//echo '<br /><pre>LINK: '.$rss_property_link.'</pre>';
			// TODO: Support more Virtual Tour formats
			if (strpos($rss_property_link, 'tour.getmytour.com') !== false) {
				$rss_virtual_tour = $rss_property_link;
			}
		}
		
		// snoopy
		$snoopy = new Snoopy('Store');
		$snoopy->agent = "Jaws";
		
		// Parse a location, if we can't then we won't add the product
		if (isset($item['g']['longitude']) && !empty($item['g']['longitude']) && isset($item['g']['latitude']) && !empty($item['g']['latitude'])) {
			$rss_location = $item['g']['latitude'].','.$item['g']['longitude'];
		} else if (isset($item['g']['location']) && !empty($item['g']['location'])) {
			$rss_location = $item['g']['location'];
		} else if ($snoopy->fetch($rss_property_link)) {
			echo " ";
			ob_flush();
			flush();
			//echo $snoopy->results;
			if (strpos($snoopy->results, "<meta name=\"geo.position\" content=\"")) {
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
		}
		
		if (isset($rss_location)) {
			usleep(200000);
			// snoopy
			$snoopy = new Snoopy('Store');
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
				$rss_email = '';
				$rss_agent = '';
				$rss_broker = '';
				$rss_broker_phone = '';
				$rss_phone = '';
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
					//$is_totalResults = false;
					if ($xml_result[0][0]['CODE'] == '200' && isset($xml_result[0][$i]['COUNTRYNAMECODE']) && isset($xml_result[0][$i]['ADMINISTRATIVEAREANAME']) && isset($xml_result[0][$i]['LOCALITYNAME']) && isset($xml_result[0][$i]['ADDRESS']) && isset($xml_result[0][$i]['COORDINATES']) && empty($rss_coordinates)) {
						//if (isset($xml_result[0][$i]['COUNTRYNAMECODE'])) {

							//echo '<br /><pre>xml_country: ';
							//var_dump($xml_result[0][$i]['COUNTRYNAMECODE']);

							//$params = array();
							//$params['is_country'] = 'Y';
							//$params['country_iso_code'] = $xml_result[0][$i]['COUNTRYNAMECODE'];
							$sql = "SELECT [id] FROM [[country]] WHERE ([is_country] = 'Y') AND ([country_iso_code] = {iso_code})";
							$paramsc = array();
							$paramsc['iso_code'] = $xml_result[0][$i]['COUNTRYNAMECODE'];
							$country = $GLOBALS['db']->queryOne($sql, $paramsc);
							if (!Jaws_Error::IsError($country)) {
								$rss_country_id = $country;
							}	

							//echo '<br />rss_country: ';
							//var_dump($rss_country_id);
							//echo '</pre>';

							unset($country);
						//}
						if ($rss_country_id != 999999) {
							$params = array();
							//$params['is_country'] = 'N';
							//$params['country_iso_code'] = $xml_result[0][$i]['ADMINISTRATIVEAREANAME'];
							$sql = "SELECT [id] FROM [[country]] WHERE ([is_country] = 'N') AND ([parent] = ".$rss_country_id.") AND ([country_iso_code] = {iso_code})";
							$paramsc = array();
							$paramsc['iso_code'] = $xml_result[0][$i]['ADMINISTRATIVEAREANAME'];
							$region = $GLOBALS['db']->queryOne($sql, $paramsc);
							if (!Jaws_Error::IsError($region)) {
								$rss_region = $region;
							}	
							unset($region);
						}

						//if (isset($xml_result[0][$i]['LOCALITYNAME']) && $override_city == '') {
						if ($override_city == '') {
							$rss_city = $xml_result[0][$i]['LOCALITYNAME'];
						}
						//if (isset($xml_result[0][$i]['ADDRESS'])) {
							$rss_address = $xml_result[0][$i]['ADDRESS'];
						//}
						if (isset($xml_result[0][$i]['POSTALCODENUMBER'])) {
							$rss_postal_code = $xml_result[0][$i]['POSTALCODENUMBER'];
						}
						//if (isset($xml_result[0][$i]['COORDINATES'])) {
							$rss_coordinates = $xml_result[0][$i]['COORDINATES'];
						//}
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

					// send highest sort_order
					$params = array();
					$params['category'] = $category;
					$sql = "SELECT COUNT([prop_id]) FROM [[properties_parents]] WHERE ([parent_id] = {category})";
					$max = $GLOBALS['db']->queryOne($sql, $params);
					if (Jaws_Error::IsError($max)) {
						$GLOBALS['app']->Session->PushLastResponse($max->getMessage(), RESPONSE_ERROR);
						echo '<br />'.$max->getMessage();
						//return new Jaws_Error($max->getMessage(), _t('STORE_NAME'));
					} else {
						if (!isset($max)) {
							$max = (is_numeric($max) ? $max+1 : 0);
						} else {
							$max = $max+1;
						}
					}	
					
					if (!isset($total)) {
						$sql = 'SELECT COUNT([id]) FROM [[product]]';
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

					$sql = 'SELECT [id] FROM [[product]] WHERE ([item2] = {checksum})';
					$found = $GLOBALS['db']->queryOne($sql, $params);
					
//					$output_html .= '<br /><pre>Importing: '. 0 .', '.$max.', '.$category.', '.$rss_mls_listing_id.', '.$rss_title.', '.$rss_image.', '.
//						''.', '.$rss_description.', '.$rss_address.', '.$rss_city.', '.$rss_region.', '.$rss_postal_code.', '.$rss_country_id.', '.
//						''.', '.''.', '.''.', '.$rss_price.', '. 0 .', '. 0 .', '. 0 .', '.$rss_status.', '.$rss_lot_size.', '.
//						$rss_square_feet.', '.$rss_bedrooms.', '.$rss_bathrooms.', '.''.', '.$rss_virtual_tour.', '.''.', '.''.', '.''.', '.
//						''.', '.''.', '.''.', '.''.', '.''.', '.''.', '.$prop_checksum.', '.''.', '.''.', '.''.', '.'N'.', '.'Y'.', '.'N'.', '.$OwnerID.', '.
//						'Y'.', '. $total .', ' . 0 . ', '.', '.$rss_property_author_link.', '.$rss_property_author.', '.$rss_property_author_type.', '.$rss_property_alt_link.', '.
//						$rss_property_alt_title.', '.$rss_property_alt_type.', '.''.', '.''.', '.''.', '.''.', '.$rss_year.', '.$rss_url.', '.
//						$rss_agent.', '.$rss_email.', '.$rss_phone.', '.$rss_property_link.', '.''.', '.$rss_broker.', '.
//						''.', '.$rss_broker_phone.', '.$rss_property_link.', '.''.', '. true .'</pre>';

						//$output_html .= "<div id=\"product".$total."\"></div><script>new Ajax.Updater('product".$total."', 'admin.php?gadget=Store&action=UpdateRSSProduct&num=".$total."', { method: 'post' });</script>";
						
					if (is_numeric($found)) {
						
						
//						$output_html .= "<div id=\"product".$i."\"></div><script>new Ajax.Updater('product".$i."', 'admin.php?gadget=Store&action=UpdateRSSProduct&id=".$found."&LinkID=0&sort_order=".$max."&category=".$category."
//						&mls=".$rss_mls_listing_id."&title=".$rss_title."&image=".$rss_image."&sm_description=&description=".$rss_description."&address=".$rss_address."&city=".$rss_city."&region=".$rss_region."
//						&postal_code=".$rss_postal_code."&country_id=".$rss_country_id."&community=&phase=&lotno=&price=".$rss_price."&rentdy=0&rentwk=0&rentmo=0&status=".$rss_status."&acreage=".$rss_lot_size."
//						&sqft=".$rss_square_feet."bedroom, $bathroom, $amenity, $i360, $maxchildno, $maxadultno, $petstay, 
//$occupancy, $maxcleanno, $roomcount, $minstay, $options, $item1, $item2, $item3, 
//$item4, $item5, $premium, $ShowMap, $featured, 
//$Active, $propertyno, $internal_propertyno, $alink, $alinkTitle, $alinkType, $alink2, $alink2Title, 
//$alink2Type, $alink3, $alink3Title, $alink3Type, $calendar_link, $year, $rss_url, 
//$agent, $agent_email, $agent_phone=".$rss_phone."&agent_website=".$rss_property_link."&agent_photo=&broker=".$rss_broker."&broker_email=&broker_phone=".$rss_broker_phone."&broker_website=".$rss_property_link."&broker_logo=&auto=true', { method: 'post' });</script>";
						

						// Update the product
						
						
						//$result = $this->UpdateProduct($found, 0, $max, $category, $rss_mls_listing_id, $rss_title, $rss_image, 
						//	'', $rss_description, $rss_address, $rss_city, $rss_region, $rss_postal_code, $rss_country_id, 
						//	'', '', '', $rss_price, 0, 0, 0, $rss_status, $rss_lot_size, 
						//	$rss_square_feet, $rss_bedrooms, $rss_bathrooms, '', $rss_virtual_tour, '', '', '', 
						//	'', '', '', '', '', '', $prop_checksum, '', '', '', 'N', 'Y', 'N', 
						//	'Y', $total, 0, $rss_property_author_link, $rss_property_author, $rss_property_author_type, $rss_property_alt_link, 
						//	$rss_property_alt_title, $rss_property_alt_type, '', '', '', '', $rss_year, $rss_url, 
						//	$rss_agent, $rss_email, $rss_phone, $rss_property_link, '', $rss_broker, 
						//	'', $rss_broker_phone, $rss_property_link, '', $rss_coordinates, true);
						
						
						$page = $model->GetProduct($found);
						if (Jaws_Error::isError($page)) {
							$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_PRODUCT_NOT_FOUND'), RESPONSE_ERROR);
							//return new Jaws_Error(_t('STORE_ERROR_PRODUCT_NOT_FOUND'), _t('STORE_NAME'));
							echo '<br />'._t('STORE_ERROR_PRODUCT_NOT_FOUND');
						} else if (isset($page['id']) && !empty($page['id'])) {
							$sql = '
								UPDATE [[product]] SET
									[updated] = {now}
								WHERE [id] = {id}';

							
							$params               		= array();
							$params['id']         		= $found;
							$params['now']        		= $GLOBALS['db']->Date();

							$result = $GLOBALS['db']->query($sql, $params);
							if (Jaws_Error::IsError($result)) {
								//return new Jaws_Error(_t('STORE_ERROR_PRODUCT_NOT_UPDATED'), _t('STORE_NAME'));
								echo '<br />'._t('STORE_ERROR_PRODUCT_NOT_UPDATED');
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
						// Add the product
						
						$result = $this->AddProduct(0, $max, $category, $rss_mls_listing_id, $rss_title, $rss_image, 
							'', $rss_description, $rss_address, $rss_city, $rss_region, $rss_postal_code, $rss_country_id, 
							'', '', '', $rss_price, 0, 0, 0, $rss_status, $rss_lot_size, 
							$rss_square_feet, $rss_bedrooms, $rss_bathrooms, '', $rss_virtual_tour, '', '', '', 
							'', '', '', '', '', '', $prop_checksum, '', '', '', 'N', 'Y', 'N', $OwnerID, 
							'Y', $total, 0, $rss_property_author_link, $rss_property_author, $rss_property_author_type, $rss_property_alt_link, 
							$rss_property_alt_title, $rss_property_alt_type, '', '', '', '', $rss_year, $rss_url, 
							$rss_agent, $rss_email, $rss_phone, $rss_property_link, '', $rss_broker, 
							'', $rss_broker_phone, $rss_property_link, '', $rss_coordinates, true);
						

						if (Jaws_Error::IsError($result)) {
							$GLOBALS['app']->Session->PushLastResponse($result->getMessage(), RESPONSE_ERROR);
							return new Jaws_Error($result->getMessage(), _t('STORE_NAME'));
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
					$sql = 'SELECT [id] FROM [[product]] WHERE ([item2] = {checksum})';
					$found = $GLOBALS['db']->queryOne($sql, $params);
					if (Jaws_Error::IsError($found) || !is_numeric($found)) {
						$GLOBALS['app']->Session->PushLastResponse('Product Not Added', RESPONSE_ERROR);
						if (($this->_propCount-1) >= 1) {
							echo '<style>#prop_'.($this->_propCount-1).' {display: none;}</style>';
							ob_flush();
							flush();
						}
						echo '<div><br />Product <b>'.$this->_propCount.' of '.$this->_propTotal.'</b> Not Added</div>';
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
					echo '<div><br />Product <b>'.$this->_propCount.' of '.$this->_propTotal.'</b> could not be Geocoded</div>';
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
		*/
		return true;
	}
    
	/**
     * Import products.
     *
     * @category 	feature
     * @param   string  $file  File path we're importing from
     * @param   string  $type  File type, we're importing from
     * @param   integer  $num  Current product num
     * @param   string  $user_attended  (Y/N) Is user attended?
     * @access  public
     * @return  string 	Response
     */
    function InsertInventory($file, $type, $num, $user_attended = 'N', $category = null)
    {		
		ignore_user_abort(true); 
        set_time_limit(0);
		echo " ";
		ob_flush();
		flush();
		require_once JAWS_PATH . 'include/Jaws/Utils.php';
		$model = $GLOBALS['app']->LoadGadget('Store', 'Model');
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
	
		if ($type == 'RSS') {
			//$result = array();
			$multifeed = false;
			if (trim($fetch_url) != '') {
				//echo '<br />RSS URL: '.$fetch_url;
				require_once(JAWS_PATH . 'libraries/magpierss-0.72/rss_fetch.inc');
				$rss = fetch_rss($fetch_url);
				if ($rss) {
					$real_rss_url = (trim($rss_url) != '' ? $rss_url : $fetch_url);
					if ($this->_propCount == 1) {
						echo '<br />&nbsp;<br />'.'<b>Now importing from: '.urldecode($real_rss_url).'</b>';
						if (isset($GLOBALS['log'])) {
							$GLOBALS['log']->Log(JAWS_LOG_INFO, "Now importing from: ".urldecode($real_rss_url));
						}	
					}
					ob_flush();
					flush();
					//echo '<pre>';
					//var_dump($rss);
					//echo '</pre>';
					$this->_propTotal = count($rss->items);
					reset($rss->items);
					if ((isset($num) && !empty($num) || $num == 0) && $user_attended == 'Y') {
						if ($num <= $this->_propTotal) {
							sleep(1);
							echo " ";
							ob_flush();
							flush();
							$this->_propCount = ($num+1);
							$this->InsertRSSProduct($rss->items[$num], $category, $real_rss_url, $OwnerID);
							if ($user_attended == 'Y') {
								echo '<form name="product_rss_form" id="product_rss_form" action="index.php?gadget=Store&action=UpdateRSSStore" method="POST">'."\n";
								echo '<input type="hidden" name="category" value="'.$category.'">'."\n";
								echo '<input type="hidden" name="fetch_url" value="'.$fetch_url.'">'."\n";
								echo '<input type="hidden" name="rss_url" value="'.$rss_url.'">'."\n";
								echo '<input type="hidden" name="OwnerID" value="'.$OwnerID.'">'."\n";
								echo '<input type="hidden" name="num" value="'.($num+1).'">'."\n";
								echo '<input type="hidden" name="ua" value="'.$user_attended.'">'."\n";
								echo '</form>'."\n";
								return true;
							}
						}
					} else {
						foreach ($rss->items as $item) {
								sleep(1);
								echo " ";
								ob_flush();
								flush();
								$this->InsertRSSProduct($item, $category, $real_rss_url, $OwnerID);
							
							$this->_propCount++;
						}
					}
					
					//var_dump($rss);
					//var_dump($result);
				} else {
					$GLOBALS['app']->Session->PushLastResponse("There was a problem parsing the RSS feed for: ".$fetch_url.". Please make sure the RSS feed URL is entered correctly.", RESPONSE_ERROR);
					//return new Jaws_Error("There was a problem parsing the RSS feed for: ".$fetch_url.". Please make sure the RSS feed URL is entered correctly.", _t('STORE_NAME'));
					echo '<br />'."There was a problem parsing the RSS feed for: ".$fetch_url.". Please make sure the RSS feed URL is entered correctly.";
					if (isset($GLOBALS['log'])) {
						$GLOBALS['log']->Log(JAWS_LOG_ERROR, "There was a problem parsing the RSS feed for: ".$fetch_url.". Please make sure the RSS feed URL is entered correctly.");
					}	
				}
				//echo $rss_html.'</table>';
			} else {
				//return new Jaws_Error("An RSS feed URL was not given.", _t('STORE_NAME'));
				echo '<br />'."An RSS feed URL was not given.";
				if (isset($GLOBALS['log'])) {
					$GLOBALS['log']->Log(JAWS_LOG_ERROR, "An RSS feed URL was not given.");
				}	
			}

			// Delete properties not found in RSS feed
			if ($multifeed === false) {
				$sql = '
					SELECT [id], [category], [title], [product_code]
					FROM [[product]]
					WHERE ([title] <> "")';
				
				$params = array();
				$types = array(
					'integer', 'text', 'text'
				);
				$result = $GLOBALS['db']->queryAll($sql, $params, $types);
				if (Jaws_Error::IsError($result)) {
					//return new Jaws_Error(_t('STORE_ERROR_PRODUCTS_NOT_RETRIEVED'), _t('STORE_NAME'));
					echo '<br />'."Could not find the product to delete.";
					if (isset($GLOBALS['log'])) {
						$GLOBALS['log']->Log(JAWS_LOG_ERROR, "Could not find the product to delete.");
					}	
				} else {
					foreach ($result as $res) {
						if (!in_array($res['product_code'], $this->_newChecksums) && (int)$category == (int)$res['category']) {
							
							$delete = $this->DeleteProduct($res['id'], true);
							if (Jaws_Error::IsError($delete)) {
								$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_PRODUCT_NOT_DELETED'), RESPONSE_ERROR);
								//return new Jaws_Error(_t('STORE_ERROR_PRODUCT_NOT_DELETED'), _t('STORE_NAME'));
								echo '<br />COULD NOT DELETE: '.$res['title'].' ::: '.$res['product_code']; 
								if (isset($GLOBALS['log'])) {
									$GLOBALS['log']->Log(JAWS_LOG_ERROR, 'COULD NOT DELETE: '.$res['title'].' ::: '.$res['product_code']);
								}	
							} else {
								echo '<br />DELETED: '.$res['title'].' ::: '.$res['product_code']; 
								if (isset($GLOBALS['log'])) {
									$GLOBALS['log']->Log(JAWS_LOG_INFO, 'DELETED: '.$res['title'].' ::: '.$res['product_code']);
								}	
							}
						}
					}
				}
			}
		} else if ($type == 'TabDelimited') {
			$output = '';
			//$result = array();
			if (trim($file) != '' && file_exists(JAWS_DATA.'files/'.$file) && strpos(strtolower($file), 'users') === false) {
				$output .= '<br />File: '.$file;
				echo '<br />File: '.$file;
				if (isset($GLOBALS['log'])) {
					$GLOBALS['log']->Log(JAWS_LOG_INFO, 'File: '.$file);
				}	
				// snoopy
				include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
				$snoopy = new Snoopy('Store');
				$fetch_url = $GLOBALS['app']->getDataURL('', true) . 'files/'.$xss->filter($file);
				
				if($snoopy->fetch($fetch_url)) {
					$inventoryContent = Jaws_Utils::split2D($snoopy->results, "\t", '"');
					if ($this->_propCount == 1) {
						$output .= '<br />&nbsp;<br />'.'<b>Now importing from: '.urldecode($file).'</b>';
						echo '<br />&nbsp;<br />'.'<b>Now importing from: '.urldecode($file).'</b>';
						if (isset($GLOBALS['log'])) {
							$GLOBALS['log']->Log(JAWS_LOG_INFO, 'Now importing from: '.urldecode($file));
						}	
					}
					ob_flush();
					flush();
					//echo '<pre>';
					//var_dump(trim(strtolower($inventoryContent[0][0])));
					//var_dump($inventoryContent);
					//exit;
					//echo '</pre>';

					// Get column headers
					// Active	
					for ($i=0;$i<50;$i++) {
						if (trim(strtolower($inventoryContent[0][$i])) == 'active') {
							$active = $i;
							break;
						}
					}
					if (!isset($active)) {
						$GLOBALS['app']->Session->PushLastResponse("There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'active'.", RESPONSE_ERROR);
						//return new Jaws_Error("There was a problem parsing the RSS feed for: ".$fetch_url.". Please make sure the RSS feed URL is entered correctly.", _t('STORE_NAME'));
						$output .= '<br />'."There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'active'.";
						echo '<br />'."There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'active'.";
						if (isset($GLOBALS['log'])) {
							$GLOBALS['log']->Log(JAWS_LOG_ERROR, "There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'active'.");
						}	
						return false;
					}
					// Brand	
					for ($i=0;$i<50;$i++) {
						if (trim(strtolower($inventoryContent[0][$i])) == 'brand') {
							$brand = $i;
							break;
						}
					}
					/*
					if (!isset($brand)) {
						$GLOBALS['app']->Session->PushLastResponse("There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'brand'.", RESPONSE_ERROR);
						//return new Jaws_Error("There was a problem parsing the RSS feed for: ".$fetch_url.". Please make sure the RSS feed URL is entered correctly.", _t('STORE_NAME'));
						$output .= '<br />'."There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'brand'.";
						echo '<br />'."There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'brand'.";
						return false;
					}
					*/
					// Category	
					for ($i=0;$i<50;$i++) {
						if (trim(strtolower($inventoryContent[0][$i])) == 'category') {
							$product_category = $i;
							break;
						}
					}
					if (!isset($product_category)) {
						$GLOBALS['app']->Session->PushLastResponse("There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'category'.", RESPONSE_ERROR);
						//return new Jaws_Error("There was a problem parsing the RSS feed for: ".$fetch_url.". Please make sure the RSS feed URL is entered correctly.", _t('STORE_NAME'));
						$output .= '<br />'."There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'category'.";
						echo '<br />'."There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'category'.";
						if (isset($GLOBALS['log'])) {
							$GLOBALS['log']->Log(JAWS_LOG_ERROR, "There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'category'.");
						}	
						return false;
					}
					// product_code	
					for ($i=0;$i<50;$i++) {
						if (trim(strtolower($inventoryContent[0][$i])) == 'product_code') {
							$product_code = $i;
							break;
						}
					}
					if (!isset($product_code)) {
						$GLOBALS['app']->Session->PushLastResponse("There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'product_code'.", RESPONSE_ERROR);
						//return new Jaws_Error("There was a problem parsing the RSS feed for: ".$fetch_url.". Please make sure the RSS feed URL is entered correctly.", _t('STORE_NAME'));
						$output .= '<br />'."There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'product_code'.";
						echo '<br />'."There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'product_code'.";
						if (isset($GLOBALS['log'])) {
							$GLOBALS['log']->Log(JAWS_LOG_ERROR, "There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'product_code'.");
						}	
						return false;
					}
					// Item Name	
					for ($i=0;$i<50;$i++) {
						if (trim(strtolower($inventoryContent[0][$i])) == 'title') {
							$title = $i;
							break;
						}
					}
					if (!isset($title)) {
						$GLOBALS['app']->Session->PushLastResponse("There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'title'.", RESPONSE_ERROR);
						//return new Jaws_Error("There was a problem parsing the RSS feed for: ".$fetch_url.". Please make sure the RSS feed URL is entered correctly.", _t('STORE_NAME'));
						$output .= '<br />'."There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'title'.";
						echo '<br />'."There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'title'.";
						if (isset($GLOBALS['log'])) {
							$GLOBALS['log']->Log(JAWS_LOG_ERROR, "There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'title'.");
						}	
						return false;
					}
					// Item Description	
					for ($i=0;$i<50;$i++) {
						if (trim(strtolower($inventoryContent[0][$i])) == 'description') {
							$description = $i;
							break;
						}
					}
					if (!isset($description)) {
						$GLOBALS['app']->Session->PushLastResponse("There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'description'.", RESPONSE_ERROR);
						//return new Jaws_Error("There was a problem parsing the RSS feed for: ".$fetch_url.". Please make sure the RSS feed URL is entered correctly.", _t('STORE_NAME'));
						$output .= '<br />'."There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'description'.";
						echo '<br />'."There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'description'.";
						if (isset($GLOBALS['log'])) {
							$GLOBALS['log']->Log(JAWS_LOG_ERROR, "There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'description'.");
						}	
						return false;
					}
					// Attributes	
					$attribute = array();
					for ($i=0;$i<50;$i++) {
						if (substr(trim(strtolower($inventoryContent[0][$i])), 0, 10) == 'attribute-') {
							$key = explode('-',$inventoryContent[0][$i]);
							if (!empty($key[1])) {
								$attribute[$i] = $key[1];
							}
						}
					}
					/*
					if (!isset($attribute[0])) {
						$GLOBALS['app']->Session->PushLastResponse("There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'Attribute'.", RESPONSE_ERROR);
						//return new Jaws_Error("There was a problem parsing the RSS feed for: ".$fetch_url.". Please make sure the RSS feed URL is entered correctly.", _t('STORE_NAME'));
						$output .= '<br />'."There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'Attribute'.";
						echo '<br />'."There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'Attribute'.";
						if (isset($GLOBALS['log'])) {
							$GLOBALS['log']->Log(JAWS_LOG_ERROR, "There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'Attribute'.");
						}	
						return false;
					}
					*/
					// Regular Price	
					for ($i=0;$i<50;$i++) {
						if (trim(strtolower($inventoryContent[0][$i])) == 'price') {
							$price = $i;
							break;
						}
					}
					if (!isset($price)) {
						$GLOBALS['app']->Session->PushLastResponse("There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'price'.", RESPONSE_ERROR);
						//return new Jaws_Error("There was a problem parsing the RSS feed for: ".$fetch_url.". Please make sure the RSS feed URL is entered correctly.", _t('STORE_NAME'));
						$output .= '<br />'."There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'price'.";
						echo '<br />'."There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'price'.";
						if (isset($GLOBALS['log'])) {
							$GLOBALS['log']->Log(JAWS_LOG_ERROR, "There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'price'.");
						}	
						return false;
					}
					
					// Retail	
					for ($i=0;$i<50;$i++) {
						if (trim(strtolower($inventoryContent[0][$i])) == 'retail') {
							$retail = $i;
							break;
						}
					}
					if (!isset($retail)) {
						$GLOBALS['app']->Session->PushLastResponse("There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'retail'.", RESPONSE_ERROR);
						//return new Jaws_Error("There was a problem parsing the RSS feed for: ".$fetch_url.". Please make sure the RSS feed URL is entered correctly.", _t('STORE_NAME'));
						$output .= '<br />'."There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'retail'.";
						echo '<br />'."There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'retail'.";
						if (isset($GLOBALS['log'])) {
							$GLOBALS['log']->Log(JAWS_LOG_ERROR, "There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'retail'.");
						}	
						return false;
					}
					
					// Cost	
					for ($i=0;$i<50;$i++) {
						if (trim(strtolower($inventoryContent[0][$i])) == 'cost') {
							$cost = $i;
							break;
						}
					}
					if (!isset($cost)) {
						$GLOBALS['app']->Session->PushLastResponse("There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'cost'.", RESPONSE_ERROR);
						//return new Jaws_Error("There was a problem parsing the RSS feed for: ".$fetch_url.". Please make sure the RSS feed URL is entered correctly.", _t('STORE_NAME'));
						$output .= '<br />'."There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'cost'.";
						echo '<br />'."There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'cost'.";
						if (isset($GLOBALS['log'])) {
							$GLOBALS['log']->Log(JAWS_LOG_ERROR, "There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'cost'.");
						}	
						return false;
					}
					
					// Unit	
					for ($i=0;$i<50;$i++) {
						if (trim(strtolower($inventoryContent[0][$i])) == 'unit') {
							$unit = $i;
							break;
						}
					}
					if (!isset($unit)) {
						$GLOBALS['app']->Session->PushLastResponse("There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'unit'.", RESPONSE_ERROR);
						//return new Jaws_Error("There was a problem parsing the RSS feed for: ".$fetch_url.". Please make sure the RSS feed URL is entered correctly.", _t('STORE_NAME'));
						$output .= '<br />'."There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'unit'.";
						echo '<br />'."There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'unit'.";
						if (isset($GLOBALS['log'])) {
							$GLOBALS['log']->Log(JAWS_LOG_ERROR, "There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'unit'.");
						}	
						return false;
					}
					
					// Inventory Management	
					for ($i=0;$i<50;$i++) {
						if (trim(strtolower($inventoryContent[0][$i])) == 'inventory') {
							$inventory = $i;
							break;
						}
					}
					if (!isset($inventory)) {
						$GLOBALS['app']->Session->PushLastResponse("There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'inventory'.", RESPONSE_ERROR);
						//return new Jaws_Error("There was a problem parsing the RSS feed for: ".$fetch_url.". Please make sure the RSS feed URL is entered correctly.", _t('STORE_NAME'));
						$output .= '<br />'."There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'inventory'.";
						echo '<br />'."There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'inventory'.";
						if (isset($GLOBALS['log'])) {
							$GLOBALS['log']->Log(JAWS_LOG_ERROR, "There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'inventory'.");
						}	
						return false;
					}
					
					// Lowstock notifications
					for ($i=0;$i<50;$i++) {
						if (trim(strtolower($inventoryContent[0][$i])) == 'lowstock') {
							$lowstock = $i;
							break;
						}
					}
					if (!isset($lowstock)) {
						$GLOBALS['app']->Session->PushLastResponse("There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'lowstock'.", RESPONSE_ERROR);
						//return new Jaws_Error("There was a problem parsing the RSS feed for: ".$fetch_url.". Please make sure the RSS feed URL is entered correctly.", _t('STORE_NAME'));
						$output .= '<br />'."There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'lowstock'.";
						echo '<br />'."There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'lowstock'.";
						if (isset($GLOBALS['log'])) {
							$GLOBALS['log']->Log(JAWS_LOG_ERROR, "There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'lowstock'.");
						}	
						return false;
					}
					
					// Outstock Buy	
					for ($i=0;$i<50;$i++) {
						if (trim(strtolower($inventoryContent[0][$i])) == 'outstockbuy') {
							$outstockbuy = $i;
							break;
						}
					}
					if (!isset($outstockbuy)) {
						$GLOBALS['app']->Session->PushLastResponse("There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'outstockbuy'.", RESPONSE_ERROR);
						//return new Jaws_Error("There was a problem parsing the RSS feed for: ".$fetch_url.". Please make sure the RSS feed URL is entered correctly.", _t('STORE_NAME'));
						$output .= '<br />'."There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'outstockbuy'.";
						echo '<br />'."There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'outstockbuy'.";
						if (isset($GLOBALS['log'])) {
							$GLOBALS['log']->Log(JAWS_LOG_ERROR, "There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'outstockbuy'.");
						}	
						return false;
					}
					
					// premium
					for ($i=0;$i<50;$i++) {
						if (trim(strtolower($inventoryContent[0][$i])) == 'premium') {
							$premium = $i;
							break;
						}
					}
					if (!isset($premium)) {
						$GLOBALS['app']->Session->PushLastResponse("There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'premium'.", RESPONSE_ERROR);
						//return new Jaws_Error("There was a problem parsing the RSS feed for: ".$fetch_url.". Please make sure the RSS feed URL is entered correctly.", _t('STORE_NAME'));
						$output .= '<br />'."There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'premium'.";
						echo '<br />'."There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'premium'.";
						if (isset($GLOBALS['log'])) {
							$GLOBALS['log']->Log(JAWS_LOG_ERROR, "There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'premium'.");
						}	
						return false;
					}
					
					// Quantity	
					for ($i=0;$i<50;$i++) {
						if (trim(strtolower($inventoryContent[0][$i])) == 'instock') {
							$instock = $i;
							break;
						}
					}
					if (!isset($instock)) {
						$GLOBALS['app']->Session->PushLastResponse("There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'instock'.", RESPONSE_ERROR);
						//return new Jaws_Error("There was a problem parsing the RSS feed for: ".$fetch_url.". Please make sure the RSS feed URL is entered correctly.", _t('STORE_NAME'));
						$output .= '<br />'."There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'instock'.";
						echo '<br />'."There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'instock'.";
						if (isset($GLOBALS['log'])) {
							$GLOBALS['log']->Log(JAWS_LOG_ERROR, "There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'instock'.");
						}	
						return false;
					}
					// Weight	
					for ($i=0;$i<50;$i++) {
						if (trim(strtolower($inventoryContent[0][$i])) == 'weight') {
							$weight = $i;
							break;
						}
					}
					if (!isset($weight)) {
						$GLOBALS['app']->Session->PushLastResponse("There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'weight'.", RESPONSE_ERROR);
						//return new Jaws_Error("There was a problem parsing the RSS feed for: ".$fetch_url.". Please make sure the RSS feed URL is entered correctly.", _t('STORE_NAME'));
						$output .= '<br />'."There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'weight'.";
						echo '<br />'."There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'weight'.";
						if (isset($GLOBALS['log'])) {
							$GLOBALS['log']->Log(JAWS_LOG_ERROR, "There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'weight'.");
						}	
						return false;
					}
					
					// Image	
					for ($i=0;$i<50;$i++) {
						if (trim(strtolower($inventoryContent[0][$i])) == 'image') {
							$image = $i;
							break;
						}
					}
					if (!isset($image)) {
						$GLOBALS['app']->Session->PushLastResponse("There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'image'.", RESPONSE_ERROR);
						//return new Jaws_Error("There was a problem parsing the RSS feed for: ".$fetch_url.". Please make sure the RSS feed URL is entered correctly.", _t('STORE_NAME'));
						$output .= '<br />'."There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'image'.";
						echo '<br />'."There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'image'.";
						if (isset($GLOBALS['log'])) {
							$GLOBALS['log']->Log(JAWS_LOG_ERROR, "There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST contain the column 'image'.");
						}	
						return false;
					}
					
					unset($inventoryContent[0]);
					array_unshift($inventoryContent, array_shift ($inventoryContent)); 					
					$this->_propTotal = count($inventoryContent);
					reset($inventoryContent);
					if ((isset($num) && !empty($num) || $num == 1) && $user_attended == 'Y') {
						if (
							$num <= $this->_propTotal && 
							(strtolower($inventoryContent[$num][$active]) == 'y' || 
							strtolower($inventoryContent[$num][$active]) == 'yes') && 
							strpos($inventoryContent[$num][$description], "\t") !== false
						) {
							sleep(1);
							echo " ";
							ob_flush();
							flush();
							$this->_propCount = ($num+1);
							$details = '<br />Num: '.$num;
							$details .= '<br />ADD Product: '.$inventoryContent[$num][$title];
							$details .= '<br />Active: '.$inventoryContent[$num][$active];
							$details .= '<br />Product Desc: '.$inventoryContent[$num][$description];
							$details .= '<br />Product Price: '.$inventoryContent[$num][$price];
							$details .= '<br />Retail: '.$inventoryContent[$num][$retail];
							$details .= '<br />Cost: '.$inventoryContent[$num][$cost];
							$details .= '<br />Category Name: '.$inventoryContent[$num][$product_category];
							$details .= '<br />Brand Name: '.$inventoryContent[$num][$brand];
							$details .= '<br />Product Code: '.$inventoryContent[$num][$product_code];
							$details .= '<br />Weight: '.$inventoryContent[$num][$weight];
							$details .= '<br />Premium: '.$inventoryContent[$num][$premium];
							$details .= '<br />Unit: '.$inventoryContent[$num][$unit];
							$details .= '<br />Lowstock: '.$inventoryContent[$num][$lowstock];
							$details .= '<br />Qty: '.$inventoryContent[$num][$instock];
							$details .= '<br />outstockbuy: '.$inventoryContent[$num][$outstockbuy];
							$details .= '<br />Inventory: '.$inventoryContent[$num][$inventory];
							$details .= '<br />Image: '.$inventoryContent[$num][$image];
							$attr = array();
							foreach($attribute as $key => $val) {
								$attr[] = array($val => $inventoryContent[$num][$key]);
							}
							$details .= '<br />Attributes: '.var_export($attr, true);
							$active = (strtolower($inventoryContent[$num][$active]) == 'y' || strtolower($inventoryContent[$num][$active]) == 'yes' ? 'Y' : 'N');
							$premium = (strtolower($inventoryContent[$num][$premium]) == 'y' || strtolower($inventoryContent[$num][$premium]) == 'yes' ? 'Y' : 'N');
							$inventory = (strtolower($inventoryContent[$num][$inventory]) == 'y' || strtolower($inventoryContent[$num][$inventory]) == 'yes' ? 'Y' : 'N');
							$outstockbuy = (strtolower($inventoryContent[$num][$outstockbuy]) == 'y' || strtolower($inventoryContent[$num][$outstockbuy]) == 'yes' ? 'Y' : 'N');
							$this->InsertInventoryProduct(
								$xss->filter($active),
								$xss->filter(trim($inventoryContent[$num][$title])),
								$xss->filter(trim($inventoryContent[$num][$description])),
								$xss->filter(trim($inventoryContent[$num][$price])),
								$xss->filter(trim($inventoryContent[$num][$retail])),
								$xss->filter(trim($inventoryContent[$num][$cost])),
								$xss->filter(trim($inventoryContent[$num][$product_category])),
								$xss->filter(trim($inventoryContent[$num][$brand])),
								$xss->filter(trim($inventoryContent[$num][$product_code])),
								$xss->filter(trim($inventoryContent[$num][$weight])),
								$xss->filter($premium),
								$xss->filter(trim($inventoryContent[$num][$unit])),
								$xss->filter(trim($inventoryContent[$num][$lowstock])),
								$xss->filter(trim($inventoryContent[$num][$instock])),
								$xss->filter(trim($inventoryContent[$num][$inventory])),
								$xss->filter(trim($inventoryContent[$num][$outstockbuy])),
								$attr, 	// Attributes
								$xss->filter(trim($inventoryContent[$num][$image]))
							);
							$details .= '<form name="product_rss_form" id="product_rss_form" action="index.php?gadget=Store&action=UpdateRSSStore" method="POST">'."\n";
							$details .= '<input type="hidden" name="file" value="'.$file.'">'."\n";
							$details .= '<input type="hidden" name="type" value="'.$type.'">'."\n";
							$details .= '<input type="hidden" name="num" value="'.($num+1).'">'."\n";
							$details .= '<input type="hidden" name="ua" value="'.$user_attended.'">'."\n";
							$details .= '</form>'."\n";
							$output .= $details;
							echo $details;
							if (isset($GLOBALS['log'])) {
								$GLOBALS['log']->Log(JAWS_LOG_INFO, var_export($details, true));
							}	
							return true;
						}
					} else {
						unset($inventoryContent[0]);
						array_unshift($inventoryContent, array_shift ($inventoryContent)); 					
						foreach ($inventoryContent as $item) {
							if (strpos($item[$description], "\t") !== false) {
								$attr = array();
								foreach($attribute as $key => $val) {
									$attr[] = array($val => $item[$key]);
								}
								$details = '<br />Num: '.$this->_propCount;
								$details .= '<br />ADD Product: '.$item[$title];
								$details .= '<br />Active: '.$item[$active];
								$details .= '<br />Product Desc: '.$item[$description];
								$details .= '<br />Product Price: '.$item[$price];
								$details .= '<br />Retail: '.$item[$retail];
								$details .= '<br />Cost: '.$item[$cost];
								$details .= '<br />Category Name: '.$item[$product_category];
								$details .= '<br />Brand Name: '.$item[$brand];
								$details .= '<br />Product Code: '.$item[$product_code];
								$details .= '<br />Weight: '.$item[$weight];
								$details .= '<br />Premium: '.$item[$premium];
								$details .= '<br />Unit: '.$item[$unit];
								$details .= '<br />Lowstock: '.$item[$lowstock];
								$details .= '<br />Qty: '.$item[$instock];
								$details .= '<br />outstockbuy: '.$item[$outstockbuy];
								$details .= '<br />Inventory: '.$item[$inventory];
								$details .= '<br />Image: '.$item[$image];
								$details .= '<br />Attributes: '.var_export($attr, true);
								//if ($this->_propCount < 100) {
									sleep(1);
									echo " ";
									ob_flush();
									flush();
									$item_active = (strtolower($item[$active]) == 'y' || strtolower($item[$active]) == 'yes' ? 'Y' : 'N');
									$item_premium = (strtolower($item[$premium]) == 'y' || strtolower($item[$premium]) == 'yes' ? 'Y' : 'N');
									$item_inventory = (strtolower($item[$inventory]) == 'y' || strtolower($item[$inventory]) == 'yes' ? 'Y' : 'N');
									$item_outstockbuy = (strtolower($item[$outstockbuy]) == 'y' || strtolower($item[$outstockbuy]) == 'yes' ? 'Y' : 'N');
									$this->InsertInventoryProduct(
										$xss->filter($item_active),
										$xss->filter(trim($item[$title])),
										$xss->filter(trim($item[$description])),
										$xss->filter(trim($item[$price])),
										$xss->filter(trim($item[$retail])),
										$xss->filter(trim($item[$cost])),
										$xss->filter(trim($item[$product_category])),
										$xss->filter(trim($item[$brand])),
										$xss->filter(trim($item[$product_code])),
										$xss->filter(trim($item[$weight])),
										$xss->filter($item_premium),
										$xss->filter(trim($item[$unit])),
										$xss->filter(trim($item[$lowstock])),
										$xss->filter(trim($item[$instock])),
										$xss->filter($item_inventory),
										$xss->filter($item_outstockbuy),
										$attr, 	// Attributes
										$xss->filter(trim($item[$image]))
									);
								//} else {
								//	break;
								//}
								$this->_propCount++;
								if (isset($GLOBALS['log'])) {
									$GLOBALS['log']->Log(JAWS_LOG_INFO, var_export($details, true));
								}	
							}
						}
					}
					//var_dump($rss);
					//var_dump($result);
				} else {
					$GLOBALS['app']->Session->PushLastResponse("There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST be Tab-Delimited.", RESPONSE_ERROR);
					//return new Jaws_Error("There was a problem parsing the RSS feed for: ".$fetch_url.". Please make sure the RSS feed URL is entered correctly.", _t('STORE_NAME'));
					$output .= '<br />'."There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST be Tab-Delimited.";
					echo '<br />'."There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST be Tab-Delimited.";
					if (isset($GLOBALS['log'])) {
						$GLOBALS['log']->Log(JAWS_LOG_ERROR, "There was a problem parsing the Inventory List File: ".$fetch_url.". The file you are importing MUST be Tab-Delimited.");
					}	
					return false;
				}
				//echo $rss_html.'</table>';
			} else {
				//return new Jaws_Error("An RSS feed URL was not given.", _t('STORE_NAME'));
				$output .= '<br />'."An Inventory List File was not given.";
				echo '<br />'."An Inventory List File was not given.";
				if (isset($GLOBALS['log'])) {
					$GLOBALS['log']->Log(JAWS_LOG_ERROR, "An Inventory List File was not given.");
				}	
			}

			// Delete properties not found in RSS feed
				$sql = '
					SELECT [id], [category], [title], [internal_productno]
					FROM [[product]]
					WHERE ([title] <> "")';
				
				$params = array();
				$types = array(
					'integer', 'text', 'text', 'text'
				);
				$result = $GLOBALS['db']->queryAll($sql, $params, $types);
				if (Jaws_Error::IsError($result)) {
					//return new Jaws_Error(_t('STORE_ERROR_PRODUCTS_NOT_RETRIEVED'), _t('STORE_NAME'));
					$output .= '<br />'."Could not find the product to delete.";
					echo '<br />'."Could not find the product to delete.";
					if (isset($GLOBALS['log'])) {
						$GLOBALS['log']->Log(JAWS_LOG_ERROR, "Could not find the product to delete.");
					}	
				} else {
					foreach ($result as $res) {
						if (!in_array($res['internal_productno'], $this->_newChecksums) && (!is_null($category) && (int)$category == (int)$res['category'])) {
							
							$sql = '
								UPDATE [[product]] SET
									[active] = {Active},
									[updated] = {now}
								WHERE [id] = {id}';

							
							$params               		= array();
							$params['id']         		= (int)$found;
							$params['Active']        	= 'N';
							$params['now']        		= $GLOBALS['db']->Date();

							$result = $GLOBALS['db']->query($sql, $params);
							if (Jaws_Error::IsError($result)) {
							//$delete = $this->DeleteProduct($res['id'], true);
							//if (Jaws_Error::IsError($delete)) {
								$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_PRODUCT_NOT_DELETED'), RESPONSE_ERROR);
								//return new Jaws_Error(_t('STORE_ERROR_PRODUCT_NOT_DELETED'), _t('STORE_NAME'));
								$output .= '<br />COULD NOT DELETE: '.$res['title'].' ::: '.$res['internal_productno']; 
								echo '<br />COULD NOT DELETE: '.$res['title'].' ::: '.$res['internal_productno']; 
								if (isset($GLOBALS['log'])) {
									$GLOBALS['log']->Log(JAWS_LOG_ERROR, 'COULD NOT DELETE: '.$res['title'].' ::: '.$res['internal_productno']);
								}	
							} else {
								$output .= '<br />DELETED: '.$res['title'].' ::: '.$res['internal_productno']; 
								echo '<br />DELETED: '.$res['title'].' ::: '.$res['internal_productno']; 
								if (isset($GLOBALS['log'])) {
									$GLOBALS['log']->Log(JAWS_LOG_INFO, 'DELETED: '.$res['title'].' ::: '.$res['internal_productno']);
								}	
							}
						}
					}
				}
		} else {
			$output .= "<h1>Inventory File Type Not Supported</h1>";
			echo "<h1>Inventory File Type Not Supported</h1>";
			if (isset($GLOBALS['log'])) {
				$GLOBALS['log']->Log(JAWS_LOG_ERROR, "Inventory File Type: ".$type." Not Supported");
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
		
		//return $result;
		//echo "<script type=\"text/javascript\">location.href='" . BASE_SCRIPT . "';</script>";
		//echo "<noscript><h1>Feed Imported Successfully</h1><a href=\"" . BASE_SCRIPT . "\">Click Here to Continue</a> if your browser does not redirect automatically.</noscript>";
        
		// Delete inventory list
		if (file_exists(JAWS_DATA.'files/'.$file)) {
			if (!Jaws_Utils::Delete(JAWS_DATA.'files/'.$file, false)) {
				$output .= "<br />Couldn't Delete File During Clean-up";
				if (isset($GLOBALS['log'])) {
					$GLOBALS['log']->Log(JAWS_LOG_ERROR, "Couldn't Delete File: ". JAWS_DATA .'files/'.$file." During Clean-up");
				}	
			}
		}
		
		$output .= "<h1>Inventory Imported Successfully</h1>";
		echo "<h1>Inventory Imported Successfully</h1>";
		if (isset($GLOBALS['log'])) {
			$GLOBALS['log']->Log(JAWS_LOG_INFO, "SUCCESS: Inventory Imported Successfully!");
		}	
		
        if (Jaws_Utils::is_writable(JAWS_DATA . 'logs/')) {
            $result = file_put_contents(JAWS_DATA . 'logs/inventoryimport.log', $output);
            if ($result === false) {
				if (isset($GLOBALS['log'])) {
					$GLOBALS['log']->Log(JAWS_LOG_ERROR, "Couldn't create inventoryimport.log file");
				}	
                return new Jaws_Error("Couldn't create inventoryimport.log file", _t('ECOMMERCE_NAME'));
                //return false;
			} else if (isset($GLOBALS['log'])) {
				$GLOBALS['log']->Log(JAWS_LOG_INFO, "inventoryimport.log file created");
			}	
		}

		return true;
    }
	
	/**
     * Insert product info.
     *
     * @param   string  $active  (Y/N) Is product active?
     * @param   string  $name  Product name
     * @param   string  $description  Product description
     * @param   string  $price  Product price
     * @param   string  $retail  Product retail
     * @param   string  $cost  Product cost
     * @param   string  $category_name  Product category name
     * @param   string  $brand_name  Product brand name
     * @param   string  $product_code  Product code
     * @param   string  $weight  Product weight
     * @param   string  $premium  (Y/N) Is product featured?
     * @param   string  $unit  Product unit
     * @param   string  $lowstock  Product lowstock
     * @param   string  $qty  Product inventory quantity
     * @param   string  $inventory  (Y/N) Use inventory management for this product?
     * @param   string  $outstockbuy  (Y/N) Allow out of stock purchasing for this product?
     * @param   array  $attrs  Array of attributes for this product
     * @access  public
     * @return  string Response
     */
    function InsertInventoryProduct(
		$active, $name, $description = '', $price = '0', $retail = '0', $cost = '0', $category_name = '', 
		$brand_name = '', $product_code = '', $weight = '0', $premium = 'N', $unit = '/ Each', 
		$lowstock = '-1', $qty = '0', $inventory = 'Y', $outstockbuy = 'N', $attrs, $image = '')
    {
		ignore_user_abort(true); 
        set_time_limit(0);
		echo " ";
		ob_flush();
		flush();
		sleep(1);
		$model = $GLOBALS['app']->LoadGadget('Store', 'Model');
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');

		// Continue only if we have a product name
		if (isset($name) && !empty($name)) {
			$rss_title = $name;
			$rss_description = (isset($description) && !empty($description) ? $description : '');
			$rss_description = strip_tags($rss_description, '<img><br>');
			$rss_active = (isset($active) && !empty($active) ? $active : 'Y');
			$rss_premium = (isset($premium) && !empty($premium) ? $premium : 'N');
			$rss_inventory = (isset($inventory) && !empty($inventory) ? $inventory : 'Y');
			$rss_price = (isset($price) && !empty($price) ? $price : '0');
			$rss_retail = (isset($retail) && !empty($retail) ? $retail : '0');
			$rss_cost = (isset($cost) && !empty($cost) ? $cost : '0');
			$rss_weight = (isset($weight) && !empty($weight) ? $weight : '0');
			$rss_unit = (isset($unit) && !empty($unit) ? $unit : "/ Each");
			$rss_category_name = (isset($category_name) && !empty($category_name) ? $category_name : '');
			$rss_product_code = (isset($product_code) && !empty($product_code) ? $product_code : '');
			$rss_lowstock = (isset($lowstock) && !empty($lowstock) ? $lowstock : '-1');
			$rss_outstockbuy = (isset($outstockbuy) && !empty($outstockbuy) ? $outstockbuy : 'N');
			$rss_qty = (isset($qty) && !empty($qty) ? $qty : '0');
			$rss_brand_name = (isset($brand_name) && !empty($brand_name) ? $brand_name : '');
			$rss_attributes = (is_array($attrs) && !count($attrs) <= 0 ? $attrs : array());
			$rss_image = (isset($image) && !empty($image) ? $image : '');

			// Get Category ID
			$category = 1;
			if (!empty($rss_category_name)) {
				if (is_numeric($rss_category_name)) {
					$params = array();
					$params['category_id'] = (int)$rss_category_name;
					$sql = "SELECT [productparentcategory_name] FROM [[productparent]] WHERE ([productparentid] = {category_id})";
					$category_name = $GLOBALS['db']->queryOne($sql, $params);
					if (Jaws_Error::IsError($category_name)) {
						$GLOBALS['app']->Session->PushLastResponse($category_name->getMessage(), RESPONSE_ERROR);
						echo '<br />'.$category_name->getMessage();
						if (isset($GLOBALS['log'])) {
							$GLOBALS['log']->Log(JAWS_LOG_ERROR, $category_name->getMessage());
						}	
						//return new Jaws_Error($max->getMessage(), _t('STORE_NAME'));
					} else if (!empty($category_name)) {
						$category = (int)$rss_category_name;
						$rss_category_name = $category_name;
					}
				} else {
					$params = array();
					$params['category_name'] = $rss_category_name;
					$sql = "SELECT [productparentid] FROM [[productparent]] WHERE ([productparentcategory_name] = {category_name})";
					$category = $GLOBALS['db']->queryOne($sql, $params);
					if (Jaws_Error::IsError($category)) {
						$GLOBALS['app']->Session->PushLastResponse($category->getMessage(), RESPONSE_ERROR);
						echo '<br />'.$category->getMessage();
						if (isset($GLOBALS['log'])) {
							$GLOBALS['log']->Log(JAWS_LOG_ERROR, $category->getMessage());
						}	
						//return new Jaws_Error($max->getMessage(), _t('STORE_NAME'));
					} else {
						// Add Category if it doesn't exist
						if (!is_numeric($category)) {
							echo '<br />Adding Category';
							if (isset($GLOBALS['log'])) {
								$GLOBALS['log']->Log(JAWS_LOG_INFO, "Adding Category");
							}	
							// send highest sort_order
							$sql = "SELECT COUNT([productparentid]) FROM [[productparent]]";
							$parentmax = $GLOBALS['db']->queryOne($sql);
							if (Jaws_Error::IsError($parentmax)) {
								$GLOBALS['app']->Session->PushLastResponse($parentmax->getMessage(), RESPONSE_ERROR);
								echo '<br />'.$parentmax->getMessage();
								if (isset($GLOBALS['log'])) {
									$GLOBALS['log']->Log(JAWS_LOG_ERROR, $parentmax->getMessage());
								}	
								//return new Jaws_Error($parentmax->getMessage(), _t('STORE_NAME'));
							} else {
								$parentmax = (is_numeric($parentmax) ? $parentmax+1 : 0);
							}	
							$result = $this->AddProductParent($parentmax, 0, $rss_category_name);
							if (Jaws_Error::IsError($result)) {
								$GLOBALS['app']->Session->PushLastResponse($result->getMessage(), RESPONSE_ERROR);
								echo '<br />'.$result->getMessage();
								if (isset($GLOBALS['log'])) {
									$GLOBALS['log']->Log(JAWS_LOG_ERROR, $result->getMessage());
								}	
							} else if (is_numeric($result)) {
								$category = $result;
								echo '<br />Category '.$rss_category_name.' Added';
								if (isset($GLOBALS['log'])) {
									$GLOBALS['log']->Log(JAWS_LOG_INFO, 'Category '.$rss_category_name.' Added');
								}	
							}
							unset($parentmax);
						}
					}	
					$category = (int)$category;
				}
			}
			echo '<br />Category Name: '.$category_name;
			echo '<br />RSS Category: '.$rss_category_name;
			if (isset($GLOBALS['log'])) {
				$GLOBALS['log']->Log(JAWS_LOG_INFO, 'Category Name: '.$category_name);
				$GLOBALS['log']->Log(JAWS_LOG_INFO, 'RSS Category: '.$rss_category_name);
			}	
			
			// send highest sort_order
			$params = array();
			$params['category'] = $category;
			$sql = "SELECT COUNT([prod_id]) FROM [[products_parents]] WHERE ([parent_id] = {category})";
			$max = $GLOBALS['db']->queryOne($sql, $params);
			if (Jaws_Error::IsError($max)) {
				$GLOBALS['app']->Session->PushLastResponse($max->getMessage(), RESPONSE_ERROR);
				echo '<br />'.$max->getMessage();
				if (isset($GLOBALS['log'])) {
					$GLOBALS['log']->Log(JAWS_LOG_ERROR, $max->getMessage());
				}	
				//return new Jaws_Error($max->getMessage(), _t('STORE_NAME'));
			} else {
				$max = (is_numeric($max) ? (int)$max+1 : 0);
			}	
						
			// Get Brand ID
			$BrandID = 0;
			echo '<br />Brand Name: '.$brand_name;
			echo '<br />RSS Brand: '.$rss_brand_name;
			if (isset($GLOBALS['log'])) {
				$GLOBALS['log']->Log(JAWS_LOG_INFO, 'Brand Name: '.$brand_name);
				$GLOBALS['log']->Log(JAWS_LOG_INFO, 'RSS Brand: '.$rss_brand_name);
			}	
			if (!empty($rss_brand_name)) {
				$params = array();
				$params['brand_name'] = $rss_brand_name;
				$sql = "SELECT [id] FROM [[productbrand]] WHERE ([title] = {brand_name})";
				$BrandID = $GLOBALS['db']->queryOne($sql, $params);
				if (Jaws_Error::IsError($BrandID)) {
					$GLOBALS['app']->Session->PushLastResponse($BrandID->getMessage(), RESPONSE_ERROR);
					echo '<br />'.$BrandID->getMessage();
					if (isset($GLOBALS['log'])) {
						$GLOBALS['log']->Log(JAWS_LOG_ERROR, $BrandID->getMessage());
					}	
					//return new Jaws_Error($max->getMessage(), _t('STORE_NAME'));
				} else {
					// Add Brand if it doesn't exist
					if (!is_numeric($BrandID)) {
						echo '<br />Adding Brand';
						if (isset($GLOBALS['log'])) {
							$GLOBALS['log']->Log(JAWS_LOG_INFO, "Adding Brand");
						}	
						$result = $this->AddBrand($rss_brand_name, '', '', '', '', '', 'Y', null);
						if (Jaws_Error::IsError($result)) {
							$GLOBALS['app']->Session->PushLastResponse($result->getMessage(), RESPONSE_ERROR);
							echo '<br />'.$result->getMessage();
							if (isset($GLOBALS['log'])) {
								$GLOBALS['log']->Log(JAWS_LOG_ERROR, $result->getMessage());
							}	
						} else if (is_numeric($result)) {
							$BrandID = $result;
							echo '<br />Brand '.$result.' Added';
							if (isset($GLOBALS['log'])) {
								$GLOBALS['log']->Log(JAWS_LOG_INFO, 'Brand '.$result.' Added');
							}	
						}
					}
				}	
			}
			
			// Get Attribute Types (Size and Style)
			$attribute = '';
			foreach ($rss_attributes as $rss_attribute) {
				foreach($rss_attribute as $rss_attr_key => $rss_attr_val) {
					if (!empty($rss_attr_val)) {
						$params = array();
						$params['title'] = $rss_attr_key;
						$sql = "SELECT [id] FROM [[attribute_types]] WHERE ([title] = {title})";
						$attribute_size = $GLOBALS['db']->queryOne($sql, $params);
						if (Jaws_Error::IsError($attribute_size)) {
							$GLOBALS['app']->Session->PushLastResponse($attribute_size->getMessage(), RESPONSE_ERROR);
							echo '<br />'.$attribute_size->getMessage();
							if (isset($GLOBALS['log'])) {
								$GLOBALS['log']->Log(JAWS_LOG_ERROR, $attribute_size->getMessage());
							}	
							//return new Jaws_Error($max->getMessage(), _t('STORE_NAME'));
						} else if (is_numeric($attribute_size)) {
							$attribute_size = (int)$attribute_size;
							//echo '<br />AttributeType Size FOUND';
							// Get Attributes of Type (Size and Style)
							$style_not_found = true;
							$types = $model->GetAttributesOfType($attribute_size);
							if (!Jaws_Error::IsError($types)) {
								foreach($types as $type) {		            
									if ($type['feature'] == $rss_attr_val) {
										if (empty($attribute)) {
											$attribute .= $type['id'];
										} else {
											$attribute .= ','.$type['id'];
										}
										$style_not_found = false;
										echo '<br />Attribute: '.$rss_attr_val .' FOUND in Size (Attributes: '.$attribute.')';
										if (isset($GLOBALS['log'])) {
											$GLOBALS['log']->Log(JAWS_LOG_INFO, 'Attribute: '.$rss_attr_val .' FOUND in Size (Attributes: '.$attribute.')');
										}	
										break;
									}
								}
							}
							// Add Attribute to these Types if current attribute doesn't exist
							if ($style_not_found === true) {
								//echo '<br />Adding to Size: '.$rss_attr_val;
								// send highest sort_order
								$params = array();
								$params['typeID'] = (int)$attribute_size;
								$sql = "SELECT COUNT([id]) FROM [[productattribute]] WHERE ([typeid] = {typeID})";
								$attrmax = $GLOBALS['db']->queryOne($sql, $params);
								if (Jaws_Error::IsError($attrmax)) {
									$GLOBALS['app']->Session->PushLastResponse($attrmax->getMessage(), RESPONSE_ERROR);
									echo '<br />'.$attrmax->getMessage();
									if (isset($GLOBALS['log'])) {
										$GLOBALS['log']->Log(JAWS_LOG_ERROR, $attrmax->getMessage());
									}	
									//return new Jaws_Error($attrmax->getMessage(), _t('STORE_NAME'));
								} else {
									$attrmax = (is_numeric($attrmax) ? $attrmax+1 : 0);
								}	
								$result = $this->AddProductAttribute($attrmax, $rss_attr_val, (int)$attribute_size, '');
								if (Jaws_Error::IsError($result)) {
									$GLOBALS['app']->Session->PushLastResponse($result->getMessage(), RESPONSE_ERROR);
									echo '<br />'.$result->getMessage();
									if (isset($GLOBALS['log'])) {
										$GLOBALS['log']->Log(JAWS_LOG_ERROR, $result->getMessage());
									}	
								} else if (is_numeric($result)) {
									if (empty($attribute)) {
										$attribute .= $result;
									} else {
										$attribute .= ','.$result;
									}
									echo '<br />Attribute: '.$rss_attr_val .' Added to Size (Attributes: '.$attribute.')';
									if (isset($GLOBALS['log'])) {
										$GLOBALS['log']->Log(JAWS_LOG_INFO, 'Attribute: '.$rss_attr_val .' Added to Size (Attributes: '.$attribute.')');
									}	
								}
								unset($attrmax);
							}
						} else {
							//echo '<br />AttributeType Size NOT FOUND';
							// Add Attribute Type if it doesn't exist
							$result = $this->AddAttributeType($rss_attr_key, '', 'SelectBox');
							if (Jaws_Error::IsError($result)) {
								$GLOBALS['app']->Session->PushLastResponse($result->getMessage(), RESPONSE_ERROR);
								echo '<br />'.$result->getMessage();
								if (isset($GLOBALS['log'])) {
									$GLOBALS['log']->Log(JAWS_LOG_ERROR, $result->getMessage());
								}	
							} else if (is_numeric($result)) {
								$attribute_size = $result;
							}
							//echo '<br />Adding to Size: '.$rss_attr_val;
							$result2 = $this->AddProductAttribute(0, $rss_attr_val, (int)$attribute_size, '');
							if (Jaws_Error::IsError($result2)) {
								$GLOBALS['app']->Session->PushLastResponse($result2->getMessage(), RESPONSE_ERROR);
								echo '<br />'.$result2->getMessage();
								if (isset($GLOBALS['log'])) {
									$GLOBALS['log']->Log(JAWS_LOG_ERROR, $result2->getMessage());
								}	
							} else if (is_numeric($result2)) {
								if (empty($attribute)) {
									$attribute .= $result2;
								} else {
									$attribute .= ','.$result2;
								}
								echo '<br />Attribute: '.$rss_attr_val .' Added to '.$rss_attr_key.' (Attributes: '.$attribute.')';
								if (isset($GLOBALS['log'])) {
									$GLOBALS['log']->Log(JAWS_LOG_INFO, 'Attribute: '.$rss_attr_val .' Added to '.$rss_attr_key.' (Attributes: '.$attribute.')');
								}	
							}
						}
						unset($attribute_size);
					}
				}
			}				
			$prop_checksum = md5($rss_title.', '.$rss_category_name.', '.$rss_brand_name.', '.$rss_product_code);
			$this->_newChecksums[] = $prop_checksum;
			if (is_numeric($max) && !empty($rss_title) && !empty($prop_checksum)) {
				$params = array();
				$params['checksum'] = $prop_checksum;

				$sql = 'SELECT [id] FROM [[product]] WHERE ([internal_productno] = {checksum})';
				$found = $GLOBALS['db']->queryOne($sql, $params);
										
				if (is_numeric($found)) {
					$attributes = '';
					// TODO: Intelligent Updates (Change price, Inventory Management, etc)
					$page = $model->GetProduct((int)$found);
					if (Jaws_Error::isError($page)) {
						$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_PRODUCT_NOT_FOUND'), RESPONSE_ERROR);
						//return new Jaws_Error(_t('STORE_ERROR_PRODUCT_NOT_FOUND'), _t('STORE_NAME'));
						echo '<br />'._t('STORE_ERROR_PRODUCT_NOT_FOUND');
						if (isset($GLOBALS['log'])) {
							$GLOBALS['log']->Log(JAWS_LOG_ERROR, var_export($page, true) . " " . _t('STORE_ERROR_PRODUCT_NOT_FOUND'));
						}	
					} else if (isset($page['id']) && !empty($page['id'])) {
						$attributes = $page['attribute'];
						//echo '<br />OLD Attributes: '.$attributes;
						// Add Attributes to Product
						if (!empty($attribute)) {
							$attr_array = explode(',', $attribute);
							foreach ($attr_array as $attr) {
								if (!in_array($attr, explode(',', $attributes))) {
									if (empty($attributes)) {
										$attributes .= $attr;
									} else {
										$attributes .= ','.$attr;
									}
								}
							}
						}
						//echo '<br />NEW Attributes: '.$attributes;
						$sql = '
							UPDATE [[product]] SET
								[active] = {Active},
								[premium] = {premium},
								[instock] = {qty},
								[attribute] = {attribute},
								[updated] = {now}
							WHERE [id] = {id}';

						
						$params               		= array();
						$params['id']         		= (int)$found;
						$params['Active']        	= $rss_active;
						$params['premium']        	= $rss_premium;
						$params['attribute']        = $attributes;
						$params['qty']        		= ((int)$rss_qty)+$page['instock'];
						$params['now']        		= $GLOBALS['db']->Date();

						$result = $GLOBALS['db']->query($sql, $params);
						if (Jaws_Error::IsError($result)) {
							//return new Jaws_Error(_t('STORE_ERROR_PRODUCT_NOT_UPDATED'), _t('STORE_NAME'));
							echo '<br />'._t('STORE_ERROR_PRODUCT_NOT_UPDATED');
							if (isset($GLOBALS['log'])) {
								$GLOBALS['log']->Log(JAWS_LOG_ERROR, var_export($result, true) . " " . _t('STORE_ERROR_PRODUCT_NOT_UPDATED'));
							}	
						} else {
							if (($this->_propCount-1) >= 1) {
								echo '<style>#prod_'.($this->_propCount-1).' {display: none;}</style>';
								ob_flush();
								flush();
							}
							echo '<div id="prod_'.$this->_propCount.'"><br />Updating <b>'.$this->_propCount.' of '.$this->_propTotal.'</b> '.$rss_title.' ' . memory_get_usage() . '</div>';
							if (isset($GLOBALS['log'])) {
								$GLOBALS['log']->Log(JAWS_LOG_INFO, 'Updating '.$this->_propCount.' of '.$this->_propTotal.' '.$rss_title.' ' . memory_get_usage());
							}	
							ob_flush();
							flush();
						}
					}
					unset($page);
					unset($attributes);
					unset($attr_array);
						
				} else {
					// Add the product
					// TODO: Inventory Management
					// TODO: Weight
					// TODO: Price differences depending on Style and Size attributes
					$result = $this->AddProduct($BrandID, $max, $category, $rss_product_code, $rss_title, $rss_image, 
						'', $rss_description, $rss_weight, $rss_retail, $rss_price, 
						$rss_cost, '0', $rss_unit, 'N', $rss_inventory, (int)$rss_qty, 
						$rss_lowstock, 'This product is sold out. Check back soon.', $rss_outstockbuy, $attribute, $rss_premium, 'N', 
						0, $rss_active, $prop_checksum);

					if (Jaws_Error::IsError($result)) {
						$GLOBALS['app']->Session->PushLastResponse($result->getMessage(), RESPONSE_ERROR);
						//return new Jaws_Error($result->getMessage(), _t('STORE_NAME'));
						echo '<br />'.$result->getMessage();
						if (isset($GLOBALS['log'])) {
							$GLOBALS['log']->Log(JAWS_LOG_ERROR, $result->getMessage());
						}	
						//$output_html .= "<br />ERROR: ".$result->getMessage();
					} else {
						if (($this->_propCount-1) >= 1) {
							echo '<style>#prod_'.($this->_propCount-1).' {display: none;}</style>';
							ob_flush();
							flush();
						}
						echo '<div id="prod_'.$this->_propCount.'"><br />Importing <b>'.$this->_propCount.' of '.$this->_propTotal.'</b> '.$rss_title.' ' . memory_get_usage() . '</div>';
						if (isset($GLOBALS['log'])) {
							$GLOBALS['log']->Log(JAWS_LOG_INFO, 'Importing '.$this->_propCount.' of '.$this->_propTotal.' '.$rss_title.' ' . memory_get_usage());
						}	
						ob_flush();
						flush();
					}
				}
				unset($found);
			
				$params = array();
				$params['checksum'] = $prop_checksum;
				$sql = 'SELECT [id] FROM [[product]] WHERE ([internal_productno] = {checksum})';
				$found = $GLOBALS['db']->queryOne($sql, $params);
				if (Jaws_Error::IsError($found) || !is_numeric($found)) {
					$GLOBALS['app']->Session->PushLastResponse('Product Not Added', RESPONSE_ERROR);
					if (($this->_propCount-1) >= 1) {
						echo '<style>#prod_'.($this->_propCount-1).' {display: none;}</style>';
						ob_flush();
						flush();
					}
					echo '<div><br />Product <b>'.$this->_propCount.' of '.$this->_propTotal.'</b> Not Added</div>';
					if (isset($GLOBALS['log'])) {
						$GLOBALS['log']->Log(JAWS_LOG_ERROR, 'Product '.$this->_propCount.' of '.$this->_propTotal.' Not Added');
					}	
					ob_flush();
					flush();
				}
			} else {
				$GLOBALS['app']->Session->PushLastResponse('Product Not Added', RESPONSE_ERROR);
				if (($this->_propCount-1) >= 1) {
					echo '<style>#prod_'.($this->_propCount-1).' {display: none;}</style>';
					ob_flush();
					flush();
				}
				echo '<div><br />Product <b>'.$this->_propCount.' of '.$this->_propTotal.'</b> Not Added</div>';
				if (isset($GLOBALS['log'])) {
					$GLOBALS['log']->Log(JAWS_LOG_ERROR, 'Product '.$this->_propCount.' of '.$this->_propTotal.' Not Added');
				}	
				ob_flush();
				flush();
			}
			unset($attribute);
			unset($result);
			unset($prop_checksum);
			unset($max);
			unset($BrandID);
			unset($category);
			
			//ob_end_flush();
			//break;
		} else {
			$GLOBALS['app']->Session->PushLastResponse('Invalid product name', RESPONSE_ERROR);
			if (($this->_propCount-1) >= 1) {
				echo '<style>#prod_'.($this->_propCount-1).' {display: none;}</style>';
				ob_flush();
				flush();
			}
			echo '<div><br />Product <b>'.$this->_propCount.' of '.$this->_propTotal.'</b> could not be added</div>';
			if (isset($GLOBALS['log'])) {
				$GLOBALS['log']->Log(JAWS_LOG_ERROR, 'Product '.$this->_propCount.' of '.$this->_propTotal.' could not be added');
			}	
			ob_flush();
			flush();
		}
		
		unset($rss_title);
		unset($rss_description);
		unset($rss_price);
		unset($rss_category_name);
		unset($rss_brand_name);
		unset($rss_qty);
		unset($rss_attributes);
		unset($rss_active);
		unset($rss_premium);
		unset($rss_inventory);
		unset($rss_retail);
		unset($rss_cost);
		unset($rss_weight);
		unset($rss_unit);
		unset($rss_product_code);
		unset($rss_lowstock);
		unset($rss_image);
		unset($model);
	
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
     * Saves the value of a key
     *
     * @access  public
     * @return  array   Response
     */
    function SaveSettings($user_post_limit, $user_desc_char_limit, $user_mask_owner_email, $user_price_limit, $randomize_listings, $default_display)
    {
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$GLOBALS['app']->Registry->LoadFile('Store');
        $GLOBALS['app']->Registry->Set('/gadgets/Store/user_post_limit', (int)$user_post_limit);
        $GLOBALS['app']->Registry->Set('/gadgets/Store/user_price_limit', (int)$user_price_limit);
        $GLOBALS['app']->Registry->Set('/gadgets/Store/user_desc_char_limit', (int)$user_desc_char_limit);
        $GLOBALS['app']->Registry->Set('/gadgets/Store/user_mask_owner_email', $xss->parse($user_mask_owner_email));
        $GLOBALS['app']->Registry->Set('/gadgets/Store/randomize', $xss->parse($randomize_listings));
        $GLOBALS['app']->Registry->Set('/gadgets/Store/default_display', $xss->parse($default_display));
		$GLOBALS['app']->Registry->Commit('Store');
		if ($GLOBALS['app']->Registry->Get('/gadgets/Store/default_display') == $xss->parse($default_display) && $GLOBALS['app']->Registry->Get('/gadgets/Store/randomize') == $xss->parse($randomize_listings) && $GLOBALS['app']->Registry->Get('/gadgets/Store/user_post_limit') == $xss->parse($user_post_limit) && $GLOBALS['app']->Registry->Get('/gadgets/Store/user_desc_char_limit') == $xss->parse($user_desc_char_limit) && $GLOBALS['app']->Registry->Get('/gadgets/Store/user_mask_owner_email') == $xss->parse($user_mask_owner_email)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('STORE_KEY_SAVED'), RESPONSE_NOTICE);
        } else {
			$GLOBALS['app']->Session->PushLastResponse(_t('STORE_KEY_NOT_SAVED'), RESPONSE_ERROR);
			return false;
		}
		return true;
    }

    /**
     * Updates a User's Store stuff
     *
     * @access  public
     * @param   int  $uid  User ID
     * @return  array   Response
     */
    function UpdateUserStore($uid) 
    {
		$model = $GLOBALS['app']->LoadGadget('Store', 'Model');
		$pages = $model->GetStoreOfUserID((int)$uid);
		require_once JAWS_PATH . 'include/Jaws/User.php';
		$jUser = new Jaws_User;
		$info = $jUser->GetUserInfoById((int)$uid, true);
		if (!Jaws_Error::IsError($pages) && !Jaws_Error::IsError($info)) {
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
				UPDATE [[product]] SET
					[active] = {Active}
				WHERE ([ownerid] = {id}) AND ([active] = {was})';

			$result = $GLOBALS['db']->query($sql, $params);
			if (Jaws_Error::IsError($result)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_USER_PRODUCTS_NOT_UPDATED'), RESPONSE_ERROR);
				return false;
			}
			if (!$info['enabled']) {
				foreach($pages as $p) {		            
					if ($p['active'] == 'Y') {
						$sql1 = "
							DELETE FROM [[products_parents]]
								WHERE ([prod_id] = {prod_id})";
						
						$params1               		= array();
						$params1['prod_id']        	= $p['id'];

						$result1 = $GLOBALS['db']->query($sql1, $params1);
						if (Jaws_Error::IsError($result1)) {
							$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_PRODUCTPARENT_NOT_UNLINKED'), RESPONSE_ERROR);
							return false;
						}
					}
				}
			} else {
				foreach($pages as $p) {		            
					if ($p['active'] == 'N') {
						$sql1 = "
							DELETE FROM [[products_parents]]
								WHERE ([prod_id] = {prod_id})";
						
						$params1               		= array();
						$params1['prod_id']        	= $p['id'];

						$result1 = $GLOBALS['db']->query($sql1, $params1);
						if (Jaws_Error::IsError($result1)) {
							$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_PRODUCTPARENT_NOT_UNLINKED'), RESPONSE_ERROR);
							return false;
						}
						$result = $this->UpdateProductsCategories($p['id']);
						if (Jaws_Error::IsError($result)) {
							$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_PRODUCTPARENT_NOT_UNLINKED'), RESPONSE_ERROR);
							return false;
						}
					}
				}
			}
			$sql2 = '
				UPDATE [[productparent]] SET
					[productparentactive] = {Active}
				WHERE ([productparentownerid] = {id}) AND ([productparentactive] = {was})';

			$result2 = $GLOBALS['db']->query($sql2, $params);
			if (Jaws_Error::IsError($result2)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_USER_PRODUCTPARENTS_NOT_UPDATED'), RESPONSE_ERROR);
				return false;
			}
			$sql3 = '
				UPDATE [[attribute_types]] SET
					[active] = {Active}
				WHERE ([ownerid] = {id}) AND ([active] = {was})';

			$result3 = $GLOBALS['db']->query($sql3, $params);
			if (Jaws_Error::IsError($result3)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_USER_ATTRIBUTETYPES_NOT_UPDATED'), RESPONSE_ERROR);
				return false;
			}
			$sql4 = '
				UPDATE [[productattribute]] SET
					[active] = {Active}
				WHERE ([ownerid] = {id}) AND ([active] = {was})';

			$result4 = $GLOBALS['db']->query($sql4, $params);
			if (Jaws_Error::IsError($result4)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_USER_ATTRIBUTES_NOT_UPDATED'), RESPONSE_ERROR);
				return false;
			}
			$sql5 = '
				UPDATE [[productbrand]] SET
					[active] = {Active}
				WHERE ([ownerid] = {id}) AND ([active] = {was})';

			$result5 = $GLOBALS['db']->query($sql5, $params);
			if (Jaws_Error::IsError($result5)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_USER_BRANDS_NOT_UPDATED'), RESPONSE_ERROR);
				return false;
			}
			$sql6 = '
				UPDATE [[sales]] SET
					[active] = {Active}
				WHERE ([ownerid] = {id}) AND ([active] = {was})';

			$result6 = $GLOBALS['db']->query($sql6, $params);
			if (Jaws_Error::IsError($result6)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_USER_SALES_NOT_UPDATED'), RESPONSE_ERROR);
				return false;
			}
			$GLOBALS['app']->Session->PushLastResponse(_t('STORE_USER_PRODUCTS_UPDATED'), RESPONSE_NOTICE);
			return true;
		} else {
			$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_USER_PRODUCTS_NOT_UPDATED'), RESPONSE_ERROR);
			return false;
		}
    }	
		
    /**
     * Deletes a User's Store stuff
     *
     * @access  public
     * @param   int  $uid  User ID
     * @return  array   Response
     */
    function RemoveUserStore($uid) 
    {
		$model = $GLOBALS['app']->LoadGadget('Store', 'Model');
		$parents = $model->GetProductParentsByUserID((int)$uid);
		if (!Jaws_Error::IsError($parents)) {
			foreach ($parents as $page) {
				$result = $this->DeleteProductParent($page['id'], true);
				if (Jaws_Error::IsError($result)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_USER_PRODUCTPARENT_NOT_DELETED'), RESPONSE_ERROR);
					return false;
				}
			}
			$GLOBALS['app']->Session->PushLastResponse(_t('STORE_USER_PRODUCTPARENTS_DELETED'), RESPONSE_NOTICE);
		} else {
			$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_USER_PRODUCTPARENTS_NOT_DELETED'), RESPONSE_NOTICE);
			return false;
		}
		$products = $model->GetStoreOfUserID((int)$uid);
		if (!Jaws_Error::IsError($products)) {
			foreach ($products as $page) {
				$result = $this->DeleteProduct($page['id'], true);
				if (Jaws_Error::IsError($result)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_USER_PRODUCT_NOT_DELETED'), RESPONSE_ERROR);
					return false;
				}
			}
			$GLOBALS['app']->Session->PushLastResponse(_t('STORE_USER_PRODUCTS_DELETED'), RESPONSE_NOTICE);
		} else {
			$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_USER_PRODUCTS_NOT_DELETED'), RESPONSE_NOTICE);
			return false;
		}
		$attributes = $model->GetAttributeTypes(null, 'title', 'ASC', false, (int)$uid);
		if (!Jaws_Error::IsError($attributes)) {
			foreach ($attributes as $page) {
				$result = $this->DeleteAttributeType($page['id'], true);
				if (Jaws_Error::IsError($result)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_USER_ATTRIBUTETYPE_NOT_DELETED'), RESPONSE_ERROR);
					return false;
				}
			}
			$GLOBALS['app']->Session->PushLastResponse(_t('STORE_USER_ATTRIBUTETYPES_DELETED'), RESPONSE_NOTICE);
		} else {
			$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_USER_ATTRIBUTETYPES_NOT_DELETED'), RESPONSE_NOTICE);
			return false;
		}
		$sales = $model->GetSales(null, 'title', 'ASC', false, (int)$uid);
		if (!Jaws_Error::IsError($sales)) {
			foreach ($sales as $page) {
				$result = $this->DeleteSale($page['id'], true);
				if (Jaws_Error::IsError($result)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_USER_SALE_NOT_DELETED'), RESPONSE_ERROR);
					return false;
				}
			}
			$GLOBALS['app']->Session->PushLastResponse(_t('STORE_USER_SALES_DELETED'), RESPONSE_NOTICE);
		} else {
			$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_USER_SALES_NOT_DELETED'), RESPONSE_NOTICE);
			return false;
		}
		$brands = $model->GetBrands(null, 'title', 'ASC', false, (int)$uid);
		if (!Jaws_Error::IsError($brands)) {
			foreach ($brands as $page) {
				$result = $this->DeleteBrand($page['id'], true);
				if (Jaws_Error::IsError($result)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_USER_BRAND_NOT_DELETED'), RESPONSE_ERROR);
					return false;
				}
			}
			$GLOBALS['app']->Session->PushLastResponse(_t('STORE_USER_BRANDS_DELETED'), RESPONSE_NOTICE);
		} else {
			$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_USER_BRANDS_NOT_DELETED'), RESPONSE_NOTICE);
			return false;
		}
		return true;
    }	

    /**
     * Deletes Store Comments
     *
     * @access  public
     * @param   int  $id  Product ID
     * @return  array   Response
     */
    function RemoveProductComments($id) 
    {
		require_once JAWS_PATH . 'include/Jaws/Comment.php';

		// Delete standard comments
		$api = new Jaws_Comment('Store');
		$result = $api->DeleteCommentsByReference($id);
		if (Jaws_Error::IsError($result)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_COMMENT_ERROR_NOT_DELETED'), RESPONSE_ERROR);
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
		if ($gadget == 'Store') {
			// Update checksum
			$config_key = $GLOBALS['app']->Registry->Get('/config/key');		
			
			$model = $GLOBALS['app']->LoadGadget('Store', 'Model');
			$parents = $model->GetProductParents();
			if (Jaws_Error::IsError($parents)) {
				return false;
			}
			foreach ($parents as $parent) {
				if (empty($parent['productparentchecksum']) || is_null($parent['productparentchecksum']) || strpos($parent['productparentchecksum'], ':') === false) {
					$params               	= array();
					$params['id'] 			= $parent['productparentid'];
					$params['checksum'] 	= $parent['productparentid'].':'.$config_key;
					
					$sql = '
						UPDATE [[productparent]] SET
							[productparentchecksum] = {checksum}
						WHERE [productparentid] = {id}';

					$result = $GLOBALS['db']->query($sql, $params);
					if (Jaws_Error::IsError($result)) {
						$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
						return false;
					}

					// Let everyone know it has been added
					$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
					$res = $GLOBALS['app']->Shouter->Shout('onAddProductParent', $parent['productparentid']);
					if (Jaws_Error::IsError($res) || !$res) {
						$GLOBALS['app']->Session->PushLastResponse((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_PRODUCTPARENT_NOT_ADDED')), RESPONSE_ERROR);
						return new Jaws_Error((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_PRODUCTPARENT_NOT_ADDED')), _t('STORE_NAME'));
					}
				}
			}
			$posts = $model->GetAttributeTypes();
			if (Jaws_Error::IsError($posts)) {
				return false;
			}
			foreach ($posts as $parent) {
				if (empty($parent['checksum']) || is_null($parent['checksum']) || strpos($parent['checksum'], ':') === false) {
					$params               	= array();
					$params['id'] 			= $parent['id'];
					$params['checksum'] 	= $parent['id'].':'.$config_key;
					
					$sql = '
						UPDATE [[attribute_types]] SET
							[checksum] = {checksum}
						WHERE [id] = {id}';

					$result = $GLOBALS['db']->query($sql, $params);
					if (Jaws_Error::IsError($result)) {
						$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
						return false;
					}

					// Let everyone know it has been added
					$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
					$res = $GLOBALS['app']->Shouter->Shout('onAddProductAttributeType', $parent['id']);
					if (Jaws_Error::IsError($res) || !$res) {
						$GLOBALS['app']->Session->PushLastResponse((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_ATTRIBUTETYPE_NOT_ADDED')), RESPONSE_ERROR);
						return new Jaws_Error((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_ATTRIBUTETYPE_NOT_ADDED')), _t('STORE_NAME'));
					}
				}
			}
			$posts = $model->GetProductAttributes();
			if (Jaws_Error::IsError($posts)) {
				return false;
			}
			foreach ($posts as $parent) {
				if (empty($parent['checksum']) || is_null($parent['checksum']) || strpos($parent['checksum'], ':') === false) {
					$params               	= array();
					$params['id'] 			= $parent['id'];
					$params['checksum'] 	= $parent['id'].':'.$config_key;
					
					$sql = '
						UPDATE [[productattribute]] SET
							[checksum] = {checksum}
						WHERE [id] = {id}';

					$result = $GLOBALS['db']->query($sql, $params);
					if (Jaws_Error::IsError($result)) {
						$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
						return false;
					}

					// Let everyone know it has been added
					$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
					$res = $GLOBALS['app']->Shouter->Shout('onAddProductAttribute', $parent['id']);
					if (Jaws_Error::IsError($res) || !$res) {
						$GLOBALS['app']->Session->PushLastResponse((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_PRODUCTATTRIBUTE_NOT_ADDED')), RESPONSE_ERROR);
						return new Jaws_Error((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_PRODUCTATTRIBUTE_NOT_ADDED')), _t('STORE_NAME'));
					}
				}
			}
			$posts = $model->GetSales();
			if (Jaws_Error::IsError($posts)) {
				return false;
			}
			foreach ($posts as $parent) {
				if (empty($parent['checksum']) || is_null($parent['checksum']) || strpos($parent['checksum'], ':') === false) {
					$params               	= array();
					$params['id'] 			= $parent['id'];
					$params['checksum'] 	= $parent['id'].':'.$config_key;
					
					$sql = '
						UPDATE [[sales]] SET
							[checksum] = {checksum}
						WHERE [id] = {id}';

					$result = $GLOBALS['db']->query($sql, $params);
					if (Jaws_Error::IsError($result)) {
						$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
						return false;
					}

					// Let everyone know it has been added
					$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
					$res = $GLOBALS['app']->Shouter->Shout('onAddProductSale', $parent['id']);
					if (Jaws_Error::IsError($res) || !$res) {
						$GLOBALS['app']->Session->PushLastResponse((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_SALE_NOT_ADDED')), RESPONSE_ERROR);
						return new Jaws_Error((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_SALE_NOT_ADDED')), _t('STORE_NAME'));
					}
				}
			}
			$posts = $model->GetBrands();
			if (Jaws_Error::IsError($posts)) {
				return false;
			}
			foreach ($posts as $parent) {
				if (empty($parent['checksum']) || is_null($parent['checksum']) || strpos($parent['checksum'], ':') === false) {
					$params               	= array();
					$params['id'] 			= $parent['id'];
					$params['checksum'] 	= $parent['id'].':'.$config_key;
					
					$sql = '
						UPDATE [[productbrand]] SET
							[checksum] = {checksum}
						WHERE [id] = {id}';

					$result = $GLOBALS['db']->query($sql, $params);
					if (Jaws_Error::IsError($result)) {
						$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
						return false;
					}

					// Let everyone know it has been added
					$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
					$res = $GLOBALS['app']->Shouter->Shout('onAddProductBrand', $parent['id']);
					if (Jaws_Error::IsError($res) || !$res) {
						$GLOBALS['app']->Session->PushLastResponse((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_BRAND_NOT_ADDED')), RESPONSE_ERROR);
						return new Jaws_Error((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_BRAND_NOT_ADDED')), _t('STORE_NAME'));
					}
				}
			}
			$posts = $model->GetProducts();
			if (Jaws_Error::IsError($posts)) {
				return false;
			}
			foreach ($posts as $parent) {
				if (empty($parent['checksum']) || is_null($parent['checksum']) || strpos($parent['checksum'], ':') === false) {
					$params               	= array();
					$params['id'] 			= $parent['id'];
					$params['checksum'] 	= $parent['id'].':'.$config_key;
					
					$sql = '
						UPDATE [[product]] SET
							[checksum] = {checksum}
						WHERE [id] = {id}';

					$result = $GLOBALS['db']->query($sql, $params);
					if (Jaws_Error::IsError($result)) {
						$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
						return false;
					}

					// Let everyone know it has been added
					$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
					$res = $GLOBALS['app']->Shouter->Shout('onAddProduct', $parent['id']);
					if (Jaws_Error::IsError($res) || !$res) {
						$GLOBALS['app']->Session->PushLastResponse((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_PRODUCT_NOT_ADDED')), RESPONSE_ERROR);
						return new Jaws_Error((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_PRODUCT_NOT_ADDED')), _t('STORE_NAME'));
					}
				}

				$posts1 = $model->GetAllPostsOfProduct($parent['id']);
				if (Jaws_Error::IsError($posts1)) {
					return false;
				}
				foreach ($posts1 as $post) {
					if (empty($post['checksum']) || is_null($post['checksum']) || strpos($post['checksum'], ':') === false) {
						$params               	= array();
						$params['id'] 			= $post['id'];
						$params['checksum'] 	= $post['id'].':'.$config_key;
						
						$sql = '
							UPDATE [[product_posts]] SET
								[checksum] = {checksum}
							WHERE [id] = {id}';

						$result = $GLOBALS['db']->query($sql, $params);
						if (Jaws_Error::IsError($result)) {
							$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
							return false;
						}

						// Let everyone know it has been added
						$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
						$res = $GLOBALS['app']->Shouter->Shout('onAddProductPost', $post['id']);
						if (Jaws_Error::IsError($res) || !$res) {
							$GLOBALS['app']->Session->PushLastResponse((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_POST_NOT_ADDED')), RESPONSE_ERROR);
							return new Jaws_Error((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('STORE_ERROR_POST_NOT_ADDED')), _t('STORE_NAME'));
						}
					}
				}
			}
		}
		return true;
    }
}
