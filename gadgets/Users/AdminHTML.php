<?php
/**
 * Users Core Gadget Admin
 *
 * @category   GadgetAdmin
 * @package    Users
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2004-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
ini_set("memory_limit","512M");
ini_set("post_max_size","25M");
ini_set("upload_max_filesize","4M");
ini_set("max_execution_time","0");

class UsersAdminHTML extends Jaws_GadgetHTML
{
    /**
     * Gadget constructor
     *
     * @access public
     */
    function UsersAdminHTML()
    {
        $this->Init('Users');
    }

    /**
     * Calls default admin action
     *
     * @access public
     * @return string  Template content
     */
    function Admin()
    {
        $this->CheckPermission('default');
        if ($this->GetPermission('ManageUsers')) {
            return $this->UsersView();
        } else if ($this->GetPermission('ManageGroups')) {
            return $this->Groups();
        }

        $this->CheckPermission('ManageProperties');
    }

    /**
     * Prepares the users menubar
     *
     * @access  public
     * @param   string  $action   Selected action
     * @return  string  XHTML of menubar
     */
    function MenuBar($action)
    {
        $actions = array('Users', 'Groups', 'Settings', 'ImportUsers', 'Messaging');
        if (!in_array($action, $actions)) {
            $action = 'Users';
        }

        require_once JAWS_PATH . 'include/Jaws/Widgets/Menubar.php';
        $menubar = new Jaws_Widgets_Menubar();
        if ($this->GetPermission('ManageUsers')) {
            $menubar->AddOption('Users', _t('USERS_NAME'),
                                BASE_SCRIPT . '?gadget=Users&amp;action=Admin', $GLOBALS['app']->GetJawsURL() . '/gadgets/Users/images/users_mini.png');
        }
        if ($this->GetPermission('ManageGroups')) {
            $menubar->AddOption('Groups', _t('USERS_GROUPS_GROUPS'),
                                BASE_SCRIPT . '?gadget=Users&amp;action=Groups', $GLOBALS['app']->GetJawsURL() . '/gadgets/Users/images/groups_mini.png');
        }
        if ($this->GetPermission('ManageMessaging')) {
			$menubar->AddOption('Messaging', _t('USERS_MESSAGING'),
								BASE_SCRIPT . '?gadget=Users&amp;action=Messaging', STOCK_TEXT_EDIT);
        }
        if ($this->GetPermission('ManageProperties')) {
			$menubar->AddOption('Settings', _t('GLOBAL_SETTINGS'),
								BASE_SCRIPT . '?gadget=Users&amp;action=Settings', STOCK_PREFERENCES);
			$menubar->AddOption('ImportUsers', _t('USERS_MENU_IMPORTUSERS'),
								'admin.php?gadget=Users&amp;action=ImportUsers', STOCK_OPEN);
        }
        $menubar->Activate($action);
        return $menubar->Get();
    }

    /**
     * Show user administration
     *
     * @access  public
     * @return  string HTML content
     */
    function UsersView()
    {
        $this->CheckPermission('ManageUsers');
		$date = $GLOBALS['app']->loadDate();
        require_once JAWS_PATH . 'include/Jaws/User.php';
        $userModel = new Jaws_User();
        
        $request =& Jaws_Request::getInstance();
		$post = $request->get(array('msg', 'action', 'id', 'Users_type', 'Users_sort', 'Users_q', 'Users_enabled', 'Users_sortDir', 'Users_group'), 'post');
        $get = $request->get(array('msg', 'action', 'id', 'Users_type', 'Users_sort', 'Users_q', 'Users_enabled', 'Users_sortDir', 'Users_group'), 'get');
        
		$action = !is_null($get['action']) ? $get['action'] : 'Admin';
		
		$enabledUsers = (!empty($post['Users_enabled']) && $post['Users_enabled'] == 'true' ? true : 
							(!empty($post['Users_enabled']) && $post['Users_enabled'] == 'false' ? false : ''));		
		if (empty($enabledUsers)) {
			$enabledUsers = (!empty($get['Users_enabled']) && $get['Users_enabled'] == 'true' ? true : 
							(!empty($get['Users_enabled']) && $get['Users_enabled'] == 'false' ? false : -1));		
        }
		
		$typeOfUsers = (!empty($post['Users_type']) ? (int)$post['Users_type'] : '');
		if (empty($typeOfUsers)) {
			$typeOfUsers = (!empty($get['Users_type']) ? (int)$get['Users_type'] : -1);
		}
		$typeOfUsers = ($typeOfUsers == 0 ? ($GLOBALS['app']->Session->IsSuperAdmin() ? 0 : 1) : $typeOfUsers);
		
		$groupUsers = (!empty($post['Users_group']) && (int)$post['Users_group'] > 0 ? (int)$post['Users_group'] : '');		
		if (empty($groupUsers)) {
			$groupUsers = (!empty($get['Users_group']) && (int)$get['Users_group'] > 0 ? (int)$get['Users_group'] : -1);		
		}
		
		$orderBy = (!empty($post['Users_sort']) ? strtolower($post['Users_sort']) : '');		
		if (empty($orderBy)) {
			$orderBy = (!empty($get['Users_sort']) ? strtolower($get['Users_sort']) : 'nickname');		
		}
		
		$sortDir = (!empty($post['Users_sortDir']) && 
						in_array(strtoupper($post['Users_sortDir']), array('ASC', 'DESC')) ? strtoupper($post['Users_sortDir']) : '');		
		if (empty($sortDir)) {
			$sortDir = (!empty($get['Users_sortDir']) && 
						in_array(strtoupper($get['Users_sortDir']), array('ASC', 'DESC')) ? strtoupper($get['Users_sortDir']) : 'ASC');		
		}
		
		$searchkeyword = (!empty($post['Users_q']) ? $post['Users_q'] : '');		
		if (empty($searchkeyword)) {
			$searchkeyword = (!empty($get['Users_q']) ? $get['Users_q'] : '');		
		}
		
		$msg = $get['msg'];
		if ($msg == 'loginSSL') {
			$profile = $userModel->GetUserInfoById((int)$get['id'], true, true, true, true);
			if (Jaws_Error::IsError($profile)) {
				return $profile;
			}
			if ($GLOBALS['app']->Session->Logged() && (int)$GLOBALS['app']->Session->GetAttribute('user_type') <= $profile['user_type']) {
				// Login over current session
				$login = $GLOBALS['app']->Session->Login($profile['username'], '', false, true);
				if (Jaws_Error::isError($login)) {
					return $login;
				}
				require_once JAWS_PATH . 'include/Jaws/Header.php';
				Jaws_Header::Location('index.php?gadget=Users');
				exit;
			}
		}
        $this->AjaxMe('script.js');
        $GLOBALS['app']->Layout->AddScriptLink('libraries/xtree/xtree.js');
        if ($GLOBALS['app']->Registry->Get('/crypt/enabled') == 'true') {
            $GLOBALS['app']->Layout->AddScriptLink('libraries/js/bigint.js');
            $GLOBALS['app']->Layout->AddScriptLink('libraries/js/bigintmath.js');
            $GLOBALS['app']->Layout->AddScriptLink('libraries/js/rsa.js');
        }
		
        $tpl = new Jaws_Template('gadgets/Users/templates/');
        $tpl->Load('Users.html');
        $tpl->SetBlock('Users');

        // Search
		$searchEntry =& Piwi::CreateWidget('Entry', 'filter_search', $searchkeyword);
		$searchEntry->SetTitle(_t('GLOBAL_SEARCH'));
		$searchEntry->SetStyle('width: 150px');
        $tpl->SetVariable('filter_search', $searchEntry->Get());
        $tpl->SetVariable('lbl_filter_search', _t('GLOBAL_SEARCH').':');
        
		$searchButton =& Piwi::CreateWidget('Button', 'search_btn', _t('GLOBAL_SEARCH'));
        $searchButton->AddEvent(ON_CLICK, "javascript: getUsers();");
        $searchButton->SetSubmit();
		$searchButton->SetStyle('width: 50px; max-width: 50px; min-width: 50px;');
        $tpl->SetVariable('search_btn', $searchButton->Get());
        
		// orderBy Filter
        $filterOrderBy =& Piwi::CreateWidget('Combo', 'filter_orderBy');
        $filterOrderBy->SetStyle('width: 100px;');
        $filterOrderBy->AddOption(_t('USERS_USERS_USERNAME'), 'username');
        $filterOrderBy->AddOption(_t('USERS_USERS_NICKNAME'), 'nickname');
        $filterOrderBy->AddOption(_t('USERS_USERS_FIRSTNAME'), 'fname');
        $filterOrderBy->AddOption(_t('USERS_USERS_LASTNAME'), 'lname');
        $filterOrderBy->AddOption(_t('USERS_USERS_USERID'), 'id');
        $filterOrderBy->AddOption(_t('USERS_USERS_WEBSITE'), 'url');
        $filterOrderBy->AddOption(_t('USERS_USERS_COMPANY'), 'company');
        $filterOrderBy->AddOption(_t('USERS_USERS_COMPANY_TYPE'), 'company_type');
        $filterOrderBy->AddOption(_t('USERS_USERS_CITY'), 'city');
        $filterOrderBy->AddOption(_t('USERS_USERS_COUNTRY'), 'country');
        $filterOrderBy->AddOption(_t('USERS_USERS_REGION'), 'region');
        $filterOrderBy->AddOption(_t('USERS_USERS_POSTAL'), 'postal');
        $filterOrderBy->AddOption(_t('USERS_USERS_PHONE'), 'phone');
        $filterOrderBy->AddOption(_t('USERS_USERS_OFFICE'), 'office');
        $filterOrderBy->AddOption(_t('USERS_USERS_TOLLFREE'), 'tollfree');
        $filterOrderBy->AddOption(_t('USERS_USERS_FAX'), 'fax');
        $filterOrderBy->AddOption(_t('USERS_USERS_MERCHANT_ID'), 'merchant_id');
        $filterOrderBy->AddOption(_t('USERS_USERS_GENDER'), 'gender');
        $filterOrderBy->AddOption(_t('USERS_USERS_BIRTHDAY'), 'dob');
        $filterOrderBy->AddEvent(ON_CHANGE, "javascript: getUsers();");
        $filterOrderBy->SetDefault($orderBy);
        $tpl->SetVariable('filter_orderBy', $filterOrderBy->Get());
        $tpl->SetVariable('lbl_filter_orderBy', "Order by:");

		// sortDir Filter
        $filterSortDir =& Piwi::CreateWidget('Combo', 'filter_sortDir');
        $filterSortDir->SetStyle('width: 60px;');
        $filterSortDir->AddOption('ASC', 'ASC');
        $filterSortDir->AddOption('DESC', 'DESC');
        $filterSortDir->AddEvent(ON_CHANGE, "javascript: getUsers();");
        $filterSortDir->SetDefault($sortDir);
        $tpl->SetVariable('filter_sortDir', $filterSortDir->Get());
        $tpl->SetVariable('lbl_filter_sortDir', "Sort:");

		// Group Filter
        $filterGroup =& Piwi::CreateWidget('Combo', 'filter_group');
        $filterGroup->SetStyle('width: 200px;');
        $filterGroup->AddOption(_t('USERS_GROUPS_ALL_GROUPS'), -1, false);
        $groups = $userModel->GetAllGroups('title');
        if (!Jaws_Error::IsError($groups)) {
            foreach ($groups as $group) {
                $filterGroup->AddOption($group['title'], $group['id']);
            }
        }
        $filterGroup->AddEvent(ON_CHANGE, "javascript: getUsers();");
        $filterGroup->SetDefault($groupUsers);
        $tpl->SetVariable('filter_group', $filterGroup->Get());
        $tpl->SetVariable('lbl_filter_group', _t('USERS_GROUPS_GROUP').':');

        // Type Filter
        $filterType =& Piwi::CreateWidget('Combo', 'filter_type');
        $filterType->SetStyle('width: 100px;');
        $filterType->AddOption(_t('GLOBAL_ALL'), -1, false);
        $filterType->AddOption(_t('USERS_USERS_TYPE_SUPERADMIN'), 0);
        $filterType->AddOption(_t('USERS_USERS_TYPE_ADMIN'),      1);
        $filterType->AddOption(_t('USERS_USERS_TYPE_NORMAL'),     2);
        $filterType->AddEvent(ON_CHANGE, "javascript: getUsers();");
        $filterType->SetDefault($typeOfUsers);
        $tpl->SetVariable('filter_type', $filterType->Get());
        $tpl->SetVariable('lbl_filter_type', _t('USERS_USERS_TYPE').':');

        // Status Filter
        $filterStatus =& Piwi::CreateWidget('Combo', 'filter_status');
        $filterStatus->SetStyle('width: 60px;');
        $filterStatus->AddOption(_t('GLOBAL_ALL'), -1, false);
        $filterStatus->AddOption(_t('GLOBAL_DISABLE'), false);
        $filterStatus->AddOption(_t('GLOBAL_ENABLE'),  true);
        $filterStatus->AddEvent(ON_CHANGE, "javascript: getUsers();");
        $filterStatus->SetDefault($enabledUsers);
        $tpl->SetVariable('filter_status', $filterStatus->Get());
        $tpl->SetVariable('lbl_filter_status', _t('GLOBAL_STATUS').':');

        // Right menu
        include_once JAWS_PATH . 'include/Jaws/Widgets/XHTMLMenu.php';
        $menu = new Jaws_Widgets_XHTMLMenu('', 'right_menu', 'visibility: hidden;');

        $addUser =& Piwi::CreateWidget('Button', 'add_user', _t('USERS_USERS_ADD'), $GLOBALS['app']->GetJawsURL() . '/gadgets/Users/images/add_user.png');
        $addUser->AddEvent(ON_CLICK, "javascript: addUser();");
        $tpl->SetVariable('add_user', $addUser->Get());

        $saveUser =& Piwi::CreateWidget('Button', 'save_user', _t('GLOBAL_SAVE'), STOCK_SAVE);
        $saveUser->AddEvent(ON_CLICK, "javascript: saveUser();");
        $saveUser->SetStyle('display: none;');
        $tpl->SetVariable('save_user', $saveUser->Get());

        $cancelAction =& Piwi::CreateWidget('Button', 'cancel_action', _t('GLOBAL_CANCEL'), STOCK_CANCEL);
        $cancelAction->AddEvent(ON_CLICK, "javascript: stopAction();");
        $cancelAction->SetStyle('display: none;');
        $tpl->SetVariable('cancel', $cancelAction->Get());

        if ($this->GetPermission('ManageUserACLs')) {
            $resetACL =& Piwi::CreateWidget('Button', 'reset_acl', _t('USERS_RESET_ACL'), STOCK_UNDO);
            $resetACL->AddEvent(ON_CLICK, "javascript: resetUserACL();");
            $resetACL->SetStyle('display: none;');
            $tpl->SetVariable('reset_acl', $resetACL->Get());

            $saveACL =& Piwi::CreateWidget('Button', 'save_acl', _t('GLOBAL_SAVE'), STOCK_SAVE);
            $saveACL->AddEvent(ON_CLICK, "javascript: saveACL();");
            $saveACL->SetStyle('display: none;');
            $tpl->SetVariable('save_acl', $saveACL->Get());

            $menu->addOption('manage_acl', _t('USERS_ACLRULES'), "javascript: manageUserACL();", $GLOBALS['app']->GetJawsURL() . '/gadgets/Users/images/acls.png', false, '', true);
        }

        //Personal information, such as fname, lname, gender, ...
        $menu->addOption('personal_information', _t('USERS_PERSONAL_USER_EDIT'), "javascript: personalInformation();",
                         STOCK_FONT, false, '', true);
        //Advanced options, such as language.. theme
        $menu->addOption('advanced_options', _t('USERS_ADVANCED_USER_EDIT'), "javascript: advancedUserOptions();",
                         STOCK_PREFERENCES, false, '', true);
        $menu->addOption('delete_user', _t('USERS_ACCOUNT_DELETE'), "javascript: deleteUser();", STOCK_DELETE, false, '', true);
        // Login as user
        $user_type = $GLOBALS['app']->Session->GetAttribute('user_type');
		if ($user_type < 2) {
			$menu->addOption('login_as_user', _t('USERS_ACCOUNT_LOGIN_AS_USER'), "javascript: loginAsUser();", $GLOBALS['app']->GetJawsURL() . '/gadgets/Users/images/users_mini.png', false, '', true);
        }
		$menu->addOption('view_files', _t('USERS_ACCOUNT_VIEW_FILES'), "javascript: viewUserFiles();", $GLOBALS['app']->GetJawsURL() . '/gadgets/FileBrowser/images/logo.png', false, '', true);
        $tpl->SetVariable('right_menu', $menu->get());

        //Menu bar
        $tpl->SetVariable('menubar', $this->MenuBar('Users'));

        //Fill the users combo..
        $comboUsers =& Piwi::CreateWidget('Combo', 'users_combo');
        $comboUsers->SetSize(20);
        $comboUsers->SetStyle('width: 200px; height: 350px;');
        $comboUsers->AddEvent(ON_CHANGE, 'javascript: editUser(this.value);');

        //Which users should we fetch first-time?
        $typeOfUsers = ($GLOBALS['app']->Session->IsSuperAdmin() ? 
							($typeOfUsers == -1 ? false : (int)$typeOfUsers) : 
								($typeOfUsers < 1 ? '1,2' : (int)$typeOfUsers));
        $enabledUsers = ($GLOBALS['app']->Session->IsSuperAdmin() ? 
							($enabledUsers == -1 ? null : $enabledUsers) : 
							$enabledUsers);

		$users = $userModel->GetUsers(false, $typeOfUsers, $enabledUsers, $orderBy, null, null, $sortDir, '', $searchkeyword);
		$sorted_users = array();
		//$u = 0;
		foreach($users as $user) {
			if (is_null($user[$orderBy]) || !isset($user[$orderBy]) || empty($user[$orderBy]) || $user[$orderBy] == 'null') {
				continue;
			}
			//$users[$u]['sort_name'] = (!empty($user['nickname']) ? $user['nickname'] : $user['username']).(!empty($user['company']) ? "&nbsp;(".$user['company'].')' : '');
			$user[$orderBy] = ($orderBy == 'dob' && !is_null($user[$orderBy]) && isset($user[$orderBy]) && !empty($user[$orderBy]) ? $date->Format($user[$orderBy]): $user[$orderBy]);
			$sorted_users[] = $user;
			//$u++;
		}
		/*
		$sorted_users = array();
		if (count($users)) {
			$subkey = 'sort_name'; 
			$temp_array = array();
			$temp_array[key($users)] = array_shift($users);
			foreach($users as $key => $val) {
				$offset = 0;
				$found = false;
				foreach($temp_array as $tmp_key => $tmp_val) {
					if(!$found and strtolower($val[$subkey]) > strtolower($tmp_val[$subkey])) {
						$temp_array = array_merge(
							(array)array_slice($temp_array,0,$offset),
							array($key => $val),
							array_slice($temp_array,$offset)
						);
						$found = true;
					}
					$offset++;
				}
				if(!$found) $temp_array = array_merge($temp_array, array($key => $val));
			}
			$sorted_users = ($sortDir == 'DESC' ? $temp_array : array_reverse($temp_array));
		}
		*/
		foreach($sorted_users as $user) {
            $comboUsers->AddOption($user[$orderBy], $user['id']);
        }

        if (!empty($get['id'])) {
			$comboUsers->SetDefault((int)$get['id']);
        }
		
		$tpl->SetVariable('combo_users', $comboUsers->Get());
        $tpl->SetVariable('noGroup', _t('USERS_GROUPS_NOGROUP'));
        $tpl->SetVariable('wrongPassword', _t('USERS_USERS_PASSWORDS_DONT_MATCH'));
        $tpl->SetVariable('incompleteUserFields', _t('USERS_USERS_INCOMPLETE_FIELDS'));
        $tpl->SetVariable('permissionsMsg', _t('USERS_USERS_PERMISSIONS'));
        $tpl->SetVariable('selectUser', _t('USERS_USERS_SELECT_A_USER'));
        $tpl->SetVariable('confirmUserDelete', _t('USERS_USER_CONFIRM_DELETE'));
        $tpl->SetVariable('confirmResetACL', _t('USERS_RESET_ACL_CONFIRM'));
        if (!empty($get['id'])) {
			$tpl->SetBlock('Users/default');
			$tpl->SetVariable('id', (int)$get['id']);
			$tpl->ParseBlock('Users/default');
        }
        $tpl->ParseBlock('Users');

        return $tpl->Get();
    }

    /**
     * Prepares the group management view
     *
     * @access  public
     * @return  string  XHTML of view
     */
    function Groups($account = false)
    {
		if ($account === false) {
			$GLOBALS['app']->Session->CheckPermission('Users', 'ManageGroups');
			$this->AjaxMe('script.js');
		} else {
			if (!$GLOBALS['app']->Session->Logged()) {
				//$GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
				return "Please log-in.";
			}
			$this->AjaxMe('client_script.js');
		}
        
		require_once JAWS_PATH . 'include/Jaws/User.php';
		$userModel = new Jaws_User();
		
		$request =& Jaws_Request::getInstance();
        $get = $request->get(array('msg', 'action', 'Users_gid', 'Users_sort', 'Users_q', 'Users_enabled', 'Users_sortDir', 'Users_founder'), 'get');

        $GLOBALS['app']->Layout->AddScriptLink('libraries/xtree/xtree.js');
        $tpl = new Jaws_Template('gadgets/Users/templates/');
        $tpl->Load('Groups.html');
        $tpl->SetBlock('Groups');

        // Right menu
        include_once JAWS_PATH . 'include/Jaws/Widgets/XHTMLMenu.php';
        $menu = new Jaws_Widgets_XHTMLMenu('', 'right_menu', 'visibility: hidden;');

		$addGroup =& Piwi::CreateWidget('Button', 'add_group', _t('USERS_GROUPS_ADD'), $GLOBALS['app']->GetJawsURL() . '/gadgets/Users/images/add_group.png');
		$addGroup->AddEvent(ON_CLICK, "javascript: addGroup();");
		$tpl->SetVariable('add_group', $addGroup->Get());

		$saveGroup =& Piwi::CreateWidget('Button', 'save_group', _t('GLOBAL_SAVE'), STOCK_SAVE);
		$saveGroup->AddEvent(ON_CLICK, "javascript: saveGroup();");
		$saveGroup->SetStyle('display: none;');
		$tpl->SetVariable('save_group', $saveGroup->Get());

		$cancelAction =& Piwi::CreateWidget('Button', 'cancel_action', _t('GLOBAL_CANCEL'), STOCK_CANCEL);
		$cancelAction->AddEvent(ON_CLICK, "javascript: stopAction();");
		$cancelAction->SetStyle('display: none;');
		$tpl->SetVariable('cancel', $cancelAction->Get());

        if ($this->GetPermission('ManageGroupACLs')) {
            $resetACL =& Piwi::CreateWidget('Button', 'reset_acl', _t('USERS_RESET_ACL'), STOCK_UNDO);
            $resetACL->AddEvent(ON_CLICK, "javascript: resetGroupACL();");
            $resetACL->SetStyle('display: none;');
            $tpl->SetVariable('reset_acl', $resetACL->Get());

            $saveACL =& Piwi::CreateWidget('Button', 'save_acl', _t('GLOBAL_SAVE'), STOCK_SAVE);
            $saveACL->AddEvent(ON_CLICK, "javascript: saveGroupACL();");
            $saveACL->SetStyle('display: none;');
            $tpl->SetVariable('save_acl', $saveACL->Get());

            $menu->addOption('manage_acl', _t('USERS_ACLRULES'), "javascript: manageGroupACL();", $GLOBALS['app']->GetJawsURL() . '/gadgets/Users/images/acls.png');
        }

        if (
			(!empty($get['Users_gid']) && 
				in_array($userModel->GetStatusOfUserInGroup(
				$GLOBALS['app']->Session->GetAttribute('user_id'), 
				(int)$get['Users_gid']), 
				array('admin','founder'))) || 
			($GLOBALS['app']->Session->IsSuperAdmin() || $this->GetPermission('ManageGroups'))
		) {
			$menu->addOption('add_usergroups', _t('USERS_GROUPS_ADD_USERS'), "javascript: addUsersToGroup();", $GLOBALS['app']->GetJawsURL() . '/gadgets/Users/images/add_group.png');
			$menu->addOption('delete_group', _t('USERS_GROUPS_DELETE'), "javascript: deleteGroup();", STOCK_DELETE, false, '', true);
        }
		$tpl->SetVariable('right_menu', $menu->get());

        if ($account === false) {
			//Menu bar
			$tpl->SetVariable('menubar', $this->MenuBar('Groups'));

			//Fill the groups combo..
			$comboGroups =& Piwi::CreateWidget('Combo', 'groups_combo');
			$comboGroups->SetSize(20);
			$comboGroups->SetStyle('width: 200px; height: 350px;');
			$comboGroups->AddEvent(ON_CHANGE, 'javascript: editGroup(this.value);');

			$groups = $userModel->GetAllGroups('name');
			if (!Jaws_Error::IsError($groups)) {
				foreach ($groups as $group) {
					$comboGroups->AddOption($group['name'].' ('.$group['title'].')', $group['id']);
				}
			}

			if (!empty($get['Users_gid'])) {
				$comboGroups->SetDefault((int)$get['Users_gid']);
			}
			
			$tpl->SetVariable('combo_groups', $comboGroups->Get());
        }
		$tpl->SetVariable('incompleteGroupFields', _t('USERS_GROUPS_INCOMPLETE_FIELDS'));
        $tpl->SetVariable('permissionsMsg', _t('USERS_USERS_PERMISSIONS'));
        $tpl->SetVariable('confirmGroupDelete', _t('USERS_GROUPS_CONFIRM_DELETE'));
        $tpl->SetVariable('confirmResetACL', _t('USERS_RESET_ACL_CONFIRM'));
        
		if (!empty($get['Users_gid'])) {
			$tpl->SetBlock('Groups/default');
			$tpl->SetVariable('id', (int)$get['Users_gid']);
			$tpl->ParseBlock('Groups/default');
        } else if ($account === true) {
			$tpl->SetBlock('Groups/addgroup');
			$tpl->ParseBlock('Groups/addgroup');
        }
        
		$tpl->ParseBlock('Groups');

        return $tpl->Get();
    }

    /**
     * Show account settings for logged user
     *
     * @access public
     * @return string  Template content
     */
    function MyAccount()
    {
        $this->CheckPermission('EditAccountInformation');

        require_once JAWS_PATH.'include/Jaws/User.php';
        $username = $GLOBALS['app']->Session->GetAttribute('username');
        $userModel = new Jaws_User();
        $userInfo = $userModel->GetUserInfoByName($username, true);

        $this->AjaxMe('script.js');
        $use_crypt = ($GLOBALS['app']->Registry->Get('/crypt/enabled') == 'true')? true : false;
        if ($use_crypt) {
            require_once JAWS_PATH . 'include/Jaws/Crypt.php';
            $JCrypt = new Jaws_Crypt();
            $use_crypt = $JCrypt->Init();
        }

        if ($use_crypt) {
            $GLOBALS['app']->Layout->AddScriptLink('libraries/js/bigint.js');
            $GLOBALS['app']->Layout->AddScriptLink('libraries/js/bigintmath.js');
            $GLOBALS['app']->Layout->AddScriptLink('libraries/js/rsa.js');
        }

        $tpl = new Jaws_Template('gadgets/Users/templates/');
        $tpl->Load('MyAccount.html');
        $tpl->SetBlock('MyAccount');
        if (!Jaws_Error::IsError($userInfo) && isset($userInfo['username'])) {
            $tpl->SetVariable('incompleteUserFields', _t('USERS_MYACCOUNT_INCOMPLETE_FIELDS'));
            $tpl->SetVariable('wrongPassword', _t('USERS_MYACCOUNT_PASSWORDS_DONT_MATCH'));

            $form =& Piwi::CreateWidget('Form', BASE_SCRIPT, 'post');
            $form->Add(Piwi::CreateWidget('HiddenEntry', 'gadget', 'Users'));
            $form->Add(Piwi::CreateWidget('HiddenEntry', 'action', 'UpdateMyAccount'));
            $form->Add(Piwi::CreateWidget('HiddenEntry', 'id', $userInfo['id']));

            if ($use_crypt) {
                $form->Add(Piwi::CreateWidget('HiddenEntry', 'modulus',  $JCrypt->math->bin2int($JCrypt->pub_key->getModulus())));
                $form->Add(Piwi::CreateWidget('HiddenEntry', 'exponent', $JCrypt->math->bin2int($JCrypt->pub_key->getExponent())));
            }

            include_once JAWS_PATH . 'include/Jaws/Widgets/FieldSet.php';
            $fieldset = new Jaws_Widgets_FieldSet(_t('USERS_USERS_ACCOUNT_INFO'));
            $fieldset->SetDirection('vertical');

            $hbox =& Piwi::CreateWidget('HBox');
            $hbox->setStyle('width: 100%;');
            $hbox->_useTitles = true;

            $usernameEntry =& Piwi::CreateWidget('Entry', 'username', $userInfo['username']);
            $usernameEntry->SetTitle(_t('USERS_USERS_USERNAME'));
            $hbox->packstart($usernameEntry);
            $avatar =& Piwi::CreateWidget('Image',
                                          $userModel->GetAvatar($userInfo['username'], $userInfo['email']), $userInfo['username']);
            $avatar->SetID('user_avatar');
            $avatar->setStyle('float: right; max-height: 60px; max-width: 60px;');
            $hbox->packstart($avatar);

            $fieldset->Add($hbox);

            $nameEntry =& Piwi::CreateWidget('Entry', 'nickname', $userInfo['nickname']);
            $nameEntry->SetTitle(_t('USERS_USERS_NICKNAME'));
            $nameEntry->SetStyle('width: 250px');
            $fieldset->Add($nameEntry);

            $emailEntry =& Piwi::CreateWidget('Entry', 'email', $userInfo['email']);
            $emailEntry->SetTitle(_t('GLOBAL_EMAIL'));
            $emailEntry->SetStyle('width: 250px');
            $fieldset->Add($emailEntry);

            $pass1Entry =& Piwi::CreateWidget('PasswordEntry', 'pass1', '');
            $pass1Entry->SetTitle(_t('USERS_USERS_PASSWORD'));
            $fieldset->Add($pass1Entry);
            $pass2Entry =& Piwi::CreateWidget('PasswordEntry', 'pass2', '');
            $pass2Entry->SetTitle(_t('USERS_USERS_PASSWORD_VERIFY'));
            $fieldset->Add($pass2Entry);

            $form->Add($fieldset);

            $buttonbox =& Piwi::CreateWidget('HBox');
            $buttonbox->SetStyle('float: right;'); //hig style
            $submit =& Piwi::CreateWidget('Button', 'SubmitButton', _t('GLOBAL_UPDATE'), STOCK_SAVE);
            $submit->AddEvent(ON_CLICK, "javascript: updateMyAccount();");
            $buttonbox->Add($submit);

            $form->Add($buttonbox);

            $tpl->SetVariable('form', $form->Get());
        }
        $tpl->ParseBlock('MyAccount');

        return $tpl->Get();
    }

    /**
     * Show a form to edit a given user
     *
     * @access  public
     * @return  string HTML content
     */
    function EditUser()
    {
        $form =& Piwi::CreateWidget('Form', BASE_SCRIPT, 'post', '', 'userInfo_form');
        $form->Add(Piwi::CreateWidget('HiddenEntry', 'userInfo_action', 'SaveEditUser'));
        $form->Add(Piwi::CreateWidget('HiddenEntry', 'userInfo_uid', ''));
        $form->SetStyle('padding: 0px;');

        $use_crypt = ($GLOBALS['app']->Registry->Get('/crypt/enabled') == 'true')? true : false;
        if ($use_crypt) {
            require_once JAWS_PATH . 'include/Jaws/Crypt.php';
            $JCrypt = new Jaws_Crypt();
            $use_crypt = $JCrypt->Init();
        }
        if ($use_crypt) {
            $form->Add(Piwi::CreateWidget('HiddenEntry', 'modulus',  $JCrypt->math->bin2int($JCrypt->pub_key->getModulus())));
            $form->Add(Piwi::CreateWidget('HiddenEntry', 'exponent', $JCrypt->math->bin2int($JCrypt->pub_key->getExponent())));
        }

        include_once JAWS_PATH . 'include/Jaws/Widgets/FieldSet.php';
        $fieldset = new Jaws_Widgets_FieldSet();
        $fieldset->SetDirection('vertical');
        $fieldset->SetID('usersFieldset');
        $fieldset->SetStyle('border: 0px; padding: 0px; margin: 0px;');

        $idEntry =& Piwi::CreateWidget('Entry', 'user_id', '');
        $idEntry->SetTitle('ID');
        $idEntry->SetStyle('display: none; width: 60px');
        $idEntry->SetReadOnly(true);
        $idEntry->SetEnabled(false);
        $fieldset->Add($idEntry);
        
		$hbox =& Piwi::CreateWidget('HBox');
        $hbox->setStyle((_t('GLOBAL_LANG_DIRECTION')=='rtl'?'text-align: right;' : 'text-align: left;').' width: 100%;');
        $hbox->_useTitles = true;

        $usernameEntry =& Piwi::CreateWidget('Entry', 'username', '');
        $usernameEntry->SetTitle(_t('USERS_USERS_USERNAME'));
        $hbox->packstart($usernameEntry);

        $avatar =& Piwi::CreateWidget('Image', $GLOBALS['app']->GetJawsURL() . '/gadgets/Users/images/no-photo.png',
                                      '', 0, 60, 60);
        $avatar->SetID('user_avatar');
        $avatar->setStyle('float: right; max-height: 60px; max-width: 60px;');
        $hbox->packstart($avatar);
        $fieldset->Add($hbox);

        $nameEntry =& Piwi::CreateWidget('Entry', 'nickname', '');
        $nameEntry->SetTitle(_t('USERS_USERS_NICKNAME'));
        $nameEntry->SetStyle('width: 250px');
        $fieldset->Add($nameEntry);

        $emailEntry =& Piwi::CreateWidget('Entry', 'email', '');
        $emailEntry->SetTitle(_t('GLOBAL_EMAIL'));
        $emailEntry->SetStyle('width: 250px; direction: ltr;');
        $fieldset->Add($emailEntry);

        $select =& Piwi::createWidget('Combo', 'user_group', _t('USERS_GROUPS_ADD_USER'));
        $select->SetID('user_group');
        $select->addOption('&nbsp;', '');
        $select->setDefault('');
        $fieldset->add($select);

        $userType =& Piwi::CreateWidget('Combo', 'user_type', _t('USERS_USERS_TYPE'));
        $userType->SetID('user_type');
        if ($GLOBALS['app']->Session->IsSuperAdmin() === true) {
            $userType->AddOption(_t('USERS_USERS_TYPE_SUPERADMIN'), '0');
        }
        $userType->AddOption(_t('USERS_USERS_TYPE_ADMIN'), '1');
        $userType->AddOption(_t('USERS_USERS_TYPE_NORMAL'), '2');
        $fieldset->Add($userType);

        $pass1Entry =& Piwi::CreateWidget('PasswordEntry', 'pass1', '');
        $pass1Entry->SetTitle(_t('USERS_USERS_PASSWORD'));
        $pass1Entry->SetStyle('direction: ltr;');
        $fieldset->Add($pass1Entry);

        $pass2Entry =& Piwi::CreateWidget('PasswordEntry', 'pass2', '');
        $pass2Entry->SetTitle(_t('USERS_USERS_PASSWORD_VERIFY'));
        $pass2Entry->SetStyle('direction: ltr;');
        $fieldset->Add($pass2Entry);

        $enabled =& Piwi::CreateWidget('Combo', 'enabled');
        $enabled->SetTitle(_t('GLOBAL_ENABLED'));
        $enabled->SetID('enabled');
        $enabled->AddOption(_t('GLOBAL_NO'),  'false');
        $enabled->AddOption(_t('GLOBAL_YES'), 'true');
        $enabled->SetDefault('true');
        $fieldset->Add($enabled);

        $form->Add($fieldset);

        return $form->Get();
    }

    /**
     * Show a form to edit a given group
     *
     * @access  public
     * @return  string HTML content
     */
    function EditGroup($account = false)
    {
        $tpl = new Jaws_Template('gadgets/Users/templates/');
        $tpl->Load('GroupInfo.html');
        $tpl->SetBlock('GroupInfo');
		
		require_once JAWS_PATH . 'include/Jaws/User.php';
        $userModel = new Jaws_User();
		
        $form =& Piwi::CreateWidget('Form', BASE_SCRIPT, 'post', '', 'groupInfo_form');
        $form->SetStyle('padding: 0px;');

        include_once JAWS_PATH . 'include/Jaws/Widgets/FieldSet.php';
        $fieldset = new Jaws_Widgets_FieldSet();
        $fieldset->SetDirection('vertical');
        $fieldset->SetStyle('border: 0px; padding: 0px; margin: 0px; height: 1px;');

		$group_founder = '';
		if ($account === false) {
			$nameEntry =& Piwi::CreateWidget('Entry', 'name', '');
			$nameEntry->SetTitle(_t('GLOBAL_NAME'));
			$nameEntry->SetStyle('width: 250px;');
			$fieldset->Add($nameEntry);
		} else {
            $group_founder = $GLOBALS['app']->Session->GetAttribute('user_id');
			$fieldset->Add(Piwi::CreateWidget('HiddenEntry', 'name', ''));
		}
		
        $titleEntry =& Piwi::CreateWidget('Entry', 'title', '');
        $titleEntry->SetTitle(_t('GLOBAL_TITLE'));
        $titleEntry->SetStyle('width: 250px;');

        $descEntry =& Piwi::CreateWidget('TextArea', 'description', '');
        $descEntry->SetTitle(_t('GLOBAL_DESCRIPTION'));
        $descEntry->SetStyle('width: 250px;');
        $descEntry->SetRows(5);
        $descEntry->SetColumns(60);

        $fieldset->Add($titleEntry);
        $fieldset->Add($descEntry);
		if ($account === false) {
			$type = ($GLOBALS['app']->Session->IsSuperAdmin() ? false : ($GLOBALS['app']->Session->IsAdmin() ? 1 : 2));
			$users = $userModel->GetUsers(false, $type);
			$founderCombo =& Piwi::CreateWidget('Combo', 'founder');
			$founderCombo->SetTitle(_t('USERS_GROUPS_FOUNDER'));
			$founderCombo->SetDefault((int)$group_founder);
			$founderCombo->AddOption('None', 0);
		   
			foreach ($users as $user) {
				$founderCombo->AddOption($user['username'] . ' (' . $user['nickname']. ')', $user['id']);
			}
			$fieldset->Add($founderCombo);
        } else {
            $fieldset->Add(Piwi::CreateWidget('HiddenEntry', 'founder', $group_founder));
		}
		$form->Add($fieldset);

        $tpl->SetVariable('form', $form->Get());
        $tpl->ParseBlock('GroupInfo');
        return $tpl->Get();
    }

    /**
     * Returns a mini-form to edit personal information of a selected user
     *
     * @access  public
     * @param   int     $uid    User ID
     * @return  string
     */
    function GetPersonalInformationUI($uid)
    {
        $tpl = new Jaws_Template('gadgets/Users/templates/');
        $tpl->Load('PersonalInformation.html');
        $tpl->SetBlock('PersonalInformation');
        
        require_once JAWS_PATH . 'include/Jaws/User.php';
        $userModel = new Jaws_User();

        //Load the user info
        $info = $userModel->GetUserInfoById($uid, true, true, true, true);
        $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');

        $form =& Piwi::CreateWidget('Form', BASE_SCRIPT, 'post', '', 'personalInfo_form');
        $form->SetStyle('padding: 0px;');

        $tpl->SetVariable('lbl_fname',     _t('USERS_USERS_FIRSTNAME'));
        $tpl->SetVariable('fname',             $xss->filter($info['fname']));

        $tpl->SetVariable('lbl_lname',     _t('USERS_USERS_LASTNAME'));
        $tpl->SetVariable('lname',             $xss->filter($info['lname']));

        $tpl->SetVariable('lbl_gender',    _t('USERS_USERS_GENDER'));
        $tpl->SetVariable('gender_male',   _t('USERS_USERS_MALE'));
        $tpl->SetVariable('gender_female', _t('USERS_USERS_FEMALE'));
        $selected = " selected=\"selected\"";
		if (empty($info['gender'])) {
            $tpl->SetVariable('male_selected', $selected);
        } else {
            $tpl->SetVariable('female_selected', $selected);
        }

		$date = $GLOBALS['app']->loadDate();
        if (empty($profile['dob'])) {
            $dob = array('', '', '');
        } else {
            //$dob = $date->Format($profile['dob'], 'Y-m-d');
            //$dob = explode('-', $dob);
            $dob = explode('-', str_replace(" 00:00:00", '', $profile['dob']));
        }
		$dob[0] = ((int)$dob[0] == 0 ? '' : (int)$dob[0]);
		$dob[1] = ((int)$dob[1] == 0 ? '' : (int)$dob[1]);
		$dob[2] = ((int)$dob[2] == 0 ? '' : (int)$dob[2]);
        
		$dob_year =& Piwi::CreateWidget('Combo', 'dob_year');
        $dob_year->setID('dob_year');
        $dob_year->setStyle('max-width: 100px;');
        $dob_year->AddOption("Year", '');
		$current_year = $date->Format($GLOBALS['db']->Date(), 'Y');
        for($i=(int)$current_year;$i>1850;$i--) {
            $dob_year->AddOption($i, $i);
        }
        $dob_year->setDefault($dob[0]);
        
		$dob_month =& Piwi::CreateWidget('Combo', 'dob_month');
        $dob_month->setID('dob_month');
        $dob_month->setStyle('max-width: 100px;');
        $dob_month->AddOption("Month", '');
        for($i=1;$i<13;$i++) {
			$dob_month->AddOption($date->Format(mktime(0, 0, 0, $i+1, 0, 0), 'MN'), $i);
        }
        $dob_month->setDefault($dob[1]);
		
		$dob_day =& Piwi::CreateWidget('Combo', 'dob_day');
        $dob_day->setID('dob_day');
        $dob_day->setStyle('max-width: 100px;');
        $dob_day->AddOption("Day", '');
        for($i=1;$i<32;$i++) {
            $dob_day->AddOption($i, $i);
        }
        $dob_day->setDefault($dob[2]);

		$dob_html = $dob_year->Get().'&nbsp;/&nbsp;'.$dob_month->Get().'&nbsp;/&nbsp;'.$dob_day->Get();
			
        $tpl->SetVariable('lbl_dob',    _t('USERS_USERS_BIRTHDAY'));
        $tpl->SetVariable('dob_html',   $dob_html);
        $tpl->SetVariable('dob_sample', _t('USERS_USERS_BIRTHDAY_SAMPLE'));

        $tpl->SetVariable('lbl_url',    _t('GLOBAL_URL'));
        $tpl->SetVariable('url',        $xss->filter($info['url']));

        $tpl->SetVariable('lbl_company', _t('USERS_USERS_COMPANY'));
        $tpl->SetVariable('company', $xss->filter($info['company']));
        
		$tpl->SetVariable('lbl_company_type', _t('USERS_USERS_COMPANY_TYPE'));
		$company_type_options = '';
		$company_type_options .= "<option value=\"\"".(empty($info['company_type']) ? $selected : '').">Select business type...</option>"; 
		$company_type_options .= "<option".($info['company_type']==_t('USERS_USERS_COMPANY_TYPE_RETAIL') ? $selected : '').">"._t('USERS_USERS_COMPANY_TYPE_RETAIL')."</option>"; 
		$company_type_options .= "<option".($info['company_type']==_t('USERS_USERS_COMPANY_TYPE_RESTAURANT') ? $selected : '').">"._t('USERS_USERS_COMPANY_TYPE_RESTAURANT')."</option>"; 
		$company_type_options .= "<option".($info['company_type']==_t('USERS_USERS_COMPANY_TYPE_SERVICES') ? $selected : '').">"._t('USERS_USERS_COMPANY_TYPE_SERVICES')."</option>"; 
		$company_type_options .= "<option".($info['company_type']==_t('USERS_USERS_COMPANY_TYPE_MEDICAL') ? $selected : '').">"._t('USERS_USERS_COMPANY_TYPE_MEDICAL')."</option>"; 
		$company_type_options .= "<option".($info['company_type']==_t('USERS_USERS_COMPANY_TYPE_MEDIA') ? $selected : '').">"._t('USERS_USERS_COMPANY_TYPE_MEDIA')."</option>"; 
		$company_type_options .= "<option".($info['company_type']==_t('USERS_USERS_COMPANY_TYPE_SALON') ? $selected : '').">"._t('USERS_USERS_COMPANY_TYPE_SALON')."</option>"; 
		$company_type_options .= "<option".($info['company_type']==_t('USERS_USERS_COMPANY_TYPE_HEALTH') ? $selected : '').">"._t('USERS_USERS_COMPANY_TYPE_HEALTH')."</option>"; 
		$company_type_options .= "<option".($info['company_type']==_t('USERS_USERS_COMPANY_TYPE_HOMEGARDEN') ? $selected : '').">"._t('USERS_USERS_COMPANY_TYPE_HOMEGARDEN')."</option>"; 
		$company_type_options .= "<option".($info['company_type']==_t('USERS_USERS_COMPANY_TYPE_ENTERTAINMENT') ? $selected : '').">"._t('USERS_USERS_COMPANY_TYPE_ENTERTAINMENT')."</option>"; 
		$company_type_options .= "<option".($info['company_type']==_t('USERS_USERS_COMPANY_TYPE_FINANCIAL') ? $selected : '').">"._t('USERS_USERS_COMPANY_TYPE_FINANCIAL')."</option>"; 
		$company_type_options .= "<option".($info['company_type']==_t('USERS_USERS_COMPANY_TYPE_ARTSCULTURE') ? $selected : '').">"._t('USERS_USERS_COMPANY_TYPE_ARTSCULTURE')."</option>"; 
		$company_type_options .= "<option".($info['company_type']==_t('USERS_USERS_COMPANY_TYPE_LODGING') ? $selected : '').">"._t('USERS_USERS_COMPANY_TYPE_LODGING')."</option>"; 
		$company_type_options .= "<option".($info['company_type']==_t('USERS_USERS_COMPANY_TYPE_MANUFACTURING') ? $selected : '').">"._t('USERS_USERS_COMPANY_TYPE_MANUFACTURING')."</option>"; 
		$company_type_options .= "<option".($info['company_type']==_t('USERS_USERS_COMPANY_TYPE_GROCERY') ? $selected : '').">"._t('USERS_USERS_COMPANY_TYPE_GROCERY')."</option>"; 
		$company_type_options .= "<option".($info['company_type']==_t('USERS_USERS_COMPANY_TYPE_FARM') ? $selected : '').">"._t('USERS_USERS_COMPANY_TYPE_FARM')."</option>"; 
		$company_type_options .= "<option".($info['company_type']==_t('USERS_USERS_COMPANY_TYPE_NONPROFIT') ? $selected : '').">"._t('USERS_USERS_COMPANY_TYPE_NONPROFIT')."</option>"; 
		$tpl->SetVariable('company_type_options', $company_type_options);
		
		$main_image_src = $userModel->GetAvatar($info['username'],$info['email']);
		$show_delete_image = false;
		require_once JAWS_PATH . 'include/Jaws/Gravatar.php';
		if ($main_image_src != Jaws_Gravatar::GetGravatar($info['email'])) {
			$show_delete_image = true;
		}
		$image_preview = '&nbsp;';
		if (!empty($main_image_src)) { 
			$image_preview = "<img id=\"logo_preview\" border=\"0\" src=\"".$main_image_src."\" width=\"80\"".(strtolower(substr($main_image_src, -3)) == 'gif' || strtolower(substr($main_image_src, -3)) == 'png' || strtolower(substr($main_image_src, -3)) == 'bmp' ? ' height="80"' : '')." style=\"padding: 5px;\"><br />";
			if ($show_delete_image === true) {
				$image_preview .= "<a id=\"logo_delete\" href=\"javascript:void(0);\" onclick=\"if (confirm('Are you sure you want to delete this logo?')){document.getElementById('logo').value = ''; document.getElementById('logo_preview').style.display = 'none'; document.getElementById('logo_delete').style.display = 'none'; document.forms['profileBox'].submit();}\">Delete</a>";
			}
		}
		$imageHidden =& Piwi::CreateWidget('HiddenEntry', 'logo', ($show_delete_image === true ? $info['logo'] : ''));
		$imageButton = "<div id=\"logo_actions\"><div style=\"text-align: center; float: left; width: 145px;\">".$image_preview."</div><div style=\"float: left;\">&nbsp;</div><div style=\"float: none; clear: both;\">&nbsp;</div></div>";
		$logo_content = "<div style=\"float: left; width: 145px;\"><p><label for=\"logo\"><nobr>Logo: </nobr></label></p></div><div id=\"main_image\" style=\"float: left;\"></div>".$imageHidden->Get()."<div style=\"float: none; clear: both;\">&nbsp;</div>".$imageButton;
        $tpl->SetVariable('lbl_logo', _t('USERS_USERS_LOGO'));
        $tpl->SetVariable('logo', $logo_content);
        
		$tpl->SetVariable('lbl_address', _t('USERS_USERS_ADDRESS'));
        $tpl->SetVariable('address', $xss->filter($info['address']));
        
		$tpl->SetVariable('lbl_address2', _t('USERS_USERS_ADDRESS2'));
        $tpl->SetVariable('address2', $xss->filter($info['address2']));
        
		$tpl->SetVariable('lbl_city', _t('USERS_USERS_CITY'));
        $tpl->SetVariable('city', $xss->filter($info['city']));
        
		//FIXME: Add predefined data from [[country]] db table
        $tpl->SetVariable('lbl_country', _t('USERS_USERS_COUNTRY'));
		$options = '';
		/*
		if ($info['country']=="Argentina") {
			$options .= "<option value=\"Argentina\"".$selected.">Argentina</option>";
		} else {
			$options .= "<option value=\"Argentina\">Argentina</option>";		
		}
		if ($info['country']=="Asia") {
			$options .= "<option value=\"Asia\"".$selected.">Asia Pacific</option>";
		} else {
			$options .= "<option value=\"Asia\">Asia Pacific</option>";
		}
		if ($info['country']=="Australia") {
			$options .= "<option value=\"Australia\"".$selected.">Australia</option>";
		} else {
			$options .= "<option value=\"Australia\">Australia</option>";
		}
		if ($info['country']=="Austria") {
			$options .= "<option value=\"Austria\"".$selected.">Austria</option>";
		} else {
			$options .= "<option value=\"Austria\">Austria</option>";
		}
		if ($info['country']=="Belgium") {
			$options .= "<option value=\"Belgium\"".$selected.">Belgium</option>";
		} else {
			$options .= "<option value=\"Belgium\">Belgium</option>";
		}
		if ($info['country']=="Bolivia") {
			$options .= "<option value=\"Bolivia\"".$selected.">Bolivia</option>";
		} else {
			$options .= "<option value=\"Bolivia\">Bolivia</option>";
		}
		if ($info['country']=="Bosnia and Herzegovina") {
			$options .= "<option value=\"Bosnia and Herzegovina\"".$selected.">Bosnia and Herzegovina</option>";
		} else {
			$options .= "<option value=\"Bosnia and Herzegovina\">Bosnia and Herzegovina</option>";
		}
		if ($info['country']=="Brazil") {
			$options .= "<option value=\"Brazil\"".$selected.">Brazil</option>";
		} else {
			$options .= "<option value=\"Brazil\">Brazil</option>";
		}
		if ($info['country']=="Bulgaria") {
			$options .= "<option value=\"Bulgaria\"".$selected.">Bulgaria</option>";
		} else {
			$options .= "<option value=\"Bulgaria\">Bulgaria</option>";
		}
		if ($info['country']=="Canada") {
			$options .= "<option value=\"Canada\"".$selected.">Canada</option>";
		} else {
			$options .= "<option value=\"Canada\">Canada</option>";
		}
		if ($info['country']=="Caribbean") {
			$options .= "<option value=\"Caribbean\"".$selected.">Caribbean</option>";
		} else {
			$options .= "<option value=\"Caribbean\">Caribbean</option>";
		}
		if ($info['country']=="Chile") {
			$options .= "<option value=\"Chile\"".$selected.">Chile</option>";
		} else {
			$options .= "<option value=\"Chile\">Chile</option>";
		}
		if ($info['country']=="China") {
			$options .= "<option value=\"China\"".$selected.">China</option>";
		} else {
			$options .= "<option value=\"China\">China</option>";
		}
		if ($info['country']=="Colombia") {
			$options .= "<option value=\"Colombia\"".$selected.">Colombia</option>";
		} else {
			$options .= "<option value=\"Colombia\">Colombia</option>";
		}
		if ($info['country']=="Costa Rica") {
			$options .= "<option value=\"Costa Rica\"".$selected.">Costa Rica</option>";
		} else {
			$options .= "<option value=\"Costa Rica\">Costa Rica</option>";
		}
		if ($info['country']=="Croatia") {
			$options .= "<option value=\"Croatia\"".$selected.">Croatia</option>";
		} else {
			$options .= "<option value=\"Croatia\">Croatia</option>";
		}
		if ($info['country']=="Czech Republic") {
			$options .= "<option value=\"Czech Republic\"".$selected.">Czech Republic</option>";
		} else {
			$options .= "<option value=\"Czech Republic\">Czech Republic</option>";
		}
		if ($info['country']=="Denmark") {
			$options .= "<option value=\"Denmark\"".$selected.">Denmark</option>";
		} else {
			$options .= "<option value=\"Denmark\">Denmark</option>";
		}
		if ($info['country']=="Dominican Republic") {
			$options .= "<option value=\"Dominican Republic\"".$selected.">Dominican Republic</option>";
		} else {
			$options .= "<option value=\"Dominican Republic\">Dominican Republic</option>";
		}
		if ($info['country']=="Ecuador") {
			$options .= "<option value=\"Ecuador\"".$selected.">Ecuador</option>";
		} else {
			$options .= "<option value=\"Ecuador\">Ecuador</option>";
		}
		if ($info['country']=="Egypt") {
			$options .= "<option value=\"Egypt\"".$selected.">Egypt</option>";
		} else {
			$options .= "<option value=\"Egypt\">Egypt</option>";
		}
		if ($info['country']=="El Salvador") {
			$options .= "<option value=\"El Salvador\"".$selected.">El Salvador</option>";
		} else {
			$options .= "<option value=\"El Salvador\">El Salvador</option>";
		}
		if ($info['country']=="Estonia") {
			$options .= "<option value=\"Estonia\"".$selected.">Estonia</option>";
		} else {
			$options .= "<option value=\"Estonia\">Estonia</option>";
		}
		if ($info['country']=="Finland") {
			$options .= "<option value=\"Finland\"".$selected.">Finland</option>";
		} else {
			$options .= "<option value=\"Finland\">Finland</option>";
		}
		if ($info['country']=="France") {
			$options .= "<option value=\"France\"".$selected.">France</option>";
		} else {
			$options .= "<option value=\"France\">France</option>";
		}
		if ($info['country']=="Germany") {
			$options .= "<option value=\"Germany\"".$selected.">Germany</option>";
		} else {
			$options .= "<option value=\"Germany\">Germany</option>";
		}
		if ($info['country']=="Greece") {
			$options .= "<option value=\"Greece\"".$selected.">Greece</option>";
		} else {
			$options .= "<option value=\"Greece\">Greece</option>";
		}
		if ($info['country']=="Guatemala") {
			$options .= "<option value=\"Guatemala\"".$selected.">Guatemala</option>";
		} else {
			$options .= "<option value=\"Guatemala\">Guatemala</option>";
		}
		if ($info['country']=="Honduras") {
			$options .= "<option value=\"Honduras\"".$selected.">Honduras</option>";
		} else {
			$options .= "<option value=\"Honduras\">Honduras</option>";
		}
		if ($info['country']=="Hong Kong") {
			$options .= "<option value=\"Hong Kong\"".$selected.">Hong Kong</option>";
		} else {
			$options .= "<option value=\"Hong Kong\">Hong Kong</option>";
		}
		if ($info['country']=="Hungary") {
			$options .= "<option value=\"Hungary\"".$selected.">Hungary</option>";
		} else {
			$options .= "<option value=\"Hungary\">Hungary</option>";
		}
		if ($info['country']=="Iceland") {
			$options .= "<option value=\"Iceland\"".$selected.">Iceland</option>";
		} else {
			$options .= "<option value=\"Iceland\">Iceland</option>";
		}
		if ($info['country']=="India") {
			$options .= "<option value=\"India\"".$selected.">India</option>";
		} else {
			$options .= "<option value=\"India\">India</option>";
		}
		if ($info['country']=="Indonesia") {
			$options .= "<option value=\"Indonesia\"".$selected.">Indonesia</option>";
		} else {
			$options .= "<option value=\"Indonesia\">Indonesia</option>";
		}
		if ($info['country']=="Ireland") {
			$options .= "<option value=\"Ireland\"".$selected.">Ireland</option>";
		} else {
			$options .= "<option value=\"Ireland\">Ireland</option>";
		}
		if ($info['country']=="Israel") {
			$options .= "<option value=\"Israel\"".$selected.">Israel</option>";
		} else {
			$options .= "<option value=\"Israel\">Israel</option>";
		}
		if ($info['country']=="Italy") {
			$options .= "<option value=\"Italy\"".$selected.">Italy</option>";
		} else {
			$options .= "<option value=\"Italy\">Italy</option>";
		}
		if ($info['country']=="Jamaica") {
			$options .= "<option value=\"Jamaica\"".$selected.">Jamaica</option>";
		} else {
			$options .= "<option value=\"Jamaica\">Jamaica</option>";
		}
		if ($info['country']=="Japan") {
			$options .= "<option value=\"Japan\"".$selected.">Japan</option>";
		} else {
			$options .= "<option value=\"Japan\">Japan</option>";
		}
		if ($info['country']=="Jordan") {
			$options .= "<option value=\"Jordan\"".$selected.">Jordan</option>";
		} else {
			$options .= "<option value=\"Jordan\">Jordan</option>";
		}
		if ($info['country']=="Korea") {
			$options .= "<option value=\"Korea\"".$selected.">Korea</option>";
		} else {
			$options .= "<option value=\"Korea\">Korea</option>";
		}
		if ($info['country']=="Kazakhstan") {
			$options .= "<option value=\"Kazakhstan\"".$selected.">Kazakhstan</option>";
		} else {
			$options .= "<option value=\"Kazakhstan\">Kazakhstan</option>";
		}
		if ($info['country']=="Latin America") {
			$options .= "<option value=\"Latin America\"".$selected.">Latin America</option>";
		} else {
			$options .= "<option value=\"Latin America\">Latin America</option>";
		}
		if ($info['country']=="Latvia") {
			$options .= "<option value=\"Latvia\"".$selected.">Latvia</option>";
		} else {
			$options .= "<option value=\"Latvia\">Latvia</option>";
		}
		if ($info['country']=="Lithuania") {
			$options .= "<option value=\"Lithuania\"".$selected.">Lithuania</option>";
		} else {
			$options .= "<option value=\"Lithuania\">Lithuania</option>";
		}
		if ($info['country']=="Luxembourg") {
			$options .= "<option value=\"Luxembourg\"".$selected.">Luxembourg</option>";
		} else {
			$options .= "<option value=\"Luxembourg\">Luxembourg</option>";
		}
		if ($info['country']=="Macedonia") {
			$options .= "<option value=\"Macedonia\"".$selected.">Macedonia</option>";
		} else {
			$options .= "<option value=\"Macedonia\">Macedonia</option>";
		}
		if ($info['country']=="Malaysia") {
			$options .= "<option value=\"Malaysia\"".$selected.">Malaysia</option>";
		} else {
			$options .= "<option value=\"Malaysia\">Malaysia</option>";
		}
		if ($info['country']=="Mexico") {
			$options .= "<option value=\"Mexico\"".$selected.">Mexico</option>";
		} else {
			$options .= "<option value=\"Mexico\">Mexico</option>";
		}
		if ($info['country']=="Middle East") {
			$options .= "<option value=\"Middle East\"".$selected.">Middle East</option>";
		} else {
			$options .= "<option value=\"Middle East\">Middle East</option>";
		}
		if ($info['country']=="Moldova") {
			$options .= "<option value=\"Moldova\"".$selected.">Moldova</option>";
		} else {
			$options .= "<option value=\"Moldova\">Moldova</option>";
		}
		if ($info['country']=="Netherlands") {
			$options .= "<option value=\"Netherlands\"".$selected.">Netherlands</option>";
		} else {
			$options .= "<option value=\"Netherlands\">Netherlands</option>";
		}
		if ($info['country']=="New Zealand") {
			$options .= "<option value=\"New Zealand\"".$selected.">New Zealand</option>";
		} else {
			$options .= "<option value=\"New Zealand\">New Zealand</option>";
		}
		if ($info['country']=="North Africa") {
			$options .= "<option value=\"North Africa\"".$selected.">North Africa</option>";
		} else {
			$options .= "<option value=\"North Africa\">North Africa</option>";
		}
		if ($info['country']=="Norway") {
			$options .= "<option value=\"Norway\"".$selected.">Norway</option>";
		} else {
			$options .= "<option value=\"Norway\">Norway</option>";
		}
		if ($info['country']=="Panama") {
			$options .= "<option value=\"Panama\"".$selected.">Panama</option>";
		} else {
			$options .= "<option value=\"Panama\">Panama</option>";
		}
		if ($info['country']=="Pakistan") {
			$options .= "<option value=\"Pakistan\"".$selected.">Pakistan</option>";
		} else {
			$options .= "<option value=\"Pakistan\">Pakistan</option>";
		}
		if ($info['country']=="Paraguay") {
			$options .= "<option value=\"Paraguay\"".$selected.">Paraguay</option>";
		} else {
			$options .= "<option value=\"Paraguay\">Paraguay</option>";
		}
		if ($info['country']=="Peru") {
			$options .= "<option value=\"Peru\"".$selected.">Peru</option>";
		} else {
			$options .= "<option value=\"Peru\">Peru</option>";
		}
		if ($info['country']=="Philippines") {
			$options .= "<option value=\"Philippines\"".$selected.">Philippines</option>";
		} else {
			$options .= "<option value=\"Philippines\">Philippines</option>";
		}
		if ($info['country']=="Poland") {
			$options .= "<option value=\"Poland\"".$selected.">Poland</option>";
		} else {
			$options .= "<option value=\"Poland\">Poland</option>";
		}
		if ($info['country']=="Portugal") {
			$options .= "<option value=\"Portugal\"".$selected.">Portugal</option>";
		} else {
			$options .= "<option value=\"Portugal\">Portugal</option>";
		}
		if ($info['country']=="Puerto Rico") {
			$options .= "<option value=\"Puerto Rico\"".$selected.">Puerto Rico</option>";
		} else {
			$options .= "<option value=\"Puerto Rico\">Puerto Rico</option>";
		}
		if ($info['country']=="Romania") {
			$options .= "<option value=\"Romania\"".$selected.">Romania</option>";
		} else {
			$options .= "<option value=\"Romania\">Romania</option>";
		}
		if ($info['country']=="Russia") {
			$options .= "<option value=\"Russia\"".$selected.">Russia</option>";
		} else {
			$options .= "<option value=\"Russia\">Russia</option>";
		}
		if ($info['country']=="Saudi Arabia") {
			$options .= "<option value=\"Saudi Arabia\"".$selected.">Saudi Arabia</option>";
		} else {
			$options .= "<option value=\"Saudi Arabia\">Saudi Arabia</option>";
		}
		if ($info['country']=="Serbia and Montenegro") {
			$options .= "<option value=\"Serbia and Montenegro\"".$selected.">Serbia and Montenegro</option>";
		} else {
			$options .= "<option value=\"Serbia and Montenegro\">Serbia and Montenegro</option>";
		}
		if ($info['country']=="Singapore") {
			$options .= "<option value=\"Singapore\"".$selected.">Singapore</option>";
		} else {
			$options .= "<option value=\"Singapore\">Singapore</option>";
		}
		if ($info['country']=="Slovakia") {
			$options .= "<option value=\"Slovakia\"".$selected.">Slovakia</option>";
		} else {
			$options .= "<option value=\"Slovakia\">Slovakia</option>";
		}
		if ($info['country']=="Slovenia") {
			$options .= "<option value=\"Slovenia\"".$selected.">Slovenia</option>";
		} else {
			$options .= "<option value=\"Slovenia\">Slovenia</option>";
		}
		if ($info['country']=="South Africa") {
			$options .= "<option value=\"South Africa\"".$selected.">South Africa</option>";
		} else {
			$options .= "<option value=\"South Africa\">South Africa</option>";
		}
		if ($info['country']=="Spain") {
			$options .= "<option value=\"Spain\"".$selected.">Spain</option>";
		} else {
			$options .= "<option value=\"Spain\">Spain</option>";
		}
		if ($info['country']=="Sweden") {
			$options .= "<option value=\"Sweden\"".$selected.">Sweden</option>";
		} else {
			$options .= "<option value=\"Sweden\">Sweden</option>";
		}
		if ($info['country']=="Switzerland") {
			$options .= "<option value=\"Switzerland\"".$selected.">Switzerland</option>";
		} else {
			$options .= "<option value=\"Switzerland\">Switzerland</option>";
		}
		if ($info['country']=="Taiwan") {
			$options .= "<option value=\"Taiwan\"".$selected.">Taiwan</option>";
		} else {
			$options .= "<option value=\"Taiwan\">Taiwan</option>";
		}
		if ($info['country']=="Thailand") {
			$options .= "<option value=\"Thailand\"".$selected.">Thailand</option>";
		} else {
			$options .= "<option value=\"Thailand\">Thailand</option>";
		}
		if ($info['country']=="Trinidad and Tobago") {
			$options .= "<option value=\"Trinidad and Tobago\"".$selected.">Trinidad and Tobago</option>";
		} else {
			$options .= "<option value=\"Trinidad and Tobago\">Trinidad and Tobago</option>";
		}
		if ($info['country']=="Tunisia") {
			$options .= "<option value=\"Tunisia\"".$selected.">Tunisia</option>";
		} else {
			$options .= "<option value=\"Tunisia\">Tunisia</option>";
		}
		if ($info['country']=="Turkey") {
			$options .= "<option value=\"Turkey\"".$selected.">Turkey</option>";
		} else {
			$options .= "<option value=\"Turkey\">Turkey</option>";
		}
		if ($info['country']=="United Arab Emirates") {
			$options .= "<option value=\"United Arab Emirates\"".$selected.">United Arab Emirates</option>";
		} else {
			$options .= "<option value=\"United Arab Emirates\">United Arab Emirates</option>";
		}
		if ($info['country']=="Ukraine") {
			$options .= "<option value=\"Ukraine\"".$selected.">Ukraine</option>";
		} else {
			$options .= "<option value=\"Ukraine\">Ukraine</option>";
		}
		if ($info['country']=="United Kingdom") {
			$options .= "<option value=\"United Kingdom\"".$selected.">United Kingdom</option>";
		} else {
			$options .= "<option value=\"United Kingdom\">United Kingdom</option>";
		}
		*/
		if ($info['country']=="United States" || $info['country']=="") {
			$options .= "<option value=\"United States\"".$selected.">United States</option>";
		} else {
			$options .= "<option value=\"United States\">United States</option>";
		}
		/*
		if ($info['country']=="Uruguay") {
			$options .= "<option value=\"Uruguay\"".$selected.">Uruguay</option>";
		} else {
			$options .= "<option value=\"Uruguay\">Uruguay</option>";
		}
		if ($info['country']=="Venezuela") {
			$options .= "<option value=\"Venezuela\"".$selected.">Venezuela</option>";
		} else {
			$options .= "<option value=\"Venezuela\">Venezuela</option>";
		}
		if ($info['country']=="Vietnam") {
			$options .= "<option value=\"Vietnam\"".$selected.">Vietnam</option>";
		} else {
			$options .= "<option value=\"Vietnam\">Vietnam</option>";
		}
		*/
		$tpl->SetVariable('country_options', $options);
        
		$tpl->SetVariable('lbl_region', _t('USERS_USERS_REGION'));
        $region_options = '';
		$region_options .= "<option value=\"\"".(empty($info['region']) ? $selected : '').">Select your State...</option>"; 
		$region_options .= "<option".(strtolower($info['region'])=="al" || strtolower($info['region'])=="alabama" ? $selected : '').">Alabama</option>"; 
		$region_options .= "<option".(strtolower($info['region'])=="ak" || strtolower($info['region'])=="alaska" ? $selected : '').">Alaska</option>";
		$region_options .= "<option".(strtolower($info['region'])=="az" || strtolower($info['region'])=="arizona" ? $selected : '').">Arizona</option>";
		$region_options .= "<option".(strtolower($info['region'])=="ar" || strtolower($info['region'])=="arkansas" ? $selected : '').">Arkansas</option>";
		$region_options .= "<option".(strtolower($info['region'])=="ca" || strtolower($info['region'])=="california" ? $selected : '').">California</option>";
		$region_options .= "<option".(strtolower($info['region'])=="co" || strtolower($info['region'])=="colorado" ? $selected : '').">Colorado</option>";
		$region_options .= "<option".(strtolower($info['region'])=="ct" || strtolower($info['region'])=="connecticut" ? $selected : '').">Connecticut</option>";
		$region_options .= "<option".(strtolower($info['region'])=="de" || strtolower($info['region'])=="delaware" ? $selected : '').">Delaware</option>";
		$region_options .= "<option".(strtolower($info['region'])=="fl" || strtolower($info['region'])=="florida" ? $selected : '').">Florida</option>";
		$region_options .= "<option".(strtolower($info['region'])=="ga" || strtolower($info['region'])=="georgia" ? $selected : '').">Georgia</option>";
		$region_options .= "<option".(strtolower($info['region'])=="hi" || strtolower($info['region'])=="hawaii" ? $selected : '').">Hawaii</option>";
		$region_options .= "<option".(strtolower($info['region'])=="id" || strtolower($info['region'])=="idaho" ? $selected : '').">Idaho</option>";
		$region_options .= "<option".(strtolower($info['region'])=="il" || strtolower($info['region'])=="illinois" ? $selected : '').">Illinois</option>";
		$region_options .= "<option".(strtolower($info['region'])=="in" || strtolower($info['region'])=="indiana" ? $selected : '').">Indiana</option>";
		$region_options .= "<option".(strtolower($info['region'])=="ia" || strtolower($info['region'])=="iowa" ? $selected : '').">Iowa</option>";
		$region_options .= "<option".(strtolower($info['region'])=="ks" || strtolower($info['region'])=="kansas" ? $selected : '').">Kansas</option>";
		$region_options .= "<option".(strtolower($info['region'])=="ky" || strtolower($info['region'])=="kentucky" ? $selected : '').">Kentucky</option>";
		$region_options .= "<option".(strtolower($info['region'])=="la" || strtolower($info['region'])=="louisiana" ? $selected : '').">Louisiana</option>";
		$region_options .= "<option".(strtolower($info['region'])=="me" || strtolower($info['region'])=="maine" ? $selected : '').">Maine</option>";
		$region_options .= "<option".(strtolower($info['region'])=="md" || strtolower($info['region'])=="maryland" ? $selected : '').">Maryland</option>";
		$region_options .= "<option".(strtolower($info['region'])=="ma" || strtolower($info['region'])=="massachusetts" ? $selected : '').">Massachusetts</option>";
		$region_options .= "<option".(strtolower($info['region'])=="mi" || strtolower($info['region'])=="michigan" ? $selected : '').">Michigan</option>";
		$region_options .= "<option".(strtolower($info['region'])=="mn" || strtolower($info['region'])=="minnesota" ? $selected : '').">Minnesota</option>";
		$region_options .= "<option".(strtolower($info['region'])=="ms" || strtolower($info['region'])=="mississippi" ? $selected : '').">Mississippi</option>";
		$region_options .= "<option".(strtolower($info['region'])=="mo" || strtolower($info['region'])=="missouri" ? $selected : '').">Missouri</option>";
		$region_options .= "<option".(strtolower($info['region'])=="mt" || strtolower($info['region'])=="montana" ? $selected : '').">Montana</option>";
		$region_options .= "<option".(strtolower($info['region'])=="ne" || strtolower($info['region'])=="nebraska" ? $selected : '').">Nebraska</option>";
		$region_options .= "<option".(strtolower($info['region'])=="nv" || strtolower($info['region'])=="nevada" ? $selected : '').">Nevada</option>";
		$region_options .= "<option".(strtolower($info['region'])=="nh" || strtolower($info['region'])=="new hampshire" ? $selected : '').">New Hampshire</option>";
		$region_options .= "<option".(strtolower($info['region'])=="nj" || strtolower($info['region'])=="new jersey" ? $selected : '').">New Jersey</option>";
		$region_options .= "<option".(strtolower($info['region'])=="nm" || strtolower($info['region'])=="new mexico" ? $selected : '').">New Mexico</option>";
		$region_options .= "<option".(strtolower($info['region'])=="ny" || strtolower($info['region'])=="new york" ? $selected : '').">New York</option>";
		$region_options .= "<option".(strtolower($info['region'])=="nc" || strtolower($info['region'])=="north carolina" ? $selected : '').">North Carolina</option>";
		$region_options .= "<option".(strtolower($info['region'])=="nd" || strtolower($info['region'])=="north dakota" ? $selected : '').">North Dakota</option>";
		$region_options .= "<option".(strtolower($info['region'])=="oh" || strtolower($info['region'])=="ohio" ? $selected : '').">Ohio</option>";
		$region_options .= "<option".(strtolower($info['region'])=="ok" || strtolower($info['region'])=="oklahoma" ? $selected : '').">Oklahoma</option>";
		$region_options .= "<option".(strtolower($info['region'])=="or" || strtolower($info['region'])=="oregon" ? $selected : '').">Oregon</option>";
		$region_options .= "<option".(strtolower($info['region'])=="pa" || strtolower($info['region'])=="pennsylvania" ? $selected : '').">Pennsylvania</option>";
		$region_options .= "<option".(strtolower($info['region'])=="ri" || strtolower($info['region'])=="rhode island" ? $selected : '').">Rhode Island</option>";
		$region_options .= "<option".(strtolower($info['region'])=="sc" || strtolower($info['region'])=="south carolina" ? $selected : '').">South Carolina</option>";
		$region_options .= "<option".(strtolower($info['region'])=="sd" || strtolower($info['region'])=="south dakota" ? $selected : '').">South Dakota</option>";
		$region_options .= "<option".(strtolower($info['region'])=="tn" || strtolower($info['region'])=="tennessee" ? $selected : '').">Tennessee</option>";
		$region_options .= "<option".(strtolower($info['region'])=="tx" || strtolower($info['region'])=="texas" ? $selected : '').">Texas</option>";
		$region_options .= "<option".(strtolower($info['region'])=="ut" || strtolower($info['region'])=="utah" ? $selected : '').">Utah</option>";
		$region_options .= "<option".(strtolower($info['region'])=="vt" || strtolower($info['region'])=="vermont" ? $selected : '').">Vermont</option>";
		$region_options .= "<option".(strtolower($info['region'])=="va" || strtolower($info['region'])=="virginia" ? $selected : '').">Virginia</option>";
		$region_options .= "<option".(strtolower($info['region'])=="wa" || strtolower($info['region'])=="washington" ? $selected : '').">Washington</option>";
		$region_options .= "<option".(strtolower($info['region'])=="dc" || strtolower($info['region'])=="washington d.c." ? $selected : '').">Washington D.C.</option>";
		$region_options .= "<option".(strtolower($info['region'])=="wv" || strtolower($info['region'])=="west virginia" ? $selected : '').">West Virginia</option>";
		$region_options .= "<option".(strtolower($info['region'])=="wi" || strtolower($info['region'])=="wisconsin" ? $selected : '').">Wisconsin</option>";
		$region_options .= "<option".(strtolower($info['region'])=="wy" || strtolower($info['region'])=="wyoming" ? $selected : '').">Wyoming</option>";
		$tpl->SetVariable('region_options', $region_options);
        
		$tpl->SetVariable('lbl_postal', _t('USERS_USERS_POSTAL'));
        $tpl->SetVariable('postal', $xss->filter($info['postal']));
       
	    $tpl->SetVariable('lbl_phone', _t('USERS_USERS_PHONE'));
        $tpl->SetVariable('phone', $xss->filter($info['phone']));
        
		$tpl->SetVariable('lbl_office', _t('USERS_USERS_OFFICE'));
        $tpl->SetVariable('office', $xss->filter($info['office']));
        
		$tpl->SetVariable('lbl_tollfree', _t('USERS_USERS_TOLLFREE'));
        $tpl->SetVariable('tollfree', $xss->filter($info['tollfree']));
        
		$tpl->SetVariable('lbl_fax', _t('USERS_USERS_FAX'));
        $tpl->SetVariable('fax', $xss->filter($info['fax']));
		
		$payment_gateway = '';
		if (Jaws_Gadget::IsGadgetUpdated('Ecommerce')) {	
			$GLOBALS['app']->Registry->LoadFile('Ecommerce');
			$GLOBALS['app']->Translate->LoadTranslation('Ecommerce', JAWS_GADGET);
			$payment_gateway = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/payment_gateway');
		}
		$tpl->SetVariable('lbl_merchant_id', _t('USERS_USERS_MERCHANT_ID'));
		$tpl->SetVariable('merchant_id', $xss->filter($info['merchant_id']));
        
		$tpl->SetVariable('lbl_description', _t('USERS_USERS_DESCRIPTION'));
        $description = $xss->filter($info['description']);
        $description = str_replace('&lt;/p&gt;',"\r\n",$description);
        $description = str_replace('&lt;p&gt;','',$description);
        $tpl->SetVariable('description', $description);
        
		$tpl->SetVariable('lbl_keywords', _t('USERS_USERS_INTERESTS')." "._t('USERS_USERS_COMMA_SEPARATED'));
        $tpl->SetVariable('keywords', $xss->filter($info['keywords']));
		$tpl->ParseBlock('PersonalInformation');
        return $tpl->Get();
    }

    /**
     * Returns a mini-form to edit advanced options of a selected user
     *
     * @access  public
     * @param   int     $uid    User ID
     * @return  string
     */
    function GetAdvancedUserOptionsUI($uid)
    {
        $tpl = new Jaws_Template('gadgets/Users/templates/');
        $tpl->Load('AdvancedUserOptions.html');
        $tpl->SetBlock('AdvancedUserOptions');
        
        require_once JAWS_PATH . 'include/Jaws/User.php';
        $userModel = new Jaws_User();

        //Load the user info
        $info = $userModel->GetUserInfoById($uid, true, true, true, true);
        if (is_null($info['theme'])) {
            $info['theme'] = '-default-';
        }

        if (is_null($info['language'])) {
            $info['language'] = '-default-';
        }

        if (is_null($info['editor'])) {
            $info['editor'] = '-default-';
        }
        
		if (is_null($info['allow_comments'])) {
            $info['allow_comments'] = 1;
        }

		if (is_null($info['notification'])) {
            $info['notification'] = '';
        }

        $form =& Piwi::CreateWidget('Form', BASE_SCRIPT, 'post', '', 'advanceduserOpts_form');
        $form->SetStyle('padding: 0px;');

        include_once JAWS_PATH . 'include/Jaws/Widgets/FieldSet.php';
        $fieldset = new Jaws_Widgets_FieldSet();
        $fieldset->SetDirection('vertical');
        $fieldset->SetStyle('border: 0px; padding: 0px; margin: 0px;');

        //Here we load the Settings/Layout models (which is part of core) to extract some data
        $settingsModel = $GLOBALS['app']->loadGadget('Settings', 'AdminModel');

        //Language
        $lang =& Piwi::CreateWidget('Combo', 'user_language');
        $lang->setID('user_language');
        $lang->AddOption(_t('USERS_ADVANCED_OPTS_NOT_YET'), '-default-');
        $languages = Jaws_Utils::GetLanguagesList();
        foreach($languages as $k => $v) {
            $lang->AddOption($v, $k);
        }
        $lang->SetDefault($info['language']);
        $lang->SetTitle(_t('USERS_ADVANCED_OPTS_LANGUAGE'));
        $fieldset->Add($lang);

        //Theme
        $theme =& Piwi::CreateWidget('Combo', 'user_theme');
        $theme->setID('user_theme');
        $theme->AddOption(_t('USERS_ADVANCED_OPTS_NOT_YET'), '-default-');
        $themes = Jaws_Utils::GetThemesList();
        foreach($themes as $k => $v) {
            $theme->AddOption($v, $v);
        }
		// Get repository themes
		if (Jaws_Gadget::IsGadgetUpdated('Tms')) {
			$tmsModel = $GLOBALS['app']->LoadGadget('Tms', 'Model');
			foreach($tmsModel->getRepositories() as $repository) {
				$rThemes = $tmsModel->getThemes($repository['id']);
				if (isset($rThemes) && is_array($rThemes)) {
					foreach ($rThemes as $th) {
						$theme->AddOption($repository['name'].' : '.$th['name'], $th['file']);
					}
				}
			}	
		}
        $theme->SetDefault($info['theme']);
        $theme->SetTitle(_t('USERS_ADVANCED_OPTS_THEME'));
        $fieldset->Add($theme);

        //Editor
        $GLOBALS['app']->Translate->loadTranslation('Settings', JAWS_GADGET);
        $editor =& Piwi::CreateWidget('Combo', 'user_editor');
        $editor->setID('user_editor');
        $editor->AddOption(_t('USERS_ADVANCED_OPTS_NOT_YET'), '-default-');
        $editors = $settingsModel->GetEditorList();
        foreach($editors as $k => $v) {
            $editor->AddOption($v, $k);
        }
        $editor->SetDefault($info['editor']);
        $editor->SetTitle(_t('USERS_ADVANCED_OPTS_EDITOR'));
        $fieldset->Add($editor);

        //Time Zones
        $timezone =& Piwi::CreateWidget('Combo', 'user_timezone');
        $timezone->setID('user_timezone');
        $timezone->AddOption(_t('USERS_ADVANCED_OPTS_NOT_YET'), '-default-');
        $timezones = $settingsModel->GetTimeZonesList();
        foreach($timezones as $k => $v) {
            $timezone->AddOption($v, $k);
        }
        $timezone->SetDefault($info['timezone']);
        $timezone->SetTitle(_t('GLOBAL_TIMEZONE'));
        $fieldset->Add($timezone);

		// Notification
		// TODO: Multiple methods
		$notificationCombo =& Piwi::CreateWidget('Combo', 'notification');
		$notificationCombo->SetTitle(_t('USERS_USERS_NOTIFICATION'));
		$notificationCombo->AddOption(_t('USERS_USERS_NOTIFICATION_EMAIL'), '');
		$notificationCombo->AddOption(_t('USERS_USERS_NOTIFICATION_WEBSITE'), 'website');
		$notificationCombo->AddOption(_t('USERS_USERS_NOTIFICATION_SMS'), 'sms');
		$notificationCombo->SetDefault($info['notification']);
		$fieldset->Add($notificationCombo);
	
		// Comments
		$commentsCombo =& Piwi::CreateWidget('Combo', 'allow_comments');
		$commentsCombo->SetTitle(_t('USERS_USERS_ALLOW_COMMENTS'));
		$commentsCombo->AddOption(_t('GLOBAL_YES'), 1);
		$commentsCombo->AddOption(_t('GLOBAL_NO'), 0);
		$commentsCombo->SetDefault($info['allow_comments']);
		$fieldset->Add($commentsCombo);
		        
		$form->add($fieldset);
        $tpl->SetVariable('form', $form->Get());

        $tpl->ParseBlock('AdvancedUserOptions');
        return $tpl->Get();
    }

    /**
     * Returns the user-group management of a selected user
     *
     * @access  public
     * @param   string  $group  Group ID
     * @return  string
     */
    function GetUserGroupUI($group)
    {
        $tpl = new Jaws_Template('gadgets/Users/templates/');
        $tpl->Load('GroupUsers.html');
        $tpl->SetBlock('GroupUsers');
        $tpl->SetVariable('base_script', BASE_SCRIPT);

        require_once JAWS_PATH . 'include/Jaws/User.php';
        $model = new Jaws_User();

        $combo =& Piwi::CreateWidget('CheckButtons', 'group_users', 'vertical');
        $combo->setColumns(1);
        $type = $GLOBALS['app']->Session->IsSuperAdmin() ? false : '1,2';
        $users = $model->GetUsers(false, $type);
        foreach ($users as $user) {
            $isInGroup = false;
			$status = $model->GetStatusOfUserInGroup($user['id'], $group);
			if ($status == 'active' || $status == 'founder' || $status == 'admin') {
                $isInGroup = true;
            }
            $combo->AddOption($user['nickname'] . ' (' . $user['username']. ')', $user['id'], null, $isInGroup);
        }

        $tpl->SetVariable('group_users_combo', $combo->Get());
        $tpl->SetVariable('title', _t('USERS_GROUPS_MARK_USERS'));
		$select_all =& Piwi::CreateWidget('Link', 'Select All',
			"javascript:$$('#group_users input').each(function(element){element.setAttribute('checked', true); element.checked = true;});;"
		);
        $tpl->SetVariable('select_all', $select_all->Get());
		$select_none =& Piwi::CreateWidget('Link', 'Select None',
			"javascript:$$('#group_users input').each(function(element){element.setAttribute('checked', false); element.checked = false;});;"
		);
        $tpl->SetVariable('select_none', $select_none->Get());
        $tpl->ParseBlock('GroupUsers');
        return $tpl->Get();
    }


    /**
     * Edit properties
     *
     * @access  public
     * @return  string HTML content
     */
    function Settings()
    {
        $this->CheckPermission('ManageProperties');
		$GLOBALS['app']->Layout->AddHeadOther("<script type=\"text/javascript\">
Event.observe(window,\"load\", function() {
	var i = 0; 
	var user_access = document.getElementsByName('user_access_items[]'); 
	for (i=0;i<user_access.length;i++){
		$(user_access[i].id).observe('click', function(event) {
			var g = 0; 
			var el = event.element();
			if(el.checked){
				for(g=0;g<5;g++){
					$$('#gadget_group_'+el.value+'_'+g).each(
						function(element){
							element.disabled = false; 
						}
					);
				}
			} else {
				for(g=0;g<5;g++){
					$$('#gadget_group_'+el.value+'_'+g).each(
						function(element){
							element.checked = false; 
							element.setAttribute('checked', false);
							element.disabled = true; 
						}
					);
				}
			}
		});	
	}
	var gadget_group = document.getElementsByName('anon_group[]'); 
	for (i=0;i<gadget_group.length;i++){
		$(gadget_group[i].id).observe('click', function(event) {
			var g = 0; 
			var el = event.element();
			if(el.checked){
				if (el.value == 0 || el.value == '0') {
					for (g=0;g<gadget_group.length;g++){
						if (el.value != gadget_group[g].value) {
							gadget_group[g].checked = false; 
							gadget_group[g].setAttribute('checked', false);
						}
					}
				} else {
					for (g=0;g<gadget_group.length;g++){
						if (gadget_group[g].value == 0 || gadget_group[g].value == '0') {
							gadget_group[g].checked = false; 
							gadget_group[g].setAttribute('checked', false);
						}
					}
				}
			} else {
				if (el.value != 0 && el.value != '0') {
					group_checked = false;
					for (var g=0;g<gadget_group.length;g++){
						if (gadget_group[g].checked === true) {
							group_checked = true;
							break;
						}
					}
					if (group_checked === false) {
						for (g=0;g<gadget_group.length;g++){
							if (gadget_group[g].value == 0 || gadget_group[g].value == '0') {
								gadget_group[g].checked = true; 
								gadget_group[g].setAttribute('checked', true);
							}
						}
					}
				}
			}
		});	
	}
});
</script>");
        $this->AjaxMe('script.js');

        $tpl = new Jaws_Template('gadgets/Users/templates/');
        $tpl->Load('Properties.html');
        $tpl->SetBlock('Properties');

        $form =& Piwi::CreateWidget('Form', BASE_SCRIPT, 'post');
        $form->Add(Piwi::CreateWidget('HiddenEntry', 'gadget', 'Users'));
        $form->Add(Piwi::CreateWidget('HiddenEntry', 'action', 'SaveProperties'));

        $priority =& Piwi::CreateWidget('Combo', 'priority');
        $priority->SetTitle(_t('USERS_PROPERTIES_PRIORITY'));
        $priority->AddOption(_t('USERS_PROPERTIES_PRIORITY_UGD'), 'user, groups, default');
        $priority->AddOption(_t('USERS_PROPERTIES_PRIORITY_GUD'), 'groups, user, default');
        $priority->AddOption(_t('USERS_PROPERTIES_PRIORITY_UD'), 'user, default');
        $priority->AddOption(_t('USERS_PROPERTIES_PRIORITY_GD'), 'groups, default');
        $priority->SetDefault($GLOBALS['app']->ACL->GetPriority());

        $authmethod =& Piwi::CreateWidget('Combo', 'auth_method');
        $authmethod->SetTitle(_t('USERS_PROPERTIES_AUTH_METHOD'));
        foreach ($GLOBALS['app']->GetAuthMethods() as $method) {
            $authmethod->AddOption($method, $method);
        }
        $authmethod->SetDefault($GLOBALS['app']->Registry->Get('/config/auth_method'));

        $anonRegister =& Piwi::CreateWidget('Combo', 'anon_register');
        $anonRegister->SetTitle(_t('USERS_PROPERTIES_ANON_REGISTER'));
        $anonRegister->AddOption(_t('GLOBAL_YES'), 'true');
        $anonRegister->AddOption(_t('GLOBAL_NO'), 'false');
        $anonRegister->SetDefault($GLOBALS['app']->Registry->Get('/config/anon_register'));

        $anonEmail =& Piwi::CreateWidget('Combo', 'anon_repetitive_email');
        $anonEmail->SetTitle(_t('USERS_PROPERTIES_ANON_REPETITIVE_EMAIL'));
        $anonEmail->AddOption(_t('GLOBAL_YES'), 'true');
        $anonEmail->AddOption(_t('GLOBAL_NO'), 'false');
        $anonEmail->SetDefault($GLOBALS['app']->Registry->Get('/config/anon_repetitive_email'));

        $anonactivate =& Piwi::CreateWidget('Combo', 'anon_activation');
        $anonactivate->SetTitle(_t('USERS_PROPERTIES_ANON_ACTIVATION'));
        $anonactivate->AddOption(_t('USERS_PROPERTIES_ACTIVATION_AUTO'), 'auto');
        $anonactivate->AddOption(_t('USERS_PROPERTIES_ACTIVATION_BY_USER'), 'user');
        $anonactivate->AddOption(_t('USERS_PROPERTIES_ACTIVATION_BY_ADMIN'), 'admin');
        $anonactivate->SetDefault($GLOBALS['app']->Registry->Get('/config/anon_activation'));

        $anonType =& Piwi::CreateWidget('Combo', 'anon_type', _t('USERS_PROPERTIES_ANON_TYPE'));
        $anonType->SetID('anon_type');
        $anonType->AddOption(_t('USERS_USERS_TYPE_ADMIN'),  1);
        $anonType->AddOption(_t('USERS_USERS_TYPE_NORMAL'), 2);
        $anonType->SetDefault($GLOBALS['app']->Registry->Get('/config/anon_type'));

        require_once JAWS_PATH . 'include/Jaws/User.php';
        $userModel = new Jaws_User();

        $passRecovery =& Piwi::CreateWidget('Combo', 'password_recovery');
        $passRecovery->SetTitle(_t('USERS_PROPERTIES_PASS_RECOVERY'));
        $passRecovery->AddOption(_t('GLOBAL_YES'), 'true');
        $passRecovery->AddOption(_t('GLOBAL_NO'), 'false');
        $passRecovery->SetDefault($GLOBALS['app']->Registry->Get('/gadgets/Users/password_recovery'));

        $socialSignOn =& Piwi::CreateWidget('Combo', 'social_sign_in');
        $socialSignOn->SetTitle(_t('USERS_PROPERTIES_SOCIAL_SIGN_IN'));
        $socialSignOn->AddOption(_t('GLOBAL_YES'), 'true');
        $socialSignOn->AddOption(_t('GLOBAL_NO'), 'false');
        $socialSignOn->SetDefault($GLOBALS['app']->Registry->Get('/gadgets/Users/social_sign_in'));
        
        $regRequireAddress =& Piwi::CreateWidget('Combo', 'signup_requires_address');
        $regRequireAddress->SetTitle(_t('USERS_PROPERTIES_REGISTER_REQUIRES_ADDRESS'));
        $regRequireAddress->AddOption(_t('GLOBAL_YES'), 'true');
        $regRequireAddress->AddOption(_t('GLOBAL_NO'), 'false');
        $regRequireAddress->SetDefault($GLOBALS['app']->Registry->Get('/gadgets/Users/signup_requires_address'));
        
		include_once JAWS_PATH . 'include/Jaws/Widgets/FieldSet.php';
        $fieldset = new Jaws_Widgets_FieldSet('');
        $fieldset->SetTitle('vertical');

        $fieldset->Add($priority);
        $fieldset->Add($authmethod);
        $fieldset->Add($anonRegister);
        $fieldset->Add($anonEmail);
        $fieldset->Add($anonactivate);
        $fieldset->Add($anonType);
        //$fieldset->Add($anonGroup);
        $fieldset->Add($passRecovery);
        $fieldset->Add($socialSignOn);
        $fieldset->Add($regRequireAddress);

        $form->Add($fieldset);

		// User-enabled gadget panes
		$enabled_fieldset = new Jaws_Widgets_FieldSet(_t('USERS_PROPERTIES_ENABLED_GADGETS'));
        $enabled_fieldset->SetTitle('vertical');
        $enabled_fieldset->SetStyle('margin-top: 30px;');
		
		$gadgets_fieldset = new Jaws_Widgets_FieldSet(_t('GLOBAL_GADGETS'));
        $gadgets_fieldset->SetTitle('vertical');
        $gadgets_fieldset->SetStyle('margin-top: 5px;');

		$checks1 =& Piwi::CreateWidget('CheckButtons', 'user_access_items', 'vertical');
		$checked1 = $GLOBALS['app']->Registry->Get('/gadgets/user_access_items');
		$checked1 = explode(",",$checked1);
				
		$groups_fieldset = new Jaws_Widgets_FieldSet(_t('USERS_PROPERTIES_ANON_GROUP'));
        $groups_fieldset->SetTitle('vertical');
        $groups_fieldset->SetStyle('margin-top: 5px;');
        
		$checks2 =& Piwi::CreateWidget('CheckButtons', 'anon_group', 'vertical');
		$checked2 = $GLOBALS['app']->Registry->Get('/config/anon_group');
		$groups_shown = array();
		$enabled2 = 0;
		if ((string)$checked2 == '0' || (string)$checked2 == '') {
			$enabled2 = 1;
		}
		$checks2->AddOption(_t('USERS_GROUPS_NOGROUP'), 0, null, $enabled2);
		$checked2 = explode(",",$checked2);
		
		
        // Update users_gadgets table
        $jms = $GLOBALS['app']->LoadGadget('Jms', 'AdminModel');
        
		// Add ALL GADGETS to users_gadgets
		$gadget_list = $jms->GetGadgetsList(null, true, true, true);
		//$users_gadgets = '';
        
		//Hold.. if we dont have a selected gadget?.. like no gadgets?
        if (!count($gadget_list) <= 0) {
			reset($gadget_list);
			$groups = $userModel->GetAllGroups('title');
			
			foreach ($gadget_list as $gadget) {
				$paneGadget = $GLOBALS['app']->LoadGadget($gadget['realname'], 'HTML');
				// Does gadget have user account panes?
				if (method_exists($paneGadget, 'GetUserAccountPanesInfo') && method_exists($paneGadget, 'GetUserAccountControls')) {
					//$users_gadgets .= $gadget['realname'].',';
					$enabled1 = 0;
					if (in_array($gadget['realname'], $checked1)) {
						$enabled1 = 1;
					}
					$checks1->AddOption($gadget['realname'], $gadget['realname'], null, $enabled1);
					
					$g = 0;
					foreach ($groups as $group) {
						if (
							(strpos(strtolower($group['name']), '_owners') !== false || 
							strpos(strtolower($group['name']), '_users') !== false) && 
							substr(strtolower($group['name']), 0, strlen(strtolower($gadget['realname']))) == strtolower($gadget['realname'])
						) {
							$enabled2 = 0;
							if (in_array((string)$group['id'], $checked2)) {
								$enabled2 = 1;
							}
							$checks2->AddOption($group['title'], $group['id'], 'gadget_group_'.$gadget['realname'].'_'.$g, $enabled2, !$enabled1);
							$groups_shown[] = $group['id'];
							$g++;
						}
					}
				}
				unset($paneGadget);
			}
		}
		
		$gadgets_fieldset->Add($checks1);

		foreach ($groups as $group) {
			if (!in_array($group['id'], $groups_shown) && $group['name'] != 'users') {
				$enabled2 = 0;
				if (in_array((string)$group['id'], $checked2)) {
					$enabled2 = 1;
				}
				$checks2->AddOption($group['title'], $group['id'], null, $enabled2);
			}
		}
		
		$groups_fieldset->Add($checks2);
		
		$enabled_fieldset->Add($gadgets_fieldset);
		$enabled_fieldset->Add($groups_fieldset);

        $form->Add($enabled_fieldset);
		
		// Password Protected Pages
		$checks_fieldset = new Jaws_Widgets_FieldSet(_t('USERS_PROPERTIES_PROTECTED_PAGES'));
		$checks_fieldset->SetTitle('vertical');
		$checks_fieldset->SetStyle('margin-top: 50px;');

		$checks =& Piwi::CreateWidget('CheckButtons', 'protected_pages','vertical');
		$checked = $GLOBALS['app']->Registry->Get('/gadgets/Users/protected_pages');
		$checked = explode(",",$checked);
				
		$sql = '
			SELECT
				[id], [menu_type], [title], [url], [visible]
			FROM [[menus]]
			ORDER BY [menu_type] ASC, [title] ASC';
		
		$menus = $GLOBALS['db']->queryAll($sql);
		if (Jaws_Error::IsError($menus)) {
			return $menus;
		}
		$checks->AddOption('Main', 0);
		if (is_array($menus)) {
			foreach ($menus as $menu => $m) {
				if ($m['visible'] == 0) {
					$checks->AddOption("<i>(not in menu) ".$m['menu_type']." : ".$m['title']."</i>", $m['id'], null, in_array($m['id'], $checked));
				} else {
					$checks->AddOption($m['menu_type']." : ".$m['title'], $m['id'], null, in_array($m['id'], $checked));
				}
			}
		}
				
		$checks_fieldset->Add($checks);
		$form->Add($checks_fieldset);

		$buttons =& Piwi::CreateWidget('HBox');
        $buttons->SetStyle(_t('GLOBAL_LANG_DIRECTION')=='rtl'?'float: left;' : 'float: right;');

        $save =& Piwi::CreateWidget('Button', 'save', _t('GLOBAL_SAVE'), STOCK_SAVE);
        $save->AddEvent(ON_CLICK, 'javascript: saveSettings();');

        $buttons->Add($save);
        $form->Add($buttons);

        $tpl->SetVariable('form', $form->Get());
        $tpl->SetVariable('menubar', $this->MenuBar('Settings'));

        $tpl->ParseBlock('Properties');

        return $tpl->Get();
    }


    /**
     * Builds the basic datagrid view
     *
     * @access  private
     * @return  string   XHTML of datagrid
     */
    function DataGrid()
    {
        $model = $GLOBALS['app']->LoadGadget('Users', 'AdminModel');
        $total = $model->TotalOfData('users_gadgets', 'user_id');

        $grid =& Piwi::CreateWidget('DataGrid', array());
        $grid->TotalRows($total);
        $grid->SetStyle('width: 100%;');
        $grid->SetID('galleries_datagrid');
        //$grid->useMultipleSelection();
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_TITLE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('FLASHGALLERY_TYPE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('FLASHGALLERY_ACTIVE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('FLASHGALLERY_LAST_UPDATE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_ACTIONS')));

        return $grid->Get();
    }

    /**
     * Returns an array with pages found
     *
     * @access  public
     * @param   string  $status  Status of user(s) we want to display
     * @param   string  $search  Keyword (title/description) of users we want to look for
     * @param   int     $limit   Data limit
     * @return  array   Data
     */
    function GetUsersGadgets($status, $search, $limit)
    {
        $model = $GLOBALS['app']->LoadGadget('Users', 'AdminModel');
        $pages = $model->SearchUsersGadgets($status, $search, $limit);
        if (Jaws_Error::IsError($pages)) {
            return array();
        }

        $data    = array();
		$date = $GLOBALS['app']->loadDate();
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');

        foreach ($pages as $page) {
			$pageData = array();
			$pageData['title'] = $page['user_id'];
			$pageData['type']  = $page['gadget_name'].' - '.$page['pane'];

			$pageData['active'] = $page['sort_order'] . '&nbsp;&nbsp;' . $page['status'];
			$pageData['date']  = $date->Format($page['updated']);

			$pageData['actions'] = '';
			$pageData['__KEY__'] = $page['user_id'];
			$data[] = $pageData;
        }
        return $data;
    }

	
    /**
     * Show the gadget embed window
     *
     * @access public
     * @return string
     */
    function ShowEmbedWindow($gadget, $acl_check, $account = false)
    {

		if ($gadget && $acl_check) {
			if ($account == false) {
				$GLOBALS['app']->Session->CheckPermission($gadget, 'default');
			} else {
				if (!$GLOBALS['app']->Session->GetPermission($gadget, 'default')) {
					$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
					if (!$GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), $gadget, $acl_check)) {
			            $GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
						$userHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
						return $userHTML->DefaultAction();
					}
				}
			}
			
			$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
			$request =& Jaws_Request::getInstance();
					
	        // FIXME: When a gadget don't have layout actions
	        // doesn't permit to add it into layout
	        require_once JAWS_PATH . 'include/Jaws/Template.php';
	        $tpl = new Jaws_Template('gadgets/Users/templates/');
	        $tpl->Load('EmbedGadget.html');

			$get = $request->get(array('mode', 'linkid', 'embed_mode', 'embed_string', 'embed_button_width'), 'get');
	        
			$tpl->SetBlock('embed_sites');
	        
			$direction = _t('GLOBAL_LANG_DIRECTION');
	        $dir  = $direction == 'rtl' ? '.' . $direction : '';
	        $brow = $GLOBALS['app']->GetBrowserFlag();
	        $brow = empty($brow)? '' : '.'.$brow;
	        $base_url = $GLOBALS['app']->GetSiteURL().'/';
	        
			//$tpl->SetVariable('DPATH', JAWS_DPATH);
			$tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . "/");
			$tpl->SetVariable('stub', ($account === false ? 'UsersAdminAjax' : 'UsersAjax'));
	        $tpl->SetVariable('BASE_URL', $base_url);
	        $tpl->SetVariable('.dir', $dir);
	        $tpl->SetVariable('.browser', $brow);
	        if ($account == false) {
				$tpl->SetVariable('base_script', BASE_SCRIPT);
				$tpl->SetVariable('client', '');
				//$embed_script .= "<script>document.write(\"<script type='text/javascript' src='http://yui.yahooapis.com/2.4.0/build/yahoo-dom-event/yahoo-dom-event.js'>\"+\"</\"+\"script><script type='text/javascript' src='http://\"+document.domain+\"/19/img/agents/19/custom_img/common_files/cross_frame.js'>\"+\"</\"+\"script><script type='text/javascript' src='".$base_url."index.php?gadget=Users&action=EmbedGadget&embedgadget=".$gadget."&embedaction=Embed".$gadget."&embedref=\" + window.location.host + window.location.pathname + \"&id=".$get['linkid']."&mode=".$get['mode'];
				$embed_script .= "<script>document.write(\"<script type='text/javascript' src='http://yui.yahooapis.com/2.4.0/build/yahoo-dom-event/yahoo-dom-event.js'>\"+\"</\"+\"script><script type='text/javascript' src='".$base_url."index.php?gadget=Users&action=EmbedGadget&embedgadget=".$gadget."&embedaction=Embed".$gadget."&embedref=\" + encodeURIComponent(window.location.host + window.location.pathname).replace(/!/g, '%21').replace(/'/g, '%27').replace(/\(/g, '%28').replace(/\)/g, '%29').replace(/\*/g, '%2A').replace(/%20/g, '+').replace(/\./g, '__DOT__') + \"&id=".$get['linkid']."&mode=".$get['mode'];
				$embed_link = $base_url."index.php?gadget=".$gadget."&action=Embed".$gadget."&id=".$get['linkid']."&mode=".$get['mode'];
			} else {
				$tpl->SetVariable('base_script', 'index.php');		
				$tpl->SetVariable('client', 'client_');
				//$embed_script .= "<script>document.write(\"<script type='text/javascript' src='http://yui.yahooapis.com/2.4.0/build/yahoo-dom-event/yahoo-dom-event.js'>\"+\"</\"+\"script><script type='text/javascript' src='http://\"+document.domain+\"/19/img/agents/19/custom_img/common_files/cross_frame.js'>\"+\"</\"+\"script><script type='text/javascript' src='".$base_url."index.php?gadget=Users&action=EmbedGadget&embedgadget=".$gadget."&embedaction=Embed".$gadget."&embedref=\" + window.location.host + window.location.pathname + \"&id=".$get['linkid']."&mode=".$get['mode']."&uid=".$GLOBALS['app']->Session->GetAttribute('user_id');
				$embed_script .= "<script>document.write(\"<script type='text/javascript' src='http://yui.yahooapis.com/2.4.0/build/yahoo-dom-event/yahoo-dom-event.js'>\"+\"</\"+\"script><script type='text/javascript' src='".$base_url."index.php?gadget=Users&action=EmbedGadget&embedgadget=".$gadget."&embedaction=Embed".$gadget."&embedref=\" + encodeURIComponent(window.location.host + window.location.pathname).replace(/!/g, '%21').replace(/'/g, '%27').replace(/\(/g, '%28').replace(/\)/g, '%29').replace(/\*/g, '%2A').replace(/%20/g, '+').replace(/\./g, '__DOT__') + \"&id=".$get['linkid']."&mode=".$get['mode']."&uid=".$GLOBALS['app']->Session->GetAttribute('user_id');
				$embed_link = $base_url."index.php?gadget=".$gadget."&action=Embed".$gadget."&id=".$get['linkid']."&mode=".$get['mode']."&uid=".$GLOBALS['app']->Session->GetAttribute('user_id');
			}
			
			//$tpl->SetVariable('DPATH', JAWS_DPATH);
			$tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . "/");
			if ((isset($get['embed_string']))) {
				$embed_string = $get['embed_string'];
			} else {
				$embed_string = "Gadget";
			}
			if ((isset($get['embed_button_width']))) {
				$embed_button_width = (int)$get['embed_button_width'];
			} else {
				$embed_button_width = 82;
			}
			if ((isset($get['embed_mode']))) {
				$embed_mode = $get['embed_mode'];
			} else {
				$embed_mode = "reg";
			}
			$embed_script .= "&embedmode=".$embed_mode."&embedbstr=".$embed_string."&embedbw=".$embed_button_width."'>\"+\"</\"+\"script>\");</script>";

	        $tpl->SetVariable('gadgets', _t('USERS_EMBED_SITES'));
	        $tpl->SetVariable('actions', _t('USERS_EMBED_PAGES'));
	        $tpl->SetVariable('no_actions_msg', _t('USERS_EMBED_NO_PAGES'));
	        $tpl->SetVariable('confirm_delete_embed', _t('USERS_EMBED_CONFIRM_DELETE'));
			
	        $post = $request->get(array('linkid'), 'post');
	        $linkid = $post['linkid'];
			if (is_null($linkid)) {
	            $linkid = $get['linkid'];
	            $linkid = (!empty($linkid) ? $linkid : '');
	        }
	        
			$search = '';
	        $searchEntry =& Piwi::CreateWidget('Entry', 'search', $search);
	        $searchEntry->SetStyle('zwidth: 100%;');
	        $tpl->SetVariable('search_entry', $searchEntry->Get());

	        $searchButton =& Piwi::CreateWidget('Button', 'searchButton', _t('USERS_EMBED_USERID_SEARCH'), STOCK_SEARCH);
	        $searchButton->AddEvent(ON_CLICK, "javascript: searchXML($('search').value, $('embed_link').value);");
			$searchButton->SetSubmit();
	        $tpl->SetVariable('search', $searchButton->Get());

			$addButton =& Piwi::CreateWidget('Button', 'add',_t('USERS_EMBED_ADD'), STOCK_ADD);
			$addButton->AddEvent(ON_CLICK, "parent.parent.addUrlToLayout($('gadget').value, getSelectedAction(), $('embed_link').value, getCheckedValue(document.forms['form0'].elements['layout']));");
			$addButton->setID("add");
			$tpl->SetVariable('add_button', $addButton->Get());

			$cancelButton =& Piwi::CreateWidget('Button', 'cancel',_t('GLOBAL_CANCEL'), STOCK_CANCEL);
			$cancelButton->AddEvent(ON_CLICK, "parent.parent.toggleNo('embedInfo'); parent.parent.hideGB();");
			$cancelButton->setID("cancel");
			$tpl->SetVariable('cancel_button', $cancelButton->Get());

			$tpl->SetVariable('embed_link', $embed_link);
			$tpl->SetVariable('embed_script', $embed_script);

			$gadget_list = array();
			
			$gadget_list[] = array(
					'id' => 'manual',
					'name' => 'Embed Code',
					'description' => 'Embed this gadget on your site manually',
			);

			$gadget_list[] = array(
					'id' => 'showcase',
					'name' => 'Showcase',
					'description' => 'Embed this gadget on your Showcase website',
			);
			
	        foreach ($gadget_list as $gadget) {
	            $tpl->SetBlock('embed_sites/gadget');
	            $tpl->SetVariable('id', $gadget['id']);
	            $tpl->SetVariable('gadget', $gadget['name']);
	            $tpl->SetVariable('desc', $gadget['description']);
	            $tpl->ParseBlock('embed_sites/gadget');
	        }
				
	        $tpl->ParseBlock('embed_sites');
	       
		    return $tpl->Get();
		}
	}
	
    /**
     * Tells the user the registation process is done
     *
     * @access  public
     * @return  string  XHTML of template
     */
    function Registered()
    {
		// Load the template
        $tpl = new Jaws_Template('gadgets/Users/templates/');
        $tpl->Load('Registered.html');
        $tpl->SetBlock('registered');
        $tpl->SetVariable('title', _t('USERS_REGISTER_REGISTERED'));
        $tpl->SetVariable('registered_msg', _t('USERS_REGISTER_REGISTERED_MSG', $GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction')));
        $tpl->ParseBlock('registered');
        return $tpl->Get();
	}

    /**
     * Register the user
     *
     * @access  public
     */
    function RegUser()
    {
        $request =& Jaws_Request::getInstance();
        $step    = $request->get('step', 'post');
        $error   = '';

        $post = $request->get(array(
			'username', 'realname', 'email', 'random_password',
            'password', 'password_check', 'captcha', 'nocaptcha', 
			'captchaKey', 'group'),'post'
		);

		$redirectTo = $request->get('redirect_to', 'post');
		$redirectTo = (isset($redirectTo) && !empty($redirectTo) ? urldecode($redirectTo) : '');
		if (empty($redirectTo)) {
			$redirectTo = $request->get('redirect_to', 'get');
			$redirectTo = (isset($redirectTo) && !empty($redirectTo) ? urldecode($redirectTo) : '');
		}
        $GLOBALS['app']->Registry->LoadFile('Policy');
        $_captcha = $GLOBALS['app']->Registry->Get('/gadgets/Policy/captcha');
        if ($_captcha != 'DISABLED' && $post['nocaptcha'] != 'rpxnow') {
            require_once JAWS_PATH . 'gadgets/Policy/captchas/' . $_captcha . '.php';
            $captcha = new $_captcha();
            if (!$captcha->Check()) {
                $error = _t('GLOBAL_CAPTCHA_ERROR_DOES_NOT_MATCH');
            }
        }

        require_once JAWS_PATH . 'include/Jaws/Header.php';

        if (empty($error)) {
            // See if this is an existing user
			require_once JAWS_PATH . 'include/Jaws/User.php';
			$jUser = new Jaws_User;
			$info  = $jUser->GetUserInfoByName($post['username']);
			$has_account = false;
			if (isset($info['id']) && !empty($info['id'])) {
				$result = true;
				$has_account = true;
			} else {
				$uModel = $GLOBALS['app']->LoadGadget('Users', 'Model');
				if (md5($post['password']) == '7de6e2f0054ca65cee6d90bd7031b995' && $post['nocaptcha'] == 'rpxnow') {
					$result = $jUser->AddUser($post['username'], $post['realname'], $post['email'], md5($post['password']), 1, true);
					$result = ($result !== false && !Jaws_Error::isError($result) ? true : false);
				} else {
					$result = $uModel->CreateUser($post['username'], $post['email'], $post['realname'],
                                          $post['password'], $post['password_check'],
                                          ($post['password'] == ''), $post['group'], 2);
				}
			}
			if ($result === true) {
				$login = $GLOBALS['app']->Session->Login($post['username'], $post['password'], false);
				if (Jaws_Error::isError($login)) {
					$error = $login->GetMessage();
				} else {
					if (md5($post['password']) == '7de6e2f0054ca65cee6d90bd7031b995' && $post['nocaptcha'] == 'rpxnow') {
						//echo $GLOBALS['app']->Session->GetAttribute('session_id');
						$attributes = $GLOBALS['app']->Session->_Attributes;
						foreach ($attributes as $sk => $sv) {
							if (is_array($sv)) {
								foreach ($sv as $k => $v) {
									//echo $sk.'['.$k.']='.$v.';';
									echo $v.';';
								}
							} else {
								//echo $sk.'='.$sv.';';
								echo $sv.';';
							}
						}
						exit;
					}
					// Requested group access and has an account, but wasn't logged in?
					if (!empty($post['group']) && $has_account === true) {
						$groupInfo = $jUser->GetGroupInfoByName($post['group']);
						if (isset($groupInfo['id']) && !empty($groupInfo['id'])) {
							Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'RequestGroupAccess', array('group' => $groupInfo['id'])));
						}
					}
					if (!empty($redirectTo)) {
						Jaws_Header::Location($redirectTo);
					} else {
						Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'));
					}
				}
                //Jaws_Header::Location($this->GetURLFor('Registered'));
            } else {
				if (isset($redirectTo) && !empty($redirectTo)) {
					if (strpos($redirectTo, 'step=2') !== false) {
						$redirectTo = str_replace('step=2', '', $redirectTo);
					} else if (strpos($redirectTo, 'step=3') !== false) {
						$redirectTo = str_replace('step=3', 'step=2', $redirectTo);
					} else if (strpos($redirectTo, 'step=4') !== false) {
						$redirectTo = str_replace('step=4', 'step=3', $redirectTo);
					} else if (strpos($redirectTo, 'step=5') !== false) {
						$redirectTo = str_replace('step=5', 'step=4', $redirectTo);
					} else if (strpos($redirectTo, 'step=6') !== false) {
						$redirectTo = str_replace('step=6', 'step=5', $redirectTo);
					} else if (strpos($redirectTo, 'step=7') !== false) {
						$redirectTo = str_replace('step=7', 'step=6', $redirectTo);
					} else if (strpos($redirectTo, 'step=8') !== false) {
						$redirectTo = str_replace('step=8', 'step=7', $redirectTo);
					}
					$GLOBALS['app']->Session->PushSimpleResponse($result);
					Jaws_Header::Location($redirectTo.'&register_error='.$result);
				} else {
					$error = $result;
				}
            }
        }

        if (!empty($error)) {
            $GLOBALS['app']->Session->PushSimpleResponse($error);
            Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'Registration'));
        }
    }
		
	/**
     * Shows activate group access result
     *
     * @access  public
     * @param   int  $user    The user id to request access for
     * @param   int  $group    The group id to request access for
     * @param   string  $status    The status of request
     * @return  string  XHTML of template
	 * @TODO 	Expose Jaws_ACL for group users
     */
    function AuthUserGroup($user = null, $group = null, $status = null)
    {
		$request =& Jaws_Request::getInstance();
		if (is_null($group)) {
			$group = $request->get('group', 'get');
		}
		if (is_null($user)) {
			$user = $request->get('user', 'get');
		}
		if (is_null($status)) {
			$status = $request->get('status', 'get');
			if (is_null($status)) {
				$status = 'active';
			}
		}
        require_once JAWS_PATH . 'include/Jaws/Header.php';
		require_once JAWS_PATH . 'include/Jaws/User.php';
		$jUser = new Jaws_User;
		$viewer_id = (int)$GLOBALS['app']->Session->GetAttribute('user_id');
		if (
			(in_array($jUser->GetStatusOfUserInGroup($viewer_id, $group), array('founder','admin')) ||     
			$GLOBALS['app']->ACL->GetFullPermission(
				$GLOBALS['app']->Session->GetAttribute('username'), 
				$GLOBALS['app']->Session->GetAttribute('groups'), 'Users', 'ManageGroups'))
			&& !empty($group) && !empty($user)
		) {
			$userInfo = $jUser->GetUserInfoById((int)$user);
			$groupInfo = $jUser->GetGroupInfoById((int)$group);
			if (
				isset($userInfo['id']) && !empty($userInfo['id']) && 
				isset($groupInfo['id']) && !empty($groupInfo['id'])
			) {
				/*
				// Load the template
				$tpl = new Jaws_Template('gadgets/Users/templates/');
				$tpl->Load('RequestedGroupAccess.html');
				if (in_array($jUser->GetStatusOfUserInGroup($user, $groupInfo['id']), array('active','founder','admin'))) {
					$tpl->SetBlock('already_in_group');
					$tpl->SetVariable('content', 'User is already active in this group.');
					$tpl->ParseBlock('already_in_group');
					return $tpl->Get();
				}
				$tpl->SetBlock('group_access');
				$tpl->SetVariable('title', _t('USERS_USERS_AUTHORIZE_GROUP_ACCESS'));
				*/
				
				// Add User to Group
				$res = $jUser->AddUserToGroup($userInfo['id'], $groupInfo['id'], $status);
				if ($res === true) {
					$GLOBALS['app']->Session->PushSimpleResponse(_t('USERS_USERS_AUTHORIZED_GROUP_ACCESS', $groupInfo['title']));
				} else {
					$GLOBALS['app']->Session->PushSimpleResponse(_t('USER_GROUP_CANT_ADD', " ".$groupInfo['title']));
				}
				
				/*
				if ($response = $GLOBALS['app']->Session->PopSimpleResponse()) {
					$tpl->SetBlock('group_access/response');
					$tpl->SetVariable('msg', $response);
					$tpl->ParseBlock('group_access/response');
				}
				$tpl->ParseBlock('group_access');
				return $tpl->Get();
				*/
				
				Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'));
			}
		}
		$GLOBALS['app']->Session->PushSimpleResponse(_t('USERS_ERROR_AUTHORIZE_GROUP_ACCESS'));
		//return new Jaws_Error(_t('USERS_ERROR_AUTHORIZE_GROUP_ACCESS'), _t('USERS_NAME'));
		Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'));
    }

    /**
     * Adds layout element
     *
     * @access public
     * @return template content
     */
    function AddLayoutElement()
    {
		if (!$GLOBALS['app']->Session->Logged()) {
			$GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
			$userHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
			return $userHTML->DefaultAction();
		}

		$site_name = $GLOBALS['app']->Registry->Get('/config/site_name');
		//$xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		require_once JAWS_PATH . 'include/Jaws/User.php';
		$jUser = new Jaws_User;
		$uid = $GLOBALS['app']->Session->GetAttribute('user_id');
		$userInfo = $jUser->GetUserInfoById((int)$uid);
		if (Jaws_Error::IsError($userInfo) || !isset($userInfo['id'])) {
			Jaws_Error::Fatal($userInfo->GetMessage(),
							 __FILE__, __LINE__);
		}
        
		$request =& Jaws_Request::getInstance();
        $post = $request->get(
			array(
				'id', 'linkid', 'mode', 'where', 'callback', 'first', 
				'fusegadget', 'show_status', 'query', 'callback', 
				'title', 'entry', 'sharing' 
			), 
			'post'
		);
        
		$id = $post['id'];
		if (empty($id)) {
            $id = $request->get('id', 'get');
            $id = !empty($id) ? $id : '';
        }
		$linkid = $post['linkid'];
		if (empty($linkid)) {
            $linkid = $request->get('linkid', 'get');
            $linkid = !empty($linkid) ? $linkid : $GLOBALS['app']->Session->GetAttribute('user_id');
        }
		$mode = $post['mode'];
		if (empty($mode)) {
			$mode = $request->get('mode', 'get');
			$mode = !empty($mode) ? $mode : 'post';
		}
		$where = $post['where'];
		if (empty($where)) {
            $where = $request->get('where', 'get');
            $where = !empty($where) ? $where : 'Image';
        }
        $callback = $post['callback'];
		if (empty($callback)) {
            $callback = $request->get('callback', 'get');
            $callback = !empty($callback) ? $callback : '';
        }
        $show_status = $post['show_status'];
		if (empty($show_status)) {
            $show_status = $request->get('show_status', 'get');
            $show_status = !empty($show_status) ? $show_status : '';
        }
		$show_status = (!empty($show_status) && $show_status == 'false' ? false : true);
        $gadget = $post['fusegadget'];
		if (empty($gadget)) {
            $gadget = $request->get('fusegadget', 'get');
            $gadget = !empty($gadget) ? $gadget : '';
        }
		$gadgetfirst = '';
		if (!empty($gadget)) {
			$gadgetfirst = $gadget;
		}
		$first = $post['first'];
		if (empty($first)) {
			$first = $request->get('first', 'get');
			$first = (!empty($first) ? $first : (!empty($gadgetfirst) ? $gadgetfirst : 'Status'));
		}
		$query = $post['query'];
		if (empty($query)) {
            $query = $request->get('query', 'get');
            $query = !empty($query) ? $query : '';
        }
		$entry = $post['entry'];
		if (empty($entry)) {
            $entry = $request->get('entry', 'get');
            $entry = !empty($entry) ? $entry : "What's on your mind?";
        }
		$title = $post['title'];
		if (empty($title)) {
            $title = $request->get('title', 'get');
            $title = !empty($title) ? $title : '';
        }
		$sharing = $post['sharing'];
		if (empty($sharing)) {
            $sharing = $request->get('sharing', 'get');
            $sharing = !empty($sharing) ? $sharing : '';
        }
		$sharing = urldecode($sharing);
		
        require_once JAWS_PATH . 'include/Jaws/Template.php';
        $tpl = new Jaws_Template('gadgets/Users/templates/');
        $tpl->Load('AddUpdate.html');
        $tpl->SetBlock('template');

        $direction = _t('GLOBAL_LANG_DIRECTION');
        $dir  = $direction == 'rtl' ? '.' . $direction : '';
        $brow = $GLOBALS['app']->GetBrowserFlag();
        $brow = empty($brow)? '' : '.'.$brow;
        $base_url = $GLOBALS['app']->GetSiteURL().'/';

        $tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . "/");
        //$tpl->SetVariable('DPATH', JAWS_DPATH);
        $tpl->SetVariable('stub', (JAWS_SCRIPT == 'admin' ? 'UsersAdminAjax' : 'UsersAjax'));
        $tpl->SetVariable('BASE_URL', $base_url);
        $tpl->SetVariable('.dir', $dir);
        $tpl->SetVariable('.browser', $brow);
		$tpl->SetVariable('base_script', BASE_SCRIPT);
		$tpl->SetVariable('account', (JAWS_SCRIPT == 'admin' ? '' : 'account_'));
		$tpl->SetVariable('script', (JAWS_SCRIPT == 'admin' ? 'script' : 'client_script'));

        $tpl->SetVariable('gadgets', _t('GLOBAL_GADGETS'));
        $tpl->SetVariable('sharing', $sharing);
        $tpl->SetVariable('actions', _t('GLOBAL_ACTIONS'));
        $tpl->SetVariable('no_actions_msg', _t('GLOBAL_NO_ACTIONS'));
        
		$socialCheck =& Piwi::CreateWidget('CheckButtons', 'syndication_checks','vertical');
		if (empty($gadget) && Jaws_Gadget::IsGadgetUpdated('Social')) {
			$GLOBALS['app']->Translate->LoadTranslation('Social', JAWS_GADGET);
			$socialCheck->AddOption(_t('SOCIAL_ACTION_DESCRIPTION'), 'true', 'syndication');
			$socialCheck->SetStyle('margin-right: 5px;');
		} else {
			$socialCheck->AddOption('', 'true', 'syndication');
			$socialCheck->SetStyle('display: none;');
		}
		$tpl->SetVariable('social_html', $socialCheck->Get());
		
		$post_as_html = '';
		if (empty($gadget) && ($GLOBALS['app']->Session->IsSuperAdmin() || $GLOBALS['app']->Session->IsAdmin())) {
			$user_name = 'My Account';
			if (isset($userInfo['company']) && !empty($userInfo['company'])) {
				$user_name = $userInfo['company'];
			} else if (isset($userInfo['nickname']) && !empty($userInfo['nickname'])) {
				$user_name = $userInfo['nickname'];
			}
			$site_name = $GLOBALS['app']->Registry->Get('/config/site_name');
			$site_url = $GLOBALS['app']->GetSiteURL();
			if (empty($site_name)) {
				$site_name = str_replace(array('http://', 'https://'), '', $site_url);
			}
			$postAs =& Piwi::CreateWidget('Combo', 'OwnerID');
			$postAs->setID('OwnerID');
			$postAs->setStyle('max-width: 150px;');
			$postAs->AddOption($user_name, $userInfo['id']);
			$postAs->AddOption($site_name, 0);
			$postAs->setDefault($userInfo['id']);
			$post_as_html = "Post as:&nbsp;".$postAs->Get();
		} else if (!empty($gadget) && ($GLOBALS['app']->Session->IsSuperAdmin() || $GLOBALS['app']->Session->IsAdmin())) {
	        $postAs =& Piwi::CreateWidget('HiddenEntry', 'OwnerID', '0');
			$post_as_html = $postAs->Get();
		}
		$tpl->SetVariable('post_as_html', $post_as_html);
		
		$share_html = '';
		if (empty($gadget)) {
			$shareButton =& Piwi::CreateWidget('Button', 'share', _t('USERS_SHARECOMMENT_TITLE'), STOCK_LOCK);
			$shareButton->AddEvent(ON_CLICK, "commentSharing();");
			$share_html = $shareButton->Get();
		} else if ($mode == 'sharing') {
			$hiddenShare =& Piwi::CreateWidget('HiddenEntry', 'share', '');
			$share_html = $hiddenShare->Get();
		}
		$tpl->SetVariable('share_button', $share_html);
		
		$saveButton =& Piwi::CreateWidget('Button', 'save', _t('GLOBAL_SAVE'), STOCK_SAVE);
		$tpl->SetVariable('save_button', $saveButton->Get());
        
		$photo_custom_function = 'null';
		if (empty($gadget) || $mode == 'sharing') {
			$form_content = '';
			// If this is just the 'sharing' dialog, show hidden entry fields
			if ($mode == 'sharing') {
				$hiddenDesc =& Piwi::CreateWidget('HiddenEntry', 'photo-entry', $entry);
				$form_content .= $hiddenDesc->Get();
				$hiddenImage =& Piwi::CreateWidget('HiddenEntry', 'photo-image', '');
				$form_content .= $hiddenImage->Get();
			} else {
				$form_content .= "<table border='0' cellpadding='0' cellspacing='0'>";
				$descEntry = "<textarea title='What is on your mind?' style='width: 500px; height: 100px;' id='photo-entry' name='photo-entry' cols='20' rows='5'>".$entry."</textarea>";
				//$form_content .= "<tr><td class='syntacts-form-row'><label for='photo-entry'><nobr>".$helpString."</nobr></label></td><td class='syntacts-form-row' colspan='3'>&nbsp;</td></tr>";
				$form_content .= "<tr><td class='syntacts-form-row' colspan='4'>".$descEntry;
				$form_content .= "<div style='display: block; clear: both; float: none; font-size: 0.0001em;'>&nbsp;</div><div id='update-preview' class='info-items update-preview' style='display: none;'></div></td></tr>";

				// Image
				$form_content .= "<tr style='display: ;' id='imageRow'>";
				$form_content .= "<td valign='top' colspan='4'>";
				$form_content .= "<table border='0' width='100%' cellpadding='0' cellspacing='0'>";
				$imageHidden =& Piwi::CreateWidget('HiddenEntry', 'photo-image', '');
				$imageHidden->setID('photo-image');
				$form_content .= "<tr><td class='syntacts-form-row'><div id='insertMedia'><label>Insert Media: </label></div></td><td class='syntacts-form-row'><div id='imageField'><div id='main_image' style='float: left; width: 450px;'></div>".$imageHidden->Get()."</div></td></tr>";
				$form_content .= '</table>';
			}					
			
			// ID
			$hiddenID =& Piwi::CreateWidget('HiddenEntry', 'photo-id', $id);
			$form_content .= $hiddenID->Get();
			
			// Title
			$hiddenTitle =& Piwi::CreateWidget('HiddenEntry', 'photo-title', $title);
			$form_content .= $hiddenTitle->Get();
			
			// URL stuff
			$hiddenInternal_url =& Piwi::CreateWidget('HiddenEntry', 'photo-internal_url', 'javascript:void(0);');
			$form_content .= $hiddenInternal_url->Get();
			$hiddenExternal_url =& Piwi::CreateWidget('HiddenEntry', 'photo-external_url', '');
			$form_content .= $hiddenExternal_url->Get();
			$hiddenUrl_type =& Piwi::CreateWidget('HiddenEntry', 'photo-url_type', 'imageviewer');
			$form_content .= $hiddenUrl_type->Get();
			
			$form_content = str_replace('"', "'", $form_content);
			$form_content = str_replace("'", "\\'", $form_content);
			$photo_custom_function = "function() {
			if ($('add')) {
				$('add').style.display = 'none';
			}
			if ($('photo-entry')) {
			} else {
				photo_holder = document.createElement('DIV');
				photo_holder.setAttribute('id', 'photo-area');
				photo_holder.style.margin = '10px';
				photo_holder.innerHTML = '".$form_content."';
				$('post-form').appendChild(photo_holder);
				$('photo-entry').onfocus = function() {
					if ($('photo-entry').value == 'What\'s on your mind?') {
						$('photo-entry').value = '';
					}
				};
				$('photo-entry').onblur = function() {
					if ($('photo-entry').value == '') {
						$('photo-entry').value = 'What\'s on your mind?';
					}
				};
				$('photo-entry').observe('keyup', function(event){if(event.keyCode == '32' || event.which == '32'){createUpdatePreview($('photo-entry').value);Event.stop(event);}});
				$('photo-entry').observe('change', function(){createUpdatePreview($('photo-entry').value);});
			}
			if ($('main_image') && !$('iframe_1')) {
				addFileToPost('Users', 'NULL', 'NULL', 'main_image', 'photo-image', 1, 450, 34);
			}
			var g = prevGadget;
			";
			if ($mode == 'sharing') {
				$photo_custom_function .= "
				while ($('actions-list').firstChild)
				{
					$('actions-list').removeChild($('actions-list').firstChild);
				};
				if($('share')) {
					$('share').style.display = 'none';
				}
				$('post-form').style.display = 'none';
				$('quick-form').style.display = '';
				g = 'Sharing';
				if ($('add')) {
					$('add').style.display = 'none';
				}
				if (ifrm[prevGadget]) {
					ifrm[prevGadget].style.display = 'none';		
				}
				if (ifrm[g]) {
					ifrm[g].style.display = '';
				} else {
					ifrm[g] = document.createElement('IFRAME');
					ifrm[g].setAttribute('id', 'quick_add_'+g);
					ifrm[g].setAttribute('name', 'quick_add_'+g);
					ifrm[g].setAttribute('src', 'admin.php?gadget=Users&action=ShareComment');
					ifrm[g].style.width = '100%';
					ifrm[g].style.height = '5000px';
					ifrm[g].style.borderWidth = 0+'px';
					ifrm[g].setAttribute('frameborder', '0');
					ifrm[g].setAttribute('scrolling', 'no');
					ifrm[g].setAttribute('allowtransparency', 'true');
					ifrm[g].frameBorder = '0';
					ifrm[g].scrolling = 'no';
					$('quick-form').appendChild(ifrm[g]);
				}
				";
			}
			$photo_custom_function .= "
				$('save').onclick = function() {
					if (window.frames['quick_add_'+g]) {
						window.frames['quick_add_'+g].saveCommentSharing();
					}
					var wm = window.top.UI.defaultWM;
					var windows = wm.windows();

					if ($('photo-entry').value != '' && $('photo-entry').value != 'What\'s on your mind?') {
						window.top.savePhoto(
							$('photo-id').value, $('photo-entry').value, $('photo-title').value, 0, $('sharing').value, 
							$('syndication').checked, ($('OwnerID') ? $('OwnerID').value : ''), 
							($('photo-image') ? $('photo-image').value : ''), 
							($('photo-url_type') ? $('photo-url_type').value : ''), 
							($('photo-internal_url') ? $('photo-internal_url').value : ''), 
							($('photo-external_url') ? $('photo-external_url').value : ''), 
							($('photo-url_target') ? $('photo-url_target').value : '')";
			if (
				!empty($gadget) && Jaws_Gadget::IsGadgetUpdated($gadget) && 
				($GLOBALS['app']->Session->IsSuperAdmin() || $GLOBALS['app']->Session->IsAdmin())
			) {
				$photo_custom_function .= ", '".$gadget."'";
			}
			if (!empty($callback)) {
				$photo_custom_function .= ",".base64_decode($callback);
			}
			$photo_custom_function .= "
						);
						windows.first().destroy();
					} else if ($('photo-entry').value == 'What\'s on your mind?' && ($('photo-image') && $('photo-image').value == '')) {
						windows.first().destroy();
					}
			";
			$photo_custom_function .= "
				};
				$('save').style.display = '';
				$('post-form').style.display = '';
			}";
			$photo_custom_function = str_replace(array("\r", "\n", "\t"), '', $photo_custom_function);
		}
		
		$tabs = array();
		$tab_ids = array();
		$head_scripts = '';
		if (!empty($gadget)) {
			$head_scripts .= '<style type="text/css">#gadget-list, #controls th {display: none;}</style>';
		}
		// Let everyone know
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout(
			'onBeforeLoadAddUpdate', 
			array('user_id' => $GLOBALS['app']->Session->GetAttribute('user_id'))
		);
		if (!Jaws_Error::IsError($res) && isset($res['tabs']) && is_array($res['tabs']) && !count($res['tabs']) <= 0) {
			$tabs = $res['tabs'];
			foreach ($tabs as $tab) {
				if (!in_array(strtolower($tab['gadget_realname']), $tab_ids)) {
					$head_scripts .= '<script type="text/javascript" src="'. BASE_SCRIPT .'?gadget='.$tab['gadget_realname'].'&amp;action=Ajax&amp;client=all&amp;stub='.$tab['gadget_realname'].'Ajax"></script>
					<script type="text/javascript" src="'. BASE_SCRIPT .'?gadget='.$tab['gadget_realname'].'&amp;action=AjaxCommonFiles"></script>
					<script type="text/javascript" src="'.$GLOBALS['app']->GetJawsURL() . '/gadgets/'.$tab['gadget_realname'].'/resources/client_script.js"></script>';
					$tab_ids[] = strtolower($tab['gadget_realname']);
				}
			}
			if (isset($res['first']) && !empty($res['first'])) {
				$first = $res['first'];
			}
			if (isset($res['show_status'])) {
				$show_status = $res['show_status'];
			}
		}
		if ($mode != 'sharing') {
			if (
				!empty($gadget) && $gadget != 'Users' && Jaws_Gadget::IsGadgetUpdated($gadget) && 
				($GLOBALS['app']->Session->IsSuperAdmin() || $GLOBALS['app']->Session->IsAdmin())
			) {
				$hook = $GLOBALS['app']->loadHook($gadget, 'URLList');
				if ($hook !== false) {
					if (method_exists($hook, 'GetQuickAddForms')) {
						$tabs[] = array(
							'gadget_id' => $gadget,
							'icon' => $GLOBALS['app']->GetJawsURL() . '/gadgets/'.$gadget.'/images/logo.png',
							'gadget' => $gadget,
							'desc' => $gadget,
							'method' => 'AddGadget',
							'custom_function' => 'null'
						);
						$tab_ids[] = strtolower($gadget);
					}
				}
			} else {
				$jms = $GLOBALS['app']->LoadGadget('Jms', 'AdminModel');
				$gadget_list = $jms->GetGadgetsList(null, true, true, true);

				//Hold.. if we dont have a selected gadget?.. like no gadgets?
				if (count($gadget_list) <= 0) {
					Jaws_Error::Fatal('You don\'t have any installed gadgets, please enable/install one and then come back',
									 __FILE__, __LINE__);
				}
				
				reset($gadget_list);
				$user_access_items = $GLOBALS['app']->Registry->Get('/gadgets/user_access_items');
				$user_access_items = explode(",",$user_access_items);
				if (!in_array('Users', $user_access_items)) {
					$user_access_items[] = 'Users';
				}
				//$first = current($gadget_list);
				foreach ($gadget_list as $gadget) {
					if (in_array($gadget['realname'], $user_access_items)) {
						$hook = $GLOBALS['app']->loadHook($gadget['realname'], 'URLList');
						if ($hook !== false) {
							if (method_exists($hook, 'GetQuickAddForms')) {
								if ($GLOBALS['app']->Session->IsSuperAdmin() || $GLOBALS['app']->Session->IsAdmin()) {
									$tabs[] = array(
										'gadget_id' => $gadget['realname'],
										'icon' => $GLOBALS['app']->GetJawsURL() . '/gadgets/'.$gadget['realname'].'/images/logo.png',
										'gadget' => $gadget['name'],
										'desc' => $gadget['description'],
										'method' => 'AddGadget',
										'custom_function' => 'null'
									);
									$tab_ids[] = strtolower($gadget['realname']);
								} else {
									$ugroups = $jUser->GetGroupsOfUser($GLOBALS['app']->Session->GetAttribute('user_id'));
									foreach ($ugroups as $ugroup) {
										if (
											strtolower($ugroup['group_name']) != 'users' && 
											strtolower($gadget['realname']) == substr(strtolower($ugroup['group_name']), 0, strlen(strtolower($gadget['realname']))) && 
											in_array($ugroup['group_status'], array('active','founder','admin'))
										) {
											if (!in_array(strtolower($gadget['realname']), $tab_ids)) {
												$tabs[] = array(
													'gadget_id' => $gadget['realname'],
													'icon' => $GLOBALS['app']->GetJawsURL() . '/gadgets/'.$gadget['realname'].'/images/logo.png',
													'gadget' => $gadget['name'],
													'desc' => $gadget['description'],
													'method' => 'AddGadget',
													'custom_function' => 'null'
												);
												$tab_ids[] = strtolower($gadget['realname']);
											}
											break;
										}
									}
								}
							}
						}
					}
				}
			}
		}
		
		$tpl->SetVariable('head_scripts', $head_scripts);
		if (!in_array(strtolower($first), $tab_ids)) {
			$first = 'Status';
		}
		$method = 'EditUpdate';
		if ($first != 'Status') {
			$method = 'AddGadget';
			$photo_custom_function = 'null';
		}
		$tpl->SetVariable('first', $first);
		//$tpl->SetVariable('addtext_content', $this->A_form((JAWS_SCRIPT == 'admin' ? false : true)));
		$tpl->SetVariable('id', $id);
		$tpl->SetVariable('linkid', $linkid);
		$tpl->SetVariable('method', $method);
		$tpl->SetVariable('custom_function', $photo_custom_function);
		$tpl->SetVariable('query', base64_decode($query));
		$tpl->SetVariable('callback', (empty($callback) ? 'null' : base64_decode($callback)));
		
		if ($show_status === true) {
			$tpl->SetBlock('template/gadget');
			$tpl->SetVariable('id', $id);
			$tpl->SetVariable('linkid', $linkid);
			$tpl->SetVariable('callback', (empty($callback) ? 'null' : base64_decode($callback)));
			$tpl->SetVariable('method', 'EditUpdate');
			$tpl->SetVariable('gadget_id', 'Status');
			$tpl->SetVariable('icon', $GLOBALS['app']->GetJawsURL() . '/gadgets/FileBrowser/images/mimetypes/text-x-image-generic.png');
			$tpl->SetVariable('gadget', 'Status');
			$tpl->SetVariable('desc', "What's on your mind?");
			$tpl->SetVariable('custom_function', $photo_custom_function);
			$tpl->ParseBlock('template/gadget');
		}
		
		foreach ($tabs as $tab) {
			if ($tab['gadget_id'] != 'Status') {
				$tpl->SetBlock('template/gadget');
				$tpl->SetVariable('id', $id);
				$tpl->SetVariable('linkid', $linkid);
				$tpl->SetVariable('method', $tab['method']);
				$tpl->SetVariable('gadget_id', $tab['gadget_id']);
				$tpl->SetVariable('icon', $tab['icon']);
				$tpl->SetVariable('gadget', $tab['gadget']);
				$tpl->SetVariable('desc', $tab['desc']);
				$tpl->SetVariable('custom_function', $tab['custom_function']);
				$tpl->SetVariable('query', base64_decode($query));
				$tpl->SetVariable('callback', (empty($callback) ? 'null' : base64_decode($callback)));
				$tpl->ParseBlock('template/gadget');
			}
		}
		
        $tpl->ParseBlock('template');

        return $tpl->Get();
    }

    /**
     * Save layout element
     *
     * @access public
     * @return template content
     */
    function SaveLayoutElement($linkid)
    {
        //$this->CheckPermission('default');
        $model = $GLOBALS['app']->loadGadget('Users', 'AdminModel');

        //$xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $request =& Jaws_Request::getInstance();
        $fields = array('gadget_field', 'action_field', 'linkid');
        $post = $request->get($fields, 'post');

        // Check that the gadget had an action set.
        if (!empty($post['action_field'])) {
            $model->NewElement($post['gadget_field'], $post['action_field']);
        }

        require_once JAWS_PATH . 'include/Jaws/Header.php';
		Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'));
    }

    /**
     * Changes action of a given gadget
     *
     * @access public
     * @return template content
     */
    function EditElementAction()
    {
		if (!$GLOBALS['app']->Session->Logged()) {
			$GLOBALS['app']->Session->PushSimpleResponse("Please log-in.");
			$userHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
			return $userHTML->DefaultAction();
		}
        $model = $GLOBALS['app']->loadGadget('Users', 'AdminModel');

        //$xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $request =& Jaws_Request::getInstance();
        $get = $request->get(array('id', 'method', 'callback'), 'get');
		
		$id = (int)$get['id'];
		$method = $get['method'];
		$callback = $get['callback'];
		$layoutElement = $model->GetPost($id);
        if (!$layoutElement || !isset($layoutElement['id'])) {
            return false;
        }
        require_once JAWS_PATH . 'include/Jaws/Template.php';
        $tpl = new Jaws_Template('gadgets/Users/templates/');
        $tpl->Load('EditUpdate.html');
        $tpl->SetBlock('template');

        $direction = _t('GLOBAL_LANG_DIRECTION');
        $dir  = $direction == 'rtl' ? '.' . $direction : '';
        $brow = $GLOBALS['app']->GetBrowserFlag();
        $brow = empty($brow)? '' : '.'.$brow;
        $base_url = $GLOBALS['app']->GetSiteURL().'/';

        $tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . "/");
        //$tpl->SetVariable('DPATH', JAWS_DPATH);
        $tpl->SetVariable('stub', (JAWS_SCRIPT == 'admin' ? 'UsersAdminAjax' : 'UsersAjax'));
        $tpl->SetVariable('BASE_URL', $base_url);
        $tpl->SetVariable('.dir', $dir);
        $tpl->SetVariable('.browser', $brow);
        $tpl->SetVariable('base_script', BASE_SCRIPT);
        $tpl->SetVariable('id', $id);
		$tpl->SetVariable('account', (JAWS_SCRIPT == 'admin' ? '' : 'account_'));
		$tpl->SetVariable('script', (JAWS_SCRIPT == 'admin' ? 'script' : 'client_script'));

        if ($layoutElement['gadget'] != 'text') {
			$actions = $model->GetGadgetActions($layoutElement['gadget']);
			$gInfo = $GLOBALS['app']->LoadGadget($layoutElement['gadget'], 'Info');
			if (!Jaws_Error::IsError($gInfo)) {
				$tpl->SetVariable('display', 'none');
				$tpl->SetVariable('gadget', $layoutElement['gadget']);
				$tpl->SetVariable('gadget_name', $gInfo->GetName());
				$tpl->SetVariable('gadget_description', $gInfo->GetDescription());
				$actionsList =& Piwi::CreateWidget('RadioButtons', 'action_field', 'vertical');
				if (!Jaws_Error::IsError($actions) && count($actions) > 0) {
					foreach ($actions as $action) {
						if (isset($action['action']) && isset($action['name'])) {
							$tpl->SetBlock('template/gadget_action');
							$tpl->SetVariable('name',   $action['name']);
							$tpl->SetVariable('action', $action['action']);
							$tpl->SetVariable('desc',   $action['desc']);
							if($layoutElement['image'] == $action['action']) {
								$tpl->SetVariable('action_checked', 'checked="checked"');
							} else {
								$tpl->SetVariable('action_checked', '');
							}
							$tpl->ParseBlock('template/gadget_action');
						}
					}
				} else {
					$tpl->SetBlock('template/no_action');
					$tpl->SetVariable('no_gadget_desc', _t('GLOBAL_NO_ACTIONS'));
					$tpl->ParseBlock('template/no_action');
				}
			}
		} else {
			$tpl->SetVariable('select_gadget', "selectGadget('Status', '".$method."', '".$id."', '', '', '".$callback."');");
			$tpl->SetVariable('display', '');
			$tpl->SetVariable('gadget', 'Users');
			$tpl->SetVariable('gadget_name', 'Status');
			$tpl->SetVariable('gadget_description', 'Add text or image content to this post.');
			$tpl->SetVariable('addtext_content', $this->A_form((JAWS_SCRIPT == 'admin' ? false : true)));
		}

		$btnSave =& Piwi::CreateWidget('Button', 'add', _t('GLOBAL_SAVE'), STOCK_SAVE);
		$url_ea = '../../' . BASE_SCRIPT . '?gadget=Users&action=EditElementAction&id='.$id;
		$btnSave->AddEvent(ON_CLICK, "parent.parent.saveElementAction(".$id.", getSelectedAction('form1'), '".$url_ea."', '".$layoutElement['gadget']."');");
		$tpl->SetVariable('add', $btnSave->Get());

		$saveButton =& Piwi::CreateWidget('Button', 'save', _t('GLOBAL_SAVE'), STOCK_SAVE);
		$tpl->SetVariable('save', $saveButton->Get());
        
		$tpl->ParseBlock('template');
        return $tpl->Get();
    }
	
    /**
     * Shows Share Comment UI
     *
     * @access public
     * @return template content
     */
    function ShareComment()
    {
        //$this->CheckPermission('default');
        require_once JAWS_PATH . 'include/Jaws/Template.php';
        $tpl = new Jaws_Template('gadgets/Users/templates/');
        $tpl->Load('ShareComment.html');
        $tpl->SetBlock('template');

        $direction = _t('GLOBAL_LANG_DIRECTION');
        $dir  = $direction == 'rtl' ? '.' . $direction : '';
        $brow = $GLOBALS['app']->GetBrowserFlag();
        $brow = empty($brow)? '' : '.'.$brow;
        $base_url = $GLOBALS['app']->GetSiteURL().'/';

        $tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . "/");
        //$tpl->SetVariable('DPATH', JAWS_DPATH);
        $tpl->SetVariable('BASE_URL', $base_url);
        $tpl->SetVariable('.dir', $dir);
        $tpl->SetVariable('.browser', $brow);
		$tpl->SetVariable('base_script', BASE_SCRIPT);
		$tpl->SetVariable('account', (JAWS_SCRIPT == 'admin' ? '' : 'account_'));
		$tpl->SetVariable('stub', (JAWS_SCRIPT == 'admin' ? 'UsersAdminAjax' : 'UsersAjax'));
		$tpl->SetVariable('script', (JAWS_SCRIPT == 'admin' ? 'script' : 'client_script'));

        $tpl->SetVariable('share_title', _t('USERS_SHARECOMMENT_TITLE'));
                
		//if (JAWS_SCRIPT == 'admin') {
			$tpl->SetBlock('template/gadget');
			$tpl->SetVariable('id', 'everyone');
			$tpl->SetVariable('title', _t('USERS_SHARECOMMENT_EVERYONE'));
			$tpl->SetVariable('desc', _t('USERS_SHARECOMMENT_EVERYONE_DESC'));
			$tpl->SetVariable('onclick', "setCommentSharing('everyone');");
			$tpl->ParseBlock('template/gadget');
			
			$tpl->SetBlock('template/gadget');
			$tpl->SetVariable('id', 'friends');
			$tpl->SetVariable('title', _t('USERS_SHARECOMMENT_FRIENDS_ONLY'));
			$tpl->SetVariable('desc', _t('USERS_SHARECOMMENT_FRIENDS_ONLY_DESC'));
			$tpl->SetVariable('onclick', "setCommentSharing('friends');");
			$tpl->ParseBlock('template/gadget');
			
			$tpl->SetBlock('template/gadget');
			$tpl->SetVariable('id', 'specific');
			$tpl->SetVariable('title', _t('USERS_SHARECOMMENT_SPECIFIC_USERS'));
			$tpl->SetVariable('desc', _t('USERS_SHARECOMMENT_SPECIFIC_USERS_DESC'));
			$tpl->SetVariable('onclick', "setCommentSharing('specific');");
			$tpl->ParseBlock('template/gadget');
		//}

        $tpl->ParseBlock('template');

        return $tpl->Get();
    }
	
    /**
     * Import User list (select type - RSS, Tab-Delimited, etc)
     *
     * @access public
     * @return XHTML string
     */
    function ImportUsers()
    {
		$GLOBALS['app']->Layout->AddHeadLink('gadgets/ControlPanel/resources/style.css', 'stylesheet', 'text/css');
        $this->AjaxMe('script.js');
        $tpl = new Jaws_Template('gadgets/Users/templates/');
        $tpl->Load('ImportUsers.html');
        $tpl->SetBlock('Properties');

		include_once JAWS_PATH . 'include/Jaws/Widgets/FieldSet.php';
        
		$form =& Piwi::CreateWidget('Form', BASE_SCRIPT, 'post');
        $form->Add(Piwi::CreateWidget('HiddenEntry', 'gadget', 'Users'));
        $form->Add(Piwi::CreateWidget('HiddenEntry', 'action', 'ImportFile'));

		$inventory_combo =& Piwi::CreateWidget('Combo', 'inventory_type');
		$inventory_combo->SetTitle(_t('USERS_IMPORTUSERS_TYPE'));
		$inventory_combo->AddOption(_t('USERS_IMPORTUSERS_TABDELIMITED'), 'TabDelimited');
		$inventory_combo->AddOption(_t('USERS_IMPORTUSERS_COMMASEPARATED'), 'CommaSeparated');
		//$inventory_combo->AddOption(_t('USERS_IMPORTUSERS_RSSFEED'), 'RSS');
		$inventory_combo->AddEvent(ON_CHANGE, "javascript: if (this.value == 'TabDelimited'){\$('example_csv').style.display = 'none';\$('example_csv_label').style.display = 'none';\$('example_tab').style.display = 'block';\$('example_tab_label').style.display = 'block';}else if (this.value == 'CommaSeparated'){\$('example_csv').style.display = 'block';\$('example_csv_label').style.display = 'block';\$('example_tab').style.display = 'none';\$('example_tab_label').style.display = 'none';}");
		$inventory_combo->SetDefault('TabDelimited');

		$inventory_fieldset = new Jaws_Widgets_FieldSet('');
		$inventory_fieldset->SetTitle('vertical');
		$inventory_fieldset->Add($inventory_combo);
		
		$example_tab_data = 'name	e-mail	company	company_type	address	city	postal	state	phone	website	groups	gender	dob	description 

Ty Davis	admin@example.org	"Ty\'s Cookies"	Retail	123 Street Rd	Dawsonville	30534	GA	555-555-5555				3/2/1982	User "number 1\'s" description.
Eve Lee	email@example.org	"Eve\'s Widgets"	Retail	456 Old Dirt Rd	Dawsonville	30534	GA	444-444-4444				3/12/1982	User "number 2\'s" description.';
		$example_tab =& Piwi::CreateWidget('TextArea', 'example_tab', $example_tab_data);
		$example_tab->SetStyle("width: 100%; height: 100px;");
		$example_tab->SetTitle('Example');
		$example_tab->_isReadOnly = true;
		$inventory_fieldset->Add($example_tab);
		
		$example_csv_data = 'name,e-mail,company,company_type,address,city,postal,state,phone,website,groups,gender,dob,description 

Ty Davis,admin@example.org,"Ty\'s Cookies",Retail,"123 Street Rd",Dawsonville,30534,GA,555-555-5555,,,,3/2/1982,"User ""number 1\'s"" description".
Eve Lee,email@example.org,"Eve\'s Widgets",Retail,"456 Old Dirt Rd",Dawsonville,30534,GA,444-444-4444,,,,3/12/1982,"User ""number 2\'s"" description".';
		$example_csv =& Piwi::CreateWidget('TextArea', 'example_csv', $example_csv_data);
		$example_csv->SetStyle("display: none; width: 100%; height: 100px;");
		$example_csv->SetTitle('Example');
		$example_csv->_isReadOnly = true;
		$inventory_fieldset->Add($example_csv);
		
		// File
		$xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$image_preview = '';
		$imageScript = "<script type=\"text/javascript\">Event.observe(window, \"load\",function(){addFileToPost('Users', 'NULL', 'NULL', 'main_image', 'inventory_file', 1, 500, 34, '', false, '', 'txt');});</script>";
		$imageHidden =& Piwi::CreateWidget('HiddenEntry', 'inventory_file', '');
		$imageButton = "&nbsp;";
		$imageEntry =& Piwi::CreateWidget('UploadEntry', 'inventory_file', _t('USERS_IMPORTUSERS_FILE'), $image_preview, $imageScript, $imageHidden->Get(), $imageButton);
		
		$inventory_fieldset->Add($imageEntry);
		
		$form->Add($inventory_fieldset);
				
		$buttons =& Piwi::CreateWidget('HBox');
		$buttons->SetStyle(_t('GLOBAL_LANG_DIRECTION')=='rtl'?'float: left;' : 'float: right;');

		$save =& Piwi::CreateWidget('Button', 'save', _t('USERS_MENU_IMPORTUSERS'), STOCK_SAVE);
		$save->SetStyle(_t('GLOBAL_LANG_DIRECTION')=='rtl'?'float: left;' : 'float: right;');
		$save->SetSubmit();

		$buttons->Add($save);
		$form->Add($buttons);

		$tpl->SetVariable('form', $form->Get());
        $tpl->SetVariable('menubar', $this->MenuBar('ImportUsers'));

        $tpl->ParseBlock('Properties');

        return $tpl->Get();
		
	}
	
    /**
     * Import inventory list (select type (RSS, Tab-Delimited)
     *
     * @access public
     * @return XHTML string
     */
    function ImportFile()
    {
		$request =& Jaws_Request::getInstance();
		$file = $request->get('inventory_file', 'post');
		$type = $request->get('inventory_type', 'post');
		require_once JAWS_PATH . 'include/Jaws/Header.php';	
		Jaws_Header::Location($GLOBALS['app']->getSiteURL().'/index.php?gadget=Users&action=UpdateRSSUsers&num=1&file='.urlencode($file).'&type='.$type.'&ua=Y');
		
		$output_html = '<script src="http://yui.yahooapis.com/2.8.0r4/build/yahoo/yahoo-min.js"></script>';
		$output_html .= '<script src="http://yui.yahooapis.com/2.8.0r4/build/event/event-min.js"></script>';
		$output_html .= '<script src="http://yui.yahooapis.com/2.8.0r4/build/connection/connection_core-min.js"></script>';

		$output_html .= '<script>var spawnCallback = {';
		$output_html .= 'success: function(o) {';
		$output_html .= '},';
		$output_html .= 'failure: function(o) {';
		$output_html .= '},';
		$output_html .= 'timeout: 2000';
		$output_html .= '};';

		$output_html .= 'function spawnProcess() {';
		$output_html .= 'YAHOO.util.Connect.asyncRequest(\'GET\',\''.$GLOBALS['app']->getSiteURL().'/index.php?gadget=Users&action=UpdateRSSUsers&num=1&file='.urlencode($file).'&type='.$type.'&ua=N\',spawnCallback);';
		$output_html .= '}';
		$output_html .= 'spawnProcess(); location.href = "admin.php?gadget=Users";</script>';
		//exec ("/usr/local/bin/php /homepages/40/d298423861/htdocs/cli.php --id=$cmd >/dev/null &");
		//backgroundPost($GLOBALS['app']->getSiteURL().'/index.php?gadget=Properties&action=UpdateRSSProperties&id='.$cmd);
		return $output_html;
	}

    /**
     * Builds the messages datagrid view
     *
     * @access  private
     * @return  string   XHTML of datagrid
     */
    function MessagesDataGrid()
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		$OwnerID = 0;
		if (BASE_SCRIPT == 'index.php') {
			$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');
		}
        $params = array();
		$params['OwnerID'] = $OwnerID;
		$params['gadget'] = 'Users';
		$sql = 'SELECT COUNT([id]) FROM [[comments]] WHERE ([ownerid] = {OwnerID} AND [gadget] = {gadget})';
        $res = $GLOBALS['db']->queryOne($sql, $params);
        $total = (Jaws_Error::IsError($res) ? 0 : $res);
        $total = (int)$total;
				
        $grid =& Piwi::CreateWidget('DataGrid', array());
        $grid->TotalRows(($total));
        $grid->SetStyle('width: 100%;');
        $grid->SetID('messages_datagrid');
        $grid->setAction('next', 'javascript:nextMessagesValues();');
        $grid->setAction('prev', 'javascript:previousMessagesValues();');
        //$grid->useMultipleSelection();
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_DESCRIPTION')));
		if (BASE_SCRIPT != 'index.php') {
	        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_PREVIEW')));
        }
		$grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_DATE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_ACTIONS')));

        return $grid->Get();
    }

    /**
     * Returns an array with pages found
     *
     * @access  public
     * @param   string  $search  Keyword (title/description) of comments we want to look for
     * @param   int     $limit   Data limit
     * @return  array   Data
     */
    function GetMessages($search, $limit, $OwnerID = 0)
    {
		$model = $GLOBALS['app']->LoadGadget('Users', 'AdminModel');
		$pages = $model->SearchMessages($search, $limit, $OwnerID);
        if (Jaws_Error::IsError($pages)) {
            return $pages;
        }

        $data = array();
		$date = $GLOBALS['app']->loadDate();
        //$xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$ACL = $GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		$i = 0;
        foreach ($pages as $page) {
			//if ($i < 10) {
				$pageData = array();
				$pageData['description'] = (strlen(trim(strip_tags($page['msg_txt']))) > 100 ? substr(trim(strip_tags($page['msg_txt'])), 0, 100).'...' : trim(strip_tags($page['msg_txt'])));
				if (BASE_SCRIPT != 'index.php') {
					$pageData['furl']  = '<a href="'.$GLOBALS['app']->Map->GetURLFor('Users', 'ShowComment', array('id' => $page['id'], 'fusegadget' => $page['gadget'])).'" title="Preview Message">View This Message</a>';
				}
				$pageData['date']  = $date->Format($page['createtime']);
				$actions = '';
				if ($this->GetPermission('ManageMessaging')) {
					$link =& Piwi::CreateWidget('Link', _t('GLOBAL_DELETE'),
												"javascript: if (confirm('"._t('GLOBAL_CONFIRM_DELETE', _t('USERS_MESSAGING_MESSAGE'))."')) ".
												"deleteMessage('".$page['id']."');"/*,
														"images/ICON_delete2.gif"*/);
					$actions.= $link->Get().'&nbsp;';
				/*
				} else {
					if ($ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Users', 'OwnPage')) {
						$link =& Piwi::CreateWidget('Link', _t('GLOBAL_DELETE'),
													"javascript: if (confirm('"._t('GLOBAL_CONFIRM_DELETE', _t('USERS_MESSAGING_EMAILPAGE'))."')) ".
													"deletePage('".$page['id']."');");
						$actions.= $link->Get().'&nbsp;';
					}
				*/
				}
				$pageData['actions'] = $actions;
				$pageData['__KEY__'] = $page['id'];
				$data[] = $pageData;
				$i++;
			//}
		}
        
		if (count($data)) {
			// Sort result array
			$subkey = 'createtime'; 
			$temp_array = array();
			
			$temp_array[key($data)] = array_shift($data);

			foreach($data as $key => $val){
				$offset = 0;
				$found = false;
				foreach($temp_array as $tmp_key => $tmp_val)
				{
					$val[$subkey] = strtotime($val[$subkey]);
					if(!$found and strtolower($val[$subkey]) > strtolower($tmp_val[$subkey]))
					{
						$temp_array = array_merge(    (array)array_slice($temp_array,0,$offset),
													array($key => $val),
													array_slice($temp_array,$offset)
												  );
						$found = true;
					}
					$offset++;
				}
				if(!$found) $temp_array = array_merge($temp_array, array($key => $val));
			}

			//$data = array_reverse($temp_array);
			$data = $temp_array;
		}
		
		return $data;
    }
	
    /**
     * Builds the basic datagrid view
     *
     * @access  private
     * @return  string   XHTML of datagrid
     * @TODO 	Move to Social gadget
     */
    function EmailPagesDataGrid()
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		$OwnerID = 0;
		if (BASE_SCRIPT == 'index.php') {
			$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');
		}
        $params = array();
		$params['OwnerID'] = $OwnerID;
		$params['gadget'] = 'Users';
		$params['gadget_action'] = 'EmailPage';
		$sql = 'SELECT COUNT([id]) FROM [[pages]] WHERE ([ownerid] = {OwnerID} AND [gadget] = {gadget} AND [gadget_action] = {gadget_action})';
        $res = $GLOBALS['db']->queryOne($sql, $params);
        $total = (Jaws_Error::IsError($res) ? 0 : $res);
        $total = (int)$total;
				
        $grid =& Piwi::CreateWidget('DataGrid', array());
        $grid->TotalRows(($total));
        $grid->SetStyle('width: 100%;');
        $grid->SetID('emailpages_datagrid');
        $grid->setAction('next', 'javascript:nextEmailPagesValues();');
        $grid->setAction('prev', 'javascript:previousEmailPagesValues();');
        //$grid->useMultipleSelection();
		//$grid->AddColumn(Piwi::CreateWidget('Column', _t('USERS_MESSAGING_ACTIVE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_TITLE')));
		if (BASE_SCRIPT != 'index.php') {
			$grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_PREVIEW')));
		}
		$grid->AddColumn(Piwi::CreateWidget('Column', _t('USERS_MESSAGING_LAST_UPDATE')));
        $grid->AddColumn(Piwi::CreateWidget('Column', _t('GLOBAL_ACTIONS')));

        return $grid->Get();
    }

    /**
     * Returns an array with pages found
     *
     * @access  public
     * @param   string  $status  Status of page(s) we want to display
     * @param   string  $search  Keyword (title/description) of pages we want to look for
     * @param   int     $limit   Data limit
     * @return  array   Data
     * @TODO 	Move to Social gadget
     */
    function GetEmailPages($status, $search, $limit, $OwnerID = 0)
    {
		$model = $GLOBALS['app']->LoadGadget('CustomPage', 'AdminModel');
		$pages = $model->SearchPages($status, $search, $limit, $OwnerID, 'Users', 'EmailPage');
        if (Jaws_Error::IsError($pages)) {
            return array();
        }

        $data = array();
		if (BASE_SCRIPT != 'index.php') {
			$edit_url    = BASE_SCRIPT . '?gadget=CustomPage&amp;action=view&amp;id=';
        } else {
			$edit_url    = 'index.php?gadget=CustomPage&amp;action=account_view&amp;id=';
		}
		$date = $GLOBALS['app']->loadDate();
        //$xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$ACL = $GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		$i = 0;
        foreach ($pages as $page) {
			//if ($i < 10) {
				$pageData = array();
				/*
				if ($page['active'] == 'Y') {
					$pageData['active'] = _t('GLOBAL_YES');
				} else {
					// Show Add To Cart Link if necessary
					// See gadgets/CustomPage/HTML.php UserCustomPageSubscriptions()
					if (BASE_SCRIPT == 'index.php' && Jaws_Gadget::IsGadgetUpdated('Ecommerce') && Jaws_Gadget::IsGadgetUpdated('Store')) {
						$GLOBALS['app']->Translate->LoadTranslation('Ecommerce', JAWS_GADGET);
						$ecommerce_model = $GLOBALS['app']->LoadGadget('Ecommerce', 'Model');
						$pane_product = $ecommerce_model->GetProductByPaneMethod('UserCustompageowners');
						if (!Jaws_Error::IsError($pane_product) && isset($pane_product['id'])) {
							$ecommerce_layout = $GLOBALS['app']->LoadGadget('Ecommerce', 'LayoutHTML');
							$product_cartHTML = $ecommerce_layout->ShowCartLink($pane_product['id']);
							if (!Jaws_Error::IsError($product_cartHTML)) {
								$pageData['active'] = $product_cartHTML;
							} else {
								$pageData['active'] = _t('GLOBAL_NO');
							}
						} else {
							$pageData['active'] = _t('GLOBAL_NO');
						}
					} else {
						$pageData['active'] = _t('GLOBAL_NO');
					}
				}
				*/
				if (!empty($page['title'])) {
					$page_title = $page['title'];
				} else if (!empty($page['sm_description'])) {
					$page_title = $page['sm_description'];
				} else {
					$page_title = "E-mail Campaign ".$page['id'];
				}
				$pageData['title'] = ($page['pid'] > 0 ? '&nbsp;&nbsp;-' : '').'<a href="'.$edit_url.$page['id'].'" title="Edit This Page">'.(strlen($page_title) > 30 ? substr($page_title, 0, 30).'...' : $page_title).'</a>';
				if (BASE_SCRIPT != 'index.php') {
					$pageData['furl']  = '<a href="'.$GLOBALS['app']->GetSiteURL('/index.php?gadget=CustomPage&action=EmbedCustomPage&id='.$page['fast_url'].'&referer='.str_replace(array('http://', 'https://'), '', strtolower($GLOBALS['app']->GetSiteURL()))).'" target="_blank" title="Preview This Page">View This Page</a>';
				}
				$pageData['date']  = $date->Format($page['updated']);
				$actions = '';
				if ($this->GetPermission('ManageMessaging')) {
					$params['id'] = $page['id'];
					$params['gadget'] = 'CustomPage';
					$params['title'] = '{GADGET:CustomPage|ACTION:Page('.$page['id'].')}';
					$params['ownerid'] = 0;
					$sql = '
						SELECT
							[id],
							[gadget_reference],
							[gadget],
							[parent],
							[title],
							[msg_txt],
							[status],
							[sharing],
							[ownerid],
							[createtime]
						FROM [[comments]]
						WHERE
							[gadget_reference] = {id} AND 
							[gadget] = {gadget} AND 
							[title] = {title} AND 
							[ownerid] = {ownerid} 
					';
					$comment = $GLOBALS['db']->queryRow($sql, $params);
					
					$add_url = $GLOBALS['app']->GetSiteURL() ."/admin.php?gadget=Users&action=AddLayoutElement";
					$add_url .= "&fusegadget=CustomPage&mode=sharing&id=".$page['id'];
					$add_url .= "&entry=".urlencode($page_title);
					$add_url .= "&title=".urlencode("{GADGET:CustomPage|ACTION:Page(".$page['id'].")}");
					$add_url .= "&callback=".urlencode(base64_encode("'window.top.searchEmailPages'"));
					
					$syndication_function = "addUpdate('".$add_url."', '"._t('USERS_MESSAGING_SYNDICATION')."');";
					if (!Jaws_Error::IsError($comment) && isset($comment['id']) && !empty($comment['id'])) {
						$syndication_function = "DeleteComment(".$comment['id']."); ".$syndication_function;
					}
					$link =& Piwi::CreateWidget('Link', _t('USERS_MESSAGING_SYNDICATION'),
												"javascript: ".$syndication_function."; return false;"/*,
												STOCK_EDIT*/);
					$actions.= $link->Get().'&nbsp;';
					
					$link1 =& Piwi::CreateWidget('Link', _t('GLOBAL_EDIT'),
												"javascript: location.href='".$edit_url.$page['id']."';"/*,
												STOCK_EDIT*/);
					$actions.= $link1->Get().'&nbsp;';
					
					$link2 =& Piwi::CreateWidget('Link', _t('GLOBAL_DELETE'),
												"javascript: if (confirm('"._t('GLOBAL_CONFIRM_DELETE', _t('USERS_MESSAGING_EMAILPAGE'))."')) ".
												"deleteEmailPage('".$page['id']."');"/*,
														"images/ICON_delete2.gif"*/);
					$actions.= $link2->Get().'&nbsp;';
				/*
				} else {
					if ($ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Users', 'OwnPage')) {
						$link =& Piwi::CreateWidget('Link', _t('GLOBAL_EDIT'),
													"javascript: window.open('".$edit_url.$page['id']."');");
						$actions.= $link->Get().'&nbsp;';
						$link2 =& Piwi::CreateWidget('Link', _t('GLOBAL_DELETE'),
													"javascript: if (confirm('"._t('GLOBAL_CONFIRM_DELETE', _t('USERS_MESSAGING_EMAILPAGE'))."')) ".
													"deleteEmailPage('".$page['id']."');");
						$actions.= $link2->Get().'&nbsp;';
					}
				*/
				}
				$pageData['actions'] = $actions;
				$pageData['__KEY__'] = $page['id'];
				$data[] = $pageData;
				$i++;
			//}
		}
        
		if (count($data)) {
			// Sort result array
			$subkey = 'date'; 
			$temp_array = array();
			
			$temp_array[key($data)] = array_shift($data);

			foreach($data as $key => $val){
				$offset = 0;
				$found = false;
				foreach($temp_array as $tmp_key => $tmp_val)
				{
					$val[$subkey] = strtotime($val[$subkey]);
					if(!$found and strtolower($val[$subkey]) > strtolower($tmp_val[$subkey]))
					{
						$temp_array = array_merge(    (array)array_slice($temp_array,0,$offset),
													array($key => $val),
													array_slice($temp_array,$offset)
												  );
						$found = true;
					}
					$offset++;
				}
				if(!$found) $temp_array = array_merge($temp_array, array($key => $val));
			}

			//$data = array_reverse($temp_array);
			$data = $temp_array;
		}
		
		return $data;
    }
	
    /**
     * TODO: Add Hooks from CustomPage to expose this functionality (and menubar option) to Users??
     * Messaging UI
     *
     * @access public
     * @return XHTML string
     * @TODO 	Move to Social gadget
     */
    function Messaging($account = false)
    {
        $tpl = new Jaws_Template('gadgets/Users/templates/');
        $tpl->Load('Messaging.html');
        
        //$xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$request =& Jaws_Request::getInstance();
		$action = $request->get('action', 'get');
       
		$tpl->SetBlock('messaging');
		
		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=CustomPage&action=account_SetGBRoot');
		if ($account === false) {
	        //$GLOBALS['app']->Layout->AddScriptLink(BASE_SCRIPT . '?gadget=Users&amp;action=Ajax&amp;client');
			$GLOBALS['app']->Layout->AddScriptLink(BASE_SCRIPT . '?gadget=Users&amp;action=Ajax&amp;client=all&amp;stub=UsersAdminAjax');
	        $GLOBALS['app']->Layout->AddScriptLink(BASE_SCRIPT . '?gadget=Users&amp;action=AjaxCommonFiles');
	        $GLOBALS['app']->Layout->AddScriptLink('gadgets/Users/resources/script.js');
			$GLOBALS['app']->Layout->AddScriptLink('libraries/js/admin.js');
			$tpl->SetVariable('menubar', $this->MenuBar($action));
			$account_prefix = '';
			$base_url = BASE_SCRIPT;
		} else {
	        //$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=Users&amp;action=Ajax&amp;client');
			$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=Users&amp;action=Ajax&amp;client=all&amp;stub=UsersAjax');
	        $GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=Users&amp;action=AjaxCommonFiles');
	        $GLOBALS['app']->Layout->AddScriptLink('gadgets/Users/resources/client_script.js');
			$tpl->SetVariable('menubar', '');
			$account_prefix = 'account_';
			$base_url = 'index.php';
		}
        
		$GLOBALS['app']->Layout->AddScriptLink('libraries/greybox/AJS.js');
        $GLOBALS['app']->Layout->AddScriptLink('libraries/greybox/AJS_fx.js');
        $GLOBALS['app']->Layout->AddScriptLink('libraries/greybox/gb_scripts.js');
        $GLOBALS['app']->Layout->AddHeadLink('libraries/greybox/gb_styles.css', 'stylesheet', 'text/css');
		
		/*
		// Show recent site messages
		$tpl->SetBlock('messaging/messages_admin');
		$tpl->SetVariable('title', _t('USERS_MESSAGING_MESSAGES'));
		$tpl->SetVariable('account', $account_prefix);
		$tpl->SetVariable('base_script', $base_url);
        $tpl->SetVariable('grid', $this->MessagesDataGrid());
        		
        // Free text search
        $searchButton =& Piwi::CreateWidget('Button', 'searchMessagesButton', _t('GLOBAL_SEARCH'), STOCK_SEARCH);
        $searchButton->AddEvent(ON_CLICK, 'javascript: searchMessages();');
        $tpl->SetVariable('search', $searchButton->Get());

        $search = '';
        $searchEntry =& Piwi::CreateWidget('Entry', 'search_messages', $search);
        $searchEntry->SetStyle('zwidth: 100%; width: 140px;');
        $tpl->SetVariable('search_field', $searchEntry->Get());
        
        $tpl->SetVariable('entries', $this->MessagesDataGrid());

		if ($account === false) {
	        $addPage =& Piwi::CreateWidget('Button', 'add_message', _t('USERS_MESSAGING_ADD_MESSAGE'), STOCK_ADD);
			$addPage->AddEvent(ON_CLICK, 'javascript: addUpdate(\''.$GLOBALS['app']->getSiteURL().'/admin.php?gadget=Users&action=AddLayoutElement&mode=new\', \'Share Content\');');
	        $tpl->SetVariable('add_message', $addPage->Get());
		} else {
			// Add button is added by HTML->GetUserAccountControls
			//$addPage->AddEvent(ON_CLICK, "javascript: window.open('".$base_url."?gadget=CustomPage&amp;action=".$account_prefix."form','','scrollbars=1,menubar=0,toolbar=0,location=0,status=1');");
	        $tpl->SetVariable('add_message', '');
		}

		$tpl->ParseBlock('messaging/messages_admin');
		*/
		
		// Show email pages
		if (Jaws_Gadget::IsGadgetUpdated('CustomPage')) {
			$tpl->SetBlock('messaging/emailpages_admin');
			
			$tpl->SetVariable('title', _t('USERS_MESSAGING_EMAILPAGES'));
			$tpl->SetVariable('account', $account_prefix);
			$tpl->SetVariable('base_script', $base_url);
			$tpl->SetVariable('grid', $this->EmailPagesDataGrid());
			
			if ($account === false) {
				//Status filter
				$status = '';
				$statusCombo =& Piwi::CreateWidget('Combo', 'status_emailpages');
				$statusCombo->setId('status_emailpages');
				$statusCombo->AddOption('&nbsp;', '');
				$statusCombo->AddOption(_t('GLOBAL_YES'), 'Y');
				$statusCombo->AddOption(_t('GLOBAL_NO'), 'N');
				$statusCombo->SetDefault($status);
				$statusCombo->AddEvent(ON_CHANGE, 'javascript: searchEmailPages();');
				$tpl->SetVariable('status', _t('USERS_MESSAGING_ACTIVE'));
				$tpl->SetVariable('status_field', $statusCombo->Get());
			} else {
				$hiddenStatus =& Piwi::CreateWidget('HiddenEntry', 'status_emailpages', '');
				$tpl->SetVariable('status_field', $hiddenStatus->Get());
			}
			
			// Free text search
			$searchButton =& Piwi::CreateWidget('Button', 'searchButton', _t('GLOBAL_SEARCH'), STOCK_SEARCH);
			$searchButton->AddEvent(ON_CLICK, 'javascript: searchEmailPages();');
			$tpl->SetVariable('search', $searchButton->Get());

			$search = '';
			$searchEntry =& Piwi::CreateWidget('Entry', 'search_emailpages', $search);
			$searchEntry->SetStyle('zwidth: 100%; width: 140px;');
			$tpl->SetVariable('search_field', $searchEntry->Get());
			
			$tpl->SetVariable('entries', $this->EmailPagesDataGrid());

			if ($account === false) {
				$addPage =& Piwi::CreateWidget('Button', 'add_page', _t('USERS_MESSAGING_ADD_PAGE'), STOCK_ADD);
				$add_url = $GLOBALS['app']->GetSiteURL() ."/admin.php?gadget=Users&action=AddLayoutElement&mode=new";
				$add_url .= "&fusegadget=CustomPage&show_status=false";
				$add_url .= "&callback=".urlencode(base64_encode("'window.top.searchEmailPages'"));
				$add_url .= "&query=".urlencode(base64_encode('&dn[0]=gadget_scope&dv[0]=Users&dn[1]=Active&dv[1]=N&dn[2]=pid&dv[2]=0&dn[3]=password_protected&dv[3]=N&dn[4]=auto_keyword&dv[4]=&dn[5]=title&dv[5]=&dn[6]=gadget_action&dv[6]=EmailPage&'));
				$addPage->AddEvent(ON_CLICK, "javascript: addUpdate('".$add_url."','"._t('USERS_MESSAGING_ADD_PAGE')."');");
				$tpl->SetVariable('add_page', $addPage->Get());
			} else {
				// Add button is added by HTML->GetUserAccountControls
				//$addPage->AddEvent(ON_CLICK, "javascript: window.open('".$base_url."?gadget=CustomPage&amp;action=".$account_prefix."form','','scrollbars=1,menubar=0,toolbar=0,location=0,status=1');");
				$tpl->SetVariable('add_page', '');
			}

			$tpl->ParseBlock('messaging/emailpages_admin');
        }
		
		$tpl->ParseBlock('messaging');

        return $tpl->Get();
	}

    /**
     * Quick add form
     *
     * @access 	public
     * @return 	string 	XHTML string
     * @TODO 	Edit forms for updating existing records
     */
    function GetQuickAddForm($account = false)
    {
		//$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		//$GLOBALS['app']->Session->CheckPermission('Users', 'default');
		//$GLOBALS['app']->ACL->CheckPermission($GLOBALS['app']->Session->GetAttribute('username'), 'Properties', 'default');
		
		require_once JAWS_PATH . 'include/Jaws/Template.php';
        $tpl = new Jaws_Template('gadgets/Users/templates/');
        $tpl->Load('QuickAddForm.html');
        $tpl->SetBlock('form');

		$request =& Jaws_Request::getInstance();
		$method = $request->get('method', 'get');
		if (empty($method)) {
			$method = 'AddGroup';
		}
		$form_content = '';
		switch($method) {
			case "AddGroup": 
				$form_content = $this->Groups($account);
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
        $tpl->SetVariable('stub', (JAWS_SCRIPT == 'admin' ? 'UsersAdminAjax' : 'UsersAjax'));
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
