<?php
/**
 * CustomPage Actions file
 *
 * @category   GadgetActions
 * @package    CustomPage
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
$actions 								= array();
$actions['Index'] 						= array('NormalAction');
$actions['Page']  						= array('StandaloneAction');
$actions['GoogleSitemap']  				= array('StandaloneAction');
$actions['Display']  					= array('LayoutAction', _t('CUSTOMPAGE_LAYOUT_LIST'), _t('CUSTOMPAGE_LAYOUT_LIST_DESCRIPTION'));
$actions['SyntactsUpdates']  			= array('StandaloneAction');
$actions['EmbedCustomPage']  			= array('StandaloneAction');
$actions['GetUserAccountControls']		= array('StandaloneAction');
$actions['GetUserAccountPanesInfo']		= array('StandaloneAction');
$actions['UserCustompage'] 				= array('StandaloneAction');
$actions['UserCustomPageSubscriptions'] = array('StandaloneAction');

// Account actions
$actions['account_Admin'] 				= array('StandaloneAction');
$actions['account_form'] 				= array('StandaloneAction');
$actions['account_form_post'] 			= array('StandaloneAction');
$actions['account_view'] 				= array('StandaloneAction');
$actions['account_A_form'] 				= array('StandaloneAction');
$actions['account_A_form_post'] 		= array('StandaloneAction');
$actions['account_ShowEmbedWindow'] 	= array('StandaloneAction');
$actions['account_SetGBRoot'] 			= array('StandaloneAction');
$actions['account_EditElementAction']   = array('StandaloneAction');
$actions['account_AddLayoutElement']    = array('StandaloneAction');
$actions['account_SaveLayoutElement']  	= array('StandaloneAction');
$actions['account_GetQuickAddForm'] 	= array('StandaloneAction');
$actions['account_SaveEditPost'] 		= array('StandaloneAction');

$actions['Admin']         		= array('AdminAction');
$actions['view']          		= array('StandaloneAdminAction');
$actions['form']          		= array('AdminAction');
$actions['A_form']        		= array('AdminAction');
$actions['Settings']      		= array('AdminAction');
$actions['form_post']     		= array('StandaloneAdminAction');
$actions['A_form_post']			= array('StandaloneAdminAction');
$actions['AddPage']     		= array('StandaloneAdminAction');
$actions['ShowEmbedWindow']     = array('StandaloneAdminAction');
$actions['SetGBRoot']      		= array('StandaloneAdminAction');
$actions['GetQuickAddForm'] 	= array('StandaloneAdminAction');

$actions['ChangeTheme']           	= array('StandaloneAdminAction');
$actions['LayoutManager']         	= array('AdminAction');
$actions['LayoutBuilder']         	= array('AdminAction');
$actions['SetLayoutMode']         	= array('AdminAction');
$actions['DeleteLayoutElement']   	= array('AdminAction');
$actions['SaveLayoutElement']  		= array('AdminAction');
$actions['EditElementAction']     	= array('StandaloneAdminAction');
$actions['AddLayoutElement']      	= array('StandaloneAdminAction');
$actions['SaveEditPost']      		= array('StandaloneAdminAction');
