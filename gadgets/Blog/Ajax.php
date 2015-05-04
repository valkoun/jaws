<?php
/**
 * Blog AJAX API
 *
 * @category   Ajax
 * @package    Blog
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2015 Alan Valkoun
 */
class BlogAjax extends Jaws_Ajax
{
    // {{{ Constructor PHP4
    /**
     * PHP 4 Constructor
     *
     * @access  public
     */
    function BlogAjax(&$model)
    {
        $this->_Model =& $model;
    }

    // }}}
    // {{{ Function DeletePage
    /**
     * Deletes a category.
     *
     * @access  public
     * @param   int     $id  Blog category ID
     * @return  array   Response (notice or error)
     */
    function DeleteCategory($id)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Blog', 'ManageCategories')) {
			$this->CheckSession('Blog', 'ManageCategories');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Blog', 'AdminModel');
        $gadget->DeleteCategory($id);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Deletes an entry.
     *
     * @access  public
     * @param   int     $id  Entry ID
     * @return  array   Response (notice or error)
     */
    function DeleteEntry($id)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Blog', 'DeleteEntries') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Blog', 'DeleteEntries')) {
			$this->CheckSession('Blog', 'DeleteEntries');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Blog', 'AdminModel');
        $gadget->DeleteEntry($id);
        return $GLOBALS['app']->Session->PopLastResponse();
    }


	/**
     * Deletes a post
     *
     * @access  public
     * @param   int     $pid  Post ID
     * @return  array   Response (notice or error)
     */
    function DeletePost($pid)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Blog', 'DeleteEntries') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Blog', 'DeleteEntries')) {
			$this->CheckSession('Blog', 'DeleteEntries');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Blog', 'AdminModel');
        $gadget->DeletePost($id);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

	/**
     * Hides RSS item
     *
     * @param   int  $pid  page ID
     * @param   string  $title  title of Rss item
     * @param   string  $published  date of Rss item
     * @param   string  $url  url of Rss item
     * @access  public
     * @return  array   Response (notice or error)
     */
    function HideRss($pid, $title, $published, $url)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Blog', 'ManageCategories')) {
			$this->CheckSession('Blog', 'ManageCategories');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Blog', 'AdminModel');
        $gadget->HideRss($pid, $title, $published, $url);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

     /**
     * Shows RSS item
     *
     * @param   int  $pid  page ID
     * @param   string  $title  title of Rss item
     * @param   string  $published  date of Rss item
     * @param   string  $url  url of Rss item
     * @access  public
     * @return  array   Response (notice or error)
     */
    function ShowRss($pid, $title, $published, $url)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Blog', 'ManageCategories')) {
			$this->CheckSession('Blog', 'ManageCategories');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Blog', 'AdminModel');
        $gadget->ShowRss($pid, $title, $published, $url);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Executes a massive-delete of pages
     *
     * @access  public
     * @param   array   $pages  Array with the ids of pages
     * @return  array   Response (notice or error)
     */
    function MassiveDelete($pages)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Blog', 'ManageCategories')) {
			$this->CheckSession('Blog', 'ManageCategories');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Blog', 'AdminModel');
        $gadget->MassiveDelete($pages);
        return $GLOBALS['app']->Session->PopLastResponse();
    }
        
    /**
     * Get total pages of a search
     *
     * @access  public
     * @param   string  $status  Status of page(s) we want to display
     * @param   string  $search  Keyword (title/description) of pages we want to look for
     * @return  int     Total of posts
     */
    function SizeOfSearch($status, $search)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Blog', 'ManageCategories')) {
			$this->CheckSession('Blog', 'ManageCategories');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Blog', 'AdminModel');
        $pages = $gadget->SearchCategories($status, $search, null);
        return count($pages);
    }

    /**
     * Returns an array with all the pages
     *
     * @access  public
     * @param   string  $status  Status of page(s) we want to display
     * @param   string  $search  Keyword (title/description) of pages we want to look for
     * @param   int     $limit   Data limit
     * @return  array   Pages data
     */
    function SearchCategories($status, $search, $limit)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Blog', 'ManageCategories')) {
			$this->CheckSession('Blog', 'ManageCategories');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Blog', 'AdminHTML');
        if (!is_numeric($limit)) {
            $limit = 0;
        }
		return $gadget->GetCategories($status, $search, $limit, $GLOBALS['app']->Session->GetAttribute('user_id'));
    }

    /**
     * This function will perform an autodraft of the content and set
     * it's value to not published, which will later be changed when the
     * user clicks on save.
     *
     * @access public
     * @param int    $id        The id of the staticpage id to update
     * @param string $fast_url  The value of the fast_url. This will
     *                          be autocreated if nothing is passed.
     * @param bool   $showtitle This will to know if we show the title or not.
     * @param string $title     The new autosaved title
     * @param string $description   The description of the page
     * @param string $keywords  The keywords of the page
     * @param bool   $active If the item is published or not. Default: draft
     */
    function AutoDraft($id = '', $fast_url = '', $showtitle = '', $title = '', $description = '',
                       $keywords = '', $active = '', $gadget, $fieldnames, $fieldvalues)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Blog', 'AddEntries') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Blog', 'AddEntries')) {
			$this->CheckSession('Blog', 'AddEntries');
		}

        /*
		if ($id == 'NEW') {
            $this->_Model->AddPage($fast_url, $show_title, $title, $content, $language, $published, true);
            $newid    = $GLOBALS['db']->lastInsertID('static_pages', 'id');
            $response['id'] = $newid;
            $response['message'] = _t('PROPERTIES_PAGE_AUTOUPDATED',
                                      date('H:i:s'),
                                      (int)$id,
                                      date('D, d'));
            $GLOBALS['app']->Session->PushLastResponse($response, RESPONSE_NOTICE);
        } else {
            $this->_Model->UpdatePage($id, $fast_url, $showtitle, $title, $content, $language, $published, true);
        }
        return $GLOBALS['app']->Session->PopLastResponse();
		*/
		return true;
	}

     /**
     * Moves an item in the sort_order
     *
     * @access  public
     * @param   int     $pid  Post ID
     * @param   string  $direction  'up', or 'down'
     * @return  array   Response (notice or error)
     */
    function SortItem($pids, $newsorts, $table)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Blog', 'ManageCategories')) {
			$this->CheckSession('Blog', 'ManageCategories');
		}
		$res = array();
        $gadget = $GLOBALS['app']->LoadGadget('Blog', 'AdminModel');
        $sort = $gadget->SortItem($pids, $newsorts, $table);
        if ($sort === false) {
            $res['success'] = false;
        } else {
            //$res['id'] = (int)$pid;
            //if ($direction == 'up') {
			//	$res['moved'] = -1;
            //} else {
			//	$res['moved'] = 1;
			//}
			$res['success'] = true;
        }
        $res['message'] = $GLOBALS['app']->Session->PopLastResponse();
        return $res;
    }
	
    /**
     * Returns entry row
     *
     * @access  public
     * @param   integer  $id  ID of the entry
     * @return  array   DB result
     */
    function GetEntry($id)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Blog', 'ManageCategories')) {
			$this->CheckSession('Blog', 'ManageCategories');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Blog', 'Model');
		$property = $gadget->GetEntry($id);
		if (Jaws_Error::IsError($property)) {
			return false;
		} else {
			if ($property['user_id'] === $GLOBALS['app']->Session->GetAttribute('user_id')) {
				return $property;
			} else {
				return false;
			}
		}
	}

    /**
     * Adds a form quickly
     *
     * @access public
     * @param string	$method	The method to call
     * @param array	$params	The params to pass to method
     * @param string	$callback	The method to call afterwards
     * @return  array	Response (notice or error)
     */
    function SaveQuickAdd($addtype = 'CustomPage', $method, $params, $callback = '') 
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Blog', 'AddEntries') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Blog', 'AddEntries')) {
			$this->CheckSession('Blog', 'AddEntries');
		}
		require_once JAWS_PATH . 'include/Jaws/Image.php';
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$adminHTML = $GLOBALS['app']->LoadGadget('Blog', 'AdminHTML');
		$model = $GLOBALS['app']->LoadGadget('Blog', 'Model');
		
		$shout_params = array();
		$shout_params['gadget'] = 'Blog';
		$res = array();
		
		// Which method
		$result = $adminHTML->form_post(true, $method, $params);
		if ($result === false || Jaws_Error::IsError($result)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_SAVE_QUICKADD'), RESPONSE_ERROR);
			$res['success'] = false;
		} else {
			$id = $result;
			if ($method == 'AddCategory' || $method == 'EditCategory') {
				$post = $model->GetProductParent($id);
			} else if ($method == 'AddEntry' || $method == 'EditEntry') {
				$post = $model->GetProduct($id);
			} else if ($method == 'AddPost' || $method == 'EditPost') {
				$post = $model->GetPost($id);
			}
		}
		if ($post && !Jaws_Error::IsError($post)) {
			if ($method == 'AddCategory' || $method == 'EditCategory') {
				$action_str = (substr($method, 0, 3) == 'Add' ? "has added a new Blog category" : '');
				if ($addtype == 'Comment') {
					$shout_params['edit_url'] = $GLOBALS['app']->GetSiteURL() .'/'. $GLOBALS['app']->Map->GetURLFor('Blog', 'ShowCategory', array('id' => $id));
				} else {
					$shout_params['edit_url'] = $GLOBALS['app']->GetSiteURL() .'/index.php?gadget=Blog&action=account_form&id='.$id;
				}
				$image = '';
				$title = $post['name'];
				$description = $post['description'];
			} else if ($method == 'AddEntry' || $method == 'EditEntry') {
				$action_str = (substr($method, 0, 3) == 'Add' ? "has added a new Blog entry" : '');
				if ($addtype == 'Comment') {
					$shout_params['edit_url'] = $GLOBALS['app']->GetSiteURL() .'/'. $GLOBALS['app']->Map->GetURLFor('Blog', 'SingleView', array('id' => $post['fast_url']));
					$post['html'] = '';
					$hook = $GLOBALS['app']->loadHook('Blog', 'Comment');
					if ($hook !== false) {
						if (method_exists($hook, 'GetBlogComment')) {
							$comment = $hook->GetBlogComment(array('gadget_reference' => $post['id'], 'public' => false));
							if (!Jaws_Error::IsError($comment) && isset($comment['msg_txt']) && !empty($comment['msg_txt'])) {
								$post['html'] = $comment['msg_txt'];
							}
						}
					}
				} else {
					$shout_params['edit_url'] = $GLOBALS['app']->GetSiteURL() .'/index.php?gadget=Blog&action=account_A_form&id='.$id;
				}
				$image = $post['image'];
				$title = $post['title'];
				$description = $post['description'];
			} else if ($method == 'AddPost' || $method == 'EditPost') {
				$action_str = (substr($method, 0, 3) == 'Add' ? "has added info to a Blog entry" : '');
				if ($addtype == 'Comment') {
					$shout_params['edit_url'] = '';
				} else {
					$shout_params['edit_url'] = $GLOBALS['app']->GetSiteURL() .'/index.php?gadget=Blog&action=account_A_form2&id='.$id;
				}
				$image = $post['image'];
				$title = $post['title'];
				$description = $post['description'];
			}
			$el = array();
			$el = $post;
			// TODO: Return different array if callback is requested ("notify" mode)
			if (!empty($callback)) {
			} else {
				$image_src = '';
				$el['tname'] = $title;
				$el['taction'] = $action_str;
				$el['tactiondesc'] = substr(strip_tags($description), 0, 100).(strlen(strip_tags($description)) > 100 ? '...' : '');
				if (!empty($image)) {
					if (isset($image) && !empty($image)) {
						$image = $xss->filter(strip_tags($image));
						if (substr(strtolower($image), 0, 4) == "http") {
							if (substr(strtolower($image), 0, 7) == "http://") {
								$image_src = explode('http://', $image);
								foreach ($image_src as $img_src) {
									if (!empty($img_src)) {
										$image_src = 'http://'.$img_src;
										break;
									}
								}
							} else {
								$image_src = explode('https://', $image);
								foreach ($image_src as $img_src) {
									if (!empty($img_src)) {
										$image_src = 'https://'.$img_src;
										break;
									}
								}
							}
						} else {
							$thumb = Jaws_Image::GetThumbPath($image);
							$medium = Jaws_Image::GetMediumPath($image);
							if (file_exists(JAWS_DATA . 'files'.$thumb)) {
								$image_src = $GLOBALS['app']->getDataURL() . 'files'.$thumb;
							} else if (file_exists(JAWS_DATA . 'files'.$medium)) {
								$image_src = $GLOBALS['app']->getDataURL() . 'files'.$medium;
							} else if (file_exists(JAWS_DATA . 'files'.$image)) {
								$image_src = $GLOBALS['app']->getDataURL() . 'files'.$image;
							}
						}
					}
				}
				$el['icon'] = $GLOBALS['app']->GetJawsURL() . '/gadgets/Blog/images/logo.png';
				//$url_ea = $GLOBALS['app']->getSiteURL() .'/'. BASE_SCRIPT. '?gadget=CustomPage&action=EditElementAction&id='.$id.'&method='.str_replace('Add', 'Edit', $method);
				$url_ea = $shout_params['edit_url'];
				$el['eaurl'] = $url_ea;
				$el['image_thumb'] = $image_src;
				$el['eaid'] = 'ea'.$id;
				//$el['section_id'] = $post['section_id'];
			}
			$res = $el;
			$res['success'] = true;
			$res['method'] = $method;
			$res['addtype'] = $addtype;
			if (isset($params['sharing']) && !empty($params['sharing'])) {
				$res['sharing'] = $params['sharing'];
			}
		} else {
			//$GLOBALS['app']->Session->PushLastResponse(_t('CUSTOMPAGE_ERROR_POST_NOT_ADDED'), RESPONSE_ERROR);
			$GLOBALS['app']->Session->PushLastResponse($post->GetMessage(), RESPONSE_ERROR);
			$res['success'] = false;
		}
		if (!empty($callback)) {
			// Let everyone know content has been added
			$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
			$res = $GLOBALS['app']->Shouter->Shout($callback, $shout_params);
			if (!Jaws_Error::IsError($res)) {
				$GLOBALS['app']->Session->PushLastResponse($res->GetMessage(), RESPONSE_ERROR);
				$res['success'] = false;
			}
		}
		
        $res['message'] = $GLOBALS['app']->Session->PopLastResponse();
        return $res;
	}

	/**
     * Adds a comment
     *
     * @access  public
     * @param   string  $title      Title of the comment
     * @param   string  $comments   Text of the comment
     * @param   int     $parent     ID of the parent comment
     * @param   int     $parentId   ID of the entry
     * @param   string  $ip         IP of the author
     * @param   boolean $set_cookie Create a cookie
     * @return  boolean True if comment was added, and false if not.
     */
    function NewBlogComment($title = '', $comments, $parent, $parentId, $ip = '', $set_cookie = true, $sharing = 'everyone', $reply = false)
    {
        $res = array();
                    //$sql = 'UPDATE [[blog]] SET [comments] = [comments] - 1 WHERE [id] = {id}';
                    //$updateCount = $GLOBALS['db']->query($sql, array('id' => 1));
		if (!$GLOBALS['app']->Session->Logged()) {
	        //require_once JAWS_PATH . 'include/Jaws/Header.php';
			//Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'));
			//$GLOBALS['app']->Session->PushLastResponse("User not logged in.", RESPONSE_ERROR);
			$this->CheckSession('Blog', 'ManageCategories');
		} else {
			if (empty($parentId)) {
				$parentId = $GLOBALS['app']->Session->GetAttribute('user_id');
			}
			require_once JAWS_PATH . 'include/Jaws/User.php';
			$jUser = new Jaws_User();
			$info = $jUser->GetUserInfoById((int)$GLOBALS['app']->Session->GetAttribute('user_id'), true, true, true, true);
			$model = $GLOBALS['app']->LoadGadget('Users', 'Model');
			$result = $model->NewComment(
				(!empty($info['company']) ? $info['company'] : $info['nickname']), $title, $info['url'], $info['email'], $comments, 
				(int)$parent, (int)$parentId, $ip, $set_cookie, (int)$GLOBALS['app']->Session->GetAttribute('user_id'), $sharing, 'Blog'
			);
			if (Jaws_Error::IsError($result)) {
				$res['css'] = 'error-message';
				$res['message'] = $result->GetMessage();
			} else {
				
				$res['css'] = 'notice-message';
				$res['message'] = _t('GLOBAL_COMMENT_ADDED');
				$res['id'] = $result['id'];
				$res['link'] = $result['link'];
				if ((int)$parent == 0 && $reply === false) {
					// Update comment count in Blog entry
					$sql = 'UPDATE [[blog]] SET [comments] = [comments] + 1 WHERE [id] = {id}';
					$params       = array();
					$params['id'] = (int)$parentId;

					$updateCount = $GLOBALS['db']->query($sql, $params);
					if (Jaws_Error::IsError($updateCount)) {
						$res = array();
						$res['css'] = 'error-message';
						$res['message'] = $updateCount->GetMessage();
						return $res;
					}
					$res['image'] = (!empty($result['link']) ? '<a href="'.$result['link'].'">' : '').'<img src="'.$result['avatar_source'].'" border="0" align="left" />'.(!empty($result['link']) ? '</a>' : '');
					if (!empty($result['image'])) {
						$res['image'] = (!empty($result['link']) ? '<a href="'.$result['link'].'">' : '').'<img src="'.$result['image'].'" border="0" align="left" />'.(!empty($result['link']) ? '</a>' : '');
					}
				} else {
					// Get Blog entry ID for comment, and update counter
					$params = array();
					if ((int)$parent > 0) {
						require_once JAWS_PATH . 'include/Jaws/Comment.php';
						while ((int)$parent > 0) {
							$sql = '
								SELECT [id], [gadget_reference], [parent] FROM [[comments]]
								WHERE [id] = {id}';

							$parentComment = $GLOBALS['db']->queryRow($sql, array('id' => (int)$parent));
							if (Jaws_Error::IsError($parentComment) || !isset($parentComment['id']) || empty($parentComment['id'])) {
								$res = array();
								$res['css'] = 'error-message';
								$res['message'] = $updateCount->GetMessage();
								return $res;
							} else {
								$parent = $parentComment['parent'];
							}
						}
						$params['id'] = (int)$parentComment['gadget_reference'];
					} else {
						$params['id'] = (int)$parentId;
					}

					$sql = 'UPDATE [[blog]] SET [comments] = [comments] + 1 WHERE [id] = {id}';
					$updateCount = $GLOBALS['db']->query($sql, $params);
					if (Jaws_Error::IsError($updateCount)) {
						$res = array();
						$res['css'] = 'error-message';
						$res['message'] = $updateCount->GetMessage();
						return $res;
					}
					$res['image'] = '<div class="comment-image-holder">'.(!empty($result['link']) ? '<a href="'.$result['link'].'">' : '').'<img src="'.$result['avatar_source'].'" border="0" align="left" class="comment-image" />'.(!empty($result['link']) ? '</a>' : '').'</div>';
					if (!empty($result['image'])) {
						$res['image'] = '<div class="comment-image-holder">'.(!empty($result['link']) ? '<a href="'.$result['link'].'">' : '').'<img src="'.$result['image'].'" border="0" align="left" class="comment-image" />'.(!empty($result['link']) ? '</a>' : '').'</div>';
					}
				}
				$res['name'] = $result['name'];
				$full_style = '';
				$preview_style = ' style="display: none;"';
				//$msg_reply = strip_tags($result['comment']);
				$msg_reply = $result['comment'];
				$msg_reply_preview = '';
				/*
				if (strlen($msg_reply) > 150) {
					$msg_reply_preview = substr($msg_reply, 0, 150).'&nbsp;<a class="comment-showhide" href="javascript:void(0);" onclick="toggleFullComment('.$result['id'].');">Read it</a>';
					$msg_reply .= '&nbsp;<a class="comment-showhide" href="javascript:void(0);" onclick="toggleFullComment('.$result['id'].');">Hide it</a>';
					$preview_style = '';
					$full_style = ' style="display: none;"';
				}
				*/
				$res['full_style'] = $full_style;
				$res['preview_style'] = $preview_style;
				$res['comment'] = $msg_reply;
				$res['preview_comment'] = $msg_reply_preview;
				$res['title'] = $result['title'];
				$res['created'] = $result['created'];
				$res['permalink'] = $result['permalink'];
				// Let everyone know
				$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
				$shout = $GLOBALS['app']->Shouter->Shout('onBeforeSocialSharing', array('url' => $result['permalink']));
				if (!Jaws_Error::IsError($shout) && (isset($shout['url']) && !empty($shout['url']))){
					$res['permalink'] = $shout['url'];
				}
				$res['activity'] = '';
			}
		}
		return $res;
    }
	
    /**
     * Deletes a comment
     *
     * @access  public
     * @param   int     $id   Comment ID
     * @return  array   Response (notice or error)
     */
    function DeleteBlogComment($id)
    {
		if (!$GLOBALS['app']->Session->Logged()) {
	        //require_once JAWS_PATH . 'include/Jaws/Header.php';
			//Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'));
			//$GLOBALS['app']->Session->PushLastResponse("User not logged in.", RESPONSE_ERROR);
			$this->CheckSession('Blog', 'ManageCategories');
		} else {
			$uid = $GLOBALS['app']->Session->GetAttribute('user_id');
			$params 		= array();
			$params['id']   = (int)$id;
					
			$sql = '
				SELECT
					[gadget], [gadget_reference], [parent], [ownerid]
				FROM [[comments]]
				WHERE [id] = {id}';

			$gadget = $GLOBALS['db']->queryRow($sql, $params);
			if (Jaws_Error::IsError($gadget) || !isset($gadget['gadget']) || empty($gadget['gadget'])) {
				$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_COMMENT_DELETED'), RESPONSE_ERROR);
				return $GLOBALS['app']->Session->PopLastResponse();
			}
			// Is this a child comment of current user? They can delete it...
			if ((int)$gadget['parent'] > 0) {
				$params 		= array();
				$params['id']	= (int)$gadget['parent'];
						
				$sql = '
					SELECT
						[gadget], [gadget_reference], [parent], [ownerid]
					FROM [[comments]]
					WHERE [id] = {id}';

				$parent = $GLOBALS['db']->queryRow($sql, $params);
				if (Jaws_Error::IsError($parent) || !isset($parent['gadget']) || empty($parent['gadget'])) {
					$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_COMMENT_DELETED'), RESPONSE_ERROR);
					return $GLOBALS['app']->Session->PopLastResponse();
				}
			} else {
				$parent = $gadget;
			}
			if ($uid != $gadget['ownerid'] && (isset($parent['ownerid']) && $uid != $parent['ownerid'])) {
				$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_COMMENT_DELETED'), RESPONSE_ERROR);
			} else {
				$model = $GLOBALS['app']->LoadGadget('Users', 'Model');
				$delete = $model->DeleteComment($id, $gadget['gadget']);
				if (!Jaws_Error::IsError($delete)) {
					// Get Blog entry ID for comment, and update counter
					if ((int)$parent['parent'] > 0) {
						require_once JAWS_PATH . 'include/Jaws/Comment.php';
						while ((int)$parent['parent'] > 0) {
							$sql = '
								SELECT [id], [gadget_reference], [parent] FROM [[comments]]
								WHERE [id] = {id}';

							$parent = $GLOBALS['db']->queryRow($sql, array('id' => (int)$parent['parent']));
							if (Jaws_Error::IsError($parent) || !isset($parent['id']) || empty($parent['id'])) {
								$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_COMMENT_DELETED'), RESPONSE_ERROR);
								return $GLOBALS['app']->Session->PopLastResponse();
							}
						}
					}
                    $params       = array();
					$params['id'] = (int)$parent['gadget_reference'];
					
					// Update comment count in Blog entry
                    $sql = 'UPDATE [[blog]] SET [comments] = [comments] - 1 WHERE [id] = {id}';
                    $updateCount = $GLOBALS['db']->query($sql, $params);
                    if (Jaws_Error::IsError($updateCount)) {
						$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_COMMENT_DELETED'), RESPONSE_ERROR);
					} else {
						$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_COMMENT_DELETED'), RESPONSE_NOTICE);
					}
				} else {
					$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_COMMENT_DELETED'), RESPONSE_ERROR);
				}
			}
		}
        return $GLOBALS['app']->Session->PopLastResponse();
    }

}
