<?php
/**
 * Websites Actions file
 *
 * @category   GadgetActions
 * @package    Websites
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
$actions = array();

$actions['Category']  					= array('NormalAction');
$actions['Website']  						= array('NormalAction');

$actions['WebsitesXML']         				= array('StandaloneAction');

$actions['EmbedWebsites']  					= array('StandaloneAction');
$actions['GetUserAccountControls']		= array('StandaloneAction');
$actions['GetUserAccountPanesInfo']		= array('StandaloneAction');
$actions['UserWebsitesowners'] 				= array('StandaloneAction');
$actions['UserWebsitesusers'] 				= array('StandaloneAction');

// Account actions
$actions['account_Admin'] 				= array('StandaloneAction');
$actions['account_form'] 				= array('StandaloneAction');
$actions['account_form_post'] 			= array('StandaloneAction');
$actions['account_view'] 				= array('StandaloneAction');
$actions['account_A']         			= array('StandaloneAction');
$actions['account_A_form']          	= array('StandaloneAction');
$actions['account_A_form_post']    		= array('StandaloneAction');
$actions['account_B']         			= array('StandaloneAction');
$actions['account_B_form']          	= array('StandaloneAction');
$actions['account_B_form_post']     	= array('StandaloneAction');
$actions['account_ShowEmbedWindow'] 	= array('StandaloneAction');
$actions['account_SetGBRoot'] 			= array('StandaloneAction');

$actions['Admin']         	= array('AdminAction');
$actions['view']          	= array('AdminAction');
$actions['form']          	= array('AdminAction');
$actions['form_post']     	= array('AdminAction');
$actions['A']         		= array('AdminAction');
$actions['A_form']          = array('AdminAction');
$actions['A_form_post']     = array('AdminAction');
$actions['B']         		= array('AdminAction');
$actions['B_form']          = array('AdminAction');
$actions['B_form_post']     = array('AdminAction');
$actions['ShowEmbedWindow'] = array('StandaloneAdminAction');
$actions['SetGBRoot']      	= array('StandaloneAdminAction');
?>
