<?php
/**
 * Weather Gadget
 *
 * @category   GadgetModel
 * @package    Weather
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @copyright  2004-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class WeatherModel extends Jaws_Model
{
    /**
     * Converts celsius 2 farenheit
     *
     * @access  public
     * @param   float   $celsius  Celsius
     * @return  float   Farenheit
     */
    function c2f($celsius)
    {
        $f = (float)($celsius*9/5+32);
        return round($f);
    }

    /**
     * Create the XML file that will be used to read the temperature in the future
     *
     * @access  public
     * @param   string  $string Where it should look for more info
     * @return  boolean True in success and Jaws_Error in failure
     */
    function CreateXML()
    {
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

        $service  =& Services_Weather::service('WeatherDotCom', $options);
        if (!PEAR::isError($service)) {
            $service->setAccountData($GLOBALS['app']->Registry->Get('/gadgets/Weather/partner_id'),
                                     $GLOBALS['app']->Registry->Get('/gadgets/Weather/license_key'));
            Jaws_Utils::mkdir(JAWS_DATA. 'weather');
            $service->setCache('file', array('cache_dir' => JAWS_DATA . 'weather/'));
            $service->setUnitsFormat('m');
            $xml = "<Weather>\n";
            $cities = explode(',', $GLOBALS['app']->Registry->Get('/gadgets/Weather/cities'));
            foreach ($cities as $city) {
                $xml .= "<city>\n";
                $city = trim($city);
                $location = $service->getLocation($city);
                if (!Services_Weather::isError($location)) {
                    $weather  = $service->getWeather($city);
                    if (!Services_Weather::isError($weather)) {
                        $xml .= "\t<name>".$location['name']."</name>\n";
                        $xml .= "\t<farenheit>".$this->c2f($weather['temperature'])."</farenheit>\n";
                        $xml .= "\t<celsius>".$weather['temperature']."</celsius>\n";
                        $xml .= "\t<icon>".$weather['conditionIcon']."</icon>\n";
                        $xml .= "\t<real_farenheit>".$this->c2f($weather['feltTemperature']).
                                "</real_farenheit>\n";
                        $xml .= "\t<real_celsius>".$weather['feltTemperature']."</real_celsius>\n";
                    }
                    // 5 days
                    $forecast = $service->getForecast($city, 5);
                    if (!Services_Weather::isError($forecast) && is_array($forecast['days'])) {
                        $forecast = $forecast['days'];
                        $day = 0;
                        foreach($forecast as $f) {
                            $xml .= "\t<forecast>\n";
                            $xml .= "\t\t<date>".date('Y-m-d H:i:s', (time()+($day * 24 * 60 * 60)))."</date>\n";
                            $xml .= "\t\t<low_farenheit>".$this->c2f($f['temperatureLow'])."</low_farenheit>\n";
                            $xml .= "\t\t<low_celsius>".$f['temperatureLow']."</low_celsius>\n";
                            $xml .= "\t\t<high_farenheit>".$this->c2f($f['temperatureHigh']).
                                    "</high_farenheit>\n";
                            $xml .= "\t\t<high_celsius>".$f['temperatureHigh']."</high_celsius>\n";
                            $xml .= "\t\t<icon>".$f['day']['conditionIcon']."</icon>\n";
                            $xml .= "\t</forecast>\n";
                            $day++;
                        }
                    } else {
                        $xml .= "\t<forecast>\n";
                        $xml .= "\t</forecast>\n";
                    }
                }
                $xml .= "</city>\n";
            }
            $xml .= "</Weather>\n";
            // write xml
            if ($fp = @fopen(JAWS_DATA.'weather/Weather.xml', 'w')) {
                fwrite($fp, $xml);
                fclose($fp);
                Jaws_Utils::chmod(JAWS_DATA.'weather/Weather.xml');
                return true;
            }
        }

        return new Jaws_Error(_t('WEATHER_ERROR_CANT_CREATE_XMLFILE'), _t('WEATHER_NAME'));
    }

    /**
     * Reads the XML File and return a XML struct
     *
     * @access  public
     * @return  array   The XML struct of the weather.xml file
     */
    function ReadXML()
    {
        $filename = JAWS_DATA.'weather/Weather.xml';
        // Generate XML if refresh time has been exceeded
        $secRefresh = $GLOBALS['app']->Registry->Get('/gadgets/Weather/refresh') * 60;
        if (file_exists($filename)) {
            $secCreated = mktime() - filemtime($filename);
        } else {
            $secCreated = $secRefresh + 1;
        }

        if ($secCreated > $secRefresh) {
            $this->CreateXML();
        }

        // Parse XML file
        if (file_exists(JAWS_DATA . 'weather/Weather.xml')) {
            $contents = implode('', file(JAWS_DATA . 'weather/Weather.xml'));
            $p = xml_parser_create();
            xml_parse_into_struct($p, $contents, $vals, $index);
            xml_parser_free($p);
        }

        //Return the struct
        if (isset($vals)) {
            return $vals;
        }

        return array();
    }

    /**
     * Gets the cities as an array
     *
     * @access  public
     * @return  array   An array of the cities
     */
    function GetCities()
    {
        $cities = explode(',',$GLOBALS['app']->Registry->Get('/gadgets/Weather/cities'));
        if ($cities[0] == '') {
            return array();
        }

        return $cities;
    }

    /**
     * Gets the total of cities
     *
     * @access  public
     * @return  int     Total of data
     */
    function TotalOfData() 
    {
        $cities = $this->GetCities();
        return count($cities);
    }
}