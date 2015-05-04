<?php
/**
 * Ecommerce - Comment gadget hook
 *
 * @category   GadgetHook
 * @package    Ecommerce
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2011 Alan Valkoun
 */
class EcommerceCommentHook
{
	var $_comments_owners = array();
	
    /**
     * Returns an array with all comments in the Ecommerce gadget owned by given user
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
        $result_counter = 0;
        $result_offset = false;
        $res = array();
        $result = array();
		require_once JAWS_PATH . 'include/Jaws/Comment.php';
		require_once JAWS_PATH . 'include/Jaws/Gravatar.php';
        $api = new Jaws_Comment('Ecommerce');
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		//Load model
		$model = $GLOBALS['app']->loadGadget('Ecommerce', 'Model');
		$usersModel = $GLOBALS['app']->loadGadget('Users', 'Model');
		
		if ($public === true) {
			return $result;
		}
		
		if (!is_null($id)) {
			$p = $model->GetOrder((int)$id);
			if (!Jaws_Error::IsError($p) && isset($p['id']) && !empty($p['id'])) {
				if (
					$GLOBALS['app']->Session->GetPermission('Ecommerce', 'ManageEcommerce') || 
					((int)$p['ownerid'] > 0 && (int)$p['ownerid'] == $viewer_id) || 
					((int)$p['customer_id'] > 0 && (int)$p['customer_id'] == $viewer_id)
				) {
					$gadget_comments = $usersModel->GetCommentsFiltered('Ecommerce', 'postid', $id, 'approved', $result_limit);
					foreach ($gadget_comments as $item) {
						if (
							$usersModel->IsCommentSharedWithUser($item, 'Ecommerce', $viewer_id, $public) && 
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
			// Get orders owned by user
			$owned = $model->GetEcommerceOfUserID((int)$OwnerID, null, null, 'updated', 'DESC', $result_limit);
			if (!Jaws_Error::IsError($owned)) {
				foreach ($owned as $p) {
					$res[] = $p;
				}
			}
			// Get orders of customer
			$customer = $model->GetEcommerceOfUserID(null, (int)$OwnerID, null, 'updated', 'DESC', $result_limit);
			if (!Jaws_Error::IsError($customer)) {
				foreach ($customer as $p) {
					if ($page['active'] != 'TEMP' && strpos($page['description'], 's:12:"Handling fee"') === false) {
						$res[] = $p;
					}
				}
			}
			
			if ($public === false && $GLOBALS['app']->Session->GetPermission('Ecommerce', 'ManageEcommerce')) {
				$orders = $model->GetOrders($result_limit, 'updated', 'DESC', false, null);
				if (!Jaws_Error::IsError($orders)) {
					foreach ($orders as $p) {
						$res[] = $p;
					}
				}
			}
			
			foreach ($res as $r) {
				$item = $this->GetEcommerceComment(array('gadget_reference' => $r['id'], 'public' => $public));
				if (!Jaws_Error::IsError($item) && isset($item['id']) && !empty($item['id'])) {
					if (
						$usersModel->IsCommentSharedWithUser($item, 'Ecommerce', $viewer_id, $public) && 
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
											'type' => 'order',
											'sharing' => $pcomment['sharing'],
											'status' => 'approved',
											'gadget' => 'Ecommerce',
											'gadget_reference' => $r['id'],
											'replies' => $item['replies'],
											'ownerid' => $pcomment['ownerid'],
											'targetid' => $pcomment['targetid'],
											'preactivity' => $pcomment['preactivity'],
											'activity' => $item['activity'],
											'permalink' => $GLOBALS['app']->GetSiteURL() . '/' . $GLOBALS['app']->Map->GetURLFor('Users', 'ShowComment', array('id' => $pcomment['id'], 'fusegadget' => $pcomment['gadget'])),
											'createtime' => $pcomment['createtime']
										);
										if ($usersModel->IsCommentSharedWithUser($new_comment, 'Ecommerce', $viewer_id, $public)) {
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
     * Returns an array of single comment thread
     *
     * @access  public
     */
    function GetEcommerceComment($params = array())
    {
		$id = $params['gadget_reference'];
		$status_note = '';
		if (isset($params['status_note']) && !empty($params['status_note'])) {
			$status_note = $params['status_note'];
		}
		$public = $params['public'];
		$viewer_id = (int)$GLOBALS['app']->Session->GetAttribute('user_id');

		require_once JAWS_PATH . 'include/Jaws/Comment.php';
        $api = new Jaws_Comment('Ecommerce');
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$site_url = $GLOBALS['app']->GetSiteURL('', false, 'https');
		
        $result = array();
		
		if (!is_null($id)) {			
			$GLOBALS['app']->Registry->LoadFile('Ecommerce');
			$GLOBALS['app']->Translate->LoadTranslation('Ecommerce', JAWS_GADGET);
			require_once JAWS_PATH . 'include/Jaws/Image.php';
			$jUser = new Jaws_User;
			$model = $GLOBALS['app']->loadGadget('Ecommerce', 'Model');
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
			$p = $model->GetOrder((int)$id);
			if (!Jaws_Error::IsError($p) && isset($p['id']) && !empty($p['id'])) {
				if (
					$GLOBALS['app']->Session->GetPermission('Ecommerce', 'ManageEcommerce') || 
					((int)$p['ownerid'] > 0 && (int)$p['ownerid'] == $viewer_id) || 
					((int)$p['customer_id'] > 0 && (int)$p['customer_id'] == $viewer_id)
				) {
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
								$user_link_start = '<a href="'.$GLOBALS['app']->GetSiteURL().'/'.$GLOBALS['app']->Map->GetURLFor('Users', 'AccountPublic', array('name' => $userInfo['username'])).'" title="'.preg_replace("[^A-Za-z0-9\ ]", '', $user_fullname).'" name="'.preg_replace("[^A-Za-z0-9\ ]", '',$user_fullname).'">';
								$user_link_end = '</a>';
							}
						}
					}
							
					$user_link_string = " ".$user_link_start.$user_name.$user_link_end;					
					$order_url = $site_url.'/index.php?gadget=Ecommerce&action=account_view&id='.$p['id'];								
					$order_title = '';
					
					$tpl = new Jaws_Template('gadgets/Ecommerce/templates/');
					$tpl->Load('CommentOrder.html');
					$tpl->SetBlock('comment');
					$tpl->SetVariable('order_url', $order_url);
					$tpl->SetVariable('order_id', $p['id']);
					$tpl->SetVariable('owner_name', $user_name);
					$tpl->SetVariable('owner_link_start', $user_link_start);
					$tpl->SetVariable('owner_link_end', $user_link_end);
					$tpl->SetVariable('orderno', $p['orderno']);
					$tpl->SetVariable('order_total', $p['total']);

					if (file_exists(JAWS_PATH . 'gadgets/Ecommerce/images/logo.png')) {
						$tpl->SetBlock('comment/logo');
						$tpl->SetVariable('order_url', $order_url);
						$tpl->SetVariable('logo', $GLOBALS['app']->GetJawsURL().'/gadgets/Ecommerce/images/logo.png');
						$tpl->ParseBlock('comment/logo');
					}
					if (!empty($status_note)) {
						$tpl->SetBlock('comment/status_note');
						$tpl->SetVariable('status_note', strip_tags($status_note));
						$tpl->ParseBlock('comment/status_note');
					}
					$tpl->SetBlock('comment/status');
					$tpl->SetVariable('order_status', $xss->filter($p['active']));
					$tpl->ParseBlock('comment/status');
					
					$pInfo = unserialize($p['description']);
					//$pDesc = $pInfo['description'];
					//$tpl->SetVariable('order_description', $xss->filter($pDesc));
					if (isset($pInfo['items']) && is_array($pInfo['items']) && !count($pInfo['items']) <= 0) {
						$tpl->SetBlock('comment/items');
						$i = 0;
						foreach ($pInfo['items'] as $item_owner => $items) {
							$tpl->SetBlock('comment/items/item');
							$comma = '';
							if ($i > 0) {
								$comma = ", ";
							}
							$tpl->SetVariable('comma', $comma);
							$tpl->SetVariable('item_link', $items['itemurl']);
							$tpl->SetVariable('item_title', $xss->filter(preg_replace("[^A-Za-z0-9\:\ \,]", '', $items['name'])));
							$tpl->SetVariable('item_qty', $items['qty']);
							$tpl->ParseBlock('comment/items/item');
							$i++;
						}
						$tpl->ParseBlock('comment/items');
					}			
					$tpl->ParseBlock('comment');
					$order_comment = $tpl->Get();

					// Get comments of this
					$comments = $api->GetComments($p['id'], 0);
					$order_comments = 0;
					if (Jaws_Error::IsError($comments)) {
						//return array();
					} else {
						$order_comments = array();
						foreach ($comments as $comment) {
							$comment['preactivity'] = 'commented on <a href="'.$order_url.'">order '.$p['orderno'].'</a> from'.$user_link_string;
							$comment['activity'] = '<img border="0" align="top" style="height: 16px; width: 16px; padding-top: 2px" src="'.$GLOBALS['app']->GetJawsURL().'/gadgets/Store/images/logo.png"> via Store';
							$comment['targetid'] = (int)$p['ownerid'];
							$order_comments[] = $comment;
							
							$replies = $usersModel->GetCommentsOfParent($comment['id'], 'approved', '');
							if (Jaws_Error::IsError($replies)) {
								//return array();
							} else {
								foreach ($replies as $reply) {
									$order_comments[] = $reply;
								}
							}
						}
					}
					$reply_comments = $order_comments;
					
					// Add this product as a comment
					$edit_links = array();
					if (
						$GLOBALS['app']->Session->GetPermission('Ecommerce', 'ManageEcommerce') || 
						((int)$p['ownerid'] > 0 && (int)$p['ownerid'] == $viewer_id)
					) {
						$edit_links = array(
							0 => array(
								'url' => $GLOBALS['app']->GetSiteURL('', false, 'http')."/index.php?gadget=Ecommerce&action=account_form&id=".$p['id'],
								'title' => "Edit this Order"
							),
							1 => array(
								'url' => "javascript: if (confirm(confirmCommentDelete)){location.href='".$GLOBALS['app']->GetSiteURL('', false, 'http')."/index.php?gadget=Ecommerce&action=account_form_post&fuseaction=DeleteOrder&id=".$p['id']."';};",
								'title' => "Delete this Order"
							),
							2 => array(
								'url' => $order_url,
								'title' => "View this Order"
							)
						);
					}
					$result = array(
						'id' => 'order'.$p['id'],
						'name' => $user_name,
						'email' => $user_email,
						'url' => $user_link,
						'title' => '',
						'msg_key' => md5(''.$user_email.$order_comment.$p['ownerid'].$p['id'].(0)),
						'msg_txt' => $order_comment,
						'image' => '',
						'type' => 'order',
						'sharing' => 'users:'.$p['ownerid'].(isset($p['customer_id']) && !empty($p['customer_id']) ? ','.$p['customer_id'] : '').
							($GLOBALS['app']->Session->GetPermission('Ecommerce', 'ManageEcommerce') && 
							$viewer_id != (int)$p['ownerid'] && $viewer_id != (int)$p['customer_id'] ? ','.$viewer_id : ''),
						'status' => 'approved',
						'gadget' => 'Ecommerce',
						'gadget_reference' => $p['id'],
						'parent' => 0,
						'replies' => $reply_comments,
						'edit_links' => $edit_links,
						'ownerid' => (isset($p['customer_id']) && !empty($p['customer_id']) ? $p['customer_id'] : $p['ownerid']),
						'targetid' => $p['ownerid'],
						'preactivity' => '',
						'activity' => '<img border="0" align="top" style="height: 16px; width: 16px; padding-top: 2px" src="'.$GLOBALS['app']->GetJawsURL().'/gadgets/Store/images/logo.png"> via Store',
						'permalink' => $order_url,
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
			$tpl = new Jaws_Template('gadgets/Ecommerce/templates/');
			$tpl->Load('SortingComments.html');
			$tpl->SetBlock('private');
			$tpl->SetVariable('title', $title);
			$tpl->SetVariable('site_url', $GLOBALS['app']->GetSiteURL());
			$tpl->SetVariable('OwnerID', (!is_null($OwnerID) ? $OwnerID : ''));
			$tpl->SetVariable('gadget', 'Ecommerce');
			$tpl->ParseBlock('private');
			$result = $tpl->Get();
		}
		return $result;
    }
	
}
