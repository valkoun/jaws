<?php
/**
 * Registry Core Gadget
 *
 * @category   GadgetInfo
 * @package    Registry
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @copyright  2004-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class RegistryInfo extends Jaws_GadgetInfo
{
    /**
     * Sets info about Registry gadget
     *
     * @access  public
     */
    function RegistryInfo()
    {
        parent::Init ('Registry');
        $this->GadgetName(_t('REGISTRY_NAME'));
        $this->GadgetDescription(_t('REGISTRY_DESCRIPTION'));
        $this->GadgetVersion('0.2.0');
        $this->Doc('gadget/Registry');
        $this->SetAttribute('core_gadget', true);

        $acls = array('ManageRegistry');
        $this->PopulateACLs($acls);
    }
}