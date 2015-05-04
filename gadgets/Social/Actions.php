<?php
/**
 * Preferences Actions file
 *
 * @category   GadgetActions
 * @package    Preferences
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2004-2009 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
/* Normal actions*/
$actions = array();
$actions['Unsubscribe'] 		= array('NormalAction');
$actions['Unsubscribed'] 		= array('NormalAction');
$actions['FacebookResponse']	= array('NormalAction');
$actions['UpdateRSSUsers']		= array('StandaloneAction');
$actions['fbchannel']			= array('StandaloneAction');

/* Layout actions */
$actions['Display']     	= array('LayoutAction', 
                                _t('SOCIAL_LAYOUT_DISPLAY'),
                                _t('SOCIAL_LAYOUT_DISPLAY_DESCRIPTION'));                                

$actions['ImportEmails']	= array('AdminAction');
$actions['ImportFile'] 		= array('StandaloneAdminAction');
