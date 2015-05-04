<?php
/**
 * Policy URL maps
 *
 * @category   GadgetMaps
 * @package    Policy
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2008-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
$GLOBALS['app']->Map->Connect('Policy', 'Captcha', 'captcha/{key}');
