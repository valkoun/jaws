<?php
/**
 * Languages Core Gadget Admin
 *
 * @category   GadgetAdmin
 * @package    Languages
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2007-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class LanguagesAdminHTML extends Jaws_GadgetHTML
{
    /**
     * Gadget constructor
     *
     * @access public
     */
    function LanguagesAdminHTML()
    {
        $this->Init('Languages');
    }

    /**
     * Prepares the users menubar
     *
     * @access  public
     * @param   string  $action   Selected action
     * @return  string  XHTML of menubar
     */
    function MenuBar($action)
    {
        $actions = array('Admin', 'Settings');
        if (!in_array($action, $actions)) {
            $action = 'Admin';
        }

        require_once JAWS_PATH . 'include/Jaws/Widgets/Menubar.php';
        $menubar = new Jaws_Widgets_Menubar();
        if ($this->GetPermission('ManageLanguages')) {
            $menubar->AddOption('Admin', _t('LANGUAGES_NAME'),
                                BASE_SCRIPT . '?gadget=Languages&amp;action=Admin', STOCK_DOWN);
			$menubar->AddOption('Settings', _t('GLOBAL_PROPERTIES'),
								BASE_SCRIPT . '?gadget=Languages&amp;action=Settings', STOCK_PREFERENCES);
        }
        $menubar->Activate($action);
        return $menubar->Get();
    }

    /**
     * Calls default action(MainMenu)
     *
     * @access public
     * @return template content
     */
    function Admin()
    {
        $this->CheckPermission('ManageLanguages');
        $this->AjaxMe('script.js');

        $model = $GLOBALS['app']->LoadGadget('Languages', 'AdminModel');
        $tpl = new Jaws_Template('gadgets/Languages/templates/');
        $tpl->Load('AdminLanguages.html');
        $tpl->SetBlock('Languages');
        $tpl->SetVariable('menubar', $this->MenuBar('Admin'));
        $tpl->SetVariable('language',   _t('LANGUAGES_LANGUAGE'));
        $tpl->SetVariable('component',  _t('LANGUAGES_COMPONENT'));
        $tpl->SetVariable('settings',   _t('LANGUAGES_SETTINGS'));
        $tpl->SetVariable('from',       _t('GLOBAL_FROM'));
        $tpl->SetVariable('to',         _t('GLOBAL_TO'));
        $tpl->SetVariable('base_script', BASE_SCRIPT);

        $btnExport =& Piwi::CreateWidget('Button','btn_export',
                                         _t('LANGUAGES_LANGUAGE_EXPORT'), STOCK_DOWN);
        $btnExport->AddEvent(ON_CLICK, 'javascript: export_lang();');
        $tpl->SetVariable('btn_export', $btnExport->Get());

        if ($this->GetPermission('ModifyLanguageProperties')) {
            $tpl->SetBlock('Languages/properties');
            $langId =& Piwi::CreateWidget('Entry', 'lang_code', '');
            $tpl->SetVariable('lang_code', $langId->Get());
            $tpl->SetVariable('lbl_lang_code', _t('LANGUAGES_LANGUAGE_CODE'));

            $langName =& Piwi::CreateWidget('Entry', 'lang_name', '');
            $tpl->SetVariable('lang_name', $langName->Get());
            $tpl->SetVariable('lbl_lang_name', _t('LANGUAGES_LANGUAGE_NAME'));

            $btnLang =& Piwi::CreateWidget('Button','btn_lang', '', STOCK_SAVE);
            $btnLang->AddEvent(ON_CLICK, 'javascript: save_lang();');
            $tpl->SetVariable('btn_lang', $btnLang->Get());
            $tpl->ParseBlock('Languages/properties');
        }

        $tpl->SetVariable('confirmSaveData',     _t('LANGUAGES_SAVEDATA'));
        $tpl->SetVariable('add_language_title',  _t('LANGUAGES_LANGUAGE_ADD'));
        $tpl->SetVariable('save_language_title', _t('LANGUAGES_LANGUAGE_SAVE'));

        // Langs
        $use_data_lang = $GLOBALS['app']->Registry->Get('/gadgets/Languages/use_data_lang') == 'true';
        $langs = Jaws_Utils::GetLanguagesList($use_data_lang);
        $tpl->SetBlock('Languages/lang');
        $tpl->SetVariable('selected', '');
        $tpl->SetVariable('code', '');
        $tpl->SetVariable('fullname', _t('LANGUAGES_LANGUAGE_NEW'));
        $tpl->ParseBlock('Languages/lang');

        foreach ($langs as $code => $fullname) {
            $tpl->SetBlock('Languages/lang');
            $tpl->SetVariable('selected', $code=='en'? 'selected="selected"': '');
            $tpl->SetVariable('code', $code);
            $tpl->SetVariable('fullname', $fullname);
            $tpl->ParseBlock('Languages/lang');
        }

        // Components
        $components = $model->GetComponents();
        $componentsName = array('Global', 'Gadgets', 'Plugins');
        foreach ($components as $compk => $compv) {
            if (is_array($compv)) {
                $tpl->SetBlock('Languages/group');
                $tpl->SetVariable('group', $componentsName[$compk]);
                foreach ($compv as $k => $v) {
                    $tpl->SetBlock('Languages/group/item');
                    $tpl->SetVariable('key', "$compk|$v");
                    $tpl->SetVariable('value', $v);
                    $tpl->ParseBlock('Languages/group/item');
                }
                $tpl->ParseBlock('Languages/group');
            } else {
                $tpl->SetBlock('Languages/component');
                $tpl->SetVariable('key', $compk);
                $tpl->SetVariable('value', $compv);
                $tpl->ParseBlock('Languages/component');
            }
        }

        $tpl->SetBlock('Languages/buttons');
        //checkbox_filter
        $check_filter =& Piwi::CreateWidget('CheckButtons', 'checkbox_filter');
        $check_filter->AddEvent(ON_CLICK, 'javascript: filterTranslated();');
        $check_filter->AddOption(_t('LANGUAGES_NOT_SHOW_TRANSLATED'), '', 'checkbox_filter');
        $tpl->SetVariable('checkbox_filter', $check_filter->Get());

        $cancel_btn =& Piwi::CreateWidget('Button','btn_cancel',
                                        _t('GLOBAL_CANCEL'), STOCK_CANCEL);
        $cancel_btn->AddEvent(ON_CLICK, 'javascript: stopAction();');
        $cancel_btn->SetStyle('visibility: hidden;');
        $tpl->SetVariable('cancel', $cancel_btn->Get());

        $save_btn =& Piwi::CreateWidget('Button','btn_save',
                                        _t('GLOBAL_SAVE', _t('LANGUAGES_CHANGES')), STOCK_SAVE);
        $save_btn->AddEvent(ON_CLICK, 'javascript: save_lang_data();');
        $save_btn->SetStyle('visibility: hidden;');
        $tpl->SetVariable('save', $save_btn->Get());
        $tpl->ParseBlock('Languages/buttons');

        $tpl->ParseBlock('Languages');
        return $tpl->Get();
    }

    /**
     * Calls default action(MainMenu)
     *
     * @access public
     * @return template content
     */
    function GetLangDataUI($module, $type, $langTo)
    {
        $this->CheckPermission('ManageLanguages');
        $model = $GLOBALS['app']->LoadGadget('Languages', 'AdminModel');
        $tpl = new Jaws_Template('gadgets/Languages/templates/');
        $tpl->Load('LangStrings.html');
        $tpl->SetBlock('LangStrings');

        $langFrom = $GLOBALS['app']->Registry->Get('/gadgets/Languages/base_lang');
        $data = $model->GetLangData($module, $type, $langTo, $langFrom);
        $color = 'even';
        if (count($data['strings']) > 0) {
            foreach($data['strings'] as $k => $v) {
                $tpl->SetBlock('LangStrings/item');
                $tpl->SetVariable('color', $color);
                $color = ($color=='odd')? 'even' : 'odd';
                if ($v[$langTo] == '') {
                    $tpl->SetVariable('from', '<span style="color: #f00;">' . nl2br($v[$langFrom]) . '</span>');
                } else {
                    $tpl->SetVariable('from', '<span style="color: #000;">' . nl2br($v[$langFrom]) . '</span>');
                }

                $brakeLines = substr_count($v[$langFrom], "\n");
                $rows = floor((strlen($v[$langFrom]) - $brakeLines*2)/42) + $brakeLines;
                if ($brakeLines == 0) {
                    $rows++;
                }

                $tpl->SetVariable('dir', $data['lang_direction']);
                $tpl->SetVariable('row_count', $rows);
                $tpl->SetVariable('height', $rows*18);
                $tpl->SetVariable('field', $k);
                $tpl->SetVariable('to', str_replace('"', '&quot;', $v[$langTo]));
                $tpl->ParseBlock('LangStrings/item');
            }
        }

        foreach($data['meta'] as $k => $v) {
            $tpl->SetBlock('LangStrings/MetaData');
            $tpl->SetVariable('label', $k);
            $tpl->SetVariable('value', $v);
            $tpl->ParseBlock('LangStrings/MetaData');
        }

        $tpl->ParseBlock('LangStrings');
        return $tpl->Get();
    }

    /**
     * Export language
     *
     * @access public
     * @return download link
     */
    function Export()
    {
        $this->CheckPermission('ManageLanguages');
        $request =& Jaws_Request::getInstance();
        $lang = $request->get('lang', 'get');

        require_once "File/Archive.php"; 
        $tmpDir = sys_get_temp_dir();
        $tmpFileName = "$lang.tar";
        $tmpArchiveName = $tmpDir . DIRECTORY_SEPARATOR . $tmpFileName;

        $files  = array();
        $jms = $GLOBALS['app']->LoadGadget('Jms', 'AdminModel');

        $globals = array('FullName', 'Global.php', 'Date.php', 'Install.php', 'Upgrade.php');
        foreach($globals as $global) {
            $lang_file = "languages/$lang/$global";
            if (file_exists(JAWS_DATA . $lang_file)) {
                $files[]   = File_Archive::read(JAWS_DATA . $lang_file , $lang_file);
            } elseif (file_exists(JAWS_PATH . $lang_file)) {
                $files[]   = File_Archive::read(JAWS_PATH . $lang_file , $lang_file);
            }
        }

        $gadgets = array_keys($jms->GetGadgetsList());
        foreach($gadgets as $gadget) {
            $data_file = "languages/$lang/gadgets/$gadget.php";
            $lang_file = "gadgets/$gadget/languages/$lang.php";
            if (file_exists(JAWS_DATA . $data_file)) {
                $files[]   = File_Archive::read(JAWS_DATA . $data_file , $lang_file);
            } elseif (file_exists(JAWS_PATH . $lang_file)) {
                $files[]   = File_Archive::read(JAWS_PATH . $lang_file , $lang_file);
            }
        }

        $plugins = array_keys($jms->GetPluginsList());
        foreach($plugins as $plugin) {
            $data_file = "languages/$lang/plugins/$plugin.php";
            $lang_file = "plugins/$plugin/languages/$lang.php";
            if (file_exists(JAWS_DATA . $data_file)) {
                $files[]   = File_Archive::read(JAWS_DATA . $data_file , $lang_file);
            } elseif (file_exists(JAWS_PATH . $lang_file)) {
                $files[]   = File_Archive::read(JAWS_PATH . $lang_file , $lang_file);
            }
        }

        File_Archive::extract($files, File_Archive::toArchive($tmpArchiveName, File_Archive::toFiles()));

        // browser must download file from server instead of cache
        header("Expires: 0");
        header("Pragma: public");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        // force download dialog
        header("Content-Type: application/force-download");
        // set data type, size and filename
        header("Content-Disposition: attachment; filename=\"$tmpFileName\"");
        header("Content-Transfer-Encoding: binary");
        header('Content-Length: '.@filesize($tmpArchiveName));
        @readfile($tmpArchiveName);
    }
	
    /**
     * Edit properties
     *
     * @access  public
     * @return  string HTML content
     */
    function Settings()
    {
        $this->AjaxMe('script.js');
        $tpl = new Jaws_Template('gadgets/Languages/templates/');
        $tpl->Load('AdminLanguages.html');
        $tpl->SetBlock('Settings');
        
		$tpl->SetVariable('menubar', $this->MenuBar('Settings'));

        $form =& Piwi::CreateWidget('Form', BASE_SCRIPT, 'post');
        $form->Add(Piwi::CreateWidget('HiddenEntry', 'gadget', 'Languages'));
        $form->Add(Piwi::CreateWidget('HiddenEntry', 'action', 'SaveLanguageChoices'));

        include_once JAWS_PATH . 'include/Jaws/Widgets/FieldSet.php';
        $gadgets_fieldset = new Jaws_Widgets_FieldSet('Languages Enabled for Visitors');
        $gadgets_fieldset->SetTitle('vertical');
        $gadgets_fieldset->SetStyle('margin-top: 30px;');

		// See: http://code.google.com/apis/ajaxlanguage/documentation/reference.html#LangNameArray
		$gadget_list = array(
		  'AFRIKAANS:af',
		  'ALBANIAN:sq',
		  'ARABIC:ar',
		  'BELARUSIAN:be',
		  'BULGARIAN:bg',
		  'CHINESE:zh',
		  'CHINESE SIMPLIFIED:zh-CN',
		  'CHINESE TRADITIONAL:zh-TW',
		  'CROATIAN:hr',
		  'CZECH:cs',
		  'DANISH:da',
		  'DUTCH:nl',  
		  'ENGLISH:en',
		  'ESTONIAN:et',
		  'FILIPINO:tl',
		  'FINNISH:fi',
		  'FRENCH:fr',
		  'GALICIAN:gl',
		  'GERMAN:de',
		  'GREEK:el',
		  'HEBREW:iw',
		  'HINDI:hi',
		  'HUNGARIAN:hu',
		  'ICELANDIC:is',
		  'INDONESIAN:id',
		  'IRISH:ga',
		  'ITALIAN:it',
		  'JAPANESE:ja',
		  'KOREAN:ko',
		  'LATVIAN:lv',
		  'LITHUANIAN:lt',
		  'MACEDONIAN:mk',
		  'MALAY:ms',
		  'MALTESE:mt',
		  'NORWEGIAN:no',
		  'PERSIAN:fa',
		  'POLISH:pl',
		  'PORTUGUESE:pt-PT',
		  'ROMANIAN:ro',
		  'RUSSIAN:ru',
		  'SERBIAN:sr',
		  'SLOVAK:sk',
		  'SLOVENIAN:sl',
		  'SPANISH:es',
		  'SWAHILI:sw',
		  'SWEDISH:sv',
		  'THAI:th',
		  'TURKISH:tr',
		  'UKRAINIAN:uk',
		  'VIETNAMESE:vi',
		  'WELSH:cy',
		  'YIDDISH:yi'		
		);
		$language_choices = '';
	      
		  foreach ($gadget_list as $lang_str) {
					$language_choices .= $lang_str.',';
					$enabled = 0;
					if (in_array($lang_str, explode(',', $GLOBALS['app']->Registry->Get('/gadgets/language_visitor_choices')))) {
						$enabled = 1;
					}
					$gadgetCombo =& Piwi::CreateWidget('Combo', $lang_str);
					$gadgetCombo->SetTitle($lang_str);
					$gadgetCombo->AddOption(_t('GLOBAL_YES'), 1);
					$gadgetCombo->AddOption(_t('GLOBAL_NO'), 0);
					$gadgetCombo->SetDefault($enabled);
					$gadgets_fieldset->Add($gadgetCombo);
			}
		
        $form->Add(Piwi::CreateWidget('HiddenEntry', 'language_choices', $language_choices));

        $form->Add($gadgets_fieldset);
		
        $buttons =& Piwi::CreateWidget('HBox');
        $buttons->SetStyle(_t('GLOBAL_LANG_DIRECTION')=='rtl'?'float: left;' : 'float: right;');

        $save =& Piwi::CreateWidget('Button', 'save', _t('GLOBAL_SAVE'), STOCK_SAVE);
        $save->AddEvent(ON_CLICK, 'javascript: saveSettings();');

        $buttons->Add($save);
        $form->Add($buttons);

        $tpl->SetVariable('form', $form->Get());

        $tpl->ParseBlock('Settings');

        return $tpl->Get();
    }
	
}
