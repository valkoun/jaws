<?php
/**
 * Weather Gadget Admin
 *
 * @category   GadgetModel
 * @package    Weather
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @copyright  2004-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
require_once JAWS_PATH . 'gadgets/Weather/Model.php';

class WeatherAdminModel extends WeatherModel
{
    /**
     * Install the gadget
     *
     * @access  public
     * @return  boolean True on successful installation, Jaws_Error otherwise
     */
    function InstallGadget()
    {
        if (!Jaws_Utils::is_writable(JAWS_DATA)) {
            return new Jaws_Error(_t('GLOBAL_ERROR_FAILED_DIRECTORY_UNWRITABLE', JAWS_DATA), _t('WEATHER_NAME'));
        }

        $new_dir = JAWS_DATA . 'weather' . DIRECTORY_SEPARATOR;
        if (!Jaws_Utils::mkdir($new_dir)) {
            return new Jaws_Error(_t('GLOBAL_ERROR_FAILED_CREATING_DIR', $new_dir), _t('WEATHER_NAME'));
        }

        //registry keys.
        $GLOBALS['app']->Registry->NewKey('/gadgets/Weather/refresh',  '5');
        $GLOBALS['app']->Registry->NewKey('/gadgets/Weather/cities',   '');
        $GLOBALS['app']->Registry->NewKey('/gadgets/Weather/units',    'C');
        $GLOBALS['app']->Registry->NewKey('/gadgets/Weather/forecast', 'yes');
        $GLOBALS['app']->Registry->NewKey('/gadgets/Weather/partner_id', '');
        $GLOBALS['app']->Registry->NewKey('/gadgets/Weather/license_key', '');

        return true;
    }

    /**
     * Uninstalls the gadget
     *
     * @access  public
     * @return  boolean  Success/Failure (Jaws_Error)
     */
    function UninstallGadget()
    {
        //registry keys.
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/Weather/refresh');
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/Weather/cities');
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/Weather/units');
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/Weather/forecast');
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/Weather/partner_id');
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/Weather/license_key');

        return true;
    }

    /**
     * Update the gadget
     *
     * @access  public
     * @param   string  $old    Current version (in registry)
     * @param   string  $new    New version (in the $gadgetInfo file)
     * @return  boolean  Success/Failure (Jaws_Error)
     */
    function UpdateGadget($old, $new)
    {
        /*
        $result = $this->installSchema('schema.xml', '', "$old.xml");
        if (Jaws_Error::IsError($result)) {
            return $result;
        }
        */

        // Registry keys.
        $GLOBALS['app']->Registry->NewKey('/gadgets/Weather/partner_id', '');
        $GLOBALS['app']->Registry->NewKey('/gadgets/Weather/license_key', '');

        return true;
    }

    /**
     * Sets the properties of Weather
     *
     * @access  public
     * @param   int     $limit How many cities to use
     * @param   string  $units Units to use
     * @param   string  $fcast Show forecast?
     * @param   string  $partner_id
     * @param   string  $license_key
     * @return  boolean True if change was successful, otherwise returns Jaws_Error
     */
    function UpdateProperties($limit, $units, $fcast, $partner_id, $license_key)
    {
        $rs = array();
        $rs[0] = $GLOBALS['app']->Registry->Set('/gadgets/Weather/refresh',     $limit);
        $rs[1] = $GLOBALS['app']->Registry->Set('/gadgets/Weather/forecast',    $fcast);
        $rs[2] = $GLOBALS['app']->Registry->Set('/gadgets/Weather/units',       $units);
        $rs[3] = $GLOBALS['app']->Registry->Set('/gadgets/Weather/partner_id',  $partner_id);
        $rs[4] = $GLOBALS['app']->Registry->Set('/gadgets/Weather/license_key', $license_key);

        foreach ($rs as $r) {
            if (Jaws_Error::IsError($r) || !$r) {
                $GLOBALS['app']->Session->PushLastResponse(_t('WEATHER_ERROR_PROPERTIES_NOT_UPDATED'), RESPONSE_ERROR);
                return new Jaws_Error(_t('WEATHER_ERROR_PROPERTIES_NOT_UPDATED'), _t('WEATHER_NAME'));
            }
        }

        $GLOBALS['app']->Registry->Commit('Weather');
        $GLOBALS['app']->Session->PushLastResponse(_t('WEATHER_PROPERTIES_UPDATED'), RESPONSE_NOTICE);
        return true;
    }

    /**
     * Adds a new city for forecasting
     *
     * @access  public
     * @param   string  $city_code The city code
     * @return  boolean True if change was successful, otherwise returns Jaws_Error
     */
    function AddCity($city_code)
    {
        $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $city_code = $xss->parse($city_code);

        //is it an array?
        if (trim($GLOBALS['app']->Registry->Get('/gadgets/Weather/cities')) != '') {
            $current_cities = explode(',', $GLOBALS['app']->Registry->Get('/gadgets/Weather/cities'));
            if (!empty($city_code) && !in_array($city_code, $current_cities)) {
                array_push($current_cities, $city_code);
            }
            $string_value = implode(',', $current_cities);
        } else {
            $string_value = $city_code;
        }

        $regreturn = $GLOBALS['app']->Registry->Set('/gadgets/Weather/cities', $string_value);
        $GLOBALS['app']->Registry->Commit('Weather');

        if ($regreturn || !Jaws_Error::IsError($regreturn)) {
            $this->CreateXML();
            $GLOBALS['app']->Session->PushLastResponse(_t('WEATHER_CITY_ADDED'), RESPONSE_NOTICE);
            return true;
        } else {
            $GLOBALS['app']->Session->PushLastResponse(_t('WEATHER_ERROR_CITY_NOT_ADDED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('WEATHER_ERROR_CITY_NOT_ADDED'), _t('WEATHER_NAME'));
        }
    }

    /**
     * Change a city code
     *
     * @access  public
     * @param   string  $old_city_code Old City code
     * @param   string  $city_code     New City Code
     * @return  boolean True if change was successful, otherwise returns Jaws_Error
     */
    function UpdateCity($old_city_code, $city_code)
    {
        $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $city_code = $xss->Parse($city_code);

        $new_cities = str_replace($old_city_code,
                                   $city_code,
                                   $GLOBALS['app']->Registry->Get('/gadgets/Weather/cities'));

        $regreturn = $GLOBALS['app']->Registry->Set('/gadgets/Weather/cities', $new_cities);
        $GLOBALS['app']->Registry->Commit('Weather');

        if ($regreturn || !Jaws_Error::IsError($regreturn)) {
            $this->CreateXML();
            $GLOBALS['app']->Session->PushLastResponse(_t('WEATHER_CITY_UPDATED'), RESPONSE_NOTICE);
            return true;
        }

        $GLOBALS['app']->Session->PushLastResponse(_t('WEATHER_ERROR_CITY_NOT_UPDATED'), RESPONSE_ERROR);
        return new Jaws_Error(_t('WEATHER_ERROR_CITY_NOT_UPDATED'), _t('WEATHER_NAME'));
    }


    /**
     * Remove a city code from cities registry
     *
     * @access  public
     * @param   string  $city_code City Code to remove
     * @return  boolean True if change was successful, otherwise returns Jaws_Error
     */
    function DeleteCity($city_code)
    {
        $cities = explode(',', $GLOBALS['app']->Registry->Get('/gadgets/Weather/cities'));
        $new_cities = null;
        foreach ($cities as $code) {
            if (trim($code) != trim($city_code)) {
                $new_cities[] = $code;
            }
        }


        $value = is_array($new_cities) ? implode(',', $new_cities) : '';
        $return = $GLOBALS['app']->Registry->Set('/gadgets/Weather/cities', $value);
        $GLOBALS['app']->Registry->Commit('Weather');

        if ($return || !Jaws_Error::IsError($return)) {
            $this->CreateXML();
            $GLOBALS['app']->Session->PushLastResponse(_t('WEATHER_CITY_DELETED'), RESPONSE_NOTICE);
            return true;
        }

        $GLOBALS['app']->Session->PushLastResponse(_t('WEATHER_ERROR_CITY_NOT_DELETED'), RESPONSE_ERROR);
        return new Jaws_Error(_t('WEATHER_ERROR_CITY_NOT_DELETED'), _t('WEATHER_NAME'));
    }
}