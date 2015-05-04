<?php
/**
 * Preferences Gadget Info
 *
 * @category   GadgetInfo
 * @package    Preferences
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2004-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class PreferencesInfo extends Jaws_GadgetInfo
{
    function PreferencesInfo()
    {
        parent::Init('Preferences');
        $this->GadgetName(_t('PREFERENCES_NAME'));
        $this->GadgetDescription(_t('PREFERENCES_DESCRIPTION'));
        $this->GadgetVersion('0.8.0');
        $this->Doc('gadget/Preferences');

        $acls = array(
            'default',
            'UpdateProperties',
        );
        $this->PopulateACLs($acls);
        $this->Requires('ControlPanel');
    }
}