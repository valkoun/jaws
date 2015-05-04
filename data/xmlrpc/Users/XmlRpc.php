<?php
/**
 * Users XML RPC
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2011 Alan Valkoun
 * @package Users
 */
require_once JAWS_PATH . 'include/Jaws/InitApplication.php';
$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');
require_once JAWS_PATH . 'include/Jaws/User.php';
require_once JAWS_PATH . 'libraries/pear/' . 'XML/RPC/Server.php';

/**
 * Get ACL permission for a specified user
 */
function GetUsersPermission($user, $task, $user_type)
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
    return $GLOBALS['app']->ACL->GetFullPermission($user, $groups, 'Users', $task);
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
 * GetUsers
 */
function GetUsers($params)
{
    // parameters
	$user 		= getScalarValue($params, 0);
	$password 	= getScalarValue($params, 1);
	$group 		= getScalarValue($params, 2);
	$type 		= getScalarValue($params, 3);
	$enabled 	= getScalarValue($params, 4);
	$enabled 	= ($enabled == 3 ? null : ($enabled == 1 ? true : false));
	$orderBy 	= getScalarValue($params, 5);
	$limit 		= getScalarValue($params, 6);
	$offset 	= getScalarValue($params, 7);

	if ($group == 0) {
		$group = false;
	}
	if ($type > 2) {
		$type = false;
	}
	if (empty($orderBy)) {
		$orderBy = 'nickname';
	}
	if ($offset == 0) {
		$offset = null;
	}

	$model = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $model->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+3,  _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetUsersPermission($user, 'ManageUsers', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }
	
	$res = $model->GetUsers($group, $type, $enabled, $orderBy, $limit, $offset);
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
 * GetUserInfoById
 */
function GetUserInfoById($params)
{
    // parameters
	$user 			= getScalarValue($params, 0);
	$password 		= getScalarValue($params, 1);
    $id				= getScalarValue($params, 2);
    $account		= getScalarValue($params, 3);
	$account 		= ((bool)$account !== true ? false : true);
    $personal		= getScalarValue($params, 4);
	$personal 		= ((bool)$personal !== true ? false : true);
    $preferences	= getScalarValue($params, 5);
 	$preferences 	= ((bool)$preferences !== true ? false : true);
	$extra			= getScalarValue($params, 6);
	$extra 			= ((bool)$extra !== true ? false : true);

	$model = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $model->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+3,  _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetUsersPermission($user, 'ManageUsers', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }
	
	// Get user by id
	$res = $model->GetUserInfoById($id, $account, $personal, $preferences, $extra);
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
 * GetUserByChecksum
 */
function GetUserByChecksum($params)
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
    if (!GetUsersPermission($user, 'ManageUsers', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }
	
	// Get user by checksum
	$res = $model->GetUserByChecksum($checksum);
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
 * GetUserInfoByName
 */
function GetUserInfoByName($params)
{
    // parameters
	$user 			= getScalarValue($params, 0);
	$password 		= getScalarValue($params, 1);
    $username		= getScalarValue($params, 2);
    $account		= getScalarValue($params, 3);
	$account 		= ((bool)$account !== true ? false : true);
    $personal		= getScalarValue($params, 4);
	$personal 		= ((bool)$personal !== true ? false : true);
    $preferences	= getScalarValue($params, 5);
 	$preferences 	= ((bool)$preferences !== true ? false : true);
	$extra			= getScalarValue($params, 6);
	$extra 			= ((bool)$extra !== true ? false : true);

	$model = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $model->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+3,  _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetUsersPermission($user, 'ManageUsers', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }
		
	$res = $model->GetUserInfoByName($username, $account, $personal, $preferences, $extra);
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
 * GetUserInfoByIdentifier
 */
function GetUserInfoByIdentifier($params)
{
    // parameters
	$user 			= getScalarValue($params, 0);
	$password 		= getScalarValue($params, 1);
    $identifier		= getScalarValue($params, 2);
    $account		= getScalarValue($params, 3);
	$account 		= ((bool)$account !== true ? false : true);
    $personal		= getScalarValue($params, 4);
	$personal 		= ((bool)$personal !== true ? false : true);
    $preferences	= getScalarValue($params, 5);
 	$preferences 	= ((bool)$preferences !== true ? false : true);
	$extra			= getScalarValue($params, 6);
	$extra 			= ((bool)$extra !== true ? false : true);

	$model = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $model->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+3,  _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetUsersPermission($user, 'ManageUsers', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }
		
	$res = $model->GetUserInfoByIdentifier($identifier, $account, $personal, $preferences, $extra);
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
 * GetUsersOfGroup
 */
function GetUsersOfGroup($params)
{
    // parameters
	$user 		= getScalarValue($params, 0);
	$password 	= getScalarValue($params, 1);
	$checksum 	= getScalarValue($params, 2);

	$model = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $model->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+3,  _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetUsersPermission($user, 'ManageUsers', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }
	
	$group = $model->GetGroupByChecksum($checksum);
    if (Jaws_Error::isError($group)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $group->GetMessage());
    }
	
	if (isset($group['id'])) {
		$res = $model->GetUsersOfGroup($group['id']);
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
		$val = new XML_RPC_Value(array(), 'false');
		return new XML_RPC_Response($val);
	}
}

/*
 * MassAddUser
 */
function MassAddUser($params)
{
	$param = $params->getParam(0);
    $par = $param->me['array'];
    //var_dump($par);
	foreach ($par as $p) {
		//var_dump($p);
		$msg = new XML_RPC_Message('AddUser', $p->me['array']);
		$res = AddUser($msg);
		$val = $res->value();
		if (!is_object($val) || !is_a($val, 'XML_RPC_value')) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, var_export($val, true));
		}
	}
	return new XML_RPC_Response(new XML_RPC_Value("true", 'boolean'));
}

/*
 * AddUser
 */
function AddUser($params)
{
    // parameters
	$user 		= getScalarValue($params, 0);
	$password 	= getScalarValue($params, 1);
	$username	= getScalarValue($params, 2);
    $nickname 	= getScalarValue($params, 3);
    $email   	= getScalarValue($params, 4);
    $password2   = getScalarValue($params, 5);
    $type    	= getScalarValue($params, 6);
    $enabled    = getScalarValue($params, 7);
	$enabled 	= ((bool)$enabled !== true ? false : true);
	$checksum   = getScalarValue($params, 8);
	$address    = getScalarValue($params, 9);
	$address2   = getScalarValue($params, 10);
	$city		= getScalarValue($params, 11);
	$country   	= getScalarValue($params, 12);
	$region   	= getScalarValue($params, 13); 
	$postal   	= getScalarValue($params, 14);
	$rpx   		= getScalarValue($params, 15); 
	$rpx 		= ((bool)$rpx !== true ? false : true);
	$redirect_to = getScalarValue($params, 16);

	$model = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $model->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetUsersPermission($user, 'ManageUsers', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }
    
	$uModel = $GLOBALS['app']->loadGadget('Users', 'Model');
    if (Jaws_Error::isError($uModel)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $uModel->GetMessage());
    }
	
	// Get group by checksum
	$groupName = '';
	$info = $model->GetGroupByChecksum($group);
    if (Jaws_Error::isError($info)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $info->GetMessage());
    } else if (isset($info['name']) && !empty($info['name'])) {
		$groupName = $info['name'];
	}
			
	$res = $uModel->CreateUser(
		$username, $email, $nickname, '', '', '', '', '', $password2, $type, null, $address, $address2, 
		$city, $country, $region, $postal, $checksum, $rpx, $redirect_to
	);
    if ($res !== true) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, $res);
    }
	$GLOBALS['app']->Registry->LoadFile('Users');
	$GLOBALS['app']->Translate->LoadTranslation('Users', JAWS_GADGET);
	$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
	if (
		$res == _t('USERS_USERS_ALREADY_EXISTS', $xss->filter($username)) ||
		$res == _t('USERS_EMAIL_ALREADY_EXISTS', $email)
	) {
		$val = new XML_RPC_Value("true", 'boolean');
		return new XML_RPC_Response($val);
	}
	
    if ($res !== true) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, var_export($res, true));
		$val = new XML_RPC_Value("false", 'boolean');
		return new XML_RPC_Response($val);
	} else {
		$info = $model->GetUserInfoByName($username);
		if (Jaws_Error::isError($info)) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $info->GetMessage());
		}
		$post_id = $info['id'];
	}
	
	$val = new XML_RPC_Value("$post_id", 'string');
    return new XML_RPC_Response($val);
}

/*
 * MassUpdateUser
 */
function MassUpdateUser($params)
{
	$param = $params->getParam(0);
    $par = $param->me['array'];
    //var_dump($par);
	foreach ($par as $p) {
		//var_dump($p);
		$msg = new XML_RPC_Message('UpdateUser', $p->me['array']);
		$res = UpdateUser($msg);
		$val = $res->value();
		if (!is_object($val) || !is_a($val, 'XML_RPC_value')) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, var_export($val, true));
		}
	}
	return new XML_RPC_Response(new XML_RPC_Value("true", 'boolean'));
}

/*
 * UpdateUser
 */
function UpdateUser($params)
{
    // parameters
	$user 			= getScalarValue($params, 0);
	$password 		= getScalarValue($params, 1);
    $checksum		= getScalarValue($params, 2);
    $username   	= getScalarValue($params, 3);
    $nickname 		= getScalarValue($params, 4);
    $email			= getScalarValue($params, 5);
    $password2   	= getScalarValue($params, 6);
    $type    		= getScalarValue($params, 7);
    $enabled    	= getScalarValue($params, 8);
	$enabled 		= ($enabled == 3 ? null : ($enabled == 1 ? true : false));

	if (empty($password2)) {
		$password2 = null;
	}
	if ($type > 2) {
		$type = null;
	}
	
	$model = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $model->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetUsersPermission($user, 'ManageUsers', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }
	
	// Get user by checksum
	$info = $model->GetUserByChecksum($checksum);
    if (Jaws_Error::isError($info)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $info->GetMessage());
    }
	if (isset($info['id'])) {
		$post_id = $model->UpdateUser(
			$info['id'], $username, $nickname, $email, $password2, $type, $enabled
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
 * MassUpdatePersonalInfo
 */
function MassUpdatePersonalInfo($params)
{
	$param = $params->getParam(0);
    $par = $param->me['array'];
    //var_dump($par);
	foreach ($par as $p) {
		//var_dump($p);
		$msg = new XML_RPC_Message('UpdatePersonalInfo', $p->me['array']);
		$res = UpdatePersonalInfo($msg);
		$val = $res->value();
		if (!is_object($val) || !is_a($val, 'XML_RPC_value')) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, var_export($val, true));
		}
	}
	return new XML_RPC_Response(new XML_RPC_Value("true", 'boolean'));
}

/*
 * UpdatePersonalInfo
 */
function UpdatePersonalInfo($params)
{
    // parameters
	$user 			= getScalarValue($params, 0);
	$password 		= getScalarValue($params, 1);
    $checksum		= getScalarValue($params, 2);
    $fname			= getScalarValue($params, 3);
    $lname			= getScalarValue($params, 4);
    $gender			= getScalarValue($params, 5);
    $dob			= getScalarValue($params, 6);
	$dob			= (is_null($dob) || strtolower($dob) == 'null' ? null : date('Y-m-d H:i:s', strtotime($dob)));    
	$url			= getScalarValue($params, 7);
    $company		= getScalarValue($params, 8);
    $address		= getScalarValue($params, 9);
    $address2		= getScalarValue($params, 10);
    $city			= getScalarValue($params, 11);
    $country		= getScalarValue($params, 12);
    $region			= getScalarValue($params, 13);
    $postal			= getScalarValue($params, 14);
    $phone			= getScalarValue($params, 15);
    $office			= getScalarValue($params, 16);
    $tollfree		= getScalarValue($params, 17);
    $fax			= getScalarValue($params, 18);
    $merchant_id	= getScalarValue($params, 19);
    $description	= getScalarValue($params, 20);
    $logo			= getScalarValue($params, 21);
    $keywords		= getScalarValue($params, 22);
    $company_type	= getScalarValue($params, 23);
	$pInfo = array(
		'fname' => $fname, 
		'lname' => $lname, 
		'gender' => $gender, 
		'dob' => $dob, 
		'url' => $url, 
		'company' => $company, 
		'address' => $address, 
		'address2' => $address2, 
		'city' => $city, 
		'country' => $country, 
		'region' => $region, 
		'postal' => $postal, 
		'phone' => $phone, 
		'office' => $office, 
		'tollfree' => $tollfree, 
		'fax' => $fax, 
		'merchant_id' => $merchant_id, 
		'description' => parseContent($description), 
		'logo' => $logo, 
		'keywords' => parseContent($keywords), 
		'company_type' => $company_type
	);
	
	$model = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $model->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetUsersPermission($user, 'ManageUsers', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }
	
	// Get user by checksum
	$info = $model->GetUserByChecksum($checksum);
    if (Jaws_Error::isError($info)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $info->GetMessage());
    }
	if (isset($info['id'])) {
		$post_id = $model->UpdatePersonalInfo(
			$info['id'], $pInfo
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
 * MassUpdateAdvancedOptions
 */
function MassUpdateAdvancedOptions($params)
{
	$param = $params->getParam(0);
    $par = $param->me['array'];
    //var_dump($par);
	foreach ($par as $p) {
		//var_dump($p);
		$msg = new XML_RPC_Message('UpdateAdvancedOptions', $p->me['array']);
		$res = UpdateAdvancedOptions($msg);
		$val = $res->value();
		if (!is_object($val) || !is_a($val, 'XML_RPC_value')) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, var_export($val, true));
		}
	}
	return new XML_RPC_Response(new XML_RPC_Value("true", 'boolean'));
}

/*
 * UpdateAdvancedOptions
 */
function UpdateAdvancedOptions($params)
{
    // parameters
	$user 			= getScalarValue($params, 0);
	$password 		= getScalarValue($params, 1);
    $checksum		= getScalarValue($params, 2);
    $language		= getScalarValue($params, 3);
    $theme			= getScalarValue($params, 4);
    $editor			= getScalarValue($params, 5);
    $timezone		= getScalarValue($params, 6);
	$notification	= getScalarValue($params, 7);
    $allow_comments = getScalarValue($params, 8);
	$allow_comments = ((bool)$allow_comments !== true ? false : true);
    $public_gadget	= getScalarValue($params, 9);
	$public_gadget	= (empty($public_gadget) ? 'all' : $public_gadget);
	$pInfo = array(
		'language' => $language, 
		'theme' => $theme, 
		'editor' => $editor, 
		'timezone' => $timezone, 
		'notification' => $notification, 
		'allow_comments' => $allow_comments, 
		'public_gadget' => $public_gadget
	);

	$model = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $model->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetUsersPermission($user, 'ManageUsers', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }
	
	// Get user by checksum
	$info = $model->GetUserByChecksum($checksum);
    if (Jaws_Error::isError($info)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $info->GetMessage());
    }
	if (isset($info['id'])) {
		$post_id = $model->UpdateAdvancedOptions(
			$info['id'], $pInfo
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
 * DeleteUser
 */
function DeleteUser($params)
{
    // parameters
	$user 		= getScalarValue($params, 0);
	$password 	= getScalarValue($params, 1);
    $checksum	= getScalarValue($params, 2);
	
	$model = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $model->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetUsersPermission($user, 'ManageUsers', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }
	
	// Get user by checksum
	$info = $model->GetUserByChecksum($checksum);
    if (Jaws_Error::isError($info)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $info->GetMessage());
    }
	
	if (isset($info['id'])) {
		$post_id = $model->DeleteUser($info['id']);
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
 * MassAddUserToGroup
 */
function MassAddUserToGroup($params)
{
	$param = $params->getParam(0);
    $par = $param->me['array'];
    //var_dump($par);
	foreach ($par as $p) {
		//var_dump($p);
		$msg = new XML_RPC_Message('AddUserToGroup', $p->me['array']);
		$res = AddUserToGroup($msg);
		$val = $res->value();
		if (!is_object($val) || !is_a($val, 'XML_RPC_value')) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, var_export($val, true));
		}
	}
	return new XML_RPC_Response(new XML_RPC_Value("true", 'boolean'));
}

/*
 * AddUserToGroup
 */
function AddUserToGroup($params)
{
    // parameters
	$user 			= getScalarValue($params, 0);
	$password 		= getScalarValue($params, 1);
    $user_checksum	= getScalarValue($params, 2);
    $group_checksum = getScalarValue($params, 3);
    $status 		= getScalarValue($params, 4);

	if (empty($status)) {
		$status = 'active';
	}
	
	$model = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $model->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetUsersPermission($user, 'ManageUsers', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }
	
	// Get user by checksum
	$user = $model->GetUserByChecksum($user_checksum);
    if (Jaws_Error::isError($user)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $user->GetMessage());
    }

	if (isset($user['id'])) {
		// Get group by checksum
		$group = $model->GetGroupByChecksum($group_checksum);
		if (Jaws_Error::isError($group)) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $group->GetMessage());
		}
		if (isset($group['id'])) {
			$res = $model->AddUserToGroup($user['id'], $group['id'], $status);
			if (Jaws_Error::IsError($res)) {
				return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, $res->GetMessage());
			}
			
			$val = new XML_RPC_Value("$res", 'boolean');
			return new XML_RPC_Response($val);
		} else {
			$val = new XML_RPC_Value("false", 'boolean');
			return new XML_RPC_Response($val);
		}
	} else {
		$val = new XML_RPC_Value("false", 'boolean');
		return new XML_RPC_Response($val);
	}
}

/*
 * AddUserToGroupName
 */
function AddUserToGroupName($params)
{
    // parameters
	$user 			= getScalarValue($params, 0);
	$password 		= getScalarValue($params, 1);
    $user_checksum	= getScalarValue($params, 2);
    $group_name 	= getScalarValue($params, 3);
    $status 		= getScalarValue($params, 4);

	if (empty($status)) {
		$status = 'active';
	}
	
	$model = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $model->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetUsersPermission($user, 'ManageUsers', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }
	
	// Get user by checksum
	$user = $model->GetUserByChecksum($user_checksum);
    if (Jaws_Error::isError($user)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $user->GetMessage());
    }

	if (isset($user['id'])) {
		$res = $model->AddUserToGroup($user['id'], $group_name, $status);
		if (Jaws_Error::IsError($res)) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, $res->GetMessage());
		}
		
		$val = new XML_RPC_Value("$res", 'boolean');
		return new XML_RPC_Response($val);
	} else {
		$val = new XML_RPC_Value("false", 'boolean');
		return new XML_RPC_Response($val);
	}
}

/*
 * MassDeleteUserFromGroup
 */
function MassDeleteUserFromGroup($params)
{
	$param = $params->getParam(0);
    $par = $param->me['array'];
    //var_dump($par);
	foreach ($par as $p) {
		//var_dump($p);
		$msg = new XML_RPC_Message('DeleteUserFromGroup', $p->me['array']);
		$res = DeleteUserFromGroup($msg);
		$val = $res->value();
		if (!is_object($val) || !is_a($val, 'XML_RPC_value')) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, var_export($val, true));
		}
	}
	return new XML_RPC_Response(new XML_RPC_Value("true", 'boolean'));
}

/*
 * DeleteUserFromGroup
 */
function DeleteUserFromGroup($params)
{
    // parameters
	$user 			= getScalarValue($params, 0);
	$password 		= getScalarValue($params, 1);
    $user_checksum	= getScalarValue($params, 2);
    $group_checksum = getScalarValue($params, 3);

	$model = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $model->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetUsersPermission($user, 'ManageUsers', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }
	
	// Get user by checksum
	$user = $model->GetUserByChecksum($user_checksum);
    if (Jaws_Error::isError($user)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $user->GetMessage());
    }

	if (isset($user['id'])) {
		// Get group by checksum
		$group = $model->GetGroupByChecksum($group_checksum);
		if (Jaws_Error::isError($group)) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $group->GetMessage());
		}

		if (isset($group['id'])) {
			$res = $model->DeleteUserFromGroup($user['id'], $group['id']);
			if (Jaws_Error::IsError($res)) {
				return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, $res->GetMessage());
			}
			
			$val = new XML_RPC_Value("$res", 'boolean');
			return new XML_RPC_Response($val);
		} else {
			$val = new XML_RPC_Value("false", 'boolean');
			return new XML_RPC_Response($val);
		}
	} else {
		$val = new XML_RPC_Value("false", 'boolean');
		return new XML_RPC_Response($val);
	}
}

/*
 * MassDeleteUserFromAllGroups
 */
function MassDeleteUserFromAllGroups($params)
{
	$param = $params->getParam(0);
    $par = $param->me['array'];
    //var_dump($par);
	foreach ($par as $p) {
		//var_dump($p);
		$msg = new XML_RPC_Message('DeleteUserFromAllGroups', $p->me['array']);
		$res = DeleteUserFromAllGroups($msg);
		$val = $res->value();
		if (!is_object($val) || !is_a($val, 'XML_RPC_value')) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, var_export($val, true));
		}
	}
	return new XML_RPC_Response(new XML_RPC_Value("true", 'boolean'));
}

/*
 * DeleteUserFromAllGroups
 */
function DeleteUserFromAllGroups($params)
{
    // parameters
	$user 				= getScalarValue($params, 0);
	$password 			= getScalarValue($params, 1);
    $user_checksum		= getScalarValue($params, 2);
    $group_exceptions 	= getScalarValue($params, 3);

	$model = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $model->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetUsersPermission($user, 'ManageUsers', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }
	
	// Get user by checksum
	$user = $model->GetUserByChecksum($user_checksum);
    if (Jaws_Error::isError($user)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $user->GetMessage());
    }

	if (isset($user['id'])) {
		// Get exception groups
		$group_exceptions = explode(',',strtolower($group_exceptions));
		$groups = $model->GetAllGroups('name');
		if (Jaws_Error::isError($groups)) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $groups->GetMessage());
		}
		foreach ($groups as $group) {
			if (isset($group['id']) && !in_array(strtolower($group['name']), $group_exceptions)) {
				$res = $model->DeleteUserFromGroup($user['id'], $group['id']);
				if (Jaws_Error::IsError($res)) {
					return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, $res->GetMessage());
				}
				$val = new XML_RPC_Value("$res", 'boolean');
				return new XML_RPC_Response($val);
			} else {
				$val = new XML_RPC_Value("false", 'boolean');
				return new XML_RPC_Response($val);
			}
		}
	} else {
		$val = new XML_RPC_Value("false", 'boolean');
		return new XML_RPC_Response($val);
	}
}

/*
 * UserIsInGroup
 */
function UserIsInGroup($params)
{
    // parameters
	$user 			= getScalarValue($params, 0);
	$password 		= getScalarValue($params, 1);
    $user_checksum	= getScalarValue($params, 2);
    $group_checksum = getScalarValue($params, 3);

	$model = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $model->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetUsersPermission($user, 'ManageUsers', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }
	
	// Get user by checksum
	$user = $model->GetUserByChecksum($user_checksum);
    if (Jaws_Error::isError($user)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $user->GetMessage());
    }

	if (isset($user['id'])) {
		// Get group by checksum
		$group = $model->GetGroupByChecksum($group_checksum);
		if (Jaws_Error::isError($group)) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $group->GetMessage());
		}

		if (isset($group['id'])) {
			$res = $model->UserIsInGroup($user['id'], $group['id']);
			if (Jaws_Error::IsError($res)) {
				return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, $res->GetMessage());
			}
			
			$val = new XML_RPC_Value("$res", 'boolean');
			return new XML_RPC_Response($val);
		} else {
			$val = new XML_RPC_Value("false", 'boolean');
			return new XML_RPC_Response($val);
		}
	} else {
		$val = new XML_RPC_Value("false", 'boolean');
		return new XML_RPC_Response($val);
	}
}

/*
 * MassAddUserToFriend
 */
function MassAddUserToFriend($params)
{
	$param = $params->getParam(0);
    $par = $param->me['array'];
    //var_dump($par);
	foreach ($par as $p) {
		//var_dump($p);
		$msg = new XML_RPC_Message('AddUserToFriend', $p->me['array']);
		$res = AddUserToFriend($msg);
		$val = $res->value();
		if (!is_object($val) || !is_a($val, 'XML_RPC_value')) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, var_export($val, true));
		}
	}
	return new XML_RPC_Response(new XML_RPC_Value("true", 'boolean'));
}

/*
 * AddUserToFriend
 */
function AddUserToFriend($params)
{
    // parameters
	$user 			= getScalarValue($params, 0);
	$password 		= getScalarValue($params, 1);
    $user_checksum	= getScalarValue($params, 2);
    $friend_checksum = getScalarValue($params, 3);
    $status 		= getScalarValue($params, 4);

	if (empty($status)) {
		$status = 'request';
	}
	
	$model = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $model->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetUsersPermission($user, 'ManageUsers', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }
	
	// Get user by checksum
	$user = $model->GetUserByChecksum($user_checksum);
    if (Jaws_Error::isError($user)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $user->GetMessage());
    }

	if (isset($user['id'])) {
		// Get friend by checksum
		$friend = $model->GetUserByChecksum($friend_checksum);
		if (Jaws_Error::isError($friend)) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $friend->GetMessage());
		}

		if (isset($friend['id'])) {
			$res = $model->AddUserToFriend($user['id'], $friend['id'], $status);
			if (Jaws_Error::IsError($res)) {
				return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, $res->GetMessage());
			}
			
			$val = new XML_RPC_Value("$res", 'boolean');
			return new XML_RPC_Response($val);
		} else {
			$val = new XML_RPC_Value("false", 'boolean');
			return new XML_RPC_Response($val);
		}
	} else {
		$val = new XML_RPC_Value("false", 'boolean');
		return new XML_RPC_Response($val);
	}
}

/*
 * DeleteUserFromFriend
 */
function DeleteUserFromFriend($params)
{
    // parameters
	$user 			= getScalarValue($params, 0);
	$password 		= getScalarValue($params, 1);
    $user_checksum	= getScalarValue($params, 2);
    $friend_checksum = getScalarValue($params, 3);

	$model = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $model->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetUsersPermission($user, 'ManageUsers', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }
	
	// Get user by checksum
	$user = $model->GetUserByChecksum($user_checksum);
    if (Jaws_Error::isError($user)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $user->GetMessage());
    }

	if (isset($user['id'])) {
		// Get friend by checksum
		$friend = $model->GetUserByChecksum($friend_checksum);
		if (Jaws_Error::isError($friend)) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $friend->GetMessage());
		}

		if (isset($friend['id'])) {
			$res = $model->DeleteUserFromFriend($user['id'], $friend['id']);
			if (Jaws_Error::IsError($res)) {
				return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, $res->GetMessage());
			}
			
			$val = new XML_RPC_Value("$res", 'boolean');
			return new XML_RPC_Response($val);
		} else {
			$val = new XML_RPC_Value("false", 'boolean');
			return new XML_RPC_Response($val);
		}
	} else {
		$val = new XML_RPC_Value("false", 'boolean');
		return new XML_RPC_Response($val);
	}
}

/*
 * UserIsFriend
 */
function UserIsFriend($params)
{
    // parameters
	$user 			= getScalarValue($params, 0);
	$password 		= getScalarValue($params, 1);
    $user_checksum	= getScalarValue($params, 2);
    $friend_checksum = getScalarValue($params, 3);

	$model = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $model->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetUsersPermission($user, 'ManageUsers', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }
	
	// Get user by checksum
	$user = $model->GetUserByChecksum($user_checksum);
    if (Jaws_Error::isError($user)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $user->GetMessage());
    }

	if (isset($user['id'])) {
		// Get friend by checksum
		$friend = $model->GetUserByChecksum($friend_checksum);
		if (Jaws_Error::isError($friend)) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $friend->GetMessage());
		}

		if (isset($friend['id'])) {
			$res = $model->UserIsFriend($user['id'], $friend['id']);
			if (Jaws_Error::IsError($res)) {
				return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, $res->GetMessage());
			}
			
			$val = new XML_RPC_Value("$res", 'boolean');
			return new XML_RPC_Response($val);
		} else {
			$val = new XML_RPC_Value("false", 'boolean');
			return new XML_RPC_Response($val);
		}
	} else {
		$val = new XML_RPC_Value("false", 'boolean');
		return new XML_RPC_Response($val);
	}
}

/*
 * GetFriendsOfUsername
 */
function GetFriendsOfUsername($params)
{
    // parameters
	$user 		= getScalarValue($params, 0);
	$password 	= getScalarValue($params, 1);
    $username	= getScalarValue($params, 2);

	$model = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $model->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+3,  _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetUsersPermission($user, 'ManageUsers', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }
		
	$res = $model->GetFriendsOfUsername($username);
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
 * GetFriendsOfUser
 */
function GetFriendsOfUser($params)
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
    if (!GetUsersPermission($user, 'ManageUsers', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }
	
	// Get user by checksum
	$info = $model->GetUserByChecksum($checksum);
    if (Jaws_Error::isError($info)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $info->GetMessage());
    }
	
	if (isset($info['id'])) {
		$res = $model->GetFriendsOfUser($info['id']);
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
 * GetAllGroups
 */
function GetAllGroups($params)
{
    // parameters
	$user 		= getScalarValue($params, 0);
	$password 	= getScalarValue($params, 1);
    $orderBy	= getScalarValue($params, 2);

	if (empty($orderBy)) {
		$orderBy = 'name';
	}
	
	$model = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $model->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+3,  _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetUsersPermission($user, 'ManageUsers', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }
		
	$res = $model->GetAllGroups($orderBy);
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
 * GetGroupsOfUsername
 */
function GetGroupsOfUsername($params)
{
    // parameters
	$user 		= getScalarValue($params, 0);
	$password 	= getScalarValue($params, 1);
    $username	= getScalarValue($params, 2);

	$model = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $model->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+3,  _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetUsersPermission($user, 'ManageUsers', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }
		
	$res = $model->GetGroupsOfUsername($username);
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
 * GetGroupsOfUser
 */
function GetGroupsOfUser($params)
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
    if (!GetUsersPermission($user, 'ManageUsers', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }
	
	// Get user by checksum
	$info = $model->GetUserByChecksum($checksum);
    if (Jaws_Error::isError($info)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $info->GetMessage());
    }
	
	if (isset($info['id'])) {
		$res = $model->GetGroupsOfUser($info['id']);
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
 * MassAddGroup
 */
function MassAddGroup($params)
{
	$param = $params->getParam(0);
    $par = $param->me['array'];
    //var_dump($par);
	foreach ($par as $p) {
		//var_dump($p);
		$msg = new XML_RPC_Message('AddGroup', $p->me['array']);
		$res = AddGroup($msg);
		$val = $res->value();
		if (!is_object($val) || !is_a($val, 'XML_RPC_value')) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, var_export($val, true));
		}
	}
	return new XML_RPC_Response(new XML_RPC_Value("true", 'boolean'));
}

/*
 * AddGroup
 */
function AddGroup($params)
{
    // parameters
	$user 			= getScalarValue($params, 0);
	$password 		= getScalarValue($params, 1);
    $name			= getScalarValue($params, 2);
    $title  		= getScalarValue($params, 3);
    $description	= getScalarValue($params, 4);
    $removable  	= getScalarValue($params, 5);
	$removable 		= ((bool)$removable !== true ? false : true);
    $checksum 		= getScalarValue($params, 6);
    $founder 		= getScalarValue($params, 7);
	
	$model = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $model->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetUsersPermission($user, 'ManageUsers', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }
	
    $description = parseContent($description);
    
    $res = $model->AddGroup($name, $title, $description, $removable, $checksum, $founder);
    if ($res !== true) {
		return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, "There was a problem adding the group.");
	}
	
	$val = new XML_RPC_Value("$res", 'string');
    return new XML_RPC_Response($val);
}

/*
 * MassUpdateGroup
 */
function MassUpdateGroup($params)
{
	$param = $params->getParam(0);
    $par = $param->me['array'];
    //var_dump($par);
	foreach ($par as $p) {
		//var_dump($p);
		$msg = new XML_RPC_Message('UpdateGroup', $p->me['array']);
		$res = UpdateGroup($msg);
		$val = $res->value();
		if (!is_object($val) || !is_a($val, 'XML_RPC_value')) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, var_export($val, true));
		}
	}
	return new XML_RPC_Response(new XML_RPC_Value("true", 'boolean'));
}

/*
 * UpdateGroup
 */
function UpdateGroup($params)
{
    // parameters
	$user 			= getScalarValue($params, 0);
	$password 		= getScalarValue($params, 1);
    $checksum		= getScalarValue($params, 2);
    $name 			= getScalarValue($params, 3);
    $title 			= getScalarValue($params, 4);
    $description 	= getScalarValue($params, 5);
    $checksum 		= getScalarValue($params, 6);
	$checksum		= (empty($checksum) ? null : $checksum);
    $founder 		= getScalarValue($params, 7);
	$founder		= (empty($founder) ? null : (int)$founder);
    
	$model = new Jaws_User;
	if (Jaws_Error::IsError($userInfo = $model->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetUsersPermission($user, 'ManageUsers', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }
	
    $description = parseContent($description);
	
	// Get group by checksum
	$group = $model->GetGroupByChecksum($checksum);
    if (Jaws_Error::isError($group)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $group->GetMessage());
    }
	
	if (isset($group['id'])) {
		$post_id = $model->UpdateGroup($group['id'], $name, $title, $description, $checksum, $founder);
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
 * MassUpdateGroupByName
 */
function MassUpdateGroupByName($params)
{
	$param = $params->getParam(0);
    $par = $param->me['array'];
    //var_dump($par);
	foreach ($par as $p) {
		//var_dump($p);
		$msg = new XML_RPC_Message('UpdateGroupByName', $p->me['array']);
		$res = UpdateGroupByName($msg);
		$val = $res->value();
		if (!is_object($val) || !is_a($val, 'XML_RPC_value')) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, var_export($val, true));
		}
	}
	return new XML_RPC_Response(new XML_RPC_Value("true", 'boolean'));
}

/*
 * UpdateGroup
 */
function UpdateGroupByName($params)
{
    // parameters
	$user 			= getScalarValue($params, 0);
	$password 		= getScalarValue($params, 1);
    $name 			= getScalarValue($params, 2);
    $newname 		= getScalarValue($params, 3);
    $title 			= getScalarValue($params, 4);
    $description 	= getScalarValue($params, 5);
    $checksum 		= getScalarValue($params, 6);
	$checksum		= (empty($checksum) ? null : $checksum);
    $founder 		= getScalarValue($params, 7);
	$founder		= (empty($founder) ? null : (int)$founder);
	
	$model = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $model->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetUsersPermission($user, 'ManageUsers', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }
	
    $description = parseContent($description);
	
	// Get group by checksum
	$group = $model->GetGroupInfoByName($name);
    if (Jaws_Error::isError($group)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $group->GetMessage());
    }
	
	if (isset($group['id'])) {
		$post_id = $model->UpdateGroup($group['id'], $newname, $title, $description, $checksum, $founder);
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
 * MassDeleteGroup
 */
function MassDeleteGroup($params)
{
	$param = $params->getParam(0);
    $par = $param->me['array'];
    //var_dump($par);
	foreach ($par as $p) {
		//var_dump($p);
		$msg = new XML_RPC_Message('DeleteGroup', $p->me['array']);
		$res = DeleteGroup($msg);
		$val = $res->value();
		if (!is_object($val) || !is_a($val, 'XML_RPC_value')) {
			return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, var_export($val, true));
		}
	}
	return new XML_RPC_Response(new XML_RPC_Value("true", 'boolean'));
}

/*
 * DeleteGroup
 */
function DeleteGroup($params)
{
    // parameters
	$user 		= getScalarValue($params, 0);
	$password 	= getScalarValue($params, 1);
    $checksum	= getScalarValue($params, 2);
	
	$model = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $model->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetUsersPermission($user, 'ManageUsers', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }
	
	// Get group by checksum
	$group = $model->GetGroupByChecksum($checksum);
    if (Jaws_Error::isError($group)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $group->GetMessage());
    }
	
	if (isset($group['id'])) {
		$post_id = $model->DeleteGroup($group['id']);
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
 * ManualExportToHost
 */
function ManualExportToHost($params)
{
    // parameters
	$user 		= getScalarValue($params, 0);
	$password 	= getScalarValue($params, 1);
    $host		= getScalarValue($params, 2);
    $methods	= getScalarValue($params, 3);
    $root_owner	= getScalarValue($params, 4);
	$root_owner = ((bool)$root_owner !== true ? false : true);
	
	$model = new Jaws_User;
    if (Jaws_Error::IsError($userInfo = $model->Valid($user, $password))) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    $GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
    if (!GetUsersPermission($user, 'ManageUsers', $userInfo['user_type'])) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+1, _t('GLOBAL_ERROR_NO_PRIVILEGES'));
    }

	$uAdminHTML = $GLOBALS['app']->loadGadget('Users', 'AdminHTML');
    if (Jaws_Error::isError($uAdminHTML)) {
        return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, $uAdminHTML->GetMessage());
    }
	
    $res = $uAdminHTML->ManualExportToHost($host, $methods, $root_owner);
    if ($res !== true) {
		return new XML_RPC_Response(0, $GLOBALS['XML_RPC_erruser']+2, "There was a problem calling ManualExportToHost: ".$host);
	}
	
	$val = new XML_RPC_Value("true", 'boolean');
	return new XML_RPC_Response($val);
}

/*
 *  XML-RPC Server
 */

$rpc_methods = array(
    // Users
    'GetUsers' => array(
        'function' => 'GetUsers',
        'signature' => array(
			array('string', 'string', 'string', 'int', 'int', 'int', 'string', 'int', 'int'),
        ),
    ),
    'GetUserInfoById' => array(
        'function' => 'GetUserInfoById',
        'signature' => array(
            array(
				'string', 'string', 'string', 'int', 'boolean', 'boolean', 'boolean', 'boolean'
			),
        ),
    ),
    'GetUserByChecksum' => array(
        'function' => 'GetUserByChecksum',
        'signature' => array(
            array(
				'string', 'string', 'string', 'string'
			),
        ),
    ),
    'GetUserInfoByName' => array(
        'function' => 'GetUserInfoByName',
        'signature' => array(
            array(
				'string', 'string', 'string', 'string', 'boolean', 'boolean', 'boolean', 'boolean'
			),
        ),
    ),
    'GetUserInfoByIdentifier' => array(
        'function' => 'GetUserInfoByIdentifier',
        'signature' => array(
            array(
				'string', 'string', 'string', 'string', 'boolean', 'boolean', 'boolean', 'boolean'
			),
        ),
    ),
    'GetUsersOfGroup' => array(
        'function' => 'GetUsersOfGroup',
        'signature' => array(
            array(
				'string', 'string', 'string', 'string'
			),
        ),
    ),
    'MassAddUser' => array(
        'function' => 'MassAddUser',
        'signature' => array(
            array(
				'string', 'array'
			),
        ),
    ),
	'AddUser' => array(
        'function' => 'AddUser',
        'signature' => array(
            array(
				'string', 'string', 'string', 'string', 'string', 'string', 'string', 'int', 'boolean', 
				'string', 'string', 'string', 'string', 'string', 'string', 'string', 'boolean', 'string'
			),
        ),
    ),
    'MassUpdateUser' => array(
        'function' => 'MassUpdateUser',
        'signature' => array(
            array(
				'string', 'array'
			),
        ),
    ),
    'UpdateUser' => array(
        'function' => 'UpdateUser',
        'signature' => array(
			array(
				'string', 'string', 'string', 'string', 'string', 'string', 'string', 'string', 'int', 'int'
			),
        ),
    ),
    'MassUpdatePersonalInfo' => array(
        'function' => 'MassUpdatePersonalInfo',
        'signature' => array(
            array(
				'string', 'array'
			),
        ),
    ),
    'UpdatePersonalInfo' => array(
        'function' => 'UpdatePersonalInfo',
        'signature' => array(
			array(
				'string', 'string', 'string', 'string', 'string', 'string', 'int', 'string', 
				'string', 'string', 'string', 'string', 'string', 'string', 'string', 'string', 
				'string', 'string', 'string', 'string', 'string', 'string', 'string', 'string', 
				'string'
			),
        ),
    ),
    'MassUpdateAdvancedOptions' => array(
        'function' => 'MassUpdateAdvancedOptions',
        'signature' => array(
            array(
				'string', 'array'
			),
        ),
    ),
	'UpdateAdvancedOptions' => array(
        'function' => 'UpdateAdvancedOptions',
        'signature' => array(
			array(
				'string', 'string', 'string', 'string', 'string', 'string', 'string', 'string', 'string', 'boolean', 'string'
			),
        ),
    ),
    'DeleteUser' => array(
        'function' => 'DeleteUser',
        'signature' => array(
            array(
				'string', 'string', 'string', 'string'
			),
        ),
    ),
    'MassAddUserToGroup' => array(
        'function' => 'MassAddUserToGroup',
        'signature' => array(
            array(
				'string', 'array'
			),
        ),
    ),
    'AddUserToGroup' => array(
        'function' => 'AddUserToGroup',
        'signature' => array(
            array(
				'string', 'string', 'string', 'string', 'string', 'string'
			),
        ),
    ),
    'AddUserToGroupName' => array(
        'function' => 'AddUserToGroupName',
        'signature' => array(
            array(
				'string', 'string', 'string', 'string', 'string', 'string'
			),
        ),
    ),
    'MassDeleteUserFromGroup' => array(
        'function' => 'MassDeleteUserFromGroup',
        'signature' => array(
            array(
				'string', 'array'
			),
        ),
    ),
    'DeleteUserFromGroup' => array(
        'function' => 'DeleteUserFromGroup',
        'signature' => array(
            array(
				'string', 'string', 'string', 'string', 'string'
			),
        ),
    ),
    'MassDeleteUserFromAllGroups' => array(
        'function' => 'MassDeleteUserFromAllGroups',
        'signature' => array(
            array(
				'string', 'array'
			),
        ),
    ),
    'DeleteUserFromAllGroups' => array(
        'function' => 'DeleteUserFromAllGroups',
        'signature' => array(
            array(
				'string', 'string', 'string', 'string', 'string'
			),
        ),
    ),
    'UserIsInGroup' => array(
        'function' => 'UserIsInGroup',
        'signature' => array(
            array(
				'string', 'string', 'string', 'string', 'string'
			),
        ),
    ),
    'MassAddUserToFriend' => array(
        'function' => 'MassAddUserToFriend',
        'signature' => array(
            array(
				'string', 'array'
			),
        ),
    ),
    'AddUserToFriend' => array(
        'function' => 'AddUserToFriend',
        'signature' => array(
            array(
				'string', 'string', 'string', 'string', 'string', 'string'
			),
        ),
    ),
    'DeleteUserFromFriend' => array(
        'function' => 'DeleteUserFromFriend',
        'signature' => array(
            array(
				'string', 'string', 'string', 'string', 'string'
			),
        ),
    ),
    'UserIsFriend' => array(
        'function' => 'UserIsFriend',
        'signature' => array(
            array(
				'string', 'string', 'string', 'string', 'string'
			),
        ),
    ),
    'GetFriendsOfUsername' => array(
        'function' => 'GetFriendsOfUsername',
        'signature' => array(
            array(
				'string', 'string', 'string', 'string'
			),
        ),
    ),
    'GetFriendsOfUser' => array(
        'function' => 'GetFriendsOfUser',
        'signature' => array(
            array(
				'string', 'string', 'string', 'string'
			),
        ),
    ),
    // Groups
    'GetAllGroups' => array(
        'function' => 'GetAllGroups',
        'signature' => array(
            array(
				'string', 'string', 'string', 'string'
			),
        ),
    ),
    'GetGroupsOfUsername' => array(
        'function' => 'GetGroupsOfUsername',
        'signature' => array(
            array(
				'string', 'string', 'string', 'string'
			),
        ),
    ),
    'GetGroupsOfUser' => array(
        'function' => 'GetGroupsOfUser',
        'signature' => array(
            array(
				'string', 'string', 'string', 'string'
			),
        ),
    ),
    'MassAddGroup' => array(
        'function' => 'MassAddGroup',
        'signature' => array(
            array(
				'string', 'array'
			),
        ),
    ),
    'AddGroup' => array(
        'function' => 'AddGroup',
        'signature' => array(
            array(
				'string', 'string', 'string', 'string', 'string', 'string', 'boolean', 'string', 'int'
			),
        ),
    ),
    'MassUpdateGroup' => array(
        'function' => 'MassUpdateGroup',
        'signature' => array(
            array(
				'string', 'array'
			),
        ),
    ),
    'UpdateGroup' => array(
        'function' => 'UpdateGroup',
        'signature' => array(
			array(
				'string', 'string', 'string', 'string', 'string', 'string', 'string', 'string', 'string'
			),
        ),
    ),
    'MassUpdateGroupByName' => array(
        'function' => 'MassUpdateGroupByName',
        'signature' => array(
            array(
				'string', 'array'
			),
        ),
    ),
    'UpdateGroupByName' => array(
        'function' => 'UpdateGroupByName',
        'signature' => array(
			array(
				'string', 'string', 'string', 'string', 'string', 'string', 'string', 'string', 'string'
			),
        ),
    ),
    'MassDeleteGroup' => array(
        'function' => 'MassDeleteGroup',
        'signature' => array(
            array(
				'string', 'array'
			),
        ),
    ),
    'DeleteGroup' => array(
        'function' => 'DeleteGroup',
        'signature' => array(
            array(
				'string', 'string', 'string', 'string'
			),
        ),
    ),
    'ManualExportToHost' => array(
        'function' => 'ManualExportToHost',
        'signature' => array(
            array(
				'string', 'string', 'string', 'string', 'string', 'boolean'
			),
        ),
    ),
);
