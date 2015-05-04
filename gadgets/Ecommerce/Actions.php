<?php
/**
 * Ecommerce Actions file
 *
 * @category   GadgetActions
 * @package    Ecommerce
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
$actions = array();

$actions['Order']  						= array('NormalAction');
$actions['CheckOrder']         			= array('StandaloneAction');
$actions['PostCart']         			= array('StandaloneAction');
$actions['EcommerceXML']         		= array('StandaloneAction');
$actions['GoogleCheckoutResponse']		= array('NormalAction');
$actions['AuthorizeNetResponse']		= array('NormalAction');
$actions['PayPalResponse']				= array('NormalAction');
$actions['ManualResponse']				= array('NormalAction');
$actions['PrintOrderDetails']			= array('StandaloneAction');

/*
$actions['CartLink']					= array('StandaloneAction');
$actions['CartButton']					= array('StandaloneAction');
$actions['SmallCartButton']				= array('StandaloneAction');
*/

//$actions['EmbedEcommerce']  			= array('StandaloneAction');
$actions['GetUserAccountControls']		= array('StandaloneAction');
$actions['GetUserAccountPanesInfo']		= array('StandaloneAction');
$actions['UserEcommerce'] 				= array('StandaloneAction');
$actions['UserEcommerceSubscriptions'] 	= array('StandaloneAction');
$actions['UserOwnedSubscriptions'] 		= array('StandaloneAction');

// Account actions
$actions['account_Admin'] 				= array('StandaloneAction');
$actions['account_form'] 				= array('StandaloneAction');
$actions['account_form_post'] 			= array('StandaloneAction');
$actions['account_view'] 				= array('StandaloneAction');
//$actions['account_ShowEmbedWindow'] 	= array('StandaloneAction');
//$actions['account_SetGBRoot'] 		= array('StandaloneAction');

$actions['Admin']         				= array('AdminAction');
$actions['view']          				= array('AdminAction');
$actions['form']          				= array('AdminAction');
$actions['form_post']     				= array('AdminAction');
//$actions['ShowEmbedWindow'] 			= array('StandaloneAdminAction');
//$actions['SetGBRoot']      			= array('StandaloneAdminAction');

// Shipping
$actions['account_B']          			= array('StandaloneAction');
$actions['account_B_form']          	= array('StandaloneAction');
$actions['account_B_form_post']     	= array('StandaloneAction');
$actions['B']          					= array('AdminAction');
$actions['B_form']          			= array('AdminAction');
$actions['B_form_post']     			= array('AdminAction');

$actions['Settings']        			= array('AdminAction');
?>
