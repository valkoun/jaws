<?php
/**
 * Create, manage and receive visitor feedback from custom Forms.
 *
 * @category   GadgetInfo
 * @category   feature
 * @package    Forms
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class FormsInfo extends Jaws_GadgetInfo
{
    function FormsInfo()
    {
        parent::Init('Forms');
        $this->GadgetName(_t('FORMS_NAME'));
        $this->GadgetDescription(_t('FORMS_DESCRIPTION'));
        $this->GadgetVersion('0.1.1');
        //$this->Doc('gadgets/Forms');
        $this->ListURL(true);

        $acls = array(
            'default',
            'OwnForm',
            'ManageForms',
            'ManagePublicForms'
        );
        $this->PopulateACLs($acls);
        $this->Requires('ControlPanel', 'Menu');
    }
}
