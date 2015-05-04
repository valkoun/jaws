<?php
/**
 * Ads Gadget Admin
 *
 * @category   GadgetAdmin
 * @package    Ads
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class AdsAdminHTML extends Jaws_GadgetHTML
{
    /**
     * Constructor
     *
     * @access public
     */
    function AdsAdminHTML()
    {
        $this->Init('Ads');
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
        $actions = array('Admin','A','A_form','A_form_post','B','B_form','B_form_post','form','form_post','view');
        if (!in_array($selected, $actions)) {
            $selected = 'Admin';
        }

        require_once JAWS_PATH . 'include/Jaws/Widgets/Menubar.php';
        $menubar = new Jaws_Widgets_Menubar();
        if ($GLOBALS['app']->Session->GetPermission('Ads', 'ManageAds')) {
            $menubar->AddOption('Admin', _t('ADS_MENU_ADMIN'),
                                'admin.php?gadget=Ads&amp;action=Admin', STOCK_OPEN);
			if (strtolower($selected) != "admin" && (strtolower($selected) == 'form' || strtolower($selected) == 'form_post')) {
				$menubar->AddOption($selected, _t('ADS_MENU_CATEGORY'),
	                                'javascript:void(0);', STOCK_EDIT);
			}
        }
		if ($GLOBALS['app']->Session->GetPermission('Ads', 'default')) {
            $menubar->AddOption('A', _t('ADS_MENU_ADS'),
                                'admin.php?gadget=Ads&amp;action=A', 'gadgets/Ads/images/x-office-spreadsheet.png');

			if (strtolower($selected) != "admin" && (strtolower($selected) == 'view' || strtolower($selected) == 'a_form' || strtolower($selected) == 'a_form_post')) {
				$menubar->AddOption($selected, _t('ADS_MENU_AD'),
	                                'javascript:void(0);', STOCK_NEW);
			}
			$menubar->AddOption('B', _t('ADS_MENU_BRANDS'),
								'admin.php?gadget=Ads&amp;action=B', STOCK_DOCUMENTS);
            if (strtolower($selected) != "admin" && (strtolower($selected) == 'b_form' || strtolower($selected) == 'b_form_post')) {
				$menubar->AddOption($selected, _t('ADS_MENU_BRAND'),
	                                'javascript:void(0);', STOCK_EDIT);
			}
		}

		$request =& Jaws_Request::getInstance();
		$id = $request->get('id', 'get');
		if (strtolower($selected) == "form" && empty($id)) {
		} else {
			if ($GLOBALS['app']->Session->GetPermission('Ads', 'ManageAds')) {
				$menubar->AddOption('Add', '',
									'admin.php?gadget=Ads&amp;action=A_form&amp;linkid=', STOCK_ADD);
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
        $model = $GLOBALS['app']->LoadGadget('Ads', 'AdminModel');
		$OwnerID = 0;
		if (BASE_SCRIPT == 'index.php') {
			$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');
		}
        $sql = 'SELECT COUNT([adparentid]) FROM [[adparent]] WHERE [adparentownerid] = '.$OwnerID;
        $res = $GLOBALS['db']->queryOne($sql);
        $total = (Jaws_Error::IsError($res) ? 0 : $res);

        $grid =& Piwi::CreateWidget('DataGrid', array());
        $grid->TotalRows($total);
        $grid->SetStyle('width: 100%;');
        $grid->SetID('adparents_datagrid');
        $grid->SetAction('next', 'javascript:nextAdParentValues();');
        $grid->SetAction('prev', 'javascript:previousAdParentValues();');
        //$grid->useMultipleSelection();
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('ADS_STATUS')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_TITLE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', ''));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('ADS_ADS')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('ADS_LAST_UPDATE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_ACTIONS')));

        return $grid->Get();
    }
    
	/**
     * Builds the basic datagrid view
     *
     * @access  private
     * @return  string   XHTML of datagrid
     */
    function AdsDataGrid()
    {
        $model = $GLOBALS['app']->LoadGadget('Ads', 'AdminModel');
		$OwnerID = 0;
		if (BASE_SCRIPT == 'index.php') {
			$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');
		}
        $sql = 'SELECT COUNT([id]) FROM [[ads]] WHERE [ownerid] = '.$OwnerID;
        $res = $GLOBALS['db']->queryOne($sql);
        $total = (Jaws_Error::IsError($res) ? 0 : $res);

        $grid =& Piwi::CreateWidget('DataGrid', array());
        $grid->TotalRows($total);
        $grid->SetStyle('width: 100%;');
        $grid->SetID('ads_datagrid');
        $grid->setAction('next', 'javascript:nextAdValues();');
        $grid->setAction('prev', 'javascript:previousAdValues();');
        //$grid->useMultipleSelection();
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('ADS_STATUS')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_TITLE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('ADS_TYPE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('ADS_LAST_UPDATE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_ACTIONS')));

        return $grid->Get();
    }

	/**
     * Builds the basic datagrid view
     *
     * @access  private
     * @return  string   XHTML of datagrid
     */
    function SavedDataGrid()
    {
        $model = $GLOBALS['app']->LoadGadget('Ads', 'AdminModel');
		$OwnerID = 0;
		if (BASE_SCRIPT == 'index.php') {
			$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');
		}
        $sql = 'SELECT COUNT([id]) FROM [[ads_subscribe]] WHERE [ownerid] = '.$OwnerID;
        $res = $GLOBALS['db']->queryOne($sql);
        $total = (Jaws_Error::IsError($res) ? 0 : $res);

        $grid =& Piwi::CreateWidget('DataGrid', array());
        $grid->TotalRows($total);
        $grid->SetStyle('width: 100%;');
        $grid->SetID('savedads_datagrid');
        $grid->setAction('next', 'javascript:nextSavedAdsValues();');
        $grid->setAction('prev', 'javascript:previousSavedAdsValues();');
        //$grid->useMultipleSelection();
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_TITLE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('ADS_DESCRIPTIONFIELD')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('ADS_LAST_UPDATE')));
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
        $model = $GLOBALS['app']->LoadGadget('Ads', 'AdminModel');
		$OwnerID = 0;
		if (BASE_SCRIPT == 'index.php') {
			$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');
		}
        $sql = 'SELECT COUNT([id]) FROM [[adbrand]] WHERE [ownerid] = '.$OwnerID;
        $res = $GLOBALS['db']->queryOne($sql);
        $total = (Jaws_Error::IsError($res) ? 0 : $res);

        $grid =& Piwi::CreateWidget('DataGrid', array());
        $grid->TotalRows($total);
        $grid->SetStyle('width: 100%;');
        $grid->SetID('brands_datagrid');
        $grid->setAction('next', 'javascript:nextBrandValues();');
        $grid->setAction('prev', 'javascript:previousBrandValues();');
        //$grid->useMultipleSelection();
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_TITLE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', ''));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('ADS_ACTIVE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('ADS_LAST_UPDATE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_ACTIONS')));

        return $grid->Get();
    }

    /**
     * Returns an array with ad parents found
     *
     * @access  public
     * @param   string  $status  Status of ad parent(s) we want to display
     * @param   string  $search  Keyword (title/description) of parents we want to look for
     * @param   int     $limit   Data limit
     * @return  array   Data
     */
    function GetAdParents($status, $search, $limit, $OwnerID = 0)
    {
        $model = $GLOBALS['app']->LoadGadget('Ads', 'AdminModel');
		$pages = $model->SearchAdParents($status, $search, $limit, $OwnerID);
        if (Jaws_Error::IsError($pages)) {
            return array();
        }

        $data    = array();
		if (BASE_SCRIPT != 'index.php') {
			$edit_url    = BASE_SCRIPT . '?gadget=Ads&amp;action=A&amp;id=';
        } else {
			$edit_url    = 'index.php?gadget=Ads&amp;action=account_A&amp;id=';
		}
		$date = $GLOBALS['app']->loadDate();
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');

        foreach ($pages as $page) {
            //if ($page['adparentparent'] == 0) {
				$pageData = array();
				if ($page['adparentactive'] == 'Y') {
					$pageData['active'] = _t('ADS_PUBLISHED');
				} else {
					$pageData['active'] = _t('ADS_NOTPUBLISHED');
				}
				$pageData['title'] = '<a href="'.$edit_url.$page['adparentid'].'">'.$page['adparentcategory_name'].'</a>';
				if (BASE_SCRIPT != 'index.php') {
					$pageData['furl']  = "<a href='javascript:void(0);' onclick='window.open(\"".$GLOBALS['app']->Map->GetURLFor('Ads', 'Category', array('id' => $xss->filter($page['adparentfast_url'])))."\");'>View This Category</a>";
				}
				$number = $model->GetAllAdsOfParent($page['adparentid']);
				if (!Jaws_Error::IsError($number)) {
					$pageData['count'] = count($number);
				} else {
					$pageData['count'] = 0;
				}
				$pageData['date']  = $date->Format($page['adparentupdated']);
				$actions = '';
				if ($this->GetPermission('ManageAdParents')) {
					if (BASE_SCRIPT != 'index.php') {
						$link =& Piwi::CreateWidget('Link', _t('ADS_EDIT_ADS'),
													$edit_url.$page['adparentid'],
													STOCK_BOOK);
					} else {
						$link =& Piwi::CreateWidget('Link', _t('ADS_EDIT_ADS'),
													"javascript:window.open('".$edit_url.$page['adparentid']."');",
													STOCK_BOOK);
					}
					$actions.= $link->Get().'&nbsp;';
				} else {
					if ($ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Ads', 'OwnAd')) {
						$link =& Piwi::CreateWidget('Link', _t('GLOBAL_EDIT'),
													"javascript:window.open('".$edit_url.$page['adparentid']."');",
													STOCK_BOOK);
						$actions.= $link->Get().'&nbsp;';
					}
				}

				if ($this->GetPermission('ManageAdParents')) {
					$link =& Piwi::CreateWidget('Link', _t('GLOBAL_DELETE'),
												"javascript: if (confirm('"._t('GLOBAL_CONFIRM_DELETE', _t('ADS_ADPARENT'))."')) ".
												"deleteAdParent('".$page['adparentid']."');",
												"imagesICON_delete2.gif");
					$actions.= $link->Get().'&nbsp;';
				} else {
					if ($ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Ads', 'OwnAd')) {
						$link =& Piwi::CreateWidget('Link', _t('GLOBAL_DELETE'),
													"javascript: if (confirm('"._t('GLOBAL_CONFIRM_DELETE', _t('ADS_ADPARENT'))."')) ".
													"deleteAdParent('".$page['adparentid']."');",
													"imagesICON_delete2.gif");
						$actions.= $link->Get().'&nbsp;';
					}
				}
				$pageData['actions'] = $actions;
				$pageData['__KEY__'] = $page['adparentid'];
				$data[] = $pageData;
		        $propertiesModel = $GLOBALS['app']->LoadGadget('Ads', 'Model');
		        $children = $propertiesModel->GetAllSubCategoriesOfParent($page['adparentid']);
		        // Has children, so indent them
				foreach ($children as $child) {
					$pageData['title'] = '&nbsp;&nbsp;-<a href="'.$edit_url.$child['adparentid'].'">'.$child['adparentcategory_name'].'</a>';
					if (BASE_SCRIPT != 'index.php') {
						$pageData['furl']  = "<a href='javascript:void(0);' onclick='window.open(\"".$GLOBALS['app']->Map->GetURLFor('Ads', 'Category', array('id' => $xss->filter($child['adparentfast_url'])))."\");'>View This Category</a>";
					}
					$number = $model->GetAllAdsOfParent($child['adparentid']);
					if (!Jaws_Error::IsError($number)) {
						$pageData['count'] = count($number);
					} else {
						$pageData['count'] = 0;
					}
					if ($child['adparentactive'] == 'Y') {
						$pageData['active'] = _t('ADS_PUBLISHED');
					} else {
						$pageData['active'] = _t('ADS_DRAFT');
					}
					$pageData['date']  = $date->Format($child['adparentupdated']);
					$actions = '';
					if ($this->GetPermission('ManageAdParents')) {
						if (BASE_SCRIPT != 'index.php') {
							$link =& Piwi::CreateWidget('Link', _t('ADS_EDIT_ADS'),
														$edit_url.$child['adparentid'],
														STOCK_BOOK);
						} else {
							$link =& Piwi::CreateWidget('Link', _t('ADS_EDIT_ADS'),
														"javascript:window.open('".$edit_url.$child['adparentid']."');",
														STOCK_BOOK);
						}
						$actions.= $link->Get().'&nbsp;';
					}

					if ($this->GetPermission('ManageAdParents')) {
						$link =& Piwi::CreateWidget('Link', _t('GLOBAL_DELETE'),
													"javascript: if (confirm('"._t('GLOBAL_CONFIRM_DELETE', _t('ADS_ADPARENT'))."')) ".
													"deleteAdParent('".$child['adparentid']."');",
													"imagesICON_delete2.gif");
						$actions.= $link->Get().'&nbsp;';
					} else {
						if ($ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Ads', 'OwnAd')) {
							$link =& Piwi::CreateWidget('Link', _t('GLOBAL_DELETE'),
														"javascript: if (confirm('"._t('GLOBAL_CONFIRM_DELETE', _t('ADS_ADPARENT'))."')) ".
														"deleteAdParent('".$child['adparentid']."');",
														"imagesICON_delete2.gif");
							$actions.= $link->Get().'&nbsp;';
						}
					}
					$pageData['actions'] = $actions;
					$pageData['__KEY__'] = $child['adparentid'];
					$data[] = $pageData;
				}
			//}
		}
        return $data;
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
    function GetAds($status, $search, $limit, $OwnerID = 0, $pid = null)
    {
        $model = $GLOBALS['app']->LoadGadget('Ads', 'AdminModel');
        $pages = $model->SearchAds($status, $search, $limit, $OwnerID, $pid);
        if (Jaws_Error::IsError($pages)) {
            return array();
        }

        $data    = array();
		if (BASE_SCRIPT != 'index.php') {
			$edit_url    = BASE_SCRIPT . '?gadget=Ads&amp;action=view&amp;id=';
        } else {
			$edit_url    = 'index.php?gadget=Ads&amp;action=account_view&amp;id=';
		}
		$date = $GLOBALS['app']->loadDate();
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$ACL = $GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			

        foreach ($pages as $page) {
			$pageData = array();
			if ($page['active'] == 'Y') {
				$pageData['active'] = _t('ADS_PUBLISHED');
			} else {
				$pageData['active'] = _t('ADS_NOTPUBLISHED');
			}
			$pageData['title'] = '<a href="'.$edit_url.$page['id'].'">'.$page['title'].'</a>';
			$ad_type = 'ADS_TYPE_'.$page['type'];
			$pageData['type']  = _t($ad_type);

			$pageData['date']  = $date->Format($page['updated']);
			$actions = '';
			if ($this->GetPermission('ManageAds')) {
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
				if ($ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Ads', 'OwnAds')) {
					$link =& Piwi::CreateWidget('Link', _t('GLOBAL_EDIT'),
												"javascript:window.open('".$edit_url.$page['id']."');",
												STOCK_EDIT);
					$actions.= $link->Get().'&nbsp;';
				}
			}

			if ($this->GetPermission('ManageAds')) {
				$link =& Piwi::CreateWidget('Link', _t('GLOBAL_DELETE'),
											"javascript: if (confirm('"._t('ADS_CONFIRM_DELETE_AD', _t('ADS_AD'))."')) ".
											"deleteAd('".$page['id']."');",
											"images/ICON_delete2.gif");
				$actions.= $link->Get().'&nbsp;';
			} else {
				if ($ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Ads', 'OwnAds')) {
					$link =& Piwi::CreateWidget('Link', _t('GLOBAL_DELETE'),
												"javascript: if (confirm('"._t('ADS_CONFIRM_DELETE_AD', _t('ADS_AD'))."')) ".
												"deleteAd('".$page['id']."');",
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
     * @param   string  $status  Status of galleries(s) we want to display
     * @param   string  $search  Keyword (title/description) of galleries we want to look for
     * @param   int     $limit   Data limit
     * @return  array   Data
     */
    function GetSavedAds($status, $search, $limit, $OwnerID = 0)
    {
        $model = $GLOBALS['app']->LoadGadget('Ads', 'Model');
        $adminModel = $GLOBALS['app']->LoadGadget('Ads', 'AdminModel');
        $pages = $adminModel->SearchSavedAds($status, $search, $limit, $OwnerID);
        if (Jaws_Error::IsError($pages)) {
            return array();
        }

        $data    = array();
		$edit_url    = 'index.php?gadget=Ads&amp;action=Ad&amp;id=';
		$date = $GLOBALS['app']->loadDate();
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$ACL = $GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			

        foreach ($pages as $page) {
			$ad = $model->GetAd($page['ad_id']);
			if (!Jaws_Error::IsError($ad) && isset($ad['id']) && !empty($ad['id'])) {
				$pageData = array();
				//$pageData['active'] = $page['status'];
				$pageData['title'] = '<a href="'.$edit_url.$ad['id'].'">'.$ad['title'].'</a>';
				$pageData['description']  = strip_tags(substr($page['description'], 0, 100)).'...';

				$pageData['date']  = $date->Format($page['updated']);
				$actions = '';
				$link =& Piwi::CreateWidget('Link', _t('GLOBAL_EDIT'),
												$edit_url.$ad['id'],
												STOCK_EDIT);
				$actions.= $link->Get().'&nbsp;';
				$link =& Piwi::CreateWidget('Link', _t('GLOBAL_DELETE'),
												"javascript: if (confirm('"._t('ADS_CONFIRM_DELETE_AD', _t('ADS_AD'))."')) ".
												"deleteSavedAd('".$page['id']."');",
												"images/ICON_delete2.gif");
				$actions.= $link->Get().'&nbsp;';
				$pageData['actions'] = $actions;
				$pageData['__KEY__'] = $page['id'];
				$data[] = $pageData;
			}
        }
        return $data;
    }

    /**
     * Returns an array with ad brands found
     *
     * @access  public
     * @param   string  $status  Status of attribute(s) we want to display
     * @param   string  $search  Keyword (title/description) of attributes we want to look for
     * @param   int     $limit   Data limit
     * @return  array   Data
     */
    function GetBrands($status, $search, $limit, $OwnerID = 0)
    {
        $model = $GLOBALS['app']->LoadGadget('Ads', 'Model');
        $adminmodel = $GLOBALS['app']->LoadGadget('Ads', 'AdminModel');
		$pages = $adminmodel->SearchBrands($search, $status, $limit, $OwnerID);
        if (Jaws_Error::IsError($pages)) {
            return array();
        }

        $data    = array();
		if (BASE_SCRIPT != 'index.php') {
			$edit_url    = BASE_SCRIPT . '?gadget=Ads&amp;action=B_form&amp;id=';
        } else {
			$edit_url    = 'index.php?gadget=Ads&amp;action=account_B_form&amp;id=';
		}
		$date = $GLOBALS['app']->loadDate();
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');

        foreach ($pages as $page) {
			$pageData = array();
			$pageData['title'] = '<a href="'.$edit_url.$page['id'].'">'.$page['title'].'</a>';
			if (BASE_SCRIPT != 'index.php') {
				$pageData['furl']  = '<a href="'.$GLOBALS['app']->Map->GetURLFor('Ads', 'Brand', array('id' => str_replace(' ', '--', $xss->parse($page['title'])))).'">View Ads</a>';
			}
			if ($page['active'] == 'Y') {
				$pageData['active'] = _t('ADS_PUBLISHED');
			} else {
				$pageData['active'] = _t('ADS_DRAFT');
			}
			$pageData['date']  = $date->Format($page['updated']);
			$actions = '';
			if ($this->GetPermission('ManageAds')) {
				if (BASE_SCRIPT != 'index.php') {
					$link =& Piwi::CreateWidget('Link', _t('ADS_EDIT_BRAND'),
												$edit_url.$page['id'],
												STOCK_EDIT);
				} else {
					$link =& Piwi::CreateWidget('Link', _t('ADS_EDIT_BRAND'),
												"javascript:window.open('".$edit_url.$page['id']."');",
												STOCK_EDIT);
				}
				$actions.= $link->Get().'&nbsp;';
			}

			if ($this->GetPermission('ManageAds')) {
				$link =& Piwi::CreateWidget('Link', _t('GLOBAL_DELETE'),
											"javascript: if (confirm('"._t('GLOBAL_CONFIRM_DELETE', _t('ADS_BRAND'))."')) ".
											"deleteBrand('".$page['id']."');",
											"images/ICON_delete2.gif");
				$actions.= $link->Get().'&nbsp;';
			} else {
				if ($ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Ads', 'OwnAd')) {
					$link =& Piwi::CreateWidget('Link', _t('GLOBAL_DELETE'),
												"javascript: if (confirm('"._t('GLOBAL_CONFIRM_DELETE', _t('ADS_BRAND'))."')) ".
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
			Jaws_Header::Location(BASE_SCRIPT . '?gadget=Ads&action=A');
		}
		
		if ($account === false) {
			$GLOBALS['app']->Session->CheckPermission('Ads', 'default');
		} else {
			$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
			if (!$GLOBALS['app']->Session->GetPermission('Ads', 'default')) {
				if (!$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Ads', 'OwnAd')) {
		            //$GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
					return "Please log-in.";
				}
			}
		}
        $tpl = new Jaws_Template('gadgets/Ads/templates/');
        $tpl->Load('admin.html');
        $tpl->SetBlock('adparents_admin');
        

		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=CustomPage&action=account_SetGBRoot');
		if ($account === false) {
	        $GLOBALS['app']->Layout->AddScriptLink(BASE_SCRIPT . '?gadget=Ads&amp;action=Ajax&amp;client=all&amp;stub=AdsAdminAjax');
	        $GLOBALS['app']->Layout->AddScriptLink(BASE_SCRIPT . '?gadget=Ads&amp;action=AjaxCommonFiles');
	        $GLOBALS['app']->Layout->AddScriptLink('gadgets/Ads/resources/script.js');
			$GLOBALS['app']->Layout->AddScriptLink('libraries/js/admin.js');
			$tpl->SetVariable('menubar', $this->MenuBar($action));
			$account_prefix = '';
			$base_url = BASE_SCRIPT;
		} else {
	        $GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=Ads&amp;action=Ajax&amp;client=all&amp;stub=AdsAjax');
	        $GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=Ads&amp;action=AjaxCommonFiles');
	        $GLOBALS['app']->Layout->AddScriptLink('gadgets/Ads/resources/client_script.js');
			$tpl->SetVariable('menubar', '');
			$account_prefix = 'account_';
			$base_url = 'index.php';
		}
        
		$tpl->SetVariable('account', $account_prefix);
		$tpl->SetVariable('base_script', $base_url);

        $tpl->SetVariable('grid', $this->DataGrid());

        $toolBar   =& Piwi::CreateWidget('HBox');

        $deleteAll =& Piwi::CreateWidget('Button', 'deleteAllAdParents',
                                         _t('GLOBAL_DELETE'),
                                         STOCK_DELETE);
        $deleteAll->AddEvent(ON_CLICK,
                             "javascript: massiveDelete('"._t('ADS_CONFIRM_MASIVE_DELETE_ADPARENT')."');");

        $toolBar->Add($deleteAll);

        $tpl->SetVariable('tools', $toolBar->Get());
                
		if ($account === false) {
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
		} else {
	        $searchEntry =& Piwi::CreateWidget('HiddenEntry', 'status', '');
	        $tpl->SetVariable('status_field', $searchEntry->Get());
		}

        // Free text search
        $searchButton =& Piwi::CreateWidget('Button', 'searchButton', _t('GLOBAL_SEARCH'), STOCK_SEARCH);
        $searchButton->AddEvent(ON_CLICK, 'javascript: searchAdParent();');
        $tpl->SetVariable('search', $searchButton->Get());

        $search = '';
        $searchEntry =& Piwi::CreateWidget('Entry', 'search', $search);
        $searchEntry->SetStyle('zwidth: 100%;');
        $tpl->SetVariable('search_field', $searchEntry->Get());

        $tpl->SetVariable('entries', $this->Datagrid());

		// Add button is added by HTML->GetUserAccountControls
		if ($account === false) {
	        $addPage =& Piwi::CreateWidget('Button', 'add_adparent', _t('ADS_ADD_ADPARENT'), STOCK_ADD);
			$addPage->AddEvent(ON_CLICK, "javascript: window.location = '".$base_url."?gadget=Ads&amp;action=".$account_prefix."form';");
	        $tpl->SetVariable('add_adparent', $addPage->Get());
		} else {
			//$addPage->AddEvent(ON_CLICK, "javascript: window.open('".$base_url."?gadget=Ads&amp;action=".$account_prefix."form','','scrollbars=1,menubar=0,toolbar=0,location=0,status=1');");
	        $tpl->SetVariable('add_adparent', '');
		}

        $tpl->ParseBlock('adparents_admin');

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
			$GLOBALS['app']->Session->CheckPermission('Ads', 'default');
		} else {
			$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
			if (!$GLOBALS['app']->Session->GetPermission('Ads', 'default')) {
				if (!$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Ads', 'OwnAd')) {
		            $GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
					$userHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
					return $userHTML->DefaultAction();
				}
			}
		}

		// document dependencies
		
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$model = $GLOBALS['app']->LoadGadget('Ads', 'Model');
		$request =& Jaws_Request::getInstance();
		$gather = array('action', 'id');
		$get = $request->get($gather, 'get');

		// initialize template
		$tpl = new Jaws_Template('gadgets/Ads/templates/');
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
		if ($account === false) {
			$this->AjaxMe('script.js');
			$tpl->SetVariable('menubar', $this->MenuBar($get['action']));
			$GLOBALS['app']->Layout->AddScriptLink('libraries/js/admin.js');			
			$submit_vars['ACTIONPREFIX'] = "";
			$submit_vars['CLOSE_BUTTON'] = "location.href='" . BASE_SCRIPT . "?gadget=Ads&amp;action=Admin';";
			$syntactsUrl = $GLOBALS['app']->getSyntactsAdminHTMLUrl();
			$OwnerID = 0;
			$base_url = BASE_SCRIPT;
		} else {
			$tpl->SetVariable('menubar', '');
			$this->AjaxMe('client_script.js');
			$submit_vars['ACTIONPREFIX'] = "account_";
			$submit_vars['CLOSE_BUTTON'] = "if (window.opener && !window.opener.closed) {window.opener.location.reload();window.close();};";
			$syntactsUrl = $GLOBALS['app']->getSyntactsAdminHTMLUrl('Ads/admin_Ad_form');
			$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');
			$base_url = 'index.php';
		}
		$tpl->SetVariable('workarea-style', "style=\"float: left; margin-top: 30px;\" ");

		// syntacts page
		if ($syntactsUrl) {
			// snoopy
			include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
			$snoopy = new Snoopy('Ads');
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
					$pageInfo = $model->GetAdParent($get['id']);
					if (!Jaws_Error::IsError($pageInfo) && ($GLOBALS['app']->Session->GetPermission('Ads', 'ManageAds') || $pageInfo['adparentownerid'] == $OwnerID)) {
						$submit_vars['SUBMIT_BUTTON'] = "<input type='Submit' name='Submit' value='Update'>";
					} else {
						//$error = _t('ADS_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403'));												
						return new Jaws_Error(_t('ADS_ERROR_ADPARENT_NOT_FOUND'), _t('ADS_NAME'));
					}
				} else {
					$submit_vars['SUBMIT_BUTTON'] = "<input type='Submit' name='Submit' value='Add'>";
				}

				// send requesting URL to syntacts
				$stpl->SetVariable('HTTP_REFERER', $GLOBALS['app']->GetSiteURL());
				//$stpl->SetVariable('DPATH', JAWS_DPATH);
				$stpl->SetVariable('actionprefix', $submit_vars['ACTIONPREFIX']);
				$stpl->SetVariable('gadget', 'Ads');
				$stpl->SetVariable('CLOSE_BUTTON', $submit_vars['CLOSE_BUTTON']);
				$stpl->SetVariable('SUBMIT_BUTTON', $submit_vars['SUBMIT_BUTTON']);
				$stpl->SetVariable('controller', $base_url);
				
				// Get Help documentation
				$help_url = $GLOBALS['app']->getSyntactsAdminHTMLUrl("Ads/admin_Ads_form_help", 'txt');
				$snoopy = new Snoopy('Ads');
		
				if($snoopy->fetch($help_url)) {
					$helpContent = Jaws_Utils::split2D($snoopy->results);
				}
								
				// Hidden elements
				$ID = (isset($pageInfo['adparentid'])) ? $pageInfo['adparentid'] : '';
				$idHidden =& Piwi::CreateWidget('HiddenEntry', 'adparentID', $ID);
		        $form_content .= $idHidden->Get()."\n";

				$sort_order = (isset($pageInfo['adparentsort_order'])) ? $pageInfo['adparentsort_order'] : '0';
				$sort_orderHidden =& Piwi::CreateWidget('HiddenEntry', 'adparentsort_order', $sort_order);
		        $form_content .= $sort_orderHidden->Get()."\n";

				$fuseaction = (isset($pageInfo['adparentid'])) ? 'EditAdParent' : 'AddAdParent';
				$fuseactionHidden =& Piwi::CreateWidget('HiddenEntry', 'fuseaction', $fuseaction);
		        $form_content .= $fuseactionHidden->Get()."\n";

				$featured = (isset($pageInfo['adparentfeatured'])) ? $pageInfo['adparentfeatured'] : 'N';
				$featuredHidden =& Piwi::CreateWidget('HiddenEntry', 'adparentFeatured', $featured);
		        $form_content .= $featuredHidden->Get()."\n";
				
				$image_code = (isset($pageInfo['adparentimage_code'])) ? $pageInfo['adparentimage_code'] : '';
				$image_codeHidden =& Piwi::CreateWidget('HiddenEntry', 'adparentimage_code', $image_code);
		        $form_content .= $image_codeHidden->Get()."\n";
				
				if ($account === false) {
					// Active
					$helpString = '';
					foreach($helpContent as $help) {		            
						if ($help[0] == _t('ADS_ACTIVE')) {
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
					$active = (isset($pageInfo['adparentactive'])) ? $pageInfo['adparentactive'] : 'Y';
					$activeCombo =& Piwi::CreateWidget('Combo', 'adparentActive');
					$activeCombo->AddOption(_t('GLOBAL_YES'), 'Y');
					$activeCombo->AddOption(_t('GLOBAL_NO'), 'N');
					$activeCombo->SetDefault($active);
					$activeCombo->setTitle(_t('ADS_ACTIVE'));
					$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"adparentActive\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$activeCombo->Get()."</td></tr>";
				} else {
					$activeHidden =& Piwi::CreateWidget('HiddenEntry', 'adparentActive', 'N');
					$form_content .= $activeHidden->Get()."\n";
				}
					
				// Parent
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('ADS_PARENT')) {
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
				$post_url = (isset($pageInfo['adparentparent']) && !strpos($pageInfo['adparentparent'], "://")) ? $pageInfo['adparentparent'] : '';
				$urlListCombo =& Piwi::CreateWidget('Combo', 'adparentParent');
				$urlListCombo->setID('adparentParent');

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
				$urlListCombo->setTitle(_t('ADS_PARENT'));
				$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"pid\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$urlListCombo->Get()."</td></tr>";

				// Title
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('ADS_TITLE')) {
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
				$title = (isset($pageInfo['adparentcategory_name'])) ? $pageInfo['adparentcategory_name'] : '';
				$titleEntry =& Piwi::CreateWidget('Entry', 'adparentCategory_Name', $title);
				$titleEntry->SetTitle(_t('ADS_TITLE'));
				$titleEntry->SetStyle('direction: ltr; width: 300px;');
				$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"adparentCategory_Name\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$titleEntry->Get()."</td></tr>";

				// Description
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('ADS_DESCRIPTIONFIELD')) {
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
				$content = (isset($pageInfo['adparentdescription'])) ? $pageInfo['adparentdescription'] : '';
				$editor =& $GLOBALS['app']->LoadEditor('Ads', 'adparentDescription', $content, false);
				$editor->TextArea->SetStyle('width: 100%;');
				//$editor->SetWidth('100%');
				$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"adparentDescription\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$editor->Get()."</td></tr>";

				// Image
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('ADS_IMAGE')) {
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
				$image = (isset($pageInfo['adparentimage'])) ? $pageInfo['adparentimage'] : '';
				$image_src = $GLOBALS['app']->getDataURL() . 'files'.$xss->filter($pageInfo['adparentimage']);
				$image_preview = '';
				if ($image != '' && file_exists($image_src)) { 
					$image_preview .= "<br /><img border=\"0\" src=\"".$image_src."\" width=\"80\"".(strtolower(substr($image, -3)) == 'gif' || strtolower(substr($image, -3)) == 'png' || strtolower(substr($image, -3)) == 'bmp' ? ' height="80"' : '')." align=\"left\" style=\"padding: 5px visibility: visible;\" id=\"main_image_src\"><br /><b><a id=\"imageDelete\" href=\"javascript:void(0);\" onclick=\"document.getElementById('main_image_src').style.visibility = 'hidden'; document.getElementById('adparentImage').value = '';\">Delete</a></b>";
				}
				$form_content .= '<tr style="display: '.($image != "" || !isset($pageInfo['adparentid']) ? 'none;' : ';').'" id="imageButton">';
				$form_content .= '<td class="syntacts-form-row" valign="top"><input TYPE="button" VALUE="Insert Media" onClick="toggleNo(\'imageButton\'); toggleYes(\'imageRow\'); toggleYes(\'imageInfo\'); toggleNo(\'imageGadgetRow\'); toggleYes(\'imageGadgetButton\'); toggleNo(\'imageCodeInfo\'); toggleYes(\'imageCodeButton\');" style="font-family: Arial; font-size: 10pt; font-weight: bold"></td>';
				$form_content .= '<td colspan="3" class="syntacts-form-row">&nbsp;</td>';
				$form_content .= '</tr>';
				$form_content .= '<TR style="display: '.($image != "" || !isset($pageInfo['adparentid']) ? ';' : 'none;').'" id="imageRow">';
				$form_content .= '<TD VALIGN="top" colspan="4">';
				$form_content .= '<table border="0" width="100%" cellpadding="0" cellspacing="0">';
				$imageScript = "<script type=\"text/javascript\">Event.observe(window, \"load\",function(){addFileToPost('Ads', 'NULL', 'NULL', 'main_image', 'adparentImage', 1, 500, 34);});</script>";
				$imageHidden =& Piwi::CreateWidget('HiddenEntry', 'adparentImage', $image);
				$imageButton = "&nbsp;&nbsp;&nbsp;&nbsp;<INPUT TYPE=\"button\" VALUE=\"Uploaded Files\" ONCLICK=\"openUploadWindow('adparentImage')\" STYLE=\"font-family: Arial; font-size: 10pt; font-weight: bold\">";
		        $form_content .= "<tr><td class=\"syntacts-form-row\"><div id=\"insertMedia\"><b>Insert Media: </b></div>".$image_preview."</td><td class=\"syntacts-form-row\"><div id=\"imageField\"><div id=\"main_image\" style=\"float: left; width: 500px;\"></div>".$imageScript.$imageHidden->Get().$imageButton."</div></td></tr>";
				  
				// Image Width and Height
				$form_content .= '<tr style="display: '.($image != "" || !isset($pageInfo['adparentid']) ? ';' : 'none;').'" id="imageInfo" class="syntacts-form-row">';
				$form_content .= '<td>&nbsp;</td>';
				$form_content .= '<td colspan="3" valign="top">';
				$form_content .= '<b>';
				$form_content .= '<select size="1" id="adparentimage_width" name="adparentimage_width" onChange="document.getElementById(\'adparentimage_height\').value=0">';
				$image_width = (isset($pageInfo['adparentimage_width'])) ? $pageInfo['adparentimage_width'] : 0;
				$form_content .= '<option value="0"'.($image_width == 0 || !isset($pageInfo['adparentid']) ? ' SELECTED' : '').'>Auto</option>';
				for ($w = 1; $w<950; $w++) { 
					$form_content .= '<option value="'.$w.'"'.($image_width == $w ? ' SELECTED' : '').'>'.$w.'</option>';
				}
				$form_content .= '</select>&nbsp;Width</b>&nbsp;&nbsp;&nbsp;';
				$form_content .= '<b><select size="1" id="adparentimage_height" name="adparentimage_height" onChange="document.getElementById(\'adparentimage_width\').value=0">';
				$image_height = (isset($pageInfo['adparentimage_height'])) ? $pageInfo['adparentimage_height'] : 0;
				$form_content .= '<option value="0"'.($image_height == 0 || !isset($pageInfo['adparentid']) ? ' SELECTED' : '').'>Auto</option>';
				for ($i = 1; $i<950; $i++) { 
					$form_content .= '<option value="'.$i.'"'.($image_height == $i ? ' SELECTED' : '').'>'.$i.'</option>';
				}
				$form_content .= '</select>&nbsp;Height</b>&nbsp;in pixels</td>';
				$form_content .= '</tr>';
				
				// Image URL Type
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('ADS_URLTYPE')) {
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
				$url = (isset($pageInfo['adparenturl'])) ? $pageInfo['adparenturl'] : '';
				$form_content .= '<tr class="syntacts-form-row" id="URLTypeInfo">';
				$form_content .= '<td><label for="adparenturl_type"><nobr>'.$helpString.'</nobr></label></td>';
				$form_content .= '<td colspan="3">';
				$form_content .= '<select NAME="adparenturl_type" SIZE="1" onChange="if (this.value == \'internal\') {toggleYes(\'internalURLInfo\'); toggleNo(\'externalURLInfo\'); toggleYes(\'urlTargetInfo\');};  if (this.value == \'external\') {toggleNo(\'internalURLInfo\'); toggleYes(\'externalURLInfo\'); toggleYes(\'urlTargetInfo\');}; if (this.value == \'imageviewer\') {toggleNo(\'internalURLInfo\'); toggleNo(\'externalURLInfo\'); toggleNo(\'urlTargetInfo\');}; ">';
				$form_content .= '<option value="imageviewer"'.((!empty($url) && $url == "javascript:void(0);") || empty($url) || !isset($pageInfo['adparentid']) ? ' selected' : '').'>Open Image in New Window</option>';
				$form_content .= '<option value="internal" '.(!empty($url) && strpos($url, "://") === false && $url != "javascript:void(0);" ? ' selected' : '').'>Internal</option>';
				$form_content .= '<option value="external" '.(!empty($url) && strpos($url, "://") === true ? ' selected' : '').'>External</option>';
				$form_content .= '</select>';
				$form_content .= '</td>';
				$form_content .= '</tr>';
						
				// Image Internal URL		
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('ADS_INTERNALURL')) {
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
				$form_content .= '<tr style="display: '.((!empty($url) && strpos($url, "://") === true) || $url == "javascript:void(0);" || empty($url) || !isset($pageInfo['adparentid']) ? 'none;' : ';').'" class="syntacts-form-row" id="internalURLInfo">';
				$form_content .= '<td><label for="adparentinternal_url"><nobr>'.$helpString.'</nobr></label></td>';
				$post_url = (!empty($url) && strpos($url, "://") === false) ? $url : '';
				$urlListCombo =& Piwi::CreateWidget('Combo', 'adparentinternal_url');
				$urlListCombo->setID('adparentinternal_url');
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
					if ($help[0] == _t('ADS_EXTERNALURL')) {
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
				$externalUrlEntry =& Piwi::CreateWidget('Entry', 'adparentexternal_url', $external_url);
				$externalUrlEntry->SetTitle(_t('ADS_EXTERNALURL'));
				$externalUrlEntry->SetStyle('direction: ltr; width: 300px;');
				$form_content .= "<tr style=\"display: ".((!empty($url) && strpos($url, "://") === false) || $url == "javascript:void(0);" || empty($url) || !isset($pageInfo['adparentid']) ? 'none;' : ';')."\" class=\"syntacts-form-row\" id=\"externalURLInfo\"><td><label for=\"adparentexternal_url\"><nobr>".$helpString."</nobr></label></td><td colspan=\"3\">".$externalUrlEntry->Get()."</td></tr>";
						
				// Image URL Target
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('ADS_URLTARGET')) {
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
				$url_target = (isset($pageInfo['adparenturl_target'])) ? $pageInfo['adparenturl_target'] : '_self';
				$url_targetCombo =& Piwi::CreateWidget('Combo', 'adparenturl_target');
				$url_targetCombo->AddOption('Open in Same Window', '_self');
				$url_targetCombo->AddOption('Open in a New Window', '_blank');
				$url_targetCombo->SetDefault($url_target);
				$url_targetCombo->setTitle(_t('ADS_URLTARGET'));
				$form_content .= "<tr style=\"display: ".((!empty($url)) || $url == "javascript:void(0);" || empty($url) || !isset($pageInfo['adparentid']) ? 'none;' : ';')."\" class=\"syntacts-form-row\" id=\"urlTargetInfo\"><td class=\"syntacts-form-row\"><label for=\"adparenturl_target\"><nobr>".$helpString."</nobr></label></td><td colspan=\"3\">".$url_targetCombo->Get()."</td></tr>";
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
						$form_content .= '<br /><b><a id="imageDelete" href="javascript:void(0);" onclick="document.getElementById(\'main_gadget_src\').style.visibility = \'hidden\'; document.getElementById(\'adparentImage\').value = \'\';">Delete</a></b>';
					}
					$form_content .= '</td>';
					$form_content .= '<td colspan="3" class="syntacts-form-row">&nbsp;</td>';
					$form_content .= '</tr>';
				}
				
				// Image HTML
				$image_code = (isset($pageInfo['adparentimage_code'])) ? $pageInfo['adparentimage_code'] : '';
				$form_content .= '<tr style="display: '.(!empty($image_code) && empty($image) ? 'none;' : ';').'" id="imageCodeButton">';
				$form_content .= '<td class="syntacts-form-row" valign="top"><input TYPE="button" VALUE="Insert HTML" onClick="toggleYes(\'imageCodeInfo\'); toggleYes(\'imageButton\'); toggleNo(\'imageRow\'); toggleYes(\'imageGadgetButton\'); toggleNo(\'imageGadgetRow\'); toggleNo(\'imageCodeButton\');" STYLE="font-family: Arial; font-size: 10pt; font-weight: bold" /></td>';
				$form_content .= '<td colspan="3" class="syntacts-form-row">&nbsp;</td>';
				$form_content .= '</tr>';
				$form_content .= '<tr style="display: '.(!empty($image_code) && empty($image) ? ';' : 'none;').'" id="imageCodeInfo">';
				$form_content .= '<td class="syntacts-form-row"><b>Insert HTML:</b></td>';
				// send main splash editor HTML to syntacts
				$editorCode=& Piwi::CreateWidget('TextArea', 'adparentimage_code', $image_code);
				$editorCode->SetStyle('width: 490px;');
				$editorCode->SetID('adparentimage_code');
				$form_content .= '<td colspan="2" class="syntacts-form-row">'.$editorCode->Get().'</td>';
				$form_content .= '<td class="syntacts-form-row"><b><a id="imageDelete" href="javascript:void(0);" onclick="document.getElementById(\'adparentimage_code\').value = \'\';">Delete</a></b></td>';
				$form_content .= '</tr>';
				
				*/

				/*
				// RSS URL
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('ADS_RSSURL')) {
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
				$url = (isset($pageInfo['adparentrss_url'])) ? $pageInfo['adparentrss_url'] : '';
				$urlEntry =& Piwi::CreateWidget('Entry', 'adparentRss_url', $url);
				$urlEntry->SetTitle(_t('ADS_RSSURL'));
				$urlEntry->SetStyle('direction: ltr; width: 300px;');
				$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"adparentRss_url\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$urlEntry->Get()."</td></tr>";
				*/
				if ($error != '') {
					$stpl->SetVariable('content', $error);
				} else {
					$stpl->SetVariable('content', $form_content);
		        }
				$stpl->ParseBlock('form');
				$page = $stpl->Get();
			} else {
				$page = _t('ADS_ERROR_ASPPAGE_NOT_RETRIEVED', $snoopy->error)."\n";
			}
			
			$tpl->SetVariable('content', $page);
		} else {
			return new Jaws_Error(_t('ADS_ERROR_ASPPAGE_NOT_RETRIEVED'), _t('ADS_NAME'));
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
    function form_post($account = false)
    {
		// check session
		if ($account === false) {
			$GLOBALS['app']->Session->CheckPermission('Ads', 'default');
		} else {
			$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
			if (!$GLOBALS['app']->Session->GetPermission('Ads', 'default')) {
				if (!$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Ads', 'OwnAds')) {
		            $GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
					$userHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
					return $userHTML->DefaultAction();
				}
			}
		}

		require_once JAWS_PATH . 'include/Jaws/Header.php';
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');        
		
		$request =& Jaws_Request::getInstance();
		$fuseaction = $request->get('fuseaction', 'post');
		
		$get  = $request->get(array('fuseaction', 'linkid', 'id'), 'get');
        if (is_null($fuseaction)) {
			$fuseaction = $get['fuseaction'];
		}
        
		$adminModel = $GLOBALS['app']->LoadGadget('Ads', 'AdminModel');
		$model = $GLOBALS['app']->LoadGadget('Ads', 'Model');

        if (!empty($fuseaction)) {		
			switch($fuseaction) {
                case "AddAdParent": 
						$keys = array(
							'adparentParent', 'adparentsort_order', 'adparentCategory_Name', 
							'adparentImage', 'adparentDescription', 'adparentActive',
							'adparentFeatured', 'adparentRss_url', 
							'adparentimage_width', 'adparentimage_height', 'adparentimage_code',
							'adparenturl_type', 'adparentinternal_url', 'adparentexternal_url', 
							'adparenturl_target'
						);
						$postData = $request->getRaw($keys, 'post');
						//foreach($postData as $key => $value) {
							//echo $key."=".$value."\n";
						//}
						// add OwnerID if no permissions
						if ($GLOBALS['app']->Session->GetPermission('Ads', 'ManageAdParents') && $account === false) {
							$OwnerID = null;
						} else {
							$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
						}
						$result = $adminModel->AddAdParent(
							$postData['adparentParent'], $postData['adparentsort_order'], $postData['adparentCategory_Name'], 
							$postData['adparentImage'], $postData['adparentDescription'], $postData['adparentActive'], 
							$OwnerID, $postData['adparentFeatured'], $postData['adparentRss_url'], $postData['adparenturl_type'], 
							$postData['adparentinternal_url'], $postData['adparentexternal_url'], $postData['adparenturl_target'],
							/* $postData['adparentimage_width'], $postData['adparentimage_height'],*/ $postData['adparentimage_code']  
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
                case "EditAdParent": 
						$keys = array(
							'adparentID', 'adparentParent', 'adparentsort_order', 'adparentCategory_Name', 
							'adparentImage', 'adparentDescription', 'adparentActive', 
							'adparentFeatured', 'adparentRss_url', 
							'adparentimage_width', 'adparentimage_height', 'adparentimage_code',
							'adparenturl_type', 'adparentinternal_url', 'adparentexternal_url', 
							'adparenturl_target'
						);
						$postData = $request->getRaw($keys, 'post');
						if (isset($postData['adparentID'])) {
							// add OwnerID if no permissions
							if ($GLOBALS['app']->Session->GetPermission('Ads', 'ManageAdParents') && $account === false) {
								$OwnerID = null;			
								$result = $adminModel->UpdateAdParent(
									(int)$postData['adparentID'], $postData['adparentParent'], 
									$postData['adparentCategory_Name'], $postData['adparentsort_order'], $postData['adparentDescription'], 
									$postData['adparentImage'], $postData['adparentFeatured'], $postData['adparentActive'],
									$postData['adparentRss_url'], $postData['adparenturl_type'], $postData['adparentinternal_url'], 
									$postData['adparentexternal_url'], $postData['adparenturl_target'],
									/* $postData['adparentimage_width'], $postData['adparentimage_height'],*/ $postData['adparentimage_code']
								);
							} else {
								$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
								$parent = $model->GetAdParent((int)$postData['adparentID']);
								if ($OwnerID == $parent['adparentownerid']) {
									$result = $adminModel->UpdateAdParent(
										$parent['adparentID'], $postData['adparentParent'], $postData['adparentCategory_Name'], 
										$postData['adparentsort_order'], $postData['adparentDescription'], 
										$postData['adparentImage'], $postData['adparentFeatured'], $postData['adparentActive'], 
										$postData['adparentRss_url'], $postData['adparentimage_width'], 
										$postData['adparentimage_height'], $postData['adparentimage_code'], $postData['adparenturl_type'], 
										$postData['adparentinternal_url'], $postData['adparentexternal_url'], $postData['adparenturl_target']
									);
								} else {
									$GLOBALS['app']->Session->PushLastResponse(_t('ADS_ERROR_ADPARENT_NOT_UPDATED'), RESPONSE_ERROR);
									//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
								}
							}
						} else {
							$GLOBALS['app']->Session->PushLastResponse(_t('ADS_ERROR_ADPARENT_NOT_UPDATED'), RESPONSE_ERROR);
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
                case "DeleteAdParent": 
				        //$keys = array('idarray', 'ID', 'xcount');
				        $keys = array('adparentID');
						$postData = $request->getRaw($keys, 'post');
						$id = $postData['adparentID'];
						if (is_null($id)) {
							$id = $get['id'];
						}
						// delete each ID
						if ($id) {
							// check OwnerID if no permissions
							if ($GLOBALS['app']->Session->GetPermission('Ads', 'ManageAdParents') && $account === false) {
								$result = $adminModel->DeleteAdParent($id);
							} else {
								$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
								$parent = $model->GetAdParent($id);
								if ($OwnerID == $parent['adparentownerid']) {
									$result = $adminModel->DeleteAdParent($id);
								} else {
									$GLOBALS['app']->Session->PushLastResponse(_t('ADS_ERROR_ADPARENT_NOT_DELETED'), RESPONSE_ERROR);
									//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
								}
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
                case "AddAds": 
						$keys = array(
							'type', 'url', 'title', 'image', 'keyword', 'sitewide', 'Active', 'barcode_type',
							'barcode_data', 'description', 'LinkID', 'BrandID'
						);
						$postData = $request->get($keys, 'post');
						//foreach($postData as $key => $value) {
							//echo $key."=".$value."\n";
						//}
						// add OwnerID if no permissions
						if ($GLOBALS['app']->Session->GetPermission('Ads', 'ManageAds') && $account === false) {
							$OwnerID = null;
						} else {
							$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
						}
						if ($account === true) {
							$postData['Active'] = 'N';
						}
						$result = $adminModel->AddAd($postData['LinkID'], $postData['BrandID'], $postData['type'], 
							$postData['image'], $postData['url'], $postData['title'], $postData['keyword'], $postData['sitewide'], 
							$OwnerID, $postData['Active'], $postData['barcode_type'], $postData['barcode_data'], $postData['description']
						);
						if ($result && !Jaws_Error::IsError($result)) {
					        // declare result as ok for later
							$result3 = true;
						} else {
							$result3 = false;
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
                case "EditAds": 
						$keys = array(
							'ID', 'type', 'url', 'title', 'image', 'keyword', 'sitewide', 'Active', 'barcode_type',
							'barcode_data', 'description', 'LinkID', 'BrandID'
						);
						$postData = $request->get($keys, 'post');
						//foreach($postData as $key => $value) {
						//	echo $key."=".$value."\n";
						//}
						if (isset($postData['ID'])) {
							if ($account === true) {
								$postData['Active'] = 'N';
							}
							// add OwnerID if no permissions
							if ($GLOBALS['app']->Session->GetPermission('Ads', 'ManageAds') && $account === false) {
								$result = $adminModel->UpdateAd($postData['ID'], $postData['LinkID'], $postData['BrandID'], $postData['type'], $postData['image'], $postData['url'], 
								$postData['title'], $postData['keyword'], $postData['sitewide'], $postData['Active'], $postData['barcode_type'], 
								$postData['barcode_data'], $postData['description']
								);
							} else {
								$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
								$parent = $model->GetAd((int)$postData['ID']);
								if ($OwnerID == $parent['ownerid']) {
									$result = $adminModel->UpdateAd($postData['ID'], $postData['LinkID'], $postData['BrandID'], $postData['type'], $postData['image'], $postData['url'], 
									$postData['title'], $postData['keyword'], $postData['sitewide'], $postData['Active'], $postData['barcode_type'], 
									$postData['barcode_data'], $postData['description']
									);
								} else {
									$GLOBALS['app']->Session->PushLastResponse(_t('ADS_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403')), RESPONSE_ERROR);
									//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
								}
							}
						}
						if ($result && !Jaws_Error::IsError($result)) {
							$result3 = true;
						} else {
							$result3 = false;
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
                case "DeleteAds": 
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
							if ($GLOBALS['app']->Session->GetPermission('Ads', 'ManageAds') && $account === false) {
								$result = $adminModel->DeleteAd($id);
							} else {
								$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
								$parent = $model->GetAd($id);
								if ($OwnerID == $parent['ownerid']) {
									$result = $adminModel->DeleteAd($id);
								} else {
									return _t('ADS_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403'));												
								}
							}
						}						
						if ($result && !Jaws_Error::IsError($result)) {
							$result4 = true;
						} else {
							$result4 = false;
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
                case "AddSavedAd": 
						$keys = array(
							'ad_id', 'status', 'description'
						);
						$postData = $request->get($keys, 'post');
						//foreach($postData as $key => $value) {
							//echo $key."=".$value."\n";
						//}
						$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
							$result = $adminModel->AddSavedAd($postData['ad_id'], $postData['status'], $OwnerID, $postData['description']
						);
						if ($result && !Jaws_Error::IsError($result)) {
					        // declare result as ok for later
							$result3 = true;
						} else {
							$result3 = false;
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
                case "EditSavedAd": 
						$keys = array('ID', 'status', 'description');
						$postData = $request->get($keys, 'post');
						//foreach($postData as $key => $value) {
						//	echo $key."=".$value."\n";
						//}
						if (isset($postData['ID'])) {
							// add OwnerID if no permissions
							$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
							$parent = $model->GetSavedAd((int)$postData['ID']);
							if ($OwnerID == $parent['ownerid']) {
								$result = $adminModel->UpdateAd($postData['ID'], $postData['status'], $postData['description']);
							} else {
								$GLOBALS['app']->Session->PushLastResponse(_t('ADS_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403')), RESPONSE_ERROR);
								//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
							}
						}
						if ($result && !Jaws_Error::IsError($result)) {
							$result3 = true;
						} else {
							$result3 = false;
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
                case "DeleteSavedAd": 
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
							$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
							$parent = $model->GetSavedAd($id);
							if ($OwnerID == $parent['ownerid']) {
								$result = $adminModel->DeleteSavedAd($id);
							} else {
								return _t('ADS_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403'));												
							}
						}						
						if ($result && !Jaws_Error::IsError($result)) {
							$result4 = true;
						} else {
							$result4 = false;
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
                case "AddBrand": 
				        $keys = array(
							'title', 'description', 
							'Image', 'image_width', 'image_height', 'layout', 'Active', 'url_type', 
							'internal_url', 'external_url', 'url_target', 'image_code'
						);
						$postData = $request->getRaw($keys, 'post');
						//foreach($postData as $key => $value) {
							//echo $key."=".$value."\n";
						//}
						// add OwnerID if no permissions
						if ($GLOBALS['app']->Session->GetPermission('Ads', 'ManageAds') && $account === false) {
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
							$result5 = true;
						} else {
							$result5 = false;
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
                case "EditBrand": 
				        $keys = array(
							'ID', 'title', 'description', 
							'Image', 'image_width', 'image_height', 'layout', 'Active', 'url_type', 
							'internal_url', 'external_url', 'url_target', 'image_code'
						);
						$postData = $request->getRaw($keys, 'post');
						//foreach($postData as $key => $value) {
							//echo $key."=".$value."\n";
						//}
						if ($postData['ID']) {
							// check OwnerID if no permissions
							if ($GLOBALS['app']->Session->GetPermission('Ads', 'ManageAds') && $account === false) {
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
									$GLOBALS['app']->Session->PushLastResponse(_t('ADS_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403')), RESPONSE_ERROR);
									//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
								}
							}
						}
						if (!Jaws_Error::IsError($result)) {
							$result5 = true;
						} else {
							$result5 = false;
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
                case "DeleteBrand": 
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
								if ($GLOBALS['app']->Session->GetPermission('Ads', 'ManageAds') && $account === false) {
									$result = $adminModel->DeleteBrand((int)$v);
								} else {
									$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
									$post = $model->GetBrand((int)$v);
									if ($OwnerID == $post['ownerid']) {
										$result = $adminModel->DeleteBrand((int)$v);
									} else {
										$GLOBALS['app']->Session->PushLastResponse(_t('ADS_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403')), RESPONSE_ERROR);
										//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
									}
								}								
								$dcount++;
							}
						} else if (!is_null($id)) {
							if ($GLOBALS['app']->Session->GetPermission('Ads', 'ManageAds') && $account === false) {
								$result = $adminModel->DeleteBrand($id);
							} else {
								$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
								$post = $model->GetBrand($id);
								if ($OwnerID == $post['ownerid']) {
									$result = $adminModel->DeleteBrand($id);
								} else {
									$GLOBALS['app']->Session->PushLastResponse(_t('ADS_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403')), RESPONSE_ERROR);
									//Jaws_Header::Location($_SERVER['HTTP_REFERER']);
								}
							}
						}
						if (!Jaws_Error::IsError($result)) {
							$result6 = true;
						} else {
							$result6 = false;
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
					$redirect = BASE_SCRIPT . '?gadget=Ads&action=A&id='.$result;
				} else {
					$redirect = BASE_SCRIPT . '?gadget=Ads&action=A&id='.$postData['adparentID'];
				}
			} else if ($result2 === true) {
				$redirect = BASE_SCRIPT . '?gadget=Ads&action=Admin';
			} else if ($result3 === true) {
				if (is_numeric($result)) {
					$redirect = BASE_SCRIPT . '?gadget=Ads&action=view&id='.$result;
				} else {
					$redirect = BASE_SCRIPT . '?gadget=Ads&action=view&id='.$postData['ID'];
				}
			} else if ($result4 === true) {
				$redirect = BASE_SCRIPT . '?gadget=Ads&action=A';
			} else if ($result5 === true || $result6 === true) {
				$redirect = BASE_SCRIPT . '?gadget=Ads&action=B';
			} else {
				if ($account === false) {
					Jaws_Header::Location(BASE_SCRIPT . '?gadget=Ads');
				} else {
					Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'));
				}
			}
			
			if ($account === false) {
				Jaws_Header::Location($redirect);
			} else {
				if ($result1 === true) {
					$output_html = "";
					$output_html .= "<script>\n";
					$output_html .= "	if (window.opener && !window.opener.closed) {\n";
					$output_html .= "		window.opener.location.reload();\n";
					$output_html .= "	}\n";
					$output_html .= "	window.location.href='index.php?gadget=Ads&action=account_view&id=".(is_numeric($result) ? $result : $postData['ID'])."';\n";
					$output_html .= "</script>\n";
					$output_html .= "<noscript><div style='color:#FF0000; font-weight: bold;'>Javascript must be enabled in your browser in order to use this service.</div></noscript>\n";
					return $output_html;
				} else if ($result2 === true) {
					$output_html = "";
					$output_html .= "<script>\n";
					$output_html .= "if (window.opener && !window.opener.closed) {\n";
					$output_html .= "	window.opener.location.reload();\n";
					$output_html .= "	window.close();\n";
					$output_html .= "}\n";
					$output_html .= "</script>\n";
					$output_html .= "<noscript><div style='color:#FF0000; font-weight: bold;'>Javascript must be enabled in your browser in order to use this service.</div></noscript>\n";
					return $output_html;
				} else if ($result3 === true) {
					$output_html = "";
					$output_html .= "<script>\n";
					$output_html .= "	parent.parent.location.reload();\n";
					$output_html .= "	//parent.parent.hideGB();\n";
					$output_html .= "</script>\n";
					$output_html .= "<noscript><div style='color:#FF0000; font-weight: bold;'>Javascript must be enabled in your browser in order to use this service.</div></noscript>\n";
					return $output_html;
					//$redirect = 'index.php?gadget=Ads&action=account_view&id='.$postData['LinkID'];
					//Jaws_Header::Location($redirect);
				}
			}

		} else {
			if ($account === false) {
				Jaws_Header::Location(BASE_SCRIPT . '?gadget=Ads');
			} else {
				Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'));
			}
		}

    }


    /**
     * Display the ads
     *
     * @access public
     * @return string
     */
    function A($account = false)
    {
		if ($account === false) {
			$GLOBALS['app']->Session->CheckPermission('Ads', 'default');
		} else {
			$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
			if (!$GLOBALS['app']->Session->GetPermission('Ads', 'default')) {
				if (!$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Ads', 'OwnAds')) {
		            //$GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
					return "Please log-in.";
				}
			}
		}

		$model = $GLOBALS['app']->LoadGadget('Ads', 'Model');
        $tpl = new Jaws_Template('gadgets/Ads/templates/');
        $tpl->Load('admin.html');
        $tpl->SetBlock('ads_admin');
        
		$request =& Jaws_Request::getInstance();
		$action = $request->get('action', 'get');
		$pid = $request->get('id', 'post');
		if (empty($pid)) {
			$pid = $request->get('id', 'get');
		}
		$submit_vars['EDIT_BUTTON'] = '';
		$submit_vars['CLOSE_BUTTON'] = '';
		$submit_vars['DELETE_BUTTON'] = '';

		if ($account === false) {
			if (!empty($pid)) {
				$pageInfo = $model->GetAdParent((int)$pid);
				if ((!Jaws_Error::IsError($pageInfo) && ($GLOBALS['app']->Session->GetPermission('Ads', 'ManageAds') || $pageInfo['adparentownerid'] == $OwnerID))) {
					$GLOBALS['app']->Layout->AddHeadOther('<style>#form_content { display: none; }</style>');
					$submit_vars['CLOSE_BUTTON'] = "&nbsp;<input type=\"button\" value=\"Cancel\" onclick=\"location.href='" . BASE_SCRIPT . "?gadget=Ads&amp;action=Admin';\">";
					$submit_vars['DELETE_BUTTON'] = "&nbsp;<input type=\"button\" name=\"Delete\" onclick=\"if (confirm('Do you want to delete this category? This cannot be undone.')) { location.href = 'admin.php?gadget=Ads&amp;action=form_post&fuseaction=DeleteAdParent&amp;id=".$pid."'; };\" value=\"Delete\">";
					$submit_vars['EDIT_BUTTON'] = "<input type=\"button\" name=\"Edit\" onclick=\"document.getElementById('form_content').style.display = 'block'; document.getElementById('view_content').style.display = 'none';\" value=\"Edit\">";

					$syntactsUrl = $GLOBALS['app']->getSyntactsAdminHTMLUrl("Ads/admin_Ads_form", 'html');

					// snoopy
					include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
					$snoopy = new Snoopy('Ads');
					$submit_url = $syntactsUrl;
					
					if($snoopy->fetch($submit_url)) {
						$submit_vars['SUBMIT_BUTTON'] = "<input type='Submit' name='Submit' value='Update'>";
						$submit_vars['CLOSE_BUTTON2'] = "document.getElementById('form_content').style.display = 'none'; document.getElementById('view_content').style.display = '';";
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

						// send requesting URL to syntacts
						$stpl->SetVariable('HTTP_REFERER', $GLOBALS['app']->GetSiteURL());
						//$stpl->SetVariable('DPATH', JAWS_DPATH);
						$stpl->SetVariable('actionprefix', '');
						$stpl->SetVariable('gadget', 'Ads');
						$stpl->SetVariable('CLOSE_BUTTON', $submit_vars['CLOSE_BUTTON2']);
						$stpl->SetVariable('SUBMIT_BUTTON', $submit_vars['SUBMIT_BUTTON']);
						$stpl->SetVariable('controller', BASE_SCRIPT);
					
						// Get Help documentation
						$help_url = $GLOBALS['app']->getSyntactsAdminHTMLUrl("Ads/admin_Ads_form_help", 'txt');
						$snoopy = new Snoopy('Ads');
				
						if($snoopy->fetch($help_url)) {
							$helpContent = Jaws_Utils::split2D($snoopy->results);
						}
										
						// Hidden elements
						$ID = (isset($pageInfo['adparentid'])) ? $pageInfo['adparentid'] : '';
						$idHidden =& Piwi::CreateWidget('HiddenEntry', 'adparentID', $ID);
						$form_content .= $idHidden->Get()."\n";

						$sort_order = (isset($pageInfo['adparentsort_order'])) ? $pageInfo['adparentsort_order'] : '0';
						$sort_orderHidden =& Piwi::CreateWidget('HiddenEntry', 'adparentsort_order', $sort_order);
						$form_content .= $sort_orderHidden->Get()."\n";

						$fuseaction = (isset($pageInfo['adparentid'])) ? 'EditAdParent' : 'AddAdParent';
						$fuseactionHidden =& Piwi::CreateWidget('HiddenEntry', 'fuseaction', $fuseaction);
						$form_content .= $fuseactionHidden->Get()."\n";

						$featured = (isset($pageInfo['adparentfeatured'])) ? $pageInfo['adparentfeatured'] : 'N';
						$featuredHidden =& Piwi::CreateWidget('HiddenEntry', 'adparentFeatured', $featured);
						$form_content .= $featuredHidden->Get()."\n";
						
						$image_code = (isset($pageInfo['adparentimage_code'])) ? $pageInfo['adparentimage_code'] : '';
						$image_codeHidden =& Piwi::CreateWidget('HiddenEntry', 'adparentimage_code', $image_code);
						$form_content .= $image_codeHidden->Get()."\n";
						
						if ($account === false) {
							// Active
							$helpString = '';
							foreach($helpContent as $help) {		            
								if ($help[0] == _t('ADS_PUBLISHED')) {
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
							$active = (isset($pageInfo['adparentactive'])) ? $pageInfo['adparentactive'] : 'Y';
							$activeCombo =& Piwi::CreateWidget('Combo', 'adparentActive');
							$activeCombo->AddOption(_t('ADS_PUBLISHED'), 'Y');
							$activeCombo->AddOption(_t('ADS_DRAFT'), 'N');
							$activeCombo->SetDefault($active);
							$activeCombo->setTitle(_t('ADS_PUBLISHED'));
							$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"adparentActive\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$activeCombo->Get()."</td></tr>";
						} else {
							$activeHidden =& Piwi::CreateWidget('HiddenEntry', 'adparentActive', 'N');
							$form_content .= $activeHidden->Get()."\n";
						}
							
						// Parent
						$helpString = '';
						foreach($helpContent as $help) {		            
							if ($help[0] == _t('ADS_PARENT')) {
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
						$post_url = (isset($pageInfo['adparentparent']) && !strpos($pageInfo['adparentparent'], "://")) ? $pageInfo['adparentparent'] : '';
						$urlListCombo =& Piwi::CreateWidget('Combo', 'adparentParent');
						$urlListCombo->setID('adparentParent');

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
						$urlListCombo->setTitle(_t('ADS_PARENT'));
						$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"pid\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$urlListCombo->Get()."</td></tr>";

						// Title
						$helpString = '';
						foreach($helpContent as $help) {		            
							if ($help[0] == _t('ADS_TITLE')) {
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
						$title = (isset($pageInfo['adparentcategory_name'])) ? $pageInfo['adparentcategory_name'] : '';
						$titleEntry =& Piwi::CreateWidget('Entry', 'adparentCategory_Name', $title);
						$titleEntry->SetTitle(_t('ADS_TITLE'));
						$titleEntry->SetStyle('direction: ltr; width: 300px;');
						$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"adparentCategory_Name\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$titleEntry->Get()."</td></tr>";

						// Description
						$helpString = '';
						foreach($helpContent as $help) {		            
							if ($help[0] == _t('ADS_DESCRIPTIONFIELD')) {
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
						$content = (isset($pageInfo['adparentdescription'])) ? $pageInfo['adparentdescription'] : '';
						$editor =& $GLOBALS['app']->LoadEditor('Ads', 'adparentDescription', $content, false);
						$editor->TextArea->SetStyle('width: 100%;');
						//$editor->SetWidth('100%');
						$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"adparentDescription\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$editor->Get()."</td></tr>";

						// Image
						$helpString = '';
						foreach($helpContent as $help) {		            
							if ($help[0] == _t('ADS_IMAGE')) {
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
						$image = (isset($pageInfo['adparentimage'])) ? $pageInfo['adparentimage'] : '';
						$image_src = $GLOBALS['app']->getDataURL() . 'files'.$pageInfo['adparentimage'];
						$image_preview = '';
						if ($image != '' && file_exists($image_src)) { 
							$image_preview .= "<br /><img border=\"0\" src=\"".$image_src."\" width=\"80\"".(strtolower(substr($image, -3)) == 'gif' || strtolower(substr($image, -3)) == 'png' || strtolower(substr($image, -3)) == 'bmp' ? ' height="80"' : '')." align=\"left\" style=\"padding: 5px visibility: visible;\" id=\"main_image_src\"><br /><b><a id=\"imageDelete\" href=\"javascript:void(0);\" onclick=\"document.getElementById('main_image_src').style.visibility = 'hidden'; document.getElementById('adparentImage').value = '';\">Delete</a></b>";
						}
						$form_content .= '<tr style="display: '.($image != "" || !isset($pageInfo['adparentid']) ? 'none;' : ';').'" id="imageButton">';
						$form_content .= '<td class="syntacts-form-row" valign="top"><input TYPE="button" VALUE="Insert Media" onClick="toggleNo(\'imageButton\'); toggleYes(\'imageRow\'); toggleYes(\'imageInfo\'); toggleNo(\'imageGadgetRow\'); toggleYes(\'imageGadgetButton\'); toggleNo(\'imageCodeInfo\'); toggleYes(\'imageCodeButton\');" style="font-family: Arial; font-size: 10pt; font-weight: bold"></td>';
						$form_content .= '<td colspan="3" class="syntacts-form-row">&nbsp;</td>';
						$form_content .= '</tr>';
						$form_content .= '<TR style="display: '.($image != "" || !isset($pageInfo['adparentid']) ? ';' : 'none;').'" id="imageRow">';
						$form_content .= '<TD VALIGN="top" colspan="4">';
						$form_content .= '<table border="0" width="100%" cellpadding="0" cellspacing="0">';
						$imageScript = "<script type=\"text/javascript\">Event.observe(window, \"load\",function(){addFileToPost('Ads', 'NULL', 'NULL', 'main_image', 'adparentImage', 1, 500, 34);});</script>";
						$imageHidden =& Piwi::CreateWidget('HiddenEntry', 'adparentImage', $image);
						$imageButton = "&nbsp;&nbsp;&nbsp;&nbsp;<INPUT TYPE=\"button\" VALUE=\"Uploaded Files\" ONCLICK=\"openUploadWindow('adparentImage')\" STYLE=\"font-family: Arial; font-size: 10pt; font-weight: bold\">";
						$form_content .= "<tr><td class=\"syntacts-form-row\"><div id=\"insertMedia\"><b>Insert Media: </b></div>".$image_preview."</td><td class=\"syntacts-form-row\"><div id=\"imageField\"><div id=\"main_image\" style=\"float: left; width: 500px;\"></div>".$imageScript.$imageHidden->Get().$imageButton."</div></td></tr>";
						  
						// Image Width and Height
						$form_content .= '<tr style="display: '.($image != "" || !isset($pageInfo['adparentid']) ? ';' : 'none;').'" id="imageInfo" class="syntacts-form-row">';
						$form_content .= '<td>&nbsp;</td>';
						$form_content .= '<td colspan="3" valign="top">';
						$form_content .= '<b>';
						$form_content .= '<select size="1" id="adparentimage_width" name="adparentimage_width" onChange="document.getElementById(\'adparentimage_height\').value=0">';
						$image_width = (isset($pageInfo['adparentimage_width'])) ? $pageInfo['adparentimage_width'] : 0;
						$form_content .= '<option value="0"'.($image_width == 0 || !isset($pageInfo['adparentid']) ? ' SELECTED' : '').'>Auto</option>';
						for ($w = 1; $w<950; $w++) { 
							$form_content .= '<option value="'.$w.'"'.($image_width == $w ? ' SELECTED' : '').'>'.$w.'</option>';
						}
						$form_content .= '</select>&nbsp;Width</b>&nbsp;&nbsp;&nbsp;';
						$form_content .= '<b><select size="1" id="adparentimage_height" name="adparentimage_height" onChange="document.getElementById(\'adparentimage_width\').value=0">';
						$image_height = (isset($pageInfo['adparentimage_height'])) ? $pageInfo['adparentimage_height'] : 0;
						$form_content .= '<option value="0"'.($image_height == 0 || !isset($pageInfo['adparentid']) ? ' SELECTED' : '').'>Auto</option>';
						for ($i = 1; $i<950; $i++) { 
							$form_content .= '<option value="'.$i.'"'.($image_height == $i ? ' SELECTED' : '').'>'.$i.'</option>';
						}
						$form_content .= '</select>&nbsp;Height</b>&nbsp;in pixels</td>';
						$form_content .= '</tr>';
						
						// Image URL Type
						$helpString = '';
						foreach($helpContent as $help) {		            
							if ($help[0] == _t('ADS_URLTYPE')) {
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
						$url = (isset($pageInfo['adparenturl'])) ? $pageInfo['adparenturl'] : '';
						$form_content .= '<tr class="syntacts-form-row" id="URLTypeInfo">';
						$form_content .= '<td><label for="adparenturl_type"><nobr>'.$helpString.'</nobr></label></td>';
						$form_content .= '<td colspan="3">';
						$form_content .= '<select NAME="adparenturl_type" SIZE="1" onChange="if (this.value == \'internal\') {toggleYes(\'internalURLInfo\'); toggleNo(\'externalURLInfo\'); toggleYes(\'urlTargetInfo\');};  if (this.value == \'external\') {toggleNo(\'internalURLInfo\'); toggleYes(\'externalURLInfo\'); toggleYes(\'urlTargetInfo\');}; if (this.value == \'imageviewer\') {toggleNo(\'internalURLInfo\'); toggleNo(\'externalURLInfo\'); toggleNo(\'urlTargetInfo\');}; ">';
						$form_content .= '<option value="imageviewer"'.((!empty($url) && $url == "javascript:void(0);") || empty($url) || !isset($pageInfo['adparentid']) ? ' selected' : '').'>Open Image in New Window</option>';
						$form_content .= '<option value="internal" '.(!empty($url) && strpos($url, "://") === false && $url != "javascript:void(0);" ? ' selected' : '').'>Internal</option>';
						$form_content .= '<option value="external" '.(!empty($url) && strpos($url, "://") === true ? ' selected' : '').'>External</option>';
						$form_content .= '</select>';
						$form_content .= '</td>';
						$form_content .= '</tr>';
								
						// Image Internal URL		
						$helpString = '';
						foreach($helpContent as $help) {		            
							if ($help[0] == _t('ADS_INTERNALURL')) {
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
						$form_content .= '<tr style="display: '.((!empty($url) && strpos($url, "://") === true) || $url == "javascript:void(0);" || empty($url) || !isset($pageInfo['adparentid']) ? 'none;' : ';').'" class="syntacts-form-row" id="internalURLInfo">';
						$form_content .= '<td><label for="adparentinternal_url"><nobr>'.$helpString.'</nobr></label></td>';
						$post_url = (!empty($url) && strpos($url, "://") === false) ? $url : '';
						$urlListCombo =& Piwi::CreateWidget('Combo', 'adparentinternal_url');
						$urlListCombo->setID('adparentinternal_url');
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
							if ($help[0] == _t('ADS_EXTERNALURL')) {
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
						$externalUrlEntry =& Piwi::CreateWidget('Entry', 'adparentexternal_url', $external_url);
						$externalUrlEntry->SetTitle(_t('ADS_EXTERNALURL'));
						$externalUrlEntry->SetStyle('direction: ltr; width: 300px;');
						$form_content .= "<tr style=\"display: ".((!empty($url) && strpos($url, "://") === false) || $url == "javascript:void(0);" || empty($url) || !isset($pageInfo['adparentid']) ? 'none;' : ';')."\" class=\"syntacts-form-row\" id=\"externalURLInfo\"><td><label for=\"adparentexternal_url\"><nobr>".$helpString."</nobr></label></td><td colspan=\"3\">".$externalUrlEntry->Get()."</td></tr>";
								
						// Image URL Target
						$helpString = '';
						foreach($helpContent as $help) {		            
							if ($help[0] == _t('ADS_URLTARGET')) {
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
						$url_target = (isset($pageInfo['adparenturl_target'])) ? $pageInfo['adparenturl_target'] : '_self';
						$url_targetCombo =& Piwi::CreateWidget('Combo', 'adparenturl_target');
						$url_targetCombo->AddOption('Open in Same Window', '_self');
						$url_targetCombo->AddOption('Open in a New Window', '_blank');
						$url_targetCombo->SetDefault($url_target);
						$url_targetCombo->setTitle(_t('ADS_URLTARGET'));
						$form_content .= "<tr style=\"display: ".((!empty($url)) || $url == "javascript:void(0);" || empty($url) || !isset($pageInfo['adparentid']) ? 'none;' : ';')."\" class=\"syntacts-form-row\" id=\"urlTargetInfo\"><td class=\"syntacts-form-row\"><label for=\"adparenturl_target\"><nobr>".$helpString."</nobr></label></td><td colspan=\"3\">".$url_targetCombo->Get()."</td></tr>";
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
								$form_content .= '<br /><b><a id="imageDelete" href="javascript:void(0);" onclick="document.getElementById(\'main_gadget_src\').style.visibility = \'hidden\'; document.getElementById(\'adparentImage\').value = \'\';">Delete</a></b>';
							}
							$form_content .= '</td>';
							$form_content .= '<td colspan="3" class="syntacts-form-row">&nbsp;</td>';
							$form_content .= '</tr>';
						}
						
						// Image HTML
						$image_code = (isset($pageInfo['adparentimage_code'])) ? $pageInfo['adparentimage_code'] : '';
						$form_content .= '<tr style="display: '.(!empty($image_code) && empty($image) ? 'none;' : ';').'" id="imageCodeButton">';
						$form_content .= '<td class="syntacts-form-row" valign="top"><input TYPE="button" VALUE="Insert HTML" onClick="toggleYes(\'imageCodeInfo\'); toggleYes(\'imageButton\'); toggleNo(\'imageRow\'); toggleYes(\'imageGadgetButton\'); toggleNo(\'imageGadgetRow\'); toggleNo(\'imageCodeButton\');" STYLE="font-family: Arial; font-size: 10pt; font-weight: bold" /></td>';
						$form_content .= '<td colspan="3" class="syntacts-form-row">&nbsp;</td>';
						$form_content .= '</tr>';
						$form_content .= '<tr style="display: '.(!empty($image_code) && empty($image) ? ';' : 'none;').'" id="imageCodeInfo">';
						$form_content .= '<td class="syntacts-form-row"><b>Insert HTML:</b></td>';
						// send main splash editor HTML to syntacts
						$editorCode=& Piwi::CreateWidget('TextArea', 'adparentimage_code', $image_code);
						$editorCode->SetStyle('width: 490px;');
						$editorCode->SetID('adparentimage_code');
						$form_content .= '<td colspan="2" class="syntacts-form-row">'.$editorCode->Get().'</td>';
						$form_content .= '<td class="syntacts-form-row"><b><a id="imageDelete" href="javascript:void(0);" onclick="document.getElementById(\'adparentimage_code\').value = \'\';">Delete</a></b></td>';
						$form_content .= '</tr>';
						
						*/

						/*
						// RSS URL
						$helpString = '';
						foreach($helpContent as $help) {		            
							if ($help[0] == _t('ADS_RSSURL')) {
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
						$url = (isset($pageInfo['adparentrss_url'])) ? $pageInfo['adparentrss_url'] : '';
						$urlEntry =& Piwi::CreateWidget('Entry', 'adparentRss_url', $url);
						$urlEntry->SetTitle(_t('ADS_RSSURL'));
						$urlEntry->SetStyle('direction: ltr; width: 300px;');
						$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"adparentRss_url\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$urlEntry->Get()."</td></tr>";
						*/
						if ($error != '') {
							$stpl->SetVariable('content', $error);
						} else {
							$stpl->SetVariable('content', $form_content);
						}
						$stpl->ParseBlock('form');
						$page = $stpl->Get();
					} else {
						$page = _t('ADS_ERROR_ASPPAGE_NOT_RETRIEVED', $snoopy->error)."\n";
					}
					
					$tpl->SetVariable('content', $page);
				}
			}
			$this->AjaxMe('script.js');
			$tpl->SetVariable('menubar', $this->MenuBar($action));
			$account_prefix = '';
			$base_url = BASE_SCRIPT;
			
			// TODO: Add search form
			/*
			$search_form = '';
			
			// Search status
			$statusCombo =& Piwi::CreateWidget('Combo', 'searchstatus');
			$statusCombo->AddOption('Show All', '');
			$statusCombo->AddOption(_t('ADS_PUBLISHED'), 'Y');
			$statusCombo->AddOption(_t('ADS_DRAFT'), 'N');
			$statusCombo->SetDefault($searchstatus);
			$statusCombo->setTitle(_t('ADS_ACTIVE'));
			$search_form .= "<td width=\"0%\" valign=\"top\"><nobr>".$statusCombo->Get()."&nbsp;&nbsp;</nobr></td>";

			// Search keyword
			$searchEntry =& Piwi::CreateWidget('Entry', 'searchkeyword', $searchkeyword);
			$searchEntry->SetTitle(_t('ADS_SEARCH'));
			$searchEntry->SetStyle('direction: ltr; width: 120px;');

			$category_select = '';
			if ($account === false) {
				$category_select .= "<select name=\"searchcategory\" id=\"searchcategory\" size=\"1\" onChange=\"location.href = 'admin.php?gadget=Ads&action=A&id='+this.value+'&searchbrand='+$('searchbrand').value;\">\n";
				$category_select .= "<option value=\"all\"".($pid == 'all' ? ' SELECTED' : '').">All Ads</option>\n";
				// send possible Parent records as options
				if (!Jaws_Error::IsError($parents)) {
					foreach($parents as $parent) {		            
						$category_select .= "<option value=\"".$parent['adparentid']."\"".($pid == $parent['adparentid'] ? ' SELECTED' : '').">".$parent['adparentcategory_name']."</option>\n";
					}
				}
				$category_select .= "</select>\n";
			}
			
			$brand_select = '';
			if ($account === false) {
				$brands = $model->GetBrands();
				$brand_select .= "<select name=\"searchbrand\" id=\"searchbrand\" size=\"1\" onChange=\"location.href = 'admin.php?gadget=Ads&action=A&id='+$('searchcategory').value+'&searchbrand='+this.value;\">\n";
				$brand_select .= "<option value=\"\"".(empty($searchbrand) ? ' SELECTED' : '').">All Brands</option>\n";
				// send possible Parent records as options
				if (!Jaws_Error::IsError($brands)) {
					foreach($brands as $brand) {		            
						$brand_select .= "<option value=\"".$brand['id']."\"".((int)$searchbrand == $brand['id'] ? ' SELECTED' : '').">".$brand['title']."</option>\n";
					}
				}
				$brand_select .= "</select>\n";
			}

			// Search submit
			$submit =& Piwi::CreateWidget('Button', 'search', _t('GLOBAL_SEARCH'), STOCK_SEARCH);
			$submit->SetSubmit();
			$search_form .= "<td width=\"0%\" valign=\"top\"><nobr>".$searchEntry->Get()."&nbsp;&nbsp;".$category_select."&nbsp;&nbsp;".$brand_select."&nbsp;&nbsp;".$submit->Get()."&nbsp;&nbsp;".$preview_link."</nobr></td>";

			$stpl->SetVariable('search_form', $search_form);
			*/
			
		} else {
			$this->AjaxMe('client_script.js');
			$tpl->SetVariable('menubar', '');
			$account_prefix = 'account_';
			$base_url = 'index.php';
		}
        
		$tpl->SetVariable('account', $account_prefix);
		$tpl->SetVariable('base_script', $base_url);
		$tpl->SetVariable('pid', $pid);

        $tpl->SetVariable('grid', $this->AdsDataGrid());

        $toolBar   =& Piwi::CreateWidget('HBox');

        $deleteAll =& Piwi::CreateWidget('Button', 'deleteAllAds',
                                         _t('GLOBAL_DELETE'),
                                         STOCK_DELETE);
        $deleteAll->AddEvent(ON_CLICK,
                             "javascript: massiveDelete('"._t('ADS_CONFIRM_MASIVE_DELETE_AD')."');");

        $toolBar->Add($deleteAll);

        $tpl->SetVariable('tools', $toolBar->Get());
                
        $tpl->SetVariable('entries', $this->AdsDatagrid());

		if ($account === false) {
	        $addPage =& Piwi::CreateWidget('Button', 'add_advertisement', _t('ADS_ADD_AD'), STOCK_ADD);
			$addPage->AddEvent(ON_CLICK, "javascript: window.location = '".$base_url."?gadget=Ads&amp;action=".$account_prefix."A_form&amp;linkid=".$pid."';");
	        $tpl->SetVariable('add_advertisement', $addPage->Get());
		} else {
			//$addPage->AddEvent(ON_CLICK, "javascript: window.open('".$base_url."?gadget=Ads&amp;action=".$account_prefix."form','','scrollbars=1,menubar=0,toolbar=0,location=0,status=1');");
	        $tpl->SetVariable('add_advertisement', '');
		}

        $tpl->ParseBlock('ads_admin');

        return $tpl->Get();
    }


    /**
     * We are on A_form page
     *
     * @access public
     * @return string
     */
    function A_form($account = false)
    {
		$GLOBALS['app']->Session->PopLastResponse();
		// check session
		if ($account === false) {
			$GLOBALS['app']->Session->CheckPermission('Ads', 'default');
		} else {
			$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
			if (!$GLOBALS['app']->Session->GetPermission('Ads', 'default')) {
				if (!$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Ads', 'OwnAds')) {
		            $GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
					$userHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
					return $userHTML->DefaultAction();
				}
			}
		}

		// document dependencies
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$model = $GLOBALS['app']->LoadGadget('Ads', 'Model');
		$request =& Jaws_Request::getInstance();
		$gather = array('action', 'id', 'linkid');
		$get = $request->get($gather, 'get');

		// initialize template
		$tpl = new Jaws_Template('gadgets/Ads/templates/');
        $tpl->Load('admin.html');
        $tpl->SetBlock('gadget_page');

		// account differences
		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=CustomPage&action=account_SetGBRoot');
		if ($account === false) {
			$this->AjaxMe('script.js');
			$tpl->SetVariable('menubar', $this->MenuBar($get['action']));
			$GLOBALS['app']->Layout->AddScriptLink('libraries/js/admin.js');			
			$submit_vars['ACTIONPREFIX'] = "";
			$submit_vars['CLOSE_BUTTON'] = "location.href='" . BASE_SCRIPT . "?gadget=Ads&amp;action=Admin';";
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
		$syntactsUrl = $GLOBALS['app']->getSyntactsAdminHTMLUrl('Ads/admin_Ads_A_form');
		$tpl->SetVariable('workarea-style', "style=\"margin-top: 30px;\" ");

		// syntacts page
		if ($syntactsUrl) {
			// snoopy
			include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
			$snoopy = new Snoopy('Ads');
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
					$pageInfo = $model->GetAd($get['id']);
					if (!Jaws_Error::IsError($pageInfo) && ($GLOBALS['app']->Session->GetPermission('Ads', 'ManageAds') || $pageInfo['ownerid'] == $OwnerID)) {
						$submit_vars['SUBMIT_BUTTON'] = "<input type='Submit' name='Submit' value='Update'>";
					} else {
						//$error = _t('ADS_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403'));												
						return new Jaws_Error(_t('ADS_ERROR_AD_NOT_FOUND'), _t('ADS_NAME'));
					}
				} else {
					$submit_vars['SUBMIT_BUTTON'] = "<input type='Submit' name='Submit' value='Add'>";
				}

				// send requesting URL to syntacts
				$stpl->SetVariable('HTTP_REFERER', $GLOBALS['app']->GetSiteURL());
				//$stpl->SetVariable('DPATH', JAWS_DPATH);
				$stpl->SetVariable('actionprefix', $submit_vars['ACTIONPREFIX']);
				$stpl->SetVariable('gadget', 'Ads');
				$stpl->SetVariable('CLOSE_BUTTON', $submit_vars['CLOSE_BUTTON']);
				$stpl->SetVariable('SUBMIT_BUTTON', $submit_vars['SUBMIT_BUTTON']);
				$stpl->SetVariable('controller', $base_url);
				
				// Get Help documentation
				$help_url = $GLOBALS['app']->getSyntactsAdminHTMLUrl("Ads/admin_Ads_A_form_help", 'txt');
				$snoopy = new Snoopy('Ads');
		
				if($snoopy->fetch($help_url)) {
					$helpContent = Jaws_Utils::split2D($snoopy->results);
				}
								
				// Hidden elements
				$ID = (isset($pageInfo['id'])) ? $pageInfo['id'] : '';
				$idHidden =& Piwi::CreateWidget('HiddenEntry', 'ID', $ID);
		        $form_content .= $idHidden->Get()."\n";

				$fuseaction = (isset($pageInfo['id'])) ? 'EditAds' : 'AddAds';
				$fuseactionHidden =& Piwi::CreateWidget('HiddenEntry', 'fuseaction', $fuseaction);
		        $form_content .= $fuseactionHidden->Get()."\n";

				$BrandID = (isset($pageInfo['brandid'])) ? $pageInfo['brandid'] : 0;
				$brandHidden =& Piwi::CreateWidget('HiddenEntry', 'BrandID', $BrandID);
		        $form_content .= $brandHidden->Get()."\n";

				$barcode_type = (isset($pageInfo['barcode_type'])) ? $pageInfo['barcode_type'] : '';
				$barcodeTypeHidden =& Piwi::CreateWidget('HiddenEntry', 'barcode_type', $barcode_type);
		        $form_content .= $barcodeTypeHidden->Get()."\n";
				
				$barcode_data = (isset($pageInfo['barcode_data'])) ? $pageInfo['barcode_data'] : '';
				$barcodeDataHidden =& Piwi::CreateWidget('HiddenEntry', 'barcode_data', $barcode_data);
		        $form_content .= $barcodeDataHidden->Get()."\n";
				
				if ($account === false) {
					// Active
					$helpString = '';
					foreach($helpContent as $help) {		            
						if ($help[0] == _t('ADS_STATUS')) {
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
					$active = (isset($pageInfo['active'])) ? $pageInfo['active'] : 'Y';
					$activeCombo =& Piwi::CreateWidget('Combo', 'Active');
					$activeCombo->AddOption(_t('ADS_PUBLISHED'), 'Y');
					$activeCombo->AddOption(_t('ADS_NOTPUBLISHED'), 'N');
					$activeCombo->SetDefault($active);
					$activeCombo->setTitle(_t('ADS_STATUS'));
					$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"Active\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$activeCombo->Get()."</td></tr>";
				} else {
					$activeHidden =& Piwi::CreateWidget('HiddenEntry', 'Active', 'N');
					$form_content .= $activeHidden->Get()."\n";
				}
					
				if ($account === false) {
					// Sitewide Fieldset
					include_once JAWS_PATH . 'include/Jaws/Widgets/FieldSet.php';
					$fieldset = new Jaws_Widgets_FieldSet(_t('ADS_SITEWIDE'));
					$helpString = '';
					foreach($helpContent as $help) {		            
						if ($help[0] == _t('ADS_SITEWIDE')) {
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
					$sitewide = (isset($pageInfo['sitewide'])) ? $pageInfo['sitewide'] : 'N';
					$sitewideCombo =& Piwi::CreateWidget('Combo', 'sitewide');
					$sitewideCombo->AddOption(_t('GLOBAL_YES'), 'Y');
					$sitewideCombo->AddOption(_t('GLOBAL_NO'), 'N');
					$sitewideCombo->SetDefault($sitewide);
					//$sitewideCombo->setTitle(_t('ADS_SITEWIDE'));
					$fieldset->Add($sitewideCombo);
					$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"sitewide\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$fieldset->Get()."</td></tr>";
				} else {
					$sitewideHidden =& Piwi::CreateWidget('HiddenEntry', 'sitewide', 'N');
					$form_content .= $sitewideHidden->Get()."\n";
				}

				// Category
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('ADS_CATEGORY')) {
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
				$LinkID = (isset($pageInfo['linkid'])) ? $pageInfo['linkid'] : (int)$get['linkid'];
				$categoryCombo =& Piwi::CreateWidget('Combo', 'LinkID');
				$categoryCombo->AddOption('Select Category...', 0);
				
				$parents = $model->GetAdParents();
				if (!Jaws_Error::IsError($parents)) {
					foreach ($parents as $parent) {
						$categoryCombo->AddOption($xss->parse(strip_tags($parent['adparentcategory_name'])), $parent['adparentid']);
					}
				} else {
					return new Jaws_Error(_t('ADS_ERROR_ADPARENTS_NOT_RETRIEVED'), _t('ADS_NAME'));
				}
				
				$categoryCombo->SetDefault($LinkID);
				
				$categoryCombo->setTitle(_t('ADS_CATEGORY'));
				$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"Category\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$categoryCombo->Get()."</td></tr>";

				// Type
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('ADS_TYPE')) {
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
				$type = (isset($pageInfo['type'])) ? $pageInfo['type'] : ($account === false ? '720' : '728');
				$typeButtons = '';
				if ($account === false) {
					$typeButtons .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">\n";
					$typeButtons .= "  <tr>\n";
					$typeButtons .= "    <td align=\"right\" style=\"padding: 5px;\"><input type=\"radio\" value=\"720\" name=\"type\"";
					if ($type == "720") {
						$typeButtons .= "checked";
					}
					$typeButtons .= "></td>\n";
					//$typeButtons .= "    <td style=\"padding: 5px;\"><img border=\"0\" src=\"" . $GLOBALS['app']->GetJawsURL() . "/gadgets/Ads/images/type_720.gif\" style=\"cursor: pointer; cursor: hand;\" onclick=\"setCheckedValue(document.forms['GLOBALform'].elements['type'], '720');\"></td>\n";
					$typeButtons .= "    <td style=\"padding: 5px;\"><img src=\"" . $GLOBALS['app']->GetJawsURL() . "/gadgets/Ads/images/type_720.gif\" width=\"240\" height=\"100\" border=\"0\" onclick=\"setCheckedValue(document.forms['GLOBALform'].elements['type'], '720');\"";
					$typeButtons .= " style=\"cursor: pointer; cursor: hand;\"";
					$typeButtons .= " title=\"header=[]"; 
					$typeButtons .= " body=[<img border='0' src='" . $GLOBALS['app']->GetJawsURL() . "/gadgets/Ads/images/type_720.gif'>";
					$typeButtons .= "] delay=[10] fade=[on] fadespeed=[.2]\"></td>";	
				}
				$typeButtons .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">\n";
				$typeButtons .= "  <tr>\n";
				$typeButtons .= "    <td align=\"right\" style=\"padding: 5px;\"><input type=\"radio\" value=\"728\" name=\"type\"";
				if ($type == "728") {
					$typeButtons .= "checked";
				}
				$typeButtons .= "></td>\n";
				//$typeButtons .= "    <td style=\"padding: 5px;\"><img border=\"0\" src=\"" . $GLOBALS['app']->GetJawsURL() . "/gadgets/Ads/images/type_728.gif\" style=\"cursor: pointer; cursor: hand;\" onclick=\"setCheckedValue(document.forms['GLOBALform'].elements['type'], '728');\"></td>\n";
				$typeButtons .= "   <td style=\"padding: 5px;\"><img src=\"" . $GLOBALS['app']->GetJawsURL() . "/gadgets/Ads/images/type_728.gif\" width=\"243\" height=\"30\" border=\"0\" onclick=\"setCheckedValue(document.forms['GLOBALform'].elements['type'], '728');\"";
				$typeButtons .= " style=\"cursor: pointer; cursor: hand;\"";
				$typeButtons .= " title=\"header=[]"; 
				$typeButtons .= " body=[<img border='0' src='" . $GLOBALS['app']->GetJawsURL() . "/gadgets/Ads/images/type_728.gif'>";
				$typeButtons .= "] delay=[10] fade=[on] fadespeed=[.2]\"></td>";	
				$typeButtons .= "  </tr><tr>\n";
				$typeButtons .= "    <td align=\"right\" style=\"padding: 5px;\"><input type=\"radio\" value=\"468\" name=\"type\"\n";
				if ($type == "468") {
					$typeButtons .= "checked";
				}
				$typeButtons .= "></td>\n";
				//$typeButtons .= "    <td style=\"padding: 5px;\"><img border=\"0\" src=\"" . $GLOBALS['app']->GetJawsURL() . "/gadgets/Ads/images/type_468.gif\" style=\"cursor: pointer; cursor: hand;\" onclick=\"setCheckedValue(document.forms['GLOBALform'].elements['type'], '468');\"></td>\n";
				$typeButtons .= "   <td style=\"padding: 5px;\"><img src=\"" . $GLOBALS['app']->GetJawsURL() . "/gadgets/Ads/images/type_468.gif\" width=\"156\" height=\"20\" border=\"0\" onclick=\"setCheckedValue(document.forms['GLOBALform'].elements['type'], '468');\"";
				$typeButtons .= " style=\"cursor: pointer; cursor: hand;\"";
				$typeButtons .= " title=\"header=[]"; 
				$typeButtons .= " body=[<img border='0' src='" . $GLOBALS['app']->GetJawsURL() . "/gadgets/Ads/images/type_468.gif'>";
				$typeButtons .= "] delay=[10] fade=[on] fadespeed=[.2]\"></td>";	
				$typeButtons .= "  </tr><tr>\n";
				$typeButtons .= "    <td align=\"right\" style=\"padding: 5px;\"><input type=\"radio\" value=\"225\" name=\"type\"\n";
				if ($type == "225") {
					$typeButtons .= "checked";
				}
				$typeButtons .= "></td>\n";
				//$typeButtons .= "    <td style=\"padding: 5px;\"><img border=\"0\" src=\"" . $GLOBALS['app']->GetJawsURL() . "/gadgets/Ads/images/type_125.gif\" style=\"cursor: pointer; cursor: hand;\" onclick=\"setCheckedValue(document.forms['GLOBALform'].elements['type'], '125');\"></td>\n";
				$typeButtons .= "   <td style=\"padding: 5px;\"><img src=\"" . $GLOBALS['app']->GetJawsURL() . "/gadgets/Ads/images/type_225.gif\" width=\"70\" height=\"64\" border=\"0\" onclick=\"setCheckedValue(document.forms['GLOBALform'].elements['type'], '225');\"";
				$typeButtons .= " style=\"cursor: pointer; cursor: hand;\"";
				$typeButtons .= " title=\"header=[]"; 
				$typeButtons .= " body=[<img border='0' src='" . $GLOBALS['app']->GetJawsURL() . "/gadgets/Ads/images/type_225.gif'>";
				$typeButtons .= "] delay=[10] fade=[on] fadespeed=[.2]\"></td>";	
				$typeButtons .= "  </tr><tr>\n";
				$typeButtons .= "    <td align=\"right\" style=\"padding: 5px;\"><input type=\"radio\" value=\"125\" name=\"type\"\n";
				if ($type == "125") {
					$typeButtons .= "checked";
				}
				$typeButtons .= "></td>\n";
				//$typeButtons .= "    <td style=\"padding: 5px;\"><img border=\"0\" src=\"" . $GLOBALS['app']->GetJawsURL() . "/gadgets/Ads/images/type_125.gif\" style=\"cursor: pointer; cursor: hand;\" onclick=\"setCheckedValue(document.forms['GLOBALform'].elements['type'], '125');\"></td>\n";
				$typeButtons .= "   <td style=\"padding: 5px;\"><img src=\"" . $GLOBALS['app']->GetJawsURL() . "/gadgets/Ads/images/type_125.gif\" width=\"42\" height=\"42\" border=\"0\" onclick=\"setCheckedValue(document.forms['GLOBALform'].elements['type'], '125');\"";
				$typeButtons .= " style=\"cursor: pointer; cursor: hand;\"";
				$typeButtons .= " title=\"header=[]"; 
				$typeButtons .= " body=[<img border='0' src='" . $GLOBALS['app']->GetJawsURL() . "/gadgets/Ads/images/type_125.gif'>";
				$typeButtons .= "] delay=[10] fade=[on] fadespeed=[.2]\"></td>";	
				$typeButtons .= "  </tr>\n";
				$typeButtons .= "</table>\n";
				$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"type\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$typeButtons."</td></tr>";
				
				// Image
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('ADS_ADIMAGE')) {
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
				$image_src = $GLOBALS['app']->getDataURL() . 'files'.$pageInfo['image'];
				$image_preview = '';
				if ($image != '' && file_exists($image_src)) { 
					$image_preview .= "<div id=\"image_preview\"><img border=\"0\" src=\"".$image_src."\" width=\"80\"".(strtolower(substr($image, -3)) == 'gif' || strtolower(substr($image, -3)) == 'png' || strtolower(substr($image, -3)) == 'bmp' ? ' height="80"' : '')." align=\"left\" style=\"padding: 5px;\"><p align=\"center\"><a href=\"javascript:void(0);\" onclick=\"document.getElementById('image').value = ''; document.getElementById('image_preview').style.display = 'none';\">Delete</a></p></div>";
				}
				$imageScript = "<script type=\"text/javascript\">Event.observe(window, \"load\",function(){addFileToPost('Ads', 'NULL', 'NULL', 'main_image', 'image', 1, 500, 34".($account === false ? ", '', false, '', 'jpg,jpeg,gif,png,bmp,tiff,tif,swf,htm,html" : '')."');});</script>";
				$imageHidden =& Piwi::CreateWidget('HiddenEntry', 'image', $image);
				//$imageButton = "&nbsp;&nbsp;&nbsp;&nbsp;<INPUT TYPE=\"button\" VALUE=\"Uploaded Files\" ONCLICK=\"openUploadWindow(null, null" . ($account === true ? ", 'account_'" : "") . ")\" STYLE=\"font-family: Arial; font-size: 10pt; font-weight: bold\">";
				$imageButton = '';
		        $form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"image\"><nobr>".$helpString."</nobr></label>".$image_preview."</td><td class=\"syntacts-form-row\"><div id=\"main_image\" style=\"float: left; width: 500px;\"></div>".$imageScript.$imageHidden->Get().$imageButton."</td></tr>";

				// Title
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('ADS_ADTITLE')) {
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
				$title = (isset($pageInfo['title'])) ? $pageInfo['title'] : '';
				$titleEntry =& Piwi::CreateWidget('Entry', 'title', $title);
				$titleEntry->SetTitle(_t('ADS_ADTITLE'));
				$titleEntry->SetStyle('direction: ltr; width: 300px;');
				$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"title\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$titleEntry->Get()."</td></tr>";

				// URL
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('ADS_URL')) {
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
				$url = (isset($pageInfo['url'])) ? $pageInfo['url'] : '';
				$urlEntry =& Piwi::CreateWidget('Entry', 'url', $url);
				$urlEntry->SetTitle(_t('ADS_URL'));
				$urlEntry->SetStyle('direction: ltr; width: 300px;');
				$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"url\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$urlEntry->Get()."</td></tr>";

				// Keyword
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('ADS_KEYWORD')) {
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
				$keyword = (isset($pageInfo['keyword'])) ? $pageInfo['keyword'] : '';
				$keywordEntry =& Piwi::CreateWidget('Entry', 'keyword', $keyword);
				$keywordEntry->SetTitle(_t('ADS_KEYWORD'));
				$keywordEntry->SetStyle('direction: ltr; width: 300px;');
				$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"keyword\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$keywordEntry->Get()."</td></tr>";

				// Description
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('ADS_DESCRIPTIONFIELD')) {
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
				$editor =& $GLOBALS['app']->LoadEditor('Ads', 'description', $content, false);
				$editor->TextArea->SetStyle('width: 100%;');
				//$editor->SetWidth('100%');
				$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"description\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$editor->Get()."</td></tr>";

				if ($error != '') {
					$stpl->SetVariable('content', $error);
				} else {
					$stpl->SetVariable('content', $form_content);
		        }
				$stpl->ParseBlock('form');
				$page = $stpl->Get();
			} else {
				$page = _t('ADS_ERROR_ASPPAGE_NOT_RETRIEVED', $snoopy->error)."\n";
			}
			
			$tpl->SetVariable('content', $page);
		} else {
			return new Jaws_Error(_t('ADS_ERROR_ASPPAGE_NOT_RETRIEVED'), _t('ADS_NAME'));
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
			$GLOBALS['app']->Session->CheckPermission('Ads', 'default');
		} else {
			$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
			if (!$GLOBALS['app']->Session->GetPermission('Ads', 'default')) {
				if (!$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Ads', 'OwnAds')) {
		            $GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
					$userHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
					return $userHTML->DefaultAction();
				}
			}
		}

		// document dependencies
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$request =& Jaws_Request::getInstance();
		$action = $request->get('action', 'get');
		$pid = $request->get('id', 'get');

		// initialize template
		$tpl = new Jaws_Template('gadgets/Ads/templates/');
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
        $tpl->SetVariable('actionsTitle', _t('ADS_ACTIONS'));
		
		// account differences
		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=CustomPage&action=account_SetGBRoot');
		if ($account === false) {
			$syntactsUrl = $GLOBALS['app']->getSyntactsAdminHTMLUrl();
			$this->AjaxMe('script.js');
			$tpl->SetVariable('menubar', $this->MenuBar($action));
			$submit_vars['ACTIONPREFIX'] = "";
			$submit_vars['CLOSE_BUTTON'] = "location.href='" . BASE_SCRIPT . "?gadget=Ads&amp;action=Admin';";
			$GLOBALS['app']->Layout->AddScriptLink('libraries/js/admin.js');
			$OwnerID = 0;
			$base_url = BASE_SCRIPT;
		} else {
			$this->AjaxMe('client_script.js');
			$syntactsUrl = $GLOBALS['app']->getSyntactsAdminHTMLUrl("Ads/admin_Ads_view");
			$tpl->SetVariable('menubar', '');
			$submit_vars['ACTIONPREFIX'] = "account_";
			$submit_vars['CLOSE_BUTTON'] = "if (window.opener && !window.opener.closed) {window.opener.location.reload();window.close();};";
			$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
			$base_url = 'index.php';
		}

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
			$snoopy = new Snoopy('Ads');
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
				$stpl->SetBlock('view');
			
				$galleryXHTML = '';
				$view_content = '';
			
				if (!is_null($pid)) {
					$model = $GLOBALS['app']->LoadGadget('Ads', 'Model');
					// send Page records
					$pageInfo = $model->GetAd($pid);
					
					if (!Jaws_Error::IsError($pageInfo) && ($GLOBALS['app']->Session->GetPermission('Ads', 'ManageAds') || $pageInfo['ownerid'] == $OwnerID)) {
						
						$galleryLayout = $GLOBALS['app']->LoadGadget('Ads', 'LayoutHTML');
						$galleryXHTML = $galleryLayout->Display($pid, false, null, true);
						$stpl->SetVariable('JAWS_AD', $galleryXHTML);
						$stpl->SetVariable('id', $pageInfo['id']);
						$stpl->SetVariable('image', $xss->filter($pageInfo['image']));
						$stpl->SetVariable('url', $xss->filter($pageInfo['url']));
						$stpl->SetVariable('title', $xss->filter($pageInfo['title']));
						$stpl->SetVariable('keyword', $xss->filter($pageInfo['keyword']));
						$stpl->SetVariable('sitewide', $pageInfo['sitewide']);
						$stpl->SetVariable('OwnerID', $pageInfo['ownerid']);
						$stpl->SetVariable('Active', $pageInfo['active']);
						$stpl->SetVariable('Created', $pageInfo['created']);
						$stpl->SetVariable('Updated', $pageInfo['updated']);
						
						// send requesting URL to syntacts
						$stpl->SetVariable('HTTP_REFERER', $GLOBALS['app']->GetSiteURL());
						//$stpl->SetVariable('DPATH', JAWS_DPATH);
						$stpl->SetVariable('actionprefix', $submit_vars['ACTIONPREFIX']);
						$stpl->SetVariable('CLOSE_BUTTON', $submit_vars['CLOSE_BUTTON']);
						$stpl->SetVariable('gadget', 'Ads');
						$stpl->SetVariable('controller', $base_url);
						
						// send embedding options
						$embed_options = "<a href=\"javascript:void(0);\" onclick=\"showEmbedWindow('".$GLOBALS['app']->GetSiteURL()."/".$base_url.".php?gadget=Ads&amp;action=".$submit_vars['ACTIONPREFIX']."ShowEmbedWindow&amp;linkid=".$pageInfo['id']."&amp;mode=full', 'Embed This Ad');\">This Ad</a>&nbsp;&nbsp;&nbsp;\n";
						if ($account === true) {
							$embed_options .= "<a href=\"javascript:void(0);\" onclick=\"showEmbedWindow('".$GLOBALS['app']->GetSiteURL()."/".$base_url."?gadget=Ads&amp;action=".$submit_vars['ACTIONPREFIX']."ShowEmbedWindow&amp;linkid=".$pageInfo['id']."&amp;uid=".$GLOBALS['app']->Session->GetAttribute('user_id')."&amp;mode=LeaderBoard', 'Embed My LeaderBoards');\">My LeaderBoards</a>&nbsp;&nbsp;&nbsp;\n";
							$embed_options .= "<a href=\"javascript:void(0);\" onclick=\"showEmbedWindow('".$GLOBALS['app']->GetSiteURL()."/".$base_url."?gadget=Ads&amp;action=".$submit_vars['ACTIONPREFIX']."ShowEmbedWindow&amp;linkid=".$pageInfo['id']."&amp;uid=".$GLOBALS['app']->Session->GetAttribute('user_id')."&amp;mode=Banner', 'Embed My Banners');\">My Banners</a>&nbsp;&nbsp;&nbsp;\n";
							$embed_options .= "<a href=\"javascript:void(0);\" onclick=\"showEmbedWindow('".$GLOBALS['app']->GetSiteURL()."/".$base_url."?gadget=Ads&amp;action=".$submit_vars['ACTIONPREFIX']."ShowEmbedWindow&amp;linkid=".$pageInfo['id']."&amp;uid=".$GLOBALS['app']->Session->GetAttribute('user_id')."&amp;mode=TwoButtons', 'Embed Two of My Buttons');\">Two Buttons</a>&nbsp;&nbsp;&nbsp;\n";
							$embed_options .= "<a href=\"javascript:void(0);\" onclick=\"showEmbedWindow('".$GLOBALS['app']->GetSiteURL()."/".$base_url."?gadget=Ads&amp;action=".$submit_vars['ACTIONPREFIX']."ShowEmbedWindow&amp;linkid=".$pageInfo['id']."&amp;uid=".$GLOBALS['app']->Session->GetAttribute('user_id')."&amp;mode=FourButtons', 'Embed Four of My Buttons');\">Four Buttons</a>&nbsp;&nbsp;&nbsp;\n";
						}
						$stpl->SetVariable('embed_options', $embed_options);
						
					} else {
						return new Jaws_Error(_t('ADS_ERROR_AD_NOT_FOUND'), _t('ADS_NAME'));
					}

					$stpl->ParseBlock('view');
					$page .= $stpl->Get();

					// syntacts page for form
					$syntactsUrl = $GLOBALS['app']->getSyntactsAdminHTMLUrl('Ads/admin_Ads_form');
					$submit_vars['CLOSE_BUTTON'] = "document.getElementById('form_content').style.display = 'none'; document.getElementById('view_content').style.display = '';";

					if ($syntactsUrl) {
						// snoopy
						$snoopy = new Snoopy('Ads');
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
							$stpl->SetBlock('form');
							
							$stpl->SetVariable('id', $pageInfo['id']);
							$stpl->SetVariable('image', $xss->filter($pageInfo['image']));
							$stpl->SetVariable('url', $xss->filter($pageInfo['url']));
							$stpl->SetVariable('title', $xss->filter($pageInfo['title']));
							$stpl->SetVariable('keyword', $xss->filter($pageInfo['keyword']));
							$stpl->SetVariable('sitewide', $pageInfo['sitewide']);
							$stpl->SetVariable('OwnerID', $pageInfo['ownerid']);
							$stpl->SetVariable('Active', $pageInfo['active']);
							$stpl->SetVariable('Created', $pageInfo['created']);
							$stpl->SetVariable('Updated', $pageInfo['updated']);
							$submit_vars['SUBMIT_BUTTON'] = "<input type='Submit' name='Submit' value='Update'>";

							// send requesting URL to syntacts
							$stpl->SetVariable('HTTP_REFERER', $GLOBALS['app']->GetSiteURL());
							//$stpl->SetVariable('DPATH', JAWS_DPATH);
							$stpl->SetVariable('actionprefix', $submit_vars['ACTIONPREFIX']);
							$stpl->SetVariable('gadget', 'Ads');
							$stpl->SetVariable('CLOSE_BUTTON', $submit_vars['CLOSE_BUTTON']);
							$stpl->SetVariable('SUBMIT_BUTTON', $submit_vars['SUBMIT_BUTTON']);
							$stpl->SetVariable('controller', $base_url);
							
							// Get Help documentation
							$help_url = $GLOBALS['app']->getSyntactsAdminHTMLUrl("Ads/admin_Ads_A_form_help", 'txt');
							$snoopy = new Snoopy('Ads');

							if($snoopy->fetch($help_url)) {
								$helpContent = Jaws_Utils::split2D($snoopy->results);
							}
							
							// Hidden elements
							$ID = (isset($pageInfo['id'])) ? $pageInfo['id'] : '';
							$idHidden =& Piwi::CreateWidget('HiddenEntry', 'ID', $ID);
							$form_content .= $idHidden->Get()."\n";

							$fuseaction = (isset($pageInfo['id'])) ? 'EditAds' : 'AddAds';
							$fuseactionHidden =& Piwi::CreateWidget('HiddenEntry', 'fuseaction', $fuseaction);
							$form_content .= $fuseactionHidden->Get()."\n";

							$BrandID = (isset($pageInfo['brandid'])) ? $pageInfo['brandid'] : 0;
							$brandHidden =& Piwi::CreateWidget('HiddenEntry', 'BrandID', $BrandID);
							$form_content .= $brandHidden->Get()."\n";

							$barcode_type = (isset($pageInfo['barcode_type'])) ? $pageInfo['barcode_type'] : '';
							$barcodeTypeHidden =& Piwi::CreateWidget('HiddenEntry', 'barcode_type', $barcode_type);
							$form_content .= $barcodeTypeHidden->Get()."\n";
							
							$barcode_data = (isset($pageInfo['barcode_data'])) ? $pageInfo['barcode_data'] : '';
							$barcodeDataHidden =& Piwi::CreateWidget('HiddenEntry', 'barcode_data', $barcode_data);
							$form_content .= $barcodeDataHidden->Get()."\n";
							
							if ($account === false) {
								// Active
								$helpString = '';
								foreach($helpContent as $help) {		            
									if ($help[0] == _t('ADS_STATUS')) {
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
								$active = (isset($pageInfo['active'])) ? $pageInfo['active'] : 'Y';
								$activeCombo =& Piwi::CreateWidget('Combo', 'Active');
								$activeCombo->AddOption(_t('ADS_PUBLISHED'), 'Y');
								$activeCombo->AddOption(_t('ADS_NOTPUBLISHED'), 'N');
								$activeCombo->SetDefault($active);
								$activeCombo->setTitle(_t('ADS_STATUS'));
								$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"Active\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$activeCombo->Get()."</td></tr>";
							} else {
								$activeHidden =& Piwi::CreateWidget('HiddenEntry', 'Active', 'N');
								$form_content .= $activeHidden->Get()."\n";
							}
								
							if ($account === false) {
								// Sitewide Fieldset
								include_once JAWS_PATH . 'include/Jaws/Widgets/FieldSet.php';
								$fieldset = new Jaws_Widgets_FieldSet(_t('ADS_SITEWIDE'));
								$helpString = '';
								foreach($helpContent as $help) {		            
									if ($help[0] == _t('ADS_SITEWIDE')) {
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
								$sitewide = (isset($pageInfo['sitewide'])) ? $pageInfo['sitewide'] : 'N';
								$sitewideCombo =& Piwi::CreateWidget('Combo', 'sitewide');
								$sitewideCombo->AddOption(_t('GLOBAL_YES'), 'Y');
								$sitewideCombo->AddOption(_t('GLOBAL_NO'), 'N');
								$sitewideCombo->SetDefault($sitewide);
								//$sitewideCombo->setTitle(_t('ADS_SITEWIDE'));
								$fieldset->Add($sitewideCombo);
								$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"sitewide\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$fieldset->Get()."</td></tr>";
							} else {
								$sitewideHidden =& Piwi::CreateWidget('HiddenEntry', 'sitewide', 'N');
								$form_content .= $sitewideHidden->Get()."\n";
							}

							// Category
							$helpString = '';
							foreach($helpContent as $help) {		            
								if ($help[0] == _t('ADS_CATEGORY')) {
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
							$LinkID = (isset($pageInfo['linkid'])) ? $pageInfo['linkid'] : (int)$get['linkid'];
							$categoryCombo =& Piwi::CreateWidget('Combo', 'LinkID');
							$categoryCombo->AddOption('Select Category...', 0);
							
							$parents = $model->GetAdParents();
							if (!Jaws_Error::IsError($parents)) {
								foreach ($parents as $parent) {
									$categoryCombo->AddOption($xss->parse(strip_tags($parent['adparentcategory_name'])), $parent['adparentid']);
								}
							} else {
								return new Jaws_Error(_t('ADS_ERROR_ADPARENTS_NOT_RETRIEVED'), _t('ADS_NAME'));
							}
							
							$categoryCombo->SetDefault($LinkID);
							
							$categoryCombo->setTitle(_t('ADS_CATEGORY'));
							$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"Category\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$categoryCombo->Get()."</td></tr>";

							// Type
							$helpString = '';
							foreach($helpContent as $help) {		            
								if ($help[0] == _t('ADS_TYPE')) {
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
							$type = (isset($pageInfo['type'])) ? $pageInfo['type'] : ($account === false ? '720' : '728');
							$typeButtons = '';
							if ($account === false) {
								$typeButtons .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">\n";
								$typeButtons .= "  <tr>\n";
								$typeButtons .= "    <td align=\"right\" style=\"padding: 5px;\"><input type=\"radio\" value=\"720\" name=\"type\"";
								if ($type == "720") {
									$typeButtons .= "checked";
								}
								$typeButtons .= "></td>\n";
								//$typeButtons .= "    <td style=\"padding: 5px;\"><img border=\"0\" src=\"" . $GLOBALS['app']->GetJawsURL() . "/gadgets/Ads/images/type_720.gif\" style=\"cursor: pointer; cursor: hand;\" onclick=\"setCheckedValue(document.forms['GLOBALform'].elements['type'], '720');\"></td>\n";
								$typeButtons .= "    <td style=\"padding: 5px;\"><img src=\"" . $GLOBALS['app']->GetJawsURL() . "/gadgets/Ads/images/type_720.gif\" width=\"240\" height=\"100\" border=\"0\" onclick=\"setCheckedValue(document.forms[1].elements['type'], '720');\"";
								$typeButtons .= " style=\"cursor: pointer; cursor: hand;\"";
								$typeButtons .= " title=\"header=[]"; 
								$typeButtons .= " body=[<img border='0' src='" . $GLOBALS['app']->GetJawsURL() . "/gadgets/Ads/images/type_720.gif'>";
								$typeButtons .= "] delay=[10] fade=[on] fadespeed=[.2]\"></td>";	
							}
							$typeButtons .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">\n";
							$typeButtons .= "  <tr>\n";
							$typeButtons .= "    <td align=\"right\" style=\"padding: 5px;\"><input type=\"radio\" value=\"728\" name=\"type\"";
							if ($type == "728") {
								$typeButtons .= "checked";
							}
							$typeButtons .= "></td>\n";
							//$typeButtons .= "    <td style=\"padding: 5px;\"><img border=\"0\" src=\"" . $GLOBALS['app']->GetJawsURL() . "/gadgets/Ads/images/type_728.gif\" style=\"cursor: pointer; cursor: hand;\" onclick=\"setCheckedValue(document.forms['GLOBALform'].elements['type'], '728');\"></td>\n";
							$typeButtons .= "   <td style=\"padding: 5px;\"><img src=\"" . $GLOBALS['app']->GetJawsURL() . "/gadgets/Ads/images/type_728.gif\" width=\"243\" height=\"30\" border=\"0\" onclick=\"setCheckedValue(document.forms[1].elements['type'], '728');\"";
							$typeButtons .= " style=\"cursor: pointer; cursor: hand;\"";
							$typeButtons .= " title=\"header=[]"; 
							$typeButtons .= " body=[<img border='0' src='" . $GLOBALS['app']->GetJawsURL() . "/gadgets/Ads/images/type_728.gif'>";
							$typeButtons .= "] delay=[10] fade=[on] fadespeed=[.2]\"></td>";	
							$typeButtons .= "  </tr><tr>\n";
							$typeButtons .= "    <td align=\"right\" style=\"padding: 5px;\"><input type=\"radio\" value=\"468\" name=\"type\"\n";
							if ($type == "468") {
								$typeButtons .= "checked";
							}
							$typeButtons .= "></td>\n";
							//$typeButtons .= "    <td style=\"padding: 5px;\"><img border=\"0\" src=\"" . $GLOBALS['app']->GetJawsURL() . "/gadgets/Ads/images/type_468.gif\" style=\"cursor: pointer; cursor: hand;\" onclick=\"setCheckedValue(document.forms['GLOBALform'].elements['type'], '468');\"></td>\n";
							$typeButtons .= "   <td style=\"padding: 5px;\"><img src=\"" . $GLOBALS['app']->GetJawsURL() . "/gadgets/Ads/images/type_468.gif\" width=\"156\" height=\"20\" border=\"0\" onclick=\"setCheckedValue(document.forms[1].elements['type'], '468');\"";
							$typeButtons .= " style=\"cursor: pointer; cursor: hand;\"";
							$typeButtons .= " title=\"header=[]"; 
							$typeButtons .= " body=[<img border='0' src='" . $GLOBALS['app']->GetJawsURL() . "/gadgets/Ads/images/type_468.gif'>";
							$typeButtons .= "] delay=[10] fade=[on] fadespeed=[.2]\"></td>";	
							$typeButtons .= "  </tr><tr>\n";
							$typeButtons .= "    <td align=\"right\" style=\"padding: 5px;\"><input type=\"radio\" value=\"225\" name=\"type\"\n";
							if ($type == "225") {
								$typeButtons .= "checked";
							}
							$typeButtons .= "></td>\n";
							//$typeButtons .= "    <td style=\"padding: 5px;\"><img border=\"0\" src=\"" . $GLOBALS['app']->GetJawsURL() . "/gadgets/Ads/images/type_125.gif\" style=\"cursor: pointer; cursor: hand;\" onclick=\"setCheckedValue(document.forms['GLOBALform'].elements['type'], '125');\"></td>\n";
							$typeButtons .= "   <td style=\"padding: 5px;\"><img src=\"" . $GLOBALS['app']->GetJawsURL() . "/gadgets/Ads/images/type_225.gif\" width=\"70\" height=\"64\" border=\"0\" onclick=\"setCheckedValue(document.forms['GLOBALform'].elements['type'], '225');\"";
							$typeButtons .= " style=\"cursor: pointer; cursor: hand;\"";
							$typeButtons .= " title=\"header=[]"; 
							$typeButtons .= " body=[<img border='0' src='" . $GLOBALS['app']->GetJawsURL() . "/gadgets/Ads/images/type_225.gif'>";
							$typeButtons .= "] delay=[10] fade=[on] fadespeed=[.2]\"></td>";	
							$typeButtons .= "  </tr><tr>\n";
							$typeButtons .= "    <td align=\"right\" style=\"padding: 5px;\"><input type=\"radio\" value=\"125\" name=\"type\"\n";
							if ($type == "125") {
								$typeButtons .= "checked";
							}
							$typeButtons .= "></td>\n";
							//$typeButtons .= "    <td style=\"padding: 5px;\"><img border=\"0\" src=\"" . $GLOBALS['app']->GetJawsURL() . "/gadgets/Ads/images/type_125.gif\" style=\"cursor: pointer; cursor: hand;\" onclick=\"setCheckedValue(document.forms['GLOBALform'].elements['type'], '125');\"></td>\n";
							$typeButtons .= "   <td style=\"padding: 5px;\"><img src=\"" . $GLOBALS['app']->GetJawsURL() . "/gadgets/Ads/images/type_125.gif\" width=\"42\" height=\"42\" border=\"0\" onclick=\"setCheckedValue(document.forms[1].elements['type'], '125');\"";
							$typeButtons .= " style=\"cursor: pointer; cursor: hand;\"";
							$typeButtons .= " title=\"header=[]"; 
							$typeButtons .= " body=[<img border='0' src='" . $GLOBALS['app']->GetJawsURL() . "/gadgets/Ads/images/type_125.gif'>";
							$typeButtons .= "] delay=[10] fade=[on] fadespeed=[.2]\"></td>";	
							$typeButtons .= "  </tr>\n";
							$typeButtons .= "</table>\n";
							$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"type\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$typeButtons."</td></tr>";
							
							// Image
							$helpString = '';
							foreach($helpContent as $help) {		            
								if ($help[0] == _t('ADS_ADIMAGE')) {
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
							$image_src = $GLOBALS['app']->getDataURL() . 'files'.$pageInfo['image'];
							$image_preview = '';
							if ($image != '' && file_exists($image_src)) { 
								$image_preview .= "<div id=\"image_preview\"><img border=\"0\" src=\"".$image_src."\" width=\"80\"".(strtolower(substr($image, -3)) == 'gif' || strtolower(substr($image, -3)) == 'png' || strtolower(substr($image, -3)) == 'bmp' ? ' height="80"' : '')." align=\"left\" style=\"padding: 5px;\"><p align=\"center\"><a href=\"javascript:void(0);\" onclick=\"document.getElementById('image').value = ''; document.getElementById('image_preview').style.display = 'none';\">Delete</a></p></div>";
							}
							$imageScript = "<script type=\"text/javascript\">Event.observe(window, \"load\",function(){addFileToPost('Ads', 'NULL', 'NULL', 'main_image', 'image', 1, 500, 34);});</script>";
							$imageHidden =& Piwi::CreateWidget('HiddenEntry', 'image', $image);
							$imageButton = "&nbsp;&nbsp;&nbsp;&nbsp;<INPUT TYPE=\"button\" VALUE=\"Uploaded Files\" ONCLICK=\"openUploadWindow(null, null" . ($account === true ? ", 'account_'" : "") . ")\" STYLE=\"font-family: Arial; font-size: 10pt; font-weight: bold\">";
							$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"image\"><nobr>".$helpString."</nobr></label>".$image_preview."</td><td class=\"syntacts-form-row\"><div id=\"main_image\" style=\"float: left; width: 500px;\"></div>".$imageScript.$imageHidden->Get().$imageButton."</td></tr>";

							// Title
							$helpString = '';
							foreach($helpContent as $help) {		            
								if ($help[0] == _t('ADS_ADTITLE')) {
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
							$title = (isset($pageInfo['title'])) ? $pageInfo['title'] : '';
							$titleEntry =& Piwi::CreateWidget('Entry', 'title', $title);
							$titleEntry->SetTitle(_t('ADS_ADTITLE'));
							$titleEntry->SetStyle('direction: ltr; width: 300px;');
							$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"title\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$titleEntry->Get()."</td></tr>";

							// URL
							$helpString = '';
							foreach($helpContent as $help) {		            
								if ($help[0] == _t('ADS_URL')) {
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
							$url = (isset($pageInfo['url'])) ? $pageInfo['url'] : '';
							$urlEntry =& Piwi::CreateWidget('Entry', 'url', $url);
							$urlEntry->SetTitle(_t('ADS_URL'));
							$urlEntry->SetStyle('direction: ltr; width: 300px;');
							$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"url\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$urlEntry->Get()."</td></tr>";

							// Keyword
							$helpString = '';
							foreach($helpContent as $help) {		            
								if ($help[0] == _t('ADS_KEYWORD')) {
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
							$keyword = (isset($pageInfo['keyword'])) ? $pageInfo['keyword'] : '';
							$keywordEntry =& Piwi::CreateWidget('Entry', 'keyword', $keyword);
							$keywordEntry->SetTitle(_t('ADS_KEYWORD'));
							$keywordEntry->SetStyle('direction: ltr; width: 300px;');
							$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"keyword\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$keywordEntry->Get()."</td></tr>";

							// Description
							$helpString = '';
							foreach($helpContent as $help) {		            
								if ($help[0] == _t('ADS_DESCRIPTIONFIELD')) {
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
							$editor =& $GLOBALS['app']->LoadEditor('Ads', 'description', $content, false);
							$editor->TextArea->SetStyle('width: 100%;');
							//$editor->SetWidth('100%');
							$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"description\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$editor->Get()."</td></tr>";

							$stpl->SetVariable('content', $form_content);
							$stpl->ParseBlock('form');
							$page .= $stpl->Get();
						} else {
							$page .= _t('ADS_ERROR_ASPPAGE_NOT_RETRIEVED', $snoopy->error)."\n";
						}
					}	
		
				} else {
					// Send us to the appropriate page
					require_once JAWS_PATH . 'include/Jaws/Header.php';
					if ($account == true) {
						Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'));
					} else {
						Jaws_Header::Location($base_url.'?gadget=Ads&action=Admin');
					}
				}
				
			} else {
				$page .= _t('ADS_ERROR_ASPPAGE_NOT_RETRIEVED', $snoopy->error)."\n";
			}
		}

		$tpl->SetVariable('content', $page);
        $tpl->ParseBlock('gadget_page');

        return $tpl->Get();

    }

    /**
     * Display the default administration page which currently lists all pages
     *
     * @access public
     * @return string
     */
    function SavedAds($account = false)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$GLOBALS['app']->Session->GetPermission('Ads', 'default')) {
			if (!$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Ads', 'OwnAd')) {
				//$GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
				return "Please log-in.";
			}
		}
        
		$tpl = new Jaws_Template('gadgets/Ads/templates/');
        $tpl->Load('admin.html');
        $tpl->SetBlock('savedads_admin');

		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=CustomPage&amp;action=account_SetGBRoot');
		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=Ads&amp;action=Ajax&amp;client=all&amp;stub=AdsAjax');
		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=Ads&amp;action=AjaxCommonFiles');
		$GLOBALS['app']->Layout->AddScriptLink('gadgets/Ads/resources/client_script.js');
		$tpl->SetVariable('menubar', '');
		$account_prefix = 'account_';
		$base_url = 'index.php';
        
		$tpl->SetVariable('account', $account_prefix);
		$tpl->SetVariable('base_script', $base_url);

        $tpl->SetVariable('grid', $this->DataGrid());

        $toolBar   =& Piwi::CreateWidget('HBox');

        $deleteAll =& Piwi::CreateWidget('Button', 'deleteAllSavedAds',
                                         _t('GLOBAL_DELETE'),
                                         STOCK_DELETE);
        $deleteAll->AddEvent(ON_CLICK,
                             "javascript: massiveDeleteSavedAds('"._t('ADS_CONFIRM_MASIVE_DELETE_AD')."');");

        $toolBar->Add($deleteAll);

        $tpl->SetVariable('tools', $toolBar->Get());
                
        $tpl->SetVariable('entries', $this->Datagrid());

        $tpl->ParseBlock('savedads_admin');

        return $tpl->Get();
    }

    /**
     * We are on the brands list page
     *
     * @access public
     * @return string
     */
    function B($account = false)
    {
		if ($account === false) {
			$GLOBALS['app']->Session->CheckPermission('Ads', 'default');
		} else {
			$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
			if (!$GLOBALS['app']->Session->GetPermission('Ads', 'default')) {
				if (!$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Ads', 'OwnAd')) {
		            $GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
					$userHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
					return $userHTML->DefaultAction();
				}
			}
		}

        $tpl = new Jaws_Template('gadgets/Ads/templates/');
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

        $tpl->SetVariable('grid', $this->BrandsDataGrid());

        $toolBar   =& Piwi::CreateWidget('HBox');

        $deleteAll =& Piwi::CreateWidget('Button', 'deleteBrands',
                                         _t('GLOBAL_DELETE'),
                                         STOCK_DELETE);
        $deleteAll->AddEvent(ON_CLICK,
                             "javascript: massiveDeleteBrands('"._t('ADS_CONFIRM_MASIVE_DELETE_BRANDS')."');");

        $toolBar->Add($deleteAll);

        $tpl->SetVariable('tools', $toolBar->Get());
                
        //Status filter
        $status = '';
        $statusCombo =& Piwi::CreateWidget('Combo', 'status');
        $statusCombo->setId('status');
        $statusCombo->AddOption('&nbsp;', '');
        $statusCombo->AddOption(_t('ADS_PUBLISHED'), 'Y');
        $statusCombo->AddOption(_t('ADS_DRAFT'), 'N');
        $statusCombo->SetDefault($status);
        $statusCombo->AddEvent(ON_CHANGE, 'javascript: searchBrand();');
        $tpl->SetVariable('status', _t('ADS_ACTIVE'));
        $tpl->SetVariable('status_field', $statusCombo->Get());

        // Free text search
        $searchButton =& Piwi::CreateWidget('Button', 'searchButton', _t('GLOBAL_SEARCH'), STOCK_SEARCH);
        $searchButton->AddEvent(ON_CLICK, 'javascript: searchBrand();');
        $tpl->SetVariable('search', $searchButton->Get());

        $search = '';
        $searchEntry =& Piwi::CreateWidget('Entry', 'search', $search);
        $searchEntry->SetStyle('zwidth: 100%;');
        $tpl->SetVariable('search_field', $searchEntry->Get());

        $tpl->SetVariable('entries', $this->BrandsDataGrid());

        $addPage =& Piwi::CreateWidget('Button', 'add_brands', _t('ADS_ADD_BRANDS'), STOCK_ADD);
        if ($account === false) {
			$addPage->AddEvent(ON_CLICK, "javascript: window.location = '".BASE_SCRIPT."?gadget=Ads&amp;action=B_form';");
        } else {
			$addPage->AddEvent(ON_CLICK, "javascript: window.location = '".$base_url."?gadget=Ads&amp;action=account_B_form';");
		}
		$tpl->SetVariable('add_brands', $addPage->Get());

        $tpl->ParseBlock('brands_admin');

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
			$GLOBALS['app']->Session->CheckPermission('Ads', 'default');
		} else {
			$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
			if (!$GLOBALS['app']->Session->GetPermission('Ads', 'default')) {
				if (!$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Ads', 'OwnAd')) {
		            $GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
					$userHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
					return $userHTML->DefaultAction();
				}
			}
		}

		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
				
		$tpl = new Jaws_Template('gadgets/Ads/templates/');
        $tpl->Load('admin.html');

        $tpl->SetBlock('gadget_page');

		$request =& Jaws_Request::getInstance();
		$action = $request->get('action', 'get');
		if ($account === false) {
			$this->AjaxMe('script.js');
			$syntactsUrl = $GLOBALS['app']->getSyntactsAdminUrl();
			$tpl->SetVariable('menubar', $this->MenuBar($action));
			$submit_vars['ACTIONPREFIX'] = "";
			$GLOBALS['app']->Layout->AddScriptLink('libraries/js/admin.js');
			$OwnerID = 0;			
			$base_url = BASE_SCRIPT;
		} else {
			$this->AjaxMe('client_script.js');
			$syntactsUrl = $GLOBALS['app']->getSyntactsAdminUrl("Ads/admin_Ads_B_form");
			$tpl->SetVariable('menubar', '');
			$submit_vars['ACTIONPREFIX'] = 'account_';
			$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
			$base_url = 'index.php';
		}

		$tpl->SetVariable('workarea-style', 'style="margin-top: 30px;" ');

		if ($syntactsUrl) {
			include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
			$snoopy = new Snoopy('Ads');
			$model = $GLOBALS['app']->LoadGadget('Ads', 'Model');
								
			$submit_url = $syntactsUrl;
			
			$id = $request->get('id', 'get');
			$linkid = $request->get('linkid', 'get');

			// send post records
			if (!is_null($id)) {
				$post = $model->GetBrand($id);
		        if (!Jaws_Error::IsError($post) && ($GLOBALS['app']->Session->GetPermission('Ads', 'ManageAds') || $post['ownerid'] == $OwnerID)) {
					$i = 0;
					$j = 0;
					$submit_vars['0:cols'] = 13;
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
					$page = _t('ADS_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403'))."\n";
				}
			}

			// send editor HTML to syntacts
			$description = (isset($post['description'])) ? $post['description'] : '';
			$editor =& $GLOBALS['app']->LoadEditor('Ads', 'description', $description, false);
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
			//$submit_vars['DPATH'] = JAWS_DPATH;
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
					$page = _t('ADS_ERROR_ASPPAGE_NOT_RETRIEVED', $snoopy->error)."\n";
				}
			}

			$tpl->SetVariable('content', $page);
		}
		
        $tpl->ParseBlock('gadget_page');

        return $tpl->Get();

    }

    /**
     * We are on the D_form_post page
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
     * ShowEmbedWindow
     *
     * @access public
     * @return string
     */
    function ShowEmbedWindow()
    {
		$user_admin = $GLOBALS['app']->LoadGadget('Users', 'AdminHTML');
		return $user_admin->ShowEmbedWindow('Ads', 'OwnAds');
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
			$GLOBALS['app']->Session->CheckPermission('Ads', 'default');
		} else {
			$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
			if (!$GLOBALS['app']->Session->GetPermission('Ads', 'default')) {
				if (!$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Ads', 'OwnAd')) {
		            $GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
					$userHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
					return $userHTML->DefaultAction();
				}
			}
		}
		$GLOBALS['app']->Registry->LoadFile('Ads');
		$GLOBALS['app']->Translate->LoadTranslation('Ads', JAWS_GADGET);
		require_once JAWS_PATH . 'include/Jaws/Template.php';
        $tpl = new Jaws_Template('gadgets/Ads/templates/');
        $tpl->Load('QuickAddForm.html');
        $tpl->SetBlock('form');

		$request =& Jaws_Request::getInstance();
		$method = $request->get('method', 'get');
		if (empty($method)) {
			$method = 'AddAd';
		}
		$form_content = '';
		switch($method) {
			case "AddAdParent": 
			case "UpdateAdParent": 
				$form_content = $this->form($account);
				break;
			case "AddGadget": 
			case "AddAd": 
			case "UpdateAd": 
				$form_content = $this->A_form($account);
				break;
			case "AddAdBrand":
			case "EditAdBrand":
				$form_content = $this->B_form($account);
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
        $tpl->SetVariable('stub', (JAWS_SCRIPT == 'admin' ? 'AdsAdminAjax' : 'AdsAjax'));
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
