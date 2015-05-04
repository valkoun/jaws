<?php
/**
 * Languages Actions
 *
 * @category   GadgetActions
 * @package    Languages
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2007-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
$actions = array();

$actions['Admin']  = array('AdminAction');
$actions['Settings']  = array('AdminAction');
$actions['Export'] = array('StandaloneAdminAction');
$actions['SaveUserLanguage'] = array('StandaloneAction');
