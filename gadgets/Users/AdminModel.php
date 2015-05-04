<?php
/**
 * Users Core Gadget
 *
 * @category   GadgetModel
 * @package    Users
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @copyright  2004-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
require_once JAWS_PATH . 'gadgets/Users/Model.php';
class UsersAdminModel extends UsersModel
{
    var $_newChecksums = array();
    var $_propCount = 1;
    var $_propTotal = 0;
	
    /**
     * Installs the gadget
     *
     * @access       public
     * @return       true on successful installation, Jaws_Error otherwise
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
        $GLOBALS['app']->Shouter->NewShouter('Core', 'onAddUser');             // trigger an action when we add an user
        $GLOBALS['app']->Shouter->NewShouter('Core', 'onDeleteUser');          // trigger an action when we delete an user
        $GLOBALS['app']->Shouter->NewShouter('Core', 'onUpdateUser');          // and when we update a user..
        $GLOBALS['app']->Shouter->NewShouter('Core', 'onAddGroup');            // and also when we add a group
        $GLOBALS['app']->Shouter->NewShouter('Core', 'onDeleteGroup');         // and also when we delete a group
        $GLOBALS['app']->Shouter->NewShouter('Core', 'onUpdateGroup');         // and when we update a group..
        $GLOBALS['app']->Shouter->NewShouter('Core', 'onAddComment');          
        $GLOBALS['app']->Shouter->NewShouter('Core', 'onDeleteComment');         
        $GLOBALS['app']->Shouter->NewShouter('Core', 'onUpdateComment');         
        $GLOBALS['app']->Shouter->NewShouter('Core', 'onAddUserToGroup');
        $GLOBALS['app']->Shouter->NewShouter('Core', 'onDeleteUserFromGroup');
        $GLOBALS['app']->Shouter->NewShouter('Core', 'onAddUserToFriend');
        $GLOBALS['app']->Shouter->NewShouter('Core', 'onDeleteUserFromFriend');
        $GLOBALS['app']->Shouter->NewShouter('Core', 'onFriendGroup');
        $GLOBALS['app']->Shouter->NewShouter('Core', 'onDefriendGroup');
        $GLOBALS['app']->Shouter->NewShouter('Core', 'onLoadAccountNews');
        $GLOBALS['app']->Shouter->NewShouter('Core', 'onBeforeLoadAccountPublic');
        $GLOBALS['app']->Shouter->NewShouter('Core', 'onBeforeLoadHomepage');
        $GLOBALS['app']->Shouter->NewShouter('Core', 'onBeforeLoadGroupPage');
        $GLOBALS['app']->Shouter->NewShouter('Core', 'onBeforeLoadAddUpdate');
        $GLOBALS['app']->Shouter->NewShouter('Core', 'onAddCommentSubscription');
        $GLOBALS['app']->Shouter->NewShouter('Core', 'onUpdateCommentSubscription');
        $GLOBALS['app']->Shouter->NewShouter('Core', 'onDeleteCommentSubscription');

        $GLOBALS['app']->loadClass('Listener', 'Jaws_EventListener');
        $GLOBALS['app']->Listener->NewListener('Users', 'onAfterEnablingGadget', 'UpdateAllUsersGadgets');
        $GLOBALS['app']->Listener->NewListener('Users', 'onAfterEnablingGadget', 'InsertDefaultChecksums');
        $GLOBALS['app']->Listener->NewListener('Users', 'onAfterDisablingGadget', 'DeleteAllUsersGadgets');
        $GLOBALS['app']->Listener->NewListener('Users', 'onAddUserToGroup', 'InsertGroupsFriends');
        $GLOBALS['app']->Listener->NewListener('Users', 'onDeleteUser', 'RemoveUsersGadgets');
        $GLOBALS['app']->Listener->NewListener('Users', 'onDeleteUser', 'RemoveUsersFriends');
        $GLOBALS['app']->Listener->NewListener('Users', 'onDeleteUser', 'RemoveUsersComments');
        $GLOBALS['app']->Listener->NewListener('Users', 'onUpdateUser', 'UpdateUsersGadgets');
        $GLOBALS['app']->Listener->NewListener('Users', 'onLoadAccountNews', 'ShowUsersRequests');

        //registry keys.
        $GLOBALS['app']->Registry->NewKey('/gadgets/Users/pluggable', 'false');
        $GLOBALS['app']->Registry->NewKey('/gadgets/Users/password_recovery', 'false');
        $GLOBALS['app']->Registry->NewKey('/gadgets/Users/register_notification', 'true');
        $GLOBALS['app']->Registry->NewKey('/gadgets/Users/protected_pages', '');
        $GLOBALS['app']->Registry->NewKey('/gadgets/Users/recentcomments_limit', 10);
		$GLOBALS['app']->Registry->NewKey('/gadgets/Users/social_sign_in', 'true');
		$GLOBALS['app']->Registry->NewKey('/gadgets/Users/signup_requires_address', 'false');

        //Create the group 'Jaws_Users'
        require_once JAWS_PATH . 'include/Jaws/User.php';
        $userModel = new Jaws_User;
        $userModel->addGroup('users', 'Users', '', false); //Don't check if it returns true or false
        $userModel->addGroup('no_profile', 'no_profile', '', false); //Don't check if it returns true or false
        $userModel->addGroup('profile', 'profile', '', false); //Don't check if it returns true or false
        
        return true;
    }

    /**
     * Update the gadget
     *
     * @access  public
     * @param   string  $old    Current version (in registry)
     * @param   string  $new    New version (in the $gadgetInfo file)
     * @return  boolean  Success/Failure (Jaws_Error)
     */
    function UpdateGadget($old, $new)
    {
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$GLOBALS['app']->loadClass('Listener', 'Jaws_EventListener');
        if (version_compare($old, '0.8.0', '<')) {
            $result = $this->installSchema('0.8.0.xml', '', "$old.xml");
            if (Jaws_Error::IsError($result)) {
                return $result;
            }
	        
			//Create the group 'Jaws_Users'
            $userModel->addGroup('users', 'Users', '', false); //Don't check if it returns true or false
	        $userModel->addGroup('no_profile', 'no_profile', '', false); //Don't check if it returns true or false
	        $userModel->addGroup('profile', 'profile', '', false); //Don't check if it returns true or false
        }

        if (version_compare($old, '0.8.2', '<')) {
            $result = $this->installSchema('0.8.2.xml', '', '0.8.0.xml');
            if (Jaws_Error::IsError($result)) {
                return $result;
            }
            			
        }

        if (version_compare($old, '0.8.4', '<')) {
            $result = $this->installSchema('0.8.4.xml', '', '0.8.2.xml');
            if (Jaws_Error::IsError($result)) {
                return $result;
            }

            // Registry keys
            $GLOBALS['app']->Registry->NewKey('/gadgets/Users/register_notification', 'true');

            $GLOBALS['app']->ACL->NewKey('/ACL/gadgets/Users/ManageProperties',       'false');
            $GLOBALS['app']->ACL->NewKey('/ACL/gadgets/Users/ManageUserACLs',         'false');
            $GLOBALS['app']->ACL->NewKey('/ACL/gadgets/Users/ManageGroupACLs',        'false');
            $GLOBALS['app']->ACL->NewKey('/ACL/gadgets/Users/EditAccountInformation', 'false');
            $GLOBALS['app']->ACL->DeleteKey('/ACL/gadgets/Users/ManageACL');
			// Events
	        $GLOBALS['app']->Shouter->NewShouter('Core', 'onAddUser');             // trigger an action when we add an user
	        $GLOBALS['app']->Shouter->NewShouter('Core', 'onDeleteUser');          // trigger an action when we delete an user
	        $GLOBALS['app']->Shouter->NewShouter('Core', 'onUpdateUser');          // and when we update a user..
	        $GLOBALS['app']->Shouter->NewShouter('Core', 'onAddGroup');            // and also when we add a group
	        $GLOBALS['app']->Shouter->NewShouter('Core', 'onDeleteGroup');         // and also when we delete a group
	        $GLOBALS['app']->Shouter->NewShouter('Core', 'onUpdateGroup');         // and when we update a group..
	        $GLOBALS['app']->Shouter->NewShouter('Core', 'onAddComment');          
	        $GLOBALS['app']->Shouter->NewShouter('Core', 'onDeleteComment');         
	        $GLOBALS['app']->Shouter->NewShouter('Core', 'onUpdateComment');         
	        $GLOBALS['app']->Shouter->NewShouter('Core', 'onAddUserToGroup');
	        $GLOBALS['app']->Shouter->NewShouter('Core', 'onDeleteUserFromGroup');
	        $GLOBALS['app']->Shouter->NewShouter('Core', 'onAddUserToFriend');
	        $GLOBALS['app']->Shouter->NewShouter('Core', 'onDeleteUserFromFriend');
	        $GLOBALS['app']->Shouter->NewShouter('Core', 'onFriendGroup');
	        $GLOBALS['app']->Shouter->NewShouter('Core', 'onDefriendGroup');
	        $GLOBALS['app']->Shouter->NewShouter('Core', 'onLoadAccountNews');
	        $GLOBALS['app']->Shouter->NewShouter('Core', 'onBeforeLoadHomepage');
			$GLOBALS['app']->Shouter->NewShouter('Core', 'onBeforeLoadGroupPage');
	        $GLOBALS['app']->Shouter->NewShouter('Core', 'onBeforeLoadAddUpdate');
	
	        $GLOBALS['app']->Listener->NewListener('Users', 'onAfterEnablingGadget', 'UpdateAllUsersGadgets');
	        $GLOBALS['app']->Listener->NewListener('Users', 'onAfterDisablingGadget', 'DeleteAllUsersGadgets');
			$GLOBALS['app']->Listener->NewListener('Users', 'onAddUserToGroup', 'InsertGroupsFriends');
	        $GLOBALS['app']->Listener->NewListener('Users', 'onDeleteUser', 'RemoveUsersGadgets');
	        $GLOBALS['app']->Listener->NewListener('Users', 'onDeleteUser', 'RemoveUsersFriends');
	        $GLOBALS['app']->Listener->NewListener('Users', 'onDeleteUser', 'RemoveUsersComments');
	        $GLOBALS['app']->Listener->NewListener('Users', 'onUpdateUser', 'UpdateUsersGadgets');
	        $GLOBALS['app']->Listener->NewListener('Users', 'onAfterEnablingGadget', 'InsertDefaultChecksums');
	        
	        $GLOBALS['app']->Registry->NewKey('/gadgets/Users/recentcomments_limit', 10);
			$GLOBALS['app']->Registry->NewKey('/gadgets/Users/protected_pages', '');
			$GLOBALS['app']->Registry->NewKey('/gadgets/Users/social_sign_in', 'true');
			$GLOBALS['app']->Registry->NewKey('/gadgets/Users/signup_requires_address', 'false');
        }

        if (version_compare($old, '0.8.5', '<')) {
            $result = $this->installSchema('0.8.5.xml', '', '0.8.4.xml');
            if (Jaws_Error::IsError($result)) {
                return $result;
            }
        }

        if (version_compare($old, '0.8.6', '<')) {
            $result = $this->installSchema('0.8.6.xml', '', '0.8.5.xml');
            if (Jaws_Error::IsError($result)) {
                return $result;
            }

            $GLOBALS['app']->ACL->NewKey('/ACL/gadgets/Users/EditAccountPassword',    'false');
            $GLOBALS['app']->ACL->NewKey('/ACL/gadgets/Users/EditAccountProfile',     'false');
            $GLOBALS['app']->ACL->NewKey('/ACL/gadgets/Users/EditAccountPreferences', 'false');
			require_once JAWS_PATH . 'include/Jaws/User.php';
			$userModel = new Jaws_User;

			$groups = $userModel->GetAllGroups();
			if (!Jaws_Error::IsError($groups)) {
				foreach ($groups as $group) {
					if (empty($group['title'])) {
						$userModel->UpdateGroup($group['id'],
												$group['name'],
												$group['name'],
												$group['description']);
					}
				}
			}
        }
		
        if (version_compare($old, '0.8.7', '<')) {
            $result = $this->installSchema('schema.xml', '', '0.8.6.xml');
            if (Jaws_Error::IsError($result)) {
                return $result;
            }
			$GLOBALS['app']->Shouter->NewShouter('Core', 'onAddCommentSubscription');
			$GLOBALS['app']->Shouter->NewShouter('Core', 'onUpdateCommentSubscription');
			$GLOBALS['app']->Shouter->NewShouter('Core', 'onDeleteCommentSubscription');
			$GLOBALS['app']->Shouter->NewShouter('Core', 'onBeforeLoadAccountPublic');
	        $GLOBALS['app']->Listener->NewListener('Users', 'onLoadAccountNews', 'ShowUsersRequests');
        }
        
		return true;
    }

    /**
     * Get ACL permissions of a given user
     *
     * @access  public
     * @param   string  $username  Username
     * @return  array   Array with ACL Keys
     */
    function GetUserACLKeys($username)
    {
        $acls = $GLOBALS['app']->ACL->GetAclPermissions($username, false);
        $perms = array();
        if (is_array($acls)) {
            foreach ($acls as $gadget => $keys) {
                $g = $GLOBALS['app']->LoadGadget($gadget, 'Info');
                if (Jaws_Error::IsError($g)) {
                    continue;
                }

                if (!Jaws_Gadget::IsGadgetUpdated($gadget)) {
                    continue;
                }

                if (!isset($perms[$gadget])) {
                    $perms[$gadget] = array();
                    $perms[$gadget]['name'] = _t(strtoupper($gadget).'_NAME');
                }

                foreach ($keys as $k) {
                    $aclkey = '/ACL'.str_replace('/ACL/users/'.$username, '', $k['name']);

                    $perms[$gadget][$aclkey] = array(
                        'desc'  => $g->GetACLDescription($aclkey),
                        'value' => $k['value'],
                        'name'  => $k['name'],
                    );
                }
            }

            ksort($perms);
            return $perms;
        }

        return false;
    }

    /**
     * Returns an array with the ACL keys of a given group
     *
     * @access  public
     * @param   int     $guid   Group's ID
     * @return  array   Array with ACL Keys
     */
    function GetGroupACLKeys($guid)
    {
        $acls = $GLOBALS['app']->ACL->GetGroupAclPermissions($guid);
        $perms = array();
        if (is_array($acls)) {
            foreach ($acls as $gadget => $keys) {
                if (!Jaws_Gadget::IsGadgetUpdated($gadget)) {
                    continue;
                }

                $g = $GLOBALS['app']->LoadGadget($gadget, 'Info');
                if (Jaws_Error::IsError($g)) {
                    continue;
                }

                if (!isset($perms[$gadget])) {
                    $perms[$gadget] = array();
                    $perms[$gadget]['name'] = _t(strtoupper($gadget).'_NAME');
                }

                foreach ($keys as $k) {
                    $aclkey = '/ACL'.str_replace('/ACL/groups/'.$guid, '', $k['name']);
                    $perms[$gadget][$aclkey] = array(
                        'desc'  => $g->GetACLDescription($aclkey),
                        'value' => $k['value'],
                        'name'  => $k['name'],
                    );
                }
            }

            ksort($perms);
            return $perms;
        }

        return false;
    }

    /**
     * Saves only the modified ACL user keys
     *
     * @access  public
     * @param   int     $uid    User' ID
     * @param   array   $keys   ACL Keys
     * @return  boolean Success/Failure(Jaws_Error)
     */
    function UpdateUserACL($uid, $keys, $check = true)
    {
        require_once JAWS_PATH . 'include/Jaws/User.php';
        $userModel = new Jaws_User();
        $currentUser = $GLOBALS['app']->Session->GetAttribute('user_id');
        if ($user = $userModel->GetUserInfoById($uid, true)) {
            //Load user keys
            $GLOBALS['app']->ACL->LoadAllFiles();
            $GLOBALS['app']->ACL->LoadKeysOf($user['username'], 'users');
            foreach($keys as $key => $value) {
				//check user permission for this key
                if ($check) {
					$expkey = explode('/', $key);
					$aclkey = end($expkey);
					$gadget = prev($expkey);
					if (!$GLOBALS['app']->Session->GetPermission($gadget, $aclkey)) {
						continue;
					}
				}
				
                //Get the current value
                if ($key == '/ACL/users/' . $user['username'] . '/gadgets/ControlPanel/default' &&
                    $value === false && $uid == $currentUser)
                {
                    return new Jaws_Error(_t('USERS_USERS_CANT_AUTO_TURN_OFF_CP'), _t('USERS_NAME'));
                }

                $valueString  = $value === true  ? 'true' : 'false';
                $currentValue = $GLOBALS['app']->ACL->Get($key);
                if ($currentValue === null) {
                    $GLOBALS['app']->ACL->NewKey($key, $valueString);
                } else {
                    if (is_bool($currentValue)) {
                        $currentValue = ($currentValue === true) ? 'true' : 'false';
                    }
                    if ($currentValue != $valueString) {
                        $GLOBALS['app']->ACL->Set($key, $valueString);
                    }
                }
            }
            return true;
        }

        return new Jaws_Error(_t('USERS_USER_NOT_EXIST'), _t('USERS_NAME'));
    }

    /**
     * Resets all ACL keys assigned to an user
     *
     * @access  public
     * @param   int     $uid    User' ID
     * @return  boolean Success/Failure(Jaws_Error)
     */
    function ResetUserACL($uid)
    {
        require_once JAWS_PATH . 'include/Jaws/User.php';
        $userModel = new Jaws_User();
        if ($user = $userModel->GetUserInfoById($uid, true)) {
            $GLOBALS['app']->ACL->DeleteUserACL($user['username']);
            return true;
        }
        return new Jaws_Error(_t('USERS_USER_NOT_EXIST'), _t('USERS_NAME'));
    }

    /**
     * Saves only the modified ACL group keys
     *
     * @access  public
     * @param   int     $guid   Group ID
     * @param   array   $keys   ACL Keys
     */
    function UpdateGroupACL($guid, $keys)
    {
        require_once JAWS_PATH . 'include/Jaws/User.php';
        $userModel = new Jaws_User();

        if ($group = $userModel->GetGroupInfoById($guid)) {
            $GLOBALS['app']->ACL->LoadAllFiles();
            $GLOBALS['app']->ACL->LoadKeysOf($guid, 'groups');
			foreach ($keys as $key => $value) {
                $valueString  = $value === true  ? 'true' : 'false';
                $currentValue = $GLOBALS['app']->ACL->Get($key);
				if ($currentValue === null) {
                    $newKey = $GLOBALS['app']->ACL->NewKey($key, $valueString);
                } else {
                    if (is_bool($currentValue)) {
                        $currentValue = ($currentValue === true) ? 'true' : 'false';
                    }

                    if ($currentValue != $valueString) {
                        $newKey = $GLOBALS['app']->ACL->Set($key, $valueString);
                    }
                }
			}
            return true;
        }
        return new Jaws_Error(_t('USERS_GROUPS_GROUP_NOT_EXIST'), _t('USERS_NAME'));
    }

    /**
     * Resets all ACL keys assigned to a group
     *
     * @access  public
     * @param   int     $guid   Group ID
     * @return  array   Response (notice or error)
     */
    function ResetGroupACL($guid)
    {
        require_once JAWS_PATH . 'include/Jaws/User.php';
        $userModel = new Jaws_User();

        if ($group = $userModel->GetGroupInfoById($guid)) {
            $GLOBALS['app']->ACL->DeleteGroupACL($guid);
            return true;
        }
        return new Jaws_Error(_t('USERS_GROUPS_GROUP_NOT_EXIST'), _t('USERS_NAME'));
    }

    /**
     * Save user config settings
     *
     * @access  public
     * @param   string  $priority   Priority
     * @param   string  $method     Authentication method
     * @param   string  $anon       Anonymous users can auto-register
     * @param   string  $repetitive Anonymous can register by repetitive email
     * @param   string  $act        Activation type
     * @param   integer $type       User's type
     * @param   integer $group      Default group of anonymous registered user
     * @param   string  $recover    Users can recover their passwords
     * @return  boolean Success/Failure
     */
    function SaveSettings($priority, $method, $anon, $repetitive, $act, $type, $group, $recover, 
	$gadgets, $protected_pages, $signup_requires_address, $social_sign_in)
    {
        $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');

        $priority   				= $xss->parse($priority);
        $method     				= $xss->parse($method);
        $anon       				= $xss->parse($anon);
        $repetitive 				= $xss->parse($repetitive);
        $recover    				= $xss->parse($recover);
        $signup_requires_address  	= $xss->parse($signup_requires_address);
        $social_sign_in  			= $xss->parse($social_sign_in);

        $res = true;
        $methods = Jaws::getAuthMethods();
        if ($methods !== false && in_array($method, $methods)) {
            $res = $GLOBALS['app']->Registry->Set('/config/auth_method', $method);
        }
		
		$str2 = ""; 
		$comma2 = "";
        foreach ($group as $Key => $Value) {
			$str2 .= $comma2.$Value;
			$comma2=",";
        }

		$str1 = ""; 
		$comma1 = "";
        foreach ($gadgets as $Key => $Value) {
			$str1 .= $comma1.$Value;
			$comma1=",";
        }

		$str = ""; 
		$comma = "";
        foreach ($protected_pages as $Key => $Value) {
			$str .= $comma.$Value;
			$comma=",";
        }
        
		$res = $res && $GLOBALS['app']->ACL->Set('/priority', $priority);
        $res = $res && $GLOBALS['app']->Registry->Set('/config/anon_register', $anon);
        $res = $res && $GLOBALS['app']->Registry->Set('/config/anon_repetitive_email', $repetitive);
        $res = $res && $GLOBALS['app']->Registry->Set('/config/anon_activation', $act);
        $res = $res && $GLOBALS['app']->Registry->Set('/config/anon_type',  (int)$type);
        $res = $res && $GLOBALS['app']->Registry->Set('/config/anon_group', $str2);
        $res = $res && $GLOBALS['app']->Registry->Set('/gadgets/Users/password_recovery', $recover);
        $res = $res && $GLOBALS['app']->Registry->Set("/gadgets/Users/protected_pages",$str);
        $res = $res && $GLOBALS['app']->Registry->Set('/gadgets/Users/signup_requires_address', $signup_requires_address);
        $res = $res && $GLOBALS['app']->Registry->Set('/gadgets/Users/social_sign_in', $social_sign_in);
        $res = $res && $GLOBALS['app']->Registry->Set("/gadgets/user_access_items",$str1);
        if ($res) {
            $GLOBALS['app']->Registry->Commit('Users');
            $GLOBALS['app']->Registry->Commit('core');
            $GLOBALS['app']->ACL->Commit('core');
            return true;
        }

        return new Jaws_Error(_t('USERS_PROPERTIES_CANT_UPDATE'), _t('USERS_NAME'));
    }

    /**
     * Search for properties that match multiple queries
     * in the title or content and return array of given key
     *
     * @access  public
     * @access  public
     * @param   string  $status  Status of page(s) we want to display
     * @param   string  $search  Keyword (title/description) of pages we want to look for
     * @param   int     $offSet  Data limit
     * @return  array   Array of matches
     */
    function SearchKeyWithUsers($search, $only_titles = false, $sortColumn = 'nickname', $sortDir = 'ASC', $return = 'nickname', $links = 'N')
    {
        $return = strtolower($return);
        $fields = array('nickname');
        //$sortColumn = strtolower($sortColumn);
        if (!in_array($sortColumn, $fields)) {
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_WARNING, _t('STORE_ERROR_UNKNOWN_COLUMN'));
            }
            $sortColumn = 'nickname';
        }
		
        $sortDir = strtoupper($sortDir);
        if ($sortDir == 'DESC') {
            $sortDir = 'DESC';
        } else {
            $sortDir = 'ASC';
        }
        
		$exact = array();
		$results = array();
		$result = array();
        require_once JAWS_PATH . 'include/Jaws/User.php';
        $model = new Jaws_User();
		//$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$users = $model->GetUsers(false, false, true, $sortColumn);
		if (Jaws_Error::IsError($users)) {
			return new Jaws_Error($users->GetMessage(), _t('USERS_NAME'));
		}
		
		$keys_found = array();
		$stop_words = array(
			"&",
			"&amp;",
			"a",
			"able",
			"about",
			"above",
			"abroad",
			"according",
			"accordingly",
			"across",
			"actually",
			"adj",
			"after",
			"afterwards",
			"again",
			"against",
			"ago",
			"ahead",
			"ain't",
			"all",
			"allow",
			"allows",
			"almost",
			"alone",
			"along",
			"alongside",
			"already",
			"also",
			"although",
			"always",
			"am",
			"amid",
			"amidst",
			"among",
			"amongst",
			"an",
			"and",
			"another",
			"any",
			"anybody",
			"anyhow",
			"anyone",
			"anything",
			"anyway",
			"anyways",
			"anywhere",
			"apart",
			"appear",
			"appreciate",
			"appropriate",
			"are",
			"aren't",
			"around",
			"as",
			"a's",
			"aside",
			"ask",
			"asking",
			"associated",
			"at",
			"available",
			"away",
			"awfully",
			"b",
			"back",
			"backward",
			"backwards",
			"be",
			"became",
			"because",
			"become",
			"becomes",
			"becoming",
			"been",
			"before",
			"beforehand",
			"begin",
			"behind",
			"being",
			"believe",
			"below",
			"beside",
			"besides",
			"best",
			"better",
			"between",
			"beyond",
			"both",
			"brief",
			"but",
			"by",
			"c",
			"came",
			"can",
			"cannot",
			"cant",
			"can't",
			"caption",
			"cause",
			"causes",
			"certain",
			"certainly",
			"changes",
			"clearly",
			"c'mon",
			"co",
			"co.",
			"com",
			"come",
			"comes",
			"concerning",
			"consequently",
			"consider",
			"considering",
			"contain",
			"containing",
			"contains",
			"corresponding",
			"could",
			"couldn't",
			"course",
			"c's",
			"currently",
			"d",
			"dare",
			"daren't",
			"definitely",
			"described",
			"despite",
			"did",
			"didn't",
			"different",
			"directly",
			"do",
			"does",
			"doesn't",
			"doing",
			"done",
			"don't",
			"down",
			"downwards",
			"during",
			"e",
			"each",
			"edu",
			"eg",
			"eight",
			"eighty",
			"either",
			"else",
			"elsewhere",
			"end",
			"ending",
			"enough",
			"entirely",
			"especially",
			"et",
			"etc",
			"even",
			"ever",
			"evermore",
			"every",
			"everybody",
			"everyone",
			"everything",
			"everywhere",
			"ex",
			"exactly",
			"except",
			"f",
			"fairly",
			"far",
			"farther",
			"few",
			"fewer",
			"fifth",
			"first",
			"five",
			"followed",
			"following",
			"follows",
			"for",
			"forever",
			"former",
			"formerly",
			"forth",
			"forward",
			"found",
			"four",
			"from",
			"further",
			"furthermore",
			"g",
			"get",
			"gets",
			"getting",
			"given",
			"gives",
			"go",
			"goes",
			"going",
			"gone",
			"got",
			"gotten",
			"greetings",
			"h",
			"had",
			"hadn't",
			"half",
			"happens",
			"hardly",
			"has",
			"hasn't",
			"have",
			"haven't",
			"having",
			"he",
			"he'd",
			"he'll",
			"hello",
			"help",
			"hence",
			"her",
			"here",
			"hereafter",
			"hereby",
			"herein",
			"here's",
			"hereupon",
			"hers",
			"herself",
			"he's",
			"hi",
			"him",
			"himself",
			"his",
			"hither",
			"hopefully",
			"how",
			"howbeit",
			"however",
			"hundred",
			"i",
			"i'd",
			"ie",
			"if",
			"ignored",
			"i'll",
			"i'm",
			"immediate",
			"in",
			"inasmuch",
			"inc",
			"inc.",
			"indeed",
			"indicate",
			"indicated",
			"indicates",
			"info",
			"inner",
			"inside",
			"insofar",
			"instead",
			"into",
			"inward",
			"is",
			"isn't",
			"it",
			"it'd",
			"it'll",
			"its",
			"it's",
			"itself",
			"i've",
			"j",
			"just",
			"k",
			"keep",
			"keeps",
			"kept",
			"know",
			"known",
			"knows",
			"l",
			"last",
			"lately",
			"later",
			"latter",
			"latterly",
			"least",
			"less",
			"lest",
			"let",
			"let's",
			"like",
			"liked",
			"likely",
			"likewise",
			"little",
			"look",
			"looking",
			"looks",
			"low",
			"lower",
			"ltd",
			"m",
			"made",
			"mainly",
			"make",
			"makes",
			"many",
			"may",
			"maybe",
			"mayn't",
			"me",
			"mean",
			"meantime",
			"meanwhile",
			"merely",
			"might",
			"mightn't",
			"mine",
			"minus",
			"miss",
			"more",
			"moreover",
			"most",
			"mostly",
			"mr",
			"mrs",
			"much",
			"must",
			"mustn't",
			"my",
			"myself",
			"n",
			"name",
			"namely",
			"nd",
			"near",
			"nearly",
			"necessary",
			"need",
			"needn't",
			"needs",
			"neither",
			"never",
			"neverf",
			"neverless",
			"nevertheless",
			"new",
			"next",
			"nine",
			"ninety",
			"no",
			"nobody",
			"non",
			"none",
			"nonetheless",
			"noone",
			"no-one",
			"nor",
			"normally",
			"not",
			"nothing",
			"notwithstanding",
			"now",
			"nowhere",
			"o",
			"obviously",
			"of",
			"off",
			"often",
			"oh",
			"ok",
			"okay",
			"old",
			"on",
			"once",
			"one",
			"ones",
			"one's",
			"only",
			"onto",
			"opposite",
			"or",
			"other",
			"others",
			"otherwise",
			"ought",
			"oughtn't",
			"our",
			"ours",
			"ourselves",
			"out",
			"outside",
			"over",
			"overall",
			"own",
			"p",
			"particular",
			"particularly",
			"past",
			"per",
			"perhaps",
			"placed",
			"please",
			"plus",
			"possible",
			"presumably",
			"probably",
			"provided",
			"provides",
			"q",
			"que",
			"quite",
			"qv",
			"r",
			"rather",
			"rd",
			"re",
			"really",
			"reasonably",
			"recent",
			"recently",
			"regarding",
			"regardless",
			"regards",
			"relatively",
			"respectively",
			"right",
			"round",
			"s",
			"said",
			"same",
			"saw",
			"say",
			"saying",
			"says",
			"second",
			"secondly",
			"see",
			"seeing",
			"seem",
			"seemed",
			"seeming",
			"seems",
			"seen",
			"self",
			"selves",
			"sensible",
			"sent",
			"serious",
			"seriously",
			"seven",
			"several",
			"shall",
			"shan't",
			"she",
			"she'd",
			"she'll",
			"she's",
			"should",
			"shouldn't",
			"since",
			"six",
			"so",
			"some",
			"somebody",
			"someday",
			"somehow",
			"someone",
			"something",
			"sometime",
			"sometimes",
			"somewhat",
			"somewhere",
			"soon",
			"sorry",
			"specified",
			"specify",
			"specifying",
			"still",
			"sub",
			"such",
			"sup",
			"sure",
			"t",
			"taking",
			"tell",
			"tends",
			"th",
			"than",
			"thank",
			"thanks",
			"thanx",
			"that",
			"that'll",
			"thats",
			"that's",
			"that've",
			"the",
			"their",
			"theirs",
			"them",
			"themselves",
			"then",
			"thence",
			"there",
			"thereafter",
			"thereby",
			"there'd",
			"therefore",
			"therein",
			"there'll",
			"there're",
			"theres",
			"there's",
			"thereupon",
			"there've",
			"these",
			"they",
			"they'd",
			"they'll",
			"they're",
			"they've",
			"thing",
			"things",
			"think",
			"third",
			"thirty",
			"this",
			"thorough",
			"thoroughly",
			"those",
			"though",
			"three",
			"through",
			"throughout",
			"thru",
			"thus",
			"till",
			"to",
			"together",
			"too",
			"took",
			"toward",
			"towards",
			"tried",
			"tries",
			"truly",
			"try",
			"trying",
			"t's",
			"twice",
			"two",
			"u",
			"un",
			"under",
			"underneath",
			"undoing",
			"unfortunately",
			"unless",
			"unlike",
			"unlikely",
			"until",
			"unto",
			"up",
			"upon",
			"upwards",
			"us",
			"use",
			"used",
			"useful",
			"uses",
			"using",
			"usually",
			"v",
			"value",
			"various",
			"very",
			"via",
			"viz",
			"vs",
			"w",
			"want",
			"wants",
			"was",
			"wasn't",
			"way",
			"we",
			"we'd",
			"welcome",
			"well",
			"we'll",
			"went",
			"were",
			"we're",
			"weren't",
			"we've",
			"what",
			"whatever",
			"what'll",
			"what's",
			"what've",
			"when",
			"whence",
			"whenever",
			"where",
			"whereafter",
			"whereas",
			"whereby",
			"wherein",
			"where's",
			"whereupon",
			"wherever",
			"whether",
			"which",
			"whichever",
			"while",
			"whilst",
			"whither",
			"who",
			"who'd",
			"whoever",
			"whole",
			"who'll",
			"whom",
			"whomever",
			"who's",
			"whose",
			"why",
			"will",
			"willing",
			"wish",
			"with",
			"within",
			"without",
			"wonder",
			"won't",
			"would",
			"wouldn't",
			"x",
			"y",
			"yes",
			"yet",
			"you",
			"you'd",
			"you'll",
			"your",
			"you're",
			"yours",
			"yourself",
			"yourselves",
			"you've",
			"z",
		);
		foreach ($users as $user) {
			//echo '<br />Search: '.$search.' status: '.$status.' bedroom: '.$bedroom.' bathroom: '.$bathroom.' cat: '.$category.' community: '.$community;
			//echo '<br />'.$user['nickname'];
			$in_title = false;
			$add_user = true;
			$groups  = $model->GetGroupsOfUser($user['id']);
			// Check if user is in profile group
			$show_link = false;
			if (!Jaws_Error::IsError($groups)) {
				foreach ($groups as $group) {
					if (
						(strtolower($group['group_name']) == 'profile' || strtolower($group['group_name']) == 'no_profile') && 
						in_array($group['group_status'], array('active','founder','admin'))
					) {
						$show_link = true;
						break;
					}
				}
			}
			
			if ($show_link === true && $user['enabled'] === true) {
				if (
					trim($search) != '' && 
					(strpos(strtolower($user['nickname']), strtolower(trim($search))) !== false || 
					strpos(strtolower($user['company']), strtolower(trim($search))) !== false || 
					strpos(strtolower($user['keywords']), strtolower(trim($search))) !== false)
				) {
					if (!empty($user['company'])) {
						$user['nickname'] = $user['company'];
					}
					$searchdata = explode(' ', $user['nickname']);
					foreach ($searchdata as $v) {
						if (!in_array(strtolower($v), $stop_words)) {
							$newstring = "";
							$array = str_split($v);
							foreach($array as $char) {
								if ((strtoupper($char) >= 'A' && strtoupper($char) <= 'Z')) {
									$newstring .= $char;
								} else {
									break;
								}
							}
							if (substr(strtolower($newstring), 0, strlen(strtolower($search))) == strtolower($search) && !in_array(strtolower((string)$user[$return]), $keys_found)) {
								$keys_found[] = strtolower((string)$user[$return]);
								if ($links == 'Y') {
									$exact[] = array('<a href="'.$GLOBALS['app']->getSiteURL().'/index.php?gadget=Users&action=UserDirectory&gid=all&q='.ucfirst(strtolower($newstring)).'">'.$user[$return].'</a>');
								} else {
									$exact[] = array($user[$return]);
								}
							} else {
								$add_user = false;
							}
						} else {
							$add_user = false;
						}
					}
				} else {
					$add_user = false;
				}
			} else {
				$add_user = false;
			}
			if ($add_user === true) {
				// Make sure this key is only added once
				if (!in_array(strtolower((string)$user[$return]), $keys_found) || count($keys_found) <= 0) {
					$results[] = array($user[$return]);
					$keys_found[] = strtolower((string)$user[$return]);
					//echo 'RETURN: '.$user[$return];
				}
			}
		}
		
		foreach($exact as $ex){
			$result[] = $ex;
		}
		foreach($results as $res){
			$result[] = $res;
		}
		///*
		if (count($result)) {
			// Sort result array
			$subkey = $sortColumn; 
			$temp_array = array();
			
			$temp_array[key($result)] = array_shift($result);

			foreach($result as $key => $val) {
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

			$result = array_reverse($temp_array);
		}
		return $result;
    }

    /**
     * Add a group of user (by they ids) to a certain group
     *
     * @access  public
     * @param   int     $guid  Group's ID
     * @param   array   $users Array with user id
     * @return  array   Response (notice or error)
     */
    function AddUsersToGroup($guid, $users)
    {
        require_once JAWS_PATH . 'include/Jaws/User.php';
        $userModel = new Jaws_User();

        $group = $userModel->GetGroupInfoById($guid);
        if (!$group) {
            return new Jaws_Error(_t('USERS_GROUPS_GROUP_NOT_EXIST'), _t('USERS_NAME'));
        }

        $postedUsers = array();
        foreach ($users as $k => $v) {
            $postedUsers[$v] = $v;
        }

        $list = $userModel->GetUsers();
        foreach ($list as $user) {
            if ($userModel->UserIsInGroup($user['id'], $guid)) {
                if (!isset($postedUsers[$user['id']])) {
                    if (!$GLOBALS['app']->Session->IsSuperAdmin() && $user['user_type'] == 0) {
                        continue;
                    }
                    $userModel->DeleteUserFromGroup($user['id'], $guid);
                }
            } else {
                if (isset($postedUsers[$user['id']])) {
                    $userModel->AddUserToGroup($user['id'], $guid);

                }
            }
        }
        return true;
    }
		
    /**
     * Removes Users-Gadgets association from db when a user is deleted
     *
     * @access  public
     * @param   string  $id user id
     * @return  boolean Returns true if pair was successfully unassociated, error if not
     */
    function RemoveUsersGadgets($id)
    {
		require_once JAWS_PATH . 'include/Jaws/User.php';
		$userModel = new Jaws_User();
        $result = $userModel->RemoveUsersGadgets($id);
		if (!$result) {
			return false;
		} else {
			return true;
		}
    }

	/**
     * Updates Users-Gadgets association from db when a user is updated
     *
     * @access  public
     * @param   int  $id user id
     * @param   string  $gadget name of gadget to update
     * @param   string  $pane_action 'minimize' or 'maximize' the pane
     * @return  boolean Returns true if pair was successfully updated, error if not
     */
    function UpdateUsersGadgets($id)
    {
		require_once JAWS_PATH . 'include/Jaws/User.php';
		$userModel = new Jaws_User();
        $result = $userModel->UpdateUsersGadgets($id);
		if (!$result) {
			return false;
		} else {
			return true;
		}
		
	}
	
	
	/**
     * Updates All Users-Gadgets associations when a gadget is updated
     *
     * @access  public
     * @param   string  $gadget name of gadget to update
     * @param   string  $pane_action 'minimize' or 'maximize' the pane
     * @param   string  $pane_method (eg UserCustomPageSubscriptions())
     * @return  boolean Returns sort_order of last pane if users_gadget was successfully updated, error if not
     */
    function UpdateAllUsersGadgets($gadget, $pane_action = 'maximized', $pane_method = null)
    {
		require_once JAWS_PATH . 'include/Jaws/User.php';
		$jawsUser = new Jaws_User();
		$users = $jawsUser->GetUsers();
		if (Jaws_Error::IsError($users)) {
			return $users;
		} else {
			$params             	= array();
			if (isset($gadget) && trim($gadget) != '') {
				$params['gadget_name']  = $gadget;
			}
			
			// Get all users
			foreach ($users as $info) {
				$pane_found = false;
				if ($pane_action == 'maximized' || $pane_action == 'minimized') {
					$params['status']  = $pane_action;
				} else {
					$params['status']  = 'maximized';
				}
				// TODO: Move GetGadgetPaneInfoByUserID() to User.php
				$userModel = $GLOBALS['app']->LoadGadget('Users', 'Model');
				$pane_info = $userModel->GetGadgetPaneInfoByUserID($gadget, $info['id']);
				if (Jaws_Error::IsError($pane_info)) {
					return $pane_info;
				} else {
					if (isset($pane_info['gadget_name'])) {
						$pane_found = true;
					}
				}
				$params['user_id'] 		= $info['id'];
				$params['enabled'] 		= true;
				if ($pane_found === false) {
					if (!is_null($pane_method) && trim($pane_method) != '') {
						$params['pane']  = $pane_method;
						// send highest sort_order
						$sql = "SELECT MAX([sort_order]) FROM [[users_gadgets]] WHERE ([user_id] = {user_id}) ORDER BY [sort_order] DESC";
						$max = $GLOBALS['db']->queryOne($sql, array('user_id' => $params['user_id']), array('integer'));
						$params['sort_order']   = 0;
						if (Jaws_Error::IsError($max)) {
							return $max;
						} else if (is_numeric($max)) {
							$params['sort_order']   = $max;
						}
						$params['now'] = $GLOBALS['db']->Date();
						$sql = '
							INSERT INTO [[users_gadgets]]
								([user_id], [gadget_name], [pane],
								[enabled], [status], [sort_order], 
								[created], [updated], [owner_id])
							VALUES
								({user_id}, {gadget_name}, {pane}, 
								{enabled}, {status}, {sort_order}, 
								{now}, {now}, 0)';
						$result = $GLOBALS['db']->query($sql, $params);
						if (Jaws_Error::IsError($result)) {
							return $result;
						}
					} else {
						// send highest sort_order
						$sql = "SELECT MAX([sort_order]) FROM [[users_gadgets]] WHERE ([user_id] = {user_id}) ORDER BY [sort_order] DESC";
						$max = $GLOBALS['db']->queryOne($sql, array('user_id' => $params['user_id']), array('integer'));
						$i = 0;
						if (Jaws_Error::IsError($max)) {
							return $max;
						} else if (is_numeric($max)) {
							$i = $max;
						}
						if (file_exists(JAWS_PATH . '/gadgets/'.$gadget.'/HTML.php')) {
							$paneGadget = $GLOBALS['app']->LoadGadget($gadget, 'HTML');
							if (!Jaws_Error::IsError($paneGadget) && method_exists($paneGadget, 'GetUserAccountControls') && method_exists($paneGadget, 'GetUserAccountPanesInfo')) {
								$panes = $paneGadget->GetUserAccountPanesInfo();
								foreach ($panes as $pane_method => $pane_name) {
									$params['pane']  = $pane_method;
									$params['sort_order']  = $i;
									$params['now'] = $GLOBALS['db']->Date();
									$sql = '
										INSERT INTO [[users_gadgets]]
											([user_id], [gadget_name], [pane],
											[enabled], [status], [sort_order], 
											[created], [updated], [owner_id])
										VALUES
											({user_id}, {gadget_name}, {pane}, 
											{enabled}, {status}, {sort_order}, 
											{now}, {now}, 0)';
									$result = $GLOBALS['db']->query($sql, $params);
									if (Jaws_Error::IsError($result)) {
										return $result;
									}
									$i++;
								}
							} else if (Jaws_Error::IsError($paneGadget)) {
								return $paneGadget;
							//} else {
							//	return new Jaws_Error(_t('GLOBAL_ERROR_GET_ACCOUNT_PANE'), _t('USERS_NAME'));
							}
						}
					}						
				} else {
					$params['now'] = $GLOBALS['db']->Date();
					$sql = '
						UPDATE [[users_gadgets]] SET
							[enabled]     = {enabled},
							[updated]  = {now},
							[status]  = {status}
						WHERE ([user_id] = {user_id} AND [gadget_name] = {gadget_name})';
					$result = $GLOBALS['db']->query($sql, $params);
					if (Jaws_Error::IsError($result)) {
						return $result;
					}
				}
					
				// add "Own" or non-core "Full" permissions to ACL array
				$gadget_acls = array();
				$gInfo = $GLOBALS['app']->LoadGadget($gadget, 'Info');
				$acl_keys = $gInfo->GetACLs();
				$core = $gInfo->GetAttribute('core_gadget');
				if (!count($acl_keys) <= 0) {
					reset($acl_keys);
					foreach ($acl_keys as $acl_key => $acl_val) {
						$key_name = strrchr($acl_key, "/");
						if (((!$core || is_null($core)) && $info['user_type'] < 2) || ($info['user_type'] < 2 && ($gadget == 'ControlPanel' || $gadget == 'Users'))) {
							if (!in_array($gadget.$key_name, $gadget_acls, TRUE)) {
								//echo '<br />FOUND: '.$gadget.$key_name.' = true';
								array_push($gadget_acls, $gadget.$key_name);
							}
						} else if (strpos($key_name,"Own") !== false) {
							if (!in_array($gadget.$key_name, $gadget_acls, TRUE)) {
								//echo '<br />FOUND: '.$gadget.$key_name.' = true';
								array_push($gadget_acls, $gadget.$key_name);
							}
						}
					}
				}
				unset($gInfo);

				// grant gadget permissions based on ACL array
				$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');
				if (!count($gadget_acls) <= 0) {
					reset($gadget_acls);
					foreach ($gadget_acls as $gadget_acl) {
						//echo '<br />SET: /ACL/users/' . $info['username'] . '/gadgets/'.$gadget_acl.' = true';
						//echo '<br />SET: /ACL/groups/1/gadgets/'.$gadget_acl.' = true';
						$g_acl = $this->UpdateUserACL($info['id'], array('/ACL/users/' . $info['username'] . '/gadgets/'.$gadget_acl => true));
						//$group_acl = $this->UpdateGroupACL(1, array('/ACL/groups/1/gadgets/'.$gadget_acl => true));
						if (Jaws_Error::IsError($g_acl)) {
							return $g_acl;
						}
					}
				}
			}
		}
	}

	/**
     * Deletes All Users-Gadgets associations when a gadget is deleted
     *
     * @access  public
     * @param   string  $gadget name of gadget to update
     * @return  boolean Returns sort_order of last pane if users_gadget was successfully updated, error if not
     */
    function DeleteAllUsersGadgets($gadget)
    {
		require_once JAWS_PATH . 'include/Jaws/User.php';
		$jawsUser = new Jaws_User();
		$users = $jawsUser->GetUsers();
		if (Jaws_Error::IsError($users)) {
			return $users;
		} else {
			MDB2::loadFile('Date');
			$params             	= array();
			if (isset($gadget) && trim($gadget) != '') {
				$params['gadget_name']  = $gadget;
			}
			
			// Delete all records for uninstalled gadget
			$sql = 'DELETE FROM [[users_gadgets]] WHERE ([gadget_name] = {gadget_name})';
			$result = $GLOBALS['db']->query($sql, $params);
			if (Jaws_Error::IsError($result)) {
				if ($insert === true) {
					return $result;
				} else {
					return false;
				}
			}
		}
	}
		
	/**
     * Search for users_gadgets that matches a status and/or a keyword
     * in the title or content
     *
     * @access  public
     * @access  public
     * @param   string  $status  Status of user(s) we want to display
     * @param   string  $search  Keyword (title/description) of users we want to look for
     * @param   int     $offSet  Data limit
     * @return  array   Array of matches
     */
    function SearchUsersGadgets($status, $search, $offSet = null)
    {
        $params = array();

		$params['status'] = $status;

        $sql = '
            SELECT [user_id], [gadget_name], [pane], [enabled], [status], [sort_order],
				[created], [updated], [owner_id]
            FROM [[users_gadgets]]
			';
	    
        if (trim($status) != '') {
            $sql .= ' WHERE ([status] = {status})';
        }
        
		if (trim($search) != '') {
            $searchdata = explode(' ', $search);
            /**
             * This query needs more work, not use $v straight, should be
             * like rest of the param stuff.
             */
            $i = 0;
            foreach ($searchdata as $v) {
                $v = trim($v);
                $sql .= " AND ([gadget_name] LIKE {textLike_".$i."} OR [pane] LIKE {textLike_".$i."} OR [status] LIKE {textLike_".$i."})";
                $params['textLike_'.$i] = '%'.$v.'%';
                $i++;
            }
        }

        $types = array(
			'integer', 'text', 'text', 'boolean', 'text', 'integer', 
			'timestamp', 'timestamp', 'integer'
		);

        if (is_numeric($offSet)) {
            $limit = 10;
            $result = $GLOBALS['db']->setLimit(10, $offSet);
            if (Jaws_Error::IsError($result)) {
                return new Jaws_Error(_t('USERS_ERROR_USERSGADGETS_NOT_RETRIEVED'), _t('USERS_NAME'));
            }
        }

        $sql.= ' ORDER BY [user_id] ASC';

        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('USERS_ERROR_USERSGADGETS_NOT_RETRIEVED'), _t('USERS_NAME'));
        }
        //limit, sort, sortDirection, offset..
        return $result;
    }

    /**
     * Adds a URL to embed_gadgets.
     *
     * @access  public
     * @param   string $gadget  		The gadget type of content
     * @param   string $url  			The url that the gadget is embedded on
     * @param   string $gadget_url  		The url of the gadget
     * @param   integer $layout  		The layout mode of the post
     * @return  ID of entered post 	    Success/failure
     */
    function AddEmbedSite($gadget, $url, $gadget_url, $layout)
    {
		if (!$url) {
	        $GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_EMBED_ERROR_INVALID_URL'), RESPONSE_ERROR);
			return $GLOBALS['app']->Session->PopLastResponse();
		}

        $param = array();

		$param['url'] = $url;

        $sql = '
            SELECT [url], [gadget_url]
			FROM [[embed_gadgets]]
			WHERE ([url] = {url})';
        
        $types = array(
			'text'
		);

        $found = $GLOBALS['db']->queryAll($sql, $param, $types);
        if (Jaws_Error::IsError($found)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_EMBED_ERROR_NOT_ADDED'), RESPONSE_ERROR);
			return $GLOBALS['app']->Session->PopLastResponse();
        }
		  
		$params               		= array();
		$params['OwnerID']         	= $GLOBALS['app']->Session->GetAttribute('user_id');
		$params['layout'] 			= $layout;
		$params['gadget'] 			= $gadget;
		$params['url']				= $url;
		$params['gadget_url']		= str_replace('&amp;', '&', $gadget_url);
		$params['now']        		= $GLOBALS['db']->Date();

		if ($found) {
			foreach ($found as $f) {
		        if ($f['gadget_url'] == str_replace('&amp;', '&', $gadget_url)) {
					$sql = '
			            UPDATE [[embed_gadgets]] SET
			                [gadget] = {gadget},
			                [url] = {url},
			                [gadget_url] = {gadget_url},
			                [layout] = {layout},
			                [updated] = {now},
			            WHERE [id] = {id}';
				} else {
					$sql = "
			            INSERT INTO [[embed_gadgets]]
			                ([gadget], [url], [gadget_url], [layout], [ownerid], [created], [updated])
			            VALUES
			                ({gadget}, {url}, {gadget_url}, {layout}, {OwnerID}, {now}, {now})";
				}
			}
		} else {
			$sql = "
	            INSERT INTO [[embed_gadgets]]
	                ([gadget], [url], [gadget_url], [layout], [ownerid], [created], [updated])
	            VALUES
	                ({gadget}, {url}, {gadget_url}, {layout}, {OwnerID}, {now}, {now})";
		}
		$result = $GLOBALS['db']->query($sql, $params);
		if (Jaws_Error::IsError($result)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_EMBED_ERROR_NOT_ADDED'), RESPONSE_ERROR);
			return $GLOBALS['app']->Session->PopLastResponse();
		}
        $GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_EMBED_CREATED'), RESPONSE_NOTICE);
		return $GLOBALS['app']->Session->PopLastResponse();
    }

	/**
     * Deletes an embedded gadget
     *
     * @access  public
     * @param   string     $url     The url of the page it's embedded on.
     * @param   string     $gadget_url     The gadget url of the embedded gadget.
     * @return  bool    Success/failure
     */
    function DeleteEmbed($url, $gadget_url)
    {
        $sql = 'DELETE FROM [[embed_gadgets]] WHERE ([url] = {url} AND [gadget_url] = {gadget_url} AND [ownerid] = {OwnerID})';
        $result = $GLOBALS['db']->query($sql, array('url' => $url, 'gadget_url' => $gadget_url, 'OwnerID' => $GLOBALS['app']->Session->GetAttribute('user_id')));
        if (Jaws_Error::IsError($result)) {
            return false;
        }
        return true;
    }

	/**
     * Imports Users
     *
     * @access  public
     * @param   string  $file  file containing users to import
     * @return  bool	true on success/false on error
     */
    function InsertUsers($file, $type, $num, $user_attended = 'N')
    {		
		ignore_user_abort(true); 
        set_time_limit(0);
		echo " ";
		ob_flush();
		flush();
        require_once JAWS_PATH . 'include/Jaws/User.php';
        $jUser = new Jaws_User;
		$model = $GLOBALS['app']->LoadGadget('Users', 'Model');
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
	
		if ($type == 'RSS') {
			//$result = array();
			$multifeed = false;
			if (trim($fetch_url) != '') {
				//echo '<br />RSS URL: '.$fetch_url;
				require_once(JAWS_PATH . 'libraries/magpierss-0.72/rss_fetch.inc');
				$rss = fetch_rss($fetch_url);
				if ($rss) {
					$real_rss_url = (trim($rss_url) != '' ? $rss_url : $fetch_url);
					if ($this->_propCount == 1) {
						echo '<br />&nbsp;<br />'.'<b>Now importing from: '.urldecode($real_rss_url).'</b>';
					}
					ob_flush();
					flush();
					//echo '<pre>';
					//var_dump($rss);
					//echo '</pre>';
					$this->_propTotal = count($rss->items);
					reset($rss->items);
					if ((isset($num) && !empty($num) || $num == 0) && $user_attended == 'Y') {
						if ($num <= $this->_propTotal) {
							sleep(1);
							echo " ";
							ob_flush();
							flush();
							$this->_propCount = ($num+1);
							$this->InsertRSSUsers($rss->items[$num], $real_rss_url);
							if ($user_attended == 'Y') {
								echo '<form name="user_rss_form" id="user_rss_form" action="index.php?gadget=Users&action=UpdateRSSUsers" method="POST">'."\n";
								echo '<input type="hidden" name="fetch_url" value="'.$fetch_url.'">'."\n";
								echo '<input type="hidden" name="rss_url" value="'.$rss_url.'">'."\n";
								echo '<input type="hidden" name="num" value="'.($num+1).'">'."\n";
								echo '<input type="hidden" name="ua" value="'.$user_attended.'">'."\n";
								echo '</form>'."\n";
								return true;
							}
						}
					} else {
						foreach ($rss->items as $item) {
								sleep(1);
								echo " ";
								ob_flush();
								flush();
								$this->InsertRSSUsers($item, $real_rss_url);
							
							$this->_propCount++;
						}
					}
					
					//var_dump($rss);
					//var_dump($result);
				} else {
					$GLOBALS['app']->Session->PushLastResponse("There was a problem parsing the RSS feed for: ".$fetch_url.". Please make sure the RSS feed URL is entered correctly.", RESPONSE_ERROR);
					//return new Jaws_Error("There was a problem parsing the RSS feed for: ".$fetch_url.". Please make sure the RSS feed URL is entered correctly.", _t('USERS_NAME'));
					echo '<br />'."There was a problem parsing the RSS feed for: ".$fetch_url.". Please make sure the RSS feed URL is entered correctly.";
				}
				//echo $rss_html.'</table>';
			} else {
				//return new Jaws_Error("An RSS feed URL was not given.", _t('USERS_NAME'));
				echo '<br />'."An RSS feed URL was not given.";
			}

			/*
			// Delete users not found in RSS feed
			if ($multifeed === false) {
				$sql = '
					SELECT [id], [nickname], [username], [email]
					FROM [[users]]
					WHERE ([nickname] <> "")';
				
				$params = array();
				$types = array(
					'integer', 'text', 'text', 'text'
				);
				$result = $GLOBALS['db']->queryAll($sql, $params, $types);
				if (Jaws_Error::IsError($result)) {
					//return new Jaws_Error(_t('STORE_ERROR_PRODUCTS_NOT_RETRIEVED'), _t('USERS_NAME'));
					echo '<br />'."Could not find the product to delete.";
				} else {
					foreach ($result as $res) {
						if (!in_array($res['recovery_key'], $this->_newChecksums)) {
							
							$delete = $jUser->DeleteUser($res['id']);
							if (Jaws_Error::IsError($delete)) {
								$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_PRODUCT_NOT_DELETED'), RESPONSE_ERROR);
								//return new Jaws_Error(_t('STORE_ERROR_PRODUCT_NOT_DELETED'), _t('USERS_NAME'));
								echo '<br />COULD NOT DELETE: '.$res['title'].' ::: '.$res['product_code']; 
							} else {
								echo '<br />DELETED: '.$res['title'].' ::: '.$res['product_code']; 
							}
						}
					}
				}
			}
			*/
		} else if ($type == 'TabDelimited' || $type == 'CommaSeparated') {
			$output = '';
			//$result = array();
			if (trim($file) != '' && file_exists(JAWS_DATA.'files'.$file) && strpos(strtolower($file), 'users/') === false) {
				$output .= '<br />File: '.$file;
				echo '<br />File: '.$file;
				// snoopy
				include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
				$snoopy = new Snoopy('Users');
				$fetch_url = $GLOBALS['app']->getDataURL('', true) . 'files'.$xss->filter($file);
				
				if($snoopy->fetch($fetch_url)) {
					$inventoryContent = Jaws_Utils::split2D($snoopy->results, ($type == 'TabDelimited' ? "\t" : ','), ($type == 'CommaSeparated' ? '"' : ''));
					if ($this->_propCount == 1) {
						$output .= '<br />&nbsp;<br />'.'<b>Now importing from: '.urldecode($file).'</b>';
						echo '<br />&nbsp;<br />'.'<b>Now importing from: '.urldecode($file).'</b>';
					}
					ob_flush();
					flush();
					/*
					echo '<pre>';
					var_dump(trim(strtolower($inventoryContent[0][0])));
					var_dump($inventoryContent);
					echo '</pre>';
					exit;
					*/

					// Get column headers
					// Name	
					for ($i=0;$i<50;$i++) {
						if (trim(strtolower($inventoryContent[0][$i]), "\x22\x27 \t\n\r\0\x0B") == 'name') {
							$name = $i;
							break;
						}
					}
					if (!isset($name)) {
						$GLOBALS['app']->Session->PushLastResponse("There was a problem parsing the User List File: ".$fetch_url.". The file you are importing MUST contain the column 'Name'.", RESPONSE_ERROR);
						//return new Jaws_Error("There was a problem parsing the RSS feed for: ".$fetch_url.". Please make sure the RSS feed URL is entered correctly.", _t('USERS_NAME'));
						$output .= '<br />'."There was a problem parsing the User List File: ".$fetch_url.". The file you are importing MUST contain the column 'Name'.";
						echo '<br />'."There was a problem parsing the User List File: ".$fetch_url.". The file you are importing MUST contain the column 'Name'.";
						return false;
					}
					// E-mail	
					for ($i=0;$i<50;$i++) {
						if (trim(strtolower($inventoryContent[0][$i]), "\x22\x27 \t\n\r\0\x0B") == 'e-mail') {
							$email = $i;
							break;
						}
					}
					if (!isset($email)) {
						$GLOBALS['app']->Session->PushLastResponse("There was a problem parsing the User List File: ".$fetch_url.". The file you are importing MUST contain the column 'E-mail'.", RESPONSE_ERROR);
						//return new Jaws_Error("There was a problem parsing the RSS feed for: ".$fetch_url.". Please make sure the RSS feed URL is entered correctly.", _t('USERS_NAME'));
						$output .= '<br />'."There was a problem parsing the User List File: ".$fetch_url.". The file you are importing MUST contain the column 'E-mail'.";
						echo '<br />'."There was a problem parsing the User List File: ".$fetch_url.". The file you are importing MUST contain the column 'E-mail'.";
						return false;
					}
					// Company	
					for ($i=0;$i<50;$i++) {
						if (trim(strtolower($inventoryContent[0][$i]), "\x22\x27 \t\n\r\0\x0B") == 'company') {
							$company = $i;
							break;
						}
					}
					// Business Type	
					for ($i=0;$i<50;$i++) {
						if (trim(strtolower($inventoryContent[0][$i]), "\x22\x27 \t\n\r\0\x0B") == 'company_type') {
							$company_type = $i;
							break;
						}
					}
					// Address	
					for ($i=0;$i<50;$i++) {
						if (trim(strtolower($inventoryContent[0][$i]), "\x22\x27 \t\n\r\0\x0B") == 'address') {
							$address = $i;
							break;
						}
					}
					// City	
					for ($i=0;$i<50;$i++) {
						if (trim(strtolower($inventoryContent[0][$i]), "\x22\x27 \t\n\r\0\x0B") == 'city') {
							$city = $i;
							break;
						}
					}
					// Postal	
					$attribute = array();
					for ($i=0;$i<50;$i++) {
						if (trim(strtolower($inventoryContent[0][$i]), "\x22\x27 \t\n\r\0\x0B") == 'postal') {
							$postal = $i;
							break;
						}
					}
					// State	
					for ($i=0;$i<50;$i++) {
						if (trim(strtolower($inventoryContent[0][$i]), "\x22\x27 \t\n\r\0\x0B") == 'state') {
							$state = $i;
							break;
						}
					}
					
					// Phone	
					for ($i=0;$i<50;$i++) {
						if (trim(strtolower($inventoryContent[0][$i]), "\x22\x27 \t\n\r\0\x0B") == 'phone') {
							$phone = $i;
							break;
						}
					}
					
					// Website	
					for ($i=0;$i<50;$i++) {
						if (trim(strtolower($inventoryContent[0][$i]), "\x22\x27 \t\n\r\0\x0B") == 'website') {
							$website = $i;
							break;
						}
					}
					
					// Groups	
					for ($i=0;$i<50;$i++) {
						if (trim(strtolower($inventoryContent[0][$i]), "\x22\x27 \t\n\r\0\x0B") == 'groups') {
							$groups = $i;
							break;
						}
					}
										
					// Gender	
					for ($i=0;$i<50;$i++) {
						if (trim(strtolower($inventoryContent[0][$i]), "\x22\x27 \t\n\r\0\x0B") == 'gender') {
							$gender = $i;
							break;
						}
					}
										
					// DOB	
					for ($i=0;$i<50;$i++) {
						if (trim(strtolower($inventoryContent[0][$i]), "\x22\x27 \t\n\r\0\x0B") == 'dob') {
							$dob = $i;
							break;
						}
					}
										
					// Description	
					for ($i=0;$i<50;$i++) {
						if (trim(strtolower($inventoryContent[0][$i]), "\x22\x27 \t\n\r\0\x0B") == 'description') {
							$description = $i;
							break;
						}
					}
										
					unset($inventoryContent[0]);
					array_unshift($inventoryContent, array_shift ($inventoryContent)); 					
					$this->_propTotal = count($inventoryContent);
					reset($inventoryContent);
					if ((isset($num) && !empty($num) || $num == 1) && $user_attended == 'Y') {
						if (($num+1) <= $this->_propTotal) {
							sleep(1);
							echo " ";
							ob_flush();
							flush();
							$this->_propCount = ($num+1);
							$details = '<br />Num: '.$num;
							$details .= '<br />ADD User: '.trim($inventoryContent[$num][$name], "\x22\x27 \t\n\r\0\x0B");
							$details .= '<br />Company Type: '.trim($inventoryContent[$num][$company_type], "\x22\x27 \t\n\r\0\x0B");
							$details .= '<br />E-mail: '.trim($inventoryContent[$num][$email], "\x22\x27 \t\n\r\0\x0B");
							$details .= '<br />Company: '.trim($inventoryContent[$num][$company], "\x22\x27 \t\n\r\0\x0B");
							$details .= '<br />Address: '.trim($inventoryContent[$num][$address], "\x22\x27 \t\n\r\0\x0B");
							$details .= '<br />City: '.trim($inventoryContent[$num][$city], "\x22\x27 \t\n\r\0\x0B");
							$details .= '<br />Postal: '.trim($inventoryContent[$num][$postal], "\x22\x27 \t\n\r\0\x0B");
							$details .= '<br />State: '.trim($inventoryContent[$num][$state], "\x22\x27 \t\n\r\0\x0B");
							$details .= '<br />Phone: '.trim($inventoryContent[$num][$phone], "\x22\x27 \t\n\r\0\x0B");
							$details .= '<br />Website: '.trim($inventoryContent[$num][$website], "\x22\x27 \t\n\r\0\x0B");
							$details .= '<br />Groups: '.trim($inventoryContent[$num][$groups], "\x22\x27 \t\n\r\0\x0B");
							$details .= '<br />Gender: '.trim($inventoryContent[$num][$gender], "\x22\x27 \t\n\r\0\x0B");
							$details .= '<br />DOB: '.trim($inventoryContent[$num][$dob], "\x22\x27 \t\n\r\0\x0B");
							$details .= '<br />Description: '.trim($inventoryContent[$num][$description], "\x22\x27 \t\n\r\0\x0B");
							$this->InsertInventoryUsers(
								$xss->filter(trim($inventoryContent[$num][$name], "\x22\x27 \t\n\r\0\x0B")),
								$xss->filter(trim($inventoryContent[$num][$email], "\x22\x27 \t\n\r\0\x0B")),
								$xss->filter(trim($inventoryContent[$num][$company], "\x22\x27 \t\n\r\0\x0B")),
								$xss->filter(trim($inventoryContent[$num][$company_type], "\x22\x27 \t\n\r\0\x0B")),
								$xss->filter(trim($inventoryContent[$num][$address], "\x22\x27 \t\n\r\0\x0B")),
								$xss->filter(trim($inventoryContent[$num][$city], "\x22\x27 \t\n\r\0\x0B")),
								$xss->filter(trim($inventoryContent[$num][$postal], "\x22\x27 \t\n\r\0\x0B")),
								$xss->filter(trim($inventoryContent[$num][$state], "\x22\x27 \t\n\r\0\x0B")),
								$xss->filter(trim($inventoryContent[$num][$phone], "\x22\x27 \t\n\r\0\x0B")),
								$xss->filter(trim($inventoryContent[$num][$website], "\x22\x27 \t\n\r\0\x0B")),
								$xss->filter(trim($inventoryContent[$num][$groups], "\x22\x27 \t\n\r\0\x0B")),
								$xss->filter(trim($inventoryContent[$num][$gender], "\x22\x27 \t\n\r\0\x0B")),
								$xss->filter(trim($inventoryContent[$num][$dob], "\x22\x27 \t\n\r\0\x0B")),
								$xss->filter(trim($inventoryContent[$num][$description], "\x22\x27 \t\n\r\0\x0B"))
							);
							$details .= '<form name="user_rss_form" id="user_rss_form" action="index.php?gadget=Users&action=UpdateRSSUsers" method="POST">'."\n";
							$details .= '<input type="hidden" name="file" value="'.$file.'">'."\n";
							$details .= '<input type="hidden" name="type" value="'.$type.'">'."\n";
							$details .= '<input type="hidden" name="num" value="'.($num+1).'">'."\n";
							$details .= '<input type="hidden" name="ua" value="'.$user_attended.'">'."\n";
							$details .= '</form>'."\n";
							$output .= $details;
							echo $details;
							return true;
						}
					} else {
						unset($inventoryContent[0]);
						array_unshift($inventoryContent, array_shift ($inventoryContent)); 					
						foreach ($inventoryContent as $item) {
							$attr = array();
							foreach($attribute as $key => $val) {
								$attr[] = array($val => $item[$key]);
							}
							//if ($this->_propCount < 100) {
								sleep(1);
								echo " ";
								ob_flush();
								flush();
								$this->InsertInventoryUsers(
									$xss->filter(trim($item[$name], "\x22\x27 \t\n\r\0\x0B")),
									$xss->filter(trim($item[$email], "\x22\x27 \t\n\r\0\x0B")),
									$xss->filter(trim($item[$company], "\x22\x27 \t\n\r\0\x0B")),
									$xss->filter(trim($item[$company_type], "\x22\x27 \t\n\r\0\x0B")),
									$xss->filter(trim($item[$address], "\x22\x27 \t\n\r\0\x0B")),
									$xss->filter(trim($item[$city], "\x22\x27 \t\n\r\0\x0B")),
									$xss->filter(trim($item[$postal], "\x22\x27 \t\n\r\0\x0B")),
									$xss->filter(trim($item[$state], "\x22\x27 \t\n\r\0\x0B")),
									$xss->filter(trim($item[$phone], "\x22\x27 \t\n\r\0\x0B")),
									$xss->filter(trim($item[$website], "\x22\x27 \t\n\r\0\x0B")),
									$xss->filter(trim($item[$groups], "\x22\x27 \t\n\r\0\x0B")), 
									$xss->filter(trim($item[$gender], "\x22\x27 \t\n\r\0\x0B")), 
									$xss->filter(trim($item[$dob], "\x22\x27 \t\n\r\0\x0B")), 
									$xss->filter(trim($item[$description], "\x22\x27 \t\n\r\0\x0B"))
								);
							//} else {
							//	break;
							//}
							$this->_propCount++;
						}
					}
					//var_dump($rss);
					//var_dump($result);
				} else {
					$GLOBALS['app']->Session->PushLastResponse("There was a problem parsing the User List File: ".$fetch_url.". The file you are importing MUST be Tab-Delimited.", RESPONSE_ERROR);
					//return new Jaws_Error("There was a problem parsing the RSS feed for: ".$fetch_url.". Please make sure the RSS feed URL is entered correctly.", _t('USERS_NAME'));
					$output .= '<br />'."There was a problem parsing the User List File: ".$fetch_url.". The file you are importing MUST be Tab-Delimited.";
					echo '<br />'."There was a problem parsing the User List File: ".$fetch_url.". The file you are importing MUST be Tab-Delimited.";
					return false;
				}
				//echo $rss_html.'</table>';
			} else {
				//return new Jaws_Error("An RSS feed URL was not given.", _t('USERS_NAME'));
				$output .= '<br />'."A User List File was not given.";
				echo '<br />'."A User List File was not given.";
			}

			/*
			// Delete Users not found in RSS feed
			$sql = '
				SELECT [id], [category], [title], [internal_productno]
				FROM [[product]]
				WHERE ([title] <> "")';
			
			$params = array();
			$types = array(
				'integer', 'text', 'text', 'text'
			);
			$result = $GLOBALS['db']->queryAll($sql, $params, $types);
			if (Jaws_Error::IsError($result)) {
				//return new Jaws_Error(_t('STORE_ERROR_PRODUCTS_NOT_RETRIEVED'), _t('USERS_NAME'));
				$output .= '<br />'."Could not find the product to delete.";
				echo '<br />'."Could not find the product to delete.";
			} else {
				foreach ($result as $res) {
					if (!in_array($res['internal_productno'], $this->_newChecksums) && (int)$category == (int)$res['category']) {
						
						$sql = '
							UPDATE [[product]] SET
								[active] = {Active},
								[updated] = {now}
							WHERE [id] = {id}';

						$params               		= array();
						$params['id']         		= (int)$found;
						$params['Active']        	= 'N';
						$params['now']        		= $GLOBALS['db']->Date();

						$result = $GLOBALS['db']->query($sql, $params);
						if (Jaws_Error::IsError($result)) {
						//$delete = $this->DeleteProduct($res['id'], true);
						//if (Jaws_Error::IsError($delete)) {
							$GLOBALS['app']->Session->PushLastResponse(_t('STORE_ERROR_PRODUCT_NOT_DELETED'), RESPONSE_ERROR);
							//return new Jaws_Error(_t('STORE_ERROR_PRODUCT_NOT_DELETED'), _t('USERS_NAME'));
							$output .= '<br />COULD NOT DELETE: '.$res['title'].' ::: '.$res['internal_productno']; 
							echo '<br />COULD NOT DELETE: '.$res['title'].' ::: '.$res['internal_productno']; 
						} else {
							$output .= '<br />DELETED: '.$res['title'].' ::: '.$res['internal_productno']; 
							echo '<br />DELETED: '.$res['title'].' ::: '.$res['internal_productno']; 
						}
					}
				}
			}
			*/
		} else {
			$output .= "<h1>User File Type Not Supported</h1>";
			echo "<h1>User File Type Not Supported</h1>";
		}

		// Get the victims and initiate that body count status
		$victims = func_get_args();
		$body_count = 0;   
	   
		// Kill those damn punks
		foreach($victims as $victim) {
			unset($victim);
			if (!isset($victim)) {
				$body_count++;
			}
		}
	   
		// How many kills did Rambo tally up on this mission?
		//echo ' ::: Removed '.$body_count.' variables';
		
		//return $result;
		//echo "<script type=\"text/javascript\">location.href='" . BASE_SCRIPT . "';</script>";
		//echo "<noscript><h1>Feed Imported Successfully</h1><a href=\"" . BASE_SCRIPT . "\">Click Here to Continue</a> if your browser does not redirect automatically.</noscript>";
        
		// Delete inventory list
		if (file_exists(JAWS_DATA.'files'.$file)) {
			if (!Jaws_Utils::Delete(JAWS_DATA.'files'.$file, false)) {
				$output .= "<br />Couldn't Delete File During Cleanup";
			}
		}
		
		$output .= "<h1>Users Imported Successfully</h1>";
		echo '<form name="user_rss_form" id="user_rss_form" action="admin.php?gadget=Users" method="POST"></form>'."\n";
		echo "<h1>Users Imported Successfully</h1>";
		
		if (Jaws_Utils::is_writable(JAWS_DATA . 'logs/')) {
            $result = file_put_contents(JAWS_DATA . 'logs/inventoryimport.log', $output);
            if ($result === false) {
                return new Jaws_Error("Couldn't create inventoryimport.log file", _t('USERS_NAME'));
                //return false;
			}
		}

		return true;
    }
	
	/**
     * Returns an array with all the country DB table data
     *
     * @access  public
     * @param   integer  $item  array of info
     * @return  array   country DB table data
     */
    function InsertInventoryUsers(
		$name, $email, $company = '', $company_type = '', $address = '', $city = '', $postal = '', 
		$state = '', $phone = '', $website = '', $groups = 'users', $gender = 0, $dob = '', $description = '')
    {
		ignore_user_abort(true); 
        set_time_limit(0);
		echo " ";
		ob_flush();
		flush();
		sleep(1);
        require_once JAWS_PATH . 'include/Jaws/User.php';
        $jUser = new Jaws_User;
		$model = $GLOBALS['app']->LoadGadget('Users', 'Model');
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');

		// Continue only if we have a product name
		if (isset($name) && !empty($name)) {
			$rss_name = $name;
			$rss_email = $email;
			$rss_company = (isset($company) && !empty($company) ? $company : '');
			$rss_business_type = (isset($company_type) && !empty($company_type) ? $company_type : '');
			$rss_address = (isset($address) && !empty($address) ? $address : '');
			$rss_city = (isset($city) && !empty($city) ? $city : '');
			$rss_postal = (isset($postal) && !empty($postal) ? $postal : '');
			$rss_state = (isset($state) && !empty($state) ? $state : '');
			$rss_phone = (isset($phone) && !empty($phone) ? $phone : '');
			$rss_website = (isset($website) && !empty($website) ? $website : '');
			$rss_gender = (isset($gender) && !empty($gender) ? $gender : 0);
			$rss_gender = ($rss_gender == '1' || strtolower($rss_gender) == 'female' || strtolower($rss_gender) == 'f' ? 1 : 0);
			$rss_description = (isset($description) && !empty($description) ? $description : '');
			$rss_dob = (isset($dob) && !empty($dob) ? date('Y-m-d H:i:s', strtotime($dob)) : null);
			$rss_groups = (isset($groups) && !empty($groups) ? $groups : 'users');
															
			// Create username
			$username = strtolower(preg_replace("[^A-Za-z0-9]", '', substr($email, 0, strpos($email, '@'))));
			$username = substr($username, 0, 8);

			// Check username
			$exists = $jUser->GetUserInfoByName($username);
			if (
				(isset($exists['id']) && !empty($exists['id'])) || 
				(!preg_match('/^[a-z0-9]+$/i', $username) || strlen($username) < 3) 
				|| strtolower($username) == 'login' || strtolower($username) == 'register'
				|| strtolower($username) == 'logout' || strtolower($username) == 'profile'
				|| strtolower($username) == 'forget' || strtolower($username) == 'recover' 
				|| strtolower($username) == 'custom' || strtolower($username) == 'directory'
				|| strtolower($username) == 'friend' 
				|| substr(strtolower($username), 0, 4) == 'test' 
				|| substr(strtolower($username), 0, 4) == 'root'
				|| substr(strtolower($username), 0, 4) == 'info'
				|| substr(strtolower($username), 0, 5) == 'admin'
			) {
				include_once 'Text/Password.php';
				$username = Text_Password::create(8, 'pronounceable', 'alphanumeric');
				$exists = $jUser->GetUserInfoByName($username);
				if (isset($exists['id']) && !empty($exists['id'])) {
					$GLOBALS['app']->Session->PushLastResponse(_t('USERS_REGISTER_USERNAME_NOT_VALID'), RESPONSE_ERROR);
					//return new Jaws_Error(_t('USERS_REGISTER_USERNAME_NOT_VALID'), _t('USERS_NAME'));
					echo '<br />'._t('USERS_REGISTER_USERNAME_NOT_VALID');
				}
			}

			$prop_checksum = md5($rss_name.', '.$rss_email.', '.$rss_groups);
			$this->_newChecksums[] = $prop_checksum;
			if (!empty($rss_name) && !empty($prop_checksum)) {
				$params = array();
				$params['checksum'] = $prop_checksum;

				$sql = 'SELECT [id] FROM [[users]] WHERE ([identifier] = {checksum})';
				$found = $GLOBALS['db']->queryOne($sql, $params);
										
				if (is_numeric($found)) {
					$page = $jUser->GetUserInfoById((int)$found);
					if (Jaws_Error::isError($page)) {
						$GLOBALS['app']->Session->PushLastResponse(_t('USERS_ERROR_USER_NOT_FOUND'), RESPONSE_ERROR);
						//return new Jaws_Error(_t('USERS_ERROR_USER_NOT_FOUND'), _t('USERS_NAME'));
						echo '<br />'._t('USERS_ERROR_USER_NOT_FOUND');
					} else if (isset($page['id']) && !empty($page['id'])) {
						$params               	= array();
						$params['id']         	= $found;
						$params['username']   	= $username;
						$params['name']       	= $rss_name;
						$params['email']      	= $rss_email;
						$params['url']    		= $rss_website;
						$params['company']    	= $rss_company;
						$params['company_type'] = $rss_business_type;
						$params['address']    	= $rss_address;
						$params['city']    		= $rss_city;
						$params['region']    	= $rss_state;
						$params['postal']    	= $rss_postal;
						$params['phone']    	= $rss_phone;
						$params['description']	= $rss_description;
						$params['dob']			= $rss_dob;
						$params['gender']		= $rss_gender;
						$params['updatetime'] 	= $GLOBALS['db']->Date();
						
						$sql = '
							UPDATE [[users]] SET
								[username] = {username},
								[nickname] = {name},
								[email] = {email},
								[updatetime] = {updatetime},
								[url] = {url},
								[company] = {company},
								[company_type] = {company_type},
								[address] = {address},
								[city] = {city},
								[region] = {region},
								[postal] = {postal},
								[dob] = {dob},
								[gender] = {gender},
								[description] = {description},
								[phone] = {phone}
							WHERE [id] = {id}';

						if (isset($GLOBALS['app']->Session) && $GLOBALS['app']->Session->GetAttribute('user_id') == $found) {
							$GLOBALS['app']->Session->SetAttribute('username', $username);
						}

						$result = $GLOBALS['db']->query($sql, $params);
						if (Jaws_Error::IsError($result)) {
							//return new Jaws_Error(_t('STORE_ERROR_PRODUCT_NOT_UPDATED'), _t('USERS_NAME'));
							echo '<br />'._t('USERS_USERS_NOT_UPDATED');
						} else {
							if (($this->_propCount-1) >= 1) {
								echo '<style>#prod_'.($this->_propCount-1).' {display: none;}</style>';
								ob_flush();
								flush();
							}
							echo '<div id="prod_'.$this->_propCount.'"><br />Updating <b>'.$this->_propCount.' of '.$this->_propTotal.'</b> '.$rss_name.' ' . memory_get_usage() . '</div>';
							ob_flush();
							flush();
						}
					}
					unset($page);
						
				} else {
					// Add the user	
					$result = $model->CreateUser(
						$username, 
						$rss_email, 
						$rss_name, 
						'', '', 
						$rss_gender, 
						$rss_dob, 
						$rss_website, 
						'', 2, null,  
						$rss_address, 
						'', 
						$rss_city, 
						"United States", 
						$rss_state, 
						$rss_postal
					);

					if ($result === false || Jaws_Error::IsError($result)) {
						$GLOBALS['app']->Session->PushLastResponse(_t('USERS_USERS_NOT_CREATED'), RESPONSE_ERROR);
						echo '<br />'._t('USERS_USERS_NOT_CREATED');
						//$output_html .= "<br />ERROR: ".$result->getMessage();
					} else {
						$info = $jUser->GetUserInfoByName($username, true);
						if (Jaws_Error::IsError($info) || !isset($info['id']) || empty($info['id'])) {
							$GLOBALS['app']->Session->PushLastResponse(_t('USERS_USER_NOT_EXIST'), RESPONSE_ERROR);
							echo '<br />'._t('USERS_USER_NOT_EXIST');
							//$output_html .= "<br />ERROR: ".$result->getMessage();
						} else {
							$pInfo = array('gender' 		=> $rss_gender,
										   'phone'    		=> $rss_phone,
										   'dob'    		=> $rss_dob,
										   'url'    		=> $rss_website,
										   'company'    	=> $rss_company, 
										   'description'    => $rss_description, 
										   'company_type'   => $rss_business_type);

							$result = $jUser->UpdatePersonalInfo($info['id'], $pInfo);
							if (Jaws_Error::IsError($result)) {
								$GLOBALS['app']->Session->PushLastResponse($result->getMessage(), RESPONSE_ERROR);
								echo '<br />'.$result->getMessage();
								//$output_html .= "<br />ERROR: ".$result->getMessage();
							} else {
								$params               	= array();
								$params['checksum']     = $prop_checksum;
								$params['id']     		= $info['id'];
								
								$sql = '
									UPDATE [[users]] SET
										[identifier] = {checksum}
									WHERE [id] = {id}';

								$result = $GLOBALS['db']->query($sql, $params);
								if (Jaws_Error::IsError($result)) {
									//return new Jaws_Error(_t('STORE_ERROR_PRODUCT_NOT_UPDATED'), _t('USERS_NAME'));
									echo '<br />'._t('USERS_USERS_NOT_UPDATED');
								}
								
								// Add to Groups
								$groups_array = explode(',', $rss_groups);
								foreach ($groups_array as $group) {
									$groupInfo = $jUser->GetGroupInfoByName($group);
									if (!isset($groupInfo['id'])) {
										echo "<br />Group not found. Adding group: ".$group;
										$add_group = $userModel->AddGroup($group);
										if ($add_group === false) {
											echo "<br />There was a problem while adding group: ".$group;
											return new Jaws_Error("There was a problem while adding group: ".$group, _t('USERS_NAME'));
										}
									}
									echo "<br />Adding user: ".$admin['username']. " to group: ".$group;
									$user_group = $jUser->AddUserToGroupName($info['id'], $group, 'active');
									if ($user_group === false) {
										echo "<br />There was a problem while adding user: ".$info['username']. " to group: ".$group;
										return new Jaws_Error("There was a problem while adding user: ".$info['username']. " to group: ".$group, _t('USERS_NAME'));
									}
								}
								if (($this->_propCount-1) >= 1) {
									echo '<style>#prod_'.($this->_propCount-1).' {display: none;}</style>';
									ob_flush();
									flush();
								}
								echo '<div id="prod_'.$this->_propCount.'"><br />Importing <b>'.$this->_propCount.' of '.$this->_propTotal.'</b> '.$rss_name.' ' . memory_get_usage() . '</div>';
								ob_flush();
								flush();
							}
						}
					}
				}
				unset($found);
			
				$params = array();
				$params['checksum'] = $prop_checksum;
				$sql = 'SELECT [id] FROM [[users]] WHERE ([identifier] = {checksum})';
				$found = $GLOBALS['db']->queryOne($sql, $params);
				if (Jaws_Error::IsError($found) || !is_numeric($found)) {
					$GLOBALS['app']->Session->PushLastResponse('User Not Added', RESPONSE_ERROR);
					if (($this->_propCount-1) >= 1) {
						echo '<style>#prod_'.($this->_propCount-1).' {display: none;}</style>';
						ob_flush();
						flush();
					}
					echo '<div><br />User <b>'.$this->_propCount.' of '.$this->_propTotal.'</b> Not Added</div>';
					ob_flush();
					flush();
				}
			} else {
				$GLOBALS['app']->Session->PushLastResponse('User Not Added', RESPONSE_ERROR);
				if (($this->_propCount-1) >= 1) {
					echo '<style>#prod_'.($this->_propCount-1).' {display: none;}</style>';
					ob_flush();
					flush();
				}
				echo '<div><br />User <b>'.$this->_propCount.' of '.$this->_propTotal.'</b> Not Added</div>';
				ob_flush();
				flush();
			}
			unset($result);
			unset($prop_checksum);
			
			//ob_end_flush();
			//break;
		} else {
			$GLOBALS['app']->Session->PushLastResponse('Invalid User Name', RESPONSE_ERROR);
			if (($this->_propCount-1) >= 1) {
				echo '<style>#prod_'.($this->_propCount-1).' {display: none;}</style>';
				ob_flush();
				flush();
			}
			echo '<div><br />User <b>'.$this->_propCount.' of '.$this->_propTotal.'</b> could not be added</div>';
			ob_flush();
			flush();
		}
		
		unset($rss_name);
		unset($rss_email);
		unset($rss_company);
		unset($rss_business_type);
		unset($rss_address);
		unset($rss_city);
		unset($rss_postal);
		unset($rss_state);
		unset($rss_phone);
		unset($rss_website);
		unset($rss_dob);
		unset($rss_gender);
		unset($rss_description);
		unset($rss_groups);
		unset($model);
		unset($jUser);
	
		// Get the victims and initiate that body count status
		$victims = func_get_args();
		$body_count = 0;   
	   
		// Kill those damn punks
		foreach($victims as $victim) {
			unset($victim);
			if (!isset($victim)) {
				$body_count++;
			}
		}
	   
		// How many kills did Rambo tally up on this mission?
		//echo ' ::: Removed '.$body_count.' variables';
		  
		//ob_end_clean();
		//return $GLOBALS['app']->Session->PopLastResponse();
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
		if ($gadget == 'Users') {
			// Update checksum
			$config_key = $GLOBALS['app']->Registry->Get('/config/key');		
			
			require_once JAWS_PATH . 'include/Jaws/User.php';
			$model = new Jaws_User;
			$parents = $model->GetUsers();
			if (Jaws_Error::IsError($parents)) {
				return false;
			}
			foreach ($parents as $parent) {
				if (empty($parent['checksum']) || is_null($parent['checksum']) || strpos($parent['checksum'], ':') === false) {
					$params               	= array();
					$params['id'] 			= $parent['id'];
					$params['checksum'] 	= $parent['id'].':'.$config_key;
					
					$sql = '
						UPDATE [[users]] SET
							[checksum] = {checksum}
						WHERE [id] = {id}';

					$result = $GLOBALS['db']->query($sql, $params);
					if (Jaws_Error::IsError($result)) {
						$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
						return false;
					}

					// Let everyone know it has been added
					$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
					$res = $GLOBALS['app']->Shouter->Shout('onAddUser', $parent['id']);
					if (Jaws_Error::IsError($res) || !$res) {
						return $res;
					}
				}
			}
			$parents = $model->GetAllGroups();
			if (Jaws_Error::IsError($parents)) {
				return false;
			}
			foreach ($parents as $parent) {
				if (empty($parent['checksum']) || is_null($parent['checksum']) || strpos($parent['checksum'], ':') === false) {
					$params               	= array();
					$params['id'] 			= $parent['id'];
					$params['checksum'] 	= $parent['id'].':'.$config_key;
					
					$sql = '
						UPDATE [[groups]] SET
							[checksum] = {checksum}
						WHERE [id] = {id}';

					$result = $GLOBALS['db']->query($sql, $params);
					if (Jaws_Error::IsError($result)) {
						$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
						return false;
					}

					// Let everyone know it has been added
					$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
					$res = $GLOBALS['app']->Shouter->Shout('onAddGroup', $parent['id']);
					if (Jaws_Error::IsError($res) || !$res) {
						return $res;
					}
				}
			}
		}
		return true;
    }

	/**
     * Friends all users following group that given user has joined
     *
     * @access  public
     * @param   array  $params user_id and group_id
     * @return  boolean Returns true if pair was successfully unassociated, error if not
     */
    function InsertGroupsFriends($params)
    {
		$user = $params['user_id'];
		$group = $params['group_id'];
		$status = $params['status'];
		
		if ($status == 'active') {
			require_once JAWS_PATH . 'include/Jaws/User.php';
			$model = new Jaws_User;
			$friends = $model->GetUsersOfFriendByStatus($group, 'group_active');
			if (Jaws_Error::IsError($friends)) {
				return false;
			}
			foreach ($friends as $friend) {
				if (isset($friend['friend_id']) && !empty($friend['friend_id'])) {
					$res = $model->AddUserToFriend($user, $friend['friend_id'], 'active');
					if (Jaws_Error::IsError($res)) {
						return false;
					}
				}
			}
			$result = $model->AddUserToFriend($user, $group, 'group_active');
			if (Jaws_Error::IsError($result)) {
				return false;
			}
		}
		return true;
    }

    /**
     * Search for comments
     *
     * @access  public
     * @param   string  $search  Keyword (title/description) of comments we want to look for
     * @param   int     $offSet  Data limit
     * @return  array   Array of matches
     */
    function SearchMessages($search, $offSet = null, $OwnerID = 0)
    {
        $params = array();
		$params['gadget'] = 'Users';
		$params['ownerid'] = $OwnerID;
        
		$sql = '
            SELECT
                [id],
                [gadget_reference],
                [gadget],
                [parent],
                [name],
                [email],
                [url],
                [ip],
                [title],
                [msg_txt],
                [status],
                [replies],
                [createtime],
				[ownerid],
				[sharing],
				[checksum]
            FROM [[comments]]
            WHERE
				[gadget] = {gadget} AND 
				[ownerid] = {ownerid}
		';
		
		if (trim($search) != '') {
            $searchdata = explode(' ', $search);
            /**
             * This query needs more work, not use $v straight, should be
             * like rest of the param stuff.
             */
            $i = 0;
            foreach ($searchdata as $v) {
                $v = trim($v);
                $sql .= " AND (
					[name] LIKE {textLike_".$i."} OR 
					[email] LIKE {textLike_".$i."} OR 
					[url] LIKE {textLike_".$i."} OR 
					[title] LIKE {textLike_".$i."} OR 
					[msg_txt] LIKE {textLike_".$i."}
				)";
                $params['textLike_'.$i] = '%'.$v.'%';
                $i++;
            }
        }

        if (is_numeric($offSet)) {
            $limit = 10;
            $result = $GLOBALS['db']->setLimit(10, $offSet);
            if (Jaws_Error::IsError($result)) {
                return new Jaws_Error(_t('GLOBAL_ERROR_RETRIEVE_COMMENTS'), _t('USERS_NAME'));
            }
        }
	    
        $result = $GLOBALS['db']->queryAll($sql, $params);
		if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('GLOBAL_ERROR_RETRIEVE_COMMENTS'), _t('USERS_NAME'));
        }
        
		return $result;
    }

    /**
     * Users Requests 
     *
     * @access  public
     * @param   array  $params
		$params = array(
			'gadget' => $gadget, 
			'user_id' => $uid, 
			'public' => $public
		)
     * @return  array   Array of requests
     */
    function ShowUsersRequests($params)
    {
		$model = $GLOBALS['app']->LoadGadget('Users', 'Model');
		return $model->GetUsersRequests($params);
	}
	
}