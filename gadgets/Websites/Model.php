<?php
/**
 * Websites Gadget
 *
 * @category   GadgetModel
 * @package    Websites
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */

class WebsitesModel extends Jaws_Model
{
    var $_Name = 'Websites';
	
    /**
     * Gets a single website by ID.
     *
     * @access  public
     * @param   int     $id     The ID of the website to get.
     * @return  array   An array containing the website information, or false if could not be loaded.
     */
    function GetWebsite($id)
    {
        $params = array();
		$params['id']       = (int)$id;
		
		$sql = '
        SELECT [id], [key], [parent], [domains], [secureaddress], [title], 
			[description], [email], [serveraddress], [mailserver], [expires], 
			[status], [ownerid], [created], [updated], [referralid], [ftp_host], 
			[ftp_port], [ftp_user], [ftp_pass], [ftp_dir], [db_host], [db_driver], 
			[db_user], [db_name], [db_port], [db_prefix], [db_password]
        FROM [[websites]] WHERE [id] = {id}';

        $types = array(
			'integer', 'text', 'integer', 'text', 'text', 'text', 
			'text', 'text', 'text', 'text', 'timestamp', 
			'text', 'integer', 'timestamp', 'timestamp', 'integer', 'text', 
			'text', 'text', 'text', 'text', 'text', 'text',
			'text', 'text', 'text', 'text', 'text'
		);

        $row = $GLOBALS['db']->queryRow($sql, $params, $types);
		
        if (isset($row['id'])) {
            return $row;
        }

        return new Jaws_Error(_t('WEBSITES_ERROR_WEBSITE_NOT_FOUND'), _t('WEBSITES_NAME'));
    }

    /**
     * Gets a single website by key.
     *
     * @access  public
     * @param   text     $key     The key of the website to get.
     * @return  array   An array containing the website information, or false if could not be loaded.
     */
    function GetWebsiteByKey($key)
    {
        $params = array();
		$params['key']       = $key;
		
		$sql = '
        SELECT [id], [key], [parent], [domains], [secureaddress], [title], 
			[description], [email], [serveraddress], [mailserver], [expires], 
			[status], [ownerid], [created], [updated], [referralid], [ftp_host], 
			[ftp_port], [ftp_user], [ftp_pass], [ftp_dir], [db_host], [db_driver], 
			[db_user], [db_name], [db_port], [db_prefix], [db_password]
        FROM [[websites]] WHERE [key] = {key}';

        $types = array(
			'integer', 'text', 'integer', 'text', 'text', 'text', 
			'text', 'text', 'text', 'text', 'timestamp', 
			'text', 'integer', 'timestamp', 'timestamp', 'integer', 'text', 
			'text', 'text', 'text', 'text', 'text', 'text',
			'text', 'text', 'text', 'text', 'text'
		);

        $row = $GLOBALS['db']->queryRow($sql, $params, $types);
		
        if (isset($row['id'])) {
            return $row;
        }

        return new Jaws_Error(_t('WEBSITES_ERROR_WEBSITE_NOT_FOUND'), _t('WEBSITES_NAME'));
    }

    /**
     * Creates new Jaws installation
     *
     * @access  public
     * @return  true on success or Jaws_Error on error
     */
    function CreateWebsite($key = null, $parent = 0)
    {
		// create new .ini file with predefined install data
		$tpl = new Jaws_Template('gadgets/Websites/templates/');
		$tpl->Load('config.html');
		$tpl->SetBlock('config');
				
		if (!is_numeric($parent)) {
			return new Jaws_Error(_t('WEBSITES_ERROR_INSTALL_PARENT_INVALID'), _t('WEBSITES_NAME'));
		}
		
		$request =& Jaws_Request::getInstance();
        if (is_null($key)) {
			$key = $request->get('key', 'get');
		}
		
		$install_gadgets = array(
			'ControlPanel',
			'Jms',
			'Languages',
			'Layout',
			'Policy',
			'Registry',
			'Search',
			'Settings',
			'UrlMapper',
			'Users',
			'Tms',
			'CustomPage',
			'Menu',
			'FileBrowser'
		);

		if (!is_null($key) && !empty($key) && $parent == 0) {
			$website = $this->GetWebsiteByKey($key);
			if (!Jaws_Error::IsError($website)) {
				if (!empty($website['gadgets'])) {
					$gadgets = explode(',',$website['gadgets']); 
					// Get all Gadget pages
					$jms = $GLOBALS['app']->LoadGadget('Jms', 'AdminModel');
					$gadget_list = $jms->GetGadgetsList(null, true, true, true);

					//Hold.. if we dont have a selected gadget?.. like no gadgets?
					if (count($gadget_list) <= 0) {
						Jaws_Error::Fatal('You don\'t have any installed gadgets, please enable/install one and then come back',
										 __FILE__, __LINE__);
					}
					
					reset($gadget_list);
					//$first = current($gadget_list);
					foreach ($gadget_list as $gadget) {
						foreach($gadgets as $igadget) {
							if ($gadget['realname'] == $igadget && !in_array($igadget, $install_gadgets)) {
								array_push($install_gadgets, $igadget);
								break;
							}
						}
					}
				}
			}
		} else if ($parent > 0) {
			// Get last ID
			$sql = "SELECT MAX([id]) FROM [[websites]] WHERE [parent] = {parent} ORDER BY [id] DESC";
			$site_id = $GLOBALS['db']->queryOne($sql, array('parent' => (int)$parent), array('integer'));
			if (Jaws_Error::IsError($site_id)) {
				return $site_id;
			} else {
				if (!is_numeric($site_id)) {
					return new Jaws_Error(_t('WEBSITES_ERROR_INSTALL_SITEID_INVALID'), _t('WEBSITES_NAME'));
				} else {
					$site_id = ((int)$site_id+1);
				}
			
				// Authentication
				$tpl->SetBlock('config/authentication_skip');
				$tpl->ParseBlock('config/authentication_skip');
				$config_parent_id = (int)$parent;
				$tpl->SetVariable('parentid', $config_parent_id);
				$key = md5(uniqid('installer')) . time() . floor(microtime()*1000);
				$config_key = substr($key, 0, 27);
				$tpl->SetVariable('key', $config_key);
				
				// Requirements
				$tpl->SetBlock('config/requirements_skip');
				$tpl->ParseBlock('config/requirements_skip');
				
				// Database
				$tpl->SetBlock('config/database_skip');
				$tpl->ParseBlock('config/database_skip');
				
				$default_expiration = $GLOBALS['app']->Registry->Get('/gadgets/Websites/default_expiration');
				if (!empty($default_expiration) && !is_nan($default_expiration)) {
					$expiration_length = floor($GLOBALS['app']->Registry->Get('/gadgets/Websites/default_expiration'));
					$expiration = mktime(0,0,0,date("m"),date("d")+$expiration_length,date("Y"));
					$config_expires		= date("m/d/Y", $expiration);
				} else {
					$default_expiration = mktime(0,0,0,date("m"),date("d")+30,date("Y"));
					$config_expires		= date("m/d/Y", $default_expiration);
				}
						
				$config_db_host = $db['host'];
				$config_db_driver = $db['driver'];
				$config_db_user = $db['user'];
				$config_db_name = $db['name'];
				$config_db_port = $db['port'];
				$config_db_prefix = $site_id.'_';
				$config_db_password = $db['password'];
				
				$config_gadgets = implode(",",$install_gadgets);
				$config_domains = $GLOBALS['app']->GetSiteURL().'/'.$site_id;
				$site_reseller = $GLOBALS['app']->Registry->Get('/config/site_reseller');
				if (!empty($site_reseller)) {
					$config_reseller = $site_reseller;
				}
				$site_reseller_link = $GLOBALS['app']->Registry->Get('/config/site_reseller_link');
				if (!empty($site_reseller_link)) {
					$config_reseller_link = $site_reseller_link;
				}
				$site_reseller_username = $GLOBALS['app']->Registry->Get('/config/site_reseller_username');
				if (!empty($site_reseller_username)) {
					$config_reseller_username = $site_reseller_username;
				}
				$site_reseller_name = $GLOBALS['app']->Registry->Get('/config/site_reseller_name');
				if (!empty($site_reseller_name)) {
					$config_reseller_name = $site_reseller_name;
				}
				$site_reseller_email = $GLOBALS['app']->Registry->Get('/config/site_reseller_email');
				if (!empty($site_reseller_email)) {
					$config_reseller_email = $site_reseller_email;
				}
				
				$config_site_id = $site_id;

				if (Jaws_Utils::is_writable(JAWS_PATH . '/'.$site_id.'/install/')) {
					$result = file_put_contents(JAWS_PATH . '/'.$site_id.'/install/'.$key.'.ini', $installData);
					if ($result) {
						$install = $this->InstallWebsite($site_id);
						if (!$install) {
							return new Jaws_Error(_t('WEBSITES_ERROR_INSTALL_WEBSITE'), _t('WEBSITES_NAME'));
						}
					} else {
						return new Jaws_Error(_t('WEBSITES_ERROR_INSTALL_WEBSITE'), _t('WEBSITES_NAME'));
					}
				}
			}
		}
		$tpl->ParseBlock('config');
		return new Jaws_Error(_t('WEBSITES_ERROR_INSTALL_WEBSITE'), _t('WEBSITES_NAME'));
		
    }

    /**
     * Copies necessary website files
     *
     * @access  public
     * @return  true or false on error
     */
	function CopyWebsiteFiles($site_id = null, $host = '208.43.223.162', $port = '21', $username = 'devsynt', $password = 'cdlarry82', $dir = 'public_html') 
	{
		if (!is_null($site_id) && !is_numeric($site_id)) {
			return new Jaws_Error(_t('WEBSITES_ERROR_COPY_WEBSITE'), _t('WEBSITES_NAME'));
		}
		require_once JAWS_PATH . 'include/Jaws/FTP.php';
		$ftp = new Jaws_FTP();
		$host = '208.43.223.162';
		$port = '21';
		$username = 'devsynt';
		$password = 'cdlarry82';
		$connect = $ftp->connect($host, $port);
		if (Jaws_Error::IsError($connect)) {
			echo $connect->GetMessage();
		} else {
			$login = $ftp->login($username, $password);
			if (Jaws_Error::IsError($login)) {
				echo $login->GetMessage();
			} else {
				if (!is_null($site_id)) {
					$cd = $ftp->cd('public_html');
					if (Jaws_Error::IsError($cd)) {
						echo $cd->GetMessage();
					} else {
						$mksite = $ftp->mkdir($site_id);
						if (Jaws_Error::IsError($mksite)) {
							echo $mksite->GetMessage();
						} else {
						}
					}
				} else {
					$cd = $ftp->cd('public_html');
					if (Jaws_Error::IsError($cd)) {
						echo $cd->GetMessage();
					} else {
						$get = $ftp->get('external.zip', JAWS_PATH . $site_id .'', true);
						if (Jaws_Error::IsError($get)) {
							echo $get->GetMessage();
						} else {
							echo 'File retrieved';
						}
					}
				}
			}
		}
		return true;
	}

    /**
     * Goes to install setup for a website
     *
     * @access  public
     * @return  page location of website install
     */
    function InstallWebsite($install_url = '')
    {
		if (!empty($install_url)) {
			require_once JAWS_PATH . 'include/Jaws/Header.php';
			Jaws_Header::Location($install_url);
		} else {
			return new Jaws_Error(_t('WEBSITES_ERROR_INSTALL_WEBSITE'), _t('WEBSITES_NAME'));
		}
	}	

}
?>
