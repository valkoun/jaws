<?php
/**
 * Forms AJAX API
 *
 * @category   Ajax
 * @package    Forms
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class FormsAdminAjax extends Jaws_Ajax
{
    // {{{ Constructor PHP4
    /**
     * PHP 4 Constructor
     *
     * @access  public
     */
    function FormsAdminAjax(&$model)
    {
        $this->_Model =& $model;
    }

    // }}}
    // {{{ Function DeletePage
    /**
     * Deletes a map.
     *
     * @access  public
     * @param   int     $id  Map ID
     * @return  array   Response (notice or error)
     */
    function DeleteForm($id)
    {
        $this->CheckSession('Forms', 'ManageForms');
        $this->_Model->DeleteForm($id);
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
        $this->CheckSession('Forms', 'ManageForms');		
        $this->_Model->DeletePost($pid);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

     /**
     * Deletes a answer
     *
     * @access  public
     * @param   int     $pid  Post ID
     * @return  array   Response (notice or error)
     */
    function DeleteAnswer($pid)
    {
        $this->CheckSession('Forms', 'ManageForms');		
        $this->_Model->DeleteAnswer($pid);
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
        $this->CheckSession('Forms', 'ManageForms');
        $this->_Model->MassiveDelete($pages);
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
        $this->CheckSession('Forms', 'default');
        $pages = $this->_Model->SearchForms($status, $search, null);
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
    function SearchForms($status, $search, $limit)
    {
        $this->CheckSession('Forms', 'default');
        $gadget = $GLOBALS['app']->LoadGadget('Forms', 'AdminHTML');
        if (!is_numeric($limit)) {
            $limit = 0;
        }
		if ($GLOBALS['app']->Session->GetPermission('Forms', 'ManageForms')) {
			return $gadget->GetForms($status, $search, $limit);
		} else {
			return $gadget->GetForms($status, $search, $limit, false, $GLOBALS['app']->Session->GetAttribute('user_id'));
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
        $this->CheckSession('Forms', 'default');

        /*
		if ($id == 'NEW') {
            $this->_Model->AddPage($fast_url, $show_title, $title, $content, $language, $published, true);
            $newid    = $GLOBALS['db']->lastInsertID('static_pages', 'id');
            $response['id'] = $newid;
            $response['message'] = _t('CUSTOMPAGE_PAGE_AUTOUPDATED',
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
    function SortItem($pids, $newsorts, $table = 'form_questions')
    {
        $this->CheckSession('Forms', 'ManageForms');		
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
     * Saves the value of a key
     *
     * @access  public
     * @param   string  $key   Key name
     * @param   string  $value Key value
     * @return  array   Response
     */
    function SaveSettings($default_recipient = '', $site_address = '', $site_office = '', $site_tollfree = '', $site_cell = '', $site_fax = '')
    {
        $this->CheckSession('Forms', 'ManageForms');
        $this->_Model->SaveSettings($default_recipient, $site_address, $site_office, $site_tollfree, $site_cell, $site_fax);
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
        $this->CheckSession('Forms', 'default');
		require_once JAWS_PATH . 'include/Jaws/Image.php';
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$adminHTML = $GLOBALS['app']->LoadGadget('Forms', 'AdminHTML');
		$model = $GLOBALS['app']->LoadGadget('Forms', 'Model');
		
		$shout_params = array();
		$shout_params['gadget'] = 'Forms';
		$res = array();
		
		// Which method
		$result = $adminHTML->form_post(false, $method, $params);
		if ($result === false || Jaws_Error::IsError($result)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('FORMS_ERROR_SAVE_QUICKADD'), RESPONSE_ERROR);
			$res['success'] = false;
		} else {
			$id = $result;
			if ($method == 'AddForm' || $method == 'EditForm') {
				$post = $model->GetForm($id);
			} else if ($method == 'AddPost' || $method == 'EditPost') {
				$post = $model->GetPost($id);
			}
		}
		if ($post && !Jaws_Error::IsError($post)) {
			$shout_params['edit_url'] = $GLOBALS['app']->GetSiteURL() .'/admin.php?gadget=Forms&action='.($method == 'AddPost' || $method == 'EditPost' ? 'A_' : '').'form&id='.$id;
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
				$el['icon'] = $GLOBALS['app']->GetJawsURL() . '/gadgets/Forms/images/logo.png';
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
}
?>
