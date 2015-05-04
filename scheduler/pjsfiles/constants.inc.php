<?php
  define('TIME_WINDOW', 3600);//denomination is in seconds, so 3600 = 60 minute time frame window

  define('ERROR_LOG', TRUE);// prints runs and errors to error log table

  define('LOCATION', dirname(__FILE__) ."/");// used to open local files

  define('PJS_TABLE','phpjobscheduler');// pjs table name
  define('LOGS_TABLE','phpjobscheduler_logs');// logs table name

  define('MAX_ERROR_LOG_LENGTH',100000);// maximum string length of output to record in error log table
?>
