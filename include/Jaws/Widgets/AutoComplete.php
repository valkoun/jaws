<?php
/**
 * Creates a Piwi::Entry but capable to be autocompletable
 *
 * @category   Widget
 * @package    Core
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @copyright  2005-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
require_once JAWS_PATH . 'libraries/piwi/Widget/Bin/Entry.php';

class Jaws_Widgets_AutoComplete extends Entry
{
    /**
     * URL to fetch data
     */
    var $_url = '';

    /**
     * Update function's name
     */
    var $_updateFunction = '';
    
    /*
     * AutoComplete public constructor
     *
     * @param    string $name   Name of the entry
     * @param    string $value  Value of the entry (optional)
     * @param    string $title  Title of the entry (optional)
     * @param    int    $length Lenght of the field (optional)
     * @param    string $status boolean Set the readonly status (optional)
     * @access   public
     */
    function Jaws_Widgets_AutoComplete($name, $value = '', $title = '', $length = '', $status = false)
    {
        parent::Entry($name, $value, $title, $length, $status);
    }

    /**
     * Sets the URL to POST to
     *
     * @access  public
     * @param   string  $url URL that receives the POST
     */
    function setURL($url)
    {
        $this->_url = $url;
    }

    /**
     * Sets the name of the POST variable holding the user's query
     *
     * @access  public
     * @param   string  $name  parameter's name
     */
    function setParamName($name)
    {
        $this->_paramName = $name;
    }

    /**
     * Updated element's function (Javascript funtion name)
     *
     * Function that will be called once the user has selected an item it receives the
     * content of the <li> (list item)
     *
     * @access  public
     * @param   string  $name  Function's name
     */
    function setUpdateFunction($name)
    {
        $this->_updateFunction = $name;
    }

    /**
     * After update callback function (Javascript function name)
     *
     * @access  public
     * @param   string  $name  Function's name
     */
    function setAfterUpdateFunction($name)
    {
        $this->_afterUpdateFunction = $name;
    }

    /**
     * Callback function that is called before POSTing (Javascript function name)
     *
     * @access  public
     * @param   string  $name  Function's name
     */
    function setCallbackFunction($name)
    {
        $this->_callbackFunction = $name;
    }


    /**
     * Prepares the XHTML data
     *
     * @access  public
     */
    function buildXHTML()
    {
        parent::buildXHTML();

        $this->_XHTML.= '<div id="'.$this->_id.'_autocomplete_choices" class="autocomplete"></div>';
        $this->_XHTML.= "\n";       
        $this->_XHTML.= "<script type=\"text/javascript\">\n";
        $this->_XHTML.= " Event.observe(window, \"load\",function(){new Ajax.Autocompleter(\"".$this->_id."\", \"".$this->_id."_autocomplete_choices\", ";
        $this->_XHTML.= '"'.$this->_url.'", {';
        if (!empty($this->_paramName)) {
            $this->_XHTML.= 'paramName: "'.$this->_paramName.'", ';
        } else {
            $this->_XHTML.= 'paramName: "value", ';
		}
        if (!empty($this->_callbackFunction)) {
            $this->_XHTML.= 'callback: '.$this->_callbackFunction.', ';
        }
        if (!empty($this->_updateFunction)) {
            $this->_XHTML.= 'updateElement: '.$this->_updateFunction.', ';
        }
        if (!empty($this->_afterUpdateFunction)) {
            $this->_XHTML.= 'afterUpdateElement: '.$this->_afterUpdateFunction.', ';
        }
        $this->_XHTML.= "minChars: 3});});\n";
        $this->_XHTML.= "</script>\n";
    }
}
?>