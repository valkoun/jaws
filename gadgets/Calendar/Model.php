<?php
/**
 * Calendar Gadget
 *
 * @category   GadgetModel
 * @package    Calendar
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */

class CalendarModel extends Jaws_Model
{
    var $_Name = 'Calendar';
	
    /**
     * Gets the all calendars
     *
     * @access  public
     * @param   bool     $public_only  Only return unowned (Public) calendars
     * @return  mixed   Returns an array with the calendars and false on error
     */
    function GetAllCalendars($public_only = true, $limit = null, $offSet = false)
    {
		$params       = array();
		$sql = "
            SELECT [calendarparentid], [calendarparentsort_order], [calendarparentcategory_name], 
				[calendarparentimage], [calendarparentdescription], [calendarparentactive],
				[calendarparentownerid], [calendarparentcreated], [calendarparentupdated], 
				[calendarparentfeatured], [calendarparenttype], [calendarparentpropid], [calendarparentchecksum]
			FROM [[calendarparent]]";
        if ($public_only == true) {
			$sql .= " WHERE ([calendarparentownerid] = 0)";
		}
		
        $types = array(
			'integer', 'integer', 'text', 'text', 'text', 'text', 
			'integer', 'timestamp', 'timestamp', 'text', 'text', 'integer', 'text'
		);
		
        if (is_numeric($offSet)) {
            $limit = is_null($limit) ? 10 : $limit;
			$result = $GLOBALS['db']->setLimit(10, $offSet);
			if (Jaws_Error::IsError($result)) {
				return new Jaws_Error(_t('CALENDAR_GET_CATEGORY'), _t('CALENDAR_NAME'));
			}
        } else if (!is_null($limit)) {
			$result = $GLOBALS['db']->setLimit($limit);
			if (Jaws_Error::IsError($result)) {
				return new Jaws_Error(_t('CALENDAR_GET_CATEGORY'), _t('CALENDAR_NAME'));
			}
        }
		
        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('CALENDAR_GET_CATEGORY'), _t('CALENDAR_NAME'));
        }
		return $result;
    }	
	    
    /**
     * Gets the users calendars by ID
     *
     * @access  public
     * @param   int     $id  The user ID
     * @return  mixed   Returns an array with the calendars and false on error
     */
    function GetCategoriesOfUserID($id, $active = null, $sortColumn = 'calendarparentsort_order', $sortDir = 'ASC', $limit = null)
    {
		$fields = array(
			'calendarparentsort_order', 'calendarparentcategory_name', 'calendarparentimage', 'calendarparentactive', 
			'calendarparentfeatured', 'calendarparentownerid', 'calendarparenttype', 'calendarparentcreated', 
			'calendarparentupdated', 'calendarparentpropid'
		);
		$sortColumn = strtolower($sortColumn);
        if (!in_array($sortColumn, $fields)) {
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_WARNING, _t('CALENDAR_ERROR_UNKNOWN_COLUMN'));
            }
            $sortColumn = 'calendarparentsort_order';
        }

        $sortDir = strtoupper($sortDir);
		
		$params       = array();
        $params['id'] = (int)$id;
		
		$sql = '
			SELECT [calendarparentid], [calendarparentsort_order], [calendarparentcategory_name], 
				[calendarparentimage], [calendarparentdescription], [calendarparentactive],
				[calendarparentownerid], [calendarparentcreated], [calendarparentupdated], 
				[calendarparentfeatured], [calendarparenttype], [calendarparentpropid], [calendarparentchecksum]
			FROM [[calendarparent]]
			WHERE ([calendarparentownerid] = {id})';
		if (!is_null($active)) {
			$sql .=  " AND ([calendarparentactive] = {active})";
			$params['active'] = $active;
		}
		$sql .= " ORDER BY [$sortColumn] $sortDir".($sortColumn == 'calendarparentsort_order' ? ", [calendarparentimage] DESC" : '');
		
		$types = array(
			'integer', 'integer', 'text', 'text', 'text', 'text', 
			'integer', 'timestamp', 'timestamp', 'text', 'text', 'integer', 'text'
		);
		
        if (!is_null($limit)) {
			$res = $GLOBALS['db']->setLimit($limit);
			if (Jaws_Error::IsError($res)) {
				return new Jaws_Error(_t('CALENDAR_GET_CATEGORY'), _t('CALENDAR_NAME'));
			}
		}
		
		$result = $GLOBALS['db']->queryAll($sql, $params, $types);
		if (Jaws_Error::IsError($result)) {
			return new Jaws_Error(_t('CALENDAR_GET_CATEGORY'), _t('CALENDAR_NAME'));
		}
		
        return $result;
    }
	
    /**
     * Returns a calendar
     *
     * @access  public
     * @return  array  The given calendar ID's row values or Jaws_Error on error
     */
    function GetCalendar($cid)
    {
		$sql = '
            SELECT [calendarparentid], [calendarparentsort_order], [calendarparentcategory_name], 
				[calendarparentimage], [calendarparentdescription], [calendarparentactive],
				[calendarparentownerid], [calendarparentcreated], [calendarparentupdated], 
				[calendarparentfeatured], [calendarparenttype], [calendarparentpropid], [calendarparentchecksum]
			FROM [[calendarparent]]
            WHERE [calendarparentid] = {cid}';

        $params        = array();
        $params['cid'] = (int)$cid;
        
        $types = array(
			'integer', 'integer', 'text', 
			'text', 'text', 'text', 
			'integer', 'timestamp', 'timestamp', 
			'text', 'text', 'integer', 'text'
		);
		
        $result = $GLOBALS['db']->queryRow($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('CALENDAR_GET_CATEGORY'), _t('CALENDAR_NAME'));
        }

        return $result;
    }

    /**
     * Returns all event IDs that belong to a calendar
     *
     * @access  public
     * @return  array  Array with all the event IDs or Jaws_Error on error
     */
    function GetAllEventsOfCalendar($cid, $limit = null)
    {
	    $sql  = 'SELECT [id], [event], [startdate], [enddate], 
				[sm_description], [description], [image], [host], 
				[itime], [endtime], [alink], [alinktitle], [alinktype], 
				[isrecurring], [active], [ownerid], [linkid], [created], 
				[updated], [checksum], [max_occupancy], [occupants]
			FROM [[calendar]] WHERE [linkid] = {cid}';

        $types = array(
			'integer', 'text', 'timestamp', 'timestamp', 'text', 'text', 
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text',  
			'text', 'integer', 'integer', 'timestamp', 'timestamp', 'text', 
			'integer', 'integer'
		);
		
        if (!is_null($limit)) {
			$res = $GLOBALS['db']->setLimit($limit);
			if (Jaws_Error::IsError($res)) {
				return new Jaws_Error(_t('CALENDAR_GET_EVENTSOFCALENDAR'), _t('CALENDAR_NAME'));
			}
		}
		
		$result = $GLOBALS['db']->queryAll($sql, array('cid' => $cid), $types);
		
        if (Jaws_Error::IsError($result)) {
            //add language word for this
            return new Jaws_Error(_t('CALENDAR_GET_EVENTSOFCALENDAR'), _t('CALENDAR_NAME'));
        }

        return $result;
    }

    /**
     * Returns all event IDs that belong to a calendar
     *
     * @access  public
     * @return  array  Array with all the event IDs or Jaws_Error on error
     */
    function GetAllEventsOfCalendarByDate($cid, $date)
    {
        if (!empty($date)) {
			$date = $GLOBALS['db']->Date($GLOBALS['app']->UserTime2UTC($date));
			$sql  = 'SELECT [id], [event], [startdate], [enddate], 
					[sm_description], [description], [image], [host], 
					[itime], [endtime], [alink], [alinktitle], [alinktype], 
					[isrecurring], [active], [ownerid], [linkid], [created], 
					[updated], [checksum], [max_occupancy], [occupants]
				FROM [[calendar]] WHERE [linkid] = {cid} AND [startdate] <= {date} AND [enddate] >= {date}';

			$types = array(
				'integer', 'text', 'timestamp', 'timestamp', 'text', 'text', 
				'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text',  
				'text', 'integer', 'integer', 'timestamp', 'timestamp', 'text', 
				'integer', 'integer'
			);
			
			$result = $GLOBALS['db']->queryAll($sql, array('cid' => $cid, 'date' => $date), $types);
			
			if (Jaws_Error::IsError($result)) {
				//add language word for this
				return new Jaws_Error(_t('CALENDAR_GET_EVENTSOFCALENDAR'), _t('CALENDAR_NAME'));
			}
			return $result;
		} else {
            return new Jaws_Error(_t('CALENDAR_GET_EVENTSOFCALENDAR'), _t('CALENDAR_NAME'));
		}
    }
    
	/**
     * Returns all event IDs that belong to a calendar
     *
     * @access  public
     * @return  array  Array with all the event IDs or Jaws_Error on error
     */
    function GetAllEventsOfCalendarByDateRange($cid, $startdate, $enddate)
    {
	    $startdate = $GLOBALS['db']->Date($GLOBALS['app']->UserTime2UTC($startdate));
	    $enddate = $GLOBALS['db']->Date($GLOBALS['app']->UserTime2UTC($enddate));
		$sql  = 'SELECT [id], [event], [startdate], [enddate], 
				[sm_description], [description], [image], [host], 
				[itime], [endtime], [alink], [alinktitle], [alinktype], 
				[isrecurring], [active], [ownerid], [linkid], [created], 
				[updated], [checksum], [max_occupancy], [occupants]
			FROM [[calendar]] WHERE [linkid] = {cid} AND ([startdate] <= {enddate} AND [enddate] >= {startdate})';

        $types = array(
			'integer', 'text', 'timestamp', 'timestamp', 'text', 'text', 
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text',  
			'text', 'integer', 'integer', 'timestamp', 'timestamp', 'text', 
			'integer', 'integer'
		);
		
		$result = $GLOBALS['db']->queryAll($sql, array('cid' => $cid, 'startdate' => $startdate, 'enddate' => $enddate), $types);
		
        if (Jaws_Error::IsError($result)) {
            //add language word for this
            return new Jaws_Error(_t('CALENDAR_GET_EVENTSOFCALENDAR'), _t('CALENDAR_NAME'));
        }

        return $result;
    }
		
	/**
     * Returns all products that belongs to users of given Group ID
     *
     * @access  public
     * @return  array  Array with all the post IDs or Jaws_Error on error
     */
    function GetAllEventsOfGroupByDateRange($gid, $startdate, $enddate)
    {
	    $startdate = $GLOBALS['db']->Date($GLOBALS['app']->UserTime2UTC($startdate));
	    $enddate = $GLOBALS['db']->Date($GLOBALS['app']->UserTime2UTC($enddate));

		$sql  = "SELECT [[calendar]].[id], [[calendar]].[event], [[calendar]].[startdate], [[calendar]].[enddate], 
				[[calendar]].[sm_description], [[calendar]].[description], [[calendar]].[image], [[calendar]].[host], 
				[[calendar]].[itime], [[calendar]].[endtime], [[calendar]].[alink], [[calendar]].[alinktitle], [[calendar]].[alinktype], 
				[[calendar]].[isrecurring], [[calendar]].[active], [[calendar]].[ownerid], [[calendar]].[linkid], [[calendar]].[created], 
				[[calendar]].[updated], [[calendar]].[checksum], [[calendar]].[max_occupancy], [[calendar]].[occupants]
			FROM [[users_groups]]
            INNER JOIN [[calendar]] ON [[users_groups]].[user_id] = [[calendar]].[ownerid]
			WHERE ([[calendar]].[startdate] <= {enddate} AND [[calendar]].[enddate] >= {startdate}) AND ([[users_groups]].[group_id] = {gid}) AND ([[users_groups]].[status] = 'active' OR [[users_groups]].[status] = 'admin' OR [[users_groups]].[status] = 'founder')
		";
        
		$events = $GLOBALS['db']->queryAll($sql, array('gid' => $gid, 'startdate' => $startdate, 'enddate' => $enddate), $types);
		
        $types = array(
			'integer', 'text', 'timestamp', 'timestamp', 'text', 'text', 
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text',  
			'text', 'integer', 'integer', 'timestamp', 'timestamp', 'text', 
			'integer', 'integer'
		);

        if (Jaws_Error::IsError($events)) {
            //add language word for this
            //return new Jaws_Error(_t('STORE_ERROR_STORE_NOT_RETRIEVED'), _t('STORE_NAME'));
            return new Jaws_Error($events->GetMessage(), _t('STORE_NAME'));
        }	
		if (count($events) <= 0) {
			return array();
		}
		
		return $events;
    }
	
	/**
     * Returns all user's events of a date range
     *
     * @access  public
     * @return  array  Array with all the event IDs or Jaws_Error on error
     */
    function GetAllEventsOfUserIDByDateRange($id, $startdate, $enddate)
    {
	    $params = array();
		$params['startdate'] = $GLOBALS['db']->Date($GLOBALS['app']->UserTime2UTC($startdate));
	    $params['enddate'] = $GLOBALS['db']->Date($GLOBALS['app']->UserTime2UTC($enddate));
	    $params['OwnerID'] = $id;
		
		$sql  = 'SELECT [id], [event], [startdate], [enddate], 
				[sm_description], [description], [image], [host], 
				[itime], [endtime], [alink], [alinktitle], [alinktype], 
				[isrecurring], [active], [ownerid], [linkid], [created], 
				[updated], [checksum], [max_occupancy], [occupants]
			FROM [[calendar]] WHERE ([startdate] <= {enddate} AND [enddate] >= {startdate}) AND ([ownerid] = {OwnerID})';

        $types = array(
			'integer', 'text', 'timestamp', 'timestamp', 'text', 'text', 
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text',  
			'text', 'integer', 'integer', 'timestamp', 'timestamp', 'text', 
			'integer', 'integer'
		);
		
		$result = $GLOBALS['db']->queryAll($sql, $params, $types);
		
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('CALENDAR_EVENT_NOT_FOUND'), _t('CALENDAR_NAME'));
        }

        return $result;
    }
	
	/**
     * Returns all event IDs of a date range
     *
     * @access  public
     * @return  array  Array with all the event IDs or Jaws_Error on error
     */
    function GetAllEventsByDateRange($startdate, $enddate)
    {
	    $startdate = $GLOBALS['db']->Date($GLOBALS['app']->UserTime2UTC($startdate));
	    $enddate = $GLOBALS['db']->Date($GLOBALS['app']->UserTime2UTC($enddate));
		$sql  = 'SELECT [id], [event], [startdate], [enddate], 
				[sm_description], [description], [image], [host], 
				[itime], [endtime], [alink], [alinktitle], [alinktype], 
				[isrecurring], [active], [ownerid], [linkid], [created], 
				[updated], [checksum], [max_occupancy], [occupants] 
			FROM [[calendar]] WHERE ([startdate] <= {enddate} AND [enddate] >= {startdate})';

        $types = array(
			'integer', 'text', 'timestamp', 'timestamp', 'text', 'text', 
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text',  
			'text', 'integer', 'integer', 'timestamp', 'timestamp', 'text', 
			'integer', 'integer'
		);
		
		$result = $GLOBALS['db']->queryAll($sql, array('startdate' => $startdate, 'enddate' => $enddate), $types);
		
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('CALENDAR_EVENT_NOT_FOUND'), _t('CALENDAR_NAME'));
        }

        return $result;
    }
	
    /**
     * Gets a User's events
     *
     * @access  public
     * @param   int     $id  The user ID
     * @return  mixed   Returns an array with the calendars and false on error
     */
    function GetEventsOfUserID($id, $active = null, $sortColumn = 'created', $sortDir = 'ASC', $limit = null)
    {
		$fields = array(
			'startdate', 'enddate', 'event', 'image', 'linkid', 'created', 'updated', 
			'host', 'itime', 'endtime', 'isrecurring', 'active', 'ownerid'
		);
		$sortColumn = strtolower($sortColumn);
        if (!in_array($sortColumn, $fields)) {
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_WARNING, _t('CALENDAR_ERROR_UNKNOWN_COLUMN'));
            }
            $sortColumn = 'created';
        }

        $sortDir = strtoupper($sortDir);
		
		$params       = array();
        $params['id'] = (int)$id;
		
	    $sql  = 'SELECT [id], [event], [startdate], [enddate], 
				[sm_description], [description], [image], [host], 
				[itime], [endtime], [alink], [alinktitle], [alinktype], 
				[isrecurring], [active], [ownerid], [linkid], [created], 
				[updated], [checksum], [max_occupancy], [occupants] 
			FROM [[calendar]]
			WHERE ([ownerid] = {id})';
		if (!is_null($active)) {
			$sql .=  " AND ([active] = {active})";
			$params['active'] = $active;
		}
		$sql .= " ORDER BY [$sortColumn] $sortDir";

        $types = array(
			'integer', 'text', 'timestamp', 'timestamp', 'text', 'text', 
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text',  
			'text', 'integer', 'integer', 'timestamp', 'timestamp', 'text', 
			'integer', 'integer'
		);
		
        if (!is_null($limit)) {
			$res = $GLOBALS['db']->setLimit($limit);
			if (Jaws_Error::IsError($res)) {
				return new Jaws_Error(_t('CALENDAR_ERROR_GET_USER_EVENTS'), _t('CALENDAR_NAME'));
			}
		}
		
		$result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('CALENDAR_ERROR_GET_USER_EVENTS'), _t('CALENDAR_NAME'));
        }

        return $result;
    }

    /**
     * Gets events
     *
     * @access  public
     * @return  mixed   Returns an array with the calendars and false on error
     */
    function GetEvents($active = null, $sortColumn = 'created', $sortDir = 'ASC', $limit = null)
    {
		$fields = array(
			'startdate', 'enddate', 'event', 'image', 'linkid', 'created', 'updated', 
			'host', 'itime', 'endtime', 'isrecurring', 'active', 'ownerid', 'max_occupancy', 'occupants'
		);
		$sortColumn = strtolower($sortColumn);
        if (!in_array($sortColumn, $fields)) {
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_WARNING, _t('CALENDAR_ERROR_UNKNOWN_COLUMN'));
            }
            $sortColumn = 'created';
        }

        $sortDir = strtoupper($sortDir);
		
		$params       = array();
		
	    $sql  = 'SELECT [id], [event], [startdate], [enddate], 
				[sm_description], [description], [image], [host], 
				[itime], [endtime], [alink], [alinktitle], [alinktype], 
				[isrecurring], [active], [ownerid], [linkid], [created], 
				[updated], [checksum], [max_occupancy], [occupants] 
			FROM [[calendar]]';
		if (!is_null($active)) {
			$sql .=  " WHERE ([active] = {active})";
			$params['active'] = $active;
		}
		$sql .= " ORDER BY [$sortColumn] $sortDir";

        $types = array(
			'integer', 'text', 'timestamp', 'timestamp', 'text', 'text', 
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text',  
			'text', 'integer', 'integer', 'timestamp', 'timestamp', 'text', 
			'integer', 'integer'
		);
		
        if (!is_null($limit)) {
			$res = $GLOBALS['db']->setLimit($limit);
			if (Jaws_Error::IsError($res)) {
				return new Jaws_Error(_t('CALENDAR_ERROR_GET_USER_EVENTS'), _t('CALENDAR_NAME'));
			}
		}
		
		$result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('CALENDAR_ERROR_GET_USER_EVENTS'), _t('CALENDAR_NAME'));
        }

        return $result;
    }

	/**
     * Returns events that *every Calendar has* on each date within a given date range
     *
     * @access  public
     * @return  array  Array with all the event IDs or Jaws_Error on error
     */
    function GetAllAvailabilityByDateRange($startdate, $enddate, $OwnerID = 0)
    {
		$results = array();
		$all_dates = array();
		$unavailable_dates = array();
		$calendars = $this->GetAllCalendars(false);
		$now = $GLOBALS['db']->Date();
		if (!Jaws_Error::IsError($calendars)) {
			$c = 0;
			foreach ($calendars as $calendar) {
				if ($calendar['calendarparentownerid'] == (int)$OwnerID && $calendar['calendarparenttype'] == 'A') {
					$events = $this->GetAllEventsOfCalendarByDateRange($calendar['calendarparentid'], $startdate, $enddate);
					if (!Jaws_Error::IsError($events)) {
						foreach ($events as $event) {
							if ($event['event'] == 'Reserved') {
								// Get day difference of event's startdate and enddate
								$date = strtotime($event['startdate']);
								$dateDiff = strtotime($event['enddate']) - $date;
								$fullDays = (int)floor($dateDiff/(60*60*24));
								// Add event's startdate to array
								$all_dates[] = $date;
								// If event lasts more than one day, add each date of event to array
								if ($fullDays > 0) {
									for ($i=1; $i<$fullDays; $i++) {
										$all_dates[] = strtotime(date("Y-m-d", $date) . " +".$i." day");
									}
								}
							} else {
								$results[] = $event;
							}
						}
						$c++;
					} else {
						return new Jaws_Error(_t('CALENDAR_GET_EVENTSOFCALENDAR'), _t('CALENDAR_NAME'));
					}
				}
			}
			// If date(s) were found in all calendars, add to unavailable_dates array
			foreach ($all_dates as $a_date) {
				$occurrences = array_count_values($all_dates);
				if ($occurrences[$a_date] === $c) {
					$unavailable_dates[] = $a_date;
				}
			}
			$unavailable_dates = array_unique($unavailable_dates);
			$total = count($unavailable_dates);
			sort($unavailable_dates);
			// Create events for each unavailable date
			$newEvents = array();
			$lastdate = null;
			$e = 0;
			foreach ($unavailable_dates as $u_date) {
				$u_date = strtotime(date("Y-m-d", $u_date));
				//echo '<br />CURRENT ::: '.date("Y-m-d", $u_date).'<br />';
				if ($e > 0) {
					$dateDiff = $u_date - $enddate;
					$fullDays = (int)floor($dateDiff/(60*60*24));
					if ($fullDays === 1) {
						$dateDiff2 = $u_date - $startdate;
						$fullDays2 = (int)floor($dateDiff2/(60*60*24));
						$startdate = strtotime(date("Y-m-d", $u_date) . " -".$fullDays2." days");
						$enddate = $u_date;
					} else {
						$newEvents[$e][0] = $startdate;
						$newEvents[$e][1] = $enddate;
						$enddate = $u_date;
						$startdate = $u_date;
					}
				} else {
					$enddate = $u_date;
					$startdate = $u_date;
				}
				if ($e == ($total-1)) {
					$newEvents[$e][0] = $startdate;
					$newEvents[$e][1] = $enddate;
				}
				//echo '<br />STARTDATE ::: '.date("Y-m-d", $startdate).'<br />';
				//echo '<br />ENDDATE ::: '.date("Y-m-d", $enddate).'<br />';

				$e++;
			}
					
			foreach ($newEvents as $newEvent) {
				$results[] = array(
					'id' => $newEvent[0], 
					'event' => 'Reserved', 
					'startdate' => $GLOBALS['db']->Date($newEvent[0]),  
					'enddate' => $GLOBALS['db']->Date($newEvent[1]), 
					'sm_description' => '', 
					'description' => '', 
					'image' => '', 
					'host' => '', 
					'itime' => '', 
					'endtime' => '', 
					'alink' => '', 
					'alinktitle' => '', 
					'alinktype' => '', 
					'isrecurring' => 'N', 
					'active' => 'Y', 
					'ownerid' => $OwnerID, 
					'linkid' => '', 
					'created' => $now, 
					'updated' => $now, 
					'max_occupancy' => 0,
					'occupants' => 0 
				);
			}
		} else {
            return new Jaws_Error(_t('CALENDAR_GET_CATEGORY'), _t('CALENDAR_NAME'));
		}
		
        return $results;
    }

	/**
     * Returns all recurring event IDs of an event entry
     *
     * @access  public
     * @return  array  Array with all the event IDs or Jaws_Error on error
     */
    function GetAllRecurringEventsOfEventEntry($eid)
    {
	    $sql  = '
			SELECT [id], [calendarid], [dayname], 
				[dates], [linkid], [created], [updated], [checksum] 
			FROM [[calendar_std]] WHERE [calendarid] = {eid}';

        $types = array(
			'integer', 'integer', 'text', 'text', 'integer', 
			'timestamp', 'timestamp', 'text'
		);

		$result = $GLOBALS['db']->queryAll($sql, array('eid' => $eid), $types);
		
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('CALENDAR_GET_RECURRINGEVENTSOFCALENDAR'), _t('CALENDAR_NAME'));
        }

        return $result;
    }

	/**
     * Returns a calendar event
     *
     * @access  public
     * @return  array  The given event ID's row values or Jaws_Error on error
     */
    function GetEvent($eid)
    {
		$sql = '
            SELECT [id], [event], [startdate], [enddate], 
				[sm_description], [description], [image], [host], 
				[itime], [endtime], [alink], [alinktitle], [alinktype], 
				[isrecurring], [active], [ownerid], [linkid], [created], 
				[updated], [checksum], [max_occupancy], [occupants]
			FROM [[calendar]]
            WHERE [id] = {eid}';

        $params        = array();
        $params['eid'] = $eid;
        
        $types = array(
			'integer', 'text', 'timestamp', 'timestamp', 'text', 'text', 
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text',  
			'text', 'integer', 'integer', 'timestamp', 'timestamp', 'text', 
			'integer', 'integer'
		);

        $result = $GLOBALS['db']->queryRow($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('CALENDAR_EVENT_NOT_FOUND'), _t('CALENDAR_NAME'));
        }

        return $result;
    }

	    /**
     * Returns a recurring calendar event
     *
     * @access  public
     * @return  array  The given event ID's row values or Jaws_Error on error
     */
    function GetRecurringEvent($rid)
    {
		$sql = '
            SELECT [id], [calendarid], [dayname], 
				[dates], [linkid], [created], [updated], [checksum]
			FROM [[calendar_std]]
            WHERE [id] = {rid}';

        $params        = array();
        $params['rid'] = $rid;
        
        $types = array(
			'integer', 'integer', 'text', 'text', 'integer', 
			'timestamp', 'timestamp', 'text'
		);

        $result = $GLOBALS['db']->queryRow($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('CALENDAR_EVENT_NOT_FOUND'), _t('CALENDAR_NAME'));
        }

        return $result;
    }
	
	/**
     * Returns all recurring events of a calendar
     *
     * @access  public
     * @return  array  Array with all the event IDs or Jaws_Error on error
     */
    function GetAllRecurringEventsOfCalendar($cid)
    {
	    $sql  = '
			SELECT [id], [calendarid], [dayname], 
				[dates], [linkid], [created], [updated], [checksum] 
			FROM [[calendar_std]] WHERE [linkid] = {cid}';

        $types = array(
			'integer', 'integer', 'text', 'text', 'integer', 
			'timestamp', 'timestamp', 'text'
		);

		$result = $GLOBALS['db']->queryAll($sql, array('cid' => $cid), $types);
		
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('CALENDAR_GET_RECURRINGEVENTSOFCALENDAR'), _t('CALENDAR_NAME'));
        }

        return $result;
    }
    
    /**
     * Gets the users calendar data by ID
     *
     * @access  public
     * @param   int     $id  The user ID
     * @return  mixed   Returns an array with the calendars and false on error
     */
    function GetCalendarOfUserID($id, $table = 'calendarparent', $active = null, $sortColumn = 'created', $sortDir = 'ASC')
    {
        if ($table == 'calendarparent') {
			return $this->GetCategoriesOfUserID($id, $active, $sortColumn, $sortDir);
		} else {
			return $this->GetEventsOfUserID($id, $active, $sortColumn, $sortDir);
        }		
    }
    
    /**
     * Gets a user's single calendar by ID
     *
     * @access  public
     * @param   int     $id  The user ID
     * @param   int     $cid  The calendar ID to return
     * @return  mixed   Returns an array with the calendars and false on error
     */
    function GetSingleCalendarByUserID($id, $cid)
    {
		$params       = array();
        $params['id'] = $id;
        $params['cid'] = $cid;
		
		$sql = '
            SELECT [calendarparentid], [calendarparentsort_order], [calendarparentcategory_name], 
				[calendarparentimage], [calendarparentdescription], [calendarparentactive],
				[calendarparentownerid], [calendarparentcreated], [calendarparentupdated], 
				[calendarparentfeatured], [calendarparenttype], [calendarparentpropid], [calendarparentchecksum]
			FROM [[calendarparent]]
            WHERE ([calendarparentownerid] = {id} AND [calendarparentid] = {cid})';
		
        $types = array(
			'integer', 'integer', 'text', 'text', 'text', 'text', 
			'integer', 'timestamp', 'timestamp', 'text', 'text', 'integer', 'text'
		);
		
        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('CALENDAR_GET_CATEGORY'), _t('CALENDAR_NAME'));
        }

        return $result;
    }

}
