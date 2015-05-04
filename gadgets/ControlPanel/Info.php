<?php
/**
 * ControlPanel to manage everything.
 *
 * @category   GadgetInfo
 * @category   feature
 * @package    ControlPanel
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @copyright  2004-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class ControlPanelInfo extends Jaws_GadgetInfo
{
    /**
     * Sets info about ControlPanel gadget
     *
     * @access  public
     */
    function ControlPanelInfo()
    {
        parent::Init('ControlPanel');
        $this->GadgetName(_t('CONTROLPANEL_NAME'));
        $this->GadgetDescription(_t('CONTROLPANEL_DESCRIPTION'));
        $this->GadgetVersion('0.8.0');
        //$this->Doc('gadget/ControlPanel');
        $this->SetAttribute('core_gadget', true);

        $acls = array(
            'default',
            'DatabaseBackups',
            'Statistics',
        );
        $this->PopulateACLs($acls);
    }
}