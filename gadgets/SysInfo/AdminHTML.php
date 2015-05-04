<?php
/**
 * SysInfo Core Gadget Admin
 *
 * @category   GadgetAdmin
 * @package    SysInfo
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2008-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class SysInfoAdminHTML extends Jaws_GadgetHTML
{
    /**
     * Gadget constructor
     *
     * @access public
     */
    function SysInfoAdminHTML()
    {
        $this->Init('SysInfo');
    }

    /**
     * Calls default action
     *
     * @access public
     * @return template content
     */
    function Admin()
    {
        $this->CheckPermission('ManageSysInfo');
        return $this->SysInfo();
    }

    /**
     * Display the sidebar
     *
     * @access  public
     * @param   string  $action Selected Action
     * @return  template content
     */
    function SideBar($action)
    {
        $actions = array('SysInfo', 'PHPInfo', 'JawsInfo', 'DirInfo');
        if (!in_array($action, $actions)) {
            $action = 'SysInfo';
        }

        require_once JAWS_PATH . 'include/Jaws/Widgets/Sidebar.php';
        $sidebar = new Jaws_Widgets_Sidebar('sysinfo');

        $sidebar->AddOption('SysInfo', _t('SYSINFO_SYSINFO'), 
                            BASE_SCRIPT . '?gadget=SysInfo&amp;action=SysInfo',
                            'gadgets/SysInfo/images/sysinfo.png');
        $sidebar->AddOption('PHPInfo', _t('SYSINFO_PHPINFO'),
                            BASE_SCRIPT . '?gadget=SysInfo&amp;action=PHPInfo',
                            'gadgets/SysInfo/images/phpinfo.png');
        $sidebar->AddOption('JawsInfo', _t('SYSINFO_JAWSINFO'),
                            BASE_SCRIPT . '?gadget=SysInfo&amp;action=JawsInfo',
                            'gadgets/SysInfo/images/jawsinfo.png');
        $sidebar->AddOption('DirInfo', _t('SYSINFO_DIRINFO'),
                            BASE_SCRIPT . '?gadget=SysInfo&amp;action=DirInfo',
                            'gadgets/SysInfo/images/dirinfo.png');

        $sidebar->Activate($action);
        return $sidebar->Get();
    }

    /**
     * Returns information around system(OS, WebServer, Database,...)
     *
     * @access public
     * @return template content
     */
    function SysInfo()
    {
        $model = $GLOBALS['app']->LoadGadget('SysInfo', 'AdminModel');
        $tpl = new Jaws_Template('gadgets/SysInfo/templates/');
        $tpl->Load('AdminSysInfo.html');
        $tpl->SetBlock('SysInfo');
        $tpl->SetVariable('sidebar', $this->SideBar('SysInfo'));

        //System Information
        $tpl->SetBlock('SysInfo/InfoSection');
        $tpl->SetVariable('section_title', _t('SYSINFO_SYSINFO'));
        $items = $model->GetSysInfo();
        foreach ($items as $item) {
            $tpl->SetBlock('SysInfo/InfoSection/InfoItem');
            $tpl->SetVariable('item_title', $item['title']);
            $tpl->SetVariable('item_value', $item['value']);
            $tpl->ParseBlock('SysInfo/InfoSection/InfoItem');
        }
        $tpl->ParseBlock('SysInfo/InfoSection');

        $tpl->ParseBlock('SysInfo');
        return $tpl->Get();
    }

    /**
     * Returns some PHP Settings
     *
     * @access public
     * @return template content
     */
    function PHPInfo()
    {
        $model = $GLOBALS['app']->LoadGadget('SysInfo', 'AdminModel');
        $tpl = new Jaws_Template('gadgets/SysInfo/templates/');
        $tpl->Load('AdminSysInfo.html');
        $tpl->SetBlock('SysInfo');
        $tpl->SetVariable('sidebar', $this->SideBar('PHPInfo'));

        //PHP Settings
        $tpl->SetBlock('SysInfo/InfoSection');
        $tpl->SetVariable('section_title', _t('SYSINFO_PHPINFO'));
        $items = $model->GetPHPInfo();
        foreach ($items as $item) {
            $tpl->SetBlock('SysInfo/InfoSection/InfoItem');
            $tpl->SetVariable('item_title', $item['title']);
            $tpl->SetVariable('item_value', $item['value']);
            $tpl->ParseBlock('SysInfo/InfoSection/InfoItem');
        }
        $tpl->ParseBlock('SysInfo/InfoSection');

        $tpl->ParseBlock('SysInfo');
        return $tpl->Get();
    }

    /**
     * Returns information around jaws
     *
     * @access public
     * @return template content
     */
    function JawsInfo()
    {
        $model = $GLOBALS['app']->LoadGadget('SysInfo', 'AdminModel');
        $tpl = new Jaws_Template('gadgets/SysInfo/templates/');
        $tpl->Load('AdminSysInfo.html');
        $tpl->SetBlock('SysInfo');
        $tpl->SetVariable('sidebar', $this->SideBar('JawsInfo'));

        //Jaws Settings
        $tpl->SetBlock('SysInfo/InfoSection');
        $tpl->SetVariable('section_title', _t('SYSINFO_JAWSINFO'));
        $items = $model->GetJawsInfo();
        foreach ($items as $item) {
            $tpl->SetBlock('SysInfo/InfoSection/InfoItem');
            $tpl->SetVariable('item_title', $item['title']);
            $tpl->SetVariable('item_value', $item['value']);
            $tpl->ParseBlock('SysInfo/InfoSection/InfoItem');
        }
        $tpl->ParseBlock('SysInfo/InfoSection');

        $tpl->ParseBlock('SysInfo');
        return $tpl->Get();
    }

    /**
     * Returns directory permissions
     *
     * @access public
     * @return template content
     */
    function DirInfo()
    {
        $this->CheckPermission('ManageSysInfo');

        $model = $GLOBALS['app']->LoadGadget('SysInfo', 'AdminModel');
        $tpl = new Jaws_Template('gadgets/SysInfo/templates/');
        $tpl->Load('AdminSysInfo.html');
        $tpl->SetBlock('SysInfo');
        $tpl->SetVariable('sidebar', $this->SideBar('DirInfo'));

        //Directory Permissions
        $tpl->SetBlock('SysInfo/InfoSection');
        $tpl->SetVariable('section_title', _t('SYSINFO_DIRINFO'));
        $items = $model->GetDirsPermissions();
        foreach ($items as $item) {
            $tpl->SetBlock('SysInfo/InfoSection/InfoItem');
            $tpl->SetVariable('item_title', $item['title']);
            $tpl->SetVariable('item_value', $item['value']);
            $tpl->ParseBlock('SysInfo/InfoSection/InfoItem');
        }
        $tpl->ParseBlock('SysInfo/InfoSection');

        $tpl->ParseBlock('SysInfo');
        return $tpl->Get();
    }
}