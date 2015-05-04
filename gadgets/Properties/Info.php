<?php
/**
 * Create and manage Properties and amenities.
 *
 * @category   GadgetInfo
 * @category   feature
 * @package    Properties
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class PropertiesInfo extends Jaws_GadgetInfo
{
    function PropertiesInfo()
    {
        parent::Init('Properties');
        $this->GadgetName(_t('PROPERTIES_NAME'));
        $this->GadgetDescription(_t('PROPERTIES_DESCRIPTION'));
        $this->GadgetVersion('0.1.1');
        //$this->Doc('gadgets/Properties');
        $this->ListURL(true);

        $acls = array(
            'default',
            'OwnProperty',
            'ManagePropertyParents',
            'ManageProperties',
            'ManagePublicProperties'
        );
        $this->PopulateACLs($acls);
        $this->Requires('ControlPanel', 'FileBrowser', 'Maps', 'CustomPage', 'Forms', 'Calendar', 'Menu');
    }
}
