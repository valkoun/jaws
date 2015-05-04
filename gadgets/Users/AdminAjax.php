<?php
/**
 * Users AJAX API
 *
 * @category   Ajax
 * @package    Users
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @copyright  2005-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class UsersAdminAjax extends Jaws_Ajax
{
    /**
     * Constructor
     *
     * @access  public
     */
    function UsersAdminAjax(&$model)
    {
        $this->_Model =& $model;
    }

    /**
     * Get a users's profile
     *
     * @access  public
     * @param   int     $uid  Users's ID
     * @return  array   User's Profile
     */
    function GetUser($uid)
    {
        $this->CheckSession('Users', 'default');
		$xss   = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        require_once JAWS_PATH . 'include/Jaws/Image.php';
        require_once JAWS_PATH . 'include/Jaws/User.php';
        $model = new Jaws_User();

        $profile = $model->GetUserInfoById($uid, true , true, true, true);
        if (Jaws_Error::IsError($profile)) {
            return array();
        }

        require_once JAWS_PATH . 'include/Jaws/Gravatar.php';
		$profile['description'] = $xss->filter($profile['description']);
		$profile['description'] = str_replace('&lt;/p&gt;',"\r\n",$profile['description']);
		$profile['description'] = str_replace('&lt;p&gt;','',$profile['description']);
		$profile['image'] = $model->GetAvatar($profile['username'], $profile['email']);
        return $profile;
    }

    /**
     * Log-in as a user
     *
     * @access  public
     * @param   int     $uid  Users's ID
     * @return  nothing
     */
    function LoginAsUser($uid)
    {
        $this->CheckSession('Users', 'ManageUsers');
        require_once JAWS_PATH . 'include/Jaws/User.php';
        $model = new Jaws_User();

        $profile = $model->GetUserInfoById($uid, true);
        if (Jaws_Error::IsError($profile)) {
            $GLOBALS['app']->Session->PushLastResponse($profile->GetMessage(), RESPONSE_ERROR);
            return $GLOBALS['app']->Session->PopLastResponse();
        }

        // Login over current session
		$login = $GLOBALS['app']->Session->Login($profile['username'], '', false, true);
        if (Jaws_Error::isError($login)) {
            $GLOBALS['app']->Session->PushLastResponse($login->GetMessage(), RESPONSE_ERROR);
            return $GLOBALS['app']->Session->PopLastResponse();
        }
				
		// Do we need to login over HTTPS session, too?
		$site_ssl_url = $GLOBALS['app']->Registry->Get('/config/site_ssl_url');
		if (!empty($site_ssl_url)) {
			$return = array();
			$return[0] = array();
			$return[0]['message'] = 'Logging in over SSL...';
			$return[0]['level'] = 'RESPONSE_NOTICE';
			$return[0]['css'] = 'notice-message';
			if (!isset($_SERVER['HTTPS']) || strtolower($_SERVER['HTTPS']) != 'on') {
				$return[0]['url'] = $GLOBALS['app']->GetSiteURL('/admin.php?gadget=Users&id='.$uid.'&msg=loginSSL', false, 'https');
			} else {
				$return[0]['url'] = $GLOBALS['app']->GetSiteURL('/admin.php?gadget=Users&id='.$uid.'&msg=loginSSL', false, 'http');
			}
			return $return;
		}

		$GLOBALS['app']->Session->PushLastResponse('Logged in as user successfully', RESPONSE_NOTICE);
		return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Get a list of users
     *
     * @access  public
     * @param   string  $match    Users who match..
     * @return  array   Users list
     */
    function GetUsers($group, $type, $enabled, $search, $orderBy, $sortDir)
    {
        $this->CheckSession('Users', 'default');
		$date = $GLOBALS['app']->loadDate();
		$type = (int)$type;
        /*
        if (!$GLOBALS['app']->Session->IsSuperAdmin() && empty($type)) {
            if ($type === 0) {
                return false;
            } else {
                $type = '1,2';
            }
        }
		*/

        $group   = $group == -1   ? false : (int)$group;
        $enabled = $enabled == -1 ? null  : (bool)$enabled;

        require_once JAWS_PATH . 'include/Jaws/User.php';
        $model = new Jaws_User();
        $type = ($GLOBALS['app']->Session->IsSuperAdmin() ? 
							($type == -1 || empty($type) && $type != 0 ? false : (int)$type) : 
								($type < 1 || empty($type) ? '1,2' : (int)$type));
		$users = $model->GetUsers(false, $type, $enabled, $orderBy, null, null, $sortDir, '', $search);
        if (count($users) <= 0) {
            return false;
        }

        $types = array(
            '0' => _t('USERS_USERS_TYPE_SUPERADMIN'),
            '1' => _t('USERS_USERS_TYPE_ADMIN'),
            '2' => _t('USERS_USERS_TYPE_NORMAL')
        );

        // sort resulting array
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
        $list = array();
        foreach ($sorted_users as $user) {
            $list[] = array(
                'username' => $user[$orderBy],
                'id'       => $user['id'],
                'nickname' => $user['nickname'],
                'type'     => $types[$user['user_type']],
            );
        }
        return $list;
    }

	/**
     * Get a list of user's friends
     *
     * @access  public
     * @param   boolean $showAll  Show all users (ADMIN and NORMAL) (by default: false)
     * @param   string  $match    Users who match..
     * @return  array   Users list
     */
    function GetFriendsOfUser($uid = null)
    {
		$list = array();
		$list_ids = array();
        if ($GLOBALS['app']->Session->Logged()) {
			if (is_null($uid)) {
				$uid = $GLOBALS['app']->Session->GetAttribute('user_id');
			}
			require_once JAWS_PATH . 'include/Jaws/User.php';
			$model = new Jaws_User();
			$users = $model->GetFriendsOfUser($uid);

			foreach ($users as $user) {
				if ($user['friend_status'] == 'active' && !in_array((int)$user['friend_id'], $list_ids)) {
					$user = $model->GetUserInfoById($user['friend_id'], true, true, true, true);
					if (!Jaws_Error::isError($user) && isset($user['id']) && !empty($user['id'])) {
						$list[] = array(
							'username' => $user['username'],
							'id'       => $user['id'],
							'realname' => (!empty($user['nickname']) ? $user['nickname'] : $user['username']).(!empty($user['company']) ? " (".$user['company'].')' : '')
						);
						$list_ids[] = (int)$user['id'];
					}
				}
			}
			if ($GLOBALS['app']->Session->IsAdmin() || $GLOBALS['app']->Session->IsSuperAdmin()) {
				$users = $model->GetUsers();
				if (count($users) <= 0 && count($list) <= 0) {
					return $list;
				}

				foreach ($users as $user) {
					if (!in_array((int)$user['id'], $list_ids)) {
						$list[] = array(
							'username' => $user['username'],
							'id'       => $user['id'],
							'realname' => (!empty($user['nickname']) ? $user['nickname'] : $user['username']).(!empty($user['company']) ? " (".$user['company'].')' : '')
						);
						$list_ids[] = (int)$user['id'];
					}
				}
			}
			if (count($list)) {
				$subkey = 'realname'; 
				$temp_array = array();
				$temp_array[key($list)] = array_shift($list);
				foreach($list as $key => $val) {
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
				$list = array_reverse($temp_array);
			}
		}
        return $list;
    }

	/**
     * Get a list of users in group
     *
     * @access  public
     * @param   boolean $showAll  Show all users (ADMIN and NORMAL) (by default: false)
     * @param   string  $match    Users who match..
     * @return  array   Users list
     */
    function GetUsersOfGroup($gid = null)
    {
		$list = array();
        if ($GLOBALS['app']->Session->Logged()) {
			if (is_null($gid)) {
				return $list;
			}
			require_once JAWS_PATH . 'include/Jaws/User.php';
			$model = new Jaws_User();
			$users = $model->GetUsersOfGroup($gid);
			if (count($users) <= 0) {
				return $list;
			}

			$types = array(
				'0' => _t('USERS_USERS_TYPE_SUPERADMIN'),
				'1' => _t('USERS_USERS_TYPE_ADMIN'),
				'2' => _t('USERS_USERS_TYPE_NORMAL')
			);

			foreach ($users as $user) {
				if ($user['group_status'] == 'founder' || $user['group_status'] == 'admin' || $user['group_status'] == 'active') {
					$list[] = array(
						'id'       => $user['user_id'],
						'realname' => $user['user_name']
					);
				}
			}
		}
        return $list;
    }
    
	/**
     * Get a list of user's social services
     *
     * @access  public
     * @param   string  $uid    Users ID..
     * @return  array	list
     */
    function GetSocialSharingOfUser($uid = null)
    {
		$list = array();
        if ($GLOBALS['app']->Session->Logged() && Jaws_Gadget::IsGadgetUpdated('Social')) {
			if (is_null($uid)) {
				$uid = $GLOBALS['app']->Session->GetAttribute('user_id');
			}
			$model = $GLOBALS['app']->LoadGadget('Social', 'Model');
			$social = $model->GetSocialOfUserID($uid);
            if (Jaws_Error::isError($social)) {
				return $list;
			}
			foreach ($social as $soc) {
				if ($soc['active'] == 'Y') {
					$list[] = array(
						'id'       => $user['social'],
						'realname' => $user['social']
					);
				}
			}
		}
        return $list;
    }
	
	/**
     * Get a list of user's e-mail contacts
     *
     * @access  public
     * @param   string  $match    Users ID..
     * @return  array   E-mail list
     */
    function GetEmailSharingOfUser($uid = null)
    {
		$list = array();
        if ($GLOBALS['app']->Session->Logged() && Jaws_Gadget::IsGadgetUpdated('Social')) {
			if (is_null($uid)) {
				$uid = $GLOBALS['app']->Session->GetAttribute('user_id');
			}
			$model = $GLOBALS['app']->LoadGadget('Social', 'Model');
			$social = $model->GetEmailsOfUserID($uid);
            if (Jaws_Error::isError($social)) {
				return $list;
			}
			foreach ($social as $soc) {
				if ($soc['active'] == 'Y') {
					$email_id = str_replace('.', '__DOT__', $user['email']);
					$email_id = str_replace('@', '__AT__', $email_id);
					$list[] = array(
						'id'       => $email_id,
						'realname' => $user['email']
					);
				}
			}
		}
        return $list;
    }

	/**
     * Adds a new user
     *
     * @access  public
     * @param   string  $username  Username
     * @param   string  $password  Password
     * @param   string  $nickname     User's display name
     * @param   string  $email     User's email
     * @param   int     $guid      Group where user should go
     * @param   string  $type      User's type (ADMIN or NORMAL)
     * @param   boolean $enabled   Enabled/Disabled
     * @return  array   Response (notice or error)
     */
    function AddUser($username, $password, $nickname, $email, $guid, $type, $enabled)
    {
        $this->CheckSession('Users', 'ManageUsers');

        if ($GLOBALS['app']->Registry->Get('/crypt/enabled') == 'true') {
            require_once JAWS_PATH . 'include/Jaws/Crypt.php';
            $JCrypt = new Jaws_Crypt();
            $JCrypt->Init();
            $password = $JCrypt->rsa->decryptBinary($JCrypt->math->int2bin($password), $JCrypt->pvt_key);
            if (Jaws_Error::isError($password)) {
                $password = '';
            }
        }
		
        if (trim($username) == '' || trim($nickname) == '' || trim($password) == '' ||  trim($email) == '')
        {
            $GLOBALS['app']->Session->PushLastResponse(_t('USERS_USERS_INCOMPLETE_FIELDS'), RESPONSE_ERROR);
            return $GLOBALS['app']->Session->PopLastResponse();
        }

        if ((int)$type == 0 && $GLOBALS['app']->Session->IsSuperAdmin() === false) {
            $type = 1;
        }

        require_once JAWS_PATH . 'include/Jaws/User.php';
        $model = new Jaws_User();
        $enabled = $enabled == 'true';
		$res = $model->AddUser($username, $nickname, $email, $password, (int)$type, $enabled);
        if (Jaws_Error::isError($res)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('USERS_USERS_NOT_CREATED', $username), RESPONSE_ERROR);
        } else {
            if (isset($guid)) {
				if ((int)$guid > 0) {
					$res2 = $model->AddUserToGroup($res, $guid);
				} else {
					$res2 = $model->AddUserToGroupName($res, $guid);
				}
           }
            $GLOBALS['app']->Session->PushLastResponse(_t('USERS_USERS_CREATED', $username), RESPONSE_NOTICE);
        }

        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Adds a new group
     *
     * @access  public
     * @param   string $name        Groups's name
     * @param   string $title       Groups's title
     * @param   string $description Groups's description
     * @param   integer $founder	Groups's founder (User ID)
     * @return  array  Response (notice or error)
     */
    function AddGroup($name, $title, $description, $founder = 0)
    {
        $this->CheckSession('Users', 'ManageGroups');
        if (trim($name) == '') {
            $GLOBALS['app']->Session->PushLastResponse(_t('USERS_GROUPS_INCOMPLETE_FIELDS'), RESPONSE_ERROR);
        } else {
            require_once JAWS_PATH . 'include/Jaws/User.php';
            $model = new Jaws_User();
            $res = $model->AddGroup($name, $title, $description, true, '', $founder);
            if ($res === false) {
                $GLOBALS['app']->Session->PushLastResponse(_t('USERS_GROUPS_NOT_CREATED', $title), RESPONSE_ERROR);
            } else {
                $GLOBALS['app']->Session->PushLastResponse(_t('USERS_GROUPS_CREATED', $title), RESPONSE_NOTICE);
            }
        }
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Updates an user
     *
     * @access  public
     * @param   int    $uid       User ID
     * @param   string $username  Username
     * @param   string $password  Password
     * @param   string $nickname     User's display name
     * @param   string $email     User's email
     * @param   string $type      User's type (ADMIN or NORMAL)
     * @return  array  Response (notice or error)
     */
    function UpdateUser($uid, $username, $password, $nickname, $email, $type, $enabled)
    {
        $this->CheckSession('Users', 'ManageUsers');

        if (trim($username) == '' || trim($nickname) == '' || trim($email) == '')
        {
            $GLOBALS['app']->Session->PushLastResponse(_t('USERS_USERS_INCOMPLETE_FIELDS'), RESPONSE_ERROR);
            return $GLOBALS['app']->Session->PopLastResponse();
        }

        if ($GLOBALS['app']->Registry->Get('/crypt/enabled') == 'true') {
            require_once JAWS_PATH . 'include/Jaws/Crypt.php';
            $JCrypt = new Jaws_Crypt();
            $JCrypt->Init();
            $password = $JCrypt->rsa->decryptBinary($JCrypt->math->int2bin($password), $JCrypt->pvt_key);
            if (Jaws_Error::isError($password)) {
                $password = '';
            }
        }

		require_once JAWS_PATH . 'include/Jaws/User.php';
        $model = new Jaws_User();
        $xss   = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        if ($GLOBALS['app']->Session->GetAttribute('user_id') == $uid) {
            $type    = null;
            $enabled = null;
        } else {
            $enabled = $enabled == 'true';
            if (!$GLOBALS['app']->Session->IsSuperAdmin()) {
                $enabled = null;
                $type = ((int)$type == 0)? null : $type;
            }
        }
        $res = $model->UpdateUser($uid,
                                  $xss->parse($username),
                                  $xss->parse($nickname),
                                  $xss->parse($email),
                                  $password,
                                  $type,
                                  $enabled);
        if (Jaws_Error::isError($res)) {
            $GLOBALS['app']->Session->PushLastResponse($res->getMessage(), RESPONSE_ERROR);
        } else {
            $GLOBALS['app']->Session->PushLastResponse(_t('USERS_USERS_UPDATED', $username), RESPONSE_NOTICE);
        }

        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Updates a group
     *
     * @access  public
     * @param   int    $guid        Group ID
     * @param   string $name        Group's name
     * @param   string $title       Groups's title
     * @param   string $description Groups's description
     * @param   integer $founder	Groups's founder (User ID)
     * @return  array  Response (notice or error)
     */
    function UpdateGroup($guid, $name, $title, $description, $founder = 0)
    {
        $this->CheckSession('Users', 'ManageGroups');
        require_once JAWS_PATH . 'include/Jaws/User.php';
        $model = new Jaws_User();
        $xss   = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');

        $res = $model->UpdateGroup($guid, $xss->parse($name), $title, $description, $founder);
        if ($res === false) {
            $GLOBALS['app']->Session->PushLastResponse(_t('USERS_GROUPS_NOT_UPDATED', $title), RESPONSE_ERROR);
        } else {
            $GLOBALS['app']->Session->PushLastResponse(_t('USERS_GROUPS_UPDATED', $title), RESPONSE_NOTICE);
        }

        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Delete an user
     *
     * @access  public
     * @param   int     $uid   User ID
     * @return  array   Response (notice or error)
     */
    function DeleteUser($uid)
    {
        $this->CheckSession('Users', 'ManageUsers');
        $currentUid = $GLOBALS['app']->Session->GetAttribute('user_id');

        if ($currentUid === $uid) {
            $GLOBALS['app']->Session->PushLastResponse(_t('USERS_USERS_CANT_DELETE_SELF'), RESPONSE_ERROR);
        } else {
            require_once JAWS_PATH . 'include/Jaws/User.php';
            $userModel = new Jaws_User();

            $profile = $userModel->GetUserInfoById($uid, true);
            if ($GLOBALS['app']->Session->IsSuperAdmin() === false && $profile['user_type'] == 0) {
                $GLOBALS['app']->Session->PushLastResponse(_t('USERS_USERS_CANT_DELETE', $profile['username']), RESPONSE_ERROR);
                return $GLOBALS['app']->Session->PopLastResponse();
            }

            if (!$userModel->DeleteUser($uid)) {
                $GLOBALS['app']->Session->PushLastResponse(_t('USERS_USERS_CANT_DELETE', $profile['username']), RESPONSE_ERROR);
            } else {
                $GLOBALS['app']->Session->PushLastResponse(_t('USERS_USER_DELETED', $profile['username']), RESPONSE_NOTICE);
            }
        }
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Deletes a group
     *
     * @access  public
     * @param   int     $guid   Group ID
     * @return  array   Response (notice or error)
     */
    function DeleteGroup($guid)
    {
        $this->CheckSession('Users', 'ManageGroups');
        $currentUid = $GLOBALS['app']->Session->GetAttribute('user_id');

        require_once JAWS_PATH . 'include/Jaws/User.php';
        $userModel = new Jaws_User();
        $groupinfo = $userModel->GetGroupInfoById($guid);

        if (!$userModel->DeleteGroup($guid)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('USERS_GROUPS_CANT_DELETE', $groupinfo['name']), RESPONSE_ERROR);
        } else {
            $GLOBALS['app']->Session->PushLastResponse(_t('USERS_GROUPS_DELETED', $groupinfo['name']), RESPONSE_NOTICE);
        }
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Saves only the modified ACL user keys
     *
     * @access  public
     * @param   int     $uid    User' ID
     * @param   array   $keys   ACL Keys
     * @return  array   Response (notice or error)
     */
    function SaveUserACL($uid, $keys)
    {
        $this->CheckSession('Users', 'ManageUserACLs');
        $res = $this->_Model->UpdateUserACL($uid, $keys);
        if (Jaws_Error::IsError($res)) {
            $GLOBALS['app']->Session->PushLastResponse($res->GetMessage(), RESPONSE_ERROR);
        } else {
            $GLOBALS['app']->Session->PushLastResponse(_t('USERS_USERS_ACL_UPDATED'), RESPONSE_NOTICE);
        }
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Resets all ACL keys assigned to an user
     *
     * @access  public
     * @param   int     $uid    User' ID
     * @return  array   Response (notice or error)
     */
    function ResetUserACL($uid)
    {
        $this->CheckSession('Users', 'ManageUserACLs');
        $res = $this->_Model->ResetUserACL($uid);
        if (Jaws_Error::IsError($res)) {
            $GLOBALS['app']->Session->PushLastResponse($res->GetMessage(), RESPONSE_ERROR);
        } else {
            $GLOBALS['app']->Session->PushLastResponse(_t('USERS_USERS_ACL_RESETED'), RESPONSE_NOTICE);
        }
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Save ACL keys of a group
     *
     * @access  public
     * @param   int     $guid   Group ID
     * @param   array   $keys   ACL Keys
     * @return  array   Response (notice or error)
     */
    function SaveGroupACL($guid, $keys)
    {
        $this->CheckSession('Users', 'ManageGroupACLs');
        $res = $this->_Model->UpdateGroupACL($guid, $keys);
        if (Jaws_Error::IsError($res)) {
            $GLOBALS['app']->Session->PushLastResponse($res->GetMessage(), RESPONSE_ERROR);
        } else {
            $GLOBALS['app']->Session->PushLastResponse(_t('USERS_GROUPS_ACL_UPDATED'), RESPONSE_NOTICE);
        }
        return $GLOBALS['app']->Session->PopLastResponse();
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
        $this->CheckSession('Users', 'ManageGroupACLs');
        $res = $this->_Model->ResetGroupACL($guid);
        if (Jaws_Error::IsError($res)) {
            $GLOBALS['app']->Session->PushLastResponse($res->GetMessage(), RESPONSE_ERROR);
        } else {
            $GLOBALS['app']->Session->PushLastResponse(_t('USERS_GROUPS_ACL_RESETED'), RESPONSE_NOTICE);
        }
        return $GLOBALS['app']->Session->PopLastResponse();
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
        $this->CheckSession('Users', 'ManageGroups');
        $res = $this->_Model->AddUsersToGroup($guid, $users);
        if (Jaws_Error::IsError($res)) {
            $GLOBALS['app']->Session->PushLastResponse($res->GetMessage(), RESPONSE_ERROR);
        } else {
            $GLOBALS['app']->Session->PushLastResponse(_t('USERS_GROUPS_UPDATED_USERS'), RESPONSE_NOTICE);
        }
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Add a user to a certain group
     *
     * @access  public
     * @param   int     $guid  Group's ID
     * @param   int   $user user id
     * @param   string   $status status
     * @return  array   Response (notice or error)
     */
    function AddUserToGroup($guid, $user, $status)
    {
        $this->CheckSession('Users', 'ManageGroups');
        $result = array();
        require_once JAWS_PATH . 'include/Jaws/User.php';
        $userModel = new Jaws_User();
        $res = $userModel->AddUserToGroup($user, $guid, $status);
		$result['success'] = false;
		if (Jaws_Error::IsError($res) || $res === false) {
			$GLOBALS['app']->Session->PushLastResponse(_t('USER_GROUP_CANT_UPDATE'), RESPONSE_ERROR);
        } else {
            $result['success'] = true;
            $result['id'] = $user;
            $GLOBALS['app']->Session->PushLastResponse(_t('USERS_GROUPS_UPDATED_USERS'), RESPONSE_NOTICE);
        }
        $result['message'] = $GLOBALS['app']->Session->PopLastResponse();
		return $result;
    }

    /**
     * Delete a user from a certain group
     *
     * @access  public
     * @param   int     $guid  Group's ID
     * @param   int   $user user id
     * @return  array   Response (notice or error)
     */
    function DeleteUserFromGroup($guid, $user)
    {
        $this->CheckSession('Users', 'ManageGroups');
        $result = array();
        require_once JAWS_PATH . 'include/Jaws/User.php';
        $userModel = new Jaws_User();
        $res = $userModel->DeleteUserFromGroup($user, $guid);
		$result['success'] = false;
        if (Jaws_Error::IsError($res) || $res === false) {
            $GLOBALS['app']->Session->PushLastResponse(_t('USER_GROUP_CANT_DELETE'), RESPONSE_ERROR);
        } else {
            $result['success'] = true;
            $result['id'] = $user;
            $GLOBALS['app']->Session->PushLastResponse(_t('USERS_GROUPS_UPDATED_USERS'), RESPONSE_NOTICE);
        }
        $result['message'] = $GLOBALS['app']->Session->PopLastResponse();
		return $result;
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
     * @return  array   Response (notice or error)
     */
    function SaveSettings($priority, $method, $anon, $repetitive, $act, $type, $group, $recover, 
		$gadgets, $protected_pages, $signup_requires_address, $social_sign_on)
    {
        $this->CheckSession('Users', 'ManageUsers');
        $res = $this->_Model->SaveSettings(
			$priority, $method, $anon, $repetitive, $act, $type, $group, $recover, 
			$gadgets, $protected_pages, $signup_requires_address, $social_sign_on
		);
        if (Jaws_Error::IsError($res)) {
            $GLOBALS['app']->Session->PushLastResponse($res->GetMessage(), RESPONSE_ERROR);
        } else {
            $GLOBALS['app']->Session->PushLastResponse(_t('USERS_PROPERTIES_UPDATED'), RESPONSE_NOTICE);
        }
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Returns an array with the ACL keys of a given user
     *
     * @access  public
     * @param   int     $uid    User's ID
     * @param   string  $gadget Gadget's name
     * @return  array   Array with ACL Keys
     */
    function GetUserACLKeys($uid)
    {
        $this->CheckSession('Users', 'ManageUserACLs');
        require_once JAWS_PATH . 'include/Jaws/User.php';
        $model = new Jaws_User();

        $profile = $model->GetUserInfoById($uid, true);
        if (isset($profile['username'])) {
            $acl = $this->_Model->GetUserACLKeys($profile['username']);
            return $acl;
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
        $this->CheckSession('Users', 'ManageGroupACLs');
        require_once JAWS_PATH . 'include/Jaws/User.php';
        $model = new Jaws_User();

        $profile = $model->GetGroupInfoById($guid);
        if (isset($profile['name'])) {
            $acl = $this->_Model->GetGroupACLKeys($guid);
            return $acl;
        }
        return false;
    }

    /**
     * Get a list of groups
     *
     * @access  public
     * @return  array    Groups list
     */
    function GetGroups()
    {
        $this->CheckSession('Users', 'default');
        require_once JAWS_PATH . 'include/Jaws/User.php';
        $model = new Jaws_User();

        $groups = $model->GetAllGroups('name');
        if (Jaws_Error::IsError($groups)) {
            return null;
        }

        $list = array();
        foreach ($groups as $group) {
            $list[] = array(
                'title' => $group['name'].' ('.$group['title'].')',
                'id'    => $group['id'],
            );
        }
        return $list;
    }

    /**
     * Get a list of groups of a user
     *
     * @access  public
     * @return  array    Groups list
     */
    function GetGroupsOfUser($uid = null)
    {
		$list = array();
        if ($GLOBALS['app']->Session->Logged()) {
			if (is_null($uid)) {
				$uid = $GLOBALS['app']->Session->GetAttribute('user_id');
			}
			require_once JAWS_PATH . 'include/Jaws/User.php';
			$model = new Jaws_User();

			$groups = $model->GetGroupsOfUser($uid);

			foreach ($groups as $group) {
				if ($group['group_name'] != 'users' && $group['group_name'] != 'profile' && $group['group_name'] != 'no_profile') {
					$list[] = array(
						'name' => $group['group_name'],
						'realname' => $group['group_title'],
						'id'   => $group['group_id'],
					);
				}
			}
			
			if ($GLOBALS['app']->Session->IsAdmin() || $GLOBALS['app']->Session->IsSuperAdmin()) {
				$groups = $model->GetAllGroups();
				if (count($groups) <= 0 && count($list) <= 0) {
					return $list;
				}
				foreach ($groups as $group) {
					$list[] = array(
						'name' => $group['name'],
						'realname' => $group['title'],
						'id'   => $group['id'],
					);
				}
			}
        }
		return $list;
    }
    
	/**
     * Get the information of a group
     *
     * @access  public
     * @param   int     $guid  Group's ID
     * @return  array   Group's information
     */
    function GetGroup($guid)
    {
        $this->CheckSession('Users', 'default');
        require_once JAWS_PATH . 'include/Jaws/User.php';
        $userModel = new Jaws_User();

        $profile = $userModel->GetGroupInfoById($guid);
        if (Jaws_Error::IsError($profile)) {
            return array();
        }
		$founder = $userModel->GetUsersOfGroupByStatus($guid, 'founder');
        if ($founder === false) {
            $profile['founder'] = '';
        } else {
			foreach ($founder as $user) {
				$profile['founder'] = $user['user_id'];
				break;
			}
		}
        return $profile;
    }

    /**
     * Updates my account
     *
     * @access  public
     * @param   string $username  Username
     * @param   string $password  Password
     * @param   string $nickname     User's display name
     * @param   string $email     User's email
     * @return  array  Response (notice or error)
     */
    function UpdateMyAccount($uid, $username, $password, $nickname, $email)
    {
        $this->CheckSession('Users', 'EditAccountInformation');

        if (trim($username) == '' || trim($nickname) == '' || trim($email) == '')
        {
            $GLOBALS['app']->Session->PushLastResponse(_t('USERS_MYACCOUNT_INCOMPLETE_FIELDS'), RESPONSE_ERROR);
            return $GLOBALS['app']->Session->PopLastResponse();
        }

        if ($GLOBALS['app']->Registry->Get('/crypt/enabled') == 'true') {
            require_once JAWS_PATH . 'include/Jaws/Crypt.php';
            $JCrypt = new Jaws_Crypt();
            $res = $JCrypt->Init();
            if (Jaws_Error::isError($res) || !$res) {
                $GLOBALS['app']->Session->PushLastResponse(_t('USERS_USERS_NOT_UPDATED'), RESPONSE_ERROR);
                return $GLOBALS['app']->Session->PopLastResponse();
            }

            $password = $JCrypt->rsa->decryptBinary($JCrypt->math->int2bin($password), $JCrypt->pvt_key);
            if (empty($password) || Jaws_Error::isError($password)) {
                $password = '';
            }
        }
        
        require_once JAWS_PATH . 'include/Jaws/User.php';
        $userModel = new Jaws_User();
        $xss       = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');

        $res = $userModel->UpdateUser($uid,
                                      $xss->parse($username),
                                      $xss->parse($nickname),
                                      $xss->parse($email),
                                      $password);
        if (Jaws_Error::isError($res)) {
            $GLOBALS['app']->Session->PushLastResponse($res->getMessage(), RESPONSE_NOTICE);
        } else {
            $GLOBALS['app']->Session->PushLastResponse(_t('USERS_MYACCOUNT_UPDATED'), RESPONSE_NOTICE);
        }

        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Returns the user form
     *
     * @access  public
     * @return  string  XHTML of userForm
     */
    function GetUserForm()
    {
        $this->CheckSession('Users', 'default');
        $gadget = $GLOBALS['app']->LoadGadget('Users', 'AdminHTML');
        return $gadget->EditUser();
    }

    /**
     * Returns the group form
     *
     * @access  public
     * @return  string  XHTML of groupForm
     */
    function GetGroupForm()
    {
        $this->CheckSession('Users', 'default');
        $gadget = $GLOBALS['app']->LoadGadget('Users', 'AdminHTML');
        return $gadget->EditGroup();
    }

    /**
     * Get the user-group form
     *
     * @access  public
     * @param   int     $guid    Group ID
     * @return  string
     */
    function GetUserGroupsForm($guid)
    {
        $this->CheckSession('Users', 'default');
        $gadget = $GLOBALS['app']->LoadGadget('Users', 'AdminHTML');
        return $gadget->GetUserGroupUI($guid);
    }

    /**
     * Returns the UI for the personal information for users
     *
     * @access  public
     * @param   int     $uid     User ID
     * @return  string
     */
    function GetPersonalInformationUI($uid)
    {
        $this->CheckSession('Users', 'default');
        $gadget = $GLOBALS['app']->LoadGadget('Users', 'AdminHTML');
        return $gadget->GetPersonalInformationUI($uid);
    }
    
    /**
     * Returns the UI for the advanced options for users
     *
     * @access  public
     * @param   int     $uid     User ID
     * @return  string
     */
    function GetAdvUserOptionsUI($uid)
    {
        $this->CheckSession('Users', 'default');
        $gadget = $GLOBALS['app']->LoadGadget('Users', 'AdminHTML');
        return $gadget->GetAdvancedUserOptionsUI($uid);
    }

    /**
     * Update personal information of selected user
     *
     * @access  public
     * @param   int     $uid     User ID
     * @param   string  $lang    User language
     * @param   string  $theme   User theme
     * @param   string  $editor  User editor
     * @return  array  Response (notice or error)
     */
    function UpdatePersonalInfo($uid, $fname, $lname, $gender, $dob_year, $dob_month, $dob_day, $url,
						   $company = '', $address = '', $address2 = '', 
						   $city = '', $country = '', $region = '', 
						   $postal = '', $phone = '', $office = '', 
						   $tollfree = '', $fax = '', $merchant_id = '', 
						   $description = '', $logo = '', $keywords = '', 
						   $company_type = '')
    {
        $this->CheckSession('Users', 'default');

        $dob  = null;
        if (!empty($dob_year) && !empty($dob_year) && !empty($dob_year)) {
            $date = $GLOBALS['app']->loadDate();
            $dob  = $date->ToBaseDate($dob_year, $dob_month, $dob_day);
            $dob  = date('Y-m-d H:i:s', $dob['timestamp']);
        }

        require_once JAWS_PATH . 'include/Jaws/User.php';
        $model = new Jaws_User();
        $pInfo = array('fname'  		=> $fname,
                       'lname'  		=> $lname,
                       'gender' 		=> $gender,
                       'dob'    		=> $dob,
                       'url'    		=> $url,
					   'company'    	=> $company, 
					   'address'    	=> $address, 
					   'address2'    	=> $address2, 
					   'city'    		=> $city, 
					   'country'    	=> $country, 
					   'region'    		=> $region, 
					   'postal'    		=> $postal, 
					   'phone'    		=> $phone, 
					   'office'    		=> $office, 
					   'tollfree'    	=> $tollfree, 
					   'fax'    		=> $fax, 
					   'merchant_id'    => $merchant_id, 
					   'description'    => $description, 
					   'logo'    		=> $logo, 
					   'keywords'    	=> $keywords, 
					   'company_type'   => $company_type);
       $res = $model->UpdatePersonalInfo($uid, $pInfo);
        if ($res === false) {
            $GLOBALS['app']->Session->PushLastResponse(_t('USERS_USERS_PERSONALINFO_NOT_UPDATED'), RESPONSE_ERROR);
        } else {
            $GLOBALS['app']->Session->PushLastResponse(_t('USERS_USERS_PERSONALINFO_UPDATED'), RESPONSE_NOTICE);
        }
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Save advanced options of selected user
     *
     * @access  public
     * @param   int     $uid     User ID
     * @param   string  $lang    User language
     * @param   string  $theme   User theme
     * @param   string  $editor  User editor
     * @return  array  Response (notice or error)
     */
    function SaveAdvancedOptions($uid, $lang, $theme, $editor, $timezone,
								 $notification = '', $allow_comments = true, 
								 $identifier = null)
    {
        $this->CheckSession('Users', 'default');

        if ($lang == '-default-') {
            $lang = null;
        }

        if ($theme == '-default-') {
            $theme = null;
        }

        if ($editor == '-default-') {
            $editor = null;
        }

        if ($timezone == '-default-') {
            $timezone = null;
        }

        $pInfo = array('language' 		=> $lang, 
                       'theme' 			=> $theme, 
                       'editor' 		=> $editor, 
                       'timezone' 		=> $timezone,
					   'notification' 	=> $notification, 
					   'allow_comments' => $allow_comments, 
					   'identifier' 	=> $identifier);
        require_once JAWS_PATH . 'include/Jaws/User.php';
        $model = new Jaws_User();
        $res = $model->UpdateAdvancedOptions($uid, $pInfo);
        if ($res === false) {
            $GLOBALS['app']->Session->PushLastResponse(_t('USERS_USERS_NOT_ADVANCED_UPDATED'), RESPONSE_ERROR);
        } else {
            $GLOBALS['app']->Session->PushLastResponse(_t('USERS_USERS_ADVANCED_UPDATED'), RESPONSE_NOTICE);
        }
        return $GLOBALS['app']->Session->PopLastResponse();
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
    function UpdateUsersGadgets($id, $gadget = null, $pane_action = null, $sort_order = null)
    {
        $this->CheckSession('Users', 'default');
        require_once JAWS_PATH . 'include/Jaws/User.php';
        $model = new Jaws_User();

        $result = $model->UpdateUsersGadgets($id, $gadget, $pane_action, $sort_order);
        if (!$result) {
			$GLOBALS['app']->Session->PushLastResponse(_t('USERS_ACCOUNTHOME_PANE_NOT_UPDATED'), RESPONSE_ERROR);
			return $GLOBALS['app']->Session->PopLastResponse();
			//return false;
        }
    }

	/**
     * Deletes a gadget subscription
     *
     * @access  public
     * @param   string  $gadget name of gadget to update
     * @param   int  $id subscription id
     * @return  boolean Returns true if subscription was successfully deleted, error if not
     */
    function DeleteSubscription($gadget, $id)
    {
		if (!$gadget || !$id) {
			$GLOBALS['app']->Session->PushLastResponse(_t('USERS_ACCOUNTHOME_SUBSCRIPTION_NOT_DELETED'), RESPONSE_ERROR);
			return $GLOBALS['app']->Session->PopLastResponse();
			//return false;
		}
		$this->CheckSession('Users', 'default');
        require_once JAWS_PATH . 'include/Jaws/User.php';
        $model = new Jaws_User();

        $result = $model->DeleteSubscription($gadget, $id);
        if (!$result) {
			$GLOBALS['app']->Session->PushLastResponse(_t('USERS_ACCOUNTHOME_SUBSCRIPTION_NOT_DELETED'), RESPONSE_ERROR);
			return $GLOBALS['app']->Session->PopLastResponse();
			//return false;
        }
    }
	
    /**
     * Get total users_gadgets of a search
     *
     * @access  public
     * @param   string  $status  Status of user(s) we want to display
     * @param   string  $search  Keyword (title/description) of users we want to look for
     * @return  int     Total of posts
     */
    function SizeOfSearch($status, $search)
    {
        $this->CheckSession('Users', 'default');
        $pages = $this->_Model->SearchUsersGadgets($status, $search, null);
        return count($pages);
    }

    /**
     * Returns an array with all the users gadgets records
     *
     * @access  public
     * @param   string  $status  Status of user(s) we want to display
     * @param   string  $search  Keyword (title/description) of users we want to look for
     * @param   int     $limit   Data limit
     * @return  array   Galleries data
     */
    function SearchUsersGadgets($status, $search, $limit)
    {
        $this->CheckSession('Users', 'default');
        $gadget = $GLOBALS['app']->LoadGadget('Users', 'AdminHTML');
        if (!is_numeric($limit)) {
            $limit = 0;
        }
        return $gadget->GetUsersGadgets($status, $search, $limit);
    }

    /**
     * Get pages of given XML
     *
     * @access  public
     * @params  string  $gadget
     * @return  array   Actions of the given gadget
     */
    function GetGadgetActions($gadget, $search, $gadget_url)
    {
        $this->CheckSession('Users', 'default');
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');
		$model = $GLOBALS['app']->LoadGadget('Users', 'Model');
		$res = array();
		if ($gadget == "showcase") {
			// snoopy
			include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
			$snoopy = new Snoopy('Users');
			$fetch_url = "http://showcasere.com/".$search."/google_sitemap.php";
			if($snoopy->fetch($fetch_url)) {
				$xml_content = $snoopy->results;
			} else {
				$GLOBALS['app']->Session->PushLastResponse("Couldn't open XML.", RESPONSE_ERROR);
				return $GLOBALS['app']->Session->PopLastResponse();
			}
			
			// XML Parser
			include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'XMLParser.php';
			$xml_parser = new XMLParser;
			$xml_result = $xml_parser->parse($xml_content);
			
			for ($i=0;$i<$xml_result[1]; $i++)
			{
				if (isset($xml_result[0][$i]["LOC"])) {
					$f = $GLOBALS['app']->UTF8->strxchr($xml_result[0][$i]["LOC"], "/", 0, 1);
					$target_url = $xml_result[0][$i]["LOC"];
					$url = (strpos($target_url, 'https') ? substr($target_url, 8, strlen($target_url)) : substr($target_url, 7, strlen($target_url)));
					$url = substr($url, strpos($url, "/"), strlen($url));
					$url = substr($url, 0, strrpos($url, "/"));
					$url = substr($url, 0, strrpos($url, "/"));
					$checked = false;
					$row = $model->GetEmbedGadgetsByUrl($url);
					// Check embedded gadgets so we can give user option to un-embed them
					if ($row) {
						foreach($row as $embed) {
							if (($GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), $GLOBALS['app']->Session->GetAttribute('groups'), 'Users', 'default') || $embed['ownerid'] == $GLOBALS['app']->Session->GetAttribute('user_id')) && $embed['url'] == $url && $embed['gadget_url'] == $gadget_url) {
								$checked = true;
							}
						}
					}			
					$res[] = array('id' => 'showcase', 
								   'name'   => str_replace('_', ' ', substr($f[0], strrpos($f[0], '/'), strlen($f[0]))),
								   'url'   => $url,
								   'checked'   => $checked
					);
				}
			}
		}
        return $res;
    }

	/**
     * Deletes an embedded gadget
     *
     * @access  public
     * @param   string     $url     The url of the page it's embedded on.
     * @param   string     $gadget_url     The gadget url of the embedded gadget.
     * @return  boolean Returns true if embedded gadget was successfully deleted, error if not
     */
    function DeleteEmbed($url, $gadget_url)
    {
		$this->CheckSession('Users', 'default');
		if (!$gadget_url || !$url) {
			$GLOBALS['app']->Session->PushLastResponse(_t('USERS_EMBED_ERROR_NOT_DELETED'), RESPONSE_ERROR);
			return $GLOBALS['app']->Session->PopLastResponse();
			//return false;
		}

        $result = $this->_Model->DeleteEmbed($url, $gadget_url);
		if (!$result) {
			$GLOBALS['app']->Session->PushLastResponse(_t('USERS_EMBED_ERROR_NOT_DELETED'), RESPONSE_ERROR);
			return $GLOBALS['app']->Session->PopLastResponse();
			//return false;
		}
        $GLOBALS['app']->Session->PushLastResponse(_t('USERS_EMBED_DELETED'), RESPONSE_NOTICE);
		return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Get Quick Add Forms of Gadget
     *
     * @access public
     * @param string	$method	The method to call
     * @param array	$params	The params to pass to method
     * @param string	$callback	The method to call afterwards
     * @return  array	Response (notice or error)
     */
    function GetQuickAddForms($gadget = '', $account = false) 
    {
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		if (!$GLOBALS['app']->Session->Logged()) {
	        //require_once JAWS_PATH . 'include/Jaws/Header.php';
			//Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'));
			$GLOBALS['app']->Session->PushLastResponse("User not logged in.", RESPONSE_ERROR);
        } else {
			$res = array();
			// Action is for specific ID
			if (!empty($gadget)) {
				$hook = $GLOBALS['app']->loadHook($gadget, 'URLList');
				if ($hook !== false) {
					if (method_exists($hook, 'GetQuickAddForms')) {
						$forms = $hook->GetQuickAddForms($account);
						if ($forms !== false) {
							$i = 0;
							foreach ($forms as $form) {
								$res[$i]['name'] = $form['name'];
								$res[$i]['method'] = $form['method'];
								$i++;
							}
						}
					}
				}
			}
			return $res;
		}
		return false;
	}

	/**
     * Adds a comment
     *
     * @access  public
     * @param   string  $title      Title of the comment
     * @param   string  $comments   Text of the comment
     * @param   int     $parent     ID of the parent comment
     * @param   int     $parentId   ID of the entry
     * @param   string  $ip         IP of the author
     * @param   boolean $set_cookie Create a cookie
     * @return  boolean True if comment was added, and false if not.
     */
	function NewComment(
		$title = '', $comments, $parent, $parentId, $OwnerID = '', $ip = '', $set_cookie = true, $sharing = 'everyone', 
		$gadget = 'Users', $auto = false, $save = true, $permalink = '', $mail = true
	) {
        $res = array();
		if (!$GLOBALS['app']->Session->Logged()) {
	        //require_once JAWS_PATH . 'include/Jaws/Header.php';
			//Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'));
			$GLOBALS['app']->Session->PushLastResponse("User not logged in.", RESPONSE_ERROR);
		} else {
			$viewer_id = (int)$GLOBALS['app']->Session->GetAttribute('user_id');
			require_once JAWS_PATH . 'include/Jaws/User.php';
			$jUser = new Jaws_User();
			if (($GLOBALS['app']->Session->IsAdmin() || $GLOBALS['app']->Session->IsSuperAdmin()) && $OwnerID == '0') {
				$OwnerID = (int)$OwnerID;
				$name = $GLOBALS['app']->Registry->Get('/config/site_name');
				$url = $GLOBALS['app']->GetSiteURL();
				if (empty($name)) {
					$name = str_replace(array('http://', 'https://'), '', $url);
				}
				$email = $GLOBALS['app']->Registry->Get('/network/site_email');
				if (empty($parentId)) {
					$parentId = 0;
				}
			} else {
				if (empty($parentId)) {
					$parentId = $GLOBALS['app']->Session->GetAttribute('user_id');
				}
				$OwnerID = (int)$GLOBALS['app']->Session->GetAttribute('user_id');
				$info = $jUser->GetUserInfoById($OwnerID, true, true, true, true);
				$name = (!empty($info['company']) ? $info['company'] : $info['nickname']);
				$url = $info['url'];
				$email = $info['email'];
			}
			$model = $GLOBALS['app']->LoadGadget('Users', 'Model');
			$result = $model->NewComment(
				$name, $title, $url, $email, $comments, 
				(int)$parent, (int)$parentId, $ip, $set_cookie, $OwnerID, $sharing, 
				$gadget, $auto, $save, $permalink, $mail
			);
			if (Jaws_Error::IsError($result)) {
				$res['css'] = 'error-message';
				$res['message'] = $result->GetMessage();
			} else {
				$res['css'] = 'notice-message';
				$res['message'] = _t('GLOBAL_COMMENT_ADDED');
				$res['id'] = $result['id'];
				$res['link'] = $result['link'];
				if ($OwnerID == 0) {
					if (file_exists(JAWS_DATA . 'files/css/icon.png')) {
						$result['image'] = $GLOBALS['app']->getDataURL('', true). 'files/css/icon.png';
					} else if (file_exists(JAWS_DATA . 'files/css/logo.png')) {
						$result['image'] = $GLOBALS['app']->getDataURL('', true). 'files/css/logo.png';
					} else {
						$result['image'] = $jUser->GetAvatar('');
					}
				}
				if ((int)$parent == 0) {
					$res['image'] = (!empty($result['link']) ? '<a href="'.$result['link'].'">' : '').'<img src="'.$result['avatar_source'].'" border="0" align="left" />'.(!empty($result['link']) ? '</a>' : '');
					if (!empty($result['image'])) {
						$res['image'] = (!empty($result['link']) ? '<a href="'.$result['link'].'">' : '').'<img src="'.$result['image'].'" border="0" align="left" />'.(!empty($result['link']) ? '</a>' : '');
					}
				} else {
					$res['image'] = '<div class="comment-image-holder">'.(!empty($result['link']) ? '<a href="'.$result['link'].'">' : '').'<img src="'.$result['avatar_source'].'" border="0" align="left" class="comment-image" />'.(!empty($result['link']) ? '</a>' : '').'</div>';
					if (!empty($result['image'])) {
						$res['image'] = '<div class="comment-image-holder">'.(!empty($result['link']) ? '<a href="'.$result['link'].'">' : '').'<img src="'.$result['image'].'" border="0" align="left" class="comment-image" />'.(!empty($result['link']) ? '</a>' : '').'</div>';
					}
				}
				$res['name'] = $result['name'];
				$full_style = '';
				$preview_style = ' style="display: none;"';
				//$msg_reply = strip_tags($result['comment']);
				$msg_reply = $result['comment'];
				$msg_reply_preview = '';
				/*
				if (strlen($msg_reply) > 150) {
					$msg_reply_preview = substr($msg_reply, 0, 150).'&nbsp;<a class="comment-showhide" href="javascript:void(0);" onclick="toggleFullComment('.$result['id'].');">Read it</a>';
					$msg_reply .= '&nbsp;<a class="comment-showhide" href="javascript:void(0);" onclick="toggleFullComment('.$result['id'].');">Hide it</a>';
					$preview_style = '';
					$full_style = ' style="display: none;"';
				}
				*/
				$res['full_style'] = $full_style;
				$res['preview_style'] = $preview_style;
				$res['comment'] = $msg_reply;
				$res['preview_comment'] = $msg_reply_preview;
				$res['title'] = $result['title'];
				$res['created'] = $result['created'];
				$res['permalink'] = $result['permalink'];
				// Let everyone know
				$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
				$shout = $GLOBALS['app']->Shouter->Shout('onBeforeSocialSharing', array('url' => $result['permalink']));
				if (!Jaws_Error::IsError($shout) && (isset($shout['url']) && !empty($shout['url']))) {
					$res['permalink'] = $shout['url'];
				}
				// Shared with specific users? Show them...
				$share_activity = '';
				$share_users = (strpos($sharing, 'users:') !== false ? explode(',', str_replace('users:', '', $sharing)) : array());
				$count_share_users = count($share_users);
				reset($share_users);
				$share_count = 1;
				if ($count_share_users < 4) {
					foreach ($share_users as $share) {
						//if ((int)$share != $viewer_id) {
							$shareInfo = $jUser->GetUserInfoById((int)$share, true, true, true, true);
							if (
								!Jaws_Error::IsError($shareInfo) && isset($shareInfo['id']) && !empty($shareInfo['id']) && 
								$jUser->GetStatusOfUserInFriend($viewer_id, (int)$shareInfo['id']) != 'blocked'
							) {
								if (!empty($shareInfo['company'])) {
									$share_user_name = (strlen($xss->filter(strip_tags($shareInfo['company']))) > 100 ? substr($xss->filter(strip_tags($shareInfo['company'])), 0, 100).'...' : $xss->filter(strip_tags($shareInfo['company'])));
									$share_user_fullname = $xss->filter(strip_tags($shareInfo['company']));
								} else {
									$share_user_name = (strlen($xss->filter(strip_tags($shareInfo['nickname']))) > 100 ? substr($xss->filter(strip_tags($shareInfo['nickname'])), 0, 100).'...' : $xss->filter(strip_tags($shareInfo['nickname'])));
									$share_user_fullname = $xss->filter(strip_tags($shareInfo['nickname']));
								}
								// Check if user is in profile group
								$share_show_link = false;
								$share_groups  = $jUser->GetGroupsOfUser($shareInfo['id']);
								if (!Jaws_Error::IsError($share_groups)) {
									foreach ($share_groups as $share_group) {
										if (
											strtolower($share_group['group_name']) == 'profile' && 
											in_array($share_group['group_status'], array('active','founder','admin'))
										) {
											$share_show_link = true;
											break;
										}
									}
								}
								$share_user_link = '';
								$share_user_link_start = '';
								$share_user_link_end = '';
								if ($share_show_link === true) {
									$share_user_link = $GLOBALS['app']->Map->GetURLFor('Users', 'AccountPublic', array('name' => $shareInfo['username']));
									$share_user_link_start = '<a href="'.$GLOBALS['app']->GetSiteURL().'/'.$GLOBALS['app']->Map->GetURLFor('Users', 'AccountPublic', array('name' => $shareInfo['username'])).'" title="'.$share_user_fullname.'" name="'.$share_user_fullname.'">';
									$share_user_link_end = '</a>';
								}
								$share_activity .= (!empty($share_activity) ? ', ' : "&nbsp;<span style=\"display: inline; font-weight: bold; color: #666666;\">&gt;</span>&nbsp;&nbsp;").$share_user_link_start.$share_user_name.$share_user_link_end;
								$share_count++;
							}
						//}
					}
				}
				$res['preactivity'] = $share_activity;
				$res['activity'] = '';
			}
		}
		return $res;
    }
	
	/**
     * Adds a comment
     *
     * @access  public
     * @param   string  $title      Title of the comment
     * @param   string  $comments   Text of the comment
     * @param   int     $parent     ID of the parent comment
     * @param   int     $parentId   ID of the entry
     * @param   string  $ip         IP of the author
     * @param   boolean $set_cookie Create a cookie
     * @return  boolean True if comment was added, and false if not.
     */
    function NewStatus($title = '', $comments, $parent, $parentId = '', $ip = '', $set_cookie = true, $sharing = 'everyone')
    {
        $res = array();
		if (!$GLOBALS['app']->Session->Logged()) {
	        //require_once JAWS_PATH . 'include/Jaws/Header.php';
			//Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'));
			$GLOBALS['app']->Session->PushLastResponse("User not logged in.", RESPONSE_ERROR);
		} else {
			if (empty($parentId)) {
				$parentId = $GLOBALS['app']->Session->GetAttribute('user_id');
			}
			require_once JAWS_PATH . 'include/Jaws/User.php';
			$jUser = new Jaws_User();
			$info = $jUser->GetUserInfoById((int)$GLOBALS['app']->Session->GetAttribute('user_id'), true, true, true, true);
			$model = $GLOBALS['app']->LoadGadget('Users', 'Model');
			$result = $model->NewComment(
				(!empty($info['company']) ? $info['company'] : $info['nickname']), $title, $info['url'], $info['email'], $comments, 
				(int)$parent, (int)$parentId, $ip, $set_cookie, (int)$GLOBALS['app']->Session->GetAttribute('user_id'), $sharing
			);
			if (Jaws_Error::IsError($result)) {
				$res['css'] = 'error-message';
				$res['message'] = $result->GetMessage();
			} else {
				$res['css'] = 'notice-message';
				$res['message'] = _t('GLOBAL_COMMENT_ADDED');
				$res['id'] = $result['id'];
				$res['link'] = $result['link'];
				if ((int)$parent == 0) {
					$res['image'] = (!empty($result['link']) ? '<a href="'.$result['link'].'">' : '').'<img src="'.$result['avatar_source'].'" border="0" align="left" />'.(!empty($result['link']) ? '</a>' : '');
					if (!empty($result['image']) && file_exists(JAWS_DATA . 'files'.$result['image'])) {
						$res['image'] = (!empty($result['link']) ? '<a href="'.$result['link'].'">' : '').'<img src="'.$GLOBALS['app']->getDataURL('', true) . 'files'.$result['image'].'" border="0" align="left" />'.(!empty($result['link']) ? '</a>' : '');
					}
				} else {
					$res['image'] = '<div class="comment-image-holder">'.(!empty($result['link']) ? '<a href="'.$result['link'].'">' : '').'<img src="'.$result['avatar_source'].'" border="0" align="left" class="comment-image" />'.(!empty($result['link']) ? '</a>' : '').'</div>';
					if (!empty($result['image']) && file_exists(JAWS_DATA . 'files'.$result['image'])) {
						$res['image'] = '<div class="comment-image-holder">'.(!empty($result['link']) ? '<a href="'.$result['link'].'">' : '').'<img src="'.$GLOBALS['app']->getDataURL('', true) . 'files'.$result['image'].'" border="0" align="left" class="comment-image" />'.(!empty($result['link']) ? '</a>' : '').'</div>';
					}
				}
				$res['name'] = $result['name'];
				$full_style = '';
				$preview_style = ' style="display: none;"';
				$msg_reply = strip_tags($result['comment']);
				$msg_reply_preview = '';
				if (strlen($msg_reply) > 150) {
					$msg_reply_preview = substr($msg_reply, 0, 150).'&nbsp;<a class="comment-showhide" href="javascript:void(0);" onclick="toggleFull'.((int)$parent == 0 ? 'Update' : 'Comment').'('.$result['id'].');">Read it</a>';
					$msg_reply .= '&nbsp;<a class="comment-showhide" href="javascript:void(0);" onclick="toggleFull'.((int)$parent == 0 ? 'Update' : 'Comment').'('.$result['id'].');">Hide it</a>';
					$preview_style = '';
					$full_style = ' style="display: none;"';
				}
				$res['full_style'] = $full_style;
				$res['preview_style'] = $preview_style;
				$res['comment'] = $msg_reply;
				$res['preview_comment'] = $msg_reply_preview;
				$res['title'] = $result['title'];
				$res['created'] = $result['created'];
				$res['permalink'] = $result['permalink'];
				$res['activity'] = '';
			}
		}
		return $res;
    }

	/**
     * Adds a comment
     *
     * @access  public
     * @param   string  $title      Title of the comment
     * @param   string  $comments   Text of the comment
     * @param   int     $parent     ID of the parent comment
     * @param   int     $parentId   ID of the entry
     * @param   string  $ip         IP of the author
     * @param   boolean $set_cookie Create a cookie
     * @return  boolean True if comment was added, and false if not.
     */
    function NewPhoto(
		$title = '', $comments, $parent, $parentId = '', $OwnerID = '', $ip = '', 
		$set_cookie = true, $sharing = 'everyone', $image = '', 
		$url_type = 'imageviewer', $internal_url = '', $external_url = '', 
		$url_target = '_self', $gadget = 'Users'
	) {
        $res = array();
		if (!$GLOBALS['app']->Session->Logged()) {
	        //require_once JAWS_PATH . 'include/Jaws/Header.php';
			//Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'));
			$GLOBALS['app']->Session->PushLastResponse("User not logged in.", RESPONSE_ERROR);
		} else {
			$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
			$model = $GLOBALS['app']->LoadGadget('Users', 'Model');
			require_once JAWS_PATH . 'include/Jaws/Image.php';
			require_once JAWS_PATH . 'include/Jaws/User.php';
			$jUser = new Jaws_User();
			$viewer_id = (int)$GLOBALS['app']->Session->GetAttribute('user_id');
			if (($GLOBALS['app']->Session->IsAdmin() || $GLOBALS['app']->Session->IsSuperAdmin()) && $OwnerID == '0') {
				$OwnerID = 0;
				$name = $GLOBALS['app']->Registry->Get('/config/site_name');
				$url = $GLOBALS['app']->GetSiteURL();
				if (empty($name)) {
					$name = str_replace(array('http://', 'https://'), '', $url);
				}
				$email = $GLOBALS['app']->Registry->Get('/network/site_email');
				if (empty($parentId)) {
					$parentId = 0;
				}
			} else {
				if (empty($parentId)) {
					$parentId = $GLOBALS['app']->Session->GetAttribute('user_id');
				}
				$OwnerID = (int)$GLOBALS['app']->Session->GetAttribute('user_id');
				$info = $jUser->GetUserInfoById($OwnerID, true, true, true, true);
				$name = (!empty($info['company']) ? $info['company'] : $info['nickname']);
				$url = $info['url'];
				$email = $info['email'];
			}
			
			if (!empty($image)) {
				$image = $model->cleanImagePath($image);
				if (
					$OwnerID > 0 && 
					(substr(strtolower(trim($image)), 0, 4) == 'http' || 
					substr(strtolower(trim($image)), 0, 2) == '//' || 
					substr(strtolower(trim($image)), 0, 2) == '\\\\')
				) {
					$image = '';
				}
			}
			
			$photo_url = '';
			$url_target = !empty($url_target) ? $xss->parse($url_target) : '';

			if (
				$url_type == 'external' && 
				(substr(strtolower(trim($external_url)), 0, 4) == 'http') && 
				strpos(strtolower(trim($external_url)), 'javascript:') === false
			) {
				$photo_url = $xss->parse($external_url);
			} else if ($url_type == 'internal' && !empty($internal_url) && strpos(strtolower(trim($internal_url)), 'javascript:') === false) {
				$photo_url = $xss->parse($internal_url);
			}
		
			//$photo_title = (strlen($xss->parse(strip_tags($photo_title))) > 255 ? substr($xss->parse(strip_tags($photo_title)), 0, 255).'...' : $xss->parse(strip_tags($photo_title)));
			$comments = $xss->parse(strip_tags(Jaws_Gadget::ParseText($comments, 'Users')));
			$safe_title = $xss->parse(preg_replace("[^A-Za-z0-9\ ]", '', $comments));

			$main_image_src = '';
			if (isset($image) && !empty($image)) {
				$image = $xss->parse(strip_tags($image));
				if (substr(strtolower($image), 0, 4) == "http") {
					$main_image_src = $image;
					if (substr(strtolower($image), 0, 7) == "http://") {
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
					if (strpos(strtolower($main_image_src), 'data/files/') !== false) {
						$main_image_src = 'image_thumb.php?uri='.urlencode($main_image_src);
					}
				} else {
					$thumb = Jaws_Image::GetThumbPath($image);
					$medium = Jaws_Image::GetMediumPath($image);
					if (file_exists(JAWS_DATA . 'files'.$thumb)) {
						$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$thumb;
					} else if (file_exists(JAWS_DATA . 'files'.$medium)) {
						$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$medium;
					} else if (file_exists(JAWS_DATA . 'files'.$image)) {
						$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$image;
					}
				}
			}
			if (empty($photo_url)) {
				$photo_url = 'javascript:void(0);" onclick="window.open(\''.$main_image_src.'\');';
			}
			$tpl = new Jaws_Template('gadgets/Users/templates/');
			$tpl->Load('CommentPhoto.html');
			$tpl->SetBlock('comment');
			$tpl->SetVariable('photo_id', ($parentId == 0 ? microtime() : $parentId));
			$tpl->SetVariable('photo_description', $comments);
			/*
			if (!empty($photo_title)) {
				$tpl->SetBlock('comment/title');
				$tpl->SetVariable('photo_url', $photo_url);
				$tpl->SetVariable('photo_title', $photo_title);
				$tpl->SetVariable('safe_title', $safe_title);
				$tpl->ParseBlock('comment/title');
			}
			*/
			if (!empty($main_image_src)) {
				$tpl->SetBlock('comment/image');
				$tpl->SetVariable('photo_id', ($parentId == 0 ? microtime() : $parentId));
				$tpl->SetVariable('photo_url', $photo_url);
				$tpl->SetVariable('safe_title', $safe_title);
				$tpl->SetVariable('icon', $main_image_src);
				$tpl->ParseBlock('comment/image');
			}
			$tpl->ParseBlock('comment');
			
			$comments = $tpl->Get();
			
			$result = $model->NewComment(
				$name, $title, $url, $email, $comments, (int)$parent, 
				(int)$parentId, $ip, $set_cookie, $OwnerID, $sharing, 
				$gadget, (!empty($main_image_src) ? true : false)
			);
			if (Jaws_Error::IsError($result)) {
				$res['css'] = 'error-message';
				$res['message'] = $result->GetMessage();
			} else {
				$res['messages'] = array();
				$res['css'] = 'notice-message';
				$res['message'] = _t('GLOBAL_COMMENT_ADDED');
				$res['id'] = $result['id'];
				$res['link'] = $result['link'];
				if ($OwnerID == 0) {
					if (file_exists(JAWS_DATA . 'files/css/icon.png')) {
						$result['image'] = $GLOBALS['app']->getDataURL('', true). 'files/css/icon.png';
					} else if (file_exists(JAWS_DATA . 'files/css/logo.png')) {
						$result['image'] = $GLOBALS['app']->getDataURL('', true). 'files/css/logo.png';
					} else {
						$result['image'] = $jUser->GetAvatar('');
					}
				}
				if ((int)$parent == 0) {
					$res['image'] = (!empty($result['link']) ? '<a href="'.$result['link'].'">' : '').'<img src="'.$result['avatar_source'].'" border="0" align="left" />'.(!empty($result['link']) ? '</a>' : '');
					if (!empty($result['image'])) {
						$res['image'] = (!empty($result['link']) ? '<a href="'.$result['link'].'">' : '').'<img src="'.$result['image'].'" border="0" align="left" />'.(!empty($result['link']) ? '</a>' : '');
					}
				} else {
					$res['image'] = '<div class="comment-image-holder">'.(!empty($result['link']) ? '<a href="'.$result['link'].'">' : '').'<img src="'.$result['avatar_source'].'" border="0" align="left" class="comment-image" />'.(!empty($result['link']) ? '</a>' : '').'</div>';
					if (!empty($result['image'])) {
						$res['image'] = '<div class="comment-image-holder">'.(!empty($result['link']) ? '<a href="'.$result['link'].'">' : '').'<img src="'.$result['image'].'" border="0" align="left" class="comment-image" />'.(!empty($result['link']) ? '</a>' : '').'</div>';
					}
				}
				$res['name'] = $result['name'];
				$full_style = '';
				$preview_style = ' style="display: none;"';
				$msg_reply = $result['comment'];
				$msg_reply_preview = '';
				/*
				if (strlen($msg_reply) > 150) {
					$msg_reply_preview = substr($msg_reply, 0, 150).'&nbsp;<a class="comment-showhide" href="javascript:void(0);" onclick="toggleFull'.((int)$parent == 0 ? 'Update' : 'Comment').'('.$result['id'].');">Read it</a>';
					$msg_reply .= '&nbsp;<a class="comment-showhide" href="javascript:void(0);" onclick="toggleFull'.((int)$parent == 0 ? 'Update' : 'Comment').'('.$result['id'].');">Hide it</a>';
					$preview_style = '';
					$full_style = ' style="display: none;"';
				}
				*/
				$res['full_style'] = $full_style;
				$res['preview_style'] = $preview_style;
				$res['comment'] = $msg_reply;
				$res['preview_comment'] = $msg_reply_preview;
				$res['title'] = $result['title'];
				$res['created'] = $result['created'];
				$res['permalink'] = $result['permalink'];
				// Let everyone know
				$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
				$shout = $GLOBALS['app']->Shouter->Shout('onBeforeSocialSharing', array('url' => $result['permalink']));
				if (!Jaws_Error::IsError($shout) && (isset($shout['url']) && !empty($shout['url']))) {
					$res['permalink'] = $shout['url'];
				}
				// Shared with specific users? Show them...
				$share_activity = '';
				$share_users = (strpos($sharing, 'users:') !== false ? explode(',', str_replace('users:', '', $sharing)) : array());
				$count_share_users = count($share_users);
				reset($share_users);
				$share_count = 1;
				if ($count_share_users < 4) {
					foreach ($share_users as $share) {
						//if ((int)$share != $viewer_id) {
							$shareInfo = $jUser->GetUserInfoById((int)$share, true, true, true, true);
							if (
								!Jaws_Error::IsError($shareInfo) && isset($shareInfo['id']) && !empty($shareInfo['id']) && 
								$jUser->GetStatusOfUserInFriend($viewer_id, (int)$shareInfo['id']) != 'blocked'
							) {
								if (!empty($shareInfo['company'])) {
									$share_user_name = (strlen($xss->filter(strip_tags($shareInfo['company']))) > 100 ? substr($xss->filter(strip_tags($shareInfo['company'])), 0, 100).'...' : $xss->filter(strip_tags($shareInfo['company'])));
									$share_user_fullname = $xss->filter(strip_tags($shareInfo['company']));
								} else {
									$share_user_name = (strlen($xss->filter(strip_tags($shareInfo['nickname']))) > 100 ? substr($xss->filter(strip_tags($shareInfo['nickname'])), 0, 100).'...' : $xss->filter(strip_tags($shareInfo['nickname'])));
									$share_user_fullname = $xss->filter(strip_tags($shareInfo['nickname']));
								}
								// Check if user is in profile group
								$share_show_link = false;
								$share_groups  = $jUser->GetGroupsOfUser($shareInfo['id']);
								if (!Jaws_Error::IsError($share_groups)) {
									foreach ($share_groups as $share_group) {
										if (
											strtolower($share_group['group_name']) == 'profile' && 
											in_array($share_group['group_status'], array('active','founder','admin'))
										) {
											$share_show_link = true;
											break;
										}
									}
								}
								$share_user_link = '';
								$share_user_link_start = '';
								$share_user_link_end = '';
								if ($share_show_link === true) {
									$share_user_link = $GLOBALS['app']->Map->GetURLFor('Users', 'AccountPublic', array('name' => $shareInfo['username']));
									$share_user_link_start = '<a href="'.$GLOBALS['app']->GetSiteURL().'/'.$GLOBALS['app']->Map->GetURLFor('Users', 'AccountPublic', array('name' => $shareInfo['username'])).'" title="'.$share_user_fullname.'" name="'.$share_user_fullname.'">';
									$share_user_link_end = '</a>';
								}
								$share_activity .= (!empty($share_activity) ? ', ' : "&nbsp;<span style=\"display: inline; font-weight: bold; color: #666666;\">&gt;</span>&nbsp;&nbsp;").$share_user_link_start.$share_user_name.$share_user_link_end;
								$share_count++;
							}
						//}
					}
				}
				$responses = $GLOBALS['app']->Session->PopLastResponse();
				if ($responses) {
					foreach ($responses as $msg_id => $response) {
						// Hack to not show superfluous messages. 
						if (
							substr($response['message'], 0, 10) != "The user's" && 
							substr($response['message'], -18) != "have been updated."
						) {
							$res['messages'][$msg_id] = array();
							$res['messages'][$msg_id]['css'] = $response['css'];
							$res['messages'][$msg_id]['message'] = $response['message'];
						}
					}
				}
				$res['preactivity'] = $share_activity;
				$res['activity'] = '';
			}
		}
		return $res;
    }
	
    /**
     * Deletes a comment
     *
     * @access  public
     * @param   int     $id   Comment ID
     * @return  array   Response (notice or error)
     */
    function DeleteComment($id)
    {
		$params = array();
		$params['id']   = $id;
				
		$sql = '
			SELECT
				[gadget], [ownerid]
			FROM [[comments]]
			WHERE [id] = {id}';

		$gadget = $GLOBALS['db']->queryRow($sql, $params);
		if (Jaws_Error::IsError($gadget) || !isset($gadget['gadget']) || empty($gadget['gadget'])) {
			if (Jaws_Error::IsError($gadget)) {
				$GLOBALS['app']->Session->PushLastResponse($gadget->GetMessage(), RESPONSE_ERROR);
			} else {
				$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_COMMENT_DELETED'), RESPONSE_ERROR);
			}
			return $GLOBALS['app']->Session->PopLastResponse();
		}
        $GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');
		if (
			(!$GLOBALS['app']->ACL->GetFullPermission(
				$GLOBALS['app']->Session->GetAttribute('username'), 
				$GLOBALS['app']->Session->GetAttribute('groups'), 'Users', 'ManageUsers')) && 
			(!$GLOBALS['app']->ACL->GetFullPermission(
				$GLOBALS['app']->Session->GetAttribute('username'), 
				$GLOBALS['app']->Session->GetAttribute('groups'), 'Users', 'ManageMessaging')) && 
			$gadget['ownerid'] != $GLOBALS['app']->Session->GetAttribute('user_id')
		) {
			$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_COMMENT_DELETED'), RESPONSE_ERROR);
		} else {
			$model = $GLOBALS['app']->LoadGadget('Users', 'Model');
			$delete = $model->DeleteComment($id, $gadget['gadget']);

			if (!Jaws_Error::IsError($delete)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_COMMENT_DELETED'), RESPONSE_NOTICE);
			} else {
				$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_COMMENT_DELETED'), RESPONSE_ERROR);
			}
		}
        return $GLOBALS['app']->Session->PopLastResponse();
    }	
	
    // }}}
    // {{{ Function DeleteEmailPage
    /**
     * Deletes a page and all translated of it.
     *
     * @access  public
     * @param   int     $id  Page ID
     * @return  array   Response (notice or error)
     */
    function DeleteEmailPage($id)
    {
		$this->CheckSession('Users', 'ManageMessaging');
		$GLOBALS['app']->Registry->LoadFile('CustomPage');
		$GLOBALS['app']->Translate->LoadTranslation('CustomPage', JAWS_GADGET);
        $gadget = $GLOBALS['app']->LoadGadget('CustomPage', 'AdminModel');
        $gadget->DeletePage($id);
        return $GLOBALS['app']->Session->PopLastResponse();
    }

    /**
     * Executes a massive-delete of pages
     *
     * @access  public
     * @param   array   $pages  Array with the ids of pages
     * @return  array   Response (notice or error)
     */
    function MassiveDeleteEmailPages($pages)
    {
		$this->CheckSession('Users', 'ManageMessaging');
		$GLOBALS['app']->Registry->LoadFile('CustomPage');
		$GLOBALS['app']->Translate->LoadTranslation('CustomPage', JAWS_GADGET);
        $gadget = $GLOBALS['app']->LoadGadget('CustomPage', 'AdminModel');
        $gadget->MassiveDeletePages($pages);
        return $GLOBALS['app']->Session->PopLastResponse();
    }
    
    /**
     * Get total pages of a search
     *
     * @access  public
     * @param   string  $status  Status of page(s) we want to display
     * @param   string  $search  Keyword (title/description) of pages we want to look for
     * @return  int     Total of posts
     */
    function SizeOfSearch1($status, $search)
    {
		$this->CheckSession('Users', 'ManageMessaging');
        $gadget = $GLOBALS['app']->LoadGadget('CustomPage', 'AdminModel');
        $pages = $gadget->SearchPages($status, $search, null, 0, 'Users', 'EmailPage');
        return count($pages);
    }

    /**
     * Returns an array with all the pages
     *
     * @access  public
     * @param   string  $status  Status of page(s) we want to display
     * @param   string  $search  Keyword (title/description) of pages we want to look for
     * @param   int     $limit   Data limit
     * @return  array   Pages data
     */
    function SearchEmailPages($status, $search, $limit)
    {
		$this->CheckSession('Users', 'ManageMessaging');
        $gadget = $GLOBALS['app']->LoadGadget('Users', 'AdminHTML');
        if (!is_numeric($limit)) {
            $limit = 0;
        }
		return $gadget->GetEmailPages($status, $search, $limit, 0);
    }
	
    // }}}
    // {{{ Function DeleteMessage
    /**
     * Deletes a page and all translated of it.
     *
     * @access  public
     * @param   int     $id  Page ID
     * @return  array   Response (notice or error)
     */
    function DeleteMessage($id)
    {
		return $this->DeleteComment($id);
    }

    /**
     * Executes a massive-delete of pages
     *
     * @access  public
     * @param   array   $pages  Array with the ids of pages
     * @return  array   Response (notice or error)
     */
    function MassiveDeleteMessages($pages)
    {
        $GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');
		if (
			!$GLOBALS['app']->ACL->GetFullPermission(
				$GLOBALS['app']->Session->GetAttribute('username'), 
				$GLOBALS['app']->Session->GetAttribute('groups'), 'Users', 'ManageMessaging')
		) {
			$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_COMMENT_DELETED'), RESPONSE_NOTICE);
		} else {
			$model = $GLOBALS['app']->LoadGadget('Users', 'Model');
			$delete = $model->DeleteMassiveComment($pages);

			if (!Jaws_Error::IsError($delete)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_COMMENT_DELETED'), RESPONSE_ERROR);
			} else {
				$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_ERROR_COMMENT_DELETED'), RESPONSE_NOTICE);
			}
		}
        return $GLOBALS['app']->Session->PopLastResponse();
    }
    
    /**
     * Get total pages of a search
     *
     * @access  public
     * @param   string  $status  Status of page(s) we want to display
     * @param   string  $search  Keyword (title/description) of pages we want to look for
     * @return  int     Total of posts
     */
    function SizeOfSearch2($search)
    {
		$this->CheckSession('Users', 'ManageMessaging');
        $pages = $this->_Model->SearchMessages($search, null, 0);
        return count($pages);
    }

    /**
     * Returns an array with all the pages
     *
     * @access  public
     * @param   string  $status  Status of page(s) we want to display
     * @param   string  $search  Keyword (title/description) of pages we want to look for
     * @param   int     $limit   Data limit
     * @return  array   Pages data
     */
    function SearchMessages($search, $limit)
    {
		$this->CheckSession('Users', 'ManageMessaging');
        $gadget = $GLOBALS['app']->LoadGadget('Users', 'AdminHTML');
        if (!is_numeric($limit)) {
            $limit = 0;
        }
		return $gadget->GetMessages($search, $limit, 0);
    }
	
}