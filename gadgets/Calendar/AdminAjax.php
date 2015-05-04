<?php
/**
 * Calendar AJAX API
 *
 * @category   Ajax
 * @package    Calendar
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class CalendarAdminAjax extends Jaws_Ajax
{
    /**
     * Constructor
     *
     * @access  public
     */
    function CalendarAdminAjax(&$model)
    {
        $this->_Model =& $model;
    }

     /**
     * Deletes an event
     *
     * @access  public
     * @param   int     $eid  Event ID
     * @return  array   Response (notice or error)
     */
    function DeleteEvent($eid)
    {
		$this->CheckSession('Calendar', 'ManageEvents');

        //$model = $GLOBALS['app']->LoadGadget('Calendar', 'Model');
		//$eventinfo = $model->GetEventInfoById($eid);
        //$adminModel = $GLOBALS['app']->LoadGadget('Calendar', 'AdminModel');
		
        $this->_Model->DeleteEvent($eid);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Deletes a calendar
     *
     * @access  public
     * @param   int     $cid  Calendar ID
     * @return  array   Response (notice or error)
     */
    function DeleteCalendar($cid)
    {
		$this->CheckSession('Calendar', 'ManageCategories');

        //$model = $GLOBALS['app']->LoadGadget('Calendar', 'Model');
		//$calendarinfo = $model->GetCalendarInfoById($cid);
        //$adminModel = $GLOBALS['app']->LoadGadget('Calendar', 'AdminModel');
		
        $this->_Model->DeleteCalendar($cid);
        return $GLOBALS['app']->Session->PopLastResponse();
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
        $this->CheckSession('Calendar', 'default');
		$gadget = $GLOBALS['app']->LoadGadget('Users', 'AdminModel');
		return $gadget->AddEmbedSite($gadget, $url, $gadget_url, $layout);
    }

    /**
     * Executes a massive-delete of calendars
     *
     * @access  public
     * @param   array   $pages  Array with the ids of pages
     * @return  array   Response (notice or error)
     */
    function MassiveDelete($pages)
    {
		$this->CheckSession('Calendar', 'ManageCategories');
        $this->_Model->MassiveDelete($pages);
        return $GLOBALS['app']->Session->PopLastResponse();
    }
    
    /**
     * Get total calendars of a search
     *
     * @access  public
     * @param   string  $status  Status of calendar(s) we want to display
     * @param   string  $search  Keyword (title/description) of calendars we want to look for
     * @return  int     Total of posts
     */
    function SizeOfSearch($status, $search)
    {
		$this->CheckSession('Calendar', 'ManageCategories');
        $pages = $this->_Model->SearchCalendars($status, $search, null, 0);
        return count($pages);
    }

    /**
     * Returns an array with all the calendars
     *
     * @access  public
     * @param   string  $status  Status of calendar(s) we want to display
     * @param   string  $search  Keyword (title/description) of calendars we want to look for
     * @param   int     $limit   Data limit
     * @return  array   Calendars data
     */
    function SearchCalendars($status, $search, $limit)
    {
		$this->CheckSession('Calendar', 'ManageCategories');
        $gadget = $GLOBALS['app']->LoadGadget('Calendar', 'AdminHTML');
        if (!is_numeric($limit)) {
            $limit = 0;
        }
        return $gadget->GetCalendars($status, $search, $limit, 0);
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
        $this->CheckSession('Calendar', 'default');
		require_once JAWS_PATH . 'include/Jaws/Image.php';
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$adminHTML = $GLOBALS['app']->LoadGadget('Calendar', 'AdminHTML');
		$model = $GLOBALS['app']->LoadGadget('Calendar', 'Model');
		
		$shout_params = array();
		$shout_params['gadget'] = 'Calendar';
		$res = array();
		
		// Which method
		$result = $adminHTML->form_post(true, $method, $params);
		if ($result === false || Jaws_Error::IsError($result)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('CALENDAR_ERROR_SAVE_QUICKADD'), RESPONSE_ERROR);
			$res['success'] = false;
		} else {
			$id = $result;
			if ($method == 'AddCalendar' || $method == 'EditCalendar') {
				$post = $model->GetCalendar($id);
			} else if ($method == 'AddEvent' || $method == 'EditEvent') {
				$post = $model->GetEvent($id);
			}
		}
		if ($post && !Jaws_Error::IsError($post)) {
			if ($method == 'AddCalendar' || $method == 'EditCalendar') {
				if ($addtype == 'Comment') {
					$shout_params['edit_url'] = $GLOBALS['app']->GetSiteURL() .'/'. $GLOBALS['app']->Map->GetURLFor('Calendar', 'Calendar', array('id' => $id));
				} else {
					$shout_params['edit_url'] = $GLOBALS['app']->GetSiteURL() .'/admin.php?gadget=Calendar&action=form&id='.$id;
				}
				$image = $post['calendarparentimage'];
				$title = $post['calendarparentcategory_name'];
				$description = $post['calendarparentdescription'];
			} else if ($method == 'AddEvent' || $method == 'EditEvent') {
				if ($addtype == 'Comment') {
					$shout_params['edit_url'] = $GLOBALS['app']->GetSiteURL() .'/'. $GLOBALS['app']->Map->GetURLFor('Calendar', 'Detail', array('id' => $id));
				} else {
					$shout_params['edit_url'] = $GLOBALS['app']->GetSiteURL() .'/admin.php?gadget=Calendar&action=A_form&id='.$id;
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
				$el['icon'] = $GLOBALS['app']->GetJawsURL() . '/gadgets/Calendar/images/logo.png';
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