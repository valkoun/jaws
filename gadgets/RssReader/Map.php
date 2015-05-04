<?php
/**
 * RssReader URL maps
 *
 * @category   GadgetMaps
 * @package    RssReader
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2007-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
$GLOBALS['app']->Map->Connect('RssReader', 'DefaultAction', 'feed/default');
$GLOBALS['app']->Map->Connect('RssReader', 'GetFeed', 'feed/{id}');
