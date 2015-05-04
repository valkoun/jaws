<?php
/**
 * Store - URL List gadget hook
 *
 * @category   GadgetHook
 * @package    Store
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class StoreURLListHook
{
    /**
     * Returns an array with all available items the Store gadget 
     * can use
     *
     * @access  public
     */
    function Hook()
    {
        $urls   = array();
        $urls[] = array('url'   => $GLOBALS['app']->Map->GetURLFor('Store', 'Index'),
                        'title' => _t('STORE_TITLE_PRODUCT_INDEX'));

        //Load model
        $model = $GLOBALS['app']->loadGadget('Store', 'Model');
        $pages = $model->GetProductParents();
        if (!Jaws_Error::IsError($pages)) {
            $max_size = 20;
            foreach($pages as $p) {
                if ($p['productparentactive'] == 'Y') {
                    if (isset($p['productparentfast_url'])) {
						$id = $p['productparentfast_url'];
					} else {
						$id = $p['productparentid'];
					}
                    $url   = $GLOBALS['app']->Map->GetURLFor('Store', 'Category', array('id' => $id));
                    $urls[] = array('url'   => $url,
                                    'title' => ($p['productparentcategory_name'])
					);
                }
            }
        }
        return $urls;
    }

	 /**
     * Returns the URL that is used to edit gadget's page 
     * @param   string  $action  Action
     * @param   int  $in  id of record we want to look for
     * @param   bool     $layout   is this a layout action?
     *
     * @access  public
     */
    function GetEditPage($action = null, $id = null, $layout = false)
    {
		if ((!is_null($action) && !empty($action))) {
			if ($layout === true) {
				switch($action) {
					case "Index": 
						return $GLOBALS['app']->GetSiteURL() .'/admin.php?gadget=Store&action=Admin';
					default:
						return $GLOBALS['app']->GetSiteURL() .'/admin.php?gadget=Store&action=A'.(!is_null($id) ? '&id='.$id : '');
				}
			} else {
				if ($action == 'Product') {
					return $GLOBALS['app']->GetSiteURL() .'/admin.php?gadget=Store&action=A_form'.(!is_null($id) ? '&id='.$id : '');
				} else {
					return $GLOBALS['app']->GetSiteURL() .'/admin.php?gadget=Store&action=A'.(!is_null($id) ? '&id='.$id : '');
				}
			}
		}
		return false;
	}

	 /**
     * Returns the URL that is used to add gadget's page 
     *
     * @access  public
     */
    function GetAddPage()
    {
		return $GLOBALS['app']->GetSiteURL() .'/admin.php?gadget=Store&action=form';
	}

	 /**
     * Returns the number of gadget's pages 
     * @param   int     $OwnerID   Owner ID
     *
     * @access  public
     */
    function GetPagesCount($OwnerID = 0)
    {
		$sql = "SELECT COUNT([productparentid]) FROM [[productparent]] WHERE [productparentownerid] = ".((int)$OwnerID)." AND [productparentactive] = 'Y'";
		$res = $GLOBALS['db']->queryOne($sql);
		return $res;
	}
	
	 /**
     * Returns the gadget's pages 
     * @param   string  $status  Status of page(s) we want to display ("Y" or "N")
     * @param   string  $search  Keyword (title/description) of pages we want to look for
     * @param   int     $limit   Data limit
     * @param   int     $OwnerID   Owner ID
     *
     * @access  public
     */
    function GetPages($status = '', $search = '', $limit = null, $OwnerID = 0)
    {
		$date = $GLOBALS['app']->loadDate();
		//$xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$ACL = $GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		
		$GLOBALS['app']->Registry->LoadFile('CustomPage');
		$GLOBALS['app']->Translate->LoadTranslation('CustomPage', JAWS_GADGET);
		
		$data    = array();
		
		$model = $GLOBALS['app']->LoadGadget('Store', 'AdminModel');
		if ($GLOBALS['app']->Session->GetPermission('Store', 'ManageProductParents')) {
			$pages = $model->SearchProductParents($status, $search, $limit, 0);
		} else {
			$pages = $model->SearchProductParents($status, $search, $limit, $GLOBALS['app']->Session->GetAttribute('user_id'));
		}

		if (Jaws_Error::IsError($pages)) {
			return new Jaws_Error($pages->GetMessage(), _t('STORE_NAME'));
		}

		if (BASE_SCRIPT != 'index.php') {
			$edit_url    = BASE_SCRIPT . '?gadget=Store&amp;action=A&amp;id=';
			$attribute_url    = BASE_SCRIPT . '?gadget=Store&amp;action=B';
			$sales_url    = BASE_SCRIPT . '?gadget=Store&amp;action=C';
		} else {
			$edit_url    = 'index.php?gadget=Store&amp;action=account_A&amp;id=';
			$attribute_url    = 'index.php?gadget=Store&amp;action=account_B';
			$sales_url    = 'index.php?gadget=Store&amp;action=account_C';
		}
		
		foreach ($pages as $page) {
			//if ($page['productparentparent'] == 0) {
				$pageData = array();
				if ($page['productparentactive'] == 'Y') {
					$pageData['active'] = _t('CUSTOMPAGE_PUBLISHED');
				} else {
					$pageData['active'] = _t('CUSTOMPAGE_DRAFT');
				}
				$pageData['title'] = ($page['productparentparent'] > 0 ? '&nbsp;&nbsp;-' : '').'<a href="'.$edit_url.$page['productparentid'].'">'.$page['productparentcategory_name'].'</a>';
				if (BASE_SCRIPT != 'index.php') {
					$pageData['furl']  = "<a href='javascript:void(0);' onclick='window.open(\"".$GLOBALS['app']->Map->GetURLFor('Store', 'Category', array('id' => $page['productparentfast_url']))."\");'>View This Category</a>";
				}
				$pageData['gadget']  = "Store";
				$pageData['date']  = $date->Format($page['productparentupdated']);
				$actions = '';
				if ($GLOBALS['app']->Session->GetPermission('Store', 'ManageProductParents')) {
					if (BASE_SCRIPT != 'index.php') {
						$link =& Piwi::CreateWidget('Link', _t('GLOBAL_EDIT'),
													$edit_url.$page['productparentid']/*,
													STOCK_BOOK*/);
					} else {
						$link =& Piwi::CreateWidget('Link', _t('GLOBAL_EDIT'),
													"javascript:window.open('".$edit_url.$page['productparentid']."');"/*,
													STOCK_BOOK*/);
					}
					$actions.= $link->Get().'&nbsp;';
				} else {
					if ($ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Store', 'OwnProduct')) {
						$link =& Piwi::CreateWidget('Link', _t('GLOBAL_EDIT'),
													"javascript:window.open('".$edit_url.$page['productparentid']."');"/*,
													STOCK_BOOK*/);
						$actions.= $link->Get().'&nbsp;';
					}
				}

				if ($GLOBALS['app']->Session->GetPermission('Store', 'ManageProductParents')) {
					$link =& Piwi::CreateWidget('Link', _t('GLOBAL_DELETE'),
												"javascript: if (confirm('"._t('GLOBAL_CONFIRM_DELETE', _t('CUSTOMPAGE_PAGE'))."')) ".
												"deleteProductParent('".$page['productparentid']."');"/*,
												"images/ICON_delete2.gif"*/);
					$actions.= $link->Get().'&nbsp;';
				} else {
					if ($ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Store', 'OwnProduct')) {
						$link =& Piwi::CreateWidget('Link', _t('GLOBAL_DELETE'),
													"javascript: if (confirm('"._t('GLOBAL_CONFIRM_DELETE', _t('CUSTOMPAGE_PAGE'))."')) ".
													"deleteProductParent('".$page['productparentid']."');"/*,
													"images/ICON_delete2.gif"*/);
						$actions.= $link->Get().'&nbsp;';
					}
				}
				$pageData['actions'] = $actions;
				$pageData['__KEY__'] = $page['productparentid'];
				$data[] = $pageData;
			//}
		}
		return $data;
	}
		
    /**
     * Returns an array with all Quick Add Forms of Gadget 
     * can use
     *
     * @access  public
     */
    function GetQuickAddForms($account = false)
    {
		$GLOBALS['app']->Registry->LoadFile('Store');
		$GLOBALS['app']->Translate->LoadTranslation('Store', JAWS_GADGET);
        $result   = array();
        if ($account === false) {
			$result[] = array('name'   => _t('STORE_QUICKADD_ADDPRODUCTPARENT'),
							'method' => 'AddProductParent');
        }
		$result[] = array('name'   => _t('STORE_QUICKADD_ADDPRODUCT'),
                        'method' => 'AddProduct');
		
        return $result;
    }

    /**
     * Returns an array with possible IDs of current request 
     *
     * @access  public
     */
    function GetAllFastURLsOfRequest($action, $id = null)
    {
        if (!is_null($id)) {
			$model = $GLOBALS['app']->loadGadget('Store', 'Model');
			switch($action) {
				case 'Category':
					$page = $model->GetProductParent($id);
					if (!Jaws_Error::IsError($page) && isset($page['productparentid']) && !empty($page['productparentid'])) {
						return array(0 => $page['productparentid'], 1 => $page['productparentfast_url']);
					}
					break;
				case 'Product':
					$page = $model->GetProduct($id);
					if (!Jaws_Error::IsError($page) && isset($page['id']) && !empty($page['id'])) {
						return array(0 => $page['id'], 1 => $page['fast_url']);
					}
					break;
			}
		}
		return false;
    }
}
