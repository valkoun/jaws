<?php
/**
 * FlashGallery Gadget Admin
 *
 * @category   GadgetAdmin
 * @package    FlashGallery
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class FlashGalleryAdminHTML extends Jaws_GadgetHTML
{
    /**
     * Constructor
     *
     * @access public
     */
    function FlashGalleryAdminHTML()
    {
        $this->Init('FlashGallery');
    }

    /**
     * Builds the menubar
     *
     * @access       public
     * @param        string  $selected Selected action
     * @return       string  The html menubar
     */
    function MenuBar($selected)
    {
        $actions = array('Admin','form','form_post','view','A_form','A_form_post');
        if (!in_array($selected, $actions)) {
            $selected = 'Admin';
        }

        require_once JAWS_PATH . 'include/Jaws/Widgets/Menubar.php';
        $menubar =& new Jaws_Widgets_Menubar();
        if ($GLOBALS['app']->Session->GetPermission('FlashGallery', 'ManageFlashGalleries')) {
            $menubar->AddOption('Admin', _t('FLASHGALLERY_MENU_ADMIN'),
                                'admin.php?gadget=FlashGallery&amp;action=Admin', STOCK_DOCUMENTS);
        }
        if ($GLOBALS['app']->Session->GetPermission('FlashGallery', 'default')) {
			if (strtolower($selected) != "admin" && (strtolower($selected) == 'view' || strtolower($selected) == 'form' || strtolower($selected) == 'form_post')) {
				$menubar->AddOption($selected, _t('FLASHGALLERY_MENU_GALLERY'),
	                                'javascript:void(0);', STOCK_NEW);
			}
            if (strtolower($selected) != "admin" && (strtolower($selected) == 'a_form' || strtolower($selected) == 'a_form_post')) {
				$menubar->AddOption($selected, _t('FLASHGALLERY_MENU_POST'),
	                                'javascript:void(0);', STOCK_EDIT);
			}
		}

		$request =& Jaws_Request::getInstance();
		$id = $request->get('id', 'get');
		if (strtolower($selected) == "form" && empty($id)) {
		} else {
			if ($GLOBALS['app']->Session->GetPermission('FlashGallery', 'ManageFlashGalleries')) {
				$menubar->AddOption('Add', '',
									'admin.php?gadget=FlashGallery&amp;action=form', STOCK_ADD);
			}
		}
        $menubar->Activate($selected);

        return $menubar->Get();
    }

    /**
     * Builds the left menu
     *
     * @access       public
     * @param        string  $selected Selected action
     * @return       string  The html menubar
     */
    function Menu()
    {
        // Left menu
        include_once JAWS_PATH . 'include/Jaws/Widgets/XHTMLMenu.php';
        $menu = new Jaws_Widgets_XHTMLMenu('', 'left_menu');
        // Main gadget content
        $menu->addOption('content_link', _t('FLASHGALLERY_GALLERY_CONTENT'), "javascript: showGadgetContent();", STOCK_EDIT);
        // Display options, such as layout, show title... language
        $menu->addOption('display_link', _t('FLASHGALLERY_GALLERY_DISPLAY'), "javascript: displayOptions();", STOCK_FONT, false, '', true);
        // Advanced options, such as metatags, keywords...
        $menu->addOption('advanced_link', _t('FLASHGALLERY_GALLERY_ADVANCED'), "javascript: advancedOptions();",
                         STOCK_PREFERENCES, false, '', true);

        return $menu->Get();
    }

    /**
     * Builds the basic datagrid view
     *
     * @access  private
     * @return  string   XHTML of datagrid
     */
    function DataGrid()
    {
        $model = $GLOBALS['app']->LoadGadget('FlashGallery', 'AdminModel');
		$OwnerID = 0;
		if (BASE_SCRIPT == 'index.php') {
			$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');
		}
        $sql = 'SELECT COUNT([id]) FROM [[flashgalleries]] WHERE [ownerid] = '.$OwnerID;
        $res = $GLOBALS['db']->queryOne($sql);
        $total = (Jaws_Error::IsError($res) ? 0 : $res);

        $grid =& Piwi::CreateWidget('DataGrid', array());
        $grid->TotalRows($total);
        $grid->SetStyle('width: 100%;');
        $grid->SetID('galleries_datagrid');
        $grid->SetAction('next', 'javascript:nextGalleryValues();');
        $grid->SetAction('prev', 'javascript:previousGalleryValues();');
        //$grid->useMultipleSelection();
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_TITLE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('FLASHGALLERY_TYPE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('FLASHGALLERY_ACTIVE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('FLASHGALLERY_LAST_UPDATE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_ACTIONS')));

        return $grid->Get();
    }

    /**
     * Returns an array with pages found
     *
     * @access  public
     * @param   string  $status  Status of galleries(s) we want to display
     * @param   string  $search  Keyword (title/description) of galleries we want to look for
     * @param   int     $limit   Data limit
     * @return  array   Data
     */
    function GetFlashGalleries($status, $search, $limit, $OwnerID = 0)
    {
        $model = $GLOBALS['app']->LoadGadget('FlashGallery', 'AdminModel');
        $pages = $model->SearchGalleries($status, $search, $limit, $OwnerID);
        if (Jaws_Error::IsError($pages)) {
            return array();
        }

        $data    = array();
		if (BASE_SCRIPT != 'index.php') {
			$edit_url    = BASE_SCRIPT . '?gadget=FlashGallery&amp;action=view&amp;id=';
        } else {
			$edit_url    = 'index.php?gadget=FlashGallery&amp;action=account_view&amp;id=';
		}
		$date = $GLOBALS['app']->loadDate();
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$ACL = $GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			

        foreach ($pages as $page) {
			$pageData = array();
			$pageData['title'] = '<a href="'.$edit_url.$page['id'].'">'.$page['title'].'</a>';
			$pageData['type']  = $page['type'];

			if ($page['active'] == 'Y') {
				$pageData['active'] = _t('FLASHGALLERY_PUBLISHED');
			} else {
				$pageData['active'] = _t('FLASHGALLERY_NOTPUBLISHED');
			}
			$pageData['date']  = $date->Format($page['updated']);
			$actions = '';
			if ($this->GetPermission('ManageFlashGalleries')) {
				if (BASE_SCRIPT != 'index.php') {
					$link =& Piwi::CreateWidget('Link', _t('GLOBAL_EDIT'),
												$edit_url.$page['id'],
												STOCK_EDIT);
				} else {
					$link =& Piwi::CreateWidget('Link', _t('GLOBAL_EDIT'),
												"javascript:window.open('".$edit_url.$page['id']."');",
												STOCK_EDIT);
				}
				$actions.= $link->Get().'&nbsp;';
			} else {
				if ($ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'FlashGallery', 'OwnFlashGallery')) {
					$link =& Piwi::CreateWidget('Link', _t('GLOBAL_EDIT'),
												"javascript:window.open('".$edit_url.$page['id']."');",
												STOCK_EDIT);
					$actions.= $link->Get().'&nbsp;';
				}
			}

			if ($this->GetPermission('ManageFlashGalleries')) {
				$link =& Piwi::CreateWidget('Link', _t('GLOBAL_DELETE'),
											"javascript: if (confirm('"._t('FLASHGALLERY_CONFIRM_DELETE_GALLERY', _t('FLASHGALLERY_GALLERY'))."')) ".
											"deleteFlashGallery('".$page['id']."');",
											"images/ICON_delete2.gif");
				$actions.= $link->Get().'&nbsp;';
			} else {
				if ($ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'FlashGallery', 'OwnFlashGallery')) {
					$link =& Piwi::CreateWidget('Link', _t('GLOBAL_DELETE'),
												"javascript: if (confirm('"._t('FLASHGALLERY_CONFIRM_DELETE_GALLERY', _t('FLASHGALLERY_GALLERY'))."')) ".
												"deleteFlashGallery('".$page['id']."');",
												"images/ICON_delete2.gif");
					$actions.= $link->Get().'&nbsp;';
				}
			}
			$pageData['actions'] = $actions;
			$pageData['__KEY__'] = $page['id'];
			$data[] = $pageData;
        }
        return $data;
    }

    /**
     * Display the default administration page which currently lists all pages
     *
     * @access public
     * @return string
     */
    function Admin($account = false)
    {
		if ($account === false) {
			$GLOBALS['app']->Session->CheckPermission('FlashGallery', 'default');
		} else {
			$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
			if (!$GLOBALS['app']->Session->GetPermission('FlashGallery', 'default')) {
				if (!$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'FlashGallery', 'OwnFlashGallery')) {
		            //$GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
					return "Please log-in.";
				}
			}
		}

        $tpl = new Jaws_Template('gadgets/FlashGallery/templates/');
        $tpl->Load('admin.html');
        $tpl->SetBlock('galleries_admin');
        
		$request =& Jaws_Request::getInstance();
		$action = $request->getRaw('action', 'get');
		if ($account === false) {
			$this->AjaxMe('script.js');
			$tpl->SetVariable('menubar', $this->MenuBar($action));
			$account_prefix = '';
			$base_url = BASE_SCRIPT;
		} else {
			$this->AjaxMe('client_script.js');
			$tpl->SetVariable('menubar', '');
			$account_prefix = 'account_';
			$base_url = 'index.php';
		}
        
		$tpl->SetVariable('account', $account_prefix);
		$tpl->SetVariable('base_script', $base_url);

        $tpl->SetVariable('grid', $this->DataGrid());

        $toolBar   =& Piwi::CreateWidget('HBox');

        $deleteAll =& Piwi::CreateWidget('Button', 'deleteAllGalleries',
                                         _t('GLOBAL_DELETE'),
                                         STOCK_DELETE);
        $deleteAll->AddEvent(ON_CLICK,
                             "javascript: massiveDelete('"._t('FLASHGALLERY_CONFIRM_MASIVE_DELETE_GALLERY')."');");

        $toolBar->Add($deleteAll);

        $tpl->SetVariable('tools', $toolBar->Get());
                
        $tpl->SetVariable('entries', $this->Datagrid());

		if ($account === false) {
	        $addPage =& Piwi::CreateWidget('Button', 'add_gallery', _t('FLASHGALLERY_ADD_GALLERY'), STOCK_ADD);
			$addPage->AddEvent(ON_CLICK, "javascript: window.location = '".$base_url."?gadget=FlashGallery&amp;action=".$account_prefix."form';");
	        $tpl->SetVariable('add_gallery', $addPage->Get());
		} else {
			//$addPage->AddEvent(ON_CLICK, "javascript: window.open('".$base_url."?gadget=FlashGallery&amp;action=".$account_prefix."form','','scrollbars=1,menubar=0,toolbar=0,location=0,status=1');");
	        $tpl->SetVariable('add_gallery', '');
		}

        $tpl->ParseBlock('galleries_admin');

        return $tpl->Get();
    }


    /**
     * We are on a form page
     *
     * @access public
     * @return string
     */
    function form($account = false)
    {
		$GLOBALS['app']->Session->PopLastResponse();
		// check session
		if ($account === false) {
			$GLOBALS['app']->Session->CheckPermission('FlashGallery', 'default');
		} else {
			$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
			if (!$GLOBALS['app']->Session->GetPermission('FlashGallery', 'default')) {
				if (!$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'FlashGallery', 'OwnFlashGallery')) {
		            $GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
					$userHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
					return $userHTML->DefaultAction();
				}
			}
		}

		// document dependencies
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$model = $GLOBALS['app']->LoadGadget('FlashGallery', 'Model');
		$request =& Jaws_Request::getInstance();
		$gather = array('action', 'id');
		$get = $request->getRaw($gather, 'get');
		$GLOBALS['app']->Layout->AddHeadLink('gadgets/FlashGallery/resources/style.css', 'stylesheet', 'text/css');

		// initialize template
		$tpl =& new Jaws_Template('gadgets/FlashGallery/templates/');
        $tpl->Load('admin.html');
        $tpl->SetBlock('gadget_page');

		// menus
		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=CustomPage&action=account_SetGBRoot');
		if ($account === false) {
			$this->AjaxMe('script.js');
			$tpl->SetVariable('menubar', $this->MenuBar($get['action']));
			$GLOBALS['app']->Layout->AddScriptLink('libraries/js/admin.js');			
			$submit_vars['ACTIONPREFIX'] = "";
			$submit_vars['CLOSE_BUTTON'] = "location.href='" . BASE_SCRIPT . "?gadget=FlashGallery&amp;action=Admin';";
			$OwnerID = 0;
			$base_url = 'admin.php';
		} else {
			$tpl->SetVariable('menubar', '');
			$this->AjaxMe('client_script.js');
			$submit_vars['ACTIONPREFIX'] = "account_";
			$submit_vars['CLOSE_BUTTON'] = "if (window.opener && !window.opener.closed) {window.opener.location.reload();window.close();};";
			$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');
			$base_url = 'index.php';
		}
		$syntactsUrl = $GLOBALS['app']->getSyntactsAdminHTMLUrl('FlashGallery/admin_FlashGallery_form');
        $tpl->SetVariable('left_menu', $this->Menu());

		$tpl->SetVariable('workarea-style', "style=\"float: left; margin-top: 30px;\" ");

		// syntacts page
		if ($syntactsUrl) {
			// snoopy
			include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
			$snoopy = new Snoopy('FlashGallery');
			$submit_url = $syntactsUrl;
			
			if($snoopy->fetch($submit_url)) {
				//while(list($key,$val) = each($snoopy->headers))
					//echo $key.": ".$val."<br>\n";
				//echo "<p>\n";
				
				//$page = "<PRE>".htmlspecialchars($snoopy->results)."</PRE>\n";
				$error = '';
				$form_content = '<div id="gadget_form"><table BORDER="0" width="100%" cellspacing="0" cellpadding="3" bordercolorlight="#C0C0C0" bordercolordark="#C0C0C0">';
				
				// initialize template
				$stpl =& new Jaws_Template();
		        $stpl->LoadFromString($snoopy->results);
		        $stpl->SetBlock('form');
				if (!empty($get['id'])) {
					// send page records
					$pageInfo = $model->GetFlashGallery($get['id']);
					if (!Jaws_Error::IsError($pageInfo) && (($GLOBALS['app']->Session->GetPermission('FlashGallery', 'ManageFlashGalleries') && $account === false) || $pageInfo['ownerid'] == $OwnerID)) {
						$submit_vars['SUBMIT_BUTTON'] = "<input type='Submit' name='Submit' value='Update'>";
					} else {
						//$error = _t('FLASHGALLERY_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403'));												
						//return new Jaws_Error(_t('FLASHGALLERY_ERROR_GALLERY_NOT_FOUND'), _t('FLASHGALLERY_NAME'));
						if ($account === true) {
							$GLOBALS['app']->Session->PushSimpleResponse(_t('FLASHGALLERY_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403')));
							$userHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
							return $userHTML->DefaultAction();
						} else {
							$GLOBALS['app']->Session->PushSimpleResponse(_t('FLASHGALLERY_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403')));
							Jaws_Header::Location($base_url.'?gadget=FlashGallery&action=Admin');
						}
					}
				} else {
					$submit_vars['SUBMIT_BUTTON'] = "<input type='Submit' name='Submit' value='Add'>";
				}

				// send requesting URL to syntacts
				$stpl->SetVariable('HTTP_REFERER', $GLOBALS['app']->GetSiteURL());
				$stpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . "/");
				//$stpl->SetVariable('DPATH', JAWS_DPATH);
				$stpl->SetVariable('actionprefix', $submit_vars['ACTIONPREFIX']);
				$stpl->SetVariable('gadget', 'FlashGallery');
				$stpl->SetVariable('CLOSE_BUTTON', $submit_vars['CLOSE_BUTTON']);
				$stpl->SetVariable('SUBMIT_BUTTON', $submit_vars['SUBMIT_BUTTON']);
				$stpl->SetVariable('controller', $base_url);
				
				// Get Help documentation
				$help_url = $GLOBALS['app']->getSyntactsAdminHTMLUrl("FlashGallery/admin_FlashGallery_form_help", 'txt');
				$snoopy_help = new Snoopy('FlashGallery');
		
				if($snoopy_help->fetch($help_url)) {
					$helpContent = Jaws_Utils::split2D($snoopy_help->results);
				}
								
				// Hidden elements
				$ID = (isset($pageInfo['id']) ? $pageInfo['id'] : '');
				$idHidden =& Piwi::CreateWidget('HiddenEntry', 'ID', $ID);
		        $form_content .= $idHidden->Get()."\n";

				$lock_label = (isset($pageInfo['lock_label']) ? $pageInfo['lock_label'] : 'Y');
				$lock_labelHidden =& Piwi::CreateWidget('HiddenEntry', 'lock_label', $lock_label);
		        $form_content .= $lock_labelHidden->Get()."\n";

				$fuseaction = (isset($pageInfo['id']) ? 'EditFlashGallery' : 'AddFlashGallery');
				$fuseactionHidden =& Piwi::CreateWidget('HiddenEntry', 'fuseaction', $fuseaction);
		        $form_content .= $fuseactionHidden->Get()."\n";
				
				$galleryType = (isset($pageInfo['type']) ? $pageInfo['type'] : 'slideshow');
				$galleryTypeHidden =& Piwi::CreateWidget('HiddenEntry', 'type', $galleryType);
		        $form_content .= $galleryTypeHidden->Get()."\n";
				
				$galleryAspect = (isset($pageInfo['aspect_ratio']) ? $pageInfo['aspect_ratio'] : '16:9');
				$galleryAspectHidden =& Piwi::CreateWidget('HiddenEntry', 'aspect_ratio', $galleryAspect);
		        $form_content .= $galleryAspectHidden->Get()."\n";
				
				$allow_fullscreen = (isset($pageInfo['allow_fullscreen']) ? $pageInfo['allow_fullscreen'] : 'Y');
				$allow_fullscreenHidden =& Piwi::CreateWidget('HiddenEntry', 'allow_fullscreen', $allow_fullscreen);
		        $form_content .= $allow_fullscreenHidden->Get()."\n";
				
				// Active
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == "Active") {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$active = (isset($pageInfo['active']) ? $pageInfo['active'] : 'Y');
				$activeCombo =& Piwi::CreateWidget('Combo', 'Active');
				$activeCombo->AddOption(_t('GLOBAL_YES'), 'Y');
				$activeCombo->AddOption(_t('GLOBAL_NO'), 'N');
				$activeCombo->SetDefault($active);
				$activeCombo->setTitle(_t('FLASHGALLERY_ACTIVE'));
				$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"Active\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$activeCombo->Get()."</td></tr>";
					
				/*
				// Type
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == "Type") {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$galleryType = (isset($pageInfo['type']) ? $pageInfo['type'] : 'gallery');
				$galleryTypeOptions = "<select NAME=\"type\" SIZE=\"1\" onChange=\"if (this.value == 'xml' || this.value == 'rss') toggleYes('urlInfo'); if (this.value != 'rss' || this.value != 'xml') toggleNo('urlInfo'); if (this.value == 'gallery') { toggleNo('aspectRatioInfo'); toggleYes('columnInfo'); toggleNo('galleryTimerInfo'); toggleNo('fadeTimeInfo'); toggleNo('orderInfo');} if (this.value != 'gallery') { toggleYes('aspectRatioInfo'); toggleNo('columnInfo'); toggleYes('galleryTimerInfo'); toggleYes('fadeTimeInfo'); toggleYes('orderInfo');}\">";
				$galleryTypeOptions .= "<option value=\"gallery\" ".($galleryType == "gallery" || $ID == "" ? " selected" : '').">Gallery</option>";
				$galleryTypeOptions .= "<option value=\"slideshow\" ".($galleryType == "slideshow" ? " selected" : '').">Slideshow</option>";
				$galleryTypeOptions .= "</select>";
				$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"type\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$galleryTypeOptions."</td></tr>";
				*/
				
				// URL
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == "URL") {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$galleryUrl = (isset($pageInfo['url']) ? $pageInfo['url'] : '');
				$galleryUrlEntry =& Piwi::CreateWidget('Entry', 'url', $galleryUrl);
				$galleryUrlEntry->SetTitle("URL");
				$galleryUrlEntry->SetStyle('direction: ltr; width: 300px;');
				$form_content .= "<tr style=\"display: ".($galleryType == "gallery" || $galleryType == "slideshow" || $ID == "" ? "none; " : "; ")."\" id=\"urlInfo\"><td class=\"syntacts-form-row\"><label for=\"url\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$galleryUrlEntry->Get()."</td></tr>";

				// Title
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == "Title") {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$title = (isset($pageInfo['title']) ? $pageInfo['title'] : '');
				$titleEntry =& Piwi::CreateWidget('Entry', 'title', $title);
				$titleEntry->SetTitle(_t('FLASHGALLERY_TITLE'));
				$titleEntry->SetStyle('direction: ltr; width: 300px;');
				$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"title\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$titleEntry->Get()."</td></tr>";

				/*
				// Aspect Ratio
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == "Aspect Ratio") {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$galleryAspect = (isset($pageInfo['aspect_ratio']) ? $pageInfo['aspect_ratio'] : '3:1');
				$galleryAspectOptions = "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
				$galleryAspectOptions .= "<tr>";
				$galleryAspectOptions .= "<td align=\"right\"><input type=\"radio\" value=\"3:1\" name=\"aspect_ratio\"".($galleryAspect == "3:1" || $ID == "" ? " checked" : '')."></td>";
				$galleryAspectOptions .= "<td><img border=\"0\" src=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/FlashGallery/images/ratio_3_1.jpg\" style=\"cursor: pointer; cursor: hand;\" onclick=\"setCheckedValue(document.forms['GLOBALform'].elements['aspect_ratio'], '3:1');\"></td>";
				$galleryAspectOptions .= "<td align=\"right\"><input type=\"radio\" value=\"16:9\" name=\"aspect_ratio\"".($galleryAspect == "16:9" ? " checked" : '')."></td>";
				$galleryAspectOptions .= "<td><img border=\"0\" src=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/FlashGallery/images/ratio_16_9.jpg\" style=\"cursor: pointer; cursor: hand;\" onclick=\"setCheckedValue(document.forms['GLOBALform'].elements['aspect_ratio'], '16:9');\"></td>";
				$galleryAspectOptions .= "<td align=\"right\"><input type=\"radio\" value=\"4:3\" name=\"aspect_ratio\"".($galleryAspect == "4:3" ? " checked" : '')."></td>";
				$galleryAspectOptions .= "<td><img border=\"0\" src=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/FlashGallery/images/ratio_4_3.jpg\" style=\"cursor: pointer; cursor: hand;\" onclick=\"setCheckedValue(document.forms['GLOBALform'].elements['aspect_ratio'], '4:3');\"></td>";
				$galleryAspectOptions .= "</tr>";
				$galleryAspectOptions .= "</table>";
				$form_content .= "<tr style=\"display: ".($galleryType == "gallery" || $ID == "" ? "none; " : "; ")."\" id=\"aspectRatioInfo\"><td class=\"syntacts-form-row\"><label for=\"aspect_ratio\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row middle\">".$galleryAspectOptions."</td></tr>";
				*/
				
				// Width
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == "Width") {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$galleryWidth = (isset($pageInfo['width']) ? $pageInfo['width'] : 'auto');
				$galleryWidthOptions = "<select NAME=\"width\" SIZE=\"1\" onChange=\"if (this.value == 'custom') toggleYes('custom_widthInfo'); if (this.value == 'auto') toggleNo('custom_widthInfo');\">";
				$galleryWidthOptions .= "<option value=\"auto\" ".($galleryWidth == "auto" || $ID == "" ? " selected" : '').">Auto</option>";
				$galleryWidthOptions .= "<option value=\"custom\" ".($galleryWidth == "custom" ? " selected" : '').">Custom</option>";
				$galleryWidthOptions .= "</select>";
				$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"width\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$galleryWidthOptions."</td></tr>";

				// Custom Width
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == "Custom Width") {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$customWidth = (isset($pageInfo['custom_width']) && $pageInfo['custom_width'] != 0 ? $pageInfo['custom_width'] : 950);
				$customWidthCombo =& Piwi::CreateWidget('Combo', 'custom_width');
				for ($i = 50; $i < 951; $i++) {
					$customWidthCombo->AddOption($i, $i);
				}
				$customWidthCombo->SetDefault($customWidth);
				$customWidthCombo->setTitle("Custom Width");
				$form_content .= "<tr style=\"display: ".($galleryWidth == "auto" || $ID == "" ? "none; " : "; ")."\" id=\"custom_widthInfo\"><td class=\"syntacts-form-row\"><label for=\"custom_width\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$customWidthCombo->Get()."</td></tr>";

				// Height
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == "Height") {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$galleryHeight = (isset($pageInfo['height']) ? $pageInfo['height'] : 'auto');
				$galleryHeightOptions = "<select NAME=\"height\" SIZE=\"1\" onChange=\"if (this.value == 'custom') toggleYes('custom_heightInfo'); if (this.value == 'auto') toggleNo('custom_heightInfo');\">";
				$galleryHeightOptions .= "<option value=\"auto\" ".($galleryHeight == "auto" || $ID == "" ? " selected" : '').">Auto</option>";
				$galleryHeightOptions .= "<option value=\"custom\" ".($galleryHeight == "custom" ? " selected" : '').">Custom</option>";
				$galleryHeightOptions .= "</select>";
				$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"height\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$galleryHeightOptions."</td></tr>";

				// Custom Height
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == "Custom Height") {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$customHeight = (isset($pageInfo['custom_height']) && $pageInfo['custom_height'] != 0 ? $pageInfo['custom_height'] : 300);
				$customHeightCombo =& Piwi::CreateWidget('Combo', 'custom_height');
				for ($i = 50; $i < 951; $i++) {
					$customHeightCombo->AddOption($i, $i);
				}
				$customHeightCombo->SetDefault($customHeight);
				$customHeightCombo->setTitle("Custom Width");
				$form_content .= "<tr style=\"display: ".($galleryHeight == "auto" || $ID == "" ? "none; " : "; ")."\" id=\"custom_heightInfo\"><td class=\"syntacts-form-row\"><label for=\"custom_height\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$customHeightCombo->Get()."</td></tr>";

				// Show Each Image For
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == "Show Each Image For") {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$galleryTimer = (isset($pageInfo['timer']) ? $pageInfo['timer'] : 5);
				$galleryTimerCombo =& Piwi::CreateWidget('Combo', 'timer');
				for ($i = 1; $i < 31; $i++) {
					$galleryTimerCombo->AddOption($i, $i);
				}
				$galleryTimerCombo->SetDefault($galleryTimer);
				$galleryTimerCombo->setTitle("Show Each Image For");
				$form_content .= "<tr style=\"display: ".($galleryType == "gallery" || $ID == "" ? "none; " : "; ")."\" id=\"galleryTimerInfo\"><td class=\"syntacts-form-row\"><label for=\"timer\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$galleryTimerCombo->Get()." seconds</td></tr>";

				// Transition Length
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == "Transition Length") {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$galleryFadetime = (isset($pageInfo['fadetime']) ? $pageInfo['fadetime'] : 3);
				$galleryFadetimeCombo =& Piwi::CreateWidget('Combo', 'fadetime');
				for ($i = 0; $i < 11; $i++) {
					$galleryFadetimeCombo->AddOption($i, $i);
				}
				$galleryFadetimeCombo->AddOption("-1", (-1));
				$galleryFadetimeCombo->SetDefault($galleryFadetime);
				$galleryFadetimeCombo->setTitle("Transition Length");
				$form_content .= "<tr style=\"display: ".($galleryType == "gallery" || $ID == "" ? "none; " : "; ")."\" id=\"fadeTimeInfo\"><td class=\"syntacts-form-row\"><label for=\"fadetime\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$galleryFadetimeCombo->Get()." seconds</td></tr>";
				
				// Grid Columns
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == "Grid Columns") {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$galleryColumns = (isset($pageInfo['columns']) ? $pageInfo['columns'] : 6);
				$galleryColumnsCombo =& Piwi::CreateWidget('Combo', 'columns');
				for ($i = 2; $i < 11; $i++) {
					$galleryColumnsCombo->AddOption($i, $i);
				}
				$galleryColumnsCombo->SetDefault($galleryColumns);
				$galleryColumnsCombo->setTitle("Grid Columns");
				$form_content .= "<tr style=\"display: ".($galleryType == "slideshow" || $galleryType == "xml" || $galleryType == "rss" ? "none; " : "; ")."\" id=\"columnInfo\"><td class=\"syntacts-form-row\"><label for=\"columns\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$galleryColumnsCombo->Get()."</td></tr>";

				// Order
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == "Order") {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$galleryOrder = (isset($pageInfo['order']) ? $pageInfo['order'] : 'sequential');
				$galleryOrderCombo =& Piwi::CreateWidget('Combo', 'order');
				$galleryOrderCombo->AddOption("Sequential", 'sequential');
				$galleryOrderCombo->AddOption("Random", 'random');
				$galleryOrderCombo->SetDefault($galleryOrder);
				$galleryOrderCombo->setTitle("Order");
				$form_content .= "<tr style=\"display: ".($galleryType == "gallery" || $ID == "" ? "none; " : "; ")."\" id=\"orderInfo\"><td class=\"syntacts-form-row\"><label for=\"order\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$galleryOrderCombo->Get()."</td></tr>";

				// Looping
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == "Looping") {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$galleryLooping = (isset($pageInfo['looping']) ? $pageInfo['looping'] : 'Y');
				$galleryLoopingCombo =& Piwi::CreateWidget('Combo', 'looping');
				$galleryLoopingCombo->AddOption(_t('GLOBAL_YES'), 'Y');
				$galleryLoopingCombo->AddOption(_t('GLOBAL_NO'), 'N');
				$galleryLoopingCombo->SetDefault($galleryLooping);
				$galleryLoopingCombo->setTitle("Looping");
				$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"looping\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$galleryLoopingCombo->Get()."</td></tr>";

				$form_content .= "\n</table>\n</div>\n<div id=\"display_form\"><table width=\"100%\" style=\"margin: 3px;\" border=\"0\" cellpadding=\"3\" cellspacing=\"0\" bordercolorlight=\"#C0C0C0\" bordercolordark=\"#C0C0C0\">";

				// Show Text Labels
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == "Show Text Labels") {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$galleryShowText = (isset($pageInfo['show_text']) ? $pageInfo['show_text'] : 'N');
				$galleryShowTextOptions = "<select NAME=\"show_text\" SIZE=\"1\" onChange=\"if (this.value == 'Y') {toggleYes('text_posInfo'); toggleYes('textbarInfo'); toggleYes('textbar_heightInfo'); toggleYes('textbar_alphaInfo');} if (this.value == 'N') {toggleNo('text_posInfo'); toggleNo('textbarInfo'); toggleNo('textbar_heightInfo'); toggleNo('textbar_alphaInfo');}\">";
				$galleryShowTextOptions .= "<option value=\"Y\" ".($galleryShowText == "Y" ? " selected" : '').">Yes</option>";
				$galleryShowTextOptions .= "<option value=\"N\" ".($galleryShowText == "N" || $ID == "" ? " selected" : '').">No</option>";
				$galleryShowTextOptions .= "</select>";
				$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"show_text\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$galleryShowTextOptions."</td></tr>";

				// Text Label Position
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == "Text Label Position") {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$galleryTextPos = (isset($pageInfo['text_pos']) ? $pageInfo['text_pos'] : 'top_left');
				$galleryTextPosOptions = "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
				$galleryTextPosOptions .= "<tr>";
				$galleryTextPosOptions .= "<td align=\"right\"><input type=\"radio\" value=\"top_left\" name=\"text_pos\"".($galleryTextPos == "top_left" || $ID == "" ? " checked" : '')."></td>";
				$galleryTextPosOptions .= "<td><img border=\"0\" src=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/FlashGallery/images/splash_align_i_L.jpg\" style=\"cursor: pointer; cursor: hand;\" onclick=\"setCheckedValue(document.forms['GLOBALform'].elements['text_pos'], 'top_left');\"></td>";
				$galleryTextPosOptions .= "<td align=\"right\"><input type=\"radio\" value=\"top\" name=\"text_pos\"".($galleryTextPos == "top" ? " checked" : '')."></td>";
				$galleryTextPosOptions .= "<td><img border=\"0\" src=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/FlashGallery/images/splash_align_top.jpg\" style=\"cursor: pointer; cursor: hand;\" onclick=\"setCheckedValue(document.forms['GLOBALform'].elements['text_pos'], 'top');\"></td>";
				$galleryTextPosOptions .= "<td align=\"right\"><input type=\"radio\" value=\"top_right\" name=\"text_pos\"".($galleryTextPos == "top_right" ? " checked" : '')."></td>";
				$galleryTextPosOptions .= "<td><img border=\"0\" src=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/FlashGallery/images/splash_align_i_R.jpg\" style=\"cursor: pointer; cursor: hand;\" onclick=\"setCheckedValue(document.forms['GLOBALform'].elements['text_pos'], 'top_right');\"></td>";
				$galleryTextPosOptions .= "</tr>";
				$galleryTextPosOptions .= "<tr><td colspan=\"6\">&nbsp;</td></tr>";
				$galleryTextPosOptions .= "<tr>";
				$galleryTextPosOptions .= "<td align=\"right\"><input type=\"radio\" value=\"left\" name=\"text_pos\"".($galleryTextPos == "left" ? " checked" : '')."></td>";
				$galleryTextPosOptions .= "<td><img border=\"0\" src=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/FlashGallery/images/splash_align_i_R_w_R.jpg\" style=\"cursor: pointer; cursor: hand;\" onclick=\"setCheckedValue(document.forms['GLOBALform'].elements['text_pos'], 'left');\"></td>";
				$galleryTextPosOptions .= "<td align=\"right\"><input type=\"radio\" value=\"center\" name=\"text_pos\"".($galleryTextPos == "center" ? " checked" : '')."></td>";
				$galleryTextPosOptions .= "<td><img border=\"0\" src=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/FlashGallery/images/splash_align_center.jpg\" style=\"cursor: pointer; cursor: hand;\" onclick=\"setCheckedValue(document.forms['GLOBALform'].elements['text_pos'], 'center');\"></td>";
				$galleryTextPosOptions .= "<td align=\"right\"><input type=\"radio\" value=\"right\" name=\"text_pos\"".($galleryTextPos == "right" ? " checked" : '')."></td>";
				$galleryTextPosOptions .= "<td><img border=\"0\" src=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/FlashGallery/images/splash_align_i_L_w_R.jpg\" style=\"cursor: pointer; cursor: hand;\" onclick=\"setCheckedValue(document.forms['GLOBALform'].elements['text_pos'], 'right');\"></td>";
				$galleryTextPosOptions .= "</tr>";
				$galleryTextPosOptions .= "<tr><td colspan=\"6\">&nbsp;</td></tr>";
				$galleryTextPosOptions .= "<tr>";
				$galleryTextPosOptions .= "<td align=\"right\"><input type=\"radio\" value=\"bottom_left\" name=\"text_pos\"".($galleryTextPos == "bottom_left" ? " checked" : '')."></td>";
				$galleryTextPosOptions .= "<td><img border=\"0\" src=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/FlashGallery/images/splash_align_i_T.jpg\" style=\"cursor: pointer; cursor: hand;\" onclick=\"setCheckedValue(document.forms['GLOBALform'].elements['text_pos'], 'bottom_left');\"></td>";
				$galleryTextPosOptions .= "<td align=\"right\"><input type=\"radio\" value=\"bottom\" name=\"text_pos\"".($galleryTextPos == "bottom" ? " checked" : '')."></td>";
				$galleryTextPosOptions .= "<td><img border=\"0\" src=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/FlashGallery/images/splash_align_bottom.jpg\" style=\"cursor: pointer; cursor: hand;\" onclick=\"setCheckedValue(document.forms['GLOBALform'].elements['text_pos'], 'bottom');\"></td>";
				$galleryTextPosOptions .= "<td align=\"right\"><input type=\"radio\" value=\"bottom_right\" name=\"text_pos\"".($galleryTextPos == "bottom_right" ? " checked" : '')."></td>";
				$galleryTextPosOptions .= "<td><img border=\"0\" src=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/FlashGallery/images/splash_align_i_L_t_T.jpg\" style=\"cursor: pointer; cursor: hand;\" onclick=\"setCheckedValue(document.forms['GLOBALform'].elements['text_pos'], 'bottom_right');\"></td>";
				$galleryTextPosOptions .= "</tr>";
				$galleryTextPosOptions .= "</table>";
				$form_content .= "<tr style=\"display: ".($galleryShowText == "N" ? "none; " : "; ")."\" id=\"text_posInfo\"><td class=\"syntacts-form-row\"><label for=\"text_pos\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row middle\">".$galleryTextPosOptions."</td></tr>";

				// Text Bar Style
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == "Text Bar Style") {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$galleryTextBar = (isset($pageInfo['textbar']) ? $pageInfo['textbar'] : 'plastic');
				$galleryTextBarColor = (isset($pageInfo['textbar_color'])) ? $pageInfo['textbar_color'] : '336699';
				$galleryTextBarOptions = "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
				$galleryTextBarOptions .= "<tr>";
				$galleryTextBarOptions .= "<td align=\"right\"><input type=\"radio\" value=\"plastic\" name=\"textbar\"".($galleryTextBar == "plastic" || $ID == "" ? " checked" : '')."></td>";
				$galleryTextBarOptions .= "<td><img border=\"0\" src=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/FlashGallery/images/textbar_plastic.jpg\" style=\"cursor: pointer; cursor: hand;\" onclick=\"setCheckedValue(document.forms['GLOBALform'].elements['textbar'], 'plastic');\"></td>";
				$galleryTextBarOptions .= "<td align=\"right\"><input type=\"radio\" value=\"solid\" name=\"textbar\"".($galleryTextBar == "solid" ? " checked" : '')."></td>";
				$galleryTextBarOptions .= "<td><div id=\"textbar_colorbox\" style=\"width: 75px; background-color: #".$galleryTextBarColor.";\">";
				$galleryTextBarOptions .= "<img border=\"0\" src=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/FlashGallery/images/textbar_solid.gif\" style=\"cursor: pointer; cursor: hand; background:;\" onclick=\"setCheckedValue(document.forms['GLOBALform'].elements['textbar'], 'solid'); openColorPicker('textbar_color','GLOBALform','textbar_colorbox','". $GLOBALS['app']->GetJawsURL() . '/' ."');\">";
				$galleryTextBarOptions .= "</div></td>";
				$galleryTextBarOptions .= "</tr>";
				$galleryTextBarOptions .= "</table>";
				$form_content .= "<tr style=\"display: ".($galleryShowText == "N" ? "none; " : "; ")."\" id=\"textbarInfo\"><td class=\"syntacts-form-row\"><label for=\"textbar\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row middle\">".$galleryTextBarOptions."</td></tr>";

				$galleryTextBarColorHidden =& Piwi::CreateWidget('HiddenEntry', 'textbar_color', $galleryTextBarColor);
		        $form_content .= $galleryTextBarColorHidden->Get()."\n";

				// Textbar Height
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == "Textbar Height") {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$galleryTextBarHeight = (isset($pageInfo['textbar_height']) ? $pageInfo['textbar_height'] : 58);
				$galleryTextBarHeightCombo =& Piwi::CreateWidget('Combo', 'textbar_height');
				for ($i = 20; $i < 91; $i++) {
					$galleryTextBarHeightCombo->AddOption($i, $i);
				}
				$galleryTextBarHeightCombo->AddOption("-1", (-1));
				$galleryTextBarHeightCombo->SetDefault($galleryTextBarHeight);
				$galleryTextBarHeightCombo->setTitle("Textbar Height");
				$form_content .= "<tr style=\"display: ".($galleryShowText == "N" || $lock_label == "N" ? "none; " : "; ")."\" id=\"textbar_heightInfo\"><td class=\"syntacts-form-row\"><label for=\"textbar_height\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$galleryTextBarHeightCombo->Get()." pixels</td></tr>";

				// Textbar Transparency
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == "Textbar Transparency") {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$galleryTextBarAlpha = (isset($pageInfo['textbar_alpha']) ? $pageInfo['textbar_alpha'] : 70);
				$galleryTextBarAlphaCombo =& Piwi::CreateWidget('Combo', 'textbar_alpha');
				for ($i = 0; $i < 100; $i += 10) {
					$galleryTextBarAlphaCombo->AddOption($i, $i);
				}
				$galleryTextBarAlphaCombo->SetDefault($galleryTextBarAlpha);
				$galleryTextBarAlphaCombo->setTitle("Textbar Transparency");
				$form_content .= "<tr style=\"display: ".($galleryShowText == "N" || $lock_label == "N" ? "none; " : "; ")."\" id=\"textbar_alphaInfo\"><td class=\"syntacts-form-row\"><label for=\"textbar_alpha\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$galleryTextBarAlphaCombo->Get()." %</td></tr>";

				// Show Buttons
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == "Show Buttons") {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$galleryShowButtons = (isset($pageInfo['show_buttons']) ? $pageInfo['show_buttons'] : 'Y');
				$galleryShowButtonsOptions = "<select NAME=\"show_buttons\" SIZE=\"1\" onChange=\"if (this.value == 'Y') {toggleYes('button_posInfo');} if (this.value == 'N') {toggleNo('button_posInfo');}\">";
				$galleryShowButtonsOptions .= "<option value=\"Y\" ".($galleryShowButtons == "Y" || $ID == "" ? " selected" : '').">Yes</option>";
				$galleryShowButtonsOptions .= "<option value=\"N\" ".($galleryShowButtons == "N" ? " selected" : '').">No</option>";
				$galleryShowButtonsOptions .= "</select>";
				$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"show_buttons\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$galleryShowButtonsOptions."</td></tr>";

				// Buttons Position
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == "Buttons Position") {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$galleryButtonPos = (isset($pageInfo['button_pos']) ? $pageInfo['button_pos'] : 'middle');
				$galleryButtonPosOptions = "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
				$galleryButtonPosOptions .= "<tr>";
				$galleryButtonPosOptions .= "<td align=\"right\"><input type=\"radio\" value=\"top\" name=\"button_pos\"".($galleryButtonPos == "top" ? " checked" : '')."></td>";
				$galleryButtonPosOptions .= "<td><img border=\"0\" src=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/FlashGallery/images/button_align_top.jpg\" style=\"cursor: pointer; cursor: hand;\" onclick=\"setCheckedValue(document.forms['GLOBALform'].elements['button_pos'], 'top');\"></td>";
				$galleryButtonPosOptions .= "<td align=\"right\"><input type=\"radio\" value=\"middle\" name=\"button_pos\"".($galleryButtonPos == "middle" || $ID == "" ? " checked" : '')."></td>";
				$galleryButtonPosOptions .= "<td><img border=\"0\" src=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/FlashGallery/images/button_align_middle.jpg\" style=\"cursor: pointer; cursor: hand;\" onclick=\"setCheckedValue(document.forms['GLOBALform'].elements['button_pos'], 'middle');\"></td>";
				$galleryButtonPosOptions .= "<td align=\"right\"><input type=\"radio\" value=\"bottom\" name=\"button_pos\"".($galleryButtonPos == "bottom" ? " checked" : '')."></td>";
				$galleryButtonPosOptions .= "<td><img border=\"0\" src=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/FlashGallery/images/button_align_bottom.jpg\" style=\"cursor: pointer; cursor: hand;\" onclick=\"setCheckedValue(document.forms['GLOBALform'].elements['button_pos'], 'bottom');\"></td>";
				$galleryButtonPosOptions .= "</tr>";
				$galleryButtonPosOptions .= "</table>";
				$form_content .= "<tr style=\"display: ".($galleryShowButtons == "N" ? "none; " : "; ")."\" id=\"button_posInfo\"><td class=\"syntacts-form-row\"><label for=\"text_pos\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row middle\">".$galleryButtonPosOptions."</td></tr>";

				// Background Color
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == "Background Color") {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$background_color = (isset($pageInfo['background_color']) ? $pageInfo['background_color'] : '');
				$galleryBackgroundOptions = "<b><input onClick=\"openColorPicker('background_color','GLOBALform','background_colorbox','". $GLOBALS['app']->GetJawsURL() . '/' ."');\" NAME=\"background_color\" SIZE=\"61\" VALUE=\"".$background_color."\"></b>&nbsp;&nbsp;<span onClick=\"openColorPicker('background_color','GLOBALform','background_colorbox','". $GLOBALS['app']->GetJawsURL() . '/' ."');\" id=\"background_colorbox\" style=\"cursor: hand; cursor: pointer; background-color: #".$background_color.";\" class=\"colorbox\">____</span>";
				$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"background_color\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$galleryBackgroundOptions."</td></tr>";

				// Background Image
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == "Background Image") {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$background_image = (isset($pageInfo['background_image']) ? $pageInfo['background_image'] : '');
				$background_image_src = $GLOBALS['app']->getDataURL() . 'files'.$xss->filter($pageInfo['background_image']);
				$background_image_preview = '';
				if ($background_image != '' && file_exists($background_image_src)) { 
					$background_image_preview .= "<br /><img border=\"0\" src=\"".$background_image_src."\" width=\"80\"".(strtolower(substr($background_image, -3)) == 'gif' || strtolower(substr($background_image, -3)) == 'png' || strtolower(substr($background_image, -3)) == 'bmp' ? ' height="80"' : '')." align=\"left\" style=\"padding: 5px;\">";
				}
				$background_imageScript = "<script type=\"text/javascript\">addFileToPost('FlashGallery', 'NULL', 'NULL', 'background_preview_image', 'background_image', 1, 400, 34);</script>";
				$background_imageHidden =& Piwi::CreateWidget('HiddenEntry', 'background_image', $background_image);
		        $form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"background_image\"><nobr>".$helpString."</nobr></label>".$background_image_preview."</td><td class=\"syntacts-form-row\"><div id=\"background_preview_image\" style=\"float: left; width: 400px;\"></div>".$background_imageScript.$background_imageHidden->Get()."</td></tr>";
				  
				// Overlay Image
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == "Overlay Image") {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$overlay_image = (isset($pageInfo['overlay_image']) ? $pageInfo['overlay_image'] : '');
				$overlay_image_src = $GLOBALS['app']->getDataURL() . 'files'.$xss->filter($pageInfo['overlay_image']);
				$overlay_image_preview = '';
				if ($overlay_image != '' && file_exists($overlay_image_src)) { 
					$overlay_image_preview .= "<br /><img border=\"0\" src=\"".$overlay_image_src."\" width=\"80\"".(strtolower(substr($overlay_image, -3)) == 'gif' || strtolower(substr($overlay_image, -3)) == 'png' || strtolower(substr($overlay_image, -3)) == 'bmp' ? ' height="80"' : '')." align=\"left\" style=\"padding: 5px;\">";
				}
				$overlay_imageScript = "<script type=\"text/javascript\">addFileToPost('FlashGallery', 'NULL', 'NULL', 'overlay_preview_image', 'overlay_image', 1, 400, 34);</script>";
				$overlay_imageHidden =& Piwi::CreateWidget('HiddenEntry', 'overlay_image', $overlay_image);
		        $form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"overlay_image\"><nobr>".$helpString."</nobr></label>".$overlay_image_preview."</td><td class=\"syntacts-form-row\"><div id=\"overlay_preview_image\" style=\"float: left; width: 400px;\"></div>".$overlay_imageScript.$overlay_imageHidden->Get()."</td></tr>";
				
				// Watermark Image
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == "Watermark Image") {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$watermark_image = (isset($pageInfo['watermark_image']) ? $pageInfo['watermark_image'] : '');
				$watermark_image_src = $GLOBALS['app']->getDataURL() . 'files'.$xss->filter($pageInfo['watermark_image']);
				$watermark_image_preview = '';
				if ($watermark_image != '' && file_exists($watermark_image_src)) { 
					$watermark_image_preview .= "<br /><img border=\"0\" src=\"".$watermark_image_src."\" width=\"80\"".(strtolower(substr($watermark_image, -3)) == 'gif' || strtolower(substr($watermark_image, -3)) == 'png' || strtolower(substr($watermark_image, -3)) == 'bmp' ? ' height="80"' : '')." align=\"left\" style=\"padding: 5px;\">";
				}
				$watermark_imageScript = "<script type=\"text/javascript\">addFileToPost('FlashGallery', 'NULL', 'NULL', 'watermark_preview_image', 'watermark_image', 1, 400, 34);</script>";
				$watermark_imageHidden =& Piwi::CreateWidget('HiddenEntry', 'watermark_image', $watermark_image);
		        $form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"watermark_image\"><nobr>".$helpString."</nobr></label>".$watermark_image_preview."</td><td class=\"syntacts-form-row\"><div id=\"watermark_preview_image\" style=\"float: left; width: 400px;\"></div>".$watermark_imageScript.$watermark_imageHidden->Get()."</td></tr>";
				
				$form_content .= "\n</table>\n</div>\n";

				$allow_fullscreenRow = '';
				/*
				// allow_fullscreen
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('FLASHGALLERY_ALLOWFULLSCREEN')) {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if ($help[2]) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if ($help[2]) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if ($help[2]) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$allow_fullscreen = (isset($pageInfo['allow_fullscreen']) ? $pageInfo['allow_fullscreen'] : 'Y');
				$fullscreenCombo =& Piwi::CreateWidget('Combo', 'allow_fullscreen');
				$fullscreenCombo->AddOption(_t('GLOBAL_YES'), 'Y');
				$fullscreenCombo->AddOption(_t('GLOBAL_NO'), 'N');
				$fullscreenCombo->SetDefault($allow_fullscreen);
				$fullscreenCombo->setTitle(_t('FLASHGALLERY_ALLOWFULLSCREEN'));
				$allow_fullscreenRow = "<tr><td class=\"syntacts-form-row\"><label for=\"allow_fullscreen\">".$helpString."</label></td><td class=\"syntacts-form-row\">".$fullscreenCombo->Get()."</td></tr>";
				*/
				
				// text_move
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('FLASHGALLERY_TEXTMOVE')) {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if ($help[2]) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if ($help[2]) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if ($help[2]) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$text_move = (isset($pageInfo['text_move']) ? $pageInfo['text_move'] : 'down');
				$textMoveCombo =& Piwi::CreateWidget('Combo', 'text_move');
				$textMoveCombo->AddOption(_t('FLASHGALLERY_NONE'), 'none');
				$textMoveCombo->AddOption(_t('FLASHGALLERY_UP'), 'up');
				$textMoveCombo->AddOption(_t('FLASHGALLERY_DOWN'), 'down');
				$textMoveCombo->AddOption(_t('FLASHGALLERY_LEFT'), 'left');
				$textMoveCombo->AddOption(_t('FLASHGALLERY_RIGHT'), 'right');
				$textMoveCombo->SetDefault($text_move);
				$textMoveCombo->setTitle(_t('FLASHGALLERY_TEXTMOVE'));

				$text_moveRow = "<tr><td class=\"syntacts-form-row\"><label for=\"text_move\">".$helpString."</label></td><td class=\"syntacts-form-row\">".$textMoveCombo->Get()."</td></tr>";

				// image_move
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('FLASHGALLERY_IMAGEMOVE')) {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if ($help[2]) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if ($help[2]) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if ($help[2]) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$image_move = (isset($pageInfo['image_move']) ? $pageInfo['image_move'] : 'N');
				$imageMoveCombo =& Piwi::CreateWidget('Combo', 'image_move');
				$imageMoveCombo->AddOption(_t('GLOBAL_YES'), 'Y');
				$imageMoveCombo->AddOption(_t('GLOBAL_NO'), 'N');
				$imageMoveCombo->SetDefault($image_move);
				$imageMoveCombo->setTitle(_t('FLASHGALLERY_IMAGEMOVE'));

				$image_moveRow = "<tr><td class=\"syntacts-form-row\"><label for=\"image_move\">".$helpString."</label></td><td class=\"syntacts-form-row\">".$imageMoveCombo->Get()."</td></tr>";

				// image_offsetX
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('FLASHGALLERY_IMAGEOFFSETX')) {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if ($help[2]) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if ($help[2]) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if ($help[2]) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$image_offsetx = (isset($pageInfo['image_offsetx']) ? $pageInfo['image_offsetx'] : '0');
				$offsetXCombo =& Piwi::CreateWidget('Combo', 'image_offsetx');
				$offsetXCombo->AddOption('0', 0);
				$offsetXCombo->AddOption('1', 1);
				$offsetXCombo->AddOption('2', 2);
				$offsetXCombo->AddOption('3', 3);
				$offsetXCombo->AddOption('4', 4);
				$offsetXCombo->AddOption('5', 5);
				$offsetXCombo->AddOption('6', 6);
				$offsetXCombo->AddOption('7', 7);
				$offsetXCombo->AddOption('8', 8);
				$offsetXCombo->AddOption('9', 9);
				$offsetXCombo->AddOption('10', 10);
				$offsetXCombo->AddOption('11', 11);
				$offsetXCombo->AddOption('12', 12);
				$offsetXCombo->AddOption('13', 13);
				$offsetXCombo->AddOption('14', 14);
				$offsetXCombo->AddOption('15', 15);
				$offsetXCombo->AddOption('16', 16);
				$offsetXCombo->AddOption('17', 17);
				$offsetXCombo->AddOption('18', 18);
				$offsetXCombo->AddOption('19', 19);
				$offsetXCombo->AddOption('20', 20);
				$offsetXCombo->AddOption('21', 21);
				$offsetXCombo->AddOption('22', 22);
				$offsetXCombo->AddOption('23', 23);
				$offsetXCombo->AddOption('24', 24);
				$offsetXCombo->AddOption('25', 25);
				$offsetXCombo->AddOption('30', 30);
				$offsetXCombo->AddOption('35', 35);
				$offsetXCombo->AddOption('40', 40);
				$offsetXCombo->AddOption('45', 45);
				$offsetXCombo->AddOption('50', 50);
				$offsetXCombo->AddOption('55', 55);
				$offsetXCombo->AddOption('60', 60);
				$offsetXCombo->AddOption('65', 65);
				$offsetXCombo->AddOption('70', 70);
				$offsetXCombo->AddOption('75', 75);
				$offsetXCombo->AddOption('80', 80);
				$offsetXCombo->AddOption('85', 85);
				$offsetXCombo->AddOption('90', 90);
				$offsetXCombo->AddOption('95', 95);
				$offsetXCombo->AddOption('100', 100);
				$offsetXCombo->AddOption('110', 110);
				$offsetXCombo->AddOption('120', 120);
				$offsetXCombo->AddOption('130', 130);
				$offsetXCombo->AddOption('140', 140);
				$offsetXCombo->AddOption('150', 150);
				$offsetXCombo->AddOption('160', 160);
				$offsetXCombo->AddOption('170', 170);
				$offsetXCombo->AddOption('180', 180);
				$offsetXCombo->AddOption('190', 190);
				$offsetXCombo->AddOption('200', 200);
				$offsetXCombo->SetDefault((int)$image_offsetx);
				$offsetXCombo->setTitle(_t('FLASHGALLERY_IMAGEOFFSETX'));

				$image_offsetXRow = "<tr><td class=\"syntacts-form-row\"><label for=\"image_offsetx\">".$helpString."</label></td><td class=\"syntacts-form-row\">".$offsetXCombo->Get()." pixels</td></tr>";
				
				// image_offsetY
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('FLASHGALLERY_IMAGEOFFSETY')) {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if ($help[2]) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if ($help[2]) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if ($help[2]) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$image_offsety = (isset($pageInfo['image_offsety']) ? $pageInfo['image_offsety'] : '0');
				$offsetYCombo =& Piwi::CreateWidget('Combo', 'image_offsety');
				$offsetYCombo->AddOption('0', 0);
				$offsetYCombo->AddOption('1', 1);
				$offsetYCombo->AddOption('2', 2);
				$offsetYCombo->AddOption('3', 3);
				$offsetYCombo->AddOption('4', 4);
				$offsetYCombo->AddOption('5', 5);
				$offsetYCombo->AddOption('6', 6);
				$offsetYCombo->AddOption('7', 7);
				$offsetYCombo->AddOption('8', 8);
				$offsetYCombo->AddOption('9', 9);
				$offsetYCombo->AddOption('10', 10);
				$offsetYCombo->AddOption('11', 11);
				$offsetYCombo->AddOption('12', 12);
				$offsetYCombo->AddOption('13', 13);
				$offsetYCombo->AddOption('14', 14);
				$offsetYCombo->AddOption('15', 15);
				$offsetYCombo->AddOption('16', 16);
				$offsetYCombo->AddOption('17', 17);
				$offsetYCombo->AddOption('18', 18);
				$offsetYCombo->AddOption('19', 19);
				$offsetYCombo->AddOption('20', 20);
				$offsetYCombo->AddOption('21', 21);
				$offsetYCombo->AddOption('22', 22);
				$offsetYCombo->AddOption('23', 23);
				$offsetYCombo->AddOption('24', 24);
				$offsetYCombo->AddOption('25', 25);
				$offsetYCombo->SetDefault((int)$image_offsety);
				$offsetYCombo->setTitle(_t('FLASHGALLERY_IMAGEOFFSETY'));

				$image_offsetYRow = "<tr><td class=\"syntacts-form-row\"><label for=\"image_offsety\">".$helpString."</label></td><td class=\"syntacts-form-row\">".$offsetYCombo->Get()." pixels</td></tr>";

				// load_immediately
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('FLASHGALLERY_LOADIMMEDIATELY')) {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if ($help[2]) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if ($help[2]) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if ($help[2]) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$load_immediately = (isset($pageInfo['load_immediately']) ? $pageInfo['load_immediately'] : 'N');
				$loadCombo =& Piwi::CreateWidget('Combo', 'load_immediately');
				$loadCombo->AddOption(_t('GLOBAL_YES'), 'N');
				$loadCombo->AddOption(_t('GLOBAL_NO'), 'Y');
				$loadCombo->SetDefault($load_immediately);
				$loadCombo->setTitle(_t('FLASHGALLERY_LOADIMMEDIATELY'));

				$load_immediatelyRow = "<tr><td class=\"syntacts-form-row\"><label for=\"load_immediately\">".$helpString."</label></td><td class=\"syntacts-form-row\">".$loadCombo->Get()."</td></tr>";
					
				$form_content .= "		<div id=\"advanced_form\"><table width=\"100%\" style=\"margin: 3px;\" border=\"0\" cellpadding=\"3\" cellspacing=\"0\">".$allow_fullscreenRow.$text_moveRow.$image_moveRow.$image_offsetXRow.$image_offsetYRow.$load_immediatelyRow."\n</table>\n</div>\n";

			if ($error != '') {
					$stpl->SetVariable('content', $error);
				} else {
					$stpl->SetVariable('content', $form_content);
		        }
				$stpl->ParseBlock('form');
				$page = $stpl->Get();
			} else {
				$page = _t('FLASHGALLERY_ERROR_ASPPAGE_NOT_RETRIEVED', $snoopy->error)."\n";
			}
						
			

			$tpl->SetVariable('content', $page);
		} else {
			return new Jaws_Error(_t('FLASHGALLERY_ERROR_ASPPAGE_NOT_RETRIEVED'), _t('FLASHGALLERY_NAME'));
		}
		
        $tpl->ParseBlock('gadget_page');
        return $tpl->Get();

    }

    /**
     * We are on the form_post page
     *
     * @access public
     * @return string
     */
    function form_post($account = false, $fuseaction = '', $params = array())
    {
		// check session
		if ($account === false) {
			$GLOBALS['app']->Session->CheckPermission('FlashGallery', 'default');
		} else {
			$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
			if (!$GLOBALS['app']->Session->GetPermission('FlashGallery', 'default')) {
				if (!$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'FlashGallery', 'OwnFlashGallery')) {
		            $GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
					$userHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
					return $userHTML->DefaultAction();
				}
			}
		}

		require_once JAWS_PATH . 'include/Jaws/Header.php';
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');        
		
		$request =& Jaws_Request::getInstance();
        if (empty($fuseaction)) {
			$fuseaction = $request->get('fuseaction', 'post');
		}
		$get  = $request->get(array('fuseaction', 'linkid', 'id'), 'get');
        if (empty($fuseaction)) {
			$fuseaction = $get['fuseaction'];
		}
        
		$adminModel = $GLOBALS['app']->LoadGadget('FlashGallery', 'AdminModel');
		$model = $GLOBALS['app']->LoadGadget('FlashGallery', 'Model');

        if (!empty($fuseaction)) {		
			switch($fuseaction) {
                case "AddFlashGallery": 
						$keys = array('type', 'url', 'title', 'aspect_ratio', 'width', 'custom_width', 'timer', 'fadetime', 
							'columns', 'order', 'show_text', 'text_pos', 'lock_label', 'textbar', 'textbar_height', 
							'textbar_alpha', 'show_buttons', 'button_pos', 'overlay_image', 'watermark_image', 'allow_fullscreen', 
							'text_move', 'image_move', 'image_offsetx', 'image_offsety', 'load_immediately', 
							'background_color', 'looping', 'textbar_color', 'background_image', 'Active', 'height', 'custom_height');
						if (count($params) > 0) {
							$postData = $params;
						} else {	
							$postData = $request->getRaw($keys, 'post');
						}
						//foreach($postData as $key => $value) {
							//echo $key."=".$value."\n";
						//}
						// add OwnerID if no permissions
						if ($GLOBALS['app']->Session->GetPermission('FlashGallery', 'ManageFlashGalleries') && $account === false) {
							$OwnerID = null;
						} else {
							$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
						}
						$result = $adminModel->AddFlashGallery($postData['type'], $postData['url'], $postData['title'], $postData['aspect_ratio'], $postData['width'], $postData['custom_width'], $postData['timer'], $postData['fadetime'], 
							$postData['columns'], $postData['order'], $postData['show_text'], $postData['text_pos'], $postData['lock_label'], $postData['textbar'], $postData['textbar_height'], 
							$postData['textbar_alpha'], $postData['show_buttons'], $postData['button_pos'], $postData['overlay_image'], $postData['allow_fullscreen'], 
							$postData['text_move'], $postData['image_move'], $postData['image_offsetx'], $postData['image_offsety'], $postData['load_immediately'], 
							$postData['background_color'], $postData['looping'], $postData['textbar_color'], $postData['background_image'], $OwnerID, $postData['Active'],
							$postData['height'], $postData['custom_height'], $postData['watermark_image']
						);
						if ($result && !Jaws_Error::IsError($result)) {
					        // declare result as ok for later
							$editgallery = true;
						} else {
							$editgallery = false;
							$url = "javascript:history.go(-1)";
							$link =& Piwi::CreateWidget('Link', "Please go back and try again",
														$url);
							return $result->GetMessage().'<br />'.$link->Get();
						}
						break;
                case "EditFlashGallery": 
						$keys = array('ID', 'type', 'url', 'title', 'aspect_ratio', 'width', 'custom_width', 'timer', 'fadetime', 
							'columns', 'order', 'show_text', 'text_pos', 'lock_label', 'textbar', 'textbar_height', 
							'textbar_alpha', 'show_buttons', 'button_pos', 'overlay_image', 'watermark_image', 'allow_fullscreen', 
							'text_move', 'image_move', 'image_offsetx', 'image_offsety', 'load_immediately', 
							'background_color', 'looping', 'textbar_color', 'background_image', 'Active', 'height', 'custom_height');
						if (count($params) > 0) {
							$postData = $params;
						} else {	
							$postData = $request->getRaw($keys, 'post');
						}
						//foreach($postData as $key => $value) {
						//	echo $key."=".$value."\n";
						//}
						if (isset($postData['ID'])) {
							// add OwnerID if no permissions
							if ($GLOBALS['app']->Session->GetPermission('FlashGallery', 'ManageFlashGalleries') && $account === false) {
								$result = $adminModel->UpdateFlashGallery($postData['ID'], $postData['type'], $postData['url'], $postData['title'], $postData['aspect_ratio'], $postData['width'], $postData['custom_width'], $postData['timer'], $postData['fadetime'], 
									$postData['columns'], $postData['order'], $postData['show_text'], $postData['text_pos'], $postData['lock_label'], $postData['textbar'], $postData['textbar_height'], 
									$postData['textbar_alpha'], $postData['show_buttons'], $postData['button_pos'], $postData['overlay_image'], $postData['allow_fullscreen'], 
									$postData['text_move'], $postData['image_move'], $postData['image_offsetx'], $postData['image_offsety'], $postData['load_immediately'], 
									$postData['background_color'], $postData['looping'], $postData['textbar_color'], $postData['background_image'], $postData['Active'],
									$postData['height'], $postData['custom_height'], $postData['watermark_image']
								);
							} else {
								$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
								$parent = $model->GetFlashGallery((int)$postData['ID']);
								if ($OwnerID == $parent['ownerid']) {
									$result = $adminModel->UpdateFlashGallery($postData['ID'], $postData['type'], $postData['url'], $postData['title'], $postData['aspect_ratio'], $postData['width'], $postData['custom_width'], $postData['timer'], $postData['fadetime'], 
									$postData['columns'], $postData['order'], $postData['show_text'], $postData['text_pos'], $postData['lock_label'], $postData['textbar'], $postData['textbar_height'], 
									$postData['textbar_alpha'], $postData['show_buttons'], $postData['button_pos'], $postData['overlay_image'], $postData['allow_fullscreen'], 
									$postData['text_move'], $postData['image_move'], $postData['image_offsetx'], $postData['image_offsety'], $postData['load_immediately'], 
									$postData['background_color'], $postData['looping'], $postData['textbar_color'], $postData['background_image'], $postData['Active'],
									$postData['height'], $postData['custom_height'], $postData['watermark_image']
									);
								} else {
									return _t('FLASHGALLERY_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403'));												
								}
							}
						}
						if ($result && !Jaws_Error::IsError($result)) {
							$editgallery = true;
						} else {
							$editgallery = false;
							$url = "javascript:history.go(-1)";
							$link =& Piwi::CreateWidget('Link', "Please go back and try again",
														$url);
							return $result->GetMessage().'<br />'.$link->Get();
						}
                       break;
                case "DeleteFlashGallery": 
				        //$keys = array('idarray', 'ID', 'xcount');
				        $keys = array('ID');
						$postData = $request->getRaw($keys, 'post');
						$id = $postData['ID'];
						if (empty($id)) {
							$id = $get['id'];
						}
						// delete each ID
						if ($id) {
							// check OwnerID if no permissions
							if ($GLOBALS['app']->Session->GetPermission('FlashGallery', 'ManageFlashGalleries') && $account === false) {
								$result = $adminModel->DeleteFlashGallery($id);
							} else {
								$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
								$parent = $model->GetFlashGallery($id);
								if ($OwnerID == $parent['ownerid']) {
									$result = $adminModel->DeleteFlashGallery($id);
								} else {
									return _t('FLASHGALLERY_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403'));												
								}
							}
						}						
						if ($result && !Jaws_Error::IsError($result)) {
							$editgallery = true;
						} else {
							$editgallery = false;
							$url = "javascript:history.go(-1)";
							$link =& Piwi::CreateWidget('Link', "Please go back and try again",
														$url);
							return $result->GetMessage().'<br />'.$link->Get();
						}
						break;
                case "AddPost": 
				        $keys = array('sort_order', 'LinkID', 'title', 'description', 
							'Image', 'url_type', 'internal_url', 'external_url', 'url_target', 'Active');
						if (count($params) > 0) {
							$postData = $params;
						} else {	
							$postData = $request->getRaw($keys, 'post');
						}
						//foreach($postData as $key => $value) {
						//	echo $key."=".$value."\n";
						//}
						// add OwnerID if no permissions
						if ($GLOBALS['app']->Session->GetPermission('FlashGallery', 'ManageFlashGalleries') && $account === false) {
							$OwnerID = null;
						} else {
							$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
						}
						$result = $adminModel->AddPost($postData['sort_order'], $postData['LinkID'], 
							$postData['title'], $postData['description'], $postData['Image'], $postData['url_type'], 
							$postData['internal_url'], $postData['external_url'], $postData['url_target'], $postData['Active'], $OwnerID);
						if ($result && !Jaws_Error::IsError($result)) {
							$editpost = true;
						} else {
							$editpost = false;
							$url = "javascript:history.go(-1)";
							$link =& Piwi::CreateWidget('Link', "Please go back and try again",
														$url);
							return $result->GetMessage().'<br />'.$link->Get();
						}
						break;
                case "EditPost": 
				        $keys = array('ID', 'sort_order', 'LinkID', 'title', 'description', 
							'Image', 'url_type', 'internal_url', 'external_url', 'url_target', 'Active');
						if (count($params) > 0) {
							$postData = $params;
						} else {	
							$postData = $request->getRaw($keys, 'post');
						}
						//foreach($postData as $key => $value) {
						//	echo $key."=".$value."\n";
						//}
						if ($postData['ID']) {
							// check OwnerID if no permissions
							if ($GLOBALS['app']->Session->GetPermission('FlashGallery', 'ManageFlashGalleries') && $account === false) {
								$result = $adminModel->UpdatePost($postData['ID'], $postData['sort_order'], 
							$postData['title'], $postData['description'], $postData['Image'], $postData['url_type'], 
							$postData['internal_url'], $postData['external_url'], $postData['url_target'], $postData['Active'], 
							$postData['LinkID']);
							} else {
								$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
								$post = $model->GetPost($postData['ID']);
								if ($OwnerID == $post['ownerid']) {
									$result = $adminModel->UpdatePost($post['id'], $postData['sort_order'], 
							$postData['title'], $postData['description'], $postData['Image'], $postData['url_type'], 
							$postData['internal_url'], $postData['external_url'], $postData['url_target'], $postData['Active'],
							$postData['LinkID']);
								} else {
									return _t('FLASHGALLERY_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403'));												
								}
							}
						}
						if ($result && !Jaws_Error::IsError($result)) {
							$editpost = true;
						} else {
							$editpost = false;
							$url = "javascript:history.go(-1)";
							$link =& Piwi::CreateWidget('Link', "Please go back and try again",
														$url);
							return $result->GetMessage().'<br />'.$link->Get();
						}
                        break;
                case "DeletePost": 
				        $keys = array('idarray', 'ID', 'xcount');
						$postData = $request->getRaw($keys, 'post');
						$id = $postData['ID'];
						if (empty($id)) {
							$id = $get['id'];
						}
						$dcount = 0;
						// loop through the idarray and delete each ID
						if ($postData['idarray'] && strpos($postData['idarray'], ',')) {
					        $ids = explode(',', $postData['idarray']);
							foreach ($ids as $i => $v) {
								if ($GLOBALS['app']->Session->GetPermission('FlashGallery', 'ManageFlashGalleries') && $account === false) {
									$result = $adminModel->DeletePost((int)$v);
								} else {
									$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
									$post = $model->GetPost((int)$v);
									if ($OwnerID == $post['ownerid']) {
										$result = $adminModel->DeletePost((int)$v);
									} else {
									return _t('FLASHGALLERY_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403'));												
									}
								}								
								$dcount++;
							}
						} else if (!empty($id)) {
							if ($GLOBALS['app']->Session->GetPermission('FlashGallery', 'ManageFlashGalleries') && $account === false) {
								$result = $adminModel->DeletePost($id);
							} else {
								$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
								$post = $model->GetPost($id);
								if ($OwnerID == $post['ownerid']) {
									$result = $adminModel->DeletePost($id);
								} else {
									return _t('FLASHGALLERY_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403'));												
								}
							}
						}
						if ($result && !Jaws_Error::IsError($result)) {
							$editpost = true;
						} else {
							$editpost = false;
							$url = "javascript:history.go(-1)";
							$link =& Piwi::CreateWidget('Link', "Please go back and try again",
														$url);
							return $result->GetMessage().'<br />'.$link->Get();
						}
						break;
            }
			
			// Send us to the appropriate page
			if ($editgallery === true) {
				if (count($params) > 0) {
					return (is_numeric($result) ? $result : (isset($postData['ID']) && !empty($postData['ID']) ? (int)$postData['ID'] : false));
				} else if ($fuseaction == 'DeleteFlashGallery') {
					$redirect = BASE_SCRIPT . '?gadget=FlashGallery&action=Admin';
				} else if (is_numeric($result)) {
					$redirect = BASE_SCRIPT . '?gadget=FlashGallery&action=view&id='.$result;
				} else {
					$redirect = BASE_SCRIPT . '?gadget=FlashGallery&action=view&id='.$postData['ID'];
				}
			} else if ($editpost === true) {
				if (count($params) > 0) {
					return (is_numeric($result) ? $result : (isset($postData['ID']) && !empty($postData['ID']) ? (int)$postData['ID'] : false));
				} else {
					$redirect = BASE_SCRIPT . '?gadget=FlashGallery&action=view&id='.$postData['LinkID'];
				}
			} else {
				if ($account === false) {
					Jaws_Header::Location(BASE_SCRIPT . '?gadget=FlashGallery');
				} else {
					Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'));
				}
			}
			
			if ($account === false) {
				Jaws_Header::Location($redirect);
			} else {
				if ($editgallery === true) {
					$output_html = "";
					$output_html .= "<script>\n";
					$output_html .= "	if (window.opener && !window.opener.closed) {\n";
					$output_html .= "		window.opener.location.reload();\n";
					$output_html .= "	}\n";
					$output_html .= "	window.location.href='index.php?gadget=FlashGallery&action=account_view&id=".(is_numeric($result) ? $result : $postData['ID'])."';\n";
					$output_html .= "</script>\n";
					$output_html .= "<noscript><div style='color:#FF0000; font-weight: bold;'>Javascript must be enabled in your browser in order to use this service.</div></noscript>\n";
					return $output_html;
				} else if ($editpost === true) {
					$output_html = "";
					$output_html .= "<script>\n";
					$output_html .= "if (window.opener && !window.opener.closed) {\n";
					$output_html .= "	window.opener.location.reload();\n";
					$output_html .= "	window.close();\n";
					$output_html .= "}\n";
					$output_html .= "</script>\n";
					$output_html .= "<noscript><div style='color:#FF0000; font-weight: bold;'>Javascript must be enabled in your browser in order to use this service.</div></noscript>\n";
					return $output_html;
				}
			}

		} else {
			if ($account === false) {
				Jaws_Header::Location(BASE_SCRIPT . '?gadget=FlashGallery');
			} else {
				Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'));
			}
		}

    }


    /**
     * We are on the view page
     *
     * @access public
     * @return string
     */
    function view($account = false)
    {
		//$GLOBALS['app']->Session->PopLastResponse();
		// check session
		if ($account === false) {
			$GLOBALS['app']->Session->CheckPermission('FlashGallery', 'default');
		} else {
			$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
			if (!$GLOBALS['app']->Session->GetPermission('FlashGallery', 'default')) {
				if (!$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'FlashGallery', 'OwnFlashGallery')) {
		            $GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
					$userHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
					return $userHTML->DefaultAction();
				}
			}
		}

		// document dependencies
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$request =& Jaws_Request::getInstance();
		$action = $request->getRaw('action', 'get');
		$pid = $request->getRaw('id', 'get');
		require_once JAWS_PATH . 'include/Jaws/Header.php';

		// initialize template
		$tpl =& new Jaws_Template('gadgets/FlashGallery/templates/');
        $tpl->Load('admin.html');
        $tpl->SetBlock('msgbox-wrapper');
        $responses = $GLOBALS['app']->Session->PopLastResponse();
        if ($responses) {
            foreach ($responses as $msg_id => $response) {
                $tpl->SetBlock('msgbox-wrapper/msgbox');
                $tpl->SetVariable('msg-css', $response['css']);
                $tpl->SetVariable('msg-txt', $response['message']);
                $tpl->SetVariable('msg-id', $msg_id);
                $tpl->ParseBlock('msgbox-wrapper/msgbox');
            }
        }
        $tpl->ParseBlock('msgbox-wrapper');
		$tpl->SetBlock('gadget_page');
		$tpl->SetVariable('workarea-style', "style=\"margin-top: 30px;\" ");
        $tpl->SetVariable('actionsTitle', _t('FLASHGALLERY_ACTIONS'));
        $tpl->SetVariable('confirmPostDelete', _t('FLASHGALLERY_POST_CONFIRM_DELETE'));
		
		// account differences
		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=CustomPage&action=account_SetGBRoot');
		if ($account === false) {
			$this->AjaxMe('script.js');
			$tpl->SetVariable('menubar', $this->MenuBar($action));
			$submit_vars['ACTIONPREFIX'] = "";
			$submit_vars['CLOSE_BUTTON'] = "location.href='" . BASE_SCRIPT . "?gadget=FlashGallery&amp;action=Admin';";
			$GLOBALS['app']->Layout->AddScriptLink('libraries/js/admin.js');
			$OwnerID = 0;
			$base_url = 'admin.php';
		} else {
			$this->AjaxMe('client_script.js');
			$tpl->SetVariable('menubar', '');
			$submit_vars['ACTIONPREFIX'] = "account_";
			$submit_vars['CLOSE_BUTTON'] = "if (window.opener && !window.opener.closed) {window.opener.location.reload();window.close();};";
			$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
			$base_url = 'index.php';
		}
		$syntactsUrl = $GLOBALS['app']->getSyntactsAdminHTMLUrl("FlashGallery/admin_FlashGallery_view");

		$GLOBALS['app']->Layout->AddHeadLink('libraries/piwi/piwidata/css/default.css', 'stylesheet', 'text/css', 'default');
		$GLOBALS['app']->Layout->AddScriptLink('libraries/greybox/AJS.js');
        $GLOBALS['app']->Layout->AddScriptLink('libraries/greybox/AJS_fx.js');
        $GLOBALS['app']->Layout->AddScriptLink('libraries/greybox/gb_scripts.js');
        $GLOBALS['app']->Layout->AddHeadLink('libraries/greybox/gb_styles.css', 'stylesheet', 'text/css');
        //$GLOBALS['app']->Layout->AddScriptLink('libraries/js/swfobject.js');
		/*
		$GLOBALS['app']->Layout->AddScriptLink('libraries/window/dist/window.js');
        $GLOBALS['app']->Layout->AddHeadLink('libraries/window/themes/window/window.css', 'stylesheet', 'text/css');
        $GLOBALS['app']->Layout->AddHeadLink('libraries/window/themes/window/simplewhite.css', 'stylesheet', 'text/css');
        $GLOBALS['app']->Layout->AddHeadLink('libraries/window/themes/shadow/mac_shadow.css', 'stylesheet', 'text/css');
		*/

		// syntacts page
		if ($syntactsUrl) {
			// snoopy
			include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
			$snoopy = new Snoopy('FlashGallery');
			$submit_url = $syntactsUrl;
			
			if($snoopy->fetch($submit_url)) {
				//while(list($key,$val) = each($snoopy->headers))
					//echo $key.": ".$val."<br>\n";
				//echo "<p>\n";
				
				//$page = "<PRE>".htmlspecialchars($snoopy->results)."</PRE>\n";
				$error = '';
				$form_content = '';
				
				// initialize template
				$stpl =& new Jaws_Template();
				$stpl->LoadFromString($snoopy->results);
				$stpl->SetBlock('view');
			
				$galleryXHTML = '';
				$view_content = '';
			
				if (!empty($pid)) {
					$model = $GLOBALS['app']->LoadGadget('FlashGallery', 'Model');
					// send Page records
					$pageInfo = $model->GetFlashGallery($pid);
					
					if (!Jaws_Error::IsError($pageInfo) && (($GLOBALS['app']->Session->GetPermission('FlashGallery', 'ManageFlashGalleries') && $account === false) || $pageInfo['ownerid'] == $OwnerID)) {
						
						$galleryLayout = $GLOBALS['app']->LoadGadget('FlashGallery', 'LayoutHTML');
						// TODO: add more types (rss, xml, featured)
						switch($pageInfo['type']) {
							case "gallery": 
								$galleryXHTML = $galleryLayout->Gallery($pid);
								break;
							case "slideshow":
								$galleryXHTML = $galleryLayout->Slideshow($pid);
								break;
						}
						$stpl->SetVariable('JAWS_GALLERY', $galleryXHTML);
						$stpl->SetVariable('id', $pageInfo['id']);
						$stpl->SetVariable('url', $xss->filter($pageInfo['url']));
						$stpl->SetVariable('title', $xss->filter($pageInfo['title']));
						$stpl->SetVariable('OwnerID', $pageInfo['ownerid']);
						$stpl->SetVariable('Active', $pageInfo['active']);
						$stpl->SetVariable('Created', $pageInfo['created']);
						$stpl->SetVariable('Updated', $pageInfo['updated']);
						
						// send requesting URL to syntacts
						$stpl->SetVariable('HTTP_REFERER', $GLOBALS['app']->GetSiteURL());
						$stpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . "/");
						//$stpl->SetVariable('DPATH', JAWS_DPATH);
						$stpl->SetVariable('actionprefix', $submit_vars['ACTIONPREFIX']);
						$stpl->SetVariable('CLOSE_BUTTON', $submit_vars['CLOSE_BUTTON']);
						$stpl->SetVariable('gadget', 'FlashGallery');
						$stpl->SetVariable('controller', $base_url);
						
						// send embedding options
						$embed_options = "<a href=\"javascript:void(0);\" onclick=\"showEmbedWindow('".$GLOBALS['app']->GetSiteURL()."/".$base_url."?gadget=FlashGallery&amp;action=".$submit_vars['ACTIONPREFIX']."ShowEmbedWindow&amp;linkid=".$pageInfo['id']."&amp;mode=full', 'Embed This Gallery');\">This Gallery</a>&nbsp;&nbsp;&nbsp;\n";
						$embed_options .= "<a href=\"javascript:void(0);\" onclick=\"showEmbedWindow('".$GLOBALS['app']->GetSiteURL()."/".$base_url."?gadget=FlashGallery&amp;action=".$submit_vars['ACTIONPREFIX']."ShowEmbedWindow&amp;linkid=".$pageInfo['id']."&amp;uid=".$GLOBALS['app']->Session->GetAttribute('user_id')."&amp;mode=Single', 'Embed One Image From This Gallery');\">One Image From This Gallery</a>&nbsp;&nbsp;&nbsp;\n";
						$stpl->SetVariable('embed_options', $embed_options);
						
						// send Post records
						$postsHTML = '';
						$posts = $model->GetPostsOfFlashGallery($pid);
						if (!Jaws_Error::IsError($posts)) {
							$i = 0;
							foreach($posts as $post) {		            
								$background = '';
								if ($i == 0) {
									$background = "background: #EDF3FE; border-top: dotted 1pt #E2E2E2; ";
								} else if (($i % 2) == 0) {
									$background = "background: #EDF3FE; ";
								}
								$postsHTML .= "<tr id=\"syntactsCategory_".$post['id']."\">\n";
								$postsHTML .= "	<td style=\"".$background."padding:3px;\" class=\"syntacts-form-row\">";
								if (!empty($post['title'])) {
									$postsHTML .= "<nobr><b>".(strlen($post['title']) > 35 ? substr($post['title'], 0, 35).'...' : $post['title'])."</b></nobr><br />";
								} else {
									$postsHTML .= "<nobr><b>".substr($post['image'], 1, strlen($post['image']))."</b></nobr><br />";
								}
								if (!empty($post['description'])) {
									$postsHTML .= (strlen($post['description']) > 35 ? substr(strip_tags($post['description']), 0, 35).'...' : strip_tags($post['description']))."<br />";
								} else {
									$postsHTML .= "<span style=\"color: #666666;\"><i>No description</i></span><br />";
								}
								$postsHTML .= "	</td>\n";
								$postsHTML .= "	<td style=\"".$background."text-align: center; padding:3px;\" class=\"syntacts-form-row\"><nobr>";
								$postsHTML .= "<a href=\"javascript:void(0);\" onclick=\"";
								if ($account === true) {
									$postsHTML .= "showPostWindow('" . $GLOBALS['app']->GetSiteURL()."/index.php?gadget=FlashGallery&amp;action=account_A_form&amp;linkid=".($pid != 'all' ? $pid : '')."&amp;id=".$post['id']."', 'Edit Post');";
								} else { 
									$postsHTML .= "location.href='admin.php?gadget=FlashGallery&amp;action=A_form&amp;linkid=".($pid != 'all' ? $pid : '')."&amp;id=".$post['id']."';";
								}
								$postsHTML .= "\">EDIT</a>";
								$postsHTML .= "&nbsp;&nbsp;<noscript><INPUT TYPE=\"checkbox\" NAME=\"ID\" VALUE=\"".$post['id']."\"></noscript><script>document.write('<a href=\"javascript:void(0);\" onClick=\"deletePost(".$post['id'].");\" title=\"Delete this Post\">DELETE</a>');</script>";
								$postsHTML .= "</nobr></td>\n";
								$postsHTML .= "</tr>\n";
								$i++;
							}
							if ($postsHTML == '') {
								$postsHTML .= "<style>#syntactsCategories_head {display: none;}</style>\n";
								$postsHTML .= "<tr id=\"syntactsCategories_no_items\" noDrop=\"true\" noDrag=\"true\"><td>&nbsp;</td><td style=\"text-align:left\"><i>No posts ";
								$postsHTML .= "have been added to this gallery yet.</i></td><td>&nbsp;</td><td>&nbsp;</td></tr>\n";
								//$propertiesHTML .= "<style>#syntactsCategories {display: none;}</style>\n";
							}
						} else {
							$postsHTML .= _t('FLASHGALLERY_ERROR_POSTS_NOT_RETRIEVED')."\n";
						}
						$stpl->SetVariable('posts_html', $postsHTML);
						
						// Drag and drop sorting
						$drag_drop = '';
						if ($pid != 'all') {
							$drag_drop = "<script type=\"text/javascript\">Event.observe(window, \"load\",function(){var table = document.getElementById('syntactsCategories');var tableDnD = new GalleryTableDnD();tableDnD.init(table);});</script>\n";			
						}
						$stpl->SetVariable('drag_drop', $drag_drop);

					} else {
						// Send us to the appropriate page
						if ($account === true) {
							$GLOBALS['app']->Session->PushSimpleResponse(_t('FLASHGALLERY_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403')));
							$userHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
							return $userHTML->DefaultAction();
						} else {
							$GLOBALS['app']->Session->PushSimpleResponse(_t('FLASHGALLERY_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403')));
							Jaws_Header::Location($base_url.'?gadget=FlashGallery&action=Admin');
						}
					}

					$stpl->ParseBlock('view');
					$page = $stpl->Get();


				} else {
					// Send us to the appropriate page
					if ($account === true) {
						Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'));
					} else {
						Jaws_Header::Location($base_url.'?gadget=FlashGallery&action=Admin');
					}
				}
			} else {
				$page = _t('FLASHGALLERY_ERROR_ASPPAGE_NOT_RETRIEVED', $snoopy->error)."\n";
			}

			$tpl->SetVariable('content', $page);
		}

        $tpl->ParseBlock('gadget_page');

        return $tpl->Get();

    }

    /**
     * We are on the A_form page
     *
     * @access public
     * @return string
     */
    function A_form($account = false)
    {
		$GLOBALS['app']->Session->PopLastResponse();
		// check session
		if ($account === false) {
			$GLOBALS['app']->Session->CheckPermission('FlashGallery', 'default');
		} else {
			$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
			if (!$GLOBALS['app']->Session->GetPermission('FlashGallery', 'default')) {
				if (!$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'FlashGallery', 'OwnFlashGallery')) {
		            $GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
					$userHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
					return $userHTML->DefaultAction();
				}
			}
		}

		// document dependencies
		
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$model = $GLOBALS['app']->LoadGadget('FlashGallery', 'Model');
		$request =& Jaws_Request::getInstance();
		$gather = array('action', 'id', 'linkid');
		$get = $request->getRaw($gather, 'get');

		// initialize template
		$tpl =& new Jaws_Template('gadgets/FlashGallery/templates/');
        $tpl->Load('admin.html');
        $tpl->SetBlock('gadget_page');

		// menus
		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=CustomPage&action=account_SetGBRoot');
		if ($account === false) {
			$this->AjaxMe('script.js');
			$tpl->SetVariable('menubar', $this->MenuBar($get['action']));
			$GLOBALS['app']->Layout->AddScriptLink('libraries/js/admin.js');			
			$submit_vars['ACTIONPREFIX'] = "";
			$submit_vars['CLOSE_BUTTON'] = "location.href='" . BASE_SCRIPT . "?gadget=FlashGallery&amp;action=view&amp;id=".$get['linkid']."';";
			$OwnerID = 0;
			$base_url = "admin.php";
		} else {
			$tpl->SetVariable('menubar', '');
			$this->AjaxMe('client_script.js');
			$submit_vars['ACTIONPREFIX'] = "account_";
			$submit_vars['CLOSE_BUTTON'] = "parent.parent.hideGB();";
			$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');
			$base_url = "index.php";
		}
		$syntactsUrl = $GLOBALS['app']->getSyntactsAdminHTMLUrl('FlashGallery/admin_FlashGallery_A_form');
        $tpl->SetVariable('left_menu', '');

		$tpl->SetVariable('workarea-style', '');

		// syntacts page
		if ($syntactsUrl) {
			// snoopy
			include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
			$snoopy = new Snoopy('FlashGallery');
			$submit_url = $syntactsUrl;
			
			if($snoopy->fetch($submit_url)) {
				//while(list($key,$val) = each($snoopy->headers))
					//echo $key.": ".$val."<br>\n";
				//echo "<p>\n";
				
				//$page = "<PRE>".htmlspecialchars($snoopy->results)."</PRE>\n";
				$error = '';
				$form_content = '<table BORDER="0" width="100%" cellspacing="0" cellpadding="3" bordercolorlight="#C0C0C0" bordercolordark="#C0C0C0">';
				
				// initialize template
				$stpl =& new Jaws_Template();
		        $stpl->LoadFromString($snoopy->results);
		        $stpl->SetBlock('form');
				if (!empty($get['id'])) {
					// send page records
					$pageInfo = $model->GetPost($get['id']);
					if (!Jaws_Error::IsError($pageInfo) && (($GLOBALS['app']->Session->GetPermission('FlashGallery', 'ManageFlashGalleries') && $account === false) || $pageInfo['ownerid'] == $OwnerID)) {
						$submit_vars['SUBMIT_BUTTON'] = "<input type='Submit' name='Submit' value='Update'>";
					} else {
						//$error = _t('FLASHGALLERY_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403'));												
						//return new Jaws_Error(_t('FLASHGALLERY_ERROR_POST_NOT_FOUND'), _t('FLASHGALLERY_NAME'));
						// Send us to the appropriate page
						if ($account === true) {
							$GLOBALS['app']->Session->PushSimpleResponse(_t('FLASHGALLERY_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403')));
							$userHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
							return $userHTML->DefaultAction();
						} else {
							$GLOBALS['app']->Session->PushSimpleResponse(_t('FLASHGALLERY_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403')));
							Jaws_Header::Location($base_url.'?gadget=FlashGallery&action=view&id='.$get['linkid']);
						}
					}
				} else {
					$submit_vars['SUBMIT_BUTTON'] = "<input type='Submit' name='Submit' value='Add'>";
				}

				// send requesting URL to syntacts
				$stpl->SetVariable('HTTP_REFERER', $GLOBALS['app']->GetSiteURL());
				$stpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . "/");
				//$stpl->SetVariable('DPATH', JAWS_DPATH);
				$stpl->SetVariable('actionprefix', $submit_vars['ACTIONPREFIX']);
				$stpl->SetVariable('gadget', 'FlashGallery');
				$stpl->SetVariable('CLOSE_BUTTON', $submit_vars['CLOSE_BUTTON']);
				$stpl->SetVariable('SUBMIT_BUTTON', $submit_vars['SUBMIT_BUTTON']);
				$stpl->SetVariable('controller', $base_url);
				
				// Get Help documentation
				$help_url = $GLOBALS['app']->getSyntactsAdminHTMLUrl("FlashGallery/admin_FlashGallery_A_form_help", 'txt');
				$snoopy = new Snoopy('FlashGallery');
		
				if($snoopy->fetch($help_url)) {
					$helpContent = Jaws_Utils::split2D($snoopy->results);
				}
								
				// Hidden elements
				$ID = (isset($pageInfo['id'])) ? $pageInfo['id'] : '';
				$idHidden =& Piwi::CreateWidget('HiddenEntry', 'ID', $ID);
		        $form_content .= $idHidden->Get()."\n";

				$LinkID = (isset($pageInfo['linkid']) ? $pageInfo['linkid'] : (!empty($get['linkid']) ? (int)$get['linkid'] : 1));
				
				/*
				$LinkIDHidden =& Piwi::CreateWidget('HiddenEntry', 'LinkID', $LinkID);
		        $form_content .= $LinkIDHidden->Get()."\n";
				*/
				
				if (empty($pageInfo['sort_order'])) {
					// send highest sort_order
					$sql = "SELECT [sort_order] FROM [[flashgalleries_posts]] WHERE ([linkid] = {linkid}) ORDER BY [sort_order] DESC LIMIT 1";
					$params = array();
					$params['linkid'] = $LinkID;
					$sort_order = $GLOBALS['db']->queryOne($sql, $params);
					if (Jaws_Error::IsError($sort_order)) {
						return new Jaws_Error($sort_order->GetMessage(), _t('FLASHGALLERY_NAME'));
					}
				} else {
					$sort_order = $pageInfo['sort_order'];
				}
				$sort_orderHidden =& Piwi::CreateWidget('HiddenEntry', 'sort_order', $sort_order);
		        $form_content .= $sort_orderHidden->Get()."\n";

				$fuseaction = (isset($pageInfo['id'])) ? 'EditPost' : 'AddPost';
				$fuseactionHidden =& Piwi::CreateWidget('HiddenEntry', 'fuseaction', $fuseaction);
		        $form_content .= $fuseactionHidden->Get()."\n";
				
				// Active
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == "Active") {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$active = (isset($pageInfo['active'])) ? $pageInfo['active'] : 'Y';
				$activeCombo =& Piwi::CreateWidget('Combo', 'Active');
				$activeCombo->AddOption(_t('GLOBAL_YES'), 'Y');
				$activeCombo->AddOption(_t('GLOBAL_NO'), 'N');
				$activeCombo->SetDefault($active);
				$activeCombo->setTitle("Active");
				$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"Active\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$activeCombo->Get()."</td></tr>";
					
				// Parent
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == "Gallery") {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$parentCombo =& Piwi::CreateWidget('Combo', 'LinkID');
				$parents = $model->GetFlashGalleries(null, 'title', 'ASC', false, ($account === false || ($GLOBALS['app']->Session->GetPermission('FlashGallery', 'default') || ($GLOBALS['app']->Session->IsAdmin() || $GLOBALS['app']->Session->IsSuperAdmin())) ? null : $OwnerID));
				foreach ($parents as $parent) {
					$parentCombo->AddOption(preg_replace("[^A-Za-z0-9\ ]", '', $parent['title']), $parent['id']);
				}
				$parentCombo->SetDefault($LinkID);
				$parentCombo->setTitle(_t('FLASHGALLERY_GALLERY'));
				$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"LinkID\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$parentCombo->Get()."</td></tr>";
					
				// Title
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == "Title") {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$title = (isset($pageInfo['title'])) ? $pageInfo['title'] : '';
				$titleEntry =& Piwi::CreateWidget('Entry', 'title', $title);
				$titleEntry->SetTitle(_t('FLASHGALLERY_TITLE'));
				$titleEntry->SetStyle('direction: ltr; width: 300px;');
				$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"title\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$titleEntry->Get()."</td></tr>";

				// Description
				if ($account === true) {
					$form_content .= "<input type=\"hidden\" value=\"\" name=\"description\">";
				} else {
					$helpString = '';
					foreach($helpContent as $help) {		            
						if ($help[0] == "Image Caption") {
							$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
							if ($help[1]) {
								if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
									$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
								}
								$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
								$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
								$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
								$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
								if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
									$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
								}
								$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
								if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
									$helpString .= "</a>";
								}
							}
						}
					}
					$content = (isset($pageInfo['description'])) ? $pageInfo['description'] : '';
					$editor =& $GLOBALS['app']->LoadEditor('FlashGallery', 'description', $content, false);
					$editor->TextArea->SetStyle('width: 100%;');
					$editor->SetWidth('490px');
					//$editor->SetWidth('100%');
					$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"propertyparentDescription\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$editor->Get()."</td></tr>";
				}
				
				// Image
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == "Image") {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$image = (isset($pageInfo['image'])) ? $pageInfo['image'] : '';
				$image_src = $GLOBALS['app']->getDataURL() . 'files'.$xss->filter($pageInfo['image']);
				$image_preview = '';
				if ($image != '' && file_exists($image_src)) { 
					$image_preview .= "<br /><img border=\"0\" src=\"".$image_src."\" width=\"80\"".(strtolower(substr($image, -3)) == 'gif' || strtolower(substr($image, -3)) == 'png' || strtolower(substr($image, -3)) == 'bmp' ? ' height="80"' : '')." align=\"left\" style=\"padding: 5px;\">";
				}
				$imageScript = "<script type=\"text/javascript\">addFileToPost('FlashGallery', 'NULL', 'NULL', 'main_image', 'Image', 1, 500, 34);</script>";
				$imageHidden =& Piwi::CreateWidget('HiddenEntry', 'Image', $image);
		        $form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"Image\"><nobr>".$helpString."</nobr></label>".$image_preview."</td><td class=\"syntacts-form-row\"><div id=\"main_image\" style=\"float: left; width: 500px;\"></div>".$imageScript.$imageHidden->Get()."</td></tr>";

				// URL Type
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == "URL Type") {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$urlTypeOptions = "<select NAME=\"url_type\" SIZE=\"1\" onChange=\"if (this.value == 'internal') {toggleYes('internalURLInfo'); toggleNo('externalURLInfo'); toggleNo('URLTargetInfo');};  if (this.value == 'external') {toggleNo('internalURLInfo'); toggleYes('externalURLInfo'); toggleYes('URLTargetInfo');};\">";
				$urlTypeOptions .= "<option value=\"internal\" ".((isset($pageInfo['url']) && strpos($pageInfo['url'], "://") === false) || $ID == "" ? " selected" : '').">Internal</option>";
				$urlTypeOptions .= "<option value=\"external\" ".(isset($pageInfo['url']) && strpos($pageInfo['url'], "://") === true ? " selected" : '').">External</option>";
				$urlTypeOptions .= "</select>";
				$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"url_type\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$urlTypeOptions."</td></tr>";

				// Internal URL
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == "Internal URL") {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$post_url = (isset($pageInfo['url']) && strpos($pageInfo['url'], "://") === false ? $pageInfo['url'] : 'javascript:void(0);');
				$urlListCombo =& Piwi::CreateWidget('Combo', 'internal_url');
				$urlListCombo->setID('internal_url');
				$urlListCombo->AddOption("Open Image in New Window", "javascript:void(0);");
				if ($account === false) {
					$sql = '
						SELECT
							[id], [menu_type], [title], [url], [visible]
						FROM [[menus]]
						ORDER BY [menu_type] ASC, [title] ASC';
					
					$menus = $GLOBALS['db']->queryAll($sql);
					if (Jaws_Error::IsError($menus)) {
						return $menus;
					}
					if (is_array($menus)) {
						foreach ($menus as $menu => $m) {
							if ($m['visible'] == 0) {
								$urlListCombo->AddOption("<i>".$m['menu_type']." : ".$m['title']."</i>", $m['url']);
							} else {
								$urlListCombo->AddOption($m['menu_type']." : ".$m['title'], $m['url']);
							}
						}
					}
				}
				$urlListCombo->setDefault($post_url);
				$form_content .= "<tr style=\"display: ".(isset($pageInfo['url']) && strpos($pageInfo['url'], "://") === true && $ID != "" ? "none; " : '; ')."\" id=\"internalURLInfo\"><td class=\"syntacts-form-row\"><label for=\"internal_url\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$urlListCombo->Get()."</td></tr>";

				// External URL
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == "External URL") {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$externalUrl = (isset($pageInfo['url']) && strpos($pageInfo['url'], "://") === true ? $pageInfo['url'] : 'http://');
				$externalUrlEntry =& Piwi::CreateWidget('Entry', 'external_url', $externalUrl);
				$externalUrlEntry->SetTitle("External URL");
				$externalUrlEntry->SetStyle('direction: ltr; width: 300px;');
				$form_content .= "<tr style=\"display: ".((isset($pageInfo['url']) && strpos($pageInfo['url'], "://") === false) || $ID == "" ? "none; " : '; ')."\" id=\"externalURLInfo\"><td class=\"syntacts-form-row\"><label for=\"external_url\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$externalUrlEntry->Get()."</td></tr>";

				// URL Target
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == "URL Target") {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$urlTarget = (isset($pageInfo['url_target'])) ? $pageInfo['url_target'] : '_blank';
				$urlTargetCombo =& Piwi::CreateWidget('Combo', 'url_target');
				$urlTargetCombo->AddOption("Opens in the same window", '_self');
				$urlTargetCombo->AddOption("Opens in a new window", '_blank');
				$urlTargetCombo->SetDefault($urlTarget);
				$urlTargetCombo->setTitle("URL Target");
				$form_content .= "<tr style=\"display: ".((isset($pageInfo['url']) && strpos($pageInfo['url'], "://") === false) || $ID == "" ? "none; " : '; ')."\" id=\"URLTargetInfo\"><td class=\"syntacts-form-row\"><label for=\"url_type\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$urlTargetCombo->Get()."</td></tr>";
			
				if ($error != '') {
					$stpl->SetVariable('content', $error);
				} else {
					$stpl->SetVariable('content', $form_content);
				}
				$stpl->ParseBlock('form');
				$page = $stpl->Get();
			
			} else {
				$page = _t('FLASHGALLERY_ERROR_ASPPAGE_NOT_RETRIEVED', $snoopy->error)."\n";
			}

			$tpl->SetVariable('content', $page);
		} else {
			return new Jaws_Error(_t('FLASHGALLERY_ERROR_ASPPAGE_NOT_RETRIEVED'), _t('FLASHGALLERY_NAME'));
		}
		
        $tpl->ParseBlock('gadget_page');
        return $tpl->Get();
    }

    /**
     * We are on the A_form_post page
     *
     * @access public
     * @return string
     */
    function A_form_post($account = false)
    {

		if ($account === false) {
			return $this->form_post();
		} else {
			return $this->form_post(true);
		}

    }

    /**
     * ShowEmbedWindow
     *
     * @access public
     * @return string
     */
    function ShowEmbedWindow()
    {
		$user_admin = $GLOBALS['app']->LoadGadget('Users', 'AdminHTML');
		return $user_admin->ShowEmbedWindow('FlashGallery', 'OwnFlashGallery');
    }

    /**
     * sets GB root with DPATH
     *
     * @access public
     * @return javascript string
     */
    function SetGBRoot()
    {
		// Make output a real JavaScript file!
		header('Content-type: text/javascript'); 
		echo "var GB_ROOT_DIR = \"data/greybox/\";";
	}

    /**
     * Quick add form
     *
     * @access public
     * @return XHTML string
     */
    function GetQuickAddForm($account = false)
    {
		// check session
		if ($account === false) {
			$GLOBALS['app']->Session->CheckPermission('FlashGallery', 'default');
		} else {
			$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
			if (!$GLOBALS['app']->Session->GetPermission('FlashGallery', 'default')) {
				if (!$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'FlashGallery', 'OwnFlashGallery')) {
		            $GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
					$userHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
					return $userHTML->DefaultAction();
				}
			}
		}
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');

		require_once JAWS_PATH . 'include/Jaws/Template.php';
        $tpl = new Jaws_Template('gadgets/FlashGallery/templates/');
        $tpl->Load('QuickAddForm.html');
        $tpl->SetBlock('form');

		$request =& Jaws_Request::getInstance();
		$method = $request->get('method', 'get');
		if (empty($method) || $method == 'AddGadget') {
			$method = 'AddFlashGallery';
		}
		$form_content = '';
		switch($method) {
			case "AddFlashGallery": 
			case "UpdateFlashGallery": 
				$form_content = $this->form($account);
				break;
			case "AddPost": 
			case "UpdatePost": 
				$form_content = $this->A_form($account);
				break;
		}
		if (Jaws_Error::IsError($form_content)) {
			$form_content = $form_content->GetMessage();
		}
        
		$direction = _t('GLOBAL_LANG_DIRECTION');
        $dir  = $direction == 'rtl' ? '.' . $direction : '';
        $brow = $GLOBALS['app']->GetBrowserFlag();
        $brow = empty($brow)? '' : '.'.$brow;
        $base_url = $GLOBALS['app']->GetSiteURL().'/';

        $tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . "/");
        //$tpl->SetVariable('DPATH', JAWS_DPATH);
        $tpl->SetVariable('stub', (JAWS_SCRIPT == 'admin' ? 'FlashGalleryAdminAjax' : 'FlashGalleryAjax'));
        $tpl->SetVariable('BASE_URL', $base_url);
        $tpl->SetVariable('.dir', $dir);
        $tpl->SetVariable('.browser', $brow);
		$tpl->SetVariable('base_script', BASE_SCRIPT);
		$tpl->SetVariable('account', (JAWS_SCRIPT == 'admin' ? '' : 'account_'));
		$tpl->SetVariable('script', (JAWS_SCRIPT == 'admin' ? 'script' : 'client_script'));
											
		$tpl->SetVariable('content', $form_content);
		
		$tpl->ParseBlock('form');
        return $tpl->Get();
	}

}
