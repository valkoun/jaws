<?php
/**
 * Weather Actions file
 *
 * @category   GadgetActions
 * @package    Weather
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @copyright  2004-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
/* Normal actions*/
$actions = array();
/* Layout actions */
$actions['Display'] = array('LayoutAction', _t('WEATHER_LAYOUT_DISPLAY_WEATHER'), _t('WEATHER_LAYOUT_DISPLAY_WEATHER_DESC')); 
/* Admin actions */
$actions['AutoComplete'] = array('StandaloneAdminAction');
