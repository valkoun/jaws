<?php
/**
 * Properties - Calendar gadget hook
 *
 * @category   GadgetHook
 * @package    Properties
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class PropertiesCalendarHook
{

    /**
     * Get Calendar reference of gadget request
     *
     * @access  public
     * @param   string   $request_action  Action string (scope)
     * @param   int   $request_id  ID of the property
     * @return  bool    Success/failure
     */
    function GetCalendarReferenceOfRequest($request_action = null, $request_id = null)
    {
		$model = $GLOBALS['app']->LoadGadget('Properties', 'Model');
		$result = array();
		switch ($request_action) {
			case 'Property':
				$result = $model->GetProperty($request_id);
				break;
		}
		return $result;
	}
	
	/**
     * Gets the calendar availability by given date range
     *
     * @access  public
     * @param   array     $params  Array of parameters
     * @return  mixed   Returns an array with the maps and false on error
     */
    function GetGadgetAvailabilityByDateRange($params = array())
    {
		$result = array();
		if ($params['gadget_action'] == 'Property') {
			$startdate = $params['startdate'];
			$enddate = $params['enddate'];
			$OwnerID = 0;
			if (isset($params['uid']) && !empty($params['uid'])) {
				$OwnerID = $params['uid'];
			}
			$active = null;
			if (isset($params['active']) && !empty($params['active'])) {
				$active = $params['active'];
			}
			$property_calendars = array();
			$calendarModel = $GLOBALS['app']->LoadGadget('Calendar', 'Model');
			
			$events = $calendarModel->GetReservations($OwnerID, null, false, 'created', 'ASC', $active, $startdate, $enddate, 'Properties', 'Property');
			if (!Jaws_Error::IsError($events)) {
				foreach ($events as $event) {
					$calendar = $calendarModel->GetCalendar($event['linkid']);
					if (!Jaws_Error::IsError($calendar)) {
						if (!in_array($calendar['calendarparentgadget_reference'], $property_calendars)) {
							$property_calendars[] = $calendar['calendarparentgadget_reference'];
						}
					}
				}
				$model = $GLOBALS['app']->LoadGadget('Properties', 'Model');
				$properties = $model->GetProperties();
				if (!Jaws_Error::IsError($properties)) {
					foreach ($properties as $property) {
						if (!in_array($property['id'], $property_calendars)) {
							$add_property = true;
							if (!is_null($active) && $property['active'] != $active) {
								$add_property = false;
							}
							if ($add_property === true) {
								$result[] = $property;
							}
						}
					}
				}
			} else {
				return new Jaws_Error($events->GetMessage(), _t('PROPERTIES_NAME'));
			}
		}
        return $result;
    }
    	
    /**
     * Adds Property Calendar when a property is added
     *
     * @access  public
     * @param   int   $id  ID of the property that was added
     * @return  bool    Success/failure
     */
    function AddCalendar($id)
    {
		// Insert Property Calendar
		$GLOBALS['app']->Translate->LoadTranslation('Calendar', JAWS_GADGET);
		$calendarAdmin = $GLOBALS['app']->LoadGadget('Calendar', 'AdminModel');
		$model = $GLOBALS['app']->LoadGadget('Properties', 'Model');
		$page = $model->GetProperty($id);
		if (Jaws_Error::IsError($page)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('CALENDAR_CATEGORY_NOT_ADDED'), RESPONSE_ERROR);
			return new Jaws_Error(_t('CALENDAR_CATEGORY_NOT_ADDED'), _t('CALENDAR_NAME'));
		} else if (
			$page['calendar_link'] == '' && ($page['status'] == 'forrent' || $page['status'] == 'rented' || 
			$page['status'] == 'forlease' || $page['status'] == 'leased')
		) {
			$calendarparentCategory_Name = '';
			if (isset($page['title']) && !empty($page['title'])) {
				$calendarparentCategory_Name = $page['title'];
			}
			$calendarparentImage = '';
			if (isset($page['image']) && !empty($page['image'])) {
				$calendarparentImage = $page['image'];
			}
			$calendarparentDescription = '';
			if (isset($page['description']) && !empty($page['description'])) {
				$calendarparentDescription = $page['description'];
			}
			if (isset($page['sm_description']) && !empty($page['sm_description'])) {
				$calendarparentDescription = $page['sm_description'];
			}
			$calendarparentActive = 'N';
			if (isset($page['active']) && !empty($page['active'])) {
				$calendarparentActive = $page['active'];
			}
			$calendarparentOwnerID = null;
			if (isset($page['ownerid']) && !empty($page['ownerid']) && $page['ownerid'] > 0) {
				$calendarparentOwnerID = $page['ownerid'];
			}
			$result = $calendarAdmin->AddCalendar(
				0, $calendarparentCategory_Name, $calendarparentImage, $calendarparentDescription, 
				$calendarparentActive, $calendarparentOwnerID, 'N', 'A', 'Properties', 'Property', $id
			);
			if (Jaws_Error::isError($result)) {
				$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
				return new Jaws_Error($result->GetMessage(), _t('CALENDAR_NAME'));
			}
		}
		return true;
	}

    /**
     * Updates Property Calendar when a property is updated
     *
     * @access  public
     * @param   int   $id  ID of the property that was updated
     * @return  bool    Success/failure
     */
    function UpdateCalendar($id)
    {
		// Update Property Calendar
		$GLOBALS['app']->Translate->LoadTranslation('Calendar', JAWS_GADGET);
		$calendarAdmin = $GLOBALS['app']->LoadGadget('Calendar', 'AdminModel');
		$calendarModel = $GLOBALS['app']->loadGadget('Calendar', 'Model');
		$model = $GLOBALS['app']->LoadGadget('Properties', 'Model');
		$page = $model->GetProperty($id);
		if (Jaws_Error::IsError($page)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('CALENDAR_CATEGORY_NOT_UPDATED'), RESPONSE_ERROR);
			return new Jaws_Error(_t('CALENDAR_CATEGORY_NOT_UPDATED'), _t('CALENDAR_NAME'));
		} else if (
			$page['calendar_link'] == '' && ($page['status'] == 'forrent' || $page['status'] == 'rented' || 
			$page['status'] == 'forlease' || $page['status'] == 'leased')
		) {
			// Get property calendar(s)
			$cids = $calendarModel->GetAllCalendars(
				$page['ownerid'], null, false, 'calendarparentsort_order', 'ASC', 'Y', 'Properties', 'Property', $id
			);
			if (Jaws_Error::IsError($cids)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('CALENDAR_ERROR_CALENDARS_NOT_RETRIEVED'), RESPONSE_ERROR);
				return new Jaws_Error(_t('CALENDAR_ERROR_CALENDARS_NOT_RETRIEVED'), _t('CALENDAR_NAME'));
			}
			$calendarparentCategory_Name = '';
			if (isset($page['title']) && !empty($page['title'])) {
				$calendarparentCategory_Name = $page['title'];
			}
			$calendarparentImage = '';
			if (isset($page['image']) && !empty($page['image'])) {
				$calendarparentImage = $page['image'];
			}
			$calendarparentDescription = '';
			if (isset($page['description']) && !empty($page['description'])) {
				$calendarparentDescription = $page['description'];
			}
			if (isset($page['sm_description']) && !empty($page['sm_description'])) {
				$calendarparentDescription = $page['sm_description'];
			}
			$calendarparentActive = 'N';
			if (isset($page['active']) && !empty($page['active'])) {
				$calendarparentActive = $page['active'];
			}
			$calendarparentOwnerID = null;
			if (isset($page['ownerid']) && !empty($page['ownerid']) && $page['ownerid'] > 0) {
				$calendarparentOwnerID = $page['ownerid'];
			}
			$calendars_found = 0;
			foreach ($cids as $cid) {
				if (isset($cid['calendarparentid']) && !empty($cid['calendarparentid'])) {
					$result = $calendarAdmin->UpdateCalendar(
						$cid['calendarparentid'], $cid['calendarparentsort_order'], $calendarparentCategory_Name, 
						$calendarparentImage, $calendarparentDescription, $calendarparentActive, 
						'N', 'A', 'Properties', 'Property', $id
					);
					if (Jaws_Error::IsError($result)) {
						$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
						return new Jaws_Error($result->GetMessage(), _t('CALENDAR_NAME'));
					}
					$calendars_found++;
				}
			}
			if ($calendars_found == 0) {
				$result = $calendarAdmin->AddCalendar(
					0, $calendarparentCategory_Name, $calendarparentImage, $calendarparentDescription, $calendarparentActive, 
					$calendarparentOwnerID, 'N', 'A', 'Properties', 'Property', $id
				);
				if (Jaws_Error::IsError($result)) {
					$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
					return new Jaws_Error($result->GetMessage(), _t('CALENDAR_NAME'));
				}
			}
		}
		return true;
	}

    /**
     * Removes Property Calendar when a property is deleted
     *
     * @access  public
     * @param   int   $id  ID of the property that was deleted
     * @return  bool    Success/failure
     */
    function DeleteCalendar($id)
    {
		// Update Property Calendar
		$GLOBALS['app']->Translate->LoadTranslation('Calendar', JAWS_GADGET);
		$calendarAdmin = $GLOBALS['app']->LoadGadget('Calendar', 'AdminModel');
		$calendarModel = $GLOBALS['app']->LoadGadget('Calendar', 'Model');
		$model = $GLOBALS['app']->LoadGadget('Properties', 'Model');
		$page = $model->GetProperty($id);
		if (Jaws_Error::IsError($page)) {
			//$GLOBALS['app']->Session->PushLastResponse(_t('CALENDAR_CATEGORY_CANT_DELETE'), RESPONSE_ERROR);
			//return new Jaws_Error(_t('CALENDAR_CATEGORY_CANT_DELETE'), _t('CALENDAR_NAME'));
			$GLOBALS['app']->Session->PushLastResponse($page->GetMessage(), RESPONSE_ERROR);
			return new Jaws_Error($page->GetMessage(), _t('CALENDAR_NAME'));
		} else {
			// Get property calendar(s)
			$cids = $calendarModel->GetAllCalendars(
				$page['ownerid'], null, false, 'calendarparentsort_order', 'ASC', 'Y', 'Properties', 'Property', $id
			);
			if (Jaws_Error::IsError($cids)) {
				//$GLOBALS['app']->Session->PushLastResponse(_t('CALENDAR_ERROR_CALENDARS_NOT_RETRIEVED'), RESPONSE_ERROR);
				//return new Jaws_Error(_t('CALENDAR_ERROR_CALENDARS_NOT_RETRIEVED'), _t('CALENDAR_NAME'));
				$GLOBALS['app']->Session->PushLastResponse($cids->GetMessage(), RESPONSE_ERROR);
				return new Jaws_Error($cids->GetMessage(), _t('CALENDAR_NAME'));
			}
			foreach ($cids as $cid) {
				if (isset($cid['calendarparentid']) && !empty($cid['calendarparentid'])) {
					$result = $calendarAdmin->DeleteCalendar($cid['calendarparentid']);
					if (Jaws_Error::isError($result)) {
						//$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
						//return new Jaws_Error($result->GetMessage(), _t('CALENDAR_NAME'));
						$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
						return new Jaws_Error($result->GetMessage(), _t('CALENDAR_NAME'));
					}
				}
			}
		}
		return true;
	}
	
    /**
     * Get Calendar inquiry form of gadget request
     *
     * @access  public
     * @param   int   $id  ID of the property that was deleted
     * @return  bool    Success/failure
     */
    function GetCalendarInquiryForm($params = array())
    {
		$cid = $params['calendarid'];
		$gadget_action = $params['gadget_action'];
		$id = $params['gadget_reference'];
		$OwnerID = null;
		if (isset($params['uid']) && !is_null($params['uid']) && !empty($params['uid'])) {
			$OwnerID = $params['uid'];
		}
		$all_availability = $params['all_availability'];
		$calendarModel = $GLOBALS['app']->LoadGadget('Calendar', 'Model');
		$calendarParent = $calendarModel->GetCalendar($cid);
		$model = $GLOBALS['app']->LoadGadget('Properties', 'Model');
		$page = $model->GetProperty($id);
		if (!Jaws_Error::isError($page) && isset($page['id']) && !empty($page['id'])) {
			/*
			Custom Form implementation
			- Add "__REQUIRED__" to any question title to make the field required
			- Add "__EXTRA_RECIPIENT__" to add the field as a recipient
			- Add "__REDIRECT__" to specify where we are coming from/return URL after form submission
			- Add "__MESSAGE__" to show as a message in the resultant e-mail
			*/	
			$formsLayout = $GLOBALS['app']->LoadGadget('Forms', 'LayoutHTML');
			$now = $GLOBALS['db']->Date();
			if (isset($page['id']) && !empty($page['id'])) {
				$redirect = $GLOBALS['app']->GetSiteURL().'/'.$GLOBALS['app']->Map->GetURLFor('Properties', 'Property', array('id' => $page['fast_url']));
			} else if ($all_properties === true) {
				$redirect = $GLOBALS['app']->GetSiteURL().'/'.$GLOBALS['app']->Map->GetURLFor('Properties', 'Category', array('id' => 'all'));
			} else {
				$redirect = $GLOBALS['app']->GetSiteURL().'/index.php?gadget=Calendar&action=Calendar&cid='.$cid.'&mode=year&all_availability='.
					($all_availability === true ? 'true' : 'false').'&owner_id='.$OwnerID;
			}
			
			require_once JAWS_PATH . 'include/Jaws/User.php';
			$jUser = new Jaws_User;
			if (isset($page['id']) && !empty($page['id'])) {
				if ((int)$page['ownerid'] > 0) {
					$info  = $jUser->GetUserInfoById((int)$page['ownerid'], true, true, true, true);
					$recipient = $info['email'];
				} else if(isset($page['agent_email']) && !empty($page['agent_email'])) {
					$recipient = $xss->filter(strip_tags($page['agent_email']));
				} else if(isset($page['broker_email']) && !empty($page['broker_email'])) {
					$recipient = $xss->filter(strip_tags($page['broker_email']));
				} else {
					$recipient = $GLOBALS['app']->Registry->Get('/network/site_email');
				}
			} else if (!is_null($OwnerID) && (int)$OwnerID > 0) {
				$info = $jUser->GetUserInfoById((int)$OwnerID, true, true, true, true);
				$recipient = $info['email'];
			} else {
				$recipient = $GLOBALS['app']->Registry->Get('/network/site_email');
			}
			
			$property_reservation_form = $formsLayout->Display(
				null, 
				true, 
				array(
					'id' => 'custom', 'sort_order' => 0, 'title' => 'Availability Inquiry for '.$calendarParent['calendarparentcategory_name'], 
					'sm_description' => '', 'description' => "Inquiring about the availability of our properties? Use the form below.", 
					'clause' => '', 'image' => '', 'recipient' => $recipient, 'parent' => 0, 'custom_action' => '', 'fast_url' => '', 
					'active' => 'Y', 'ownerid' => 0, 'created' => $now, 'updated' => $now, 
					'submit_content' => "<div style='margin-bottom: 10px;'>Thank you for taking the time to ask us about our properties! 
						We'll review your inquiry and get back to you when necessary.</div><div><a href=\"javascript:history.go(-1);\">
						Click here to go back</a>.</div>",
					'checksum' => ''
				),
				array(
					array(
						'id' => 13, 'sort_order' => 0, 'formid' => 'custom', 'title' => "__MESSAGE__", 'itype' => 'HiddenField', 
						'required' => 'N', 'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''
					),
					array(
						'id' => 2, 'sort_order' => 1, 'formid' => 'custom', 'title' => '__FROM_EMAIL____REQUIRED__', 
						'itype' => 'TextBox', 'required' => 'N', 'ownerid' => 0, 'created' => $now, 'updated' => $now,
						'checksum' => ''
					),
					array(
						'id' => 1, 'sort_order' => 2, 'formid' => 'custom', 'title' => '__FROM_NAME__', 'itype' => 'TextBox', 
						'required' => 'N', 'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''
					), 
					array(
						'id' => 3, 'sort_order' => 3, 'formid' => 'custom', 'title' => "Arrival Date__REQUIRED__", 
						'itype' => 'TextBox', 'required' => 'N', 'ownerid' => 0,  'created' => $now, 'updated' => $now,
						'checksum' => ''
					),
					array(
						'id' => 4, 'sort_order' => 4, 'formid' => 'custom', 'title' => "Departure Date", 'itype' => 'TextBox', 
						'required' => 'N', 'ownerid' => 0, 'created' => $now,  'updated' => $now, 'checksum' => ''
					),
					array(
						'id' => 5, 'sort_order' => 5, 'formid' => 'custom', 'title' => "Phone", 'itype' => 'TextBox', 
						'required' => 'N', 'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''
					),
					array(
						'id' => 6, 'sort_order' => 6, 'formid' => 'custom', 'title' => "Address", 'itype' => 'TextBox', 
						'required' => 'N', 'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''
					),
					array(
						'id' => 7, 'sort_order' => 7, 'formid' => 'custom', 'title' => "City", 'itype' => 'TextBox', 
						'required' => 'N', 'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''
					),
					array(
						'id' => 8, 'sort_order' => 8, 'formid' => 'custom', 'title' => "State or Province", 
						'itype' => 'TextBox', 'required' => 'N', 'ownerid' => 0, 'created' => $now, 
						'updated' => $now, 'checksum' => ''
					),
					array(
						'id' => 9, 'sort_order' => 9, 'formid' => 'custom', 'title' => "Zip", 'itype' => 'TextBox', 
						'required' => 'N', 'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''
					),
					array(
						'id' => 10, 'sort_order' => 10, 'formid' => 'custom', 'title' => "__REDIRECT__", 
						'itype' => 'HiddenField', 'required' => 'N', 'ownerid' => 0, 'created' => $now, 
						'updated' => $now, 'checksum' => ''
					),
					array(
						'id' => 11, 'sort_order' => 11, 'formid' => 'custom', 'title' => "Best Time To Reach", 
						'itype' => 'RadioBtn', 'required' => 'N', 'ownerid' => 0, 'created' => $now, 
						'updated' => $now, 'checksum' => ''
					),
					array(
						'id' => 12, 'sort_order' => 12, 'formid' => 'custom', 'title' => "Message", 
						'itype' => 'TextArea', 'required' => 'N', 'ownerid' => 0, 'created' => $now, 
						'updated' => $now, 'checksum' => ''
					)
				), 
				array(
					array(
						'id' => 1, 'sort_order' => 0, 'linkid' => 10, 'formid' => 'custom', 
						'title' => "<a href='".$redirect."'>".$redirect."</a>",
						'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''
					),
					array(
						'id' => 2, 'sort_order' => 0, 'linkid' => 13, 'formid' => 'custom', 
						'title' => "A message has been received for ".htmlentities($calendarParent['calendarparentcategory_name'])." via 
							the Availability Calendar",
						'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''
					),
					array(
						'id' => 3, 'sort_order' => 0, 'linkid' => 11, 'formid' => 'custom', 'title' => "Any",
						'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''
					),
					array(
						'id' => 4, 'sort_order' => 1, 'linkid' => 11, 'formid' => 'custom', 'title' => "Morning",
						'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''
					),
					array(
						'id' => 5, 'sort_order' => 2, 'linkid' => 11, 'formid' => 'custom', 'title' => "Afternoon",
						'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''
					),
					array(
						'id' => 6, 'sort_order' => 3, 'linkid' => 11, 'formid' => 'custom', 'title' => "Evening",
						'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''
					),
					array(
						'id' => 7, 'sort_order' => 0, 'linkid' => 3, 'formid' => 'custom', 'title' => '__STARTDATE__',
						'ownerid' => 0, 'created' => $now,  'updated' => $now, 'checksum' => ''
					),
					array(
						'id' => 8, 'sort_order' => 0, 'linkid' => 4,  'formid' => 'custom', 'title' => '', 
						'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''
					),
				)
			);
		}
	}
	
    /**
     * Show Property Calendar
     *
     * @access  public
     * @param   int   $id  ID of the property
     * @return  bool    Success/failure
     */
    function ShowCalendar($params = array())
    {
		$mode = 'LayoutYear';
		if (isset($params['mode']) && !empty($params['mode'])) {
			$mode = $params['mode'];
		}
		$id = 'all';
		if (isset($params['gadget_reference']) && !empty($params['gadet_reference'])) {
			$id = $params['gadget_reference'];
		}
		$OwnerID = null;
		if (isset($params['uid']) && !empty($params['uid'])) {
			$OwnerID = $params['uid'];
		}
		
		$tpl = new Jaws_Template('gadgets/Calendar/templates/');
		$tpl->Load('normal.html');

		$tpl->SetBlock('calendar');
		$tpl->SetVariable('id', 'PropertyCalendar_'.$id);

		$tpl->SetBlock('calendar/content');
				
		$calendarModel = $GLOBALS['app']->LoadGadget('Calendar', 'Model');
		$calendarLayout = $GLOBALS['app']->LoadGadget('Calendar', 'LayoutHTML');
        $calendar_html = '';
		if ($id != 'all') {
			$calendars = $calendarModel->GetAllCalendars(
				$OwnerID, null, false, 'calendarparentsort_order', 'ASC', 'Y', 'Properties', 'Property', ($id == 'all' ? null : (int)$id)
			);
			if (!Jaws_Error::IsError($calendars)) {
				// TODO: Support multiple calendars per gadget_reference
				foreach ($calendars as $calendar) {
					if (isset($calendar['calendarparentid']) && !empty($calendar['calendarparentid'])) {
						$calendar_html = $calendarLayout->Display($calendar['calendarparentid'], $mode, false);
						$calendar_html .= '<style type="text/css">
							#layout_LayoutYear_'.$calendar['calendarparentid'].'__title { display: none; }
						</style>'."\n";
						break;
					}
				}
			}
		} else {
			$calendar_html = $calendarLayout->Display(null, $mode, true);
			$calendar_html .= '<style type="text/css">
				#layout_LayoutYear_All__title { display: none; }
			</style>'."\n";
		}
		$tpl->SetVariable('content', $calendar_html);
		
		$tpl->ParseBlock('calendar/content');
		$tpl->ParseBlock('calendar');

		return $tpl->Get();
	}
	
}
