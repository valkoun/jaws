<?php
/**
 * Users - Comment gadget hook
 *
 * @category   GadgetHook
 * @package    Users
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2011 Alan Valkoun
 * @TODO	Move comment types HTML output into separate methods, use template files
 */
class UsersCommentHook
{
	var $_comments_owners = array();
			
    /**
     * Returns an array with all comments in the Store gadget owned by given user
     *
     * @access  public
     */
    function GetComments($params = array())
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
		$layout = strtolower($params['layout']);
		if (!in_array($layout, array('full', 'tiles', 'boxy'))) {
			$layout = 'full';
		}
		/*
		echo "\n\nlimit: ";
		var_dump($result_limit);
		echo "\n\nmax: ";
		var_dump($result_max);
		echo "\n\n";
		*/
        $result_counter = 0;
        $result_offset = 0;
        $result = array();
		require_once JAWS_PATH . 'include/Jaws/Comment.php';
        $api = new Jaws_Comment('Users');
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		require_once JAWS_PATH . 'include/Jaws/Image.php';
		$usersModel = $GLOBALS['app']->loadGadget('Users', 'Model');
		$jUser = new Jaws_User;
		
		if ($public === false && ($GLOBALS['app']->Session->IsAdmin() || $GLOBALS['app']->Session->IsSuperAdmin())) { 
			// Get all admin comments
			$admin_comments = $usersModel->GetCommentsFiltered('Users', 'ownerid', 0, 'approved', $result_limit);
			foreach ($admin_comments as $item) {
				if (
					($public === true || (int)$item['parent'] == 0) && !in_array('_'.$gadget.$item['id'], $GLOBALS['app']->_ItemsOnLayout)
				) {
					$GLOBALS['app']->_ItemsOnLayout[] = '_'.$gadget.$item['id'];
					if (!isset($item['preactivity'])) {
						// Shared with specific users? Show them...
						$share_activity = $usersModel->GetCommentShareActivity($item['sharing']);
						$item['preactivity'] = $share_activity;
					}
					if (!isset($item['msg_key'])) {
						$item['msg_key'] = md5($item['title'].$item['email'].$item['msg_txt'].$item['ownerid'].$item['gadget_reference'].$item['gadget'].$item['parent']);
					}
					$item['activity'] = '';
					if (!isset($this->_comments_owners[md5($item['preactivity'].$item['activity'].$item['gadget_reference'])])) {
						$this->_comments_owners[md5($item['preactivity'].$item['activity'].$item['gadget_reference'])] = array();
					}
					if (!in_array($item['ownerid'], $this->_comments_owners[md5($item['preactivity'].$item['activity'].$item['gadget_reference'])])) {
						$this->_comments_owners[md5($item['preactivity'].$item['activity'].$item['gadget_reference'])][] = $item['ownerid'];
					}
					$result[] = $item;
					$result_counter++;
					if ($result_counter > $result_max) {
						return $usersModel->GetSortedComments($result, $params);
					}
				}
			}
						
			// Get all users
			$users = $jUser->GetUsers(false, false, null, 'nickname', $result_limit);
			foreach ($users as $friend) {
				$friendInfo = $jUser->GetUserInfoById((int)$friend['id'], true, true, true, true);
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
			
					// Show users added to groups
					if (!Jaws_Error::IsError($friend_groups)) {
						foreach ($friend_groups as $group) {
							if (
								(in_array($group['group_status'], array('active','founder','admin')) && 
								!in_array($group['group_name'], array('users','no_profile','profile')) && 
								strpos(strtolower($group['group_name']), '_owners') === false &&
								strpos(strtolower($group['group_name']), '_users') === false)
							) {
								if (!in_array('_'.$gadget.'addusertogroup'.$friendInfo['id'].$group['group_id'], $GLOBALS['app']->_ItemsOnLayout)) {
									$group_name = (!empty($group['group_title']) ? $group['group_title'] : $group['group_name']);
									$safe_name = $xss->filter(preg_replace("[^A-Za-z0-9\ ]", '', $group_name));
									$image = $jUser->GetGroupAvatar($group['group_id']);
									$link = $GLOBALS['app']->Map->GetURLFor('Users', 'UserDirectory', array('Users_gid' => $group['group_id']));
									
									// Group's member count
									$group_count = '';
									$params = array();
									$params['group_id'] = $group['group_id'];
									$params['active'] = 'active';
									$params['admin'] = 'admin';
									$params['founder'] = 'founder';

									$sql = '
										SELECT COUNT([user_id])
										FROM [[users_groups]]
										WHERE
											[group_id] = {group_id}
											AND ([status] = {active} OR [status] = {admin} OR [status] = {founder})';

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
									/*
									if ($group['group_status'] == 'request') {
										$comment = '<table border="0" width="100%" cellpadding="0" cellspacing="0"><tbody><tr><td valign="top" width="100%">
											<p classname="news-update news-addusertogroup-update" class="news-update news-addusertogroup-update" style="text-align: left; display: block;" id="news-addusertogroup-update-'.$friendInfo['id'].$group['group_id'].'" align="left">
											<a title="'.$safe_name.'" name="'.$safe_name.'" href="'.$link.'">
											<img style="text-align: left; margin-right: 10px; margin-bottom: 10px; max-width: 80px; max-height: 80px;" class="news-update-icon news-addusertogroup-update-icon" id="news-addusertogroup-update-'.$group['group_id'].'-icon" src="'.$image.'" alt="'.$safe_name.'" align="left" />
											</a>
											<strong><a title="'.$safe_name.'" name="'.$safe_name.'" href="'.$link.'">'.$group_name.'</a></strong>
											<br />'.$group_name.' has '.$group_count.' members</p></td>
											<td width="0%"><nobr><button title="Accept" name="accept" onclick="location.href=\''.$GLOBALS['app']->GetSiteURL().'/admin.php?gadget=Users&action=AuthUserGroup&group='.$group['group_id'].'&user='.$friendInfo['id'].'&status=active\';">Accept</button>&nbsp;
											<button title="Deny" name="deny" onclick="location.href=\''.$GLOBALS['app']->GetSiteURL().'/admin.php?gadget=Users&action=AuthUserGroup&group='.$group['group_id'].'&user='.$friendInfo['id'].'&status=denied\';">Deny</button></nobr>
											</td></tr></tbody></table>';
										$preactivity = 'is requesting access to the group';
									} else {
									*/
										$comment = '<p class="news-update news-addusertogroup-update" style="padding-top: 5px; text-align: left; display: block;" id="news-addusertogroup-update-'.$friendInfo['id'].$group['group_id'].'" align="left">
											<span class="news-comments"><span class="comment">
											<a title="'.$safe_name.'" name="'.$safe_name.'" href="'.$link.'">
											<img style="text-align: left; margin-right: 10px; margin-bottom: 10px; max-width: 80px; max-height: 80px;" class="news-update-icon news-addusertogroup-update-icon" id="news-addusertogroup-update-'.$group['group_id'].'-icon" src="'.$image.'" alt="'.$safe_name.'" align="left" />
											</a>
											<strong><a title="'.$safe_name.'" name="'.$safe_name.'" href="'.$link.'">'.$group_name.'</a></strong>
											<br />'.$group_name.' has '.$group_count.' members<span style="display: block; float: none; clear: both; font-size: 1px; height: 1px;">&nbsp;</span></span></span></p>';
										$preactivity = 'is now a'.($group['group_status'] == 'admin' ? 'n admin' : ($group['group_status'] == 'founder' ? ' founder' : ' member')).' of';
									//}							
									// Add this product as a comment
									$item = array(
										'id' => 'addusertogroup'.$friendInfo['id'].$group['group_id'],
										'name' => '',
										'email' => '',
										'url' => '',
										'title' => '',
										'msg_key' => md5(''.$user_email.$comment.$friendInfo['id'].$group['group_id'].'Groups'.(0)),
										'msg_txt' => $comment,
										'image' => '',
										'type' => 'addusertogroup',
										'sharing' => 'everyone',
										'status' => 'approved',
										'gadget' => 'Groups',
										'gadget_reference' => $group['group_id'],
										'parent' => 0,
										'replies' => false,
										'ownerid' => $friendInfo['id'],
										'targetid' => $group['group_id'],
										'preactivity' => $preactivity,
										'activity' => '',
										'createtime' => $group['group_updated']
									);
									if ($usersModel->IsCommentSharedWithUser($item, 'Users', $viewer_id, $public)) {
										$GLOBALS['app']->_ItemsOnLayout[] = '_'.$gadget.'addusertogroup'.$friendInfo['id'].$group['group_id'];
										if (!isset($this->_comments_owners[md5($item['preactivity'].$item['activity'].$item['gadget_reference'])])) {
											$this->_comments_owners[md5($item['preactivity'].$item['activity'].$item['gadget_reference'])] = array();
										}
										if (!in_array($item['ownerid'], $this->_comments_owners[md5($item['preactivity'].$item['activity'].$item['gadget_reference'])])) {
											$this->_comments_owners[md5($item['preactivity'].$item['activity'].$item['gadget_reference'])][] = $item['ownerid'];
										}
										$result[] = $item;
										$result_counter++;
										if ($result_counter > $result_max) {
											return $usersModel->GetSortedComments($result, $params);
										}
									}
								}
							}
						}
					}
				}
			}
		}
		
		if (!is_null($OwnerID)) {	
			// Get requested user
			$userInfo = $jUser->GetUserInfoById((int)$OwnerID, true, true, true, true);
			if (!Jaws_Error::IsError($userInfo) && isset($userInfo['id']) && !empty($userInfo['id'])) {
				if (!empty($userInfo['company'])) {
					$user_name = (strlen($xss->filter(strip_tags(htmlspecialchars_decode($userInfo['company'], ENT_QUOTES)))) > 100 ? substr($xss->filter(strip_tags(htmlspecialchars_decode($userInfo['company'], ENT_QUOTES))), 0, 100).'...' : $xss->filter(strip_tags(htmlspecialchars_decode($userInfo['company'], ENT_QUOTES))));
					$user_fullname = $xss->filter(strip_tags(htmlspecialchars_decode($userInfo['company'], ENT_QUOTES)));
				} else {
					$user_name = (strlen($xss->filter(strip_tags(htmlspecialchars_decode($userInfo['nickname'], ENT_QUOTES)))) > 100 ? substr($xss->filter(strip_tags(htmlspecialchars_decode($userInfo['nickname'], ENT_QUOTES))), 0, 100).'...' : $xss->filter(strip_tags(htmlspecialchars_decode($userInfo['nickname'], ENT_QUOTES))));
					$user_fullname = $xss->filter(strip_tags(htmlspecialchars_decode($userInfo['nickname'], ENT_QUOTES)));
				}
				
				$user_image = $jUser->GetAvatar($userInfo['username'], $userInfo['email']);
				$user_email = $userInfo['email'];
				$groups  = $jUser->GetGroupsOfUser($userInfo['id']);
				
				// Check if user is in profile group
				$show_link = false;
				if (!Jaws_Error::IsError($groups)) {
					foreach ($groups as $group) {
						if (
							strtolower($group['group_name']) == 'profile' && 
							in_array($group['group_status'], array('active','founder','admin'))
						) {
							$show_link = true;
							break;
						}
					}
				}
				$user_link = '';
				$user_link_start = '';
				$user_link_end = '';
				if ($show_link === true) {
					$user_link = $GLOBALS['app']->Map->GetURLFor('Users', 'AccountPublic', array('name' => $userInfo['username']));
					$user_link_start = '<a href="'.$GLOBALS['app']->GetSiteURL().'/'.$GLOBALS['app']->Map->GetURLFor('Users', 'AccountPublic', array('name' => $userInfo['username'])).'" title="'.preg_replace("[^A-Za-z0-9\ ]", '', $user_fullname).'" name="'.preg_replace("[^A-Za-z0-9\ ]", '', $user_fullname).'">';
					$user_link_end = '</a>';
				}
								
				// Comments owned by user
				$owned_comments = $usersModel->GetCommentsFiltered('Users', 'ownerid', $userInfo['id'], 'approved', $result_limit);
				foreach ($owned_comments as $item) {
					if (
						($public === true || (int)$item['parent'] == 0) && ((int)$userInfo['id'] == (int)$item['ownerid'] || $viewer_id == (int)$item['ownerid'] || 
						$usersModel->IsCommentSharedWithUser($item, 'Users', $viewer_id, $public)) && 
						!in_array('_'.$gadget.$item['id'], $GLOBALS['app']->_ItemsOnLayout)
					) {
						$GLOBALS['app']->_ItemsOnLayout[] = '_'.$gadget.$item['id'];
						if (!isset($item['preactivity'])) {
							// Shared with specific users? Show them...
							$share_activity = $usersModel->GetCommentShareActivity($item['sharing']);
							$item['preactivity'] = $share_activity;
						}
						if (!isset($item['msg_key'])) {
							$item['msg_key'] = md5($item['title'].$item['email'].$item['msg_txt'].$item['ownerid'].$item['gadget_reference'].$item['gadget'].$item['parent']);
						}
						$item['activity'] = '';
						if (!isset($this->_comments_owners[md5($item['preactivity'].$item['activity'].$item['gadget_reference'])])) {
							$this->_comments_owners[md5($item['preactivity'].$item['activity'].$item['gadget_reference'])] = array();
						}
						if (!in_array($item['ownerid'], $this->_comments_owners[md5($item['preactivity'].$item['activity'].$item['gadget_reference'])])) {
							$this->_comments_owners[md5($item['preactivity'].$item['activity'].$item['gadget_reference'])][] = $item['ownerid'];
						}
						$result[] = $item;
						$result_counter++;
						if ($result_counter > $result_max) {
							return $usersModel->GetSortedComments($result, $params);
						}
					}
				}
				
				// Comments to user
				$shared_comments = $usersModel->GetCommentsSharedWithUser($userInfo['id'], 'Users', 'approved', $result_limit);
				$admin_comments = $usersModel->GetCommentsFiltered('Users', 'postid', $userInfo['id'], 'approved', $result_limit);
				if (is_array($admin_comments)) {
					$shared_comments = array_merge($shared_comments, $admin_comments);
				}
				foreach ($shared_comments as $item) {
					if (
						($public === true || (int)$item['parent'] == 0) && ((int)$userInfo['id'] == (int)$item['ownerid'] || $viewer_id == (int)$item['ownerid'] || 
						$usersModel->IsCommentSharedWithUser($item, 'Users', $viewer_id, $public)) && 
						!in_array('_'.$gadget.$item['id'], $GLOBALS['app']->_ItemsOnLayout)
					) {
						$GLOBALS['app']->_ItemsOnLayout[] = '_'.$gadget.$item['id'];
						if (!isset($item['preactivity'])) {
							// Shared with specific users? Show them...
							$share_activity = $usersModel->GetCommentShareActivity($item['sharing']);
							$item['preactivity'] = $share_activity;
						}
						if (!isset($item['msg_key'])) {
							$item['msg_key'] = md5($item['title'].$item['email'].$item['msg_txt'].$item['ownerid'].$item['gadget_reference'].$item['gadget'].$item['parent']);
						}
						$item['activity'] = '';
						if (!isset($this->_comments_owners[md5($item['preactivity'].$item['activity'].$item['gadget_reference'])])) {
							$this->_comments_owners[md5($item['preactivity'].$item['activity'].$item['gadget_reference'])] = array();
						}
						if (!in_array($item['ownerid'], $this->_comments_owners[md5($item['preactivity'].$item['activity'].$item['gadget_reference'])])) {
							$this->_comments_owners[md5($item['preactivity'].$item['activity'].$item['gadget_reference'])][] = $item['ownerid'];
						}
						$result[] = $item;
						$result_counter++;
						if ($result_counter > $result_max) {
							return $usersModel->GetSortedComments($result, $params);
						}
					}
				}
									
				// Added to groups
				if (!Jaws_Error::IsError($groups)) {
					foreach ($groups as $group) {
						if (
							in_array($group['group_status'], array('active','founder','admin')) && 
							!in_array($group['group_name'], array('profile','no_profile','users')) && 
							strpos(strtolower($group['group_name']), '_owners') === false &&
							strpos(strtolower($group['group_name']), '_users') === false
						) {
							if (!in_array('_'.$gadget.'addusertogroup'.$userInfo['id'].$group['group_id'], $GLOBALS['app']->_ItemsOnLayout)) {
								$group_name = (!empty($group['group_title']) ? $group['group_title'] : $group['group_name']);
								$safe_name = $xss->filter(preg_replace("[^A-Za-z0-9\ ]", '', $group_name));
								$image = $jUser->GetGroupAvatar($group['group_id']);
								$link = $GLOBALS['app']->Map->GetURLFor('Users', 'UserDirectory', array('Users_gid' => $group['group_id']));
								
								// Group's member count
								$group_count = '';
								$params = array();
								$params['group_id'] = $group['group_id'];
								$params['active'] = 'active';
								$params['admin'] = 'admin';
								$params['founder'] = 'founder';

								$sql = '
									SELECT COUNT([user_id])
									FROM [[users_groups]]
									WHERE
										[group_id] = {group_id}
										AND ([status] = {active} OR [status] = {admin} OR [status] = {founder})';

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
								
								$comment = '<p class="news-update news-addusertogroup-update" style="padding-top: 5px; text-align: left; display: block;" id="news-addusertogroup-update-'.$group['group_id'].'" align="left">
									<span class="news-comments"><span class="comment">
									<a title="'.$safe_name.'" name="'.$safe_name.'" href="'.$link.'">
									<img style="text-align: left; margin-right: 10px; margin-bottom: 10px; max-width: 80px; max-height: 80px;" class="news-update-icon news-addusertogroup-update-icon" id="news-addusertogroup-update-'.$group['group_id'].'-icon" src="'.$image.'" alt="'.$safe_name.'" align="left" />
									</a>
									<strong><a title="'.$safe_name.'" name="'.$safe_name.'" href="'.$link.'">'.$group_name.'</a></strong>
									<br />'.$group_name.' has '.$group_count.' members<span style="display: block; float: none; clear: both; font-size: 1px; height: 1px;">&nbsp;</span></span></span></p>';
															
								// Add this product as a comment
								$item = array(
									'id' => 'addusertogroup'.$userInfo['id'].$group['group_id'],
									'name' => '',
									'email' => '',
									'url' => '',
									'title' => '',
									'msg_key' => md5(''.$user_email.$comment.$userInfo['id'].$group['group_id'].'Groups'.(0)),
									'msg_txt' => $comment,
									'image' => '',
									'type' => 'addusertogroup',
									'sharing' => 'everyone',
									'status' => 'approved',
									'gadget' => 'Groups',
									'gadget_reference' => $group['group_id'],
									'parent' => 0,
									'replies' => false,
									'ownerid' => $userInfo['id'],
									'targetid' => $group['group_id'],
									'preactivity' => 'is now a member of',
									'activity' => '',
									'createtime' => $group['group_updated']
								);
								if ($usersModel->IsCommentSharedWithUser($item, 'Users', $viewer_id, $public)) {
									$GLOBALS['app']->_ItemsOnLayout[] = '_'.$gadget.'addusertogroup'.$userInfo['id'].$group['group_id'];
									if (!isset($this->_comments_owners[md5($item['preactivity'].$item['activity'].$item['gadget_reference'])])) {
										$this->_comments_owners[md5($item['preactivity'].$item['activity'].$item['gadget_reference'])] = array();
									}
									if (!in_array($item['ownerid'], $this->_comments_owners[md5($item['preactivity'].$item['activity'].$item['gadget_reference'])])) {
										$this->_comments_owners[md5($item['preactivity'].$item['activity'].$item['gadget_reference'])][] = $item['ownerid'];
									}
									$result[] = $item;
									$result_counter++;
									if ($result_counter > $result_max) {
										return $usersModel->GetSortedComments($result, $params);
									}
								}
							}
						}
					}
				}
								
				if ($public === false) {
					// Get all admin comments
					$admin_comments = $usersModel->GetCommentsFiltered('Users', 'ownerid', 0, 'approved', $result_limit);
					foreach ($admin_comments as $item) {
						if (
							(int)$item['parent'] == 0 && $usersModel->IsCommentSharedWithUser($item, 'Users', $viewer_id, $public) && 
							!in_array('_'.$gadget.$item['id'], $GLOBALS['app']->_ItemsOnLayout)
						) {
							$GLOBALS['app']->_ItemsOnLayout[] = '_'.$gadget.$item['id'];
							if (!isset($item['preactivity'])) {
								// Shared with specific users? Show them...
								$share_activity = $usersModel->GetCommentShareActivity($item['sharing']);
								$item['preactivity'] = $share_activity;
							}
							if (!isset($item['msg_key'])) {
								$item['msg_key'] = md5($item['title'].$item['email'].$item['msg_txt'].$item['ownerid'].$item['gadget_reference'].$item['gadget'].$item['parent']);
							}
							$item['activity'] = '';
							if (!isset($this->_comments_owners[md5($item['preactivity'].$item['activity'].$item['gadget_reference'])])) {
								$this->_comments_owners[md5($item['preactivity'].$item['activity'].$item['gadget_reference'])] = array();
							}
							if (!in_array($item['ownerid'], $this->_comments_owners[md5($item['preactivity'].$item['activity'].$item['gadget_reference'])])) {
								$this->_comments_owners[md5($item['preactivity'].$item['activity'].$item['gadget_reference'])][] = $item['ownerid'];
							}
							$result[] = $item;
							$result_counter++;
							if ($result_counter > $result_max) {
								return $usersModel->GetSortedComments($result, $params);
							}
						}
					}
					
					// Get all comments of interests
					/*
					if (isset($userInfo['keywords']) && !empty($userInfo['keywords'])) {
						$keywords = str_replace(', ', ',', $userInfo['keywords']);
						$keywords = str_replace(' ,', ',', $keywords);
						$keywords = explode(',', $keywords);
						foreach ($keywords as $keyword) {
							if (!empty($keyword)) {
								$messages = $usersModel->GetCommentsFiltered('Users', 'comment', $keyword, 'approved', $result_limit);
								foreach ($messages as $item) {
									if (
										(int)$item['parent'] == 0 && $usersModel->IsCommentSharedWithUser($item, 'Users', $viewer_id, $public) && 
										!in_array('_'.$gadget.$item['id'], $GLOBALS['app']->_ItemsOnLayout)
									) {
										$GLOBALS['app']->_ItemsOnLayout[] = '_'.$gadget.$item['id'];
										if (!isset($item['preactivity'])) {
											$item['preactivity'] = '';
										}
										if (!isset($item['msg_key'])) {
											$item['msg_key'] = md5($item['title'].$item['email'].$item['msg_txt'].$item['ownerid'].$item['gadget_reference'].$item['gadget'].$item['parent']);
										}
										$item['activity'] = 'via '.$item['gadget'];
										if (!isset($this->_comments_owners[md5($item['preactivity'].$item['activity'].$item['gadget_reference'])])) {
											$this->_comments_owners[md5($item['preactivity'].$item['activity'].$item['gadget_reference'])] = array();
										}
										if (!in_array($item['ownerid'], $this->_comments_owners[md5($item['preactivity'].$item['activity'].$item['gadget_reference'])])) {
											$this->_comments_owners[md5($item['preactivity'].$item['activity'].$item['gadget_reference'])][] = $item['ownerid'];
										}
										$result[] = $item;
										$result_counter++;
										if ($result_counter > $result_max) {
											return $usersModel->GetSortedComments($result, $params);
										}
									}
								}
							}
						}
					}
					*/
					
					// Get all friends of user
					$friends = $jUser->GetFriendsOfUser($userInfo['id'], 'created', 'DESC', $result_limit);
					foreach ($friends as $friend) {
						$friendInfo = $jUser->GetUserInfoById((int)$friend['friend_id'], true, true, true, true);
						if (!Jaws_Error::IsError($friendInfo) && isset($friendInfo['id']) && !empty($friendInfo['id'])) {
							if (!in_array('_'.$gadget.'addusertofriend'.$userInfo['id'].$friendInfo['id'], $GLOBALS['app']->_ItemsOnLayout)) {
								$GLOBALS['app']->_ItemsOnLayout[] = '_'.$gadget.'addusertofriend'.$userInfo['id'].$friendInfo['id'];
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
												!in_array($group['group_name'], array('profile','no_profile','users')) && 
												strpos(strtolower($group['group_name']), '_owners') === false &&
												strpos(strtolower($group['group_name']), '_users') === false
											) {
												if (!in_array('_'.$gadget.'addusertogroup'.$friendInfo['id'].$group['group_id'], $GLOBALS['app']->_ItemsOnLayout)) {
													$GLOBALS['app']->_ItemsOnLayout[] = '_'.$gadget.'addusertogroup'.$friendInfo['id'].$group['group_id'];
													$group_name = (!empty($group['group_title']) ? $group['group_title'] : $group['group_name']);
													$safe_name = $xss->filter(preg_replace("[^A-Za-z0-9\ ]", '', $group_name));
													$image = $jUser->GetGroupAvatar($group['group_id']);
													$link = $GLOBALS['app']->Map->GetURLFor('Users', 'UserDirectory', array('Users_gid' => $group['group_id']));
													
													// Group's member count
													$group_count = '';
													$params = array();
													$params['group_id'] = $group['group_id'];
													$params['active'] = 'active';
													$params['admin'] = 'admin';
													$params['founder'] = 'founder';

													$sql = '
														SELECT COUNT([user_id])
														FROM [[users_groups]]
														WHERE
															[group_id] = {group_id}
															AND ([status] = {active} OR [status] = {admin} OR [status] = {founder})';

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
													
													$comment = '<p class="news-update news-addusertogroup-update" style="padding-top: 5px; text-align: left; display: block;" id="news-addusertogroup-update-'.$group['group_id'].'" align="left">
														<span class="news-comments"><span class="comment">
														<a title="'.$safe_name.'" name="'.$safe_name.'" href="'.$link.'">
														<img style="text-align: left; margin-right: 10px; margin-bottom: 10px; max-width: 80px; max-height: 80px;" class="news-update-icon news-addusertogroup-update-icon" id="news-addusertogroup-update-'.$group['group_id'].'-icon" src="'.$image.'" alt="'.$safe_name.'" align="left" />
														</a>
														<strong><a title="'.$safe_name.'" name="'.$safe_name.'" href="'.$link.'">'.$group_name.'</a></strong>
														<br />'.$group_name.' has '.$group_count.' members<span style="display: block; float: none; clear: both; font-size: 1px; height: 1px;">&nbsp;</span></span></span></p>';
																				
													$item = array(
														'id' => 'addusertogroup'.$friendInfo['id'].$group['group_id'],
														'name' => '',
														'email' => '',
														'url' => '',
														'title' => '',
														'msg_key' => md5(''.$user_email.$comment.$friendInfo['id'].$group['group_id'].'Groups'.(0)),
														'msg_txt' => $comment,
														'image' => '',
														'type' => 'addusertogroup',
														'sharing' => 'everyone',
														'status' => 'approved',
														'gadget' => 'Groups',
														'gadget_reference' => $group['group_id'],
														'parent' => 0,
														'replies' => false,
														'ownerid' => $friendInfo['id'],
														'targetid' => $group['group_id'],
														'preactivity' => 'is now a member of',
														'activity' => '',
														'createtime' => $group['group_updated']
													);
													if ($usersModel->IsCommentSharedWithUser($item, 'Users', $viewer_id, $public)) {
														if (!isset($this->_comments_owners[md5($item['preactivity'].$item['activity'].$item['gadget_reference'])])) {
															$this->_comments_owners[md5($item['preactivity'].$item['activity'].$item['gadget_reference'])] = array();
														}
														if (!in_array($item['ownerid'], $this->_comments_owners[md5($item['preactivity'].$item['activity'].$item['gadget_reference'])])) {
															$this->_comments_owners[md5($item['preactivity'].$item['activity'].$item['gadget_reference'])][] = $item['ownerid'];
														}
														$result[] = $item;
														$result_counter++;
														if ($result_counter > $result_max) {
															return $usersModel->GetSortedComments($result, $params);
														}
													}
												}
											}
										}
									}
									// Get all friends of friend
									$friendFriends = $jUser->GetFriendsOfUser($friendInfo['id'], 'created', 'DESC', $result_limit);
									foreach ($friendFriends as $friendFriend) {
										if (
											$friendFriend['friend_status'] == 'active' && (int)$friendFriend['friend_id'] != $viewer_id && 
											$jUser->GetStatusOfUserInFriend($viewer_id, (int)$friendFriend['friend_id']) != 'blocked'
										) {
											$friendFriendInfo = $jUser->GetUserInfoById((int)$friendFriend['friend_id'], true, true, true, true);
											if (!Jaws_Error::IsError($friendFriendInfo) && isset($friendFriendInfo['id']) && !empty($friendFriendInfo['id'])) {
												if (!in_array('_'.$gadget.'addusertofriend'.$friendInfo['id'].$friendFriendInfo['id'], $GLOBALS['app']->_ItemsOnLayout)) {
													$GLOBALS['app']->_ItemsOnLayout[] = '_'.$gadget.'addusertofriend'.$friendInfo['id'].$friendFriendInfo['id'];
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
											
													$friend_friend_comment = '<p class="news-update news-addusertofriend-update" style="padding-top: 5px; text-align: left; display: block;" id="news-addusertofriend-update-'.$friendInfo['id'].$friendFriendInfo['id'].'" align="left">
														<span class="news-comments"><span class="comment">
														'.$friendFriend_link_start.'
														<img style="text-align: left; margin-right: 10px; margin-bottom: 10px; max-width: 50px; max-height: 50px;" class="news-update-icon news-addusertofriend-update-icon" id="news-addusertofriend-update-'.$friendFriendInfo['id'].'-icon" src="'.$friendFriend_image.'" alt="'.preg_replace("[^A-Za-z0-9\ ]", '', $friendFriend_fullname).'" align="left" />
														'.$friendFriend_link_end.'
														<strong>'.$friendFriend_link_start.$friendFriend_fullname.$friendFriend_link_end.'</strong><span style="display: block; float: none; clear: both; font-size: 1px; height: 1px;">&nbsp;</span></span></span></p>';

													$item = array(
														'id' => 'addusertofriend'.$friendInfo['id'].$friendFriendInfo['id'],
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
														'ownerid' => $friendInfo['id'],
														'targetid' => $friendFriendInfo['id'],
														'preactivity' => 'is now friends with',
														'activity' => '',
														'createtime' => $friendFriend['friend_updated']
													);
													if ($usersModel->IsCommentSharedWithUser($item, 'Users', $viewer_id, $public)) {
														if (!isset($this->_comments_owners[md5($item['preactivity'].$item['activity'].$item['gadget_reference'])])) {
															$this->_comments_owners[md5($item['preactivity'].$item['activity'].$item['gadget_reference'])] = array();
														}
														if (!in_array($item['ownerid'], $this->_comments_owners[md5($item['preactivity'].$item['activity'].$item['gadget_reference'])])) {
															$this->_comments_owners[md5($item['preactivity'].$item['activity'].$item['gadget_reference'])][] = $item['ownerid'];
														}
														$result[] = $item;
														$result_counter++;
														if ($result_counter > $result_max) {
															return $usersModel->GetSortedComments($result, $params);
														}
													}
												}
											}
										}
									}
									$friend_comment = '<p class="news-update news-addusertofriend-update" style="padding-top: 5px; text-align: left; display: block;" id="news-addusertofriend-update-'.$userInfo['id'].$friendInfo['id'].'" align="left">
										<span class="news-comments"><span class="comment">
										'.$friend_link_start.'
										<img style="text-align: left; margin-right: 10px; margin-bottom: 10px; max-width: 50px; max-height: 50px;" class="news-update-icon news-addusertofriend-update-icon" id="news-addusertofriend-update-'.$friendInfo['id'].'-icon" src="'.$friend_image.'" alt="'.preg_replace("[^A-Za-z0-9\ ]", '', $friend_fullname).'" align="left" />
										'.$friend_link_end.'
										<strong>'.$friend_link_start.$friend_fullname.$friend_link_end.'</strong><span style="display: block; float: none; clear: both; font-size: 1px; height: 1px;">&nbsp;</span></span></span></p>';
									$preactivity = 'is now friends with';
								} else {
									$friend_comment = '<table border="0" width="100%" cellpadding="0" cellspacing="0"><tbody><tr><td valign="top" width="100%">
										<p classname="news-update news-addusertofriend-update" class="news-update news-addusertofriend-update" style="text-align: left; display: block;" id="news-addusertofriend-update-'.$userInfo['id'].$friendInfo['id'].'" align="left">
										'.$friend_link_start.'
										<img style="text-align: left; margin-right: 10px; margin-bottom: 10px; max-width: 50px; max-height: 50px;" class="news-update-icon news-addusertofriend-update-icon" id="news-addusertofriend-update-'.$friendInfo['id'].'-icon" src="'.$friend_image.'" alt="'.preg_replace("[^A-Za-z0-9\ ]", '', $friend_fullname).'" align="left" />
										'.$friend_link_end.'
										<strong>'.$friend_link_start.$friend_fullname.$friend_link_end.'</strong></p>
										</p></td>
										<td width="0%"><nobr><button title="Accept" name="accept" onclick="location.href=\''.$GLOBALS['app']->GetSiteURL().'/admin.php?gadget=Users&action=account_AuthUserFriend&friend_id='.$friendInfo['id'].'&user='.$userInfo['id'].'&status=active\';">Accept</button>&nbsp;
										<button title="Deny" name="deny" onclick="location.href=\''.$GLOBALS['app']->GetSiteURL().'/admin.php?gadget=Users&action=account_AuthUserFriend&friend_id='.$friendInfo['id'].'&user='.$userInfo['id'].'&status=denied\';">Deny</button></nobr>
										</td></tr></tbody></table>';
									$preactivity = 'has added you as a friend';
								}
								// Add this product as a comment
								$item = array(
									'id' => 'addusertofriend'.$userInfo['id'].$friendInfo['id'],
									'name' => '',
									'email' => '',
									'url' => '',
									'title' => '',
									'msg_key' => md5(''.$user_email.$friend_comment.$friendInfo['id'].$userInfo['id'].'Users'.(0)),
									'msg_txt' => $friend_comment,
									'image' => '',
									'type' => 'addusertofriend',
									'sharing' => 'everyone',
									'status' => 'approved',
									'gadget' => 'Users',
									'gadget_reference' => $userInfo['id'],
									'parent' => 0,
									'replies' => false,
									'ownerid' => $userInfo['id'],
									'targetid' => $friendInfo['id'],
									'preactivity' => $preactivity,
									'activity' => '',
									'createtime' => $friend['friend_updated']
								);
								if ($usersModel->IsCommentSharedWithUser($item, 'Users', $viewer_id, $public)) {
									if (!isset($this->_comments_owners[md5($item['preactivity'].$item['activity'].$item['gadget_reference'])])) {
										$this->_comments_owners[md5($item['preactivity'].$item['activity'].$item['gadget_reference'])] = array();
									}
									if (!in_array($item['ownerid'], $this->_comments_owners[md5($item['preactivity'].$item['activity'].$item['gadget_reference'])])) {
										$this->_comments_owners[md5($item['preactivity'].$item['activity'].$item['gadget_reference'])][] = $item['ownerid'];
									}
									$result[] = $item;
									$result_counter++;
									if ($result_counter > $result_max) {
										return $usersModel->GetSortedComments($result, $params);
									}
								}
							}
						}
					}
				}
			}
		}
		
		return $usersModel->GetSortedComments($result, $params);
    }
    	
    /**
     * Returns an array with all comments relating to given Group
     *
     * @access  public
     */
    function GetGroupComments($params = array())
    {
		$OwnerID = $params['uid'];
		$viewer_id = (int)$GLOBALS['app']->Session->GetAttribute('user_id');
		$id = $params['gadget_reference'];
		$gadget = $params['gadget'];
		$public = $params['public'];
		$result_limit = (int)$params['limit'];
		$result_max = (int)$params['max'];
		$layout = strtolower($params['layout']);
		if (!in_array($layout, array('full', 'tiles', 'boxy'))) {
			$layout = 'full';
		}
        $result_counter = 0;
        $result_offset = 0;
        $result = array();
		require_once JAWS_PATH . 'include/Jaws/Comment.php';
        $api = new Jaws_Comment('Users');
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		require_once JAWS_PATH . 'include/Jaws/Image.php';
		$usersModel = $GLOBALS['app']->loadGadget('Users', 'Model');
		require_once JAWS_PATH . 'include/Jaws/User.php';
		$jUser = new Jaws_User;
		
		if (!is_null($id)) {			
			// Get requested group
			$groupInfo = $jUser->GetGroupInfoById((int)$id);
			if (!Jaws_Error::IsError($groupInfo) && isset($groupInfo['id']) && !empty($groupInfo['id'])) {
				if ($layout != 'tiles') {
					// Comments shared with group
					$group_comments = $usersModel->GetCommentsSharedWithGroup((int)$id, 'Users', 'approved', $result_limit);
					foreach ($group_comments as $item) {
						if (
							$usersModel->IsCommentSharedWithUser($item, 'Users', $viewer_id, $public) && 
							!in_array('_'.$gadget.$item['id'], $GLOBALS['app']->_ItemsOnLayout)
						) {
							$GLOBALS['app']->_ItemsOnLayout[] = '_'.$gadget.$item['id'];
							if (!isset($item['preactivity'])) {
								// Shared with specific group? Show it...
								$share_activity = $usersModel->GetCommentShareActivity('groups:'.$id);
								$item['preactivity'] = $share_activity;
							}
							if (!isset($item['msg_key'])) {
								$item['msg_key'] = md5($item['title'].$item['email'].$item['msg_txt'].$item['ownerid'].$item['gadget_reference'].$item['gadget'].$item['parent']);
							}
							$item['activity'] = '';
							if (!isset($this->_comments_owners[md5($item['preactivity'].$item['activity'].$item['gadget_reference'])])) {
								$this->_comments_owners[md5($item['preactivity'].$item['activity'].$item['gadget_reference'])] = array();
							}
							if (!in_array($item['ownerid'], $this->_comments_owners[md5($item['preactivity'].$item['activity'].$item['gadget_reference'])])) {
								$this->_comments_owners[md5($item['preactivity'].$item['activity'].$item['gadget_reference'])][] = $item['ownerid'];
							}
							$result[] = $item;
							$result_counter++;
							if ($result_counter > $result_max) {
								//return $usersModel->GetSortedComments($result, $params);
								return $this->GetAllOtherGroupComments($groupInfo['id'], $result, $params);
							}
						}
					}
				}
				
				// Get all users of group
				$group_count = $jUser->GetTotalOfUsersOfGroupByStatus($groupInfo['id'], array('active', 'admin', 'founder'));
				$random = rand(0, $group_count);
				if ($random > ($result_limit+1)) {
					$random = ($random - ($result_limit+1));
				}
				if (!Jaws_Error::IsError($group_count)) {
					$group_users = $jUser->GetUsersOfGroupByStatus($groupInfo['id'], array('active', 'admin', 'founder'), $result_limit+1, $random);
					foreach ($group_users as $user) {
					//if ($user['status'] != 'denied') { 
						$userInfo = $jUser->GetUserInfoById($user['user_id'], true, true, true, true);
						if (!Jaws_Error::IsError($userInfo) && isset($userInfo['id']) && !empty($userInfo['id'])) {
							if (!empty($userInfo['company'])) {
								$user_name = (strlen($xss->filter(strip_tags(htmlspecialchars_decode($userInfo['company'], ENT_QUOTES)))) > 100 ? substr($xss->filter(strip_tags(htmlspecialchars_decode($userInfo['company'], ENT_QUOTES))), 0, 100).'...' : $xss->filter(strip_tags(htmlspecialchars_decode($userInfo['company'], ENT_QUOTES))));
								$user_fullname = $xss->filter(strip_tags(htmlspecialchars_decode($userInfo['company'], ENT_QUOTES)));
							} else {
								$user_name = (strlen($xss->filter(strip_tags(htmlspecialchars_decode($userInfo['nickname'], ENT_QUOTES)))) > 100 ? substr($xss->filter(strip_tags(htmlspecialchars_decode($userInfo['nickname'], ENT_QUOTES))), 0, 100).'...' : $xss->filter(strip_tags(htmlspecialchars_decode($userInfo['nickname'], ENT_QUOTES))));
								$user_fullname = $xss->filter(strip_tags(htmlspecialchars_decode($userInfo['nickname'], ENT_QUOTES)));
							}
							
							$user_image = $jUser->GetAvatar($userInfo['username'], $userInfo['email']);
							$user_email = $userInfo['email'];
							
							// Check if user is in profile group
							$show_link = false;
							if (in_array($jUser->GetStatusOfUserInGroup($userInfo['id'], 'profile'), array('active','founder','admin'))) {
								$show_link = true;
							}

							$user_link = '';
							$user_link_start = '';
							$user_link_end = '';
							if ($show_link === true) {
								$user_link = $GLOBALS['app']->Map->GetURLFor('Users', 'AccountPublic', array('name' => $userInfo['username']));
								$user_link_start = '<a href="'.$GLOBALS['app']->GetSiteURL().'/'.$GLOBALS['app']->Map->GetURLFor('Users', 'AccountPublic', array('name' => $userInfo['username'])).'" title="'.preg_replace("[^A-Za-z0-9\ ]", '', $user_fullname).'" name="'.preg_replace("[^A-Za-z0-9\ ]", '', $user_fullname).'">';
								$user_link_end = '</a>';
							}
					
							//if (in_array($user['status'], array('active','admin','founder'))) { 
								if ($layout != 'tiles') {
									// Comments owned by user
									$owned_comments = $usersModel->GetCommentsFiltered('Users', 'ownerid', $userInfo['id'], 'approved', $result_limit);
									foreach ($owned_comments as $item) {
										if (
											$usersModel->IsCommentSharedWithUser($item, 'Users', $viewer_id, $public) && 
											!in_array('_'.$gadget.$item['id'], $GLOBALS['app']->_ItemsOnLayout)
										) {
											$GLOBALS['app']->_ItemsOnLayout[] = '_'.$gadget.$item['id'];
											if (!isset($item['preactivity'])) {
												// Shared with specific users? Show them...
												$share_activity = $usersModel->GetCommentShareActivity($item['sharing']);
												$item['preactivity'] = $share_activity;
											}
											if (!isset($item['msg_key'])) {
												$item['msg_key'] = md5($item['title'].$item['email'].$item['msg_txt'].$item['ownerid'].$item['gadget_reference'].$item['gadget'].$item['parent']);
											}
											$item['activity'] = '';
											if (!isset($this->_comments_owners[md5($item['preactivity'].$item['activity'].$item['gadget_reference'])])) {
												$this->_comments_owners[md5($item['preactivity'].$item['activity'].$item['gadget_reference'])] = array();
											}
											if (!in_array($item['ownerid'], $this->_comments_owners[md5($item['preactivity'].$item['activity'].$item['gadget_reference'])])) {
												$this->_comments_owners[md5($item['preactivity'].$item['activity'].$item['gadget_reference'])][] = $item['ownerid'];
											}
											$result[] = $item;
											$result_counter++;
											if ($result_counter > $result_max) {
												//return $usersModel->GetSortedComments($result, $params);
												return $this->GetAllOtherGroupComments($groupInfo['id'], $result, $params);
											}
										}
									}
								}
								
								// Added to this group
								if ($layout == 'tiles' && !empty($user_link) && strpos(strtolower($user_image), 'default_avatar.png') === false && !in_array('_'.$gadget.'addusertogroup'.$userInfo['id'].$groupInfo['id'], $GLOBALS['app']->_ItemsOnLayout)) {
									$group_name = (!empty($groupInfo['title']) ? $groupInfo['title'] : $groupInfo['name']);
									$safe_name = $xss->filter(preg_replace("[^A-Za-z0-9\ ]", '', $group_name));
									$image = $jUser->GetGroupAvatar($groupInfo['id']);
									$link = $GLOBALS['app']->Map->GetURLFor('Users', 'UserDirectory', array('Users_gid' => $groupInfo['id']));
														
									// Group's CustomPage
									if (Jaws_Gadget::IsGadgetUpdated('CustomPage')) {
										$pageModel = $GLOBALS['app']->LoadGadget('CustomPage', 'Model');
										$pageHTML = $GLOBALS['app']->LoadGadget('CustomPage', 'HTML');
										$page = $pageModel->GetGroupHomePage($groupInfo['id']);
										if (!Jaws_Error::IsError($page) && isset($page['id'])) {
											$link = $GLOBALS['app']->Map->GetURLFor('Users', 'GroupPage', array('group' => $groupInfo['name']));
										}
									}
									
									$comment = '<p class="news-update news-addusertogroup-update" style="padding-top: 5px; text-align: left; display: block;" id="news-addusertogroup-update-'.$groupInfo['id'].'" align="left">
										<span class="news-comments"><span class="comment">
										<a title="'.$safe_name.'" name="'.$safe_name.'" href="'.$link.'">
										<img style="text-align: left; margin-right: 10px; margin-bottom: 10px; max-width: 80px; max-height: 80px;" class="news-update-icon news-addusertogroup-update-icon" id="news-addusertogroup-update-'.$groupInfo['id'].'-icon" src="'.$image.'" alt="'.$safe_name.'" align="left" />
										</a>
										<strong><a title="'.$safe_name.'" name="'.$safe_name.'" href="'.$link.'">'.$group_name.'</a></strong>
										<br />'.$group_name.' has '.$group_count.' members<span style="display: block; float: none; clear: both; font-size: 1px; height: 1px;">&nbsp;</span></span></span></p>';
									
									$preactivity = 'is now a member of';
									
									// Add this product as a comment
									$item = array(
										'id' => 'addusertogroup'.$userInfo['id'].$groupInfo['id'],
										'name' => '',
										'email' => '',
										'url' => '',
										'title' => '',
										'msg_key' => md5(''.$user_email.$comment.$userInfo['id'].$groupInfo['id'].'Groups'.(0)),
										'msg_txt' => $comment,
										'image' => '',
										'type' => 'addusertogroup',
										'sharing' => 'everyone',
										'status' => 'approved',
										'gadget' => 'Groups',
										'gadget_reference' => $groupInfo['id'],
										'parent' => 0,
										'replies' => false,
										'ownerid' => $userInfo['id'],
										'targetid' => $groupInfo['id'],
										'preactivity' => $preactivity,
										'activity' => '',
										'createtime' => (!is_null($user['updated']) && !empty($user['updated']) ? $user['updated'] : $GLOBALS['db']->Date())
									);
									if ($usersModel->IsCommentSharedWithUser($item, 'Users', $viewer_id, $public)) {
										$GLOBALS['app']->_ItemsOnLayout[] = '_'.$gadget.'addusertogroup'.$userInfo['id'].$groupInfo['id'];
										if (!isset($this->_comments_owners[md5($item['preactivity'].$item['activity'].$item['gadget_reference'])])) {
											$this->_comments_owners[md5($item['preactivity'].$item['activity'].$item['gadget_reference'])] = array();
										}
										if (!in_array($item['ownerid'], $this->_comments_owners[md5($item['preactivity'].$item['activity'].$item['gadget_reference'])])) {
											$this->_comments_owners[md5($item['preactivity'].$item['activity'].$item['gadget_reference'])][] = $item['ownerid'];
										}
										$result[] = $item;
										$result_counter++;
										if ($result_counter > $result_max) {
											//return $usersModel->GetSortedComments($result, $params);
											return $this->GetAllOtherGroupComments($groupInfo['id'], $result, $params);
										}
									}
								}
							//}
						}
					}
				}
			}
		}
		return $this->GetAllOtherGroupComments((int)$id, $result, $params);
    }

    /**
     * Returns an array with all Group comments
     *
     * @access  public
     */
    function GetAllOtherGroupComments($group_id, $result, $params = array())
    {
		$result_limit = (int)$params['limit'];
		$result_max = (int)$params['max'];
		$layout = $params['layout'];
		$gadget = $params['gadget'];
		
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$usersModel = $GLOBALS['app']->loadGadget('Users', 'Model');

		// Load comments of all gadgets
		$jms = $GLOBALS['app']->LoadGadget('Jms', 'AdminModel');
		$gadget_list = $jms->GetGadgetsList(null, true, true, true);
		if (count($gadget_list) <= 0) {
			Jaws_Error::Fatal('You don\'t have any installed gadgets, please enable/install one and then come back',
							 __FILE__, __LINE__);
		}
		reset($gadget_list);
		$result_total = count($result);
		$last_result_total = $result_total;
		// Get all other gadget comments of group
			foreach ($gadget_list as $g) {
				if ($g['realname'] != 'Users') {
					/*
					// Log start time (microseconds)
					$mtime = microtime();
					$mtime = explode(' ', $mtime);
					$mtime = (double) $mtime[0] + $mtime[1];
					$tstart = $mtime;
					*/
					$hook = $GLOBALS['app']->loadHook($g['realname'], 'Comment');
					if ($hook !== false) {
						if (method_exists($hook, 'GetGroupComments')) {
							$result_counter = 0;
							$gadgetOwned = $hook->GetGroupComments(
								array(
								'gadget_reference' => $group_id, 
								'gadget' => $gadget, 
								'uid' => null, 
								'limit' => $result_limit, 
								'max' => $result_max, 
								'public' => true,
								'layout' => $layout
								)
							);
							if (!Jaws_Error::IsError($gadgetOwned) && is_array($gadgetOwned)) {
								foreach ($gadgetOwned as $gOwned) {
									/*
									if (count($new_comments_data) <= 0 && strtolower($gOwned['createtime']) > $last_login) {
										$new_comments[] = $gOwned;
									}
									*/
									$result[] = $gOwned;
									$result_counter++;
									$result_total++;
									if ($result_counter > $result_max) {
										//return $usersModel->GetSortedComments($result, $params);
										break;
									}
								}
							}
						}
					}
					/*
					// Log generation time
					$mtime = microtime();
					$mtime = explode(' ', $mtime);
					$mtime = $mtime[1] + $mtime[0];
					$tend  = $mtime;
					$totaltime = ($tend - $tstart);
					var_dump($g['realname'].' was generated in '. $totaltime . ' seconds');
					*/
				}
			}
		if ($last_result_total != $result_total && $result_total < 8) {
			return $this->GetAllOtherGroupComments($group_id, $result, $params);
		}
		return $usersModel->GetSortedComments($result, $params);
	}
	
    /**
     * Returns an array with all Group requests
     *
     * @access  public
     */
    function GetGroupRequests($params = array())
    {
		$OwnerID = $params['uid'];
		$viewer_id = (int)$GLOBALS['app']->Session->GetAttribute('user_id');
		$id = $params['gadget_reference'];
		$gadget = $params['gadget'];
		$public = $params['public'];
		$result_limit = (int)$params['limit'];
		$result_max = (int)$params['max'];
        $result_counter = 0;
        $result_offset = 0;
        $result = array();
		require_once JAWS_PATH . 'include/Jaws/Comment.php';
        $api = new Jaws_Comment('Users');
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		require_once JAWS_PATH . 'include/Jaws/Image.php';
		$usersModel = $GLOBALS['app']->loadGadget('Users', 'Model');
		require_once JAWS_PATH . 'include/Jaws/User.php';
		$jUser = new Jaws_User;
		if (!isset($GLOBALS['app']->ACL)) {
			$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		}
		
		if (!is_null($id)) {			
			// Get requested group
			$groupInfo = $jUser->GetGroupInfoById((int)$id);
			if (!Jaws_Error::IsError($groupInfo) && isset($groupInfo['id']) && !empty($groupInfo['id'])) {
				// Get all users of group
				$group_users = $jUser->GetUsersOfGroup($groupInfo['id']);
				foreach ($group_users as $user) {
					if (
						$user['group_status'] == 'request' &&   
						(in_array($jUser->GetStatusOfUserInGroup($viewer_id, $groupInfo['id']), array('founder','admin')) ||     
						$GLOBALS['app']->ACL->GetFullPermission(
							$GLOBALS['app']->Session->GetAttribute('username'), 
							$GLOBALS['app']->Session->GetAttribute('groups'), 'Users', 'ManageGroups'))
					) {
						$userInfo = $jUser->GetUserInfoById($user['user_id'], true, true, true, true);
						if (!Jaws_Error::IsError($userInfo) && isset($userInfo['id']) && !empty($userInfo['id'])) {
							if (!empty($userInfo['company'])) {
								$user_name = (strlen($xss->filter(strip_tags(htmlspecialchars_decode($userInfo['company'], ENT_QUOTES)))) > 100 ? substr($xss->filter(strip_tags(htmlspecialchars_decode($userInfo['company'], ENT_QUOTES))), 0, 100).'...' : $xss->filter(strip_tags(htmlspecialchars_decode($userInfo['company'], ENT_QUOTES))));
								$user_fullname = $xss->filter(strip_tags(htmlspecialchars_decode($userInfo['company'], ENT_QUOTES)));
							} else {
								$user_name = (strlen($xss->filter(strip_tags(htmlspecialchars_decode($userInfo['nickname'], ENT_QUOTES)))) > 100 ? substr($xss->filter(strip_tags(htmlspecialchars_decode($userInfo['nickname'], ENT_QUOTES))), 0, 100).'...' : $xss->filter(strip_tags(htmlspecialchars_decode($userInfo['nickname'], ENT_QUOTES))));
								$user_fullname = $xss->filter(strip_tags(htmlspecialchars_decode($userInfo['nickname'], ENT_QUOTES)));
							}
							
							$user_image = $jUser->GetAvatar($userInfo['username'], $userInfo['email']);
							$user_email = $userInfo['email'];
							$groups  = $jUser->GetGroupsOfUser($userInfo['id']);
							
							// Check if user is in profile group
							$show_link = false;
							if (!Jaws_Error::IsError($groups)) {
								foreach ($groups as $group) {
									if (
										strtolower($group['group_name']) == 'profile' && 
										in_array($group['group_status'], array('active','founder','admin'))
									) {
										$show_link = true;
										break;
									}
								}
							}
							$user_link = '';
							$user_link_start = '';
							$user_link_end = '';
							if ($show_link === true) {
								$user_link = $GLOBALS['app']->Map->GetURLFor('Users', 'AccountPublic', array('name' => $userInfo['username']));
								$user_link_start = '<a href="'.$GLOBALS['app']->GetSiteURL().'/'.$GLOBALS['app']->Map->GetURLFor('Users', 'AccountPublic', array('name' => $userInfo['username'])).'" title="'.preg_replace("[^A-Za-z0-9\ ]", '', $user_fullname).'" name="'.preg_replace("[^A-Za-z0-9\ ]", '', $user_fullname).'">';
								$user_link_end = '</a>';
							}
																		
							// Added to this group
							if (!in_array('_'.$gadget.'addusertogroup'.$userInfo['id'].$groupInfo['id'], $GLOBALS['app']->_ItemsOnLayout)) {
								$group_name = (!empty($groupInfo['title']) ? $groupInfo['title'] : $groupInfo['name']);
								$safe_name = $xss->filter(preg_replace("[^A-Za-z0-9\ ]", '', $group_name));
								$image = $jUser->GetGroupAvatar($groupInfo['id']);
								$link = $GLOBALS['app']->Map->GetURLFor('Users', 'UserDirectory', array('Users_gid' => $groupInfo['id']));
													
								// Group's member count
								$group_count = '0';
								$params = array();
								$params['group_id'] = $groupInfo['id'];
								$params['active'] = 'active';
								$params['admin'] = 'admin';
								$params['founder'] = 'founder';

								$sql = '
									SELECT COUNT([user_id])
									FROM [[users_groups]]
									WHERE
										[group_id] = {group_id}
										AND ([status] = {active} OR [status] = {admin} OR [status] = {founder})';

								$howmany = $GLOBALS['db']->queryOne($sql, $params);
								if (!Jaws_Error::IsError($howmany) && (int)$howmany > 0) {
									$group_count = $howmany;
								}
								
								// Group's CustomPage
								if (Jaws_Gadget::IsGadgetUpdated('CustomPage')) {
									$pageModel = $GLOBALS['app']->LoadGadget('CustomPage', 'Model');
									$pageHTML = $GLOBALS['app']->LoadGadget('CustomPage', 'HTML');
									$page = $pageModel->GetGroupHomePage($groupInfo['id']);
									if (!Jaws_Error::IsError($page) && isset($page['id'])) {
										$link = $GLOBALS['app']->Map->GetURLFor('Users', 'GroupPage', array('group' => $groupInfo['name']));
									}
								}
								
								$comment = '<table border="0" width="100%" cellpadding="0" cellspacing="0"><tbody><tr><td valign="top" width="100%">
									<p classname="news-update news-addusertogroup-update" class="news-update news-addusertogroup-update" style="text-align: left; display: block;" id="news-addusertogroup-update-'.$userInfo['id'].$group['group_id'].'" align="left">
									<a title="'.$safe_name.'" name="'.$safe_name.'" href="'.$link.'">
									<img style="text-align: left; margin-right: 10px; margin-bottom: 10px; max-width: 80px; max-height: 80px;" class="news-update-icon news-addusertogroup-update-icon" id="news-addusertogroup-update-'.$group['group_id'].'-icon" src="'.$image.'" alt="'.$safe_name.'" align="left" />
									</a>
									<strong><a title="'.$safe_name.'" name="'.$safe_name.'" href="'.$link.'">'.$group_name.'</a></strong>
									<br />'.$group_count.' members</p></td>
									<td width="0%"><nobr><button title="Accept" name="accept" onclick="location.href=\''.$GLOBALS['app']->GetSiteURL().'/admin.php?gadget=Users&action=AuthUserGroup&group='.$group['group_id'].'&user='.$userInfo['id'].'&status=active\';">Accept</button>&nbsp;
									<button title="Deny" name="deny" onclick="location.href=\''.$GLOBALS['app']->GetSiteURL().'/admin.php?gadget=Users&action=AuthUserGroup&group='.$group['group_id'].'&user='.$userInfo['id'].'&status=denied\';">Deny</button></nobr>
									</td></tr></tbody></table>';
								$preactivity = 'is requesting access to the group';
								
								// Add this product as a comment
								$item = array(
									'id' => 'addusertogroup'.$userInfo['id'].$groupInfo['id'],
									'name' => '',
									'email' => '',
									'url' => '',
									'title' => '',
									'msg_key' => md5(''.$user_email.$comment.$userInfo['id'].$groupInfo['id'].'Groups'.(0)),
									'msg_txt' => $comment,
									'image' => '',
									'type' => 'addusertogroup',
									'sharing' => 'everyone',
									'status' => 'approved',
									'gadget' => 'Groups',
									'gadget_reference' => $groupInfo['id'],
									'parent' => 0,
									'replies' => false,
									'ownerid' => $userInfo['id'],
									'targetid' => $groupInfo['id'],
									'preactivity' => $preactivity,
									'activity' => '',
									'createtime' => (!is_null($user['group_updated']) ? $user['group_updated'] : $GLOBALS['db']->Date())
								);
								if ($usersModel->IsCommentSharedWithUser($item, 'Users', $viewer_id, $public)) {
									$GLOBALS['app']->_ItemsOnLayout[] = '_'.$gadget.'addusertogroup'.$userInfo['id'].$groupInfo['id'];
									if (!isset($this->_comments_owners[md5($item['preactivity'].$item['activity'].$item['gadget_reference'])])) {
										$this->_comments_owners[md5($item['preactivity'].$item['activity'].$item['gadget_reference'])] = array();
									}
									if (!in_array($item['ownerid'], $this->_comments_owners[md5($item['preactivity'].$item['activity'].$item['gadget_reference'])])) {
										$this->_comments_owners[md5($item['preactivity'].$item['activity'].$item['gadget_reference'])][] = $item['ownerid'];
									}
									$result[] = $item;
									$result_counter++;
									if ($result_counter > $result_max) {
										return $usersModel->GetSortedComments($result, $params);
									}
								}
							}
						}
					}
				}
			}
		}
		return $usersModel->GetSortedComments($result, $params);
    }
	
	
    /**
     * Returns an array of single comment thread
     *
     * @access  public
     */
    function GetUsersComment($params = array())
    {
		$id = $params['gadget_reference'];
		$public = $params['public'];
		$viewer_id = (int)$GLOBALS['app']->Session->GetAttribute('user_id');

		require_once JAWS_PATH . 'include/Jaws/Comment.php';
        $api = new Jaws_Comment('Users');
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		
        $result = array();
		
		if (!is_null($id)) {						
			$GLOBALS['app']->Registry->LoadFile('Users');
			$GLOBALS['app']->Translate->LoadTranslation('Users', JAWS_GADGET);
			require_once JAWS_PATH . 'include/Jaws/Image.php';
			$jUser = new Jaws_User;
			$usersModel = $GLOBALS['app']->loadGadget('Users', 'Model');
			
			/*
			$user_name = $GLOBALS['app']->Registry->Get('/config/site_name');
			$user_link = $GLOBALS['app']->GetSiteURL();
			$user_link_start = '<a href="'.$user_link.'" title="'.$xss->filter(preg_replace("[^A-Za-z0-9\ ]", '', $user_name)).'" name="'.$xss->filter(preg_replace("[^A-Za-z0-9\ ]", '', $user_name)).'">';
			$user_link_end = '</a>';
			if (empty($user_name)) {
				$user_name = str_replace(array('http://', 'https://'), '', $user_link);
			}
			$user_email = $GLOBALS['app']->Registry->Get('/network/site_email');
			$user_image = $jUser->GetAvatar();
			if (file_exists(JAWS_DATA . 'files/css/logo.png')) {
				$user_image = $GLOBALS['app']->GetDataURL('files/css/logo.png', true);
			}
			*/
			$item = $api->GetComment($id);
			if (Jaws_Error::IsError($item)) {
				//return array();
			} else {
				if (!isset($item['preactivity'])) {
					// Shared with specific users? Show them...
					$share_activity = $usersModel->GetCommentShareActivity($item['sharing']);
					$item['preactivity'] = $share_activity;
				}
				if (!isset($item['msg_key'])) {
					$item['msg_key'] = md5($item['title'].$item['email'].$item['msg_txt'].$item['ownerid'].$item['gadget_reference'].$item['gadget'].$item['parent']);
				}
				$item['activity'] = '';
				
				// Add this product as a comment
				/*
				$edit_links = array();
				if ((int)$p['ownerid'] > 0 && (int)$p['ownerid'] == (int)$GLOBALS['app']->Session->GetAttribute('user_id')) {
					$edit_links = array(
						0 => array(
							'url' => $GLOBALS['app']->GetSiteURL('', false, 'http')."/index.php?gadget=Store&action=account_A_form&id=".$p['id'],
							'title' => "Edit this Product"
						),
						1 => array(
							'url' => "javascript: if (confirm(confirmCommentDelete)){location.href='".$GLOBALS['app']->GetSiteURL('', false, 'http')."/index.php?gadget=Store&action=account_form_post&fuseaction=DeleteProduct&id=".$p['id']."';};",
							'title' => "Delete this Product"
						),
						2 => array(
							'url' => $product_url,
							'title' => "View this Product"
						)
					);
				}
				*/
				$result = $item;
				if (!isset($this->_comments_owners[md5($result['preactivity'].$result['activity'].$result['gadget_reference'])])) {
					$this->_comments_owners[md5($result['preactivity'].$result['activity'].$result['gadget_reference'])] = array();
				}
				if (!in_array($result['ownerid'], $this->_comments_owners[md5($result['preactivity'].$result['activity'].$result['gadget_reference'])])) {
					$this->_comments_owners[md5($result['preactivity'].$result['activity'].$result['gadget_reference'])][] = $result['ownerid'];
				}
			}
		}
		return $result;
	}
	
    /**
     * Returns an HTML string of comment sorting options
     *
     * @access  public
     */
    function GetCommentsTitleOptions($params = array())
    {
		$OwnerID = $params['uid'];
		$public = $params['public'];
		$title = $params['title'];
        $layout = $params['layout'];
		if ($layout == 'boxy') {
			$namespace_single = 'box';
			$namespace = 'boxes';
		} else if ($layout == 'tiles') {
			$namespace_single = 'tile';
			$namespace = 'tiles';
		} else {
			$namespace_single = '';
			$namespace = 'news';
		}
		$result = '';
		$tpl = new Jaws_Template('gadgets/Users/templates/');
		$tpl->Load('SortingComments.html');
		if ($public === false) {
			$tpl->SetBlock('private');
			$tpl->SetVariable('namespace_capitalized', ucfirst($namespace));
			$tpl->SetVariable('namespace', $namespace);
			$tpl->SetVariable('title', $title);
			$tpl->SetVariable('gadget', 'Users');
			$tpl->ParseBlock('private');
		} else {
			$tpl->SetBlock('public');
			$tpl->SetVariable('namespace_capitalized', ucfirst($namespace));
			$tpl->SetVariable('namespace', $namespace);
			$tpl->SetVariable('title', $title);
			$tpl->SetVariable('gadget', 'Users');
			$tpl->ParseBlock('public');
		}
		$result = $tpl->Get();
		return $result;
    }
}
