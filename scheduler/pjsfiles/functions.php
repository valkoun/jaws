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
 include_once("config.inc.php");
 include_once("constants.inc.php");
 $app_name = "phpJobScheduler";
 $phpJobScheduler_version = "3.5";
// ---------------------------------------------------------
if (DBNAME=="-")//not configured
{
 header("Location: scheduler/readme.html");
 exit;
}

if (!function_exists('clean_input')) // check to see if function is not already defined by another application
{
 function clean_input($string)
 {
  $patterns[0] = "/'/";
  $patterns[1] = "/\"/";
  $string = preg_replace($patterns,'',$string);
  $string = trim($string);
  $string = stripslashes($string);
  return preg_replace("/[<>]/", '_', $string);
 }
}

function update_db()
{
 db_connect();
 if(mysql_num_rows(mysql_query("SHOW TABLES LIKE '".LOGS_TABLE."'"))==0)
  {
   $q_create_table = "
    CREATE TABLE ".LOGS_TABLE." (
    id int(11) NOT NULL,
    script varchar(128) default NULL,
    output text default NULL,
    execution_time varchar(60) default NULL,
    PRIMARY KEY (id)
    ) TYPE=MyISAM;";
   $result=mysql_query($q_create_table);
  }
 if(mysql_num_rows(mysql_query("SHOW TABLES LIKE '".PJS_TABLE."'"))==0)
 {
  $main_table="CREATE TABLE ".PJS_TABLE." (
  id int(11) NOT NULL auto_increment,
  scriptpath varchar(255) default NULL,
  name varchar(128) default NULL,
  time_interval int(11) default NULL,
  fire_time int(11) NOT NULL default '0',
  time_last_fired int(11) default NULL,
  run_only_once tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (id),
  KEY fire_time (fire_time)) TYPE=MyISAM";
  $result=mysql_query($main_table);
 }
 $result=mysql_query("select scriptpath from ".PJS_TABLE);
 if (mysql_field_len($result, 0)<255) $result=mysql_query("ALTER TABLE ".PJS_TABLE." CHANGE scriptpath scriptpath VARCHAR(255)");
 $result=mysql_query("SHOW COLUMNS FROM ".PJS_TABLE." LIKE 'run_only_once' ");
 if (!mysql_num_rows($result)) $result=mysql_query("ALTER TABLE ".PJS_TABLE." ADD run_only_once tinyint(1) NOT NULL DEFAULT '0'");
 db_close();
}


function time_unit($time_interval)
{
 global $app_name;
 $unit = array(0, 'type');
 //check if its minutes
 if ($time_interval <= (59 * 60))
 {
  $unit[0]=$time_interval/60;
  $unit[1]="<font color=\"#000000\">minute(s)</font>";
 }
 //check if its hours
 if ( ($time_interval > (59 * 60)) AND ($time_interval<= (23 * 3600)) )
 {
  $unit[0]=$time_interval/3600;
  $unit[1]="<font color=\"#ff0000\">hour(s)</font>";
 }
  // check if its days
 if ( ($time_interval > (23 * 3600)) AND ($time_interval <= (6 * 86400)) )
 {
  $unit[0]=$time_interval/86400;
  $unit[1]="<font color=\"#FF8000\">day(s)</font>";
 }
 if ($time_interval >(6 * 86400))
 {
  $unit[0]=$time_interval/604800;
  $unit[1]="<font color=\"#C00000\">week(s)</font>";
 }
 $thedomain = $_SERVER['HTTP_HOST'];
 return $unit;
}

function db_connect()
{
 @$db_link = mysql_connect(DBHOST, DBUSER, DBPASS);
 if ($db_link) @mysql_select_db(DBNAME);
 if (mysql_error())
 {
  if (SHOW_MYSQL_ERRORS) echo "MySQL error: ". mysql_error(). " MySQL error no: ".mysql_errno();
  exit;
 }
 return $db_link;
}

function db_close()
{
 global $db_link;
 if ($db_link) $result = mysql_close($db_link);
}

function js_msg($msg)
{
 echo "<script language=\"JavaScript\"><!--\n alert(\"$msg\");\n// --></script>";
}

function save_log($script,$output,$execution_time)
{
 $now = time();
 $script=clean_input($script);
 $output=htmlentities($output);
 $i_query="INSERT INTO ".LOGS_TABLE." (id, script, output, execution_time)
               VALUES ('$now', '$script','$output','$execution_time');";
 $result=mysql_query($i_query);
}

function show_jobs()
{
 db_connect();
 $query="select * from ".PJS_TABLE;
 $result = mysql_query($query);
 if (!$result) js_msg("There has been an error: ".mysql_error() );
 else
 {
 if (mysql_num_rows($result))  // check has got some
 {
  $i = 0;
  $table_rows="";
  $bg_colour="#FFFFFF";
  while ($i < mysql_num_rows($result))
  {
   $id=mysql_result($result,$i, 'id');
   $scriptpath=mysql_result($result,$i, 'scriptpath');
   $name=mysql_result($result,$i, 'name');
   $time_interval=mysql_result($result,$i, 'time_interval');
   $fire_time=mysql_result($result,$i, 'fire_time');
   $time_last_fired=mysql_result($result,$i, 'time_last_fired');
   $run_only_once_txt= (mysql_result($result,$i, 'run_only_once'))? "<i><font color=\"#ff0000\"> Will run just once</font></i>":"";
   $time_interval = time_unit($time_interval);
   if ($time_last_fired==0)
   {
    $last_fire_hours = "<font color=\"#FF8000\">NOT yet fired</font>";
    $last_fire_date = "";
   }
   else
   {
    $last_fire_hours = strftime("%H:%M:%S ",$time_last_fired);
    $last_fire_date = strftime("on<br> %b %d, %Y",$time_last_fired);
   }
   $fire_hours = strftime("%H:%M:%S ",$fire_time);
   $fire_date = strftime("%b %d, %Y",$fire_time);
   if ($bg_colour=="#E9E9E9") $bg_colour="#FFFFFF"; else $bg_colour="#E9E9E9";
   $table_rows.="
      <tr align=\"center\">
      <th align=\"left\" bgcolor=\"$bg_colour\">
      <div id=\"pjs$id\">
        <small><font color=\"#008000\">&quot;$name&quot;</font> - <a
        href=\"javascript:modify($id);\">MODIFY</a> -
        <a href=\"javascript:deletepjs('".PJS_TABLE."',$id,'$name');\">DELETE?</a> <small>$run_only_once_txt<br>
        Script path: <font color=\"#000000\">$scriptpath</font></small></small>
      </div>
      </th>
     <th align=\"center\" bgcolor=\"$bg_colour\"><small>
      <div id=\"pjs$id\">
        $last_fire_hours $last_fire_date
      </div>
      </small></th>
      <th align=\"center\" bgcolor=\"$bg_colour\"><small>
      <div id=\"pjs$id\">
        $fire_hours on<br> $fire_date
      </div>
      </small></th>
       <th align=\"center\" bgcolor=\"$bg_colour\"><small>
      <div id=\"pjs$id\">
       $time_interval[0] $time_interval[1]
      </div>
      </small></th>
      </tr>";
   $i++;
  }
 }
 else $table_rows="<b><font color=\"#FF0000\">NO Jobs saved - to add a NEW scheduled job click the Add NEW schedule link above.</font></b><br><br>";
 }
 db_close();
 echo $table_rows;
}

function show_logs($qstart)
{
 db_connect();
 $num=5;// logs to display
 $next_logs=$num+$qstart;
 $query="select * from ".LOGS_TABLE." ORDER BY id DESC LIMIT $qstart, $num";
 $result = mysql_query($query);
 if (!$result) js_msg("There has been an error: ".mysql_error() );
 else
 {
  if (mysql_num_rows($result))  // check has got some
  {
  $i = 0;
  $table_rows="";
  $bg_colour="#FFFFFF";
  while ($i < mysql_num_rows($result))
  {
   $id=mysql_result($result,$i, 'id');
   $script=mysql_result($result,$i, 'script');
   $output= mysql_result($result,$i, 'output') ;
   $execution_time= mysql_result($result,$i,'execution_time');
   $log_date=strftime("Date: %d %b %Y  Time: %H:%M:%S",$id);
   if ($bg_colour=="#E9E9E9") $bg_colour="#FFFFFF"; else $bg_colour="#E9E9E9";
   if ($output!="") $show_hide="<a href=\"javascript:show_hide('$id');\">Show/Hide</a>";
   else $show_hide="NO data";
   $table_rows.="
     <tr align=\"center\">
      <th align=\"left\" bgcolor=\"$bg_colour\">
      <div id=\"pjs$id\">
        <small>Script: <font color=\"#000000\">$script</font>
            <br>Execution time: <font color=\"#000000\">$execution_time</font>
        <br>Output: <font color=\"#FF8000\">*</font>
         $show_hide
         <div id=\"$id\" style=\"display:none;background-color:#FFE6E6;color:#FF0000\">
          <blockquote>$output</blockquote>
         </div>
        </small></small>
      </div>
     </th>
     <th align=\"center\" bgcolor=\"$bg_colour\">
      <small><div id=\"pjs$id\">$log_date <br>
       <a href=\"javascript:deletepjs('".LOGS_TABLE."',$id,'$script');\">DELETE?</a>
       <br></small></div>
     </th>
    </tr>";
   $i++;
  }
  $qend=$i+$qstart;
  db_close();
  echo "$table_rows </table></center></div></form> <center><strong><big>
      Currently displaying most recent logs from $qstart to $qend<br></big></strong>";
  $next_link="<strong><big><a href=\"scheduler.php?action=error-logs?start=$next_logs\">Show Next $num logs &gt;&gt;</a>
             </big></strong><br><br><br>";
  if ($num==$i) echo $next_link;
  echo '<p align="center"><font color="#FF8000">* Maximum length of output will be
       <strong>'.MAX_ERROR_LOG_LENGTH.' characters</strong>. </font>To change this
       <a href="scheduler/readme.html#error_log">please see the readme file</a><br>';

 }
 else echo "<b><center><font color=\"#FF0000\">NO logs.</font>";
 }
}

function fire_script($script,$buffer_output=1)
{
 if(!session_id()) session_start();
 $md5_script=md5($script);
 if (!isset($_SESSION[$md5_script]))//check script is not already running
 {
      $_SESSION[$md5_script]="currently-running";
      $start_time = microtime(true);
      if($buffer_output) ob_start();
      $fire_type = (function_exists('curl_exec') ) ? " PHP CURL " : " PHP fsockopen ";
      //                 "://" satisfies both cases http:// and https://
      if (strstr($script,"://") ) fire_remote_script($script);
      else
       {
         include(LOCATION.$script);
         $fire_type=" PHP include ";
       }
      if($buffer_output) $output=ob_get_contents();
      else $output="";
      if($buffer_output) ob_end_clean();
      $execution_time=number_format( (microtime(true) - $start_time), 5 )." seconds via".$fire_type;
      $output=substr($output, 0, MAX_ERROR_LOG_LENGTH);// truncate output to defined length
      if (ERROR_LOG) save_log($script,$output,$execution_time);
      unset($_SESSION[$md5_script]);
 }
}

function fire_remote_script($url)
{
  $url_parsed = parse_url($url);
  $scheme = $url_parsed["scheme"];
  $host = $url_parsed["host"];
  $port = isset($url_parsed["port"]) ? $url_parsed["port"] : 80;
  $path = isset($url_parsed["path"]) ? $url_parsed["path"] : "/";
  $query = isset($url_parsed["query"]) ? $url_parsed["query"] : "";
  $user = isset($url_parsed["user"]) ? $url_parsed["user"] : "";
  $pass = isset($url_parsed["pass"]) ? $url_parsed["pass"] : "";
  $useragent="phpJobScheduler (http://www.dwalker.co.uk/phpjobscheduler/)";
  $referer=$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
  $buffer="";
  if (function_exists('curl_exec'))
  {
   $ch = curl_init($scheme."://".$host.$path);
   curl_setopt($ch, CURLOPT_PORT, $port);
   curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
   curl_setopt($ch, CURLOPT_HEADER, 0);
   curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
   curl_setopt($ch,CURLOPT_FAILONERROR,1); // true to fail silently
   curl_setopt($ch,CURLOPT_AUTOREFERER,1);
   curl_setopt($ch, CURLOPT_POSTFIELDS,$query);
   curl_setopt($ch, CURLOPT_REFERER,$referer);
   curl_setopt($ch, CURLOPT_USERAGENT,$useragent);
   curl_setopt($ch, CURLOPT_USERPWD,$user.":".$pass);
   $buffer = curl_exec($ch);
   curl_close($ch);
  }
  elseif ( $fp = @fsockopen($host, $port, $errno, $errstr, 30) )
  {
   $header = "POST $path HTTP/1.0\r\nHost: $host\r\nReferer: $referer\r\n"
             ."Content-Type: application/x-www-form-urlencoded\r\n"
             ."User-Agent: $useragent\r\n"
             ."Content-Length: ". strlen($query)."\r\n";
   if($user!= "") $header.= "Authorization: Basic ".base64_encode("$user:$pass")."\r\n";
   $header.= "Connection: close\r\n\r\n";
   fputs($fp, $header);
   fputs($fp, $query);
   if ($fp) while (!feof($fp)) $buffer.= fgets($fp, 8192);
   @fclose($fp);
  }
 echo $buffer;
}
?>