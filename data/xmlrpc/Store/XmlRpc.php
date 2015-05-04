<?php
/**
 * Store XML RPC
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2011 Alan Valkoun
 * @package Store
 */
require_once JAWS_PATH . 'include/Jaws/InitApplication.php';
$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');
require_once JAWS_PATH . 'include/Jaws/User.php';
require_once JAWS_PATH . 'libraries/pear/' . 'XML/RPC/Server.php';

/**
 * Get Store ACL permission for a specified user
 */
function GetStorePermission($user, $task, $user_type)
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
    return $GLOBALS['app']->ACL->GetFullPermission($user, $groups, 'Store', $task);
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
 * GetProductParent
 */
function GetProductParent($params)
{
    // parameters
	$user 		= getScalarValue($params, 0);
	$password 	= getScalarValue($params, 1);
    $checksum	= getScalarValue($params, 2);

	$userModel = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $userModel->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+3,  _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetStorePermission($user, 'ManageProducts', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $model = $GLOBALS['app']->loadGadget('Store', 'Model');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }
	
	// Get item by checksum
	$res = $model->GetProductParentByChecksum($checksum);
    if (Jaws_Error::isError($res)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $res->GetMessage());
    }
		
	$data = array();
	foreach ($res as $k => $v) {
		// URLs
		if (
			$k == 'productparentimage' || $k == 'productparenturl' 
			&& !empty($v) && file_exists(JAWS_DATA . 'files'.$v) && substr(strtolower($v), 0, 4) != 'http'
		) {
			$v = $GLOBALS['app']->getDataURL('', true) . 'files'.$v;
		}
		$data[$k] = XML_RPC_encode($v);
	}
    
	$struct = array();
    $struct[]  = new XML_RPC_Value($data, 'struct');
    $data = array($struct[0]);
    $response = new XML_RPC_Value($data, 'array');
    return new XML_RPC_Response($response);
}

/*
 * GetSingleProductParentByUserID
 */
function GetSingleProductParentByUserID($params)
{
    // parameters
	$user 			= getScalarValue($params, 0);
	$password 		= getScalarValue($params, 1);
    $OwnerChecksum	= getScalarValue($params, 2);
    $checksum		= getScalarValue($params, 3);

	$userModel = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $userModel->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+3,  _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetStorePermission($user, 'ManageProducts', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $model = $GLOBALS['app']->loadGadget('Store', 'Model');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }
	
	if (!empty($OwnerChecksum) && $OwnerChecksum != '0') {
		// Get user by checksum
		$info = $userModel->GetUserByChecksum($OwnerChecksum);
		if (Jaws_Error::isError($info)) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $info->GetMessage());
		}
		if (isset($info['id'])) {
			$OwnerID = $info['id'];
		} else {
			$val = new XML_RPC_Value(array(), 'array');
			return new XML_RPC_Response($val);
		}
	} else {
		$OwnerID = null;
	}
	
	// Get item by checksum
	$parent = $model->GetProductParentByChecksum($checksum);
    if (Jaws_Error::isError($parent)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $parent->GetMessage());
    }
		
	$res = $model->GetSingleProductParentByUserID($OwnerID, $parent['productparentid']);
	$data = array();
	foreach ($res as $k => $v) {
		// URLs
		if (
			$k == 'productparentimage' || $k == 'productparenturl' 
			&& !empty($v) && file_exists(JAWS_DATA . 'files'.$v) && substr(strtolower($v), 0, 4) != 'http'
		) {
			$v = $GLOBALS['app']->getDataURL('', true) . 'files'.$v;
		}
		$data[$k] = XML_RPC_encode($v);
	}
    
	$struct = array();
    $struct[]  = new XML_RPC_Value($data, 'struct');
    $data = array($struct[0]);
    $response = new XML_RPC_Value($data, 'array');
    return new XML_RPC_Response($response);
}

/*
 * GetProductParent
 */
function GetProductParents($params)
{
    // parameters
	$user 		= getScalarValue($params, 0);
	$password 	= getScalarValue($params, 1);
	$limit 		= getScalarValue($params, 2);
	$sortColumn = getScalarValue($params, 3);
	$sortDir 	= getScalarValue($params, 4);
	$offSet 	= getScalarValue($params, 5);
	$OwnerChecksum 	= getScalarValue($params, 6);
	
	if ($limit <= 0) {
		$limit = null;
	}
	if (empty($sortColumn)) {
		$sortColumn = 'productparentsort_order';
	}
	if (empty($sortDir)) {
		$sortDir = 'ASC';
	}
	if ($offSet <= 0) {
		$offSet = false;
	}
	
	$userModel = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $userModel->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+3,  _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetStorePermission($user, 'ManageProducts', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $model = $GLOBALS['app']->loadGadget('Store', 'Model');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }
	
	if (!empty($OwnerChecksum) && $OwnerChecksum != '0') {
		// Get user by checksum
		$info = $userModel->GetUserByChecksum($OwnerChecksum);
		if (Jaws_Error::isError($info)) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $info->GetMessage());
		}
		if (isset($info['id'])) {
			$OwnerID = $info['id'];
		} else {
			$val = new XML_RPC_Value(array(), 'array');
			return new XML_RPC_Response($val);
		}
	} else {
		$OwnerID = null;
	}
	
	$res = $model->GetProductParents($limit, $sortColumn, $sortDir, $offSet, $OwnerID);
    if (Jaws_Error::isError($res)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $res->GetMessage());
    }
	
	$struct = array();
    $i = 0;
    foreach ($res as $r) {
        if ($i > 99) {
			break;
		}
        /*
		$publishtime = strtotime($entry['publishtime']);
        $publishtime = date('Ymd', $publishtime) . 'T' . date('H:i:s', $publishtime);
        $content = stripslashes($entry['text']);
        $data = array(
            'dateCreated' => new XML_RPC_Value($publishtime, 'dateTime.iso8601'),
            'content'     => new XML_RPC_Value($content),
        );
		*/
		$data = array();
		foreach ($r as $k => $v) {
			// URLs
			if (
				$k == 'productparentimage' || $k == 'productparenturl' 
				&& !empty($v) && file_exists(JAWS_DATA . 'files'.$v) && substr(strtolower($v), 0, 4) != 'http'
			) {
				$v = $GLOBALS['app']->getDataURL('', true) . 'files'.$v;
			}
			$data[$k] = XML_RPC_encode($v);
		}
        $struct[$i] = new XML_RPC_Value($data, 'struct');
        $i++;
    }

    if ($i > 0 ) {
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
 * GetProductParentsByUserID
 */
function GetProductParentsByUserID($params)
{
    // parameters
	$user 			= getScalarValue($params, 0);
	$password 		= getScalarValue($params, 1);
	$OwnerChecksum 	= getScalarValue($params, 2);
		
	$userModel = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $userModel->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+3,  _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetStorePermission($user, 'ManageProducts', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $model = $GLOBALS['app']->loadGadget('Store', 'Model');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }
	
	if (!empty($OwnerChecksum) && $OwnerChecksum != '0') {
		// Get user by checksum
		$info = $userModel->GetUserByChecksum($OwnerChecksum);
		if (Jaws_Error::isError($info)) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $info->GetMessage());
		}
		if (isset($info['id'])) {
			$OwnerID = $info['id'];
		} else {
			$val = new XML_RPC_Value(array(), 'array');
			return new XML_RPC_Response($val);
		}
	} else {
		$OwnerID = null;
	}
	
	$res = $model->GetProductParentsByUserID($OwnerID);
    if (Jaws_Error::isError($res)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $res->GetMessage());
    }
	
	$struct = array();
    $i = 0;
    foreach ($res as $r) {
        if ($i > 99) {
			break;
		}
        /*
		$publishtime = strtotime($entry['publishtime']);
        $publishtime = date('Ymd', $publishtime) . 'T' . date('H:i:s', $publishtime);
        $content = stripslashes($entry['text']);
        $data = array(
            'dateCreated' => new XML_RPC_Value($publishtime, 'dateTime.iso8601'),
            'content'     => new XML_RPC_Value($content),
        );
		*/
		$data = array();
		foreach ($r as $k => $v) {
			// URLs
			if (
				$k == 'productparentimage' || $k == 'productparenturl' 
				&& !empty($v) && file_exists(JAWS_DATA . 'files'.$v) && substr(strtolower($v), 0, 4) != 'http'
			) {
				$v = $GLOBALS['app']->getDataURL('', true) . 'files'.$v;
			}
			$data[$k] = XML_RPC_encode($v);
		}
        $struct[$i] = new XML_RPC_Value($data, 'struct');
        $i++;
    }

    if ($i > 0 ) {
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
 * GetStoreOwnersOfParent
 */
function GetStoreOwnersOfParent($params)
{
    // parameters
	$user 			= getScalarValue($params, 0);
	$password 		= getScalarValue($params, 1);
	$checksum 		= getScalarValue($params, 2);
		
	$model = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $model->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+3,  _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetStorePermission($user, 'ManageProducts', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $model = $GLOBALS['app']->loadGadget('Store', 'Model');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }
	
	// Get item by checksum
	$parent = $model->GetProductParentByChecksum($checksum);
    if (Jaws_Error::isError($parent)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $parent->GetMessage());
    }
	
	$res = $model->GetStoreOwnersOfParent($parent['productparentid']);
    if (Jaws_Error::isError($res)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $res->GetMessage());
    }
	
	$struct = array();
    $i = 0;
    foreach ($res as $r) {
        if ($i > 99) {
			break;
		}
        /*
		$publishtime = strtotime($entry['publishtime']);
        $publishtime = date('Ymd', $publishtime) . 'T' . date('H:i:s', $publishtime);
        $content = stripslashes($entry['text']);
        $data = array(
            'dateCreated' => new XML_RPC_Value($publishtime, 'dateTime.iso8601'),
            'content'     => new XML_RPC_Value($content),
        );
		*/
		$data = array();
		foreach ($r as $k => $v) {
			$data[$k] = XML_RPC_encode($v);
		}
        $struct[$i] = new XML_RPC_Value($data, 'struct');
        $i++;
    }

    if ($i > 0 ) {
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
 * UserOwnsStoreInParent
 */
function UserOwnsStoreInParent($params)
{
    // parameters
	$user 			= getScalarValue($params, 0);
	$password 		= getScalarValue($params, 1);
    $checksum		= getScalarValue($params, 2);
    $OwnerChecksum	= getScalarValue($params, 3);

	$userModel = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $userModel->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+3,  _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetStorePermission($user, 'ManageProducts', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $model = $GLOBALS['app']->loadGadget('Store', 'Model');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }
	
	if (!empty($OwnerChecksum) && $OwnerChecksum != '0') {
		// Get user by checksum
		$info = $userModel->GetUserByChecksum($OwnerChecksum);
		if (Jaws_Error::isError($info)) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $info->GetMessage());
		}
		if (isset($info['id'])) {
			$OwnerID = $info['id'];
		} else {
			$val = new XML_RPC_Value(array(), 'array');
			return new XML_RPC_Response($val);
		}
	} else {
		$OwnerID = null;
	}
	
	// Get item by checksum
	$parent = $model->GetProductParentByChecksum($checksum);
	if (Jaws_Error::isError($parent)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $parent->GetMessage());
    }
		
	$res = $model->UserOwnsStoreInParent($parent['productparentid'], $OwnerID);
	if ($res === true) {
		return new XML_RPC_Response(new XML_RPC_Value("true", 'boolean'));
    } else {
		return new XML_RPC_Response(new XML_RPC_Value("false", 'boolean'));
	}
}

/*
 * MassAddProductParent
 */
function MassAddProductParent($params)
{
	$param = $params->getParam(0);
    $par = $param->me['array'];
    //var_dump($par);
	foreach ($par as $p) {
		//var_dump($p);
		$msg = new XML_RPC_Message('AddProductParent', $p->me['array']);
		$res = AddProductParent($msg);
		$val = $res->value();
		if (!is_object($val) || !is_a($val, 'XML_RPC_value')) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, var_export($val, true));
		}
	}
	return new XML_RPC_Response(new XML_RPC_Value("true", 'boolean'));
}

/*
 * AddProductParent
 */
function AddProductParent($params)
{
    // parameters
	$user 						= getScalarValue($params, 0);
	$password 					= getScalarValue($params, 1);
    $productparentsort_order	= getScalarValue($params, 2);
    $productparentParent   		= getScalarValue($params, 3);
    $productparentCategory_Name = getScalarValue($params, 4);
    $productparentDescription   = getScalarValue($params, 5);
    $productparentImage    		= getScalarValue($params, 6);
    $productparentFeatured    	= getScalarValue($params, 7);
    $productparentActive    	= getScalarValue($params, 8);
    $productparentOwnerID    	= getScalarValue($params, 9);
    $productparentRss_url    	= getScalarValue($params, 10);
    $productparenturl_type    	= getScalarValue($params, 11);
    $productparentinternal_url	= getScalarValue($params, 12);
    $productparentexternal_url	= getScalarValue($params, 13);
    $productparenturl_target    = getScalarValue($params, 14);
    $productparentimage_code    = getScalarValue($params, 15);
    $productparentchecksum    	= getScalarValue($params, 16);

	if (empty($productparenturl_type)) {
		$productparenturl_type = 'imageviewer';
	}
	if (empty($productparentFeatured)) {
		$productparentFeatured = 'N';
	}
	if (empty($productparentActive)) {
		$productparentActive = 'Y';
	}
	if (empty($productparenturl_target)) {
		$productparenturl_target = '_self';
	}
    $productparentDescription = parseContent($productparentDescription);
    /*
	$productparentCategory_Name = parseContent($productparentCategory_Name);
    $productparentimage_code = parseContent($productparentimage_code);
	*/
	
	$userModel = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $userModel->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetStorePermission($user, 'ManageProducts', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $model = $GLOBALS['app']->loadGadget('Store', 'AdminModel');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }

	if (!empty($productparentOwnerID) && $productparentOwnerID != '0') {
		// Get user by checksum
		$info = $userModel->GetUserByChecksum($productparentOwnerID);
		if (Jaws_Error::isError($info)) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $info->GetMessage());
		}
		if (isset($info['id'])) {
			$OwnerID = $info['id'];
		} else {
			$val = new XML_RPC_Value("false", 'boolean');
			return new XML_RPC_Response($val);
		}
	} else {
		$OwnerID = null;
	}
	
	// Get menu by checksum
	$menuID = null;

    $post_id = $model->AddProductParent(
		$productparentsort_order, $menuID, $productparentCategory_Name, $productparentDescription, 
		$productparentImage, $productparentFeatured, $productparentActive, $OwnerID, $productparentRss_url, 
		$productparenturl_type, $productparentinternal_url, $productparentexternal_url, $productparenturl_target, 
		$productparentimage_code, $productparentchecksum, true
	);
    if (Jaws_Error::IsError($post_id)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, $post_id->GetMessage());
    }

    $val = new XML_RPC_Value("$post_id", 'string');
    return new XML_RPC_Response($val);
}

/*
 * MassUpdateProductParent
 */
function MassUpdateProductParent($params)
{
	$param = $params->getParam(0);
    $par = $param->me['array'];
    //var_dump($par);
	foreach ($par as $p) {
		//var_dump($p);
		$msg = new XML_RPC_Message('UpdateProductParent', $p->me['array']);
		$res = UpdateProductParent($msg);
		$val = $res->value();
		if (!is_object($val) || !is_a($val, 'XML_RPC_value')) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, var_export($val, true));
		}
	}
	return new XML_RPC_Response(new XML_RPC_Value("true", 'boolean'));
}

/*
 * UpdateProductParent
 */
function UpdateProductParent($params)
{
    // parameters
	$user 						= getScalarValue($params, 0);
	$password 					= getScalarValue($params, 1);
    $productparentchecksum		= getScalarValue($params, 2);
    $productparentParent   		= getScalarValue($params, 3);
    $productparentCategory_Name = getScalarValue($params, 4);
    $productparentsort_order	= getScalarValue($params, 5);
    $productparentDescription   = getScalarValue($params, 6);
    $productparentImage    		= getScalarValue($params, 7);
    $productparentFeatured    	= getScalarValue($params, 8);
    $productparentActive    	= getScalarValue($params, 9);
    $productparentRss_url    	= getScalarValue($params, 10);
    $productparenturl_type    	= getScalarValue($params, 11);
    $productparentinternal_url	= getScalarValue($params, 12);
    $productparentexternal_url	= getScalarValue($params, 13);
    $productparenturl_target    = getScalarValue($params, 14);
    $productparentimage_code    = getScalarValue($params, 15);

	if (empty($productparenturl_type)) {
		$productparenturl_type = 'imageviewer';
	}
	if (empty($productparentFeatured)) {
		$productparentFeatured = 'N';
	}
	if (empty($productparentActive)) {
		$productparentActive = 'Y';
	}
	if (empty($productparenturl_target)) {
		$productparenturl_target = '_self';
	}
    $productparentDescription = parseContent($productparentDescription);
    /*
	$productparentCategory_Name = parseContent($productparentCategory_Name);
    $productparentimage_code = parseContent($productparentimage_code);
	*/
	
	$model = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $model->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetStorePermission($user, 'ManageProducts', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $adminModel = $GLOBALS['app']->loadGadget('Store', 'AdminModel');
    if (Jaws_Error::isError($adminModel)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $adminModel->GetMessage());
    }
    $model = $GLOBALS['app']->loadGadget('Store', 'Model');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }

    // Get item by checksum
	$info = $model->GetProductParentByChecksum($productparentchecksum);
    /*
	if (Jaws_Error::isError($info)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $info->GetMessage());
    }
	*/
	if (!Jaws_Error::isError($info) && isset($info['productparentid']) && !empty($info['productparentid'])) {
    
		// Get menu by checksum
		$menuID = null;
			
		$post_id = $adminModel->UpdateProductParent(
			$info['productparentid'], $menuID, $productparentCategory_Name,  $productparentsort_order,
			$productparentDescription, $productparentImage, $productparentFeatured, $productparentActive, $productparentRss_url, 
			$productparenturl_type, $productparentinternal_url, $productparentexternal_url, $productparenturl_target, 
			$productparentimage_code, true
		);
		if (Jaws_Error::IsError($post_id)) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, $post_id->GetMessage());
		}

		$val = new XML_RPC_Value("$post_id", 'boolean');
		return new XML_RPC_Response($val);
	}
	$val = new XML_RPC_Value("false", 'boolean');
	return new XML_RPC_Response($val);
}

/*
 * DeleteProductParent
 */
function DeleteProductParent($params)
{
    // parameters
	$user 		= getScalarValue($params, 0);
	$password 	= getScalarValue($params, 1);
    $checksum	= getScalarValue($params, 2);
    $massive	= getScalarValue($params, 3);
	$massive = ((bool)$massive !== true ? false : true);

    /*
	$productparentCategory_Name = parseContent($productparentCategory_Name);
    $productparentDescription = parseContent($productparentDescription);
    $productparentimage_code = parseContent($productparentimage_code);
	*/
	
	$model = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $model->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetStorePermission($user, 'ManageProducts', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $model = $GLOBALS['app']->loadGadget('Store', 'Model');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }

    // Get item by checksum
	$info = $model->GetProductParentByChecksum($checksum);
    /*
	if (Jaws_Error::isError($info)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $info->GetMessage());
    }
	*/
	if (!Jaws_Error::isError($info) && isset($info['productparentid']) && !empty($info['productparentid'])) {
		$adminModel = $GLOBALS['app']->loadGadget('Store', 'AdminModel');
		if (Jaws_Error::isError($adminModel)) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $adminModel->GetMessage());
		}
    
		$post_id = $adminModel->DeleteProductParent($info['productparentid'], $massive);
		if (Jaws_Error::IsError($post_id)) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, $post_id->GetMessage());
		}

		$val = new XML_RPC_Value("$post_id", 'boolean');
		return new XML_RPC_Response($val);
	} else {
		$val = new XML_RPC_Value("false", 'boolean');
		return new XML_RPC_Response($val);
	}
}

/*
 * GetProduct
 */
function GetProduct($params)
{
    // parameters
	$user 		= getScalarValue($params, 0);
	$password 	= getScalarValue($params, 1);
    $checksum	= getScalarValue($params, 2);

	$model = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $model->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+3,  _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetStorePermission($user, 'ManageProducts', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $model = $GLOBALS['app']->loadGadget('Store', 'Model');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }
	
    // Get item by checksum
	$res = $model->GetProductByChecksum($checksum);
    if (Jaws_Error::isError($res)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $res->GetMessage());
    }
		
	$data = array();
	foreach ($res as $k => $v) {
		// URLs
		if (
			$k == 'image' && 
			!empty($v) && file_exists(JAWS_DATA . 'files'.$v) && substr(strtolower($v), 0, 4) != 'http'
		) {
			$v = $GLOBALS['app']->getDataURL('', true) . 'files'.$v;
		}
		$data[$k] = XML_RPC_encode($v);
	}
    
	$struct = array();
    $struct[]  = new XML_RPC_Value($data, 'struct');
    $data = array($struct[0]);
    $response = new XML_RPC_Value($data, 'array');
    return new XML_RPC_Response($response);
}

/*
 * GetSingleProductByUserID
 */
function GetSingleProductByUserID($params)
{
    // parameters
	$user 			= getScalarValue($params, 0);
	$password 		= getScalarValue($params, 1);
    $OwnerChecksum	= getScalarValue($params, 2);
    $checksum		= getScalarValue($params, 3);

	$userModel = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $userModel->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+3,  _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetStorePermission($user, 'ManageProducts', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $model = $GLOBALS['app']->loadGadget('Store', 'Model');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }
	
	if (!empty($OwnerChecksum) && $OwnerChecksum != '0') {
		// Get user by checksum
		$info = $userModel->GetUserByChecksum($OwnerChecksum);
		if (Jaws_Error::isError($info)) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $info->GetMessage());
		}
		if (isset($info['id'])) {
			$OwnerID = $info['id'];
		} else {
			$val = new XML_RPC_Value(array(), 'array');
			return new XML_RPC_Response($val);
		}
	} else {
		$OwnerID = null;
	}
	
    // Get item by checksum
	$product = $model->GetProductByChecksum($checksum);
    if (Jaws_Error::isError($product)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $product->GetMessage());
    }
		
    // Get product
	$res = $model->GetSingleProductByUserID($OwnerID, $product['id']);
    if (Jaws_Error::isError($res)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $res->GetMessage());
    }
		
	$data = array();
	foreach ($res as $k => $v) {
		// URLs
		if (
			$k == 'image' && 
			!empty($v) && file_exists(JAWS_DATA . 'files'.$v) && substr(strtolower($v), 0, 4) != 'http'
		) {
			$v = $GLOBALS['app']->getDataURL('', true) . 'files'.$v;
		}
		$data[$k] = XML_RPC_encode($v);
	}
    
	$struct = array();
    $struct[]  = new XML_RPC_Value($data, 'struct');
    $data = array($struct[0]);
    $response = new XML_RPC_Value($data, 'array');
    return new XML_RPC_Response($response);
}

/*
 * GetProducts
 */
function GetProducts($params)
{
    // parameters
	$user 		= getScalarValue($params, 0);
	$password 	= getScalarValue($params, 1);
	$limit 		= getScalarValue($params, 2);
	$sortColumn = getScalarValue($params, 3);
	$sortDir 	= getScalarValue($params, 4);
	$offSet 	= getScalarValue($params, 5);
	$OwnerChecksum 	= getScalarValue($params, 6);
	$active 	= getScalarValue($params, 7);
	$return 	= getScalarValue($params, 8);
	$search 	= getScalarValue($params, 9);
	
	if ($limit <= 0) {
		$limit = null;
	}
	if (empty($sortColumn)) {
		$sortColumn = 'sort_order';
	}
	if (empty($sortDir)) {
		$sortDir = 'ASC';
	}
	if (empty($active)) {
		$active = null;
	}
	if (empty($return)) {
		$return = null;
	}
	if ($offSet <= 0) {
		$offSet = false;
	}
    //return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+3, var_export($user, true).':'.var_export($password, true).':'.var_export($limit, true).':'.var_export($sortColumn, true).':'.var_export($sortDir, true).':'.var_export($offSet, true).':'.var_export($OwnerID, true).':'.var_export($active, true).':'.var_export($return, true).':'.var_export($search, true));
	
	$userModel = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $userModel->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+3,  _t('GLOBAL_ERROR_LOGIN_WRONG').' '.$user.':'.$password);
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetStorePermission($user, 'ManageProducts', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $model = $GLOBALS['app']->loadGadget('Store', 'Model');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }
	
	// Get user by checksum
	if (!empty($OwnerChecksum) && $OwnerChecksum != '0') {
		// Get user by checksum
		$info = $userModel->GetUserByChecksum($OwnerChecksum);
		if (Jaws_Error::isError($info)) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $info->GetMessage());
		}
		if (isset($info['id'])) {
			$OwnerID = $info['id'];
		} else {
			$val = new XML_RPC_Value(array(), 'array');
			return new XML_RPC_Response($val);
		}
	} else {
		$OwnerID = null;
	}
	
	$res = $model->GetProducts($limit, $sortColumn, $sortDir, $offSet, $OwnerID, $active, $return, $search);
    if (Jaws_Error::isError($res)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $res->GetMessage());
    }
	
	$struct = array();
    $i = 0;
    foreach ($res as $r) {
        if ($i > 99) {
			break;
		}
		/*
		$publishtime = strtotime($entry['publishtime']);
        $publishtime = date('Ymd', $publishtime) . 'T' . date('H:i:s', $publishtime);
        $content = stripslashes($entry['text']);
        $data = array(
            'dateCreated' => new XML_RPC_Value($publishtime, 'dateTime.iso8601'),
            'content'     => new XML_RPC_Value($content),
        );
		*/
		$data = array();
		foreach ($r as $k => $v) {
			// URLs
			if (
				$k == 'image' && 
				!empty($v) && file_exists(JAWS_DATA . 'files'.$v) && substr(strtolower($v), 0, 4) != 'http'
			) {
				$v = $GLOBALS['app']->getDataURL('', true) . 'files'.$v;
			}
			$data[$k] = XML_RPC_encode($v);
		}
        $struct[$i] = new XML_RPC_Value($data, 'struct');
        $i++;
    }

    if ($i > 0 ) {
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
 * GetAllProductsOfParent
 */
function GetAllProductsOfParent($params)
{
    // parameters
	$user 		= getScalarValue($params, 0);
	$password 	= getScalarValue($params, 1);
	$checksum	= getScalarValue($params, 2);
	$sortColumn = getScalarValue($params, 3);
	$sortDir 	= getScalarValue($params, 4);
	$active 	= getScalarValue($params, 5);
	$OwnerChecksum 	= getScalarValue($params, 6);
	
	if (empty($sortColumn)) {
		$sortColumn = 'sort_order';
	}
	if (empty($sortDir)) {
		$sortDir = 'ASC';
	}
	if (empty($active)) {
		$active = null;
	}
	
	$userModel = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $userModel->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+3,  _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetStorePermission($user, 'ManageProducts', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $model = $GLOBALS['app']->loadGadget('Store', 'Model');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }
	
    // Get item by checksum
	$info = $model->GetProductParentByChecksum($checksum);
    if (Jaws_Error::isError($info)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $info->GetMessage());
    }
	
	if (isset($info['id'])) {
		// Get user by checksum
		if (!empty($OwnerChecksum) && $OwnerChecksum != '0') {
			// Get user by checksum
			$user = $userModel->GetUserByChecksum($OwnerChecksum);
			if (Jaws_Error::isError($user)) {
				return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $user->GetMessage());
			}
			if (isset($user['id'])) {
				$OwnerID = $user['id'];
			} else {
				$val = new XML_RPC_Value(array(), 'array');
				return new XML_RPC_Response($val);
			}
		} else {
			$OwnerID = null;
		}
		
		$res = $model->GetAllProductsOfParent($info['id'], $sortColumn, $sortDir, $active, $OwnerID);
		if (Jaws_Error::isError($res)) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $res->GetMessage());
		}
		
		$struct = array();
		$i = 0;
		foreach ($res as $r) {
			if ($i > 99) {
				break;
			}
			/*
			$publishtime = strtotime($entry['publishtime']);
			$publishtime = date('Ymd', $publishtime) . 'T' . date('H:i:s', $publishtime);
			$content = stripslashes($entry['text']);
			$data = array(
				'dateCreated' => new XML_RPC_Value($publishtime, 'dateTime.iso8601'),
				'content'     => new XML_RPC_Value($content),
			);
			*/
			$data = array();
			foreach ($r as $k => $v) {
				// URLs
				if (
					$k == 'image' && 
					!empty($v) && file_exists(JAWS_DATA . 'files'.$v) && substr(strtolower($v), 0, 4) != 'http'
				) {
					$v = $GLOBALS['app']->getDataURL('', true) . 'files'.$v;
				}
				$data[$k] = XML_RPC_encode($v);
			}
			$struct[$i] = new XML_RPC_Value($data, 'struct');
			$i++;
		}

		if ($i > 0 ) {
			$data = array($struct[0]);
			for ($j = 1; $j < $i; $j++) {
				array_push($data, $struct[$j]);
			}
		} else {
			$data = array();
		}
		$response = new XML_RPC_Value($data, 'array');
		return new XML_RPC_Response($response);
	} else {
		$val = new XML_RPC_Value(array(), 'array');
		return new XML_RPC_Response($val);
	}
}

/*
 * GetStoreOfUserID
 */
function GetStoreOfUserID($params)
{
    // parameters
	$user 		= getScalarValue($params, 0);
	$password 	= getScalarValue($params, 1);
    $checksum	= getScalarValue($params, 2);
    $active 	= getScalarValue($params, 3);
	if (empty($active)) {
		$active = null;
	}

	$userModel = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $userModel->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+3,  _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetStorePermission($user, 'ManageProducts', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $model = $GLOBALS['app']->loadGadget('Store', 'Model');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }
		
	// Get user by checksum
	if (!empty($checksum) && $checksum != '0') {
		$user = $userModel->GetUserByChecksum($checksum);
		if (Jaws_Error::isError($user)) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $user->GetMessage());
		}
		if (isset($user['id'])) {
			$OwnerID = $user['id'];
		} else {
			$val = new XML_RPC_Value(array(), 'array');
			return new XML_RPC_Response($val);
		}
	} else {
		$OwnerID = 0;
	}
	
	$res = $model->GetStoreOfUserID($OwnerID, $active);
    if (Jaws_Error::isError($res)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $res->GetMessage());
    }
	$struct = array();
    $i = 0;
    foreach ($res as $r) {
        if ($i > 99) {
			break;
		}
        /*
		$publishtime = strtotime($entry['publishtime']);
        $publishtime = date('Ymd', $publishtime) . 'T' . date('H:i:s', $publishtime);
        $content = stripslashes($entry['text']);
        $data = array(
            'dateCreated' => new XML_RPC_Value($publishtime, 'dateTime.iso8601'),
            'content'     => new XML_RPC_Value($content),
        );
		*/
		$data = array();
		foreach ($r as $k => $v) {
			// URLs
			if (
				$k == 'image' && 
				!empty($v) && file_exists(JAWS_DATA . 'files'.$v) && substr(strtolower($v), 0, 4) != 'http'
			) {
				$v = $GLOBALS['app']->getDataURL('', true) . 'files'.$v;
			}
			$data[$k] = XML_RPC_encode($v);
		}
        $struct[$i] = new XML_RPC_Value($data, 'struct');
        $i++;
    }

    if ($i > 0 ) {
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
 * GetStoreOfGroup
 */
function GetStoreOfGroup($params)
{
    // parameters
	$user 		= getScalarValue($params, 0);
	$password 	= getScalarValue($params, 1);
    $checksum	= getScalarValue($params, 2);
    $sortColumn = getScalarValue($params, 3);
    $sortDir 	= getScalarValue($params, 4);
    $active 	= getScalarValue($params, 5);
	if (empty($sortColumn)) {
		$sortColumn = 'sort_order';
	}
	if (empty($sortDir)) {
		$sortDir = 'ASC';
	}
	if (empty($active)) {
		$active = null;
	}

	$userModel = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $userModel->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+3,  _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetStorePermission($user, 'ManageProducts', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $model = $GLOBALS['app']->loadGadget('Store', 'Model');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }
	
	// Get group by checksum
	if (!empty($checksum)) {
		$group = $userModel->GetUserByChecksum($checksum);
		if (Jaws_Error::isError($group)) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $group->GetMessage());
		}
		if (isset($group['id'])) {
			$gid = $group['id'];
		} else {
			$val = new XML_RPC_Value(array(), 'array');
			return new XML_RPC_Response($val);
		}
	} else {
		$gid = 0;
	}
	
	$res = $model->GetStoreOfGroup($gid, $sortColumn, $sortDir, $active);
    if (Jaws_Error::isError($res)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $res->GetMessage());
    }
	$struct = array();
    $i = 0;
    foreach ($res as $r) {
        if ($i > 99) {
			break;
		}
        /*
		$publishtime = strtotime($entry['publishtime']);
        $publishtime = date('Ymd', $publishtime) . 'T' . date('H:i:s', $publishtime);
        $content = stripslashes($entry['text']);
        $data = array(
            'dateCreated' => new XML_RPC_Value($publishtime, 'dateTime.iso8601'),
            'content'     => new XML_RPC_Value($content),
        );
		*/
		$data = array();
		foreach ($r as $k => $v) {
			// URLs
			if (
				$k == 'image' && 
				!empty($v) && file_exists(JAWS_DATA . 'files'.$v) && substr(strtolower($v), 0, 4) != 'http'
			) {
				$v = $GLOBALS['app']->getDataURL('', true) . 'files'.$v;
			}
			$data[$k] = XML_RPC_encode($v);
		}
        $struct[$i] = new XML_RPC_Value($data, 'struct');
        $i++;
    }

    if ($i > 0 ) {
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
 * MassAddProduct
 */
function MassAddProduct($params)
{
	$param = $params->getParam(0);
    $par = $param->me['array'];
    //var_dump($par);
	foreach ($par as $p) {
		//var_dump($p);
		$msg = new XML_RPC_Message('AddProduct', $p->me['array']);
		$res = AddProduct($msg);
		$val = $res->value();
		if (!is_object($val) || !is_a($val, 'XML_RPC_value')) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, var_export($val, true));
		}
	}
	return new XML_RPC_Response(new XML_RPC_Value("true", 'boolean'));
}

/*
 * AddProductParent
 */
function AddProduct($params)
{
	// parameters
	$user 				= getScalarValue($params, 0);
	$password 			= getScalarValue($params, 1);
    $BrandID			= getScalarValue($params, 2);
    $sort_order   		= getScalarValue($params, 3);
    $category 			= getScalarValue($params, 4);
    $product_code   	= getScalarValue($params, 5);
    $title    			= getScalarValue($params, 6);
    $image    			= getScalarValue($params, 7);
    $sm_description    	= getScalarValue($params, 8);
    $description    	= getScalarValue($params, 9);
    $weight    			= getScalarValue($params, 10);
    $retail    			= getScalarValue($params, 11);
    $price				= getScalarValue($params, 12);
    $cost				= getScalarValue($params, 13);
    $setup_fee    		= getScalarValue($params, 14);
    $unit    			= getScalarValue($params, 15);
    $recurring    		= getScalarValue($params, 16);
    $inventory    		= getScalarValue($params, 17);
    $instock    		= getScalarValue($params, 18);
    $lowstock    		= getScalarValue($params, 19);
    $outstockmsg    	= getScalarValue($params, 20);
    $outstockbuy    	= getScalarValue($params, 21);
    $attribute    		= getScalarValue($params, 22);
    $premium    		= getScalarValue($params, 23);
    $featured    		= getScalarValue($params, 24);
    $OwnerChecksum    	= getScalarValue($params, 25);
    $Active    			= getScalarValue($params, 26);
    $internal_productno	= getScalarValue($params, 27);
    $alink    			= getScalarValue($params, 28);
    $alinkTitle    		= getScalarValue($params, 29);
    $alinkType    		= getScalarValue($params, 30);
    $alink2    			= getScalarValue($params, 31);
    $alink2Title    	= getScalarValue($params, 32);
    $alink2Type    		= getScalarValue($params, 33);
    $alink3    			= getScalarValue($params, 34);
    $alink3Title    	= getScalarValue($params, 35);
    $alink3Type    		= getScalarValue($params, 36);
    $rss_url    		= getScalarValue($params, 37);
    $contact   		 	= getScalarValue($params, 38);
    $contact_email    	= getScalarValue($params, 39);
    $contact_phone    	= getScalarValue($params, 40);
    $contact_website    = getScalarValue($params, 41);
    $contact_photo    	= getScalarValue($params, 42);
    $company    		= getScalarValue($params, 43);
    $company_email    	= getScalarValue($params, 44);
    $company_phone    	= getScalarValue($params, 45);
    $company_website    = getScalarValue($params, 46);
    $company_logo    	= getScalarValue($params, 47);
    $subscribe_method	= getScalarValue($params, 48);
    $sales    			= getScalarValue($params, 49);
    $min_qty    		= getScalarValue($params, 50);
    $checksum    		= getScalarValue($params, 51);

	if (empty($weight)) {
		$weight = '0';
	}
	if (empty($retail)) {
		$retail = '0';
	}
	if (empty($price)) {
		$price = '0';
	}
	if (empty($cost)) {
		$cost = '0';
	}
	if (empty($setup_fee)) {
		$setup_fee = '0';
	}
	if (empty($unit)) {
		$unit = '/ Each';
	}
	if (empty($recurring)) {
		$recurring = 'N';
	}
	if (empty($inventory)) {
		$inventory = 'N';
	}
	if (empty($instock)) {
		$instock = '1';
	}
	if (empty($lowstock)) {
		$lowstock = '-1';
	}
	if (empty($outstockmsg)) {
		$outstockmsg = "This product is sold out. Check back soon.";
	}
	if (empty($outstockbuy)) {
		$outstockbuy = 'N';
	}
	if (empty($premium)) {
		$premium = 'N';
	}
	if (empty($featured)) {
		$featured = 'N';
	}
	if (empty($Active)) {
		$Active = 'N';
	}
    $description = parseContent($description);
    /*
	$productparentCategory_Name = parseContent($productparentCategory_Name);
    $productparentimage_code = parseContent($productparentimage_code);
	*/
	
	$userModel = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $userModel->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetStorePermission($user, 'ManageProducts', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $adminModel = $GLOBALS['app']->loadGadget('Store', 'AdminModel');
    if (Jaws_Error::isError($adminModel)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $adminModel->GetMessage());
    }
    $model = $GLOBALS['app']->loadGadget('Store', 'Model');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }
	
	// Get user by checksum
	if (!empty($OwnerChecksum) && $OwnerChecksum != '0') {
		$user = $userModel->GetUserByChecksum($OwnerChecksum);
		if (Jaws_Error::isError($user)) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $user->GetMessage());
		}
		if (isset($user['id'])) {
			$OwnerID = $user['id'];
		} else {
			$val = new XML_RPC_Value("false", 'boolean');
			return new XML_RPC_Response($val);
		}
	} else {
		$OwnerID = null;
	}
	
	// Get sales by checksum
	$salesIDs = '';
	if (!empty($sales)) {
		$sales = explode(',', $sales);
		foreach ($sales as $sale) {
			$saleInfo = $model->GetSaleByChecksum($sale);
			/*
			if (Jaws_Error::isError($saleInfo)) {
				return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $saleInfo->GetMessage());
			}
			*/
			if (!Jaws_Error::isError($saleInfo) && isset($saleInfo['id'])) {
				$salesIDs .= (!empty($salesIDs) ? ',' : '').$saleInfo['id'];
			/*
			} else {
				$val = new XML_RPC_Value("false", 'boolean');
				return new XML_RPC_Response($val);
			*/
			}
		}
	}

	// Get categories by checksum
	$categoryIDs = '';
	if (!empty($category)) {
		$category = explode(',', $category);
		foreach ($category as $cat) {
			$catInfo = $model->GetProductParentByChecksum($cat);
			/*
			if (Jaws_Error::isError($catInfo)) {
				return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $catInfo->GetMessage());
			}
			*/
			if (!Jaws_Error::isError($catInfo) && isset($catInfo['productparentid'])) {
				$categoryIDs .= (!empty($categoryIDs) ? ',' : '').$catInfo['productparentid'];
			/*
			} else {
				$val = new XML_RPC_Value("false", 'boolean');
				return new XML_RPC_Response($val);
			*/
			}
		}
	}

	// Get attributes by checksum
	$attributeIDs = '';
	if (!empty($attribute)) {
		$attribute = explode(',', $attribute);
		foreach ($attribute as $att) {
			$attInfo = $model->GetAttributeByChecksum($att);
			/*
			if (Jaws_Error::isError($attInfo)) {
				return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $attInfo->GetMessage());
			}
			*/
			if (!Jaws_Error::isError($attInfo) && isset($attInfo['id'])) {
				$attributeIDs .= (!empty($attributeIDs) ? ',' : '').$attInfo['id'];
			/*
			} else {
				$val = new XML_RPC_Value("false", 'boolean');
				return new XML_RPC_Response($val);
			*/
			}
		}
	}

	// Get BrandID by checksum
	$brandIDs = 0;
	if (!empty($BrandID)) {
		$brandInfo = $model->GetBrandByChecksum($BrandID);
		/*
		if (Jaws_Error::isError($brandInfo)) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $brandInfo->GetMessage());
		}
		*/
		if (!Jaws_Error::isError($brandInfo) && isset($brandInfo['id'])) {
			$brandIDs = $brandInfo['id'];
		/*
		} else {
			$val = new XML_RPC_Value("false", 'boolean');
			return new XML_RPC_Response($val);
		*/
		}
	}

    $post_id = $adminModel->AddProduct(
		$brandIDs, $sort_order, $categoryIDs, $product_code, $title, $image, 
		$sm_description, $description, $weight, $retail, $price, $cost, 
		$setup_fee, $unit, $recurring, $inventory, $instock, 
		$lowstock, $outstockmsg, $outstockbuy, $attributeIDs, $premium, $featured, 
		$OwnerID, $Active, $internal_productno, $alink, $alinkTitle, 
		$alinkType, $alink2, $alink2Title, $alink2Type, $alink3, $alink3Title, $alink3Type, 
		$rss_url, $contact, $contact_email, $contact_phone, $contact_website, $contact_photo, $company, 
		$company_email, $company_phone, $company_website, $company_logo, $subscribe_method, $salesIDs, 
		$min_qty, $checksum, true
	);
    if (Jaws_Error::IsError($post_id)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, $post_id->GetMessage());
    }
	$update = $adminModel->ActivateProductsCategories($info['id']);
	if (Jaws_Error::IsError($update)) {
		return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, $update->GetMessage());
	}

    $val = new XML_RPC_Value("$post_id", 'string');
    return new XML_RPC_Response($val);
}

/*
 * UpdateProductParent
 */
function UpdateProduct($params)
{
    // parameters
	$user 				= getScalarValue($params, 0);
	$password 			= getScalarValue($params, 1);
    $checksum			= getScalarValue($params, 2);
    $BrandID			= getScalarValue($params, 3);
    $sort_order   		= getScalarValue($params, 4);
    $category 			= getScalarValue($params, 5);
    $product_code   	= getScalarValue($params, 6);
    $title    			= getScalarValue($params, 7);
    $image    			= getScalarValue($params, 8);
    $sm_description    	= getScalarValue($params, 9);
    $description    	= getScalarValue($params, 10);
    $weight    			= getScalarValue($params, 11);
    $retail    			= getScalarValue($params, 12);
    $price				= getScalarValue($params, 13);
    $cost				= getScalarValue($params, 14);
    $setup_fee    		= getScalarValue($params, 15);
    $unit    			= getScalarValue($params, 16);
    $recurring    		= getScalarValue($params, 17);
    $inventory    		= getScalarValue($params, 18);
    $instock    		= getScalarValue($params, 19);
    $lowstock    		= getScalarValue($params, 20);
    $outstockmsg    	= getScalarValue($params, 21);
    $outstockbuy    	= getScalarValue($params, 22);
    $attribute    		= getScalarValue($params, 23);
    $premium    		= getScalarValue($params, 24);
    $featured    		= getScalarValue($params, 25);
    $Active    			= getScalarValue($params, 26);
    $internal_productno	= getScalarValue($params, 27);
    $alink    			= getScalarValue($params, 28);
    $alinkTitle    		= getScalarValue($params, 29);
    $alinkType    		= getScalarValue($params, 30);
    $alink2    			= getScalarValue($params, 31);
    $alink2Title    	= getScalarValue($params, 32);
    $alink2Type    		= getScalarValue($params, 33);
    $alink3    			= getScalarValue($params, 34);
    $alink3Title    	= getScalarValue($params, 35);
    $alink3Type    		= getScalarValue($params, 36);
    $rss_url    		= getScalarValue($params, 37);
    $contact   		 	= getScalarValue($params, 38);
    $contact_email    	= getScalarValue($params, 39);
    $contact_phone    	= getScalarValue($params, 40);
    $contact_website    = getScalarValue($params, 41);
    $contact_photo    	= getScalarValue($params, 42);
    $company    		= getScalarValue($params, 43);
    $company_email    	= getScalarValue($params, 44);
    $company_phone    	= getScalarValue($params, 45);
    $company_website    = getScalarValue($params, 46);
    $company_logo    	= getScalarValue($params, 47);
    $subscribe_method	= getScalarValue($params, 48);
    $sales    			= getScalarValue($params, 49);
    $min_qty    		= getScalarValue($params, 50);

	if (empty($weight)) {
		$weight = '0';
	}
	if (empty($retail)) {
		$retail = '0';
	}
	if (empty($price)) {
		$price = '0';
	}
	if (empty($cost)) {
		$cost = '0';
	}
	if (empty($setup_fee)) {
		$setup_fee = '0';
	}
	if (empty($recurring)) {
		$recurring = 'N';
	}
	if (empty($inventory)) {
		$inventory = 'N';
	}
	if (empty($instock)) {
		$instock = '1';
	}
	if (empty($lowstock)) {
		$lowstock = '-1';
	}
	if (empty($outstockbuy)) {
		$outstockbuy = 'N';
	}
	if (empty($premium)) {
		$premium = 'N';
	}
	if (empty($featured)) {
		$featured = 'N';
	}
	if (empty($Active)) {
		$Active = 'N';
	}

    $description = parseContent($description);
    /*
	$productparentCategory_Name = parseContent($productparentCategory_Name);
    $productparentimage_code = parseContent($productparentimage_code);
	*/
	
	$userModel = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $userModel->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetStorePermission($user, 'ManageProducts', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $adminModel = $GLOBALS['app']->loadGadget('Store', 'AdminModel');
    if (Jaws_Error::isError($adminModel)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $adminModel->GetMessage());
    }
    $model = $GLOBALS['app']->loadGadget('Store', 'Model');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }

    // Get item by checksum
	$info = $model->GetProductByChecksum($checksum);
    /*
	if (Jaws_Error::isError($info)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $info->GetMessage());
    }
	*/
	if (!Jaws_Error::isError($info) && isset($info['id']) && !empty($info['id'])) {
	
		// Get sales by checksum
		$salesIDs = '';
		if (!empty($sales)) {
			$sales = explode(',', $sales);
			foreach ($sales as $sale) {
				$saleInfo = $model->GetSaleByChecksum($sale);
				/*
				if (Jaws_Error::isError($saleInfo)) {
					return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $saleInfo->GetMessage());
				}
				*/
				if (!Jaws_Error::isError($saleInfo) && isset($saleInfo['id'])) {
					$salesIDs .= (!empty($salesIDs) ? ',' : '').$saleInfo['id'];
				/*
				} else {
					$val = new XML_RPC_Value("false", 'boolean');
					return new XML_RPC_Response($val);
				*/
				}
			}
		}

		// Get categories by checksum
		$categoryIDs = '';
		if (!empty($category)) {
			$category = explode(',', $category);
			foreach ($category as $cat) {
				$catInfo = $model->GetProductParentByChecksum($cat);
				/*
				if (Jaws_Error::isError($catInfo)) {
					return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $catInfo->GetMessage());
				}
				*/
				if (!Jaws_Error::isError($catInfo) && isset($catInfo['productparentid'])) {
					$categoryIDs .= (!empty($categoryIDs) ? ',' : '').$catInfo['productparentid'];
				/*
				} else {
					$val = new XML_RPC_Value("false", 'boolean');
					return new XML_RPC_Response($val);
				*/
				}
			}
		}

		// Get attributes by checksum
		$attributeIDs = '';
		if (!empty($attribute)) {
			$attribute = explode(',', $attribute);
			foreach ($attribute as $att) {
				$attInfo = $model->GetAttributeByChecksum($att);
				/*
				if (Jaws_Error::isError($attInfo)) {
					return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $attInfo->GetMessage());
				}
				*/
				if (!Jaws_Error::isError($attInfo) && isset($attInfo['id'])) {
					$attributeIDs .= (!empty($attributeIDs) ? ',' : '').$attInfo['id'];
				/*
				} else {
					$val = new XML_RPC_Value("false", 'boolean');
					return new XML_RPC_Response($val);
				*/
				}
			}
		}

		// Get BrandID by checksum
		$brandIDs = 0;
		if (!empty($BrandID)) {
			$brandInfo = $model->GetBrandByChecksum($BrandID);
			/*
			if (Jaws_Error::isError($brandInfo)) {
				return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $brandInfo->GetMessage());
			}
			*/
			if (!Jaws_Error::isError($brandInfo) && isset($brandInfo['id'])) {
				$brandIDs = $brandInfo['id'];
			/*
			} else {
				$val = new XML_RPC_Value("false", 'boolean');
				return new XML_RPC_Response($val);
			*/
			}
		}

		$post_id = $adminModel->UpdateProduct(
			$info['id'], $brandIDs, $sort_order, $categoryIDs, $product_code, $title, $image, 
			$sm_description, $description, $weight, $retail, $price, $cost, 
			$setup_fee, $unit, $recurring, $inventory, $instock, 
			$lowstock, $outstockmsg, $outstockbuy, $attributeIDs, $premium, $featured,
			$Active, $internal_productno, $alink, $alinkTitle, 
			$alinkType, $alink2, $alink2Title, $alink2Type, $alink3, $alink3Title, $alink3Type, 
			$rss_url, $contact, $contact_email, $contact_phone, $contact_website, $contact_photo, $company, 
			$company_email, $company_phone, $company_website, $company_logo, $subscribe_method, $salesIDs, $min_qty, true
		);
		if (Jaws_Error::IsError($post_id)) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, $post_id->GetMessage());
		}

		$update = $adminModel->UpdateProductsCategories($info['id']);
		if (Jaws_Error::IsError($update)) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, $update->GetMessage());
		}
		$val = new XML_RPC_Value("$post_id", 'boolean');
		return new XML_RPC_Response($val);
	} else {
		$val = new XML_RPC_Value("false", 'boolean');
		return new XML_RPC_Response($val);
	}
}

/*
 * DeleteProductParent
 */
function DeleteProduct($params)
{
    // parameters
	$user 		= getScalarValue($params, 0);
	$password 	= getScalarValue($params, 1);
    $checksum	= getScalarValue($params, 2);
    $massive	= getScalarValue($params, 3);
	$massive = ((bool)$massive !== true ? false : true);
	
	$userModel = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $userModel->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetStorePermission($user, 'ManageProducts', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $model = $GLOBALS['app']->loadGadget('Store', 'Model');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }

    // Get item by checksum
	$info = $model->GetProductByChecksum($checksum);
    /*
	if (Jaws_Error::isError($info)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $info->GetMessage());
    }
	*/
		
	if (!Jaws_Error::isError($info) && isset($info['id']) && !empty($info['id'])) {
		$adminModel = $GLOBALS['app']->loadGadget('Store', 'AdminModel');
		if (Jaws_Error::isError($adminModel)) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $adminModel->GetMessage());
		}
		$post_id = $adminModel->DeleteProduct($info['id'], $massive);
		if (Jaws_Error::IsError($post_id)) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, $post_id->GetMessage());
		}

		$val = new XML_RPC_Value("$post_id", 'boolean');
		return new XML_RPC_Response($val);
	} else {
		$val = new XML_RPC_Value("false", 'boolean');
		return new XML_RPC_Response($val);
	}
}

/*
 * GetPost
 */
function GetPost($params)
{
    // parameters
	$user 		= getScalarValue($params, 0);
	$password 	= getScalarValue($params, 1);
    $checksum	= getScalarValue($params, 2);

	$userModel = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $userModel->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+3,  _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetStorePermission($user, 'ManageProducts', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $model = $GLOBALS['app']->loadGadget('Store', 'Model');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }
	
    // Get item by checksum
	$res = $model->GetPostByChecksum($checksum);
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
 * GetAllPostsOfProduct
 */
function GetAllPostsOfProduct($params)
{
    // parameters
	$user 		= getScalarValue($params, 0);
	$password 	= getScalarValue($params, 1);
    $checksum	= getScalarValue($params, 2);

	$userModel = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $userModel->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+3,  _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetStorePermission($user, 'ManageProducts', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $model = $GLOBALS['app']->loadGadget('Store', 'Model');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }
	
    // Get item by checksum
	$info = $model->GetProductByChecksum($checksum);
    if (Jaws_Error::isError($info)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $info->GetMessage());
    }
	
	if (isset($info['id'])) {
		$res = $model->GetAllPostsOfProduct($info['id']);
		if (Jaws_Error::isError($res)) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $res->GetMessage());
		}
		$struct = array();
		$i = 0;
		foreach ($res as $r) {
			if ($i > 99) {
				break;
			}
			/*
			$publishtime = strtotime($entry['publishtime']);
			$publishtime = date('Ymd', $publishtime) . 'T' . date('H:i:s', $publishtime);
			$content = stripslashes($entry['text']);
			$data = array(
				'dateCreated' => new XML_RPC_Value($publishtime, 'dateTime.iso8601'),
				'content'     => new XML_RPC_Value($content),
			);
			*/
			$data = array();
			foreach ($r as $k => $v) {
				$data[$k] = XML_RPC_encode($v);
			}
			$struct[$i] = new XML_RPC_Value($data, 'struct');
			$i++;
		}

		if ($i > 0 ) {
			$data = array($struct[0]);
			for ($j = 1; $j < $i; $j++) {
				array_push($data, $struct[$j]);
			}
		} else {
			$data = array();
		}
		
		$response = new XML_RPC_Value($data, 'array');
		return new XML_RPC_Response($response);
	} else {
		$val = new XML_RPC_Value(array(), 'array');
		return new XML_RPC_Response($val);
	}
}

/*
 * MassAddPost
 */
function MassAddPost($params)
{
	$param = $params->getParam(0);
    $par = $param->me['array'];
    //var_dump($par);
	foreach ($par as $p) {
		//var_dump($p);
		$msg = new XML_RPC_Message('AddPost', $p->me['array']);
		$res = AddPost($msg);
		$val = $res->value();
		if (!is_object($val) || !is_a($val, 'XML_RPC_value')) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, var_export($msg, true).' : '.var_export($res, true).' : '.var_export($val, true));
		}
	}
	return new XML_RPC_Response(new XML_RPC_Value("true", 'boolean'));
}

/*
 * AddPost
 */
function AddPost($params)
{
	// parameters
	$user 				= getScalarValue($params, 0);
	$password 			= getScalarValue($params, 1);
    $sort_order			= getScalarValue($params, 2);
    $LinkChecksum		= getScalarValue($params, 3);
    $title 				= getScalarValue($params, 4);
    $description   		= getScalarValue($params, 5);
    $image    			= getScalarValue($params, 6);
    $image_width    	= getScalarValue($params, 7);
    $image_height    	= getScalarValue($params, 8);
    $layout    			= getScalarValue($params, 9);
    $active    			= getScalarValue($params, 10);
    $OwnerChecksum    	= getScalarValue($params, 11);
    $url_type			= getScalarValue($params, 12);
    $internal_url		= getScalarValue($params, 13);
    $external_url    	= getScalarValue($params, 14);
    $url_target    		= getScalarValue($params, 15);
    $checksum    		= getScalarValue($params, 16);

	if (empty($url_target)) {
		$url_target = '_self';
	}
	if (empty($active)) {
		$active = 'Y';
	}
    $description = parseContent($description);
    /*
	$productparentCategory_Name = parseContent($productparentCategory_Name);
    $productparentimage_code = parseContent($productparentimage_code);
	*/
	
	$userModel = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $userModel->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetStorePermission($user, 'ManageProducts', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $adminModel = $GLOBALS['app']->loadGadget('Store', 'AdminModel');
    if (Jaws_Error::isError($adminModel)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $adminModel->GetMessage());
    }
    $model = $GLOBALS['app']->loadGadget('Store', 'Model');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }
    
	// Get item by checksum
	$info = $model->GetProductByChecksum($LinkChecksum);
    /*
	if (Jaws_Error::isError($info)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $info->GetMessage());
    }
	*/
	
	if (!Jaws_Error::isError($info) && isset($info['id']) && !empty($info['id'])) {
		// Get user by checksum
		if (!empty($OwnerChecksum) && $OwnerChecksum != '0') {
			$user = $userModel->GetUserByChecksum($OwnerChecksum);
			if (Jaws_Error::isError($user)) {
				return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $user->GetMessage());
			}
			if (isset($user['id'])) {
				$OwnerID = $user['id'];
			} else {
				$val = new XML_RPC_Value("false", 'boolean');
				return new XML_RPC_Response($val);
			}
		} else {
			$OwnerID = null;
		}

		$post_id = $adminModel->AddPost(
			$sort_order, $info['id'], $title, $description, $image, $image_width, $image_height, 
			$layout, $active, $OwnerID, $url_type, $internal_url, $external_url, 
			$url_target, $checksum, true
		);
		if (Jaws_Error::IsError($post_id)) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, $post_id->GetMessage());
		}

		$val = new XML_RPC_Value("$post_id", 'string');
		return new XML_RPC_Response($val);
	} else {
		$val = new XML_RPC_Value("false", 'boolean');
		return new XML_RPC_Response($val);
	}
}

/*
 * UpdatePost
 */
function UpdatePost($params)
{
	// parameters
	$user 				= getScalarValue($params, 0);
	$password 			= getScalarValue($params, 1);
    $checksum			= getScalarValue($params, 2);
    $sort_order			= getScalarValue($params, 3);
    $title   			= getScalarValue($params, 4);
    $description 		= getScalarValue($params, 5);
    $image   			= getScalarValue($params, 6);
    $image_width    	= getScalarValue($params, 7);
    $image_height    	= getScalarValue($params, 8);
    $layout    			= getScalarValue($params, 9);
    $active    			= getScalarValue($params, 10);
    $url_type    		= getScalarValue($params, 11);
    $internal_url    	= getScalarValue($params, 12);
    $external_url		= getScalarValue($params, 13);
    $url_target			= getScalarValue($params, 14);

	if (empty($url_target)) {
		$url_target = '_self';
	}

    $description = parseContent($description);
    /*
	$productparentCategory_Name = parseContent($productparentCategory_Name);
    $productparentimage_code = parseContent($productparentimage_code);
	*/
	
	$userModel = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $userModel->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetStorePermission($user, 'ManageProducts', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $adminModel = $GLOBALS['app']->loadGadget('Store', 'AdminModel');
    if (Jaws_Error::isError($adminModel)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $adminModel->GetMessage());
    }
    $model = $GLOBALS['app']->loadGadget('Store', 'Model');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }

    // Get item by checksum
	$info = $model->GetPostByChecksum($checksum);
    /*
	if (Jaws_Error::isError($info)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $info->GetMessage());
    }
	*/
	if (!Jaws_Error::isError($info) && isset($info['id']) && !empty($info['id'])) {
		$post_id = $adminModel->UpdatePost(
			$info['id'], $sort_order, $title, $description, $image, $image_width, $image_height, 
			$layout, $active, $url_type, $internal_url, $external_url, $url_target, true
		);
		if (Jaws_Error::IsError($post_id)) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, $post_id->GetMessage());
		}

		$val = new XML_RPC_Value("$post_id", 'boolean');
		return new XML_RPC_Response($val);
	} else {
		$val = new XML_RPC_Value("false", 'boolean');
		return new XML_RPC_Response($val);
	}
}

/*
 * DeletePost
 */
function DeletePost($params)
{
    // parameters
	$user 		= getScalarValue($params, 0);
	$password 	= getScalarValue($params, 1);
    $checksum	= getScalarValue($params, 2);
    $massive	= getScalarValue($params, 3);
	$massive = ((bool)$massive !== true ? false : true);
	
	$userModel = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $userModel->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetStorePermission($user, 'ManageProducts', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $model = $GLOBALS['app']->loadGadget('Store', 'Model');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }

    // Get item by checksum
	$info = $model->GetPostByChecksum($checksum);
    /*
	if (Jaws_Error::isError($info)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $info->GetMessage());
    }
	*/
		
	if (!Jaws_Error::isError($info) && isset($info['id']) && !empty($info['id'])) {
		$adminModel = $GLOBALS['app']->loadGadget('Store', 'AdminModel');
		if (Jaws_Error::isError($adminModel)) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $adminModel->GetMessage());
		}
		$post_id = $adminModel->DeletePost($info['id'], $massive);
		if (Jaws_Error::IsError($post_id)) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, $post_id->GetMessage());
		}

		$val = new XML_RPC_Value("$post_id", 'boolean');
		return new XML_RPC_Response($val);
	} else {
		$val = new XML_RPC_Value("false", 'boolean');
		return new XML_RPC_Response($val);
	}
}

/*
 * GetAttribute
 */
function GetAttribute($params)
{
    // parameters
	$user 		= getScalarValue($params, 0);
	$password 	= getScalarValue($params, 1);
    $checksum	= getScalarValue($params, 2);

	$userModel = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $userModel->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+3,  _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetStorePermission($user, 'ManageProducts', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $model = $GLOBALS['app']->loadGadget('Store', 'Model');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }
	
    // Get item by checksum
	$res = $model->GetAttributeByChecksum($checksum);
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
 * GetProductAttributes
 */
function GetProductAttributes($params)
{
    // parameters
	$user 			= getScalarValue($params, 0);
	$password 		= getScalarValue($params, 1);
    $limit			= getScalarValue($params, 2);
    $sortColumn		= getScalarValue($params, 3);
    $sortDir		= getScalarValue($params, 4);
    $offSet			= getScalarValue($params, 5);
	$offSet 		= ((bool)$offSet !== true ? false : true);
    $OwnerChecksum	= getScalarValue($params, 6);

	if (empty($sortColumn)) {
		$sortColumn = 'sort_order';
	}
	if (empty($sortDir)) {
		$sortDir = 'ASC';
	}
	
	$userModel = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $userModel->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+3,  _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetStorePermission($user, 'ManageProducts', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $model = $GLOBALS['app']->loadGadget('Store', 'Model');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }
	
	// Get user by checksum
	if (!empty($OwnerChecksum) && $OwnerChecksum != '0') {
		$user = $userModel->GetUserByChecksum($OwnerChecksum);
		if (Jaws_Error::isError($user)) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $user->GetMessage());
		}
		if (isset($user['id'])) {
			$OwnerID = $user['id'];
		} else {
			$val = new XML_RPC_Value(array(), 'array');
			return new XML_RPC_Response($val);
		}
	} else {
		$OwnerID = null;
	}
	
	$res = $model->GetProductAttributes($limit, $sortColumn, $sortDir, $offSet, $OwnerID);
    if (Jaws_Error::isError($res)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $res->GetMessage());
    }
	$struct = array();
    $i = 0;
    foreach ($res as $r) {
        if ($i > 99) {
			break;
		}
        /*
		$publishtime = strtotime($entry['publishtime']);
        $publishtime = date('Ymd', $publishtime) . 'T' . date('H:i:s', $publishtime);
        $content = stripslashes($entry['text']);
        $data = array(
            'dateCreated' => new XML_RPC_Value($publishtime, 'dateTime.iso8601'),
            'content'     => new XML_RPC_Value($content),
        );
		*/
		$data = array();
		foreach ($r as $k => $v) {
			$data[$k] = XML_RPC_encode($v);
		}
        $struct[$i] = new XML_RPC_Value($data, 'struct');
        $i++;
    }

    if ($i > 0 ) {
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
 * GetAttributesOfType
 */
function GetAttributesOfType($params)
{
    // parameters
	$user 			= getScalarValue($params, 0);
	$password 		= getScalarValue($params, 1);
    $checksum		= getScalarValue($params, 2);

	$userModel = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $userModel->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+3,  _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetStorePermission($user, 'ManageProducts', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $model = $GLOBALS['app']->loadGadget('Store', 'Model');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }
	
    // Get item by checksum
	$info = $model->GetAttributeTypeByChecksum($checksum);
    if (Jaws_Error::isError($info)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $info->GetMessage());
    }
	
	if (isset($info['id'])) {
		$res = $model->GetAttributesOfType($info['id']);
		if (Jaws_Error::isError($res)) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $res->GetMessage());
		}
		$struct = array();
		$i = 0;
		foreach ($res as $r) {
			if ($i > 99) {
				break;
			}
			/*
			$publishtime = strtotime($entry['publishtime']);
			$publishtime = date('Ymd', $publishtime) . 'T' . date('H:i:s', $publishtime);
			$content = stripslashes($entry['text']);
			$data = array(
				'dateCreated' => new XML_RPC_Value($publishtime, 'dateTime.iso8601'),
				'content'     => new XML_RPC_Value($content),
			);
			*/
			$data = array();
			foreach ($r as $k => $v) {
				$data[$k] = XML_RPC_encode($v);
			}
			$struct[$i] = new XML_RPC_Value($data, 'struct');
			$i++;
		}

		if ($i > 0 ) {
			$data = array($struct[0]);
			for ($j = 1; $j < $i; $j++) {
				array_push($data, $struct[$j]);
			}
		} else {
			$data = array();
		}
		
		$response = new XML_RPC_Value($data, 'array');
		return new XML_RPC_Response($response);
	} else {
		$val = new XML_RPC_Value(array(), 'array');
		return new XML_RPC_Response($val);
	}
}

/*
 * GetAttributeType
 */
function GetAttributeType($params)
{
    // parameters
	$user 		= getScalarValue($params, 0);
	$password 	= getScalarValue($params, 1);
    $checksum	= getScalarValue($params, 2);

	$userModel = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $userModel->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+3,  _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetStorePermission($user, 'ManageProducts', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $model = $GLOBALS['app']->loadGadget('Store', 'Model');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }
	
    // Get item by checksum
	$res = $model->GetAttributeTypeByChecksum($checksum);
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
 * GetAttributeTypes
 */
function GetAttributeTypes($params)
{
    // parameters
	$user 			= getScalarValue($params, 0);
	$password 		= getScalarValue($params, 1);
    $limit			= getScalarValue($params, 2);
    $sortColumn		= getScalarValue($params, 3);
    $sortDir		= getScalarValue($params, 4);
    $offSet			= getScalarValue($params, 5);
	$offSet 		= ((bool)$offSet !== true ? false : true);
    $OwnerChecksum	= getScalarValue($params, 6);

	if (empty($sortColumn)) {
		$sortColumn = 'sort_order';
	}
	if (empty($sortDir)) {
		$sortDir = 'ASC';
	}
	
	$userModel = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $userModel->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+3,  _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetStorePermission($user, 'ManageProducts', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $model = $GLOBALS['app']->loadGadget('Store', 'Model');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }
	
	// Get user by checksum
	if (!empty($OwnerChecksum) && $OwnerChecksum != '0') {
		$user = $userModel->GetUserByChecksum($OwnerChecksum);
		if (Jaws_Error::isError($user)) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $user->GetMessage());
		}
		if (isset($user['id'])) {
			$OwnerID = $user['id'];
		} else {
			$val = new XML_RPC_Value(array(), 'array');
			return new XML_RPC_Response($val);
		}
	} else {
		$OwnerID = null;
	}
	
	$res = $model->GetAttributeTypes($limit, $sortColumn, $sortDir, $offSet, $OwnerID);
    if (Jaws_Error::isError($res)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $res->GetMessage());
    }
	$struct = array();
    $i = 0;
    foreach ($res as $r) {
        if ($i > 99) {
			break;
		}
        /*
		$publishtime = strtotime($entry['publishtime']);
        $publishtime = date('Ymd', $publishtime) . 'T' . date('H:i:s', $publishtime);
        $content = stripslashes($entry['text']);
        $data = array(
            'dateCreated' => new XML_RPC_Value($publishtime, 'dateTime.iso8601'),
            'content'     => new XML_RPC_Value($content),
        );
		*/
		$data = array();
		foreach ($r as $k => $v) {
			$data[$k] = XML_RPC_encode($v);
		}
        $struct[$i] = new XML_RPC_Value($data, 'struct');
        $i++;
    }

    if ($i > 0 ) {
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
 * MassAddProductAttribute
 */
function MassAddProductAttribute($params)
{
	$param = $params->getParam(0);
    $par = $param->me['array'];
    //var_dump($par);
	foreach ($par as $p) {
		//var_dump($p);
		$msg = new XML_RPC_Message('AddProductAttribute', $p->me['array']);
		$res = AddProductAttribute($msg, true);
		$val = $res->value();
		if (!is_object($val) || !is_a($val, 'XML_RPC_value')) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, var_export($val, true));
		}
	}
	return new XML_RPC_Response(new XML_RPC_Value("true", 'boolean'));
}

/*
 * AddProductAttribute
 */
function AddProductAttribute($params, $massive = false)
{
	// parameters
	$user 			= getScalarValue($params, 0);
	$password 		= getScalarValue($params, 1);
    $sort_order		= getScalarValue($params, 2);
    $feature		= getScalarValue($params, 3);
    $typeChecksum 	= getScalarValue($params, 4);
    $description   	= getScalarValue($params, 5);
    $add_amount    	= getScalarValue($params, 6);
    $add_percent    = getScalarValue($params, 7);
    $newprice    	= getScalarValue($params, 8);
    $OwnerChecksum  = getScalarValue($params, 9);
    $Active    		= getScalarValue($params, 10);
    $checksum    	= getScalarValue($params, 11);

	if (empty($Active)) {
		$Active = 'Y';
	}
    $description = parseContent($description);
    /*
	$productparentCategory_Name = parseContent($productparentCategory_Name);
    $productparentimage_code = parseContent($productparentimage_code);
	*/
	
	$userModel = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $userModel->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetStorePermission($user, 'ManageProducts', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $model = $GLOBALS['app']->loadGadget('Store', 'Model');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }
    
	// Get item by checksum
	$info = $model->GetAttributeTypeByChecksum($typeChecksum);
    /*
	if (Jaws_Error::isError($info)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $info->GetMessage() .' : '.$typeChecksum.' : '.var_export($params));
    }
	*/
	
	if (!Jaws_Error::isError($info) && isset($info['id']) && !empty($info['id'])) {
		// Get user by checksum
		if (!empty($OwnerChecksum) && $OwnerChecksum != '0') {
			$user = $userModel->GetUserByChecksum($OwnerChecksum);
			if (Jaws_Error::isError($user)) {
				return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $user->GetMessage());
			}
			if (isset($user['id'])) {
				$OwnerID = $user['id'];
			} else {
				$val = new XML_RPC_Value("false", 'boolean');
				return new XML_RPC_Response($val);
			}
		} else {
			$OwnerID = null;
		}

		$adminModel = $GLOBALS['app']->loadGadget('Store', 'AdminModel');
		if (Jaws_Error::isError($adminModel)) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $adminModel->GetMessage());
		}
		$post_id = $adminModel->AddProductAttribute(
			$sort_order, $feature, $info['id'], $description, $add_amount, $add_percent, 
			$newprice, $OwnerID, $Active, $checksum, true
		);
		if (Jaws_Error::IsError($post_id)) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, $post_id->GetMessage());
		}

		$val = new XML_RPC_Value("$post_id", 'string');
		return new XML_RPC_Response($val);
	}
	if ($massive === true) {
		$val = new XML_RPC_Value("true", 'boolean');
		return new XML_RPC_Response($val);
	}
	$val = new XML_RPC_Value("false", 'boolean');
	return new XML_RPC_Response($val);
}

/*
 * MassUpdateProductAttribute
 */
function MassUpdateProductAttribute($params)
{
	$param = $params->getParam(0);
    $par = $param->me['array'];
    //var_dump($par);
	foreach ($par as $p) {
		//var_dump($p);
		$msg = new XML_RPC_Message('UpdateProductAttribute', $p->me['array']);
		$res = UpdateProductAttribute($msg, true);
		$val = $res->value();
		if (!is_object($val) || !is_a($val, 'XML_RPC_value')) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, var_export($val, true));
		}
	}
	return new XML_RPC_Response(new XML_RPC_Value("true", 'boolean'));
}

/*
 * UpdateProductAttribute
 */
function UpdateProductAttribute($params, $massive = false)
{
	// parameters
	$user 			= getScalarValue($params, 0);
	$password 		= getScalarValue($params, 1);
    $checksum		= getScalarValue($params, 2);
    $feature		= getScalarValue($params, 3);
    $typeChecksum   = getScalarValue($params, 4);
    $description 	= getScalarValue($params, 5);
    $add_amount   	= getScalarValue($params, 6);
    $add_percent    = getScalarValue($params, 7);
    $newprice    	= getScalarValue($params, 8);
    $Active    		= getScalarValue($params, 9);

    $description = parseContent($description);
    /*
	$productparentCategory_Name = parseContent($productparentCategory_Name);
    $productparentimage_code = parseContent($productparentimage_code);
	*/
	
	$userModel = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $userModel->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetStorePermission($user, 'ManageProducts', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $model = $GLOBALS['app']->loadGadget('Store', 'Model');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }

    // Get item by checksum
	$info = $model->GetAttributeByChecksum($checksum);
    /*
	if (Jaws_Error::isError($info)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $info->GetMessage());
    }
	*/
	if (!Jaws_Error::isError($info) && isset($info['id']) && !empty($info['id'])) {
		// Get item by checksum
		$type = $model->GetAttributeTypeByChecksum($typeChecksum);
		if (!Jaws_Error::isError($type) && isset($type['id'])) {
			$adminModel = $GLOBALS['app']->loadGadget('Store', 'AdminModel');
			if (Jaws_Error::isError($adminModel)) {
				return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $adminModel->GetMessage());
			}
			$post_id = $adminModel->UpdateProductAttribute(
				$info['id'], $feature, $type['id'], $description, $add_amount, $add_percent, $newprice, $Active, true
			);
			if (Jaws_Error::IsError($post_id)) {
				return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, $post_id->GetMessage());
			}
			$val = new XML_RPC_Value("$post_id", 'boolean');
			return new XML_RPC_Response($val);
		}
	}
	if ($massive === true) {
		$val = new XML_RPC_Value("true", 'boolean');
		return new XML_RPC_Response($val);
	}
	$val = new XML_RPC_Value("false", 'boolean');
	return new XML_RPC_Response($val);
}

/*
 * MassDeleteProductAttribute
 */
function MassDeleteProductAttribute($params)
{
	$param = $params->getParam(0);
    $par = $param->me['array'];
    //var_dump($par);
	foreach ($par as $p) {
		//var_dump($p);
		$msg = new XML_RPC_Message('DeleteProductAttribute', $p->me['array']);
		$res = DeleteProductAttribute($msg, true);
		$val = $res->value();
		if (!is_object($val) || !is_a($val, 'XML_RPC_value')) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, var_export($val, true));
		}
	}
	return new XML_RPC_Response(new XML_RPC_Value("true", 'boolean'));
}

/*
 * DeleteProductAttribute
 */
function DeleteProductAttribute($params, $massive = false)
{
    // parameters
	$user 		= getScalarValue($params, 0);
	$password 	= getScalarValue($params, 1);
    $checksum	= getScalarValue($params, 2);
    $mass		= getScalarValue($params, 3);
	$mass 		= ((bool)$mass !== true ? false : true);
	
	$userModel = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $userModel->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetStorePermission($user, 'ManageProducts', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $model = $GLOBALS['app']->loadGadget('Store', 'Model');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }

    // Get item by checksum
	$info = $model->GetAttributeByChecksum($checksum);
    /*
	if (Jaws_Error::isError($info)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $info->GetMessage());
    }
	*/
		
	if (!Jaws_Error::isError($info) && isset($info['id']) && !empty($info['id'])) {
		$adminModel = $GLOBALS['app']->loadGadget('Store', 'AdminModel');
		if (Jaws_Error::isError($adminModel)) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $adminModel->GetMessage());
		}
		$post_id = $adminModel->DeleteProductAttribute($info['id'], $mass);
		if (!Jaws_Error::IsError($post_id)) {
			$val = new XML_RPC_Value("$post_id", 'boolean');
			return new XML_RPC_Response($val);
		}
	}
	if ($massive === true) {
		$val = new XML_RPC_Value("true", 'boolean');
		return new XML_RPC_Response($val);
	}
	$val = new XML_RPC_Value("false", 'boolean');
	return new XML_RPC_Response($val);
}

/*
 * MassAddAttributeType
 */
function MassAddAttributeType($params)
{
	$param = $params->getParam(0);
    $par = $param->me['array'];
    //var_dump($par);
	foreach ($par as $p) {
		//var_dump($p);
		$msg = new XML_RPC_Message('AddAttributeType', $p->me['array']);
		$res = AddAttributeType($msg);
		$val = $res->value();
		if (!is_object($val) || !is_a($val, 'XML_RPC_value')) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, var_export($val, true));
		}
	}
	return new XML_RPC_Response(new XML_RPC_Value("true", 'boolean'));
}

/*
 * AddAttributeType
 */
function AddAttributeType($params)
{
	// parameters
	$user 			= getScalarValue($params, 0);
	$password 		= getScalarValue($params, 1);
    $title			= getScalarValue($params, 2);
    $description	= getScalarValue($params, 3);
    $itype 			= getScalarValue($params, 4);
    $required   	= getScalarValue($params, 5);
    $OwnerChecksum  = getScalarValue($params, 6);
    $Active    		= getScalarValue($params, 7);
    $checksum    	= getScalarValue($params, 8);

	if (empty($itype)) {
		$itype = 'TextBox';
	}
	if (empty($required)) {
		$required = 'N';
	}
	if (empty($Active)) {
		$Active = 'Y';
	}
    $description = parseContent($description);
    /*
	$productparentCategory_Name = parseContent($productparentCategory_Name);
    $productparentimage_code = parseContent($productparentimage_code);
	*/
	
	$userModel = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $userModel->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetStorePermission($user, 'ManageProducts', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $model = $GLOBALS['app']->loadGadget('Store', 'AdminModel');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }
    	
	// Get user by checksum
	if (!empty($OwnerChecksum) && $OwnerChecksum != '0') {
		$user = $userModel->GetUserByChecksum($OwnerChecksum);
		if (Jaws_Error::isError($user)) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $user->GetMessage());
		}
		if (isset($user['id'])) {
			$OwnerID = $user['id'];
		} else {
			$val = new XML_RPC_Value("false", 'boolean');
			return new XML_RPC_Response($val);
		}
	} else {
		$OwnerID = null;
	}

    $post_id = $model->AddAttributeType(
		$title, $description, $itype, $required, $OwnerID, $Active, $checksum
	);
    if (Jaws_Error::IsError($post_id)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, $post_id->GetMessage());
    }

    $val = new XML_RPC_Value("$post_id", 'string');
    return new XML_RPC_Response($val);
}

/*
 * UpdateAttributeType
 */
function UpdateAttributeType($params)
{
	// parameters
	$user 			= getScalarValue($params, 0);
	$password 		= getScalarValue($params, 1);
    $checksum		= getScalarValue($params, 2);
    $title			= getScalarValue($params, 3);
    $description   	= getScalarValue($params, 4);
    $itype 			= getScalarValue($params, 5);
    $required   	= getScalarValue($params, 6);
    $Active    		= getScalarValue($params, 7);

    $description = parseContent($description);
    /*
	$productparentCategory_Name = parseContent($productparentCategory_Name);
    $productparentimage_code = parseContent($productparentimage_code);
	*/
	
	$userModel = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $userModel->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetStorePermission($user, 'ManageProducts', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $model = $GLOBALS['app']->loadGadget('Store', 'Model');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }
	
    // Get item by checksum
	$type = $model->GetAttributeTypeByChecksum($checksum);
    /*
	if (Jaws_Error::isError($type)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $type->GetMessage());
    }
	*/
	
	if (!Jaws_Error::isError($type) && isset($type['id']) && !empty($type['id'])) {
		$adminModel = $GLOBALS['app']->loadGadget('Store', 'AdminModel');
		if (Jaws_Error::isError($adminModel)) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $adminModel->GetMessage());
		}
		$post_id = $adminModel->UpdateAttributeType($type['id'], $title, $description, $itype, $required, $Active);
		if (Jaws_Error::IsError($post_id)) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, $post_id->GetMessage());
		}

		$val = new XML_RPC_Value("$post_id", 'boolean');
		return new XML_RPC_Response($val);
	} else {
		$val = new XML_RPC_Value("false", 'boolean');
		return new XML_RPC_Response($val);
	}
}

/*
 * DeleteAttributeType
 */
function DeleteAttributeType($params)
{
    // parameters
	$user 		= getScalarValue($params, 0);
	$password 	= getScalarValue($params, 1);
    $checksum	= getScalarValue($params, 2);
    $massive	= getScalarValue($params, 3);
	$massive = ((bool)$massive !== true ? false : true);
	
	$userModel = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $userModel->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetStorePermission($user, 'ManageProducts', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $model = $GLOBALS['app']->loadGadget('Store', 'Model');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }

    // Get item by checksum
	$info = $model->GetAttributeTypeByChecksum($checksum);
    /*
	if (Jaws_Error::isError($info)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $info->GetMessage());
    }
	*/
		
	if (!Jaws_Error::isError($info) && isset($info['id']) && !empty($info['id'])) {
		$adminModel = $GLOBALS['app']->loadGadget('Store', 'AdminModel');
		if (Jaws_Error::isError($adminModel)) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $adminModel->GetMessage());
		}
		$post_id = $adminModel->DeleteAttributeType($info['id'], $massive);
		if (Jaws_Error::IsError($post_id)) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, $post_id->GetMessage());
		}

		$val = new XML_RPC_Value("$post_id", 'boolean');
		return new XML_RPC_Response($val);
	} else {
		$val = new XML_RPC_Value("false", 'boolean');
		return new XML_RPC_Response($val);
	}
}

/*
 * GetSale
 */
function GetSale($params)
{
    // parameters
	$user 		= getScalarValue($params, 0);
	$password 	= getScalarValue($params, 1);
    $checksum	= getScalarValue($params, 2);

	$userModel = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $userModel->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+3,  _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetStorePermission($user, 'ManageProducts', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $model = $GLOBALS['app']->loadGadget('Store', 'Model');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }
	
    // Get item by checksum
	$res = $model->GetSaleByChecksum($checksum);
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
 * GetSales
 */
function GetSales($params)
{
    // parameters
	$user 			= getScalarValue($params, 0);
	$password 		= getScalarValue($params, 1);
    $limit			= getScalarValue($params, 2);
    $sortColumn		= getScalarValue($params, 3);
    $sortDir		= getScalarValue($params, 4);
    $offSet			= getScalarValue($params, 5);
	$offSet 		= ((bool)$offSet !== true ? false : true);
    $OwnerChecksum	= getScalarValue($params, 6);

	if (empty($sortColumn)) {
		$sortColumn = 'sort_order';
	}
	if (empty($sortDir)) {
		$sortDir = 'ASC';
	}
	
	$userModel = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $userModel->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+3,  _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetStorePermission($user, 'ManageProducts', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $model = $GLOBALS['app']->loadGadget('Store', 'Model');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }
	
	// Get user by checksum
	if (!empty($OwnerChecksum) && $OwnerChecksum != '0') {
		$user = $userModel->GetUserByChecksum($OwnerChecksum);
		if (Jaws_Error::isError($user)) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $user->GetMessage());
		}
		if (isset($user['id'])) {
			$OwnerID = $user['id'];
		} else {
			$val = new XML_RPC_Value(array(), 'array');
			return new XML_RPC_Response($val);
		}
	} else {
		$OwnerID = null;
	}
	
	$res = $model->GetSales($limit, $sortColumn, $sortDir, $offSet, $OwnerID);
    if (Jaws_Error::isError($res)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $res->GetMessage());
    }
	$struct = array();
    $i = 0;
    foreach ($res as $r) {
        if ($i > 99) {
			break;
		}
        /*
		$publishtime = strtotime($entry['publishtime']);
        $publishtime = date('Ymd', $publishtime) . 'T' . date('H:i:s', $publishtime);
        $content = stripslashes($entry['text']);
        $data = array(
            'dateCreated' => new XML_RPC_Value($publishtime, 'dateTime.iso8601'),
            'content'     => new XML_RPC_Value($content),
        );
		*/
		$data = array();
		foreach ($r as $k => $v) {
			$data[$k] = XML_RPC_encode($v);
		}
        $struct[$i] = new XML_RPC_Value($data, 'struct');
        $i++;
    }

    if ($i > 0 ) {
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
 * MassAddSale
 */
function MassAddSale($params)
{
	$param = $params->getParam(0);
    $par = $param->me['array'];
    //var_dump($par);
	foreach ($par as $p) {
		//var_dump($p);
		$msg = new XML_RPC_Message('AddSale', $p->me['array']);
		$res = AddSale($msg);
		$val = $res->value();
		if (!is_object($val) || !is_a($val, 'XML_RPC_value')) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, var_export($val, true));
		}
	}
	return new XML_RPC_Response(new XML_RPC_Value("true", 'boolean'));
}

/*
 * AddSale
 */
function AddSale($params)
{
	// parameters
	$user 				= getScalarValue($params, 0);
	$password 			= getScalarValue($params, 1);
    $title				= getScalarValue($params, 2);
    $startdate			= getScalarValue($params, 3);
    $enddate 			= getScalarValue($params, 4);
    $description   		= getScalarValue($params, 5);
    $discount_amount    = getScalarValue($params, 6);
    $discount_percent   = getScalarValue($params, 7);
    $discount_newprice  = getScalarValue($params, 8);
    $coupon_code  		= getScalarValue($params, 9);
    $featured    		= getScalarValue($params, 10);
    $OwnerChecksum    	= getScalarValue($params, 11);
    $Active    			= getScalarValue($params, 12);
    $checksum    		= getScalarValue($params, 13);

	if (empty($startdate)) {
		$startdate = null;
	}
	if (empty($enddate)) {
		$enddate = null;
	}
	if (empty($featured)) {
		$featured = 'N';
	}
	if (empty($Active)) {
		$Active = 'Y';
	}
    $description = parseContent($description);
    /*
	$productparentCategory_Name = parseContent($productparentCategory_Name);
    $productparentimage_code = parseContent($productparentimage_code);
	*/
	
	$userModel = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $userModel->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetStorePermission($user, 'ManageProducts', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $model = $GLOBALS['app']->loadGadget('Store', 'AdminModel');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }
    	
	// Get user by checksum
	if (!empty($OwnerChecksum) && $OwnerChecksum != '0') {
		$user = $userModel->GetUserByChecksum($OwnerChecksum);
		if (Jaws_Error::isError($user)) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $user->GetMessage());
		}
		if (isset($user['id'])) {
			$OwnerID = $user['id'];
		} else {
			$val = new XML_RPC_Value("false", 'boolean');
			return new XML_RPC_Response($val);
		}
	} else {
		$OwnerID = null;
	}

    $post_id = $model->AddSale(
		$title, $startdate, $enddate, $description, $discount_amount, $discount_percent, 
		$discount_newprice, $coupon_code, $featured, $OwnerID, $Active, $checksum, true
	);
    if (Jaws_Error::IsError($post_id)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, $post_id->GetMessage());
    }

    $val = new XML_RPC_Value("$post_id", 'string');
    return new XML_RPC_Response($val);
}

/*
 * UpdateSale
 */
function UpdateSale($params)
{
	// parameters
	$user 				= getScalarValue($params, 0);
	$password 			= getScalarValue($params, 1);
    $checksum			= getScalarValue($params, 2);
    $title				= getScalarValue($params, 3);
    $startdate   		= getScalarValue($params, 4);
    $enddate 			= getScalarValue($params, 5);
    $description   		= getScalarValue($params, 6);
    $discount_amount    = getScalarValue($params, 7);
    $discount_percent   = getScalarValue($params, 8);
    $discount_newprice  = getScalarValue($params, 9);
    $coupon_code  		= getScalarValue($params, 10);
    $featured    		= getScalarValue($params, 11);
    $Active    			= getScalarValue($params, 12);

    $description = parseContent($description);
    /*
	$productparentCategory_Name = parseContent($productparentCategory_Name);
    $productparentimage_code = parseContent($productparentimage_code);
	*/
	
	$userModel = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $userModel->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetStorePermission($user, 'ManageProducts', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $adminModel = $GLOBALS['app']->loadGadget('Store', 'AdminModel');
    if (Jaws_Error::isError($adminModel)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $adminModel->GetMessage());
    }
    $model = $GLOBALS['app']->loadGadget('Store', 'Model');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }

    // Get item by checksum
	$info = $model->GetSaleByChecksum($checksum);
    /*
	if (Jaws_Error::isError($info)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $info->GetMessage());
    }
	*/
		
	if (!Jaws_Error::isError($info) && isset($info['id']) && !empty($info['id'])) {
		$post_id = $adminModel->UpdateSale(
			$info['id'], $title, $startdate, $enddate, $description, $discount_amount, 
			$discount_percent, $discount_newprice, $coupon_code, $featured, $Active, true
		);
		if (Jaws_Error::IsError($post_id)) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, $post_id->GetMessage());
		}

		$val = new XML_RPC_Value("$post_id", 'boolean');
		return new XML_RPC_Response($val);
	} else {
		$val = new XML_RPC_Value("false", 'boolean');
		return new XML_RPC_Response($val);
	}
}

/*
 * DeleteSale
 */
function DeleteSale($params)
{
    // parameters
	$user 		= getScalarValue($params, 0);
	$password 	= getScalarValue($params, 1);
    $checksum	= getScalarValue($params, 2);
    $massive	= getScalarValue($params, 3);
	$massive = ((bool)$massive !== true ? false : true);
	
	$userModel = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $userModel->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetStorePermission($user, 'ManageProducts', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $model = $GLOBALS['app']->loadGadget('Store', 'Model');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }

    // Get item by checksum
	$info = $model->GetSaleByChecksum($checksum);
    /*
	if (Jaws_Error::isError($info)) {
		return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $info->GetMessage());
    }
	*/
		
	if (!Jaws_Error::isError($info) && isset($info['id']) && !empty($info['id'])) {
		$adminModel = $GLOBALS['app']->loadGadget('Store', 'AdminModel');
		if (Jaws_Error::isError($adminModel)) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $adminModel->GetMessage());
		}
		$post_id = $adminModel->DeleteSale($info['id'], $massive);
		if (Jaws_Error::IsError($post_id)) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, $post_id->GetMessage());
		}

		$val = new XML_RPC_Value("$post_id", 'boolean');
		return new XML_RPC_Response($val);
	} else {
		$val = new XML_RPC_Value("false", 'boolean');
		return new XML_RPC_Response($val);
	}
}

/*
 * GetBrand
 */
function GetBrand($params)
{
    // parameters
	$user 		= getScalarValue($params, 0);
	$password 	= getScalarValue($params, 1);
    $checksum	= getScalarValue($params, 2);

	$userModel = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $userModel->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+3,  _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetStorePermission($user, 'ManageProducts', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $model = $GLOBALS['app']->loadGadget('Store', 'Model');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }
	
    // Get item by checksum
	$res = $model->GetBrandByChecksum($checksum);
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
 * GetBrands
 */
function GetBrands($params)
{
    // parameters
	$user 			= getScalarValue($params, 0);
	$password 		= getScalarValue($params, 1);
    $limit			= getScalarValue($params, 2);
    $sortColumn		= getScalarValue($params, 3);
    $sortDir		= getScalarValue($params, 4);
    $offSet			= getScalarValue($params, 5);
	$offSet 		= ((bool)$offSet !== true ? false : true);
    $OwnerChecksum	= getScalarValue($params, 6);

	if (empty($sortColumn)) {
		$sortColumn = 'sort_order';
	}
	if (empty($sortDir)) {
		$sortDir = 'ASC';
	}
	
	$userModel = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $userModel->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+3,  _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetStorePermission($user, 'ManageProducts', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $model = $GLOBALS['app']->loadGadget('Store', 'Model');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }
	
	// Get user by checksum
	if (!empty($OwnerChecksum) && $OwnerChecksum != '0') {
		$user = $userModel->GetUserByChecksum($OwnerChecksum);
		if (Jaws_Error::isError($user)) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $user->GetMessage());
		}
		if (isset($user['id'])) {
			$OwnerID = $user['id'];
		} else {
			$val = new XML_RPC_Value(array(), 'array');
			return new XML_RPC_Response($val);
		}
	} else {
		$OwnerID = null;
	}
	
	$res = $model->GetBrands($limit, $sortColumn, $sortDir, $offSet, $OwnerID);
    if (Jaws_Error::isError($res)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $res->GetMessage());
    }
	$struct = array();
    $i = 0;
    foreach ($res as $r) {
        if ($i > 99) {
			break;
		}
        /*
		$publishtime = strtotime($entry['publishtime']);
        $publishtime = date('Ymd', $publishtime) . 'T' . date('H:i:s', $publishtime);
        $content = stripslashes($entry['text']);
        $data = array(
            'dateCreated' => new XML_RPC_Value($publishtime, 'dateTime.iso8601'),
            'content'     => new XML_RPC_Value($content),
        );
		*/
		$data = array();
		foreach ($r as $k => $v) {
			$data[$k] = XML_RPC_encode($v);
		}
        $struct[$i] = new XML_RPC_Value($data, 'struct');
        $i++;
    }

    if ($i > 0 ) {
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
 * MassAddBrand
 */
function MassAddBrand($params)
{
	$param = $params->getParam(0);
    $par = $param->me['array'];
    //var_dump($par);
	foreach ($par as $p) {
		//var_dump($p);
		$msg = new XML_RPC_Message('AddBrand', $p->me['array']);
		$res = AddBrand($msg);
		$val = $res->value();
		if (!is_object($val) || !is_a($val, 'XML_RPC_value')) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, var_export($val, true));
		}
	}
	return new XML_RPC_Response(new XML_RPC_Value("true", 'boolean'));
}

/*
 * AddBrand
 */
function AddBrand($params)
{
	// parameters
	$user 			= getScalarValue($params, 0);
	$password 		= getScalarValue($params, 1);
    $title			= getScalarValue($params, 2);
    $description	= getScalarValue($params, 3);
    $image 			= getScalarValue($params, 4);
    $image_width   	= getScalarValue($params, 5);
    $image_height   = getScalarValue($params, 6);
    $layout   		= getScalarValue($params, 7);
    $active  		= getScalarValue($params, 8);
    $OwnerChecksum  = getScalarValue($params, 9);
    $url_type    	= getScalarValue($params, 10);
    $internal_url   = getScalarValue($params, 11);
    $external_url   = getScalarValue($params, 12);
    $url_target    	= getScalarValue($params, 13);
    $image_code    	= getScalarValue($params, 14);
    $checksum    	= getScalarValue($params, 15);

	if (empty($startdate)) {
		$startdate = null;
	}
	if (empty($url_target)) {
		$url_target = '_self';
	}
	if (empty($url_type)) {
		$url_type = 'imageviewer';
	}
	if (empty($active)) {
		$active = 'Y';
	}
    $description = parseContent($description);
    /*
	$productparentCategory_Name = parseContent($productparentCategory_Name);
    $productparentimage_code = parseContent($productparentimage_code);
	*/
	
	$userModel = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $userModel->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetStorePermission($user, 'ManageProducts', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $adminModel = $GLOBALS['app']->loadGadget('Store', 'AdminModel');
    if (Jaws_Error::isError($adminModel)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $adminModel->GetMessage());
    }
    $model = $GLOBALS['app']->loadGadget('Store', 'Model');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }
    	
	// Get user by checksum
	if (!empty($OwnerChecksum) && $OwnerChecksum != '0') {
		$user = $userModel->GetUserByChecksum($OwnerChecksum);
		if (Jaws_Error::isError($user)) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $user->GetMessage());
		}
		if (isset($user['id'])) {
			$OwnerID = $user['id'];
		} else {
			$val = new XML_RPC_Value("false", 'boolean');
			return new XML_RPC_Response($val);
		}
	} else {
		$OwnerID = null;
	}

    $post_id = $adminModel->AddBrand(
		$title, $description, $image, $image_width, $image_height, 
		$layout, $active, $OwnerID, $url_type, $internal_url, 
		$external_url, $url_target, $image_code, $checksum, true
	);
    if (Jaws_Error::IsError($post_id)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, $post_id->GetMessage());
    }

    $val = new XML_RPC_Value("$post_id", 'string');
    return new XML_RPC_Response($val);
}

/*
 * UpdateBrand
 */
function UpdateBrand($params)
{
	// parameters
	$user 			= getScalarValue($params, 0);
	$password 		= getScalarValue($params, 1);
    $checksum		= getScalarValue($params, 2);
    $title			= getScalarValue($params, 3);
    $description   	= getScalarValue($params, 4);
    $image 			= getScalarValue($params, 5);
    $image_width   	= getScalarValue($params, 6);
    $image_height   = getScalarValue($params, 7);
    $layout   		= getScalarValue($params, 8);
    $active  		= getScalarValue($params, 9);
    $url_type    	= getScalarValue($params, 10);
    $internal_url   = getScalarValue($params, 11);
    $external_url   = getScalarValue($params, 12);
    $url_target    	= getScalarValue($params, 13);
    $image_code    	= getScalarValue($params, 14);

	if (empty($url_target)) {
		$url_target = '_self';
	}
	if (empty($url_type)) {
		$url_type = 'imageviewer';
	}

    $description = parseContent($description);
    /*
	$productparentCategory_Name = parseContent($productparentCategory_Name);
    $productparentimage_code = parseContent($productparentimage_code);
	*/
	
	$userModel = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $userModel->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetStorePermission($user, 'ManageProducts', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $adminModel = $GLOBALS['app']->loadGadget('Store', 'AdminModel');
    if (Jaws_Error::isError($adminModel)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $adminModel->GetMessage());
    }
    $model = $GLOBALS['app']->loadGadget('Store', 'Model');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }

    // Get item by checksum
	$info = $model->GetBrandByChecksum($checksum);
    /*
	if (Jaws_Error::isError($info)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $info->GetMessage());
    }
	*/	
	if (!Jaws_Error::isError($info) && isset($info['id']) && !empty($info['id'])) {
		$post_id = $adminModel->UpdateBrand(
			$info['id'], $title, $description, $image, $image_width, $image_height, 
			$layout, $active, $url_type, $internal_url, $external_url, 
			$url_target, $image_code, true
		);
		if (Jaws_Error::IsError($post_id)) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, $post_id->GetMessage());
		}

		$val = new XML_RPC_Value("$post_id", 'boolean');
		return new XML_RPC_Response($val);
	} else {
		$val = new XML_RPC_Value("false", 'boolean');
		return new XML_RPC_Response($val);
	}
}

/*
 * DeleteBrand
 */
function DeleteBrand($params)
{
    // parameters
	$user 		= getScalarValue($params, 0);
	$password 	= getScalarValue($params, 1);
    $checksum	= getScalarValue($params, 2);
    $massive	= getScalarValue($params, 3);
	$massive = ((bool)$massive !== true ? false : true);
	
	$userModel = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $userModel->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetStorePermission($user, 'ManageProducts', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $model = $GLOBALS['app']->loadGadget('Store', 'Model');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }

    // Get item by checksum
	$info = $model->GetBrandByChecksum($checksum);
    /*
	if (Jaws_Error::isError($info)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $info->GetMessage());
    }
	*/
		
	if (!Jaws_Error::isError($info) && isset($info['id']) && !empty($info['id'])) {
		$adminModel = $GLOBALS['app']->loadGadget('Store', 'AdminModel');
		if (Jaws_Error::isError($adminModel)) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $adminModel->GetMessage());
		}
		$post_id = $adminModel->DeleteBrand($info['id'], $massive);
		if (Jaws_Error::IsError($post_id)) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, $post_id->GetMessage());
		}

		$val = new XML_RPC_Value("$post_id", 'boolean');
		return new XML_RPC_Response($val);
	} else {
		$val = new XML_RPC_Value("false", 'boolean');
		return new XML_RPC_Response($val);
	}
}

/*
 *  XML-RPC Server
 */

$rpc_methods = array(
    // Product parents
    'GetProductParent' => array(
        'function' => 'GetProductParent',
        'signature' => array(
            array('string', 'string', 'string', 'string'),
        ),
    ),
    'GetSingleProductParentByUserID' => array(
        'function' => 'GetSingleProductParentByUserID',
        'signature' => array(
            array('string', 'string', 'string', 'string', 'string'),
        ),
    ),
    'GetProductParents' => array(
        'function' => 'GetProductParents',
        'signature' => array(
            array('string', 'string', 'string', 'int', 'string', 'string', 'int', 'string'),
        ),
    ),
    'GetProductParentsByUserID' => array(
        'function' => 'GetProductParentsByUserID',
        'signature' => array(
            array('string', 'string', 'string', 'string'),
        ),
    ),
    'GetStoreOwnersOfParent' => array(
        'function' => 'GetStoreOwnersOfParent',
        'signature' => array(
            array('string', 'string', 'string', 'string'),
        ),
    ),
    'UserOwnsStoreInParent' => array(
        'function' => 'UserOwnsStoreInParent',
        'signature' => array(
            array('string', 'string', 'string', 'string', 'string'),
        ),
    ),
    'MassAddProductParent' => array(
        'function' => 'MassAddProductParent',
        'signature' => array(
            array(
				'string', 'array'
			),
        ),
    ),
    'AddProductParent' => array(
        'function' => 'AddProductParent',
        'signature' => array(
            array(
				'string', 'string', 'string', 'int', 'string', 'string', 'string', 'string', 'string', 'string', 
				'string', 'string', 'string', 'string', 'string', 'string', 'string', 'string'
			),
        ),
    ),
    'UpdateProductParent' => array(
        'function' => 'UpdateProductParent',
        'signature' => array(
            array(
				'string', 'string', 'string', 'string', 'string', 'string', 'int', 'string', 'string', 'string', 
				'string', 'string', 'string', 'string', 'string', 'string', 'string'
			),
        ),
    ),
    'DeleteProductParent' => array(
        'function' => 'DeleteProductParent',
        'signature' => array(
            array(
				'string', 'string', 'string', 'string', 'boolean'
			),
        ),
    ),
    // Products
	'GetProduct' => array(
        'function' => 'GetProduct',
        'signature' => array(
            array('string', 'string', 'string', 'string'),
        ),
    ),
	'GetSingleProductByUserID' => array(
        'function' => 'GetSingleProductByUserID',
        'signature' => array(
            array('string', 'string', 'string', 'string', 'string'),
        ),
    ),
	'GetProducts' => array(
        'function' => 'GetProducts',
        'signature' => array(
            array('string', 'string', 'string', 'int', 'string', 'string', 'int', 'string', 'string', 'string', 'string'),
        ),
    ),

	'GetAllProductsOfParent' => array(
        'function' => 'GetAllProductsOfParent',
        'signature' => array(
            array('string', 'string', 'string', 'string', 'string', 'string', 'string', 'string'),
        ),
    ),
	'GetStoreOfUserID' => array(
        'function' => 'GetStoreOfUserID',
        'signature' => array(
            array('string', 'string', 'string', 'string', 'string'),
        ),
    ),
	'GetStoreOfGroup' => array(
        'function' => 'GetStoreOfGroup',
        'signature' => array(
            array('string', 'string', 'string', 'string', 'string', 'string', 'string'),
        ),
    ),
    'MassAddProduct' => array(
        'function' => 'MassAddProduct',
        'signature' => array(
            array(
				'string', 'array'
			),
        ),
    ),
    'AddProduct' => array(
        'function' => 'AddProduct',
        'signature' => array(
			array(
				'string', 'string', 'string', 'string', 'int', 'string', 'string', 'string', 'string', 'string', 
				'string', 'string', 'string', 'string', 'string', 'string', 'string', 'string', 
				'string', 'string', 'string', 'string', 'string', 'string', 'string', 'string', 
				'string', 'string', 'string', 'string', 'string', 'string', 'string', 'string',
				'string', 'string', 'string', 'string', 'string', 'string', 'string', 'string', 
				'string', 'string', 'string', 'string', 'string', 'string', 'string', 'string', 
				'string', 'int', 'string'
			),
        ),
    ),
    'UpdateProduct' => array(
        'function' => 'UpdateProduct',
        'signature' => array(
			array(
				'string', 'string', 'string', 'string', 'string', 'int', 'string', 'string', 'string', 'string', 'string', 
				'string', 'string', 'string', 'string', 'string', 'string', 'string', 'string', 
				'string', 'string', 'string', 'string', 'string', 'string', 'string', 'string', 
				'string', 'string', 'string', 'string', 'string', 'string', 'string', 'string', 'string',
				'string', 'string', 'string', 'string', 'string', 'string', 'string', 'string', 
				'string', 'string', 'string', 'string', 'string', 'string', 'string', 'int'
			),
        ),
    ),
    'DeleteProduct' => array(
        'function' => 'DeleteProduct',
        'signature' => array(
            array(
				'string', 'string', 'string', 'string', 'boolean'
			),
        ),
    ),
	// Posts
	'GetPost' => array(
        'function' => 'GetPost',
        'signature' => array(
            array('string', 'string', 'string', 'string'),
        ),
    ),
	'GetAllPostsOfProduct' => array(
        'function' => 'GetAllPostsOfProduct',
        'signature' => array(
            array('string', 'string', 'string', 'string'),
        ),
    ),
    'MassAddPost' => array(
        'function' => 'MassAddPost',
        'signature' => array(
            array(
				'string', 'array'
			),
        ),
    ),
    'AddPost' => array(
        'function' => 'AddPost',
        'signature' => array(
			array(
				'string', 'string', 'string', 'int', 'string', 'string', 'string', 'string', 'int', 'int', 'int', 
				'string', 'string', 'string', 'string', 'string', 'string', 'string'
			),
        ),
    ),
    'UpdatePost' => array(
        'function' => 'UpdatePost',
        'signature' => array(
			array(
				'string', 'string', 'string', 'string', 'int', 'string', 'string', 'string', 'int', 'int', 'int', 
				'string', 'string', 'string', 'string', 'string'
			),
        ),
    ),
    'DeletePost' => array(
        'function' => 'DeletePost',
        'signature' => array(
            array(
				'string', 'string', 'string', 'string', 'boolean'
			),
        ),
    ),
	// Attributes
	'GetAttribute' => array(
        'function' => 'GetAttribute',
        'signature' => array(
            array('string', 'string', 'string', 'string'),
        ),
    ),
	'GetProductAttributes' => array(
        'function' => 'GetProductAttributes',
        'signature' => array(
            array('string', 'string', 'string', 'int', 'string', 'string', 'boolean', 'string'),
        ),
    ),
	'GetAttributesOfType' => array(
        'function' => 'GetAttributesOfType',
        'signature' => array(
            array('string', 'string', 'string', 'string'),
        ),
    ),
	'GetAttributeType' => array(
        'function' => 'GetAttributeType',
        'signature' => array(
            array('string', 'string', 'string', 'string'),
        ),
    ),
	'GetAttributeTypes' => array(
        'function' => 'GetAttributeTypes',
        'signature' => array(
            array('string', 'string', 'string', 'int', 'string', 'string', 'boolean', 'string'),
        ),
    ),
    'MassAddProductAttribute' => array(
        'function' => 'MassAddProductAttribute',
        'signature' => array(
            array(
				'string', 'array'
			),
        ),
    ),
    'AddProductAttribute' => array(
        'function' => 'AddProductAttribute',
        'signature' => array(
			array(
				'string', 'string', 'string', 'int', 'string', 'string', 'string', 'string', 'string', 'string', 
				'string', 'string', 'string'
			),
        ),
    ),
    'MassUpdateProductAttribute' => array(
        'function' => 'MassUpdateProductAttribute',
        'signature' => array(
            array(
				'string', 'array'
			),
        ),
    ),
    'UpdateProductAttribute' => array(
        'function' => 'UpdateProductAttribute',
        'signature' => array(
			array(
				'string', 'string', 'string', 'string', 'string', 'string', 'string', 'string', 'string', 'string', 'string'
			),
        ),
    ),
    'MassDeleteProductAttribute' => array(
        'function' => 'MassDeleteProductAttribute',
        'signature' => array(
            array(
				'string', 'array'
			),
        ),
    ),
    'DeleteProductAttribute' => array(
        'function' => 'DeleteProductAttribute',
        'signature' => array(
            array(
				'string', 'string', 'string', 'string', 'boolean'
			),
        ),
    ),
    'MassAddAttributeType' => array(
        'function' => 'MassAddAttributeType',
        'signature' => array(
            array(
				'string', 'array'
			),
        ),
    ),
    'AddAttributeType' => array(
        'function' => 'AddAttributeType',
        'signature' => array(
			array(
				'string', 'string', 'string', 'string', 'string', 'string', 'string', 'string', 'string', 'string'
			),
        ),
    ),
    'UpdateAttributeType' => array(
        'function' => 'UpdateAttributeType',
        'signature' => array(
			array(
				'string', 'string', 'string', 'string', 'string', 'string', 'string', 'string', 'string'
			),
        ),
    ),
    'DeleteAttributeType' => array(
        'function' => 'DeleteAttributeType',
        'signature' => array(
            array(
				'string', 'string', 'string', 'string', 'boolean'
			),
        ),
    ),
	// Sales
	'GetSale' => array(
        'function' => 'GetSale',
        'signature' => array(
            array('string', 'string', 'string', 'string'),
        ),
    ),
	'GetSales' => array(
        'function' => 'GetSales',
        'signature' => array(
            array('string', 'string', 'string', 'string', 'string', 'string', 'boolean', 'string'),
        ),
    ),
    'MassAddSale' => array(
        'function' => 'MassAddSale',
        'signature' => array(
            array(
				'string', 'array'
			),
        ),
    ),
    'AddSale' => array(
        'function' => 'AddSale',
        'signature' => array(
			array(
				'string', 'string', 'string', 'string', 'string', 'string', 'string', 'string', 'string', 'string', 
				'string', 'string', 'string', 'string', 'string'
				
			),
        ),
    ),
    'UpdateSale' => array(
        'function' => 'UpdateSale',
        'signature' => array(
			array(
				'string', 'string', 'string', 'string', 'string', 'string', 'string', 'string', 'string', 'string', 'string', 
				'string', 'string', 'string'
			),
        ),
    ),
    'DeleteSale' => array(
        'function' => 'DeleteSale',
        'signature' => array(
            array(
				'string', 'string', 'string', 'string', 'boolean'
			),
        ),
    ),
	// Brands
	'GetBrand' => array(
        'function' => 'GetBrand',
        'signature' => array(
            array('string', 'string', 'string', 'string'),
        ),
    ),
	'GetBrands' => array(
        'function' => 'GetBrands',
        'signature' => array(
            array('string', 'string', 'string', 'int', 'string', 'string', 'boolean', 'string'),
        ),
    ),
    'MassAddBrand' => array(
        'function' => 'MassAddBrand',
        'signature' => array(
            array(
				'string', 'array'
			),
        ),
    ),
    'AddBrand' => array(
        'function' => 'AddBrand',
        'signature' => array(
			array(
				'string', 'string', 'string', 'string', 'string', 'string', 'int', 'int', 'int', 'string', 'string', 
				'string', 'string', 'string', 'string', 'string', 'string'
				
			),
        ),
    ),
    'UpdateBrand' => array(
        'function' => 'UpdateBrand',
        'signature' => array(
			array(
				'string', 'string', 'string', 'string', 'string', 'string', 'string', 'int', 'int', 'int', 'string', 'string', 
				'string', 'string', 'string', 'string',
				
			),
        ),
    ),
    'DeleteBrand' => array(
        'function' => 'DeleteBrand',
        'signature' => array(
            array(
				'string', 'string', 'string', 'string', 'boolean'
			),
        ),
    )
);
