<?php
/**
 * Site-wide Layout, gadget-wide Layouts and per-page Layouts. Drag and drop any kind of content onto the Layout.
 *
 * @category   Layout
 * @category   feature
 * @package    Core
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2005-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class Jaws_Layout
{
    /**
     * Model that will be used to get data
     *
     * @var    LayoutJaws_Model
     * @access private
     */
    var $_Model;

    /**
     * Template that will be used to print the data
     *
     * @var    Jaws_Template
     * @access private
     */
    var $_Template;

    /**
     * Array that will have the meta tags
     *
     * @var    array
     * @access private
     */
    var $_HeadMeta = array();

    /**
     * Array that will have the links meta tags
     *
     * @var    array
     * @access private
     */
    var $_HeadLink = array();

    /**
     * Array that will have the JS links
     *
     * @var    array
     * @access private
     */
    var $_ScriptLink = array();

    /**
     * Array that will contain other info/text
     * that has to go into the <head> part
     *
     * @var    array
     * @access private
     */
    var $_HeadOther = array();

    /**
     * Requested gadget
     *
     * @access private
     * @var    string
     */
    var $_RequestedGadget;

    /**
     * Requested gadget's action
     *
     * @access private
     * @var    string
     */
    var $_RequestedAction;

    /**
     * Current section
     *
     * @access private
     * @var string
     */
    var $_Section = '';

    /**
     * Current section
     *
     * @access private
     * @var string
     */
    var $_SectionAttributes = array();

    /**
     * Returns the current URI location (without BASE_SCRIPT's value)
     *
     * @access  private
     * @var     string
     */
    var $_CurrentLocation;

    /**
     * Page title
     *
     * @access  private
     * @var     string
     */
    var $_Title = null;

    /**
     * Page description
     *
     * @access  private
     * @var     string
     */
    var $_Description = null;

    /**
     * Page keywords
     *
     * @access  private
     * @var     array
     */
    var $_Keywords = array();

    /**
     * Page languages
     *
     * @access  private
     * @var     array
     */
    var $_Languages = array();
    
    /**
     * Requested ID
     *
     * @access  private
     * @var     array
     */
    var $_RequestedId;
    
	/**
     * Requested ID
     *
     * @access  private
     * @var     array
     */
    var $_CustomReplacements = array();
	
	/**
     * Stand Alone Mode
     *
     * @access  private
     * @var     array
     */
    var $_standAloneMode = null;
    
	/**
     * Initializes the Layout
     *
     * @access  public
     */
    function Jaws_Layout($IsStandAlone = null)
    {
        // Set Headers
        header('Content-Type: text/html; charset=utf-8'); //magic, big fix
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');

        if (!is_null($IsStandAlone)) {
			$this->_standAloneMode = $IsStandAlone;
		} else {
			$this->_standAloneMode = $GLOBALS['app']->IsStandAloneMode();
		}
		
		//load default site keywords
        $keywords = $GLOBALS['app']->Registry->Get('/config/site_keywords');
        $this->_Keywords = array_map(array('Jaws_UTF8','trim'), explode(',', $keywords));

        // set default site language
        $this->_Languages[] = $GLOBALS['app']->GetLanguage();

        $this->_Model = $GLOBALS['app']->loadGadget('Layout', 'Model');
        if (Jaws_Error::isError($this->_Model)) {
            Jaws_Error::Fatal("Can't load layout model", __FILE__, __LINE__);
        }
    }

    /**
     * Gets the current section
     *
     * @access public
     * @return string Current section
     */
    function GetSectionName()
    {
        return $this->_Section;
    }

    /**
     * Is current section wide?
     *
     * @access public
     * @return boolean
     */
    function IsSectionWide()
    {
        return !isset($this->_SectionAttributes['narrow']);
    }

    /**
     */
    function AddJSPrototypeHeadLink()
    {
        // Prototype, Scriptaculous and Response
        $this->AddScriptLink($GLOBALS['app']->GetJawsURL() . '/libraries/prototype/prototype.js');
        $this->AddScriptLink($GLOBALS['app']->GetJawsURL() . '/libraries/prototype/scriptaculous.js');
        $this->AddScriptLink($GLOBALS['app']->GetJawsURL() . '/libraries/prototype/effects.js');
        $this->AddScriptLink($GLOBALS['app']->GetJawsURL() . '/libraries/prototype/controls.js');
		//if (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on') {
        /*
			$this->AddScriptLink('https://ajax.googleapis.com/ajax/libs/prototype/1.6.1/prototype.js');
			$this->AddScriptLink('https://ajax.googleapis.com/ajax/libs/scriptaculous/1/scriptaculous.js');
			$this->AddScriptLink('https://ajax.googleapis.com/ajax/libs/scriptaculous/1/effects.js');
			$this->AddScriptLink('https://ajax.googleapis.com/ajax/libs/scriptaculous/1/controls.js');
        } else {
			$this->AddScriptLink('http://ajax.googleapis.com/ajax/libs/prototype/1.6.1/prototype.js');
			$this->AddScriptLink('http://ajax.googleapis.com/ajax/libs/scriptaculous/1.8/scriptaculous.js?load=effects,controls');
        }
		*/
		$this->AddScriptLink('include/Jaws/Ajax/Response.js');
    }

    /**
     * Loads the template
     *
     * @access  public
     */
    function Load($ajaxEnabled = false, $path = null, $file = null)
    {
        if ($GLOBALS['app']->Registry->Get('/config/site_status') == 'disabled' && !$GLOBALS['app']->Session->IsAdmin())
        {
            require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
            echo Jaws_HTTPError::Get(503);
            exit;
        }

        $favicon = $GLOBALS['app']->Registry->Get('/config/site_favicon');
        if (!empty($favicon)) {
            $this->AddHeadLink($favicon, 'icon', 'image/png');
        }

        if ($ajaxEnabled || $GLOBALS['app']->Registry->Get('/config/frontend_ajaxed') == 'true') {
            $this->AddJSPrototypeHeadLink();
        }

		// Get requested stuff
		$request =& Jaws_Request::getInstance();
		if (!isset($this->_RequestedGadget) || is_null($this->_RequestedGadget) || empty($this->_RequestedGadget)) {
			if (isset($GLOBALS['app']->_MainRequestGadget) && !empty($GLOBALS['app']->_MainRequestGadget)) {
				$gadget = $GLOBALS['app']->_MainRequestGadget;
			} else {
				$get_gadget = $request->get('gadget', 'get');
				if ($get_gadget !== null) {
					$gadget = $get_gadget;
				} else {
					$post_gadget = $request->get('gadget', 'post');
					$gadget = $post_gadget !== null ? $post_gadget : '';
				}
			}
			$this->_RequestedGadget = $gadget;
		}
		if (!isset($this->_RequestedAction) || is_null($this->_RequestedAction) || empty($this->_RequestedAction)) {
			if (isset($GLOBALS['app']->_MainRequestAction) && !empty($GLOBALS['app']->_MainRequestAction)) {
				$action = $GLOBALS['app']->_MainRequestAction;
			} else {
				$get_action = $request->get('action', 'get');
				if ($get_action !== null) {
					$action = $get_action;
				} else {
					$post_action = $request->get('action', 'post');
					$action = $post_action !== null ? $post_action : '';
				}
			}
			$this->_RequestedAction = $action;
		}
		if (!isset($this->_RequestedId) || is_null($this->_RequestedId) || empty($this->_RequestedId)) {
			if (isset($GLOBALS['app']->_MainRequestId) && !empty($GLOBALS['app']->_MainRequestId)) {
				$id = $GLOBALS['app']->_MainRequestId;
			} else {
				$get_id = $request->get('id', 'get');
				if ($get_id !== null) {
					$id = $get_id;
				} else {
					$post_id = $request->get('id', 'post');
					$id = $post_id !== null ? $post_id : '';
				}
			}
			$this->_RequestedId = $id;
        }
		
		// Current Gadget's URL Has Page?
		$page = array();
		if (Jaws_Gadget::IsGadgetUpdated('CustomPage')) {
			$hook = $GLOBALS['app']->loadHook('CustomPage', 'URLList');
			if ($hook !== false) {
				if (method_exists($hook, 'CurrentURLHasPage')) {
					$page = $hook->CurrentURLHasPage($this->_RequestedGadget, $this->_RequestedAction, $this->_RequestedId);
					if ($page !== false && isset($page['id']) && !empty($page['id'])) {
						$this->_RequestedId = $page['linkid'];
						if (!empty($page['auto_keyword'])) {
							$file = 'layout_auto.html';
						} else {
							if (!empty($page['layout']) && strlen($page['layout']) > 1) {
								$file = $page['layout'];
							}
							if (!empty($page['theme']) && strlen($page['theme']) > 1) {
								$path = $page['theme'];
							}
						}
					}
				}
			}
		}
		
		// Load the template
		$full_path = $path;
		if (!is_null($path)) {
			$path = (substr($path, 0, 1) == '/' ? substr($path, 1, strlen($path)) : $path);
			$path = (substr($path, -1) != '/' ? $path.'/' : $path);
			$data_path = (substr(JAWS_DATA, -1) != '/' ? JAWS_DATA . '/' : JAWS_DATA);
			$jaws_path = (substr(JAWS_PATH, -1) != '/' ? JAWS_PATH . '/' : JAWS_PATH);
			if (file_exists($data_path.$path) || file_exists($jaws_path.$path)) {
				$tplpath = (file_exists($data_path . $path) ? $data_path . $path : $path);
				$full_path = (file_exists($data_path . $path) ? $data_path . $path : $jaws_path . $path);
				$this->_Template = new Jaws_Template($tplpath);
			} else {
				$this->_Template = new Jaws_Template();
			}
		} else {
			$this->_Template = new Jaws_Template();
		}
        
		$site_layout = $GLOBALS['app']->Registry->Get('/config/layout');
		if ($site_layout === false || is_null($site_layout) || empty($site_layout)) {
			$GLOBALS['app']->Registry->Set('/config/layout', 'layout.html');
			$GLOBALS['app']->Registry->Commit('core');
			$site_layout = 'layout.html';
		}
		if (!is_null($file)) {
			$file = (substr($file, 0, 1) == '/' ? substr($file, 1, strlen($file)) : $file);
			if (file_exists($full_path.$file)) {
				$this->_Template->Load($file);
			} else {
				$this->_Template->Load($site_layout);
			}
		} else {
			$this->_Template->Load($site_layout);
		}

        $direction = _t('GLOBAL_LANG_DIRECTION');
        $dir  = $direction == 'rtl' ? '.' . $direction : '';
        $brow = $GLOBALS['app']->GetBrowserFlag();
        $brow = empty($brow)? '' : '.'.$brow;
        $base_url = $GLOBALS['app']->GetSiteURL('/');
        $site_url = $GLOBALS['app']->Registry->Get('/config/site_url');

		$this->_Template->SetBlock('layout');
        $this->_Template->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . "/");
        $this->_Template->SetVariable('BASE_URL', $base_url);
        $this->_Template->SetVariable('.dir', $dir);
        $this->_Template->SetVariable('.browser', $brow);
		//$this->_Template->SetVariable('site-url', empty($site_url)? $base_url : $site_url);
		$this->_Template->SetVariable('site-url', $base_url);
        $this->_Template->SetVariable('site-name',        $GLOBALS['app']->Registry->Get('/config/site_name'));
		$this->_Template->SetVariable('site-slogan',      $GLOBALS['app']->Registry->Get('/config/site_slogan'));
        $this->_Template->SetVariable('site-comment',     $GLOBALS['app']->Registry->Get('/config/site_comment'));
        $this->_Template->SetVariable('site-author',      $GLOBALS['app']->Registry->Get('/config/site_author'));
        $this->_Template->SetVariable('site-copyright',   $GLOBALS['app']->Registry->Get('/config/copyright'));
        
		$this->_Template->SetVariable('powered-by', $GLOBALS['app']->Registry->Get('/config/site_reseller'));
        $this->_Template->SetVariable('powered-link', $GLOBALS['app']->Registry->Get('/config/site_reseller_link'));

		if ($this->_Template->VariableExists('syntacts-body') !== false) {
			$bodyclass = " ".ucfirst($this->_RequestedGadget).'-';
			$bodyclass .= (!empty($this->_RequestedAction) ? ucfirst($this->_RequestedAction) : 'DefaultAction');
			$bodyclass .= (!empty($this->_RequestedId) ? " ".ucfirst($this->_RequestedGadget).'-'.
			ucfirst($this->_RequestedAction).'-'.$this->_RequestedId : '');
			$bodyclass .= " ".($GLOBALS['app']->Session->Logged() ? 'logged' : 'not-logged');
			$full_url = $GLOBALS['app']->getFullURL();
			if (
				!empty($full_url) && (strpos($full_url, "admin.php") === false || 
					($this->_RequestedGadget == 'Layout' || 
					($this->_RequestedGadget == 'CustomPage' && $this->_RequestedAction == 'view')))
			) {	
				$alias_page = '';
				$alias_pages = array();
				// Add complete query values to body class
				$alias_query = '';
				if (strpos($full_url, '=') !== false) {
					$fast_urls = false;
					// Get possible IDs that can be associated with requested URL
					$hook = $GLOBALS['app']->loadHook($this->_RequestedGadget, 'URLList');
					if ($hook !== false) {
						if (method_exists($hook, 'GetAllFastURLsOfRequest')) {
							$fast_urls = $hook->GetAllFastURLsOfRequest($this->_RequestedAction, $this->_RequestedId);
						}
					}
					if (is_array($fast_urls) && !count($fast_urls) <= 0) {
						foreach ($fast_urls as $f_url) {
							$alias_pages[] = $GLOBALS['app']->Map->GetURLFor(
								$this->_RequestedGadget, 
								($this->_RequestedGadget == 'CustomPage' && $this->_RequestedAction == 'view' ? 'Page' : $this->_RequestedAction), 
								array('id' => $f_url)
							);
						}
					} else {
						$alias_page = $GLOBALS['app']->Map->GetURLFor($this->_RequestedGadget, $this->_RequestedAction, $_GET);
					}
				} else {
					$alias_page = $GLOBALS['app']->Map->Parse($full_url, true);
				}
				if (!is_bool($alias_page) && strpos($alias_page, '=') !== false) {
					$parsed_alias = parse_url($alias_page);
					$parsed_query = array();
					parse_str($parsed_alias['query'], $parsed_query);
					foreach ($parsed_query as $pk => $pv) {
						if (strpos(strtolower($pv), 'http:') === false) {
							$alias_query .= (empty($alias_query) ? " " : '-').$pv;
						}
					}
				}
				$bodyclass .= $alias_query;
				
				$config_home_page = $GLOBALS['app']->Registry->Get('/config/home_page');
				if (substr(strtolower($config_home_page), 0, 4) == 'http') {
					$config_home_page = str_replace(array(
														$GLOBALS['app']->getSiteURL('/', false, 'http'), 
														$GLOBALS['app']->getSiteURL('/', false, 'https')
													), '', $config_home_page);
				}
				if (
					($alias_page == $GLOBALS['app']->Registry->Get('/config/home_page')) || 
					(in_array($GLOBALS['app']->Registry->Get('/config/home_page'), $alias_pages)) || 
					strpos($full_url, $GLOBALS['app']->Registry->Get('/config/home_page')) !== false || 
					($GLOBALS['app']->getSiteURL('', false, 'http') .'/'.$config_home_page == $full_url) || 
					($GLOBALS['app']->getSiteURL('', false, 'https') .'/'.$config_home_page == $full_url) || 
					substr($full_url,-9) == "index.php"
				) {
					$this->_Template->SetVariable('syntacts-body', 'homepage'.$bodyclass);
				} else {
					$this->_Template->SetVariable('syntacts-body', 'not-homepage'.$bodyclass);
				}
			} else {
				$this->_Template->SetVariable('syntacts-body', 'not-homepage'.$bodyclass);
			}
		}
		
		if ($this->_Template->VariableExists('PAGE_ID') !== false) {
			if (isset($page['id']) && !empty($page['id'])) { 
				$this->_Template->SetVariable('PAGE_ID', $page['id']);
				if ($this->_Template->VariableExists('DISPLAY_ID') !== false) {
					$this->_Template->SetVariable('DISPLAY_ID', md5('CustomPage'.$page['id']));
				}
			}
		}
		
		if (
			isset($page['show_title']) && 
			!empty($page['show_title']) &&
			$page['show_title'] == "Y" && 
			$this->_Template->VariableExists('PAGE_TITLE') !== false
		) {
			$page_title = '';
			if (isset($page['logo']) && $page['logo'] != "") { 
				$page_title .= '<!-- START Title Logo -->'."\n";
				$page_title .= '<h1><img class="custom_page-logo-image" border="0" src="'.$GLOBALS['app']->getDataURL().'files'.$page['logo'].'"></h1>'."\n";
				$page_title .= '<!-- END Title Logo -->'."\n";
			}
			if (isset($page['sm_description']) && $page['sm_description'] != "" && $page['logo'] == "") {
				$page_title .= '<!-- START Title -->'."\n";
				$page_title .= '<h1>'.$page['sm_description'].'</h1><br />'."\n";
				$page_title .= '<!-- END Title -->'."\n";
			}
			$this->_Template->SetVariable('PAGE_TITLE', $page_title);
		}
		
		// Edit Page button
		if (
			JAWS_SCRIPT != 'admin' && $GLOBALS['app']->Session->Logged() && 
			($GLOBALS['app']->Session->IsAdmin() || $GLOBALS['app']->Session->IsSuperAdmin()) && 
			$this->_Template->VariableExists('edit-page-button') !== false
		) {
			if (Jaws_Gadget::IsGadgetUpdated($this->_RequestedGadget)) {
				$hook = $GLOBALS['app']->loadHook($this->_RequestedGadget, 'URLList');
				if ($hook !== false) {
					if (method_exists($hook, 'GetEditPage')) {
						$edit_url = $hook->GetEditPage($this->_RequestedAction, $this->_RequestedId);
						if ($edit_url !== false && !empty($edit_url)) {
							$this->_Template->SetVariable('edit-page-button', '<div style="z-index: 99999; left: 8px; top: 50px; text-align: left; position: fixed; width: 150px;"><button onclick="location.href=\''.$edit_url.'\';" style="padding: 5px; font-size: 1em;">Edit Page</button></div>');
						}
					}
				}
			}
		}
		
		if (isset($page['auto_keyword']) && !empty($page['auto_keyword']) && $this->_Template->VariableExists('CONTENT') !== false) {
			$customPageModel = $GLOBALS['app']->LoadGadget('CustomPage', 'Model');
			$wikipediaContent = $customPageModel->fetchWikipedia($page['auto_keyword']);
			$this->_Template->SetVariable('CONTENT', $wikipediaContent);
		}
		
		// Statistics
		if (Jaws_Gadget::IsGadgetUpdated('CustomPage') && $this->_Template->VariableExists('STATISTICS') !== false) {
			$GLOBALS['app']->Registry->LoadFile('CustomPage');
			$statistics = $GLOBALS['app']->Registry->Get('/gadgets/CustomPage/googleanalytics_code');
			if (!empty($statistics)) {
				$statistics = html_entity_decode($GLOBALS['app']->Registry->Get('/gadgets/CustomPage/googleanalytics_code'));
			}
			$this->_Template->SetVariable('STATISTICS', $statistics);
		}
        
		// Deprecated since 0.8: This is for backwards compatibility
        switch ($GLOBALS['app']->Registry->Get('/config/layoutmode')) {
            case 1: $layoutmode = 'twobar';
                    break;
            case 2: $layoutmode = 'leftbar';
                    break;
            case 3: $layoutmode = 'rightbar';
                    break;
            case 4: $layoutmode = 'nobar';
                    break;
        } 
        $this->_Template->SetVariable('layout-mode-name', $layoutmode);
        $this->_Template->SetVariable('encoding', 'utf-8');
        $this->_Template->SetVariable('loading-message', _t('GLOBAL_LOADING'));
		
		$this->AddHeadOther('<script type="text/javascript">
		Event.observe(window, "load", function(){$$(".menu_li_item").each(function(element){
			checkSubMenus(element);
			Tips.add(element, ($(element).down(".ul_sub_menu") ? "<div class=\"ym-vlist\"><ul class=\"ul_sub_menu\">"+$(element).down(".ul_sub_menu").innerHTML+"</ul></div>" : ""), {
				className: (element.hasClassName("menu_super") ? "slick" : "ym-hideme"),
				showOn: "mouseover",
				hideTrigger: "tip",
				hideOn: "mouseout",
				stem: false,
				delay: false,
				tipJoint: [ "center", "top" ],
				target: element,
				showEffect: "appear",
				offset: [ 0, ((-10)+($$("html")[0].style.paddingTop != "" && $$("html")[0].style.paddingTop != "0px" ? parseFloat($$("html")[0].style.paddingTop.replace("px", "")) : 0)) ]
			});
		});});</script>');
	}

    /**
     * Loads the template for head of control panel
     *
     * @access  public
     */
    function LoadControlPanelHead()
    {
        $this->AddHeadLink('gadgets/ControlPanel/resources/public.css', 'stylesheet', 'text/css');
        $this->AddHeadLink(PIWI_URL . 'piwidata/css/default.css', 'stylesheet', 'text/css');
        $this->AddJSPrototypeHeadLink();
		$this->AddHeadLink('gadgets/Menu/resources/style.css', 'stylesheet', 'text/css');
		$this->AddHeadLink('libraries/opentip/opentip.css', 'stylesheet', 'text/css');
		$this->AddScriptLink('libraries/js/global2.js');			
		$this->AddScriptLink('libraries/opentip/opentip.js');			
		$this->AddScriptLink('libraries/opentip/excanvas.js');			
		$this->AddScriptLink('libraries/window/dist/window.js');
        $this->AddHeadLink('libraries/window/themes/window/window.css', 'stylesheet', 'text/css');
        $this->AddHeadLink('libraries/window/themes/window/simpleblue.css', 'stylesheet', 'text/css');
        $this->AddHeadLink('libraries/window/themes/window/simplewhite.css', 'stylesheet', 'text/css');
        $this->AddHeadLink('libraries/window/themes/shadow/mac_shadow.css', 'stylesheet', 'text/css');

		$this->AddHeadOther('<style type="text/css">
		#quickuser_controls #quickuser_centered #quickuser_holder #jaws-menubar-login li a:hover, 
		#quickuser_controls #quickuser_centered #quickuser_holder #jaws-menubar-login li a:visited, 
		#quickuser_controls #quickuser_centered #quickuser_holder #jaws-menubar-login li a {
			border: 0px;
			line-height: 26px;
		}
		</style>');
		$this->AddHeadOther('<script type="text/javascript">
		Event.observe(window, "load", function(){$$(".menu_li_item").each(function(element){
			checkSubMenus(element);
			Tips.add(element, ($(element).down(".ul_sub_menu") ? "<div class=\"ym-vlist\"><ul class=\"ul_sub_menu\">"+$(element).down(".ul_sub_menu").innerHTML+"</ul></div>" : ""), {
				className: (element.hasClassName("menu_super") ? "slick" : "ym-hideme"),
				showOn: "mouseover",
				hideTrigger: "tip",
				hideOn: "mouseout",
				stem: false,
				delay: false,
				tipJoint: [ "center", "top" ],
				target: element,
				showEffect: "appear",
				offset: [ 0, ((-10)+($$("html")[0].style.paddingTop != "" && $$("html")[0].style.paddingTop != "0px" ? parseFloat($$("html")[0].style.paddingTop.replace("px", "")) : 0)) ]
			});
		});});</script>');

        $favicon = $GLOBALS['app']->Registry->Get('/config/site_favicon');
        if (!empty($favicon)) {
            $this->AddHeadLink($favicon, 'icon', 'image/png');
        }

        $GLOBALS['app']->LoadGadget('ControlPanel', 'AdminHTML');
        $this->_Template = new Jaws_Template('gadgets/ControlPanel/templates/');
        $this->_Template->Load('Layout.html');
        $this->_Template->SetBlock('layout');

        $base_url = $GLOBALS['app']->GetSiteURL('/');
        $this->_Template->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . "/");
        $this->_Template->SetVariable('BASE_URL', $base_url);
        $this->_Template->SetVariable('powered-by', $GLOBALS['app']->Registry->Get('/config/site_reseller'));
        $this->_Template->SetVariable('powered-link', $GLOBALS['app']->Registry->Get('/config/site_reseller_link'));
        $this->_Template->SetVariable('admin_script', BASE_SCRIPT);
        $this->_Template->SetVariable('site-name',        $GLOBALS['app']->Registry->Get('/config/site_name'));
        $this->_Template->SetVariable('site-slogan',      $GLOBALS['app']->Registry->Get('/config/site_slogan'));
        $this->_Template->SetVariable('site-author',      $GLOBALS['app']->Registry->Get('/config/site_author'));
        $this->_Template->SetVariable('site-copyright',   $GLOBALS['app']->Registry->Get('/config/copyright'));
        $this->_Template->SetVariable('control-panel', _t('CONTROLPANEL_NAME'));
        $this->_Template->SetVariable('loading-message', _t('GLOBAL_LOADING'));
        $this->_Template->SetVariable('navigate-away-message', _t('CONTROLPANEL_UNSAVED_CHANGES'));
        $this->_Template->SetVariable('encoding', 'utf-8');
    }

    /**
     * Loads the template for controlpanel
     *
     * @param   string  $gadget Gadget name
     * @access  public
     */
    function LoadControlPanel($gadget)
    {
        $this->_Template->SetBlock('layout/login-info', false);
        $this->_Template->SetVariable('logged-in-as', _t('CONTROLPANEL_LOGGED_IN_AS'));
        $this->_Template->SetVariable('username', $GLOBALS['app']->Session->GetAttribute('username'));
        require_once JAWS_PATH . 'include/Jaws/User.php';
        $userModel = new Jaws_User;
        $userInfo = $userModel->GetUserInfoByName($GLOBALS['app']->Session->GetAttribute('username'), true, true, true, true);
        if (Jaws_Error::isError($userInfo) || !isset($userInfo['nickname']) || !$GLOBALS['app']->Session->Logged()) {
            Jaws_Error::Fatal("User not logged in, try again.", __FILE__, __LINE__);
		}
		$this->_Template->SetVariable('nickname', $userInfo['nickname']);
        $this->_Template->SetVariable('email', $userInfo['email']);
        $this->_Template->SetVariable('user_image', $userModel->GetAvatar($userInfo['username'], $userInfo['email']));
        $this->_Template->SetVariable('site-url', $GLOBALS['app']->GetSiteURL());
        $this->_Template->SetVariable('my-account', _t('GLOBAL_MY_ACCOUNT'));
        $this->_Template->SetVariable('my-account-url', $GLOBALS['app']->Map->GetURLFor('Users', 'Profile'));
        $this->_Template->SetVariable('logout', _t('GLOBAL_LOGOUT'));
        $this->_Template->SetVariable('logout-url', BASE_SCRIPT . '?gadget=ControlPanel&amp;action=Logout');
        $this->_Template->ParseBlock('layout/login-info');

        // Set the header thingie for each gadget and the response box
        if (isset($gadget) && ($gadget != 'ControlPanel')){
            $gInfo  = $GLOBALS['app']->loadGadget($gadget, 'Info');
            $docurl = null;
            if (!Jaws_Error::isError($gInfo)) {
                $docurl = $gInfo->GetDoc();
            }
            $gname = _t(strtoupper($gadget) . '_NAME');
            $this->_Template->SetBlock('layout/cptitle');
            $this->_Template->SetVariable('admin_script', BASE_SCRIPT);
            $this->_Template->SetVariable('title-cp', _t('CONTROLPANEL_NAME'));
            $this->_Template->SetVariable('title-name', $gname);
            $this->_Template->SetVariable('icon-gadget', $GLOBALS['app']->GetJawsURL() . '/gadgets/'.$gadget.'/images/logo.png');
            $this->_Template->SetVariable('title-gadget', $gadget);
            if (!empty($docurl) && !is_null($docurl)) {
                $this->_Template->SetBlock('layout/cptitle/documentation');
                $this->_Template->SetVariable('src', $GLOBALS['app']->GetJawsURL() . '/images/stock/help-browser.png');
                $this->_Template->SetVariable('alt', _t('GLOBAL_READ_DOCUMENTATION'));
                $this->_Template->SetVariable('url', $docurl);
                $this->_Template->ParseBlock('layout/cptitle/documentation');
            }

            if (_t(strtoupper($gadget).'_ADMIN_MESSAGE') != strtoupper($gadget).'_ADMIN_MESSAGE*') {
                $this->_Template->SetBlock('layout/cptitle/description');
                $this->_Template->SetVariable('title-desc', _t(strtoupper($gadget) . '_ADMIN_MESSAGE'));
                $this->_Template->ParseBlock('layout/cptitle/description');
            }
            $this->_Template->ParseBlock('layout/cptitle');
        }

        if ($GLOBALS['app']->Registry->Get('/config/site_status') == 'disabled') {
            $this->_Template->SetBlock('layout/warning');
            $this->_Template->SetVariable('warning', _t('CONTROLPANEL_OFFLINE_WARNING'));
            $this->_Template->ParseBlock('layout/warning');
        }

        $responses = $GLOBALS['app']->Session->PopLastResponse();
        if ($responses) {
            foreach ($responses as $msg_id => $response) {
                $this->_Template->SetBlock('layout/msgbox');
                $this->_Template->SetVariable('msg-css', $response['css']);
                $this->_Template->SetVariable('msg-txt', $response['message']);
                $this->_Template->SetVariable('msg-id', $msg_id);
                $this->_Template->ParseBlock('layout/msgbox');
            }
        }
    }

    /**
     * Changes the site-title with something else
     *
     * @access  public
     * @param   string  $title  New title
     */
    function SetTitle($title)
    {
        $this->_Title = strip_tags($title);
    }

    /**
     * Assign the right head's title
     *
     * @access  public
     */
    function PutTitle()
    {
        if (!empty($this->_Title)) {
            $pageTitle = array($this->_Title, $GLOBALS['app']->Registry->Get('/config/site_name'));
        } else {
            $slogan = $GLOBALS['app']->Registry->Get('/config/site_slogan');
            $slogan = $GLOBALS['app']->UTF8->str_replace('<br />', '&nbsp;'.$GLOBALS['app']->Registry->Get('/config/title_separator').'&nbsp;', $slogan);
            $slogan = $GLOBALS['app']->UTF8->str_replace('<br>', '&nbsp;'.$GLOBALS['app']->Registry->Get('/config/title_separator').'&nbsp;', $slogan);
            $pageTitle   = array();
            $pageTitle[] = $GLOBALS['app']->Registry->Get('/config/site_name');
            if (!empty($slogan)) {
                $pageTitle[] = $slogan;
            }
        }
        $pageTitle = implode(' ' . $GLOBALS['app']->Registry->Get('/config/title_separator').' ', $pageTitle);
        $this->_Template->ResetVariable('site-title', strip_tags($pageTitle), 'layout');
    }

    /**
     * Changes the site-description with something else
     *
     * @access  public
     * @param   string  $desc  New description
     */
    function SetDescription($desc)
    {
        $this->_Description = strip_tags($desc);
    }

    /**
     * Assign the right page's description
     *
     * @access  public
     */
    function PutDescription()
    {
        if (empty($this->_Description)) {
            $this->_Description = $GLOBALS['app']->Registry->Get('/config/site_description');
        }
        $this->_Template->ResetVariable('site-description', strip_tags($this->_Description), 'layout');
    }

    /**
     * Add keywords to meta keywords tag
     *
     * @access  public
     * @param   string  $keywords  page keywords
     */
    function AddToMetaKeywords($keywords)
    {
        if (!empty($keywords)) {
            $keywords = array_map(array('Jaws_UTF8','trim'), explode(',', $keywords));
            $this->_Keywords = array_merge($this->_Keywords, $keywords);
        }
    }

    /**
     * Assign the site keywords
     *
     * @access  public
     */
    function PutMetaKeywords()
    {
        $this->_Template->ResetVariable('site-keywords',
                                        strip_tags(implode(', ', $this->_Keywords)),
                                        'layout');
    }

    /**
     * Add a language to meta language tag
     *
     * @access  public
     * @param   string  $language  Language
     */
    function AddToMetaLanguages($language)
    {
        if (!empty($language)) {
            if (!in_array($language, $this->_Languages)) {
                $this->_Languages[] = $language;
            }
        }
    }

    /**
     * Assign the site languages
     *
     * @access  public
     */
    function PutMetaLanguages()
    {
        $this->_Template->ResetVariable('site-languages',
                                        strip_tags(implode(',', $this->_Languages)),
                                        'layout');
    }

    /**
     * Add replacement variables to the array
     *
     * @access  public
     * @param   string  $replacements  template variable replacements
     */
    function AddToCustomReplacements($replacements)
    {
        if (!count($replacements) <= 0) {
            $this->_CustomReplacements = array_merge($this->_CustomReplacements, $replacements);
        }
    }

    /**
     * Assign the custom replacement variables
     *
     * @access  public
     */
    function PutCustomReplacements()
    {
		if (!count($this->_CustomReplacements) <= 0) {
			foreach ($this->_CustomReplacements as $rk => $rv) {
				if ($this->_Template->VariableExists($rk)) {
					$this->_Template->ResetVariable($rk,
												$rv,
												'layout');
				}
			}
		}
	}

    /**
     * Get replacement variables array
     *
     * @access  public
     * @return  array   CustomReplacements
     */
    function GetCustomReplacements()
    {
        return $this->_CustomReplacements;
    }

    /**
     * Set replacement variables array
     *
     * @access  public
     */
    function SetCustomReplacements($replacements = array())
    {
        $this->_CustomReplacements = $replacements;
    }

    /**
     * Returns the items that should be displayed in the layout
     *
     * @access  public
     * @return  array   Items according to BASE_SCRIPT
     */
    function GetLayoutItems($gadget = null, $action = null, $linkid = null, $index = null)
    {
        if (JAWS_SCRIPT == 'index') {
            return $this->_Model->GetLayoutItems($gadget, $action, $linkid, $index);
        }
        $items = array();
        $items[] = array('id'            => null,
                         'gadget'        => '[REQUESTEDGADGET]',
                         'gadget_action' => '[REQUESTEDACTION]',
                         'display_when'  => '*',
                         'section'       => 'main',
                         );
        return $items;
    }

    /**
     * Is gadget item displayable?
     *
     * @access  public
     * @return  boolean
     */
    function IsDisplayable($gadget, $action, $display_when, $index, $id = null)
    {
        return true;
		$displayWhen = explode(',', $display_when);
        if ($display_when == '*' || ($index && in_array('index', $displayWhen))) {
            return true;
        }
		$return = false;
		foreach ($displayWhen as $item) {
			$gActions = explode(';', $item);
			$g = array_shift($gActions);
			if ($g == $gadget) {
				if (empty($gActions) || in_array($action, $gActions)) {
					return true;
				}
				break;
			}
        }
        return $return;
    }

    /**
     * Look for the available gadgets and put them on the template
     *
     * @access  public
     */
    function Populate(&$goGadget, $am_i_index = false, $http_error = '')
    {
        $this->_RequestedGadget = empty($goGadget)? '': $goGadget->GetName();
        $this->_RequestedAction = empty($goGadget)? '': $goGadget->GetAction();
		$items = $this->GetLayoutItems($this->_RequestedGadget, $this->_RequestedAction, $this->_RequestedId, $am_i_index);
		if (is_array($items)) {
			$contents = array();
            $gadgetsInLayout = array();
			$contentString  = '';
			$requestedString  = '';
			// Populate REQUESTEDGADGET first, to give precedence to layout items loaded by it.
			foreach ($items as $item) {
				if ($item['gadget'] == '[REQUESTEDGADGET]') {
					if (empty($this->_RequestedGadget)) {
						break;
					}
					if ($this->_Section != $item['section']) {
						if (!empty($this->_Section)) {
							$this->_Template->SetVariable('ELEMENT', $requestedString);
							$this->_Template->ParseBlock('layout/' . $this->_Section);
							$this->_Section = '';
						}
						if (!$this->_Template->BlockExists('layout/' . $item['section'])) {
							continue;
						}
						$this->_Section = $item['section'];
						$this->_Template->SetBlock('layout/' . $this->_Section);
						$this->_SectionAttributes = $this->_Template->GetCurrentBlockAttributes();
						$currentContent = $this->_Template->GetCurrentBlockContent();
						$this->_Template->SetCurrentBlockContent('{ELEMENT}');
						$requestedString  = '';
					}
					if ($this->IsDisplayable(
						$this->_RequestedGadget,
						$this->_RequestedAction,
						$item['display_when'],
						$am_i_index,
						$this->_RequestedId)
					) {
						if (!isset($contents[$item['section']])) {
							$contents[$item['section']] = array();
						}

						$content = '';
	                    if (!empty($http_error)) {
	                        require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
	                        $content = Jaws_HTTPError::Get($http_error);
	                    } else {	
							$gadgetsInLayout[$goGadget->GetName()] = $goGadget->GetName();
							if (Jaws_Gadget::IsGadgetUpdated($goGadget->GetName())) {
								$content = $this->PutGadget($goGadget->GetName(), $goGadget->GetAction(), $item['section'], true);
							} elseif (isset($GLOBALS['log'])) {
								$GLOBALS['log']->Log(JAWS_LOG_ERR, 'Trying to populate ' . $goGadget->GetName() .
													 ' in layout, but looks that it is not installed/upgraded');
							}
						}
						if (!empty($content)) {
							$requestedString .= str_replace('{ELEMENT}', $content, $currentContent)."\n\n\n";
						}
					}
					break;
				} else {
					continue;
				}
			}
			$i = 0;
			// Then populate the rest
			foreach ($items as $item) {
                // We've already populated REQUESTEDGADGET
				if (empty($this->_RequestedGadget) && ($item['gadget'] == '[REQUESTEDGADGET]')) {
                    continue;
                }

                if ($this->_Section != $item['section']) {
					$i = 0;
                    if (!empty($this->_Section)) {
                        $this->_Template->SetVariable('ELEMENT', $contentString);
                        $this->_Template->ParseBlock('layout/' . $this->_Section);
                        $this->_Section = '';
                    }
                    if (!$this->_Template->BlockExists('layout/' . $item['section'])) {
                        continue;
                    }
                    $this->_Section = $item['section'];
                    $this->_Template->SetBlock('layout/' . $this->_Section);
                    $this->_SectionAttributes = $this->_Template->GetCurrentBlockAttributes();
                    $currentContent = $this->_Template->GetCurrentBlockContent();
                    $this->_Template->SetCurrentBlockContent('{ELEMENT}');
                    $contentString  = '';
                }

                if ($this->IsDisplayable(
					$this->_RequestedGadget,
                    $this->_RequestedAction,
                    $item['display_when'],
                    $am_i_index,
					$this->_RequestedId)
				) {
                    if (!isset($contents[$item['section']])) {
                        $contents[$item['section']] = array();
                    }

                    $content = '';
                    if ($item['gadget'] != '[REQUESTEDGADGET]') {
						$gadgetsInLayout[$item['gadget']] = $item['gadget'];
						if (Jaws_Gadget::IsGadgetUpdated($item['gadget'])) {
							$content = $this->PutGadget($item['gadget'], $item['gadget_action'], $item['section']);
						}  elseif (isset($GLOBALS['log'])) {
							$GLOBALS['log']->Log(JAWS_LOG_ERR, 'Trying to populate ' . $item['gadget'] .
													' in layout, but looks that it is not installed/upgraded');
						}
                    }
					if ($item['gadget'] == '[REQUESTEDGADGET]') {
						$contentString .= $requestedString;
					} else {
						if (!empty($content)) {
							$evenodd = ($i == 0 || (($i % 2) == 0) ? 'even' : 'odd');
							$content = str_replace('__EVENODD__', $evenodd, $content);
							//$content = str_replace('__LAYOUT_ID__', $item['id'], $content);
							$contentString .= str_replace('{ELEMENT}', $content, $currentContent)."\n\n\n";
						}
					}
					$i++;
				}
            }
            if (!empty($this->_Section)) {
                $this->_Template->SetVariable('ELEMENT', $contentString);
                $this->_Template->ParseBlock('layout/' . $this->_Section);
            }
        }
    }

    /**
     * Put a gadget on the template
     *
     * @access  public
     * @param   string  $gadget  Gadget to put
     * @param   string  $action  Action to execute
     * @param   string  $section Where to put it
     * @param   boolean $requested Requested gadget (to know if we should load the LayoutHTML file or not)
     */
    function PutGadget($gadget, $action, $section, $requested = false)
    {
        $enabled = $GLOBALS['app']->Registry->Get('/gadgets/' . $gadget . '/enabled');
        if (Jaws_Error::isError($enabled)) {
            $enabled = 'false';
        }

		$output = '';
        if ($enabled == 'true') {
			if (
				JAWS_SCRIPT == 'index' ||  
				strtolower($this->_RequestedAction) == 'ajax' ||  
				(JAWS_SCRIPT == 'admin' && 
				($this->_RequestedGadget == 'Layout' || ($this->_RequestedGadget == 'CustomPage' &&   
					strtolower($this->_RequestedAction) == 'view')))
			) {
				// Gadget requires HTTPS?
				$require_https = $GLOBALS['app']->Registry->Get('/gadgets/require_https');
				if (
					(in_array(strtolower($gadget), explode(',', strtolower($require_https))) || 
						strtolower($gadget) == strtolower($require_https)) && 
					(!isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) != 'on')
				) {
					$full_url = $GLOBALS['app']->getFullURL();
					if (!empty($full_url)) {	
						require_once JAWS_PATH . 'include/Jaws/Header.php';
						Jaws_Header::Location(
							str_replace(
								'http://'.str_replace('http://', '', $GLOBALS['app']->GetSiteURL()), 
								'https://'.str_replace('https://', '', $GLOBALS['app']->Registry->Get('/config/site_ssl_url')), 
								$full_url
							)
						);
					}
				}
				
                
				if ($requested === true) {
                    $goGadget = $GLOBALS['app']->loadGadget($gadget, 'HTML');
                    $goGadget->SetAction($action);
                    //$this->_Template->SetVariable('ELEMENT', $goGadget->Execute());
                    $output = $goGadget->Execute();
                } else {
                    preg_match_all('/^([a-z0-9]+)\((.*?)\)$/i', $action, $matches);
                    if (isset($matches[1][0]) && isset($matches[2][0])) {
                        $action = $matches[1][0];
                        $params = $matches[2][0];
                    }

                    $goGadget = $GLOBALS['app']->loadGadget($gadget, 'LayoutHTML');
                    if (!Jaws_Error::isError($goGadget)) {
                        $GLOBALS['app']->Registry->LoadFile($gadget);
						$GLOBALS['app']->Translate->LoadTranslation($gadget, JAWS_GADGET);
                        if (method_exists($goGadget, $action)) {
                            $output = isset($params)? $goGadget->$action($params) : $goGadget->$action();
							// For backwards compatibility with CustomPage gadget
							// TODO: Add this to CustomPage ugradeScript and remove from here
							if (
								substr($section, 0, 7) == 'section' && 
								substr($action, 0, 8) != 'ShowPost' && 
								Jaws_Gadget::IsGadgetUpdated('CustomPage')
							) {
								$posttpl = new Jaws_Template('gadgets/CustomPage/templates/');
								$posttpl->Load('Post.html');
								$image_a = $GLOBALS['app']->UTF8->str_replace(' ', '_', $action);
								$image_a = $GLOBALS['app']->UTF8->str_replace('(', '_', $image_a);
								$image_a = $GLOBALS['app']->UTF8->str_replace(')', '_', $image_a);
								
								$posttpl->SetBlock('post_gadget');
								$posttpl->SetVariable('id', $image_a);
								$posttpl->SetVariable('gadget_action', $image_a);
								$posttpl->SetVariable('type', 'gadget');
								$posttpl->SetVariable('content', $output);
								$posttpl->ParseBlock('post_gadget');
								$output = $posttpl->Get();
							}
						} elseif (isset($GLOBALS['log'])) {
                            $GLOBALS['log']->Log(JAWS_LOG_ERR, "Action $action in $gadget's LayoutHTML dosn't exist.");
                        }
                    } else {
                        //$this->_Template->SetVariable('ELEMENT', '');
                        if (isset($GLOBALS['log'])) {
                            $GLOBALS['log']->Log(JAWS_LOG_ERR, $gadget ." is missing the LayoutHTML. We can't execute Layout " .
                                                 "actions if the file doesn't exist");
                        }
                    }
                }
            } else {
				$this->AddHeadLink(
					'gadgets/'.$gadget.'/resources/style.css',
					'stylesheet',
					'text/css',
					'',
					null,
					true
				);
                $goGadget = $GLOBALS['app']->loadGadget($gadget, 'AdminHTML');
                if (!Jaws_Error::isError($goGadget)) {
                    $goGadget->SetAction($action);
                    //$this->_Template->SetVariable('ELEMENT', $goGadget->Execute());
                    $output = $goGadget->Execute();
                } else {
                    //$this->_Template->SetVariable('ELEMENT', '');
                }
            }
        } else {
            Jaws_Error::Fatal('Gadget ' . $gadget . ' is not enabled', __FILE__, __LINE__);
        }
        if (Jaws_Error::isError($output)) {
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_ERR, 'In '.$gadget.'::'.$action.','.$output->GetMessage());
            }
            return '';
        }

        return $output;
    }

    /**
     * Get the HTML code of the head content.
     *
     * @access  public
     */
    function GetHeaderContent(&$headLink, &$headScript, &$headMeta, &$headOther)
    {			
		$headContent = '';
        // meta
        foreach ($headMeta as $meta) {
            if ($meta['use_http_equiv']) {
                $meta_add = 'http-equiv="' . $meta['name'] . '"';
            } else {
                $meta_add = 'name="' . $meta['name'] . '"';
            }

            if (!in_array('<meta ' . $meta_add . ' content="' . $meta['content'] . '" />', $existingHeadContent)) {
				$headContent .= '<meta ' . $meta_add . ' content="' . $meta['content'] . '" />'. "\n";
			}
		}
		
        // link
		$linkContent = '';
		foreach ($headLink as $link) {
			$title = '';
			$linkString = '<link rel="' . $link['rel'] . '"';
			if (!empty($link['media'])) {
				$linkString.= ' media="' . $link['media'] . '"';
			}
			if (!empty($link['type'])) {
				$linkString.= ' type="' . $link['type'] . '"';
			}
			if (!empty($link['href'])) {
				$linkString.= ' href="' . $link['href'] . '"';
			}
			if (!empty($link['title'])) {
				$linkString.= ' title="' . $link['title'] . '"';
			}
			$linkString .= ' />';
            if (!in_array($linkString, $existingHeadContent)) {
				$linkContent .= $linkString . "\n";
			}
        }
		$headContent .= $linkContent;
		
		// Overloaded CSS file?				
		if (
			(BASE_SCRIPT == 'index.php' || 
			(BASE_SCRIPT == 'admin.php' && 
				($this->_RequestedGadget == 'Layout' || ($this->_RequestedGadget == 'CustomPage' && $this->_RequestedAction == 'view')) && 
				$GLOBALS['app']->Session->Logged())) && 
			file_exists(JAWS_DATA . 'files/css/custom.css')
		) {
			$mod_time = filemtime(JAWS_DATA . 'files/css/custom.css');
			$headContent.= '<link rel="stylesheet" media="screen" type="text/css" href="'.
				$GLOBALS['app']->getDataURL('', true).'files/css/custom.css'.
				($mod_time !== false && is_numeric($mod_time) ? '?'.$mod_time : '?'.time()).'" />'."\n";
		}
		$request =& Jaws_Request::getInstance();
		$customtheme = $request->get('customtheme', 'get');
		if (!empty($customtheme)) {
			$customtheme = urldecode($customtheme);
			if (!in_array('<link rel="stylesheet" media="screen" type="text/css" href="' . $customtheme . '" />', $existingHeadContent)) {
				$headContent.= '<link rel="stylesheet" media="screen" type="text/css" href="' . $customtheme . '" />' . "\n";			
			}
		}
		
		// other CSS
		$otherCSSContent = '';
		foreach ($headOther as $element) {
			if (
				!in_array($element, $existingHeadContent) && 
				strpos($element, "<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" href=\"") !== false
			) {
				$otherCSSContent .= $element . "\n";
			}
		}
		$headContent .= $otherCSSContent;
		
		// scripts
		$site_url = $GLOBALS['app']->GetSiteURL('', false, 'http');
		$site_ssl_url = $GLOBALS['app']->GetSiteURL('', false, 'https');
		$GZheadContent = '<script type="text/javascript" src="'.$GLOBALS['app']->GetSiteURL().'/gz.php?type=javascript';
		$scriptHeadContent = '';
		$i = 0;
		foreach ($headScript as $link) {
			if (
				substr(strtolower($link['href']), 0, 5) == 'index' || substr(strtolower($link['href']), 0, 5) == 'admin' || 
				substr(strtolower($link['href']), 0, strlen(strtolower($site_url))) == $site_url || 
				substr(strtolower($link['href']), 0, strlen(strtolower($site_ssl_url))) == $site_ssl_url || 
				(strpos(strtolower($site_url), '/', 9) !== false && 
				substr(strtolower($link['href']), 0, strpos(strtolower($site_url), '/', 9)) == substr(strtolower($site_url), 0, strpos(strtolower($site_url), '/', 9))) || 
				(strpos(strtolower($site_ssl_url), '/', 9) !== false && 
				substr(strtolower($link['href']), 0, strpos(strtolower($site_ssl_url), '/', 9)) == substr(strtolower($site_ssl_url), 0, strpos(strtolower($site_ssl_url), '/', 9))) || 
				substr(strtolower($link['href']), 0, 26) == 'http://ajax.googleapis.com' || 
				substr(strtolower($link['href']), 0, 27) == 'https://ajax.googleapis.com' || 
				substr(strtolower($link['href']), 0, 23) == 'https://maps.google.com'
				substr(strtolower($link['href']), 0, 22) == 'http://maps.google.com'
			) {
				$GZheadContent .= '&uri'.$i.'=' . urlencode($link['href']);
				$i++;
			} else if (!in_array('<script type="' . $link['type'] . '" src="' . $link['href'] . '"></script>', $existingHeadContent)) {
				$scriptHeadContent .= '<script type="' . $link['type'] . '" src="' . $link['href'] . '"></script>' . "\n";
			}
        }
		if (
			!in_array($GZheadContent . '"></script>', $existingHeadContent) && 
			$GZheadContent != '<script type="text/javascript" src="'.$GLOBALS['app']->GetSiteURL().'/gz.php?type=javascript'
		) {
			$headContent .= $GZheadContent . '"></script>' . "\n";
		}
		$headContent .= $scriptHeadContent;

		// other
        foreach ($headOther as $element) {
            if (
				!in_array($element, $existingHeadContent) && 
				strpos($element, "<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" href=\"") === false
			) {
				$headContent .= $element . "\n";
			}
		}
		
		return $headContent;
    }

    /**
     * Shows the HTML of the Layout.
     *
     * @access  public
     */
    function Show($res_echo = true, $inline = false)
    {
		$headContent = $this->GetHeaderContent($this->_HeadLink, $this->_ScriptLink, $this->_HeadMeta, $this->_HeadOther);

        if ($inline === false && $this->_standAloneMode === false) {
			if (!empty($headContent)) {
				$this->_Template->SetBlock('layout/head');
				$this->_Template->SetVariable('ELEMENT', $headContent);
				$this->_Template->ParseBlock('layout/head');
			}

			if (JAWS_SCRIPT == 'index') {
				$this->PutTitle();
				$this->PutDescription();
				$this->PutMetaKeywords();
				$this->PutMetaLanguages();
			}
		}
		
		if (JAWS_SCRIPT == 'index') {
			$this->PutCustomReplacements();
		}
			
        // parse template an show the HTML
        $this->_Template->ParseBlock('layout');
		$output = $this->_Template->Get();
				
		if (JAWS_SCRIPT == 'admin') {
			if (
				(empty($this->_RequestedGadget) && empty($this->_RequestedAction)) || 
				($this->_RequestedGadget == 'ControlPanel' && $this->_RequestedAction == 'DefaultAction')
			) {
				if (!Jaws_Session_Web::GetCookie('tip0')) {
					$tip = "<script type=\"text/javascript\">document.whenReady(showWindow);var w1;function showWindow() {var url = '".$GLOBALS['app']->GetSiteURL()."/admin.php?gadget=ControlPanel&action=ShowTip&tip=tip0'; w1 = new UI.URLWindow({theme: \"simplewhite\",height: 300,width: 400,shadow: true,minimize: false,maximize: false,close: 'destroy',resizable: false,draggable: true,url: url});w1.show().focus();/*w1.adapt.bind(w1).delay(0.3); w1.center();*/w1.bottomRight();/* Hook the window to an item if ($('body')) {var items = $('body').getElementsByTagName('div')[1].getElementsByTagName('a');for (var i = 0; i < items.length; i++) {if (items[i].innerHTML.indexOf('Custom Page') > -1) {currentItem = $(items[i]);break;}}if (currentItem) {var itemOffset = currentItem.cumulativeOffset();newTop = ((itemOffset.top - windowSize.height) > 10 ? (itemOffset.top - windowSize.height) : 10);newLeft = (Math.round(itemOffset.left + ((currentItem.offsetWidth/2)-(windowSize.width/2))) > 10 ? Math.round(itemOffset.left + ((currentItem.offsetWidth/2)-(windowSize.width/2))) : 10);w1.setPosition(newTop, newLeft);} else {w1.destroy();}}*/}Event.observe(window, \"resize\", function() {/*w1.center();*/w1.bottomRight();});</script><!--[if lte IE 7]><style>html>body .ui-window .content { border-top: 1px solid #FFF;}</style><![endif]-->";
					$output = str_replace('</body>', $tip.'</body>', $output);
				}
			}
		}
		
		// Quickmenu dock
		if ($GLOBALS['app']->Session->Logged() && ($inline === false  && $this->_standAloneMode === false)) {
			if (
				JAWS_SCRIPT == 'admin' && 
				(((is_null($this->_RequestedGadget) || empty($this->_RequestedGadget)) && 
				(is_null($this->_RequestedAction) || empty($this->_RequestedAction))) || 
				($this->_RequestedGadget == 'Layout' || 
				($this->_RequestedGadget == 'ControlPanel' && $this->_RequestedAction == 'DefaultAction') || 
				($this->_RequestedGadget == 'CustomPage' && $this->_RequestedAction == 'view')))
			) {
			} else if (
				strpos($output, "<!-- start_quickmenu -->") === false && 
				($GLOBALS['app']->Session->IsAdmin() || $GLOBALS['app']->Session->IsSuperAdmin())
			) {
				$cp_admin = $GLOBALS['app']->LoadGadget('ControlPanel', 'AdminHTML');
				$quickmenu = $cp_admin->MainMenu(false, true);
				$quickmenu_head_content = "<link rel=\"stylesheet\" media=\"Screen\" type=\"text/css\" href=\"" . $GLOBALS['app']->GetJawsURL() . "/libraries/carousel/themes/carousel/prototype-ui.css\" /><script type=\"text/javascript\" src=\"" . $GLOBALS['app']->GetJawsURL() . "/libraries/carousel/dist/carousel.js\"></script><style type=\"text/css\">#horizontal_carouselDock #next_buttonDock, #horizontal_carouselDock #previous_buttonDock {height: 80px;} #quickmenu_centered{display: table; margin: 0 auto; text-align: center; /* fixes IE bug */} #quickmenu_centered > div{display: inline-block; /* fixes IE bug */} #quickmenu_centered > div{display: inline; /* fixes IE bug */} #quickmenu_centered > div{display: table-cell; text-align: left; border-bottom: 2px solid rgb(220,220,200);} #quickmenu_centered > div > div > div{height: 100px; overflow: hidden; padding: 0 20px;} #dock img {cursor: pointer; cursor: hand;} #quickmenu_controls { -moz-background-clip: border; -moz-background-inline-policy: continuous; -moz-background-origin: padding; background: transparent url('" . $GLOBALS['app']->GetJawsURL() . "/gadgets/ControlPanel/images/layout-controls-bg.png') repeat scroll 0 0; bottom: 0; color: #FFFFFF; direction: ltr; font-family: \"Lucida Grande\",Myriad,\"Andale Sans\",\"Luxi Sans\",\"Bitstream Vera Sans\",Tahoma,\"Toga Sans\",Helvetica,Arial,sans-serif; font-size: 12px; height: 80px; left: 0; padding-top: 5px; position: fixed; text-align:center; width:100%; z-index: 2147483643;} #container {padding-bottom: 130px;} #quickmenu_centered {position: absolute; visibility: hidden; height: 80px; width: 1000px;}</style>";
				$output = str_replace('</head>', $quickmenu_head_content.'</head>', $output);
				$output = eregi_replace('<body([^>]*)>', '<body\1>' . $quickmenu, $output);
				$quickmenu_end_content = "<script type=\"text/javascript\">function positionIt() {if( document.getElementById( \"quickmenu_centered\" ) ) {/* Get a reference to divTest and measure its width and height. */ var div = document.getElementById( \"quickmenu_centered\" ); var divWidth = (div.offsetWidth ? div.offsetWidth : (div.style.width ? parseInt( div.style.width ) : 0)); /* Calculating setX and setX so the div will be centered in the viewport. */ var setX = ( getViewportWidth() - divWidth ) / 2; /* If setX or setY have become smaller than 0, make them 0. */ if( setX < 0 ) setX = 0; /* Position the div in the center of the page and make it visible. */ div.style.left = setX + \"px\"; div.style.visibility = \"visible\";}} function getViewportWidth() {var width = 0;if( document.documentElement && document.documentElement.clientWidth ) {width = document.documentElement.clientWidth;}else if( document.body && document.body.clientWidth ) {width = document.body.clientWidth;}else if( window.innerWidth ) {width = window.innerWidth - 18;}return width;} Event.observe(window, 'load', function() { /*var dock = new MacStyleDock(document.getElementById('dock'),createDockParameters(),48,64,2);*/ positionIt();}, false); window.onresize = positionIt;</script>";
				$output = str_replace('</body>', $quickmenu_end_content.'</body>', $output);
			}
			if (
				strpos($output, "<!-- start_quickuser -->") === false
			) {
				$GLOBALS['app']->Registry->LoadFile('Users');
				$GLOBALS['app']->Translate->LoadTranslation('Users', JAWS_GADGET);
				$userLayout = $GLOBALS['app']->LoadGadget('Users', 'LayoutHTML');
				$quickuser = "<div id=\"quickuser_controls\"><div id=\"quickuser_centered\"><div id=\"quickuser_holder\">".$userLayout->LoginLinks()."</div></div></div>";
				$quickuser_head_content = "<style type=\"text/css\">#quickuser_controls { height: 45px; background: url(\"" . $GLOBALS['app']->GetJawsURL() . "/gadgets/ControlPanel/images/user-controls-bg.png\") repeat scroll 0pt 0pt transparent; color: #000000; direction: ltr; position: fixed; top: -10px; left: 0px; font-family: \"Lucida Grande\",Myriad,\"Andale Sans\",\"Luxi Sans\",\"Bitstream Vera Sans\",Tahoma,\"Toga Sans\",Helvetica,Arial,sans-serif; font-size: 12px; text-align: center; width: 100%; z-index: 2147483644; padding-top: 14px;}#quickuser_centered {margin: auto; visibility: visible; width:1000px; height: 45px;} #quickuser_centered #quickuser_holder {text-align: left; color: #000000; border-top: 0px none; display: block; float: none; clear: both;} #quickuser_centered #quickuser_holder .clearfix {text-align: left;} #quickuser_centered #quickuser_holder #jaws-menubar-login {background: none repeat scroll 0 0 transparent;float: left;font-size: 100%;list-style: none outside none;margin: 0 0 1.5em;padding: 0;width: 100%;line-height: 25px;} #quickuser_centered #quickuser_holder #jaws-menubar-login li {background: none repeat scroll 0 0 transparent;float: left;padding: 0 0 0 12px;} #quickuser_centered #quickuser_holder #jaws-menubar-login li:first-child {margin-left: 1em;}#quickuser_centered #quickuser_holder #jaws-menubar-login #menu-option-Logged, #quickuser_centered #quickuser_holder #jaws-menubar-login #menu-option-Profile {display: inline;}#quickuser_centered #quickuser_holder #jaws-menubar-login li a:hover,#quickuser_centered #quickuser_holder #jaws-menubar-login li a:visited,#quickuser_centered #quickuser_holder #jaws-menubar-login li a {background: none repeat scroll 0 0 transparent;border-bottom: 0 none;color: #333333; display: inline;font-weight: bold;padding: 0;text-decoration: none;vertical-align: baseline;}#quickuser_centered #quickuser_holder #jaws-menubar-login li a:hover {color: #000000; }#quickuser_centered #quickuser_holder #jaws-menubar-login #menu-option-Avatar a img {background-color: rgb(255, 255, 255); height: 20px; width: 20px; max-height: 20px; max-width: 20px; margin-top: 2px; border: 1px solid #999999; padding: 1px;} </style>";
				$output = str_replace('</head>', $quickuser_head_content.'</head>', $output);
				$output = eregi_replace('<body([^>]*)>', '<body\1><!-- start_quickuser -->' . $quickuser . '<!-- end_quickuser -->', $output);
				$html_background = '';
				$output = str_replace('<html', '<html style="padding-top: 35px;'.$html_background.'"', $output);
			}
		}
						
		if (strpos($output, 'class="homepage ') !== false) {
			// Let everyone know
			$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
			$res = $GLOBALS['app']->Shouter->Shout('onAfterLoadHomepage', null);
			if (Jaws_Error::IsError($res) || !$res) {
			} else if (isset($res['vars'])) {
				foreach ($res['vars'] as $var_key => $var_val) {
					$output = str_replace('{'.$var_key.'}', $var_val, $output);
				}
			}
		}
		
		if (strpos($output, "<!-- Working Notification -->") === false) {
			$working_notification_head = "<style type=\"text/css\">#working_notification { background: url(\"images/transparent.png\") repeat scroll 2px center transparent;color: #FFFFFF;font-size: 10pt;font-weight: bold;height: 100%;padding: 4px 10px 4px 0;position: fixed;right: 0;text-align: right;top: 0;visibility: hidden;width: 100%;}</style>";
			$working_notification = "\n<!-- Working Notification -->\n<div id=\"working_notification\" class=\"working_notification\"></div>";
			$working_notification .= "<script type=\"text/javascript\">Event.observe(window, 'resize', function(){";
			$working_notification .= "$('working_notification').style.height = '100%'; ";
			$working_notification .= "$('working_notification').style.width = '100%';});";
			$working_notification .= "var loading_message = \""._t('GLOBAL_LOADING')."\";</script>";
			$working_notification .= "\n<!-- /Working Notification -->\n";
			$output = str_replace('</head>', $working_notification_head.'</head>', $output);
			$output = eregi_replace('<body([^>]*)>', '<body\1>' . $working_notification, $output);
		}
														
		// Insert any requested Layout Actions
		$jms = $GLOBALS['app']->LoadGadget('Jms', 'AdminModel');
		$gadget_list = $jms->GetGadgetsList();
		while (strpos($output, '{GADGET:') !== false) {
			$post_gadget = '';
			$post_action = '';
			$inputStr = $output;
			$delimeterLeft = "{GADGET:";
			$delimeterRight = "|";
			$startLeft = strpos($inputStr, $delimeterLeft);
			$posLeft = ($startLeft+strlen($delimeterLeft));
			$posRight = strpos($inputStr, $delimeterRight, $posLeft);
			$post_gadget = substr($inputStr, $posLeft, $posRight-$posLeft);
			$delimeterLeft = "|ACTION:";
			$delimeterRight = "}";
			$posLeft = strpos($inputStr, $delimeterLeft);
			$posLeft += strlen($delimeterLeft);
			$posRight = strpos($inputStr, $delimeterRight, $posLeft);
			$endRight = $posRight;
			$post_action = substr($inputStr, $posLeft, $posRight-$posLeft);
			$layout_html = '';
			$layoutGadget = $GLOBALS['app']->LoadGadget($post_gadget, 'LayoutHTML');
			if (!Jaws_Error::isError($layoutGadget)) {
				$GLOBALS['app']->Registry->LoadFile($post_gadget);
				if (strpos($post_action, '(') === false) {
					if (method_exists($layoutGadget, $post_action)) {
						$layout_html = $layoutGadget->$post_action();
					} elseif (isset($GLOBALS['log'])) {
						$GLOBALS['log']->Log(JAWS_LOG_ERR, "Action ".$post_action." in ".$post_gadget."'s LayoutHTML doesn't exist.");
					}
				} else {
					preg_match_all('/^([a-z0-9]+)\((.*?)\)$/i', $post_action, $matches);
					if (isset($matches[1][0]) && isset($matches[2][0])) {
						if (isset($matches[1][0])) {
							if (method_exists($layoutGadget, $matches[1][0])) {
								$layout_html = $layoutGadget->$matches[1][0]($matches[2][0]);
							} elseif (isset($GLOBALS['log'])) {
								$GLOBALS['log']->Log(JAWS_LOG_ERR, "Action ".$matches[1][0]." in ".$post_gadget."'s LayoutHTML doesn't exist.");
							}
						}
					}
				}
			} else {
				if (isset($GLOBALS['log'])) {
					$GLOBALS['log']->Log(JAWS_LOG_ERR, $post_gadget ." is missing the LayoutHTML. Jaws can't execute Layout " .
										 "actions if the file doesn't exists");
				}
			}
			unset($layoutGadget);
			
			if (Jaws_Error::isError($layout_html)) {
				if (isset($GLOBALS['log'])) {
					$GLOBALS['log']->Log(JAWS_LOG_ERR, $layout_html->GetMessage());
				}
				$layout_html = '';
			}
			$output = substr_replace($output, $layout_html, $startLeft, ($endRight-$startLeft)+1);
		}
		
		// Syntacts Translations
		while (strpos($output, '{TRANSLATE:') !== false) {
			$translate = '';
			$translate_gadget = '';
			$inputStr = $output;
			$delimeterLeft = "{TRANSLATE:";
			$delimeterRight = "|";
			$startLeft = strpos($inputStr, $delimeterLeft);
			$posLeft = ($startLeft+strlen($delimeterLeft));
			$posRight = strpos($inputStr, $delimeterRight, $posLeft);
			$translate = substr($inputStr, $posLeft, $posRight-$posLeft);
			$delimeterLeft = "|GADGET:";
			$delimeterRight = "}";
			$posLeft = strpos($inputStr, $delimeterLeft);
			$posLeft += strlen($delimeterLeft);
			$posRight = strpos($inputStr, $delimeterRight, $posLeft);
			$endRight = $posRight;
			$translate_gadget = substr($inputStr, $posLeft, $posRight-$posLeft);
			$GLOBALS['app']->Registry->LoadFile($translate_gadget);
			$GLOBALS['app']->Translate->LoadTranslation($translate_gadget, JAWS_GADGET);
			$output = substr_replace($output, _t($translate), $startLeft, ($endRight-$startLeft)+1);
		}
		$session_id = $GLOBALS['app']->Session->GetAttribute('session_id');
		$params          = array();
		$params['session_id'] = $session_id;

		$sql = '
		   SELECT [language]
		   FROM [[session]]
		   WHERE [session_id] = {session_id}';

		$user_data = $GLOBALS['db']->queryRow($sql, $params);
		if (!Jaws_Error::isError($user_data) && isset($user_data['language'])) {
			if ($user_data['language'] == 'en') {
				$translate = (strpos($output, '<!-- start_translation --><!-- end_translation -->') === false ? '<!-- start_translation --><!-- end_translation -->' : '');
			} else {
				$translate = "<!-- start_translation --><script type=\"text/javascript\" src=\"http://www.google.com/jsapi\"></script><script>function trim12(str) { var str = str.replace(/^\\s\\s*/, ''); var ws = /\\s/; var i = str.length; while (ws.test(str.charAt(--i))) {return str.slice(0, i + 1);}} function translateNode(node) {/* Note: by putting in an empty string for the source language ('en') then the translation will auto-detect the source language. *//*for (i=0;i<nodeCount;i++) {*/ google.language.translate(node.nodeValue, 'en', '".$user_data['language']."', function(result) { /* var translated = document.getElementById(\"translation\"); */ if (result.translation) {node.nodeValue = result.translation;}});/*}*/ return true;} /* Helper methods for setting/getting element text without mucking * around with multiple TextNodes. */ Element.addMethods({ getText: function(element, recursive) { \$A(element.childNodes).each(function(node) { if (node.nodeType == 3 && trim12(node.nodeValue) != '' && node.nodeName.toLowerCase().indexOf('script') == -1 && node.nodeName.toLowerCase().indexOf('object') == -1 && node.nodeName.toLowerCase().indexOf('style') == -1 && node.nodeName.toLowerCase().indexOf('link') == -1 && node.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node).nodeValue; /* text[nodeCount] = node.nodeValue; nodes[nodeCount] = node; nodeCount += 1; */ return translateNode(node); } else if (recursive && node.hasChildNodes()) { /* if (node.nodeType == 1 && node.nodeName.toLowerCase().indexOf('script') == -1 && node.nodeName.toLowerCase().indexOf('object') == -1 && node.nodeName.toLowerCase().indexOf('style') == -1 && node.nodeName.toLowerCase().indexOf('link') == -1 && node.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node).getText(true); \$(node).getText(true); */ \$A(node.childNodes).each(function(node1) { if (node1.nodeType == 3 && trim12(node1.nodeValue) != '' && node1.nodeName.toLowerCase().indexOf('script') == -1 && node1.nodeName.toLowerCase().indexOf('object') == -1 && node1.nodeName.toLowerCase().indexOf('style') == -1 && node1.nodeName.toLowerCase().indexOf('link') == -1 && node1.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node1).nodeValue; return translateNode(node1); } else if (recursive && node1.hasChildNodes()) { \$A(node1.childNodes).each(function(node2) { if (node2.nodeType == 3 && trim12(node2.nodeValue) != '' && node2.nodeName.toLowerCase().indexOf('script') == -1 && node2.nodeName.toLowerCase().indexOf('object') == -1 && node2.nodeName.toLowerCase().indexOf('style') == -1 && node2.nodeName.toLowerCase().indexOf('link') == -1 && node2.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node2).nodeValue; return translateNode(node2); } else if (recursive && node2.hasChildNodes()) { \$A(node2.childNodes).each(function(node3) { if (node3.nodeType == 3 && trim12(node3.nodeValue) != '' && node3.nodeName.toLowerCase().indexOf('script') == -1 && node3.nodeName.toLowerCase().indexOf('object') == -1 && node3.nodeName.toLowerCase().indexOf('style') == -1 && node3.nodeName.toLowerCase().indexOf('link') == -1 && node3.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node3).nodeValue; return translateNode(node3); } else if (recursive && node3.hasChildNodes()) { \$A(node3.childNodes).each(function(node4) { if (node4.nodeType == 3 && trim12(node4.nodeValue) != '' && node4.nodeName.toLowerCase().indexOf('script') == -1 && node4.nodeName.toLowerCase().indexOf('object') == -1 && node4.nodeName.toLowerCase().indexOf('style') == -1 && node4.nodeName.toLowerCase().indexOf('link') == -1 && node4.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node4).nodeValue; return translateNode(node4); } else if (recursive && node4.hasChildNodes()) { \$A(node4.childNodes).each(function(node5) { if (node5.nodeType == 3 && trim12(node5.nodeValue) != '' && node5.nodeName.toLowerCase().indexOf('script') == -1 && node5.nodeName.toLowerCase().indexOf('object') == -1 && node5.nodeName.toLowerCase().indexOf('style') == -1 && node5.nodeName.toLowerCase().indexOf('link') == -1 && node5.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node5).nodeValue; return translateNode(node5); } else if (recursive && node5.hasChildNodes()) { \$A(node5.childNodes).each(function(node6) { if (node6.nodeType == 3 && trim12(node6.nodeValue) != '' && node6.nodeName.toLowerCase().indexOf('script') == -1 && node6.nodeName.toLowerCase().indexOf('object') == -1 && node6.nodeName.toLowerCase().indexOf('style') == -1 && node6.nodeName.toLowerCase().indexOf('link') == -1 && node6.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node6).nodeValue; return translateNode(node6); } else if (recursive && node6.hasChildNodes()) { \$A(node6.childNodes).each(function(node7) { if (node7.nodeType == 3 && trim12(node7.nodeValue) != '' && node7.nodeName.toLowerCase().indexOf('script') == -1 && node7.nodeName.toLowerCase().indexOf('object') == -1 && node7.nodeName.toLowerCase().indexOf('style') == -1 && node7.nodeName.toLowerCase().indexOf('link') == -1 && node7.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node7).nodeValue; return translateNode(node7); } else if (recursive && node7.hasChildNodes()) { \$A(node7.childNodes).each(function(node8) { if (node8.nodeType == 3 && trim12(node8.nodeValue) != '' && node8.nodeName.toLowerCase().indexOf('script') == -1 && node8.nodeName.toLowerCase().indexOf('object') == -1 && node8.nodeName.toLowerCase().indexOf('style') == -1 && node8.nodeName.toLowerCase().indexOf('link') == -1 && node8.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node8).nodeValue; return translateNode(node8); } else if (recursive && node8.hasChildNodes()) { \$A(node8.childNodes).each(function(node9) { if (node9.nodeType == 3 && trim12(node9.nodeValue) != '' && node9.nodeName.toLowerCase().indexOf('script') == -1 && node9.nodeName.toLowerCase().indexOf('object') == -1 && node9.nodeName.toLowerCase().indexOf('style') == -1 && node9.nodeName.toLowerCase().indexOf('link') == -1 && node9.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node9).nodeValue; return translateNode(node9); } else if (recursive && node9.hasChildNodes()) { \$A(node9.childNodes).each(function(node10) { if (node10.nodeType == 3 && trim12(node10.nodeValue) != '' && node10.nodeName.toLowerCase().indexOf('script') == -1 && node10.nodeName.toLowerCase().indexOf('object') == -1 && node10.nodeName.toLowerCase().indexOf('style') == -1 && node10.nodeName.toLowerCase().indexOf('link') == -1 && node10.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node10).nodeValue; return translateNode(node10); } else if (recursive && node10.hasChildNodes()) { \$A(node10.childNodes).each(function(node11) { if (node11.nodeType == 3 && trim12(node11.nodeValue) != '' && node11.nodeName.toLowerCase().indexOf('script') == -1 && node11.nodeName.toLowerCase().indexOf('object') == -1 && node11.nodeName.toLowerCase().indexOf('style') == -1 && node11.nodeName.toLowerCase().indexOf('link') == -1 && node11.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node11).nodeValue; return translateNode(node11); } else if (recursive && node11.hasChildNodes()) { \$A(node11.childNodes).each(function(node12) { if (node12.nodeType == 3 && trim12(node12.nodeValue) != '' && node12.nodeName.toLowerCase().indexOf('script') == -1 && node12.nodeName.toLowerCase().indexOf('object') == -1 && node12.nodeName.toLowerCase().indexOf('style') == -1 && node12.nodeName.toLowerCase().indexOf('link') == -1 && node12.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node12).nodeValue; return translateNode(node12); } else if (recursive && node12.hasChildNodes()) { \$A(node12.childNodes).each(function(node13) { if (node13.nodeType == 3 && trim12(node13.nodeValue) != '' && node13.nodeName.toLowerCase().indexOf('script') == -1 && node13.nodeName.toLowerCase().indexOf('object') == -1 && node13.nodeName.toLowerCase().indexOf('style') == -1 && node13.nodeName.toLowerCase().indexOf('link') == -1 && node13.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node13).nodeValue; return translateNode(node13); } else if (recursive && node13.hasChildNodes()) { \$A(node13.childNodes).each(function(node14) { if (node14.nodeType == 3 && trim12(node14.nodeValue) != '' && node14.nodeName.toLowerCase().indexOf('script') == -1 && node14.nodeName.toLowerCase().indexOf('object') == -1 && node14.nodeName.toLowerCase().indexOf('style') == -1 && node14.nodeName.toLowerCase().indexOf('link') == -1 && node14.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node14).nodeValue; return translateNode(node14); } else if (recursive && node14.hasChildNodes()) { \$A(node14.childNodes).each(function(node15) { if (node15.nodeType == 3 && trim12(node15.nodeValue) != '' && node15.nodeName.toLowerCase().indexOf('script') == -1 && node15.nodeName.toLowerCase().indexOf('object') == -1 && node15.nodeName.toLowerCase().indexOf('style') == -1 && node15.nodeName.toLowerCase().indexOf('link') == -1 && node15.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node15).nodeValue; return translateNode(node15); } else if (recursive && node15.hasChildNodes()) { \$A(node15.childNodes).each(function(node16) { if (node16.nodeType == 3 && trim12(node16.nodeValue) != '' && node16.nodeName.toLowerCase().indexOf('script') == -1 && node16.nodeName.toLowerCase().indexOf('object') == -1 && node16.nodeName.toLowerCase().indexOf('style') == -1 && node16.nodeName.toLowerCase().indexOf('link') == -1 && node16.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node16).nodeValue; return translateNode(node16); } else if (recursive && node16.hasChildNodes()) { \$A(node16.childNodes).each(function(node17) { if (node17.nodeType == 3 && trim12(node17.nodeValue) != '' && node17.nodeName.toLowerCase().indexOf('script') == -1 && node17.nodeName.toLowerCase().indexOf('object') == -1 && node17.nodeName.toLowerCase().indexOf('style') == -1 && node17.nodeName.toLowerCase().indexOf('link') == -1 && node17.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node17).nodeValue; return translateNode(node17); } else if (recursive && node17.hasChildNodes()) { \$A(node17.childNodes).each(function(node18) { if (node18.nodeType == 3 && trim12(node18.nodeValue) != '' && node18.nodeName.toLowerCase().indexOf('script') == -1 && node18.nodeName.toLowerCase().indexOf('object') == -1 && node18.nodeName.toLowerCase().indexOf('style') == -1 && node18.nodeName.toLowerCase().indexOf('link') == -1 && node18.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node18).nodeValue; return translateNode(node18); } else if (recursive && node18.hasChildNodes()) { \$A(node18.childNodes).each(function(node19) { if (node19.nodeType == 3 && trim12(node19.nodeValue) != '' && node19.nodeName.toLowerCase().indexOf('script') == -1 && node19.nodeName.toLowerCase().indexOf('object') == -1 && node19.nodeName.toLowerCase().indexOf('style') == -1 && node19.nodeName.toLowerCase().indexOf('link') == -1 && node19.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node19).nodeValue; return translateNode(node19); } else if (recursive && node19.hasChildNodes()) { \$A(node19.childNodes).each(function(node20) { if (node20.nodeType == 3 && trim12(node20.nodeValue) != '' && node20.nodeName.toLowerCase().indexOf('script') == -1 && node20.nodeName.toLowerCase().indexOf('object') == -1 && node20.nodeName.toLowerCase().indexOf('style') == -1 && node20.nodeName.toLowerCase().indexOf('link') == -1 && node20.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node20).nodeValue; return translateNode(node20); } else if (recursive && node20.hasChildNodes()) { \$A(node20.childNodes).each(function(node21) { if (node21.nodeType == 3 && trim12(node21.nodeValue) != '' && node21.nodeName.toLowerCase().indexOf('script') == -1 && node21.nodeName.toLowerCase().indexOf('object') == -1 && node21.nodeName.toLowerCase().indexOf('style') == -1 && node21.nodeName.toLowerCase().indexOf('link') == -1 && node21.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node21).nodeValue; return translateNode(node21); } else if (recursive && node21.hasChildNodes()) { \$A(node21.childNodes).each(function(node22) { if (node22.nodeType == 3 && trim12(node22.nodeValue) != '' && node22.nodeName.toLowerCase().indexOf('script') == -1 && node22.nodeName.toLowerCase().indexOf('object') == -1 && node22.nodeName.toLowerCase().indexOf('style') == -1 && node22.nodeName.toLowerCase().indexOf('link') == -1 && node22.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node22).nodeValue; return translateNode(node22); } else if (recursive && node22.hasChildNodes()) { \$A(node22.childNodes).each(function(node23) { if (node23.nodeType == 3 && trim12(node23.nodeValue) != '' && node23.nodeName.toLowerCase().indexOf('script') == -1 && node23.nodeName.toLowerCase().indexOf('object') == -1 && node23.nodeName.toLowerCase().indexOf('style') == -1 && node23.nodeName.toLowerCase().indexOf('link') == -1 && node23.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node23).nodeValue; return translateNode(node23); } else if (recursive && node23.hasChildNodes()) { \$A(node23.childNodes).each(function(node24) { if (node24.nodeType == 3 && trim12(node24.nodeValue) != '' && node24.nodeName.toLowerCase().indexOf('script') == -1 && node24.nodeName.toLowerCase().indexOf('object') == -1 && node24.nodeName.toLowerCase().indexOf('style') == -1 && node24.nodeName.toLowerCase().indexOf('link') == -1 && node24.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node24).nodeValue; return translateNode(node24); } else if (recursive && node24.hasChildNodes()) { \$A(node23.childNodes).each(function(node24) { if (node24.nodeType == 3 && trim12(node24.nodeValue) != '' && node24.nodeName.toLowerCase().indexOf('script') == -1 && node24.nodeName.toLowerCase().indexOf('object') == -1 && node24.nodeName.toLowerCase().indexOf('style') == -1 && node24.nodeName.toLowerCase().indexOf('link') == -1 && node24.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node24).nodeValue; return translateNode(node24); } else if (recursive && node24.hasChildNodes()) { \$A(node24.childNodes).each(function(node25) { if (node25.nodeType == 3 && trim12(node25.nodeValue) != '' && node25.nodeName.toLowerCase().indexOf('script') == -1 && node25.nodeName.toLowerCase().indexOf('object') == -1 && node25.nodeName.toLowerCase().indexOf('style') == -1 && node25.nodeName.toLowerCase().indexOf('link') == -1 && node25.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node25).nodeValue; return translateNode(node25); } else if (recursive && node25.hasChildNodes()) { \$A(node25.childNodes).each(function(node26) { if (node26.nodeType == 3 && trim12(node26.nodeValue) != '' && node26.nodeName.toLowerCase().indexOf('script') == -1 && node26.nodeName.toLowerCase().indexOf('object') == -1 && node26.nodeName.toLowerCase().indexOf('style') == -1 && node26.nodeName.toLowerCase().indexOf('link') == -1 && node26.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node26).nodeValue; return translateNode(node26); } else if (recursive && node26.hasChildNodes()) { \$A(node26.childNodes).each(function(node27) { if (node27.nodeType == 3 && trim12(node27.nodeValue) != '' && node27.nodeName.toLowerCase().indexOf('script') == -1 && node27.nodeName.toLowerCase().indexOf('object') == -1 && node27.nodeName.toLowerCase().indexOf('style') == -1 && node27.nodeName.toLowerCase().indexOf('link') == -1 && node27.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node27).nodeValue; return translateNode(node27); } else if (recursive && node27.hasChildNodes()) { \$A(node27.childNodes).each(function(node28) { if (node28.nodeType == 3 && trim12(node28.nodeValue) != '' && node28.nodeName.toLowerCase().indexOf('script') == -1 && node28.nodeName.toLowerCase().indexOf('object') == -1 && node28.nodeName.toLowerCase().indexOf('style') == -1 && node28.nodeName.toLowerCase().indexOf('link') == -1 && node28.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node28).nodeValue; return translateNode(node28); } else if (recursive && node28.hasChildNodes()) { \$A(node28.childNodes).each(function(node29) { if (node29.nodeType == 3 && trim12(node29.nodeValue) != '' && node29.nodeName.toLowerCase().indexOf('script') == -1 && node29.nodeName.toLowerCase().indexOf('object') == -1 && node29.nodeName.toLowerCase().indexOf('style') == -1 && node29.nodeName.toLowerCase().indexOf('link') == -1 && node29.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node29).nodeValue; return translateNode(node29); } else if (recursive && node29.hasChildNodes()) { \$A(node29.childNodes).each(function(node30) { if (node30.nodeType == 3 && trim12(node30.nodeValue) != '' && node30.nodeName.toLowerCase().indexOf('script') == -1 && node30.nodeName.toLowerCase().indexOf('object') == -1 && node30.nodeName.toLowerCase().indexOf('style') == -1 && node30.nodeName.toLowerCase().indexOf('link') == -1 && node30.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node30).nodeValue; return translateNode(node30); } else if (recursive && node30.hasChildNodes()) { \$A(node30.childNodes).each(function(node31) { if (node31.nodeType == 3 && trim12(node31.nodeValue) != '' && node31.nodeName.toLowerCase().indexOf('script') == -1 && node31.nodeName.toLowerCase().indexOf('object') == -1 && node31.nodeName.toLowerCase().indexOf('style') == -1 && node31.nodeName.toLowerCase().indexOf('link') == -1 && node31.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node31).nodeValue; return translateNode(node31); } else if (recursive && node31.hasChildNodes()) { \$A(node31.childNodes).each(function(node32) { if (node32.nodeType == 3 && trim12(node32.nodeValue) != '' && node32.nodeName.toLowerCase().indexOf('script') == -1 && node32.nodeName.toLowerCase().indexOf('object') == -1 && node32.nodeName.toLowerCase().indexOf('style') == -1 && node32.nodeName.toLowerCase().indexOf('link') == -1 && node32.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node32).nodeValue; return translateNode(node32); } else if (recursive && node32.hasChildNodes()) { \$A(node32.childNodes).each(function(node33) { if (node33.nodeType == 3 && trim12(node33.nodeValue) != '' && node33.nodeName.toLowerCase().indexOf('script') == -1 && node33.nodeName.toLowerCase().indexOf('object') == -1 && node33.nodeName.toLowerCase().indexOf('style') == -1 && node33.nodeName.toLowerCase().indexOf('link') == -1 && node33.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node33).nodeValue; return translateNode(node33); } else if (recursive && node33.hasChildNodes()) { \$A(node33.childNodes).each(function(node34) { if (node34.nodeType == 3 && trim12(node34.nodeValue) != '' && node34.nodeName.toLowerCase().indexOf('script') == -1 && node34.nodeName.toLowerCase().indexOf('object') == -1 && node34.nodeName.toLowerCase().indexOf('style') == -1 && node34.nodeName.toLowerCase().indexOf('link') == -1 && node34.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node34).nodeValue; return translateNode(node34); } else if (recursive && node34.hasChildNodes()) { \$A(node34.childNodes).each(function(node35) { if (node35.nodeType == 3 && trim12(node35.nodeValue) != '' && node35.nodeName.toLowerCase().indexOf('script') == -1 && node35.nodeName.toLowerCase().indexOf('object') == -1 && node35.nodeName.toLowerCase().indexOf('style') == -1 && node35.nodeName.toLowerCase().indexOf('link') == -1 && node35.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node35).nodeValue; return translateNode(node35); } else if (recursive && node35.hasChildNodes()) { \$A(node35.childNodes).each(function(node36) { if (node36.nodeType == 3 && trim12(node36.nodeValue) != '' && node36.nodeName.toLowerCase().indexOf('script') == -1 && node36.nodeName.toLowerCase().indexOf('object') == -1 && node36.nodeName.toLowerCase().indexOf('style') == -1 && node36.nodeName.toLowerCase().indexOf('link') == -1 && node36.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node36).nodeValue; return translateNode(node36); } else if (recursive && node36.hasChildNodes()) { \$A(node36.childNodes).each(function(node37) { if (node37.nodeType == 3 && trim12(node37.nodeValue) != '' && node37.nodeName.toLowerCase().indexOf('script') == -1 && node37.nodeName.toLowerCase().indexOf('object') == -1 && node37.nodeName.toLowerCase().indexOf('style') == -1 && node37.nodeName.toLowerCase().indexOf('link') == -1 && node37.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node37).nodeValue; return translateNode(node37); } else if (recursive && node37.hasChildNodes()) { \$A(node37.childNodes).each(function(node38) { if (node38.nodeType == 3 && trim12(node38.nodeValue) != '' && node38.nodeName.toLowerCase().indexOf('script') == -1 && node38.nodeName.toLowerCase().indexOf('object') == -1 && node38.nodeName.toLowerCase().indexOf('style') == -1 && node38.nodeName.toLowerCase().indexOf('link') == -1 && node38.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node38).nodeValue; return translateNode(node38); } else if (recursive && node38.hasChildNodes()) { \$A(node38.childNodes).each(function(node39) { if (node39.nodeType == 3 && trim12(node39.nodeValue) != '' && node39.nodeName.toLowerCase().indexOf('script') == -1 && node39.nodeName.toLowerCase().indexOf('object') == -1 && node39.nodeName.toLowerCase().indexOf('style') == -1 && node39.nodeName.toLowerCase().indexOf('link') == -1 && node39.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node39).nodeValue; return translateNode(node39); } else if (recursive && node39.hasChildNodes()) { \$A(node39.childNodes).each(function(node40) { if (node40.nodeType == 3 && trim12(node40.nodeValue) != '' && node40.nodeName.toLowerCase().indexOf('script') == -1 && node40.nodeName.toLowerCase().indexOf('object') == -1 && node40.nodeName.toLowerCase().indexOf('style') == -1 && node40.nodeName.toLowerCase().indexOf('link') == -1 && node40.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node40).nodeValue; return translateNode(node40); } else if (recursive && node40.hasChildNodes()) { \$A(node40.childNodes).each(function(node41) { if (node41.nodeType == 3 && trim12(node41.nodeValue) != '' && node41.nodeName.toLowerCase().indexOf('script') == -1 && node41.nodeName.toLowerCase().indexOf('object') == -1 && node41.nodeName.toLowerCase().indexOf('style') == -1 && node41.nodeName.toLowerCase().indexOf('link') == -1 && node41.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node41).nodeValue; return translateNode(node41); } else if (recursive && node41.hasChildNodes()) { \$A(node41.childNodes).each(function(node42) { if (node42.nodeType == 3 && trim12(node42.nodeValue) != '' && node42.nodeName.toLowerCase().indexOf('script') == -1 && node42.nodeName.toLowerCase().indexOf('object') == -1 && node42.nodeName.toLowerCase().indexOf('style') == -1 && node42.nodeName.toLowerCase().indexOf('link') == -1 && node42.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node42).nodeValue; return translateNode(node42); } else if (recursive && node42.hasChildNodes()) { \$A(node42.childNodes).each(function(node43) { if (node43.nodeType == 3 && trim12(node43.nodeValue) != '' && node43.nodeName.toLowerCase().indexOf('script') == -1 && node43.nodeName.toLowerCase().indexOf('object') == -1 && node43.nodeName.toLowerCase().indexOf('style') == -1 && node43.nodeName.toLowerCase().indexOf('link') == -1 && node43.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node43).nodeValue; return translateNode(node43); } else if (recursive && node43.hasChildNodes()) { \$A(node43.childNodes).each(function(node44) { if (node44.nodeType == 3 && trim12(node44.nodeValue) != '' && node44.nodeName.toLowerCase().indexOf('script') == -1 && node44.nodeName.toLowerCase().indexOf('object') == -1 && node44.nodeName.toLowerCase().indexOf('style') == -1 && node44.nodeName.toLowerCase().indexOf('link') == -1 && node44.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node44).nodeValue; return translateNode(node44); } else if (recursive && node44.hasChildNodes()) { \$A(node44.childNodes).each(function(node45) { if (node45.nodeType == 3 && trim12(node45.nodeValue) != '' && node45.nodeName.toLowerCase().indexOf('script') == -1 && node45.nodeName.toLowerCase().indexOf('object') == -1 && node45.nodeName.toLowerCase().indexOf('style') == -1 && node45.nodeName.toLowerCase().indexOf('link') == -1 && node45.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node45).nodeValue; return translateNode(node45); } else if (recursive && node45.hasChildNodes()) { \$A(node45.childNodes).each(function(node46) { if (node46.nodeType == 3 && trim12(node46.nodeValue) != '' && node46.nodeName.toLowerCase().indexOf('script') == -1 && node46.nodeName.toLowerCase().indexOf('object') == -1 && node46.nodeName.toLowerCase().indexOf('style') == -1 && node46.nodeName.toLowerCase().indexOf('link') == -1 && node46.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node46).nodeValue; return translateNode(node46); } else if (recursive && node46.hasChildNodes()) { \$A(node46.childNodes).each(function(node47) { if (node47.nodeType == 3 && trim12(node47.nodeValue) != '' && node47.nodeName.toLowerCase().indexOf('script') == -1 && node47.nodeName.toLowerCase().indexOf('object') == -1 && node47.nodeName.toLowerCase().indexOf('style') == -1 && node47.nodeName.toLowerCase().indexOf('link') == -1 && node47.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node47).nodeValue; return translateNode(node47); } else if (recursive && node47.hasChildNodes()) { \$A(node47.childNodes).each(function(node48) { if (node48.nodeType == 3 && trim12(node48.nodeValue) != '' && node48.nodeName.toLowerCase().indexOf('script') == -1 && node48.nodeName.toLowerCase().indexOf('object') == -1 && node48.nodeName.toLowerCase().indexOf('style') == -1 && node48.nodeName.toLowerCase().indexOf('link') == -1 && node48.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node48).nodeValue; return translateNode(node48); } else if (recursive && node48.hasChildNodes()) { \$A(node48.childNodes).each(function(node49) { if (node49.nodeType == 3 && trim12(node49.nodeValue) != '' && node49.nodeName.toLowerCase().indexOf('script') == -1 && node49.nodeName.toLowerCase().indexOf('object') == -1 && node49.nodeName.toLowerCase().indexOf('style') == -1 && node49.nodeName.toLowerCase().indexOf('link') == -1 && node49.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node49).nodeValue; return translateNode(node49); } else if (recursive && node49.hasChildNodes()) { \$A(node49.childNodes).each(function(node50) { if (node50.nodeType == 3 && trim12(node50.nodeValue) != '' && node50.nodeName.toLowerCase().indexOf('script') == -1 && node50.nodeName.toLowerCase().indexOf('object') == -1 && node50.nodeName.toLowerCase().indexOf('style') == -1 && node50.nodeName.toLowerCase().indexOf('link') == -1 && node50.nodeName.toLowerCase().indexOf('embed') == -1) { text += \$(node50).nodeValue; return translateNode(node50); } else if (recursive && node50.hasChildNodes()) { } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } }); } /* if (node2.nodeType == 1 && node2.nodeName.toLowerCase().indexOf('script') == -1 && node2.nodeName.toLowerCase().indexOf('object') == -1 && node2.nodeName.toLowerCase().indexOf('style') == -1 && node2.nodeName.toLowerCase().indexOf('link') == -1 && node2.nodeName.toLowerCase().indexOf('embed') == -1) { \$(node2).getText(true); } */ }); } }); return true; } });  google.load(\"language\", \"1\"); var text = ''; var nodes = []; var nodeCount = 0; Event.observe(window, 'load', function(){ \$('container').getText(true); });</script><!-- end_translation -->";
			}
			$output = str_replace('</body>', $translate.'</body>', $output);
		}
	
		// HTML inline mode
		if ($inline === true || $this->_standAloneMode === true) {
			// remove HEAD and add theme stuff
			$headStr = $output;
			if (strpos($headStr, '<body') !== false) {
				$headStr = substr($headStr, 0, strpos($headStr, '<body'));
				$output = str_replace($headStr, '', $output);
				if (!empty($headContent)) {
					$output = eregi_replace('<body([^>]*)>', '<body\1>' . $headContent, $output);
				}
				$theme = $GLOBALS['app']->GetTheme();
				$direction = _t('GLOBAL_LANG_DIRECTION');
				$dir  = $direction == 'rtl' ? '.' . $direction : '';
				$themeLinks = '
				<link rel="stylesheet" type="text/css" href="'.$theme['url'].'style'.$dir.'.css" media="screen" />
				<link rel="stylesheet" type="text/css" href="'.$theme['url'].'blog'.$dir.'.css" media="screen" />
				';
				if (file_exists(JAWS_DATA . 'files/css/custom.css')) {
					$themeLinks .= '<link rel="stylesheet" type="text/css" href="'.$GLOBALS['app']->GetDataURL() .'files/css/custom.css" media="screen" />';
				}
				$output = eregi_replace('<body([^>]*)>', '<body\1>' . $themeLinks .'<style type="text/css">#working_notification {display: none;}</style>', $output);
				$output = eregi_replace('<body([^>]*)>', '<div>', $output);
			}
			$footStr = $output;
			if (strpos($footStr, '</body>') !== false) {
				$footStr = substr($footStr, strpos($footStr, '</body>'), strlen($footStr));
				$output = str_replace($footStr, '</div>', $output);
			}
			
			// remove login menubar 
			if (strpos($output, '<ul id="jaws-menubar-login"') !== false) {
				$inputStr = $output;
				$delimeterLeft = "<ul id=\"jaws-menubar-login\"";
				$delimeterRight = "</ul>";
				$startLeft = strpos($inputStr, $delimeterLeft);
				$posLeft = ($startLeft+strlen($delimeterLeft));
				$posRight = strpos($inputStr, $delimeterRight, $posLeft);
				$inputStr = substr($inputStr, $posLeft, $posRight-$posLeft);
				$output = str_replace($inputStr, '', $output);
			}
			
			$match = array();
			// absolute paths for all links
			preg_match_all("'<\s*a\s.*?href\s*=\s*			# find <a href=
							([\"\'])?					# find single or double quote
							(?(1) (.*?)\\1 | ([^\s\>]+))		# if quote found, match up to next matching
														# quote, otherwise match up to next space
							'isx",$output,$links);
							
			// catenate the non-empty matches from the conditional subpattern
			while(list($key,$val) = each($links[2])) {if(!empty($val)) $match[] = $val;}				
			while(list($key,$val) = each($links[3])) {if(!empty($val)) $match[] = $val;}		
			
			// absolute paths for all images
			// TODO: inline CSS, and make CSS URLS absolute paths...
			preg_match_all("'<\s*img\s.*?src\s*=\s*			# find <a href=
							([\"\'])?					# find single or double quote
							(?(1) (.*?)\\1 | ([^\s\>]+))		# if quote found, match up to next matching
														# quote, otherwise match up to next space
							'isx",$output,$links);
							
			// catenate the non-empty matches from the conditional subpattern
			while(list($key,$val) = each($links[2])) {if(!empty($val)) $match[] = $val;}				
			while(list($key,$val) = each($links[3])) {if(!empty($val)) $match[] = $val;}		
			
			// add only unique URLs
			$res = array();
			$stripurls = array();
			foreach ($match as $m) {
				if (!isset($res[$m])) {
					$res[$m] = $m;
				} else {
					unset($res[$m]);
				}
			}
			foreach ($res as $r => $v) {
				$stripurls[] = html_entity_decode($v);
			}
			foreach ($stripurls as $href) {
				if (substr(strtolower($href), 0, 4) != 'http') {
					$newhref = $href;
					if (substr(strtolower($newhref), 0, 1) == '/') {
						$newhref = substr($newhref, 1, strlen($newhref));
					}
					if (substr(strtolower($newhref), 0, 4) == 'data') {
						$newhref = $GLOBALS['app']->GetDataURL('', true) . substr($newhref, 5, strlen($newhref));
					} else if (substr(strtolower($newhref), 0, 9) == 'index.php' || substr(strtolower($newhref), 0, 9) == 'admin.php') {
						$newhref = $GLOBALS['app']->GetSiteURL() . substr($newhref, 9, strlen($newhref));
					} else {
						$newhref = $GLOBALS['app']->GetJawsURL() . '/' . $newhref;
					}
					$output = str_replace($href, $newhref, $output);
				}
			}
			$output = str_replace($GLOBALS['app']->GetSiteURL().'/'.$GLOBALS['app']->GetSiteURL(), $GLOBALS['app']->GetSiteURL().'/', $output);
		}
		
		// Make sure only one Google Analytics script is added
		if (strpos($output, '(function() {') !== false && strpos(strtolower($output), "'http://www') + '.google-analytics.com/ga.js';") !== false) {
			while (strpos($output, '(function() {') !== false && strpos(strtolower($output), "'http://www') + '.google-analytics.com/ga.js';") !== false) {
				$inputStr = $output;
				$delimeterLeft = "(function() {";
				$delimeterRight = "})();";
				$posLeft = (strpos($inputStr, $delimeterLeft)+strlen($delimeterLeft));
				$posRight = strpos($inputStr, $delimeterRight, $posLeft);
				$analytics = substr($inputStr, $posLeft, $posRight-$posLeft);
				if (strpos(strtolower($analytics), 'google-analytics.com/ga.js') !== false) {
					$output = str_replace($delimeterLeft.$analytics.$delimeterRight, '', $output);
				}
			}
			$analytics_body = "<script type=\"text/javascript\">(function() {
				var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
				ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
				var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
			})();</script>";
			$output = str_replace('</body>', $analytics_body.'</body>', $output);
		}
		
		if ($res_echo) {
            if ($GLOBALS['app']->GZipEnabled()) {
                $output = gzencode($output, COMPRESS_LEVEL, FORCE_GZIP);
                header('Content-Length: '.strlen($output));
                header('Content-Encoding: '.(strpos($GLOBALS['app']->GetBrowserEncoding(), 'x-gzip')!== false? 'x-gzip' : 'gzip'));
            }
            echo $output;
        } else {
            return $output;
        }
    }

    /**
     * Add a meta tag
     *
     * @access  public
     * @param   string  $name           Key of the meta tag
     * @param   string  $content        Value of the key
     * @param   boolean $use_http_equiv Use the equiv of HTTP
     */
    function AddHeadMeta($name, $content, $use_http_equiv = false)
    {
        $this->_HeadMeta[$name]['name']    = $name;
        $this->_HeadMeta[$name]['content'] = $content;
        $this->_HeadMeta[$name]['use_http_equiv'] = $use_http_equiv;
    }

    /**
     * Add a HeadLink
     *
     * @access  public
     * @param   string  $href  The HREF
     * @param   string  $rel   The REL that will be associated
     * @param   string  $type  Type of HeadLink
     * @param   boolean $checkInTheme Check if resource exists in the current theme directory
     * @param   string  $title Title of the HeadLink
     * @param   string  $media Media type, screen, print or such
     * @param   boolean $standanlone for use in static load
     */
    function AddHeadLink($href, $rel, $type, $title = '', $direction = null, $checkInTheme = false,
                         $media = 'screen', $standanlone = false)
    {
        $fileName = basename($href);
        $fileExt  = strrchr($fileName, '.');
        $fileName = substr($fileName, 0, -strlen($fileExt));
        if (substr($href, 0, 1) == '/') {
            $path = substr($href , 1, - strlen($fileName.$fileExt));
        } else {
            $path = substr($href , 0, - strlen($fileName.$fileExt));
        }

        $brow = $GLOBALS['app']->GetBrowserFlag();
        $brow = empty($brow)? '' : '.'.$brow;

        $prefix = '.' . strtolower(empty($direction) ? _t('GLOBAL_LANG_DIRECTION') : $direction);
        if ($prefix !== '.rtl') {
            $prefix = '';
        }

        // First we try to load the css files from the theme dir.
        if ($checkInTheme) {
            $theme = $GLOBALS['app']->GetTheme();
			$gadget = str_replace(array('gadgets/', 'resources/'), '', $path);
			$href = $theme['path'] . $gadget . $fileName . $prefix . $fileExt;
            $content = '';
			if (substr(strtolower($href), 0, 4) == 'http') {
				// snoopy
				include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
				$snoopy = new Snoopy;
				$snoopy->fetch($href);
				if($snoopy->status == "200") {
					$content = $snoopy->results;
				}
			}
			if (empty($content) && !empty($prefix) && !file_exists($href)) {
				$href = JAWS_PATH . 'gadgets/' . $gadget . 'resources/' . $fileName . $prefix . $fileExt;
				if (!file_exists($href)) {
					$href = $theme['path'] . $gadget . $fileName . $fileExt;
				}
			}
			if (empty($content) && !file_exists($href)) {
				$href = JAWS_PATH . 'gadgets/' . $gadget . 'resources/' . $fileName . $fileExt;
				if (!file_exists($href)) {
					return false;
				}
			}

            $href = str_replace(JAWS_PATH, '', $href);
        } else {
            $href = $path . $fileName . $prefix . $fileExt;
            if (!empty($prefix) && !file_exists($href)) {
                $href = $path . $fileName . $fileExt;
            }
        }

		if (
			substr(strtolower($href), 0, 7) == 'include' || 
			substr(strtolower($href), 0, 10) == '../include' ||
			substr(strtolower($href), 0, 7) == 'gadgets' || 
			substr(strtolower($href), 0, 10) == '../gadgets' ||
			substr(strtolower($href), 0, 9) == 'libraries' || 
			substr(strtolower($href), 0, 12) == '../libraries'
		) {
			$href = $GLOBALS['app']->GetJawsURL() . "/" . (substr(strtolower($href), 0, 3) == '../' ? substr($href, 3, strlen($href)) : $href);
		}
		
        $hLinks[] = array(
            'href'  => $href,
            'rel'   => $rel,
            'type'  => $type,
            'title' => $title,
            'media' => $media,
        );
        
		// Don't add it if it's already been added
		foreach ($this->_HeadLink as $links) {
			if (
				str_replace(array('http://', 'https://'), 'http://', $href) == str_replace(array('http://', 'https://'), 'http://', $links['href'])
			) {
				return false;
			}
		}
		
		if (!$standanlone) $this->_HeadLink[] = $hLinks[0];

        $brow_href = substr_replace($href, $brow, strrpos($href, '.'), 0);
        if (!empty($brow) && file_exists($brow_href)) {
            $hLinks[] = array(
                'href'  => $brow_href,
                'rel'   => $rel,
                'type'  => $type,
                'title' => $title,
                'media' => $media,
            );
			// Don't add it if it's already been added
			foreach ($this->_HeadLink as $links) {
				if (
					str_replace(array('http://', 'https://'), 'http://', $brow_href) == str_replace(array('http://', 'https://'), 'http://', $links['href'])
				) {
					return false;
				}
			}
            if (!$standanlone) $this->_HeadLink[] = $hLinks[1];
        }

        return $standanlone? $hLinks : true;
    }

    /**
     * Add a Javascript source
     *
     * @access  public
     * @param   string  $href   The path for the source.
     * @param   string  $type   The mime type.
     * @param   boolean $standanlone for use in static load
     * @return  null
     * @since   0.6
     */
    function AddScriptLink($href, $type = 'text/javascript', $standanlone = false)
    {
		$href = str_replace('&amp;', '&', $href);
		if (
			substr(strtolower($href), 0, 7) == 'include' || 
			substr(strtolower($href), 0, 10) == '../include' ||
			substr(strtolower($href), 0, 7) == 'gadgets' || 
			substr(strtolower($href), 0, 10) == '../gadgets' ||
			substr(strtolower($href), 0, 9) == 'libraries' || 
			substr(strtolower($href), 0, 12) == '../libraries'
		) {
			$href = $GLOBALS['app']->GetJawsURL() . "/" . (substr(strtolower($href), 0, 3) == '../' ? substr($href, 3, strlen($href)) : $href);
		}
		if (substr(strtolower($href), 0, 7) == 'http://' && isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on') {
			$href = $GLOBALS['app']->GetSiteURL("/gz.php?type=javascript&uri=".urlencode($href));
		}
		$sLink = array(
                'href' => $href,
                'type' => $type
        );
		// Don't add it if it's already been added
		foreach ($this->_ScriptLink as $scripts) {
			if (
				(strpos($href, 'AjaxCommonFiles') !== false && strpos($scripts['href'], 'AjaxCommonFiles') !== false) || 
				str_replace(array('http://', 'https://'), 'http://', $href) == str_replace(array('http://', 'https://'), 'http://', $scripts['href'])
			) {
				return false;
			}
		}
        if (!$standanlone) $this->_ScriptLink[] = $sLink;

        return $standanlone? $sLink : true;
    }

    /**
     * Add other info to the head tag
     *
     * @access  public
     * @param   string  $text Text to add.
     * @return  null
     * @since   0.6
     */
    function addHeadOther($text)
    {
		// Don't add it if it's already been added
		if (in_array($text, $this->_HeadOther)) {
			return false;
		}
        $this->_HeadOther[] = $text;
    }

    /**
     * Get Requested gadget
     */
    function GetRequestedGadget()
    {
        return $this->_RequestedGadget;
    }

}