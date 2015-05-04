<?php
/**
 * Users Gadget (layout actions for client side)
 *
 * @category   Gadget
 * @package    Users
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2007-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class UsersLayoutHTML 
{

    /**
     * Loads layout actions
     *
     * @access private
     */
    function LoadLayoutActions()
    {
        $actions = array();
        
		$actions['LoginBox']       = array(
			'mode' => 'LayoutAction', 
			'name' => _t('USERS_LAYOUT_LOGINBOX'), 
			'desc' => _t('USERS_LAYOUT_LOGINBOX_DESC')
		);
		$actions['LoginBar']       = array(
			'mode' => 'LayoutAction', 
			'name' => _t('USERS_LAYOUT_LOGINBAR'), 
			'desc' => _t('USERS_LAYOUT_LOGINBAR_DESC')
		);
		$actions['LoginLinks']       = array(
			'mode' => 'LayoutAction', 
			'name' => _t('USERS_LAYOUT_LOGINLINKS'), 
			'desc' => _t('USERS_LAYOUT_LOGINLINKS_DESC')
		);

		$actions['UserAdvancedFilter'] = array(
			'mode' => 'LayoutAction',
			'name' => _t('USERS_LAYOUT_ADVANCED_FILTER'),
			'desc' => _t('USERS_LAYOUT_ADVANCED_FILTER_DESCRIPTION')
		);
        
		$actions['ShowFollowButtons'] = array(
			'mode' => 'LayoutAction',
			'name' => _t('USERS_LAYOUT_FOLLOW_BUTTONS'),
			'desc' => _t('USERS_LAYOUT_FOLLOW_BUTTONS_DESCRIPTION')
		);
        
		if ($GLOBALS['app']->Registry->Get('/config/anon_register') == 'true') {
			$actions['Register(null)'] = array(
				'mode' => 'LayoutAction',
				'name' => _t('USERS_LAYOUT_REGISTERDEFAULT'),
				'desc' => _t('USERS_LAYOUT_REGISTERDEFAULT_DESCRIPTION')
			);
		}
		$actions['ShowFiveUsersOfGroup'] = array(
			'mode' => 'LayoutAction',
			'name' => _t('USERS_LAYOUT_FIVEUSERSOFGROUP'),
			'desc' => _t('USERS_LAYOUT_FIVEUSERSOFGROUP_DESCRIPTION')
		);
		
		// Load comments of all gadgets
		$jms = $GLOBALS['app']->LoadGadget('Jms', 'AdminModel');
		$gadget_list = $jms->GetGadgetsList(null, true, true, true);
		if (count($gadget_list) <= 0) {
			Jaws_Error::Fatal('You don\'t have any installed gadgets, please enable/install one and then come back',
							 __FILE__, __LINE__);
		}
		reset($gadget_list);
	
		foreach ($gadget_list as $g) {
			$hook = $GLOBALS['app']->loadHook($g['realname'], 'Recommendation');
			if ($hook !== false) {
				if (method_exists($hook, 'GetRecommendations')) {
					$actions['ShowRecommendations("' . $g['realname'] . '")'] = array(
						'mode' => 'LayoutAction',
						'name' => _t('USERS_LAYOUT_RECOMMENDATIONS', $g['realname']),
						'desc' => _t('USERS_LAYOUT_RECOMMENDATIONS_DESCRIPTION')
					);
				}
			}
			$hook = $GLOBALS['app']->loadHook($g['realname'], 'Comment');
			if ($hook !== false) {
				if (method_exists($hook, 'GetComments')) {
					$actions['ShowComments("' . $g['realname'] . '")'] = array(
						'mode' => 'LayoutAction',
						'name' => _t('USERS_LAYOUT_COMMENTS', $g['realname']),
						'desc' => _t('USERS_LAYOUT_COMMENTS_DESCRIPTION')
					);
				}
			}
		}
		
		require_once JAWS_PATH . 'include/Jaws/User.php';
		$uModel = new Jaws_User;
		$groups = $uModel->GetAllGroups();

		if ($groups) {
			foreach ($groups as $group) {
				$groupName = (strpos($group['name'], '_') !== false ? ucfirst(str_replace('_', ' ', $group['name'])) : ucfirst($group['name']));
				if ($GLOBALS['app']->Registry->Get('/config/anon_register') == 'true' && $group['name'] != 'users') {
					$actions['Register("' . $group['name'] . '")'] = array(
						'mode' => 'LayoutAction',
						'name' => _t('USERS_LAYOUT_REGISTER', $groupName),
						'desc' => _t('USERS_LAYOUT_REGISTER_DESCRIPTION', $groupName)
					);
				}
				$actions['ShowFiveUsersOfGroup(' . $group['id'] . ')'] = array(
					'mode' => 'LayoutAction',
					'name' => _t('USERS_LAYOUT_FIVEUSERSOFGROUP', $groupName),
					'desc' => _t('USERS_LAYOUT_FIVEUSERSOFGROUP_DESCRIPTION', $groupName)
				);
				$actions['ShowCommentsOfGroup(' . $group['id'] . ')'] = array(
					'mode' => 'LayoutAction',
					'name' => _t('USERS_LAYOUT_COMMENTS', $groupName),
					'desc' => _t('USERS_LAYOUT_COMMENTS_DESCRIPTION', $groupName)
				);
			}
		}
		$actions['ShowUserInfo'] = array(
			'mode' => 'LayoutAction',
			'name' => _t('USERS_LAYOUT_USERINFO'),
			'desc' => _t('USERS_LAYOUT_USERINFO_DESCRIPTION')
		);
		$users = $uModel->GetUsers();
		if (!Jaws_Error::IsError($users)) {
			foreach ($users as $user) {
				$actions['ShowUserInfo(' . $user['username'] . ')'] = array(
					'mode' => 'LayoutAction',
					'name' => _t('USERS_LAYOUT_USERINFO', "of ". $user['nickname']),
					'desc' => _t('USERS_LAYOUT_USERINFO_DESCRIPTION', "of ". $user['nickname'])
				);
			}
		}
		
        return $actions;
    }

    /**
     * Calls Login box
     *
     * @access public
     * @return string template content
     */
    function LoginBox()
    {
		$res = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		return $res->LoginForm();
    }

    /**
     * Calls Login bar
     *
     * @access public
     * @return string template content
     */
    function LoginBar()
    {
		$res = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		return $res->LoginForm(true);
    }

    /**
     * Displays hyperlinks to User sections
     *
     * @access public
     * @return XHTML link if not logged, else welcome message
     */
    function LoginLinks()
    {
        require_once JAWS_PATH . 'include/Jaws/Widgets/Menubar.php';
        $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $menubar = new Jaws_Widgets_Menubar('login');
		$request =& Jaws_Request::getInstance();
		$get = $request->get(array('gadget', 'action', 'id'), 'get');
		require_once JAWS_PATH . 'include/Jaws/User.php';
		$uModel = new Jaws_User;
		$model  = $GLOBALS['app']->LoadGadget('Users', 'Model');
        if (!$GLOBALS['app']->Session->Logged()) {
            /*
			$menubar->AddOption('AdminUser', _t('GLOBAL_ADMINLINK_LOGIN'),
                                'admin.php');
			*/
            $menubar->AddOption('User', _t('GLOBAL_USERLINK_LOGIN'),
                                $GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'));
			if ($get['gadget'] == 'Users' && $get['action'] == 'GroupPage' && (!empty($get['id']) && !$uModel->UserIsInGroup($GLOBALS['app']->Session->GetAttribute('user_id'), (int)$get['id']))) {
				$menubar->AddOption('Group', _t('GLOBAL_GROUPLINK_JOIN'),
                                'index.php?gadget=Users&action=Registration&group='.$get['id']);
			}
		} else {
			$uInfo = $uModel->GetUserInfoById((int)$GLOBALS['app']->Session->GetAttribute('user_id'), true, true, true, true);
			if (!Jaws_Error::isError($uInfo) && isset($uInfo['id']) && !empty($uInfo['id'])) {
				$main_image_src = $uModel->GetAvatar($uInfo['username'],$uInfo['email']);
				$user_name = (isset($uInfo['company']) && !empty($uInfo['company']) ? $uInfo['company'] : $uInfo['nickname']);
				
				$image_link = "<a target=\"_self\" href=\"".($GLOBALS['app']->Session->GetPermission('Users', 'EditAccountProfile') ? 
					$GLOBALS['app']->Map->GetURLFor('Users', 'Profile')."\" title=\""._t('USERS_EDIT_PROFILE') : 
					$GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction')."\" title=\""._t('GLOBAL_USERLINK_ACCOUNTHOME')
				)."\">";
				$image_link .= "<img alt=\"".$user_name."\" height=\"20\" width=\"20\" border=\"0\" src=\"".$main_image_src."\" />";
				$image_link .= "</a>";
				$menubar->AddOption('Avatar', $image_link);
				
				$link = "<a href=\"".$GLOBALS['app']->Map->GetURLFor('Users', 'Logout')."\" target=\"_self\">"._t('GLOBAL_LOGOUT')."</a>";
				$menubar->AddOption('Logged', $user_name.' ('.$link.')');
				/*
				$count = $model->GetTotalOfNewComments();
				$count_html = '';
				if ($count > 0) {
					$count_html = '&nbsp;<span style="text-decoration: none; font-size: 9px; color: #FFFFFF; background: #888888; padding: 1px 3px 1px 3px; font-weight: bold; border-radius: 5px">'.$count.'</span>';
				}
				*/
				$account_home_link = _t('GLOBAL_USERLINK_ACCOUNTHOME');
				/*
				$panes = $model->GetPanes();
				if ($GLOBALS['app']->_standAloneMode !== true && !Jaws_Error::IsError($panes) && is_array($panes) && !count($panes) <= 0) {
					$account_home_link = '<div style="text-align: center; float: right;" class="gadget menu loginlinks-sort-menu" id="loginlinks-sort-menu-1">
						<div class="content">
							<ul class="ul_top_menu" style="text-align: left;">
								<li id="loginlinks-sort-1" class="menu_li_item menu_first menu_super">
									<a href="'.$GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction').'" target="_self" class="menu_a loginlinks-title loginlinks-title-selected">
									'. _t('GLOBAL_USERLINK_ACCOUNTHOME') .' <!--[if gt IE 6]><!-->
									</a><!--<![endif]--> 
									<!--[if lte IE 6]><table><tr><td><![endif]-->
									<ul class="ul_sub_menu">';
					foreach ($panes as $p) {
						$account_home_link .= '<li id="loginlinks-sort-'.$p['id'].'" class="menu_li_item">
							<a href="'.$GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction').'#pane='.$p['id'].'" onclick="if(typeof(selectPane) == \'function\'){selectPane(\''.$p['id'].'\');};" target="_self" class="sub_menu_a">'.$p['gadget'].'<!--[if gt IE 6]><!--></a><!--<![endif]-->
							<!--[if lte IE 6]><table><tr><td><![endif]--><!--[if lte IE 6]></td></tr></table></a><![endif]-->  
						</li>';
					}
					$account_home_link .= '</ul><!--[if lte IE 6]></td></tr></table></a><![endif]-->
								</li>
							</ul>
						</div>
					</div>';
					$menubar->AddOption('UserLogged', $account_home_link);
				} else {
				*/
					$menubar->AddOption('UserLogged', $account_home_link,
									$GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'));
				//}
				
                if ($GLOBALS['app']->Session->GetPermission('Users', 'EditAccountInformation')) {
					$menubar->AddOption('Account', _t('USERS_EDIT_ACCOUNT'),
									$GLOBALS['app']->Map->GetURLFor('Users', 'Account'));
                }

                if ($GLOBALS['app']->Session->GetPermission('Users', 'EditAccountProfile')) {
					$menubar->AddOption('Profile', _t('USERS_EDIT_PROFILE'),
									$GLOBALS['app']->Map->GetURLFor('Users', 'Profile'));
                }

                if ($GLOBALS['app']->Session->GetPermission('Users', 'EditAccountPreferences')) {
					$menubar->AddOption('Preferences', _t('USERS_EDIT_PREFERENCES'),
									$GLOBALS['app']->Map->GetURLFor('Users', 'Preferences'));
                }

				if ($get['gadget'] == 'Users' && $get['action'] == 'GroupPage' && (!empty($get['id']) && !$uModel->UserIsInGroup($GLOBALS['app']->Session->GetAttribute('user_id'), (int)$get['id']))) {
					$menubar->AddOption('Group', _t('GLOBAL_GROUPLINK_JOIN'),
									$GLOBALS['app']->Map->GetURLFor('Users', 'RequestGroupAccess', array('group' => $get['id'])));
				}
				/*
				// Groups of user
				$groups_html = '';
				$groups = $uModel->GetAllGroups();
				$ugroups = $uModel->GetGroupsOfUser($GLOBALS['app']->Session->GetAttribute('user_id'));
				$user_gadgets = explode(',', $GLOBALS['app']->Registry->Get('/gadgets/user_access_items'));
				$ugadgets = array();
				foreach ($user_gadgets as $gadget) {
					$ugadgets[] = strtolower($gadget);
				}
				
				// Check if user's groups match gadget
				foreach ($groups as $group) {
					$inGroup = false;
					$gadget = (strpos($group['name'], '_owners') !== false ? str_replace('_owners', '', $group['name']) : str_replace('_users', '', $group['name']));
					$gadget = strtolower($gadget);
					if ($gadget != 'users' && in_array($gadget, $ugadgets) && strpos($group['name'], '_owners') !== false) {
						foreach ($ugroups as $ugroup) {
							if ($ugroup['group_name'] == $group['name']) {
								$inGroup = true;
								break;
							}
						}
						if ($inGroup === false) {	
							$groups_html .= (empty($groups_html) ? '<b>Add Services:</b><br />' : '&nbsp;&nbsp;&nbsp;')."<a href=\'#\' onclick=\'addUserToGroup(".$GLOBALS['app']->Session->GetAttribute('user_id').",".$group['id']."); return false;\'>".ucfirst(str_replace('_owners','',$group['name']))."</a>";
						}
					}
				}
				
				$public_group_html = '';
				if (!empty($groups_html)) {
					$GLOBALS['app']->Layout->AddScriptLink('libraries/window/dist/window.js');
					$GLOBALS['app']->Layout->AddHeadLink('libraries/window/themes/window/window.css', 'stylesheet', 'text/css');
					$GLOBALS['app']->Layout->AddHeadLink('libraries/window/themes/window/simplewhite.css', 'stylesheet', 'text/css');
					$GLOBALS['app']->Layout->AddHeadLink('libraries/window/themes/shadow/mac_shadow.css', 'stylesheet', 'text/css');
					$window = "var w = new UI.Window({theme: 'simplewhite',height: 100,width: 450,shadow: true,minimize: false,maximize: false,close: 'destroy',resizable: false,draggable: true});w.setContent(String.fromCharCode(60)+'div'+String.fromCharCode(62)+'".$groups_html."'+String.fromCharCode(60)+'/div'+String.fromCharCode(62));w.adapt.bind(w).delay(0.3);w.show(true).focus();w.center();";
					$timestamp = '_'.time();
					$menubar->AddOption('Groups', _t('GLOBAL_USERLINK_SETTINGS')."&nbsp;<span style='display: inline; padding: 0px; font-stretch: expanded; vertical-align: middle; font-size: 0.5em; font-weight: bold;'>V</span>",
									"javascript:if (this.nodeName == 'LI'){".$window."}");
				}
				*/
			} else {
	            /*
				$menubar->AddOption('AdminUser', _t('GLOBAL_ADMINLINK_LOGIN'),
	                                'admin.php');
	            */
				$menubar->AddOption('User', _t('GLOBAL_USERLINK_LOGIN'),
	                                $GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'));
			}
		} 
        return $menubar->Get();

	}

    /**
     * Displays user registration form
     *
     * @access public
     * @return XHTML 
     */
    function Register($group = null)
    {
		$res = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		return $res->Registration($group);
	}
    
	/**
     * Displays info of username or ID
     *
     * @access public
     * @return XHTML 
     */
    function ShowUserInfo($username = '', $embedded = false, $referer = null)
    {
        $GLOBALS['app']->Layout->AddHeadLink('gadgets/Users/resources/style.css', 'stylesheet', 'text/css');
		require_once JAWS_PATH . 'include/Jaws/Image.php';
        $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$request =& Jaws_Request::getInstance();
        $get     = $request->get(array('name', 'gadget'), 'get');
		if (empty($username)) {
			$username = $get['name'];
		}
		//$this->AjaxMe('client_script.js');
		$output_html = '<div class="gadget accountpublic">';
		require_once JAWS_PATH . 'include/Jaws/User.php';
        $jUser = new Jaws_User;
		$info = $jUser->GetUserInfoByName($username, true, true, true, true);
		if (!Jaws_Error::IsError($info)) {
			if (!isset($info['id'])) {
				$info = $jUser->GetUserInfoByID((int)$username, true, true, true, true);
				if (!isset($info['id'])) {
					//require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
					//return Jaws_HTTPError::Get(404);
					return '';
				}
			}
			$groupInfo = $jUser->GetGroupInfoByName('profile');
			if (
				!$info['enabled'] || Jaws_Error::IsError($groupInfo) || !isset($groupInfo['id']) || empty($groupInfo['id']) || 
				!in_array($jUser->GetStatusOfUserInGroup($info['id'], $groupInfo['id']), array('active','founder','admin'))
			) {
				return '';
			}
			if (file_exists(JAWS_DATA . 'files/css/users/'.$info['id'].'/custom.css')) {
				$GLOBALS['app']->Layout->AddHeadOther('<link rel="stylesheet" media="screen" type="text/css" href="'.$GLOBALS['app']->getDataURL('', true). 'files/css/users/'.$info['id'].'/custom.css" />');
			}
			if ((isset($info['address']) && !empty($info['address'])) || (isset($info['address2']) && !empty($info['address2'])) || (isset($info['city']) && !empty($info['city'])) || (isset($info['region']) && !empty($info['region'])) || (isset($info['office']) && !empty($info['office'])) || (isset($info['tollfree']) && !empty($info['tollfree'])) || (isset($info['phone']) && !empty($info['phone'])) || (isset($info['fax']) && !empty($info['fax'])) || (isset($info['url']) && !empty($info['url']))) {
				$output_html .= "<div class=\"merchant-details\">";
				$output_html .= "<h3 class=\"merchant-details-header\">Basic Info</h3><div class=\"merchant-details-holder\">";
				if ((isset($info['address']) && !empty($info['address'])) || (isset($info['address2']) && !empty($info['address2'])) || (isset($info['city']) && !empty($info['city'])) || (isset($info['region']) && !empty($info['region']))) {
					$address_string = '';
					$output_html .= "<div class=\"merchant-item\" id=\"merchant-address\"><div class=\"merchant-label merchant-address-label\">Location:&nbsp;</div><div class=\"merchant-holder merchant-address-holder\">";
					if (isset($info['address']) && !empty($info['address'])) {
						$address_string .= $xss->filter($info['address']);
						$address_html .= "<span class=\"merchant-address-item merchant-address\">".$xss->filter($info['address'])."</span>";
					}
					if (isset($info['address2']) && !empty($info['address2'])) {
						$address_string .= ' '.$xss->filter($info['address2']);
						$address_html .= "<span class=\"merchant-address-item merchant-address2\">".$xss->filter($info['address2'])."</span>";
					}
					if (isset($info['city']) && !empty($info['city'])) {
						$address_string .= ', '.$xss->filter($info['city']).(isset($info['region']) && !empty($info['region']) ? ', '.$xss->filter($info['region']) : '').(isset($info['postal']) && !empty($info['postal']) ? ' '.$xss->filter($info['postal']) : '');
						$address_html .= "<span class=\"merchant-address-item merchant-city\">".$xss->filter($info['city']).(isset($info['region']) && !empty($info['region']) ? ', '.$xss->filter($info['region']) : '').(isset($info['postal']) && !empty($info['postal']) ? ' '.$xss->filter($info['postal']) : '')."</span>";
					} else if (isset($info['region']) && !empty($info['region'])) {
						$address_string .= ', '.$xss->filter($info['region']).(isset($info['postal']) && !empty($info['postal']) ? ' '.$xss->filter($info['postal']) : '');
						$address_html .= "<span class=\"merchant-address-item merchant-region\">".$xss->filter($info['region']).(isset($info['postal']) && !empty($info['postal']) ? ' '.$xss->filter($info['postal']) : '')."</span>";
					}
					$address_string = (substr($address_string, 0, 2) == ", " ? substr($address_string, 2, strlen($address_string)) : $address_string);
					$output_html .= (!empty($address_string) && (isset($info['address']) && !empty($info['address'])) ? '<a href="http://maps.google.com/maps?ie=UTF-8&q='.urlencode($address_string).'" target="_blank">' : '').$address_html.(!empty($address_string) && (isset($info['address']) && !empty($info['address'])) ? '</a>' : '')."</div></div>";
				}

				if (isset($info['office']) && !empty($info['office'])) {
					$output_html .= "<div class=\"merchant-item\" id=\"merchant-office\"><div class=\"merchant-label merchant-office-label\">Office Phone:&nbsp;</div><div class=\"merchant-holder merchant-office-holder\">";
					$output_html .= "<div class=\"merchant-office\">".$xss->filter($info['office'])."</div>";
					$output_html .= "</div></div>";
				}
				if (isset($info['tollfree']) && !empty($info['tollfree'])) {
					$output_html .= "<div class=\"merchant-item\" id=\"merchant-tollfree\"><div class=\"merchant-label merchant-tollfree-label\">Tollfree:&nbsp;</div><div class=\"merchant-holder merchant-tollfree-holder\">";
					$output_html .= "<div class=\"merchant-tollfree\">".$xss->filter($info['tollfree'])."</div>";
					$output_html .= "</div></div>";
				}
				if (isset($info['phone']) && !empty($info['phone'])) {
					$output_html .= "<div class=\"merchant-item\" id=\"merchant-phone\"><div class=\"merchant-label merchant-phone-label\">Phone:&nbsp;</div><div class=\"merchant-holder merchant-phone-holder\">";
					$output_html .= "<div class=\"merchant-phone\">".$xss->filter($info['phone'])."</div>";
					$output_html .= "</div></div>";
				}
				if (isset($info['fax']) && !empty($info['fax'])) {
					$output_html .= "<div class=\"merchant-item\" id=\"merchant-fax\"><div class=\"merchant-label merchant-fax-label\">Fax:&nbsp;</div><div class=\"merchant-holder merchant-fax-holder\">";
					$output_html .= "<div class=\"merchant-fax\">".$xss->filter($info['fax'])."</div>";
					$output_html .= "</div></div>";
				}
				if (isset($info['url']) && !empty($info['url'])) {
					$output_html .= "<div class=\"merchant-item\" id=\"merchant-website\"><div class=\"merchant-label merchant-website-label\">Website:&nbsp;</div><div class=\"merchant-holder merchant-website-holder\">";
					$website_link = (substr($info['url'], 0, 4) != 'http' && substr($info['url'], 0, 5) != 'https' ? 'http://' : '').$xss->filter($info['url']);
					$website_link = str_replace('"', '', $website_link);
					$website_link = str_replace("'", '', $website_link);
					$website = $xss->filter($info['url']);
					$output_html .= "<div class=\"merchant-website\"><a href=\"".$website_link."\" target=\"_blank\" class=\"merchant_link\">Click Here to Visit</a></div>";
					$output_html .= "</div></div>";
				}
				
				$title = '';
				if (isset($info['company']) && !empty($info['company'])) {
					$title = $xss->filter(strip_tags($info['company']));
				} else if (isset($info['nickname']) && !empty($info['nickname'])) {
					$title = $xss->filter(strip_tags($info['nickname']));
				}
				
				// E-mail Form
				if (Jaws_Gadget::IsGadgetUpdated('Forms') || $get['gadget'] == 'Users') {
					$link_click = "if(\$('merchant-form-content')){if(\$('merchant-form-content').style.display == 'none'){\$('merchant-form-content').style.display = 'block';}else{\$('merchant-form-content').style.display = 'none';}};";
					$link_click_text = "Click Here to Contact Us";
					if ($get['gadget'] == 'Users') {
						$username = $GLOBALS['app']->Session->GetAttribute('username');
						if (empty($username) || $username == 'anonymous') {
							//$GLOBALS['app']->Session->PushSimpleResponse('You must log-in to continue.');
							$submit =& Piwi::CreateWidget('Button', 'updateButton', "Sign-in To Comment", STOCK_ADD);
							$submit->SetStyle('min-width: 60px;');
							$submit->AddEvent(ON_CLICK, "javascript: location.href = '".$GLOBALS['app']->GetSiteURL() . '/index.php?gadget=Users&action=DefaultAction&redirect_to='.urlencode($GLOBALS['app']->GetFullURL())."';");
							$inquiry_form = '<div>'.$submit->Get().'</div>';
						} else {
							$submit =& Piwi::CreateWidget('Button', 'merchant-updateButton', _t('GLOBAL_ADD'), STOCK_ADD);
							$submit->SetStyle('min-width: 60px;');
							$submit->AddEvent(ON_CLICK, 'javascript: saveUpdate('.$info['id'].', ($(\'merchant-update-entry\').value.length > 0 ? $(\'merchant-update-entry\').value : \'\'), \'\', 0, ($(\'merchant-update-sharing-public\').checked ? \'everyone\' : \'owner\')); $(\'merchant-update-actions\').style.display = \'none\'; $(\'merchant-update-entry\').style.height = \'40px\'; $(\'merchant-update-entry\').style.color = \'#888888\'; $(\'merchant-update-entry\').value = \'Leave a comment...\';');
							$descriptionEntry=& Piwi::CreateWidget('TextArea', 'merchant-update-entry', 'Leave a comment...');
							$descriptionEntry->SetTitle('Leave a comment...');
							$descriptionEntry->AddEvent(ON_FOCUS, "javascript: if (\$('merchant-update-entry').value == 'Leave a comment...') {\$('merchant-update-actions').style.display = 'block'; \$('merchant-update-entry').value = ''; \$('merchant-update-entry').style.color = '#000000'; \$('merchant-update-entry').style.height = '100px';}");
							//$descriptionEntry->AddEvent(ON_BLUR, "javascript: if (\$('merchant-update-entry').value == '') {\$('merchant-update-actions').style.display = 'none'; \$('merchant-update-entry').style.height = '40px'; \$('merchant-update-entry').style.color = '#888888'; \$('merchant-update-entry').value = 'Leave a comment...';}");
							$descriptionEntry->SetStyle('color: #888888; height: 40px; width: 100%; min-width: 200px;');
							$inquiry_form = '<div>'.$descriptionEntry->Get().'</div>
							<div id="merchant-update-actions" style="display: none; clear: both; float: right; text-align: right;">
							<nobr><label for="merchant-update-sharing-public">Public</label>
							<input type="checkbox" checked="checked" name="sharing_public" id="merchant-update-sharing-public" value="true" />&nbsp;&nbsp;'.
							$submit->Get().'</nobr></div>
							<div style="display: block; clear: both; float: none; font-size: 0.0001em;">&nbsp;</div>';
						}
						
					} else if (Jaws_Gadget::IsGadgetUpdated('Forms')) {
						if (isset($info['email']) && !empty($info['email'])) {
							$formsLayout = $GLOBALS['app']->LoadGadget('Forms', 'LayoutHTML');
							$now = $GLOBALS['db']->Date();
							if (strrpos($GLOBALS['app']->GetSiteURL(), "/") > 8) {
								$site_url = substr($GLOBALS['app']->GetSiteURL(), 0, strrpos($GLOBALS['app']->GetSiteURL(), "/"));
							} else {
								$site_url = $GLOBALS['app']->GetSiteURL();		
							}
							$site_name = $GLOBALS['app']->Registry->Get('/config/site_name');
							$site_name = (empty($site_name) ? str_replace('https://', '', str_replace('http://', '', $site_url)) : $site_name);
							//$redirect = $GLOBALS['app']->GetSiteURL() . "/index.php?gadget=".$this->_Name."&action=Product&id=".$page['id'];
							$redirect = $GLOBALS['app']->Map->GetURLFor('Users', 'AccountPublic', array('name' => $info['username']));
							$redirect = (substr($redirect, 0, 4) != 'http' ? $site_url.'/'.$redirect : $redirect);
							
							$recipient = $info['email'];
							$inquiry_form = $formsLayout->Display(null, true, array('id' => 'custom', 'sort_order' => 0, 'title' => 'Contact Us', 
								'sm_description' => '', 'description' => "Send us your questions/comments.", 'clause' => '', 
								'image' => '', 'recipient' => $recipient, 'parent' => 0, 'custom_action' => '', 'fast_url' => '', 'active' => 'Y', 
								'ownerid' => 0, 'created' => $now, 'updated' => $now, 
								'submit_content' => "<div style='margin-bottom: 10px;'>Thank you for taking the time to contact us! We'll review your inquiry and get back to you when necessary.</div><div><a href='".$redirect."'>Click here to return to our profile</a>.</div>",
								'checksum' => ''),
								array(array('id' => 9, 'sort_order' => 0, 'formid' => 'custom', 
								'title' => "__MESSAGE__", 'itype' => 'HiddenField', 'required' => 'N', 
								'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''),
								array('id' => 2, 'sort_order' => 1, 'formid' => 'custom', 
								'title' => '__FROM_EMAIL____REQUIRED__', 'itype' => 'TextBox', 'required' => 'N', 
								'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''),
								array('id' => 1, 'sort_order' => 2, 'formid' => 'custom', 
								'title' => '__FROM_NAME__', 'itype' => 'TextBox', 'required' => 'N', 
								'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''), 
								array('id' => 3, 'sort_order' => 3, 'formid' => 'custom', 
								'title' => "Phone", 'itype' => 'TextBox', 'required' => 'N', 
								'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''),
								array('id' => 4, 'sort_order' => 4, 'formid' => 'custom', 
								'title' => "Address", 'itype' => 'TextBox', 'required' => 'N', 
								'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''),
								array('id' => 5, 'sort_order' => 5, 'formid' => 'custom', 
								'title' => "City", 'itype' => 'TextBox', 'required' => 'N', 
								'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''),
								array('id' => 6, 'sort_order' => 6, 'formid' => 'custom', 
								'title' => "State or Province", 'itype' => 'TextBox', 'required' => 'N', 
								'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''),
								array('id' => 7, 'sort_order' => 7, 'formid' => 'custom', 
								'title' => "Zip", 'itype' => 'TextBox', 'required' => 'N', 
								'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''),
								array('id' => 8, 'sort_order' => 8, 'formid' => 'custom', 
								'title' => "__REDIRECT__", 'itype' => 'HiddenField', 'required' => 'N', 
								'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''),
								array('id' => 10, 'sort_order' => 9, 'formid' => 'custom', 
								'title' => "Best Time To Reach", 'itype' => 'RadioBtn', 'required' => 'N', 
								'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''),
								array('id' => 11, 'sort_order' => 10, 'formid' => 'custom', 
								'title' => "Message", 'itype' => 'TextArea', 'required' => 'N', 
								'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => '')
								), 
								array(array('id' => 1, 'sort_order' => 0, 'linkid' => 8, 
								'formid' => 'custom', 'title' => "<a href='".$redirect."'>".$redirect."</a>",
								'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''),
								array('id' => 2, 'sort_order' => 0, 'linkid' => 9, 
								'formid' => 'custom', 'title' => "A message has been received for you from your profile page.",
								'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''),
								array('id' => 3, 'sort_order' => 0, 'linkid' => 10, 
								'formid' => 'custom', 'title' => "Any",
								'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''),
								array('id' => 4, 'sort_order' => 1, 'linkid' => 10, 
								'formid' => 'custom', 'title' => "Morning",
								'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''),
								array('id' => 5, 'sort_order' => 2, 'linkid' => 10, 
								'formid' => 'custom', 'title' => "Afternoon",
								'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''),
								array('id' => 6, 'sort_order' => 3, 'linkid' => 10, 
								'formid' => 'custom', 'title' => "Evening",
								'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => '')
								)
							);
							
						}
					}
					$output_html .= "<div class=\"merchant-item\" id=\"merchant-form\"><div class=\"merchant-label merchant-form-label\">E-mail:&nbsp;</div><div class=\"merchant-holder merchant-form-holder\">";
					$output_html .= "<div class=\"merchant-form\" id=\"merchant-form-link\"><a href=\"javascript:void(0);\" onclick=\"".$link_click."\">".$link_click_text."</a></div>";
					$output_html .= "</div></div>";
					$output_html .= "<div class=\"merchant-item\" id=\"merchant-form-content\" style=\"display: none;\"><div class=\"merchant-holder merchant-form-holder\">";
					$output_html .= "<div class=\"merchant-form\" id=\"merchant-form-content-holder\">".$inquiry_form."</div>";
					$output_html .= "</div></div>";
				}
				
				// Social
				if (Jaws_Gadget::IsGadgetUpdated('Social')) {
					$socialLayout = $GLOBALS['app']->LoadGadget('Social', 'LayoutHTML');
					$social_html = $socialLayout->Display();
					$output_html .= "<div class=\"merchant-item\" id=\"merchant-social\"><div class=\"merchant-label merchant-social-label\">Share:&nbsp;</div><div class=\"merchant-holder merchant-social-holder\">";
					$output_html .= "<div class=\"merchant-social\">".$social_html."</div>";
					$output_html .= "</div></div>";
				}
				$output_html .= "</div><div style=\"clear: both; float: none; font-size: 0.01em;\">&nbsp;</div>";
				
				// Map
				if (Jaws_Gadget::IsGadgetUpdated('Maps')) {
					if (!empty($address_string) && (isset($info['address']) && !empty($info['address']))) {
						$mapLayout = $GLOBALS['app']->LoadGadget('Maps', 'LayoutHTML');
						$map_html = $mapLayout->DisplayMapOfAddress($address_string, $title, 300, 15, 'TERRAIN');
						$output_html .= "<div>&nbsp;</div>";
						$output_html .= "<div class=\"merchant-holder merchant-map-holder gadget maps\">";
						$output_html .= $map_html;
						$output_html .= "</div><div style=\"clear: both; float: none; font-size: 0.01em;\">&nbsp;</div>";
					}
				}
				$output_html .= "</div>";
			}
		}
		$output_html .= '</div>';
		return $output_html;
	}
	
	/**
     * Displays five random users of given group ID
     *
     * @access public
     * @return XHTML 
     */
    function ShowFiveUsersOfGroup($gid = 1, $embedded = false, $referer = null)
    {
        $GLOBALS['app']->Layout->AddHeadLink('gadgets/Users/resources/style.css', 'stylesheet', 'text/css');
		require_once JAWS_PATH . 'include/Jaws/Image.php';
		require_once JAWS_PATH . 'include/Jaws/User.php';
		$uModel = new Jaws_User;
       
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');

		$groupInfo = $uModel->GetGroupInfoById($gid);
		if (!Jaws_Error::IsError($groupInfo) && isset($groupInfo['id']) && !empty($groupInfo['id'])) {
			require_once JAWS_PATH . 'include/Jaws/Template.php';
			$tpl = new Jaws_Template('gadgets/Users/templates/');
	        $tpl->Load('UsersOfGroup.html');

	        $tpl->SetBlock('layout');
			$tpl->SetVariable('actionName', 'FiveUsersOfGroup_' . $groupInfo['id'] . '_');
	        $tpl->SetVariable('layout_title', "Featured Members");
			$tpl->SetVariable('id', 'FiveUsersOfGroup');
	        $tpl->SetBlock('layout/users');
			$tpl->SetVariable('gid', $groupInfo['id']);
			
			$ba = array();
			$i = 0;
			$users = $uModel->GetUsersOfGroup($groupInfo['id']);
			if (!Jaws_Error::IsError($users)) {
				foreach($users as $user) {		            
					$p = $uModel->GetUserInfoById((int)$user['user_id'], true, true, true, true);
					if (!Jaws_Error::IsError($p) && isset($p['id']) && !empty($p['logo'])) {
						$ba[$i] = $p['id'];
						$i++;
					}
				}
			
				// Choose random IDs
				if (isset($ba[0])) {
					if ($i > 4) {
						$i = 5;
					}
					for ($b=0; $b<$i; $b++) {
						while (true) {
							$buttons_rand = array_rand($ba);
							if (!in_array('users_'.$ba[$buttons_rand], $GLOBALS['app']->_ItemsOnLayout)) {
								array_push($GLOBALS['app']->_ItemsOnLayout, 'users_'.$ba[$buttons_rand]);
								break;
							} else {
								$buttons_rand = -1;
							}
						}
						$userInfo = $uModel->GetUserInfoById($ba[$buttons_rand], true, true, true, true);
						if (!Jaws_Error::IsError($userInfo) && isset($userInfo['id']) && !empty($userInfo['id'])) {
							$tpl->SetBlock('layout/users/item');
							$tpl->SetVariable('uid', $userInfo['id']);
							$title = '';
							$title = (!empty($userInfo['company']) ? $xss->filter(strip_tags(str_replace('"', "'", $userInfo['company']))) : $xss->filter(strip_tags(str_replace('"', "'", $userInfo['nickname']))));
							$tpl->SetVariable('title', (strlen($title) > 25 ? substr(htmlspecialchars_decode($title), 0, 25) . '...' : $title));
							$tpl->SetVariable('caption', $title);
							$href = '';
							$href = $GLOBALS['app']->Map->GetURLFor('Users', 'AccountPublic', array('name' => $userInfo['username']));
							$tpl->SetVariable('href', $href);
							$image_src = $uModel->GetAvatar($userInfo['username'], $userInfo['email']);
							if (!empty($image_src)) {
								$tpl->SetBlock('layout/users/item/image');
								$tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . "/");
								//$tpl->SetVariable('base_url', JAWS_DPATH);
								$tpl->SetVariable('image_src', $image_src);
								$tpl->SetVariable('image_caption', $title);
								$tpl->SetVariable('image_href', $href);
								$tpl->ParseBlock('layout/users/item/image');
							} else {
								$tpl->SetBlock('layout/users/item/no_image');
								$tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . "/");
								//$tpl->SetVariable('base_url', JAWS_DPATH);
								$tpl->SetVariable('image_href', $href);
								$tpl->ParseBlock('layout/users/item/no_image');
							}
							$tpl->ParseBlock('layout/users/item');
						}
					}
				}
			}
	        $tpl->ParseBlock('layout/users');

			// If this is embedded, we need to pass the javascript variable that has the IFRAME height to the session 
			if ($embedded == true && !is_null($referer)) {	
				$tpl->SetBlock('layout/embedded');
				$tpl->SetVariable('id', (!empty($gid) && is_numeric($gid) ? (int)$gid : 'all'));		        
				if (strtolower($referer) == strtolower($_SERVER['SERVER_NAME'])) {
					$tpl->SetVariable('referer', $GLOBALS['app']->GetJawsURL() . "/libraries/iframes/domain1/iframetest_resize1.html");		        
				} else {	
					$tpl->SetVariable('referer', "http://".$referer."/19/img/agents/19/custom_img/common_files/iframetest_resize1.html");		        
				}
				$tpl->ParseBlock('layout/embedded');
			} else {
				$tpl->SetBlock('layout/not_embedded');
				$tpl->SetVariable('id', (!empty($gid) && is_numeric($gid) ? (int)$gid : 'all'));		        
				$tpl->ParseBlock('layout/not_embedded');
			}

	        $tpl->ParseBlock('layout');

	        return $tpl->Get();
		}
    }
	
	/**
     * Displays advanced user filter/search form
     *
     * @access public
     * @return XHTML 
     */
    function UserAdvancedFilter($id = null, $embedded = false, $referer = null)
    {
        $GLOBALS['app']->Layout->AddHeadLink('gadgets/Users/resources/style.css', 'stylesheet', 'text/css');
		require_once JAWS_PATH . 'include/Jaws/User.php';
		$uModel = new Jaws_User;
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$request =& Jaws_Request::getInstance();
        //$GLOBALS['app']->Layout->AddHeadLink('libraries/autocomplete/autocomplete.css', 'stylesheet', 'text/css', 'default');
        $GLOBALS['app']->Layout->AddHeadLink('gadgets/Users/resources/style.css', 'stylesheet', 'text/css');
		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=Users&amp;action=Ajax&amp;client=all&amp;stub=UsersAjax');
		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=Users&amp;action=AjaxCommonFiles');
		$GLOBALS['app']->Layout->AddScriptLink('gadgets/Users/resources/client_script.js');
	
		
		if (is_null($id)) {
			$id = $request->get('gid', 'post');
			if (empty($id)) {
				$id = $request->get('gid', 'get');
				if (empty($id)) {
					$id = $request->get('Users_gid', 'post');
					if (empty($id)) {
						$id = $request->get('Users_gid', 'get');
					}
				}
			}
		}
		if (!is_null($id)) {
			$groupInfo = $uModel->GetGroupInfoById((int)$id);
			if (!Jaws_Error::IsError($groupInfo) && isset($groupInfo['id']) && !empty($groupInfo['id'])) {
				// TODO: Implement group specific filters...
			}
		}
		$searchkeyword = $request->get('Users_q', 'post');
		if (empty($searchkeyword)) {
			$searchkeyword = $request->get('Users_q', 'get');
		}
		$searchkeyword = $xss->filter($searchkeyword);
		$searchfilters = $request->get('Users_f', 'post');
		if (empty($searchfilters)) {
			$searchfilters = $request->get('Users_f', 'get');
		}
		$searchfilters = (is_array($searchfilters) ? $xss->filter(implode(',',$searchfilters)) : $xss->filter($searchfilters));
		$searchhoods = $request->get('Users_h', 'post');
		if (empty($searchhoods)) {
			$searchhoods = $request->get('Users_h', 'get');
		}
		$searchhoods = (is_array($searchhoods) ? $xss->filter(implode(',',$searchhoods)) : $xss->filter($searchhoods));
		$searchletter = $request->get('Users_l', 'post');
		if (empty($searchletter)) {
			$searchletter = $request->get('Users_l', 'get');
		}
		$numbers = array();
		$letters = array();
		if ($searchletter == 'num') {
			$numbers = array('0','1','3','4','5','6','7','8','9');
		} else {
			$letters = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z');
		}
		$searchletter = (empty($searchletter) && empty($searchkeyword) ? '' : (!in_array($searchletter, $letters) ? 'A' : $searchletter));
		
		require_once JAWS_PATH . 'include/Jaws/Template.php';
		$tpl = new Jaws_Template('gadgets/Users/templates/');
		$tpl->Load('UserSearch.html');

		$tpl->SetBlock('advancedfilter');
		$tpl->SetVariable('action', 'index.php?gadget=Users&action=UserDirectory');
		$tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . "/");
		//$tpl->SetVariable('base_url', JAWS_DPATH);
		$tpl->SetVariable('site_url', $GLOBALS['app']->getSiteURL());
		$tpl->SetVariable('searchgid', $id);
		$tpl->SetVariable('searchletter', $searchletter);
		$tpl->SetVariable('actionName', 'UserAdvancedFilter');
		$tpl->SetVariable('layout_title', _t('USERS_LAYOUT_ADVANCED_FILTER'));
		$tpl->SetVariable('id_autocomplete', 'null');
		
		$searchkeyword_value = (!empty($searchkeyword) ? $searchkeyword : _t('USERS_LAYOUT_ADVANCED_FILTER_DEFAULT_SEARCH_TEXT'));
		$tpl->SetVariable('searchkeyword_value', $searchkeyword_value);
				
		// Filters
		// Retail
		$tpl->SetBlock('advancedfilter/filter');
		$filter_name = strtolower(preg_replace("[^A-Za-z0-9]", '', _t('USERS_USERS_COMPANY_TYPE_RETAIL')));
		$tpl->SetVariable('filter_name', $filter_name);
		$tpl->SetVariable('filter_value', str_replace('"',"'",_t('USERS_USERS_COMPANY_TYPE_RETAIL')));
		$tpl->SetVariable('filter_label', _t('USERS_USERS_COMPANY_TYPE_RETAIL'));
		$filter_onclick = " onclick=\"if($('".$filter_name."-subfilters')){";
		$filter_onclick .= "if($('".$filter_name."-subfilters').style.display != 'block'){";
		$filter_onclick .= "$('".$filter_name."-subfilters').style.display = 'block';";
		$filter_onclick .= "$('".$filter_name."-subfilters').style.float = 'none';";
		$filter_onclick .= "$('".$filter_name."-subfilters').style.clear = 'both';}}\"";
		$tpl->SetVariable('onclick', $filter_onclick);
		$tpl->SetVariable('checked', (in_array($xss->filter(_t('USERS_USERS_COMPANY_TYPE_RETAIL')), explode(',',$searchfilters)) ? ' checked="checked"' : ''));
		$tpl->SetVariable('substyle', (in_array($xss->filter(_t('USERS_USERS_COMPANY_TYPE_RETAIL')), explode(',',$searchfilters)) || strpos($searchfilters, 'Store:') !== false ? ' style="display: block;"' : ''));
		
		// FIXME: Expose this gadget data with a "hook" method.
		// Store gadget enabled? List Product Categories as subfilters of Retail
		if (Jaws_Gadget::IsGadgetUpdated('Store')) {
			$model = $GLOBALS['app']->LoadGadget('Store', 'Model');
			$parents = $model->GetProductParents();
			if (!Jaws_Error::IsError($parents)) {
				foreach($parents as $parent) {
					if ($parent['productparentactive'] == 'Y') {
						$tpl->SetBlock('advancedfilter/filter/subfilter');
						$filter_name = strtolower(preg_replace("[^A-Za-z0-9]", '', $parent['productparentcategory_name']));
						$tpl->SetVariable('subfilter_name', $filter_name);
						$tpl->SetVariable('subfilter_value', 'Store:GetStoreOwnersOfParent:'.$parent['productparentid']);
						$tpl->SetVariable('subfilter_label', $parent['productparentcategory_name']);
						$tpl->SetVariable('subchecked', (in_array($xss->filter('Store:GetStoreOwnersOfParent:'.$parent['productparentid']), explode(',',$searchfilters)) ? ' checked="checked"' : ''));
						$tpl->ParseBlock('advancedfilter/filter/subfilter');
					}
				}
			}
		}
		$tpl->ParseBlock('advancedfilter/filter');
		
		// Restaurant
		$tpl->SetBlock('advancedfilter/filter');
		$filter_name = strtolower(preg_replace("[^A-Za-z0-9]", '', _t('USERS_USERS_COMPANY_TYPE_RESTAURANT')));
		$tpl->SetVariable('filter_name', $filter_name);
		$tpl->SetVariable('filter_value', str_replace('"',"'",_t('USERS_USERS_COMPANY_TYPE_RESTAURANT')));
		$tpl->SetVariable('filter_label', _t('USERS_USERS_COMPANY_TYPE_RESTAURANT'));
		$tpl->SetVariable('onclick', '');
		$tpl->SetVariable('checked', (in_array($xss->filter(_t('USERS_USERS_COMPANY_TYPE_RESTAURANT')), explode(',',$searchfilters)) ? ' checked="checked"' : ''));
		$tpl->SetVariable('substyle', '');
		$tpl->ParseBlock('advancedfilter/filter');
		
		// Services
		$tpl->SetBlock('advancedfilter/filter');
		$filter_name = strtolower(preg_replace("[^A-Za-z0-9]", '', _t('USERS_USERS_COMPANY_TYPE_SERVICES')));
		$tpl->SetVariable('filter_name', $filter_name);
		$tpl->SetVariable('filter_value', str_replace('"',"'",_t('USERS_USERS_COMPANY_TYPE_SERVICES')));
		$tpl->SetVariable('filter_label', _t('USERS_USERS_COMPANY_TYPE_SERVICES'));
		$tpl->SetVariable('onclick', '');
		$tpl->SetVariable('checked', (in_array($xss->filter(_t('USERS_USERS_COMPANY_TYPE_SERVICES')), explode(',',$searchfilters)) ? ' checked="checked"' : ''));
		$tpl->SetVariable('substyle', '');
		$tpl->ParseBlock('advancedfilter/filter');
		
		// Medical
		$tpl->SetBlock('advancedfilter/filter');
		$filter_name = strtolower(preg_replace("[^A-Za-z0-9]", '', _t('USERS_USERS_COMPANY_TYPE_MEDICAL')));
		$tpl->SetVariable('filter_name', $filter_name);
		$tpl->SetVariable('filter_value', str_replace('"',"'",_t('USERS_USERS_COMPANY_TYPE_MEDICAL')));
		$tpl->SetVariable('filter_label', _t('USERS_USERS_COMPANY_TYPE_MEDICAL'));
		$tpl->SetVariable('onclick', '');
		$tpl->SetVariable('checked', (in_array($xss->filter(_t('USERS_USERS_COMPANY_TYPE_MEDICAL')), explode(',',$searchfilters)) ? ' checked="checked"' : ''));
		$tpl->SetVariable('substyle', '');
		$tpl->ParseBlock('advancedfilter/filter');
		
		// Media
		$tpl->SetBlock('advancedfilter/filter');
		$filter_name = strtolower(preg_replace("[^A-Za-z0-9]", '', _t('USERS_USERS_COMPANY_TYPE_MEDIA')));
		$tpl->SetVariable('filter_name', $filter_name);
		$tpl->SetVariable('filter_value', str_replace('"',"'",_t('USERS_USERS_COMPANY_TYPE_MEDIA')));
		$tpl->SetVariable('filter_label', _t('USERS_USERS_COMPANY_TYPE_MEDIA'));
		$tpl->SetVariable('onclick', '');
		$tpl->SetVariable('checked', (in_array($xss->filter(_t('USERS_USERS_COMPANY_TYPE_MEDIA')), explode(',',$searchfilters)) ? ' checked="checked"' : ''));
		$tpl->SetVariable('substyle', '');
		$tpl->ParseBlock('advancedfilter/filter');
		
		// Salon
		$tpl->SetBlock('advancedfilter/filter');
		$filter_name = strtolower(preg_replace("[^A-Za-z0-9]", '', _t('USERS_USERS_COMPANY_TYPE_SALON')));
		$tpl->SetVariable('filter_name', $filter_name);
		$tpl->SetVariable('filter_value', str_replace('"',"'",_t('USERS_USERS_COMPANY_TYPE_SALON')));
		$tpl->SetVariable('filter_label', _t('USERS_USERS_COMPANY_TYPE_SALON'));
		$tpl->SetVariable('onclick', '');
		$tpl->SetVariable('checked', (in_array($xss->filter(_t('USERS_USERS_COMPANY_TYPE_SALON')), explode(',',$searchfilters)) ? ' checked="checked"' : ''));
		$tpl->SetVariable('substyle', '');
		$tpl->ParseBlock('advancedfilter/filter');
		
		// Health & Fitness
		$tpl->SetBlock('advancedfilter/filter');
		$filter_name = strtolower(preg_replace("[^A-Za-z0-9]", '', _t('USERS_USERS_COMPANY_TYPE_HEALTH')));
		$tpl->SetVariable('filter_name', $filter_name);
		$tpl->SetVariable('filter_value', str_replace('"',"'",_t('USERS_USERS_COMPANY_TYPE_HEALTH')));
		$tpl->SetVariable('filter_label', _t('USERS_USERS_COMPANY_TYPE_HEALTH'));
		$tpl->SetVariable('onclick', '');
		$tpl->SetVariable('checked', (in_array($xss->filter(_t('USERS_USERS_COMPANY_TYPE_HEALTH')), explode(',',$searchfilters)) ? ' checked="checked"' : ''));
		$tpl->SetVariable('substyle', '');
		$tpl->ParseBlock('advancedfilter/filter');
		
		// Home & Garden
		$tpl->SetBlock('advancedfilter/filter');
		$filter_name = strtolower(preg_replace("[^A-Za-z0-9]", '', _t('USERS_USERS_COMPANY_TYPE_HOMEGARDEN')));
		$tpl->SetVariable('filter_name', $filter_name);
		$tpl->SetVariable('filter_value', str_replace('"',"'",_t('USERS_USERS_COMPANY_TYPE_HOMEGARDEN')));
		$tpl->SetVariable('filter_label', _t('USERS_USERS_COMPANY_TYPE_HOMEGARDEN'));
		$tpl->SetVariable('onclick', '');
		$tpl->SetVariable('checked', (in_array($xss->filter(_t('USERS_USERS_COMPANY_TYPE_HOMEGARDEN')), explode(',',$searchfilters)) ? ' checked="checked"' : ''));
		$tpl->SetVariable('substyle', '');
		$tpl->ParseBlock('advancedfilter/filter');
		
		// Entertainment
		$tpl->SetBlock('advancedfilter/filter');
		$filter_name = strtolower(preg_replace("[^A-Za-z0-9]", '', _t('USERS_USERS_COMPANY_TYPE_ENTERTAINMENT')));
		$tpl->SetVariable('filter_name', $filter_name);
		$tpl->SetVariable('filter_value', str_replace('"',"'",_t('USERS_USERS_COMPANY_TYPE_ENTERTAINMENT')));
		$tpl->SetVariable('filter_label', _t('USERS_USERS_COMPANY_TYPE_ENTERTAINMENT'));
		$tpl->SetVariable('onclick', '');
		$tpl->SetVariable('checked', (in_array($xss->filter(_t('USERS_USERS_COMPANY_TYPE_ENTERTAINMENT')), explode(',',$searchfilters)) ? ' checked="checked"' : ''));
		$tpl->SetVariable('substyle', '');
		$tpl->ParseBlock('advancedfilter/filter');
		
		// Financial
		$tpl->SetBlock('advancedfilter/filter');
		$filter_name = strtolower(preg_replace("[^A-Za-z0-9]", '', _t('USERS_USERS_COMPANY_TYPE_FINANCIAL')));
		$tpl->SetVariable('filter_name', $filter_name);
		$tpl->SetVariable('filter_value', str_replace('"',"'",_t('USERS_USERS_COMPANY_TYPE_FINANCIAL')));
		$tpl->SetVariable('filter_label', _t('USERS_USERS_COMPANY_TYPE_FINANCIAL'));
		$tpl->SetVariable('onclick', '');
		$tpl->SetVariable('checked', (in_array($xss->filter(_t('USERS_USERS_COMPANY_TYPE_FINANCIAL')), explode(',',$searchfilters)) ? ' checked="checked"' : ''));
		$tpl->SetVariable('substyle', '');
		$tpl->ParseBlock('advancedfilter/filter');
		
		// Arts & Culture
		$tpl->SetBlock('advancedfilter/filter');
		$filter_name = strtolower(preg_replace("[^A-Za-z0-9]", '', _t('USERS_USERS_COMPANY_TYPE_ARTSCULTURE')));
		$tpl->SetVariable('filter_name', $filter_name);
		$tpl->SetVariable('filter_value', str_replace('"',"'",_t('USERS_USERS_COMPANY_TYPE_ARTSCULTURE')));
		$tpl->SetVariable('filter_label', _t('USERS_USERS_COMPANY_TYPE_ARTSCULTURE'));
		$tpl->SetVariable('onclick', '');
		$tpl->SetVariable('checked', (in_array($xss->filter(_t('USERS_USERS_COMPANY_TYPE_ARTSCULTURE')), explode(',',$searchfilters)) ? ' checked="checked"' : ''));
		$tpl->SetVariable('substyle', '');
		$tpl->ParseBlock('advancedfilter/filter');
		
		// Lodging
		$tpl->SetBlock('advancedfilter/filter');
		$filter_name = strtolower(preg_replace("[^A-Za-z0-9]", '', _t('USERS_USERS_COMPANY_TYPE_LODGING')));
		$tpl->SetVariable('filter_name', $filter_name);
		$tpl->SetVariable('filter_value', str_replace('"',"'",_t('USERS_USERS_COMPANY_TYPE_LODGING')));
		$tpl->SetVariable('filter_label', _t('USERS_USERS_COMPANY_TYPE_LODGING'));
		$tpl->SetVariable('onclick', '');
		$tpl->SetVariable('checked', (in_array($xss->filter(_t('USERS_USERS_COMPANY_TYPE_LODGING')), explode(',',$searchfilters)) ? ' checked="checked"' : ''));
		$tpl->SetVariable('substyle', '');
		$tpl->ParseBlock('advancedfilter/filter');
		
		// Manufacturing
		$tpl->SetBlock('advancedfilter/filter');
		$filter_name = strtolower(preg_replace("[^A-Za-z0-9]", '', _t('USERS_USERS_COMPANY_TYPE_MANUFACTURING')));
		$tpl->SetVariable('filter_name', $filter_name);
		$tpl->SetVariable('filter_value', str_replace('"',"'",_t('USERS_USERS_COMPANY_TYPE_MANUFACTURING')));
		$tpl->SetVariable('filter_label', _t('USERS_USERS_COMPANY_TYPE_MANUFACTURING'));
		$tpl->SetVariable('onclick', '');
		$tpl->SetVariable('checked', (in_array($xss->filter(_t('USERS_USERS_COMPANY_TYPE_MANUFACTURING')), explode(',',$searchfilters)) ? ' checked="checked"' : ''));
		$tpl->SetVariable('substyle', '');
		$tpl->ParseBlock('advancedfilter/filter');
		
		// Grocery & Market
		$tpl->SetBlock('advancedfilter/filter');
		$filter_name = strtolower(preg_replace("[^A-Za-z0-9]", '', _t('USERS_USERS_COMPANY_TYPE_GROCERY')));
		$tpl->SetVariable('filter_name', $filter_name);
		$tpl->SetVariable('filter_value', str_replace('"',"'",_t('USERS_USERS_COMPANY_TYPE_GROCERY')));
		$tpl->SetVariable('filter_label', _t('USERS_USERS_COMPANY_TYPE_GROCERY'));
		$tpl->SetVariable('onclick', '');
		$tpl->SetVariable('checked', (in_array($xss->filter(_t('USERS_USERS_COMPANY_TYPE_GROCERY')), explode(',',$searchfilters)) ? ' checked="checked"' : ''));
		$tpl->SetVariable('substyle', '');
		$tpl->ParseBlock('advancedfilter/filter');
		
		// Farm
		$tpl->SetBlock('advancedfilter/filter');
		$filter_name = strtolower(preg_replace("[^A-Za-z0-9]", '', _t('USERS_USERS_COMPANY_TYPE_FARM')));
		$tpl->SetVariable('filter_name', $filter_name);
		$tpl->SetVariable('filter_value', str_replace('"',"'",_t('USERS_USERS_COMPANY_TYPE_FARM')));
		$tpl->SetVariable('filter_label', _t('USERS_USERS_COMPANY_TYPE_FARM'));
		$tpl->SetVariable('onclick', '');
		$tpl->SetVariable('checked', (in_array($xss->filter(_t('USERS_USERS_COMPANY_TYPE_FARM')), explode(',',$searchfilters)) ? ' checked="checked"' : ''));
		$tpl->SetVariable('substyle', '');
		$tpl->ParseBlock('advancedfilter/filter');
		
		// Non-Profit
		$tpl->SetBlock('advancedfilter/filter');
		$filter_name = strtolower(preg_replace("[^A-Za-z0-9]", '', _t('USERS_USERS_COMPANY_TYPE_NONPROFIT')));
		$tpl->SetVariable('filter_name', $filter_name);
		$tpl->SetVariable('filter_value', str_replace('"',"'",_t('USERS_USERS_COMPANY_TYPE_NONPROFIT')));
		$tpl->SetVariable('filter_label', _t('USERS_USERS_COMPANY_TYPE_NONPROFIT'));
		$tpl->SetVariable('onclick', '');
		$tpl->SetVariable('checked', (in_array($xss->filter(_t('USERS_USERS_COMPANY_TYPE_NONPROFIT')), explode(',',$searchfilters)) ? ' checked="checked"' : ''));
		$tpl->SetVariable('substyle', '');
		$tpl->ParseBlock('advancedfilter/filter');
		
		$tpl->ParseBlock('advancedfilter');
		return $tpl->Get();
	}
	
    /**
     * Shows Comments of Group
     *
     * @access public
     * @return string template content
     */
    function ShowCommentsOfGroup($id, $layout = 'full') {
		return $this->ShowComments('Users', true, $id, 'Activity', true, 2, $layout, false, 5, 'GetGroupComments');
    }
	
    /**
     * Shows User comments
     *
     * @access public
     * @return string template content
     */
    function ShowComments(
		$gadget = 'Users', $public = true, $id = null, $header = false, $interactive = true, $replies_shown = 2, 
		$layout = 'full', $only_comments = false, $limit = 5, $method = 'GetComments'
	) {
		$res = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		return $res->ShowComments($gadget, $public, $id, $header, $interactive, $replies_shown, $layout, $only_comments, $limit, $method);
    }
	
    /**
     * Shows User recommendations
     *
     * @access public
     * @return string template content
     */
    function ShowRecommendations(
		$gadget = 'Users', $public = false, $id = null, $layout = 'full', 
		$method = 'GetRecommendations', $only_recommendations = false, $limit = 5
	) {
		$res = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		return $res->ShowRecommendations($gadget, $public, $id, $layout, $method, $only_recommendations, $limit);
    }
	
	/**
     * Displays follow buttons
     *
     * @access public
     * @return XHTML 
     */
    function ShowFollowButtons($page_gadget = null, $page_action = null, $page_linkid = null, $embedded = false, $referer = null)
    {
        $GLOBALS['app']->Layout->AddHeadLink('gadgets/Users/resources/style.css', 'stylesheet', 'text/css');
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$request =& Jaws_Request::getInstance();
        //$GLOBALS['app']->Layout->AddHeadLink('libraries/autocomplete/autocomplete.css', 'stylesheet', 'text/css', 'default');
        $GLOBALS['app']->Layout->AddHeadLink('gadgets/Users/resources/style.css', 'stylesheet', 'text/css');
		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=Users&amp;action=Ajax&amp;client=all&amp;stub=UsersAjax');
		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=Users&amp;action=AjaxCommonFiles');
		$GLOBALS['app']->Layout->AddScriptLink('gadgets/Users/resources/client_script.js');
	
		$request_gadget = $GLOBALS['app']->_MainRequestGadget;
		if (!is_null($page_gadget) && !empty($page_gadget)) {
			$request_gadget = $page_gadget;
		}
		$request_action = $GLOBALS['app']->_MainRequestAction;
		if (!is_null($page_action) && !empty($page_action)) {
			$request_action = $page_action;
		}
		$request_id = $GLOBALS['app']->_MainRequestId;
		if (!is_null($page_linkid) && !empty($page_linkid)) {
			$request_id = $page_linkid;
		}
						
		require_once JAWS_PATH . 'include/Jaws/Template.php';
		$tpl = new Jaws_Template('gadgets/Users/templates/');
		$tpl->Load('FollowButtons.html');

		$tpl->SetBlock('follow');
		$tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . "/");
		$tpl->SetVariable('actionName', 'FollowButtons');
		$tpl->SetVariable('site_url', $GLOBALS['app']->getSiteURL());
		
		$subscription_found = false;
		$hook = $GLOBALS['app']->loadHook($request_gadget, 'Subscribe');
		if ($hook !== false) {
			if (method_exists($hook, 'CurrentURLSubscriptions')) {
				$subscription = $hook->CurrentURLSubscriptions($request_gadget, $request_action, $request_id);
				if ($subscription !== false && isset($subscription['subscriptions']) && is_array($subscription['subscriptions'])) {
					foreach ($subscription['subscriptions'] as $subscribe) {
						$subscription_found = true;
						$tpl->SetBlock("follow/button");
						$tpl->SetVariable('button', $subscribe['button']);
						$tpl->SetVariable('id', (isset($subscribe['id']) && !empty($subscribe['id']) ? $subscribe['id'] : microtime()));
						$tpl->SetVariable('class', (isset($subscribe['class']) && !empty($subscribe['class']) ? " ".$subscribe['class'] : ''));
						$tpl->ParseBlock("follow/button");
					}
				}
			}
		}
		
		if ($subscription_found === false) {
			return '';
		}
		
		$tpl->ParseBlock("follow");
		return $tpl->Get();
	}
	
}
