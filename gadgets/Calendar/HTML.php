<?php
/**
 * Calendar Gadget
 *
 * @category   Gadget
 * @package    Calendar
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */

class CalendarHTML extends Jaws_GadgetHTML
{
    var $_Name = 'Calendar';

    /**
     * Constructor
     *
     * @access 	public
     */
    function CalendarHTML()
    {
        $this->Init('Calendar');
    }

    /**
     * Excutes the default action, currently displaying the default page.
     *
     * @access 	public
     * @return 	string
     */
    function DefaultAction()
    {
        return $this->Index();
    }

    /**
     * Display an individual calendar.
     *
     * @param 	int 	$id 	Calendar ID (optional)
     * @param 	string 	$mode 	Display mode (Display/Mini/Month/Year/Week/Day/List) 
     * @param 	boolean 	$embedded 	Embedded mode 
     * @param 	string 	$referer 	Embedding referer 
     * @param 	boolean 	$all_properties 	Properties mode 
     * @param 	boolean 	$all_availability 	Availability mode 
     * @param 	string 	$OwnerID 	Owner ID 
     * @param 	string 	$searchgroup 	Group ID 
     * @access 	public
     * @return 	string
     */
	function Calendar(
		$id = null, $mode = 'Display', $embedded = false, $referer = null, $all_properties = false, 
		$all_availability = false, $OwnerID = null, $searchgroup = null
	) {
        $GLOBALS['app']->Layout->AddScriptLink('libraries/js/global2.js');
		$request =& Jaws_Request::getInstance();
        $get = $request->get(array('id','mode','all_properties','all_availability','owner_id', 'gadget', 'name'), 'get');
        $post = $request->get(array('id','mode','all_properties','all_availability','owner_id'), 'post');
		/*
		if (strtolower($get['gadget']) == 'users' && isset($get['name']) && !empty($get['name'])) {
			require_once JAWS_PATH . 'include/Jaws/User.php';
			$jUser = new Jaws_User;
			$info  = $jUser->GetUserInfoByName($get['name']);
			$OwnerID = $info['id'];
		}
		*/
		if (is_null($OwnerID)) {
			$OwnerID = $post['owner_id'];
			if (empty($OwnerID)) {
				$OwnerID = $get['owner_id'];
			}
		}
		$OwnerID = (!is_null($OwnerID) ? (int)$OwnerID : null);
		
		$searchgroup = $request->get('Calendar_group', 'post');
		if (empty($searchgroup)) {
			$searchgroup = $request->get('Calendar_group', 'get');
		}
		
		$get_all_properties = (!empty($post['all_properties']) ? $post['all_properties'] : $get['all_properties']);
		if (!empty($get_all_properties)) {
			$all_properties = ($get_all_properties == 'true' ? true : false);
		}
		$get_all_availability = (!empty($post['all_availability']) ? $post['all_availability'] : $get['all_availability']);
		if (!empty($get_all_availability)) {
			$all_availability = ($get_all_availability == 'true' ? true : false);
		}
        $GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $get['id'] = $xss->defilter($get['id']);

		$calendarLayout = $GLOBALS['app']->LoadGadget('Calendar', 'LayoutHTML');
        $model = $GLOBALS['app']->LoadGadget('Calendar', 'Model');
        if (is_null($id) && !empty($get['id']) && is_numeric($get['id'])) {
			$id = (int)$get['id'];
		} else {
			if (is_null($id) && !empty($post['id']) && is_numeric($post['id'])) {
				$id = (int)$post['id'];
			}
		}
        if (is_null($id) && !empty($get['cid']) && is_numeric($get['cid'])) {
			$id = (int)$get['cid'];
		} else {
			if (is_null($id) && !empty($post['cid']) && is_numeric($post['cid'])) {
				$id = (int)$post['cid'];
			}
		}
		if (is_null($id)) {
			$id = 'all';
		}
		$display_id = md5($this->_Name.$id);

		$modes = array('mini', 'month', 'year', 'week', 'day', 'list');
		$mode = 'Display';
		if (isset($post['mode']) && in_array(strtolower($post['mode']), $modes)) {
			$mode = 'Layout'.ucfirst(strtolower($post['mode']));
		} else if (isset($get['mode']) && in_array(strtolower($get['mode']), $modes)) {
			$mode = 'Layout'.ucfirst(strtolower($get['mode']));
		}
		$output = '';
		
		$tpl = new Jaws_Template('gadgets/Calendar/templates/');
		$tpl->Load('normal.html');
		$tpl->SetBlock('calendar');
		
		if (!is_null($searchgroup)) {
			$output = $calendarLayout->Display(null, $mode, false, false, null, $searchgroup);
		} else if ($all_properties === true) {
			$output = $calendarLayout->Display(null, $mode, true, false, $OwnerID);
		} else if ($all_availability === true) {
			$output = $calendarLayout->Display(null, $mode, false, true, $OwnerID);
		} else if ($id == 'all') {
			$output = $calendarLayout->Display(null, $mode, false, false, $OwnerID);
		} else {
		
			$page = $model->GetCalendar($id);

			if (Jaws_Error::IsError($page)) {
				require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
				return Jaws_HTTPError::Get(404);
			} else {

				if ($page['calendarparentactive'] == 'N' && $embedded == false) {
					//$this->SetTitle(_t('CALENDAR_TITLE_NOT_FOUND'));
					$tpl->SetBlock('calendar/not_found');
					$tpl->SetVariable('id', 'Calendar');
					$tpl->SetVariable('content', _t('CALENDAR_CONTENT_NOT_FOUND'));
					$tpl->SetVariable('title', _t('CALENDAR_TITLE_NOT_FOUND'));
					$tpl->ParseBlock('calendar/not_found');
				} else if ($page['calendarparenttype'] == 'G' && isset($page['calendarparentcategory_name'])) {
					$tpl->SetBlock('calendar/content');
					$tpl->SetVariable('id', $page['calendarparentid']);
					$google_calendarHTML = "";
					if ($page['calendarparenttype'] == 'G') {
						// Google Calendar Embed URL
						$googleCalendarUrl = "http://www.google.com/calendar/embed?src=".$page['calendarparentcategory_name'];
						$google_calendarHTML .= "<div>".$page['calendarparentdescription']."</div>\n";
						$google_calendarHTML .= "<iframe id='calendar-iframe-google".$page['calendarparentcategory_name']."' style='background: transparent url(); border-right: 0pt; border-top: 0pt; border-left: 0pt; border-bottom: 0pt; min-height: 300px; height: 100%; width: 100%;' src='".$googleCalendarUrl."' frameborder='0' allowTransparency='true' scrolling='no'></iframe>";
						//$google_calendarHTML .= "<script type=\"text/javascript\">if (document.getElementById('calendar-iframe-google".$page['calendarparentcategory_name']."').parentNode) { document.getElementById('calendar-iframe-google".$page['calendarparentcategory_name']."').style.height = parseInt(document.getElementById('calendar-iframe-google".$page['calendarparentcategory_name']."').parentNode.offsetHeight); }</script>";
					}
					$tpl->SetVariable('content', $google_calendarHTML);
					$tpl->ParseBlock('calendar/content');
				} else {
					$tpl->SetBlock('calendar/content');
					$tpl->SetVariable('id', $page['calendarparentid']);
					$page_content = "<div>".$page['calendarparentdescription']."</div>\n";
					// show the right calendar view
					if ($mode == 'Display') {
						$page_content .= $calendarLayout->Display($page['calendarparentid'], 'Display', false, false, $OwnerID);
					} else {
						if (method_exists($calendarLayout, $mode)) {
							$page_content .= $calendarLayout->$mode($page['calendarparentid'], $OwnerID);
						} else {
							$page_content .= $calendarLayout->Display($page['calendarparentid'], $mode, false, false, $OwnerID);
						}
					}
					$tpl->SetVariable('content', $page_content);
					$tpl->ParseBlock('calendar/content');
				}
			}
		}
			
		if ($embedded == true && !is_null($referer)) {	
			$tpl->SetBlock('calendar/embedded');
			$tpl->SetVariable('id', $display_id);		        
			if (strtolower($referer) == strtolower($_SERVER['SERVER_NAME'])) {
				$tpl->SetVariable('referer', $GLOBALS['app']->GetJawsURL() . "/libraries/iframes/domain1/iframetest_resize1.html");		        
			} else {	
				$tpl->SetVariable('referer', "http://".$referer."/19/img/agents/19/custom_img/common_files/iframetest_resize1.html");		        
			}
			$tpl->ParseBlock('calendar/embedded');
		} else {
			$tpl->SetBlock('calendar/not_embedded');
			$tpl->SetVariable('id', $display_id);		        
			$tpl->ParseBlock('calendar/not_embedded');
		}
			
        $tpl->ParseBlock('calendar');

        $output .= $tpl->Get();
		return $output;
    }

    /**
     * View calendar by month.
     *
     * @access 	public
     * @return 	string
     */
    function Month()
    {
        return $this->Calendar();
    }

    /**
     * View calendar by week.
     *
     * @access 	public
     * @return 	string
     */
    function Week()
    {
        return $this->Calendar(null, 'week');
    }

    /**
     * View calendar by year.
     *
     * @access 	public
     * @return 	string
     */
    function Year()
    {
        return $this->Calendar(null, 'year');
    }

    /**
     * RSVP and comment threads for events.
     *
     * @category 	feature
     * @param 	int 	$id 	Event ID
     * @access 	public
     * @return 	string
     */
    function Detail($id = null)
    {
		require_once JAWS_PATH . 'include/Jaws/Image.php';
        
		$request =& Jaws_Request::getInstance();
        $get = $request->get(array('id'), 'get');

        $GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $get['id'] = $xss->defilter($get['id']);
        $model = $GLOBALS['app']->LoadGadget('Calendar', 'Model');
        $usersHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');

        if (is_null($id)) {
			$id = $get['id'];
        }
        $page = $model->GetEvent((int)$id);
		if (Jaws_Error::IsError($page) || !isset($page['id']) || empty($page['id'])) {
            //return _t('CALENDAR_ERROR_DETAIL_NOT_LOADED');
			require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
			return Jaws_HTTPError::Get(404);
        } else {
			return $usersHTML->ShowComments('Calendar', true, null, false, false, 2, 'full', false, 5, 'GetComments');
		}
		
		/*
		$xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $tpl = new Jaws_Template('gadgets/Calendar/templates/');
        $tpl->Load('normal.html');
        $tpl->SetBlock('detail');
	    $tpl->SetVariable('actionName', 'Detail');
        $tpl->SetVariable('title', _t('CALENDAR_TITLE_DETAIL'));
        $tpl->SetVariable('link', $GLOBALS['app']->Map->GetURLFor('Calendar', 'Detail', array('id' => (int)$id)));
        if ($page['active'] == 'Y') {
			$main_image_src = '';
			if (!empty($page['image']) && isset($page['image'])) {
				if (strpos($page['image'],".swf") !== false) {
					// Flash file not supported
				} else if (substr($page['image'],0,7) == "GADGET:") {
					$main_image_src = $xss->filter(strip_tags($page['image']));
				} else {
					$main_image_src = $xss->filter(strip_tags($page['image']));
					if (substr(strtolower($main_image_src), 0, 4) == "http") {
						if (substr(strtolower($main_image_src), 0, 7) == "http://") {
							$main_image_src = explode('http://', $main_image_src);
							foreach ($main_image_src as $img_src) {
								if (!empty($img_src)) {
									$main_image_src = 'http://'.$img_src;
									break;
								}
							}
						} else {
							$main_image_src = explode('https://', $main_image_src);
							foreach ($main_image_src as $img_src) {
								if (!empty($img_src)) {
									$main_image_src = 'https://'.$img_src;
									break;
								}
							}
						}
					} else {
						$medium = Jaws_Image::GetMediumPath($main_image_src);
						if (file_exists(JAWS_DATA . 'files'.$medium)) {
							$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$medium;
						} else if (file_exists(JAWS_DATA . 'files'.$main_image_src)) {
							$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$main_image_src;
						}
					}
				}
			}
			$image = (!empty($main_image_src) ? '<img border="0" src="' . $main_image_src.'" />' : '&nbsp;');
			$host = (isset($page['host']) && !empty($page['host']) ? '<b>Where</b>:&nbsp; '.$page['host'] : '&nbsp;');
			$date = $GLOBALS['app']->loadDate();
			$date_str = '';
			if (isset($page['startdate']) && !empty($page['startdate'])) {
				$event_start_day = $date->Format($page['startdate'], 'DN');
				$event_start_date = $date->Format($page['startdate'], 'd');
				$event_start_monthname = $date->Format($page['startdate'], 'MN');
				$event_start_year = $date->Format($page['startdate'], 'Y');
				$date_str .= $event_start_day.', '.$event_start_monthname.' '.$event_start_date;
				if (isset($page['enddate']) && !empty($page['enddate']) && ($page['enddate'] != $page['startdate'])) {
					$event_end_day = $date->Format($page['enddate'], 'DN');
					$event_end_date = $date->Format($page['enddate'], 'd');
					$event_end_monthname = $date->Format($page['enddate'], 'MN');
					$event_end_year = $date->Format($page['enddate'], 'Y');
					$date_str .= (!empty($date_str) ? ' - ' : '');
					$date_str .= $event_end_day.', '.$event_end_monthname.' '.$event_end_date;
				}
			} else {
				$date_str .= "date to be decided";
			}
			$time_str = (isset($page['itime']) && !empty($page['itime']) ? $date->Format($page['itime'], "g:i A") : '');
			$time_str .= (isset($page['endtime']) && !empty($page['endtime']) && ($page['endtime'] != $page['itime']) ? (!empty($time_str) ? ' - ' : '').$date->Format($page['endtime'], "g:i A") : '');
			if (!empty($time_str)) {
				$time_str .= ', ';
			}
			$tpl->SetBlock('detail/item');
			$tpl->SetVariable('event', $page['event']);
			$tpl->SetVariable('date_str', $date_str);
			$tpl->SetVariable('time_str', $time_str);
			$tpl->SetVariable('description', $page['description']);
			$tpl->SetVariable('sm_description', $page['sm_description']);
			$tpl->SetVariable('image', $image);
			$tpl->SetVariable('host', (!empty($host) ? $host : ''));
			$tpl->ParseBlock('detail/item');
		}
        $tpl->ParseBlock('detail');

        return $tpl->Get();
		*/
    }
	
    /**
     * View all events for given date.
     *
     * @param 	int 	$cid 	Calendar ID
     * @access 	public
     * @return 	string
     */
    function Day($cid = null)
    {
		require_once JAWS_PATH . 'include/Jaws/Image.php';
        
		$request =& Jaws_Request::getInstance();
        $post = $request->get(array('id', 'tdate'), 'get');

        $GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $post['id'] = $xss->defilter($post['id']);
        $post['tdate'] = $xss->defilter(urldecode($post['tdate']));
		if (strpos($post['tdate'], "-")) {
			$post['tdate'] = str_replace("-", "/", $post['tdate']);
		}
        $model = $GLOBALS['app']->LoadGadget('Calendar', 'Model');

        if (is_null($cid)) {
			$cid = $post['id'];
        }
        $pages = $model->GetAllEventsOfCalendarByDate((int)$cid, $post['tdate']);
        if (Jaws_Error::IsError($pages)) {
            return _t('CALENDAR_ERROR_DETAIL_NOT_LOADED');
        }

		$xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $tpl = new Jaws_Template('gadgets/Calendar/templates/');
        $tpl->Load('normal.html');
        $tpl->SetBlock('detail');
	    $tpl->SetVariable('actionName', 'Detail');
        $tpl->SetVariable('title', _t('CALENDAR_TITLE_DETAIL'));
        $tpl->SetVariable('link', $GLOBALS['app']->Map->GetURLFor('Calendar', 'Day', array('id' => (int)$cid, 'tdate' =>  urlencode(str_replace("/", "-",$post['tdate'])))));
        foreach ($pages as $page) {
            if ($page['active'] == 'Y') {
				$main_image_src = '';
				if (!empty($page['image']) && isset($page['image'])) {
					if (strpos($page['image'],".swf") !== false) {
						// Flash file not supported
					} else if (substr($page['image'],0,7) == "GADGET:") {
						$main_image_src = $xss->filter(strip_tags($page['image']));
					} else {
						$main_image_src = $xss->filter(strip_tags($page['image']));
						if (substr(strtolower($main_image_src), 0, 4) == "http") {
							if (substr(strtolower($main_image_src), 0, 7) == "http://") {
								$main_image_src = explode('http://', $main_image_src);
								foreach ($main_image_src as $img_src) {
									if (!empty($img_src)) {
										$main_image_src = 'http://'.$img_src;
										break;
									}
								}
							} else {
								$main_image_src = explode('https://', $main_image_src);
								foreach ($main_image_src as $img_src) {
									if (!empty($img_src)) {
										$main_image_src = 'https://'.$img_src;
										break;
									}
								}
							}
						} else {
							$medium = Jaws_Image::GetMediumPath($main_image_src);
							if (file_exists(JAWS_DATA . 'files'.$medium)) {
								$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$medium;
							} else if (file_exists(JAWS_DATA . 'files'.$main_image_src)) {
								$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$main_image_src;
							}
						}
					}
				}
				$image = (!empty($main_image_src) ? '<img border="0" src="' . $main_image_src.'" />' : '&nbsp;');
				$host = (isset($page['host']) && $page['host'] != '') ? '<b>Where</b>:&nbsp; '.$page['host'] : '&nbsp;';
				$date = $GLOBALS['app']->loadDate();
				$page['startdate'] =  str_replace("12:00am", "", $date->Format($page['startdate']));
				$page['startdate'] =  str_replace(",", "", $page['startdate']);
				$page['startdate'] =  str_replace("-", "", $page['startdate']);
				$page['enddate'] =  str_replace("12:00am", "", $date->Format($page['enddate']));
				$page['enddate'] =  str_replace(",", "", $page['enddate']);
				$page['enddate'] =  str_replace("-", "", $page['enddate']);
				$enddate = (isset($page['enddate']) && $page['enddate'] != $page['startdate']) ? ' - '.$page['enddate'] : '&nbsp;';
				$tpl->SetBlock('detail/item');
				$tpl->SetVariable('event', $page['event']);
				$tpl->SetVariable('startdate', $page['startdate']);
				$tpl->SetVariable('enddate', $enddate);
				$tpl->SetVariable('description', $page['description']);
				$tpl->SetVariable('image', $image);
				$tpl->SetVariable('host', $host);
				$tpl->SetVariable('itime', $page['itime']);
				$tpl->SetVariable('endtime', $page['endtime']);
				$tpl->ParseBlock('detail/item');
			}
		}
        $tpl->ParseBlock('detail');

        return $tpl->Get();
    }

    /**
     * Displays an index of calendars.
     *
     * @category 	feature
     * @param 	int 	$uid 	Owner ID
     * @param 	boolean 	$embedded 	Embedded mode
     * @param 	string 	$refere 	Embedding referer
     * @access 	public
     * @return 	string
     */
    function Index($uid = null, $embedded = false, $referer = null)
    {
        $model = $GLOBALS['app']->LoadGadget('Calendar', 'Model');
        if (!is_null($uid)) {
			$pages = $model->GetCalendarOfUserID($uid);
		} else {
			$pages = $model->GetAllCalendars();
        }
        if (Jaws_Error::IsError($pages)) {
            return _t('CALENDAR_ERROR_INDEX_NOT_LOADED');
        }
		reset($pages);
		$date = $GLOBALS['app']->loadDate();
		$xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $tpl = new Jaws_Template('gadgets/Calendar/templates/');
        $tpl->Load('normal.html');
        $tpl->SetBlock('index');
		if ($embedded == true && !is_null($referer) && isset($embed_id)) {
			$id = $embed_id;
		} else {
			$id = 'List';
		}
		$tpl->SetVariable('id', $id);
		$display_id = md5($this->_Name.$id);
	    $tpl->SetVariable('actionName', 'Index');
        $tpl->SetVariable('title', _t('CALENDAR_TITLE_INDEX'));
		if (count($pages) <= 0) {
			$tpl->SetBlock('index/no_items');
			$tpl->SetVariable('content', _t('CALENDAR_CATEGORY_NOT_FOUND'));
			$tpl->ParseBlock('index/no_items');
		}
        //$tpl->SetVariable('link', $GLOBALS['app']->Map->GetURLFor('CustomPage', 'Index'));
        foreach ($pages as $page) {
            if ($page['calendarparentpropid'] == 0) {
				if (!is_null($uid)) {
					$tpl->SetBlock('index/item');
					$tpl->SetVariable('title', $page['calendarparentcategory_name']);
					$tpl->SetVariable('update_string',  _t('CALENDAR_LAST_UPDATE') . ': ');
					$tpl->SetVariable('updated', $date->Format($page['calendarparentupdated']));
					//$tpl->SetVariable('desc', strip_tags($page['content'], '<p><a><b><em><i><strong><div><table><tbody><thead><tr><td><span><font><hr /><br />'));
					if ($embedded == false) {
						$param = array('id' => $page['calendarparentid']);
						$link = $GLOBALS['app']->Map->GetURLFor('Calendar', 'Calendar', $param);
						$tpl->SetVariable('desc', (strlen(strip_tags($page['calendarparentdescription'])) > 247 ? substr(strip_tags($page['calendarparentdescription']),0,247)."<a href=\"".$link."\">... Read More</a>" : strip_tags($page['calendarparentdescription'])));
					} else {
						$base_url = $GLOBALS['app']->GetSiteURL().'/';
						$link = $base_url."index.php?gadget=Calendar&action=EmbedCalendar&id=".$page['id']."&mode=month";
						$tpl->SetVariable('desc', (strlen(strip_tags($page['calendarparentdescription'])) > 247 ? substr(strip_tags($page['calendarparentdescription']),0,247)."<a href=\"".$link."\">... Read More</a>" : strip_tags($page['calendarparentdescription'])));
					}
					$tpl->SetVariable('link',  $link);
					$tpl->ParseBlock('index/item');
				} else {
					if ($page['active'] == 'Y' || $embedded == true) {
						$tpl->SetBlock('index/item');
						$tpl->SetVariable('title', $page['calendarparentcategory_name']);
						$tpl->SetVariable('update_string',  _t('CALENDAR_LAST_UPDATE') . ': ');
						$tpl->SetVariable('updated', $date->Format($page['calendarparentupdated']));
						if ($embedded == false) {
							$param = array('id' => $page['calendarparentid']);
							$link = $GLOBALS['app']->Map->GetURLFor('Calendar', 'Calendar', $param);
							$tpl->SetVariable('desc', (strlen(strip_tags($page['calendarparentdescription'])) > 247 ? substr(strip_tags($page['calendarparentdescription']),0,247)."<a href=\"".$link."\">... Read More</a>" : strip_tags($page['calendarparentdescription'])));
						} else {
							$base_url = $GLOBALS['app']->GetSiteURL().'/';
							$link = $base_url."index.php?gadget=Calendar&action=EmbedCalendar&id=".$page['calendarparentid']."&mode=month";
							$tpl->SetVariable('desc', (strlen(strip_tags($page['calendarparentdescription'])) > 247 ? substr(strip_tags($page['calendarparentdescription']),0,247)."<a href=\"".$link."\">... Read More</a>" : strip_tags($page['calendarparentdescription'])));
						}
						$tpl->SetVariable('link',  $link);
						$tpl->ParseBlock('index/item');
					}
				}
			}
		}
		if ($embedded == true && !is_null($referer) && isset($embed_id)) {	
			$tpl->SetBlock('index/embedded');
			$tpl->SetVariable('id', $display_id);		        
			if (strtolower($referer) == strtolower($_SERVER['SERVER_NAME'])) {
				$tpl->SetVariable('referer', $GLOBALS['app']->GetJawsURL() . "/libraries/iframes/domain1/iframetest_resize1.html");		        
			} else {	
				$tpl->SetVariable('referer', "http://".$referer."/19/img/agents/19/custom_img/common_files/iframetest_resize1.html");		        
			}
			$tpl->ParseBlock('index/embedded');
		} else {
			$tpl->SetBlock('index/not_embedded');
			$tpl->SetVariable('id', $display_id);		        
			$tpl->ParseBlock('index/not_embedded');
		}
        $tpl->ParseBlock('index');

        return $tpl->Get();
    }
	
    /**
     * Displays an XML file with the requested calendars events
     *
     * @access public
     * @return string
     */
    function CalendarXML()
    {
		require_once JAWS_PATH . 'include/Jaws/Image.php';
        
		header("Content-type: text/xml");
		$output_xml = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>\n"; 
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');

		$request =& Jaws_Request::getInstance();
        $get = $request->get(array('id', 'showcase_id'), 'get');

        //$post['showcase_id'] = $xss->defilter($post['showcase_id']);

		//if(!empty($post['showcase_id'])) {
		//	$agentID = $post['showcase_id'];
		//}
		  
		if(!empty($get['id'])) {
			$gid = $get['id'];

	        $model = $GLOBALS['app']->LoadGadget('Calendar', 'Model');
			$galleryPosts = $model->GetAllEventsOfCalendar($gid);
			
			if (!$galleryPosts) {
				if (isset($GLOBALS['log'])) {
					$GLOBALS['log']->Log(JAWS_LOG_ERR, "No locations were found for calendar ID: $gid");
				}
			} else {
				$output_xml .= "<markers>\n";
				foreach($galleryPosts as $parents) {		            
					if (isset($parents['address']) || isset($parents['city']) || isset($parents['region']) || isset($parents['country_id'])) {
						// build address
						$marker_address = isset($parents['address']) ? $parents['address'] : '';
						$marker_address .= isset($parents['city']) ? " ".$parents['city'] : '';
						$marker_address .= isset($parents['region']) ? ", ".$parents['region'] : '';
						$info_address = isset($parents['address']) ? $parents['address'] : '';
						$info_address .= '<br />'.isset($parents['city']) ? " ".$parents['city'] : '';
						$info_address .= isset($parents['region']) ? ", ".$parents['region'] : '';
						// TODO: map country names to country_id if no other address info was supplied
						if (isset($parents['description'])) {
							$description = $this->ParseText($parents['description'], 'Calendar');
						} else {
							$description = '';
						}
						$main_image_src = '';
						if (!empty($parents['image']) && isset($parents['image'])) {
							if (strpos($parents['image'],".swf") !== false) {
								// Flash file not supported
							} else if (substr($parents['image'],0,7) == "GADGET:") {
								$main_image_src = $xss->filter(strip_tags($parents['image']));
							} else {
								$main_image_src = $xss->filter(strip_tags($parents['image']));
								if (substr(strtolower($main_image_src), 0, 4) == "http") {
									if (substr(strtolower($main_image_src), 0, 7) == "http://") {
										$main_image_src = explode('http://', $main_image_src);
										foreach ($main_image_src as $img_src) {
											if (!empty($img_src)) {
												$main_image_src = 'http://'.$img_src;
												break;
											}
										}
									} else {
										$main_image_src = explode('https://', $main_image_src);
										foreach ($main_image_src as $img_src) {
											if (!empty($img_src)) {
												$main_image_src = 'https://'.$img_src;
												break;
											}
										}
									}
								} else {
									$thumb = Jaws_Image::GetThumbPath($main_image_src);
									$medium = Jaws_Image::GetMediumPath($main_image_src);
									if (file_exists(JAWS_DATA . 'files'.$thumb)) {
										$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$thumb;
									} else if (file_exists(JAWS_DATA . 'files'.$medium)) {
										$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$medium;
									} else if (file_exists(JAWS_DATA . 'files'.$main_image_src)) {
										$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$main_image_src;
									}
								}
							}
						}
						if (!empty($main_image_src)) {
							$image_exists = "<img border=\"0\" src=\"".$main_image_src."\" width=\"150\" />";
							$image_style = "";
						} else {
							$image_exists = "";
							$image_style = "display: none; ";
						}
						$marker_html = "<div style=\"".$image_style."clear: left;\">".$image_exists."</div>";
						$marker_html .= "<div style=\"clear: left;\"><b>".(isset($parents['title']) ? $parents['title'] : 'My Location')."</b><br />".$info_address."<hr>".$description."</div>";
						$marker_html .= "<div style=\"clear: both;\">&nbsp;</div>";
						$output_xml .=  "	<marker address=\"".$marker_address."\" title=\"".(isset($parents['title']) ? $parents['title'] : 'My Location')."\" fs=\"".(isset($parents['marker_font_size']) ? $parents['marker_font_size'] : '10')."\" sfs=\"".(isset($parents['marker_subfont_size']) ? $parents['marker_subfont_size'] : '6')."\" bw=\"".(isset($parents['marker_border_width']) ? $parents['marker_border_width'] : '2')."\" ra=\"".(isset($parents['marker_radius']) ? $parents['marker_radius'] : '9')."\" fc=\"".(isset($parents['marker_font_color']) ? $parents['marker_font_color'] : 'FFFFFF')."\" fg=\"".(isset($parents['marker_foreground']) ? $parents['marker_foreground'] : '666666')."\" bc=\"".(isset($parents['marker_border_color']) ? $parents['marker_border_color'] : 'FFFFFF')."\" hfc=\"".(isset($parents['marker_hover_font_color']) ? $parents['marker_hover_font_color'] : '222222')."\" hfg=\"".(isset($parents['marker_hover_foreground']) ? $parents['marker_hover_foreground'] : 'FFFFFF')."\" hbc=\"".(isset($parents['marker_hover_border_color']) ? $parents['marker_hover_border_color'] : '666666')."\"><![CDATA[ ".$marker_html." ]]></marker>\n";
					}
				}
				$output_xml .= "</markers>\n";
				// reset xml output if no addresses were added
				/*
				if (!strpos($output_xml, "marker address")) {
					$output_xml = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>\n";
				}
				*/
			} 
			
		} else {
			if (isset($GLOBALS['log'])) {
				$GLOBALS['log']->Log(JAWS_LOG_ERR, "No events were found for calendar ID: $gid");
			}
		}
		return $output_xml;
	}

	/**
     * Returns events of date range in JSON format
     *
     * @access  public
     * @params  integer  $cid	ID of the calendar to get events for
     * @params  string  $startdate	Starting date
     * @params  string  $enddate	Ending date
     * @return  string	JSON formatted array of events on success or nothing on error   
     * @TODO 	Provide API to all gadgets (via hooks/Calendar.php).
     */
    function JSONCalendarEventsByDateRange(
		$cid = null, $all_properties = 'false', $all_availability = 'false', $start = '1970-01-01', $end = '2038-01-01', 
		$OwnerID = null, $searchgroup = null
	) {
		header('Content-type: text/plain'); 
		header('Content-Transfer-Encoding: binary'); 
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		$request =& Jaws_Request::getInstance();
        $get = $request->get(array('cid', 'start', 'end', 'all_properties', 'all_availability', 'owner_id', 'Calendar_group'), 'get');
		$model = $GLOBALS['app']->LoadGadget('Calendar', 'Model');
		$result = '';
        /*
		$get = array();
		$get['all_properties'] = $all_properties;
		$get['all_availability'] = $all_availability;
		$get['cid'] = $cid;
		$get['start'] = $start;
		$get['end'] = $end;
		*/
		$OwnerID = (!empty($get['owner_id']) ? (int)$get['owner_id'] : null);
		if (is_null($cid)) {
			$cid = (int)$get['cid'];
		}
		$searchgroup = (!empty($get['Calendar_group']) ? (int)$get['Calendar_group'] : null);
		if (isset($get['start']) && !empty($get['start']) && isset($get['end']) && !empty($get['end'])) {
			$startdate = $GLOBALS['db']->Date($GLOBALS['app']->UserTime2UTC($get['start']));
			$enddate = $GLOBALS['db']->Date($GLOBALS['app']->UserTime2UTC($get['end']));
			if (
				(isset($get['all_properties']) && $get['all_properties'] == 'true') || 
				(isset($get['all_availability']) && $get['all_availability'] == 'true') || 
				!is_null($cid)
			) {
				if (!is_null($searchgroup)) {
					$events = $model->GetAllEventsOfGroupByDateRange((int)$searchgroup, $startdate, $enddate);
				} else if (isset($get['all_properties']) && $get['all_properties'] == 'true') {
					$hook = $GLOBALS['app']->loadHook('Properties', 'Calendar');
					if ($hook !== false) {
						if (method_exists($hook, 'GetAllEventsByDateRange')) {
							$events = $hook->GetAllEventsByDateRange(
								array(
									'startdate' => $startdate, 
									'enddate' => $enddate, 
									'uid' => $OwnerID, 
									'type' => 'availability'
								)
							);
						}
					}
				} else if (isset($get['all_availability']) && $get['all_availability'] == 'true') {
					$events = $model->GetAllAvailabilityByDateRange($startdate, $enddate, $OwnerID);
				} else if ($cid == 0) {
					$events = $model->GetAllEventsByDateRange($startdate, $enddate);
				} else {
					$events = $model->GetAllEventsOfCalendarByDateRange($cid, $startdate, $enddate);
				}
			} else {
				$events = $model->GetAllEventsByDateRange($startdate, $enddate);
			}
			foreach($events as $event) {
				$itime = (!empty($event['itime']) ? $GLOBALS['app']->UTC2UserTime($event['itime'],"H:i:s") : '');
				$etime = (!empty($event['endtime']) ? $GLOBALS['app']->UTC2UserTime($event['endtime'],"H:i:s") : '');
				$isTentative = '';
				if ($event['event'] == 'Tentative') {
					$isTentative = 'true';
				}
				$allDay = 'true';
				if (!empty($itime) && !empty($etime)) {
					$allDay = 'false';
				}
				//$clickURL = $GLOBALS['app']->Map->GetURLFor('Calendar', 'Detail', array('id' => $event['id']));
				$clickURL = $GLOBALS['app']->GetSiteURL() .'/index.php?gadget=Users&action=ShowRawComments&fusegadget=Calendar&id='.$event['id'].'&h=false&p=true&i=false';
				$deleteBtn = '';
				if (($GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Calendar', 'OwnEvent') && $event['ownerid'] == $GLOBALS['app']->Session->GetAttribute('user_id')) || $GLOBALS['app']->Session->GetPermission('Calendar', 'default')) {
					//$clickURL = $GLOBALS['app']->GetSiteURL() ."/".($GLOBALS['app']->Session->GetPermission('Calendar', 'default') ? "admin" : "index").".php?gadget=Calendar&action=".($GLOBALS['app']->Session->GetPermission('Calendar', 'default') ? "" : "account_")."A_form&startdate=".urlencode($GLOBALS['app']->UTC2UserTime($event['startdate'],"m/d/Y"))."&id=".$event['id']."&linkid=".($cid > 0 ? $cid : '');
					$deleteBtn = '<a href="javascript:void(0);" onclick="deleteEvent('.$event['id'].');" style="width: 17px; float: right; display: block;"><img border="0" style="margin-right: 4px; cursor: hand; cursor: pointer;" align="right" class="syntacts-img-button" title="Delete" height="13" width="13" src="images/ICON_delete2.gif"></a>';
				}
				if (!empty($result)) {
					$result .= ','."\n";
				}
				$result .= '{"id":'.$event['id'].',"title":'.$GLOBALS['app']->UTF8->json_encode($event['event']).',"start":"'.$GLOBALS['app']->UTC2UserTime($event['startdate'],"Y-m-d").(!empty($itime) ? 'T'.$itime : '').'","end":"'.$GLOBALS['app']->UTC2UserTime($event['enddate'],"Y-m-d").(!empty($etime) ? 'T'.$etime : '').'","clickUrl":'.$GLOBALS['app']->UTF8->json_encode($clickURL).',"deleteBtn":'.$GLOBALS['app']->UTF8->json_encode($deleteBtn).',"allDay":'.$allDay.(!empty($isTentative) ? ',"isTentative":'.$isTentative : '').'}';
			}
		}
		$result = '['.$result.']';
		echo $result;
    }

    /**
     * Show calendar.
     *
     * @access 	public
     * @return 	string
     */
    function Display()
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$GLOBALS['app']->loadClass('Registry', 'Jaws_Registry');			
		$GLOBALS['app']->loadClass('Translate', 'Jaws_Translate');			
		$GLOBALS['app']->Registry->LoadFile('Calendar');
		$GLOBALS['app']->Translate->LoadTranslation('Calendar', JAWS_GADGET);
        $model = $GLOBALS['app']->LoadGadget('Calendar', 'Model');
		// send calendarParent records
		$request =& Jaws_Request::getInstance();
		$fetch = array('cid', 'layoutAction', 'all_properties', 'all_availability', 'owner_id', 'action', 'startdate', 'Calendar_group', 'mode', 'css');
		$get  = $request->get($fetch, 'get');
		$post  = $request->get($fetch, 'post');

		$cid = 0;
		if (isset($post['cid']) && !empty($post['cid']) && is_numeric($post['cid'])) {
			$cid = (int)$post['cid'];
		} else if (isset($get['cid']) && !empty($get['cid']) && is_numeric($get['cid'])) {
			$cid = (int)$get['cid'];
		}
		
		$startdate = '';
		if (isset($post['startdate']) && !empty($post['startdate'])) {
			$startdate = $post['startdate'];
		} else if (isset($get['startdate']) && !empty($get['startdate'])) {
			$startdate = $get['startdate'];
		}
		if (empty($startdate)) {
			$startdate  = $GLOBALS['app']->UTC2UserTime('', "m/d/Y");
		} else {
			$startdate = urldecode($startdate);
		}
		
		$OwnerID = null;
		if (isset($post['owner_id']) && !empty($post['owner_id'])) {
			$OwnerID = (int)$post['owner_id'];
		} else if (isset($get['owner_id']) && !empty($get['owner_id'])) {
			$OwnerID = (int)$get['owner_id'];
		}
		
		$searchgroup = null;
		if (isset($post['Calendar_group']) && !empty($post['Calendar_group'])) {
			$searchgroup = (int)$post['Calendar_group'];
		} else if (isset($get['Calendar_group']) && !empty($get['Calendar_group'])) {
			$searchgroup = (int)$get['Calendar_group'];
		}
		
		$modes = array('month', 'mini', 'year', 'week', 'day', 'list');
		$layoutAction = 'Display';
		if (isset($post['layoutAction']) && !empty($post['layoutAction']) && in_array(str_replace('layout', '', strtolower($post['layoutAction'])), $modes)) {
			$layoutAction = $post['layoutAction'];
		} else if (isset($get['layoutAction']) && !empty($get['layoutAction']) && in_array(str_replace('layout', '', strtolower($get['layoutAction'])), $modes)) {
			$layoutAction = $get['layoutAction'];
		}
		
		$all_properties = false;
		if (isset($post['all_properties']) && $post['all_properties'] == 'true') {
			$all_properties = true;
		} else if (isset($get['all_properties']) && $get['all_properties'] == 'true') {
			$all_properties = true;
		}
		
		$all_availability = false;
		if (isset($post['all_availability']) && $post['all_availability'] == 'true') {
			$all_availability = true;
		} else if (isset($get['all_availability']) && $get['all_availability'] == 'true') {
			$all_availability = true;
		}
		
		if ($all_properties === false && $all_availability === false && $cid > 0) {
			//if on a users home page, show their stuff
			if (strtolower($get['gadget']) == 'users' && !empty($get['id'])) {
				$calendarParent = $model->GetSingleCalendarByUserID($get['id'], $cid);
			} else {
				$calendarParent = $model->GetCalendar($cid);
			}
		}
		
		require_once JAWS_PATH . 'include/Jaws/Template.php';
		$tpl = new Jaws_Template('gadgets/Calendar/templates/');
		$tpl->Load('FullCalendar.html');

		$tpl->SetBlock('fullcalendar');
        
		$tpl->SetVariable('css', $get['css']);
		$tpl->SetVariable('CALENDAR_CATEGORY_CONFIRM_DELETE', _t('CALENDAR_CATEGORY_CONFIRM_DELETE'));
		$tpl->SetVariable('CALENDAR_EVENT_CONFIRM_DELETE', _t('CALENDAR_EVENT_CONFIRM_DELETE'));
		
		$theme = $GLOBALS['app']->Registry->Get('/config/theme');
		$themeHREF = (strpos($theme, 'http://') !== false ? $theme : $GLOBALS['app']->getDataURL('', true) . "themes/" . $theme);
		$tpl->SetVariable('themeHREF', $themeHREF);
		
		$jaws_url = $GLOBALS['app']->GetJawsURL();
		$site_url = $GLOBALS['app']->GetSiteURL();
		$tpl->SetVariable('JAWS_URL', $jaws_url);
		$tpl->SetVariable('site_url', $site_url);
		
		$tpl->SetVariable('cid', $cid);
		//if (!empty($startdate)) {
			//$tpl->SetBlock('fullcalendar/calendar_date');
			//$tpl->SetVariable('startdate', $startdate);
			$tpl->SetVariable('startdate', $startdate);
			//$tpl->SetVariable('cid', $cid);
			//$tpl->ParseBlock('fullcalendar/calendar_date');
		//}
		
		$json_events_url = $GLOBALS['app']->GetSiteURL()."/index.php?gadget=Calendar&action=JSONCalendarEventsByDateRange&cid=".$cid."&all_properties=".($all_properties === true ? 'true' : 'false')."&all_availability=".($all_availability === true ? 'true' : 'false').(!is_null($OwnerID) ? "&owner_id=".$OwnerID : '').(!is_null($searchgroup) ? "&Calendar_group=".$searchgroup : ''); 								
		$tpl->SetVariable('json_events_url', $json_events_url);
				
		if (isset($calendarParent['calendarparentid'])) {
			$tpl->SetVariable('actionName', $layoutAction . '_' . $calendarParent['calendarparentid'] . '_');
			$modeLink = str_replace('Layout', '', $layoutAction);
			//$tpl->SetVariable('link', "index.php?gadget=Calendar&action=Calendar&id=".$cid."&mode=".strtolower($modeLink));
			$tpl->SetVariable('layout_title', ($calendarParent['calendarparentownerid'] == 0 ? '<a href="index.php?gadget=Calendar&action=Calendar&id='.$cid.'&mode='.strtolower($modeLink).'" target="_top">' : '').$xss->filter($calendarParent['calendarparentcategory_name']).($calendarParent['calendarparentownerid'] == 0 ? '</a>' : ''));
		} else {
			$layoutTitle = ($all_properties === true ? "All Properties" : ($all_availability === true ? "All Availability" : "All Events"));
			$tpl->SetVariable('actionName', $layoutAction . '_All_');
			$tpl->SetVariable('layout_title', $layoutTitle);
		}
		
		if ($cid == 0 || (isset($calendarParent['calendarparenttype']) && $calendarParent['calendarparenttype'] == 'E')) {
			if (
				(isset($calendarParent['calendarparenttype']) && $calendarParent['calendarparenttype'] == 'A') || 
				$all_properties === true || $all_availability === true
			) {
				$hook = $GLOBALS['app']->loadHook('Properties', 'Calendar');
				if ($hook !== false) {
					if (method_exists($hook, 'GetCalendarReference')) {
						$page = $hook->GetCalendarReference($calendarParent['calendarparentpropid']);
					}
				}
				$tpl->SetBlock('fullcalendar/simple_header');
				$tpl->ParseBlock('fullcalendar/simple_header');
			} else {
				$tpl->SetBlock('fullcalendar/advanced_header');
				$tpl->ParseBlock('fullcalendar/advanced_header');
			}
		} else {
			$tpl->SetBlock('fullcalendar/simple_header');
			$tpl->ParseBlock('fullcalendar/simple_header');
		}
		
		$tpl->SetBlock('fullcalendar/eventClick');
		$tpl->ParseBlock('fullcalendar/eventClick');
		
		$gadget_reservation_form = '';
		$event_tentative = '';
		$event_select = '';

		// If we're logged in or this is a property calendar, make events selectable and the calendar editable
		if (
			((($GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), 
			$GLOBALS['app']->Session->GetAttribute('groups'), 'Calendar', 'OwnEvent') && 
			(isset($calendarParent['calendarparentownerid']) && 
			$calendarParent['calendarparentownerid'] == $GLOBALS['app']->Session->GetAttribute('user_id'))) || 
			$GLOBALS['app']->Session->GetPermission('Calendar', 'default')) && 
			$all_properties === false && $all_availability === false && $cid > 0) || 
			((isset($calendarParent['calendarparentpropid']) && $calendarParent['calendarparentpropid'] > 0) || 
			(isset($calendarParent['calendarparenttype']) && $calendarParent['calendarparenttype'] == 'A') || 
			$all_properties === true || $all_availability === true)
		) {
			$tpl->SetBlock('fullcalendar/event_select');
			$tpl->SetVariable('cid', $cid);
			if (
				((($GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), 
				$GLOBALS['app']->Session->GetAttribute('groups'), 'Calendar', 'OwnEvent') && 
				(isset($calendarParent['calendarparentownerid']) && 
				$calendarParent['calendarparentownerid'] == $GLOBALS['app']->Session->GetAttribute('user_id'))) || 
				$GLOBALS['app']->Session->GetPermission('Calendar', 'default')) && 
				$all_properties === false && $all_availability === false && $cid > 0) 
			) {
				$tpl->SetBlock('fullcalendar/event_select/event');
				$tpl->ParseBlock('fullcalendar/event_select/event');
				$tpl->ParseBlock('fullcalendar/event_click');
			} else {
				$tpl->SetBlock('fullcalendar/event_select/reservation');
				$tpl->SetVariable('cid', $cid);
				$tpl->ParseBlock('fullcalendar/event_select/reservation');
				if (
					($GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), 
					$GLOBALS['app']->Session->GetAttribute('groups'), 'Calendar', 'OwnEvent') && 
					(isset($calendarParent['calendarparentownerid']) && 
					$calendarParent['calendarparentownerid'] == $GLOBALS['app']->Session->GetAttribute('user_id'))) || 
					$GLOBALS['app']->Session->GetPermission('Calendar', 'default') && $cid > 0
				) {
					$tpl->SetBlock('fullcalendar/event_select/admin_reservation');
					$tpl->ParseBlock('fullcalendar/event_select/admin_reservation');
					$tpl->ParseBlock('fullcalendar/event_click');
				} else {
					$tpl->SetBlock('fullcalendar/event_select/public_reservation_start');
					$tpl->ParseBlock('fullcalendar/event_select/public_reservation_start');
					$tpl->SetBlock('fullcalendar/event_select/public_reservation_end');
					$tpl->ParseBlock('fullcalendar/event_select/public_reservation_end');
					$tpl->ParseBlock('fullcalendar/event_click');
					$tpl->SetBlock('fullcalendar/event_tentative');
					$tpl->ParseBlock('fullcalendar/event_tentative');
					
					// Gadget Inquiry Form
					if (!Jaws_Error::isError($page) && isset($page['id']) && !empty($page['id'])) {
						$hook = $GLOBALS['app']->loadHook('Properties', 'Calendar');
						if ($hook !== false) {
							if (method_exists($hook, 'GetCalendarInquiryForm')) {
								$gadget_reservation_form = $hook->GetCalendarInquiryForm(
									array(
										'calendarid' => $cid, 
										'gadget_reference' => $page['id'], 
										'all_availability' => $all_availability
									)
								);
								if (!Jaws_Error::isError($gadget_reservation_form) && !empty($gadget_reservation_form)) {
									$tpl->SetBlock('fullcalendar/gadget_reservation_form');
									$tpl->SetVariable('gadget_reservation_form', $gadget_reservation_form);
									$tpl->ParseBlock('fullcalendar/gadget_reservation_form');
								}
							}
						}
					}
					
					$tpl->SetBlock('fullcalendar/calendar_text');
					if (!is_null($OwnerID) && (int)$OwnerID > 0) {
						$tpl->SetBlock('fullcalendar/calendar_text/link');
						$tpl->SetVariable('site_url', $site_url);
						$tpl->ParseBlock('fullcalendar/calendar_text/link');
					}
					$tpl->ParseBlock('fullcalendar/calendar_text');
				}
			}
			
		}
				
		if (
			($GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), 
				$GLOBALS['app']->Session->GetAttribute('groups'), 'Calendar', 'OwnEvent') && 
			$calendarParent['calendarparentownerid'] == $GLOBALS['app']->Session->GetAttribute('user_id')) || 
			$GLOBALS['app']->Session->GetPermission('Calendar', 'default') && $all_properties === false
		) {
			$tpl->SetBlock('fullcalendar/eventDrop');
			$tpl->ParseBlock('fullcalendar/eventDrop');
			$tpl->SetBlock('fullcalendar/eventResize');
			$tpl->ParseBlock('fullcalendar/eventResize');
			$tpl->SetBlock('fullcalendar/eventRender');
			$tpl->ParseBlock('fullcalendar/eventRender');
		}
		
		if ($cid == 0 || (isset($calendarParent['calendarparenttype']) && $calendarParent['calendarparenttype'] == 'E')) {
			$tpl->SetBlock('fullcalendar/list_view');
			$tpl->SetVariable('cid', $cid);
			//$calendarLayout = $GLOBALS['app']->LoadGadget('Calendar', 'LayoutHTML');
			//$list_html = $calendarLayout->ShowComments(($cid == 0 ? null : $calendarParent['calendarparentid']), true, true, 2, false, false, 10);
			$list_html = '';
			$tpl->SetVariable('list_html', $list_html);
			$tpl->ParseBlock('fullcalendar/list_view');
			$tpl->SetBlock('fullcalendar/list_view_script');
			$tpl->SetVariable('cid', $cid);
			switch ($layoutAction) {
				case "LayoutDay":
					$tpl->SetBlock('fullcalendar/list_view_script/day');
					$tpl->SetVariable('cid', $cid);
					$tpl->ParseBlock('fullcalendar/list_view_script/day');
				break;
				case "LayoutWeek":
					$tpl->SetBlock('fullcalendar/list_view_script/week');
					$tpl->SetVariable('cid', $cid);
					$tpl->ParseBlock('fullcalendar/list_view_script/week');
				break;
				case "LayoutList":
					$tpl->SetBlock('fullcalendar/list_view_script/list');
					$tpl->SetVariable('cid', $cid);
					$tpl->ParseBlock('fullcalendar/list_view_script/list');
				break;
				default:
					$tpl->SetBlock('fullcalendar/list_view_script/month');
					$tpl->SetVariable('cid', $cid);
					$tpl->ParseBlock('fullcalendar/list_view_script/month');
				break;
			}
			$tpl->ParseBlock('fullcalendar/list_view_script');
		}
		
		$tpl->ParseBlock('fullcalendar');

		return $tpl->Get();
	}
	
    /**
     * Embed calendars on external sites.
     *
     * @category 	feature
     * @access 	public
     * @return 	string
     */
    function EmbedCalendar()
    {
		$request =& Jaws_Request::getInstance();
        $get = $request->get(array('id', 'mode', 'uid', 'referer', 'css'), 'get');
 		$id = (isset($get['id']) && is_numeric($get['id']) ? (int)$get['id'] : 'all');
		$output_html = "";
		$all_properties = false;
		$all_availability = false;
		$mode = $get['mode'];
		if ($mode == 'all_properties') {
			$mode = 'year';
			$all_properties = true;
		}
		if ($mode == 'all_availability') {
			$mode = 'year';
			$all_availability = true;
		}
		if (is_null($mode) || empty($mode)) {
			$mode = 'month';
		}
		$display_id = md5($this->_Name.$id);
        //$output_html .= "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
		$output_html .= "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" style=\"background: url();\">\n";
		$output_html .= " <head>\n";
		$output_html .= "  <title>Calendar</title>\n";
		$output_html .= "  <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n";
        $theme = $GLOBALS['app']->Registry->Get('/config/theme');
		$themeHREF = (strpos($theme, 'http://') !== false ? $theme : $GLOBALS['app']->getDataURL('', true) . "themes/" . $theme);
		$output_html .= "	<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" href=\"". $themeHREF . "/style.css\" />\n";
		$output_html .= "	<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" href=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/".$this->_Name."/resources/style.css\" />\n";
		$output_html .= "	<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" href=\"". $GLOBALS['app']->getDataURL('', true) . "files/css/custom.css\" />\n";
		if (isset($get['css']) && !empty($get['css'])) {
			$output_html .= "	<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" href=\"".$get['css']."\" />\n";
		}
		//$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/prototype/prototype.js\"></script>\n";
		//$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/prototype/scriptaculous.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/prototype/prototype.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/prototype/scriptaculous.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/prototype/effects.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/prototype/controls.js\"></script>\n";
		//$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/include/Jaws/Ajax/Response.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetSiteURL() . "/index.php?gadget=".$this->_Name."&amp;action=Ajax&amp;client=all&amp;stub=CalendarAjax\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetSiteURL() . "/index.php?gadget=".$this->_Name."&amp;action=AjaxCommonFiles\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/".$this->_Name."/resources/client_script.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/js/admin.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"http://yui.yahooapis.com/2.4.0/build/yahoo-dom-event/yahoo-dom-event.js\"></script>\n";
		if (isset($get['mode']) && $get['mode'] == 'reservation') {
			$output_html .= "	<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" href=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/piwi/piwidata/css/calendar-blue.css\" />\n";
			$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/piwi/piwidata/js/jscalendar/calendar.js\"></script>\n";
			$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/piwi/piwidata/js/jscalendar/calendar-setup.js\"></script>\n";
			$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/piwi/piwidata/js/jscalendar/lang/calendar-en.js\"></script>\n";
		}
		$output_html .= " </head>\n";
		$output_html .= " <body style=\"background: url();\" onLoad=\"sizeFrame".$display_id."(); document.getElementById('".$this->_Name."-editDivStretch-".$display_id."').style.width = (parseInt(document.getElementById('".$this->_Name."-editDiv-".$display_id."').offsetWidth)-20)+'px';\">\n";
		if (isset($id) && (isset($get['referer']) || $GLOBALS['app']->Session->GetAttribute('gadget_referer'))) {
			$layoutGadget = $GLOBALS['app']->LoadGadget('Calendar', 'LayoutHTML');
			$referer = (isset($get['referer']) ? $get['referer'] : $GLOBALS['app']->Session->GetAttribute('gadget_referer'));
			$output_html .= " <style>\n";
			$output_html .= "   #".$this->_Name."-editDiv-".$display_id." { width: 100%; text-align: right; }\n";
			$output_html .= "   #".$this->_Name."-edit-".$display_id." { display: block; width:20px; height:20px; overflow:hidden; }\n";
			$output_html .= "   #".$this->_Name."-edit-".$display_id.":hover { width: 118px; }\n";
			$output_html .= " </style>\n";
			if ($get['mode'] == 'list') {
				$output_html .= " <div id=\"".$this->_Name."-editDiv-".$display_id."\"><div id=\"".$this->_Name."-editDivStretch-".$display_id."\" style=\"float: left;\">&nbsp;</div><div style=\"float: left;\"><a onMouseOver=\"document.getElementById('".$this->_Name."-editDivStretch-".$display_id."').style.width = (parseInt(document.getElementById('".$this->_Name."-editDiv-".$display_id."').offsetWidth)-118)+'px';\" onMouseOut=\"document.getElementById('".$this->_Name."-editDivStretch-".$display_id."').style.width = (parseInt(document.getElementById('".$this->_Name."-editDiv-".$display_id."').offsetWidth)-20)+'px';\" id=\"".$this->_Name."-edit-".$display_id."\" target=\"_blank\" href=\"".$GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction')."\" title=\"Edit This Gadget\"><img border=\"0\" src=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/Users/images/edit_gadget.png\" /></a></div><div style=\"font-size: 0.1em; clear: both;\">&nbsp;</div></div>";
				if (!empty($get['uid'])) {
					$output_html .= $this->Index((int)$get['uid'], true, $referer);
				} else {
					$output_html .= $this->Index(null, true, $referer);
				}
				$output_html .= "<div style=\"text-align: center; padding: 15px;\"><a target=\"_blank\" href=\"".$GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction')."\">Edit This Gadget</a></div>";
			} else if ($get['mode'] == 'reservation') {
				$output_html .= " <div id=\"".$this->_Name."-editDiv-".$display_id."\"><div id=\"".$this->_Name."-editDivStretch-".$display_id."\" style=\"float: left;\">&nbsp;</div><div style=\"float: left;\"><a onMouseOver=\"document.getElementById('".$this->_Name."-editDivStretch-".$display_id."').style.width = (parseInt(document.getElementById('".$this->_Name."-editDiv-".$display_id."').offsetWidth)-118)+'px';\" onMouseOut=\"document.getElementById('".$this->_Name."-editDivStretch-".$display_id."').style.width = (parseInt(document.getElementById('".$this->_Name."-editDiv-".$display_id."').offsetWidth)-20)+'px';\" id=\"".$this->_Name."-edit-".$display_id."\" target=\"_blank\" href=\"".$GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction')."\" title=\"Edit This Gadget\"><img border=\"0\" src=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/Users/images/edit_gadget.png\" /></a></div><div style=\"font-size: 0.1em; clear: both;\">&nbsp;</div></div>";
				if (!empty($get['uid'])) {
					$output_html .= $layoutGadget->ReservationForm($id, true, $referer, (int)$get['uid']);
				} else {
					$output_html .= $layoutGadget->ReservationForm($id, true, $referer);
				}
			} else {
				$output_html .= " <div id=\"".$this->_Name."-editDiv-".$display_id."\"><div id=\"".$this->_Name."-editDivStretch-".$display_id."\" style=\"float: left;\">&nbsp;</div><div style=\"float: left;\"><a onMouseOver=\"document.getElementById('".$this->_Name."-editDivStretch-".$display_id."').style.width = (parseInt(document.getElementById('".$this->_Name."-editDiv-".$display_id."').offsetWidth)-118)+'px';\" onMouseOut=\"document.getElementById('".$this->_Name."-editDivStretch-".$display_id."').style.width = (parseInt(document.getElementById('".$this->_Name."-editDiv-".$display_id."').offsetWidth)-20)+'px';\" id=\"".$this->_Name."-edit-".$display_id."\" target=\"_blank\" href=\"".$GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction')."\" title=\"Edit This Gadget\"><img border=\"0\" src=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/Users/images/edit_gadget.png\" /></a></div><div style=\"font-size: 0.1em; clear: both;\">&nbsp;</div></div>";
				if (!empty($get['uid'])) {
					$output_html .= $this->Calendar($id, $mode, true, $referer, $all_properties, $all_availability, (int)$get['uid']);
				} else {
					$output_html .= $this->Calendar($id, $mode, true, $referer, $all_properties, $all_availability);
				}
			}
		}
		$output_html .= " </body>\n";
		$output_html .= "</html>\n";
		return $output_html;
    }

	/**
     * Displays user account controls.
     *
     * @param array  $info  user information
     * @access public
     * @return string
     */
    function GetUserAccountControls($info, $groups)
    {
        if (!isset($info['id'])) {
			return new Jaws_Error(_t('GLOBAL_ERROR_GET_ACCOUNT_PANE'), $this->_Name);
		}
		//require_once JAWS_PATH . 'include/Jaws/User.php';
        //$jUser = new Jaws_User;
        //$info  = $jUser->GetUserInfoById((int)$GLOBALS['app']->Session->GetAttribute('user_id'));
		$userModel  = $GLOBALS['app']->LoadGadget('Users', 'Model');
		$pane_groups = array();

		/*
		$pane_status = $userModel->GetGadgetPaneInfoByUserID($this->_Name, $info['id']);
		if (!Jaws_Error::IsError($pane_status) && isset($pane_status['status'])) {
		*/
			//Construct panes for each available pane_method
			$panes = $this->GetUserAccountPanesInfo($groups);
			foreach ($panes as $pane) {
				$pane_groups[] = array(
					'id' => $pane['id'],
					'icon' => $pane['icon'],
					'name' => $pane['name'],
					'method' => $pane['method'],
					'params' => array()
				);
			}
		/*
		} else if (Jaws_Error::IsError($pane_status)) {
			return new Jaws_Error(_t('GLOBAL_ERROR_GET_ACCOUNT_PANE'), $this->_Name);
		}
		*/
		return $pane_groups;
    }

     /*
     * Define array of panes for this gadget's account controls.
     * (i.e. Store gadget has "My Products" and "Saved Products" panes) 
     * 
     * $panes array structured as follows:
     * 'AdminHTML->MethodName' => 'Pane Title'
     * 
     * @access public
     * @return array of pane names
     */
    function GetUserAccountPanesInfo($groups = array())
    {		
		$panes = array();
		foreach ($groups as $group) {
			if (
				isset($group['group_name']) && 
				($group['group_name'] == strtolower($this->_Name).'_owners' || $group['group_name'] == strtolower($this->_Name).'_users') && 
				($group['group_status'] == 'active' || $group['group_status'] == 'founder' || $group['group_status'] == 'admin')
			) {
				// FIXME: Add translation string for this
				$panes[] = array(
					'name' => $this->_Name,
					'id' => str_replace(" ",'',$this->_Name),
					'method' => 'User'.ucfirst(str_replace('_','',str_replace(array('_owners','_users'),'',$group['group_name']))),
					'icon' => $GLOBALS['app']->GetJawsURL() . '/gadgets/'.$this->_Name.'/images/logo.png'
				);
			}
		}
		return $panes;
	}
	
    /**
     * Display the pane content.
     *
     * @param int  $user  user id
     * @access public
     * @return string
     */
    function UserCalendar()
    {		
		if (!$GLOBALS['app']->Session->Logged()) {
			//require_once JAWS_PATH . 'include/Jaws/Header.php';
			//Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'));
			$GLOBALS['app']->Session->PushLastResponse("User not logged in.", RESPONSE_ERROR);
			$GLOBALS['app']->Session->CheckPermission('Users', 'default');
		}
		$GLOBALS['app']->Layout->AddHeadLink('gadgets/Calendar/resources/style.css', 'stylesheet', 'text/css');
		require_once JAWS_PATH . 'include/Jaws/User.php';
        $jUser = new Jaws_User;
		
		require_once JAWS_PATH . 'include/Jaws/Template.php';
        $tpl = new Jaws_Template('gadgets/Calendar/templates/');
        $tpl->Load('users.html');
		$tpl->SetBlock('pane');
		$tpl->SetVariable('title', $this->_Name);
		$tpl->SetVariable('pane_id', str_replace(" ",'',$this->_Name));
		$tpl->SetBlock('pane/pane_item');
		$tpl->SetVariable('pane_id', str_replace(" ",'',$this->_Name));
		$tpl->SetVariable('pane', 'UserCalendar');
		$tpl->SetVariable('icon', $GLOBALS['app']->GetJawsURL() . '/gadgets/'.$this->_Name.'/images/logo.png');
		
		$stpl = new Jaws_Template('gadgets/Calendar/templates/');
		$stpl->Load('users.html');
		$stpl->SetBlock('UserCalendarSubscriptions');
		$status = $jUser->GetStatusOfUserInGroup($GLOBALS['app']->Session->GetAttribute('user_id'), 'calendar_owners');
		$usersHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		// Can add?
		$interactive = false;
		if (
			$GLOBALS['app']->Session->IsSuperAdmin() || 
			$GLOBALS['app']->ACL->GetFullPermission(
				$GLOBALS['app']->Session->GetAttribute('username'), 
				$GLOBALS['app']->Session->GetAttribute('groups'), 'Calendar', 'ManageEvents') || 
			$GLOBALS['app']->ACL->GetFullPermission(
				$GLOBALS['app']->Session->GetAttribute('username'), 
				$GLOBALS['app']->Session->GetAttribute('groups'), 'Calendar', 'ManagePublicEvents') || 
			$GLOBALS['app']->ACL->GetFullPermission(
				$GLOBALS['app']->Session->GetAttribute('username'), 
				$GLOBALS['app']->Session->GetAttribute('groups'), 'Calendar', 'default') || 
			in_array($status, array('active','admin','founder'))
		) {
			$interactive = true;
		}
		$page = $usersHTML->ShowComments('Calendar', false, null, 'Calendar', $interactive);
		if (!Jaws_Error::IsError($page)) {
			$stpl->SetVariable('element', $page);
		} else {
			$stpl->SetVariable('element', _t('GLOBAL_ERROR_GET_ACCOUNT_PANE'));
		}
		$stpl->ParseBlock('UserCalendarSubscriptions');
		
		$tpl->SetVariable('gadget_pane', $stpl->Get());
		$tpl->ParseBlock('pane/pane_item');
		$tpl->ParseBlock('pane');

        return $tpl->Get();
    }
		
    /**
     * Account Admin
     *
     * @access public
     * @return string
     */
    function account_Admin()
    {
		$calendar_admin = $GLOBALS['app']->LoadGadget('Calendar', 'AdminHTML');
		return $calendar_admin->Admin(true);
    }
	

    /**
     * Account form
     *
     * @access public
     * @return string
     */
    function account_form()
    {
		$calendar_admin = $GLOBALS['app']->LoadGadget('Calendar', 'AdminHTML');
		$page = $calendar_admin->form(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$html_output = $users_html->GetAccountHTML('Calendar');
		$html_output = str_replace("</head>", "<style type=\"text/css\">body { background: transparent none; }</style>\n</head>", $html_output);
		$output_html = str_replace("__JAWS_GADGET__", $page, $html_output);
		return $output_html;
    }

    /**
     * Account form_post
     *
     * @access public
     * @return string
     */
    function account_form_post()
    {
		$calendar_admin = $GLOBALS['app']->LoadGadget('Calendar', 'AdminHTML');
		$page = $calendar_admin->form_post(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$output_html = str_replace("__JAWS_GADGET__", $page, $users_html->GetAccountHTML('Calendar'));
		return $output_html;
    }

    /**
     * Account A
     *
     * @access public
     * @return string
     */
    function account_A()
    {
		$calendar_admin = $GLOBALS['app']->LoadGadget('Calendar', 'AdminHTML');
		$page = $calendar_admin->A(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$html_output = $users_html->GetAccountHTML('Calendar');
		$html_output = str_replace("</head>", "<style type=\"text/css\">body { background: transparent none; }</style>\n</head>", $html_output);
		$output_html = str_replace("__JAWS_GADGET__", $page, $html_output);
		return $output_html;
    }

    /**
     * Account A_form
     *
     * @access public
     * @return string
     */
    function account_A_form()
    {
		$calendar_admin = $GLOBALS['app']->LoadGadget('Calendar', 'AdminHTML');
		$page = $calendar_admin->A_form(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$html_output = $users_html->GetAccountHTML('Calendar');
		$html_output = str_replace("</head>", "<style type=\"text/css\">body { background: transparent none; }</style>\n</head>", $html_output);
		$output_html = str_replace("__JAWS_GADGET__", $page, $html_output);
		return $output_html;
    }

    /**
     * Account A_form_post
     *
     * @access public
     * @return string
     */
    function account_A_form_post()
    {
		$calendar_admin = $GLOBALS['app']->LoadGadget('Calendar', 'AdminHTML');
		$page = $calendar_admin->A_form_post(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$output_html = str_replace("__JAWS_GADGET__", $page, $users_html->GetAccountHTML('Calendar'));
		return $output_html;
    }

    /**
     * Account ShowEmbedWindow
     *
     * @access public
     * @return string
     */
    function account_ShowEmbedWindow()
    {
		$user_admin = $GLOBALS['app']->LoadGadget('Users', 'AdminHTML');
		return $user_admin->ShowEmbedWindow('Calendar', 'OwnCategory', true);
    }
	
    /**
     * Account GetQuickAddForm
     *
     * @access public
     * @return string
     */
    function account_GetQuickAddForm()
    {
		$gadget_admin = $GLOBALS['app']->LoadGadget('Calendar', 'AdminHTML');
		$page = $gadget_admin->GetQuickAddForm(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$output_html = str_replace("__JAWS_GADGET__", $page, $users_html->GetAccountHTML('Calendar'));
		return $output_html;
    }
	
    /**
     * sets GB root with DPATH
     *
     * @access public
     * @return javascript string
     */
    function account_SetGBRoot()
    {
		// Make output a real JavaScript file!
		header('Content-type: text/javascript'); 
		echo "var GB_ROOT_DIR = \"data/greybox/\";";
	}
	
    /**
     * Account Public Profile
     *
     * @access public
     * @return string
     */
    function account_profile($uid = 0)
    {
		$output_html = '';
		if($uid > 0) {
			$output_html .= $this->Calendar();
		} else {
            require_once JAWS_PATH . 'include/Jaws/Header.php';
            Jaws_Header::Location($GLOBALS['app']->GetSiteURL().'/');
		}
		
		return $output_html;
    }
}