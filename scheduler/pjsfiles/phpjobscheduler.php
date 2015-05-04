<?php
/**********************************************************
 *                phpJobScheduler                         *
 *           Author:  DWalker.co.uk                        *
 *    phpJobScheduler © Copyright 2003 DWalker.co.uk      *
 *              All rights reserved.                      *
 **********************************************************
 *        Launch Date:  Oct 2003                          *
 *     Version    Date              Comment               *
 *     1.0       14th Oct 2003      Original release      *
 *     3.0       Nov 2005       Released under GPL/GNU    *
 *     3.0       Nov 2005       Released under GPL/GNU    *
 *     3.1       June 2006       Fixed modify issues,     *
 *                               and other minor issues   *
 *     3.3       Dec 2006     removed bugs/improved code  *
 *     3.4       Nov 2007     AJAX, and improved script   *
 *                       include using CURL and fsockopen *
 *     3.5     Dec 2008    Improvements, including        *
 *   single fire, silent db connect, fire time in minutes *
 *  NOTES:                                                *
 *        Requires:  PHP and MySQL                        *
 **********************************************************/
 $app_name = "phpJobScheduler";
 $phpJobScheduler_version = "3.5";
// ---------------------------------------------------------
include_once("functions.php");
db_connect();
$time_and_window =  time() + TIME_WINDOW;
$query="select * from phpjobscheduler
        WHERE fire_time <= $time_and_window";
$result = mysql_query($query);
$scripts_to_run = array();
if (mysql_num_rows($result))  // check has got some
{
 $i = 0;
 while ($i < mysql_num_rows($result))
 {
  $id=mysql_result($result,$i, 'id');
  $scriptpath=mysql_result($result,$i, 'scriptpath');
  $time_interval=mysql_result($result,$i, 'time_interval');
  $fire_time=mysql_result($result,$i, 'fire_time');
  $time_last_fired=mysql_result($result,$i, 'time_last_fired');
  $run_only_once=mysql_result($result,$i, 'run_only_once');
  $fire_time_new = $fire_time + $time_interval;
  $scripts_to_run[$i]="$scriptpath";
  $query="UPDATE phpjobscheduler
          SET
           fire_time='$fire_time_new',
           time_last_fired='$fire_time'
          WHERE id='$id'";
  if($run_only_once) $query="DELETE from phpjobscheduler WHERE id='$id' ";
  mysql_query($query);
  $i++;
 }
}
// run the scheduled scripts
$log_date="";
$log_output="";
for ($i = 0; $i < count($scripts_to_run); $i++) fire_script($scripts_to_run[$i]);
db_close();
?>