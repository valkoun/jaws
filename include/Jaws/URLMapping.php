<?php
/**
 * Custom URL Mapping/Rewriting for SEO-friendly URL patterns.
 *
 * @category   JawsType
 * @category   feature
 * @package    Core
 * @author     Jonathan Hernandez  <ion@suavizado.com>
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2005-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class Jaws_URLMapping
{
    /**
     * Model that will be used to get data
     *
     * @var    UrlMapperModel
     * @access private
     */
    var $_Model;

    var $_map = array();
    var $_delimiter = '@';
    var $_enabled;
    var $_use_file;
    var $_use_rewrite;
    var $_custom_precedence;
    var $_use_aliases;
    var $_extension;

    /**
     * Constructor
     * Initializes the map, just pass null to a param if you want
     * to use the default values
     *
     * @param   boolean $enabled        When true uses maps
     * @param   boolean $use_file       When true it uses maps files
     * @param   boolean $use_rewrite    Set to true if you're using
     *                                  mod_rewrite (don't show ? in url)
     * @param   string  $use_aliases    When true it parses aliases in each 'Parse' request
     * @param   string  $to_use         Which maps should we use? both (custom and core), just core or only custom?
     * @param   string  $extension      Extension URL maps should append or parse
     * @access public
     */
    function Jaws_URLMapping($enabled = null, $use_file = null, $use_rewrite = null, $use_aliases = null, $to_use = null, $extension = null)
    {
        if ($enabled === null) {
            $enabled = ($GLOBALS['app']->Registry->Get('/map/enabled') == 'true');
        }

        if ($use_file === null) {
            $use_file = ($GLOBALS['app']->Registry->Get('/map/use_file')    == 'true');
        }

        if ($use_rewrite === null) {
            $use_rewrite = ($GLOBALS['app']->Registry->Get('/map/use_rewrite') == 'true');
        }

        if ($use_aliases === null) {
            $use_aliases = ($GLOBALS['app']->Registry->Get('/map/use_aliases') == 'true');
        }

        if ($extension === null) {
            $extension = $GLOBALS['app']->Registry->Get('/map/extensions');
        }

        $this->_enabled           = $enabled;
        $this->_use_file          = $use_file;
        $this->_use_rewrite       = $use_rewrite;
        $this->_use_aliases       = $use_aliases;
        $this->_custom_precedence = $GLOBALS['app']->Registry->Get('/map/custom_precedence') == 'true';
        if (!empty($extension) && $extension{0} != '.') {
            $extension = '.'.$extension;
        }
        $this->_extension = $extension;

        $this->_Model = $GLOBALS['app']->loadGadget('UrlMapper', 'Model');
        if (Jaws_Error::isError($this->_Model)) {
            Jaws_Error::Fatal($this->_Model->getMessage(), __FILE__, __LINE__);
        }
    }

    /**
     * Resets the map
     *
     * @access  public
     */
    function Reset()
    {
        $this->_map = array();
    }

    /**
     * Adds a map
     *
     * @param   string  $gadget     Gadget name
     * @param   string  $action     Action name
     * @param   string  $map        Map (e.g. '/blog/view/{id}')
     * @param   string  $extension  Extension of mapped utl (e.g. html, xml, ....)
     * @param   array   $reqs       Array with the validation for each var
     * @param   array   $extraparms Array with the extra params with its default values
     * @param   boolean $custom Is it a custom map? (defined by user)
     * @access public
     */
    function Connect($gadget, $action, $map, $extension = '', $reqs = null, $extraparams = null)
    {
        $regexp = str_replace('/', '\/', $map);

        // Default validation
        if (preg_match_all('#{(\w+)}#si', $regexp, $matches)) {
            foreach ($matches[1] as $m) {
                if (!isset($reqs[$m])) {
                    $reqs[$m] = '\w+';
                }
            }
        }

        if (is_array($reqs)) {
            foreach ($reqs as $k => $v) {
                $regexp = str_replace('{' . $k . '}', '(' . $v . ')', $regexp);
            }
        }

        $regexp = str_replace($this->_delimiter, '\\' . $this->_delimiter, $regexp);
        $regexp = $this->_delimiter . '^' . $regexp . '$' . $this->_delimiter;

        //for compatible with old versions
        $extension = ($extension == 'index.php')? '' : $extension;

        $this->_Model->AddMap($gadget, $action, $map, $regexp, $extension, false);
    }

    /**
     * Loads the maps
     *
     * @access public
     */
    function Load()
    {
        if ($this->_enabled) {
            for ($i = 1; $i <= 2; $i++) {
                $map_type = $this->_custom_precedence? 'custom' : 'core';
                if ($this->_use_file) {
                    $map_file = JAWS_DATA . "maps/$map_type.php";
                    if (!file_exists($map_file)) {
                        $this->CreateMapFile($this->_custom_precedence);
                    }
                    @include $map_file;
                    if (isset($map) && is_array($map)) {
                        $this->SetMap($map);
                        unset($map);
                    }
                } else {
                    $this->LoadMapsFromTable($this->_custom_precedence);
                }

                //change map file by reverse condition
                $this->_custom_precedence = !$this->_custom_precedence;
            }
        }
    }

    /**
     * Setter for _map
     *
     * @access public
     */
    function SetMap($map)
    {
        foreach ($map as $gadget => $gadgetsActions) {
            foreach ($gadgetsActions as $action => $actionsMaps) {
                foreach ($actionsMaps as $map) {
                    $this->_map[$gadget][$action][] = $map;
                }
            }
        }
    }

    /**
     * Returns the map
     *
     * @access  public
     * @return  array   Complete map
     */
    function GetMap()
    {
        return $this->_map;
    }

    /**
     * Load map from UrlMapper table
     *
     * @access public
     */
    function LoadMapsFromTable($custom = false)
    {
        $maps = $this->_Model->GetMaps(!$custom, $custom);
        if (Jaws_Error::IsError($maps)) {
            return false;
        }
				
		foreach ($maps as $map) {
			$this->_map[$map['gadget']][$map['action']][] = array(
								'map'       => $map['map'],
								'params'    => null,
								'regexp'    => $map['regexp'],
								'extension' => $map['extension'],
								'custom'    => $map['custom'],
			);
		}
    }

    /**
     * Creates the JAWS_DATA . '/maps/core.php' or JAWS_DATA . '/maps/custom.php'
     *
     * This file will have an array of all maps of the enabled gadgets
     *
     * @param string Gadget's name, if the name is given the method will *force*
     *               the require_once on the gadget cause it means it's going to be
     *
     * @access public
     */
    function CreateMapFile($custom = false)
    {
        $map_dir = JAWS_DATA. 'maps'. DIRECTORY_SEPARATOR;
        if (is_dir($map_dir)) {
            if (!Jaws_Utils::is_writable($map_dir)) {
                return new Jaws_Error(_t('GLOBAL_ERROR_URLMAP_MAPDIR_NOT_WRITABLE', $map_dir), 'CORE');
            }
        } else {
            $map_dir = JAWS_DATA. 'maps'. DIRECTORY_SEPARATOR;
            if (!Jaws_Utils::mkdir($map_dir)) {
                return new Jaws_Error(_t('GLOBAL_ERROR_URLMAP_MAPDIR_CANT_CREATE', $map_dir), 'CORE');
            }
        }

        $arrayAsString = "<?php\n" . $this->ToArrayString($custom);
        $mapFile = $map_dir . (($custom === false)? 'core.php' : 'custom.php');
        Jaws_Utils::file_put_contents($mapFile, $arrayAsString);
    }

    /**
     * Parses a QUERY URI and if its valid it extracts the values from
     * it and creates $_GET variables for each value.
     *
     * @param   string  $path   Query URI
     * @param   boolean  $return_realpath   Should we only return the parsed URL?
     */
    function Parse($path = '', $return_realpath = false)
    {
        if (!$this->_enabled && !is_array($this->_map)) {
            return false;
        }
        if (empty($path)) {
            $path = $this->getPathInfo(false);
        } elseif (strpos($path, 'http') !== false) {
            //prepare it manually
            $strPos = stripos($path, BASE_SCRIPT);
            if ($strPos != false) {
                $strPos = $strPos + strlen(BASE_SCRIPT);
                $path   = substr($path, $strPos);
            }
        }
        $path = urldecode($path);
		if (substr($path, 0, 4) == '253F' || substr($path, 0, 2) == '3F' || substr($path, 0, 2) == '2F') {
			return true;
		}
        //If it has a slash at the start or end, remove it
        $path = trim($path, '/');
        if (substr($path, - strlen($this->_extension)) == $this->_extension) {
            $path = substr($path, 0, - strlen($this->_extension));
        }


        //Moment.. first check if we are running on aliases_mode
        if ($this->_use_aliases && $realPath = $this->_Model->GetAliasPath($path)) {
            $path = str_ireplace(BASE_SCRIPT, '', $realPath);
        }

        //If no path info is given but count($_POST) > 0?
        if (empty($path) && count($_POST) > 0) {
            return true;
        }

        if (strpos($path, '=') !== false) {
            return true;
        }

        $request =& Jaws_Request::getInstance();
        //Lets check HTTP headers to see if user is trying to login
        if ($request->get('gadget', 'post') == 'ControlPanel' && $request->get('action', 'post') == 'Login') {
            $request->set('get', 'gadget', 'ControlPanel');
            $request->set('get', 'action', 'Login');
            return true;
        }

        $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'get';
        $method = strtolower($method);
        $method = in_array($method, array('get', 'head', 'post', 'put'))? $method : 'get';


        //Aliases only work with GET, maybe we should add POST maps
        $params = explode('/', $path);
        $path = implode('/', array_map('urlencode', $params));
		$actual_path = '';
		
		//var_dump($this->_map);
		$i = 0;
		$urlmaps = array();
		$custommaps = array();
		$regmaps = array();
		foreach ($this->_map as $gadget => $actions) {
            foreach ($actions as $action => $maps) {
                foreach ($maps as $map) {
					$map['params'] = ((isset($map['params']) && is_array($map['params'])) ? $map['params'] : null);
					if ($map['custom']) {
						$custommaps[] = array(
								'sort'       => $i,
								'gadget'       => $gadget,
								'action'       => $action,
								'params'    => $map['params'],
								'regexp'    => $map['regexp'],
								'extension' => $map['extension'],
								'map'    => $map['map'],
								'custom'    => $map['custom'],
						);
						$i++;
					} else {
						$regmaps[] = array(
								'sort'       => $i,
								'gadget'       => $gadget,
								'action'       => $action,
								'params'    => $map['params'],
								'regexp'    => $map['regexp'],
								'extension' => $map['extension'],
								'map'    => $map['map'],
								'custom'    => $map['custom'],
						);
						$i++;
					}
				}
			}
		}
				
		if ($this->_custom_precedence) {
			$urlmaps = array_merge($custommaps, $regmaps);
		} else {
			$urlmaps = array_merge($regmaps, $custommaps);
		}
		foreach ($urlmaps as $map) {
			$url = $path;
			$ext = empty($map['extension'])?  $this->_extension : $map['extension'];
			if (substr($url, - strlen($ext)) == $ext) {
				$url = substr($url, 0, - strlen($ext));
			}
			if ($return_realpath === true) {
				if (substr($url, 0, 3) == '%3F') {
					$url = substr($url, 3, strlen($url));
				} else if (substr($url, 0, 3) == '%2F') {
					$url = substr($url, 3, strlen($url));
				}
			}

			if (preg_match($map['regexp'], $url, $matches) == 1) {
				/**
				 * TODO: I'm still not pretty sure if we should 'force' the
				 * method to 'get' or if we should use $method  (the real requested method)
				 */
				// Gadget/Action
				if ($return_realpath === true) {
					$actual_path = '?gadget='.$map['gadget'];
					$actual_path .= '&action='.$map['action'];
				} else {
					$request->set('get', 'gadget', $map['gadget']);
					$request->set('get', 'action', $map['action']);
				}
				// Params
				if (isset($map['params']) && is_array($map['params'])) {
					foreach ($map['params'] as $key => $value) {
						if ($return_realpath === true) {
							$actual_path .= '&'.$key.'='.$value;
						} else {
							$request->set('get', $key, $value);
						}
					}
				}
				// Vars
				preg_match_all('#{(\w+)}#si', $map['map'], $matches_vars);
				if (is_array($matches_vars)) {
					foreach ($matches_vars[1] as $key => $value) {
						if ($return_realpath === true) {
							$actual_path .= '&'.$value.'='.urldecode($matches[$key + 1]);
						} else {
							$request->set('get', $value, urldecode($matches[$key + 1]));
						}
					}
				}
				return ($return_realpath === true ? (!empty($actual_path) ? $GLOBALS['app']->GetSiteURL() . '/index.php'.$actual_path : '') : true);
			}
		}

        /**
         * Ok, no alias and map found, so lets parse the path directly.
         * The first rule: it should have at least one value (the gadget name)
         */
        $params_count = count($params);
        if ($params_count >= 1) {
            if ($params_count == 1) {
				if ($return_realpath === true) {
					$actual_path = '?gadget='.$params[0];
                } else {
					$request->set($method, 'gadget', $params[0]);
				}
			} else {
                //First value is the gadget name.. but which protocol are we using?
				if ($return_realpath === true) {
					$actual_path = '?gadget='.$params[0];
					$actual_path .= '&action='.$params[1];
                } else {
					$request->set($method, 'gadget', $params[0]);
					$request->set($method, 'action', $params[1]);
                }
				/**
                 * If we have a request via POST we should take those values, not the GET ones
                 * However, I'm not pretty sure if we should allow gadget and action being passed
                 * with /, cause officially (HTTP) you can't do that (params are passed via & not /)
                 *
                 * Next params following gadget/action should be parsed only if they come from a
                 * GET request
                 */
                if ($method == 'get') {
                    //Ok, next values should be formed in pairs
                    $params = array_slice($params, 2);
                    $params_count = count($params);
                    if ($params_count % 2 == 0) {
                        for ($i = 0; $i < $params_count; $i += 2) {
							if ($return_realpath === true) {
								$actual_path .= '&'.$params[$i].'='.$params[$i+1];
							} else {
								$request->set($method, $params[$i], $params[$i+1]);
							}
						}
                    }
                }
            }

			return ($return_realpath === true ? (!empty($actual_path) ? $GLOBALS['app']->GetSiteURL() . '/index.php'.$actual_path : '') : true);
        }

        return false;
    }

    /**
     * Returns the prefix URI
     *
     * @access  public
     * @param   string  $option   Can be:
     *
     *          - site_url: Will take what is in /config/url
     *          - uri_location: Will use the URI location (NO HTTP protocol defined)
     *          - nothing: Use nothing
     * @return  string URI prefix
     */
    function GetURIPrefix($option)
    {
        static $site_url;

        switch($option) {
        case 'site_url':
            if (isset($site_url)) {
                return $site_url;
            }
            $site_url = $GLOBALS['app']->getSiteURL('/');
            return $site_url;
            break;
        case 'uri_location':
            return $GLOBALS['app']->GetURILocation();
            break;
        }
        return '';
    }

    /**
     * Does the reverse stuff for an URL map. It gets all the params i
     * as an array and converts all the stuff to an URL map
     *
     * @access  public
     * @param   string  $gadget   Gadget's name
     * @param   string  $action   Gadget's action name
     * @param   array   $params   Params that the URL map requires
     * @param   boolean $useExt   Append the extension? (if there's)
     * @param   mixed   $URIPrefix Prefix to use: site_url (config/url), uri_location or false for nothing
     * @return  string  The real URL map (aka jaws permalink)
     */
    function GetURLFor($gadget, $action='', $params = null, $useExt = true, $URIPrefix = false)
    {
        //in IIS webservice and also in cgi mode when cgi.fix_pathinfo=0, url  mapping not work
        // we can't detect cgi.fix_pathinfo via ini_get and only can detect this option with it's affect
        // when cgi.fix_pathinfo=1 then $_SERVER['PATH_INFO'] not set
        //$map_off = (strpos(strtolower(strip_tags($_SERVER['SERVER_SOFTWARE'])), 'iis')!==false) ||
        //           (substr(php_sapi_name(), 0, 3) == 'cgi' && isset($_SERVER['PATH_INFO']));
        $map_off = true;
        if ($this->_enabled && isset($this->_map[$gadget][$action])) {
            foreach ($this->_map[$gadget][$action] as $map) {
                $url = $map['map'];
                if (is_array($params)) {
                    foreach ($params as $key => $value) {
                        $value = implode('/', array_map('urlencode', explode('/', $value)));
                        $url = str_replace('{' . $key . '}', $value, $url);
                    }
                }

                if (!preg_match('#{\w+}#si', $url)) {
                    if (!$this->_use_rewrite) {
                        $url = 'index.php' . ($map_off? '?' : '/') . $url;
                    }
                    if ($useExt) {
                        $url .= empty($map['extension'])?  $this->_extension : $map['extension'];
                    }
                    break;
                }
            }

            return $this->GetURIPrefix($URIPrefix) . $url;
        }

        if ($this->_use_rewrite) {
            $url = $gadget . '/'. $action;
        } elseif ($map_off || !$this->_enabled) {
            $url = 'index.php?' .$gadget . '/'. $action;
        } else {
            $url = 'index.php/' .$gadget . '/'. $action;
        }
        if (is_array($params)) {
            //Params should be in pairs
            foreach ($params as $key => $value) {
                $value = implode('/', array_map('urlencode', explode('/', $value)));
                $url.= '/' . $key . '/' . $value;
            }
        }

        return $url;
    }

    /**
     * Returns the real path of an alias (given path), if no alias is found
     * it returns false
     *
     * @access  private
     * @param   string  $alias       Alias
     * @return  mixed   Real path (URL) or false
     */
    function GetAliasPath($alias)
    {
        $sql = '
            SELECT
               [real_url]
            FROM [[url_aliases]]
            WHERE [alias_hash] = {hash}';

        $result = $GLOBALS['db']->queryOne($sql, array('hash' => md5($alias)));
        if (Jaws_Error::IsError($result) || empty($result)) {
            return false;
        }

        return $result;
    }

    /**
     * Returns the map as an array definition
     *
     * @access  public
     * @return  string  Array definition
     */
    function ToArrayString($custom = false)
    {
        $this->LoadMapsFromTable($custom);


        $res = "\$map = array();\n";
        foreach ($this->_map as $gadget => $actions) {
            foreach ($actions as $action => $maps) {
                $count = 0;
                foreach ($maps as $map) {
                    if ($custom != $map['custom']) {
                        continue;
                    }


                    $res .= "\$map['{$gadget}']['{$action}'][] = array(\n";
                    $res .= "\t\t\t\t'map' => '{$map['map']}',\n";
                    $res .= "\t\t\t\t'extension'  => '{$map['extension']}',\n";
                    if ($map['custom'] === true) {
                        $res .= "\t\t\t\t'custom'  => true,\n";
                    } else {
                        $res .= "\t\t\t\t'custom'  => false,\n";
                    }
                    $res .= "\t\t\t\t'regexp' => '{$map['regexp']}'";
                    if (isset($map['params']) && is_array($map['params'])) {
                        $res .= ",\n";
                        $res .= "\t\t\t\t'params' => array(\n";
                        foreach ($map['params'] as $k => $v) {
                            $res .= "\t\t\t\t\t\t'{$k}' => '{$v}',\n";
                        }
                        $res = substr($res, 0, -2);
                        $res .= "\n\t\t\t\t\t\t)";
                    }
                    $res .= ");\n";
                    $count++;
                }
            }
        }
        return $res;
    }

    /**
     * Returns the PATH_INFO or simulates it
     *
     * @access  private
     * @param   boolean  $useDash  Return the PATH_INFO with the first dash (YES)
     * @return  string   PATH_INFO (empty or with a trailing dash)
     */
    function getPathInfo($useDash = true)
    {
        static $pathInfo;

        if (isset($pathInfo)) {
            if ($useDash === false) {
                return substr($pathInfo, 1);
            }
            return $pathInfo;
        }

        $pathInfo = '';
        if (isset($_SERVER['PATH_INFO']) && !empty($_SERVER['PATH_INFO'])) {
            $pathInfo = $_SERVER['PATH_INFO']; //its already defined..
        }

        if (isset($_SERVER['ORIG_PATH_INFO']) && !empty($_SERVER['ORIG_PATH_INFO'])) {
            $pathInfo = $_SERVER['ORIG_PATH_INFO'];
        }

        //If we already have a path info try to avoid checking ENV, I don't Like it very much :-/
        if (empty($pathInfo)) {
            if (isset($_ENV['ORIG_PATH_INFO']) && !empty($_ENV['ORIG_PATH_INFO'])) {
                $pathInfo = $_ENV['ORIG_PATH_INFO'];
            }

            if (isset($_ENV['PATH_INFO']) && !empty($_ENV['PATH_INFO'])) {
                $pathInfo = $_ENV['PATH_INFO'];
            }
        }

        /**
         * Hold.. during this point we should have a valid PATH_INFO?
         * remember that a valid PATH_INFO should have a / after the BASE_SCRIPT
         */
        if (!empty($pathInfo) && !($this->_use_rewrite)) {
            $strPos = stripos($pathInfo, BASE_SCRIPT . '/');
            if ($strPos === false) {
                $pathInfo = '';
            }
        }

        //Ok, NO PATH_INFO found..
        if (empty($pathInfo)) {
            //prepare it manually
            if (isset($_SERVER['REQUEST_URI']) && !empty($_SERVER['REQUEST_URI'])) {
                $uri = $_SERVER['REQUEST_URI'];
            } elseif (isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING'])) {
                $uri = $_SERVER['PHP_SELF'] . '?' .$_SERVER['QUERY_STRING'];
            } else {
                $uri = '';
            }

            if (!empty($uri)) {
                $strPos = stripos($uri, BASE_SCRIPT);
                if ($strPos != false) {
                    $strPos = $strPos + strlen(BASE_SCRIPT);
                    $pathInfo = substr($uri, $strPos);
                } else {
                    $strPos = strpos($uri, '?');
                    if ($strPos) {
                        $pathInfo = substr($uri, $strPos);
                    } else {
                        $base_uri = $GLOBALS['app']->GetSiteURL('', true);
                        if ($base_uri == substr($uri, 0, strlen($base_uri))) {
                            $pathInfo = substr($uri, strlen($base_uri));
                        }
                    }
                }
            }
        }

        //IIS trick
        $serverSoftware = isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : '';
        if (strpos($serverSoftware, 'iis') != false) {
            if (!empty($pathInfo) && $pos = strpos($pathInfo, '.php')) {
                $pathInfo = substr($pathInfo, $pos + 4);
            }
        }

        if (!empty($pathInfo)) {
            $dotPosition = stripos($pathInfo, BASE_SCRIPT);
            if ($dotPosition !== false) {
                $pathInfo = substr($pathInfo, $dotPosition + strlen(BASE_SCRIPT));
            }
        }


        if (empty($pathInfo)) {
            $pathInfo = '/';
        }

        if ($useDash === false) {
            $pathInfo = substr($pathInfo, 1);
        }

        return $pathInfo;
    }

}