<?php
/**
 * SysInfo Core Gadget
 *
 * @category   Gadget
 * @package    SysInfo
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2008-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class SysInfoHTML extends Jaws_GadgetHTML
{
    /**
     * Gadget constructor
     *
     * @access       public
     */
    function SysInfoHTML()
    {
        $this->Init('SysInfo');
    }

    /**
     * Default Action
     *
     * @access  public
     * @return  string  HTML content of DefaultAction
     */
    function DefaultAction()
    {
        $layoutGadget = $GLOBALS['app']->LoadGadget('SysInfo', 'LayoutHTML');
        return $layoutGadget->SysInfo();
    }

    /**
     * System Information
     *
     * @access  public
     * @return  string  HTML content of DefaultAction
     */
    function SysInfo()
    {
        $this->SetTitle(_t('SYSINFO_SYSINFO'));
        $layoutGadget = $GLOBALS['app']->LoadGadget('SysInfo', 'LayoutHTML');
        return $layoutGadget->SysInfo();
    }

    /**
     * PHP Settings
     *
     * @access  public
     * @return  string  HTML content of DefaultAction
     */
    function PHPInfo()
    {
        $this->SetTitle(_t('SYSINFO_PHPINFO'));
        $layoutGadget = $GLOBALS['app']->LoadGadget('SysInfo', 'LayoutHTML');
        return $layoutGadget->PHPInfo();
    }

    /**
     * Jaws Settings
     *
     * @access  public
     * @return  string  HTML content of DefaultAction
     */
    function JawsInfo()
    {
        $this->SetTitle(_t('SYSINFO_JAWSINFO'));
        $layoutGadget = $GLOBALS['app']->LoadGadget('SysInfo', 'LayoutHTML');
        return $layoutGadget->JawsInfo();
    }

    /**
     * Directory Permissions
     *
     * @access  public
     * @return  string  HTML content of DefaultAction
     */
    function DirInfo()
    {
        $this->SetTitle(_t('SYSINFO_DIRINFO'));
        $layoutGadget = $GLOBALS['app']->LoadGadget('SysInfo', 'LayoutHTML');
        return $layoutGadget->DirInfo();
    }
}
