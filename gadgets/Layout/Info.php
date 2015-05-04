<?php
/**
 * Layout Core Gadget
 *
 * @category   GadgetInfo
 * @package    Layout
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2004-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class LayoutInfo extends Jaws_GadgetInfo
{
    /**
     * Sets info about Layout gadget
     *
     * @access public
     */
    function LayoutInfo()
    {
        parent::Init('Layout');
        $this->GadgetName(_t('LAYOUT_NAME'));
        $this->GadgetDescription(_t('LAYOUT_DESCRIPTION'));
        $this->GadgetVersion('0.4.1');
        $this->Doc('gadget/Layout');
        $this->SetAttribute('core_gadget', true);

        $acls = array(
            'ManageLayout',
            'ManageThemes',
        );
        $this->PopulateACLs($acls);
    }
}