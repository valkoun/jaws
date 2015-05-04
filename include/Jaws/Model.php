<?php
/**
 * Jaws Model schema
 *
 * @category   Gadget
 * @package    Core
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2005-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class Jaws_Model
{
    /**
     * Model's name
     * @var    string
     * @access private
     * @see SetName()
     * @see GetName()
     */
    var $_Name;

    /**
     * Model's name
     * @var    string
     * @access private
     * @see SetDescription()
     * @see GetDescription()
     */
    var $_Description;

    /**
     * Model's name
     * @var    string
     * @access private
     * @see SetVersion()
     * @see GetVersion()
     */
    var $_Version;

    /**
     * Refactor Init, Jaws_Model::Init()
     *
     * @access  protected
     */
    function Init($name = '')
    {
        $this->_Name = $name;
    }

    /**
     * Set the model's name
     *
     * @access  public
     * @param   string  $value Model's name
     */
    function SetName($value)
    {
        $this->_Name = $value;
    }

    /**
     * Get the Model's name
     *
     * @access  public
     * @return  string  Returns the Model's name
     */
    function GetName()
    {
        return $this->_Name;
    }

    /**
     * Set the Model's mode
     *
     * @access  public
     * @param   string  $value Model's mode
     */
    function SetVersion($value)
    {
        $this->_Version = $value;
    }

    /**
     * Get the Model's mode
     *
     * @access  public
     * @return  string  Returns the Model's version
     */
    function GetVersion()
    {
        return $this->_Version;
    }

    /**
     * Set the Model's description
     *
     * @access  public
     * @param   string  $value Model's description
     */
    function SetDescription($value)
    {
        $this->_Description = $value;
    }

    /**
     * Get the Model's description
     *
     * @access  public
     * @return  string  Returns the Model's description
     */
    function GetDescription()
    {
        return $this->_Description;
    }

    /**
     * Performs any actions required to finish installing a gadget.
     * Gadgets should override this method only if they need to perform actions to install.
     *
     * @access  public
     * @return  boolean True on success and Jaws_Error on failure
     */
    function InstallGadget()
    {
        return true;
    }

    /**
     * Updates the gadget
     *
     * @access  public
     * @return  boolean True on success and Jaws_Error on failure
     */
    function UpdateGadget()
    {
        return true;
    }

    /**
     * @access public
     */
    function InstallSchema($main_schema, $variables = array(), $base_schema = false, $data = false, $create = true, $debug = false)
    {
        $info = $GLOBALS['app']->LoadGadget($this->_Name, 'Info');
        $main_file = JAWS_PATH . 'gadgets/'. $this->_Name . '/schema/' . $main_schema;
        if (!file_exists($main_file)) {
            return new Jaws_Error (_t('GLOBAL_ERROR_SQLFILE_NOT_EXISTS', $main_file), $info->GetAttribute('Name'));
        }

        $base_file = false;
        if (!empty($base_schema)) {
            $base_file = JAWS_PATH . 'gadgets/'. $this->_Name . '/schema/' . $base_schema;
            if (!file_exists($base_file)) {
                return new Jaws_Error (_t('GLOBAL_ERROR_SQLFILE_NOT_EXISTS', $base_file),
                                      $info->GetAttribute('Name'));
            }
        }

        $result = $GLOBALS['db']->installSchema($main_file, $variables, $base_file, $data, $create, $debug);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error($result->GetMessage(),
            //return new Jaws_Error(_t('GLOBAL_ERROR_FAILED_QUERY_FILE', $main_schema . (empty($base_schema)? '': "/$base_schema")),
                                 $info->GetAttribute('Name'));
        }

        return true;
    }

    /**
     * Wrapper of $GLOBALS['app']->Shouter->Shout() for models
     *
     * @access  protected
     */
    function Shout($call, $param, $time = null)
    {
        $GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
        return $GLOBALS['app']->Shouter->Shout($call, $param, $time);
    }

    /**
     * Return an array with the Search Results
     * This method must be implemented by each model
     *
     * Struct spec:
     * title - Title of the resource
     * url - URL to resource found
     * image - URL to image(can be relative or absolute, suggested size: 133x100)
     * snippet - Snippet of the result(can be null)
     * date - Insert or update date(can be null)
     */
    function Search($string)
    {
        return false;
    }

    /**
     * Returns the fast URL of an entry
     *
     * @access  public
     * @param   string   $fastUrl  FastUrl string
     * @return  array    Entry info or false
     */
    function GetFastURL($fastUrl)
    {
        return false;
    }

    /**
     * Get the total of data we have in a table
     *
     * @access  public
     * @param   string  $table  Table's name to query
     * @param   string  $pKey   Optional. Primary key to use for counting
     * @return  int     Total of data we have
     */
    function TotalOfData($table, $pKey = 'id')
    {
        $sql = 'SELECT COUNT(['.$pKey.']) FROM [['. $table . ']]';
        $res = $GLOBALS['db']->queryOne($sql);
        return Jaws_Error::IsError($res) ? 0 : $res;
    }

    /**
     * Installs the ACLs defined in the Info
     *
     * @access public
     */
    function InstallACLs()
    {
        $acls = array();
        $info = $GLOBALS['app']->LoadGadget($this->_Name, 'Info');
        foreach ($info->GetACLs() as $acl => $opts) {
            $acls[] = array($acl, $opts['Default']);
        }
        $GLOBALS['app']->ACL->NewKeyEx($acls);
        $GLOBALS['app']->ACL->commit($this->_Name);
    }

    /**
     * Installs the ACLs defined in the Info
     *
     * @access public
     */
    function UninstallACLs()
    {
        $info = $GLOBALS['app']->LoadGadget($this->_Name, 'Info');
        foreach($info->GetACLs() as $acl => $opts){
            $GLOBALS['app']->ACL->DeleteKey($acl);
        }
        $GLOBALS['app']->ACL->Commit($this->_Name);
    }

    /**
     * Checks if fast_url already exists in a table, if it doesn't then it returns
     * the original fast_url (the param value). However, if it already exists then 
     * it starts looking for a 'valid' fast_url using the 'foobar-[1...n]' schema.
     *
     * @access  protected
     * @param   string     $fast_url     Fast URL
     * @param   string     $table        DB table name (without [[ ]])
     * @param   boolean    $unique_check must be false in update methods
     * @param   string     $field        Table field where fast_url is stored
     * @return  string     Correct fast URL
     */
    function GetRealFastURL($fast_url, $table, $unique_check = true, $field = 'fast_url', $current_id_field = null, $current_id = null)
    {
        if (is_numeric($fast_url)) {
            $fast_url = '-' . $fast_url . '-';
        }

        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $fast_url = $xss->defilter($fast_url, true);

        $fast_url = preg_replace('#\s|/|\\\#', '-', $fast_url);
        $fast_url = preg_replace('#&|"|\'|\%|<|>#', '', $fast_url);

        if (!$unique_check) {
            return $fast_url;
        }

        $params = array();
        $params['fast_url'] = $fast_url;

        $sql = "
             SELECT COUNT(*)
             FROM [[$table]]
             WHERE [$field] = {fast_url}";
			 
		// Check everything except the current ID
		if (!is_null($current_id_field) && !is_null($current_id)) {
			$params['current_id'] = $current_id;
			$sql .= " AND [$current_id_field] != {current_id}";
		}		

        $total = $GLOBALS['db']->queryOne($sql, $params);
        if (Jaws_Error::isError($total) || ($total == '0')) {
            return $fast_url;
        }

        //Get a list of fast_url's that match the current entry
        $params['fast_url'] = $GLOBALS['app']->UTF8->trim($fast_url).'%';

        $sql = "
             SELECT [$field]
             FROM [[$table]]
             WHERE [$field] LIKE {fast_url}";

        $urlList = $GLOBALS['db']->queryAll($sql, $params);
        if (Jaws_Error::IsError($urlList)) {
            return $fast_url;
        }

        $counter = 1;
        $numbers = array();
        foreach($urlList as $url) {
            //Matches the foo-\d?
            if (preg_match("/(.+?)-([0-9]{0,})/", $url[$field], $matches)) {
                $numbers[] = (int)$matches[2];
            }
        }
        if (count($numbers) == 0) {
            return $fast_url . '-1';
        }
        $lastNumber = $numbers[count($numbers)-1];
        return $fast_url.'-'.($lastNumber+1);
    }
    
	/**
     * Cleans image paths for local images being inserted
     *
     * @access  protected
     * @param   string     $image     The image URL
     * @return  string     Correct image URL
     */
    function cleanImagePath($image = '')
    {
        if (empty($image)) {
			return $image;
		}
		$old_src = $image;
		$image_src = str_replace(array(
			strtolower($GLOBALS['app']->getDataURL('files/thumb/', true)), 
			strtolower($GLOBALS['app']->getDataURL('files/medium/', true)), 
			strtolower($GLOBALS['app']->getDataURL('files/', true)), 
			strtolower($GLOBALS['app']->getDataURL('/', true)), 
			strtolower($GLOBALS['app']->getDataURL('files/thumb/', true, false, true)),
			strtolower($GLOBALS['app']->getDataURL('files/medium/', true, false, true)),
			strtolower($GLOBALS['app']->getDataURL('files/', true, false, true)),
			strtolower($GLOBALS['app']->getDataURL('/', true, false, true))
		), '/', $image);
		$image_src = (substr($image_src, 0, 1) == '/' && substr(strtolower(trim($image_src)), 0, 4) != 'http' && !file_exists(JAWS_DATA . 'files/'.$image_src) ? '' : $image_src);
		while (substr($image_src, 0, 1) == '/') {
			$image_src = substr($image_src, 1, strlen($image_src));
			if (substr(strtolower($image_src), 0, 4) == "http") {
				if (substr(strtolower($image_src), 0, 7) == "http://") {
					$image_src = explode('http://', $image_src);
					foreach ($image_src as $img_src) {
						if (!empty($img_src)) {
							$image_src = 'http://'.$img_src;
							break;
						}
					}
				} else {
					$image_src = explode('https://', $image_src);
					foreach ($image_src as $img_src) {
						if (!empty($img_src)) {
							$image_src = 'https://'.$img_src;
							break;
						}
					}
				}
				break;
			} else {
				if (substr($image_src, 0, 1) != '/' && file_exists(JAWS_DATA . 'files/'.$image_src)) {
					$image_src = '/'.$image_src;
					break;
				}
			}
		}
		if ($image_src != $old_src) {
			$image_src = (substr($image_src, 0, 1) != '/' && !is_array($image_src) ? '/'.$image_src : (is_array($image_src) ? implode('', $image_src) : $image_src));
		}
		return $image_src;
	}

}