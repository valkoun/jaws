<?php
/**
 * Store Gadget Admin
 *
 * @category   GadgetAdmin
 * @package    Store
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class StoreAdminHTML extends Jaws_GadgetHTML
{
    /**
     * Constructor
     *
     * @access public
     */
    function StoreAdminHTML()
    {
        $this->Init('Store');
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
        $actions = array('Admin','Settings','ImportInventory','form','form_post','A','A_form','A_form_post','A_form2','A_form_post2','B','B_form','B_form_post','B2','B_form2','B_form_post2','C','C_form','C_form_post','D','D_form','D_form_post');
        if (!in_array($selected, $actions)) {
            $selected = 'Admin';
        }

        require_once JAWS_PATH . 'include/Jaws/Widgets/Menubar.php';
        $menubar =& new Jaws_Widgets_Menubar();
        if ($GLOBALS['app']->Session->GetPermission('Store', 'ManageProducts')) {
            $menubar->AddOption('Admin', _t('STORE_MENU_ADMIN'),
                                'admin.php?gadget=Store&amp;action=Admin', STOCK_OPEN);
			if (strtolower($selected) != "admin" && (strtolower($selected) == 'form' || strtolower($selected) == 'form_post')) {
				$menubar->AddOption($selected, _t('STORE_MENU_CATEGORY'),
	                                'javascript:void(0);', STOCK_EDIT);
			}
        }
        if ($GLOBALS['app']->Session->GetPermission('Store', 'default')) {
            $menubar->AddOption('A', _t('STORE_MENU_PRODUCTS'),
                                'admin.php?gadget=Store&amp;action=A', STOCK_BOOK);
			if (strtolower($selected) != "admin" && (strtolower($selected) == 'a_form' || strtolower($selected) == 'a_form_post')) {
				$menubar->AddOption($selected, _t('STORE_MENU_PRODUCT'),
	                                'javascript:void(0);', STOCK_EDIT);
			}
            if (strtolower($selected) != "admin" && (strtolower($selected) == 'a_form2' || strtolower($selected) == 'a_form_post2')) {
				$menubar->AddOption($selected, _t('STORE_MENU_POST'),
	                                'javascript:void(0);', STOCK_EDIT);
			}
			$menubar->AddOption('B2', _t('STORE_MENU_ATTRIBUTE'),
								'admin.php?gadget=Store&amp;action=B2', STOCK_INSERT_IMAGE);
			if (strtolower($selected) != "admin" && (strtolower($selected) == 'b' || strtolower($selected) == 'b_form' || strtolower($selected) == 'b_form_post' || strtolower($selected) == 'b_form2' || strtolower($selected) == 'b_form_post2')) {
				$menubar->AddOption($selected, _t('STORE_EDIT_ATTRIBUTES'),
	                                'javascript:void(0);', STOCK_EDIT);
			}
			$menubar->AddOption('C', _t('STORE_MENU_SALES'),
								'admin.php?gadget=Store&amp;action=C', STOCK_CALENDAR);
            if (strtolower($selected) != "admin" && (strtolower($selected) == 'c_form' || strtolower($selected) == 'c_form_post')) {
				$menubar->AddOption($selected, _t('STORE_MENU_SALE'),
	                                'javascript:void(0);', STOCK_EDIT);
			}
			$menubar->AddOption('D', _t('STORE_MENU_BRANDS'),
								'admin.php?gadget=Store&amp;action=D', STOCK_DOCUMENTS);
            if (strtolower($selected) != "admin" && (strtolower($selected) == 'd_form' || strtolower($selected) == 'd_form_post')) {
				$menubar->AddOption($selected, _t('STORE_MENU_BRAND'),
	                                'javascript:void(0);', STOCK_EDIT);
			}
		}
		if ($GLOBALS['app']->Session->GetPermission('Store', 'ManageProducts')) {
			$menubar->AddOption('Settings', _t('GLOBAL_SETTINGS'),
								'admin.php?gadget=Store&amp;action=Settings', STOCK_ALIGN_CENTER);
			$menubar->AddOption('ImportInventory', _t('STORE_MENU_IMPORTINVENTORY'),
								'admin.php?gadget=Store&amp;action=ImportInventory', STOCK_OPEN);
		}

 		$request =& Jaws_Request::getInstance();
		$id = $request->get('id', 'get');
		if (strtolower($selected) == "form" && empty($id)) {
		} else {
			if ($GLOBALS['app']->Session->GetPermission('Store', 'ManageProducts')) {
				$menubar->AddOption('Add', '',
									'admin.php?gadget=Store&amp;action=form', STOCK_ADD);
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
        $model = $GLOBALS['app']->LoadGadget('Store', 'AdminModel');
		$OwnerID = 0;
		if (BASE_SCRIPT == 'index.php') {
			$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');
		}
        $sql = 'SELECT COUNT([productparentid]) FROM [[productparent]] WHERE [productparentownerid] = '.$OwnerID;
        $res = $GLOBALS['db']->queryOne($sql);
        $total = (Jaws_Error::IsError($res) ? 0 : $res);

        $grid =& Piwi::CreateWidget('DataGrid', array());
        $grid->TotalRows($total);
        $grid->SetStyle('width: 100%;');
        $grid->SetID('products_datagrid');
        $grid->SetAction('next', 'javascript:nextProductValues();');
        $grid->SetAction('prev', 'javascript:previousProductValues();');
        $grid->SetAction('first', 'javascript:firstProductValues();');
        $grid->SetAction('last', 'javascript:lastProductValues();');
        //$grid->useMultipleSelection();
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_TITLE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', ''));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('STORE_PRODUCTS')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('STORE_ACTIVE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('STORE_LAST_UPDATE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_ACTIONS')));

        return $grid->Get();
    }

    /**
     * Builds the basic datagrid view
     *
     * @access  private
     * @return  string   XHTML of datagrid
     */
    function AttributeDataGrid($use_for_select = false)
    {
        $model = $GLOBALS['app']->LoadGadget('Store', 'AdminModel');
		$OwnerID = 0;
		if (BASE_SCRIPT == 'index.php') {
			$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');
		}
		$pages = $model->SearchAttributes('', '', null, $OwnerID);
        $total = (Jaws_Error::IsError($pages) ? 0 : count($pages));

        $grid =& Piwi::CreateWidget('DataGrid', array());
        $grid->TotalRows($total);
        $grid->SetStyle('width: 100%;');
        $grid->SetID('attributes_datagrid');
        $grid->setAction('prev', 'javascript:previousAttributeValues('.($use_for_select === true ? 'true' : '').');');
        $grid->setAction('next', 'javascript:nextAttributeValues('.($use_for_select === true ? 'true' : '').');');
        $grid->setAction('first', 'javascript:firstAttributeValues('.($use_for_select === true ? 'true' : '').');');
        $grid->setAction('last', 'javascript:lastAttributeValues('.($use_for_select === true ? 'true' : '').');');
		$grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_TITLE')));
		$grid->AddColumn(Piwi::CreateWidget('Column', _t('STORE_TYPE')));
		if ($use_for_select === true) {
			$grid->useMultipleSelection();
        } else {
			$grid->AddColumn(Piwi::CreateWidget('Column', _t('STORE_ACTIVE')));
		}
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('STORE_LAST_UPDATE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_ACTIONS')));

        return $grid->Get();
    }

    /**
     * Builds the basic datagrid view
     *
     * @access  private
     * @return  string   XHTML of datagrid
     */
    function AttributeTypesDataGrid()
    {
        $model = $GLOBALS['app']->LoadGadget('Store', 'AdminModel');
		$OwnerID = 0;
		if (BASE_SCRIPT == 'index.php') {
			$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');
		}
        $sql = 'SELECT COUNT([id]) FROM [[attribute_types]] WHERE [ownerid] = '.$OwnerID;
        $res = $GLOBALS['db']->queryOne($sql);
        $total = (Jaws_Error::IsError($res) ? 0 : $res);

        $grid =& Piwi::CreateWidget('DataGrid', array());
        $grid->TotalRows($total);
        $grid->SetStyle('width: 100%;');
        $grid->SetID('attribute_types_datagrid');
        $grid->setAction('next', 'javascript:nextAttributeTypeValues();');
        $grid->setAction('prev', 'javascript:previousAttributeTypeValues();');
        $grid->SetAction('first', 'javascript:firstAttributeTypeValues();');
        $grid->SetAction('last', 'javascript:lastAttributeTypeValues();');
        //$grid->useMultipleSelection();
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_TITLE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', ''));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('STORE_ACTIVE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('STORE_LAST_UPDATE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_ACTIONS')));

        return $grid->Get();
    }

    /**
     * Builds the basic datagrid view
     *
     * @access  private
     * @return  string   XHTML of datagrid
     */
    function SalesDataGrid($use_for_select = false)
    {
        $model = $GLOBALS['app']->LoadGadget('Store', 'AdminModel');
		$OwnerID = 0;
		if (BASE_SCRIPT == 'index.php') {
			$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');
		}
        $sql = 'SELECT COUNT([id]) FROM [[sales]] WHERE [ownerid] = '.$OwnerID;
        $res = $GLOBALS['db']->queryOne($sql);
        $total = (Jaws_Error::IsError($res) ? 0 : $res);

        $grid =& Piwi::CreateWidget('DataGrid', array());
        $grid->TotalRows($total);
        $grid->SetStyle('width: 100%;');
        $grid->SetID('sales_datagrid');
        $grid->setAction('next', 'javascript:nextSaleValues();');
        $grid->setAction('prev', 'javascript:previousSaleValues();');
        $grid->SetAction('first', 'javascript:firstSaleValues();');
        $grid->SetAction('last', 'javascript:lastSaleValues();');
        if ($use_for_select === true) {
			$grid->useMultipleSelection();
        }
		$grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_TITLE')));
        //$grid->AddColumn(Piwi::CreateWidget('Column', ''));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('STORE_ACTIVE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('STORE_STARTDATE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('STORE_ENDDATE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('STORE_SALE_AMOUNT')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_ACTIONS')));

        return $grid->Get();
    }

    /**
     * Builds the basic datagrid view
     *
     * @access  private
     * @return  string   XHTML of datagrid
     */
    function BrandsDataGrid()
    {
        $model = $GLOBALS['app']->LoadGadget('Store', 'AdminModel');
		$OwnerID = 0;
		if (BASE_SCRIPT == 'index.php') {
			$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');
		}
        $sql = 'SELECT COUNT([id]) FROM [[productbrand]] WHERE [ownerid] = '.$OwnerID;
        $res = $GLOBALS['db']->queryOne($sql);
        $total = (Jaws_Error::IsError($res) ? 0 : $res);

        $grid =& Piwi::CreateWidget('DataGrid', array());
        $grid->TotalRows($total);
        $grid->SetStyle('width: 100%;');
        $grid->SetID('brands_datagrid');
        $grid->setAction('next', 'javascript:nextBrandValues();');
        $grid->setAction('prev', 'javascript:previousBrandValues();');
        $grid->SetAction('first', 'javascript:firstBrandValues();');
        $grid->SetAction('last', 'javascript:lastBrandValues();');
        //$grid->useMultipleSelection();
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_TITLE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', ''));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('STORE_ACTIVE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('STORE_LAST_UPDATE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_ACTIONS')));

        return $grid->Get();
    }

    /**
     * Builds the templates datagrid view
     *
     * @access  private
     * @return  string   XHTML of datagrid
     */
    function TemplatesDataGrid($gadgetscope = 'product')
    {
        $model = $GLOBALS['app']->LoadGadget('Store', 'AdminModel');
		$OwnerID = 0;
		if (BASE_SCRIPT == 'index.php') {
			$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');
		}
        
		$res = $model->SearchTemplates($gadgetscope, '', null);
		$total = (Jaws_Error::IsError($res) ? 0 : count($res));
		
        $grid =& Piwi::CreateWidget('DataGrid', array());
        $grid->TotalRows(($total));
        $grid->SetStyle('width: 100%;');
        $grid->SetID('templates_datagrid');
        $grid->setAction('next', "javascript:nextTemplatesValues('".$gadgetscope."');");
        $grid->setAction('prev', "javascript:previousTemplatesValues('".$gadgetscope."');");
        $grid->setAction('first', "javascript:firstTemplatesValues('".$gadgetscope."');");
        $grid->setAction('last', "javascript:lastTemplatesValues('".$gadgetscope."');");
        $grid->useMultipleSelection();
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_TITLE')));
		$grid->AddColumn(Piwi::CreateWidget('Column', _t('STORE_LAST_UPDATE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_ACTIONS')));

        return $grid->Get();
    }

    /**
     * Builds the basic datagrid view
     *
     * @access  private
     * @return  string   XHTML of datagrid
     */
    function PostsDataGrid()
    {
        $model = $GLOBALS['app']->LoadGadget('Store', 'AdminModel');
		$OwnerID = 0;
		if (BASE_SCRIPT == 'index.php') {
			$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');
		}
        $sql = 'SELECT COUNT([id]) FROM [[product_posts]] WHERE [ownerid] = '.$OwnerID;
        $res = $GLOBALS['db']->queryOne($sql);
        $total = (Jaws_Error::IsError($res) ? 0 : $res);

        $grid =& Piwi::CreateWidget('DataGrid', array());
        $grid->TotalRows($total);
        $grid->SetStyle('width: 100%;');
        $grid->SetID('posts_datagrid');
        $grid->setAction('next', 'javascript:nextPostValues();');
        $grid->setAction('prev', 'javascript:previousPostValues();');
        $grid->setAction('first', 'javascript:firstPostValues();');
        $grid->setAction('last', 'javascript:lastPostValues();');
        //$grid->useMultipleSelection();
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('STORE_IMAGE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_TITLE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('STORE_ACTIVE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('STORE_LAST_UPDATE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_ACTIONS')));

        return $grid->Get();
    }
	
    /**
     * Returns an array with product parents found
     *
     * @access  public
     * @param   string  $status  Status of product parent(s) we want to display
     * @param   string  $search  Keyword (title/description) of parents we want to look for
     * @param   int     $limit   Data limit
     * @return  array   Data
     */
    function GetProductParents($status, $search, $limit, $OwnerID = 0)
    {
        $model = $GLOBALS['app']->LoadGadget('Store', 'AdminModel');
		$pages = $model->SearchProductParents($status, $search, $limit, $OwnerID);
        //$pages = $model->SearchProductParents($status, $search, $limit);
        if (Jaws_Error::IsError($pages)) {
            return array();
        }

        $data    = array();
		if (BASE_SCRIPT != 'index.php') {
			$edit_url    = BASE_SCRIPT . '?gadget=Store&amp;action=A&amp;id=';
			$attribute_url    = BASE_SCRIPT . '?gadget=Store&amp;action=B';
			$sales_url    = BASE_SCRIPT . '?gadget=Store&amp;action=C';
        } else {
			$edit_url    = 'index.php?gadget=Store&amp;action=account_A&amp;id=';
			$attribute_url    = 'index.php?gadget=Store&amp;action=account_B';
			$sales_url    = 'index.php?gadget=Store&amp;action=account_C';
		}
		$date = $GLOBALS['app']->loadDate();
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$ACL = $GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			

        foreach ($pages as $page) {
			$pageData = array();
			$pageData['title'] = ($page['productparentparent'] > 0 ? '&nbsp;&nbsp;-' : '').'<a href="'.$edit_url.$page['productparentid'].'">'.$page['productparentcategory_name'].'</a>';
			if (BASE_SCRIPT != 'index.php') {
				$pageData['furl']  = "<a href='javascript:void(0);' onclick='window.open(\"".$GLOBALS['app']->Map->GetURLFor('Store', 'Category', array('id' => $xss->filter($page['productparentfast_url'])))."\");'>View This Category</a>";
			}
			$number = $model->GetAllProductsOfParent($page['productparentid']);
			if (!Jaws_Error::IsError($number)) {
				$pageData['count'] = count($number);
			} else {
				$pageData['count'] = 0;
			}
			if ($page['productparentactive'] == 'Y') {
				$pageData['active'] = _t('STORE_PUBLISHED');
			} else {
				$pageData['active'] = _t('STORE_DRAFT');
			}
			$pageData['date']  = $date->Format($page['productparentupdated']);
			$actions = '';
			if ($account === false) {
				$stats =& Piwi::CreateWidget('Link', 'STATS',
					BASE_SCRIPT.'?gadget=ControlPanel&action=Statistics&fusegadget=Store&fuseaction=Category&fuselinkid='.$page['productparentid']);
				$actions.= $stats->Get().'&nbsp;';
			}
			if ($this->GetPermission('ManageProductParents')) {
				if (BASE_SCRIPT != 'index.php') {
					$link =& Piwi::CreateWidget('Link', strtoupper(_t('STORE_PRODUCTS')),
												$edit_url.$page['productparentid']/*,
												STOCK_BOOK*/);
				} else {
					$link =& Piwi::CreateWidget('Link', strtoupper(_t('STORE_PRODUCTS')),
												"javascript:window.open('".$edit_url.$page['productparentid']."');"/*,
												STOCK_BOOK*/);
				}
				$actions.= $link->Get().'&nbsp;';
			} else {
				if ($ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Store', 'OwnProduct')) {
					$link =& Piwi::CreateWidget('Link', strtoupper(_t('STORE_PRODUCTS')),
												"javascript:window.open('".$edit_url.$page['productparentid']."');"/*,
												STOCK_BOOK*/);
					$actions.= $link->Get().'&nbsp;';
				}
			}

			if ($this->GetPermission('ManageProductParents')) {
				$link =& Piwi::CreateWidget('Link', strtoupper(_t('GLOBAL_DELETE')),
											"javascript: if (confirm('"._t('GLOBAL_CONFIRM_DELETE', _t('STORE_PRODUCTPARENT'))."')) ".
											"deleteProductParent('".$page['productparentid']."');"/*,
											"images/ICON_delete2.gif"*/);
				$actions.= $link->Get().'&nbsp;';
			} else {
				if ($ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Store', 'OwnProduct')) {
					$link =& Piwi::CreateWidget('Link', strtoupper(_t('GLOBAL_DELETE')),
												"javascript: if (confirm('"._t('GLOBAL_CONFIRM_DELETE', _t('STORE_PRODUCTPARENT'))."')) ".
												"deleteProductParent('".$page['productparentid']."');"/*,
												"images/ICON_delete2.gif"*/);
					$actions.= $link->Get().'&nbsp;';
				}
			}
			$pageData['actions'] = $actions;
			$pageData['__KEY__'] = $page['productparentid'];
			$data[] = $pageData;
		}
        return $data;
    }

    /**
     * Returns an array with product attributes found
     *
     * @access  public
     * @param   string  $status  Status of attribute(s) we want to display
     * @param   string  $search  Keyword (title/description) of attributes we want to look for
     * @param   int     $limit   Data limit
     * @return  array   Data
     */
    function GetAttributes($status, $search, $limit, $OwnerID = 0, $use_for_select = false)
    {
        $model = $GLOBALS['app']->LoadGadget('Store', 'Model');
        $adminmodel = $GLOBALS['app']->LoadGadget('Store', 'AdminModel');

        $data    = array();
		if (BASE_SCRIPT != 'index.php') {
			$edit_url    = BASE_SCRIPT . '?gadget=Store&amp;action=B_form&amp;id=';
        } else {
			$edit_url    = 'index.php?gadget=Store&amp;action=account_B_form&amp;id=';
		}
		$date = $GLOBALS['app']->loadDate();
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$ACL = $GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			

		$pages = $adminmodel->SearchAttributes($search, $status, $limit, $OwnerID);
		if (Jaws_Error::IsError($pages)) {
			return array();
		}
		foreach ($pages as $page) {
			if (($use_for_select === true && $page['active'] == 'Y') || $use_for_select === false) {
				// get attribute type by it's ID
				$pageData = array();
				if ($page['typeid'] == 'template') {
					$type = 'Template';
					$type_url = 'javascript:void(0);';
					$pageData['title'] = $page['feature'];
				} else {
					$typeID = $model->GetAttributeType($page['typeid']);
					if (!Jaws_Error::IsError($typeID)) {
						$type = $typeID['title'];
						if (BASE_SCRIPT != 'index.php') {
							$type_url    = BASE_SCRIPT . '?gadget=Store&amp;action=B_form2&amp;id='.$typeID['id'];
						} else {
							$type_url    = 'index.php?gadget=Store&amp;action=account_B_form2&amp;id='.$typeID['id'];
						}
					} else {
						$type = '';
						$type_url = 'javascript:void(0);';
					}
					$pageData['title'] = '<a href="'.$edit_url.$page['id'].'">'.$page['feature'].'</a>';
				}
				$pageData['type'] = '<a href="'.$type_url.'">'.$type.'</a>';
				/*
				if (BASE_SCRIPT != 'index.php') {
					$pageData['furl']  = '<a href="'.$GLOBALS['app']->Map->GetURLFor('Store', 'Attribute', array('id' => str_replace(' ', '--', $xss->filter($page['feature'])))).'">View Products</a>';
				}
				*/
				if ($use_for_select === false) {
					if ($page['active'] == 'Y') {
						$pageData['active'] = _t('STORE_PUBLISHED');
					} else {
						$pageData['active'] = _t('STORE_DRAFT');
					}
				}
				$pageData['date']  = $date->Format($page['updated']);
				$actions = '';
				
				if ($page['typeid'] != 'template') {
					if ($this->GetPermission('ManageProducts')) {
						if (BASE_SCRIPT != 'index.php') {
							$link =& Piwi::CreateWidget('Link', _t('STORE_EDIT_ATTRIBUTE'),
														$edit_url.$page['id'],
														STOCK_EDIT);
						} else {
							$link =& Piwi::CreateWidget('Link', _t('STORE_EDIT_ATTRIBUTE'),
														"javascript:window.open('".$edit_url.$page['id']."');",
														STOCK_EDIT);
						}
						$actions.= $link->Get().'&nbsp;';
					}

					if ($this->GetPermission('ManageProducts')) {
						$link =& Piwi::CreateWidget('Link', _t('GLOBAL_DELETE'),
													"javascript: if (confirm('"._t('GLOBAL_CONFIRM_DELETE', _t('STORE_ATTRIBUTE'))."')) ".
													"deleteAttribute('".$page['id']."');",
													"images/ICON_delete2.gif");
						$actions.= $link->Get().'&nbsp;';
					} else {
						if (
							$ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), 
								$GLOBALS['app']->Session->GetAttribute('groups'), 'Store', 'OwnProduct')
						) {
							$link =& Piwi::CreateWidget('Link', _t('GLOBAL_DELETE'),
														"javascript: if (confirm('"._t('GLOBAL_CONFIRM_DELETE', _t('STORE_ATTRIBUTE'))."')) ".
														"deleteAttribute('".$page['id']."');",
														"images/ICON_delete2.gif");
							$actions.= $link->Get().'&nbsp;';
						}
					}
				}
				$pageData['actions'] = $actions;
				$pageData['__KEY__'] = $page['id'];
				$data[] = $pageData;
			}
		}
        return $data;
    }

    /**
     * Returns an array with product attributes found
     *
     * @access  public
     * @param   string  $status  Status of attribute type(s) we want to display
     * @param   string  $search  Keyword (title/description) of attributes we want to look for
     * @param   int     $limit   Data limit
     * @return  array   Data
     */
    function GetAttributeTypes($status, $search, $limit, $OwnerID = 0)
    {
        $model = $GLOBALS['app']->LoadGadget('Store', 'AdminModel');
		$pages = $model->SearchAttributeTypes($status, $search, $limit, $OwnerID);
        if (Jaws_Error::IsError($pages)) {
            return array();
        }

        $data    = array();
		if (BASE_SCRIPT != 'index.php') {
			$edit_url    = BASE_SCRIPT . '?gadget=Store&amp;action=B_form2&amp;id=';
        } else {
			$edit_url    = 'index.php?gadget=Store&amp;action=account_B_form2&amp;id=';
		}
		$date = $GLOBALS['app']->loadDate();
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$ACL = $GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			

        foreach ($pages as $page) {
			$pageData = array();
			$pageData['title'] = '<a href="'.$edit_url.$page['id'].'">'.$page['title'].'</a>';
			$pageData['furl']  = '';

			if ($page['active'] == 'Y') {
				$pageData['active'] = _t('STORE_PUBLISHED');
			} else {
				$pageData['active'] = _t('STORE_DRAFT');
			}
			$pageData['date']  = $date->Format($page['updated']);
			$actions = '';
			if ($this->GetPermission('ManageProducts')) {
				if (BASE_SCRIPT != 'index.php') {
					$link =& Piwi::CreateWidget('Link', _t('STORE_EDIT_ATTRIBUTETYPE'),
												$edit_url.$page['id'],
												STOCK_EDIT);
				} else {
					$link =& Piwi::CreateWidget('Link', _t('STORE_EDIT_ATTRIBUTETYPE'),
												"javascript:window.open('".$edit_url.$page['id']."');",
												STOCK_EDIT);
				}
				$actions.= $link->Get().'&nbsp;';
			}

			if ($this->GetPermission('ManageProducts')) {
				$link =& Piwi::CreateWidget('Link', _t('GLOBAL_DELETE'),
											"javascript: if (confirm('"._t('GLOBAL_CONFIRM_DELETE', _t('STORE_ATTRIBUTE_TYPE'))."')) ".
											"deleteAttributeType('".$page['id']."');",
											"images/ICON_delete2.gif");
				$actions.= $link->Get().'&nbsp;';
			} else {
				if ($ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Store', 'OwnProduct')) {
					$link =& Piwi::CreateWidget('Link', _t('GLOBAL_DELETE'),
												"javascript: if (confirm('"._t('GLOBAL_CONFIRM_DELETE', _t('STORE_ATTRIBUTE_TYPE'))."')) ".
												"deleteAttributeType('".$page['id']."');",
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
     * Returns an array with product sales found
     *
     * @access  public
     * @param   string  $status  Status of attribute(s) we want to display
     * @param   string  $search  Keyword (title/description) of attributes we want to look for
     * @param   int     $limit   Data limit
     * @return  array   Data
     */
    function GetSales($status, $search, $limit, $OwnerID = 0)
    {
        $model = $GLOBALS['app']->LoadGadget('Store', 'Model');
        $adminmodel = $GLOBALS['app']->LoadGadget('Store', 'AdminModel');
		$pages = $adminmodel->SearchSales($search, $status, $limit, $OwnerID);
        if (Jaws_Error::IsError($pages)) {
            return array();
        }

        $data    = array();
		if (BASE_SCRIPT != 'index.php') {
			$edit_url    = BASE_SCRIPT . '?gadget=Store&amp;action=C_form&amp;id=';
        } else {
			$edit_url    = 'index.php?gadget=Store&amp;action=account_C_form&amp;id=';
		}
		$date = $GLOBALS['app']->loadDate();
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$ACL = $GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			

        foreach ($pages as $page) {
			$pageData = array();
			$pageData['title'] = '<a href="'.$edit_url.$page['id'].'">'.$page['title'].'</a>';
			//if (BASE_SCRIPT != 'index.php') {
			//	$pageData['furl']  = '<a href="'.$GLOBALS['app']->Map->GetURLFor('Store', 'Sale', array('id' => str_replace(' ', '--', $xss->filter($page['title'])))).'">View Products</a>';
			//}
			if ($page['active'] == 'Y') {
				$pageData['active'] = _t('STORE_PUBLISHED');
			} else {
				$pageData['active'] = _t('STORE_DRAFT');
			}
			$pageData['startdate']  = $date->Format($page['startdate']);
			$pageData['enddate']  = $date->Format($page['enddate']);
			$sale_amount = '$0.00 Off';
			if ($page['discount_amount'] > 0) {
				$sale_amount = '$'.number_format($page['discount_amount'], 2, '.', ',').' Off'.(!empty($sale['coupon_code']) ? ' Order' : ' Product');
			} else if ($page['discount_percent'] > 0) {
				$sale_amount = number_format($page['discount_percent'], 2, '.', ',').'% Off'.(!empty($sale['coupon_code']) ? ' Order' : ' Product');
			} else if ($page['discount_newprice'] > 0) {
				$sale_amount = (!empty($page['coupon_code']) ? 'Makes Order: ' : 'Makes Product: ').'$'.number_format($page['discount_newprice'], 2, '.', ',');
			}
			$pageData['amount']  = $sale_amount;
			$actions = '';
			if ($this->GetPermission('ManageProducts')) {
				if (BASE_SCRIPT != 'index.php') {
					$link =& Piwi::CreateWidget('Link', _t('STORE_EDIT_SALE'),
												$edit_url.$page['id'],
												STOCK_EDIT);
				} else {
					$link =& Piwi::CreateWidget('Link', _t('STORE_EDIT_SALE'),
												"javascript:window.open('".$edit_url.$page['id']."');",
												STOCK_EDIT);
				}
				$actions.= $link->Get().'&nbsp;';
			}

			if ($this->GetPermission('ManageProducts')) {
				$link =& Piwi::CreateWidget('Link', _t('GLOBAL_DELETE'),
											"javascript: if (confirm('"._t('GLOBAL_CONFIRM_DELETE', _t('STORE_SALE'))."')) ".
											"deleteSale('".$page['id']."');",
											"images/ICON_delete2.gif");
				$actions.= $link->Get().'&nbsp;';
			} else {
				if ($ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Store', 'OwnProduct')) {
					$link =& Piwi::CreateWidget('Link', _t('GLOBAL_DELETE'),
												"javascript: if (confirm('"._t('GLOBAL_CONFIRM_DELETE', _t('STORE_SALE'))."')) ".
												"deleteSale('".$page['id']."');",
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
     * Returns an array with product sales found
     *
     * @access  public
     * @param   string  $status  Status of attribute(s) we want to display
     * @param   string  $search  Keyword (title/description) of attributes we want to look for
     * @param   int     $limit   Data limit
     * @return  array   Data
     */
    function GetBrands($status, $search, $limit, $OwnerID = 0)
    {
        $model = $GLOBALS['app']->LoadGadget('Store', 'Model');
        $adminmodel = $GLOBALS['app']->LoadGadget('Store', 'AdminModel');
		$pages = $adminmodel->SearchBrands($search, $status, $limit, $OwnerID);
        if (Jaws_Error::IsError($pages)) {
            return array();
        }

        $data    = array();
		if (BASE_SCRIPT != 'index.php') {
			$edit_url    = BASE_SCRIPT . '?gadget=Store&amp;action=D_form&amp;id=';
        } else {
			$edit_url    = 'index.php?gadget=Store&amp;action=account_D_form&amp;id=';
		}
		$date = $GLOBALS['app']->loadDate();
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$ACL = $GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			

        foreach ($pages as $page) {
			$pageData = array();
			$pageData['title'] = '<a href="'.$edit_url.$page['id'].'">'.$page['title'].'</a>';
			if (BASE_SCRIPT != 'index.php') {
				$pageData['furl']  = '<a href="'.$GLOBALS['app']->Map->GetURLFor('Store', 'Brand', array('id' => str_replace(' ', '--', $xss->filter($page['title'])))).'">View Products</a>';
			}
			if ($page['active'] == 'Y') {
				$pageData['active'] = _t('STORE_PUBLISHED');
			} else {
				$pageData['active'] = _t('STORE_DRAFT');
			}
			$pageData['date']  = $date->Format($page['updated']);
			$actions = '';
			if ($this->GetPermission('ManageProducts')) {
				if (BASE_SCRIPT != 'index.php') {
					$link =& Piwi::CreateWidget('Link', _t('STORE_EDIT_BRAND'),
												$edit_url.$page['id'],
												STOCK_EDIT);
				} else {
					$link =& Piwi::CreateWidget('Link', _t('STORE_EDIT_BRAND'),
												"javascript:window.open('".$edit_url.$page['id']."');",
												STOCK_EDIT);
				}
				$actions.= $link->Get().'&nbsp;';
			}

			if ($this->GetPermission('ManageProducts')) {
				$link =& Piwi::CreateWidget('Link', _t('GLOBAL_DELETE'),
											"javascript: if (confirm('"._t('GLOBAL_CONFIRM_DELETE', _t('STORE_BRAND'))."')) ".
											"deleteBrand('".$page['id']."');",
											"images/ICON_delete2.gif");
				$actions.= $link->Get().'&nbsp;';
			} else {
				if ($ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Store', 'OwnProduct')) {
					$link =& Piwi::CreateWidget('Link', _t('GLOBAL_DELETE'),
												"javascript: if (confirm('"._t('GLOBAL_CONFIRM_DELETE', _t('STORE_BRAND'))."')) ".
												"deleteBrand('".$page['id']."');",
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
     * Returns an array with pages found
     *
     * @access  public
     * @param   string  $search  Keyword (title/description) of pages we want to look for
     * @param   int     $limit   Data limit
     * @return  array   Data
     */
    function GetTemplates($gadgetscope = 'product', $search, $limit, $OwnerID = null)
    {
		$model = $GLOBALS['app']->LoadGadget('Store', 'AdminModel');
		$OwnerID = 0;
		if (BASE_SCRIPT == 'index.php') {
			$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');
		}
		$pages = $model->SearchTemplates($gadgetscope, $search, $limit);
		if (Jaws_Error::IsError($pages)) {
            return array();
        }
        $data = array();
		$date = $GLOBALS['app']->loadDate();
		$ACL = $GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
        foreach ($pages as $page) {
			$pageData = array();
			$pageData['filename'] = (strlen($page['filename']) > 30 ? substr($page['filename'], 0, 30).'...' : $page['filename']);
			$pageData['date']  = $date->Format($page['date']);
			$actions = '';

			if ($this->GetPermission('ManageProducts')) {
				$link =& Piwi::CreateWidget('Link', _t('GLOBAL_DELETE'),
											"javascript: if (confirm('"._t('GLOBAL_CONFIRM_DELETE', _t('GLOBAL_TEMPLATE'))."')) ".
											"deleteTemplate('".$page['fullpath']."');");
				$actions .= $link->Get().'&nbsp;';
			}
			$pageData['actions'] = $actions;
			$key = $page['fullpath'];
			if (substr(strtolower($key), 0, strlen(JAWS_DATA)) == strtolower(JAWS_DATA)) {
				$key = substr($key, strlen(JAWS_DATA));
			}
			$pageData['__KEY__'] = $key;
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
			Jaws_Header::Location(BASE_SCRIPT . '?gadget=Store&action=A');
		}
		
		if ($account === false) {
			$GLOBALS['app']->Session->CheckPermission('Store', 'default');
		} else {
			$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
			if (!$GLOBALS['app']->Session->GetPermission('Store', 'default')) {
				if (!$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Store', 'OwnProduct')) {
		            //$GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
					return "Please log-in.";
				}
			}
		}
        $tpl = new Jaws_Template('gadgets/Store/templates/');
        $tpl->Load('admin.html');
        $tpl->SetBlock('products_admin');
        

		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=CustomPage&action=account_SetGBRoot');
		if ($account === false) {
	        $GLOBALS['app']->Layout->AddScriptLink(BASE_SCRIPT . '?gadget=Store&amp;action=Ajax&amp;client=all&amp;stub=StoreAdminAjax');
	        $GLOBALS['app']->Layout->AddScriptLink(BASE_SCRIPT . '?gadget=Store&amp;action=AjaxCommonFiles');
	        $GLOBALS['app']->Layout->AddScriptLink('gadgets/Store/resources/script.js');
			$GLOBALS['app']->Layout->AddScriptLink('libraries/js/admin.js');
			$tpl->SetVariable('menubar', $this->MenuBar($action));
			$account_prefix = '';
			$base_url = BASE_SCRIPT;
		} else {
	        $GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=Store&amp;action=Ajax&amp;client=all&amp;stub=StoreAjax');
	        $GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=Store&amp;action=AjaxCommonFiles');
	        $GLOBALS['app']->Layout->AddScriptLink('gadgets/Store/resources/client_script.js');
			$tpl->SetVariable('menubar', '');
			$account_prefix = 'account_';
			$base_url = 'index.php';
		}
        
		$tpl->SetVariable('account', $account_prefix);
		$tpl->SetVariable('base_script', $base_url);

        $tpl->SetVariable('grid', $this->DataGrid());

        $toolBar   =& Piwi::CreateWidget('HBox');

        $deleteAll =& Piwi::CreateWidget('Button', 'deleteAllProductParents',
                                         _t('GLOBAL_DELETE'),
                                         STOCK_DELETE);
        $deleteAll->AddEvent(ON_CLICK,
                             "javascript: massiveDelete('"._t('STORE_CONFIRM_MASIVE_DELETE_PRODUCTPARENT')."');");

        $toolBar->Add($deleteAll);

        $tpl->SetVariable('tools', $toolBar->Get());
                
		if ($account === false) {
	        //Status filter
	        $status = '';
	        $statusCombo =& Piwi::CreateWidget('Combo', 'productparent_status');
	        $statusCombo->setId('productparent_status');
	        $statusCombo->AddOption('&nbsp;', '');
	        $statusCombo->AddOption(_t('STORE_PUBLISHED'), 'Y');
	        $statusCombo->AddOption(_t('STORE_DRAFT'), 'N');
	        $statusCombo->SetDefault($status);
	        $statusCombo->AddEvent(ON_CHANGE, 'javascript: searchProductParent();');
	        $tpl->SetVariable('status', _t('STORE_ACTIVE').':');
	        $tpl->SetVariable('status_field', $statusCombo->Get());
		} else {
	        $searchEntry =& Piwi::CreateWidget('HiddenEntry', 'status', '');
	        $tpl->SetVariable('status_field', $searchEntry->Get());
		}

        // Free text search
        $searchButton =& Piwi::CreateWidget('Button', 'searchButton', _t('GLOBAL_SEARCH'), STOCK_SEARCH);
        $searchButton->AddEvent(ON_CLICK, 'javascript: searchProductParent();');
        $tpl->SetVariable('search', $searchButton->Get());

        $search = '';
        $searchEntry =& Piwi::CreateWidget('Entry', 'productparent_search', $search);
        $searchEntry->SetStyle('zwidth: 100%;');
        $tpl->SetVariable('search_field', $searchEntry->Get());

        $tpl->SetVariable('entries', $this->Datagrid());

		// Add button is added by HTML->GetUserAccountControls
		if ($account === false) {
	        $addPage =& Piwi::CreateWidget('Button', 'add_productparent', _t('STORE_ADD_PRODUCTPARENT'), STOCK_ADD);
			$addPage->AddEvent(ON_CLICK, "javascript: window.location = '".$base_url."?gadget=Store&amp;action=".$account_prefix."form';");
	        $tpl->SetVariable('add_productparent', $addPage->Get());
		} else {
			//$addPage->AddEvent(ON_CLICK, "javascript: window.open('".$base_url."?gadget=CustomPage&amp;action=".$account_prefix."form','','scrollbars=1,menubar=0,toolbar=0,location=0,status=1');");
	        $tpl->SetVariable('add_productparent', '');
		}

        $tpl->ParseBlock('products_admin');

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
			$GLOBALS['app']->Session->CheckPermission('Store', 'default');
		} else {
			$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
			if (!$GLOBALS['app']->Session->GetPermission('Store', 'default')) {
				if (!$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Store', 'OwnProduct')) {
		            $GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
					$userHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
					return $userHTML->DefaultAction();
				}
			}
		}

		// document dependencies
		
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$model = $GLOBALS['app']->LoadGadget('Store', 'Model');
		$request =& Jaws_Request::getInstance();
		$gather = array('action', 'id');
		$get = $request->get($gather, 'get');

		// initialize template
		$tpl =& new Jaws_Template('gadgets/Store/templates/');
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

        $tpl->SetBlock('gadget_product');

		// account differences
		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=CustomPage&action=account_SetGBRoot');
		if ($account === false) {
			$this->AjaxMe('script.js');
			$tpl->SetVariable('menubar', $this->MenuBar($get['action']));
			$GLOBALS['app']->Layout->AddScriptLink('libraries/js/admin.js');			
			$submit_vars['ACTIONPREFIX'] = "";
			$submit_vars['CLOSE_BUTTON'] = "location.href='" . BASE_SCRIPT . "?gadget=Store&amp;action=Admin';";
			$OwnerID = 0;
			$base_url = BASE_SCRIPT;
		} else {
			$tpl->SetVariable('menubar', '');
			$this->AjaxMe('client_script.js');
			$submit_vars['ACTIONPREFIX'] = "account_";
			$submit_vars['CLOSE_BUTTON'] = "if (window.opener && !window.opener.closed) {window.opener.location.reload();window.close();};";
			$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');
			$base_url = 'index.php';
		}
		$syntactsUrl = $GLOBALS['app']->getSyntactsAdminHTMLUrl('Store/admin_Store_form');
		$tpl->SetVariable('workarea-style', "style=\"float: left; margin-top: 30px;\" ");

		// syntacts page
		if ($syntactsUrl) {
			// snoopy
			include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
			$snoopy = new Snoopy('Store');
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
		        $stpl->SetBlock('form');
				if (!empty($get['id'])) {
					// send page records
					$pageInfo = $model->GetProductParent($get['id']);
					if (!Jaws_Error::IsError($pageInfo) && ($GLOBALS['app']->Session->GetPermission('Store', 'ManageProducts') || $pageInfo['productparentownerid'] == $OwnerID)) {
						$submit_vars['SUBMIT_BUTTON'] = "<input type='Submit' name='Submit' value='Update'>";
					} else {
						//$error = _t('STORE_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403'));												
						return new Jaws_Error(_t('STORE_ERROR_PRODUCTPARENT_NOT_FOUND'), _t('STORE_NAME'));
					}
				} else {
					$submit_vars['SUBMIT_BUTTON'] = "<input type='Submit' name='Submit' value='Add'>";
				}

				// send requesting URL to syntacts
				$stpl->SetVariable('HTTP_REFERER', $GLOBALS['app']->GetSiteURL());
				$stpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . '/');
				//$stpl->SetVariable('DPATH', JAWS_DPATH);
				$stpl->SetVariable('actionprefix', $submit_vars['ACTIONPREFIX']);
				$stpl->SetVariable('gadget', 'Store');
				$stpl->SetVariable('CLOSE_BUTTON', $submit_vars['CLOSE_BUTTON']);
				$stpl->SetVariable('SUBMIT_BUTTON', $submit_vars['SUBMIT_BUTTON']);
				$stpl->SetVariable('controller', $base_url);
				
				// Get Help documentation
				$help_url = $GLOBALS['app']->getSyntactsAdminHTMLUrl("Store/admin_Store_form_help", 'txt');
				$snoopy = new Snoopy('Store');
		
				if($snoopy->fetch($help_url)) {
					$helpContent = Jaws_Utils::split2D($snoopy->results);
				}
								
				// Hidden elements
				$ID = (isset($pageInfo['productparentid'])) ? $pageInfo['productparentid'] : '';
				$idHidden =& Piwi::CreateWidget('HiddenEntry', 'productparentID', $ID);
		        $form_content .= $idHidden->Get()."\n";

				// send highest sort_order
				$sort_order = 0;
				if (isset($pageInfo['productparentsort_order'])) {
					$sort_order = $pageInfo['productparentsort_order'];
				} else {
					$sql = "SELECT MAX([productparentsort_order]) FROM [[productparent]] ORDER BY [productparentsort_order] DESC";
					$max = $GLOBALS['db']->queryOne($sql);
					if (Jaws_Error::IsError($max)) {
						return $max;
					} else if ($max >= 0) {
						$sort_order = $max+1;
					}
				}
				
				$sort_orderHidden =& Piwi::CreateWidget('HiddenEntry', 'productparentsort_order', $sort_order);
		        $form_content .= $sort_orderHidden->Get()."\n";

				$fuseaction = (isset($pageInfo['productparentid'])) ? 'EditProductParent' : 'AddProductParent';
				$fuseactionHidden =& Piwi::CreateWidget('HiddenEntry', 'fuseaction', $fuseaction);
		        $form_content .= $fuseactionHidden->Get()."\n";

				$featured = (isset($pageInfo['productparentfeatured'])) ? $pageInfo['productparentfeatured'] : 'N';
				$featuredHidden =& Piwi::CreateWidget('HiddenEntry', 'productparentFeatured', $featured);
		        $form_content .= $featuredHidden->Get()."\n";
								
				if ($account === false) {
					// Active
					$helpString = '';
					foreach($helpContent as $help) {		            
						if ($help[0] == _t('STORE_PUBLISHED')) {
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
					$active = (isset($pageInfo['productparentactive'])) ? $pageInfo['productparentactive'] : 'Y';
					$activeCombo =& Piwi::CreateWidget('Combo', 'productparentActive');
					$activeCombo->AddOption(_t('STORE_PUBLISHED'), 'Y');
					$activeCombo->AddOption(_t('STORE_DRAFT'), 'N');
					$activeCombo->SetDefault($active);
					$activeCombo->setTitle(_t('STORE_PUBLISHED'));
					$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"productparentActive\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$activeCombo->Get()."</td></tr>";
				} else {
					$activeHidden =& Piwi::CreateWidget('HiddenEntry', 'productparentActive', 'N');
					$form_content .= $activeHidden->Get()."\n";
				}
					
				// Parent
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('STORE_PARENT')) {
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
				$post_url = (isset($pageInfo['productparentparent']) && (int)$parent['productparentparent'] > 0 ? (int)$pageInfo['productparentparent'] : 0);
				if ($post_url == 0) {	
					$GLOBALS['db']->dbc->loadModule('Function', null, true);
					$url = $GLOBALS['db']->dbc->function->lower('[url]');
					$url1 = $GLOBALS['app']->Map->GetURLFor('Store', 'Category', array('id' => (int)$parent['productparentfast_url']));
																	
					$sql  = "SELECT [id] FROM [[menus]] WHERE $url LIKE {url}";
					$oid = $GLOBALS['db']->queryRow($sql, array('url' => '%'.strtolower($url1)));
					if (!Jaws_Error::IsError($oid) && isset($oid['gid']) && !empty($oid['gid'])) {
						$post_url = (int)$oid['gid'];
					}
				}
				$urlListCombo =& Piwi::CreateWidget('Combo', 'productparentParent');
				$urlListCombo->setID('productparentParent');

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
				$urlListCombo->setTitle(_t('STORE_PARENT'));
				$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"pid\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$urlListCombo->Get()."</td></tr>";

				// Title
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('STORE_TITLE')) {
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
				$title = (isset($pageInfo['productparentcategory_name'])) ? $pageInfo['productparentcategory_name'] : '';
				$titleEntry =& Piwi::CreateWidget('Entry', 'productparentCategory_Name', $title);
				$titleEntry->SetTitle(_t('STORE_TITLE'));
				$titleEntry->SetStyle('direction: ltr; width: 300px;');
				$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"productparentCategory_Name\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$titleEntry->Get()."</td></tr>";

				// Description
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('STORE_DESCRIPTIONFIELD')) {
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
				$content = (isset($pageInfo['productparentdescription'])) ? $pageInfo['productparentdescription'] : '';
				$editor =& $GLOBALS['app']->LoadEditor('Store', 'productparentDescription', $content, false);
				$editor->TextArea->SetStyle('width: 100%;');
				//$editor->SetWidth('100%');
				$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"productparentDescription\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$editor->Get()."</td></tr>";

				// Image
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('STORE_IMAGE')) {
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
				$image = (isset($pageInfo['productparentimage'])) ? $pageInfo['productparentimage'] : '';
				$image_src = $GLOBALS['app']->getDataURL() . 'files'.$xss->filter($pageInfo['productparentimage']);
				$image_preview = '';
				if ($image != '' && file_exists($image_src)) { 
					$image_preview .= "<br /><img border=\"0\" src=\"".$image_src."\" width=\"80\"".(strtolower(substr($image, -3)) == 'gif' || strtolower(substr($image, -3)) == 'png' || strtolower(substr($image, -3)) == 'bmp' ? ' height="80"' : '')." align=\"left\" style=\"padding: 5px visibility: visible;\" id=\"main_image_src\"><br /><b><a id=\"imageDelete\" href=\"javascript:void(0);\" onclick=\"document.getElementById('main_image_src').style.visibility = 'hidden'; document.getElementById('productparentImage').value = '';\">Delete</a></b>";
				}
				$form_content .= '<tr style="display: '.($image != "" || !isset($pageInfo['productparentid']) ? 'none;' : ';').'" id="imageButton">';
				$form_content .= '<td class="syntacts-form-row" valign="top"><input TYPE="button" VALUE="Insert Media" onClick="toggleNo(\'imageButton\'); toggleYes(\'imageRow\'); toggleYes(\'imageInfo\'); toggleNo(\'imageGadgetRow\'); toggleYes(\'imageGadgetButton\'); toggleNo(\'imageCodeInfo\'); toggleYes(\'imageCodeButton\');" style="font-family: Arial; font-size: 10pt; font-weight: bold"></td>';
				$form_content .= '<td colspan="3" class="syntacts-form-row">&nbsp;</td>';
				$form_content .= '</tr>';
				$form_content .= '<TR style="display: '.($image != "" || !isset($pageInfo['productparentid']) ? ';' : 'none;').'" id="imageRow">';
				$form_content .= '<TD VALIGN="top" colspan="4">';
				$form_content .= '<table border="0" width="100%" cellpadding="0" cellspacing="0">';
				$imageScript = "<script type=\"text/javascript\">Event.observe(window, \"load\",function(){addFileToPost('Store', 'NULL', 'NULL', 'main_image', 'productparentImage', 1, 500, 34);});</script>";
				$imageHidden =& Piwi::CreateWidget('HiddenEntry', 'productparentImage', $image);
				$imageButton = "&nbsp;&nbsp;&nbsp;&nbsp;<INPUT TYPE=\"button\" VALUE=\"Uploaded Files\" ONCLICK=\"openUploadWindow('productparentImage')\" STYLE=\"font-family: Arial; font-size: 10pt; font-weight: bold\">";
		        $form_content .= "<tr><td class=\"syntacts-form-row\"><div id=\"insertMedia\"><b>Insert Media: </b></div>".$image_preview."</td><td class=\"syntacts-form-row\"><div id=\"imageField\"><div id=\"main_image\" style=\"float: left; width: 500px;\"></div>".$imageScript.$imageHidden->Get().$imageButton."</div></td></tr>";
				  
				// Image Width and Height
				$form_content .= '<tr style="display: '.($image != "" || !isset($pageInfo['productparentid']) ? ';' : 'none;').'" id="imageInfo" class="syntacts-form-row">';
				$form_content .= '<td>&nbsp;</td>';
				$form_content .= '<td colspan="3" valign="top">';
				$form_content .= '<b>';
				$form_content .= '<select size="1" id="productparentimage_width" name="productparentimage_width" onChange="document.getElementById(\'productparentimage_height\').value=0">';
				$image_width = (isset($pageInfo['productparentimage_width'])) ? $pageInfo['productparentimage_width'] : 0;
				$form_content .= '<option value="0"'.($image_width == 0 || !isset($pageInfo['productparentid']) ? ' SELECTED' : '').'>Auto</option>';
				for ($w = 1; $w<950; $w++) { 
					$form_content .= '<option value="'.$w.'"'.($image_width == $w ? ' SELECTED' : '').'>'.$w.'</option>';
				}
				$form_content .= '</select>&nbsp;Width</b>&nbsp;&nbsp;&nbsp;';
				$form_content .= '<b><select size="1" id="productparentimage_height" name="productparentimage_height" onChange="document.getElementById(\'productparentimage_width\').value=0">';
				$image_height = (isset($pageInfo['productparentimage_height'])) ? $pageInfo['productparentimage_height'] : 0;
				$form_content .= '<option value="0"'.($image_height == 0 || !isset($pageInfo['productparentid']) ? ' SELECTED' : '').'>Auto</option>';
				for ($i = 1; $i<950; $i++) { 
					$form_content .= '<option value="'.$i.'"'.($image_height == $i ? ' SELECTED' : '').'>'.$i.'</option>';
				}
				$form_content .= '</select>&nbsp;Height</b>&nbsp;in pixels</td>';
				$form_content .= '</tr>';
				
				// Image URL Type
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('STORE_URLTYPE')) {
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
				$url = (isset($pageInfo['productparenturl'])) ? $pageInfo['productparenturl'] : '';
				$form_content .= '<tr class="syntacts-form-row" id="URLTypeInfo">';
				$form_content .= '<td><label for="productparenturl_type"><nobr>'.$helpString.'</nobr></label></td>';
				$form_content .= '<td colspan="3">';
				$form_content .= '<select NAME="productparenturl_type" SIZE="1" onChange="if (this.value == \'internal\') {toggleYes(\'internalURLInfo\'); toggleNo(\'externalURLInfo\'); toggleYes(\'urlTargetInfo\');};  if (this.value == \'external\') {toggleNo(\'internalURLInfo\'); toggleYes(\'externalURLInfo\'); toggleYes(\'urlTargetInfo\');}; if (this.value == \'imageviewer\') {toggleNo(\'internalURLInfo\'); toggleNo(\'externalURLInfo\'); toggleNo(\'urlTargetInfo\');}; ">';
				$form_content .= '<option value="imageviewer"'.((!empty($url) && $url == "javascript:void(0);") || empty($url) || !isset($pageInfo['productparentid']) ? ' selected' : '').'>Open Image in New Window</option>';
				$form_content .= '<option value="internal" '.(!empty($url) && strpos($url, "://") === false && $url != "javascript:void(0);" ? ' selected' : '').'>Internal</option>';
				$form_content .= '<option value="external" '.(!empty($url) && strpos($url, "://") === true ? ' selected' : '').'>External</option>';
				$form_content .= '</select>';
				$form_content .= '</td>';
				$form_content .= '</tr>';
						
				// Image Internal URL		
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('STORE_INTERNALURL')) {
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
				$form_content .= '<tr style="display: '.((!empty($url) && strpos($url, "://") === true) || $url == "javascript:void(0);" || empty($url) || !isset($pageInfo['productparentid']) ? 'none;' : ';').'" class="syntacts-form-row" id="internalURLInfo">';
				$form_content .= '<td><label for="productparentinternal_url"><nobr>'.$helpString.'</nobr></label></td>';
				$post_url = (!empty($url) && strpos($url, "://") === false) ? $url : '';
				$urlListCombo =& Piwi::CreateWidget('Combo', 'productparentinternal_url');
				$urlListCombo->setID('productparentinternal_url');
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
				$form_content .= '<td colspan="3">'.$urlListCombo->Get().'</td>';
				$form_content .= '</tr>';
						
				// Image External URL		
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('STORE_EXTERNALURL')) {
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
				$external_url = (!empty($url) && strpos($url, "://") === true) ? $url : '';
				$externalUrlEntry =& Piwi::CreateWidget('Entry', 'productparentexternal_url', $external_url);
				$externalUrlEntry->SetTitle(_t('STORE_EXTERNALURL'));
				$externalUrlEntry->SetStyle('direction: ltr; width: 300px;');
				$form_content .= "<tr style=\"display: ".((!empty($url) && strpos($url, "://") === false) || $url == "javascript:void(0);" || empty($url) || !isset($pageInfo['productparentid']) ? 'none;' : ';')."\" class=\"syntacts-form-row\" id=\"externalURLInfo\"><td><label for=\"productparentexternal_url\"><nobr>".$helpString."</nobr></label></td><td colspan=\"3\">".$externalUrlEntry->Get()."</td></tr>";
						
				// Image URL Target
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('STORE_URLTARGET')) {
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
				$url_target = (isset($pageInfo['productparenturl_target'])) ? $pageInfo['productparenturl_target'] : '_self';
				$url_targetCombo =& Piwi::CreateWidget('Combo', 'productparenturl_target');
				$url_targetCombo->AddOption('Open in Same Window', '_self');
				$url_targetCombo->AddOption('Open in a New Window', '_blank');
				$url_targetCombo->SetDefault($url_target);
				$url_targetCombo->setTitle(_t('STORE_URLTARGET'));
				$form_content .= "<tr style=\"display: ".((!empty($url)) || $url == "javascript:void(0);" || empty($url) || !isset($pageInfo['productparentid']) ? 'none;' : ';')."\" class=\"syntacts-form-row\" id=\"urlTargetInfo\"><td class=\"syntacts-form-row\"><label for=\"productparenturl_target\"><nobr>".$helpString."</nobr></label></td><td colspan=\"3\">".$url_targetCombo->Get()."</td></tr>";
				$form_content .= '</table>';
				$form_content .= '</td>';
				$form_content .= '</tr>';
				
				// Image Gadget
				/*
				if ($account === false) {
					$form_content .= '<tr style="display: '.(substr($image, 0, 7) == "GADGET:" ? 'none;' : ';').'" id="imageGadgetButton">';
					$form_content .= '<td class="syntacts-form-row" valign="top"><input TYPE="button" VALUE="Insert Gadget" onClick="toggleYes(\'imageButton\'); toggleNo(\'imageRow\'); toggleNo(\'imageCodeInfo\'); toggleNo(\'imageGadgetButton\'); toggleYes(\'imageGadgetRow\'); toggleYes(\'imageCodeButton\'); insertGadget(\''.$GLOBALS['app']->getSiteURL().'/'. BASE_SCRIPT .'?gadget=CustomPage&amp;action=AddLayoutElement&amp;mode=insert&amp;where=Image\', \'Insert Gadget Content\');" style="font-family: Arial; font-size: 10pt; font-weight: bold"></td>';
					$form_content .= '<td colspan="3" class="syntacts-form-row">&nbsp;</td>';
					$form_content .= '</tr>';
					$form_content .= '<tr style="display: '.(substr($image, 0, 7) == "GADGET:" ? ';' : 'none;').'" id="imageGadgetRow">';
					$form_content .= '<td class="syntacts-form-row" valign="top">';
					$form_content .= '<div id="insertGadget"><b>Insert Gadget: </b></div><br />';
					if (!empty($image)) {
						if (substr($image, 0, 7) == "GADGET:") {
							$form_content .= '<img border="0" src="'. $GLOBALS['app']->GetJawsURL() . '/gadgets/'.substr($image, strpos($image, 'GADGET:')+6, strpos($image, '_ACTION:')-1).'/images/logo.png" align="left" style="padding: 5px; visibility: visible;" id="main_gadget_src">';
						}
						$form_content .= '<br /><b><a id="imageDelete" href="javascript:void(0);" onclick="document.getElementById(\'main_gadget_src\').style.visibility = \'hidden\'; document.getElementById(\'productparentImage\').value = \'\';">Delete</a></b>';
					}
					$form_content .= '</td>';
					$form_content .= '<td colspan="3" class="syntacts-form-row">&nbsp;</td>';
					$form_content .= '</tr>';
				}
				*/
				
				// Image HTML
				$image_code = (isset($pageInfo['productparentimage_code'])) ? $pageInfo['productparentimage_code'] : '';
				$form_content .= '<tr style="display: '.(!empty($image_code) && empty($image) ? 'none;' : ';').'" id="imageCodeButton">';
				$form_content .= '<td class="syntacts-form-row" valign="top"><input TYPE="button" VALUE="Insert HTML" onClick="toggleYes(\'imageCodeInfo\'); toggleYes(\'imageButton\'); toggleNo(\'imageRow\'); toggleYes(\'imageGadgetButton\'); toggleNo(\'imageGadgetRow\'); toggleNo(\'imageCodeButton\');" STYLE="font-family: Arial; font-size: 10pt; font-weight: bold" /></td>';
				$form_content .= '<td colspan="3" class="syntacts-form-row">&nbsp;</td>';
				$form_content .= '</tr>';
				$form_content .= '<tr style="display: '.(!empty($image_code) && empty($image) ? ';' : 'none;').'" id="imageCodeInfo">';
				$form_content .= '<td class="syntacts-form-row"><b>Insert HTML:</b></td>';
				// send main splash editor HTML to syntacts
				$editorCode=& Piwi::CreateWidget('TextArea', 'productparentimage_code', $image_code);
				$editorCode->SetStyle('width: 490px;');
				$editorCode->SetID('productparentimage_code');
				$form_content .= '<td colspan="2" class="syntacts-form-row">'.$editorCode->Get().'</td>';
				$form_content .= '<td class="syntacts-form-row"><b><a id="imageDelete" href="javascript:void(0);" onclick="document.getElementById(\'productparentimage_code\').value = \'\';">Delete</a></b></td>';
				$form_content .= '</tr>';

				/*
				// RSS URL
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('STORE_RSSURL')) {
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
				$url = (isset($pageInfo['productparentrss_url'])) ? $pageInfo['productparentrss_url'] : '';
				$urlEntry =& Piwi::CreateWidget('Entry', 'productparentRss_url', $url);
				$urlEntry->SetTitle(_t('STORE_RSSURL'));
				$urlEntry->SetStyle('direction: ltr; width: 300px;');
				$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"productparentRss_url\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$urlEntry->Get()."</td></tr>";
				*/
				if ($error != '') {
					$stpl->SetVariable('content', $error);
				} else {
					$stpl->SetVariable('content', $form_content);
		        }
				$stpl->ParseBlock('form');
				$page = $stpl->Get();
			} else {
				$page = _t('STORE_ERROR_ASPPAGE_NOT_RETRIEVED', $snoopy->error)."\n";
			}
			
			$tpl->SetVariable('content', $page);
		} else {
			return new Jaws_Error(_t('STORE_ERROR_ASPPAGE_NOT_RETRIEVED'), _t('STORE_NAME'));
		}
		
        $tpl->ParseBlock('gadget_product');
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
			$GLOBALS['app']->Session->CheckPermission('Store', 'default');
		} else {
			if (!$this->GetPermission('Store', 'ManageProducts') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Store', 'OwnProduct')) {
				$GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
				$userHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
				return $userHTML->DefaultAction();
			}
		}
		
		require_once JAWS_PATH . 'include/Jaws/Header.php';
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');        
		
		$request =& Jaws_Request::getInstance();
		if (empty($fuseaction)) {
			$fuseaction = $request->get('fuseaction', 'post');
		}
		$get  = $request->get(array('fuseaction', 'pct', 'linkid', 'id'), 'get');
        if (empty($fuseaction)) {
			$fuseaction = $get['fuseaction'];
		}
        
		$adminModel = $GLOBALS['app']->LoadGadget('Store', 'AdminModel');
		$model = $GLOBALS['app']->LoadGadget('Store', 'Model');
		$redirect_response = '';

		$editproductparent = false;
		$editproduct = false;
		$editpost = false;
		$editproductattribute = false;
		$editattributetype = false;
		$editsale = false;
		$editbrand = false;
		
        if (!empty($fuseaction)) {		
			switch($fuseaction) {
                case "AddProductParent": 
						$keys = array(
							'productparentParent', 'productparentsort_order', 'productparentCategory_Name', 
							'productparentImage', 'productparentDescription', 'productparentActive',
							'productparentFeatured', 'productparentRss_url', 
							'productparentimage_width', 'productparentimage_height', 'productparentimage_code',
							'productparenturl_type', 'productparentinternal_url', 'productparentexternal_url', 
							'productparenturl_target'
						);
						if (count($params) > 0) {
							$postData = $params;
							foreach ($keys as $key) {
								if (!isset($postData[$key])) {
									$postData[$key] = '';
								}	
							}
						} else {	
							$postData = $request->getRaw($keys, 'post');
						}
						//foreach($postData as $key => $value) {
							//echo $key."=".$value."\n";
						//}
						// add OwnerID if no permissions
						if ($GLOBALS['app']->Session->GetPermission('Store', 'ManageProductParents') && $account === false) {
							$OwnerID = null;
						} else {
							$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
						}
						$result = $adminModel->AddProductParent(
							$postData['productparentsort_order'], $postData['productparentParent'], $postData['productparentCategory_Name'], 
							$postData['productparentDescription'], $postData['productparentImage'], $postData['productparentFeatured'], 
							$postData['productparentActive'], $OwnerID, $postData['productparentRss_url'], $postData['productparenturl_type'], 
							$postData['productparentinternal_url'], $postData['productparentexternal_url'], $postData['productparenturl_target'],
							/* $postData['productparentimage_width'], $postData['productparentimage_height'],*/ $postData['productparentimage_code']
						);
						if (!Jaws_Error::IsError($result)) {
					        // declare result as ok for later
							$editproductparent = true;
						} else {
							$editproductparent = false;
							$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
							//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
						}
						break;
                case "EditProductParent": 
						$keys = array(
							'productparentID', 'productparentParent', 'productparentsort_order', 'productparentCategory_Name', 
							'productparentImage', 'productparentDescription', 'productparentActive', 
							'productparentFeatured', 'productparentRss_url', 
							'productparentimage_width', 'productparentimage_height', 'productparentimage_code',
							'productparenturl_type', 'productparentinternal_url', 'productparentexternal_url', 
							'productparenturl_target'
						);
						if (count($params) > 0) {
							$postData = $params;
							foreach ($keys as $key) {
								if (!isset($postData[$key])) {
									$postData[$key] = '';
								}	
							}
						} else {	
							$postData = $request->getRaw($keys, 'post');
						}
						if (isset($postData['productparentID'])) {
							// add OwnerID if no permissions
							if ($GLOBALS['app']->Session->GetPermission('Store', 'ManageProductParents') && $account === false) {
								$OwnerID = null;			
								$result = $adminModel->UpdateProductParent(
									(int)$postData['productparentID'], $postData['productparentParent'], 
									$postData['productparentCategory_Name'], $postData['productparentsort_order'], $postData['productparentDescription'], 
									$postData['productparentImage'], $postData['productparentFeatured'], $postData['productparentActive'],
									$postData['productparentRss_url'], $postData['productparenturl_type'], $postData['productparentinternal_url'], 
									$postData['productparentexternal_url'], $postData['productparenturl_target'],
									/* $postData['productparentimage_width'], $postData['productparentimage_height'],*/ $postData['productparentimage_code']
								);
							} else {
								$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
								$parent = $model->GetProductParent((int)$postData['productparentID']);
								if ($OwnerID == $parent['productparentownerid']) {
									$result = $adminModel->UpdateProductParent(
										$parent['productparentID'], $postData['productparentParent'], $postData['productparentCategory_Name'], 
										$postData['productparentsort_order'], $postData['productparentDescription'], 
										$postData['productparentImage'], $postData['productparentFeatured'], $postData['productparentActive'], 
										$postData['productparentRss_url'], /*$postData['productparentimage_width'], $postData['productparentimage_height'],*/ 
										$postData['productparenturl_type'], $postData['productparentinternal_url'], $postData['productparentexternal_url'], 
										$postData['productparenturl_target'], $postData['productparentimage_code']
									);
								} else {
									$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_PRODUCTPARENT_NOT_UPDATED'), RESPONSE_ERROR);
									//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
								}
							}
						} else {
							$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_PRODUCTPARENT_NOT_UPDATED'), RESPONSE_ERROR);
							//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
						}
						if (!Jaws_Error::IsError($result)) {
							$editproductparent = true;
						} else {
							$editproductparent = false;
							$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
							//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
						}
                       break;
                case "DeleteProductParent": 
				        //$keys = array('idarray', 'ID', 'xcount');
				        $keys = array('productparentID');
						$postData = $request->getRaw($keys, 'post');
						$id = $postData['productparentID'];
						if (empty($id)) {
							$id = $get['id'];
						}
						// delete each ID
						if ($id) {
							// check OwnerID if no permissions
							if ($GLOBALS['app']->Session->GetPermission('Store', 'ManageProductParents') && $account === false) {
								$result = $adminModel->DeleteProductParent($id);
							} else {
								$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
								$parent = $model->GetProductParent($id);
								if ($OwnerID == $parent['productparentownerid']) {
									$result = $adminModel->DeleteProductParent($id);
								} else {
									$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_PRODUCTPARENT_NOT_DELETED'), RESPONSE_ERROR);
									//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
								}
							}
						}						
						if (!Jaws_Error::IsError($result)) {
							$editproductparent = true;
						} else {
							$editproductparent = false;
							$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
							//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
						}
						break;
                case "AddProduct": 
						$keys = array(
							'BrandID', 'sort_order', 'category', 'product_code', 'title', 'image', 
							'sm_description', 'description', 'weight', 'retail', 'price', 'cost', 
							'setup_fee', 'unit', 'custom_unit', 'recurring', 'inventory', 'instock', 
							'lowstock', 'outstockmsg', 'outstockbuy', 'attribute', 'premium', 'featured', 
							'Active', 'internal_productno', 'alink', 'alinkTitle', 
							'alinkType', 'alink2', 'alink2Title', 'alink2Type', 'alink3', 'alink3Title', 'alink3Type',
							'rss_url', 'contact', 'contact_email', 'contact_phone', 'contact_website', 'contact_photo', 'company', 
							'company_email', 'company_phone', 'company_website', 'company_logo', 'subscribe_method', 'sales', 'min_qty', 'checksum'
						);
						if (count($params) > 0) {
							$postData = $params;
							foreach ($keys as $key) {
								if (!isset($postData[$key])) {
									$postData[$key] = '';
								}	
							}
						} else {	
							$postData = $request->getRaw($keys, 'post');
						}
						/*
						foreach($postData as $key => $value) {
							echo $key."=".$value."\n";
						}
						*/
						// add OwnerID if no permissions
						if (isset($postData['custom_unit']) && !empty($postData['custom_unit'])) {
							$unit = $postData['custom_unit'];
						} else {
							$unit = $postData['unit'];
						}
						if ($GLOBALS['app']->Session->GetPermission('Store', 'ManageProducts') && $account === false) {
							$OwnerID = null;
						} else {
							$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
						}
						$result = $adminModel->AddProduct(
							$postData['BrandID'], $postData['sort_order'], $postData['category'], $postData['product_code'], 
							$postData['title'], $postData['image'], $postData['sm_description'], $postData['description'], 
							$postData['weight'], $postData['retail'], $postData['price'], $postData['cost'], 
							$postData['setup_fee'], $unit, $postData['recurring'], $postData['inventory'], $postData['instock'], 
							$postData['lowstock'], $postData['outstockmsg'], $postData['outstockbuy'], $postData['attribute'], $postData['premium'], 
							$postData['featured'], $OwnerID, $postData['Active'], $postData['internal_productno'], $postData['alink'], $postData['alinkTitle'], 
							$postData['alinkType'], $postData['alink2'], $postData['alink2Title'], $postData['alink2Type'], $postData['alink3'], 
							$postData['alink3Title'], $postData['alink3Type'], $postData['rss_url'], $postData['contact'], $postData['contact_email'], 
							$postData['contact_phone'], $postData['contact_website'], $postData['contact_photo'], $postData['company'], 
							$postData['company_email'], $postData['company_phone'], $postData['company_website'], $postData['company_logo'], 
							$postData['subscribe_method'], $postData['sales'], $postData['min_qty'], $postData['checksum']
						);
						if (!Jaws_Error::IsError($result) ) {
					        // declare result as ok for later
							$editproduct = true;
							$redirect_response = '&linkid='.$result;
						} else {
							$editproduct = false;
							$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
							//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
						}
						break;
                case "EditProduct": 
						$keys = array(
							'ID', 'BrandID', 'sort_order', 'category', 'product_code', 'title', 'image', 
							'sm_description', 'description', 'weight', 'retail', 'price', 'cost', 
							'setup_fee', 'unit', 'custom_unit', 'recurring', 'inventory', 'instock', 
							'lowstock', 'outstockmsg', 'outstockbuy', 'attribute', 'premium', 'featured', 
							'Active', 'internal_productno', 'alink', 'alinkTitle', 
							'alinkType', 'alink2', 'alink2Title', 'alink2Type', 'alink3', 'alink3Title', 'alink3Type',
							'rss_url', 'contact', 'contact_email', 'contact_phone', 'contact_website', 'contact_photo', 'company', 
							'company_email', 'company_phone', 'company_website', 'company_logo', 'subscribe_method', 'sales', 'min_qty'
						);
						if (count($params) > 0) {
							$postData = $params;
							foreach ($keys as $key) {
								if (!isset($postData[$key])) {
									$postData[$key] = '';
								}	
							}
						} else {	
							$postData = $request->getRaw($keys, 'post');
						}
						if (isset($postData['ID']) && !empty($postData['ID'])) {
							if (isset($postData['custom_unit']) && !empty($postData['custom_unit'])) {
								$unit = $postData['custom_unit'];
							} else {
								$unit = $postData['unit'];
							}
							// add OwnerID if no permissions
							if ($GLOBALS['app']->Session->GetPermission('Store', 'ManageProducts') && $account === false) {
								$result = $adminModel->UpdateProduct(
									(int)$postData['ID'], $postData['BrandID'], $postData['sort_order'], $postData['category'], $postData['product_code'], 
									$postData['title'], $postData['image'], $postData['sm_description'], $postData['description'], 
									$postData['weight'], $postData['retail'], $postData['price'], $postData['cost'], 
									$postData['setup_fee'], $unit, $postData['recurring'], $postData['inventory'], $postData['instock'], 
									$postData['lowstock'], $postData['outstockmsg'], $postData['outstockbuy'], $postData['attribute'], $postData['premium'], 
									$postData['featured'], $postData['Active'], $postData['internal_productno'], $postData['alink'], $postData['alinkTitle'], 
									$postData['alinkType'], $postData['alink2'], $postData['alink2Title'], $postData['alink2Type'], $postData['alink3'], 
									$postData['alink3Title'], $postData['alink3Type'], $postData['rss_url'], $postData['contact'], $postData['contact_email'], 
									$postData['contact_phone'], $postData['contact_website'], $postData['contact_photo'], $postData['company'], 
									$postData['company_email'], $postData['company_phone'], $postData['company_website'], $postData['company_logo'], 
									$postData['subscribe_method'], $postData['sales'], $postData['min_qty']
								);
							} else {
								$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
								$parent = $model->GetProduct((int)$postData['ID']);
								if ($OwnerID == $parent['ownerid']) {
									$result = $adminModel->UpdateProduct(
										$parent['id'], $postData['BrandID'], $postData['sort_order'], $postData['category'], $postData['product_code'], 
										$postData['title'], $postData['image'], $postData['sm_description'], $postData['description'], 
										$postData['weight'], $postData['retail'], $postData['price'], $postData['cost'], 
										$postData['setup_fee'], $unit, $postData['recurring'], $postData['inventory'], $postData['instock'], 
										$postData['lowstock'], $postData['outstockmsg'], $postData['outstockbuy'], $postData['attribute'], $postData['premium'], 
										$postData['featured'], $postData['Active'], $postData['internal_productno'], $postData['alink'], $postData['alinkTitle'], 
										$postData['alinkType'], $postData['alink2'], $postData['alink2Title'], $postData['alink2Type'], $postData['alink3'], 
										$postData['alink3Title'], $postData['alink3Type'], $postData['rss_url'], $postData['contact'], $postData['contact_email'], 
										$postData['contact_phone'], $postData['contact_website'], $postData['contact_photo'], $postData['company'], 
										$postData['company_email'], $postData['company_phone'], $postData['company_website'], $postData['company_logo'], 
										$postData['subscribe_method'], $postData['sales'], $postData['min_qty']
									);
								} else {
									$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_PRODUCT_NOT_UPDATED'), RESPONSE_ERROR);
									//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
								}
							}
						}
						if (!Jaws_Error::IsError($result)) {
							$editproduct = true;
							$redirect_response = '&linkid='.$postData['ID'];
						} else {
							$editproduct = false;
							$redirect_response = '&linkid='.$postData['ID'];
							$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
							//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
						}
                       break;
                case "DeleteProduct": 
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
							if ($GLOBALS['app']->Session->GetPermission('Store', 'ManageProducts') && $account === false) {
								$result = $adminModel->DeleteProduct($id);
							} else {
								$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
								$parent = $model->GetProduct($id);
								if ($OwnerID == $parent['ownerid']) {
									$result = $adminModel->DeleteProduct($id);
								} else {
									$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_PRODUCT_NOT_DELETED'), RESPONSE_ERROR);
									//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
								}
							}
						}						
						if (!Jaws_Error::IsError($result)) {
							$editproduct = true;
							$redirect_response = '&linkid='.$id;
						} else {
							$editproduct = false;
							$redirect_response = '&linkid='.$id;
							$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
							//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
						}
						break;
                case "AddPost": 
				        $keys = array(
							'sort_order', 'LinkID', 'title', 'description', 
							'Image', 'image_width', 'image_height', 'layout', 'Active'
						);
						if (count($params) > 0) {
							$postData = $params;
							foreach ($keys as $key) {
								if (!isset($postData[$key])) {
									$postData[$key] = '';
								}	
							}
						} else {	
							$postData = $request->getRaw($keys, 'post');
						}
						//foreach($postData as $key => $value) {
							//echo $key."=".$value."\n";
						//}
						// add OwnerID if no permissions
						if ($GLOBALS['app']->Session->GetPermission('Store', 'ManageProducts') && $account === false) {
							$OwnerID = null;
						} else {
							$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
							$parent = $model->GetProduct($postData['LinkID']);
							if ($OwnerID != $parent['ownerid'] || !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Store', 'OwnProduct')) {
								$GLOBALS['app']->Session->CheckPermission('Store', 'OwnProduct');
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
							$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
							//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
						}
						break;
                case "EditPost": 
				        $keys = array(
							'ID', 'sort_order', 'LinkID', 'title', 'description', 
							'Image', 'image_width', 'image_height', 'layout', 'Active'
						);
						if (count($params) > 0) {
							$postData = $params;
							foreach ($keys as $key) {
								if (!isset($postData[$key])) {
									$postData[$key] = '';
								}	
							}
						} else {	
							$postData = $request->getRaw($keys, 'post');
						}
						//foreach($postData as $key => $value) {
							//echo $key."=".$value."\n";
						//}
						if ($postData['ID']) {
							// check OwnerID if no permissions
							if ($GLOBALS['app']->Session->GetPermission('Store', 'ManageProducts') && $account === false) {
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
									$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403')), RESPONSE_ERROR);
									//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
								}
							}
						}
						if (!Jaws_Error::IsError($result)) {
							$editpost = true;
						} else {
							$editpost = false;
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
								if ($GLOBALS['app']->Session->GetPermission('Store', 'ManageProducts') && $account === false) {
									$result = $adminModel->DeletePost((int)$v);
								} else {
									$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
									$post = $model->GetPost((int)$v);
									if ($OwnerID == $post['ownerid']) {
										$result = $adminModel->DeletePost((int)$v);
									} else {
										$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403')), RESPONSE_ERROR);
										//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
									}
								}								
								$dcount++;
							}
						} else if (!empty($id)) {
							if ($GLOBALS['app']->Session->GetPermission('Store', 'ManageProducts') && $account === false) {
								$result = $adminModel->DeletePost($id);
							} else {
								$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
								$post = $model->GetPost($id);
								if ($OwnerID == $post['ownerid']) {
									$result = $adminModel->DeletePost($id);
								} else {
									$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403')), RESPONSE_ERROR);
									//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
								}
							}
						}
						if (!Jaws_Error::IsError($result)) {
							$editpost = true;
						} else {
							$editpost = false;
							$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
							//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
						}
						break;
                 case "AddProductAttribute": 
						$keys = array(
							'sort_order', 'feature', 'typeID', 'description', 'add_amount', 
							'add_percent', 'newprice', 'Active'
						);
						if (count($params) > 0) {
							$postData = $params;
							foreach ($keys as $key) {
								if (!isset($postData[$key])) {
									$postData[$key] = '';
								}	
							}
						} else {	
							$postData = $request->getRaw($keys, 'post');
						}
						//foreach($postData as $key => $value) {
							//echo $key."=".$value."\n";
						//}
						// add OwnerID if no permissions
						if ($GLOBALS['app']->Session->GetPermission('Store', 'ManageProducts') && $account === false) {
							$OwnerID = null;
						} else {
							$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
						}
						$result = $adminModel->AddProductAttribute(
							$postData['sort_order'], $postData['feature'], $postData['typeID'], $postData['description'], 
							$postData['add_amount'], $postData['add_percent'], $postData['newprice'], 
							$OwnerID, $postData['Active']
						);
						if (!Jaws_Error::IsError($result)) {
					        // declare result as ok for later
							$editproductattribute = true;
						} else {
							$editproductattribute = false;
							$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
							//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
						}
						break;
                case "EditProductAttribute": 
						$keys = array(
							'ID', 'sort_order', 'feature', 'typeID', 'description', 'add_amount', 
							'add_percent', 'newprice', 'Active'
						);
						if (count($params) > 0) {
							$postData = $params;
							foreach ($keys as $key) {
								if (!isset($postData[$key])) {
									$postData[$key] = '';
								}	
							}
						} else {	
							$postData = $request->getRaw($keys, 'post');
						}
						if (isset($postData['ID'])) {
							// add OwnerID if no permissions
							if ($GLOBALS['app']->Session->GetPermission('Store', 'ManageProducts') && $account === false) {
								$result = $adminModel->UpdateProductAttribute(
									(int)$postData['ID'], $postData['sort_order'], $postData['feature'], $postData['typeID'], 
									$postData['description'], $postData['add_amount'], $postData['add_percent'], 
									$postData['newprice'], $postData['Active']
								);
							} else {
								$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
								$parent = $model->GetAttribute((int)$postData['ID']);
								if ($OwnerID == $parent['ownerid']) {
									$result = $adminModel->UpdateProductAttribute(
										$parent['id'], $postData['sort_order'], $$postData['feature'], $postData['typeID'], 
										$postData['description'], $postData['add_amount'], $postData['add_percent'], 
										$postData['newprice'], $postData['Active']
									);
								} else {
									$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_PRODUCTATTRIBUTE_NOT_UPDATED'), RESPONSE_ERROR);
									//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
								}
							}
						}
						if (!Jaws_Error::IsError($result)) {
							$editproductattribute = true;
						} else {
							$editproductattribute = false;
							$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
							//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
						}
                       break;
                case "DeleteProductAttribute": 
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
							if ($GLOBALS['app']->Session->GetPermission('Store', 'ManageProducts') && $account === false) {
								$result = $adminModel->DeleteProductAttribute($id);
							} else {
								$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
								if ($linkid) {
									$parent = $model->GetAttribute($linkid);
									if ($OwnerID == $parent['ownerid']) {
										$result = $adminModel->DeleteProductAttribute($id);
									} else {
										$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_PRODUCTATTRIBUTE_NOT_DELETED'), RESPONSE_ERROR);
										//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
									}
								} else {
									$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_PRODUCTATTRIBUTE_NOT_DELETED'), RESPONSE_ERROR);
									//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
								}
							}
						}						
						if (!Jaws_Error::IsError($result)) {
							$editproductattribute = true;
						} else {
							$editproductattribute = false;
							$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
							//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
						}
						break;
                 case "AddAttributeType": 
						$keys = array('title', 'description', 'Active', 'itype', 'required');
						if (count($params) > 0) {
							$postData = $params;
							foreach ($keys as $key) {
								if (!isset($postData[$key])) {
									$postData[$key] = '';
								}	
							}
						} else {	
							$postData = $request->getRaw($keys, 'post');
						}
						//foreach($postData as $key => $value) {
							//echo $key."=".$value."\n";
						//}
						// add OwnerID if no permissions
						if ($GLOBALS['app']->Session->GetPermission('Store', 'ManageProducts') && $account === false) {
							$OwnerID = null;
						} else {
							$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
						}
						$result = $adminModel->AddAttributeType(
							$postData['title'], $postData['description'], 
							$postData['itype'], $postData['required'], 
							$OwnerID, $postData['Active']
						);
						if (!Jaws_Error::IsError($result) && is_numeric($result)) {
					        // declare result as ok for later
							$editattributetype = true;
							$mass_add = array();
							$i = 0;
							// FIXME: Don't use $_POST directly. Incrementally check for non-empty request->get('Answer'.$i, 'post')
							foreach ($_POST as $p => $pv) {
								if (
									!empty($pv) && substr($p, 0, 9) == 'AnswerNew' && 
									substr($p, 0, 16) != 'AnswerNewPercent' && 
									substr($p, 0, 15) != 'AnswerNewAmount' && 
									substr($p, 0, 14) != 'AnswerNewPrice' 
								) {
									$j = (int)substr($p, (strpos($p, 'AnswerNew')+9), strlen($p)); 
									$feature = $pv;
									$description = '';
									$add_percent = '';
									$add_amount = '';
									$newprice = '';
									if (!empty($_POST['AnswerNewPercent'.$j])) {
										$add_percent = $_POST['AnswerNewPercent'.$j];
									}
									if (!empty($_POST['AnswerNewAmount'.$j])) {
										$add_amount = $_POST['AnswerNewAmount'.$j];
									}
									if (!empty($_POST['AnswerNewPrice'.$j])) {
										$newprice = $_POST['AnswerNewPrice'.$j];
									}
									$res0 = $adminModel->AddProductAttribute(
										$i, $feature, $result, $description, $add_amount, 
										$add_percent, $newprice, $OwnerID, 'Y', '', false, true
									);
									if (Jaws_Error::IsError($res0)) {
										$result5 = false;
										$GLOBALS['app']->Session->PushLastResponse($res0->GetMessage(), RESPONSE_ERROR);
										//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
									} else if (is_numeric($res0)) {
										array_push($mass_add, $res0);
									}
									$i++;
								}
							}
							if (!count($mass_add) <= 0) {
								reset($mass_add);
								$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
								$res = $GLOBALS['app']->Shouter->Shout('onMassAddProductAttribute', $mass_add);
								if (Jaws_Error::IsError($res) || !$res) {
									$GLOBALS['app']->Session->PushLastResponse((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('GLOBAL_ERROR_EVENTS_LISTENER_ERROR')), RESPONSE_ERROR);
								}
							}
						} else {
							$editattributetype = false;
							$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
							//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
						}
						break;
                case "EditAttributeType": 
						$keys = array('ID', 'title', 'description', 'Active', 'itype', 'required');
						if (count($params) > 0) {
							$postData = $params;
							foreach ($keys as $key) {
								if (!isset($postData[$key])) {
									$postData[$key] = '';
								}	
							}
						} else {	
							$postData = $request->getRaw($keys, 'post');
						}
						if (isset($postData['ID'])) {
							// add OwnerID if no permissions
							if ($GLOBALS['app']->Session->GetPermission('Store', 'ManageProducts') && $account === false) {
								$result = $adminModel->UpdateAttributeType(
									(int)$postData['ID'], $postData['title'], $postData['description'], 
									$postData['itype'], $postData['required'], 
									$postData['Active']
								);
							} else {
								$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
								$parent = $model->GetAttributeType((int)$postData['ID']);
								if ($OwnerID == $parent['ownerid']) {
									$result = $adminModel->UpdateAttributeType(
										$parent['id'], $postData['title'], $postData['description'], 
										$postData['itype'], $postData['required'], 
										$postData['Active']
									);
								} else {
									$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_PRODUCTATTRIBUTE_NOT_UPDATED'), RESPONSE_ERROR);
									//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
								}
							}
						}
						if (isset($postData['ID']) && !Jaws_Error::IsError($result)) {
							$editattributetype = true;
							$mass_add = array();
							$mass_update = array();
							$i = 0;
							foreach ($_POST as $p => $pv) {
								if (substr($p, 0, 8) == 'AnswerID' && !empty($_POST['AnswerTitle'.$pv])) {
									$answer = $model->GetAttribute((int)$pv);
									if (!Jaws_Error::IsError($answer) && isset($answer['id'])) {
										$description = '';
										$add_percent = '';
										$add_amount = '';
										$newprice = '';
										if (!empty($_POST['AnswerPercent'.$pv])) {
											$add_percent = $_POST['AnswerPercent'.$pv];
										}
										if (!empty($_POST['AnswerAmount'.$pv])) {
											$add_amount = $_POST['AnswerAmount'.$pv];
										}
										if (!empty($_POST['AnswerPrice'.$pv])) {
											$newprice = $_POST['AnswerPrice'.$pv];
										}
										$res0 = $adminModel->UpdateProductAttribute(
											$answer['id'], $_POST['AnswerTitle'.$pv], (int)$postData['ID'],
											$description, $add_amount, $add_percent, $newprice, 'Y', false, true
										);
										if (Jaws_Error::IsError($res0)) {
											$result5 = false;
											$GLOBALS['app']->Session->PushLastResponse($res0->GetMessage(), RESPONSE_ERROR);
											//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
										} else {
											array_push($mass_update, $answer['id']);
										}
										$i++;
									} else {
										$GLOBALS['app']->Session->PushLastResponse(_t('FORMS_ERROR_ANSWER_NOT_FOUND'), RESPONSE_ERROR);
										//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
									}
								}
								if (substr($p, 0, 9) == 'AnswerNew' && substr($p, 0, 16) != 'AnswerNewPercent' && substr($p, 0, 15) != 'AnswerNewAmount' && substr($p, 0, 14) != 'AnswerNewPrice' && !empty($pv)) {
									$j = (int)substr($p, (strpos($p, 'AnswerNew')+9), strlen($p)); 
									$feature = $pv;
									$description = '';
									$add_percent = '';
									$add_amount = '';
									$newprice = '';
									if (!empty($_POST['AnswerNewPercent'.$j])) {
										$add_percent = $_POST['AnswerNewPercent'.$j];
									}
									if (!empty($_POST['AnswerNewAmount'.$j])) {
										$add_amount = $_POST['AnswerNewAmount'.$j];
									}
									if (!empty($_POST['AnswerNewPrice'.$j])) {
										$newprice = $_POST['AnswerNewPrice'.$j];
									}
									$res0 = $adminModel->AddProductAttribute(
										$i, $feature, (int)$postData['ID'], $description, $add_amount, 
										$add_percent, $newprice, $OwnerID, 'Y', '', false, true
									);
									if (Jaws_Error::IsError($res0)) {
										$result5 = false;
										$GLOBALS['app']->Session->PushLastResponse($res0->GetMessage(), RESPONSE_ERROR);
										//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
									} else if (is_numeric($res0)) {
										array_push($mass_add, $res0);
									}
									$i++;
								}
							}
							if (!count($mass_add) <= 0) {
								reset($mass_add);
								$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
								$res = $GLOBALS['app']->Shouter->Shout('onMassAddProductAttribute', $mass_add);
								if (Jaws_Error::IsError($res) || !$res) {
									$GLOBALS['app']->Session->PushLastResponse((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('GLOBAL_ERROR_EVENTS_LISTENER_ERROR')), RESPONSE_ERROR);
								}
							}
							if (!count($mass_update) <= 0) {
								reset($mass_update);
								$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
								$res = $GLOBALS['app']->Shouter->Shout('onMassUpdateProductAttribute', $mass_update);
								if (Jaws_Error::IsError($res) || !$res) {
									$GLOBALS['app']->Session->PushLastResponse((Jaws_Error::IsError($res) ? $res->GetMessage() : _t('GLOBAL_ERROR_EVENTS_LISTENER_ERROR')), RESPONSE_ERROR);
								}
							}
						} else {
							$editattributetype = false;
							$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
							//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
						}
                       break;
                case "DeleteAttributeType": 
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
							if ($GLOBALS['app']->Session->GetPermission('Store', 'ManageProducts') && $account === false) {
								$result = $adminModel->DeleteAttributeType($id);
							} else {
								$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
								if ($linkid) {
									$parent = $model->GetAttributeType($linkid);
									if ($OwnerID == $parent['ownerid']) {
										$result = $adminModel->DeleteAttributeType($id);
									} else {
										$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_PRODUCTATTRIBUTE_NOT_DELETED'), RESPONSE_ERROR);
										//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
									}
								} else {
									$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_PRODUCTATTRIBUTE_NOT_DELETED'), RESPONSE_ERROR);
									//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
								}
							}
						}						
						if (!Jaws_Error::IsError($result)) {
							$editattributetype = true;
						} else {
							$editattributetype = false;
							$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
							//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
						}
						break;
                 case "AddSale": 
						$keys = array(
							'title', 'startdate', 'enddate', 'description', 'discount_amount', 
							'discount_percent', 'discount_newprice', 'coupon_code', 'featured', 'Active',
							'iTimeHr', 'iTimeMin', 'iTimeSuffix', 'eTimeHr', 'eTimeMin', 'eTimeSuffix');
						if (count($params) > 0) {
							$postData = $params;
							foreach ($keys as $key) {
								if (!isset($postData[$key])) {
									$postData[$key] = '';
								}	
							}
						} else {	
							$postData = $request->getRaw($keys, 'post');
						}
						$iTime = null;
						if (isset($postData['startdate']) && !empty($postData['startdate'])) {
							$iTimeHr = (int)$postData['iTimeHr'];
							if ($postData['iTimeSuffix'] == 'PM' && $iTimeHr != 12) {
								$iTimeHr = $iTimeHr + 12;
							}
							if ($postData['iTimeSuffix'] == 'AM' && $iTimeHr == 12) {
								$iTimeHr = 0;
							}
							$iTime = $postData['startdate'] . ' ' .($iTimeHr < 10 ? '0'.$iTimeHr : $iTimeHr).":".$postData['iTimeMin'].":00";
							$iTime = $GLOBALS['db']->Date(strtotime($iTime));
						}
						$eTime = null;
						if (isset($postData['enddate']) && !empty($postData['enddate'])) {
							$eTimeHr = (int)$postData['eTimeHr'];
							if ($postData['eTimeSuffix'] == 'PM' && $eTimeHr != 12) {
								$eTimeHr = $eTimeHr + 12;
							}
							if ($postData['eTimeSuffix'] == 'AM' && $eTimeHr == 12) {
								$eTimeHr = 0;
							}
							$eTime = $postData['enddate'] . ' ' .($eTimeHr < 10 ? '0'.$eTimeHr : $eTimeHr).":".$postData['eTimeMin'].":00";
							$eTime = $GLOBALS['db']->Date(strtotime($eTime));
						}
						//foreach($postData as $key => $value) {
							//echo $key."=".$value."\n";
						//}
						// add OwnerID if no permissions
						if ($GLOBALS['app']->Session->GetPermission('Store', 'ManageProducts') && $account === false) {
							$OwnerID = null;
						} else {
							$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
						}						
						$result = $adminModel->AddSale(
							$postData['title'], $iTime, $eTime, $postData['description'], $postData['discount_amount'], 
							$postData['discount_percent'], $postData['discount_newprice'], $postData['coupon_code'], 
							$postData['featured'], $OwnerID, $postData['Active']
						);
						if (!Jaws_Error::IsError($result)) {
					        // declare result as ok for later
							$editsale = true;
						} else {
							$editsale = false;
							$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
							//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
						}
						break;
                case "EditSale": 
						$keys = array(
							'ID', 'title', 'startdate', 'enddate', 'description', 'discount_amount', 
							'discount_percent', 'discount_newprice', 'coupon_code', 'featured', 'Active',
							'iTimeHr', 'iTimeMin', 'iTimeSuffix', 'eTimeHr', 'eTimeMin', 'eTimeSuffix');
						if (count($params) > 0) {
							$postData = $params;
							foreach ($keys as $key) {
								if (!isset($postData[$key])) {
									$postData[$key] = '';
								}	
							}
						} else {	
							$postData = $request->getRaw($keys, 'post');
						}
						$iTime = null;
						if (isset($postData['startdate']) && !empty($postData['startdate'])) {
							$iTimeHr = (int)$postData['iTimeHr'];
							if ($postData['iTimeSuffix'] == 'PM' && $iTimeHr != 12) {
								$iTimeHr = $iTimeHr + 12;
							}
							if ($postData['iTimeSuffix'] == 'AM' && $iTimeHr == 12) {
								$iTimeHr = 0;
							}
							$iTime = $postData['startdate'] . ' ' .($iTimeHr < 10 ? '0'.$iTimeHr : $iTimeHr).":".$postData['iTimeMin'].":00";
							$iTime = $GLOBALS['db']->Date(strtotime($iTime));
						}
						$eTime = null;
						if (isset($postData['enddate']) && !empty($postData['enddate'])) {
							$eTimeHr = (int)$postData['eTimeHr'];
							if ($postData['eTimeSuffix'] == 'PM' && $eTimeHr != 12) {
								$eTimeHr = $eTimeHr + 12;
							}
							if ($postData['eTimeSuffix'] == 'AM' && $eTimeHr == 12) {
								$eTimeHr = 0;
							}
							$eTime = $postData['enddate'] . ' ' .($eTimeHr < 10 ? '0'.$eTimeHr : $eTimeHr).":".$postData['eTimeMin'].":00";
							$eTime = $GLOBALS['db']->Date(strtotime($eTime));
						}
						if (isset($postData['ID'])) {
							// add OwnerID if no permissions
							if ($GLOBALS['app']->Session->GetPermission('Store', 'ManageProducts') && $account === false) {
								$result = $adminModel->UpdateSale(
									(int)$postData['ID'], $postData['title'], $iTime, $eTime, $postData['description'], 
									$postData['discount_amount'], $postData['discount_percent'], $postData['discount_newprice'], $postData['coupon_code'], 
									$postData['featured'], $postData['Active']
								);
							} else {
								$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
								$parent = $model->GetSale((int)$postData['ID']);
								if ($OwnerID == $parent['ownerid']) {
									$result = $adminModel->UpdateSale(
										$parent['id'], $postData['title'], $iTime, $eTime, $postData['description'], 
										$postData['discount_amount'], $postData['discount_percent'], $postData['discount_newprice'], $postData['coupon_code'], 
										$postData['featured'], $postData['Active']
									);
								} else {
									$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_SALE_NOT_UPDATED'), RESPONSE_ERROR);
									//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
								}
							}
						}
						if (!Jaws_Error::IsError($result)) {
							$editsale = true;
						} else {
							$editsale = false;
							$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
							//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
						}
                       break;
                case "DeleteSale": 
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
							if ($GLOBALS['app']->Session->GetPermission('Store', 'ManageProducts') && $account === false) {
								$result = $adminModel->DeleteSale($id);
							} else {
								$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
								if ($linkid) {
									$parent = $model->GetSale($id);
									if ($OwnerID == $parent['ownerid']) {
										$result = $adminModel->DeleteSale($id);
									} else {
										$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_SALE_NOT_DELETED'), RESPONSE_ERROR);
										//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
									}
								} else {
									$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_SALE_NOT_DELETED'), RESPONSE_ERROR);
									//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
								}
							}
						}						
						if (!Jaws_Error::IsError($result)) {
							$editsale = true;
						} else {
							$editsale = false;
							$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
							//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
						}
						break;
                case "AddBrand": 
				        $keys = array(
							'title', 'description', 
							'Image', 'image_width', 'image_height', 'layout', 'Active', 'url_type', 
							'internal_url', 'external_url', 'url_target', 'image_code'
						);
						if (count($params) > 0) {
							$postData = $params;
							foreach ($keys as $key) {
								if (!isset($postData[$key])) {
									$postData[$key] = '';
								}	
							}
						} else {	
							$postData = $request->getRaw($keys, 'post');
						}
						//foreach($postData as $key => $value) {
							//echo $key."=".$value."\n";
						//}
						// add OwnerID if no permissions
						if ($GLOBALS['app']->Session->GetPermission('Store', 'ManageProducts') && $account === false) {
							$OwnerID = null;
						} else {
							$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
						}
						$result = $adminModel->AddBrand(
							$postData['title'], $postData['description'], $postData['Image'], 
							$postData['image_width'], $postData['image_height'], $postData['layout'], 
							$postData['Active'], $OwnerID, $postData['url_type'], 
							$postData['internal_url'], $postData['external_url'], 
							$postData['url_target'], $postData['image_code']
						);
						if (!Jaws_Error::IsError($result)) {
							$editbrand = true;
						} else {
							$editbrand = false;
							$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
							//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
						}
						break;
                case "EditBrand": 
				        $keys = array(
							'ID', 'title', 'description', 
							'Image', 'image_width', 'image_height', 'layout', 'Active', 'url_type', 
							'internal_url', 'external_url', 'url_target', 'image_code'
						);
						if (count($params) > 0) {
							$postData = $params;
							foreach ($keys as $key) {
								if (!isset($postData[$key])) {
									$postData[$key] = '';
								}	
							}
						} else {	
							$postData = $request->getRaw($keys, 'post');
						}
						//foreach($postData as $key => $value) {
							//echo $key."=".$value."\n";
						//}
						if ($postData['ID']) {
							// check OwnerID if no permissions
							if ($GLOBALS['app']->Session->GetPermission('Store', 'ManageProducts') && $account === false) {
								$result = $adminModel->UpdateBrand(
									$postData['ID'], 
									$postData['title'], $postData['description'], $postData['Image'], 
									$postData['image_width'], $postData['image_height'], $postData['layout'],
									$postData['Active'], $postData['url_type'], $postData['internal_url'], 
									$postData['external_url'], $postData['url_target'], $postData['image_code']
								);
							} else {
								$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
								$post = $model->GetBrand($postData['ID']);
								if ($OwnerID == $post['ownerid']) {
									$result = $adminModel->UpdateBrand(
										$post['id'], 
										$postData['title'], $postData['description'], $postData['Image'], 
										$postData['image_width'], $postData['image_height'], $postData['layout'], 
										$postData['Active'], $postData['url_type'], $postData['internal_url'], 
										$postData['external_url'], $postData['url_target'], $postData['image_code']
									);
								} else {
									$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403')), RESPONSE_ERROR);
									//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
								}
							}
						}
						if (!Jaws_Error::IsError($result)) {
							$editbrand = true;
						} else {
							$editbrand = false;
							$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
							//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
						}
                        break;
                case "DeleteBrand": 
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
								if ($GLOBALS['app']->Session->GetPermission('Store', 'ManageProducts') && $account === false) {
									$result = $adminModel->DeleteBrand((int)$v);
								} else {
									$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
									$post = $model->GetBrand((int)$v);
									if ($OwnerID == $post['ownerid']) {
										$result = $adminModel->DeleteBrand((int)$v);
									} else {
										$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403')), RESPONSE_ERROR);
										//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
									}
								}								
								$dcount++;
							}
						} else if (!empty($id)) {
							if ($GLOBALS['app']->Session->GetPermission('Store', 'ManageProducts') && $account === false) {
								$result = $adminModel->DeleteBrand($id);
							} else {
								$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
								$post = $model->GetBrand($id);
								if ($OwnerID == $post['ownerid']) {
									$result = $adminModel->DeleteBrand($id);
								} else {
									$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403')), RESPONSE_ERROR);
									//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
								}
							}
						}
						if (!Jaws_Error::IsError($result)) {
							$editbrand = true;
						} else {
							$editbrand = false;
							$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
							//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
						}
						break;
		   }
			
			// Send us to the appropriate page
			if ($editproductparent === true) {
				if (count($params) > 0) {
					return (is_numeric($result) ? $result : (isset($postData['productparentID']) && !empty($postData['productparentID']) ? (int)$postData['productparentID'] : false));
				} else if ($fuseaction == 'DeleteProductParent') {
					$redirect = BASE_SCRIPT . '?gadget=Store&action=Admin';
				} else if (is_numeric($result)) {
					$redirect = BASE_SCRIPT . '?gadget=Store&action=A&id='.$result;
				} else if (isset($postData['productparentID'])) {
					$redirect = BASE_SCRIPT . '?gadget=Store&action=A&id='.$postData['productparentID'];
				} else {
					$redirect = BASE_SCRIPT . '?gadget=Store&action=A&id='.$get['id'];
				}
			} else if ($editproduct === true || $editpost === true) {
				if (count($params) > 0) {
					return (is_numeric($result) ? $result : (isset($postData['ID']) && !empty($postData['ID']) ? (int)$postData['ID'] : false));
				} else {
					$redirect = BASE_SCRIPT . '?gadget=Store&action=A&id='.$get['linkid'];
				}
			} else if ($editproductattribute === true) {
				if (count($params) > 0) {
					return (is_numeric($result) ? $result : (isset($postData['ID']) && !empty($postData['ID']) ? (int)$postData['ID'] : false));
				} else {
					$redirect = BASE_SCRIPT . '?gadget=Store&action=B';
				}
			} else if ($editattributetype === true) {
				if (count($params) > 0) {
					return (is_numeric($result) ? $result : (isset($postData['ID']) && !empty($postData['ID']) ? (int)$postData['ID'] : false));
				} else {
					$redirect = BASE_SCRIPT . '?gadget=Store&action=B2';
				}
			} else if ($editsale === true) {
				if (count($params) > 0) {
					return (is_numeric($result) ? $result : (isset($postData['ID']) && !empty($postData['ID']) ? (int)$postData['ID'] : false));
				} else {
					$redirect = BASE_SCRIPT . '?gadget=Store&action=C';
				}
			} else if ($editbrand === true) {
				if (count($params) > 0) {
					return (is_numeric($result) ? $result : (isset($postData['ID']) && !empty($postData['ID']) ? (int)$postData['ID'] : false));
				} else {
					$redirect = BASE_SCRIPT . '?gadget=Store&action=D';
				}
			} else {
				if ($account === false) {
					Jaws_Header::Location(BASE_SCRIPT . '?gadget=Store');
				} else {
					Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'));
				}
			}
			$redirect_to = $request->get('redirect_to', 'post');
			if (isset($redirect_to) && !empty($redirect_to)) {
				if ($editpost === false || $editproductattribute === false || $editattributetype === false || $editsale === false) {
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
				/*
				if (isset($postData['productparentRss_url']) && !empty($postData['productparentRss_url'])) {	
					$output_html .= "<script>\n";
					$output_html .= "	setTimeout(function(){window.location.href='".$redirect."';}, 4000);\n";
					$output_html .= "</script>\n";
					$output_html .= "<noscript><div style='color:#FF0000; font-weight: bold;'>Javascript must be enabled in your browser in order to use this service.</div></noscript>\n";
					return $output_html;
				} else {
				*/
					Jaws_Header::Location($redirect);
				//}
			} else {
				if ($editproduct === true || $editpost === true) {
					$output_html .= "";
					$output_html .= "<script>\n";
					$output_html .= "	if (window.opener && !window.opener.closed) {\n";
					$output_html .= "		window.opener.location.reload();\n";
					$output_html .= "		window.close();\n";
					$output_html .= "	} else {\n";
					$output_html .= "		window.location.href='".$GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction')."';\n";
					$output_html .= "	}\n";
					$output_html .= "</script>\n";
					$output_html .= "<noscript><div style='color:#FF0000; font-weight: bold;'>Javascript must be enabled in your browser in order to use this service.</div></noscript>\n";
					return $output_html;
				} else if ($editproductattribute === true) {
					$output_html = "";
					$output_html .= "<script>\n";
					$output_html .= "	if (window.opener && !window.opener.closed) {\n";
					$output_html .= "		window.opener.location.reload();\n";
					$output_html .= "	}\n";
					$output_html .= "	window.location.href='index.php?gadget=Store&action=account_B';\n";
					$output_html .= "</script>\n";
					$output_html .= "<noscript><div style='color:#FF0000; font-weight: bold;'>Javascript must be enabled in your browser in order to use this service.</div></noscript>\n";
					return $output_html;
				} else if ($editattributetype === true) {
					$output_html = "";
					$output_html .= "<script>\n";
					$output_html .= "	if (window.opener && !window.opener.closed) {\n";
					$output_html .= "		window.opener.location.reload();\n";
					$output_html .= "	}\n";
					$output_html .= "	window.location.href='index.php?gadget=Store&action=account_C';\n";
					$output_html .= "</script>\n";
					$output_html .= "<noscript><div style='color:#FF0000; font-weight: bold;'>Javascript must be enabled in your browser in order to use this service.</div></noscript>\n";
					return $output_html;
				} else if ($editsale === true) {
					$output_html = "";
					$output_html .= "<script>\n";
					$output_html .= "	if (window.opener && !window.opener.closed) {\n";
					$output_html .= "		window.opener.location.reload();\n";
					$output_html .= "	}\n";
					$output_html .= "	window.location.href='index.php?gadget=Store&action=account_D';\n";
					$output_html .= "</script>\n";
					$output_html .= "<noscript><div style='color:#FF0000; font-weight: bold;'>Javascript must be enabled in your browser in order to use this service.</div></noscript>\n";
					return $output_html;
				}
			}

		} else {
			if ($account === false) {
				Jaws_Header::Location(BASE_SCRIPT . '?gadget=Store');
			} else {
				Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'));
			}
		}

    }


    /**
     * Display the product list page
     *
     * @access public
     * @return string
     */
    function A($account = false)
    {
		// check session
		if ($account === false) {
			$GLOBALS['app']->Session->CheckPermission('Store', 'default');
		} else {
			$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
			if (!$GLOBALS['app']->Session->GetPermission('Store', 'default')) {
				if (!$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Store', 'OwnProduct')) {
		            $GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
					$userHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
					return $userHTML->DefaultAction();
				}
			}
		}

		// document dependencies
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$request =& Jaws_Request::getInstance();
		$get = $request->get(array('action', 'id'), 'get');
		$action = $get['action'];
		$searchkeyword = $request->get('searchkeyword', 'post');
		$searchstatus = $request->get('searchstatus', 'post');
		$searchbrand = $request->get('searchbrand', 'post');
		if (empty($searchbrand)) {
			$searchbrand = $request->get('searchbrand', 'get');
		}
		$searchownerid = $request->get('searchownerid', 'post');
		if (empty($searchownerid)) {
			$searchownerid = $request->get('searchownerid', 'get');
		}
		$pid = 'all';
		if (!empty($get['id']) && strtolower($get['id']) != 'all') {
			$pid = (int)$get['id'];
		}
		$page = '';
		
		// initialize template
		$tpl =& new Jaws_Template('gadgets/Store/templates/');
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
		$tpl->SetBlock('gadget_product');
        $tpl->SetVariable('confirmProductDelete', _t('STORE_CONFIRM_DELETE_PRODUCT'));
        //$tpl->SetVariable('confirmRssHide', _t('STORE_CONFIRM_RSS_HIDE'));
		
		// account differences
		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=CustomPage&action=account_SetGBRoot');
		if ($account === false) {
			$tpl->SetVariable('workarea-style', "style=\"width: 100%; float: left; margin-top: 30px;\" ");
			$this->AjaxMe('script.js');
			$tpl->SetVariable('menubar', $this->MenuBar($action));
			$submit_vars['ACTIONPREFIX'] = "";
			$submit_vars['CLOSE_BUTTON'] = "location.href='" . BASE_SCRIPT . "?gadget=Store&amp;action=Admin';";
			$GLOBALS['app']->Layout->AddScriptLink('libraries/js/admin.js');
			$OwnerID = null;
			$base_url = BASE_SCRIPT;
		} else {
			$tpl->SetVariable('workarea-style', '');
			$this->AjaxMe('client_script.js');
			$tpl->SetVariable('menubar', '');
			$submit_vars['ACTIONPREFIX'] = "account_";
			$submit_vars['CLOSE_BUTTON'] = "if (window.opener && !window.opener.closed) {window.opener.location.reload();window.close();};";
			$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
			$base_url = 'index.php';
		}
		$syntactsUrl = $GLOBALS['app']->getSyntactsAdminHTMLUrl("Store/admin_Store_A");
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
			$snoopy = new Snoopy('Store');
			$submit_url = $syntactsUrl;
			
			if($snoopy->fetch($submit_url)) {
				$model = $GLOBALS['app']->LoadGadget('Store', 'Model');
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
			
				$mapHTML = '';
				$view_content = '';
			
				$pageInfo = array();
				if ($pid != 'all') {
					// send Page records
					$pageInfo = $model->GetProductParent($pid);
				}
				if ((!Jaws_Error::IsError($pageInfo) && ($GLOBALS['app']->Session->GetPermission('Store', 'ManageProducts') || (isset($pageInfo['productparentownerid']) && $pageInfo['productparentownerid'] == $OwnerID))) || $pid == 'all') {
					$stpl->SetVariable('id', $pid);
					$submit_vars['ADDPRODUCT_BUTTON'] = "";
					$submit_vars['DELETE_BUTTON'] = "";
					$submit_vars['EDIT_BUTTON'] = "";
					if ($account === false) {
						$parents = $model->GetProductParents();
						$stpl->SetVariable('search_action', 'admin.php?gadget=Store&action=A&id='.$pid);
						//$preview_link = "[<a href=\"". $GLOBALS['app']->Map->GetURLFor('Store', 'Category', array('id' => $pid)) ."\" target=\"_blank\">Preview Category</a>]";
						if ($pid != 'all') {
							$submit_vars['DELETE_BUTTON'] = "&nbsp;<input type=\"button\" name=\"Delete\" onclick=\"if (confirm('Do you want to delete this category? This cannot be undone.')) { location.href = 'admin.php?gadget=Store&amp;action=form_post&fuseaction=DeleteProductParent&amp;id=".$pid."'; };\" value=\"Delete\">";
							$submit_vars['EDIT_BUTTON'] = "<input style=\"max-width: 50px; width: 50px; min-width: 50px;\" type=\"button\" name=\"Edit\" onclick=\"document.getElementById('form_content').style.display = 'block'; document.getElementById('view_content').style.display = 'none';\" value=\"Edit\">";
						}
						$submit_vars['ADDPRODUCT_BUTTON'] = "&nbsp;<input type=\"button\" name=\"Add Product\" onclick=\"location.href = 'admin.php?gadget=Store&amp;action=A_form&amp;linkid=".($pid != 'all' ? $pid : '')."';\" value=\"Add Product\">";
					} else {
						$stpl->SetVariable('search_action', '');
					}
					$preview_link = '';
					
					$search_form = '';
					
					// Search status
					$statusCombo =& Piwi::CreateWidget('Combo', 'searchstatus');
					$statusCombo->AddOption('All', '');
					$statusCombo->AddOption(_t('STORE_PUBLISHED'), 'Y');
					$statusCombo->AddOption(_t('STORE_DRAFT'), 'N');
					$statusCombo->SetDefault($searchstatus);
					$statusCombo->setTitle(_t('STORE_ACTIVE'));
					$statusCombo->SetStyle('max-width: 48px; width: 48px; ');
					$search_form .= $statusCombo->Get()."&nbsp;&nbsp;";

					// Search keyword
					$searchEntry =& Piwi::CreateWidget('Entry', 'searchkeyword', $searchkeyword);
					$searchEntry->SetTitle(_t('STORE_SEARCH'));
					$searchEntry->SetStyle('direction: ltr; max-width: 95px; width: 95px;');

					$category_select = '';
					$brand_select = '';
					$owner_select = '';
					if ($account === false) {
						$category_select .= "<select style=\"width: 82px; max-width: 82px;\" name=\"searchcategory\" id=\"searchcategory\" size=\"1\" onChange=\"location.href = 'admin.php?gadget=Store&action=A&id='+this.value+'&searchbrand='+$('searchbrand').value;\">\n";
						$category_select .= "<option value=\"all\"".($pid == 'all' ? ' SELECTED' : '').">Products</option>\n";
						// send possible Parent records as options
						if (!Jaws_Error::IsError($parents)) {
							foreach($parents as $parent) {		            
								$category_select .= "<option value=\"".$parent['productparentid']."\"".($pid == $parent['productparentid'] ? ' SELECTED' : '').">".$parent['productparentcategory_name']."</option>\n";
							}
						}
						$category_select .= "</select>\n";
						$brands = $model->GetBrands();
						$brand_select .= "<select style=\"width: 73px; max-width: 73px;\" name=\"searchbrand\" id=\"searchbrand\" size=\"1\" onChange=\"location.href = 'admin.php?gadget=Store&action=A&id='+$('searchcategory').value+'&searchbrand='+this.value;\">\n";
						$brand_select .= "<option value=\"\"".(empty($searchbrand) ? ' SELECTED' : '').">Brands</option>\n";
						// send possible Parent records as options
						if (!Jaws_Error::IsError($brands)) {
							foreach($brands as $brand) {		            
								$brand_select .= "<option value=\"".$brand['id']."\"".((int)$searchbrand == $brand['id'] ? ' SELECTED' : '').">".$brand['title']."</option>\n";
							}
						}
						$brand_select .= "</select>\n";
						require_once JAWS_PATH . 'include/Jaws/User.php';
						$jUser = new Jaws_User;
						$users = $jUser->GetUsers();
						$owner_select .= "<select style=\"width: 65px; max-width: 65px;\" name=\"searchownerid\" id=\"searchownerid\" size=\"1\" onChange=\"location.href = 'admin.php?gadget=Store&action=A&searchownerid='+this.value;\">\n";
						$owner_select .= "<option value=\"\"".(empty($searchownerid) ? ' SELECTED' : '').">Users</option>\n";
						if (!Jaws_Error::IsError($users)) {
							foreach($users as $u) {		            
								$owner_select .= "<option value=\"".$u['id']."\"".((int)$searchownerid == $u['id'] ? ' SELECTED' : '').">".$u['username']."</option>\n";
							}
						}
						$owner_select .= "</select>\n";
					}

					// Search submit
					$submit =& Piwi::CreateWidget('Button', 'search', _t('GLOBAL_SEARCH'), STOCK_SEARCH);
					$submit->SetSubmit();
					$search_form .= $searchEntry->Get()."&nbsp;&nbsp;".$category_select."&nbsp;&nbsp;".$brand_select."&nbsp;&nbsp;".$owner_select."&nbsp;&nbsp;".$submit->Get()."&nbsp;&nbsp;".$preview_link;

					$stpl->SetVariable('search_form', $search_form);
					
					// send requesting URL to syntacts
					$stpl->SetVariable('HTTP_REFERER', $GLOBALS['app']->GetSiteURL());
					$stpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . '/');
					//$stpl->SetVariable('DPATH', JAWS_DPATH);
					$stpl->SetVariable('actionprefix', $submit_vars['ACTIONPREFIX']);
					$stpl->SetVariable('CLOSE_BUTTON', $submit_vars['CLOSE_BUTTON']);
					$stpl->SetVariable('DELETE_BUTTON', $submit_vars['DELETE_BUTTON']);
					$stpl->SetVariable('EDIT_BUTTON', $submit_vars['EDIT_BUTTON']);
					$stpl->SetVariable('ADDPRODUCT_BUTTON', $submit_vars['ADDPRODUCT_BUTTON']);
					$stpl->SetVariable('gadget', 'Store');
					$stpl->SetVariable('controller', $base_url);
					
					$embed_options = '';
					/*
					// send embedding options
					$embed_options = "<a href=\"javascript:void(0);\" onclick=\"showEmbedWindow('".$GLOBALS['app']->GetSiteURL()."/".$base_url.".php?gadget=Ads&amp;action=".$submit_vars['ACTIONPREFIX']."ShowEmbedWindow&amp;linkid=".$pageInfo['id']."&amp;mode=full', 'Embed This Ad');\">This Ad</a>&nbsp;&nbsp;&nbsp;\n";
					if ($account === true) {
						$embed_options .= "<a href=\"javascript:void(0);\" onclick=\"showEmbedWindow('".$GLOBALS['app']->GetSiteURL()."/".$base_url.".php?gadget=Ads&amp;action=".$submit_vars['ACTIONPREFIX']."ShowEmbedWindow&amp;linkid=".$pageInfo['id']."&amp;uid=".$GLOBALS['app']->Session->GetAttribute('user_id')."&amp;mode=LeaderBoard', 'Embed My LeaderBoards');\">My LeaderBoards</a>&nbsp;&nbsp;&nbsp;\n";
						$embed_options .= "<a href=\"javascript:void(0);\" onclick=\"showEmbedWindow('".$GLOBALS['app']->GetSiteURL()."/".$base_url.".php?gadget=Ads&amp;action=".$submit_vars['ACTIONPREFIX']."ShowEmbedWindow&amp;linkid=".$pageInfo['id']."&amp;uid=".$GLOBALS['app']->Session->GetAttribute('user_id')."&amp;mode=Banner', 'Embed My Banners');\">My Banners</a>&nbsp;&nbsp;&nbsp;\n";
						$embed_options .= "<a href=\"javascript:void(0);\" onclick=\"showEmbedWindow('".$GLOBALS['app']->GetSiteURL()."/".$base_url.".php?gadget=Ads&amp;action=".$submit_vars['ACTIONPREFIX']."ShowEmbedWindow&amp;linkid=".$pageInfo['id']."&amp;uid=".$GLOBALS['app']->Session->GetAttribute('user_id')."&amp;mode=TwoButtons', 'Embed Two of My Buttons');\">Two Buttons</a>&nbsp;&nbsp;&nbsp;\n";
						$embed_options .= "<a href=\"javascript:void(0);\" onclick=\"showEmbedWindow('".$GLOBALS['app']->GetSiteURL()."/".$base_url.".php?gadget=Ads&amp;action=".$submit_vars['ACTIONPREFIX']."ShowEmbedWindow&amp;linkid=".$pageInfo['id']."&amp;uid=".$GLOBALS['app']->Session->GetAttribute('user_id')."&amp;mode=FourButtons', 'Embed Four of My Buttons');\">Four Buttons</a>&nbsp;&nbsp;&nbsp;\n";
					}
					*/
					$stpl->SetVariable('embed_options', $embed_options);
					
					// send Post records
					$propertiesHTML = '';
					$adminmodel = $GLOBALS['app']->LoadGadget('Store', 'AdminModel');
					$searchstatus = (empty($searchstatus) ? null : $searchstatus);
					
					if (!empty($searchownerid)) {
						$posts = $model->GetStoreOfUserID((int)$searchownerid);
					} else if ((!empty($searchkeyword) || !is_null($searchstatus) || !empty($searchbrand)) && $pid != 'all') {
						$posts = $adminmodel->MultipleSearchProducts($searchkeyword, $searchbrand, '', '', '', null, $OwnerID, $pid, '', '', $searchstatus);
					} else if ($pid == 'all') {
						$posts = $adminmodel->MultipleSearchProducts($searchkeyword, $searchbrand, '', '', '', null, $OwnerID, null, '', '', $searchstatus);
					} else {
						$posts = $model->GetAllProductsOfParent($pid);
					}
			        if (!Jaws_Error::IsError($posts)) {
						$i = 0;
						foreach($posts as $post) {		            
							if ($i < 500 && isset($post['id'])) {
								$background = '';
								if ($i == 0) {
									$background = "background: #EDF3FE; border-top: dotted 1pt #E2E2E2; ";
								} else if (($i % 2) == 0) {
									$background = "background: #EDF3FE; ";
								}
								$propertiesHTML .= "<tr id=\"syntactsCategory_".$post['id']."\">\n";
								$propertiesHTML .= "	<td style=\"".$background."\" class=\"syntacts-form-row\">";
								// Show Add To Cart Link if necessary
								// See gadgets/CustomPage/HTML.php UserCustomPageSubscriptions()
								if ($post['active'] == 'Y') {
									$propertiesHTML .= _t('STORE_PUBLISHED');
								} else {
									if (BASE_SCRIPT == 'index.php' && Jaws_Gadget::IsGadgetUpdated('Ecommerce')) {
										$ecommerce_model = $GLOBALS['app']->LoadGadget('Ecommerce', 'Model');
										$pane_product = $ecommerce_model->GetProductByPaneMethod('UserStoreowners');
										if (!Jaws_Error::IsError($pane_product) && isset($pane_product['id'])) {
											$ecommerce_layout = $GLOBALS['app']->LoadGadget('Ecommerce', 'LayoutHTML');
											$product_cartHTML = $ecommerce_layout->ShowCartLink($pane_product['id']);
											if (!Jaws_Error::IsError($product_cartHTML)) {
												//var_dump($product_cartHTML);
												//exit;
												$propertiesHTML .= $product_cartHTML;
											} else {
												$propertiesHTML .= _t('STORE_DRAFT');
											}
										} else {
											$propertiesHTML .= _t('STORE_DRAFT');
										}
									} else {
										$propertiesHTML .= _t('STORE_DRAFT');
									}
								}
								$propertiesHTML .= "</td>\n";
								$propertiesHTML .= "	<td style=\"".$background."\" class=\"syntacts-form-row\"><b>".$post['id']."</b><br />";
								if (!empty($post['internal_productno']) && ($post['internal_productno'] <> 0)) {
									$propertiesHTML .= "<nobr>Internal: ".$post['internal_productno']."</nobr><br />";
								}
								$propertiesHTML .= "	</td>\n";
								$propertiesHTML .= "	<td style=\"".$background."\" class=\"syntacts-form-row\"><nobr>";
								// Does it have an image?
								$posts = $model->GetAllPostsOfProduct($post['id']);
								if (!Jaws_Error::IsError($posts)) {
									if (!empty($post['image']) || !count($posts) <= 0) {
										$propertiesHTML .= '<img title="Product has images" src="'.STOCK_IMAGE.'" border="0" />&nbsp;';
									}
								}
								// Does it have a description?
								if (!empty($post['description'])) {
									$propertiesHTML .= '<img title="Product has a description" src="'.STOCK_ALIGN_CENTER.'" border="0" />&nbsp;';
								}
								$propertiesHTML .= "<a".(!empty($post['title']) ? " title=\"".htmlentities($xss->filter($post['title']))."\""  : '')." href=\"javascript:void(0);\" onclick=\"";
								if ($account === true) {
									$propertiesHTML .= "window.open('index.php?gadget=Store&amp;action=account_A_form&amp;linkid=".($pid != 'all' ? $pid : '')."&amp;id=".$post['id']."');";
								} else { 
									$propertiesHTML .= "location.href='admin.php?gadget=Store&amp;action=A_form&amp;linkid=".($pid != 'all' ? $pid : '')."&amp;id=".$post['id']."';";
								}
								$propertiesHTML .= "\">";
								$propertiesHTML .= "<b>".(!empty($post['title']) ? (strlen($post['title']) > 25 ? substr($post['title'], 0, 25)."..." : $post['title']) : 'Product ID: '.$post['id'])."</b></a></nobr>&nbsp;&nbsp;";
								
								if (!empty($post['fast_url'])) {
									$propertiesHTML .= "[<a href=\"index.php?product/".$post['fast_url'].".html\" target=\"_blank\">Preview</a>]";
								}
								$propertiesHTML .= "</td>\n";
								$propertiesHTML .= "	<td style=\"".$background."\" class=\"syntacts-form-row\">";
								if (!empty($post['category'])) {
									$propCategories = explode(',', $post['category']);
									$catCount = 0;
									foreach($propCategories as $propCategory) {		            
										$parent = $model->GetProductParent((int)$propCategory);
										if (!Jaws_Error::IsError($parent) && isset($parent['productparentid'])) {
											if ($catCount > 0) {
												$propertiesHTML .= ", ";
											}
											if ($account === true) {
												$propertiesHTML .= $parent['productparentcategory_name'];
											} else { 
												$propertiesHTML .= "<nobr><a href=\"admin.php?gadget=Store&action=A&id=".$parent['productparentid']."\">".$parent['productparentcategory_name']."</a></nobr>";
											}
											$catCount++;
										}
									}
								}
								$propertiesHTML .= "</td>\n";
								$propertiesHTML .= "	<td style=\"".$background."text-align: center;\" class=\"syntacts-form-row\"><nobr>";
								$price = 0.00;
								if (!empty($post['price']) && ($post['price'] > 0)) {
									$price = number_format($post['price'], 2, '.', '');
								}
								// sales
								$now = $GLOBALS['db']->Date();
								$sale_price = $price;
								if (isset($post['sales']) && !empty($post['sales'])) {
									$propSales = explode(',', $post['sales']);
									$saleCount = 0;
									foreach($propSales as $propSale) {		            
										$saleParent = $model->GetSale((int)$propSale);
										if (!Jaws_Error::IsError($saleParent)) {
											if (
												empty($saleParent['coupon_code']) && $saleParent['active'] == 'Y' && 
												($now > $saleParent['startdate'] && $now < $saleParent['enddate'])
											) {
												if ($saleParent['discount_amount'] > 0) {
													$sale_price = number_format($sale_price - number_format($saleParent['discount_amount'], 2, '.', ','), 2, '.', '');
												} else if ($saleParent['discount_percent'] > 0) {
													$sale_price = number_format($sale_price - ($sale_price * ($saleParent['discount_percent'] * .01)), 2, '.', '');
												} else if ($saleParent['discount_newprice'] > 0) {
													$sale_price = number_format($saleParent['discount_newprice'], 2, '.', '');
												}
											}
										}
										$saleCount++;
									}
								}
								$price_string = '&nbsp;$'.number_format($sale_price, 2, '.', ',').'&nbsp;&nbsp;';
								$propertiesHTML .= $xss->parse($price_string);
								$propertiesHTML .= "</nobr></td>\n";
								$propertiesHTML .= "	<td style=\"".$background."text-align: center;\" class=\"syntacts-form-row\"><nobr>";
								if ($account === false) {
									$stats =& Piwi::CreateWidget('Link', 'STATS',
										BASE_SCRIPT.'?gadget=ControlPanel&action=Statistics&fusegadget=Store&fuseaction=Product&fuselinkid='.$post['id']);
									$propertiesHTML .= $stats->Get().'&nbsp;';
								}
								$propertiesHTML .= "<a href=\"javascript:void(0);\" onclick=\"";
								if ($account === true) {
									$propertiesHTML .= "window.open('index.php?gadget=Store&amp;action=account_A_form&amp;linkid=".($pid != 'all' ? $pid : '')."&amp;id=".$post['id']."');";
								} else { 
									$propertiesHTML .= "location.href='admin.php?gadget=Store&amp;action=A_form&amp;linkid=".($pid != 'all' ? $pid : '')."&amp;id=".$post['id']."';";
								}
								$propertiesHTML .= "\">EDIT</a>";
								$propertiesHTML .= "&nbsp;<noscript><INPUT TYPE=\"checkbox\" NAME=\"ID\" VALUE=\"".$post['id']."\"></noscript><script>document.write('<a href=\"javascript:void(0);\" onClick=\"deleteProduct(".$post['id'].");\" title=\"Delete this Product\">DELETE</a>');</script>";
								$propertiesHTML .= "</nobr></td>\n";
								$propertiesHTML .= "</tr>\n";
								$i++;
							}
						}
						if ($propertiesHTML == '') {
							//$propertiesHTML .= "<style>#syntactsCategories_head {display: none;}</style>\n";
							$propertiesHTML .= "<tr id=\"syntactsCategories_no_items\" noDrop=\"true\" noDrag=\"true\"><td colspan=\"100%\" style=\"text-align:left\"><i>No products ";
							if (!empty($searchkeyword)) {
								$propertiesHTML .= "that match the keyword  <b>\"".$searchkeyword."\"</b> ";
							} 
							$propertiesHTML .= "have been added to this category yet.</i></td></tr>\n";
							//$propertiesHTML .= "<style>#syntactsCategories {display: none;}</style>\n";
						}
					} else {
						$propertiesHTML .= _t('STORE_ERROR_PRODUCTS_NOT_RETRIEVED')."\n";
					}
					$stpl->SetVariable('products_html', $propertiesHTML);
					
					// Drag and drop sorting
					$drag_drop = '';
					if ($pid != 'all') {
						$drag_drop = "<script type=\"text/javascript\">Event.observe(window, \"load\",function(){var table = document.getElementById('syntactsCategories');var tableDnD = new StoreTableDnD();tableDnD.init(table);});</script>\n";			
					}
					$stpl->SetVariable('drag_drop', $drag_drop);
						
					$stpl->ParseBlock('view');
					$page .= $stpl->Get();

					// syntacts page for form
					if ($account === false && $pid != 'all') {
						$syntactsUrl = $GLOBALS['app']->getSyntactsAdminHTMLUrl('Store/admin_Store_form');
						$submit_vars['CLOSE_BUTTON'] = "document.getElementById('form_content').style.display = 'none'; document.getElementById('view_content').style.display = '';";

						// syntacts page
						if ($syntactsUrl) {
							// snoopy
							include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
							$snoopy = new Snoopy('Store');
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
								$stpl->SetBlock('form');
								// send page records
								$pageInfo = $model->GetProductParent($pid);
								if (!Jaws_Error::IsError($pageInfo) && ($GLOBALS['app']->Session->GetPermission('Store', 'ManageProducts') || $pageInfo['productparentownerid'] == $OwnerID)) {
									$submit_vars['SUBMIT_BUTTON'] = "<input type='Submit' name='Submit' value='Update'>";
								} else {
									//$error = _t('STORE_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403'));												
									return new Jaws_Error(_t('STORE_ERROR_PRODUCTPARENT_NOT_FOUND'), _t('STORE_NAME'));
								}

								// send requesting URL to syntacts
								$stpl->SetVariable('HTTP_REFERER', $GLOBALS['app']->GetSiteURL());
								$stpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . '/');
								//$stpl->SetVariable('DPATH', JAWS_DPATH);
								$stpl->SetVariable('actionprefix', $submit_vars['ACTIONPREFIX']);
								$stpl->SetVariable('gadget', 'Store');
								$stpl->SetVariable('CLOSE_BUTTON', $submit_vars['CLOSE_BUTTON']);
								$stpl->SetVariable('SUBMIT_BUTTON', $submit_vars['SUBMIT_BUTTON']);
								$stpl->SetVariable('controller', $base_url);
								
								// Get Help documentation
								$help_url = $GLOBALS['app']->getSyntactsAdminHTMLUrl("Store/admin_Store_form_help", 'txt');
								$snoopy = new Snoopy('Store');
						
								if($snoopy->fetch($help_url)) {
									$helpContent = Jaws_Utils::split2D($snoopy->results);
								}
												
								// Hidden elements
								$ID = (isset($pageInfo['productparentid'])) ? $pageInfo['productparentid'] : '';
								$idHidden =& Piwi::CreateWidget('HiddenEntry', 'productparentID', $ID);
								$form_content .= $idHidden->Get()."\n";

								$sort_order = (isset($pageInfo['productparentsort_order'])) ? $pageInfo['productparentsort_order'] : '0';
								$sort_orderHidden =& Piwi::CreateWidget('HiddenEntry', 'productparentsort_order', $sort_order);
								$form_content .= $sort_orderHidden->Get()."\n";

								$fuseaction = (isset($pageInfo['productparentid'])) ? 'EditProductParent' : 'AddProductParent';
								$fuseactionHidden =& Piwi::CreateWidget('HiddenEntry', 'fuseaction', $fuseaction);
								$form_content .= $fuseactionHidden->Get()."\n";

								$featured = (isset($pageInfo['productparentfeatured'])) ? $pageInfo['productparentfeatured'] : 'N';
								$featuredHidden =& Piwi::CreateWidget('HiddenEntry', 'productparentFeatured', $featured);
								$form_content .= $featuredHidden->Get()."\n";
								
								if ($account === false) {
									// Active
									$helpString = '';
									foreach($helpContent as $help) {		            
										if ($help[0] == _t('STORE_PUBLISHED')) {
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
									$active = (isset($pageInfo['productparentactive'])) ? $pageInfo['productparentactive'] : 'Y';
									$activeCombo =& Piwi::CreateWidget('Combo', 'productparentActive');
									$activeCombo->AddOption(_t('STORE_PUBLISHED'), 'Y');
									$activeCombo->AddOption(_t('STORE_DRAFT'), 'N');
									$activeCombo->SetDefault($active);
									$activeCombo->setTitle(_t('STORE_PUBLISHED'));
									$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"productparentActive\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$activeCombo->Get()."</td></tr>";
								} else {
									$activeHidden =& Piwi::CreateWidget('HiddenEntry', 'productparentActive', 'N');
									$form_content .= $activeHidden->Get()."\n";
								}
									
								// Parent
								$helpString = '';
								foreach($helpContent as $help) {		            
									if ($help[0] == _t('STORE_PARENT')) {
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
								$post_url = (isset($pageInfo['productparentparent']) && (int)$parent['productparentparent'] > 0 ? (int)$pageInfo['productparentparent'] : 0);
								if ($post_url == 0) {	
									$GLOBALS['db']->dbc->loadModule('Function', null, true);
									$url = $GLOBALS['db']->dbc->function->lower('[url]');
									$url1 = $GLOBALS['app']->Map->GetURLFor('Store', 'Category', array('id' => (int)$parent['productparentfast_url']));
																					
									$sql  = "SELECT [id] FROM [[menus]] WHERE $url LIKE {url}";
									$oid = $GLOBALS['db']->queryRow($sql, array('url' => '%'.strtolower($url1)));
									if (!Jaws_Error::IsError($oid) && isset($oid['gid']) && !empty($oid['gid'])) {
										$post_url = (int)$oid['gid'];
									}
								}
								$urlListCombo =& Piwi::CreateWidget('Combo', 'productparentParent');
								$urlListCombo->setID('productparentParent');

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
								$urlListCombo->setTitle(_t('STORE_PARENT'));
								$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"pid\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$urlListCombo->Get()."</td></tr>";

								// Title
								$helpString = '';
								foreach($helpContent as $help) {		            
									if ($help[0] == _t('STORE_TITLE')) {
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
								$title = (isset($pageInfo['productparentcategory_name'])) ? $pageInfo['productparentcategory_name'] : '';
								$titleEntry =& Piwi::CreateWidget('Entry', 'productparentCategory_Name', $title);
								$titleEntry->SetTitle(_t('STORE_TITLE'));
								$titleEntry->SetStyle('direction: ltr; width: 300px;');
								$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"productparentCategory_Name\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$titleEntry->Get()."</td></tr>";

								// Description
								$helpString = '';
								foreach($helpContent as $help) {		            
									if ($help[0] == _t('STORE_DESCRIPTIONFIELD')) {
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
								$content = (isset($pageInfo['productparentdescription'])) ? $pageInfo['productparentdescription'] : '';
								$editor =& $GLOBALS['app']->LoadEditor('Store', 'productparentDescription', $content, false);
								$editor->TextArea->SetStyle('width: 100%;');
								//$editor->SetWidth('100%');
								$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"productparentDescription\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$editor->Get()."</td></tr>";

								// Image
								$helpString = '';
								foreach($helpContent as $help) {		            
									if ($help[0] == _t('STORE_IMAGE')) {
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
								$image = (isset($pageInfo['productparentimage'])) ? $pageInfo['productparentimage'] : '';
								$image_src = $GLOBALS['app']->getDataURL() . 'files'.$xss->filter($pageInfo['productparentimage']);
								$image_preview = '';
								if ($image != '' && file_exists($image_src)) { 
									$image_preview .= "<br /><img border=\"0\" src=\"".$image_src."\" width=\"80\"".(strtolower(substr($image, -3)) == 'gif' || strtolower(substr($image, -3)) == 'png' || strtolower(substr($image, -3)) == 'bmp' ? ' height="80"' : '')." align=\"left\" style=\"padding: 5px visibility: visible;\" id=\"main_image_src\"><br /><b><a id=\"imageDelete\" href=\"javascript:void(0);\" onclick=\"document.getElementById('main_image_src').style.visibility = 'hidden'; document.getElementById('productparentImage').value = '';\">Delete</a></b>";
								}
								$form_content .= '<tr style="display: '.($image != "" || !isset($pageInfo['productparentid']) ? 'none;' : ';').'" id="imageButton">';
								$form_content .= '<td class="syntacts-form-row" valign="top"><input TYPE="button" VALUE="Insert Media" onClick="toggleNo(\'imageButton\'); toggleYes(\'imageRow\'); toggleYes(\'imageInfo\'); toggleNo(\'imageGadgetRow\'); toggleYes(\'imageGadgetButton\'); toggleNo(\'imageCodeInfo\'); toggleYes(\'imageCodeButton\');" style="font-family: Arial; font-size: 10pt; font-weight: bold"></td>';
								$form_content .= '<td colspan="3" class="syntacts-form-row">&nbsp;</td>';
								$form_content .= '</tr>';
								$form_content .= '<TR style="display: '.($image != "" || !isset($pageInfo['productparentid']) ? ';' : 'none;').'" id="imageRow">';
								$form_content .= '<TD VALIGN="top" colspan="4">';
								$form_content .= '<table border="0" width="100%" cellpadding="0" cellspacing="0">';
								$imageScript = "<script type=\"text/javascript\">Event.observe(window, \"load\",function(){addFileToPost('Store', 'NULL', 'NULL', 'main_image', 'productparentImage', 1, 500, 34);});</script>";
								$imageHidden =& Piwi::CreateWidget('HiddenEntry', 'productparentImage', $image);
								$imageButton = "&nbsp;&nbsp;&nbsp;&nbsp;<INPUT TYPE=\"button\" VALUE=\"Uploaded Files\" ONCLICK=\"openUploadWindow('productparentImage')\" STYLE=\"font-family: Arial; font-size: 10pt; font-weight: bold\">";
								$form_content .= "<tr><td class=\"syntacts-form-row\"><div id=\"insertMedia\"><b>Insert Media: </b></div>".$image_preview."</td><td class=\"syntacts-form-row\"><div id=\"imageField\"><div id=\"main_image\" style=\"float: left; width: 500px;\"></div>".$imageScript.$imageHidden->Get().$imageButton."</div></td></tr>";
								  
								// Image Width and Height
								$form_content .= '<tr style="display: '.($image != "" || !isset($pageInfo['productparentid']) ? ';' : 'none;').'" id="imageInfo" class="syntacts-form-row">';
								$form_content .= '<td>&nbsp;</td>';
								$form_content .= '<td colspan="3" valign="top">';
								$form_content .= '<b>';
								$form_content .= '<select size="1" id="productparentimage_width" name="productparentimage_width" onChange="document.getElementById(\'productparentimage_height\').value=0">';
								$image_width = (isset($pageInfo['productparentimage_width'])) ? $pageInfo['productparentimage_width'] : 0;
								$form_content .= '<option value="0"'.($image_width == 0 || !isset($pageInfo['productparentid']) ? ' SELECTED' : '').'>Auto</option>';
								for ($w = 1; $w<950; $w++) { 
									$form_content .= '<option value="'.$w.'"'.($image_width == $w ? ' SELECTED' : '').'>'.$w.'</option>';
								}
								$form_content .= '</select>&nbsp;Width</b>&nbsp;&nbsp;&nbsp;';
								$form_content .= '<b><select size="1" id="productparentimage_height" name="productparentimage_height" onChange="document.getElementById(\'productparentimage_width\').value=0">';
								$image_height = (isset($pageInfo['productparentimage_height'])) ? $pageInfo['productparentimage_height'] : 0;
								$form_content .= '<option value="0"'.($image_height == 0 || !isset($pageInfo['productparentid']) ? ' SELECTED' : '').'>Auto</option>';
								for ($i = 1; $i<950; $i++) { 
									$form_content .= '<option value="'.$i.'"'.($image_height == $i ? ' SELECTED' : '').'>'.$i.'</option>';
								}
								$form_content .= '</select>&nbsp;Height</b>&nbsp;in pixels</td>';
								$form_content .= '</tr>';
								
								// Image URL Type
								$helpString = '';
								foreach($helpContent as $help) {		            
									if ($help[0] == _t('STORE_URLTYPE')) {
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
								$url = (isset($pageInfo['productparenturl'])) ? $pageInfo['productparenturl'] : '';
								$form_content .= '<tr class="syntacts-form-row" id="URLTypeInfo">';
								$form_content .= '<td><label for="productparenturl_type"><nobr>'.$helpString.'</nobr></label></td>';
								$form_content .= '<td colspan="3">';
								$form_content .= '<select NAME="productparenturl_type" SIZE="1" onChange="if (this.value == \'internal\') {toggleYes(\'internalURLInfo\'); toggleNo(\'externalURLInfo\'); toggleYes(\'urlTargetInfo\');};  if (this.value == \'external\') {toggleNo(\'internalURLInfo\'); toggleYes(\'externalURLInfo\'); toggleYes(\'urlTargetInfo\');}; if (this.value == \'imageviewer\') {toggleNo(\'internalURLInfo\'); toggleNo(\'externalURLInfo\'); toggleNo(\'urlTargetInfo\');}; ">';
								$form_content .= '<option value="imageviewer"'.((!empty($url) && $url == "javascript:void(0);") || empty($url) || !isset($pageInfo['productparentid']) ? ' selected' : '').'>Open Image in New Window</option>';
								$form_content .= '<option value="internal" '.(!empty($url) && strpos($url, "://") === false && $url != "javascript:void(0);" ? ' selected' : '').'>Internal</option>';
								$form_content .= '<option value="external" '.(!empty($url) && strpos($url, "://") === true ? ' selected' : '').'>External</option>';
								$form_content .= '</select>';
								$form_content .= '</td>';
								$form_content .= '</tr>';
										
								// Image Internal URL		
								$helpString = '';
								foreach($helpContent as $help) {		            
									if ($help[0] == _t('STORE_INTERNALURL')) {
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
								$form_content .= '<tr style="display: '.((!empty($url) && strpos($url, "://") === true) || $url == "javascript:void(0);" || empty($url) || !isset($pageInfo['productparentid']) ? 'none;' : ';').'" class="syntacts-form-row" id="internalURLInfo">';
								$form_content .= '<td><label for="productparentinternal_url"><nobr>'.$helpString.'</nobr></label></td>';
								$post_url = (!empty($url) && strpos($url, "://") === false) ? $url : '';
								$urlListCombo =& Piwi::CreateWidget('Combo', 'productparentinternal_url');
								$urlListCombo->setID('productparentinternal_url');
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
								$form_content .= '<td colspan="3">'.$urlListCombo->Get().'</td>';
								$form_content .= '</tr>';
										
								// Image External URL		
								$helpString = '';
								foreach($helpContent as $help) {		            
									if ($help[0] == _t('STORE_EXTERNALURL')) {
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
								$external_url = (!empty($url) && strpos($url, "://") === true) ? $url : '';
								$externalUrlEntry =& Piwi::CreateWidget('Entry', 'productparentexternal_url', $external_url);
								$externalUrlEntry->SetTitle(_t('STORE_EXTERNALURL'));
								$externalUrlEntry->SetStyle('direction: ltr; width: 300px;');
								$form_content .= "<tr style=\"display: ".((!empty($url) && strpos($url, "://") === false) || $url == "javascript:void(0);" || empty($url) || !isset($pageInfo['productparentid']) ? 'none;' : ';')."\" class=\"syntacts-form-row\" id=\"externalURLInfo\"><td><label for=\"productparentexternal_url\"><nobr>".$helpString."</nobr></label></td><td colspan=\"3\">".$externalUrlEntry->Get()."</td></tr>";
										
								// Image URL Target
								$helpString = '';
								foreach($helpContent as $help) {		            
									if ($help[0] == _t('STORE_URLTARGET')) {
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
								$url_target = (isset($pageInfo['productparenturl_target'])) ? $pageInfo['productparenturl_target'] : '_self';
								$url_targetCombo =& Piwi::CreateWidget('Combo', 'productparenturl_target');
								$url_targetCombo->AddOption('Open in Same Window', '_self');
								$url_targetCombo->AddOption('Open in a New Window', '_blank');
								$url_targetCombo->SetDefault($url_target);
								$url_targetCombo->setTitle(_t('STORE_URLTARGET'));
								$form_content .= "<tr style=\"display: ".((!empty($url)) || $url == "javascript:void(0);" || empty($url) || !isset($pageInfo['productparentid']) ? 'none;' : ';')."\" class=\"syntacts-form-row\" id=\"urlTargetInfo\"><td class=\"syntacts-form-row\"><label for=\"productparenturl_target\"><nobr>".$helpString."</nobr></label></td><td colspan=\"3\">".$url_targetCombo->Get()."</td></tr>";
								$form_content .= '</table>';
								$form_content .= '</td>';
								$form_content .= '</tr>';
								
								// Image Gadget
								/*
								if ($account === false) {
									$form_content .= '<tr style="display: '.(substr($image, 0, 7) == "GADGET:" ? 'none;' : ';').'" id="imageGadgetButton">';
									$form_content .= '<td class="syntacts-form-row" valign="top"><input TYPE="button" VALUE="Insert Gadget" onClick="toggleYes(\'imageButton\'); toggleNo(\'imageRow\'); toggleNo(\'imageCodeInfo\'); toggleNo(\'imageGadgetButton\'); toggleYes(\'imageGadgetRow\'); toggleYes(\'imageCodeButton\'); insertGadget(\''.$GLOBALS['app']->getSiteURL().'/'. BASE_SCRIPT .'?gadget=CustomPage&amp;action=AddLayoutElement&amp;mode=insert&amp;where=Image\', \'Insert Gadget Content\');" style="font-family: Arial; font-size: 10pt; font-weight: bold"></td>';
									$form_content .= '<td colspan="3" class="syntacts-form-row">&nbsp;</td>';
									$form_content .= '</tr>';
									$form_content .= '<tr style="display: '.(substr($image, 0, 7) == "GADGET:" ? ';' : 'none;').'" id="imageGadgetRow">';
									$form_content .= '<td class="syntacts-form-row" valign="top">';
									$form_content .= '<div id="insertGadget"><b>Insert Gadget: </b></div><br />';
									if (!empty($image)) {
										if (substr($image, 0, 7) == "GADGET:") {
											$form_content .= '<img border="0" src="'. $GLOBALS['app']->GetJawsURL() . '/gadgets/'.substr($image, strpos($image, 'GADGET:')+6, strpos($image, '_ACTION:')-1).'/images/logo.png" align="left" style="padding: 5px; visibility: visible;" id="main_gadget_src">';
										}
										$form_content .= '<br /><b><a id="imageDelete" href="javascript:void(0);" onclick="document.getElementById(\'main_gadget_src\').style.visibility = \'hidden\'; document.getElementById(\'productparentImage\').value = \'\';">Delete</a></b>';
									}
									$form_content .= '</td>';
									$form_content .= '<td colspan="3" class="syntacts-form-row">&nbsp;</td>';
									$form_content .= '</tr>';
								}
								*/
								
								// Image HTML
								$image_code = (isset($pageInfo['productparentimage_code'])) ? $pageInfo['productparentimage_code'] : '';
								$form_content .= '<tr style="display: '.(!empty($image_code) && empty($image) ? 'none;' : ';').'" id="imageCodeButton">';
								$form_content .= '<td class="syntacts-form-row" valign="top"><input TYPE="button" VALUE="Insert HTML" onClick="toggleYes(\'imageCodeInfo\'); toggleYes(\'imageButton\'); toggleNo(\'imageRow\'); toggleYes(\'imageGadgetButton\'); toggleNo(\'imageGadgetRow\'); toggleNo(\'imageCodeButton\');" STYLE="font-family: Arial; font-size: 10pt; font-weight: bold" /></td>';
								$form_content .= '<td colspan="3" class="syntacts-form-row">&nbsp;</td>';
								$form_content .= '</tr>';
								$form_content .= '<tr style="display: '.(!empty($image_code) && empty($image) ? ';' : 'none;').'" id="imageCodeInfo">';
								$form_content .= '<td class="syntacts-form-row"><b>Insert HTML:</b></td>';
								// send main splash editor HTML to syntacts
								$editorCode=& Piwi::CreateWidget('TextArea', 'productparentimage_code', $image_code);
								$editorCode->SetStyle('width: 490px;');
								$editorCode->SetID('productparentimage_code');
								$form_content .= '<td colspan="2" class="syntacts-form-row">'.$editorCode->Get().'</td>';
								$form_content .= '<td class="syntacts-form-row"><b><a id="imageDelete" href="javascript:void(0);" onclick="document.getElementById(\'productparentimage_code\').value = \'\';">Delete</a></b></td>';
								$form_content .= '</tr>';
								  
								/*
								// RSS URL
								$helpString = '';
								foreach($helpContent as $help) {		            
									if ($help[0] == _t('STORE_RSSURL')) {
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
								$url = (isset($pageInfo['productparentrss_url'])) ? $pageInfo['productparentrss_url'] : '';
								$urlEntry =& Piwi::CreateWidget('Entry', 'productparentRss_url', $url);
								$urlEntry->SetTitle(_t('STORE_RSSURL'));
								$urlEntry->SetStyle('direction: ltr; width: 300px;');
								$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"productparentRss_url\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$urlEntry->Get()."</td></tr>";
								*/
								
								if ($error != '') {
									$stpl->SetVariable('content', $error);
								} else {
									$stpl->SetVariable('content', $form_content);
								}
								$stpl->ParseBlock('form');
								$page .= $stpl->Get();
							} else {
								$page .= _t('STORE_ERROR_ASPPAGE_NOT_RETRIEVED', $snoopy->error)."\n";
							}
						}
					}
				} else {
					// Send us to the appropriate page
					require_once JAWS_PATH . 'include/Jaws/Header.php';
					//if ($account === true) {
						$page .= _t('STORE_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403'))."\n";
						//Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'));
					/*
					} else {
						Jaws_Header::Location($base_url.'?gadget=Store&action=Admin');
					}
					*/
				}
			} else {
				$page .= _t('STORE_ERROR_ASPPAGE_NOT_RETRIEVED', $snoopy->error)."\n";
			}
		}

		$tpl->SetVariable('content', $page);
        $tpl->ParseBlock('gadget_product');

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
		include_once JAWS_PATH . 'include/Jaws/Widgets/FieldSet.php';
        
		// check session
		if ($account === false) {
			$GLOBALS['app']->Session->CheckPermission('Store', 'default');
		} else {
			$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
			if (!$GLOBALS['app']->Session->GetPermission('Store', 'default')) {
				if (!$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Store', 'OwnProduct')) {
		            $GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
					$userHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
					return $userHTML->DefaultAction();
				}
			}
		}
		
		//$GLOBALS['app']->Layout->AddScriptLink('libraries/autocomplete/autocomplete.js');
        $GLOBALS['app']->Layout->AddHeadLink('libraries/autocomplete/autocomplete.css', 'stylesheet', 'text/css', 'default');

		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$date = $GLOBALS['app']->loadDate();
		$request =& Jaws_Request::getInstance();
		$action = $request->get('action', 'get');
		$submit_vars = array();
				
		$tpl =& new Jaws_Template('gadgets/Store/templates/');
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

        $tpl->SetBlock('gadget_product');

		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=CustomPage&action=account_SetGBRoot');
		if ($account === false) {
			$this->AjaxMe('script.js');
			$tpl->SetVariable('menubar', $this->MenuBar($action));
			$GLOBALS['app']->Layout->AddScriptLink('libraries/js/admin.js');
			$submit_vars['ACTIONPREFIX'] = "";
			$submit_vars['CLOSE_BUTTON'] = "location.href='" . BASE_SCRIPT . "?gadget=CustomPage&amp;action=view&id=".$get['linkid']."';";
			$OwnerID = 0;			
			$base_url = 'admin.php';
		} else {
			$this->AjaxMe('client_script.js');
			$tpl->SetVariable('menubar', '');
			$submit_vars['ACTIONPREFIX'] = "account_";
			$submit_vars['CLOSE_BUTTON'] = "parent.parent.storeHideGB();";
			$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
			$base_url = 'index.php';
		}
		$syntactsUrl = $GLOBALS['app']->getSyntactsAdminHTMLUrl("Store/admin_Store_A_form");

		$GLOBALS['app']->Layout->AddScriptLink('libraries/greybox/AJS.js');
        $GLOBALS['app']->Layout->AddScriptLink('libraries/greybox/AJS_fx.js');
        $GLOBALS['app']->Layout->AddScriptLink('libraries/greybox/gb_scripts.js');
        $GLOBALS['app']->Layout->AddHeadLink('libraries/greybox/gb_styles.css', 'stylesheet', 'text/css');

		$tpl->SetVariable('workarea-style', 'style="margin-top: 30px;" ');
        $tpl->SetVariable('confirmPostDelete', _t('STORE_POST_CONFIRM_DELETE'));
        $tpl->SetVariable('confirmAttributeDelete', _t('STORE_ATTRIBUTE_CONFIRM_DELETE'));
		
		if ($syntactsUrl) {
			$model = $GLOBALS['app']->LoadGadget('Store', 'Model');
			$id = $request->get('id', 'get');
			$linkid = $request->get('linkid', 'get');
			
			// snoopy
			include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
			$snoopy = new Snoopy('Store');
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
		        $stpl->SetBlock('form');
			
				// Get Help documentation
				$help_url = $GLOBALS['app']->getSyntactsAdminHTMLUrl("Store/admin_Store_A_form_help", 'txt');
				$snoopy = new Snoopy('Store');
		
				if($snoopy->fetch($help_url)) {
					$helpContent = Jaws_Utils::split2D($snoopy->results);
				}
								
				$submitButton =& Piwi::CreateWidget('Button', 'submit_button', _t('GLOBAL_ADD'));
				$submitButton->AddEvent(ON_CLICK, "javascript: if(validate(document.forms['GLOBALform'])){document.forms['GLOBALform'].submit();}");
				
				if (!empty($id)) {
					$post = $model->GetProduct($id);
					if (
						!Jaws_Error::IsError($post) && 
						($GLOBALS['app']->Session->GetPermission('Store', 'ManageProducts') || 
							$post['ownerid'] == $GLOBALS['app']->Session->GetAttribute('user_id'))
					) {
						$pageInfo = $post;
						$submitButton =& Piwi::CreateWidget('Button', 'submit_button', _t('GLOBAL_UPDATE'));
						$submitButton->AddEvent(ON_CLICK, "javascript: if(validate(document.forms['GLOBALform'])){document.forms['GLOBALform'].submit();}");
						$main_image_src = '';
						if (isset($pageInfo['image']) && !empty($pageInfo['image'])) {
							$pageInfo['image'] = $xss->filter(strip_tags($pageInfo['image']));
							if (substr(strtolower($pageInfo['image']), 0, 4) == "http") {
								if (substr(strtolower($pageInfo['image']), 0, 7) == "http://") {
									$main_image_src = explode('http://', $pageInfo['image']);
									foreach ($main_image_src as $img_src) {
										if (!empty($img_src)) {
											$main_image_src = 'http://'.$img_src;
											break;
										}
									}
								} else {
									$main_image_src = explode('https://', $pageInfo['image']);
									foreach ($main_image_src as $img_src) {
										if (!empty($img_src)) {
											$main_image_src = 'https://'.$img_src;
											break;
										}
									}
								}
								if (strpos(strtolower($main_image_src), 'data/files/') !== false) {
									$main_image_src = 'image_thumb.php?uri='.urlencode($main_image_src);
								}
							} else {
								$thumb = Jaws_Image::GetThumbPath($pageInfo['image']);
								$medium = Jaws_Image::GetMediumPath($pageInfo['image']);
								if (file_exists(JAWS_DATA . 'files'.$thumb)) {
									$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$thumb;
								} else if (file_exists(JAWS_DATA . 'files'.$medium)) {
									$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$medium;
								} else if (file_exists(JAWS_DATA . 'files'.$pageInfo['image'])) {
									$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$pageInfo['image'];
								}
							}
						}

					} else {
						if (Jaws_Error::IsError($post)) {
							$page = _t('STORE_ERROR_ASPPAGE_NOT_RETRIEVED', $post->GetMessage())."\n";
						}
						//return new Jaws_Error(_t('STORE_ERROR_POST_NOT_FOUND'), _t('STORE_NAME'));
					}
									
				} else if (!empty($linkid)) {
					// send highest sort_order
					$sql = "SELECT MAX([sort_order]) FROM [[product]] ORDER BY [sort_order] DESC";
					$max = $GLOBALS['db']->queryOne($sql, array('linkid' => $linkid), array('integer'));
					if (Jaws_Error::IsError($max)) {
						$page = _t('STORE_ERROR_ASPPAGE_NOT_RETRIEVED', $max->GetMessage())."\n";
						//return $max;
					}
					$sort_order = $max;
					
				}
				$submit_vars['SUBMIT_BUTTON'] = $submitButton->Get();
			
				// Hidden elements
				$ID = (isset($pageInfo['id'])) ? $pageInfo['id'] : '';
				$idHidden =& Piwi::CreateWidget('HiddenEntry', 'ID', $ID);
				$form_content .= $idHidden->Get()."\n";

				$sortOrder = (isset($pageInfo['sort_order'])) ? $pageInfo['sort_order'] : $sort_order;
				$sort_orderHidden =& Piwi::CreateWidget('HiddenEntry', 'sort_order', $sortOrder);
				$form_content .= $sort_orderHidden->Get()."\n";

				$fuseaction = (isset($pageInfo['id'])) ? 'EditProduct' : 'AddProduct';
				$fuseactionHidden =& Piwi::CreateWidget('HiddenEntry', 'fuseaction', $fuseaction);
				$form_content .= $fuseactionHidden->Get()."\n";

				$LinkID = (isset($pageInfo['linkid'])) ? $pageInfo['linkid'] : $linkid;
				$linkIDHidden =& Piwi::CreateWidget('HiddenEntry', 'LinkID', $LinkID);
				$form_content .= $linkIDHidden->Get()."\n";
								
				$rss_url = (isset($pageInfo['rss_url'])) ? $pageInfo['rss_url'] : '';
				$rss_urlHidden =& Piwi::CreateWidget('HiddenEntry', 'rss_url', $rss_url);
				$form_content .= $rss_urlHidden->Get()."\n";
				
				$checksum = (isset($pageInfo['checksum'])) ? $pageInfo['checksum'] : '';
				$checksumHidden =& Piwi::CreateWidget('HiddenEntry', 'checksum', $checksum);
				$form_content .= $checksumHidden->Get()."\n";
								
				$attribute = (isset($pageInfo['attribute'])) ? $pageInfo['attribute'] : '';
				$attributeHidden =& Piwi::CreateWidget('HiddenEntry', 'attribute', $attribute);
				$form_content .= $attributeHidden->Get()."\n";
								
				$product_sales = (isset($pageInfo['sales'])) ? $pageInfo['sales'] : '';
				$salesHidden =& Piwi::CreateWidget('HiddenEntry', 'sales', $product_sales);
				$form_content .= $salesHidden->Get()."\n";
								
				$category = (isset($pageInfo['category'])) ? $pageInfo['category'] : '';
				$categoryHidden =& Piwi::CreateWidget('HiddenEntry', 'category', $category);
				$form_content .= $categoryHidden->Get()."\n";
								
				// Active
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('STORE_PUBLISHED')) {
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
				if (empty($helpString)) {
					$helpString = _t('STORE_PUBLISHED');
				}
				$active = (isset($pageInfo['active']) ? $pageInfo['active'] : 'Y');
				$activeCombo =& Piwi::CreateWidget('Combo', 'Active');
				$activeCombo->AddOption(_t('GLOBAL_YES'), 'Y');
				$activeCombo->AddOption(_t('GLOBAL_NO'), 'N');
				$activeCombo->SetDefault($active);
				$activeCombo->setTitle(_t('STORE_PUBLISHED'));
				$form_content .= "<tr><td class=\"syntacts-form-row product_Active_field\"><label for=\"Active\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row product_Active_field\" colspan=\"3\">".$activeCombo->Get()."</td></tr>";
				
				// Title
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('GLOBAL_TITLE')) {
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
				if (empty($helpString)) {
					$helpString = _t('GLOBAL_TITLE');
				}
				$title = (isset($pageInfo['title'])) ? $pageInfo['title'] : '';
				$titleEntry =& Piwi::CreateWidget('Entry', 'title', $title);
				$titleEntry->SetTitle(_t('GLOBAL_TITLE'));
				$titleEntry->SetStyle('direction: ltr; width: 300px;');
				$form_content .= "<tr><td class=\"syntacts-form-row product_title_field\"><label for=\"title\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row product_title_field\" colspan=\"3\">".$titleEntry->Get()."</td></tr>";
				
				// Premium
				$premium = (isset($pageInfo['premium']) ? $pageInfo['premium'] : 'N');
				if ($account === true) {
					$premiumHidden =& Piwi::CreateWidget('HiddenEntry', 'premium', $premium);
					$form_content .= $premiumHidden->Get()."\n";
				} else {
					$helpString = '';
					foreach($helpContent as $help) {		            
						if ($help[0] == _t('STORE_PREMIUM_DESC')) {
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
					if (empty($helpString)) {
						$helpString = _t('STORE_PREMIUM_DESC');
					}
					$premiumCombo =& Piwi::CreateWidget('Combo', 'premium');
					$premiumCombo->AddOption(_t('GLOBAL_YES'), 'Y');
					$premiumCombo->AddOption(_t('GLOBAL_NO'), 'N');
					$premiumCombo->SetDefault($premium);
					//$premiumCombo->setTitle(_t('STORE_PREMIUM'));
					$premiumFieldset = new Jaws_Widgets_FieldSet("<label for=\"premium\"><nobr>".$helpString."</nobr></label>");
					$premiumFieldset->SetTitle('vertical');
					$premiumFieldset->SetStyle('margin-top: 30px;');
					$premiumFieldset->Add($premiumCombo);
					$form_content .= "<tr><td class=\"syntacts-form-row product_premium_field\" colspan=\"4\">".$premiumFieldset->Get()."</td></tr>";
				}
				
				// Featured
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('STORE_FEATURED')) {
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
				if (empty($helpString)) {
					$helpString = _t('STORE_FEATURED');
				}
				$featured = (isset($pageInfo['featured']) ? $pageInfo['featured'] : 'Y');
				$featuredCombo =& Piwi::CreateWidget('Combo', 'featured');
				$featuredCombo->AddOption(_t('GLOBAL_YES'), 'Y');
				$featuredCombo->AddOption(_t('GLOBAL_NO'), 'N');
				$featuredCombo->SetDefault($featured);
				$featuredCombo->setTitle(_t('STORE_FEATURED'));
				$form_content .= "<tr><td class=\"syntacts-form-row product_featured_field\"><label for=\"featured\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row product_featured_field\" colspan=\"3\">".$featuredCombo->Get()."</td></tr>";
				
				// send possible Parent records
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('STORE_PRODUCTPARENT')) {
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
				if (empty($helpString)) {
					$helpString = _t('STORE_PRODUCTPARENT');
				}
				$parents = $model->GetProductParents();
				$categoriesHTML = '';
				$categoriesFound = false;
				if (!Jaws_Error::IsError($parents)) {
					$loopCount = 0;
					foreach($parents as $parent) {		            
						if ($parent['productparentownerid'] == $GLOBALS['app']->Session->GetAttribute('user_id') || $parent['productparentownerid'] == 0) {
							if ($categoriesFound === false) {
								$categoriesFound = true;
							}
							// Build Categories checkboxes
							if ($account === false ) {
								if ($loopCount % 2 == 0 && $loopCount > 0) {
									$loopCount = 1;
									$categoriesHTML .= "</tr><tr>";
								} else {
									$loopCount++;
								}
								$categoriesHTML .= "<td style=\"padding: 3px;\"><INPUT TYPE=\"checkbox\" id=\"category_".$parent['productparentid']."\" NAME=\"categories\" VALUE=\"".$parent['productparentid']."\"";
								$categoryChecked = false;
								if (isset($pageInfo['category']) && !empty($pageInfo['category'])) {
									$propCategories = explode(',', $pageInfo['category']);
									foreach($propCategories as $propCategory) {		            
										if ($parent['productparentid'] == (int)$propCategory) { 
											$categoryChecked = true;
											$categoriesHTML .= "CHECKED";
											break;
										}
									}
								} 
								if (((int)$LinkID == $parent['productparentid'] && $categoryChecked === false)) {
									$categoriesHTML .= "CHECKED";
								}
								$categoriesHTML .= ">&nbsp;<label for=\"category_".$parent['productparentid']."\">";
								if (isset($parent['productparentdescription']) && !empty($parent['productparentdescription'])) {
									$categoriesHTML .= "<a href=\"javascript: void(0);\" title=\"".$parent['productparentdescription']."\">".$parent['productparentcategory_name']."<a/>";
								} else {
									$categoriesHTML .= $parent['productparentcategory_name'];
								}
								$categoriesHTML .= "</label>&nbsp;&nbsp;&nbsp;</td>";
							} else {
								if (isset($pageInfo['category']) && !empty($pageInfo['category'])) {
									$propCategories = explode(',', $pageInfo['category']);
									$propCategories[0] = (int)$propCategories[0];
									$propCategories[1] = (int)$propCategories[1];
									$propCategories[2] = (int)$propCategories[2];
								}
								//if ($parent['productparentactive'] == 'Y') {
									$categoriesHTML .= "<option value=\"".$parent['productparentid']."\"".($propCategories[0] == $parent['productparentid'] ? ' selected="selected"' : '').">".$parent['productparentcategory_name']."</option>";
									$categoriesHTML2 .= "<option value=\"".$parent['productparentid']."\"".($propCategories[1] == $parent['productparentid'] ? ' selected="selected"' : '').">".$parent['productparentcategory_name']."</option>";
									$categoriesHTML3 .= "<option value=\"".$parent['productparentid']."\"".($propCategories[2] == $parent['productparentid'] ? ' selected="selected"' : '').">".$parent['productparentcategory_name']."</option>";
									//$categoriesCombo->AddOption($parent['productparentcategory_name'], $parent['productparentid']);
								//}
							}
						}
					}
					if ($categoriesFound === true) {
						if ($account === true) {
							$categoriesHTML = '<select name="category1" id="category1" class="product_category1_field">'."<option value=\"\">Select...</option>".$categoriesHTML.'</select>' .
								'&nbsp;&nbsp;<select name="category2" id="category2" class="product_category2_field">'."<option value=\"\">(Optional)</option>".$categoriesHTML2.'</select>' .
								'&nbsp;&nbsp;<select name="category3" id="category3" class="product_category3_field">'."<option value=\"\">(Optional)</option>".$categoriesHTML3.'</select>';
						}
					} else {
						$categoriesHTML = "No categories currently exist".($account === false ? ", <a href=\"admin.php?gadget=Store&action=form\">CREATE ONE</a>." : '.');
					}
				} else {
					$page = _t('STORE_ERROR_ASPPAGE_NOT_RETRIEVED', $parents->GetMessage())."\n";
				}
				$form_content .= "<tr><td class=\"syntacts-form-row product_categories_field\"><label for=\"categories\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row product_categories_field\" colspan=\"3\"><a href=\"".$base_url."?gadget=Store&action=".$submit_vars['ACTIONPREFIX']."Admin\"><b>EDIT CATEGORIES LIST</b></a><table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\"><tr>".$categoriesHTML."</tr></table></td></tr>";
				
				// BrandID
				$BrandID = (isset($pageInfo['brandid'])) ? $pageInfo['brandid'] : 0;
				if ($account === true) {
					$brandIDHidden =& Piwi::CreateWidget('HiddenEntry', 'BrandID', $BrandID);
					$form_content .= $brandIDHidden->Get()."\n";
				} else {
					$helpString = '';
					foreach($helpContent as $help) {		            
						if ($help[0] == _t('STORE_BRAND')) {
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
					if (empty($helpString)) {
						$helpString = _t('STORE_BRAND');
					}
					$BrandIDCombo =& Piwi::CreateWidget('Combo', 'BrandID');
					$BrandIDCombo->AddOption('None', 0);
					$brand_options = '';
					$brands = $model->GetBrands();
					if (!Jaws_Error::IsError($brands)) {
						foreach($brands as $brand) {
							$BrandIDCombo->AddOption($brand['title'], $brand['id']);
						}
					}
					$BrandIDCombo->SetDefault($BrandID);
					$BrandIDCombo->setTitle(_t('STORE_BRAND'));
					$form_content .= "<tr><td class=\"syntacts-form-row product_BrandID_field\"><label for=\"BrandID\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row product_BrandID_field\" colspan=\"3\">".$BrandIDCombo->Get()."</td></tr>";
				}
				
				// subscribe methods
				$subscribe_method = (isset($pageInfo['subscribe_method'])) ? $pageInfo['subscribe_method'] : '';
				if ($account === true) {
					$subscribe_methodHidden =& Piwi::CreateWidget('HiddenEntry', 'subscribe_method', $subscribe_method);
					$form_content .= $subscribe_methodHidden->Get()."\n";
				} else {
					$helpString = '';
					foreach($helpContent as $help) {		            
						if ($help[0] == _t('STORE_SUBSCRIBE_METHOD_DESC')) {
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
					if (empty($helpString)) {
						$helpString = _t('STORE_SUBSCRIBE_METHOD_DESC');
					}
					$subscribe_methodCombo =& Piwi::CreateWidget('Combo', 'subscribe_method');
					$subscribe_methodCombo->AddOption('None', '');
					
					//Get gadgets list
					$jms = $GLOBALS['app']->LoadGadget('Jms', 'AdminModel');
					$gadget_list = $jms->GetGadgetsList(null, true, true, true);
					// Get all groups of user
					require_once JAWS_PATH . 'include/Jaws/User.php';
					$jUser = new Jaws_User;
					$groups  = $jUser->GetGroupsOfUser($GLOBALS['app']->Session->GetAttribute('user_id'));
					//Hold.. if we dont have a selected gadget?.. like no gadgets?
					if (count($gadget_list) <= 0) {
						Jaws_Error::Fatal('There are no installed gadgets, enable/install one and then come back',
										 __FILE__, __LINE__);
					} else {
						reset($gadget_list);
					   //Construct panes for each available gadget
					   foreach ($gadget_list as $gadget) {
							$paneGadget = $GLOBALS['app']->LoadGadget($gadget['realname'], 'HTML');
							if (method_exists($paneGadget, 'GetUserAccountControls') && method_exists($paneGadget, 'GetUserAccountPanesInfo') && in_array($gadget['realname'], explode(',', $GLOBALS['app']->Registry->Get('/gadgets/user_access_items')))) {
								$panes = $paneGadget->GetUserAccountPanesInfo($groups);
								if (!Jaws_Error::IsError($panes)) {
									foreach ($panes as $pane_key => $pane_val) {
										// TODO: some filtering to make this easier to understand
										$subscribe_methodCombo->AddOption($gadget['realname'].': '.$pane_val, $pane_key);
									}
								} else {
									return $panes;
								}
							}
							unset($panes);
							unset($paneGadget);
						}
					}
					$subscribe_methodCombo->SetDefault($subscribe_method);
					$subscribe_methodCombo->setTitle(_t('STORE_SUBSCRIBE_METHOD_DESC'));
					$form_content .= '<TR><TD class="syntacts-form-row product_subscribe_method_field" VALIGN="top"><label for="subscribe_method"><nobr>'.$helpString.'</nobr></label></TD><TD VALIGN="top" class="syntacts-form-row product_subscribe_method_field"><a href="admin.php?gadget=Users&action=Properties"><b>EDIT USER GADGETS LIST</b></a><br />'.$subscribe_methodCombo->Get().'</TD></TR>';
				}
				
				// Product Code
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('STORE_PRODUCTCODE')) {
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
				if (empty($helpString)) {
					$helpString = _t('STORE_PRODUCTCODE');
				}
				$product_code = (isset($pageInfo['product_code'])) ? $pageInfo['product_code'] : '';
				$product_codeEntry =& Piwi::CreateWidget('Entry', 'product_code', $product_code);
				$product_codeEntry->SetTitle(_t('STORE_PRODUCTCODE'));
				$product_codeEntry->SetStyle('direction: ltr; width: 300px;');
				$form_content .= "<tr><td class=\"syntacts-form-row product_product_code_field\"><label for=\"product_code\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row product_product_code_field\" colspan=\"3\">".$product_codeEntry->Get()."</td></tr>";
				
				// Recurring
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('STORE_RECURRING')) {
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
				if (empty($helpString)) {
					$helpString = _t('STORE_RECURRING');
				}
				$recurring = (isset($pageInfo['recurring']) ? $pageInfo['recurring'] : 'N');
				$recurringCombo =& Piwi::CreateWidget('Combo', 'recurring');
				$recurringCombo->AddOption(_t('GLOBAL_YES'), 'Y');
				$recurringCombo->AddOption(_t('GLOBAL_NO'), 'N');
				$recurringCombo->SetDefault($recurring);
				$recurringCombo->setTitle(_t('STORE_RECURRING'));
				$form_content .= "<tr><td class=\"syntacts-form-row product_recurring_field\"><label for=\"recurring\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row product_recurring_field\" colspan=\"3\">".$recurringCombo->Get()."</td></tr>";
				
				// Price
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('STORE_PRICE')) {
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
				if (empty($helpString)) {
					$helpString = _t('STORE_PRICE');
				}
				$price = (isset($pageInfo['price']) && (int)$pageInfo['price'] > 0 ? $pageInfo['price'] : '');
				$priceEntry =& Piwi::CreateWidget('Entry', 'price', $price);
				$priceEntry->SetTitle(_t('STORE_PRICE'));
				$priceEntry->SetStyle('direction: ltr; width: 300px;');
				$form_content .= "<tr><td class=\"syntacts-form-row product_price_field\"><label for=\"price\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row product_price_field\" colspan=\"3\">".$priceEntry->Get()."</td></tr>";
				
				// Unit
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('STORE_UNIT')) {
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
				if (empty($helpString)) {
					$helpString = _t('STORE_UNIT');
				}
				$unit = (isset($pageInfo['unit']) ? $pageInfo['unit'] : "/ Each");
				$unitCombo =& Piwi::CreateWidget('Combo', 'unit');
				$unitCombo->AddOption("/ Each", "/ Each");
				$unitCombo->AddOption("/ Pound", "/ Pound");
				$unitCombo->AddOption("/ Gallon", "/ Gallon");
				$unitCombo->AddOption("/ Case", "/ Case");
				$unitCombo->AddOption("/ Basket", "/ Basket");
				$unitCombo->AddOption("/ Box", "/ Box");
				$unitCombo->AddOption("/ Half Dozen", "/ Half Dozen");
				$unitCombo->AddOption("/ Dozen", "/ Dozen");
				$unitCombo->AddOption("/ Foot", "/ Foot");
				$unitCombo->AddOption("/ Square Foot", "/ Square Foot");
				$unitCombo->AddOption("/ Yard", "/ Yard");
				$unitCombo->AddOption("/ Day", "/ Day");
				$unitCombo->AddOption("/ Week", "/ Week");
				$unitCombo->AddOption("/ Month", "/ Month");
				$unitCombo->AddOption("/ Year", "/ Year");
				$unitCombo->AddOption("Other", "Other");
				$unitCombo->SetDefault($unit);
				$custom_unit = '';
				if (
					$unit != "/ Each" && $unit != "/ Pound" && $unit != "/ Gallon" && 
					$unit != "/ Case" && $unit != "/ Basket" && $unit != "/ Box" && 
					$unit != "/ Foot" && $unit != "/ Square Foot" && $unit != "/ Yard" && 
					$unit != "/ Day" && $unit != "/ Week" && $unit != "/ Month" && 
					$unit != "/ Year" && isset($pageInfo['id']) && !empty($pageInfo['id'])
				) {
					$unitCombo->SetDefault("Other");
					$custom_unit = $unit;
				}
				$unitCombo->AddEvent(ON_CHANGE, "if (this.value == 'Other') { toggleYes('unitInfo'); } else { toggleNo('unitInfo'); };");
				$unitCombo->setTitle(_t('STORE_UNIT'));
				$form_content .= "<tr><td class=\"syntacts-form-row product_unit_field\"><label for=\"unit\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row product_unit_field\" colspan=\"3\">".$unitCombo->Get()."</td></tr>";
				
				// Custom Unit
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('STORE_CUSTOMUNIT')) {
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
				if (empty($helpString)) {
					$helpString = _t('STORE_CUSTOMUNIT');
				}
				$custom_unitEntry =& Piwi::CreateWidget('Entry', 'custom_unit', $custom_unit);
				$custom_unitEntry->SetTitle(_t('STORE_CUSTOMUNIT'));
				$custom_unitEntry->SetStyle('direction: ltr; width: 300px;');
				$form_content .= "<tr style=\"display: ".(!empty($custom_unit) ? "; " : "none; ")."\" id=\"unitInfo\"><td class=\"syntacts-form-row product_custom_unit_field\"><label for=\"custom_unit\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row product_custom_unit_field\" colspan=\"3\">".$custom_unitEntry->Get()."</td></tr>";
				
				// Min Quantity
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('STORE_MINQTY')) {
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
				if (empty($helpString)) {
					$helpString = _t('STORE_MINQTY');
				}
				$min_qty = (isset($pageInfo['min_qty']) && (int)$pageInfo['min_qty'] > 0 ? $pageInfo['min_qty'] : '');
				$min_qtyEntry =& Piwi::CreateWidget('Entry', 'min_qty', $min_qty);
				$min_qtyEntry->SetTitle(_t('STORE_MINQTY'));
				$min_qtyEntry->SetStyle('direction: ltr; width: 300px;');
				$form_content .= "<tr><td class=\"syntacts-form-row product_min_qty_field\"><label for=\"min_qty\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row product_min_qty_field\" colspan=\"3\">".$min_qtyEntry->Get()."</td></tr>";
				
				// Retail
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('STORE_RETAIL')) {
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
				if (empty($helpString)) {
					$helpString = _t('STORE_RETAIL');
				}
				$retail = (isset($pageInfo['retail']) && (int)$pageInfo['retail'] > 0 ? $pageInfo['retail'] : '');
				$retailEntry =& Piwi::CreateWidget('Entry', 'retail', $retail);
				$retailEntry->SetTitle(_t('STORE_RETAIL'));
				$retailEntry->SetStyle('direction: ltr; width: 300px;');
				$form_content .= "<tr><td class=\"syntacts-form-row product_retail_field\"><label for=\"retail\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row product_retail_field\" colspan=\"3\">".$retailEntry->Get()."</td></tr>";
				
				// Cost
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('STORE_COST')) {
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
				if (empty($helpString)) {
					$helpString = _t('STORE_COST');
				}
				$cost = (isset($pageInfo['cost']) && (int)$pageInfo['cost'] > 0 ? $pageInfo['cost'] : '');
				$costEntry =& Piwi::CreateWidget('Entry', 'cost', $cost);
				$costEntry->SetTitle(_t('STORE_COST'));
				$costEntry->SetStyle('direction: ltr; width: 300px;');
				$form_content .= "<tr><td class=\"syntacts-form-row product_cost_field\"><label for=\"cost\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row product_cost_field\" colspan=\"3\">".$costEntry->Get()."</td></tr>";
				
				// Setup Fee
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('STORE_SETUP_FEE')) {
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
				if (empty($helpString)) {
					$helpString = _t('STORE_SETUP_FEE');
				}
				$setup_fee = (isset($pageInfo['setup_fee']) && (int)$pageInfo['setup_fee'] > 0 ? $pageInfo['setup_fee'] : '');
				$setup_feeEntry =& Piwi::CreateWidget('Entry', 'setup_fee', $setup_fee);
				$setup_feeEntry->SetTitle(_t('STORE_SETUP_FEE'));
				$setup_feeEntry->SetStyle('direction: ltr; width: 300px;');
				$form_content .= "<tr><td class=\"syntacts-form-row product_setup_fee_field\"><label for=\"setup_fee\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row product_setup_fee_field\" colspan=\"3\">".$setup_feeEntry->Get()."</td></tr>";
				
				// Weight
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('STORE_WEIGHT')) {
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
				if (empty($helpString)) {
					$helpString = _t('STORE_WEIGHT');
				}
				$weight = (isset($pageInfo['weight']) && (int)$pageInfo['weight'] > 0 ? $pageInfo['weight'] : '');
				$weightEntry =& Piwi::CreateWidget('Entry', 'weight', $weight);
				$weightEntry->SetTitle(_t('STORE_WEIGHT'));
				$weightEntry->SetStyle('direction: ltr; width: 300px;');
				$form_content .= "<tr><td class=\"syntacts-form-row product_weight_field\"><label for=\"weight\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row product_weight_field\" colspan=\"3\">".$weightEntry->Get()."&nbsp;(in pounds)</td></tr>";
				
				// Inventory Management
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('STORE_INVENTORY_MANAGEMENT')) {
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
				if (empty($helpString)) {
					$helpString = _t('STORE_INVENTORY_MANAGEMENT');
				}
				$inventory = (isset($pageInfo['inventory']) ? $pageInfo['inventory'] : 'N');
				$inventoryCombo =& Piwi::CreateWidget('Combo', 'inventory');
				$inventoryCombo->AddOption(_t('GLOBAL_YES'), 'Y');
				$inventoryCombo->AddOption(_t('GLOBAL_NO'), 'N');
				$inventoryCombo->SetDefault($inventory);
				$inventoryCombo->setTitle(_t('STORE_INVENTORY_MANAGEMENT'));
				$inventoryCombo->AddEvent(ON_CHANGE, "if (this.value == 'Y') { toggleYes('inventoryInStockInfo'); toggleYes('inventoryLowStockInfo'); toggleYes('inventoryOutstockBuyInfo'); toggleYes('inventoryOutstockMsgInfo'); } else { toggleNo('inventoryInStockInfo'); toggleNo('inventoryLowStockInfo'); toggleNo('inventoryOutstockBuyInfo'); toggleNo('inventoryOutstockMsgInfo'); };");
				$form_content .= "<tr><td class=\"syntacts-form-row product_inventory_field\"><label for=\"inventory\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row product_inventory_field\" colspan=\"3\">".$inventoryCombo->Get()."</td></tr>";
				
				// In Stock
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('STORE_INSTOCK')) {
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
				if (empty($helpString)) {
					$helpString = _t('STORE_INSTOCK');
				}
				$instock = (isset($pageInfo['instock']) && (int)$pageInfo['instock'] > 0 ? $pageInfo['instock'] : '');
				$instockEntry =& Piwi::CreateWidget('Entry', 'instock', $instock);
				$instockEntry->SetTitle(_t('STORE_INSTOCK'));
				$instockEntry->SetStyle('direction: ltr; width: 300px;');
				$form_content .= "<tr style=\"display: ".($inventory == "Y" ? "; " : "none; ")."\" id=\"inventoryInStockInfo\"><td class=\"syntacts-form-row product_instock_field\"><label for=\"instock\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row product_instock_field\" colspan=\"3\">".$instockEntry->Get()."</td></tr>";
				
				// Low Stock Notify
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('STORE_LOWSTOCK')) {
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
				if (empty($helpString)) {
					$helpString = _t('STORE_LOWSTOCK');
				}
				$lowstock = (isset($pageInfo['lowstock']) && (int)$pageInfo['lowstock'] > 0 ? $pageInfo['lowstock'] : '');
				$lowstockEntry =& Piwi::CreateWidget('Entry', 'lowstock', $lowstock);
				$lowstockEntry->SetTitle(_t('STORE_LOWSTOCK'));
				$lowstockEntry->SetStyle('direction: ltr; width: 300px;');
				$form_content .= "<tr style=\"display: ".($inventory == "Y" ? "; " : "none; ")."\" id=\"inventoryLowStockInfo\"><td class=\"syntacts-form-row product_lowstock_field\"><label for=\"lowstock\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row product_lowstock_field\" colspan=\"3\">".$lowstockEntry->Get()."</td></tr>";
				
				// Out Stock Purchasing
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('STORE_OUTSTOCKBUY')) {
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
				if (empty($helpString)) {
					$helpString = _t('STORE_OUTSTOCKBUY');
				}
				$outstockbuy = (isset($pageInfo['outstockbuy']) ? $pageInfo['outstockbuy'] : 'N');
				$outstockbuyCombo =& Piwi::CreateWidget('Combo', 'outstockbuy');
				$outstockbuyCombo->AddOption(_t('GLOBAL_YES'), 'Y');
				$outstockbuyCombo->AddOption(_t('GLOBAL_NO'), 'N');
				$outstockbuyCombo->SetDefault($outstockbuy);
				$outstockbuyCombo->setTitle(_t('STORE_OUTSTOCKBUY'));
				$form_content .= "<tr style=\"display: ".($inventory == "Y" ? "; " : "none; ")."\" id=\"inventoryOutstockBuyInfo\"><td class=\"syntacts-form-row product_outstockbuy_field\"><label for=\"outstockbuy\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row product_outstockbuy_field\" colspan=\"3\">".$outstockbuyCombo->Get()."</td></tr>";
				
				// Out Stock Message
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('STORE_OUTSTOCKMSG')) {
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
				if (empty($helpString)) {
					$helpString = _t('STORE_OUTSTOCKMSG');
				}
				$outstockmsg = (isset($pageInfo['outstockmsg']) ? $pageInfo['outstockmsg'] : "This product is sold out. Check back soon.");
				$outstockmsgEntry =& Piwi::CreateWidget('Entry', 'outstockmsg', $outstockmsg);
				$outstockmsgEntry->SetTitle(_t('STORE_OUTSTOCKMSG'));
				$outstockmsgEntry->SetStyle('direction: ltr; width: 300px;');
				$form_content .= "<tr style=\"display: ".($inventory == "Y" ? "; " : "none; ")."\" id=\"inventoryOutstockMsgInfo\"><td class=\"syntacts-form-row product_outstockmsg_field\"><label for=\"outstockmsg\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row product_outstockmsg_field\" colspan=\"3\">".$outstockmsgEntry->Get()."</td></tr>";

				// Internal Product Number
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('STORE_INTERNAL_PRODUCTNO')) {
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
				if (empty($helpString)) {
					$helpString = _t('STORE_INTERNAL_PRODUCTNO');
				}
				$internal_productno = (isset($pageInfo['internal_productno']) ? $pageInfo['internal_productno'] : '');
				$internal_productnoEntry =& Piwi::CreateWidget('Entry', 'internal_productno', $internal_productno);
				$internal_productnoEntry->SetTitle(_t('STORE_INTERNAL_PRODUCTNO'));
				$internal_productnoEntry->SetStyle('direction: ltr; width: 300px;');
				$form_content .= "<tr><td class=\"syntacts-form-row product_internal_productno_field\"><label for=\"internal_productno\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row product_internal_productno_field\" colspan=\"3\">".$internal_productnoEntry->Get()."</td></tr>";

				// Image
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('STORE_IMAGE')) {
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
				if (empty($helpString)) {
					$helpString = _t('STORE_IMAGE');
				}
				$image = (isset($pageInfo['image'])) ? $pageInfo['image'] : '';
				$main_image_src = '';
				if (isset($pageInfo['image']) && !empty($pageInfo['image'])) {
					$pageInfo['image'] = $xss->filter(strip_tags($pageInfo['image']));
					if (substr(strtolower($pageInfo['image']), 0, 4) == "http") {
						if (substr(strtolower($pageInfo['image']), 0, 7) == "http://") {
							$main_image_src = explode('http://', $pageInfo['image']);
							foreach ($main_image_src as $img_src) {
								if (!empty($img_src)) {
									$main_image_src = 'http://'.$img_src;
									break;
								}
							}
						} else {
							$main_image_src = explode('https://', $pageInfo['image']);
							foreach ($main_image_src as $img_src) {
								if (!empty($img_src)) {
									$main_image_src = 'https://'.$img_src;
									break;
								}
							}
						}
						if (strpos(strtolower($main_image_src), 'data/files/') !== false) {
							$main_image_src = 'image_thumb.php?uri='.urlencode($main_image_src);
						}
					} else {
						$thumb = Jaws_Image::GetThumbPath($pageInfo['image']);
						$medium = Jaws_Image::GetMediumPath($pageInfo['image']);
						if (file_exists(JAWS_DATA . 'files'.$thumb)) {
							$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$thumb;
						} else if (file_exists(JAWS_DATA . 'files'.$medium)) {
							$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$medium;
						} else if (file_exists(JAWS_DATA . 'files'.$pageInfo['image'])) {
							$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$pageInfo['image'];
						}
					}
				}
				$image_preview = '';
				if (!empty($main_image_src)) { 
					$image_preview .= "<br /><img border=\"0\" src=\"".$main_image_src."\" width=\"80\"".(strtolower(substr($main_image_src, -3)) == 'gif' || strtolower(substr($main_image_src, -3)) == 'png' || strtolower(substr($main_image_src, -3)) == 'bmp' ? ' height="80"' : '')." align=\"left\" style=\"padding: 5px visibility: visible;\" id=\"main_image_src\"><br /><b><a id=\"imageDelete\" href=\"javascript:void(0);\" onclick=\"document.getElementById('main_image_src').style.visibility = 'hidden'; document.getElementById('image').value = '';\">Delete</a></b>";
				}
				$form_content .= '<tr>';
				$form_content .= '<td valign="top" colspan="4">';
				$form_content .= '<table border="0" width="100%" cellpadding="0" cellspacing="0">';
				$imageScript = "<script type=\"text/javascript\">Event.observe(window, \"load\",function(){addFileToPost('Store', 'NULL', 'NULL', 'main_image', 'image', 1, 500, 34);});</script>";
				$imageHidden =& Piwi::CreateWidget('HiddenEntry', 'image', $image);
		        $form_content .= "<tr><td class=\"syntacts-form-row\"><div id=\"insertMedia\"><b>".$helpString."</b></div>".$image_preview."</td><td class=\"syntacts-form-row\" colspan=\"3\"><div id=\"main_image\" style=\"float: left; width: 500px;\"></div>".$imageScript.$imageHidden->Get()."</td></tr>";

				// Template
				$templatesHTML = '';
				$template = (isset($pageInfo['template'])) ? $pageInfo['template'] : '';
				if ($account === true) {
					$templateHidden =& Piwi::CreateWidget('HiddenEntry', 'template', $template);
					$form_content .= $templateHidden->Get()."\n";
				} else {
					// initialize template
					$ttpl =& new Jaws_Template('gadgets/Store/templates/');
					$ttpl->Load('datagrid.html');
					$ttpl->SetBlock('datagrid');
					$ttpl->SetVariable('label', _t('GLOBAL_TEMPLATE'));
					
					// Free text search
					$searchButton =& Piwi::CreateWidget('Button', 'searchTemplatesButton', _t('GLOBAL_SEARCH'), STOCK_SEARCH);
					$searchButton->AddEvent(ON_CLICK, "javascript: searchTemplates('product');");
					$ttpl->SetVariable('search', $searchButton->Get());

					$search = '';
					$searchEntry =& Piwi::CreateWidget('Entry', 'search_templates', $search);
					$searchEntry->SetStyle('zwidth: 100%; width: 140px;');
					$ttpl->SetVariable('search_field', $searchEntry->Get());
					
					//Status filter
					/*
					$status = '';
					$statusCombo =& Piwi::CreateWidget('Combo', 'status');
					$statusCombo->setId('status');
					$statusCombo->AddOption('&nbsp;', '');
					$statusCombo->SetDefault($status);
					$statusCombo->AddEvent(ON_CHANGE, "javascript: searchTemplates('product');");
					$ttpl->SetVariable('status_field', $statusCombo->Get());
					*/
					
					$ttpl->SetVariable('entries', $this->TemplatesDataGrid('product'));

					// TODO: Template uploading
					/*
					$addTemplate =& Piwi::CreateWidget('Button', 'add_template', _t('STORE_ADD_PRODUCT_TEMPLATE'), STOCK_ADD);
					$addTemplate->AddEvent(ON_CLICK, "javascript: addTemplate('".$GLOBALS['app']->GetSiteURL() ."/admin.php?gadget=Store&action=E_form&gadgetscope=product', '"._t('STORE_ADD_PRODUCT_TEMPLATE')."');");
					$ttpl->SetVariable('add_item', $addTemplate->Get());
					*/
					
					$ttpl->SetVariable('on_load_function', "Event.observe(window, 'load',function(){getTemplatesData($('templates_datagrid').getCurrentPage(), 'product');});");
					$ttpl->ParseBlock('datagrid');
					$templatesHTML = "<tr><td class=\"syntacts-form-row product_template_field\" colspan=\"4\" valign=\"top\">";
					$templatesHTML .= $ttpl->Get();
					$templatesHTML .= "</td></tr>";
					$form_content .= $templatesHTML;
				}
				
				// description
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('STORE_DESCRIPTIONFIELD')) {
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
				if (empty($helpString)) {
					$helpString = _t('STORE_DESCRIPTIONFIELD');
				}
				//$pageInfo['description'] = $this->ParseText($pageInfo['description'], 'Store');
				$description = (isset($pageInfo['description'])) ? $pageInfo['description'] : '';
				$editor =& $GLOBALS['app']->LoadEditor('Store', 'description', $description, false);
				$editor->TextArea->SetStyle('width: 100%;');
				//$editor->SetWidth('490px');
				$form_content .= "<tr><td class=\"syntacts-form-row product_description_field\"><label for=\"description\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row product_description_field\" colspan=\"3\">".$editor->Get()."</td></tr>";

				// send attribute records
				//$amenities = $model->GetAttributeTypes(null, 'title', 'ASC', false, $OwnerID);
				$amenitiesHTML = '';
				//if (!Jaws_Error::IsError($amenities)) {
					// initialize template
					$ttpl =& new Jaws_Template('gadgets/Store/templates/');
					$ttpl->Load('datagrid.html');
					$ttpl->SetBlock('datagrid');
					$ttpl->SetVariable('label', _t('STORE_MENU_ATTRIBUTE'));
					
					// Free text search
					$searchButton =& Piwi::CreateWidget('Button', 'searchAttributeButton', _t('GLOBAL_SEARCH'), STOCK_SEARCH);
					$searchButton->AddEvent(ON_CLICK, "javascript: searchAttribute(true);");
					$ttpl->SetVariable('search', $searchButton->Get());

					$search = '';
					$searchEntry =& Piwi::CreateWidget('Entry', 'attributes_search', $search);
					$searchEntry->SetStyle('zwidth: 100%; width: 140px;');
					$ttpl->SetVariable('search_field', $searchEntry->Get());
					
					//Status filter
					$status = '';
					$statusCombo =& Piwi::CreateWidget('Combo', 'attributes_status');
					$statusCombo->setId('attributes_status');
					$statusCombo->AddOption('All Types', '');
					$types = $model->GetAttributeTypes(null, 'title', 'ASC', false, $OwnerID);
					if (!Jaws_Error::IsError($types)) {
						foreach($types as $type) {
							$statusCombo->AddOption($type['title'], $type['id']);
						}	
					}
					$statusCombo->AddOption('Templates', 'template');
					$statusCombo->SetDefault($status);
					$statusCombo->AddEvent(ON_CHANGE, 'javascript: searchAttribute(true);');
					$ttpl->SetVariable('status_field', $statusCombo->Get());
					
					$ttpl->SetVariable('entries', $this->AttributeDataGrid(true));

					$addAttribute =& Piwi::CreateWidget('Button', 'add_attribute', _t('STORE_ADD_ATTRIBUTE'), STOCK_ADD);
					if ($account === false) {
						/*
						$addTemplate =& Piwi::CreateWidget('Button', 'add_template', _t('STORE_ADD_ATTRIBUTE_TEMPLATE'), STOCK_ADD);
						$addTemplate->AddEvent(ON_CLICK, "javascript: addTemplate('".$GLOBALS['app']->GetSiteURL() ."/admin.php?gadget=Store&action=E_form', '"._t('STORE_ADD_ATTRIBUTE_TEMPLATE')."');");
						$add_item = $addTemplate->Get().'&nbsp;&nbsp;';
						*/
						$addAttribute->AddEvent(ON_CLICK, "javascript: addAttribute('".$GLOBALS['app']->GetSiteURL() ."/admin.php?gadget=Store&action=B_form2', '"._t('STORE_ADD_ATTRIBUTE')."');");
						$add_item .= $addAttribute->Get();
					} else {
						//$addPage->AddEvent(ON_CLICK, "javascript: window.open('".$base_url."?gadget=Store&amp;action=".$account_prefix."form','','scrollbars=1,menubar=0,toolbar=0,location=0,status=1');");
						$addAttribute->AddEvent(ON_CLICK, "javascript: addAttribute('".$GLOBALS['app']->GetSiteURL() ."/index.php?gadget=Store&action=account_B_form2', '"._t('STORE_ADD_ATTRIBUTE')."');");
						$add_item = $addAttribute->Get();
					}
					$ttpl->SetVariable('add_item', $add_item);
					$ttpl->SetVariable('on_load_function', "Event.observe(window, 'load',function(){getAttributeData($('attributes_datagrid').getCurrentPage(), true);});");
					$ttpl->ParseBlock('datagrid');
					$amenitiesHTML = "<tr><td class=\"syntacts-form-row product_attributes_field\" colspan=\"4\" valign=\"top\">";
					$amenitiesHTML .= $ttpl->Get();
					$amenitiesHTML .= "</td></tr>";
					$form_content .= $amenitiesHTML;
				/*
				} else {
					$page = _t('STORE_ERROR_ASPPAGE_NOT_RETRIEVED', $amenities->GetMessage())."\n";
				}
				*/
											
				// send possible sales
				// TODO: datagrid this!
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('STORE_MENU_SALES')) {
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
				if (empty($helpString)) {
					$helpString = _t('STORE_MENU_SALES');
				}
				if ($account === false) {
					$sales = $model->GetSales();
				} else {
					$sales = $model->GetSales(null, 'title', 'ASC', false, (int)$GLOBALS['app']->Session->GetAttribute('user_id'));
				}
				$salesHTML = '';
				if (!Jaws_Error::IsError($sales)) {
					$salesFound = false;
					$loopCount = 0;
					foreach($sales as $sale) {		            
						if (
							$GLOBALS['app']->Session->GetPermission('Store', 'ManageProducts') || 
							$sale['ownerid'] == $GLOBALS['app']->Session->GetAttribute('user_id') || 
							$sale['ownerid'] == 0
						) {
							$salesFound = true;
							// Build Sales checkboxes
							if ($loopCount % 2 == 0 && $loopCount > 0) {
								$loopCount = 1;
								$salesHTML .= "</tr><tr>";
							} else {
								$loopCount++;
							}
							$salesHTML .= "<td style=\"padding: 3px;\"><input type=\"checkbox\" name=\"productsales\" value=\"".$sale['id']."\"";
							$salesChecked = false;
							if (isset($pageInfo['sales']) && !empty($pageInfo['sales'])) {
								$propSales = explode(',', $pageInfo['sales']);
								foreach($propSales as $propCategory) {		            
									If ($sale['id'] == (int)$propCategory) { 
										$salesChecked = true;
										$salesHTML .= "checked=\"checked\"";
										break;
									}
								}
							} 
							if ($linkid == $sale['id'] && $salesChecked === false) {
								$salesHTML .= "checked=\"checked\"";
							}
							$salesHTML .= ">&nbsp;";
							if (isset($sale['description']) && !empty($sale['description'])) {
								$salesHTML .= "<a href=\"javascript: void(0);\" title=\"".$sale['description']."\">".$sale['title']."</a>";
							} else {
								$salesHTML .= $sale['title'];
							}
							$salesHTML .= "<br />&nbsp;&nbsp;&nbsp;<i>".$date->Format($sale['startdate'])." - ".$date->Format($sale['enddate'])."</i>";
							if (isset($pageInfo['price']) && !empty($pageInfo['price']) && $pageInfo['price'] > 0) {
								$sale_string = '';
								if ($sale['discount_amount'] > 0) {
									$sale_string = '$'.number_format(number_format($sale['discount_amount'], 2, '.', ',')).' Off'.(!empty($sale['coupon_code']) ? ' Order Total' : ' Product Price');
								} else if ($sale['discount_percent'] > 0) {
									$sale_string = number_format($sale['discount_percent'], 2, '.', ',').'% Off'.(!empty($sale['coupon_code']) ? ' Order Total' : ' Product Price');
								} else if ($sale['discount_newprice'] > 0) {
									$sale_string = (!empty($sale['coupon_code']) ? 'Makes Order Total: ' : 'Makes Product Price: ').'$'.number_format($sale['discount_newprice'], 2, '.', ',');
								}
								if (!empty($sale_string)) {
									$salesHTML .= "<br />&nbsp;&nbsp;&nbsp;<b>". $xss->parse($sale_string)."</b>";
								}
							}
							$salesHTML .= "&nbsp;&nbsp;&nbsp;</td>";
						}
					}
					if ($salesFound === false) {
						if ($account === true) {
							$salesHTML = "No sales currently exist, <a href=\"index.php?gadget=Store&action=account_C_form\">CREATE ONE</a>.";
						} else {
							$salesHTML = "No sales currently exist, <a href=\"admin.php?gadget=Store&action=C_form\">CREATE ONE</a>.";
						}
					}
				} else {
					$page = _t('STORE_ERROR_ASPPAGE_NOT_RETRIEVED', $sale->GetMessage())."\n";
				}
				$form_content .= "<tr><td colspan=\"4\" class=\"syntacts-form-row product_sales_field\"><label for=\"sales\"><nobr>".$helpString."</nobr></label></td></tr><tr><td class=\"syntacts-form-row product_sales_field\" colspan=\"4\"><table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\"><tr>".$salesHTML."</tr></table></td></tr>";

				// product images
				// TODO: FileBrowser datagrid implementation with multiple-select, drag n drop sorting
				if (isset($pageInfo['id']) && !empty($pageInfo['id'])) {
					$posts = $model->GetAllPostsOfProduct($pageInfo['id']);
					//$postsHTML = '';
					if (!Jaws_Error::IsError($posts)) {
						$user_post_limit = $GLOBALS['app']->Registry->Get('/gadgets/Store/user_post_limit');
						/*
						// initialize template
						$ttpl =& new Jaws_Template('gadgets/Store/templates/');
						$ttpl->Load('datagrid.html');
						$ttpl->SetBlock('datagrid');
						$ttpl->SetVariable('label', _t('STORE_POSTS'));
						
						// Free text search
						$searchButton =& Piwi::CreateWidget('Button', 'searchPostsButton', _t('GLOBAL_SEARCH'), STOCK_SEARCH);
						$searchButton->AddEvent(ON_CLICK, "javascript: searchPosts(true);");
						$ttpl->SetVariable('search', $searchButton->Get());

						$search = '';
						$searchEntry =& Piwi::CreateWidget('Entry', 'posts_search', $search);
						$searchEntry->SetStyle('zwidth: 100%; width: 140px;');
						$ttpl->SetVariable('search_field', $searchEntry->Get());
						
						//Status filter
						$status = '';
						$statusCombo =& Piwi::CreateWidget('Combo', 'posts_status');
						$statusCombo->setId('posts_status');
						$statusCombo->AddOption('&nbsp;', '');
						$statusCombo->SetDefault($status);
						$statusCombo->AddEvent(ON_CHANGE, 'javascript: searchAttribute(true);');
						$ttpl->SetVariable('status_field', $statusCombo->Get());
						
						$ttpl->SetVariable('entries', $this->PostsDatagrid());

						if ((int)$user_post_limit > (count($posts))) {
							$addPost =& Piwi::CreateWidget('Button', 'add_post', _t('GLOBAL_ADD'), STOCK_ADD);
							if ($account === false) {
								$addPost->AddEvent(ON_CLICK, "javascript: addPost('".$GLOBALS['app']->GetSiteURL() ."/admin.php?gadget=Store&action=A_form2', '"._t('GLOBAL_ADD')."');");
							} else {
								$addPost->AddEvent(ON_CLICK, "javascript: addPost('".$GLOBALS['app']->GetSiteURL() ."/index.php?gadget=Store&action=account_A_form2', '"._t('GLOBAL_ADD')."');");
							}
							$ttpl->SetVariable('add_item', $addPost->Get());
						} else {
							$ttpl->SetVariable('add_item', '');
						}
						$ttpl->SetVariable('on_load_function', "Event.observe(window, 'load',function(){getPostsData();});");
						$ttpl->ParseBlock('datagrid');
						$postsHTML = "<tr><td class=\"syntacts-form-row\" colspan=\"4\" valign=\"top\">";
						$postsHTML .= $ttpl->Get();
						$postsHTML .= "</td></tr>";
						*/
						$stpl->SetBlock('form/posts');
						$stpl->SetVariable('base_url', $base_url);
						$stpl->SetVariable('actionprefix', $submit_vars['ACTIONPREFIX']);
						if ($account === false) {
							$stpl->SetBlock('form/posts/admin');
							$stpl->SetVariable('linkid', $pageInfo['id']);
							$stpl->ParseBlock('form/posts/admin');
						} else if ((int)$user_post_limit > (count($posts))) {
							$stpl->SetBlock('form/posts/account');
							$stpl->SetVariable('linkid', $pageInfo['id']);
							$stpl->SetVariable('post_limit', (int)$user_post_limit);
							$stpl->ParseBlock('form/posts/account');
						}
						if (!count($posts) <= 0) {	
							reset($posts);
							$i = 0;
							foreach ($posts as $post) {
								$background = '';
								if ($i == 0) {
									$background = "background: #EDF3FE; border-top: dotted 1pt #E2E2E2; ";
								} else if (($i % 2) == 0) {
									$background = "background: #EDF3FE; ";
								}
								$stpl->SetBlock('form/posts/post');
								$stpl->SetVariable('id', $post['id']);
								$stpl->SetVariable('background', $background);
								$stpl->SetVariable('linkid', $pageInfo['id']);
								$stpl->SetVariable('onclick', ($account === true ? "showPostWindow('".$GLOBALS['app']->GetSiteURL() ."index.php?gadget=Store&amp;action=account_A_form2&amp;linkid=".$pageInfo['id']."&amp;id=".$post['id']."', 'Edit Text/Image');" : "location.href='admin.php?gadget=Store&amp;action=A_form2&amp;linkid=".$pageInfo['id']."&amp;id=".$post['id']."';"));
								$main_image_src = '';
								if (isset($post['image']) && !empty($post['image'])) {
									$post['image'] = $xss->filter(strip_tags($post['image']));
									if (substr(strtolower($post['image']), 0, 4) == "http") {
										if (substr(strtolower($post['image']), 0, 7) == "http://") {
											$main_image_src = explode('http://', $post['image']);
											foreach ($main_image_src as $img_src) {
												if (!empty($img_src)) {
													$main_image_src = 'http://'.$img_src;
													break;
												}
											}
										} else {
											$main_image_src = explode('https://', $post['image']);
											foreach ($main_image_src as $img_src) {
												if (!empty($img_src)) {
													$main_image_src = 'https://'.$img_src;
													break;
												}
											}
										}
										if (strpos(strtolower($main_image_src), 'data/files/') !== false) {
											$main_image_src = 'image_thumb.php?uri='.urlencode($main_image_src);
										}
									} else {
										$thumb = Jaws_Image::GetThumbPath($post['image']);
										$medium = Jaws_Image::GetMediumPath($post['image']);
										if (file_exists(JAWS_DATA . 'files'.$thumb)) {
											$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$thumb;
										} else if (file_exists(JAWS_DATA . 'files'.$medium)) {
											$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$medium;
										} else if (file_exists(JAWS_DATA . 'files'.$post['image'])) {
											$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$post['image'];
										}
									}
								}
								if (!empty($main_image_src)) {
									$stpl->SetBlock('form/posts/post/image');
									$stpl->SetVariable('image_src', $main_image_src);
									$stpl->ParseBlock('form/posts/post/image');
								}
								$title = '';
								if (isset($post['title']) && !empty($post['title'])) {
									$title = $post['title'];
									$stpl->SetBlock('form/posts/post/title');
									$stpl->SetVariable('title', strip_tags(substr($title, 0, 80)));
									$stpl->ParseBlock('form/posts/post/title');
								}
								$description = '';
								if (isset($post['description']) && !empty($post['description'])) {
									$description = $post['description'];
									$stpl->SetBlock('form/posts/post/description');
									$stpl->SetVariable('description', strip_tags(substr($post['description'], 0, 150)));
									$stpl->ParseBlock('form/posts/post/description');
								}
								$stpl->ParseBlock('form/posts/post');
								$i++;
							}
						} else {
							$stpl->SetBlock('form/posts/no_items');
							$stpl->SetVariable('no_items_msg', _t('STORE_NO_ITEMS_MSG', 'images'));
							$stpl->ParseBlock('form/posts/no_items');
						}
						$stpl->ParseBlock('form/posts');
					} else {
						$page = _t('STORE_ERROR_ASPPAGE_NOT_RETRIEVED', $posts->GetMessage())."\n";
					}
				}
			
				if ($account === false) {
					$stpl->SetBlock('form/category_admin');
					$stpl->ParseBlock('form/category_admin');
				} else {
					$stpl->SetBlock('form/category_account');
					$stpl->ParseBlock('form/category_account');
				}
				
				if (!isset($page)) {
					// send requesting URL to syntacts
					$stpl->SetVariable('HTTP_REFERER', $GLOBALS['app']->GetSiteURL());
					$stpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . "/");
					//$stpl->SetVariable('DPATH', JAWS_DPATH);
					$stpl->SetVariable('actionprefix', $submit_vars['ACTIONPREFIX']);
					$stpl->SetVariable('gadget', 'Store');
					$stpl->SetVariable('CLOSE_BUTTON', $submit_vars['CLOSE_BUTTON']);
					$stpl->SetVariable('SUBMIT_BUTTON', $submit_vars['SUBMIT_BUTTON']);
					$stpl->SetVariable('controller', $base_url);
					$stpl->SetVariable('content', $form_content);
					$stpl->ParseBlock('form');
					$page = $stpl->Get();
				}
			} else {
				$page = _t('STORE_ERROR_ASPPAGE_NOT_RETRIEVED', $snoopy->error)."\n";
			}
			
			$tpl->SetVariable('content', $page);
		}
		
        $tpl->ParseBlock('gadget_product');

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
			$GLOBALS['app']->Session->CheckPermission('Store', 'default');
		} else {
			$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
			if (!$GLOBALS['app']->Session->GetPermission('Store', 'default')) {
				if (!$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Store', 'OwnProduct')) {
		            $GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
					$userHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
					return $userHTML->DefaultAction();
				}
			}
		}

		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
				
		$tpl =& new Jaws_Template('gadgets/Store/templates/');
        $tpl->Load('admin.html');

        $tpl->SetBlock('gadget_product');

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
			$submit_vars['ACTIONPREFIX'] = 'account_';
			$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
			$base_url = 'index.php';
		}
		$syntactsUrl = $GLOBALS['app']->getSyntactsAdminUrl("Store/admin_Store_A_form2");

		$tpl->SetVariable('workarea-style', 'style="margin-top: 30px;" ');

		if ($syntactsUrl) {
			include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
			$snoopy = new Snoopy('Store');
			$model = $GLOBALS['app']->LoadGadget('Store', 'Model');
								
			$submit_url = $syntactsUrl;
			
			$id = $request->get('id', 'get');
			$linkid = $request->get('linkid', 'get');

			// send post records
			if (!empty($id)) {
				$post = $model->GetPost($id);
		        if (!Jaws_Error::IsError($post) && ($GLOBALS['app']->Session->GetPermission('Store', 'ManageProducts') || $post['ownerid'] == $OwnerID)) {
					$i = 0;
					$j = 0;
					$submit_vars['0:cols'] = 15;
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
					$page = _t('STORE_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403'))."\n";
				}
								
			} else if (!empty($linkid)) {
				// send highest sort_order
				$sql = "SELECT MAX([sort_order]) FROM [[product_posts]] ORDER BY [sort_order] DESC";
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
					Jaws_Header::Location($base_url . '?gadget=Store&action=Admin');
				}
			}

			// send editor HTML to syntacts
			$description = (isset($post['description'])) ? $post['description'] : '';
			$editor =& $GLOBALS['app']->LoadEditor('Store', 'description', $description, false);
			$editor->TextArea->SetStyle('width: 100%;');
			$editor->SetWidth('490px');

			$submit_vars['HTTP_REFERER'] = $GLOBALS['app']->GetSiteURL();
			$submit_vars['JAWS_URL'] = $GLOBALS['app']->GetJawsURL() . '/';
			$submit_vars['DPATH'] = '';
			$submit_vars['ID'] = $id;
			$submit_vars['LINKID'] = $linkid;
			
			if (!isset($page)) {
				if($snoopy->submit($submit_url,$submit_vars)) {
					//while(list($key,$val) = each($snoopy->headers))
						//echo $key.": ".$val."<br>\n";
					//echo "<p>\n";
					
					//$page = "<PRE>".htmlspecialchars($snoopy->results)."</PRE>\n";
					$page = $snoopy->results;
					$page = str_replace("__JAWS_EDITOR__", $editor->Get(), $page);
				} else {
					$page = _t('STORE_ERROR_ASPPAGE_NOT_RETRIEVED', $snoopy->error)."\n";
				}
			}

			$tpl->SetVariable('content', $page);
		}
		
        $tpl->ParseBlock('gadget_product');

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
     * We are on the product attribute list page
     *
     * @access public
     * @return string
     */
    function B($account = false)
    {
		if ($account === false) {
			$GLOBALS['app']->Session->CheckPermission('Store', 'default');
		} else {
			$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
			if (!$GLOBALS['app']->Session->GetPermission('Store', 'default')) {
				if (!$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Store', 'OwnProduct')) {
		            $GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
					$userHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
					return $userHTML->DefaultAction();
				}
			}
		}
        $tpl = new Jaws_Template('gadgets/Store/templates/');
        $tpl->Load('admin.html');
        $tpl->SetBlock('attributes_admin');
        
		$request =& Jaws_Request::getInstance();
		$action = $request->get('action', 'get');
		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=CustomPage&action=account_SetGBRoot');
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

        $tpl->SetVariable('grid', $this->AttributeDataGrid());

        $toolBar   =& Piwi::CreateWidget('HBox');

        $deleteAll =& Piwi::CreateWidget('Button', 'deleteAllAttributes',
                                         _t('GLOBAL_DELETE'),
                                         STOCK_DELETE);
        $deleteAll->AddEvent(ON_CLICK,
                             "javascript: massiveDeleteAttribute('"._t('STORE_CONFIRM_MASIVE_DELETE_ATTRIBUTE')."');");

        $toolBar->Add($deleteAll);

        $tpl->SetVariable('tools', $toolBar->Get());
                
        //Status filter
		$status = '';
		$statusCombo =& Piwi::CreateWidget('Combo', 'attributes_status');
		$statusCombo->setId('attributes_status');
		$statusCombo->AddOption('All Types', '');
		$types = $model->GetAttributeTypes(null, 'title', 'ASC', false, $OwnerID);
		if (!Jaws_Error::IsError($types)) {
			foreach($types as $type) {
				$statusCombo->AddOption($type['title'], $type['id']);
			}	
		}
		$statusCombo->AddOption('Templates', 'template');
		$statusCombo->SetDefault($status);
        $statusCombo->AddEvent(ON_CHANGE, 'javascript: searchAttribute();');
        $tpl->SetVariable('status', _t('STORE_ACTIVE'));
        $tpl->SetVariable('status_field', $statusCombo->Get());

        // Free text search
        $searchButton =& Piwi::CreateWidget('Button', 'searchButton', _t('GLOBAL_SEARCH'), STOCK_SEARCH);
        $searchButton->AddEvent(ON_CLICK, 'javascript: searchAttribute();');
        $tpl->SetVariable('search', $searchButton->Get());

        $search = '';
        $searchEntry =& Piwi::CreateWidget('Entry', 'attributes_search', $search);
        $searchEntry->SetStyle('zwidth: 100%;');
        $tpl->SetVariable('search_field', $searchEntry->Get());

        $tpl->SetVariable('entries', $this->AttributeDatagrid());

        $addPage =& Piwi::CreateWidget('Button', 'add_attribute', _t('STORE_ADD_ATTRIBUTE'), STOCK_ADD);
		if ($account === false) {
			$addPage->AddEvent(ON_CLICK, "javascript: window.location = '".BASE_SCRIPT."?gadget=Store&amp;action=B_form';");
        } else {
			$addPage->AddEvent(ON_CLICK, "javascript: window.location = '".$base_url."?gadget=Store&amp;action=account_B_form';");
		}
		$tpl->SetVariable('add_attribute', $addPage->Get());

        $tpl->ParseBlock('attributes_admin');

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
			$GLOBALS['app']->Session->CheckPermission('Store', 'default');
		} else {
			$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
			if (!$GLOBALS['app']->Session->GetPermission('Store', 'default')) {
				if (!$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Store', 'OwnProduct')) {
		            $GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
					$userHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
					return $userHTML->DefaultAction();
				}
			}
		}
		
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$request =& Jaws_Request::getInstance();
		$action = $request->get('action', 'get');
				
		$tpl =& new Jaws_Template('gadgets/Store/templates/');
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

        $tpl->SetBlock('gadget_product');

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
		$syntactsUrl = $GLOBALS['app']->getSyntactsAdminUrl("Store/admin_Store_B_form");

		$tpl->SetVariable('workarea-style', 'style="margin-top: 30px;" ');

		if ($syntactsUrl) {
			include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
			$snoopy = new Snoopy('Store');
			$model = $GLOBALS['app']->LoadGadget('Store', 'Model');
								
			$submit_url = $syntactsUrl;
			
			$id = $request->get('id', 'get');

			// send post records
			if (!empty($id)) {
				$post = $model->GetAttribute($id);
		        if (!Jaws_Error::IsError($post) && ($GLOBALS['app']->Session->GetPermission('Store', 'ManageProducts') || $post['ownerid'] == $GLOBALS['app']->Session->GetAttribute('user_id'))) {
					$i = 0;
					$j = 0;
					$submit_vars['0:cols'] = 12;
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
                    //return new Jaws_Error(_t('STORE_ERROR_POST_NOT_FOUND'), _t('STORE_NAME'));
				}
								
			}

			// send editor HTML to syntacts
			$description = (isset($post['description'])) ? $post['description'] : '';
			$editor =& $GLOBALS['app']->LoadEditor('Store', 'description', $description, false);
			$editor->TextArea->SetStyle('width: 100%;');
			$editor->SetWidth('490px');
			
			// send Post records
			if ($account === false) {
				$posts = $model->GetAttributeTypes();
			} else {
				$posts = $model->GetAttributeTypes(null, 'title', 'ASC', false, (int)$GLOBALS['app']->Session->GetAttribute('user_id'));
			}
			
			if (!Jaws_Error::IsError($posts)) {
				$i = 0;
				$j = 0;
				$submit_vars['1:cols'] = 9;
				foreach($posts as $type) {		            
					if ($GLOBALS['app']->Session->GetPermission('Store', 'ManageProducts') || $type['ownerid'] == $GLOBALS['app']->Session->GetAttribute('user_id') || $type['ownerid'] == 0) {
						foreach ($type as $p => $pv) {
							$submit_vars[SYNTACTS_DB ."1:$j:$i"] = $xss->filter($pv);
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
				//return new Jaws_Error(_t('STORE_ERROR_POST_NOT_FOUND'), _t('STORE_NAME'));
			}
			
			$submit_vars['HTTP_REFERER'] = $GLOBALS['app']->GetSiteURL();
			$submit_vars['JAWS_URL'] = $GLOBALS['app']->GetJawsURL() . '/';
			$submit_vars['DPATH'] = '';
			$submit_vars['ID'] = $id;
			

			if (!isset($page)) {
				if($snoopy->submit($submit_url,$submit_vars)) {
					//while(list($key,$val) = each($snoopy->headers))
						//echo $key.": ".$val."<br>\n";
					//echo "<p>\n";
					
					//$page = "<PRE>".htmlspecialchars($snoopy->results)."</PRE>\n";
					$page = $snoopy->results;
					$page = str_replace("__JAWS_EDITOR__", $editor->Get(), $page);
				} else {
					$page = _t('STORE_ERROR_ASPPAGE_NOT_RETRIEVED', $snoopy->error)."\n";
				}
			}

			$tpl->SetVariable('content', $page);
		}
		
        $tpl->ParseBlock('gadget_product');

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
     * We are on the attribute types list page
     *
     * @access public
     * @return string
     */
    function B2($account = false)
    {
		if ($account === false) {
			$GLOBALS['app']->Session->CheckPermission('Store', 'default');
		} else {
			$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
			if (!$GLOBALS['app']->Session->GetPermission('Store', 'default')) {
				if (!$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Store', 'OwnProduct')) {
		            $GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
					$userHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
					return $userHTML->DefaultAction();
				}
			}
		}

        $tpl = new Jaws_Template('gadgets/Store/templates/');
        $tpl->Load('admin.html');
        $tpl->SetBlock('attribute_types_admin');
        
		$request =& Jaws_Request::getInstance();
		$action = $request->get('action', 'get');
        
		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=CustomPage&action=account_SetGBRoot');
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

        $tpl->SetVariable('grid', $this->AttributeTypesDataGrid());

        $toolBar   =& Piwi::CreateWidget('HBox');

        $deleteAll =& Piwi::CreateWidget('Button', 'deleteAllAttributeTypes',
                                         _t('GLOBAL_DELETE'),
                                         STOCK_DELETE);
        $deleteAll->AddEvent(ON_CLICK,
                             "javascript: massiveDeleteAttributeTypes('"._t('STORE_CONFIRM_MASIVE_DELETE_ATTRIBUTETYPES')."');");

        $toolBar->Add($deleteAll);

        $tpl->SetVariable('tools', $toolBar->Get());
                
        //Status filter
        $status = '';
        $statusCombo =& Piwi::CreateWidget('Combo', 'attribute_types_status');
        $statusCombo->setId('attribute_types_status');
        $statusCombo->AddOption('&nbsp;', '');
        $statusCombo->AddOption(_t('STORE_PUBLISHED'), 'Y');
        $statusCombo->AddOption(_t('STORE_DRAFT'), 'N');
        $statusCombo->SetDefault($status);
        $statusCombo->AddEvent(ON_CHANGE, 'javascript: searchAttributeType();');
        $tpl->SetVariable('status', _t('STORE_ACTIVE'));
        $tpl->SetVariable('status_field', $statusCombo->Get());

        // Free text search
        $searchButton =& Piwi::CreateWidget('Button', 'searchButton', _t('GLOBAL_SEARCH'), STOCK_SEARCH);
        $searchButton->AddEvent(ON_CLICK, 'javascript: searchAttributeType();');
        $tpl->SetVariable('search', $searchButton->Get());

        $search = '';
        $searchEntry =& Piwi::CreateWidget('Entry', 'attribute_types_search', $search);
        $searchEntry->SetStyle('zwidth: 100%;');
        $tpl->SetVariable('search_field', $searchEntry->Get());

        $tpl->SetVariable('entries', $this->AttributeTypesDataGrid());

        $addPage =& Piwi::CreateWidget('Button', 'add_attribute_types', _t('STORE_ADD_ATTRIBUTETYPES'), STOCK_ADD);
        if ($account === false) {
			$addPage->AddEvent(ON_CLICK, "javascript: window.location = '".BASE_SCRIPT."?gadget=Store&amp;action=B_form2';");
        } else {
			$addPage->AddEvent(ON_CLICK, "javascript: window.location = '".$base_url."?gadget=Store&amp;action=account_B_form2';");
		}
		$tpl->SetVariable('add_attribute_types', $addPage->Get());

        $tpl->ParseBlock('attribute_types_admin');

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
			$GLOBALS['app']->Session->CheckPermission('Store', 'default');
		} else {
			$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
			if (!$GLOBALS['app']->Session->GetPermission('Store', 'default')) {
				if (!$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Store', 'OwnProduct')) {
		            $GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
					$userHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
					return $userHTML->DefaultAction();
				}
			}
		}
		
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$request =& Jaws_Request::getInstance();
		$action = $request->get('action', 'get');
				
		$tpl =& new Jaws_Template('gadgets/Store/templates/');
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

        $tpl->SetBlock('gadget_product');

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
		$syntactsUrl = $GLOBALS['app']->getSyntactsAdminUrl("Store/admin_Store_B_form2");

		$tpl->SetVariable('workarea-style', 'style="margin-top: 30px;" ');
        $tpl->SetVariable('confirmAttributeDelete', _t('STORE_ATTRIBUTE_CONFIRM_DELETE'));

		if ($syntactsUrl) {
			include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
			$snoopy = new Snoopy('Store');
			$model = $GLOBALS['app']->LoadGadget('Store', 'Model');
								
			$submit_url = $syntactsUrl;
			
			$id = $request->get('id', 'get');

			// send post records
			if (!empty($id)) {
				$post = $model->GetAttributeType($id);
		        if (!Jaws_Error::IsError($post) && ($GLOBALS['app']->Session->GetPermission('Store', 'ManageProducts') || $post['ownerid'] == $GLOBALS['app']->Session->GetAttribute('user_id'))) {
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

				} else {
                    //return new Jaws_Error(_t('STORE_ERROR_POST_NOT_FOUND'), _t('STORE_NAME'));
				}
								
				// send post records
				if (!empty($id)) {
					$posts = $model->GetAttributesOfType($id);
					if (!Jaws_Error::IsError($post) && ($GLOBALS['app']->Session->GetPermission('Store', 'ManageProducts') || $post['ownerid'] == $GLOBALS['app']->Session->GetAttribute('user_id'))) {
						$i = 0;
						$j = 0;
						$submit_vars['2:cols'] = 12;
						foreach($posts as $type) {		            
							if ($GLOBALS['app']->Session->GetPermission('Store', 'ManageProducts') || $type['ownerid'] == $GLOBALS['app']->Session->GetAttribute('user_id') || $type['ownerid'] == 0) {
								foreach ($type as $p => $pv) {
									$submit_vars[SYNTACTS_DB ."2:$j:$i"] = $xss->filter($pv);
									$j++;
									if ($j > $submit_vars['2:cols']) {
										$j=0;
									}
								}
								$i++;
							}
						}
						$submit_vars['2:rows'] = $i-1;
					} else {
						//return new Jaws_Error(_t('STORE_ERROR_POST_NOT_FOUND'), _t('STORE_NAME'));
					}
				}
			}
			
			// send editor HTML to syntacts
			$description = (isset($post['description'])) ? $post['description'] : '';
			$editor =& $GLOBALS['app']->LoadEditor('Store', 'description', $description, false);
			$editor->TextArea->SetStyle('width: 100%;');
			$editor->SetWidth('490px');

			$submit_vars['HTTP_REFERER'] = $GLOBALS['app']->GetSiteURL();
			$submit_vars['JAWS_URL'] = $GLOBALS['app']->GetJawsURL() . '/';
			$submit_vars['DPATH'] = '';
			$submit_vars['ID'] = $id;
			
			if (!isset($page)) {
				if($snoopy->submit($submit_url,$submit_vars)) {
					//while(list($key,$val) = each($snoopy->headers))
						//echo $key.": ".$val."<br>\n";
					//echo "<p>\n";
					
					//$page = "<PRE>".htmlspecialchars($snoopy->results)."</PRE>\n";
					$page = $snoopy->results;
					$page = str_replace("__JAWS_EDITOR__", $editor->Get(), $page);
				} else {
					$page = _t('STORE_ERROR_ASPPAGE_NOT_RETRIEVED', $snoopy->error)."\n";
				}
			}

			$tpl->SetVariable('content', $page);
		}
		
        $tpl->ParseBlock('gadget_product');

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
     * We are on the sales list page
     *
     * @access public
     * @return string
     */
    function C($account = false)
    {
		if ($account === false) {
			$GLOBALS['app']->Session->CheckPermission('Store', 'default');
		} else {
			$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
			if (!$GLOBALS['app']->Session->GetPermission('Store', 'default')) {
				if (!$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Store', 'OwnProduct')) {
		            $GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
					$userHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
					return $userHTML->DefaultAction();
				}
			}
		}

        $tpl = new Jaws_Template('gadgets/Store/templates/');
        $tpl->Load('admin.html');
        $tpl->SetBlock('sales_admin');
        
		$request =& Jaws_Request::getInstance();
		$action = $request->get('action', 'get');
        
		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=CustomPage&action=account_SetGBRoot');
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

        $tpl->SetVariable('grid', $this->SalesDataGrid());

        $toolBar   =& Piwi::CreateWidget('HBox');

        $deleteAll =& Piwi::CreateWidget('Button', 'deleteAllSales',
                                         _t('GLOBAL_DELETE'),
                                         STOCK_DELETE);
        $deleteAll->AddEvent(ON_CLICK,
                             "javascript: massiveDeleteSales('"._t('STORE_CONFIRM_MASIVE_DELETE_SALES')."');");

        $toolBar->Add($deleteAll);

        $tpl->SetVariable('tools', $toolBar->Get());
                
        //Status filter
        $status = '';
        $statusCombo =& Piwi::CreateWidget('Combo', 'sales_status');
        $statusCombo->setId('sales_status');
        $statusCombo->AddOption('&nbsp;', '');
        $statusCombo->AddOption(_t('STORE_PUBLISHED'), 'Y');
        $statusCombo->AddOption(_t('STORE_DRAFT'), 'N');
        $statusCombo->SetDefault($status);
        $statusCombo->AddEvent(ON_CHANGE, 'javascript: searchSale();');
        $tpl->SetVariable('status', _t('STORE_ACTIVE'));
        $tpl->SetVariable('status_field', $statusCombo->Get());

        // Free text search
        $searchButton =& Piwi::CreateWidget('Button', 'searchButton', _t('GLOBAL_SEARCH'), STOCK_SEARCH);
        $searchButton->AddEvent(ON_CLICK, 'javascript: searchSale();');
        $tpl->SetVariable('search', $searchButton->Get());

        $search = '';
        $searchEntry =& Piwi::CreateWidget('Entry', 'sales_search', $search);
        $searchEntry->SetStyle('zwidth: 100%;');
        $tpl->SetVariable('search_field', $searchEntry->Get());

        $tpl->SetVariable('entries', $this->SalesDataGrid());

        $addPage =& Piwi::CreateWidget('Button', 'add_sales', _t('STORE_ADD_SALES'), STOCK_ADD);
        if ($account === false) {
			$addPage->AddEvent(ON_CLICK, "javascript: window.location = '".BASE_SCRIPT."?gadget=Store&amp;action=C_form';");
        } else {
			$addPage->AddEvent(ON_CLICK, "javascript: window.location = '".$base_url."?gadget=Store&amp;action=account_C_form';");
		}
		$tpl->SetVariable('add_sales', $addPage->Get());

        $tpl->ParseBlock('sales_admin');

        return $tpl->Get();
    }

    /**
     * We are on the C_form page
     *
     * @access public
     * @return string
     */
    function C_form($account = false)
    {
		if ($account === false) {
			$GLOBALS['app']->Session->CheckPermission('Store', 'default');
		} else {
			$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
			if (!$GLOBALS['app']->Session->GetPermission('Store', 'default')) {
				if (!$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Store', 'OwnProduct')) {
		            $GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
					$userHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
					return $userHTML->DefaultAction();
				}
			}
		}
		
        $GLOBALS['app']->Layout->AddHeadOther("<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" href=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/piwi/piwidata/css/calendar-blue.css\" />\n");
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$request =& Jaws_Request::getInstance();
		$action = $request->get('action', 'get');
				
		$tpl =& new Jaws_Template('gadgets/Store/templates/');
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

        $tpl->SetBlock('gadget_product');

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
		$syntactsUrl = $GLOBALS['app']->getSyntactsAdminUrl("Store/admin_Store_C_form");

		$tpl->SetVariable('workarea-style', 'style="margin-top: 30px;" ');

		if ($syntactsUrl) {
			include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
			$snoopy = new Snoopy('Store');
			$model = $GLOBALS['app']->LoadGadget('Store', 'Model');
								
			$submit_url = $syntactsUrl;
			
			$id = $request->get('id', 'get');

			// send post records
			if (!empty($id)) {
				$post = $model->GetSale($id);
		        if (!Jaws_Error::IsError($post) && ($GLOBALS['app']->Session->GetPermission('Store', 'ManageProducts') || $post['ownerid'] == $GLOBALS['app']->Session->GetAttribute('user_id'))) {
					$i = 0;
					$j = 0;
					$submit_vars['0:cols'] = 15;
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
                    //return new Jaws_Error(_t('STORE_ERROR_POST_NOT_FOUND'), _t('STORE_NAME'));
				}
								
			}

			// send editor HTML to syntacts
			$description = (isset($post['description'])) ? $post['description'] : '';
			$editor =& $GLOBALS['app']->LoadEditor('Store', 'description', $description, false);
			$editor->TextArea->SetStyle('width: 100%;');
			$editor->SetWidth('490px');

			$submit_vars['HTTP_REFERER'] = $GLOBALS['app']->GetSiteURL();
			$submit_vars['JAWS_URL'] = $GLOBALS['app']->GetJawsURL() . '/';
			$submit_vars['DPATH'] = '';
			$submit_vars['ID'] = $id;
			$now = $GLOBALS['db']->Date();
			$submit_vars['STARTDATE'] = $now;
			$submit_vars['ENDDATE'] = $now;
			
			if (!isset($page)) {
				if($snoopy->submit($submit_url,$submit_vars)) {
					//while(list($key,$val) = each($snoopy->headers))
						//echo $key.": ".$val."<br>\n";
					//echo "<p>\n";
					
					//$page = "<PRE>".htmlspecialchars($snoopy->results)."</PRE>\n";
					$page = $snoopy->results;
					$page = str_replace("__JAWS_EDITOR__", $editor->Get(), $page);
				} else {
					$page = _t('STORE_ERROR_ASPPAGE_NOT_RETRIEVED', $snoopy->error)."\n";
				}
			}

			$tpl->SetVariable('content', $page);
		}
		
        $tpl->ParseBlock('gadget_product');

        return $tpl->Get();

    }

    /**
     * We are on the B_form_post2 page
     *
     * @access public
     * @return string
     */
    function C_form_post($account = false)
    {

		if ($account === false) {
			return $this->form_post();
		} else {
			return $this->form_post(true);
		}

    }
	
    /**
     * We are on the brands list page
     *
     * @access public
     * @return string
     */
    function D($account = false)
    {
		if ($account === false) {
			$GLOBALS['app']->Session->CheckPermission('Store', 'default');
		} else {
			$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
			if (!$GLOBALS['app']->Session->GetPermission('Store', 'default')) {
				if (!$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Store', 'OwnProduct')) {
		            $GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
					$userHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
					return $userHTML->DefaultAction();
				}
			}
		}

        $tpl = new Jaws_Template('gadgets/Store/templates/');
        $tpl->Load('admin.html');
        $tpl->SetBlock('brands_admin');
        
		$request =& Jaws_Request::getInstance();
		$action = $request->get('action', 'get');
        
		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=CustomPage&action=account_SetGBRoot');
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

        $tpl->SetVariable('grid', $this->AttributeTypesDataGrid());

        $toolBar   =& Piwi::CreateWidget('HBox');

        $deleteAll =& Piwi::CreateWidget('Button', 'deleteBrands',
                                         _t('GLOBAL_DELETE'),
                                         STOCK_DELETE);
        $deleteAll->AddEvent(ON_CLICK,
                             "javascript: massiveDeleteBrands('"._t('STORE_CONFIRM_MASIVE_DELETE_BRANDS')."');");

        $toolBar->Add($deleteAll);

        $tpl->SetVariable('tools', $toolBar->Get());
                
        //Status filter
        $status = '';
        $statusCombo =& Piwi::CreateWidget('Combo', 'brands_status');
        $statusCombo->setId('brands_status');
        $statusCombo->AddOption('&nbsp;', '');
        $statusCombo->AddOption(_t('STORE_PUBLISHED'), 'Y');
        $statusCombo->AddOption(_t('STORE_DRAFT'), 'N');
        $statusCombo->SetDefault($status);
        $statusCombo->AddEvent(ON_CHANGE, 'javascript: searchBrand();');
        $tpl->SetVariable('status', _t('STORE_ACTIVE'));
        $tpl->SetVariable('status_field', $statusCombo->Get());

        // Free text search
        $searchButton =& Piwi::CreateWidget('Button', 'searchButton', _t('GLOBAL_SEARCH'), STOCK_SEARCH);
        $searchButton->AddEvent(ON_CLICK, 'javascript: searchBrand();');
        $tpl->SetVariable('search', $searchButton->Get());

        $search = '';
        $searchEntry =& Piwi::CreateWidget('Entry', 'brands_search', $search);
        $searchEntry->SetStyle('zwidth: 100%;');
        $tpl->SetVariable('search_field', $searchEntry->Get());

        $tpl->SetVariable('entries', $this->BrandsDataGrid());

        $addPage =& Piwi::CreateWidget('Button', 'add_brands', _t('STORE_ADD_BRANDS'), STOCK_ADD);
        if ($account === false) {
			$addPage->AddEvent(ON_CLICK, "javascript: window.location = '".BASE_SCRIPT."?gadget=Store&amp;action=D_form';");
        } else {
			$addPage->AddEvent(ON_CLICK, "javascript: window.location = '".$base_url."?gadget=Store&amp;action=account_D_form';");
		}
		$tpl->SetVariable('add_brands', $addPage->Get());

        $tpl->ParseBlock('brands_admin');

        return $tpl->Get();
    }

    /**
     * We are on the D_form page
     *
     * @access public
     * @return string
     */
    function D_form($account = false)
    {
		require_once JAWS_PATH . 'include/Jaws/Image.php';
        
		if ($account === false) {
			$GLOBALS['app']->Session->CheckPermission('Store', 'default');
		} else {
			$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
			if (!$GLOBALS['app']->Session->GetPermission('Store', 'default')) {
				if (!$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Store', 'OwnProduct')) {
		            $GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
					$userHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
					return $userHTML->DefaultAction();
				}
			}
		}

		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
				
		$tpl =& new Jaws_Template('gadgets/Store/templates/');
        $tpl->Load('admin.html');

        $tpl->SetBlock('gadget_product');

		$request =& Jaws_Request::getInstance();
		$action = $request->get('action', 'get');
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
			$submit_vars['ACTIONPREFIX'] = 'account_';
			$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
			$base_url = 'index.php';
		}
		$syntactsUrl = $GLOBALS['app']->getSyntactsAdminUrl("Store/admin_Store_D_form");

		$tpl->SetVariable('workarea-style', 'style="margin-top: 30px;" ');

		if ($syntactsUrl) {
			include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
			$snoopy = new Snoopy('Store');
			$model = $GLOBALS['app']->LoadGadget('Store', 'Model');
								
			$submit_url = $syntactsUrl;
			
			$id = $request->get('id', 'get');
			$linkid = $request->get('linkid', 'get');

			// send post records
			if (!empty($id)) {
				$post = $model->GetBrand($id);
		        if (!Jaws_Error::IsError($post) && ($GLOBALS['app']->Session->GetPermission('Store', 'ManageProducts') || $post['ownerid'] == $OwnerID)) {
					$i = 0;
					$j = 0;
					$submit_vars['0:cols'] = 14;
					foreach($post as $e => $v) {		            
							
						if ($e == 'description') {
							$submit_vars[SYNTACTS_DB . "0:$j:0"] = $this->ParseText($v, 'Store');
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
									if (strpos(strtolower($main_image_src), 'data/files/') !== false) {
										$main_image_src = 'image_thumb.php?uri='.urlencode($main_image_src);
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
						} else {	
							$submit_vars[SYNTACTS_DB . "0:$j:0"] = $xss->filter($v);
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
					$page = _t('STORE_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403'))."\n";
				}
			}

			// send editor HTML to syntacts
			$description = (isset($post['description'])) ? $post['description'] : '';
			$editor =& $GLOBALS['app']->LoadEditor('Store', 'description', $description, false);
			$editor->TextArea->SetStyle('width: 100%;');
			$editor->SetWidth('490px');

	        // url list combo
			$post_url = (isset($post['url']) && strpos($post['url'], "://") === false) ? $post['url'] : '';
			$urlListCombo =& Piwi::CreateWidget('Combo', 'internal_url');
	        $urlListCombo->setID('internal_url');

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

			// send main splash editor HTML to syntacts
			$contentCode = (isset($post['image_code'])) ? $post['image_code'] : '';
	 		$editorCode=& Piwi::CreateWidget('TextArea', 'image_code', $contentCode);
	        $editorCode->SetStyle('width: 490px;');
			$editorCode->SetID('image_code');

			$submit_vars['HTTP_REFERER'] = $GLOBALS['app']->GetSiteURL();
			$submit_vars['JAWS_URL'] = $GLOBALS['app']->GetJawsURL() . '/';
			$submit_vars['DPATH'] = '';
			$submit_vars['ID'] = $id;
			$submit_vars['LINKID'] = $linkid;
			
			if (!isset($page)) {
				if($snoopy->submit($submit_url,$submit_vars)) {
					//while(list($key,$val) = each($snoopy->headers))
						//echo $key.": ".$val."<br>\n";
					//echo "<p>\n";
					
					//$page = "<PRE>".htmlspecialchars($snoopy->results)."</PRE>\n";
					$page = $snoopy->results;
					$page = str_replace("__JAWS_INTERNALURLS__", $urlListCombo->Get(), $page);
					$page = str_replace("__JAWS_EDITOR__", $editor->Get(), $page);
					$page = str_replace("__JAWS_CODEEDITOR__", $editorCode->Get(), $page);
				} else {
					$page = _t('STORE_ERROR_ASPPAGE_NOT_RETRIEVED', $snoopy->error)."\n";
				}
			}

			$tpl->SetVariable('content', $page);
		}
		
        $tpl->ParseBlock('gadget_product');

        return $tpl->Get();

    }

    /**
     * We are on the D_form_post page
     *
     * @access public
     * @return string
     */
    function D_form_post($account = false)
    {

		if ($account === false) {
			return $this->form_post();
		} else {
			return $this->form_post(true);
		}

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

		$tpl = new Jaws_Template('gadgets/Store/templates/');
        $tpl->Load('admin.html');
        $tpl->SetBlock('settings');

		$request =& Jaws_Request::getInstance();
		$action = $request->get('action', 'get');
        $tpl->SetVariable('menubar', $this->MenuBar($action));

        $tpl->SetVariable('base_script', BASE_SCRIPT);
        $tpl->SetVariable('settings', _t('GLOBAL_SETTINGS'));

        $model = $GLOBALS['app']->LoadGadget('Store', 'AdminModel');
        $postLimitKey = $GLOBALS['app']->Registry->Get('/gadgets/Store/user_post_limit');
        $priceLimitKey = $GLOBALS['app']->Registry->Get('/gadgets/Store/user_price_limit');
        $descLimitKey = $GLOBALS['app']->Registry->Get('/gadgets/Store/user_desc_char_limit');
        $maskEmailKey = $GLOBALS['app']->Registry->Get('/gadgets/Store/user_mask_owner_email');
        $randomizeKey = $GLOBALS['app']->Registry->Get('/gadgets/Store/randomize');
        $defaultDisplayKey = $GLOBALS['app']->Registry->Get('/gadgets/Store/default_display');
		
		$randomizeCombo =& Piwi::CreateWidget('Combo', 'randomize');
		$randomizeCombo->AddOption(_t('GLOBAL_YES'), 'Y');
		$randomizeCombo->AddOption(_t('GLOBAL_NO'), 'N');
		$randomizeCombo->SetDefault($randomizeKey);
		$randomizeCombo->setTitle(_t("STORE_RANDOMIZE"));
        $tpl->SetVariable('key0_label', _t("STORE_RANDOMIZE").":");
        $tpl->SetVariable('key0_entry', $randomizeCombo->Get());

		$defaultDisplayCombo =& Piwi::CreateWidget('Combo', 'default_display');
		$defaultDisplayCombo->AddOption(_t('STORE_LIST'), 'list');
		$defaultDisplayCombo->AddOption(_t('STORE_GRID'), 'grid');
		$defaultDisplayCombo->SetDefault($defaultDisplayKey);
		$defaultDisplayCombo->setTitle(_t("STORE_DEFAULT_DISPLAY"));
        $tpl->SetVariable('key0a_label', _t("STORE_DEFAULT_DISPLAY").":");
        $tpl->SetVariable('key0a_entry', $defaultDisplayCombo->Get());

        $key1Entry =& Piwi::CreateWidget('Entry', 'user_post_limit', $postLimitKey);
        $key1Entry->SetStyle('width: 100px;');
        $tpl->SetVariable('key1_label', 'Limit Product images that a user can add (Enter \'0\' for unlimited):');
		$tpl->SetVariable('key1_entry', $key1Entry->Get());
        
        $key4Entry =& Piwi::CreateWidget('Entry', 'user_price_limit', $priceLimitKey);
        $key4Entry->SetStyle('width: 100px;');
        $tpl->SetVariable('key4_label', 'Limit Product price that a user can add (Enter \'0\' for unlimited):');
		$tpl->SetVariable('key4_entry', $key4Entry->Get());

		$key2Entry =& Piwi::CreateWidget('Entry', 'user_desc_char_limit', $descLimitKey);
        $key2Entry->SetStyle('width: 100px;');
        $tpl->SetVariable('key2_label', 'Limit Product description field length for users (Number of characters. Enter \'0\' for unlimited):');
		$tpl->SetVariable('key2_entry', $key2Entry->Get());
		
		$active1Combo =& Piwi::CreateWidget('Combo', 'user_mask_owner_email');
		$active1Combo->AddOption(_t('GLOBAL_YES'), 'Y');
		$active1Combo->AddOption(_t('GLOBAL_NO'), 'N');
		$active1Combo->SetDefault($maskEmailKey);
		$active1Combo->setTitle("Use a \"no-reply\" e-mail address for all Product Inquiries going to users");
        $tpl->SetVariable('key3_label', "Use a \"no-reply\" e-mail address for all Product Inquiries going to users:");
        $tpl->SetVariable('key3_entry', $active1Combo->Get());

        $saveButton =& Piwi::CreateWidget('Button', 'Save', _t('GLOBAL_SAVE'), STOCK_SAVE);
        $saveButton->AddEvent(ON_CLICK,
                             "javascript: saveSettings();");

        $tpl->SetVariable('save_button', $saveButton->Get());

        $tpl->ParseBlock('settings');
        return $tpl->Get();
		
		/*
		$request =& Jaws_Request::getInstance();
		$search = $request->get('search', 'get');
		$tpl =& new Jaws_Template('gadgets/Maps/templates/');
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
     * Import inventory list (select type (RSS, Tab-Delimited)
     *
     * @access public
     * @return XHTML string
     */
    function ImportInventory()
    {
		$GLOBALS['app']->Layout->AddHeadLink('gadgets/ControlPanel/resources/style.css', 'stylesheet', 'text/css');
        $this->AjaxMe('script.js');
        $tpl = new Jaws_Template('gadgets/Store/templates/');
        $tpl->Load('ImportInventory.html');
        $tpl->SetBlock('Properties');

		include_once JAWS_PATH . 'include/Jaws/Widgets/FieldSet.php';
        
		$form =& Piwi::CreateWidget('Form', BASE_SCRIPT, 'post');
        $form->Add(Piwi::CreateWidget('HiddenEntry', 'gadget', 'Store'));
        $form->Add(Piwi::CreateWidget('HiddenEntry', 'action', 'ImportFile'));

		$payment_gateway =& Piwi::CreateWidget('Combo', 'inventory_type');
		$payment_gateway->SetTitle(_t('STORE_IMPORTINVENTORY_TYPE'));
		$payment_gateway->AddOption(_t('STORE_IMPORTINVENTORY_TABDELIMITED'), 'TabDelimited');
		//$payment_gateway->AddOption(_t('STORE_IMPORTINVENTORY_COMMASEPARATED'), 'CSV');
		//$payment_gateway->AddOption(_t('STORE_IMPORTINVENTORY_RSSFEED'), 'RSS');
		$payment_gateway->SetDefault('TabDelimited');

		$gateway_fieldset = new Jaws_Widgets_FieldSet('');
		$gateway_fieldset->SetTitle('vertical');
		$gateway_fieldset->Add($payment_gateway);
		
		// Image
		$xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$image = '';
		$image_src = $GLOBALS['app']->getDataURL() . 'files'.$xss->filter($image);
		$image_preview = '';
		if ($image != '' && file_exists($image_src)) { 
			$image_preview .= "<br /><img border=\"0\" src=\"".$image_src."\" width=\"80\"".(strtolower(substr($image, -3)) == 'gif' || strtolower(substr($image, -3)) == 'png' || strtolower(substr($image, -3)) == 'bmp' ? ' height="80"' : '')." align=\"left\" style=\"padding: 5px;\" />";
		}
		$imageScript = "<script type=\"text/javascript\">Event.observe(window, \"load\",function(){addFileToPost('Store', 'NULL', 'NULL', 'main_image', 'inventory_file', 1, 500, 34, '', false, '', 'txt');});</script>";
		$imageHidden =& Piwi::CreateWidget('HiddenEntry', 'inventory_file', $image);
		$imageButton = "&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"button\" value=\"Uploaded Files\" onclick=\"openUploadWindow('inventory_file')\" style=\"font-family: Arial; font-size: 10pt; font-weight: bold\" />";
		$imageEntry =& Piwi::CreateWidget('UploadEntry', 'inventory_file', _t('STORE_IMPORTINVENTORY_FILE'), $image_preview, $imageScript, $imageHidden->Get(), $imageButton);
		
		$gateway_fieldset->Add($imageEntry);
		
		$form->Add($gateway_fieldset);
				
		$buttons =& Piwi::CreateWidget('HBox');
		$buttons->SetStyle(_t('GLOBAL_LANG_DIRECTION')=='rtl'?'float: left;' : 'float: right;');

		$save =& Piwi::CreateWidget('Button', 'save', _t('GLOBAL_SAVE'), STOCK_SAVE);
		$save->SetSubmit();

		$buttons->Add($save);
		$form->Add($buttons);

		$tpl->SetVariable('form', $form->Get());
        $tpl->SetVariable('menubar', $this->MenuBar('ImportInventory'));

        $tpl->ParseBlock('Properties');

        return $tpl->Get();
		
	}
	
    /**
     * Import inventory list (select type (RSS, Tab-Delimited)
     *
     * @access public
     * @return XHTML string
     */
    function ImportFile()
    {
		$request =& Jaws_Request::getInstance();
		$file = $request->get('inventory_file', 'post');
		$type = $request->get('inventory_type', 'post');
		require_once JAWS_PATH . 'include/Jaws/Header.php';	
		Jaws_Header::Location($GLOBALS['app']->getSiteURL().'/index.php?gadget=Store&action=UpdateRSSStore&num=1&file='.urlencode($file).'&type='.$type.'&ua=Y');

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
		$output_html .= 'YAHOO.util.Connect.asyncRequest(\'GET\',\''.$GLOBALS['app']->getSiteURL().'/index.php?gadget=Store&action=UpdateRSSStore&num=1&file='.urlencode($file).'&type='.$type.'&ua=N\',spawnCallback);';
		$output_html .= '}';
		$output_html .= 'spawnProcess(); location.href = "admin.php?gadget=Store";</script>';
		//exec ("/usr/local/bin/php /homepages/40/d298423861/htdocs/cli.php --id=$cmd >/dev/null &");
		//backgroundPost($GLOBALS['app']->getSiteURL().'/index.php?gadget=Properties&action=UpdateRSSProperties&id='.$cmd);
		return $output_html;
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
		return $user_admin->ShowEmbedWindow('Store', 'OwnProduct');
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
			$GLOBALS['app']->Session->CheckPermission('Store', 'default');
		} else {
			$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
			if (!$GLOBALS['app']->Session->GetPermission('Store', 'default')) {
				if (!$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Store', 'OwnProduct')) {
		            $GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
					$userHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
					return $userHTML->DefaultAction();
				}
			}
		}

		require_once JAWS_PATH . 'include/Jaws/Template.php';
        $tpl = new Jaws_Template('gadgets/Store/templates/');
        $tpl->Load('QuickAddForm.html');
        $tpl->SetBlock('form');

		$request =& Jaws_Request::getInstance();
		$method = $request->get('method', 'get');
		if (empty($method)) {
			$method = 'AddProduct';
		}
		$form_content = '';
		switch($method) {
			case "AddProductParent": 
			case "UpdateProductParent": 
				$form_content = $this->form($account);
				break;
			case "AddGadget": 
			case "AddProduct": 
			case "UpdateProduct": 
				$form_content = $this->A_form($account);
				break;
			case "AddPost":
			case "EditPost":
				$form_content = $this->A_form2($account);
				break;
			case "AddProductAttribute":
			case "EditProductAttribute":
				$form_content = $this->B_form($account);
				break;
			case "AddAttributeType":
			case "EditAttributeType":
				$form_content = $this->B_form2($account);
				break;
			case "AddSale":
			case "EditSale":
				$form_content = $this->C_form($account);
				break;
			case "AddBrand":
			case "EditBrand":
				$form_content = $this->D_form($account);
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
        $tpl->SetVariable('stub', (JAWS_SCRIPT == 'admin' ? 'StoreAdminAjax' : 'StoreAjax'));
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
