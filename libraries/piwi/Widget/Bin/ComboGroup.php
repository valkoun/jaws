<?php
/**
 * ComboGroup.php - ComboGroup Class
 *
 * @version  $Id $
 * @author   Pablo Fischer <pablo@pablo.com.mx>
 *
 * <c> Jonathan Hernandez 2004
 * <c> Pablo Fischer 2004
 * <c> Piwi
 */
require_once PIWI_PATH . '/Widget/Bin/Bin.php';
require_once PIWI_PATH . '/Widget/Bin/ComboOption.php';

define('COMBOGROUP_REQ_PARAMS', 1);
class ComboGroup extends Bin
{
    /**
     * Combo data. The options of the Combo
     *
     * @var    array $_Options
     * @access private
     * @see    addOption()
     */
    var $_groups;

    /**
     * Odd/Even row color
     *
     * @var     array  $_colors
     * @see     setOddColor(), setEvenColor()
     * @access  private
     */
    var $_colors = array('even' => 'white', 'odd' => 'gray');
    
    /**
     * Public constructor
     *
     * @param   string Name of the combo
     * @param   string Data of the combo
     * @param   string Title of the combo
     * @access  public
     */
    function ComboGroup ($name, $title = '')
    {
        $this->_name   = $name;
        $this->_title  = $title;
        $this->_groups = array();

        $oddColor = Piwi::getVarConf('COLOR_ODD');
        if (empty($oddColor)) {
            $oddColor = '#eee';
        }
        $this->setOddColor($oddColor);

        $evenColor = Piwi::getVarConf('COLOR_EVEN');
        if (empty($evenColor)) {
            $evenColor = '#fff';
        }
        $this->setEvenColor($evenColor);

        $this->_availableEvents = array("onclick", "ondblclick", "onmousedown",
                                        "onmouseup", "onmouseover", "onmousemove",
                                        "onmouseout", "onkeypress", "onkeydown", "onkeyup");
        parent::init();
    }

    /**
     * Set the odd color
     *
     * @param  string  $color  Color
     * @access public
     */
    function setOddColor($color)
    {
        $this->_colors['odd'] = $color;
    }

    /**
     * Set the even color
     *
     * @param  string  $color  Color
     * @access public
     */
    function setEvenColor($color)
    {
        $this->_colors['even'] = $color;
    }

    /**
     * Add a new Group to the combo group
     *
     * @param   string $group The name of the group
     * @param   array  $options The Options of this group
     * @param   boolean $isdisabled Sometimes a option can be disabled by default
     * @param   string $class The class of the option
     * @param   string $style The style of the option
     *
     * @access  public
     */
    function addGroup($group, &$options, $isdisabled = false, $class = '', $style = '')
    {
        if (is_array($options) && is_object($options[0])) {
            if ($options[0]->getClassName() == 'combooption')
                $this->_groups[$group] = array('options'     => $options,
                                               'name'        => $group,
                                               'is_disabled' => $isdisabled,
                                               'class'       => $class,
                                               'style'       => $style);
        } else {
            die("Sorry, in ComboGroup to add a group you need to give the name of the group and a array of ComboOptions");
        }
    }

    /**
     * Set a key as the selected one
     *
     * @param   string $group   The group where you think the option is
     * @param   string $default The option that should be marked as selected
     * @access  public
     */
    function setDefault($group, $key)
    {
        if (isset ($this->_groups[$group])) {
            $option_id = 0;
            if (count($this->_groups[$group]['options']) > 0) {
                foreach ($this->_groups[$group]['options'] as $group_name => $option) {
                    if ($option->getValue() == $key || $option->getText() == $key) {
                        $this->_groups[$group]['options'][$option_id]->select();
                    } else {
                        $this->_groups[$group]['options'][$option_id]->select(false);
                    }
                    $option_id++;
                }
            }
        }
    }

    /**
     * Build the piwiXML data.
     *
     * @access    public
     */
    function buildPiwiXML ()
    {
        $this->buildBasicPiwiXML();

        if (count($this->_groups) > 0) {
            foreach ($this->_groups as $group) {
                $this->_PiwiXML->openElement('group');
                $this->_PiwiXML->addAttribute('label', $group['name']);

                if (!empty($group['class'])) {
                    $this->_PiwiXML->addAttribute('class', $group['class']);
                }

                if (!empty($group['style'])) {
                    $this->_PiwiXML->addAttribute('style', $group['style']);
                }

                if (!empty($group['is_disabled'])) {
                    $this->_PiwiXML->addAttribute('enabled', 'false');
                }

                if (count($group['options']) > 0) {
                    $this->_PiwiXML->openElement('options');
                    foreach ($group['options'] as $option) {
                        $this->_PiwiXML->openElement('option', true);

                        $value = $option->getValue();
                        $this->_PiwiXML->addAttribute('value', $value);
                        $this->_PiwiXML->addAttribute('label', $value);

                        $class = $option->getClass();
                        if (!empty($class)) {
                            $this->_PiwiXML->addAttribute('class', $class);
                        }

                        $style = $option->getStyle();
                        if (!empty($style)) {
                            $this->_PiwiXML->addAttribute('style', $style);
                        }

                        $disabled = $option->isDisabled();
                        if ($disabled) {
                            $this->_PiwiXML->addAttribute('enabled', 'false');
                        }

                        $selected = $option->isSelected();
                        if ($selected) {
                            $this->_PiwiXML->addAttribute('selected', 'true');
                        } else {
                            $this->_PiwiXML->addAttribute('selected', 'false');
                        }

                        $this->_PiwiXML->closeElement('option');

                    }
                    $this->_PiwiXML->closeElement('options');
                }
                $this->_PiwiXML->closeElement('group');
            }
        }

        $this->buildXMLEvents();
        $this->_PiwiXML->closeElement($this->getClassName());
    }

    /**
     * Build the XHTML data
     *
     * @access  private
     */
    function buildXHTML()
    {
        if (count($this->_groups) > 0) {

            $this->_XHTML = "<select";

            $this->_XHTML.= $this->buildBasicXHTML();
            $this->_XHTML.= $this->buildJSEvents();

            $this->_XHTML.= ">\n";

            foreach ($this->_groups as $group) {
                $this->_XHTML.= "<optgroup label=\"".$group['name']."\"";

                if (!empty($group['class'])) {
                    $this->_XHTML.= " class=\"".$group['class']."\"";
                }

                if (!empty($group['style'])) {
                    $this->_XHTML.= " style=\"".$group['style']."\"";
                }

                if (!empty($group['is_disabled'])) {
                    $this->_XHTML.= " disabled=\"disabled\"";
                }

                $color = $this->_colors['even'];
                $colorcounter = 0;

                $this->_XHTML.= ">\n";
                foreach ($group['options'] as $option) {
                    $this->_XHTML.= "<option value=\"".$option->getValue()."\"";

                    $class = $option->getClass();
                    if (!empty($class)) {
                        $this->_XHTML.= " class=\"".$class."\"";
                    }

                    $style = $option->getStyle();
                    if (!empty($style)) {
                        $this->_XHTML.= " style=\"".$style."; background: ".$color.";\"";
                    } else {
                        $this->_XHTML.= " style=\"background: ".$color.";\"";
                    }


                    $disabled = $option->isDisabled();
                    if ($disabled) {
                        $this->_XHTML.= " disabled=\"disabled\"";
                    }

                    $selected = $option->isSelected();
                    if ($selected) {
                        $this->_XHTML.= " selected=\"selected\"";
                    }

                    $this->_XHTML.= ">";
                    $this->_XHTML.= $option->getText();
                    $this->_XHTML.= "</option>\n";
                    
                    if ($colorcounter % 2 == 0) {
                        $color = $this->_colors['odd'];
                    } else {
                        $color = $this->_colors['even'];
                    }
                    $colorcounter++;
                }
                $this->_XHTML.= "</optgroup>\n";
            }
            $this->_XHTML.= "</select>\n";
        }
    }
}
?>
