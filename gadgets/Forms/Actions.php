<?php
/**
 * Forms Actions file
 *
 * @category   GadgetActions
 * @package    Forms
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
$actions = array();

$actions['Index'] 						= array('NormalAction');
$actions['Form']  						= array('NormalAction');
$actions['Send']  						= array('NormalAction');
$actions['RSS']  						= array('StandaloneAction');
$actions['account_GetQuickAddForm'] 	= array('StandaloneAction');

$actions['Admin']         		= array('AdminAction');
$actions['form']          		= array('AdminAction');
$actions['form_post']     		= array('AdminAction');
$actions['view']          		= array('AdminAction');
$actions['A_form']        		= array('AdminAction');
$actions['A_form_post']			= array('AdminAction');
$actions['Settings']      		= array('AdminAction');
$actions['SaveChanges']      	= array('StandaloneAdminAction');
$actions['GetQuickAddForm']     = array('StandaloneAdminAction');
?>
