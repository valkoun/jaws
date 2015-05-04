<?php
/**
 * MDB2 database abstraction. Supports many RDBMS (MySQL, MySQLi [PHP5 only], PostgreSQL, Oracle, Frontbase, Querysim, Interbase/Firebird [PHP5 only], MSSQL, SQLite).  
 *
 * @category   Database
 * @category   feature
 * @package    Core
 * @author     Helgi �ormar �orbj�rnsson <dufuz@php.net>
 * @autho      Ali Fazelzadeh <afz@php.net>
 * @copyright  2005-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */

ini_set("memory_limit","384M");
ini_set("post_max_size","25M");
ini_set("upload_max_filesize","2M");
ini_set("max_execution_time","300");

require_once 'MDB2.php';

class Jaws_DB
{
    var $dbc = null;

    /**
     * The DB prefix for tables
     *
     * @var    string
     * @access private
     */
    var $_prefix;

    /**
     * The DB driver we are using
     *
     * @var    string
     * @access private
     */
    var $_driver;

    /**
     * The DB charset
     *
     * @var    string
     * @access private
     */
    var $_charset;

    /**
     * This user is DB sdministrator?
     *
     * @var    boolean
     * @access private
     */
    var $_is_dba;

    /**
     * This DB path
     *
     * @var    string
     * @access private
     */
    var $_db_path;

    var $_dsn;

    function Jaws_DB($options)
    {
        $this->_dsn = array(
            'phptype'  => strtolower($options['driver']),
            'username' => $options['user'],
            'password' => $options['password'],
            'hostspec' => $options['host'],
            'database' => $options['name'],
        );

        //set charset
        $options['charset'] = isset($options['charset'])? $options['charset'] : $this->getUnicodeCharset();
        if (!empty($options['charset'])) {
            $this->_dsn['charset'] = $options['charset'];
        }

        if (!empty($options['port'])) {
            $this->_dsn['port'] = $options['port'];
        }

        $this->_db_path = isset($options['path'])? $options['path'] : '';
        $this->_is_dba  = $options['isdba'] == 'true' ? true : false;
        $this->_driver  = strtolower($options['driver']);
        $this->_prefix  = $options['prefix'];
        $this->_charset = $options['charset'];

        $this->connect();

        // Set Assoc as default fetch mode.
        $this->dbc->setFetchMode(MDB2_FETCHMODE_ASSOC);
    }

    function &getInstance($options)
    {
        static $instances;
        if (!isset($instances)) {
            $instances = array();
        }

        $signature = serialize(array('db'));
        if (!isset($instances[$signature])) {
            $instances[$signature] = new Jaws_DB($options);
        }

        return $instances[$signature];
    }

    function connect()
    {
        // connect to database
        if (!defined('DEBUG_ACTIVATED')) {
            define('DEBUG_ACTIVATED', false);
        }

        $options = array(
            'debug' => true,
            'debug_handler' => 'logQuery',
            'portability' => (MDB2_PORTABILITY_ALL ^ MDB2_PORTABILITY_EMPTY_TO_NULL ^ MDB2_PORTABILITY_FIX_CASE),
            'quote_identifier' => true,
        );

        switch ($this->_dsn['phptype']) {
            case 'ibase':
                $options['database_path'] = empty($this->_db_path)? JAWS_DATA : $this->_db_path;
                $options['portability'] = $options['portability'] | MDB2_PORTABILITY_FIX_CASE;
                break;

            case 'oci8':
                $options['emulate_database'] = false;
                $options['portability'] = $options['portability'] | MDB2_PORTABILITY_FIX_CASE;
                break;

            case 'sqlite':
                $options['database_path'] = empty($this->_db_path)? JAWS_DATA : $this->_db_path;
                break;

            case 'mssql':
                $options['multibyte_text_field_type'] = $this->Is_FreeTDS_MSSQL_Driver();
                break;
        }

        if ($this->_is_dba) {
            $options['DBA_username'] = $this->_dsn['username'];
            $options['DBA_password'] = $this->_dsn['password'];
        }

        $this->dbc =& MDB2::singleton($this->_dsn, $options);
        if (PEAR::IsError($this->dbc)) {
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_ERR, "[SQL Fatal Error]\n " . $this->dbc->getMessage() .
                                     ' : ' . $this->dbc->getUserInfo());
            }
            Jaws_Error::Fatal("Couldn\'t connect to the database<br />" .
                              $this->dbc->getMessage() . "<br />" . $this->dbc->getUserinfo(), __FILE__, __LINE__);
        }
    }

    /**
     * Get the driver name we are using
     *
     * @access  public
     * @return  string  DB Driver
     */
    function getDriver()
    {
        return $this->_driver;
    }

    /**
     * Get DB server version information
     *
     * @access  public
     * @param   boolean $native determines if the raw version string should be returned
     * @return  string  DB Driver
     */
    function getDBVersion($native = true)
    {
        $dbInfo = $this->dbc->getServerVersion($native);
        return PEAR::IsError($dbInfo)? '' : $dbInfo;
    }

    /**
     * Get the DB prefix name
     *
     * @access  public
     * @return  string  DB Prefix
     */
    function getPrefix()
    {
        return $prefix = $this->_prefix;
    }

    /**
     * Get the DB charset
     *
     * @access  public
     * @return  string  DB charset
     */
    function getCharset()
    {
        return $this->_charset;
    }

    /**
     * Execute a manipulation query to the database and return any the affected rows
     *
     * @param string $query the SQL query
     * @param array $params replace values in the query
     * @return mixed a result handle or MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function query($sql, $params = array())
    {
        $sql = $this->sqlParse($sql, $params);
        $result = $this->dbc->exec($sql);
        if (PEAR::IsError($result)) {
            return new Jaws_Error($result->getMessage() . ' : ' . $result->getUserInfo(), 'SQL', JAWS_ERROR_ERROR);
        }
		
		/*
		if (isset($GLOBALS['log'])) {
			$GLOBALS['log']->Log(JAWS_LOG_INFO, 'DBCache: '. var_export($GLOBALS['app']->_DBCache, true));
		}
		*/
		$GLOBALS['app']->_DBCache = array();

        return (string)$result;
    }

    /**
     * Execute the specified query, fetch the value from the first column of
     * the first row of the result set and then frees
     * the result set.
     *
     * @param string $query the SELECT query statement to be executed.
     * @param array $params replace values in the query
     * @param string $type optional argument that specifies the expected
     *       datatype of the result set field, so that an eventual conversion
     *       may be performed. The default datatype is text, meaning that no
     *       conversion is performed
     * @return mixed MDB2_OK or field value on success, a MDB2 error on failure
     * @access public
     */
    function queryOne($sql, $params = array(), $type = null)
    {
        $sql = $this->sqlParse($sql, $params);
		if (isset($GLOBALS['app']->_DBCache[md5(serialize(array($sql, $type)))])) {
			return $GLOBALS['app']->_DBCache[md5(serialize(array($sql, $type)))];
		}
        $result = $this->dbc->queryOne($sql, $type);
        if (PEAR::IsError($result)) {
            return new Jaws_Error($result->getMessage() . ' : ' . $result->getUserInfo(), 'SQL', JAWS_ERROR_ERROR);
        }

        if ($type === null) {
			$GLOBALS['app']->_DBCache[md5(serialize(array($sql, $type)))] = (string)$result;
            return (string)$result;
        }
		$GLOBALS['app']->_DBCache[md5(serialize(array($sql, $type)))] = $result;
        return $result;
    }

    /**
     * Execute the specified query, fetch the values from the first
     * row of the result set into an array and then frees
     * the result set.
     *
     * @param string $query the SELECT query statement to be executed.
     * @param array $params replace values in the query
     * @param array $types optional array argument that specifies a list of
     *       expected datatypes of the result set columns, so that the eventual
     *       conversions may be performed. The default list of datatypes is
     *       empty, meaning that no conversion is performed.
     * @param int $fetchmode how the array data should be indexed
     * @return mixed MDB2_OK or data array on success, a MDB2 error on failure
     * @access public
     */
    function queryRow($sql, $params = array(), $types = null, $fetchmode = MDB2_FETCHMODE_DEFAULT)
    {
		$sql = $this->sqlParse($sql, $params);
		if (isset($GLOBALS['app']->_DBCache[md5(serialize(array($sql,$types,$fetchmode)))])) {
			return $GLOBALS['app']->_DBCache[md5(serialize(array($sql,$types,$fetchmode)))];
		}
        $result = $this->dbc->queryRow($sql, $types, $fetchmode);
        if (PEAR::IsError($result)) {
            return new Jaws_Error($result->getMessage() . ' : ' . $result->getUserInfo(), 'SQL', JAWS_ERROR_ERROR);
        }
		$GLOBALS['app']->_DBCache[md5(serialize(array($sql,$types,$fetchmode)))] = (array)$result;
        return (array)$result;
    }

    /**
     * Execute the specified query, fetch all the rows of the result set into
     * a two dimensional array and then frees the result set.
     *
     * @param string $query the SELECT query statement to be executed.
     * @param array $params replace values in the query
     * @param array $types optional array argument that specifies a list of
     *       expected datatypes of the result set columns, so that the eventual
     *       conversions may be performed. The default list of datatypes is
     *       empty, meaning that no conversion is performed.
     * @param int $fetchmode how the array data should be indexed
     * @param boolean $rekey if set to true, the $all will have the first
     *       column as its first dimension
     * @param boolean $force_array used only when the query returns exactly
     *       two columns. If true, the values of the returned array will be
     *       one-element arrays instead of scalars.
     * @param boolean $group if true, the values of the returned array is
     *       wrapped in another array.  If the same key value(in the first
     *       column) repeats itself, the values will be appended to this array
     *       instead of overwriting the existing values.
     * @return mixed MDB2_OK or data array on success, a MDB2 error on failure
     * @access public
     */
    function queryAll($sql, $params = array(), $types = null, $fetchmode = MDB2_FETCHMODE_DEFAULT,
        $rekey = false, $force_array = false, $group = false)
    {
        $sql = $this->sqlParse($sql, $params);
		if (isset($GLOBALS['app']->_DBCache[md5(serialize(array($sql, $types, $fetchmode, $rekey, $force_array, $group)))])) {
			return $GLOBALS['app']->_DBCache[md5(serialize(array($sql, $types, $fetchmode, $rekey, $force_array, $group)))];
		}
        $result = $this->dbc->queryAll($sql, $types, $fetchmode, $rekey, $force_array, $group);
        if (PEAR::IsError($result)) {
            return new Jaws_Error($result->getMessage() . ' : ' . $result->getUserInfo(), 'SQL', JAWS_ERROR_ERROR);
        }
		$GLOBALS['app']->_DBCache[md5(serialize(array($sql, $types, $fetchmode, $rekey, $force_array, $group)))] = (array)$result;

        return (array)$result;
    }

    /**
     * Execute the specified query, fetch the value from the first column of
     * each row of the result set into an array and then frees the result set.
     *
     * @param string $query the SELECT query statement to be executed.
     * @param array $params replace values in the query
     * @param string $type optional argument that specifies the expected
     *       datatype of the result set field, so that an eventual conversion
     *       may be performed. The default datatype is text, meaning that no
     *       conversion is performed
     * @param int $colnum the row number to fetch
     * @return mixed MDB2_OK or data array on success, a MDB2 error on failure
     * @access public
     */
    function queryCol($sql, $params = array(), $type = null, $colnum = 0)
    {
        $sql = $this->sqlParse($sql, $params);
		if (isset($GLOBALS['app']->_DBCache[md5(serialize(array($sql, $type, $colnum)))])) {
			return $GLOBALS['app']->_DBCache[md5(serialize(array($sql, $type, $colnum)))];
		}
        $result = $this->dbc->queryCol($sql, $type, $colnum);
        if (PEAR::IsError($result)) {
            return new Jaws_Error($result->getMessage() . ' : ' . $result->getUserInfo(), 'SQL', JAWS_ERROR_ERROR);
        }
		$GLOBALS['app']->_DBCache[md5(serialize(array($sql, $type, $colnum)))] = (array)$result;
        return (array)$result;
    }

    /**
     * returns the DB unicode charset
     *
     * @return string
     * @access public
     */
    function getUnicodeCharset()
    {
        switch ($this->_dsn['phptype']) {
            case 'mysql':
            case 'mysqli':
                return 'utf8';
            case 'pgsql':
                return 'UNICODE';
            case 'oci8':
                return 'UTF8';
            case 'sqlsrv':
                return 'UTF-8';
            default:
                return '';
        }
    }

    /**
     * returns the autoincrement ID if supported or $id
     *
     * @param mixed $id value as returned by getBeforeId()
     * @param string $table name of the table into which a new row was inserted
     * @return mixed MDB2 Error Object or id
     * @access public
     */
    function lastInsertID($table = null, $field = null)
    {
        return $this->dbc->lastInsertID($this->getPrefix() . $table, $field);
    }

    /**
     * returns the next free id of a sequence if the RDBMS
     * does not support auto increment
     *
     * @param string name of the table into which a new row was inserted
     * @param string name of the field that the sequence belongs to
     * @param bool when true the sequence is automatic created, if it not exists
     * @param bool if the returned value should be quoted
     *
     * @return mixed MDB2 Error Object or id
     * @access public
     */
    function getBeforeId($table, $field, $ondemend = true, $quote = true)
    {
        if ($this->dbc->supports('auto_increment') !== true) {
            $table = $this->getPrefix() . $table;
            $seq = $table . '_' . $field;
            $id = $this->dbc->nextID($seq, $ondemend);
            if (!$quote || PEAR::isError($id)) {
                return $id;
            }
            return $this->dbc->quote($id, 'integer');
        } elseif (!$quote) {
            return null;
        }

        if ($this->_dsn['phptype'] == 'pgsql') {
            return 'DEFAULT';
        }

        return 'NULL';
    }

    /**
     * returns the autoincrement ID if supported or $id
     *
     * @param string name of the table into which a new row was inserted
     * @param string name of the field into which a new row was inserted
     *
     * @return mixed MDB2 Error Object or id
     * @access public
     */
    function getAfterId($table, $field = null)
    {
        if ($this->dbc->supports('auto_increment') === false) {
            return null;
        }
        return $this->lastInsertID($table, $field);
    }

    /**
     * gives you a dump of the table
     *
     * @param $type the type of data/structure you want to dump
     *              allowed options are 'all', 'structure' and 'content'
     */
    function Dump($file, $type = '')
    {
        set_time_limit(0);
        require_once 'MDB2/Schema.php';

        $dsn = $this->_dsn;

        $options = array(
            'debug' => DEBUG_ACTIVATED,
            'log_line_break' => '<br />',
            'portability' => (MDB2_PORTABILITY_ALL ^ MDB2_PORTABILITY_EMPTY_TO_NULL ^ MDB2_PORTABILITY_FIX_CASE),
            'quote_identifier' => true
        );

        $schema =& MDB2_Schema::factory($dsn, $options);
        if (PEAR::IsError($schema)) {
            return $schema->getMessage();
        }

        switch ($type) {
            case 'structure':
                $dump_what = MDB2_SCHEMA_DUMP_STRUCTURE;
                break;
            case 'content':
                $dump_what = MDB2_SCHEMA_DUMP_CONTENT;
                break;
            default:
                $dump_what = MDB2_SCHEMA_DUMP_ALL;
                break;
        }

        $config = array(
            'output_mode' => 'file',
            'output' => $file
        );

        $DBDef = $schema->getDefinitionFromDatabase();
        if (PEAR::isError($DBDef)) {
            return $DBDef->getMessage();
        }

        $res = $schema->dumpDatabase($DBDef, $config, $dump_what);
        if (PEAR::isError($res)) {
            return $res->getMessage();
        }

        return $file;
    }

    /**
     * set the range of the next query
     *
     * @param string $limit number of rows to select
     * @param string $offset first row to select
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function setLimit($limit, $offset = null)
    {
        $result = $this->dbc->setLimit((int)$limit, $offset);
        if (PEAR::IsError($result)) {
            return new Jaws_Error($result->getMessage() . ' : ' . $result->getUserInfo(), 'SQL', JAWS_ERROR_ERROR);
        }

        return $result;
    }

    /**
     * Quote wrapper for MDB2-Jaws
     *
     * @access  public
     * @param   string  $value   Value to quote
     * @param   string  $type    Value type (integer, text, boolean)
     * @return  string  Quoted value
     */
    function quote($value, $type = 'text')
    {
        if (in_array($type, array('text', 'integer', 'value'))) {
            return $this->dbc->quote($value, $type);
        }
        return $value;
    }

    /**
     * drop an existing table via MDB2 management module
     *
     * @access  public
     * @param   string  $table  name of table
     * @return  mixed   MDB2_OK on success, a MDB2 error on failure
     */
    function dropTable($table)
    {
        $this->dbc->loadModule('Manager');
        $result = $this->dbc->manager->dropTable($this->getPrefix() . $table);
        return $result;
    }

    /**
     * Executes a query
     *
     * @access public
     * @param  string  SQL To Execute
     * @param  array   Array that has the params to replace
     * @return string  parsed sql string with [[table]] and {field} replaced
     */
    function sqlParse($sql, $params = null)
    {
        if (!preg_match('#\[\[\w+]\]#si', $sql) && isset($GLOBALS['log'])) {
            $GLOBALS['log']->log(JAWS_LOG_DEBUG, 'Query: '.$sql.' needs to have its table names inside brackets([[table]])');
        }
        $sql = preg_replace('@\[\[(.*?)\]\]@sm', $this->dbc->quoteIdentifier($this->GetPrefix() .'\\1'), $sql);
        $sql = preg_replace('@\[(.*?)\]@sm', $this->dbc->quoteIdentifier('\\1'), $sql);

        if (is_array($params)) {
            foreach ($params as $key => $param) {
                if (is_array($param)) {
                    $value = $param['value'];
                    $type  = $param['type'];
                } else {
                    $value = $param;
                    $type  = null;
                }

                //Add "N" character before text field value,
                //when using FreeTDS as MSSQL driver, to supporting unicode text
                if ($this->_dsn['phptype'] == 'mssql' && is_string($value) && $this->Is_FreeTDS_MSSQL_Driver()) {
                    $value = 'N' . $this->dbc->quote($value, $type);
                } else {
                    $value = $this->dbc->quote($value, $type);
                }

                $sql = str_replace('{'.$key.'}', $value, $sql);
            }
        }

        return $sql;
    }

    /**
     *
     *
     * @access public
     */
    function installSchema($file, $variables = array(), $file_update = false, $data = false, $create = true, $debug = false)
    {
        MDB2::loadFile('Schema');

        $dsn = $this->_dsn;
        unset($dsn['database']);

        // If the database should be created
        $variables['create'] = (int)$create;
        // The database name
        $variables['database'] = $this->_dsn['database'];
        // Prefix of all the tables added
        $variables['table_prefix'] = $this->getPrefix();
        // set default charset
        if (!isset($variables['charset'])) {
            $variables['charset'] = $this->getUnicodeCharset();
        }

        $options = array(
            'debug' => $debug,
            'log_line_break' => '<br />',
            'portability' => (MDB2_PORTABILITY_ALL ^ MDB2_PORTABILITY_EMPTY_TO_NULL ^ MDB2_PORTABILITY_FIX_CASE),
            'quote_identifier' => true,
            'force_defaults' => false,
            //'dtd_file' => '',
        );

        switch ($this->_dsn['phptype']) {
            case 'ibase':
                $options['portability'] = $options['portability'] | MDB2_PORTABILITY_FIX_CASE;
                $options['database_path'] = empty($this->_db_path)? JAWS_DATA : $this->_db_path;
                break;

            case 'oci8':
                $options['emulate_database'] = false;
                $options['portability'] = $options['portability'] | MDB2_PORTABILITY_FIX_CASE;
                break;

            case 'sqlite':
                $options['database_path'] = empty($this->_db_path)? JAWS_DATA : $this->_db_path;
                break;

            case 'mssql':
                $options['multibyte_text_field_type'] = $this->Is_FreeTDS_MSSQL_Driver();
                break;
        }

        if ($this->_is_dba) {
            $options['DBA_username'] = $this->_dsn['username'];
            $options['DBA_password'] = $this->_dsn['password'];
        }

        if (!isset($this->schema)) {
            $this->schema =& MDB2_Schema::factory($this->dbc, $options);;
            if (PEAR::IsError($this->schema)) {
                return $this->schema;
            }
        }

        $method = $data === true ? 'writeInitialization' : 'updateDatabase';
        $result = $this->schema->$method($file, $file_update, $variables);

        if ($debug) {
            $debug = $this->schema->getOption('debug');
            if ($debug && !PEAR::IsError($debug)) {
                echo('Debug messages<br />');
                echo($this->schema->db->getDebugOutput() . '<br />');
            }
        }

        if (PEAR::isError($result)) {
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_DEBUG, 'Schema: ' . $result->getMessage());
            }

            $this->schema->disconnect();
            return new Jaws_Error($result->getMessage() . ' : ' . $result->getUserInfo(), 'SQL Schema', JAWS_ERROR_ERROR);
        }

        return $result;
    }

    /**
     * return the current datetime
     *
     * @return string current datetime in the MDB2 format
     * @access public
     */
    function Date($timestamp = '')
    {
        return empty($timestamp)? gmdate('Y-m-d H:i:s') : date('Y-m-d H:i:s', (int)$timestamp);
        //return empty($timestamp)? gmdate('Y-m-d H:i:s') : gmdate('Y-m-d H:i:s', (int)$timestamp);
    }

    /**
     * Detect mssql driver is FreeTDS
     *
     * @access public
     * @return boolean
     */
    function Is_FreeTDS_MSSQL_Driver()
    {
        static $freeTDS;
        if (!isset($freeTDS)) {
            ob_start();
            @phpinfo(INFO_MODULES);
            $info = ob_get_contents();
            ob_end_clean();
            $freeTDS = stripos($info, 'FreeTDS') !== false;
        }

        return $freeTDS;
    }

}

/**
 * Debug function.
 *
 */
function logQuery(&$db, $scope, $message)
{
    if (isset($GLOBALS['log'])) {
        $GLOBALS['log']->Log(JAWS_LOG_DEBUG, $scope.': '.$message);
		/*
		$d = debug_backtrace();
		var_dump($d);
		$b = '';
		for ($i = 0; $i < count($d); $i++) {
			$b .= (empty($b) ? 'Location called by: '."\n" : ', '."\n").$d[$i]["file"].': '.$d[$i]["function"];
		}
		$GLOBALS['log']->Log(JAWS_LOG_DEBUG, $b); 
		*/
    }
}
