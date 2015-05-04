<?php
/**
 * Store Actions file
 *
 * @category   GadgetActions
 * @package    Store
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
$actions 								= array();
$actions['Index'] 						= array('NormalAction');
$actions['Category']  					= array('NormalAction');
$actions['BrandIndex']  				= array('NormalAction');
$actions['Brand']  						= array('NormalAction');
$actions['Sale']  						= array('NormalAction');
$actions['Product']  					= array('NormalAction');
$actions['Attribute']   				= array('NormalAction');
$actions['ProductRSS']  				= array('StandaloneAction');
$actions['ProductAtom']  				= array('StandaloneAction');
$actions['CategoryRSS']  				= array('StandaloneAction');
$actions['CategoryAtom']  				= array('StandaloneAction');
$actions['EmbedProduct']  				= array('StandaloneAction');
$actions['GetUserAccountControls']		= array('StandaloneAction');
$actions['GetUserAccountPanesInfo']		= array('StandaloneAction');
$actions['UserStore'] 					= array('StandaloneAction');
$actions['UserProductsSubscriptions'] 	= array('StandaloneAction');
$actions['PrintProductDetails'] 		= array('StandaloneAction');
$actions['AutoComplete'] 				= array('StandaloneAction');
$actions['UpdateRSSStore'] 				= array('StandaloneAction');
$actions['RSS'] 						= array('StandaloneAction');
$actions['Atom'] 						= array('StandaloneAction');
$actions['ShowRSSCategory'] 			= array('StandaloneAction');
$actions['ShowAtomCategory'] 			= array('StandaloneAction');
$actions['ShowTemplate'] 				= array('NormalAction');
$actions['ShowAttribute'] 				= array('StandaloneAction');

// Account actions
$actions['account_Admin'] 				= array('StandaloneAction'); // productparent
$actions['account_form'] 				= array('StandaloneAction'); // productparent
$actions['account_form_post'] 			= array('StandaloneAction'); // productparent
$actions['account_A'] 					= array('StandaloneAction'); // product
$actions['account_A_form'] 				= array('StandaloneAction'); // product
$actions['account_A_form_post'] 		= array('StandaloneAction'); // product
$actions['account_A_form2'] 			= array('StandaloneAction'); // product_posts
$actions['account_A_form_post2'] 		= array('StandaloneAction'); // product_posts
$actions['account_B'] 					= array('StandaloneAction'); // productattribute
$actions['account_B_form'] 				= array('StandaloneAction'); // productattribute
$actions['account_B_form_post'] 		= array('StandaloneAction'); // productattribute
$actions['account_B2'] 					= array('StandaloneAction'); // attribute_types
$actions['account_B_form2'] 			= array('StandaloneAction'); // attribute_types
$actions['account_B_form_post2'] 		= array('StandaloneAction'); // attribute_types
$actions['account_C'] 					= array('StandaloneAction'); // sales
$actions['account_C_form'] 				= array('StandaloneAction'); // sales
$actions['account_C_form_post'] 		= array('StandaloneAction'); // sales
$actions['account_D'] 					= array('StandaloneAction'); // productbrand
$actions['account_D_form'] 				= array('StandaloneAction'); // productbrand
$actions['account_D_form_post'] 		= array('StandaloneAction'); // productbrand
$actions['account_ShowEmbedWindow'] 	= array('StandaloneAction');
$actions['account_SetGBRoot'] 			= array('StandaloneAction');
$actions['account_GetQuickAddForm'] 	= array('StandaloneAction');

$actions['Admin']         	= array('AdminAction');
$actions['form']          	= array('AdminAction');
$actions['form_post']     	= array('AdminAction');
$actions['A']          		= array('AdminAction');
$actions['A_form']        	= array('AdminAction');
$actions['A_form_post']		= array('AdminAction');
$actions['A_form2']        	= array('AdminAction');
$actions['A_form_post2']	= array('AdminAction');
$actions['B']          		= array('AdminAction');
$actions['B_form']        	= array('AdminAction');
$actions['B_form_post']		= array('AdminAction');
$actions['B2']          	= array('AdminAction');
$actions['B_form2']        	= array('AdminAction');
$actions['B_form_post2']	= array('AdminAction');
$actions['C']          		= array('AdminAction');
$actions['C_form']        	= array('AdminAction');
$actions['C_form_post']		= array('AdminAction');
$actions['D']          		= array('AdminAction');
$actions['D_form']        	= array('AdminAction');
$actions['D_form_post']		= array('AdminAction');
$actions['Settings']		= array('AdminAction');
$actions['ImportInventory']	= array('AdminAction');
$actions['ImportFile'] 		= array('StandaloneAdminAction');
$actions['ShowEmbedWindow'] = array('StandaloneAdminAction');
$actions['SetGBRoot']      	= array('StandaloneAdminAction');
$actions['GetQuickAddForm'] = array('StandaloneAdminAction');
?>
