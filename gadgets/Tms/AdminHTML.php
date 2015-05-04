<?php
/**
 * TMS (Theme Management System) Gadget Admin view
 *
 * @category   GadgetAdmin
 * @package    TMS
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @copyright  2007-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class TmsAdminHTML extends Jaws_GadgetHTML
{
    /**
     * Gadget constructor
     *
     * @access public
     */
    function TmsAdminHTML()
    {
        $this->Init('Tms');
    }

    /**
     * Main method
     *
     * @access  public
     * @return  string  HTML content of main
     */
    function Admin()
    {
        $this->CheckPermission('ManageTms');
        return $this->ViewThemes();
    }

    /**
     * Prepares the menubar
     *
     * @access  public
     * @param   string  $action  Selected action
     * @return  string  XHTML of menubar
     */
    function Menubar($action)
    {
        $actions = array('Admin', 'Repositories', 'Properties');
        if (!in_array($action, $actions)) {
            $action = 'Admin';
        }

        require_once JAWS_PATH . 'include/Jaws/Widgets/Menubar.php';
        $menubar = new Jaws_Widgets_Menubar();
        if ($this->GetPermission('ManageTms')) {
            $menubar->AddOption('Admin', _t('TMS_THEMES'),
                                BASE_SCRIPT . '?gadget=Tms&amp;action=Admin', 'gadgets/Tms/images/themes.png');
        }
        if ($this->GetPermission('ManageRepositories')) {
            $menubar->AddOption('Repositories', _t('TMS_REPOSITORIES'),
                                BASE_SCRIPT . '?gadget=Tms&amp;action=Repositories', 'gadgets/Tms/images/repositories.png');
        }
        if ($this->GetPermission('ManageProperties')) {
            $menubar->AddOption('Properties', _t('GLOBAL_SETTINGS'),
                                BASE_SCRIPT . '?gadget=Tms&amp;action=Properties', STOCK_PREFERENCES);
        }
        $menubar->Activate($action);
        return $menubar->Get();
    }

    /**
     * Prepares the view to manage properties/settings of 
     * Tms
     *
     * @access  public
     * @return  string  XHTML of view
     */
    function Properties()
    {
        $this->CheckPermission('ManageTms');
        $this->AjaxMe('script.js');

        $model = $GLOBALS['app']->LoadGadget('Tms', 'AdminModel');

        $tpl = new Jaws_Template('gadgets/Tms/templates/');
        $tpl->Load('Properties.html');
        $tpl->SetBlock('Properties');

        $form =& Piwi::CreateWidget('Form', BASE_SCRIPT, 'post');
        $form->Add(Piwi::CreateWidget('HiddenEntry', 'gadget', 'Tms'));
        $form->Add(Piwi::CreateWidget('HiddenEntry', 'action', 'SaveProperties'));

        $shareThemes =& Piwi::CreateWidget('Combo', 'share_themes');
        $shareThemes->SetTitle(_t('TMS_PROPERTIES_SHARE_THEMES'));
        $shareThemes->AddOption(_t('GLOBAL_YES'), 'yes');
        $shareThemes->AddOption(_t('GLOBAL_NO'), 'no');
        $shareThemes->SetDefault($GLOBALS['app']->Registry->Get('/gadgets/Tms/share_mode'));

        include_once JAWS_PATH . 'include/Jaws/Widgets/FieldSet.php';
        $fieldset = new Jaws_Widgets_FieldSet('');
        $fieldset->SetTitle('vertical');

        $fieldset->Add($shareThemes);
        $form->Add($fieldset);

        $buttons =& Piwi::CreateWidget('HBox');
        $buttons->SetStyle(_t('GLOBAL_LANG_DIRECTION')=='rtl'?'float: left;' : 'float: right;');

        $save =& Piwi::CreateWidget('Button', 'save', _t('GLOBAL_SAVE'), STOCK_SAVE);
        $save->AddEvent(ON_CLICK, 'javascript: saveSettings();');

        $buttons->Add($save);
        $form->Add($buttons);

        $tpl->SetVariable('form', $form->Get());
        $tpl->SetVariable('menubar', $this->MenuBar('Properties'));

        $tpl->ParseBlock('Properties');

        return $tpl->Get();      
    }

    /**
     * Manages the themes
     *
     * @access  public
     * @param   string  $content  HTML content(if needed)
     */
    function ViewThemes()
    {
        $this->CheckPermission('ManageTms');
        $this->AjaxMe('script.js');

        $model = $GLOBALS['app']->LoadGadget('Tms', 'AdminModel');
        $tpl = new Jaws_Template('gadgets/Tms/templates/');
        $tpl->Load('Admin.html');
        $tpl->SetBlock('Tms');

        $tpl->SetVariable('confirmEnableTheme', _t('TMS_THEMES_ENABLE_CONFIRM'));
        $tpl->SetVariable('confirmUninstallTheme', _t('TMS_THEMES_UNINSTALL_CONFIRM'));
        $tpl->SetVariable('noAvailableData', _t('TMS_THEMES_NOTHING'));
        $tpl->SetVariable('only_show_t', _t('TMS_ONLY_SHOW'));

        $themesCombo =& Piwi::CreateWidget('Combo', 'themes_combo');
        $themesCombo->SetSize(20);
        $themesCombo->SetStyle('width: 250px; height: 250px;');
        $themesCombo->AddEvent(ON_CHANGE, 'javascript: editTheme(this.value);');

        $onlyShow =& Piwi::CreateWidget('Combo', 'only_show');
        $onlyShow->SetID('only_show');
        $onlyShow->SetStyle('width: 250px;');
        $onlyShow->AddOption(_t('TMS_LOCAL_THEMES'), 'local');
        foreach($model->getRepositories() as $repository) {
            $onlyShow->AddOption($repository['name'], $repository['id']);
        }
        $onlyShow->AddEvent(ON_CHANGE, 'javascript: updateView();');

        if ($this->GetPermission('UploadTheme')) {
            // Upload theme
            $tpl->SetBlock('Tms/UploadTheme');
            $fileEntry =& Piwi::CreateWidget('FileEntry', 'theme_upload');
            $fileEntry->SetStyle('width: 250px;');
            $fileEntry->AddEvent(ON_CHANGE, 'javascript: uploadTheme();');
            $tpl->SetVariable('lbl_theme_upload', _t('TMS_UPLOAD_THEME'));
            $tpl->SetVariable('theme_upload', $fileEntry->Get());
            $tpl->ParseBlock('Tms/UploadTheme');
        }

        $buttons =& Piwi::CreateWidget('HBox');

        $disableTheme =& Piwi::CreateWidget('Button', 'enable_button', _t('TMS_MAKE_DEFAULT'), STOCK_DELETE);
        $disableTheme->AddEvent(ON_CLICK, 'javascript: enableTheme();');
        $disableTheme->SetStyle('display: none');

        $uninstallTheme =& Piwi::CreateWidget('Button', 'uninstall_button', _t('TMS_UNINSTALL'), STOCK_REMOVE);
        $uninstallTheme->AddEvent(ON_CLICK, 'javascript: uninstallTheme();');
        $uninstallTheme->SetStyle('display: none');

        $installTheme =& Piwi::CreateWidget('Button', 'install_button', _t('TMS_INSTALL'), STOCK_SAVE);
        $installTheme->AddEvent(ON_CLICK, 'javascript: installTheme();');
        $installTheme->SetStyle('display: none');
        
        $shareTheme =& Piwi::CreateWidget('Button', 'share_button', _t('TMS_SHARE'), 
                                          'gadgets/Tms/images/unshare.png');
        $shareTheme->AddEvent(ON_CLICK, 'javascript: shareTheme();');
        $shareTheme->SetStyle('display: none');

        $unshareTheme =& Piwi::CreateWidget('Button', 'unshare_button', _t('TMS_UNSHARE'), 
                                            'gadgets/Tms/images/share.png');
        $unshareTheme->AddEvent(ON_CLICK, 'javascript: unshareTheme();');
        $unshareTheme->SetStyle('display: none');

        $buttons->Add($disableTheme);
        $buttons->Add($uninstallTheme);
        $buttons->Add($installTheme);
        $buttons->Add($shareTheme);
        $buttons->Add($unshareTheme);        
        
        $tpl->SetVariable('combo_themes', $themesCombo->Get());
        $tpl->SetVariable('only_show', $onlyShow->Get());
        $tpl->SetVariable('buttons', $buttons->Get());
        $tpl->SetVariable('menubar', $this->Menubar('Admin'));
        $tpl->SetVariable('combo_name', 'themes_combo');
        $tpl->ParseBlock('Tms');

        return $tpl->Get();
    }

    /**
     * Prepares the HTML for managing repositories
     *
     * @access  public
     * @return  string  XHTML content
     */
    function Repositories()
    {
        $this->CheckPermission('ManageRepositories');
        $this->AjaxMe('script.js');
        
        $model = $GLOBALS['app']->LoadGadget('Tms', 'AdminModel');
        
        $tpl = new Jaws_Template('gadgets/Tms/templates/');
        $tpl->Load('Repositories.html');
        $tpl->SetBlock('Repositories');
        $tpl->SetVariable('menubar', $this->Menubar('Repositories'));
        $tpl->SetVariable('grid', $this->Datagrid());

        $repositories_form =& Piwi::CreateWidget('Form', BASE_SCRIPT, 'post', '', 'repositories_form');
        $repositories_form->Add(Piwi::CreateWidget('HiddenEntry', 'action', ''));
        $repositories_form->Add(Piwi::CreateWidget('HiddenEntry', 'id', ''));

        include_once JAWS_PATH . 'include/Jaws/Widgets/FieldSet.php';
        $fieldset_repository = new Jaws_Widgets_FieldSet(_t('TMS_REPOSITORY'));
        $fieldset_repository->SetDirection('vertical');

        $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $repository =& Piwi::CreateWidget('Entry', 'name', '');
        $repository->SetTitle(_t('TMS_REPOSITORY_NAME'));
        $fieldset_repository->Add($repository);

        $urlentry =& Piwi::CreateWidget('Entry', 'url', 'http://');
        $urlentry->SetTitle(_t('GLOBAL_URL'));
        $urlentry->SetStyle('direction: ltr;');
        $fieldset_repository->Add($urlentry);

        $buttonbox =& Piwi::CreateWidget('HBox');
        $buttonbox->SetStyle(_t('GLOBAL_LANG_DIRECTION')=='rtl'?'float: left;' : 'float: right;'); 
        
        $submit =& Piwi::CreateWidget('Button', 'addnewrepostiroy', 
                                      _t('GLOBAL_SAVE'), STOCK_SAVE);
        $submit->AddEvent(ON_CLICK, 'javascript: submitForm(this.form);');

        $cancel =& Piwi::CreateWidget('Button', 'cancelform', _t('GLOBAL_CANCEL'), STOCK_CANCEL);
        $cancel->AddEvent(ON_CLICK, "cleanForm(this.form);");

        $buttonbox->Add($cancel);
        $buttonbox->Add($submit);

        $repositories_form->Add($fieldset_repository);
        $repositories_form->Add($buttonbox);
        
        $tpl->SetVariable('repository_form', $repositories_form->Get());
        $tpl->ParseBlock('Repositories');
        return $tpl->Get();
    }

    /**
     * Creates the datagrid
     *
     * @access  public
     * @return  string XHTML of datagrid
     */
    function DataGrid()
    {
        $model = $GLOBALS['app']->LoadGadget('Tms', 'AdminModel');
        $total = $model->TotalOfData('tms_repositories');
        $datagrid =& Piwi::CreateWidget('DataGrid', array());
        $datagrid->SetID('repositories_datagrid');
        $datagrid->TotalRows($total);
        $datagrid->AddColumn(Piwi::CreateWidget('Column', _t('TMS_REPOSITORY')));
        $datagrid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_URL')));
        $datagrid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_ACTIONS')));
        $datagrid->SetStyle('width: 100%;');
        return $datagrid->Get();
    }

    /**
     * Get a list of repositories in an array, it will contain actions and all
     * the stuff so Ajax can use it
     *
     * @access  public
     * @param   int     $limit  Limit of data
     * @return  array   Data
     */
    function GetRepositories($limit = 0)
    {
        $model        = $GLOBALS['app']->LoadGadget('Tms', 'AdminModel');
        $repositories = $model->GetRepositories($limit);
        if (Jaws_Error::IsError($repositories)) {
            return array();
        }

        $i    = 0;
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $data = array();
        foreach($repositories as $repository) {
            $url = $repository['url'];
            $clean_url = $xss->filter($url);
            if (strlen($url) > 30) {
                $url = '<a title="'.$clean_url.'" href="'.$clean_url.'">' . $xss->filter(substr($url, 0, 30)) . '...</a>';
            } else {
                $url = '<a title="'.$clean_url.'" href="'.$clean_url.'">'.$clean_url.'</a>';
            }
            
            $repository['url'] = $url;
            $actions = '';
            $link = Piwi::CreateWidget('Link', _t('GLOBAL_EDIT'),
                                       "javascript: editRepository('".$repository['id']."');",
                                       STOCK_EDIT);
            $actions.= $link->Get().'&nbsp;|&nbsp;';
            
            $link = Piwi::CreateWidget('Link', _t('GLOBAL_DELETE'),
                                       "javascript: if (confirm('"._t("TMS_CONFIRM_DELETE_REPOSITORY")."')) ".
                                       "deleteRepository('".$repository['id']."');",
                                       STOCK_DELETE);
            $actions.= $link->Get();
            
            unset($repository['id']);
            $repository['actions'] = $actions;
            $data[] = $repository;
        }
        return $data;
    }

    /**
     * Returns the XHTML for viewing theme information
     *
     * @access  public
     * @param   string  $theme      Theme's name
     * @param   mixed   $repository Repository identifier (can be local or an integer, 
     *                              the remote repository
     * @return  string  XHTML view
     */
    function GetThemeInfo($theme, $repository)
    {
        $theme      = trim($theme);
        $repository = trim($repository);

        $model = $GLOBALS['app']->loadGadget('Tms', 'AdminModel');
        $tInfo = $model->getThemeInfo($theme, $repository);

        $tpl= new Jaws_Template('gadgets/Tms/templates/');
        $tpl->Load('ThemeInfo.html');
        $tpl->SetBlock('ThemeInfo');
        $tpl->SetVariable('theme_str', _t('TMS_THEME_INFO_NAME'));
        $tpl->SetVariable('theme_name', $tInfo['name']);
        if (!Jaws_Error::isError($tInfo)) {
            if (empty($tInfo['image'])) {
                $tInfo['image'] = $GLOBALS['app']->GetJawsURL() . '/gadgets/Tms/images/noexample.png';
            }
            $tpl->SetVariable('theme_image', $tInfo['image']);
            $tpl->SetBlock('ThemeInfo/section');
            $tpl->SetVariable('name', _t('TMS_THEME_INFO_DESCRIPTION'));
            if (empty($tInfo['desc'])) {
                $tpl->SetVariable('value', _t('TMS_THEME_INFO_DESCRIPTION_DEFAULT'));
            } else {
                $tpl->SetVariable('value', $tInfo['desc']);
            }
            $tpl->ParseBlock('ThemeInfo/section');

            //We have authors?
            if (count($tInfo['authors']) > 0) {
                $tpl->SetBlock('ThemeInfo/multisection');
                $tpl->SetVariable('name', _t('TMS_THEME_INFO_AUTHOR'));
                foreach($tInfo['authors'] as $author) {
                    $value = '';
                    if (empty($author[1]) && !empty($author[0])) {
                        //User only has email
                        $value = $author[0];
                    } else if (empty($author[0]) && !empty($author[1])) {
                        //Only has name
                        $value = $author[1];
                    } else {
                        $value = $author[1] . ' ('.$author[0].')';
                    }
                    $tpl->SetBlock('ThemeInfo/multisection/subsection');
                    $tpl->SetVariable('value', $value);
                    $tpl->ParseBlock('ThemeInfo/multisection/subsection');
                }
                $tpl->ParseBlock('ThemeInfo/multisection');
            }
        } else {
            $tpl->SetBlock('ThemeInfo/error');
            $tpl->SetVariable('msg', $tInfo->getMessage());
            $tpl->ParseBlock('ThemeInfo/error');
        }
        $tpl->ParseBlock('ThemeInfo');
        return $tpl->Get();
    }

    /**
     * Upload new theme
     *
     * @access  public
     * @return
     */
    function UploadTheme()
    {
        $this->CheckPermission('UploadTheme');

        $res = Jaws_Utils::ExtractFiles($_FILES, JAWS_DATA . 'themes' . DIRECTORY_SEPARATOR);
        if (!Jaws_Error::IsError($res)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('TMS_THEME_UPLOADED'), RESPONSE_NOTICE);
        } else {
            $GLOBALS['app']->Session->PushLastResponse($res->getMessage(), RESPONSE_ERROR);
        }

        require_once JAWS_PATH . 'include/Jaws/Header.php';
        Jaws_Header::Location(BASE_SCRIPT . '?gadget=Tms&action=Admin');
    }

}