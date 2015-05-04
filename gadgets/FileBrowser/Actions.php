<?php
/**
 * FileBrowser Actions file
 *
 * @category   GadgetActions
 * @package    FileBrowser
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @copyright  2004-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
$actions = array();

$actions['Display']       	= array('NormalAction');
$actions['FileInfo']      	= array('NormalAction');

$actions['InitialFolder'] 	= array('LayoutAction',
                                  _t('FILEBROWSER_INITIAL_FOLDER'),
                                  _t('FILEBROWSER_INITIAL_FOLDER_DESC'));

$actions['Admin']         	= array('AdminAction');
$actions['AddFileToPost']	= array('StandaloneAdminAction');
$actions['FilePicker']    	= array('StandaloneAdminAction');
$actions['UploadFile']    	= array('StandaloneAdminAction');

// Account actions
$actions['account_Admin'] 			= array('StandaloneAction');
$actions['account_UploadFile']      = array('StandaloneAction');
$actions['account_AddFileToPost']	= array('StandaloneAction');
$actions['account_FilePicker']		= array('StandaloneAction');
$actions['GetUserAccountControls']	= array('StandaloneAction');
$actions['GetUserAccountPanesInfo']	= array('StandaloneAction');
$actions['Watermark']				= array('StandaloneAction');