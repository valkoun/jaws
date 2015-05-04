<?php
/**
 * Languages Core Gadget
 *
 * @category   GadgetModel
 * @package    Languages
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2007-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
define('EMPTY_STRING', "-EMPTY-");

class LanguagesAdminModel extends Jaws_Model
{
    /**
     * Installs the gadget
     *
     * @access 	public
     * @return 	mixed 	True on successful installation, Jaws_Error otherwise
     */
    function InstallGadget()
    {
        if (!Jaws_Utils::is_writable(JAWS_DATA)) {
            return new Jaws_Error(_t('GLOBAL_ERROR_FAILED_DIRECTORY_UNWRITABLE', JAWS_DATA));
        }

        $new_dir = JAWS_DATA . 'languages' . DIRECTORY_SEPARATOR;
        if (!Jaws_Utils::mkdir($new_dir)) {
            return new Jaws_Error(_t('GLOBAL_ERROR_FAILED_CREATING_DIR', $new_dir), _t('LANGUAGES_NAME'));
        }

        $GLOBALS['app']->Registry->NewKey('/gadgets/language_visitor_choices', '');
        $GLOBALS['app']->Registry->NewKey('/gadgets/Languages/base_lang', 'en');
        $GLOBALS['app']->Registry->NewKey('/gadgets/Languages/use_data_lang', 'true');
        $GLOBALS['app']->Registry->NewKey('/gadgets/Languages/pluggable', 'false');
        return true;
    }

    /**
     * Update the gadget
     *
     * @access  public
     * @param   string  $old    Current version (in registry)
     * @param   string  $new    New version (in the $gadgetInfo file)
     * @return  boolean  Success/Failure (Jaws_Error)
     */
    function UpdateGadget($old, $new)
    {
        if (!Jaws_Utils::is_writable(JAWS_DATA)) {
            return new Jaws_Error(_t('GLOBAL_ERROR_FAILED_DIRECTORY_UNWRITABLE', JAWS_DATA));
        }

        $new_dir = JAWS_DATA . 'languages' . DIRECTORY_SEPARATOR;
        if (!Jaws_Utils::mkdir($new_dir)) {
            return new Jaws_Error(_t('GLOBAL_ERROR_FAILED_CREATING_DIR', $new_dir), _t('LANGUAGES_NAME'));
        }

        // Registry keys
        $GLOBALS['app']->Registry->NewKey('/gadgets/Languages/use_data_lang', 'true');

        // ACL keys
        $GLOBALS['app']->ACL->NewKey('/ACL/gadgets/Languages/ModifyLanguageProperties', 'false');

        return true;
    }

    /**
     * @access  public
     *
     * @param   string  $lang_str   Language code and name
     * @return  boolean Success/Failure (Jaws_Error)
     */
    function SaveLanguage($lang_str)
    {
        $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        if ($lang_str == $xss->parse($lang_str)) {
            $lang_code = substr($lang_str, 0, strpos($lang_str, ';'));
            if (preg_match("/^([a-z]{2})$|^([a-z]{2}[-][a-z]{2})$/", $lang_code)) {
                $lang_name = substr($lang_str, strpos($lang_str, ';')+1);
                if (!empty($lang_name) || trim($lang_name) == $lang_name) {
                    $use_data_lang = $GLOBALS['app']->Registry->Get('/gadgets/Languages/use_data_lang') == 'true';
                    $jaws_lang_dir = ($use_data_lang? JAWS_DATA : JAWS_PATH) . "languages";

                    $lang_dir = $jaws_lang_dir. DIRECTORY_SEPARATOR. $lang_code;
                    if (!Jaws_Utils::mkdir($lang_dir, 2)) {
                        $GLOBALS['app']->Session->PushLastResponse(
                                            _t('GLOBAL_ERROR_FAILED_CREATING_DIR'),
                                            RESPONSE_ERROR);
                        return false;
                    }

                    if (!Jaws_Utils::is_writable($jaws_lang_dir)) {
                        $GLOBALS['app']->Session->PushLastResponse(
                                            _t('GLOBAL_ERROR_FAILED_DIRECTORY_UNWRITABLE'),
                                            RESPONSE_ERROR);
                        return false;
                    }

                    $lang_exist = @is_dir($lang_dir);
                    $lang_fname_file = $lang_dir. DIRECTORY_SEPARATOR. 'FullName';
                    if (Jaws_Utils::file_put_contents($lang_fname_file, $lang_name)) {
                        if ($lang_exist) {
                            $GLOBALS['app']->Session->PushLastResponse(
                                            _t('LANGUAGES_LANGUAGE_UPDATED', $lang_code),
                                            RESPONSE_NOTICE);
                        } else {
                            $GLOBALS['app']->Session->PushLastResponse(
                                            _t('LANGUAGES_LANGUAGE_ADDED', $lang_code),
                                            RESPONSE_NOTICE);
                        }
                        return true;
                    } else {
                        if ($lang_exist) {
                            $GLOBALS['app']->Session->PushLastResponse(
                                            _t('LANGUAGES_LANGUAGE_UPDATE_ERROR', $lang_code),
                                            RESPONSE_ERROR);
                        } else {
                            $GLOBALS['app']->Session->PushLastResponse(
                                            _t('LANGUAGES_LANGUAGE_ADD_ERROR', $lang_code),
                                            RESPONSE_ERROR);
                        }
                        return false;
                    }
                }
            }
        }

        $GLOBALS['app']->Session->PushLastResponse(_t('LANGUAGES_NAME_ERROR'), RESPONSE_ERROR);
        return false;
    }

    /**
     *
     */
    function GetComponents()
    {
        function GetModulesList($type = 'gadgets')
        {
            $modules = array();
            $mDir = JAWS_PATH . $type . DIRECTORY_SEPARATOR;
            if (!is_dir($mDir)) {
                return $modules;
            }
            $dir = scandir($mDir);
            foreach($dir as $file) {
                if ($file != '.' && $file != '..' && !strpos($file, '.php') && $file != '.svn') {
                    $modules[] = $file;
                }
            }
            asort($modules);
            return $modules;        
        }

        $components = array();
        $components[JAWS_COMMON] = array('Global', 'Date', 'Install', 'Upgrade');
        $components[JAWS_GADGET] = GetModulesList('gadgets');
        $components[JAWS_PLUGIN] = GetModulesList('plugins');
        return $components;
    }

    /**
     * Returns an array of module language data
     *
     * @access  public
     * @return  array   A list of module language string
     */
    function GetLangData($module, $type, $langTo, $langFrom)
    {
        switch ($type) {
            case JAWS_GADGET:
                $data_file = JAWS_DATA . "languages/$langTo/gadgets/$module.php";
                $orig_file = JAWS_PATH . "gadgets/$module/languages/$langTo.php";
                $from_file = JAWS_PATH . "gadgets/$module/languages/$langFrom.php";
                break;

            case JAWS_PLUGIN:
                $data_file = JAWS_DATA . "languages/$langTo/plugins/$module.php";
                $orig_file = JAWS_PATH . "plugins/$module/languages/$langTo.php";
                $from_file = JAWS_PATH . "plugins/$module/languages/$langFrom.php";
                $module = 'Plugins_' . $module;
                break;

            default:
                $data_file = JAWS_DATA . "languages/$langTo/$module.php";
                $orig_file = JAWS_PATH . "languages/$langTo/$module.php";
                $from_file = JAWS_PATH . "languages/$langFrom/$module.php";
        }

        $use_data_lang = $GLOBALS['app']->Registry->Get('/gadgets/Languages/use_data_lang') == 'true';
        if (!$use_data_lang || !file_exists($data_file)) {
            $data_file = $orig_file;
        }

        if (!file_exists($from_file)) {
            return false;
        }

        $data = array();
        if (file_exists($data_file)) {
            require_once $data_file;
            $contents = file_get_contents($data_file);
            $data['writable'] = is_writable($data_file);
            $data['file'] = $data_file;
        } else {
            $data['writable'] = is_writable(dirname($data_file));
            $data['file'] = dirname($data_file);
        }

        @require_once $from_file;
        $fromstrings = get_defined_constants();

        $global = JAWS_PATH . "languages/$langTo/Global.php";
        if (file_exists($global)) {
            @require_once $global;
        }

        if (defined('_' . strtoupper($langTo) . '_GLOBAL_LANG_DIRECTION')) {
            $data['lang_direction'] = constant('_' . strtoupper($langTo) . '_GLOBAL_LANG_DIRECTION');
        } else {
            $data['lang_direction'] = 'ltr';
        }

        // Metadata
        preg_match('/"Last-Translator:(.*)"/', isset($contents)?$contents:'', $res);
        $data['meta']['Last-Translator'] = !empty($res) ? trim($res[1]) : '';

        // Strings
        foreach ($fromstrings as $k => $v) {
            if (strpos($k, strtoupper("_{$langFrom}_{$module}")) === false) {
                continue;
            }
            $cons = str_replace('_' . strtoupper($langFrom) . '_', '', $k);
            $data['strings'][$cons][$langFrom] = $v;
            if (defined('_' . strtoupper($langTo) . '_' . $cons)) {
                $toValue = constant('_' . strtoupper($langTo) . '_' . $cons);
                if ($toValue == '') {
                    $toValue = EMPTY_STRING;
                }
                $data['strings'][$cons][$langTo] = $toValue;
            } else {
                $data['strings'][$cons][$langTo] = '';
            }
        }
        return $data;
    }

    /**
     * @access  public
     *
     * @return  boolean Success/Failure (Jaws_Error)
     */
    function SetLangData($module, $type, $langTo, $data = null)
    {
        $module_name = $module;
        switch ($type) {
            case JAWS_GADGET:
                $data_file = JAWS_DATA . "languages/$langTo/gadgets/$module.php";
                $orig_file = JAWS_PATH . "gadgets/$module/languages/$langTo.php";
                break;

            case JAWS_PLUGIN:
                $data_file = JAWS_DATA . "languages/$langTo/plugins/$module.php";
                $orig_file = JAWS_PATH . "plugins/$module/languages/$langTo.php";
                $module_name = 'Plugins_' . $module;
                break;

            default:
                $data_file = JAWS_DATA . "languages/$langTo/$module.php";
                $orig_file = JAWS_PATH . "languages/$langTo/$module.php";
        }

        $use_data_lang = $GLOBALS['app']->Registry->Get('/gadgets/Languages/use_data_lang') == 'true';
        if (!$use_data_lang) {
            $data_file = $orig_file;
        }

        $tpl = new Jaws_Template('gadgets/Languages/templates/');
        $tpl->Load('FileTemplate.html');
        $tpl->SetBlock('template');
        $tpl->SetVariable('project', $module_name);
        $tpl->SetVariable('language', strtoupper($langTo));            

        // Meta
        foreach ($data['meta'] as $k => $v) {
            $tpl->SetBlock('template/meta');
            $tpl->SetVariable('key', $k);
            $tpl->SetVariable('value', str_replace('"', '\"', $v));
            $tpl->ParseBlock('template/meta');
        }

        // Strings
        foreach ($data['strings'] as $k => $v) {
            if ($v == '') {
                continue;
            } elseif ($v === EMPTY_STRING) {
                $v = '';
            }

            $tpl->SetBlock('template/string');
            $tpl->SetVariable('key', '_' . strtoupper($langTo) . '_' . $k);

            $v = preg_replace("$\r\n|\n$", '\n', $v);
            $tpl->SetVariable('value', str_replace('"', '\"', $v));
            $tpl->ParseBlock('template/string');
        }

        $tpl->ParseBlock('template');

        // Writable
        if(file_exists($data_file)) {
            $writeable = Jaws_Utils::is_writable($data_file);
        } else {
            Jaws_Utils::mkdir(dirname($data_file), 3);
            $writeable = Jaws_Utils::is_writable(dirname($data_file));
        }

        if (!$writeable) {
            $GLOBALS['app']->Session->PushLastResponse(_t('LANGUAGES_NOT_PERMISSION'), RESPONSE_ERROR);
            return false;
        }

        if (Jaws_Utils::file_put_contents($data_file, $tpl->Get())) {
            $GLOBALS['app']->Session->PushLastResponse(_t('LANGUAGES_UPDATED', $module), RESPONSE_NOTICE);
            return true;
        } else {
            $GLOBALS['app']->Session->PushLastResponse(_t('LANGUAGES_NOT_UPDATED', $module), RESPONSE_ERROR);
            return false;
        }
    }
    
	/**
     * Save config settings
     *
     * @access  public
     * @param   string  $gadgets   Languages
     * @return  boolean Success/Failure
     */
    function SaveSettings($gadgets)
    {
        $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$language_choices = '';
		$language_visitor_choices = $GLOBALS['app']->Registry->Get('/gadgets/language_visitor_choices');
		foreach ($gadgets as $gadget => $g_value) {
			if (!in_array($gadget, explode(',', $language_visitor_choices)) && $g_value == '1') {
				if ($language_visitor_choices == '') {
					$language_choices = $gadget;
				} else {
					$language_choices = $language_visitor_choices.','.$gadget;
				}
			} else if (in_array($gadget, explode(',', $language_visitor_choices)) && $g_value == '0') {
				$language_choices = str_replace(','.$gadget, '', $language_visitor_choices);
			}
		}
		$res5 = $GLOBALS['app']->Registry->Set('/gadgets/language_visitor_choices', $language_choices);
		if ($res5 === true) {
            $GLOBALS['app']->Registry->Commit('Languages');
            $GLOBALS['app']->Registry->Commit('core');
            $GLOBALS['app']->ACL->Commit('core');
            return true;
        }
        return new Jaws_Error(_t('LANGUAGES_SETTINGS_CANT_UPDATE'), _t('LANGUAGES_NAME'));
    }
}
