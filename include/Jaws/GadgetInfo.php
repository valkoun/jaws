<?php
/**
 * Class that manages/saves the basic info of a gadget
 *
 * @category   Gadget
 * @package    Core
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @copyright  2005-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
define('WEBSERVICE', 'Webservice');
define('WSDL', 'WSDL');
define('XMLRPC', 'XML-RPC');
//Types of requirements
define('REQ_GADGET', 0);
define('REQ_LIBRARY', 1);
define('REQ_BINARY', 3);
define('REQ_3rdParty', 4);

class Jaws_GadgetInfo
{
    /**
     * - Provides
     *  List of stuff the gadget provides,
     *  like webservices.
     * - ACL
     *  List of access list the gadget
     *  has and their translated name/functionality
     * - Requires
     *  List of requirements a gadget
     *  needs
     * - Attributes
     *  Attributes of the gadget
     * - Urls
     *  Attributes of the fast URLs
     *
     * @access  private
     * @var     array
     */
    var $_info = array(
        'provides'   => array(),
        'acl'        => array(),
        'requires'   => array(),
        'attributes' => array(),
        'urls'       => array(),
    );

    /**
     * Name of the gadget, is shorter using it this way
     */
    var $_Name = '';

    /**
     * Initializes the Info object, loading the trasnlation file for example
     *
     * @access protected
     * @param  string   Gadget's name(same as the filesystem name)
     */
    function Init($gadget)
    {
        $GLOBALS['app']->Translate->LoadTranslation($gadget, JAWS_GADGET);
        $this->_Name = $gadget;
    }

    /**
     * Sets an attribute
     *
     * @access protected
     * @param  string    Key name
     * @param  string    Key value
     * @param  string    Key description
     */
    function SetAttribute($key, $value, $description = '')
    {
        $this->_info['attributes'][$key] =
            array(
                'Value'       => $value,
                'Description' => $description
            );
    }

    /**
     * Returns the value of the given key
     *
     * @access protected
     * @param  string    Key
     */
    function GetAttribute($key)
    {
        if (isset($this->_info['attributes'][$key]['Value'])) {
            return $this->_info['attributes'][$key]['Value'];
        }

        return null;
    }

    /**
     * Returns the description of the given key
     *
     * @access protected
     * @param  string    Key
     */
    function GetAttributeDescription($key)
    {
        if ($this->_info['attributes'][$key]['Description']) {
            return $this->_info['attributes'][$key]['Description'];
        }

        return null;
    }

    /**
     * Sets the manual/doc URL
     *
     * @access protected
     * @param  string     Manual/doc URL
     */
    function Doc($page, $url = JAWS_WIKI, $multilanguage = true)
    {
        $lang = $GLOBALS['app']->GetLanguage();
        $lang = ($multilanguage && $lang!= 'en')? ($lang . '/') : '';
        $this->SetAttribute('Doc', $url . $lang . $page);
    }

    /**
     * Sets the gadget name
     *
     * @access protected
     * @param  string    Gadget name
     */
    function GadgetName($name)
    {
        $this->SetAttribute('Name', $name, _t('GLOBAL_GI_GADGET_NAME'));
    }

    /**
     * Sets the section of the gadget(Gadget, Customers, etc..)
     *
     * @access protected
     * @param  string   Gadget's section
     */
    function GadgetSection($section)
    {
        $this->SetAttribute('Section', $section, _t('GLOBAL_GI_GADGET_SECTION'));
    }

    /**
     * Sets the Jaws version that the gadget's depends on
     *
     * @access protected
     * @param  string    Jaws's version
     */
    function RequiresJaws($version)
    {
        $this->SetAttribute('JawsVersion', $version, _t('GLOBAL_GI_GADGET_JAWSVERSION'));
    }
    
    /**
     * Gets the jaws version that the gadget requires
     *
     * @access protected
     */
    function GetRequiredJawsVersion()
    {
        $jawsVersion = $this->GetAttribute('JawsVersion');
        if (is_null($jawsVersion)) {
            $jawsVersion = $GLOBALS['app']->Registry->Get('/config/version');
        }

        return $jawsVersion;
    }

    /**
     * Gets the gadget name
     *
     * @access protected
     */
    function GetName()
    {
        return $this->GetAttribute('Name');
    }

    /**
     * Gets the gadget doc/manual URL
     *
     * @access protected
     */
    function GetDoc()
    {
        return $this->GetAttribute('Doc');
    }

    /**
     * Sets the gadget description
     *
     * @access protected
     * @param   string Gadget description
     */
    function GadgetDescription($desc)
    {
        $this->SetAttribute('Description', $desc, _t('GLOBAL_GI_GADGET_DESC'));
    }

    /**
     * Gets the gadget description
     *
     * @access protected
     */
    function GetDescription()
    {
        return $this->GetAttribute('Description');
    }

    /**
     * Sets the gadget version
     *
     * @access protected
     * @param  string    Gadget version
     */
    function GadgetVersion($version)
    {
        $this->SetAttribute('Version', $version, _t('GLOBAL_GI_GADGET_VERSION'));
    }

    /**
     * Turns on/off if gadget offers a method to retrieve a list of URLs.
     *
     * @access protected
     * @param  boolean Yes/No
     */
    function ListURL($allow = true)
    {
        $this->SetAttribute('AllowListURL', $allow);
    }

    /**
     * Returns true or false if gadget offers a method (Model::GetListURLs) to retrieve a list of URLs
     *
     * @access  public
     * @return  boolean  Yes/No
     */
    function AllowListURL()
    {
        $allow = $this->GetAttribute('AllowListURL');

        return (is_bool($allow)) ? $allow : false;
    }

    /**
     * Gets the gadget version
     *
     * @access protected
     */
    function GetVersion()
    {
        return $this->GetAttribute('Version');
    }

    /**
     * Gets the gadget's section
     *
     * @access protected
     * @return string Gadget's section
     */
    function GetSection()
    {
        $section = $this->GetAttribute('Section');
        if (empty($section)) {
            return 'Gadgets';
        }

        return $section;
    }

    /**
     * Register an ACL key info
     *
     * @access  protected
     * @param string ACL Key
     * @param string Short description
     */
    function ACLKey($key, $desc, $default='false')
    {
        $this->_info['acl'][$key] =
            array(
                'Description' => $desc,
                'Default' => $default
            );
    }

    /**
     * Gets the short description of a given ACL key
     *
     * @access protected
     * @param  string     $key ACL Key
     * @return string     The ACL description
     */
    function GetACLDescription($key)
    {
        if (isset($this->_info['acl'][$key]['Description'])) {
            return $this->_info['acl'][$key]['Description'];
        }

        return $key;
    }

    /**
     * Register required gadgets
     *
     * @param string Gadget's name as arguments
     */
    function Requires()
    {
        $gadgets = func_get_args();
        foreach ($gadgets  as $gadget) {
            $this->_info['requires'][] = $gadget;
        }
    }

    /**
     * Get the requirements of the gadget
     * @return  array Gadget's Requirements
     */
    function GetRequirements()
    {
        return $this->_info['requires'];
    }

    /**
     * Get what the gadget provides
     * @return  array What the gadget provides
     */
    function GetProvides()
    {
        return $this->_info['provides'];
    }

    /**
     * Get all ACLs for the gadet
     * @return  array ACLs of the gadget
     */
    function GetACLs()
    {
        return $this->_info['acl'];
    }

    /**
     * Get all attributres for the gadet
     * @return  array Attributes of the gadget
     */
    function GetAttributes()
    {
        return $this->_info['attributes'];
    }

    /**
     * Register a provided service
     * @access protected
     * @param string Service name
     * @param string Service description
     * @param string URL where the service is provided
     */
    function Provides($service, $url, $desc)
    {
        $this->_info['provides'][$service][] =
            array(
                'Description' => $desc,
                'URL'         => $url
            );
    }

    /**
     * Loads an associative array as the ACL keys and descriptions
     * according with the gadget name
     *
     * @access public
     * @param  array The array
     */
    function PopulateACLs($acls)
    {
        if (is_array($acls)) {
            foreach ($acls as $key => $value) {
                //ACL comes with a value?
                if ($value === 'true' || $value === 'false') {
                    $default = $value;
                    $acl     = $key;
                } else {
                    //False by default
                    $default = 'false';
                    $acl     = $value;
                }
                $key = '/ACL/gadgets/' . $this->_Name . '/' . $acl;
                $desc = strtoupper($this->_Name) . '_ACL_' . strtoupper($acl);
                $this->ACLKey($key, _t($desc), $default);
            }
        }
    }
}