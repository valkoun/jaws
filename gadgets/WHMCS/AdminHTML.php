<?php
/**
 * WHMCS Gadget Admin
 *
 * @category   GadgetAdmin
 * @package    WHMCS
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2012 Alan Valkoun
 */
class WHMCSAdminHTML extends Jaws_GadgetHTML
{
    /**
     * Constructor
     *
     * @access public
     */
    function WHMCSAdminHTML()
    {
        $this->Init('WHMCS');
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
        $actions = array('Admin','form','form_post','Settings');
        if (!in_array($selected, $actions)) {
            $selected = 'Admin';
        }

        require_once JAWS_PATH . 'include/Jaws/Widgets/Menubar.php';
        $menubar = new Jaws_Widgets_Menubar();
        if ($GLOBALS['app']->Session->GetPermission('WHMCS', 'ManageWHMCSClients')) {
            $menubar->AddOption('Admin', _t('WHMCS_MENU_ADMIN'),
                                'admin.php?gadget=WHMCS&amp;action=Admin', STOCK_OPEN);
        }
		if ($GLOBALS['app']->Session->GetPermission('WHMCS', 'Settings')) {
            $menubar->AddOption('Settings', _t('WHMCS_MENU_SETTINGS'),
                                'admin.php?gadget=WHMCS&amp;action=Settings');
		}

        $menubar->Activate($selected);

        return $menubar->Get();
    }

    /**
     * Builds the basic datagrid view
     *
     * @access  private
     * @return  string   XHTML of datagrid
     */
    function DataGrid()
    {
        $model = $GLOBALS['app']->LoadGadget('WHMCS', 'AdminModel');
		/*
		$OwnerID = 0;
		if (BASE_SCRIPT == 'index.php') {
			$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');
		}
		*/
        $sql = 'SELECT COUNT([id]) FROM [[users_whmcsclients]]';
        $res = $GLOBALS['db']->queryOne($sql);
        $total = (Jaws_Error::IsError($res) ? 0 : $res);

        $grid =& Piwi::CreateWidget('DataGrid', array());
        $grid->TotalRows($total);
        $grid->SetStyle('width: 100%;');
        $grid->SetID('whmcs_client_datagrid');
        $grid->SetAction('next', 'javascript:nextWHMCSClientValues();');
        $grid->SetAction('prev', 'javascript:previousWHMCSClientValues();');
        //$grid->useMultipleSelection();
        $grid->AddColumn(Piwi::CreateWidget('Column', 'ID'));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_NAME')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('WHMCS_WHMCS_ID')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('WHMCS_LAST_UPDATE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_ACTIONS')));

        return $grid->Get();
    }
    
    /**
     * Returns an array with clients found
     *
     * @access  public
     * @param   string  $status  Status of client(s) we want to display
     * @param   string  $search  Keyword (title/description) of clients we want to look for
     * @param   int     $limit   Data limit
     * @return  array   Data
     */
    function GetClients($status, $search, $limit)
    {
		require_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'User.php';
		$jUser = new Jaws_User;
        $model = $GLOBALS['app']->LoadGadget('WHMCS', 'AdminModel');
		$pages = $model->SearchClients($status, $search, $limit);
        if (Jaws_Error::IsError($pages)) {
            return array();
        }

        $data    = array();
		$edit_url    = BASE_SCRIPT . '?gadget=WHMCS&amp;action=form&amp;id=';
		$date = $GLOBALS['app']->loadDate();
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');

        foreach ($pages as $page) {
			$pageData = array();
			$userInfo = $jUser->GetUserInfoById($page['user_id'], true, true, true, true);
			if (!Jaws_Error::IsError($userInfo) && isset($userInfo['id']) && !empty($userInfo['id'])) {
				$pageData['id'] = $userInfo['id'];
				$pageData['title'] = $userInfo['username'];
				if (isset($userInfo['company']) && !empty($userInfo['company'])) {
					$pageData['title'] = $userInfo['company'];
				} else if (isset($userInfo['fname']) && !empty($userInfo['fname']) && isset($userInfo['lname']) && !empty($userInfo['lname'])) {
					$pageData['title'] = $userInfo['fname']." ".$userInfo['lname'];
				} else if (isset($userInfo['nickname']) && !empty($userInfo['nickname'])) {
					$pageData['title'] = $userInfo['nickname'];
				}
				$pageData['whmcsid']  = $page['whmcs_id'];
				$pageData['date']  = $date->Format($page['updated']);
				$actions = '';
				if ($this->GetPermission('ManageAdParents')) {
					/*
					$link =& Piwi::CreateWidget('Link', _t('GLOBAL_EDIT'),
												$edit_url.$page['id']);
					$actions.= $link->Get().'&nbsp;';
					*/
					
					$link =& Piwi::CreateWidget('Link', _t('GLOBAL_DELETE'),
												"javascript: if (confirm('"._t('GLOBAL_CONFIRM_DELETE', _t('WHMCS_WHMCS_USER'))."')) ".
												"deleteClient('".$page['id']."');");
					$actions.= $link->Get().'&nbsp;';
				}

				$pageData['actions'] = $actions;
				$pageData['__KEY__'] = $page['id'];
				$data[] = $pageData;
			}
		}
        return $data;
    }

    /**
     * Display the default administration page which currently lists all clients
     *
     * @access public
     * @return string
     */
    function Admin()
    {
		$request =& Jaws_Request::getInstance();
		$action = $request->get('action', 'get');
				
		$GLOBALS['app']->Session->CheckPermission('WHMCS', 'ManageWHMCSClients');
        $tpl = new Jaws_Template('gadgets/WHMCS/templates/');
        $tpl->Load('admin.html');
        $tpl->SetBlock('clients_admin');
        

		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=CustomPage&action=account_SetGBRoot');
		$GLOBALS['app']->Layout->AddScriptLink(BASE_SCRIPT . '?gadget=WHMCS&amp;action=Ajax&amp;client=all&amp;stub=WHMCSAdminAjax');
		$GLOBALS['app']->Layout->AddScriptLink(BASE_SCRIPT . '?gadget=WHMCS&amp;action=AjaxCommonFiles');
		$GLOBALS['app']->Layout->AddScriptLink('gadgets/WHMCS/resources/script.js');
		$GLOBALS['app']->Layout->AddScriptLink('libraries/js/admin.js');
		$tpl->SetVariable('menubar', $this->MenuBar($action));
		$account_prefix = '';
		$base_url = BASE_SCRIPT;
        
		$tpl->SetVariable('account', $account_prefix);
		$tpl->SetVariable('base_script', $base_url);

        $tpl->SetVariable('grid', $this->DataGrid());

        $toolBar   =& Piwi::CreateWidget('HBox');

        $deleteAll =& Piwi::CreateWidget('Button', 'deleteAllClients',
                                         _t('GLOBAL_DELETE'),
                                         STOCK_DELETE);
        $deleteAll->AddEvent(ON_CLICK,
                             "javascript: massiveDelete('"._t('WHMCS_CONFIRM_MASIVE_DELETE_WHMCS_USER')."');");

        $toolBar->Add($deleteAll);

        $tpl->SetVariable('tools', $toolBar->Get());
                
        $statusEntry =& Piwi::CreateWidget('HiddenEntry', 'status', '');
		$statusEntry->SetID('status');
		$tpl->SetVariable('status_field', $statusEntry->Get());
		
		/*
		//Status filter
		$status = '';
		$statusCombo =& Piwi::CreateWidget('Combo', 'status');
		$statusCombo->setId('status');
		$statusCombo->AddOption('&nbsp;', '');
		$statusCombo->AddOption(_t('ADS_PUBLISHED'), 'Y');
		$statusCombo->AddOption(_t('ADS_DRAFT'), 'N');
		$statusCombo->SetDefault($status);
		$statusCombo->AddEvent(ON_CHANGE, 'javascript: searchAdParent();');
		$tpl->SetVariable('status', _t('ADS_ACTIVE').':');
		$tpl->SetVariable('status_field', $statusCombo->Get());
        */
		
		// Free text search
        $searchButton =& Piwi::CreateWidget('Button', 'searchButton', _t('GLOBAL_SEARCH'), STOCK_SEARCH);
        $searchButton->AddEvent(ON_CLICK, 'javascript: searchClient();');
        $tpl->SetVariable('search', $searchButton->Get());

        $search = '';
        $searchEntry =& Piwi::CreateWidget('Entry', 'search', $search);
        $searchEntry->SetStyle('zwidth: 100%;');
        $tpl->SetVariable('search_field', $searchEntry->Get());

        $tpl->SetVariable('entries', $this->Datagrid());

		$addPage =& Piwi::CreateWidget('Button', 'add_client', _t('WHMCS_ADD_WHMCS_USER'), STOCK_ADD);
		$addPage->AddEvent(ON_CLICK, "javascript: window.location = '".$base_url."?gadget=WHMCS&amp;action=".$account_prefix."form';");
		$tpl->SetVariable('add_client', $addPage->Get());

        $tpl->ParseBlock('clients_admin');

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
		// check session
		$GLOBALS['app']->Session->CheckPermission('WHMCS', 'ManageWHMCSClients');

		// document dependencies
		
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$model = $GLOBALS['app']->LoadGadget('WHMCS', 'Model');
		$request =& Jaws_Request::getInstance();
		$gather = array('action', 'id');
		$get = $request->get($gather, 'get');

		// initialize template
		$tpl = new Jaws_Template('gadgets/WHMCS/templates/');
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

		// account differences
		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=CustomPage&action=account_SetGBRoot');
		$this->AjaxMe('script.js');
		$tpl->SetVariable('menubar', $this->MenuBar($get['action']));
		$GLOBALS['app']->Layout->AddScriptLink('libraries/js/admin.js');			
		$submit_vars['ACTIONPREFIX'] = "";
		$submit_vars['CLOSE_BUTTON'] = "location.href='" . BASE_SCRIPT . "?gadget=WHMCS&amp;action=Admin';";
		$syntactsUrl = $GLOBALS['app']->getSyntactsAdminHTMLUrl();
		$OwnerID = 0;
		$base_url = BASE_SCRIPT;
		$tpl->SetVariable('workarea-style', "style=\"float: left; margin-top: 30px;\" ");

		// syntacts page
		if ($syntactsUrl) {
			// snoopy
			include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
			$snoopy = new Snoopy('WHMCS');
			$submit_url = $syntactsUrl;
			
			if($snoopy->fetch($submit_url)) {
				//while(list($key,$val) = each($snoopy->headers))
					//echo $key.": ".$val."<br>\n";
				//echo "<p>\n";
				
				//$page = "<PRE>".htmlspecialchars($snoopy->results)."</PRE>\n";
				$error = '';
				$form_content = '';
				
				// initialize template
				$stpl = new Jaws_Template();
		        $stpl->LoadFromString($snoopy->results);
		        $stpl->SetBlock('form');
				if (!is_null($get['id'])) {
					// send page records
					$pageInfo = $model->GetClient($get['id']);
					if (!Jaws_Error::IsError($pageInfo)) {
						$submit_vars['SUBMIT_BUTTON'] = "<input type='Submit' name='Submit' value='Update'>";
					} else {
						//$error = _t('ADS_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403'));												
						return new Jaws_Error(_t('WHMCS_ERROR_WHMCS_USER_NOT_FOUND'), _t('WHMCS_NAME'));
					}
				} else {
					$submit_vars['SUBMIT_BUTTON'] = "<input type='Submit' name='Submit' value='Add'>";
				}

				// send requesting URL to syntacts
				$stpl->SetVariable('HTTP_REFERER', $GLOBALS['app']->GetSiteURL());
				//$stpl->SetVariable('DPATH', JAWS_DPATH);
				$stpl->SetVariable('actionprefix', $submit_vars['ACTIONPREFIX']);
				$stpl->SetVariable('gadget', 'WHMCS');
				$stpl->SetVariable('CLOSE_BUTTON', $submit_vars['CLOSE_BUTTON']);
				$stpl->SetVariable('SUBMIT_BUTTON', $submit_vars['SUBMIT_BUTTON']);
				$stpl->SetVariable('controller', $base_url);
				
				// Get Help documentation
				$help_url = $GLOBALS['app']->getSyntactsAdminHTMLUrl("WHMCS/admin_WHMCS_form_help", 'txt');
				$snoopy = new Snoopy('WHMCS');
		
				if($snoopy->fetch($help_url)) {
					$helpContent = Jaws_Utils::split2D($snoopy->results);
				}
								
				// Hidden elements
				$ID = (isset($pageInfo['id'])) ? $pageInfo['id'] : '';
				$idHidden =& Piwi::CreateWidget('HiddenEntry', 'ID', $ID);
		        $form_content .= $idHidden->Get()."\n";

				$fuseaction = (isset($pageInfo['id'])) ? 'EditClient' : 'AddClient';
				$fuseactionHidden =& Piwi::CreateWidget('HiddenEntry', 'fuseaction', $fuseaction);
		        $form_content .= $fuseactionHidden->Get()."\n";
				
				/*
				// Active
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('WHMCS_ACTIVE')) {
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
				$activeCombo->setTitle(_t('WHMCS_ACTIVE'));
				$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"Active\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$activeCombo->Get()."</td></tr>";
				*/
				
				if ($error != '') {
					$stpl->SetVariable('content', $error);
				} else {
					$stpl->SetVariable('content', $form_content);
		        }
				$stpl->ParseBlock('form');
				$page = $stpl->Get();
			} else {
				$page = _t('WHMCS_ERROR_ASPPAGE_NOT_RETRIEVED', $snoopy->error)."\n";
			}
			
			$tpl->SetVariable('content', $page);
		} else {
			return new Jaws_Error(_t('WHMCS_ERROR_ASPPAGE_NOT_RETRIEVED'), _t('ADS_NAME'));
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
    function form_post()
    {
		// check session
		$GLOBALS['app']->Session->CheckPermission('WHMCS', 'default');

		require_once JAWS_PATH . 'include/Jaws/Header.php';
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');        
		
		$request =& Jaws_Request::getInstance();
		$fuseaction = $request->get('fuseaction', 'post');
		
		$get  = $request->get(array('fuseaction', 'linkid', 'id'), 'get');
        if (is_null($fuseaction)) {
			$fuseaction = $get['fuseaction'];
		}
        
		$adminModel = $GLOBALS['app']->LoadGadget('WHMCS', 'AdminModel');
		$model = $GLOBALS['app']->LoadGadget('WHMCS', 'Model');

        if (!empty($fuseaction)) {		
			switch($fuseaction) {
                case "AddClient": 
						$keys = array(
							'user_id'
						);
						$postData = $request->getRaw($keys, 'post');
						//foreach($postData as $key => $value) {
							//echo $key."=".$value."\n";
						//}
						$result = $adminModel->AddClient(
							$postData['user_id'] 
						);
						if (!Jaws_Error::IsError($result)) {
					        // declare result as ok for later
							$result1 = true;
						} else {
							/*
							$url = "javascript:history.go(-1)";
							$link =& Piwi::CreateWidget('Link', "Please go back and try again",
														$url);
							return $result->GetMessage().'<br />'.$link->Get();
							*/
							$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
							//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
						}
						break;
                case "EditClient": 
						$keys = array(
							'ID', 'user_id'
						);
						$postData = $request->getRaw($keys, 'post');
						if (isset($postData['ID'])) {
							// add OwnerID if no permissions
							if ($GLOBALS['app']->Session->GetPermission('WHMCS', 'ManageWHMCSClients')) {
								$OwnerID = null;			
								$result = $adminModel->UpdateClient(
									(int)$postData['ID'], $postData['user_id']
								);
							}
						} else {
							$GLOBALS['app']->Session->PushLastResponse(_t('WHMCS_ERROR_WHMCS_USER_NOT_UPDATED'), RESPONSE_ERROR);
							//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
						}
						if (!Jaws_Error::IsError($result)) {
							$result1 = true;
						} else {
							/*
							$url = "javascript:history.go(-1)";
							$link =& Piwi::CreateWidget('Link', "Please go back and try again",
														$url);
							return $result->GetMessage().'<br />'.$link->Get();
							*/
							$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
							//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
						}
                       break;
                case "DeleteClient": 
				        //$keys = array('idarray', 'ID', 'xcount');
				        $keys = array('ID');
						$postData = $request->getRaw($keys, 'post');
						$id = $postData['ID'];
						if (is_null($id)) {
							$id = $get['id'];
						}
						// delete each ID
						if ($id) {
							// check OwnerID if no permissions
							if ($GLOBALS['app']->Session->GetPermission('WHMCS', 'ManageWHMCSClients')) {
								$result = $adminModel->DeleteClient($id);
							}
						}						
						if (!Jaws_Error::IsError($result)) {
							$result2 = true;
						} else {
							/*
							$url = "javascript:history.go(-1)";
							$link =& Piwi::CreateWidget('Link', "Please go back and try again",
														$url);
							return $result->GetMessage().'<br />'.$link->Get();
							*/
							$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
							//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
						}
						break;
            }
			
			// Send us to the appropriate page
			if ($result1 === true) {
				if (is_numeric($result)) {
					$redirect = BASE_SCRIPT . '?gadget=WHMCS&action=Admin';
				} else {
					$redirect = BASE_SCRIPT . '?gadget=WHMCS&action=Admin';
				}
				Jaws_Header::Location($redirect);
			} else {
				Jaws_Header::Location(BASE_SCRIPT . '?gadget=WHMCS');
			}
		} else {
			Jaws_Header::Location(BASE_SCRIPT . '?gadget=WHMCS');
		}

    }

    /**
     * Edit settings
     *
     * @access  public
     * @return  string HTML content
     */
    function Settings()
    {
		$GLOBALS['app']->Layout->AddHeadLink('gadgets/ControlPanel/resources/style.css', 'stylesheet', 'text/css');
        $this->AjaxMe('script.js');
        $tpl = new Jaws_Template('gadgets/WHMCS/templates/');
        $tpl->Load('Properties.html');
        $tpl->SetBlock('Properties');

		include_once JAWS_PATH . 'include/Jaws/Widgets/FieldSet.php';
        
		$form =& Piwi::CreateWidget('Form', BASE_SCRIPT, 'post');
        $form->Add(Piwi::CreateWidget('HiddenEntry', 'gadget', 'WHMCS'));
        $form->Add(Piwi::CreateWidget('HiddenEntry', 'action', 'SaveSettings'));

		$whmcs_url = $GLOBALS['app']->Registry->Get('/gadgets/WHMCS/whmcs_url');
		$whmcs_api = $GLOBALS['app']->Registry->Get('/gadgets/WHMCS/whmcs_api');
		$whmcs_auth = $GLOBALS['app']->Registry->Get('/gadgets/WHMCS/whmcs_auth');
        		
		$api_fieldset = new Jaws_Widgets_FieldSet(_t('WHMCS_SETTINGS_API'));
		$api_fieldset->SetTitle('vertical');

		// API URL
		$api_url =& Piwi::CreateWidget('Entry', 'whmcs_url', $whmcs_url);
		$api_url->SetTitle(_t('WHMCS_WHMCS_API_URL'));
		$api_url->SetStyle('direction: ltr; width: 300px;');
		$api_fieldset->Add($api_url);
		
		// API User
		$api_user =& Piwi::CreateWidget('Entry', 'whmcs_api', $whmcs_api);
		$api_user->SetTitle(_t('WHMCS_WHMCS_API_USER'));
		$api_user->SetStyle('direction: ltr; width: 300px;');
		$api_fieldset->Add($api_user);
		
		// API Auth
		$api_auth =& Piwi::CreateWidget('Entry', 'whmcs_auth', $whmcs_auth);
		$api_auth->SetTitle(_t('WHMCS_WHMCS_API_AUTH'));
		$api_auth->SetStyle('direction: ltr; width: 300px;');
		$api_fieldset->Add($api_auth);
		
		$form->Add($api_fieldset);
		
		$buttons =& Piwi::CreateWidget('HBox');
		$buttons->SetStyle(_t('GLOBAL_LANG_DIRECTION')=='rtl'?'float: left;' : 'float: right;');

		$save =& Piwi::CreateWidget('Button', 'save', _t('GLOBAL_SAVE'), STOCK_SAVE);
		$save->AddEvent(ON_CLICK, 'javascript: saveSettings();');

		$buttons->Add($save);
		$form->Add($buttons);

		$tpl->SetVariable('form', $form->Get());
        $tpl->SetVariable('menubar', $this->MenuBar('Settings'));

        $tpl->ParseBlock('Properties');

        return $tpl->Get();
    }
}
