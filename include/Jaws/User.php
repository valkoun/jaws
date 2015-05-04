<?php
/**
 * Users and groups platform. Subscriptions, messages and recommendations, allow user-created gadget content.
 *
 * @category 	User
 * @package 	Core
 * @author     Ivan -sk8- Chavero <imcsk8@gluch.org.mx>
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2005-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
define('PASSWORD_SALT_LENGTH', 24);

class Jaws_User
{
    /**
     * Get hashed password
     *
     * @access  private
     * @param   string  $password
     * @param   string  $salt
     * @return  string  Returns hashed password
     */
    function GetHashedPassword($password, $salt = null)
    {
        if (is_null($salt)) {
            $salt = substr(md5(uniqid(rand(), true)), 0, PASSWORD_SALT_LENGTH);
        } else {
            $salt = substr($salt, 0, PASSWORD_SALT_LENGTH);
        }

        return $salt . sha1($salt . $password);
    }

    /**
     * Validate a user
     *
     * @access  public
     * @param   string  $user      User to validate
     * @param   string  $password  Password of the user
     * @param   string  $onlyAdmin Only validate for admins
     * @return  boolean Returns true if the user is valid and false if not
     */
    function Valid($user, $password, $onlyAdmin = false)
    {
        $params         = array();
        $params['user'] = strtolower($user);
        $GLOBALS['db']->dbc->loadModule('Function', null, true);
        $username = $GLOBALS['db']->dbc->function->lower('[username]');

        $sql = "
            SELECT [id], [passwd], [user_type], [bad_passwd_count], [last_access], [enabled]
            FROM [[users]]
            WHERE $username = {user}";

        $types = array('integer', 'text', 'integer', 'integer', 'integer', 'boolean');
        $result = $GLOBALS['db']->queryRow($sql, $params, $types);
		if (isset($GLOBALS['log'])) {
			$GLOBALS['log']->Log(JAWS_LOG_INFO, "User->Valid() result: ".var_export($result, true));
        }
		if (Jaws_Error::IsError($result)) {
            return $result;
        }

        if (isset($result['id'])) {
            if ($onlyAdmin && $result['user_type'] > 1) {
                return new Jaws_Error(_t('GLOBAL_ERROR_LOGIN_ONLY_ADMIN'));
            }

            if ($result['bad_passwd_count'] >= $GLOBALS['app']->Registry->Get('/policy/passwd_bad_count') &&
               ((time() - $result['last_access']) <= $GLOBALS['app']->Registry->Get('/policy/passwd_lockedout_time')))
            {
                return new Jaws_Error(_t('GLOBAL_ERROR_LOGIN_LOCKED_OUT'));
            }

            // compare md5ed password for backward compatibility
            if ($result['passwd'] === $this->GetHashedPassword($password, $result['passwd']) ||
                trim($result['passwd']) === md5($password))
            {
                if (!$result['enabled']) {
                    return new Jaws_Error(_t('GLOBAL_ERROR_LOGIN_DISABLED'));
                }

                return array('id' => $result['id'],
                            'user_type' => $result['user_type']);

            } else {
                $params['id']          = $result['id'];
                $params['bad_count']   = $result['bad_passwd_count'] + 1;
                $params['last_access'] = time();

                $sql = '
                    UPDATE [[users]] SET
                        [last_access]      = {last_access},
                        [bad_passwd_count] = {bad_count}
                    WHERE [id] = {id}';

                $result = $GLOBALS['db']->query($sql, $params);
            }
        }

        return new Jaws_Error(_t('GLOBAL_ERROR_LOGIN_WRONG'));
    }

    /**
     * Updates the last login time for the given user
     *
     * @param $user_id integer user id of the user being updated
     * @return boolean true if all is ok, false if error
     */
    function updateLoginTime($user_id)
    {
        $params = array();
        $params['last_login'] = $GLOBALS['db']->Date();
        $params['id']         = (int)$user_id;

        $sql = '
            UPDATE [[users]] SET
                [last_login]       = {last_login},
                [bad_passwd_count] = 0
            WHERE [id] = {id}';
        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::isError($result)) {
            return false;
        }

        return true;
    }

    /**
     * Get the info of an user
     *
     * @access  public
     * @param   int     $id  The user ID
     * @return  mixed   Returns an array with the info of the user and false on error
     */
    function GetUserInfoById($id, $account = true, $personal = false, $preferences = false, $extra = false)
    {
        $params = array();
        $params['id'] = $id;

        $sql = 'SELECT [id]';
        $types = array('integer');

        if ($account) {
            $sql .= ', [username], [nickname], [email], [user_type], [enabled]';
            $types = array_merge($types, array('text', 'text', 'text', 'integer', 'boolean'));
        }
        if ($personal) {
            $sql .= ', [fname], [lname], [gender], [dob], [url], [company],
				[address], [address2], [city], [region], [postal], [country], 
				[description], [office], [tollfree], [phone], [fax], [merchant_id], 
				[keywords], [image], [logo], [notification], [company_type]';
            $types = array_merge($types, array(
				'text', 'text', 'integer', 'timestamp', 'text', 'text',
				'text', 'text', 'text', 'text', 'text', 'text', 
				'text', 'text', 'text', 'text', 'text', 'text',
				'text', 'text', 'text', 'text', 'text'
			));
        }
        if ($preferences) {
            $sql .= ', [language], [theme], [editor], [timezone], [allow_comments], [identifier]';
            $types = array_merge($types, array('text', 'text', 'text', 'text', 'boolean', 'text'));
        }
        if ($extra) {
            $sql .= ', [passwd], [last_login], [createtime], [updatetime], [closetime], [checksum], [new_messages]';
            $types = array_merge($types, array('text', 'timestamp', 'timestamp', 'timestamp', 'timestamp', 'text', 'text'));
        }

        $sql .= '
            FROM [[users]]
            WHERE [id] = {id}';

        $result = $GLOBALS['db']->queryRow($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return false;
        }

        return $result;
    }

    /**
     * Get the info of a group
     *
     * @access  public
     * @param   int     $id  The group ID
     * @return  mixed   Returns an array with the info of the group and false on error
     */
    function GetGroupInfoById($id)
    {
        $params       = array();
        $params['id'] = $id;
        $sql = '
            SELECT
                [id], [name], [title], [description], [checksum]
            FROM [[groups]]
            WHERE [id] = {id}';

        $result = $GLOBALS['db']->queryRow($sql, $params);
        if (Jaws_Error::IsError($result)) {
            return false;
        }

        return $result;
    }

    /**
     * Get the info of an user by the username
     *
     * @access  public
     * @param   int     $user  The username
     * @return  mixed   Returns an array with the info of the user and false on error
     */
    function GetUserInfoByName($user, $account = true, $personal = false, $preferences = false, $extra = false)
    {
        $params = array();
        $params['user'] = strtolower($user);

        $sql = 'SELECT [id]';
        $types = array('integer');

        if ($account) {
            $sql .= ', [username], [nickname], [email], [user_type], [enabled]';
            $types = array_merge($types, array('text', 'text', 'text', 'integer', 'boolean'));
        }
        if ($personal) {
            $sql .= ', [fname], [lname], [gender], [dob], [url], [company],
				[address], [address2], [city], [region], [postal], [country], 
				[description], [office], [tollfree], [phone], [fax], [merchant_id], 
				[keywords], [image], [logo], [notification], [company_type]';
            $types = array_merge($types, array(
				'text', 'text', 'integer', 'timestamp', 'text', 'text',
				'text', 'text', 'text', 'text', 'text', 'text', 
				'text', 'text', 'text', 'text', 'text', 'text',
				'text', 'text', 'text', 'text', 'text'
			));
        }
        if ($preferences) {
            $sql .= ', [language], [theme], [editor], [timezone], [allow_comments], [identifier]';
            $types = array_merge($types, array('text', 'text', 'text', 'text', 'boolean', 'text'));
        }
        if ($extra) {
            $sql .= ', [passwd], [last_login], [createtime], [updatetime], [closetime], [checksum], [new_messages]';
            $types = array_merge($types, array('text', 'timestamp', 'timestamp', 'timestamp', 'timestamp', 'text', 'text'));
        }

        $GLOBALS['db']->dbc->loadModule('Function', null, true);
        $username = $GLOBALS['db']->dbc->function->lower('[username]');

        $sql .= "
            FROM [[users]]
            WHERE $username = {user}";

        return $GLOBALS['db']->queryRow($sql, $params);
    }

    /**
     * Get the info of a group by its name
     *
     * @access  public
     * @param   int     $id  The group name
     * @return  mixed   Returns an array with the info of the group and false on error
     */
    function GetGroupInfoByName($name)
    {
        $params         = array();
        $params['name'] = $name;
        $sql = '
            SELECT
                [id], [name], [title], [description], [checksum]
            FROM [[groups]]
            WHERE [name] = {name}';

        $result = $GLOBALS['db']->queryRow($sql, $params);
        if (Jaws_Error::IsError($result)) {
            return false;
        }

        return $result;
    }

    /**
     * Check and email address already exists
     *
     * @access  public
     * @param   string  $email      The email address
     * @param   int     $exclude    Excluded user ID
     * @return  mixed   Returns an array with the info of the user and false on error
     */
    function UserEmailExists($email, $exclude = 0)
    {
        $params = array();
        $params['id']    = $exclude;
        $params['email'] = strtolower($email);
		$GLOBALS['db']->dbc->loadModule('Function', null, true);
		$email = $GLOBALS['db']->dbc->function->lower('[email]');

        $sql = "
            SELECT COUNT([id])
            FROM [[users]]
            WHERE
                $email = {email}
              AND
                [id] <> {id}";

        $howmany = $GLOBALS['db']->queryOne($sql, $params);
        if (Jaws_Error::IsError($howmany) || empty($howmany)) {
            return false;
        }

        return true;
    }

    /**
     * Returns true if a group (by its ID) can be removed
     *
     * @access  public
     * @param   int     $id  Group's ID
     * @return  boolean Can be removed?
     */
    function CanRemoveGroup($id)
    {
       $params       = array();
       $params['id'] = $id;
       
        $sql = '
            SELECT
                [removable]
            FROM [[groups]]
            WHERE [id] = {id}';

        $result = $GLOBALS['db']->queryOne($sql, $params, array('boolean'));
        if (Jaws_Error::IsError($result)) {
            return false;
        }
        
        return $result;
    }

    /**
     * Get the avatar url
     * @access public
     * @param  string   $username   Username
     * @param  string   $email      Email
     * @return string   Url to avatar image
     */
    function GetAvatar($username, $email)
    {
        $avatar = $GLOBALS['app']->getDataURL("avatar/$username.png");
        if (!file_exists($avatar)) {
			require_once JAWS_PATH . 'include/Jaws/Image.php';
			$image_src = '';
			$info = $this->GetUserInfoByName($username, true, true, true, true);
			if (!Jaws_Error::IsError($info)) {
				if (!empty($info['logo']) && isset($info['logo'])) {
					$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
					$info['logo'] = $xss->filter(strip_tags($info['logo']));
					if (substr(strtolower($info['logo']), 0, 4) == "http") {
						$image_src = $info['logo'];
						if (substr(strtolower($info['logo']), 0, 7) == "http://") {
							$image_src = explode('http://', $image_src);
							foreach ($image_src as $img_src) {
								if (!empty($img_src)) {
									$image_src = 'http://'.$img_src;
									break;
								}
							}
						} else {
							$image_src = explode('https://', $image_src);
							foreach ($image_src as $img_src) {
								if (!empty($img_src)) {
									$image_src = 'https://'.$img_src;
									break;
								}
							}
						}
						if (strpos(strtolower($image_src), 'data/files/') !== false) {
							$image_src = 'image_thumb.php?uri='.urlencode($image_src);
						}
					} else {
						$thumb = Jaws_Image::GetThumbPath($info['logo']);
						$medium = Jaws_Image::GetMediumPath($info['logo']);
						if (file_exists(JAWS_DATA . 'files'.$thumb)) {
							$image_src = $GLOBALS['app']->getDataURL() . 'files'.$thumb;
						} else if (file_exists(JAWS_DATA . 'files'.$medium)) {
							$image_src = $GLOBALS['app']->getDataURL() . 'files'.$medium;
						} else if (file_exists(JAWS_DATA . 'files'.$info['logo'])) {
							$image_src = $GLOBALS['app']->getDataURL() . 'files'.$info['logo'];
						}
					}
				}
			}
			if (!empty($image_src)) {
				return $image_src;
			} else {	
				require_once JAWS_PATH . 'include/Jaws/Gravatar.php';
				$avatar = Jaws_Gravatar::GetGravatar($email);
			}
        }
        return $avatar;
    }

    /**
     * Get the avatar url
     * @access public
     * @param  string   $group   Group name
     * @param  string   $email      Email
     * @return string   Url to avatar image
     */
    function GetGroupAvatar($group)
    {
		// Logo in css directory? Otherwise show group's founder or admin or first user with logo, otherwise show site's logo.
		$avatar = $GLOBALS['app']->GetDataURL('files/css/groups/'.$group.'/logo.png', true);
		if (!file_exists(JAWS_DATA . 'files/css/groups/'.$group.'/logo.png')) {
/*
			require_once JAWS_PATH . 'include/Jaws/Image.php';
			$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
			$image_src = '';
			$founders = $jUser->GetUsersOfGroupByStatus($community['id'], 'founder');
			if (!Jaws_Error::IsError($founders)) {
				foreach ($founders as $ugroup) {
					$info = $this->GetUserInfoById($ugroup['user_id'], true, true, true, true);
					if (!Jaws_Error::IsError($info)) {
						if (!empty($info['logo']) && isset($info['logo'])) {
							$image_src = '';
							$info['logo'] = $xss->filter(strip_tags($info['logo']));
							if (substr(strtolower($info['logo']), 0, 4) == "http") {
								$image_src = $info['logo'];
								if (substr(strtolower($info['logo']), 0, 7) == "http://") {
									$image_src = explode('http://', $image_src);
									foreach ($image_src as $img_src) {
										if (!empty($img_src)) {
											$image_src = 'http://'.$img_src;
											break;
										}
									}
								} else {
									$image_src = explode('https://', $image_src);
									foreach ($image_src as $img_src) {
										if (!empty($img_src)) {
											$image_src = 'https://'.$img_src;
											break;
										}
									}
								}
								if (strpos(strtolower($image_src), 'data/files/') !== false) {
									$image_src = 'image_thumb.php?uri='.urlencode($image_src);
								}
							} else {
								$thumb = Jaws_Image::GetThumbPath($info['logo']);
								$medium = Jaws_Image::GetMediumPath($info['logo']);
								if (file_exists(JAWS_DATA . 'files'.$thumb)) {
									$image_src = $GLOBALS['app']->getDataURL() . 'files'.$thumb;
								} else if (file_exists(JAWS_DATA . 'files'.$medium)) {
									$image_src = $GLOBALS['app']->getDataURL() . 'files'.$medium;
								} else if (file_exists(JAWS_DATA . 'files'.$info['logo'])) {
									$image_src = $GLOBALS['app']->getDataURL() . 'files'.$info['logo'];
								}
							}
						}
					}
					break;
				}
			}
			if (empty($image_src)) {
				$admins = $jUser->GetUsersOfGroupByStatus($community['id'], 'admin');
				if (!Jaws_Error::IsError($admins)) {
					foreach ($admins as $ugroup) {
						$info = $this->GetUserInfoById($ugroup['user_id'], true, true, true, true);
						if (!Jaws_Error::IsError($info)) {
							if (!empty($info['logo']) && isset($info['logo'])) {
								$image_src = '';
								$info['logo'] = $xss->filter(strip_tags($info['logo']));
								if (substr(strtolower($info['logo']), 0, 4) == "http") {
									$image_src = $info['logo'];
									if (substr(strtolower($info['logo']), 0, 7) == "http://") {
										$image_src = explode('http://', $image_src);
										foreach ($image_src as $img_src) {
											if (!empty($img_src)) {
												$image_src = 'http://'.$img_src;
												break;
											}
										}
									} else {
										$image_src = explode('https://', $image_src);
										foreach ($image_src as $img_src) {
											if (!empty($img_src)) {
												$image_src = 'https://'.$img_src;
												break;
											}
										}
									}
									if (strpos(strtolower($image_src), 'data/files/') !== false) {
										$image_src = 'image_thumb.php?uri='.urlencode($image_src);
									}
								} else {
									$thumb = Jaws_Image::GetThumbPath($info['logo']);
									$medium = Jaws_Image::GetMediumPath($info['logo']);
									if (file_exists(JAWS_DATA . 'files'.$thumb)) {
										$image_src = $GLOBALS['app']->getDataURL() . 'files'.$thumb;
									} else if (file_exists(JAWS_DATA . 'files'.$medium)) {
										$image_src = $GLOBALS['app']->getDataURL() . 'files'.$medium;
									} else if (file_exists(JAWS_DATA . 'files'.$info['logo'])) {
										$image_src = $GLOBALS['app']->getDataURL() . 'files'.$info['logo'];
									}
								}
							}
						}
					}
				}
			}
			if (empty($image_src)) {
				$users = $jUser->GetUsersOfGroup($community['id']);
				if (!Jaws_Error::IsError($users)) {
					foreach ($users as $ugroup) {
						$info = $this->GetUserInfoById($ugroup['user_id'], true, true, true, true);
						if (!Jaws_Error::IsError($info)) {
							if (!empty($info['logo']) && isset($info['logo'])) {
								$image_src = '';
								$info['logo'] = $xss->filter(strip_tags($info['logo']));
								if (substr(strtolower($info['logo']), 0, 4) == "http") {
									$image_src = $info['logo'];
									if (substr(strtolower($info['logo']), 0, 7) == "http://") {
										$image_src = explode('http://', $image_src);
										foreach ($image_src as $img_src) {
											if (!empty($img_src)) {
												$image_src = 'http://'.$img_src;
												break;
											}
										}
									} else {
										$image_src = explode('https://', $image_src);
										foreach ($image_src as $img_src) {
											if (!empty($img_src)) {
												$image_src = 'https://'.$img_src;
												break;
											}
										}
									}
									if (strpos(strtolower($image_src), 'data/files/') !== false) {
										$image_src = 'image_thumb.php?uri='.urlencode($image_src);
									}
								} else {
									$thumb = Jaws_Image::GetThumbPath($info['logo']);
									$medium = Jaws_Image::GetMediumPath($info['logo']);
									if (file_exists(JAWS_DATA . 'files'.$thumb)) {
										$image_src = $GLOBALS['app']->getDataURL() . 'files'.$thumb;
									} else if (file_exists(JAWS_DATA . 'files'.$medium)) {
										$image_src = $GLOBALS['app']->getDataURL() . 'files'.$medium;
									} else if (file_exists(JAWS_DATA . 'files'.$info['logo'])) {
										$image_src = $GLOBALS['app']->getDataURL() . 'files'.$info['logo'];
									}
								}
							}
						}
					}
				}
			}
			if (!empty($image_src)) {
				return $image_src;
			} else {
*/			
				$theme = $GLOBALS['app']->GetTheme();
				$avatar = $GLOBALS['app']->GetJawsURL() . '/gadgets/Users/images/group-photo.png';
				if (substr(strtolower($theme['path']), 0, 4) == 'http') {
					// snoopy
					include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
					$snoopy = new Snoopy;
					$snoopy->fetch($theme['url'] . 'group_avatar.png');
					if($snoopy->status == "200") {
						return $theme['url'] . 'group_avatar.png';
					}
				} else if (file_exists($theme['path'] . 'group_avatar.png')) {
					return $theme['url'] . 'group_avatar.png';
				}
//			}
		}
        return $avatar;
    }

    /**
     * Get a list of users
     *
     * @access  public
     * @param   mixed   $group      Group ID of users
     * @param   mixed   $type       Type of user to use (false = all types, 0 = superadmin, 1 = admin, 2 = normal)
     * @param   boolean $enabled    enabled users?(null for both)
     * @param   string  $orderBy    field to order by
     * @return  array   Returns an array of the available users and false on error
     */
	function GetUsers(
		$group = false, $type = false, $enabled = null, $orderBy = 'nickname', 
		$limit = null, $offset = null, $sortDir = 'ASC', 
		$searchletter = '', $searchkeyword = '', $requestedowners = array(), 
		$radiusowners = array(), $namefilters = array()
	) {
        $fields  = array(
			'id', 'username', 'email', 'nickname', 'createtime', 'updatetime', 'closetime', 
			'fname', 'lname', 'user_type', 'company', 'url', 'city', 'region', 'postal', 
			'country', 'office', 'tollfree', 'phone', 'fax', 'merchant_id', 'company_type'
		);
        $orderBy = strtolower($orderBy);
        if (!in_array($orderBy, $fields)) {
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_WARNING, _t('GLOBAL_ERROR_UNKNOWN_COLUMN'));
            }
            $orderBy = 'username';
        }
		$sortDir = strtoupper($sortDir);
        if (!in_array($sortDir, array('ASC','DESC'))) {
            $sortDir = 'ASC';
        }
        $params = array();
        $params['gid']     = $group;
        $params['enabled'] = (bool)$enabled;

        $sql = '
            SELECT
                [[users]].[id], [username], [email], [url], [nickname], [fname], [lname],
                [user_type], [language], [theme], [editor], [timezone], [[users]].[enabled],
				[createtime], [updatetime], [closetime], [company], [url], [address], [address2], 
				[city], [region], [postal], [country], [[users]].[description], [office], [tollfree], 
				[phone], [fax], [merchant_id], [keywords], [image], [logo], [notification], [allow_comments], 
				[identifier], [[users]].[checksum], [passwd], [company_type], [new_messages]
            FROM [[users]]';

        if ($group !== false) {
            $sql .= '
                INNER JOIN [[users_groups]] ON [[users_groups]].[user_id] = [[users]].[id]
			';
        }

		$sql .= '
			WHERE ([[users]].[id] > 0)
		';
        
		if ($group !== false) {
            $sql .= ' AND ([[users_groups]].[group_id] = {gid})';
        }
        if ($type !== false) {
			$sql .= " AND ([user_type] IN ({$type}))";
        }
        if (!is_null($enabled)) {
			$sql .= ' AND ([[users]].[enabled] = {enabled})';
        }

		$GLOBALS['db']->dbc->loadModule('Function', null, true);
		$numbers = array();
		$letters = array();
		if (strtolower($searchletter) == 'num') {
			$numbers = array('0','1','3','4','5','6','7','8','9');
		} else {
			$letters = array(
			'A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z',
			'a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z'
			);
		}
		$searchletter = (empty($searchletter) && empty($searchkeyword) ? '' : (!empty($searchletter) && !in_array($searchletter, $letters) && strtolower($searchletter) != 'num' ? 'A' : $searchletter));
		if (!empty($searchletter)) {
			if (strtolower($searchletter) == 'num') {
				$first_title = $GLOBALS['db']->dbc->function->substring('[[users]].[company]', 1, 1);
				$first_name = $GLOBALS['db']->dbc->function->substring('[[users]].[nickname]', 1, 1);
				$sql .= ' AND (';
				for ($i=0; $i<10; $i++) {
					$sql .= " $first_title = {letter".$i."} OR"; 
					$sql .= " $first_name = {letter".$i."}"; 
					$sql .= ($i==9 ? ')' : " OR");
					$params['letter'.$i] = $i;
				}
			} else {
				$first_title = $GLOBALS['db']->dbc->function->lower($GLOBALS['db']->dbc->function->substring('[[users]].[company]', 1, 1));
				$first_name = $GLOBALS['db']->dbc->function->lower($GLOBALS['db']->dbc->function->substring('[[users]].[nickname]', 1, 1));
				$sql .= " AND ($first_title = {letter} OR $first_name = {letter})"; 
				$params['letter'] = strtolower($searchletter);
			}
		}
		
		if (!empty($searchkeyword)) {
			$first_title = $GLOBALS['db']->dbc->function->lower('[[users]].[company]');
			$first_name = $GLOBALS['db']->dbc->function->lower('[[users]].[nickname]');
			$first_username = $GLOBALS['db']->dbc->function->lower('[[users]].[username]');
			$first_url = $GLOBALS['db']->dbc->function->lower('[[users]].[url]');
			$first_address = $GLOBALS['db']->dbc->function->lower('[[users]].[address]');
			$first_city = $GLOBALS['db']->dbc->function->lower('[[users]].[city]');
			$first_region = $GLOBALS['db']->dbc->function->lower('[[users]].[region]');
			$first_postal = $GLOBALS['db']->dbc->function->lower('[[users]].[postal]');
			$first_country = $GLOBALS['db']->dbc->function->lower('[[users]].[country]');
			$first_keywords = $GLOBALS['db']->dbc->function->lower('[[users]].[keywords]');
			$first_company_type = $GLOBALS['db']->dbc->function->lower('[[users]].[company_type]');
			$sql .= " AND (
				$first_title LIKE {keyword} OR $first_name LIKE {keyword} OR 
				$first_username LIKE {keyword} OR $first_url LIKE {keyword} OR 
				$first_address LIKE {keyword} OR $first_city LIKE {keyword} OR 
				$first_region LIKE {keyword} OR $first_postal LIKE {keyword} OR 
				$first_country LIKE {keyword} OR $first_keywords LIKE {keyword} OR 
				$first_company_type LIKE {keyword} 
			)"; 
			$params['keyword'] = '%'.strtolower($searchkeyword).'%';
		}
		
		$count_filters = count($namefilters);
		if (!$count_filters <= 0) {
			$sql .= ' AND (';
			$i = 0;
			foreach ($namefilters as $filter) {
				$filter_company_type = $GLOBALS['db']->dbc->function->lower('[[users]].[company_type]');
				$sql .= " ($filter_company_type = {company_type})"; 
				$params['company_type'] = strtolower(preg_replace("[^A-Za-z0-9]", '', $filter));
				$i++;
				if ($i < $count_filters) {
					$sql .= " OR ";
				}
			}
			$sql .= ')';
		}
		
		$count_owners = count($requestedowners);
		if (!$count_owners <= 0) {
			$sql .= ' AND (';
			$i = 0;
			foreach ($requestedowners as $owner) {
				$user_id = $GLOBALS['db']->dbc->function->lower('[[users]].[id]');
				$sql .= " ($user_id = {id})"; 
				$params['id'] = $owner;
				$i++;
				if ($i < $count_owners) {
					$sql .= " OR ";
				}
			}
			$sql .= ')';
		}
		
		$count_radius = count($radiusowners);
		if (!$count_radius <= 0) {
			$sql .= ' AND (';
			$i = 0;
			foreach ($radiusowners as $owner) {
				$user_id = $GLOBALS['db']->dbc->function->lower('[[users]].[id]');
				$sql .= " ($user_id = {id})"; 
				$params['id'] = $owner;
				$i++;
				if ($i < $count_radius) {
					$sql .= " OR ";
				}
			}
			$sql .= ')';
		}
											
        $sql .= "
            ORDER BY [[users]].[$orderBy] $sortDir";

        if (!empty($limit)) {
            $result = $GLOBALS['db']->setLimit($limit, $offset);
            if (Jaws_Error::IsError($res)) {
                return false;
            }
        }

        $result = $GLOBALS['db']->queryAll($sql, $params);
        if (Jaws_Error::IsError($result)) {
            return false;
        }

        return $result;
    }

    /**
     * Get a list of users by email
     *
     * @access  public
     * @param   string   $email      email address
     * @param   boolean $enabled    enabled users?(null for both)
     * @param   string  $orderBy    field to order by
     * @return  array   Returns an array of the available users and false on error
     */
    function GetUsersByEmail($email, $enabled = null, $orderBy = 'id', $limit = null, $offset = null)
    {
        $fields  = array('id', 'username', 'email', 'nickname');
        $orderBy = strtolower($orderBy);
        if (!in_array($orderBy, $fields)) {
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_WARNING, _t('GLOBAL_ERROR_UNKNOWN_COLUMN'));
            }
            $orderBy = 'id';
        }

        $params = array();
		$params['email'] = strtolower($email);
		$GLOBALS['db']->dbc->loadModule('Function', null, true);
		$user_email = $GLOBALS['db']->dbc->function->lower('[[users]].[email]');

        $sql = "
            SELECT
                [[users]].[id], [username], [email], [url], [nickname], [fname], [lname],
                [user_type], [language], [theme], [editor], [timezone], [[users]].[enabled],
				[closetime], [company], [url], [address], [address2], [city], [region], [postal], 
				[country], [[users]].[description], [office], [tollfree], [phone], [fax], 
				[merchant_id], [keywords], [image], [logo], [notification], [allow_comments], 
				[identifier], [[users]].[checksum], [passwd], [company_type], [new_messages]
            FROM [[users]]
			WHERE $user_email = {email} ";

        if (!is_null($enabled)) {
			$params['enabled'] = (bool)$enabled;
			$sql .= '
				AND [[users]].[enabled] = {enabled}';
        }

        $sql .= '
            ORDER BY [[users]].[' . $orderBy . ']';

        if (!empty($limit)) {
            $result = $GLOBALS['db']->setLimit($limit, $offset);
            if (Jaws_Error::IsError($res)) {
                return false;
            }
        }
        $result = $GLOBALS['db']->queryAll($sql, $params);
        if (Jaws_Error::IsError($result)) {
            return false;
        }

        return $result;
    }

    /**
     * Get a list of all groups
     *
     * @access  public
     * @param   string 	$orderBy 	Field to order by
     * @param   array 	$exclude_groups 	Array of groups to exclude (core/noncore/or groupname)
     * @param   int 	$limit 	Data limit
     * @param   int 	$offSet 	Data offset
     * @return  array   Returns an array of the available groups and false on error
     */
    function GetAllGroups($orderBy = 'name', $exclude_groups = null, $limit = null, $offset = null, $searchletter = '', $searchkeyword = '', $searchhoods = '')
    {
        $fields  = array('id', 'name', 'title');
        $orderBy = strtolower($orderBy);
        if (!in_array($orderBy, $fields)) {
            $GLOBALS['log']->Log(JAWS_LOG_WARNING, _t('GLOBAL_ERROR_UNKNOWN_COLUMN'));
            $orderBy = 'name';
        }
		        
		$sql = '
            SELECT
                [id], [name], [title], [description], [checksum]
            FROM [[groups]] 
			WHERE ([id] > 0)
		';
		
		$params = array();
        $GLOBALS['db']->dbc->loadModule('Function', null, true);
		$numbers = array();
		$letters = array();
		if (strtolower($searchletter) == 'num') {
			$numbers = array('0','1','3','4','5','6','7','8','9');
		} else {
			$letters = array(
			'A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z',
			'a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z'
			);
		}
		$searchletter = (empty($searchletter) && empty($searchkeyword) ? '' : (!empty($searchletter) && !in_array($searchletter, $letters) && strtolower($searchletter) != 'num' ? 'A' : $searchletter));
		if (!empty($searchletter)) {
			if (strtolower($searchletter) == 'num') {
				$first_title = $GLOBALS['db']->dbc->function->substring('[[groups]].[title]', 1, 1);
				$first_name = $GLOBALS['db']->dbc->function->substring('[[groups]].[name]', 1, 1);
				$sql .= ' AND (';
				for ($i=0; $i<10; $i++) {
					$sql .= " $first_title = {letter".$i."} OR"; 
					$sql .= " $first_name = {letter".$i."}"; 
					$sql .= ($i==9 ? ')' : " OR");
					$params['letter'.$i] = (string)$i;
				}
			} else {
				$first_title = $GLOBALS['db']->dbc->function->lower($GLOBALS['db']->dbc->function->substring('[[groups]].[title]', 1, 1));
				$first_name = $GLOBALS['db']->dbc->function->lower($GLOBALS['db']->dbc->function->substring('[[groups]].[name]', 1, 1));
				$sql .= " AND ($first_title = {letter} OR $first_name = {letter})"; 
				$params['letter'] = strtolower($searchletter);
			}
		}
		
		if (!empty($searchkeyword)) {
			$first_title = $GLOBALS['db']->dbc->function->lower('[[groups]].[title]');
			$first_name = $GLOBALS['db']->dbc->function->lower('[[groups]].[name]');
			$sql .= " AND ($first_title LIKE {keyword} OR $first_name LIKE {keyword})"; 
			$params['keyword'] = '%'.strtolower($searchkeyword).'%';
		}
		
		if (is_array($exclude_groups)) {
			$params['core1'] = 'users';
			$params['core2'] = 'profile';
			$params['core3'] = 'no_profile';
			$params['core4'] = '%_owners';
			$params['core5'] = '%_users';
			if (in_array('noncore', $exclude_groups)) {
				$sql .= ' AND ([[groups]].[name] = {core1}) AND  
					([[groups]].[name] = {core2}) AND 
					([[groups]].[name] = {core3}) AND 
					([[groups]].[name] LIKE {core4}) AND 
					([[groups]].[name] LIKE {core5})';
			} else {
				if (in_array('core', $exclude_groups)) {
					$sql .= ' AND ([[groups]].[name] != {core1}) AND  
						([[groups]].[name] != {core2}) AND 
						([[groups]].[name] != {core3}) AND NOT  
						([[groups]].[name] LIKE {core4}) AND NOT  
						([[groups]].[name] LIKE {core5})';
				}
				$e = 0;
				foreach ($exclude_groups as $exclude) {
					if (!in_array($exclude, array('core','noncore'))) {
						$params['exclude'.$e] = $exclude;
						$sql .= ' AND ([[groups]].[name] != {exclude'.$e.'})';
						$e++;
					}
				}
			}
		}
		$sql .= " 
			ORDER BY [$orderBy] ASC
		";
				
		if (!empty($limit)) {
            $result = $GLOBALS['db']->setLimit($limit, $offset);
            if (Jaws_Error::IsError($res)) {
                return false;
            }
        }

        return $GLOBALS['db']->queryAll($sql, $params);
    }

    /**
     * Get a list of groups where a user is
     *
     * @access  public
     * @param   mixed  $user  Username or UserID
     * @param   array 	$exclude_groups 	Array of groups to exclude (core/noncore/or groupname)
     * @param   int 	$limit 	Data limit
     * @param   int 	$offSet 	Data offset
     * @return  array  Returns an array of the available groups and false on error
     */
    function GetGroupsOfUser($user, $exclude_groups = null, $limit = null, $offset = null, $searchletter = '', $searchkeyword = '', $searchhoods = '')
    {
        $params  = array();
        $params['user'] = $user;
        $sql = '
            SELECT
                [[groups]].[id] AS group_id, [[groups]].[name] AS group_name, [[groups]].[title] AS group_title, 
				[[users_groups]].[status] AS group_status, [[groups]].[checksum] AS group_checksum, [[users_groups]].[updated] AS group_updated
            FROM [[users_groups]]
            INNER JOIN [[users]]  ON [[users]].[id] =  [[users_groups]].[user_id]
            INNER JOIN [[groups]] ON [[groups]].[id] = [[users_groups]].[group_id]
            WHERE
		';
        if (is_numeric($user)) {
            $sql .= ' [[users]].[id] = {user}';
        } else {
            $sql .= ' [[users]].[username] = {user}';
		}
        
		$GLOBALS['db']->dbc->loadModule('Function', null, true);
		$numbers = array();
		$letters = array();
		if (strtolower($searchletter) == 'num') {
			$numbers = array('0','1','3','4','5','6','7','8','9');
		} else {
			$letters = array(
			'A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z',
			'a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z'
			);
		}
		$searchletter = (empty($searchletter) && empty($searchkeyword) ? '' : (!empty($searchletter) && !in_array($searchletter, $letters) && strtolower($searchletter) != 'num' ? 'A' : $searchletter));
		if (!empty($searchletter)) {
			if (strtolower($searchletter) == 'num') {
				$first_title = $GLOBALS['db']->dbc->function->substring('[[groups]].[title]', 1, 1);
				$first_name = $GLOBALS['db']->dbc->function->substring('[[groups]].[name]', 1, 1);
				$sql .= ' AND (';
				for ($i=0; $i<10; $i++) {
					$sql .= " $first_title = {letter".$i."} OR"; 
					$sql .= " $first_name = {letter".$i."}"; 
					$sql .= ($i==9 ? ')' : " OR");
					$params['letter'.$i] = $i;
				}
			} else {
				$first_title = $GLOBALS['db']->dbc->function->lower($GLOBALS['db']->dbc->function->substring('[[groups]].[title]', 1, 1));
				$first_name = $GLOBALS['db']->dbc->function->lower($GLOBALS['db']->dbc->function->substring('[[groups]].[name]', 1, 1));
				$sql .= " AND ($first_title = {letter} OR $first_name = {letter})"; 
				$params['letter'] = strtolower($searchletter);
			}
		}
		
		if (!empty($searchkeyword)) {
			$first_title = $GLOBALS['db']->dbc->function->lower('[[groups]].[title]');
			$first_name = $GLOBALS['db']->dbc->function->lower('[[groups]].[name]');
			$sql .= " AND ($first_title LIKE {keyword} OR $first_name LIKE {keyword})"; 
			$params['keyword'] = '%'.strtolower($searchkeyword).'%';
		}
		if (is_array($exclude_groups)) {
			$params['core1'] = 'users';
			$params['core2'] = 'profile';
			$params['core3'] = 'no_profile';
			$params['core4'] = '%_owners';
			$params['core5'] = '%_users';
			if (in_array('noncore', $exclude_groups)) {
				$sql .= ' AND ([[groups]].[name] = {core1}) AND  
					([[groups]].[name] = {core2}) AND 
					([[groups]].[name] = {core3}) AND 
					([[groups]].[name] LIKE {core4}) AND 
					([[groups]].[name] LIKE {core5})';
			} else {
				if (in_array('core', $exclude_groups)) {
					$sql .= ' AND ([[groups]].[name] != {core1}) AND  
						([[groups]].[name] != {core2}) AND 
						([[groups]].[name] != {core3}) AND NOT  
						([[groups]].[name] LIKE {core4}) AND NOT  
						([[groups]].[name] LIKE {core5})';
				}
				$e = 0;
				foreach ($exclude_groups as $exclude) {
					if (!in_array($exclude, array('core','noncore'))) {
						$params['exclude'.$e] = $exclude;
						$sql .= ' AND ([[groups]].[name] != {exclude'.$e.'})';
						$e++;
					}
				}
			}
		}
        if (!empty($limit)) {
            $result = $GLOBALS['db']->setLimit($limit, $offset);
            if (Jaws_Error::IsError($res)) {
                return false;
            }
        }
        $result = $GLOBALS['db']->queryAll($sql, $params);
        if (Jaws_Error::IsError($result)) {
            return false;
        }

        return $result;
    }

    /**
     * Get a list of users that are inside a group
     *
     * @access  public
     * @param   mixed  	$group  Group name or GroupID
     * @param   int 	$limit 	Data limit
     * @param   int 	$offSet 	Data offset
     * @return  array   Returns an array of the available users and false on error
     */
    function GetUsersOfGroup(
		$group, $limit = 0, $offset = null, 
		$searchletter = '', $searchkeyword = '', $requestedowners = array(), 
		$radiusowners = array(), $namefilters = array()
	) {
        $params       = array();
        $params['group'] = $group;
        $sql = '
            SELECT
                [[groups]].[id] AS group_id, [[users]].[id] AS user_id, [[users]].[nickname] AS user_name,
                [[users]].[email] AS user_email, [[users_groups]].[status] as group_status, [[users_groups]].[updated] as group_updated, 
				[[users]].[checksum] AS user_checksum
            FROM [[users_groups]]
            INNER JOIN [[users]]  ON [[users]].[id] =  [[users_groups]].[user_id]
            INNER JOIN [[groups]] ON [[groups]].[id] = [[users_groups]].[group_id]
            WHERE ([[groups]].[id] > 0)';
        if (is_numeric($group)) {
            $sql .= ' AND [[groups]].[id] = {group}';
        } else {
            $sql .= ' AND [[groups]].[name] = {group}';
        }
		
		$GLOBALS['db']->dbc->loadModule('Function', null, true);
		$numbers = array();
		$letters = array();
		if (strtolower($searchletter) == 'num') {
			$numbers = array('0','1','3','4','5','6','7','8','9');
		} else {
			$letters = array(
			'A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z',
			'a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z'
			);
		}
		$searchletter = (empty($searchletter) && empty($searchkeyword) ? '' : (!empty($searchletter) && !in_array($searchletter, $letters) && strtolower($searchletter) != 'num' ? 'A' : $searchletter));
		if (!empty($searchletter)) {
			if (strtolower($searchletter) == 'num') {
				$first_title = $GLOBALS['db']->dbc->function->substring('[[users]].[company]', 1, 1);
				$first_name = $GLOBALS['db']->dbc->function->substring('[[users]].[nickname]', 1, 1);
				$sql .= ' AND (';
				for ($i=0; $i<10; $i++) {
					$sql .= " $first_title = {letter".$i."} OR"; 
					$sql .= " $first_name = {letter".$i."}"; 
					$sql .= ($i==9 ? ')' : " OR");
					$params['letter'.$i] = (string)$i;
				}
			} else {
				$first_title = $GLOBALS['db']->dbc->function->lower($GLOBALS['db']->dbc->function->substring('[[users]].[company]', 1, 1));
				$first_name = $GLOBALS['db']->dbc->function->lower($GLOBALS['db']->dbc->function->substring('[[users]].[nickname]', 1, 1));
				$sql .= " AND ($first_title = {letter} OR $first_name = {letter})"; 
				$params['letter'] = strtolower($searchletter);
			}
		}
		
		if (!empty($searchkeyword)) {
			$first_title = $GLOBALS['db']->dbc->function->lower('[[users]].[company]');
			$first_name = $GLOBALS['db']->dbc->function->lower('[[users]].[nickname]');
			$first_username = $GLOBALS['db']->dbc->function->lower('[[users]].[username]');
			$first_url = $GLOBALS['db']->dbc->function->lower('[[users]].[url]');
			$first_address = $GLOBALS['db']->dbc->function->lower('[[users]].[address]');
			$first_city = $GLOBALS['db']->dbc->function->lower('[[users]].[city]');
			$first_region = $GLOBALS['db']->dbc->function->lower('[[users]].[region]');
			$first_postal = $GLOBALS['db']->dbc->function->lower('[[users]].[postal]');
			$first_country = $GLOBALS['db']->dbc->function->lower('[[users]].[country]');
			$first_keywords = $GLOBALS['db']->dbc->function->lower('[[users]].[keywords]');
			$first_company_type = $GLOBALS['db']->dbc->function->lower('[[users]].[company_type]');
			$sql .= " AND (
				$first_title LIKE {keyword} OR $first_name LIKE {keyword} OR 
				$first_username LIKE {keyword} OR $first_url LIKE {keyword} OR 
				$first_address LIKE {keyword} OR $first_city LIKE {keyword} OR 
				$first_region LIKE {keyword} OR $first_postal LIKE {keyword} OR 
				$first_country LIKE {keyword} OR $first_keywords LIKE {keyword} OR 
				$first_company_type LIKE {keyword} 
			)"; 
			$params['keyword'] = '%'.strtolower($searchkeyword).'%';
		}
		
		$count_filters = count($namefilters);
		if (!$count_filters <= 0) {
			$sql .= ' AND (';
			$i = 0;
			foreach ($namefilters as $filter) {
				$filter_company_type = $GLOBALS['db']->dbc->function->lower('[[users]].[company_type]');
				$sql .= " ($filter_company_type = {company_type})"; 
				$params['company_type'] = strtolower(preg_replace("[^A-Za-z0-9]", '', $filter));
				$i++;
				if ($i < $count_filters) {
					$sql .= " OR ";
				}
			}
			$sql .= ')';
		}
		
		$count_owners = count($requestedowners);
		if (!$count_owners <= 0) {
			$sql .= ' AND (';
			$i = 0;
			foreach ($requestedowners as $owner) {
				$user_id = $GLOBALS['db']->dbc->function->lower('[[users]].[id]');
				$sql .= " ($user_id = {id})"; 
				$params['id'] = $owner;
				$i++;
				if ($i < $count_owners) {
					$sql .= " OR ";
				}
			}
			$sql .= ')';
		}
		
		$count_radius = count($radiusowners);
		if (!$count_radius <= 0) {
			$sql .= ' AND (';
			$i = 0;
			foreach ($radiusowners as $owner) {
				$user_id = $GLOBALS['db']->dbc->function->lower('[[users]].[id]');
				$sql .= " ($user_id = {id})"; 
				$params['id'] = $owner;
				$i++;
				if ($i < $count_radius) {
					$sql .= " OR ";
				}
			}
			$sql .= ')';
		}
		
        if (!empty($limit)) {
            $result = $GLOBALS['db']->setLimit($limit, $offset);
            if (Jaws_Error::IsError($result)) {
                return false;
            }
        }
        
		$result = $GLOBALS['db']->queryAll($sql, $params);
        if (Jaws_Error::IsError($result)) {
            return false;
        }

        return $result;
    }

    /**
     * Adds a new user
     *
     * @access  public
     * @param   string  $username   The username
     * @param   string  $nickname      User's display name
     * @param   string  $email      User's email
     * @param   string  $password   User's password
     * @param   string  $type       User's type (ADMIN or NORMAL)
     * @return  boolean Returns true if user was sucessfully added, false if not
     */
    function AddUser($username, $nickname, $email, $password, $type = 2, $enabled = true, 
		$checksum = '', $address = '', $address2 = '', $city = '', $country = '', 
		$region = '', $postal = '')
    {
		if (isset($GLOBALS['log'])) {
			$log_args = func_get_args();
			$GLOBALS['log']->Log(JAWS_LOG_INFO, "Jaws-User->AddUser() ".implode(', ',$log_args));
		}
        //We already have a $username in the DB?
        $info = $this->GetUserInfoByName($username, true);
        //username exists
        if (Jaws_Error::IsError($info) || isset($info['username'])) {
			if (isset($GLOBALS['log'])) {
				$GLOBALS['log']->Log(JAWS_LOG_ERR, "Jaws_User->AddUser() result: User exists: username: ".$username);
			}
            return false;
        }
        //Check username
        if ($type > 1 && ((!preg_match('/^[a-z0-9]+$/i', $username) || strlen($username) < 3) 
			|| strtolower($username) == 'login' || strtolower($username) == 'register'
			|| strtolower($username) == 'logout' || strtolower($username) == 'profile'
			|| strtolower($username) == 'forget' || strtolower($username) == 'recover' 
			|| strtolower($username) == 'custom' || strtolower($username) == 'directory'
			|| strtolower($username) == 'friend' 
			|| substr(strtolower($username), 0, 4) == 'test' 
			|| substr(strtolower($username), 0, 4) == 'root' 
			|| substr(strtolower($username), 0, 4) == 'info' 
			|| substr(strtolower($username), 0, 5) == 'admin')) {
			if (isset($GLOBALS['log'])) {
				$GLOBALS['log']->Log(JAWS_LOG_ERR, "Jaws_User->AddUser() result: Username not valid: username: ".$username);
			}
            return false;
        }

        if (is_numeric($type)) {
            $type = ($type > 2 && $type < 0) ? 2 : (int)$type;
        }

        $params = array();
        $params['username'] = $username;
        $params['nickname'] = $nickname;
        $params['email']    = $email;
        $params['password'] = $this->GetHashedPassword($password);
        $params['type']     = $type;
        $params['now']      = $GLOBALS['db']->Date();
        $params['enabled']  = (bool)$enabled;
        $params['address']  = $address;
        $params['address2'] = $address2;
        $params['city']  	= $city;
        $params['country']  = $country;
        $params['region']  	= $region;
        $params['postal']  	= $postal;
        $params['checksum'] = $checksum;

        $sql = '
            INSERT INTO [[users]]
                (
				 [username], [nickname], [email], [passwd], [user_type],
                 [createtime], [updatetime], [enabled], [checksum], 
				 [address], [address2], [city], [country], [region], [postal]
				)
            VALUES
                (
				 {username}, {nickname}, {email}, {password}, {type},
                 {now}, {now}, {enabled}, {checksum}, 
				 {address}, {address2}, {city}, {country}, {region}, {postal}
				)';

        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
			if (isset($GLOBALS['log'])) {
				$GLOBALS['log']->Log(JAWS_LOG_ERR, "Jaws_User->AddUser() result: ".var_export($result, true));
			}
            return $result;
        }

        // Fetch the id of the user that was just created
        $id = $GLOBALS['db']->lastInsertID('users', 'id');
        if (Jaws_Error::IsError($id)) {
			if (isset($GLOBALS['log'])) {
				$GLOBALS['log']->Log(JAWS_LOG_ERR, "Jaws_User->AddUser() result: ".var_export($id, true));
			}
            return false;
		}

        // Update users_groups table
        if ($type == 2) {
	        if (!$this->AddUserToGroup($id, 1)) {
				if (isset($GLOBALS['log'])) {
					$GLOBALS['log']->Log(JAWS_LOG_ERR, "Jaws_User->AddUser() result: Could not add user: ".var_export($id, true)." to group: 1");
				}
	            return false;
	        }
		}

		if (empty($checksum)) {
			// Update checksum
			$config_key = $GLOBALS['app']->Registry->Get('/config/key');		
			$params2               = array();
			$params2['id'] = $id;
			$params2['checksum'] = $id.':'.$config_key;
			
			$sql = '
				UPDATE [[users]] SET
					[checksum] = {checksum}
				WHERE [id] = {id}';

			$result = $GLOBALS['db']->query($sql, $params2);
			if (Jaws_Error::IsError($result)) {
				if (isset($GLOBALS['log'])) {
					$GLOBALS['log']->Log(JAWS_LOG_ERR, "Jaws_User->AddUser() result: ".var_export($result, true));
				}
				return false;
			}
		}
		
        if (isset($GLOBALS['app']->Session) && $GLOBALS['app']->Session->GetAttribute('user_id') == $id) {
            $GLOBALS['app']->Session->SetAttribute('username', $username);
        }		
        
		$groups = $this->GetGroupsOfUser($id);
        
		// Update users_gadgets table
        $jms = $GLOBALS['app']->LoadGadget('Jms', 'AdminModel');
        
		// Add ALL GADGETS to users_gadgets
		$gadget_list = $jms->GetGadgetsList(null, true, true, null);
		$i = 0;
		if ($type < 2) {
			$gadget_acls = array(
				'ControlPanel/default',
				'ControlPanel/DatabaseBackups',
				'Settings/ManageSettings',
				'Layout/ManageLayout',
				'Layout/ManageThemes',
				'Users/default',
				'Users/EditAccountInformation',
				'Users/EditAccountPassword',
				'Users/EditAccountPreferences',
				'Users/EditAccountProfile',
				'Users/ManageUsers',
				'Users/ManageGroups',
				'Users/ManageACL',
				'Jms/ManagePlugins',
				'Policy/ManagePolicy',
				'Policy/IPBlocking',
				'Policy/ManageIPs',
				'Policy/AgentBlocking',
				'Policy/ManageAgents',
				'Policy/Encryption',
				'Policy/AntiSpam',
				'Policy/AdvancedPolicies',
				'Menu/default',
				'Menu/ManageMenus',
				'Menu/ManageGroups'
			);	
		} else {
			$gadget_acls = array(
				'Users/EditAccountInformation',
				'Users/EditAccountPassword',
				'Users/EditAccountPreferences',
				'Users/EditAccountProfile'
			);
		}

        //Hold.. if we dont have a selected gadget?.. like no gadgets?
        if (!count($gadget_list) <= 0) {
	       reset($gadget_list);
			
	       foreach ($gadget_list as $gadget) {
				// add "Own" or non-core "Full" permissions to ACL array
				$gInfo = $GLOBALS['app']->LoadGadget($gadget['realname'], 'Info');
				$acl_keys = $gInfo->GetACLs();
				$core = $gInfo->GetAttribute('core_gadget');
				if (!count($acl_keys) <= 0) {
					reset($acl_keys);
					foreach ($acl_keys as $acl_key => $acl_val) {
						$key_name = strrchr($acl_key, "/");
						if ((!$core || is_null($core)) && $type < 2) {
							if (!in_array($gadget['realname'].$key_name, $gadget_acls, TRUE)) {
								array_push($gadget_acls, $gadget['realname'].$key_name);
							}
						} else if (strpos($key_name,"Own") !== false) {
							if (!in_array($gadget['realname'].$key_name, $gadget_acls, TRUE)) {
								array_push($gadget_acls, $gadget['realname'].$key_name);
							}
						}
					}
				}
				unset($gInfo);
								
			}
		}
		
		// grant gadget permissions based on ACL array
		$userAdminModel = $GLOBALS['app']->LoadGadget('Users', 'AdminModel');
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');
		if (!count($gadget_acls) <= 0) {
			reset($gadget_acls);
			foreach ($gadget_acls as $gadget_acl) {
				$g_acl = $userAdminModel->UpdateUserACL($id, array('/ACL/users/' . $username . '/gadgets/'.$gadget_acl => true), false);
				/*
				foreach ($groups as $group) {
					if ($group['group_status'] == 'active' || $group['group_status'] == 'founder' || $group['group_status'] == 'admin') {
						$group_acl = $userAdminModel->UpdateGroupACL($group['group_id'], array('/ACL/groups/'.$group['group_id'].'/gadgets/'.$gadget_acl => true));
					}
				}
				*/
				if (Jaws_Error::IsError($g_acl)) {
					if (isset($GLOBALS['log'])) {
						$GLOBALS['log']->Log(JAWS_LOG_ERR, "Jaws_User->AddUser() result: ".var_export($g_acl, true));
					}
					return false;
				}
			}
		}

		// Let everyone know a user has been added
        $GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
        $res = $GLOBALS['app']->Shouter->Shout('onAddUser', $id);
        if (Jaws_Error::IsError($res) || !$res) {
			if (isset($GLOBALS['log'])) {
				$GLOBALS['log']->Log(JAWS_LOG_ERR, "Jaws_User->AddUser() result: shouter error: ".var_export($res, true));
			}
            return false;
        }

		if (isset($GLOBALS['log'])) {
			$GLOBALS['log']->Log(JAWS_LOG_INFO, "Jaws_User->AddUser() result: ".var_export($id, true));
		}
        return $id;
    }

    /**
     * Adds a new group
     *
     * @access  public
     * @param   string  $name        Group's name
     * @param   string  $title       Group's title
     * @param   string  $description Group's description
     * @param   boolean $removable   (Optional) Can the group be removed by users (via UI)?
     * @return  boolean Returns true if group  was sucessfully added, false if not
     */
    function AddGroup($name, $title = '', $description = '', $removable = true, $checksum = '', $founder = 0)
    {
		//We already have a $groupname in the DB?
        $info = $this->GetGroupInfoByName($name);
        if (isset($info['name'])) {
            //groupname exists
            return false;
        }

        //Check groupname
        if ((!preg_match('/^[a-zA-Z0-9_]+$/i', $name) || strlen($name) < 3) 
			|| strtolower($name) == 'friend' || strtolower($name) == 'request'
			|| strtolower($name) == 'requested' || strtolower($name) == 'directory' 
			|| substr(strtolower($name), 0, 4) == 'test' 
			|| substr(strtolower($name), 0, 4) == 'root') {
            return false;
        }
		
		if (empty($title)) {
			$title = $name;
		}

        $params = array();
        $params['name']        	= $name;
        $params['title']       	= $title;
        $params['description'] 	= $description;
        $params['checksum'] 	= $checksum;
        $params['removable']   	= (is_bool($removable)) ? $removable : true;
        
        $sql = '
            INSERT INTO [[groups]]
                ([name], [title], [description], [removable], [checksum])
            VALUES
                ({name}, {title}, {description}, {removable}, {checksum})';

        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
            return false;
        }

        // Fetch the id of the group that was just created
        $id = $GLOBALS['db']->lastInsertID('groups', 'id');
        if (Jaws_Error::IsError($id)) {
            return false;
        }

		if (empty($checksum)) {
			// Update checksum
			$config_key = $GLOBALS['app']->Registry->Get('/config/key');		
			$params = array();
			$params['id'] = $id;
			$params['checksum'] = $id.':'.$config_key;
			
			$sql = '
				UPDATE [[groups]] SET
					[checksum] = {checksum}
				WHERE [id] = {id}';

			$result = $GLOBALS['db']->query($sql, $params);
			if (Jaws_Error::IsError($result)) {
				return false;
			}
        }
		
        if ($founder > 0) {
			$result2 = $this->AddUserToGroup($founder, $id, 'founder');
			if ($result2 !== true) {
				return false;
			}
		}

        // Let everyone know a group has been added
        $GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
        $res = $GLOBALS['app']->Shouter->Shout('onAddGroup', $id);
        if (Jaws_Error::IsError($res) || !$res) {
            //do nothing
        }

        return $id;
    }

    /**
     * Update the info of an user
     *
     * @access  public
     * @param   int     $id         User's ID
     * @param   string  $username   The username
     * @param   string  $nickname      User's display name
     * @param   string  $email      User's email
     * @param   string  $password   User's password
     * @param   string  $type       Type of user to use
     * @param   boolean $enabled    enable/disable user
     * @return  boolean Returns true if user was sucessfully updated, false if not
     */
    function UpdateUser($id, $username, $nickname, $email, $password = null, $type = null, $enabled = null)
    {
		$GLOBALS['app']->Registry->LoadFile('Users');
		$GLOBALS['app']->Translate->LoadTranslation('Users', JAWS_GADGET);
        //Check username
        if ((!preg_match('/^[a-z0-9]+$/i', $username) || strlen($username) < 3) 
			|| strtolower($username) == 'login' || strtolower($username) == 'register'
			|| strtolower($username) == 'logout' || strtolower($username) == 'profile'
			|| strtolower($username) == 'forget' || strtolower($username) == 'recover' 
			|| strtolower($username) == 'custom' || strtolower($username) == 'directory'
			|| strtolower($username) == 'friend'
			|| substr(strtolower($username), 0, 4) == 'test' 
			|| substr(strtolower($username), 0, 4) == 'root' 
			|| substr(strtolower($username), 0, 4) == 'info') {
            return _t('USERS_REGISTER_USERNAME_NOT_VALID');
        }

        $params = array();
        $params['id']         = $id;
        $params['username']   = $username;
        $params['nickname']   = $nickname;
        $params['password']   = $this->GetHashedPassword($password);
		$params['type']       = $type;
        $params['updatetime'] = $GLOBALS['db']->Date();
        $params['enabled']    = (bool)$enabled;

        $sql = '
            UPDATE [[users]] SET
                [username] = {username},
                [nickname] = {nickname},
                [updatetime] = {updatetime} ';
        if (!empty($password)) {
            $sql .= ', [passwd] = {password} ';
        }
        if (!is_null($type)) {
            $sql .= ', [user_type] = {type} ';
        }
        if ($GLOBALS['app']->Session->IsAdmin() || $GLOBALS['app']->Session->IsSuperAdmin()) {
			$params['email']   	  = $email;
            $sql .= ', [email] = {email} ';
        }
        if (!is_null($enabled)) {
            $sql .= ', [enabled] = {enabled} ';
			if ((bool)$enabled === false) {
				$sql .= ', [closetime] = {updatetime} ';
			}
        }

        $sql .= 'WHERE [id] = {id}';

        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
            return $result;
        }

        if (isset($GLOBALS['app']->Session) && $GLOBALS['app']->Session->GetAttribute('user_id') == $id) {
            $GLOBALS['app']->Session->SetAttribute('nickname', $nickname);
            $GLOBALS['app']->Session->SetAttribute('username', $username);
        }

        // Let everyone know a user has been updated
        $GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
        $res = $GLOBALS['app']->Shouter->Shout('onUpdateUser', $id);
        if (Jaws_Error::IsError($res) || !$res) {
            return false;
        }

        return true;
    }
    
	/**
     * Update the password of an user
     *
     * @access  public
     * @param   int     $id         User's ID
     * @param   string  $username   The username
     * @param   string  $name       User's name
     * @param   string  $email      User's email
     * @param   string  $passwd     User's password(md5ed)
     * @return  boolean Returns true if user was sucessfully updated, false if not
     */
    function UpdatePassword($id, $username, $name, $email, $passwd)
    {
		$GLOBALS['app']->Registry->LoadFile('Users');
		$GLOBALS['app']->Translate->LoadTranslation('Users', JAWS_GADGET);
        //Check username
        if ((!preg_match('/^[a-z0-9]+$/i', $username) || strlen($username) < 3) 
			|| strtolower($username) == 'login' || strtolower($username) == 'register'
			|| strtolower($username) == 'logout' || strtolower($username) == 'profile'
			|| strtolower($username) == 'forget' || strtolower($username) == 'recover' 
			|| strtolower($username) == 'custom' || strtolower($username) == 'directory' 
			|| strtolower($username) == 'friend'
			|| substr(strtolower($username), 0, 4) == 'test' 
			|| substr(strtolower($username), 0, 4) == 'root') {
            return _t('USERS_REGISTER_USERNAME_NOT_VALID');
        }

        
		$params               = array();
        $params['id']         = $id;
        $params['username']   = $username;
        $params['name']       = $name;
        //$params['email']      = $email;
        $params['passwd']     = $this->GetHashedPassword($passwd);
        $params['updatetime'] = $GLOBALS['db']->Date();
        
		$sql = '
            UPDATE [[users]] SET
                [username] = {username},
                [nickname] = {name},
                [updatetime] = {updatetime},
				[passwd] = {passwd}
			WHERE [id] = {id}';

        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
            return false;
        }

        if (isset($GLOBALS['app']->Session) && $GLOBALS['app']->Session->GetAttribute('user_id') == $id) {
            $GLOBALS['app']->Session->SetAttribute('username', $username);
        }

        // Let everyone know a user has been updated
        $GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
        $res = $GLOBALS['app']->Shouter->Shout('onUpdateUser', $id);
        if (Jaws_Error::IsError($res) || !$res) {
            return false;
        }

        return true;
    }

    /**
     * Update personal information of a user such as fname, lname, gender, etc..
     *
     * @access  public
     * @param   int     $id    User's ID
     * @param   array   $info  Personal information
     * @return  boolean Returns true on success, false on failure
     */
    function UpdatePersonalInfo($id, $info = array())
    {
		$userModel = $GLOBALS['app']->LoadGadget('Users', 'Model');
		$validInfo = array(
			'fname', 'lname', 'gender', 'dob', 'url', 
			'company', 'address', 'address2', 'city', 'country', 
			'region', 'postal', 'phone', 'office', 'tollfree', 
			'fax', 'merchant_id', 'description', 'logo', 
			'keywords', 'company_type'
		);
        $params    = array();
        $updateStr = '';
        foreach($info as $k => $v) {
            if (in_array($k, $validInfo)) {
        		if ($k == 'logo') {
					$v = $userModel->cleanImagePath($v);
                }
				$params[$k] = $v;
                $updateStr.= '['. $k . '] = {'.$k.'}, ';
            }
        }

        if (count($params) > 0) {
            $updateStr = substr($updateStr, 0, -2);
            $params['id'] = $id;
            $sql = 'UPDATE [[users]] SET '.$updateStr.
                ' WHERE [id] = {id}';
            $result = $GLOBALS['db']->query($sql, $params);
            if (Jaws_Error::IsError($result)) {
                return $result;
            }

            if (isset($GLOBALS['app']->Session) && $GLOBALS['app']->Session->GetAttribute('user_id') == $id) {
                foreach($params as $k => $v) {
                    $GLOBALS['app']->Session->SetAttribute($k, $v);
                }
            }
        }

        // Let everyone know a user has been updated
        $GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
        $res = $GLOBALS['app']->Shouter->Shout('onUpdateUser', $id);
        if (Jaws_Error::IsError($res) || !$res) {
            return false;
        }

        return true;
    }

    /**
     * Update advanced options of a user such as language, theme, editor, etc..
     *
     * @access  public
     * @param   int     $id    User's ID
     * @param   array   $opts  Advanced options
     * @return  boolean Returns true on success, false on failure
     */
    function UpdateAdvancedOptions($id, $opts = array())
    {
        $validOptions = array(
			'language', 'theme', 'editor', 'timezone', 'notification', 
			'allow_comments', 'identifier', 'new_messages'
		);
        $params       = array();
        $updateStr    = '';
        foreach($opts as $k => $v) {
            if (in_array($k, $validOptions)) {
                if ($k == 'identifier' && $v === null) {
				} else {
					$params[$k] = $v;
					$updateStr.= '['. $k . '] = {'.$k.'}, ';
				}
			}
        }

        if (count($params) > 0) {
            $updateStr = substr($updateStr, 0, -2);
            $params['id'] = $id;
            $sql = 'UPDATE [[users]] SET '.$updateStr.
                ' WHERE [id] = {id}';
            $result = $GLOBALS['db']->query($sql, $params);
            if (Jaws_Error::IsError($result)) {
                return false;
            }

            if (isset($GLOBALS['app']->Session) && $GLOBALS['app']->Session->GetAttribute('user_id') == $id) {
                foreach($params as $k => $v) {
                    $GLOBALS['app']->Session->SetAttribute($k, $v);
                }
            }
        }
        
		// Let everyone know a user has been updated
        $GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
        $res = $GLOBALS['app']->Shouter->Shout('onUpdateUser', $id);
        if (Jaws_Error::IsError($res) || !$res) {
            return false;
        }

        return true;
    }

    /**
     * Update the info of a group
     *
     * @access  public
     * @param   int     $id          Group's ID
     * @param   string  $name        Group's title
     * @param   string  $title       Group's name
     * @param   string  $description Group's description
     * @return  boolean Returns true if group was sucessfully updated, false if not
     */
    function UpdateGroup($id, $name, $title, $description, $checksum = null, $founder = null)
    {
        //Check groupname
        if ((!preg_match('/^[a-zA-Z0-9_]+$/i', $name) || strlen($name) < 3) 
			|| strtolower($name) == 'friend' || strtolower($name) == 'request'
			|| strtolower($name) == 'requested' || strtolower($name) == 'directory' 
			|| substr(strtolower($name), 0, 4) == 'test' 
			|| substr(strtolower($name), 0, 4) == 'root') {
            return false;
        }
		
        $params = array();
        $params['id']          	= $id;
        $params['name']        	= $name;
        $params['title']       	= $title;
        $params['description'] 	= $description;

        $sql = '
            UPDATE [[groups]] SET
                [name]        	= {name},
                [title]       	= {title},
                [description] 	= {description} 
		';
        if (!is_null($checksum)) {
			$params['checksum']	= $checksum;
			$sql .= ', [checksum] = {checksum}';
		}
        if (!is_null($founder)) {
			$params['founder'] 	= $founder;
			$sql .= ', [founder] = {founder}';
		}
		$sql .= '
            WHERE [id] = {id}';

        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
            return false;
        }

        if ($founder > 0) {
			$result2 = $this->AddUserToGroup($founder, $id, 'founder');
			if ($result2 !== true) {
				return false;
			}
		}
		
		// Let everyone know a group has been updated
        $GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
        $res = $GLOBALS['app']->Shouter->Shout('onUpdateGroup', $id);
        if (Jaws_Error::IsError($res) || !$res) {
            //do nothing
        }

        return true;
    }

    /**
     * Deletes an user
     *
     * @access  public
     * @param   int     $id     User's ID
     * @return  boolean Returns true if user was sucessfully deleted, false if not
     */
    function DeleteUser($id)
    {
        $sql = 'SELECT COUNT([id]) FROM [[users]]';
        $c = $GLOBALS['db']->queryOne($sql);
        if (Jaws_Error::IsError($c)) {
            return false;
        }

        if ($c > '1') {
            $user = $this->GetUserInfoById($id, true);
            if (!$user) {
                return false;
            }

            // Let everyone know that a user has been deleted
            $GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
            $res = $GLOBALS['app']->Shouter->Shout('onDeleteUser', $id);
            if (Jaws_Error::IsError($res) || !$res) {
                return false;
            }

            $params = array();
            $params['id'] = $id;
            $sql = 'DELETE FROM [[users]] WHERE [id] = {id}';
            $result = $GLOBALS['db']->query($sql, $params);
            if (Jaws_Error::IsError($result)) {
                return false;
            }

            $sql = 'DELETE FROM [[users_groups]] WHERE [user_id] = {id}';
            $result = $GLOBALS['db']->query($sql, $params);
            if (Jaws_Error::IsError($result)) {
                return false;
            }

            $GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');
            $GLOBALS['app']->ACL->DeleteUserACL($user['username']);

            if (isset($GLOBALS['app']->Session)) {
                $res = $GLOBALS['app']->Session->_cache->DeleteUserSessions($id);
                if (!$res) {
                    return false;
                }
            }

			require_once JAWS_PATH . 'include/Jaws/FileManagement.php';
			if (file_exists(JAWS_DATA . 'files/users/'.$id)) {
				$return = Jaws_FileManagement::FullRemoval(JAWS_DATA . 'files/users/'.$id);
			}
			if (file_exists(JAWS_DATA . 'files/css/users/'.$id)) {
				$return = Jaws_FileManagement::FullRemoval(JAWS_DATA . 'files/css/users/'.$id);
			}
			
            return true;
        }

        return false;
    }


    /**
     * Deletes a group
     *
     * @access  public
     * @param   int     $id     Group's ID
     * @return  boolean Returns true if group was sucessfully deleted, false if not
     */
    function DeleteGroup($id)
    {
        if ($this->canRemoveGroup($id) === false) {
            return false;
        }
        
        // Let everyone know a group has been deleted
        $GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
        $res = $GLOBALS['app']->Shouter->Shout('onDeleteGroup', $id);
        if (Jaws_Error::IsError($res) || !$res) {
            return false;
        }

        $params = array();
        $params['id'] = $id;
        $sql = 'DELETE FROM [[groups]] WHERE [id] = {id}';

        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
            return false;
        }

        $sql = 'DELETE FROM [[users_groups]] WHERE [group_id] = {id}';
        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
            return false;
        }

        $GLOBALS['app']->ACL->DeleteGroupACL($id);
		
		if (file_exists(JAWS_DATA . 'files/css/groups/'.$id)) {
			require_once JAWS_PATH . 'include/Jaws/FileManagement.php';
			$return = Jaws_FileManagement::FullRemoval(JAWS_DATA . 'files/css/groups/'.$id);
		}
		
        return true;
    }

    /**
     * Adds an user to a group
     *
     * @access  public
     * @param   int     $user   User's ID
     * @param   int     $group  Group's ID
     * @return  boolean Returns true if user was sucessfully added to the group, false if not
     */
    function AddUserToGroup($user, $group, $status = 'active')
    {
		if (isset($GLOBALS['log'])) {
			$log_args = func_get_args();
			$GLOBALS['log']->Log(JAWS_LOG_INFO, "Jaws_User->AddUserToGroup() ".implode(', ',$log_args));
		}
		$status = strtolower($status);
		if (!in_array($status, array('active', 'request', 'denied', 'admin', 'founder'))) {
			if (isset($GLOBALS['log'])) {
				$GLOBALS['log']->Log(JAWS_LOG_INFO, "Jaws_User->AddUserToGroup() result: unknown status column: ".var_export($status, true));
			}
			return false;
		}
		$info = array();
		if ($GLOBALS['app']->Session->Logged()) {
			$info = $this->GetUserInfoById((int)$GLOBALS['app']->Session->GetAttribute('user_id'), true, true, true, true);
			if ((Jaws_Error::IsError($info) || !isset($info['id']) || empty($info['id']))) {
				if (isset($GLOBALS['log'])) {
					$GLOBALS['log']->Log(JAWS_LOG_INFO, "Jaws_User->AddUserToGroup() result: Error getting logged user: ".var_export($info, true));
				}
				//return false;
			}
		}
		$userInfo = $info;
		if ((int)$user != $info['id']) {
			$userInfo = $this->GetUserInfoById((int)$user);
			if (Jaws_Error::IsError($userInfo) || !isset($userInfo['id']) || empty($userInfo['id'])) {
				if (isset($GLOBALS['log'])) {
					$GLOBALS['log']->Log(JAWS_LOG_INFO, "Jaws_User->AddUserToGroup() result: Error getting added user's info: ".var_export($userInfo, true));
				}
				return false;
			}
			if (!isset($info['id'])) {
				$GLOBALS['log']->Log(JAWS_LOG_INFO, "Jaws_User->AddUserToGroup() result: There is no logged user, setting added user");
				$info = $userInfo;
			}
		}
		$user_status = $this->GetStatusOfUserInGroup($info['id'], $groupInfo['id']);
		if (Jaws_Error::IsError($user_status)) {
			if (isset($GLOBALS['log'])) {
				$GLOBALS['log']->Log(JAWS_LOG_INFO, "Jaws_User->AddUserToGroup() result: Error getting user's status in groups: ".var_export($user_status, true));
			}
			$user_status = '';
		}
		$user_groups = $this->GetGroupsOfUser($info['id']);
		if (Jaws_Error::IsError($user_groups)) {
			if (isset($GLOBALS['log'])) {
				$GLOBALS['log']->Log(JAWS_LOG_INFO, "Jaws_User->AddUserToGroup() result: Error getting user's groups: ".var_export($user_groups, true));
			}
			return false;
		}
		$groupInfo = $this->GetGroupInfoById((int)$group);
        if (Jaws_Error::IsError($groupInfo) || !isset($groupInfo['id']) || empty($groupInfo['id'])) {
			if (isset($GLOBALS['log'])) {
				$GLOBALS['log']->Log(JAWS_LOG_INFO, "Jaws_User->AddUserToGroup() result: Error getting group info: ".var_export($groupInfo, true));
			}
			return false;		
		}
		// Only one founder per group.
		// Founders can add others as founders of their groups, but relinquish foundership upon doing so.
		$revoke_foundership = false;
		if ($status == 'founder') {
			$founders = $this->GetUsersOfGroupByStatus($groupInfo['id'], 'founder');
			if (!Jaws_Error::IsError($founders) && !count($founders) <= 0) {
				if ($user_status == 'founder') {
					$revoke_foundership = true;
				} else {
					if (isset($GLOBALS['log'])) {
						$GLOBALS['log']->Log(JAWS_LOG_INFO, "Jaws_User->AddUserToGroup() result: Error getting founders: ".var_export($founders, true));
					}
					return false;
				}
			}
		}
		
		if (!isset($GLOBALS['app']->ACL)) {
			$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		}
		if (
			($groupInfo['id'] == 1 && !$this->UserIsInGroup($userInfo['id'], $groupInfo['id'])) || 
			($userInfo['id'] == $info['id'] && $status == 'request') || 
			in_array($user_status, array('founder','admin')) || 
			$GLOBALS['app']->ACL->GetFullPermission(
				$info['username'], 
				$user_groups, 'Users', 'ManageGroups')
		) {
			$params = array();
			$params['user']  = $userInfo['id'];
			$params['group'] = $groupInfo['id'];
			$params['status'] = $status;
			$enabled = (in_array($status, array('active','admin','founder')) ? true : false);
			
			if (!$this->UserIsInGroup($userInfo['id'], $groupInfo['id'])) {
				$sql = '
					INSERT INTO [[users_groups]]
						([user_id], [group_id], [status])
					VALUES
						({user}, {group}, {status})';
			} else {
				$sql = '
					UPDATE [[users_groups]] SET
						[status] = {status}
					WHERE ([user_id] = {user} AND [group_id] = {group})';
			}
			
			$result = $GLOBALS['db']->query($sql, $params);
			if (Jaws_Error::IsError($result)) {
				if (isset($GLOBALS['log'])) {
					$GLOBALS['log']->Log(JAWS_LOG_INFO, "Jaws_User->AddUserToGroup() result: ".var_export($result, true));
				}
				return false;
			}
			
			if ($revoke_foundership === true) {
				$params = array();
				$params['user']  = $info['id'];
				$params['group'] = $groupInfo['id'];
				$params['status'] = 'admin';
				$sql = '
					UPDATE [[users_groups]] SET
						[status] = {status}
					WHERE ([user_id] = {user} AND [group_id] = {group})';
				$result = $GLOBALS['db']->query($sql, $params);
				if (Jaws_Error::IsError($result)) {
					if (isset($GLOBALS['log'])) {
						$GLOBALS['log']->Log(JAWS_LOG_INFO, "Jaws_User->AddUserToGroup() result: ".var_export($result, true));
					}
					return false;
				}
			}
			
			
			// get all gadgets with account panes
			$jms = $GLOBALS['app']->LoadGadget('Jms', 'AdminModel');
			$gadget_list = $jms->GetGadgetsList(null, true, true, null);

			//Hold.. if we dont have a selected gadget?.. like no gadgets?
			if (!count($gadget_list) <= 0) {
				reset($gadget_list);
				foreach ($gadget_list as $gadget) {
					if (substr(strtolower($groupInfo['name']), 0, strlen(strtolower($gadget['realname']))) == strtolower($gadget['realname'])) {
						$paneGadget = $this->UpdateUsersGadgets($userInfo['id'], $gadget['realname'], 'maximized', null, true, $enabled);								
					}
				}
			}
			
			// Let everyone know
			$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
			$res = $GLOBALS['app']->Shouter->Shout('onAddUserToGroup', array('user_id' => $userInfo['id'], 'group_id' => $groupInfo['id'], 'status' => $status, 'old_status' => $user_status));
			if (Jaws_Error::IsError($res) || !$res) {
				if (isset($GLOBALS['log'])) {
					$GLOBALS['log']->Log(JAWS_LOG_INFO, "Jaws_User->AddUserToGroup() result: shouter error: ".var_export($res, true));
				}
				return false;
			}
			if (isset($GLOBALS['log'])) {
				$GLOBALS['log']->Log(JAWS_LOG_INFO, "Jaws_User->AddUserToGroup() result: true");
			}
			return true;
		}
		if (isset($GLOBALS['log'])) {
			$GLOBALS['log']->Log(JAWS_LOG_INFO, "Jaws_User->AddUserToGroup() result: false, logged user: ".var_export($info, true).", ACL: ".
				var_export($GLOBALS['app']->ACL->GetFullPermission(
				$info['username'], 
				$user_groups, 'Users', 'ManageGroups'), true));
		}
		return false;
    }
    
	/**
     * Adds an user to a group
     *
     * @access  public
     * @param   int     $user   User's ID
     * @param   int     $group  Group's ID
     * @return  boolean Returns true if user was sucessfully added to the group, false if not
     */
    function AddUserToGroupName($user, $group, $status = 'active')
    {
        $groupInfo = $this->GetGroupInfoByName($group);
        if (!Jaws_Error::IsError($groupInfo) && isset($groupInfo['id'])) {
			return $this->AddUserToGroup($user, $groupInfo['id'], $status);
        }
		if (isset($GLOBALS['log'])) {
			$GLOBALS['log']->Log(JAWS_LOG_INFO, "Jaws_User->AddUserToGroupName() result: Error: ".var_export($groupInfo, true));
		}
		return false;
    }

    /**
     * Deletes an user from a group
     *
     * @access  public
     * @param   int     $user   User's ID
     * @param   int     $group  Group's ID
     * @return  boolean Returns true if user was sucessfully deleted from a group, false if not
     */
    function DeleteUserFromGroup($user, $group)
    {
        $params = array();
        $params['user']  = $user;
        $params['group'] = $group;

		// Let everyone know
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onDeleteUserFromGroup', array('user_id' => $user, 'group_id' => $group));
		if (Jaws_Error::IsError($res) || !$res) {
			return false;
		}
        
		$sql = '
            DELETE FROM [[users_groups]]
            WHERE
                [user_id] = {user}
              AND
                [group_id] = {group}';

        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
            return false;
        }

		// TODO: Remove users_gadgets records for this user and group
        return true;
    }

    /**
     * Checks if a user is in a group
     *
     * @access  public
     * @param   int     $user   User's ID
     * @param   int     $group  Group's ID
     * @return  boolean Returns true if user in in the group or false if not
     */
    function UserIsInGroup($user, $group)
    {
        $params = array();
        $params['user']  = $user;
        $params['group'] = $group;

        $sql = '
            SELECT COUNT([user_id])
            FROM [[users_groups]]
            WHERE
                [user_id] = {user}
              AND
                [group_id] = {group}';

        $howmany = $GLOBALS['db']->queryOne($sql, $params);
        if (Jaws_Error::IsError($howmany)) {
            return false;
        }

        return ($howmany == '0') ? false : true;
    }

    /**
     * Gets status of user in group
     *
     * @access  public
     * @param   int     $user   User's ID
     * @param   int     $group  Group's ID
     * @return  boolean Returns true if user in in the group or false if not
     */
    function GetStatusOfUserInGroup($user, $group)
    {
        $params = array();
        
		if (is_numeric($user)) {
			$params['user']  = $user;
		} else {
			$info = $this->GetUserInfoByName($name);
			if (!Jaws_Error::isError($info) && isset($info['id']) && !empty($info['id'])) {
				$params['user']  = $info['id'];
			}
		}		
		if (is_numeric($group)) {
			$params['group'] = $group;
		} else {
			$info = $this->GetGroupInfoByName($group);
			if (!Jaws_Error::isError($info) && isset($info['id']) && !empty($info['id'])) {
				$params['group']  = $info['id'];
			} else {
				return false;
			}
		}		

        $sql = '
            SELECT [status]
            FROM [[users_groups]]
            WHERE
                [user_id] = {user}
              AND
                [group_id] = {group}';

        $status = $GLOBALS['db']->queryOne($sql, $params);
		if (Jaws_Error::IsError($status)) {
            return false;
        }

        return $status;
    }

    /**
     * Gets founder of group
     *
     * @access  public
     * @param   int     $user   User's ID
     * @param   int     $group  Group's ID
     * @return  boolean Returns true if user in in the group or false if not
     */
    function GetUsersOfGroupByStatus($group, $status = 'active', $limit = 0, $offset = null, $random_seed = '')
    {
        $params = array();
        $params['group'] = (int)$group;

        $sql = '
            SELECT [user_id], [group_id], [status], [updated]
            FROM [[users_groups]]
			WHERE (';
        
		if (is_array($status)) {
			$i = 0;
			foreach ($status as $s) {
				$params['status'.$i] = $s;
				$sql .=	($i > 0 ? ' OR ' : '').' [status] = {status'.$i.'}';
				$i++;
			}
		} else {
			$params['status'] = $status;
			$sql .=	'[status] = {status}';
		}
		
		$sql .= ') AND
                [group_id] = {group}';

		if (trim($random_seed) != '') {
			$sql .= " ORDER BY rand(".(int)$random_seed.")";
        }

		if (!empty($limit)) {
            $result = $GLOBALS['db']->setLimit($limit, $offset);
            if (Jaws_Error::IsError($result)) {
                return false;
            }
        }
		
        $result = $GLOBALS['db']->queryAll($sql, $params);
        if (Jaws_Error::IsError($result)) {
            return false;
        }

        return $result;
    }
        
    /**
     * Gets total of group members by status
     *
     * @access  public
     * @param   int     $user   User's ID
     * @param   int     $group  Group's ID
     * @return  boolean Returns true if user in in the group or false if not
     */
    function GetTotalOfUsersOfGroupByStatus($group, $status = 'active')
    {
        $params = array();
        $params['group'] = (int)$group;

        $sql = '
            SELECT COUNT([user_id])
            FROM [[users_groups]]
			WHERE (';
        
		if (is_array($status)) {
			$i = 0;
			foreach ($status as $s) {
				$params['status'.$i] = $s;
				$sql .=	($i > 0 ? ' OR ' : '').' [status] = {status'.$i.'}';
				$i++;
			}
		} else {
			$params['status'] = $status;
			$sql .=	'[status] = {status}';
		}
		
		$sql .= ') AND
                [group_id] = {group}';
		
		$res = $GLOBALS['db']->queryOne($sql, $params);
		$total = (Jaws_Error::IsError($res) ? 0 : (int)$res);
        			
		return $total;
    }
        
	/**
     * Gets users within radius of given longitude and latitude
     *
     * @access  public
     * @param   float     $long	longitude
     * @param   float     $lat	latitude
     * @param   int     $radius	radius (miles)
     * @param   int     $limit	limit number of cities to search through
     * @return  boolean Returns true if user in in the group or false if not
     */
    function GetUsersWithinRadius($long, $lat, $region, $radius = 150, $limit = 100, $enabled = null)
    {
        // TODO: Add longitude/latitude to users DB table
        // TODO: When updating/adding user address, cross-reference country DB
		$results = array();
		$mapsAdminModel = $GLOBALS['app']->LoadGadget('Maps', 'AdminModel');
		$regions = $mapsAdminModel->SearchRegionsWithinRadius($long, $lat, $radius, 300, $limit);
		if (!Jaws_Error::IsError($regions)) {
			$GLOBALS['db']->dbc->loadModule('Function', null, true);
			$state = $GLOBALS['db']->dbc->function->lower('[region]');
			$city = $GLOBALS['db']->dbc->function->lower('[city]');
			foreach ($regions as $reg) {
				if (!empty($reg['region'])) {
					$params = array();
					switch(strtoupper($region)) {
						case "AL": 
							$region = 'alabama';
							break;
						case "AK": 
							$region = 'alaska';
							break;
						case "AZ": 
							$region = 'arizona';
							break;
						case "AR": 
							$region = 'arkansas';
							break;
						case "CA": 
							$region = 'california';
							break;
						case "CO": 
							$region = 'colorado';
							break;
						case "CT": 
							$region = 'connecticut';
							break;
						case "DE": 
							$region = 'delaware';
							break;
						case "FL": 
							$region = 'florida';
							break;
						case "GA": 
							$region = 'georgia';
							break;
						case "HI": 
							$region = 'hawaii';
							break;
						case "ID": 
							$region = 'idaho';
							break;
						case "IL": 
							$region = 'illinois';
							break;
						case "IN": 
							$region = 'indiana';
							break;
						case "IA": 
							$region = 'iowa';
							break;
						case "KS": 
							$region = 'kansas';
							break;
						case "KY": 
							$region = 'kentucky';
							break;
						case "LA": 
							$region = 'louisiana';
							break;
						case "ME": 
							$region = 'maine';
							break;
						case "MD": 
							$region = 'maryland';
							break;
						case "MA": 
							$region = 'massachusetts';
							break;
						case "MI": 
							$region = 'michigan';
							break;
						case "MN": 
							$region = 'minnesota';
							break;
						case "MS": 
							$region = 'mississippi';
							break;
						case "MO": 
							$region = 'missouri';
							break;
						case "MT": 
							$region = 'montana';
							break;
						case "NE": 
							$region = 'nebraska';
							break;
						case "NV": 
							$region = 'nevada';
							break;
						case "NH": 
							$region = 'new hampshire';
							break;
						case "NJ": 
							$region = 'new jersey';
							break;
						case "NM": 
							$region = 'new mexico';
							break;
						case "NY": 
							$region = 'new york';
							break;
						case "NC": 
							$region = 'north carolina';
							break;
						case "ND": 
							$region = 'north dakota';
							break;
						case "OH": 
							$region = 'ohio';
							break;
						case "OK": 
							$region = 'oklahoma';
							break;
						case "OR": 
							$region = 'oregon';
							break;
						case "PA": 
							$region = 'pennsylvania';
							break;
						case "RI": 
							$region = 'rhode island';
							break;
						case "SC": 
							$region = 'south carolina';
							break;
						case "SD": 
							$region = 'south dakota';
							break;
						case "TN": 
							$region = 'tennessee';
							break;
						case "TX": 
							$region = 'texas';
							break;
						case "UT": 
							$region = 'utah';
							break;
						case "VT": 
							$region = 'vermont';
							break;
						case "VA": 
							$region = 'virginia';
							break;
						case "WA": 
							$region = 'washington';
							break;
						case "DC": 
							$region = 'washington d.c.';
							break;
						case "WV": 
							$region = 'west virginia';
							break;
						case "WI": 
							$region = 'wisconsin';
							break;
						case "WY": 
							$region = 'wyoming';
							break;
					}
					$params['region']	= strtolower($region);
					$params['city']		= strtolower(substr($reg['region'], 0, (strpos($reg['region'], '(')-1)));
					$sql = "
						SELECT
							[id]
						FROM [[users]]
						WHERE (($city = {city} AND ([region] = '' OR [region] IS NULL)) OR ($city = {city} AND $state = {region}))";

					if (!is_null($enabled)) {
						$params['enabled']	= (bool)$enabled;
						$sql .= ' AND [enabled] = {enabled}';
					}

					$res = $GLOBALS['db']->queryAll($sql, $params);
					if (Jaws_Error::IsError($res)) {
						continue;
					} else {
						foreach ($res as $user) {
							$results[] = $user;
						}
					}
				}
			}
		}
		return $results;
    }

    /**
     * Get the info of an user by the identifier
     *
     * @access  public
     * @param   string     $identifier  The identifier
     * @return  mixed   Returns an array with the info of the user and false on error
     */
    function GetUserInfoByIdentifier($identifier, $account = true, $personal = false, $preferences = false, $extra = false)
    {
        $params = array();
        $params['identifier'] = $identifier;

        $sql = 'SELECT [id]';
        $types = array('integer');

        if ($account) {
            $sql .= ', [username], [nickname], [email], [user_type], [enabled]';
            $types = array_merge($types, array('text', 'text', 'text', 'integer', 'boolean'));
        }
        if ($personal) {
            $sql .= ', [fname], [lname], [gender], [dob], [url], [company],
				[address], [address2], [city], [region], [postal], [country], 
				[description], [office], [tollfree], [phone], [fax], [merchant_id], 
				[keywords], [image], [logo], [notification], [company_type]';
            $types = array_merge($types, array(
				'text', 'text', 'integer', 'timestamp', 'text', 'text',
				'text', 'text', 'text', 'text', 'text', 'text', 
				'text', 'text', 'text', 'text', 'text', 'text',
				'text', 'text', 'text', 'text', 'text'
			));
        }
        if ($preferences) {
            $sql .= ', [language], [theme], [editor], [timezone], [allow_comments], [identifier]';
            $types = array_merge($types, array('text', 'text', 'text', 'text', 'boolean', 'text'));
        }
        if ($extra) {
            $sql .= ', [passwd], [last_login], [createtime], [updatetime], [closetime], [checksum], [new_messages]';
            $types = array_merge($types, array('text', 'timestamp', 'timestamp', 'timestamp', 'timestamp', 'text', 'text'));
        }


        $sql .= "
            FROM [[users]]
            WHERE [identifier] = {identifier}";

        return $GLOBALS['db']->queryRow($sql, $params);
    }

    /**
     * Returns the user info by checksum
     *
     * @access  public
     * @param   string  $key  Secret key
     * @return  boolean Success/Failure
     */
    function GetUserByChecksum($key)
    {
        $key = trim($key);
        if (empty($key)) {
            return false;
        }

        $params        = array();
        $params['key'] = $key;

        $sql = '
            SELECT
                [id], [username], [email], [url], [nickname], [fname], [lname],
                [user_type], [language], [theme], [editor], [timezone], [enabled],
				[closetime], [company], [url], [address], [address2], [city], [region], [postal], 
				[country], [description], [office], [tollfree], [phone], [fax], 
				[merchant_id], [keywords], [image], [logo], [notification], [allow_comments], 
				[identifier], [checksum], [passwd], [company_type], [new_messages]
            FROM [[users]]
            WHERE [checksum] = {key}';

        $types = array('integer', 'text', 'text', 'text', 'timestamp', 'timestamp', 'timestamp', 'integer', 
                       'text', 'text', 'text', 'text', 'boolean', 'timestamp', 'text', 'text', 'text', 'text',
					   'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text', 
					   'boolean', 'text', 'text', 'text', 'text', 'text');
        
		$row = $GLOBALS['db']->queryRow($sql, $params, $types);
        if (Jaws_Error::IsError($row) || !isset($row['id'])) {
            return false;
        }

        return $row;
    }
    
	/**
     * Returns the group info by checksum
     *
     * @access  public
     * @param   string  $key  Secret key
     * @return  boolean Success/Failure
     */
    function GetGroupByChecksum($key)
    {
        $key = trim($key);
        if (empty($key)) {
            return false;
        }

        $params        = array();
        $params['key'] = $key;

        $sql = '
            SELECT
                [name], [title], [description], [removable], [checksum]
            FROM [[groups]]
            WHERE [checksum] = {key}';

        $types = array('integer', 'text', 'text', 'text');
        
		$row = $GLOBALS['db']->queryRow($sql, $params, $types);
        if (Jaws_Error::IsError($row) || !isset($row['id'])) {
            return false;
        }

        return $row;
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
		
		$params             	= array();
		$params['user_id'] 		= $id;

		$sql = '
			DELETE FROM [[users_gadgets]]
				WHERE [user_id] = {user_id}';

		$result = $GLOBALS['db']->query($sql, $params);
		if (Jaws_Error::IsError($result)) {
			return false;
		}
        return true;
    }

	/**
     * Updates Users-Gadgets association from db when a user is updated
     *
     * @access  public
     * @param   int  $id user id
     * @param   string  $gadget name of gadget to update
     * @param   string  $pane name of specific pane to update
     * @param   string  $pane_action 'minimize' or 'maximize' the pane
     * @return  boolean Returns sort_order of last pane if users_gadget was successfully updated, error if not
     */
    function UpdateUsersGadgets($id, $gadget = null, $pane_action = null, $sort_order = null, $insert = false, $enabled = true)
    {
		$info = $this->GetUserInfoById($id, true);
		if (!Jaws_Error::IsError($info) && (isset($info['id']) && !empty($info['id']))) {
			if ($info['user_type'] < 2) {
				$gadget_acls = array(
					'ControlPanel/default',
					'ControlPanel/DatabaseBackups',
					'Settings/ManageSettings',
					'Layout/ManageLayout',
					'Layout/ManageThemes',
					'Users/default',
					'Users/EditAccountInformation',
					'Users/EditAccountPassword',
					'Users/EditAccountPreferences',
					'Users/EditAccountProfile',
					'Users/ManageUsers',
					'Users/ManageGroups',
					'Users/ManageACL',
					'Jms/ManagePlugins',
					'Policy/ManagePolicy',
					'Policy/IPBlocking',
					'Policy/ManageIPs',
					'Policy/AgentBlocking',
					'Policy/ManageAgents',
					'Policy/Encryption',
					'Policy/AntiSpam',
					'Policy/AdvancedPolicies',
					'Menu/default',
					'Menu/ManageMenus',
					'Menu/ManageGroups'
				);	
			} else {
				$gadget_acls = array(
					'Users/EditAccountInformation',
					'Users/EditAccountPassword',
					'Users/EditAccountPreferences',
					'Users/EditAccountProfile'
				);
			}
			$gadget_list = array();
			if ($gadget != null) {
				$gadget_list[] = array('realname' => $gadget);
			} else {
				// Update users_gadgets table
				$jms = $GLOBALS['app']->LoadGadget('Jms', 'AdminModel');
				// Add ALL GADGETS to users_gadgets
				$gadget_list = $jms->GetGadgetsList(null, true, true, null);
			}
			//Hold.. if we dont have a selected gadget?.. like no gadgets?
			if (!count($gadget_list) <= 0) {
				reset($gadget_list);
				
				$groups = $this->GetGroupsOfUser($info['id']);
			
				foreach ($gadget_list as $g) {	
					if ($pane_action != null && !is_null($gadget)) {
						$paneGadget = $GLOBALS['app']->LoadGadget($g['realname'], 'HTML');
						if (
							method_exists($paneGadget, 'GetUserAccountPanesInfo') && 
							method_exists($paneGadget, 'GetUserAccountControls')
						) {
							if (!Jaws_Error::isError($groups) && !count($groups) <= 0) {
								reset($groups);
								$gadget_panes = $paneGadget->GetUserAccountPanesInfo($groups);
								foreach ($gadget_panes as $pane_method => $pane_name) {
									// Select existing users_gadgets table
									$pane_found = false;
									if ($insert === true) {
										$userModel = $GLOBALS['app']->LoadGadget('Users', 'Model');
										$pane_info = $userModel->GetGadgetPaneInfoByUserID($g['realname'], $info['id']);
										if (Jaws_Error::IsError($pane_info)) {
											return $pane_info;
										} else {
											if (isset($pane_info['gadget_name'])) {
												$pane_found = true;
												//echo '<br />found:::'.$g['realname'];
											} else {
												//echo '<br />not found:::'.$g['realname'];
											}
										}
									}
									
									$params             	= array();
									$params['user_id'] 		= $info['id'];
									$params['enabled'] 		= $enabled;
									$params['status']   	= $pane_action;
									$params['gadget_name']  = $g['realname'];
									$params['now']      	= $GLOBALS['db']->Date();
									if ($sort_order != null) {
										$params['sort_order']   = (int)$sort_order;
									} else {
										$sort_order = 0;
										$params['sort_order']   = $sort_order;
									}

									if ($insert === true && $pane_found === false) {
										$params['pane']  = $pane_method;
										$sql = '
											INSERT INTO [[users_gadgets]]
												([user_id], [gadget_name], [pane], 
												[enabled], [status], [sort_order], 
												[created], [updated], [owner_id])
											VALUES
												({user_id}, {gadget_name}, {pane}, 
												{enabled}, {status}, {sort_order}, 
												{now}, {now}, 0)';
									} else {
										$sql = '
											UPDATE [[users_gadgets]] SET
												[enabled]     = {enabled},
												[updated]  = {now},
												[status]  = {status},
												[sort_order] = {sort_order} 
											WHERE ([user_id] = {user_id} AND [gadget_name] = {gadget_name})';
									}

									$result = $GLOBALS['db']->query($sql, $params);
									if (Jaws_Error::IsError($result)) {
										if ($insert === true) {
											return $result;
										} else {
											return false;
										}
									}
									$sort_order++;
								}
							}
						/*
						} else {
							if (isset($GLOBALS['log'])) {
								$GLOBALS['log']->Log(JAWS_LOG_ERR, "GetUserAccountPanesInfo and GetUserAccountControls in ".$g['realname']."'s HTML doesn't exist.");
							}
						*/
						}
						unset($paneGadget);
					}
					
					// add "Own" or non-core "Full" permissions to ACL array
					$gInfo = $GLOBALS['app']->LoadGadget($g['realname'], 'Info');
					if (!Jaws_Error::isError($gInfo)) {
						$acl_keys = $gInfo->GetACLs();
						$core = $gInfo->GetAttribute('core_gadget');
						if (!count($acl_keys) <= 0) {
							reset($acl_keys);
							foreach ($acl_keys as $acl_key => $acl_val) {
								if (((!$core || is_null($core)) && $info['user_type'] < 2) || ($info['user_type'] < 2 && ($g['realname'] == 'ControlPanel' || $g['realname'] == 'Users'))) {
									if (!in_array($g['realname'].$key_name, $gadget_acls, TRUE)) {
										array_push($gadget_acls, $g['realname'].$key_name);
									}
								} else if (strpos($key_name,"Own") !== false) {
									if (!in_array($g['realname'].$key_name, $gadget_acls, TRUE)) {
										array_push($gadget_acls, $g['realname'].$key_name);
									}
								}
							}
						}
					} else {
						if (isset($GLOBALS['log'])) {
							$GLOBALS['log']->Log(JAWS_LOG_ERR, $gInfo->GetMessage());
						}
					}
					unset($gInfo);
				}

				// grant gadget permissions based on ACL array
				$userAdminModel = $GLOBALS['app']->LoadGadget('Users', 'AdminModel');
				$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');
				if (!count($gadget_acls) <= 0) {
					reset($gadget_acls);
					foreach ($gadget_acls as $gadget_acl) {
						$currentValue = $GLOBALS['app']->ACL->Get('/ACL/users/' . $info['username'] . '/gadgets/'.$gadget_acl);
						if ($currentValue === null) {
							$g_acl = $userAdminModel->UpdateUserACL($info['id'], array('/ACL/users/' . $info['username'] . '/gadgets/'.$gadget_acl => true), false);
							/*
							foreach ($groups as $group) {
								$group_acl = $userAdminModel->UpdateGroupACL($group['id'], array('/ACL/groups/'.$group['id'].'/gadgets/'.$gadget_acl => true));
							}
							*/
							if (Jaws_Error::IsError($g_acl)) {
								if (isset($GLOBALS['log'])) {
									$GLOBALS['log']->Log(JAWS_LOG_ERR, $g_acl->GetMessage());
								}
							}
						}
					}
				}
			}
		}
        return $sort_order;
    }
		
    /**
     * Removes Users-Gadgets subscriptions
     *
     * @access  public
     * @param   string  $gadget name of gadget
     * @param   int  $id subscription id
     * @return  boolean Returns true if subscription was successfully deleted, false if not
     */
    function DeleteSubscription($gadget, $id)
    {
		if (!$gadget || !$id) {
			return false;
		}
		$params             	= array();
		$params['id'] 		= $id;

		$table = strtolower($gadget).'_subscriptions';
		$sql = "
			DELETE FROM [[$table]]
				WHERE [id] = {id}";

		$result = $GLOBALS['db']->query($sql, $params);
		if (Jaws_Error::IsError($result)) {
			return false;
		}
		
        return true;
    }

    /**
     * Adds user to a friend
     *
     * @access  public
     * @param   int     $user   User's ID
     * @param   int     $friend_id  Friend's ID
     * @return  boolean Returns true if user was sucessfully added to the friend, false if not
     */
    function AddUserToFriend($user, $friend_id, $status = 'request')
    {
        $userInfo = $this->GetUserInfoById($user);
        if (Jaws_Error::IsError($userInfo)) {
			$GLOBALS['app']->Session->PushSimpleResponse($userInfo->GetMessage());
            return false;
        }
        $friendInfo = $this->GetUserInfoById($friend_id);
        if (Jaws_Error::IsError($friendInfo)) {
			$GLOBALS['app']->Session->PushSimpleResponse($friendInfo->GetMessage());
            return false;
        }
		$params = array();
        $params['user']  = $user;
        $params['friend_id'] = $friend_id;
        $params['status'] = $status;
        $params['now'] = $GLOBALS['db']->Date();

        if (
			(substr(strtolower($status), 0, 6) != 'group_' && $this->UserIsFriend($user, $friend_id)) || 
			(substr(strtolower($status), 0, 6) == 'group_' && $this->UserIsGroupFriend($user, $friend_id))
		) {
			$sql = '
				UPDATE [[users_friends]] SET
					[status] = {status}, 
					[updated] = {now}
				WHERE ([user_id] = {user} AND [friend_id] = {friend_id})';
		} else {
			$sql = '
				INSERT INTO [[users_friends]]
					([user_id], [friend_id], [status], [created], [updated])
				VALUES
					({user}, {friend_id}, {status}, {now}, {now})';
		}
		
        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
			$GLOBALS['app']->Session->PushSimpleResponse($result->GetMessage());
            return false;
        }
		
		$request =& Jaws_Request::getInstance();
		$action = $request->get('action', 'get');
		if (empty($action)) {
			$action = $request->get('action', 'post');
		}
		if ($action != 'RequestedFriendGroup') {
			// Let everyone know
			$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
			$res = $GLOBALS['app']->Shouter->Shout('onAddUserToFriend', array('user_id' => $user, 'friend_id' => $friend_id, 'status' => $status));
			if (Jaws_Error::IsError($res) || !$res) {
				return false;
			}
		}
		return true;
	}

    /**
     * Deletes user from a friend
     *
     * @access  public
     * @param   int     $user   User's ID
     * @param   int     $friend_id  Friend's ID
     * @return  boolean Returns true if user was sucessfully deleted from a friend, false if not
     */
    function DeleteUserFromFriend($user, $friend_id)
    {
        $params = array();
        $params['user']  = $user;
        $params['friend_id'] = $friend_id;
        $params['status'] = 'group_%';

		// Let everyone know
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onDeleteUserFromFriend', array('user_id' => $user, 'friend_id' => $friend_id));
		if (Jaws_Error::IsError($res) || !$res) {
			return false;
		}
        
		$sql = '
            DELETE FROM [[users_friends]]
            WHERE
                (([user_id] = {user} AND [friend_id] = {friend_id}) 
			  OR 
				([user_id] = {friend_id} AND [friend_id] = {user}))
			  AND NOT 
				([status] LIKE {status})';

        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
            return false;
        }

        return true;
    }
		
    /**
     * Deletes user from a group friend
     *
     * @access  public
     * @param   int     $user   User's ID
     * @param   int     $group  group's ID
     * @return  boolean Returns true if user was sucessfully deleted from a friend, false if not
     */
    function DeleteUserFromGroupFriend($user, $group)
    {
        $params = array();
        $params['user']  = $user;
        $params['group'] = $group;
        $params['status'] = 'group_%';

		// Let everyone know
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onDefriendGroup', array('user_id' => $user, 'group' => $friend_id));
		if (Jaws_Error::IsError($res) || !$res) {
			return false;
		}
        
		$sql = '
            DELETE FROM [[users_friends]]
            WHERE
                (([user_id] = {user} AND [friend_id] = {friend_id}) 
			  OR 
				([user_id] = {friend_id} AND [friend_id] = {user}))
			  AND 
				([status] LIKE {status})';

        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
            return false;
        }

        return true;
    }
		
    /**
     * Get a list of friends of user
     *
     * @access  public
     * @param   string  $username  Username
     * @param   string  $sortColoumn 	Sort column
     * @param   string  $sortDir 	Sort direction (ASC/DESC)
     * @param   int  $limit 	Data limit
     * @param   boolean  $blocked 	If true show blocked
     * @return  array   Returns an array of the available friends and false on error
     */
    function GetFriendsOfUsername($username, $sortColumn = 'created', $sortDir = 'ASC', $limit = null, $offset = null, $blocked = false)
    {
        $info = $this->GetUserInfoByName($username);
        if (Jaws_Error::IsError($info) || !isset($info['id'])|| empty($info['id'])) {
            return array();
        }

        return $this->GetFriendsOfUser($info['id'], $sortColumn, $sortDir, $limit, $offset, $blocked);
    }

    /**
     * Get a list of friends of a user by userid
     *
     * @access  public
     * @param   int  $user_id 	User ID
     * @param   string  $sortColoumn 	Sort column
     * @param   string  $sortDir 	Sort direction (ASC/DESC)
     * @param   int  $limit 	Data limit
     * @param   boolean  $blocked 	If true show blocked
     * @return  array   Returns an array of the available friends and false on error
     */
    function GetFriendsOfUser($user_id, $sortColumn = 'created', $sortDir = 'ASC', $limit = null, $offset = null, $blocked = false)
    {
        $fields = array('created', 'updated', 'status');
        $sortColumn = strtolower($sortColumn);
        if (!in_array($sortColumn, $fields)) {
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_WARNING, _t('GLOBAL_ERROR_UNKNOWN_COLUMN'));
            }
            $sortColumn = 'created';
        }

        $sortDir = strtoupper($sortDir);
		
		$params = array();
        $params['user_id'] = $user_id;
		$params['status'] 	= 'group_%';

		$sql = '
			SELECT [[users]].[id] AS friend_id, [[users]].[nickname] AS friend_name, [[users]].[company] AS friend_company,
                [[users_friends]].[status] AS friend_status, [[users_friends]].[created] AS friend_created, 
				[[users_friends]].[updated] AS friend_updated
            FROM [[users_friends]]
            INNER JOIN [[users]] ON ([[users]].[id] = [[users_friends]].[user_id]) OR ([[users]].[id] = [[users_friends]].[friend_id])
			WHERE
			    [[users]].[id] != {user_id}
			';
        if ($blocked === false) {
			$params['blocked'] 	= 'blocked';
			$sql .= ' AND [[users_friends]].[status] != {blocked}';
        }
		$sql .= ' AND 
				([[users_friends]].[user_id] = {user_id} OR [[users_friends]].[friend_id] = {user_id})
			  AND NOT 
				([[users_friends]].[status] LIKE {status})
		';
				
		$sql .= " ORDER BY [[users_friends]].[$sortColumn] $sortDir";

        if (!empty($limit)) {
            $result = $GLOBALS['db']->setLimit($limit, $offset);
            if (Jaws_Error::IsError($res)) {
                return false;
            }
        }
		
        $result = $GLOBALS['db']->queryAll($sql, $params);
        if (Jaws_Error::IsError($result)) {
            return false;
        }

        return $result;
    }
    
    /**
     * Get a list of group friends of a user
     *
     * @access  public
     * @param   string  $username  Username
     * @param   string  $sortColoumn 	Sort column
     * @param   string  $sortDir 	Sort direction (ASC/DESC)
     * @param   int  $limit 	Data limit
     * @param   boolean  $blocked 	If true show blocked
     * @return  array   Returns an array of the available friends and false on error
     */
    function GetGroupFriendsOfUsername($username, $sortColumn = 'created', $sortDir = 'ASC', $limit = null, $blocked = false)
    {
        $info = $this->GetUserInfoByName($username);
        if (Jaws_Error::IsError($info) || !isset($info['id'])|| empty($info['id'])) {
            return array();
        }

        return $this->GetGroupFriendsOfUser($info['id'], $sortColumn, $sortDir, $limit, $blocked);
    }

    /**
     * Get a list of groups a user is following by userid
     *
     * @access  public
     * @param   int  $user_id 	User ID
     * @param   string  $sortColoumn 	Sort column
     * @param   string  $sortDir 	Sort direction (ASC/DESC)
     * @param   int  $limit 	Data limit
     * @param   boolean  $blocked 	If true show blocked
     * @return  array   Returns an array of the available friends and false on error
     */
    function GetGroupFriendsOfUser($user_id, $sortColumn = 'created', $sortDir = 'ASC', $limit = null, $blocked = false)
    {
        $fields = array('created', 'updated', 'status');
        $sortColumn = strtolower($sortColumn);
        if (!in_array($sortColumn, $fields)) {
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_WARNING, _t('GLOBAL_ERROR_UNKNOWN_COLUMN'));
            }
            $sortColumn = 'created';
        }

        $sortDir = strtoupper($sortDir);
		
		$params = array();
        $params['user_id'] = $user_id;
        $params['status'] 	= 'group_%';

		$sql = '
			SELECT [[groups]].[id] AS friend_id, [[groups]].[name] AS friend_name,
                [[users_friends]].[status] AS friend_status, [[users_friends]].[created] AS friend_created, 
				[[users_friends]].[updated] AS friend_updated
            FROM [[users_friends]]
            INNER JOIN [[groups]] ON ([[groups]].[id] = [[users_friends]].[friend_id])
			WHERE
			    [[groups]].[id] != {user_id}
			';
        if ($blocked === false) {
			$params['blocked'] 	= 'group_blocked';
			$sql .= ' AND [[users_friends]].[status] != {blocked}';
        }
		$sql .= ' AND 
				([[users_friends]].[user_id] = {user_id} OR [[users_friends]].[friend_id] = {user_id})
			  AND 
				([[users_friends]].[status] LIKE {status})
		';
				
		$sql .= " ORDER BY [[users_friends]].[$sortColumn] $sortDir";

        if (!is_null($limit)) {
			$res = $GLOBALS['db']->setLimit($limit);
			if (Jaws_Error::IsError($res)) {
				return false;
			}
		}
		
        $result = $GLOBALS['db']->queryAll($sql, $params);
        if (Jaws_Error::IsError($result)) {
            return false;
        }

        return $result;
    }
    
    /**
     * Checks if a user is a friend
     *
     * @access  public
     * @param   int     $user   User's ID
     * @param   int     $friend_id  Friend's ID
     * @return  boolean Returns true if user in in the group or false if not
     */
    function UserIsFriend($user, $friend_id)
    {
        $params = array();
        $params['user']  = $user;
        $params['friend_id'] = $friend_id;
        $params['status'] = 'group_%';

        $sql = '
            SELECT COUNT([user_id])
            FROM [[users_friends]]
            WHERE
                (([user_id] = {user} AND [friend_id] = {friend_id})
			  OR 
                ([friend_id] = {user} AND [user_id] = {friend_id}))
              AND NOT
			    ([status] LIKE {status})';

        $howmany = $GLOBALS['db']->queryOne($sql, $params);
        if (Jaws_Error::IsError($howmany)) {
            return false;
        }

        return ($howmany == '0') ? false : true;
    }

    /**
     * Checks if a user is a friend
     *
     * @access  public
     * @param   int     $user   User's ID
     * @param   int     $group  group's ID
     * @return  boolean Returns true if user in the group or false if not
     */
    function UserIsGroupFriend($user, $group)
    {
        $params = array();
        $params['user']  = $user;
        $params['group'] = $group;
        $params['status'] = 'group_%';

        $sql = '
            SELECT COUNT([user_id])
            FROM [[users_friends]]
            WHERE
                (([user_id] = {user} AND [friend_id] = {group})
			  OR 
                ([friend_id] = {user} AND [user_id] = {group}))
			  AND 
			    ([status] LIKE {status})
			';

        $howmany = $GLOBALS['db']->queryOne($sql, $params);
        if (Jaws_Error::IsError($howmany)) {
            return false;
        }

        return ($howmany == '0') ? false : true;
    }

    /**
     * Gets status of user in friend
     *
     * @access  public
     * @param   int     $user   User's ID
     * @param   int     $friend  friend's ID
     * @return  boolean Returns true if user in in the friend or false if not
     */
    function GetStatusOfUserInFriend($user, $friend)
    {
		$params = array();
        $params['user']  = $user;
        $params['friend'] = $friend;
        $params['status'] = 'group_%';

        $sql = '
            SELECT [status]
            FROM [[users_friends]]
            WHERE
                (([user_id] = {user} AND [friend_id] = {friend}) 
			  OR 
			    ([friend_id] = {user} AND [user_id] = {friend})) 
			  AND NOT 
			    ([status] LIKE {status})';

        $status = $GLOBALS['db']->queryOne($sql, $params);
        if (Jaws_Error::IsError($status)) {
            return false;
        }
		
        return $status;
    }

    /**
     * Gets status of user in group friend
     *
     * @access  public
     * @param   int     $user   User's ID
     * @param   int     $group  group's ID
     * @return  boolean Returns true if user is group friend or false if not
     */
    function GetStatusOfUserInGroupFriend($user, $group)
    {
		$params = array();
        $params['user']  = $user;
        $params['group'] = $group;
        $params['status'] = 'group_%';

        $sql = '
            SELECT [status]
            FROM [[users_friends]]
            WHERE
                (([user_id] = {user} AND [friend_id] = {group}) 
			  OR 
			    ([friend_id] = {user} AND [user_id] = {group})) 
			  AND 
			    ([status] LIKE {status})';

        $status = $GLOBALS['db']->queryOne($sql, $params);
        if (Jaws_Error::IsError($status)) {
            return false;
        }
		
        return $status;
    }

    /**
     * Gets founder of friend
     *
     * @access  public
     * @param   int     $user   User's ID
     * @param   int     $friend  friend's ID
     * @return  boolean Returns true if user in in the friend or false if not
     */
    function GetUsersOfFriendByStatus($friend, $status = 'active', $sortColumn = 'created', $sortDir = 'ASC')
    {
        $fields = array('created', 'updated', 'status');
        $sortColumn = strtolower($sortColumn);
        if (!in_array($sortColumn, $fields)) {
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_WARNING, _t('GLOBAL_ERROR_UNKNOWN_COLUMN'));
            }
            $sortColumn = 'created';
        }

        $sortDir = strtoupper($sortDir);
		
		$params = array();
        $params['friend'] = $friend;
        $params['status'] = $status;

		$sql = '
			SELECT [[users]].[id] AS friend_id, [[users]].[nickname] AS friend_name,
                [[users_friends]].[status] AS friend_status, [[users_friends]].[created] AS friend_created, 
				[[users_friends]].[updated] AS friend_updated
            FROM [[users_friends]]
            INNER JOIN [[users]] ON ([[users]].[id] = [[users_friends]].[user_id]) OR ([[users]].[id] = [[users_friends]].[friend_id])
			WHERE
                [[users]].[id] != {friend} AND [[users_friends]].[status] = {status} AND 
				([[users_friends]].[user_id] = {friend} OR [[users_friends]].[friend_id] = {friend})
		';
				
		$sql .= " ORDER BY [[users_friends]].[$sortColumn] $sortDir";

        $result = $GLOBALS['db']->queryAll($sql, $params);
        if (Jaws_Error::IsError($result)) {
            return false;
        }

        return $result;
    }
	
	/**
     * Removes users_friends association from db when a user is deleted
     *
     * @access  public
     * @param   string  $id user id
     * @return  boolean Returns true if pair was successfully unassociated, error if not
     */
    function RemoveUsersFriends($id)
    {
		$friends = $this->GetFriendsOfUser($id);
		if (Jaws_Error::IsError($friends)) {
			return false;
		}
		foreach ($friends as $friend) {
			$result = $this->DeleteUserFromFriend($id, $friend['friend_id']);
			if (Jaws_Error::IsError($friends)) {
				return false;
			}
		}
		
		$groups = $this->GetGroupFriendsOfUser($id);
		if (Jaws_Error::IsError($groups)) {
			return false;
		}
		foreach ($groups as $friend) {
			$result = $this->DeleteUserFromGroupFriend($id, $friend['friend_id']);
			if (Jaws_Error::IsError($friends)) {
				return false;
			}
		}
        return true;
    }
		
    /**
     * Returns the ID of a user by a certain validation key
     *
     * @access  public
     * @param   string  $key  Secret key
     * @return  boolean Success/Failure
     */
    function GetIDByValidationKey($key)
    {
        $key = trim($key);
        if (empty($key)) {
            return false;
        }

        $params        = array();
        $params['key'] = $key;

        $sql = ' SELECT [id] FROM [[users]] WHERE [validation_key] = {key}';
        $uid = $GLOBALS['db']->queryOne($sql, $params);
        if (Jaws_Error::IsError($uid) || empty($uid)) {
            return false;
        }

        return $uid;
    }

    /**
     * Update the activation key of a certain user with a given (or auto-generated)
     * secret key (MD5)
     *
     * @access  public
     * @param   int     $uid  User's ID
     * @param   string  $key  (Optional) Secret key
     * @return  boolean Success/Failure
     */
    function UpdateValidationKey($uid, $key = '')
    {
        if (empty($key)) {
            $key = md5(uniqid(rand(), true)) . time() . floor(microtime()*1000);
        }

        $params = array();
        $params['key'] = $key;
        $params['id']  = (int)$uid;

        $sql = '
            UPDATE [[users]] SET
                [validation_key] = {key}
            WHERE [id] = {id}';
        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::isError($result)) {
            return $result;
        }

        return true;
    }
}
