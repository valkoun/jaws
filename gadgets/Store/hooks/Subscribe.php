<?php
/**
 * Store - Subscribe gadget hook
 *
 * @category   GadgetHook
 * @package    Store
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2012 Alan Valkoun
 */
class StoreSubscribeHook
{			
    /**
     * Returns an array with all subscribables of current URL
     *
     * @access  public
     */
    function GetSubscribeButtons($request_gadget = null, $request_action = null, $request_id = null)
    {
		require_once JAWS_PATH . 'include/Jaws/User.php';
		$jUser = new Jaws_User;
		$request =& Jaws_Request::getInstance();
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$viewer_id = (int)$GLOBALS['app']->Session->GetAttribute('user_id');
		
		$post = $request->get(array('Store_group', 'Store_ownerid'), 'post');
		$get = $request->get(array('Store_group', 'Store_ownerid'), 'get');
		foreach ($post as $k => $v) {
			if (empty($v) && !empty($get[$k])) {
				$post[$k] = $get[$k];
			}
		}
		if ($request_gadget == 'Store') {
			// Groups
			if (!empty($post['Store_group'])) {
				$groupInfo = $jUser->GetGroupInfoById((int)$post['Store_group']);
			// Users
			} else if (!empty($post['Store_ownerid'])) {
				$userInfo = $jUser->GetUserInfoById((int)$post['Store_ownerid']);
			}
			
			$subscriptions = array();
			$subscriptions['subscriptions'] = array();
			if (isset($groupInfo) && !Jaws_Error::IsError($groupInfo) && isset($groupInfo['id']) && !empty($groupInfo['id'])) {
				$is_friend = $jUser->UserIsGroupFriend($viewer_id, $groupInfo['id']);
				if ($is_friend === false && !in_array($jUser->GetStatusOfUserInFriend($viewer_id, $groupInfo['id']), array('group_blocked', 'group_denied'))) {
					$followButton =& Piwi::CreateWidget('Button', 'followButton', _t('USERS_USERS_REQUEST_FRIEND_GROUP'), STOCK_ADD);
					$followButton->SetStyle('min-width: 50px;');
					$followButton->AddEvent(ON_CLICK, "javascript: location.href = 'index.php?gadget=Users&action=RequestFriendGroup&group=".$groupInfo['id']."&redirect_to=".urlencode($GLOBALS['app']->GetFullURL())."';");
					$follow = $followButton->Get();
				} else {
					$follow = "<button disabled=\"disabled\"  style=\"color: #999999; background: #EEEEEE; font-size: 12px;\" class=\"merchant-unfollow-button\">Request Sent</button>";
				}
				$friends = $jUser->GetUsersOfFriendByStatus($groupInfo['id'], 'group_active');
				$friend_count = 0;
				if ($friends !== false && !count($friends) <= 0) {
					$friend_count = count($friends);
				}
				$subscribed = '<span id="subscribed-count-group-'.$groupInfo['id'].'" class="subscribed-count-group">'.$friend_count.'<span class="subscribed-count-text subscribed-count-group-text">&nbsp;followers.</span></span>';
				$subscriptions['subscriptions'][] = array(
					'button' => $follow.$subscribed, 
					'class' => 'subscribe-group', 
					'id' => 'subscribe-group-'.$groupInfo['id'] 
				);
				$is_member = $jUser->UserIsInGroup($viewer_id, $groupInfo['id']);
				if ($is_member === false && !in_array($jUser->GetStatusOfUserInGroup($viewer_id, $groupInfo['id']), array('denied','blocked'))) {
					$joinButton =& Piwi::CreateWidget('Button', 'joinButton', _t('USERS_USERS_REQUEST_GROUP_ACCESS'));
					$joinButton->SetStyle('min-width: 50px;');
					$joinButton->AddEvent(ON_CLICK, "javascript: location.href = 'index.php?gadget=Users&action=RequestGroupAccess&group=".$groupInfo['name']."&redirect_to=".urlencode($GLOBALS['app']->GetFullURL())."';");
					$join = $joinButton->Get();
				} else {
					$join = "<button disabled=\"disabled\"  style=\"color: #999999; background: #EEEEEE; font-size: 12px;\" class=\"merchant-unfollow-button\">Request Sent</button>";
				}
				$member_count = 0;
				$members = $jUser->GetUsersOfGroupByStatus($groupInfo['id'], 'active');
				if ($members !== false && !count($members) <= 0) {
					$member_count = count($members);
				}
				$admins = $jUser->GetUsersOfGroupByStatus($groupInfo['id'], 'admin');
				if ($admins !== false && !count($admins) <= 0) {
					$member_count = ($member_count+count($admins));
				}
				$founders = $jUser->GetUsersOfGroupByStatus($groupInfo['id'], 'founder');
				if ($founders !== false && !count($founders) <= 0) {
					$member_count = ($member_count+count($founders));
				}
				$joined = '<span id="joined-count-group-'.$groupInfo['id'].'" class="joined-count-group">'.$member_count.'<span class="joined-count-text joined-count-group-text">&nbsp;users in&nbsp;'.$xss->filter(strip_tags($groupInfo['title'])).'.</span></span>';
				$subscriptions['subscriptions'][] = array(
					'button' => $join.$joined, 
					'class' => 'join-group',
					'id' => 'join-group-'.$groupInfo['id'] 
				);
				return $subscriptions;
			} else if (isset($userInfo) && !Jaws_Error::IsError($userInfo) && isset($userInfo['id']) && !empty($userInfo['id'])) {
				$is_friend = $jUser->UserIsFriend($viewer_id, $userInfo['id']);
				if ($is_friend === false && !in_array($jUser->GetStatusOfUserInFriend($viewer_id, $userInfo['id']), array('blocked', 'denied'))) {
					$followButton =& Piwi::CreateWidget('Button', 'followButton', _t('USERS_USERS_REQUEST_FRIEND_GROUP'), STOCK_ADD);
					$followButton->SetStyle('min-width: 50px;');
					$followButton->AddEvent(ON_CLICK, "javascript: location.href = 'index.php?gadget=Users&action=RequestFriend&friend_id=".$userInfo['id']."&redirect_to=".urlencode($GLOBALS['app']->GetFullURL())."';");
					$follow = $followButton->Get();
				} else {
					$follow = "<button disabled=\"disabled\"  style=\"color: #999999; background: #EEEEEE; font-size: 12px;\" class=\"merchant-unfollow-button\">Request Sent</button>";
				}
				$friends = $jUser->GetUsersOfFriendByStatus($userInfo['id'], 'active');
				$friend_count = 0;
				if ($friends !== false && !count($friends) <= 0) {
					$friend_count = count($friends);
				}
				$subscribed = '<span id="subscribed-count-friend-'.$userInfo['id'].'" class="subscribed-count-friend">'.$friend_count.'<span class="subscribed-count-text subscribed-count-friend-text">&nbsp;followers.</span></span>';
				$subscriptions['subscriptions'][] = array(
					'button' => $follow.$subscribed, 
					'class' => 'subscribe-friend',
					'id' => 'subscribe-friend-'.$groupInfo['id']
				);
				return $subscriptions;
			}
		}
		return false;
	}
}
