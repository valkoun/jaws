<?php
/**
 * Users Core Gadget
 *
 * @category   GadgetModel
 * @package    Users
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2006-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class UsersModel extends Jaws_Model
{    
	/**
	 * Creates a valid(registered) n user for an anonymous user
     *
     * @access  public
     * @param   string  $username  Username
     * @param   string  $email     User's email
     * @param   string  $nickname     User's display name
     * @param   string  $password  Password
     * @param   string  $p_check   Password check (to verify)
     * @param   string  $user_type 	User's type
     * @param   string  $group     Default user group
     * @return  boolean Success/Failure
     */		
	function CreateUser($username, $user_email, $nickname, $fname, $lname, $gender, $dob, $url,
                        $password, $user_type = 2, $group = null, $address = '', $address2 = '', 
						$city = '', $country = '', $region = '', $postal = '', $checksum = '', 
						$rpx = false, $redirect_to = '')
    {
		if (isset($GLOBALS['log'])) {
			$log_args = func_get_args();
			$GLOBALS['log']->Log(JAWS_LOG_INFO, "UsersModel->CreateUser() ".implode(', ',$log_args));
		}
        if (empty($username) || empty($nickname) || empty($user_email))
        {
			if (isset($GLOBALS['log'])) {
				$GLOBALS['log']->Log(JAWS_LOG_ERR, "UsersModel->CreateUser() result: Incomplete fields: username: ".$username." nickname: ".$nickname." email: ".$user_email);
			}
            return _t('USERS_USERS_INCOMPLETE_FIELDS');
        }

        $random = false;
        if (trim($password) == '') {
            $random = true;
            include_once 'Text/Password.php';
            $password = Text_Password::create(8, 'pronounceable', 'alphanumeric');
        }

        //Check username
        if ((!preg_match('/^[a-z0-9]+$/i', $username) || strlen($username) < 3) 
			|| strtolower($username) == 'login' || strtolower($username) == 'register'
			|| strtolower($username) == 'logout' || strtolower($username) == 'profile'
			|| strtolower($username) == 'forget' || strtolower($username) == 'recover' 
			|| strtolower($username) == 'custom' || strtolower($username) == 'directory'
			|| strtolower($username) == 'friend' 
			|| substr(strtolower($username), 0, 4) == 'test' 
			|| substr(strtolower($username), 0, 4) == 'root' 
			|| substr(strtolower($username), 0, 4) == 'info' 
			|| substr(strtolower($username), 0, 5) == 'admin') {
			if (isset($GLOBALS['log'])) {
				$GLOBALS['log']->Log(JAWS_LOG_ERR, "UsersModel->CreateUser() result: Username: ".$username." not valid");
			}
            return _t('USERS_REGISTER_USERNAME_NOT_VALID');
        }


        $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $user_email = $xss->parse($user_email);

        require_once JAWS_PATH . 'libraries/pear/Validate.php';
        if (Validate::email($user_email, true) === false) {
			if (isset($GLOBALS['log'])) {
				$GLOBALS['log']->Log(JAWS_LOG_INFO, "UsersModel->CreateUser() result: ".var_export(_t('USERS_REGISTER_EMAIL_NOT_VALID'), true));
			}
            return _t('USERS_REGISTER_EMAIL_NOT_VALID');
        }

        require_once JAWS_PATH . 'include/Jaws/User.php';
        $jUser = new Jaws_User;

        // This user is valid?
		$valid = $jUser->Valid($username, $password);
		if (!Jaws_Error::isError($valid)) {
			if (isset($GLOBALS['log'])) {
				$GLOBALS['log']->Log(JAWS_LOG_INFO, "UsersModel->CreateUser() result: true");
			}
			return true;
		}
        // We already have the same $username in the DB?
        $info = $jUser->GetUserInfoByName($username);
        if (Jaws_Error::IsError($info) || isset($info['username'])) {
			if (isset($GLOBALS['log'])) {
				$GLOBALS['log']->Log(JAWS_LOG_INFO, "UsersModel->CreateUser() result: ".var_export(_t('USERS_USERS_ALREADY_EXISTS', $xss->filter($username)), true));
			}
            return _t('USERS_USERS_ALREADY_EXISTS', $xss->filter($username));
        }

        if ($GLOBALS['app']->Registry->Get('/config/anon_repetitive_email') == 'false') {
            if ($jUser->UserEmailExists($user_email)) {
				if (isset($GLOBALS['log'])) {
					$GLOBALS['log']->Log(JAWS_LOG_INFO, "UsersModel->CreateUser() result: ".var_export(_t('USERS_EMAIL_ALREADY_EXISTS', $user_email), true));
				}
                return _t('USERS_EMAIL_ALREADY_EXISTS', $user_email);
            }
        }

        $user_type = is_numeric($user_type)? $user_type : 2;
        $user_enabled = ($GLOBALS['app']->Registry->Get('/config/anon_activation') == 'auto');
        $user_id = $jUser->AddUser($username,
                                   $xss->parse($nickname),
                                   $user_email,
                                   $password,
                                   $user_type,
                                   $user_enabled,
								   $checksum);
        if (Jaws_Error::IsError($user_id) || $user_id === false) {
			if (isset($GLOBALS['log'])) {
				$GLOBALS['log']->Log(JAWS_LOG_INFO, "UsersModel->CreateUser() result: ".var_export(_t('USERS_USERS_NOT_CREATED', $xss->filter($username)), true));
			}
            return _t('USERS_USERS_NOT_CREATED', $xss->filter($username));
        }
		$userInfo = $jUser->GetUserInfoById($user_id);
        if (Jaws_Error::IsError($userInfo) || !isset($userInfo['id']) || empty($userInfo['id'])) {
			if (isset($GLOBALS['log'])) {
				$GLOBALS['log']->Log(JAWS_LOG_INFO, "UsersModel->CreateUser() result: ".var_export(_t('USERS_USERS_NOT_CREATED', $xss->filter($username)), true));
			}
            return _t('USERS_USERS_NOT_CREATED', $xss->filter($username));
		}

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
					   'postal'    		=> $postal);

        $res = $jUser->UpdatePersonalInfo($userInfo['id'], $pInfo);
        if ($res === false) {
            //do nothing
        }

        if (!is_null($group) && is_numeric($group) && strpos($group, ',') === false) {
            $jUser->AddUserToGroup($userInfo['id'], $group);
        } else if (strpos($group, ',') !== false) {
			$groups = explode(',', $group);
			foreach ($groups as $group) {
				if ((int)$group > 0) {
					$jUser->AddUserToGroup($userInfo['id'], (int)$group);
				}
			}
		}

        require_once JAWS_PATH . 'include/Jaws/Mail.php';
        $mail = new Jaws_Mail;

        $site_url     = $GLOBALS['app']->getSiteURL('/');
        $site_name    = $GLOBALS['app']->Registry->Get('/config/site_name');
        $site_author  = $GLOBALS['app']->Registry->Get('/config/site_author');
        $activation   = $GLOBALS['app']->Registry->Get('/config/anon_activation');
        $notification = $GLOBALS['app']->Registry->Get('/gadgets/Users/register_notification');
        $delete_user  = false;
        $message      = '';

		if ($random === true || $activation != 'admin') {
			$tpl = new Jaws_Template('gadgets/Users/templates/');
			$tpl->Load('UserNotification.txt');
			$tpl->SetBlock('Notification');
			$tpl->SetVariable('say_hello', _t('USERS_REGISTER_HELLO', (!empty($nickname) ? $xss->filter($nickname) : $xss->filter($username))));

			if ($rpx === false && $random === true) {
				switch ($activation) {
					case 'admin':
						$tpl->SetVariable('message', _t('USERS_REGISTER_BY_ADMIN_RANDOM_MAIL_MSG'));
						break;

					case 'user':
						$tpl->SetVariable('message', _t('USERS_REGISTER_BY_USER_RANDOM_MAIL_MSG'));
						break;

					default:
						$tpl->SetVariable('message', _t('USERS_REGISTER_RANDOM_MAIL_MSG'));
						
				}

				$tpl->SetBlock('Notification/Password');
				$tpl->SetVariable('lbl_password', _t('USERS_USERS_PASSWORD'));
				$tpl->SetVariable('password', $xss->filter($password));
				$tpl->ParseBlock('Notification/Password');
			} elseif ($activation == 'user') {
				$tpl->SetVariable('message', _t('USERS_REGISTER_ACTIVATION_MAIL_MSG'));
			} else {
				$tpl->SetVariable('message', _t('USERS_REGISTER_MAIL_MSG'));
			}

			$tpl->SetBlock('Notification/IP');
			$tpl->SetVariable('lbl_ip', _t('GLOBAL_IP'));
			$tpl->SetVariable('ip', $_SERVER['REMOTE_ADDR']);
			$tpl->ParseBlock('Notification/IP');

			$tpl->SetVariable('lbl_username', _t('USERS_USERS_USERNAME'));
			$tpl->SetVariable('username', $xss->filter($username));

			if ($activation == 'user') {
				$secretKey = md5(uniqid(rand(), true)) . time() . floor(microtime()*1000);
				$res = $jUser->UpdateValidationKey($userInfo['id'], $secretKey);
				if ($res === true) {
					$tpl->SetBlock('Notification/Activation');
					$tpl->SetVariable('lbl_activation_link', _t('USERS_ACTIVATE_ACTIVATION_LINK'));
					$tpl->SetVariable('activation_link',
									  $GLOBALS['app']->Map->GetURLFor('Users', 'ActivateUser',
																	  array('key' => $secretKey), true, 'site_url'));
					$tpl->ParseBlock('Notification/Activation');
				} else {
					$delete_user = true;
					$message = _t('GLOBAL_ERROR_QUERY_FAILED');
				}
			}

			$tpl->SetVariable('thanks',    _t('GLOBAL_THANKS'));
			$tpl->SetVariable('site-name', $site_name);
			$tpl->SetVariable('site-url',  $site_url);

			$tpl->ParseBlock('Notification');
			$body = $tpl->Get();

			if ((JAWS_SCRIPT != 'xmlrpc' || $rpx === true) && !$delete_user) {
				$subject = _t('USERS_REGISTER_SUBJECT', $site_name);
				$mail->SetHeaders($user_email, '', '', $subject);
				$mail->AddRecipient($user_email);
				$mail->SetBody(Jaws_Gadget::ParseText($body, 'Users'), 'html');
				$mresult = $mail->send();
				if (Jaws_Error::IsError($mresult)) {
					if ($activation == 'user') {
						$delete_user = true;
						$message = _t('USERS_REGISTER_ACTIVATION_SENDMAIL_FAILED', $xss->filter($user_email));
					} elseif ($random === true) {
						$delete_user = true;
						$message = _t('USERS_REGISTER_RANDOM_SENDMAIL_FAILED', $xss->filter($user_email));
					}
				}
			}
		}

		//Send an email to website owner
		$mail->ResetValues();
		if (!$delete_user && ($notification == 'true' || $activation == 'admin')) {
			$tpl = new Jaws_Template('gadgets/Users/templates/');
			$tpl->Load('AdminNotification.txt');
			$tpl->SetBlock('Notification');
			$tpl->SetVariable('say_hello', _t('USERS_REGISTER_HELLO', $site_author));
			$tpl->SetVariable('message', _t('USERS_REGISTER_ADMIN_MAIL_MSG'));
			$tpl->SetVariable('lbl_username', _t('USERS_USERS_USERNAME'));
			$tpl->SetVariable('username', $xss->filter($username));
			$tpl->SetVariable('lbl_nickname', _t('USERS_USERS_NICKNAME'));
			$tpl->SetVariable('nickname', $xss->filter($nickname));
			$tpl->SetVariable('lbl_email', _t('GLOBAL_EMAIL'));
			$tpl->SetVariable('email', $xss->filter($user_email));
			$tpl->SetVariable('lbl_ip', _t('GLOBAL_IP'));
			$tpl->SetVariable('ip', $_SERVER['REMOTE_ADDR']);
			if ($activation == 'admin') {
				if (!isset($secretKey)) {
					$secretKey = md5(uniqid(rand(), true)) . time() . floor(microtime()*1000);
				}
				$res = $jUser->UpdateValidationKey($userInfo['id'], $secretKey);
				if ($res === true) {
					$tpl->SetBlock('Notification/Activation');
					$tpl->SetVariable('lbl_activation_link', _t('USERS_ACTIVATE_ACTIVATION_LINK'));
					$tpl->SetVariable('activation_link', $GLOBALS['app']->Map->GetURLFor('Users', 'ActivateUser',
																array('key' => $secretKey), true, 'site_url'));
					$tpl->ParseBlock('Notification/Activation');
				}
			}
			$tpl->SetVariable('thanks', _t('GLOBAL_THANKS'));
			$tpl->SetVariable('site-name', $site_name);
			$tpl->SetVariable('site-url', $site_url);
			$tpl->ParseBlock('Notification');
			$body = $tpl->Get();

			if (JAWS_SCRIPT != 'xmlrpc' && !$delete_user) {
				$subject = _t('USERS_REGISTER_SUBJECT', $site_name);
				$mail->SetHeaders('', '', $user_email, $subject);
				$mail->SetBody(Jaws_Gadget::ParseText($body, 'Users'), 'html');
				$mresult = $mail->send();
				if (Jaws_Error::IsError($mresult) && $activation == 'admin') {
					// do nothing
					//$delete_user = true;
					//$message = _t('USERS_ACTIVATE_NOT_ACTIVATED_SENDMAIL', $xss->filter($user_email));
				}
			}
		}
		
        if ($delete_user) {
            $jUser->DeleteUser($userInfo['id']);
			if (isset($GLOBALS['log'])) {
				$GLOBALS['log']->Log(JAWS_LOG_INFO, "UsersModel->CreateUser() result: ".var_export($message, true));
			}
            return $message;
        }
		
		if (isset($GLOBALS['log'])) {
			$GLOBALS['log']->Log(JAWS_LOG_INFO, "UsersModel->CreateUser() result: true");
		}
		return true;
	}

    /**
     * Updates the profile of an user
     *
     * @access  public
     * @param   int      $uid       User's ID
     * @param   string   $username  Username
     * @param   string   $email     User's email
     * @param   string   $nickname     User's display name
     * @param   string   $password  Password
     * @return  mixed    True (Success) or Jaws_Error (failure)
     */
    function UpdateAccount($uid, $username, $email, $nickname, $password)
    {
		if (isset($GLOBALS['log'])) {
			$log_args = func_get_args();
			$GLOBALS['log']->Log(JAWS_LOG_INFO, "UsersModel->UpdateAccount() ".implode(', ',$log_args));
		}
        if (trim($nickname) == '')
        {
            return new Jaws_Error(_t('USERS_USERS_INCOMPLETE_FIELDS'), _t('USERS_NAME'));
        }

        if (trim($email) != '') {
			require_once JAWS_PATH . 'libraries/pear/Validate.php';
			if (Validate::email($email, true) === false) {
				return new Jaws_Error(_t('USERS_REGISTER_EMAIL_NOT_VALID'), _t('USERS_NAME'));
			} 
		}
		
        require_once JAWS_PATH . 'include/Jaws/User.php';
        $jUser  = new Jaws_User;
        $result = $jUser->UpdateUser($uid,
                                     $username,
                                     $nickname,
                                     $email,
                                     $password);
        return $result;
    }

    /**
     * Updates the profile of an user
     *
     * @access  public
     * @param   int      $uid       User's ID
     * @param   string   $username  Username
     * @param   string   $nickname     User's display name
     * @param   string   $fname     First name
     * @param   string   $lname     Last name
     * @param   string   $email     User's email
     * @param   string   $url       User's url
     * @param   string   $password  Password
     * @param   boolean  $uppass    Really updte the user password?
     * @return  mixed    True (Success) or Jaws_Error (failure)
     */
    function UpdateProfile($uid, $fname, $lname, $gender, $dob, $url,
						   $company = '', $address = '', $address2 = '', 
						   $city = '', $country = '', $region = '', 
						   $postal = '', $phone = '', $office = '', 
						   $tollfree = '', $fax = '', $merchant_id = '', 
						   $description = '', $logo = '', $keywords = '', 
						   $company_type = '')
    {
		if (isset($GLOBALS['log'])) {
			$log_args = func_get_args();
			$GLOBALS['log']->Log(JAWS_LOG_INFO, "UsersModel->UpdateProfile() ".implode(', ',$log_args));
		}
        require_once JAWS_PATH . 'include/Jaws/User.php';
        $jUser = new Jaws_User;
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

        $result = $jUser->UpdatePersonalInfo($uid, $pInfo);
        return $result;
    }

    /**
     * Updates the profile of an user
     *
     * @access  public
     * @param   int      $uid       User's ID
     * @param   string   $username  Username
     * @param   string   $name      User's real name
     * @param   string   $email     User's email
     * @param   string   $url       User's url
     * @param   string   $password  Password
     * @param   boolean  $uppass    Really updte the user password?
     * @return  mixed    True (Success) or Jaws_Error (failure)
     */
    function UpdatePreferences($uid, $language, $theme, $editor, $timezone,
							   $notification = '', $allow_comments = true, 
							   $identifier = null)
    {
        require_once JAWS_PATH . 'include/Jaws/User.php';
        $jUser    = new Jaws_User;
        $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $pInfo = array('language' 		=> $language, 
                       'theme' 			=> $theme, 
                       'editor' 		=> $editor, 
                       'timezone' 		=> $timezone,
					   'notification' 	=> $notification, 
					   'allow_comments' => $allow_comments, 
					   'identifier' 	=> $identifier);
		$result = $jUser->UpdateAdvancedOptions($uid, $pInfo); 
        //TODO: catch error
        return $result;
    }

    /**
     * Checks if user/email are valid, if they are generates a recovery secret
     * key and sends it to the user
     *
     * @access  public
     * @param   string  $username   Username
     * @param   string  $user_email Email
     * @return  boolean Success/Failure
     */
    function SendRecoveryKey($username, $user_email)
    {
        require_once JAWS_PATH . 'include/Jaws/User.php';
        
        $userModel = new Jaws_User;
        if (!empty($username)) {
			$info = $userModel->GetUserInfoByName($username, true, true, true, true);
		} else {
			$email_users = $userModel->GetUsersByEmail($user_email, true);
			if (!Jaws_Error::IsError($email_users) && !count($email_users) <= 0) {
				reset($email_users);
				foreach ($email_users as $email_user) {
					$info = $userModel->GetUserInfoById($email_user['id'], true, true, true, true);
					break;
				}
			}
		}
		if (Jaws_Error::IsError($info)) {
			return $info;
		}
        if (!isset($info['email']) || (strtolower($info['email']) != strtolower($user_email))) {
            return new Jaws_Error(_t('USERS_USER_NOT_EXIST'));                
        }

        $secretKey = md5(uniqid(rand(), true)) . time() . floor(microtime()*1000);
        $result    = $userModel->UpdateValidationKey($info['id'], $secretKey);
        if ($result === true) {
            $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');

            $site_url    = $GLOBALS['app']->getSiteURL('/');
            $site_name   = $GLOBALS['app']->Registry->Get('/config/site_name');

            $tpl = new Jaws_Template('gadgets/Users/templates/');
            $tpl->Load('RecoverPassword.txt');
            $tpl->SetBlock('RecoverPassword');
            $tpl->SetVariable('username', $xss->filter($info['username']));
            $tpl->SetVariable('nickname', $xss->filter($info['nickname']));
            $tpl->SetVariable('message', _t('USERS_FORGOT_MAIL_MESSAGE'));
            $tpl->SetVariable('lbl_url', _t('GLOBAL_URL'));
            $tpl->SetVariable('url',
                              $GLOBALS['app']->Map->GetURLFor('Users', 'ChangePassword',
                                                              array('key' => $secretKey), true, 'site_url'));
            $tpl->SetVariable('lbl_ip', _t('GLOBAL_IP'));
            $tpl->SetVariable('ip', $_SERVER['REMOTE_ADDR']);
            $tpl->SetVariable('thanks', _t('GLOBAL_THANKS'));
            $tpl->SetVariable('site-name', $site_name);
            $tpl->SetVariable('site-url', $site_url);
            $tpl->ParseBlock('RecoverPassword');

            $message = $tpl->Get();            
            $subject = _t('USERS_FORGOT_REMEMBER', $site_name);

            require_once JAWS_PATH . 'include/Jaws/Mail.php';
            $mail = new Jaws_Mail;
            $mail->SetHeaders($user_email, '', '', $subject);
            $mail->AddRecipient($user_email);
            $mail->SetBody(Jaws_Gadget::ParseText($message, 'Users'), 'html');
            $mresult = $mail->send();
            if (Jaws_Error::IsError($mresult)) {
                return new Jaws_Error(_t('USERS_FORGOT_ERROR_SENDING_MAIL'));
            } else {
                return true;
            }
        } else {
            return new Jaws_Error(_t('USERS_FORGOT_ERROR_SENDING_MAIL'));
        }
    }

    /**
     * Changes a password from a given key
     *
     * @access  public
     * @param   string   $key   Recovery key
     * @return  boolean
     */
    function ChangePassword($key)
    {
        require_once JAWS_PATH . 'include/Jaws/User.php';

        $jUser = new Jaws_User;
        if ($id = $jUser->GetIDByValidationKey($key)) {
            $info = $jUser->GetUserInfoById($id);

            include_once 'Text/Password.php';
            $password = Text_Password::create(8, 'pronounceable', 'alphanumeric');

            $res = $jUser->UpdateValidationKey($id);
            if (Jaws_Error::IsError($res)) {
                return $res;
            }

            $res = $jUser->UpdateUser($id,
                                      $info['username'],
                                      $info['nickname'],
                                      $info['email'],
                                      $password);
            if (Jaws_Error::IsError($res)) {
                return $res;
            }

            $site_url  = $GLOBALS['app']->getSiteURL('/');
            $site_name = $GLOBALS['app']->Registry->Get('/config/site_name');

            $tpl = new Jaws_Template('gadgets/Users/templates/');
            $tpl->Load('NewPassword.txt');
            $tpl->SetBlock('NewPassword');
            $tpl->SetVariable('username', $info['username']);
            $tpl->SetVariable('nickname', $info['nickname']);
            $tpl->SetVariable('password', $password);
            $tpl->SetVariable('message',  _t('USERS_FORGOT_PASSWORD_CHANGED_MESSAGE', $info['username']));
            $tpl->SetVariable('lbl_password', _t('USERS_USERS_PASSWORD'));
            $tpl->SetVariable('lbl_username', _t('USERS_USERS_USERNAME'));
            $tpl->SetVariable('thanks', _t('GLOBAL_THANKS'));
            $tpl->SetVariable('site-name', $site_name);
            $tpl->SetVariable('site-url',  $site_url);
            $tpl->ParseBlock('NewPassword');

            $message = $tpl->Get();            
            $subject = _t('USERS_FORGOT_PASSWORD_CHANGED_SUBJECT');

            require_once JAWS_PATH . 'include/Jaws/Mail.php';
            $mail = new Jaws_Mail;

            $mail->SetHeaders($info['email'], '', '', $subject);
            $mail->AddRecipient($info['email']);
            $mail->SetBody(Jaws_Gadget::ParseText($message, 'Users'));
            $mresult = $mail->send();
            if (Jaws_Error::IsError($mresult)) {
                return new Jaws_Error(_t('USERS_FORGOT_ERROR_SENDING_MAIL'));
            } else {
                return true;
            }
        } else {
            return new Jaws_Error(_t('USERS_FORGOT_KEY_NOT_VALID'));
        }
    }

    /**
     * Changes a enabled from a given key
     *
     * @access  public
     * @param   string   $key   Recovery key
     * @return  boolean
     */
    function ActivateUser($key)
    {
        require_once JAWS_PATH . 'include/Jaws/User.php';
        
        $jUser = new Jaws_User;
        if ($id = $jUser->GetIDByValidationKey($key)) {
            $info = $jUser->GetUserInfoById($id);

            $res = $jUser->UpdateValidationKey($id);
            if (Jaws_Error::IsError($res)) {
                return $res;
            }

            $res = $jUser->UpdateUser($id,
                                       $info['username'],
                                       $info['nickname'],
                                       $info['email'],
                                       null,
                                       null,
                                       true);
            if (Jaws_Error::IsError($res)) {
                return $res;
            }

            $site_url  = $GLOBALS['app']->getSiteURL('/');
            $site_name = $GLOBALS['app']->Registry->Get('/config/site_name');

            $tpl = new Jaws_Template('gadgets/Users/templates/');
            $tpl->Load('UserNotification.txt');
            $tpl->SetBlock('Notification');
            $tpl->SetVariable('say_hello', _t('USERS_REGISTER_HELLO', $info['username']));
            $tpl->SetVariable('message', _t('USERS_ACTIVATE_ACTIVATED_MAIL_MSG'));
            if ($GLOBALS['app']->Registry->Get('/config/anon_activation') == 'user') {
                $tpl->SetBlock('Notification/IP');
                $tpl->SetVariable('lbl_ip', _t('GLOBAL_IP'));
                $tpl->SetVariable('ip', $_SERVER['REMOTE_ADDR']);
                $tpl->ParseBlock('Notification/IP');
            }

            $tpl->SetVariable('lbl_username', _t('USERS_USERS_USERNAME'));
            $tpl->SetVariable('username', $info['username']);

            $tpl->SetVariable('thanks', _t('GLOBAL_THANKS'));
            $tpl->SetVariable('site-name', $site_name);
            $tpl->SetVariable('site-url', $site_url);
            $tpl->ParseBlock('Notification');

            $body = $tpl->Get();
            $subject = _t('USERS_REGISTER_SUBJECT', $site_name);

            require_once JAWS_PATH . 'include/Jaws/Mail.php';
            $mail = new Jaws_Mail;

            $mail->SetHeaders($info['email'], '', '', $subject);
            $mail->AddRecipient($info['email']);
            $mail->SetBody(Jaws_Gadget::ParseText($body, 'Users'));
            $mresult = $mail->send();
            if (Jaws_Error::IsError($mresult)) {
                // do nothing
            }
            return true;
        } else {
            return new Jaws_Error(_t('USERS_ACTIVATION_KEY_NOT_VALID'));
        }
    }
	
    /**
     * Gets the users_gadgets status info of a user by ID
     *
     * @access  public
     * @param   string  $gadget  The gadget requested
     * @param   int     $id  The user ID
     * @param   string  $pane  The pane name requested
     * @return  mixed   Returns an array with the info of the gadget and false on error
     */
    function GetGadgetPaneInfoByUserID($gadget, $id, $pane = '')
    {
        $params       = array();
        $params['id'] = $id;
        $params['gadget'] = $gadget;
        $params['pane'] = $pane;
		$sql = '
			SELECT
				[user_id], [gadget_name], [pane], [enabled], [status], [sort_order]
			FROM [[users_gadgets]]
			WHERE ([user_id] = {id} AND [gadget_name] = {gadget}';

		if ($pane != '') {
			$sql .= ' AND [pane] = {pane}';
		}		
		$sql .= ')';
		
        $types = array('integer', 'text', 'text', 'boolean', 'text', 'integer');
        $result = $GLOBALS['db']->queryRow($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('USERS_USERS_ERROR_GET_GADGET_STATUS'), $gadget);
        }

        return $result;
    }

    /**
     * Gets the embed_gadgets info by URL
     *
     * @access  public
     * @param   string  $pane  The URL requested
     * @return  mixed   Returns an array with the info of the gadget and false on error
     */
    function GetEmbedGadgetsByUrl($url)
    {
        $params       = array();
        $params['url'] = $url;
		$sql = '
			SELECT
				[id], [gadget], [url], [gadget_url], [layout], [ownerid], [created], [updated]
			FROM [[embed_gadgets]]
			WHERE ([url] = {url})';
		
        $types = array('integer', 'text', 'text', 'text', 'text', 'integer', 'timestamp', 'timestamp');
        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('USERS_EMBED_ERROR_GET_GADGET'), _t('USERS_NAME'));
        }

        return $result;
    }
		
    /**
     * Get a list of user recommendations
     *
     * @access  public
     * @param   int     $id     ID of the comment
     * @param   int     $parent ID of the parent comment
     * @return  array   Returns a list of comments and false on error
     */
    function GetRecommendations($gadget = 'Users', $public = false, $id = null, $method = 'GetRecommendations', $limit = 5, $result_max = 10)
    {
		$uid = (int)$GLOBALS['app']->Session->GetAttribute('user_id');
		$news_items = array();
		if ($gadget == 'Users' && $method == 'GetRecommendations') {
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
					if (method_exists($hook, $method)) {
						$gadgetOwned = $hook->$method(
							array(
							'gadget_reference' => null, 
							'gadget' => $gadget, 
							'uid' => ($public === true ? $id : $uid), 
							'limit' => $limit, 
							'max' => $result_max, 
							'public' => $public
							)
						);
						if (!Jaws_Error::IsError($gadgetOwned) && is_array($gadgetOwned)) {
							foreach ($gadgetOwned as $gOwned) {
								if (!isset($gOwned['sort_order'])) {
									$gOwned['sort_order'] = microtime();
								}
								/*
								if (count($new_comments_data) <= 0 && strtolower($gOwned['createtime']) > $last_login) {
									$new_comments[] = $gOwned;
								}
								*/
								$news_items[] = $gOwned;
							}
						}
						if ($public === false) {
							$gadgetAll = $hook->$method(
								array(
									'gadget_reference' => null, 
									'gadget' => $gadget, 
									'uid' => null, 
									'limit' => $limit, 
									'max' => $result_max, 
									'public' => true
								)
							);
							if (!Jaws_Error::IsError($gadgetAll) && is_array($gadgetAll)) {
								foreach ($gadgetAll as $gAll) {
									if (!isset($gAll['sort_order'])) {
										$gAll['sort_order'] = microtime();
									}
									/*
									if (count($new_comments_data) <= 0 && strtolower($gAll['createtime']) > $last_login) {
										$new_comments[] = $gAll;
									}
									*/
									$news_items[] = $gAll;
								}
							}
						}
					}
				}
			}
		} else {
			// Load comments of requested gadget
			$hook = $GLOBALS['app']->loadHook($gadget, 'Recommendation');
			if ($hook !== false) {
				if (method_exists($hook, $method)) {
					$gadgetOwned = $hook->$method(
						array(
						'gadget_reference' => $id, 
						'gadget' => $gadget, 
						'uid' => $uid, 
						'limit' => $limit, 
						'max' => $result_max, 
						'public' => $public
						)
					);
					/*
					echo "\n\ngadgetOwned ".$i.": ";
					var_dump($gadgetOwned);
					*/
					if (!Jaws_Error::IsError($gadgetOwned) && is_array($gadgetOwned)) {
						foreach ($gadgetOwned as $gOwned) {
							if (!isset($gOwned['sort_order'])) {
								$gOwned['sort_order'] = microtime();
							}
							/*
							if (count($new_comments_data) <= 0 && strtolower($gOwned['createtime']) > $last_login) {
								$new_comments[] = $gOwned;
							}
							*/
							$news_items[] = $gOwned;
						}
					}
					if ($public === false) {
						$gadgetAll = $hook->$method(
							array(
							'gadget_reference' => null, 
							'gadget' => $gadget, 
							'uid' => null, 
							'limit' => $limit, 
							'max' => $result_max, 
							'public' => true
							)
						);
						/*
						echo "\n\ngadgetAll ".$i.": ";
						var_dump($gadgetAll);
						*/
						if (!Jaws_Error::IsError($gadgetAll) && is_array($gadgetAll)) {
							foreach ($gadgetAll as $gAll) {
								if (!isset($gAll['sort_order'])) {
									$gAll['sort_order'] = microtime();
								}
								/*
								if (count($new_comments_data) <= 0 && strtolower($gAll['createtime']) > $last_login) {
									$new_comments[] = $gAll;
								}
								*/
								$news_items[] = $gAll;
							}
						}
					}
				}
			}
		}
		return $news_items;
    }

    /**
     * Get a list of comments
     *
     * @access  public
     * @param   int     $id     ID of the comment
     * @param   int     $parent ID of the parent comment
     * @return  array   Returns a list of comments and false on error
     */
    function GetComments($gadget = 'Users', $public = false, $id = null, $method = 'GetComments', $limit = 5, $result_max = 10, $layout = 'full')
    {
		$uid = (int)$GLOBALS['app']->Session->GetAttribute('user_id');
		/*
		require_once JAWS_PATH . 'include/Jaws/User.php';
		$jUser = new Jaws_User;
		$userInfo = $jUser->GetUserInfoById($uid, true, true, true, true);
		$last_login = strtolower($userInfo['last_login']);
		$new_comments = array();
		$new_comments_data = array();
		if ($new_comments_data = $GLOBALS['app']->Session->PopSimpleResponse('Users.NewComments.Data')) {
			$new_comments = $new_comments_data;
		}
		*/
		$news_items = array();
		if ($gadget == 'Users' && $method == 'GetComments') {
			// Load comments of all gadgets
			$jms = $GLOBALS['app']->LoadGadget('Jms', 'AdminModel');
			$gadget_list = $jms->GetGadgetsList(null, true, true, true);
			if (count($gadget_list) <= 0) {
				Jaws_Error::Fatal('You don\'t have any installed gadgets, please enable/install one and then come back',
								 __FILE__, __LINE__);
			}
			reset($gadget_list);
		
			foreach ($gadget_list as $g) {
				$hook = $GLOBALS['app']->loadHook($g['realname'], 'Comment');
				if ($hook !== false) {
					if (method_exists($hook, $method)) {
						$gadgetOwned = $hook->$method(
							array(
							'gadget_reference' => ($method == 'GetComments' ? null : $id), 
							'gadget' => $gadget, 
							'uid' => ($public === true ? $id : $uid), 
							'limit' => $limit, 
							'max' => $result_max, 
							'public' => $public, 
							'layout' => $layout
							)
						);
						if (!Jaws_Error::IsError($gadgetOwned) && is_array($gadgetOwned)) {
							foreach ($gadgetOwned as $gOwned) {
								/*
								if (count($new_comments_data) <= 0 && strtolower($gOwned['createtime']) > $last_login) {
									$new_comments[] = $gOwned;
								}
								*/
								$news_items[] = $gOwned;
							}
						}
						if ($public === false) {
							$gadgetAll = $hook->$method(
								array(
									'gadget_reference' => null, 
									'gadget' => $gadget, 
									'uid' => null, 
									'limit' => $limit, 
									'max' => $result_max, 
									'public' => true,
									'layout' => $layout
								)
							);
							if (!Jaws_Error::IsError($gadgetAll) && is_array($gadgetAll)) {
								foreach ($gadgetAll as $gAll) {
									/*
									if (count($new_comments_data) <= 0 && strtolower($gAll['createtime']) > $last_login) {
										$new_comments[] = $gAll;
									}
									*/
									$news_items[] = $gAll;
								}
							}
						}
					}
				}
			}
		} else {
			/*
			echo "\n\ninitial new_count: ";
			var_dump($new_count);
			echo "\n\ninitial last_count: ";
			var_dump($last_count);
			*/
			// Load comments of requested gadget
			$hook = $GLOBALS['app']->loadHook($gadget, 'Comment');
			if ($hook !== false) {
				if (method_exists($hook, $method)) {
					$gadgetOwned = $hook->$method(
						array(
						'gadget_reference' => $id, 
						'gadget' => $gadget, 
						'uid' => $uid, 
						'limit' => $limit, 
						'max' => $result_max, 
						'public' => $public,
						'layout' => $layout
						)
					);
					if (!Jaws_Error::IsError($gadgetOwned) && is_array($gadgetOwned)) {
						foreach ($gadgetOwned as $gOwned) {
							/*
							if (count($new_comments_data) <= 0 && strtolower($gOwned['createtime']) > $last_login) {
								$new_comments[] = $gOwned;
							}
							*/
							$news_items[] = $gOwned;
						}
					}
					if ($public === false) {
						$gadgetAll = $hook->$method(
							array(
							'gadget_reference' => null, 
							'gadget' => $gadget, 
							'uid' => null, 
							'limit' => $limit, 
							'max' => $result_max, 
							'public' => true,
							'layout' => $layout
							)
						);
						/*
						echo "\n\ngadgetAll ".$i.": ";
						var_dump($gadgetAll);
						*/
						if (!Jaws_Error::IsError($gadgetAll) && is_array($gadgetAll)) {
							foreach ($gadgetAll as $gAll) {
								/*
								if (count($new_comments_data) <= 0 && strtolower($gAll['createtime']) > $last_login) {
									$new_comments[] = $gAll;
								}
								*/
								$news_items[] = $gAll;
							}
						}
					}
				}
			}
		}
		/*
		if (count($new_comments_data) <= 0) {
			$GLOBALS['app']->Session->PushSimpleResponse($new_comments, 'Users.NewComments.Data');				
		}
		*/
		return $news_items;
    }

	/**
	 * Puts avatar and format time for given comments
	 * @access private
	 */
    function _AdditionalCommentsData(&$comments, $prenum = '')
    {
        $date = $GLOBALS['app']->loadDate();
        require_once JAWS_PATH . 'include/Jaws/Gravatar.php';
        $num = 0;
        foreach ($comments as $k => $v) {
            $num++;
            $comments[$k]['avatar_source'] = Jaws_Gravatar::GetGravatar($v['email']);
            //$comments[$k]['createtime']    = $date->ToISO($v['createtime']);
            $comments[$k]['num'] = $prenum.$num;
            if (isset($comments[$k]['childs']) && count($comments[$k]['childs']) > 0) {
                $this->_AdditionalCommentsData($comments[$k]['childs'], $prenum.$num . '.');
            }
        }
    }

    /**
     * Get last comments
     *
     * @access  public
     * @return  array   Returns a list of recent comments and false on error
     */
    function GetRecentComments()
    {
        $recentcommentsLimit = $GLOBALS['app']->Registry->Get('/gadgets/Users/recentcomments_limit');

        require_once JAWS_PATH . 'include/Jaws/Comment.php';
        $api = new Jaws_Comment($this->_Name);
        $comments = $api->GetRecentComments((int)$recentcommentsLimit);

        if (Jaws_Error::IsError($comments)) {
            return new Jaws_Error(_t('USERS_ERROR_GETTING_RECENT_COMMENTS'), _t('USERS_NAME'));
        }

        /*
		$newComments = array();
        $i = 0;
        foreach ($comments as $comment) {
            $newComments[$i] = $comment;
            $userInfo = $jUser->GetUserInfoById((int)$comment['gadget_reference']);
            if (!Jaws_Error::IsError($blogEntry)) {
                $newComments[$i]['blog_title'] = $blogEntry['title'];
                $newComments[$i]['entry_id']   = $comment['gadget_reference'];
                $newComments[$i]['comment_id'] = $comment['id'];
            }
            $i++;
        }
		*/
        return $comments;
    }

    /**
     * Get total number of new comments
     *
     * @access  public
     * @return  array   Returns a list of recent comments and false on error
     */
    function GetTotalOfNewComments()
    {
		if ($new_comments_data = $GLOBALS['app']->Session->PopSimpleResponse('Users.NewComments.Data')) {
			return count($new_comments_data);
		}
		return 0;
    }

    /**
     * Get a list of comments
     *
     * @access  public
     * @param   string  $filterby Filter to use(postid, author, email, url, title, comment)
     * @param   string  $filter   Filter data
     * @param   string  $status   Spam status (approved, waiting, spam)
     * @param   mixed   $limit    Data limit (numeric/boolean)
     * @return  array   Returns a list of comments and false on error
     */
    function GetCommentsFiltered($gadget, $filterby, $filter, $status, $limit)
    {
        require_once JAWS_PATH . 'include/Jaws/Comment.php';
        $api = new Jaws_Comment($gadget);

        $filterMode = '';
        switch($filterby) {
        case 'gadget_reference':
        case 'postid':
            $filterMode = COMMENT_FILTERBY_REFERENCE;
            break;
        case 'name':
            $filterMode = COMMENT_FILTERBY_NAME;
            break;
        case 'email':
            $filterMode = COMMENT_FILTERBY_EMAIL;
            break;
        case 'url':
            $filterMode = COMMENT_FILTERBY_URL;
            break;
        case 'title':
            $filterMode = COMMENT_FILTERBY_TITLE;
            break;
        case 'ip':
            $filterMode = COMMENT_FILTERBY_IP;
            break;
        case 'comment':
            $filterMode = COMMENT_FILTERBY_MESSAGE;
            break;
        case 'various':
            $filterMode = COMMENT_FILTERBY_VARIOUS;
            break;
        case 'status':
            $filterMode = COMMENT_FILTERBY_STATUS;
            break;
        case 'ownerid':
            $filterMode = COMMENT_FILTERBY_OWNER;
            break;
        default:
            $filterMode = null;
            break;
        }

        $comments = $api->GetFilteredComments($filterMode, $filter, $status, $limit);
        if (Jaws_Error::IsError($comments)) {
            return new Jaws_Error(_t('USERS_ERROR_GETTING_FILTERED_COMMENTS'), _t('USERS_NAME'));
        }

        $date = $GLOBALS['app']->loadDate();
        $commentsGravatar = array();
        require_once JAWS_PATH . 'include/Jaws/Gravatar.php';
        foreach ($comments as $r) {
			$r['avatar_source'] = Jaws_Gravatar::GetGravatar($r['email']);
            //$r['createtime']    = $date->ToISO($r['createtime']);
            $commentsGravatar[] = $r;
        }

        return $commentsGravatar;
    }

    /**
     * Get a list of comments
     *
     * @access  public
     * @param   int     $parent ID of the parent comment
     * @return  array   Returns a list of comments and false on error
     */
    function GetCommentsOfParent($parent, $status = 'approved', $gadget = 'Users', $limit = null)
    {
        if (!in_array($status, array('approved', 'waiting', 'spam'))) {
			$status = 'approved';
        }

        $params = array();
        $params['parent']   = $parent;

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
                [sharing],
                [ownerid],
                [checksum],
                [createtime]
            FROM [[comments]]
            WHERE
                [parent] = {parent}';
        if (!empty($gadget)) {
			$params['gadget'] = $gadget;
			$sql .= ' AND [gadget] = {gadget}';
		}
        if (!empty($status)) {
			$params['status'] = $status;
			$sql .= ' AND [status] = {status}';
		}
		$sql .= ' ORDER BY [createtime] DESC';

        if (!is_null($limit)) {
			$res = $GLOBALS['db']->setLimit($limit);
			if (Jaws_Error::IsError($res)) {
				return new Jaws_Error(_t('USERS_ERROR_GETTING_COMMENTS'), _t('USERS_NAME'));
			}
		}
		
        $comments = $GLOBALS['db']->queryAll($sql, $params);
        if (Jaws_Error::IsError($comments)) {
            return new Jaws_Error(_t('USERS_ERROR_GETTING_COMMENTS'), _t('USERS_NAME'));
        }

        $commentsNew = array();

        $this->_AdditionalCommentsData($comments);

        return $comments;
    }

    /**
     * Get a comment
     *
     * @access  public
     * @param   int     $id  ID of the comment
     * @return  array   Properties of a comment and false on error
     */
    function GetComment($id, $gadget = 'Users', $public = false)
    {
        /*
		require_once JAWS_PATH . 'include/Jaws/Comment.php';
        $api = new Jaws_Comment($gadget);
        $comment = $api->GetComment($id);

        if (Jaws_Error::IsError($comment)) {
            return new Jaws_Error(_t('USERS_ERROR_GETTING_COMMENT'), _t('USERS_NAME'));
        }
		*/
		$hook = $GLOBALS['app']->loadHook($gadget, 'Comment');
		if ($hook !== false) {
			$hook_method = 'Get'.$gadget.'Comment';
			if (method_exists($hook, $hook_method)) {
				$comment = $hook->$hook_method(array('gadget_reference' => $id, 'public' => $public));
				if (!Jaws_Error::IsError($comment) && isset($comment['id']) && !empty($comment['id'])) {
					require_once JAWS_PATH . 'include/Jaws/Gravatar.php';
					$date = $GLOBALS['app']->loadDate();
					$comment['avatar_source'] = Jaws_Gravatar::GetGravatar($comment['email']);
					//$comment['createtime']    = $date->ToISO($comment['createtime']);
					$comment['comments']      = $comment['msg_txt'];
					return $comment;
				}
			}
		}
		return new Jaws_Error(_t('USERS_ERROR_GETTING_COMMENT'), _t('USERS_NAME'));
    }

    /**
     * Get a list of comments
     *
     * @access  public
     * @param   int     $id ID of the user comment should be shared with
     * @return  array   Returns a list of comments and false on error
     */
    function GetCommentsSharedWithUser($id, $gadget = 'Users', $status = 'approved', $limit = null)
    {
        if (!in_array($status, array('approved', 'waiting', 'spam'))) {
			$status = 'approved';
        }

        $params = array();
        $params['id']   = (string)$id;
        $params['gadget']   = $gadget;
        $params['everyone']	= 'everyone';
		$GLOBALS['db']->dbc->loadModule('Function', null, true);
		//$sharing = $GLOBALS['db']->dbc->function->replace('[[comments]].[sharing]', '"users:"', '""');
        $prefix = $GLOBALS['db']->dbc->function->substring('[[comments]].[sharing]', 1, 7);
        $params['sharing']   = 'users:'.$id;
        $params['prefix']   = 'users:';
        $params['status']   = $status;

        $sql = "
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
                [sharing],
                [ownerid],
                [checksum],
                [createtime]
            FROM [[comments]]
            WHERE
                $prefix = {prefix} 
				AND ([sharing] = {everyone} OR ({id} IN ([sharing]) OR [sharing] = {sharing})) 
				AND [gadget] = {gadget}";
        if (!empty($status)) {
			$params['status'] = $status;
			$sql .= ' AND [status] = {status}';
		}
		$sql .= ' ORDER BY [createtime] DESC';

        if (!is_null($limit)) {
			$res = $GLOBALS['db']->setLimit($limit);
			if (Jaws_Error::IsError($res)) {
				return new Jaws_Error(_t('USERS_ERROR_GETTING_COMMENTS'), _t('USERS_NAME'));
			}
		}
		
        $comments = $GLOBALS['db']->queryAll($sql, $params);
        if (Jaws_Error::IsError($comments)) {
            return new Jaws_Error(_t('USERS_ERROR_GETTING_COMMENTS'), _t('USERS_NAME'));
        }

        $commentsNew = array();

        $this->_AdditionalCommentsData($comments);

        return $comments;
    }
	
    /**
	 * Returns true or false if comment can be viewed by given user
     *
     * @access  public
     */
    function IsCommentSharedWithUser($comment, $gadget = 'Users', $viewer_id = null, $public = true)
    {
        require_once JAWS_PATH . 'include/Jaws/User.php';
        $jUser = new Jaws_User;
		if (!is_array($comment) && is_numeric($comment)) {
			require_once JAWS_PATH . 'include/Jaws/Comment.php';
			$api = new Jaws_Comment($gadget);
			$comment = $api->GetComment($comment);
		}
		if (!Jaws_Error::IsError($comment) && isset($comment['id']) && !empty($comment['id'])) {
			if (($public === false || $comment['ownerid'] == 0) && ($GLOBALS['app']->Session->IsAdmin() || $GLOBALS['app']->Session->IsSuperAdmin())) {
				return true;
			}
			if (is_null($viewer_id)) {
				$viewer_id = (int)$GLOBALS['app']->Session->GetAttribute('user_id');
			}
			// TODO: Comment is not hidden?
			// Comment is shared with viewer?
			if ($jUser->GetStatusOfUserInFriend($viewer_id, $comment['ownerid']) != 'blocked') {
				if (
					$comment['ownerid'] == $viewer_id || 
					($comment['sharing'] == 'everyone' && ($public === true || $comment['ownerid'] == 0)) ||
					(($comment['sharing'] == 'everyone' || $comment['sharing'] == 'friends') && 
					$jUser->GetStatusOfUserInFriend($viewer_id, $comment['ownerid']) == 'active')
				) {
					return true;
				} else if (substr($comment['sharing'], 0, 6) == 'users:') {
					$shared_with = str_replace('users:', '', $comment['sharing']);
					if (in_array($viewer_id, explode(',', $shared_with))) {
						return true;
					}					
				} else if (substr($comment['sharing'], 0, 7) == 'groups:') {
					$shared_with = str_replace('groups:', '', $comment['sharing']);
					//if ($public === false) {
						$groups = $jUser->GetGroupsOfUser($viewer_id);
						// Check if user is in group
						if (!Jaws_Error::IsError($groups)) {
							foreach ($groups as $group) {
								if (in_array($group['group_id'], explode(',', $shared_with))) {
									return true;
								}
							}
						}
					//}					
				}
			}
		}
		return false;
	}
		
    /**
     * Get a list of comments
     *
     * @access  public
     * @param   int     $id ID of the user comment should be shared with
     * @return  array   Returns a list of comments and false on error
     */
    function GetCommentsSharedWithGroup($id, $gadget = 'Users', $status = 'approved', $limit = null)
    {
        if (!in_array($status, array('approved', 'waiting', 'spam'))) {
			$status = 'approved';
        }

        $params = array();
        $params['id']   = (string)$id;
        $params['gadget']   = $gadget;
        $params['everyone']	= 'everyone';
		$GLOBALS['db']->dbc->loadModule('Function', null, true);
		//$sharing = $GLOBALS['db']->dbc->function->replace('[[comments]].[sharing]', '"groups:"', '""');
        $prefix = $GLOBALS['db']->dbc->function->substring('[[comments]].[sharing]', 1, 7);
        $params['sharing']   = 'groups:'.$id;
        $params['prefix']   = 'groups:';
        $params['status']   = $status;

        $sql = "
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
                [sharing],
                [ownerid],
                [checksum],
                [createtime]
            FROM [[comments]]
            WHERE
                $prefix = {prefix} 
				AND ([sharing] = {everyone} OR ({id} IN ([sharing]) OR [sharing] = {sharing})) 
				AND [gadget] = {gadget}";
        if (!empty($status)) {
			$params['status'] = $status;
			$sql .= ' AND [status] = {status}';
		}
		$sql .= ' ORDER BY [createtime] DESC';

        if (!is_null($limit)) {
			$res = $GLOBALS['db']->setLimit($limit);
			if (Jaws_Error::IsError($res)) {
				return new Jaws_Error(_t('USERS_ERROR_GETTING_COMMENTS'), _t('USERS_NAME'));
			}
		}
		
        $comments = $GLOBALS['db']->queryAll($sql, $params);
		if (Jaws_Error::IsError($comments)) {
            return new Jaws_Error(_t('USERS_ERROR_GETTING_COMMENTS'), _t('USERS_NAME'));
        }

        $commentsNew = array();

        $this->_AdditionalCommentsData($comments);

        return $comments;
    }
		
    /**
     * TODO: Maybe UsersModel->GetComments() should do this? (sort and aggregate similar comments)
	 * Returns an array with all comments, sorted pleasantly
     *
     * @access  public
     */
    function GetSortedComments($result = array(), $params = array())
    {
		require_once JAWS_PATH . 'include/Jaws/User.php';
		$jUser = new Jaws_User;
		$viewer_id = (int)$GLOBALS['app']->Session->GetAttribute('user_id');
		
		/*
		echo "\n\nresult :";
		var_dump($result);
		*/

		$id = $params['gadget_reference'];
		if (!is_null($id)) {
			return $result;
		}

		$results = array();
		$res = array();
		$result_ids = array();
		$result_activities = array();
		$result_replies = array();
		$replies_replies_keys = array();
		$reply_count = 0;
		foreach ($result as $r) {
			//if (!in_array($r['id'], $result_ids)) {
				$result_ids[] = $r['id'];
				$checksum = md5($r['preactivity'].$r['activity'].$r['gadget_reference']);
				if (isset($r['replies']) && is_array($r['replies']) && !count($r['replies']) <= 0) {
					if (!isset($result_replies[$checksum]) || !is_array($result_replies[$checksum])) {
						$result_replies[$checksum] = array();
					}
					if (isset($result_replies[$checksum]) && is_array($result_replies[$checksum]) && !count($result_replies[$checksum]) <= 0) {
						$result_replies[$checksum] = array_merge($r['replies'], $result_replies[$checksum]);
					} else {
						$result_replies[$checksum] = $r['replies'];
					}
					// Sort result array
					$subkey = 'createtime'; 
					$temp_array = array();
					$temp_array[key($result_replies[$checksum])] = array_shift($result_replies[$checksum]);
					foreach($result_replies[$checksum] as $key => $val){
						/*
						if (!isset($replies_replies_keys[$checksum])) {
							$replies_replies_keys[$checksum] = array();
						}
						if (!in_array($val['checksum'], $replies_replies_keys[$checksum])) {
							$replies_replies_keys[$checksum][] = $val['checksum'];
						*/
							$offset = 0;
							$found = false;
							foreach($temp_array as $tmp_key => $tmp_val) {
								$val[$subkey] = (string)$val[$subkey];
								if(!$found && strtolower($val[$subkey]) > strtolower($tmp_val[$subkey])) {
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
						//}
					}
					$result_replies[$checksum] = array_reverse($temp_array);
					$r['replies'] = $result_replies[$checksum];
				} else if (isset($result_replies[$checksum]) && is_array($result_replies[$checksum]) && !count($result_replies[$checksum]) <= 0) {
					$r['replies'] = $result_replies[$checksum];
				} else if (!isset($result_replies[$checksum])) {
					$result_replies[$checksum] = 0;
				}
				if (!empty($r['preactivity']) && !in_array($checksum, $result_activities)) {
					$result_activities[] = $checksum;
				} else if (!empty($r['preactivity']) && in_array($checksum, $result_activities)) {
					if (
						isset($this->_comments_owners[$checksum]) && 
						is_array($this->_comments_owners[$checksum]) && 
						count($this->_comments_owners[$checksum]) > 1
					) {
						$r['commenters_count'] = count($this->_comments_owners[$checksum]); 
						$preactivity = 'and '.($r['commenters_count']-1)." other".(($r['commenters_count']-1) > 1 ? "s " : " ");
						if (strtolower(substr($r['preactivity'], 0, 9)) == "is now a ") {
							$preactivity .= 'are now'.substr($r['preactivity'], 9, strlen($r['preactivity']));
						} else {
							$preactivity .= $r['preactivity'];
						}
						$r['preactivity'] = $preactivity;
					}
				}
				
				$res[$r['msg_key']] = $r;
			//}
		}
		
		foreach ($res as $rk => $rv) {
			$unique_replies = false;
			$reply_checksums = array();
			if ($rv['replies'] !== false) {
				$unique_replies = array();
				if (!is_array($rv['replies'])) {
					$related = $this->GetCommentsOfParent($rv['id'], 'approved', $rv['gadget']);
					if (!Jaws_Error::IsError($related) && is_array($related)) {
						$rv['replies'] = $related;
					} else {
						continue;
					}
				}
				foreach ($rv['replies'] as $reply) {
					if (!in_array($reply['checksum'], $reply_checksums) && $jUser->GetStatusOfUserInFriend($viewer_id, (int)$reply['ownerid']) != 'blocked') {
						$reply_checksums[] = $reply['checksum'];
						$unique_replies[] = $reply;
					}
				}
			}
			$rv['replies'] = $unique_replies;
			$results[] = $rv;
		}

		/*
		echo "\n\nresults :";
		var_dump($results);
		*/
		
		return $results;
	}

    /**
     * This function mails the comments to the admin and
     * involved user(s).
     *
     * @access public
     * @param string $email	The email(s) to sendto (comma-separated string)
     * @param string $msg_txt	The body of the email (The actual comment)
     * @param string $name	The name of the comment author
     * @param string $title	The title of the comment
     * @param string $url	The url of the author's public page.
     * @param string $gadget	The gadget scope
     * @param int $gadget_reference	The comment id or gadget id this relates to.
     * @param bool $auto	This was added automatically?
     * @return
     */
    function MailComment(
		$email, $msg_txt = '', $name = '', $title = '', $url = '', $gadget = 'Users', 
		$gadget_reference = null, $auto = false, $saved = false, $force_send = false
	) {
		$GLOBALS['app']->Registry->LoadFile('Users');
		$GLOBALS['app']->Translate->LoadTranslation('Users', JAWS_GADGET);
        $site_name  = $GLOBALS['app']->Registry->Get('/config/site_name');
		if (empty($site_name)) {
			$site_name = str_replace(array('http://', 'https://'), '', $GLOBALS['app']->GetSiteURL());
		} else {
			$site_name = preg_replace("[^A-Za-z0-9\ \.\,\'\-\_\%\#\@\!\&\(\)\?]", '', strip_tags($site_name));
			$site_name = (strlen($site_name) > 100 ? substr($site_name, 0, 100).'...' : $site_name);
		}
		if (isset($GLOBALS['log'])) {
			$GLOBALS['log']->Log(JAWS_LOG_INFO, 'Users->MailComment(): '.$GLOBALS['app']->GetSiteURL());
		}
		$mail_url = str_replace(array('http://', 'https://'), '', $GLOBALS['app']->GetSiteURL());
		if (strpos($mail_url, '/') !== false) {
			$mail_url = substr($mail_url, 0, strpos($mail_url, '/'));
        }
		$from_name  = $GLOBALS['app']->Registry->Get('/network/email_name');
		if (empty($from_name)) {
			$from_name = $GLOBALS['app']->GetSiteURL();
		}
        $from_email = $GLOBALS['app']->Registry->Get('/network/site_email');
 		if (empty($from_email)) {
            $from_email = 'webmaster@'.$mail_url;
		}
		if (!empty($title) && substr($title, 0, 8) == '{GADGET:') {
			$titles = explode('|', $title);
			$post_gadget = str_replace(array('{', '}', 'GADGET:', 'ACTION:'), '', $titles[0]);
			$post_action = str_replace(array('{', '}', 'GADGET:', 'ACTION:'), '', $titles[1]);
		}
       
		require_once JAWS_PATH . 'include/Jaws/User.php';
		$jUser = new Jaws_User;
		
		// Who is subscribed to this thread?
		$notify_users = $this->GetUsersSubscribedToComment($gadget_reference, $gadget, $saved);
		if (Jaws_Error::IsError($notify_users)) {
			return $notify_users;
		}
		
		$notify_emails = array();
		$notify_emails[] = $from_email;
		$messages = array();
		$thread = '';
		if ($saved === true) {
			$params = array();
			$params['id'] = $gadget_reference;

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
					[sharing],
					[ownerid],
					[checksum],
					[createtime]
				FROM [[comments]]
				WHERE
					[id] = {id}';

			$comment = $GLOBALS['db']->queryRow($sql, $params);
			if (!Jaws_Error::IsError($comment) && isset($comment['id']) && !empty($comment['id'])) {
			
				// TODO: Types of message reflected in subject (posted on wall, commented, etc)
				$new_comment = $comment;
				
				// Get all parents, if this is a reply
				$parent = $comment;
				if ((int)$parent['parent'] > 0) {
					require_once JAWS_PATH . 'include/Jaws/Comment.php';
					while ((int)$parent['parent'] > 0) {
						$sql = '
							SELECT [id], [gadget_reference], [gadget], [parent], [name], [email], [url],
								[ip], [title], [msg_txt], [status], [replies], [createtime], [ownerid],
								[sharing], [checksum]
							FROM [[comments]]
							WHERE [id] = {id}';

						$parent = $GLOBALS['db']->queryRow($sql, array('id' => (int)$parent['parent']));
						if (Jaws_Error::IsError($parent) || !isset($parent['id']) || empty($parent['id'])) {
							return new Jaws_Error('parent: '.var_export($parent, true), _t('USERS_NAME'));
							//return new Jaws_Error(_t('USERS_ERROR_GETTING_COMMENT'), _t('USERS_NAME'));
						}
					}
					$comment = $parent;
				}
				
				$hook = $GLOBALS['app']->loadHook($comment['gadget'], 'Comment');
				if ($hook !== false) {
					$hook_method = 'Get'.$comment['gadget'].'Comment';
					if (method_exists($hook, $hook_method)) {
						$usersHTML = $GLOBALS['app']->loadGadget('Users', 'HTML');
						if (empty($title) || substr($title, 0, 8) != '{GADGET:') {
							if ($comment['gadget'] == 'Users') {
								$thread = $usersHTML->ShowComment($comment['id'], $comment['gadget'], 5, false, 'mail', true);
							} else {
								$thread = $usersHTML->ShowComment($comment['gadget_reference'], $comment['gadget'], 5, false, 'mail', true);
							}
							if (Jaws_Error::isError($thread)) {
								$thread = '';
							} else if (!empty($thread) && file_exists(JAWS_PATH . 'gadgets/Users/resources/style.css')) {
								$thread = '<style>'.file_get_contents(JAWS_PATH . 'gadgets/Users/resources/style.css').' .comments-form {display: none;}</style>'.$thread;
							}
						}
					}
				}
				
				// Get user info of the new comment's owner
				if ((int)$new_comment['ownerid'] > 0) {
					$ownerInfo = $jUser->GetUserInfoById((int)$new_comment['ownerid'], true, true, true, true);
					if (!Jaws_Error::IsError($ownerInfo) && isset($ownerInfo['email']) && !empty($ownerInfo['email'])) {
						$owner_id = (int)$ownerInfo['id'];
						$owner_email = $ownerInfo['email'];
						$owner_name = (isset($ownerInfo['company']) && !empty($ownerInfo['company']) ? $ownerInfo['company'] : (isset($ownerInfo['nickname']) && !empty($ownerInfo['nickname']) ? $ownerInfo['nickname'] : 'Someone'));
					} else {
						$error = new Jaws_Error('ownerInfo: '.var_export($ownerInfo, true), _t('USERS_NAME'));
						//return new Jaws_Error(_t('USERS_ERROR_GETTING_COMMENT'), _t('USERS_NAME'));
					}
				} else {
					$owner_id = 0;
					$owner_email = $from_email;
					$owner_name = $from_name;
				}
				
				$msg_txt = $new_comment['msg_txt'];
				$redirect = $GLOBALS['app']->getSiteURL(). '/'. $GLOBALS['app']->Map->GetURLFor('Users', 'ShowComment', array('fusegadget' => $parent['gadget'], 'id' => $parent['id']));
			}
		} else {
			$auto = false;
			$ownerInfo = $jUser->GetUserInfoById((int)$GLOBALS['app']->Session->GetAttribute('user_id'), true, true, true, true);
			if (!Jaws_Error::IsError($ownerInfo) && isset($ownerInfo['email']) && !empty($ownerInfo['email'])) {
				// Get gadget_reference data
				$hook = $GLOBALS['app']->loadHook($gadget, 'Comment');
				if ($hook !== false) {
					$hook_method = 'Get'.$gadget.'Comment';
					if (method_exists($hook, $hook_method)) {
						if (empty($title) || substr($title, 0, 8) != '{GADGET:') {
							$usersHTML = $GLOBALS['app']->loadGadget('Users', 'HTML');
							$thread = $usersHTML->ShowComment($gadget_reference, $gadget, 5, false, 'mail', true, false);
							if (Jaws_Error::isError($thread)) {
								$thread = '';
							} else if (!empty($thread) && file_exists(JAWS_PATH . 'gadgets/Users/resources/style.css')) {
								$thread = '<style>'.file_get_contents(JAWS_PATH . 'gadgets/Users/resources/style.css').' .comments-form {display: none;}</style>'.$thread;
							}
						}
					}
				}
				foreach (explode(',', $email) as $e) {
					if (strtolower($e) != strtolower($ownerInfo['email']) || $force_send === true) {
						$notify_emails[] = strtolower($e);
					}
				}
				$redirect = $GLOBALS['app']->getSiteURL();
				$owner_id = (int)$ownerInfo['id'];
				$owner_email = $ownerInfo['email'];
				$owner_name = (isset($ownerInfo['company']) && !empty($ownerInfo['company']) ? $ownerInfo['company'] : (isset($ownerInfo['nickname']) && !empty($ownerInfo['nickname']) ? $ownerInfo['nickname'] : 'Someone'));
				if (!empty($name)) {
					$owner_name = $name;
				}
			} else {
				$error = new Jaws_Error('ownerInfo: '.var_export($ownerInfo, true), _t('USERS_NAME'));
				//return new Jaws_Error(_t('USERS_ERROR_GETTING_COMMENT'), _t('USERS_NAME'));
			}
		}
		
		foreach ($notify_users as $notify) {
			$notify_email = '';
			if ($notify > 0 && ($owner_id != $notify || $auto === true || $force_send === true)) {
				// Skip if friend status is blocked
				if ($jUser->GetStatusOfUserInFriend($owner_id, $notify) == 'blocked') {
					continue;
				}
				$userInfo = $jUser->GetUserInfoById($notify, true, true, true, true);
				if (!Jaws_Error::IsError($userInfo) && isset($userInfo['email']) && !empty($userInfo['email'])) {
					$notify_email = $userInfo['email'];
					$new_messages = $this->AddCommentToNewMessages($gadget.':'.$gadget_reference.':'.($saved === true ? 'y' : 'n'), $notify);
					if (Jaws_Error::isError($new_messages)) {
						return $new_messages;
					}
				}
			}
			if (!empty($notify_email) && !in_array(strtolower($notify_email), $notify_emails)) {
				// TODO: replace {GADGET} comments with actual gadget data, in recipient's scope...
				if (!empty($title) && substr($title, 0, 8) == '{GADGET:') {
					$info = $jUser->GetUserInfoById((int)$GLOBALS['app']->Session->GetAttribute('user_id'));
					$current_session_id = $GLOBALS['app']->Session->GetAttribute('session_id');
					$current_session_updatetime = $GLOBALS['app']->Session->GetAttribute('updatetime');
					if (isset($info['id']) && !empty($info['id'])) {
						// Spoof recipient session
						if (isset($userInfo['id']) && !empty($userInfo['id'])) {
							$GLOBALS['app']->Session->SetAttribute('logged', true);
							$GLOBALS['app']->Session->SetAttribute('session_id', null);
							$GLOBALS['app']->Session->SetAttribute('user_id', $userInfo['id']);
							$GLOBALS['app']->Session->SetAttribute('user_type', (int)$userInfo['user_type']);
							$groups = $jUser->GetGroupsOfUser($userInfo['username']);
							if (!Jaws_Error::IsError($groups)) {
								$GLOBALS['app']->Session->SetAttribute('groups', $groups);
							}
							$GLOBALS['app']->Session->SetAttribute('type', APP_TYPE);
							$GLOBALS['app']->Session->SetAttribute('updatetime', time());
							$GLOBALS['app']->Session->SetAttribute('username', $userInfo['username']);

							if (isset($userInfo['last_login'])) {
								$GLOBALS['app']->Session->SetAttribute('last_login', $userInfo['last_login']);
							}
							$GLOBALS['app']->Session->SetAttribute('nickname', $userInfo['nickname']);
							$GLOBALS['app']->Session->SetAttribute('email', $userInfo['email']);
							$GLOBALS['app']->Session->SetAttribute('url',   $userInfo['url']);
							$userInfo['timezone'] = (trim($userInfo['timezone']) == "") ? null : $userInfo['timezone'];
							$GLOBALS['app']->Session->SetAttribute('language', $userInfo['language']);
							$GLOBALS['app']->Session->SetAttribute('theme',    $userInfo['theme']);
							$GLOBALS['app']->Session->SetAttribute('editor',   $userInfo['editor']);
							$GLOBALS['app']->Session->SetAttribute('timezone', $userInfo['timezone']);
							
							// Set message to gadget data
							$layout_html = '';
							$layoutGadget = $GLOBALS['app']->LoadGadget($post_gadget, 'LayoutHTML');
							if (!Jaws_Error::isError($layoutGadget)) {
								$GLOBALS['app']->Registry->LoadFile($post_gadget);
								if (strpos($post_action, '(') === false) {
									if (method_exists($layoutGadget, $post_action)) {
										$layout_html = $layoutGadget->$post_action();
									} elseif (isset($GLOBALS['log'])) {
										$GLOBALS['log']->Log(JAWS_LOG_ERR, "Action ".$post_action." in ".$post_gadget."'s LayoutHTML doesn't exist.");
									}
								} else {
									preg_match_all('/^([a-z0-9]+)\((.*?)\)$/i', $post_action, $matches);
									if (isset($matches[1][0]) && isset($matches[2][0])) {
										if (isset($matches[1][0])) {
											if (method_exists($layoutGadget, $matches[1][0])) {
												$layout_html = $layoutGadget->$matches[1][0]($matches[2][0]);
											} elseif (isset($GLOBALS['log'])) {
												$GLOBALS['log']->Log(JAWS_LOG_ERR, "Action ".$matches[1][0]." in ".$post_gadget."'s LayoutHTML doesn't exist.");
											}
										}
									}
								}
							} else {
								if (isset($GLOBALS['log'])) {
									$GLOBALS['log']->Log(JAWS_LOG_ERR, $post_gadget ." is missing the LayoutHTML. Jaws can't execute Layout " .
														 "actions if the file doesn't exists");
								}
							}
							unset($layoutGadget);
							
							if (Jaws_Error::isError($layout_html) || empty($layout_html)) {
								if (Jaws_Error::isError($layout_html) && isset($GLOBALS['log'])) {
									$GLOBALS['log']->Log(JAWS_LOG_ERR, $layout_html->GetMessage());
								}
								$layout_html = "Log-in to <a href=\"".$GLOBALS['app']->GetSiteURL('/').$GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction')."\">".$GLOBALS['app']->GetSiteURL('/').$GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction')."</a> to view this message.";
							}
							$messages[strtolower($notify_email)] = $layout_html;
						}
						// Rollback session
						$GLOBALS['app']->Session->SetAttribute('logged', true);
						$GLOBALS['app']->Session->SetAttribute('session_id', $current_session_id);
						$GLOBALS['app']->Session->SetAttribute('user_id', $info['id']);
						$GLOBALS['app']->Session->SetAttribute('user_type', (int)$info['user_type']);
						$groups = $jUser->GetGroupsOfUser($info['username']);
						if (!Jaws_Error::IsError($groups)) {
							$GLOBALS['app']->Session->SetAttribute('groups', $groups);
						}
						$GLOBALS['app']->Session->SetAttribute('type', APP_TYPE);
						$GLOBALS['app']->Session->SetAttribute('updatetime', $current_session_updatetime);
						$GLOBALS['app']->Session->SetAttribute('username', $info['username']);

						if (isset($info['last_login'])) {
							$GLOBALS['app']->Session->SetAttribute('last_login', $info['last_login']);
						}
						$GLOBALS['app']->Session->SetAttribute('nickname', $info['nickname']);
						$GLOBALS['app']->Session->SetAttribute('email', $info['email']);
						$GLOBALS['app']->Session->SetAttribute('url',   $info['url']);
						$info['timezone'] = (trim($info['timezone']) == "") ? null : $info['timezone'];
						$GLOBALS['app']->Session->SetAttribute('language', $info['language']);
						$GLOBALS['app']->Session->SetAttribute('theme',    $info['theme']);
						$GLOBALS['app']->Session->SetAttribute('editor',   $info['editor']);
						$GLOBALS['app']->Session->SetAttribute('timezone', $info['timezone']);
					}
				}
				$notify_emails[] = strtolower($notify_email);
			}
		}
						
		if (empty($msg_txt) && empty($thread)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('USERS_ERROR_COMMENT_NOT_MAILED_TO_USERS', count($notify_emails)), RESPONSE_ERROR);
			return true;
		}
		
		require_once JAWS_PATH . 'include/Jaws/Mail.php';
		$send_count = 0;
		$error_count = 0;
		//var_dump($notify_emails);
		foreach ($notify_emails as $email) {
			$message = '';
			if (!empty($email)) {
				$mail = new Jaws_Mail;
				$subject = _t('USERS_COMMENT_REPLY', $site_name);
				
				$mail->SetHeaders($from_email, $site_name, 'webmaster@'.$mail_url, $subject);
				$addemail = $mail->AddRecipient($email, true, false);
				if (!isset($messages[$email]) || empty($messages[$email])) {
					$tpl = new Jaws_Template('gadgets/Users/templates/');
					$tpl->Load('Mail.html');
					$tpl->SetBlock('mail');
					$tpl->SetVariable('subject', _t("USERS_COMMENT_MAIL_WELCOME"));
					$tpl->SetVariable('title', (!empty($title) ? $title : _t('USERS_COMMENT_REPLY', $site_name)));
					$tpl->SetBlock('mail/body');
					$tpl->SetBlock('mail/body/message');
					if (!empty($msg_txt)) {
						if (!empty($owner_name)) {
							$message .= "<div style=\"text-align: left;\">".$owner_name.'</div>';
						}
						$message .= "<div style=\"text-align: left;\">";
						if (!empty($owner_name) && $auto === false) {
							$message .= " "._t("USERS_COMMENT_MAIL_WROTE")." ";
						}
						$message .= $msg_txt;
						$message .= "</div>";
					}
					$message .= "<div style=\"display: block; clear: both; float: none;\">&nbsp;</div>";
					// remove script tags from mail body
					if (strpos($thread, '<script') !== false) {
						while (strpos($thread, '<script') !== false) {
							$inputStr = $thread;
							$delimeterLeft = "<script";
							$delimeterRight = "</script>";
							$posLeft = (strpos($inputStr, $delimeterLeft)+strlen($delimeterLeft));
							$posRight = strpos($inputStr, $delimeterRight, $posLeft);
							$script = substr($inputStr, $posLeft, $posRight-$posLeft);
							$thread = str_replace($delimeterLeft.$script.$delimeterRight, '', $thread);
						}
					}
					$message .= preg_replace("/<script.*?>(.*)?<\/script>/im","",$thread);
					
					$tpl->SetVariable('content', $message);
					$tpl->ParseBlock('mail/body/message');
					$tpl->SetBlock('mail/body/footer');
					$footer = _t("USERS_COMMENT_MAIL_VISIT");
					$footer .= _t("USERS_COMMENT_MAIL_VISIT_URL", $redirect);
					$footer .= '&nbsp;' . _t("USERS_COMMENT_MAIL_SUBSCRIBEPREFS_URL", $GLOBALS['app']->getSiteURL(). '/'. $GLOBALS['app']->Map->GetURLFor('Users', 'Preferences'));
					$tpl->SetVariable('footer', $footer);
					$tpl->ParseBlock('mail/body/footer');
					$tpl->ParseBlock('mail/body');
					$tpl->ParseBlock('mail');
					$mail->SetBody($tpl->Get(), 'html');
				} else {
					// TODO: Make message e-mail-safe? (strip out unnecessary HTML tags)
					// TODO: Add unsubscribe link
					$mail->SetBody($messages[$email], 'html');
				}
				$mresult = $mail->send();
				if (Jaws_Error::IsError($mresult)) {
					// Trigger silent error so we don't interrupt bulk sending
					$error = new Jaws_Error($mresult->GetMessage(), _t('USERS_NAME'));
					$error_count++;
				} else {
					$send_count++;
				}
			}
		}
		$GLOBALS['app']->Session->PushLastResponse(_t('USERS_COMMENT_MAILED_TO_USERS', $send_count), RESPONSE_NOTICE);
		if ($error_count > 0) {
			$GLOBALS['app']->Session->PushLastResponse(_t('USERS_ERROR_COMMENT_NOT_MAILED_TO_USERS', $error_count), RESPONSE_ERROR);
		}
		return true;
    }

    /**
     * Create a new Comment
     *
     * @access  public
     * @param   string  $name       Name of the author
     * @param   string  $title      Title of the comment
     * @param   string  $url        Url of the author
     * @param   string  $email      Email of the author
     * @param   string  $comments   Text of the comment
     * @param   int     $parent     ID of the parent comment
     * @param   int     $gadgetId   ID of the entry
     * @param   string  $ip         IP of the author
     * @param   boolean $set_cookie Create a cookie
     * @return  boolean True if comment was added, and false if not.
     */   
	function NewComment(
		$name, $title, $url, $email, $comments, $parent, $gadgetId, $ip = '', 
		$set_cookie = true, $OwnerID = null, $sharing = 'everyone', $gadget = 'Users', 
		$auto = false, $save = true, $permalink = '', $mail = true
	) {
		$result = array();
		$date = $GLOBALS['app']->loadDate();
        require_once JAWS_PATH . 'include/Jaws/User.php';
		require_once JAWS_PATH . 'include/Jaws/Comment.php';
		$jUser = new Jaws_User;
		
		if (empty($ip)) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        if (!$parent) {
            $parent = 0;
        }

		$OwnerID 	= (!is_null($OwnerID) ? (int)$OwnerID : 0);
        $title    	= strip_tags($title);
        $comments 	= ($OwnerID > 0 && $auto === false ? strip_tags($comments) : $comments);
           
		$image_src = '';
		if ($OwnerID > 0) {
			$ownerInfo = $jUser->GetUserInfoById($OwnerID, true, true, true, true);
			if (Jaws_Error::IsError($ownerInfo)) {
				return new Jaws_Error(_t('USERS_ERROR_COMMENT_NOT_ADDED'), _t('USERS_NAME'));
			}
			$ownerName = (!empty($ownerInfo['company']) ? $ownerInfo['company'] : $ownerInfo['nickname']);
			$ownerEmail = $ownerInfo['email'];
			$groups  = $jUser->GetGroupsOfUser($ownerInfo['id']);
			// Check if user is in profile group
			$show_link = false;
			if (!Jaws_Error::IsError($groups)) {
				foreach ($groups as $group) {
					if (
						strtolower($group['group_name']) == 'profile' && 
						in_array($group['group_status'], array('active','founder','admin'))
					) {
						$show_link = true;
						break;
					}
				}
			}
			$ownerLink = ($show_link === true ? $GLOBALS['app']->Map->GetURLFor('Users', 'AccountPublic', array('name' => $ownerInfo['username'])) : '');
			$image_src = $jUser->GetAvatar($ownerInfo['username'], $ownerInfo['email']);
		} else {
			$ownerName = $GLOBALS['app']->Registry->Get('/config/site_name');
			$ownerLink = $GLOBALS['app']->GetSiteURL();
			if (empty($ownerName)) {
				$ownerName = str_replace(array('http://', 'https://'), '', $ownerLink);
			}
			$ownerEmail = $GLOBALS['app']->Registry->Get('/network/site_email');
			/*
			if (file_exists(JAWS_DATA . 'files/css/icon.png')) {
				$image_src = $GLOBALS['app']->getDataURL('', true). 'files/css/icon.png';
			} else if (file_exists(JAWS_DATA . 'files/css/logo.png')) {
				$image_src = $GLOBALS['app']->getDataURL('', true). 'files/css/logo.png';
			} else {
				$image_src = $jUser->GetAvatar('');
			}
			*/
		}

		$api = new Jaws_Comment($gadget);
		if ($save === true) {
			$res = $api->NewComment(
				$gadgetId, $ownerName, $ownerEmail, $ownerLink, $title, $comments,
				$ip, '', $parent, COMMENT_STATUS_APPROVED, $OwnerID, $sharing
			);
		} else {
			$res = true;
			$set_cookie = false;
		}
		
		//Update comments counter to +1
		if (!Jaws_Error::IsError($res)) {
			// get last ID
			$sql = "SELECT [id] FROM [[comments]] ORDER BY [id] DESC LIMIT 1";
			$max = $GLOBALS['db']->queryOne($sql, array(), array('integer'));
			if (Jaws_Error::IsError($max)) {
				return new Jaws_Error($max->GetMessage(), _t('USERS_NAME'));
			} else {
				$result['id'] = $max;
			}
			
			if ($set_cookie) {
				Jaws_Session_Web::SetCookie('visitor_name',  $ownerName,  time()+(60*24*150));
				Jaws_Session_Web::SetCookie('visitor_email', $ownerEmail, time()+(60*24*150));
				Jaws_Session_Web::SetCookie('visitor_url',   $ownerLink,   time()+(60*24*150));
			}
			
			$result['name'] = $ownerName;
			$result['link'] = $ownerLink;
			$result['comment'] = $comments;
			$result['title'] = $title;
			if (!empty($email)) {
				require_once JAWS_PATH . 'include/Jaws/Gravatar.php';
				$result['avatar_source'] = Jaws_Gravatar::GetGravatar($email);
			}
			$result['image'] = $image_src;
			
			if ($save === false) {
				$result['id'] = $gadgetId;
			}
			
			//Send an email to users subscribed to message thread
			if ($mail === true) {
				$mresult = $this->MailComment(
					$ownerEmail, $comments, $ownerName, $title, $ownerLink, $gadget, $result['id'], $auto, $save
				);
			} else {
				// Add this message to the Users' new_messages
				$notify_users = $this->GetUsersSubscribedToComment($result['id'], $gadget, $save);
				if (Jaws_Error::IsError($notify_users)) {
					return $notify_users;
				} else {
					foreach ($notify_users as $notify) {
						if ($OwnerID != $notify || $auto === true) {
							// Skip if friend status is blocked
							if ($jUser->GetStatusOfUserInFriend($OwnerID, $notify) == 'blocked') {
								continue;
							}
							$new_messages = $this->AddCommentToNewMessages($gadget.':'.$result['id'].':'.($save === true ? 'y' : 'n'), $notify);
							if (Jaws_Error::isError($new_messages)) {
								return $new_messages;
							}
						}
					}
				}
			}
						
			if (empty($permalink)) {
				$permalink = $GLOBALS['app']->getSiteURL('/');
				$permalink .= $GLOBALS['app']->Map->GetURLFor('Users', 'ShowComment', array('id' => $result['id'], 'fusegadget' => $gadget));
			}
			
			$result['permalink'] = $permalink;
			$result['created'] = $date->Format($GLOBALS['db']->Date(), "since");
			
			return $result;
		}
		return new Jaws_Error(_t('USERS_ERROR_COMMENT_NOT_ADDED'), _t('USERS_NAME'));
    }
    
	/**
     * Deletes a Comment
     *
     * @access  public
     * @param   int     $id   ID of the entry
     * @param   string  $gadget	Gadget namespace
     * @return  boolean True if comment was deleted, and false if not.
     */   
	function DeleteComment($id, $gadget = 'Users') {
		require_once JAWS_PATH . 'include/Jaws/Comment.php';
		$api = new Jaws_Comment($gadget);
		$res = $api->DeleteComment($id);
		if (!Jaws_Error::IsError($res)) {
			return true;
		}
		return new Jaws_Error($res->GetMessage(), _t('USERS_NAME'));
    }
		
	/**
     * Delete multiple Comments
     *
     * @access  public
     * @param   array     $ids   array of IDs
     * @param   string  $gadget	Gadget namespace
     * @return  boolean True if comment was deleted, and false if not.
     */   
	function MassiveDeleteComment($ids, $gadget = 'Users') {
		require_once JAWS_PATH . 'include/Jaws/Comment.php';
		$api = new Jaws_Comment($gadget);
		if (is_array($ids)) {
			foreach ($ids as $id) {
				$res = $api->DeleteComment($id);
				if (Jaws_Error::IsError($res)) {
					return new Jaws_Error($res->GetMessage(), _t('USERS_NAME'));
				}
			}
		}
		return true;
    }
			
    /**
     * Create RSS of the users
     *
     * @access  public
     * @param   boolean  $write  Flag that determinates if it should returns the RSS
     * @param   boolean  $gid  Group ID of users to get output for
     * @return  mixed    Returns the RSS(string) if it was required, or true
     */
    function makeRSS($write = false, $gid = null)
    {
        $atom = $this->GetAtomStruct($gid);
        if (Jaws_Error::IsError($atom)) {
            return $atom;
        }

        if ($write) {
            if (!Jaws_Utils::is_writable(JAWS_DATA . 'xml/')) {
                return new Jaws_Error(_t('USERS_ERROR_WRITING_RSSFILE'), _t('USERS_NAME'));
            }

            ///FIXME we need to do more error checking over here
            @file_put_contents(JAWS_DATA . 'xml/users.rss', $atom->ToRSS2WithCustom());
            //Chmod!
            Jaws_Utils::chmod(JAWS_DATA . 'xml/users.rss');
        }

        return $atom->ToRSS2WithCustom();
    }
	
    /**
     * Parses the author value that comes in RSS.
     *
     * The RSS is generated by Jaws, so we know that the format is:
     *
     *         foo@example.com (Foobar Name)
     */
    function _parseRSSAuthor($authorStr) 
    {
        if (preg_match('/(.*?)\s\((.+?)\)/i', $authorStr, $matches)) {
            return array($matches[1], $matches[2]);
        } else {
            return array('', '');
        }
    }

    /**
     * Creates the Atom struct
     *
     * @access  public
     * @return  object  Atom structure
     */
    function getAtomStruct($gid = null)
    {
        if (isset($this->_Atom) && is_array($this->_Atom->Entries) && count($this->_Atom->Entries) > 0) {
            return $this->_Atom;
        }

        require_once JAWS_PATH . 'include/Jaws/AtomFeed.php';
        $this->_Atom = new Jaws_AtomFeed();

        require_once JAWS_PATH . 'include/Jaws/Image.php';
        require_once JAWS_PATH . 'include/Jaws/User.php';
        $jUser = new Jaws_User;
        
		if (is_null($gid)) {
			$result = $jUser->GetUsers();
		} else {
			$result = $jUser->GetUsersOfGroup((int)$gid);
		}
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('USERS_ERROR_GETTING_ATOMSTRUCT'), _t('USERS_NAME'));
        }
		
        $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $url = (( isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on' ) ? 'https://' : 'http://').
               strip_tags($_SERVER['SERVER_NAME']);
        if (!isset($_SERVER['REQUEST_URI']) || empty($_SERVER['REQUEST_URI'])) {
            $url .= $xss->filter($_SERVER['SCRIPT_NAME']);
        } else {
            $url .= $xss->filter($_SERVER['REQUEST_URI']);
        }

        //$this->_Atom->SetTitle($GLOBALS['app']->Registry->Get('/config/site_name'));
        $this->_Atom->SetTitle('Users');
        $this->_Atom->SetLink($url);
        $this->_Atom->SetSiteURL($GLOBALS['app']->GetSiteURL());
        /// FIXME: Get an IRI from the URL or something...
        $this->_Atom->SetId($GLOBALS['app']->GetSiteURL());
        $this->_Atom->SetTagLine($GLOBALS['app']->Registry->Get('/config/site_slogan'));
        $this->_Atom->SetAuthor($GLOBALS['app']->Registry->Get('/config/site_author'),
                                $GLOBALS['app']->GetSiteURL(),
                                $GLOBALS['app']->Registry->Get('/network/site_email'));
        $jaws_version = $GLOBALS['app']->Registry->Get('/version');
		$this->_Atom->SetGenerator('Jaws'.(!empty($jaws_version) ? $jaws_version : ''));
        $this->_Atom->SetCopyright($GLOBALS['app']->Registry->Get('/config/copyright'));
        $date = $GLOBALS['app']->loadDate();
        foreach ($result as $r) {
            if (isset($r['user_id'])) {
				$userInfo = $jUser->GetUserInfoById((int)$r['user_id'], true, true, true, true);
			} else {
				$userInfo = $jUser->GetUserInfoById((int)$r['id'], true, true, true, true);
			}
			if (Jaws_Error::IsError($userInfo)) {
				return new Jaws_Error(_t('USERS_ERROR_GETTING_ATOMSTRUCT'), _t('USERS_NAME'));
			}
			$info = $userInfo;
			$entry = new AtomEntry();
            $entry->SetTitle($info['nickname']);
            $url = $GLOBALS['app']->Map->GetURLFor('Users', 'AccountPublic', array('name' => $info['username']));

            $entry->SetLink($url);
            $entry->SetId($url);
            if (!empty($info['logo'])) {
				$enclosure = JAWS_DATA . 'files'.$xss->parse(strip_tags($info['logo']));
				if (file_exists($enclosure)) {
					$size = filesize($enclosure);
					$img_info = Jaws_Image::image_info($enclosure);
					$entry->AddEnclosure($GLOBALS['app']->GetDataURL('', true) . 'files'.$xss->parse(strip_tags($info['logo'])),
										 $size, $img_info['mime']);
				}
            }
			$content = $xss->parse($info['description']);
            
            $entry->SetSummary($content, 'html');
			$entry->SetContent($content, 'html');
            
			//Get theme authors
			$entry->SetAuthor(str_replace('&', 'and', $info['nickname']), $this->_Atom->Link->HRef, $info['email']);
			/*
			//We have more entries?
			if (isset($tAuthors[1])) {
				for($i=1; $i<count($tAuthors); $i++) {
					//Add the author as a contributor
					$entry->AddContributor($tAuthors[$i]['name'],
										   $this->_Atom->Link->HRef,
										   $tAuthors[$i]['email']);
				}
			}
			*/
            $entry->SetPublished($date->ToISO($info['updatetime']));
            $entry->SetUpdated($date->ToISO($info['updatetime']));
			
			// Add other tags
			$entry->Categories[] = new AtomContentConstruct('g:user_id', $info['id'], 'text');
			// TODO: Use gadgets_repositories_up to filter which gadgets are shared. Currently we just use ALL user_access_items... 
			$user_gadgets = $GLOBALS['app']->Registry->Get('/gadgets/user_access_items');
			if (!empty($user_gadgets)) {
				$u_gadgets = explode(',', $GLOBALS['app']->Registry->Get('/gadgets/user_access_items'));
				foreach ($u_gadgets as $u_gadget) {
					$gadget = $GLOBALS['app']->LoadGadget($u_gadget, 'Model');
					$get_method = (isset($r['user_id']) ? 'Get'.$u_gadget.'OfGroup' : 'Get'.$u_gadget.'OfUserID');
					$get_id = (isset($r['user_id']) ? (int)$gid : $info['id']);

					if (!Jaws_Error::IsError($gadget) && method_exists($gadget, $get_method)) { 				
						// Get all gadget records
						$records = $gadget->$get_method($get_id);
						if (!count($records) <= 0) {	
							$entry->Categories[] = new AtomContentConstruct('g:gadget_'.strtolower($u_gadget), $entry->ToCDATA($GLOBALS['app']->GetSiteURL() . '/index.php?gadget='.$u_gadget.'&action=RSS'.(isset($r['user_id']) ? '&gid='.(int)$gid : '&OwnerID='.$info['id'])), 'text');
						}
					}
				}
            }
            $this->_Atom->AddEntry($entry);
            if (!isset($last_modified)) {
                $last_modified = $info['updatetime'];
            }
        }

        if (isset($last_modified)) {
            $this->_Atom->SetUpdated($date->ToISO($last_modified));
        } else {
            $this->_Atom->SetUpdated($date->ToISO(date('Y-m-d H:i:s')));
        }
        return $this->_Atom;
    }
    
    /**
     * Subscribe/un-subscribe to related comments based on filters
     *
     * @access  public
     * @param   int	$uid	User ID
     * @param   string	$filterby	Format: "<show|hide>_<Gadget>_<comment|postid|reference|gadget|ownerid>"
     * @param   mixed	$filter filter based on string, integer or array of filterbys
     * @return  array   Returns true on success and Jaws_Error on error
     */
    function SubscribeUserToComments($uid = null, $filterby = 'comment', $filter = null, $filterGadget = 'Users', $status = 'show')
    {
        if (is_null($filterby) || empty($filterby)) {
            return new Jaws_Error(_t('USERS_ERROR_GETTING_COMMENTS'), _t('USERS_NAME'));
		}
		$uid = (is_null($uid) ? (int)$GLOBALS['app']->Session->GetAttribute('user_id') : (int)$uid);
		
        if (!in_array($status,  array('show', 'hide', ''))) {
            $status = 'show';
        }
		
		$filterMode = $filterby;
		if (!in_array($filterMode, array('postid', 'ownerid', 'comment', 'reference', 'gadget'))) {
			$filterMode = 'comment';
		}
        switch($filterMode) {
		// Specific comment
        case 'postid':
		// Owner comments
        case 'ownerid':
		// Gadget references
        case 'reference':
		// Gadget comments
        case 'gadget':
			require_once JAWS_PATH . 'include/Jaws/Comment.php';
			$api = new Jaws_Comment($filterGadget);
			$subscribe = $api->SubscribeUserToComments($uid, $filterMode, $filter, $status);

			if (Jaws_Error::IsError($subscribe)) {
				return new Jaws_Error(_t('USERS_ERROR_GETTING_COMMENT'), _t('USERS_NAME'));
			}
            
			break;
		// Subscribe / Un-subscribe keywords of user interests
        case 'comment':
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
					
			if (trim($filter) != '') {
				$new_keywords = $existing_keywords;
				$old_keywords = array();
				$searchdata = explode(' ', $filter);
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
					$newstring = ucfirst(strtolower($newstring));
					if ($status == 'show' && !in_array($newstring, $new_keywords)) {
						$new_keywords[] = $newstring;
					} else if ($status == 'hide' && in_array($newstring, $existing_keywords)) {
						$old_keywords[] = $newstring;
					}
				  }
				}
				if (!count($old_keywords) <= 0) {
					$new_keywords = array_diff($existing_keywords, $old_keywords);
				}
			}
            break;
        /*
		case 'various':
            if (
				is_array($filter) && (isset($filter['postid']) || 
				isset($filter['ownerid']) || isset($filter['comment']) || 
				isset($filter['reference']) || isset($filter['gadget']))
			) {
					
			}
			$filterMode = COMMENT_FILTERBY_MESSAGE;
            break;
        */
        }

        return true;
    }
	
	/**
     * Returns string of who comment is shared with
     *
     * @access  public
     * @return  string  XHTML
     */
    function GetCommentShareActivity($sharing)
    {
		require_once JAWS_PATH . 'include/Jaws/User.php';
		$jUser = new Jaws_User;
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$share_activity = '';
		if (substr($sharing, 0, 6) == 'users:') {
			$share_users = explode(',', str_replace('users:', '', $sharing));
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
								$share_user_name = (strlen($xss->filter(strip_tags(htmlspecialchars_decode($shareInfo['company'], ENT_QUOTES)))) > 100 ? substr($xss->filter(strip_tags(htmlspecialchars_decode($shareInfo['company'], ENT_QUOTES))), 0, 100).'...' : $xss->filter(strip_tags(htmlspecialchars_decode($shareInfo['company'], ENT_QUOTES))));
								$share_user_fullname = $xss->filter(strip_tags(htmlspecialchars_decode($shareInfo['company'], ENT_QUOTES)));
							} else {
								$share_user_name = (strlen($xss->filter(strip_tags(htmlspecialchars_decode($shareInfo['nickname'], ENT_QUOTES)))) > 100 ? substr($xss->filter(strip_tags(htmlspecialchars_decode($shareInfo['nickname'], ENT_QUOTES))), 0, 100).'...' : $xss->filter(strip_tags(htmlspecialchars_decode($shareInfo['nickname'], ENT_QUOTES))));
								$share_user_fullname = $xss->filter(strip_tags(htmlspecialchars_decode($shareInfo['nickname'], ENT_QUOTES)));
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
								$share_user_link_start = '<a href="'.$GLOBALS['app']->GetSiteURL().'/'.$GLOBALS['app']->Map->GetURLFor('Users', 'AccountPublic', array('name' => $shareInfo['username'])).'" title="'.preg_replace("[^A-Za-z0-9\ ]", '', $share_user_fullname).'" name="'.preg_replace("[^A-Za-z0-9\ ]", '', $share_user_fullname).'">';
								$share_user_link_end = '</a>';
							}
							$share_activity .= (!empty($share_activity) ? ', ' : "&nbsp;<span style=\"display: inline; font-weight: bold; color: #666666;\">&gt;</span>&nbsp;&nbsp;").$share_user_link_start.$share_user_name.$share_user_link_end;
							$share_count++;
						}
					//}
				}
			}
		} else if (substr($sharing, 0, 7) == 'groups:') {
			$share_groups = explode(',', str_replace('groups:', '', $sharing));
			$count_share_groups = count($share_groups);
			reset($share_groups);
			$share_count = 1;
			if ($count_share_groups < 4) {
				foreach ($share_groups as $share) {
					$groupInfo = $jUser->GetGroupInfoById((int)$share);
					if (!Jaws_Error::IsError($groupInfo) && isset($groupInfo['id']) && !empty($groupInfo['id'])) {
						$share_group_name = (strlen($xss->filter(strip_tags(htmlspecialchars_decode($groupInfo['title'], ENT_QUOTES)))) > 100 ? substr($xss->filter(strip_tags(htmlspecialchars_decode($groupInfo['title'], ENT_QUOTES))), 0, 100).'...' : $xss->filter(strip_tags(htmlspecialchars_decode($groupInfo['title'], ENT_QUOTES))));
						$share_group_fullname = $xss->filter(strip_tags(htmlspecialchars_decode($groupInfo['title'], ENT_QUOTES)));
						$share_group_link = $GLOBALS['app']->Map->GetURLFor('Users', 'GroupPage', array('group' => $groupInfo['name']));
						$share_group_link_start = '<a href="'.$GLOBALS['app']->GetSiteURL().'/'.$GLOBALS['app']->Map->GetURLFor('Users', 'GroupPage', array('group' => $groupInfo['name'])).'" title="'.preg_replace("[^A-Za-z0-9\ ]", '', $share_group_fullname).'" name="'.preg_replace("[^A-Za-z0-9\ ]", '', $share_group_fullname).'">';
						$share_group_link_end = '</a>';
						$share_activity .= (!empty($share_activity) ? ', ' : "&nbsp;<span style=\"display: inline; font-weight: bold; color: #666666;\">&gt;</span>&nbsp;&nbsp;").$share_group_link_start.$share_group_name.$share_group_link_end;
					}
				}
			}
		}
		return $share_activity;
	}
	
	/**
     * Returns array of users subscribed to comment
     *
     * @access  public
     * @return  string  XHTML
     */
    function GetUsersSubscribedToComment($id = null, $gadget = 'Users', $saved = true)
    {
		require_once JAWS_PATH . 'include/Jaws/User.php';
		$jUser = new Jaws_User;
		$notify_users = array();
		$result =  array();
		$params = array();
		if ($saved === true) {
			$params['id'] = $id;

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
					[sharing],
					[ownerid],
					[checksum],
					[createtime]
				FROM [[comments]]
				WHERE
					[id] = {id}
			';

			$comment = $GLOBALS['db']->queryRow($sql, $params);
			if (!Jaws_Error::IsError($comment) && isset($comment['id']) && !empty($comment['id'])) {
				// TODO: Types of message reflected in subject (posted on wall, commented, etc)
				$new_comment = $comment;
				
				$notify_users[] = (int)$new_comment['ownerid'];
				if ($new_comment['gadget'] == 'Users' && !in_array((int)$new_comment['gadget_reference'], $notify_users)) {
					$notify_users[] = (int)$new_comment['gadget_reference'];
				}
				$share_users = (strpos($new_comment['sharing'], 'users:') !== false ? explode(',', str_replace('users:', '', $new_comment['sharing'])) : array());
				foreach ($share_users as $share) {
					if (!in_array((int)$share, $notify_users)) {
						$notify_users[] = (int)$share;
					}
				}
				// Get all parents, if this is a reply
				$parent = $comment;
				if ((int)$parent['parent'] > 0) {
					require_once JAWS_PATH . 'include/Jaws/Comment.php';
					while ((int)$parent['parent'] > 0) {
						$sql = '
							SELECT [id], [gadget_reference], [gadget], [parent], [name], [email], [url],
								[ip], [title], [msg_txt], [status], [replies], [createtime], [ownerid],
								[sharing], [checksum]
							FROM [[comments]]
							WHERE [id] = {id}';

						$parent = $GLOBALS['db']->queryRow($sql, array('id' => (int)$parent['parent']));
						if (Jaws_Error::IsError($parent) || !isset($parent['id']) || empty($parent['id'])) {
							return new Jaws_Error('parent: '.var_export($parent, true), _t('USERS_NAME'));
							//return new Jaws_Error(_t('USERS_ERROR_GETTING_COMMENT'), _t('USERS_NAME'));
						}
						if (!in_array((int)$parent['ownerid'], $notify_users)) {
							$notify_users[] = (int)$parent['ownerid'];
						}
						if ($parent['gadget'] == 'Users' && !in_array((int)$parent['gadget_reference'], $notify_users)) {
							$notify_users[] = (int)$parent['gadget_reference'];
						}
						$share_users = (strpos($parent['sharing'], 'users:') !== false ? explode(',', str_replace('users:', '', $parent['sharing'])) : array());
						foreach ($share_users as $share) {
							if (!in_array((int)$share, $notify_users)) {
								$notify_users[] = (int)$share;
							}
						}
						$related = $this->GetCommentsOfParent($parent['id'], 'approved', $parent['gadget']);
						if (Jaws_Error::IsError($related)) {
							return new Jaws_Error('related: '.var_export($related, true), _t('USERS_NAME'));
							//return new Jaws_Error(_t('USERS_ERROR_GETTING_COMMENT'), _t('USERS_NAME'));
						}
						foreach ($related as $r) {
							if (!in_array((int)$r['ownerid'], $notify_users)) {
								$notify_users[] = (int)$r['ownerid'];
							}
							if ($r['gadget'] == 'Users' && !in_array((int)$r['gadget_reference'], $notify_users)) {
								$notify_users[] = (int)$r['gadget_reference'];
							}
							$share_users = (strpos($r['sharing'], 'users:') !== false ? explode(',', str_replace('users:', '', $r['sharing'])) : array());
							foreach ($share_users as $share) {
								if (!in_array((int)$share, $notify_users)) {
									$notify_users[] = (int)$share;
								}
							}
						}
					}
					$comment = $parent;
				}
				
				$hook = $GLOBALS['app']->loadHook($comment['gadget'], 'Comment');
				if ($hook !== false) {
					$hook_method = 'Get'.$comment['gadget'].'Comment';
					if (method_exists($hook, $hook_method)) {
						$usersHTML = $GLOBALS['app']->loadGadget('Users', 'HTML');
						if ($comment['gadget'] != 'Users') {
							$comment_hook = $hook->$hook_method(array('gadget_reference' => $comment['gadget_reference'], 'public' => false));
							if (!in_array((int)$comment_hook['ownerid'], $notify_users)) {
								$notify_users[] = (int)$comment_hook['ownerid'];
							}
							if (!in_array((int)$comment_hook['targetid'], $notify_users)) {
								$notify_users[] = (int)$comment_hook['targetid'];
							}
							if (isset($comment_hook['replies']) && is_array($comment_hook['replies'])) {
								foreach ($comment_hook['replies'] as $r) {
									if (!in_array((int)$r['ownerid'], $notify_users)) {
										$notify_users[] = (int)$r['ownerid'];
									}
									if ($r['gadget'] == 'Users' && !in_array((int)$r['gadget_reference'], $notify_users)) {
										$notify_users[] = (int)$r['gadget_reference'];
									}
									$share_users = (strpos($r['sharing'], 'users:') !== false ? explode(',', str_replace('users:', '', $r['sharing'])) : array());
									foreach ($share_users as $share) {
										if (!in_array((int)$share, $notify_users)) {
											$notify_users[] = (int)$share;
										}
									}
								}
							}
						}
					}
				}
			}
		} else {
			// Get gadget_reference data
			$hook = $GLOBALS['app']->loadHook($gadget, 'Comment');
			if ($hook !== false) {
				$hook_method = 'Get'.$gadget.'Comment';
				if (method_exists($hook, $hook_method)) {
					$comment_hook = $hook->$hook_method(array('gadget_reference' => $id, 'public' => false));
					if (!in_array((int)$comment_hook['ownerid'], $notify_users)) {
						$notify_users[] = (int)$comment_hook['ownerid'];
					}
					if (isset($comment_hook['replies']) && is_array($comment_hook['replies'])) {
						foreach ($comment_hook['replies'] as $r) {
							if (!in_array((int)$r['ownerid'], $notify_users)) {
								$notify_users[] = (int)$r['ownerid'];
							}
							if ($r['gadget'] == 'Users' && !in_array((int)$r['gadget_reference'], $notify_users)) {
								$notify_users[] = (int)$r['gadget_reference'];
							}
							$share_users = (strpos($r['sharing'], 'users:') !== false ? explode(',', str_replace('users:', '', $r['sharing'])) : array());
							foreach ($share_users as $share) {
								if (!in_array((int)$share, $notify_users)) {
									$notify_users[] = (int)$share;
								}
							}
						}
					}
				}
			}
		}
		foreach ($notify_users as $notify) {	
			$notify = (int)$notify;
			if ($notify > 0) {
				$userInfo = $jUser->GetUserInfoById($notify, true, true);
				if (!Jaws_Error::IsError($userInfo) && isset($userInfo['id']) && !empty($userInfo['id']) && $userInfo['notification'] == '') {
					$result[] = $notify;
				}
			}
		}
		return $result;
	}

	/**
     * Add comment to user's new_messages
     *
     * @access  public
     * @return  mixed bool on success, Jaws_Error on error
     */
    function AddCommentToNewMessages($id, $uid = null)
    {
		require_once JAWS_PATH . 'include/Jaws/User.php';
		$jUser = new Jaws_User;
		$viewer_id = (is_null($uid) ? (int)$GLOBALS['app']->Session->GetAttribute('user_id') : $uid);
		$userInfo = $jUser->GetUserInfoById($viewer_id, true, true, true, true);
		if (!Jaws_Error::IsError($userInfo) && isset($userInfo['id']) && !empty($userInfo['id'])) {
			$new_messages = '';
			if (!in_array($id, explode(',', $userInfo['new_messages']))) {
				if ($userInfo['new_messages'] == '') {
					$new_messages = $id;
				} else {
					$new_messages = $userInfo['new_messages'].','.$id;
				}
				// Save new_messages
				$update = $jUser->UpdateAdvancedOptions($userInfo['id'], array('new_messages' => $new_messages));
				if ($update === true) {
					return true;
				}
				return new Jaws_Error(_t('USERS_ERROR_GETTING_COMMENT'), _t('USERS_NAME'));
			}
		}
		return true;
	}
	
	/**
     * Remove comment from user's new_messages
     *
     * @access  public
     * @return  mixed bool on success, Jaws_Error on error
     */
    function RemoveCommentFromNewMessages($id, $uid = null)
    {
		require_once JAWS_PATH . 'include/Jaws/User.php';
		$jUser = new Jaws_User;
		$viewer_id = (is_null($uid) ? (int)$GLOBALS['app']->Session->GetAttribute('user_id') : $uid);
		$userInfo = $jUser->GetUserInfoById($viewer_id, true, true, true, true);
		if (!Jaws_Error::IsError($userInfo) && isset($userInfo['id']) && !empty($userInfo['id'])) {
			$new_messages = '';
			if (in_array($id, explode(',', $userInfo['new_messages']))) {
				if ($userInfo['new_messages'] == $id) {
					$new_messages = '';
				} else {
					$new_messages = str_replace(','.$id, '', $userInfo['new_messages']);
				}
				// Save new_messages
				$update = $jUser->UpdateAdvancedOptions($userInfo['id'], array('new_messages' => $new_messages));
				if ($update === true) {
					return true;
				}
				return new Jaws_Error(_t('USERS_ERROR_GETTING_COMMENT'), _t('USERS_NAME'));
			}
		}
		return true;
	}
	
	/**
     * Remove multiple comments from user's new_messages
     *
     * @access  public
     * @return  mixed bool on success, Jaws_Error on error
     */
    function MassRemoveCommentsFromNewMessages($ids, $uid = null)
    {
		require_once JAWS_PATH . 'include/Jaws/User.php';
		$jUser = new Jaws_User;
		$viewer_id = (is_null($uid) ? (int)$GLOBALS['app']->Session->GetAttribute('user_id') : $uid);
		$userInfo = $jUser->GetUserInfoById($viewer_id, true, true, true, true);
		if (Jaws_Error::IsError($userInfo) && isset($userInfo['id']) && !empty($userInfo['id'])) {
			$new_messages = '';
			if (is_array($ids) && !count($ids) <= 0) {
				foreach ($ids as $id) {
					if (in_array($id, explode(',', $userInfo['new_messages']))) {
						if ($userInfo['new_messages'] == $id) {
							$new_messages = '';
							break;
						} else {
							$new_messages = str_replace(','.$id, '', $userInfo['new_messages']);
						}
					}
				}
			}
			// Save new_messages
			if ($new_messages != $userInfo['new_messages']) {
				$update = $jUser->UpdateAdvancedOptions($userInfo['id'], array('new_messages' => $new_messages));
				if ($update === true) {
					return true;
				}
				return new Jaws_Error(_t('USERS_ERROR_GETTING_COMMENT'), _t('USERS_NAME'));
			}
		}
		return true;
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
    function GetUsersRequests($params)
    {
		require_once JAWS_PATH . 'include/Jaws/User.php';
		$jUser = new Jaws_User;
		if (!isset($GLOBALS['app']->ACL)) {
			$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		}
		$gadget = $params['gadget'];
		$uid = $params['user_id'];
		$public = $params['public'];
		$news_items = array();
		
		if ($gadget == 'Users' && $public === false) {
			$hook = $GLOBALS['app']->loadHook($gadget, 'Comment');
			if ($hook !== false) {
				if (method_exists($hook, 'GetGroupRequests')) {
					$manage_groups = $GLOBALS['app']->ACL->GetFullPermission(
								$GLOBALS['app']->Session->GetAttribute('username'), 
								$GLOBALS['app']->Session->GetAttribute('groups'), 'Users', 'ManageGroups');
					if ($manage_groups) {
						$groups = $jUser->GetAllGroups();
					} else {
						$groups = $jUser->GetGroupsOfUser($viewer_id);
					}
					foreach ($groups as $group) {
						if ($manage_groups) {
							$group['group_status'] = 'admin';
							$group['group_id'] = $group['id'];
						}
						if (in_array($group['group_status'], array('founder','admin'))) {
							$requests = $hook->GetGroupRequests(
								array(
								'gadget_reference' => $group['group_id'], 
								'gadget' => $gadget, 
								'uid' => $uid, 
								'limit' => 999999, 
								'max' => 999999, 
								'public' => $public
								)
							);
							if (!Jaws_Error::IsError($requests) && is_array($requests)) {
								foreach ($requests as $request) {
									$news_items[] = $request;
								}
							}
						}
					}
				}
			}
		}
		return $news_items;
	}
    
	/**
     * Account Home Panes 
     *
     * @access  public
     * @return  array   Array of panes
     */
    function GetPanes($layout = null)
    {
		require_once JAWS_PATH . 'include/Jaws/User.php';
        $jUser = new Jaws_User;
		$panes = array();
        
		$info  = $jUser->GetUserInfoById((int)$GLOBALS['app']->Session->GetAttribute('user_id'), true, true, true, true);
        if (!Jaws_Error::isError($info) && isset($info['id']) && !empty($info['id'])) {
		
			$panes_found = false;
			
			// Newsfeed pane
			// FIXME: Add Users registry key for showing Users pane...
			//$updates_html = $this->ShowComments('Users', false);
			$params_array = array('standalone' => '1', 'p' => 'false');
			$params_array = array_merge($params_array, $_GET);
			if (!is_null($layout)) {
				$params_array['o'] = $layout;
			}
			unset($params_array['gadget']);
			unset($params_array['action']);
			$panes[] = array(
				'id' => 'Users',
				'desc' => 'All',
				'icon' => $GLOBALS['app']->GetJawsURL() . '/gadgets/Users/images/icon_updates.png',
				'gadget' => 'All',
				'class' => 'pane-item pane-selected',
				'style' => '',
				'gadgetrealname' => 'Users',
				'method' => 'ShowComments',
				'params' => $params_array
			);
			
			// Get all groups of user
			$groups_found = false;
			$groups = array();
			$available_groups = array();
			$usergroups = $jUser->GetGroupsOfUser($GLOBALS['app']->Session->GetAttribute('user_id'));
			foreach ($usergroups as $usergroup) {
				$available_groups[] = str_replace(array('_owners', '_users'), '', $usergroup['group_name']);
				$groups[] = $usergroup;
			}
			
			// Get public groups (gadget_users) enabled for users
			$user_access = $GLOBALS['app']->Registry->Get('/gadgets/user_access_items');
			$user_access = explode(",",$user_access);
			foreach ($user_access as $p_group) {
				if (!empty($p_group) && !in_array(strtolower($p_group), $available_groups)) {
					$available_groups[] = strtolower($p_group);
					$groups[] = array(
						'group_id' => strtolower($p_group).'_users',
						'group_title' => strtolower($p_group).'_users',
						'group_name' => strtolower($p_group).'_users',
						'group_status' => 'active',
						'group_checksum' => ''
					);
				}
			}
			
			// Gadget panes
			$jms = $GLOBALS['app']->LoadGadget('Jms', 'AdminModel');
			$gadget_list = $jms->GetGadgetsList(null, true, true, true);
			
			//Hold.. if we dont have a selected gadget?.. like no gadgets?
			if (count($gadget_list) <= 0) {
				Jaws_Error::Fatal('There are no installed gadgets, enable/install one and then come back',
								 __FILE__, __LINE__);
			} else {
				reset($gadget_list);
				//Construct panes for each available gadget
				foreach ($gadget_list as $gadget) {
					if (file_exists(JAWS_PATH . '/gadgets/'.$gadget['realname'].'/HTML.php')) {
						$paneGadget = $GLOBALS['app']->LoadGadget($gadget['realname'], 'HTML');
						if (
							!Jaws_Error::IsError($paneGadget) && method_exists($paneGadget, 'GetUserAccountControls') && 
							method_exists($paneGadget, 'GetUserAccountPanesInfo') && 
							in_array($gadget['realname'], explode(',', $GLOBALS['app']->Registry->Get('/gadgets/user_access_items')))
						) {
							// Check if user's groups match gadget
							$inGroup = false;
							foreach ($groups as $group) {
								if (
									substr(strtolower($group['group_name']), 0, strlen(strtolower($gadget['realname']))) == strtolower($gadget['realname']) && 
									(in_array($group['group_status'], array('active','founder','admin')))
								) {
									$inGroup = true;
									break;
								}
							}
							$pane_groups = $paneGadget->GetUserAccountControls($info, $groups);
							if (!Jaws_Error::IsError($pane_groups)) {
								foreach ($pane_groups as $pane) {
									if ($panes_found !== true) {
										$panes_found = true;
									}
									if ($inGroup === true) {
										if ($groups_found !== true) {
											$groups_found = true;
										}
										if (file_exists(JAWS_PATH . 'gadgets/'.$gadget['realname'].'/resources/style.css')) {
											$GLOBALS['app']->Layout->AddHeadLink('gadgets/'.$gadget['realname'].'/resources/style.css', 'stylesheet', 'text/css', 'Users');
										}
										$panes[] = array(
											'id' => $gadget['realname'],
											'desc' => $pane['name'],
											'icon' => $pane['icon'],
											'gadget' => $pane['name'],
											'class' => 'pane-item',
											'style' => 'display: none;',
											'gadgetrealname' => $gadget['realname'],
											'method' => $pane['method'], 
											'params' => $pane['params']
										);
									}
								}
							} else {
								$error = new Jaws_Error($pane_groups->GetMessage(), $this->_Name);
								//return $pane_groups;
								//exit;
							}
						} else if (Jaws_Error::IsError($paneGadget)) {
							$error = new Jaws_Error($paneGadget->GetMessage(), $this->_Name);
						}
						unset($paneGadget);
					}
				}
			}
			
			// Friends pane
			// FIXME: Add Users registry key for showing Friends pane...
			//$friends_html = '';
			//$friends_html = $this->ShowRecommendations('Friends');
			//$friends_html .= $this->UserDirectory(false);
			$params_array = array('standalone' => '1', 'Users_public' => 'false');
			$params_array = array_merge($params_array, $_GET);
			unset($params_array['gadget']);
			unset($params_array['action']);
			$panes[] = array(
				'id' => 'Friends',
				'desc' => _t('USERS_ACCOUNTHOME_PANE_FRIENDS'),
				'icon' => $GLOBALS['app']->GetJawsURL() . '/gadgets/Users/images/logo.png',
				'gadget' => 'Users',
				'class' => 'pane-item',
				'style' => 'display: none;',
				'gadgetrealname' => 'Users',
				'method' => 'UserDirectory',
				'params' => $params_array
			);
			
			// Groups pane
			$params_array = array('standalone' => '1', 'Users_public' => 'false');
			$params_array = array_merge($params_array, $_GET);
			unset($params_array['gadget']);
			unset($params_array['action']);
			$panes[] = array(
				'id' => 'Groups',
				'desc' => _t('USERS_ACCOUNTHOME_PANE_GROUPS'),
				'icon' => $GLOBALS['app']->GetJawsURL() . '/gadgets/Users/images/Groups.png',
				'gadget' => 'Groups',
				'class' => 'pane-item',
				'style' => 'display: none;',
				'gadgetrealname' => 'Users',
				'method' => 'GroupDirectory',
				'params' => $params_array
			);
			
		}
		
		return $panes;
	}
}