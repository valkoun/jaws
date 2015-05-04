<?php
/**
 * JSNumericValidator.php - Validate if the entry is just made of numbers
 *
 * @version  $Id $
 * @author   Pablo Fischer <pablo@pablo.com.mx>
 *
 * <c> Pablo Fischer 2004
 * <c> Piwi
 */
require_once PIWI_PATH . '/JS/JSValidator.php';

class JSNumericValidator extends JSValidator
{
    /**
     * Constructor
     *
     * @param  string  $field  Field to validate
     * @param  string  $error  Error to print
     * @access public
     */
    function JSNumericValidator($field, $error)
    {
        parent::__construct($field, $error);

        $this->_code = "if (!isNumeric(form.".$this->_field.".value)) {\n";
        $this->_code.= "   alert ('".$this->_error."');\n";
        $this->_code.= "   form.".$this->_field.".focus ();\n";
        $this->_code.= "   return false;\n";
        $this->_code.= "}\n\n";
    }
}
?>