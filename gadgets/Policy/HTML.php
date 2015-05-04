<?php
/**
 * Policy Core Gadget
 *
 * @category   Gadget
 * @package    Policy
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2008-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class PolicyHTML extends Jaws_GadgetHTML
{
    /**
     * Gadget constructor
     *
     * @access public
     */
    function PolicyHTML()
    {
        $this->Init('Policy');
    }

    /**
     * Calls default action
     *
     * @access public
     * @return string template content
     */
    function DefaultAction()
    {
		require_once JAWS_PATH . 'include/Jaws/Header.php';
		Jaws_Header::Location(BASE_SCRIPT);
    }

    /**
     * Tricky way to get the captcha image...
     * @access public
     * @return PNG image
     */
    function Captcha()
    {
        $GLOBALS['app']->Registry->LoadFile('Policy');
        $_captcha = $GLOBALS['app']->Registry->Get('/gadgets/Policy/captcha');
        if ($_captcha == 'DISABLED') {
            return '';
        }
        require_once JAWS_PATH . 'gadgets/Policy/captchas/' . $_captcha . '.php';
        $captcha = new $_captcha();
        $captcha->Image();
    }
}
