<?php
/**
 * Label.php - Label Class
 *
 * @version  $Id $
 * @author   Pablo Fischer <pablo@pablo.com.mx>
 *
 * <c> Pablo Fischer 2004
 * <c> Piwi
 */
require_once PIWI_PATH . '/Widget/Misc/Misc.php';

define('LABEL_REQ_PARAMS', 2);
class Label extends Misc
{
    /**
     * Object that Label is pointing
     *
     * @var      object $_Object
     * @access   private
     */
    var $_object;

    /**
     * Public constructor
     *
     * @param    string  $label Text to use
     * @param    object  $obj   Object to use
     * @access   public
     */
    function Label($label, $obj)
    {
        $this->_familyWidget = 'misc';
        $this->_value = $label;

        if (is_object($obj)) {
            $this->_object = $obj;
        } else {
            die("Second parameter should be an object");
        }
    }

    /**
     * Build XHTML data
     *
     * @access   public
     */
    function buildXHTML()
    {
        if (!empty($this->_value)) {
            $this->_XHTML = '<label for="' . $this->_object->getID() . '">' . $this->_value . '</label>';
        } else {
            $this->_XHTML = '';
        }
    }

    function getValidators()
    {
        return array();
    }
}
