<?php
/**
 * Saves a configure JawsConfig.php
 *
 * @author Jon Wood <jon@substance-it.co.uk>
 * @access public
 */
class Installer_WriteConfig extends JawsInstallerStage
{
    /**
     * Sets up a JawsConfig
     *
     * @access public
     * @return string
     */
    function BuildConfig()
    {
        include_once JAWS_PATH . 'include/Jaws/Template.php';
        $tpl = new Jaws_Template(INSTALL_PATH . 'stages/WriteConfig/templates');
        $tpl->Load('JawsConfig.php', false, false);

        $tpl->SetBlock('JawsConfig');
		$tpl->SetVariable('jaws_path',  "DIRECTORY_SEPARATOR");
        $tpl->SetVariable('db_driver',  $_SESSION['install']['Database']['driver']);
        $tpl->SetVariable('db_host',    $_SESSION['install']['Database']['host']);
        $tpl->setVariable('db_port',    $_SESSION['install']['Database']['port']);
        $tpl->SetVariable('db_user',    $_SESSION['install']['Database']['user']);
        $tpl->SetVariable('db_pass',    $_SESSION['install']['Database']['password']);
        $tpl->SetVariable('db_isdba',   $_SESSION['install']['Database']['isdba']);
        $tpl->SetVariable('db_path',    addslashes($_SESSION['install']['Database']['path']));
        $tpl->SetVariable('db_name',    $_SESSION['install']['Database']['name']);
        $tpl->SetVariable('db_prefix',  $_SESSION['install']['Database']['prefix']);
		
		$tpl->SetBlock('JawsConfig/jaws_urls');
		$tpl->SetVariable('jaws_ssl_url', $GLOBALS['app']->GetJawsURL());
		$tpl->SetVariable('jaws_url', $GLOBALS['app']->GetJawsURL());
        $tpl->ParseBlock('JawsConfig/jaws_urls');
        
		require_once JAWS_PATH . 'include/Jaws/DB.php';
		$GLOBALS['db'] = Jaws_DB::getInstance($_SESSION['install']['Database']);

		require_once JAWS_PATH . 'include/Jaws.php';
		$GLOBALS['app'] = new Jaws();
		$GLOBALS['app']->create();
		$GLOBALS['app']->OverwriteDefaults(array('language' => $_SESSION['install']['language']));
	
        $tpl->ParseBlock('JawsConfig');

        return $tpl->Get();
    }

    /**
     * Builds the installer page.
     *
     * @access  public
     * @return  string      A block of valid XHTML to display an introduction and form.
     */
    function Display()
    {
        log_install("Preparing configuration file");
        $tpl = new Jaws_Template(INSTALL_PATH . 'stages/WriteConfig/templates/');
        $tpl->Load('display.html', false, false);
		if (isset($_SESSION['install']['data']['WriteConfig']['skip']) && !isset($GLOBALS['message'])) {	        
           $_SESSION['install']['WriteConfig']['skip'] = '1';
           header('Location: index.php');
        } else {
	        $tpl->SetBlock('WriteConfig');
	
	        $config_path = str_replace('/data', '', JAWS_DATA) .'config'.DIRECTORY_SEPARATOR;
	        $tpl->setVariable('lbl_info',                _t('INSTALL_CONFIG_INFO'));
	        $tpl->setVariable('lbl_solution',            _t('INSTALL_CONFIG_SOLUTION'));
	        $tpl->setVariable('lbl_solution_permission', _t('INSTALL_CONFIG_SOLUTION_PERMISSION', $config_path));
	        $tpl->setVariable('lbl_solution_upload',     _t('INSTALL_CONFIG_SOLUTION_UPLOAD', $config_path. 'JawsConfig.php'));
	        $tpl->SetVariable('next',                    _t('GLOBAL_NEXT'));
	
	        $tpl->SetVariable('config', $this->BuildConfig());
	        $tpl->ParseBlock('WriteConfig');
		}
		return $tpl->Get();
    }

    /**
     * Does any actions required to finish the stage, such as DB queries.
     *
     * @access  public
     * @return  bool|Jaws_Error  Either true on success, or a Jaws_Error
     *                          containing the reason for failure.
     */
    function Run()
    {
		$return = false;
        //config string
        $configString = $this->BuildConfig();
		$configDir = str_replace('/data', '', JAWS_DATA) . 'config/';
        
		// following what the web page says (choice 1) and assume that the user has created it already
        if (file_exists($configDir . 'JawsConfig.php')) {
            $configMD5    = md5($configString);
            $existsConfig = file_get_contents($configDir . 'JawsConfig.php');
            $existsMD5    = md5($existsConfig);
            if ($configMD5 == $existsMD5) {
                log_install("Previous and new configuration files have the same content, everything is ok");
                $return = true;
            }
            log_install("Previous and new configuration files have different content, trying to update content");
        }

        // create a new one if the dir is writeable
        if (Jaws_Utils::is_writable($configDir)) {
            $result = file_put_contents($configDir . 'JawsConfig.php', $configString);
            if ($result) {
                log_install("Configuration file has been created/updated");
                $return = true;
			} else {
	            log_install("Configuration file couldn't be created/updated");
	            return new Jaws_Error(_t('INSTALL_CONFIG_RESPONSE_WRITE_FAILED'), 0, JAWS_ERROR_ERROR);
			}
		} else {
			log_install("Configuration file couldn't be created/updated");
			return new Jaws_Error(_t('INSTALL_CONFIG_RESPONSE_WRITE_FAILED'), 0, JAWS_ERROR_ERROR);
		}
		
        if ((file_exists(INSTALL_PATH . 'key.txt') || (isset($_SESSION['install']['data']['Authentication']['key']) && isset($_SESSION['install']['data']['Authentication']['parent']))) && $return == true) {
			require_once JAWS_PATH . 'include/Jaws/DB.php';
			$GLOBALS['db'] = Jaws_DB::getInstance($_SESSION['install']['Database']);
			#if (Jaws_Error::IsError($GLOBALS['db'])) {
			#   return new Jaws_Error("There was a problem connecting to the database, please check the details and try again.", 0, JAWS_ERROR_WARNING);
			#}

			require_once JAWS_PATH . 'include/Jaws.php';
			$GLOBALS['app'] = new Jaws();
			$GLOBALS['app']->create();
			$GLOBALS['app']->OverwriteDefaults(array('language' => $_SESSION['install']['language']));

			$config_key = $GLOBALS['app']->Registry->Get('/config/key');
			if (file_exists(INSTALL_PATH . 'key.txt') && !empty($config_key)) {
				$key = $config_key;
            } else if (isset($_SESSION['install']['data']['Authentication']['key'])) {
				$key = $_SESSION['install']['data']['Authentication']['key'];
			} else {
				log_install("Authentication Key does not exist");
				return new Jaws_Error(_t('INSTALL_CONFIG_GLOBALDATA_RESPONSE_WRITE_FAILED'), 0, JAWS_ERROR_ERROR);
			}

			require_once JAWS_PATH . 'include/Jaws/User.php';
			$userModel = new Jaws_User();

			if (isset($_SESSION['install']['CreateUser']['username'])) {
				$userInfo = $userModel->GetUserInfoByName($_SESSION['install']['CreateUser']['username']);
			} else {
				log_install("The newly created user could not be found");
				return new Jaws_Error(_t('INSTALL_CONFIG_GLOBALDATA_RESPONSE_WRITE_FAILED'), 0, JAWS_ERROR_ERROR);
			}
			
			$globalID = 0;
			$parentID = 0;
			//0	ID = 0, 
            if (!file_exists(INSTALL_PATH . 'key.txt')) {
				if (isset($_SESSION['install']['data']['WriteConfig']['siteid'])) {
					$globalID = (int)$_SESSION['install']['data']['WriteConfig']['siteid'];
				/*
				} else {
					log_install("A site id could not be found");
					return new Jaws_Error(_t('INSTALL_CONFIG_GLOBALDATA_RESPONSE_WRITE_FAILED'), 0, JAWS_ERROR_ERROR);
				*/
				}
				if (isset($_SESSION['install']['data']['Authentication']['parent'])) {
					$parentID = (int)$_SESSION['install']['data']['Authentication']['parent'];
				/*
				} else {
					log_install("A parent id could not be found");
					return new Jaws_Error(_t('INSTALL_CONFIG_GLOBALDATA_RESPONSE_WRITE_FAILED'), 0, JAWS_ERROR_ERROR);
				*/
				}
			}
			//1	company = 'Jaws Project', 
			$global_company = "";
			if ($GLOBALS['app']->Registry->Get('/config/site_name')) {
				$global_company = $GLOBALS['app']->Registry->Get('/config/site_name');
			}
			//2	tagline = 'Create it, and Share it.', 
			$global_tagline = "";
			if ($GLOBALS['app']->Registry->Get('/config/site_slogan')) {
				$global_tagline = $GLOBALS['app']->Registry->Get('/config/site_slogan');
			}
			//3	domains (comma-separated) = 'example.com,example.org', 
			$global_domainname = '';
			if (isset($_SESSION['install']['data']['WriteConfig']['domains']) && !empty($_SESSION['install']['data']['WriteConfig']['domains'])) {
				$global_domainname = $_SESSION['install']['data']['WriteConfig']['domains'];
				if (strpos($global_domainname, ',') !== false) {
					$domains = explode(',', $global_domainname);
					$global_domainname = $domains[0];
				}
			} else {
				if ($GLOBALS['app']->getSiteURL()) {
					$global_domainname = $GLOBALS['app']->getSiteURL();
				} else {
					log_install("Domain name could not be retrieved");
					return new Jaws_Error(_t('INSTALL_CONFIG_GLOBALDATA_RESPONSE_WRITE_FAILED'), 0, JAWS_ERROR_ERROR);
				}
			}
			//5	secureaddress = 'secure.jaws-project.com', 
			$global_secureaddress = "";
			if (isset($_SESSION['install']['data']['WriteConfig']['secureaddress']) && !empty($_SESSION['install']['data']['WriteConfig']['secureaddress'])) {
				$global_secureaddress = $_SESSION['install']['data']['WriteConfig']['secureaddress'];
			}
			//6	serveraddress = '74.208.28.14', 
			if (function_exists('gethostbyname')) {
				$global_sIP = false;
				if (isset($global_domainname) && !empty($global_domainname)) {
					$global_sIP = gethostbyname($global_domainname);
				} else {
					$global_sIP = gethostbyname($GLOBALS['app']->getSiteURL());
				}
				if ($global_sIP !== false && !empty($global_sIP)) {
					$global_serveraddress = $global_sIP;
				} else {
					log_install("Server address could not be read");
					return new Jaws_Error(_t('INSTALL_CONFIG_GLOBALDATA_RESPONSE_WRITE_FAILED'), 0, JAWS_ERROR_ERROR);
				}
			} else {
				log_install("Function gethostbyname does not exist");
				return new Jaws_Error(_t('INSTALL_CONFIG_GLOBALDATA_RESPONSE_WRITE_FAILED'), 0, JAWS_ERROR_ERROR);
			}
			//7 Expires = '" & DateAdd("y", 1, now()) & "', 
			$expire_year = 2038;
			$expire_month = (int)date("m");
			$expire_date = (int)date("d");
			$default_expiration = mktime(0,0,0,$expire_date,$expire_month,$expire_year);
			$global_expires	= $GLOBALS['db']->Date($default_expiration);
			if (isset($_SESSION['install']['data']['WriteConfig']['expires']) && !empty($_SESSION['install']['data']['WriteConfig']['expires'])) {
				$global_expires = $GLOBALS['db']->Date(strtotime($_SESSION['install']['data']['WriteConfig']['expires']));
			}
			//8	status = 'temp',
			if (isset($_SESSION['install']['data']['WriteConfig']['status']) && !empty($_SESSION['install']['data']['WriteConfig']['status'])) {
				$global_status = $_SESSION['install']['data']['WriteConfig']['status'];
			} else {	
				if ($globalID > 0) {
					$global_status = "temp";
				} else {
					$global_status = "active";
				}
			}
			//9	fromaddr = 'info@example.org', 
			if (isset($_SESSION['install']['CreateUser']['email'])) {
				$global_fromaddr = $_SESSION['install']['CreateUser']['email'];
			} else {
				log_install("E-mail address for newly created user could not be found");
				return new Jaws_Error(_t('INSTALL_CONFIG_GLOBALDATA_RESPONSE_WRITE_FAILED'), 0, JAWS_ERROR_ERROR);
			}
			//10 mailserver = '74.208.28.14', 
			$global_mailserver = $global_serveraddress;
			if (isset($_SESSION['install']['data']['WriteConfig']['mailserver']) && !empty($_SESSION['install']['data']['WriteConfig']['mailserver'])) {
				$global_mailserver = $_SESSION['install']['data']['WriteConfig']['mailserver'];
			}
			//11 OwnerID = '295', 
			$global_OwnerID = $userInfo['id'];
			if (isset($_SESSION['install']['data']['WriteConfig']['ownerid']) && !empty($_SESSION['install']['data']['WriteConfig']['ownerid'])) {
				$global_OwnerID = $_SESSION['install']['data']['WriteConfig']['ownerid'];
			}
			//12 referralID = '6c86ab917c20be34230fc660bb64f6y8', 
			$global_referralID = '';
			if (isset($_SESSION['install']['data']['WriteConfig']['referralid']) && !empty($_SESSION['install']['data']['WriteConfig']['referralid'])) {
				$global_referralID = $_SESSION['install']['data']['WriteConfig']['referralid'];
			}
			//16 created = '11/10/2008', 
			$global_created = $GLOBALS['db']->Date();
						
			$toReplace = 'http://';
			if (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on') {
				$toReplace = 'https://';
			}
			
			$globaldata = '';
			/*
			$sql = "
				INSERT INTO [websites]
					([id], [key], [parent], [domains], [secureaddress], 
					[title], [description], [email], [serveraddress], [mailserver], 
					[expires], [status], [OwnerID], [created], [updated], [referralID])
				VALUES
					({id}, {key}, {parent}, {domains}, {secureaddress}, 
					{title}, {description}, {email}, {serveraddress}, {mailserver}, 
					{expires}, {status}, {OwnerID}, {now}, {now}, {referralID})";

			$params               		= array();
			$params['id']      			= $globalID;
			$params['key']      		= $key;
			$params['parent']   		= $parentID;
			$params['domains']   		= str_replace($toReplace, '', $global_domainname);
			$params['secureaddress']   	= str_replace($toReplace, '', $global_secureaddress);
			$params['title']   			= $global_company;
			$params['description']   	= $global_tagline;
			$params['email']   			= $global_fromaddr;
			$params['serveraddress']   	= str_replace($toReplace, '', $global_serveraddress);
			$params['mailserver']   	= str_replace($toReplace, '', $global_mailserver);
			$params['expires']   		= $global_expires;
			$params['status']   		= $global_status;
			$params['OwnerID']        	= (int)$global_OwnerID;
			$params['referralID']       = $global_referralID;
			$params['now'] 				= $global_created;

			$result = $GLOBALS['db']->query($sql, $params);
			if (!Jaws_Error::IsError($result)) {
			*/
				if ($parentID > 0) {
					$globaldata .= "\n".
						$globalID."\t".
						$key."\t".
						$parentID."\t".
						str_replace($toReplace, '', $global_domainname)."\t".
						str_replace($toReplace, '', $global_secureaddress)."\t".
						$global_company."\t".
						$global_tagline."\t".
						$global_fromaddr."\t".
						str_replace($toReplace, '', $global_serveraddress)."\t".
						str_replace($toReplace, '', $global_mailserver)."\t".
						$global_expires."\t".
						$global_status."\t".
						$global_OwnerID."\t".
						$global_referralID."\t".
						$global_created;
					log_install("Internal Website was created.");
					log_install($globaldata);
					return true;				
				} else {
					$globaldata = "\n".
						$key."\t".
						$global_company."\t".
						$global_tagline."\t".
						str_replace($toReplace, '', $global_domainname)."\t".
						str_replace($toReplace, '', $global_domainname)."\t".
						$global_expires."\t".
						$global_status."\t".
						$_SESSION['install']['data']['CreateUser']['reseller_email']."\t".
						$global_created;
					
					$keydata = "\n".
						$globalID."\t".
						$global_company."\t".
						$global_tagline."\t".
						str_replace($toReplace, '', $global_domainname)."\t".
						"\t".
						str_replace($toReplace, '', $global_domainname)."\t".
						$global_expires."\t".
						$global_status."\t".
						$_SESSION['install']['data']['CreateUser']['reseller_email']."\t".
						str_replace($toReplace, '', $global_domainname)."\t".
						"12\t".
						"Y\t".
						"Y\t".
						$_SESSION['install']['data']['CreateUser']['reseller_email']."\t".
						"3\t".
						$global_created;
					
					if (Jaws_Utils::is_writable(JAWS_DATA)) {
						$result = file_put_contents(JAWS_DATA . $key.'.txt', $keydata);
						if ($result) {
							log_install("data/".$key.".txt file has been created");
							$return = true;
						} else {
							log_install("data/".$key.".txt file couldn't be created");
							return new Jaws_Error(_t('INSTALL_CONFIG_RESPONSE_WRITE_FAILED'), 0, JAWS_ERROR_ERROR);
						}
					}
					
					log_install($globaldata);
					return true;				
				}
			//}
			log_install("Global data file couldn't be created/updated");
			return new Jaws_Error(_t('INSTALL_CONFIG_GLOBALDATA_RESPONSE_WRITE_FAILED'), 0, JAWS_ERROR_ERROR);
		}
        return new Jaws_Error(_t('INSTALL_CONFIG_RESPONSE_MAKE_GLOBALDATA', 'key.txt'), 0, JAWS_ERROR_WARNING);
	}

}