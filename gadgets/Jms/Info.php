<?php
/**
 * JMS (Jaws Management System) Gadget
 *
 * @category   GadgetInfo
 * @package    JMS
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Helgi þormar <dufuz@php.net>
 * @copyright  2004-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class JmsInfo extends Jaws_GadgetInfo
{
    /**
     * Sets info about Users gadget
     *
     * @access  public
     */
    function JmsInfo()
    {
        parent::Init('Jms');
        $this->GadgetName(_t('JMS_NAME'));
        $this->GadgetDescription(_t('JMS_DESCRIPTION'));
        $this->GadgetVersion('0.2.0');
        //$this->Doc('gadgets/Jms');
        $this->SetAttribute('core_gadget', true);

        $acls = array(
            'ManageJms',
            'ManageGadgets',
            'ManagePlugins',
        );
        $this->PopulateACLs($acls);
    }
}