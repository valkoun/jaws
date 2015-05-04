<?php
/**
 * Create and manage Google Maps.
 *
 * @category   GadgetInfo
 * @category   feature
 * @package    Maps
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class MapsInfo extends Jaws_GadgetInfo
{
    function MapsInfo()
    {
        parent::Init('Maps');
        $this->GadgetName(_t('MAPS_NAME'));
        $this->GadgetDescription(_t('MAPS_DESCRIPTION'));
        $this->GadgetVersion('0.1.1');
        //$this->Doc('gadgets/Maps');
        $this->ListURL(true);

        $acls = array(
            'default',
            'OwnMap',
            'ManageMaps',
            'ManagePublicMaps'
        );
        $this->PopulateACLs($acls);
        $this->Requires('ControlPanel');
    }
}
