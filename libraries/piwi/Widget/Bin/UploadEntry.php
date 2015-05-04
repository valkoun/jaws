<?php
/*
 * UploadEntry.php - Upload Entry Class, file entry
 *
 * @version  $Id: $
 * @author   Alan Valkoun <valkoun@gmail.com>
 *
 * <c> Alan Valkoun 2010
 * <c> Piwi
 */
require_once PIWI_PATH . '/Widget/Bin/Bin.php';

define('ENTRY_REQ_PARAMS', 6);
class UploadEntry extends Bin
{
	/**
     * Label Title 
     *
     * @var      string $_labelTitle
     * @access   private
     */
    var $_labelTitle;
	
	/**
     * image preview HTML 
     *
     * @var      string $_imagePreview
     * @access   private
     */
    var $_imagePreview;
	
	/**
     * image script 
     *
     * @var      string $_imageScript
     * @access   private
     */
    var $_imageScript;
	
	/**
     * image hidden field 
     *
     * @var      string $_imageHidden
     * @access   private
     */
    var $_imageHidden;
	
	/**
     * image button field 
     *
     * @var      string $_imageButton
     * @access   private
     */
    var $_imageButton;
	
	/**
     * main image field 
     *
     * @var      string $_mainImage
     * @access   private
     */
    var $_mainImage;
	
	/**
     * width of entry 
     *
     * @var      string $_width
     * @access   private
     */
    var $_width;
    
    /**
     * Public constructor
     *
     * @param    string Name of the entry
     * @param    string Name of gadget to add this upload to
     * @param    string Name of gadget table (optional)
     * @param    string Name of gadget method (optional)
     * @param    int 	Number of entries to create (optional)
     * @param    string Value of the entry (optional)
     * @param    boolean Use User Account (optional)
     * @param    int    Width of the entry (optional)
     * @param    int    Height of the entry (optional)
     * @param    string HTML for image preview (optional)
     * @access   public
     */
    function UploadEntry($name, $label_title, $image_preview, $image_script, $image_hidden, $image_button, $main_image = 'main_image', $width = 500)
    {
        $this->_name       		= $name;
        $this->_labelTitle       	= $label_title;
		$this->_imagePreview 	= $image_preview;
		$this->_imageScript 	= $image_script;
        $this->_imageHidden     = $image_hidden;
        $this->_imageButton     = $image_button;
        $this->_mainImage     	= $main_image;
        $this->_width     		= $width;
        $this->_availableEvents = array();
        parent::init();
    }
    
    /**
     * Build the piwiXML data.
     *
     * @access    public
     */
    function buildPiwiXML()
    {
        $this->buildBasicPiwiXML();

        if (!empty($this->_mainImage)) {
            $this->_PiwiXML->addAttribute('main_field', $this->_mainImage);
        }

        if (!empty($this->_width) && is_numeric($this->_width)) {
            $this->_PiwiXML->addAttribute('width', $this->_width);
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
		$this->_XHTML = "<table width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\"><tr><td class=\"syntacts-form-row\">";
		$this->_XHTML .= "<label for=\"".$this->_name."\"><nobr>".$this->_labelTitle."</nobr></label>";
		$this->_XHTML .= $this->_imagePreview."</td><td class=\"syntacts-form-row\"><div id=\"".$this->_mainImage."\" style=\"float: left; width: ".$this->_width."px;\">";
		$this->_XHTML .= "</div>".$this->_imageScript.$this->_imageHidden.$this->_imageButton."</td></tr></table>\n";
    }
}
?>
