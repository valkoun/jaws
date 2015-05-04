<?php
/**
 * Jaws Gadgets : HTML part
 *
 * @category   Gadget
 * @package    Core
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @copyright  2005-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class Jaws_GadgetHTML extends Jaws_Gadget
{
    /**
     * Are we running Ajax?
     *
     * @access  private
     * @var     boolean
     */
    var $_usingAjax = false;

    /**
     * Refactor Init, Jaws_GadgetHTML::Init() loads the Piwi stuff
     *
     * @access  protected
     * @param   string    $value Name of the gadget's model
     */
    function Init($model)
    {
        parent::Init($model);
        // Load Piwi if it's a web app
        if (APP_TYPE == 'web') {
            // Add ShowGadgetInfo action
            $this->StandaloneAction('ShowGadgetInfo','');

            // Add Ajax actions.
            $this->StandaloneAction('Ajax', '');
            $this->StandaloneAction('AjaxCommonFiles', '');
            $this->StandaloneAdminAction('Ajax', '');
            $this->StandaloneAdminAction('AjaxCommonFiles', '');

            // Add _404 as normal action
            $this->NormalAction('_404');
        }
    }

    /**
     * Adds a layout action
     *
     * @access  protected
     * @param   string  $action Action
     * @param   string  $name Action's name
     * @param   string  $description Action's description
     */
    function LayoutAction($action, $name, $description = null)
    {
        $this->AddAction($action, 'LayoutAction', $name, $description);
    }

    /**
     * Adds a standalone action
     *
     * @access  protected
     * @param   string  $action Action
     * @param   string  $name Action's name
     * @param   string  $description Action's description
     */
    function StandaloneAction($action, $name = null, $description = null)
    {
        $this->AddAction($action, 'StandaloneAction', $name, $description);
    }

    /**
     * Adds a standalone/admin action
     *
     * @access  protected
     * @param   string  $action Action
     * @param   string  $name Action's name
     * @param   string  $description Action's description
     */
    function StandaloneAdminAction($action, $name = null, $description = null)
    {
        $this->AddAction($action, 'StandaloneAdminAction', $name, $description);
    }

    /**
     * Verifies if action is a standalone
     *
     * @access  public
     * @param   string  $action to Verify
     * @return  boolean True if action is standalone, if not, returns false
     */
    function IsStandAlone($action)
    {
        if ($this->IsValidAction($action)) {
            return isset($this->_ValidAction['StandaloneAction'][$action]);
        }
        return false;
    }

    /**
     * Verifies if action is a standalone of controlpanel
     *
     * @access  public
     * @param   string  $action to Verify
     * @return  boolean True if action is standalone of the controlpanel if not, returns false
     */
    function IsStandAloneAdmin($action)
    {
        $actionmode = '';
        if ($this->IsValidAction($action)) {
            return isset($this->_ValidAction['StandaloneAdminAction'][$action]);
        }
        return false;
    }

    /**
     * Ajax Admin stuff
     * This method should be overridden by gadgets if a more complex operation
     * is required, and then called with an array of objects to be provided
     * to the client, like this:
     *
     * <code>
     * <?php
     * function Ajax()
     * {
     *     $objects = array();
     *     $objects[] = new GadgetAPI();
     *     $objects[] = new OtherAPI();
     *
     *     return parent::InitAjax($objects);
     * }
     * ?>
     * </code>
     *
     * @access public
     */
    function Ajax()
    {
        $name = $this->GetName();
        $objects = array();
        require_once JAWS_PATH . 'include/Jaws/Ajax.php';

        if (JAWS_SCRIPT == 'admin') {
            $model = $GLOBALS['app']->LoadGadget($name, 'AdminModel');
            require_once JAWS_PATH.'gadgets/' . $name . '/AdminAjax.php';
            $ajaxClass = $name . 'AdminAjax';
        } else {
            $model = $GLOBALS['app']->LoadGadget($name, 'Model');
            require_once JAWS_PATH.'gadgets/' . $name . '/Ajax.php';
            $ajaxClass = $name . 'Ajax';
        }
        $objects[] = new $ajaxClass($model);

        $this->InitAjax($objects);
    }

    /**
     * Provides the Javascript interface for a gadget.
     *
     *
     * @access  public
     * @param   array   $objects    An array of objects to provide to the client.
     * @return  string  The reply.
     * @since   0.6
     */
    function InitAjax($objects = array())
    {
		if (count($objects)) { 
			// Load the HTML_AJAX library 
			require_once 'HTML/AJAX/Server.php'; 
			
			// Create a server object, set the URL to submit to, and export some objects. 
			$server = new HTML_AJAX_Server(); 
			$server->setSerializer('JSON'); 
			$server->ajax->php4CompatCase = true; 
			//$server->ajax->serverUrl = $GLOBALS['app']->getSiteURL('', false, 'https') . '/'. BASE_SCRIPT.'?gadget='.$this->_Name.'&action=Ajax'; 
			$server->ajax->serverUrl = BASE_SCRIPT.'?gadget='.$this->_Name.'&action=Ajax'; 
			
			foreach ($objects as $object) { 
				$server->registerClass($object); 
			} 
			
			$server->handleRequest(); 
/*
            // Load the JPSpan library
            require_once JAWS_PATH . 'libraries/jpspan/JPSpan.php';
            require_once JAWS_PATH . 'libraries/jpspan/JPSpan/Server/PostOffice.php';

            // Create a server object, set the URL to submit to, and export some objects.
            $server = new JPSpan_Server_Postoffice();
            $server->setServerUrl(BASE_SCRIPT.'?gadget='.$this->_Name.'&action=Ajax');
            
            foreach ($objects as $object) {
                $server->addHandler($object);
            }

            if (isset($_GET['client'])) {
                // Display the client code.
                define('JPSPAN_INCLUDE_COMPRESS', true);
                $client = $server->displayClient();

                header('Content-type: text/javascript; charset: UTF-8');
                header('Content-Type: application/x-javascript');
                header("Vary: Accept-Encoding");
                header('Cache-Control: must-revalidate');
                header('Expires: ' . gmdate('D, d M Y H:i:s', time() + (60 * 60)) . ' GMT');
                if ($GLOBALS['app']->GZipEnabled()) {
                    $client = @gzencode($client, COMPRESS_LEVEL, FORCE_GZIP);
                    header('Content-Length: '.strlen($client));
                    header('Content-Encoding: '.(strpos($GLOBALS['app']->GetBrowserEncoding(), 'x-gzip')!== false? 'x-gzip' : 'gzip'));
                } else {
                    header('Content-Length: '.strlen($client));
                }
                echo $client;
                exit;
            } else {
                // Process method calls, displaying any errors that occur on the client side.
                require_once JAWS_PATH.'libraries/jpspan/JPSpan/ErrorHandler.php';
                $server->serve();
            }
*/
		} 

        // Yeah, so it's a hack.
        return "alert('The ".$this->GetName()." gadget does not provide a Javascript interface.')";
    }

    /**
     * Overloads Jaws_Gadget::IsValid. Difference: Checks that the gadget (HTML) file exists
     *
     * @access  public
     * @param   string  $gadget Gadget's Name
     * @return  boolean Returns true if the gadget is valid, otherwise will finish the execution
     */
    function IsValid($gadget)
    {
        // Check if file exists
        // Hack until we decide if $gadget.php will be a proxy file
        if (!file_exists(JAWS_PATH . 'gadgets/'.$gadget.'/HTML.php')) {
            Jaws_Error::Fatal('Gadget file doesn\'t exists', __FILE__, __LINE__);
        }

        parent::IsValid($gadget);
    }

    /**
     * Load the most common JS files to use in Ajax
     *
     * @access protected
     */
    function AjaxCommonFiles()
    {
        $content = file_get_contents(JAWS_PATH . 'include/Jaws/Ajax/ErrorHandler.js');
        $content.= file_get_contents(JAWS_PATH . 'include/Jaws/Ajax/Ajax.js');

        header('Content-type: text/javascript; charset: UTF-8');
        header('Content-Type: application/x-javascript');
        header("Vary: Accept-Encoding");
        header('Cache-Control: must-revalidate');
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + (60 * 60)) . ' GMT');
        if ($GLOBALS['app']->GZipEnabled()) {
            $content = gzencode($content, COMPRESS_LEVEL, FORCE_GZIP);
            header('Content-Length: '.strlen($content));
            header('Content-Encoding: '.(strpos($GLOBALS['app']->GetBrowserEncoding(), 'x-gzip')!== false? 'x-gzip' : 'gzip'));
        } else {
            header('Content-Length: '.strlen($content));
        }
		//header('Connection: close');
        echo $content;
		//exit;
    }

    /**
     * Ajax the gadget adding the basic script links to build the interface
     *
     * @access  protected
     * @param   string     $file (Optional) The gadget can require a special JS file, it should be located under
     *                           gadgets/$gadget/resources/$file
     */
    function AjaxMe($file = '')
    {

		$this->_usingAjax = true; 
		$name = $this->GetName();

        /*
		$GLOBALS['app']->Layout->AddScriptLink(BASE_SCRIPT.'?gadget=' . $name . '&amp;action=Ajax&amp;client');
        $GLOBALS['app']->Layout->AddScriptLink(BASE_SCRIPT.'?gadget=' . $name . '&amp;action=AjaxCommonFiles');
        */

		if (BASE_SCRIPT == 'index.php') {
			if ($file == 'client_script.js') {
				$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=' . $name . '&amp;action=Ajax&amp;client=all&amp;stub=' . $name . 'Ajax');
				$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=' . $name . '&amp;action=AjaxCommonFiles');
			} else if ($file == 'script.js') {
				$GLOBALS['app']->Layout->AddScriptLink('admin.php?gadget=' . $name . '&amp;action=Ajax&amp;client=all&amp;stub=' . $name . 'AdminAjax');
				$GLOBALS['app']->Layout->AddScriptLink('admin.php?gadget=' . $name . '&amp;action=AjaxCommonFiles');
			} else {
				$GLOBALS['app']->Layout->AddScriptLink(BASE_SCRIPT . '?gadget=' . $name . '&amp;action=Ajax&amp;client=all&amp;stub=' . $name . (JAWS_SCRIPT == 'admin' ? 'Admin' : '') . 'Ajax');
				$GLOBALS['app']->Layout->AddScriptLink(BASE_SCRIPT . '?gadget=' . $name . '&amp;action=AjaxCommonFiles');
			}
		} else {
			$GLOBALS['app']->Layout->AddScriptLink(BASE_SCRIPT . '?gadget=' . $name . '&amp;action=Ajax&amp;client=all&amp;stub=' . $name . (JAWS_SCRIPT == 'admin' ? 'Admin' : '') . 'Ajax');
			$GLOBALS['app']->Layout->AddScriptLink(BASE_SCRIPT . '?gadget=' . $name . '&amp;action=AjaxCommonFiles');
		}		
		
		if (!empty($file) && file_exists(JAWS_PATH . 'gadgets/' . $name . '/resources/' . $file)) {
            $GLOBALS['app']->Layout->AddScriptLink('gadgets/' . $name . '/resources/' . $file);
        }

        $config = array(
            'DATAGRID_PAGER_FIRSTACTION' => 'javascript: firstValues(); return false;',
            'DATAGRID_PAGER_PREVACTION'  => 'javascript: previousValues(); return false;',
            'DATAGRID_PAGER_NEXTACTION'  => 'javascript: nextValues(); return false;',
            'DATAGRID_PAGER_LASTACTION'  => 'javascript: lastValues(); return false;',
            'DATAGRID_DATA_ONLOADING'   => 'showWorkingNotification;',
            'DATAGRID_DATA_ONLOADED'    => 'hideWorkingNotification;',
        );
        Piwi::addExtraConf($config);
    }

    /**
     * Return the 404 message (page not found)
     *
     * @access  protected
     */
    function _404()
    {
        require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
        return Jaws_HTTPError::Get(404);
    }

    /**
     * Sets the browser's title (<title></title>)
     *
     * @access  public
     * @param   string  $title  Browser's title
     */
    function SetTitle($title)
    {
        //Set title in case we are no running on standalone..
        if (isset($GLOBALS['app']->Layout)) {
            $GLOBALS['app']->Layout->SetTitle($title);
        }
    }

    /**
     * Sets the browser's title (<title></title>)
     *
     * @access  public
     * @param   string  $title  Browser's title
     */
    function SetDescription($desc)
    {
        //Set description in case we are no running on standalone..
        if (isset($GLOBALS['app']->Layout)) {
            $GLOBALS['app']->Layout->SetDescription($desc);
        }
    }

    /**
     * Add keywords to meta keywords tag
     *
     * @access  public
     * @param   string  $keywords
     */
    function AddToMetaKeywords($keywords)
    {
        //Add keywords in case we are no running on standalone..
        if (isset($GLOBALS['app']->Layout)) {
            $GLOBALS['app']->Layout->AddToMetaKeywords($keywords);
        }
    }

    /**
     * Add a language to meta language tag
     *
     * @access  public
     * @param   string  $language  Language
     */
    function AddToMetaLanguages($language)
    {
        //Add language in case we are no running on standalone..
        if (isset($GLOBALS['app']->Layout)) {
            $GLOBALS['app']->Layout->AddToMetaLanguages($language);
        }
    }

    /**
     * Returns the state of usingAjax
     *
     * @access  public
     * @return  boolean
     */
    function usingAjax()
    {
        return $this->_usingAjax;
    }

    /**
     * Search in map and return its url if found
     *
     * @access  protected
     * @param   string     $action    Gadget's action name
     * @param   array      $params    Params that the URL map requires
     * @param   array      $params    Params that the URL map requires
     * @param   boolean    $useExt    Append the extension? (if there's)
     * @param   mixed      URIPrefix  Prefix to use: site_url (config/url), uri_location or false for nothing
     * @return  string     The mapped URL
     */
    function GetURLFor($action='', $params = null, $useExt = true, $URIPrefix = false)
    {
        return $GLOBALS['app']->Map->GetURLFor($this->_Name, $action, $params, $useExt, $URIPrefix);
    }
}
