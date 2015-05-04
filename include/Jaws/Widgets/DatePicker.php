<?php
/**
 * Widget that interacts with piwi and jaws and extends Piwi::DatePicker
 *
 * @category   Widget
 * @package    Core
 * @author     Helgi �ormar �orbj�rnsson <dufuz@php.net>
 * @copyright  2005-2010 Jaws Development Group
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 */
require_once JAWS_PATH . 'libraries/piwi/Widget/Bin/DatePicker.php';

class Jaws_Widgets_DatePicker extends DatePicker
{
    /**
     * Default theme
     */
    var $_theme = 'calendar-system';

    function setTheme($theme)
    {
        $theme = strtolower($theme);
        $themes = array(
            'blue', 'blue2', 'brown',
            'green', 'system', 'tas', 'win2k-1',
            'win2k-2', 'win2k-cold-1', 'wink2-cold-2'
        );

        if (!in_array($theme, $themes)) {
            $theme = 'calendar-system';
        }

        if (!strstr('calendar-', $theme)) {
            $theme = 'calendar-' . $theme;
        }

        $this->_theme = $theme;
    }

    function _buildXHTML()
    {
        $this->_XHTML .= $this->_entry->get();
        $this->_XHTML .= $this->_button->get();
    }

    function buildXHTML()
    {
        $GLOBALS['app']->Layout->addHeadLink('libraries/piwi/data/css/' . $this->_theme . '.css',
                                             'stylesheet', 'text/css');
        parent::buildXHTML();
    }
}
?>