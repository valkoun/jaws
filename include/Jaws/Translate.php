<?php
/**
 * Translate class
 *
 * @category   Languages
 * @package    Core
 * @author     Jorge A Gallegos <kad@gulags.org>
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Jon Wood <jon@substance-it.co.uk>
 * @copyright  2005-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */

define('JAWS_COMMON', 0);
define('JAWS_GADGET', 1);
define('JAWS_PLUGIN', 2);

class Jaws_Translate
{
    /**
     * Default language to use
     *
     * @access private
     * @var    string
     */
    var $_defaultLanguage = 'en';
    
    /**
     * Initializes the Translate
     */
    function Init($lang = 'en')
    {
        $this->_defaultLanguage = $lang;
        $this->LoadTranslation('Global');
        $this->LoadTranslation('Date');
    }

    /**
     * Set the default language to use
     *
     * @access  public
     * @param   string  $lang  Language to use
     */
    function SetLanguage($lang)
    {
        $this->_defaultLanguage = $lang;
    }

    /**
     * Translate a string.
     *
     * @access public
     * @static
     * @param string $string The ID of the string to translate.
     * @param array $replacements An array replacements to make in the string.
     * @return string The tranlsated string, with replacements made.
     */
    function Translate($lang, $string, $replacements = array())
    {
        $language = strtoupper($this->_defaultLanguage);
        // Quick hack until we can _ from the beginning of translation IDs.
        if ($string[0] != '_') {
            $string = '_' . $string;
        }

        $translated = '_' . (empty($lang) ? $language : strtoupper($lang)) . $string;
        $not_translated = true;
        if (defined($translated)) {
            $string = constant($translated);
            $not_translated = false;
        } elseif (defined('_EN' . $string)) {
            $string = constant('_EN' . $string);
        }

        $count = count($replacements);
        if ($count) {
            for ($i = 0; $i < $count; $i++) {
                $string = str_replace('{' . $i . '}', $replacements[$i], $string);
            }
        }

        if (strpos($string, '{') !== false) {
            $originalString = $string;
            $string = preg_replace('/[\s\{[0-9]+\}]*/', '', $string);
            if ($originalString != $string) {
                if (isset($GLOBALS['log'])) {
                    $GLOBALS['log']->Log(JAWS_LOG_DEBUG, 'A placeholder was not replaced while trying to translate ' . $string);
                }
            }
        }

        if ($not_translated && $string != '') {
            if ($string[0] == '_') {
                $string = substr($string, 1);
            }
            $string .= '*';
        }

        return $string;
    }

    /**
     * Loads a translation file.
     *
     * Loaded translations are kept in $GLOBALS['i18n'], so that they aren't
     * reloaded.
     *
     * @access public
     * @static
     * @param string module The translation to load.
     * @param string type   Type of modulr(JAWS_COMMON, JAWS_GADGET, JAWS_PLUGIN)
     * @return void
     */
    function LoadTranslation($module, $type = JAWS_COMMON, $lang = null)
    {
        $language = $this->_defaultLanguage;
        if ($module == 'Date' && isset($GLOBALS['app'])) {
            $language = $GLOBALS['app']->GetCalendarLanguage();
        }
        $language = empty($lang) ? $language : $lang;

        // Make sure the arrays are setup
        if (!isset($GLOBALS['i18n'])) {
            $GLOBALS['i18n'] = array();
        }

        if (!isset($GLOBALS['i18n'][$language])) {
            $GLOBALS['i18n'][$language] = array();
        }

        if (!isset($GLOBALS['i18n']['en'])) {
            $GLOBALS['i18n']['en'] = array();
        }

        // Only attempt to load a translation if it isn't already loaded.
        if (in_array(array($module, $type), $GLOBALS['i18n'][$language])) {
            return true;
        }

        switch ($type) {
            case JAWS_GADGET:
                $orig_i18n = JAWS_PATH . "gadgets/$module/languages/$language.php";
                $data_i18n = JAWS_DATA . "languages/$language/gadgets/$module.php";
                $fall_back = JAWS_PATH . "gadgets/$module/languages/en.php";
                break;

            case JAWS_PLUGIN:
                $orig_i18n = JAWS_PATH . "plugins/$module/languages/$language.php";
                $data_i18n = JAWS_DATA . "languages/$language/plugins/$module.php";
                $fall_back = JAWS_PATH . "plugins/$module/languages/en.php";
                break;

            default:
                $orig_i18n = JAWS_PATH . "languages/$language/$module.php";
                $data_i18n = JAWS_DATA . "languages/$language/$module.php";
                $fall_back = JAWS_PATH . "languages/en/$module.php";
        }

        
        if (file_exists($data_i18n)) {
            require_once $data_i18n;
            $GLOBALS['i18n'][$language][] = array($module, $type);
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_DEBUG, "Loaded translation for $module, language $language");
            }
        } elseif (file_exists($orig_i18n)) {
            require_once $orig_i18n;
            $GLOBALS['i18n'][$language][] = array($module, $type);
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_DEBUG, "Loaded translation for $module, language $language");
            }
        } else {
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_DEBUG, "No translation could be found for $module for language $language");
            }
        }

        if ($language != 'en') {
            if (file_exists($fall_back)) {
                require_once $fall_back;
                $GLOBALS['i18n']['en'][] = array($module, $type);
                if (isset($GLOBALS['log'])) {
                    $GLOBALS['log']->Log(JAWS_LOG_DEBUG, "Loaded fallback translation for $module, language en");
                }
            } else {
                if (isset($GLOBALS['log'])) {
                    $GLOBALS['log']->Log(JAWS_LOG_DEBUG, "No fallback translation could be found for $module for language en");
                }
            }
        }
    }
}

/**
 * Convenience function to translate strings.
 *
 * Passes it's arguments to Jaws_Translate::Translate to do the actual translation.
 *
 * @access public
 * @param string        string The string to translate.
 * @return string
 */
function _t($string)
{
    $args = array();
    if (func_num_args() > 1) {
        $args = func_get_args();

        // Argument 1 is the string to be translated.
        array_shift($args);
    }

    return isset($GLOBALS['app']->Translate)?
           $GLOBALS['app']->Translate->Translate(null, $string, $args) :
           $GLOBALS['i10n']->Translate(null, $string, $args);
}

/**
 * Convenience function to translate strings.
 *
 * Passes it's arguments to Jaws_Translate::Translate to do the actual translation.
 *
 * @access public
 * @param string        lang The language.
 * @param string        string The string to translate.
 * @return string
 */
function _t_lang($lang, $string)
{
    $args = array();
    if (func_num_args() > 2) {
        $args = func_get_args();

        // Argument 1th for lang and argument 2th is the string to be translated.
        array_shift($args);
        array_shift($args);
    }

    return isset($GLOBALS['app']->Translate)?
           $GLOBALS['app']->Translate->Translate($lang, $string, $args) :
           $GLOBALS['i10n']->Translate($lang, $string, $args);
}