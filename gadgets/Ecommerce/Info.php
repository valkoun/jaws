<?php
/**
 * Create, accept and manage e-commerce Orders. 
 *
 * @category   GadgetInfo
 * @category   feature
 * @package    Ecommerce
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class EcommerceInfo extends Jaws_GadgetInfo
{
    function EcommerceInfo()
    {
        parent::Init('Ecommerce');
        $this->GadgetName(_t('ECOMMERCE_NAME'));
        $this->GadgetDescription(_t('ECOMMERCE_DESCRIPTION'));
        $this->GadgetVersion('0.1.1');
        //$this->Doc('gadgets/Ecommerce');

        $acls = array(
            'default',
            'OwnEcommerce',
            'ManageEcommerce',
            'ManagePublicEcommerce'
        );
        $this->PopulateACLs($acls);
        $this->Requires('ControlPanel', 'Store', 'Maps');
    }
}
