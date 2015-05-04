<?php
/**
 * Forms - URL List gadget hook
 *
 * @category   GadgetHook
 * @package    Forms
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class FormsURLListHook
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
        $urls[] = array('url'   => $GLOBALS['app']->Map->GetURLFor('Forms', 'Index'),
                        'title' => _t('FORMS_TITLE_PAGE_INDEX'));

        //Load model
        $model = $GLOBALS['app']->loadGadget('Forms', 'Model');
        $pages = $model->GetForms();
        if (!Jaws_Error::IsError($pages)) {
            $max_size = 20;
            foreach($pages as $p) {
                if ($p['active'] == 'Y') {
                    $url   = $GLOBALS['app']->Map->GetURLFor('Forms', 'Form', array('id' => $p['fast_url']));
                    $urls[] = array('url'   => $url,
                                    'title' => ($p['title'])
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
			return $GLOBALS['app']->GetSiteURL() .'/admin.php?gadget=Forms&action=form'.(!is_null($id) ? '&id='.$id : '');
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
		return $GLOBALS['app']->GetSiteURL() .'/admin.php?gadget=Forms&action=form';
	}
	
	 /**
     * Returns the number of gadget's pages 
     * @param   int     $OwnerID   Owner ID
     *
     * @access  public
     */
    function GetPagesCount($OwnerID = 0)
    {
		$sql = "SELECT COUNT([id]) FROM [[forms]] WHERE [ownerid] = ".$OwnerID." AND [active] = 'Y'";
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
		
		$model = $GLOBALS['app']->LoadGadget('Forms', 'AdminModel');
		if ($GLOBALS['app']->Session->GetPermission('Forms', 'ManageForms')) {
			$pages = $model->SearchForms($status, $search, $limit);
		} else {
			$pages = $model->SearchForms($status, $search, $limit, $GLOBALS['app']->Session->GetAttribute('user_id'));
		}

		if (Jaws_Error::IsError($pages)) {
			return new Jaws_Error($pages->GetMessage(), _t('FORMS_NAME'));
		}

		$edit_url    = BASE_SCRIPT . '?gadget=Forms&amp;action=view&amp;id=';

		foreach ($pages as $page) {
			$pageData = array();
			if ($page['active'] == 'Y') {
				$pageData['active'] = _t('CUSTOMPAGE_PUBLISHED');
			} else {
				$pageData['active'] = _t('CUSTOMPAGE_DRAFT');
			}
			$pageData['title'] = '<a href="'.$edit_url.$page['id'].'">'.(strlen($page['title']) > 30 ? substr($page['title'], 0, 30).'...' : $page['title']).'</a>';
			$preview_url = $GLOBALS['app']->Map->GetURLFor('Forms', 'Form', array('id' => $page['fast_url']));
			$pageData['furl']  = '<a href="'.$preview_url.'">View This Form</a>';

			
			$pageData['gadget']  = "Forms";
			$pageData['date']  = $date->Format($page['updated']);
			$actions = '';
			if ($GLOBALS['app']->Session->GetPermission('Forms', 'ManageForms')) {
				$link =& Piwi::CreateWidget('Link', _t('GLOBAL_EDIT'),
											$edit_url.$page['id']/*,
										STOCK_EDIT*/);
				$actions.= $link->Get().'&nbsp;';
			}

			if ($GLOBALS['app']->Session->GetPermission('Forms', 'ManageForms')) {
				$link =& Piwi::CreateWidget('Link', _t('GLOBAL_DELETE'),
											"javascript: if (confirm('"._t('GLOBAL_CONFIRM_DELETE', _t('CUSTOMPAGE_PAGE'))."')) ".
											"deleteForm('".$page['id']."');"/*,
											"images/ICON_delete2.gif"*/);
				$actions.= $link->Get().'&nbsp;';
			}
			$pageData['actions'] = $actions;
			$pageData['__KEY__'] = $page['id'];
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
    function GetQuickAddForms()
    {
		$GLOBALS['app']->Registry->LoadFile('Forms');
		$GLOBALS['app']->Translate->LoadTranslation('Forms', JAWS_GADGET);
        $result   = array();
        $result[] = array('name'   => _t('FORMS_QUICKADD_ADDFORM'),
                        'method' => 'AddForm');
        /*
		$result[] = array('name'   => _t('FORMS_QUICKADD_ADDPOST'),
                        'method' => 'AddPost');
		*/

        return $result;
    }
}
