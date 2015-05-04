<?php
/**
 * Calendar Gadget (layout actions in client side)
 *
 * @category   GadgetLayout
 * @package    Calendar
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class CalendarLayoutHTML
{

    /**
     * Loads layout actions
     *
     * @access private
     */
    function LoadLayoutActions($limit = null, $offset = null)
    {
        $actions = array();
		if (is_null($offset) || $offset == 0) {
			$actions['Display'] = array(
				'mode' => 'LayoutAction', 
				'name' => _t('CALENDAR_NAME'), 
				'desc' => _t('CALENDAR_LAYOUT_FULL_DESCRIPTION')
			);
			$actions['LayoutWeek'] = array(
				'mode' => 'LayoutAction', 
				'name' => _t('CALENDAR_LAYOUT_WEEK'), 
				'desc' => _t('CALENDAR_LAYOUT_WEEK_DESCRIPTION')
			);
			/*
			$actions['LayoutYear'] = array(
				'mode' => 'LayoutAction', 
				'name' => _t('CALENDAR_LAYOUT_YEAR'), 
				'desc' => _t('CALENDAR_LAYOUT_YEAR_DESCRIPTION')
			);
			*/
			$actions['LayoutList'] = array(
				'mode' => 'LayoutAction', 
				'name' => _t('CALENDAR_LAYOUT_UPCOMINGEVENTS'), 
				'desc' => _t('CALENDAR_LAYOUT_UPCOMINGEVENTS_DESCRIPTION')
			);
			$actions['ListCategories']  = array(
				'mode' => 'LayoutAction', 
				'name' => _t('CALENDAR_LAYOUT_CATEGORIES'), 
				'desc' => _t('CALENDAR_LAYOUT_CATEGORIES_DESCRIPTION')
			);

			$actions['ReservationForm']  = array(
				'mode' => 'LayoutAction', 
				'name' => _t('CALENDAR_LAYOUT_RESERVATION'), 
				'desc' => _t('CALENDAR_LAYOUT_RESERVATION_DESCRIPTION')
			);
		}
		
		$model = $GLOBALS['app']->LoadGadget('Calendar', 'Model');
        
		//if ($GLOBALS['app']->Session->GetPermission('Calendar', 'ManageCategories')) {
			$calendars = $model->GetAllCalendars(false, $limit, $offset);
		//} else {
		//	$calendars = $model->GetCalendarOfUserID($GLOBALS['app']->Session->GetAttribute('user_id'));			
		//}

        if (!Jaws_Error::isError($calendars)) {
            foreach ($calendars as $calendar) {
				if ($calendar['calendarparentownerid'] == 0) {
	                /*
					$actions['LayoutMini(' . $calendar['calendarparentid'] . ')'] = array(
	                    'mode' => 'LayoutAction',
	                    'name' => $calendar['calendarparentcategory_name'],
	                    'desc' => _t('CALENDAR_LAYOUT_MINI_DESCRIPTION')
	                );
	                $actions['LayoutYear(' . $calendar['calendarparentid'] . ')'] = array(
	                    'mode' => 'LayoutAction',
	                    'name' => $calendar['calendarparentcategory_name'],
	                    'desc' => _t('CALENDAR_LAYOUT_YEAR_DESCRIPTION')
	                );
	                */
	                $actions['Display(' . $calendar['calendarparentid'] . ')'] = array(
	                    'mode' => 'LayoutAction',
	                    'name' => _t('CALENDAR_LAYOUT_FULL', $calendar['calendarparentcategory_name']),
	                    'desc' => _t('CALENDAR_LAYOUT_FULL_DESCRIPTION')
	                );
					$actions['LayoutWeek(' . $calendar['calendarparentid'] . ')'] = array(
	                    'mode' => 'LayoutAction',
	                    'name' => $calendar['calendarparentcategory_name'],
	                    'desc' => _t('CALENDAR_LAYOUT_WEEK_DESCRIPTION')
	                );
	                $actions['LayoutList(' . $calendar['calendarparentid'] . ')'] = array(
	                    'mode' => 'LayoutAction',
	                    'name' => _t('CALENDAR_LAYOUT_CATEGORYUPCOMINGEVENTS', $calendar['calendarparentcategory_name']),
	                    'desc' => _t('CALENDAR_LAYOUT_CATEGORYUPCOMINGEVENTS_DESCRIPTION')
	                );
				}
            }
        }

		if (is_null($offset) || $offset == 0) {
			$actions['ShowComments']  = array(
				'mode' => 'LayoutAction', 
				'name' => _t('CALENDAR_LAYOUT_SHOWCOMMENTS'), 
				'desc' => _t('CALENDAR_LAYOUT_SHOWCOMMENTS_DESCRIPTION')
			);
        }
		
		require_once JAWS_PATH . 'include/Jaws/User.php';
		$uModel = new Jaws_User;
		$groups = $uModel->GetAllGroups('name', null, $limit, $offset);

		if ($groups) {
			foreach ($groups as $group) {
				$groupName = (strpos($group['name'], '_') !== false ? ucfirst(str_replace('_', ' ', $group['name'])) : ucfirst($group['name']));
				$actions['ShowCommentsOfGroup(' . $group['id'] . ')'] = array(
					'mode' => 'LayoutAction',
					'name' => _t('CALENDAR_LAYOUT_SHOWGROUPCOMMENTS', $groupName),
					'desc' => _t('CALENDAR_LAYOUT_SHOWGROUPCOMMENTS_DESCRIPTION', $groupName)
				);
				$actions['CalendarOfGroup(' . $group['id'] . ')'] = array(
					'mode' => 'LayoutAction',
					'name' => _t('CALENDAR_LAYOUT_SHOWGROUPCALENDAR', $groupName),
					'desc' => _t('CALENDAR_LAYOUT_SHOWGROUPCALENDAR_DESCRIPTION', $groupName)
				);
			}
		}
		
        return $actions;
    }


	/**
     * Displays a calendar.
     *
     * @param 	int 	$id 	Calendar ID (optional)
     * @param 	string 	$layoutAction 	Display mode (Display/Mini/Month/Year/Week/Day/List) 
     * @param 	boolean 	$all_properties 	Properties mode 
     * @param 	boolean 	$all_availability 	Availability mode 
     * @param 	string 	$OwnerID 	Owner ID 
     * @param 	string 	$searchgroup 	Group ID 
     * @access public
     * @return string
     */
    function Display($cid = null, $layoutAction = 'Display', $all_properties = false, $all_availability = false, $OwnerID = null, $searchgroup = null)
    {		
        $GLOBALS['app']->Layout->AddHeadLink('gadgets/Calendar/resources/style.css', 'stylesheet', 'text/css');
		$request 	=& Jaws_Request::getInstance();
		$fetch 		= array('owner_id', 'startdate', 'css');
		$get  		= $request->getRaw($fetch, 'get');
		$post  		= $request->getRaw($fetch, 'post');
		
		if (is_null($OwnerID)) {
			$OwnerID = $get['owner_id'];
			if (empty($OwnerID)) {
				$OwnerID = $post['owner_id'];
			}
		}
		$OwnerID = (!is_null($OwnerID) ? (int)$OwnerID : null);
		
		$searchgroup = $request->get('Calendar_group', 'post');
		if (empty($searchgroup)) {
			$searchgroup = $request->get('Calendar_group', 'get');
		}
		
		$startdate = '';
		if (!empty($get['startdate'])) {
			$startdate = $get['startdate'];
		}
		if (!empty($post['startdate'])) {
			$startdate = $post['startdate'];
		}
		
		if (!is_null($cid) && is_numeric($cid)) {
			$cid = (int)$cid;
		} else {
			$cid = 0;
		}
				
		if ($all_properties === true) {
			$all_properties = 'true';
		} else {
			$all_properties = 'false';
		}
		
		if ($all_availability === true) {
			$all_availability = 'true';
		} else {
			$all_availability = 'false';
		}
		
		$output_html = "<iframe id='Calendar_iframe_".md5('Calendar'.$cid)."' class='calendar-iframe' src='index.php?gadget=Calendar&action=Display&cid=".$cid."&layoutAction=".$layoutAction."&all_properties=".$all_properties."&all_availability=".$all_availability.(!is_null($OwnerID) && !empty($OwnerID) ? "&owner_id=".$OwnerID : '').(!is_null($searchgroup) && !empty($searchgroup) ? "&Calendar_group=".$searchgroup : '')."&css=".urlencode($get['css'])."' frameborder='0'></iframe>";
		
		return $output_html;
		
    }

	/**
     * Accept reservation inquiries for availability calendars.
     *
     * @category 	feature
     * @access 	public
     * @return 	string
     */
    function ReservationForm($cid = null, $embedded = false, $referer = null, $searchownerid = '')
    {
        $GLOBALS['app']->Layout->AddHeadLink('gadgets/Calendar/resources/style.css', 'stylesheet', 'text/css');
        $GLOBALS['app']->Layout->AddHeadLink('libraries/piwi/piwidata/css/calendar-blue.css', 'stylesheet', 'text/css');
		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=Calendar&amp;action=Ajax&amp;client=all&amp;stub=CalendarAjax');
		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=Calendar&amp;action=AjaxCommonFiles');
		$GLOBALS['app']->Layout->AddScriptLink('gadgets/Calendar/resources/client_script.js');
		$GLOBALS['app']->Layout->AddScriptLink('libraries/piwi/piwidata/js/jscalendar/calendar.js');
		$GLOBALS['app']->Layout->AddScriptLink('libraries/piwi/piwidata/js/jscalendar/calendar-setup.js');
		$GLOBALS['app']->Layout->AddScriptLink('libraries/piwi/piwidata/js/jscalendar/lang/calendar-en.js');

		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        //$model = $GLOBALS['app']->LoadGadget('Calendar', 'Model');
		if (is_null($cid)) {
			$cid = (isset($get['id']) && is_numeric($get['id']) ? (int)$get['id'] : 'all');
		}
		$tpl = new Jaws_Template('gadgets/Calendar/templates/');
		$tpl->Load('normal.html');

		$tpl->SetBlock('layout');
		$tpl->SetVariable('actionName', 'ReservationForm_');
		$tpl->SetVariable('layout_title', '');

		$tpl->SetBlock('layout/reservationform');
		
		$onclick = '';
		if ($embedded === true) {
			$tpl->SetVariable('action', 'index.php?gadget=Calendar&action=Display');
			$onclick = ' onclick="window.open(\''.$GLOBALS['app']->getSiteURL().'/index.php?gadget=Calendar&action=Display&all_availability=\'+$(\'all_availability\').value+\'&owner_id=\'+$(\'owner_id\').value+\'&cid=\'+$(\'cid\').value+\'&startdate=\'+$(\'startdate\').value); return false;"';
		} else {
			$tpl->SetVariable('action', 'index.php?gadget=Calendar&action=Calendar');
		}
		//$tpl->SetVariable('base_url', JAWS_DPATH);
		$tpl->SetVariable('site_url', $GLOBALS['app']->getSiteURL());
		$tpl->SetVariable('onclick', $onclick);
				
		$category_options = '';
		$date = $GLOBALS['app']->UTC2UserTime('', "m/d/Y");
		
	  $category_options .= '<p>Arrival Date:&nbsp;<input type="text" NAME="startdate" ID="startdate" SIZE="10" VALUE="'.$date.'" maxlength="10">
		&nbsp;&nbsp;<button type="button" name="start_button" id="start_button">
		<img id="start_button_stockimage" src="'.$GLOBALS['app']->GetJawsURL() . '/libraries/piwi/piwidata/art/stock/apps/office-calendar.png" border="0" alt="" height="16" width="16" />
		</button>
		<input NAME="all_availability" ID="all_availability" type="hidden" VALUE="true">
		<input NAME="owner_id" ID="owner_id" type="hidden" VALUE="'.$searchownerid.'">
		<input NAME="cid" ID="cid" type="hidden" VALUE="'.$cid.'">
		</p>
		<script type="text/javascript">
		 Calendar.setup({
		  inputField: "startdate",
		  ifFormat: "%m/%d/%Y",
		  button: "start_button",
		  singleClick: true,
		  weekNumbers: false,
		  firstDay: 0,
		  date: "",
		  showsTime: false,
		  multiple: false});
		</script>
        ';
		/*
		$category_options .= '<b>Time: </b>&nbsp;
		<select size="1" name="iTimeHr">
			  <option value="12" selected>12</option>
			  <option value="1" >1</option>
			  <option value="2" >2</option>
			  <option value="3" >3</option>
			  <option value="4" >4</option>

			  <option value="5" >5</option>
			  <option value="6" >6</option>
			  <option value="7" >7</option>
			  <option value="8" >8</option>
			  <option value="9" >9</option>
			  <option value="10" >10</option>

			  <option value="11" >11</option>
		  </select>
		  <select size="1" name="iTimeMin">
			  <option value="00" selected>00</option>
			  <option value="01" >01</option>
			  <option value="02" >02</option>
			  <option value="03" >03</option>

			  <option value="04" >04</option>
			  <option value="05" >05</option>
			  <option value="06" >06</option>
			  <option value="07" >07</option>
			  <option value="08" >08</option>
			  <option value="09" >09</option>

			  <option value="10" >10</option>
			  <option value="11" >11</option>
			  <option value="12" >12</option>
			  <option value="13" >13</option>
			  <option value="14" >14</option>
			  <option value="15" >15</option>

			  <option value="16" >16</option>
			  <option value="17" >17</option>
			  <option value="18" >18</option>
			  <option value="19" >19</option>
			  <option value="20" >20</option>
			  <option value="21" >21</option>

			  <option value="22" >22</option>
			  <option value="23" >23</option>
			  <option value="24" >24</option>
			  <option value="25" >25</option>
			  <option value="26" >26</option>
			  <option value="27" >27</option>

			  <option value="28" >28</option>
			  <option value="29" >29</option>
			  <option value="30" >30</option>
			  <option value="31" >31</option>
			  <option value="32" >32</option>
			  <option value="33" >33</option>

			  <option value="34" >34</option>
			  <option value="35" >35</option>
			  <option value="36" >36</option>
			  <option value="37" >37</option>
			  <option value="38" >38</option>
			  <option value="39" >39</option>

			  <option value="40" >40</option>
			  <option value="41" >41</option>
			  <option value="42" >42</option>
			  <option value="43" >43</option>
			  <option value="44" >44</option>
			  <option value="45">45</option>

			  <option value="46" >46</option>
			  <option value="47" >47</option>
			  <option value="48" >48</option>
			  <option value="49" >49</option>
			  <option value="50" >50</option>
			  <option value="51" >51</option>

			  <option value="52" >52</option>
			  <option value="53" >53</option>
			  <option value="54" >54</option>
			  <option value="55" >55</option>
			  <option value="56" >56</option>
			  <option value="57" >57</option>

			  <option value="58" >58</option>
			  <option value="59" >59</option>
		  </select>
		  <select size="1" name="iTimeSuffix">
			  <option value="AM" >AM</option>
			  <option value="PM" selected>PM</option>
		  </select>';
		*/
		$tpl->SetVariable('content', $category_options);
		
		$tpl->ParseBlock('layout/reservationform');
			
		// If this is embedded, we need to pass the javascript variable that has the IFRAME height to the session 
		if ($embedded == true && !is_null($referer)) {	
			$tpl->SetBlock('layout/embedded');
			$tpl->SetVariable('id', (!empty($cid) && is_numeric($cid) ? (int)$cid : 'all'));		        
			if (strtolower($referer) == strtolower($_SERVER['SERVER_NAME'])) {
				$tpl->SetVariable('referer', $GLOBALS['app']->GetJawsURL() . "/libraries/iframes/domain1/iframetest_resize1.html");		        
			} else {	
				$tpl->SetVariable('referer', "http://".$referer."/19/img/agents/19/custom_img/common_files/iframetest_resize1.html");		        
			}
			$tpl->ParseBlock('layout/embedded');
		} else {
			$tpl->SetBlock('layout/not_embedded');
			$tpl->SetVariable('id', (!empty($cid) && is_numeric($cid) ? (int)$cid : 'all'));		        
			$tpl->ParseBlock('layout/not_embedded');
		}
		
		$tpl->ParseBlock('layout');

		return $tpl->Get();
    }

	/**
     * View mini calendar.
     *
     * @category 	feature
     * @access 	public
     * @return 	string
     */
    function LayoutMini($cid = null, $OwnerID = 0)
    {
		if (!is_null($cid)) {
			$cid = (int)$cid;
		}
        return $this->Display($cid, 'LayoutMini', false, false, $OwnerID);
    }

	/**
     * View calendar by week.
     *
     * @category 	feature
     * @access 	public
     * @return 	string
     */
    function LayoutWeek($cid = null, $OwnerID = 0)
    {
		if (!is_null($cid)) {
			$cid = (int)$cid;
		}
        return $this->Display($cid, 'LayoutWeek', false, false, $OwnerID);
    }

	/**
     * View calendar by year.
     *
     * @category 	feature
     * @access 	public
     * @return 	string
     */
    function LayoutYear($cid = null, $OwnerID = 0)
    {
		if (!is_null($cid)) {
			$cid = (int)$cid;
		}
        return $this->Display($cid, 'LayoutYear', false, false, $OwnerID);
    }

	/*
	 * View agenda list of upcoming events.
     *
     * @category 	feature
     * @access 	public
     * @return 	string
     */
    function LayoutList($cid = null, $OwnerID = 0, $startdate = null, $enddate = null)
    {		
		if (!is_null($cid)) {
			$cid = (int)$cid;
		}
/*
		$GLOBALS['app']->Layout->AddHeadLink('gadgets/Calendar/resources/style.css', 'stylesheet', 'text/css');
		$model = $GLOBALS['app']->LoadGadget('Calendar', 'Model');

		$tpl = new Jaws_Template('gadgets/Calendar/templates/');
        $tpl->Load('normal.html');

        $tpl->SetBlock('layout');
		$tpl->SetVariable('actionName', 'LayoutList');
		$tpl->SetVariable('link', "?gadget=Calendar");
        $tpl->SetVariable('layout_title', _t('CALENDAR_LAYOUT_UPCOMINGEVENTS'));
	    $tpl->SetBlock('layout/calendarlayout');

        $tpl->SetVariable('element', _t('CALENDAR_LAYOUT_UPCOMINGEVENTS_DESCRIPTION'));

	    $tpl->ParseBlock('layout/calendarlayout');
        $tpl->ParseBlock('layout');
        return $tpl->Get();
*/
		if (!is_null($cid)) {
			$cid = (int)$cid;
		}
        return $this->Display($cid, 'LayoutList', false, false, $OwnerID);

	}

	/*
     * View list of public calendars.
     *
     * @category 	feature
     * @access 	public
     * @return 	string
     */
    function ListCategories()
    {
        $GLOBALS['app']->Layout->AddHeadLink('gadgets/Calendar/resources/style.css', 'stylesheet', 'text/css');
		$model = $GLOBALS['app']->LoadGadget('Calendar', 'Model');

		$tpl = new Jaws_Template('gadgets/Calendar/templates/');
        $tpl->Load('normal.html');

        $tpl->SetBlock('layout');
		$tpl->SetVariable('actionName', 'ListCategories');
		$tpl->SetVariable('link', "?gadget=Calendar");
        $tpl->SetVariable('layout_title', _t('CALENDAR_LAYOUT_CATEGORIES'));
	    $tpl->SetBlock('layout/calendarlayout');

        $tpl->SetVariable('element', _t('CALENDAR_LAYOUT_CATEGORIES_DESCRIPTION'));

	    $tpl->ParseBlock('layout/calendarlayout');
        $tpl->ParseBlock('layout');

        return $tpl->Get();
	}
	
	/*
     * Calendar comments.
     *
     * @access 	public
     * @return 	string
     */
    function ShowComments(
		$id = null, $public = true, $header = 'Events', $interactive = true, $replies_shown = 2, 
		$layout = 'full', $only_comments = false, $limit = 5, $method = 'GetComments'
	) {
        $GLOBALS['app']->Layout->AddHeadLink('gadgets/Calendar/resources/style.css', 'stylesheet', 'text/css');
		$usersHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		return $usersHTML->ShowComments('Calendar', $public, $id, $header, $interactive, $replies_shown, $layout, $only_comments, $limit, $method);
	}
	
    /**
     * Show comments of group
     *
     * @access 	public
     * @return 	string template content
     */
    function ShowCommentsOfGroup($id) {
        $GLOBALS['app']->Layout->AddHeadLink('gadgets/Calendar/resources/style.css', 'stylesheet', 'text/css');
		$usersHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		return $usersHTML->ShowComments('Calendar', true, $id, 'Events', true, 2, 'full', false, 5, 'GetGroupComments');
    }
	
    /**
     * Shows calendar of group
     *
     * @access 	public
     * @return 	string template content
     */
    function CalendarOfGroup($id) {
        $GLOBALS['app']->Layout->AddHeadLink('gadgets/Calendar/resources/style.css', 'stylesheet', 'text/css');
		return $this->Display(null, 'Display', false, false, null, $id);
    }
	
}

