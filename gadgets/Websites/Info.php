<?php
/**
 * Websites Gadget
 * *
 * @category   GadgetInfo
 * @package    Websites
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */

class WebsitesInfo extends Jaws_GadgetInfo
{
    function WebsitesInfo()
    {
        parent::Init('Websites');
        $this->GadgetName(_t('WEBSITES_NAME'));
        $this->GadgetDescription(_t('WEBSITES_DESCRIPTION'));
        $this->GadgetVersion('0.1.0');
        $this->ListURL(true);

        $acls = array(
            'default',
            'OwnWebsites',
            'OwnPublicWebsites',
			'ManageWebsites',
			'ManagePublicWebsites'
        );
        $this->PopulateACLs($acls);
        $this->Requires('ControlPanel', 'Forms', 'Maps');
    }
}
