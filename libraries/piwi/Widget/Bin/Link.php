<?php
/**
 * Link.php - Link Class
 *
 * @version  $Id $
 * @author   Pablo Fischer <pablo@pablo.com.mx>
 *
 * <c> Pablo Fischer 2006
 * <c> Piwi
 */
require_once PIWI_PATH . '/Widget/Bin/Bin.php';

define('LINK_REQ_PARAMS', 2);
class Link extends Bin
{
    /**
     * Link reference
     *
     * @var    string 
     * @access private
     * @see    setLink
     */
    var $_link;

    /**
     * Link text
     *
     * @var    string 
     * @access private
     * @see    setText
     */
    var $_text;

    /**
     * Img SRC
     *
     * @var    string
     * @access private
     * @see    setImage
     */
    var $_image;

    /**
     * Link target
     *
     * @var    string
     * @access private
     * @see    setTarget
     */
    var $_target;

    /**
     * Hide the text an only use image?
     *
     * @var    string $_alt;
     * @access private
     * @see    setAlt
     */
    var $_hideText = false;

    /**
     * Public constructor
     *
     * @param   string $text  Link Text
     * @param   string $href  Link Reference
     * @param   string $image Link Image
     * @param   string $target Link Target
     * @access  public
     */
    function Link($text, $href, $image = '', $target = '')
    {
        $this->_text  = $text;
        $this->_link  = $href;
        $this->_target  = $target;
        $this->_image = (substr($image,0,1) == '?' ||
                         substr($image,0,7) == 'http://' ||
                         substr($image,0,8) == 'https://')? $image : Piwi::getVarConf('LINK_PRIFIX') . $image;
        if ($this->_image == Piwi::getVarConf('LINK_PRIFIX')) {
			$this->_image = '';
		}
        if (!empty($this->_image)) {
            $this->_hideText = true;
        }
        parent::init();
    }

    /**
     * Set the image alternate text
     *
     * @access   public
     * @param    string Alternate text
     */
    function setAlt($alt)
    {
        $this->_alt = $alt;
    }

    /**
     * Set the link target
     *
     * @access   public
     * @param    string Target
     */
    function setTarget($target)
    {
        $this->_target = $target;
    }

    /**
     * Set the image 
     *
     * @access   public
     * @param    string $image SRC of image (or STOCK)
     */
    function setImage($image)
    {
        $this->_image = (substr($image,0,1) == '?' ||
                         substr($image,0,7) == 'http://' ||
                         substr($image,0,8) == 'https://')? $image : Piwi::getVarConf('LINK_PRIFIX') . $image;
		if (!empty($this->_image)) {
            $this->_hideText = true;
        }
    }

    /**
     * Set the text link 
     *
     * @access   public
     * @param    string $text Text of link
     */
    function setText($text)
    {
        $this->_text = $text;
    }

    /**
     * Set the link reference 
     *
     * @access   public
     * @param    string $link  Link reference
     */
    function setLink($link)
    {
        $this->_link = $link;
    }

    /**
     * Construct the widget
     *
     * @access   private
     */
    function buildXHTML()
    {
        if (strpos($this->_link, 'javascript') === false) {
            $this->_XHTML = '<a href="' . $this->_link . '"'.(!empty($this->_target) && ($this->_target == '_self' || $this->_target == '_blank' || $this->_target == '_parent') ? ' target="'.$this->_target.'"' : '');
        } else {
            $this->_XHTML = '<a href="javascript:void(0);" onclick="' . $this->_link . '"';
        }
        $this->_XHTML .= $this->buildBasicXHTML();
        $this->_XHTML .= $this->buildJSEvents();
        $this->_XHTML.= '>';

        if (!empty($this->_image)) {
            $this->_XHTML.= '<img';
            $this->_XHTML.= ' src="'.$this->_image.'"';
            $this->_XHTML.= ' border="0"';
            $this->_XHTML.= ' alt="'.$this->_text.'"';
            $this->_XHTML.= ' width="16" height="16"';
            $this->_XHTML.= ' title="'.$this->_text.'"';
            $this->_XHTML.= ' />';            
        }

        if (!$this->_hideText && !empty($this->_text)) {
            $this->_XHTML.= $this->_text;
        }

        $this->_XHTML.= '</a>';
    }
}
?>
