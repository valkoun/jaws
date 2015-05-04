<?php
/**
 * Store - Comment gadget hook
 *
 * @category   GadgetHook
 * @package    Store
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2011 Alan Valkoun
 * @TODO	 Add Category, Sale, Brand, Attribute, AttributeType message types
 */
class StoreCommentHook
{
	var $_comments_owners = array();
	
    /**
     * Returns an array with all comments in the Store gadget by given criteria
     *
     * @access  public
     */
    function GetComments($params = array())
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
        $res = array();
        $result = array();
		require_once JAWS_PATH . 'include/Jaws/Comment.php';
        $api = new Jaws_Comment('Store');
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		
		//Load model
		$usersModel = $GLOBALS['app']->loadGadget('Users', 'Model');
		$model = $GLOBALS['app']->loadGadget('Store', 'Model');
		
		if (!is_null($id)) {
			$gadget_comments = $usersModel->GetCommentsFiltered('Store', 'postid', $id, 'approved', $result_limit);
			foreach ($gadget_comments as $item) {
				if (
					$usersModel->IsCommentSharedWithUser($item, 'Store', $viewer_id, $public) && 
					!in_array('_'.$gadget.$item['id'], $GLOBALS['app']->_ItemsOnLayout)
				) {
					$GLOBALS['app']->_ItemsOnLayout[] = '_'.$gadget.$item['id'];
					if (!isset($item['preactivity'])) {
						$item['preactivity'] = '';
					}
					$item['activity'] = '<img border="0" align="top" style="height: 16px; width: 16px; padding-top: 2px" src="'.$GLOBALS['app']->GetJawsURL().'/gadgets/'.$item['gadget'].'/images/logo.png"> via '.$item['gadget'];
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
		} else if (!is_null($OwnerID)) {
			require_once JAWS_PATH . 'include/Jaws/User.php';
			require_once JAWS_PATH . 'include/Jaws/Image.php';
			$jUser = new Jaws_User;
			$userInfo = $jUser->GetUserInfoById((int)$OwnerID, true, true, true, true);
			if (!Jaws_Error::IsError($userInfo) && isset($userInfo['id']) && !empty($userInfo['id'])) {
				// Get products of user
				$pages = $model->GetStoreOfUserID($userInfo['id'], ($public === true ? 'Y' : null), 'created', 'DESC', $result_limit);
				if (!Jaws_Error::IsError($pages)) {
					foreach($pages as $p) {
						$res[] = $p;
					}
				}

				// Get Store comments that user made
				$filtered_comments = $usersModel->GetCommentsFiltered('Store', 'ownerid', $userInfo['id'], 'approved', $result_limit);
				$filtered_product_comments = 0;
				if (!Jaws_Error::isError($filtered_comments) && !count($filtered_comments) <= 0) {
					reset($filtered_comments);
					$filtered_product_comments = array();
					foreach ($filtered_comments as $filtered_comment) {
						// Get product details
						$page = $model->GetProduct((int)$filtered_comment['gadget_reference']);
						if (!Jaws_Error::IsError($page) && isset($page['id']) && !empty($page['id'])) {
							$item = $this->GetStoreComment(array('gadget_reference' => $page['id'], 'public' => $public));

							$safe_title = $xss->filter(preg_replace("[^A-Za-z0-9\ ]", '', $page['title']));
							$product_url = $GLOBALS['app']->GetSiteURL() .'/'. $GLOBALS['app']->Map->GetURLFor('Store', 'Product', array('id' => $page['fast_url']));
							
							if (isset($item['url']) && !empty($item['url'])) {
								$owner_link = $item['url'];
								$owner_link_start = '<a href="'.$owner_link.'" title="'.preg_replace("[^A-Za-z0-9\ ]", '', $item['name']).'" name="'.preg_replace("[^A-Za-z0-9\ ]", '', $item['name']).'">';
								$owner_link_end = '</a>';
							}
							
							$filtered_comment['type'] = 'auto';
							$filtered_comment['ownerid'] = (int)$filtered_comment['ownerid'];
							$filtered_comment['targetid'] = $item['ownerid'];
							$owner_link_string = " ".$owner_link_start.$item['name'].$owner_link_end.($item['name'] != 'their own' ? '\'s' : '');					
							$filtered_comment['preactivity'] = 'commented on'.$owner_link_string.' <a href="'.$product_url.'" title="'.$safe_title.'" name="'.$safe_title.'">Store item</a>';
							$filtered_comment['activity'] = $item['activity'];
							$filtered_product_comments[] = $filtered_comment;
							$replies = $usersModel->GetCommentsOfParent($filtered_comment['id'], 'approved', '');
							if (Jaws_Error::IsError($replies)) {
								//return array();
							} else {
								foreach ($replies as $reply) {
									$filtered_product_comments[] = $reply;
								}
							}
						}
					}
				}

				$reply_comments = $filtered_product_comments;
				if (is_array($filtered_product_comments)) {
					foreach ($filtered_product_comments as $fcomment) {
						if ($fcomment['parent'] == 0) {
							$item = $this->GetStoreComment(array('gadget_reference' => $fcomment['gadget_reference'], 'public' => $public));
							if (!in_array('_'.$gadget.'comment'.$fcomment['gadget_reference'], $GLOBALS['app']->_ItemsOnLayout)) {
								$new_comment = array(
									'id' => 'comment'.$fcomment['gadget_reference'],
									'parent' => $fcomment['parent'],
									'name' => $item['name'],
									'email' => $item['email'],
									'url' => $item['url'],
									'title' => '',
									'msg_key' => md5(''.$item['email'].$item['msg_txt'].$fcomment['ownerid'].$fcomment['gadget_reference'].$fcomment['parent']),
									'msg_txt' => $item['msg_txt'],
									'image' => '',
									'type' => 'product',
									'sharing' => $fcomment['sharing'],
									'status' => 'approved',
									'gadget' => 'Store',
									'gadget_reference' => $fcomment['gadget_reference'],
									'replies' => $item['replies'],
									'ownerid' => $fcomment['ownerid'],
									'targetid' => $fcomment['targetid'],
									'preactivity' => $fcomment['preactivity'],
									'activity' => $item['activity'],
									'permalink' => $GLOBALS['app']->GetSiteURL() . '/' . $GLOBALS['app']->Map->GetURLFor('Users', 'ShowComment', array('id' => $fcomment['id'], 'fusegadget' => $fcomment['gadget'])),
									'createtime' => $fcomment['createtime']
								);
								if ($usersModel->IsCommentSharedWithUser($new_comment, 'Store', $viewer_id, $public)) {
									$GLOBALS['app']->_ItemsOnLayout[] = '_'.$gadget.'comment'.$fcomment['gadget_reference'];
									if (!isset($this->_comments_owners[md5($fcomment['preactivity'].$fcomment['activity'].$fcomment['gadget_reference'])])) {
										$this->_comments_owners[md5($fcomment['preactivity'].$fcomment['activity'].$fcomment['gadget_reference'])] = array();
									}
									if (!in_array($fcomment['ownerid'], $this->_comments_owners[md5($fcomment['preactivity'].$fcomment['activity'].$fcomment['gadget_reference'])])) {
										$this->_comments_owners[md5($fcomment['preactivity'].$fcomment['activity'].$fcomment['gadget_reference'])][] = $fcomment['ownerid'];
									}
									$result[] = $new_comment;
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
					// Get all friends of user
					$friends = $jUser->GetFriendsOfUser($userInfo['id'], 'created', 'DESC', $result_limit);
					foreach ($friends as $friend) {
						if ($friend['friend_status'] == 'active') {
							$friendInfo = $jUser->GetUserInfoById((int)$friend['friend_id'], true, true, true, true);
							if (!Jaws_Error::IsError($friendInfo) && isset($friendInfo['id']) && !empty($friendInfo['id'])) {
								// Get all friends' products
								$pages = $model->GetStoreOfUserID($friendInfo['id'], 'Y', 'created', 'DESC', $result_limit);
								if (!Jaws_Error::IsError($pages)) {
									foreach($pages as $p) {
										if ($p['active'] == 'Y') {
											$res[] = $p;
										}
									}
								}
							}
						}
					}
				}
			}
			
		} else {			
			// Get all products
			$pages = $model->GetProducts($result_limit, 'created', 'DESC', false, null, 'Y');
			if (!Jaws_Error::IsError($pages)) {
				foreach($pages as $p) {
					$res[] = $p;
				}
			}

			// Get all Store comments
			$filtered_comments = $usersModel->GetCommentsOfParent(0, 'approved', 'Store', $result_limit);
			$filtered_product_comments = 0;
			if (!Jaws_Error::isError($filtered_comments) && !count($filtered_comments) <= 0) {
				reset($filtered_comments);
				$filtered_product_comments = array();
				foreach ($filtered_comments as $filtered_comment) {
					// Get product details
					$page = $model->GetProduct((int)$filtered_comment['gadget_reference']);
					if (!Jaws_Error::IsError($page) && isset($page['id']) && !empty($page['id']) && $page['active'] == 'Y') {
						$item = $this->GetStoreComment(array('gadget_reference' => $page['id'], 'public' => $public));
						$safe_title = $xss->filter(preg_replace("[^A-Za-z0-9\ ]", '', $page['title']));
						$product_url = $GLOBALS['app']->GetSiteURL() .'/'. $GLOBALS['app']->Map->GetURLFor('Store', 'Product', array('id' => $page['fast_url']));
						
						if (isset($item['url']) && !empty($item['url'])) {
							$owner_link = $item['url'];
							$owner_link_start = '<a href="'.$owner_link.'" title="'.preg_replace("[^A-Za-z0-9\ ]", '', $item['name']).'" name="'.preg_replace("[^A-Za-z0-9\ ]", '', $item['name']).'">';
							$owner_link_end = '</a>';
						}
						
						$filtered_comment['type'] = 'auto';
						$filtered_comment['ownerid'] = (int)$filtered_comment['ownerid'];
						$filtered_comment['targetid'] = $item['ownerid'];
						$owner_link_string = " ".$owner_link_start.$item['name'].$owner_link_end.($item['name'] != 'their own' ? '\'s' : '');					
						$filtered_comment['preactivity'] = 'commented on'.$owner_link_string.' <a href="'.$product_url.'" title="'.$safe_title.'" name="'.$safe_title.'">Store item</a>';
						$filtered_comment['activity'] = $item['activity'];
						$filtered_product_comments[] = $filtered_comment;
						$replies = $usersModel->GetCommentsOfParent($filtered_comment['id'], 'approved', '');
						if (Jaws_Error::IsError($replies)) {
							//return array();
						} else {
							foreach ($replies as $reply) {
								$filtered_product_comments[] = $reply;
							}
						}
					}
				}
			}

			$reply_comments = $filtered_product_comments;
			if (is_array($filtered_product_comments)) {
				foreach ($filtered_product_comments as $fcomment) {
					if ($fcomment['parent'] == 0) {
						$item = $this->GetStoreComment(array('gadget_reference' => $fcomment['gadget_reference'], 'public' => $public));
						if (!in_array('_'.$gadget.'comment'.$fcomment['gadget_reference'], $GLOBALS['app']->_ItemsOnLayout)) {
							$new_comment = array(
								'id' => 'comment'.$fcomment['gadget_reference'],
								'parent' => $fcomment['parent'],
								'name' => $item['name'],
								'email' => $item['email'],
								'url' => $item['url'],
								'title' => '',
								'msg_key' => md5(''.$item['email'].$item['msg_txt'].$fcomment['ownerid'].$fcomment['gadget_reference'].$fcomment['parent']),
								'msg_txt' => $item['msg_txt'],
								'image' => '',
								'type' => 'product',
								'sharing' => 'everyone',
								'status' => 'approved',
								'gadget' => 'Store',
								'gadget_reference' => $fcomment['gadget_reference'],
								'replies' => $item['replies'],
								'ownerid' => $fcomment['ownerid'],
								'targetid' => $fcomment['targetid'],
								'preactivity' => $fcomment['preactivity'],
								'activity' => $item['activity'],
								'permalink' => $GLOBALS['app']->GetSiteURL() . '/' . $GLOBALS['app']->Map->GetURLFor('Users', 'ShowComment', array('id' => $fcomment['id'], 'fusegadget' => $fcomment['gadget'])),
								'createtime' => $fcomment['createtime']
							);
							if ($usersModel->IsCommentSharedWithUser($new_comment, 'Store', $viewer_id, $public)) {
								$GLOBALS['app']->_ItemsOnLayout[] = '_'.$gadget.'comment'.$fcomment['gadget_reference'];
								if (!isset($this->_comments_owners[md5($fcomment['preactivity'].$fcomment['activity'].$fcomment['gadget_reference'])])) {
									$this->_comments_owners[md5($fcomment['preactivity'].$fcomment['activity'].$fcomment['gadget_reference'])] = array();
								}
								if (!in_array($fcomment['ownerid'], $this->_comments_owners[md5($fcomment['preactivity'].$fcomment['activity'].$fcomment['gadget_reference'])])) {
									$this->_comments_owners[md5($fcomment['preactivity'].$fcomment['activity'].$fcomment['gadget_reference'])][] = $fcomment['ownerid'];
								}
								$result[] = $new_comment;
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
		
		foreach ($res as $r) {
			$item = $this->GetStoreComment(array('gadget_reference' => $r['id'], 'public' => $public));
			if (!Jaws_Error::IsError($item) && isset($item['id']) && !empty($item['id'])) {
				if (
					$usersModel->IsCommentSharedWithUser($item, 'Store', $viewer_id, $public) && 
					!in_array('_'.$gadget.$item['id'], $GLOBALS['app']->_ItemsOnLayout)
				) {
					$GLOBALS['app']->_ItemsOnLayout[] = '_'.$gadget.$item['id'];
					if (isset($item['replies']) && is_array($item['replies'])) {
						foreach ($item['replies'] as $pcomment) {
							if ($pcomment['parent'] == 0) {
								if (!in_array('_'.$gadget.'comment'.$r['id'], $GLOBALS['app']->_ItemsOnLayout)) {
									$new_comment = array(
										'id' => 'comment'.$r['id'],
										'parent' => $pcomment['parent'],
										'name' => $item['name'],
										'email' => $item['email'],
										'url' => $item['url'],
										'title' => '',
										'msg_key' => md5(''.$item['email'].$item['msg_txt'].$pcomment['ownerid'].$r['id'].$pcomment['parent']),
										'msg_txt' => $item['msg_txt'],
										'image' => '',
										'type' => 'product',
										'sharing' => 'everyone',
										'status' => 'approved',
										'gadget' => 'Store',
										'gadget_reference' => $r['id'],
										'replies' => $item['replies'],
										'ownerid' => $pcomment['ownerid'],
										'targetid' => $pcomment['targetid'],
										'preactivity' => $pcomment['preactivity'],
										'activity' => $item['activity'],
										'permalink' => $GLOBALS['app']->GetSiteURL() . '/' . $GLOBALS['app']->Map->GetURLFor('Users', 'ShowComment', array('id' => $pcomment['id'], 'fusegadget' => $pcomment['gadget'])),
										'createtime' => $pcomment['createtime']
									);
									if ($usersModel->IsCommentSharedWithUser($new_comment, 'Store', $viewer_id, $public)) {
										$GLOBALS['app']->_ItemsOnLayout[] = '_'.$gadget.'comment'.$r['id'];
										if (!isset($this->_comments_owners[md5($pcomment['preactivity'].$pcomment['activity'].$pcomment['gadget_reference'])])) {
											$this->_comments_owners[md5($pcomment['preactivity'].$pcomment['activity'].$pcomment['gadget_reference'])] = array();
										}
										if (!in_array($pcomment['ownerid'], $this->_comments_owners[md5($pcomment['preactivity'].$pcomment['activity'].$pcomment['gadget_reference'])])) {
											$this->_comments_owners[md5($pcomment['preactivity'].$pcomment['activity'].$pcomment['gadget_reference'])][] = $pcomment['ownerid'];
										}
										$result[] = $new_comment;
										$result_counter++;
										if ($result_counter > $result_max) {
											return $usersModel->GetSortedComments($result, $params);
										}
									}
								}
							}
						}
					}
					
					$result[] = $item;
					$result_counter++;
					if ($result_counter > $result_max) {
						return $usersModel->GetSortedComments($result, $params);
					}
				}
			}
		}
		return $usersModel->GetSortedComments($result, $params);
    }
	
    /**
     * Returns an array with all comments in the Store gadget by given criteria
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
        $res = array();
        $result = array();
		require_once JAWS_PATH . 'include/Jaws/Comment.php';
        $api = new Jaws_Comment('Store');
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		
		//Load model
		$usersModel = $GLOBALS['app']->loadGadget('Users', 'Model');
		$model = $GLOBALS['app']->loadGadget('Store', 'Model');
		
		if (!is_null($id)) {
			require_once JAWS_PATH . 'include/Jaws/User.php';
			$jUser = new Jaws_User;
			// Get requested group
			$groupInfo = $jUser->GetGroupInfoById((int)$id);
			if (!Jaws_Error::IsError($groupInfo) && isset($groupInfo['id']) && !empty($groupInfo['id'])) {
				// Load comments of all gadgets
				$jms = $GLOBALS['app']->LoadGadget('Jms', 'AdminModel');
				$gadget_list = $jms->GetGadgetsList(null, true, true, true);
				if (count($gadget_list) <= 0) {
					Jaws_Error::Fatal('You don\'t have any installed gadgets, please enable/install one and then come back',
									 __FILE__, __LINE__);
				}
				reset($gadget_list);
			
				if ($layout != 'tiles') {
					// Comments shared with group
					$group_comments = $usersModel->GetCommentsSharedWithGroup((int)$id, 'Store', 'approved', $result_limit);
					foreach ($group_comments as $item) {
						if (
							$usersModel->IsCommentSharedWithUser($item, 'Store', $viewer_id, $public) && 
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
								return $usersModel->GetSortedComments($result, $params);
							}
						}
					}
									
					// Get all users of group
					$group_users = $jUser->GetUsersOfGroupByStatus($groupInfo['id'], array('active', 'admin', 'founder'));
					//$group_count = count($group_users);
					//reset($group_users);
					foreach ($group_users as $user) {
						//if ($user['group_status'] != 'denied') { 
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
								//$groups  = $jUser->GetGroupsOfUser($userInfo['id']);
								
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
									// Comments owned by user
									$owned_comments = $usersModel->GetCommentsFiltered('Store', 'ownerid', $userInfo['id'], 'approved', $result_limit);
									foreach ($owned_comments as $item) {
										if (
											$usersModel->IsCommentSharedWithUser($item, 'Store', $viewer_id, $public) && 
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
								//}
							}
						//}
					}
				}
				
				// Get products of group
				$pages = $model->GetStoreOfGroup($groupInfo['id'], 'updated', 'DESC', ($public === true ? 'Y' : null), null, '', '', '', '', '', $result_limit);
				if (!Jaws_Error::IsError($pages)) {
					foreach($pages as $p) {
						$res[] = $p;
					}
				}

				// Get all Store comments
				if ($layout != 'tiles') {
					$filtered_comments = $usersModel->GetCommentsOfParent(0, 'approved', 'Store', $result_limit);
					$filtered_product_comments = 0;
					if (!Jaws_Error::isError($filtered_comments) && !count($filtered_comments) <= 0) {
						reset($filtered_comments);
						$filtered_product_comments = array();
						foreach ($filtered_comments as $filtered_comment) {
							// Get product details
							$page = $model->GetProduct((int)$filtered_comment['gadget_reference']);
							if (!Jaws_Error::IsError($page) && isset($page['id']) && !empty($page['id']) && $page['active'] == 'Y') {
								$item = $this->GetStoreComment(array('gadget_reference' => $page['id'], 'public' => $public));
								$safe_title = $xss->filter(preg_replace("[^A-Za-z0-9\ ]", '', $page['title']));
								$product_url = $GLOBALS['app']->GetSiteURL() .'/'. $GLOBALS['app']->Map->GetURLFor('Store', 'Product', array('id' => $page['fast_url']));
								
								if (isset($item['url']) && !empty($item['url'])) {
									$owner_link = $item['url'];
									$owner_link_start = '<a href="'.$owner_link.'" title="'.preg_replace("[^A-Za-z0-9\ ]", '', $item['name']).'" name="'.preg_replace("[^A-Za-z0-9\ ]", '', $item['name']).'">';
									$owner_link_end = '</a>';
								}
								
								$filtered_comment['type'] = 'auto';
								$filtered_comment['ownerid'] = (int)$filtered_comment['ownerid'];
								$filtered_comment['targetid'] = $item['ownerid'];
								$owner_link_string = " ".$owner_link_start.$item['name'].$owner_link_end.($item['name'] != 'their own' ? '\'s' : '');					
								$filtered_comment['preactivity'] = 'commented on'.$owner_link_string.' <a href="'.$product_url.'" title="'.$safe_title.'" name="'.$safe_title.'">Store item</a>';
								$filtered_comment['activity'] = $item['activity'];
								$filtered_product_comments[] = $filtered_comment;
								$replies = $usersModel->GetCommentsOfParent($filtered_comment['id'], 'approved', '');
								if (Jaws_Error::IsError($replies)) {
									//return array();
								} else {
									foreach ($replies as $reply) {
										$filtered_product_comments[] = $reply;
									}
								}
							}
						}
					}

					$reply_comments = $filtered_product_comments;
					if (is_array($filtered_product_comments)) {
						foreach ($filtered_product_comments as $fcomment) {
							if ($fcomment['parent'] == 0) {
								$item = $this->GetStoreComment(array('gadget_reference' => $fcomment['gadget_reference'], 'public' => $public));
								if (!in_array('_'.$gadget.'comment'.$fcomment['gadget_reference'], $GLOBALS['app']->_ItemsOnLayout)) {
									$new_comment = array(
										'id' => 'comment'.$fcomment['gadget_reference'],
										'parent' => $fcomment['parent'],
										'name' => $item['name'],
										'email' => $item['email'],
										'url' => $item['url'],
										'title' => '',
										'msg_key' => md5(''.$item['email'].$item['msg_txt'].$fcomment['ownerid'].$fcomment['gadget_reference'].$fcomment['parent']),
										'msg_txt' => $item['msg_txt'],
										'image' => '',
										'type' => 'product',
										'sharing' => 'everyone',
										'status' => 'approved',
										'gadget' => 'Store',
										'gadget_reference' => $fcomment['gadget_reference'],
										'replies' => $item['replies'],
										'ownerid' => $fcomment['ownerid'],
										'targetid' => $fcomment['targetid'],
										'preactivity' => $fcomment['preactivity'],
										'activity' => $item['activity'],
										'permalink' => $GLOBALS['app']->GetSiteURL() . '/' . $GLOBALS['app']->Map->GetURLFor('Users', 'ShowComment', array('id' => $fcomment['id'], 'fusegadget' => $fcomment['gadget'])),
										'createtime' => $fcomment['createtime']
									);
									if ($usersModel->IsCommentSharedWithUser($new_comment, 'Store', $viewer_id, $public)) {
										$GLOBALS['app']->_ItemsOnLayout[] = '_'.$gadget.'comment'.$fcomment['gadget_reference'];
										if (!isset($this->_comments_owners[md5($fcomment['preactivity'].$fcomment['activity'].$fcomment['gadget_reference'])])) {
											$this->_comments_owners[md5($fcomment['preactivity'].$fcomment['activity'].$fcomment['gadget_reference'])] = array();
										}
										if (!in_array($fcomment['ownerid'], $this->_comments_owners[md5($fcomment['preactivity'].$fcomment['activity'].$fcomment['gadget_reference'])])) {
											$this->_comments_owners[md5($fcomment['preactivity'].$fcomment['activity'].$fcomment['gadget_reference'])][] = $fcomment['ownerid'];
										}
										$result[] = $new_comment;
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
				
				foreach ($res as $r) {
					$item = $this->GetStoreComment(array('gadget_reference' => $r['id'], 'public' => $public));
					if (!Jaws_Error::IsError($item) && isset($item['id']) && !empty($item['id'])) {
						if (
							$usersModel->IsCommentSharedWithUser($item, 'Store', $viewer_id, $public) && 
							!in_array('_'.$gadget.$item['id'], $GLOBALS['app']->_ItemsOnLayout)
						) {
							$GLOBALS['app']->_ItemsOnLayout[] = '_'.$gadget.$item['id'];
							if (isset($item['replies']) && is_array($item['replies'])) {
								foreach ($item['replies'] as $pcomment) {
									if ($pcomment['parent'] == 0) {
										if (!in_array('_'.$gadget.'comment'.$r['id'], $GLOBALS['app']->_ItemsOnLayout)) {
											$new_comment = array(
												'id' => 'comment'.$r['id'],
												'parent' => $pcomment['parent'],
												'name' => $item['name'],
												'email' => $item['email'],
												'url' => $item['url'],
												'title' => '',
												'msg_key' => md5(''.$item['email'].$item['msg_txt'].$pcomment['ownerid'].$r['id'].$pcomment['parent']),
												'msg_txt' => $item['msg_txt'],
												'image' => '',
												'type' => 'product',
												'sharing' => 'everyone',
												'status' => 'approved',
												'gadget' => 'Store',
												'gadget_reference' => $r['id'],
												'replies' => $item['replies'],
												'ownerid' => $pcomment['ownerid'],
												'targetid' => $pcomment['targetid'],
												'preactivity' => $pcomment['preactivity'],
												'activity' => $item['activity'],
												'permalink' => $GLOBALS['app']->GetSiteURL() . '/' . $GLOBALS['app']->Map->GetURLFor('Users', 'ShowComment', array('id' => $pcomment['id'], 'fusegadget' => $pcomment['gadget'])),
												'createtime' => $pcomment['createtime']
											);
											if ($usersModel->IsCommentSharedWithUser($new_comment, 'Store', $viewer_id, $public)) {
												$GLOBALS['app']->_ItemsOnLayout[] = '_'.$gadget.'comment'.$r['id'];
												if (!isset($this->_comments_owners[md5($pcomment['preactivity'].$pcomment['activity'].$pcomment['gadget_reference'])])) {
													$this->_comments_owners[md5($pcomment['preactivity'].$pcomment['activity'].$pcomment['gadget_reference'])] = array();
												}
												if (!in_array($pcomment['ownerid'], $this->_comments_owners[md5($pcomment['preactivity'].$pcomment['activity'].$pcomment['gadget_reference'])])) {
													$this->_comments_owners[md5($pcomment['preactivity'].$pcomment['activity'].$pcomment['gadget_reference'])][] = $pcomment['ownerid'];
												}
												$result[] = $new_comment;
												$result_counter++;
												if ($result_counter > $result_max) {
													return $usersModel->GetSortedComments($result, $params);
												}
											}
										}
									}
								}
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
		return $usersModel->GetSortedComments($result, $params);
    }
	
    /**
     * Returns an array of single comment thread
     *
     * @access  public
     */
    function GetStoreComment($params = array())
    {
		$id = $params['gadget_reference'];
		$public = $params['public'];

		require_once JAWS_PATH . 'include/Jaws/Comment.php';
        $api = new Jaws_Comment('Store');
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		
        $result = array();
		
		if (!is_null($id)) {			
			$GLOBALS['app']->Registry->LoadFile('Store');
			$GLOBALS['app']->Translate->LoadTranslation('Store', JAWS_GADGET);
			require_once JAWS_PATH . 'include/Jaws/Image.php';
			require_once JAWS_PATH . 'include/Jaws/User.php';
			$jUser = new Jaws_User;
			$model = $GLOBALS['app']->loadGadget('Store', 'Model');
			$usersModel = $GLOBALS['app']->loadGadget('Users', 'Model');
			
			$user_name = $GLOBALS['app']->Registry->Get('/config/site_name');
			$user_link = $GLOBALS['app']->GetSiteURL();
			$user_link_start = '<a href="'.$user_link.'" title="'.$xss->filter(preg_replace("[^A-Za-z0-9\ ]", '', $user_name)).'" name="'.$xss->filter(preg_replace("[^A-Za-z0-9\ ]", '', $user_name)).'">';
			$user_link_end = '</a>';
			if (empty($user_name)) {
				$user_name = str_replace(array('http://', 'https://'), '', $user_link);
			}
			$user_email = $GLOBALS['app']->Registry->Get('/network/site_email');
			$user_image = $jUser->GetAvatar();
			if (file_exists(JAWS_DATA . 'files/css/icon.png')) {
				$user_image = $GLOBALS['app']->GetDataURL('files/css/icon.png', true);
			} else if (file_exists(JAWS_DATA . 'files/css/logo.png')) {
				$user_image = $GLOBALS['app']->GetDataURL('files/css/logo.png', true);
			}
			
			$p = $model->GetProduct((int)$id);
			if (!Jaws_Error::IsError($p) && isset($p['id']) && !empty($p['id'])) {
				if ((int)$p['ownerid'] > 0) {
					$userInfo = $jUser->GetUserInfoById((int)$p['ownerid'], true, true, true, true);
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
						//$groups  = $jUser->GetGroupsOfUser($userInfo['id']);
						
						// Check if user is in profile group
						if (in_array($jUser->GetStatusOfUserInGroup($userInfo['id'], 'profile'), array('active','founder','admin'))) {
								$show_link = true;
						}
						$user_link = '';
						$user_link_start = '';
						$user_link_end = '';
						if ($show_link === true) {
							$user_link = $GLOBALS['app']->Map->GetURLFor('Users', 'AccountPublic', array('name' => $userInfo['username']));
							$user_link_start = '<a href="'.$user_link.'" title="'.preg_replace("[^A-Za-z0-9\ ]", '', $user_fullname).'" name="'.preg_replace("[^A-Za-z0-9\ ]", '',$user_fullname).'">';
							$user_link_end = '</a>';
						}
					}
				}
						
				$user_link_string = " ".$user_link_start.$user_name.$user_link_end.($user_name != 'their own' ? '\'s' : '');					
				
				$product_title = (strlen($xss->filter(strip_tags(htmlspecialchars_decode($p['title'], ENT_QUOTES)))) > 100 ? substr($xss->filter(strip_tags(htmlspecialchars_decode($p['title'], ENT_QUOTES))), 0, 100).'...' : $xss->filter(strip_tags(htmlspecialchars_decode($p['title'], ENT_QUOTES))));
				$safe_title = $xss->filter(preg_replace("[^A-Za-z0-9\ ]", '', $p['title']));
				$product_url = $GLOBALS['app']->GetSiteURL() .'/'. $GLOBALS['app']->Map->GetURLFor('Store', 'Product', array('id' => $p['fast_url']));
				$product_desc = $xss->filter(strip_tags(Jaws_Gadget::ParseText($p['description'], 'Store')));
				$product_desc = (strlen($product_desc) > 150 ? substr($product_desc, 0, 150).'...' : $product_desc);
				if (empty($product_desc)) {
					$product_desc = '<span style="display: inline; font-style: italic;">No description.</span>';
				}
				$product_price = ($p['price'] > 0 ? '$'.number_format($p['price'], 2, '.', ',') : 'Free');
				// TODO: Add post images (up to 3), and place in background similar to UsersLayoutHTML->ShowFiveUsers()
				$main_image_src = $GLOBALS['app']->GetJawsURL().'/gadgets/Store/images/logo.png';
				if (isset($p['image']) && !empty($p['image'])) {
					$p['image'] = $xss->filter(strip_tags($p['image']));
					if (substr(strtolower($p['image']), 0, 4) == "http") {
						$main_image_src = $p['image'];
						if (substr(strtolower($p['image']), 0, 7) == "http://") {
							$main_image_src = explode('http://', $main_image_src);
							foreach ($main_image_src as $img_src) {
								if (!empty($img_src)) {
									$main_image_src = 'http://'.$img_src;
									break;
								}
							}
						} else {
							$main_image_src = explode('https://', $main_image_src);
							foreach ($main_image_src as $img_src) {
								if (!empty($img_src)) {
									$main_image_src = 'https://'.$img_src;
									break;
								}
							}
						}
						if (strpos(strtolower($main_image_src), 'data/files/') !== false) {
							$main_image_src = 'image_thumb.php?uri='.urlencode($main_image_src);
						}
					} else {
						$thumb = Jaws_Image::GetThumbPath($p['image']);
						$medium = Jaws_Image::GetMediumPath($p['image']);
						if (file_exists(JAWS_DATA . 'files'.$thumb)) {
							$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$thumb;
						} else if (file_exists(JAWS_DATA . 'files'.$medium)) {
							$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$medium;
						} else if (file_exists(JAWS_DATA . 'files'.$p['image'])) {
							$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$p['image'];
						}
					}
				}
				
				$tpl = new Jaws_Template('gadgets/Store/templates/');
				$tpl->Load('CommentProduct.html');
				$tpl->SetBlock('comment');
				$tpl->SetVariable('product_url', $product_url);
				$tpl->SetVariable('product_id', $p['id']);
				$tpl->SetVariable('product_title', $product_title);
				$tpl->SetVariable('safe_title', $safe_title);
				$tpl->SetVariable('icon', $main_image_src);
				$tpl->SetVariable('product_description', $product_desc);
				$tpl->SetVariable('price_str', $product_price." ".$xss->filter(strip_tags(htmlspecialchars_decode($p['unit'], ENT_QUOTES))));
				$tpl->SetVariable('owner_name', $user_name);
				$tpl->ParseBlock('comment');
				
				$product_comment = $tpl->Get();
				
				// Get comments of this
				$comments = $api->GetComments($p['id'], 0);
				$product_comments = 0;
				if (Jaws_Error::IsError($comments)) {
					//return array();
				} else {
					$product_comments = array();
					foreach ($comments as $comment) {
						$comment['preactivity'] = 'commented on'.$user_link_string.' <a href="'.$product_url.'" title="'.$safe_title.'" name="'.$safe_title.'">Store item</a>';
						$comment['activity'] = '<img border="0" align="top" style="height: 16px; width: 16px; padding-top: 2px" src="'.$GLOBALS['app']->GetJawsURL().'/gadgets/Store/images/logo.png"> via Store';
						$comment['targetid'] = (int)$p['ownerid'];
						$product_comments[] = $comment;
						
						$replies = $usersModel->GetCommentsOfParent($comment['id'], 'approved', '');
						if (Jaws_Error::IsError($replies)) {
							//return array();
						} else {
							foreach ($replies as $reply) {
								$product_comments[] = $reply;
							}
						}
					}
				}
				$reply_comments = $product_comments;
				
				// Add this product as a comment
				$edit_links = array();
				if (
					$GLOBALS['app']->Session->GetPermission('Store', 'ManageProducts') || 
					((int)$p['ownerid'] > 0 && (int)$p['ownerid'] == $viewer_id)
				) {
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
				$result = array(
					'id' => 'product'.$p['id'],
					'name' => $user_name,
					'email' => $user_email,
					'url' => $user_link,
					'title' => '',
					'msg_key' => md5(''.$user_email.$product_comment.$p['ownerid'].$p['id'].(0)),
					'msg_txt' => $product_comment,
					'image' => $user_image,
					'type' => 'product',
					'sharing' => 'everyone',
					'status' => 'approved',
					'gadget' => 'Store',
					'gadget_reference' => $p['id'],
					'parent' => 0,
					'replies' => $reply_comments,
					'edit_links' => $edit_links,
					'ownerid' => $p['ownerid'],
					'targetid' => $p['ownerid'],
					'preactivity' => 'added a product',
					'activity' => '<img border="0" align="top" style="height: 16px; width: 16px; padding-top: 2px" src="'.$GLOBALS['app']->GetJawsURL().'/gadgets/Store/images/logo.png"> via Store',
					'permalink' => $product_url,
					'createtime' => $p['updated']
				);
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
        $result = '';
		if ($public === false) {
			$tpl = new Jaws_Template('gadgets/Store/templates/');
			$tpl->Load('SortingComments.html');
			$tpl->SetBlock('private');
			$tpl->SetVariable('title', $title);
			$tpl->SetVariable('site_url', $GLOBALS['app']->GetSiteURL());
			$tpl->SetVariable('OwnerID', (!is_null($OwnerID) ? $OwnerID : ''));
			$tpl->SetVariable('gadget', 'Store');
			$tpl->ParseBlock('private');
			$result = $tpl->Get();
		}
		return $result;
    }
	
}
