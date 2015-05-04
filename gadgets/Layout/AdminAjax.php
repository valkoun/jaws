<?php
/**
 * Layout AJAX API
 *
 * @category   Ajax
 * @package    Layout
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @copyright  2005-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class LayoutAdminAjax extends Jaws_Ajax
{
    /**
     * Constructor
     *
     * @access  public
     */
    function LayoutAdminAjax(&$model)
    {
        $this->_Model =& $model;
    }

    /**
     * Change items position
     *
     * @access  public
     * @param   int     $item        Item ID
     * @param   mixed   $section     Can be the section referenced by names or by ids
     * @param   int     $pos         Position that will be used, all other positions will be placed under this
     * @param   array   $sortedItems An array with the sorted items of $section. WARNING: keys have the item_ prefix
     * @return  array   Response
     */
    function MoveElement($item, $section, $position, $sortedItems, $page_gadget = null, $page_action = null, $page_linkid = null)
    {
        $this->CheckSession('Layout', 'ManageLayout');

        $res = $this->_Model->MoveElementToSection($item, $section, $position, $sortedItems, $page_gadget, $page_action, $page_linkid);
        if ($res === false) {
            $GLOBALS['app']->Session->PushLastResponse(_t('LAYOUT_ERROR_ELEMENT_MOVED'), RESPONSE_ERROR);
        } else {
            $GLOBALS['app']->Session->PushLastResponse(_t('LAYOUT_ELEMENT_MOVED'), RESPONSE_NOTICE);
        }

        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Deletes an element
     *
     * @access  public
     * @param   int     $item    Item ID
     * @return  array   Response
     */
    function DeleteElement($item)
    {
        $this->CheckSession('Layout', 'ManageLayout');
        $res = $this->_Model->DeleteElement($item);
        if ($res === false) {
            $GLOBALS['app']->Session->PushLastResponse(_t('LAYOUT_ERROR_ELEMENT_DELETED'), RESPONSE_ERROR);
        } else {
            $GLOBALS['app']->Session->PushLastResponse(_t('LAYOUT_ELEMENT_DELETED'), RESPONSE_NOTICE);
        }
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Hides an element
     *
     * @access  public
     * @param   int     $item    Item ID
     * @return  array   Response
     */
    function HideElement($item, $pageGadget, $pageAction, $pageId = null)
    {
        $this->CheckSession('Layout', 'ManageLayout');
        $res = $this->_Model->HideElement($item, $pageGadget, $pageAction, $pageId);
        if ($res === false) {
            $GLOBALS['app']->Session->PushLastResponse(_t('LAYOUT_ERROR_ELEMENT_DELETED'), RESPONSE_ERROR);
        } else {
            $GLOBALS['app']->Session->PushLastResponse(_t('LAYOUT_ELEMENT_DELETED'), RESPONSE_NOTICE);
        }
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Change when to display a gadget
     * 
     * @access  public
     * @param   int     $item   Item ID
     * @param   string  $dw     Display in these gadgets
     * @return  array   Response
     */
    function ChangeDisplayWhen($item, $dw) 
    {
        $this->CheckSession('Layout', 'ManageLayout');
        $res = $this->_Model->ChangeDisplayWhen($item, $dw);
        if ($res === false) {
            $GLOBALS['app']->Session->PushLastResponse(_t('LAYOUT_ERROR_CHANGE_WHEN'), RESPONSE_ERROR);
        } else {
            $GLOBALS['app']->Session->PushLastResponse(_t('LAYOUT_ELEMENT_CHANGE_WHEN'), RESPONSE_NOTICE);
        }
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Get actions of a given gadget
     *
     * @access  public
     * @params  string  $gadget
     * @return  array   Actions of the given gadget
     */
    function GetGadgetActions($gadget)
    {
        $this->CheckSession('Layout', 'ManageLayout');
        return $this->_Model->GetGadgetActions($gadget);
    }

    /**
     * Add gadget to layout 
     *
     * @access  public
     * @params  string  $gadget
     * @params  string  $action
     * @return  array   Details of the added gadget/action
     */
    function AddGadget($gadget, $action, $page_gadget = null, $page_action = null, $page_linkid = null, $items_on_layout = '') 
    {
        $this->CheckSession('Layout', 'ManageLayout');
		require_once JAWS_PATH . 'include/Jaws/Layout.php';
		if (!isset($GLOBALS['app']->Layout)) {
			$GLOBALS['app']->Layout = new Jaws_Layout();
		}
        $res = array();
		$displayWhen = '*';
		$dwlabel = _t('GLOBAL_ALWAYS');
		$page_gadget = ($page_gadget == 'null' ? null : $page_gadget);
		$page_action = ($page_action == 'null' ? null : $page_action);
		$page_linkid = ($page_linkid == 'null' ? null : $page_linkid);
		if (
			Jaws_Gadget::IsGadgetUpdated('CustomPage') && 
			(!is_null($page_gadget) && !is_null($page_action) && !is_null($page_linkid) && 
			!empty($page_gadget) && !empty($page_action) && !empty($page_linkid)) 
		) {
			$GLOBALS['app']->Registry->LoadFile('CustomPage');
			$GLOBALS['app']->Translate->LoadTranslation('CustomPage', JAWS_GADGET);
			if (
				!is_null($page_gadget) && !is_null($page_action) && !is_null($page_linkid) && 
				!empty($page_gadget) && !empty($page_action) && !empty($page_linkid)
			) {
				$displayWhen = '{GADGET:'.$page_gadget.'|ACTION:'.$page_action.'('.$page_linkid.')}';
				$dwlabel = _t('CUSTOMPAGE_THIS_PAGE');
			}
		} else if (!is_null($page_gadget)) {
			$displayWhen = $page_gadget;
			$dwlabel = $page_gadget;
		}
		$id = $this->_Model->NewElement('main', $gadget, $action, '', $displayWhen);
        if ($id === false) {
            $GLOBALS['app']->Session->PushLastResponse(_t('LAYOUT_ERROR_ELEMENT_ADDED'), RESPONSE_ERROR);
            $res['success'] = false;
        } else {
            $gadget = $this->_Model->GetElement($id);
            $GLOBALS['app']->Session->PushLastResponse(_t('LAYOUT_ELEMENT_ADDED'), RESPONSE_NOTICE);
            $name = 'main';
			
			$t_item = new Jaws_Template('gadgets/Layout/templates/');
			$t_item->Load('LayoutManager.html');
			$t_item->SetBlock('item');
			
			$item_gadget = '';
			$item_action = '';
			
			$actions = $GLOBALS['app']->GetGadgetActions($gadget['gadget']);
			$actions = (isset($actions['LayoutAction'])) ? $actions['LayoutAction'] : array();
			if (isset($actions)) {
				$info = $GLOBALS['app']->LoadGadget($gadget['gadget'], 'Info');
				$item_gadget = $info->GetName();
				if (isset($actions[$gadget['gadget_action']]['name'])) {
					$item_action = $actions[$gadget['gadget_action']]['name'];
				} else {
					$layoutGadget = $GLOBALS['app']->LoadGadget($gadget['gadget'], 'LayoutHTML');
					if (method_exists($layoutGadget, 'LoadLayoutActions')) {
						$actions = $layoutGadget->LoadLayoutActions();
						if (isset($actions[$gadget['gadget_action']]['name'])) {
							$item_action = $actions[$gadget['gadget_action']]['name'];
						} else {
							$item_action = $gadget['gadget_action'];
						}
					} else {
						$item_action = $gadget['gadget_action'];
					}
					unset($layoutGadget);
				}
				unset($info);
			} else {
				$item_gadget = $gadget['gadget'];
				$item_action = _t('LAYOUT_ACTIONS');
			}
			if (isset($actions[$gadget['gadget_action']])) {
				$t_item->SetVariable('description', $actions[$gadget['gadget_action']]['desc']);
				$t_item->SetVariable('item_status', 'none');
			} else {
				$t_item->SetVariable('description', $gadget['gadget_action']);
				$t_item->SetVariable('item_status', 'line-through');
			}
			unset($actions);
			
			$t_item->SetVariable('item_id', $id);
			$t_item->SetVariable('section_id', $name);
			$t_item->SetBlock('item/gadget');
			
			$t_item->SetVariable('gadget', $item_gadget);
			$t_item->SetVariable('action', $item_action);
			$t_item->SetVariable('pos', $gadget['layout_position']);
			$t_item->SetVariable('icon', $GLOBALS['app']->GetJawsURL() . '/gadgets/'.$gadget['gadget'].'/images/logo.png');

			$t_item->SetVariable('delete-img', $GLOBALS['app']->GetJawsURL() . '/gadgets/Layout/images/delete-item.gif');
			$t_item->SetVariable('delete', 'deleteElement(\''.$gadget['id'].'\',\''._t('LAYOUT_CONFIRM_DELETE').'\');');
			$t_item->SetVariable('lbl_display_when', _t('LAYOUT_DISPLAY_IN'));
			$t_item->SetVariable('display_when', _t('LAYOUT_ALWAYS'));
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
			
			$edit_action = (strpos($gadget['gadget_action'], '(') !== false ? substr($gadget['gadget_action'], 0, strpos($gadget['gadget_action'], '(')) : $gadget['gadget_action']);
			$edit_id = (strpos($gadget['gadget_action'], '(') !== false ? str_replace(array('(',')'), '', substr($gadget['gadget_action'], strpos($gadget['gadget_action'], '('), strlen($gadget['gadget_action']))) : null);
			if ($edit_action == 'ShowPost') {
				$editor =& $GLOBALS['app']->LoadEditor('CustomPage', 'custom_page-post-text-'.$edit_id, $content, false, '', true, $GLOBALS['app']->GetSiteURL().'/admin.php?gadget=CustomPage&action=SaveEditPost&id='.$edit_id, $inplace_options);
				$editor->SetBaseToolbar(array($baseToolbar));
				$content = $editor->Get();
				$content = str_replace(array("\r","\n","\t"), '', $content);
				$inputStr = $content;
				$delimeterLeft = "<script language=\"javascript\" type=\"text/javascript\" src=\"data/tinymce/tiny_mce.js";
				$delimeterRight = "return value;}</script>";
				$startLeft = strpos($inputStr, $delimeterLeft);
				$posLeft = ($startLeft+strlen($delimeterLeft));
				$posRight = strpos($inputStr, $delimeterRight, $posLeft);
				$toReplace = $delimeterLeft.substr($inputStr, $posLeft, $posRight-$posLeft).$delimeterRight;
				$content = str_replace($toReplace, '', $content);
				$t_item->SetVariable('move-edit', '');
				$t_item->SetVariable('edit_style', " style=\"display: none;\"");
				$editImg = $GLOBALS['app']->GetJawsURL() . '/gadgets/Layout/images/edit-item.gif';
			} else {
				$t_item->SetVariable('move-edit', " item-move-edit");
				$t_item->SetVariable('edit_style', '');
				$editImg = $GLOBALS['app']->GetJawsURL() . '/gadgets/Layout/images/gadget-item.gif';
			}

			$t_item->SetVariable('gadget', $content);
			
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
			$t_item->ParseBlock('item/gadget');
			$t_item->ParseBlock('item');
            $res['success'] = true;
            $res['id'] = $id;
            $res['section'] = $name;
            $res['section_name'] = $name;
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
        $this->CheckSession('Layout', 'ManageLayout');
        $res = $this->_Model->EditElementAction($item, $action);
        if ($res === false) {
            $GLOBALS['app']->Session->PushLastResponse(_t('LAYOUT_ERROR_ELEMENT_UPDATED'), RESPONSE_ERROR);
        } else {
            $GLOBALS['app']->Session->PushLastResponse(_t('LAYOUT_ELEMENT_UPDATED'), RESPONSE_NOTICE);
        }
        return $GLOBALS['app']->Session->PopLastResponse();
    }

	
    /**
     * Save CSS to custom.css file
     * 
     * @access  public
     * @params  string  $data
     * @return  array   Response
     */
    function SaveCSS($data) 
    {
        $this->CheckSession('Layout', 'ManageLayout');
        $res = $this->_Model->SaveCSS($data);
        return $GLOBALS['app']->Session->PopLastResponse();
    }
}
