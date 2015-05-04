<?php
/**
 * Weather Gadget Admin
 *
 * @category   GadgetAdmin
 * @package    Weather
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @copyright  2004-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class WeatherAdminHTML extends Jaws_GadgetHTML
{
    /**
     * Constructor
     *
     * @access  public
     */
    function WeatherAdminHTML()
    {
        $this->Init('Weather');
    }

    /**
     * Get a list of cities in an array
     *
     * @access  public
     * @param   int     $limit  Limit
     * @return  array   Data
     */
    function GetCities($limit = 0)
    {
        $counter = 0;
        $counterStop = 0;
        $model  = $GLOBALS['app']->LoadGadget('Weather', 'AdminModel');
        $cities = $model->GetCities();
        $new_cities = array();
        if (is_array($cities)) {
            for ($i = $limit; $i < ($limit+10); $i++) {
                if (isset($cities[$i])) {
                    $city = $cities[$i];
                    $tmpArray = array();
                    $tmpArray['code'] = $city;
                    $actions = '';
                    if ($this->GetPermission('EditCity')) {
                        $link = Piwi::CreateWidget('Link', _t('GLOBAL_EDIT'),
                                                   "javascript: editCity('".$city."');",
                                                   STOCK_EDIT);
                        $actions.= $link->Get().'&nbsp;';
                    }

                    if ($this->GetPermission('DeleteCity')) {
                        $actions = (empty($actions)) ? $actions : $actions . '|&nbsp;';
                        $link = Piwi::CreateWidget('Link', _t('GLOBAL_DELETE'),
                                                   "javascript: if (confirm('"._t('WEATHER_CONFIRM_DELETE_CITY')."')) ".
                                                   "deleteCity('".$city."');",
                                                   STOCK_DELETE);
                        $actions.= $link->Get();
                    }
                    $tmpArray['actions'] = $actions;
                    $new_cities[] = $tmpArray;
                }
            }
        }
        return $new_cities;
    }

    /**
     * Creates the datagrid
     *
     * @access  public
     * @return  string XHTML of datagrid
     */
    function DataGrid()
    {
        $model = $GLOBALS['app']->LoadGadget('Weather', 'AdminModel');
        $total = $model->TotalOfData();

        $datagrid =& Piwi::CreateWidget('DataGrid', array());
        $datagrid->SetID('weather_datagrid');
        $datagrid->TotalRows($total);
        $datagrid->AddColumn(Piwi::CreateWidget('Column', _t('WEATHER_CODE')));
        $datagrid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_ACTIONS')));
        $datagrid->SetStyle('width: 100%;');
        return $datagrid->Get();
    }

    /**
     * Handles admin functions
     *
     * @access  public
     */
    function Admin()
    {
        $this->CheckPermission('default');
        $this->AjaxMe('script.js');

        $tpl = new Jaws_Template('gadgets/Weather/templates/');
        $tpl->Load('AdminWeather.html');
        $tpl->SetBlock('weather');

        $tpl->SetVariable('grid', $this->DataGrid());

        if ($this->GetPermission('UpdateProperties')) {
            ///Config city
            $config_form =& Piwi::CreateWidget('Form', BASE_SCRIPT, 'post');
            $config_form->Add(Piwi::CreateWidget('HiddenEntry', 'gadget', 'Weather'));
            $config_form->Add(Piwi::CreateWidget('HiddenEntry', 'action', 'UpdateProperties'));

            include_once JAWS_PATH . 'include/Jaws/Widgets/FieldSet.php';
            $fieldset_config = new Jaws_Widgets_FieldSet(_t('GLOBAL_PROPERTIES'));
            $fieldset_config->SetDirection('vertical');

            $limitcombo =& Piwi::CreateWidget('Combo', 'limit_values');
            $limitcombo->SetTitle(_t('WEATHER_REFRESH_TIME'));
            for ($i = 5; $i <= 120; $i += 5) {
                $limitcombo->AddOption($i, $i);
            }

            $refresh = $GLOBALS['app']->Registry->Get('/gadgets/Weather/refresh');
            if (!$refresh || Jaws_Error::IsError($refresh))
                $refresh = 5;

            $limitcombo->SetDefault($refresh);
            $fieldset_config->Add($limitcombo);

            $unitscombo =& Piwi::CreateWidget('Combo', 'units');
            $unitscombo->SetTitle(_t('WEATHER_UNITS'));
            $unitscombo->AddOption(_t('WEATHER_CENTIGRADE'), 'C');
            $unitscombo->AddOption(_t('WEATHER_FAHRENHEIT'), 'F');
            $units = $GLOBALS['app']->Registry->Get('/gadgets/Weather/units');

            if (!$units || Jaws_Error::IsError($units)) {
                $units = 'F';
            }

            $unitscombo->SetDefault($units);

            $fieldset_config->Add($unitscombo);

            $check =& Piwi::CreateWidget('RadioButtons', 'show_forecast');
            $check->SetTitle(_t('WEATHER_SHOW_FORECAST'));
            $check->AddOption(_t('GLOBAL_YES'), 'yes');
            $check->AddOption(_t('GLOBAL_NO'), 'no');

            $forecast = $GLOBALS['app']->Registry->Get('/gadgets/Weather/forecast');
            if (!$forecast || Jaws_Error::IsError($forecast)) {
                $forecast = 'yes';
            }

            $partnerid =& Piwi::CreateWidget('Entry', 'partner_id', $GLOBALS['app']->Registry->Get('/gadgets/Weather/partner_id'));
            $partnerid->SetTitle(_t('WEATHER_PARTNERID'));
            $partnerid->SetStyle('width: 180px;');

            $lickey =& Piwi::CreateWidget('Entry', 'license_key', $GLOBALS['app']->Registry->Get('/gadgets/Weather/license_key'));
            $lickey->SetTitle(_t('WEATHER_LICENSEKEY'));
            $lickey->SetStyle('width: 180px;');

            $check->SetDefault($forecast);
            $fieldset_config->Add($check);
            $fieldset_config->Add($partnerid);
            $fieldset_config->Add($lickey);

            $fieldset_config->SetStyle('width: 250px;');

            $config_form->Add($fieldset_config);

            $submit_config =& Piwi::CreateWidget('Button', 'saveproperties',
                                                 _t('GLOBAL_UPDATE', _t('GLOBAL_PROPERTIES')), STOCK_SAVE);
            $submit_config->AddEvent(ON_CLICK, 'javascript: updateProperties(this.form);');
            $submit_config->SetStyle('float: right;');

            $config_form->Add($submit_config);
            $tpl->SetVariable('config_form', $config_form->Get());
        }

        ///Add City
        if ($this->GetPermission('AddCity')) {
            if (Jaws_Utils::is_writable(JAWS_DATA . 'weather')) {
                $city_form =& Piwi::CreateWidget('Form', BASE_SCRIPT, 'post', '', 'city_form');
                $city_form->Add(Piwi::CreateWidget('HiddenEntry', 'gadget', 'Weather'));
                $city_form->Add(Piwi::CreateWidget('HiddenEntry', 'action', 'AddCity'));

                include_once JAWS_PATH . 'include/Jaws/Widgets/FieldSet.php';
                $fieldset_city = new Jaws_Widgets_FieldSet(_t('WEATHER_CITY'));
                $fieldset_city->SetDirection('vertical');

                $hiddencodeentry =& Piwi::CreateWidget('HiddenEntry', 'hidden_code', '');
                $hiddencodeentry->SetID('hidden_code');
                $fieldset_city->Add($hiddencodeentry);

                /**
                 */
                require_once JAWS_PATH . 'include/Jaws/Widgets/AutoComplete.php';
                $complete = new Jaws_Widgets_AutoComplete('code', '', _t('WEATHER_HINT_GET_CODES_FROM'));
                $complete->SetID('weather_code');
                $complete->SetStyle('width: 180px;');
                $complete->SetURL(BASE_SCRIPT . '?gadget=Weather&action=AutoComplete');
                $complete->SetUpdateFunction('autoCompleteLocation');
                $complete->AddEvent(ON_CHANGE, 'javascript: CheckCodeChange();');

                $fieldset_city->Add($complete, _t('WEATHER_HINT_GET_CODES_FROM'));

                $buttonbox =& Piwi::CreateWidget('HBox');
                $buttonbox->SetStyle('float: right;'); //hig style
                $cancel =& Piwi::CreateWidget('Button', 'cancelform', _t('GLOBAL_CANCEL'), STOCK_CANCEL);
                $cancel->AddEvent(ON_CLICK, 'javascript: cleanForm(this.form);');

                $submit =& Piwi::CreateWidget('Button', 'addnewcity',
                                              _t('GLOBAL_SAVE', _t('WEATHER_CITY')), STOCK_SAVE);
                $submit->AddEvent(ON_CLICK, 'javascript: submitForm(this.form);');

                $buttonbox->Add($cancel);
                $buttonbox->Add($submit);

                $fieldset_city->SetStyle('width: 250px;');

                $city_form->Add($fieldset_city);
                $city_form->Add($buttonbox);
                $tpl->SetVariable('instructions', _t('WEATHER_INSTRUCTIONS'));
                $tpl->SetVariable('city_form', $city_form->Get());
            } else {
                $warning = new Jaws_Template('gadgets/Weather/templates/');
                $warning->Load('AdminWeather.html');
                $warning->SetBlock('warning');
                $warning->SetVariable('message',
                                      _t('GLOBAL_ERROR_FAILED_DIRECTORY_UNWRITABLE', $GLOBALS['app']->getDataURL('weather')));
                $warning->ParseBlock('warning');
                $tpl->SetVariable('city_form', $warning->Get());
            }
        }

        $tpl->ParseBlock('weather');
        return $tpl->Get();
    }

    /**
     * Manages the autocomplete stuff
     *
     * @acccess  public
     * @return   string  (a XHTML list)
     */
    function AutoComplete()
    {
        $request =& Jaws_Request::getInstance();
        $value   = $request->get('value', 'post');

        require_once 'Services/Weather.php';
        $timeout = (int)$GLOBALS['app']->Registry->Get('/config/connection_timeout');
        $options = array('debug' => 2, 'httpTimeout' => $timeout);
        if ($GLOBALS['app']->Registry->Get('/network/proxy_enabled') == 'true') {
            $httpProxy = $GLOBALS['app']->Registry->Get('/network/proxy_type');
            $httpProxy.= '://';
            if ($GLOBALS['app']->Registry->Get('/network/proxy_auth') == 'true') {
                $httpProxy.= $GLOBALS['app']->Registry->Get('/network/proxy_user'). ':' .
                             $GLOBALS['app']->Registry->Get('/network/proxy_pass');
            }
            $httpProxy.= '@';
            $httpProxy.= $GLOBALS['app']->Registry->Get('/network/proxy_host');
            $proxyPort = $GLOBALS['app']->Registry->Get('/network/proxy_port');
            if (!empty($proxyPort)) {
                $httpProxy.= ':' . $proxyPort;
            }
            $options['httpProxy'] = $httpProxy;
        }

        $service   =& Services_Weather::service('WeatherDotCom', $options);
        $service->setCache('file', array('cache_dir' => JAWS_DATA . 'weather/'));
        $locations =  $service->searchLocation($value);

        if (Services_Weather::isError($locations)) {
            $ret = '<ul>';
            $ret.= '<li>'._t('WEATHER_NO_LOCATION_MATCH').'</li>';
            $ret.= '</ul>';
        } else {
            $ret = '<ul>';
            if (is_array($locations)) {
                foreach($locations as $code => $location) {
                    $ret.= '<li class="code_'.$code.'">'.$location.'</li>';
                }
            } else {
                $ret.= '<li class="code_'.$locations.'">'.$value.'</li>';
            }
        }
        $ret.= '</ul>';

        return $ret;
    }
}
