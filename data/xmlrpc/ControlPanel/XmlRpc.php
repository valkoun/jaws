<?php
/**
 * ControlPanel XML RPC
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2011 Alan Valkoun
 * @package ControlPanel
 */
require_once JAWS_PATH . 'include/Jaws/InitApplication.php';
$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');
require_once JAWS_PATH . 'include/Jaws/User.php';
require_once JAWS_PATH . 'libraries/pear/' . 'XML/RPC/Server.php';

/**
 * Get ControlPanel ACL permission for a specified user
 */
function GetControlPanelPermission($user, $task, $user_type)
{
    if ($user_type == 0) {
        return true;
    }

	$model = new Jaws_User;
    $groups = $model->GetGroupsOfUsername($user);
    if (Jaws_Error::IsError($groups)) {
        return false;
    }

    $groups = array_map(create_function('$row','return $row["group_id"];'), $groups);
    return $GLOBALS['app']->ACL->GetFullPermission($user, $groups, 'ControlPanel', $task);
}

/**
 * Aux functions
 */
function getScalarValue($p, $i)
{
    $r = $p->getParam($i);
    if (!XML_RPC_Value::isValue($r)) {
        return false;
        //return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+3, 'fubar user param');
    }

    return $r->scalarval();
}

function parseContent($content)
{
	$encoding = mb_detect_encoding( $content, "auto" );
	$content = str_replace( "?", "__question__mark__", $content );
	$content = mb_convert_encoding( $content, 'ASCII', $encoding);
	$content = str_replace( "?", "", $content );
	$content = str_replace( "__question__mark__", "?", $content );
	$content = mb_convert_encoding( $content, 'UTF-8', 'ASCII');
    
    $content = htmlentities($content, ENT_NOQUOTES, 'UTF-8');
    $in  = array('&gt;', '&lt;', '&quot;', '&amp;');
    $out = array('>', '<', '"', '&');
    $content = str_replace($in, $out, $content);

    return $content;
}

/*
 * CreatePDFOfURL
 */
function CreatePDFOfURL($params)
{
    // parameters
	$user 		= getScalarValue($params, 0);
	$password 	= getScalarValue($params, 1);
    $url		= getScalarValue($params, 2);
    $filename	= getScalarValue($params, 3);
	$filename	= (empty($filename) ? false : $filename);

	$userModel = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $userModel->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+3,  _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetControlPanelPermission($user, 'default', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $adminHTML = $GLOBALS['app']->loadGadget('ControlPanel', 'AdminHTML');
    if (Jaws_Error::isError($adminHTML)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $adminHTML->GetMessage());
    }
	
	// Create PDF
	$res = $adminHTML->CreatePDFOfURL($url, $filename);
    if (Jaws_Error::isError($res)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $res->GetMessage());
    }
		
	$val = new XML_RPC_Value("$res", 'string');
    return new XML_RPC_Response($val);
}

/*
 * CreatePDFsOfAllURLs
 */
function CreatePDFsOfAllURLs($params)
{
    // parameters
	$user 		= getScalarValue($params, 0);
	$password 	= getScalarValue($params, 1);
    $urls		= getScalarValue($params, 2);
	$urls		= (!is_array($urls) ? array() : $urls);
	$userModel = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $userModel->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+3,  _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetControlPanelPermission($user, 'default', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $adminHTML = $GLOBALS['app']->loadGadget('ControlPanel', 'AdminHTML');
    if (Jaws_Error::isError($adminHTML)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $adminHTML->GetMessage());
    }
	
	// Create PDFs
	$res = $adminHTML->CreatePDFsOfAllURLs($urls);
    if (Jaws_Error::isError($res)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $res->GetMessage());
    }
		
	$val = new XML_RPC_Value("$res", 'string');
    return new XML_RPC_Response($val);
}

/*
 *  XML-RPC Server
 */

$rpc_methods = array(
    // Regions
    'CreatePDFOfURL' => array(
        'function' => 'CreatePDFOfURL',
        'signature' => array(
            array('string', 'string', 'string', 'string', 'string'),
        ),
    ),
    'CreatePDFsOfAllURLs' => array(
        'function' => 'CreatePDFsOfAllURLs',
        'signature' => array(
            array('string', 'string', 'string', 'array'),
        ),
    )
);