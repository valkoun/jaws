<?php
/**
 * Poll URL maps
 *
 * @category   GadgetMaps
 * @package    Poll
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2007-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
$GLOBALS['app']->Map->Connect('Poll', 'LastPoll', 'poll/last');
$GLOBALS['app']->Map->Connect('Poll', 'ListOfPolls', 'poll/list');
$GLOBALS['app']->Map->Connect('Poll', 'ViewPoll', 'poll/{id}');
$GLOBALS['app']->Map->Connect('Poll', 'ViewResult', 'poll/results/{id}');
