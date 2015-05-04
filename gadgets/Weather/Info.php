<?php
/**
 * Weather Gadget
 *
 * @category   GadgetInfo
 * @package    Weather
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @copyright  2004-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class WeatherInfo extends Jaws_GadgetInfo
{
    function WeatherInfo()
    {
        parent::Init('Weather');
        $this->GadgetName(_t('WEATHER_NAME'));
        $this->GadgetDescription(_t('WEATHER_DESC'));
        $this->GadgetVersion('0.7.0');
        $this->Doc('gadget/Weather');

        $acls = array(
            'default',
            'AddCity',
            'EditCity',
            'DeleteCity',
            'UpdateProperties'
        );

        $this->PopulateACLs($acls);
        $this->Requires('ControlPanel');
    }
}