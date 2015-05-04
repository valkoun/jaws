<?php
/**
 * Database Stage
 *
 * @category   Application
 * @package    InstallStage
 * @author     Jon Wood <jon@substance-it.co.uk>
 * @author     Helgi �ormar �orbj�rnsson <dufuz@php.net>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2005-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class Installer_Database extends JawsInstallerStage
{
    /**
     * Default values.
     * @var string
     * @access protected
     */
    var $_Defaults = array(
        'host'   => 'localhost',
        'driver' => '',
        'user'   => '',
        'isdba'  => '',
        'path'   => '',
        'name'   => 'jaws',
        'prefix' => '',
        'port'   => '',
    );

    /**
     * Builds the installer page.
     *
     * @access  public
     * @return  string A block of valid XHTML to display an introduction and form.
     */
    function Display()
    {
        $jconfig = dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'config/JawsConfig.php';
        if (file_exists($jconfig)) {
            @include $jconfig;
        }

        // Get values
        $values = $this->_Defaults;
        foreach ($this->_Defaults as $name => $value) {
            if (isset($_SESSION['install']['Database'][$name])) {
                $values[$name] = $_SESSION['install']['Database'][$name];
            } elseif (isset($db[$name])) {
                $values[$name] = $db[$name];
            }
        }
        $values['isdba'] = !empty($values['isdba']) && $values['isdba'] == 'true';

        $data = array();
        if (isset($_SESSION['install']['data']['Database'])) {
            $data = $_SESSION['install']['data']['Database'];
        }

		if (isset($_SESSION['install']['data']['Database']['skip']) && !isset($GLOBALS['message'])) {	        
			$_SESSION['install']['Database']['skip'] = '1';
			header('Location: index.php');
        } else {        
			$tpl = new Jaws_Template(INSTALL_PATH . 'stages/Database/templates/');
			$tpl->Load('display.html', false, false);
			$tpl->SetBlock('Database');

			$tpl->setVariable('db_info',   _t('INSTALL_DB_INFO'));
			$tpl->setVariable('db_notice', _t('INSTALL_DB_NOTICE'));
			$tpl->SetVariable('next',      _t('GLOBAL_NEXT'));

			if ($_SESSION['install']['secure'] && isset($_SESSION['pub_mod']) && isset($_SESSION['pub_exp'])) {
				$tpl->SetVariable('pub_modulus',  $_SESSION['pub_mod']);
				$tpl->SetVariable('pub_exponent', $_SESSION['pub_exp']);
				$tpl->SetVariable('func_onsubmit', 'EncryptPassword(this)');
			} else {
				$_SESSION['pub_key'] = '';
				$_SESSION['pvt_key'] = '';
				$tpl->SetVariable('func_onsubmit', 'true');
			}

			$fields = 0;
			if (!isset($data['host'])) {
				$fields++;
				$tpl->SetBlock('Database/host');
				$tpl->setVariable('lbl_host',  _t('INSTALL_DB_HOST'));
				$tpl->setVariable('host_info', _t('INSTALL_DB_HOST_INFO', 'localhost'));
				$tpl->SetVariable('host', $values['host']);
				$tpl->ParseBlock('Database/host');
			}

			if (!isset($data['user'])) {
				$fields++;
				$tpl->SetBlock('Database/user');
				$tpl->setVariable('lbl_user',    _t('INSTALL_DB_USER'));
				$tpl->setVariable('is_db_admin', _t('INSTALL_DB_IS_ADMIN'));
				$tpl->SetVariable('user', $values['user']);
				$tpl->SetVariable('isdba_checked', !empty($values['isdba'])? 'checked="checked"' : '');
				$tpl->ParseBlock('Database/user');
			}

			if (!isset($data['dbpass'])) {
				$fields++;
				$tpl->SetBlock('Database/password');
				$tpl->setVariable('lbl_pass', _t('INSTALL_DB_PASS'));
				$tpl->SetVariable('dbpass', '');
				$tpl->ParseBlock('Database/password');
			}

			if (!isset($data['name'])) {
				$fields++;
				$tpl->SetBlock('Database/name');
				$tpl->setVariable('lbl_db_name', _t('INSTALL_DB_NAME'));
				$tpl->SetVariable('name', $values['name']);
				$tpl->ParseBlock('Database/name');
			}

			if (!isset($data['path'])) {
				$fields++;
				$tpl->SetBlock('Database/path');
				$tpl->setVariable('lbl_db_path', _t('INSTALL_DB_PATH'));
				$tpl->setVariable('path_info',   _t('INSTALL_DB_PATH_INFO'));
				$tpl->SetVariable('path', $values['path']);
				$tpl->ParseBlock('Database/path');
			}

			if (!isset($data['port'])) {
				$fields++;
				$tpl->SetBlock('Database/port');
				$tpl->setVariable('lbl_port',  _t('INSTALL_DB_PORT'));
				$tpl->setVariable('port_info', _t('INSTALL_DB_PORT_INFO'));
				$tpl->SetVariable('port', $values['port']);
				$tpl->ParseBlock('Database/port');
			}

			if (!isset($data['prefix'])) {
				$fields++;
				$tpl->SetBlock('Database/prefix');
				$tpl->setVariable('lbl_prefix',  _t('INSTALL_DB_PREFIX'));
				$tpl->setVariable('prefix_info', _t('INSTALL_DB_PREFIX_INFO'));
				$tpl->SetVariable('prefix', $values['prefix']);
				$tpl->ParseBlock('Database/prefix');
			}

			// drivers
			if (!isset($data['driver'])) {
				$fields++;
				$tpl->SetBlock('Database/drivers');
				$tpl->setVariable('lbl_driver',  _t('INSTALL_DB_DRIVER'));

				$drivers = array(
					'mysqli' => array('ext' => 'mysqli',    'title' => 'MySQLi (4.1.3 and above)'),
					'mysql'  => array('ext' => 'mysql',     'title' => 'MySQL'),
					'pgsql'  => array('ext' => 'pgsql',     'title' => 'PostgreSQL'),
					'oci8'   => array('ext' => 'oci8',      'title' => 'Oracle'),
					'mssql'  => array('ext' => 'mssql',     'title' => 'MSSQL Server'),
					'sqlsrv' => array('ext' => 'sqlsrv',    'title' => 'MSSQL Server(Microsoft Driver)'),
					'ibase'  => array('ext' => 'interbase', 'title' => 'Interbase/Firebird'),
					'sqlite' => array('ext' => 'sqlite',    'title' => 'SQLite 2'),
					/* These databases either haven't been tested or are kown not to work.
					'fbsql'  => 'Frontbase',
					*/
				);

				$modules = get_loaded_extensions();
				$modules = array_map('strtolower', $modules);
				foreach ($drivers as $driver => $driver_info) {
					log_install("Checking if ".$driver_info['title']. "(".$driver_info['ext'].") driver is available");
					if (!in_array($driver_info['ext'], $modules)) {
						$available = false;
						//However... mssql support exists in some Linux distros with the sybase package
						if ($driver_info['ext'] == 'mssql' && function_exists('mssql_connect')) {
							$available = true;
						}
						
						if ($available === false) {
							log_install("Driver ".$driver_info['title']. "(".$driver_info['ext'].") is NOT available");
							continue;
						}
					}
					log_install("Driver ".$driver_info['title']. "(".$driver_info['ext'].") is available");
					$tpl->setBlock('Database/drivers/driver');
					$tpl->setVariable('d_name', $driver);
					$tpl->setVariable('d_realname', $driver_info['title']);
					if ($values['driver'] == $driver) {
						$selected = ' selected="selected"';
					} else {
						$selected = '';
					}
					$tpl->setVariable('d_selected', $selected);
					$tpl->ParseBlock('Database/drivers/driver');
				}
				$tpl->ParseBlock('Database/drivers');
			}

			if ($fields === 0 && !isset($GLOBALS['message'])) {
				$_SESSION['install']['Database']['skip'] = '1';
			   header('Location: index.php');
			}
			
			$tpl->ParseBlock('Database');
			return $tpl->Get();
		}
    }

    /**
     * Validates any data provided to the stage.
     *
     * @access  public
     * @return  bool|Jaws_Error  Returns either true on success, or a Jaws_Error
     *                          containing the reason for failure.
     */
    function Validate()
    {
        $request =& Jaws_Request::getInstance();
        $post = $request->get(array('host', 'user', 'name', 'path', 'port'), 'post');
        if (isset($_SESSION['install']['data']['Database'])) {
            $post = $_SESSION['install']['data']['Database'] + $post;
        }

        if (isset($post['path']) && $post['path'] !== '' && !is_dir($post['path'])) {
            log_install("The database path must be exists");
            return new Jaws_Error(_t('INSTALL_DB_RESPONSE_PATH'), 0, JAWS_ERROR_WARNING);
        }

        if (isset($post['port']) && $post['port'] !== '' && !is_numeric($post['port'])) {
            log_install("The port can only be a numeric value");
            return new Jaws_Error(_t('INSTALL_DB_RESPONSE_PORT'), 0, JAWS_ERROR_WARNING);
        }

        if (!empty($post['host']) && !empty($post['user']) && !empty($post['name'])) {
            return true;
        }

        log_install("You must fill in all the fields apart from table prefix and port");
        return new Jaws_Error(_t('INSTALL_DB_RESPONSE_INCOMPLETE'), 0, JAWS_ERROR_WARNING);
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
        $keys = array_keys($this->_Defaults);
        $keys[] = 'dbpass';
        $request =& Jaws_Request::getInstance();
        $post = $request->get($keys, 'post');
        $request->reset();

        if (isset($_SESSION['install']['data']['Database'])) {
            $post = $_SESSION['install']['data']['Database'] + $post;
        }

        if ($_SESSION['install']['secure'] && isset($_SESSION['pvt_key'])) {
            require_once JAWS_PATH . 'include/Jaws/Crypt.php';
            $JCrypt = new Jaws_Crypt();
            $pvt_key = Crypt_RSA_Key::fromString($_SESSION['pvt_key'], $JCrypt->wrapper);
            $post['dbpass'] = $JCrypt->rsa->decryptBinary($JCrypt->math->int2bin($post['dbpass']), $pvt_key);
            if (Jaws_Error::isError($post['dbpass'])) {
                log_install($post['dbpass']->getMessage());
                return new Jaws_Error($post['dbpass']->getMessage(), 0, JAWS_ERROR_ERROR);
            }
        }

        if (substr($post['prefix'], -1) == '_') {
            $prefix = $post['prefix'];
        } elseif (strlen($post['prefix']) > 0) {
            $prefix = $post['prefix'] . '_';
        } else {
            $prefix = $post['prefix'];
        }

        if (!empty($post['path'])) {
            if (DIRECTORY_SEPARATOR != '/') {
                $post['path'] = str_replace('/', '\\', $post['path']);
            }
            if (substr($post['path'], -1) != DIRECTORY_SEPARATOR) {
                $post['path'] .= DIRECTORY_SEPARATOR;
            }
        }

        $_SESSION['install']['Database'] = array(
            'user'     => trim($post['user']),
            'password' => $post['dbpass'],
            'isdba'    => !empty($post['isdba'])? 'true' : 'false',
            'name'     => trim($post['name']),
            'path'     => trim($post['path']),
            'host'     => trim($post['host']),
            'port'     => trim($post['port']),
            'prefix'   => $prefix,
            'driver'   => $post['driver'],
        );
		
        // Connect to database
        require_once JAWS_PATH . 'include/Jaws/DB.php';
        $GLOBALS['db'] = new Jaws_DB($_SESSION['install']['Database']);
        if (Jaws_Error::IsError($GLOBALS['db'])) {
            log_install("There was a problem connecting to the database.");
            return new Jaws_Error(_t('INSTALL_DB_RESPONSE_CONNECT_FAILED'), 0, JAWS_ERROR_WARNING);
        }

        $variables = array();
        $core_ok = $data_ok = $structure_ok = true;
        $variables['timestamp'] = $GLOBALS['db']->Date();

        $result = $GLOBALS['db']->installSchema('schema/schema.xml', $variables);
        log_install("Installing core schema");
        if (Jaws_Error::isError($result)) {
            $structure_ok = false;
            log_install($result->getMessage());
            return $result;
        }

        //Make sure user don't have any data/cache/registry|acl stuff
        $path = JAWS_DATA . 'cache/registry';
        if (!Jaws_Utils::Delete($path, false)) {
            log_install("Can't delete $path");
        }

        $path = JAWS_DATA . 'cache/acl';
        if (!Jaws_Utils::Delete($path, false)) {
            log_install("Can't delete $path");
        }

        // Create application
        require_once JAWS_PATH . 'include/Jaws.php';
        $GLOBALS['app'] = new Jaws();
        $GLOBALS['app']->create();
        $GLOBALS['app']->OverwriteDefaults(array('language' => $_SESSION['install']['language']));
        $GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');

        log_install("Cleaning previous registry and acl cache data files");
        //Make sure user don't have any data/cache/registry|acl stuff
        $path = JAWS_DATA . 'cache/registry';
        if (!Jaws_Utils::Delete($path, false)) {
            log_install("Can't delete $path");
        }

        $path = JAWS_DATA . 'cache/acl';
        if (!Jaws_Utils::Delete($path, false)) {
            log_install("Can't delete $path");
        }

        // Input datas
        $timestamp = $GLOBALS['db']->Date();

		if (isset($_SESSION['install']['data']['Authentication']['key'])) {
			$uniqueKey = $_SESSION['install']['data']['Authentication']['key'];
		} else {        
			/**
	         * Create a jaws key (should be unique)
	         *
	         * We use RFC 4122 (http://www.ietf.org/rfc/rfc4122.txt) for generating unique
	         * ids
	         */
	        $uniqueKey =  sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
	                              mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
	                              mt_rand( 0, 0x0fff ) | 0x4000,
	                              mt_rand( 0, 0x3fff ) | 0x8000,
	                              mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ) );
	        $uniqueKey = md5($uniqueKey);
		}

        $robots = array('Yahoo! Slurp',
                        'Baiduspider',
                        'Googlebot',
                        'msnbot',
                        'Gigabot',
                        'ia_archiver',
                        'yacybot',
                        'http://www.WISEnutbot.com',
                        'psbot',
                        'msnbot-media',
                        'Ask Jeeves',
                        );

        //registry keys.
        $result = $GLOBALS['app']->Registry->NewKeyEx(
                    array('/last_update', $timestamp),
                    array('/version', JAWS_VERSION),
                    array('/config/admin_script', ''),
                    array('/config/frontend_ajaxed', 'true'),
                    array('/config/http_auth', 'false'),
                    array('/config/realm', 'Jaws Control Panel'),
                    array('/config/auth_method', 'DefaultAuthentication'),
                    array('/config/key', $uniqueKey),
                    array('/config/date_format', 'd MN Y'),
                    array('/config/calendar_type', 'Gregorian'),
                    array('/config/calendar_language', 'en'),
                    array('/config/timezone', '0'),
                    array('/config/cookie/path', '/'),
                    array('/config/cookie/version', '0.4'),
                    array('/config/cookie/session', 'false'),
                    array('/config/cookie/secure', 'false'),
                    array('/config/gzip_compression', 'false'),
                    array('/config/anon_register', 'false'),
                    array('/config/anon_repetitive_email', 'true'),
                    array('/config/anon_activation', 'user'),
                    array('/config/anon_type', '2'),
                    array('/config/anon_group', ''),
                    array('/config/use_gravatar', 'no'),
                    array('/config/gravatar_rating', 'G'),
                    array('/config/editor', 'TinyMCE'),
                    array('/config/browsers_flag', 'opera,firefox,ie7up,ie,safari,nav,konq,gecko,text'),
                    array('/config/allow_comments', 'true'),
                    array('/config/controlpanel_name', 'ControlPanel'),
                    array('/config/show_viewsite', (isset($_SESSION['install']['data']['Settings']['show_viewsite']) && $_SESSION['install']['data']['Settings']['show_viewsite'] == 'yes' ? 'true' : 'false')),
                    array('/config/site_url', ''),
                    array('/config/site_ssl_url', ''),
                    array('/config/cookie_precedence', 'false'),
                    array('/config/robots', implode(',', $robots)),
                    array('/config/connection_timeout', '5'),           // per second
                    array('/config/pageconst', (isset($_SESSION['install']['data']['WriteConfig']['pageconst']) ? (int)$_SESSION['install']['data']['WriteConfig']['pageconst'] : 12)),
                    array('/config/whmcs_url', ''),
                    array('/config/whmcs_user', ''),
                    array('/config/whmcs_password', ''),
					array('/policy/passwd_bad_count',         '7'),
                    array('/policy/passwd_lockedout_time',    '60'),    // per second
                    array('/policy/passwd_max_age',           '0'),     // per day  0 = resistant
                    array('/policy/passwd_min_length',        '0'),
                    array('/policy/passwd_complexity',        'no'),
                    array('/policy/xss_parsing_level',        'paranoid'),
                    array('/policy/session_idle_timeout',     '30'),    // per minute
                    array('/policy/session_remember_timeout', '720'),   // hours = 1 month
                    array('/gadgets/enabled_items', ''),
                    array('/gadgets/core_items', ''),
                    array('/gadgets/allowurl_items', ''),
                    array('/gadgets/autoload_items', ''),
                    array('/gadgets/plain_editor_items', ''),
					array('/gadgets/language_visitor_choices', 'CHINESE:zh,ENGLISH:en,FRENCH:fr,GERMAN:de,ITALIAN:it,RUSSIAN:ru,SPANISH:es'),
					array('/gadgets/user_access_items', ''),
					array('/gadgets/require_https', ''),
                    array('/plugins/parse_text/enabled_items', ''),
                    array('/network/ftp_enabled', 'false'),
                    array('/network/ftp_host', '127.0.0.1'),
                    array('/network/ftp_port', '21'),
                    array('/network/ftp_mode', 'passive'),
                    array('/network/ftp_user', ''),
                    array('/network/ftp_pass', ''),
                    array('/network/ftp_root', ''),
                    array('/network/proxy_enabled', 'false'),
                    array('/network/proxy_type', 'http'),
                    array('/network/proxy_host', ''),
                    array('/network/proxy_port', '80'),
                    array('/network/proxy_auth', 'false'),
                    array('/network/proxy_user', ''),
                    array('/network/proxy_pass', ''),
                    array('/network/mailer', 'phpmail'),
                    array('/network/site_email', ''),
                    array('/network/email_name', ''),
                    array('/network/smtp_vrfy', 'false'),
                    array('/network/sendmail_path', '/usr/sbin/sendmail'),
                    array('/network/sendmail_args', ''),
                    array('/network/smtp_host', '127.0.0.1'),
                    array('/network/smtp_port', '25'),
                    array('/network/smtp_auth', 'false'),
                    array('/network/pipelining', 'false'),
                    array('/network/smtp_user', ''),
                    array('/network/smtp_pass', ''),
                    array('/map/enabled', 'true'),
                    array('/map/use_file', 'true'),
                    array('/map/use_rewrite', 'false'),
                    array('/map/map_to_use', 'both'),
                    array('/map/custom_precedence', 'false'),
                    array('/map/extensions', 'html'),
                    array('/map/use_aliases', 'false'),
                    array('/crypt/enabled', isset($_SESSION['install']['secure'])? 'true' : 'false'),
                    array('/crypt/pub_key', isset($_SESSION['pub_key'])),
                    array('/crypt/pvt_key', isset($_SESSION['pvt_key'])),
                    array('/crypt/key_len', '128'),
                    array('/crypt/key_age', '86400'),
                    array('/crypt/key_start_date', isset($_SESSION['install']['secure'])? time() : '0')
        );
        if (Jaws_Error::isError($result)) {
            log_install($result->getMessage());
            // No return error for reinstall Jaws
            //return new Jaws_Error(_t('GLOBAL_ERROR_FAILED_ADDING_REGISTRY_KEY'), 'CORE');
        }

        //-- if user enterd incorrect authentication method, after reinstall we set it to default.
        $GLOBALS['app']->Registry->Set('/config/auth_method', 'DefaultAuthentication');

        // Commit the changes so they get saved
        $GLOBALS['app']->Registry->commit('core');

        // ACL keys
        $GLOBALS['app']->ACL->NewKey('/last_update', $timestamp);
        $GLOBALS['app']->ACL->NewKey('/priority',    'user, groups, default');

		$gadgetsList = array(
				'ControlPanel',
				'FileBrowser',
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
				'Menu',
				'CustomPage'
		);

		// Set initial enabled gadgets
		if (isset($_SESSION['install']['data']['Database']['gadgets'])) {
			$ncgadgets = explode(',', $_SESSION['install']['data']['Database']['gadgets']);
			foreach ($ncgadgets as $noncore) {
				if (!in_array($noncore, $gadgetsList)) {
					array_push($gadgetsList, $noncore);
				}
			}
		}

        require_once JAWS_PATH . 'include/Jaws/URLMapping.php';
        $GLOBALS['app']->Map = new Jaws_URLMapping();

        foreach ($gadgetsList as $gadget) {
			log_install("Installing gadget: ".$gadget);
            $result = Jaws_Gadget::EnableGadget($gadget);
            if (Jaws_Error::IsError($result)) {
                log_install($result->GetMessage());
                log_install("There was a problem installing gadget: ".$gadget);
                $core_ok = false;
                return new Jaws_Error(_t('INSTALL_DB_RESPONSE_GADGET_INSTALL', $gadget), 0, JAWS_ERROR_ERROR);
            }
        }

        if ($structure_ok && $core_ok) {
            return true;
        }

        log_install("There was a problem while setting up the database. Please contact jaws-dev@forge.novell.com for help.");
        return new Jaws_Error(_t('INSTALL_DB_RESPONSE_SETTINGS'), 0, JAWS_ERROR_ERROR);
    }
}
