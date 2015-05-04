<?php
/**
 * Manage site-wide Settings.
 *
 * @category   GadgetInfo
 * @category 	feature
 * @package    Settings
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2004-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class SettingsInfo extends Jaws_GadgetInfo
{
    /**
     * Sets info about Settings gadget
     *
     * @access public
     */
    function SettingsInfo()
    {
        parent::Init('Settings');
        $this->GadgetName(_t('SETTINGS_NAME'));
        $this->GadgetDescription(_t('SETTINGS_DESCRIPTION'));
        $this->GadgetVersion('0.3.0');
        $this->Doc('gadget/Settings');
        $this->SetAttribute('core_gadget', true);

        $acls = array('ManageSettings');
        $this->PopulateACLs($acls);
    }
}
