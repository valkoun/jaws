<?php
/**
 * Weather Layout HTML file (for layout purposes)
 *
 * @category   GadgetLayout
 * @package    Weather
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @copyright  2004-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class WeatherLayoutHTML
{
    /**
     * Rounds and validate value
     *
     * @param   float   $value Weather Value
     * @access  private
     */
    function val($value)
    {
        if ($value == '-17.78' OR $value == '-0') {
            return '--';
        }

        return round($value);
    }

    /**
     * Loads the template and prints the cities
     *
     * @access  public
     * @return  string HTML content of Weather cities
     */
    function Display()
    {
        $t = new Jaws_Template('gadgets/Weather/templates/');
        $t->Load('Weather.html');
        $t->SetBlock('weather');
        $t->SetVariable('title', _t('WEATHER_NAME'));
        $units = $GLOBALS['app']->Registry->Get('/gadgets/Weather/units');
        if (Jaws_Error::IsError($units) || !$units) {
            $units = 'F';
        }
        $ignore = false;
        $openedForecast = false;
        $model = $GLOBALS['app']->LoadGadget('Weather', 'Model');
        $xml   = $model->ReadXML();
        $date  = $GLOBALS['app']->loadDate();
        if (is_array($xml)) {
            foreach ($xml as $item) {
                switch ($item['type']) {
                case 'complete':
                    if ($item['tag'] == 'FARENHEIT' || $item['tag'] == 'CELSIUS') {
                        $t->SetVariable($item['tag'], isset($item['value']) ? $item['value'] : '');
                        switch($item['tag']) {
                        case 'FARENHEIT':
                            if ($units == 'F') {
                                $t->SetVariable('TEMP', $this->val($item['value']).'&deg;F');
                            }
                            break;
                        case 'CELSIUS':
                            if ($units == 'C') {
                                $t->SetVariable('TEMP', $this->val($item['value']).'&deg;C');
                            }
                            break;
                        }
                    } else {
                        if (!$ignore) {
                            if ($item['tag']=='DATE') {
                                $item['value'] = Jaws_Gadget::ParseText($date->Format($item['value'], 'd M Y'), 'Weather');
                            }
                            $t->SetVariable($item['tag'], isset($item['value']) ? $item['value'] : '');
                            switch ($item['tag']) {
                            case 'REAL_FARENHEIT':
                                if ($units == 'F') {
                                    $t->SetVariable('REAL_TEMP', _t('WEATHER_FEELS').' '.$this->val($item['value']).'&deg;F');
                                }
                                break;
                            case 'REAL_CELSIUS':
                                if ($units == 'C') {
                                    $t->SetVariable('REAL_TEMP', _t('WEATHER_FEELS').' '.$this->val($item['value']).'&deg;C');
                                }
                                break;
                            case 'HIGH_FARENHEIT':
                                if ($units == 'F') {
                                    $t->SetVariable('HIGH_TEMP', _t('WEATHER_HIGH').' '.$this->val($item['value']).'&deg;F');
                                }
                                break;
                            case 'HIGH_CELSIUS':
                                if ($units == 'C') {
                                    $t->SetVariable('HIGH_TEMP', _t('WEATHER_HIGH').' '.$this->val($item['value']).'&deg;C');
                                }
                                break;
                            case 'LOW_FARENHEIT':
                                if ($units == 'F') {
                                    $t->SetVariable('LOW_TEMP', _t('WEATHER_LO').' '.$this->val($item['value']).'&deg;F');
                                }
                                break;
                            case 'LOW_CELSIUS':
                                if ($units == 'C') {
                                    $t->SetVariable('LOW_TEMP', _t('WEATHER_LO').' '.$this->val($item['value']).'&deg;C');
                                }
                                break;
                            }
                        }
                    }
                    break;
                case 'open':
                    switch ($item['tag']) {
                    case 'weather':
                        //$t->SetBlock('weather');
                        //$t->SetVariable('title',_t('WEATHER_NAME'));
                        break;
                    case 'CITY':
                        $t->SetBlock('weather/city');
                        break;
                    case 'FORECAST':
                        if ($GLOBALS['app']->Registry->Get('/gadgets/Weather/forecast') == 'no') {
                            $ignore = true;
                        } else {
                            $ignore = false;
                        }
                        if (!$ignore) {
                            if (!$openedForecast) {
                                $t->SetBlock('weather/city/forecast');
                                $t->SetVariable('forecast', _t('WEATHER_FORECAST'));
                                $openedForecast = true;
                            }
                            $t->SetBlock('weather/city/forecast/forecast_entry');
                        }
                        break;
                    }
                    break;
                case 'close':
                    switch ($item['tag']) {
                    case 'WEATHER':
                        //$t->SetVariable('title',_t('WEATHER_NAME'));
                        //$t->ParseBlock('weather');
                        break;
                    case 'CITY':
                        if ($openedForecast) {
                            $t->ParseBlock('weather/city/forecast');
                            $openedForecast = false;
                        }
                        $t->ParseBlock('weather/city');
                        break;
                    case 'FORECAST':
                        if (!$ignore) {
                            $t->ParseBlock('weather/city/forecast/forecast_entry');
                        } else {
                            $ignore = false;
                        }
                        break;
                    }
                    break;
                }
            }
        }
        $t->ParseBlock('weather');

        return $t->Get();
    }
}