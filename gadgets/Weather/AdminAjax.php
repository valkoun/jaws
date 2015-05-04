<?php
/**
 * Weather AJAX API
 *
 * @category   Ajax
 * @package    Weather
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @copyright  2005-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class WeatherAdminAjax extends Jaws_Ajax
{
    /**
     * Constructor
     *
     * @access  public
     */
    function WeatherAdminAjax(&$model)
    {
        $this->_Model =& $model;
    }

    /**
     * Add a city
     *
     * @access  public
     * @param   string  $code    Weather code
     * @return  array   Response (notice or error)
     */
    function NewCity($code)
    {
        $this->CheckSession('Weather', 'AddCity');
        $this->_Model->AddCity($code);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Update a city
     *
     * @access  public
     * @param   string  $old     Old code
     * @param   string  $code    New code
     * @return  array   Response (notice or error)
     */
    function UpdateCity($old, $code)
    {
        $this->CheckSession('Weather', 'EditCity');
        $this->_Model->UpdateCity($old, $code);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Delete a city
     *
     * @access  public
     * @param   string  $code   Code
     * @return  array  Response (notice or error)
     */
    function DeleteCity($code)
    {
        $this->CheckSession('Weather', 'DeleteCity');
        $this->_Model->DeleteCity($code);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Update the properties
     *
     * @access  public
     * @param   int     $limit How many cities to use
     * @param   string  $units Units to use
     * @param   string  $fcast Show forecast?
     * @param   string  $partner_id
     * @param   string  $license_key
     * @return  array   Response
     */
    function UpdateProperties($limit, $units, $fcast, $partner_id, $license_key)
    {
        $this->CheckSession('Weather', 'UpdateProperties');
        $this->_Model->UpdateProperties($limit, $units, $fcast, $partner_id, $license_key);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Rebuild the datagrid
     *
     * @access  public
     */
    function GetData($limit)
    {
        $this->CheckSession('Weather', 'default');
        $gadget = $GLOBALS['app']->LoadGadget('Weather', 'AdminHTML');
        if (!is_numeric($limit)) {
            $limit = 0;
        }
        return $gadget->GetCities($limit);
    }

    /**
     * Get location from code
     *
     * @access  public
     * @return  string  The location name
     */
    function Code2Location($code)
    {
        $this->CheckSession('Weather', 'default');
        require_once 'Services/Weather.php';
        $timeout  = (int)$GLOBALS['app']->Registry->Get('/config/connection_timeout');
        $service  =& Services_Weather::service('WeatherDotCom', array('debug' => 2, 'httpTimeout' => $timeout));
        $service->setCache('file', array('cache_dir' => JAWS_DATA . 'weather/'));
        $location =  $service->getLocation($code);

        if (Services_Weather::isError($location)) {
            return $code;
        }

        if (isset($location['name'])) {
            return $location['name'];
        }

        return $code;
    }

}
?>
