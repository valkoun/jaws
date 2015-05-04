<?php
/**
 * UrlMapper Core Gadget Admin
 *
 * @category   Gadget
 * @package    UrlMapper
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2006-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class UrlMapperAdminHTML extends Jaws_GadgetHTML
{
    /**
     * Gadget constructor
     *
     * @access   public
     */
    function UrlMapperAdminHTML()
    {
        $this->Init('UrlMapper');
    }

    /**
     * Calls default admin action
     *
     * @access  public
     * @return  string  Template content
     */
    function Admin()
    {
        $this->CheckPermission('ManageUrlMapper');
        return $this->Maps();
    }

    /**
     * Prepares the menubar
     *
     * @access  public
     * @param   string   $action_selected selected action
     * @return  string   Template content
     */
    function MenuBar($action_selected)
    {
        $actions = array('Admin', 'Properties', 'Aliases');
        if (!in_array($action_selected, $actions)) {
            $action_selected = 'Admin';
        }

        require_once JAWS_PATH . 'include/Jaws/Widgets/Menubar.php';
        $menubar = new Jaws_Widgets_Menubar();
        $menubar->AddOption('Admin', _t('URLMAPPER_MAPS'),
                            BASE_SCRIPT . '?gadget=UrlMapper&amp;action=Admin', STOCK_DOCUMENTS);
        $menubar->AddOption('Aliases', _t('URLMAPPER_ALIASES'),
                            BASE_SCRIPT . '?gadget=UrlMapper&amp;action=Aliases', 'gadgets/UrlMapper/images/aliases.png');
        $menubar->AddOption('Properties', _t('GLOBAL_PROPERTIES'),
                            BASE_SCRIPT . '?gadget=UrlMapper&amp;action=Properties', STOCK_PREFERENCES);
        $menubar->Activate($action_selected);
        return $menubar->Get();
    }

    /**
     * Prepares the data (an array) of maps for gadget action
     *
     * @access  public
     * @param   string  $gadget  gadget name
     * @param   string  $action  action name
     * @return  array   Data
     */
    function GetMaps($gadget, $action)
    {
        $model = $GLOBALS['app']->LoadGadget('UrlMapper', 'AdminModel');
        $maps  = $model->GetActionMaps($gadget, $action);
        if (Jaws_Error::IsError($maps)) {
            return array();
        }

        $newData = array();
        foreach($maps as $map) {
            $mapData = array();
            $mapData['map'] = $map['map'];
            $actions = '';
            if ($this->GetPermission('EditMaps')) {
                if ($map['custom']) {
                    $link =& Piwi::CreateWidget('Link', _t('GLOBAL_EDIT'),
                                                "javascript: editMap(this, '".$map['id']."');",
                                                STOCK_EDIT);
                    $actions.= $link->Get().'&nbsp;';
                    $link =& Piwi::CreateWidget('Link', _t('GLOBAL_DELETE'),
                                                "javascript: deleteMap(this, '".$map['id']."');",
                                                STOCK_DELETE);
                    $actions.= $link->Get().'&nbsp;';
                } else {
                    $link =& Piwi::CreateWidget('Link', _t('URLMAPPER_ADD_MAP'),
                                                "javascript: addMap(this, '".$map['id']."');",
                                                STOCK_NEW);
                    $actions.= $link->Get().'&nbsp;';
                }
            }
            $mapData['actions'] = $actions;
            $newData[] = $mapData;
        }
        return $newData;
    }

    /**
     * Prepares a clean template for showing the maps
     *
     * @access  public
     * @return  string  XHTML of Datagrid
     */
    function MapsDatagrid()
    {
        $datagrid =& Piwi::CreateWidget('DataGrid', array());
        $datagrid->setID('maps_datagrid');

        $datagrid->addColumn(Piwi::CreateWidget('Column', _t('URLMAPPER_MAPS'), null, false));
        $colActions = Piwi::CreateWidget('Column', _t('GLOBAL_ACTIONS'), null, false);
        $colActions->SetStyle('width: 80px; white-space:nowrap;');
        $datagrid->addColumn($colActions);

        $datagrid->SetStyle('margin-top: 0px; width: 100%;');
        return $datagrid->Get();
    }

    /**
     * Returns the Maps UI
     *
     * @access  public
     * @return  string  Template content
     */
    function Maps()
    {
        $this->AjaxMe('script.js');
        $tpl = new Jaws_Template('gadgets/UrlMapper/templates/');
        $tpl->Load('Maps.html');
        $tpl->SetBlock('UrlMapper');

        // Menubar
        $tpl->SetVariable('menubar', $this->MenuBar('Admin'));

        //Combo for gadgets
        $model = $GLOBALS['app']->LoadGadget('Jms', 'AdminModel');
        $gadgets = $model->GetGadgetsList(null, true, true, true);
        $comboGadgets =& Piwi::CreateWidget('Combo', 'gadgets_combo');
        $comboGadgets->SetStyle('width: 200px;');
        foreach($gadgets as $gadget) {
            $comboGadgets->AddOption($gadget['name'], $gadget['realname']);
        }
        $comboGadgets->AddEvent(ON_CHANGE, 'javascript: rebuildActionCombo();');
        $tpl->SetVariable('lbl_gadgets', _t('GLOBAL_GADGETS'));
        $tpl->SetVariable('combo_gadgets', $comboGadgets->Get());

        //Combo for actions
        $comboActions =& Piwi::CreateWidget('Combo', 'actions_combo');
        $comboActions->SetSize(20);
        $comboActions->SetStyle('width: 200px; height: 280px;');
        $comboActions->AddEvent(ON_CHANGE, 'javascript: showActionMaps();');
        $tpl->SetVariable('lbl_actions', _t('GLOBAL_GADGET_ACTIONS'));
        $tpl->SetVariable('combo_actions', $comboActions->Get());

        $tpl->SetVariable('lbl_maps',    _t('URLMAPPER_MAPS'));
        $tpl->SetVariable('datagrid', $this->MapsDatagrid());

        //route entry
        $routeEntry =& Piwi::CreateWidget('Entry', 'map_route', '');
        $routeEntry->SetStyle('direction: ltr; width: 200px;');
        $routeEntry->SetEnabled(false);
        $tpl->SetVariable('map_route', _t('URLMAPPER_MAPS_ROUTE'));
        $tpl->SetVariable('map_route_entry', $routeEntry->Get());

        // map regexp
        $regexpEntry =& Piwi::CreateWidget('Entry', 'map_regexp', '');
        $regexpEntry->SetStyle('direction: ltr; width: 200px;');
        $regexpEntry->SetEnabled(false);
        $tpl->SetVariable('map_regexp', _t('URLMAPPER_MAPS_REGEXP'));
        $tpl->SetVariable('map_regexp_entry', $regexpEntry->Get());

        // map extension
        $extEntry =& Piwi::CreateWidget('Entry', 'map_ext', '');
        $extEntry->SetStyle('direction: ltr; width: 200px;');
        $extEntry->SetEnabled(false);
        $tpl->SetVariable('map_ext', _t('URLMAPPER_MAPS_EXTENSION'));
        $tpl->SetVariable('map_ext_entry', $extEntry->Get());

        $btnCancel =& Piwi::CreateWidget('Button', 'btn_cancel', _t('GLOBAL_CANCEL'), STOCK_CANCEL);
        $btnCancel->SetEnabled(false);
        $btnCancel->AddEvent(ON_CLICK, "javascript: enableMapEditingArea(false);");
        $tpl->SetVariable('btn_cancel', $btnCancel->Get());

        $btnSave =& Piwi::CreateWidget('Button', 'btn_save', _t('GLOBAL_SAVE'), STOCK_SAVE);
        $btnSave->SetEnabled(false);
        $btnSave->AddEvent(ON_CLICK, "javascript: saveMap();");
        $tpl->SetVariable('btn_save', $btnSave->Get());

        $tpl->SetVariable('addMap_title',     _t('URLMAPPER_MAPS_ADD_TITLE'));
        $tpl->SetVariable('editMap_title',    _t('URLMAPPER_MAPS_EDIT_TITLE'));
        $tpl->SetVariable('confirmMapDelete', _t('URLMAPPER_MAPS_CONFIRM_DELETE'));

        $tpl->ParseBlock('UrlMapper');
        return $tpl->Get();
    }

    /**
     * Prepares the aliases UI
     *
     * @access  public
     * @return  string  XHTML template
     */
    function Aliases()
    {
        $this->AjaxMe('script.js');
        $tpl = new Jaws_Template('gadgets/UrlMapper/templates/');
        $tpl->Load('Aliases.html');
        $tpl->SetBlock('Aliases');
        $tpl->SetVariable('base_script', BASE_SCRIPT);

        $form =& Piwi::CreateWidget('Form', BASE_SCRIPT, 'post');
        $form->Add(Piwi::CreateWidget('HiddenEntry', 'alias_id', '-'));

        include_once JAWS_PATH . 'include/Jaws/Widgets/FieldSet.php';
        $fieldset = new Jaws_Widgets_FieldSet(_t('URLMAPPER_ALIASES_ALIAS'));
        $fieldset->SetDirection('vertical');
        $fieldset->SetID('AliasFieldset');
        $fieldset->SetStyle('width: 300px; min-width: 250px; max-width: 300px;');

        $customUrl =& Piwi::CreateWidget('Entry', 'custom_url');
        $customUrl->SetTitle(_t('URLMAPPER_ALIASES_LINK'));
        $customUrl->SetId('custom_url');
        $customUrl->SetStyle('width: 230px;');
        $fieldset->Add($customUrl);

        $alias =& Piwi::CreateWidget('Entry', 'alias', '', _t('URLMAPPER_ALIASES_ALIAS'));
        $alias->SetId('alias');
        $alias->SetStyle('width: 230px;');
        $fieldset->Add($alias);

        $hbox =& Piwi::CreateWidget('HBox');
        $hbox->SetStyle(_t('GLOBAL_LANG_DIRECTION')=='rtl'?'float: left;' : 'float: right;'); //hig style
        $delete =& Piwi::CreateWidget('Button', 'delete', _t('GLOBAL_DELETE'), STOCK_DELETE);
        $delete->AddEvent(ON_CLICK, 'deleteCurrentAlias();');
        $delete->SetId('delete_button');
        $delete->SetStyle('visibility: hidden;');
        $hbox->Add($delete);
        $cancel =& Piwi::CreateWidget('Button', 'cancel', _t('GLOBAL_CANCEL'), STOCK_CANCEL);
        $cancel->AddEvent(ON_CLICK, 'stopAction();');
        $cancel->SetId('cancel_button');
        $hbox->Add($cancel);
        $save =& Piwi::CreateWidget('Button', 'save', _t('GLOBAL_SAVE'), STOCK_SAVE);
        $save->SetId('save_button');
        $save->AddEvent(ON_CLICK, 'javascript: saveAlias();');

        $hbox->Add($save);

        $form->Add($fieldset);
        $form->Add($hbox);

        $tpl->SetVariable('edit_form', $form->Get());
        $tpl->SetVariable('menubar', $this->MenuBar('Aliases'));

        $model = $GLOBALS['app']->LoadGadget('UrlMapper', 'AdminModel');
        $aliases = $model->GetAliases();

        foreach($aliases as $alias) {
            $tpl->SetBlock('Aliases/alias');
            $tpl->SetVariable('id', $alias['id']);
            $tpl->SetVariable('alias_value', $alias['alias_url']);
            $tpl->ParseBlock('Aliases/alias');
        }

        $tpl->ParseBlock('Aliases');
        return $tpl->Get();
    }

    /**
     * Prepares the view for properties
     *
     * @access  public
     * @return  string  XHTML template
     */
    function Properties()
    {
        $this->AjaxMe('script.js');
        $tpl = new Jaws_Template('gadgets/UrlMapper/templates/');
        $tpl->Load('Properties.html');
        $tpl->SetBlock('Properties');

        $form =& Piwi::CreateWidget('Form', BASE_SCRIPT, 'POST');

        include_once JAWS_PATH . 'include/Jaws/Widgets/FieldSet.php';
        $fieldset = new Jaws_Widgets_FieldSet(_t('GLOBAL_PROPERTIES'));
        $fieldset->SetDirection('vertical');

        $useMapCombo =& Piwi::CreateWidget('Combo', 'enabled');
        $useMapCombo->SetTitle(_t('URLMAPPER_SETTINGS_MAP_ENABLED'));
        $useMapCombo->AddOption(_t('GLOBAL_YES'), 'true');
        $useMapCombo->AddOption(_t('GLOBAL_NO'), 'false');
        $useMapCombo->SetDefault($GLOBALS['app']->Registry->Get('/map/enabled'));

        $precedence =& Piwi::CreateWidget('Combo', 'custom_precedence');
        $precedence->SetTitle(_t('URLMAPPER_CUSTOM_MAP_PRECEDENCE'));
        $precedence->AddOption(_t('GLOBAL_YES'), 'true');
        $precedence->AddOption(_t('GLOBAL_NO'), 'false');
        $precedence->SetDefault($GLOBALS['app']->Registry->Get('/map/custom_precedence'));

        $useAliasesCombo =& Piwi::CreateWidget('Combo', 'use_aliases');
        $useAliasesCombo->SetTitle(_t('URLMAPPER_SETTINGS_MAP_USE_ALIASES'));
        $useAliasesCombo->AddOption(_t('GLOBAL_YES'), 'true');
        $useAliasesCombo->AddOption(_t('GLOBAL_NO'), 'false');
        $useAliasesCombo->SetDefault($GLOBALS['app']->Registry->Get('/map/use_aliases'));

        $extension =& Piwi::CreateWidget('Entry', 'extension',
                                         $GLOBALS['app']->Registry->Get('/map/extensions'));
        $extension->SetTitle(_t('URLMAPPER_SETTINGS_MAP_EXTENSION'));
        $extension->SetStyle('direction: ltr;');

        $fieldset->Add($useMapCombo);
        $fieldset->Add($useAliasesCombo);
        $fieldset->Add($precedence);
        $fieldset->Add($extension);

        $save =& Piwi::CreateWidget('Button', 'save', _t('GLOBAL_SAVE'), STOCK_SAVE);
        $save->AddEvent(ON_CLICK, 'javascript: updateProperties(this.form);');

        $buttonbox =& Piwi::CreateWidget('HBox');
        $buttonbox->SetStyle(_t('GLOBAL_LANG_DIRECTION')=='rtl'?'float: left;' : 'float: right;');
        $buttonbox->PackStart($save);

        $form->Add($fieldset);
        $form->Add($buttonbox);


        $tpl->SetVariable('menubar', $this->MenuBar('Properties'));
        $tpl->SetVariable('form', $form->Get());
        $tpl->ParseBlock('Properties');
        return $tpl->Get();
    }
}