<?php
/**
 * FlashGallery Actions file
 *
 * @category   GadgetActions
 * @package    FlashGallery
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
$actions 									= array();
$actions['RSSXML']         					= array('StandaloneAction');
$actions['GalleryXML']      				= array('StandaloneAction');
$actions['EmbedFlashGallery']  				= array('StandaloneAction');
$actions['GetUserAccountControls']			= array('StandaloneAction');
$actions['GetUserAccountPanesInfo']			= array('StandaloneAction');
$actions['UserFlashGallery'] 				= array('StandaloneAction');
$actions['UserFlashGallerySubscriptions'] 	= array('StandaloneAction');

// Account actions
$actions['account_Admin'] 				= array('StandaloneAction');
$actions['account_form'] 				= array('StandaloneAction');
$actions['account_form_post'] 			= array('StandaloneAction');
$actions['account_view'] 				= array('StandaloneAction');
$actions['account_A_form'] 				= array('StandaloneAction');
$actions['account_A_form_post'] 		= array('StandaloneAction');
$actions['account_ShowEmbedWindow'] 	= array('StandaloneAction');
$actions['account_GetQuickAddForm'] 	= array('StandaloneAction');

$actions['Admin']         	= array('AdminAction');
$actions['view']          	= array('AdminAction');
$actions['form']          	= array('AdminAction');
$actions['form_post']     	= array('AdminAction');
$actions['A_form']        	= array('AdminAction');
$actions['A_form_post']		= array('AdminAction');
$actions['ShowEmbedWindow'] = array('StandaloneAdminAction');
$actions['GetQuickAddForm'] = array('StandaloneAdminAction');
