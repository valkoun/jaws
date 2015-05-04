<?php
/**
 * FlashGallery AJAX API
 *
 * @category   Ajax
 * @package    FlashGallery
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class FlashGalleryAdminAjax extends Jaws_Ajax
{
    // {{{ Constructor PHP4
    /**
     * PHP 4 Constructor
     *
     * @access  public
     */
    function FlashGalleryAdminAjax(&$model)
    {
        $this->_Model =& $model;
    }

    // }}}
    // {{{ Function DeleteFlashGallery
    /**
     * Deletes a gallery and all posts of it.
     *
     * @access  public
     * @param   int     $id  Gallery ID
     * @return  array   Response (notice or error)
     */
    function DeleteFlashGallery($id)
    {
		$this->CheckSession('FlashGallery', 'ManageFlashGalleries');
        $this->_Model->DeleteFlashGallery($id);
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
		$this->CheckSession('FlashGallery', 'ManageFlashGalleries');
        $this->_Model->DeletePost($pid);
        return $GLOBALS['app']->Session->PopLastResponse();
    }


     /**
     * Moves an item in the sort_order
     *
     * @access  public
     * @param   int     $pid  Post ID
     * @param   string  $direction  'up', or 'down'
     * @return  array   Response (notice or error)
     */
    function SortItem($pids, $newsorts)
    {
		$this->CheckSession('FlashGallery', 'ManageFlashGalleries');
		$res = array();
		$sort = $this->_Model->SortItem($pids, $newsorts);
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
     * Executes a massive-delete of galleries
     *
     * @access  public
     * @param   array   $pages  Array with the ids of pages
     * @return  array   Response (notice or error)
     */
    function MassiveDelete($pages)
    {
		$this->CheckSession('FlashGallery', 'ManageFlashGalleries');
        $this->_Model->MassiveDelete($pages);
        return $GLOBALS['app']->Session->PopLastResponse();
    }
    
    /**
     * Get total galleries of a search
     *
     * @access  public
     * @param   string  $status  Status of gallery(s) we want to display
     * @param   string  $search  Keyword (title/description) of galleries we want to look for
     * @return  int     Total of posts
     */
    function SizeOfSearch($status, $search)
    {
		$this->CheckSession('FlashGallery', 'ManageFlashGalleries');
        $pages = $this->_Model->SearchGalleries($status, $search, null, 0);
        return count($pages);
    }

    /**
     * Returns an array with all the galleries
     *
     * @access  public
     * @param   string  $status  Status of galleries(s) we want to display
     * @param   string  $search  Keyword (title/description) of galleries we want to look for
     * @param   int     $limit   Data limit
     * @return  array   Galleries data
     */
    function SearchGalleries($status, $search, $limit)
    {
		$this->CheckSession('FlashGallery', 'ManageFlashGalleries');
        $gadget = $GLOBALS['app']->LoadGadget('FlashGallery', 'AdminHTML');
        if (!is_numeric($limit)) {
            $limit = 0;
        }
        return $gadget->GetFlashGalleries($status, $search, $limit, 0);
    }

    /**
     * Adds URL to embed_gadgets
     *
     * @access  public
     * @params  string  $gadget
     * @return  array   Actions of the given gadget
     */
    function AddEmbedSite($gadget, $url, $gadget_url, $layout)
    {
        $this->CheckSession('FlashGallery', 'default');
		$gadget = $GLOBALS['app']->LoadGadget('Users', 'AdminModel');
		return $gadget->AddEmbedSite($gadget, $url, $gadget_url, $layout);
    }

    /**
     * Returns gallery LayoutHTML
     *
     * @access  public
     * @return  string   Galleries data
     */
    function ShowGallery($id = 1, $postid)
    {
		$this->CheckSession('FlashGallery', 'default');
        $gadget = $GLOBALS['app']->LoadGadget('FlashGallery', 'LayoutHTML');
        if (is_numeric($id)) {
			$galleryHTML = $gadget->Slideshow($id);
			$galleryHTML = str_replace("Event.observe(window,\"load\",function(){", "var i = 0; while(i < slideshow".$id."_slides.length){if (slideshow".$id."_slides[i] == 'image-'+".$postid."){slideshow".$id."_slides.splice(i, 1);}else{i++;}};for(n=0;n<slideshow".$id."_slides.length;n++){alert(slideshow".$id."_slides[n]);}", $galleryHTML);
			$galleryHTML = str_replace("}});", "}", $galleryHTML);
			return $galleryHTML;
        } else {
			return '';
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
		$this->CheckSession('FlashGallery', 'default');
		require_once JAWS_PATH . 'include/Jaws/Image.php';
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$adminHTML = $GLOBALS['app']->LoadGadget('FlashGallery', 'AdminHTML');
		$model = $GLOBALS['app']->LoadGadget('FlashGallery', 'Model');
		
		$shout_params = array();
		$shout_params['gadget'] = 'FlashGallery';
		$res = array();
		
		// Which method
		$result = $adminHTML->form_post(false, $method, $params);
		if ($result === false || Jaws_Error::IsError($result)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('FLASHGALLERY_ERROR_SAVE_QUICKADD'), RESPONSE_ERROR);
			$res['success'] = false;
		} else {
			$id = $result;
			if ($method == 'AddFlashGallery' || $method == 'EditFlashGallery') {
				$post = $model->GetFlashGallery($id);
			} else if ($method == 'AddPost' || $method == 'EditPost') {
				$post = $model->GetPost($id);
			}
		}
		if ($post && !Jaws_Error::IsError($post)) {
			$shout_params['edit_url'] = $GLOBALS['app']->GetSiteURL() .'/admin.php?gadget=FlashGallery&action='.($method == 'AddPost' || $method == 'EditPost' ? 'A_' : '').'form&id='.$id;
			$el = array();
			$el = $post;
			// TODO: Return different array if callback is requested ("notify" mode)
			if (!empty($callback)) {
			} else {
				$image_src = '';
				$image = $post['image'];
				$el['tname'] = $post['title'];
				$el['taction'] = '';
				$el['tactiondesc'] = substr(strip_tags($post['description']), 0, 100).(strlen(strip_tags($post['description'])) > 100 ? '...' : '');
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
				$el['icon'] = $GLOBALS['app']->GetJawsURL() . '/gadgets/FlashGallery/images/logo.png';
				//$url_ea = $GLOBALS['app']->getSiteURL() .'/'. BASE_SCRIPT. '?gadget=CustomPage&action=EditElementAction&id='.$id.'&method=EditPost';
				$url_ea = $shout_params['edit_url'];
				$el['eaurl'] = $url_ea;
				$el['image_thumb'] = $image_src;
				$el['eaid'] = 'ea'.$id;
				//$el['section_id'] = $post['section_id'];
			}
			$res = $el;
			$res['success'] = true;
			$res['addtype'] = $addtype;
			$res['method'] = $method;
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
    function NewComment($title = '', $comments, $parent, $parentId, $ip = '', $set_cookie = true, $sharing = 'everyone')
    {
        $res = array();
		if (!$GLOBALS['app']->Session->Logged()) {
	        //require_once JAWS_PATH . 'include/Jaws/Header.php';
			//Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'));
			$GLOBALS['app']->Session->PushLastResponse("User not logged in.", RESPONSE_ERROR);
		} else {
			if (empty($parentId)) {
				$parentId = $GLOBALS['app']->Session->GetAttribute('user_id');
			}
			require_once JAWS_PATH . 'include/Jaws/User.php';
			$jUser = new Jaws_User();
			$info = $jUser->GetUserInfoById((int)$GLOBALS['app']->Session->GetAttribute('user_id'), true, true, true, true);
			$model = $GLOBALS['app']->LoadGadget('Users', 'Model');
			$result = $model->NewComment(
				$info['nickname'], $title, $info['url'], $info['email'], $comments, (int)$parent, 
				(int)$parentId, $ip, $set_cookie, (int)$GLOBALS['app']->Session->GetAttribute('user_id'), 
				$sharing, 'FlashGallery', true
			);
			if (Jaws_Error::IsError($result)) {
				$res['css'] = 'error-message';
				$res['message'] = $result->GetMessage();
			} else {
				$res['css'] = 'notice-message';
				$res['message'] = _t('GLOBAL_COMMENT_ADDED');
				$res['id'] = $result['id'];
				$res['link'] = $result['link'];
				$image_src = $result['avatar_source'];
				if (!empty($result['image'])) {
					$image_src = $result['image'];
				}
				if ((int)$parent == 0) {
					$res['image'] = '<a href="'.$result['link'].'"><img src="'.$image_src.'" border="0" align="left" /></a>';
				} else {
					$res['image'] = '<div class="comment-image-holder"><a href="'.$result['link'].'"><img src="'.$image_src.'" border="0" align="left" class="comment-image" /></a></div>';
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
				$res['activity'] = '';
			}
		}
		return $res;
    }
}
?>
