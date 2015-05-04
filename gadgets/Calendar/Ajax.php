<?php
/**
 * Calendar AJAX API
 *
 * @category   Ajax
 * @package    Calendar
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class CalendarAjax extends Jaws_Ajax
{
    /**
     * Constructor
     *
     * @access  public
     */
    function CalendarAjax(&$model)
    {
        $this->_Model =& $model;
    }

     /**
     * Adds an event
     *
     * @access  public
     * @param   int     $eid  Event ID
     * @return  array   Response (notice or error)
     */
    function AddEvent($cid, $title = '', $startdate, $enddate, $iTimeHr = '', $iTimeMin = '', $iTimeSuffix = '', $eTimeHr = '', $eTimeMin = '', $eTimeSuffix = '', $sm_description = '', $description = '', $host = '', $image = '', $alink = '', $alinkTitle = '', $alinkType = '', $isRecurring = 'N', $active = 'Y')
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Calendar', 'ManageEvents') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Calendar', 'OwnEvent') && $title != 'Tentative') {
			$this->CheckSession('Calendar', 'ManageEvents');
		}

		$iTime = '';
		$eTime = '';
        if ($iTimeHr != '' && $eTimeHr != '') {
			$iTime = $iTimeHr.':'.$iTimeMin.' '.strtoupper($iTimeSuffix);
			$eTime = $eTimeHr.':'.$eTimeMin.' '.strtoupper($eTimeSuffix);
		}
		
		if (BASE_SCRIPT == 'index.php' && $title != 'Tentative' || (!$this->GetPermission('Calendar', 'ManageEvents') && $GLOBALS['app']->Session->Logged())) {
			$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');
		} else {
			$OwnerID = 0;
		}
		
		//$model = $GLOBALS['app']->LoadGadget('Calendar', 'Model');
		//$eventinfo = $model->GetEventInfoById($eid);
        //$adminModel = $GLOBALS['app']->LoadGadget('Calendar', 'AdminModel');
		
        $adminModel = $GLOBALS['app']->LoadGadget('Calendar', 'AdminModel');
		$adminModel->AddEvent($title, $startdate, $enddate, $sm_description, $description, $host, $iTime, $eTime, $image, $alink, $alinkTitle, $alinkType, $isRecurring, $active, $OwnerID, $cid);
        return $GLOBALS['app']->Session->PopLastResponse();
    }
     
	 /**
     * Updates an event's date
     *
     * @access  public
     * @param   int     $eid  Event ID
     * @return  array   Response (notice or error)
     */
    function UpdateEventDelta($id, $startDayDelta = 0, $startMinuteDelta = 0, $endDayDelta = 0, $endMinuteDelta = 0, $allDay = false)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Calendar', 'ManageEvents') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Calendar', 'OwnEvent')) {
			$this->CheckSession('Calendar', 'ManageEvents');
		}
		$model = $GLOBALS['app']->LoadGadget('Calendar', 'Model');
		$event = $model->GetEvent($id);
        if (!Jaws_Error::IsError($event) && ($GLOBALS['app']->Session->GetPermission('Calendar', 'ManageEvents') || $GLOBALS['app']->Session->GetAttribute('user_id') == $event['ownerid'])) {
			$startDateStamp = $GLOBALS['app']->UTC2UserTime($event['startdate']);
			$endDateStamp = $GLOBALS['app']->UTC2UserTime($event['enddate']);
			$startTimeStamp = '';
			if (!empty($event['itime']) && $allDay === false) {
				$startTimeStamp = $GLOBALS['app']->UTC2UserTime($event['itime']);
			}
			$endTimeStamp = '';
			if (!empty($event['endtime']) && $allDay === false) {
				$endTimeStamp = $GLOBALS['app']->UTC2UserTime($event['endtime']);
			}
			if ($startDayDelta != 0) {
				$thePHPDate = getdate($startDateStamp);
				$thePHPDate['mday'] = $thePHPDate['mday']+$startDayDelta;
				$startDateStamp = mktime($thePHPDate['hours'], $thePHPDate['minutes'], $thePHPDate['seconds'], $thePHPDate['mon'], $thePHPDate['mday'], $thePHPDate['year']);
				$thePHPDate2 = getdate($endDateStamp);
				$thePHPDate2['mday'] = $thePHPDate2['mday']+$startDayDelta;
				$endDateStamp = mktime($thePHPDate2['hours'], $thePHPDate2['minutes'], $thePHPDate2['seconds'], $thePHPDate2['mon'], $thePHPDate2['mday'], $thePHPDate2['year']);
			}
			
			if ($endDayDelta != 0) {
				$thePHPDate = getdate($endDateStamp);
				$thePHPDate['mday'] = $thePHPDate['mday']+$endDayDelta;
				$endDateStamp = mktime($thePHPDate['hours'], $thePHPDate['minutes'], $thePHPDate['seconds'], $thePHPDate['mon'], $thePHPDate['mday'], $thePHPDate['year']);
			}

			if ($startMinuteDelta != 0) {
				$thePHPDate = getdate($startTimeStamp);
				$thePHPDate['minutes'] = $thePHPDate['minutes']+$startMinuteDelta;
				$startTimeStamp = mktime($thePHPDate['hours'], $thePHPDate['minutes'], $thePHPDate['seconds'], $thePHPDate['mon'], $thePHPDate['mday'], $thePHPDate['year']);
				$thePHPDate2 = getdate($endTimeStamp);
				$thePHPDate2['minutes'] = $thePHPDate2['minutes']+$startMinuteDelta;
				$endTimeStamp = mktime($thePHPDate2['hours'], $thePHPDate2['minutes'], $thePHPDate2['seconds'], $thePHPDate2['mon'], $thePHPDate2['mday'], $thePHPDate2['year']);
			}
			
			if ($endMinuteDelta != 0) {
				$thePHPDate = getdate($endTimeStamp);
				$thePHPDate['minutes'] = $thePHPDate['minutes']+$endMinuteDelta;
				$endTimeStamp = mktime($thePHPDate['hours'], $thePHPDate['minutes'], $thePHPDate['seconds'], $thePHPDate['mon'], $thePHPDate['mday'], $thePHPDate['year']);
			}

			$startdate = date('m/d/Y', $startDateStamp);
			$enddate = date('m/d/Y', $endDateStamp);
			
			$itime = (!empty($startTimeStamp) ? date("g:i A",$startTimeStamp) : '');
			$etime = (!empty($endTimeStamp) ? date("g:i A",$endTimeStamp) : '');
			
			$adminModel = $GLOBALS['app']->LoadGadget('Calendar', 'AdminModel');
			$result = $adminModel->UpdateEvent($id, $event['event'], $startdate, $enddate, $event['sm_description'], $event['description'], $event['host'], $itime, $etime, $event['image'], $event['alink'], $event['alinktitle'], $event['alinktype'], $event['isrecurring'], $event['active']);
			if (Jaws_Error::IsError($result)) {
				//$GLOBALS['app']->Session->PushLastResponse(_t('CALENDAR_EVENT_NOT_UPDATED'), RESPONSE_ERROR);
				$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
			}
		} else {
			//$GLOBALS['app']->Session->PushLastResponse(_t('CALENDAR_EVENT_NOT_UPDATED'), RESPONSE_ERROR);
			if (Jaws_Error::IsError($event)) {
				$GLOBALS['app']->Session->PushLastResponse($event->GetMessage(), RESPONSE_ERROR);
			} else if (!$GLOBALS['app']->Session->GetPermission('Calendar', 'ManageEvents')) {
				$GLOBALS['app']->Session->PushLastResponse('No permission to ManageEvents', RESPONSE_ERROR);
			} else {
				$GLOBALS['app']->Session->PushLastResponse('Owner ID not valid', RESPONSE_ERROR);
			}
		}
        return $GLOBALS['app']->Session->PopLastResponse();
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
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Calendar', 'ManageEvents') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Calendar', 'OwnEvent')) {
			$this->CheckSession('Calendar', 'ManageEvents');
		}

        $model = $GLOBALS['app']->LoadGadget('Calendar', 'Model');
		$event = $model->GetEvent($eid);
        if (!Jaws_Error::IsError($event) && (($GLOBALS['app']->Session->GetPermission('Calendar', 'ManageEvents')) || $GLOBALS['app']->Session->GetAttribute('user_id') == $event['ownerid'])) {
			$adminModel = $GLOBALS['app']->LoadGadget('Calendar', 'AdminModel');
			$adminModel->DeleteEvent($eid);
		} else {
			$GLOBALS['app']->Session->PushLastResponse(_t('CALENDAR_EVENT_CANT_DELETE'), RESPONSE_ERROR);
		}
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
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Calendar', 'ManageCategories') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Calendar', 'OwnCategory')) {
			$this->CheckSession('Calendar', 'ManageCategories');
		}

        $model = $GLOBALS['app']->LoadGadget('Calendar', 'Model');
		$calendar = $model->GetCalendar($cid);
        if (!Jaws_Error::IsError($calendar)) {
			if (($GLOBALS['app']->Session->GetPermission('Calendar', 'ManageCategories') && BASE_SCRIPT != 'index.php') || $GLOBALS['app']->Session->GetAttribute('user_id') == $calendar['calendarparentownerid']) {
				$adminModel = $GLOBALS['app']->LoadGadget('Calendar', 'AdminModel');
				$adminModel->DeleteCalendar($cid);
			} else {
				$GLOBALS['app']->Session->PushLastResponse(_t('CALENDAR_CATEGORY_CANT_DELETE'), RESPONSE_ERROR);
			}
		} else {
			$GLOBALS['app']->Session->PushLastResponse($calendar->GetMessage(), RESPONSE_ERROR);
		}
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
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Calendar', 'ManageCategories') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Calendar', 'OwnCategory')) {
			$this->CheckSession('Calendar', 'ManageCategories');
		}
		$gadget = $GLOBALS['app']->LoadGadget('Users', 'AdminModel');
		return $gadget->AddEmbedSite($gadget, $url, $gadget_url, $layout);
    }

    /**
     * Executes a massive-delete of Calendars
     *
     * @access  public
     * @param   array   $pages  Array with the ids of pages
     * @return  array   Response (notice or error)
     */
    function MassiveDelete($pages)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Calendar', 'ManageCategories') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Calendar', 'OwnCategory')) {
			$this->CheckSession('Calendar', 'ManageCategories');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Calendar', 'AdminModel');
        $gadget->MassiveDelete($pages);
        return $GLOBALS['app']->Session->PopLastResponse();
    }
    
    /**
     * Get total Calendars of a search
     *
     * @access  public
     * @param   string  $status  Status of Calendar(s) we want to display
     * @param   string  $search  Keyword (title/description) of Calendars we want to look for
     * @return  int     Total of posts
     */
    function SizeOfSearch($status, $search)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Calendar', 'ManageCategories') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Calendar', 'OwnCategory')) {
			$this->CheckSession('Calendar', 'ManageCategories');
		}
        $pages = $this->_Model->SearchCalendars($status, $search, null, $GLOBALS['app']->Session->GetAttribute('user_id'));
        return count($pages);
    }

    /**
     * Returns an array with all the Calendars
     *
     * @access  public
     * @param   string  $status  Status of Calendar(s) we want to display
     * @param   string  $search  Keyword (title/description) of Calendars we want to look for
     * @param   int     $limit   Data limit
     * @return  array   Calendar data
     */
    function SearchCalendars($status, $search, $limit)
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$this->GetPermission('Calendar', 'ManageCategories') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Calendar', 'OwnCategory')) {
			$this->CheckSession('Calendar', 'ManageCategories');
		}
        $gadget = $GLOBALS['app']->LoadGadget('Calendar', 'AdminHTML');
        if (!is_numeric($limit)) {
            $limit = 0;
        }
        return $gadget->GetCalendars($status, $search, $limit, $GLOBALS['app']->Session->GetAttribute('user_id'));
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
		if (!$this->GetPermission('Calendar', 'ManageCategories') && !$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Calendar', 'OwnEvent')) {
			$this->CheckSession('Calendar', 'ManageEvents');
		}
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
				$action_str = (substr($method, 0, 3) == 'Add' ? "has added a new calendar" : '');
				if ($addtype == 'Comment') {
					$shout_params['edit_url'] = $GLOBALS['app']->GetSiteURL() .'/'. $GLOBALS['app']->Map->GetURLFor('Calendar', 'Calendar', array('id' => $id));
				} else {
					$shout_params['edit_url'] = $GLOBALS['app']->GetSiteURL() .'/index.php?gadget=Calendar&action=account_form&id='.$id;
				}
				$image = $post['calendarparentimage'];
				$title = $post['calendarparentcategory_name'];
				$description = $post['calendarparentdescription'];
			} else if ($method == 'AddEvent' || $method == 'EditEvent') {
				$action_str = (substr($method, 0, 3) == 'Add' ? "has added a new event" : "has updated an event");
				if ($addtype == 'Comment') {
					$shout_params['edit_url'] = $GLOBALS['app']->GetSiteURL() .'/'. $GLOBALS['app']->Map->GetURLFor('Calendar', 'Detail', array('id' => $id));
					$post['html'] = '';
					$hook = $GLOBALS['app']->loadHook('Calendar', 'Comment');
					if ($hook !== false) {
						if (method_exists($hook, 'GetCalendarComment')) {
							$comment = $hook->GetCalendarComment(array('gadget_reference' => $post['id'], 'public' => false));
							if (!Jaws_Error::IsError($comment) && isset($comment['msg_txt']) && !empty($comment['msg_txt'])) {
								$post['html'] = $comment['msg_txt'];
							}
						}
					}
				} else {
					$shout_params['edit_url'] = $GLOBALS['app']->GetSiteURL() .'/index.php?gadget=Calendar&action=account_A_form&id='.$id;
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
    function NewCalendarComment($title = '', $comments, $parent, $parentId, $ip = '', $set_cookie = true, $sharing = 'everyone', $reply = false, $eventid = null)
    {
        $res = array();
		if (!$GLOBALS['app']->Session->Logged()) {
	        //require_once JAWS_PATH . 'include/Jaws/Header.php';
			//Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'));
			$this->CheckSession('Calendar', 'ManageEvents');
		} else {
			$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');
			if (empty($parentId)) {
				$parentId = $GLOBALS['app']->Session->GetAttribute('user_id');
			}
			require_once JAWS_PATH . 'include/Jaws/User.php';
			$jUser = new Jaws_User();
			$info = $jUser->GetUserInfoById((int)$GLOBALS['app']->Session->GetAttribute('user_id'), true, true, true, true);
			// if a response already exists from a user, delete it.
			if (
				substr($comments, 0, 12) == 'is attending' || 
				substr($comments, 0, 18) == 'might be attending' || 
				substr($comments, 0, 16) == 'is not attending'
			) {
				$params = array();
				$params['id'] = (int)$GLOBALS['app']->Session->GetAttribute('user_id');
				$params['gadget'] = 'Calendar';
				$params['gadget_reference'] = (int)$parentId;
				$params['like1'] = 'is attending%';
				$params['like2'] = 'might be attending%';
				$params['like3'] = 'is not attending%';
				$sql = '
					SELECT [id] FROM [[comments]] 
					WHERE 
						([ownerid] = {id}) AND ([gadget] = {gadget}) AND 
						([gadget_reference] = {gadget_reference}) AND 
						([msg_txt] LIKE {like1} OR [msg_txt] LIKE {like2} OR [msg_txt] LIKE {like3})';
				$existing = $GLOBALS['db']->queryAll($sql, $params);
				if (Jaws_Error::IsError($existing)) {
					$res['css'] = 'error-message';
					$res['message'] = _t('GLOBAL_ERROR_COMMENT_ADDED');
					return $res;
				} else {
					$res['delete_ids'] = $existing;
				}
			}
			$eventid = str_replace('Usersevent', '', $eventid);
			if ((int)$eventid != (int)$parent) {
				$calendarModel = $GLOBALS['app']->LoadGadget('Calendar', 'Model');
				$eventInfo = $calendarModel->GetEvent((int)$eventid);
				if (
					!Jaws_Error::IsError($eventInfo) && isset($eventInfo['id']) && !empty($eventInfo['id']) && 
					(($eventInfo['max_occupancy'] >= (count(explode(',',$eventInfo['occupants']))+1) && substr($comments, 0, 12) == 'is attending') || 
					substr($comments, 0, 16) == 'is not attending')
				) {
					$params 		= array();
					if (substr($comments, 0, 12) == 'is attending') {
						if (!in_array($OwnerID, explode(',', $eventInfo['occupants']))) {
							if ($eventInfo['occupants'] == '') {
								$eventInfo['occupants'] = $OwnerID;
							} else {
								$eventInfo['occupants'] = $eventInfo['occupants'].','.$OwnerID;
							}
						}
					}
					if (substr($comments, 0, 16) == 'is not attending') {
						if (in_array($OwnerID, explode(',', $eventInfo['occupants']))) {
							if ($eventInfo['occupants'] == $OwnerID) {
								$eventInfo['occupants'] = '';
							} else {
								$new_occupants = array();
								foreach (explode(',', $eventInfo['occupants']) as $occupant) {
									if ($occupant != $OwnerID) {
										$new_occupants[] = $OwnerID;
									}
								}
								$eventInfo['occupants'] = implode(',', $new_occupants);
							}
						}
					}
					$params['occupants']    = $eventInfo['occupants'];
					$params['id']	= $eventInfo['id'];
							
					$sql = '
						UPDATE [[calendar]] SET
							[occupants]       = {occupants}
						WHERE [id] = {id}';

					$result = $GLOBALS['db']->query($sql, $params);
					if (Jaws_Error::IsError($result)) {
						$res['css'] = 'error-message';
						$res['message'] = $result->GetMessage();
						//return false;
					}
					
					// Let everyone know
					$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
					$result = $GLOBALS['app']->Shouter->Shout('onUpdateCalendarEvent', $id);
					if (Jaws_Error::IsError($result) || !$result) {
						$res['css'] = 'error-message';
						$res['message'] = $result->GetMessage();
					}
				}
			}
			$model = $GLOBALS['app']->LoadGadget('Users', 'Model');
			$result = $model->NewComment(
				(!empty($info['company']) ? $info['company'] : $info['nickname']), $title, $info['url'], $info['email'], $comments, 
				(int)$parent, (int)$parentId, $ip, $set_cookie, (int)$GLOBALS['app']->Session->GetAttribute('user_id'), $sharing, 'Calendar'
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
    function DeleteCalendarComment($id)
    {
		if (!$GLOBALS['app']->Session->Logged()) {
	        //require_once JAWS_PATH . 'include/Jaws/Header.php';
			//Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'));
			$this->CheckSession('Calendar', 'ManageEvents');
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
	
	/**
     * Shows more comments
     *
     * @access  public
     * @param   int     $id   Comment ID
     * @return  array   Response (notice or error)
     */
    function ShowMoreCalendarComments($public = false, $id = null, $interactive = true, $limit = 5)
    {
/*
		if (!$GLOBALS['app']->Session->Logged()) {
			//require_once JAWS_PATH . 'include/Jaws/Header.php';
			//Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'));
			$GLOBALS['app']->Session->PushLastResponse("User not logged in.", RESPONSE_ERROR);
			$this->CheckSession('Users', 'default');
		} else {
*/
			$res = array();
			//$uid = $GLOBALS['app']->Session->GetAttribute('user_id');
			$usersHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
			/*
			$items_on_layout = explode(',', $items_on_layout);
			foreach ($items_on_layout as $on_layout) {
				if (!is_null($on_layout) && !empty($on_layout)) {
					$GLOBALS['app']->_ItemsOnLayout[] = $on_layout;
				}
			}
			*/
			if (is_array($id) && !count($id) <= 0) {
				$comments_html = $usersHTML->ShowComments('Calendar', $public, $id, '', $interactive, 2, false, false, 9999);
			} else {
				$comments_html = $usersHTML->ShowComments('Calendar', $public, $id, '', $interactive, 2, false, true, (int)$limit);
			}
			if (!Jaws_Error::IsError($comments_html)) {
				$res['css'] = 'notice-message';
				$res['message'] = _t('GLOBAL_COMMENT_ADDED');
				$res['comments_html'] = $comments_html;
				/*
				$res['items_limit'] = 0;
				$items_on_layout = array();
				foreach ($GLOBALS['app']->_ItemsOnLayout as $on_layout) {
					if (substr($on_layout, 0, strlen('_total'.$gadget.'_')) == '_total'.$gadget.'_') {
						$res['items_limit'] = str_replace('_total'.$gadget.'_', '', $on_layout);
					}
					if (substr($on_layout, 0, strlen('_'.$gadget)) == '_'.$gadget) {
						$items_on_layout[] = $on_layout;
					}
				}
				$res['items_limit'] = (int)$res['items_limit'];
				if ($res['items_limit'] == 0) {
					$res['items_limit'] = ((int)$limit+5);
				}
				$res['items_on_layout'] = implode(',',$items_on_layout);
				*/
			} else {
				$res['css'] = 'error-message';
				$res['message'] = $comments_html->GetMessage();
			}
			return $res;
//		}
    }
}