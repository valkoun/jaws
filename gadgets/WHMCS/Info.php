<?php
/**
 * A gadget to interact with WHMCS.
 *
 * @category   GadgetInfo
 * @package    WHMCS
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2012 Alan Valkoun
 */
class WHMCSInfo extends Jaws_GadgetInfo
{
    function WHMCSInfo()
    {
        parent::Init('WHMCS');
        $this->GadgetName(_t('WHMCS_NAME'));
        $this->GadgetDescription(_t('WHMCS_DESCRIPTION'));
        $this->GadgetVersion('0.1.0');
        //$this->Doc('gadgets/WHMCS');

        $acls = array(
            'default',
            'ManageWHMCSClients'
        );
        $this->PopulateACLs($acls);
        $this->Requires('ControlPanel','Users');
    }
}
