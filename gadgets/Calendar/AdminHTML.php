<?php
/**
 * Calendar Gadget Admin
 *
 * @category   GadgetAdmin
 * @package    Calendar
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */

class CalendarAdminHTML extends Jaws_GadgetHTML
{

    /**
     * Constructor
     *
     * @access public
     */
    function CalendarAdminHTML()
    {
        $this->Init('Calendar');
    }

    /**
     * Builds the menubar
     *
     * @access       public
     * @param        string  $selected Selected action
     * @return       string  The html menubar
     */
    function MenuBar($selected)
    {
        $actions = array('Admin','A','A_form','A_form_post');
        if (!in_array($selected, $actions)) {
            $selected = 'Admin';
        } else {
            if ($selected != 'Admin') {
				$selected = 'A';
			}
		}

        //if ($selected != 'Admin') {
        //    $selected = 'Admin';
        //}

        require_once JAWS_PATH . 'include/Jaws/Widgets/Menubar.php';
        $menubar = new Jaws_Widgets_Menubar();
        if ($GLOBALS['app']->Session->GetPermission('Calendar', 'default')) {
            $menubar->AddOption('Admin', _t('CALENDAR_MENU_CALENDARS'),
                                'admin.php?gadget=Calendar&amp;action=Admin', STOCK_DOCUMENTS);
            if (strtolower($selected) != "admin") {
				$menubar->AddOption('A', _t('CALENDAR_MENU_EVENTS'),
	                                'admin.php?gadget=Calendar&amp;action=A', STOCK_CALENDAR);
			}
        }

		$request =& Jaws_Request::getInstance();
		$id = $request->get('id', 'get');
		if (strtolower($selected) == "form" && empty($id)) {
		} else {
			if ($GLOBALS['app']->Session->GetPermission('Calendar', 'default')) {
				$menubar->AddOption('Add', '',
									'admin.php?gadget=Calendar&amp;action=form', STOCK_ADD);
			}
		}
        $menubar->Activate($selected);

        return $menubar->Get();
    }

    /**
     * Builds the basic datagrid view
     *
     * @access  private
     * @return  string   XHTML of datagrid
     */
    function DataGrid()
    {
        //$model = $GLOBALS['app']->LoadGadget('Calendar', 'AdminModel');
		$OwnerID = 0;
		if (BASE_SCRIPT == 'index.php') {
			$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');
		}
        $sql = 'SELECT COUNT([calendarparentid]) FROM [[calendarparent]] WHERE [calendarparentownerid] = '.$OwnerID;
        $res = $GLOBALS['db']->queryOne($sql);
        $total = (Jaws_Error::IsError($res) ? 0 : $res);

        $grid =& Piwi::CreateWidget('DataGrid', array());
        $grid->TotalRows($total);
        $grid->SetStyle('width: 100%;');
        $grid->SetID('calendar_datagrid');
        $grid->SetAction('next', 'javascript:nextCalendarValues();');
        $grid->SetAction('prev', 'javascript:previousCalendarValues();');
        //$grid->useMultipleSelection();
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('CALENDAR_STATUS')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_TITLE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('CALENDAR_TYPE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('CALENDAR_LAST_UPDATE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_ACTIONS')));

        return $grid->Get();
    }

    /**
     * Returns an array with pages found
     *
     * @access  public
     * @param   string  $status  Status of calendar(s) we want to display
     * @param   string  $search  Keyword (title/description) of calendars we want to look for
     * @param   int     $limit   Data limit
     * @return  array   Data
     */
    function GetCalendars($status, $search, $limit, $OwnerID = 0)
    {
        $model = $GLOBALS['app']->LoadGadget('Calendar', 'AdminModel');
        $pages = $model->SearchCalendars($status, $search, $limit, $OwnerID);
        if (Jaws_Error::IsError($pages)) {
            return $pages;
        }

        $data    = array();
		if (BASE_SCRIPT != 'index.php') {
			$edit_url    = BASE_SCRIPT . '?gadget=Calendar&amp;action=A&amp;linkid=';
        } else {
			$edit_url    = 'index.php?gadget=Calendar&amp;action=account_A&amp;linkid=';
		}
		$date = $GLOBALS['app']->loadDate();
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$ACL = $GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			

        foreach ($pages as $page) {
			$pageData = array();
			if ($page['calendarparentactive'] == 'Y') {
				$pageData['status']  = _t('CALENDAR_PUBLISHED');
			} else {
				$pageData['status']  = _t('CALENDAR_NOTPUBLISHED');
			}
			$pageData['title'] = '<a href="'.$edit_url.$page['calendarparentid'].'">'.$page['calendarparentcategory_name'].'</a>';

			if ($page['calendarparenttype'] == 'E') {
				$pageData['type']  = _t('CALENDAR_TYPE_EVENTS');
			} else if ($page['calendarparenttype'] == 'A') {
				$pageData['type']  = _t('CALENDAR_TYPE_AVAILABILITY');
			} else {
				$pageData['type']  = _t('CALENDAR_TYPE_GOOGLE');
			}
			
			$pageData['date']  = $date->Format($page['calendarparentupdated']);
			
			$actions = '';
			if ($this->GetPermission('ManageCategories')) {
				if (BASE_SCRIPT != 'index.php') {
					$link =& Piwi::CreateWidget('Link', _t('GLOBAL_EDIT'),
												$edit_url.$page['calendarparentid'],
												STOCK_CALENDAR);
				} else {
					$link =& Piwi::CreateWidget('Link', _t('GLOBAL_EDIT'),
												"javascript:window.open('".$edit_url.$page['calendarparentid']."');",
												STOCK_CALENDAR);
				}
				$actions.= $link->Get().'&nbsp;';
			} else {
				if ($ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Calendar', 'OwnCategory')) {
					$link =& Piwi::CreateWidget('Link', _t('GLOBAL_EDIT'),
												"javascript:window.open('".$edit_url.$page['calendarparentid']."');",
												STOCK_CALENDAR);
					$actions.= $link->Get().'&nbsp;';
				}
			}

			if ($this->GetPermission('ManageCategories')) {
				$link =& Piwi::CreateWidget('Link', _t('GLOBAL_DELETE'),
											"javascript: if (confirm('"._t('CALENDAR_CATEGORY_CONFIRM_DELETE', _t('CALENDAR_CATEGORY'))."')) ".
											"deleteCalendar('".$page['calendarparentid']."');",
											"images/ICON_delete2.gif");
				$actions.= $link->Get().'&nbsp;';
			} else {
				if ($ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Calendar', 'OwnCategory')) {
					$link =& Piwi::CreateWidget('Link', _t('GLOBAL_DELETE'),
												"javascript: if (confirm('"._t('CALENDAR_CATEGORY_CONFIRM_DELETE', _t('CALENDAR_CATEGORY'))."')) ".
												"deleteCalendar('".$page['calendarparentid']."');",
												"images/ICON_delete2.gif");
					$actions.= $link->Get().'&nbsp;';
				}
			}
			$pageData['actions'] = $actions;
			$pageData['__KEY__'] = $page['calendarparentid'];
			$data[] = $pageData;
        }
        return $data;
    }
			
    /**
     * Display the default administration page which currently lists all pages
     *
     * @access public
     * @return string
     */
    function Admin($account = false)
    {
		$request =& Jaws_Request::getInstance();
		$action = $request->getRaw('action', 'get');
				
		if ($account === false) {
			$GLOBALS['app']->Session->CheckPermission('Calendar', 'default');
		} else {
			$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
			if (!$GLOBALS['app']->Session->GetPermission('Calendar', 'default')) {
				if (!$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Calendar', 'OwnCategory')) {
		            //$GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
					return "Please log-in.";
				}
			}
		}
        $tpl = new Jaws_Template('gadgets/Calendar/templates/');
        $tpl->Load('admin.html');
        $tpl->SetBlock('calendar_admin');
        

		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=CustomPage&action=account_SetGBRoot');
		if ($account === false) {
	        $GLOBALS['app']->Layout->AddScriptLink(BASE_SCRIPT . '?gadget=Calendar&amp;action=Ajax&amp;client=all&amp;stub=CalendarAdminAjax');
	        $GLOBALS['app']->Layout->AddScriptLink(BASE_SCRIPT . '?gadget=Calendar&amp;action=AjaxCommonFiles');
	        $GLOBALS['app']->Layout->AddScriptLink('gadgets/Calendar/resources/script.js');
			$GLOBALS['app']->Layout->AddScriptLink('libraries/js/admin.js');
			$tpl->SetVariable('menubar', $this->MenuBar($action));
			$account_prefix = '';
			$base_url = BASE_SCRIPT;
		} else {
	        $GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=Calendar&amp;action=Ajax&amp;client=all&amp;stub=CalendarAjax');
	        $GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=Calendar&amp;action=AjaxCommonFiles');
	        $GLOBALS['app']->Layout->AddScriptLink('gadgets/Calendar/resources/client_script.js');
			$tpl->SetVariable('menubar', '');
			$account_prefix = 'account_';
			$base_url = 'index.php';
		}
        
		$tpl->SetVariable('account', $account_prefix);
		$tpl->SetVariable('base_script', $base_url);

        $tpl->SetVariable('grid', $this->DataGrid());

        $toolBar   =& Piwi::CreateWidget('HBox');

        $deleteAll =& Piwi::CreateWidget('Button', 'deleteAllCalendars',
                                         _t('GLOBAL_DELETE'),
                                         STOCK_DELETE);
        $deleteAll->AddEvent(ON_CLICK,
                             "javascript: massiveDelete('"._t('CALENDAR_CONFIRM_MASIVE_DELETE_CATEGORY')."');");

        $toolBar->Add($deleteAll);

        $tpl->SetVariable('tools', $toolBar->Get());
                
		if ($account === false) {
	        //Status filter
	        $status = '';
	        $statusCombo =& Piwi::CreateWidget('Combo', 'status');
	        $statusCombo->setId('status');
	        $statusCombo->AddOption('&nbsp;', '');
	        $statusCombo->AddOption(_t('CALENDAR_PUBLISHED'), 'Y');
	        $statusCombo->AddOption(_t('CALENDAR_NOTPUBLISHED'), 'N');
	        $statusCombo->SetDefault($status);
	        $statusCombo->AddEvent(ON_CHANGE, 'javascript: searchCalendars();');
	        $tpl->SetVariable('status', _t('CALENDAR_STATUS').':');
	        $tpl->SetVariable('status_field', $statusCombo->Get());
		} else {
	        $searchEntry =& Piwi::CreateWidget('HiddenEntry', 'status', '');
	        $tpl->SetVariable('status_field', $searchEntry->Get());
		}

        // Free text search
        $searchButton =& Piwi::CreateWidget('Button', 'searchButton', _t('GLOBAL_SEARCH'), STOCK_SEARCH);
        $searchButton->AddEvent(ON_CLICK, 'javascript: searchCalendars();');
        $tpl->SetVariable('search', $searchButton->Get());

        $search = '';
        $searchEntry =& Piwi::CreateWidget('Entry', 'search', $search);
        $searchEntry->SetStyle('zwidth: 100%;');
        $tpl->SetVariable('search_field', $searchEntry->Get());

        $tpl->SetVariable('confirmCalendarDelete', _t('CALENDAR_CATEGORY_CONFIRM_DELETE'));

		// send embedding options
		$embed_button = "<td width=\"0%\" align=\"right\">
			<nobr>&nbsp;<input style=\"width: 130px;\" id=\"embedCalendarButton\" class=\"embedButton\" type=\"button\" value=\"Embed This\" onclick=\"if ( document.getElementById('embedCalendarInfo').style.display == 'none' ) { document.getElementById('embedCalendarInfo').style.display = 'block'; } else { document.getElementById('embedCalendarInfo').style.display = 'none';};\"></nobr>
		</td>";
		
		$embed_options = '';
		$embed_options .= "<a href=\"javascript:void(0);\" onclick=\"showEmbedWindow('".$GLOBALS['app']->GetSiteURL()."/".$base_url."?gadget=Calendar&amp;action=".$account_prefix."ShowEmbedWindow&amp;linkid=all&amp;uid=".($OwnerID != 0 ? $OwnerID : '')."&amp;mode=all_availability', 'Embed Availability Calendar');\">All Availability</a>&nbsp;&nbsp;&nbsp;\n";
		$embed_options .= "<a href=\"javascript:void(0);\" onclick=\"showEmbedWindow('".$GLOBALS['app']->GetSiteURL()."/".$base_url."?gadget=Calendar&amp;action=".$account_prefix."ShowEmbedWindow&amp;linkid=all&amp;uid=".($OwnerID != 0 ? $OwnerID : '')."&amp;mode=reservation', 'Embed Reservation Form');\">Reservation Form</a>&nbsp;&nbsp;&nbsp;\n";
		$embed_options .= "<a href=\"javascript:void(0);\" onclick=\"showEmbedWindow('".$GLOBALS['app']->GetSiteURL()."/".$base_url."?gadget=Calendar&amp;action=".$account_prefix."ShowEmbedWindow&amp;linkid=all&amp;uid=".($OwnerID != 0 ? $OwnerID : '')."&amp;mode=list', 'Embed Calendar Index');\">List of All Calendars</a>&nbsp;&nbsp;&nbsp;\n";
		$embed_options = "<tr>
			<td colspan=\"2\">
				<div id=\"embedCalendarInfo\" style=\"display: none; display: none; background: #fff url(". $GLOBALS['app']->GetJawsURL() . "/gadgets/ControlPanel/images/right_menu_bg.png) top left repeat-x; border: 1px solid #babdb6; margin: 1em 150px; width: 450px; font-size: 85%; text-align: center; padding: 15px;\">
				What do you want to embed on your site?<br />
				".$embed_options."
				</div>
			</td>
		</tr>";

		$tpl->SetVariable('embed_button', $embed_button);
		$tpl->SetVariable('embed_options', $embed_options);

        $tpl->SetVariable('entries', $this->Datagrid());

		// Add button is added by HTML->GetUserAccountControls
		if ($account === false) {
	        $addPage =& Piwi::CreateWidget('Button', 'add_calendar', _t('CALENDAR_ADD_CATEGORY'), STOCK_ADD);
			$addPage->AddEvent(ON_CLICK, "javascript: window.location = '".$base_url."?gadget=Calendar&amp;action=".$account_prefix."form';");
	        $tpl->SetVariable('add_calendar', $addPage->Get());
		} else {
			//$addPage->AddEvent(ON_CLICK, "javascript: window.open('".$base_url."?gadget=CustomPage&amp;action=".$account_prefix."form','','scrollbars=1,menubar=0,toolbar=0,location=0,status=1');");
	        $tpl->SetVariable('add_calendar', '');
		}

        $tpl->ParseBlock('calendar_admin');

        return $tpl->Get();
    }	

    /**
     * We are on a form page
     *
     * @access public
     * @return string
     */
    function form($account = false)
    {
		// check session
		if ($account === false) {
			$GLOBALS['app']->Session->CheckPermission('Calendar', 'default');
		} else {
			$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
			if (!$GLOBALS['app']->Session->GetPermission('Calendar', 'default')) {
				if (!$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Calendar', 'OwnCategory')) {
		            $GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
					$userHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
					return $userHTML->DefaultAction();
				}
			}
		}

		// document dependencies
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$model = $GLOBALS['app']->LoadGadget('Calendar', 'Model');
		$request =& Jaws_Request::getInstance();
		$gather = array('action', 'id');
		$get = $request->getRaw($gather, 'get');

		// initialize template
		$tpl = new Jaws_Template('gadgets/Calendar/templates/');
        $tpl->Load('admin.html');
        $tpl->SetBlock('msgbox-wrapper');
        $responses = $GLOBALS['app']->Session->PopLastResponse();
        if ($responses) {
            foreach ($responses as $msg_id => $response) {
                $tpl->SetBlock('msgbox-wrapper/msgbox');
                $tpl->SetVariable('msg-css', $response['css']);
                $tpl->SetVariable('msg-txt', $response['message']);
                $tpl->SetVariable('msg-id', $msg_id);
                $tpl->ParseBlock('msgbox-wrapper/msgbox');
            }
        }
        $tpl->ParseBlock('msgbox-wrapper');

        $tpl->SetBlock('gadget_calendar');

		// account differences
		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=CustomPage&action=account_SetGBRoot');
		if ($account === false) {
			$this->AjaxMe('script.js');
			$tpl->SetVariable('menubar', $this->MenuBar($get['action']));
			$GLOBALS['app']->Layout->AddScriptLink('libraries/js/admin.js');			
			$submit_vars['ACTIONPREFIX'] = "";
			$submit_vars['CLOSE_BUTTON'] = "location.href='" . BASE_SCRIPT . "?gadget=Calendar&amp;action=Admin';";
			$syntactsUrl = $GLOBALS['app']->getSyntactsAdminHTMLUrl();
			$OwnerID = 0;
			$base_url = BASE_SCRIPT;
		} else {
			$tpl->SetVariable('menubar', '');
			$this->AjaxMe('client_script.js');
			$submit_vars['ACTIONPREFIX'] = "account_";
			$submit_vars['CLOSE_BUTTON'] = "parent.parent.hideGB();";
			$syntactsUrl = $GLOBALS['app']->getSyntactsAdminHTMLUrl('Calendar/admin_Calendar_form');
			$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');
			$base_url = 'index.php';
		}
		$tpl->SetVariable('workarea-style', "style=\"float: left; margin-top: 30px;\" ");

		// syntacts page
		if ($syntactsUrl) {
			// snoopy
			include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
			$snoopy = new Snoopy('Calendar');
			$submit_url = $syntactsUrl;
			
			if($snoopy->fetch($submit_url)) {
				//while(list($key,$val) = each($snoopy->headers))
					//echo $key.": ".$val."<br>\n";
				//echo "<p>\n";
				
				//$page = "<PRE>".htmlspecialchars($snoopy->results)."</PRE>\n";
				$error = '';
				$form_content = '';
				
				// initialize template
				$stpl = new Jaws_Template();
		        $stpl->LoadFromString($snoopy->results);
		        $stpl->SetBlock('form');
				if (!empty($get['id'])) {
					// send page records
					$pageInfo = $model->GetCalendar($get['id']);
					if (!Jaws_Error::IsError($pageInfo) && ($GLOBALS['app']->Session->GetPermission('Calendar', 'ManageCategories') || $pageInfo['calendarparentownerid'] == $OwnerID)) {
						$submit_vars['SUBMIT_BUTTON'] = "<input type='Submit' name='Submit' value='Update'>";
					} else {
						//$error = _t('CALENDAR_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403'));												
						return new Jaws_Error(_t('CALENDAR_CATEGORY_NOT_FOUND'), _t('CALENDAR_NAME'));
					}
				} else {
					$submit_vars['SUBMIT_BUTTON'] = "<input type='Submit' name='Submit' value='Add'>";
				}

				// send requesting URL to syntacts
				$stpl->SetVariable('HTTP_REFERER', $GLOBALS['app']->GetSiteURL());
				//$stpl->SetVariable('DPATH', JAWS_DPATH);
				$stpl->SetVariable('actionprefix', $submit_vars['ACTIONPREFIX']);
				$stpl->SetVariable('gadget', 'Calendar');
				$stpl->SetVariable('CLOSE_BUTTON', $submit_vars['CLOSE_BUTTON']);
				$stpl->SetVariable('SUBMIT_BUTTON', $submit_vars['SUBMIT_BUTTON']);
				$stpl->SetVariable('controller', $base_url);
				
				// Get Help documentation
				$help_url = $GLOBALS['app']->getSyntactsAdminHTMLUrl("Calendar/admin_Calendar_form_help", 'txt');
				$snoopy = new Snoopy('Calendar');
		
				if($snoopy->fetch($help_url)) {
					$helpContent = Jaws_Utils::split2D($snoopy->results);
				}
								
				// Hidden elements
				$ID = (isset($pageInfo['calendarparentid'])) ? $pageInfo['calendarparentid'] : '';
				$idHidden =& Piwi::CreateWidget('HiddenEntry', 'calendarparentID', $ID);
		        $form_content .= $idHidden->Get()."\n";

				$sort_order = (isset($pageInfo['calendarparentsort_order'])) ? $pageInfo['calendarparentsort_order'] : '0';
				$sort_orderHidden =& Piwi::CreateWidget('HiddenEntry', 'calendarparentsort_order', $sort_order);
		        $form_content .= $sort_orderHidden->Get()."\n";

				$fuseaction = (isset($pageInfo['calendarparentid'])) ? 'EditCalendar' : 'AddCalendar';
				$fuseactionHidden =& Piwi::CreateWidget('HiddenEntry', 'fuseaction', $fuseaction);
		        $form_content .= $fuseactionHidden->Get()."\n";

				$featured = (isset($pageInfo['calendarparentfeatured'])) ? $pageInfo['calendarparentfeatured'] : 'N';
				$featuredHidden =& Piwi::CreateWidget('HiddenEntry', 'calendarparentFeatured', $featured);
		        $form_content .= $featuredHidden->Get()."\n";
				
				$propID = (isset($pageInfo['calendarparentpropid'])) ? $pageInfo['calendarparentpropid'] : '';
				$propIDHidden =& Piwi::CreateWidget('HiddenEntry', 'calendarparentPropID', $propID);
		        $form_content .= $propIDHidden->Get()."\n";
				
				// Active
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('CALENDAR_PUBLISHED')) {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$active = (isset($pageInfo['calendarparentactive'])) ? $pageInfo['calendarparentactive'] : 'Y';
				$activeCombo =& Piwi::CreateWidget('Combo', 'calendarparentActive');
				$activeCombo->AddOption(_t('CALENDAR_PUBLISHED'), 'Y');
				$activeCombo->AddOption(_t('CALENDAR_NOTPUBLISHED'), 'N');
				$activeCombo->SetDefault($active);
				$activeCombo->setTitle(_t('CALENDAR_PUBLISHED'));
				$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"calendarparentActive\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$activeCombo->Get()."</td></tr>";
				
				// Type
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('CALENDAR_TYPE')) {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$type = (isset($pageInfo['calendarparenttype'])) ? $pageInfo['calendarparenttype'] : 'E';
				$randomizeCombo =& Piwi::CreateWidget('Combo', 'calendarparentType');
				$randomizeCombo->AddOption(_t('CALENDAR_TYPE_EVENTS'), 'E');
				$randomizeCombo->AddOption(_t('CALENDAR_TYPE_AVAILABILITY'), 'A');
				$randomizeCombo->AddOption(_t('CALENDAR_TYPE_GOOGLE'), 'G');
				$randomizeCombo->SetDefault($type);
				$randomizeCombo->setTitle(_t('CALENDAR_TYPE'));
				$randomizeCombo->AddEvent(ON_CHANGE, "javascript: if (this.value == 'G') {toggleYes('googleInfo'); toggleNo('titleInfo'); } if (this.value != 'G') { toggleNo('googleInfo'); toggleYes('titleInfo'); };");
				$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"calendarparentType\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$randomizeCombo->Get()."</td></tr>";
				
				// Title
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('CALENDAR_GOOGLE_USERNAME')) {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "</a>";
							}
						}
					}
				}
				
				$form_content .= "<tr>";
				$form_content .= "<td style=\"display: ".($type != "G" || empty($ID) ? "none; " : "; ")."\" id=\"googleInfo\" valign=\"top\" class=\"syntacts-form-row\"><label for=\"calendarparentCategory_Name\"><nobr>".$helpString."</nobr></label>
					<div class=\"simple-response-msg\">For this to work, your calendar must be shared publically. For information on how to share your calendar, <a href=\"http://www.google.com/support/calendar/bin/answer.py?answer=37083\" target=\"_blank\">Click Here</a>.</div>	  
				</td>";
				
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('GLOBAL_TITLE')) {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "</a>";
							}
						}
					}
				}

				$form_content .= "<td style=\"display: ".($type == "G" ? "none; " : "; ")."\" id=\"titleInfo\" valign=\"top\" class=\"syntacts-form-row\"><label for=\"calendarparentCategory_Name\"><nobr>".$helpString."</nobr></label></td>";
				
				$title = (isset($pageInfo['calendarparentcategory_name'])) ? $pageInfo['calendarparentcategory_name'] : '';
				$titleEntry =& Piwi::CreateWidget('Entry', 'calendarparentCategory_Name', $title);
				$titleEntry->SetTitle(_t('GLOBAL_TITLE'));
				$titleEntry->SetStyle('direction: ltr; width: 300px;');
				$form_content .= "<td class=\"syntacts-form-row\">".$titleEntry->Get()."</td></tr>";

				// Description
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('CALENDAR_DESCRIPTIONFIELD')) {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$content = (isset($pageInfo['calendarparentdescription'])) ? $pageInfo['calendarparentdescription'] : '';
				$editor =& $GLOBALS['app']->LoadEditor('Calendar', 'calendarparentDescription', $content, false);
				$editor->TextArea->SetStyle('width: 100%;');
				//$editor->SetWidth('100%');
				$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"calendarparentDescription\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$editor->Get()."</td></tr>";

				if ($account === false) {	
					// Image
					$helpString = '';
					foreach($helpContent as $help) {		            
						if ($help[0] == _t('CALENDAR_IMAGE')) {
							$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
							if ($help[1]) {
								if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
									$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
								}
								$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
								$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
								$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
								$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
								if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
									$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
								}
								$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
								if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
									$helpString .= "</a>";
								}
							}
						}
					}
					$image = (isset($pageInfo['calendarparentimage'])) ? $pageInfo['calendarparentimage'] : '';
					$image_src = $GLOBALS['app']->getDataURL() . 'files'.$xss->filter($pageInfo['calendarparentimage']);
					$image_preview = '';
					if ($image != '' && file_exists($image_src)) { 
						$image_preview .= "<br /><img border=\"0\" src=\"".$image_src."\" width=\"80\"".(strtolower(substr($image, -3)) == 'gif' || strtolower(substr($image, -3)) == 'png' || strtolower(substr($image, -3)) == 'bmp' ? ' height="80"' : '')." align=\"left\" style=\"padding: 5px;\">";
					}
					$imageScript = "<script type=\"text/javascript\">Event.observe(window, \"load\",function(){addFileToPost('Calendar', 'NULL', 'NULL', 'main_image', 'calendarparentImage', 1, 500, 34);});</script>";
					$imageHidden =& Piwi::CreateWidget('HiddenEntry', 'calendarparentImage', $image);
					//$imageButton = "&nbsp;&nbsp;&nbsp;&nbsp;<INPUT TYPE=\"button\" VALUE=\"Uploaded Files\" ONCLICK=\"openUploadWindow('calendarparentImage')\" STYLE=\"font-family: Arial; font-size: 10pt; font-weight: bold\">";
					$imageButton = '';
					$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"calendarparentImage\"><nobr>".$helpString."</nobr></label>".$image_preview."</td><td class=\"syntacts-form-row\"><div id=\"main_image\" style=\"float: left; width: 500px;\"></div>".$imageScript.$imageHidden->Get().$imageButton."</td></tr>";
				} else {					
					$imageHidden =& Piwi::CreateWidget('HiddenEntry', 'calendarparentImage', '');
					$form_content .= $imageHidden->Get()."\n";
				}
					
				if ($error != '') {
					$stpl->SetVariable('content', $error);
				} else {
					$stpl->SetVariable('content', $form_content);
		        }
				$stpl->ParseBlock('form');
				$page = $stpl->Get();
			} else {
				$page = _t('CALENDAR_ERROR_ASPPAGE_NOT_RETRIEVED', $snoopy->error)."\n";
			}
			
			$tpl->SetVariable('content', $page);
		} else {
			return new Jaws_Error(_t('CALENDAR_ERROR_ASPPAGE_NOT_RETRIEVED'), _t('CALENDAR_NAME'));
		}
		
        $tpl->ParseBlock('gadget_calendar');
        return $tpl->Get();
						
    }

    /**
     * We are on the form_post page
     *
     * @access public
     * @return string
     */
    function form_post($account = false, $fuseaction = '', $params = array())
    {
		if ($account === false) {
			$GLOBALS['app']->Session->CheckPermission('Calendar', 'default');
		} else {
			$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
			if (!$GLOBALS['app']->Session->GetPermission('Calendar', 'default')) {
				if (!$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Calendar', 'OwnCategory')) {
		            $GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
					$userHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
					return $userHTML->DefaultAction();
				}
			}
		}
        
		require_once JAWS_PATH . 'include/Jaws/Header.php';
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		
		$request =& Jaws_Request::getInstance();
		$request =& Jaws_Request::getInstance();
        if (empty($fuseaction)) {
			$fuseaction = $request->get('fuseaction', 'post');
		}
		$get  = $request->get(array('fuseaction','pct','tdate','linkid', 'id'), 'get');
        if (empty($fuseaction)) {
			$fuseaction = $get['fuseaction'];
		}
        $get['tdate'] = urldecode($get['tdate']);
		$adminModel = $GLOBALS['app']->LoadGadget('Calendar', 'AdminModel');
		$model = $GLOBALS['app']->LoadGadget('Calendar', 'Model');
		
		$editcalendar = false;
		$editevent = false;
        
		if ($fuseaction) {		
			switch($fuseaction) {
                case "AddCalendar": 
				        $keys = array('calendarparentsort_order', 'calendarparentCategory_Name', 
							'calendarparentImage', 'calendarparentDescription', 'calendarparentActive', 
							'calendarparentOwnerID', 'calendarparentFeatured', 'calendarparentType', 'calendarparentPropID', 'calendarparentPropCatID');
						if (count($params) > 0) {
							$postData = $params;
						} else {	
							$postData = $request->getRaw($keys, 'post');
						}
						//foreach($postData as $key => $value) {
							//echo $key."=".$value."\n";
						//}
						// add OwnerID if no permissions
						if ($GLOBALS['app']->Session->GetPermission('Calendar', 'ManageCategories') && $account === false) {
							$OwnerID = null;
						} else {
							$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
						}
						$result = $adminModel->AddCalendar($postData['calendarparentsort_order'], 
							$postData['calendarparentCategory_Name'], $postData['calendarparentImage'], 
							$postData['calendarparentDescription'], $postData['calendarparentActive'], 
							$OwnerID, $postData['calendarparentFeatured'], $postData['calendarparentType'], $postData['calendarparentPropID']
						);
						if (!Jaws_Error::IsError($result)) {
					        // declare result as ok for later
							$editcalendar = true;
						} else {
							$editcalendar = false;
							$url = "javascript:history.go(-1)";
							$link =& Piwi::CreateWidget('Link', "Please go back and try again",
														$url);
							return '<br />'.$link->Get();
						}
						break;
                case "EditCalendar": 
				        $keys = array('calendarparentID', 'calendarparentsort_order', 
							'calendarparentCategory_Name', 'calendarparentImage', 
							'calendarparentDescription', 'calendarparentActive', 
							'calendarparentOwnerID', 'calendarparentFeatured', 'calendarparentType', 'calendarparentPropID', 'calendarparentPropCatID');
						if (count($params) > 0) {
							$postData = $params;
						} else {	
							$postData = $request->getRaw($keys, 'post');
						}
						if ($postData['calendarparentID']) {
							// add OwnerID if no permissions
							if ($GLOBALS['app']->Session->GetPermission('Calendar', 'ManageCategories')) {
								$result = $adminModel->UpdateCalendar($postData['calendarparentID'], $postData['calendarparentsort_order'], 
									$postData['calendarparentCategory_Name'], $postData['calendarparentImage'], 
									$postData['calendarparentDescription'], $postData['calendarparentActive'], 
									$postData['calendarparentFeatured'], $postData['calendarparentType'], $postData['calendarparentPropID']
								);
							} else {
								$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
								$calendarParent = $model->GetCalendar($postData['calendarparentID']);
								if ($OwnerID == $calendarParent['calendarparentownerid']) {
									$result = $adminModel->UpdateCalendar($calendarParent['calendarparentid'], $postData['calendarparentsort_order'], 
										$postData['calendarparentCategory_Name'], $postData['calendarparentImage'], 
										$postData['calendarparentDescription'], $postData['calendarparentActive'],  
										$postData['calendarparentFeatured'], $postData['calendarparentType'], $postData['calendarparentPropID']
									);
								} else {
									return _t('CALENDAR_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403'));												
								}
							}
						}
						if (!Jaws_Error::IsError($result)) {
							$editcalendar = true;
						} else {
							$editcalendar = false;
							$url = "javascript:history.go(-1)";
							$link =& Piwi::CreateWidget('Link', "Please go back and try again",
														$url);
							return '<br />'.$link->Get();
						}
                       break;
                case "DeleteCalendar": 
				        $keys = array('idarray', 'calendarparentID', 'xcount');
						$postData = $request->get($keys, 'post');
						$pct = $get['pct'];
						$dcount = 0;
						// loop through the idarray and delete each ID
						if ($postData['idarray'] && strpos($postData['idarray'], ',')) {
					        $ids = explode(',', $postData['idarray']);
							foreach ($ids as $i => $v) {
								if ($GLOBALS['app']->Session->GetPermission('Calendar', 'ManageCategories') && $account === false) {
									$result = $adminModel->DeleteCalendar((int)$v);
								} else {
									$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
									$calendarParent = $model->GetCalendar((int)$v);
									if ($OwnerID == $calendarParent['calendarparentownerid']) {
										$result = $adminModel->DeleteCalendar((int)$v);
									} else {
										return _t('CALENDAR_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403'));												
									}
								}								
								$dcount++;
							}
						} else if ($postData['calendarparentID']) {
							// check OwnerID if no permissions
							if ($GLOBALS['app']->Session->GetPermission('Calendar', 'ManageCategories')) {
								$result = $adminModel->DeleteCalendar($postData['calendarparentID']);
							} else {
								$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
								$calendarParent = $model->GetCalendar($postData['calendarparentID']);
								if ($OwnerID == $calendarParent['calendarparentownerid']) {
									$result = $adminModel->DeleteCalendar($postData['calendarparentID']);
								} else {
									return _t('CALENDAR_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403'));												
								}
							}
							$dcount++;
						}						
						//if the entire page has been deleted, set back the pct
						if ((int)$postData['xcount'] == ($dcount-1)) {
							//FIXME: Sync global variables (or User-prefs) with Syntacts 
							$pct = $pct - 12; //pageconst
							if ($pct < 0) {
								$pct = 0;
							}
						}
						if (!Jaws_Error::IsError($result)) {
							$editcalendar = true;
						} else {
							$editcalendar = false;
							$url = "javascript:history.go(-1)";
							$link =& Piwi::CreateWidget('Link', "Please go back and try again",
														$url);
							return '<br />'.$link->Get();
						}
						break;
                case "AddEvent": 
				        $keys = array('Event', 'startdate', 'enddate', 
							'Host', 'Image', 'sm_description', 'Description',  
							'iTimeHr', 'iTimeMin', 'iTimeSuffix', 'eTimeHr', 
							'eTimeMin', 'eTimeSuffix', 'Image', 'alink', 'alinkTitle', 
							'alinkType', 'isRecurring', 'Active', 'LinkID', 'max_occupancy', 'occupants');
						if (count($params) > 0) {
							$postData = $params;
							$postRaw = array();
							$postRaw['dayArr'] = $postData['dayArr'];
							$postRaw['dateArr'] = $postData['dateArr'];
						} else {	
							$postData = $request->getRaw($keys, 'post');
							$postRaw = $request->getRaw(array('dayArr', 'dateArr'), 'post');
						}
						//foreach($postData as $key => $value) {
							//echo $key."=".$value."\n";
						//}
						// add OwnerID if no permissions
						if ($GLOBALS['app']->Session->GetPermission('Calendar', 'ManageEvents') && $account === false) {
							$OwnerID = null;
						} else {
							// TODO: Add Public Events
							//if ($GLOBALS['app']->Session->GetPermission('Calendar', 'OwnPublicEvent')) {
							//	$OwnerID = null;
							//} else {
								$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
							//}
								$calendarParent = $model->GetCalendar((int)$postData['LinkID']);
								if (
									(isset($calendarParent['calendarparentownerid']) && $OwnerID != $calendarParent['calendarparentownerid']) || 
									!$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), 
										$GLOBALS['app']->Session->GetAttribute('groups'), 'Calendar', 'OwnEvent')
								) {
									$GLOBALS['app']->Session->CheckPermission('Calendar', 'OwnEvent');
								}
						}
						$iTime = '';
						if (!empty($postData['iTimeHr']) && !empty($postData['iTimeMin']) && !empty($postData['iTimeSuffix'])) {
							$iTime = $postData['iTimeHr'].":".$postData['iTimeMin']." ".$postData['iTimeSuffix'];
						}
						$endTime = '';
						if (!empty($postData['eTimeHr']) && !empty($postData['eTimeMin']) && !empty($postData['eTimeSuffix'])) {
							$endTime = $postData['eTimeHr'].":".$postData['eTimeMin']." ".$postData['eTimeSuffix'];
						}
						$result = $adminModel->AddEvent($postData['Event'], $postData['startdate'], 
							$postData['enddate'], $postData['sm_description'], $postData['Description'], 
							$postData['Host'], $iTime, $endTime, $postData['Image'], $postData['alink'], 
							$postData['alinkTitle'], $postData['alinkType'], $postData['isRecurring'], 
							$postData['Active'], $OwnerID, $postData['LinkID'], '', $postData['max_occupancy'], 
							$postData['occupants']);
						if (!Jaws_Error::IsError($result)) {
					        if ($postData['isRecurring'] == 'Y') {
								// check that recurring dayArr or dateArr has data, and both are not empty 
								if ((($postRaw['dayArr'] != '') && ($postRaw['dateArr'] == '')) || (($postRaw['dayArr'] == '') && ($postRaw['dateArr'] != ''))) {
									$res = $adminModel->AddRecurringEvent($result, $postRaw['dayArr'], $postRaw['dateArr'], $postData['LinkID']);
									if (Jaws_Error::IsError($result)) {
										return _t('CALENDAR_RECURRINGEVENT_NOT_ADDED');
									}
								} else {
									////$GLOBALS['app']->Session->PushSimpleResponse(_t('CALENDAR_ERROR_RECURRINGSCHEDULE'));
									$result4 = true;
								}
							}
							$editevent = true;
						} else {
							$editevent = false;
							$url = "javascript:history.go(-1)";
							$link =& Piwi::CreateWidget('Link', "Please go back and try again",
														$url);
							return '<br />'.$link->Get();
						}
						break;
                case "EditEvent": 
				        $keys = array('ID', 'Event', 'startdate', 'enddate', 
							'Host', 'Image', 'sm_description', 'Description',  
							'iTimeHr', 'iTimeMin', 'iTimeSuffix', 'eTimeHr', 
							'eTimeMin', 'eTimeSuffix', 'Image', 'alink', 'alinkTitle', 
							'alinkType', 'isRecurring', 'Active', 'LinkID', 'max_occupancy', 'occupants');
						if (count($params) > 0) {
							$postData = $params;
							$postRaw = array();
							$postRaw['dayArr'] = $postData['dayArr'];
							$postRaw['dateArr'] = $postData['dateArr'];
						} else {	
							$postData = $request->getRaw($keys, 'post');
							$postRaw = $request->getRaw(array('dayArr', 'dateArr'), 'post');
						}
						if ($postData['ID']) {
							// build readable time for event
							$iTime = '';
							if (!empty($postData['iTimeHr']) && !empty($postData['iTimeMin']) && !empty($postData['iTimeSuffix'])) {
								$iTime = $postData['iTimeHr'].":".$postData['iTimeMin']." ".$postData['iTimeSuffix'];
							}
							$endTime = '';
							if (!empty($postData['eTimeHr']) && !empty($postData['eTimeMin']) && !empty($postData['eTimeSuffix'])) {
								$endTime = $postData['eTimeHr'].":".$postData['eTimeMin']." ".$postData['eTimeSuffix'];
							}
							// check OwnerID if no permissions
							if ($GLOBALS['app']->Session->GetPermission('Calendar', 'ManageEvents') && $account === false) {
								$result = $adminModel->UpdateEvent($postData['ID'], $postData['Event'], $postData['startdate'], 
									$postData['enddate'], $postData['sm_description'], $postData['Description'], 
									$postData['Host'], $iTime, $endTime, $postData['Image'], $postData['alink'], 
									$postData['alinkTitle'], $postData['alinkType'], $postData['isRecurring'], 
									$postData['Active'], $postData['max_occupancy'], $postData['occupants'], $postData['LinkID']);
							} else {
								$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
								$event = $model->GetEvent($postData['ID']);
								if ($GLOBALS['app']->Session->GetPermission('Calendar', 'ManageEvents') || $OwnerID == $event['ownerid']) {
									$result = $adminModel->UpdateEvent($postData['ID'], $postData['Event'], $postData['startdate'], 
										$postData['enddate'], $postData['sm_description'], $postData['Description'], 
										$postData['Host'], $iTime, $endTime, $postData['Image'], $postData['alink'], 
										$postData['alinkTitle'], $postData['alinkType'], $postData['isRecurring'], 
										$postData['Active'], $postData['max_occupancy'], $postData['occupants'], $postData['LinkID']);
								} else {
									return _t('CALENDAR_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403'));												
								}
							}
							if (!Jaws_Error::IsError($result)) {
						        if ($postData['isRecurring'] == 'Y') {
									if ((($postRaw['dayArr'] != '') && ($postRaw['dateArr'] == '')) || (($postRaw['dayArr'] == '') && ($postRaw['dateArr'] != ''))) {
										$rids = $model->GetAllRecurringEventsOfEventEntry($postData['ID']);
										if (Jaws_Error::IsError($rids)) {
											$url = "javascript:history.go(-1)";
											$link =& Piwi::CreateWidget('Link', "Please go back and try again",
																		$url);
											return $rids->GetMessage().'<br />'.$link->Get();
											//return new Jaws_Error(_t('CALENDAR_GET_RECURRINGEVENTSOFCALENDAR'), _t('CALENDAR_NAME'));
										}
										foreach ($rids as $rid) {
											$res = $adminModel->UpdateRecurringEvent($rid['id'], $postRaw['dayArr'], $postRaw['dateArr']);
											if (Jaws_Error::IsError($res)) {
												return new Jaws_Error(_t('CALENDAR_RECURRINGEVENT_NOT_UPDATED'), _t('CALENDAR_NAME'));												
											}
										}
										if (empty($rids)) {
											$res = $adminModel->AddRecurringEvent($postData['ID'], $postRaw['dayArr'], $postRaw['dateArr'], $postData['LinkID']);
											if (Jaws_Error::IsError($res)) {
												return new Jaws_Error(_t('CALENDAR_RECURRINGEVENT_NOT_ADDED'), _t('CALENDAR_NAME'));												
											}
										}
									} else {
										//$GLOBALS['app']->Session->PushSimpleResponse(_t('CALENDAR_ERROR_RECURRINGSCHEDULE'));
										$result4 = true;
									}
								} else {
									$rids = $model->GetAllRecurringEventsOfEventEntry($postData['ID']);
									if (Jaws_Error::IsError($rids)) {
										$url = "javascript:history.go(-1)";
										$link =& Piwi::CreateWidget('Link', "Please go back and try again",
																	$url);
										return $rids->GetMessage().'<br />'.$link->Get();
										//return new Jaws_Error(_t('CALENDAR_GET_RECURRINGEVENTSOFCALENDAR'), _t('CALENDAR_NAME'));
									}
									foreach ($rids as $rid) {
										$res = $adminModel->DeleteRecurringEvent($rid['id']);
										if (Jaws_Error::IsError($res)) {
											return new Jaws_Error(_t('CALENDAR_RECURRINGEVENT_NOT_UPDATED'), _t('CALENDAR_NAME'));												
										}
									}
								}
								$editevent = true;
	                        } else {
								$editevent = false;
								$url = "javascript:history.go(-1)";
								$link =& Piwi::CreateWidget('Link', "Please go back and try again",
															$url);
								return '<br />'.$link->Get();
							}
						}
                        break;
                case "DeleteEvent": 
				        $keys = array('ID');
						$postData = $request->get($keys, 'post');
						$id = $postData['ID'];
						if (is_null($id)) {
							$id = $get['id'];
						}
						if (!is_null($id)) {
							if ($GLOBALS['app']->Session->GetPermission('Calendar', 'ManageEvents')) {
								$result = $adminModel->DeleteEvent((int)$id);
							} else {
								$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');			
								$event = $model->GetEvent((int)$id);
								if ($OwnerID == $event['ownerid']) {
									$result = $adminModel->DeleteEvent((int)$id);
								} else {
									return _t('CALENDAR_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403'));												
								}
							}
						}
						if (!Jaws_Error::IsError($result)) {
							$editevent = true;
						} else {
							$editevent = false;
							$url = "javascript:history.go(-1)";
							$link =& Piwi::CreateWidget('Link', "Please go back and try again",
														$url);
							return '<br />'.$link->Get();
						}
						break;
            }
			
			// Send us to the appropriate page
			if ($editcalendar === true) {
				if (count($params) > 0) {
					return (is_numeric($result) ? $result : (isset($postData['calendarparentID']) && !empty($postData['calendarparentID']) ? (int)$postData['calendarparentID'] : false));
				} else {				
					if ($fuseaction == 'DeleteCalendar') {
						$redirect = BASE_SCRIPT . '?gadget=Calendar';
					} else {
						$redirect = BASE_SCRIPT . '?gadget=Calendar&action=A&linkid='.(is_numeric($result) ? $result : $postData['calendarparentID']).'&tdate='.urlencode($postData['startdate']);
					}
				}
			} else if ($editevent === true) {
				if (count($params) > 0) {
					return (is_numeric($result) ? $result : (isset($postData['ID']) && !empty($postData['ID']) ? (int)$postData['ID'] : false));
				} else if ($result4 === true) {
					$redirect = BASE_SCRIPT . '?gadget=Calendar&action=A_form&linkid='.$postData['LinkID'].'&startdate='.urlencode($postData['startdate']).'&gadgetMsg=CALENDAR_ERROR_RECURRINGSCHEDULE';
				} else {
					$redirect = ((int)$postData['calendarparentPropID'] != 0 ? BASE_SCRIPT . '?gadget=Properties&action=A&id='.(int)$postData['calendarparentPropCatID'] : BASE_SCRIPT . '?gadget=Calendar&action=A&linkid='.$postData['LinkID'].'&tdate='.urlencode($postData['startdate']));
				}
			} else {
				if ($account === false) {
					Jaws_Header::Location(BASE_SCRIPT . '?gadget=Calendar');
				} else {
					Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'));
				}
			}
			if ($account === false) {
				Jaws_Header::Location($redirect);
			} else {
				if ($editcalendar === true) {
					$output_html = "";
					$output_html .= "<script>\n";
					$output_html .= "	if (window.opener && !window.opener.closed) {\n";
					$output_html .= "		window.opener.location.reload();\n";
					$output_html .= "	}\n";
					$output_html .= "	window.location.href='index.php?gadget=Calendar&action=account_A&linkid=".(is_numeric($result) ? $result : $postData['calendarparentID'])."&tdate=".urlencode($postData['startdate'])."';\n";
					$output_html .= "</script>\n";
					$output_html .= "<noscript><div style='color:#FF0000; font-weight: bold;'>Javascript must be enabled in your browser in order to use this service.</div></noscript>\n";
					return $output_html;
				} else {
					$output_html = "";
					$output_html .= "<script>\n";
					$output_html .= "	if (window.opener && !window.opener.closed) {\n";
					$output_html .= "		window.opener.location.reload();\n";
					$output_html .= "	}\n";
					$output_html .= "	window.location.href='".$GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction')."';\n";
					$output_html .= "</script>\n";
					$output_html .= "<noscript><div style='color:#FF0000; font-weight: bold;'>Javascript must be enabled in your browser in order to use this service.</div></noscript>\n";
					return $output_html;
				}
			}


		} else {
			if ($account === false) {
				Jaws_Header::Location(BASE_SCRIPT . '?gadget=Calendar');
			} else {
				Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'));
			}
		}

    }


    /**
     * We are on the calendar_A page
     *
     * @access public
     * @return string
     */
    function A($account = false)
    {

		if ($account === false) {
			$GLOBALS['app']->Session->CheckPermission('Calendar', 'default');
		} else {
			$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
			if (!$GLOBALS['app']->Session->GetPermission('Calendar', 'default')) {
				if (!$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Calendar', 'OwnEvent')) {
		            $GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
					$userHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
					return $userHTML->DefaultAction();
				}
			}
		}

		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
						
		$tpl = new Jaws_Template('gadgets/Calendar/templates/');
        $tpl->Load('admin.html');

        $tpl->SetBlock('gadget_calendar');

		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=CustomPage&action=account_SetGBRoot');
		if ($account === false) {
			$GLOBALS['app']->Layout->AddScriptLink('libraries/js/admin.js');
			$this->AjaxMe('script.js');
			$tpl->SetVariable('menubar', $this->MenuBar($action));
			$submit_vars['ACTIONPREFIX'] = "";
			$OwnerID = 0;
			$base_url = BASE_SCRIPT;
		} else {
			$this->AjaxMe('client_script.js');
			$tpl->SetVariable('menubar', "&nbsp;");
			$submit_vars['ACTIONPREFIX'] = "account_";
			$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');
			$base_url = 'index.php';
		}

		$GLOBALS['app']->Layout->AddScriptLink('libraries/greybox/AJS.js');
        $GLOBALS['app']->Layout->AddScriptLink('libraries/greybox/AJS_fx.js');
        $GLOBALS['app']->Layout->AddScriptLink('libraries/greybox/gb_scripts.js');
        $GLOBALS['app']->Layout->AddHeadLink('libraries/greybox/gb_styles.css', 'stylesheet', 'text/css');
		/*
		$GLOBALS['app']->Layout->AddScriptLink('libraries/window/dist/window.js');
        $GLOBALS['app']->Layout->AddHeadLink('libraries/window/themes/window/window.css', 'stylesheet', 'text/css');
        $GLOBALS['app']->Layout->AddHeadLink('libraries/window/themes/window/simplewhite.css', 'stylesheet', 'text/css');
        $GLOBALS['app']->Layout->AddHeadLink('libraries/window/themes/shadow/mac_shadow.css', 'stylesheet', 'text/css');
        */
		$GLOBALS['app']->Layout->AddHeadLink('libraries/piwi/piwidata/css/default.css', 'stylesheet', 'text/css', 'default');
        
		$request =& Jaws_Request::getInstance();
		$cid = $request->getRaw('linkid', 'get');
		if (!empty($cid)) {
			$cid = (int)$cid;
		
			$model = $GLOBALS['app']->LoadGadget('Calendar', 'Model');
				
			// send calendarParent records
			$calendarParent = $model->GetCalendar($cid);
			
			if ($calendarParent && (($GLOBALS['app']->Session->GetPermission('Calendar', 'ManageCategories') || $calendarParent['calendarparentownerid'] == $OwnerID))) {
				$calendarLayout = $GLOBALS['app']->LoadGadget('Calendar', 'LayoutHTML');
				$calendar_html = $calendarLayout->Display($calendarParent['calendarparentid'], 'LayoutYear', false);
				$header_html = '<table width="100%" cellpadding="0" cellspacing="0" border="0"> 
					<tr>
					  <td width="0%" align="right"><nobr>
					  &nbsp;<input type="button" value="Edit Details" onclick="location.href=\''.$base_url.'?gadget=Calendar&amp;action='.$submit_vars['ACTIONPREFIX'].'form&amp;id='.$calendarParent['calendarparentid'].'\';">&nbsp;&nbsp;
					  &nbsp;<input type="button" name="Delete" onclick="if (confirm(\'Do you want to delete this calendar? This will delete all events for this calendar as well.\')) { location.href = \''.$base_url.'?gadget=Calendar&amp;action='.$submit_vars['ACTIONPREFIX'].'form_post&fuseaction=DeleteCalendar&amp;id='.$calendarParent['calendarparentid'].'\'; }" value="Delete">
					  &nbsp;<input type="button" '.($calendarParent['calendarparenttype'] == "G" ? 'value="Edit Events" onclick="window.open(\'http://calendar.google.com\');' : 'value="Add Event" onclick="'.($submit_vars['ACTIONPREFIX'] == "account_" ? "showPostWindow('" . $GLOBALS['app']->GetSiteURL(). "/" . $base_url ."?gadget=Calendar&action=".$submit_vars['ACTIONPREFIX']."A_form&linkid=".$calendarParent['calendarparentid']."', 'Add Event');" : "location.href='" . $GLOBALS['app']->GetSiteURL(). "/" . $base_url ."?gadget=Calendar&amp;action=".$submit_vars['ACTIONPREFIX']."A_form&amp;linkid=".$calendarParent['calendarparentid']."';")).'">&nbsp;&nbsp;
					  &nbsp;<input id="embedButton" type="button" value="Embed This" onclick="if ( document.getElementById(\'embedInfo\').style.display == \'none\' ) { document.getElementById(\'embedInfo\').style.display = \'block\'; } else { document.getElementById(\'embedInfo\').style.display = \'none\';};">
					  </nobr></td>
					</tr>
				</table>
				<div id="embedInfo" style="display: none;">
				What do you want to embed on your site?<br />';
				/*
				if ($calendarParent['calendarparenttype'] != "G") {
					$header_html .= '<a href="javascript:void(0);" onclick="showEmbedWindow(\''.$GLOBALS['app']->GetSiteURL(). "/" . $base_url .'?gadget=Calendar&amp;action='.$submit_vars['ACTIONPREFIX'].'ShowEmbedWindow&amp;linkid='.$calendarParent['calendarparentid'].'&amp;mode=mini\', \'Embed Mini Calendar\');">Mini Calendar</a>&nbsp;&nbsp;&nbsp;';
				}
				*/
				$header_html .= '<a href="javascript:void(0);" onclick="showEmbedWindow(\''.$GLOBALS['app']->GetSiteURL(). "/" . $base_url .'?gadget=Calendar&action='.$submit_vars['ACTIONPREFIX'].'ShowEmbedWindow&linkid='.$calendarParent['calendarparentid'].'&mode=month&embed_string=Calendar\', \'Embed Full Calendar\');">Full Calendar</a>&nbsp;&nbsp;&nbsp;';
				if ($calendarParent['calendarparenttype'] == "A") {
					$header_html .= '<a href="javascript:void(0);" onclick="showEmbedWindow(\''.$GLOBALS['app']->GetSiteURL(). "/" . $base_url .'?gadget=Calendar&action='.$submit_vars['ACTIONPREFIX'].'ShowEmbedWindow&linkid='.$calendarParent['calendarparentid'].'&mode=year&embed_mode=button&embed_string=Availability%20Calendar\', \'Embed Availability Button\');">Availability Button</a>&nbsp;&nbsp;&nbsp;';
				}
				if ($calendarParent['calendarparenttype'] == "E") {
					$header_html .= '<a href="javascript:void(0);" onclick="showEmbedWindow(\''.$GLOBALS['app']->GetSiteURL(). "/" . $base_url .'?gadget=Calendar&action='.$submit_vars['ACTIONPREFIX'].'ShowEmbedWindow&linkid='.$calendarParent['calendarparentid'].'&mode=list&embed_string=Upcoming%20Events\', \'Embed Events Listing\');">List of Upcoming Events</a>&nbsp;&nbsp;&nbsp;';
				}
				$header_html .= '</div>'."\n";
				$header_html .= '<div>';
				if (!empty($calendarParent['calendarparentdescription'])) { 
					$content = $calendarParent['calendarparentdescription'];
					if (strpos($content, "\"color: #ffffff;") !== false) {
						$content = str_replace("\"color: #ffffff;", "\"color: #cccccc;", $content);
					}
					if (strpos($content, "; color: #ffffff;") !== false) {
						$content = str_replace("; color: #ffffff;", "; color: #cccccc;", $content);
					}
					$header_html .= $content.'<br />';
				}
				$header_html .= '</div>';
			} else {
				$page = _t('CALENDAR_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403'))."\n";
			}
		} else {
			// Send us to the appropriate page
			require_once JAWS_PATH . 'include/Jaws/Header.php';
			if ($account === true) {
				Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'));
			} else {
				Jaws_Header::Location($base_url . '?gadget=Calendar&action=Admin');
			}
		}

		if (!isset($page)) {
			if ($calendarParent['calendarparenttype'] == 'G') {
				// TODO: Use Google feed, not embedded
				// Google Calendar Embed URL
				$googleCalendarUrl = "http://www.google.com/calendar/embed?src=".$calendarParent['calendarparentcategory_name'];
				$google_calendarHTML = "<iframe id='calendar-iframe-google".$calendarParent['calendarparentcategory_name']."' style='background: transparent url(); border-right: 0pt; border-top: 0pt; border-left: 0pt; border-bottom: 0pt; min-height: 300px; height: 100%; width: 100%;' src='".$googleCalendarUrl."' frameborder='0' allowTransparency='true' scrolling='no'></iframe>";
				//$google_calendarHTML .= "<script type=\"text/javascript\">if (document.getElementById('calendar-iframe-google".$calendarParent['calendarparentcategory_name']."').parentNode) { document.getElementById('calendar-iframe-google".$calendarParent['calendarparentcategory_name']."').style.height = parseInt(document.getElementById('calendar-iframe-google".$calendarParent['calendarparentcategory_name']."').parentNode.offsetHeight); }</script>";
				$page = $google_calendarHTML;
			} else if (isset($calendar_html)) {
				$page = $calendar_html;
			} else {
				$page = _t('CALENDAR_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403'))."\n";
			}
		}

		$tpl->SetVariable('content', $header_html.$page);

        $tpl->ParseBlock('gadget_calendar');

        return $tpl->Get();

    }

    /**
     * We are on A_form page
     *
     * @access public
     * @return string
     */
    function A_form($account = false)
    {
		// check session
		if ($account === false) {
			$GLOBALS['app']->Session->CheckPermission('Calendar', 'default');
		} else {
			$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
			if (!$GLOBALS['app']->Session->GetPermission('Calendar', 'default')) {
				if (!$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Calendar', 'OwnEvent')) {
		            $GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
					$userHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
					return $userHTML->DefaultAction();
				}
			}
		}

		// document dependencies
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$model = $GLOBALS['app']->LoadGadget('Calendar', 'Model');
		$request =& Jaws_Request::getInstance();
		$gather = array('action', 'id', 'linkid', 'startdate', 'enddate');
		$get = $request->get($gather, 'get');
		$get['startdate'] = urldecode($get['startdate']);
		$get['enddate'] = urldecode($get['enddate']);
		
		// initialize template
		$tpl = new Jaws_Template('gadgets/Calendar/templates/');
        $tpl->Load('admin.html');
        $tpl->SetBlock('msgbox-wrapper');
        $responses = $GLOBALS['app']->Session->PopLastResponse();
        if ($responses) {
            foreach ($responses as $msg_id => $response) {
                $tpl->SetBlock('msgbox-wrapper/msgbox');
                $tpl->SetVariable('msg-css', $response['css']);
                $tpl->SetVariable('msg-txt', $response['message']);
                $tpl->SetVariable('msg-id', $msg_id);
                $tpl->ParseBlock('msgbox-wrapper/msgbox');
            }
        }
        $tpl->ParseBlock('msgbox-wrapper');

        $tpl->SetBlock('gadget_calendar');

		// account differences
		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=CustomPage&action=account_SetGBRoot');
		if ($account === false) {
			$this->AjaxMe('script.js');
			$tpl->SetVariable('menubar', $this->MenuBar($get['action']));
			$GLOBALS['app']->Layout->AddScriptLink('libraries/js/admin.js');			
			$submit_vars['ACTIONPREFIX'] = "";
			$submit_vars['CLOSE_BUTTON'] = "location.href='" . BASE_SCRIPT . "?gadget=Calendar&action=A&linkid=".$get['linkid']."';";
			$syntactsUrl = $GLOBALS['app']->getSyntactsAdminHTMLUrl();
			$OwnerID = 0;
			$base_url = BASE_SCRIPT;
		} else {
			$tpl->SetVariable('menubar', '');
			$this->AjaxMe('client_script.js');
			$submit_vars['ACTIONPREFIX'] = "account_";
			$submit_vars['CLOSE_BUTTON'] = "javascript:history.go(-1);";
			$syntactsUrl = $GLOBALS['app']->getSyntactsAdminHTMLUrl('Calendar/admin_Calendar_A_form');
			$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');
			$base_url = 'index.php';
		}
        
		$GLOBALS['app']->Layout->AddHeadLink('libraries/piwi/piwidata/css/calendar-blue.css', 'stylesheet', 'text/css');
		$GLOBALS['app']->Layout->AddScriptLink('libraries/piwi/piwidata/js/jscalendar/calendar.js');			
		$GLOBALS['app']->Layout->AddScriptLink('libraries/piwi/piwidata/js/jscalendar/calendar-setup.js');			
		$GLOBALS['app']->Layout->AddScriptLink('libraries/piwi/piwidata/js/jscalendar/lang/calendar-en.js');			
				
		$tpl->SetVariable('workarea-style', "style=\"float: left; margin-top: 30px;\" ");

		if (!empty($get['linkid'])) {
			$calendar = $model->GetCalendar($get['linkid']);
			if (Jaws_Error::IsError($calendar) || (!$GLOBALS['app']->Session->GetPermission('Calendar', 'ManageCategories') && $calendar['calendarparentownerid'] != $OwnerID)) {
				//$error = _t('CALENDAR_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403'));												
				return new Jaws_Error(_t('CALENDAR_CATEGORY_NOT_FOUND'), _t('CALENDAR_NAME'));
			}
		}

		// syntacts page
		if ($syntactsUrl) {
			// snoopy
			include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
			$snoopy = new Snoopy('Calendar');
			$submit_url = $syntactsUrl;
			
			if($snoopy->fetch($submit_url)) {
				//while(list($key,$val) = each($snoopy->headers))
					//echo $key.": ".$val."<br>\n";
				//echo "<p>\n";
				
				//$page = "<PRE>".htmlspecialchars($snoopy->results)."</PRE>\n";
				$error = '';
				$form_content = '';
				
				// initialize template
				$stpl = new Jaws_Template();
		        $stpl->LoadFromString($snoopy->results);
		        $stpl->SetBlock('form');
				if (!empty($get['id'])) {
					$pageInfo = $model->GetEvent($get['id']);
					if (!Jaws_Error::IsError($pageInfo) && ($GLOBALS['app']->Session->GetPermission('Calendar', 'ManageEvents') || $pageInfo['ownerid'] == $OwnerID)) {
						$submit_vars['SUBMIT_BUTTON'] = "<input type='Submit' name='Submit' value='Update'>";
						$submit_vars['DELETE_BUTTON'] = "<input type=\"button\" value=\"Delete\" name=\"Delete\" onclick=\"if (confirm('Are you sure?')) {location.href='". BASE_SCRIPT ."?gadget=Calendar&amp;action=".$submit_vars['ACTIONPREFIX']."A_form_post&amp;id=".$get['id']."&amp;linkid=".$get['linkid']."&amp;fuseaction=DeleteEvent';}\">";
						/*
						// send recurring event records
						$recurring = $model->GetAllRecurringEventsOfEventEntry((int)$get['id']);
						*/
					} else {
						//$error = _t('CALENDAR_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403'));												
						return new Jaws_Error(_t('CALENDAR_EVENT_NOT_FOUND'), _t('CALENDAR_NAME'));
					}
				} else {
					$submit_vars['SUBMIT_BUTTON'] = "<input type='Submit' name='Submit' value='Add'>";
					$submit_vars['DELETE_BUTTON'] = '';
				}

				// send requesting URL to syntacts
				$stpl->SetVariable('HTTP_REFERER', $GLOBALS['app']->GetSiteURL());
				//$stpl->SetVariable('DPATH', JAWS_DPATH);
				$stpl->SetVariable('actionprefix', $submit_vars['ACTIONPREFIX']);
				$stpl->SetVariable('gadget', 'Calendar');
				$stpl->SetVariable('DELETE_BUTTON', $submit_vars['DELETE_BUTTON']);
				$stpl->SetVariable('CLOSE_BUTTON', $submit_vars['CLOSE_BUTTON']);
				$stpl->SetVariable('SUBMIT_BUTTON', $submit_vars['SUBMIT_BUTTON']);
				$stpl->SetVariable('controller', $base_url);
				
				// Get Help documentation
				$help_url = $GLOBALS['app']->getSyntactsAdminHTMLUrl("Calendar/admin_Calendar_A_form_help", 'txt');
				$snoopy = new Snoopy('Calendar');
		
				if($snoopy->fetch($help_url)) {
					$helpContent = Jaws_Utils::split2D($snoopy->results);
				}
								
				// Hidden elements
				$ID = (isset($pageInfo['id'])) ? $pageInfo['id'] : '';
				$idHidden =& Piwi::CreateWidget('HiddenEntry', 'ID', $ID);
		        $form_content .= $idHidden->Get()."\n";

				$LinkID = (isset($get['linkid']) && !empty($get['linkid'])) ? $get['linkid'] : '';
				$linkIDHidden =& Piwi::CreateWidget('HiddenEntry', 'LinkID', $LinkID);
		        $form_content .= $linkIDHidden->Get()."\n";

				$fuseaction = (isset($pageInfo['id'])) ? 'EditEvent' : 'AddEvent';
				$fuseactionHidden =& Piwi::CreateWidget('HiddenEntry', 'fuseaction', $fuseaction);
		        $form_content .= $fuseactionHidden->Get()."\n";

				$dayArr = (isset($eventInfo['dayname'])) ? $eventInfo['dayname'] : '';
				$dayArrHidden =& Piwi::CreateWidget('HiddenEntry', 'dayArr', $dayArr);
		        $form_content .= $dayArrHidden->Get()."\n";
				
				$dateArr = (isset($eventInfo['dates'])) ? $eventInfo['dates'] : '';
				$dateArrHidden =& Piwi::CreateWidget('HiddenEntry', 'dateArr', $dateArr);
		        $form_content .= $dateArrHidden->Get()."\n";
								
				$recurring = (isset($pageInfo['isrecurring'])) ? $eventInfo['isrecurring'] : 'N';
				$recurringHidden =& Piwi::CreateWidget('HiddenEntry', 'isRecurring', $recurring);
		        $form_content .= $recurringHidden->Get()."\n";
								
				$recurringdaynameHidden =& Piwi::CreateWidget('HiddenEntry', 'recurringdayname', '');
		        $form_content .= $recurringdaynameHidden->Get()."\n";
								
				$recurringdatesHidden =& Piwi::CreateWidget('HiddenEntry', 'recurringdates', '');
		        $form_content .= $recurringdatesHidden->Get()."\n";
								
				$occupants = (isset($pageInfo['occupants'])) ? $eventInfo['occupants'] : '';
				$occupantsHidden =& Piwi::CreateWidget('HiddenEntry', 'occupants', $occupants);
		        $form_content .= $occupantsHidden->Get()."\n";
								
				// Active
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('CALENDAR_PUBLISHED')) {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$active = (isset($pageInfo['active'])) ? $pageInfo['active'] : 'Y';
				$activeCombo =& Piwi::CreateWidget('Combo', 'Active');
				$activeCombo->AddOption(_t('CALENDAR_PUBLISHED'), 'Y');
				$activeCombo->AddOption(_t('CALENDAR_NOTPUBLISHED'), 'N');
				$activeCombo->SetDefault($active);
				$activeCombo->setTitle(_t('CALENDAR_PUBLISHED'));
				$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"Active\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$activeCombo->Get()."</td></tr>";
				
				// Startdate
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('CALENDAR_STARTDATE')) {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "</a>";
							}
						}
					}
				}
				if (isset($pageInfo['startdate'])) {
					$startdate = $GLOBALS['app']->UTC2UserTime($pageInfo['startdate'], "m/d/Y");
				} else {
					if (!empty($get['startdate'])) {
						$startdate = $get['startdate'];
					} else {
						$startdate = $GLOBALS['app']->UTC2UserTime('', "m/d/Y");
					}
				}
				$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"startdate\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\"><input name=\"startdate\" id=\"startdate\" size=\"10\" value=\"".$startdate."\" maxlength=\"10\">&nbsp;<button type=\"button\" name=\"start_button\" id=\"start_button\"><img id=\"start_button_stockimage\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/piwi/piwidata/art/stock/apps/office-calendar.png\" border=\"0\" alt=\"\" height=\"16\" width=\"16\" /></button>
				<script type=\"text/javascript\">
				 Calendar.setup({
				  inputField: \"startdate\",
				  ifFormat: \"%m/%d/%Y\",
				  button: \"start_button\",
				  singleClick: true,
				  weekNumbers: false,
				  firstDay: 0,
				  date: \"\",
				  showsTime: false,
				  multiple: false});
				</script>
				";
				
				$itime = '12:00 AM';
				if (isset($pageInfo['itime']) && !empty($pageInfo['itime'])) {
					$itime = $GLOBALS['app']->UTC2UserTime($pageInfo['itime'], "g:i A");
				} else if (!isset($pageInfo['id'])) {
					$itime = $GLOBALS['app']->UTC2UserTime('', "g:i A");
				}
					
				if (
					(isset($calendar['calendarparenttype']) && $calendar['calendarparenttype'] != 'E')
				) {
					$iTimeHrHidden =& Piwi::CreateWidget('HiddenEntry', 'iTimeHr', '12');
					$form_content .= $iTimeHrHidden->Get()."\n";
									
					$iTimeMinHidden =& Piwi::CreateWidget('HiddenEntry', 'iTimeMin', '00');
					$form_content .= $iTimeMinHidden->Get()."\n";
					
					$iTimeSuffixHidden =& Piwi::CreateWidget('HiddenEntry', 'iTimeSuffix', 'PM');
					$form_content .= $iTimeSuffixHidden->Get()."\n";
				} else {
					// Start Time
					$helpString = '';
					foreach($helpContent as $help) {		            
						if ($help[0] == _t('CALENDAR_STARTTIME')) {
							$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
							if ($help[1]) {
								if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
									$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
								}
								$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
								$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
								$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
								$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
								if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
									$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
								}
								$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
								if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
									$helpString .= "</a>";
								}
							}
						}
					}
					$form_content .= $helpString.'
					  <select size="1" name="iTimeHr">
						  <option value="12"'.(strpos($itime, "12:") !== false ? ' selected=""' : '').'>12</option>
						  <option value="1"'.(strpos($itime, "1:") !== false && substr($itime, 0, 3) != '11:' ? ' selected=""' : '').'>1</option>
						  <option value="2"'.(strpos($itime, "2:") !== false && substr($itime, 0, 3) != '12:' ? ' selected=""' : '').'>2</option>
						  <option value="3"'.(strpos($itime, "3:") !== false ? ' selected=""' : '').'>3</option>
						  <option value="4"'.(strpos($itime, "4:") !== false ? ' selected=""' : '').'>4</option>
						  <option value="5"'.(strpos($itime, "5:") !== false ? ' selected=""' : '').'>5</option>
						  <option value="6"'.(strpos($itime, "6:") !== false ? ' selected=""' : '').'>6</option>
						  <option value="7"'.(strpos($itime, "7:") !== false ? ' selected=""' : '').'>7</option>
						  <option value="8"'.(strpos($itime, "8:") !== false ? ' selected=""' : '').'>8</option>
						  <option value="9"'.(strpos($itime, "9:") !== false ? ' selected=""' : '').'>9</option>
						  <option value="10"'.(strpos($itime, "10:") !== false ? ' selected=""' : '').'>10</option>
						  <option value="11"'.(strpos($itime, "11:") !== false ? ' selected=""' : '').'>11</option>
					  </select>
					  <select size="1" name="iTimeMin">
						  <option value="00"'.(strpos($itime, ":00") !== false ? ' selected=""' : '').'>00</option>
						  <option value="01"'.(strpos($itime, ":01") !== false ? ' selected=""' : '').'>01</option>
						  <option value="02"'.(strpos($itime, ":02") !== false ? ' selected=""' : '').'>02</option>
						  <option value="03"'.(strpos($itime, ":03") !== false ? ' selected=""' : '').'>03</option>
						  <option value="04"'.(strpos($itime, ":04") !== false ? ' selected=""' : '').'>04</option>
						  <option value="05"'.(strpos($itime, ":05") !== false ? ' selected=""' : '').'>05</option>
						  <option value="06"'.(strpos($itime, ":06") !== false ? ' selected=""' : '').'>06</option>
						  <option value="07"'.(strpos($itime, ":07") !== false ? ' selected=""' : '').'>07</option>
						  <option value="08"'.(strpos($itime, ":08") !== false ? ' selected=""' : '').'>08</option>
						  <option value="09"'.(strpos($itime, ":09") !== false ? ' selected=""' : '').'>09</option>
						  ';
					for ($i = 10; $i < 59; $i++) {  
						$form_content .= '<option value="'.$i.'"'.(strpos($itime, ":".$i) !== false ? ' selected=""' : '').'>'.$i.'</option>'."\n";
					}  
					$form_content .= '</select>
					  <select size="1" name="iTimeSuffix">
						  <option value="AM"'.(strpos($itime, "AM") !== false ? ' selected=""' : '').'>AM</option>
						  <option value="PM"'.(strpos($itime, "PM") !== false ? ' selected=""' : '').'>PM</option>
					  </select></td></tr>';
				}
				
				// Enddate
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('CALENDAR_ENDDATE')) {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "</a>";
							}
						}
					}
				}
				if (isset($pageInfo['enddate'])) {
					$enddate = $GLOBALS['app']->UTC2UserTime($pageInfo['enddate'], "m/d/Y");
				} else {
					if (!empty($get['enddate'])) {
						$enddate = $get['enddate'];
					} else {
						$enddate = $startdate;
					}
				}
				$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"enddate\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\"><input name=\"enddate\" id=\"enddate\" size=\"10\" value=\"".$enddate."\" maxlength=\"10\">&nbsp;<button type=\"button\" name=\"end_button\" id=\"end_button\"><img id=\"end_button_stockimage\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/piwi/piwidata/art/stock/apps/office-calendar.png\" border=\"0\" alt=\"\" height=\"16\" width=\"16\" /></button>
				<script type=\"text/javascript\">
				 Calendar.setup({
				  inputField: \"enddate\",
				  ifFormat: \"%m/%d/%Y\",
				  button: \"end_button\",
				  singleClick: true,
				  weekNumbers: false,
				  firstDay: 0,
				  date: \"\",
				  showsTime: false,
				  multiple: false});
				</script>
				";
				
				$endtime = '12:00 AM';
				if (isset($pageInfo['endtime']) && !empty($pageInfo['endtime'])) {
					$endtime = $GLOBALS['app']->UTC2UserTime($pageInfo['endtime'], "g:i A");
				} else if (!isset($pageInfo['id'])) {
					$utc_str = gmdate("M d Y H:i:s", time()+3600);
					$utc = strtotime($utc_str);
					$endtime = $GLOBALS['app']->UTC2UserTime($utc, "g:i A");
				}
				
				if (
					(isset($calendar['calendarparenttype']) && $calendar['calendarparenttype'] != 'E')
				) {
					$eTimeHrHidden =& Piwi::CreateWidget('HiddenEntry', 'eTimeHr', '12');
					$form_content .= $eTimeHrHidden->Get()."\n";
									
					$eTimeMinHidden =& Piwi::CreateWidget('HiddenEntry', 'eTimeMin', '00');
					$form_content .= $eTimeMinHidden->Get()."\n";
					
					$eTimeSuffixHidden =& Piwi::CreateWidget('HiddenEntry', 'eTimeSuffix', 'PM');
					$form_content .= $eTimeSuffixHidden->Get()."\n";
				} else {
					// Start Time
					$helpString = '';
					foreach($helpContent as $help) {		            
						if ($help[0] == _t('CALENDAR_ENDTIME')) {
							$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
							if ($help[1]) {
								if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
									$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
								}
								$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
								$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
								$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
								$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
								if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
									$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
								}
								$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
								if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
									$helpString .= "</a>";
								}
							}
						}
					}
					$form_content .= $helpString.'
					  <select size="1" name="eTimeHr">
						  <option value="12"'.(strpos($endtime, "12:") !== false ? ' selected=""' : '').'>12</option>
						  <option value="1"'.(strpos($endtime, "1:") !== false && substr($endtime, 0, 3) != '11:' ? ' selected=""' : '').'>1</option>
						  <option value="2"'.(strpos($endtime, "2:") !== false && substr($endtime, 0, 3) != '12:' ? ' selected=""' : '').'>2</option>
						  <option value="3"'.(strpos($endtime, "3:") !== false ? ' selected=""' : '').'>3</option>
						  <option value="4"'.(strpos($endtime, "4:") !== false ? ' selected=""' : '').'>4</option>
						  <option value="5"'.(strpos($endtime, "5:") !== false ? ' selected=""' : '').'>5</option>
						  <option value="6"'.(strpos($endtime, "6:") !== false ? ' selected=""' : '').'>6</option>
						  <option value="7"'.(strpos($endtime, "7:") !== false ? ' selected=""' : '').'>7</option>
						  <option value="8"'.(strpos($endtime, "8:") !== false ? ' selected=""' : '').'>8</option>
						  <option value="9"'.(strpos($endtime, "9:") !== false ? ' selected=""' : '').'>9</option>
						  <option value="10"'.(strpos($endtime, "10:") !== false ? ' selected=""' : '').'>10</option>
						  <option value="11"'.(strpos($endtime, "11:") !== false ? ' selected=""' : '').'>11</option>
					  </select>
					  <select size="1" name="eTimeMin">
						  <option value="00"'.(strpos($endtime, ":00") !== false ? ' selected=""' : '').'>00</option>
						  <option value="01"'.(strpos($endtime, ":01") !== false ? ' selected=""' : '').'>01</option>
						  <option value="02"'.(strpos($endtime, ":02") !== false ? ' selected=""' : '').'>02</option>
						  <option value="03"'.(strpos($endtime, ":03") !== false ? ' selected=""' : '').'>03</option>
						  <option value="04"'.(strpos($endtime, ":04") !== false ? ' selected=""' : '').'>04</option>
						  <option value="05"'.(strpos($endtime, ":05") !== false ? ' selected=""' : '').'>05</option>
						  <option value="06"'.(strpos($endtime, ":06") !== false ? ' selected=""' : '').'>06</option>
						  <option value="07"'.(strpos($endtime, ":07") !== false ? ' selected=""' : '').'>07</option>
						  <option value="08"'.(strpos($endtime, ":08") !== false ? ' selected=""' : '').'>08</option>
						  <option value="09"'.(strpos($endtime, ":09") !== false ? ' selected=""' : '').'>09</option>
						  ';
					for ($i = 10; $i < 59; $i++) {  
						$form_content .= '<option value="'.$i.'"'.(strpos($endtime, ":".$i) !== false ? ' selected=""' : '').'>'.$i.'</option>'."\n";
					}  
					$form_content .= '</select>
					  <select size="1" name="eTimeSuffix">
						  <option value="AM"'.(strpos($endtime, "AM") !== false ? ' selected=""' : '').'>AM</option>
						  <option value="PM"'.(strpos($endtime, "PM") !== false ? ' selected=""' : '').'>PM</option>
					  </select></td></tr>';
				}

				// Title
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == (isset($calendar['calendarparenttype']) && $calendar['calendarparenttype'] == 'A' ? _t('CALENDAR_STATUS') : _t('GLOBAL_TITLE'))) {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "</a>";
							}
						}
					}
				}
				
				if (isset($calendar['calendarparenttype']) && $calendar['calendarparenttype'] == 'A') {
					$title = (isset($pageInfo['event'])) ? $pageInfo['event'] : 'Tentative';
					$titleEntry =& Piwi::CreateWidget('Combo', 'Event');
					$titleEntry->AddOption(_t('CALENDAR_RESERVED'), 'Reserved');
					$titleEntry->AddOption(_t('CALENDAR_TENTATIVE'), 'Tentative');
					$titleEntry->SetDefault($title);
					$titleEntry->SetTitle(_t('CALENDAR_STATUS'));
				} else {
					$title = (isset($pageInfo['event'])) ? $pageInfo['event'] : '';
					$titleEntry =& Piwi::CreateWidget('Entry', 'Event', $title);
					$titleEntry->SetStyle('direction: ltr; width: 300px;');
					$titleEntry->SetTitle(_t('GLOBAL_TITLE'));
				}
				$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"Event\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$titleEntry->Get()."</td></tr>";
				
				// Host
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('CALENDAR_WHERE')) {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "</a>";
							}
						}
					}
				}
				
				$host = (isset($pageInfo['host'])) ? $pageInfo['host'] : '';
				$hostEntry =& Piwi::CreateWidget('Entry', 'Host', $host);
				$hostEntry->SetStyle('direction: ltr; width: 300px;');
				$hostEntry->SetTitle(_t('CALENDAR_WHERE'));
				$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"Host\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$hostEntry->Get()."</td></tr>";
				
				// Max Occupancy
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('CALENDAR_MAX_OCCUPANCY')) {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "</a>";
							}
						}
					}
				}
				
				$max_occupancy = (isset($pageInfo['max_occupancy'])) ? $pageInfo['max_occupancy'] : '';
				$max_occupancyEntry =& Piwi::CreateWidget('Entry', 'max_occupancy', $max_occupancy);
				$max_occupancyEntry->SetStyle('direction: ltr; width: 100px;');
				$max_occupancyEntry->SetTitle(_t('CALENDAR_MAX_OCCUPANCY'));
				$max_occupancyEntry->AddEvent(ON_CHANGE, "javascript: this.value = this.value.replace(/\D/g, '');");
				$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"max_occupancy\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$max_occupancyEntry->Get()."</td></tr>";

				// Summary
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('CALENDAR_SUMMARY')) {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "</a>";
							}
						}
					}
				}
				
				$summary = (isset($pageInfo['sm_description'])) ? $pageInfo['sm_description'] : '';
				$summaryEntry =& Piwi::CreateWidget('Entry', 'sm_description', $summary);
				$summaryEntry->SetStyle('direction: ltr; width: 400px;');
				$summaryEntry->SetTitle(_t('CALENDAR_SUMMARY'));
				$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"sm_description\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$summaryEntry->Get()."</td></tr>";

				// Description
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('CALENDAR_DESCRIPTIONFIELD')) {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$content = (isset($pageInfo['description'])) ? $pageInfo['description'] : '';
				$editor =& $GLOBALS['app']->LoadEditor('Calendar', 'Description', $content, false);
				$editor->TextArea->SetStyle('width: 100%;');
				//$editor->SetWidth('100%');
				$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"Description\"><nobr>".$helpString."</nobr></label></td><td class=\"syntacts-form-row\">".$editor->Get()."</td></tr>";

				// Image
				$helpString = '';
				foreach($helpContent as $help) {		            
					if ($help[0] == _t('CALENDAR_IMAGE')) {
						$helpString = "<a name=\"".$help[0]."\"><b>".$help[0].": </b></a>\n";
						if ($help[1]) {
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<a href=\"".$help[2]."\" target=\"_blank\">";
							}
							$helpString .= "<img src=\"images/stock/help-browser.png\" border=\"0\"";
							$helpString .= " style=\"cursor: pointer; cursor: hand;\"";
							$helpString .= " title=\"header=[<p style='padding: 1px' align='left'>Help</p>]"; 
							$helpString .= " body=[<p style='padding: 1px' align='left'>".$help[1]."</p>";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "<p style='padding: 1px' align='left'><a><b>Click on the &quot;?&quot; Icon For More Information</b></a></p>";
							}
							$helpString .= "] delay=[10] fade=[on] fadespeed=[.2]\">";
							if (isset($help[2]) && !empty($help[2]) && strlen($help[2]) > 4) {
								$helpString .= "</a>";
							}
						}
					}
				}
				$image = (isset($pageInfo['image'])) ? $pageInfo['image'] : '';
				$image_src = $GLOBALS['app']->getDataURL() . 'files'.$xss->filter($pageInfo['image']);
				$image_preview = '';
				if ($image != '' && file_exists($image_src)) { 
					$image_preview .= "<br /><img border=\"0\" src=\"".$image_src."\" width=\"80\"".(strtolower(substr($image, -3)) == 'gif' || strtolower(substr($image, -3)) == 'png' || strtolower(substr($image, -3)) == 'bmp' ? ' height="80"' : '')." align=\"left\" style=\"padding: 5px;\">";
				}
				$imageScript = "<script type=\"text/javascript\">Event.observe(window, \"load\",function(){addFileToPost('Calendar', 'NULL', 'NULL', 'main_image', 'Image', 1, 500, 34);});</script>";
				$imageHidden =& Piwi::CreateWidget('HiddenEntry', 'Image', $image);
				$imageButton = "&nbsp;&nbsp;&nbsp;&nbsp;<INPUT TYPE=\"button\" VALUE=\"Uploaded Files\" ONCLICK=\"openUploadWindow('Image')\" STYLE=\"font-family: Arial; font-size: 10pt; font-weight: bold\">";
				$form_content .= "<tr><td class=\"syntacts-form-row\"><label for=\"Image\"><nobr>".$helpString."</nobr></label>".$image_preview."</td><td class=\"syntacts-form-row\"><div id=\"main_image\" style=\"float: left; width: 500px;\"></div>".$imageScript.$imageHidden->Get().$imageButton."</td></tr>";
					
				if ($error != '') {
					$stpl->SetVariable('content', $error);
				} else {
					$stpl->SetVariable('content', $form_content);
		        }
				$stpl->ParseBlock('form');
				$page = $stpl->Get();
			} else {
				$page = _t('CALENDAR_ERROR_ASPPAGE_NOT_RETRIEVED', $snoopy->error)."\n";
			}
			
			$tpl->SetVariable('content', $page);
		} else {
			return new Jaws_Error(_t('CALENDAR_ERROR_ASPPAGE_NOT_RETRIEVED'), _t('CALENDAR_NAME'));
		}
		
        $tpl->ParseBlock('gadget_calendar');
        return $tpl->Get();
						
    }

    /**
     * We are on the calendar_A_form_post page
     *
     * @access public
     * @return string
     */
    function A_form_post($account = false)
    {

		if ($account === false) {
			return $this->form_post();
		} else {
			return $this->form_post(true);
		}

    }

    /**
     * ShowEmbedWindow
     *
     * @access public
     * @return string
     */
    function ShowEmbedWindow()
    {
		$user_admin = $GLOBALS['app']->LoadGadget('Users', 'AdminHTML');
		return $user_admin->ShowEmbedWindow('Calendar', 'OwnCategory');
    }

    /**
     * sets GB root with DPATH
     *
     * @access public
     * @return javascript string
     */
    function SetGBRoot()
    {
		// Make output a real JavaScript file!
		header('Content-type: text/javascript'); 
		echo "var GB_ROOT_DIR = \"data/greybox/\";";
	}

    /**
     * Quick add form
     *
     * @access public
     * @return XHTML string
     */
    function GetQuickAddForm($account = false)
    {
		// check session
		if ($account === false) {
			$GLOBALS['app']->Session->CheckPermission('Calendar', 'default');
		} else {
			$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
			if (!$GLOBALS['app']->Session->GetPermission('Calendar', 'default')) {
				if (!$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Calendar', 'OwnEvent')) {
		            $GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
					$userHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
					return $userHTML->DefaultAction();
				}
			}
		}
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');

		require_once JAWS_PATH . 'include/Jaws/Template.php';
        $tpl = new Jaws_Template('gadgets/Calendar/templates/');
        $tpl->Load('QuickAddForm.html');
        $tpl->SetBlock('form');

		$request =& Jaws_Request::getInstance();
		$method = $request->get('method', 'get');
		if (empty($method)) {
			$method = 'AddEvent';
		}
		$form_content = '';
		switch($method) {
			case "AddCalendar": 
			case "UpdateCalendar": 
				$form_content = $this->form($account);
				break;
			case "AddEvent": 
				$request->set('get', 'linkid', '');
			case "AddGadget": 
			case "UpdateEvent": 
				$form_content = $this->A_form($account);
				break;
		}
		if (Jaws_Error::IsError($form_content)) {
			$form_content = $form_content->GetMessage();
		}
        
		$direction = _t('GLOBAL_LANG_DIRECTION');
        $dir  = $direction == 'rtl' ? '.' . $direction : '';
        $brow = $GLOBALS['app']->GetBrowserFlag();
        $brow = empty($brow)? '' : '.'.$brow;
        $base_url = $GLOBALS['app']->GetSiteURL().'/';

        $tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . "/");
        //$tpl->SetVariable('DPATH', JAWS_DPATH);
        $tpl->SetVariable('stub', (JAWS_SCRIPT == 'admin' ? 'CalendarAdminAjax' : 'CalendarAjax'));
        $tpl->SetVariable('BASE_URL', $base_url);
        $tpl->SetVariable('.dir', $dir);
        $tpl->SetVariable('.browser', $brow);
		$tpl->SetVariable('base_script', BASE_SCRIPT);
		$tpl->SetVariable('account', (JAWS_SCRIPT == 'admin' ? '' : 'account_'));
		$tpl->SetVariable('script', (JAWS_SCRIPT == 'admin' ? 'script' : 'client_script'));
											
		$tpl->SetVariable('content', $form_content);
		
		$tpl->ParseBlock('form');
        return $tpl->Get();
	}
}