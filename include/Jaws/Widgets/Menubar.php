<?php
/**
 * Widget that prints a menubar with many options
 *
 * @category   Widget
 * @package    Core
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @copyright  2005-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class Jaws_Widgets_Menubar
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
    function Jaws_Widgets_Menubar($name = 'menu')
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
		if ($icon != '') {
			$icon = (substr(strtolower($icon), 0, strlen(strtolower($GLOBALS['app']->GetJawsURL()))) != strtolower($GLOBALS['app']->GetJawsURL()) ? $GLOBALS['app']->GetJawsURL() . '/' . $icon : $icon);
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
     * Build the menubar with its options
     *
     * @access  private
     */
    function Get()
    {
        $menubar = "\n" . '<div class="clearfix"><ul id="jaws-menubar-' . $this->_Name . '" class="jaws-menubar">' . "\n";

        foreach ($this->_Options as $option) {
            $menubar .= '   <li id="menu-option-' . $option['action']. '"';
            if (!empty($option['url'])) {
                if (!is_null($option['onclick']) && $option['onclick'] == 'void(0);') {
                    $menubar .= ' onclick="' . $option['onclick'] . '" ';
                } elseif (strpos($option['url'], 'javascript:') === false && $option['url'] != '#') {
                    $menubar .= ' onclick="window.location=\'' . $option['url'] . '\';" ';
                } else {
                    // Deprecated style
                    //$menubar .= ' onclick="' . $option['url'] . '" ';
                    $menubar .= ' onclick="void(0);" ';
                }
            }
            if ($option['selected']) {
                $menubar .= ' class="selected" ';
            }

            $menubar .= '>';
            if (!empty($option['url'])) {
                if (!is_null($option['onclick'])) {
                    $menubar .= '    <a href="#" onclick="' . $option['onclick'] . '; return false;">';
                } elseif (strpos($option['url'], 'javascript:') === false) {
                    $menubar .= '    <a href="' . $option['url'] . '">';
                } else {
                    // Deprecated style
                    $menubar .= '    <a href="javascript:void(0);" onclick="' . $option['url'] . '">';
                }
            }

            if (!empty($option['icon'])) {
                $menubar .= '<img alt="' . $option['name'] . '" src="' . $option['icon'] . '" width="16" height="16" /> ';
            }
            $menubar .= $option['name'];
            if (!empty($option['url'])) {
                $menubar .= "</a>";
            }
            $menubar .= "   </li>\n";
        }

        $menubar .= "</ul></div>\n";
        return $menubar;
    }
}
