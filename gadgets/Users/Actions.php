<?php
/**
 * Users Actions
 *
 * @category   GadgetActions
 * @package    Users
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2004-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
$actions = array();

/* Admin actions */
$actions['Admin']               = array('AdminAction');
$actions['MyAccount']           = array('AdminAction');
$actions['Groups']              = array('AdminAction');
$actions['Settings']          	= array('AdminAction');
$actions['RegUser']    			= array('AdminAction');
$actions['Sharing']    			= array('AdminAction');
$actions['AuthUserGroup']   	= array('AdminAction');
$actions['SaveLayoutElement']  	= array('AdminAction');
$actions['ImportUsers']			= array('AdminAction');
$actions['Messaging']			= array('AdminAction');
$actions['ImportFile'] 			= array('StandaloneAdminAction');

/* Normal actions */
$actions['LoginForm']           = array('NormalAction');
$actions['Login']               = array('StandaloneAction');
$actions['Logout']              = array('StandaloneAction');

$actions['Registration']        = array('NormalAction');
$actions['DoRegister']          = array('NormalAction');
$actions['Registered']          = array('NormalAction');

$actions['Account']             = array('NormalAction');
$actions['UpdateAccount']       = array('StandaloneAction');

$actions['Profile']             = array('NormalAction');
$actions['UpdateProfile']       = array('StandaloneAction');

$actions['Preferences']         = array('NormalAction');
$actions['UpdatePreferences']   = array('StandaloneAction');

$actions['ForgotPassword']      = array('NormalAction');
$actions['SendRecoverKey']      = array('NormalAction');
$actions['ChangePassword']      = array('NormalAction');
$actions['ActivateUser']        = array('NormalAction');
$actions['UpdatePublicGadget'] 	= array('NormalAction');
$actions['RequestGroupAccess'] 	= array('NormalAction');
$actions['RequestedGroupAccess'] = array('NormalAction');
$actions['RequestFriendGroup'] 	= array('NormalAction');
$actions['RequestedFriendGroup'] = array('NormalAction');
$actions['RequestFriend'] 		= array('NormalAction');
$actions['RequestedFriend'] 	= array('NormalAction');
$actions['RemoveFriend'] 		= array('NormalAction');
$actions['RemovedFriend'] 		= array('NormalAction');
$actions['AccountHome']    		= array('NormalAction');
$actions['AccountPublic'] 		= array('NormalAction');
$actions['GroupPage'] 			= array('StandaloneAction');
$actions['UserDirectory'] 		= array('NormalAction');
$actions['GroupDirectory'] 		= array('NormalAction');
$actions['ShowFrame'] 			= array('NormalAction');
$actions['ShowComments'] 		= array('NormalAction');
$actions['ShowComment'] 		= array('NormalAction');
$actions['EmailPage'] 			= array('NormalAction');
$actions['account_Groups']  	= array('NormalAction');

/* Standalone actions */
$actions['ShowRawComments'] 				= array('StandaloneAction');
$actions['EmbedGadget'] 					= array('StandaloneAction');
$actions['YourAccountRaw']    				= array('StandaloneAction');
$actions['RSS'] 							= array('StandaloneAction');
$actions['UpdateRSSUsers']					= array('StandaloneAction');
$actions['AutoComplete'] 					= array('StandaloneAction');
$actions['ShowNoPermission'] 				= array('StandaloneAction');
$actions['account_SetGBRoot'] 				= array('StandaloneAction');
$actions['account_EditElementAction']  	 	= array('StandaloneAction');
$actions['account_AddLayoutElement']    	= array('StandaloneAction');
$actions['account_SaveLayoutElement']  		= array('StandaloneAction');
$actions['account_ShareComment']  			= array('StandaloneAction');
$actions['account_GetQuickAddForm']  		= array('StandaloneAction');
$actions['account_AuthUserGroup']  			= array('StandaloneAction');
$actions['ManualUpdateUsersGadgets'] 		= array('StandaloneAdminAction');
$actions['ManualInsertDefaultChecksums'] 	= array('StandaloneAdminAction');
$actions['ManualCreateThumbs'] 				= array('StandaloneAdminAction');
$actions['ManualPayPalCapture'] 			= array('StandaloneAdminAction');
$actions['ManualPayPalVoid'] 				= array('StandaloneAdminAction');
$actions['ManualUpdateAux'] 				= array('StandaloneAdminAction');
$actions['ManualExportToHost'] 				= array('StandaloneAdminAction');
$actions['EditElementAction']   			= array('StandaloneAdminAction');
$actions['AddLayoutElement']    			= array('StandaloneAdminAction');
$actions['ShareComment']    				= array('StandaloneAdminAction');
$actions['SetGBRoot']      					= array('StandaloneAdminAction');
$actions['GetQuickAddForm'] 				= array('StandaloneAdminAction');
