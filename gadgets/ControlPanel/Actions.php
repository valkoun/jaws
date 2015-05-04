<?php
/**
 * ControlPanel Actions
 *
 * @category   GadgetActions
 * @package    ControlPanel
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @copyright  2004-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
$actions = array();

$actions['DefaultAction']  		= array('AdminAction');
$actions['Logout']         		= array('AdminAction');
$actions['DatabaseBackup']		= array('AdminAction');
$actions['Statistics'] 			= array('AdminAction');

$actions['DBBackup']       		= array('StandaloneAdminAction');
$actions['RestoreBackup'] 		= array('StandaloneAdminAction');
$actions['ShowTip']       		= array('StandaloneAdminAction');
$actions['CreatePDFsOfAllURLs']	= array('StandaloneAdminAction');
$actions['CreatePDFOfURL']      = array('StandaloneAdminAction');
