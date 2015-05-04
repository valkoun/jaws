<?php
/**
 * Connect to Social networks, allow sharing and more.
 *
 * @category   GadgetInfo
 * @category   feature
 * @package    Social
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2004-2009 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class SocialInfo extends Jaws_GadgetInfo
{
    function SocialInfo()
    {
        parent::Init('Social');
        $this->GadgetName(_t('SOCIAL_NAME'));
        $this->GadgetDescription(_t('SOCIAL_DESCRIPTION'));
        $this->GadgetVersion('0.8.2');
        $this->Doc('gadget/Social');

        $acls = array(
            'default',
            'UpdateProperties',
        );
        $this->PopulateACLs($acls);
        $this->Requires('ControlPanel', 'Forms');
    }
}
