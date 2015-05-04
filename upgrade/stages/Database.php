<?php
/**
 * Database Stage
 *
 * @category   Application
 * @package    UpgradeStage
 * @author     Jon Wood <jon@substance-it.co.uk>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2005-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class Upgrader_Database extends JawsUpgraderStage
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
     * Constructor
     *
     * @param array The database configuration
     */
    function Upgrader_Database($db_config)
    {
        $this->_Defaults['host']   = $db_config['host'];
        $this->_Defaults['driver'] = $db_config['driver'];
        $this->_Defaults['user']   = $db_config['user'];
        $this->_Defaults['isdba']  = isset($db_config['isdba'])? $db_config['isdba'] : 'false';;
        $this->_Defaults['path']   = isset($db_config['path']) ? $db_config['path']  : '';
        $this->_Defaults['name']   = $db_config['name'];
        $this->_Defaults['prefix'] = $db_config['prefix'];
        $this->_Defaults['port']   = $db_config['port'];
    }

    /**
     * Builds the upgrader page.
     *
     * @access  public
     * @return  string      A block of valid XHTML to display an introduction and form.
     */
    function Display()
    {
        // Get values
        $values = $this->_Defaults;
        foreach ($this->_Defaults as $name => $value) {
            if (isset($_SESSION['upgrade']['Database'][$name])) {
                $values[$name] = $_SESSION['upgrade']['Database'][$name];
            }
        }

        $data = array();
        if (isset($_SESSION['upgrade']['data']['Database'])) {
            $data = $_SESSION['upgrade']['data']['Database'];
        }

        $tpl = new Jaws_Template(UPGRADE_PATH . 'stages/Database/templates/');
        $tpl->Load('display.html', false, false);
        $tpl->SetBlock('Database');

        $tpl->setVariable('db_info',   _t('UPGRADE_DB_INFO'));
        $tpl->SetVariable('next',      _t('GLOBAL_NEXT'));

        if ($_SESSION['upgrade']['secure']) {
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
            $tpl->setVariable('lbl_host',  _t('UPGRADE_DB_HOST'));
            $tpl->setVariable('host_info', _t('UPGRADE_DB_HOST_INFO', 'localhost'));
            $tpl->SetVariable('host', $values['host']);
            $tpl->ParseBlock('Database/host');
        }

        if (!isset($data['user'])) {
            $fields++;
            $tpl->SetBlock('Database/user');
            $tpl->setVariable('lbl_user',    _t('UPGRADE_DB_USER'));
            $tpl->setVariable('is_db_admin', _t('UPGRADE_DB_IS_ADMIN'));
            $tpl->SetVariable('user', $values['user']);
            $tpl->SetVariable('isdba_checked', (empty($values['isdba']) || $values['isdba'] == 'false')? '' : 'checked="checked"');
            $tpl->ParseBlock('Database/user');
        }

        if (!isset($data['password'])) {
            $fields++;
            $tpl->SetBlock('Database/password');
            $tpl->setVariable('lbl_pass', _t('UPGRADE_DB_PASS'));
            $tpl->SetVariable('dbpass', '');
            $tpl->ParseBlock('Database/password');
        }

        if (!isset($data['name'])) {
            $fields++;
            $tpl->SetBlock('Database/name');
            $tpl->setVariable('lbl_db_name',  _t('UPGRADE_DB_NAME'));
            $tpl->SetVariable('name', $values['name']);
            $tpl->ParseBlock('Database/name');
        }

        if (!isset($data['path'])) {
            $fields++;
            $tpl->SetBlock('Database/path');
            $tpl->setVariable('lbl_db_path', _t('UPGRADE_DB_PATH'));
            $tpl->setVariable('path_info',   _t('UPGRADE_DB_PATH_INFO'));
            $tpl->SetVariable('path', $values['path']);
            $tpl->ParseBlock('Database/path');
        }

        if (!isset($data['port'])) {
            $fields++;
            $tpl->SetBlock('Database/port');
            $tpl->setVariable('lbl_port',  _t('UPGRADE_DB_PORT'));
            $tpl->setVariable('port_info', _t('UPGRADE_DB_PORT_INFO'));
            $tpl->SetVariable('port', $values['port']);
            $tpl->ParseBlock('Database/port');
        }

        if (!isset($data['prefix'])) {
            $fields++;
            $tpl->SetBlock('Database/prefix');
            $tpl->setVariable('lbl_prefix',  _t('UPGRADE_DB_PREFIX'));
            $tpl->setVariable('prefix_info', _t('UPGRADE_DB_PREFIX_INFO'));
            $tpl->SetVariable('prefix', $values['prefix']);
            $tpl->ParseBlock('Database/prefix');
        }

        // drivers
        if (!isset($data['driver'])) {
            $fields++;
            $tpl->SetBlock('Database/drivers');
            $tpl->setVariable('lbl_driver', _t('UPGRADE_DB_DRIVER'));

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
                log_upgrade("Checking if ".$driver_info['title']. "(".$driver_info['ext'].") driver is available");
                if (!in_array($driver_info['ext'], $modules)) {
                    $available = false;
                    //However... mssql support exists in some Linux distros with the sybase package
                    if ($driver_info['ext'] == 'mssql' && function_exists('mssql_connect')) {
                        $available = true;
                    }
                    
                    if ($available === false) {
                        log_upgrade("Driver ".$driver_info['title']. "(".$driver_info['ext'].") is NOT available");
                        continue;
                    }
                }
                log_upgrade("Driver ".$driver_info['title']. "(".$driver_info['ext'].") is available");
                $tpl->setBlock('Database/drivers/driver');
                $tpl->setVariable('d_name', $driver);
                $tpl->setVariable('d_realname', $driver_info['title']);
                if (!empty($values['driver']) && $values['driver'] == $driver) {
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
            $_SESSION['upgrade']['Database']['skip'] = '1';
           header('Location: index.php');
        }

        $tpl->ParseBlock('Database');

        return $tpl->Get();
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
        if (isset($_SESSION['upgrade']['data']['Database'])) {
            $post = $_SESSION['upgrade']['data']['Database'] + $post;
        }

        if (isset($post['path']) && $post['path'] !== '' && !is_dir($post['path'])) {
            log_upgrade("The database path must be exists");
            return new Jaws_Error(_t('UPGRADE_DB_RESPONSE_PATH'), 0, JAWS_ERROR_WARNING);
        }

        if (isset($post['port']) && $post['port'] !== '' && !is_numeric($post['port'])) {
            log_upgrade("The port can only be a numeric value");
            return new Jaws_Error(_t('UPGRADE_DB_RESPONSE_PORT'), 0, JAWS_ERROR_WARNING);
        }

        if (!empty($post['host']) && !empty($post['user']) && !empty($post['name'])) {
            return true;
        }

        log_upgrade("You must fill in all the fields apart from table prefix and port");
        return new Jaws_Error(_t('UPGRADE_DB_RESPONSE_INCOMPLETE'), 0, JAWS_ERROR_WARNING);
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

        if (isset($_SESSION['upgrade']['data']['Database'])) {
            $post = $_SESSION['upgrade']['data']['Database'] + $post;
        }

        if ($_SESSION['upgrade']['secure']) {
            require_once JAWS_PATH . 'include/Jaws/Crypt.php';
            $JCrypt = new Jaws_Crypt();
            $pvt_key = Crypt_RSA_Key::fromString($_SESSION['pvt_key'], $JCrypt->wrapper);
            $post['dbpass'] = $JCrypt->rsa->decryptBinary($JCrypt->math->int2bin($post['dbpass']), $pvt_key);
            if (Jaws_Error::isError($post['dbpass'])) {
                log_upgrade($post['dbpass']->getMessage());
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

        $_SESSION['upgrade']['Database'] = array(
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
        $GLOBALS['db'] = new Jaws_DB($_SESSION['upgrade']['Database']);
        if (Jaws_Error::IsError($GLOBALS['db'])) {
            log_upgrade("There was a problem connecting to the database, please check the details and try again");
            return new Jaws_Error(_t('UPGRADE_DB_RESPONSE_CONNECT_FAILED'), 0, JAWS_ERROR_WARNING);
        }

        log_upgrade("Checking current database");
        $sql = "SELECT * FROM [[registry]]";
        $result = $GLOBALS['db']->queryRow($sql);
        if (Jaws_Error::isError($result)) {
            log_upgrade("Something wrong happened while checking the current database, error is:");
            log_upgrade($result->getMessage());
            return new Jaws_Error($result->getMessage(), 0, JAWS_ERROR_ERROR);
        }
        log_upgrade("Connected to ".$_SESSION['upgrade']['Database']['driver']." database driver successfully.");

        // Create application
        include_once JAWS_PATH . 'include/Jaws.php';
        $GLOBALS['app'] = new Jaws();
        $GLOBALS['app']->create();
        $GLOBALS['app']->OverwriteDefaults(array('language' => $_SESSION['upgrade']['language']));
        $GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');
        include_once JAWS_PATH . 'include/Jaws/Version.php';

        // Input datas
        $timestamp = $GLOBALS['db']->Date();

        //registry keys
        $GLOBALS['app']->Registry->Set('/last_update', $timestamp);

        // Commit the changes so they get saved
        $GLOBALS['app']->Registry->commit('core');

        require_once JAWS_PATH . 'include/Jaws/URLMapping.php';
        $GLOBALS['app']->Map = new Jaws_URLMapping();

        $gadgets = array('ControlPanel', 'Layout', 'UrlMapper', 'Users');
        foreach ($gadgets as $gadget) {
            if (Jaws_Gadget::IsGadgetInstalled($gadget)) {
				$result = Jaws_Gadget::UpdateGadget($gadget);
				if (Jaws_Error::IsError($result)) {
					log_upgrade("There was a problem upgrading gadget [".$gadget."]: ".var_export($result, true));
					return new Jaws_Error(_t('UPGRADE_VER_RESPONSE_GADGET_FAILED', $gadget), 0, JAWS_ERROR_ERROR);
				}
			}
        }

		$jms = $GLOBALS['app']->LoadGadget('Jms', 'AdminModel');
		$urlmapping = $GLOBALS['app']->LoadGadget('UrlMapper', 'AdminModel');
		$gadget_list = $jms->GetGadgetsList(null, true, true, null);
		
		//Hold.. if we dont have a selected gadget?.. like no gadgets?
		if (count($gadget_list) <= 0) {
			return false;
		} else {
	
			reset($gadget_list);
			
			foreach ($gadget_list as $gadget) {
				$urlmapping->UpdateGadgetMaps($gadget['realname']);
			}
		}
		
        log_upgrade("Cleaning previous maps cache data files - step 0.8.13->0.8.14");
        //Make sure user don't have any data/maps stuff
        $path = JAWS_DATA . 'maps';
        if (!Jaws_Utils::Delete($path, false)) {
            log_upgrade("Can't delete $path");
        }

        log_upgrade("Cleaning previous registry cache data files - step 0.8.13->0.8.14");
        //Make sure user don't have any data/cache/registry stuff
        $path = JAWS_DATA . 'cache/registry';
        if (!Jaws_Utils::Delete($path, false)) {
            log_upgrade("Can't delete $path");
        }

        log_upgrade("Cleaning previous acl cache data files - step 0.8.13->0.8.14");
        //Make sure user don't have any data/cache/acl stuff
        $path = JAWS_DATA . 'cache/acl';
        if (!Jaws_Utils::Delete($path, false)) {
            log_upgrade("Can't delete $path");
        }

        return true;
    }
}