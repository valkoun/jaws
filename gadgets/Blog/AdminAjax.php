<?php
/**
 * BlogStaticPage AJAX API
 *
 * @category   Ajax
 * @package    Blog
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2005-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class BlogAdminAjax extends Jaws_Ajax
{
    /**
     * Constructor
     *
     * @access  public
     */
    function BlogAdminAjax(&$model)
    {
        $this->_Model  =& $model;
    }

    /**
     * Parse text
     *
     * @access  public
     * @param   string  $text  Input text
     * @return  string  parsed Text
     */
    function ParseText($text)
    {
        $this->CheckSession('Blog', 'default');
        $gadget = $GLOBALS['app']->LoadGadget('Blog', 'AdminHTML');
        return $gadget->ParseText($text, 'Blog');
    }

    /**
     * Search for posts and return a datagrid
     *
     * @access  public
     * @param   string  $period  Period to look for
     * @param   int     $cat     Category
     * @param   int     $status  Status (0=Draft, 1=Published)
     * @param   string  $search  Search word
     * @param   int     $limit   Limit data
     * @return  array   Posts Array
     */
    function SearchPosts($period, $cat, $status, $search, $limit = 0)
    {
        $this->CheckSession('Blog', 'default');
        $gadget = $GLOBALS['app']->LoadGadget('Blog', 'AdminHTML');
        return $gadget->PostsData($period, $cat, $status, $search, $limit);
    }

    /**
     * Get total posts of a search
     *
     * @access  public
     * @param   int     $cat     Category
     * @param   int     $status  Status (0=Draft, 1=Published)
     * @param   string  $search  Search word
     * @return  int     Total of posts
     */
    function SizeOfSearch($period, $cat, $status, $search)
    {
        $this->CheckSession('Blog', 'default');
        $entries = $this->_Model->AdvancedSearch(false, $period, $cat, $status, $search,
                                                 $GLOBALS['app']->Session->GetAttribute('user_id'));
        return count($entries);
    }

    /**
     * Save blog settings
     *
     * @access  public
     * @param   string  $view               The default View
     * @param   int     $limit              Limit of entries that blog will show
     * @param   int     $popularLimit       Limit of popular entries
     * @param   int     $commentsLimit      Limit of comments that blog will show
     * @param   int     $recentcommentLimit Limit of recent comments to display
     * @param   string  $commentStatus      Default comment status
     * @param   string  $category           The default category for blog entries
     * @param   boolean $comments           If comments should appear
     * @param   string  $comment_status     Default comment status
     * @param   boolean $trackback          If Trackback should be used
     * @param   string  $trackback_status   Default trackback status
     * @param   boolean $pingback           If Pingback should be used
     * @return  array   Response (notice or error)
     */
    function SaveSettings($view, $limit, $popularLimit, $commentsLimit, $recentcommentsLimit, $category, 
                          $xml_limit, $comments, $comment_status, $trackback, $trackback_status,
                          $pingback)
    {
        $this->CheckSession('Blog', 'default');
        $this->_Model->SaveSettings($view, $limit, $popularLimit, $commentsLimit, $recentcommentsLimit, $category, 
                                    $xml_limit, $comments, $comment_status, $trackback, $trackback_status,
                                    $pingback);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Prepare the Category form
     *
     * @access  public
     * @return  string  XHTML of Category Form
     */
    function GetCategoryForm($action, $id)
    {
        $this->CheckSession('Blog', 'ManageCategories');
        $gadget = $GLOBALS['app']->LoadGadget('Blog', 'AdminHTML');
        return $gadget->CategoryForm($action, $id);
    }

    /**
     * Add a new category
     *
     * @access  public
     * @param   string  $name        Category name
     * @param   string  $description Category description
     * @param   string  $fast_url    Category fast url
     * @return  array   Response (notice or error)
     */
    function AddCategory($name, $description, $fast_url)
    {
        $this->CheckSession('Blog', 'ManageCategories');
        $this->_Model->NewCategory($name, $description, $fast_url);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Update a category
     *
     * @access  public
     * @param   int     $id          ID of category
     * @param   string  $name        Name of category
     * @param   string  $description Category description
     * @param   string  $fast_url    Category fast url
     * @return  array   Response (notice or error)
     */
    function UpdateCategory($id, $name, $description, $fast_url)
    {
        $this->CheckSession('Blog', 'ManageCategories');
        $this->_Model->UpdateCategory($id, $name, $description, $fast_url);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Delete a category
     *
     * @access  public
     * @param   int     $id   ID of category
     * @return  array   Response (notice or error)
     */
    function DeleteCategory($id)
    {
        $this->CheckSession('Blog', 'ManageCategories');
        $this->_Model->DeleteCategory($id);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Retrieves the category combo (the big one)
     *
     * @access  public
     * @return  string  XHTML of the combo
     */
    function GetCategoryCombo()
    {
        $this->CheckSession('Blog', 'ManageCategories');
        $gadget = $GLOBALS['app']->LoadGadget('Blog', 'AdminHTML');
        return $gadget->GetCategoriesAsCombo();
    }

    /**
     * Search for comments and return the data in an array
     *
     * @access  public
     * @param   int     $limit   Data limit
     * @param   string  $filter  Filter
     * @param   string  $search  Search word
     * @param   string  $status  Spam status (approved, waiting, spam)
     * @return  array   Data
     */
    function SearchComments($limit, $filter, $search, $status)
    {
        $this->CheckSession('Blog', 'ManageComments');
        $gadget = $GLOBALS['app']->LoadGadget('Blog', 'AdminHTML');
        return $gadget->CommentsData($limit, $filter, $search, $status);
    }

    /**
     * Search for trackbacks and return the data in an array
     *
     * @access  public
     * @param   int     $limit   Data limit
     * @param   string  $filter  Filter
     * @param   string  $search  Search word
     * @param   string  $status  Spam status (approved, waiting, spam)
     * @return  array   Data
     */
    function SearchTrackbacks($limit, $filter, $search, $status)
    {
        $this->CheckSession('Blog', 'ManageTrackbacks');
        $gadget = $GLOBALS['app']->LoadGadget('Blog', 'AdminHTML');
        return $gadget->TrackbacksData($limit, $filter, $search, $status);
    }

    /**
     * Get total posts of a comment search
     *
     * @access  public
     * @param   string  $filter  Filter
     * @param   string  $search  Search word
     * @param   string  $status  Spam status (approved, waiting, spam)
     * @return  int     Total of posts
     */
    function SizeOfCommentsSearch($filter, $search, $status)
    {
        $this->CheckSession('Blog', 'default');
        require_once JAWS_PATH.'include/Jaws/Comment.php';
        $api = new Jaws_Comment('Blog');
        $filterMode = null;
        switch($filter) {
            case 'postid':
                $filterMode = COMMENT_FILTERBY_REFERENCE;
                break;
            case 'name':
                $filterMode = COMMENT_FILTERBY_NAME;
                break;
            case 'email':
                $filterMode = COMMENT_FILTERBY_EMAIL;
                break;
            case 'url':
                $filterMode = COMMENT_FILTERBY_URL;
                break;
            case 'title':
                $filterMode = COMMENT_FILTERBY_TITLE;
                break;
            case 'ip':
                $filterMode = COMMENT_FILTERBY_IP;
                break;
            case 'comment':
                $filterMode = COMMENT_FILTERBY_MESSAGE;
                break;
            case 'various':
                $filterMode = COMMENT_FILTERBY_VARIOUS;
                break;
            case 'status':
                $filterMode = COMMENT_FILTERBY_STATUS;
                break;
            default:
                $filterMode = null;
                break;
        }
        return $api->HowManyFilteredComments($filterMode, $search, $status, false);
    }

    /**
     * Get total posts of a trackback search
     *
     * @access  public
     * @param   string  $filter  Filter
     * @param   string  $search  Search word
     * @param   string  $status  Spam status (approved, waiting, spam)
     * @return  int     Total of posts
     */
    function SizeOfTrackbacksSearch($filter, $search, $status)
    {
        $this->CheckSession('Blog', 'default');
        return $this->_Model->HowManyFilteredTrackbacks($filter, $search, $status, false);
    }

    /**
     * Does a massive delete on comments
     *
     * @access  public
     * @param   array   $ids     Comment ids
     * @return  array   Response (notice or error)
     */
    function DeleteComments($ids)
    {
        $this->CheckSession('Blog', 'ManageComments');
        $this->_Model->MassiveCommentDelete($ids);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Does a massive delete on entries
     *
     * @access  public
     * @param   array   $ids     Entries ids
     * @return  array   Response (notice or error)
     */
    function DeleteEntries($ids)
    {
        $this->CheckSession('Blog', 'DeleteEntries');
        $this->_Model->MassiveEntryDelete($ids);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Change status of group of entries ids
     *
     * @access  public
     * @param   array   $ids    Ids of entries
     * @param   string  $status New status
     * @return  array   Response (notice or error)
     */
    function ChangeEntryStatus($ids, $status)
    {
        $this->CheckSession('Blog', 'PublishEntries');
        $this->_Model->ChangeEntryStatus($ids, $status);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Does a massive delete on trackbacks
     *
     * @access  public
     * @param   array   $ids     Trackback ids
     * @return  array   Response (notice or error)
     */
    function DeleteTrackbacks($ids)
    {
        $this->CheckSession('Blog', 'ManageTrackbacks');
        $this->_Model->MassiveTrackbackDelete($ids);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Mark as different type a group of ids
     *
     * @access  public
     * @param   array   $ids    Ids of comments
     * @param   string  $status New status
     * @return  array   Response (notice or error)
     */
    function MarkAs($ids, $status)
    {
        $this->CheckSession('Blog', 'ManageComments');
        $this->_Model->MarkCommentsAs($ids, $status);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Mark as different type a group of ids
     *
     * @access  public
     * @param   array   $ids    Ids of comments
     * @param   string  $status New status
     * @return  array   Response (notice or error)
     */
    function TrackbackMarkAs($ids, $status)
    {
        $this->CheckSession('Blog', 'ManageTrackbacks');
        $this->_Model->MarkTrackbacksAs($ids, $status);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * This function will perform an autodraft of the content and set
     * it's value to not published, which will later be changed when the
     * user clicks on save.
     *
     * @access public
     * @param   array   $categories     Array with categories id's
     * @param   string  $title          Title of the entry
     * @param   string  $summary        Summary of the entry
     * @param   string  $text           Content of the entry
     * @param   string  $fasturl        FastURL
     * @param   string  $trackback      Trackback to send
     * @param   boolean $allow_comments If entry should allow commnets
     * @param   boolean $publish        If entry should be published
     * @param   string  $timestamp      Entry timestamp (optional)
     */
    function AutoDraft($id, $categories, $title, $summary, $text, $fasturl, $allow_comments,
                       $trackbacks, $published, $timestamp)
    {
        $this->CheckSession('Blog', 'AddEntries');

        if ($id == 'NEW') {
            $res = $this->_Model->NewEntry($GLOBALS['app']->Session->GetAttribute('user_id'),
                                           $categories,
                                           $title,
                                           $summary,
                                           $text,
                                           $fasturl,
                                           $allow_comments,
                                           $trackbacks,
                                           false,
                                           $timestamp,
                                           true);
            if (!Jaws_Error::IsError($res)) {
                $GLOBALS['app']->Session->PopLastResponse(); // emptying all responses message
                $newid          = $GLOBALS['db']->lastInsertID('blog', 'id');
                $response['id'] = $newid;
                $response['message'] = _t('BLOG_ENTRY_AUTOUPDATED',
                                          date('H:i:s'),
                                          (int)$id,
                                          date('D, d'));
                $GLOBALS['app']->Session->PushLastResponse($response, RESPONSE_NOTICE);
            }
        } else {
            $this->_Model->UpdateEntry($id,
                                       $categories,
                                       $title,
                                       $summary,
                                       $text,
                                       $fasturl,
                                       $allow_comments,
                                       $trackbacks,
                                       $published,
                                       $timestamp,
                                       true);
        }
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
		$this->CheckSession('Blog', 'default');
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
		$this->CheckSession('Blog', 'default');
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
     * Get actions of a given gadget
     *
     * @access  public
     * @params  string  $gadget
     * @return  array   Actions of the given gadget
     */
    function GetGadgetActions($gadget)
    {
        $this->CheckSession('Blog', 'default');
        return $this->_Model->GetGadgetActions($gadget);
    }

    /**
     * Add gadget to layout 
     *
     * @access  public
     * @params  string  $gadget
     * @params  string  $action
     * @return  array   Details of the added gadget/action
     */
    function AddGadget($itemId = '', $gadget, $action, $linkid) 
    {
		$this->CheckSession('Blog', 'default');
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$model = $GLOBALS['app']->LoadGadget('Blog', 'Model');
        $res = array();
		$sort_order = 0;
		if (empty($itemId)) {
			// get highest sort_order
			$sql = "SELECT [sort_order] FROM [[blog_posts]] WHERE ([linkid] = {linkid}) ORDER BY [sort_order] DESC LIMIT 1";
			$params = array();
			$params['linkid'] = (int)$linkid;
			$result = $GLOBALS['db']->queryOne($sql, $params);
			if (Jaws_Error::IsError($result)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('BLOG_ERROR_POST_NOT_ADDED'), RESPONSE_ERROR);
				$res['success'] = false;
			}
			if ($result && is_numeric($result)) {
				$sort_order = $result+1;
			}
		}
		if ($GLOBALS['app']->Session->GetPermission('Blog', 'default')) {
			$OwnerID = null;
		} else {
			$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
		}
		$actions = $GLOBALS['app']->GetGadgetActions($gadget);
		$actions = (isset($actions['LayoutAction'])) ? $actions['LayoutAction'] : array();
		foreach ($actions as $aName => $aProps) {
			if ($aName == $action) {
				$aAction = $aName;
			}
		}
		if (isset($aAction)) {
			$taction = $aAction;
		} else {
			$layoutGadget = $GLOBALS['app']->LoadGadget($gadget, 'LayoutHTML');
			if (method_exists($layoutGadget, 'LoadLayoutActions')) {
				$actions = $layoutGadget->LoadLayoutActions();
				foreach ($actions as $aName1 => $aProps1) {
					if ($aName1 == $action) {
						$taction = $aName1;
					}
				}
			} else {
				$taction =  _t('BLOG_ACTIONS');
			}
			unset($layoutGadget);
		}
		$id = $this->_Model->AddPost((int)$sort_order, $linkid, $gadget, $actions[$action]['desc'], $taction, 0, 0, 0, 'Y', $OwnerID, $gadget, '', '', '', '', null);
		if ($id === false || Jaws_Error::IsError($id)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('BLOG_ERROR_POST_NOT_ADDED'), RESPONSE_ERROR);
            $res['success'] = false;
        } else {
			if (!empty($itemId)) {
				$id = (int)$itemId;
			}
			$post = $model->GetPost($id);
			if ($post && !Jaws_Error::IsError($post)) {
				$el = array();
				$el = $post;
	            //$GLOBALS['app']->Session->PushLastResponse(_t('LAYOUT_ELEMENT_ADDED'), RESPONSE_NOTICE);
				$info = $GLOBALS['app']->LoadGadget($gadget, 'Info');
				$url_ea2 = $GLOBALS['app']->getSiteURL() .'/'. BASE_SCRIPT. '?gadget=Blog&action=EditElementAction&id='.$id;
				$el['eaonclick2'] = "editElementAction('".$url_ea2."');";
				$url_ea = $GLOBALS['app']->getSiteURL() .'/admin.php?gadget='.$gadget;
				$el['eaonclick'] = "editElementAction('', '".$gadget."', '".$actions[$action]['name']."');";
				$el['tname'] = $info->GetName();
				$el['taction'] = $actions[$action]['name'];
				$el['tactiondesc'] = $actions[$action]['desc'];
				unset($info);
				$el['icon'] = $GLOBALS['app']->GetJawsURL() . '/gadgets/'.$gadget.'/images/logo.png';
				$image_src = '';
				$el['image_thumb'] = $image_src;
				$el['eaid'] = 'ea'.$id;
	            $el['delete'] = "deletePost('".$id."');";
	            $res = $el;
	            $res['success'] = true;
			} else {
	            $GLOBALS['app']->Session->PushLastResponse($post->GetMessage(), RESPONSE_ERROR);
	            $res['success'] = false;
			}
        }
        $res['message'] = $GLOBALS['app']->Session->PopLastResponse();
        return $res;
    }

    /**
     * Edit layout's element action
     * 
     * @access  public
     * @param   int     $item   Item ID
     * @params  string  $action
     * @return  array   Response
     */
    function EditElementAction($item, $action) 
    {
        $this->CheckSession('Blog', 'default');
        $res = $this->_Model->EditElementAction($item, $action);
        if ($res === false) {
            $GLOBALS['app']->Session->PushLastResponse(_t('BLOG_ERROR_POST_NOT_UPDATED'), RESPONSE_ERROR);
        } else {
            $GLOBALS['app']->Session->PushLastResponse(_t('BLOG_POST_UPDATED'), RESPONSE_NOTICE);
        }
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Get Quick Add Forms of Gadget
     *
     * @access public
     * @param string	$method	The method to call
     * @param array	$params	The params to pass to method
     * @param string	$callback	The method to call afterwards
     * @return  array	Response (notice or error)
     */
    function GetQuickAddForms($gadget = '') 
    {
        $this->CheckSession('Blog', 'default');
        $res = array();
		// Action is for specific ID
		if (!empty($gadget)) {
			$hook = $GLOBALS['app']->loadHook($gadget, 'URLList');
			if ($hook !== false) {
				if (method_exists($hook, 'GetQuickAddForms')) {
					$forms = $hook->GetQuickAddForms();
					if ($forms !== false) {
						$i = 0;
						foreach ($forms as $form) {
							$res[$i]['name'] = $form['name'];
							$res[$i]['method'] = $form['method'];
							$i++;
						}
					}
				}
			}
		}
		return $res;
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
    function SaveQuickAdd($addtype = 'Blog', $method, $params, $callback = '') 
    {
		$this->CheckSession('Blog', 'default');
		require_once JAWS_PATH . 'include/Jaws/Image.php';
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$adminHTML = $GLOBALS['app']->LoadGadget('Blog', 'AdminHTML');
		$model = $GLOBALS['app']->LoadGadget('Blog', 'Model');
		
		$shout_params = array();
		$shout_params['gadget'] = 'Blog';
		$res = array();
		// Which method
		$result = $adminHTML->form_post(false, $method, $params);
		if ($result === false || Jaws_Error::IsError($result)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('BLOG_ERROR_SAVE_QUICKADD'), RESPONSE_ERROR);
			$res['success'] = false;
		} else {
			$id = $result;
			if ($method == 'AddPost' || $method == 'EditPost') {
				$post = $model->GetPost($id);
			}
		}
		if (isset($post['id']) && !empty($post['id']) && !Jaws_Error::IsError($post)) {
			$shout_params['edit_url'] = $GLOBALS['app']->GetSiteURL() .'/admin.php?gadget=Blog&action='.($method == 'AddPost' || $method == 'EditPost' ? 'A_' : '').'form&id='.$id;
			$el = array();
			$el = $post;
			// TODO: Return different array if callback is requested ("notify" mode)
			if (!empty($callback)) {
			} else {
				//$GLOBALS['app']->Session->PushLastResponse(_t('LAYOUT_ELEMENT_ADDED'), RESPONSE_NOTICE);
				$image_src = '';
				$image = $post['image'];
				$el['tname'] = $post['title'];
				$el['taction'] = '';
				$el['tactiondesc'] = substr(strip_tags($post['description']), 0, 100).(strlen(strip_tags($post['description'])) > 100 ? '...' : '');
				if (!empty($image)) {
					$el['icon'] = $GLOBALS['app']->GetJawsURL() . '/gadgets/FileBrowser/images/mimetypes/text-x-image-generic.png';
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
				} else {
					if (!empty($post['image_code'])) {
						$el['icon'] = $GLOBALS['app']->GetJawsURL() . '/gadgets/FileBrowser/images/mimetypes/text-x-html-generic.png';
					} else {
						$el['icon'] = $GLOBALS['app']->GetJawsURL() . '/gadgets/FileBrowser/images/mimetypes/text-x-generic.png';
					}
				}
				$url_ea = $GLOBALS['app']->getSiteURL() .'/'. BASE_SCRIPT. '?gadget=Blog&action=EditElementAction&id='.$id.'&method='.str_replace('Add', 'Edit', $method);
				$el['eaurl'] = $url_ea;
				$el['image_thumb'] = $image_src;
				$el['eaid'] = 'ea'.$id;
			}	
			$res = $el;
			$res['success'] = true;
			$res['addtype'] = $addtype;
			$res['method'] = $method;
			if (isset($params['sharing']) && !empty($params['sharing'])) {
				$res['sharing'] = $params['sharing'];
			}
		} else {
			if (Jaws_Error::IsError($post)) {
				$GLOBALS['app']->Session->PushLastResponse($post->GetMessage(), RESPONSE_ERROR);
			} else {
				$GLOBALS['app']->Session->PushLastResponse(_t('BLOG_ERROR_POST_NOT_ADDED').' '.var_export($post, true), RESPONSE_ERROR);
			}
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
}
