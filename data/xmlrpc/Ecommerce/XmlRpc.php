<?php
/**
 * Ecommerce XML RPC
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2011 Alan Valkoun
 * @package Ecommerce
 */
require_once JAWS_PATH . 'include/Jaws/InitApplication.php';
$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');
require_once JAWS_PATH . 'include/Jaws/User.php';
require_once JAWS_PATH . 'libraries/pear/' . 'XML/RPC/Server.php';

/**
 * Get ACL permission for a specified user
 */
function GetEcommercePermission($user, $task, $user_type)
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
    return $GLOBALS['app']->ACL->GetFullPermission($user, $groups, 'Ecommerce', $task);
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
 * GetOrder
 */
function GetOrder($params)
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
    if (!GetEcommercePermission($user, 'ManageEcommerce', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $model = $GLOBALS['app']->loadGadget('Ecommerce', 'Model');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }
	
	// Get item by checksum
	$res = $model->GetOrderByChecksum($checksum);
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
 * GetOrders
 */
function GetOrders($params)
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
    if (!GetEcommercePermission($user, 'ManageEcommerce', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $model = $GLOBALS['app']->loadGadget('Ecommerce', 'Model');
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
			$response = new XML_RPC_Value(array(), 'array');
			return new XML_RPC_Response($response);
		}
	} else {
		$OwnerID = null;
	}
	
	$res = $model->GetOrders($limit, $sortColumn, $sortDir, $offSet, $OwnerID);
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
 * GetEcommerceOfUserID
 */
function GetEcommerceOfUserID($params)
{
    // parameters
	$user 		= getScalarValue($params, 0);
	$password 	= getScalarValue($params, 1);
    $checksum	= getScalarValue($params, 2);
    $customer_checksum	= getScalarValue($params, 2);
    $active 	= getScalarValue($params, 4);
	if (empty($active)) {
		$active = null;
	}

	$userModel = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $userModel->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+3,  _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetEcommercePermission($user, 'ManageEcommerce', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $model = $GLOBALS['app']->loadGadget('Ecommerce', 'Model');
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
			$response = new XML_RPC_Value(array(), 'array');
			return new XML_RPC_Response($response);
		}
	} else {
		$OwnerID = null;
	}
	
	// Get user by checksum
	if (!empty($customer_checksum) && $customer_checksum != '0') {
		$user = $userModel->GetUserByChecksum($customer_checksum);
		if (Jaws_Error::isError($user)) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $user->GetMessage());
		}
		if (isset($user['id'])) {
			$customer = $user['id'];
		} else {
			$response = new XML_RPC_Value(array(), 'array');
			return new XML_RPC_Response($response);
		}
	} else {
		$customer = null;
	}
	
	$res = $model->GetEcommerceOfUserID($OwnerID, $customer, $active);
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
 * GetEcommerceOfGroup
 */
function GetEcommerceOfGroup($params)
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
    if (!GetEcommercePermission($user, 'ManageEcommerce', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $model = $GLOBALS['app']->loadGadget('Ecommerce', 'Model');
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
			$val = new XML_RPC_Value("false", 'boolean');
			return new XML_RPC_Response($val);
		}
	} else {
		$gid = 0;
	}
	
	$res = $model->GetEcommerceOfGroup($gid, $sortColumn, $sortDir, $active);
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
 * AddOrder
 */
function AddOrder($params)
{
	// parameters
	$user 					= getScalarValue($params, 0);
	$password 				= getScalarValue($params, 1);
    $orderno				= getScalarValue($params, 2);
    $prod_id   				= getScalarValue($params, 3);
    $price 					= getScalarValue($params, 4);
    $qty   					= getScalarValue($params, 5);
    $unit    				= getScalarValue($params, 6);
    $weight    				= getScalarValue($params, 7);
    $attribute    			= getScalarValue($params, 8);
    $backorder    			= getScalarValue($params, 9);
    $description    		= getScalarValue($params, 10);
    $recurring    			= getScalarValue($params, 11);
    $gadget_table			= getScalarValue($params, 12);
    $gadget_id				= getScalarValue($params, 13);
    $OwnerChecksum    		= getScalarValue($params, 14);
    $active    				= getScalarValue($params, 15);
    $customer_email    		= getScalarValue($params, 16);
    $customer_name    		= getScalarValue($params, 17);
    $customer_company    	= getScalarValue($params, 18);
    $customer_address    	= getScalarValue($params, 19);
    $customer_address2    	= getScalarValue($params, 20);
    $customer_city    		= getScalarValue($params, 21);
    $customer_region    	= getScalarValue($params, 22);
    $customer_postal    	= getScalarValue($params, 23);
    $customer_country    	= getScalarValue($params, 24);
    $customer_phone    		= getScalarValue($params, 25);
    $customer_fax    		= getScalarValue($params, 26);
    $customer_shipname		= getScalarValue($params, 27);
    $customer_shipaddress   = getScalarValue($params, 28);
    $customer_shipaddress2  = getScalarValue($params, 29);
    $customer_shipcity    	= getScalarValue($params, 30);
    $customer_shipregion    = getScalarValue($params, 31);
    $customer_shippostal    = getScalarValue($params, 32);
    $customer_shipcountry   = getScalarValue($params, 33);
    $total    				= getScalarValue($params, 34);
    $shiptype    			= getScalarValue($params, 35);
    $customer_checksum    	= getScalarValue($params, 36);
    $checksum    			= getScalarValue($params, 37);

	if ((int)$qty <= 0) {
		$qty = 1;
	}
	if ((int)$gadget_id <= 0) {
		$gadget_id = null;
	}
	if (empty($recurring)) {
		$recurring = 'N';
	}
	if (empty($active)) {
		$active = 'NEW';
	}
    /*
	$productparentCategory_Name = parseContent($productparentCategory_Name);
    $productparentDescription = parseContent($productparentDescription);
    $productparentimage_code = parseContent($productparentimage_code);
	*/
	
	$userModel = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $userModel->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetEcommercePermission($user, 'ManageEcommerce', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $model = $GLOBALS['app']->loadGadget('Ecommerce', 'AdminModel');
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

	// Get user by checksum
	if (!empty($customer_checksum) && $customer_checksum != '0') {
		$user = $userModel->GetUserByChecksum($customer_checksum);
		if (Jaws_Error::isError($user)) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $user->GetMessage());
		}
		if (isset($user['id'])) {
			$customer = $user['id'];
		} else {
			$val = new XML_RPC_Value("false", 'boolean');
			return new XML_RPC_Response($val);
		}
	} else {
		$customer = null;
	}

    $post_id = $model->AddOrder(
		$orderno, $prod_id, $price, $qty, $unit, $weight, 
		$attribute, $backorder, $description, $recurring, 
		$gadget_table, $gadget_id, $OwnerID, $active, $customer_email, $customer_name, 
		$customer_company, $customer_address, $customer_address2, 
		$customer_city, $customer_region, $customer_postal, $customer_country, 
		$customer_phone, $customer_fax, $customer_shipname, $customer_shipaddress, 
		$customer_shipaddress2, $customer_shipcity, $customer_shipregion, 
		$customer_shippostal, $customer_shipcountry, $total, $shiptype, $customer, $checksum
	);
    if (Jaws_Error::IsError($post_id)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, $post_id->GetMessage());
    }

    $val = new XML_RPC_Value("$post_id", 'string');
    return new XML_RPC_Response($val);
}

/*
 * UpdateOrder
 */
function UpdateOrder($params)
{
	// parameters
	$user 					= getScalarValue($params, 0);
	$password 				= getScalarValue($params, 1);
    $checksum				= getScalarValue($params, 2);
    $orderno				= getScalarValue($params, 3);
    $prod_id   				= getScalarValue($params, 4);
    $price 					= getScalarValue($params, 5);
    $qty   					= getScalarValue($params, 6);
    $unit    				= getScalarValue($params, 7);
    $weight    				= getScalarValue($params, 8);
    $attribute    			= getScalarValue($params, 9);
    $backorder    			= getScalarValue($params, 10);
    $description    		= getScalarValue($params, 11);
    $recurring    			= getScalarValue($params, 12);
    $gadget_table			= getScalarValue($params, 13);
    $gadget_id				= getScalarValue($params, 14);
    $active    				= getScalarValue($params, 15);
    $customer_email    		= getScalarValue($params, 16);
    $customer_name    		= getScalarValue($params, 17);
    $customer_company    	= getScalarValue($params, 18);
    $customer_address    	= getScalarValue($params, 19);
    $customer_address2    	= getScalarValue($params, 20);
    $customer_city    		= getScalarValue($params, 21);
    $customer_region    	= getScalarValue($params, 22);
    $customer_postal    	= getScalarValue($params, 23);
    $customer_country    	= getScalarValue($params, 24);
    $customer_phone    		= getScalarValue($params, 25);
    $customer_fax    		= getScalarValue($params, 26);
    $customer_shipname		= getScalarValue($params, 27);
    $customer_shipaddress   = getScalarValue($params, 28);
    $customer_shipaddress2  = getScalarValue($params, 29);
    $customer_shipcity    	= getScalarValue($params, 30);
    $customer_shipregion    = getScalarValue($params, 31);
    $customer_shippostal    = getScalarValue($params, 32);
    $customer_shipcountry   = getScalarValue($params, 33);
    $total    				= getScalarValue($params, 34);
    $shiptype    			= getScalarValue($params, 35);

	if (empty($recurring)) {
		$recurring = 'N';
	}
	if (empty($active)) {
		$active = 'NEW';
	}

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
    if (!GetEcommercePermission($user, 'ManageEcommerce', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $adminModel = $GLOBALS['app']->loadGadget('Ecommerce', 'AdminModel');
    if (Jaws_Error::isError($adminModel)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $adminModel->GetMessage());
    }
    $model = $GLOBALS['app']->loadGadget('Ecommerce', 'Model');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }

    // Get item by checksum
	$info = $model->GetOrderByChecksum($checksum);
    if (Jaws_Error::isError($info)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $info->GetMessage());
    }
	if (isset($info['id'])) {
		$post_id = $adminModel->UpdateOrder(
			$info['id'], $orderno, $prod_id, $price, $qty, $unit, $weight, 
			$attribute, $backorder, $description, $recurring, 
			$gadget_table, $gadget_id, $active, $customer_email, $customer_name, 
			$customer_company, $customer_address, $customer_address2, 
			$customer_city, $customer_region, $customer_postal, $customer_country, 
			$customer_phone, $customer_fax, $customer_shipname, $customer_shipaddress, 
			$customer_shipaddress2, $customer_shipcity, $customer_shipregion, 
			$customer_shippostal, $customer_shipcountry, $total, $shiptype
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
 * DeleteOrder
 */
function DeleteOrder($params)
{
    // parameters
	$user 		= getScalarValue($params, 0);
	$password 	= getScalarValue($params, 1);
    $checksum	= getScalarValue($params, 2);
    $massive	= getScalarValue($params, 3);
	$massive = ((bool)$massive !== true ? false : true);
	
	$model = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $model->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetEcommercePermission($user, 'ManageEcommerce', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $adminModel = $GLOBALS['app']->loadGadget('Ecommerce', 'AdminModel');
    if (Jaws_Error::isError($adminModel)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $adminModel->GetMessage());
    }
    $model = $GLOBALS['app']->loadGadget('Ecommerce', 'Model');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }

    // Get item by checksum
	$info = $model->GetOrderByChecksum($checksum);
    if (Jaws_Error::isError($info)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $info->GetMessage());
    }
		
	if (isset($info['id'])) {	
		$post_id = $adminModel->DeleteOrder($info['id'], $massive);
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
 * GetShipping
 */
function GetShipping($params)
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
    if (!GetEcommercePermission($user, 'ManageEcommerce', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $model = $GLOBALS['app']->loadGadget('Ecommerce', 'Model');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }
	
    // Get item by checksum
	$res = $model->GetShippingByChecksum($checksum);
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
 * GetShippings
 */
function GetShippings($params)
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
    if (!GetEcommercePermission($user, 'ManageEcommerce', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $model = $GLOBALS['app']->loadGadget('Ecommerce', 'Model');
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
			$response = new XML_RPC_Value(array(), 'array');
			return new XML_RPC_Response($response);
		}
	} else {
		$OwnerID = null;
	}
	
	$res = $model->GetShippings($limit, $sortColumn, $sortDir, $offSet, $OwnerID);
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
 * AddShipping
 */
function AddShipping($params)
{
	/*
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
		$OwnerID = $user['id'];
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
	*/
	return true;
}

/*
 * UpdateShipping
 */
function UpdateShipping($params)
{
	/*
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
    $coupon_code  		= getScalarValue($params, 9);
    $featured    		= getScalarValue($params, 10);
    $Active    			= getScalarValue($params, 11);
	
	$model = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $model->Valid($user, $password))) {
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

    // Get item by checksum
	$info = $model->GetSaleByChecksum($checksum);
    if (Jaws_Error::isError($info)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $info->GetMessage());
    }
		
	$post_id = $model->UpdateSale(
		$info['id'], $title, $startdate, $enddate, $description, $discount_amount, 
		$discount_percent, $discount_newprice, $coupon_code, $featured, $Active, true
	);
    if (Jaws_Error::IsError($post_id)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, $post_id->GetMessage());
    }

    $val = new XML_RPC_Value("$post_id", 'boolean');
    return new XML_RPC_Response($val);
	*/
	return true;
}

/*
 * DeleteShipping
 */
function DeleteShipping($params)
{
    /*
	// parameters
	$user 		= getScalarValue($params, 0);
	$password 	= getScalarValue($params, 1);
    $checksum	= getScalarValue($params, 2);
    $massive	= getScalarValue($params, 3);
	$massive = ((bool)$massive !== true ? false : true);
	
	$model = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $model->Valid($user, $password))) {
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

    // Get item by checksum
	$info = $model->GetSaleByChecksum($checksum);
    if (Jaws_Error::isError($info)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $info->GetMessage());
    }
		
	$post_id = $model->DeleteSale($info['id'], $massive);
    if (Jaws_Error::IsError($post_id)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, $post_id->GetMessage());
    }

    $val = new XML_RPC_Value("$post_id", 'boolean');
    return new XML_RPC_Response($val);
	*/
	return true;
}

/*
 * GetTax
 */
function GetTax($params)
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
    if (!GetEcommercePermission($user, 'ManageEcommerce', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $model = $GLOBALS['app']->loadGadget('Ecommerce', 'Model');
    if (Jaws_Error::isError($model)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $model->GetMessage());
    }
	
    // Get item by checksum
	$res = $model->GetTaxByChecksum($checksum);
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
 * GetTaxes
 */
function GetTaxes($params)
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
    if (!GetEcommercePermission($user, 'ManageEcommerce', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

    $model = $GLOBALS['app']->loadGadget('Ecommerce', 'Model');
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
			$response = new XML_RPC_Value(array(), 'array');
			return new XML_RPC_Response($response);
		}
	} else {
		$OwnerID = null;
	}
	
	$res = $model->GetTaxes($limit, $sortColumn, $sortDir, $offSet, $OwnerID);
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
 * AddTax
 */
function AddTax($params)
{
	/*
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
		$OwnerID = $user['id'];
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
	*/
	return true;
}

/*
 * UpdateTax
 */
function UpdateTax($params)
{
	/*
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
    $coupon_code  		= getScalarValue($params, 9);
    $featured    		= getScalarValue($params, 10);
    $Active    			= getScalarValue($params, 11);
	
	$model = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $model->Valid($user, $password))) {
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

    // Get item by checksum
	$info = $model->GetSaleByChecksum($checksum);
    if (Jaws_Error::isError($info)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $info->GetMessage());
    }
		
	$post_id = $model->UpdateSale(
		$info['id'], $title, $startdate, $enddate, $description, $discount_amount, 
		$discount_percent, $discount_newprice, $coupon_code, $featured, $Active, true
	);
    if (Jaws_Error::IsError($post_id)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, $post_id->GetMessage());
    }

    $val = new XML_RPC_Value("$post_id", 'boolean');
    return new XML_RPC_Response($val);
	*/
	return true;
}

/*
 * DeleteTax
 */
function DeleteTax($params)
{
    /*
	// parameters
	$user 		= getScalarValue($params, 0);
	$password 	= getScalarValue($params, 1);
    $checksum	= getScalarValue($params, 2);
    $massive	= getScalarValue($params, 3);
	$massive = ((bool)$massive !== true ? false : true);
	
	$model = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $model->Valid($user, $password))) {
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

    // Get item by checksum
	$info = $model->GetSaleByChecksum($checksum);
    if (Jaws_Error::isError($info)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $info->GetMessage());
    }
		
	$post_id = $model->DeleteSale($info['id'], $massive);
    if (Jaws_Error::IsError($post_id)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, $post_id->GetMessage());
    }

    $val = new XML_RPC_Value("$post_id", 'boolean');
    return new XML_RPC_Response($val);
	*/
	return true;
}


/*
 *  XML-RPC Server
 */

$rpc_methods = array(
    // Orders
    'GetOrder' => array(
        'function' => 'GetOrder',
        'signature' => array(
            array('string', 'string', 'string', 'string'),
        ),
    ),
    'GetOrders' => array(
        'function' => 'GetOrders',
        'signature' => array(
            array('string', 'string', 'string', 'int', 'string', 'string', 'int', 'string'),
        ),
    ),
	'GetEcommerceOfUserID' => array(
        'function' => 'GetEcommerceOfUserID',
        'signature' => array(
            array('string', 'string', 'string', 'string', 'string', 'string'),
        ),
    ),
	'GetEcommerceOfGroup' => array(
        'function' => 'GetEcommerceOfGroup',
        'signature' => array(
            array('string', 'string', 'string', 'string', 'string', 'string', 'string'),
        ),
    ),
    'AddOrder' => array(
        'function' => 'AddOrder',
        'signature' => array(
			array(
				'string', 'string', 'string', 'int', 'int', 'string', 'int', 'string', 'string', 'string', 
				'int', 'string', 'string', 'string', 'int', 'string', 'string', 'string', 
				'string', 'string', 'string', 'string', 'string', 'string', 'string', 'string', 
				'string', 'string', 'string', 'string', 'string', 'string', 'string', 'string',
				'string', 'string', 'string', 'string', 'string'
			),
        ),
    ),
    'UpdateOrder' => array(
        'function' => 'UpdateOrder',
        'signature' => array(
			array(
				'string', 'string', 'string', 'string', 'int', 'int', 'string', 'int', 'string', 'string', 
				'string', 'int', 'string', 'string', 'string', 'int', 'string', 'string', 'string', 
				'string', 'string', 'string', 'string', 'string', 'string', 'string', 'string', 
				'string', 'string', 'string', 'string', 'string', 'string', 'string',
				'string', 'string', 'string'
			),
        ),
    ),
    'DeleteOrder' => array(
        'function' => 'DeleteOrder',
        'signature' => array(
            array(
				'string', 'string', 'string', 'string', 'boolean'
			),
        ),
    ),
	// Shipping
	'GetShipping' => array(
        'function' => 'GetShipping',
        'signature' => array(
            array('string', 'string', 'string', 'string'),
        ),
    ),
	'GetShippings' => array(
        'function' => 'GetShippings',
        'signature' => array(
            array('string', 'string', 'string', 'string', 'string', 'string', 'boolean', 'string'),
        ),
    ),
    'AddShipping' => array(
        'function' => 'AddShipping',
        'signature' => array(
			array(
				'string', 'string', 'string', 'string', 'string', 'string', 'string', 'int', 'int', 'int', 
				'string', 'string', 'string', 'string', 'string'
				
			),
        ),
    ),
    'UpdateShipping' => array(
        'function' => 'UpdateShipping',
        'signature' => array(
			array(
				'string', 'string', 'string', 'string', 'string', 'string', 'string', 'string', 'int', 'int', 'int', 
				'string', 'string', 'string'
			),
        ),
    ),
    'DeleteShipping' => array(
        'function' => 'DeleteShipping',
        'signature' => array(
            array(
				'string', 'string', 'string', 'string', 'boolean'
			),
        ),
    ),
	// Taxes
	'GetTax' => array(
        'function' => 'GetTax',
        'signature' => array(
            array('string', 'string', 'string', 'string'),
        ),
    ),
	'GetTaxes' => array(
        'function' => 'GetTaxes',
        'signature' => array(
            array('string', 'string', 'string', 'int', 'string', 'string', 'boolean', 'string'),
        ),
    ),
    'AddTax' => array(
        'function' => 'AddTax',
        'signature' => array(
			array(
				'string', 'string', 'string', 'string', 'string', 'string', 'int', 'int', 'int', 'string', 'string', 
				'string', 'string', 'string', 'string', 'string', 'string'
				
			),
        ),
    ),
    'UpdateTax' => array(
        'function' => 'UpdateTax',
        'signature' => array(
			array(
				'string', 'string', 'string', 'string', 'string', 'string', 'string', 'int', 'int', 'int', 'string', 'string', 
				'string', 'string', 'string', 'string',
				
			),
        ),
    ),
    'DeleteTax' => array(
        'function' => 'DeleteTax',
        'signature' => array(
            array(
				'string', 'string', 'string', 'string', 'boolean'
			),
        ),
    )
);