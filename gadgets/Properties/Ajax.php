<?php
/**
 * Properties AJAX API
 *
 * @category   Ajax
 * @package    Properties
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class PropertiesAjax extends Jaws_Ajax
{
    // {{{ Constructor PHP4
    /**
     * PHP 4 Constructor
     *
     * @access  public
     */
    function PropertiesAjax(&$model)
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
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Properties', 'ManageProperties') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Properties', 'OwnProperty')) {
			$this->CheckSession('Properties', 'ManageProperties');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Properties', 'AdminModel');
        $gadget->DeletePropertyParent($id);
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
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Properties', 'ManageProperties') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Properties', 'OwnProperty')) {
			$this->CheckSession('Properties', 'ManageProperties');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Properties', 'AdminModel');
        $gadget->DeleteProperty($id);
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
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Properties', 'ManageProperties') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Properties', 'OwnProperty')) {
			$this->CheckSession('Properties', 'ManageProperties');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Properties', 'AdminModel');
        $gadget->DeleteAmenity($id);
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
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Properties', 'ManageProperties') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Properties', 'OwnProperty')) {
			$this->CheckSession('Properties', 'ManageProperties');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Properties', 'AdminModel');
        $gadget->DeleteAmenityType($id);
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
		if (!$this->GetPermission('Properties', 'ManageProperties') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Properties', 'OwnProperty')) {
			$this->CheckSession('Properties', 'ManageProperties');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Properties', 'AdminModel');
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
		if (!$this->GetPermission('Properties', 'ManageProperties') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Properties', 'OwnProperty')) {
			$this->CheckSession('Properties', 'ManageProperties');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Properties', 'AdminModel');
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
		if (!$this->GetPermission('Properties', 'ManageProperties') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Properties', 'OwnProperty')) {
			$this->CheckSession('Properties', 'ManageProperties');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Properties', 'AdminModel');
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
		if (!$this->GetPermission('Properties', 'ManageProperties') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Properties', 'OwnProperty')) {
			$this->CheckSession('Properties', 'ManageProperties');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Properties', 'AdminModel');
        $gadget->MassiveDelete($pages);
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
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Properties', 'ManageProperties') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Properties', 'OwnProperty')) {
			$this->CheckSession('Properties', 'ManageProperties');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Properties', 'AdminModel');
        $gadget->SaveAddress($pid, $address);
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
		if (!$this->GetPermission('Properties', 'ManageProperties') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Properties', 'OwnProperty')) {
			$this->CheckSession('Properties', 'ManageProperties');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Properties', 'AdminModel');
        $pages = $gadget->SearchPropertyParents($status, $search, null);
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
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Properties', 'ManageProperties') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Properties', 'OwnProperty')) {
			$this->CheckSession('Properties', 'ManageProperties');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Properties', 'AdminModel');
        $pages = $gadget->SearchAmenities($search, $status, null);
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
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Properties', 'ManageProperties') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Properties', 'OwnProperty')) {
			$this->CheckSession('Properties', 'ManageProperties');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Properties', 'AdminModel');
        $pages = $gadget->SearchAmenityTypes($status, $search, null);
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
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Properties', 'ManageProperties') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Properties', 'OwnProperty')) {
			$this->CheckSession('Properties', 'ManageProperties');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Properties', 'AdminHTML');
        if (!is_numeric($limit)) {
            $limit = 0;
        }
		return $gadget->GetPropertyParents($status, $search, $limit, $GLOBALS['app']->Session->GetAttribute('user_id'));
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
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Properties', 'ManageProperties') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Properties', 'OwnProperty')) {
			$this->CheckSession('Properties', 'ManageProperties');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Properties', 'AdminHTML');
        if (!is_numeric($limit)) {
            $limit = 0;
        }
		return $gadget->GetAmenities($status, $search, $limit, $GLOBALS['app']->Session->GetAttribute('user_id'));
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
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Properties', 'ManageProperties') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Properties', 'OwnProperty')) {
			$this->CheckSession('Properties', 'ManageProperties');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Properties', 'AdminHTML');
        if (!is_numeric($limit)) {
            $limit = 0;
        }
		return $gadget->GetAmenityTypes($status, $search, $limit, $GLOBALS['app']->Session->GetAttribute('user_id'));
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
		if (!$this->GetPermission('Properties', 'ManageProperties') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Properties', 'OwnProperty')) {
			$this->CheckSession('Properties', 'ManageProperties');
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
		if (!$this->GetPermission('Properties', 'ManageProperties') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Properties', 'OwnProperty')) {
			$this->CheckSession('Properties', 'ManageProperties');
		}
		$res = array();
        $gadget = $GLOBALS['app']->LoadGadget('Properties', 'AdminModel');
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
     * Returns an array with all the country DB table data
     *
     * @access  public
     * @param   integer  $id  ID of the parent
     * @return  array   country DB table data
     */
    function GetRegionsOfParent($id)
    {
		/*
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Properties', 'ManageProperties') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Properties', 'OwnProperty')) {
			$this->CheckSession('Properties', 'ManageProperties');
		}
		*/
        $gadget = $GLOBALS['app']->LoadGadget('Properties', 'Model');
        $result = $gadget->GetRegionsOfParent($id);
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
    function GetClosestMatch($value, $pid, $table = '', $properties)
    {
		$res = array();
		if (trim($value) != '') {
			if ((int)$pid > 0) {
				$pid = (int)$pid;
			} else {
				$pid = null;
			}
			if (trim($table) == '') {
				$table = null;
			}
			
			if (!is_null($table)) {
				$gadget = $GLOBALS['app']->LoadGadget('Maps', 'AdminModel');
				$haystacks = $gadget->SearchRegions(substr($value, 0, 3), $pid, $table);
			} else {
				$search = '';
				if (!is_numeric(trim($value))) {
					$search = substr($value, 0, 3);
				}
				$gadget = $GLOBALS['app']->LoadGadget('Properties', 'AdminModel');
				$haystacks = $gadget->SearchKeyWithProperties($search, '', '', '', '', '', '', null, null, false, 'city', 'ASC', 'city');
			}
			$shortest = -1;
			$closest = null;
			$result = array();
			$allwords = array();
			$i = 0;
			foreach ($haystacks as $haystack){
				foreach ($haystack as $word){
					if (!in_array($word, $allwords) && ($i < 100)) {
						$result[] = array();
						$result[]['region'] = $word;
						$allwords[] = $word;
						$i++;
					}
					if ($value == $word) {
						$res['value'] = $word;
						return $res;
					}

					$lev = levenshtein($value, $word);
					if ($lev == 0) {
						$closest = $word; $shortest = 0;
					}
					if ($lev <= $shortest || $shortest <0) {
						$closest  = $word; $shortest = $lev;
					}
				}
			}
			if (!is_null($closest)) {
				$res['suggestions'] = $result;
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
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Properties', 'ManageProperties') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Properties', 'OwnProperty')) {
			$this->CheckSession('Properties', 'ManageProperties');
		}
		require_once JAWS_PATH . 'include/Jaws/Image.php';
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$adminHTML = $GLOBALS['app']->LoadGadget('Properties', 'AdminHTML');
		$model = $GLOBALS['app']->LoadGadget('Properties', 'Model');
		
		$shout_params = array();
		$shout_params['gadget'] = 'Properties';
		$res = array();
		
		// Which method
		$result = $adminHTML->form_post(true, $method, $params);
		if ($result === false || Jaws_Error::IsError($result)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('PROPERTIES_ERROR_SAVE_QUICK_ADD'), RESPONSE_ERROR);
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
				if ($addtype == 'Comment') {
					$shout_params['edit_url'] = $GLOBALS['app']->GetSiteURL() .'/'. $GLOBALS['app']->Map->GetURLFor('Properties', 'Category', array('id' => $id));
				} else {
					$shout_params['edit_url'] = $GLOBALS['app']->GetSiteURL() .'/index.php?gadget=Properties&action=account_form&id='.$id;
				}
				$image = $post['propertyparentimage'];
				$title = $post['propertyparentcategory_name'];
				$description = $post['propertyparentdescription'];
			} else if ($method == 'AddProperty' || $method == 'EditProperty') {
				if ($addtype == 'Comment') {
					$shout_params['edit_url'] = $GLOBALS['app']->GetSiteURL() .'/'. $GLOBALS['app']->Map->GetURLFor('Properties', 'Property', array('id' => $post['fast_url']));
					$post['html'] = '';
					$hook = $GLOBALS['app']->loadHook('Properties', 'Comment');
					if ($hook !== false) {
						if (method_exists($hook, 'GetPropertiesComment')) {
							$comment = $hook->GetPropertiesComment(array('gadget_reference' => $post['id'], 'public' => false));
							if (!Jaws_Error::IsError($comment) && isset($comment['msg_txt']) && !empty($comment['msg_txt'])) {
								$post['html'] = $comment['msg_txt'];
							}
						}
					}
				} else {
					$shout_params['edit_url'] = $GLOBALS['app']->GetSiteURL() .'/index.php?gadget=Properties&action=account_A_form&id='.$id;
				}
				$image = $post['image'];
				$title = $post['title'];
				$description = $post['description'];
			} else if ($method == 'AddPost' || $method == 'EditPost') {
				$shout_params['edit_url'] = $GLOBALS['app']->GetSiteURL() .'/index.php?gadget=Properties&action=account_A_form2&id='.$id;
				$image = $post['image'];
				$title = $post['title'];
				$description = $post['description'];
			} else if ($method == 'AddPropertyAmenity' || $method == 'EditPropertyAmenity') {
				$shout_params['edit_url'] = $GLOBALS['app']->GetSiteURL() .'/index.php?gadget=Properties&action=account_B_form&id='.$id;
				$image = '';
				$title = $post['feature'];
				$description = $post['description'];
			} else if ($method == 'AddAmenityType' || $method == 'EditAmenityType') {
				$shout_params['edit_url'] = $GLOBALS['app']->GetSiteURL() .'/index.php?gadget=Properties&action=account_B_form2&id='.$id;
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
				$el['taction'] = $method;
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
    function NewPropertiesComment($title = '', $comments, $parent, $parentId, $ip = '', $set_cookie = true, $sharing = 'everyone', $reply = false)
    {
        $res = array();
		if (!$GLOBALS['app']->Session->Logged()) {
	        //require_once JAWS_PATH . 'include/Jaws/Header.php';
			//Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'));
			$this->CheckSession('Properties', 'ManageProperties');
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
				(int)$parent, (int)$parentId, $ip, $set_cookie, (int)$GLOBALS['app']->Session->GetAttribute('user_id'), $sharing, 'Properties'
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
					$res['image'] = (!empty($result['link']) ? '<a href="'.$result['link'].'">' : '').'<img src="'.$result['avatar_source'].'" border="0" align="left" />'.(!empty($result['link']) ? '</a>' : '');
					if (!empty($result['image'])) {
						$res['image'] = (!empty($result['link']) ? '<a href="'.$result['link'].'">' : '').'<img src="'.$result['image'].'" border="0" align="left" />'.(!empty($result['link']) ? '</a>' : '');
					}
				} else {
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
				if (!Jaws_Error::IsError($shout) && (isset($shout['url']) && !empty($shout['url']))) {
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
    function DeletePropertiesComment($id)
    {
		if (!$GLOBALS['app']->Session->Logged()) {
	        //require_once JAWS_PATH . 'include/Jaws/Header.php';
			//Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'));
			$this->CheckSession('Properties', 'ManageProperties');
		} else {
			$uid = $GLOBALS['app']->Session->GetAttribute('user_id');
			$params 		= array();
			$params['id']   = (int)$id;
					
			$sql = '
				SELECT
					[gadget], [parent], [ownerid]
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
						[gadget], [parent], [ownerid]
					FROM [[comments]]
					WHERE [id] = {id}';

				$parent = $GLOBALS['db']->queryRow($sql, $params);
				if (Jaws_Error::IsError($parent) || !isset($parent['gadget']) || empty($parent['gadget'])) {
					$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_COMMENT_DELETED'), RESPONSE_ERROR);
					return $GLOBALS['app']->Session->PopLastResponse();
				}
			}
			if ($uid != $gadget['ownerid'] && (isset($parent['ownerid']) && $uid != $parent['ownerid'])) {
				$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_COMMENT_DELETED'), RESPONSE_ERROR);
			} else {
				$model = $GLOBALS['app']->LoadGadget('Users', 'Model');
				$delete = $model->DeleteComment($id, $gadget['gadget']);
				if (!Jaws_Error::IsError($delete)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_COMMENT_DELETED'), RESPONSE_NOTICE);
				} else {
					$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_COMMENT_DELETED'), RESPONSE_ERROR);
				}
			}
		}
        return $GLOBALS['app']->Session->PopLastResponse();
    }
}
