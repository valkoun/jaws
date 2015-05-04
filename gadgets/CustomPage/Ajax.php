<?php
/**
 * CustomPage AJAX API
 *
 * @category   Ajax
 * @package    CustomPage
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class CustomPageAjax extends Jaws_Ajax
{
    // {{{ Constructor PHP4
    /**
     * PHP 4 Constructor
     *
     * @access  public
     */
    function CustomPageAjax(&$model)
    {
        $gadget =& $model;
    }

    // }}}
    // {{{ Function DeletePage
    /**
     * Deletes a page and all translated of it.
     *
     * @access  public
     * @param   int     $id  Page ID
     * @return  array   Response (notice or error)
     */
    function DeletePage($id)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('CustomPage', 'ManagePages') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'CustomPage', 'OwnPage')) {
			$this->CheckSession('CustomPage', 'ManagePages');
		}
        $gadget = $GLOBALS['app']->LoadGadget('CustomPage', 'AdminModel');
        $gadget->DeletePage($id);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

     /**
     * Deletes a post
     *
     * @access  public
     * @param   int     $pid  Post ID
     * @return  array   Response (notice or error)
     */
    function DeletePost($pid)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('CustomPage', 'ManagePages') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'CustomPage', 'OwnPage')) {
			$this->CheckSession('CustomPage', 'ManagePages');
		}
        $gadget = $GLOBALS['app']->LoadGadget('CustomPage', 'AdminModel');
        $gadget->DeletePost($pid);
        return $GLOBALS['app']->Session->PopLastResponse();
    }


     /**
     * Hides Rss Item
     *
     * @access  public
     * @param   int  $pid  page ID
     * @param   string  $title  title of Rss item
     * @param   string  $published  date of Rss item
     * @param   string  $url  url of Rss item
     * @return  array   Response (notice or error)
     */
    function HideRss($pid, $title, $published, $url)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('CustomPage', 'ManagePages') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'CustomPage', 'OwnPage')) {
			$this->CheckSession('CustomPage', 'ManagePages');
		}
        $gadget = $GLOBALS['app']->LoadGadget('CustomPage', 'AdminModel');
        $gadget->HideRss($pid, $title, $published, $url);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

     /**
     * Shows Rss Item
     *
     * @param   int  $pid  page ID
     * @param   string  $title  title of Rss item
     * @param   string  $published  date of Rss item
     * @param   string  $url  url of Rss item
     * @access  public
     * @return  array   Response (notice or error)
     */
    function ShowRss($pid, $title, $published, $url)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('CustomPage', 'ManagePages') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'CustomPage', 'OwnPage')) {
			$this->CheckSession('CustomPage', 'ManagePages');
		}
        $gadget = $GLOBALS['app']->LoadGadget('CustomPage', 'AdminModel');
        $gadget->ShowRss($pid, $title, $published, $url);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Executes a massive-delete of pages
     *
     * @access  public
     * @param   array   $pages  Array with the ids of pages
     * @return  array   Response (notice or error)
     */
    function MassiveDelete($pages)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('CustomPage', 'ManagePages') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'CustomPage', 'OwnPage')) {
			$this->CheckSession('CustomPage', 'ManagePages');
		}
        $gadget = $GLOBALS['app']->LoadGadget('CustomPage', 'AdminModel');
        $gadget->MassiveDelete($pages);
        return $GLOBALS['app']->Session->PopLastResponse();
    }
    
    /**
     * Get total pages of a search
     *
     * @access  public
     * @param   string  $status  Status of page(s) we want to display
     * @param   string  $search  Keyword (title/description) of pages we want to look for
     * @return  int     Total of posts
     */
    function SizeOfSearch($status, $search)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('CustomPage', 'ManagePages') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'CustomPage', 'OwnPage')) {
			$this->CheckSession('CustomPage', 'ManagePages');
		}
        $gadget = $GLOBALS['app']->LoadGadget('CustomPage', 'AdminModel');
        $pages = $gadget->SearchPages($status, $search, null, $GLOBALS['app']->Session->GetAttribute('user_id'));
        return count($pages);
    }

    /**
     * Returns an array with all the pages
     *
     * @access  public
     * @param   string  $status  Status of page(s) we want to display
     * @param   string  $search  Keyword (title/description) of pages we want to look for
     * @param   int     $limit   Data limit
     * @return  array   Pages data
     */
    function SearchPages($status, $search, $limit)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('CustomPage', 'ManagePages') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'CustomPage', 'OwnPage')) {
			$this->CheckSession('CustomPage', 'ManagePages');
		}
        $gadget = $GLOBALS['app']->LoadGadget('CustomPage', 'AdminHTML');
        if (!is_numeric($limit)) {
            $limit = 0;
        }
		return $gadget->GetPages($status, $search, $limit, $GLOBALS['app']->Session->GetAttribute('user_id'));
    }
    
    /**
     * This function will perform an autodraft of the content and set
     * it's value to not published, which will later be changed when the
     * user clicks on save.
     *
     * @access public
     * @param int    $id        The id of the staticpage id to update
     * @param string $fast_url  The value of the fast_url. This will
     *                          be autocreated if nothing is passed.
     * @param bool   $showtitle This will to know if we show the title or not.
     * @param string $title     The new autosaved title
     * @param string $description   The description of the page
     * @param string $keywords  The keywords of the page
     * @param bool   $active If the item is published or not. Default: draft
     */
    function AutoDraft($id = '', $fast_url = '', $showtitle = '', $title = '', $description = '',
                       $keywords = '', $active = '', $gadget, $fieldnames, $fieldvalues)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('CustomPage', 'ManagePages') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'CustomPage', 'OwnPage')) {
			$this->CheckSession('CustomPage', 'ManagePages');
		}

        /*
		if ($id == 'NEW') {
            $adminModel->AddPage($fast_url, $show_title, $title, $content, $language, $published, true);
            $newid    = $GLOBALS['db']->lastInsertID('static_pages', 'id');
            $response['id'] = $newid;
            $response['message'] = _t('CUSTOMPAGE_PAGE_AUTOUPDATED',
                                      date('H:i:s'),
                                      (int)$id,
                                      date('D, d'));
            $GLOBALS['app']->Session->PushLastResponse($response, RESPONSE_NOTICE);
        } else {
            $adminModel->UpdatePage($id, $fast_url, $showtitle, $title, $content, $language, $published, true);
        }
        return $GLOBALS['app']->Session->PopLastResponse();
		*/
		return true;
	}

     /**
     * Moves an item in the sort_order
     *
     * @access  public
     * @param   int     $pid  Post ID
     * @param   string  $direction  'up', or 'down'
     * @return  array   Response (notice or error)
     */
    function SortItem($pids, $newsorts)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('CustomPage', 'ManagePages') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'CustomPage', 'OwnPage')) {
			$this->CheckSession('CustomPage', 'ManagePages');
		}
		$res = array();
		$gadget = $GLOBALS['app']->LoadGadget('CustomPage', 'AdminModel');
		$sort = $gadget->SortItem($pids, $newsorts);
        if ($sort === false) {
            $res['success'] = false;
        } else {
            //$res['id'] = (int)$pid;
            //if ($direction == 'up') {
			//	$res['moved'] = -1;
            //} else {
			//	$res['moved'] = 1;
			//}
			$res['success'] = true;
        }
        $res['message'] = $GLOBALS['app']->Session->PopLastResponse();
        return $res;
    }

    /**
     * Get actions of a given gadget
     *
     * @access  public
     * @params  string  $gadget
     * @return  array   Actions of the given gadget
     */
    function GetGadgetActions($gadget, $limit = null, $offset = null)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('CustomPage', 'ManagePages') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'CustomPage', 'OwnPage')) {
			$this->CheckSession('CustomPage', 'ManagePages');
		}
		$gadget = $GLOBALS['app']->LoadGadget('CustomPage', 'AdminModel');
        return $gadget->GetGadgetActions($gadget, $limit, $offset);
    }

    /**
     * Add gadget to layout 
     *
     * @access  public
     * @params  string  $gadget
     * @params  string  $action
     * @return  array   Details of the added gadget/action
     */
    function AddGadget($addgadget, $addaction, $page_gadget = 'CustomPage', $page_action = 'Page', $page_linkid = '', $items_on_layout = '') 
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('CustomPage', 'ManagePages') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'CustomPage', 'OwnPage')) {
			$this->CheckSession('CustomPage', 'ManagePages');
		}
		require_once JAWS_PATH . 'include/Jaws/Layout.php';
		if (!isset($GLOBALS['app']->Layout)) {
			$GLOBALS['app']->Layout = new Jaws_Layout();
		}
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$adminModel = $GLOBALS['app']->LoadGadget('CustomPage', 'AdminModel');
		$model = $GLOBALS['app']->LoadGadget('CustomPage', 'Model');
		$GLOBALS['app']->Registry->LoadFile('Layout');
		$GLOBALS['app']->Translate->LoadTranslation('Layout', JAWS_GADGET);
		$layoutAdminModel = $GLOBALS['app']->LoadGadget('Layout', 'AdminModel');
        if (is_null($page_linkid) || empty($page_linkid)) {    
			$GLOBALS['app']->Session->PushLastResponse(_t('LAYOUT_ERROR_ELEMENT_ADDED'), RESPONSE_ERROR);
            $res['success'] = false;
        }
		$displayWhen = '{GADGET:'.$page_gadget.'|ACTION:'.$page_action.'('.$page_linkid.')}';
		$res = array();
		$id = $layoutAdminModel->NewElement('main', $addgadget, $addaction, '', $displayWhen);
		if ($id === false) {
            $GLOBALS['app']->Session->PushLastResponse(_t('LAYOUT_ERROR_ELEMENT_ADDED'), RESPONSE_ERROR);
            $res['success'] = false;
        } else {
            $gadget = $layoutAdminModel->GetElement($id);
            $GLOBALS['app']->Session->PushLastResponse(_t('LAYOUT_ELEMENT_ADDED'), RESPONSE_NOTICE);
            			
            $name = 'main';
            $name_prefix = ($name == 'main' || substr($name, 0, 7) == 'section' ? 'custom_page-' : '');
			
			$t_item = new Jaws_Template('gadgets/CustomPage/templates/');
			$t_item->Load('LayoutManager.html');
			$t_item->SetBlock('item');
			$t_item->SetVariable('section_id', $name_prefix.$name);

			$actions = $GLOBALS['app']->GetGadgetActions($gadget['gadget']);
			$actions = (isset($actions['LayoutAction'])) ? $actions['LayoutAction'] : array();
			if (isset($actions)) {
				$info = $GLOBALS['app']->LoadGadget($gadget['gadget'], 'Info');
				//$t_item->SetVariable('gadget', $info->GetName());
				if (isset($actions[$gadget['gadget_action']]['name'])) {
					//$t_item->SetVariable('action', $actions[$gadget['gadget_action']]['name']);
				} else {
					$layoutGadget = $GLOBALS['app']->LoadGadget($gadget['gadget'], 'LayoutHTML');
					if (method_exists($layoutGadget, 'LoadLayoutActions')) {
						$actions = $layoutGadget->LoadLayoutActions();
						if (isset($actions[$gadget['gadget_action']]['name'])) {
							//$t_item->SetVariable('action', $actions[$gadget['gadget_action']]['name']);
						} else {
							//$t_item->SetVariable('action', $gadget['gadget_action']);
						}
					} else {
						//$t_item->SetVariable('action', $gadget['gadget_action']);
					}
					unset($layoutGadget);
				}
				unset($info);
			} else {
				//$t_item->SetVariable('gadget', $gadget['gadget']);
				//$t_item->SetVariable('action', _t('LAYOUT_ACTIONS'));
			}
			
			$t_item->SetVariable('pos', $gadget['layout_position']);
			$t_item->SetVariable('icon', $GLOBALS['app']->GetJawsURL() . '/gadgets/'.$gadget['gadget'].'/images/logo.png');
			if (isset($actions[$gadget['gadget_action']])) {
				$t_item->SetVariable('description', $actions[$gadget['gadget_action']]['desc']);
				$t_item->SetVariable('item_status', 'none');
			} else {
				$t_item->SetVariable('description', $gadget['gadget_action']);
				$t_item->SetVariable('item_status', 'line-through');
			}
			unset($actions);

			$t_item->SetVariable('delete-img', $GLOBALS['app']->GetJawsURL() . '/gadgets/Layout/images/delete-item.gif');
			$t_item->SetVariable('delete', 'deleteElement(\''.$gadget['id'].'\',\''._t('LAYOUT_CONFIRM_DELETE').'\');');
			$t_item->SetVariable('lbl_display_when', _t('LAYOUT_DISPLAY_IN'));
			$t_item->SetVariable('display_when', _t('CUSTOMPAGE_THIS_PAGE'));
			$t_item->SetVariable('move-img', $GLOBALS['app']->GetJawsURL() . '/gadgets/Layout/images/move-item.gif');
			
			// Set items already on the layout
			$items_on_layout = explode(',', $items_on_layout);
			foreach ($items_on_layout as $on_layout) {
				if (!is_null($on_layout) && !empty($on_layout)) {
					$GLOBALS['app']->_ItemsOnLayout[] = $on_layout;
				}
			}
			
			// Put gadget on the layout
			$fakeLayout = new Jaws_Layout();
            $fakeLayout->_RequestedGadget = 'CustomPage';
            $fakeLayout->_RequestedAction = 'Ajax';
			$content = $fakeLayout->PutGadget($gadget['gadget'], $gadget['gadget_action'], $gadget['section']);
			
			// Return new items on the layout
			$items_on_layout = array();
			foreach ($GLOBALS['app']->_ItemsOnLayout as $on_layout) {
				$items_on_layout[] = $on_layout;
			}
			$new_items_on_layout = implode(',',$items_on_layout);
			
			$head_items[$name_prefix.$name] .= (!empty($head_items[$name_prefix.$name]) ? ',' : '').'"item_'.$id.'":true';
			if (substr($gadget['gadget_action'], 0, 9) == 'ShowPost(') {
				$post_id = str_replace(array('ShowPost(', ')'), '', $gadget['gadget_action']);
				$editor =& $GLOBALS['app']->LoadEditor('CustomPage', 'custom_page-post-text-'.$post_id, $content, false, '', true, $GLOBALS['app']->GetSiteURL().'/admin.php?gadget=CustomPage&action=SaveEditPost&id='.$post_id, $inplace_options);
				$editor->SetBaseToolbar(array($baseToolbar));
				//$head_scripts_editable = '<script type="text/javascript">Event.observe(window, "load", function(){if($(\'item_'.$id.'\')){$$(\'#item_'.$id.' .custom_page-post-text\').each(function(element){element.addClassName(\'custom_page-post-text-'.$post_id.'\');});}});</script>'."\n";
				$head_scripts_editable = '';
				$content = $head_scripts_editable.$editor->Get();
				$t_item->SetVariable('move-edit', '');
				$t_item->SetVariable('edit_style', " style=\"display: none;\"");
				$editImg = $GLOBALS['app']->GetJawsURL() . '/gadgets/Layout/images/edit-item.gif';
			} else {
				$t_item->SetVariable('move-edit', " item-move-edit");
				$t_item->SetVariable('edit_style', '');
				$editImg = $GLOBALS['app']->GetJawsURL() . '/gadgets/Layout/images/gadget-item.gif';
			}

			$content = str_replace(array("\r","\n","\t"), '', $content);
			$inputStr = $content;
			$delimeterLeft = "<script language=\"javascript\" type=\"text/javascript\" src=\"data/tinymce/tiny_mce.js";
			$delimeterRight = "return value;}</script>";
			$startLeft = strpos($inputStr, $delimeterLeft);
			$posLeft = ($startLeft+strlen($delimeterLeft));
			$posRight = strpos($inputStr, $delimeterRight, $posLeft);
			$toReplace = $delimeterLeft.substr($inputStr, $posLeft, $posRight-$posLeft).$delimeterRight;
			$content = str_replace($toReplace, '', $content);
			$t_item->SetVariable('gadget', $content);
			
			$t_item->SetVariable('controls', $controls);
			$t_item->SetVariable('void_link', '');
			$t_item->SetVariable('item_id', $id);
			$t_item->SetVariable('base_script_url', $GLOBALS['app']->GetSiteURL('/'.BASE_SCRIPT));
			$t_item->SetVariable('edit-img', $editImg);
			$t_item->SetVariable('controls_style', '');
			$t_item->SetVariable('item_class', '');
			$t_item->ParseBlock('item');
            $res['success'] = true;
            $res['id'] = $id;
            $res['section'] = $name;
            $res['section_name'] = $name_prefix.$name;
            $res['gadget_html'] = $t_item->Get();
            $res['items_on_layout'] = $new_items_on_layout;
        }
        $res['message'] = $GLOBALS['app']->Session->PopLastResponse();
        return $res;
    }

    /**
     * Edit layout's element action
     * 
     * @access  public
     * @param   int     $item   Item ID
     * @params  string  $action
     * @return  array   Response
     */
    function EditElementAction($item, $action) 
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('CustomPage', 'ManagePages') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'CustomPage', 'OwnPage')) {
			$this->CheckSession('CustomPage', 'ManagePages');
		}
		$gadget = $GLOBALS['app']->LoadGadget('CustomPage', 'AdminModel');
        $res = $gadget->EditElementAction($item, $action);
        if ($res === false) {
            $GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_POST_NOT_UPDATED'), RESPONSE_ERROR);
        } else {
            $GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_POST_UPDATED'), RESPONSE_NOTICE);
        }
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Adds URL to embed_gadgets
     *
     * @access  public
     * @params  string  $gadget
     * @return  array   Actions of the given gadget
     */
    function AddEmbedSite($gadget, $url, $gadget_url, $layout)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('CustomPage', 'ManagePages') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'CustomPage', 'OwnPage')) {
			$this->CheckSession('CustomPage', 'ManagePages');
		}
		$adminModel = $GLOBALS['app']->LoadGadget('Users', 'AdminModel');
		return $adminModel->AddEmbedSite($gadget, $url, $gadget_url, $layout);
    }

     /**
     * Saves stack order of a page's section
     *
     * @param   int  $sid  section ID
     * @param   string  $stack  stack order
     * @access  public
     * @return  array   Response (notice or error)
     */
    function UpdateStackOrder($sid, $stack)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('CustomPage', 'ManagePages') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'CustomPage', 'OwnPage')) {
			$this->CheckSession('CustomPage', 'ManagePages');
		}
		$gadget = $GLOBALS['app']->LoadGadget('Users', 'AdminModel');
        $gadget->UpdateStackOrder($sid, $stack);
        return $GLOBALS['app']->Session->PopLastResponse();
    }
	
    /**
     * Get Quick Add Forms of Gadget
     *
     * @access public
     * @param string	$method	The method to call
     * @param array	$params	The params to pass to method
     * @param string	$callback	The method to call afterwards
     * @return  array	Response (notice or error)
     */
    function GetQuickAddForms($gadget = '') 
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('CustomPage', 'ManagePages') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'CustomPage', 'OwnPage')) {
			$this->CheckSession('CustomPage', 'ManagePages');
		}
        $res = array();
		// Action is for specific ID
		if (!empty($gadget) && Jaws_Gadget::IsGadgetUpdated($gadget)) {
			$GLOBALS['app']->Registry->LoadFile($gadget);
			$GLOBALS['app']->Translate->LoadTranslation($gadget, JAWS_GADGET);
			$hook = $GLOBALS['app']->loadHook($gadget, 'URLList');
			if ($hook !== false) {
				if (method_exists($hook, 'GetQuickAddForms')) {
					$forms = $hook->GetQuickAddForms();
					if ($forms !== false) {
						$i = 0;
						foreach ($forms as $form) {
							$res[$i]['name'] = $form['name'];
							$res[$i]['method'] = $form['method'];
							$i++;
						}
					}
				}
			}
		}
		return $res;
	}
    
	/**
     * Adds a form quickly
     *
     * @access public
     * @param string	$method	The method to call
     * @param array	$params	The params to pass to method
     * @return  array	Response (notice or error)
     */
    function SaveQuickAdd($addtype = 'CustomPage', $method, $params) 
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('CustomPage', 'ManagePages') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'CustomPage', 'OwnPage')) {
			$this->CheckSession('CustomPage', 'ManagePages');
		}
		require_once JAWS_PATH . 'include/Jaws/Image.php';
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$adminHTML = $GLOBALS['app']->LoadGadget('CustomPage', 'AdminHTML');
		$model = $GLOBALS['app']->LoadGadget('CustomPage', 'Model');
		
		$shout_params = array();
		$shout_params['gadget'] = 'CustomPage';
		$res = array();
		// Which method
		$result = $adminHTML->form_post(false, $method, $params);
		if ($result === false || Jaws_Error::IsError($result)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_SAVE_QUICKADD'), RESPONSE_ERROR);
			$res['success'] = false;
		} else {
			$id = $result;
			if ($method == 'AddPage' || $method == 'EditPage') {
				$post = $model->GetPage($id);
			} else if ($method == 'AddPost' || $method == 'EditPost') {
				$post = $model->GetPost($id);
			}
			if (!Jaws_Error::IsError($post) && isset($post['id']) && !empty($post['id'])) {
				$shout_params['edit_url'] = $GLOBALS['app']->GetSiteURL() .'/admin.php?gadget=CustomPage&action='.($method == 'AddPost' || $method == 'EditPost' ? 'A_' : '').'form&id='.$id;
				$el = array();
				$el = $post;
				// TODO: Return different array if callback is requested ("notify" mode)
				/*
				if (!is_null($params['callback'])) {
				} else {
				*/
					//$GLOBALS['app']->Session->PushLastResponse(_t('LAYOUT_ELEMENT_ADDED'), RESPONSE_NOTICE);
					$image_src = '';
					$image = $post['image'];
					$hasSplash = false;
					if ($method == 'AddPost' || $method == 'EditPost') {
						$splashInfo = $model->GetSplashPanelsOfPage($post['id']);
						if (count($splashInfo) > 0 && isset($splashInfo[0]['id'])) {
							$hasSplash = true;
						}
					}
					$el['tname'] = '';
					if (
						isset($params['gadget_scope']) && $params['gadget_scope'] == 'Users' && 
						isset($params['gadget_action']) && $params['gadget_action'] == 'EmailPage'
					) {
						$el['tname'] = '{GADGET:Users|ACTION:EmailPage('.$post['id'].')}';
					}
					$el['taction'] = '';
					$el['tactiondesc'] = substr(strip_tags($post['description']), 0, 100).(strlen(strip_tags($post['description'])) > 100 ? '...' : '');
					if (!empty($image)) {
						if ($method == 'AddPage' || $method == 'EditPage') {
							$el['icon'] = $GLOBALS['app']->GetJawsURL() . '/gadgets/CustomPage/images/logo.png';
							$url_ea = $GLOBALS['app']->getSiteURL() .'/'. BASE_SCRIPT. '?gadget=CustomPage&action=view&id='.$id;
						} else {
							$url_ea = $GLOBALS['app']->getSiteURL() .'/'. BASE_SCRIPT. '?gadget=CustomPage&action=EditElementAction&id='.$id.'&method='.str_replace('Add', 'Edit', $method);
							if ($hasSplash === true) {
								$el['icon'] = $GLOBALS['app']->GetJawsURL() . '/gadgets/FileBrowser/images/mimetypes/text-x-carousel-generic.png';
							} else {
								$el['icon'] = $GLOBALS['app']->GetJawsURL() . '/gadgets/FileBrowser/images/mimetypes/text-x-image-generic.png';
							}
						}
						if (isset($image) && !empty($image)) {
							$image = $xss->filter(strip_tags($image));
							if (substr(strtolower($image), 0, 4) == "http") {
								if (substr(strtolower($image), 0, 7) == "http://") {
									$image_src = explode('http://', $image);
									foreach ($image_src as $img_src) {
										if (!empty($img_src)) {
											$image_src = 'http://'.$img_src;
											break;
										}
									}
								} else {
									$image_src = explode('https://', $image);
									foreach ($image_src as $img_src) {
										if (!empty($img_src)) {
											$image_src = 'https://'.$img_src;
											break;
										}
									}
								}
							} else {
								$thumb = Jaws_Image::GetThumbPath($image);
								$medium = Jaws_Image::GetMediumPath($image);
								if (file_exists(JAWS_DATA . 'files'.$thumb)) {
									$image_src = $GLOBALS['app']->getDataURL() . 'files'.$thumb;
								} else if (file_exists(JAWS_DATA . 'files'.$medium)) {
									$image_src = $GLOBALS['app']->getDataURL() . 'files'.$medium;
								} else if (file_exists(JAWS_DATA . 'files'.$image)) {
									$image_src = $GLOBALS['app']->getDataURL() . 'files'.$image;
								}
							}
						}
					} else {
						if ($method == 'AddPage' || $method == 'EditPage') {
							$el['icon'] = $GLOBALS['app']->GetJawsURL() . '/gadgets/CustomPage/images/logo.png';
							$url_ea = $GLOBALS['app']->getSiteURL() .'/'. BASE_SCRIPT. '?gadget=CustomPage&action=view&id='.$id;
						} else {
							$url_ea = $GLOBALS['app']->getSiteURL() .'/'. BASE_SCRIPT. '?gadget=CustomPage&action=EditElementAction&id='.$id.'&method='.str_replace('Add', 'Edit', $method);
							if ($hasSplash === true) {
								$el['icon'] = $GLOBALS['app']->GetJawsURL() . '/gadgets/FileBrowser/images/mimetypes/text-x-carousel-generic.png';
							} else if (!empty($post['image_code'])) {
								$el['icon'] = $GLOBALS['app']->GetJawsURL() . '/gadgets/FileBrowser/images/mimetypes/text-x-html-generic.png';
							} else {
								$el['icon'] = $GLOBALS['app']->GetJawsURL() . '/gadgets/FileBrowser/images/mimetypes/text-x-generic.png';
							}
						}
					}
					$el['lgadget'] = 'CustomPage';
					if ($method == 'AddPage' || $method == 'EditPage') {
					} else {
						$page = $model->GetPage($post['linkid']);
						if (!Jaws_Error::IsError($page) && isset($page['id']) && !empty($page['id'])) {
							$el['laction'] = 'ShowPost('.$post['id'].')';
							$el['page_gadget'] = $page['gadget'];
							$el['page_action'] = $page['gadget_action'];
							$el['page_linkid'] = $page['linkid'];
						}
					}
					$el['eaurl'] = $url_ea;
					$el['image_thumb'] = $image_src;
					$el['eaid'] = 'ea'.$id;
				//}	
				$res = $el;
				$res['success'] = true;
				$res['addtype'] = $addtype;
				$res['method'] = $method;
				if (isset($params['sharing']) && !empty($params['sharing'])) {
					$res['sharing'] = $params['sharing'];
				}
				if (isset($params['callback']) && !empty($params['callback'])) {
					$res['callback'] = $params['callback'];
				}
				if (isset($params['items_on_layout']) && !empty($params['items_on_layout'])) {
					$res['items_on_layout'] = $params['items_on_layout'];
				}
			} else {
				if (Jaws_Error::IsError($post)) {
					$GLOBALS['app']->Session->PushLastResponse($post->GetMessage(), RESPONSE_ERROR);
				} else {
					$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_POST_NOT_ADDED').' '.var_export($post, true), RESPONSE_ERROR);
				}
				$res['success'] = false;
			}
		}
		/*
		if (!is_null($callback)) {
			// Let everyone know content has been added
			$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
			$res = $GLOBALS['app']->Shouter->Shout($callback, $shout_params);
			if (!Jaws_Error::IsError($res)) {
				$GLOBALS['app']->Session->PushLastResponse($res->GetMessage(), RESPONSE_ERROR);
				$res['success'] = false;
			}
		}
		*/
		
        $res['message'] = $GLOBALS['app']->Session->PopLastResponse();
        return $res;
	}
			
	/**
     * Adds a comment
     *
     * @access  public
     * @param   string  $title      Title of the comment
     * @param   string  $comments   Text of the comment
     * @param   int     $parent     ID of the parent comment
     * @param   int     $parentId   ID of the entry
     * @param   string  $ip         IP of the author
     * @param   boolean $set_cookie Create a cookie
     * @return  boolean True if comment was added, and false if not.
     */
    function NewCustomPageComment($title = '', $comments, $parent, $parentId, $ip = '', $set_cookie = true, $sharing = 'everyone', $reply = false)
    {
        $res = array();
		if (!$GLOBALS['app']->Session->Logged()) {
	        //require_once JAWS_PATH . 'include/Jaws/Header.php';
			//Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'));
			$this->CheckSession('CustomPage', 'ManagePages');
		} else {
			if (empty($parentId)) {
				$parentId = $GLOBALS['app']->Session->GetAttribute('user_id');
			}
			require_once JAWS_PATH . 'include/Jaws/User.php';
			$jUser = new Jaws_User();
			$info = $jUser->GetUserInfoById((int)$GLOBALS['app']->Session->GetAttribute('user_id'), true, true, true, true);
			$model = $GLOBALS['app']->LoadGadget('Users', 'Model');
			$result = $model->NewComment(
				(!empty($info['company']) ? $info['company'] : $info['nickname']), $title, $info['url'], $info['email'], $comments, 
				(int)$parent, (int)$parentId, $ip, $set_cookie, (int)$GLOBALS['app']->Session->GetAttribute('user_id'), $sharing, 'CustomPage'
			);
			if (Jaws_Error::IsError($result)) {
				$res['css'] = 'error-message';
				$res['message'] = $result->GetMessage();
			} else {
				$res['css'] = 'notice-message';
				$res['message'] = _t('GLOBAL_COMMENT_ADDED');
				$res['id'] = $result['id'];
				$res['link'] = $result['link'];
				if ((int)$parent == 0 && $reply === false) {
					$res['image'] = (!empty($result['link']) ? '<a href="'.$result['link'].'">' : '').'<img src="'.$result['avatar_source'].'" border="0" align="left" />'.(!empty($result['link']) ? '</a>' : '');
					if (!empty($result['image'])) {
						$res['image'] = (!empty($result['link']) ? '<a href="'.$result['link'].'">' : '').'<img src="'.$result['image'].'" border="0" align="left" />'.(!empty($result['link']) ? '</a>' : '');
					}
				} else {
					$res['image'] = '<div class="comment-image-holder">'.(!empty($result['link']) ? '<a href="'.$result['link'].'">' : '').'<img src="'.$result['avatar_source'].'" border="0" align="left" class="comment-image" />'.(!empty($result['link']) ? '</a>' : '').'</div>';
					if (!empty($result['image'])) {
						$res['image'] = '<div class="comment-image-holder">'.(!empty($result['link']) ? '<a href="'.$result['link'].'">' : '').'<img src="'.$result['image'].'" border="0" align="left" class="comment-image" />'.(!empty($result['link']) ? '</a>' : '').'</div>';
					}
				}
				$res['name'] = $result['name'];
				$full_style = '';
				$preview_style = ' style="display: none;"';
				//$msg_reply = strip_tags($result['comment']);
				$msg_reply = $result['comment'];
				$msg_reply_preview = '';
				/*
				if (strlen($msg_reply) > 150) {
					$msg_reply_preview = substr($msg_reply, 0, 150).'&nbsp;<a class="comment-showhide" href="javascript:void(0);" onclick="toggleFullComment('.$result['id'].');">Read it</a>';
					$msg_reply .= '&nbsp;<a class="comment-showhide" href="javascript:void(0);" onclick="toggleFullComment('.$result['id'].');">Hide it</a>';
					$preview_style = '';
					$full_style = ' style="display: none;"';
				}
				*/
				$res['full_style'] = $full_style;
				$res['preview_style'] = $preview_style;
				$res['comment'] = $msg_reply;
				$res['preview_comment'] = $msg_reply_preview;
				$res['title'] = $result['title'];
				$res['created'] = $result['created'];
				$res['permalink'] = $result['permalink'];
				// Let everyone know
				$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
				$shout = $GLOBALS['app']->Shouter->Shout('onBeforeSocialSharing', array('url' => $result['permalink']));
				if (!Jaws_Error::IsError($shout) && (isset($shout['url']) && !empty($shout['url']))) {
					$res['permalink'] = $shout['url'];
				}
				$res['activity'] = '';
			}
		}
		return $res;
    }
	
    /**
     * Deletes a comment
     *
     * @access  public
     * @param   int     $id   Comment ID
     * @return  array   Response (notice or error)
     */
    function DeleteCustomPageComment($id)
    {
		if (!$GLOBALS['app']->Session->Logged()) {
	        //require_once JAWS_PATH . 'include/Jaws/Header.php';
			//Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'));
			$this->CheckSession('CustomPage', 'ManagePages');
		} else {
			$uid = $GLOBALS['app']->Session->GetAttribute('user_id');
			$params 		= array();
			$params['id']   = (int)$id;
					
			$sql = '
				SELECT
					[gadget], [parent], [ownerid]
				FROM [[comments]]
				WHERE [id] = {id}';

			$gadget = $GLOBALS['db']->queryRow($sql, $params);
			if (Jaws_Error::IsError($gadget) || !isset($gadget['gadget']) || empty($gadget['gadget'])) {
				$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_COMMENT_DELETED'), RESPONSE_ERROR);
				return $GLOBALS['app']->Session->PopLastResponse();
			}
			// Is this a child comment of current user? They can delete it...
			if ((int)$gadget['parent'] > 0) {
				$params 		= array();
				$params['id']	= (int)$gadget['parent'];
						
				$sql = '
					SELECT
						[gadget], [parent], [ownerid]
					FROM [[comments]]
					WHERE [id] = {id}';

				$parent = $GLOBALS['db']->queryRow($sql, $params);
				if (Jaws_Error::IsError($parent) || !isset($parent['gadget']) || empty($parent['gadget'])) {
					$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_COMMENT_DELETED'), RESPONSE_ERROR);
					return $GLOBALS['app']->Session->PopLastResponse();
				}
			}
			if ($uid != $gadget['ownerid'] && (isset($parent['ownerid']) && $uid != $parent['ownerid'])) {
				$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_COMMENT_DELETED'), RESPONSE_ERROR);
			} else {
				$model = $GLOBALS['app']->LoadGadget('Users', 'Model');
				$delete = $model->DeleteComment($id, $gadget['gadget']);
				if (!Jaws_Error::IsError($delete)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_COMMENT_DELETED'), RESPONSE_NOTICE);
				} else {
					$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_COMMENT_DELETED'), RESPONSE_ERROR);
				}
			}
		}
        return $GLOBALS['app']->Session->PopLastResponse();
    }
}
