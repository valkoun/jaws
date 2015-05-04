<?php
/**
 * Visit Counter Gadget Admin
 *
 * @category   GadgetAdmin
 * @package    VisitCounter
 * @author     Jorge A Gallegos <kad@gulags.org.mx>
 * @author     Jon Wood <jon@jellybob.co.uk>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2004-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class VisitCounterAdminHTML extends Jaws_GadgetHTML
{
    /**
     * Constructor
     *
     * @access public
     */
    function VisitCounterAdminHTML()
    {
        $this->Init('VisitCounter');
    }

    /**
     * Creates the menubar
     *
     * @access private
     * @return string  Menubar HTML content
     */
    function MenuBar($selected)
    {
        $actions = array('Admin', 'ResetCounter', 'CleanEntries');

        if (!in_array($selected, $actions)) {
            $selected = 'Admin';
        }

        require_once JAWS_PATH . 'include/Jaws/Widgets/Menubar.php';
        $menubar = new Jaws_Widgets_Menubar();
        if ($this->GetPermission('default')) {
            $menubar->AddOption('Admin', _t('VISITCOUNTER_ADMIN_ACTION'), '');
        }

        if ($this->GetPermission('ResetCounter')) {
            $menubar->AddOption('ResetCounter', _t('VISITCOUNTER_RESET_COUNTER_ACTION'),
                                "javascript: if (confirm('"._t("VISITCOUNTER_RESET_COUNTER_CONFIRM")."')) ".
                                "resetCounter(); return false;");
        }

        if ($this->GetPermission('CleanEntries')) {
            $menubar->AddOption('CleanEntries', _t('VISITCOUNTER_CLEAN_COUNTER'),
                                "javascript: if (confirm('"._t("VISITCOUNTER_CLEAN_COUNTER_CONFIRM")."')) ".
                                "cleanEntries(); return false;");
        }
        $menubar->Activate($selected);

        return $menubar->Get();
    }

    /**
     * Get a list of visits
     *
     * @access  public
     * @param   int     $limit  Limit
     * @return  array   Data
     */
    function GetVisits($limit = 0)
    {
        $model  = $GLOBALS['app']->LoadGadget('VisitCounter', 'AdminModel');
        $visits = $model->GetVisitors($limit);
        if (Jaws_Error::IsError($visits)) {
            return array();
        }

        $newData = array();
        $date = $GLOBALS['app']->loadDate();
        foreach($visits as $visit) {
            $visitData = array();
            $visitData['ip']     = $visit['ip'];
            $visitData['date']   = $date->Format($visit['visit_date'],'Y-m-d H:i:s');
            $visitData['visits'] = $visit['visits'];

            $newData[] = $visitData;
        }
        return $newData;

    }

    /**
     * Creates the datagrid
     *
     * @access  public
     * @param   string $mode  ViewAll or ViewRecent
     * @return  string XHTML of datagrid
     */
    function DataGrid()
    {
        $model = $GLOBALS['app']->LoadGadget('VisitCounter', 'AdminModel');
        $total = $model->TotalOfData('ipvisitor', 'ip');

        $datagrid =& Piwi::CreateWidget('DataGrid', array());
        $datagrid->TotalRows($total);
        $datagrid->pageBy(15);
        $datagrid->SetID('visitcounter_datagrid');
        $datagrid->SetStyle('width: 100%; margin-top: 0.1em;');
        $datagrid->AddColumn(Piwi::CreateWidget('Column', _t('VISITCOUNTER_IP')));
        $datagrid->AddColumn(Piwi::CreateWidget('Column', _t('VISITCOUNTER_DATE')));
        $datagrid->AddColumn(Piwi::CreateWidget('Column', _t('VISITCOUNTER_VISITS')));

        return $datagrid->Get();
    }

    /**
     * Displays the administration page.
     *
     * @access public
     * @return string HTML content
     */
    function Admin()
    {
        $this->CheckPermission('default');
        $this->AjaxMe('script.js');

        $model = $GLOBALS['app']->LoadGadget('VisitCounter', 'AdminModel');
        $num_online       = $model->GetOnlineVisitors();
        $uniqueToday      = $model->GetTodayVisitors('unique');
        $impressionsToday = $model->GetTodayVisitors('impressions');
        $uniqueTotal      = $model->GetTotalVisitors('unique');
        $impressionsTotal = $model->GetTotalVisitors('impressions');
        $startDate        = $model->GetStartDate();

        $tpl = new Jaws_Template('gadgets/VisitCounter/templates/');
        $tpl->Load('AdminVisitCounter.html');
        $tpl->SetBlock('visitcounter');

        $tpl->SetVariable('grid', $this->DataGrid());
        //Ok, the config..
        if ($this->GetPermission('UpdateProperties')) {
            $config_form =& Piwi::CreateWidget('Form', BASE_SCRIPT, 'post');
            $config_form->Add(Piwi::CreateWidget('HiddenEntry', 'gadget', 'VisitCounter'));
            $config_form->Add(Piwi::CreateWidget('HiddenEntry', 'action', 'UpdateProperties'));

            include_once JAWS_PATH . 'include/Jaws/Widgets/FieldSet.php';
            $fieldset_config = new Jaws_Widgets_FieldSet(_t('VISITCOUNTER_PROPERTIES'));
            $fieldset_config->SetDirection('vertical');
            $fieldset_config->SetStyle('white-space: nowrap; width: 228px;');

            $visit_counters = explode(',', $GLOBALS['app']->Registry->get('/gadgets/VisitCounter/visit_counters'));
            $check_counters =& Piwi::CreateWidget('CheckButtons', 'c_kind', 'vertical');
            $check_counters->SetTitle(_t('VISITCOUNTER_DISPLAY_COUNTER'));
            $check_counters->AddOption(_t('VISITCOUNTER_ONLINE_VISITORS'), '0', null, in_array('online', $visit_counters));
            $check_counters->AddOption(_t('VISITCOUNTER_TODAY_VISITORS'),  '1', null, in_array('today',  $visit_counters));
            $check_counters->AddOption(_t('VISITCOUNTER_TOTAL_VISITORS'),  '2', null, in_array('total',  $visit_counters));
            $check_counters->AddOption(_t('VISITCOUNTER_CUSTOM_VISITORS'), '3', null, in_array('custom', $visit_counters));
            $fieldset_config->Add($check_counters);

            $type =& Piwi::CreateWidget('Combo', 'type');
            $type->SetTitle(_t('VISITCOUNTER_TYPE'));
            $type->AddOption(_t('VISITCOUNTER_UNIQUE'), 'unique');
            $type->AddOption(_t('VISITCOUNTER_BY_IMPRESSIONS'), 'impressions');
            $type->SetDefault($model->GetVisitType());
            $fieldset_config->Add($type);

            $period =& Piwi::CreateWidget('Combo', 'period');
            $period->SetTitle(_t('VISITCOUNTER_COOKIE_PERIOD'));
            for ($i = 0; $i <= 15; $i +=1 ) {
                $period->AddOption($i, $i);
            }
            $period->SetDefault($model->GetCookiePeriod());
            $fieldset_config->Add($period);

            $mode =& Piwi::CreateWidget('Combo', 'mode');
            $mode->SetTitle(_t('VISITCOUNTER_MODE'));
            $mode_reg = $GLOBALS['app']->Registry->get('/gadgets/VisitCounter/mode');
            $mode->AddOption(_t('VISITCOUNTER_MODE_TEXT'), 'text');
            $mode->AddOption(_t('VISITCOUNTER_MODE_IMAGE'), 'image');
            $mode->SetDefault($mode_reg);
            $mode->SetId('custom');
            $fieldset_config->Add($mode);

            $custom_reg = stripslashes($GLOBALS['app']->Registry->get('/gadgets/VisitCounter/custom_text'));
            $customText =& Piwi::CreateWidget('Entry', 'custom_text');
            $customText->SetTitle(_t('VISITCOUNTER_CUSTOM_TEXT'));
            $customText->SetValue($custom_reg);
            $customText->SetStyle('width: 200px;');
            $fieldset_config->Add($customText);

            $config_form->Add($fieldset_config);
            $submit_config =& Piwi::CreateWidget('Button', 'saveproperties',
                                                 _t('VISITCOUNTER_UPDATE_PROPS'), STOCK_SAVE);
            $submit_config->SetStyle(_t('GLOBAL_LANG_DIRECTION')=='rtl'?'float: left;' : 'float: right;');
            $submit_config->AddEvent(ON_CLICK, 'javascript: updateProperties(this.form);');
            $config_form->Add($submit_config);

            //$tpl->SetVariable('menubar', $this->menubar(''));
            $tpl->SetVariable('config_form', $config_form->Get());
        }

        //Stats..
        $tpl->SetVariable('visitor_stats', _t('VISITCOUNTER_VISITOR_STATS'));
        $tpl->SetBlock('visitcounter/item');
        $tpl->SetVariable('label', _t('VISITCOUNTER_STATS_FROM'));
        $date = $GLOBALS['app']->loadDate();
        $tpl->SetVariable('value',  $date->Format($startDate,'Y-m-d'));
        $tpl->SetVariable('item_id', 'stats_from');
        $tpl->ParseBlock('visitcounter/item');
        $tpl->SetBlock('visitcounter/item');
        $tpl->SetVariable('label', _t('VISITCOUNTER_ONLINE_VISITORS'));
        $tpl->SetVariable('value', $num_online);
        $tpl->SetVariable('item_id', 'visitors');
        $tpl->ParseBlock('visitcounter/item');
        $tpl->SetBlock('visitcounter/item');
        $tpl->SetVariable('label', _t('VISITCOUNTER_TODAY_UNIQUE_VISITORS'));
        $tpl->SetVariable('value', $uniqueToday);
        $tpl->SetVariable('item_id', 'impressions');
        $tpl->ParseBlock('visitcounter/item');
        $tpl->SetBlock('visitcounter/item');
        $tpl->SetVariable('label', _t('VISITCOUNTER_TODAY_PAGE_IMPRESSIONS'));
        $tpl->SetVariable('value', $impressionsToday);
        $tpl->SetVariable('item_id', 'impressions');
        $tpl->ParseBlock('visitcounter/item');
        $tpl->SetBlock('visitcounter/item');
        $tpl->SetVariable('label', _t('VISITCOUNTER_TOTAL_UNIQUE_VISITORS'));
        $tpl->SetVariable('value', $uniqueTotal);
        $tpl->SetVariable('item_id', 'impressions');
        $tpl->ParseBlock('visitcounter/item');
        $tpl->SetBlock('visitcounter/item');
        $tpl->SetVariable('label', _t('VISITCOUNTER_TOTAL_PAGE_IMPRESSIONS'));
        $tpl->SetVariable('value', $impressionsTotal);
        $tpl->SetVariable('item_id', 'impressions');
        $tpl->ParseBlock('visitcounter/item');

        $tpl->ParseBlock('visitcounter');

        return $tpl->Get();
    }
}
