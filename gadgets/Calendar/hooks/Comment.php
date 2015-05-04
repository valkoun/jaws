<?php
/**
 * Calendar - Comment gadget hook
 *
 * @category   GadgetHook
 * @package    Calendar
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2011 Alan Valkoun
 */
class CalendarCommentHook
{
	var $_comments_owners = array();
    
	/**
     * Returns an array with all comments in the Calendar gadget owned by given user
     *
     * @access  public
     */
    function GetComments($params = array())
    {
		$OwnerID = $params['uid'];
		$id = $params['gadget_reference'];
		$gadget = $params['gadget'];
		$public = $params['public'];
		$viewer_id = (int)$GLOBALS['app']->Session->GetAttribute('user_id');
		$result_limit = (int)$params['limit'];
		$result_max = (int)$params['max'];
        $result_counter = 0;
        $result_offset = false;
        $res = array();
        $result = array();
		require_once JAWS_PATH . 'include/Jaws/Comment.php';
        $api = new Jaws_Comment('Calendar');
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		//Load model
		$usersModel = $GLOBALS['app']->loadGadget('Users', 'Model');
		$model = $GLOBALS['app']->loadGadget('Calendar', 'Model');
		require_once JAWS_PATH . 'include/Jaws/Image.php';
		require_once JAWS_PATH . 'include/Jaws/User.php';
		$jUser = new Jaws_User;
		
		if (is_array($id) && !count($id) <= 0) {
			foreach ($id as $i) {
				$page = $model->GetEvent((int)$i);
				if (!Jaws_Error::IsError($page) && isset($page['id']) && !empty($page['id'])) {
					$res[] = $page;
				}
			}
		} else if (!is_null($id)) {
			$gadget_comments = $usersModel->GetCommentsFiltered('Calendar', 'postid', $id, 'approved', $result_limit);
			foreach ($gadget_comments as $item) {
				if (
					$usersModel->IsCommentSharedWithUser($item, 'Calendar', $viewer_id, $public) && 
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
			$userInfo = $jUser->GetUserInfoById((int)$OwnerID, true, true, true, true);
			if (!Jaws_Error::IsError($userInfo) && isset($userInfo['id']) && !empty($userInfo['id'])) {
				
				
				// Get calendars of user
				$pages = $model->GetCategoriesOfUserID($userInfo['id'], ($public === true ? 'Y' : null), 'calendarparentcreated', 'DESC', $result_limit);
				if (!Jaws_Error::IsError($pages)) {
					foreach($pages as $p) {
						$res[] = $p;
					}
				}

				// Get events of user
				$pages = $model->GetEventsOfUserID($userInfo['id'], ($public === true ? 'Y' : null), 'created', 'DESC', $result_limit);
				if (!Jaws_Error::IsError($pages)) {
					foreach($pages as $p) {
						$res[] = $p;
					}
				}

				// Get Event comments that user made
				$filtered_comments = $usersModel->GetCommentsFiltered('Calendar', 'ownerid', $userInfo['id'], 'approved', $result_limit);
				$filtered_product_comments = 0;
				if (!Jaws_Error::isError($filtered_comments) && !count($filtered_comments) <= 0) {
					reset($filtered_comments);
					$filtered_product_comments = array();
					foreach ($filtered_comments as $filtered_comment) {
						// Get product details
						$page = $model->GetEvent((int)$filtered_comment['gadget_reference']);
						if (!Jaws_Error::IsError($page) && isset($page['id']) && !empty($page['id'])) {
							$item = $this->GetCalendarComment(array('gadget_reference' => $page['id'], 'public' => $public));

							$event_title = (strlen($xss->filter(strip_tags(htmlspecialchars_decode($page['event'], ENT_QUOTES)))) > 100 ? substr($xss->filter(strip_tags(htmlspecialchars_decode($page['event'], ENT_QUOTES))), 0, 100).'...' : $xss->filter(strip_tags(htmlspecialchars_decode($page['event'], ENT_QUOTES))));
							$safe_title = $xss->filter(preg_replace("[^A-Za-z0-9\ ]", '', $page['event']));
							$event_url = $GLOBALS['app']->GetSiteURL() .'/'. $GLOBALS['app']->Map->GetURLFor('Calendar', 'Detail', array('id' => $page['id']));
							
							if (isset($item['url']) && !empty($item['url'])) {
								$owner_link = $item['url'];
								$owner_link_start = '<a href="'.$owner_link.'" title="'.preg_replace("[^A-Za-z0-9\ ]", '', $item['name']).'" name="'.preg_replace("[^A-Za-z0-9\ ]", '', $item['name']).'">';
								$owner_link_end = '</a>';
							}
							
							$filtered_comment['type'] = 'auto';
							$filtered_comment['ownerid'] = (int)$filtered_comment['ownerid'];
							$filtered_comment['targetid'] = $item['ownerid'];
							$owner_link_string = " ".$owner_link_start.$item['name'].$owner_link_end.($item['name'] != 'their own' ? '\'s' : '');					
							if (substr($filtered_comment['msg_txt'], 0, 12) == 'is attending') {
								$filtered_comment['preactivity'] = 'is attending'.$owner_link_string.' <a href="'.$event_url.'" title="'.$safe_title.'" name="'.$safe_title.'">event</a>';
								//$replied_attending[] = $filtered_comment['ownerid'];
							} else if (substr($filtered_comment['msg_txt'], 0, 18) == 'might be attending') {
								$filtered_comment['preactivity'] = 'might be attending'.$owner_link_string.' <a href="'.$event_url.'" title="'.$safe_title.'" name="'.$safe_title.'">event</a>';
								//$replied_maybe[] = $filtered_comment['ownerid'];
							} else if (substr($filtered_comment['msg_txt'], 0, 16) == 'is not attending') {
								$filtered_comment['preactivity'] = 'is not attending'.$owner_link_string.' <a href="'.$event_url.'" title="'.$safe_title.'" name="'.$safe_title.'">event</a>';
								//$replied_decline[] = $filtered_comment['ownerid'];
							} else {
								$filtered_comment['preactivity'] = 'commented on'.$owner_link_string.' <a href="'.$event_url.'" title="'.$safe_title.'" name="'.$safe_title.'">event</a>';
							}
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
						if (
							$fcomment['parent'] == 0 && substr($fcomment['msg_txt'], 0, 12) != 'is attending' && 
							substr($fcomment['msg_txt'], 0, 18) != 'might be attending' && substr($fcomment['msg_txt'], 0, 16) != 'is not attending'
						) {
							$item = $this->GetCalendarComment(array('gadget_reference' => $fcomment['gadget_reference'], 'public' => $public));
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
									'image' => $item['image'],
									'type' => 'event',
									'sharing' => 'everyone',
									'status' => 'approved',
									'gadget' => 'Calendar',
									'gadget_reference' => $fcomment['gadget_reference'],
									'replies' => $item['replies'],
									'ownerid' => $fcomment['ownerid'],
									'targetid' => $fcomment['targetid'],
									'preactivity' => $fcomment['preactivity'],
									'activity' => $item['activity'],
									'permalink' => $GLOBALS['app']->GetSiteURL() . '/' . $GLOBALS['app']->Map->GetURLFor('Users', 'ShowComment', array('id' => $fcomment['id'], 'fusegadget' => $fcomment['gadget'])),
									'createtime' => $fcomment['createtime']
								);
								if ($usersModel->IsCommentSharedWithUser($new_comment, 'Calendar', $viewer_id, $public)) {
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
					$filtered_product_comments = 0;
					foreach ($friends as $friend) {
						if ($friend['friend_status'] == 'active') {
							$friendInfo = $jUser->GetUserInfoById((int)$friend['friend_id'], true, true, true, true);
							if (!Jaws_Error::IsError($friendInfo) && isset($friendInfo['id']) && !empty($friendInfo['id'])) {
								// Get all friends' events
								$pages = $model->GetEventsOfUserID($friendInfo['id'], 'Y', 'created', 'DESC', $result_limit);
								if (!Jaws_Error::IsError($pages)) {
									foreach($pages as $p) {
										if ($p['active'] == 'Y') {
											$res[] = $p;
										}
									}
								}
								// Get Event comments that user made
								$filtered_comments = $usersModel->GetCommentsFiltered('Calendar', 'ownerid', $friendInfo['id'], 'approved', $result_limit);
								if (!Jaws_Error::isError($filtered_comments) && !count($filtered_comments) <= 0) {
									reset($filtered_comments);
									$filtered_product_comments = ($filtered_product_comments == 0 ? array() : $filtered_product_comments);
									foreach ($filtered_comments as $filtered_comment) {
										// Get product details
										$page = $model->GetEvent((int)$filtered_comment['gadget_reference']);
										if (!Jaws_Error::IsError($page) && isset($page['id']) && !empty($page['id'])) {
											$item = $this->GetCalendarComment(array('gadget_reference' => $page['id'], 'public' => $public));

											$event_title = (strlen($xss->filter(strip_tags(htmlspecialchars_decode($page['event'], ENT_QUOTES)))) > 100 ? substr($xss->filter(strip_tags(htmlspecialchars_decode($page['event'], ENT_QUOTES))), 0, 100).'...' : $xss->filter(strip_tags(htmlspecialchars_decode($page['event'], ENT_QUOTES))));
											$safe_title = $xss->filter(preg_replace("[^A-Za-z0-9\ ]", '', $page['event']));
											$event_url = $GLOBALS['app']->GetSiteURL() .'/'. $GLOBALS['app']->Map->GetURLFor('Calendar', 'Detail', array('id' => $page['id']));
											
											if (isset($item['url']) && !empty($item['url'])) {
												$owner_link = $item['url'];
												$owner_link_start = '<a href="'.$owner_link.'" title="'.preg_replace("[^A-Za-z0-9\ ]", '', $item['name']).'" name="'.preg_replace("[^A-Za-z0-9\ ]", '', $item['name']).'">';
												$owner_link_end = '</a>';
											}
											
											$filtered_comment['type'] = 'auto';
											$filtered_comment['ownerid'] = (int)$filtered_comment['ownerid'];
											$filtered_comment['targetid'] = $item['ownerid'];
											$owner_link_string = " ".$owner_link_start.$item['name'].$owner_link_end.($item['name'] != 'their own' ? '\'s' : '');					
											if (substr($filtered_comment['msg_txt'], 0, 12) == 'is attending') {
												$filtered_comment['preactivity'] = 'is attending'.$owner_link_string.' <a href="'.$event_url.'" title="'.$safe_title.'" name="'.$safe_title.'">event</a>';
												//$replied_attending[] = $filtered_comment['ownerid'];
											} else if (substr($filtered_comment['msg_txt'], 0, 18) == 'might be attending') {
												$filtered_comment['preactivity'] = 'might be attending'.$owner_link_string.' <a href="'.$event_url.'" title="'.$safe_title.'" name="'.$safe_title.'">event</a>';
												//$replied_maybe[] = $filtered_comment['ownerid'];
											} else if (substr($filtered_comment['msg_txt'], 0, 16) == 'is not attending') {
												$filtered_comment['preactivity'] = 'is not attending'.$owner_link_string.' <a href="'.$event_url.'" title="'.$safe_title.'" name="'.$safe_title.'">event</a>';
												//$replied_decline[] = $filtered_comment['ownerid'];
											} else {
												$filtered_comment['preactivity'] = 'commented on'.$owner_link_string.' <a href="'.$event_url.'" title="'.$safe_title.'" name="'.$safe_title.'">event</a>';
											}
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
											$item = $this->GetCalendarComment(array('gadget_reference' => $fcomment['gadget_reference'], 'public' => $public));
											if (!in_array('_'.$gadget.'comment'.$fcomment['gadget_reference'], $GLOBALS['app']->_ItemsOnLayout)) {
												$new_comment = array(
													'id' => 'comment'.$fcomment['gadget_reference'],
													'parent' => $fcomment['parent'],
													'name' => $fcomment['name'],
													'email' => $fcomment['email'],
													'url' => $fcomment['url'],
													'title' => '',
													'msg_key' => md5(''.$fcomment['email'].$item['msg_txt'].$fcomment['ownerid'].$fcomment['gadget_reference'].$fcomment['parent']),
													'msg_txt' => $item['msg_txt'],
													'image' => $fcomment['image'],
													'type' => 'event',
													'sharing' => 'everyone',
													'status' => 'approved',
													'gadget' => 'Calendar',
													'gadget_reference' => $fcomment['gadget_reference'],
													'replies' => $item['replies'],
													'ownerid' => $fcomment['ownerid'],
													'targetid' => $fcomment['targetid'],
													'preactivity' => $fcomment['preactivity'],
													'activity' => $item['activity'],
													'permalink' => $GLOBALS['app']->GetSiteURL() . '/' . $GLOBALS['app']->Map->GetURLFor('Users', 'ShowComment', array('id' => $fcomment['id'], 'fusegadget' => $fcomment['gadget'])),
													'createtime' => $fcomment['createtime']
												);
												if ($usersModel->IsCommentSharedWithUser($new_comment, 'Calendar', $viewer_id, $public)) {
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
						}
					}
				}
			}
		} else {
			// Get all events
			$calendars = $model->GetAllCalendars(true);
			if (!Jaws_Error::IsError($calendars)) {
				foreach ($calendars as $calendar) {
					if ($calendar['calendarparenttype'] == 'A') {
						$pages = $model->GetAllEventsOfCalendar($calendar['calendarparentid'], $result_limit);
						if (!Jaws_Error::IsError($pages) && count($pages)) {
							// Sort result array
							$subkey = 'created'; 
							$temp_array = array();
							
							$temp_array[key($pages)] = array_shift($pages);

							foreach($pages as $key => $val) {
								$offset = 0;
								$found = false;
								foreach($temp_array as $tmp_key => $tmp_val) {
									$val[$subkey] = strtotime($val[$subkey]);
									if(!$found and strtolower($val[$subkey]) > strtolower($tmp_val[$subkey])) {
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

							$pages = $temp_array;
							foreach($pages as $p) {
								$res[] = $p;
							}
						}
					}
				}
			}

			// Get events of user
			$pages = $model->GetEvents(($public === true ? 'Y' : null), 'created', 'DESC', $result_limit);
			if (!Jaws_Error::IsError($pages)) {
				foreach($pages as $p) {
					$res[] = $p;
				}
			}
				
			// Get Event comments
			$filtered_comments = $usersModel->GetCommentsOfParent(0, 'approved', 'Calendar', $result_limit);
			$filtered_product_comments = 0;
			if (!Jaws_Error::isError($filtered_comments) && !count($filtered_comments) <= 0) {
				reset($filtered_comments);
				$filtered_product_comments = array();
				foreach ($filtered_comments as $filtered_comment) {
					// Get product details
					$page = $model->GetEvent((int)$filtered_comment['gadget_reference']);
					if (!Jaws_Error::IsError($page) && isset($page['id']) && !empty($page['id'])) {
						$item = $this->GetCalendarComment(array('gadget_reference' => $page['id'], 'public' => $public));

						$event_title = (strlen($xss->filter(strip_tags(htmlspecialchars_decode($page['event'], ENT_QUOTES)))) > 100 ? substr($xss->filter(strip_tags(htmlspecialchars_decode($page['event'], ENT_QUOTES))), 0, 100).'...' : $xss->filter(strip_tags(htmlspecialchars_decode($page['event'], ENT_QUOTES))));
						$safe_title = $xss->filter(preg_replace("[^A-Za-z0-9\ ]", '', $page['event']));
						$event_url = $GLOBALS['app']->GetSiteURL() .'/'. $GLOBALS['app']->Map->GetURLFor('Calendar', 'Detail', array('id' => $page['id']));
						
						if (isset($item['url']) && !empty($item['url'])) {
							$owner_link = $item['url'];
							$owner_link_start = '<a href="'.$owner_link.'" title="'.preg_replace("[^A-Za-z0-9\ ]", '', $item['name']).'" name="'.preg_replace("[^A-Za-z0-9\ ]", '', $item['name']).'">';
							$owner_link_end = '</a>';
						}
						
						$filtered_comment['type'] = 'auto';
						$filtered_comment['ownerid'] = (int)$filtered_comment['ownerid'];
						$filtered_comment['targetid'] = $item['ownerid'];
						$owner_link_string = " ".$owner_link_start.$item['name'].$owner_link_end.($item['name'] != 'their own' ? '\'s' : '');					
						if (substr($filtered_comment['msg_txt'], 0, 12) == 'is attending') {
							$filtered_comment['preactivity'] = 'is attending'.$owner_link_string.' <a href="'.$event_url.'" title="'.$safe_title.'" name="'.$safe_title.'">event</a>';
							//$replied_attending[] = $filtered_comment['ownerid'];
						} else if (substr($filtered_comment['msg_txt'], 0, 18) == 'might be attending') {
							$filtered_comment['preactivity'] = 'might be attending'.$owner_link_string.' <a href="'.$event_url.'" title="'.$safe_title.'" name="'.$safe_title.'">event</a>';
							//$replied_maybe[] = $filtered_comment['ownerid'];
						} else if (substr($filtered_comment['msg_txt'], 0, 16) == 'is not attending') {
							$filtered_comment['preactivity'] = 'is not attending'.$owner_link_string.' <a href="'.$event_url.'" title="'.$safe_title.'" name="'.$safe_title.'">event</a>';
							//$replied_decline[] = $filtered_comment['ownerid'];
						} else {
							$filtered_comment['preactivity'] = 'commented on'.$owner_link_string.' <a href="'.$event_url.'" title="'.$safe_title.'" name="'.$safe_title.'">event</a>';
						}
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
					if (
						$fcomment['parent'] == 0 && substr($fcomment['msg_txt'], 0, 12) != 'is attending' && 
						substr($fcomment['msg_txt'], 0, 18) != 'might be attending' && substr($fcomment['msg_txt'], 0, 16) != 'is not attending'
					) {
						$item = $this->GetCalendarComment(array('gadget_reference' => $fcomment['gadget_reference'], 'public' => $public));
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
								'image' => $item['image'],
								'type' => 'event',
								'sharing' => 'everyone',
								'status' => 'approved',
								'gadget' => 'Calendar',
								'gadget_reference' => $fcomment['gadget_reference'],
								'replies' => $item['replies'],
								'ownerid' => $fcomment['ownerid'],
								'targetid' => $fcomment['targetid'],
								'preactivity' => $fcomment['preactivity'],
								'activity' => $item['activity'],
								'permalink' => $GLOBALS['app']->GetSiteURL() . '/' . $GLOBALS['app']->Map->GetURLFor('Users', 'ShowComment', array('id' => $fcomment['id'], 'fusegadget' => $fcomment['gadget'])),
								'createtime' => $fcomment['createtime']
							);
							if ($usersModel->IsCommentSharedWithUser($new_comment, 'Calendar', $viewer_id, $public)) {
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
		
		if ($public === false && ($GLOBALS['app']->Session->IsAdmin() || $GLOBALS['app']->Session->IsSuperAdmin())) { 
			$users = $jUser->GetUsers(false, false, null, 'nickname', $result_limit);
			$filtered_product_comments = 0;
			foreach ($users as $friend) {
				$friendInfo = $jUser->GetUserInfoById((int)$friend['id'], true, true, true, true);
				if (!Jaws_Error::IsError($friendInfo) && isset($friendInfo['id']) && !empty($friendInfo['id'])) {
					// Get Event comments that user made
					$filtered_comments = $usersModel->GetCommentsFiltered('Calendar', 'ownerid', $friendInfo['id'], 'approved', $result_limit);
					if (!Jaws_Error::isError($filtered_comments) && !count($filtered_comments) <= 0) {
						reset($filtered_comments);
						$filtered_product_comments = ($filtered_product_comments == 0 ? array() : $filtered_product_comments);
						foreach ($filtered_comments as $filtered_comment) {
							// Get product details
							$page = $model->GetEvent((int)$filtered_comment['gadget_reference']);
							if (!Jaws_Error::IsError($page) && isset($page['id']) && !empty($page['id'])) {
								$item = $this->GetCalendarComment(array('gadget_reference' => $page['id'], 'public' => $public));

								$event_title = (strlen($xss->filter(strip_tags(htmlspecialchars_decode($page['event'], ENT_QUOTES)))) > 100 ? substr($xss->filter(strip_tags(htmlspecialchars_decode($page['event'], ENT_QUOTES))), 0, 100).'...' : $xss->filter(strip_tags(htmlspecialchars_decode($page['event'], ENT_QUOTES))));
								$safe_title = $xss->filter(preg_replace("[^A-Za-z0-9\ ]", '', $page['event']));
								$event_url = $GLOBALS['app']->GetSiteURL() .'/'. $GLOBALS['app']->Map->GetURLFor('Calendar', 'Detail', array('id' => $page['id']));
								
								if (isset($item['url']) && !empty($item['url'])) {
									$owner_link = $item['url'];
									$owner_link_start = '<a href="'.$owner_link.'" title="'.preg_replace("[^A-Za-z0-9\ ]", '', $item['name']).'" name="'.preg_replace("[^A-Za-z0-9\ ]", '', $item['name']).'">';
									$owner_link_end = '</a>';
								}
								
								$filtered_comment['type'] = 'auto';
								$filtered_comment['ownerid'] = (int)$filtered_comment['ownerid'];
								$filtered_comment['targetid'] = $item['ownerid'];
								$owner_link_string = " ".$owner_link_start.$item['name'].$owner_link_end.($item['name'] != 'their own' ? '\'s' : '');					
								if (substr($filtered_comment['msg_txt'], 0, 12) == 'is attending') {
									$filtered_comment['preactivity'] = 'is attending'.$owner_link_string.' <a href="'.$event_url.'" title="'.$safe_title.'" name="'.$safe_title.'">event</a>';
									//$replied_attending[] = $filtered_comment['ownerid'];
								} else if (substr($filtered_comment['msg_txt'], 0, 18) == 'might be attending') {
									$filtered_comment['preactivity'] = 'might be attending'.$owner_link_string.' <a href="'.$event_url.'" title="'.$safe_title.'" name="'.$safe_title.'">event</a>';
									//$replied_maybe[] = $filtered_comment['ownerid'];
								} else if (substr($filtered_comment['msg_txt'], 0, 16) == 'is not attending') {
									$filtered_comment['preactivity'] = 'is not attending'.$owner_link_string.' <a href="'.$event_url.'" title="'.$safe_title.'" name="'.$safe_title.'">event</a>';
									//$replied_decline[] = $filtered_comment['ownerid'];
								} else {
									$filtered_comment['preactivity'] = 'commented on'.$owner_link_string.' <a href="'.$event_url.'" title="'.$safe_title.'" name="'.$safe_title.'">event</a>';
								}
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
				}
			}

			$reply_comments = $filtered_product_comments;
			if (is_array($filtered_product_comments)) {
				foreach ($filtered_product_comments as $fcomment) {
					if ((int)$fcomment['parent'] == 0) {
						$item = $this->GetCalendarComment(array('gadget_reference' => $fcomment['gadget_reference'], 'public' => $public));
						if (!in_array('_'.$gadget.'comment'.$fcomment['gadget_reference'], $GLOBALS['app']->_ItemsOnLayout)) {
							$new_comment = array(
								'id' => 'comment'.$fcomment['gadget_reference'],
								'parent' => $fcomment['parent'],
								'name' => $fcomment['name'],
								'email' => $fcomment['email'],
								'url' => $fcomment['url'],
								'title' => '',
								'msg_key' => md5(''.$fcomment['email'].$item['msg_txt'].$fcomment['ownerid'].$fcomment['gadget_reference'].$fcomment['parent']),
								'msg_txt' => $item['msg_txt'],
								'image' => $fcomment['image'],
								'type' => 'event',
								'sharing' => 'everyone',
								'status' => 'approved',
								'gadget' => 'Calendar',
								'gadget_reference' => $fcomment['gadget_reference'],
								'replies' => $item['replies'],
								'ownerid' => $fcomment['ownerid'],
								'targetid' => $fcomment['targetid'],
								'preactivity' => $fcomment['preactivity'],
								'activity' => $item['activity'],
								'permalink' => $GLOBALS['app']->GetSiteURL() . '/' . $GLOBALS['app']->Map->GetURLFor('Users', 'ShowComment', array('id' => $fcomment['id'], 'fusegadget' => $fcomment['gadget'])),
								'createtime' => $fcomment['createtime']
							);
							//if ($usersModel->IsCommentSharedWithUser($new_comment, 'Calendar', $viewer_id, $public)) {
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
							//}
						}
					}
				}
			}
		}
		
		foreach ($res as $r) {
			$item = $this->GetCalendarComment(array('gadget_reference' => $r['id'], 'public' => $public));
			if (!Jaws_Error::IsError($item) && isset($item['id']) && !empty($item['id'])) {
				if (
					$usersModel->IsCommentSharedWithUser($item, 'Calendar', $viewer_id, $public) && 
					!in_array('_'.$gadget.$item['id'], $GLOBALS['app']->_ItemsOnLayout)
				) {
					$GLOBALS['app']->_ItemsOnLayout[] = '_'.$gadget.$item['id'];
					if (isset($item['replies']) && is_array($item['replies'])) {
						foreach ($item['replies'] as $pcomment) {
							if (
								$pcomment['parent'] == 0 && substr($pcomment['msg_txt'], 0, 12) != 'is attending' && 
								substr($pcomment['msg_txt'], 0, 18) != 'might be attending' && substr($pcomment['msg_txt'], 0, 16) != 'is not attending'
							) {
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
										'image' => $item['image'],
										'type' => 'event',
										'sharing' => 'everyone',
										'status' => 'approved',
										'gadget' => 'Calendar',
										'gadget_reference' => $r['id'],
										'replies' => $item['replies'],
										'ownerid' => $pcomment['ownerid'],
										'targetid' => $pcomment['targetid'],
										'preactivity' => $pcomment['preactivity'],
										'activity' => $item['activity'],
										'permalink' => $GLOBALS['app']->GetSiteURL() . '/' . $GLOBALS['app']->Map->GetURLFor('Users', 'ShowComment', array('id' => $pcomment['id'], 'fusegadget' => $pcomment['gadget'])),
										'createtime' => $pcomment['createtime']
									);
									if ($usersModel->IsCommentSharedWithUser($new_comment, 'Calendar', $viewer_id, $public)) {
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
     * Returns an array of comments for given group
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
        $result_counter = 0;
        $result_offset = 0;
        $res = array();
        $result = array();
		require_once JAWS_PATH . 'include/Jaws/Comment.php';
        $api = new Jaws_Comment('Calendar');
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		require_once JAWS_PATH . 'include/Jaws/Image.php';
		$usersModel = $GLOBALS['app']->loadGadget('Users', 'Model');
		$model = $GLOBALS['app']->loadGadget('Calendar', 'Model');
		$jUser = new Jaws_User;
		
		if (!is_null($id)) {			
			// Get requested group
			$groupInfo = $jUser->GetGroupInfoById((int)$id);
			if (!Jaws_Error::IsError($groupInfo) && isset($groupInfo['id']) && !empty($groupInfo['id'])) {
			
				// Get all users of group
				$group_users = $jUser->GetUsersOfGroup($groupInfo['id']);
				foreach ($group_users as $user) {
					if ($user['group_status'] != 'denied') { 
						$userInfo = $jUser->GetUserInfoById($user['user_id'], true, true, true, true);
						if (!Jaws_Error::IsError($userInfo) && isset($userInfo['id']) && !empty($userInfo['id'])) {
				
							// Get events of user
							$pages = $model->GetEventsOfUserID($userInfo['id'], ($public === true ? 'Y' : null), 'created', 'DESC', $result_limit);
							if (!Jaws_Error::IsError($pages)) {
								foreach($pages as $p) {
									$res[] = $p;
								}
							}
						}
					}
				}
			}
		}
		foreach ($res as $r) {
			$item = $this->GetCalendarComment(array('gadget_reference' => $r['id'], 'public' => $public));
			if (!Jaws_Error::IsError($item) && isset($item['id']) && !empty($item['id'])) {
				if (
					$usersModel->IsCommentSharedWithUser($item, 'Calendar', $viewer_id, $public) && 
					!in_array('_'.$gadget.$item['id'], $GLOBALS['app']->_ItemsOnLayout)
				) {
					$GLOBALS['app']->_ItemsOnLayout[] = '_'.$gadget.$item['id'];
					if (isset($item['replies']) && is_array($item['replies'])) {
						foreach ($item['replies'] as $pcomment) {
							if (
								$pcomment['parent'] == 0 && substr($pcomment['msg_txt'], 0, 12) != 'is attending' && 
								substr($pcomment['msg_txt'], 0, 18) != 'might be attending' && substr($pcomment['msg_txt'], 0, 16) != 'is not attending'
							) {
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
										'image' => $item['image'],
										'type' => 'event',
										'sharing' => 'everyone',
										'status' => 'approved',
										'gadget' => 'Calendar',
										'gadget_reference' => $r['id'],
										'replies' => $item['replies'],
										'ownerid' => $pcomment['ownerid'],
										'targetid' => $pcomment['targetid'],
										'preactivity' => $pcomment['preactivity'],
										'activity' => $item['activity'],
										'permalink' => $GLOBALS['app']->GetSiteURL() . '/' . $GLOBALS['app']->Map->GetURLFor('Users', 'ShowComment', array('id' => $pcomment['id'], 'fusegadget' => $pcomment['gadget'])),
										'createtime' => $pcomment['createtime']
									);
									if ($usersModel->IsCommentSharedWithUser($new_comment, 'Calendar', $viewer_id, $public)) {
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
     * Returns an array of single comment thread
     *
     * @access  public
     */
    function GetCalendarComment($params = array())
    {
		$id = $params['gadget_reference'];
		$public = $params['public'];
		$viewer_id = (int)$GLOBALS['app']->Session->GetAttribute('user_id');
		
		require_once JAWS_PATH . 'include/Jaws/Comment.php';
        $api = new Jaws_Comment('Calendar');
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		
        $result = array();
		
		if (!is_null($id)) {			
			$GLOBALS['app']->Registry->LoadFile('Calendar');
			$GLOBALS['app']->Translate->LoadTranslation('Calendar', JAWS_GADGET);
			require_once JAWS_PATH . 'include/Jaws/Image.php';
			require_once JAWS_PATH . 'include/Jaws/User.php';
			$jUser = new Jaws_User;
			$model = $GLOBALS['app']->loadGadget('Calendar', 'Model');
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
			
			$p = $model->GetEvent((int)$id);
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
						$groups  = $jUser->GetGroupsOfUser($userInfo['id']);
						
						// Check if user is in profile group
						$show_link = false;
						if (!Jaws_Error::IsError($groups)) {
							foreach ($groups as $group) {
								if (
									strtolower($group['group_name']) == 'profile' && 
									($group['group_status'] == 'active' || 
									$group['group_status'] == 'founder' || 
									$group['group_status'] == 'admin')
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
							$user_link_start = '<a href="'.$user_link.'" title="'.preg_replace("[^A-Za-z0-9\ ]", '', $user_fullname).'" name="'.preg_replace("[^A-Za-z0-9\ ]", '',$user_fullname).'">';
							$user_link_end = '</a>';
						}
					}
				}
						
				$user_link_string = " ".$user_link_start.$user_name.$user_link_end.($user_name != 'their own' ? '\'s' : '');					
				
				$event_title = (strlen($xss->filter(strip_tags(htmlspecialchars_decode($p['event'], ENT_QUOTES)))) > 100 ? substr($xss->filter(strip_tags(htmlspecialchars_decode($p['event'], ENT_QUOTES))), 0, 100).'...' : $xss->filter(strip_tags(htmlspecialchars_decode($p['event'], ENT_QUOTES))));
				$safe_title = $xss->filter(preg_replace("[^A-Za-z0-9\ ]", '', $p['event']));
				$event_host = (strlen($xss->filter(strip_tags(htmlspecialchars_decode($p['host'], ENT_QUOTES)))) > 100 ? substr($xss->filter(strip_tags(htmlspecialchars_decode($p['host'], ENT_QUOTES))), 0, 100).'...' : $xss->filter(strip_tags(htmlspecialchars_decode($p['host'], ENT_QUOTES))));
				$safe_host = $xss->filter(preg_replace("[^A-Za-z0-9\ ]", '', $p['host']));
				$event_url = $GLOBALS['app']->GetSiteURL() .'/'. $GLOBALS['app']->Map->GetURLFor('Calendar', 'Detail', array('id' => $p['id']));
				if (isset($p['sm_description']) && !empty($p['sm_description'])) {
					$event_desc = (strlen($xss->filter(strip_tags(htmlspecialchars_decode($p['sm_description'], ENT_QUOTES)))) > 100 ? substr($xss->filter(strip_tags(htmlspecialchars_decode($p['sm_description'], ENT_QUOTES))), 0, 100).'...' : $xss->filter(strip_tags(htmlspecialchars_decode($p['sm_description'], ENT_QUOTES))));
				} else {
					$event_desc = (strlen($xss->filter(strip_tags(htmlspecialchars_decode($p['description'], ENT_QUOTES)))) > 100 ? substr($xss->filter(strip_tags(htmlspecialchars_decode($p['description'], ENT_QUOTES))), 0, 100).'...' : $xss->filter(strip_tags(htmlspecialchars_decode($p['description'], ENT_QUOTES))));
				}
				if (empty($event_desc)) {
					$event_desc = '<span style="display: inline; font-style: italic;">No description.</span>';
				}
				$date = $GLOBALS['app']->loadDate();
				$date_str = '';
				$event_start_date = '';
				$event_start_shortmonth = '';
				if (isset($p['startdate']) && !empty($p['startdate'])) {
					$event_start_day = $date->Format($p['startdate'], 'DN');
					$event_start_date = $date->Format($p['startdate'], 'd');
					$event_start_monthname = $date->Format($p['startdate'], 'MN');
					$event_start_shortmonth = substr($event_start_monthname, 0, 3);
					$event_start_year = $date->Format($p['startdate'], 'Y');
					$date_str .= $event_start_day.', '.$event_start_shortmonth.' '.$event_start_date;
					if (isset($p['enddate']) && !empty($p['enddate']) && ($p['enddate'] != $p['startdate'])) {
						$event_end_day = $date->Format($p['enddate'], 'DN');
						$event_end_date = $date->Format($p['enddate'], 'd');
						$event_end_monthname = $date->Format($p['enddate'], 'MN');
						$event_end_shortmonth = substr($event_end_monthname, 0, 3);
						$event_end_year = $date->Format($p['enddate'], 'Y');
						$date_str .= (!empty($date_str) ? ' - ' : '');
						$date_str .= $event_end_day.', '.$event_end_shortmonth.' '.$event_end_date;
					}
				} else {
					$date_str .= "date to be decided";
				}
				if (!empty($date_str)) {
					$date_str .= '&nbsp;';
				}
				$time_str = (isset($p['itime']) && !empty($p['itime']) ? $date->Format($p['itime'], "g:i").' '.date('a', strtotime($date->Format($p['itime'], "F j, g:i a"))) : '');
				$time_str .= (isset($p['endtime']) && !empty($p['endtime']) && ($p['endtime'] != $p['itime']) ? (!empty($time_str) ? ' - ' : '').$date->Format($p['endtime'], "g:i").' '.date('a', strtotime($date->Format($p['endtime'], "F j, g:i a"))) : '');
				if (!empty($time_str)) {
					$time_str .= ',&nbsp;';
				}
				
				// Get comments of this
				$comments = $api->GetComments($p['id'], 0);
				$event_comments = 0;
				$replied_attending = array();
				$replied_maybe = array();
				$replied_decline = array();
				if (Jaws_Error::IsError($comments)) {
					//return array();
				} else {
					$event_comments = array();
					// FIXME: Language strings
					foreach ($comments as $comment) {
						if (substr($comment['msg_txt'], 0, 12) == 'is attending') {
							$comment['preactivity'] = 'is attending'.$user_link_string.' <a href="'.$event_url.'" title="'.$safe_title.'" name="'.$safe_title.'">event</a>';
							$replied_attending[] = $comment['ownerid'];
						} else if (substr($comment['msg_txt'], 0, 18) == 'might be attending') {
							$comment['preactivity'] = 'might be attending'.$user_link_string.' <a href="'.$event_url.'" title="'.$safe_title.'" name="'.$safe_title.'">event</a>';
							$replied_maybe[] = $comment['ownerid'];
						} else if (substr($comment['msg_txt'], 0, 16) == 'is not attending') {
							$replied_decline[] = $comment['ownerid'];
							continue;
							$comment['preactivity'] = 'is not attending'.$user_link_string.' <a href="'.$event_url.'" title="'.$safe_title.'" name="'.$safe_title.'">event</a>';
						} else {
							$comment['preactivity'] = 'commented on'.$user_link_string.' <a href="'.$event_url.'" title="'.$safe_title.'" name="'.$safe_title.'">event</a>';
						}
						$comment['activity'] = '<img border="0" align="top" style="height: 16px; width: 16px; padding-top: 2px" src="'.$GLOBALS['app']->GetJawsURL().'/gadgets/Calendar/images/logo.png"> via Calendar';
						$comment['targetid'] = (int)$p['ownerid'];
						$event_comments[] = $comment;
						
						$replies = $usersModel->GetCommentsOfParent($comment['id'], 'approved', '');
						if (Jaws_Error::IsError($replies)) {
							//return array();
						} else {
							foreach ($replies as $reply) {
								$event_comments[] = $reply;
							}
						}
					}
				}
				$reply_comments = $event_comments;
				
				$event_attendance = '';
				if (!count($replied_attending) <= 0 || !count($replied_maybe) <= 0 || !count($replied_decline) <= 0 || $p['max_occupancy'] > 0) {
					$event_attendance .= (!count($replied_attending) <= 0 ? count($replied_attending)." ".(count($replied_attending) > 1 ? "people are" : "person is")." going" : '');
					$event_attendance .= (!count($replied_maybe) <= 0 ? (!empty($event_attendance) ? ', ' : '').count($replied_maybe)." ".(count($replied_maybe) > 1 ? "people" : "person")." might go" : '');
					$event_attendance .= (!count($replied_decline) <= 0 ? (!empty($event_attendance) ? ', ' : '').count($replied_decline)." ".(count($replied_decline) > 1 ? "people" : "person")." declined" : '');
					$event_attendance .= ($p['max_occupancy'] > 0 ? (!empty($event_attendance) ? " (" : '').($p['max_occupancy']-$p['occupants'])." reservations left".(!empty($event_attendance) ? ")" : '') : '');
					$event_desc .= '<br /><span style="display: inline; font-size: 11px; color: gray;">'.$event_attendance.'</span>';
				}
				
				$tpl = new Jaws_Template('gadgets/Calendar/templates/');
				$tpl->Load('CommentEvent.html');
				$tpl->SetBlock('comment');
				$tpl->SetVariable('event_url', $event_url);
				$tpl->SetVariable('event_id', $p['id']);
				$tpl->SetVariable('event_title', $event_title);
				$tpl->SetVariable('event_description', $event_desc);
				$tpl->SetVariable('time_str', $time_str);
				$tpl->SetVariable('date_str', $date_str);
				$tpl->SetVariable('host', (!empty($event_host) ? 'at '.$event_host : ''));
				$tpl->SetVariable('owner_name', $user_name);

				if (!empty($event_start_date) && !empty($event_start_shortmonth)) {
					$tpl->SetBlock('comment/icon');
					$tpl->SetVariable('event_url', $event_url);
					$tpl->SetVariable('event_start_date', $event_start_date);
					$tpl->SetVariable('event_start_shortmonth', $event_start_shortmonth);
					$tpl->ParseBlock('comment/icon');
				} else if (file_exists(JAWS_PATH . 'gadgets/Calendar/images/logo.png')) {
					$tpl->SetBlock('comment/logo');
					$tpl->SetVariable('event_url', $event_url);
					$tpl->SetVariable('logo', $GLOBALS['app']->GetJawsURL().'/gadgets/Calendar/images/logo.png');
					$tpl->ParseBlock('comment/logo');
				}
												
				// Get possible actions that the logged user can take 
				$tpl->SetBlock('comment/actions');
				$joinButton =& Piwi::CreateWidget('Button', '', _t('CALENDAR_EVENT_RSVP_JOIN'));
				if (($p['max_occupancy']-$p['occupants']) == 0 || in_array($viewer_id, $replied_attending) || in_array($viewer_id, explode(',',$p['occupants']))) {
					$joinButton->SetClass("event-button join-event-button button-disabled");
				} else {
					if (!$GLOBALS['app']->Session->Logged()) {
						$joinButton->AddEvent(ON_CLICK, "javascript: location.href = '".$GLOBALS['app']->GetSiteURL() . '/index.php?gadget=Users&action=DefaultAction&redirect_to='.urlencode($GLOBALS['app']->GetFullURL())."';");
					} else {
						$joinButton->AddEvent(ON_CLICK,
							"javascript: $(this).up('.news-body').down('.comment-entry').value = 'is attending.'; saveCalendarReply(0, ".$p['id'].", $(this).up('.news-item').id.replace('news-', ''));");
					}
					$joinButton->SetClass("event-button join-event-button");
				}
				$joinButton->SetStyle("min-width: 40px; font-size: 12px; margin: 0px;");
				$tpl->SetVariable('button_join', $joinButton->Get().'&nbsp;');
				$maybeButton =& Piwi::CreateWidget('Button', '', _t('CALENDAR_EVENT_RSVP_MAYBE'));
				if (in_array($viewer_id, $replied_maybe)) {
					$maybeButton->SetClass("event-button maybe-event-button button-disabled");
				} else {
					if (!$GLOBALS['app']->Session->Logged()) {
						$maybeButton->AddEvent(ON_CLICK, "javascript: location.href = '".$GLOBALS['app']->GetSiteURL() . '/index.php?gadget=Users&action=DefaultAction&redirect_to='.urlencode($GLOBALS['app']->GetFullURL())."';");
					} else {
						$maybeButton->AddEvent(ON_CLICK,
							"javascript: $(this).up('.news-body').down('.comment-entry').value = 'might be attending.'; saveCalendarReply(0, ".$p['id'].", $(this).up('.news-item').id.replace('news-', ''));");
					}
					$maybeButton->SetClass("event-button maybe-event-button");
				}
				$maybeButton->SetStyle("min-width: 40px; font-size: 12px; margin: 0px;");
				$tpl->SetVariable('button_maybe', $maybeButton->Get().'&nbsp;');
				$declineButton =& Piwi::CreateWidget('Button', '', _t('CALENDAR_EVENT_RSVP_DECLINE'));
				if (in_array($viewer_id, $replied_decline)) {
					$declineButton->SetClass("event-button decline-event-button button-disabled");
				} else {
					if (!$GLOBALS['app']->Session->Logged()) {
						$declineButton->AddEvent(ON_CLICK, "javascript: location.href = '".$GLOBALS['app']->GetSiteURL() . '/index.php?gadget=Users&action=DefaultAction&redirect_to='.urlencode($GLOBALS['app']->GetFullURL())."';");
					} else {
						$declineButton->AddEvent(ON_CLICK,
							"javascript: $(this).up('.news-body').down('.comment-entry').value = 'is not attending.'; saveCalendarReply(0, ".$p['id'].", $(this).up('.news-item').id.replace('news-', ''));");
					}
					$declineButton->SetClass("event-button decline-event-button");
				}
				$declineButton->SetStyle("min-width: 40px; font-size: 12px; margin: 0px;");
				$tpl->SetVariable('button_decline', $declineButton->Get());
				$tpl->ParseBlock('comment/actions');
				
				$tpl->ParseBlock('comment');
				$event_comment = $tpl->Get();
				
				// Add this product as a comment
				$edit_links = array();
				if (
					$GLOBALS['app']->Session->GetPermission('Calendar', 'ManageEvents') || 
					((int)$p['ownerid'] > 0 && (int)$p['ownerid'] == $viewer_id)
				) {
					$edit_links = array(
						0 => array(
							'url' => $GLOBALS['app']->GetSiteURL('', false, 'http')."/index.php?gadget=Calendar&action=account_A_form&id=".$p['id'],
							'title' => "Edit this Event"
						),
						1 => array(
							'url' => "javascript: if (confirm(confirmCommentDelete)){location.href='".$GLOBALS['app']->GetSiteURL('', false, 'http')."/index.php?gadget=Calendar&action=account_form_post&fuseaction=DeleteEvent&id=".$p['id']."';};",
							'title' => "Delete this Event"
						),
						2 => array(
							'url' => $event_url,
							'title' => "View this Event"
						)
					);
				}
				$result = array(
					'id' => 'event'.$p['id'],
					'name' => $user_name,
					'email' => $user_email,
					'url' => $user_link,
					'title' => '',
					'msg_key' => md5(''.$user_email.$event_comment.$p['ownerid'].$p['id'].(0)),
					'msg_txt' => $event_comment,
					'image' => $user_image,
					'type' => 'event',
					'sharing' => 'everyone',
					'status' => 'approved',
					'gadget' => 'Calendar',
					'gadget_reference' => $p['id'],
					'parent' => 0,
					'replies' => $reply_comments,
					'edit_links' => $edit_links,
					'ownerid' => $p['ownerid'],
					'targetid' => $p['ownerid'],
					'preactivity' => 'added an event',
					'activity' => '<img border="0" align="top" style="height: 16px; width: 16px; padding-top: 2px" src="'.$GLOBALS['app']->GetJawsURL().'/gadgets/Calendar/images/logo.png"> via Calendar',
					'permalink' => $event_url,
					'createtime' => $p['updated']
				);
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
		$tpl = new Jaws_Template('gadgets/Calendar/templates/');
		$tpl->Load('SortingComments.html');
		if ($public === false) {
			$tpl->SetBlock('private');
			$tpl->SetVariable('title', $title);
			$tpl->SetVariable('site_url', $GLOBALS['app']->GetSiteURL());
			$tpl->SetVariable('OwnerID', (!is_null($OwnerID) ? $OwnerID : ''));
			$tpl->SetVariable('gadget', 'Calendar');
			$tpl->ParseBlock('private');
		} else {
			$tpl->SetBlock('public');
			$tpl->SetVariable('title', $title);
			$tpl->SetVariable('site_url', $GLOBALS['app']->GetSiteURL());
			$tpl->SetVariable('OwnerID', (!is_null($OwnerID) ? $OwnerID : ''));
			$tpl->SetVariable('gadget', 'Calendar');
			$tpl->ParseBlock('public');
		}
		$result = $tpl->Get();
		return $result;
    }
}
