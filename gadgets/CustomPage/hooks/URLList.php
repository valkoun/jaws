<?php
/**
 * CustomPage - URL List gadget hook
 *
 * @category   GadgetHook
 * @package    CustomPage
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class CustomPageURLListHook extends Jaws_Model
{
    /**
     * Returns an array with all available items the Menu gadget 
     * can use
     *
     * @access  public
     */
    function Hook()
    {
        $urls   = array();
        $urls[] = array('url'   => $GLOBALS['app']->Map->GetURLFor('CustomPage', 'Index'),
                        'title' => _t('CUSTOMPAGE_TITLE_PAGE_INDEX'));

        //Load model
        $model = $GLOBALS['app']->loadGadget('CustomPage', 'Model');
        $pages = $model->GetPages(null, 'sm_description', 'ASC', false, 0);
        if (!Jaws_Error::IsError($pages)) {
            $max_size = 20;
            foreach($pages as $p) {
                if ($p['active'] == 'Y') {
                    if (isset($p['fast_url'])) {
						$id = $p['fast_url'];
					} else {
						$id = $p['id'];
					}
					$url   = $GLOBALS['app']->Map->GetURLFor('CustomPage', 'Page', array('id' => $id));
                    $urls[] = array('url'   => $url,
                                    'title' => ($GLOBALS['app']->UTF8->strlen($p['fast_url']) > $max_size ?
                                                $GLOBALS['app']->UTF8->substr($p['fast_url'], 0, $max_size).'...' :
                                                $p['fast_url'])
					);
                }
            }
        }
		
        return $urls;
    }
	
	 /**
     * Returns the URL that is used to edit gadget's page 
     *
     * @access  public
     */
    function GetEditPage($action = null, $id = null, $layout = false)
    {
		if (!is_null($action) && !empty($action)) {
			if ($layout === true) {
				switch ($action) {
					case "Page": 
						//$HTML = $GLOBALS['app']->loadGadget('CustomPage', 'HTML');
						//return $GLOBALS['app']->GetSiteURL() .'/index.php?gadget=CustomPage&action=Page&id='.$id.'&edit=true';
						return $GLOBALS['app']->GetSiteURL() .'/admin.php?gadget=CustomPage&action=view&id='.$id;
						break;
					default:
						//$HTML = $GLOBALS['app']->loadGadget('CustomPage', 'HTML');
						//return $GLOBALS['app']->GetSiteURL() .'/index.php?gadget=CustomPage&action=Page&id='.$id.'&edit=true';
						return $GLOBALS['app']->GetSiteURL() .'/admin.php?gadget=CustomPage&action=Admin';
						break;
				}
			} else {
				switch ($action) {
					case "Page": 
						//$HTML = $GLOBALS['app']->loadGadget('CustomPage', 'HTML');
						//return $GLOBALS['app']->GetSiteURL() .'/index.php?gadget=CustomPage&action=Page&id='.$id.'&edit=true';
						return $GLOBALS['app']->GetSiteURL() .'/admin.php?gadget=CustomPage&action=view&id='.$id;
						break;
					default:
						//$HTML = $GLOBALS['app']->loadGadget('CustomPage', 'HTML');
						//return $GLOBALS['app']->GetSiteURL() .'/index.php?gadget=CustomPage&action=Page&id='.$id.'&edit=true';
						return $GLOBALS['app']->GetSiteURL() .'/admin.php?gadget=CustomPage&action=Admin';
						break;
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
		return $GLOBALS['app']->GetSiteURL() .'/admin.php?gadget=CustomPage&action=form';
	}
	
    /**
     * Returns an array with all Quick Add Forms of Gadget 
     * can use
     *
     * @access  public
     */
    function GetQuickAddForms()
    {
		$GLOBALS['app']->Registry->LoadFile('CustomPage');
		$GLOBALS['app']->Translate->LoadTranslation('CustomPage', JAWS_GADGET);
        $result   = array();
        $result[] = array('name'   => _t('CUSTOMPAGE_QUICKADD_ADDPAGE'),
                        'method' => 'AddPage');
        /*
		$result[] = array('name'   => _t('CUSTOMPAGE_QUICKADD_ADDPOST'),
                        'method' => 'AddPost');
		*/
        return $result;
    }
	
    /**
     * Returns an array with a page of current URL 
     *
     * @access  public
     */
    function CurrentURLHasPage($gadget, $action = null, $id = null)
    {
		$model = $GLOBALS['app']->loadGadget('CustomPage', 'Model');
		if (!is_null($action)) {
			switch($gadget) {
				case "Users":
					if ($action == 'GroupPage') {
						$request =& Jaws_Request::getInstance();
						if (JAWS_SCRIPT == 'index') {
							if (is_null($id) || empty($id)) {
								$id = 'Main';
							}	
							$group = $request->get('group', 'get');
							if (empty($group)) {
								$group = $request->get('group', 'post');
							}
						} else {
							$group = $id;
							$id = $request->get('id', 'get');
							if (empty($id)) {
								$id = $request->get('id', 'post');
							}
						}
						$page = $model->GetPage($id, 'Users', 'GroupPage', $group);
						if (!Jaws_Error::IsError($page) && isset($page['id']) && !empty($page['id'])) {
							$page['permalink'] = $GLOBALS['app']->Map->GetURLFor($gadget, $action, array('group' => $page['linkid'], 'id' => $page['id']));
							return $page;
						}
					}					
					break;
				default:
					$get_gadget = ($gadget == 'CustomPage' ? null : $gadget);
					$get_action = ($action == 'view' || $action == 'Page' ? null : $action);
					$page = $model->GetPage($id, $get_gadget, $get_action);
					if (!Jaws_Error::IsError($page) && isset($page['id']) && !empty($page['id'])) {
						$page['permalink'] = $GLOBALS['app']->Map->GetURLFor($gadget, $action, array('id' => $page['fast_url']));
						return $page;
					}
					break;
			}
		}
		return false;
    }
	
    /**
     * Returns an array with possible IDs of current request 
     *
     * @access  public
     */
    function GetAllFastURLsOfRequest($action, $id = null)
    {
        if (!is_null($id)) {
			$model = $GLOBALS['app']->loadGadget('CustomPage', 'Model');
			$page = $model->GetPage($id);
			if (!Jaws_Error::IsError($page) && isset($page['id']) && !empty($page['id'])) {
				return array(0 => $page['id'], 1 => $page['fast_url']);
			}
		}
		return false;
    }
	
}
