<?php
/**
 * CustomPage AJAX API
 *
 * @category   Ajax
 * @package    CustomPage
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class CustomPageAdminAjax extends Jaws_Ajax
{
    // {{{ Constructor PHP4
    /**
     * PHP 4 Constructor
     *
     * @access  public
     */
    function CustomPageAdminAjax(&$model)
    {
        $this->_Model =& $model;
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
		$this->CheckSession('CustomPage', 'ManagePages');
        $this->_Model->DeletePage($id);
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
		$this->CheckSession('CustomPage', 'ManagePages');
        $this->_Model->DeletePost($pid);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

     /**
     * Hides RSS item
     *
     * @param   int  $pid  page ID
     * @param   string  $title  title of Rss item
     * @param   string  $published  date of Rss item
     * @param   string  $url  url of Rss item
     * @access  public
     * @return  array   Response (notice or error)
     */
    function HideRss($pid, $title, $published, $url)
    {
		$this->CheckSession('CustomPage', 'ManagePages');
        $this->_Model->HideRss($pid, $title, $published, $url);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

     /**
     * Shows RSS item
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
		$this->CheckSession('CustomPage', 'ManagePages');
        $this->_Model->ShowRss($pid, $title, $published, $url);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

	/**
     * Deletes a post
     *
     * @access  public
     * @param   int     $pid  Post ID
     * @return  array   Response (notice or error)
     */
    function DeleteSplashPanel($pid)
    {
		$this->CheckSession('CustomPage', 'ManagePages');
        $this->_Model->DeleteSplashPanel($pid);
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
		$this->CheckSession('CustomPage', 'ManagePages');
        $this->_Model->MassiveDelete($pages);
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
		$this->CheckSession('CustomPage', 'ManagePages');
        $pages = $this->_Model->SearchPages($status, $search, null, 0);
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
		$this->CheckSession('CustomPage', 'ManagePages');
        $gadget = $GLOBALS['app']->LoadGadget('CustomPage', 'AdminHTML');
        if (!is_numeric($limit)) {
            $limit = 0;
        }
		return $gadget->GetPages($status, $search, $limit, false, 0);
    }
    
    /**
     * Get total templates of a search
     *
     * @access  public
     * @param   string  $status  Status of page(s) we want to display
     * @param   string  $search  Keyword (title/description) of pages we want to look for
     * @return  int     Total of posts
     */
    function SizeOfSearch1($gadgetscope = 'CustomPage', $search = '')
    {
		$this->CheckSession('CustomPage', 'ManagePages');
        $pages = $this->_Model->SearchTemplates($gadgetscope, $search);
        return count($pages);
    }

    /**
     * Returns an array with all the templates
     *
     * @access  public
     * @param   string  $search  Keyword (title/description) of pages we want to look for
     * @param   int     $limit   Data limit
     * @return  array   Pages data
     */
    function SearchTemplates($gadgetscope = 'CustomPage', $search = '', $limit)
    {
		$this->CheckSession('CustomPage', 'ManagePages');
        $gadget = $GLOBALS['app']->LoadGadget('CustomPage', 'AdminHTML');
        if (!is_numeric($limit)) {
            $limit = 0;
        }
		return $gadget->GetTemplates($gadgetscope, $search, $limit);
    }
    
	/**
     * Deletes a template
     *
     * @access  public
     * @param   string     $file  Filename
     * @return  array   Response (notice or error)
     */
    function DeleteTemplate($file)
    {
		$this->CheckSession('CustomPage', 'ManagePages');
		$GLOBALS['app']->Registry->LoadFile('FileBrowser');
		$GLOBALS['app']->Translate->LoadTranslation('FileBrowser', JAWS_GADGET);
        if (file_exists($file)) {
			// Delete file
            $return = @unlink($file);
            if (!$return) {
                $GLOBALS['app']->Session->PushLastResponse(_t('FILEBROWSER_ERROR_CANT_DELETE_FILE', $file), RESPONSE_ERROR);
            } else {
				$GLOBALS['app']->Session->PushLastResponse(_t('FILEBROWSER_DELETED_FILE', $file), RESPONSE_NOTICE);
			}
		} else {
            $GLOBALS['app']->Session->PushLastResponse(_t('FILEBROWSER_ERROR_CANT_DELETE_FILE', $file), RESPONSE_NOTICE);
		}
        return $GLOBALS['app']->Session->PopLastResponse();
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
		$this->CheckSession('CustomPage', 'ManagePages');

        /*
		if ($id == 'NEW') {
            $this->_Model->AddPage($fast_url, $show_title, $title, $content, $language, $published, true);
            $newid    = $GLOBALS['db']->lastInsertID('static_pages', 'id');
            $response['id'] = $newid;
            $response['message'] = _t('CUSTOMPAGE_PAGE_AUTOUPDATED',
                                      date('H:i:s'),
                                      (int)$id,
                                      date('D, d'));
            $GLOBALS['app']->Session->PushLastResponse($response, RESPONSE_NOTICE);
        } else {
            $this->_Model->UpdatePage($id, $fast_url, $showtitle, $title, $content, $language, $published, true);
        }
        return $GLOBALS['app']->Session->PopLastResponse();
		*/
		return true;
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
        $this->CheckSession('CustomPage', 'default');
        if ($gadget == 'Text') {
			$res = array();
			$res[] = array('action' => 'ShowPost({new0})', 
						   'name'   => _t('CUSTOMPAGE_LAYOUT_NEWPOST0'),
						   'desc'   => _t('CUSTOMPAGE_LAYOUT_NEWPOST0_DESCRIPTION'),
						   'add'	=> false);
			return $res;
		} else {
			return $this->_Model->GetGadgetActions($gadget, $limit, $offset);
		}
	}

    /**
     * Add gadget to layout 
     *
     * @access  public
     * @params  string  $gadget
     * @params  string  $action
     * @return  array   Details of the added gadget/action
     */
    function AddGadget($addgadget, $addaction, $page_gadget = 'CustomPage', $page_action = 'Page', $page_linkid = '', $items_on_layout = '', $section_name = 'main') 
    {
        $this->CheckSession('CustomPage', 'ManagePages');
		require_once JAWS_PATH . 'include/Jaws/Layout.php';
		if (!isset($GLOBALS['app']->Layout)) {
			$GLOBALS['app']->Layout = new Jaws_Layout();
		}
		$GLOBALS['app']->Registry->LoadFile('Layout');
		$GLOBALS['app']->Translate->LoadTranslation('Layout', JAWS_GADGET);
		$layoutAdminModel = $GLOBALS['app']->LoadGadget('Layout', 'AdminModel');
		$res = array();
        if (is_null($page_linkid) || empty($page_linkid)) {    
			$GLOBALS['app']->Session->PushLastResponse(_t('LAYOUT_ERROR_ELEMENT_ADDED'), RESPONSE_ERROR);
            $res['success'] = false;
			$res['message'] = $GLOBALS['app']->Session->PopLastResponse();
			return $res;
        }
		$displayWhen = '{GADGET:'.$page_gadget.'|ACTION:'.$page_action.'('.$page_linkid.')}';
		$id = $layoutAdminModel->NewElement($section_name, $addgadget, $addaction, '', $displayWhen);
		if ($id === false) {
            $GLOBALS['app']->Session->PushLastResponse(_t('LAYOUT_ERROR_ELEMENT_ADDED'), RESPONSE_ERROR);
            $res['success'] = false;
			$res['message'] = $GLOBALS['app']->Session->PopLastResponse();
			return $res;
        } else {
            $gadget = $layoutAdminModel->GetElement($id);
            $GLOBALS['app']->Session->PushLastResponse(_t('LAYOUT_ELEMENT_ADDED'), RESPONSE_NOTICE);
            			
            $name = $section_name;
            $name_prefix = 'custom_page-';
			
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
			$edit_action = (strpos($gadget['gadget_action'], '(') !== false ? substr($gadget['gadget_action'], 0, strpos($gadget['gadget_action'], '(')) : $gadget['gadget_action']);
			$edit_id = (strpos($gadget['gadget_action'], '(') !== false ? str_replace(array('(',')'), '', substr($gadget['gadget_action'], strpos($gadget['gadget_action'], '('), strlen($gadget['gadget_action']))) : null);
			$baseToolbar = array(
				'bold,italic,strikethrough,underline,|,formatselect,|,justifyleft,justifycenter,justifyright,justifyfull',
				'bullist,numlist,|,code,|,undo,redo,|,image,example,unlink,|'
			);			
			$editImg = $GLOBALS['app']->GetJawsURL() . '/gadgets/Layout/images/gadget-item.gif';
			if ($edit_action == 'ShowPost') {
				$editor =& $GLOBALS['app']->LoadEditor('CustomPage', 'custom_page-post-text-'.$edit_id, $content, false, '', true, $GLOBALS['app']->GetSiteURL().'/admin.php?gadget=CustomPage&action=SaveEditPost&id='.$edit_id, $inplace_options);
				$editor->SetBaseToolbar(array($baseToolbar));
				//$head_scripts_editable = '<script type="text/javascript">Event.observe(window, "load", function(){if($(\'item_'.$id.'\')){$$(\'#item_'.$id.' .custom_page-post-text\').each(function(element){element.addClassName(\'custom_page-post-text-'.$edit_id.'\');});}});</script>'."\n";
				$head_scripts_editable = '';
				$content = $head_scripts_editable.$editor->Get();
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
			
			$t_item->SetVariable('move-edit', " item-move-edit");
			$t_item->SetVariable('edit_style', "");
			$t_item->SetVariable('controls', $controls);
			$t_item->SetVariable('void_link', '');
			$t_item->SetVariable('item_id', $id);
			$t_item->SetVariable('base_script_url', $GLOBALS['app']->GetSiteURL('/'.BASE_SCRIPT));
			$edit = "javascript:void(0);\" onclick=\"editElementAction('".$GLOBALS['app']->GetSiteURL('/'.BASE_SCRIPT)."?gadget=Layout&amp;action=EditElementAction&amp;id=".$id."');";
			$hook = $GLOBALS['app']->loadHook($gadget['gadget'], 'URLList');
			if ($hook !== false) {
				if (method_exists($hook, 'GetEditPage')) {
					$page = $hook->GetEditPage($edit_action, $edit_id, true);
					if ($page !== false) {
						$edit = $page."\" target=\"_blank";
					}
				}
			}
			$t_item->SetVariable('edit', $edit);
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
        $this->CheckSession('CustomPage', 'default');
        $res = $this->_Model->EditElementAction($item, $action);
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
        $this->CheckSession('CustomPage', 'default');
		$gadget = $GLOBALS['app']->LoadGadget('Users', 'AdminModel');
		return $gadget->AddEmbedSite($gadget, $url, $gadget_url, $layout);
    }
    
	/**
     * Saves the value of a key
     *
     * @access  public
     * @param   string  $key   Key name
     * @param   string  $value Key value
     * @return  array   Response
     */
    function SetRegistryKey($key, $value)
    {
        $this->CheckSession('CustomPage', 'ManagePages');
        $this->_Model->SetRegistryKey($key, $value);
		return $GLOBALS['app']->Session->PopLastResponse();
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
		$this->CheckSession('CustomPage', 'ManagePages');
        $this->_Model->UpdateStackOrder($sid, $stack);
        return $GLOBALS['app']->Session->PopLastResponse();
    }
	
    /**
     * Get actions of a given gadget
     *
     * @access  public
     * @params  string  $gadget
     * @return  array   Actions of the given gadget
     */
    function GetGadgetEditPage($gadget = '', $action = '')
    {
        $this->CheckSession('CustomPage', 'default');
        $res = array();
		$res['response'] = false;
		// Action is for specific ID
		if (!empty($gadget) && !empty($action)) {
			$res['response'] = true;
			$res['url'] = $GLOBALS['app']->GetSiteURL() .'/admin.php?gadget='.$gadget;
			if (strpos($action, '(') !== false) {
				$actions = explode('(', $action);
				$action = $actions[0];
				$id = str_replace(')', '', $actions[1]);
			} else {
				$id = null;
			}
			$hook = $GLOBALS['app']->loadHook($gadget, 'URLList');
			if ($hook !== false) {
				if (method_exists($hook, 'GetEditPage')) {
					$edit_url = $hook->GetEditPage($action, $id, true);
					if ($edit_url !== false && !empty($edit_url)) {
						$res['url'] = $edit_url;
					}
				}
			}
			$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_POST_UPDATED'), RESPONSE_NOTICE);
			$res['message'] = $GLOBALS['app']->Session->PopLastResponse();
			return $res;
		}
		$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_POST_NOT_UPDATED'), RESPONSE_ERROR);
		$res['message'] = $GLOBALS['app']->Session->PopLastResponse();
		return $res;
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
        $this->CheckSession('CustomPage', 'default');
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
		$this->CheckSession('CustomPage', 'default');
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
    function NewComment($title = '', $comments, $parent, $parentId, $ip = '', $set_cookie = true, $sharing = 'everyone')
    {
        $res = array();
		if (!$GLOBALS['app']->Session->Logged()) {
	        //require_once JAWS_PATH . 'include/Jaws/Header.php';
			//Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'));
			$GLOBALS['app']->Session->PushLastResponse("User not logged in.", RESPONSE_ERROR);
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
				$image_src = $result['avatar_source'];
				if (!empty($result['image'])) {
					$image_src = $result['image'];
				}
				if ((int)$parent == 0) {
					$res['image'] = '<a href="'.$result['link'].'"><img src="'.$image_src.'" border="0" align="left" /></a>';
				} else {
					$res['image'] = '<div class="comment-image-holder"><a href="'.$result['link'].'"><img src="'.$image_src.'" border="0" align="left" class="comment-image" /></a></div>';
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
				$res['activity'] = '';
			}
		}
		return $res;
    }
	
     /**
     * Returns a post
     *
     * @access  public
     * @param   int     $pid  Post ID
     * @return  array   Response (notice or error)
     */
    function GetPost($pid)
    {
		$this->CheckSession('CustomPage', 'ManagePages');
		$result = array();
		$result[] = $this->_Model->GetPost($pid);
		return $result;
    }
     
	 /**
     * Saves post content
     *
     * @access  public
     * @param   int     $pid  Post ID
     * @return  array   Response (notice or error)
     */
    function SaveEditPost($pid, $content = '', $page_gadget = null, $page_action = null, 
	$page_linkid = null, $addtype = 'CustomPage', $method = 'EditPost', $section_name = 'main')
    {
		$this->CheckSession('CustomPage', 'ManagePages');
		if ($method == 'TempPost') {
			$result = $this->_Model->SaveTempPost(
				'<button class="temp-post-button">Add Post To This Section</button>', $page_gadget, $page_action, $page_linkid, $addtype, true, $section_name
			);
			$pid = $result;
		} else {
			$result = $this->_Model->SaveEditPost(
				$pid, $content, $page_gadget, $page_action, $page_linkid, $addtype, $method, true, $section_name
			);
        }
		$res = array();
		if (Jaws_Error::IsError($result)) {
			$res['success'] = false;
			$res['message'] = array(array('css' => 'error-message', 'message' => $result->GetMessage(), 'level' => 'RESPONSE_ERROR'));
		} else {
			$res['success'] = true;
			$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_POST_UPDATED'), RESPONSE_NOTICE);
			
			require_once JAWS_PATH . 'include/Jaws/Layout.php';
			if (!isset($GLOBALS['app']->Layout)) {
				$GLOBALS['app']->Layout = new Jaws_Layout();
			}
			$GLOBALS['app']->Registry->LoadFile('Layout');
			$GLOBALS['app']->Translate->LoadTranslation('Layout', JAWS_GADGET);
			$layoutAdminModel = $GLOBALS['app']->LoadGadget('Layout', 'AdminModel');
			if (is_null($page_linkid) || empty($page_linkid)) {    
				$res['success'] = false;
				$GLOBALS['app']->Session->PushLastResponse(_t('LAYOUT_ERROR_ELEMENT_ADDED'), RESPONSE_ERROR);
				$res['message'] = $GLOBALS['app']->Session->PopLastResponse();
				return $res;
			}
				
			// Get layout item
			$sql = '
				SELECT
					[id], [section], [gadget], [gadget_action], [layout_position], [display_when]
				FROM [[layout]]
				WHERE [gadget] = {gadget} AND [gadget_action] = {gadget_action} AND [display_when] LIKE {like_dw}
			';
			$params = array();
			$params['gadget'] = 'CustomPage'; 
			$params['gadget_action'] = 'ShowPost('.$pid.')'; 
			$dw_prefix = ($method == 'TempPost' ? 'TEMP' : '');
			$params['like_dw'] = '%{'.$dw_prefix.'GADGET:%';
			if (!is_null($page_gadget)) {
				$params['like_dw'] = '%{'.$dw_prefix.'GADGET:'.$page_gadget.'}%';
				if (!is_null($page_action)) {
					$params['like_dw'] = '%{'.$dw_prefix.'GADGET:'.$page_gadget.'|ACTION:'.$page_action.'}%';
					if (!is_null($page_linkid)) {
						$params['like_dw'] = '%{'.$dw_prefix.'GADGET:'.$page_gadget.'|ACTION:'.$page_action.'('.$page_linkid.')}%';
					}
				}
			}
			
			$gadget = $GLOBALS['db']->queryAll($sql, $params);
			if (Jaws_Error::IsError($gadget)) {
				$res['success'] = false;
				$GLOBALS['app']->Session->PushLastResponse($gadget->GetMessage(), RESPONSE_ERROR);
				$res['message'] = $GLOBALS['app']->Session->PopLastResponse();
				return $res;
			} else if (isset($gadget[0]) && isset($gadget[0]['id'])) {

				$GLOBALS['app']->Session->PushLastResponse(_t('LAYOUT_ELEMENT_ADDED'), RESPONSE_NOTICE);
				
				$id = $gadget[0]['id'];			
				$name_prefix = ($section_name == 'main' || substr($section_name, 0, 7) == 'section' ? 'custom_page-' : '');
				
				$t_item = new Jaws_Template('gadgets/CustomPage/templates/');
				$t_item->Load('LayoutManager.html');
				$t_item->SetBlock('item');
				$t_item->SetVariable('section_id', $name_prefix.$section_name);

				$actions = $GLOBALS['app']->GetGadgetActions($gadget[0]['gadget']);
				$actions = (isset($actions['LayoutAction'])) ? $actions['LayoutAction'] : array();
				if (isset($actions)) {
					$info = $GLOBALS['app']->LoadGadget($gadget[0]['gadget'], 'Info');
					//$t_item->SetVariable('gadget', $info->GetName());
					if (isset($actions[$gadget[0]['gadget_action']]['name'])) {
						//$t_item->SetVariable('action', $actions[$gadget[0]['gadget_action']]['name']);
					} else {
						$layoutGadget = $GLOBALS['app']->LoadGadget($gadget[0]['gadget'], 'LayoutHTML');
						if (method_exists($layoutGadget, 'LoadLayoutActions')) {
							$actions = $layoutGadget->LoadLayoutActions();
						}
						unset($layoutGadget);
					}
					unset($info);
				}
				
				$t_item->SetVariable('pos', $gadget[0]['layout_position']);
				$t_item->SetVariable('icon', $GLOBALS['app']->GetJawsURL() . '/gadgets/'.$gadget[0]['gadget'].'/images/logo.png');
				if (isset($actions[$gadget[0]['gadget_action']])) {
					$t_item->SetVariable('description', $actions[$gadget[0]['gadget_action']]['desc']);
					$t_item->SetVariable('item_status', 'none');
				} else {
					$t_item->SetVariable('description', $gadget[0]['gadget_action']);
					$t_item->SetVariable('item_status', 'line-through');
				}
				unset($actions);

				$t_item->SetVariable('delete-img', $GLOBALS['app']->GetJawsURL() . '/gadgets/Layout/images/delete-item.gif');
				$t_item->SetVariable('delete', 'deleteElement(\''.$id.'\',\''._t('LAYOUT_CONFIRM_DELETE').'\');');
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
				$content = $fakeLayout->PutGadget($gadget[0]['gadget'], $gadget[0]['gadget_action'], $gadget[0]['section']);
				
				// Return new items on the layout
				$items_on_layout = array();
				foreach ($GLOBALS['app']->_ItemsOnLayout as $on_layout) {
					$items_on_layout[] = $on_layout;
				}
				$new_items_on_layout = implode(',',$items_on_layout);
				
				$head_items[$name_prefix.$section_name] .= (!empty($head_items[$name_prefix.$section_name]) ? ',' : '').'"item_'.$id.'":true';
				$edit_action = (strpos($gadget[0]['gadget_action'], '(') !== false ? substr($gadget[0]['gadget_action'], 0, strpos($gadget[0]['gadget_action'], '(')) : $gadget[0]['gadget_action']);
				$edit_id = (strpos($gadget[0]['gadget_action'], '(') !== false ? str_replace(array('(',')'), '', substr($gadget[0]['gadget_action'], strpos($gadget[0]['gadget_action'], '('), strlen($gadget[0]['gadget_action']))) : null);
				$controls_class = '';
				$item_class = '';
				$baseToolbar = array(
					'bold,italic,strikethrough,underline,|,formatselect,|,justifyleft,justifycenter,justifyright,justifyfull',
					'bullist,numlist,|,code,|,undo,redo,|,image,example,unlink,|'
				);			
				if ($edit_action == 'ShowPost') {
					$editor =& $GLOBALS['app']->LoadEditor('CustomPage', 'custom_page-post-text-'.$edit_id, $content, false, '', true, $GLOBALS['app']->GetSiteURL().'/admin.php?gadget=CustomPage&action=SaveEditPost&id='.$edit_id, $inplace_options);
					$editor->SetBaseToolbar(array($baseToolbar));
					//$head_scripts_editable = '<script type="text/javascript">Event.observe(window, "load", function(){if($(\'item_'.$id.'\')){$$(\'#item_'.$id.' .custom_page-post-text\').each(function(element){element.addClassName(\'custom_page-post-text-'.$edit_id.'\');});}});</script>'."\n";
					$head_scripts_editable = '';
					$content = $head_scripts_editable.$editor->Get();
					$editImg = $GLOBALS['app']->GetJawsURL() . '/gadgets/Layout/images/edit-item.gif';
					$edit = "javascript:void(0);\" onclick=\"new_custom_pageposttext_InPlaceRichEditor['".$edit_id."'].enterEditMode();";
					if ($method == 'TempPost') {
						$controls_class = ' temp-controls';
						$item_class = ' item-temp';
					}
				}

				$content = str_replace(array("\r","\n","\t"), '', $content);
				$inputStr = $content;
				$delimeterLeft = "<script language=\"javascript\" type=\"text/javascript\" src=\"data/tinymce/tiny_mce_src.js";
				$delimeterRight = "_options);});</script>";
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
				$t_item->SetVariable('move-edit', '');
				$t_item->SetVariable('edit_style', '');
				$t_item->SetVariable('edit', $edit);
				$t_item->SetVariable('edit-img', $editImg);
				$t_item->SetVariable('controls_style', '');
				$t_item->SetVariable('controls_class', $controls_class);
				$t_item->SetVariable('item_class', $item_class);
				$t_item->ParseBlock('item');
				
				$res['id'] = $id;
				$res['post_id'] = $edit_id;
				$res['saveeditpost_url'] = $GLOBALS['app']->GetSiteURL().'/admin.php?gadget=CustomPage&action=SaveEditPost&id='.$edit_id;
				$res['external_link_list_url'] = $GLOBALS['app']->getSiteURL(). '/admin.php?gadget=Menu&action=TinyMCEMenus';
				$theme = $GLOBALS['app']->GetTheme();
				if (isset($theme['url'])) {
					$theme['url'] .= 'style.css';
					if (file_exists(JAWS_DATA . 'files/css/custom.css')) {
						$theme['url'] = $GLOBALS['app']->GetSiteURL() . '/gz.php?type=css&uri1='.urlencode($theme['url']).'&uri2='.urlencode($GLOBALS['app']->GetDataURL('files/css/custom.css', true));
					}
				}
				$res['content_css_url'] = $theme['url'];
				$res['section'] = $section_name;
				$res['section_name'] = $name_prefix.$section_name;
				$res['gadget_html'] = str_replace("text-decoration: line-through;", '', $t_item->Get());
				$res['items_on_layout'] = $new_items_on_layout;
			}
			
			$res['message'] = $GLOBALS['app']->Session->PopLastResponse();
			return $res;
		}
    }
}