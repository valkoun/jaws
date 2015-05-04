<?php
/**
 * Main application, the core ;-)
 *
 * @category   Application
 * @package    Core
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Helgi Þormar Þorbjörnsson <dufuz@php.net>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2005-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 * @TODO:	Static-ize URLs (jaws, data, full, etc) within methods
 * @TODO:	Store visitor language preference along with admin language in LoadDefaults
 * @TODO:	Use Jaws_Cache for all caching
 */
class Jaws
{
    /**
     * The main request's gadget
     * @var	string
     * @access	protected
     */
    var $_MainRequestGadget = '';

    /**
     * The main request's action
     * @var	string
     * @access	protected
     */
    var $_MainRequestAction = '';
    
	/**
     * The main request's id
     * @var	string
     * @access	protected
     */
    var $_MainRequestId = '';

    /**
     * The application's theme.
     * @var	string
     * @access	protected
     */
    var $_Theme = 'default';

    /**
     * The language the application is running in.
     * @var	string
     * @access	protected
     */
    var $_Language = 'en';

    /**
     * The calendar type.
     * @var	string
     * @access	protected
     */
    var $_CalendarType = 'Gregorian';

    /**
     * The calendar language the application is running in.
     * @var	string
     * @access	protected
     */
    var $_CalendarLanguage = 'en';

    /**
     * The editor application is using
     * @var	string
     * @access	protected
     */
    var $_Editor = null;

    /**
     * The user timezone
     * @var	string
     * @access	protected
     */
    var $_UserTimezone = null;

    /**
     * Browser flag
     * @var	string
     * @access	protected
     */
    var $_BrowserFlag = '';

    /**
     * Browser HTTP_ACCEPT_ENCODING
     * @var	string
     * @access	protected
     */
    var $_BrowserEncoding = '';

    /**
     * Should application use layout?
     * @var	boolean
     * @access	protected
     */
    var $_UseLayout = false;

    /**
     * Application is in stand alone mode
     * @var	boolean
     * @access	protected
     */
    var $_standAloneMode = false;

    /**
     * Store gadget object for later use so we aren't running
     * around with multiple copies
     * @var	array
     * @access	protected
     */
    var $_Gadgets = array();

    /**
     * Store what's on layout, so things are added only once
     * @var	array
     * @access	protected
     */
    var $_ItemsOnLayout = array();
    
    /**
     * Store DB queries
     * @var	array
     * @access	protected
     */
    var $_DBCache = array();
    
	/**
     *
     * @var	array
     * @access	protected
     */
    var $_Classes = array();

    /**
     * Does everything needed to get the application to a usable state.
     *
     * @access 	public
     * @return 	void
     */
    function Create()
    {
        $this->loadClass('UTF8', 'Jaws_UTF8');
        $this->loadClass('Translate', 'Jaws_Translate');
        $this->loadClass('Registry', 'Jaws_Registry');
        $this->Registry->Init();
        $this->InstanceSession();
  
        $this->loadDefaults();
        $this->Translate->Init($this->_Language);

        // This is needed for all gadgets
        require_once JAWS_PATH . 'include/Jaws/Gadget.php';
        require_once JAWS_PATH . 'include/Jaws/Template.php';

        $this->loadClass('Map', 'Jaws_URLMapping');
        $this->Map->Load();
    }

    /**
     * Visitor application preferences such as language, theme, editor, timezone, and calendar type.
     *
     * @access 	public
     * @category 	feature
     * @return 	void
     */
    function LoadDefaults()
    {
        if (APP_TYPE == 'web') {
            $cookie_precedence = ($this->Registry->Get('/config/cookie_precedence') == 'true');

            $this->_Theme            = $this->Session->GetAttribute('theme');
            $this->_CalendarLanguage = $this->Session->GetAttribute('calendarlanguage');
            $this->_Editor           = $this->Session->GetAttribute('editor');
            $this->_UserTimezone     = $this->Session->GetAttribute('timezone');
            if (is_null($this->_UserTimezone)) {
                if ($cookie_precedence && !is_null($this->Session->GetCookie('timezone'))) {
                    $this->_UserTimezone = $this->Session->GetCookie('timezone');
                } else {
                    $this->_UserTimezone = $this->Registry->Get('/config/timezone');
                }
            }
			$dst = date('I');
			$this->_UserTimezone = ($dst == 1 ? (string)(((int)$this->_UserTimezone) + 1) : $this->_UserTimezone);
			
            if (empty($this->_Theme)) {
                if ($cookie_precedence && $this->Session->GetCookie('theme')) {
                    $this->_Theme = $this->Session->GetCookie('theme');
                } else {
                    $this->_Theme = $this->Registry->Get('/config/theme');
                }
            }

            if (JAWS_SCRIPT == 'admin') {
                $userLanguage    = $this->Session->GetAttribute('language');
                $this->_Language = empty($userLanguage)? $this->Registry->Get('/config/admin_language') : $userLanguage;
            } elseif (JAWS_SCRIPT == 'index') {
                if ($cookie_precedence && $this->Session->GetCookie('language')) {
                    $this->_Language = $this->Session->GetCookie('language');
                } else {
                    $this->_Language = $this->Registry->Get('/config/site_language');
                }
            } else {
                $this->_Language = 'en';
            }

            if ($cookie_precedence && $this->Session->GetCookie('calendar_type')) {
                $this->_CalendarType = $this->Session->GetCookie('calendar_type');
            } else {
                $this->_CalendarType = $this->Registry->Get('/config/calendar_type');
            }

            if (empty($this->_CalendarLanguage)) {
                if ($cookie_precedence && $this->Session->GetCookie('calendar_language')) {
                    $this->_CalendarLanguage = $this->Session->GetCookie('calendar_language');
                } else {
                    $this->_CalendarLanguage = $this->Registry->Get('/config/calendar_language');
                }
            }
        } else {
            $this->_Theme    = $this->Registry->Get('/config/theme');
            if (JAWS_SCRIPT == 'admin') {
                $this->_Language = $this->Registry->Get('/config/admin_language');
            } elseif (JAWS_SCRIPT == 'index') {
                $this->_Language = $this->Registry->Get('/config/site_language');
            } else {
                $this->_Language = 'en';
            }

            $this->_CalendarType = $this->Registry->Get('/config/calendar_type');
            $this->_CalendarLanguage = $this->Registry->Get('/config/calendar_language');
        }

        if (empty($this->_Editor)) {
            $this->_Editor = $this->Registry->Get('/config/editor');
        }

        require_once 'Net/Detect.php';
        $bFlags = explode(',', $this->Registry->Get('/config/browsers_flag'));
        $this->_BrowserFlag = Net_UserAgent_Detect::getBrowser($bFlags);
    }

    /**
     * Setup the applications session.
     *
     * @access 	public
     * @return 	void
     */
    function InstanceSession()
    {
        require_once JAWS_PATH . 'include/Jaws/Session.php';
        $this->Session =& Jaws_Session::factory();
		$this->Session->Init();
    }

    /**
     * Setup the applications cache.
     *
     * @access 	public
     * @return 	void
     */
    function InstanceCache()
    {
        require_once JAWS_PATH . 'include/Jaws/Cache.php';
        $this->Cache =& Jaws_Cache::factory();
    }

    /**
     * Setup the applications Layout object.
     *
     * @access 	public
     * @return 	void
     */
    function InstanceLayout()
    {
        $this->loadClass('Layout', 'Jaws_Layout');
        $this->_UseLayout = true;
    }

    /**
     * Get the boolean answer if application is using a layout
     *
     * @access 	public
     * @return 	boolean
     */
    function IsUsingLayout()
    {
        return $this->_UseLayout;
    }

    /**
     * Get the boolean answer if application is standalone
     *
     * @access 	public
     * @return 	boolean
     */
    function IsStandAloneMode()
    {
        return $this->_standAloneMode;
    }

    /**
     * Set the boolean answer if application is standalone
     *
     * @access 	public
     * @param 	boolean	$mode	standalone mode (true or false)
     * @return 	void
     */
    function SetStandAloneMode($mode = false)
    {
        $this->_standAloneMode = $mode;
    }

    /**
     * Get default theme
     *
     * @access 	public
     * @return 	mixed	Array of theme info, or Jaws_Error on error
     */
    function GetTheme()
    {
        static $theme;
        if (!isset($theme)) {
            // Check if valid theme name
            if (strpos($this->_Theme, '..') !== false ||
                strpos($this->_Theme, '\\') !== false) {
                    return new Jaws_Error(_t('GLOBAL_ERROR_INVALID_NAME', 'GetTheme'), 'Getting theme name');
            }

            $theme = array();
            $theme['name'] = $this->_Theme;
            if (substr(strtolower($this->_Theme), 0, 4) == 'http') {
				if (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on' && substr(strtolower($this->_Theme), 0, 5) == 'http:') {
					$theme['path']	= $this->GetSiteURL('', false, 'https').'/gz.php?type=css&uri=' . urlencode($this->_Theme . '/');
					$theme['url']	= $this->GetSiteURL('', false, 'https').'/gz.php?type=css&uri=' . urlencode($this->_Theme . '/');
				} else {	
					$theme['path']	= $this->_Theme . '/';
					$theme['url']	= $this->_Theme . '/';
                }
				$theme['exists'] = true;
			} else {
				$theme['path'] = JAWS_DATA . 'themes/' . $this->_Theme . '/';
	            if (!is_dir($theme['path'])) {
                	$theme['url']    = $this->getDataURL('themes/' . $this->_Theme . '/', true, true);
                	$theme['path']   = JAWS_BASE_DATA .  'themes/' . $this->_Theme . '/';
                	$theme['exists'] = is_dir($theme['path']);
            	} else {
               		$theme['url']    = $this->getDataURL('themes/' . $this->_Theme . '/', true);
                	$theme['exists'] = true;
            	}			
			}
        }
        return $theme;
    }

    /**
     * Get default language
     *
     * @access 	public
     * @return 	string	The default language
     */
    function GetLanguage()
    {
        // Check if valid language name
        if (strpos($this->_Language, '..') !== false ||
            strpos($this->_Language, '%') !== false ||
            strpos($this->_Language, '\\') !== false ||
            strpos($this->_Language, '/') !== false) {
                return new Jaws_Error(_t('GLOBAL_ERROR_INVALID_NAME', 'GetLanguage'), 'Getting language name');
        }
        return $this->_Language;
    }

    /**
     * Get the default editor
     *
     * @access 	public
     * @return 	string	The default language
     */
    function GetEditor()
    {
        return $this->_Editor;
    }

    /**
     * Get Browser flag
     *
     * @access 	public
     * @return 	string	The type of browser
     */
    function GetBrowserFlag()
    {
        return $this->_BrowserFlag;
    }

    /**
     * Overwrites the default values the Application uses
     *
     * It overwrites the default values with the input values
     * (which should come in an array)
     *
     *  - Theme:            Array key should be named theme
     *  - Language:         Array key should be named language
     *  - CalendarType:     Array key should be named calendartype
     *  - CalendarLanguage: Array key should be named calendarlanguage
     *  - Editor:           Array key should be named editor
     *
     * In the case of Language and CalendarLanguage, if the new values are
     * different from the default ones (or the values that were already loaded)
     * we load the translation stuff again
     *
     * @access 	public
     * @param 	array   $defaults  New default values
     * @return 	void
     */
    function OverwriteDefaults($defaults) 
    {
        if (!is_array($defaults)) {
            return;
        }

        $loadLanguageAgain = false;
        foreach($defaults as $key => $value) {
            $key = strtolower($key);
            if (empty($value)) {
                continue;
            }

            switch($key) {
                case 'theme':
                    $this->_Theme = $value;
                    break;

                case 'language':
                    if ($this->_Language != $value) {
                        $loadLanguageAgain = true;
                        $this->_Language = $value;
                    }
                    break;

                case 'calendartype':
                    $this->_CalendarType = $value;
                    break;

                case 'calendarlanguage':
                    if ($this->_CalendarLanguage != $value) {
                        $loadLanguageAgain = true;
                        $this->_CalendarLanguage = $value;
                    }
                    break;

                case 'editor':
                    $this->_Editor = $value;
                    break;

                case 'timezone':
                    $dst = date('I');
					$this->_UserTimezone = ($dst == 1 ? (string)(((int)$value) + 1) : $value);
                    break;
            }
        }

        if ($loadLanguageAgain) {
            $this->Translate->Init($this->_Language);
        }
    }
    
    /**
     * Get the default Calendar type
     *
     * @access 	public
     * @return 	string	The default Calendar type
     */
    function GetCalendarType()
    {
        return $this->_CalendarType;
    }

    /**
     * Get the default Calendar language
     *
     * @access 	public
     * @return 	string	The default Calendar language
     */
    function GetCalendarLanguage()
    {
        return $this->_CalendarLanguage;
    }

    /**
     * Get the available authentication methods
     *
     * @access	public
     * @return 	array	Array with available authentication methods
     */
    function GetAuthMethods()
    {
        $path = JAWS_PATH . 'include/Jaws/AuthScripts';
        if (is_dir($path)) {
            $methods = array();
            $dir = scandir($path);
            foreach ($dir as $method) {
                if (stristr($method, '.php')) {
                    $method = str_replace('.php', '', $method);
                    $methods[$method] = $method;
                }
            }

            return $methods;
        }

        return false;
    }

    /**
     * Loads the gadget file in question, makes a instance and
     * stores it globally for later use so we do not have duplicates
     * of the same instance around in our code.
     *
     * @access 	public
     * @param 	string	$gadget	Name of the gadget
     * @param 	string	$filename	The file being loaded
     * @return 	object	Gadget instance or Jaws_Error on error
     */
    function LoadGadget($gadget, $filename = 'HTML')
    {
        $gadget   = urlencode(trim(strip_tags($gadget)));
        $filename = trim($filename);
        $gadgetname = $gadget . ucfirst($filename);
        $load_registry = true;
        if (!isset($this->_Gadgets[$gadget][$filename])) {
            switch ($filename) {
                case 'Info':
                    $load_registry = false;
                    if (!Jaws::classExists('Jaws_GadgetInfo')) {
                        require_once JAWS_PATH . 'include/Jaws/GadgetInfo.php';
                    }
                    break;
                case 'HTML':
                case 'AdminHTML':
                    if (!Jaws::classExists('Jaws_GadgetHTML')) {
                        require_once JAWS_PATH . 'include/Jaws/GadgetHTML.php';
                    }
                    break;
            }

            $file = JAWS_PATH . 'gadgets/' . $gadget . '/' . $filename . '.php';
            if (file_exists($file)) {
                include_once $file;
            }

            if (!Jaws::classExists($gadgetname)) {
                // return a error
                $error = new Jaws_Error(_t('GLOBAL_ERROR_CLASS_DOES_NOT_EXIST', $gadgetname), 'Gadget class check');
                return $error;
            }

            $obj = new $gadgetname();
			if (Jaws_Error::IsError($obj)) {
                $error = new Jaws_Error(_t('GLOBAL_ERROR_FAILED_CREATING_INSTANCE', $file, $gadgetname), 'Gadget file loading');
                return $error;
            }

            if ($load_registry && (!isset($this->_Gadgets[$gadget]) || !isset($this->_Gadgets[$gadget]['Registry']))) {
                $this->_Gadgets[$gadget]['Registry'] = true;
                if (isset($this->ACL)) {
                    $this->ACL->LoadFile($gadget);
                }
                $this->Registry->LoadFile($gadget);
            }

            if (in_array($filename, array('Model', 'AdminModel'))) {
                $obj->Init($gadget);
            }

            $this->_Gadgets[$gadget][$filename] = $obj;
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_DEBUG, 'Loaded gadget: ' . $gadget . ', File: ' . $filename);
            }
        }

        return $this->_Gadgets[$gadget][$filename];
    }

    /**
     * Set main request properties like gadget and action
     *
     * @access 	public
     * @param 	string  $gadget	Gadget's name
     * @param 	string  $action	Gadget's action
     * @param 	string  $id	Gadget's ID
     * @return 	void
     */
    function SetMainRequest($gadget, $action, $id = null)
    {
        $this->_MainRequestGadget = $gadget;
        $this->_MainRequestAction = $action;
        if (!is_null($id)) {
			$this->_MainRequestId = $id;
		}
	}

    /**
     * Get main request properties like gadget and action
     *
     * @access 	public
     * @return 	array	Array of main request information
     */
    function GetMainRequest()
    {
        return array('gadget' => $this->_MainRequestGadget,
                     'action' => $this->_MainRequestAction,
					 'id' => $this->_MainRequestId);
    }

    /**
     * Set true or false if a gadget has been updated so we don't check it again and again
     *
     * @access 	public
     * @param 	string  $gadget	Gadget's name
     * @param 	boolean	$status	True if gadget is updated (installed and latest version)
     * @return 	void
     */
    function SetGadgetAsUpdated($gadget, $status = true)
    {
        if (!empty($gadget) && !isset($this->_Gadgets[$gadget]['is_updated'])) {
            $this->_Gadgets[$gadget]['is_updated'] = $status;
        }
    }

    /**
     * Returns true or false is gadget has been marked as updated. If the gadget hasn't been marked
     * it returns null.
     *
     * @access 	public
     * @param 	string	$gadget	Gadget's name
     * @return 	mixed	boolean True if Gadget is marked, null otherwise
     */
    function IsGadgetMarkedAsUpdated($gadget)
    {
        if (!empty($gadget) && isset($this->_Gadgets[$gadget]['is_updated'])) {
            return $this->_Gadgets[$gadget]['is_updated'];
        }

        return null;
    }

    /**
     * Gets a list of installed gadgets (using Singleton), it uses
     * the /gadget/enabled_items
     *
     * @access 	public
     * @return 	array   Array of enabled_items (and updated)
     */
    function GetInstalledGadgets()
    {
        static $installedGadgets;

        if (isset($installedGadgets)) {
            return $installedGadgets;
        }
        $installedGadgets = array();

        $gs = explode(',', $GLOBALS['app']->Registry->Get('/gadgets/enabled_items'));
        $ci = explode(',', $GLOBALS['app']->Registry->Get('/gadgets/core_items'));
        $ci = str_replace(' ', '', $ci);
        $gs = array_merge($gs, $ci);

        if (count($gs) > 0) {
            foreach ($gs as $gadget) {
                if (file_exists(JAWS_PATH . 'gadgets/' . $gadget . '/Info.php')) {
                    if (Jaws_Gadget::IsGadgetUpdated($gadget)) {
                        $installedGadgets[$gadget] = $gadget;
                    }
                }
            }
        }

        return $installedGadgets;
    }

    /**
     * Loads the action file of a gadget
     *
     * @access 	public
     * @param 	string	$gadget	Gadget's name
     * @return 	void
     */
    function LoadGadgetActions($gadget)
    {
        if (!isset($this->_Gadgets[$gadget]['actions'])) {
            $file = JAWS_PATH . 'gadgets/' . $gadget . '/Actions.php';

            if (file_exists($file)) {
                $this->Translate->LoadTranslation($gadget, JAWS_GADGET);
                require_once $file;
                if (isset($actions)) {
                    $tmp = array();

                    // key: Action Name  value: Action Properties
                    foreach ($actions as $aName => $aProps) {
                        if (isset($aProps[2])) {
                            $name = isset($aProps[1]) ? $aProps[1] : '';
                        } else {
                            $name = $aName;
                        }

                        if (!isset($aProps[0])) {
                            $aProps[0] = 'NormalAction';
                        }
                        foreach (explode(",", $aProps[0]) as $type) {
                            $tmp[trim($type)][$aName] = array(
                                                            'name' => $name,
                                                            'mode' => trim($type),
                                                            'desc' => (isset($aProps[2])) ? $aProps[2] : ''
                                                        );
                        }
                    }
                    $this->_Gadgets[$gadget]['actions'] = $tmp;
                } else {
                    $this->_Gadgets[$gadget]['actions'] = array();
                }
            } else {
                $this->_Gadgets[$gadget]['actions'] = array();
            }
        }
		return null;
    }

    /**
     * Gets the actions of a gadget
     *
     * @access 	public
     * @param 	string  $gadget	Gadget's name
     * @return 	array   Gadget actions
     */
    function GetGadgetActions($gadget)
    {
        if (!isset($this->_Gadgets[$gadget]['actions'])) {
            $this->LoadGadgetActions($gadget);
        }
        return $this->_Gadgets[$gadget]['actions'];
    }

    /**
     * Prepares the Jaws Editor
     *
     * @access 	public
     * @param 	string	$gadget	Gadget that uses the editor (usable for plugins)
     * @param 	string  $name	Name of the editor
     * @param 	string  $value	Value of the editor/content (optional)
     * @param 	boolean  $filter	sanitize the content (optional)
     * @param 	string  $label	Label that the editor will have (optional)
     * @param 	boolean  $inplace	Is this an in-place editor? (optional)
     * @param 	string  $url	In-place URL to post updates (optional)
     * @param 	string  $inplace_options	In-place options javascript object (optional)
     * @return 	object  The editor in /config/editor
     */
    function &LoadEditor($gadget, $name, $value = '', $filter = true, $label = '', $inplace = false, $url = null, $inplace_options = null)
    {
        if ($filter && !empty($value)) {
            $xss   = $this->loadClass('XSS', 'Jaws_XSS');
            $value = $xss->filter($value);
        }

        $editor = $this->_Editor;
        $file   = JAWS_PATH . 'include/Jaws/Widgets/' . $editor . '.php';
        if (!file_exists($file)) {
            $editor = 'TextArea';
            $file   = JAWS_PATH . 'include/Jaws/Widgets/' . $editor . '.php';
        }
        $editorClass = "Jaws_Widgets_$editor";

        require_once $file;
		if ($editor == 'TinyMCE') {
			$editor = new $editorClass($gadget, $name, $value, $label, $inplace, $url, $inplace_options);
		} else {
			$editor = new $editorClass($gadget, $name, $value, $label);
		}

        return $editor;
    }

    /**
     * Loads the Jaws Date class.
     * Singleton approach.
     *
     * @access 	public
     * @return 	object	The Jaws date object
     */
    function LoadDate()
    {
        static $instances;
        if (!isset($instances)) {
            $instances = array();
        }

        $signature = serialize(array('date'));
        if (!isset($instances[$signature])) {
            include_once JAWS_PATH . 'include/Jaws/Date.php';
            $calendar = $this->GetCalendarType();
            $instances[$signature] =& Jaws_Date::factory($calendar);

            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_DEBUG, 'Date class is loaded');
            }
        }

        return $instances[$signature];

//         return $this->loadClass('Date', 'Jaws_Date');
    }

    /**
     * Loads a class from within the Jaws dir
     * 
     * @access 	public
     * @param 	string	$property	The property name to assign to
     * @param 	string	$class	The class name
     * @return 	object	The class object, or Jaws_Error on error
     */
    function LoadClass($property, $class)
    {
        if (!isset($this->{$property})) {
            $file = JAWS_PATH . 'include'. DIRECTORY_SEPARATOR . str_replace('_', DIRECTORY_SEPARATOR, $class).'.php';
            if (!file_exists($file)) {
                $error = new Jaws_Error(_t('GLOBAL_ERROR_FILE_DOES_NOT_EXIST', $file), 'File exists check');
                return $error;
            }

            include_once $file;

            if (!$this->classExists($class)) {
                $error = new Jaws_Error(_t('GLOBAL_ERROR_CLASS_DOES_NOT_EXIST', $class), 'Class exists check');
                return $error;
            }

            $this->{$property} = new $class();
            if (Jaws_Error::IsError($this->{$property})) {
                $error = new Jaws_Error(_t('GLOBAL_ERROR_FAILED_CREATING_INSTANCE', $file, $class), 'Class file loading');
                return $error;
            }

            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_DEBUG, 'Loaded class: ' . $class . ', File: ' . $file);
            }
        }

        return $this->{$property};
    }

    /**
     * Stub function for now, it will handle loading files with only include
     * kinda internal include_once
     */
    function LoadFile($path)
    {

    }

    /**
     * Verify if an image exists, if not returns a default image (unknown.png)
     *
     * @access 	public
     * @param 	string	$path	Image path
     * @param 	boolean	$check_thumb	Check for thumb Image
     * @param 	boolean	$check_medium	Check for medium Image
     * @return 	string	The original path if it exists or an unknown.png path
     */
    function CheckImage($path, $check_thumb = true, $check_medium = true)
    {
        if (is_file($path)) {
            return $path;
        } else if (is_file(JAWS_PATH . $path)) {
			return $GLOBALS['app']->GetJawsURL() . '/'. $path;
		} else if (substr(strtolower($path), 0, 15) == 'image_thumb.php' || substr(strtoupper($path), 0, 7) == "GADGET:") {
			return $path;
		} else {
			$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
			$image = $GLOBALS['app']->loadClass('Image', 'Jaws_Image');
			$path = $xss->filter(strip_tags($path));
			if (substr(strtolower($path), 0, 4) == "http") {
				if (substr(strtolower($path), 0, 7) == "http://") {
					$path = explode('http://', $path);
					foreach ($path as $img_src) {
						if (!empty($img_src)) {
							return 'http://'.$img_src;
						}
					}
				} else {
					$path = explode('https://', $path);
					foreach ($path as $img_src) {
						if (!empty($img_src)) {
							return 'https://'.$img_src;
						}
					}
				}
				if (strpos(strtolower($path), 'data/files/') !== false || strpos(strtolower($path), 'data/themes/') !== false) {
					return $GLOBALS['app']->GetSiteURL() . '/image_thumb.php?uri='.urlencode($path);
				}
			} else {
				if ($check_thumb === true) {
					$thumb = $image->GetThumbPath($path);
					if (file_exists(JAWS_DATA . 'files'.$thumb)) {
						return $GLOBALS['app']->getDataURL() . 'files'.$thumb;
					}
				}
				if ($check_medium === true) {
					$medium = $image->GetMediumPath($path);
					if (file_exists(JAWS_DATA . 'files'.$medium)) {
						return $GLOBALS['app']->getDataURL() . 'files'.$medium;
					}
				}
				if (file_exists(JAWS_DATA . 'files'.$path)) {
					return $GLOBALS['app']->getDataURL() . 'files'.$path;
				}
			}
		}

        return 'images/unknown.png';
    }

    /**
     * Returns the current URI location (without BASE_SCRIPT)
     *
     * @access 	public
     * @return 	string	Current URI location
     */
    function GetURILocation()
    {
        static $location;

        if (isset($location)) {
            return $location;
        }

        $xss = $this->loadClass('XSS', 'Jaws_XSS');
        //TODO: Need to check which SERVER var is always sent to the server
        if (!isset($_SERVER['REQUEST_URI']) || empty($_SERVER['REQUEST_URI'])) {
            $location = $xss->filter($_SERVER['SCRIPT_NAME']);
        } else {
            $location = $xss->filter($_SERVER['REQUEST_URI']);
        }
        $location = substr($location, 0, stripos($location, BASE_SCRIPT));
        return $location;
    }

    /**
     * Returns the URL of the site
     *
     * @access 	public
     * @param 	string	$suffix	url of jaws instance
     * @param 	boolean	$rel_url	relative url
     * @param 	boolean	$force_scheme	force https?
     * @return 	string	Site's URL
     */
    function GetSiteURL($suffix = '', $rel_url = false, $force_scheme = null)
    {
        //static $site_url;
        //if (!isset($site_url)) {
 			$site_url = '';
			$site_ssl_url = '';
			if (isset($GLOBALS['app']->Registry)) {
				$site_url = $GLOBALS['app']->Registry->Get('/config/site_url');
				$site_ssl_url = $GLOBALS['app']->Registry->Get('/config/site_ssl_url');
			}
			$cfg_url = (!empty($site_ssl_url) && ((isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on' && $force_scheme != 'http') || $force_scheme == 'https') ? $site_ssl_url : (!empty($site_url) ? $site_url : ''));
            
			if (!empty($cfg_url)) {
				$cfg_url = str_replace(array('http://','https://'), '', strtolower($cfg_url));
				$cfg_url = (strpos($cfg_url, '/') !== false ? substr($cfg_url, 0, strpos($cfg_url, '/')) : $cfg_url); 
			}

			$site_url = array();
			$site_url['scheme'] = ((!empty($site_ssl_url) && (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on' && $force_scheme != 'http') || $force_scheme == 'https') ? 'https' : 'http');
			$host = (!empty($cfg_url) ? $cfg_url : $_SERVER['SERVER_NAME']);
			$site_url['host'] = $host;
			$site_url['port'] = (isset($_SERVER["SERVER_PORT"]) && ((int)$_SERVER["SERVER_PORT"] == 80 || (int)$_SERVER["SERVER_PORT"] == 443) ? '' : (isset($_SERVER["SERVER_PORT"]) ? ':'.$_SERVER["SERVER_PORT"] : ''));
			$path = strip_tags($_SERVER['PHP_SELF']);
			if (false === strpos($path, BASE_SCRIPT)) {
				$path = strip_tags($_SERVER['SCRIPT_NAME']);
			}
			$site_url['path'] = substr($path, 0, strpos($path, BASE_SCRIPT)-1);
        //}

        $url = $site_url['path'];
        if (!$rel_url) {
            $url = $site_url['scheme'] . '://' . $site_url['host'] . (isset($site_url['port']) && $site_url['port'] != '' ? $site_url['port'] : '') . $url;
        }

        if (substr($url, -1) == '/') {
            $url = substr($url, 0, -1);
        }
		$data_path = preg_quote('/data/xmlrpc/', '/');
		$url = preg_replace("/$data_path([^>]*)/i", '', $url); 
		$url = str_replace(array(':80',':443'), '', $url) . (is_bool($suffix)? '' : $suffix);
		return $url;
    }

    /**
     * Returns the URL of the data
     *
     * @access 	public
     * @param 	string	$suffix	suffix part of url
     * @param 	boolean	$full_url	full url(not relative url)
     * @param 	boolean $base_data	use JAWS_BASE_DATA instead of JAWS_DATA
     * @param 	boolean	$https	force HTTPS scheme
     * @return 	string  Data's URL
     */
    function GetDataURL($suffix = '', $full_url = false, $base_data = false, $https = false)
    {
        if ((isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on') || $https === true) {
	        if (!defined('JAWS_DATA_SSL_URL') || $base_data) {
	            $url = (strpos(JAWS_BASE_DATA, JAWS_PATH) !== false && strpos(JAWS_DATA, JAWS_PATH) !== false ? substr($base_data? JAWS_BASE_DATA : JAWS_DATA, strlen(JAWS_PATH)) : JAWS_DATA);
	            if (DIRECTORY_SEPARATOR !='/') {
	                $url = str_replace('\\', '/', $url);
	            }
				$url = substr($url, (!defined('JAWS_DATA_SSL_URL') ? strpos($url, 'data') : strpos($url, 'data')), strlen($url));
	            if ($full_url) {
	                $url = $this->getSiteURL('/' . $url, false, 'https');
	            }
	        } else {
	            $url = JAWS_DATA_SSL_URL;
	        }
		} else {
			if (!defined('JAWS_DATA_URL') || $base_data) {
	            $url = (strpos(JAWS_BASE_DATA, JAWS_PATH) !== false && strpos(JAWS_DATA, JAWS_PATH) !== false ? substr($base_data? JAWS_BASE_DATA : JAWS_DATA, strlen(JAWS_PATH)) : JAWS_DATA);
	            if (DIRECTORY_SEPARATOR !='/') {
	                $url = str_replace('\\', '/', $url);
	            }
				$url = substr($url, (!defined('JAWS_DATA_URL') ? strpos($url, 'data') : strpos($url, 'data')), strlen($url));
				if ($full_url) {
	                $url = $this->getSiteURL('/' . $url, false, 'http');
	            }
	        } else {
	            $url = JAWS_DATA_URL;
	        }		
		}	
        if (substr($url, -1) != '/') {
            $url = $url . '/';
        }
        return $url . (is_bool($suffix)? '' : $suffix);
    }

    /**
     * Returns the URL of the Jaws Path
     *
     * @access 	public
     * @param 	boolean	$https	force HTTPS scheme
     * @return 	string	Jaws Path URL
     */
    function GetJawsURL($https = false)
    {
        if ((isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on') || $https) {
			if (!defined('JAWS_SSL_URL')) {
	            $url = $this->getSiteURL('', false, 'https');
				define('JAWS_SSL_URL', $url);
	        } else {
	            return JAWS_SSL_URL;
	        }
		} else {
			if (!defined('JAWS_URL')) {
				$url = $this->getSiteURL('', false, 'http');
	            define('JAWS_URL', $url);
	        } else {
	            return JAWS_URL;
	        }		
		}	
		return $url;
    }

    /**
     * Returns the current full request URL
     *
     * @access 	public
     * @return 	string	current full request URL
     */
    function GetFullURL()
    {
		if (!isset($_SERVER['FULL_URL']) || empty($_SERVER['FULL_URL'])) {
			$scheme = (isset($_SERVER["HTTPS"]) && strtolower($_SERVER["HTTPS"]) == "on") ? "https" : "http"; 
			$port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"]); 
			$full_url = $scheme."://".$_SERVER['SERVER_NAME'].$port.$_SERVER['REQUEST_URI']; 
		} else {
			$full_url = $_SERVER['FULL_URL'];
		}
		if (!empty($full_url)) {
			$full_url = str_replace(array('www.', ':80', ':443'), '', $full_url);
		} else {
			Jaws_Error::Fatal("Current URL could not be determined.");
		}
        
		return $full_url;
    }

    /**
     * Gadgets can autoload some actions before each page load.
     *
     * @access 	public
     * @category 	developer_feature
     * @return 	void
     */
    function RunAutoload()
    {
        $data    = $GLOBALS['app']->Registry->Get('/gadgets/autoload_items');
        $gadgets = array_filter(explode(',', $data));
        foreach($gadgets as $gadgetName) {
            $gadget = $this->loadGadget($gadgetName, 'Autoload');
            if (!Jaws_Error::isError($gadget)) {
                if (method_exists($gadget, 'Execute')) {
                    $gadget->Execute();
                }
            }
        }
    }

    /**
     * Gadgets can provide hooks for cross-Gadget data sharing.
     *
     * @access 	public
     * @category 	developer_feature
     * @param 	string  $gadget	Gadget we want to load (where the hook is)
     * @param 	string  $hook	Gadget hook (the hook name)
     * @return 	mixed	object Gadget's hook if it exists or boolean false
     */
    function LoadHook($gadget, $hook)
    {
        $hookName = $gadget.$hook.'Hook';
        if (!isset($this->_Classes[$hookName])) {
            $hookFile = JAWS_PATH . 'gadgets/' . $gadget . '/hooks/' . $hook . '.php';
            if (file_exists($hookFile)) {
                include_once $hookFile;
            }

            if (!Jaws::classExists($hookName)) {
                return false;
            }

            $obj = new $hookName();
            $this->_Classes[$hookName] = $obj;
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_DEBUG, 'Loaded hook: ' . $hook . ' of gadget '. $gadget. ', File: ' . $hookFile);
            }
        }
        return $this->_Classes[$hookName];
    }

    /**
     * Checks if a class exists without triggering __autoload
     *
     * @access 	public
     * @param 	string  $classname Class name
     * @return 	boolean	true on success and false on error
     */
    function ClassExists($classname)
    {
        if (version_compare(PHP_VERSION, '5.0', '>=')) {
            return class_exists($classname, false);
        }
        return class_exists($classname);
    }

    /**
     * Get Browser accept encoding
     *
     * @access 	public
     * @return 	string	The type of browser
     */
    function GetBrowserEncoding()
    {
        return $this->_BrowserEncoding;
    }

    /**
     * Use native gzip compression?
     *
     * @access 	private
     * @return 	boolean	true on success or false on error
     */
    function GZipEnabled()
    {
        static $_GZipEnabled;
        if (!isset($_GZipEnabled)) {
            $this->_BrowserEncoding = (isset($_SERVER['HTTP_ACCEPT_ENCODING']) ? $_SERVER['HTTP_ACCEPT_ENCODING'] : '');
            $this->_BrowserEncoding = strtolower($this->_BrowserEncoding);
            $_GZipEnabled = true;
            if (($this->Registry->Get('/config/gzip_compression') != 'true') ||
                !extension_loaded('zlib') ||
                ini_get('zlib.output_compression') ||
                (ini_get('zlib.output_compression_level') > 0) ||
                (ini_get('output_handler') == 'ob_gzhandler') ||
                (ini_get('output_handler') == 'mb_output_handler') ||
                (strpos($this->_BrowserEncoding, 'gzip') === false))
            {
                $_GZipEnabled = false;
            }
        }

        return $_GZipEnabled;
    }

    /**
     * Is actual agent a robot?
     *
     * @access 	private
     * @return 	boolean	true on success or false on error
     */
    function IsAgentRobot()
    {
        static $_IsRobot;
        if (!isset($_IsRobot)) {
            $_IsRobot = false;
            $robots = explode(',', $this->Registry->Get('/config/robots'));
            $robots = array_map('strtolower', $robots);
            $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
            $uagent = strtolower($GLOBALS['app']->XSS->parse($_SERVER['HTTP_USER_AGENT']));
            $ipaddr = $_SERVER['REMOTE_ADDR'];
            foreach($robots as $robot) {
                if (!empty($robot) && (($ipaddr == $robot) || (strpos($uagent, $robot) !== false))) {
                    $_IsRobot = true;
                    break;
                }
            }
        }

        return $_IsRobot;
    }

    /**
     * Converts UTC time to user's time, with timezone offset
     *
     * @access 	private
     * @param 	mixed	$time	timestamp
     * @param 	string	$format	date format
     * @return 	datetime	User's Time
     */
    function UTC2UserTime($time = '', $format = '')
    {
		if(empty($time)) {
			//if (!function_exists("date_default_timezone_set") || !function_exists("date_default_timezone_get")) {
				$utc_str = gmdate("M d Y H:i:s", time());
				$time = strtotime($utc_str);
			/*
			} else {
				$time = time();
			}
			*/
		}
        $time = is_numeric($time)? $time : strtotime($time);
        $time = $time + ($this->_UserTimezone * 3600);
		return empty($format)? $time : date($format, $time);
    }

    /**
     * Converts user time to UTC time, with timezone offset.
     *
     * @access 	private
     * @param 	mixed	$time	timestamp
     * @param 	string	$format	date format
     * @return 	datetime	UTC Time
     */
    function UserTime2UTC($time, $format = '')
    {
        $time = is_numeric($time)? $time : strtotime($time);
        $time = $time - ($this->_UserTimezone * 3600);
        return empty($format)? $time : date($format, $time);
    }

    /**
     * Gets the entire querystring without the gadget and action
     *
     * @access 	public
     * @param 	boolean	$embedded	Remove embed Gadget querystrings
     * @return 	string	Querystring.
     */

	function GetQuery($embedded = false) {
		$url_Query = '';
		if (isset($_SERVER['QUERY_STRING'])) {
			$xss = $this->loadClass('XSS', 'Jaws_XSS');
	        $queryString = $xss->parse($_SERVER['QUERY_STRING']);
			//parse the query strings and strip out "gadget" and "action"
			if (!empty($queryString)) {
				$url_Query = $queryString;
				//echo "full::::".$url_Query;
				
				$request =& Jaws_Request::getInstance();
	   			$fetch = array('action', 'gadget', 'embedgadget', 'embedaction', 'embedmode', 'embedbw', 'embedbstr', 'embedref', 'embedcss', 'embedid');
				$get  = $request->getRaw($fetch, 'get');

				if (isset($get['gadget'])) {
					$url_App = $get['gadget'];
					$url_Query = substr($url_Query, 7 + strlen($url_App), strlen($url_Query));
				//echo "<br>without app::::".$url_Query;
				}
				if (isset($get['action'])) {
					$url_Action = $get['action'];
					$url_Query = substr($url_Query, 8 + strlen($url_Action), strlen($url_Query));
				//echo "<br>without action::::".$url_Query;
				}
				if ($embedded === true) {
					if (isset($get['embedgadget']) && strpos($url_Query, $get['embedgadget']) !== false) {
						$url_QueryBefore = substr($url_Query, 0, strpos($url_Query, $get['embedgadget'])-13);
						$url_QueryAfter = substr($url_Query, strlen($get['embedgadget']) + strpos($url_Query, $get['embedgadget']), strlen($url_Query));
						$url_Query = $url_QueryBefore.$url_QueryAfter;
					//echo "<br>without app::::".$url_Query;
					}
					if (isset($get['embedaction']) && strpos($url_Query, $get['embedaction']) !== false) {
						$url_QueryBefore = substr($url_Query, 0, strpos($url_Query, $get['embedaction'])-13);
						$url_QueryAfter = substr($url_Query, strlen($get['embedaction']) + strpos($url_Query, $get['embedaction']), strlen($url_Query));
						$url_Query = $url_QueryBefore.$url_QueryAfter;
					//echo "<br>without action::::".$url_Query;
					}				
					if (isset($get['embedmode']) && strpos($url_Query, $get['embedmode']) !== false) {
						$url_QueryBefore = substr($url_Query, 0, strpos($url_Query, $get['embedmode'])-11);
						$url_QueryAfter = substr($url_Query, strlen($get['embedmode']) + strpos($url_Query, $get['embedmode']), strlen($url_Query));
						$url_Query = $url_QueryBefore.$url_QueryAfter;
					//echo "<br>without action::::".$url_Query;
					}		
					if (isset($get['embedbw']) && strpos($url_Query, $get['embedbw']) !== false) {
						$url_QueryBefore = substr($url_Query, 0, strpos($url_Query, $get['embedbw'])-9);
						$url_QueryAfter = substr($url_Query, strlen($get['embedbw']) + strpos($url_Query, $get['embedbw']), strlen($url_Query));
						$url_Query = $url_QueryBefore.$url_QueryAfter;
					//echo "<br>without action::::".$url_Query;
					}		
					if (isset($get['embedbstr']) && strpos($url_Query, $get['embedbstr']) !== false) {
						$embedbstr = $get['embedbstr'];
						$url_QueryBefore = substr($url_Query, 0, strpos($url_Query, $embedbstr)-11);
						$url_QueryAfter = substr($url_Query, strlen($embedbstr) + strpos($url_Query, $embedbstr), strlen($url_Query));
						$url_Query = $url_QueryBefore.$url_QueryAfter;
					//echo "<br>without action::::".$url_Query;
					}		
					if (isset($get['embedref']) && strpos($url_Query, $get['embedref']) !== false) {
						$url_QueryBefore = substr($url_Query, 0, strpos($url_Query, $get['embedref'])-10);
						$url_QueryAfter = substr($url_Query, strlen($get['embedref']) + strpos($url_Query, $get['embedref']), strlen($url_Query));
						$url_Query = $url_QueryBefore.$url_QueryAfter;
					//echo "<br>without action::::".$url_Query;
					}		
					if (isset($get['embedcss']) && strpos($url_Query, $get['embedcss']) !== false) {
						$url_QueryBefore = substr($url_Query, 0, strpos($url_Query, $get['embedcss'])-10);
						$url_QueryAfter = substr($url_Query, strlen($get['embedcss']) + strpos($url_Query, $get['embedcss']), strlen($url_Query));
						$url_Query = $url_QueryBefore.$url_QueryAfter;
					//echo "<br>without action::::".$url_Query;
					}		
					if (isset($get['embedid']) && strpos($url_Query, $get['embedid']) !== false) {
						$url_QueryBefore = substr($url_Query, 0, strpos($url_Query, $get['embedid'])-9);
						$url_QueryAfter = substr($url_Query, strlen($get['embedid']) + strpos($url_Query, $get['embedid']), strlen($url_Query));
						$url_Query = $url_QueryBefore.$url_QueryAfter;
					//echo "<br>without action::::".$url_Query;
					}		
				}
			}
			//echo "<br>query::::".$url_Query;
		}	
		return $url_Query;
	}

    /**
     * Return cache filename
     *
     * @access 	public
     * @param 	string  $file	filename
     * @param 	string  $gadget	Gadget scope
     * @return 	mixed	the cache filename or false if doesn't exist.
     */
    function GetSyntactsCacheFile($file = null, $gadget = '')
    {
		if (!is_null($file)) {
        	$filename = $file;
			$cache_id = $gadget."_".md5($filename);
			
			return $cache_id;
		}
		return false;
	}
	
	
    /**
     * Delete cache files by gadget scope
     *
     * @access 	public
     * @param 	array	$gadget  Gadget scope to delete
     * @return 	boolean	true on success or false on error
     */
    function DeleteSyntactsCacheFile($gadgets)
    {
		if (is_array($gadgets)) {
			foreach ($gadgets as $gadget) {
				$cache_gadget = $gadget.'_';
				if ($foldername = opendir(JAWS_DATA . "cache" . DIRECTORY_SEPARATOR . "apps")) {
				  while (false !== ($filename = readdir($foldername))) {
					if ($filename != "." && $filename != "..") {
						if (substr($filename, 0, strlen($cache_gadget)) == $cache_gadget) {
							if (!Jaws_Utils::Delete(JAWS_DATA . "cache" . DIRECTORY_SEPARATOR . "apps" . DIRECTORY_SEPARATOR . $filename, false)) {
								return false;
								$f = Jaws_Utils::strxchr($filename, ".", 0, 1);
							}
						}
						if ($gadget == 'CustomPage') {
							if (strpos($filename, '_') == 1) {
								if (!Jaws_Utils::Delete(JAWS_DATA . "cache" . DIRECTORY_SEPARATOR . "apps" . DIRECTORY_SEPARATOR . $filename, false)) {
									return false;
								}
							}
						}
						$f = $GLOBALS['app']->UTF8->strxchr($filename, ".", 0, 1);
					}
				  }
				}
			}
		}
		return true;
	}

    /**
     * Write some content for caching
     *
     * @access 	public
     * @param 	string	$file	filename
     * @param 	string  $content	content of cache file
     * @param 	string  $gadget	gadget scope
     * @return 	boolean	true on success or false on error
     */
    function WriteSyntactsCacheFile($file = null,  $content = '', $gadget = '')
    {
		if (!is_null($file)) {
			$filename = $file;

			$content = str_replace("<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />", 
			"  <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n" . 
			"  <meta name=\"version\" content=\"cached\" />",
			$content);

			$cache_id = $gadget."_".md5($filename);
	
			if (file_put_contents(JAWS_DATA . "cache" . DIRECTORY_SEPARATOR . "apps" . DIRECTORY_SEPARATOR . $cache_id .".php", $content)) {
				return true;
			}
		}
		return false;
	}
	
    /**
     * Return the Syntacts admin asp page to include
     *
     * @access 	public
     * @param 	string	$file  Syntacts App file
     * @return 	string	The Syntacts asp page to pass to the include_asp function
     */
    function GetSyntactsAdminUrl($file = null, $query = null)
    {
		$url_Query = $this->getQuery();
		if (!is_null($file)) {
			if (!is_null($query)) {
				return "http://67.211.19.180/apps/".$file.".asp?".$query.$url_Query;
			} else {	
				return "http://67.211.19.180/apps/".$file.".asp?".$url_Query;
			}
        }
        $request =& Jaws_Request::getInstance();
        $fetch  = array('action', 'gadget');
        $get  = $request->getRaw($fetch, 'get');
		$action = '';
		if ($get['action'] != '' && $get['action'] != 'Admin') {
			$action = '_'.$get['action'];
		}

		if (!is_null($query)) {
			return "http://67.211.19.180/apps/".$get['gadget']."/admin_".$get['gadget'].$action.".asp?".$query.$url_Query;
		} else {	
			return "http://67.211.19.180/apps/".$get['gadget']."/admin_".$get['gadget'].$action.".asp?".$url_Query;
		}
	}

    /**
     * Return the Syntacts asp page to include
     *
     * @access 	public
     * @param 	string	$file	Syntacts App file
     * @param 	string  $query	query_string starting with "&" (i.e. "&foo=1&bar=2")
     * @return 	string   The Syntacts asp page URL
     */
    function GetSyntactsUrl($file = null, $query = null)
    {
		$url_Query = $this->getQuery();
		if (!is_null($file)) {
			if (!is_null($query)) {
				return "http://67.211.19.180/apps/".$file.".asp?".$query.$url_Query;
			} else {	
				return "http://67.211.19.180/apps/".$file.".asp?".$url_Query;
			}
		}
		$request =& Jaws_Request::getInstance();
        $fetch  = array('action', 'gadget');
        $get  = $request->getRaw($fetch, 'get');
		$action = '';
		if ($get['action'] != '') {
			$action = '_'.$get['action'];
		}

		if (!is_null($query)) {
			return "http://67.211.19.180/apps/".$get['gadget']."/".$get['gadget'].$action.".asp?".$query.$url_Query;
		} else {	
			return "http://67.211.19.180/apps/".$get['gadget']."/".$get['gadget'].$action.".asp?".$url_Query;
		}
	}

    /**
     * Return the Syntacts admin HTML page to include
     *
     * @access 	public
     * @param 	string	$file	Syntacts App file
     * @param 	string	$file_ext	file extension
     * @return 	string	The Syntacts HTML page URL
     */
    function GetSyntactsAdminHTMLUrl($file = null, $file_ext = 'html')
    {
		if (!is_null($file)) {
			return "http://67.211.19.180/apps/".$file.".".$file_ext;
        }
        $request =& Jaws_Request::getInstance();
        $fetch  = array('action', 'gadget');
        $get  = $request->getRaw($fetch, 'get');
		$action = '';
		if ($get['action'] != '' && $get['action'] != 'Admin') {
			$action = '_'.$get['action'];
		}

		return "http://67.211.19.180/apps/".$get['gadget']."/admin_".$get['gadget'].$action.".".$file_ext;
	}

    /**
     * Return the Syntacts HTML page to include
     *
     * @access 	public
     * @param 	string	$file	Syntacts App file
     * @param 	string	$file_ext	file extension
     * @return 	string   The Syntacts HTML page URL
     */
    function GetSyntactsHTMLUrl($file = null, $file_ext = 'html')
    {
		if (!is_null($file)) {
			return "http://67.211.19.180/apps/".$file.".".$file_ext;
		}
		$request =& Jaws_Request::getInstance();
        $fetch  = array('action', 'gadget');
        $get  = $request->getRaw($fetch, 'get');
		$action = '';
		if ($get['action'] != '') {
			$action = '_'.$get['action'];
		}

		return "http://67.211.19.180/apps/".$get['gadget']."/".$get['gadget'].$action.".".$file_ext;
	}
	
    /**
     * Return keyword from Menu gadget that we are on
     *
     * @access 	public
     * @return 	string   keyword
     */
    function GetCurrentKeyword()
    {
		$kw = '';
		if (JAWS_SCRIPT == 'index') {
			$request =& Jaws_Request::getInstance();
			$fetch = array('gadget', 'action');
			$get  = $request->getRaw($fetch, 'get');
			
			$full_url = $_SERVER['SCRIPT_NAME'];
			if ($_SERVER['QUERY_STRING'] > ' ') { 
				$full_url .= '?'.$_SERVER['QUERY_STRING'];
			} else { 
				$full_url .=  '';
			}
			if (substr($full_url, 0, 1) == '/') {
				$full_url = substr($full_url, 1, strlen($full_url));
			}
			$sql  = 'SELECT [title] FROM [[menus]] WHERE ([url] LIKE {url})';
			$parentMenu = $GLOBALS['db']->queryRow($sql, array('url' => $full_url.'%'));
			if (!Jaws_Error::IsError($parentMenu) && !empty($parentMenu)) {
				$kw = $parentMenu['title'];
			}
			if ($kw == '') {
				if (!empty($get['action'])) {
					$sql  = 'SELECT [alias_url] FROM [[url_aliases]] WHERE ([real_url] LIKE {real_url})';
					$alias = $GLOBALS['db']->queryRow($sql, array('real_url' => $full_url.'%'));
					if (!Jaws_Error::IsError($alias) && !empty($alias)) {
						$sql2  = 'SELECT [title] FROM [[menus]] WHERE [url] LIKE {url}';
						$parentMenu = $GLOBALS['db']->queryRow($sql2, array('url' => $alias['alias_url'].'%'));
						if (!Jaws_Error::IsError($parentMenu) && !empty($parentMenu)) {
							$kw = $parentMenu['title'];
						}
					}
				}
			}
		}
		return $kw;
	}
	
    /**
     * Rebuild the Jaws Cache
     *
     * @access 	public
     * @param 	boolean	echo	echo response?
     * @return 	string	Response or error messages
     */
    function RebuildJawsCache($echo = true)
    {
		$error_array = array();

		// Delete all currently cached items
		$GLOBALS['app']->Registry->deleteCacheFile('core');
		$GLOBALS['app']->Registry->_regenerateInternalRegistry('core');
				
		$jms = $this->LoadGadget('Jms', 'AdminModel');
		$urlmapping = $this->LoadGadget('UrlMapper', 'AdminModel');
		$gadget_list = $jms->GetGadgetsList();
		//Hold.. if we dont have a selected gadget?.. like no gadgets?
		if (!count($gadget_list) <= 0) {
			reset($gadget_list);
			foreach ($gadget_list as $gadget) {
				$urlmapping->UpdateGadgetMaps($gadget['realname']);
				$GLOBALS['app']->Registry->deleteCacheFile($gadget['realname']);
				$GLOBALS['app']->Registry->_regenerateInternalRegistry($gadget['realname']);
			}
		}
		
		if ($foldername = @opendir(JAWS_DATA . "cache" . DIRECTORY_SEPARATOR . "apps")) {
		  while (false !== ($filename = readdir($foldername))) {
			if ($filename != "." && $filename != ".." && substr($filename, 0, 7) != 'cities_') {
				if (!Jaws_Utils::Delete(JAWS_DATA . "cache" . DIRECTORY_SEPARATOR . "apps" . DIRECTORY_SEPARATOR . $filename, false)) {
					array_merge('Filename: apps/'.$filename.', could not be deleted from cache<br />', $error_array); 
				}
			}
		  }
		  closedir($foldername);
		} 
		
		if ($foldername = @opendir(JAWS_DATA . "cache" . DIRECTORY_SEPARATOR . "images")) {
		  while (false !== ($filename = readdir($foldername))) {
			if ($filename != "." && $filename != "..") {
				if (!Jaws_Utils::Delete(JAWS_DATA . "cache" . DIRECTORY_SEPARATOR . "images" . DIRECTORY_SEPARATOR . $filename, false)) {
					array_merge('Filename: images/'.$filename.', could not be deleted from cache<br />', $error_array); 
				}
			}
		  }
		  closedir($foldername);
		} 
		
		if ($foldername = @opendir(JAWS_DATA . "cache" . DIRECTORY_SEPARATOR . "addressprotector")) {
		  while (false !== ($filename = readdir($foldername))) {
			if ($filename != "." && $filename != "..") {
				if (!Jaws_Utils::Delete(JAWS_DATA . "cache" . DIRECTORY_SEPARATOR . "addressprotector" . DIRECTORY_SEPARATOR . $filename, false)) {
					array_merge('Filename: addressprotector/'.$filename.', could not be deleted from cache<br />', $error_array); 
				}
			}
		  }
		  closedir($foldername);
		} 
		
		if ($foldername = @opendir(JAWS_DATA . "maps")) {
		  while (false !== ($filename = readdir($foldername))) {
			if ($filename != "." && $filename != "..") {
				if (!Jaws_Utils::Delete(JAWS_DATA . "maps" . DIRECTORY_SEPARATOR . $filename, false)) {
					array_merge('Filename: maps/'.$filename.', could not be deleted from maps<br />', $error_array); 
				}
			}
		  }
		  closedir($foldername);
		} 
		
		/*
		// Get current menu items
        $sql = '
            SELECT
                [id], [menu_type], [title], [url], [url_target], [rank]
            FROM [[menus]]
			WHERE ([url_target] = {url_target} AND [url] != {url})';

        $params = array();
        $params['url'] = 'javascript: void(0);';
        $params['url_target'] = 0;

		$result = $GLOBALS['db']->queryAll($sql, $params);
        
		if (Jaws_Error::IsError($result)) {
			array_push($error_array, $result->GetMessage().'<br />'); 
        } else {
			include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
			// Visit each menu item, which will cache it		
			foreach ($result as $menu) {
				$snoopy = new Snoopy;
				if (strpos($menu['url'], 'http://') !== false) {
					$submit_url = $menu['url'];
				} else {
					$submit_url = $this->getSiteURL().'/'.$menu['url'];
				}
				if(!$snoopy->fetch($submit_url)) {
					array_push($error_array, $snoopy->error.'<br />'); 
				}
			}
		}
		*/
		if (isset($error_array[0])) {
			foreach ($error_array as $error_msg) {
				echo $error_msg;
			}
		} else {
			if ($echo === true) {
				echo 'Cache was rebuilt successfully.';
			}
		}
		return true;
	}

		
    /**
     * Redirect to the correct Jaws Site using the resellers data and parent site data
     *
     * @access 	public
     * @param 	boolean	$admin	admin section?
     * @param 	boolean	$onlyWWW	only check for (and remove) "www." in SERVER_NAME
     * @return 	mixed	void or Jaws_Error on error
     */
    function GetCorrectURL($admin = false, $onlyWWW = false)
    {
		$full_url = $this->getFullURL(); 
		$xss   = $this->loadClass('XSS', 'Jaws_XSS');
		$domain = $xss->filter(str_replace('www.', '', strtolower($_SERVER['SERVER_NAME'])));
		$scheme = 'http://';
		if (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on') {
			$scheme = 'https://';
		}
		if (strpos($_SERVER['QUERY_STRING'], "images/blank.gif") !== false) {
			header("HTTP/1.1 301 Moved Permanently");
			require_once JAWS_PATH . 'include/Jaws/Header.php';
			Jaws_Header::Location($scheme.$domain.'/images/blank.gif');
			exit;
		}
		if (substr_count($full_url,"index.php?") > 1 || substr_count($_SERVER['REQUEST_URI'],"index.php?") > 1) {
			header("HTTP/1.1 301 Moved Permanently");
			require_once JAWS_PATH . 'include/Jaws/Header.php';
			Jaws_Header::Location($scheme.$domain);
			exit;
		}
		if (strpos($_SERVER['REQUEST_URI'],"index.php") !== false && strpos($_SERVER['REQUEST_URI'],"data/files/") !== false && strpos($_SERVER['REQUEST_URI'], "customtheme") === false) {
			header("HTTP/1.1 301 Moved Permanently");
			require_once JAWS_PATH . 'include/Jaws/Header.php';
			Jaws_Header::Location($scheme.$domain);
			exit;
		}
		
		if (isset($_SERVER['SERVER_NAME']) && !empty($_SERVER['SERVER_NAME'])) {
			if ($onlyWWW === true) {
				header("HTTP/1.1 301 Moved Permanently");
				require_once JAWS_PATH . 'include/Jaws/Header.php';
				Jaws_Header::Location($full_url);
				exit;
			} else {
				$domains_url = '';
				// Get reseller key
				$datadir = JAWS_DATA;
				if (!is_dir($datadir)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_FILE_DOES_NOT_EXIST'), RESPONSE_ERROR);
					return new Jaws_Error(_t('GLOBAL_ERROR_FILE_DOES_NOT_EXIST'), "Reading data directory");
				}
				$dir = scandir($datadir);
				foreach($dir as $file) {
					if ($file != '.' && $file != '..' && substr($file, 0, (strpos($file, '.'))) != 'resellers' && end(explode('.', strtolower($file))) == 'txt') {
						$reseller_key = substr($file, 0, (strpos($file, '.')));
						break;
					}
				}
				// Get reseller information from reseller key
				$reseller_info = $this->GetResellerInfo($reseller_key);
				if (Jaws_Error::IsError($reseller_info)) {
					return $reseller_info;
				} else if (isset($reseller_info[6]) && $reseller_info[6] == 'active' && isset($reseller_info[4]) && !empty($reseller_info[4])) {
					$reseller_title = $reseller_info[1];
					$reseller_desc = $reseller_info[2];
					$reseller_link = $reseller_info[3];
					$reseller_domains = explode(',', $reseller_info[4]);
					$domains_url = "http://".$reseller_domains[0]."/data/".$reseller_key.".txt";
					
					$reseller_expires = $reseller_info[5];
					$reseller_status = $reseller_info[6];
					$reseller_email = $reseller_info[7];
					$reseller_created = $reseller_info[8];
					
				} else {
					return new Jaws_Error("Could not parse active domains list file", "Parsing active domains file");
				}
				/*
				include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
				$snoopy = new Snoopy;
				if($domains_url != '' && $snoopy->fetch($domains_url)) {
				*/
				if(file_exists(JAWS_DATA . $reseller_key . '.txt')) {
					$site_info = Jaws_Utils::split2D(file_get_contents(JAWS_DATA . $reseller_key . '.txt'));
					$parent_domains = array();
					
					foreach($site_info as $site) {		            
						$site_id = (isset($site[0]) ? $site[0] : '');
						$site_title = (isset($site[1]) ? $site[1] : '');
						$site_desc = (isset($site[2]) ? $site[2] : '');
						$site_domains = (isset($site[3]) ? explode(',', $site[3]) : array());
						$site_secureaddress = (isset($site[4]) ? $site[4] : '');
						$site_serveraddress = (isset($site[5]) ? $site[5] : '');
						$site_expires = (isset($site[6]) ? $site[6] : '');
						$site_status = (isset($site[7]) ? $site[7] : '');
						$site_email = (isset($site[8]) ? $site[8] : '');
						$site_mailserver = (isset($site[9]) ? $site[9] : '');
						$site_pageconst = (isset($site[10]) ? $site[10] : '');
						$site_reservationOn = (isset($site[11]) ? $site[11] : '');
						$site_ecommerceOn = (isset($site[12]) ? $site[12] : '');
						$site_ecommerceTrack = (isset($site[13]) ? $site[13] : '');
						$site_ownerId = (isset($site[14]) ? $site[14] : '');
						$site_created = (isset($site[15]) ? $site[15] : '');
						foreach ($site_domains as $site_domain) {
							$site_domain = strtolower($site_domain);
							$site_base_domain = (strpos($site_domain, '/') !== false ? substr($site_domain, 0, strpos($site_domain, '/')) : $site_domain);
							$site_active = false;
							if ($site_id == '0') {
								$parent_domains[] = $site_domain;
							}
							if ($site_status == 'temp' || $site_status == 'active') {
								$site_active = true;
							}
							$requested_domain = $domain;
							if ($site_active && ($site_id == '0' && strtolower($_SERVER['SERVER_NAME']) == 'www.'.$site_domain) || (!empty($site_id) && ($site_id != '0' && ((($requested_domain == $site_base_domain) && !in_array($site_base_domain, $reseller_domains)) || $requested_domain == $site_domain) || (strtolower($_SERVER['SCRIPT_NAME']) == ($site_id != '0' ? "/".$site_id : '').'/index.php' || strtolower($_SERVER['SCRIPT_NAME']) == ($site_id != '0' ? "/".$site_id : '').'/admin.php')))) {
								header("HTTP/1.1 301 Moved Permanently");
								require_once JAWS_PATH . 'include/Jaws/Header.php';
								Jaws_Header::Location($scheme.str_replace('www.', '', $site_domains[0]).'/'.($admin === true ? 'admin' : 'index' ).'.php');
								exit;
							}
						}
					}
				} else {
					return new Jaws_Error("Could not parse active domains list file", "Parsing active domains file");
				}

			}
		}
	}
   
    /**
     * Tasks can be scheduled to be fired periodically at a given time, or one time only.
     *
     * @access 	public
     * @category 	developer_feature
     * @param 	string	$scriptpath	URL to execute
     * @param 	integer	$time_interval	interval execution (in seconds)
     * @param 	timestamp	$fire_time	unix timestamp of first execution
     * @param 	boolean	$run_only_once	should the schedule only execute once?
     * @return 	mixed	True on success, Jaws_Error on error
     */
    function InsertScheduler($scriptpath = '', $time_interval = 43200, $fire_time = null, $run_only_once = 0)
    {
        if (trim($scriptpath) != '') {
			$sql = "
				INSERT INTO [phpjobscheduler]
					([scriptpath], [time_interval], [fire_time], [run_only_once])
				VALUES
					({scriptpath}, {time_interval}, {fire_time}, {run_only_once})";

			$fire_time = (!is_null($fire_time)) ? $fire_time : strtotime("now");

			$params               		= array();
			$params['scriptpath']      	= $scriptpath;
			$params['time_interval']   	= $time_interval;
			$params['fire_time']        = $fire_time;
			$params['run_only_once'] 	= $run_only_once;

			$result = $GLOBALS['db']->query($sql, $params);
			if (Jaws_Error::IsError($result)) {
	            $GLOBALS['app']->Session->PushLastResponse("Couldn't insert schedule", RESPONSE_ERROR);
				return new Jaws_Error("Couldn't insert schedule for ".$scriptpath, "Inserting schedule");
			}
			$GLOBALS['app']->Session->PushLastResponse("Schedule created", RESPONSE_NOTICE);
			return true;
		} else {
			$GLOBALS['app']->Session->PushLastResponse("Scriptpath was not provided to create a schedule", RESPONSE_ERROR);
			return new Jaws_Error("Scriptpath was not provided to create a schedule", "Inserting schedule");
		}
	}

    /**
     * Update Scheduler by ID
     *
     * @access 	public
     * @param 	integer	$id	ID of existing schedule
     * @param 	integer	$time_interval	interval execution (in seconds)
     * @param 	timestamp	$fire_time	unix timestamp of first execution
     * @param 	boolean	$run_only_once	should the schedule only execute once?
     * @return 	mixed	True on success, Jaws_Error on error
     */
    function UpdateScheduler($id, $time_interval = 43200, $fire_time = null, $run_only_once = 0)
    {
        if (!empty($id)) {
			$sql = "
				UPDATE [phpjobscheduler] SET 
					[time_interval] = {time_interval}, 
					[fire_time] = {fire_time}, 
					[run_only_once] = {run_only_once}
				WHERE [id] = {id}";

			$fire_time = (!is_null($fire_time)) ? $fire_time : strtotime("now");

			$params               		= array();
			$params['id']      			= (int)$id;
			$params['time_interval']   	= $time_interval;
			$params['fire_time']        = $fire_time;
			$params['run_only_once'] 	= $run_only_once;

			$result = $GLOBALS['db']->query($sql, $params);
			if (Jaws_Error::IsError($result)) {
				$GLOBALS['app']->Session->PushLastResponse("Couldn't update schedule", RESPONSE_ERROR);
				return new Jaws_Error("Couldn't update schedule ID: ".$id, "Updating schedule");
			}
			$GLOBALS['app']->Session->PushLastResponse("Schedule updated", RESPONSE_NOTICE);
			return true;
		} else {
			$GLOBALS['app']->Session->PushLastResponse("Scriptpath was not provided to update schedule", RESPONSE_ERROR);
			return new Jaws_Error("Scriptpath was not provided to update schedule", "Updating schedule");
		}
	}

    /**
     * Delete Scheduler by ID
     *
     * @access 	public
     * @param 	integer	$id	ID of existing schedule
     * @return 	mixed	True on success, Jaws_Error on error
     */
    function DeleteScheduler($id)
    {
        if (!empty($id)) {

			$sql = 'DELETE FROM [phpjobscheduler] WHERE [id] = {id}';
			$result = $GLOBALS['db']->query($sql, array('id' => $id));
			if (Jaws_Error::IsError($result)) {
				$GLOBALS['app']->Session->PushLastResponse("Couldn't delete schedule", RESPONSE_ERROR);
				return new Jaws_Error("Couldn't delete schedule ID: ".$id, "Deleting schedule");
			}
			$GLOBALS['app']->Session->PushLastResponse("Schedule deleted", RESPONSE_NOTICE);
			return true;
		} else {
			$GLOBALS['app']->Session->PushLastResponse("Scriptpath was not provided to delete schedule", RESPONSE_ERROR);
			return new Jaws_Error("Scriptpath was not provided to delete schedule", "Deleting schedule");
		}
	}
	
    /**
     * Reseller support.
     *
     * @access 	public
     * @category 	feature
     * @param 	string	$key	Jaws reseller site/config_key to look for
     * @param 	string	$host	Jaws reseller hostname to look for
     * @return 	mixed	Array of reseller data, or Jaws_Error on error
     */
    function GetResellerInfo($key = null, $host = null)
    {
		require_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
		$reseller_url = "http://jaws-project.com/data/resellers.txt";
		$snoopy = new Snoopy;

		if(!is_null($key) && $snoopy->fetch($reseller_url)) {
			$reseller_info = Jaws_Utils::split2D(trim($snoopy->results));
			foreach($reseller_info as $reseller) {		            
				if ($reseller[0] == $key) {
					return $reseller;
				}
			}
		} else if(!is_null($host) && $snoopy->fetch($reseller_url)) {
			$reseller_info = Jaws_Utils::split2D(trim($snoopy->results));
			foreach($reseller_info as $reseller) {		            
				$reseller_domains = explode(',', $reseller[4]);
				if (in_array(strtolower($host), $reseller_domains)) {
					return $reseller;
				}
			}
		} else {
			return new Jaws_Error("Could not parse reseller file", "Getting reseller info");
		}
	}
    
	/**
     * Run custom scripts.
     *
     * @access 	public
     * @category 	developer_feature
     * @return 	mixed	output of custom hook, or Jaws_Error on error
     */
    function LoadCustomHook()
    {
		if (file_exists(JAWS_DATA . 'hooks' . DIRECTORY_SEPARATOR . 'Custom.php')) {
			include_once JAWS_DATA . 'hooks' . DIRECTORY_SEPARATOR . 'Custom.php';
			$hook = new CustomHook;
			$request =& Jaws_Request::getInstance();
			$call = $request->get('fuseaction', 'get');
			if (method_exists($hook, $call)) {
				$res = $hook->$call();
				if ($res === false || Jaws_Error::IsError($res)) {
					//return $res;
					return new Jaws_Error(_t('GLOBAL_ERROR_EVENTS_LISTENER_ERROR'), 'CORE');
				} else if (isset($res['return'])) {
					return $res['return'];
				}
			}
		}
	}
	
	/**
     * Password protected pages.
     *
     * @access 	public
     * @category 	feature
     * @param 	string	$url	URL to check
     * @param 	boolean	$standAlone	Is standalone?
     * @param 	boolean	$redirect	Redirect to log-in if protected?
     * @return 	mixed	HTTP redirect, or boolean true on success or false on error
     */
    function IsPasswordProtected($url = null, $standAlone = null, $redirect = true)
    {
		// Password protected pages
		if (!$GLOBALS['app']->Session->Logged()) {
			// Get protected_pages registry value
			$GLOBALS['app']->Registry->LoadFile('Users');
			$password_protected = $GLOBALS['app']->Registry->Get('/gadgets/Users/protected_pages');
			// Get URL of menu id
			if (!empty($password_protected)) {
				$menu_ids = explode(',',$password_protected);
				$menuModel = $GLOBALS['app']->LoadGadget('Menu', 'Model');
				foreach ($menu_ids as $id) {
					$menu = $menuModel->GetMenu((int)$id);
					if (!Jaws_Error::IsError($menu)) {
						// Redirect to log-in if password protected
						$real_url = $GLOBALS['app']->Map->GetAliasPath($menu['url']);
						$real_url = ($real_url === false ? $menu['url'] : $real_url);
						if (is_null($url) || empty($url)) {
							$url = $this->GetFullURL();
						}
						if (strpos($url, $real_url) !== false || strpos($url, $menu['url']) !== false) {
							for ($i = 0; $i < 10; $i++) {
								if (strpos($url, $real_url.$i) !== false) {
									return false;
								}
							}
							$GLOBALS['app']->Session->PushSimpleResponse("You must log-in to view this page. If you don't have an account, you can <a href=\"index.php?gadget=Users&action=Registration&redirect_to=".urlencode($url)."\">Create one</a>");
							$userHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
							if (is_null($standAlone)) {
								$standAlone = $this->IsStandAloneMode();
							}
							if ($redirect === false) {
								return true;
							} else {
								if ($standAlone === true) {
									echo $userHTML->DefaultAction();
								} else {
									require_once JAWS_PATH . 'include/Jaws/Header.php';
									Jaws_Header::Location('index.php?gadget=Users&redirect_to='.urlencode($url));
								}
							}
							exit;
						}
					}
				}
			}
		}
	}
	
	/**
     * XML-RPC API.
     *
     * @access 	public
     * @category 	feature
     * @param 	string	$host	hostname
     * @param 	string	$path	script to call
     * @param 	string	$method	method name we're calling
     * @param 	array	$params	parameters to send
     * @return 	object	Object of returned data, or Jaws_Error on error
     */
    function XmlRpc($host, $path, $method, $params)
    {
		//var_dump($params);
		require_once 'XML/RPC.php';
        
		// Prepare an XML-RPC Message.
        $eArgs = array();
		foreach ($params as $v) {
			$eArgs[] = XML_RPC_encode($v);
        }
        $msg = new XML_RPC_Message($method, $eArgs);

        $cli = new XML_RPC_Client($path, $host, (substr(strtolower($host), 0, 5) == 'https' ? 443 : 80));
        //$cli->setDebug(1);
		//var_dump($cli);
        $res = $cli->send($msg, 0);
		//var_dump($res);
		//$error = new Jaws_Error(var_export($res, true).': '.$method.' ('.var_export($params, true).')', "XMLRPC");
		if (is_object($res) && method_exists($res, 'value')) {
			$val = $res->value();
		}
        if (!is_object($res) || (!is_object($val) || !is_a($val, 'XML_RPC_value'))) {
			$params[0] = '*****';
			$params[1] = '*****';
			return new Jaws_Error($method.': '."\n".var_export($res, true)."\n".' ('.var_export($params, true).')', "XMLRPC");
		}
		
        return XML_RPC_decode($val);
	}
	
	/**
     * RESTful API.
     *
     * @access 	public
     * @category 	feature
     * @param 	string	$url	script to call
     * @param 	string	$method	HTTP method
     * @param 	string	$response_type	response body format (json or xml)
     * @param 	array	$post	post parameters
     * @return 	string	Response body
     */
    function Rest($url, $method = 'GET', $response_type = 'json', $post_params = array())
    {
		require_once 'HTTP/Request.php';
		$httpRequest = new HTTP_Request($url);
		$httpRequest->setMethod(HTTP_REQUEST_METHOD_POST);
		//$httpRequest->setBasicAuth("Username", "Password");
		if (!count($post_params) <= 0) {
			//$httpRequest->setBody($GLOBALS['app']->UTF8->json_encode($params));
			foreach ($post_params as $pk => $pv) {
				$httpRequest->addPostData($pk, $pv);
			}
		}
		$resRequest = $httpRequest->sendRequest();
		if (PEAR::isError($resRequest) || (int) $httpRequest->getResponseCode() <> 200) {
			return new Jaws_Error('ERROR REQUESTING URL: '.$url.' ('.$httpRequest->getResponseCode().')', _t('USERS_NAME'));
		}
		$data = $httpRequest->getResponseBody();
		switch (strtolower($response_type)) {
			case 'xml':
				require_once 'XML/Unserializer.php';

				$unserializer = new XML_Unserializer();
				$unserializer->setOption('parseAttributes', true);
				//$unserializer->setOption('decodeFunction', 'strtolower');
				$data = $unserializer->unserialize($data);
				break;
			case 'json':
				$data = $GLOBALS['app']->UTF8->json_decode($data);
				break;
		}
		return $data;
	}
}
