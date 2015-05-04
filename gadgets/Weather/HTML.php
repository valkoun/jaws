<?php
/**
 * Weather Gadget
 *
 * @category   Gadget
 * @package    Weather
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @copyright  2004-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class WeatherHTML extends Jaws_GadgetHTML
{
    /**
     * Constructor
     *
     * @access  public
     */
    function WeatherHTML()
    {
        $this->Init('Weather');
    }

    /**
     * Default action
     *
     * @acces  public
     * @return string  HTML result
     */
    function DefaultAction()
    {
        $this->SetTitle(_t('WEATHER_NAME'));
        $layoutGadget = $GLOBALS['app']->LoadGadget('Weather', 'LayoutHTML');
        return $layoutGadget->Display();
    }
}
?>
