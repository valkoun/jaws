<?php
/**
 * Preferences URL maps
 *
 * @category   GadgetMaps
 * @package    Preferences
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2008-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
$GLOBALS['app']->Map->Connect('Preferences', 'DefaultAction', 'preferences');
$GLOBALS['app']->Map->Connect('Preferences', 'SetLanguage', 'language/{lang}');
