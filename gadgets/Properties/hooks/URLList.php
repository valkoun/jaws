<?php
/**
 * Properties - URL List gadget hook
 *
 * @category   GadgetHook
 * @package    Properties
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class PropertiesURLListHook
{
    /**
     * Returns an array with all available items the Properties gadget 
     * can use
     *
     * @access  public
     */
    function Hook()
    {
        $urls   = array();
        $urls[] = array('url'   => $GLOBALS['app']->Map->GetURLFor('Properties', 'Index'),
                        'title' => _t('PROPERTIES_TITLE_PROPERTY_INDEX'));

        //Load model
        $model = $GLOBALS['app']->loadGadget('Properties', 'Model');
        $pages = $model->GetPropertyParents();
        if (!Jaws_Error::IsError($pages)) {
            $max_size = 20;
            foreach($pages as $p) {
                if ($p['propertyparentactive'] == 'Y') {
                    if (isset($p['propertyparentfast_url'])) {
						$id = $p['propertyparentfast_url'];
					} else {
						$id = $p['propertyparentid'];
					}
                    $url   = $GLOBALS['app']->Map->GetURLFor('Properties', 'Category', array('id' => $id));
                    $urls[] = array('url'   => $url,
                                    'title' => ($p['propertyparentcategory_name'])
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
						return $GLOBALS['app']->GetSiteURL() .'/admin.php?gadget=Properties&action=Admin';
					default:
						return $GLOBALS['app']->GetSiteURL() .'/admin.php?gadget=Properties&action=A'.(!is_null($id) ? '&id='.$id : '');
				}
			} else {
				if ($action == 'Property') {
					return $GLOBALS['app']->GetSiteURL() .'/admin.php?gadget=Properties&action=A_form'.(!is_null($id) ? '&id='.$id : '');
				} else {
					return $GLOBALS['app']->GetSiteURL() .'/admin.php?gadget=Properties&action=A'.(!is_null($id) ? '&id='.$id : '');
				}
			}
		}
		return false;
	}

	 /**
     * Returns the URL that is used to add gadget's page 
     * @param   string  $status  Status of page(s) we want to display ("Y" or "N")
     * @param   string  $search  Keyword (title/description) of pages we want to look for
     * @param   int     $limit   Data limit
     * @param   int     $OwnerID   Owner ID
     *
     * @access  public
     */
    function GetAddPage()
    {
		return $GLOBALS['app']->GetSiteURL() .'/admin.php?gadget=Properties&action=form';
	}

	 /**
     * Returns the number of gadget's pages 
     * @param   int     $OwnerID   Owner ID
     *
     * @access  public
     */
    function GetPagesCount($OwnerID = 0)
    {
		$sql = "SELECT COUNT([propertyparentid]) FROM [[propertyparent]] WHERE [propertyparentownerid] = ".$OwnerID." AND [propertyparentactive] = 'Y'";
		$res = $GLOBALS['db']->queryOne($sql);
		return $res;
	}
	
	 /**
     * Returns the gadget's pages 
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
		
		$model = $GLOBALS['app']->LoadGadget('Properties', 'AdminModel');
		if ($GLOBALS['app']->Session->GetPermission('Properties', 'ManagePropertyParents')) {
			$pages = $model->SearchPropertyParents($status, $search, $limit, 0);
		} else {
			$pages = $model->SearchPropertyParents($status, $search, $limit, $GLOBALS['app']->Session->GetAttribute('user_id'));
		}
		
		if (Jaws_Error::IsError($pages)) {
			return new Jaws_Error($pages->GetMessage(), _t('PROPERTIES_NAME'));
		}

		$edit_url    = BASE_SCRIPT . '?gadget=Properties&amp;action=A&amp;id=';
		$amenity_url    = BASE_SCRIPT . '?gadget=Properties&amp;action=B';
		$reservation_url    = BASE_SCRIPT . '?gadget=Properties&amp;action=C';

		foreach ($pages as $page) {
			$pageData = array();
			if ($page['propertyparentactive'] == 'Y') {
				$pageData['active'] = _t('CUSTOMPAGE_PUBLISHED');
			} else {
				$pageData['active'] = _t('CUSTOMPAGE_DRAFT');
			}
			$pageData['title'] = ($page['propertyparentparent'] > 0 ? '&nbsp;&nbsp;-' : '').'<a href="'.$edit_url.$page['propertyparentid'].'">'.(strlen($page['propertyparentcategory_name']) > 30 ? substr($page['propertyparentcategory_name'], 0, 30).'...' : $page['propertyparentcategory_name']).'</a>';
			$pageData['furl']  = "<a href='javascript:void(0);' onclick='window.open(\"".$GLOBALS['app']->Map->GetURLFor('Properties', 'Category', array('id' => $page['propertyparentfast_url']))."\");'>View This Category</a>";
			
			$pageData['gadget']  = "Properties";
			$pageData['date']  = $date->Format($page['propertyparentupdated']);
			$actions = '';
			if ($GLOBALS['app']->Session->GetPermission('Properties', 'ManagePropertyParents')) {
				if (BASE_SCRIPT != 'index.php') {
					$link =& Piwi::CreateWidget('Link', _t('GLOBAL_EDIT'),
												$edit_url.$page['propertyparentid']/*,
									STOCK_EDIT*/);
				} else {
					$link =& Piwi::CreateWidget('Link', _t('GLOBAL_EDIT'),
												"javascript:window.open('".$edit_url.$page['propertyparentid']."');"/*,
									STOCK_EDIT*/);
				}
				$actions.= $link->Get().'&nbsp;';
			} else {
				if ($ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Properties', 'OwnProperty')) {
					$link =& Piwi::CreateWidget('Link', _t('GLOBAL_EDIT'),
												"javascript:window.open('".$edit_url.$page['propertyparentid']."');"/*,
									STOCK_EDIT*/);
					$actions.= $link->Get().'&nbsp;';
				}
			}

			if ($GLOBALS['app']->Session->GetPermission('Properties', 'ManagePropertyParents')) {
				$link =& Piwi::CreateWidget('Link', _t('GLOBAL_DELETE'),
											"javascript: if (confirm('"._t('GLOBAL_CONFIRM_DELETE', _t('CUSTOMPAGE_PAGE'))."')) ".
											"deletePropertyParent('".$page['propertyparentid']."');"/*,
										"images/ICON_delete2.gif"*/);
				$actions.= $link->Get().'&nbsp;';
			} else {
				if ($ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Properties', 'OwnProperty')) {
					$link =& Piwi::CreateWidget('Link', _t('GLOBAL_DELETE'),
												"javascript: if (confirm('"._t('GLOBAL_CONFIRM_DELETE', _t('CUSTOMPAGE_PAGE'))."')) ".
												"deletePropertyParent('".$page['propertyparentid']."');"/*,
										"images/ICON_delete2.gif"*/);
					$actions.= $link->Get().'&nbsp;';
				}
			}
			$pageData['actions'] = $actions;
			$pageData['__KEY__'] = $page['propertyparentid'];
			$data[] = $pageData;
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
		$GLOBALS['app']->Registry->LoadFile('Properties');
		$GLOBALS['app']->Translate->LoadTranslation('Properties', JAWS_GADGET);
        $result   = array();
        if ($account === false) {
			$result[] = array('name'   => _t('PROPERTIES_QUICKADD_ADDPROPERTYPARENT'),
							'method' => 'AddPropertyParent');
        }
		$result[] = array('name'   => _t('PROPERTIES_QUICKADD_ADDPROPERTY'),
                        'method' => 'AddProperty');
		

        return $result;
    }
}
