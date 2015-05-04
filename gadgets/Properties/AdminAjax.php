<?php
/**
 * Properties AJAX API
 *
 * @category   Ajax
 * @package    Properties
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class PropertiesAdminAjax extends Jaws_Ajax
{
    // {{{ Constructor PHP4
    /**
     * PHP 4 Constructor
     *
     * @access  public
     */
    function PropertiesAdminAjax(&$model)
    {
        $this->_Model =& $model;
    }

    // }}}
    // {{{ Function DeletePage
    /**
     * Deletes a property parent.
     *
     * @access  public
     * @param   int     $id  Property parent ID
     * @return  array   Response (notice or error)
     */
    function DeletePropertyParent($id)
    {
        $this->CheckSession('Properties', 'ManageProperties');
        $this->_Model->DeletePropertyParent($id);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Deletes a property.
     *
     * @access  public
     * @param   int     $id  Property ID
     * @return  array   Response (notice or error)
     */
    function DeleteProperty($id)
    {
        $this->CheckSession('Properties', 'ManageProperties');
        $this->_Model->DeleteProperty($id);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Deletes a amenity.
     *
     * @access  public
     * @param   int     $id  Property ID
     * @return  array   Response (notice or error)
     */
    function DeleteAmenity($id)
    {
        $this->CheckSession('Properties', 'ManageProperties');
        $this->_Model->DeleteAmenity($id);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Deletes a amenity.
     *
     * @access  public
     * @param   int     $id  Property ID
     * @return  array   Response (notice or error)
     */
    function DeleteAmenityType($id)
    {
        $this->CheckSession('Properties', 'ManageProperties');
        $this->_Model->DeleteAmenityType($id);
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
        $this->CheckSession('Properties', 'ManageProperties');		
        $this->_Model->DeletePost($pid);
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
		$this->CheckSession('Properties', 'ManageProperties');
        $this->_Model->HideRss($pid, $title, $published, $url);
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
		$this->CheckSession('Properties', 'ManageProperties');
        $this->_Model->ShowRss($pid, $title, $published, $url);
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
        $this->CheckSession('Properties', 'ManageProperties');
        $this->_Model->MassiveDelete($pages);
        return $GLOBALS['app']->Session->PopLastResponse();
    }
        
     /**
     * Saves the address of a property
     *
     * @access  public
     * @param   int     $pid  Property ID
     * @param   string  $address  Address string to save
     * @return  array   Response (notice or error)
     */
    function SaveAddress($pid, $address)
    {
        $this->CheckSession('Properties', 'ManageProperties');		
        $this->_Model->SaveAddress($pid, $address);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

     /**
     * Save Settings form
     *
     * @access  public
     * @return  array   Response (notice or error)
     */
    function SaveSettings($showmap = 'Y', $user_post_limit = 6, $user_desc_char_limit = 650, $user_mask_owner_email = 'Y', $user_min_price = '0.00', $user_max_price = '0.00', $user_status_limit = 'forsale,forrent,forlease,undercontract,sold,rented,leased', $randomize = 'Y', $showcalendar = 'Y')
    {
		$this->CheckSession('Properties', 'ManageProperties');
        $this->_Model->SaveSettings($showmap, $user_post_limit, $user_desc_char_limit, $user_mask_owner_email, $user_min_price, $user_max_price, $user_status_limit, $randomize, $showcalendar);
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
        $this->CheckSession('Properties', 'default');
        $pages = $this->_Model->SearchPropertyParents($status, $search, null);
        return count($pages);
    }

    /**
     * Get total pages of a search
     *
     * @access  public
     * @param   string  $status  Status of page(s) we want to display
     * @param   string  $search  Keyword (title/description) of pages we want to look for
     * @return  int     Total of posts
     */
    function SizeOfSearch1($status, $search)
    {
        $this->CheckSession('Properties', 'default');
        $pages = $this->_Model->SearchAmenities($search, $status, null);
        return count($pages);
    }

    /**
     * Get total pages of a search
     *
     * @access  public
     * @param   string  $status  Status of page(s) we want to display
     * @param   string  $search  Keyword (title/description) of pages we want to look for
     * @return  int     Total of posts
     */
    function SizeOfSearch2($status, $search)
    {
        $this->CheckSession('Properties', 'default');
        $pages = $this->_Model->SearchAmenityTypes($status, $search, null);
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
    function SearchPropertyParents($status, $search, $limit)
    {
        $this->CheckSession('Properties', 'default');
        $gadget = $GLOBALS['app']->LoadGadget('Properties', 'AdminHTML');
        if (!is_numeric($limit)) {
            $limit = 0;
        }
		if ($GLOBALS['app']->Session->GetPermission('Properties', 'ManageProperties')) {
			return $gadget->GetPropertyParents($status, $search, $limit);
		} else {
			return $gadget->GetPropertyParents($status, $search, $limit, $GLOBALS['app']->Session->GetAttribute('user_id'));
		}
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
    function SearchAmenities($search, $status, $limit)
    {
        $this->CheckSession('Properties', 'default');
        $gadget = $GLOBALS['app']->LoadGadget('Properties', 'AdminHTML');
        if (!is_numeric($limit)) {
            $limit = 0;
        }
		if ($GLOBALS['app']->Session->GetPermission('Properties', 'ManageProperties')) {
			return $gadget->GetAmenities($status, $search, $limit);
		} else {
			return $gadget->GetAmenities($status, $search, $limit, $GLOBALS['app']->Session->GetAttribute('user_id'));
		}
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
    function SearchAmenityTypes($status, $search, $limit)
    {
        $this->CheckSession('Properties', 'default');
        $gadget = $GLOBALS['app']->LoadGadget('Properties', 'AdminHTML');
        if (!is_numeric($limit)) {
            $limit = 0;
        }
		if ($GLOBALS['app']->Session->GetPermission('Properties', 'ManageProperties')) {
			return $gadget->GetAmenityTypes($status, $search, $limit);
		} else {
			return $gadget->GetAmenityTypes($status, $search, $limit, $GLOBALS['app']->Session->GetAttribute('user_id'));
		}
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
        $this->CheckSession('Properties', 'default');

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
        $this->CheckSession('Properties', 'ManageProperties');		
		$res = array();
		$sort = $this->_Model->SortItem($pids, $newsorts, $table);
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
     * Returns an array with all the country DB table data
     *
     * @access  public
     * @param   integer  $id  ID of the parent
     * @return  array   country DB table data
     */
    function GetRegionsOfParent($id, $where = 'region')
    {
        //$this->CheckSession('Properties', 'default');
        $gadget = $GLOBALS['app']->LoadGadget('Properties', 'Model');
        if ($where == 'child') {
			$result = $gadget->GetGeonamesCitiesOfRegion($id);
		} else {
			$result = $gadget->GetRegionsOfParent($id);
		}
		if (Jaws_Error::IsError($result)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_REGIONS_NOT_RETRIEVED'), RESPONSE_ERROR);
			return new Jaws_Error(_t('PROPERTIES_ERROR_REGIONS_NOT_RETRIEVED'), _t('PROPERTIES_NAME'));
        }
		return $result;
    }
    	
    /**
     * Returns closest match of city from the country DB table
     *
     * @access  public
     * @param   string  $value  seed to match
     * @param   integer  $pid  ID of the parent
     * @return  array   country DB result
     */
    function GetClosestMatch($value, $pid, $table = '')
    {
		$res = array();
		if ((int)$pid > 0 && trim($value) != '') {
			if (trim($table) == '') {
				$table = null;
			}
			$gadget = $GLOBALS['app']->LoadGadget('Maps', 'AdminModel');
			$haystacks = $gadget->SearchRegions(substr($value, 0, 1), $pid, $table);
			
			$shortest = -1;
			$closest = null;
			foreach ($haystacks as $haystack){
				foreach ($haystack as $word){
					$lev = levenshtein($value, $word);
					if ($lev == 0) {
						$closest = $word; $shortest = 0; break;
					}
					if ($lev <= $shortest || $shortest <0) {
						$closest  = $word; $shortest = $lev;
					}
				}
			}
			if (!is_null($closest)) {
				$res['value'] = $closest;
			} else {
				$res['value'] = false;
			}
		} else {
			$res['value'] = false;
		}
		return $res;
	}	
    
	/**
     * Returns property row
     *
     * @access  public
     * @param   integer  $id  ID of the property
     * @return  array   property DB result
     */
    function GetProperty($id)
    {
        $gadget = $GLOBALS['app']->LoadGadget('Properties', 'Model');
		$property = $gadget->GetProperty((int)$id);
		if (Jaws_Error::IsError($property)) {
			$GLOBALS['app']->Session->PushLastResponse($property->GetMessage(), RESPONSE_ERROR);
			//return false;
		} else {
			if ($property['ownerid'] == $GLOBALS['app']->Session->GetAttribute('user_id')) {
				return $property;
			} else {
				$GLOBALS['app']->Session->PushLastResponse('OwnerID of '.$property['ownerid'].' does not equal '.$GLOBALS['app']->Session->GetAttribute('user_id'), RESPONSE_ERROR);
				//return false;
			}
		}
		return $GLOBALS['app']->Session->PopLastResponse();
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
        $this->CheckSession('Properties', 'default');
		require_once JAWS_PATH . 'include/Jaws/Image.php';
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$adminHTML = $GLOBALS['app']->LoadGadget('Properties', 'AdminHTML');
		$model = $GLOBALS['app']->LoadGadget('Properties', 'Model');
		
		$shout_params = array();
		$shout_params['gadget'] = 'Properties';
		$res = array();
		
		// Which method
		$result = $adminHTML->form_post(false, $method, $params);
		if ($result === false || Jaws_Error::IsError($result)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_SAVE_QUICKADD'), RESPONSE_ERROR);
			$res['success'] = false;
		} else {
			$id = $result;
			if ($method == 'AddPropertyParent' || $method == 'EditPropertyParent') {
				$post = $model->GetPropertyParent($id);
			} else if ($method == 'AddProperty' || $method == 'EditProperty') {
				$post = $model->GetProperty($id);
			} else if ($method == 'AddPost' || $method == 'EditPost') {
				$post = $model->GetPost($id);
			} else if ($method == 'AddPropertyAmenity' || $method == 'EditPropertyAmenity') {
				$post = $model->GetAmenity($id);
			} else if ($method == 'AddAmenityType' || $method == 'EditAmenityType') {
				$post = $model->GetAmenityType($id);
			}
		}
		if ($post && !Jaws_Error::IsError($post)) {
			if ($method == 'AddPropertyParent' || $method == 'EditPropertyParent') {
				$shout_params['edit_url'] = $GLOBALS['app']->GetSiteURL() .'/admin.php?gadget=Properties&action=form&id='.$id;
				$image = $post['propertyparentimage'];
				$title = $post['propertyparentcategory_name'];
				$description = $post['propertyparentdescription'];
			} else if ($method == 'AddProperty' || $method == 'EditProperty') {
				$shout_params['edit_url'] = $GLOBALS['app']->GetSiteURL() .'/admin.php?gadget=Properties&action=A_form&id='.$id;
				$image = $post['image'];
				$title = $post['title'];
				$description = $post['description'];
			} else if ($method == 'AddPost' || $method == 'EditPost') {
				$shout_params['edit_url'] = $GLOBALS['app']->GetSiteURL() .'/admin.php?gadget=Properties&action=A_form2&id='.$id;
				$image = $post['image'];
				$title = $post['title'];
				$description = $post['description'];
			} else if ($method == 'AddPropertyAmenity' || $method == 'EditPropertyAmenity') {
				$shout_params['edit_url'] = $GLOBALS['app']->GetSiteURL() .'/admin.php?gadget=Properties&action=B_form&id='.$id;
				$image = '';
				$title = $post['feature'];
				$description = $post['description'];
			} else if ($method == 'AddAmenityType' || $method == 'EditAmenityType') {
				$shout_params['edit_url'] = $GLOBALS['app']->GetSiteURL() .'/admin.php?gadget=Properties&action=B_form2&id='.$id;
				$image = '';
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
				$el['taction'] = '';
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
				$el['icon'] = $GLOBALS['app']->GetJawsURL() . '/gadgets/Properties/images/logo.png';
				//$url_ea = $GLOBALS['app']->getSiteURL() .'/'. BASE_SCRIPT. '?gadget=CustomPage&action=EditElementAction&id='.$id.'&method='.str_replace('Add', 'Edit', $method);
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
				$sharing, 'Properties', true
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
