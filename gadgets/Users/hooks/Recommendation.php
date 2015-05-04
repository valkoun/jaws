<?php
/**
 * Users - Recommendation gadget hook
 *
 * @category   GadgetHook
 * @package    Users
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2012 Alan Valkoun
 */
class UsersRecommendationHook
{			
    /**
     * Returns an array with all recommendations for user
     *
     * @access  public
     */ 
    function GetRecommendations($params = array())
    {
		$OwnerID = $params['uid'];
		$viewer_id = (int)$GLOBALS['app']->Session->GetAttribute('user_id');
		$id = $params['gadget_reference'];
		$gadget = $params['gadget'];
		$public = $params['public'];
		$result_limit = (int)$params['limit'];
		$result_max = (int)$params['max'];
		$extra_params = $params['params'];
		
        $result_counter = 0;
        $result_offset = 0;
        $result = array();
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		require_once JAWS_PATH . 'include/Jaws/Image.php';
		$usersModel = $GLOBALS['app']->loadGadget('Users', 'Model');
		$jUser = new Jaws_User;
		
		if (!is_null($OwnerID)) {			
			if ($OwnerID == $viewer_id) {
				// Get requested user
				$userInfo = $jUser->GetUserInfoById((int)$viewer_id, true, true, true, true);
				if (!Jaws_Error::IsError($userInfo) && isset($userInfo['id']) && !empty($userInfo['id'])) {
					// Group recommendations
					$group_rec = $this->GetGroupRecommendations(
						array(
							'uid' => $OwnerID, 
							'gadget_reference' => $id, 
							'gadget' => $gadget, 
							'public' => $public,
							'limit' => $result_limit,
							'max' => $result_max
						)
					);
					foreach ($group_rec as $group) {
						$result[] = $group;
					}
					
					// Friends recommendations
					$friend_rec = $this->GetFriendRecommendations(
						array(
							'uid' => $OwnerID, 
							'gadget_reference' => $id, 
							'gadget' => $gadget, 
							'public' => $public,
							'limit' => $result_limit,
							'max' => $result_max
						)
					);
					foreach ($friend_rec as $friend) {
						$result[] = $group;
					}
				}	
			}
		}
		
		return $result;
    }
	
    /**
     * Returns an array of single comment thread
     *
     * @access  public
     */
    function GetGroupRecommendations($params = array())
    {
		$OwnerID = $params['uid'];
		$viewer_id = (int)$GLOBALS['app']->Session->GetAttribute('user_id');
		$id = $params['gadget_reference'];
		if (!is_null($id)) {
			$OwnerID = $id;
		}
		$gadget = $params['gadget'];
		$public = $params['public'];
		$result_limit = (int)$params['limit'];
		$result_max = (int)$params['max'];
		$extra_params = $params['params'];
		
		$result_counter = 0;
		
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		require_once JAWS_PATH . 'include/Jaws/Image.php';
		$usersModel = $GLOBALS['app']->loadGadget('Users', 'Model');
		$jUser = new Jaws_User;
				
		// Engagement Groups matching sharing demographics of viewer (groups, 
		// users, location, interest) within namespace of this request (gadget, action, ref)
							
		/*
		// Follow Friends' Groups
		$friends = $jUser->GetFriendsOfUser($viewer_id, 'created', 'DESC', $result_limit);
		foreach ($friends as $friend) {
			$friendInfo = $jUser->GetUserInfoById((int)$friend['friend_id'], true, true, true, true);
			if (!Jaws_Error::IsError($friendInfo) && isset($friendInfo['id']) && !empty($friendInfo['id'])) {
				if (!empty($friendInfo['company'])) {
					$friend_name = (strlen($xss->filter(strip_tags(htmlspecialchars_decode($friendInfo['company'], ENT_QUOTES)))) > 100 ? substr($xss->filter(strip_tags(htmlspecialchars_decode($friendInfo['company'], ENT_QUOTES))), 0, 100).'...' : $xss->filter(strip_tags(htmlspecialchars_decode($friendInfo['company'], ENT_QUOTES))));
					$friend_fullname = $xss->filter(strip_tags(htmlspecialchars_decode($friendInfo['company'], ENT_QUOTES)));
				} else {
					$friend_name = (strlen($xss->filter(strip_tags(htmlspecialchars_decode($friendInfo['nickname'], ENT_QUOTES)))) > 100 ? substr($xss->filter(strip_tags(htmlspecialchars_decode($friendInfo['nickname'], ENT_QUOTES))), 0, 100).'...' : $xss->filter(strip_tags(htmlspecialchars_decode($friendInfo['nickname'], ENT_QUOTES))));
					$friend_fullname = $xss->filter(strip_tags(htmlspecialchars_decode($friendInfo['nickname'], ENT_QUOTES)));
				}
				
				$friend_image = $jUser->GetAvatar($friendInfo['username'], $friendInfo['email']);
				$friend_email = $friendInfo['email'];
				$friend_groups  = $jUser->GetGroupsOfUser($friendInfo['id']);
				
				// Check if user is in profile group
				$show_link = false;
				if (!Jaws_Error::IsError($friend_groups)) {
					foreach ($friend_groups as $group) {
						if (
							strtolower($group['group_name']) == 'profile' && 
							in_array($group['group_status'], array('active','founder','admin'))
						) {
							$show_link = true;
							break;
						}
					}
				}
				$friend_link = '';
				$friend_link_start = '';
				$friend_link_end = '';
				if ($show_link === true) {
					$friend_link = $GLOBALS['app']->GetSiteURL().'/'.$GLOBALS['app']->Map->GetURLFor('Users', 'AccountPublic', array('name' => $friendInfo['username']));
					$friend_link_start = '<a href="'.$friend_link.'" title="'.preg_replace("[^A-Za-z0-9\ ]", '', $friend_fullname).'" name="'.preg_replace("[^A-Za-z0-9\ ]", '', $friend_fullname).'">';
					$friend_link_end = '</a>';
				}
		
				if ($friend['friend_status'] == 'active') {
					// Show users added to groups
					if (!Jaws_Error::IsError($friend_groups)) {
						foreach ($friend_groups as $group) {
							if (
								in_array($group['group_status'], array('active','founder','admin')) && 
								!in_array($group['group_name'], array('users','no_profile','profile')) && 
								strpos(strtolower($group['group_name']), '_owners') === false &&
								strpos(strtolower($group['group_name']), '_users') === false
							) {
								$groupInfo = $jUser->GetGroupInfoById($group['group_id']);
								if (!Jaws_Error::IsError($groupInfo) && isset($groupInfo['id']) && !empty($groupInfo['id'])) {
									// Friends' Groups
									if (
										$usersModel->IsRecommendationSharedWithUser('_r_'.$gadget.'addusertogroup'.$viewer_id.$group['group_id'], 'Users', $viewer_id, $public) && 
										!in_array('_r_'.$gadget.'addusertogroup'.$viewer_id.$group['group_id'], $GLOBALS['app']->_ItemsOnLayout)
									) {
										$GLOBALS['app']->_ItemsOnLayout[] = '_r_'.$gadget.'addusertogroup'.$viewer_id.$group['group_id'];
										$group_name = (!empty($group['group_title']) ? $group['group_title'] : $group['group_name']);
										$safe_name = $xss->filter(preg_replace("[^A-Za-z0-9\ ]", '', $group_name));
										$image = $jUser->GetGroupAvatar($group['group_id']);
										$link = $GLOBALS['app']->Map->GetURLFor('Users', 'UserDirectory', array('Users_gid' => $group['group_id']));
										// Group's member count
										$group_count = '0';
										$params = array();
										$params['group_id'] = $group['group_id'];

										$sql = '
											SELECT COUNT([user_id])
											FROM [[users_groups]]
											WHERE
												[group_id] = {group_id}';

										$howmany = $GLOBALS['db']->queryOne($sql, $params);
										if (!Jaws_Error::IsError($howmany) && (int)$howmany > 0) {
											$group_count = $howmany;
										}
										
										// Group's CustomPage
										if (Jaws_Gadget::IsGadgetUpdated('CustomPage')) {
											$pageModel = $GLOBALS['app']->LoadGadget('CustomPage', 'Model');
											$pageHTML = $GLOBALS['app']->LoadGadget('CustomPage', 'HTML');
											$page = $pageModel->GetGroupHomePage($group['group_id']);
											if (!Jaws_Error::IsError($page) && isset($page['id'])) {
												$link = $GLOBALS['app']->Map->GetURLFor('Users', 'GroupPage', array('group' => $group['group_name']));
											}
										}
										
										$comment = '<table border="0" width="100%" cellpadding="0" cellspacing="0"><tbody><tr><td valign="top" width="100%">
											<p classname="recommendation-update recommendation-addusertogroup-update" class="recommendation-update recommendation-addusertogroup-update" style="text-align: left; display: block;" id="recommendation-addusertogroup-update-'.$viewer_id.$group['group_id'].'" align="left">
											<a title="'.$safe_name.'" name="'.$safe_name.'" href="'.$link.'">
											<img style="text-align: left; margin-right: 10px; margin-bottom: 10px; max-width: 80px; max-height: 80px;" class="recommendation-update-icon recommendation-addusertogroup-update-icon" id="recommendation-addusertogroup-update-'.$group['group_id'].'-icon" src="'.$image.'" alt="'.$safe_name.'" align="left" />
											</a>
											<strong><a title="'.$safe_name.'" name="'.$safe_name.'" href="'.$link.'">'.$group_name.'</a></strong>
											<br />'.$group_name.' has '.$group_count.' members</p></td>
											<td width="0%"><nobr>
											<button title="Join" name="accept" onclick="location.href=\''.$GLOBALS['app']->GetSiteURL().'/index.php?gadget=Users&action=account_AuthUserGroup&group='.$group['group_id'].'&user='.$viewer_id.'&status=request\';">Join</button>&nbsp;
											</nobr></td></tr></tbody></table>';
																	
										$item = array(
											'id' => '_r_'.$gadget.'addusertogroup'.$viewer_id.$group['group_id'],
											'name' => '',
											'email' => '',
											'url' => '',
											'title' => '',
											'msg_key' => md5(''.''.$comment.$viewer_id.$group['group_id'].'Groups'.(0)),
											'msg_txt' => $comment,
											'image' => '',
											'type' => 'addusertogroup',
											'sharing' => 'everyone',
											'status' => 'approved',
											'gadget' => 'Groups',
											'gadget_reference' => $group['group_id'],
											'parent' => 0,
											'replies' => false,
											'ownerid' => $viewer_id,
											'targetid' => $group['group_id'],
											'preactivity' => '',
											'activity' => '',
											'createtime' => $group['group_updated'],
											'data' => $groupInfo
										);
										$result[] = $item;
										$result_counter++;
										if ($result_counter > $result_max) {
											return $result;
										}
									}
								}
								
								// Similar to Friends' joined Groups
							}
						}
					}
				}
			}
		}
		*/
		
		// Follow Popular Groups (rated, number of joined)
		
		// Similar to Demographics
				
		// Follow all other Groups
		$groups = $jUser->GetAllGroups('name', array('core'));
		if (!Jaws_Error::IsError($groups)) {
			foreach ($groups as $group) {
				if (
					/* $usersModel->IsRecommendationSharedWithUser('_r_'.$gadget.'addusertogroup'.$viewer_id.$group['group_id'], 'Users', $viewer_id, $public) && */
					!in_array('_r_'.$gadget.'addusertogroup'.$viewer_id.$group['id'], $GLOBALS['app']->_ItemsOnLayout)
				) {
					$group_name = (!empty($group['title']) ? $group['title'] : $group['name']);
					$safe_name = $xss->filter(preg_replace("[^A-Za-z0-9\ ]", '', $group_name));
					$image = $jUser->GetGroupAvatar($group['id']);
					$link = $GLOBALS['app']->Map->GetURLFor('Users', 'UserDirectory', array('Users_gid' => $group['id']));
					
					// Group's member count
					$group_count = '';
					$params = array();
					$params['group_id'] = $group['id'];

					$sql = '
						SELECT COUNT([user_id])
						FROM [[users_groups]]
						WHERE
							[group_id] = {group_id}';

					$howmany = $GLOBALS['db']->queryOne($sql, $params);
					if (!Jaws_Error::IsError($howmany) && (int)$howmany > 0) {
						$group_count = $howmany;
					}
					
					// Group's CustomPage
					if (Jaws_Gadget::IsGadgetUpdated('CustomPage')) {
						$pageModel = $GLOBALS['app']->LoadGadget('CustomPage', 'Model');
						$pageHTML = $GLOBALS['app']->LoadGadget('CustomPage', 'HTML');
						$page = $pageModel->GetGroupHomePage($group['id']);
						if (!Jaws_Error::IsError($page) && isset($page['id'])) {
							$link = $GLOBALS['app']->Map->GetURLFor('Users', 'GroupPage', array('group' => $group['name']));
						}
					}
					
					$comment = '<table border="0" width="100%" cellpadding="0" cellspacing="0"><tbody><tr><td valign="top" width="100%">
						<p classname="recommendation-update recommendation-addusertogroup-update" class="recommendation-update recommendation-addusertogroup-update" style="text-align: left; display: block;" id="recommendation-addusertogroup-update-'.$viewer_id.$group['id'].'" align="left">
						<a title="'.$safe_name.'" name="'.$safe_name.'" href="'.$link.'">
						<img style="text-align: left; margin-right: 10px; margin-bottom: 10px; max-width: 80px; max-height: 80px;" class="recommendation-update-icon recommendation-addusertogroup-update-icon" id="recommendation-addusertogroup-update-'.$group['id'].'-icon" src="'.$image.'" alt="'.$safe_name.'" align="left" />
						</a>
						<strong><a title="'.$safe_name.'" name="'.$safe_name.'" href="'.$link.'">'.$group_name.'</a></strong>
						<br />'.$group_name.' has '.$group_count.' members</p></td>
						<td width="0%"><nobr>
						<button title="Join" name="accept" onclick="location.href=\''.$GLOBALS['app']->GetSiteURL().'/index.php?gadget=Users&action=account_AuthUserGroup&group='.$group['id'].'&status=request\';">Join</button>&nbsp;
						</nobr></td></tr></tbody></table>';
												
					// Add this product as a comment
					$item = array(
						'id' => '_r_'.$gadget.'addusertogroup'.$viewer_id.$group['id'],
						'name' => '',
						'email' => '',
						'url' => '',
						'title' => '',
						'msg_key' => md5(''.''.$comment.$viewer_id.$group['id'].'Groups'.(0)),
						'msg_txt' => $comment,
						'image' => '',
						'type' => 'addusertogroup',
						'sharing' => 'everyone',
						'status' => 'approved',
						'gadget' => 'Groups',
						'gadget_reference' => $group['id'],
						'parent' => 0,
						'replies' => false,
						'ownerid' => $viewer_id,
						'targetid' => $group['id'],
						'preactivity' => '',
						'activity' => '',
						'createtime' => $GLOBALS['db']->Date(),
						'data' => $group
					);
					$GLOBALS['app']->_ItemsOnLayout[] = '_r_'.$gadget.'addusertogroup'.$viewer_id.$group['id'];
					$result[] = $item;
					$result_counter++;
					if ($result_counter > $result_max) {
						return $result;
					}
				}
			}
		}
		return $result;
	}
    
    /**
     * Returns an array of single comment thread
     *
     * @access  public
     */
    function GetFriendRecommendations($params = array())
    {
		$OwnerID = $params['uid'];
		$viewer_id = (int)$GLOBALS['app']->Session->GetAttribute('user_id');
		$id = $params['gadget_reference'];
		if (!is_null($id)) {
			$OwnerID = $id;
		}
		$gadget = $params['gadget'];
		$public = $params['public'];
		$result_limit = (int)$params['limit'];
		$result_max = (int)$params['max'];
		$extra_params = $params['params'];
		
		$result_counter = 0;
		
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		require_once JAWS_PATH . 'include/Jaws/Image.php';
		$usersModel = $GLOBALS['app']->loadGadget('Users', 'Model');
		$jUser = new Jaws_User;
		
		// Load via hook		
		
		// Get all friend recommendations
		$friends = $jUser->GetFriendsOfUser($viewer_id, 'created', 'DESC', $result_limit);
		foreach ($friends as $friend) {
			if ($friend['friend_status'] == 'active' && (int)$friend['friend_id'] != $viewer_id) {
				$friendInfo = $jUser->GetUserInfoById((int)$friend['friend_id'], true, true, true, true);
				if (!Jaws_Error::IsError($friendInfo) && isset($friendInfo['id']) && !empty($friendInfo['id'])) {
					if (!empty($friendInfo['company'])) {
						$friend_name = (strlen($xss->filter(strip_tags(htmlspecialchars_decode($friendInfo['company'], ENT_QUOTES)))) > 100 ? substr($xss->filter(strip_tags(htmlspecialchars_decode($friendInfo['company'], ENT_QUOTES))), 0, 100).'...' : $xss->filter(strip_tags(htmlspecialchars_decode($friendInfo['company'], ENT_QUOTES))));
						$friend_fullname = $xss->filter(strip_tags(htmlspecialchars_decode($friendInfo['company'], ENT_QUOTES)));
					} else {
						$friend_name = (strlen($xss->filter(strip_tags(htmlspecialchars_decode($friendInfo['nickname'], ENT_QUOTES)))) > 100 ? substr($xss->filter(strip_tags(htmlspecialchars_decode($friendInfo['nickname'], ENT_QUOTES))), 0, 100).'...' : $xss->filter(strip_tags(htmlspecialchars_decode($friendInfo['nickname'], ENT_QUOTES))));
						$friend_fullname = $xss->filter(strip_tags(htmlspecialchars_decode($friendInfo['nickname'], ENT_QUOTES)));
					}
					
					$friend_image = $jUser->GetAvatar($friendInfo['username'], $friendInfo['email']);
					$friend_email = $friendInfo['email'];
					$friend_groups  = $jUser->GetGroupsOfUser($friendInfo['id']);
					
					// Check if user is in profile group
					$show_link = false;
					if (!Jaws_Error::IsError($friend_groups)) {
						foreach ($friend_groups as $group) {
							if (
								strtolower($group['group_name']) == 'profile' && 
								in_array($group['group_status'], array('active','founder','admin'))
							) {
								$show_link = true;
								break;
							}
						}
					}
					$friend_link = '';
					$friend_link_start = '';
					$friend_link_end = '';
					if ($show_link === true) {
						$friend_link = $GLOBALS['app']->GetSiteURL().'/'.$GLOBALS['app']->Map->GetURLFor('Users', 'AccountPublic', array('name' => $friendInfo['username']));
						$friend_link_start = '<a href="'.$friend_link.'" title="'.preg_replace("[^A-Za-z0-9\ ]", '', $friend_fullname).'" name="'.preg_replace("[^A-Za-z0-9\ ]", '', $friend_fullname).'">';
						$friend_link_end = '</a>';
					}
			
					// Get all friends of friend
					$friendFriends = $jUser->GetFriendsOfUser($friendInfo['id'], 'created', 'DESC', $result_limit);
					foreach ($friendFriends as $friendFriend) {
						if (
							$friendFriend['friend_status'] == 'active' && (int)$friendFriend['friend_id'] != $viewer_id && 
							!in_array($jUser->GetStatusOfUserInFriend($viewer_id, (int)$friendFriend['friend_id']), array('blocked','active','denied','request'))
						) {
							$friendFriendInfo = $jUser->GetUserInfoById((int)$friendFriend['friend_id'], true, true, true, true);
							if (!Jaws_Error::IsError($friendFriendInfo) && isset($friendFriendInfo['id']) && !empty($friendFriendInfo['id'])) {
								if (
									/* $usersModel->IsRecommendationSharedWithUser('_r_'.$gadget.'addusertofriend'.$viewer_id.$friendFriendInfo['id'], 'Users', $viewer_id, $public) && */ 
									!in_array('_r_'.$gadget.'addusertofriend'.$viewer_id.$friendFriendInfo['id'], $GLOBALS['app']->_ItemsOnLayout)
								) {
									$GLOBALS['app']->_ItemsOnLayout[] = '_r_'.$gadget.'addusertofriend'.$viewer_id.$friendFriendInfo['id'];
									if (!empty($friendFriendInfo['company'])) {
										$friendFriend_name = (strlen($xss->filter(strip_tags(htmlspecialchars_decode($friendFriendInfo['company'], ENT_QUOTES)))) > 100 ? substr($xss->filter(strip_tags(htmlspecialchars_decode($friendFriendInfo['company'], ENT_QUOTES))), 0, 100).'...' : $xss->filter(strip_tags(htmlspecialchars_decode($friendFriendInfo['company'], ENT_QUOTES))));
										$friendFriend_fullname = $xss->filter(strip_tags(htmlspecialchars_decode($friendFriendInfo['company'], ENT_QUOTES)));
									} else {
										$friendFriend_name = (strlen($xss->filter(strip_tags(htmlspecialchars_decode($friendFriendInfo['nickname'], ENT_QUOTES)))) > 100 ? substr($xss->filter(strip_tags(htmlspecialchars_decode($friendFriendInfo['nickname'], ENT_QUOTES))), 0, 100).'...' : $xss->filter(strip_tags(htmlspecialchars_decode($friendFriendInfo['nickname'], ENT_QUOTES))));
										$friendFriend_fullname = $xss->filter(strip_tags(htmlspecialchars_decode($friendFriendInfo['nickname'], ENT_QUOTES)));
									}
									
									$friendFriend_image = $jUser->GetAvatar($friendFriendInfo['username'], $friendFriendInfo['email']);
									$friendFriend_email = $friendFriendInfo['email'];
									$friendFriend_groups  = $jUser->GetGroupsOfUser($friendFriendInfo['id']);
									
									// Check if user is in profile group
									$friend_show_link = false;
									if (!Jaws_Error::IsError($friendFriend_groups)) {
										foreach ($friendFriend_groups as $friend_group) {
											if (
												strtolower($friend_group['group_name']) == 'profile' && 
												in_array($friend_group['group_status'], array('active','founder','admin'))
											) {
												$friend_show_link = true;
												break;
											}
										}
									}
									$friendFriend_link = '';
									$friendFriend_link_start = '';
									$friendFriend_link_end = '';
									if ($friend_show_link === true) {
										$friendFriend_link = $GLOBALS['app']->GetSiteURL().'/'.$GLOBALS['app']->Map->GetURLFor('Users', 'AccountPublic', array('name' => $friendFriendInfo['username']));
										$friendFriend_link_start = '<a href="'.$friendFriend_link.'" title="'.preg_replace("[^A-Za-z0-9\ ]", '', $friendFriend_fullname).'" name="'.preg_replace("[^A-Za-z0-9\ ]", '', $friendFriend_fullname).'">';
										$friendFriend_link_end = '</a>';
									}
							
									$friend_friend_comment = '<table border="0" width="100%" cellpadding="0" cellspacing="0"><tbody><tr><td valign="top" width="100%">
										<p classname="recommendation-update recommendation-addusertofriend-update" class="recommendation-update recommendation-addusertofriend-update" style="text-align: left; display: block;" id="recommendation-addusertofriend-update-'.$viewer_id.$friendFriendInfo['id'].'" align="left">
										'.$friendFriend_link_start.'
										<img style="text-align: left; margin-right: 10px; margin-bottom: 10px; max-width: 50px; max-height: 50px;" class="recommendation-update-icon recommendation-addusertofriend-update-icon" id="recommendation-addusertofriend-update-'.$friendFriendInfo['id'].'-icon" src="'.$friendFriend_image.'" alt="'.preg_replace("[^A-Za-z0-9\ ]", '', $friendFriend_fullname).'" align="left" />
										'.$friendFriend_link_end.'
										<strong>'.$friendFriend_link_start.$friendFriend_fullname.$friendFriend_link_end.'</strong></p>
										</p></td>
										<td width="0%"><nobr><button title="Add" name="accept" onclick="location.href=\''.$GLOBALS['app']->GetSiteURL().'/index.php?gadget=Users&action=RequestedFriend&friend_id='.$friendFriendInfo['id'].'&status=request&redirect_to='.urlencode($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction').'#pane=Friends').'\';">Add</button>&nbsp;
										</nobr>
										</td></tr></tbody></table>';

									$edit_links = array(
										0 => array(
											'url' => "javascript: if (confirm(confirmRecommendationDelete)){location.href='".$GLOBALS['app']->GetSiteURL('', false, 'http')."/index.php?gadget=Users&action=RequestedFriend&friend_id=".$friendFriendInfo['id']."&status=denied&redirect_to=".urlencode($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction').'#pane=Friends')."';};",
											'title' => "Hide This"
										),
										1 => array(
											'url' => "javascript: addUpdate('".$GLOBALS['app']->GetSiteURL('', false, 'http')."/index.php?gadget=Users&action=account_AddLayoutElement&mode=new&first=Status&sharing=".urlencode("users:".$friendFriendInfo['id'])."');",
											'title' => "Send Message"
										)
									);
									if ($friendFriend_show_link === true) {
										$edit_links[] = array(
											'url' => $friendFriend_link,
											'title' => "View"
										);
									}
										
									$item = array(
										'id' => '_r_'.$gadget.'addusertofriend'.$viewer_id.$friendFriendInfo['id'],
										'name' => '',
										'email' => '',
										'url' => '',
										'title' => '',
										'msg_key' => md5(''.$friend_email.$friend_friend_comment.$friendFriendInfo['id'].$friendInfo['id'].'Users'.(0)),
										'msg_txt' => $friend_friend_comment,
										'image' => '',
										'type' => 'addusertofriend',
										'sharing' => 'everyone',
										'status' => 'approved',
										'gadget' => 'Users',
										'gadget_reference' => $friendInfo['id'],
										'parent' => 0,
										'replies' => false,
										'ownerid' => $viewer_id,
										'targetid' => $friendFriendInfo['id'],
										'preactivity' => '',
										'activity' => '',
										'createtime' => $friendFriend['friend_updated']
									);
									$result[] = $item;
									$result_counter++;
									if ($result_counter > $result_max) {
										return $result;
									}
								}
							}
						}
					}
				}
			}
		}
				
		// Show users in user's groups
		$userInfo = $jUser->GetUserInfoById($viewer_id, true, true, true, true);
		if (!Jaws_Error::IsError($userInfo) && isset($userInfo['id']) && !empty($userInfo['id'])) {
			/*
			if (!empty($userInfo['company'])) {
				$friend_name = (strlen($xss->filter(strip_tags(htmlspecialchars_decode($userInfo['company'], ENT_QUOTES)))) > 100 ? substr($xss->filter(strip_tags(htmlspecialchars_decode($userInfo['company'], ENT_QUOTES))), 0, 100).'...' : $xss->filter(strip_tags(htmlspecialchars_decode($userInfo['company'], ENT_QUOTES))));
				$friend_fullname = $xss->filter(strip_tags(htmlspecialchars_decode($userInfo['company'], ENT_QUOTES)));
			} else {
				$friend_name = (strlen($xss->filter(strip_tags(htmlspecialchars_decode($userInfo['nickname'], ENT_QUOTES)))) > 100 ? substr($xss->filter(strip_tags(htmlspecialchars_decode($userInfo['nickname'], ENT_QUOTES))), 0, 100).'...' : $xss->filter(strip_tags(htmlspecialchars_decode($userInfo['nickname'], ENT_QUOTES))));
				$friend_fullname = $xss->filter(strip_tags(htmlspecialchars_decode($userInfo['nickname'], ENT_QUOTES)));
			}
			
			$user_image = $jUser->GetAvatar($userInfo['username'], $userInfo['email']);
			*/
			$user_email = $userInfo['email'];
			$user_groups  = $jUser->GetGroupsOfUser($userInfo['id']);
			if (!Jaws_Error::IsError($user_groups)) {
				// Sort result array
				$subkey = 'group_id'; 
				$temp_array = array();
				$temp_array[key($user_groups)] = array_shift($user_groups);
				$users_found = false;
				foreach($user_groups as $key => $val){
					if ($val['group_name'] == 'users') {
						$users_found = true;
					}
					$offset = 0;
					$found = false;
					foreach($temp_array as $tmp_key => $tmp_val) {
						$val[$subkey] = (string)$val[$subkey];
						if(!$found && strtolower($val[$subkey]) > strtolower($tmp_val[$subkey])) {
							$temp_array = array_merge(
								(array)array_slice($temp_array,0,$offset),
								array($key => $val),
								array_slice($temp_array,$offset)
							);
							$found = true;
						}
						$offset++;
					}
					if(!$found) $temp_array = array_merge($temp_array, array($key => $val));
				}
				$user_groups = array_reverse($temp_array);
				if ($users_found === false) {
					$defaultGroup = $jUser->GetGroupInfoByName('users');
					if (!Jaws_Error::IsError($defaultGroup) && isset($defaultGroup['name'])) {
						$user_groups[] = array(
							"group_id" => $defaultGroup['id'], 
							"group_name" => 'users',
							"group_title" => $defaultGroup['title'],
							"group_status" => 'active',
							"group_checksum" => $defaultGroup['checksum'],
							"group_updated" => NULL
						);
					}
				}
				foreach ($user_groups as $group) {
					if (in_array($group['group_status'], array('active','founder','admin'))) {
						// Get all users in group
						$groupUsers = $jUser->GetUsersOfGroup($group['group_id']);
						foreach ($groupUsers as $groupUser) {
							if (
								in_array($groupUser['group_status'], array('active','founder','admin')) && (int)$groupUser['user_id'] != $viewer_id && 
								!in_array($jUser->GetStatusOfUserInFriend($viewer_id, (int)$groupUser['user_id']), array('blocked','active','denied','request'))
							) {
								$groupUserInfo = $jUser->GetUserInfoById((int)$groupUser['user_id'], true, true, true, true);
								if (!Jaws_Error::IsError($groupUserInfo) && isset($groupUserInfo['id']) && !empty($groupUserInfo['id'])) {
									if (
										/* $usersModel->IsRecommendationSharedWithUser('_r_'.$gadget.'addusertofriend'.$viewer_id.$groupUserInfo['id'], 'Users', $viewer_id, $public) && */
										!in_array('_r_'.$gadget.'addusertofriend'.$viewer_id.$groupUserInfo['id'], $GLOBALS['app']->_ItemsOnLayout)
									) {
										$GLOBALS['app']->_ItemsOnLayout[] = '_r_'.$gadget.'addusertofriend'.$viewer_id.$groupUserInfo['id'];
										if (!empty($groupUserInfo['company'])) {
											$groupUser_name = (strlen($xss->filter(strip_tags(htmlspecialchars_decode($groupUserInfo['company'], ENT_QUOTES)))) > 100 ? substr($xss->filter(strip_tags(htmlspecialchars_decode($groupUserInfo['company'], ENT_QUOTES))), 0, 100).'...' : $xss->filter(strip_tags(htmlspecialchars_decode($groupUserInfo['company'], ENT_QUOTES))));
											$groupUser_fullname = $xss->filter(strip_tags(htmlspecialchars_decode($groupUserInfo['company'], ENT_QUOTES)));
										} else {
											$groupUser_name = (strlen($xss->filter(strip_tags(htmlspecialchars_decode($groupUserInfo['nickname'], ENT_QUOTES)))) > 100 ? substr($xss->filter(strip_tags(htmlspecialchars_decode($groupUserInfo['nickname'], ENT_QUOTES))), 0, 100).'...' : $xss->filter(strip_tags(htmlspecialchars_decode($groupUserInfo['nickname'], ENT_QUOTES))));
											$groupUser_fullname = $xss->filter(strip_tags(htmlspecialchars_decode($groupUserInfo['nickname'], ENT_QUOTES)));
										}
										
										$groupUser_image = $jUser->GetAvatar($groupUserInfo['username'], $groupUserInfo['email']);
										$groupUser_email = $groupUserInfo['email'];
										$groupUser_groups  = $jUser->GetGroupsOfUser($groupUserInfo['id']);
										
										// Check if user is in profile group
										$groupUser_show_link = false;
										if (!Jaws_Error::IsError($groupUser_groups)) {
											foreach ($groupUser_groups as $groupUser_groups) {
												if (
													strtolower($groupUser_groups['group_name']) == 'profile' && 
													in_array($groupUser_groups['group_status'], array('active','founder','admin'))
												) {
													$groupUser_show_link = true;
													break;
												}
											}
										}
										$groupUser_link = '';
										$groupUser_link_start = '';
										$groupUser_link_end = '';
										if ($groupUser_show_link === true) {
											$groupUser_link = $GLOBALS['app']->GetSiteURL().'/'.$GLOBALS['app']->Map->GetURLFor('Users', 'AccountPublic', array('name' => $groupUserInfo['username']));
											$groupUser_link_start = '<a href="'.$groupUser_link.'" title="'.preg_replace("[^A-Za-z0-9\ ]", '', $groupUser_fullname).'" name="'.preg_replace("[^A-Za-z0-9\ ]", '', $groupUser_fullname).'">';
											$groupUser_link_end = '</a>';
										}
								
										$groupUser_comment = '<table border="0" width="100%" cellpadding="0" cellspacing="0"><tbody><tr><td valign="top" width="100%">
											<p classname="recommendation-update recommendation-addusertofriend-update" class="recommendation-update recommendation-addusertofriend-update" style="text-align: left; display: block;" id="recommendation-addusertofriend-update-'.$viewer_id.$groupUserInfo['id'].'" align="left">
											'.$groupUser_link_start.'
											<img style="text-align: left; margin-right: 10px; margin-bottom: 10px; max-width: 50px; max-height: 50px;" class="recommendation-update-icon recommendation-addusertofriend-update-icon" id="recommendation-addusertofriend-update-'.$groupUserInfo['id'].'-icon" src="'.$groupUser_image.'" alt="'.preg_replace("[^A-Za-z0-9\ ]", '', $groupUser_fullname).'" align="left" />
											'.$groupUser_link_end.'
											<strong>'.$groupUser_link_start.$groupUser_fullname.$groupUser_link_end.'</strong></p>
											</p></td>
											<td width="0%"><nobr><button title="Add" name="accept" onclick="location.href=\''.$GLOBALS['app']->GetSiteURL().'/index.php?gadget=Users&action=RequestedFriend&friend_id='.$groupUserInfo['id'].'&status=request&redirect_to='.urlencode($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction').'#pane=Friends').'\';">Add</button>&nbsp;
											</nobr>
											</td></tr></tbody></table>';

										$edit_links = array(
											0 => array(
												'url' => "javascript: if (confirm(confirmRecommendationDelete)){location.href='".$GLOBALS['app']->GetSiteURL('', false, 'http')."/index.php?gadget=Users&action=RequestedFriend&friend_id=".$groupUserInfo['id']."&status=denied&redirect_to=".urlencode($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction').'#pane=Friends')."';};",
												'title' => "Hide This"
											),
											1 => array(
												'url' => "javascript: addUpdate('".$GLOBALS['app']->GetSiteURL('', false, 'http')."/index.php?gadget=Users&action=account_AddLayoutElement&mode=new&first=Status&sharing=".urlencode("users:".$groupUserInfo['id'])."');",
												'title' => "Send Message"
											)
										);
										if ($groupUser_show_link === true) {
											$edit_links[] = array(
												'url' => $groupUser_link,
												'title' => "View"
											);
										}

										$item = array(
											'id' => '_r_'.$gadget.'addusertofriend'.$viewer_id.$groupUserInfo['id'],
											'name' => '',
											'email' => '',
											'url' => '',
											'title' => '',
											'msg_key' => md5(''.$user_email.$groupUser_comment.$groupUserInfo['id'].$group['group_id'].'Users'.(0)),
											'msg_txt' => $groupUser_comment,
											'image' => '',
											'type' => 'addusertofriend',
											'sharing' => 'everyone',
											'status' => 'approved',
											'gadget' => 'Users',
											'gadget_reference' => $group['group_id'],
											'parent' => 0,
											'replies' => false,
											'ownerid' => $viewer_id,
											'targetid' => $groupUserInfo['id'],
											'preactivity' => '',
											'activity' => '',
											'edit_links' => $edit_links,
											'createtime' => $groupUser['group_updated']
										);
										$result[] = $item;
										$result_counter++;
										if ($result_counter > $result_max) {
											return $result;
										}
									}
								}
							}
						}
					}
				}
			}
		}
		return $result;
	}
											
	/**
     * Returns an array of single comment thread
     *
     * @access  public
     */
    function GetGroupRecommendation($params = array())
    {
		//return $result;
	}
	
	/**
     * Returns an array of single comment thread
     *
     * @access  public
     */
    function GetFriendRecommendation($params = array())
    {
		//return $result;
	}
}
