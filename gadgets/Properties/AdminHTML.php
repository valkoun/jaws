<?php
/**
 * Properties Gadget Admin
 *
 * @category   GadgetAdmin
 * @package    Properties
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class PropertiesAdminHTML extends Jaws_GadgetHTML
{
    /**
     * Constructor
     *
     * @access public
     */
    function PropertiesAdminHTML()
    {
        $this->Init('Properties');
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
        $actions = array('Admin','form','form_post','A','A_form','A_form_post','A_form2','A_form_post2','B','B_form','B_form_post','B2','B_form2','B_form_post2','C','C_form','C_form_post','Settings');
        if (!in_array($selected, $actions)) {
            $selected = 'Admin';
        }

        require_once JAWS_PATH . 'include/Jaws/Widgets/Menubar.php';
        $menubar = new Jaws_Widgets_Menubar();
        if ($GLOBALS['app']->Session->GetPermission('Properties', 'ManageProperties')) {
            $menubar->AddOption('Admin', _t('PROPERTIES_MENU_ADMIN'),
                                'admin.php?gadget=Properties&amp;action=Admin', STOCK_OPEN);
            $menubar->AddOption('A', _t('PROPERTIES_MENU_PROPERTIES'),
                                'admin.php?gadget=Properties&amp;action=A', STOCK_HOME);
        }
        if ($GLOBALS['app']->Session->GetPermission('Properties', 'default')) {
			if (strtolower($selected) != "admin" && (strtolower($selected) == 'form' || strtolower($selected) == 'form_post')) {
				$menubar->AddOption($selected, _t('PROPERTIES_MENU_CATEGORY'),
	                                'javascript:void(0);', $GLOBALS['app']->GetJawsURL() . '/gadgets/Properties/images/home_folder.png');
			}
			if (strtolower($selected) != "admin" && (strtolower($selected) == 'a_form' || strtolower($selected) == 'a_form_post')) {
				$menubar->AddOption($selected, _t('PROPERTIES_MENU_PROPERTY'),
	                                'javascript:void(0);', STOCK_HOME);
			}
            if (strtolower($selected) != "admin" && (strtolower($selected) == 'a_form2' || strtolower($selected) == 'a_form_post2')) {
				$menubar->AddOption($selected, _t('PROPERTIES_MENU_POST'),
	                                'javascript:void(0);', STOCK_EDIT);
			}
			$menubar->AddOption('B', _t('PROPERTIES_MENU_AMENITY'),
								'admin.php?gadget=Properties&amp;action=B', STOCK_INSERT_IMAGE);
            if (strtolower($selected) != "admin" && (strtolower($selected) == 'b' || strtolower($selected) == 'b_form' || strtolower($selected) == 'b_form_post' || strtolower($selected) == 'b2' || strtolower($selected) == 'b_form2' || strtolower($selected) == 'b_form_post2')) {
				$menubar->AddOption('B2', _t('PROPERTIES_MENU_AMENITYTYPES'),
	                                'admin.php?gadget=Properties&amp;action=B2', STOCK_DOCUMENTS);
			}
			/*
			$menubar->AddOption('C', _t('PROPERTIES_MENU_RESERVATIONDATES'),
								'javascript:void(0);', STOCK_CALENDAR);
            if (strtolower($selected) != "admin" && (strtolower($selected) == 'c' || strtolower($selected) == 'c_form' || strtolower($selected) == 'c_form_post' || strtolower($selected) == 'c2' || strtolower($selected) == 'c_form2' || strtolower($selected) == 'c_form_post2')) {
				$menubar->AddOption('C2', _t('PROPERTIES_MENU_RESERVATIONRATES'),
	                                'javascript:void(0);', STOCK_INSERT_TABLE);
			}
			*/
		}
		if ($GLOBALS['app']->Session->GetPermission('Properties', 'ManageProperties')) {
			$menubar->AddOption('Settings', _t('GLOBAL_SETTINGS'),
								'admin.php?gadget=Properties&amp;action=Settings', STOCK_ALIGN_CENTER);
		}
 		$request =& Jaws_Request::getInstance();
		$id = $request->get('id', 'get');
		if (strtolower($selected) == "form" && empty($id)) {
		} else {
			if ($GLOBALS['app']->Session->GetPermission('Properties', 'ManageProperties')) {
				$menubar->AddOption('Add', '',
									'admin.php?gadget=Properties&amp;action=form', STOCK_ADD);
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
        $model = $GLOBALS['app']->LoadGadget('Properties', 'AdminModel');
		$OwnerID = 0;
		if (BASE_SCRIPT == 'index.php') {
			$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');
		}
        $sql = 'SELECT COUNT([propertyparentid]) FROM [[propertyparent]] WHERE [propertyparentownerid] = '.$OwnerID;
        $res = $GLOBALS['db']->queryOne($sql);
        $total = (Jaws_Error::IsError($res) ? 0 : $res);

        $grid =& Piwi::CreateWidget('DataGrid', array());
        $grid->TotalRows($total);
        $grid->SetStyle('width: 100%;');
        $grid->SetID('properties_datagrid');
        $grid->SetAction('next', 'javascript:nextPropertyValues();');
        $grid->SetAction('prev', 'javascript:previousPropertyValues();');
        //$grid->useMultipleSelection();
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_TITLE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', ''));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('PROPERTIES_NAME')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('PROPERTIES_ACTIVE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('PROPERTIES_LAST_UPDATE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_ACTIONS')));

        return $grid->Get();
    }

    /**
     * Builds the basic datagrid view
     *
     * @access  private
     * @return  string   XHTML of datagrid
     */
    function AmenityDataGrid()
    {
        $model = $GLOBALS['app']->LoadGadget('Properties', 'AdminModel');
		$OwnerID = 0;
		if (BASE_SCRIPT == 'index.php') {
			$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');
		}
        $sql = 'SELECT COUNT([id]) FROM [[propertyamenity]] WHERE [ownerid] = '.$OwnerID;
        $res = $GLOBALS['db']->queryOne($sql);
        $total = (Jaws_Error::IsError($res) ? 0 : $res);

        $grid =& Piwi::CreateWidget('DataGrid', array());
        $grid->TotalRows($total);
        $grid->SetStyle('width: 100%;');
        $grid->SetID('amenities_datagrid');
        $grid->setAction('next', 'javascript:nextAmenityValues();');
        $grid->setAction('prev', 'javascript:previousAmenityValues();');
        //$grid->useMultipleSelection();
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_TITLE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', ''));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('PROPERTIES_ACTIVE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('PROPERTIES_LAST_UPDATE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_ACTIONS')));

        return $grid->Get();
    }

    /**
     * Builds the basic datagrid view
     *
     * @access  private
     * @return  string   XHTML of datagrid
     */
    function AmenityTypesDataGrid()
    {
        $model = $GLOBALS['app']->LoadGadget('Properties', 'AdminModel');
		$OwnerID = 0;
		if (BASE_SCRIPT == 'index.php') {
			$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');
		}
        $sql = 'SELECT COUNT([id]) FROM [[amenity_types]] WHERE [ownerid] = '.$OwnerID;
        $res = $GLOBALS['db']->queryOne($sql);
        $total = (Jaws_Error::IsError($res) ? 0 : $res);

        $grid =& Piwi::CreateWidget('DataGrid', array());
        $grid->TotalRows($total);
        $grid->SetStyle('width: 100%;');
        $grid->SetID('amenity_types_datagrid');
        $grid->SetNextAction('javascript:nextAmenityTypeValues();');
        $grid->SetPreviousAction('javascript:previousAmenityTypeValues();');
        //$grid->useMultipleSelection();
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_TITLE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', ''));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('PROPERTIES_ACTIVE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('PROPERTIES_LAST_UPDATE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_ACTIONS')));

        return $grid->Get();
    }

    /**
     * Returns an array with property parents found
     *
     * @access  public
     * @param   string  $status  Status of property parent(s) we want to display
     * @param   string  $search  Keyword (title/description) of parents we want to look for
     * @param   int     $limit   Data limit
     * @return  array   Data
     */
    function GetPropertyParents($status, $search, $limit, $OwnerID = 0)
    {
        $model = $GLOBALS['app']->LoadGadget('Properties', 'AdminModel');
		$pages = $model->SearchPropertyParents($status, $search, $limit, $OwnerID);
        //$pages = $model->SearchPropertyParents($status, $search, $limit);
        if (Jaws_Error::IsError($pages)) {
            return array();
        }

        $data    = array();
		if (BASE_SCRIPT != 'index.php') {
			$edit_url    = BASE_SCRIPT . '?gadget=Properties&amp;action=A&amp;id=';
			$amenity_url    = BASE_SCRIPT . '?gadget=Properties&amp;action=B';
			$reservation_url    = BASE_SCRIPT . '?gadget=Properties&amp;action=C';
        } else {
			$edit_url    = 'index.php?gadget=Properties&amp;action=account_A&amp;id=';
			$amenity_url    = 'index.php?gadget=Properties&amp;action=account_B';
			$reservation_url    = 'index.php?gadget=Properties&amp;action=account_C';
		}
		$date = $GLOBALS['app']->loadDate();
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');

        foreach ($pages as $page) {
            //if ($page['propertyparentparent'] == 0) {
				$pageData = array();
				$pageData['title'] = ($page['propertyparentparent'] > 0 ? '&nbsp;&nbsp;-' : '').'<a href="'.$edit_url.$page['propertyparentid'].'">'.$page['propertyparentcategory_name'].'</a>';
				if (BASE_SCRIPT != 'index.php') {
					$pageData['furl']  = "<a href='javascript:void(0);' onclick='window.open(\"".$GLOBALS['app']->Map->GetURLFor('Properties', 'Category', array('id' => $xss->parse($page['propertyparentfast_url'])))."\");'>View This Category</a>";
				}
				$number = $model->GetAllPropertiesOfParent($page['propertyparentid']);
				if (!Jaws_Error::IsError($number)) {
					$pageData['count'] = count($number);
				} else {
					$pageData['count'] = 0;
				}
				if ($page['propertyparentactive'] == 'Y') {
					$pageData['active'] = _t('PROPERTIES_PUBLISHED');
				} else {
					$pageData['active'] = _t('PROPERTIES_DRAFT');
				}
				$pageData['date']  = $date->Format($page['propertyparentupdated']);
				$actions = '';
				if ($this->GetPermission('ManagePropertyParents')) {
					if (BASE_SCRIPT != 'index.php') {
						$link =& Piwi::CreateWidget('Link', _t('PROPERTIES_EDIT_PROPERTIES'),
													$edit_url.$page['propertyparentid'],
													STOCK_HOME);
					} else {
						$link =& Piwi::CreateWidget('Link', _t('PROPERTIES_EDIT_PROPERTIES'),
													"javascript:window.open('".$edit_url.$page['propertyparentid']."');",
													STOCK_HOME);
					}
					$actions.= $link->Get().'&nbsp;';
				} else {
					if ($ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Properties', 'OwnProperty')) {
						$link =& Piwi::CreateWidget('Link', _t('GLOBAL_EDIT'),
													"javascript:window.open('".$edit_url.$page['propertyparentid']."');",
													STOCK_HOME);
						$actions.= $link->Get().'&nbsp;';
					}
				}

				if ($this->GetPermission('ManagePropertyParents')) {
					$link =& Piwi::CreateWidget('Link', _t('GLOBAL_DELETE'),
												"javascript: if (confirm('"._t('GLOBAL_CONFIRM_DELETE', _t('PROPERTIES_PROPERTYPARENT'))."')) ".
												"deletePropertyParent('".$page['propertyparentid']."');",
												$GLOBALS['app']->GetJawsURL()."/images/ICON_delete2.gif");
					$actions.= $link->Get().'&nbsp;';
				} else {
					if ($ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Properties', 'OwnProperty')) {
						$link =& Piwi::CreateWidget('Link', _t('GLOBAL_DELETE'),
													"javascript: if (confirm('"._t('GLOBAL_CONFIRM_DELETE', _t('PROPERTIES_PROPERTYPARENT'))."')) ".
													"deletePropertyParent('".$page['propertyparentid']."');",
													$GLOBALS['app']->GetJawsURL()."/images/ICON_delete2.gif");
						$actions.= $link->Get().'&nbsp;';
					}
				}
				$pageData['actions'] = $actions;
				$pageData['__KEY__'] = $page['propertyparentid'];
				$data[] = $pageData;
				/*
				$propertiesModel = $GLOBALS['app']->LoadGadget('Properties', 'Model');
		        $children = $propertiesModel->GetAllSubCategoriesOfParent($page['propertyparentid']);
		        // Has children, so indent them
				foreach ($children as $child) {
					$pageData['title'] = '&nbsp;&nbsp;-<a href="'.$edit_url.$child['propertyparentid'].'">'.$child['propertyparentcategory_name'].'</a>';
					if (BASE_SCRIPT != 'index.php') {
						$pageData['furl']  = "<a href='javascript:void(0);' onclick='window.open(\"".$GLOBALS['app']->Map->GetURLFor('Properties', 'Category', array('id' => $xss->parse($child['propertyparentfast_url'])))."\");'>View This Category</a>";
					}
					$number = $model->GetAllPropertiesOfParent($child['propertyparentid']);
					if (!Jaws_Error::IsError($number)) {
						$pageData['count'] = count($number);
					} else {
						$pageData['count'] = 0;
					}
					if ($child['propertyparentactive'] == 'Y') {
						$pageData['active'] = _t('PROPERTIES_PUBLISHED');
					} else {
						$pageData['active'] = _t('PROPERTIES_DRAFT');
					}
					$pageData['date']  = $date->Format($child['propertyparentupdated']);
					$actions = '';
					if ($this->GetPermission('ManagePropertyParents')) {
						if (BASE_SCRIPT != 'index.php') {
							$link =& Piwi::CreateWidget('Link', _t('PROPERTIES_EDIT_PROPERTIES'),
														$edit_url.$child['propertyparentid'],
														STOCK_HOME);
						} else {
							$link =& Piwi::CreateWidget('Link', _t('PROPERTIES_EDIT_PROPERTIES'),
														"javascript:window.open('".$edit_url.$child['propertyparentid']."');",
														STOCK_HOME);
						}
						$actions.= $link->Get().'&nbsp;';
					}

					if ($this->GetPermission('ManagePropertyParents')) {
						$link =& Piwi::CreateWidget('Link', _t('GLOBAL_DELETE'),
													"javascript: if (confirm('"._t('GLOBAL_CONFIRM_DELETE', _t('PROPERTIES_PROPERTYPARENT'))."')) ".
													"deletePropertyParent('".$child['propertyparentid']."');",
													$GLOBALS['app']->GetJawsURL()."/images/ICON_delete2.gif");
						$actions.= $link->Get().'&nbsp;';
					} else {
						if ($ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Properties', 'OwnProperty')) {
							$link =& Piwi::CreateWidget('Link', _t('GLOBAL_DELETE'),
														"javascript: if (confirm('"._t('GLOBAL_CONFIRM_DELETE', _t('PROPERTIES_PROPERTYPARENT'))."')) ".
														"deletePropertyParent('".$child['propertyparentid']."');",
														$GLOBALS['app']->GetJawsURL()."/images/ICON_delete2.gif");
							$actions.= $link->Get().'&nbsp;';
						}
					}
					$pageData['actions'] = $actions;
					$pageData['__KEY__'] = $child['propertyparentid'];
					$data[] = $pageData;
				}
				*/
			//}
		}
        return $data;
    }

    /**
     * Returns an array with property amenities found
     *
     * @access  public
     * @param   string  $status  Status of amenity(s) we want to display
     * @param   string  $search  Keyword (title/description) of amenities we want to look for
     * @param   int     $limit   Data limit
     * @return  array   Data
     */
    function GetAmenities($status, $search, $limit, $OwnerID = 0)
    {
        $model = $GLOBALS['app']->LoadGadget('Properties', 'Model');
        $adminmodel = $GLOBALS['app']->LoadGadget('Properties', 'AdminModel');
		$pages = $adminmodel->SearchAmenities($search, $status, $limit, $OwnerID);
        //$pages = $adminmodel->SearchAmenities($search, $status, $limit);
        if (Jaws_Error::IsError($pages)) {
            return array();
        }

        $data    = array();
		if (BASE_SCRIPT != 'index.php') {
			$edit_url    = BASE_SCRIPT . '?gadget=Properties&amp;action=B_form&amp;id=';
        } else {
			$edit_url    = 'index.php?gadget=Properties&amp;action=account_B_form&amp;id=';
		}
		$date = $GLOBALS['app']->loadDate();
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');

        foreach ($pages as $page) {
			// get amenity type by it's ID
			$typeID = $model->GetAmenityType($page['typeid']);
	        if (!Jaws_Error::IsError($typeID)) {
	            $type = ' - '.$typeID['title'];
	        } else {
	            $type = '';
			}
			$pageData = array();
			$pageData['title'] = '<a href="'.$edit_url.$page['id'].'">'.$page['feature'].$type.'</a>';
			if (BASE_SCRIPT != 'index.php') {
				$pageData['furl']  = '<a href="'.$GLOBALS['app']->Map->GetURLFor('Properties', 'Amenity', array('id' => str_replace(' ', '--', $xss->parse($page['feature'])))).'">View Properties</a>';
			}
			if ($page['active'] == 'Y') {
				$pageData['active'] = _t('PROPERTIES_PUBLISHED');
			} else {
				$pageData['active'] = _t('PROPERTIES_DRAFT');
			}
			$pageData['date']  = $date->Format($page['updated']);
			$actions = '';
			if ($this->GetPermission('ManageProperties')) {
				if (BASE_SCRIPT != 'index.php') {
					$link =& Piwi::CreateWidget('Link', _t('PROPERTIES_EDIT_AMENITY'),
												$edit_url.$page['id'],
												STOCK_EDIT);
				} else {
					$link =& Piwi::CreateWidget('Link', _t('PROPERTIES_EDIT_AMENITY'),
												"javascript:window.open('".$edit_url.$page['id']."');",
												STOCK_EDIT);
				}
				$actions.= $link->Get().'&nbsp;';
			}

			if ($this->GetPermission('ManageProperties')) {
				$link =& Piwi::CreateWidget('Link', _t('GLOBAL_DELETE'),
											"javascript: if (confirm('"._t('GLOBAL_CONFIRM_DELETE', _t('PROPERTIES_AMENITY'))."')) ".
											"deleteAmenity('".$page['id']."');",
											$GLOBALS['app']->GetJawsURL()."/images/ICON_delete2.gif");
				$actions.= $link->Get().'&nbsp;';
			} else {
				if ($ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Properties', 'OwnProperty')) {
					$link =& Piwi::CreateWidget('Link', _t('GLOBAL_DELETE'),
												"javascript: if (confirm('"._t('GLOBAL_CONFIRM_DELETE', _t('PROPERTIES_AMENITY'))."')) ".
												"deleteAmenity('".$page['id']."');",
												$GLOBALS['app']->GetJawsURL()."/images/ICON_delete2.gif");
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
     * Returns an array with property amenities found
     *
     * @access  public
     * @param   string  $status  Status of amenity type(s) we want to display
     * @param   string  $search  Keyword (title/description) of amenities we want to look for
     * @param   int     $limit   Data limit
     * @return  array   Data
     */
    function GetAmenityTypes($status, $search, $limit, $OwnerID = 0)
    {
        $model = $GLOBALS['app']->LoadGadget('Properties', 'AdminModel');
		$pages = $model->SearchAmenityTypes($status, $search, $limit, $OwnerID);
        //$pages = $model->SearchAmenityTypes($status, $search, $limit);
        if (Jaws_Error::IsError($pages)) {
            return array();
        }

        $data    = array();
		if (BASE_SCRIPT != 'index.php') {
			$edit_url    = BASE_SCRIPT . '?gadget=Properties&amp;action=B_form2&amp;id=';
        } else {
			$edit_url    = 'index.php?gadget=Properties&amp;action=account_B_form2&amp;id=';
		}
		$date = $GLOBALS['app']->loadDate();
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');

        foreach ($pages as $page) {
			$pageData = array();
			$pageData['title'] = '<a href="'.$edit_url.$page['id'].'">'.$page['title'].'</a>';
			$pageData['furl']  = '';

			if ($page['active'] == 'Y') {
				$pageData['active'] = _t('PROPERTIES_PUBLISHED');
			} else {
				$pageData['active'] = _t('PROPERTIES_DRAFT');
			}
			$pageData['date']  = $date->Format($page['updated']);
			$actions = '';
			if ($this->GetPermission('ManageProperties')) {
				if (BASE_SCRIPT != 'index.php') {
					$link =& Piwi::CreateWidget('Link', _t('PROPERTIES_EDIT_AMENITYTYPE'),
												$edit_url.$page['id'],
												STOCK_EDIT);
				} else {
					$link =& Piwi::CreateWidget('Link', _t('PROPERTIES_EDIT_AMENITYTYPE'),
												"javascript:window.open('".$edit_url.$page['id']."');",
												STOCK_EDIT);
				}
				$actions.= $link->Get().'&nbsp;';
			}

			if ($this->GetPermission('ManageProperties')) {
				$link =& Piwi::CreateWidget('Link', _t('GLOBAL_DELETE'),
											"javascript: if (confirm('"._t('GLOBAL_CONFIRM_DELETE', _t('PROPERTIES_AMENITY_TYPE'))."')) ".
											"deleteAmenityType('".$page['id']."');",
											$GLOBALS['app']->GetJawsURL()."/images/ICON_delete2.gif");
				$actions.= $link->Get().'&nbsp;';
			} else {
				if ($ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Properties', 'OwnProperty')) {
					$link =& Piwi::CreateWidget('Link', _t('GLOBAL_DELETE'),
												"javascript: if (confirm('"._t('GLOBAL_CONFIRM_DELETE', _t('PROPERTIES_AMENITY_TYPE'))."')) ".
												"deleteAmenityType('".$page['id']."');",
												$GLOBALS['app']->GetJawsURL()."/images/ICON_delete2.gif");
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
		$request =& Jaws_Request::getInstance();
		$action = $request->get('action', 'get');
		
		if (empty($action)) {
			require_once JAWS_PATH . 'include/Jaws/Header.php';	
			Jaws_Header::Location(BASE_SCRIPT . '?gadget=Properties&action=A');
		}
		
		if ($account === false) {
			$GLOBALS['app']->Session->CheckPermission('Properties', 'default');
		} else {
			$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
			if (!$GLOBALS['app']->Session->GetPermission('Properties', 'default')) {
				if (!$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Properties', 'OwnProperty')) {
		            //$GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
					return "Please log-in.";
				}
			}
		}
        $tpl = new Jaws_Template('gadgets/Properties/templates/');
        $tpl->Load('admin.html');
        $tpl->SetBlock('properties_admin');
        
		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=CustomPage&amp;action=account_SetGBRoot');

		if ($account === false) {
	        $GLOBALS['app']->Layout->AddScriptLink(BASE_SCRIPT . '?gadget=Properties&amp;action=Ajax&amp;client=all&amp;stub=PropertiesAdminAjax');
	        $GLOBALS['app']->Layout->AddScriptLink(BASE_SCRIPT . '?gadget=Properties&amp;action=AjaxCommonFiles');
	        $GLOBALS['app']->Layout->AddScriptLink('gadgets/Properties/resources/script.js');
			$GLOBALS['app']->Layout->AddScriptLink('libraries/js/admin.js');
			$tpl->SetVariable('menubar', $this->MenuBar($action));
			$account_prefix = '';
			$base_url = BASE_SCRIPT;
		} else {
	        $GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=Properties&amp;action=Ajax&amp;client=all&amp;stub=PropertiesAjax');
	        $GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=Properties&amp;action=AjaxCommonFiles');
	        $GLOBALS['app']->Layout->AddScriptLink('gadgets/Properties/resources/client_script.js');
			$tpl->SetVariable('menubar', '');
			$account_prefix = 'account_';
			$base_url = 'index.php';
		}
        
		$tpl->SetVariable('account', $account_prefix);
		$tpl->SetVariable('base_script', $base_url);

        $tpl->SetVariable('grid', $this->DataGrid());

        $toolBar   =& Piwi::CreateWidget('HBox');

        $deleteAll =& Piwi::CreateWidget('Button', 'deleteAllPropertyParents',
                                         _t('GLOBAL_DELETE'),
                                         STOCK_DELETE);
        $deleteAll->AddEvent(ON_CLICK,
                             "javascript: massiveDelete('"._t('PROPERTIES_CONFIRM_MASIVE_DELETE_PROPERTYPARENT')."');");

        $toolBar->Add($deleteAll);

        $tpl->SetVariable('tools', $toolBar->Get());
                
		if ($account === false) {
	        //Status filter
	        $status = '';
	        $statusCombo =& Piwi::CreateWidget('Combo', 'status');
	        $statusCombo->setId('status');
	        $statusCombo->AddOption('&nbsp;', '');
	        $statusCombo->AddOption(_t('PROPERTIES_PUBLISHED'), 'Y');
	        $statusCombo->AddOption(_t('PROPERTIES_DRAFT'), 'N');
	        $statusCombo->SetDefault($status);
	        $statusCombo->AddEvent(ON_CHANGE, 'javascript: searchPropertyParent();');
	        $tpl->SetVariable('status', _t('PROPERTIES_ACTIVE').':');
	        $tpl->SetVariable('status_field', $statusCombo->Get());
		} else {
	        $searchEntry =& Piwi::CreateWidget('HiddenEntry', 'status', '');
	        $tpl->SetVariable('status_field', $searchEntry->Get());
		}

        // Free text search
        $searchButton =& Piwi::CreateWidget('Button', 'searchButton', _t('GLOBAL_SEARCH'), STOCK_SEARCH);
        $searchButton->AddEvent(ON_CLICK, 'javascript: searchPropertyParent();');
        $tpl->SetVariable('search', $searchButton->Get());

        $search = '';
        $searchEntry =& Piwi::CreateWidget('Entry', 'search', $search);
        $searchEntry->SetStyle('zwidth: 100%;');
        $tpl->SetVariable('search_field', $searchEntry->Get());

        $tpl->SetVariable('entries', $this->Datagrid());

		// Add button is added by HTML->GetUserAccountControls
		if ($account === false) {
	        $addPage =& Piwi::CreateWidget('Button', 'add_propertyparent', _t('PROPERTIES_ADD_PROPERTYPARENT'), STOCK_ADD);
			$addPage->AddEvent(ON_CLICK, "javascript: window.location = '".$base_url."?gadget=Properties&amp;action=".$account_prefix."form';");
	        $tpl->SetVariable('add_propertyparent', $addPage->Get());
		} else {
			//$addPage->AddEvent(ON_CLICK, "javascript: window.open('".$base_url."?gadget=CustomPage&amp;action=".$account_prefix."form','','scrollbars=1,menubar=0,toolbar=0,location=0,status=1');");
	        $tpl->SetVariable('add_propertyparent', '');
		}

        $tpl->ParseBlock('properties_admin');

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
		if ($account === false) {
			$GLOBALS['app']->Session->CheckPermission('Properties', 'default');
		} else {
			$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
			if (!$GLOBALS['app']->Session->GetPermission('Properties', 'default')) {
				if (!$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Properties', 'OwnProperty')) {
		            $GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
					$userHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
					return $userHTML->DefaultAction();
				}
			}
		}

		// document dependencies
		
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$model = $GLOBALS['app']->LoadGadget('Properties', 'Model');
		$request =& Jaws_Request::getInstance();
		$gather = array('action', 'id');
		$get = $request->get($gather, 'get');

		// initialize template
		$tpl = new Jaws_Template('gadgets/Properties/templates/');
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

        $tpl->SetBlock('gadget_property');

		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=CustomPage&amp;action=account_SetGBRoot');
		// account differences
		if ($account === false) {
			$this->AjaxMe('script.js');
			$tpl->SetVariable('menubar', $this->MenuBar($get['action']));
			$GLOBALS['app']->Layout->AddScriptLink('libraries/js/admin.js');			
			$submit_vars['ACTIONPREFIX'] = "";
			$submit_vars['CLOSE_BUTTON'] = "location.href='" . BASE_SCRIPT . "?gadget=Properties&amp;action=Admin';";
			$OwnerID = 0;
			$base_url = BASE_SCRIPT;
		} else {
			$tpl->SetVariable('menubar', '');
			$this->AjaxMe('client_script.js');
			$submit_vars['ACTIONPREFIX'] = "account_";
			$submit_vars['CLOSE_BUTTON'] = "parent.parent.hideGB();";
			$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');
			$base_url = 'index.php';
		}
		$syntactsUrl = $GLOBALS['app']->getSyntactsAdminHTMLUrl('Properties/admin_Properties_form');
		$tpl->SetVariable('workarea-style', "style=\"float: left; margin-top: 30px;\" ");

		// syntacts page
		if ($syntactsUrl) {
			// snoopy
			include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
			$snoopy = new Snoopy('Properties');
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
				if (!empty($get['id'])) {
					// send page records
					$pageInfo = $model->GetPropertyParent($get['id']);
					if (!Jaws_Error::IsError($pageInfo) && ($GLOBALS['app']->Session->GetPermission('Properties', 'ManageProperties') || $pageInfo['propertyparentownerid'] == $OwnerID)) {
						$submit_vars['SUBMIT_BUTTON'] = "<input type='Submit' name='Submit' value='Update'>";
					} else {
						//$error = _t('PROPERTIES_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403'));												
						return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTYPARENT_NOT_FOUND'), _t('PROPERTIES_NAME'));
					}
				} else {
					$submit_vars['SUBMIT_BUTTON'] = "<input type='Submit' name='Submit' value='Add'>";
				}

				// send requesting URL to syntacts
				$stpl->SetVariable('HTTP_REFERER', $GLOBALS['app']->GetSiteURL());
				$stpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . '/');
				//$stpl->SetVariable('DPATH', JAWS_DPATH);
				$stpl->SetVariable('actionprefix', $submit_vars['ACTIONPREFIX']);
				$stpl->SetVariable('gadget', 'Properties');
				$stpl->SetVariable('CLOSE_BUTTON', $submit_vars['CLOSE_BUTTON']);
				$stpl->SetVariable('SUBMIT_BUTTON', $submit_vars['SUBMIT_BUTTON']);
				$stpl->SetVariable('controller', $base_url);
				
				// Get Help documentation
				$help_url = $GLOBALS['app']->getSyntactsAdminHTMLUrl("Properties/admin_Properties_form_help", 'txt');
				$snoopy = new Snoopy('Properties');
		
				if($snoopy->fetch($help_url)) {
					$helpContent = Jaws_Utils::split2D($snoopy->results);
				}
								
				// Hidden elements
				$ID = (isset($pageInfo['propertyparentid'])) ? $pageInfo['propertyparentid'] : '';
				$idHidden =& Piwi::CreateWidget('HiddenEntry', 'propertyparentID', $ID);
		        $form_content .= $idHidden->Get()."\n";

				// send highest sort_order
				$sort_order = 0;
				if (isset($pageInfo['propertyparentsort_order'])) {
					$sort_order = $pageInfo['propertyparentsort_order'];
				} else {
					$sql = "SELECT MAX([propertyparentsort_order]) FROM [[propertyparent]] ORDER BY [propertyparentsort_order] DESC";
					$max = $GLOBALS['db']->queryOne($sql);
					if (Jaws_Error::IsError($max)) {
						return $max;
					} else if ($max >= 0) {
						$sort_order = $max+1;
					}
				}
				
				$sort_orderHidden =& Piwi::CreateWidget('HiddenEntry', 'propertyparentsort_order', $sort_order);
		        $form_content .= $sort_orderHidden->Get()."\n";

				$fuseaction = (isset($pageInfo['propertyparentid'])) ? 'EditPropertyParent' : 'AddPropertyParent';
				$fuseactionHidden =& Piwi::CreateWidget('HiddenEntry', 'fuseaction', $fuseaction);
		        $form_content .= $fuseactionHidden->Get()."\n";

				$featured = (isset($pageInfo['propertyparentfeatured'])) ? $pageInfo['propertyparentfeatured'] : 'N';
				$featuredHidden =& Piwi::CreateWidget('HiddenEntry', 'propertyparentFeatured', $featured);
		        $form_content .= $featuredHidden->Get()."\n";
				
				if ($account === false) {
					// Active
					$helpString = '';
					foreach($helpContent as $help) {		            
						if ($help[0] == _t('PROPERTIES_PUBLISHED')) {
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
					$active = (isset($pageInfo['propertyparentactive'])) ? $pageInfo['propertyparentactive'] : 'Y';
					$activeCombo =& Piwi::CreateWidget('Combo', 'propertyparentActive');
					$activeCombo->AddOption(_t('PROPERTIES_PUBLISHED'), 'Y');
					$activeCombo->AddOption(_t('PROPERTIES_DRAFT'), 'N');
					$activeCombo->SetDefault($active);
					$activeCombo->setTitle(_t('PROPERTIES_PUBLISHED'));
					$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"propertyparentActive\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$activeCombo->Get()."</td></tr>";
					
					// Randomize
					$helpString = '';
					foreach($helpContent as $help) {		            
						if ($help[0] == _t('PROPERTIES_RANDOMIZE')) {
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
					$randomize = (isset($pageInfo['propertyparentrandomize'])) ? $pageInfo['propertyparentrandomize'] : 'Y';
					$randomizeCombo =& Piwi::CreateWidget('Combo', 'propertyparentrandomize');
					$randomizeCombo->AddOption(_t('GLOBAL_YES'), 'Y');
					$randomizeCombo->AddOption(_t('GLOBAL_NO'), 'N');
					$randomizeCombo->SetDefault($randomize);
					$randomizeCombo->setTitle(_t('PROPERTIES_RANDOMIZE'));
					$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"propertyparentrandomize\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$randomizeCombo->Get()."</td></tr>";
					
					// Parent
					$helpString = '';
					foreach($helpContent as $help) {		            
						if ($help[0] == _t('PROPERTIES_PARENT')) {
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
					$post_url = (isset($pageInfo['propertyparentparent']) && !strpos($pageInfo['propertyparentparent'], "://")) ? $pageInfo['propertyparentparent'] : '';
					$urlListCombo =& Piwi::CreateWidget('Combo', 'propertyparentParent');
					$urlListCombo->setID('propertyparentParent');

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
							if ($m['id'] != $pageInfo['id']) {
								if ($m['visible'] == 0) {
									$urlListCombo->AddOption("<i>".$m['menu_type']." : ".$m['title']."</i>", $m['id']);
								} else {
									$urlListCombo->AddOption($m['menu_type']." : ".$m['title'], $m['id']);
								}
							}
						}
					}
					$urlListCombo->setDefault($post_url);
					$urlListCombo->setTitle(_t('PROPERTIES_PARENT'));
					$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"pid\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$urlListCombo->Get()."</td></tr>";

					// Title
					$helpString = '';
					foreach($helpContent as $help) {		            
						if ($help[0] == _t('PROPERTIES_TITLE')) {
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
					$title = (isset($pageInfo['propertyparentcategory_name'])) ? $pageInfo['propertyparentcategory_name'] : '';
					$titleEntry =& Piwi::CreateWidget('Entry', 'propertyparentCategory_Name', $title);
					$titleEntry->SetTitle(_t('PROPERTIES_TITLE'));
					$titleEntry->SetStyle('direction: ltr; width: 300px;');
					$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"propertyparentCategory_Name\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$titleEntry->Get()."</td></tr>";

					// Description
					$helpString = '';
					foreach($helpContent as $help) {		            
						if ($help[0] == _t('PROPERTIES_DESCRIPTIONFIELD')) {
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
					$content = (isset($pageInfo['propertyparentdescription'])) ? $pageInfo['propertyparentdescription'] : '';
					$editor =& $GLOBALS['app']->LoadEditor('Properties', 'propertyparentDescription', $content, false);
					$editor->TextArea->SetStyle('width: 100%;');
					//$editor->SetWidth('100%');
					$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"propertyparentDescription\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$editor->Get()."</td></tr>";

					// Image
					$helpString = '';
					foreach($helpContent as $help) {		            
						if ($help[0] == _t('PROPERTIES_IMAGE')) {
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
					$image = (isset($pageInfo['propertyparentimage'])) ? $pageInfo['propertyparentimage'] : '';
					$image_src = $GLOBALS['app']->getDataURL() . 'files'.$xss->parse($pageInfo['propertyparentimage']);
					$image_preview = '';
					if ($image != '' && file_exists($image_src)) { 
						$image_preview .= "<br /><img border=\"0\" src=\"".$image_src."\" width=\"80\"".(strtolower(substr($image, -3)) == 'gif' || strtolower(substr($image, -3)) == 'png' || strtolower(substr($image, -3)) == 'bmp' ? ' height="80"' : '')." align=\"left\" style=\"padding: 5px;\">";
					}
					$imageScript = "<script type=\"text/javascript\">Event.observe(window, \"load\",function(){addFileToPost('Properties', 'NULL', 'NULL', 'main_image', 'propertyparentImage', 1, 500, 34);});</script>";
					$imageHidden =& Piwi::CreateWidget('HiddenEntry', 'propertyparentImage', $image);
					$imageButton = "&nbsp;&nbsp;&nbsp;&nbsp;<INPUT TYPE=\"button\" VALUE=\"Uploaded Files\" ONCLICK=\"openUploadWindow('propertyparentImage')\" STYLE=\"font-family: Arial; font-size: 10pt; font-weight: bold\">";
					$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"propertyparentImage\"><nobr>".$helpString."</nobr></label>".$image_preview."</td><td class=\"syntacts-form-row\"><div id=\"main_image\" style=\"float: left; width: 500px;\"></div>".$imageScript.$imageHidden->Get().$imageButton."</td></tr>";
				} else {
					$activeHidden =& Piwi::CreateWidget('HiddenEntry', 'propertyparentActive', 'N');
					$form_content .= $activeHidden->Get()."\n";
					
					$randomizeHidden =& Piwi::CreateWidget('HiddenEntry', 'propertyparentrandomize', 'N');
					$form_content .= $randomizeHidden->Get()."\n";
					
					$parentHidden =& Piwi::CreateWidget('HiddenEntry', 'propertyparentParent', '0');
					$form_content .= $parentHidden->Get()."\n";
					
					$titleHidden =& Piwi::CreateWidget('HiddenEntry', 'propertyparentCategory_Name', 'Imported Properties');
					$form_content .= $titleHidden->Get()."\n";
					
					$descHidden =& Piwi::CreateWidget('HiddenEntry', 'propertyparentDescription', '');
					$form_content .= $descHidden->Get()."\n";
					
					$imageHidden =& Piwi::CreateWidget('HiddenEntry', 'propertyparentImage', '');
					$form_content .= $imageHidden->Get()."\n";
				}
					
				  
				// RSS URL
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('PROPERTIES_RSSURL')) {
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
				$url = (isset($pageInfo['propertyparentrss_url'])) ? $pageInfo['propertyparentrss_url'] : '';
				$urlEntry =& Piwi::CreateWidget('Entry', 'propertyparentRss_url', $url);
				$urlEntry->SetTitle(_t('PROPERTIES_RSSURL'));
				$urlEntry->SetStyle('direction: ltr; width: 300px;');
				$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"propertyparentRss_url\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$urlEntry->Get()."</td></tr>";

				// Override City on Import
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('PROPERTIES_OVERRIDECITY')) {
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
				$keyword = (isset($pageInfo['propertyparentrss_overridecity'])) ? $pageInfo['propertyparentrss_overridecity'] : '';
				$keywordEntry =& Piwi::CreateWidget('Entry', 'propertyparentRss_overridecity', $keyword);
				$keywordEntry->SetTitle(_t('PROPERTIES_OVERRIDECITY'));
				$keywordEntry->SetStyle('direction: ltr; width: 300px;');
				$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"propertyparentRss_overridecity\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$keywordEntry->Get()."</td></tr>";

				if ($error != '') {
					$stpl->SetVariable('content', $error);
				} else {
					$stpl->SetVariable('content', $form_content);
		        }
				$stpl->ParseBlock('form');
				$page = $stpl->Get();
			} else {
				$page = _t('PROPERTIES_ERROR_ASPPAGE_NOT_RETRIEVED', $snoopy->error)."\n";
			}
			
			$tpl->SetVariable('content', $page);
		} else {
			return new Jaws_Error(_t('PROPERTIES_ERROR_ASPPAGE_NOT_RETRIEVED'), _t('PROPERTIES_NAME'));
		}
		
        $tpl->ParseBlock('gadget_property');
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
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if ($account === false) {
			$GLOBALS['app']->Session->CheckPermission('Properties', 'default');
		} else {
			if (!$GLOBALS['app']->Session->GetPermission('Properties', 'default')) {
				if (!$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Properties', 'OwnProperty')) {
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
		$get = $request->get(array('fuseaction', 'pct', 'linkid', 'id'), 'get');
        if (empty($fuseaction)) {
			$fuseaction = $get['fuseaction'];
		}
        
		$adminModel = $GLOBALS['app']->LoadGadget('Properties', 'AdminModel');
		$model = $GLOBALS['app']->LoadGadget('Properties', 'Model');
		$redirect_response = '';

        if (!empty($fuseaction)) {		
			switch($fuseaction) {
                case "AddPropertyParent": 
						$keys = array('propertyparentCategory_Name', 'propertyparentParent', 
						'propertyparentsort_order', 'propertyparentDescription', 'propertyparentActive', 
						'propertyparentImage', 'propertyparentFeatured', 'propertyparentRss_url', 
						'propertyparentRegionID', 'propertyparentRss_overridecity', 'propertyparentrandomize');
						if (count($params) > 0) {
							$postData = $params;
						} else {	
							$postData = $request->getRaw($keys, 'post');
						}
						//foreach($postData as $key => $value) {
							//echo $key."=".$value."\n";
						//}
						// add OwnerID if no permissions
						if ($GLOBALS['app']->Session->GetPermission('Properties', 'ManagePropertyParents') && $account === false) {
							$OwnerID = null;
						} else {
							$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
						}
						$result = $adminModel->AddPropertyParent($postData['propertyparentsort_order'], $postData['propertyparentParent'], 
										$postData['propertyparentCategory_Name'], $postData['propertyparentDescription'], 
										$postData['propertyparentImage'], $postData['propertyparentFeatured'], $postData['propertyparentActive'], 
										$OwnerID, $postData['propertyparentRss_url'], $postData['propertyparentRegionID'], $postData['propertyparentRss_overridecity'], 
										$postData['propertyparentrandomize']
						);
						if (!Jaws_Error::IsError($result)) {
					        // declare result as ok for later
							$editpropertyparent = true;
							if (isset($postData['propertyparentRss_url']) && !empty($postData['propertyparentRss_url'])) {	
								$output_html = '<script src="http://yui.yahooapis.com/2.8.0r4/build/yahoo/yahoo-min.js"></script>';
								$output_html .= '<script src="http://yui.yahooapis.com/2.8.0r4/build/event/event-min.js"></script>';
								$output_html .= '<script src="http://yui.yahooapis.com/2.8.0r4/build/connection/connection_core-min.js"></script>';

								$output_html .= '<script>var spawnCallback = {';
								$output_html .= 'success: function(o) {';
								$output_html .= '},';
								$output_html .= 'failure: function(o) {';
								$output_html .= '},';
								$output_html .= 'timeout: 2000';
								$output_html .= '};';

								$output_html .= 'function spawnProcess() {';
								$output_html .= 'YAHOO.util.Connect.asyncRequest(\'GET\',\''.$GLOBALS['app']->getSiteURL().'/index.php?gadget=Properties&action=UpdateRSSProperties&id='.$result.'&ua=N\',spawnCallback);';
								$output_html .= '}';
								$output_html .= 'spawnProcess();</script>';
								//exec ("/usr/local/bin/php /homepages/40/d298423861/htdocs/cli.php --id=$cmd >/dev/null &");
								//backgroundPost($GLOBALS['app']->getSiteURL().'/index.php?gadget=Properties&action=UpdateRSSProperties&id='.$cmd);
							}
						} else {
							$editpropertyparent = false;
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
                case "EditPropertyParent": 
						$keys = array('propertyparentID', 'propertyparentCategory_Name', 'propertyparentParent', 
						'propertyparentsort_order', 'propertyparentDescription', 'propertyparentActive', 
						'propertyparentImage', 'propertyparentFeatured', 'propertyparentRss_url', 
						'propertyparentRegionID', 'propertyparentRss_overridecity', 'propertyparentrandomize');
						if (count($params) > 0) {
							$postData = $params;
						} else {	
							$postData = $request->getRaw($keys, 'post');
						}
						if (isset($postData['propertyparentID'])) {
							// add OwnerID if no permissions
							if ($GLOBALS['app']->Session->GetPermission('Properties', 'ManagePropertyParents') && $account === false) {
								$OwnerID = null;			
								$result = $adminModel->UpdatePropertyParent((int)$postData['propertyparentID'], $postData['propertyparentParent'], 
									$postData['propertyparentCategory_Name'], $postData['propertyparentsort_order'], $postData['propertyparentDescription'], 
									$postData['propertyparentImage'], $postData['propertyparentFeatured'], $postData['propertyparentActive'],
									$postData['propertyparentRss_url'], $postData['propertyparentRegionID'], $postData['propertyparentRss_overridecity'], 
									$postData['propertyparentrandomize']
								);
							} else {
								$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
								$parent = $model->GetPropertyParent((int)$postData['propertyparentID']);
								if ($OwnerID == $parent['propertyparentownerid']) {
									$result = $adminModel->UpdatePropertyParent($parent['propertyparentid'], $postData['propertyparentParent'], 
									$postData['propertyparentCategory_Name'], $postData['propertyparentsort_order'], $postData['propertyparentDescription'], 
									$postData['propertyparentImage'], $postData['propertyparentFeatured'], $postData['propertyparentActive'], 
									$postData['propertyparentRss_url'], $postData['propertyparentRegionID'], $postData['propertyparentRss_overridecity'], 
									$postData['propertyparentrandomize']
									);
								} else {
									$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_PROPERTYPARENT_NOT_UPDATED'), RESPONSE_ERROR);
									//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
								}
							}
						} else {
							$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_PROPERTYPARENT_NOT_UPDATED'), RESPONSE_ERROR);
							//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
						}
						if (!Jaws_Error::IsError($result)) {
							$editpropertyparent = true;
							if (isset($postData['propertyparentRss_url']) && !empty($postData['propertyparentRss_url'])) {	
								$output_html = '<script src="http://yui.yahooapis.com/2.8.0r4/build/yahoo/yahoo-min.js"></script>';
								$output_html .= '<script src="http://yui.yahooapis.com/2.8.0r4/build/event/event-min.js"></script>';
								$output_html .= '<script src="http://yui.yahooapis.com/2.8.0r4/build/connection/connection_core-min.js"></script>';

								$output_html .= '<script>var spawnCallback = {';
								$output_html .= 'success: function(o) {';
								$output_html .= '},';
								$output_html .= 'failure: function(o) {';
								$output_html .= '},';
								$output_html .= 'timeout: 2000';
								$output_html .= '};';

								$output_html .= 'function spawnProcess() {';
								$output_html .= 'YAHOO.util.Connect.asyncRequest(\'GET\',\''.$GLOBALS['app']->getSiteURL().'/index.php?gadget=Properties&action=UpdateRSSProperties&id='.$parent['propertyparentownerid'].'&ua=N\',spawnCallback);';
								$output_html .= '}';
								$output_html .= 'spawnProcess();</script>';
								//exec ("/usr/local/bin/php /homepages/40/d298423861/htdocs/cli.php --id=$cmd >/dev/null &");
								//backgroundPost($GLOBALS['app']->getSiteURL().'/index.php?gadget=Properties&action=UpdateRSSProperties&id='.$cmd);
							}
						} else {
							$editpropertyparent = false;
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
                case "DeletePropertyParent": 
				        //$keys = array('idarray', 'ID', 'xcount');
				        $keys = array('propertyparentID');
						$postData = $request->getRaw($keys, 'post');
						$id = $postData['propertyparentID'];
						if (empty($id)) {
							$id = $get['id'];
						}
						// delete each ID
						if ($id) {
							// check OwnerID if no permissions
							if ($GLOBALS['app']->Session->GetPermission('Properties', 'ManagePropertyParents') && $account === false) {
								$result = $adminModel->DeletePropertyParent($id);
							} else {
								$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
								$parent = $model->GetPropertyParent($id);
								if ($OwnerID == $parent['propertyparentownerid']) {
									$result = $adminModel->DeletePropertyParent($id);
								} else {
									$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_PROPERTYPARENT_NOT_DELETED'), RESPONSE_ERROR);
									//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
								}
							}
						}						
						if (!Jaws_Error::IsError($result)) {
							$deletepropertyparent = true;
						} else {
							$deletepropertyparent = false;
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
                case "AddProperty": 
						$keys = array('LinkID', 'sort_order', 'category', 'mls', 'title', 'image', 
				'sm_description', 'description', 'address', 'city', 'region', 'postal_code', 'country_id', 
				'community', 'phase', 'lotno', 'price', 'rentdy', 'rentwk', 'rentmo', 'status', 'acreage', 
				'sqft', 'bedroom', 'bathroom', 'amenity', 'i360', 'maxchildno', 'maxadultno', 'petstay', 
				'occupancy', 'maxcleanno', 'roomcount', 'minstay', 'options', 'item1', 'item2', 'item3', 
				'item4', 'item5', 'premium', 'ShowMap', 'featured', 'Active', 
				'propertyno', 'internal_propertyno', 'alink', 'alinkTitle', 'alinkType', 'alink2', 
				'alink2Title', 'alink2Type', 'alink3', 'alink3Title', 'alink3Type', 'calendar_link', 'year',
				'coordinates');
						if (count($params) > 0) {
							$postData = $params;
						} else {	
							$postData = $request->getRaw($keys, 'post');
						}
						//foreach($postData as $key => $value) {
							//echo $key."=".$value."\n";
						//}
						// add OwnerID if no permissions
						if ($GLOBALS['app']->Session->GetPermission('Properties', 'ManageProperties') && $account === false) {
							$OwnerID = null;
						} else {
							$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
						}
						$result = $adminModel->AddProperty($postData['LinkID'], $postData['sort_order'], 
							$postData['category'], $postData['mls'], $postData['title'], $postData['image'], 
							$postData['sm_description'], $postData['description'], $postData['address'], 
							$postData['city'], $postData['region'], $postData['postal_code'], $postData['country_id'], 
							$postData['community'], $postData['phase'], $postData['lotno'], $postData['price'], 
							$postData['rentdy'], $postData['rentwk'], $postData['rentmo'], $postData['status'], 
							$postData['acreage'], $postData['sqft'], $postData['bedroom'], $postData['bathroom'], 
							$postData['amenity'], $postData['i360'], $postData['maxchildno'], $postData['maxadultno'], 
							$postData['petstay'], $postData['occupancy'], $postData['maxcleanno'], 
							$postData['roomcount'], $postData['minstay'], $postData['options'], $postData['item1'], 
							$postData['item2'], $postData['item3'], $postData['item4'], $postData['item5'], 
							$postData['premium'], $postData['ShowMap'], $postData['featured'], $OwnerID, 
							$postData['Active'], $postData['propertyno'], $postData['internal_propertyno'],
							$postData['alink'], $postData['alinkTitle'], $postData['alinkType'], 
							$postData['alink2'], $postData['alink2Title'], $postData['alink2Type'], 
							$postData['alink3'], $postData['alink3Title'], $postData['alink3Type'], $postData['calendar_link'], 
							$postData['year'], '', '', '', '', '', '', '', '', '', '', '', $postData['coordinates']);
						if (!Jaws_Error::IsError($result)) {
					        // declare result as ok for later
							$editproperty = true;
							$redirect_response = '&linkid='.$result;
						} else {
							$editproperty = false;
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
                case "EditProperty": 
						$keys = array('ID', 'LinkID', 'sort_order', 'category', 'mls', 'title', 'image', 
				'sm_description', 'description', 'address', 'city', 'region', 'postal_code', 'country_id', 
				'community', 'phase', 'lotno', 'price', 'rentdy', 'rentwk', 'rentmo', 'status', 'acreage', 
				'sqft', 'bedroom', 'bathroom', 'amenity', 'i360', 'maxchildno', 'maxadultno', 'petstay', 
				'occupancy', 'maxcleanno', 'roomcount', 'minstay', 'options', 'item1', 'item2', 'item3', 
				'item4', 'item5', 'premium', 'ShowMap', 'featured', 'Active', 
				'propertyno', 'internal_propertyno', 'alink', 'alinkTitle', 'alinkType', 'alink2', 'alink2Title', 
				'alink2Type', 'alink3', 'alink3Title', 'alink3Type', 'calendar_link', 'year', 'coordinates', 'rss_url');
						if (count($params) > 0) {
							$postData = $params;
						} else {	
							$postData = $request->getRaw($keys, 'post');
						}
						if (isset($postData['ID'])) {
							// add OwnerID if no permissions
							if ($GLOBALS['app']->Session->GetPermission('Properties', 'ManageProperties') && $account === false) {
								$result = $adminModel->UpdateProperty((int)$postData['ID'], $postData['LinkID'], $postData['sort_order'], 
							$postData['category'], $postData['mls'], $postData['title'], $postData['image'], 
							$postData['sm_description'], $postData['description'], $postData['address'], 
							$postData['city'], $postData['region'], $postData['postal_code'],  $postData['country_id'], 
							$postData['community'], $postData['phase'], $postData['lotno'], $postData['price'], 
							$postData['rentdy'], $postData['rentwk'], $postData['rentmo'], $postData['status'], 
							$postData['acreage'], $postData['sqft'], $postData['bedroom'], $postData['bathroom'], 
							$postData['amenity'], $postData['i360'], $postData['maxchildno'], $postData['maxadultno'], 
							$postData['petstay'], $postData['occupancy'], $postData['maxcleanno'], 
							$postData['roomcount'], $postData['minstay'], $postData['options'], $postData['item1'], 
							$postData['item2'], $postData['item3'], $postData['item4'], $postData['item5'], 
							$postData['premium'], $postData['ShowMap'], $postData['featured'], 
							$postData['Active'], $postData['propertyno'], $postData['internal_propertyno'], 
							$postData['alink'], $postData['alinkTitle'], $postData['alinkType'], 
							$postData['alink2'], $postData['alink2Title'], $postData['alink2Type'], 
							$postData['alink3'], $postData['alink3Title'], $postData['alink3Type'], $postData['calendar_link'], 
							$postData['year'], $postData['rss_url'], '', '', '', '', '', '', '', '', '', '', $postData['coordinates']);
							} else {
								$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
								$parent = $model->GetProperty((int)$postData['ID']);
								if ($OwnerID == $parent['ownerid']) {
									$result = $adminModel->UpdateProperty($parent['id'], $postData['LinkID'], $postData['sort_order'], 
							$postData['category'], $postData['mls'], $postData['title'], $postData['image'], 
							$postData['sm_description'], $postData['description'], $postData['address'], 
							$postData['city'], $postData['region'], $postData['postal_code'],  $postData['country_id'], 
							$postData['community'], $postData['phase'], $postData['lotno'], $postData['price'], 
							$postData['rentdy'], $postData['rentwk'], $postData['rentmo'], $postData['status'], 
							$postData['acreage'], $postData['sqft'], $postData['bedroom'], $postData['bathroom'], 
							$postData['amenity'], $postData['i360'], $postData['maxchildno'], $postData['maxadultno'], 
							$postData['petstay'], $postData['occupancy'], $postData['maxcleanno'], 
							$postData['roomcount'], $postData['minstay'], $postData['options'], $postData['item1'], 
							$postData['item2'], $postData['item3'], $postData['item4'], $postData['item5'], 
							$postData['premium'], $postData['ShowMap'], $postData['featured'], 
							$postData['Active'], $postData['propertyno'], $postData['internal_propertyno'], 
							$postData['alink'], $postData['alinkTitle'], $postData['alinkType'], 
							$postData['alink2'], $postData['alink2Title'], $postData['alink2Type'], 
							$postData['alink3'], $postData['alink3Title'], $postData['alink3Type'], $postData['calendar_link'], 
							$postData['year'], '', '', '', '', '', '', '', '', '', '', '', $postData['coordinates']);
								} else {
									$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_PROPERTY_NOT_UPDATED'), RESPONSE_ERROR);
									//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
								}
							}
						}
						if (!Jaws_Error::IsError($result)) {
							$editproperty = true;
							$redirect_response = '&linkid='.$postData['ID'];
						} else {
							$editproperty = false;
							$redirect_response = '&linkid='.$postData['ID'];
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
                case "DeleteProperty": 
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
							if ($GLOBALS['app']->Session->GetPermission('Properties', 'ManageProperties') && $account === false) {
								$result = $adminModel->DeleteProperty($id);
							} else {
								$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
								$parent = $model->GetProperty($id);
								if ($OwnerID == $parent['ownerid']) {
									$result = $adminModel->DeleteProperty($id);
								} else {
									$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_PROPERTY_NOT_DELETED'), RESPONSE_ERROR);
									//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
								}
							}
						}						
						if (!Jaws_Error::IsError($result)) {
							$editproperty = true;
							$redirect_response = '&linkid='.$id;
						} else {
							$editproperty = false;
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
                case "AddPost": 
				        $keys = array('sort_order', 'LinkID', 'title', 'description', 
							'Image', 'image_width', 'image_height', 'layout', 'Active');
						if (count($params) > 0) {
							$postData = $params;
						} else {	
							$postData = $request->getRaw($keys, 'post');
						}
						//foreach($postData as $key => $value) {
							//echo $key."=".$value."\n";
						//}
						// add OwnerID if no permissions
						if ($GLOBALS['app']->Session->GetPermission('Properties', 'ManageProperties') && $account === false) {
							$OwnerID = null;
						} else {
							$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
							$parent = $model->GetProperty($postData['LinkID']);
							if ($OwnerID != $parent['ownerid'] || !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Properties', 'OwnProperty')) {
								$GLOBALS['app']->Session->CheckPermission('Properties', 'OwnProperty');
							}
						}
						$result = $adminModel->AddPost($postData['sort_order'], $postData['LinkID'], 
							$postData['title'], $postData['description'], $postData['Image'], 
							$postData['image_width'], $postData['image_height'], $postData['layout'], 
							$postData['Active'], $OwnerID, 'text', '', '', '', '_self');
						if (!Jaws_Error::IsError($result)) {
							$editpost = true;
						} else {
							$editpost = false;
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
                case "EditPost": 
				        $keys = array('ID', 'sort_order', 'LinkID', 'title', 'description', 
							'Image', 'image_width', 'image_height', 'layout', 'Active');
						if (count($params) > 0) {
							$postData = $params;
						} else {	
							$postData = $request->getRaw($keys, 'post');
						}
						//foreach($postData as $key => $value) {
							//echo $key."=".$value."\n";
						//}
						if ($postData['ID']) {
							// check OwnerID if no permissions
							if ($GLOBALS['app']->Session->GetPermission('Properties', 'ManageProperties') && $account === false) {
								$result = $adminModel->UpdatePost($postData['ID'], $postData['sort_order'], 
							$postData['title'], $postData['description'], $postData['Image'], 
							$postData['image_width'], $postData['image_height'], $postData['layout'],
							$postData['Active'], 'text', '', '', '', '_self');
							} else {
								$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
								$post = $model->GetPost($postData['ID']);
								if ($OwnerID == $post['ownerid']) {
									$result = $adminModel->UpdatePost($postData['ID'], 
										$postData['sort_order'], $postData['title'], 
										$postData['description'], $postData['Image'], 
										$postData['image_width'], $postData['image_height'],
										$postData['layout'], $postData['Active'], 'text', '', '', '', '_self');
								} else {
									$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403')), RESPONSE_ERROR);
									//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
								}
							}
						}
						if (!Jaws_Error::IsError($result)) {
							$editpost = true;
						} else {
							$editpost = false;
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
								if ($GLOBALS['app']->Session->GetPermission('Properties', 'ManageProperties') && $account === false) {
									$result = $adminModel->DeletePost((int)$v);
								} else {
									$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
									$post = $model->GetPost((int)$v);
									if ($OwnerID == $post['ownerid']) {
										$result = $adminModel->DeletePost((int)$v);
									} else {
										$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403')), RESPONSE_ERROR);
										//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
									}
								}								
								$dcount++;
							}
						} else if (!empty($id)) {
							if ($GLOBALS['app']->Session->GetPermission('Properties', 'ManageProperties') && $account === false) {
								$result = $adminModel->DeletePost($id);
							} else {
								$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
								$post = $model->GetPost($id);
								if ($OwnerID == $post['ownerid']) {
									$result = $adminModel->DeletePost($id);
								} else {
									$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403')), RESPONSE_ERROR);
									//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
								}
							}
						}
						if (!Jaws_Error::IsError($result)) {
							$editpost = true;
						} else {
							$editpost = false;
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
                 case "AddPropertyAmenity": 
						$keys = array('feature', 'typeID', 'description', 'Active');
						if (count($params) > 0) {
							$postData = $params;
						} else {	
							$postData = $request->getRaw($keys, 'post');
						}
						//foreach($postData as $key => $value) {
							//echo $key."=".$value."\n";
						//}
						// add OwnerID if no permissions
						if ($GLOBALS['app']->Session->GetPermission('Properties', 'ManageProperties') && $account === false) {
							$OwnerID = null;
						} else {
							$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
						}
						$result = $adminModel->AddPropertyAmenity($postData['feature'], $postData['typeID'], $postData['description'], $OwnerID, $postData['Active']);
						if (!Jaws_Error::IsError($result)) {
					        // declare result as ok for later
							$editamenity = true;
						} else {
							$editamenity = false;
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
                case "EditPropertyAmenity": 
						$keys = array('ID', 'feature', 'typeID', 'description', 'Active');
						if (count($params) > 0) {
							$postData = $params;
						} else {	
							$postData = $request->getRaw($keys, 'post');
						}
						if (isset($postData['ID'])) {
							// add OwnerID if no permissions
							if ($GLOBALS['app']->Session->GetPermission('Properties', 'ManageProperties') && $account === false) {
								$result = $adminModel->UpdatePropertyAmenity((int)$postData['ID'], $postData['feature'], 
								$postData['typeID'], $postData['description'], $postData['Active']
								);
							} else {
								$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
								$parent = $model->GetAmenity((int)$postData['ID']);
								if ($OwnerID == $parent['ownerid']) {
									$result = $adminModel->UpdatePropertyAmenity($parent['id'], $postData['feature'], 
									$postData['typeID'], $postData['description'], $postData['Active']
									);
								} else {
									$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_PROPERTYAMENITY_NOT_UPDATED'), RESPONSE_ERROR);
									//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
								}
							}
						}
						if (!Jaws_Error::IsError($result)) {
							$editamenity = true;
						} else {
							$editamenity = false;
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
                case "DeletePropertyAmenity": 
				        //$keys = array('idarray', 'ID', 'xcount');
				        $keys = array('ID', 'linkid');
						$postData = $request->getRaw($keys, 'post');
						$id = $postData['ID'];
						$linkid = $postData['linkid'];
						if (empty($id)) {
							$id = $get['id'];
						}
						if (empty($linkid)) {
							$linkid = $get['linkid'];
						}
						// delete each ID
						if ($id) {
							// check OwnerID if no permissions
							if ($GLOBALS['app']->Session->GetPermission('Properties', 'ManageProperties') && $account === false) {
								$result = $adminModel->DeletePropertyAmenity($id);
							} else {
								$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
								if ($linkid) {
									$parent = $model->GetAmenity($linkid);
									if ($OwnerID == $parent['ownerid']) {
										$result = $adminModel->DeletePropertyAmenity($id);
									} else {
										$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_PROPERTYAMENITY_NOT_DELETED'), RESPONSE_ERROR);
										//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
									}
								} else {
									$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_PROPERTYAMENITY_NOT_DELETED'), RESPONSE_ERROR);
									//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
								}
							}
						}						
						if (!Jaws_Error::IsError($result)) {
							$editamenity = true;
						} else {
							$editamenity = false;
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
                 case "AddAmenityType": 
						$keys = array('title', 'description', 'Active');
						if (count($params) > 0) {
							$postData = $params;
						} else {	
							$postData = $request->getRaw($keys, 'post');
						}
						//foreach($postData as $key => $value) {
							//echo $key."=".$value."\n";
						//}
						// add OwnerID if no permissions
						if ($GLOBALS['app']->Session->GetPermission('Properties', 'ManageProperties') && $account === false) {
							$OwnerID = null;
						} else {
							$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
						}
						$result = $adminModel->AddAmenityType($postData['title'], 
										$postData['description'], $OwnerID,
										$postData['Active']
						);
						if (!Jaws_Error::IsError($result)) {
					        // declare result as ok for later
							$editamenitytype = true;
						} else {
							$editamenitytype = false;
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
                case "EditAmenityType": 
						$keys = array('ID', 'title', 'description', 'Active');
						if (count($params) > 0) {
							$postData = $params;
						} else {	
							$postData = $request->getRaw($keys, 'post');
						}
						if (isset($postData['ID'])) {
							// add OwnerID if no permissions
							if ($GLOBALS['app']->Session->GetPermission('Properties', 'ManageProperties') && $account === false) {
								$result = $adminModel->UpdateAmenityType((int)$postData['ID'], $postData['title'], 
										$postData['description'], $postData['Active']
								);
							} else {
								$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
								$parent = $model->GetAmenityType((int)$postData['ID']);
								if ($OwnerID == $parent['ownerid']) {
									$result = $adminModel->UpdateAmenityType($parent['id'], $postData['title'], 
										$postData['description'], $postData['Active']
									);
								} else {
									$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_AMENITYTYPE_NOT_UPDATED'), RESPONSE_ERROR);
									//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
								}
							}
						}
						if (!Jaws_Error::IsError($result)) {
							$editamenitytype = true;
						} else {
							$editamenitytype = false;
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
                case "DeleteAmenityType": 
				        //$keys = array('idarray', 'ID', 'xcount');
				        $keys = array('ID', 'linkid');
						$postData = $request->getRaw($keys, 'post');
						$id = $postData['ID'];
						$linkid = $postData['linkid'];
						if (empty($id)) {
							$id = $get['id'];
						}
						if (empty($linkid)) {
							$linkid = $get['linkid'];
						}
						// delete each ID
						if ($id) {
							// check OwnerID if no permissions
							if ($GLOBALS['app']->Session->GetPermission('Properties', 'ManageProperties') && $account === false) {
								$result = $adminModel->DeleteAmenityType($id);
							} else {
								$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
								if ($linkid) {
									$parent = $model->GetAmenityType($linkid);
									if ($OwnerID == $parent['ownerid']) {
										$result = $adminModel->DeleteAmenityType($id);
									} else {
										$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_AMENITYTYPE_NOT_DELETED'), RESPONSE_ERROR);
										//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
									}
								} else {
									$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_AMENITYTYPE_NOT_DELETED'), RESPONSE_ERROR);
									//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
								}
							}
						}						
						if (!Jaws_Error::IsError($result)) {
							$editamenitytype = true;
						} else {
							$editamenitytype = false;
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
			$redirect_to = $request->get('redirect_to', 'post');
			if (!empty($redirect_to)) {
				if ($editproperty === false || $editpost === false || $editamenity === false || $editamenitytype === false) {
					if (strpos($redirect_to, 'step=2') !== false) {
						$redirect_to = str_replace('step=2', '', $redirect_to);
					} else if (strpos($redirect_to, 'step=3') !== false) {
						$redirect_to = str_replace('step=3', 'step=2', $redirect_to);
					} else if (strpos($redirect_to, 'step=4') !== false) {
						$redirect_to = str_replace('step=4', 'step=3', $redirect_to);
					} else if (strpos($redirect_to, 'step=5') !== false) {
						$redirect_to = str_replace('step=5', 'step=4', $redirect_to);
					} else if (strpos($redirect_to, 'step=6') !== false) {
						$redirect_to = str_replace('step=6', 'step=5', $redirect_to);
					} else if (strpos($redirect_to, 'step=7') !== false) {
						$redirect_to = str_replace('step=7', 'step=6', $redirect_to);
					} else if (strpos($redirect_to, 'step=8') !== false) {
						$redirect_to = str_replace('step=8', 'step=7', $redirect_to);
					}
				}
				Jaws_Header::Location($redirect_to.$redirect_response);
			}
			
			if ($account === false) {
				if ($editpropertyparent === true) {
					if (count($params) > 0) {
						return (is_numeric($result) ? $result : (isset($postData['propertyparentID']) && !empty($postData['propertyparentID']) ? (int)$postData['propertyparentID'] : false));
					} else if ($fuseaction == 'DeletePropertyParent') {
						$redirect = BASE_SCRIPT . '?gadget=Properties&action=Admin';
					} else if (is_numeric($result)) {
						$redirect = BASE_SCRIPT . '?gadget=Properties&action=A&id='.$result;
					} else if (isset($postData['propertyparentID'])) {
						if (strpos($_SERVER['HTTP_REFERER'], '&edit=true') !== false) {
							$parent = $model->GetPropertyParent((int)$postData['propertyparentID']);
							if (!Jaws_Error::IsError($parent)) {
								$redirect = 'index.php?gadget=Properties&action=Category&id='.$parent['propertyparentfast_url'].'&edit=true';
							} else {
								$redirect = 'admin.php?gadget=Properties&action=A&id='.$postData['propertyparentID'];
							}
						} else {
							$redirect = 'admin.php?gadget=Properties&action=A&id='.$postData['propertyparentID'];
						}
					} else {
						if (strpos($_SERVER['HTTP_REFERER'], '&edit=true') !== false) {
							$redirect = 'index.php?gadget=Properties&action=Category&id='.$get['id'].'&edit=true';
						} else {
							$redirect = 'admin.php?gadget=Properties&action=A&id='.$get['id'];
						}
					}
				} else if ($deletepropertyparent === true) {
					$redirect = BASE_SCRIPT . '?gadget=Properties&action=Admin';
				} else if ($editproperty === true) {
					if (count($params) > 0) {
						return (is_numeric($result) ? $result : (isset($postData['ID']) && !empty($postData['ID']) ? (int)$postData['ID'] : false));
					} else {
						$redirect = (isset($get['linkid']) && !empty($get['linkid']) ? BASE_SCRIPT . '?gadget=Properties&action=A&id='.$get['linkid'] : BASE_SCRIPT . '?gadget=Properties&action=A&id=all');
					}
				} else if ($editpost === true) {
					if (count($params) > 0) {
						return (is_numeric($result) ? $result : (isset($postData['ID']) && !empty($postData['ID']) ? (int)$postData['ID'] : false));
					} else if (isset($postData['LinkID'])) {
						$redirect = BASE_SCRIPT . '?gadget=Properties&action=A_form&id='.$postData['LinkID'];
					} else {
						$redirect = BASE_SCRIPT . '?gadget=Properties&action=A&id=all';
					}
				} else if ($editamenitytype === true) {
					if (count($params) > 0) {
						return (is_numeric($result) ? $result : (isset($postData['ID']) && !empty($postData['ID']) ? (int)$postData['ID'] : false));
					} else {
						$redirect = BASE_SCRIPT . '?gadget=Properties&action=B2';
					}
				} else if ($editamenity === true) {
					if (count($params) > 0) {
						return (is_numeric($result) ? $result : (isset($postData['ID']) && !empty($postData['ID']) ? (int)$postData['ID'] : false));
					} else {
						$redirect = BASE_SCRIPT . '?gadget=Properties&action=B';
					}
				} else {
					$redirect = BASE_SCRIPT . '?gadget=Properties';
				}
				if (isset($postData['propertyparentRss_url']) && !empty($postData['propertyparentRss_url'])) {	
					$output_html .= "<script>\n";
					$output_html .= "	setTimeout(function(){window.location.href='".$redirect."';}, 4000);\n";
					$output_html .= "</script>\n";
					$output_html .= "<noscript><div style='color:#FF0000; font-weight: bold;'>Javascript must be enabled in your browser in order to use this service.</div></noscript>\n";
					return $output_html;
				} else {
					Jaws_Header::Location($redirect);
				}
			} else {
				if ($editpropertyparent === true) {
					if (count($params) > 0) {
						return (is_numeric($result) ? $result : (isset($postData['propertyparentID']) && !empty($postData['propertyparentID']) ? (int)$postData['propertyparentID'] : false));
					} else {
						$output_html = "";
						$output_html .= "<script>\n";
						$output_html .= "	parent.parent.location.reload();\n";
						$output_html .= "	//parent.parent.hideGB();\n";
						$output_html .= "</script>\n";
						$output_html .= "<noscript><div style='color:#FF0000; font-weight: bold;'>Javascript must be enabled in your browser in order to use this service.</div></noscript>\n";
						return $output_html;
					}
				} else if ($editpost === true) {
					if (count($params) > 0) {
						return (is_numeric($result) ? $result : (isset($postData['ID']) && !empty($postData['ID']) ? (int)$postData['ID'] : false));
					} else {
						$output_html = "";
						$output_html .= "<script>\n";
						$output_html .= "	parent.parent.location.reload();\n";
						$output_html .= "	//parent.parent.hideGB();\n";
						$output_html .= "</script>\n";
						$output_html .= "<noscript><div style='color:#FF0000; font-weight: bold;'>Javascript must be enabled in your browser in order to use this service.</div></noscript>\n";
						return $output_html;
					}
				} else if ($editproperty === true) {
					if (count($params) > 0) {
						return (is_numeric($result) ? $result : (isset($postData['ID']) && !empty($postData['ID']) ? (int)$postData['ID'] : false));
					} else {
						$output_html = "";
						$output_html .= "<script>\n";
						$output_html .= "	if (window.opener && !window.opener.closed) {\n";
						$output_html .= "		window.opener.location.reload();\n";
						$output_html .= "	}\n";
						$output_html .= "	window.location.href='index.php?user/login.html';\n";
						$output_html .= "</script>\n";
						$output_html .= "<noscript><div style='color:#FF0000; font-weight: bold;'>Javascript must be enabled in your browser in order to use this service.</div></noscript>\n";
						return $output_html;
					}
				} else if ($editamenity === true || $editamenitytype === true) {
					if (count($params) > 0) {
						return (is_numeric($result) ? $result : (isset($postData['ID']) && !empty($postData['ID']) ? (int)$postData['ID'] : false));
					} else {
						$output_html = "";
						$output_html .= "<script>\n";
						$output_html .= "	if (window.opener && !window.opener.closed) {\n";
						$output_html .= "		window.opener.location.reload();\n";
						$output_html .= "	}\n";
						$output_html .= "	window.location.href='index.php?gadget=Properties&action=account_B';\n";
						$output_html .= "</script>\n";
						$output_html .= "<noscript><div style='color:#FF0000; font-weight: bold;'>Javascript must be enabled in your browser in order to use this service.</div></noscript>\n";
						return $output_html;
					}
				}
			}

		} else {
			if ($account === false) {
				Jaws_Header::Location(BASE_SCRIPT . '?gadget=Properties');
			} else {
				Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'));
			}
		}

    }


    /**
     * Display the property list page
     *
     * @access public
     * @return string
     */
    function A($account = false)
    {
		// check session
		if ($account === false) {
			$GLOBALS['app']->Session->CheckPermission('Properties', 'default');
		} else {
			$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
			if (!$GLOBALS['app']->Session->GetPermission('Properties', 'default')) {
				if (!$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Properties', 'OwnProperty')) {
		            $GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
					$userHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
					return $userHTML->DefaultAction();
				}
			}
		}

		// document dependencies
		require_once JAWS_PATH . 'include/Jaws/Header.php';
		$model = $GLOBALS['app']->LoadGadget('Properties', 'Model');
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$request =& Jaws_Request::getInstance();
		$get = $request->get(array('action', 'id'), 'get');
		$action = $get['action'];
		$searchkeyword = $request->get('searchkeyword', 'post');
		$searchstatus = $request->get('searchstatus', 'post');
		$searchownerid = $request->get('searchownerid', 'post');
		if (empty($searchownerid)) {
			$searchownerid = $request->get('searchownerid', 'get');
		}
		
		$error = '';
		$page = '';
		
		$pid = 'all';
		if (!empty($get['id']) && strtolower($get['id']) != 'all') {
			$pid = $get['id'];
		}
		
		// initialize template
		$tpl = new Jaws_Template('gadgets/Properties/templates/');
        $tpl->Load('admin.html');
        if ($account === false) {
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
		}
		$tpl->SetBlock('gadget_property');
        $tpl->SetVariable('confirmPropertyDelete', _t('PROPERTIES_CONFIRM_DELETE_PROPERTY'));
        $tpl->SetVariable('confirmRssHide', _t('PROPERTIES_CONFIRM_RSS_HIDE'));
		
		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=CustomPage&amp;action=account_SetGBRoot');
		// account differences
		if ($account === false) {
			$tpl->SetVariable('workarea-style', "style=\"float: left; margin-top: 30px;\" ");
			$this->AjaxMe('script.js');
			$tpl->SetVariable('menubar', $this->MenuBar($action));
			$submit_vars['ACTIONPREFIX'] = "";
			$submit_vars['CLOSE_BUTTON'] = "location.href='admin.php?gadget=Properties&amp;action=Admin';";
			$GLOBALS['app']->Layout->AddScriptLink('libraries/js/admin.js');
			$OwnerID = null;
			$base_url = 'admin.php';
		} else {
			$tpl->SetVariable('workarea-style', '');
			$this->AjaxMe('client_script.js');
			$tpl->SetVariable('menubar', '');
			$submit_vars['ACTIONPREFIX'] = "account_";
			$submit_vars['CLOSE_BUTTON'] = "if (window.opener && !window.opener.closed) {window.opener.location.reload();window.close();};";
			$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
			$base_url = 'index.php';
		}
		$syntactsUrl = $GLOBALS['app']->getSyntactsAdminHTMLUrl("Properties/admin_Properties_A");
		$GLOBALS['app']->Layout->AddHeadLink('libraries/piwi/piwidata/css/default.css', 'stylesheet', 'text/css', 'default');
		$GLOBALS['app']->Layout->AddScriptLink('libraries/greybox/AJS.js');
        $GLOBALS['app']->Layout->AddScriptLink('libraries/greybox/AJS_fx.js');
        $GLOBALS['app']->Layout->AddScriptLink('libraries/greybox/gb_scripts.js');
        $GLOBALS['app']->Layout->AddHeadLink('libraries/greybox/gb_styles.css', 'stylesheet', 'text/css');
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
			$snoopy = new Snoopy('Properties');
			$submit_url = $syntactsUrl;
			
			if($snoopy->fetch($submit_url)) {
				//while(list($key,$val) = each($snoopy->headers))
					//echo $key.": ".$val."<br>\n";
				//echo "<p>\n";
				
				//$page = "<PRE>".htmlspecialchars($snoopy->results)."</PRE>\n";
				$form_content = '';
				
				// initialize template
				$stpl = new Jaws_Template();
				$stpl->LoadFromString($snoopy->results);
				$stpl->SetBlock('view');
			
				$mapHTML = '';
				$view_content = '';
			
				$pageInfo = array();
				if ($pid != 'all') {
					// send Page records
					$pageInfo = $model->GetPropertyParent($pid);
				}
				$numeric_OwnerID = ($OwnerID === null ? 0 : (int)$OwnerID);
				if ((!Jaws_Error::IsError($pageInfo) && ($GLOBALS['app']->Session->GetPermission('Properties', 'ManageProperties') || $pageInfo['propertyparentownerid'] == $numeric_OwnerID)) || $pid == 'all') {
					if (isset($pageInfo['propertyparentid'])) {
						$pid = $pageInfo['propertyparentid'];
					}
					/*
					if ($account === false && $GLOBALS['app']->Registry->Get('/gadgets/Properties/showmap') == 'Y') {
						$propertiesLayout = $GLOBALS['app']->LoadGadget('Properties', 'LayoutHTML');
						$mapHTML = $propertiesLayout->CategoryMap($pid);
					} else {
					*/
						$mapHTML = '';
					//}
					$stpl->SetVariable('CATEGORY_MAP', $mapHTML);
					$stpl->SetVariable('id', $pid);
					$submit_vars['ADDPROPERTY_BUTTON'] = "";
					$submit_vars['DELETE_BUTTON'] = "";
					$submit_vars['EDIT_BUTTON'] = "";
					$category_select = '';
					if ($account === false) {
						$parents = $model->GetPropertyParents();
						$category_select .= "<select name=\"searchcategory\" id=\"searchcategory\" size=\"1\" onChange=\"location.href = 'admin.php?gadget=Properties&action=A&id='+this.value;\">\n";
						$category_select .= "<option value=\"all\"".($pid == 'all' ? ' SELECTED' : '').">All Properties</option>\n";
						// send possible Parent records as options
						if (!Jaws_Error::IsError($parents)) {
							foreach($parents as $parent) {		            
								$category_select .= "<option value=\"".$parent['propertyparentid']."\"".($pid == $parent['propertyparentid'] ? ' SELECTED' : '').">".$parent['propertyparentcategory_name']."</option>\n";
							}
						}
						$category_select .= "</select>\n";
						$stpl->SetVariable('search_action', 'admin.php?gadget=Properties&action=A&id='.$pid);
						//$preview_link = "[<a href=\"". $GLOBALS['app']->Map->GetURLFor('Properties', 'Category', array('id' => $pid)) ."\" target=\"_blank\">Preview Category</a>]";
						if ($pid != 'all') {
							$submit_vars['DELETE_BUTTON'] = "&nbsp;<input type=\"button\" name=\"Delete\" onclick=\"if (confirm('Do you want to delete this category? This will delete all properties, availability calendars and import schedules as well.')) { location.href = 'admin.php?gadget=Properties&amp;action=form_post&fuseaction=DeletePropertyParent&amp;id=".$pid."'; };\" value=\"Delete\">";
							$submit_vars['EDIT_BUTTON'] = "<input type=\"button\" name=\"Edit\" onclick=\"document.getElementById('form_content').style.display = 'block'; document.getElementById('view_content').style.display = 'none';\" value=\"Edit\">";
						}
						$submit_vars['ADDPROPERTY_BUTTON'] = "&nbsp;<input type=\"button\" name=\"Add Property\" onclick=\"location.href = 'admin.php?gadget=Properties&amp;action=A_form&amp;linkid=".($pid != 'all' ? $pid : '')."';\" value=\"Add Property\">";
						$submit_vars['IMPORT_BUTTON'] = '';
					} else {
						// Get Catgories of this OwnerID
						$ownerCat_id = '';
						$ownerCategories = $model->GetPropertyParentsByUserID($OwnerID);
						if (!Jaws_Error::IsError($ownerCategories)) {
							foreach($ownerCategories as $ownerCat) {
								if (isset($ownerCat['propertyparentid']) && !empty($ownerCat['propertyparentid'])) {
									$ownerCat_id = $ownerCat['propertyparentid'];
									break;
								}
							}
						}
						if (!empty($ownerCat_id)) {
							$submit_vars['DELETE_BUTTON'] = "&nbsp;<input type=\"button\" style=\"width: 130px;\" name=\"Delete\" onclick=\"if (confirm('Do you want to delete your imported properties? This will delete all properties, availability calendars and import schedules as well.')) { location.href = 'index.php?gadget=Properties&amp;action=account_form_post&fuseaction=DeletePropertyParent&amp;id=".$ownerCat_id."'; };\" value=\"Delete\">";
						}
						$submit_vars['IMPORT_BUTTON'] = "&nbsp;<input type=\"button\" style=\"width: 130px;\" name=\"Import Properties\" onclick=\"showPostWindow('" . $GLOBALS['app']->GetSiteURL() . "/index.php?gadget=Properties&amp;action=account_form&amp;id=".$ownerCat_id."', 'Import Properties');\" value=\"Import Properties\">";
						$stpl->SetVariable('search_action', '');
					}
					$preview_link = '';
					
					$search_form = '';
					// Search status
					$statusCombo =& Piwi::CreateWidget('Combo', 'searchstatus');
					$statusCombo->AddOption('Show All', '');
					$statusCombo->AddOption(_t('PROPERTIES_STATUS_FORSALE'), 'forsale');
					$statusCombo->AddOption(_t('PROPERTIES_STATUS_FORRENT'), 'forrent');
					$statusCombo->AddOption(_t('PROPERTIES_STATUS_FORLEASE'), 'forlease');
					$statusCombo->AddOption(_t('PROPERTIES_STATUS_UNDERCONTRACT'), 'undercontract');
					$statusCombo->AddOption(_t('PROPERTIES_STATUS_SOLD'), 'sold');
					$statusCombo->AddOption(_t('PROPERTIES_STATUS_RENTED'), 'rented');
					$statusCombo->AddOption(_t('PROPERTIES_STATUS_LEASED'), 'leased');
					$statusCombo->SetDefault($searchstatus);
					$statusCombo->setTitle(_t('PROPERTIES_ACTIVE'));
					$statusCombo->SetStyle('width: 100px;');
					$search_form .= "<td id=\"filter_options\" width=\"0%\" valign=\"top\" style=\"min-width: 750px;\"><nobr>".$statusCombo->Get()."&nbsp;&nbsp;";

					// Search keyword
					$searchEntry =& Piwi::CreateWidget('Entry', 'searchkeyword', $searchkeyword);
					$searchEntry->SetTitle(_t('PROPERTIES_SEARCH'));
					$searchEntry->SetStyle('direction: ltr; width: 120px;');
					
					// Search submit
					$submit =& Piwi::CreateWidget('Button', 'search', _t('GLOBAL_SEARCH'), STOCK_SEARCH);
					$submit->SetSubmit();
					
					// Owner ID
					$owner_select = '';
					if ($account === false) {
						require_once JAWS_PATH . 'include/Jaws/User.php';
						$jUser = new Jaws_User;
						$users = $jUser->GetUsers();
						$owner_select .= "<select name=\"searchownerid\" id=\"searchownerid\" size=\"1\" onChange=\"location.href = 'admin.php?gadget=Store&action=A&id='+$('searchcategory').value+'&searchbrand='+$('searchbrand').value+'&searchownerid='+this.value;\">\n";
						$owner_select .= "<option value=\"\"".(empty($searchownerid) ? ' SELECTED' : '').">All Users</option>\n";
						if (!Jaws_Error::IsError($users)) {
							foreach($users as $u) {		            
								$owner_select .= "<option value=\"".$u['id']."\"".((int)$searchownerid == $u['id'] ? ' SELECTED' : '').">".$u['username']."</option>\n";
							}
						}
						$owner_select .= "</select>\n";
					}
					$search_form .= $searchEntry->Get()."&nbsp;&nbsp;".$category_select."&nbsp;&nbsp;".$owner_select."&nbsp;&nbsp;".$submit->Get()."&nbsp;&nbsp;".$preview_link."</nobr></td>";

					$stpl->SetVariable('search_form', $search_form);
					
					// send requesting URL to syntacts
					$stpl->SetVariable('HTTP_REFERER', $GLOBALS['app']->GetSiteURL());
					$stpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . '/');
					$stpl->SetVariable('DPATH', JAWS_DPATH);
					$stpl->SetVariable('actionprefix', $submit_vars['ACTIONPREFIX']);
					$stpl->SetVariable('CLOSE_BUTTON', $submit_vars['CLOSE_BUTTON']);
					$stpl->SetVariable('DELETE_BUTTON', $submit_vars['DELETE_BUTTON']);
					$stpl->SetVariable('EDIT_BUTTON', $submit_vars['EDIT_BUTTON']);
					$stpl->SetVariable('ADDPROPERTY_BUTTON', $submit_vars['ADDPROPERTY_BUTTON']);
					$stpl->SetVariable('IMPORT_BUTTON', $submit_vars['IMPORT_BUTTON']);
					$stpl->SetVariable('gadget', 'Properties');
					$stpl->SetVariable('controller', $base_url);
					
					$embed_options = '';
					// send embedding options
					if ($pid != 'all') {
						$embed_options .= "<a href=\"javascript:void(0);\" onclick=\"showEmbedWindow('".$GLOBALS['app']->GetSiteURL()."/".$base_url."?gadget=Properties&amp;action=".$submit_vars['ACTIONPREFIX']."ShowEmbedWindow&amp;linkid=".$pid."&amp;uid=".$OwnerID."&amp;mode=full', 'Embed This Category');\">Property List of this Category</a>&nbsp;&nbsp;&nbsp;\n";
						$embed_options .= "<a href=\"javascript:void(0);\" onclick=\"showEmbedWindow('".$GLOBALS['app']->GetSiteURL()."/".$base_url."?gadget=Properties&amp;action=".$submit_vars['ACTIONPREFIX']."ShowEmbedWindow&amp;linkid=".$pid."&amp;uid=".$OwnerID."&amp;mode=slideshow', 'Embed Slideshow');\">Property Slideshow of this Category</a>&nbsp;&nbsp;&nbsp;\n";
						$embed_options .= "<a href=\"javascript:void(0);\" onclick=\"showEmbedWindow('".$GLOBALS['app']->GetSiteURL()."/".$base_url."?gadget=Properties&amp;action=".$submit_vars['ACTIONPREFIX']."ShowEmbedWindow&amp;linkid=".$pid."&amp;uid=".$OwnerID."&amp;mode=categorymap', 'Embed Category Map');\">Map of Properties in this Category (12 maximum)</a>&nbsp;&nbsp;&nbsp;\n";
						$embed_options .= "<a href=\"javascript:void(0);\" onclick=\"showEmbedWindow('".$GLOBALS['app']->GetSiteURL()."/".$base_url."?gadget=Properties&amp;action=".$submit_vars['ACTIONPREFIX']."ShowEmbedWindow&amp;linkid=".$pid."&amp;uid=".$OwnerID."&amp;mode=citiesmap', 'Embed Cities Map');\">Map of cities that contain Properties in this Category</a>&nbsp;&nbsp;&nbsp;\n";
						$embed_options .= "<a href=\"javascript:void(0);\" onclick=\"showEmbedWindow('".$GLOBALS['app']->GetSiteURL()."/".$base_url."?gadget=Properties&amp;action=".$submit_vars['ACTIONPREFIX']."ShowEmbedWindow&amp;linkid=".$pid."&amp;uid=".$OwnerID."&amp;mode=search', 'Embed Property Search');\">Search Properties in this Category</a>&nbsp;&nbsp;&nbsp;\n";
						$embed_options .= "<a href=\"javascript:void(0);\" onclick=\"showEmbedWindow('".$GLOBALS['app']->GetSiteURL()."/".$base_url."?gadget=Properties&amp;action=".$submit_vars['ACTIONPREFIX']."ShowEmbedWindow&amp;linkid=".$pid."&amp;uid=".$OwnerID."&amp;mode=reservation', 'Embed Reservation Form');\">Reservation Form for Rentals in this Category</a>&nbsp;&nbsp;&nbsp;\n";
						$embed_options .= "<a href=\"javascript:void(0);\" onclick=\"showEmbedWindow('".$GLOBALS['app']->GetSiteURL()."/".$base_url."?gadget=Properties&amp;action=".$submit_vars['ACTIONPREFIX']."ShowEmbedWindow&amp;linkid=".$pid."&amp;uid=".$OwnerID."&amp;mode=calendar', 'Embed Property Calendar');\">Availability Calendar of Rentals in this Category</a>&nbsp;&nbsp;&nbsp;\n";
					}
					$embed_options .= "<a href=\"javascript:void(0);\" onclick=\"showEmbedWindow('".$GLOBALS['app']->GetSiteURL()."/".$base_url."?gadget=Properties&amp;action=".$submit_vars['ACTIONPREFIX']."ShowEmbedWindow&amp;linkid=all&amp;uid=".$OwnerID."&amp;mode=full', 'Embed All Properties');\">All Properties</a>&nbsp;&nbsp;&nbsp;\n";
					$embed_options .= "<a href=\"javascript:void(0);\" onclick=\"showEmbedWindow('".$GLOBALS['app']->GetSiteURL()."/".$base_url."?gadget=Properties&amp;action=".$submit_vars['ACTIONPREFIX']."ShowEmbedWindow&amp;linkid=all&amp;uid=".$OwnerID."&amp;mode=slideshow', 'Embed Slideshow');\">Slideshow of All Properties</a>&nbsp;&nbsp;&nbsp;\n";
					$embed_options .= "<a href=\"javascript:void(0);\" onclick=\"showEmbedWindow('".$GLOBALS['app']->GetSiteURL()."/".$base_url."?gadget=Properties&amp;action=".$submit_vars['ACTIONPREFIX']."ShowEmbedWindow&amp;linkid=all&amp;uid=".$OwnerID."&amp;mode=categorymap', 'Embed Property Map');\">Map of All Properties (12 maximum)</a>&nbsp;&nbsp;&nbsp;\n";
					$embed_options .= "<a href=\"javascript:void(0);\" onclick=\"showEmbedWindow('".$GLOBALS['app']->GetSiteURL()."/".$base_url."?gadget=Properties&amp;action=".$submit_vars['ACTIONPREFIX']."ShowEmbedWindow&amp;linkid=all&amp;uid=".$OwnerID."&amp;mode=citiesmap', 'Embed Cities Map');\">Map of cities that contain Properties</a>&nbsp;&nbsp;&nbsp;\n";
					$embed_options .= "<a href=\"javascript:void(0);\" onclick=\"showEmbedWindow('".$GLOBALS['app']->GetSiteURL()."/".$base_url."?gadget=Properties&amp;action=".$submit_vars['ACTIONPREFIX']."ShowEmbedWindow&amp;linkid=all&amp;uid=".$OwnerID."&amp;mode=globalmap', 'Embed Global Map');\">Map of global Regions</a>&nbsp;&nbsp;&nbsp;\n";
					$embed_options .= "<a href=\"javascript:void(0);\" onclick=\"showEmbedWindow('".$GLOBALS['app']->GetSiteURL()."/".$base_url."?gadget=Properties&amp;action=".$submit_vars['ACTIONPREFIX']."ShowEmbedWindow&amp;linkid=all&amp;uid=".$OwnerID."&amp;mode=search', 'Embed Property Search');\">Search All Properties</a>&nbsp;&nbsp;&nbsp;\n";
					$embed_options .= "<a href=\"javascript:void(0);\" onclick=\"showEmbedWindow('".$GLOBALS['app']->GetSiteURL()."/".$base_url."?gadget=Properties&amp;action=".$submit_vars['ACTIONPREFIX']."ShowEmbedWindow&amp;linkid=all&amp;uid=".$OwnerID."&amp;mode=reservation', 'Embed Reservation Form');\">Reservation Form for All Rentals</a>&nbsp;&nbsp;&nbsp;\n";
					$embed_options .= "<a href=\"javascript:void(0);\" onclick=\"showEmbedWindow('".$GLOBALS['app']->GetSiteURL()."/".$base_url."?gadget=Properties&amp;action=".$submit_vars['ACTIONPREFIX']."ShowEmbedWindow&amp;linkid=all&amp;uid=".$OwnerID."&amp;mode=calendar', 'Embed Property Calendar');\">Availability Calendar of All Rentals</a>&nbsp;&nbsp;&nbsp;\n";
					$embed_options .= "<a href=\"javascript:void(0);\" onclick=\"showEmbedWindow('".$GLOBALS['app']->GetSiteURL()."/".$base_url."?gadget=Properties&amp;action=".$submit_vars['ACTIONPREFIX']."ShowEmbedWindow&amp;linkid=all&amp;uid=".$OwnerID."&amp;mode=list', 'Embed Category Index');\">List of All Categories</a>&nbsp;&nbsp;&nbsp;\n";
					$stpl->SetVariable('embed_options', $embed_options);
					
					// send Post records
					$propertiesHTML = '';
					$adminmodel = $GLOBALS['app']->LoadGadget('Properties', 'AdminModel');
					if (!empty($searchownerid)) {
						$posts = $model->GetPropertiesOfUserID((int)$searchownerid);
					} else if ((!empty($searchkeyword) || !empty($searchstatus)) && $pid != 'all') {
						$posts = $adminmodel->MultipleSearchProperties($searchstatus, $searchkeyword, '', '', '', '', '', null, $OwnerID, $pid);
					} else if ($pid == 'all') {
						$posts = $adminmodel->MultipleSearchProperties($searchstatus, $searchkeyword, '', '', '', '', '', null, $OwnerID);
					} else {
						$posts = $model->GetAllPropertiesOfParent($pid);
					}
			        if (!Jaws_Error::IsError($posts)) {
						$i = 0;
						foreach($posts as $post) {		            
							if (isset($post['id'])) {
								$background = '';
								if ($i == 0) {
									$background = "background: #EDF3FE; border-top: dotted 1pt #E2E2E2; ";
								} else if (($i % 2) == 0) {
									$background = "background: #EDF3FE; ";
								}
								$propertiesHTML .= "<tr id=\"syntactsCategory_".$post['id']."\">\n";
								$propertiesHTML .= "	<td style=\"".$background."padding:3px;\" class=\"syntacts-form-row\">".$post['status']."</td>\n";
								$propertiesHTML .= "	<td style=\"".$background."padding:3px;\" class=\"syntacts-form-row\"><b>".$post['id']."</b><br />";
								if (!empty($post['internal_propertyno']) && ($post['internal_propertyno'] <> 0)) {
									$propertiesHTML .= "<nobr>Internal: ".$post['internal_propertyno']."</nobr><br />";
								}
								if (!empty($post['lotno'])) {
									$propertiesHTML .= "<nobr>Lot: ".$post['lotno']."</nobr><br />";
								}
								if (!empty($post['mls'])) {
									$propertiesHTML .= "<nobr>MLS: ".$post['mls']."</nobr><br />";
								}
								$propertiesHTML .= "	</td>\n";
								$propertiesHTML .= "	<td style=\"".$background."padding:3px;\" class=\"syntacts-form-row\"><nobr>";
								// Does it have an image?
								$posts = $model->GetAllPostsOfProperty($post['id']);
								if (!Jaws_Error::IsError($posts)) {
									if (!empty($post['image']) || !count($posts) <= 0) {
										$propertiesHTML .= '<img title="Property has images" src="'.STOCK_IMAGE.'" border="0" />&nbsp;';
									}
								}
								// Does it have a description?
								if (!empty($post['description'])) {
									$propertiesHTML .= '<img title="Property has a description" src="'.STOCK_ALIGN_CENTER.'" border="0" />&nbsp;';
								}
								$propertiesHTML .= "<a href=\"javascript:void(0);\" ".(!empty($post['title']) ? 'title="'.$post['title'].'" ' : '')."onclick=\"";
								if ($account === true) {
									$propertiesHTML .= "window.open('index.php?gadget=Properties&amp;action=account_A_form&amp;linkid=".($pid != 'all' ? $pid : '')."&amp;id=".$post['id']."');";
								} else { 
									$propertiesHTML .= "location.href='admin.php?gadget=Properties&amp;action=A_form&amp;linkid=".($pid != 'all' ? $pid : '')."&amp;id=".$post['id']."';";
								}
								$propertiesHTML .= "\"><b>".(!empty($post['title']) ? (strlen($post['title']) > 25 ? substr($post['title'], 0, 25).'...' : $post['title']) : '')."</b></a></nobr>&nbsp;&nbsp;";
								if (!empty($post['fast_url'])) {
									$propertiesHTML .= "[<a href=\"index.php?property/".$post['fast_url'].".html\" target=\"_blank\">Preview</a>]";
								}
								$propertiesHTML .= "</td>\n";
								$propertiesHTML .= "	<td style=\"".$background."padding:3px;\" class=\"syntacts-form-row\">";
								if (!empty($post['category'])) {
									$propCategories = explode(',', $post['category']);
									$catCount = 0;
									foreach($propCategories as $propCategory) {		            
										$parent = $model->GetPropertyParent((int)$propCategory);
										if (!Jaws_Error::IsError($parent) && isset($parent['propertyparentid'])) {
											if ($catCount > 0) {
												$propertiesHTML .= ", ";
											}
											if ($account === true) {
												$propertiesHTML .= $parent['propertyparentcategory_name'];
											} else { 
												$propertiesHTML .= "<nobr><a href=\"admin.php?gadget=Properties&action=A&id=".$parent['propertyparentid']."\">".$parent['propertyparentcategory_name']."</a></nobr>";
											}
											$catCount++;
										}
									}
								}
								$propertiesHTML .= "</td>\n";
								$propertiesHTML .= "	<td style=\"".$background."text-align: center; padding:3px;\" class=\"syntacts-form-row\"><nobr>";
								if (!empty($post['price']) && ($post['price'] > 0)) {
									$propertiesHTML .=  '$'.number_format($post['price'], 2, '.', ',');
								} else if (!empty($post['rentdy']) || !empty($post['rentwk']) || !empty($post['rentmo'])) {
									if (!empty($post['rentdy']) && ($post['rentdy'] > 0)) {
										$propertiesHTML .= "Nightly From: $".number_format($post['rentdy'], 2, '.', ',')."<br />";
									}
									if (!empty($post['rentwk']) && ($post['rentwk'] > 0)) {
										$propertiesHTML .= "Weekly From: $".number_format($post['rentwk'], 2, '.', ',')."<br />";
									}
									if (!empty($post['rentmo']) && ($post['rentmo'] > 0)) {
										$propertiesHTML .= "Monthly From: $".number_format($post['rentmo'], 2, '.', ',')."<br />";
									}
								}
								$propertiesHTML .= "</nobr></td>\n";
								$propertiesHTML .= "	<td style=\"".$background."text-align: center; padding:3px;\" class=\"syntacts-form-row\"><nobr>";
								$propertiesHTML .= "<a href=\"javascript:void(0);\" onclick=\"";
								if ($account === true) {
									$propertiesHTML .= "window.open('index.php?gadget=Properties&amp;action=account_A_form&amp;linkid=".($pid != 'all' ? $pid : '')."&amp;id=".$post['id']."');";
								} else { 
									$propertiesHTML .= "location.href='admin.php?gadget=Properties&amp;action=A_form&amp;linkid=".($pid != 'all' ? $pid : '')."&amp;id=".$post['id']."';";
								}
								$propertiesHTML .= "\">EDIT</a>";
								if ($post['calendar_link'] == '' && ($post['status'] == 'forrent' || $post['status'] == 'rented' || $post['status'] == 'forlease' || $post['status'] == 'leased')) {
									$hook = $GLOBALS['app']->loadHook('Properties', 'Calendar');
									if ($hook !== false) {
										if (method_exists($hook, 'GetAllCalendars')) {
											$calendars = $hook->GetAllCalendars(
												array(
													'gadget_reference' => $post['id']
												)
											);
											if (!Jaws_Error::isError($calendars)) {
												foreach ($calendars as $calendar) {
													if (isset($calendar['calendarparentid'])) {
														$propertiesHTML .= "&nbsp;&nbsp;<a href=\"";
														if ($account === true) {
															$propertiesHTML .= "index.php?gadget=Calendar&amp;action=account_A&amp;linkid=".$calendar['calendarparentid']."\" target=\"_blank";
														} else { 
															$propertiesHTML .= "admin.php?gadget=Calendar&amp;action=A&amp;linkid=".$calendar['calendarparentid'];
														}
														$propertiesHTML .= "\">CALENDAR</a>";
														break;
													}
												}
											}
										}
									}
								}
								$propertiesHTML .= "&nbsp;&nbsp;<noscript><INPUT TYPE=\"checkbox\" NAME=\"ID\" VALUE=\"".$post['id']."\"></noscript><script>document.write('<a href=\"javascript:void(0);\" onClick=\"deleteProperty(".$post['id'].");\" title=\"Delete this Property\">DELETE</a>');</script>";
								$propertiesHTML .= "</nobr></td>\n";
								$propertiesHTML .= "</tr>\n";
								$i++;
							}
						}
						if ($propertiesHTML == '') {
							//$propertiesHTML .= "<style>#syntactsCategories_head {display: none;}</style>\n";
							$propertiesHTML .= "<tr id=\"syntactsCategories_no_items\" noDrop=\"true\" noDrag=\"true\"><td colspan=\"100%\" style=\"text-align:left\"><i>No properties ";
							if (!empty($searchstatus)) {
								$propertiesHTML .= "that match the status of  <b>\"".$searchstatus."\"</b> "; 
								if (!empty($searchkeyword)) {
									$propertiesHTML .= "AND ";
								}
							}
							if (!empty($searchkeyword)) {
								$propertiesHTML .= "that match the keyword  <b>\"".$searchkeyword."\"</b> ";
							} 
							$propertiesHTML .= "have been added to this category yet.</i></td></tr>\n";
							//$propertiesHTML .= "<style>#syntactsCategories {display: none;}</style>\n";
						}
					} else {
						$propertiesHTML .= _t('PROPERTIES_ERROR_PROPERTIES_NOT_RETRIEVED')."\n";
					}
					$stpl->SetVariable('properties_html', $propertiesHTML);
					
					// Drag and drop sorting
					$drag_drop = '';
					if ($pid != 'all') {
						$drag_drop = "<script type=\"text/javascript\">Event.observe(window, \"load\",function(){var table = document.getElementById('syntactsCategories');var tableDnD = new PropertiesTableDnD();tableDnD.init(table);});</script>\n";			
					}
					$stpl->SetVariable('drag_drop', $drag_drop);
						
					$stpl->ParseBlock('view');
					$page .= $stpl->Get();

					// syntacts page for form
					if ($account === false && $pid != 'all') {
						$syntactsUrl = $GLOBALS['app']->getSyntactsAdminHTMLUrl('Properties/admin_Properties_form');
						$submit_vars['CLOSE_BUTTON'] = "document.getElementById('form_content').style.display = 'none'; document.getElementById('view_content').style.display = '';";

						// syntacts page
						if ($syntactsUrl) {
							// snoopy
							include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
							$snoopy = new Snoopy('Properties');
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
								// send page records
								$pageInfo = $model->GetPropertyParent($pid);
								if (!Jaws_Error::IsError($pageInfo) && ($GLOBALS['app']->Session->GetPermission('Properties', 'ManageProperties') || $pageInfo['propertyparentownerid'] == $numeric_OwnerID)) {
									$submit_vars['SUBMIT_BUTTON'] = "<input type='Submit' name='Submit' value='Update'>";
								} else {
									//$error = _t('PROPERTIES_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403'));												
									return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTYPARENT_NOT_FOUND'), _t('PROPERTIES_NAME'));
								}

								// send requesting URL to syntacts
								$stpl->SetVariable('HTTP_REFERER', $GLOBALS['app']->GetSiteURL());
								$stpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . '/');
								//$stpl->SetVariable('DPATH', JAWS_DPATH);
								$stpl->SetVariable('actionprefix', $submit_vars['ACTIONPREFIX']);
								$stpl->SetVariable('gadget', 'Properties');
								$stpl->SetVariable('CLOSE_BUTTON', $submit_vars['CLOSE_BUTTON']);
								$stpl->SetVariable('SUBMIT_BUTTON', $submit_vars['SUBMIT_BUTTON']);
								$stpl->SetVariable('controller', $base_url);
								
								// Get Help documentation
								$help_url = $GLOBALS['app']->getSyntactsAdminHTMLUrl("Properties/admin_Properties_form_help", 'txt');
								$snoopy = new Snoopy('Properties');
						
								if($snoopy->fetch($help_url)) {
									$helpContent = Jaws_Utils::split2D($snoopy->results);
								}
												
								// Hidden elements
								$ID = (isset($pageInfo['propertyparentid'])) ? $pageInfo['propertyparentid'] : '';
								$idHidden =& Piwi::CreateWidget('HiddenEntry', 'propertyparentID', $ID);
								$form_content .= $idHidden->Get()."\n";

								$sort_order = (isset($pageInfo['propertyparentsort_order'])) ? $pageInfo['propertyparentsort_order'] : '0';
								$sort_orderHidden =& Piwi::CreateWidget('HiddenEntry', 'propertyparentsort_order', $sort_order);
								$form_content .= $sort_orderHidden->Get()."\n";

								$fuseaction = (isset($pageInfo['propertyparentid'])) ? 'EditPropertyParent' : 'AddPropertyParent';
								$fuseactionHidden =& Piwi::CreateWidget('HiddenEntry', 'fuseaction', $fuseaction);
								$form_content .= $fuseactionHidden->Get()."\n";

								$featured = (isset($pageInfo['propertyparentfeatured'])) ? $pageInfo['propertyparentfeatured'] : 'N';
								$featuredHidden =& Piwi::CreateWidget('HiddenEntry', 'propertyparentFeatured', $featured);
								$form_content .= $featuredHidden->Get()."\n";
								
								if ($account === false) {
									// Active
									$helpString = '';
									foreach($helpContent as $help) {		            
										if ($help[0] == _t('PROPERTIES_PUBLISHED')) {
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
									$active = (isset($pageInfo['propertyparentactive'])) ? $pageInfo['propertyparentactive'] : 'Y';
									$activeCombo =& Piwi::CreateWidget('Combo', 'propertyparentActive');
									$activeCombo->AddOption(_t('PROPERTIES_PUBLISHED'), 'Y');
									$activeCombo->AddOption(_t('PROPERTIES_DRAFT'), 'N');
									$activeCombo->SetDefault($active);
									$activeCombo->setTitle(_t('PROPERTIES_PUBLISHED'));
									$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"propertyparentActive\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$activeCombo->Get()."</td></tr>";
								} else {
									$activeHidden =& Piwi::CreateWidget('HiddenEntry', 'propertyparentActive', 'N');
									$form_content .= $activeHidden->Get()."\n";
								}
									
								// Randomize
								$helpString = '';
								foreach($helpContent as $help) {		            
									if ($help[0] == _t('PROPERTIES_RANDOMIZE')) {
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
								$randomize = (isset($pageInfo['propertyparentrandomize'])) ? $pageInfo['propertyparentrandomize'] : 'Y';
								$randomizeCombo =& Piwi::CreateWidget('Combo', 'propertyparentrandomize');
								$randomizeCombo->AddOption(_t('GLOBAL_YES'), 'Y');
								$randomizeCombo->AddOption(_t('GLOBAL_NO'), 'N');
								$randomizeCombo->SetDefault($randomize);
								$randomizeCombo->setTitle(_t('PROPERTIES_RANDOMIZE'));
								$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"propertyparentrandomize\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$randomizeCombo->Get()."</td></tr>";
								
								// Parent
								$helpString = '';
								foreach($helpContent as $help) {		            
									if ($help[0] == _t('PROPERTIES_PARENT')) {
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
								$post_url = (isset($pageInfo['propertyparentparent']) && !strpos($pageInfo['propertyparentparent'], "://")) ? $pageInfo['propertyparentparent'] : '';
								$urlListCombo =& Piwi::CreateWidget('Combo', 'propertyparentParent');
								$urlListCombo->setID('propertyparentParent');

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
										if ($m['id'] != $pageInfo['id']) {
											if ($m['visible'] == 0) {
												$urlListCombo->AddOption("<i>".$m['menu_type']." : ".$m['title']."</i>", $m['id']);
											} else {
												$urlListCombo->AddOption($m['menu_type']." : ".$m['title'], $m['id']);
											}
										}
									}
								}
								$urlListCombo->setDefault($post_url);
								$urlListCombo->setTitle(_t('PROPERTIES_PARENT'));
								$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"pid\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$urlListCombo->Get()."</td></tr>";

								// Title
								$helpString = '';
								foreach($helpContent as $help) {		            
									if ($help[0] == _t('PROPERTIES_TITLE')) {
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
								$title = (isset($pageInfo['propertyparentcategory_name'])) ? $pageInfo['propertyparentcategory_name'] : '';
								$titleEntry =& Piwi::CreateWidget('Entry', 'propertyparentCategory_Name', $title);
								$titleEntry->SetTitle(_t('PROPERTIES_TITLE'));
								$titleEntry->SetStyle('direction: ltr; width: 300px;');
								$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"propertyparentCategory_Name\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$titleEntry->Get()."</td></tr>";

								// Description
								$helpString = '';
								foreach($helpContent as $help) {		            
									if ($help[0] == _t('PROPERTIES_DESCRIPTIONFIELD')) {
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
								$content = (isset($pageInfo['propertyparentdescription'])) ? $pageInfo['propertyparentdescription'] : '';
								$editor =& $GLOBALS['app']->LoadEditor('Properties', 'propertyparentDescription', $content, false);
								$editor->TextArea->SetStyle('width: 100%;');
								//$editor->SetWidth('100%');
								$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"propertyparentDescription\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$editor->Get()."</td></tr>";

								// Image
								$helpString = '';
								foreach($helpContent as $help) {		            
									if ($help[0] == _t('PROPERTIES_IMAGE')) {
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
								$image = (isset($pageInfo['propertyparentimage'])) ? $pageInfo['propertyparentimage'] : '';
								$image_src = $GLOBALS['app']->getDataURL() . 'files'.$xss->parse($pageInfo['propertyparentimage']);
								$image_preview = '';
								if ($image != '' && file_exists($image_src)) { 
									$image_preview .= "<br /><img border=\"0\" src=\"".$image_src."\" width=\"80\"".(strtolower(substr($image, -3)) == 'gif' || strtolower(substr($image, -3)) == 'png' || strtolower(substr($image, -3)) == 'bmp' ? ' height="80"' : '')." align=\"left\" style=\"padding: 5px;\">";
								}
								$imageScript = "<script type=\"text/javascript\">Event.observe(window, \"load\",function(){addFileToPost('Properties', 'NULL', 'NULL', 'main_image', 'propertyparentImage', 1, 500, 34);});</script>";
								$imageHidden =& Piwi::CreateWidget('HiddenEntry', 'propertyparentImage', $image);
								$imageButton = "&nbsp;&nbsp;&nbsp;&nbsp;<INPUT TYPE=\"button\" VALUE=\"Uploaded Files\" ONCLICK=\"openUploadWindow('propertyparentImage')\" STYLE=\"font-family: Arial; font-size: 10pt; font-weight: bold\">";
								$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"propertyparentImage\"><nobr>".$helpString."</nobr></label>".$image_preview."</td><td class=\"syntacts-form-row\"><div id=\"main_image\" style=\"float: left; width: 500px;\"></div>".$imageScript.$imageHidden->Get().$imageButton."</td></tr>";
								  
								// RSS URL
								$helpString = '';
								foreach($helpContent as $help) {		            
									if ($help[0] == _t('PROPERTIES_RSSURL')) {
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
								$url = (isset($pageInfo['propertyparentrss_url'])) ? $pageInfo['propertyparentrss_url'] : '';
								$urlEntry =& Piwi::CreateWidget('Entry', 'propertyparentRss_url', $url);
								$urlEntry->SetTitle(_t('PROPERTIES_RSSURL'));
								$urlEntry->SetStyle('direction: ltr; width: 300px;');
								$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"propertyparentRss_url\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$urlEntry->Get()."</td></tr>";

								// Override City on Import
								$helpString = '';
								foreach($helpContent as $help) {		            
									if ($help[0] == _t('PROPERTIES_OVERRIDECITY')) {
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
								$keyword = (isset($pageInfo['propertyparentrss_overridecity'])) ? $pageInfo['propertyparentrss_overridecity'] : '';
								$keywordEntry =& Piwi::CreateWidget('Entry', 'propertyparentRss_overridecity', $keyword);
								$keywordEntry->SetTitle(_t('PROPERTIES_OVERRIDECITY'));
								$keywordEntry->SetStyle('direction: ltr; width: 300px;');
								$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"propertyparentRss_overridecity\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$keywordEntry->Get()."</td></tr>";

								if ($error != '') {
									$stpl->SetVariable('content', $error);
								} else {
									$stpl->SetVariable('content', $form_content);
								}
								$stpl->ParseBlock('form');
								$page .= $stpl->Get();
							} else {
								$page .= _t('PROPERTIES_ERROR_ASPPAGE_NOT_RETRIEVED', $snoopy->error)."\n";
							}
						}
					}
				} else {
					// Send us to the appropriate page
					require_once JAWS_PATH . 'include/Jaws/Header.php';
					if ($account == true) {
						Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'));
					} else {
						Jaws_Header::Location($base_url.'?gadget=Properties&action=Admin');
					}
				}
			} else {
				$page .= _t('PROPERTIES_ERROR_ASPPAGE_NOT_RETRIEVED', $snoopy->error)."\n";
			}
		}

		$tpl->SetVariable('content', $page);
        $tpl->ParseBlock('gadget_property');

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
		require_once JAWS_PATH . 'include/Jaws/Image.php';
        
		// check session
		if ($account === false) {
			$GLOBALS['app']->Session->CheckPermission('Properties', 'default');
		} else {
			$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
			if (!$GLOBALS['app']->Session->GetPermission('Properties', 'default')) {
				if (!$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Properties', 'OwnProperty')) {
		            $GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
					$userHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
					return $userHTML->DefaultAction();
				}
			}
		}
		
		//$GLOBALS['app']->Layout->AddScriptLink('libraries/autocomplete/autocomplete.js');
        $GLOBALS['app']->Layout->AddHeadLink('libraries/autocomplete/autocomplete.css', 'stylesheet', 'text/css', 'default');
		//$GLOBALS['app']->Layout->AddScriptLink("http://getfirebug.com/releases/lite/1.2/firebug-lite-compressed.js");

		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$request =& Jaws_Request::getInstance();
		$action = $request->get('action', 'get');
				
		$tpl = new Jaws_Template('gadgets/Properties/templates/');
        $tpl->Load('admin.html');

        // Handle any simple messages
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

        $tpl->SetBlock('gadget_property');

		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=CustomPage&amp;action=account_SetGBRoot');
		if ($account === false) {
			$this->AjaxMe('script.js');
			$tpl->SetVariable('menubar', $this->MenuBar($action));
			$submit_vars['ACTIONPREFIX'] = "";
			$GLOBALS['app']->Layout->AddScriptLink('libraries/js/admin.js');
			$OwnerID = 0;			
			$base_url = BASE_SCRIPT;
		} else {
			$this->AjaxMe('client_script.js');
			$tpl->SetVariable('menubar', '');
			$submit_vars['ACTIONPREFIX'] = "account_";
			$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
			$base_url = 'index.php';
		}
		$syntactsUrl = $GLOBALS['app']->getSyntactsAdminUrl("Properties/admin_Properties_A_form");

		$GLOBALS['app']->Layout->AddScriptLink('libraries/greybox/AJS.js');
        $GLOBALS['app']->Layout->AddScriptLink('libraries/greybox/AJS_fx.js');
        $GLOBALS['app']->Layout->AddScriptLink('libraries/greybox/gb_scripts.js');
        $GLOBALS['app']->Layout->AddHeadLink('libraries/greybox/gb_styles.css', 'stylesheet', 'text/css');

		$tpl->SetVariable('workarea-style', 'style="margin-top: 30px;" ');
        $tpl->SetVariable('confirmPostDelete', _t('PROPERTIES_POST_CONFIRM_DELETE'));
		
		if ($syntactsUrl) {
			include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
			$snoopy = new Snoopy('Properties');
			$model = $GLOBALS['app']->LoadGadget('Properties', 'Model');
					
			/*
			if (!is_null($request->get('gadgetMsg', 'get'))) {
	            $submit_vars['simple-msg:0'] =  _t($request->get('gadgetMsg', 'get'));
			}
			*/
			
			$submit_url = $syntactsUrl;
			
			$id = $request->get('id', 'get');
			$linkid = $request->get('linkid', 'get');

			// send post records
			if (!empty($id)) {
				$post = $model->GetProperty($id);
		        if (!Jaws_Error::IsError($post) && ($GLOBALS['app']->Session->GetPermission('Properties', 'ManageProperties') || $post['ownerid'] == $GLOBALS['app']->Session->GetAttribute('user_id'))) {
					$i = 0;
					$j = 0;
					$submit_vars['0:cols'] = 74;
					foreach($post as $e => $v) {		            
						if ($e == 'description') {
							$submit_vars[SYNTACTS_DB . "0:$j:0"] = $this->ParseText($v, 'Properties');
						} else if ($e == 'image') {
							$main_image_src = '';
							if (isset($v) && !empty($v)) {
								$v = $xss->filter(strip_tags($v));
								if (substr(strtolower($v), 0, 4) == "http") {
									if (substr(strtolower($v), 0, 7) == "http://") {
										$main_image_src = explode('http://', $v);
										foreach ($main_image_src as $img_src) {
											if (!empty($img_src)) {
												$main_image_src = 'http://'.$img_src;
												break;
											}
										}
									} else {
										$main_image_src = explode('https://', $v);
										foreach ($main_image_src as $img_src) {
											if (!empty($img_src)) {
												$main_image_src = 'https://'.$img_src;
												break;
											}
										}
									}
								} else {
									$thumb = Jaws_Image::GetThumbPath($v);
									$medium = Jaws_Image::GetMediumPath($v);
									if (file_exists(JAWS_DATA . 'files'.$thumb)) {
										$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$thumb;
									} else if (file_exists(JAWS_DATA . 'files'.$medium)) {
										$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$medium;
									} else if (file_exists(JAWS_DATA . 'files'.$v)) {
										$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$v;
									}
								}
							}
							$submit_vars[SYNTACTS_DB . "0:$j:0"] = (!empty($main_image_src) ? $main_image_src : '');
							$submit_vars[SYNTACTS_DB . "0:74:0"] = $v;
						} else {	
							$submit_vars[SYNTACTS_DB . "0:$j:0"] = $xss->filter($v);
						}
						$j++;
						if ($j > $submit_vars['0:cols']) {
							$j=0;
						}
						//echo '<br />'.$v;
						$i++;
					}
					$submit_vars['0:rows'] = $i-1;
					//$submit_vars['0:rows'] = 0;

				} else {
					if (Jaws_Error::IsError($post)) {
						$page = _t('PROPERTIES_ERROR_ASPPAGE_NOT_RETRIEVED', $post->GetMessage())."\n";
                    }
					//return new Jaws_Error(_t('PROPERTIES_ERROR_POST_NOT_FOUND'), _t('PROPERTIES_NAME'));
				}
								
			} else if (!empty($linkid)) {
				// send highest sort_order
				$sql = "SELECT MAX([sort_order]) FROM [[property]] WHERE [linkid] = {linkid} ORDER BY [sort_order] DESC";
				$max = $GLOBALS['db']->queryOne($sql, array('linkid' => $linkid), array('integer'));
				if (Jaws_Error::IsError($max)) {
					$page = _t('PROPERTIES_ERROR_ASPPAGE_NOT_RETRIEVED', $max->GetMessage())."\n";
					//return $max;
				}
				$submit_vars[SYNTACTS_DB ."3:0:0"] = $max;
				$submit_vars['3:cols'] = 0;
				$submit_vars['3:rows'] = 0;
				
			/*
			} else {
				// Send us to the appropriate page
				require_once JAWS_PATH . 'include/Jaws/Header.php';
				if ($account == true) {
					Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'));
				} else {
					Jaws_Header::Location($base_url . '?gadget=Properties&action=Admin');
				}
			*/
			}

			// send highest propertyno
			$sql = "SELECT MAX([propertyno]) FROM [[property]] ORDER BY [propertyno] DESC";
			$max2 = $GLOBALS['db']->queryOne($sql);
			if (Jaws_Error::IsError($max2)) {
				$page = _t('PROPERTIES_ERROR_ASPPAGE_NOT_RETRIEVED', $max2->GetMessage())."\n";
				//return $max2;
			}
			$submit_vars[SYNTACTS_DB ."4:0:0"] = $max2;
			$submit_vars['4:cols'] = 0;
			$submit_vars['4:rows'] = 0;
			
			// send status types
			$checked = $GLOBALS['app']->Registry->Get('/gadgets/Properties/user_status_limit');
			$checked = explode(",",$checked);
			$statusHTML = '';
			$statusHTML .= '<SELECT SIZE="1" NAME="status" onChange="if (this.value == \'forrent\' || this.value == \'rented\') { toggleYes(\'rentalRates\'); toggleYes(\'rentalInfo\'); toggleYes(\'rentalAvailabilityLink\'); } else if (this.value == \'forlease\' || this.value == \'leased\') {toggleYes(\'rentalRates\'); toggleNo(\'rentalInfo\'); toggleYes(\'rentalAvailabilityLink\');} else { toggleNo(\'rentalRates\'); toggleNo(\'rentalAvailabilityLink\'); toggleNo(\'rentalInfo\'); };">'."\n";
			if (($account === true && (in_array("forsale", $checked))) || $account === false) {
				$statusHTML .= '<OPTION'.((!isset($post['id']) || empty($post['id'])) || $post['status'] == "forsale" ? ' SELECTED' : '').' VALUE="forsale">'._t('PROPERTIES_STATUS_FORSALE').'</OPTION>'."\n";
			}
			if (($account === true && (in_array("forrent", $checked))) || $account === false) {
				$statusHTML .= '<OPTION'.($post['status'] == "forrent" ? ' SELECTED' : '').' VALUE="forrent">'._t('PROPERTIES_STATUS_FORRENT').'</OPTION>'."\n";
			}
			if (($account === true && (in_array("forlease", $checked))) || $account === false) {
				$statusHTML .= '<OPTION'.($post['status'] == "forlease" ? ' SELECTED' : '').' VALUE="forlease">'._t('PROPERTIES_STATUS_FORLEASE').'</OPTION>'."\n";
			}
			if (($account === true && (in_array("undercontract", $checked))) || $account === false) {
				$statusHTML .= '<OPTION'.($post['status'] == "undercontract" ? ' SELECTED' : '').' VALUE="undercontract">'._t('PROPERTIES_STATUS_UNDERCONTRACT').'</OPTION>'."\n";
			}
			if (($account === true && (in_array("sold", $checked))) || $account === false) {
				$statusHTML .= '<OPTION'.($post['status'] == "sold" ? ' SELECTED' : '').' VALUE="sold">'._t('PROPERTIES_STATUS_SOLD').'</OPTION>'."\n";
			}
			if (($account === true && (in_array("rented", $checked))) || $account === false) {
				$statusHTML .= '<OPTION'.($post['status'] == "rented" ? ' SELECTED' : '').' VALUE="rented">'._t('PROPERTIES_STATUS_RENTED').'</OPTION>'."\n";
			}
			if (($account === true && (in_array("leased", $checked))) || $account === false) {
				$statusHTML .= '<OPTION'.($post['status'] == "leased" ? ' SELECTED' : '').' VALUE="leased">'._t('PROPERTIES_STATUS_LEASED').'</OPTION>'."\n";
			}
			$statusHTML .= '</SELECT>'."\n";

			// send amenity records
			$amenities = $model->GetAmenityTypes();
			
			if (!Jaws_Error::IsError($amenities)) {
				$amenitiesHTML = '';
				$lastType = 0;
				$loopCount = 0;
				foreach($amenities as $amenity) {		            
					if ($GLOBALS['app']->Session->GetPermission('Properties', 'ManageProperties') || ($amenity['ownerid'] == $GLOBALS['app']->Session->GetAttribute('user_id') || $amenity['ownerid'] == 0)) {
						$types = $model->GetAmenitiesOfType((int)$amenity['id']);
						if (!Jaws_Error::IsError($types)) {
							foreach($types as $type) {		            
								if ($GLOBALS['app']->Session->GetPermission('Properties', 'ManageProperties') || ($type['ownerid'] == (int)$GLOBALS['app']->Session->GetAttribute('user_id') || $type['ownerid'] == 0)) {
									if ($type['typeid'] != $lastType) {
										$lastType = $type['typeid'];
										$loopCount = 1;
										$amenitiesHTML .= "<tr><td style=\"border:1pt solid #CCCCCC; padding: 5px; background: #EEEEEE;\" colspan=\"2\" width=\"100%\"><b>".$amenity['title']."</b></td></tr>";
									} else {
										if ($loopCount % 2 == 0 && $loopCount > 0) {
										$loopCount = 1;
										$amenitiesHTML .= "</tr><tr>";
										} else {
											$loopCount++;
										}
									}
									$amenitiesHTML .= "<td style=\"padding: 3px;\"><INPUT TYPE=\"checkbox\" NAME=\"amenities\" VALUE=\"".$type['id']."\"";
									$amenityChecked = false;
									if (isset($post['amenity']) && !empty($post['amenity'])) {
										$propAmenities = explode(',', $post['amenity']);
										foreach($propAmenities as $propAmenity) {		            
											If ($type['id'] == (int)$propAmenity) { 
												$amenityChecked = true;
												$amenitiesHTML .= "CHECKED";
												break;
											}
										}
									} 
									$amenitiesHTML .= ">&nbsp;";
									if (isset($type['description']) && !empty($type['description'])) {
										$amenitiesHTML .= "<a href=\"javascript: void(0);\" title=\"".$type['description']."\">".$type['feature']."<a/>";
									} else {
										$amenitiesHTML .= $type['feature'];
									}
									$amenitiesHTML .= "&nbsp;&nbsp;&nbsp;</td>";
								}
							}
						}
					}
				}
				if ($amenitiesHTML != '') {
					$amenitiesHTML = $amenitiesHTML;
				} else {
					$amenitiesHTML = "No amenities currently exist, <a href=\"".$base_url."?gadget=Properties&action=".$submit_vars['ACTIONPREFIX']."B\">CREATE ONE</a>.";
				}
				
			} else {
				$page = _t('STORE_ERROR_ASPPAGE_NOT_RETRIEVED', $amenities->GetMessage())."\n";
				//return new Jaws_Error(_t('STORE_ERROR_POST_NOT_FOUND'), _t('STORE_NAME'));
			}
			
			// send default region records
			$regions = $model->GetRegionsOfParent(0);
			
			if (!Jaws_Error::IsError($regions)) {
				$i = 0;
				$j = 0;
				$submit_vars['5:cols'] = 8;
				foreach($regions as $region) {		            
					foreach ($region as $r => $rv) {
						$submit_vars[SYNTACTS_DB ."5:$j:$i"] = $xss->parse($rv);
						$j++;
						if ($j > $submit_vars['5:cols']) {
							$j=0;
						}
					}
					$i++;
				}
				$submit_vars['5:rows'] = $i-1;
			} else {
				$page = _t('PROPERTIES_ERROR_ASPPAGE_NOT_RETRIEVED', $regions->GetMessage())."\n";
				//return new Jaws_Error(_t('PROPERTIES_ERROR_POST_NOT_FOUND'), _t('PROPERTIES_NAME'));
			}
			
			// send possible Parent records
			$parents = $model->GetPropertyParents();
			if (!Jaws_Error::IsError($parents)) {
				$categoriesHTML = '';
				$categoriesFound = false;
				$loopCount = 0;
				foreach($parents as $parent) {		            
					if ($parent['propertyparentownerid'] == $GLOBALS['app']->Session->GetAttribute('user_id') || $parent['propertyparentownerid'] == 0) {
						$categoriesFound = true;
						// Build Categories checkboxes
						if ($account === false ) {
							if ($loopCount % 2 == 0 && $loopCount > 0) {
								$loopCount = 1;
								$categoriesHTML .= "</tr><tr>";
							} else {
								$loopCount++;
							}
							$categoriesHTML .= "<td style=\"padding: 3px;\"><INPUT TYPE=\"checkbox\" NAME=\"categories\" VALUE=\"".$parent['propertyparentid']."\"";
							$categoryChecked = false;
							if (isset($post['category']) && !empty($post['category'])) {
								$propCategories = explode(',', $post['category']);
								foreach($propCategories as $propCategory) {		            
									If ($parent['propertyparentid'] == (int)$propCategory) { 
										$categoryChecked = true;
										$categoriesHTML .= "CHECKED";
										break;
									}
								}
							} 
							If ($linkid == $parent['propertyparentid'] && $categoryChecked === false) {
								$categoriesHTML .= "CHECKED";
							}
							$categoriesHTML .= ">&nbsp;";
							//if (isset($parent['propertyparentdescription']) && !empty($parent['propertyparentdescription'])) {
							//	$categoriesHTML .= "<a href=\"javascript: void(0);\" title=\"".strip_tags($parent['propertyparentdescription'])."\">".$parent['propertyparentcategory_name']."<a/>";
							//} else {
								$categoriesHTML .= $parent['propertyparentcategory_name'];
							//}
							$categoriesHTML .= "&nbsp;&nbsp;&nbsp;</td>";
						} else {
							if (isset($post['category']) && !empty($post['category'])) {
								$propCategories = explode(',', $post['category']);
								$propCategories[0] = (int)$propCategories[0];
								$propCategories[1] = (int)$propCategories[1];
								$propCategories[2] = (int)$propCategories[2];
							}
							//if ($parent['productparentactive'] == 'Y') {
								$categoriesHTML .= "<option value=\"".$parent['propertyparentid']."\"".($propCategories[0] == $parent['propertyparentid'] ? ' selected="selected"' : '').">".$parent['propertyparentcategory_name']."</option>";
								$categoriesHTML2 .= "<option value=\"".$parent['propertyparentid']."\"".($propCategories[1] == $parent['propertyparentid'] ? ' selected="selected"' : '').">".$parent['propertyparentcategory_name']."</option>";
								$categoriesHTML3 .= "<option value=\"".$parent['propertyparentid']."\"".($propCategories[2] == $parent['propertyparentid'] ? ' selected="selected"' : '').">".$parent['propertyparentcategory_name']."</option>";
								//$categoriesCombo->AddOption($parent['productparentcategory_name'], $parent['propertyparentid']);
							//}
						}
					}
				}
				if ($categoriesFound === true) {
					if ($account === true) {
						$categoriesHTML = '<select name="category1" id="category1" class="property_category1_field">'."<option value=\"\">Select...</option>".$categoriesHTML.'</select>' .
							'&nbsp;&nbsp;<select name="category2" id="category2" class="property_category2_field">'."<option value=\"\">(Optional)</option>".$categoriesHTML2.'</select>' .
							'&nbsp;&nbsp;<select name="category3" id="category3" class="property_category3_field">'."<option value=\"\">(Optional)</option>".$categoriesHTML3.'</select>';
					}
				} else {
					if ($account === false) {
						$categoriesHTML = "No categories currently exist, <a href=\"admin.php?gadget=Properties&action=Admin\">CREATE ONE</a>.";
					}
				}
			} else {
				$page = _t('PROPERTIES_ERROR_ASPPAGE_NOT_RETRIEVED', $parents->GetMessage())."\n";
			}

			// send editor HTML to syntacts
			$description = (isset($post['description'])) ? $post['description'] : '';
			$editor =& $GLOBALS['app']->LoadEditor('Properties', 'description', $description, false);
			$editor->TextArea->SetStyle('width: 100%;');
			$editor->SetWidth('490px');

			// send Post records
			$posts = $model->GetAllPostsOfProperty($post['id']);
			
			if (!Jaws_Error::IsError($posts)) {
				$i = 0;
				$j = 0;
				$submit_vars['7:cols'] = 15;
				foreach($posts as $p) {
					if ($i < 20) {
						foreach ($p as $e => $ev) {
							if ($e == 'description') {
								$submit_vars[SYNTACTS_DB . "7:$j:$i"] = $this->ParseText($ev, 'Properties');
							} else if ($e == 'image') {
								$main_image_src = '';
								if (isset($ev) && !empty($ev)) {
									$ev = $xss->filter(strip_tags($ev));
									if (substr(strtolower($ev), 0, 4) == "http") {
										if (substr(strtolower($ev), 0, 7) == "http://") {
											$main_image_src = explode('http://', $ev);
											foreach ($main_image_src as $img_src) {
												if (!empty($img_src)) {
													$main_image_src = 'http://'.$img_src;
													break;
												}
											}
										} else {
											$main_image_src = explode('https://', $ev);
											foreach ($main_image_src as $img_src) {
												if (!empty($img_src)) {
													$main_image_src = 'https://'.$img_src;
													break;
												}
											}
										}
									} else {
										$thumb = Jaws_Image::GetThumbPath($ev);
										$medium = Jaws_Image::GetMediumPath($ev);
										if (file_exists(JAWS_DATA . 'files'.$thumb)) {
											$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$thumb;
										} else if (file_exists(JAWS_DATA . 'files'.$medium)) {
											$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$medium;
										} else if (file_exists(JAWS_DATA . 'files'.$ev)) {
											$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$ev;
										}
									}
								}
								$submit_vars[SYNTACTS_DB . "7:$j:$i"] = $main_image_src;
							} else {	
								$submit_vars[SYNTACTS_DB . "7:$j:$i"] = $xss->filter($ev);
							}
							$j++;
							if ($j > $submit_vars['7:cols']) {
								$j=0;
							}
						}
						$i++;
					}
				}
				$submit_vars['7:rows'] = $i-1;
			} else {
				$page = _t('PROPERTIES_ERROR_ASPPAGE_NOT_RETRIEVED', $posts->GetMessage())."\n";
			}

			$submit_vars['HTTP_REFERER'] = $GLOBALS['app']->GetSiteURL();
			$submit_vars['JAWS_URL'] = $GLOBALS['app']->GetJawsURL() . '/';
			$submit_vars['DPATH'] = '';
			$submit_vars["ID"] = $post['id'];
			$submit_vars["LINKID"] = $linkid;
			
			if ($account === true) {
				$user_post_limit = $GLOBALS['app']->Registry->Get('/gadgets/Properties/user_post_limit');
				$submit_vars['POST_LIMIT'] = ((int)$user_post_limit - ($submit_vars['7:rows']+1));
			}
			
			if (!isset($page)) {
				if($snoopy->submit($submit_url,$submit_vars)) {
					//while(list($key,$val) = each($snoopy->headers))
						//echo $key.": ".$val."<br>\n";
					//echo "<p>\n";
					
					//$page = "<PRE>".htmlspecialchars($snoopy->results)."</PRE>\n";
					$page = $snoopy->results;
					$page = str_replace("__JAWS_EDITOR__", $editor->Get(), $page);
					$page = str_replace("__STATUS_HTML__", $statusHTML, $page);
					$page = str_replace("__CATEGORIES_HTML__", $categoriesHTML, $page);
					$page = str_replace("__AMENITIES_HTML__", $amenitiesHTML, $page);
				} else {
					$page = _t('PROPERTIES_ERROR_ASPPAGE_NOT_RETRIEVED', $snoopy->error)."\n";
				}
			}

			$tpl->SetVariable('content', $page);
		}
		
        $tpl->ParseBlock('gadget_property');

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
     * We are on the A_form2 page
     *
     * @access public
     * @return string
     */
    function A_form2($account = false)
    {
		if ($account === false) {
			$GLOBALS['app']->Session->CheckPermission('Properties', 'default');
		} else {
			$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
			if (!$GLOBALS['app']->Session->GetPermission('Properties', 'default')) {
				if (!$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Properties', 'OwnProperty')) {
		            $GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
					$userHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
					return $userHTML->DefaultAction();
				}
			}
		}

		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$request =& Jaws_Request::getInstance();
				
		$tpl = new Jaws_Template('gadgets/Properties/templates/');
        $tpl->Load('admin.html');

        $tpl->SetBlock('gadget_property');

		$request =& Jaws_Request::getInstance();
		$action = $request->get('action', 'get');
		if ($account === false) {
			$tpl->SetVariable('menubar', $this->MenuBar($action));
			$submit_vars['ACTIONPREFIX'] = "";
			$GLOBALS['app']->Layout->AddScriptLink('libraries/js/admin.js');
			$OwnerID = 0;			
			$base_url = BASE_SCRIPT;
		} else {
			$tpl->SetVariable('menubar', '');
			$submit_vars['ACTIONPREFIX'] = "account_";
			$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
			$base_url = 'index.php';
		}
		$syntactsUrl = $GLOBALS['app']->getSyntactsAdminUrl("Properties/admin_Properties_A_form2");

		$tpl->SetVariable('workarea-style', 'style="margin-top: 30px;" ');

		if ($syntactsUrl) {
			include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
			$snoopy = new Snoopy('Properties');
			$model = $GLOBALS['app']->LoadGadget('Properties', 'Model');
								
			$submit_url = $syntactsUrl;
			
			$id = $request->get('id', 'get');
			$linkid = $request->get('linkid', 'get');
			
			// send post records
			if (!empty($id)) {
				$post = $model->GetPost($id);
		        if (!Jaws_Error::IsError($post) && ($GLOBALS['app']->Session->GetPermission('Properties', 'ManageProperties') || $post['ownerid'] == $OwnerID)) {
					$i = 0;
					$j = 0;
					$submit_vars['0:cols'] = 15;
					foreach($post as $e => $v) {		            
							
							$submit_vars[SYNTACTS_DB ."0:$j:0"] = $xss->parse($v);
							
							$j++;
							if ($j > $submit_vars['0:cols']) {
								$j=0;
							}
							//echo $xss->parse($value);
							$i++;
					}
					$submit_vars['0:rows'] = $i-1;
					//$submit_vars['0:rows'] = 0;

				} else {
					$page = _t('PROPERTIES_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403'))."\n";
				}
								
			} else if (!empty($linkid)) {
				// send highest sort_order
				$sql = "SELECT MAX([sort_order]) FROM [[property_posts]] ORDER BY [sort_order] DESC";
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
				if ($account == true) {
					Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'));
				} else {
					Jaws_Header::Location($base_url . '?gadget=Properties&action=Admin');
				}
			}

			// send editor HTML to syntacts
			$description = (isset($post['description'])) ? $post['description'] : '';
			$editor =& $GLOBALS['app']->LoadEditor('Properties', 'description', $description, false);
			$editor->TextArea->SetStyle('width: 100%;');
			$editor->SetWidth('490px');

			$submit_vars['HTTP_REFERER'] = $GLOBALS['app']->GetSiteURL();
			$submit_vars['JAWS_URL'] = $GLOBALS['app']->GetJawsURL() . '/';
			$submit_vars['DPATH'] = '';
			$submit_vars["ID"] = $id;
			$submit_vars["LINKID"] = $linkid;
			
			if (!isset($page)) {
				if($snoopy->submit($submit_url,$submit_vars)) {
					//while(list($key,$val) = each($snoopy->headers))
						//echo $key.": ".$val."<br>\n";
					//echo "<p>\n";
					
					//$page = "<PRE>".htmlspecialchars($snoopy->results)."</PRE>\n";
					$page = $snoopy->results;
					$page = str_replace("__JAWS_EDITOR__", $editor->Get(), $page);
				} else {
					$page = _t('PROPERTIES_ERROR_ASPPAGE_NOT_RETRIEVED', $snoopy->error)."\n";
				}
			}

			$tpl->SetVariable('content', $page);
		}
		
        $tpl->ParseBlock('gadget_property');

        return $tpl->Get();

    }

    /**
     * We are on the A_form_post2 page
     *
     * @access public
     * @return string
     */
    function A_form_post2($account = false)
    {

		if ($account === false) {
			return $this->form_post();
		} else {
			return $this->form_post(true);
		}

    }

    /**
     * We are on the property amenity list page
     *
     * @access public
     * @return string
     */
    function B($account = false)
    {
		if ($account === false) {
			$GLOBALS['app']->Session->CheckPermission('Properties', 'default');
		} else {
			$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
			if (!$GLOBALS['app']->Session->GetPermission('Properties', 'default')) {
				if (!$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Properties', 'OwnProperty')) {
		            $GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
					$userHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
					return $userHTML->DefaultAction();
				}
			}
		}
        $tpl = new Jaws_Template('gadgets/Properties/templates/');
        $tpl->Load('admin.html');
        $tpl->SetBlock('amenities_admin');
        
		$request =& Jaws_Request::getInstance();
		$action = $request->get('action', 'get');
		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=CustomPage&amp;action=account_SetGBRoot');
		if ($account === false) {
			$this->AjaxMe('script.js');
			$tpl->SetVariable('menubar', $this->MenuBar($action));
			$GLOBALS['app']->Layout->AddScriptLink('libraries/js/admin.js');
			$OwnerID = 0;			
			$base_url = BASE_SCRIPT;
		} else {
			$tpl->SetVariable('menubar', '');
			$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
			$base_url = 'index.php';
		}

		$GLOBALS['app']->Layout->AddScriptLink('libraries/greybox/AJS.js');
        $GLOBALS['app']->Layout->AddScriptLink('libraries/greybox/AJS_fx.js');
        $GLOBALS['app']->Layout->AddScriptLink('libraries/greybox/gb_scripts.js');
        $GLOBALS['app']->Layout->AddHeadLink('libraries/greybox/gb_styles.css', 'stylesheet', 'text/css');

        $tpl->SetVariable('grid', $this->AmenityDataGrid());

        $toolBar   =& Piwi::CreateWidget('HBox');

        $deleteAll =& Piwi::CreateWidget('Button', 'deleteAllAmenities',
                                         _t('GLOBAL_DELETE'),
                                         STOCK_DELETE);
        $deleteAll->AddEvent(ON_CLICK,
                             "javascript: massiveDeleteAmenity('"._t('PROPERTIES_CONFIRM_MASIVE_DELETE_AMENITY')."');");

        $toolBar->Add($deleteAll);

        $tpl->SetVariable('tools', $toolBar->Get());
                
        //Status filter
        $status = '';
        $statusCombo =& Piwi::CreateWidget('Combo', 'status');
        $statusCombo->setId('status');
        $statusCombo->AddOption('&nbsp;', '');
        $statusCombo->AddOption(_t('PROPERTIES_PUBLISHED'), 'Y');
        $statusCombo->AddOption(_t('PROPERTIES_DRAFT'), 'N');
        $statusCombo->SetDefault($status);
        $statusCombo->AddEvent(ON_CHANGE, 'javascript: searchAmenity();');
        $tpl->SetVariable('status', _t('PROPERTIES_ACTIVE'));
        $tpl->SetVariable('status_field', $statusCombo->Get());

        // Free text search
        $searchButton =& Piwi::CreateWidget('Button', 'searchButton', _t('GLOBAL_SEARCH'), STOCK_SEARCH);
        $searchButton->AddEvent(ON_CLICK, 'javascript: searchAmenity();');
        $tpl->SetVariable('search', $searchButton->Get());

        $search = '';
        $searchEntry =& Piwi::CreateWidget('Entry', 'search', $search);
        $searchEntry->SetStyle('zwidth: 100%;');
        $tpl->SetVariable('search_field', $searchEntry->Get());

        $tpl->SetVariable('entries', $this->AmenityDatagrid());

        $addPage =& Piwi::CreateWidget('Button', 'add_amenity', _t('PROPERTIES_ADD_AMENITY'), STOCK_ADD);
		if ($account === false) {
			$addPage->AddEvent(ON_CLICK, "javascript: window.location = '".$base_url.'?gadget=Properties&amp;action=B_form'."';");
        } else {
			$addPage->AddEvent(ON_CLICK, "javascript: window.location = '".$base_url.'?gadget=Properties&amp;action=account_B_form'."';");
		}
		$tpl->SetVariable('add_amenity', $addPage->Get());

        $tpl->ParseBlock('amenities_admin');

        return $tpl->Get();
    }

    /**
     * We are on the B_form page
     *
     * @access public
     * @return string
     */
    function B_form($account = false)
    {
		if ($account === false) {
			$GLOBALS['app']->Session->CheckPermission('Properties', 'default');
		} else {
			$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
			if (!$GLOBALS['app']->Session->GetPermission('Properties', 'default')) {
				if (!$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Properties', 'OwnProperty')) {
		            $GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
					$userHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
					return $userHTML->DefaultAction();
				}
			}
		}
		
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$request =& Jaws_Request::getInstance();
		$action = $request->get('action', 'get');
				
		$tpl = new Jaws_Template('gadgets/Properties/templates/');
        $tpl->Load('admin.html');

        // Handle any simple messages
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

        $tpl->SetBlock('gadget_property');

		if ($account === false) {
			$tpl->SetVariable('menubar', $this->MenuBar($action));
			$submit_vars['ACTIONPREFIX'] = "";
			$GLOBALS['app']->Layout->AddScriptLink('libraries/js/admin.js');
			$OwnerID = 0;			
			$base_url = BASE_SCRIPT;
		} else {
			$tpl->SetVariable('menubar', '');
			$submit_vars['ACTIONPREFIX'] = "account_";
			$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
			$base_url = 'index.php';
		}
		$syntactsUrl = $GLOBALS['app']->getSyntactsAdminUrl("Properties/admin_Properties_B_form");

		$tpl->SetVariable('workarea-style', 'style="margin-top: 30px;" ');

		if ($syntactsUrl) {
			include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
			$snoopy = new Snoopy('Properties');
			$model = $GLOBALS['app']->LoadGadget('Properties', 'Model');
								
			$submit_url = $syntactsUrl;
			
			$id = $request->get('id', 'get');

			// send post records
			if (!empty($id)) {
				$post = $model->GetAmenity($id);
		        if (!Jaws_Error::IsError($post) && ($GLOBALS['app']->Session->GetPermission('Properties', 'ManageProperties') || $post['ownerid'] == $GLOBALS['app']->Session->GetAttribute('user_id'))) {
					$i = 0;
					$j = 0;
					$submit_vars['0:cols'] = 8;
					foreach($post as $e => $v) {		            
							
							$submit_vars[SYNTACTS_DB ."0:$j:0"] = $xss->parse($v);
							
							$j++;
							if ($j > $submit_vars['0:cols']) {
								$j=0;
							}
							//echo $xss->parse($value);
							$i++;
					}
					$submit_vars['0:rows'] = $i-1;
					//$submit_vars['0:rows'] = 0;

				} else {
                    //return new Jaws_Error(_t('PROPERTIES_ERROR_POST_NOT_FOUND'), _t('PROPERTIES_NAME'));
				}
								
			}

			// send editor HTML to syntacts
			$description = (isset($post['description'])) ? $post['description'] : '';
			$editor =& $GLOBALS['app']->LoadEditor('Properties', 'description', $description, false);
			$editor->TextArea->SetStyle('width: 100%;');
			$editor->SetWidth('490px');
			
			// send Post records
			$posts = $model->GetAmenityTypes();
			
			if (!Jaws_Error::IsError($posts)) {
				$i = 0;
				$j = 0;
				$submit_vars['1:cols'] = 7;
				foreach($posts as $type) {		            
					if ($GLOBALS['app']->Session->GetPermission('Properties', 'ManageProperties') || $type['ownerid'] == $GLOBALS['app']->Session->GetAttribute('user_id') || $type['ownerid'] == 0) {
						foreach ($type as $p => $pv) {
							$submit_vars[SYNTACTS_DB ."1:$j:$i"] = $xss->parse($pv);
							$j++;
							if ($j > $submit_vars['1:cols']) {
								$j=0;
							}
						}
						$i++;
					}
				}
				$submit_vars['1:rows'] = $i-1;
			} else {
				//return new Jaws_Error(_t('PROPERTIES_ERROR_POST_NOT_FOUND'), _t('PROPERTIES_NAME'));
			}
			
			$submit_vars['HTTP_REFERER'] = $GLOBALS['app']->GetSiteURL();
			$submit_vars['JAWS_URL'] = $GLOBALS['app']->GetJawsURL() . '/';
			$submit_vars['DPATH'] = '';
			$submit_vars["ID"] = $id;
			

			if (!isset($page)) {
				if($snoopy->submit($submit_url,$submit_vars)) {
					//while(list($key,$val) = each($snoopy->headers))
						//echo $key.": ".$val."<br>\n";
					//echo "<p>\n";
					
					//$page = "<PRE>".htmlspecialchars($snoopy->results)."</PRE>\n";
					$page = $snoopy->results;
					$page = str_replace("__JAWS_EDITOR__", $editor->Get(), $page);
				} else {
					$page = _t('PROPERTIES_ERROR_ASPPAGE_NOT_RETRIEVED', $snoopy->error)."\n";
				}
			}

			$tpl->SetVariable('content', $page);
		}
		
        $tpl->ParseBlock('gadget_property');

        return $tpl->Get();

    }

    /**
     * We are on the B_form_post page
     *
     * @access public
     * @return string
     */
    function B_form_post($account = false)
    {

		if ($account === false) {
			return $this->form_post();
		} else {
			return $this->form_post(true);
		}

    }

    /**
     * We are on the amenity types list page
     *
     * @access public
     * @return string
     */
    function B2($account = false)
    {
		if ($account === false) {
			$GLOBALS['app']->Session->CheckPermission('Properties', 'default');
		} else {
			$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
			if (!$GLOBALS['app']->Session->GetPermission('Properties', 'default')) {
				if (!$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Properties', 'OwnProperty')) {
		            $GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
					$userHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
					return $userHTML->DefaultAction();
				}
			}
		}

        $tpl = new Jaws_Template('gadgets/Properties/templates/');
        $tpl->Load('admin.html');
        $tpl->SetBlock('amenity_types_admin');
        
		$request =& Jaws_Request::getInstance();
		$action = $request->get('action', 'get');
        
		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=CustomPage&amp;action=account_SetGBRoot');
		if ($account === false) {
			$this->AjaxMe('script.js');
			$tpl->SetVariable('menubar', $this->MenuBar($action));
			$GLOBALS['app']->Layout->AddScriptLink('libraries/js/admin.js');
			$OwnerID = 0;			
			$base_url = BASE_SCRIPT;
		} else {
			$tpl->SetVariable('menubar', '');
			$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
			$base_url = 'index.php';
		}

		$GLOBALS['app']->Layout->AddScriptLink('libraries/greybox/AJS.js');
        $GLOBALS['app']->Layout->AddScriptLink('libraries/greybox/AJS_fx.js');
        $GLOBALS['app']->Layout->AddScriptLink('libraries/greybox/gb_scripts.js');
        $GLOBALS['app']->Layout->AddHeadLink('libraries/greybox/gb_styles.css', 'stylesheet', 'text/css');

        $tpl->SetVariable('grid', $this->AmenityTypesDataGrid());

        $toolBar   =& Piwi::CreateWidget('HBox');

        $deleteAll =& Piwi::CreateWidget('Button', 'deleteAllAmenityTypes',
                                         _t('GLOBAL_DELETE'),
                                         STOCK_DELETE);
        $deleteAll->AddEvent(ON_CLICK,
                             "javascript: massiveDeleteAmenityTypes('"._t('PROPERTIES_CONFIRM_MASIVE_DELETE_AMENITYTYPES')."');");

        $toolBar->Add($deleteAll);

        $tpl->SetVariable('tools', $toolBar->Get());
                
        //Status filter
        $status = '';
        $statusCombo =& Piwi::CreateWidget('Combo', 'status');
        $statusCombo->setId('status');
        $statusCombo->AddOption('&nbsp;', '');
        $statusCombo->AddOption(_t('PROPERTIES_PUBLISHED'), 'Y');
        $statusCombo->AddOption(_t('PROPERTIES_DRAFT'), 'N');
        $statusCombo->SetDefault($status);
        $statusCombo->AddEvent(ON_CHANGE, 'javascript: searchAmenityType();');
        $tpl->SetVariable('status', _t('PROPERTIES_ACTIVE'));
        $tpl->SetVariable('status_field', $statusCombo->Get());

        // Free text search
        $searchButton =& Piwi::CreateWidget('Button', 'searchButton', _t('GLOBAL_SEARCH'), STOCK_SEARCH);
        $searchButton->AddEvent(ON_CLICK, 'javascript: searchAmenityType();');
        $tpl->SetVariable('search', $searchButton->Get());

        $search = '';
        $searchEntry =& Piwi::CreateWidget('Entry', 'search', $search);
        $searchEntry->SetStyle('zwidth: 100%;');
        $tpl->SetVariable('search_field', $searchEntry->Get());

        $tpl->SetVariable('entries', $this->AmenityTypesDataGrid());

        $addPage =& Piwi::CreateWidget('Button', 'add_amenity_types', _t('PROPERTIES_ADD_AMENITYTYPES'), STOCK_ADD);
        if ($account === false) {
			$addPage->AddEvent(ON_CLICK, "javascript: window.location = '".BASE_SCRIPT.'?gadget=Properties&amp;action=B_form2'."';");
        } else {
			$addPage->AddEvent(ON_CLICK, "javascript: window.location = '".$base_url.'?gadget=Properties&amp;action=account_B_form2'."';");
		}
		$tpl->SetVariable('add_amenity_types', $addPage->Get());

        $tpl->ParseBlock('amenity_types_admin');

        return $tpl->Get();
    }

    /**
     * We are on the B_form2 page
     *
     * @access public
     * @return string
     */
    function B_form2($account = false)
    {
		if ($account === false) {
			$GLOBALS['app']->Session->CheckPermission('Properties', 'default');
		} else {
			$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
			if (!$GLOBALS['app']->Session->GetPermission('Properties', 'default')) {
				if (!$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Properties', 'OwnProperty')) {
		            $GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
					$userHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
					return $userHTML->DefaultAction();
				}
			}
		}
		
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$request =& Jaws_Request::getInstance();
		$action = $request->get('action', 'get');
				
		$tpl = new Jaws_Template('gadgets/Properties/templates/');
        $tpl->Load('admin.html');

        // Handle any simple messages
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

        $tpl->SetBlock('gadget_property');

		if ($account === false) {
			$tpl->SetVariable('menubar', $this->MenuBar($action));
			$submit_vars['ACTIONPREFIX'] = "";
			$GLOBALS['app']->Layout->AddScriptLink('libraries/js/admin.js');
			$OwnerID = 0;			
			$base_url = BASE_SCRIPT;
		} else {
			$tpl->SetVariable('menubar', '');
			$submit_vars['ACTIONPREFIX'] = "account_";
			$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
			$base_url = 'index.php';
		}
		$syntactsUrl = $GLOBALS['app']->getSyntactsAdminUrl("Properties/admin_Properties_B_form2");

		$tpl->SetVariable('workarea-style', 'style="margin-top: 30px;" ');

		if ($syntactsUrl) {
			include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
			$snoopy = new Snoopy('Properties');
			$model = $GLOBALS['app']->LoadGadget('Properties', 'Model');
								
			$submit_url = $syntactsUrl;
			
			$id = $request->get('id', 'get');

			// send post records
			if (!empty($id)) {
				$post = $model->GetAmenityType($id);
		        if (!Jaws_Error::IsError($post) && ($GLOBALS['app']->Session->GetPermission('Properties', 'ManageProperties') || $post['ownerid'] == $GLOBALS['app']->Session->GetAttribute('user_id'))) {
					$i = 0;
					$j = 0;
					$submit_vars['0:cols'] = 7;
					foreach($post as $e => $v) {		            
							
							$submit_vars[SYNTACTS_DB ."0:$j:0"] = $xss->parse($v);
							
							$j++;
							if ($j > $submit_vars['0:cols']) {
								$j=0;
							}
							//echo $xss->parse($value);
							$i++;
					}
					$submit_vars['0:rows'] = $i-1;
					//$submit_vars['0:rows'] = 0;

				} else {
                    //return new Jaws_Error(_t('PROPERTIES_ERROR_POST_NOT_FOUND'), _t('PROPERTIES_NAME'));
				}
								
			}

			// send editor HTML to syntacts
			$description = (isset($post['description'])) ? $post['description'] : '';
			$editor =& $GLOBALS['app']->LoadEditor('Properties', 'description', $description, false);
			$editor->TextArea->SetStyle('width: 100%;');
			$editor->SetWidth('490px');

			$submit_vars['HTTP_REFERER'] = $GLOBALS['app']->GetSiteURL();
			$submit_vars['JAWS_URL'] = $GLOBALS['app']->GetJawsURL() . '/';
			$submit_vars['DPATH'] = '';
			$submit_vars["ID"] = $id;
			
			if (!isset($page)) {
				if($snoopy->submit($submit_url,$submit_vars)) {
					//while(list($key,$val) = each($snoopy->headers))
						//echo $key.": ".$val."<br>\n";
					//echo "<p>\n";
					
					//$page = "<PRE>".htmlspecialchars($snoopy->results)."</PRE>\n";
					$page = $snoopy->results;
					$page = str_replace("__JAWS_EDITOR__", $editor->Get(), $page);
				} else {
					$page = _t('PROPERTIES_ERROR_ASPPAGE_NOT_RETRIEVED', $snoopy->error)."\n";
				}
			}

			$tpl->SetVariable('content', $page);
		}
		
        $tpl->ParseBlock('gadget_property');

        return $tpl->Get();

    }

    /**
     * We are on the B_form_post2 page
     *
     * @access public
     * @return string
     */
    function B_form_post2($account = false)
    {

		if ($account === false) {
			return $this->form_post();
		} else {
			return $this->form_post(true);
		}

    }

    /**
     * We are on the reservation dates page
     *
     * @access public
     * @return string
     */
    /*
	function C($account = false)
    {
		// Send us to the appropriate page
		require_once JAWS_PATH . 'include/Jaws/Header.php';
		if ($account == true) {
			Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'));
		} else {
			Jaws_Header::Location(BASE_SCRIPT . '?gadget=Properties&action=Admin');
		}
    }
	*/
    /**
     * We are on the C_form page
     *
     * @access public
     * @return string
     */
    /*
	function C_form($account = false)
    {
		// Send us to the appropriate page
		require_once JAWS_PATH . 'include/Jaws/Header.php';
		if ($account == true) {
			Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'));
		} else {
			Jaws_Header::Location(BASE_SCRIPT . '?gadget=Properties&action=Admin');
		}
    }
	*/
    /**
     * We are on the C_form_post page
     *
     * @access public
     * @return string
     */
	 /*
    function C_form_post($account = false)
    {
		// Send us to the appropriate page
		require_once JAWS_PATH . 'include/Jaws/Header.php';
		if ($account == true) {
			Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'));
		} else {
			Jaws_Header::Location(BASE_SCRIPT . '?gadget=Properties&action=Admin');
		}
    }
    */
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

        $tpl = new Jaws_Template('gadgets/Properties/templates/');
        $tpl->Load('admin.html');
        $tpl->SetBlock('settings');

		$request =& Jaws_Request::getInstance();
		$action = $request->get('action', 'get');
        $tpl->SetVariable('menubar', $this->MenuBar($action));

        $tpl->SetVariable('base_script', BASE_SCRIPT);
        $tpl->SetVariable('settings', _t('GLOBAL_SETTINGS'));

        $model = $GLOBALS['app']->LoadGadget('Properties', 'AdminModel');
        $showMapKey = $GLOBALS['app']->Registry->Get('/gadgets/Properties/showmap');
        $showCalendarKey = $GLOBALS['app']->Registry->Get('/gadgets/Properties/showcalendar');
        $randomizeKey = $GLOBALS['app']->Registry->Get('/gadgets/Properties/randomize');
        $postLimitKey = $GLOBALS['app']->Registry->Get('/gadgets/Properties/user_post_limit');
        $descLimitKey = $GLOBALS['app']->Registry->Get('/gadgets/Properties/user_desc_char_limit');
        $maskEmailKey = $GLOBALS['app']->Registry->Get('/gadgets/Properties/user_mask_owner_email');
        $minPriceKey = $GLOBALS['app']->Registry->Get('/gadgets/Properties/user_min_price');
        $maxPriceKey = $GLOBALS['app']->Registry->Get('/gadgets/Properties/user_max_price');
        $statusLimitKey = $GLOBALS['app']->Registry->Get('/gadgets/Properties/user_status_limit');

		$activeCombo =& Piwi::CreateWidget('Combo', 'showmap');
		$activeCombo->AddOption(_t('GLOBAL_YES'), 'Y');
		$activeCombo->AddOption(_t('GLOBAL_NO'), 'N');
		$activeCombo->SetDefault($showMapKey);
		$activeCombo->setTitle("Show Map of Properties");
        $tpl->SetVariable('key_label', "Show Map of Properties:");
        $tpl->SetVariable('key_entry', $activeCombo->Get());
		
		$calendarCombo =& Piwi::CreateWidget('Combo', 'showcalendar');
		$calendarCombo->AddOption(_t('GLOBAL_YES'), 'Y');
		$calendarCombo->AddOption(_t('GLOBAL_NO'), 'N');
		$calendarCombo->SetDefault($showCalendarKey);
		$calendarCombo->setTitle("Show Calendar");
        $tpl->SetVariable('keyc_label', "Show Calendar:");
        $tpl->SetVariable('keyc_entry', $calendarCombo->Get());
		
		$randomizeCombo =& Piwi::CreateWidget('Combo', 'randomize');
		$randomizeCombo->AddOption(_t('GLOBAL_YES'), 'Y');
		$randomizeCombo->AddOption(_t('GLOBAL_NO'), 'N');
		$randomizeCombo->SetDefault($randomizeKey);
		$randomizeCombo->setTitle(_t("PROPERTIES_RANDOMIZE"));
        $tpl->SetVariable('key0_label', _t("PROPERTIES_RANDOMIZE").":");
        $tpl->SetVariable('key0_entry', $randomizeCombo->Get());

        $key1Entry =& Piwi::CreateWidget('Entry', 'user_post_limit', $postLimitKey);
        $key1Entry->SetStyle('width: 100px;');
        $tpl->SetVariable('key1_label', 'Limit Property images that a user can add (Enter \'0\' for unlimited):');
		$tpl->SetVariable('key1_entry', $key1Entry->Get());
        
		$key2Entry =& Piwi::CreateWidget('Entry', 'user_desc_char_limit', $descLimitKey);
        $key2Entry->SetStyle('width: 100px;');
        $tpl->SetVariable('key2_label', 'Limit Property description field length for users (Number of characters. Enter \'0\' for unlimited):');
		$tpl->SetVariable('key2_entry', $key2Entry->Get());
		
		$active1Combo =& Piwi::CreateWidget('Combo', 'user_mask_owner_email');
		$active1Combo->AddOption(_t('GLOBAL_YES'), 'Y');
		$active1Combo->AddOption(_t('GLOBAL_NO'), 'N');
		$active1Combo->SetDefault($maskEmailKey);
		$active1Combo->setTitle("Use a \"no-reply\" e-mail address for all user Property Inquiries");
        $tpl->SetVariable('key3_label', "Use a \"no-reply\" e-mail address for all user Property Inquiries:");
        $tpl->SetVariable('key3_entry', $active1Combo->Get());

		$key4Entry =& Piwi::CreateWidget('Entry', 'user_min_price', $minPriceKey);
        $key4Entry->SetStyle('width: 100px;');
        $tpl->SetVariable('key4_label', 'Minimum Property Price that users can enter (in U.S. Dollars, Enter \'0\' for No Limit) (Example: 500000):');
		$tpl->SetVariable('key4_entry', $key4Entry->Get());
		
		$key5Entry =& Piwi::CreateWidget('Entry', 'user_max_price', $maxPriceKey);
        $key5Entry->SetStyle('width: 100px;');
        $tpl->SetVariable('key5_label', 'Maximum Property Price that users can enter (in U.S. Dollars, Enter \'0\' for No Limit) (Example: 10000000):');
		$tpl->SetVariable('key5_entry', $key5Entry->Get());

		include_once JAWS_PATH . 'include/Jaws/Widgets/FieldSet.php';
		// Property Status limits
		$gadgets_fieldset = new Jaws_Widgets_FieldSet(_t('PROPERTIES_SETTINGS_USER_STATUS_LIMIT'));
		$gadgets_fieldset->SetTitle('vertical');
		$gadgets_fieldset->SetStyle('margin-top: 50px; width: 300px; display: block; text-align: left;');

		$checks =& Piwi::CreateWidget('CheckButtons', 'user_status_limit','vertical');
		$checked = $GLOBALS['app']->Registry->Get('/gadgets/Properties/user_status_limit');
		$checked = explode(",",$checked);
		
		$checks->AddOption(_t('PROPERTIES_STATUS_FORSALE'), 'forsale', null, in_array('forsale', $checked));
		$checks->AddOption(_t('PROPERTIES_STATUS_FORRENT'), 'forrent', null, in_array('forrent', $checked));
		$checks->AddOption(_t('PROPERTIES_STATUS_FORLEASE'), 'forlease', null, in_array('forlease', $checked));
		$checks->AddOption(_t('PROPERTIES_STATUS_UNDERCONTRACT'), 'undercontract', null, in_array('undercontract', $checked));
		$checks->AddOption(_t('PROPERTIES_STATUS_SOLD'), 'sold', null, in_array('sold', $checked));
		$checks->AddOption(_t('PROPERTIES_STATUS_RENTED'), 'rented', null, in_array('rented', $checked));
		$checks->AddOption(_t('PROPERTIES_STATUS_LEASED'), 'leased', null, in_array('leased', $checked));
		
		$gadgets_fieldset->Add($checks);
		
		$tpl->SetVariable('status_limit', $gadgets_fieldset->Get());

		$saveButton =& Piwi::CreateWidget('Button', 'Save', _t('GLOBAL_SAVE'), STOCK_SAVE);
        $saveButton->AddEvent(ON_CLICK,
                             "javascript: saveSettings();");

        $tpl->SetVariable('save_button', $saveButton->Get());

        $tpl->ParseBlock('settings');
        return $tpl->Get();
		
		/*
		$request =& Jaws_Request::getInstance();
		$search = $request->get('search', 'get');
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

    /**
     * ShowEmbedWindow
     *
     * @access public
     * @return string
     */
    function ShowEmbedWindow()
    {
		$user_admin = $GLOBALS['app']->LoadGadget('Users', 'AdminHTML');
		return $user_admin->ShowEmbedWindow('Properties', 'OwnProperty');
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
			$GLOBALS['app']->Session->CheckPermission('Properties', 'default');
		} else {
			$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
			if (!$GLOBALS['app']->Session->GetPermission('Properties', 'default')) {
				if (!$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Properties', 'OwnProperty')) {
		            $GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
					$userHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
					return $userHTML->DefaultAction();
				}
			}
		}
		require_once JAWS_PATH . 'include/Jaws/Template.php';
        $tpl = new Jaws_Template('gadgets/Properties/templates/');
        $tpl->Load('QuickAddForm.html');
        $tpl->SetBlock('form');

		$request =& Jaws_Request::getInstance();
		$method = $request->get('method', 'get');
		if (empty($method)) {
			$method = 'AddProperty';
		}
		$form_content = '';
		switch($method) {
			case "AddPropertyParent": 
			case "UpdatePropertyParent": 
				$form_content = $this->form($account);
				break;
			case "AddGadget": 
			case "AddProperty": 
			case "UpdateProperty": 
				$form_content = $this->A_form($account);
				break;
			case "AddPost":
			case "EditPost":
				$form_content = $this->A_form2($account);
				break;
			case "AddPropertyAmenity":
			case "EditPropertyAmenity":
				$form_content = $this->B_form($account);
				break;
			case "AddAmenityType":
			case "EditAmenityType":
				$form_content = $this->B_form2($account);
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
        $tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . '/');
        $tpl->SetVariable('stub', (JAWS_SCRIPT == 'admin' ? 'PropertiesAdminAjax' : 'PropertiesAjax'));
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
