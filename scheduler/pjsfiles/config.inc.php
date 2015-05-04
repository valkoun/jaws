<?php
// ---------------------------------------------------------
 $app_name = "phpJobScheduler";
 $phpJobScheduler_version = "3.5";
// ---------------------------------------------------------

if (BASE_SCRIPT == 'scheduler.php') {
	$dbhost = $db->_dsn['hostspec'];
	$dbname = $db->_dsn['database'];
	$dbuser = $db->_dsn['username'];
	$dbpass = $db->_dsn['password'];
	$dbprefix = $db->_prefix;
} else {
	$dbhost = $db['host'];
	$dbname = $db['name'];
	$dbuser = $db['user'];
	$dbpass = $db['password'];
	$dbprefix = $db['prefix'];
}

define('DBHOST', $dbhost);// MySQL host address - localhost is usually fine
define('DBNAME', $dbname);// MySQL database name - must already exist
define('DBUSER', $dbuser);// MySQL username - must already exist
define('DBPASS', $dbpass);// MySQL password for above username
define('DBPREFIX', $dbprefix);// MySQL password for above username
?>