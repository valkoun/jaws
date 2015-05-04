<?php
/**
 * Properties Actions file
 *
 * @category   GadgetActions
 * @package    Properties
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
$actions 								= array();
$actions['Index'] 						= array('NormalAction');
$actions['Category']  					= array('NormalAction');
$actions['Property']  					= array('NormalAction');
$actions['Amenity']   					= array('NormalAction');
$actions['CategoryMapXML']  			= array('StandaloneAction');
$actions['PropertyMapXML']  			= array('StandaloneAction');
$actions['RegionsMapXML']  				= array('StandaloneAction');
$actions['CitiesMapXML']  				= array('StandaloneAction');
$actions['PropertyRSS']  				= array('StandaloneAction');
$actions['PropertyAtom']  				= array('StandaloneAction');
$actions['EmbedProperty']  				= array('StandaloneAction');
$actions['GetUserAccountControls']		= array('StandaloneAction');
$actions['GetUserAccountPanesInfo']		= array('StandaloneAction');
$actions['UserProperties'] 				= array('StandaloneAction');
$actions['UserPropertiesSubscriptions'] = array('StandaloneAction');
$actions['PrintPropertyDetails'] 		= array('StandaloneAction');
$actions['hideGoogleAPIAlerts']			= array('StandaloneAction');
$actions['UpdateRSSProperties']			= array('StandaloneAction');
$actions['SnoopRSSProperties']			= array('StandaloneAction');

// Account actions
$actions['account_Admin'] 				= array('StandaloneAction');
$actions['account_form'] 				= array('StandaloneAction');
$actions['account_form_post'] 			= array('StandaloneAction');
$actions['account_A'] 					= array('StandaloneAction');
$actions['account_A_form'] 				= array('StandaloneAction');
$actions['account_A_form_post'] 		= array('StandaloneAction');
$actions['account_A_form2'] 			= array('StandaloneAction');
$actions['account_A_form_post2'] 		= array('StandaloneAction');
$actions['account_B'] 					= array('StandaloneAction');
$actions['account_B_form'] 				= array('StandaloneAction');
$actions['account_B_form_post'] 		= array('StandaloneAction');
$actions['account_B2'] 					= array('StandaloneAction');
$actions['account_B_form2'] 			= array('StandaloneAction');
$actions['account_B_form_post2'] 		= array('StandaloneAction');
$actions['account_C'] 					= array('StandaloneAction');
$actions['account_C_form'] 				= array('StandaloneAction');
$actions['account_C_form_post'] 		= array('StandaloneAction');
$actions['account_C2'] 					= array('StandaloneAction');
$actions['account_C_form2'] 			= array('StandaloneAction');
$actions['account_C_form_post2'] 		= array('StandaloneAction');
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
$actions['C2']          	= array('AdminAction');
$actions['C_form2']        	= array('AdminAction');
$actions['C_form_post2']	= array('AdminAction');
$actions['Settings']		= array('AdminAction');
$actions['ShowEmbedWindow'] = array('StandaloneAdminAction');
$actions['SetGBRoot']      	= array('StandaloneAdminAction');
$actions['GetQuickAddForm'] = array('StandaloneAdminAction');
?>
