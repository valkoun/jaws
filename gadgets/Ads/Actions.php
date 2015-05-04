<?php
/**
 * Ads Actions file
 *
 * @category   GadgetActions
 * @package    Ads
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
$actions = array();

$actions['AddClick']  					= array('NormalAction');
$actions['Category']  					= array('NormalAction');
$actions['Ad']  						= array('NormalAction');

$actions['AdsXML']         				= array('StandaloneAction');

$actions['EmbedAds']  					= array('StandaloneAction');
$actions['GetUserAccountControls']		= array('StandaloneAction');
$actions['GetUserAccountPanesInfo']		= array('StandaloneAction');
$actions['UserAds'] 					= array('StandaloneAction');
$actions['UserAdsowners'] 				= array('StandaloneAction');
$actions['UserAdsusers'] 				= array('StandaloneAction');

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
$actions['account_GetQuickAddForm'] 	= array('StandaloneAction');
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
$actions['GetQuickAddForm'] = array('StandaloneAdminAction');
$actions['ShowEmbedWindow'] = array('StandaloneAdminAction');
$actions['SetGBRoot']      	= array('StandaloneAdminAction');