<?php
/**
 * SysInfo Core Gadget
 *
 * @category   GadgetInfo
 * @package    SysInfo
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2008-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class SysInfoInfo extends Jaws_GadgetInfo
{
    /**
     * Sets info about SysInfo gadget
     *
     * @access public
     */
    function SysInfoInfo()
    {
        parent::Init('SysInfo');
        $this->GadgetName(_t('SYSINFO_NAME'));
        $this->GadgetDescription(_t('SYSINFO_DESCRIPTION'));
        $this->GadgetVersion('0.1.0');
        $this->Doc('gadget/SysInfo');
        $this->SetAttribute('core_gadget', true);

        $acls  = array(
            'ManageSysInfo'
        );
        $this->PopulateACLs($acls);
    }
}
