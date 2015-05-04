<?php
/**
 * CustomPage Gadget
 *
 * @category   GadgetModel
 * @package    CustomPage
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */

require_once JAWS_PATH . 'gadgets/CustomPage/Model.php';
class CustomPageAdminModel extends CustomPageModel
{
    var $_Name = 'CustomPage';
	
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
			$theme = $GLOBALS['app']->GetTheme();
			$theme_name = '';
			if (isset($theme['name']) && !empty($theme['name'])) {
				$theme_name = $theme['name'];
				if (substr(strtolower($theme['name']), 0, 4) != 'http') {
					$theme_name = 'themes/'.$theme['name'].'/CustomPage';
				}
			}
			$variables['theme'] = $theme_name;

			$result = $this->installSchema('insert.xml', $variables, 'schema.xml', true);
			if (Jaws_Error::IsError($result)) {
				return $result;
			}
						
		}

		// Listeners and Shouters
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
        $GLOBALS['app']->Shouter->NewShouter('Core', 'onAddContent');          		// trigger an action when we add content
        $GLOBALS['app']->Shouter->NewShouter('Core', 'onUpdateContent');          	// trigger an action when we update content
        $GLOBALS['app']->Shouter->NewShouter('Core', 'onRemoveContent');          	// trigger an action when we remove content
        $GLOBALS['app']->Shouter->NewShouter('CustomPage', 'onAddPage');          
        $GLOBALS['app']->Shouter->NewShouter('CustomPage', 'onUpdatePage');          
        $GLOBALS['app']->Shouter->NewShouter('CustomPage', 'onDeletePage');          
        $GLOBALS['app']->Shouter->NewShouter('CustomPage', 'onAddPagePost');          
        $GLOBALS['app']->Shouter->NewShouter('CustomPage', 'onUpdatePagePost');          
        $GLOBALS['app']->Shouter->NewShouter('CustomPage', 'onDeletePagePost');          
        $GLOBALS['app']->Shouter->NewShouter('CustomPage', 'onAddPageSplashPanel');          
        $GLOBALS['app']->Shouter->NewShouter('CustomPage', 'onUpdatePageSplashPanel');          
        $GLOBALS['app']->Shouter->NewShouter('CustomPage', 'onDeletePageSplashPanel');          
        $GLOBALS['app']->Shouter->NewShouter('CustomPage', 'onAddPageSection');          
        $GLOBALS['app']->Shouter->NewShouter('CustomPage', 'onUpdatePageSection');          
        $GLOBALS['app']->Shouter->NewShouter('CustomPage', 'onDeletePageSection');          
		$GLOBALS['app']->Shouter->NewShouter('CustomPage', 'onBeforeLoadPage');
		
		$GLOBALS['app']->loadClass('Listener', 'Jaws_EventListener');
		$GLOBALS['app']->Listener->NewListener('CustomPage', 'onAddContent', 'NotifyContentAdded');
		$GLOBALS['app']->Listener->NewListener('CustomPage', 'onUpdateContent', 'NotifyContentUpdated');
		$GLOBALS['app']->Listener->NewListener('CustomPage', 'onRemoveContent', 'NotifyContentDeleted');
        $GLOBALS['app']->Listener->NewListener('CustomPage', 'onDeleteUser', 'RemoveUserPages');
        $GLOBALS['app']->Listener->NewListener('CustomPage', 'onUpdateUser', 'UpdateUserPages');
        $GLOBALS['app']->Listener->NewListener('CustomPage', 'onDeleteGroup', 'RemoveGroupPages');
		$GLOBALS['app']->Listener->NewListener('CustomPage', 'onAfterEnablingGadget', 'InsertHomePageToMenu');
		$GLOBALS['app']->Listener->NewListener('CustomPage', 'onAfterEnablingGadget', 'InsertDefaultChecksums');
		$GLOBALS['app']->Listener->NewListener('CustomPage', 'onDeleteLayoutElement', 'RemoveLayoutPost');
		$GLOBALS['app']->Listener->NewListener('CustomPage', 'onUpdateLayoutElement', 'UpdateLayoutPost');

		// Registry keys.
        $GLOBALS['app']->Registry->NewKey('/gadgets/CustomPage/default_page', '1');
        $GLOBALS['app']->Registry->NewKey('/gadgets/CustomPage/googleanalytics_code', '');
        $GLOBALS['app']->Registry->Set('/config/main_gadget', $this->_Name);
		
		// Create Jaws_User groups
        require_once JAWS_PATH . 'include/Jaws/User.php';
        $userModel = new Jaws_User;
        $userModel->addGroup('custompage_owners', false); //Don't check if it returns true or false
        $group = $userModel->GetGroupInfoByName('custompage_owners');
		if (isset($group['id']) && !empty($group['id'])) {
			$GLOBALS['app']->ACL->NewKey('/ACL/groups/'.$group['id'].'/gadgets/CustomPage/OwnPage', 'true');
        }
        //$userModel->addGroup('custompage_users', false); //Don't check if it returns true or false

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
        $tables = array('pages',
                        'pages_posts',
						'splash_panels',
						'rss_hide',
						'pages_sections');
        foreach ($tables as $table) {
            $result = $GLOBALS['db']->dropTable($table);
            if (Jaws_Error::IsError($result)) {
                $gName  = _t('CUSTOMPAGE_NAME');
                $errMsg = _t('GLOBAL_ERROR_GADGET_NOT_UNINSTALLED', $gName);
                $GLOBALS['app']->Session->PushLastResponse($errMsg, RESPONSE_ERROR);
                return new Jaws_Error($errMsg, $gName);
            }
        }

		// Listeners and Shouters
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
        $GLOBALS['app']->Shouter->DeleteShouter('Core', 'onAddContent');
        $GLOBALS['app']->Shouter->DeleteShouter('Core', 'onUpdateContent');
        $GLOBALS['app']->Shouter->DeleteShouter('Core', 'onRemoveContent');
        $GLOBALS['app']->Shouter->DeleteShouter('CustomPage', 'onAddPage');          
        $GLOBALS['app']->Shouter->DeleteShouter('CustomPage', 'onUpdatePage');          
        $GLOBALS['app']->Shouter->DeleteShouter('CustomPage', 'onDeletePage');          
        $GLOBALS['app']->Shouter->DeleteShouter('CustomPage', 'onAddPagePost');          
        $GLOBALS['app']->Shouter->DeleteShouter('CustomPage', 'onUpdatePagePost');          
        $GLOBALS['app']->Shouter->DeleteShouter('CustomPage', 'onDeletePagePost');          
        $GLOBALS['app']->Shouter->DeleteShouter('CustomPage', 'onAddPageSplashPanel');          
        $GLOBALS['app']->Shouter->DeleteShouter('CustomPage', 'onUpdatePageSplashPanel');          
        $GLOBALS['app']->Shouter->DeleteShouter('CustomPage', 'onDeletePageSplashPanel');          
        $GLOBALS['app']->Shouter->DeleteShouter('CustomPage', 'onAddPageSection');          
        $GLOBALS['app']->Shouter->DeleteShouter('CustomPage', 'onUpdatePageSection');          
        $GLOBALS['app']->Shouter->DeleteShouter('CustomPage', 'onDeletePageSection');          
        $GLOBALS['app']->Shouter->DeleteShouter('CustomPage', 'onBeforeLoadPage');          
		
		$GLOBALS['app']->loadClass('Listener', 'Jaws_EventListener');
		$GLOBALS['app']->Listener->DeleteListener('CustomPage', 'NotifyContentAdded');
		$GLOBALS['app']->Listener->DeleteListener('CustomPage', 'NotifyContentUpdated');
		$GLOBALS['app']->Listener->DeleteListener('CustomPage', 'NotifyContentDeleted');
        $GLOBALS['app']->Listener->DeleteListener('CustomPage', 'RemoveGroupPages');
        $GLOBALS['app']->Listener->DeleteListener('CustomPage', 'RemoveUserPages');
        $GLOBALS['app']->Listener->DeleteListener('CustomPage', 'UpdateUserPages');
		$GLOBALS['app']->Listener->DeleteListener('CustomPage', 'InsertDefaultChecksums');
		$GLOBALS['app']->Listener->DeleteListener('CustomPage', 'UpdateLayoutPost');
		$GLOBALS['app']->Listener->DeleteListener('CustomPage', 'RemoveLayoutPost');

        // Registry keys
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/CustomPage/default_page');
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/CustomPage/googleanalytics_code');
        $GLOBALS['app']->Registry->Set('/config/main_gadget', 'Users');
		/*
		if (in_array('CustomPage', explode(',', $GLOBALS['app']->Registry->Get('/gadgets/user_access_items')))) {
			if ($GLOBALS['app']->Registry->Get('/gadgets/user_access_items') == 'CustomPage') {
				$GLOBALS['app']->Registry->Set('/gadgets/user_access_items', '');
			} else {
				$GLOBALS['app']->Registry->Set('/gadgets/user_access_items', str_replace(',CustomPage', '', $GLOBALS['app']->Registry->Get('/gadgets/user_access_items')));
			}
		}
		*/
		
		// Delete Jaws_User groups
        require_once JAWS_PATH . 'include/Jaws/User.php';
        $userModel = new Jaws_User;
        $group = $userModel->GetGroupInfoByName('custompage_owners');
		if (isset($group['id']) && !empty($group['id'])) {
			$userModel->DeleteGroup($group['id']);
			$GLOBALS['app']->ACL->DeleteKey('/ACL/groups/'.$group['id'].'/gadgets/CustomPage/OwnPage');
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
     * @param   string  $old	Current version (in registry)
     * @param   string  $new	New version (in the $gadgetInfo file)
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
        
		$GLOBALS['app']->loadClass('Listener', 'Jaws_EventListener');
		if (version_compare($old, '0.1.2', '<')) {			
			$result = $this->installSchema('schema.xml', '', '0.1.1.xml');
			if (Jaws_Error::IsError($result)) {
				return $result;
			}
			$GLOBALS['app']->Listener->NewListener('CustomPage', 'onUpdateLayoutElement', 'UpdateLayoutPost');
			$GLOBALS['app']->Listener->NewListener('CustomPage', 'onDeleteLayoutElement', 'RemoveLayoutPost');
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
     * Creates a new page.
     *
     * @param   int  	 $pid         The parent ID of the page.
     * @param   string  $fast_url       The fast URL of the page.
     * @param   boolean $show_title     If the document should publish the title or not
     * @param   string  $sm_description          The title of the page.
     * @param   string  $content        The new contents of the page.
     * @param   string  $image    	   image to accompany the contents of page
     * @param   int  	 $image_width    image width in pixels
     * @param   int  	 $image_height   image height in pixels
     * @param   string  $logo    	   image to replace Title of page
     * @param   string  $keywords       The keywords (META) of page
     * @param   string  $description    The description (META) of page
     * @param   int  	 $pageCol        Number of columns for data on page (posts, products, etc)
     * @param   int  	 $pageConst      Data limit on page (posts, products, etc)
     * @param   int  	 $layout         Layout of page...
     * @param   int  	 $theme          Theme of page...
     * @param   string  $active         (Y/N) If the page is published or not
     * @param   int  	 $LinkID        Gadget reference ID
     * @param   string  $gadget         Gadget reference
     * @param   int  	 $OwnerID        The poster's user ID
     * @param   string  $rss_url         RSS URL
     * @param   string  $image_code      Script code (for <head> of page)
     * @param   string  $auto_keyword 	Keyword to auto-generate content for
     * @param   string  $checksum 	Unique ID
     * @param   boolean 	$auto       		If it's auto saved or not
     * @param   boolean 	$create_menu       	If a menu item should be created for this page
     * @param   string 	$password_protected 	(Y/N) Is this page password protected?
     * @param   string  $gadget         Gadget page type
     * @access  public
     * @return  boolean    Success/failure
     */
    function AddPage(
		$pid = 0, $fast_url, $show_title, $sm_description, $content, $image, $image_width, 
		$image_height, $logo, $keywords, $description, $pageCol, $pageConst = 12, $layout, $theme = null, 
		$active, $LinkID, $gadget = 'CustomPage', $OwnerID = null, $rss_url = null, $image_code = '', $auto_keyword = '', 
		$checksum = '', $auto = false, $create_menu = true, $password_protected = 'N', $gadget_action = 'Page'
	) {
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$model = $GLOBALS['app']->LoadGadget('CustomPage', 'Model');
        
		$fast_url = (!empty($fast_url) ? $fast_url : (!empty($sm_description) ? $sm_description : ''));
        $sm_description = (!empty($sm_description) ? $sm_description : (!empty($fast_url) ? $fast_url : ''));
		if (empty($fast_url)) {
			$fast_url = time();
			$show_title = false;
		}
		
		$title = $fast_url;
		
		// Get the fast url
        $fast_url = $this->GetRealFastUrl(
			$fast_url, 'pages', true
		);
		        
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
		
		if (!empty($logo)) {
			$logo = $this->cleanImagePath($logo);
			if (
				$OwnerID > 0 && 
				(substr(strtolower(trim($logo)), 0, 4) == 'http' || 
				substr(strtolower(trim($logo)), 0, 2) == '//' || 
				substr(strtolower(trim($logo)), 0, 2) == '\\\\') 
			) {
				$logo = '';
			}
		}
		
		$content = strip_tags($content, '<p><a><b><em><i><strong><div><table><tbody><thead><tr><td><span><font><hr><br><img><marquee><ul><ol><li>');
		  
		$sql = "
            INSERT INTO [[pages]]
                ([pid], [fast_url], [title], [show_title], [content],
				[sm_description], [image], [image_width], [image_height], [logo], [description], 
				[keywords], [pagecol], [pageconst], [layout], [theme], 
				[ownerid], [gadget], [gadget_action], [linkid], [active], [created], [updated], [rss_url], [image_code], 
				[auto_keyword], [checksum])
            VALUES
                ({pid}, {fast_url}, {title}, {show_title}, {content}, 
				{sm_description}, {image}, {image_width}, {image_height}, {logo}, {description}, 
				{keywords}, {pageCol}, {pageConst}, {layout}, {theme}, 
				{ownerid}, {gadget}, {gadget_action}, {LinkID}, {Active}, {now}, {now}, {rss_url}, {image_code}, 
				{auto_keyword}, {checksum})";
		
        $pid = (int)$pid;
        $rss_url = !empty($rss_url) ? $xss->parse($rss_url) : null;
		$image_code = ($OwnerID == 0 ? htmlspecialchars($image_code) : '');
		if (empty($theme) && $layout != 'layout.html') {
			$theme = $GLOBALS['app']->GetTheme();
			$theme_name = '';
			if (isset($theme['name']) && !empty($theme['name'])) {
				$theme_name = $theme['name'];
				if (substr(strtolower($theme['name']), 0, 4) != 'http') {
					$theme_name = 'themes/'.$theme['name'].'/CustomPage';
				}
			}
			$theme = $theme_name;
        }
		$params               		= array();
        $params['pid']         		= $pid;
        $params['title'] 			= $xss->parse($title);
        $params['fast_url'] 		= $xss->parse($fast_url);
        $params['show_title'] 		= (bool)$show_title;
        $params['content']   		= str_replace("\r\n", "\n", $content);
        $params['sm_description'] 	= $xss->parse($sm_description);
        $params['image'] 			= $xss->parse($image);
        $params['image_width'] 		= (int)$image_width;
        $params['image_height'] 	= (int)$image_height;
        $params['logo'] 			= $xss->parse($logo);
		$params['description']      = $xss->parse(strip_tags(str_replace("\r\n", ", ", $description)));
		$params['keywords']       	= $xss->parse(strip_tags(str_replace("\r\n", ", ", $keywords)));
        $params['pageCol']         	= (int)$pageCol;
        $params['pageConst']        = (int)$pageConst;
        $params['layout']         	= $xss->parse($layout);
        $params['theme']         	= $xss->parse($theme);
        $params['gadget'] 			= $xss->parse($gadget);
        $params['gadget_action'] 	= $xss->parse($gadget_action);
		$params['ownerid']         	= $OwnerID;
		$params['rss_url']         	= $rss_url;
		$params['image_code']       = $image_code;
		$params['auto_keyword']     = $xss->parse($auto_keyword);
        $params['LinkID']         	= $xss->parse($LinkID);
        $params['Active'] 			= $xss->parse($active);
        $params['checksum'] 		= $xss->parse($checksum);
        $params['now']        		= $GLOBALS['db']->Date();
		
		$result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_PAGE_NOT_ADDED'), RESPONSE_ERROR);
            return $result;
        }
        $newid = $GLOBALS['db']->lastInsertID('pages', 'id');

		if (BASE_SCRIPT != 'index.php' && $create_menu === true) {
			// add Menu Item for Page
			$visible = ($active == 'Y') ? 1 : 0;
			$url = $GLOBALS['app']->Map->GetURLFor('CustomPage', 'Page', array('id' => $fast_url));
			
			$sql  = 'SELECT [id] FROM [[menus]] WHERE [url] = {url}';
			$oid = $GLOBALS['db']->queryRow($sql, array('url' => $url));
			if (Jaws_Error::IsError($oid)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
				return false;
			} else {
				if (empty($oid['id'])) {
					// Get highest rank of current menu items
					$sql = "SELECT MAX([rank]) FROM [[menus]] WHERE [gid] = 1 ORDER BY [rank] DESC";
					$rank = $GLOBALS['db']->queryOne($sql);
					if (Jaws_Error::IsError($rank)) {
						$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
						return false;
					}
					$menuAdmin = $GLOBALS['app']->LoadGadget('Menu', 'AdminModel');
					if (!$menuAdmin->InsertMenu($pid, 1, 'CustomPage', $title, $url, 0, (int)$rank+1, $visible, true)) {
						$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
						return false;
					}
				} else {
					$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
					return false;
				}
			}
		}

		if ((empty($LinkID) || $LinkID == '0' || $LinkID == 0) || empty($checksum)) {
			// Update checksum
			$config_key = $GLOBALS['app']->Registry->Get('/config/key');		
			$params               	= array();
			$params['id'] 			= $newid;
			$sql = '
				UPDATE [[pages]] SET
			';
			if (empty($LinkID) || $LinkID == '0' || $LinkID == 0) {
				$params['linkid'] 		= $newid;
				$sql .= '[linkid] = {linkid}';
			}	
			if (empty($checksum)) {
				$params['checksum'] 	= $newid.':'.$config_key;
				$sql .= (empty($LinkID) || $LinkID == '0' || $LinkID == 0 ? ', ' : '').'[checksum] = {checksum}';
			}
			$sql .= '
				WHERE [id] = {id}';

			$result = $GLOBALS['db']->query($sql, $params);
			if (Jaws_Error::IsError($result)) {
				$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
				return false;
			}
		}
		
		for ($i = 0; $i < 4; $i++) {
			$sql = "
	            INSERT INTO [[pages_sections]]
	                ([sort_order], [page_id], [stack], [created], [updated], [checksum])
	            VALUES
	                ({sort_order}, {page_id}, {stack}, {now}, {now}, {checksum})";

	        $params               		= array();
	        $params['page_id']         	= $newid;
			$params['stack'] 			= 'vertical';
	        $params['sort_order'] 		= $i;
	        $params['checksum'] 		= $checksum;
	        $params['now']        		= $GLOBALS['db']->Date();
			
			$result = $GLOBALS['db']->query($sql, $params);
	        if (Jaws_Error::IsError($result)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_PAGE_NOT_ADDED'), RESPONSE_ERROR);
	            return $result;
	        }
			$sectionid = $GLOBALS['db']->lastInsertID('pages_sections', 'id');
						
			// Let everyone know it has been added
			$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
			$res = $GLOBALS['app']->Shouter->Shout('onAddPageSection', $sectionid);
			if (Jaws_Error::IsError($res) || !$res) {
				return $res;
			}
		}
		
		// Password protected?
		$GLOBALS['app']->Registry->LoadFile('Users');
		$GLOBALS['app']->Translate->LoadTranslation('Users', JAWS_GADGET);
		$password_pages = $GLOBALS['app']->Registry->Get('/gadgets/Users/protected_pages');
		$params = array();
		$params['url'] = $GLOBALS['app']->Map->GetURLFor('CustomPage', 'Page', array('id' => $fast_url));
		$params['url2'] = $GLOBALS['app']->GetSiteURL().'/'.$params['url'];
		$sql = '
			SELECT
				[id], [menu_type], [title], [url], [visible]
			FROM [[menus]]
			WHERE [url] = {url} OR [url] = {url2}
			ORDER BY [menu_type] ASC, [title] ASC';
		
		$menus = $GLOBALS['db']->queryAll($sql, $params);
		if (Jaws_Error::IsError($menus)) {
			return $menus;
		}

		if ($password_protected == 'Y') {
			if (is_array($menus)) {
				foreach ($menus as $menu => $m) {
					if (!in_array($m['id'].'', explode(',',$password_pages))) {
						if ($password_pages == '') {
							$GLOBALS['app']->Registry->Set('/gadgets/Users/protected_pages', $m['id']);
							$GLOBALS['app']->Registry->Commit('Users');
							$GLOBALS['app']->Registry->Commit('core');
						} else {
							$GLOBALS['app']->Registry->Set('/gadgets/Users/protected_pages', $password_pages.','.$m['id']);
							$GLOBALS['app']->Registry->Commit('Users');
							$GLOBALS['app']->Registry->Commit('core');
						}
					}
				}
			}
		} else {
			if (is_array($menus)) {
				foreach ($menus as $menu => $m) {
					if (in_array($m['id'].'', explode(',',$password_pages))) {
						if ($password_pages == $m['id']) {
							$GLOBALS['app']->Registry->Set('/gadgets/Users/protected_pages', '');
							$GLOBALS['app']->Registry->Commit('Users');
							$GLOBALS['app']->Registry->Commit('core');
						} else {
							$GLOBALS['app']->Registry->Set('/gadgets/Users/protected_pages', str_replace(','.$m['id'], '', $password_pages));
							$GLOBALS['app']->Registry->Commit('Users');
							$GLOBALS['app']->Registry->Commit('core');
						}
					}
				}
			}
		}
		
		// Let everyone know it has been added
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onAddPage', $newid);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		
        $GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_PAGE_CREATED'), RESPONSE_NOTICE);
        return $newid;
    }

    /**
     * Updates a page.
     *
     * @param   int     $id             The ID of the page to update.
     * @param   int  	 $pid         The parent ID of the page.
     * @param   string  $fast_url       The fast URL of the page.
     * @param   boolean $show_title     If the document should publish the title or not
     * @param   string  $sm_description          The title of the page.
     * @param   string  $content        The new contents of the page.
     * @param   string  $image    	   image to accompany the contents of page
     * @param   int  	 $image_width    image width in pixels
     * @param   int  	 $image_height   image height in pixels
     * @param   string  $logo    	   image to replace Title of page
     * @param   string  $keywords       The keywords (META) of page
     * @param   string  $description    The description (META) of page
     * @param   int  	 $pageCol        Number of columns for data on page (posts, products, etc)
     * @param   int  	 $pageConst      Data limit on page (posts, products, etc)
     * @param   int  	 $layout         Layout of page...
     * @param   int  	 $theme          Theme of page...
     * @param   string  $active         (Y/N) If the page is published or not
     * @param   string  $rss_url         RSS URL
     * @param   string  $image_code      Script code (for <head> of page)
     * @param   string  $auto_keyword 	Keyword to auto-generate content for
     * @param   boolean 	$auto       		If it's auto saved or not
     * @param   string 	$password_protected 	(Y/N) Is this page password protected?
     * @access  public
     * @return  boolean 	Success/failure
     */
    function UpdatePage(
		$id, $pid, $fast_url, $show_title, $sm_description, $content, $image, 
		$image_width, $image_height, $logo, $keywords, $description, $pageCol, $pageConst, $layout, 
		$theme, $active, $rss_url, $image_code, $auto_keyword, $auto = false, $password_protected = null
	) {
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$model = $GLOBALS['app']->LoadGadget('CustomPage', 'Model');
        $params               		= array();
		
        $page = $model->GetPage((int)$id);
        if (Jaws_Error::isError($page)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_PAGE_NOT_FOUND'), RESPONSE_ERROR);
            return $page;
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
		
		if (!empty($logo)) {
			$logo = $this->cleanImagePath($logo);
			if (
				$OwnerID > 0 && 
				(substr(strtolower(trim($logo)), 0, 4) == 'http' || 
				substr(strtolower(trim($logo)), 0, 2) == '//' || 
				substr(strtolower(trim($logo)), 0, 2) == '\\\\') 
			) {
				$logo = '';
			}
		}

        $rss_url = (!empty($rss_url) ? $xss->parse($rss_url) : null);
        $fast_url = (!empty($fast_url) ? $fast_url : (!empty($sm_description) ? $sm_description : ''));
        $sm_description = (!empty($sm_description) ? $sm_description : (!empty($fast_url) ? $fast_url : ''));
		if (empty($fast_url)) {
			$fast_url = time();
			$show_title = false;
		}
		$title = $fast_url;
		
		// Get the fast url
        $fast_url = $this->GetRealFastUrl(
			$fast_url, 'pages', true, 'fast_url', 'id', $id
		);
		
        //Current fast url changes?
        if ($page['fast_url'] != $fast_url && $auto === false) {
            $oldfast_url = $page['fast_url'];
        }

		$content = strip_tags($content, '<p><a><b><em><i><strong><div><table><tbody><thead><tr><td><span><font><hr><br><img><marquee><ul><ol><li>');
		$image_code = ($page['ownerid'] == 0 ? htmlspecialchars($image_code) : '');
		if (empty($theme) && $layout != 'layout.html') {
			$theme = $GLOBALS['app']->GetTheme();
			$theme_name = '';
			if (isset($theme['name']) && !empty($theme['name'])) {
				$theme_name = $theme['name'];
				if (substr(strtolower($theme['name']), 0, 4) != 'http') {
					$theme_name = 'themes/'.$theme['name'].'/CustomPage';
				}
			}
			$theme = $theme_name;
        }

		$sql = '
		UPDATE [[pages]] SET
			[pid] = {pid}, 
			[sm_description] = {sm_description}, 
			[content] = {content}, 
			[image] = {image}, 
			[image_width] = {image_width},
			[image_height] = {image_height},
			[logo] = {logo}, 
			[fast_url] = {fast_url}, 
			[title] = {title}, 
			[show_title] = {show_title}, 
			[description] = {description}, 
			[keywords] = {keywords}, 
			[pagecol] = {pageCol}, 
			[pageconst] = {pageConst},
			[layout] = {layout},
			';
		if (!empty($theme)) {
	        $params['theme']         	= $theme;
			$sql .=	'[theme] = {theme},
				';
		}
		$sql .=	'[active] = {Active}, 
			[updated] = {now},
			[rss_url] = {rss_url},
			[image_code] = {image_code},
			[auto_keyword] = {auto_keyword}
		WHERE [id] = {id}';

        $params['id']         		= (int)$id;
        $params['pid']         		= (int)$pid;
        $params['fast_url'] 		= $xss->parse($fast_url);
        $params['show_title'] 		= (bool)$show_title;
        $params['title'] 			= $xss->parse($title);
        $params['content']   		= str_replace("\r\n", "\n", $content);
        $params['sm_description'] 	= $xss->parse($sm_description);
        $params['image'] 			= $xss->parse($image);
        $params['image_width'] 		= (int)$image_width;
        $params['image_height'] 	= (int)$image_height;
        $params['logo'] 			= $xss->parse($logo);
		$params['description']      = $xss->parse(strip_tags(str_replace("\r\n", ", ", $description)));
		$params['keywords']       	= $xss->parse(strip_tags(str_replace("\r\n", ", ", $keywords)));
        $params['pageCol']         	= (int)$pageCol;
        $params['pageConst']        = (int)$pageConst;
        $params['layout']         	= $xss->parse($layout);
        $params['Active'] 			= $xss->parse($active);
		$params['rss_url']         	= $rss_url;
        $params['image_code']   	= $image_code;
		$params['auto_keyword']     = $xss->parse($auto_keyword);
        $params['now']        		= $GLOBALS['db']->Date();		

        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_PAGE_NOT_UPDATED'), RESPONSE_ERROR);
            return $result;
        }

		if (BASE_SCRIPT != 'index.php') {
			// update Menu Item for Page
			$visible = ($active == 'Y') ? 1 : 0;
			// if old title is different, update menu item
			if (isset($oldfast_url) && !empty($oldfast_url)) {
				$old_url = $GLOBALS['app']->Map->GetURLFor('CustomPage', 'Page', array('id' => $oldfast_url));
			} else {
				$old_url = $GLOBALS['app']->Map->GetURLFor('CustomPage', 'Page', array('id' => $fast_url));
			}
			$new_url = $GLOBALS['app']->Map->GetURLFor('CustomPage', 'Page', array('id' => $fast_url));
			
			if ($old_url != $new_url) {
				$sql  = 'SELECT [id], [rank] FROM [[menus]] WHERE [url] = {url}';
				$oid = $GLOBALS['db']->queryRow($sql, array('url' => $old_url));
				if (Jaws_Error::IsError($oid)) {
					//$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
					$GLOBALS['app']->Session->PushLastResponse($oid->GetMessage(), RESPONSE_ERROR);
					return false;
				} else if (!empty($oid['id']) && isset($oid['id'])) {
					$menuAdmin = $GLOBALS['app']->LoadGadget('Menu', 'AdminModel');
					if (!$menuAdmin->UpdateMenu($oid['id'], (int)$pid, 1, 'CustomPage', $title, $new_url, 0, $oid['rank'], $visible)) {
						//$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
						$GLOBALS['app']->Session->PushLastResponse($menuAdmin->GetMessage(), RESPONSE_ERROR);
						return false;
					}
				} else {
					// add Menu Item for Page
					$visible = ($active == 'Y') ? 1 : 0;
					$url = $GLOBALS['app']->Map->GetURLFor('CustomPage', 'Page', array('id' => $fast_url));
					
					$sql  = 'SELECT [id] FROM [[menus]] WHERE [url] = {url}';
					$oid = $GLOBALS['db']->queryRow($sql, array('url' => $url));
					if (Jaws_Error::IsError($oid)) {
						$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
						return false;
					} else {
						if (empty($oid['id'])) {
							// Get highest rank of current menu items
							$sql = "SELECT MAX([rank]) FROM [[menus]] WHERE [gid] = 1 ORDER BY [rank] DESC";
							$rank = $GLOBALS['db']->queryOne($sql);
							if (Jaws_Error::IsError($rank)) {
								$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
								return false;
							}
							$menuAdmin = $GLOBALS['app']->LoadGadget('Menu', 'AdminModel');
							if (!$menuAdmin->InsertMenu((int)$pid, 1, 'CustomPage', $title, $url, 0, (int)$rank+1, $visible, true)) {
								$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
								return false;
							}
						} else {
							$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
							return false;
						}
					}
				}
				if ($GLOBALS['app']->Registry->Get('/config/home_page') == $old_url) {
					$GLOBALS['app']->Registry->Set('/config/home_page', $new_url);
					$GLOBALS['app']->Registry->Commit('core');
					$GLOBALS['app']->Session->PushLastResponse('New home page set', RESPONSE_NOTICE);
				}
			}
		}
		
		// Password protected?
		$params = array();
		$params['url'] = $GLOBALS['app']->Map->GetURLFor('CustomPage', 'Page', array('id' => $fast_url));
		$params['url2'] = $GLOBALS['app']->GetSiteURL().'/'.$params['url'];
		$sql = '
			SELECT
				[id], [menu_type], [title], [url], [visible]
			FROM [[menus]]
			WHERE [url] = {url} OR [url] = {url2}
			ORDER BY [menu_type] ASC, [title] ASC';
		
		$menus = $GLOBALS['db']->queryAll($sql, $params);
		if (Jaws_Error::IsError($menus)) {
			return $menus;
		}

		if (!is_null($password_protected) && is_array($menus)) {
			$GLOBALS['app']->Registry->LoadFile('Users');
			$GLOBALS['app']->Translate->LoadTranslation('Users', JAWS_GADGET);
			$password_pages = $GLOBALS['app']->Registry->Get('/gadgets/Users/protected_pages');
			foreach ($menus as $menu => $m) {
				if ($password_protected == 'Y') {
					if (!in_array($m['id'].'', explode(',',$password_pages))) {
						if ($password_pages == '') {
							$GLOBALS['app']->Registry->Set('/gadgets/Users/protected_pages', $m['id']);
							$GLOBALS['app']->Registry->Commit('Users');
							$GLOBALS['app']->Registry->Commit('core');
						} else {
							$GLOBALS['app']->Registry->Set('/gadgets/Users/protected_pages', $password_pages.','.$m['id']);
							$GLOBALS['app']->Registry->Commit('Users');
							$GLOBALS['app']->Registry->Commit('core');
						}
					}
				} else if ($password_protected == 'N') {
					if (in_array($m['id'].'', explode(',',$password_pages))) {
						if ($password_pages == $m['id']) {
							$GLOBALS['app']->Registry->Set('/gadgets/Users/protected_pages', '');
							$GLOBALS['app']->Registry->Commit('Users');
							$GLOBALS['app']->Registry->Commit('core');
						} else {
							$GLOBALS['app']->Registry->Set('/gadgets/Users/protected_pages', str_replace(','.$m['id'], '', $password_pages));
							$GLOBALS['app']->Registry->Commit('Users');
							$GLOBALS['app']->Registry->Commit('core');
						}
					}
				}
			}
		}
				
		// Let everyone know
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onUpdatePage', $id);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		
		if ($auto) {
            $GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_PAGE_AUTOUPDATED',
                                                     date('H:i:s'),
                                                     (int)$id,
                                                     date('D, d')),
                                                  RESPONSE_NOTICE);
        } else {
            $GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_PAGE_UPDATED'), RESPONSE_NOTICE);
        }
        return true;
    }

    /**
     * Updates a page's theme.
     *
     * @param   int     $id             The ID of the page to update.
     * @param   int  	 $layout         Layout of page...
     * @param   int  	 $theme          Theme of page...
     * @access  public
     * @return  boolean 	Success/failure
     */
    function UpdatePageTheme($id, $layout = '', $theme = '') {
        if (empty($layout) && empty($theme)) {
			return true;
		}
		$xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$model = $GLOBALS['app']->LoadGadget('CustomPage', 'Model');
        $params = array();
		
        $page = $model->GetPage((int)$id);
        if (Jaws_Error::isError($page)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_PAGE_NOT_FOUND'), RESPONSE_ERROR);
            return $page;
        }
        
		$sql = '
			UPDATE [[pages]] SET 
		';
		if (!empty($theme)) {
	        $params['layout'] = $xss->parse($layout);
			$sql .=	'[layout] = {layout}, 
			';
		}
		if (!empty($theme)) {
	        $params['theme'] = $theme;
			$sql .=	'[theme] = {theme}, 
			';
		}
		$sql .=	'[updated] = {now}
			WHERE [id] = {id}
		';

        $params['id']         		= (int)$id;
        $params['now']        		= $GLOBALS['db']->Date();		

        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_PAGE_NOT_UPDATED'), RESPONSE_ERROR);
            return $result;
        }
				
		// Let everyone know
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onUpdatePage', $id);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		
		$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_PAGE_UPDATED'), RESPONSE_NOTICE);
        return true;
    }

	/**
     * Delete a page
     *
     * @param  int 	$id 	Page ID
     * @param  boolean 	$massive 	Is this part of a massive delete?
     * @access  public
     * @return  boolean True if query was successful and Jaws_Error on error
     */
    function DeletePage($id, $massive = false)
    {
		$model = $GLOBALS['app']->LoadGadget('CustomPage', 'Model');
		$parent = $model->GetPage((int)$id);
		if (Jaws_Error::IsError($parent)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_PAGE_NOT_DELETED'), RESPONSE_ERROR);
			return new Jaws_Error(_t('CUSTOMPAGE_ERROR_PAGE_NOT_DELETED'), _t('CUSTOMPAGE_NAME'));
		}

		if(!isset($parent['id'])) {
			$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_PAGE_NOT_DELETED'), RESPONSE_ERROR);
			return new Jaws_Error(_t('CUSTOMPAGE_ERROR_PAGE_NOT_DELETED'), _t('CUSTOMPAGE_NAME'));
		} else {
			// Let everyone know
			$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
			$res = $GLOBALS['app']->Shouter->Shout('onDeletePage', $id);
			if (Jaws_Error::IsError($res) || !$res) {
				return $res;
			}
			
			$eids = $model->GetAllSubPagesOfPage((int)$id);
			if (Jaws_Error::IsError($eids)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_PAGE_NOT_DELETED'), RESPONSE_ERROR);
				return new Jaws_Error(_t('CUSTOMPAGE_ERROR_PAGE_NOT_DELETED'), _t('CUSTOMPAGE_NAME'));
			}

			foreach ($eids as $eid) {
				$rids = $model->GetAllPostsOfPage($eid['id']);
				if (Jaws_Error::IsError($rids)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_PAGE_NOT_DELETED'), RESPONSE_ERROR);
					return new Jaws_Error(_t('CUSTOMPAGE_ERROR_PAGE_NOT_DELETED'), _t('CUSTOMPAGE_NAME'));
				}

				foreach ($rids as $rid) {
					if (!$this->DeletePost($rid['id'], true)) {
						$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_POST_NOT_DELETED'), RESPONSE_ERROR);
						return new Jaws_Error(_t('CUSTOMPAGE_ERROR_POST_NOT_DELETED'), _t('CUSTOMPAGE_NAME'));
					}
				}
				if (!$this->DeletePage($eid['id'], true)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_PAGE_NOT_DELETED'), RESPONSE_ERROR);
					return new Jaws_Error(_t('CUSTOMPAGE_ERROR_PAGE_NOT_DELETED'), _t('CUSTOMPAGE_NAME'));
				}
			}
			$oids = $model->GetAllPostsOfPage($parent['id']);
			if (Jaws_Error::IsError($oids)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_PAGE_NOT_DELETED'), RESPONSE_ERROR);
				return new Jaws_Error(_t('CUSTOMPAGE_ERROR_PAGE_NOT_DELETED'), _t('CUSTOMPAGE_NAME'));
			}

			foreach ($oids as $oid) {
				if (!$this->DeletePost($oid['id'], true)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_POST_NOT_DELETED'), RESPONSE_ERROR);
					return new Jaws_Error(_t('CUSTOMPAGE_ERROR_POST_NOT_DELETED'), _t('CUSTOMPAGE_NAME'));
				}
			}
		
			$sql = 'DELETE FROM [[rss_hide]] WHERE [linkid] = {id}';
			$res = $GLOBALS['db']->query($sql, array('id' => (int)$id));
			if (Jaws_Error::IsError($res)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_PAGE_NOT_DELETED'), RESPONSE_ERROR);
				return new Jaws_Error(_t('CUSTOMPAGE_ERROR_PAGE_NOT_DELETED'), _t('CUSTOMPAGE_NAME'));
			}

			$sql = 'DELETE FROM [[pages_sections]] WHERE [page_id] = {id}';
			$res = $GLOBALS['db']->query($sql, array('id' => (int)$id));
			if (Jaws_Error::IsError($res)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_PAGE_NOT_DELETED'), RESPONSE_ERROR);
				return new Jaws_Error(_t('CUSTOMPAGE_ERROR_PAGE_NOT_DELETED'), _t('CUSTOMPAGE_NAME'));
			}
			
			// TODO: Shouter for deleted page_sections

			$sql = 'DELETE FROM [[pages]] WHERE [id] = {id}';
			$res = $GLOBALS['db']->query($sql, array('id' => (int)$id));
			if (Jaws_Error::IsError($res)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_PAGE_NOT_DELETED'), RESPONSE_ERROR);
				return new Jaws_Error(_t('CUSTOMPAGE_ERROR_PAGE_NOT_DELETED'), _t('CUSTOMPAGE_NAME'));
			}
			
			if (BASE_SCRIPT != 'index.php') {

				// delete menu item for page
				$url = $GLOBALS['app']->Map->GetURLFor('CustomPage', 'Page', array('id' => $parent['fast_url']));

				/*
				$sql  = 'SELECT [id] FROM [[menus]] WHERE [url] = {url}';
				$oid = $GLOBALS['db']->queryRow($sql, array('url' => $url));
				if (Jaws_Error::IsError($oid)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
					return false;
				} else {
					if (!empty($oid['id'])) {
						$menuAdmin = $GLOBALS['app']->LoadGadget('Menu', 'AdminModel');
						if (!$menuAdmin->DeleteMenu($oid['id'])) {
							return false;
						}
					}
				}
				*/
				$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
				$res = $GLOBALS['app']->Shouter->Shout('onDeleteMenuItem', $url);
				if (Jaws_Error::IsError($res) || !$res) {
					return $res;
				}

			}
		}

		if ($massive === false) {
			$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_PAGE_DELETED'), RESPONSE_NOTICE);
        }
		return true;
    }

	
    /**
     * Deletes a group of pages
     *
     * @access  public
     * @param   array   $pages  Array with the IDs of pages
     * @return  bool    Success/failure
     */
    function MassiveDelete($pages)
    {
        if (!is_array($pages)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_PAGE_NOT_MASSIVE_DELETED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('CUSTOMPAGE_ERROR_PAGE_NOT_MASSIVE_DELETED'), _t('CUSTOMPAGE_NAME'));
        }

        foreach ($pages as $page) {
            $res = $this->DeletePage((int)$page);
            if (Jaws_Error::IsError($res)) {
                $GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_PAGE_NOT_MASSIVE_DELETED'), RESPONSE_ERROR);
                return new Jaws_Error(_t('CUSTOMPAGE_ERROR_PAGE_NOT_MASSIVE_DELETED'), _t('CUSTOMPAGE_NAME'));
            }
        }
        $GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_PAGE_MASSIVE_DELETED'), RESPONSE_NOTICE);
        return true;
    }

    /**
     * Add text posts and gadgets to Pages.
     *
     * @param   int  $sort_order 	Priority order
     * @param   int  $LinkID 	The page ID this post belongs to.
     * @param   string  $title      		The title of the post.
     * @param   string  $description    	The contents of the post.
     * @param   string  $image   		An image to accompany the post or the gadget "layout action"
     * @param   int  	 $image_width     image width in pixels
     * @param   int  	 $image_height    image height in pixels
     * @param   int 	$layout  		The layout mode of the post
     * @param   string 	$active  		(Y/N) If the post is published or not
     * @param   int	 $OwnerID  		The poster's user ID
     * @param   string 	$gadget  		The gadget type of content
     * @param   string 	$url_type  		The URL type of post's image (imageviewer/internal/external)
     * @param   string 	$internal_url  		The internal URL of post's image
     * @param   string 	$external_url  		The external URL of post's image
     * @param   string 	$url_target  		The URL target of post's image (_self/_blank)
     * @param   string 	$rss_url  		RSS URL
     * @param   int 	$section_id  		Page section this post is in
     * @param   string 	$image_code  		Custom HTML code 
     * @param   datetime 	$iTime  		Publish time (defaults to now)
     * @param   string 	$checksum  		Unique ID
     * @param   boolean 	$auto       		If it's auto saved or not
     * @access  public
     * @return  mixed 	ID of new post or Jaws_Error on failure
     */
    function AddPost(
		$sort_order, $LinkID, $title, $description, $image, $image_width, $image_height, $layout, 
		$active, $OwnerID, $gadget, $url_type = 'imageviewer', $internal_url = '', $external_url = '', 
		$url_target = '_self', $rss_url = null, $section_id, $image_code = '', $iTime = null, $checksum = '', $auto = false
	) {
		$layoutHTML = $GLOBALS['app']->LoadGadget('CustomPage', 'LayoutHTML');
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$description = trim($description);
		$description = strip_tags($description, '<h1><h2><h3><h4><h5><h6><pre><del><sub><sup><u><p><a><b><em><i><strong><div><table><tbody><thead><tr><td><span><font><hr><br><img><marquee><ul><ol><li><iframe><input><select><button><textarea><form>');
		
		$OwnerID = (!is_null($OwnerID)) ? (int)$OwnerID : 0;
		
		if ($image_code != '' && !empty($image)) {
			$image = '';
			$image_width = 0;
			$image_height = 0;
			$url_type = 'imageviewer';
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
		
		$image_code = htmlspecialchars($image_code);
        $rss_url = !empty($rss_url) && !is_null($rss_url) ? $xss->parse($rss_url) : null;
        $url = !empty($url) ? $url : '';
        $url_target = !empty($url_target) ? $xss->parse($url_target) : '';

		if (
			$OwnerID == 0 && 
			$url_type == 'external' && 
			(substr(strtolower(trim($external_url)), 0, 4) == 'http') && 
			strpos(strtolower(trim($external_url)), 'javascript:') === false
		) {
			$url = $xss->parse($external_url);
		} else if ($url_type == 'internal' && !empty($internal_url) && strpos(strtolower(trim($internal_url)), 'javascript:') === false) {
			$url = $xss->parse($internal_url);
		} else if ($url_type == 'imageviewer') {
			$url = "javascript:void(0);";
		} else if ($gadget == 'text') {
	        $GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_INVALID_URL'), RESPONSE_ERROR);
            return new Jaws_Error(_t('CUSTOMPAGE_ERROR_INVALID_URL'), _t('CUSTOMPAGE_NAME'));
		}

		$sql = "
            INSERT INTO [[pages_posts]]
                ([sort_order], [linkid], [title], 
				[description], [image], [image_width], [image_height], 
				[layout], [active], [ownerid], [created], [updated], [gadget],
				[url], [url_target], [rss_url], [section_id], [image_code], [checksum])
            VALUES
                ({sort_order}, {LinkID}, {title}, 
				{description}, {image}, {image_width}, {image_height},
				{layout}, {Active}, {ownerid}, {now}, {now}, {gadget},
				{url}, {url_target}, {rss_url}, {section_id}, {image_code}, {checksum})";

		$params               		= array();
        $params['sort_order']       = (int)$sort_order;
        $params['title'] 			= $xss->parse($title);
		$params['description']   	= str_replace("\r\n", "\n", $description);
		$params['image'] 			= $xss->parse($image);
        $params['image_width'] 		= (int)$image_width;
        $params['image_height'] 	= (int)$image_height;
        $params['layout'] 			= (int)$layout;
		$params['LinkID']         	= (int)$LinkID;
		$params['ownerid']         	= $OwnerID;
        $params['Active'] 			= $xss->parse($active);
        $params['gadget'] 			= $xss->parse($gadget);
        $params['url']				= $url;
		$params['url_target']		= $url_target;
		$params['rss_url']			= $rss_url;
		$params['section_id']		= (int)$section_id;
		$params['image_code']   	= ($OwnerID == 0 ? str_replace("\r\n", "\n", $image_code) : '');
        $params['checksum'] 		= $xss->parse($checksum);
		if (is_null($iTime)) {
			$params['now']        	= $GLOBALS['db']->Date();
		} else {
			$params['now']        	= $GLOBALS['db']->Date($GLOBALS['app']->UserTime2UTC($iTime));
		}

        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('CUSTOMPAGE_ERROR_POST_NOT_ADDED'), _t('CUSTOMPAGE_NAME'));
            //return new Jaws_Error($result->GetMessage(), _t('CUSTOMPAGE_NAME'));
        }
        $newid = $GLOBALS['db']->lastInsertID('pages_posts', 'id');

		if (empty($checksum)) {
			// Update checksum
			$config_key = $GLOBALS['app']->Registry->Get('/config/key');		
			$params               	= array();
			$params['id'] 			= $newid;
			$params['checksum'] 	= $newid.':'.$config_key;
			
			$sql = '
				UPDATE [[pages_posts]] SET
					[checksum] = {checksum}
				WHERE [id] = {id}';

			$result = $GLOBALS['db']->query($sql, $params);
			if (Jaws_Error::IsError($result)) {
				$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
				return false;
			}
		}
		
		if (strtolower($gadget) == 'text') {
			$new_description = $layoutHTML->ShowPost($newid);
			$delimeterLeft = "<!-- START_post -->";
			$delimeterRight = "<!-- END_post -->";
			$startLeft = strpos($new_description, $delimeterLeft);
			$posLeft = ($startLeft+strlen($delimeterLeft));
			$posRight = strpos($new_description, $delimeterRight, $posLeft);
			$new_description = substr($new_description, $posLeft, $posRight-$posLeft);
			$new_description = str_replace(array($delimeterLeft, $delimeterRight), '', $new_description);
			if (substr(strtolower(trim($new_description)), 0, 7) == '<p></p>') {
				$new_description = substr(trim($new_description), 8, strlen($new_description));
			}
			
			// update post
			$sql = '
			UPDATE [[pages_posts]] SET
				[description] = {description},
				[title] = {title},
				[image] = {image}
			WHERE [id] = {id}
			';

			$params               		= array();
			$params['description']      = $new_description;
			$params['image']      		= '';
			$params['title']       		= '';
			$params['id']         		= $newid;
					
			$result = $GLOBALS['db']->query($sql, $params);
			if (Jaws_Error::IsError($result)) {
				return $result;
			}
		}
		
		// Let everyone know it has been added
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onAddPagePost', $newid);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		
        $GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_POST_CREATED'), RESPONSE_NOTICE);
        return $newid;
    }

    /**
     * Updates a post.
     *
     * @param   int     $id             The ID of the post to update.
     * @param   int  $sort_order 	Priority order
     * @param   string  $title      		The title of the post.
     * @param   string  $description    	The contents of the post.
     * @param   string  $image   		An image to accompany the post or the gadget "layout action"
     * @param   int  	 $image_width     image width in pixels
     * @param   int  	 $image_height    image height in pixels
     * @param   int 	$layout  		The layout mode of the post
     * @param   string 	$active  		(Y/N) If the post is published or not
     * @param   string 	$gadget  		The gadget type of content
     * @param   string 	$url_type  		The URL type of post's image (imageviewer/internal/external)
     * @param   string 	$internal_url  		The internal URL of post's image
     * @param   string 	$external_url  		The external URL of post's image
     * @param   string 	$url_target  		The URL target of post's image (_self/_blank)
     * @param   string 	$rss_url  		RSS URL
     * @param   int 	$section_id  		Page section this post is in
     * @param   string 	$image_code  		Custom HTML code 
     * @param   datetime 	$iTime  		Publish time (defaults to now)
     * @param   boolean 	$auto       		If it's auto saved or not
     * @access  public
     * @return  boolean 	Success/failure
     */
    function UpdatePost(
		$id, $sort_order, $title, $description, $image, $image_width, $image_height, $layout, 
		$active, $gadget, $url_type = 'imageviewer', $internal_url, $external_url, $url_target = '_self', 
		$rss_url, $section_id, $image_code, $iTime = null, $auto = false
	) {
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$model = $GLOBALS['app']->LoadGadget('CustomPage', 'Model');
		$layoutHTML = $GLOBALS['app']->LoadGadget('CustomPage', 'LayoutHTML');
        $page = $model->GetPost((int)$id);
        if (Jaws_Error::isError($page)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_POST_NOT_FOUND'), RESPONSE_ERROR);
            return new Jaws_Error(_t('CUSTOMPAGE_ERROR_POST_NOT_FOUND'), _t('CUSTOMPAGE_NAME'));
        }

		$description = trim($description);
		$description = strip_tags($description, '<h1><h2><h3><h4><h5><h6><pre><del><sub><sup><u><p><a><b><em><i><strong><div><table><tbody><thead><tr><td><span><font><hr><br><img><marquee><ul><ol><li><iframe><input><select><button><textarea><form>');

		if ($image_code != '' && !empty($image)) {
			$image = '';
			$image_width = 0;
			$image_height = 0;
			$url_type = 'imageviewer';
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
		
		if (
			$page['ownerid'] == 0 && 
			$url_type == 'external' && 
			substr(strtolower(trim($external_url)), 0, 4) == 'http' && 
			strpos(strtolower(trim(urldecode($external_url))), 'javascript:') === false
		) {
			$url = $xss->parse($external_url);
		} else if ($url_type == 'internal' && !empty($internal_url) && strpos(strtolower(trim(urldecode($internal_url))), 'javascript:') === false) {
			$url = $xss->parse($internal_url);
		} else {
			$url = "javascript:void(0);";
		}

		$sql = '
            UPDATE [[pages_posts]] SET
				[sort_order] = {sort_order}, 
				[title] = {title}, 
				[description] = {description}, 
				[image] = {image}, 
				[image_width] = {image_width},
				[image_height] = {image_height},
				[layout] = {layout}, 
				[active] = {Active}, 
				[updated] = {now},
				[gadget] = {gadget},
				[url] = {url},
				[url_target] = {url_target},
				[rss_url] = {rss_url},
				[section_id] = {section_id},
				[image_code] = {image_code}
			WHERE [id] = {id}';

		
		$image_code = htmlspecialchars($image_code);
        $rss_url = !empty($rss_url) ? $xss->parse($rss_url) : null;
        $url = !empty($url) ? $url : '';
        $url_target = !empty($url_target) ? $xss->parse($url_target) : '';
        $params               	= array();
        $params['id']         	= (int)$id;
        $params['sort_order'] 	= (!is_null($sort_order) ? (int)$sort_order : $page['sort_order']);
        $params['title'] 		= $xss->parse($title);
		$params['description']  = str_replace("\r\n", "\n", $description);
        $params['image'] 		= $xss->parse($image);
        $params['image_width'] 	= (int)$image_width;
        $params['image_height'] = (int)$image_height;
        $params['layout'] 		= (int)$layout;
        $params['Active'] 		= $xss->parse($active);
        $params['gadget'] 		= $xss->parse($gadget);
        $params['url']			= $url;
		$params['url_target']	= $url_target;
		$params['rss_url']		= $rss_url;
		$params['section_id']	= (!is_null($section_id) ? (int)$section_id : $page['section_id']);
		$params['image_code']   = ($page['ownerid'] == 0 ? str_replace("\r\n", "\n", $image_code) : '');
		if (is_null($iTime)) {
			$params['now']  = $GLOBALS['db']->Date();
		} else {
			$params['now']  = $GLOBALS['db']->Date($GLOBALS['app']->UserTime2UTC($iTime));
		}
		
        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('CUSTOMPAGE_ERROR_POST_NOT_UPDATED'), _t('CUSTOMPAGE_NAME'));
        }

		if (strtolower($gadget) == 'text') {
			$new_description = $layoutHTML->ShowPost((int)$id);
			$delimeterLeft = "<!-- START_post -->";
			$delimeterRight = "<!-- END_post -->";
			$startLeft = strpos($new_description, $delimeterLeft);
			$posLeft = ($startLeft+strlen($delimeterLeft));
			$posRight = strpos($new_description, $delimeterRight, $posLeft);
			$new_description = substr($new_description, $posLeft, $posRight-$posLeft);
			$new_description = str_replace(array($delimeterLeft, $delimeterRight), '', $new_description);
			if (substr(strtolower(trim($new_description)), 0, 7) == '<p></p>') {
				$new_description = substr(trim($new_description), 8, strlen($new_description));
			}
			
			// update post
			$sql = '
			UPDATE [[pages_posts]] SET
				[description] = {description},
				[title] = {title},
				[image] = {image}
			WHERE [id] = {id}
			';

			$params               		= array();
			$params['description']      = $new_description;
			$params['image']      		= '';
			$params['title']       		= '';
			$params['id']         		= $id;
					
			$result = $GLOBALS['db']->query($sql, $params);
			if (Jaws_Error::IsError($result)) {
				return $result;
			}
		}
		
		// Let everyone know
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onUpdatePagePost', $id);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		
        if ($auto) {
            $GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_POST_AUTOUPDATED',
                                                     date('H:i:s'),
                                                     (int)$id,
                                                     date('D, d')),
                                                  RESPONSE_NOTICE);
        } else {
            $GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_POST_UPDATED'), RESPONSE_NOTICE);
        }
        return true;
    }

    /**
     * Deletes a post
     *
     * @param   int     $id     The ID of the post to delete.
     * @param   boolean     $massive     Is this part of a massive delete?
     * @access  public
     * @return  bool    Success/failure
     */
    function DeletePost($id, $massive = false)
    {
		$model = $GLOBALS['app']->LoadGadget('CustomPage', 'Model');
		$parent = $model->GetPost((int)$id);
		if (Jaws_Error::IsError($parent) || !isset($parent['id'])) {
			$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_POST_NOT_DELETED'), RESPONSE_ERROR);
			return new Jaws_Error(_t('CUSTOMPAGE_ERROR_POST_NOT_DELETED'), _t('CUSTOMPAGE_NAME'));
		} else {
			// Let everyone know
			$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
			$res = $GLOBALS['app']->Shouter->Shout('onDeletePagePost', $id);
			if (Jaws_Error::IsError($res) || !$res) {
				return $res;
			}
			
			$sids = $model->GetSplashPanelsOfPage($id);
			if (Jaws_Error::IsError($sids)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_POST_NOT_DELETED'), RESPONSE_ERROR);
				return new Jaws_Error(_t('CUSTOMPAGE_ERROR_POST_NOT_DELETED'), _t('CUSTOMPAGE_NAME'));
			}

			foreach ($sids as $sid) {
				if (!$this->DeleteSplashPanel($sid['id'], $massive)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_SPLASHPANEL_NOT_DELETED'), RESPONSE_ERROR);
					return new Jaws_Error(_t('CUSTOMPAGE_ERROR_SPLASHPANEL_NOT_DELETED'), _t('CUSTOMPAGE_NAME'));
				}
			}
		
			$sql = 'DELETE FROM [[rss_hide]] WHERE [linkid] = {id}';
			$res = $GLOBALS['db']->query($sql, array('id' => $id));
			if (Jaws_Error::IsError($res)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_POST_NOT_DELETED'), RESPONSE_ERROR);
				return new Jaws_Error(_t('CUSTOMPAGE_ERROR_POST_NOT_DELETED'), _t('CUSTOMPAGE_NAME'));
			}

			$sql = 'DELETE FROM [[pages_posts]] WHERE [id] = {id}';
			$result = $GLOBALS['db']->query($sql, array('id' => $id));
			if (Jaws_Error::IsError($result)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_POST_NOT_DELETED'), RESPONSE_ERROR);
				return new Jaws_Error(_t('CUSTOMPAGE_ERROR_POST_NOT_DELETED'), _t('CUSTOMPAGE_NAME'));
			}
			//$error = new Jaws_Error(_t('CUSTOMPAGE_POST_DELETED'), _t('CUSTOMPAGE_NAME'));

			if ($massive === false) {
				$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_POST_DELETED'), RESPONSE_NOTICE);
			}
			return true;
		}
    }

    /**
     * Creates a new splash panel.
     *
     * @access  public
     * @param   integer  $sort_order 	The priority order
     * @param   integer  $LinkID 	The post ID this splash panel belongs to
     * @param   string  $image   		An image to accompany the post or the gadget "layout action"
     * @param   int  	 $splash_width     image width in pixels
     * @param   int  	 $splash_height    image height in pixels
     * @param   string  $code    	The contents of the post.
     * @param   integer 	$OwnerID  		The poster's user ID
     * @param   string 	$checksum  		Unique ID
     * @param   boolean 	$auto       		If it's auto saved or not
     * @return  ID of entered post 	    Success/failure
     */
    function AddSplashPanel(
		$sort_order, $LinkID, $image = '', $splash_width, $splash_height, $code = '', 
		$OwnerID = null, $checksum = '', $auto = false
	) {
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		if ($code != '' && $image != '') {
			$image = '';
			$image_width = 0;
			$image_height = 0;
		}
		
		//$code = strip_tags($code, '<p><a><b><em><i><strong><div><table><tbody><thead><tr><td><span><font><hr><br>');
        
		$sql = "
            INSERT INTO [[splash_panels]]
                ([sort_order], [linkid], [image], 
				[splash_width], [splash_height], [code], 
				[ownerid], [created], [updated], [checksum])
            VALUES
                ({sort_order}, {LinkID}, {image}, 
				{splash_width}, {splash_height}, {code}, 
				{ownerid}, {now}, {now}, {checksum})";

		$OwnerID = (!is_null($OwnerID)) ? (int)$OwnerID : 0;
		$code = htmlspecialchars($code);
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
        
		$params               		= array();
        $params['sort_order']       = (int)$sort_order;
		$params['LinkID']         	= (int)$LinkID;
		$params['image'] 			= $xss->parse($image);
        $params['splash_width'] 	= (int)$splash_width;
        $params['splash_height'] 	= (int)$splash_height;
		$params['code']   			= str_replace("\r\n", "\n", $code);
		$params['ownerid']         	= (int)$OwnerID;
		$params['checksum'] 		= $xss->parse($checksum);
		$params['now']        		= $GLOBALS['db']->Date();

        $result = $GLOBALS['db']->query($sql, $params);
		if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('CUSTOMPAGE_ERROR_SPLASHPANEL_NOT_ADDED'), _t('CUSTOMPAGE_NAME'));
            //return new Jaws_Error($result->GetMessage(), _t('CUSTOMPAGE_NAME'));
        }
        $newid = $GLOBALS['db']->lastInsertID('splash_panels', 'id');

		if (empty($checksum)) {
			// Update checksum
			$config_key = $GLOBALS['app']->Registry->Get('/config/key');		
			$params               	= array();
			$params['id'] 			= $newid;
			$params['checksum'] 	= $newid.':'.$config_key;
			
			$sql = '
				UPDATE [[splash_panels]] SET
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
		$res = $GLOBALS['app']->Shouter->Shout('onAddPageSplashPanel', $newid);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		
        //$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_SPLASHPANEL_CREATED'), RESPONSE_NOTICE);
        return $newid;
    }

    /**
     * Updates a splash panel.
     *
     * @param   int     $id             The ID of the post to update.
     * @param   integer  $LinkID 	The post ID this splash panel belongs to
     * @param   string  $image   		An image to accompany the post or the gadget "layout action"
     * @param   int  	 $splash_width     image width in pixels
     * @param   int  	 $splash_height    image height in pixels
     * @param   string  $code    	The contents of the post.
     * @param   boolean 	$auto       		If it's auto saved or not
     * @access  public
     * @return  boolean 	Success/failure
     */
    function UpdateSplashPanel($id, $LinkID, $image = '', $splash_width, $splash_height, $code = '', $auto = false)
	{
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$model = $GLOBALS['app']->LoadGadget('CustomPage', 'Model');
        $page = $model->GetSplashPanel($id);
        if (Jaws_Error::isError($page)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_SPLASHPANEL_NOT_FOUND'), RESPONSE_ERROR);
            return new Jaws_Error(_t('CUSTOMPAGE_ERROR_SPLASHPANEL_NOT_FOUND'), _t('CUSTOMPAGE_NAME'));
        }
		
		if ($code != '' && $image != '') {
			$image = '';
			$image_width = 0;
			$image_height = 0;
		} else if ($code == '' && $image == '') {
			return $this->DeleteSplashPanel($id);
		}
		
		$code = htmlspecialchars($code);
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
            UPDATE [[splash_panels]] SET
				[image] = {image}, 
				[splash_width] = {splash_width},
				[splash_height] = {splash_height},
				[code] = {code}, 
				[updated] = {now}
			WHERE [id] = {id}';

        $params               	= array();
        //$params['sort_order']       = (int)$sort_order;
		$params['id']         		= (int)$id;
		$params['LinkID']         	= (int)$LinkID;
		$params['image'] 			= $xss->parse($image);
        $params['splash_width'] 	= (int)$splash_width;
        $params['splash_height'] 	= (int)$splash_height;
		$params['code']   			= str_replace("\r\n", "\n", $code);
		$params['ownerid']         	= (int)$OwnerID;
        $params['now']        		= $GLOBALS['db']->Date();

        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('CUSTOMPAGE_ERROR_SPLASHPANEL_NOT_UPDATED'), _t('CUSTOMPAGE_NAME'));
            //return new Jaws_Error($result->GetMessage(), _t('CUSTOMPAGE_NAME'));
        }

		// Let everyone know
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onUpdatePageSplashPanel', $id);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		
        if ($auto) {
            $GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_SPLASHPANEL_AUTOUPDATED',
                                                     date('H:i:s'),
                                                     (int)$id,
                                                     date('D, d')),
                                                  RESPONSE_NOTICE);
        } else {
            //$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_SPLASHPANEL_UPDATED'), RESPONSE_NOTICE);
        }
        return true;
    }


    /**
     * Deletes a splash panel
     *
     * @access  public
     * @param   int     $id     The ID of the page to delete.
     * @param   boolean     $massive     Is this part of a massive delete?
     * @return  bool    Success/failure
     */
    function DeleteSplashPanel($id, $massive = false)
    {
		// Let everyone know
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onDeletePageSplashPanel', $id);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		
        $sql = 'DELETE FROM [[splash_panels]] WHERE [id] = {id}';
        $result = $GLOBALS['db']->query($sql, array('id' => $id));
        if (Jaws_Error::IsError($result)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_SPLASHPANEL_NOT_DELETED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('CUSTOMPAGE_ERROR_SPLASHPANEL_NOT_DELETED'), _t('CUSTOMPAGE_NAME'));
        }

		if ($massive === false) {
			$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_SPLASHPANEL_DELETED'), RESPONSE_NOTICE);
        }
		return true;
    }

    /**
     * Deletes a page section by Layout ID
     *
     * @access  public
     * @param   int     $id     The Layout item ID to delete.
     * @param   int     $page_id     The Page ID to delete for.
     * @param   boolean     $massive     Is this part of a massive delete?
     * @return  bool    Success/failure
     */
    function DeletePageElementByLayoutID($id, $massive = false)
    {
		$sql = 'SELECT [id] FROM [[pages_sections]] WHERE [layout_id] = {layout_id}';
		$row = $GLOBALS['db']->queryRow($sql, array('layout_id' => $id));
        if (Jaws_Error::IsError($row) || !isset($row['id']) || empty($row['id'])) {
            $GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_PAGESECTION_NOT_FOUND'), RESPONSE_ERROR);
            return new Jaws_Error(_t('CUSTOMPAGE_ERROR_PAGESECTION_NOT_FOUND'), _t('CUSTOMPAGE_NAME'));
        }
		
		// Let everyone know
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onDeletePageSection', $row['id']);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
        
        $sql = 'DELETE FROM [[pages_sections]] WHERE [id] = {id}';
        $result = $GLOBALS['db']->query($sql, array('id' => $row['id']));
        if (Jaws_Error::IsError($result)) {
            //$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_PAGESECTION_NOT_DELETED'), RESPONSE_ERROR);
            return new Jaws_Error($result->GetMessage(), _t('CUSTOMPAGE_NAME'));
        }

		if ($massive === false) {
			//$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_PAGESECTION_DELETED'), RESPONSE_NOTICE);
        }
		return true;
    }

    /**
     * Hides an RSS item
     *
     * @access  public
     * @param   int  $pid  page ID
     * @param   string  $title  title of RSS item
     * @param   string  $published  date of RSS item
     * @param   string  $url  url of RSS item
     * @return  bool    Success/failure
     */
    function HideRss($pid, $title = '', $published = '', $url = '', $elementId = null)
    {		
		$sql = "
            INSERT INTO [[rss_hide]]
                ([linkid], [title], [published], [url], [element])
            VALUES
                ({LinkID}, {title}, {published}, {url}, {elementId})";
        
		$params               		= array();
		$params['title'] 			= $title;
		$params['published'] 		= $published;
		$params['url'] 				= $url;
		$params['LinkID']         	= (int)$pid;
		$params['elementId']        = (!is_null($elementId) ? (int)$elementId : null);

        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('CUSTOMPAGE_ERROR_RSS_NOT_HIDDEN'), _t('CUSTOMPAGE_NAME'));
            //return new Jaws_Error($result->GetMessage(), _t('CUSTOMPAGE_NAME'));
        }

        $GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_RSS_HIDDEN'), RESPONSE_NOTICE);
        return true;
    }

    /**
     * Shows RSS item
     *
     * @access  public
     * @param   int  $pid  page ID
     * @param   string  $title  title of RSS item
     * @param   string  $published  date of RSS item
     * @param   string  $url  url of RSS item
     * @return  bool    Success/failure
     */
    function ShowRss($pid, $title, $published, $url)
    {
        $sql = 'DELETE FROM [[rss_hide]] WHERE ([linkid] = {LinkID} AND [title] = {title} AND [published] = {published} AND [url] = {url})';
		$params               		= array();
		$params['title'] 			= $title;
		$params['published'] 		= $published;
		$params['url'] 				= $url;
		$params['LinkID']         	= (int)$pid;
        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_RSS_NOT_SHOWN'), RESPONSE_ERROR);
            return new Jaws_Error(_t('CUSTOMPAGE_ERROR_RSS_NOT_SHOWN'), _t('CUSTOMPAGE_NAME'));
        }

        $GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_RSS_SHOWN'), RESPONSE_NOTICE);
        return true;
    }

    /**
     * Re-sorts posts
     *
     * @access  public
     * @param   int     $pids     ',' separated values of IDs of the posts
     * @param   string     $newsorts     ',' separated values of new sort_orders
     * @return  bool    Success/failure
     */
    function SortItem($pids, $newsorts)
    {
		//$model = $GLOBALS['app']->LoadGadget('CustomPage', 'Model');
        $ids = explode(',', $pids);
        $sorts = explode(',', $newsorts);
        $i = 0;
		foreach ($ids as $pid) {
			if ((int)$pid != 0) {
				$new_sort = $sorts[$i];
				//$page = $model->GetPost($pid);
		        //if (Jaws_Error::isError($page)) {
		        //    $GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_POST_NOT_SORTED'), RESPONSE_ERROR);
				//	return false;
		        //} else {
				$params               	= array();
				$params['pid']         	= (int)$pid;
				$params['new_sort'] 	= (int)$new_sort;
				
				$sql = '
					UPDATE [[pages_posts]] SET
						[sort_order] = {new_sort} 
					WHERE [id] = {pid}';

				$result1 = $GLOBALS['db']->query($sql, $params);
				if (Jaws_Error::IsError($result1)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_POST_NOT_SORTED'), RESPONSE_ERROR);
					//$GLOBALS['app']->Session->PushLastResponse($result1->GetMessage(), RESPONSE_ERROR);
					return false;
				}
				$i++;
			}
		}
		$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_POST_UPDATED'), RESPONSE_NOTICE);
		return true;
    }

    /**
     * Re-sorts posts
     *
     * @access  public
     * @param   int     $pids     ',' separated values of IDs of the posts
     * @param   string     $newsorts     ',' separated values of new sort_orders
     * @return  bool    Success/failure
     */
    function SortItemSplash($pids, $newsorts)
    {
		$model = $GLOBALS['app']->LoadGadget('CustomPage', 'Model');
        $ids = explode(',', $pids);
        $sorts = explode(',', $newsorts);
        $i = 0;
		foreach ($ids as $pid) {
			if ((int)$pid != 0) {
				$new_sort = $sorts[$i];
				$params               	= array();
				$params['pid']         	= (int)$pid;
				$params['new_sort'] 	= (int)$new_sort;
				
				$sql = 'UPDATE [[splash_panels]] SET
						[sort_order] = {new_sort} 
					WHERE ([id] = {pid})';

				$result1 = $GLOBALS['db']->query($sql, $params);
				if (Jaws_Error::IsError($result1)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_SPLASHPANEL_NOT_SORTED'), RESPONSE_ERROR);
					//$GLOBALS['app']->Session->PushLastResponse($result1->GetMessage(), RESPONSE_ERROR);
					return false;
				}
				$i++;
			}
		}
		$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_SPLASHPANEL_UPDATED'), RESPONSE_NOTICE);
		return true;
    }

    /**
     * Search for pages that matches a status and/or a keyword
     * in the title or content
     *
     * @param   string  $status  Status of page(s) we want to display
     * @param   string  $search  Keyword (title/description) of pages we want to look for
     * @param   int     $offSet  Data limit
     * @param   int     $OwnerID  Owner's ID
     * @param   string     $gadget 	Gadget
     * @param   string     $gadget_action 	Gadget page type
     * @access  public
     * @return  array   Array of matches
     */
    function SearchPages($status, $search, $offSet = null, $OwnerID = 0, $gadget = null, $gadget_action = null)
    {
        $params = array();


        $sql = '
            SELECT [id], [pid], [sm_description], [content], 
				[image], [image_width], [image_height], [logo], 
				[fast_url], [title], [show_title], 
				[description], [keywords], [pagecol], [pageconst], 
				[layout], [theme], [ownerid], [gadget], [gadget_action], [linkid], 
				[active], [created], [updated], [rss_url], [image_code], [auto_keyword], [checksum]
            FROM [[pages]]
			WHERE ([id] > 0';

        if (trim($status) != '') {
            $sql .= ' AND [active] = {status}';
			$params['status'] = $status;
        }
        $sql .= ')';
        
        if (!is_null($gadget)) {
			$sql .= ' AND ([gadget] = {gadget})';
			$params['gadget'] = $gadget;
		}
		
        if (!is_null($gadget_action)) {
			$sql .= ' AND ([gadget_action] = {gadget_action})';
			$params['gadget_action'] = $gadget_action;
		}
		
		$sql .= ' AND ([ownerid] = {ownerid})';
		$params['ownerid'] = $OwnerID;
		
		if (trim($search) != '') {
            $searchdata = explode(' ', $search);
            /**
             * This query needs more work, not use $v straight, should be
             * like rest of the param stuff.
             */
            $i = 0;
            foreach ($searchdata as $v) {
                $v = trim($v);
                $sql .= " AND ([title] LIKE {textLike_".$i."} OR [description] LIKE {textLike_".$i."} OR [fast_url] LIKE {textLike_".$i."} OR [keywords] LIKE {textLike_".$i."} OR [gadget] LIKE {textLike_".$i."})";
                $params['textLike_'.$i] = '%'.$v.'%';
                $i++;
            }
        }


        if (is_numeric($offSet)) {
            $limit = 10;
            $result = $GLOBALS['db']->setLimit(10, $offSet);
            if (Jaws_Error::IsError($result)) {
                return new Jaws_Error(_t('CUSTOMPAGE_ERROR_PAGES_NOT_RETRIEVED'), _t('CUSTOMPAGE_NAME'));
            }
        }

        $sql.= ' ORDER BY [id] ASC';

        $types = array(
			'integer', 'integer', 'text', 'text', 
			'text', 'integer', 'integer', 'text', 
			'text', 'text', 'boolean', 
			'text', 'text', 'integer', 'integer', 
			'text', 'text', 'integer', 'text', 'text', 'text', 
			'text', 'timestamp', 'timestamp', 'text', 'text', 'text', 'text'
		);

        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('CUSTOMPAGE_ERROR_PAGES_NOT_RETRIEVED'), _t('CUSTOMPAGE_NAME'));
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
    function SearchTemplates($type = 'CustomPage', $search, $offSet = null, $limit = null)
    {
		$fileBrowserModel = $GLOBALS['app']->LoadGadget('FileBrowser', 'Model');
		$root_dir = JAWS_DATA . 'themes/'.$GLOBALS['app']->_Theme.'/'.$type.'/';
		$templates = $fileBrowserModel->ReadDir('/', (is_numeric($offSet) ? (!is_null($limit) ? $limit : 10) : 0), (is_numeric($offSet) ? $offSet : 0), $root_dir);		
		if (Jaws_Error::IsError($templates)) {
			return array();
		}
		/*
		$res = array();
		foreach ($templates as $tpl) {
			// TODO: load template Info.php file
			if ($tpl['is_dir'] === true) {
				$res[] = $tpl;
			}
		}
		*/
		$result = array();
		
		if (trim($search) != '') {
            $searchdata = explode(' ', $search);
            foreach ($searchdata as $v) {
                $v = trim($v);
                foreach ($templates as $r) {
					if (strpos(strtolower($r['filename']), strtolower(trim($v))) !== false) {
						$result[] = $r;
					}
				}
            }
        } else {
			// Default layout
			if ($offSet === null || $offSet == 0) {
				$defaultData = $fileBrowserModel->GetFileProperties(JAWS_DATA .'themes/'. $GLOBALS['app']->_Theme .'/', 'layout.html', '');
				if (!Jaws_Error::IsError($defaultData)) {
					//var_dump($defaultData);
					$result[] = $defaultData;
				}
			}
			foreach ($templates as $r) {
				$result[] = $r;
			}
		}
        
		return $result;
    }

    /**
     * Edit layout's element action
     * 
     * @access  public
     * @param   int     $id   Item ID
     * @params  string  $action 	New action	
     * @return  array   Response
     */
    function EditElementAction($id, $action) 
    {
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $params           = array();
        $params['id']     = (int)$id;
        $params['action'] = $xss->parse($action);
        $sql = '
            UPDATE [[pages_posts]] SET
                [image] = {action}
            WHERE [id] = {id}';

        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
            return false;
        }
		
		// Let everyone know
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onUpdatePagePost', $id);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		
        return true;
    }

    /**
     * Get actions of a given gadget
     * 
     * @access  public
     * @param   string  $g 	Gadget 
     * @return  array   Array with the actions of the given gadget
     */
    function GetGadgetActions($g, $limit = null, $offset = null)
    { 
		$model = $GLOBALS['app']->LoadGadget('CustomPage', 'Model');
		return $model->GetGadgetActions($g, $limit, $offset);
    }

    /**
     * Saves the value of a key
     *
     * @access  public
     * @param   string  $key   Key name
     * @param   string  $value Key value
     * @return  array   Response
     */
    function SetRegistryKey($key, $value)
    {
        /*
		if (preg_match("#^/(gadgets|plugins\/parse_text)/(.*?)/(.*?)#i", $key, $matches)) {
            $GLOBALS['app']->Registry->LoadFile($matches[2]);
            $GLOBALS['app']->Registry->Set($key, $value);
            $GLOBALS['app']->Registry->Commit($matches[2]);
		}
		*/
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$value = htmlentities($value);
		$GLOBALS['app']->Registry->LoadFile('CustomPage');
		$GLOBALS['app']->Registry->Set($key, $xss->parse($value));
		$GLOBALS['app']->Registry->Commit('CustomPage');
		if ($GLOBALS['app']->Registry->Get($key) == $value) {
			$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_KEY_SAVED'), RESPONSE_NOTICE);
        } else {
			$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_KEY_NOT_SAVED'), RESPONSE_ERROR);
			return false;
		}
		return true;
    }
	
    /**
     * Saves stack direction of a page's section
     *
     * @access  public
     * @param   int  $id  section ID
     * @param   string  $stack  stack order (horizontal/vertical)
     * @return  array   Response
     */
    function UpdateStackOrder($id, $stack) 
    {
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $params           = array();
        $params['id']     = (int)$id;
        $params['stack'] = $xss->parse($stack);
        $sql = '
            UPDATE [[pages_sections]] SET
                [stack] = {stack}
            WHERE [id] = {id}';

        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_STACK_NOT_UPDATED'), RESPONSE_ERROR);
			return false;
        }
		
		// Let everyone know
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onUpdatePageSection', $id);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		
		$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_STACK_SAVED'), RESPONSE_NOTICE);
		return true;
    }	
		
    /**
     * Updates a User's Pages
     *
     * @access  public
     * @param   int  $uid  User ID
     * @return  array   Response
     */
    function UpdateUserPages($uid) 
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
				UPDATE [[pages]] SET
					[active] = {Active}
				WHERE ([ownerid] = {id}) AND ([active] = {was})';

			$result = $GLOBALS['db']->query($sql, $params);
			if (Jaws_Error::IsError($result)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_USER_PAGES_NOT_UPDATED'), RESPONSE_ERROR);
				return false;
			}
			$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_USER_PAGES_UPDATED'), RESPONSE_NOTICE);
			return true;
		} else {
			$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_USER_PAGES_NOT_UPDATED'), RESPONSE_ERROR);
			return false;
		}
    }	
		
    /**
     * Deletes a User's Pages
     *
     * @access  public
     * @param   int  $uid  User ID
     * @return  array   Response
     */
    function RemoveUserPages($uid) 
    {
		$model = $GLOBALS['app']->LoadGadget('CustomPage', 'Model');
		$pages = $model->GetCustomPageOfUserID((int)$uid);
		if (!Jaws_Error::IsError($pages)) {
			foreach ($pages as $page) {
				$result = $this->DeletePage($page['id'], true);
				if (Jaws_Error::IsError($result)) {
					/*
					$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_USER_PAGES_NOT_DELETED'), RESPONSE_ERROR);
					return false;
					*/
				}
			}
			$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_USER_PAGES_DELETED'), RESPONSE_NOTICE);
			return true;
		} else {
			$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_USER_PAGES_NOT_DELETED'), RESPONSE_ERROR);
			return false;
		}
    }	
		
    /**
     * Deletes a Group's Pages
     *
     * @access  public
     * @param   int  $gid  Group ID
     * @return  array   Response
     */
    function RemoveGroupPages($gid) 
    {
		$model = $GLOBALS['app']->LoadGadget('CustomPage', 'Model');
		$pages = $model->GetCustomPageOfGroup($gid);
		if (!Jaws_Error::IsError($pages)) {
			foreach ($pages as $page) {
				$result = $this->DeletePage($page['id'], true);
				if (Jaws_Error::IsError($result)) {
					/*
					$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_USER_PAGES_NOT_DELETED'), RESPONSE_ERROR);
					return false;
					*/
				}
				$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_USER_PAGES_DELETED'), RESPONSE_NOTICE);
				return true;
			}
		} else {
			$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_USER_PAGES_NOT_DELETED'), RESPONSE_ERROR);
			return false;
		}
    }	
		
	/**
     * Notify content has been added
     *
     * @access  public
     * @param   array	$params	(edit_url => val, gadget => val, action_performed => val)
     * @return  array	Response
     */
    function NotifyContentAdded($params) 
    {
		$GLOBALS['app']->Registry->LoadFile('CustomPage');
		$GLOBALS['app']->Translate->LoadTranslation('CustomPage', JAWS_GADGET);
		if (is_array($params)) {
			$edit_url = $params['edit_url'];
			$gadget = $params['gadget'];
			$action_performed = $params['action_performed'];
			if (!empty($edit_url) && !empty($gadget) && !empty($action_performed)) {
				$subject = $GLOBALS['app']->GetSiteURL().' - '.$gadget.' - '.$action_performed;
				// E-mail address(es) to notify?
				$notify_email  = $GLOBALS['app']->Registry->Get('/config/notify_email');
				$from_email  = $GLOBALS['app']->Registry->Get('/config/notify_from_email');
				$from_name  = $GLOBALS['app']->Registry->Get('/config/site_name');

				$message = $edit_url;
				$mail = new Jaws_Mail;
				$mail->SetFormat('text');
				$mail->SetHeaders($notify_email, $from_name, $from_email, $subject);
				$mail->AddRecipient($notify_email, false, false);
				$mail->SetBody($message, 'text');
				$mresult = $mail->send();
				
				if (Jaws_Error::IsError($mresult)) {
					return new Jaws_Error(_t('CUSTOMPAGE_ERROR_NOTIFY_ADDED'), _t('CUSTOMPAGE_NAME'));
					//return false;
				}
				return true;
			}
		}
		return new Jaws_Error(_t('CUSTOMPAGE_ERROR_NOTIFY_ADDED'), _t('CUSTOMPAGE_NAME'));
		//return false;
    }
	
	/**
     * Notify content has been deleted
     *
     * @access  public
     * @param   array	$params	(edit_url => val, gadget => val, action_performed => val)
     * @return  array	Response
     */
    function NotifyContentDeleted($params) 
    {
		return true;
	}
			
	/**
     * Update post after Layout item has been updated
     *
     * @access  public
     * @param   array 	$params 	Layout item array 
		$params = array(
			'id' => //ID,
			'section' => //SECTION,
			'new_pos' => //NEW POSITION,
			'page_gadget' => //PAGE GADGET,
			'page_action' => //PAGE ACTION,
			'page_linkid' => //PAGE LINKID
		);
     * @return  array	Response
     */
    function UpdateLayoutPost($params) 
    {
		if (
			!isset($params['id']) || empty($params['id']) || 
			!isset($params['section']) || empty($params['section']) || 
			!isset($params['page_gadget']) || empty($params['page_gadget']) || 
			!isset($params['page_action']) || empty($params['page_action']) || 
			!isset($params['page_linkid']) || empty($params['page_linkid'])
		) {
			return true;
		}
		$id = $params['id'];
		$section = $params['section'];
		$new_pos = $params['new_pos'];
		$GLOBALS['app']->Registry->LoadFile('Layout');
		$GLOBALS['app']->Translate->LoadTranslation('Layout', JAWS_GADGET);
		$layoutAdminModel = $GLOBALS['app']->LoadGadget('Layout', 'AdminModel');
		$page = $this->GetPage(null, $params['page_gadget'], $params['page_action'], $params['page_linkid']);
		if (!Jaws_Error::IsError($page) && isset($page['id']) && !empty($page['id'])) {
			$section_item = $this->GetPageElement(null, $page['id'], $id);
			if (!Jaws_Error::IsError($section_item) && isset($section_item['id']) && !empty($section_item['id'])) {
				// Update pages_sections
				$params           		= array();
				$params['id']     		= $section_item['id'];
				$params['sort_order'] 	= $new_pos;
				$params['stack'] 		= $section;
				$sql = '
					UPDATE [[pages_sections]] SET
						[sort_order] = {sort_order}, 
						[stack] = {stack}
					WHERE [id] = {id}';

				$result = $GLOBALS['db']->query($sql, $params);
				if (Jaws_Error::IsError($result)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_STACK_NOT_UPDATED'), RESPONSE_ERROR);
					return false;
				}
				// Let everyone know it has been added
				$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
				$res = $GLOBALS['app']->Shouter->Shout('onUpdatePageSection', $section_item['id']);
				if (Jaws_Error::IsError($res) || !$res) {
					return $res;
				}
				return true;
			} else {
				// Insert new pages_sections
				$sql = "
					INSERT INTO [[pages_sections]]
						([sort_order], [page_id], [layout_id], [stack], [created], [updated])
					VALUES
						({sort_order}, {page_id}, {layout_id}, {stack}, {now}, {now})";

				$params               		= array();
				$params['page_id']         	= $page['id'];
				$params['layout_id']        = $id;
				$params['stack'] 			= $section;
				$params['sort_order'] 		= $new_pos;
				$params['now']        		= $GLOBALS['db']->Date();
				
				$result = $GLOBALS['db']->query($sql, $params);
				if (Jaws_Error::IsError($result)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_STACK_NOT_ADDED'), RESPONSE_ERROR);
					return $result;
				}
				$sectionid = $GLOBALS['db']->lastInsertID('pages_sections', 'id');
				
				$config_key = $GLOBALS['app']->Registry->Get('/config/key');		
				$params2           		= array();
				$params2['id']     		= (int)$sectionid;
				$params2['checksum'] 	= $sectionid.':'.$config_key;
				$sql2 = '
					UPDATE [[pages_sections]] SET
						[checksum] = {checksum}
					WHERE [id] = {id}';

				$result2 = $GLOBALS['db']->query($sql2, $params2);
				if (Jaws_Error::IsError($result2)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_STACK_NOT_UPDATED'), RESPONSE_ERROR);
					return false;
				}
						
				// Let everyone know it has been added
				$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
				$res = $GLOBALS['app']->Shouter->Shout('onAddPageSection', $sectionid);
				if (Jaws_Error::IsError($res) || !$res) {
					return $res;
				}
				return true;
			}
		}
		return false;
	}
			
	/**
     * Remove post after Layout item has been deleted, if its only shown on single page
     *
     * @access  public
     * @param   int 	$params 	Layout item ID 
     * @return  array	Response
     */
    function RemoveLayoutPost($params) 
    {
		$id = $params;
		$GLOBALS['app']->Registry->LoadFile('Layout');
		$GLOBALS['app']->Translate->LoadTranslation('Layout', JAWS_GADGET);
		$layoutAdminModel = $GLOBALS['app']->LoadGadget('Layout', 'AdminModel');
		$element = $layoutAdminModel->GetElement($id);
		if (!Jaws_Error::IsError($element) && isset($element['id']) && !empty($element['id'])) {
			if (
				substr($element['gadget_action'], 0, 9) == 'ShowPost(' && 
				strpos($element['display_when'], ',') === false && 
				strpos($element['display_when'], 'GADGET:') !== false && 
				$element['display_when'] != '*'
			) {
				$delete = $this->DeletePost((int)str_replace(array('ShowPost(', ')'), '', $element['gadget_action']));
				if (Jaws_Error::IsError($delete)) {
					return $delete;
				} else {
					return true;
				}
				$delete = $this->DeletePageElementByLayoutID($element['id']);
				if (Jaws_Error::IsError($delete)) {
					return $delete;
				} else {
					return true;
				}
			}
		} else if (Jaws_Error::IsError($element)) {
			return $element;
		}
		return false;
	}
			
    /**
     * Inserts home page to menu
     *
     * @access  public
     * @param   string  $gadget   Gadget name (CustomPage) from onAfterEnablingGadget shouter call
     * @return  array   Response
     */
    function InsertHomePageToMenu($gadget)
    {
		if ($gadget == 'CustomPage') {
			$model = $GLOBALS['app']->LoadGadget('CustomPage', 'Model');
			$visible = 1;
			$page = $model->GetPage(1);
			if (!Jaws_Error::IsError($page)) {
				if (!isset($GLOBALS['app']->Map)) {
					require_once JAWS_PATH . 'include/Jaws/URLMapping.php';
					$GLOBALS['app']->Map = new Jaws_URLMapping();
				}
				$url = $GLOBALS['app']->Map->GetURLFor('CustomPage', 'Page', array('id' => $page['fast_url']));
							
				// get parent menus			
				$sql  = 'SELECT [id] FROM [[menus]] WHERE [url] = {url}';
				$oid = $GLOBALS['db']->queryRow($sql, array('url' => $url));
				if (Jaws_Error::IsError($oid)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
					return false;
				} else {
					if (empty($oid['id'])) {
						// Get highest rank of current menu items
						$sql = "SELECT MAX([rank]) FROM [[menus]] WHERE [gid] = 1 ORDER BY [rank] DESC";
						$rank = $GLOBALS['db']->queryOne($sql);
						if (Jaws_Error::IsError($rank)) {
							$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
							return false;
						}
						$menuAdmin = $GLOBALS['app']->LoadGadget('Menu', 'AdminModel');
						if (!$menuAdmin->InsertMenu(0, 1, 'CustomPage', $page['title'], $url, 0, (int)$rank+1, $visible, true)) {
							$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
							return false;
						}
					} else {
						$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
						return false;
					}
				}
				$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ADDED_TO_MENU'), RESPONSE_NOTICE);
				return true;
			}
			$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_NOT_ADDED_TO_MENU'), RESPONSE_ERROR);
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
		if ($gadget == 'CustomPage') {
			// Update checksum
			$config_key = $GLOBALS['app']->Registry->Get('/config/key');		
			
			$model = $GLOBALS['app']->LoadGadget('CustomPage', 'Model');
			$layoutAdminModel = $GLOBALS['app']->LoadGadget('Layout', 'AdminModel');
			$customPageLayoutHTML = $GLOBALS['app']->LoadGadget('CustomPage', 'LayoutHTML');
			$parents = $model->GetPages();
			if (Jaws_Error::IsError($parents)) {
				return false;
			}
			foreach ($parents as $parent) {
				if (empty($parent['checksum']) || is_null($parent['checksum']) || strpos($parent['checksum'], ':') === false) {
					$params               	= array();
					$params['id'] 			= $parent['id'];
					$params['checksum'] 	= $parent['id'].':'.$config_key;
					
					$sql = '
						UPDATE [[pages]] SET
							[checksum] = {checksum}
						WHERE [id] = {id}';

					$result = $GLOBALS['db']->query($sql, $params);
					if (Jaws_Error::IsError($result)) {
						$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
						return false;
					}

					// Let everyone know it has been added
					$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
					$res = $GLOBALS['app']->Shouter->Shout('onAddPage', $parent['id']);
					if (Jaws_Error::IsError($res) || !$res) {
						return $res;
					}
				}
				$posts = $model->GetAllPostsOfPage($parent['id']);
				if (Jaws_Error::IsError($posts)) {
					return false;
				}
				$pg = $parent['gadget'];
				$pa = $parent['gadget_action'].'('.$parent['linkid'].')';
				foreach ($posts as $post) {
					if (empty($post['checksum']) || is_null($post['checksum']) || strpos($post['checksum'], ':') === false) {
						$params               	= array();
						$params['id'] 			= $post['id'];
						$params['checksum'] 	= $post['id'].':'.$config_key;
						
						$sql = '
							UPDATE [[pages_posts]] SET
								[checksum] = {checksum}
							WHERE [id] = {id}';

						$result = $GLOBALS['db']->query($sql, $params);
						if (Jaws_Error::IsError($result)) {
							$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
							return false;
						}

						// Let everyone know it has been added
						$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
						$res = $GLOBALS['app']->Shouter->Shout('onAddPagePost', $post['id']);
						if (Jaws_Error::IsError($res) || !$res) {
							return $res;
						}
					}
					if (strtolower($post['gadget']) == 'text') {
						$new_description = $customPageLayoutHTML->ShowPost($post['id']);
						$delimeterLeft = "<!-- START_post -->";
						$delimeterRight = "<!-- END_post -->";
						$startLeft = strpos($new_description, $delimeterLeft);
						$posLeft = ($startLeft+strlen($delimeterLeft));
						$posRight = strpos($new_description, $delimeterRight, $posLeft);
						$new_description = substr($new_description, $posLeft, $posRight-$posLeft);
						$new_description = str_replace(array($delimeterLeft, $delimeterRight), '', $new_description);
						
						// update post
						$sql = '
						UPDATE [[pages_posts]] SET
							[description] = {description},
							[title] = {title},
							[image] = {image}
						WHERE [id] = {id}
						';

						$params               		= array();
						$params['description']      = $new_description;
						$params['image']      		= '';
						$params['title']       		= '';
						$params['id']         		= $post['id'];
								
						/*
						echo "\n\n";
						$show_sql = $sql;
						foreach ($params as $k => $v) {
							$show_sql = str_replace('{'.$k.'}', $v, $show_sql);
						}
						var_dump($show_sql);
						echo "\n\n";
						*/	
						
						$result = $GLOBALS['db']->query($sql, $params);
						if (Jaws_Error::IsError($result)) {
							return $result;
						}
						
						// Let everyone know it has been added
						$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
						$res = $GLOBALS['app']->Shouter->Shout('onUpdatePagePost', $post['id']);
						if (Jaws_Error::IsError($res) || !$res) {
							return $res;
						}
						$post_gadget = 'CustomPage';
						$post_action = 'ShowPost('.$post['id'].')';
					} else {
						$post_gadget = $post['gadget'];
						$post_action = $post['image'];
					}
					
					// Add post as new Layout element on page
					// Skip if already added to layout
					$sql2 = '
						SELECT
							[id]
						FROM [[layout]]
						WHERE ([section] = {section} AND [gadget] = {gadget} AND [gadget_action] = {action} AND [display_when] LIKE {like_dw})
					';
					$params2 = array();
					$params2['section'] = 'section'.$post['section_id'];
					$params2['gadget'] = $post_gadget; 
					$params2['action'] = $post_action;
					$params2['like_dw'] = '%{GADGET:'.$pg.'|ACTION:'.$pa.'}%';
					$params2['dw'] = '{GADGET:'.$pg.'|ACTION:'.$pa.'}';

					/*
					echo "\n\n";
					$show_sql2 = $sql2;
					foreach ($params2 as $k => $v) {
						$show_sql2 = str_replace('{'.$k.'}', $v, $show_sql2);
					}
					var_dump($show_sql2);
					echo "\n\n";
					*/
					
					$result = $GLOBALS['db']->queryAll($sql2, $params2);
					if (Jaws_Error::IsError($result)) {
						return new Jaws_Error($result->GetMessage(), _t('CUSTOMPAGE_NAME'));
					} else if (isset($result[0]['id'])) {
						continue;
					} else {
						/*
						var_dump(
							'layoutAdminModel->NewElement('.
							'section'.$post['section_id'].', '.
							$post_gadget.', '. 
							$post_action.', '. 
							$post['sort_order'].', '. 
							'{GADGET:'.$pg.'|ACTION:'.$pa.'})'
						);
						*/
						$id = $layoutAdminModel->NewElement(
							'section'.$post['section_id'], 
							$post_gadget, 
							$post_action, 
							$post['sort_order'], 
							'{GADGET:'.$pg.'|ACTION:'.$pa.'}'
						);
						if ($id === false) {
							return new Jaws_Error("Layout element not created for post ID: ".$post['id'], _t('CUSTOMPAGE_NAME'));
						}
					}
				}
				$posts = $model->GetAllSectionsOfPage($parent['id']);
				if (Jaws_Error::IsError($posts)) {
					return false;
				}
				foreach ($posts as $post) {
					if (empty($post['checksum']) || is_null($post['checksum']) || strpos($post['checksum'], ':') === false) {
						$params               	= array();
						$params['id'] 			= $post['id'];
						$params['checksum'] 	= $post['id'].':'.$config_key;
						
						$sql = '
							UPDATE [[pages_sections]] SET
								[checksum] = {checksum}
							WHERE [id] = {id}';

						$result = $GLOBALS['db']->query($sql, $params);
						if (Jaws_Error::IsError($result)) {
							$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
							return false;
						}

						// Let everyone know it has been added
						$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
						$res = $GLOBALS['app']->Shouter->Shout('onAddPageSection', $post['id']);
						if (Jaws_Error::IsError($res) || !$res) {
							return $res;
						}
					}
				}
			}
		}
		return true;
    }
	
    /**
     * Saves or updates a post
     *
     * @access  public
     * @param   int     $id             The ID of the post to update.
     * @param   string  $description    	The contents of the post.
     * @param   string  $page_gadget    	Gadget
     * @param   string  $page_action    	Gadget page type
     * @param   string  $page_linkid    	Gadget reference ID
     * @param   string  $addtype    	How are we adding this post?
     * @param   string  $method    	Method to use to save or edit
     * @param   boolean $auto       		If it's auto saved or not
     * @return  boolean Success/failure
     */
    function SaveEditPost(
		$id, $description = '', $page_gadget = null, 
		$page_action = null, $page_linkid = null, 
		$addtype = 'CustomPage', $method = 'EditPost', 
		$auto = false, $section_name = 'main'
	) {
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$GLOBALS['app']->Registry->LoadFile('Layout');
		$GLOBALS['app']->Translate->LoadTranslation('Layout', JAWS_GADGET);
		$layoutAdminModel = $GLOBALS['app']->LoadGadget('Layout', 'AdminModel');
		$model = $GLOBALS['app']->LoadGadget('CustomPage', 'Model');
		$layoutHTML = $GLOBALS['app']->LoadGadget('CustomPage', 'LayoutHTML');
		switch ($addtype) {
			case 'CustomPage':
				$cleaned_description = trim($description);
				// remove multiple chained links
				for ($i = 0; $i < substr_count($cleaned_description, '<a'); $i++) {
					$delimeterLeft = "<a";
					$delimeterRight = ">";
					$inputStr = $cleaned_description;
					$startLeft = strpos($inputStr, $delimeterLeft);
					$posLeft = ($startLeft+strlen($delimeterLeft));
					$posRight = strpos($inputStr, $delimeterRight, $posLeft);
					$inputStr = '<a'.substr($inputStr, $posLeft, $posRight-$posLeft).'>';
					$cleaned_description = str_replace($inputStr.'</a>', '', $cleaned_description);
					if (strpos($cleaned_description, $inputStr.$inputStr) !== false) {
						$cleaned_description = str_replace($inputStr.$inputStr, $inputStr, $cleaned_description);
					}
				}
				$cleaned_description = str_replace('</a></a>', '</a>', $cleaned_description);
				if ($cleaned_description == '<button class="temp-post-button">Add Post To This Section</button>') {
					return $cleaned_description;
				}
				switch ($method) {
					case 'AddPost':
						if (empty($cleaned_description)) {
							//$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_POST_NOT_UPDATED'), RESPONSE_ERROR);
							return new Jaws_Error(_t('CUSTOMPAGE_ERROR_POST_NOT_UPDATED'), _t('CUSTOMPAGE_NAME'));
						}
						// Save post?
						if (is_numeric($id)) {
							$page = $model->GetPost((int)$id);
							if (Jaws_Error::isError($page) || !isset($page['id'])) {
								//$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_POST_NOT_FOUND'), RESPONSE_ERROR);
								return new Jaws_Error(_t('CUSTOMPAGE_ERROR_POST_NOT_FOUND'), _t('CUSTOMPAGE_NAME'));
							} else {
								$result = $id.'';
							}
						} else {
							$result = $this->AddPost(
								0, (is_null($page_linkid) ? 0 : $page_linkid), '', $cleaned_description, '', 0, 0, 0, 'Y', 0, 'text'
							);
						}
						if (Jaws_Error::IsError($result) || !is_numeric($result)) {
							//$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_POST_NOT_ADDED'), RESPONSE_ERROR);
							return new Jaws_Error(_t('CUSTOMPAGE_ERROR_POST_NOT_ADDED'), _t('CUSTOMPAGE_NAME'));
						} else {
							// save new layout element
							$displayWhen = '*';
							if (!is_null($page_gadget)) {
								$displayWhen = '{GADGET:'.$page_gadget.'}';
								if (!is_null($page_action)) {
									$displayWhen = '{GADGET:'.$page_gadget.'|ACTION:'.$page_action.'}';
									if (!is_null($page_linkid)) {
										$displayWhen = '{GADGET:'.$page_gadget.'|ACTION:'.$page_action.'('.$page_linkid.')}';
									}
								}
							}
							$res = $layoutAdminModel->NewElement($section_name, 'CustomPage', 'ShowPost('.$result.')', '', $displayWhen);
							if (Jaws_Error::IsError($res)) {
								//$GLOBALS['app']->Session->PushLastResponse($res->GetMessage(), RESPONSE_ERROR);
								return new Jaws_Error($res->GetMessage(), _t('CUSTOMPAGE_NAME'));
							}
							$id = $result;
						}
						break;
					case 'EditPost':
						// Delete it if empty
						if (empty($cleaned_description)) {
						}
						$description = strip_tags($cleaned_description, '<h1><h2><h3><h4><h5><h6><pre><del><sub><sup><u><p><a><b><em><i><strong><div><table><tbody><thead><tr><td><span><font><hr><br><img><marquee><ul><ol><li><iframe><input><select><button><textarea><form>');
						$page = $model->GetPost((int)$id);
						if (Jaws_Error::isError($page)) {
							//$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_POST_NOT_FOUND'), RESPONSE_ERROR);
							return new Jaws_Error(_t('CUSTOMPAGE_ERROR_POST_NOT_FOUND'), _t('CUSTOMPAGE_NAME'));
						}

						$sql = '
							UPDATE [[pages_posts]] SET
								[description] = {description}, 
								[active] = {active}, 
								[updated] = {now}
							WHERE [id] = {id}';

						$params               	= array();
						$params['id']         	= (int)$id;
						$params['active']       = 'Y';
						$params['description']  = str_replace("\r\n", "\n", $description);
						$params['now']  		= $GLOBALS['db']->Date();
						
						$result = $GLOBALS['db']->query($sql, $params);
						if (Jaws_Error::IsError($result)) {
							//$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_POST_NOT_UPDATED'), RESPONSE_ERROR);
							return new Jaws_Error(_t('CUSTOMPAGE_ERROR_POST_NOT_UPDATED'), _t('CUSTOMPAGE_NAME'));
						}
						
						// Update Layout item to remove temp status
						$sql = '
							SELECT
								[id], [display_when]
							FROM [[layout]]
							WHERE [gadget] = {gadget} AND [gadget_action] = {gadget_action} AND [display_when] LIKE {like_dw}
						';
						$params = array();
						$params['gadget'] = 'CustomPage'; 
						$params['gadget_action'] = 'ShowPost('.$id.')'; 
						$params['like_dw'] = '%{TEMPGADGET:%';
						if (!is_null($page_gadget)) {
							$params['like_dw'] = '%{TEMPGADGET:'.$page_gadget.'}%';
							if (!is_null($page_action)) {
								$params['like_dw'] = '%{TEMPGADGET:'.$page_gadget.'|ACTION:'.$page_action.'}%';
								if (!is_null($page_linkid)) {
									$params['like_dw'] = '%{TEMPGADGET:'.$page_gadget.'|ACTION:'.$page_action.'('.$page_linkid.')}%';
								}
							}
						}
						
						$result = $GLOBALS['db']->queryAll($sql, $params);
						if (Jaws_Error::IsError($result)) {
							return new Jaws_Error($result->GetMessage(), _t('CUSTOMPAGE_NAME'));
						} else if (isset($result[0]) && isset($result[0]['id'])) {
							$res = $layoutAdminModel->ChangeDisplayWhen($result[0]['id'], str_replace('TEMPGADGET', 'GADGET', $result[0]['display_when']));
							if ($res === false) {
								//$GLOBALS['app']->Session->PushLastResponse($res->GetMessage(), RESPONSE_ERROR);
								return new Jaws_Error(_t('CUSTOMPAGE_ERROR_POST_NOT_UPDATED'), _t('CUSTOMPAGE_NAME'));
							}
						}
						break;
				}
				$new_description = $layoutHTML->ShowPost((int)$id);
				$delimeterLeft = "<!-- START_post -->";
				$delimeterRight = "<!-- END_post -->";
				$startLeft = strpos($new_description, $delimeterLeft);
				$posLeft = ($startLeft+strlen($delimeterLeft));
				$posRight = strpos($new_description, $delimeterRight, $posLeft);
				$new_description = substr($new_description, $posLeft, $posRight-$posLeft);
				$new_description = str_replace(array($delimeterLeft, $delimeterRight), '', $new_description);
								
				// update post
				$sql = '
				UPDATE [[pages_posts]] SET
					[description] = {description},
					[title] = {title},
					[image] = {image}
				WHERE [id] = {id}
				';

				$params               		= array();
				$params['description']   	= str_replace(array("\r","\n"), '', $new_description);
				$params['image']      		= '';
				$params['title']       		= '';
				$params['id']         		= (int)$id;
						
				$result = $GLOBALS['db']->query($sql, $params);
				if (Jaws_Error::IsError($result)) {
					return $result;
				}
								
				// Let everyone know
				$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
				$res = $GLOBALS['app']->Shouter->Shout('onUpdatePagePost', $id);
				if (Jaws_Error::IsError($res) || !$res) {
					if (Jaws_Error::IsError($res)) {
						//$GLOBALS['app']->Session->PushLastResponse($res->GetMessage(), RESPONSE_ERROR);
						return $res;
					} else {
						//$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_POST_NOT_UPDATED'), RESPONSE_ERROR);
						return new Jaws_Error(_t('CUSTOMPAGE_ERROR_POST_NOT_UPDATED'), _t('CUSTOMPAGE_NAME'));
					}
				}
				
				/*
				if ($auto) {
					$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_POST_AUTOUPDATED',
															 date('H:i:s'),
															 (int)$id,
															 date('D, d')),
														  RESPONSE_NOTICE);
				} else {
					$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_POST_UPDATED'), RESPONSE_NOTICE);
				}
				*/
				$post = $model->GetPost($id);
				if (Jaws_Error::IsError($post) || !isset($post['description'])) {
					return new Jaws_Error(_t('CUSTOMPAGE_ERROR_POST_NOT_UPDATED'), _t('CUSTOMPAGE_NAME'));	
				} else {
					$GLOBALS['app']->Session->PopLastResponse();
					$new_description = $layoutHTML->ShowPost($post['id']);
					$delimeterLeft = "<!-- START_post -->";
					$delimeterRight = "<!-- END_post -->";
					$startLeft = strpos($new_description, $delimeterLeft);
					$posLeft = ($startLeft+strlen($delimeterLeft));
					$posRight = strpos($new_description, $delimeterRight, $posLeft);
					$new_description = substr($new_description, $posLeft, $posRight-$posLeft);
					$new_description = str_replace(array($delimeterLeft, $delimeterRight), '', $new_description);
					return $new_description;
				}
				break;
		}
		
		return new Jaws_Error(_t('CUSTOMPAGE_ERROR_POST_NOT_UPDATED'), _t('CUSTOMPAGE_NAME'));
    }
	
    /**
     * Creates a temp post
     *
     * @access  public
     * @param   int     $id             The ID of the post to update.
     * @param   string  $description    	The contents of the post.
     * @param   string  $page_gadget    	Gadget
     * @param   string  $page_action    	Gadget page type
     * @param   string  $page_linkid    	Gadget reference ID
     * @param   string  $addtype    	How are we adding this post?
     * @param   string  $method    	Method to use to save or edit
     * @param   boolean $auto       		If it's auto saved or not
     * @return  boolean Success/failure
     */
    function SaveTempPost(
		$description = '', $page_gadget = null, 
		$page_action = null, $page_linkid = null, 
		$addtype = 'CustomPage', $auto = false, 
		$section_name = 'main'
	) {
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$GLOBALS['app']->Registry->LoadFile('Layout');
		$GLOBALS['app']->Translate->LoadTranslation('Layout', JAWS_GADGET);
		$layoutAdminModel = $GLOBALS['app']->LoadGadget('Layout', 'AdminModel');
		$model = $GLOBALS['app']->LoadGadget('CustomPage', 'Model');
		$layoutHTML = $GLOBALS['app']->LoadGadget('CustomPage', 'LayoutHTML');
		switch ($addtype) {
			case 'CustomPage':
				$cleaned_description = trim($description);
				// remove multiple chained links
				for ($i = 0; $i < substr_count($cleaned_description, '<a'); $i++) {
					$delimeterLeft = "<a";
					$delimeterRight = ">";
					$inputStr = $cleaned_description;
					$startLeft = strpos($inputStr, $delimeterLeft);
					$posLeft = ($startLeft+strlen($delimeterLeft));
					$posRight = strpos($inputStr, $delimeterRight, $posLeft);
					$inputStr = '<a'.substr($inputStr, $posLeft, $posRight-$posLeft).'>';
					$cleaned_description = str_replace($inputStr.'</a>', '', $cleaned_description);
					if (strpos($cleaned_description, $inputStr.$inputStr) !== false) {
						$cleaned_description = str_replace($inputStr.$inputStr, $inputStr, $cleaned_description);
					}
				}
				$cleaned_description = str_replace('</a></a>', '</a>', $cleaned_description);
				if (empty($cleaned_description)) {
					//$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_POST_NOT_UPDATED'), RESPONSE_ERROR);
					return new Jaws_Error(_t('CUSTOMPAGE_ERROR_POST_NOT_UPDATED'), _t('CUSTOMPAGE_NAME'));
				}
				// Save post
				$result = $this->AddPost(
					0, (is_null($page_linkid) ? 0 : $page_linkid), '', $cleaned_description, '', 0, 0, 0, 'T', 0, 'text'
				);
				if (Jaws_Error::IsError($result) || !is_numeric($result)) {
					//$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_POST_NOT_ADDED'), RESPONSE_ERROR);
					return new Jaws_Error(_t('CUSTOMPAGE_ERROR_POST_NOT_ADDED'), _t('CUSTOMPAGE_NAME'));
				} else {
					// save new layout element
					if (!is_null($page_gadget)) {
						$displayWhen = '{TEMPGADGET:'.$page_gadget.'}';
						if (!is_null($page_action)) {
							$displayWhen = '{TEMPGADGET:'.$page_gadget.'|ACTION:'.$page_action.'}';
							if (!is_null($page_linkid)) {
								$displayWhen = '{TEMPGADGET:'.$page_gadget.'|ACTION:'.$page_action.'('.$page_linkid.')}';
							}
						}
						$res = $layoutAdminModel->NewElement($section_name, 'CustomPage', 'ShowPost('.$result.')', '', $displayWhen);
						if (Jaws_Error::IsError($res)) {
							//$GLOBALS['app']->Session->PushLastResponse($res->GetMessage(), RESPONSE_ERROR);
							return new Jaws_Error($res->GetMessage(), _t('CUSTOMPAGE_NAME'));
						}
						$id = $result;
					}
				}
				$new_description = $layoutHTML->ShowPost((int)$id);
				$delimeterLeft = "<!-- START_post -->";
				$delimeterRight = "<!-- END_post -->";
				$startLeft = strpos($new_description, $delimeterLeft);
				$posLeft = ($startLeft+strlen($delimeterLeft));
				$posRight = strpos($new_description, $delimeterRight, $posLeft);
				$new_description = substr($new_description, $posLeft, $posRight-$posLeft);
				$new_description = str_replace(array($delimeterLeft, $delimeterRight), '', $new_description);
								
				// update post
				$sql = '
				UPDATE [[pages_posts]] SET
					[description] = {description},
					[title] = {title},
					[image] = {image}
				WHERE [id] = {id}
				';

				$params               		= array();
				$params['description']   	= str_replace(array("\r","\n"), '', $new_description);
				$params['image']      		= '';
				$params['title']       		= '';
				$params['id']         		= (int)$id;
						
				$result = $GLOBALS['db']->query($sql, $params);
				if (Jaws_Error::IsError($result)) {
					return $result;
				}
								
				// Let everyone know
				$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
				$res = $GLOBALS['app']->Shouter->Shout('onUpdatePagePost', $id);
				if (Jaws_Error::IsError($res) || !$res) {
					if (Jaws_Error::IsError($res)) {
						//$GLOBALS['app']->Session->PushLastResponse($res->GetMessage(), RESPONSE_ERROR);
						return $res;
					} else {
						//$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_POST_NOT_UPDATED'), RESPONSE_ERROR);
						return new Jaws_Error(_t('CUSTOMPAGE_ERROR_POST_NOT_UPDATED'), _t('CUSTOMPAGE_NAME'));
					}
				}
				
				/*
				if ($auto) {
					$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_POST_AUTOUPDATED',
															 date('H:i:s'),
															 (int)$id,
															 date('D, d')),
														  RESPONSE_NOTICE);
				} else {
					$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_POST_UPDATED'), RESPONSE_NOTICE);
				}
				*/
				return $id;
		}
		
		return new Jaws_Error(_t('CUSTOMPAGE_ERROR_POST_NOT_UPDATED'), _t('CUSTOMPAGE_NAME'));
    }
	
}