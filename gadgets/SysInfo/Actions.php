<?php
/**
 * SysInfo Actions
 *
 * @category   GadgetActions
 * @package    SysInfo
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2008-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
$actions = array();

$actions['SysInfo']  = array('LayoutAction,AdminAction,NormalAction',
                             _t('SYSINFO_SYSINFO'),
                             _t('SYSINFO_SYSINFO_DESC'));
$actions['PHPInfo']  = array('LayoutAction,AdminAction,NormalAction',
                             _t('SYSINFO_PHPINFO'),
                             _t('SYSINFO_PHPINFO_DESC'));
$actions['JawsInfo'] = array('LayoutAction,AdminAction,NormalAction',
                             _t('SYSINFO_JAWSINFO'),
                             _t('SYSINFO_JAWSINFO_DESC'));
$actions['DirInfo']  = array('LayoutAction,AdminAction,NormalAction',
                             _t('SYSINFO_DIRINFO'),
                             _t('SYSINFO_DIRINFO_DESC'));
