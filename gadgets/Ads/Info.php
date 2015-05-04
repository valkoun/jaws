<?php
/**
 * Create and manage advertisements and coupons.
 *
 * @category   GadgetInfo
 * @category   feature
 * @package    Ads
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class AdsInfo extends Jaws_GadgetInfo
{
    function AdsInfo()
    {
        parent::Init('Ads');
        $this->GadgetName(_t('ADS_NAME'));
        $this->GadgetDescription(_t('ADS_DESCRIPTION'));
        $this->GadgetVersion('0.1.1');
        //$this->Doc('gadgets/Ads');

        $acls = array(
            'default',
            'OwnAds',
            'ManageAds',
            'ManagePublicAds'
        );
        $this->PopulateACLs($acls);
        $this->Requires('ControlPanel', 'Forms', 'Maps');
    }
}
