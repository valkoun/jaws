<?php
/**
 * Menu Gadget
 *
 * @category   Gadget
 * @package    Menu
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Jon Wood <jon@substance-it.co.uk>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2004-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class MenuHTML extends Jaws_GadgetHTML
{
    /**
     * Public constructor
     *
     * @access  public
     */
    function MenuHTML()
    {
        $this->Init('Menu');
    }

    /**
     * Default action
     *
     * @acces  public
     * @return string  HTML result
     */
    function DefaultAction()
    {
        $layoutGadget = $GLOBALS['app']->LoadGadget('Menu', 'LayoutHTML');
        return $layoutGadget->Display($GLOBALS['app']->Registry->Get('/gadgets/Menu/default_group_id'));
    }
}