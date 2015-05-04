<?php
/**
 * FlashGallery Gadget
 *
 * @category   GadgetModel
 * @package    FlashGallery
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */

require_once JAWS_PATH . 'gadgets/FlashGallery/Model.php';
class FlashGalleryAdminModel extends FlashGalleryModel
{
    var $_Name = 'FlashGallery';
	
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
        $GLOBALS['app']->Shouter->NewShouter('FlashGallery', 'onAddFlashGallery');   		// trigger an action when we add a parent
        $GLOBALS['app']->Shouter->NewShouter('FlashGallery', 'onDeleteFlashGallery');		// trigger an action when we delete a parent
        $GLOBALS['app']->Shouter->NewShouter('FlashGallery', 'onUpdateFlashGallery');		// and when we update a parent..
        $GLOBALS['app']->Shouter->NewShouter('FlashGallery', 'onAddFlashGalleryPost');   	// trigger an action when we add a post
        $GLOBALS['app']->Shouter->NewShouter('FlashGallery', 'onDeleteFlashGalleryPost');	// trigger an action when we delete a post
        $GLOBALS['app']->Shouter->NewShouter('FlashGallery', 'onUpdateFlashGalleryPost');	// and when we update a post..

		$GLOBALS['app']->loadClass('Listener', 'Jaws_EventListener');
        $GLOBALS['app']->Listener->NewListener('FlashGallery', 'onDeleteUser', 'RemoveUserGalleries');
        $GLOBALS['app']->Listener->NewListener('FlashGallery', 'onUpdateUser', 'UpdateUserGalleries');
		$GLOBALS['app']->Listener->NewListener('FlashGallery', 'onAfterEnablingGadget', 'InsertDefaultChecksums');

		if (!in_array('FlashGallery', explode(',', $GLOBALS['app']->Registry->Get('/gadgets/plain_editor_items')))) {
			if ($GLOBALS['app']->Registry->Get('/gadgets/plain_editor_items') == '') {
				$GLOBALS['app']->Registry->Set('/gadgets/plain_editor_items', 'FlashGallery');
			} else {
				$GLOBALS['app']->Registry->Set('/gadgets/plain_editor_items', $GLOBALS['app']->Registry->Get('/gadgets/plain_editor_items').',FlashGallery');
			}
		}
		/*
		if (!in_array('FlashGallery', explode(',', $GLOBALS['app']->Registry->Get('/gadgets/user_access_items')))) {
			if ($GLOBALS['app']->Registry->Get('/gadgets/user_access_items') == '') {
				$GLOBALS['app']->Registry->Set('/gadgets/user_access_items', 'FlashGallery');
			} else {
				$GLOBALS['app']->Registry->Set('/gadgets/user_access_items', $GLOBALS['app']->Registry->Get('/gadgets/user_access_items').',FlashGallery');
			}
		}
		*/
		
		//Create Jaws_User groups
        require_once JAWS_PATH . 'include/Jaws/User.php';
        $userModel = new Jaws_User;
        $userModel->addGroup('flashgallery_owners', false); //Don't check if it returns true or false
        $group = $userModel->GetGroupInfoByName('flashgallery_owners');
		if (isset($group['id']) && !empty($group['id'])) {
			$GLOBALS['app']->ACL->NewKey('/ACL/groups/'.$group['id'].'/gadgets/FlashGallery/OwnFlashGallery', 'true');
        }
		//$userModel->addGroup('flashgallery_users', false); //Don't check if it returns true or false

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
        $tables = array('flashgalleries',
                        'flashgalleries_posts');
        foreach ($tables as $table) {
            $result = $GLOBALS['db']->dropTable($table);
            if (Jaws_Error::IsError($result)) {
                $gName  = _t('FLASHGALLERY_NAME');
                $errMsg = _t('GLOBAL_ERROR_GADGET_NOT_UNINSTALLED', $gName);
                $GLOBALS['app']->Session->PushLastResponse($errMsg, RESPONSE_ERROR);
                return new Jaws_Error($errMsg, $gName);
            }
        }

        // Events
        $GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
        $GLOBALS['app']->Shouter->DeleteShouter('FlashGallery', 'onAddFlashGallery');   		// trigger an action when we add a parent
        $GLOBALS['app']->Shouter->DeleteShouter('FlashGallery', 'onDeleteFlashGallery');		// trigger an action when we delete a parent
        $GLOBALS['app']->Shouter->DeleteShouter('FlashGallery', 'onUpdateFlashGallery');		// and when we update a parent..
        $GLOBALS['app']->Shouter->DeleteShouter('FlashGallery', 'onAddFlashGalleryPost');   	// trigger an action when we add a post
        $GLOBALS['app']->Shouter->DeleteShouter('FlashGallery', 'onDeleteFlashGalleryPost');	// trigger an action when we delete a post
        $GLOBALS['app']->Shouter->DeleteShouter('FlashGallery', 'onUpdateFlashGalleryPost');	// and when we update a post..

		$GLOBALS['app']->loadClass('Listener', 'Jaws_EventListener');
        $GLOBALS['app']->Listener->DeleteListener('FlashGallery', 'RemoveUserGalleries');
        $GLOBALS['app']->Listener->DeleteListener('FlashGallery', 'UpdateUserGalleries');
		$GLOBALS['app']->Listener->DeleteListener('FlashGallery', 'InsertDefaultChecksums');

		if (in_array('FlashGallery', explode(',', $GLOBALS['app']->Registry->Get('/gadgets/plain_editor_items')))) {
			if ($GLOBALS['app']->Registry->Get('/gadgets/plain_editor_items') == 'FlashGallery') {
				$GLOBALS['app']->Registry->Set('/gadgets/plain_editor_items', '');
			} else {
				$GLOBALS['app']->Registry->Set('/gadgets/plain_editor_items', str_replace(',FlashGallery', '', $GLOBALS['app']->Registry->Get('/gadgets/plain_editor_items')));
			}
		}
		/*
		if (in_array('FlashGallery', explode(',', $GLOBALS['app']->Registry->Get('/gadgets/user_access_items')))) {
			if ($GLOBALS['app']->Registry->Get('/gadgets/user_access_items') == 'FlashGallery') {
				$GLOBALS['app']->Registry->Set('/gadgets/user_access_items', '');
			} else {
				$GLOBALS['app']->Registry->Set('/gadgets/user_access_items', str_replace(',FlashGallery', '', $GLOBALS['app']->Registry->Get('/gadgets/user_access_items')));
			}
		}
		*/
		
		//Delete Jaws_User groups
        require_once JAWS_PATH . 'include/Jaws/User.php';
        $userModel = new Jaws_User;
        $group = $userModel->GetGroupInfoByName('flashgallery_owners');
		if (isset($group['id']) && !empty($group['id'])) {
			$userModel->DeleteGroup($group['id']);
			$GLOBALS['app']->ACL->DeleteKey('/ACL/groups/'.$group['id'].'/gadgets/FlashGallery/OwnFlashGallery');
		}
        /*
		$group = $userModel->GetGroupInfoByName('flashgallery_users');
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
     * Creates a new page.
     *
     * @access  public
     * @param   string  $type			'gallery', 'slideshow', 'featured', or 'rss'
     * @param   string  $url			link to an xml or rss feed to display
     * @param   string  $title			title of the gallery
     * @param   string  $aspect_ratio	'16:9', '4:3', '1:1'
     * @param   string  $width			'auto' or 'custom'
     * @param   int  	 $custom_width	width (in pixels), if 'custom'
     * @param   int  	 $timer			time each frame is displayed for
     * @param   int  	 $fadetime		time it takes for a fade transition to occur
     * @param   int  	 $columns		number of columns to show if type is 'gallery'
     * @param   string  $order			'random' or 'sequential'
     * @param   string  $show_text		(Y/N) show text labels
     * @param   string  $text_pos		"top", "top left", "top right", "left", "center", "right", "bottom", "bottom left", "bottom right", "random"
     * @param   string  $lock_label 		(Y/N) lock labels into 'text_pos'
     * @param   string  $textbar		'solid' color or 'plastic' for image background on the text background label
     * @param   int  	 $textbar_height 	height (in pixels) of the textbar 
     * @param   int  	 $textbar_alpha	alpha transparency (0-100) of textbar
     * @param   string  $show_buttons	(Y/N) show the next/previous buttons
     * @param   string  $button_pos		'top', 'middle', 'bottom'
     * @param   string  $overlay_image	image that will be loaded on top of Flash object (typically for a border mask)
     * @param   string  $allow_fullscreen	(Y/N) allow fullscreen mode
     * @param   string  $text_move		'none', 'up', 'down', 'left', 'right' movement of text label
     * @param   string  $image_move		(Y/N) pan the images left and right alternatively
     * @param   int  	 $image_offsetx	image X coordinate offset to accommodate 'overlay_image'
     * @param   int  	 $image_offsety	Y coordinate offset
     * @param   string  $load_immediately	(Y/N) load first item immediately instead of waiting for initial timer length
     * @param   string  $OwnerID
     * @param   string  $active         (Y/N) If the gallery is published or not
     * @return  bool    Success/failure
     */
    function AddFlashGallery($type, $url, $title, $aspect_ratio, $width, $custom_width = 0, $timer, $fadetime, 
		$columns = 6, $order, $show_text, $text_pos, $lock_label, $textbar, $textbar_height, 
		$textbar_alpha, $show_buttons, $button_pos, $overlay_image, $allow_fullscreen, 
		$text_move, $image_move, $image_offsetx = 0, $image_offsety = 0, $load_immediately, 
		$background_color, $looping, $textbar_color, $background_image, $OwnerID, $active, $height, $custom_height = 0, 
		$watermark_image, $checksum = '')
    {
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		if ((($type == 'rss' || $type == 'xml') && empty($url)) || (($type == 'rss' || $type == 'xml') && strpos($url, '://') === false)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('FLASHGALLERY_ERROR_INVALID_URL'), RESPONSE_ERROR);
			require_once JAWS_PATH . 'include/Jaws/Header.php';
			if (BASE_SCRIPT != 'index.php') {
				Jaws_Header::Location(BASE_SCRIPT . '?gadget=FlashGallery&action=form');
			} else {
				Jaws_Header::Location('index.php?gadget=FlashGallery&action=account_form');
			}
		}
		if (
			(isset($background_image) && !empty($background_image)) || 
			(isset($overlay_image) && !empty($overlay_image)) || 
			(isset($watermark_image) && !empty($watermark_image))
		) {
			$background_image = $this->cleanImagePath($background_image);
			$overlay_image = $this->cleanImagePath($overlay_image);
			$watermark_image = $this->cleanImagePath($watermark_image);			
			if (
				substr(strtolower(trim($background_image)), 0, 4) == 'http' || 
				substr(strtolower(trim($background_image)), 0, 2) == '//' || 
				substr(strtolower(trim($background_image)), 0, 2) == '\\\\' || 
				substr(strtolower(trim($overlay_image)), 0, 4) == 'http' || 
				substr(strtolower(trim($overlay_image)), 0, 2) == '//' || 
				substr(strtolower(trim($overlay_image)), 0, 2) == '\\\\' || 
				substr(strtolower(trim($watermark_image)), 0, 4) == 'http' || 
				substr(strtolower(trim($watermark_image)), 0, 2) == '//' || 
				substr(strtolower(trim($watermark_image)), 0, 2) == '\\\\'
			) {
				$GLOBALS['app']->Session->PushLastResponse(_t('FLASHGALLERY_ERROR_INVALID_IMAGE'), RESPONSE_ERROR);
				require_once JAWS_PATH . 'include/Jaws/Header.php';
				if (BASE_SCRIPT != 'index.php') {
					Jaws_Header::Location(BASE_SCRIPT . '?gadget=FlashGallery&action=form');
				} else {
					Jaws_Header::Location('index.php?gadget=FlashGallery&action=account_form');
				}
			}
		}
        
		$sql = "
            INSERT INTO [[flashgalleries]]
                ([type], [url], [title], [aspect_ratio], [width],
				[custom_width], [timer], [fadetime], [columns], 
				[order], [show_text], [text_pos], [lock_label], [textbar], 
				[textbar_height], [textbar_alpha], [show_buttons], [button_pos], 
				[overlay_image], [allow_fullscreen], [text_move], [image_move], 
				[image_offsetx], [image_offsety], [load_immediately], [background_color], 
				[looping], [textbar_color], [background_image], [ownerid], [active], [created], 
				[updated], [height], [custom_height], [watermark_image], [checksum])
            VALUES
                ({type}, {url}, {title}, {aspect_ratio}, {width},
				{custom_width}, {timer}, {fadetime}, {columns}, 
				{order}, {show_text}, {text_pos}, {lock_label}, {textbar}, 
				{textbar_height}, {textbar_alpha}, {show_buttons}, {button_pos}, 
				{overlay_image}, {allow_fullscreen}, {text_move}, {image_move}, 
				{image_offsetx}, {image_offsety}, {load_immediately}, {background_color}, 
				{looping}, {textbar_color}, {background_image}, {OwnerID}, {Active}, {now}, 
				{now}, {height}, {custom_height}, {watermark_image}, {checksum})";
		
		$OwnerID = (!is_null($OwnerID)) ? (int)$OwnerID : 0;
        $params               			= array();
        $params['type']         		= $type;
        $params['url'] 					= $url;
        $params['title'] 				= $title;
        $params['aspect_ratio'] 		= $aspect_ratio;
        $params['width']   				= $width;
        $params['custom_width'] 		= (int)$custom_width;
        $params['height']   			= $height;
        $params['custom_height'] 		= (int)$custom_height;
        $params['timer'] 				= (int)$timer;
        $params['fadetime'] 			= (int)$fadetime;
        $params['columns'] 				= (int)$columns;
        $params['order'] 				= $order;
        $params['show_text']        	= $show_text;
        $params['text_pos']        		= $text_pos;
        $params['lock_label']       	= $lock_label;
        $params['textbar']         		= $textbar;
        $params['textbar_height'] 		= (int)$textbar_height;
        $params['textbar_alpha'] 		= (int)$textbar_alpha;
        $params['show_buttons'] 		= $show_buttons;
        $params['button_pos'] 			= $button_pos;
        $params['overlay_image']   		= $overlay_image;
        $params['allow_fullscreen'] 	= $allow_fullscreen;
        $params['text_move'] 			= $text_move;
        $params['image_move'] 			= $image_move;
        $params['image_offsetx'] 		= (int)$image_offsetx;
        $params['image_offsety'] 		= (int)$image_offsety;
        $params['load_immediately'] 	= $load_immediately;
        $params['background_color'] 	= $background_color;
        $params['looping'] 				= $looping;
        $params['textbar_color'] 		= $textbar_color;
        $params['background_image'] 	= $background_image;
        $params['watermark_image'] 		= $watermark_image;
		$params['OwnerID']         		= $OwnerID;
        $params['Active'] 				= $active;
        $params['checksum'] 			= $checksum;
        $params['now']        			= $GLOBALS['db']->Date();
		
		$result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('FLASHGALLERY_ERROR_GALLERY_NOT_ADDED'), RESPONSE_ERROR);
            return $result;
        }
        $newid = $GLOBALS['db']->lastInsertID('flashgalleries', 'id');

		if (empty($checksum)) {
			// Update checksum
			$config_key = $GLOBALS['app']->Registry->Get('/config/key');		
			$params               	= array();
			$params['id'] 			= $newid;
			$params['checksum'] 	= $newid.':'.$config_key;
			
			$sql = '
				UPDATE [[flashgalleries]] SET
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
		$res = $GLOBALS['app']->Shouter->Shout('onAddFlashGallery', $newid);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		
        $GLOBALS['app']->Session->PushLastResponse(_t('FLASHGALLERY_GALLERY_CREATED'), RESPONSE_NOTICE);
        return $newid;
    }

    /**
     * Updates a gallery.
     *
     * @access  public
     * @param   int     $id             The ID of the gallery to update.
     * @param   string  $type			'gallery', 'slideshow', 'featured', or 'rss'
     * @param   string  $url			link to an xml or rss feed to display
     * @param   string  $title			title of the gallery
     * @param   string  $aspect_ratio	'16:9', '4:3', '1:1'
     * @param   string  $width			'auto' or 'custom'
     * @param   int  	 $custom_width	width (in pixels), if 'custom'
     * @param   int  	 $timer			time each frame is displayed for
     * @param   int  	 $fadetime		time it takes for a fade transition to occur
     * @param   int  	 $columns		number of columns to show if type is 'gallery'
     * @param   string  $order			'random' or 'sequential'
     * @param   string  $show_text		(Y/N) show text labels
     * @param   string  $text_pos		"top", "top left", "top right", "left", "center", "right", "bottom", "bottom left", "bottom right", "random"
     * @param   string  $lock_label 		(Y/N) lock labels into 'text_pos'
     * @param   string  $textbar		'solid' color or 'plastic' for image background on the text background label
     * @param   int  	 $textbar_height 	height (in pixels) of the textbar 
     * @param   int  	 $textbar_alpha	alpha transparency (0-100) of textbar
     * @param   string  $show_buttons	(Y/N) show the next/previous buttons
     * @param   string  $button_pos		'top', 'middle', 'bottom'
     * @param   string  $overlay_image	image that will be loaded on top of Flash object (typically for a border mask)
     * @param   string  $allow_fullscreen	(Y/N) allow fullscreen mode
     * @param   string  $text_move		'none', 'up', 'down', 'left', 'right' movement of text label
     * @param   string  $image_move		(Y/N) pan the images left and right alternatively
     * @param   int  	 $image_offsetx	image X coordinate offset to accommodate 'overlay_image'
     * @param   int  	 $image_offsety	Y coordinate offset
     * @param   string  $load_immediately	(Y/N) load first item immediately instead of waiting for initial timer length
     * @param   string  $OwnerID
     * @param   string  $active         (Y/N) If the gallery is published or not
     * @return  boolean Success/failure
     */
    function UpdateFlashGallery($id, $type, $url, $title, $aspect_ratio, $width, $custom_width, $timer, $fadetime, 
		$columns, $order, $show_text, $text_pos, $lock_label, $textbar, $textbar_height, 
		$textbar_alpha, $show_buttons, $button_pos, $overlay_image, $allow_fullscreen, 
		$text_move, $image_move, $image_offsetx, $image_offsety, $load_immediately, 
		$background_color, $looping, $textbar_color, $background_image, $active, $height, $custom_height, $watermark_image)
	{
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		if ((($type == 'rss' || $type == 'xml') && empty($url)) || (($type == 'rss' || $type == 'xml') && !strpos($url, '://') !== false)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('FLASHGALLERY_ERROR_INVALID_URL'), RESPONSE_ERROR);
			require_once JAWS_PATH . 'include/Jaws/Header.php';
			if (BASE_SCRIPT != 'index.php') {
				Jaws_Header::Location(BASE_SCRIPT . '?gadget=FlashGallery&action=form&id='.$id);
			} else {
				Jaws_Header::Location('index.php?gadget=FlashGallery&action=account_form&id='.$id);
			}
		}
		if (
			(isset($background_image) && !empty($background_image)) || 
			(isset($overlay_image) && !empty($overlay_image)) || 
			(isset($watermark_image) && !empty($watermark_image))
		) {
			$background_image = $this->cleanImagePath($background_image);
			$overlay_image = $this->cleanImagePath($overlay_image);
			$watermark_image = $this->cleanImagePath($watermark_image);			
			if (
				substr(strtolower(trim($background_image)), 0, 4) == 'http' || 
				substr(strtolower(trim($background_image)), 0, 2) == '//' || 
				substr(strtolower(trim($background_image)), 0, 2) == '\\\\' || 
				substr(strtolower(trim($overlay_image)), 0, 4) == 'http' || 
				substr(strtolower(trim($overlay_image)), 0, 2) == '//' || 
				substr(strtolower(trim($overlay_image)), 0, 2) == '\\\\' || 
				substr(strtolower(trim($watermark_image)), 0, 4) == 'http' || 
				substr(strtolower(trim($watermark_image)), 0, 2) == '//' || 
				substr(strtolower(trim($watermark_image)), 0, 2) == '\\\\'
			) {
				$GLOBALS['app']->Session->PushLastResponse(_t('FLASHGALLERY_ERROR_INVALID_IMAGE'), RESPONSE_ERROR);
				require_once JAWS_PATH . 'include/Jaws/Header.php';
				if (BASE_SCRIPT != 'index.php') {
					Jaws_Header::Location(BASE_SCRIPT . '?gadget=FlashGallery&action=form&id='.$id);
				} else {
					Jaws_Header::Location('index.php?gadget=FlashGallery&action=account_form&id='.$id);
				}
			}
		}
        
		$model = $GLOBALS['app']->LoadGadget('FlashGallery', 'Model');
        $page = $model->GetFlashGallery($id);
        if (Jaws_Error::isError($page)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('FLASHGALLERY_ERROR_GALLERY_NOT_FOUND'), RESPONSE_ERROR);
            return $page;
        }

        $sql = '
            UPDATE [[flashgalleries]] SET
				[type] = {type}, 
				[url] = {url}, 
				[title] = {title}, 
				[aspect_ratio] = {aspect_ratio}, 
				[width] = {width}, 
				[custom_width] = {custom_width}, 
				[timer] = {timer}, 
				[fadetime] = {fadetime}, 
				[columns] = {columns}, 
				[order] = {order}, 
				[show_text] = {show_text}, 
				[text_pos] = {text_pos}, 
				[lock_label] = {lock_label}, 
				[textbar] = {textbar}, 
				[textbar_height] = {textbar_height}, 
				[textbar_alpha] = {textbar_alpha}, 
				[show_buttons] = {show_buttons}, 
				[button_pos] = {button_pos}, 
				[overlay_image] = {overlay_image}, 
				[allow_fullscreen] = {allow_fullscreen}, 
				[text_move] = {text_move}, 
				[image_move] = {image_move}, 
				[image_offsetx] = {image_offsetx}, 
				[image_offsety] = {image_offsety}, 
				[load_immediately] = {load_immediately}, 
				[background_color] = {background_color}, 
				[looping] = {looping}, 
				[textbar_color] = {textbar_color}, 
				[background_image] = {background_image}, 
				[active] = {Active}, 
				[updated] = {now},
				[height] = {height},
				[custom_height] = {custom_height},
				[watermark_image] = {watermark_image}
			WHERE [id] = {id}';

        $params               			= array();
        $params['id']         			= (int)$id;
        $params['type']         		= $type;
        $params['url'] 					= $url;
        $params['title'] 				= $title;
        $params['aspect_ratio'] 		= $aspect_ratio;
        $params['width']   				= $width;
        $params['custom_width'] 		= (int)$custom_width;
        $params['height']   			= $height;
        $params['custom_height'] 		= (int)$custom_height;
        $params['timer'] 				= (int)$timer;
        $params['fadetime'] 			= (int)$fadetime;
        $params['columns'] 				= (int)$columns;
        $params['order'] 				= $order;
        $params['show_text']        	= $show_text;
        $params['text_pos']        		= $text_pos;
        $params['lock_label']       	= $lock_label;
        $params['textbar']         		= $textbar;
        $params['textbar_height'] 		= (int)$textbar_height;
        $params['textbar_alpha'] 		= (int)$textbar_alpha;
        $params['show_buttons'] 		= $show_buttons;
        $params['button_pos'] 			= $button_pos;
        $params['overlay_image']   		= $overlay_image;
        $params['allow_fullscreen'] 	= $allow_fullscreen;
        $params['text_move'] 			= $text_move;
        $params['image_move'] 			= $image_move;
        $params['image_offsetx'] 		= (int)$image_offsetx;
        $params['image_offsety'] 		= (int)$image_offsety;
        $params['load_immediately'] 	= $load_immediately;
        $params['background_color'] 	= $background_color;
        $params['looping'] 				= $looping;
        $params['textbar_color'] 		= $textbar_color;
        $params['background_image'] 	= $background_image;
        $params['watermark_image'] 		= $watermark_image;
        $params['Active'] 				= $active;
        $params['now']        			= $GLOBALS['db']->Date();		

        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('FLASHGALLERY_ERROR_GALLERY_NOT_UPDATED'), RESPONSE_ERROR);
			//$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
            return $result;
        }
				
		// Let everyone know it has been added
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onUpdateFlashGallery', $id);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		
		$GLOBALS['app']->Session->PushLastResponse(_t('FLASHGALLERY_GALLERY_UPDATED'), RESPONSE_NOTICE);
        return true;
    }

	/**
     * Delete a gallery
     *
     * @access  public
     * @return  boolean True if query was successful and Jaws_Error on error
     */
    function DeleteFlashGallery($id, $massive = false)
    {
		$model = $GLOBALS['app']->LoadGadget('FlashGallery', 'Model');
		$parent = $model->GetFlashGallery((int)$id);
		if (Jaws_Error::IsError($parent)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('FLASHGALLERY_ERROR_GALLERY_NOT_DELETED'), RESPONSE_ERROR);
			return new Jaws_Error(_t('FLASHGALLERY_ERROR_GALLERY_NOT_DELETED'), _t('FLASHGALLERY_NAME'));
		}

		if(!isset($parent['id'])) {
			$GLOBALS['app']->Session->PushLastResponse(_t('FLASHGALLERY_ERROR_GALLERY_NOT_DELETED'), RESPONSE_ERROR);
			return new Jaws_Error(_t('FLASHGALLERY_ERROR_PAGE_NOT_DELETED'), _t('FLASHGALLERY_NAME'));
		} else {
			// Let everyone know it has been added
			$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
			$res = $GLOBALS['app']->Shouter->Shout('onDeleteFlashGallery', $id);
			if (Jaws_Error::IsError($res) || !$res) {
				return $res;
			}
			
			$rids = $model->GetPostsOfFlashGallery($parent['id']);
			if (Jaws_Error::IsError($rids)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('FLASHGALLERY_ERROR_GALLERY_NOT_DELETED'), RESPONSE_ERROR);
				return new Jaws_Error(_t('FLASHGALLERY_ERROR_GALLERY_NOT_DELETED'), _t('FLASHGALLERY_NAME'));
			}

			foreach ($rids as $rid) {
				if (!$this->DeletePost($rid['id'], true)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('FLASHGALLERY_ERROR_POST_NOT_DELETED'), RESPONSE_ERROR);
					return new Jaws_Error(_t('FLASHGALLERY_ERROR_POST_NOT_DELETED'), _t('FLASHGALLERY_NAME'));
				}
			}
	
			$sql = 'DELETE FROM [[flashgalleries]] WHERE [id] = {id}';
			$res = $GLOBALS['db']->query($sql, array('id' => $id));
			if (Jaws_Error::IsError($res)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('FLASHGALLERY_ERROR_GALLERY_NOT_DELETED'), RESPONSE_ERROR);
				return new Jaws_Error(_t('FLASHGALLERY_ERROR_GALLERY_NOT_DELETED'), _t('FLASHGALLERY_NAME'));
			}
		}

		if ($massive === false) {
			$GLOBALS['app']->Session->PushLastResponse(_t('FLASHGALLERY_GALLERY_DELETED'), RESPONSE_NOTICE);
        }
		return true;
    }

    /**
     * Deletes a group of galleries
     *
     * @access  public
     * @param   array   $pages  Array with the ids of pages
     * @return  bool    Success/failure
     */
    function MassiveDelete($pages)
    {
        if (!is_array($pages)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('FLASHGALLERY_ERROR_GALLERY_NOT_MASSIVE_DELETED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('FLASHGALLERY_ERROR_GALLERY_NOT_MASSIVE_DELETED'), _t('FLASHGALLERY_NAME'));
        }

        foreach ($pages as $page) {
            $res = $this->DeleteFlashGallery($page);
            if (Jaws_Error::IsError($res)) {
                $GLOBALS['app']->Session->PushLastResponse(_t('FLASHGALLERY_ERROR_GALLERY_NOT_MASSIVE_DELETED'), RESPONSE_ERROR);
                return new Jaws_Error(_t('FLASHGALLERY_ERROR_GALLERY_NOT_MASSIVE_DELETED'), _t('FLASHGALLERY_NAME'));
            }
        }
        $GLOBALS['app']->Session->PushLastResponse(_t('FLASHGALLERY_GALLERY_MASSIVE_DELETED'), RESPONSE_NOTICE);
        return true;
    }

    /**
     * Creates a new post to a gallery.
     *
     * @access  public
     * @param   integer  $sort_order 	The chronological order
     * @param   integer $LinkID  		The gallery ID to post it to
     * @param   string  $title      		The title of the post.
     * @param   string  $description    	The contents of the post.
     * @param   string  $image   		An image to accompany the post or the gadget "layout action"
     * @param   integer $url  			The url to link the post to
     * @param   string $url_target  		The target method of the link
     * @param   string $active  		(Y/N) If it's published or not
     * @param   integer $OwnerID  		The poster's user ID
     * @return  ID of entered post 	    Success/failure
     */
	function AddPost($sort_order, $LinkID, $title, $description, $image, $url_type = 'imageviewer', 
	$internal_url, $external_url, $url_target = '_self', $active = 'Y', $OwnerID = null, $checksum = '')
    {
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		if (strpos($_SERVER['SCRIPT_NAME'], 'index.php') !== false && is_null($OwnerID)) {
			$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');
		} else {
			$OwnerID = (!is_null($OwnerID)) ? (int)$OwnerID : 0;
		}

		if (
			$OwnerID == 0 && $url_type == 'external' && 
			substr(strtolower(trim($external_url)), 0, 4) == 'http' && 
			strpos(strtolower(trim(urldecode($external_url))), 'javascript:') === false
		) {
			$url = $xss->parse($external_url);
		} else if (
			$url_type == 'internal' && !empty($internal_url) && 
			(strpos(strtolower(trim(urldecode($internal_url))), 'javascript:') === false || 
			strtolower(trim(urldecode($internal_url))) == "javascript:void(0);")
		) {
			$url = $xss->parse($internal_url);
	    } else if (!empty($external_url) || !empty($internal_url)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('FLASHGALLERY_ERROR_INVALID_URL'), RESPONSE_ERROR);
            return new Jaws_Error(_t('FLASHGALLERY_ERROR_INVALID_URL'), _t('FLASHGALLERY_NAME'));
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
        
		$sql = "
            INSERT INTO [[flashgalleries_posts]]
                ([sort_order], [linkid], [title], 
				[description], [image], [url], [url_target], 
				[active], [ownerid], [created], [updated], [checksum])
            VALUES
                ({sort_order}, {LinkID}, {title}, 
				{description}, {image}, {url}, {url_target}, 
				{Active}, {OwnerID}, {now}, {now}, {checksum})";

        $params               		= array();
        $params['sort_order']       = (int)$sort_order;
        $params['title'] 			= $title;
		$description  				= str_replace("\r\n", "\n", $description);
		$params['description']  	= str_replace("<br />", "<br>", $description);
		$params['image'] 			= $image;
        $params['url'] 				= $url;
		$params['LinkID']         	= (int)$LinkID;
		$params['OwnerID']         	= $OwnerID;
        $params['Active'] 			= $active;
        $params['url_target'] 		= $url_target;
        $params['checksum'] 		= $checksum;
        $params['now']        		= $GLOBALS['db']->Date();
		
		$result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('FLASHGALLERY_ERROR_POST_NOT_ADDED'), RESPONSE_ERROR);
			//$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
            return $result;
        }
        $newid = $GLOBALS['db']->lastInsertID('flashgalleries_posts', 'id');

		if (empty($checksum)) {
			// Update checksum
			$config_key = $GLOBALS['app']->Registry->Get('/config/key');		
			$params               	= array();
			$params['id'] 			= $newid;
			$params['checksum'] 	= $newid.':'.$config_key;
			
			$sql = '
				UPDATE [[flashgalleries_posts]] SET
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
		$res = $GLOBALS['app']->Shouter->Shout('onAddFlashGalleryPost', $newid);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		
        $GLOBALS['app']->Session->PushLastResponse(_t('FLASHGALLERY_POST_CREATED'), RESPONSE_NOTICE);
        return $newid;
    }

    /**
     * Updates a post.
     *
     * @access  public
     * @param   int     $id             The ID of the post to update.
     * @param   integer $LinkID  		The gallery ID to post it to
     * @param   integer  $sort_order 	The chronological order
     * @param   string  $title      		The title of the post.
     * @param   string  $description    	The contents of the post.
     * @param   string  $image   		An image to accompany the post or the gadget "layout action"
     * @param   integer $url  			The url to link the post to
     * @param   string $Active  		(Y/N) If the post is published or not
     * @param   integer $OwnerID  		The poster's user ID
     * @param   string $url_target  		Window method to link the post with
     * @return  boolean Success/failure
     */
    function UpdatePost($id, $sort_order, $title, $description, $image, $url_type, $internal_url, $external_url, $url_target = '_self', $active, $LinkID = null)
    {
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$model = $GLOBALS['app']->LoadGadget('FlashGallery', 'Model');
        $page = $model->GetPost($id);
        if (Jaws_Error::isError($page)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('FLASHGALLERY_ERROR_POST_NOT_FOUND'), RESPONSE_ERROR);
            return new Jaws_Error(_t('FLASHGALLERY_ERROR_POST_NOT_FOUND'), _t('FLASHGALLERY_NAME'));
        }
		
		if (
			$page['ownerid'] == 0 && $url_type == 'external' && 
			substr(strtolower(trim($external_url)), 0, 4) == 'http' && 
			strpos(strtolower(trim(urldecode($external_url))), 'javascript:') === false
		) {
			$url = $xss->parse($external_url);
		} else if (
			$url_type == 'internal' && !empty($internal_url) && 
			(strpos(strtolower(trim(urldecode($internal_url))), 'javascript:') === false || 
			strtolower(trim(urldecode($internal_url))) == "javascript:void(0);")
		) {
			$url = $xss->parse($internal_url);
	    } else if (!empty($external_url) || !empty($internal_url)) {
	        $GLOBALS['app']->Session->PushLastResponse(_t('FLASHGALLERY_ERROR_INVALID_URL'), RESPONSE_ERROR);
            return new Jaws_Error(_t('FLASHGALLERY_ERROR_INVALID_URL'), _t('FLASHGALLERY_NAME'));
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
        $params               	= array();
        
		$sql = '
            UPDATE [[flashgalleries_posts]] SET
				[sort_order] = {sort_order}, 
				[title] = {title}, 
				[description] = {description}, 
				[image] = {image}, 
				[url] = {url}, 
				[active] = {Active}, 
				[updated] = {now},
				[url_target] = {url_target}
		';
		if (!is_null($LinkID)) {
			$parent = $model->GetFlashGallery((int)$LinkID);
			if (!Jaws_Error::IsError($parent) && isset($parent['id'])) {
				$params['linkid'] = $parent['id'];
				$sql .= ', [linkid] = {linkid}';
			}
		}
		$sql .=	'
			WHERE [id] = {id}';

        $params['id']         	= (int)$id;
        $params['sort_order'] 	= (int)$sort_order;
        $params['title'] 		= $title;
		$description  			= str_replace("\r\n", "\n", $description);
		$params['description']  = str_replace("<br />", "<br>", $description);
        $params['image'] 		= $image;
        $params['url'] 			= $url;
        $params['Active'] 		= $active;
        $params['url_target'] 	= $url_target;
        $params['now']        	= $GLOBALS['db']->Date();
		
        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('FLASHGALLERY_ERROR_POST_NOT_UPDATED'), _t('FLASHGALLERY_NAME'));
        }

		// Let everyone know it has been added
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onUpdateFlashGalleryPost', $id);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		
		$GLOBALS['app']->Session->PushLastResponse(_t('FLASHGALLERY_POST_UPDATED'), RESPONSE_NOTICE);
        return true;
    }


    /**
     * Deletes a post
     *
     * @access  public
     * @param   int     $id     The ID of the page to delete.
     * @return  bool    Success/failure
     */
    function DeletePost($id, $massive = false)
    {
		$model = $GLOBALS['app']->LoadGadget('FlashGallery', 'Model');
        $page = $model->GetPost($id);
        if (Jaws_Error::isError($page)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('FLASHGALLERY_ERROR_POST_NOT_FOUND'), RESPONSE_ERROR);
            return new Jaws_Error(_t('FLASHGALLERY_ERROR_POST_NOT_FOUND'), _t('FLASHGALLERY_NAME'));
        }
        
		// Let everyone know it has been added
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onDeleteFlashGalleryPost', $id);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		
		$sql = 'DELETE FROM [[flashgalleries_posts]] WHERE [id] = {id}';
        $result = $GLOBALS['db']->query($sql, array('id' => $page['id']));
        if (Jaws_Error::IsError($result)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('FLASHGALLERY_ERROR_POST_NOT_DELETED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('FLASHGALLERY_ERROR_POST_NOT_DELETED'), _t('FLASHGALLERY_NAME'));
        }

		if ($massive === false) {
			$GLOBALS['app']->Session->PushLastResponse(_t('FLASHGALLERY_POST_DELETED'), RESPONSE_NOTICE);
        }
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
		$model = $GLOBALS['app']->LoadGadget('FlashGallery', 'Model');
        $ids = explode(',', $pids);
        $sorts = explode(',', $newsorts);
        $i = 0;
		foreach ($ids as $pid) {
			if ((int)$pid != 0) {
				$new_sort = $sorts[$i];
				//$page = $model->GetPost($pid);
		        //if (Jaws_Error::isError($page)) {
		        //    $GLOBALS['app']->Session->PushLastResponse(_t('FLASHGALLERY_ERROR_POST_NOT_FOUND'), RESPONSE_ERROR);
				//	return false;
		        //} else {
				$params               	= array();
				$params['pid']         	= (int)$pid;
				$params['new_sort'] 	= (int)$new_sort;
				
				$sql = '
					UPDATE [[flashgalleries_posts]] SET
						[sort_order] = {new_sort} 
					WHERE [id] = {pid}';

				$result1 = $GLOBALS['db']->query($sql, $params);
				if (Jaws_Error::IsError($result1)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('FLASHGALLERY_ERROR_POST_NOT_SORTED'), RESPONSE_ERROR);
					//$GLOBALS['app']->Session->PushLastResponse($result1->GetMessage(), RESPONSE_ERROR);
					return false;
				}
				$i++;
			}
		}
		$GLOBALS['app']->Session->PushLastResponse(_t('FLASHGALLERY_POST_UPDATED'), RESPONSE_NOTICE);
		return true;
    }

	/**
     * Search for galleries that matches a status and/or a keyword
     * in the title or content
     *
     * @access  public
     * @access  public
     * @param   string  $status  Status of galleries(s) we want to display
     * @param   string  $search  Keyword (title/description) of galleries we want to look for
     * @param   int     $offSet  Data limit
     * @return  array   Array of matches
     */
    function SearchGalleries($status, $search, $offSet = null, $OwnerID = 0)
    {
        $params = array();
		$params['status'] = $status;

        $sql = '
            SELECT [id], [type], [url], [title], [aspect_ratio], [width],
				[custom_width], [timer], [fadetime], [columns], 
				[order], [show_text], [text_pos], [lock_label], [textbar], 
				[textbar_height], [textbar_alpha], [show_buttons], [button_pos], 
				[overlay_image], [allow_fullscreen], [text_move], [image_move], 
				[image_offsetx], [image_offsety], [load_immediately], [background_color], 
				[looping], [textbar_color], [background_image], [ownerid], [active], 
				[created], [updated], [height], [custom_height], [watermark_image], [checksum]
            FROM [[flashgalleries]]
			WHERE (title <> ""';
	    
        if (trim($status) != '') {
            $sql .= ' AND [active] = {status}';
        }
        $sql .= ')';
        
		$sql .= ' AND ([ownerid] = {OwnerID})';
		$params['OwnerID'] = $OwnerID;

		if (trim($search) != '') {
            $searchdata = explode(' ', $search);
            /**
             * This query needs more work, not use $v straight, should be
             * like rest of the param stuff.
             */
            $i = 0;
            foreach ($searchdata as $v) {
                $v = trim($v);
                $sql .= " AND ([title] LIKE {textLike_".$i."} OR [url] LIKE {textLike_".$i."} OR [type] LIKE {textLike_".$i."})";
                $params['textLike_'.$i] = '%'.$v.'%';
                $i++;
            }
        }

        $types = array(
			'integer', 'text', 'text', 'text', 'text', 'text', 
			'integer', 'integer', 'integer', 'integer', 'text', 
			'text', 'text', 'text', 'text', 'integer', 'integer',
			'text', 'text', 'text', 'text', 'text', 'text', 'integer', 
			'integer', 'text', 'text', 'text', 'text', 'text', 'integer', 
			'text', 'timestamp', 'timestamp', 'text', 'integer', 'text', 'text'
		);

        if (is_numeric($offSet)) {
            $limit = 10;
            $result = $GLOBALS['db']->setLimit(10, $offSet);
            if (Jaws_Error::IsError($result)) {
                return new Jaws_Error(_t('FLASHGALLERY_ERROR_GALLERIES_NOT_RETRIEVED'), _t('FLASHGALLERY_NAME'));
            }
        }

        $sql.= ' ORDER BY [id] ASC';

        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('FLASHGALLERY_ERROR_GALLERIES_NOT_RETRIEVED'), _t('FLASHGALLERY_NAME'));
        }
        //limit, sort, sortDirection, offset..
        return $result;
    }

    /**
     * Updates a User's galleries
     *
     * @access  public
     * @param   int  $uid  User ID
     * @return  array   Response
     */
    function UpdateUserGalleries($uid) 
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
				UPDATE [[flashgalleries]] SET
					[active] = {Active}
				WHERE ([ownerid] = {id}) AND ([active] = {was})';

			$result = $GLOBALS['db']->query($sql, $params);
			if (Jaws_Error::IsError($result)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('FLASHGALLERY_ERROR_USER_GALLERIES_NOT_UPDATED'), RESPONSE_ERROR);
				return false;
			}
			$GLOBALS['app']->Session->PushLastResponse(_t('FLASHGALLERY_USER_GALLERIES_UPDATED'), RESPONSE_NOTICE);
			return true;
		} else {
			$GLOBALS['app']->Session->PushLastResponse(_t('FLASHGALLERY_ERROR_USER_GALLERIES_NOT_UPDATED'), RESPONSE_ERROR);
			return false;
		}
    }	
		
    /**
     * Deletes a User's galleries
     *
     * @access  public
     * @param   int  $uid  User ID
     * @return  array   Response
     */
    function RemoveUserGalleries($uid) 
    {
		$model = $GLOBALS['app']->LoadGadget('FlashGallery', 'Model');
		$parents = $model->GetFlashGalleryOfUserID((int)$uid);
		if (!Jaws_Error::IsError($parents)) {
			foreach ($parents as $page) {
				$result = $this->DeleteFlashGallery($page['id'], true);
				if (Jaws_Error::IsError($result)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('FLASHGALLERY_ERROR_USER_GALLERY_NOT_DELETED'), RESPONSE_ERROR);
					return false;
				}
			}
			$GLOBALS['app']->Session->PushLastResponse(_t('FLASHGALLERY_USER_GALLERIES_DELETED'), RESPONSE_NOTICE);
		} else {
			$GLOBALS['app']->Session->PushLastResponse(_t('FLASHGALLERY_ERROR_USER_GALLERIES_NOT_DELETED'), RESPONSE_NOTICE);
			return false;
		}
		return true;
    }	

	/**
     * Inserts checksums for default (insert.xml) content
     *
     * @access  public
     * @param   string  $gadget   Get gadget name from onAfterEnablingGadget shouter call
     * @return  array   Response
     */
    function InsertDefaultChecksums($gadget)
    {
		if ($gadget == 'FlashGallery') {
			// Update checksum
			$config_key = $GLOBALS['app']->Registry->Get('/config/key');		
			
			$model = $GLOBALS['app']->LoadGadget('FlashGallery', 'Model');
			$parents = $model->GetFlashGalleries();
			if (Jaws_Error::IsError($parents)) {
				return false;
			}
			foreach ($parents as $parent) {
				if (empty($parent['checksum']) || is_null($parent['checksum']) || strpos($parent['checksum'], ':') === false) {
					$params               	= array();
					$params['id'] 			= $parent['id'];
					$params['checksum'] 	= $parent['id'].':'.$config_key;
					
					$sql = '
						UPDATE [[flash_galleries]] SET
							[checksum] = {checksum}
						WHERE [id] = {id}';

					$result = $GLOBALS['db']->query($sql, $params);
					if (Jaws_Error::IsError($result)) {
						$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
						return false;
					}

					// Let everyone know it has been added
					$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
					$res = $GLOBALS['app']->Shouter->Shout('onAddFlashGallery', $parent['id']);
					if (Jaws_Error::IsError($res) || !$res) {
						return $res;
					}
				}
				$posts = $model->GetPostsOfFlashGallery($parent['id']);
				if (Jaws_Error::IsError($posts)) {
					return false;
				}
				foreach ($posts as $post) {
					if (empty($post['checksum']) || is_null($post['checksum']) || strpos($post['checksum'], ':') === false) {
						$params               	= array();
						$params['id'] 			= $post['id'];
						$params['checksum'] 	= $post['id'].':'.$config_key;
						
						$sql = '
							UPDATE [[flashgalleries_posts]] SET
								[checksum] = {checksum}
							WHERE [id] = {id}';

						$result = $GLOBALS['db']->query($sql, $params);
						if (Jaws_Error::IsError($result)) {
							$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
							return false;
						}

						// Let everyone know it has been added
						$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
						$res = $GLOBALS['app']->Shouter->Shout('onAddFlashGalleryPost', $post['id']);
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