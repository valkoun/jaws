<?php
/**
 * Forms Gadget Admin
 *
 * @category   GadgetAdmin
 * @package    Forms
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class FormsAdminHTML extends Jaws_GadgetHTML
{
    /**
     * Constructor
     *
     * @access public
     */
    function FormsAdminHTML()
    {
        $this->Init('Forms');
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
        $actions = array('Admin','form','form_post','view','A_form','A_form_post','Settings');
        if (!in_array($selected, $actions)) {
            $selected = 'Admin';
        }

        require_once JAWS_PATH . 'include/Jaws/Widgets/Menubar.php';
        $menubar = new Jaws_Widgets_Menubar();
        if ($GLOBALS['app']->Session->GetPermission('Forms', 'ManageForms')) {
            $menubar->AddOption('Admin', _t('FORMS_MENU_ADMIN'),
                                'admin.php?gadget=Forms&amp;action=Admin', STOCK_DOCUMENTS);
        }
        if ($GLOBALS['app']->Session->GetPermission('Forms', 'default')) {
			if (strtolower($selected) != "admin" && (strtolower($selected) == 'view' || strtolower($selected) == 'form' || strtolower($selected) == 'form_post')) {
				$menubar->AddOption($selected, _t('FORMS_MENU_FORM'),
	                                'javascript:void(0);', STOCK_NEW);
			}
            if (strtolower($selected) != "admin" && (strtolower($selected) == 'a_form' || strtolower($selected) == 'a_form_post')) {
				$menubar->AddOption($selected, _t('FORMS_MENU_POST'),
	                                'javascript:void(0);', STOCK_EDIT);
			}
			if ($GLOBALS['app']->Session->GetPermission('Forms', 'ManageForms')) {
	            $menubar->AddOption('Settings', _t('FORMS_MENU_PROPERTIES'),
	                                'admin.php?gadget=Forms&amp;action=Settings', STOCK_DOCUMENTS);
	        }
			
		}

		$request =& Jaws_Request::getInstance();
		$id = $request->get('id', 'get');
		if (strtolower($selected) == "form" && empty($id)) {
		} else {
			if ($GLOBALS['app']->Session->GetPermission('Forms', 'ManageForms')) {
				$menubar->AddOption('Add', '',
									'admin.php?gadget=Forms&amp;action=form', STOCK_ADD);
			}
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
        $model = $GLOBALS['app']->LoadGadget('Forms', 'AdminModel');
		$OwnerID = 0;
		if (BASE_SCRIPT == 'index.php') {
			$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');
		}
        $sql = 'SELECT COUNT([id]) FROM [[forms]] WHERE [ownerid] = '.$OwnerID;
        $res = $GLOBALS['db']->queryOne($sql);
        $total = (Jaws_Error::IsError($res) ? 0 : $res);

        $grid =& Piwi::CreateWidget('DataGrid', array());
        $grid->TotalRows($total);
        $grid->SetStyle('width: 100%;');
        $grid->SetID('forms_datagrid');
        $grid->SetAction('next', 'javascript:nextFormsValues();');
        $grid->SetAction('prev', 'javascript:previousFormsValues();');
        //$grid->useMultipleSelection();
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_TITLE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', ''));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('FORMS_ACTIVE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('FORMS_LAST_UPDATE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_ACTIONS')));

        return $grid->Get();
    }

    /**
     * Returns an array with forms found
     *
     * @access  public
     * @param   string  $status  Status of form(s) we want to display
     * @param   string  $search  Keyword (title/description) of forms we want to look for
     * @param   int     $limit   Data limit
     * @return  array   Data
     */
    function GetForms($status, $search, $limit)
    {
        $model = $GLOBALS['app']->LoadGadget('Forms', 'AdminModel');
		if ($GLOBALS['app']->Session->GetPermission('Forms', 'ManageForms')) {
	        $pages = $model->SearchForms($status, $search, $limit);
		} else {
			$pages = $model->SearchForms($status, $search, $limit, $GLOBALS['app']->Session->GetAttribute('user_id'));
		}
        //$pages = $model->SearchForms($status, $search, $limit);
        if (Jaws_Error::IsError($pages)) {
            return array();
        }

        $data    = array();
        $edit_url    = BASE_SCRIPT . '?gadget=Forms&amp;action=view&amp;id=';
        $date = $GLOBALS['app']->loadDate();
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');

        foreach ($pages as $page) {
			$pageData = array();
			$pageData['title'] = '<a href="'.$edit_url.$page['id'].'">'.$page['title'].'</a>';
			$preview_url = $GLOBALS['app']->Map->GetURLFor('Forms', 'Form', array('id' => $page['fast_url']));
			$pageData['furl']  = '<a href="'.$preview_url.'">View This Form</a>';

			if ($page['active'] == 'Y') {
				$pageData['active'] = _t('FORMS_PUBLISHED');
			} else {
				$pageData['active'] = _t('FORMS_DRAFT');
			}
			$pageData['date']  = $date->Format($page['updated']);
			$actions = '';
			if ($this->GetPermission('ManageForms')) {
				$link =& Piwi::CreateWidget('Link', _t('GLOBAL_EDIT'),
											$edit_url.$page['id'],
											STOCK_EDIT);
				$actions.= $link->Get().'&nbsp;';
			}

			if ($this->GetPermission('ManageForms')) {
				$link =& Piwi::CreateWidget('Link', _t('GLOBAL_DELETE'),
											"javascript: if (confirm('"._t('GLOBAL_CONFIRM_DELETE', _t('FORMS_FORM'))."')) ".
											"deleteForm('".$page['id']."');",
											"images/ICON_delete2.gif");
				$actions.= $link->Get().'&nbsp;';
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
    function Admin()
    {
        $this->CheckPermission('default');
        $this->AjaxMe('script.js');

        $tpl = new Jaws_Template('gadgets/Forms/templates/');
        $tpl->Load('admin.html');
        $tpl->SetBlock('forms_admin');
        
		$request =& Jaws_Request::getInstance();
		$action = $request->getRaw('action', 'get');
        $tpl->SetVariable('menubar', $this->MenuBar($action));
        
		$tpl->SetVariable('base_script', BASE_SCRIPT);

        $tpl->SetVariable('grid', $this->DataGrid());

        $toolBar   =& Piwi::CreateWidget('HBox');

        $deleteAll =& Piwi::CreateWidget('Button', 'deleteAllForms',
                                         _t('GLOBAL_DELETE'),
                                         STOCK_DELETE);
        $deleteAll->AddEvent(ON_CLICK,
                             "javascript: massiveDelete('"._t('FORMS_CONFIRM_MASIVE_DELETE_FORM')."');");

        $toolBar->Add($deleteAll);

        $tpl->SetVariable('tools', $toolBar->Get());
                
        //Status filter
        $status = '';
        $statusCombo =& Piwi::CreateWidget('Combo', 'status');
        $statusCombo->setId('status');
        $statusCombo->AddOption('&nbsp;', '');
        $statusCombo->AddOption(_t('FORMS_PUBLISHED'), 'Y');
        $statusCombo->AddOption(_t('FORMS_DRAFT'), 'N');
        $statusCombo->SetDefault($status);
        $statusCombo->AddEvent(ON_CHANGE, 'javascript: searchForm();');
        $tpl->SetVariable('status', _t('FORMS_ACTIVE'));
        $tpl->SetVariable('status_field', $statusCombo->Get());

        // Free text search
        $searchButton =& Piwi::CreateWidget('Button', 'searchButton', _t('GLOBAL_SEARCH'), STOCK_SEARCH);
        $searchButton->AddEvent(ON_CLICK, 'javascript: searchForm();');
        $tpl->SetVariable('search', $searchButton->Get());

        $search = '';
        $searchEntry =& Piwi::CreateWidget('Entry', 'search', $search);
        $searchEntry->SetStyle('zwidth: 100%;');
        $tpl->SetVariable('search_field', $searchEntry->Get());

        $tpl->SetVariable('entries', $this->Datagrid());

        $addPage =& Piwi::CreateWidget('Button', 'add_form', _t('FORMS_ADD_FORM'), STOCK_ADD);
        $addPage->AddEvent(ON_CLICK, "javascript: window.location = '".BASE_SCRIPT.'?gadget=Forms&amp;action=form'."';");
        $tpl->SetVariable('add_form', $addPage->Get());

        $tpl->ParseBlock('forms_admin');

        return $tpl->Get();
    }


    /**
     * We are on a form page
     *
     * @access public
     * @return string
     */
    function form()
    {
		$GLOBALS['app']->Session->PopLastResponse();
		// check session
        $GLOBALS['app']->Session->CheckPermission('Forms', 'default');

		// document dependencies
        $this->AjaxMe('script.js');

        $GLOBALS['app']->Layout->AddScriptLink('libraries/js/admin.js');

		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
						
		// initialize template
		$tpl = new Jaws_Template('gadgets/Forms/templates/');
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

		$model = $GLOBALS['app']->LoadGadget('Forms', 'Model');
		$request =& Jaws_Request::getInstance();
		$gather = array('action', 'id');
		$get = $request->getRaw($gather, 'get');

        $tpl->SetBlock('gadget_form');

		// menus
		$tpl->SetVariable('menubar', $this->MenuBar($get['action']));

		$tpl->SetVariable('workarea-style', "style=\"float: left;\" ");

		// syntacts page
		$syntactsUrl = $GLOBALS['app']->getSyntactsAdminUrl("Forms/admin_Forms_form");
		if ($syntactsUrl) {
			// snoopy
			include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
			$snoopy = new Snoopy('Forms');
			$submit_url = $syntactsUrl;
			
			if (!empty($get['id'])) {
				// send page records
				$pageInfo = $model->GetForm($get['id']);
		        if (!Jaws_Error::IsError($pageInfo) && ($GLOBALS['app']->Session->GetPermission('Forms', 'ManageForms') || $pageInfo['ownerid'] == $GLOBALS['app']->Session->GetAttribute('user_id'))) {
						$i = 0;
						$j = 0;
						$submit_vars['0:cols'] = 16;
						foreach($pageInfo as $p => $value) {		            
								if ($p == 'description' || $p == 'clause' || $p == 'submit_content') {
									$submit_vars[SYNTACTS_DB ."0:$j:0"] = $this->ParseText($value, 'Forms');
								} else {
									$submit_vars[SYNTACTS_DB ."0:$j:0"] = $xss->filter($value);
								}
								$j++;
								if ($j > $submit_vars['0:cols']) {
									$j=0;
								}
								//echo $xss->filter($value);
								$i++;
						}
						$submit_vars['0:rows'] = $i-1;
						//$submit_vars['0:rows'] = 0;
				} else {
					if (Jaws_Error::IsError($pageInfo)) {
						$page = _t('FORMS_ERROR_ASPPAGE_NOT_RETRIEVED', $pageInfo->GetMessage())."\n";
					} else {
						$page = _t('FORMS_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403'))."\n";
					}
				}
			} else {
				// send highest sort_order
				$sql = "SELECT MAX([sort_order]) FROM [[forms]] ORDER BY [sort_order] DESC";
				$max = $GLOBALS['db']->queryOne($sql);
				if (Jaws_Error::IsError($max)) {
				   return $max;
				}
				$submit_vars[SYNTACTS_DB ."1:0:0"] = $max;
				$submit_vars['1:cols'] = 0;
				$submit_vars['1:rows'] = 0;
			}

			// send requesting URL to syntacts
			$submit_vars['HTTP_REFERER'] = $GLOBALS['app']->GetSiteURL();
			$submit_vars['JAWS_URL'] = $GLOBALS['app']->GetJawsURL() . "/";
			$submit_vars['DPATH'] = '';
			
			// send editor HTML to syntacts
			$content = (isset($pageInfo['description'])) ? $pageInfo['description'] : '';
			$editor =& $GLOBALS['app']->LoadEditor('Forms', 'description', $content, false);
			$editor->TextArea->SetStyle('width: 100%;');
			//$editor->SetWidth('100%');
	
			// send Clause editor HTML to syntacts
			$Clause = (isset($pageInfo['clause'])) ? $pageInfo['clause'] : '';
			$clause_editor =& $GLOBALS['app']->LoadEditor('Forms', 'Clause', $Clause, false);
			$clause_editor->TextArea->SetStyle('width: 100%;');

	        // url list combo
			$post_url = (isset($pageInfo['parent'])) ? $pageInfo['parent'] : 0;
			$urlListCombo =& Piwi::CreateWidget('Combo', 'parent');
	        $urlListCombo->setID('parent');

	        $sql = '
	            SELECT
	                [id], [menu_type], [title], [url], [visible]
	            FROM [[menus]]
				ORDER BY [menu_type] ASC, [title] ASC';
	        
	        $menus = $GLOBALS['db']->queryAll($sql);
			if (Jaws_Error::IsError($menus)) {
				return $menus;
			}
			$urlListCombo->AddOption('Main', 0);
	        if (is_array($menus)) {
				foreach ($menus as $menu => $m) {
					if ($m['visible'] == 0) {
						$urlListCombo->AddOption("<i>".$m['menu_type']." : ".$m['title']."</i>", $m['id']);
					} else {
						$urlListCombo->AddOption($m['menu_type']." : ".$m['title'], $m['id']);
					}
				}
			}
	        $urlListCombo->setDefault($post_url);

			$autodraft = '<script type="text/javascript" language="javascript">Event.observe(window, "load",function(){setTimeout(\'startAutoDrafting();\', 1200000);});</script>';
			$tpl->SetVariable('autodraft', $autodraft);
			
			if (!isset($page)) {
				if($snoopy->submit($submit_url,$submit_vars)) {
					//while(list($key,$val) = each($snoopy->headers))
						//echo $key.": ".$val."<br>\n";
					//echo "<p>\n";
					
					//$page = "<PRE>".htmlspecialchars($snoopy->results)."</PRE>\n";
					$page = $snoopy->results;
					// add Pages forms elements to the results
					$page = str_replace("__JAWS_INTERNALURLS__", $urlListCombo->Get(), $page);
					$page = str_replace("__JAWS_EDITOR__", $editor->Get(), $page);
					$page = str_replace("__JAWS_CLAUSEEDITOR__", $clause_editor->Get(), $page);
				} else {
					$page = _t('FORMS_ERROR_ASPPAGE_NOT_RETRIEVED', $snoopy->error)."\n";
				}
			}

			$tpl->SetVariable('content', $page);
		} else {
			return new Jaws_Error(_t('FORMS_ERROR_ASPPAGE_NOT_RETRIEVED'), _t('FORMS_NAME'));
		}
		
        $tpl->ParseBlock('gadget_form');

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
		$GLOBALS['app']->Session->CheckPermission('Forms', 'default');
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');        
		
		$request =& Jaws_Request::getInstance();
        if (empty($fuseaction)) {
			$fuseaction = $request->getRaw('fuseaction', 'post');
		}
		$get  = $request->getRaw(array('fuseaction', 'pct', 'linkid', 'id'), 'get');
        if (empty($fuseaction)) {
			$fuseaction = $get['fuseaction'];
		}
        
		$adminModel = $GLOBALS['app']->LoadGadget('Forms', 'AdminModel');
		$model = $GLOBALS['app']->LoadGadget('Forms', 'Model');

        if (!empty($fuseaction)) {		
			switch($fuseaction) {
                case "AddForm": 
						$keys = array('sort_order', 'title', 'sm_description', 'description', 
							'Clause', 'Image', 'recipient', 'parent', 'custom_action', 'Active', 'submit_content');
						if (count($params) > 0) {
							$postData = $params;
						} else {	
							$postData = $request->getRaw($keys, 'post');
						}
						//foreach($postData as $key => $value) {
							//echo $key."=".$value."\n";
						//}
						// add OwnerID if no permissions
						if ($GLOBALS['app']->Session->GetPermission('Forms', 'ManageForms') ) {
							$OwnerID = null;
						} else {
							$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
						}
						$result = $adminModel->AddForm($postData['sort_order'], $postData['title'],
										$postData['sm_description'], $postData['description'], $postData['Clause'],
										$postData['Image'], $postData['recipient'], $postData['parent'], 
										$postData['custom_action'], $postData['Active'], $OwnerID, $postData['submit_content']
						);
						if (!Jaws_Error::IsError($result)) {
					        // declare result as ok for later
							$editform = true;
						} else {
							$editform = false;
							$url = "javascript:history.go(-1)";
							$link =& Piwi::CreateWidget('Link', "Please go back and try again",
														$url);
							return $result->GetMessage().'<br />'.$link->Get();
						}
						break;
                case "EditForm": 
						$keys = array('ID', 'sort_order', 'title', 'sm_description', 'description', 
							'Clause', 'Image', 'recipient', 'parent', 'custom_action', 'Active', 'submit_content');
						if (count($params) > 0) {
							$postData = $params;
						} else {	
							$postData = $request->getRaw($keys, 'post');
						}
						if (isset($postData['ID'])) {
							// add OwnerID if no permissions
							if ($GLOBALS['app']->Session->GetPermission('Forms', 'ManageForms')) {
								$result = $adminModel->UpdateForm((int)$postData['ID'], $postData['sort_order'], $postData['title'],
										$postData['sm_description'], $postData['description'], $postData['Clause'],
										$postData['Image'], $postData['recipient'], $postData['parent'], 
										$postData['custom_action'], $postData['Active'], $postData['submit_content']
								);
							} else {
								$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
								$parent = $model->GetForm((int)$postData['ID']);
								if ($OwnerID == $parent['ownerid']) {
									$result = $adminModel->UpdateForm($parent['id'], $postData['sort_order'], $postData['title'],
										$postData['sm_description'], $postData['description'], $postData['Clause'],
										$postData['Image'], $postData['recipient'], $postData['parent'], 
										$postData['custom_action'], $postData['Active'], $postData['submit_content']
									);
								} else {
									return _t('FORMS_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403'));												
								}
							}
						}
						if (!Jaws_Error::IsError($result)) {
							$editform = true;
						} else {
							$editform = false;
							$url = "javascript:history.go(-1)";
							$link =& Piwi::CreateWidget('Link', "Please go back and try again",
														$url);
							return $result->GetMessage().'<br />'.$link->Get();
						}
                       break;
                case "DeleteForm": 
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
							if ($GLOBALS['app']->Session->GetPermission('Forms', 'ManageForms')) {
								$result = $adminModel->DeleteForm($id);
							} else {
								$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
								$parent = $model->GetForm($id);
								if ($OwnerID == $parent['id']) {
									$result = $adminModel->DeleteForm($id);
								} else {
									return _t('FORMS_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403'));												
								}
							}
						}						
						if (!Jaws_Error::IsError($result)) {
							$editform = true;
						} else {
							$editform = false;
							$url = "javascript:history.go(-1)";
							$link =& Piwi::CreateWidget('Link', "Please go back and try again",
														$url);
							return $result->GetMessage().'<br />'.$link->Get();
						}
						break;
                case "AddPost": 
				        $keys = array('sort_order', 'formID', 'title', 'itype', 'required');
						if (count($params) > 0) {
							$postData = $params;
						} else {	
							$postData = $request->getRaw($keys, 'post');
						}
						//foreach($postData as $key => $value) {
						//	echo $key."=".$value."\n";
						//}
						// add OwnerID if no permissions
						if ($GLOBALS['app']->Session->GetPermission('Forms', 'ManageForms')) {
							$OwnerID = null;
						} else {
							$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
						}
						$result = $adminModel->AddPost($postData['sort_order'], $postData['formID'], 
							$postData['title'], $postData['itype'], $postData['required'], $OwnerID);
						if (!Jaws_Error::IsError($result)) {
							$editpost = true;
							$i = 0;
							foreach ($_POST as $p => $pv) {
								if (substr($p, 0, 9) == 'AnswerNew' && !empty($pv)) {
									$res0 = $adminModel->AddAnswer($i, $result, $postData['formID'], $pv, $OwnerID);
									if (Jaws_Error::IsError($res0)) {
										$result2 = false;
										$url = "javascript:history.go(-1)";
										$link =& Piwi::CreateWidget('Link', "Please go back and try again",
																	$url);
										return $res0->GetMessage().'<br />'.$link->Get();
									}
									$i++;
								}
							}
						} else {
							$editpost = false;
							$url = "javascript:history.go(-1)";
							$link =& Piwi::CreateWidget('Link', "Please go back and try again",
														$url);
							return $result->GetMessage().'<br />'.$link->Get();
						}
						break;
                case "EditPost": 
				        $keys = array('ID', 'sort_order', 'formID', 'title', 'itype', 'required');
						if (count($params) > 0) {
							$postData = $params;
						} else {	
							$postData = $request->getRaw($keys, 'post');
						}
						if ($postData['ID']) {
							// check OwnerID if no permissions
							if ($GLOBALS['app']->Session->GetPermission('Forms', 'ManageForms')) {
								$result = $adminModel->UpdatePost((int)$postData['ID'], $postData['sort_order'], $postData['formID'], 
							$postData['title'], $postData['itype'], $postData['required']);
							} else {
								$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
								$post = $model->GetPost((int)$postData['ID']);
								if ($OwnerID == $post['ownerid']) {
									$result = $adminModel->UpdatePost($postData['ID'], $postData['sort_order'], $postData['formID'], 
							$postData['title'], $postData['itype'], $postData['required']);
								} else {
									return _t('FORMS_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403'));												
								}
							}
							if (!Jaws_Error::IsError($result)) {
								$editpost = true;
								$i = 0;
								foreach ($_POST as $p => $pv) {
									if (substr($p, 0, 8) == 'AnswerID' && !empty($_POST['AnswerTitle'.$pv])) {
										$answer = $model->GetAnswer((int)$pv);
										if (isset($answer['id'])) {
											$res0 = $adminModel->UpdateAnswer((int)$pv, $i, $postData['ID'], $_POST['AnswerTitle'.$pv]);
											if (Jaws_Error::IsError($res0)) {
												$result2 = false;
												$url = "javascript:history.go(-1)";
												$link =& Piwi::CreateWidget('Link', "Please go back and try again",
																			$url);
												return $res0->GetMessage().'<br />'.$link->Get();
											}
											$i++;
										} else {
											$url = "javascript:history.go(-1)";
											$link =& Piwi::CreateWidget('Link', "Please go back and try again",
																		$url);
											return new Jaws_Error(_t('FORMS_ERROR_ANSWER_NOT_FOUND').'<br />'.$link->Get(), _t('FORMS_NAME'));
										}
									}
									if (substr($p, 0, 9) == 'AnswerNew' && !empty($pv)) {
										$res0 = $adminModel->AddAnswer($i, $postData['ID'], $postData['formID'], $pv, $OwnerID);
										if (Jaws_Error::IsError($res0)) {
											$result2 = false;
											$url = "javascript:history.go(-1)";
											$link =& Piwi::CreateWidget('Link', "Please go back and try again",
																		$url);
											return $res0->GetMessage().'<br />'.$link->Get();
										}
										$i++;
									}
								}
							}
						} else {
							$editpost = false;
							$url = "javascript:history.go(-1)";
							$link =& Piwi::CreateWidget('Link', "Please go back and try again",
														$url);
							return (Jaws_Error::IsError($result) ? $result->GetMessage().'<br />' : '').$link->Get();
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
								if ($GLOBALS['app']->Session->GetPermission('Forms', 'ManageForms')) {
									$result = $adminModel->DeletePost((int)$v);
								} else {
									$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
									$post = $model->GetPost((int)$v);
									if ($OwnerID == $post['ownerid']) {
										$result = $adminModel->DeletePost((int)$v);
									} else {
										return _t('FORMS_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403'));												
									}
								}								
								$dcount++;
							}
						} else if (!empty($id)) {
							if ($GLOBALS['app']->Session->GetPermission('Forms', 'ManageForms')) {
								$result = $adminModel->DeletePost($id);
							} else {
								$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
								$post = $model->GetPost($id);
								if ($OwnerID == $post['ownerid']) {
									$result = $adminModel->DeletePost($id);
								} else {
									return _t('FORMS_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403'));												
								}
							}
						}
						if (!Jaws_Error::IsError($result)) {
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
			require_once JAWS_PATH . 'include/Jaws/Header.php';
			
			// Send us to the appropriate page
			if ($editform === true) {
				if (count($params) > 0) {
					return (is_numeric($result) ? $result : (isset($postData['ID']) && !empty($postData['ID']) ? (int)$postData['ID'] : false));
				} else if ($fuseaction == 'DeleteForm') {
					$redirect = BASE_SCRIPT . '?gadget=Forms&action=Admin';
				} else if (is_numeric($result)) {
					$redirect = BASE_SCRIPT . '?gadget=Forms&action=view&id='.$result;
				} else {
					$redirect = BASE_SCRIPT . '?gadget=Forms&action=view&id='.$postData['ID'];
				}
			} else if ($editpost === true) {
				if (count($params) > 0) {
					return (is_numeric($result) ? $result : (isset($postData['ID']) && !empty($postData['ID']) ? (int)$postData['ID'] : false));
				} else {
					$redirect = BASE_SCRIPT . '?gadget=Forms&action=view&id='.$postData['formID'];
				}
			} else {
				return new Jaws_Error(_t('FORMS_ERROR_ASPPAGE_NOT_RETRIEVED'), _t('FORMS_NAME'));
			}
			
			if (isset($redirect)) {
				Jaws_Header::Location($redirect);
			}

		} else {
            return new Jaws_Error(_t('FORMS_ERROR_ASPPAGE_NOT_RETRIEVED'), _t('FORMS_NAME'));
		}

    }


    /**
     * We are on the view page
     *
     * @access public
     * @return string
     */
    function view()
    {
		//$GLOBALS['app']->Session->PopLastResponse();
        $GLOBALS['app']->Session->CheckPermission('Forms', 'default');
        $this->AjaxMe('script.js');

        $GLOBALS['app']->Layout->AddScriptLink('libraries/js/admin.js');

		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
						
		$tpl = new Jaws_Template('gadgets/Forms/templates/');
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

        $tpl->SetBlock('gadget_form');

		$request =& Jaws_Request::getInstance();
		$action = $request->getRaw('action', 'get');
		$tpl->SetVariable('workarea-style', '');
        $tpl->SetVariable('actionsTitle', _t('FORMS_ACTIONS'));
        $tpl->SetVariable('menubar', $this->MenuBar($action));

        $tpl->SetVariable('confirmPostDelete', _t('FORMS_POST_CONFIRM_DELETE'));

		// get current Syntacts URL
		$syntactsUrl = $GLOBALS['app']->getSyntactsAdminUrl("Forms/admin_Forms_view");
		if ($syntactsUrl) {
			
			// snoopy class
			include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
			$snoopy = new Snoopy('Forms');
					
			$submit_url = $syntactsUrl;
					
			$request =& Jaws_Request::getInstance();
			$pid = $request->getRaw('id', 'get');
			if (!empty($pid)) {
			
				$model = $GLOBALS['app']->LoadGadget('Forms', 'Model');
					
				// send Page records
				$pageInfo = $model->GetForm($pid);
				
				if (!Jaws_Error::IsError($pageInfo) && ($GLOBALS['app']->Session->GetPermission('Forms', 'ManageForms') || $pageInfo['ownerid'] == $GLOBALS['app']->Session->GetAttribute('user_id'))) {
				
					$i = 0;
					$j = 0;
					$submit_vars['0:cols'] = 16;
					foreach($pageInfo as $p => $pv) {		            
			                $submit_vars[SYNTACTS_DB ."0:$j:0"] = ($p == 'description') ? $this->ParseText($pv, 'Forms') : $xss->filter($pv);
							$j++;
							if ($j > $submit_vars['0:cols']) {
								$j=0;
							}
							//echo $xss->filter($value);
							$i++;
					}
					$submit_vars['0:rows'] = $i-1;

				} else {
					if (Jaws_Error::IsError($pageInfo)) {
						$page = _t('FORMS_ERROR_ASPPAGE_NOT_RETRIEVED', $pageInfo->GetMessage())."\n";
					} else {
						$page = _t('FORMS_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403'))."\n";
					}
				}

				// send Post records
				$posts = $model->GetAllPostsOfForm($pid);
				
		        if (!Jaws_Error::IsError($posts)) {
			        $i = 0;
			        $j = 0;
					$submit_vars['1:cols'] = 9;
					foreach($posts as $post) {		            
						foreach ($post as $e => $ev) {
			                $submit_vars[SYNTACTS_DB ."1:$j:$i"] = ($e == 'description') ? $this->ParseText($ev, 'Forms') : $xss->filter($ev);
							$j++;
							if ($j > $submit_vars['1:cols']) {
								$j=0;
							}
						}
						$i++;
					}
					$submit_vars['1:rows'] = $i-1;
		        } else {
					if (Jaws_Error::IsError($posts)) {
						$page = _t('FORMS_ERROR_ASPPAGE_NOT_RETRIEVED', $posts->GetMessage())."\n";
					} else {
						$page = _t('FORMS_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403'))."\n";
					}
				}
			} else {
				// Send us to the appropriate page
				require_once JAWS_PATH . 'include/Jaws/Header.php';
				Jaws_Header::Location(BASE_SCRIPT . '?gadget=Forms');
			}

			$submit_vars['HTTP_REFERER'] = $GLOBALS['app']->GetSiteURL();
			$submit_vars['JAWS_URL'] = $GLOBALS['app']->GetJawsURL() . "/";
			$submit_vars['DPATH'] = '';

			if (!isset($page)) {
				if($snoopy->submit($submit_url,$submit_vars)) {
					//while(list($key,$val) = each($snoopy->headers))
						//echo $key.": ".$val."<br>\n";
					//echo "<p>\n";
					
					//$page = "<PRE>".htmlspecialchars($snoopy->results)."</PRE>\n";
					$page = $snoopy->results;
					if (empty($pageInfo['recipient'])) { 
						$default_recipient = $GLOBALS['app']->Registry->Get('/gadgets/Forms/default_recipient');
						if (!empty($default_recipient)) { 
							$recipient = $GLOBALS['app']->Registry->Get('/gadgets/Forms/default_recipient');
						} else {
							$recipient = $GLOBALS['app']->Registry->Get('/network/site_email');
						}
					} else {
						$recipient = $pageInfo['recipient'];
					}
					// Add Preview link
					$url = $GLOBALS['app']->Map->GetURLFor('Forms', 'Form', array('id' => $pageInfo['fast_url']));
					$link =& Piwi::CreateWidget('Link', "Preview This Form",
												$url);
					$page = str_replace("__JAWS_PREVIEW__", "[".$link->Get()."]", $page);
					// Show who the Recipient will be 
					$page = str_replace("__JAWS_RECIPIENT__", "<b>".$recipient."</b>", $page);
					$site_name = $GLOBALS['app']->Registry->Get('/config/site_name');
					if (!empty($site_name)) {
						$page = str_replace("__SITE_NAME__", "<tr><td width=\"27%\">".$GLOBALS['app']->Registry->Get('/config/site_name')."</td><td width=\"73%\">&nbsp;</td></tr>", $page);
					} else {
						$page = str_replace("__SITE_NAME__", "", $page);
					}
					$site_address = $GLOBALS['app']->Registry->Get('/gadgets/Forms/site_address');
					if (!empty($site_address)) {
						$page = str_replace("__SITE_ADDRESS__", "<tr><td width=\"27%\">".$GLOBALS['app']->Registry->Get('/gadgets/Forms/site_address')."</td><td width=\"73%\">&nbsp;</td></tr>", $page);
					} else {
						$page = str_replace("__SITE_ADDRESS__", "", $page);
					}
					$site_address2 = $GLOBALS['app']->Registry->Get('/gadgets/Forms/site_address2');
					if (!empty($site_address2)) {
						$page = str_replace("__SITE_ADDRESS2__", "<tr><td width=\"27%\">".$GLOBALS['app']->Registry->Get('/gadgets/Forms/site_address2')."</td><td width=\"73%\">&nbsp;</td></tr>", $page);
					} else {
						$page = str_replace("__SITE_ADDRESS2__", "", $page);
					}
					$site_office = $GLOBALS['app']->Registry->Get('/gadgets/Forms/site_office');
					if (!empty($site_office)) {
						$page = str_replace("__SITE_OFFICE__", "<tr><td width=\"27%\">Office Phone:</td><td width=\"73%\">".$GLOBALS['app']->Registry->Get('/gadgets/Forms/site_office')."</td></tr>", $page);
					} else {
						$page = str_replace("__SITE_OFFICE__", "", $page);
					}
					$site_tollfree = $GLOBALS['app']->Registry->Get('/gadgets/Forms/site_tollfree');
					if (!empty($site_tollfree)) {
						$page = str_replace("__SITE_TOLLFREE__", "<tr><td width=\"27%\">Toll-Free:</td><td width=\"73%\">".$GLOBALS['app']->Registry->Get('/gadgets/Forms/site_tollfree')."</td></tr>", $page);
					} else {
						$page = str_replace("__SITE_TOLLFREE__", "", $page);
					}
					$site_cell = $GLOBALS['app']->Registry->Get('/gadgets/Forms/site_cell');
					if (!empty($site_cell)) {
						$page = str_replace("__SITE_CELL__", "<tr><td width=\"27%\">Cell/Direct Phone:</td><td width=\"73%\">".$GLOBALS['app']->Registry->Get('/gadgets/Forms/site_cell')."</td></tr>", $page);
					} else {
						$page = str_replace("__SITE_CELL__", "", $page);
					}
					$site_fax = $GLOBALS['app']->Registry->Get('/gadgets/Forms/site_fax');
					if (!empty($site_fax)) {
						$page = str_replace("__SITE_FAX__", "<tr><td width=\"27%\">Fax:</td><td width=\"73%\">".$GLOBALS['app']->Registry->Get('/gadgets/Forms/site_fax')."</td></tr>", $page);
					} else {
						$page = str_replace("__SITE_FAX__", "", $page);
					}
					/*
					$site_email = $GLOBALS['app']->Registry->Get('/network/site_email');
					if (!empty($site_email)) {
						$page = str_replace("__SITE_EMAIL__", "<tr><td width=\"27%\">E-mail:</td><td width=\"73%\">".$GLOBALS['app']->Registry->Get('/network/site_email')."</td></tr>", $page);
					} else {
						$page = str_replace("__SITE_EMAIL__", "", $page);
					}
					*/
				} else {
					$page = _t('FORMS_ERROR_ASPPAGE_NOT_RETRIEVED', $snoopy->error)."\n";
				}
			}

			$tpl->SetVariable('content', $page);
		}
        
		$tpl->ParseBlock('gadget_form');

        return $tpl->Get();

    }

    /**
     * We are on the A_form page
     *
     * @access public
     * @return string
     */
    function A_form()
    {
        $GLOBALS['app']->Session->CheckPermission('Forms', 'default');
        $this->AjaxMe('script.js');
		$GLOBALS['app']->Layout->AddScriptLink('libraries/js/admin.js');			
		
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$request =& Jaws_Request::getInstance();
				
		$tpl = new Jaws_Template('gadgets/Forms/templates/');
        $tpl->Load('admin.html');

        $tpl->SetBlock('gadget_form');

		$request =& Jaws_Request::getInstance();
		$action = $request->getRaw('action', 'get');
        $gadgetMsg = $request->get('gadgetMsg', 'get');
		$id = $request->get('id', 'get');
		$linkid = $request->get('linkid', 'get');
		$tpl->SetVariable('menubar', $this->MenuBar($action));
		$tpl->SetVariable('workarea-style', '');
        $tpl->SetVariable('confirmAnswerDelete', _t('FORMS_ANSWER_CONFIRM_DELETE'));

		$syntactsUrl = $GLOBALS['app']->getSyntactsAdminUrl("Forms/admin_Forms_A_form");
		if ($syntactsUrl) {
			include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
			$snoopy = new Snoopy('Forms');
			$model = $GLOBALS['app']->LoadGadget('Forms', 'Model');
					
			if (!empty($gadgetMsg)) {
	            $submit_vars['simple-msg:0'] =  _t($gadgetMsg);
			}
			
			$submit_url = $syntactsUrl;
			
			// send post records
			if (!empty($id)) {
				$post = $model->GetPost($id);
		        if (!Jaws_Error::IsError($post) && ($GLOBALS['app']->Session->GetPermission('Forms', 'ManageForms') || $post['ownerid'] == $GLOBALS['app']->Session->GetAttribute('user_id'))) {
					$i = 0;
					$j = 0;
					$submit_vars['0:cols'] = 9;
					foreach($post as $e => $v) {		            
							
							$submit_vars[SYNTACTS_DB ."0:$j:0"] = $xss->filter($v);
							
							$j++;
							if ($j > $submit_vars['0:cols']) {
								$j=0;
							}
							//echo $xss->filter($value);
							$i++;
					}
					$submit_vars['0:rows'] = $i-1;
					//$submit_vars['0:rows'] = 0;

					// send Post records
					$answers = $model->GetAllAnswersOfPost($id);
					
					if (!Jaws_Error::IsError($answers)) {
						$i = 0;
						$j = 0;
						$submit_vars['2:cols'] = 8;
						foreach($answers as $region) {		            
							foreach ($region as $r => $rv) {
								$submit_vars[SYNTACTS_DB ."2:$j:$i"] = $xss->filter($rv);
								$j++;
								if ($j > $submit_vars['2:cols']) {
									$j=0;
								}
							}
							$i++;
						}
						$submit_vars['2:rows'] = $i-1;
					} else {
						if (Jaws_Error::IsError($answers)) {
							$page = _t('FORMS_ERROR_ASPPAGE_NOT_RETRIEVED', $answers->GetMessage())."\n";
						} else {
							$page = _t('FORMS_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403'))."\n";
						}
					}

				} else {
					if (Jaws_Error::IsError($post)) {
						$page = _t('FORMS_ERROR_ASPPAGE_NOT_RETRIEVED', $post->GetMessage())."\n";
					} else {
						$page = _t('FORMS_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403'))."\n";
					}
				}
								
			} else if (!empty($linkid)) {
				// send highest sort_order
				$sql = "SELECT MAX([sort_order]) FROM [[form_questions]] ORDER BY [sort_order] DESC";
				$max = $GLOBALS['db']->queryOne($sql);
				if (Jaws_Error::IsError($max)) {
				   return $max;
				}
				$submit_vars[SYNTACTS_DB ."1:0:0"] = $max;
				$submit_vars['1:cols'] = 0;
				$submit_vars['1:rows'] = 0;
			} else {
				// Send us to the appropriate page
				require_once JAWS_PATH . 'include/Jaws/Header.php';
				Jaws_Header::Location(BASE_SCRIPT . '?gadget=Forms');
			}

	        // url list combo
			$post_url = (isset($post['marker_url']) && !strpos($post['marker_url'], "://")) ? $post['marker_url'] : '';
			$urlListCombo =& Piwi::CreateWidget('Combo', 'internal_marker_url');
	        $urlListCombo->setID('internal_marker_url');

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
	        $urlListCombo->setDefault($post_url);

			// send editor HTML to syntacts
			$description = (isset($post['description'])) ? $post['description'] : '';
			$editor =& $GLOBALS['app']->LoadEditor('Forms', 'description', $description, false);
			$editor->TextArea->SetStyle('width: 100%;');
			$editor->SetWidth('490px');

			$submit_vars['HTTP_REFERER'] = $GLOBALS['app']->GetSiteURL();
			$submit_vars['JAWS_URL'] = $GLOBALS['app']->GetJawsURL() . "/";
			$submit_vars['DPATH'] = '';
			
			if (!isset($page)) {
				if($snoopy->submit($submit_url,$submit_vars)) {
					//while(list($key,$val) = each($snoopy->headers))
						//echo $key.": ".$val."<br>\n";
					//echo "<p>\n";
					
					//$page = "<PRE>".htmlspecialchars($snoopy->results)."</PRE>\n";
					$page = $snoopy->results;
					$page = str_replace("__JAWS_EDITOR__", $editor->Get(), $page);
					$page = str_replace("__JAWS_INTERNALURLS__", $urlListCombo->Get(), $page);
				} else {
					$page = _t('FORMS_ERROR_ASPPAGE_NOT_RETRIEVED', $snoopy->error)."\n";
				}
			}

			$tpl->SetVariable('content', $page);
		}
		
        $tpl->ParseBlock('gadget_form');
        return $tpl->Get();
    }

    /**
     * We are on the A_form_post page
     *
     * @access public
     * @return string
     */
    function A_form_post()
    {

        return $this->form_post();

    }

    /**
     * We are on the properties page
     *
     * @access public
     * @return XHTML string
     */
    function Settings()
    {
        $this->CheckPermission('default');
        $this->AjaxMe('script.js');

        $tpl = new Jaws_Template('gadgets/Forms/templates/');
        $tpl->Load('admin.html');
        $tpl->SetBlock('properties');

		$request =& Jaws_Request::getInstance();
		$action = $request->getRaw('action', 'get');
        $tpl->SetVariable('menubar', $this->MenuBar($action));

        $tpl->SetVariable('base_script', BASE_SCRIPT);
        $tpl->SetVariable('settings', _t('GLOBAL_SETTINGS'));

        $model = $GLOBALS['app']->LoadGadget('Forms', 'AdminModel');
        $googleMapsKey = $GLOBALS['app']->Registry->Get('/gadgets/Forms/default_recipient');
        $addressKey = $GLOBALS['app']->Registry->Get('/gadgets/Forms/site_address');
        $officeKey = $GLOBALS['app']->Registry->Get('/gadgets/Forms/site_office');
        $tollfreeKey = $GLOBALS['app']->Registry->Get('/gadgets/Forms/site_tollfree');
        $cellKey = $GLOBALS['app']->Registry->Get('/gadgets/Forms/site_cell');
        $faxKey = $GLOBALS['app']->Registry->Get('/gadgets/Forms/site_fax');

        $keyEntry =& Piwi::CreateWidget('Entry', 'default_recipient', $googleMapsKey);
        $keyEntry->SetStyle('zwidth: 100%;');
        $tpl->SetVariable('key_entry', $keyEntry->Get());

        $addressEntry =& Piwi::CreateWidget('Entry', 'site_address', $addressKey);
        $addressEntry->SetStyle('zwidth: 100%;');
        $tpl->SetVariable('address_entry', $addressEntry->Get());

        $officeEntry =& Piwi::CreateWidget('Entry', 'site_office', $officeKey);
        $officeEntry->SetStyle('zwidth: 100%;');
        $tpl->SetVariable('office_entry', $officeEntry->Get());

        $tollfreeEntry =& Piwi::CreateWidget('Entry', 'site_tollfree', $tollfreeKey);
        $tollfreeEntry->SetStyle('zwidth: 100%;');
        $tpl->SetVariable('tollfree_entry', $tollfreeEntry->Get());

        $cellEntry =& Piwi::CreateWidget('Entry', 'site_cell', $cellKey);
        $cellEntry->SetStyle('zwidth: 100%;');
        $tpl->SetVariable('cell_entry', $cellEntry->Get());

        $faxEntry =& Piwi::CreateWidget('Entry', 'site_fax', $faxKey);
        $faxEntry->SetStyle('zwidth: 100%;');
        $tpl->SetVariable('fax_entry', $faxEntry->Get());

        $saveButton =& Piwi::CreateWidget('Button', 'Save', _t('GLOBAL_SAVE'), STOCK_SAVE);
        $saveButton->AddEvent(ON_CLICK,
                             "javascript: setRegistryKey();");

        $tpl->SetVariable('save_button', $saveButton->Get());
        $tpl->SetVariable('key_label', _t('FORMS_RECIPIENT_KEY'));
        $tpl->SetVariable('key_extra', _t('FORMS_RECIPIENT_EXTRA', $GLOBALS['app']->Registry->Get('/network/site_email')));

        $tpl->SetVariable('address_label', _t('FORMS_ADDRESS_KEY'));
        $tpl->SetVariable('office_label', _t('FORMS_OFFICE_KEY'));
        $tpl->SetVariable('tollfree_label', _t('FORMS_TOLLFREE_KEY'));
        $tpl->SetVariable('cell_label', _t('FORMS_CELL_KEY'));
        $tpl->SetVariable('fax_label', _t('FORMS_FAX_KEY'));
		
        $tpl->ParseBlock('properties');
        return $tpl->Get();
	}
	
    /**
     * Quick add form
     *
     * @access public
     * @return XHTML string
     */
    function GetQuickAddForm($account = false)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		$GLOBALS['app']->Session->CheckPermission('Forms', 'default');
		//$GLOBALS['app']->ACL->CheckPermission($GLOBALS['app']->Session->GetAttribute('username'), 'Forms', 'default');

		require_once JAWS_PATH . 'include/Jaws/Template.php';
        $tpl = new Jaws_Template('gadgets/Forms/templates/');
        $tpl->Load('QuickAddForm.html');
        $tpl->SetBlock('form');

		$request =& Jaws_Request::getInstance();
		$method = $request->get('method', 'get');
		if (empty($method)) {
			$method = 'AddForm';
		}
		$form_content = '';
		switch($method) {
			case "AddForm": 
			case "UpdateForm": 
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

        //$tpl->SetVariable('DPATH', JAWS_DPATH);
        $tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . "/");
        $tpl->SetVariable('stub', (JAWS_SCRIPT == 'admin' ? 'FormsAdminAjax' : 'FormsAjax'));
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
