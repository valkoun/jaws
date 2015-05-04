<?php
/**
 * Policy Actions file
 *
 * @category   GadgetActions
 * @package    Policy
 * @author     Amir Mohammad Saied <amir@gluegadget.com>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2007-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
$actions = array();

$actions['IPBlocking']       = array('AdminAction');
$actions['AgentBlocking']    = array('AdminAction');
$actions['Encryption']       = array('AdminAction');
$actions['AntiSpam']         = array('AdminAction');
$actions['AdvancedPolicies'] = array('AdminAction');

$actions['Captcha']       = array('StandaloneAction');
