<?php
/**
 * SysInfo URL maps
 *
 * @category   GadgetMaps
 * @package    SysInfo
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2008-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
$GLOBALS['app']->Map->Connect('SysInfo', 'DefaultAction', 'info');
$GLOBALS['app']->Map->Connect('SysInfo', 'SysInfo',  'info/sys');
$GLOBALS['app']->Map->Connect('SysInfo', 'PHPInfo',  'info/php');
$GLOBALS['app']->Map->Connect('SysInfo', 'JawsInfo', 'info/jaws');
$GLOBALS['app']->Map->Connect('SysInfo', 'DirInfo',  'info/dir');
