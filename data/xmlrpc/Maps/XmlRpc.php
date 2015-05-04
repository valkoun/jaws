<?php
/**
 * Maps XML RPC
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2011 Alan Valkoun
 * @package Maps
 */
require_once JAWS_PATH . 'include/Jaws/InitApplication.php';
$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');
require_once JAWS_PATH . 'include/Jaws/User.php';
require_once JAWS_PATH . 'libraries/pear/' . 'XML/RPC/Server.php';

/**
 * Get Maps ACL permission for a specified user
 */
function GetMapsPermission($user, $task, $user_type)
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
    return $GLOBALS['app']->ACL->GetFullPermission($user, $groups, 'Maps', $task);
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
 * GetRegion
 */
function GetRegion($params)
{
    // parameters
	$user 		= getScalarValue($params, 0);
	$password 	= getScalarValue($params, 1);
    $checksum	= getScalarValue($params, 2);

	/*
	$userModel = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $userModel->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+3,  _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetMapsPermission($user, 'ManageProducts', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }
	*/

    $model = $GLOBALS['app']->loadGadget('Maps', 'Model');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }
	
	// Get item by checksum
	$res = $model->GetRegionByChecksum($checksum);
    if (Jaws_Error::isError($res)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $res->GetMessage());
    }
		
	$data = array();
	foreach ($res as $k => $v) {
		$data[$k] = XML_RPC_encode($v);
	}
    
	$struct = array();
    $struct[]  = new XML_RPC_Value($data, 'struct');
    $data = array($struct[0]);
    $response = new XML_RPC_Value($data, 'array');
    return new XML_RPC_Response($response);
}

/*
 * GetRegionsWithinRadius
 */
function GetRegionsWithinRadius($params)
{
    // parameters
	$user 			= getScalarValue($params, 0);
	$password 		= getScalarValue($params, 1);
    $long			= getScalarValue($params, 2);
    $lat			= getScalarValue($params, 3);
    $radius			= getScalarValue($params, 4);
    $limit			= getScalarValue($params, 5);
    $pop			= getScalarValue($params, 6);

	if ((int)$radius == 0) {
		$radius = 150;
	}
	
	if ((int)$limit == 0) {
		$limit = 100;
	}
	
	if ($pop <= 0) {
		$pop = null;
	}
	
	/*
	$userModel = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $userModel->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+3,  _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetMapsPermission($user, 'ManageMaps', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }
	*/

    $model = $GLOBALS['app']->loadGadget('Maps', 'Model');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }
		
	// Get items
	$res = $model->GetRegionsWithinRadius($long, $lat, $radius, $limit, $pop);
	if (Jaws_Error::isError($res)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $res->GetMessage());
    }
		
	$struct = array();
    $i = 0;
    foreach ($res as $r) {
        if ($i > 99) {
			break;
		}
		$data = array();
		foreach ($r as $k => $v) {
			$data[$k] = XML_RPC_encode($v);
		}
        $struct[$i] = new XML_RPC_Value($data, 'struct');
        $i++;
    }

    if ($i > 0) {
        $data = array($struct[0]);
        for ($j = 1; $j < $i; $j++) {
            array_push($data, $struct[$j]);
        }
    } else {
        $data = array();
    }
    $response = new XML_RPC_Value($data, 'array');
    return new XML_RPC_Response($response);
}

/*
 * SearchRegions
 */
function SearchRegions($params)
{
    // parameters
	$user 			= getScalarValue($params, 0);
	$password 		= getScalarValue($params, 1);
    $search			= getScalarValue($params, 2);
    $parentChecksum = getScalarValue($params, 3);
    $table			= getScalarValue($params, 4);

	if ($table == '') {
		$table = null;
	}
		
	/*
	$userModel = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $userModel->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+3,  _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetMapsPermission($user, 'ManageMaps', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }
	*/

    $model = $GLOBALS['app']->loadGadget('Maps', 'Model');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }
		
	// Get item by checksum
	$pid = null;
	if (!empty($parentChecksum)) {
		$parent = $model->GetRegionByChecksum($parentChecksum);
		if (Jaws_Error::isError($parent)) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $parent->GetMessage());
		} else if (isset($parent['id']) && !empty($parent['id'])) {
			$pid = $parent['id'];
		}
	}
	
    $adminModel = $GLOBALS['app']->loadGadget('Maps', 'AdminModel');
    if (Jaws_Error::isError($adminModel)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $adminModel->GetMessage());
    }
	
	$res = $adminModel->SearchRegions($search, $pid, $table);
    if (Jaws_Error::isError($res)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $res->GetMessage());
    }
		
	$struct = array();
    $i = 0;
    foreach ($res as $r) {
        if ($i > 99) {
			break;
		}
		$data = array();
		foreach ($r as $k => $v) {
			$data[$k] = XML_RPC_encode($v);
		}
        $struct[$i] = new XML_RPC_Value($data, 'struct');
        $i++;
    }

    if ($i > 0) {
        $data = array($struct[0]);
        for ($j = 1; $j < $i; $j++) {
            array_push($data, $struct[$j]);
        }
    } else {
        $data = array();
    }
    $response = new XML_RPC_Value($data, 'array');
    return new XML_RPC_Response($response);
}

/*
 * GetRegionsOfParent
 */
function GetRegionsOfParent($params)
{
    // parameters
	$user 		= getScalarValue($params, 0);
	$password 	= getScalarValue($params, 1);
	$checksum 	= getScalarValue($params, 2);
		
	/*
	$userModel = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $userModel->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+3,  _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetMapsPermission($user, 'ManageMaps', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }
	*/

    $model = $GLOBALS['app']->loadGadget('Maps', 'Model');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }
		
	$parent = $model->GetRegionByChecksum($checksum);
    if (Jaws_Error::isError($parent)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $parent->GetMessage());
    }
	
	if (isset($parent['id']) && !empty($parent['id'])) {
		
		$res = $model->GetRegionsOfParent($parent['id']);
		if (Jaws_Error::isError($res)) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $res->GetMessage());
		}
		
		$struct = array();
		$i = 0;
		foreach ($res as $r) {
			if ($i > 99) {
				break;
			}
			$data = array();
			foreach ($r as $k => $v) {
				$data[$k] = XML_RPC_encode($v);
			}
			$struct[$i] = new XML_RPC_Value($data, 'struct');
			$i++;
		}

		if ($i > 0) {
			$data = array($struct[0]);
			for ($j = 1; $j < $i; $j++) {
				array_push($data, $struct[$j]);
			}
		} else {
			$data = array();
		}
    } else {
        $data = array();
    }
    $response = new XML_RPC_Value($data, 'array');
    return new XML_RPC_Response($response);
}

/*
 *  XML-RPC Server
 */

$rpc_methods = array(
    // Regions
    'GetRegion' => array(
        'function' => 'GetRegion',
        'signature' => array(
            array('string', 'string', 'string', 'string'),
        ),
    ),
    'GetRegionsWithinRadius' => array(
        'function' => 'GetRegionsWithinRadius',
        'signature' => array(
            array('string', 'string', 'string', 'double', 'double', 'int', 'int', 'int'),
        ),
    ),
    'SearchRegions' => array(
        'function' => 'SearchRegions',
        'signature' => array(
            array('string', 'string', 'string', 'string', 'string', 'string'),
        ),
    ),
    'GetRegionsOfParent' => array(
        'function' => 'GetRegionsOfParent',
        'signature' => array(
            array('string', 'string', 'string', 'string'),
        ),
    )
);
