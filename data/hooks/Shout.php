<?php
/**
 * Custom Listener gadget hook
 *
 * To create custom Event Listeners for Event Shouters,
 * create a function below with the same name as the Shouter 
 * to Listen to. 
 *
 * All Listeners receive one parameter, which can be anything: array,
 * string, boolean, integer, etc. 
 * Two return values are possible:
 * 1) return array('return' => $mixed) 
 * to pass $mixed to the script that Shouted, or:  
 * 2) return true on success or false (or Jaws_Error) on error
 *
 * @category   GadgetHook
 * @package    Core
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2015 Alan Valkoun
 */
class ShoutHook
{	
    /**
     * onAddUser listener
     *
     * @access  public
     */
    function onAddUser($params)
    {
		/*
        $id = $params;
		require_once JAWS_PATH . 'include/Jaws/User.php';
		$userModel = new Jaws_User();
		$info = $userModel->GetUserInfoById($id, true, true, true, true);
		if (Jaws_Error::IsError($info) || !isset($info['id'])) {
			return false;
		} else {
			// Do something with newly added user
		}
		*/		
		return true;
	}
	
    /**
     * onUpdateUser listener
     *
     * @access  public
     */
    function onUpdateUser($params)
    {
		/*
        $id = $params;
		require_once JAWS_PATH . 'include/Jaws/User.php';
		$userModel = new Jaws_User();
		$info = $userModel->GetUserInfoById($id, true, true, true, true);
		if (Jaws_Error::IsError($info) || !isset($info['id'])) {
			return false;
		} else {
			// Do something with updated user
		}
		*/
				
		return true;
	}
    
	/**
     * onDeleteUser listener
     *
     * @access  public
     */
    function onDeleteUser($params)
    {
		/*
        $id = $params;
		require_once JAWS_PATH . 'include/Jaws/User.php';
		$userModel = new Jaws_User();
		$info = $userModel->GetUserInfoById($id, true, true, true, true);
		if (Jaws_Error::IsError($info) || !isset($info['id'])) {
			return false;
		} else {
			// Do something with the user that is _about to be_ deleted.
		}
		*/	
		return true;
	}

    /**
     * onAddUserToGroup listener
     *
     * @access  public
     */
    function onAddUserToGroup($params)
    {
        /*
		$user_id = $params['user_id'];
        $group_id = $params['group_id'];
        $status = $params['status'];
		require_once JAWS_PATH . 'include/Jaws/User.php';
		$userModel = new Jaws_User();
		$info = $userModel->GetUserInfoById($user_id, true, true, true, true);
		if (Jaws_Error::IsError($info) || !isset($info['id'])) {
			return false;
		}
		$groupInfo = $userModel->GetGroupInfoByID($group_id);
		if (Jaws_Error::IsError($groupInfo) || !isset($groupInfo['id'])) {
			return false;
		}
		*/
		return true;
	}
	
    /**
     * onAddGroup listener
     *
     * @access  public
     */
    function onAddGroup($params)
    {
		/*
		$id = $params;
		// Get Group info
		require_once JAWS_PATH . 'include/Jaws/User.php';
        $jUser = new Jaws_User;
		$group = $jUser->GetGroupInfoById($id);
		if (!Jaws_Error::IsError($group)) {
			// Do something with the newly added group
		}
		*/
		return true;
    }
	
    /**
     * onDeleteGroup listener
     *
     * @access  public
     */
    function onDeleteGroup($params)
    {
		/*
		$id = $params;
		// Get Group info
		require_once JAWS_PATH . 'include/Jaws/User.php';
        $jUser = new Jaws_User;
		$info = $jUser->GetGroupInfoById($id);
		if (!Jaws_Error::IsError($info)) {
			// Do something with the group _about to be_ deleted
		}
		*/
 		return true;
    }
	
    /**
     * onAddUserToFriend listener
     *
     * @access  public
     */
    function onAddUserToFriend($params)
    {
		/*
        $user_id = $params['user_id'];
        $friend_id = $params['friend_id'];
        $status = $params['status'];
		require_once JAWS_PATH . 'include/Jaws/User.php';
		$userModel = new Jaws_User();
		$info = $userModel->GetUserInfoById($user_id, true, true, true, true);
		if (Jaws_Error::IsError($info) || !isset($info['id'])) {
			$GLOBALS['app']->Session->PushSimpleResponse("onAddUserToFriend: Could not GetUserInfoById of user: ".$user_id);
			return false;
		}
		$friendInfo = $userModel->GetUserInfoById($friend_id, true, true, true, true);
		if (Jaws_Error::IsError($friendInfo) || !isset($friendInfo['id'])) {
			$GLOBALS['app']->Session->PushSimpleResponse("onAddUserToFriend: Could not GetUserInfoById of friend: ".$friend_id);
			return false;
		}
		*/						
		return true;
	}
    
	/**
     * onFriendGroup listener
     *
     * @access  public
     */
    function onFriendGroup($params)
    {
		/*
        $user_id = $params['user_id'];
        $group_id = $params['group_id'];
		require_once JAWS_PATH . 'include/Jaws/User.php';
		$userModel = new Jaws_User();
		$info = $userModel->GetUserInfoById($user_id, true, true, true, true);
		if (Jaws_Error::IsError($info) || !isset($info['id'])) {
			$GLOBALS['app']->Session->PushSimpleResponse("Shout.php onFriendGroup: Could not GetUserInfoById of user: ".$user_id);
			return false;
		}
		$groupInfo = $userModel->GetGroupInfoByID($group_id);
		if (Jaws_Error::IsError($groupInfo) || !isset($groupInfo['id'])) {
			$GLOBALS['app']->Session->PushSimpleResponse("Shout.php onFriendGroup: Could not GetGroupInfoByID of group: ".$group_id);
			return false;
		}
		*/
		return true;
	}
	
    /**
     * onDeleteUserFromFriend listener
     *
     * @access  public
     */
    function onDeleteUserFromFriend($params)
    {
        /*
		$user_id = $params['user_id'];
        $friend_id = $params['friend_id'];

		require_once JAWS_PATH . 'include/Jaws/User.php';
		$userModel = new Jaws_User();
		$info = $userModel->GetUserInfoById($user_id, true, true, true, true);
		if (Jaws_Error::IsError($info) || !isset($info['id'])) {
			return false;
		}
		$friendInfo = $userModel->GetUserInfoById($friend_id, true, true, true, true);
		if (Jaws_Error::IsError($friendInfo) || !isset($friendInfo['id'])) {
			return false;
		}
		*/
		return true;
	}
		
	/**
     * Ecommerce gadget: onBeforeAddToCart listener
     *
     * @access  public
     */
    function onBeforeAddToCart($params)
    {
		$result = array();
		if (!$GLOBALS['app']->Session->Logged()) {
			$GLOBALS['app']->Session->PushSimpleResponse("You must log-in to continue. If you don't have an account, you can <a href=\"index.php?gadget=Users&action=Register&redirect_to=".$redirect_to."\">Create one</a>");
			$result['message'] = 'false';
			$result['url'] = $GLOBALS['app']->GetSiteURL() . '/index.php?gadget=Users&action=DefaultAction&redirect_to='.urlencode($full_url);
		}
		if (isset($result['message'])) {
			return array('return' => $result);		
		}
		return true;
	}
	
	/**
     * Ecommerce: onBeforeCheckout listener
     *
     * @access  public
     */
    function onBeforeCheckout($params)
    {
		$items = $params['items']; 
		$paymentmethod = $params['paymentmethod']; 
		$customer_shipfirstname = $params['customer_shipfirstname']; 
		$customer_shiplastname = $params['customer_shiplastname']; 
		$customer_shipaddress = $params['customer_shipaddress']; 
		$customer_shipcity = $params['customer_shipcity']; 
		$customer_shipregion = $params['customer_shipregion'];  
		$customer_shippostal = $params['customer_shippostal'];  
		$customer_shipcountry = $params['customer_shipcountry'];  
		$shipfreight = $params['shipfreight'];  
		$customer_shipaddress2 = $params['customer_shipaddress2'];  
		$customer_firstname = $params['customer_firstname'];  
		$customer_middlename = $params['customer_middlename'];  
		$customer_lastname = $params['customer_lastname'];  
		$customer_suffix = $params['customer_suffix'];  
		$customer_address = $params['customer_address'];  
		$customer_address2 = $params['customer_address2'];  
		$customer_city = $params['customer_city'];  
		$customer_region = $params['customer_region']; 
		$customer_postal = $params['customer_postal'];  
		$customer_country = $params['customer_country']; 
		$cc_creditcardtype = $params['cc_creditcardtype']; 
		$cc_acct = $params['cc_acct'];  
		$cc_expdate_month = $params['cc_expdate_month'];  
		$cc_expdate_year = $params['cc_expdate_year']; 
		$cc_cvv2 = $params['cc_cvv2'];  
		$usecase = $params['usecase']; 
		$total_weight = $params['total_weight']; 
		$redirect_to = (!empty($params['redirect_to']) ? $params['redirect_to'] : urlencode($full_url));
		
		$full_url = $GLOBALS['app']->GetFullURL();
        $uid = $GLOBALS['app']->Session->GetAttribute('user_id');
		
		require_once JAWS_PATH . 'include/Jaws/User.php';
		$jUser = new Jaws_User();
		$userInfo = $jUser->GetUserInfoById($uid, true, true, true, true);
		
		$result = array();
		
		// Logged in?
		if (Jaws_Error::IsError($userInfo) || !isset($userInfo['id'])) {
			$GLOBALS['app']->Session->PushSimpleResponse("You must log-in to continue. If you don't have an account, you can <a href=\"index.php?gadget=Users&action=Register&redirect_to=".$redirect_to."\">Create one</a>");
			$result['message'] = 'true';
			$result['url'] = $GLOBALS['app']->GetSiteURL() . '/index.php?gadget=Users&action=DefaultAction&redirect_to='.$redirect_to;
		} else {
			if (
				$total_weight > 0 && ((empty($customer_shiplastname) && 
				empty($customer_shipfirstname)) || (empty($customer_shipaddress) ||    
				empty($customer_shipcity) || empty($customer_shipregion) || 
				empty($customer_shippostal) || empty($customer_shipcountry)) || 
				(!empty($shipfreight) && $shipfreight != '0'))
			) {
				$result['message'] = 'javascript';
				$result['body'] = 'showShipping';
			}
		}
		if (isset($result['message'])) {
			return array('return' => $result);		
		}
		
		return true;
    }

	/**
     * onLoadAccountNews listener
     *
     * @access  public
     */
    function onLoadAccountNews($params)
    {
		/*
		$gadget_reference = $params['id'];
		$header = $params['header'];
		$interactive = $params['interactive'];
		$replies_shown = $params['replies_shown']; 
		$layout = (isset($params['layout']) && !empty($params['layout']) ? strtolower($params['layout']) : 'full');
		if (!in_array($layout, array('full', 'tiles', 'boxy'))) {
			$layout = 'full';
		}
		$only_comments = $params['only_comments'];
		$method = $params['method'];
		$limit = (int)$params['limit'];
		$result_max = (int)$params['max'];
		$uid = $params['user_id'];
		$items = $params['items'];
		$gadget = $params['gadget'];
		$public = $params['public'];
		
		$viewer_id = (int)$GLOBALS['app']->Session->GetAttribute('user_id');
		
		$site_name = $GLOBALS['app']->Registry->Get('/config/site_name');
		$site_url = $GLOBALS['app']->GetSiteURL();
		if (empty($site_name)) {
			$site_name = str_replace(array('http://', 'https://'), '', $site_url);
		}
		$site_email = $GLOBALS['app']->Registry->Get('/network/site_email');
		$site_image = '';
		if (file_exists(JAWS_DATA . 'files/css/logo.png')) {
			$site_image = $GLOBALS['app']->GetDataURL('files/css/logo.png', true);
		}
	
		$result = array();
		$result['comments'] = array();
		
		// Load hook comments
		require_once JAWS_PATH . 'include/Jaws/User.php';
		$jUser = new Jaws_User;
		$model = $GLOBALS['app']->LoadGadget('Users', 'Model');
		// Private Users newsfeed
		if ($gadget == 'Users' && $public === false) {
			// User requests
			$user_requests = $model->GetUsersRequests($params);
			if (!Jaws_Error::IsError($user_requests)) {
				$result['comments'] = array_merge($result['comments'], $user_requests);
			}
			// Connect to Facebook
			$social_exists = false;
			$socialModel = $GLOBALS['app']->LoadGadget('Social', 'Model');
			// Get social
			$social_websites = $socialModel->GetSocialOfUserID(0, 'facebook');
			if (
				!Jaws_Error::IsError($social_websites) && 
				isset($social_websites[0]['social_id']) && !empty($social_websites[0]['social_id']) && 
				isset($social_websites[0]['social_id2']) && !empty($social_websites[0]['social_id2'])
			) {
				$social_tokens = $socialModel->GetAccessTokensOfUserID($uid, $social_websites[0]['id']);
				if (!Jaws_Error::IsError($social_tokens) && isset($social_tokens['id']) && !empty($social_tokens['id'])) {
					$social_exists = true;
				}
				// Only show the message if the user has not already connected with Facebook
				if ($social_exists === false && !in_array('_'.$gadget.'custom1', $GLOBALS['app']->_ItemsOnLayout)) {
					// Facebook Connect
					$msg = '<table width="100%" border="0" cellpadding="0" cellspacing="0"><tbody><tr><td width="100%" valign="top"><p style="padding-top: 6px; padding-right: 6px; padding-bottom: 6px;">';
					$msg .= '<a href="https://www.facebook.com/connect/uiserver.php?app_id='.$social_websites[0]['social_id'].'&method=permissions.request&redirect_uri='.urlencode($GLOBALS['app']->GetSiteURL().'/index.php?gadget=Social&action=FacebookResponse').'&response_type=code&display=page&auth_referral=1"><img align="left" border="0" src="'.$GLOBALS['app']->GetJawsURL().'/gadgets/Social/images/normal/facebook.png" style="padding-right: 5px; padding-bottom: 5px;" /></a>';
					$msg .= '<strong>Connect Facebook</strong> Connect your '.$site_name.' profile with Facebook to post your updates automatically, and reach all of your fans/friends.<br />';
					$msg .= '<a href="https://www.facebook.com/connect/uiserver.php?app_id='.$social_websites[0]['social_id'].'&method=permissions.request&redirect_uri='.urlencode($GLOBALS['app']->GetSiteURL().'/index.php?gadget=Social&action=FacebookResponse').'&response_type=code&display=page&auth_referral=1">Connect Facebook</a>';
					$msg .= '</p></td><td width="0" valign="middle"><button onclick="location.href=\'https://www.facebook.com/connect/uiserver.php?app_id='.$social_websites[0]['social_id'].'&method=permissions.request&redirect_uri='.urlencode($GLOBALS['app']->GetSiteURL().'/index.php?gadget=Social&action=FacebookResponse').'&response_type=code&display=page&auth_referral=1\';">';
					$msg .= 'Connect</button></a></td></tr></tbody></table>';
					
					$item = array(
						'id' => 'custom1',
						'name' => '',
						'email' => $site_email,
						'url' => '',
						'title' => '',
						'msg_txt' => $msg,
						'image' => $site_image,
						'type' => 'custom',
						'sharing' => 'everyone',
						'status' => 'approved',
						'gadget' => 'Users',
						'gadget_reference' => $uid,
						'parent' => 0,
						'replies' => false,
						'ownerid' => 0,
						'createtime' => ''
					);
					$result['comments'][] = $item;
					$GLOBALS['app']->_ItemsOnLayout[] = '_'.$gadget.$item['id'];	
				} 
			}
		// Public newsfeed
		} else if ($gadget == 'Users' && $public === true) {
			// Show a custom message in the public newsfeed.
		}
		return array('return' => $result);	
		*/
		return true;
	}
			
	/**
     * onBeforeLoadMenus listener
     *
     * @access  public
     */
    function onBeforeLoadMenus($params)
    {
		/*
		$menu_group = $params['gid'];
		$menu_parent = $params['pid'];
		
		// What is the main gadget requested from the controller? (i.e. index.php?gadget=MainRequestGadget)
		$request_gadget = $GLOBALS['app']->_MainRequestGadget;
		// What is the main action requested from the controller? (i.e. index.php?action=MainRequestAction)
		$request_action = $GLOBALS['app']->_MainRequestAction;
		// What is the main ID requested from the controller? (i.e. index.php?id=MainRequestId)
		$request_id = $GLOBALS['app']->_MainRequestId;
		
		$model = $GLOBALS['app']->LoadGadget('Menu', 'Model');
		// Group ID = 1?
		if ($menu_group == 1 && $menu_parent == 0) {
			$result = array();
			$result['menus'] = array();
			// Look through all existing menu items in this group
			$levels = $model->GetLevelsMenus(0, $menu_group, true);
			if (!Jaws_Error::IsError($levels)) {
				foreach ($levels as $level) {
					if ($level['id'] == 1) {
						$level['title'] = '<img src="'.$GLOBALS['app']->GetDataURL().'/files/css/logo.png" border="0" title="'.$level['title'].'" alt="'.$level['title'].'" />';
					}
					$result['menus'][] = $level;
				}
			}
			return array('return' => $result);	
		}
		// Group ID = 2?
		if ($menu_group == 2 && $menu_parent == 0) {
			$result = array();
			$result['menus'] = array();
			// Look through all existing menu items in this group
			$levels = $model->GetLevelsMenus(0, $menu_group, true);
			if (!Jaws_Error::IsError($levels)) {
				foreach ($levels as $level) {
					if ($level['id'] == 12) {
						$level['title'] = '<img src="'.$GLOBALS['app']->GetDataURL().'/files/css/footer_logo.png" border="0" title="'.$level['title'].'" alt="'.$level['title'].'" />';
					}
					$result['menus'][] = $level;
				}
			}
			return array('return' => $result);	
		}
		*/
		return true;
	}
			
	/**
     * onBeforeLoadPage listener
     *
     * @access  public
     */
    function onBeforeLoadPage($params)
    {
		// Before the page content is generated, for example replace template variables:
		//$result = array('return' => array('vars' => array('GROUP_NAME' => "Your Community")));		
		return true;
	}
	
	/**
     * onBeforeLoadHomepage listener
     *
     * @access  public
     */
    function onBeforeLoadHomepage($params)
    {
		// Before the homepage content is generated, for example replace template variables:
		//$result = array('return' => array('vars' => array('GROUP_NAME' => "Your Community")));		
		return true;
	}
	
	/**
     * onAfterLoadHomepage listener
     *
     * @access  public
     */
    function onAfterLoadHomepage($params)
    {
		// After the homepage content is generated
		return true;
	}
	
}
