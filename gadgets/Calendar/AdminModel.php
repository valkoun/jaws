<?php
/**
 * Calendar Gadget
 *
 * @category   GadgetModel
 * @package    Calendar
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */

require_once JAWS_PATH . 'gadgets/Calendar/Model.php';
class CalendarAdminModel extends CalendarModel
{
    var $_Name = 'Calendar';
	
	/**
     * Install the gadget
     *
     * @access  public
     * @return  boolean  Success/failure
     */
    function InstallGadget()
    {

        $result = $this->installSchema('schema.xml');
        if (Jaws_Error::IsError($result)) {
            return $result;
        }

        if (file_exists(JAWS_PATH . 'gadgets/'.$this->_Name.'/schema/insert.xml')) {
			$variables = array();
			$variables['timestamp'] = $GLOBALS['db']->Date();

			$result = $this->installSchema('insert.xml', $variables, 'schema.xml', true);
			if (Jaws_Error::IsError($result)) {
				return $result;
			}
		}

        // Events
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
        $GLOBALS['app']->Shouter->NewShouter('Calendar', 'onAddCalendar');   				// trigger an action when we add a parent
        $GLOBALS['app']->Shouter->NewShouter('Calendar', 'onDeleteCalendar');				// trigger an action when we delete a parent
        $GLOBALS['app']->Shouter->NewShouter('Calendar', 'onUpdateCalendar');				// and when we update a parent..
        $GLOBALS['app']->Shouter->NewShouter('Calendar', 'onAddCalendarEvent');   		
        $GLOBALS['app']->Shouter->NewShouter('Calendar', 'onDeleteCalendarEvent');		
        $GLOBALS['app']->Shouter->NewShouter('Calendar', 'onUpdateCalendarEvent');		
        $GLOBALS['app']->Shouter->NewShouter('Calendar', 'onAddCalendarRecurringEvent');   		
        $GLOBALS['app']->Shouter->NewShouter('Calendar', 'onDeleteCalendarRecurringEvent');		
        $GLOBALS['app']->Shouter->NewShouter('Calendar', 'onUpdateCalendarRecurringEvent');		

        $GLOBALS['app']->loadClass('Listener', 'Jaws_EventListener');
        $GLOBALS['app']->Listener->NewListener('Calendar', 'onDeleteUser', 'RemoveUserCalendars');
        $GLOBALS['app']->Listener->NewListener('Calendar', 'onDeleteCalendarEvent', 'RemoveEventComments');
        $GLOBALS['app']->Listener->NewListener('Calendar', 'onUpdateUser', 'UpdateUserCalendars');
		$GLOBALS['app']->Listener->NewListener('Calendar', 'onAfterEnablingGadget', 'InsertDefaultChecksums');

		//Create Jaws_User groups
        require_once JAWS_PATH . 'include/Jaws/User.php';
        $userModel = new Jaws_User;
        $userModel->addGroup('calendar_owners', false); //Don't check if it returns true or false
        //$userModel->addGroup('calendar_users', false); //Don't check if it returns true or false
        $group = $userModel->GetGroupInfoByName('calendar_owners');
		if (isset($group['id']) && !empty($group['id'])) {
			$GLOBALS['app']->ACL->NewKey('/ACL/groups/'.$group['id'].'/gadgets/Calendar/OwnEvent', 'true');
			$GLOBALS['app']->ACL->NewKey('/ACL/groups/'.$group['id'].'/gadgets/Calendar/OwnPublicEvent', 'true');
			$GLOBALS['app']->ACL->NewKey('/ACL/groups/'.$group['id'].'/gadgets/Calendar/OwnCategory', 'true');
        }
		
        return true;
    }

    /**
     * Uninstall the gadget
     *
     * @access  public
     * @param   string   $gadget  Gadget name (should be the same as $this->_Name, the model name)
     * @return  boolean Success/Failure (JawsError)
     */
    function UninstallGadget()
    {
        $tables = array('calendar',
                        'calendar_std',
						'calendarparent');
        foreach ($tables as $table) {
            $result = $GLOBALS['db']->dropTable($table);
            if (Jaws_Error::IsError($result)) {
                $gName  = _t('CALENDAR_NAME');
                $errMsg = _t('GLOBAL_ERROR_GADGET_NOT_UNINSTALLED', $gName);
                $GLOBALS['app']->Session->PushLastResponse($errMsg, RESPONSE_ERROR);
                return new Jaws_Error($errMsg, $gName);
            }
        }
		
        // Events
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
        $GLOBALS['app']->Shouter->DeleteShouter('Calendar', 'onAddCalendar');   				// trigger an action when we add a parent
        $GLOBALS['app']->Shouter->DeleteShouter('Calendar', 'onDeleteCalendar');				// trigger an action when we delete a parent
        $GLOBALS['app']->Shouter->DeleteShouter('Calendar', 'onUpdateCalendar');				// and when we update a parent..
        $GLOBALS['app']->Shouter->DeleteShouter('Calendar', 'onAddCalendarEvent');   		
        $GLOBALS['app']->Shouter->DeleteShouter('Calendar', 'onDeleteCalendarEvent');		
        $GLOBALS['app']->Shouter->DeleteShouter('Calendar', 'onUpdateCalendarEvent');		
        $GLOBALS['app']->Shouter->DeleteShouter('Calendar', 'onAddCalendarRecurringEvent');   		
        $GLOBALS['app']->Shouter->DeleteShouter('Calendar', 'onDeleteCalendarRecurringEvent');		
        $GLOBALS['app']->Shouter->DeleteShouter('Calendar', 'onUpdateCalendarRecurringEvent');		
        
		$GLOBALS['app']->loadClass('Listener', 'Jaws_EventListener');
        $GLOBALS['app']->Listener->DeleteListener('Calendar', 'RemoveUserCalendars');
        $GLOBALS['app']->Listener->DeleteListener('Calendar', 'UpdateUserCalendars');
        $GLOBALS['app']->Listener->DeleteListener('Calendar', 'RemoveEventComments');
		$GLOBALS['app']->Listener->DeleteListener('Calendar', 'InsertDefaultChecksums');

		//Delete Jaws_User groups
        require_once JAWS_PATH . 'include/Jaws/User.php';
        $userModel = new Jaws_User;
        $group = $userModel->GetGroupInfoByName('calendar_owners');
		if (isset($group['id']) && !empty($group['id'])) {
			$userModel->DeleteGroup($group['id']);
			$GLOBALS['app']->ACL->DeleteKey('/ACL/groups/'.$group['id'].'/gadgets/Calendar/OwnEvent');
			$GLOBALS['app']->ACL->DeleteKey('/ACL/groups/'.$group['id'].'/gadgets/Calendar/OwnCategory');
			$GLOBALS['app']->ACL->DeleteKey('/ACL/groups/'.$group['id'].'/gadgets/Calendar/OwnPublicEvent');
		}
        /*
		$group = $userModel->GetGroupInfoByName('calendar_users');
		if (isset($group['id']) && !empty($group['id'])) {
			$userModel->DeleteGroup($group['id']);
		}
		*/
		return true;
	}

    /**
     * Update the gadget
     *
     * @access  public
     * @param   string  $old Current version (in registry)
     * @param   string  $new     New version (in the $gadgetInfo file)
     * @return  boolean  Success/Failure (JawsError)
     */
    function UpdateGadget($old, $new)
    {
        if (version_compare($old, '0.1.1', '<')) {			
			$result = $this->installSchema('0.1.1.xml', '', '0.1.0.xml');
			if (Jaws_Error::IsError($result)) {
				return $result;
			}
		}
        if (version_compare($old, '0.1.2', '<')) {			
			$result = $this->installSchema('schema.xml', '', '0.1.1.xml');
			if (Jaws_Error::IsError($result)) {
				return $result;
			}
		}
		
		$GLOBALS['app']->loadClass('Listener', 'Jaws_EventListener');
		$GLOBALS['app']->Listener->NewListener('Calendar', 'onDeleteCalendarEvent', 'RemoveEventComments');
        
		$currentClean = str_replace(array('.', ' '), '', $old);
        $newClean     = str_replace(array('.', ' '), '', $new);

        $funcName   = 'upgradeFrom' . $currentClean;
        $scriptFile = JAWS_PATH . 'gadgets/' . $this->_Name . '/upgradeScripts/' . $funcName . '.php';
        if (file_exists($scriptFile)) {
            require_once $scriptFile;
            //Ok.. append the funcName at the start
            $funcName = $this->_Name . '_' . $funcName;
            if (function_exists($funcName)) {
                $res = $funcName();
                return $res;
            }
        }
        return true;
    }
	
    /**
     * Adds a new calendar
     *
     * @access  public
     * @return  boolean Returns true if calendar was sucessfully added, false if not
     */
    function AddCalendar($calendarparentsort_order = 1, $calendarparentCategory_Name = '', $calendarparentImage = '', 
	$calendarparentDescription = '', $calendarparentActive = 'Y', $calendarparentOwnerID = null, $calendarparentFeatured = 'N', 
	$calendarparentType = 'E', $calendarparentPropID = null, $calendarparentchecksum = '')
    {
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$calendarparentOwnerID = (!is_null($calendarparentOwnerID)) ? (int)$calendarparentOwnerID : 0;
		if (!empty($calendarparentImage)) {
			$calendarparentImage = $this->cleanImagePath($calendarparentImage);
			if (
				$calendarparentOwnerID > 0 && 
				(substr(strtolower(trim($calendarparentImage)), 0, 4) == 'http' || 
				substr(strtolower(trim($calendarparentImage)), 0, 2) == '//' || 
				substr(strtolower(trim($calendarparentImage)), 0, 2) == '\\\\')
			) {
				$calendarparentImage = '';
			}
		}
		$calendarparentPropID = (!is_null($calendarparentPropID)) ? (int)$calendarparentPropID : 0;
		$params             					= array();
        $params['calendarparentsort_order']		= $calendarparentsort_order;
        $params['calendarparentCategory_Name']	= $calendarparentCategory_Name;
        $params['calendarparentImage']         	= $calendarparentImage;
        $params['calendarparentDescription']	= $calendarparentDescription;
        $params['calendarparentActive']			= $calendarparentActive;
        $params['calendarparentOwnerID']		= $calendarparentOwnerID;
        $params['calendarparentFeatured']		= $calendarparentFeatured;
        $params['calendarparentType']			= $calendarparentType;
        $params['calendarparentPropID']			= $calendarparentPropID;
        $params['calendarparentchecksum']		= $calendarparentchecksum;
		$params['now']							= $GLOBALS['db']->Date();

        $sql = '
            INSERT INTO [[calendarparent]]
                ([calendarparentsort_order], [calendarparentcategory_name], [calendarparentimage], 
				[calendarparentdescription], [calendarparentactive], [calendarparentownerid], [calendarparentcreated], 
				[calendarparentupdated], [calendarparentfeatured], [calendarparenttype], [calendarparentpropid], [calendarparentchecksum])
            VALUES
                ({calendarparentsort_order}, {calendarparentCategory_Name}, {calendarparentImage}, 
				{calendarparentDescription}, {calendarparentActive}, {calendarparentOwnerID}, {now}, 
				{now}, {calendarparentFeatured}, {calendarparentType}, {calendarparentPropID}, {calendarparentchecksum})';

        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('CALENDAR_CATEGORY_NOT_ADDED'), RESPONSE_ERROR);
			return new Jaws_Error(_t('CALENDAR_CATEGORY_NOT_ADDED'), _t('CALENDAR_NAME'));
			//return false;
        }

        // Fetch the id that was just created
        $newid = $GLOBALS['db']->lastInsertID('calendarparent', 'calendarparentid');
	
		if (empty($calendarparentchecksum)) {
			// Update checksum
			$config_key = $GLOBALS['app']->Registry->Get('/config/key');		
			$params               	= array();
			$params['id'] 			= $newid;
			$params['checksum'] 	= $newid.':'.$config_key;
			
			$sql = '
				UPDATE [[calendarparent]] SET
					[calendarparentchecksum] = {checksum}
				WHERE [calendarparentid] = {id}';

			$result = $GLOBALS['db']->query($sql, $params);
			if (Jaws_Error::IsError($result)) {
				$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
				return false;
			}
		}
		
		// Let everyone know it has been added
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onAddCalendar', $newid);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		
		$GLOBALS['app']->Session->PushLastResponse(_t('CALENDAR_CATEGORY_ADDED'), RESPONSE_NOTICE);
		return $newid;
	}

	/**
     * Updates a calendar
     *
     * @param $calendarID integer id of the calendar to be updated
     * @return boolean true if all is ok, false if error
     */
    function UpdateCalendar($calendarparentID, $calendarparentsort_order = 1, $calendarparentCategory_Name = '', $calendarparentImage = '', 
		$calendarparentDescription = '', $calendarparentActive = 'Y', $calendarparentFeatured = 'N',  
		$calendarparentType = 'E', $calendarparentPropID = null)
    {
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$model = $GLOBALS['app']->LoadGadget('Calendar', 'Model');
        $page = $model->GetCalendar($calendarparentID);
        if (Jaws_Error::isError($page)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('CALENDAR_ERROR_CATEGORY_NOT_FOUND'), RESPONSE_ERROR);
            return $page;
        }
		if (!empty($calendarparentImage)) {
			$calendarparentImage = $this->cleanImagePath($calendarparentImage);
			if (
				$page['calendarparentownerid'] > 0 && 
				(substr(strtolower(trim($calendarparentImage)), 0, 4) == 'http' || 
				substr(strtolower(trim($calendarparentImage)), 0, 2) == '//' || 
				substr(strtolower(trim($calendarparentImage)), 0, 2) == '\\\\')
			) {
				$calendarparentImage = '';
			}
		}
 		$calendarparentPropID = (!is_null($calendarparentPropID)) ? (int)$calendarparentPropID : 0;
        $params               					= array();
        $params['now'] 							= $GLOBALS['db']->Date();
        $params['calendarparentID']         	= (int)$calendarparentID;
        $params['calendarparentsort_order']     = $calendarparentsort_order;
        $params['calendarparentCategory_Name']  = $calendarparentCategory_Name;
        $params['calendarparentImage']         	= $calendarparentImage;
        $params['calendarparentDescription']    = $calendarparentDescription;
        $params['calendarparentActive']         = $calendarparentActive;
        $params['calendarparentFeatured']       = $calendarparentFeatured;
        $params['calendarparentType']         	= $calendarparentType;
        $params['calendarparentPropID']         = $calendarparentPropID;

        $sql = '
            UPDATE [[calendarparent]] SET
                [calendarparentupdated] = {now},
                [calendarparentsort_order] = {calendarparentsort_order},
                [calendarparentcategory_name] = {calendarparentCategory_Name},
                [calendarparentimage] = {calendarparentImage},
                [calendarparentdescription] = {calendarparentDescription},
                [calendarparentactive] = {calendarparentActive},
                [calendarparentfeatured] = {calendarparentFeatured},
				[calendarparenttype] = {calendarparentType},
				[calendarparentpropid] = {calendarparentPropID}
            WHERE [calendarparentid] = {calendarparentID}';
        
		$result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('CALENDAR_CATEGORY_NOT_UPDATED'), RESPONSE_ERROR);
			return new Jaws_Error(_t('CALENDAR_CATEGORY_NOT_UPDATED'), _t('CALENDAR_NAME'));
			//return false;
        }
		
		// Let everyone know
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onUpdateCalendar', $calendarparentID);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		
		$GLOBALS['app']->Session->PushLastResponse(_t('CALENDAR_CATEGORY_UPDATED'), RESPONSE_NOTICE);
        return true;
    }

	/**
     * Delete a calendar
     *
     * @access  public
     * @return  boolean True if query was successful and Jaws_Error on error
     */
    function DeleteCalendar($cid)
    {
		$model = $GLOBALS['app']->LoadGadget('Calendar', 'Model');
		$calendarParent = $model->GetCalendar($cid);
		if (Jaws_Error::IsError($calendarParent)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
			return new Jaws_Error(_t('GLOBAL_ERROR_QUERY_FAILED'), _t('CALENDAR_NAME'));
			//return false;
		}

		if(!isset($calendarParent['calendarparentid'])) {
			$GLOBALS['app']->Session->PushLastResponse(_t('CALENDAR_CATEGORY_NOT_FOUND'), RESPONSE_ERROR);
			return new Jaws_Error(_t('CALENDAR_CATEGORY_NOT_FOUND'), _t('CALENDAR_NAME'));
			//return false;
		} else {
			// Let everyone know
			$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
			$res = $GLOBALS['app']->Shouter->Shout('onDeleteCalendar', $cid);
			if (Jaws_Error::IsError($res) || !$res) {
				return $res;
			}
			
			$eids = $model->GetAllEventsOfCalendar($cid);
			if (Jaws_Error::IsError($eids)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
				return new Jaws_Error(_t('GLOBAL_ERROR_QUERY_FAILED'), _t('CALENDAR_NAME'));
				//return false;
			}

			foreach ($eids as $eid) {
				$rids = $model->GetAllRecurringEventsOfEventEntry($eid['id']);
				if (Jaws_Error::IsError($rids)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
					return new Jaws_Error(_t('GLOBAL_ERROR_QUERY_FAILED'), _t('CALENDAR_NAME'));
					//return false;
				}

				foreach ($rids as $rid) {
					if (!$this->DeleteRecurringEvent($rid['id'])) {
						$GLOBALS['app']->Session->PushLastResponse(_t('CALENDAR_EVENT_CANT_DELETE'), RESPONSE_ERROR);
						return new Jaws_Error(_t('CALENDAR_EVENT_CANT_DELETE'), _t('CALENDAR_NAME'));
						//return false;
					}
				}
				if (!$this->DeleteEvent($eid['id'])) {
					$GLOBALS['app']->Session->PushLastResponse(_t('CALENDAR_EVENT_CANT_DELETE'), RESPONSE_ERROR);
					return new Jaws_Error(_t('CALENDAR_EVENT_CANT_DELETE'), _t('CALENDAR_NAME'));
					//return false;
				}
			}
		
			$sql = 'DELETE FROM [[calendarparent]] WHERE [calendarparentid] = {cid}';
			$res = $GLOBALS['db']->query($sql, array('cid' => $cid));
			if (Jaws_Error::IsError($res)) {
				//$GLOBALS['app']->Session->PushLastResponse(_t('CALENDAR_CATEGORY_CANT_DELETE'), RESPONSE_ERROR);
				$GLOBALS['app']->Session->PushLastResponse($res->GetMessage(), RESPONSE_ERROR);
				//return new Jaws_Error(_t('CALENDAR_CATEGORY_CANT_DELETE'), _t('CALENDAR_NAME'));
				return new Jaws_Error($res->GetMessage(), _t('CALENDAR_NAME'));
				//return false;
			}
		}
				
		$GLOBALS['app']->Session->PushLastResponse(_t('CALENDAR_CATEGORY_DELETED'), RESPONSE_NOTICE);
        return true;
    }

    /**
     * Adds a new calendar event
     *
     * @access  public
     * @return  boolean Returns true if calendar event was sucessfully added, false if not
     */
	function AddEvent(
		$event = '', $startdate = null, $enddate = null, $sm_description = '', $description = '', 
		$host = '', $iTime = '', $endTime = '', $image = '', $alink = '', $alinkTitle = '', 
		$alinkType = '', $isRecurring = 'N', $active = 'Y', $OwnerID = 0, $LinkID = null, 
		$checksum = '', $max_occupancy = 0, $occupants = ''
	){
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        if (is_null($startdate) || is_null($enddate) || is_null($LinkID)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('CALENDAR_EVENT_NOT_ADDED').': NULL FIELDS', RESPONSE_ERROR);
			return new Jaws_Error(_t('CALENDAR_EVENT_NOT_ADDED'), _t('CALENDAR_NAME'));
			//return false;
        }
		$OwnerID = (!is_null($OwnerID)) ? (int)$OwnerID : 0;
		if (!empty($image)) {
			$image = $this->cleanImagePath($image);
			if (
				$OwnerID > 0 && 
				(substr(strtolower(trim($image)), 0, 4) == 'http' || 
				substr(strtolower(trim($image)), 0, 2) == '//' || 
				substr(strtolower(trim($image)), 0, 2) == '\\\\')
			) {
				$image = '';
			}
		}
        $params             		= array();
        $params['Event']         	= $event;
        $params['startdate'] 		= $GLOBALS['db']->Date($GLOBALS['app']->UserTime2UTC($startdate));
        $params['enddate'] 			= $GLOBALS['db']->Date($GLOBALS['app']->UserTime2UTC($enddate));
        $params['sm_description']	= $sm_description;
        $params['description']		= $description;
        $params['image']			= $image;
        $params['Host']				= $host;
        $params['iTime']			= (!empty($iTime) ? $GLOBALS['app']->UserTime2UTC($iTime, "g:i A") : '');
        $params['endTime']			= (!empty($endTime) ? $GLOBALS['app']->UserTime2UTC($endTime, "g:i A") : '');
        $params['alink']			= $alink;
        $params['alinkTitle']		= $alinkTitle;
        $params['alinkType']		= $alinkType;
        $params['isRecurring']		= $isRecurring;
        $params['Active']			= $active;
		$params['OwnerID'] 			= (int)$OwnerID;
        $params['LinkID']         	= (int)$LinkID;
        $params['checksum']         = $checksum;
		$params['max_occupancy']    = (int)preg_replace("[^0-9]", '', $max_occupancy);
		$params['occupants']    	= $occupants;
		$params['now']      		= $GLOBALS['db']->Date();

		/*
		foreach ($params as $pkey => $pval) {
			echo $pkey . ':::::' . $pval;
		}
		exit;
		*/
        
		$sql = '
            INSERT INTO [[calendar]]
                ([event], [startdate], [enddate], [sm_description], [description], [image], 
				[host], [itime], [endtime], [alink], [alinktitle], [alinktype], [isrecurring], 
				[active], [ownerid], [linkid], [created], [updated], [checksum], [max_occupancy], [occupants])
            VALUES
                ({Event}, {startdate}, {enddate}, {sm_description}, {description}, {image}, 
				{Host}, {iTime}, {endTime}, {alink}, {alinkTitle}, {alinkType}, {isRecurring}, 
				{Active}, {OwnerID}, {LinkID}, {now}, {now}, {checksum}, {max_occupancy}, {occupants})';

        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('CALENDAR_EVENT_NOT_ADDED'), RESPONSE_ERROR);
			return new Jaws_Error(_t('CALENDAR_EVENT_NOT_ADDED'), _t('CALENDAR_NAME'));
			//return false;
        }

        // Fetch the id that was just created
        $newid = $GLOBALS['db']->lastInsertID('calendar', 'id');
	
		if (empty($checksum)) {
			// Update checksum
			$config_key = $GLOBALS['app']->Registry->Get('/config/key');		
			$params               	= array();
			$params['id'] 			= $newid;
			$params['checksum'] 	= $newid.':'.$config_key;
			
			$sql = '
				UPDATE [[calendar]] SET
					[checksum] = {checksum}
				WHERE [id] = {id}';

			$result = $GLOBALS['db']->query($sql, $params);
			if (Jaws_Error::IsError($result)) {
				$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
				return false;
			}
		}
		
		// Let everyone know
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onAddCalendarEvent', $newid);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}

		$GLOBALS['app']->Session->PushLastResponse(_t('CALENDAR_EVENT_ADDED'), RESPONSE_NOTICE);
		return $newid;
	}

    /**
     * Updates a calendar event
     *
     * @param $id integer id of the event to be updated
     * @return boolean true if all is ok, false if error
     */
    function UpdateEvent(
		$id, $event = '', $startdate, $enddate, $sm_description = '', $description = '', 
		$host = '', $iTime = '', $endTime = '', $image = '', $alink = '', $alinkTitle = '', $alinkType = '', 
		$isRecurring = 'N', $active = 'Y', $max_occupancy = null, $occupants = null
	) {
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$model = $GLOBALS['app']->LoadGadget('Calendar', 'Model');
        $page = $model->GetEvent($id);
        if (Jaws_Error::isError($page)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('CALENDAR_ERROR_EVENT_NOT_FOUND'), RESPONSE_ERROR);
            return $page;
        }
        if (is_null($id) || is_null($startdate) || is_null($enddate)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('CALENDAR_EVENT_NOT_UPDATED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('CALENDAR_EVENT_NOT_UPDATED'), _t('CALENDAR_NAME'));
			//return false;
        }
		if (!empty($image)) {
			$image = $this->cleanImagePath($image);
			if (
				$page['ownerid'] > 0 && 
				(substr(strtolower(trim($image)), 0, 4) == 'http' || 
				substr(strtolower(trim($image)), 0, 2) == '//' || 
				substr(strtolower(trim($image)), 0, 2) == '\\\\')
			) {
				$image = '';
			}
		}
        $params             		= array();
        $params['id']         		= (int)$id;
        $params['Event']         	= $event;
        $params['startdate'] 		= $GLOBALS['db']->Date($GLOBALS['app']->UserTime2UTC($startdate));
        $params['enddate'] 			= $GLOBALS['db']->Date($GLOBALS['app']->UserTime2UTC($enddate));
        $params['sm_description']	= $sm_description;
        $params['description']		= $description;
        $params['image']			= $image;
        $params['Host']				= $host;
        $params['iTime']			= (!empty($iTime) ? $GLOBALS['app']->UserTime2UTC($iTime, "g:i A") : '');
        $params['endTime']			= (!empty($endTime) ? $GLOBALS['app']->UserTime2UTC($endTime, "g:i A") : '');
        $params['alink']			= $alink;
        $params['alinkTitle']		= $alinkTitle;
        $params['alinkType']		= $alinkType;
        $params['isRecurring']		= $isRecurring;
        $params['Active']			= $active;
		$params['now']      		= $GLOBALS['db']->Date();
				
        $sql = '
            UPDATE [[calendar]] SET
                [updated]       = {now},
                [event]       = {Event},
                [startdate]       = {startdate},
                [enddate]       = {enddate},
                [sm_description]       = {sm_description},
                [description]       = {description},
                [image]       = {image},
                [host]       = {Host},
                [itime]       = {iTime},
                [endtime]       = {endTime},
                [alink]       = {alink},
                [alinktitle]       = {alinkTitle},
                [alinktype]       = {alinkType},
                [isrecurring]       = {isRecurring}, 
                [active]       = {Active}';
        if (!is_null($max_occupancy)) {
			$params['max_occupancy']    = (int)preg_replace("[^0-9]", '', $max_occupancy);
			$sql .= ', 
					[max_occupancy]       = {max_occupancy}';
		}
        if (!is_null($occupants)) {
			$params['occupants']    	= $occupants;
			$sql .= ', 
					[occupants]       = {occupants}';
        }
		$sql .= '
            WHERE [id] = {id}';

		$result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('CALENDAR_EVENT_NOT_UPDATED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('CALENDAR_EVENT_NOT_UPDATED'), _t('CALENDAR_NAME'));
			//return false;
        }
		
		// Let everyone know
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onUpdateCalendarEvent', $id);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}

		$GLOBALS['app']->Session->PushLastResponse(_t('CALENDAR_EVENT_UPDATED'), RESPONSE_NOTICE);
		return true;
    }

	/**
     * Delete an event
     *
     * @access  public
     * @return  boolean True if query was successful and Jaws_Error on error
     */
    function DeleteEvent($eid)
    {
		$model = $GLOBALS['app']->LoadGadget('Calendar', 'Model');
		$event = $model->GetEvent($eid);
		if (Jaws_Error::IsError($event)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
			return new Jaws_Error(_t('GLOBAL_ERROR_QUERY_FAILED'), _t('CALENDAR_NAME'));
			//return false;
		}

		if(!isset($event['id'])) {
			$GLOBALS['app']->Session->PushLastResponse(_t('CALENDAR_EVENT_NOT_FOUND'), RESPONSE_ERROR);
			return new Jaws_Error(_t('CALENDAR_EVENT_NOT_FOUND'), _t('CALENDAR_NAME'));
			//return false;
		} else {
			// Let everyone know
			$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
			$res = $GLOBALS['app']->Shouter->Shout('onDeleteCalendarEvent', $eid);
			if (Jaws_Error::IsError($res) || !$res) {
				return $res;
			}

			$rids = $model->GetAllRecurringEventsOfEventEntry($eid);
			if (Jaws_Error::IsError($rids)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
				return new Jaws_Error(_t('GLOBAL_ERROR_QUERY_FAILED'), _t('CALENDAR_NAME'));
				//return false;
			}
			foreach ($rids as $rid) {
				if (!$this->DeleteRecurringEvent($rid['id'])) {
					$GLOBALS['app']->Session->PushLastResponse(_t('CALENDAR_EVENT_CANT_DELETE'), RESPONSE_ERROR);
					return new Jaws_Error(_t('CALENDAR_EVENT_CANT_DELETE'), _t('CALENDAR_NAME'));
					//return false;
				}
			}
			$sql = 'DELETE FROM [[calendar]] WHERE [id] = {eid}';
			$res = $GLOBALS['db']->query($sql, array('eid' => $eid));
			if (Jaws_Error::IsError($res)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('CALENDAR_EVENT_CANT_DELETE'), RESPONSE_ERROR);
				return new Jaws_Error(_t('CALENDAR_EVENT_CANT_DELETE'), _t('CALENDAR_NAME'));
				//return false;
			}
		}
		
		$GLOBALS['app']->Session->PushLastResponse(_t('CALENDAR_EVENT_DELETED'), RESPONSE_NOTICE);
        return true;
    }

    /**
     * Adds a recurring calendar event
     *
     * @access  public
     * @return  boolean Returns true if calendar event was sucessfully added, false if not
     */
	function AddRecurringEvent($calendarID, $dayname, $dates, $LinkID, $checksum = '')
    {
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        if (is_null($calendarID) || (is_null($dayname) && is_null($dates)) || is_null($LinkID)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('CALENDAR_RECURRINGEVENT_NOT_ADDED'), RESPONSE_ERROR);
			return new Jaws_Error(_t('CALENDAR_RECURRINGEVENT_NOT_ADDED'), _t('CALENDAR_NAME'));
			//return false;
        }
        $params             	= array();
        $params['calendarID']	= $calendarID;
        $params['dayname']		= $dayname;
        $params['dates']		= $dates;
        $params['LinkID']		= (int)$LinkID;
        $params['checksum']		= $checksum;
		$params['now']			= $GLOBALS['db']->Date();

        $sql = '
            INSERT INTO [[calendar_std]]
                ([calendarid], [dayname], [dates], [linkid], [created], [updated], [checksum])
            VALUES
                ({calendarID}, {dayname}, {dates}, {LinkID}, {now}, {now}, {checksum})';
        
		$result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('CALENDAR_RECURRINGEVENT_NOT_ADDED'), RESPONSE_ERROR);
			return new Jaws_Error(_t('CALENDAR_RECURRINGEVENT_NOT_ADDED'), _t('CALENDAR_NAME'));
			//return false;
        }

        // Fetch the id that was just created
        $newid = $GLOBALS['db']->lastInsertID('calendar_std', 'id');
	
		if (empty($checksum)) {
			// Update checksum
			$config_key = $GLOBALS['app']->Registry->Get('/config/key');		
			$params               	= array();
			$params['id'] 			= $newid;
			$params['checksum'] 	= $newid.':'.$config_key;
			
			$sql = '
				UPDATE [[calendar_std]] SET
					[checksum] = {checksum}
				WHERE [id] = {id}';

			$result = $GLOBALS['db']->query($sql, $params);
			if (Jaws_Error::IsError($result)) {
				$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
				return false;
			}
		}
		
		// Let everyone know
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onAddCalendarRecurringEvent', $newid);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}

		$GLOBALS['app']->Session->PushLastResponse(_t('CALENDAR_RECURRINGEVENT_ADDED'), RESPONSE_NOTICE);
		return $newid;
	}
		
    /**
     * Updates a recurring calendar event
     *
     * @param $id integer id of the event to be updated
     * @return boolean true if all is ok, false if error
     */
    function UpdateRecurringEvent($id, $dayname, $dates)
    {
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        if (is_null($id) || (is_null($dayname) && is_null($dates))) {
			$GLOBALS['app']->Session->PushLastResponse(_t('CALENDAR_RECURRINGEVENT_NOT_UPDATED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('CALENDAR_RECURRINGEVENT_NOT_UPDATED'), _t('CALENDAR_NAME'));
			//return false;
        }
        $params             = array();
        $params['id']       = (int)$id;
		$params['now']      = $GLOBALS['db']->Date();
        $params['dayname']  = $dayname;
        $params['dates']   	= $dates;
				
        $sql = '
            UPDATE [[calendar_std]] SET
                [updated]       = {now},
                [dayname]       = {dayname},
                [dates]       = {dates}
            WHERE [id] = {id}';
        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('CALENDAR_RECURRINGEVENT_NOT_UPDATED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('CALENDAR_RECURRINGEVENT_NOT_UPDATED'), _t('CALENDAR_NAME'));
			//return false;
        }

		// Let everyone know
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onUpdateCalendarRecurringEvent', $id);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}

		$GLOBALS['app']->Session->PushLastResponse(_t('CALENDAR_RECURRINGEVENT_UPDATED'), RESPONSE_NOTICE);
        return true;
    }

	    /**
     * Delete a recurring event
     *
     * @access  public
     * @return  boolean True if query was successful and Jaws_Error on error
     */
    function DeleteRecurringEvent($rid)
    {
		$model = $GLOBALS['app']->LoadGadget('Calendar', 'Model');
		$recurringEvent = $model->GetRecurringEvent($rid);
		if (Jaws_Error::IsError($recurringEvent)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_QUERY_FAILED'), RESPONSE_ERROR);
			return new Jaws_Error(_t('GLOBAL_ERROR_QUERY_FAILED'), _t('CALENDAR_NAME'));
			//return false;
		}

		if(!isset($recurringEvent['id'])) {
			$GLOBALS['app']->Session->PushLastResponse(_t('CALENDAR_EVENT_NOT_FOUND'), RESPONSE_ERROR);
			return new Jaws_Error(_t('CALENDAR_EVENT_NOT_FOUND'), _t('CALENDAR_NAME'));
			//return false;
		} else {
			// Let everyone know
			$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
			$res = $GLOBALS['app']->Shouter->Shout('onDeleteCalendarRecurringEvent', $rid);
			if (Jaws_Error::IsError($res) || !$res) {
				return $res;
			}

			$sql = 'DELETE FROM [[calendar_std]] WHERE [id] = {rid}';
			$res = $GLOBALS['db']->query($sql, array('rid' => $rid));
			if (Jaws_Error::IsError($res)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('CALENDAR_EVENT_CANT_DELETE'), RESPONSE_ERROR);
				return new Jaws_Error(_t('CALENDAR_EVENT_CANT_DELETE'), _t('CALENDAR_NAME'));
				//return false;
			}
		}
		
		//$GLOBALS['app']->Session->PushLastResponse(_t('CALENDAR_EVENT_DELETED'), RESPONSE_NOTICE);       
		return true;
    }

    /**
     * Deletes a group of pages
     *
     * @access  public
     * @param   array   $pages  Array with the ids of pages
     * @return  bool    Success/failure
     */
    function MassiveDelete($pages)
    {
        if (!is_array($pages)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('CALENDAR_ERROR_CATEGORY_NOT_MASSIVE_DELETED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('CALENDAR_ERROR_CATEGORY_NOT_MASSIVE_DELETED'), _t('CALENDAR_NAME'));
        }

        foreach ($pages as $page) {
            $res = $this->DeleteCalendar($page);
            if (Jaws_Error::IsError($res)) {
                $GLOBALS['app']->Session->PushLastResponse(_t('CALENDAR_ERROR_CATEGORY_NOT_MASSIVE_DELETED'), RESPONSE_ERROR);
                return new Jaws_Error(_t('CALENDAR_ERROR_CATEGORY_NOT_MASSIVE_DELETED'), _t('CALENDAR_NAME'));
            }
        }
        $GLOBALS['app']->Session->PushLastResponse(_t('CALENDAR_CATEGORY_MASSIVE_DELETED'), RESPONSE_NOTICE);
        return true;
    }

    /**
     * Search for Calendars that matches a status and/or a keyword
     * in the title or content
     *
     * @access  public
     * @access  public
     * @param   string  $status  Status of page(s) we want to display
     * @param   string  $search  Keyword (title/description) of pages we want to look for
     * @param   int     $offSet  Data limit
     * @return  array   Array of matches
     */
    function SearchCalendars($status, $search, $offSet = null, $OwnerID = null)
    {
        $params = array();
        $sql = '
            SELECT [calendarparentid], [calendarparentsort_order], [calendarparentcategory_name], 
				[calendarparentimage], [calendarparentdescription], [calendarparentactive], 
				[calendarparentownerid], [calendarparentcreated], [calendarparentupdated], 
				[calendarparentfeatured], [calendarparenttype], [calendarparentpropid], [calendarparentchecksum]
            FROM [[calendarparent]]
			WHERE ([calendarparentcategory_name] <> ""';

        if (trim($status) != '') {
            $sql .= ' AND [calendarparentactive] = {status}';
			$params['status'] = $status;
        }
        
		if (!is_null($OwnerID)) {
			$sql .= ' AND [calendarparentownerid] = {OwnerID}';
			$params['OwnerID'] = $OwnerID;
		}
        $sql .= ')';
		
		if (trim($search) != '') {
            $searchdata = explode(' ', $search);
            /**
             * This query needs more work, not use $v straight, should be
             * like rest of the param stuff.
             */
            $i = 0;
            foreach ($searchdata as $v) {
                $v = trim($v);
                $sql .= " AND ([calendarparentcategory_name] LIKE {textLike_".$i."} OR [calendarparentdescription] LIKE {textLike_".$i."})";
                $params['textLike_'.$i] = '%'.$v.'%';
                $i++;
            }
        }

        if (is_numeric($offSet)) {
            $limit = 10;
            $result = $GLOBALS['db']->setLimit(10, $offSet);
            if (Jaws_Error::IsError($result)) {
                return new Jaws_Error(_t('CALENDAR_ERROR_CALENDARS_NOT_RETRIEVED'), _t('CALENDAR_NAME'));
            }
        }

        $sql.= ' ORDER BY [calendarparentid] ASC';

        $types = array(
			'integer', 'integer', 'text', 'text', 'text', 'text', 
			'integer', 'timestamp', 'timestamp', 'text', 'text', 'integer', 'text'
		);
	    
        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('CALENDAR_ERROR_CALENDARS_NOT_RETRIEVED'), _t('CALENDAR_NAME'));
        }
        //limit, sort, sortDirection, offset..
        return $result;
    }
		
    /**
     * Updates a User's Calendars
     *
     * @access  public
     * @param   int  $uid  User ID
     * @return  array   Response
     */
    function UpdateUserCalendars($uid) 
    {
		require_once JAWS_PATH . 'include/Jaws/User.php';
		$jUser = new Jaws_User;
		$info = $jUser->GetUserInfoById((int)$uid, true);
		if (!Jaws_Error::IsError($info)) {
			$params           	= array();
			$params['id']     	= $info['id'];
			if (!$info['enabled']) {
				$params['Active'] = 'N';
				$params['was'] = 'Y';
			} else {
				$params['Active'] = 'Y';
				$params['was'] = 'N';
			}
			$sql = '
				UPDATE [[calendar]] SET
					[active] = {Active}
				WHERE ([ownerid] = {id}) AND ([active] = {was})';

			$result = $GLOBALS['db']->query($sql, $params);
			if (Jaws_Error::IsError($result)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('CALENDAR_ERROR_USER_EVENTS_NOT_UPDATED'), RESPONSE_ERROR);
				return false;
			}
			$sql2 = '
				UPDATE [[calendarparent]] SET
					[calendarparentactive] = {Active}
				WHERE ([calendarparentownerid] = {id}) AND ([calendarparentactive] = {was})';

			$result2 = $GLOBALS['db']->query($sql2, $params);
			if (Jaws_Error::IsError($result2)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('CALENDAR_ERROR_USER_CALENDARS_NOT_UPDATED'), RESPONSE_ERROR);
				return false;
			}
			$GLOBALS['app']->Session->PushLastResponse(_t('CALENDAR_USER_CALENDARS_UPDATED'), RESPONSE_NOTICE);
			return true;
		} else {
			$GLOBALS['app']->Session->PushLastResponse(_t('CALENDAR_ERROR_USER_CALENDARS_NOT_UPDATED'), RESPONSE_ERROR);
			return false;
		}
    }	
		
    /**
     * Deletes a User's Calendars
     *
     * @access  public
     * @param   int  $uid  User ID
     * @return  array   Response
     */
    function RemoveUserCalendars($uid) 
    {
		$model = $GLOBALS['app']->LoadGadget('Calendar', 'Model');
		$parents = $model->GetCalendarOfUserID((int)$uid);
		if (!Jaws_Error::IsError($parents)) {
			foreach ($parents as $page) {
				$result = $this->DeleteCalendar($page['calendarparentid'], true);
				if (Jaws_Error::IsError($result)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('CALENDAR_ERROR_USER_CALENDAR_NOT_DELETED'), RESPONSE_ERROR);
					return false;
				}
			}
			$GLOBALS['app']->Session->PushLastResponse(_t('CALENDAR_USER_CALENDARS_DELETED'), RESPONSE_NOTICE);
		} else {
			$GLOBALS['app']->Session->PushLastResponse(_t('CALENDAR_ERROR_USER_CALENDARS_NOT_DELETED'), RESPONSE_NOTICE);
			return false;
		}
		$events = $model->GetEventsOfUserID((int)$uid);
		if (!Jaws_Error::IsError($events)) {
			foreach ($events as $page) {
				$result = $this->DeleteEvent($page['id'], true);
				if (Jaws_Error::IsError($result)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('CALENDAR_ERROR_USER_EVENT_NOT_DELETED'), RESPONSE_ERROR);
					return false;
				}
			}
			$GLOBALS['app']->Session->PushLastResponse(_t('CALENDAR_USER_EVENTS_DELETED'), RESPONSE_NOTICE);
		} else {
			$GLOBALS['app']->Session->PushLastResponse(_t('CALENDAR_ERROR_USER_EVENTS_NOT_DELETED'), RESPONSE_NOTICE);
			return false;
		}
		return true;		
    }	
	
    /**
     * Deletes Event Comments
     *
     * @access  public
     * @param   int  $id  ID
     * @return  array   Response
     */
    function RemoveEventComments($id) 
    {
		require_once JAWS_PATH . 'include/Jaws/Comment.php';

		// Delete standard comments
		$api = new Jaws_Comment('Calendar');
		$result = $api->DeleteCommentsByReference($id);
		if (Jaws_Error::IsError($result)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_COMMENT_ERROR_NOT_DELETED'), RESPONSE_ERROR);
			return false;
		}
				
		return true;
    }	
	
	/**
     * Inserts checksums for default (insert.xml) content
     *
     * @access  public
     * @param   string  $gadget   Get gadget name from onAfterEnablingGadget shouter call
     * @return  array   Response
     */
    function InsertDefaultChecksums($gadget)
    {
		if ($gadget == 'Calendar') {
			// Update checksum
			$config_key = $GLOBALS['app']->Registry->Get('/config/key');		
			
			$model = $GLOBALS['app']->LoadGadget('Calendar', 'Model');
			$parents = $model->GetAllCalendars();
			if (Jaws_Error::IsError($parents)) {
				return false;
			}
			foreach ($parents as $parent) {
				if (empty($parent['calendarparentchecksum']) || is_null($parent['calendarparentchecksum']) || strpos($parent['calendarparentchecksum'], ':') === false) {
					$params               	= array();
					$params['id'] 			= $parent['calendarparentid'];
					$params['checksum'] 	= $parent['calendarparentid'].':'.$config_key;
					
					$sql = '
						UPDATE [[calendarparent]] SET
							[calendarparentchecksum] = {checksum}
						WHERE [calendarparentid] = {id}';

					$result = $GLOBALS['db']->query($sql, $params);
					if (Jaws_Error::IsError($result)) {
						$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
						return false;
					}

					// Let everyone know it has been added
					$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
					$res = $GLOBALS['app']->Shouter->Shout('onAddCalendar', $parent['calendarparentid']);
					if (Jaws_Error::IsError($res) || !$res) {
						return $res;
					}
				}
				$posts = $model->GetAllEventsOfCalendar($parent['calendarparentid']);
				if (Jaws_Error::IsError($posts)) {
					return false;
				}
				foreach ($posts as $post) {
					if (empty($post['checksum']) || is_null($post['checksum']) || strpos($post['checksum'], ':') === false) {
						$params               	= array();
						$params['id'] 			= $post['id'];
						$params['checksum'] 	= $post['id'].':'.$config_key;
						
						$sql = '
							UPDATE [[calendar]] SET
								[checksum] = {checksum}
							WHERE [id] = {id}';

						$result = $GLOBALS['db']->query($sql, $params);
						if (Jaws_Error::IsError($result)) {
							$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
							return false;
						}

						// Let everyone know it has been added
						$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
						$res = $GLOBALS['app']->Shouter->Shout('onAddCalendarEvent', $post['id']);
						if (Jaws_Error::IsError($res) || !$res) {
							return $res;
						}
					}
					$posts1 = $model->GetAllRecurringEventsOfEventEntry($post['id']);
					if (Jaws_Error::IsError($posts1)) {
						return false;
					}
					foreach ($posts1 as $post1) {
						if (empty($post1['checksum']) || is_null($post1['checksum']) || strpos($post1['checksum'], ':') === false) {
							$params               	= array();
							$params['id'] 			= $post1['id'];
							$params['checksum'] 	= $post1['id'].':'.$config_key;
							
							$sql = '
								UPDATE [[calendar_std]] SET
									[checksum] = {checksum}
								WHERE [id] = {id}';

							$result = $GLOBALS['db']->query($sql, $params);
							if (Jaws_Error::IsError($result)) {
								$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
								return false;
							}

							// Let everyone know it has been added
							$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
							$res = $GLOBALS['app']->Shouter->Shout('onAddCalendarRecurringEvent', $post1['id']);
							if (Jaws_Error::IsError($res) || !$res) {
								return $res;
							}

						}
					}
				}
			}
		}
		return true;
    }
}