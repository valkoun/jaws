<?php
/**
 * Calendar Actions file
 * *
 * @category   GadgetActions
 * @package    Calendar
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
$actions = array();

$actions['Calendar']  						= array('NormalAction');
$actions['Index']  							= array('NormalAction');
$actions['Month']  							= array('NormalAction');
$actions['Week']  							= array('NormalAction');
$actions['Year']  							= array('NormalAction');
$actions['Detail']  						= array('NormalAction');
$actions['Day']  							= array('NormalAction');
$actions['CalendarXML']  					= array('StandaloneAction');
$actions['EmbedCalendar']  					= array('StandaloneAction');
$actions['GetUserAccountControls']			= array('StandaloneAction');
$actions['GetUserAccountPanesInfo']			= array('StandaloneAction');
$actions['UserCalendar']  					= array('StandaloneAction');
$actions['JSONCalendarEventsByDateRange']  	= array('StandaloneAction');
$actions['Display']  						= array('StandaloneAction');

// Account actions
$actions['account_Admin'] 				= array('StandaloneAction');
$actions['account_form'] 				= array('StandaloneAction');
$actions['account_form_post'] 			= array('StandaloneAction');
$actions['account_A'] 					= array('StandaloneAction');
$actions['account_A_form'] 				= array('StandaloneAction');
$actions['account_A_form_post'] 		= array('StandaloneAction');
$actions['account_ShowEmbedWindow'] 	= array('StandaloneAction');
$actions['account_GetQuickAddForm'] 	= array('StandaloneAction');
$actions['account_SetGBRoot'] 			= array('StandaloneAction');

// Admin actions
$actions['Admin']   		= array('AdminAction');
$actions['form']   			= array('AdminAction');
$actions['form_post']     	= array('AdminAction');
$actions['A'] 				= array('AdminAction');
$actions['A_form'] 			= array('AdminAction');
$actions['A_form_post'] 	= array('AdminAction');
$actions['GetQuickAddForm'] = array('StandaloneAdminAction');
$actions['ShowEmbedWindow'] = array('StandaloneAdminAction');
$actions['SetGBRoot']	 	= array('StandaloneAdminAction');
