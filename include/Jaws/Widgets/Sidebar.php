<?php
/**
 * Widget that prints a sidebar with many options
 *
 * @category   Widget
 * @package    Core
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2007-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class Jaws_Widgets_Sidebar
{
    /**
     * @access  private
     * @var     array
     * @see     function  AddOption
     */
    var $_Options;

    /**
     * Menu bar name
     *
     * @access  private
     * @var     string
     */
    var $_Name;

    /**
     * Main Constructor
     *
     * @access  public
     */
    function Jaws_Widgets_Sidebar($name = 'menu')
    {
        $this->_Options = array();
        $this->_Name    = $name;
    }

    /**
     * Add a new option
     *
     * @access   public
     * @param    string  $action Action's shorname(NOT URL)
     * @param    string  $name Title to print
     * @param    string  $url  Url to point
     * @param    string  $icon Icon/Stock to use
     * @param    string  $onclick Javascript OnClick function
     * @param    boolean $selected If the option is marked as selected
     */
    function AddOption($action, $name, $url = '', $icon = '', $selected = false, $onclick = null)
    {
        // Little fix to avoid javascript: 
        if (strpos($url, 'javascript:') !== false) {
            $onclick = str_replace('javascript:', '', $url);
            $url = '#';
        }

		if (!empty($icon) && substr(strtolower($icon), 0, 6) == 'gadgets' || substr(strtolower($icon), 0, 9) == 'libraries') {
			$icon = $GLOBALS['app']->GetJawsURL() . '/' . $icon;
		}
		
        $this->_Options[$action] = array(
                                         'action' => $action,
                                         'name' => $name,
                                         'url'  => $url,
                                         'icon' => $icon,
                                         'selected' => $selected,
                                         'onclick' => $onclick
                                         );
    }

    /**
     * Select an option to make it active and others inactive
     *
     * @access  public
     * @param   string  $name  Actions's name to activate
     */
    function Activate($name)
    {
        if (isset($this->_Options[$name])) {
            $this->_Options[$name]['selected'] = true;
        }
    }

    /**
     * Build the sidebar with its options
     *
     * @access  private
     */
    function Get()
    {
        $sidebar = "\n" . '<div id="jaws-sidebar-' . $this->_Name . '" class="jaws-sidebar"><ul>' . "\n";

        foreach ($this->_Options as $option) {
            $sidebar .= '   <li id="menu-option-' . $option['action']. '"';
            if (!empty($option['url'])) {
                if (!is_null($option['onclick'])) {
                    $sidebar .= ' onclick="' . $option['onclick'] . '" ';
                } elseif (strpos($option['url'], 'javascript:') === false) {
                    $sidebar .= ' onclick="window.location=\'' . $option['url'] . '\';" ';
                } else {
                    // Deprecated style
                    $sidebar .= ' onclick="' . $option['url'] . '" ';
                }
            }
            if ($option['selected']) {
                $sidebar .= ' class="selected" ';
            }

            $sidebar .= '>';
            if (!empty($option['url'])) {
                if (!is_null($option['onclick'])) {
                    $sidebar .= '    <a href="#" onclick="' . $option['onclick'] . '; return false;">';
                } elseif (strpos($option['url'], 'javascript:') === false) {
                    $sidebar .= '    <a href="' . $option['url'] . '">';
                } else {
                    // Deprecated style
                    $sidebar .= '    <a href="javascript:void(0);" onclick="' . $option['url'] . '">';
                }
            }

            if (!empty($option['icon'])) {
                $sidebar .= '<img alt="' . $option['name'] . '" src="' . $option['icon'] . '" /> ';
            }
            $sidebar .= $option['name'];
            if (!empty($option['url'])) {
                $sidebar .= "</a>";
            }
            $sidebar .= "   </li>\n";
        }

        $sidebar .= "</ul></div>\n";
        return $sidebar;
    }
}
