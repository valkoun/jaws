<?php
/**
 * Create and manage products, brands, attributes and sales.
 *
 * @category   GadgetInfo
 * @category   feature
 * @package    Store
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class StoreInfo extends Jaws_GadgetInfo
{
    function StoreInfo()
    {
        parent::Init('Store');
        $this->GadgetName(_t('STORE_NAME'));
        $this->GadgetDescription(_t('STORE_DESCRIPTION'));
        $this->GadgetVersion('0.1.2');
        //$this->Doc('gadgets/Store');
        $this->ListURL(true);

        $acls = array(
            'default',
            'OwnProduct',
            'ManageProductParents',
            'ManageProducts',
            'ManagePublicProducts'
        );
        $this->PopulateACLs($acls);
        $this->Requires('ControlPanel', 'FileBrowser', 'CustomPage', 'Forms', 'Menu');
    }
}
