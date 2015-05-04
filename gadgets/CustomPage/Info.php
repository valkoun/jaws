<?php
/**
 * Create and manage custom Pages.
 *
 * @category   GadgetInfo
 * @category   feature
 * @package    CustomPage
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class CustomPageInfo extends Jaws_GadgetInfo
{
    function CustomPageInfo()
    {
        parent::Init('CustomPage');
        $this->GadgetName(_t('CUSTOMPAGE_NAME'));
        $this->GadgetDescription(_t('CUSTOMPAGE_DESCRIPTION'));
        $this->GadgetVersion('0.1.2');
        //$this->Doc('gadgets/CustomPage');
        $this->ListURL(true);

        $acls = array(
            'default',
            'OwnPage',
            'ManagePages',
            'ManagePublicPages'
        );
        $this->PopulateACLs($acls);
        $this->Requires('ControlPanel','Layout','Menu','FileBrowser');
    }
}
