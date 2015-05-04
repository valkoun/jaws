<?php
/**
 * Menu Admin Gadget
 *
 * @category   GadgetAdmin
 * @package    Menu
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Jon Wood <jon@substance-it.co.uk>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2004-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class MenuAdminHTML extends Jaws_GadgetHTML
{
    /**
     * Public constructor
     *
     * @access  public
     */
    function MenuAdminHTML()
    {
        $this->Init('Menu');
    }

    /**
     * Displays gadget administration section
     *
     * @access  public
     * @return  string HTML Template content
     */
    function Admin()
    {
        $this->CheckPermission('default');
        $this->AjaxMe('script.js');

        $tpl = new Jaws_Template('gadgets/Menu/templates/');
        $tpl->Load('AdminMenu.html');
        $tpl->SetBlock('menus');
        $tpl->SetBlock('menus/menus_base');

        $tpl->SetVariable('menus_trees', $this->GetMenusTrees());
        $add_btn =& Piwi::CreateWidget('Button','btn_add', _t('MENU_ADD_GROUP'), STOCK_NEW);
        $add_btn->AddEvent(ON_CLICK, 'javascript: addGroup();');
        $tpl->SetVariable('add', $add_btn->Get());

        $save_btn =& Piwi::CreateWidget('Button','btn_save', _t('GLOBAL_SAVE'), STOCK_SAVE);
        $save_btn->SetStyle('display: none;');
        $save_btn->AddEvent(ON_CLICK, 'javascript: saveMenus();');
        $tpl->SetVariable('save', $save_btn->Get());

        $del_btn =& Piwi::CreateWidget('Button','btn_del', _t('GLOBAL_DELETE'), STOCK_DELETE);
        $del_btn->SetStyle('display: none;');
        $del_btn->AddEvent(ON_CLICK, 'javascript: delMenus();');
        $tpl->SetVariable('del', $del_btn->Get());

        $cancel_btn =& Piwi::CreateWidget('Button','btn_cancel', _t('GLOBAL_CANCEL'), STOCK_CANCEL);
        $cancel_btn->SetStyle('display: none;');
        $cancel_btn->AddEvent(ON_CLICK, 'javascript: stopAction();');
        $tpl->SetVariable('cancel', $cancel_btn->Get());

        $tpl->SetVariable('menu_tree_image', $GLOBALS['app']->GetJawsURL() . '/gadgets/Menu/images/menu-item.png');
        $tpl->SetVariable('menu_tree_title', _t('MENU_TREE_TITLE'));
        $tpl->SetVariable('addMenuTitle',    _t('MENU_ADD_MENU'));
        $tpl->SetVariable('editMenuTitle',   _t('MENU_EDIT_MENU'));
        $tpl->SetVariable('delMenuTitle',    _t('MENU_DELETE_MENU'));
        $tpl->SetVariable('addGroupTitle',   _t('MENU_ADD_GROUP'));
        $tpl->SetVariable('editGroupTitle',  _t('MENU_EDIT_GROUP'));
        $tpl->SetVariable('delGroupTitle',   _t('MENU_DELETE_GROUP'));
        $tpl->SetVariable('menuImageSrc',    $GLOBALS['app']->GetJawsURL() . '/gadgets/Menu/images/menu-item.png');
        $tpl->SetVariable('incompleteFields',   _t('MENU_INCOMPLETE_FIELDS'));
        $tpl->SetVariable('confirmDeleteGroup', _t('MENU_CONFIRM_DELETE_GROUP'));
        $tpl->SetVariable('confirmDeleteMenu',  _t('MENU_CONFIRM_DELETE_MENU'));

        $tpl->ParseBlock('menus/menus_base');
        $tpl->ParseBlock('menus');
        return $tpl->Get();
    }

    function GetMenuLevel(&$model, &$tpl_str, $gid, $pid)
    {
        $menus = $model->GetLevelsMenus($pid, $gid);
        if (Jaws_Error::IsError($menus) || empty($menus)) return '';

        $tpl = new Jaws_Template();
        $tpl->LoadFromString($tpl_str);
        $tpl->SetBlock('parent');
        foreach ($menus as $menu) {
            $tpl->SetBlock('parent/menu');
            $tpl->SetVariable('class_name', 'menu_levels');
            $tpl->SetVariable('mg_id', 'menu_'.$menu['id']);
            $tpl->SetVariable('icon', $GLOBALS['app']->GetJawsURL() . '/gadgets/Menu/images/menu-item.png');
            $tpl->SetVariable('title', $menu['title']);
            $tpl->SetVariable('js_edit_func', "editMenu({$menu['id']})");
            $tpl->SetVariable('add_title', _t('MENU_ADD_MENU'));
            $tpl->SetVariable('add_icon', STOCK_NEW);
            $tpl->SetVariable('js_add_func', "addMenu($gid, {$menu['id']})");
            $tpl->SetVariable('sub_menus', $this->GetMenuLevel($model, $tpl_str, $gid, $menu['id']));
            $tpl->ParseBlock('parent/menu');
        }
        $tpl->ParseBlock('parent');
        return $tpl->Get();
    }

    /**
     * Providing a treeview of menus and gadgtes
     *
     * @access  public
     * @return  string HTML Template content
     */
    function GetMenusTrees()
    {
        $tpl = new Jaws_Template('gadgets/Menu/templates/');
        $tpl->Load('AdminMenu.html');
        $tpl->SetBlock('menus');

        $model = $GLOBALS['app']->LoadGadget('Menu', 'AdminModel');
        $groups = $model->GetGroups();
        foreach ($groups as $group) {
            $tpl->SetBlock('menus/menus_tree');
            $tpl_str = '<!-- BEGIN parent --><!-- BEGIN menu -->'.$tpl->GetCurrentBlockContent().'<!-- END menu --><!-- END parent -->';
            $tpl->SetVariable('class_name', 'menu_groups');
            $tpl->SetVariable('mg_id', 'group_'.$group['id']);
            $tpl->SetVariable('icon', $GLOBALS['app']->GetJawsURL() . '/gadgets/Menu/images/menu-group.png');
            $tpl->SetVariable('title', $group['title']);
            $tpl->SetVariable('js_edit_func', "editGroup({$group['id']})");
            $tpl->SetVariable('add_title', _t('MENU_ADD_MENU'));
            $tpl->SetVariable('add_icon', STOCK_NEW);
            $tpl->SetVariable('js_add_func', "addMenu({$group['id']}, 0)");
            $tpl->SetVariable('sub_menus',  $this->GetMenuLevel($model, $tpl_str, $group['id'], 0));
            $tpl->ParseBlock('menus/menus_tree');
        }

        $tpl->ParseBlock('menus');
        return $tpl->Get();
    }

    /**
     * Show a form to edit a given group
     *
     * @access  public
     * @return  string HTML content
     */
    function GetGroupUI()
    {
        $this->CheckPermission('default');
        $tpl = new Jaws_Template('gadgets/Menu/templates/');
        $tpl->Load('AdminMenu.html');
        $tpl->SetBlock('menus');
        $tpl->SetBlock('menus/GroupsUI');

        $tpl->SetVariable('lbl_title', _t('GLOBAL_TITLE'));
        $titleEntry =& Piwi::CreateWidget('Entry', 'title', '');
        $titleEntry->SetStyle('width: 300px; margin-top:2px; margin-bottom:5px;');
        $tpl->SetVariable('title', $titleEntry->Get());

        $titleview =& Piwi::CreateWidget('Combo', 'title_view');
        $titleview->SetID('title_view');
        $titleview->setStyle('width: 96px; margin-top:2px; margin-bottom:5px;');
        $titleview->AddOption(_t('GLOBAL_NO'),  '0');
        $titleview->AddOption(_t('GLOBAL_YES'), '1');
        $tpl->SetVariable('lbl_title_view', _t('MENU_GROUPS_TITLE_VIEW'));
        $tpl->SetVariable('title_view', $titleview->Get());

        $tpl->SetVariable('lbl_visible', _t('GLOBAL_VISIBLE'));
        $visibleType =& Piwi::CreateWidget('Combo', 'visible');
        $visibleType->SetID('visible');
        $visibleType->SetStyle('width: 96px; margin-top:2px; margin-bottom:5px;');
        $visibleType->AddOption(_t('GLOBAL_NO'),  '0');
        $visibleType->AddOption(_t('GLOBAL_YES'), '1');
        $visibleType->SetDefault('1');
        $tpl->SetVariable('visible', $visibleType->Get());

        $tpl->ParseBlock('menus/GroupsUI');
        $tpl->ParseBlock('menus');
        return $tpl->Get();
    }

    /**
     * Show a form to edit a given group
     *
     * @access  public
     * @return  string HTML content
     */
    function GetMenuUI()
    {
        $this->CheckPermission('default');
        $tpl = new Jaws_Template('gadgets/Menu/templates/');
        $tpl->Load('AdminMenu.html');
        $tpl->SetBlock('menus');
        $tpl->SetBlock('menus/MenusUI');

        $model = $GLOBALS['app']->LoadGadget('Menu', 'AdminModel');
        $groups = $model->GetGroups();
        $groupCombo =& Piwi::CreateWidget('Combo', 'gid');
        $groupCombo->SetID('gid');
        $groupCombo->setStyle('width: 256px;');
        foreach ($groups as $group) {
            $groupCombo->AddOption($group['title'], $group['id']);
        }
        $groupCombo->AddEvent(ON_CHANGE, 'changeMenuGroup(this.value);');
        $tpl->SetVariable('lbl_gid', _t('MENU_GROUP'));
        $tpl->SetVariable('gid', $groupCombo->Get());

        $parentCombo =& Piwi::CreateWidget('Combo', 'pid');
        $parentCombo->SetID('pid');
        $parentCombo->setStyle('width: 256px;');
        $parentCombo->AddEvent(ON_CHANGE, 'changeMenuParent(this.value);');
        $tpl->SetVariable('lbl_pid', _t('MENU_PARENT'));
        $tpl->SetVariable('pid', $parentCombo->Get());

        $typeCombo =& Piwi::CreateWidget('Combo', 'type');
        $typeCombo->SetID('type');
        $typeCombo->setStyle('width: 256px;');
        $typeCombo->AddOption(_t('GLOBAL_URL'), 'url');
        $jms = $GLOBALS['app']->LoadGadget('Jms', 'AdminModel');
        $gadgets = $jms->GetGadgetsList(null, true, true);
        foreach ($gadgets as $gadget) {
            if (false !== $GLOBALS['app']->loadHook($gadget['realname'], 'URLList')) {
                $typeCombo->AddOption($gadget['name'], $gadget['realname']);
            }
        }

        $typeCombo->AddEvent(ON_CHANGE, 'changeType(this.value);');
        $tpl->SetVariable('lbl_type', _t('MENU_TYPE'));
        $tpl->SetVariable('type', $typeCombo->Get());

        $rfcCombo =& Piwi::CreateWidget('Combo', 'references');
        $rfcCombo->SetID('references');
        $rfcCombo->setStyle('width: 256px;');
        $rfcCombo->AddEvent(ON_CHANGE, 'changeReferences();');
        $tpl->SetVariable('lbl_references', _t('MENU_REFERENCES'));
        $tpl->SetVariable('references', $rfcCombo->Get());

        $tpl->SetVariable('lbl_title', _t('GLOBAL_TITLE'));
        $titleEntry =& Piwi::CreateWidget('Entry', 'title', '');
        $titleEntry->SetStyle('width: 256px;');
        $tpl->SetVariable('title', $titleEntry->Get());

        $tpl->SetVariable('lbl_url', _t('GLOBAL_URL'));
        $urlEntry =& Piwi::CreateWidget('Entry', 'url', 'http://');
        $urlEntry->SetStyle('direction: ltr;width: 256px;');
        $tpl->SetVariable('url', $urlEntry->Get());

        $targetType =& Piwi::CreateWidget('Combo', 'url_target');
        $targetType->SetID('url_target');
        $targetType->setStyle('width: 128px;');
        $targetType->AddOption(_t('MENU_TARGET_SELF'),  0);
        $targetType->AddOption(_t('MENU_TARGET_BLANK'), 1);
        $tpl->SetVariable('lbl_url_target', _t('MENU_TARGET'));
        $tpl->SetVariable('url_target', $targetType->Get());

        $rank =& Piwi::CreateWidget('Combo', 'rank');
        $rank->SetID('rank');
        $rank->setStyle('width: 128px;');
        $tpl->SetVariable('lbl_rank', _t('MENU_RANK'));
        $tpl->SetVariable('rank', $rank->Get());

        $tpl->SetVariable('lbl_visible', _t('GLOBAL_VISIBLE'));
        $visibleType =& Piwi::CreateWidget('Combo', 'visible');
        $visibleType->SetID('visible');
        $visibleType->SetStyle('width: 128px;');
        $visibleType->AddOption(_t('GLOBAL_NO'),  '0');
        $visibleType->AddOption(_t('GLOBAL_YES'), '1');
        $visibleType->SetDefault('1');
        $tpl->SetVariable('visible', $visibleType->Get());

        $tpl->ParseBlock('menus/MenusUI');
        $tpl->ParseBlock('menus');
        return $tpl->Get();
    }

    /**
     * Returns a list with all the menus in a TinyMCE struct
     *
     * @access  public
     * @return  array  Array with all the available menus and Jaws_Error on error
     */
    function TinyMCEMenus()
    {        
        $model = $GLOBALS['app']->LoadGadget('Menu', 'Model');
		$menus = $model->GetMenus(null, 0, false);
        $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		
		if (Jaws_Error::IsError($menus)) {
            return $menus;
		} else {
			$output = '';
			// for eye candy... code gets new lines
			$delimiter = "\n"; 
			$output .= 'var tinyMCELinkList = new Array(';
			$i = 0;
			foreach ($menus as $m => $menu) {
				$output .= ($i > 0 ? ','.$delimiter : '').'["'
					. $xss->parse($menu['menu_type']).' : '.$xss->parse($menu['title'])
					. '", "'
					. ($menu['url_target'] == 0 ? $xss->parse($menu['url']) : $xss->parse($GLOBALS['app']->GetSiteURL().'/'.$menu['url']))
					. '"]';
				$i++;
			}
			// remove last comma from array item list (breaks some browsers)
		}

		$output .= ');';

		// Make output a real JavaScript file!
		header('Content-type: text/javascript'); 

		echo $output;
	}
}