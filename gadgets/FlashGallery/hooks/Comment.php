<?php
/**
 * FlashGallery - Comment gadget hook
 *
 * @category   GadgetHook
 * @package    FlashGallery
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2012 Alan Valkoun
 */
class FlashGalleryCommentHook
{	
	var $_comments_owners = array();
	
    /**
     * Returns an array with all comments in the FlashGallery gadget owned by given user
     *
     * @access  public
     */
    function GetComments($params = array())
    {
		$OwnerID = $params['uid'];
		$id = null;
		if (isset($params['gadget_reference']) && !empty($params['gadget_reference'])) {
			$id = $params['gadget_reference'];
		}
		$viewer_id = (int)$GLOBALS['app']->Session->GetAttribute('user_id');
		$gadget = $params['gadget'];
		$public = $params['public'];
		$result_limit = (int)$params['limit'];
		$result_max = (int)$params['max'];
		$layout = strtolower($params['layout']);
		if (!in_array($layout, array('full', 'tiles', 'boxy'))) {
			$layout = 'full';
		}
        $result_counter = 0;
        $result_offset = false;
        $res = array();
        $result = array();
		require_once JAWS_PATH . 'include/Jaws/Image.php';
		require_once JAWS_PATH . 'include/Jaws/Comment.php';
		require_once JAWS_PATH . 'include/Jaws/Gravatar.php';
        $api = new Jaws_Comment('FlashGallery');
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		//Load model
		$model = $GLOBALS['app']->loadGadget('FlashGallery', 'Model');
		$usersModel = $GLOBALS['app']->loadGadget('Users', 'Model');
		
		/*
		if ($public === true) {
			return $result;
		}
		*/
		
		if (!is_null($id)) {
			$p = $model->GetPost((int)$id);
			if (!Jaws_Error::IsError($p) && isset($p['id']) && !empty($p['id'])) {
				if (
					$GLOBALS['app']->Session->GetPermission('FlashGallery', 'ManageFlashGallery') || 
					((int)$p['ownerid'] > 0 && (int)$p['ownerid'] == $viewer_id)
				) {
					$gadget_comments = $usersModel->GetCommentsFiltered('FlashGallery', 'postid', $id, 'approved', $result_limit);
					foreach ($gadget_comments as $item) {
						if (
							$usersModel->IsCommentSharedWithUser($item, 'FlashGallery', $viewer_id, $public) && 
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
				}
			}
		} else if (!is_null($OwnerID)) {			
			// Get galleries owned by user
			$owned = $model->GetAllPosts($result_limit, 'updated', 'DESC', false, (int)$OwnerID);
			if (!Jaws_Error::IsError($owned)) {
				foreach ($owned as $p) {
					$p['image'] = $xss->filter(strip_tags($p['image']));
					$thumb = Jaws_Image::GetThumbPath($p['image']);
					$medium = Jaws_Image::GetMediumPath($p['image']);
					if (file_exists(JAWS_DATA . 'files'.$thumb)) {
						$image = $thumb;
					} else if (file_exists(JAWS_DATA . 'files'.$medium)) {
						$image = $medium;
					} else if (file_exists(JAWS_DATA . 'files'.$p['image'])) {
						$image = $p['image'];
					}
					if (file_exists(JAWS_DATA . 'files'.$p['image'])) {
						$full_image = $p['image'];
					}
					if (!empty($image) && !empty($full_image)) {
						$res[] = $p;
					}
				}
			}
			
			if ($public === false && $GLOBALS['app']->Session->GetPermission('FlashGallery', 'ManageFlashGallery')) {
				$galleries = $model->GetAllPosts($result_limit, 'updated', 'DESC', false, null);
				if (!Jaws_Error::IsError($galleries)) {
					foreach ($galleries as $p) {
						$p['image'] = $xss->filter(strip_tags($p['image']));
						$thumb = Jaws_Image::GetThumbPath($p['image']);
						$medium = Jaws_Image::GetMediumPath($p['image']);
						if (file_exists(JAWS_DATA . 'files'.$thumb)) {
							$image = $thumb;
						} else if (file_exists(JAWS_DATA . 'files'.$medium)) {
							$image = $medium;
						} else if (file_exists(JAWS_DATA . 'files'.$p['image'])) {
							$image = $p['image'];
						}
						if (file_exists(JAWS_DATA . 'files'.$p['image'])) {
							$full_image = $p['image'];
						}
						if (!empty($image) && !empty($full_image)) {
							$res[] = $p;
						}
					}
				}
			}
			
			foreach ($res as $r) {
				$item = $this->GetFlashGalleryComment(array('gadget_reference' => $r['id'], 'public' => $public));
				if (!Jaws_Error::IsError($item) && isset($item['id']) && !empty($item['id'])) {
					if (
						$usersModel->IsCommentSharedWithUser($item, 'FlashGallery', $viewer_id, $public) && 
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
											'type' => (strpos(strtolower($r['url']), 'tour.getmytour.com') !== false ? 'tour' : 'photos'),
											'sharing' => $pcomment['sharing'],
											'status' => 'approved',
											'gadget' => 'FlashGallery',
											'gadget_reference' => $r['id'],
											'replies' => $item['replies'],
											'ownerid' => $pcomment['ownerid'],
											'targetid' => $pcomment['targetid'],
											'preactivity' => $pcomment['preactivity'],
											'activity' => $item['activity'],
											'permalink' => $GLOBALS['app']->GetSiteURL() . '/' . $GLOBALS['app']->Map->GetURLFor('Users', 'ShowComment', array('id' => $pcomment['id'], 'fusegadget' => $pcomment['gadget'])),
											'createtime' => $pcomment['createtime']
										);
										if ($usersModel->IsCommentSharedWithUser($new_comment, 'FlashGallery', $viewer_id, $public)) {
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
		return $usersModel->GetSortedComments($result, $params);
    }
		
    /**
     * Returns an array with all comments in the FlashGallery gadget by given criteria
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
        $api = new Jaws_Comment('FlashGallery');
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		
		//Load model
		$usersModel = $GLOBALS['app']->loadGadget('Users', 'Model');
		$model = $GLOBALS['app']->loadGadget('FlashGallery', 'Model');
		
		if (!is_null($id)) {
			require_once JAWS_PATH . 'include/Jaws/Image.php';
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
			
				// Comments shared with group
				if ($layout != 'tiles') {
					$group_comments = $usersModel->GetCommentsSharedWithGroup((int)$id, 'FlashGallery', 'approved', $result_limit);
					foreach ($group_comments as $item) {
						if (
							$usersModel->IsCommentSharedWithUser($item, 'FlashGallery', $viewer_id, $public) && 
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
						
								//if (in_array($user['group_status'], array('active','admin','founder'))) { 
									// Comments owned by user
									$owned_comments = $usersModel->GetCommentsFiltered('FlashGallery', 'ownerid', $userInfo['id'], 'approved', $result_limit);
									foreach ($owned_comments as $item) {
										if (
											$usersModel->IsCommentSharedWithUser($item, 'FlashGallery', $viewer_id, $public) && 
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
				
				// Get posts of group
				$pages = $model->GetFlashGalleryOfGroup($groupInfo['id'], 'updated', 'DESC', ($public === true ? 'Y' : null), null, '', '', '', '', '', $result_limit);
				if (!Jaws_Error::IsError($pages)) {
					foreach($pages as $p) {
						$p['image'] = $xss->filter(strip_tags($p['image']));
						if (file_exists(JAWS_DATA . 'files'.$p['image'])) {
							$res[] = $p;
						}
					}
				}

				// Get all FlashGallery comments
				if ($layout != 'tiles') {
					$filtered_comments = $usersModel->GetCommentsOfParent(0, 'approved', 'FlashGallery', $result_limit);
					$filtered_gallery_comments = 0;
					if (!Jaws_Error::isError($filtered_comments) && !count($filtered_comments) <= 0) {
						reset($filtered_comments);
						$filtered_gallery_comments = array();
						foreach ($filtered_comments as $filtered_comment) {
							// Get post details
							$page = $model->GetPost((int)$filtered_comment['gadget_reference']);
							if (!Jaws_Error::IsError($page) && isset($page['id']) && !empty($page['id']) && $page['active'] == 'Y') {
								$item = $this->GetFlashGalleryComment(array('gadget_reference' => $page['id'], 'public' => $public));
								$safe_title = $xss->filter(preg_replace("[^A-Za-z0-9\ ]", '', $page['title']));
								$gallery_url = $GLOBALS['app']->GetSiteURL() .'/'. $GLOBALS['app']->Map->GetURLFor('FlashGallery', 'Display', array('id' => $page['id']));
								
								if (isset($item['url']) && !empty($item['url'])) {
									$owner_link = $item['url'];
									$owner_link_start = '<a href="'.$owner_link.'" title="'.preg_replace("[^A-Za-z0-9\ ]", '', $item['name']).'" name="'.preg_replace("[^A-Za-z0-9\ ]", '', $item['name']).'">';
									$owner_link_end = '</a>';
								}
								
								$filtered_comment['type'] = 'auto';
								$filtered_comment['ownerid'] = (int)$filtered_comment['ownerid'];
								$filtered_comment['targetid'] = $item['ownerid'];
								$owner_link_string = " ".$owner_link_start.$item['name'].$owner_link_end.($item['name'] != 'their own' ? '\'s' : '');					
								$filtered_comment['preactivity'] = 'commented on'.$owner_link_string.' <a href="'.$gallery_url.'" title="'.$safe_title.'" name="'.$safe_title.'">photo</a>';
								$filtered_comment['activity'] = $item['activity'];
								$filtered_gallery_comments[] = $filtered_comment;
								$replies = $usersModel->GetCommentsOfParent($filtered_comment['id'], 'approved', '');
								if (Jaws_Error::IsError($replies)) {
									//return array();
								} else {
									foreach ($replies as $reply) {
										$filtered_gallery_comments[] = $reply;
									}
								}
							}
						}
					}

					$reply_comments = $filtered_gallery_comments;
					if (is_array($filtered_gallery_comments)) {
						foreach ($filtered_gallery_comments as $fcomment) {
							if ($fcomment['parent'] == 0) {
								$item = $this->GetFlashGalleryComment(array('gadget_reference' => $fcomment['gadget_reference'], 'public' => $public));
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
										'type' => 'gallery',
										'sharing' => 'everyone',
										'status' => 'approved',
										'gadget' => 'FlashGallery',
										'gadget_reference' => $fcomment['gadget_reference'],
										'replies' => $item['replies'],
										'ownerid' => $fcomment['ownerid'],
										'targetid' => $fcomment['targetid'],
										'preactivity' => $fcomment['preactivity'],
										'activity' => $item['activity'],
										'permalink' => $GLOBALS['app']->GetSiteURL() . '/' . $GLOBALS['app']->Map->GetURLFor('Users', 'ShowComment', array('id' => $fcomment['id'], 'fusegadget' => $fcomment['gadget'])),
										'createtime' => $fcomment['createtime']
									);
									if ($usersModel->IsCommentSharedWithUser($new_comment, 'FlashGallery', $viewer_id, $public)) {
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
					$item = $this->GetFlashGalleryComment(array('gadget_reference' => $r['id'], 'public' => $public));
					if (!Jaws_Error::IsError($item) && isset($item['id']) && !empty($item['id'])) {
						if (
							$usersModel->IsCommentSharedWithUser($item, 'FlashGallery', $viewer_id, $public) && 
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
												'type' => (strpos(strtolower($r['url']), 'tour.getmytour.com') !== false ? 'tour' : 'photos'),
												'sharing' => 'everyone',
												'status' => 'approved',
												'gadget' => 'FlashGallery',
												'gadget_reference' => $r['id'],
												'replies' => $item['replies'],
												'ownerid' => $pcomment['ownerid'],
												'targetid' => $pcomment['targetid'],
												'preactivity' => $pcomment['preactivity'],
												'activity' => $item['activity'],
												'permalink' => $GLOBALS['app']->GetSiteURL() . '/' . $GLOBALS['app']->Map->GetURLFor('Users', 'ShowComment', array('id' => $pcomment['id'], 'fusegadget' => $pcomment['gadget'])),
												'createtime' => $pcomment['createtime']
											);
											if ($usersModel->IsCommentSharedWithUser($new_comment, 'FlashGallery', $viewer_id, $public)) {
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
    function GetFlashGalleryComment($params = array())
    {
		$id = $params['gadget_reference'];
		$public = $params['public'];
		$viewer_id = (int)$GLOBALS['app']->Session->GetAttribute('user_id');

		require_once JAWS_PATH . 'include/Jaws/Comment.php';
        $api = new Jaws_Comment('FlashGallery');
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$site_url = $GLOBALS['app']->GetSiteURL('', false, 'https');
		
        $result = array();
		
		if (!is_null($id)) {			
			$GLOBALS['app']->Registry->LoadFile('FlashGallery');
			$GLOBALS['app']->Translate->LoadTranslation('FlashGallery', JAWS_GADGET);
			require_once JAWS_PATH . 'include/Jaws/Image.php';
			require_once JAWS_PATH . 'include/Jaws/User.php';
			$jUser = new Jaws_User;
			$model = $GLOBALS['app']->loadGadget('FlashGallery', 'Model');
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
			$p = $model->GetPost((int)$id);
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
						$show_link = false;
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
				
				$gallery_title = (strlen($xss->filter(strip_tags(htmlspecialchars_decode($p['title'], ENT_QUOTES)))) > 100 ? substr($xss->filter(strip_tags(htmlspecialchars_decode($p['title'], ENT_QUOTES))), 0, 100).'...' : $xss->filter(strip_tags(htmlspecialchars_decode($p['title'], ENT_QUOTES))));
				$safe_title = $xss->filter(preg_replace("[^A-Za-z0-9\ ]", '', $p['title']));
				$gallery_url = $GLOBALS['app']->GetSiteURL() .'/'. $GLOBALS['app']->Map->GetURLFor('FlashGallery', 'Display', array('id' => $p['id']));
				$gallery_desc = $xss->filter(strip_tags(Jaws_Gadget::ParseText($p['description'], 'FlashGallery')));
				$gallery_desc = (strlen($gallery_desc) > 150 ? substr($gallery_desc, 0, 150).'...' : $gallery_desc);
				if (empty($gallery_desc)) {
					if (!empty($safe_title)) {
						$gallery_desc = (strlen($safe_title) > 150 ? substr($safe_title, 0, 150).'...' : $safe_title);
					} else {
						$gallery_desc = "No description.";
					}
				}
				$image_src = '';
				$p['image'] = $xss->filter(strip_tags($p['image']));
				//$thumb = Jaws_Image::GetThumbPath($p['image']);
				$medium = Jaws_Image::GetMediumPath($p['image']);
				if (file_exists(JAWS_DATA . 'files'.$medium)) {
					$image = $medium;
				} else if (file_exists(JAWS_DATA . 'files'.$p['image'])) {
					$image = $p['image'];
				}
				if (file_exists(JAWS_DATA . 'files'.$p['image'])) {
					$full_image = $p['image'];
				}
				if (!empty($image) && !empty($full_image)) {
					$good_ext = array('jpg', 'jpeg', 'swf', 'gif', 'png', 'tif', 'bmp');
					$ext = end(explode('.', $image));  
					if(in_array(strtolower($ext),$good_ext)) { 
						$p['ext'] = $ext;
						$p['image'] = $image;
						$p['full_image'] = $full_image;
					}
					if (isset($p['url']) && !empty($p['url']) && strpos(strtolower($p['url']), 'tour.getmytour.com') !== false) {
						include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
						// snoopy
						$snoopy = new Snoopy('FlashGallery');
						$snoopy->agent = "Jaws";
						$tour_url = $p['url'];
						if($snoopy->fetch($tour_url)) {
							$tour_results = $snoopy->results;
							if (strpos($tour_results, '<link rel="image_src" href="') !== false) {
								$inputStr = $tour_results;
								$delimeterLeft = '<link rel="image_src" href="';
								$delimeterRight = '"';
								$startLeft = strpos($inputStr, $delimeterLeft);
								$posLeft = ($startLeft+strlen($delimeterLeft));
								$posRight = strpos($inputStr, $delimeterRight, $posLeft);
								$image_src = substr($inputStr, $posLeft, $posRight-$posLeft);
							}
						}
					}
					if (empty($image_src)) {
						$image_src = $GLOBALS['app']->getDataURL('', true) . 'files'.$p['image'];
						if (isset($p['watermark_image']) && !empty($p['watermark_image']) && file_exists(JAWS_DATA . 'files'.$p['watermark_image'])) {
							$watermark_image = $p['watermark_image'];
							$image_src = $GLOBALS['app']->getSiteURL().'/index.php?gadget=FileBrowser&action=Watermark&path='.urlencode($p['image']).'&wm='.urlencode($watermark_image);
						}
					}
				}
				
				$tpl = new Jaws_Template('gadgets/FlashGallery/templates/');
				$tpl->Load('CommentPhoto.html');
				$tpl->SetBlock('comment');
				$tpl->SetVariable('gallery_url', $gallery_url);
				$tpl->SetVariable('gallery_id', $p['id']);
				$tpl->SetVariable('gallery_title', $gallery_title);
				$tpl->SetVariable('safe_title', $safe_title);
				$tpl->SetVariable('icon', $image_src);
				$tpl->SetVariable('description', $gallery_desc);
				$tpl->SetVariable('owner_name', $user_name);
				$tpl->ParseBlock('comment');
				
				$gallery_comment = $tpl->Get();
				
				// Get comments of this
				$comments = $api->GetComments($p['id'], 0);
				$gallery_comments = 0;
				if (Jaws_Error::IsError($comments)) {
					//return array();
				} else {
					$gallery_comments = array();
					foreach ($comments as $comment) {
						$comment['preactivity'] = 'commented on'.$user_link_string.' <a href="'.$gallery_url.'" title="'.$safe_title.'" name="'.$safe_title.'">Photo</a>';
						$comment['activity'] = '<img border="0" align="top" style="height: 16px; width: 16px; padding-top: 2px" src="'.$GLOBALS['app']->GetJawsURL().'/gadgets/FlashGallery/images/logo.png"> via Gallery';
						$comment['targetid'] = (int)$p['ownerid'];
						$gallery_comments[] = $comment;
						
						$replies = $usersModel->GetCommentsOfParent($comment['id'], 'approved', '');
						if (Jaws_Error::IsError($replies)) {
							//return array();
						} else {
							foreach ($replies as $reply) {
								$gallery_comments[] = $reply;
							}
						}
					}
				}
				$reply_comments = $gallery_comments;
				
				// Add this post as a comment
				$edit_links = array();
				if (
					$GLOBALS['app']->Session->GetPermission('FlashGallery', 'ManageFlashGallery') || 
					((int)$p['ownerid'] > 0 && (int)$p['ownerid'] == $viewer_id)
				) {
					$edit_links = array(
						0 => array(
							'url' => $GLOBALS['app']->GetSiteURL('', false, 'http')."/index.php?gadget=FlashGallery&action=account_view&id=".$p['linkid'],
							'title' => "Edit this Album"
						),
						1 => array(
							'url' => "javascript: if (confirm(confirmCommentDelete)){location.href='".$GLOBALS['app']->GetSiteURL('', false, 'http')."/index.php?gadget=FlashGallery&action=account_form_post&fuseaction=DeletePost&id=".$p['id']."';};",
							'title' => "Delete this Photo"
						),
						2 => array(
							'url' => $gallery_url,
							'title' => "View this Album"
						)
					);
				}
				$result = array(
					'id' => 'gallery'.$p['id'],
					'name' => $user_name,
					'email' => $user_email,
					'url' => $user_link,
					'title' => '',
					'msg_key' => md5(''.$user_email.$gallery_comment.$p['ownerid'].$p['id'].(0)),
					'msg_txt' => $gallery_comment,
					'image' => $user_image,
					'type' => (strpos(strtolower($p['url']), 'tour.getmytour.com') !== false ? 'tour' : 'photos'),
					'sharing' => 'everyone',
					'status' => 'approved',
					'gadget' => 'FlashGallery',
					'gadget_reference' => $p['id'],
					'parent' => 0,
					'replies' => $reply_comments,
					'edit_links' => $edit_links,
					'ownerid' => $p['ownerid'],
					'targetid' => $p['ownerid'],
					'preactivity' => 'added a '.(strpos(strtolower($p['url']), 'tour.getmytour.com') !== false ? 'tour' : 'photo'),
					'activity' => '<img border="0" align="top" style="height: 16px; width: 16px; padding-top: 2px" src="'.$GLOBALS['app']->GetJawsURL().'/gadgets/FlashGallery/images/logo.png"> via Gallery',
					'permalink' => $gallery_url,
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
			$tpl = new Jaws_Template('gadgets/FlashGallery/templates/');
			$tpl->Load('SortingComments.html');
			$tpl->SetBlock('private');
			$tpl->SetVariable('title', $title);
			$tpl->SetVariable('site_url', $GLOBALS['app']->GetSiteURL());
			$tpl->SetVariable('OwnerID', (!is_null($OwnerID) ? $OwnerID : ''));
			$tpl->SetVariable('gadget', 'FlashGallery');
			$tpl->ParseBlock('private');
			$result = $tpl->Get();
		}
		return $result;
    }
}
