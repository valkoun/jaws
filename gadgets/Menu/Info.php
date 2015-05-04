<?php
/**
 * Create and manage navigation Menus.
 *
 * @category   GadgetInfo
 * @category   feature
 * @package    Menu
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2004-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class MenuInfo extends Jaws_GadgetInfo
{
    function MenuInfo()
    {
        parent::Init ('Menu');
        $this->GadgetName(_t('MENU_NAME'));
        $this->GadgetDescription(_t('MENU_DESCRIPTION'));
        $this->GadgetVersion('0.7.1');
        //$this->Doc('gadgets/Menu');

        $acls = array(
            'default',
            'ManageMenus',
            'ManageGroups',
        );
        $this->PopulateACLs($acls);
        $this->Requires('ControlPanel');
    }
}