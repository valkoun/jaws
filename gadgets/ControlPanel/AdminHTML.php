<?php
/**
 * ControlPanel Gadget Admin
 *
 * @category   GadgetAdmin
 * @package    ControlPanel
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2004-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */

ini_set("memory_limit","999M");
ini_set("post_max_size","25M");
ini_set("upload_max_filesize","2M");
ini_set("max_execution_time","300");

class ControlPanelAdminHTML extends Jaws_GadgetHTML
{
    /**
     * Gadget constructor
     *
     * @access public
     */
    function ControlPanelAdminHTML()
    {
        $this->Init('ControlPanel');
    }

    /**
     * Calls default action(MainMenu)
     *
     * @access public
     * @return string template content
     */
    function DefaultAction()
    {
        return $this->MainMenu();
    }

    /**
     * Displays the Control Panel main menu
     *
     * @access       public
     * @return       template content
     */
    function MainMenu($footer = false, $dock = false)
    {
        // Load the template
		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=Users&amp;action=Ajax&client=all&stub=UsersAjax');
		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=Users&amp;action=AjaxCommonFiles');
		$GLOBALS['app']->Layout->AddScriptLink('gadgets/Users/resources/client_script.js');
        $tpl = new Jaws_Template('gadgets/ControlPanel/templates/');
		if ($footer === true) {
			$tpl->Load('QuickMenu.html');
		} else if ($dock === true) {	
			$tpl->Load('DockMenu.html');
		} else {	
			$GLOBALS['app']->Layout->AddHeadOther('<style type="text/css">
		p.date {
			height: 20px;
			margin-left: 10px;
			padding: 15px 0 14px;
			text-align: center;
			width: 48px;
		}
		.email-item-icon { background: url('.$GLOBALS['app']->GetJawsURL() . '/gadgets/ControlPanel/images/email.png) no-repeat center 0; }
		</style>');
			$tpl->Load('MainMenu.html');
		}
        $jms = $GLOBALS['app']->LoadGadget('Jms', 'AdminModel');
        $coreGadgets = array_keys($jms->GetGadgetsList(true, true, true));

        $general = array();
		
		if ($dock === true && $this->GetPermission('default')) {
            $general[] = array("Control Panel",
                               "Control Panel Home",
                               'gadgets/ControlPanel/images/logo.png',
                               $GLOBALS['app']->GetSiteURL('', false, 'http').'/admin.php?gadget=ControlPanel&amp;action=DefaultAction');
        }

        $coreitems = $GLOBALS['app']->Registry->Get('/gadgets/core_items');
        $coreitems = explode(',', $coreitems);
        foreach ($coreitems as $item) {
            if (!is_dir(JAWS_PATH . 'gadgets/' . $item)) continue;
            if ($item != '' && $item != 'ControlPanel' && in_array($item, $coreGadgets)) {
                if (Jaws_Error::IsError(Jaws_GadgetInfo::Init($item))) {
                    continue;
                }

                if ($this->GetPermission('Manage'.$item, $item)) {
                    $general[] = array(_t(strtoupper($item).'_NAME'),
                                       _t(strtoupper($item).'_DESCRIPTION'),
                                       'gadgets/'.$item.'/images/logo.png',
                                       $GLOBALS['app']->GetSiteURL('', false, 'http').'/admin.php?gadget='.$item);
                }
            }
        }

       if ($this->GetPermission('DatabaseBackups')) {
            $general[] = array(_t('CONTROLPANEL_GENERAL_BACKUP'),
                               _t('CONTROLPANEL_GENERAL_BACKUP'),
                               'gadgets/ControlPanel/images/db-backup.png',
                               $GLOBALS['app']->GetSiteURL('', false, 'http').'/admin.php?gadget=ControlPanel&amp;action=DatabaseBackup');
        }

       if ($this->GetPermission('Statistics')) {
            $general[] = array(_t('CONTROLPANEL_GENERAL_STATISTICS'),
                               _t('CONTROLPANEL_GENERAL_STATISTICS'),
                               'gadgets/ControlPanel/images/statistics.png',
                               $GLOBALS['app']->GetSiteURL('', false, 'http').'/admin.php?gadget=ControlPanel&amp;action=Statistics');
        }

        if ($GLOBALS['app']->Registry->Get('/config/show_viewsite') == 'true') {
            $general[] = array(_t('CONTROLPANEL_GENERAL_VIEWSITE'),
                               _t('CONTROLPANEL_GENERAL_VIEWSITE'),
                               'gadgets/ControlPanel/images/view_site.png',
                               $GLOBALS['app']->GetSiteURL('', false, 'http').'/index.php');
        }
        
        //If no items are found for general section then we shoudln't print it
        $i = 0;
        $num = 0;
		if (count($general) > 0) {
            // Parse out core gadgets and CP components
            $tpl->SetBlock('main');
			if ($dock === true) {	
				$cid = 'Dock';
				$gallery_dimensions = "gmaxWidth".$cid." = 950;\n";
				$gallery_dimensions .= "gmaxHeight".$cid." = 80;\n";
				/*
				$gallery_dimensions .= "if ($('flash-gallery-".$cid."').parentNode) {\n";
				$gallery_dimensions .= " 	gmaxWidth".$cid." = parseInt($('flash-gallery-".$cid."').parentNode.offsetWidth);\n";	
				$gallery_dimensions .= "}\n";
				*/						
				
				$tpl->SetVariable('gallery_dimensions', $gallery_dimensions);
			}
            $tpl->SetVariable('title',_t('CONTROLPANEL_GENERAL'));
            foreach ($general as $item) {
				$i++;
				if ($dock === false || ($dock === true && $item[0] != 'URL Manager' && $item[0] != 'Theme Manager' && $item[0] != 'Website Backup')) {
					$tpl->SetBlock('main/item');
					if ($dock === false) {	
						$tpl->SetVariable('name', $item[0]);
						$tpl->SetVariable('desc', $item[1]);
						$tpl->SetVariable('icon', Jaws::CheckImage($item[2]));
						$tpl->SetVariable('url', $item[3]);
						if ($footer === true) {
							$tpl->SetVariable('realname', str_replace(" ", '_', $item[3]));
						}
					} else {	
						$tpl->SetVariable('image_src', Jaws::CheckImage($item[2]));
						$tpl->SetVariable('image_url', $item[3]);
						$tpl->SetVariable('image_target', ($item[3] == 'index.php' ? "_blank" : "_self"));
						$tpl->SetVariable('image_caption', $item[0]);
						$tpl->SetVariable('image_id', $num);
						$num++;
					}
					$tpl->ParseBlock('main/item');
				}
            }
			if ($dock === false) {	
				$tpl->ParseBlock('main');
            }
        }

        // gadgets
        $installedgadgets = array();
        $gadgetsections = array();
        $last = '';

        $gadgets = $jms->GetGadgetsList(false, true, true);
        foreach ($gadgets as $gadget => $tgadget) {
            if ($this->GetPermission('default', $gadget)) {
                $gadgetinfo = $GLOBALS['app']->loadGadget($gadget, 'Info');
                if (!Jaws_Error::IsError($gadgetinfo)) {
                    $section = $gadgetinfo->GetSection();
                    if (!isset($gadgetsections[$section]['gadgets'])) {
                        $gadgetsections[$section]['gadgets'] = array();
                        $gadgetsections[$section]['section'] = $section;
                    }
                    $tmp = array(
                                 'name'  => $gadget,
                                 'tname' => $tgadget['name'],
                                 'desc'  => $tgadget['description'],
                                 );

                    array_push($gadgetsections[$section]['gadgets'], $tmp);
                }
            }
        }

        $g = 0;
		foreach ($gadgetsections as $section) {
			foreach ($section['gadgets'] as $gadget) {
				$g++;
			}
		}
		$i = 0;
        foreach ($gadgetsections as $section) {
			if ($dock === false) {	
				$tpl->SetBlock('main');
            }
			$tpl->SetVariable('title', _t('CONTROLPANEL_' . strtoupper($section['section'])));
			foreach ($section['gadgets'] as $gadget) {
				$i++;
                $tpl->SetBlock('main/item');
				if ($dock === false) {	
					$tpl->SetVariable('name', $gadget['tname']);
					$tpl->SetVariable('desc', $gadget['desc']);
					$tpl->SetVariable('icon', Jaws::CheckImage('gadgets/'.$gadget['name'].'/images/logo.png'));
					$tpl->SetVariable('url', $GLOBALS['app']->GetSiteURL('', false, 'http').'/admin.php?gadget='.$gadget['name']);
					if ($footer === true || $dock === true) {
						$tpl->SetVariable('realname', str_replace(" ", '_', $gadget['tname']));
					}
				} else {	
					$tpl->SetVariable('image_src', Jaws::CheckImage('gadgets/'.$gadget['name'].'/images/logo.png'));
					$tpl->SetVariable('image_url', $GLOBALS['app']->GetSiteURL('', false, 'http').'/admin.php?gadget='.$gadget['name']);
					$tpl->SetVariable('image_target', "_self");
					$tpl->SetVariable('image_caption', $gadget['tname']);
					$tpl->SetVariable('image_id', $num);
					$num++;
                }
                $tpl->ParseBlock('main/item');
            }
            $tpl->ParseBlock('main');
        }

        if ($footer === false && $dock === false) {
        	if ($this->GetPermission('ManageGadgets', 'Jms')) {
            	$GLOBALS['app']->Translate->LoadTranslation('Jms', JAWS_GADGET);

				$jms = $GLOBALS['app']->LoadGadget('Jms', 'AdminModel');
				//Count non-installed gadgets..
				$noninstalled = $jms->GetGadgetsList(null, false);
				//Count out date gadgets..
				$nonupdated   = $jms->GetGadgetsList(null, true, false);
				$jms = null;
				$tpl->SetBlock('sidebar');
								
				// Outdated/Uninstalled Gadgets
				if ((count($noninstalled) + count($nonupdated)) > 0) {
					if (count($noninstalled) > 0) {
						$tpl->SetBlock('sidebar/gadget_notifications');
						$tpl->SetVariable('notify-title', '<h1>'._t('JMS_SIDEBAR_DISABLED_GADGETS').'</h1>');
						$tpl->SetVariable('notify_desc', _t('JMS_SIDEBAR_GADGETS_WAITING'));
						foreach ($noninstalled as $key => $gadget) {
							$tpl->SetBlock('sidebar/gadget_notifications/item');
							$gadgetCompleteDesc = $gadget['name'] . ' - ' . $gadget['description'];
							$icon = Jaws::CheckImage('gadgets/' . $key . '/images/logo.png');
							$tpl->SetVariable('title', $gadgetCompleteDesc);
							$tpl->SetVariable('name', $gadget['name']);
							$tpl->SetVariable('icon', $icon);
							$tpl->SetVariable('url', BASE_SCRIPT . '?gadget=Jms&amp;action=EnableGadget&amp;comp='.
											  $key . '&amp;location=sidebar');
							$tpl->SetVariable('install', _t('JMS_INSTALL'));
							$tpl->ParseBlock('sidebar/gadget_notifications/item');
						}
						$tpl->ParseBlock('sidebar/gadget_notifications');
					}

					if (count($nonupdated) > 0) {
						$tpl->SetBlock('sidebar/gadget_notifications');
						$tpl->SetVariable('notify-title', _t('JMS_SIDEBAR_NOTUPDATED_GADGETS'));
						$tpl->SetVariable('notify_desc', _t('JMS_SIDEBAR_NOTUPDATED_SUGESTION'));
						foreach ($nonupdated as $key => $gadget) {
							$tpl->SetBlock('sidebar/gadget_notifications/item');
							$gadgetCompleteDesc = $gadget['name'] . ' - ' . $gadget['description'];
							$icon = Jaws::CheckImage('gadgets/' . $key . '/images/logo.png');
							$tpl->SetVariable('title', $gadgetCompleteDesc);
							$tpl->SetVariable('name', $gadget['name']);
							$tpl->SetVariable('icon', $icon);
							$tpl->SetVariable('url', BASE_SCRIPT . '?gadget=Jms&amp;action=UpdateGadget&amp;comp='.
											  $key . '&amp;location=sidebar');
							$tpl->SetVariable('install', _t('JMS_UPDATE'));
							$tpl->ParseBlock('sidebar/gadget_notifications/item');
						}
						$tpl->ParseBlock('sidebar/gadget_notifications');
					}
				}
				$tpl->ParseBlock('sidebar');
			}
		}

        return $tpl->Get();
    }

    /**
     * Get HTML login form
     *
     * @access public
     * @param  string  $message If a message is needed
     * @return string  HTML of the form
     */
    function ShowLoginForm($message = '')
    {
        $GLOBALS['app']->Translate->LoadTranslation('ControlPanel', JAWS_GADGET);

        $use_crypt = ($GLOBALS['app']->Registry->Get('/crypt/enabled') == 'true')? true : false;
        if ($use_crypt) {
            require_once JAWS_PATH . 'include/Jaws/Crypt.php';
            $JCrypt = new Jaws_Crypt();
            $use_crypt = $JCrypt->Init();
        }

        $tpl = new Jaws_Template('gadgets/ControlPanel/templates/');
        $tpl->Load('Login.html');
        $tpl->SetBlock('login');

		$tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . "/");
        $tpl->SetVariable('BASE_URL', $GLOBALS['app']->GetSiteURL().'/'.BASE_SCRIPT);
        $tpl->SetVariable('admin_script', BASE_SCRIPT);
        $tpl->SetVariable('site-name', $GLOBALS['app']->Registry->Get('/config/site_name'));
        $tpl->SetVariable('site-slogan', $GLOBALS['app']->Registry->Get('/config/site_slogan'));
        $tpl->SetVariable('site-reseller-link', $GLOBALS['app']->Registry->Get('/config/site_reseller_link'));
        $tpl->SetVariable('site-reseller', $GLOBALS['app']->Registry->Get('/config/site_reseller'));
        $tpl->SetVariable('control-panel', _t('CONTROLPANEL_NAME'));

        $request =& Jaws_Request::getInstance();
        $form =& Piwi::CreateWidget('Form', BASE_SCRIPT, 'post');
        $form->setID('login_form');
        $form->shouldValidate($use_crypt, $use_crypt);

        $redirectTo = '';
        if (isset($_SERVER['QUERY_STRING'])) {
            $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
            $queryString = $xss->parse($_SERVER['QUERY_STRING']);
            if (!empty($queryString)) {
                $redirectTo  = '?' . $queryString;
                $redirectTo  = $xss->filter($redirectTo);
            }
        }

        $form->Add(Piwi::CreateWidget('HiddenEntry', 'gadget', 'ControlPanel'));
        $form->Add(Piwi::CreateWidget('HiddenEntry', 'action', 'Login'));
        $form->Add(Piwi::CreateWidget('HiddenEntry', 'redirect_to', $redirectTo));

        if ($use_crypt) {
            $form->Add(Piwi::CreateWidget('HiddenEntry', 'modulus',  $JCrypt->math->bin2int($JCrypt->pub_key->getModulus())));
            $form->Add(Piwi::CreateWidget('HiddenEntry', 'exponent', $JCrypt->math->bin2int($JCrypt->pub_key->getExponent())));
        }

        include_once JAWS_PATH . 'include/Jaws/Widgets/FieldSet.php';
        $fieldset = new Jaws_Widgets_FieldSet(_t('CONTROLPANEL_LOGIN_TITLE'));
        $fieldset->SetDirection('vertical');
        $fieldset->SetStyle('width: 100%;');

        $username = $request->get('username', 'post');
        $usernameEntry =& Piwi::CreateWidget('Entry', 'username',
                                             isset($username) ? $username : '');
        $usernameEntry->SetTitle(_t('GLOBAL_USERNAME'));
        $fieldset->Add($usernameEntry);

        $tpl->SetVariable('loadObject', $usernameEntry->GetID());

        $passEntry =& Piwi::CreateWidget('PasswordEntry', 'password', '');
        $passEntry->SetTitle(_t('GLOBAL_PASSWORD'));
        $fieldset->Add($passEntry);

        $rememberMe =& Piwi::CreateWidget('CheckButtons', 'remember');
        $rememberMe->setID('remember');
        $rememberMe->setColumns(1);
        $rememberMe->AddOption(_t('GLOBAL_REMEMBER_ME'), 'true');
        $fieldset->Add($rememberMe);

        if ($use_crypt) {
            $useCrypt =& Piwi::CreateWidget('CheckButtons', 'usecrypt');
            $useCrypt->setID('usecrypt');
            $useCrypt->setColumns(1);
            $useCrypt->AddOption(_t('CONTROLPANEL_LOGIN_SECURE'), 'true');
            $useCrypt->SetDefault('true');
            $fieldset->Add($useCrypt);
        }

        $submit =& Piwi::CreateWidget('Button', 'loginButton', _t('GLOBAL_LOGIN'), STOCK_OK);
        $submit->SetSubmit();
        $fieldset->Add($submit);

        $form->Add($fieldset);

        $tpl->SetVariable('form', $form->Get());
        $tpl->SetVariable('back', _t('CONTROLPANEL_LOGIN_BACK_TO_SITE'));

        $prefix = '.' . strtolower(_t('GLOBAL_LANG_DIRECTION'));
        if ($prefix !== '.rtl') {
            $prefix = '';
        }

        $hLinks = $GLOBALS['app']->Layout->AddHeadLink(
                                    'gadgets/ControlPanel/resources/public.css',
                                    'stylesheet', 'text/css', '',
                                    null, false, '', true);
        $sLinks[] = $GLOBALS['app']->Layout->AddScriptLink('libraries/js/bigint.js', 'text/javascript', true);
        $sLinks[] = $GLOBALS['app']->Layout->AddScriptLink('libraries/js/bigintmath.js', 'text/javascript', true);
        $sLinks[] = $GLOBALS['app']->Layout->AddScriptLink('libraries/js/rsa.js', 'text/javascript', true);
        $tmpArray = array();
        $headContent = $GLOBALS['app']->Layout->GetHeaderContent($hLinks, $sLinks, $tmpArray, $tmpArray);

        $tpl->SetBlock('login/head');
        $tpl->SetVariable('ELEMENT', $headContent);
        $tpl->ParseBlock('login/head');

        if (!empty($message)) {
            $tpl->SetBlock('login/message');
            $tpl->SetVariable('message', $message);
            $tpl->ParseBlock('login/message');
        }

        $tpl->ParseBlock('login');

        return $tpl->Get();
    }

    /**
     * Terminates Control Panel session and redirects to website
     *
     * @access public
     */
    function Logout()
    {
		//$GLOBALS['app']->Session->Logout();
        require_once JAWS_PATH . 'include/Jaws/Header.php';
        //$urlRedirect = $GLOBALS['app']->GetSiteURL().'/';
        $urlRedirect = $GLOBALS['app']->GetSiteURL('', false, 'http').'/' .  $GLOBALS['app']->Map->GetURLFor('Users', 'Logout');
		Jaws_Header::Location($urlRedirect);
    }

    /**
     * Database backups.
     *
     * @category 	feature
     * @access 	public
     * @return 	string template content
     */
    function DatabaseBackup()
    {
		// Get all backups in db directory
		$dbdir = JAWS_DATA . 'db/';
		if (!is_dir($dbdir)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_FILE_DOES_NOT_EXIST'), RESPONSE_ERROR);
			return false;
		}
		$dir = scandir($dbdir);

		// Which backups to keep?
		$tokeep = array();
		$tokeep[]  = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
		for ($i=1; $i<7; $i++) {
			$tokeep[]  = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d")-$i, date("Y")));
		}
		for ($i=0; $i<6; $i++) {
			$tokeep[]  = date("Y-m-d", mktime(0, 0, 0, date("m")-$i, 1, date("Y")));
		}
		
		foreach($dir as $file) {
			if ($file != '.' && $file != '..' && !in_array(substr($file, 0, 10), $tokeep) && strpos($file, '.zip') !== false) {
				if (!Jaws_Utils::Delete($dbdir . $file, true, true)) {
					return new Jaws_Error("Can't delete ".$dbdir . $file, _t('CONTROLPANEL_NAME'));
				}
			}
		}
		$this->CheckPermission('DatabaseBackups', 'ControlPanel');
		$date = $GLOBALS['app']->loadDate();
		$request =& Jaws_Request::getInstance();
		$get = $request->get(array('tdate'), 'get');
		if (!empty($get['tdate'])) {			
			$current_month_number = end(explode('-', $get['tdate']));
			$current_year = substr($get['tdate'], 0, (strpos($get['tdate'], '-')));
		} else {
			$current_month_number = date('m');
			$current_year = date('Y');
		}
		//$current_month_str = ($current_month_number < 10 ? '0'.$current_month_number : $current_month_number);
		$current_month_name = $date->MonthString((int)$current_month_number);
		
		// initialize template
		$tpl =& new Jaws_Template('gadgets/ControlPanel/templates/');
        $tpl->Load('Backup.html');
		$tpl->SetBlock('backup');
		$tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . "/");
		//$tpl->SetVariable('DPATH', JAWS_DPATH);
		
		// Get all backups in db directory
		$dbdir = JAWS_DATA . 'db/';
		if (!is_dir($dbdir)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_FILE_DOES_NOT_EXIST'), RESPONSE_ERROR);
			return false;
		}
		$dir = scandir($dbdir);
		
		// Month links
		$left_nav = '';
		$right_nav = '';
		$months_html = "<b>".$current_month_name."</b>&nbsp;&nbsp;&nbsp;&nbsp;";
		$prev_month = ((int)$current_month_number)-1;
		$prev_year = $current_year;
		for ($i=1; $i<5; $i++) {
			if ($prev_month <= 0) {
				$prev_month = 12+($prev_month);
				$prev_year = ($prev_year-1);
			}
			$prev_month_name = $date->MonthString($prev_month);
			$prev_month_str = ($prev_month < 10 ? '0'.$prev_month : $prev_month);
			foreach($dir as $file) {
				if ($file != '.' && $file != '..' && substr($file, 0, 7) == $prev_year."-".$prev_month_str && strpos($file, '.zip') !== false) {
					if ($i == 4) {
						$left_nav = "<a href=\"admin.php?gadget=ControlPanel&action=DatabaseBackup&tdate=".$prev_year."-".$prev_month_str."\"><<</a>&nbsp;&nbsp;&nbsp;&nbsp;";
					} else {
						$months_html = "<a href=\"admin.php?gadget=ControlPanel&action=DatabaseBackup&tdate=".$prev_year."-".$prev_month_str."\">".$prev_month_name."</a>&nbsp;&nbsp;&nbsp;&nbsp;".$months_html;
					}
					break;
				}
			}
			$prev_month = ($prev_month-1);
		}
		if (!empty($get['tdate'])) {			
			$next_month = ((int)$current_month_number)+1;
			$next_year = $current_year;
			for ($i=1; $i<5; $i++) {
				if ($next_month > 12) {
					$next_month = ($next_month-12);
					$next_year = ($next_year+1);
				}
				$next_month_name = $date->MonthString($next_month);
				$next_month_str = ($next_month < 10 ? '0'.$next_month : $next_month);
				foreach($dir as $file) {
					if ($file != '.' && $file != '..' && substr($file, 0, 7) == $next_year."-".$next_month_str && strpos($file, '.zip') !== false) {
						if ($i == 4) {
							$right_nav = "<a href=\"admin.php?gadget=ControlPanel&action=DatabaseBackup&tdate=".$next_year."-".$next_month_str."\">>></a>";
						} else {
							$months_html = $months_html."<a href=\"admin.php?gadget=ControlPanel&action=DatabaseBackup&tdate=".$next_year."-".$next_month_str."\">".$next_month_name."</a>&nbsp;&nbsp;&nbsp;&nbsp;";
						}
						break;
					}
				}
				$next_month = ($next_month+1);
			}
		}
		$tpl->SetVariable('months_html', "<nobr>".$left_nav.$months_html.$right_nav."</nobr>");
		$tpl->SetVariable('description', "Showing ".$current_month_name." ".$current_year." backups");
		
		// HTML list of Backups
		$backups_html = '';
		$i = 0;
		foreach($dir as $file) {
			//echo '<br />'.$file;
			//echo '<br />'.substr($file, 0, 7);
			//echo '<br />'.$current_year."-".$current_month_number;
			if ($file != '.' && $file != '..' && substr($file, 0, 7) == $current_year."-".$current_month_number && strpos($file, '.zip') !== false) {
				$background = '';
				if ($i == 0) {
					$background = "background: #EDF3FE; border-top: dotted 1pt #E2E2E2; ";
				} else if (($i % 2) == 0) {
					$background = "background: #EDF3FE; ";
				}
				$backups_html .= "<tr id=\"syntactsCategory_".$i."\">\n";
				$backups_html .= "	<td style=\"".$background."padding:3px;\" class=\"syntacts-form-row\">".$date->Format(date("Y-m-d H:i:s", filemtime(JAWS_DATA . 'db/'.$file)), "MN j, g:ia")."</td>\n";
				$backups_html .= "	<td style=\"".$background."padding:3px;\" class=\"syntacts-form-row\"><a href=\"admin.php?gadget=ControlPanel&action=RestoreBackup&file=".$file."\">RESTORE</a></td>\n";
				$backups_html .= "</tr>\n";
				$i++;
			}
		}
		if ($backups_html == '') {
			$backups_html .= "<style>#syntactsCategories_head {display: none;}</style>\n";
			$backups_html .= "<tr id=\"syntactsCategories_no_items\" noDrop=\"true\" noDrag=\"true\"><td>&nbsp;</td><td style=\"text-align:left\"><i>No backups ";
			$backups_html .= "have been created for this month.</i></td><td>&nbsp;</td><td>&nbsp;</td></tr>\n";
			//$backups_html .= "<style>#syntactsCategories {display: none;}</style>\n";
		}
		$tpl->SetVariable('backups_html', $backups_html);
		$tpl->ParseBlock('backup');
		return $tpl->Get();
    }

    /**
     * Returns the database sql dump
     *
     * @access public
     * @return string template content
     * @todo rewrite to work with MDB2
     */
    function DBBackup()
    {
		//$this->CheckPermission('DatabaseBackups', 'ControlPanel');
		ignore_user_abort(true); 
        set_time_limit(0);
		for ($i=10; $i>0; $i--) {
			echo str_pad("&nbsp;<br>\n",8);
			// tag after text for Safari & Firefox
			// 8 char minimum for Firefox
			//ob_flush();
			flush();  // worked without ob_flush() for me
			usleep(100);
		}
		echo ". ";
		ob_flush();
		flush();
		//$table_prefix = isset($get['prefix']) && !empty($get['prefix']) ? $get['prefix'] : 'none';
		
        $request =& Jaws_Request::getInstance();
        $get_filename = $request->get('file', 'get');
		if (!empty($get_filename)) {
			$filename = $get_filename;
		}
        
		$get_num = $request->get('num', 'get');
		$num = 1;
		if (!empty($get_num)) {
			$num = ((int)$get_num > 0 ? (int)$get_num : 1);
		}
		
		// Count other backups for this day, and append a number to this one
		if ($num == 1) {
			$new_dirs = array();
			$new_dirs[] = JAWS_DATA . 'dbbackup';
			$new_dirs[] = JAWS_DATA . 'db';
			foreach ($new_dirs as $new_dir) {
				if (!Jaws_Utils::mkdir($new_dir)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_FAILED_CREATING_DIR', $new_dir), RESPONSE_ERROR);
				}
			}
		}
		$dbbackupdir = JAWS_DATA . 'dbbackup/';
		if (!is_dir($dbbackupdir)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_FAILED_CREATING_DIR', $dbbackupdir), RESPONSE_ERROR);
			return false;
		}
		$dbdir = JAWS_DATA . 'db/';
		if (!is_dir($dbdir)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_FAILED_CREATING_DIR', $dbdir), RESPONSE_ERROR);
			return false;
		}
		if ($num == 1) {
			$dir = scandir($dbdir);
			$i = 0;
			$file_count = '';
			foreach($dir as $file) {
				//echo '<br />'.$file;
				//echo '<br />'.substr($file, 0, 7);
				//echo '<br />'.$current_year."-".$current_month_number;
				if ($file != '.' && $file != '..' && substr($file, 0, 10) == date('Y-m-d') && strpos($file, '.zip') !== false) {
					$i++;
				}
			}
			if ($i > 0) {
				$file_count = '-'.($i < 10 ? '00'.$i : ($i < 100 ? '0'.$i : $i));
			}
			$filename = date('Y-m-d') . $file_count .'-'.substr(md5(date("Y-m-d H:i:s").$GLOBALS['app']->Session->GetAttribute('session_id')), 0, 12);

			$dump_schema = $GLOBALS['db']->Dump($dbbackupdir . $filename.'-schema.xml', 'structure');
			echo ". ";
			ob_flush();
			flush();
			$dump = $GLOBALS['db']->Dump($dbbackupdir . $filename.'.xml', 'content');
		}
		echo ". ";
		ob_flush();
		flush();
		
		
		// Remove default variables
		if (file_exists($dbbackupdir.$filename.'-schema.xml') && Jaws_Utils::is_writable($dbbackupdir)) {
			$schema_content = file_get_contents($dbbackupdir . $filename.'-schema.xml');
			while (strpos($schema_content, "<default>CURRENT_") !== false) {
				$inputStr = $schema_content;
				$delimeterLeft = "<default>CURRENT_";
				$delimeterRight = "</notnull>";
				$posLeft = strpos($inputStr, $delimeterLeft);
				$posRight = strpos($inputStr, $delimeterRight, $posLeft+strlen($delimeterLeft));
				$default_variable = substr($inputStr, $posLeft, (($posRight-$posLeft)+strlen($delimeterRight)));
				$schema_content = str_replace($default_variable, '<default><default>'."\n".'<notnull>false</notnull>', $schema_content);
			}
            $result = file_put_contents($dbbackupdir . $filename.'-schema.xml', $schema_content);
			if (!$result) {
				return new Jaws_Error("Could not remove variables from ".$filename."-schema.xml", 'SQL Schema', JAWS_ERROR_ERROR);
			}
		}
		/*
		$dbhost     =  $GLOBALS['db']->_dsn['hostspec'];
		$dbname     =  $GLOBALS['db']->_dsn['database'];
		$dbuser     =  $GLOBALS['db']->_dsn['username'];
		$dbpass     =  $GLOBALS['db']->_dsn['password'];
		$backupFile = $dbbackupdir . $filename .'.sql';
		
		require_once 'MDB2/Schema.php';

		$dsn = $GLOBALS['db']->_dsn;
		$table_prefix = $GLOBALS['db']->_prefix;
		//echo 'prefix = '.$table_prefix;
		//exit;
		$options = array(
			'debug' => DEBUG_ACTIVATED,
			'log_line_break' => '<br />',
			'portability' => (MDB2_PORTABILITY_ALL ^ MDB2_PORTABILITY_EMPTY_TO_NULL),
			'quote_identifier' => true
		);

		$schema =& MDB2_Schema::factory($dsn, $options);
		if (PEAR::IsError($schema)) {
			return new Jaws_Error($schema->getMessage(), 'SQL Schema', JAWS_ERROR_ERROR);
			//return $schema->getMessage();
		}

		$DBDef = $schema->getDefinitionFromDatabase();
		if (PEAR::isError($DBDef)) {
			return new Jaws_Error($DBDef->getMessage(), 'SQL Schema', JAWS_ERROR_ERROR);
			//return $DBDef->getMessage();
		}
		
        // get initialization data
        $tables = array();
		if (isset($DBDef['tables']) && is_array($DBDef['tables'])) {
            foreach ($DBDef['tables'] as $table_name => $table) {
				// make sure prefix matches
				$continue_table = true;
				if ($table_prefix == '') {
					$prefix = substr($table_name, 0, 1);
					if (is_numeric($prefix)) {
						$continue_table = false;
					}
				}
				if (substr($table_name, 0, strlen($table_prefix)) == $table_prefix && strpos($table_name, "ads_impressions") === false && strpos($table_name, "phpjobscheduler_logs") === false && strpos($table_name, "country") === false && $continue_table === true) {	
					$tables[] = $table_name;
				}
			}
		}
		$tables = implode(" ", $tables);
		$command = "mysqldump --opt -h $dbhost -u $dbuser -p $dbpass --compact --add-drop-table --add-locks $dbname $tables > $backupFile";
		exec($command);
		*/
		$model = $GLOBALS['app']->LoadGadget('ControlPanel', 'AdminModel');
		$result = $model->packData($filename, $num);
		if (Jaws_Error::IsError($result)) {
			$error = $result->GetMessage();
			$GLOBALS['app']->Session->PushLastResponse($error, RESPONSE_ERROR);
		} else {
			$GLOBALS['app']->Session->PushLastResponse(_t('CONTROLPANEL_BACKUP_CREATED'), RESPONSE_NOTICE);
		}
		usleep(100);
		if ($result === true || !is_numeric($result)) {
			echo "<script type=\"text/javascript\">location.href='" . BASE_SCRIPT . "?gadget=ControlPanel&action=DatabaseBackup';</script>";
			echo "<noscript><h1>Success</h1><a href=\"" . BASE_SCRIPT . "?gadget=ControlPanel&action=DatabaseBackup\">Click Here to Continue</a> if your browser does not redirect automatically.</noscript>";
		} else {
			echo "<script type=\"text/javascript\">location.href='" . BASE_SCRIPT . "?gadget=ControlPanel&action=DBBackup&num=".$result."&file=".urlencode($filename)."';</script>";
			echo "<noscript><h1>Success</h1><a href=\"" . BASE_SCRIPT . "?gadget=ControlPanel&action=DBBackup&num=".$result."&file=".urlencode($filename)."\">Click Here to Continue</a> if your browser does not redirect automatically.</noscript>";
		}
	}
	
    /**
     * Restores a database backup (DB schema and data directory)
     *
     * @access public
     * @return string template content
     */
    function RestoreBackup()
    {
		$this->CheckPermission('DatabaseBackups', 'ControlPanel');
        set_time_limit(0);
		echo str_pad('',1024);  // minimum start for Safari
		for ($i=10; $i>0; $i--) {
			echo str_pad("&nbsp;<br>\n",8);
			// tag after text for Safari & Firefox
			// 8 char minimum for Firefox
			//ob_flush();
			flush();  // worked without ob_flush() for me
			sleep(1);
		}
		echo ".";
		ob_flush();
		flush();
		$request =& Jaws_Request::getInstance();
		$get = $request->get(array('file'), 'get');
        require_once JAWS_PATH . 'include/Jaws/Header.php';
		if (!empty($get['file']) && file_exists(JAWS_DATA . 'db/'.$get['file'])) {
			$model = $GLOBALS['app']->LoadGadget('ControlPanel', 'AdminModel');
			$result = $model->unpackData(substr($get['file'], 0, -4));
			if (Jaws_Error::IsError($result)) {
				$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
				//Jaws_Header::Location(BASE_SCRIPT . '?gadget=ControlPanel&action=DatabaseBackup');
			} else {
				$GLOBALS['app']->Session->PushLastResponse(_t('CONTROLPANEL_BACKUP_RESTORED'), RESPONSE_NOTICE);
			}
		} else {
			$GLOBALS['app']->Session->PushLastResponse(_t('CONTROLPANEL_BACKUP_NOTRESTORED'), RESPONSE_ERROR);
		}
		echo "<script type=\"text/javascript\">location.href='" . BASE_SCRIPT . "?gadget=ControlPanel&action=DatabaseBackup';</script>";
		echo "<noscript><h1>Success</h1><a href=\"" . BASE_SCRIPT . "?gadget=ControlPanel&action=DatabaseBackup\">Click Here to Continue</a> if your browser does not redirect automatically.</noscript>";
		//Jaws_Header::Location(BASE_SCRIPT . '?gadget=ControlPanel&action=DatabaseBackup');
	}
	
    /**
     * Returns the default help tip
     *
     * @access public
     * @return string template content
     * @todo rewrite to work with MDB2
     */
    function ShowTip()
    {
		$request =& Jaws_Request::getInstance();
		$get = $request->getRaw(array('tip'), 'get');
        
		// Load the template
        require_once JAWS_PATH . 'include/Jaws/Template.php';
        $tpl = new Jaws_Template('gadgets/ControlPanel/templates/');
        $tpl->Load('HelpTips.html');
		
		if (isset($get['tip']) && !empty($get['tip'])) {
			$tpl->SetBlock($get['tip']);
			$jms = $GLOBALS['app']->LoadGadget('Jms', 'AdminModel');
			$gadgets = $jms->GetGadgetsList(null, true, true);
			$script = '';
			// Show gadgets with permission
			foreach ($gadgets as $gadget => $tgadget) {
				if ($this->GetPermission('default', $gadget)) {
					$gadgetinfo = $GLOBALS['app']->loadGadget($gadget, 'Info');
					if (!Jaws_Error::IsError($gadgetinfo)) {
						$script .= "$$('.".$gadget."').each(function(element){element.style.display = '';});\n";
					}
				}
			}
			$tpl->SetVariable('script',$script);
			$tpl->SetVariable('referer',$GLOBALS['app']->GetSiteURL());
			$tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . "/");
			//$tpl->SetVariable('DPATH',JAWS_DPATH);
			$tpl->ParseBlock($get['tip']);
        }
		
		return $tpl->Get();
	}
	

    /**
     * Creates PDF of given URL
     *
     * @access public
     * @return string HTML content
     */
    function CreatePDFOfURL($url = '', $filename = '')
    {
		$request =& Jaws_Request::getInstance();
		if (empty($url)) {
			$url = $request->get('url', 'get');
			if (empty($url)) {
				$url = $request->get('url', 'post');
			}
		}
		if (empty($filename)) {
			$filename = $request->get('f', 'get');
			if (empty($filename)) {
				$filename = $request->get('f', 'post');
			}
		}		
		/*
		if (!empty($filename)) {
			ignore_user_abort(true); 
			set_time_limit(0);
			echo str_pad('',1024);  // minimum start for Safari
			// tag after text for Safari & Firefox
			// 8 char minimum for Firefox
			ob_flush();
			flush();  // worked without ob_flush() for me
			sleep(1);
		}
		*/
		
        
		if (empty($filename)) {
			$filename = false;
		} else {
			if (!is_dir(JAWS_DATA . 'files/pdf/')) {	
				if (!Jaws_Utils::mkdir(JAWS_DATA . 'files/pdf/')) {
					return new Jaws_Error(_t('GLOBAL_ERROR_FAILED_CREATING_DIR', JAWS_DATA . 'files/pdf/'), _t('FILEBROWSER_NAME'));
				}
			}
			if (!Jaws_Utils::is_writable(JAWS_DATA . 'files/pdf/')) {
				return new Jaws_Error(_t('GLOBAL_ERROR_FAILED_DIRECTORY_UNWRITABLE', JAWS_DATA . 'files/pdf/'));
			}
			$filename = JAWS_DATA . 'files/pdf/'.$filename;
		}
						
		if (substr($url, 0, 1) == '/') {
			$url = substr($url, 1, strlen($url));
		}					

		if (substr(strtolower($url), 0, 5) == 'index') {
			$url = $GLOBALS['app']->GetSiteURL('/'.$url);
		}					

		if (
			!empty($url) && 
			(substr($url, 0, strlen($GLOBALS['app']->GetSiteURL('', false, 'http'))) == $GLOBALS['app']->GetSiteURL('', false, 'http') || 
			substr($url, 0, strlen($GLOBALS['app']->GetSiteURL('', false, 'https'))) == $GLOBALS['app']->GetSiteURL('', false, 'https'))
		) {
			$result = Jaws_Utils::CreatePDF($url, '', $filename, '', 'A4');
			if ($result !== true || ($filename !== false && !file_exists(JAWS_DATA . 'files/pdf/'.$filename))) {
				return new Jaws_Error("Couldn't create PDF of URL: ".$url, _t('CONTROLPANEL_NAME'));
			}
		}
		if ($filename !== false) {
			return '<p><strong>Success</strong>PDF of URL: '.$url.' saved successfully to: <a href="'.$GLOBALS['app']->getDataURL('files/pdf/'.$filename, true).'">'.JAWS_DATA . 'files/pdf/'.$filename.'</a></p>';
		}
	}

    /**
     * Creates PDF of given URL
     *
     * @access public
     * @return string HTML content
     */
    function CreatePDFsOfAllURLs($urls = array(), $save = '')
    {
		$request =& Jaws_Request::getInstance();
		if (empty($save)) {
			$save = $request->get('save', 'get');
			if (empty($save)) {
				$save = $request->get('save', 'post');
			}
		}
		if (empty($save)) {
			$save = false;
		} else {
			$save = true;
		}
		/*
		if ($save === true) {
			ignore_user_abort(true); 
			set_time_limit(0);
			echo str_pad('',1024);  // minimum start for Safari
			// tag after text for Safari & Firefox
			// 8 char minimum for Firefox
			ob_flush();
			flush();  // worked without ob_flush() for me
			sleep(1);
		}
		*/
		if (count($urls) <= 0) {
			$sql = '
				SELECT
					[id], [menu_type], [title], [url], [visible]
				FROM [[menus]]
				ORDER BY [menu_type] ASC, [title] ASC';
			
			$menus = $GLOBALS['db']->queryAll($sql);
			if (Jaws_Error::IsError($menus)) {
				return $menus;
			}
			$urls = array();
			if (is_array($menus)) {
				foreach ($menus as $menu => $m) {
					if (substr($m['url'], 0, 1) == '/') {
						$m['url'] = substr($m['url'], 1, strlen($m['url']));
					}
					if (substr(strtolower($m['url']), 0, 5) == 'index') {
						$m['url'] = $GLOBALS['app']->GetSiteURL('/'.$m['url']);
					}					
					// Skip if already added, has javascript: prefix, or is outside site's domain
					if (
						!in_array($m['url'], $urls) && 
						(strpos($m['url'], 'page/') !== false || strpos($m['url'], 'CustomPage') !== false) && 
						substr($m['url'], 0, 11) != 'javascript:' && 
						(substr($m['url'], 0, strlen($GLOBALS['app']->GetSiteURL('', false, 'http'))) != $GLOBALS['app']->GetSiteURL('', false, 'http') ||  
						substr($m['url'], 0, strlen($GLOBALS['app']->GetSiteURL('', false, 'https'))) != $GLOBALS['app']->GetSiteURL('', false, 'https')) 
					) {
						$urls[] = $m['url'];
					}
				}
			}
		}
		
		$return = '';
		$i = 1;
		foreach ($urls as $url) {
			$output = "\n"."<script type=\"text/javascript\" language=\"JavaScript\">";
			$output .= "setTimeout(\"window.open('".$url."');\", ".($i*5000).");";
			$output .= "</script>"."\n"; 
			$return .= $output;
			/*
			$filename = ($save === true ? time().'_'.$i.'.pdf' : $save);
			$result = $this->CreatePDFOfURL($url, $filename);
			if (Jaws_Error::IsError($result)) {
				$return .= '<p>'.$result->GetMessage().'</p>';
			} else {
				$return .= $result;
			}
			$i++;
			*/
		}
		return $return;
	}
    
	/**
     * View site statistics.
     *
     * @category 	feature
     * @access 	public
     * @return 	string HTML content
     */
    function Statistics($account = false, $OwnerID = null, $fusegadget = '', $fuseaction = '', $fuselinkid = '')
    {
		require_once JAWS_PATH .'libraries/googleanalytics/analytics.class.php';
		$request =& Jaws_Request::getInstance();
		
		// TODO: registry keys for this
		$username = "valkoun@gmail.com";
		$password = "renea82";
		$profileID = "59747120";
		$startdate = date('Y-m-d', mktime(0, 0, 0, date("m")-6, date("d"), date("Y")));
		$enddate = date('Y-m-d');
		
		$host = str_replace(array('http://', 'https://'), '', $GLOBALS['app']->GetSiteURL());
		$base_host = (strpos($host, '/') !== false ? substr($host, 0, strpos($host, '/')) : $host);
		$subdir = str_replace($base_host, '', $host);
		$page_prefix = 'ga:pagePath%3D~%5E/cdn/.*';
		if (!empty($subdir)) {
			$subdir = urlencode($subdir);
			$page_prefix .= ',ga:pagePath%3D~%5E'.$subdir.'.*';
		}
		
		if (empty($fusegadget)) {
			$fusegadget = $request->get('fusegadget', 'get');
			if (empty($fusegadget)) {
				$fusegadget = $request->get('fusegadget', 'post');
			}
		}
		if (empty($fuseaction)) {
			$fuseaction = $request->get('fuseaction', 'get');
			if (empty($fuseaction)) {
				$fuseaction = $request->get('fuseaction', 'post');
			}
		}
		if (empty($fuselinkid)) {
			$fuselinkid = $request->get('fuselinkid', 'get');
			if (empty($fuselinkid)) {
				$fuselinkid = $request->get('fuselinkid', 'post');
			}
		}
		
		if (!empty($fusegadget)) {
			$page_prefix = '';
			$alias_pages = array();
			if (!empty($fuseaction) && !empty($fuselinkid)) {
				// Get possible URLs this request can have
				$fast_urls = false;
				$hook = $GLOBALS['app']->loadHook($fusegadget, 'URLList');
				if ($hook !== false) {
					if (method_exists($hook, 'GetAllFastURLsOfRequest')) {
						$fast_urls = $hook->GetAllFastURLsOfRequest($fuseaction, $fuselinkid);
					}
				}
				if (is_array($fast_urls) && !count($fast_urls) <= 0) {
					foreach ($fast_urls as $f_url) {
						if (!in_array('index.php?gadget='.$fusegadget.'&action='.$fuseaction.'&id='.$f_url, $alias_pages)) {
							$alias_pages[] = 'index.php?gadget='.$fusegadget.'&action='.$fuseaction.'&id='.$f_url;
						}
						$get_url = $GLOBALS['app']->Map->GetURLFor($fusegadget, $fuseaction, array('id' => $f_url));
						if (!in_array($get_url, $alias_pages)) {
							$alias_pages[] = $get_url;
						}
					}
				} else {
					$alias_pages[] = 'index.php?gadget='.$fusegadget.'&action='.$fuseaction.'&id='.$fuselinkid;
					$alias_pages[] = $GLOBALS['app']->Map->GetURLFor($fusegadget, $fuseaction, array('id' => $fuselinkid));
				}
			} else {
				if (!empty($fuseaction)) {
					$alias_pages[] = 'index.php?gadget='.$fusegadget.'&action='.$fuseaction;
				} else {
					$alias_pages[] = 'index.php?gadget='.$fusegadget;
				}
				// Get possible URLs this gadget can have
				$urlmap = $GLOBALS['app']->Map->GetMap();
				foreach ($urlmap as $map_gadget => $map_actions) {
					if ($map_gadget == $fusegadget) {
						foreach ($map_actions as $map_action => $maps) {
							if (empty($fuseaction) || (!empty($fuseaction) && strtolower($fuseaction) == strtolower($map_action))) {
								foreach ($maps as $map) {
									if (strpos($map['map'], '/') !== false) {
										$umap = substr($map['map'], 0, (strpos($map['map'], '/')+1));
										if (!in_array('index.php?'.$umap, $alias_pages)) {
											$alias_pages[] = 'index.php?'.$umap;
										}
									} else {
										// TODO: handle extensions better (.html, etc)
										if (!in_array('index.php?'.$map['map'].'.', $alias_pages)) {
											$alias_pages[] = 'index.php?'.$map['map'].'.';
										}
									}
								}
							}
						}
					}
				}
			}
			foreach ($alias_pages as $alias) {
				$page_prefix .= (!empty($page_prefix) ? ',' : '').'ga:pagePath%3D~%5E'.(!empty($subdir) ? $subdir : '').urlencode('/'.str_replace(array(';', ',', '\\'), array('\;', '\,', '\\\\'), $alias)).'.*';
			}
		}
		
		if (is_null($OwnerID)) {
			if ($account === false) {
				$OwnerID = 0;
			} else {
				$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');
			}
		}
		
		$filter = 'ga:hostname%3D~%5E'.$base_host.'.*'.(!empty($page_prefix) ? ';'.$page_prefix : '');
				
		// construct the class
		$oAnalytics = new analytics($username, $password);
		$oAnalytics->setProfileById('ga:'.$profileID);
		// set it up to use caching
		$oAnalytics->useCache();
		// set the date range
		//$oAnalytics->setMonth(date('n'), date('Y'));
		$oAnalytics->setDateRange($startdate, $enddate);
		
		$tpl = new Jaws_Template('gadgets/ControlPanel/templates/');
		$tpl->Load('Statistics.html');
		
		if (!$GLOBALS['app']->IsStandAloneMode()) {
			$GLOBALS['app']->Layout->AddScriptLink('https://www.google.com/jsapi');
			$GLOBALS['app']->Layout->AddHeadOther('<script type="text/javascript">google.load("visualization", "1", {packages:["controls","corechart"]});</script>');
			$tpl->SetBlock('cptitle');
			$tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL().'/');
			$tpl->ParseBlock('cptitle');
		} else {
			$tpl->SetBlock('header');
			$tpl->SetVariable('site_url', $GLOBALS['app']->GetSiteURL().'/');
			$tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL().'/');
			$tpl->ParseBlock('header');
		}
		
		$tpl->SetBlock('statistics');
		
		// Unique Pageviews
		$tpl->SetBlock('statistics/uniquepageviews');
		$result = $oAnalytics->getData(
			array(
				'dimensions' => 'ga:date',
				'metrics'    => 'ga:uniquePageviews',
				'filters'    => $filter,
				'sort'    => 'ga:date'
			)
		);
		$i = 0;
		$dimension = array();
		$vals = array();
		// Add up day totals for each month
		foreach ($result as $key => $val) {
			$key = strtotime($key);
			$dimension[] = array("new Date(".date('Y', $key).", ".(date('n', $key)-1).", ".date('j', $key).")",$val);
			/*
			if (date('d', $key) == date('t', $key) || date('Y-m-d', $key) == date('Y-m-d', time())) {
				$dimension[] = array("{v:new Date(".date('Y', $key).", ".(date('n', $key)-1).", ".date('j', $key)."), f:'".date('M', $key).", ".date('Y', $key)."'}",($vals[$i]+$val));
				$i++;
			} else {
				$vals[$i] = ($vals[$i]+$val);
			}
			*/
		}	
		$metric = array(
			array("'date'", "'Date'"),
			array("'number'", "'Unique Pageviews'")
		);
		$uniquepageviews = $this->_graph($dimension, $metric, 'Unique Pageviews:', true, 'AreaChart', ", focusTarget: 'category'"); 
		$tpl->SetVariable('graph', $uniquepageviews);
		$tpl->ParseBlock('statistics/uniquepageviews');
		
		
		// Unique Visitors
		$tpl->SetBlock('statistics/uniquevisitors');
		$result = $oAnalytics->getData(
			array(
				'dimensions' => 'ga:date',
				'metrics'    => 'ga:visitors', 
				'filters'    => $filter,
				'sort'    => 'ga:date'
			)
		);
		$i = 0;
		$dimension = array();
		$vals = array();
		// Add up day totals for each month
		foreach ($result as $key => $val) {
			$key = strtotime($key);
			$dimension[] = array("new Date(".date('Y', $key).", ".(date('n', $key)-1).", ".date('j', $key).")",$val);
		}		
		$metric = array(
			array("'date'", "'Date'"),
			array("'number'", "'Unique Visitors'")
		);
		$uniquevisitors = $this->_graph($dimension, $metric, 'Unique Visitors:', true, 'AreaChart', ", focusTarget: 'category'"); 
		$tpl->SetVariable('graph', $uniquevisitors);
		$tpl->ParseBlock('statistics/uniquevisitors');
		
		
		// Total Visits
		$tpl->SetBlock('statistics/visits');
		$result = $oAnalytics->getData(
			array(
				'dimensions' => 'ga:date',
				'metrics'    => 'ga:visits', 
				'filters'    => $filter,
				'sort'    => 'ga:date'
			)
		);
		$i = 0;
		$dimension = array();
		$vals = array();
		// Add up day totals for each month
		foreach ($result as $key => $val) {
			$key = strtotime($key);
			$dimension[] = array("new Date(".date('Y', $key).", ".(date('n', $key)-1).", ".date('j', $key).")",$val);
		}		
		$metric = array(
			array("'date'", "'Date'"),
			array("'number'", "'Visits'")
		);
		$visits = $this->_graph($dimension, $metric, 'Visits:', true, 'AreaChart', ", focusTarget: 'category'"); 
		$tpl->SetVariable('graph', $visits);
		$tpl->ParseBlock('statistics/visits');
		
		
		// Total Pageviews
		$tpl->SetBlock('statistics/pageviews');
		$result = $oAnalytics->getData(
			array(
				'dimensions' => 'ga:date',
                'metrics'    => 'ga:pageviews', 
				'filters'    => $filter,
				'sort'    => 'ga:date'
			)
		);
		$i = 0;
		$dimension = array();
		$vals = array();
		// Add up day totals for each month
		foreach ($result as $key => $val) {
			$key = strtotime($key);
			$dimension[] = array("new Date(".date('Y', $key).", ".(date('n', $key)-1).", ".date('j', $key).")",$val);
			/*
			if (date('d', $key) == date('t', $key) || date('Y-m-d', $key) == date('Y-m-d', time())) {
				$dimension[] = array("{v:new Date(".date('Y', $key).", ".(date('n', $key)-1).", ".date('j', $key)."), f:'".date('M', $key).", ".date('Y', $key)."'}",($vals[$i]+$val));
				$i++;
			} else {
				$vals[$i] = ($vals[$i]+$val);
			}
			*/
		}		
		$metric = array(
			array("'date'", "'Date'"),
			array("'number'", "'Pageviews'")
		);
		$pageviews = $this->_graph($dimension, $metric, 'Pageviews:', true, 'AreaChart', ", focusTarget: 'category'"); 
		$tpl->SetVariable('graph', $pageviews);
		$tpl->ParseBlock('statistics/pageviews');
		
		
		// Visits per Hour
		$tpl->SetBlock('statistics/visitsperhour');
        $result = $oAnalytics->getData(
			array( 
				'dimensions' => 'ga:hour',
				'metrics'    => 'ga:visits',
				'filters'    => $filter,
				'sort'       => 'ga:hour'
			)
		);
		$dimension = array();
		foreach ($result as $key => $val) {
			$dimension[] = array("'".stripslashes($key)."'",$val);
		}
		$metric = array(
			array("'string'", "'Hour'"),
			array("'number'", "'Visits'")
		);
		$visitsperhour = $this->_graph($dimension, $metric, 'Visits per Hour:', false, 'AreaChart', ", focusTarget: 'category'"); 
		$tpl->SetVariable('graph', $visitsperhour);
		$tpl->ParseBlock('statistics/visitsperhour');
		
		
		// Pages
		if (empty($fuselinkid)) {
			$tpl->SetBlock('statistics/pages');
			$result = $oAnalytics->getData(
				array( 
					'dimensions' => 'ga:hostname,ga:pagePath',
					'metrics'    => 'ga:pageviews',
					'filters'    => $filter,
					'max-results' => 20,
					'sort'       => '-ga:pageviews'
				)
			);
			$dimension = array();
			foreach ($result as $key => $val) {
				$dimension[] = array("'".stripslashes($key)."'",$val);
			}
			$metric = array(
				array("'string'", "'Page'"),
				array("'number'", "'Pageviews'")
			);
			$pages = $this->_graph($dimension, $metric, 'Pages:', false, 'PieChart', ", is3D: true, height: 500, legend: {textStyle: {fontSize: 9}}"); 
			$tpl->SetVariable('graph', $pages);
			$tpl->ParseBlock('statistics/pages');
		}
		
		// Browsers
		$tpl->SetBlock('statistics/browsers');
        $result = $oAnalytics->getData(
			array(  
				'dimensions' => 'ga:browser,ga:browserVersion',
				'metrics'    => 'ga:visits',
				'filters'    => $filter,
				'max-results' => 20,
				'sort'       => '-ga:visits'
			)
		);             
        arsort($result);
		$result = $oAnalytics->getBrowsers();
		$dimension = array();
		foreach ($result as $key => $val) {
			$dimension[] = array("'".stripslashes($key)."'",$val);
		}
		$metric = array(
			array("'string'", "'Browser'"),
			array("'number'", "'Visits'")
		);
		$browsers = $this->_graph($dimension, $metric, 'Browsers:', false, 'PieChart', ", is3D: true, height: 500, legend: {/*title: 'Year',  */textStyle: {fontSize: 9}}"); 
		$tpl->SetVariable('graph', $browsers);
		$tpl->ParseBlock('statistics/browsers');
		
		
		// Referrers
		$tpl->SetBlock('statistics/referrer');
        $result = $oAnalytics->getData(
			array(   
				'dimensions' => 'ga:source',
				'metrics'    => 'ga:visits',
				'filters'    => $filter,
				'max-results' => 20,
				'sort'       => '-ga:visits'
			)
		);
        arsort($result);
		$dimension = array();
		foreach ($result as $key => $val) {
			$dimension[] = array("'".stripslashes($key)."'",$val);
		}
		$metric = array(
			array("'string'", "'Referrer'"),
			array("'number'", "'Visits'")
		);
		$referrer = $this->_graph($dimension, $metric, 'Referrer:', false, 'PieChart', ", is3D: true, height: 500, legend: {/*title: 'Year',  */textStyle: {fontSize: 9}}"); 
		$tpl->SetVariable('graph', $referrer);
		$tpl->ParseBlock('statistics/referrer');
		
		
		// Search words
		$tpl->SetBlock('statistics/searchwords');
        $result = $oAnalytics->getData(
			array(   
				'dimensions' => 'ga:keyword',
				'metrics'    => 'ga:visits',
				'filters'    => $filter,
				'max-results' => 20,
				'sort'       => '-ga:visits'
			)
		);
        arsort($result);
		$dimension = array();
		foreach ($result as $key => $val) {
			$dimension[] = array("'".stripslashes($key)."'",$val);
		}
		$metric = array(
			array("'string'", "'Search words'"),
			array("'number'", "'Searches'")
		);
		$searchwords = $this->_graph($dimension, $metric, 'Search words:', false, 'PieChart', ", is3D: true, height: 500, legend: {/*title: 'Year',  */textStyle: {fontSize: 9}}"); 
		$tpl->SetVariable('graph', $searchwords);
		$tpl->ParseBlock('statistics/searchwords');
		
		
		// Screen resolution
		$tpl->SetBlock('statistics/screenresolution');
        $result = $oAnalytics->getData(
			array(   
				'dimensions' => 'ga:screenResolution',
				'metrics'    => 'ga:visits',
				'filters'    => $filter,
				'max-results' => 20,
				'sort'       => '-ga:visits'
			)
		);
        arsort($result);
		$dimension = array();
		foreach ($result as $key => $val) {
			$dimension[] = array("'".stripslashes($key)."'",$val);
		}
		$metric = array(
			array("'string'", "'Screen resolution'"),
			array("'number'", "'Visits'")
		);
		$screenresolution = $this->_graph($dimension, $metric, 'Screen resolution:', false, 'PieChart', ", is3D: true, height: 500, legend: {/*title: 'Year',  */textStyle: {fontSize: 9}}"); 
		$tpl->SetVariable('graph', $screenresolution);
		$tpl->ParseBlock('statistics/screenresolution');
		
		
		// Operating System
		$tpl->SetBlock('statistics/operatingsystem');
        $result = $oAnalytics->getData(
			array(   
				'dimensions' => 'ga:operatingSystem',
				'metrics'    => 'ga:visits',
				'filters'    => $filter,
				'max-results' => 20,
				'sort'       => '-ga:visits'
			)
		);
        // sort descending by number of visits
        arsort($result);
		$dimension = array();
		foreach ($result as $key => $val) {
			$dimension[] = array("'".stripslashes($key)."'",$val);
		}
		$metric = array(
			array("'string'", "'Operating system'"),
			array("'number'", "'Visits'")
		);
		$operatingsystem = $this->_graph($dimension, $metric, 'Operating system:', false, 'PieChart', ", is3D: true, height: 500, legend: {/*title: 'Year',  */textStyle: {fontSize: 9}}"); 
		$tpl->SetVariable('graph', $operatingsystem);
		$tpl->ParseBlock('statistics/operatingsystem');
		
		$tpl->ParseBlock('statistics');
		
		if ($GLOBALS['app']->IsStandAloneMode()) {
			$tpl->SetBlock('footer');
			$tpl->ParseBlock('footer');
		}
		
		return $tpl->Get();
	}

	/**
	* Basic display for graphs
	* 
	* @param array $dimension
	* @TODO 	Move this to Jaws_Utils::GoogleVisualization!!!
	*/
	function _graph($dimension = array(), $metric = array(), $title = '', $range = false, $chart = 'AreaChart', $options = ''){		
		$iMax = max($dimension);
		$safe_title = ereg_replace("[^A-Za-z0-9]", '', (strtolower($title)));
		$return = "<script type=\"text/javascript\">\n";
		if ($iMax == 0){
			$return .= "Event.observe(window, 'load', function(){if(\$('".$safe_title."')){\$('".$safe_title."').innerHTML = '";
			$return .= "<table class=\"syntactsCategories\" width=\"100%\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
			$return .= "<tbody><tr class=\"syntactsCategories_no_items\"><td class=\"syntacts-form-row\"><p>No data</p></td></tr></tbody></table>';}});\n";
		/*
		} else if ($iMax == 1) {
			echo "document.getElementById('".$safe_title."').innerHTML = '".."';";
		*/
		} else {
			$col_count = count($metric[0]);
			$col_string = '';
			for ($i=1; $i<$col_count;$i++) {
				$col_string .= ', '.$i;
			}
			if ($range === true) {
				$return .= "function drawVisualization".$safe_title."() {
					var dashboard = new google.visualization.Dashboard(
					   document.getElementById('".$safe_title."'));

					var control = new google.visualization.ControlWrapper({
					 'controlType': 'ChartRangeFilter',
					 'containerId': 'control".$safe_title."',
					 'options': {
					   // Filter by the date axis.
					   'filterColumnIndex': 0,
					   'ui': {
						 'chartType': 'LineChart',
						 'chartOptions': {
						   'height': 50,
						   /*'chartArea': {'width': '90%'},*/
						   'hAxis': {'baselineColor': 'none'}
						 },
						 // Display a single series of first two columns
						 'chartView': {
						   'columns': [0".$col_string."]
						 },
						 // 1 day in milliseconds = 24 * 60 * 60 * 1000 = 86,400,000
						 'minRangeSize': 86400000
					   }
					 },
					 // Initial range
					 'state': {'range': {'start': new Date(".date('Y', mktime(0, 0, 0, date("m")-1, date("d"), date("Y"))).", ".(date('n', mktime(0, 0, 0, date("m")-1, date("d"), date("Y")))-1).", ".date('j', mktime(0, 0, 0, date("m")-1, date("d"), date("Y")))."), 'end': new Date(".date('Y').", ".(date('n')-1).", ".date('j').")}}
					});

					var chart = new google.visualization.ChartWrapper({
					 'chartType': '".$chart."',
					 'containerId': 'chart".$safe_title."',
					 'options': {
					   'height': 300,
					   /*
					   'chartArea': {'height': '80%', 'width': '90%'},
					   */
					   'hAxis': {'slantedText': false, textStyle: {fontSize: 10}},
					   'legend': {'position': 'none'}".$options."
					 },
					 // Display a single series of first two columns
					 // Convert the first column from 'date' to 'string'.
					 'view': {
					   'columns': [
						 {
						   'calc': function(dataTable, rowIndex) {
							 return dataTable.getFormattedValue(rowIndex, 0);
						   },
						   'type': 'string'
						 }".$col_string."]
					 }
					});
				";
			} else {
				$return .= "function drawChart".$safe_title."() {\n";
			}
			$return .= "var data = new google.visualization.DataTable();\n";
			foreach ($metric as $m) {
				$return .= "data.addColumn(".implode(',',$m).");\n";
			}
			foreach ($dimension as $d) {
				$return .= "data.addRow([".implode(',',$d)."]);\n";
			}
			if ($range === true) {
				$return .= "dashboard.bind(control, chart);\n
					dashboard.draw(data);\n
				}
				google.setOnLoadCallback(drawVisualization".$safe_title.");\n";
			} else {
				$return .= "var options = {
					  title: '',focusTarget:'category'".$options."
					};
					var chart = new google.visualization.".$chart."(document.getElementById('".$safe_title."'));\n
					chart.draw(data, options);\n
				}
				google.setOnLoadCallback(drawChart".$safe_title.");\n";
			}
		}
		$return .= "</script>
		";
		return $return;
	}
}	
