<?php
/**
 * Maps Gadget Admin
 *
 * @category   GadgetAdmin
 * @package    Maps
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class MapsAdminHTML extends Jaws_GadgetHTML
{
    /**
     * Constructor
     *
     * @access public
     */
    function MapsAdminHTML()
    {
        $this->Init('Maps');
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
        if ($GLOBALS['app']->Session->GetPermission('Maps', 'ManageMaps')) {
            $menubar->AddOption('Admin', _t('MAPS_MENU_ADMIN'),
                                'admin.php?gadget=Maps&amp;action=Admin', STOCK_DOCUMENTS);
        }
        if ($GLOBALS['app']->Session->GetPermission('Maps', 'default')) {
			if (strtolower($selected) != "admin" && (strtolower($selected) == 'view' || strtolower($selected) == 'form' || strtolower($selected) == 'form_post')) {
				$menubar->AddOption($selected, _t('MAPS_MENU_MAP'),
	                                'javascript:void(0);', STOCK_NEW);
			}
            if (strtolower($selected) != "admin" && (strtolower($selected) == 'a_form' || strtolower($selected) == 'a_form_post')) {
				$menubar->AddOption($selected, _t('MAPS_MENU_POST'),
	                                'javascript:void(0);', STOCK_EDIT);
			}
	        if ($GLOBALS['app']->Session->GetPermission('Maps', 'ManageMaps')) {
	            $menubar->AddOption('Settings', _t('MAPS_MENU_PROPERTIES'),
	                                'admin.php?gadget=Maps&amp;action=Settings', STOCK_DOCUMENTS);
	        }
		}

		$request =& Jaws_Request::getInstance();
		$id = $request->get('id', 'get');
		if (strtolower($selected) == "form" && empty($id)) {
		} else {
	        if ($GLOBALS['app']->Session->GetPermission('Maps', 'ManageMaps')) {
				$menubar->AddOption('Add', '',
									'admin.php?gadget=Maps&amp;action=form', STOCK_ADD);
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
        $model = $GLOBALS['app']->LoadGadget('Maps', 'AdminModel');
		$OwnerID = 0;
		if (BASE_SCRIPT == 'index.php') {
			$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');
		}
        $sql = 'SELECT COUNT([id]) FROM [[maps]] WHERE [ownerid] = '.$OwnerID;
        $res = $GLOBALS['db']->queryOne($sql);
        $total = (Jaws_Error::IsError($res) ? 0 : $res);

        $grid =& Piwi::CreateWidget('DataGrid', array());
        $grid->TotalRows($total);
        $grid->SetStyle('width: 100%;');
        $grid->SetID('maps_datagrid');
        $grid->SetAction('next', 'javascript:nextValues();');
        $grid->SetAction('prev', 'javascript:previousValues();');
        //$grid->useMultipleSelection();
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_TITLE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', ''));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('MAPS_ACTIVE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('MAPS_LAST_UPDATE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_ACTIONS')));

        return $grid->Get();
    }

    /**
     * Returns an array with maps found
     *
     * @access  public
     * @param   string  $status  Status of map(s) we want to display
     * @param   string  $search  Keyword (title/description) of maps we want to look for
     * @param   int     $limit   Data limit
     * @return  array   Data
     */
    function GetMaps($status, $search, $limit)
    {
        $model = $GLOBALS['app']->LoadGadget('Maps', 'AdminModel');
		if ($GLOBALS['app']->Session->GetPermission('Maps', 'ManageMaps')) {
	        $pages = $model->SearchMaps($status, $search, $limit);
		} else {
			$pages = $model->SearchMaps($status, $search, $limit, $GLOBALS['app']->Session->GetAttribute('user_id'));
		}
        $pages = $model->SearchMaps($status, $search, $limit);
        if (Jaws_Error::IsError($pages)) {
            return array();
        }

        $data    = array();
        $edit_url    = BASE_SCRIPT . '?gadget=Maps&amp;action=view&amp;id=';
        $date = $GLOBALS['app']->loadDate();
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');

        foreach ($pages as $page) {
			$pageData = array();
			$pageData['title'] = '<a href="'.$edit_url.$page['id'].'">'.$page['title'].'</a>';
			$pageData['furl']  = '<a href="'.$GLOBALS['app']->Map->GetURLFor('Maps', 'Map', array('id' => $page['id'])).'">View This Map</a>';

			if ($page['active'] == 'Y') {
				$pageData['active'] = _t('MAPS_PUBLISHED');
			} else {
				$pageData['active'] = _t('MAPS_DRAFT');
			}
			$pageData['date']  = $date->Format($page['updated']);
			$actions = '';
			if ($this->GetPermission('ManageMaps')) {
				$link =& Piwi::CreateWidget('Link', _t('GLOBAL_EDIT'),
											$edit_url.$page['id'],
											STOCK_EDIT);
				$actions.= $link->Get().'&nbsp;';
			}

			if ($this->GetPermission('ManageMaps')) {
				$link =& Piwi::CreateWidget('Link', _t('GLOBAL_DELETE'),
											"javascript: if (confirm('"._t('GLOBAL_CONFIRM_DELETE', _t('MAPS_MAP'))."')) ".
											"deleteMap('".$page['id']."');",
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

        $tpl = new Jaws_Template('gadgets/Maps/templates/');
        $tpl->Load('admin.html');
        $tpl->SetBlock('maps_admin');
        
		$request =& Jaws_Request::getInstance();
		$action = $request->getRaw('action', 'get');
        $tpl->SetVariable('menubar', $this->MenuBar($action));
        
		$tpl->SetVariable('base_script', BASE_SCRIPT);

        $tpl->SetVariable('grid', $this->DataGrid());

        $toolBar   =& Piwi::CreateWidget('HBox');

        $deleteAll =& Piwi::CreateWidget('Button', 'deleteAllMaps',
                                         _t('GLOBAL_DELETE'),
                                         STOCK_DELETE);
        $deleteAll->AddEvent(ON_CLICK,
                             "javascript: massiveDelete('"._t('MAPS_CONFIRM_MASIVE_DELETE_MAP')."');");

        $toolBar->Add($deleteAll);

        $tpl->SetVariable('tools', $toolBar->Get());
                
        //Status filter
        $status = '';
        $statusCombo =& Piwi::CreateWidget('Combo', 'status');
        $statusCombo->setId('status');
        $statusCombo->AddOption('&nbsp;', '');
        $statusCombo->AddOption(_t('GLOBAL_YES'), 'Y');
        $statusCombo->AddOption(_t('GLOBAL_NO'), 'N');
        $statusCombo->SetDefault($status);
        $statusCombo->AddEvent(ON_CHANGE, 'javascript: searchMap();');
        $tpl->SetVariable('status', _t('MAPS_ACTIVE'));
        $tpl->SetVariable('status_field', $statusCombo->Get());

        // Free text search
        $searchButton =& Piwi::CreateWidget('Button', 'searchButton', _t('GLOBAL_SEARCH'), STOCK_SEARCH);
        $searchButton->AddEvent(ON_CLICK, 'javascript: searchMap();');
        $tpl->SetVariable('search', $searchButton->Get());

        $search = '';
        $searchEntry =& Piwi::CreateWidget('Entry', 'search', $search);
        $searchEntry->SetStyle('zwidth: 100%;');
        $tpl->SetVariable('search_field', $searchEntry->Get());

        $tpl->SetVariable('entries', $this->Datagrid());

        $addPage =& Piwi::CreateWidget('Button', 'add_map', _t('MAPS_ADD_MAP'), STOCK_ADD);
        $addPage->AddEvent(ON_CLICK, "javascript: window.location = '".BASE_SCRIPT.'?gadget=Maps&amp;action=form'."';");
        $tpl->SetVariable('add_map', $addPage->Get());

        $tpl->ParseBlock('maps_admin');

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
        $GLOBALS['app']->Session->CheckPermission('Maps', 'default');

		// document dependencies
        $this->AjaxMe('script.js');

        $GLOBALS['app']->Layout->AddScriptLink('libraries/js/admin.js');

		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
						
		// initialize template
		$tpl = new Jaws_Template('gadgets/Maps/templates/');
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

		$model = $GLOBALS['app']->LoadGadget('Maps', 'Model');
		$request =& Jaws_Request::getInstance();
		$gather = array('action', 'id');
		$get = $request->getRaw($gather, 'get');

        $tpl->SetBlock('gadget_map');

		// menus
		$tpl->SetVariable('menubar', $this->MenuBar($get['action']));

		$tpl->SetVariable('workarea-style', "style=\"float: left;\" ");

		// syntacts page
		$syntactsUrl = $GLOBALS['app']->getSyntactsAdminUrl();
		if ($syntactsUrl) {
			// snoopy
			include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
			$snoopy = new Snoopy('Maps');
			$submit_url = $syntactsUrl;
			
			if (!is_null($get['id'])) {
				// send page records
				$pageInfo = $model->GetMap($get['id']);
		        if (!Jaws_Error::IsError($pageInfo) && ($GLOBALS['app']->Session->GetPermission('Maps', 'ManageMaps') || $pageInfo['ownerid'] == $GLOBALS['app']->Session->GetAttribute('user_id'))) {
						$i = 0;
						$j = 0;
						$submit_vars['0:cols'] = 9;
						foreach($pageInfo as $p => $value) {		            
								$submit_vars[SYNTACTS_DB ."0:$j:0"] = ($p == 'description') ? $this->ParseText($value, 'Maps') : $xss->filter($value);
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
                    //return new Jaws_Error(_t('MAPS_ERROR_PAGE_NOT_FOUND'), _t('MAPS_NAME'));
				}
			}			
			// send possible parent page records
			if ($GLOBALS['app']->Session->GetPermission('Maps', 'ManageMaps')) {
				$pages = $model->GetMaps();
			} else {
				$pages = $model->GetMaps(null, 'title', 'ASC', false, $GLOBALS['app']->Session->GetAttribute('user_id'));
			}
			if (!Jaws_Error::IsError($pages)) {
				$i = 0;
				$j = 0;
				$submit_vars['1:cols'] = 9;
				foreach($pages as $p) {		            
					foreach ($p as $e => $ev) {
						if (isset($e['id'])) {					            
							$submit_vars[SYNTACTS_DB ."1:$j:$i"] = $xss->filter($ev);
							$j++;
							if ($j > $submit_vars['1:cols']) {
								$j=0;
							}
						}
					}
					$i++;
				}
				$submit_vars['1:rows'] = $i-1;
			} else {
				//return new Jaws_Error(_t('MAPS_ERROR_PAGES_NOT_RETRIEVED'), _t('MAPS_NAME'));
			}

			// send requesting URL to syntacts
			$submit_vars['HTTP_REFERER'] = $GLOBALS['app']->GetSiteURL();
			$submit_vars['JAWS_URL'] = $GLOBALS['app']->GetJawsURL() . '/';
			$submit_vars['DPATH'] = '';
			
			// send editor HTML to syntacts
			$content = (isset($pageInfo['description'])) ? $pageInfo['description'] : '';
			$editor =& $GLOBALS['app']->LoadEditor('Maps', 'description', $content, false);
			$editor->TextArea->SetStyle('width: 100%;');
			//$editor->SetWidth('100%');
	
			$autodraft = '<script type="text/javascript" language="javascript">setTimeout(\'startAutoDrafting();\', 1200000);</script>';
			$tpl->SetVariable('autodraft', $autodraft);
			
			if (!isset($page)) {
				if($snoopy->submit($submit_url,$submit_vars)) {
					//while(list($key,$val) = each($snoopy->headers))
						//echo $key.": ".$val."<br>\n";
					//echo "<p>\n";
					
					//$page = "<PRE>".htmlspecialchars($snoopy->results)."</PRE>\n";
					$page = $snoopy->results;
					// add Pages forms elements to the results
					$page = str_replace("__JAWS_EDITOR__", $editor->Get(), $page);
				} else {
					$page = _t('MAPS_ERROR_ASPPAGE_NOT_RETRIEVED', $snoopy->error)."\n";
				}
			}

			$tpl->SetVariable('content', $page);
		} else {
			return new Jaws_Error(_t('MAPS_ERROR_ASPPAGE_NOT_RETRIEVED'), _t('MAPS_NAME'));
		}
		
        $tpl->ParseBlock('gadget_map');

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
		$GLOBALS['app']->Session->CheckPermission('Maps', 'default');
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');        
		
		$request =& Jaws_Request::getInstance();
		$fuseaction = $request->getRaw('fuseaction', 'post');
		
		$get  = $request->getRaw(array('fuseaction', 'pct', 'linkid', 'id'), 'get');
        if (is_null($fuseaction)) {
			$fuseaction = $get['fuseaction'];
		}
        
		$adminModel = $GLOBALS['app']->LoadGadget('Maps', 'AdminModel');
		$model = $GLOBALS['app']->LoadGadget('Maps', 'Model');

        if (!empty($fuseaction)) {		
			switch($fuseaction) {
                case "AddMap": 
						$keys = array('title', 'description', 'custom_height', 'Active', 'map_type');
						$postData = $request->get($keys, 'post');
						//foreach($postData as $key => $value) {
							//echo $key."=".$value."\n";
						//}
						// add OwnerID if no permissions
						if ($GLOBALS['app']->Session->GetPermission('Maps', 'ManageMaps') ) {
							$OwnerID = null;
						} else {
							$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
						}
						$result = $adminModel->AddMap($postData['title'], 
										$postData['description'], $postData['custom_height'],
										$postData['Active'], $OwnerID, $postData['map_type']
						);
						if (!Jaws_Error::IsError($result)) {
					        // declare result as ok for later
							$result1 = true;
						} else {
							$url = "javascript:history.go(-1)";
							$link =& Piwi::CreateWidget('Link', "Please go back and try again",
														$url);
							return $result->GetMessage().'<br />'.$link->Get();
						}
						break;
                case "EditMap": 
						$keys = array('ID', 'title', 'description', 'custom_height', 'Active', 'map_type');
						$postData = $request->get($keys, 'post');
						if (isset($postData['ID'])) {
							// add OwnerID if no permissions
							if ($GLOBALS['app']->Session->GetPermission('Maps', 'ManageMaps')) {
								$result = $adminModel->UpdateMap((int)$postData['ID'], $postData['title'], 
										$postData['description'], $postData['custom_height'],
										$postData['Active'], $postData['map_type']
								);
							} else {
								$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
								$parent = $model->GetMap((int)$postData['ID']);
								if ($OwnerID == $parent['ownerid']) {
									$result = $adminModel->UpdateMap($parent['id'], $postData['title'], 
										$postData['description'], $postData['custom_height'],
										$postData['Active'], $postData['map_type']
									);
								} else {
									return _t('MAPS_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403'));												
								}
							}
						}
						if (!Jaws_Error::IsError($result)) {
							$result1 = true;
						} else {
							$url = "javascript:history.go(-1)";
							$link =& Piwi::CreateWidget('Link', "Please go back and try again",
														$url);
							return $result->GetMessage().'<br />'.$link->Get();
						}
                       break;
                case "DeleteMap": 
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
							if ($GLOBALS['app']->Session->GetPermission('Maps', 'ManageMaps')) {
								$result = $adminModel->DeleteMap($id);
							} else {
								$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
								$parent = $model->GetMap($id);
								if ($OwnerID == $parent['id']) {
									$result = $adminModel->DeleteMap($id);
								} else {
									return _t('MAPS_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403'));												
								}
							}
						}						
						if (!Jaws_Error::IsError($result)) {
							$result1 = true;
						} else {
							$url = "javascript:history.go(-1)";
							$link =& Piwi::CreateWidget('Link', "Please go back and try again",
														$url);
							return $result->GetMessage().'<br />'.$link->Get();
						}
						break;
                case "AddPost": 
				        $keys = array('sort_order', 'LinkID', 'title', 'Image', 'description', 
							'sm_description', 'address', 'city', 'region', 'country_id', 
							'prop_id', 'marker_font_size', 'marker_font_color', 'marker_subfont_size', 
							'marker_border_width', 'marker_border_color', 'marker_radius', 'marker_foreground',
							'marker_hover_font_color', 'marker_hover_foreground', 'marker_hover_border_color', 'Active', 
							'marker_url', 'marker_url_target', 'internal_marker_url');
						$postData = $request->get($keys, 'post');
						//foreach($postData as $key => $value) {
						//	echo $key."=".$value."\n";
						//}
						// add OwnerID if no permissions
						if ($GLOBALS['app']->Session->GetPermission('Maps', 'ManageMaps')) {
							$OwnerID = null;
						} else {
							$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
						}
						$result = $adminModel->AddPost($postData['sort_order'], $postData['LinkID'], 
							$postData['title'], $postData['description'], $postData['sm_description'], $postData['Image'], 
							$postData['address'], $postData['city'], $postData['region'], 
							$postData['country_id'], $postData['prop_id'], $postData['marker_font_size'], $postData['marker_font_color'], 
							$postData['marker_subfont_size'], $postData['marker_border_width'], $postData['marker_border_color'], 
							$postData['marker_radius'], $postData['marker_foreground'], $postData['marker_hover_font_color'], 
							$postData['marker_hover_foreground'], $postData['marker_hover_border_color'], $postData['Active'], $OwnerID,
							$postData['marker_url'], $postData['marker_url_target'], $postData['internal_marker_url']);
						if (!Jaws_Error::IsError($result)) {
							$result2 = true;
						} else {
							$url = "javascript:history.go(-1)";
							$link =& Piwi::CreateWidget('Link', "Please go back and try again",
														$url);
							return $result->GetMessage().'<br />'.$link->Get();
						}
						break;
                case "EditPost": 
				        $keys = array('ID', 'sort_order', 'LinkID', 'title', 'Image', 'description', 
							'sm_description', 'address', 'city', 'region', 'country_id', 
							'prop_id', 'marker_font_size', 'marker_font_color', 'marker_subfont_size', 
							'marker_border_width', 'marker_border_color', 'marker_radius', 'marker_foreground',
							'marker_hover_font_color', 'marker_hover_foreground', 'marker_hover_border_color', 'Active',
							'marker_url', 'marker_url_target', 'internal_marker_url');
							
						$postData = $request->get($keys, 'post');
						if ($postData['ID']) {
							// check OwnerID if no permissions
							if ($GLOBALS['app']->Session->GetPermission('Maps', 'ManageMaps')) {
								$result = $adminModel->UpdatePost($postData['ID'], $postData['sort_order'], 
								$postData['title'], $postData['description'], $postData['sm_description'],
								$postData['Image'], $postData['address'], $postData['city'], $postData['region'], 
								$postData['country_id'], $postData['prop_id'], $postData['marker_font_size'], $postData['marker_font_color'], 
								$postData['marker_subfont_size'], $postData['marker_border_width'], $postData['marker_border_color'], 
								$postData['marker_radius'], $postData['marker_foreground'], $postData['marker_hover_font_color'], 
								$postData['marker_hover_foreground'], $postData['marker_hover_border_color'], $postData['Active'],
								$postData['marker_url'], $postData['marker_url_target'], $postData['internal_marker_url']);
							} else {
								$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
								$post = $model->GetPost($postData['ID']);
								if ($OwnerID == $post['ownerid']) {
									$result = $adminModel->UpdatePost($postData['ID'], $postData['sort_order'], 
								$postData['title'], $postData['description'], $postData['sm_description'], 
								$postData['Image'], $postData['address'], $postData['city'], $postData['region'], 
								$postData['country_id'], $postData['prop_id'], $postData['marker_font_size'], $postData['marker_font_color'], 
								$postData['marker_subfont_size'], $postData['marker_border_width'], $postData['marker_border_color'], 
								$postData['marker_radius'], $postData['marker_foreground'], $postData['marker_hover_font_color'], 
								$postData['marker_hover_foreground'], $postData['marker_hover_border_color'], $postData['Active'],
								$postData['marker_url'], $postData['marker_url_target'], $postData['internal_marker_url']);
								} else {
									return _t('MAPS_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403'));												
								}
							}
						}
						if (!Jaws_Error::IsError($result)) {
							$result2 = true;
						} else {
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
						if (is_null($id)) {
							$id = $get['id'];
						}
						$dcount = 0;
						// loop through the idarray and delete each ID
						if ($postData['idarray'] && strpos($postData['idarray'], ',')) {
					        $ids = explode(',', $postData['idarray']);
							foreach ($ids as $i => $v) {
								if ($GLOBALS['app']->Session->GetPermission('Maps', 'ManageMaps')) {
									$result = $adminModel->DeletePost((int)$v);
								} else {
									$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
									$post = $model->GetPost((int)$v);
									if ($OwnerID == $post['ownerid']) {
										$result = $adminModel->DeletePost((int)$v);
									} else {
										return _t('MAPS_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403'));												
									}
								}								
								$dcount++;
							}
						} else if (!is_null($id)) {
							if ($GLOBALS['app']->Session->GetPermission('Maps', 'ManageMaps')) {
								$result = $adminModel->DeletePost($id);
							} else {
								$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
								$post = $model->GetPost($id);
								if ($OwnerID == $post['ownerid']) {
									$result = $adminModel->DeletePost($id);
								} else {
									return _t('MAPS_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403'));												
								}
							}
						}
						if (!Jaws_Error::IsError($result)) {
							$result2 = true;
						} else {
							$url = "javascript:history.go(-1)";
							$link =& Piwi::CreateWidget('Link', "Please go back and try again",
														$url);
							return $result->GetMessage().'<br />'.$link->Get();
						}
						break;
            }
			require_once JAWS_PATH . 'include/Jaws/Header.php';
			
			// Send us to the appropriate page
			if ($result1 === true) {
				if (is_numeric($result)) {
					$redirect = BASE_SCRIPT . '?gadget=Maps&action=view&id='.$result;
				} else {
					$redirect = BASE_SCRIPT . '?gadget=Maps&action=view&id='.$postData['ID'];
				}
			} else if ($result2 === true) {
				$redirect = BASE_SCRIPT . '?gadget=Maps&action=view&id='.$postData['LinkID'];
			} else {
				return new Jaws_Error(_t('MAPS_ERROR_ASPPAGE_NOT_RETRIEVED'), _t('MAPS_NAME'));
			}
			
			if (isset($redirect)) {
				Jaws_Header::Location($redirect);
			}

		} else {
            return new Jaws_Error(_t('MAPS_ERROR_ASPPAGE_NOT_RETRIEVED'), _t('MAPS_NAME'));
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
        $GLOBALS['app']->Session->CheckPermission('Maps', 'default');
        $this->AjaxMe('script.js');

        $GLOBALS['app']->Layout->AddScriptLink('libraries/js/admin.js');

		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
						
		$tpl = new Jaws_Template('gadgets/Maps/templates/');
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

        $tpl->SetBlock('gadget_map');

		$request =& Jaws_Request::getInstance();
		$action = $request->getRaw('action', 'get');
		$tpl->SetVariable('workarea-style', '');
        $tpl->SetVariable('actionsTitle', _t('MAPS_ACTIONS'));
        $tpl->SetVariable('menubar', $this->MenuBar($action));

        $tpl->SetVariable('confirmPostDelete', _t('MAPS_POST_CONFIRM_DELETE'));

		// get current Syntacts URL
		$syntactsUrl = $GLOBALS['app']->getSyntactsAdminUrl();
		if ($syntactsUrl) {
			
			// snoopy class
			include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
			$snoopy = new Snoopy('Maps');
					
			$submit_url = $syntactsUrl;
					
			$request =& Jaws_Request::getInstance();
			$pid = $request->getRaw('id', 'get');
			if (!is_null($pid)) {
			
				$model = $GLOBALS['app']->LoadGadget('Maps', 'Model');
					
				// send Page records
				$pageInfo = $model->GetMap($pid);
				
				if (!Jaws_Error::IsError($pageInfo) && ($GLOBALS['app']->Session->GetPermission('Maps', 'ManageMaps') || $pageInfo['ownerid'] == $GLOBALS['app']->Session->GetAttribute('user_id'))) {
				
			        $mapLayout = $GLOBALS['app']->LoadGadget('Maps', 'LayoutHTML');
					// TODO: add more types (rss, xml, featured)
					$mapXHTML = $mapLayout->Display($pid);

				} else {
                    if (Jaws_Error::IsError($pageInfo)) {
						return $pageInfo;
					}
				}

				// send Post records
				$posts = $model->GetAllPostsOfMap($pid);
				
		        if (!Jaws_Error::IsError($posts)) {
			        $i = 0;
			        $j = 0;
					$submit_vars['1:cols'] = 29;
					foreach($posts as $post) {		            
						foreach ($post as $e => $ev) {
			                if ($e == 'description') {
								$submit_vars[SYNTACTS_DB ."1:$j:$i"] = $this->ParseText($ev, 'Maps');
							} else {
								$submit_vars[SYNTACTS_DB ."1:$j:$i"] = $xss->filter($ev);
							}
							$j++;
							if ($j == 28) {
								$region = $model->GetRegion((int)$post['region']);
								if (!Jaws_Error::IsError($region) && isset($region['region']) && !empty($region['region'])) {
									$submit_vars[SYNTACTS_DB ."1:28:$i"] = $xss->filter($region['region']);
								}
								$j=29;
							}
							if ($j > $submit_vars['1:cols']) {
								$j=0;
							}
						}
						$i++;
					}
					$submit_vars['1:rows'] = $i-1;
		        } else {
                    //return new Jaws_Error(_t('MAPS_ERROR_POST_NOT_FOUND'), _t('MAPS_NAME'));
				}
			} else {
				// Send us to the appropriate page
				require_once JAWS_PATH . 'include/Jaws/Header.php';
				Jaws_Header::Location(BASE_SCRIPT . '?gadget=Maps');
			}

			$submit_vars['HTTP_REFERER'] = $GLOBALS['app']->GetSiteURL();
			$submit_vars['JAWS_URL'] = $GLOBALS['app']->GetJawsURL() . '/';
			$submit_vars['DPATH'] = '';

			if($snoopy->submit($submit_url,$submit_vars)) {
				//while(list($key,$val) = each($snoopy->headers))
					//echo $key.": ".$val."<br>\n";
				//echo "<p>\n";
				
				//$page = "<PRE>".htmlspecialchars($snoopy->results)."</PRE>\n";
				$page = $snoopy->results;
				$page = str_replace("__JAWS_MAP__", $mapXHTML, $page);
			} else {
				$page = _t('MAPS_ERROR_ASPPAGE_NOT_RETRIEVED', $snoopy->error)."\n";
			}	

			$tpl->SetVariable('content', $page);
		}
        
		$tpl->ParseBlock('gadget_map');

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
        $GLOBALS['app']->Session->CheckPermission('Maps', 'default');

        $this->AjaxMe('script.js');
        $GLOBALS['app']->Layout->AddScriptLink('libraries/js/admin.js');
        $GLOBALS['app']->Layout->AddHeadLink('libraries/autocomplete/autocomplete.css', 'stylesheet', 'text/css', 'default');
		
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$request =& Jaws_Request::getInstance();
				
		$tpl = new Jaws_Template('gadgets/Maps/templates/');
        $tpl->Load('admin.html');

        $tpl->SetBlock('gadget_map');

		$request =& Jaws_Request::getInstance();
		$action = $request->getRaw('action', 'get');
        $tpl->SetVariable('menubar', $this->MenuBar($action));

		$tpl->SetVariable('workarea-style', '');

		$syntactsUrl = $GLOBALS['app']->getSyntactsAdminUrl();
		if ($syntactsUrl) {
			include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
			$snoopy = new Snoopy('Maps');
			$model = $GLOBALS['app']->LoadGadget('Maps', 'Model');
					
			if (!is_null($request->getRaw('gadgetMsg', 'get'))) {
	            $submit_vars['simple-msg:0'] =  _t($request->getRaw('gadgetMsg', 'get'));
			}
			
			$submit_url = $syntactsUrl;
			
			$id = $request->getRaw('id', 'get');

			// send post records
			if (!is_null($id)) {
				$post = $model->GetPost($id);
		        if (!Jaws_Error::IsError($post) && ($GLOBALS['app']->Session->GetPermission('Maps', 'ManageMaps') || $post['ownerid'] == $GLOBALS['app']->Session->GetAttribute('user_id'))) {
					$i = 0;
					$j = 0;
					$submit_vars['0:cols'] = 28;
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

				} else {
                    //return new Jaws_Error(_t('MAPS_ERROR_POST_NOT_FOUND'), _t('MAPS_NAME'));
				}
								
			} else if (!is_null($request->getRaw('linkid', 'get'))) {
				// send highest sort_order
				$sql = "SELECT MAX([sort_order]) FROM [[maps_locations]] ORDER BY [sort_order] DESC";
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
				Jaws_Header::Location(BASE_SCRIPT . '?gadget=Maps');
			}

			// send Post records
			$regions = $model->GetRegionsOfParent(0);
			
			if (!Jaws_Error::IsError($regions)) {
				$i = 0;
				$j = 0;
				$submit_vars['2:cols'] = 9;
				foreach($regions as $region) {		            
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
				$page = _t('MAPS_ERROR_ASPPAGE_NOT_RETRIEVED', $regions->GetMessage())."\n";
				//return new Jaws_Error(_t('MAPS_ERROR_POST_NOT_FOUND'), _t('MAPS_NAME'));
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
			$editor =& $GLOBALS['app']->LoadEditor('Maps', 'description', $description, false);
			$editor->TextArea->SetStyle('width: 100%;');
			$editor->SetWidth('490px');

			$submit_vars['HTTP_REFERER'] = $GLOBALS['app']->GetSiteURL();
			$submit_vars['JAWS_URL'] = $GLOBALS['app']->GetJawsURL() . '/';
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
					$page = _t('MAPS_ERROR_ASPPAGE_NOT_RETRIEVED', $snoopy->error)."\n";
				}
			}

			$tpl->SetVariable('content', $page);
		}
		
        $tpl->ParseBlock('gadget_map');

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

        $tpl = new Jaws_Template('gadgets/Maps/templates/');
        $tpl->Load('admin.html');
        $tpl->SetBlock('properties');

		$request =& Jaws_Request::getInstance();
		$action = $request->getRaw('action', 'get');
        $tpl->SetVariable('menubar', $this->MenuBar($action));

        $tpl->SetVariable('base_script', BASE_SCRIPT);
        $tpl->SetVariable('settings', _t('GLOBAL_SETTINGS'));

        $model = $GLOBALS['app']->LoadGadget('Maps', 'AdminModel');
        $googleMapsKey = $GLOBALS['app']->Registry->Get('/gadgets/Maps/googlemaps_key');

        $keyEntry =& Piwi::CreateWidget('Entry', 'googlemaps_key', $googleMapsKey);
        $keyEntry->SetStyle('zwidth: 100%;');
        $tpl->SetVariable('key_entry', $keyEntry->Get());

        $saveButton =& Piwi::CreateWidget('Button', 'Save', _t('GLOBAL_SAVE'), STOCK_SAVE);
        $saveButton->AddEvent(ON_CLICK,
                             "javascript: setRegistryKey($('googlemaps_key').value);");

        $tpl->SetVariable('save_button', $saveButton->Get());
        $tpl->SetVariable('key_label', _t('MAPS_GOOGLE_KEY'));
        $tpl->SetVariable('key_extra', _t('MAPS_GOOGLE_EXTRA', $GLOBALS['app']->GetSiteURL()));

        $tpl->ParseBlock('properties');
        return $tpl->Get();
		
		/*
		$request =& Jaws_Request::getInstance();
		$search = $request->getRaw('search', 'get');
		$tpl = new Jaws_Template('gadgets/Maps/templates/');
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
        $output = $tpl->Get();
		$model = $GLOBALS['app']->LoadGadget('Maps', 'Model');
		$cities = $model->GetGeonamesCitiesOfRegion($search);
		foreach ($cities as $city) {
			foreach ($city as $ck => $cv) {
				$output .= '<br />'.$ck.' = ' .$cv; 
			}
		}
		return $output;
		*/
	}
}
