<?php
/**
 * Users Gadget
 *
 * @category   Gadget
 * @package    Users
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2004-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class UsersHTML extends Jaws_GadgetHTML
{
    var $_Name = 'Users';
    
	/**
     * Gadget constructor
     *
     * @access 	public
     * @return 	void
     */
    function UsersHTML()
    {
        $this->Init('Users');
    }

    /**
     * Default Action
     *
     * @access  public
     * @return  string  HTML template of DefaultAction
     */
    function DefaultAction()
    {
        return $this->LoginForm();
    }

	/**
     * Show Login Form
     *
     * @access  public
     * @param 	boolean 	$bar 	output LoginBar
     * @return  string  HTML template
     */
    function LoginForm($bar = false)
	{
		require_once JAWS_PATH . 'include/Jaws/Header.php';
		$request =& Jaws_Request::getInstance();
		$get = $request->get(array('gadget', 'action', 'msg'), 'get');
		$redirectTo = $request->get('redirect_to', 'post');
		$redirectTo = (isset($redirectTo) && !empty($redirectTo) ? urldecode($redirectTo) : '');
		$full_url = $GLOBALS['app']->GetFullURL();
		$site_url = $GLOBALS['app']->GetSiteURL();
		if (empty($redirectTo)) {
			$redirectTo = $request->get('redirect_to', 'get');
			$redirectTo = (isset($redirectTo) && !empty($redirectTo) ? urldecode($redirectTo) : '');
		}
		if (!$GLOBALS['app']->Session->Logged()) {
			$site_ssl_url = $GLOBALS['app']->Registry->Get('/config/site_ssl_url');
			if (!empty($site_ssl_url) && (!isset($_SERVER['HTTPS']) || strtolower($_SERVER['HTTPS']) != 'on')) {
				$redirectTo = 'https://'. str_replace(array('http://','https://'), '', str_replace(str_replace(array('http://','https://'), '', $site_url), $site_ssl_url, $full_url));
				Jaws_Header::Location($redirectTo);
			}
			$GLOBALS['app']->Layout->AddHeadLink('gadgets/Users/resources/style.css', 'stylesheet', 'text/css');
			if (empty($redirectTo)) {
				$redirectTo = $GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction');
			}
			$redirectTo = str_replace('&amp;', '&', $redirectTo);
			$tpl = new Jaws_Template('gadgets/Users/templates/');
            if ($bar === true) {
				$tpl->Load('LoginBar.html');
				$tpl->SetBlock('LoginBar');
				$tpl->SetVariable('actionName', 'LoginBar');
			} else {
				$tpl->Load('LoginBox.html');
				$tpl->SetBlock('LoginBox');
				$tpl->SetVariable('actionName', 'LoginBox');
			}
            $tpl->SetVariable('title', _t('USERS_LOGIN_TITLE'));
            $tpl->SetVariable('site_url', urlencode($site_url));
            $tpl->SetVariable('base_script', BASE_SCRIPT);
            $tpl->SetVariable('login', _t('GLOBAL_LOGIN'));
            $tpl->SetVariable('username', _t('GLOBAL_USERNAME'));
            $tpl->SetVariable('password', _t('GLOBAL_PASSWORD'));
            $tpl->SetVariable('remember', _t('GLOBAL_REMEMBER_ME'));
            $tpl->SetVariable('redirect_to', $redirectTo);
			$rpx_redirect = $redirectTo;
			$rpx_redirect = (substr(strtolower($rpx_redirect), 0, 4) != 'http' ? $site_url.'/'.$rpx_redirect : $rpx_redirect);
			$rpx_redirect = str_replace('=', '__EQ__', $rpx_redirect);
			$rpx_redirect = str_replace('/', '__FS__', $rpx_redirect);
			$rpx_redirect = str_replace('&', '__AM__', $rpx_redirect);
			$rpx_redirect = str_replace('?', '__QM__', $rpx_redirect);
			if ($GLOBALS['app']->Registry->Get('/config/anon_register') == 'true') {
                $link =& Piwi::CreateWidget('Link', _t('USERS_REGISTER'),
                                            $GLOBALS['app']->Map->GetURLFor('Users', 'Registration'));
                $tpl->SetVariable('user-register', $link->Get());
            }

            if ($GLOBALS['app']->Registry->Get('/gadgets/Users/password_recovery') == 'true') {
                $link =& Piwi::CreateWidget('Link', _t('USERS_FORGOT_PASSWORD'),
                                            $GLOBALS['app']->Map->GetURLFor('Users', 'ForgotPassword'));
                $tpl->SetVariable('forgot-password', $link->Get());
            }

            /*
			if ($get['gadget'] == 'Users' && ($get['action'] == 'DefaultAction' || $get['action'] == 'LoginForm')) {
				$focusOnload = "function focusOnLoad(focusObject) {
					if (document.getElementById(focusObject)) {
						document.getElementById(focusObject).focus();
						document.getElementById(focusObject).select();
					}
				}

				Event.observe(window,\"load\", function () {
					focusOnLoad('login_username');
				});";
				$tpl->SetVariable('focus_onload', $focusOnload);
            }
			*/
			
			if ($bar === true) {
				$tpl->ParseBlock('LoginBar');
			} else {	
				if ($response = $GLOBALS['app']->Session->PopSimpleResponse()) {
					$tpl->SetBlock('LoginBox/response');
					$tpl->SetVariable('msg', $response);
					$tpl->ParseBlock('LoginBox/response');
				} else if (!empty($get['msg'])) {
					$tpl->SetBlock('LoginBox/response');
					$tpl->SetVariable('msg', $xss->parse($get['msg']));
					$tpl->ParseBlock('LoginBox/response');
				}
				$tpl->ParseBlock('LoginBox');
				if ($GLOBALS['app']->Registry->Get('/config/anon_register') == 'true') {
					$register_html = $this->Registration();
					$tpl->SetBlock('RegisterBox');
					$tpl->SetVariable('content', $register_html);
					$tpl->ParseBlock('RegisterBox');
				}
			}
			return $tpl->Get();
        } else {
			if (!empty($redirectTo)) {
				$redirectTo = str_replace('&amp;', '&', $redirectTo);
				if (
					strtolower($redirectTo) != strtolower($GLOBALS['app']->Map->GetURLFor('Users', 'Registration')) &&  
					strtolower($redirectTo) != strtolower($GLOBALS['app']->Map->GetURLFor('Users', 'LoginForm')) &&  
					strtolower($redirectTo) != strtolower($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction')) &&  
					strtolower($redirectTo) != strtolower($GLOBALS['app']->Map->Parse($this->GetURLFor('Users', 'Registration'), true)) && 
					strtolower($redirectTo) != strtolower($GLOBALS['app']->Map->Parse($this->GetURLFor('Users', 'LoginForm'), true)) && 
					strtolower($redirectTo) != strtolower($GLOBALS['app']->Map->Parse($this->GetURLFor('Users', 'DefaultAction'), true)) 
				) {
					if (strpos(strtolower($redirectTo), 'admin.php') !== false &&   
					!$GLOBALS['app']->ACL->GetFullPermission(
						$GLOBALS['app']->Session->GetAttribute('username'), 
						$GLOBALS['app']->Session->GetAttribute('groups'), 'ControlPanel', 'default')
					) {
						return $this->ShowNoPermission($GLOBALS['app']->Session->GetAttribute('username'), 'ControlPanel', 'default');
					} else {
						Jaws_Header::Location($redirectTo);
					}
				}
			}
			if ($bar === false  && $get['gadget'] == 'Users' && ($get['action'] == 'DefaultAction' || $get['action'] == 'LoginForm')) {
				return $this->AccountHome();
			} else {
				return '';
			}
        }
	}

    /**
     * Logins an user, if something goes wrong then redirect user to previous page
     * and notify the error
     *
     * @access  public
     * @return  void
     */
    function Login()
    {
		require_once JAWS_PATH . 'include/Jaws/Header.php';
        $request =& Jaws_Request::getInstance();
        $post    = $request->get(array('username', 'password', 'remember', 'redirect_to'), 'post');
        $get    = $request->get(array('redirect_to'), 'get');
		if (isset($GLOBALS['log'])) {
			$log_post = $post;
			$log_post['password'] = '*****';
			$log_get = $get;
			$log_get['password'] = '*****';
			$GLOBALS['log']->Log(JAWS_LOG_INFO, "UsersHTML->Login() ".var_export($log_post, true).var_export($log_get, true));
		}

		$redirectTo = $post['redirect_to'];
		$redirectTo = (isset($redirectTo) && !empty($redirectTo) ? urldecode($redirectTo) : '');
		if (empty($redirectTo)) {
			$redirectTo = $get['redirect_to'];
			$redirectTo = (isset($redirectTo) && !empty($redirectTo) ? urldecode($redirectTo) : '');
		}

        $login = $GLOBALS['app']->Session->Login($post['username'], $post['password'], $post['remember']);
		if (Jaws_Error::isError($login)) {
            $GLOBALS['app']->Session->PushSimpleResponse($login->GetMessage());
			Jaws_Header::Location($GLOBALS['app']->GetSiteURL() . '/index.php?gadget=Users&action=DefaultAction&redirect_to='.urlencode($redirectTo));
        }

		//require_once JAWS_PATH . 'include/Jaws/Header.php';
		$redirect = '';
		if ($GLOBALS['app']->Session->Logged() && ($GLOBALS['app']->Session->IsAdmin() || $GLOBALS['app']->Session->IsSuperAdmin())) {
			if (!empty($redirectTo)) {
				if ($redirectTo == $GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction')) {
					$redirect = $GLOBALS['app']->Registry->Get('/config/home_page');
				} else {
					if (substr($redirectTo, 0, 1) == '?') {
						$redirectTo = str_replace('&amp;', '&', $redirectTo);
						$redirectTo = 'index.php'.$redirectTo;
					}
					//Jaws_Header::Location($redirectTo);
					$redirect = $redirectTo;
				}
			} else {	
				//Jaws_Header::Location();
				$redirect = 'admin.php?gadget=ControlPanel&action=DefaultAction';
			}
		} else {
			if (empty($redirectTo)) {
				$redirectTo = 'index.php';
				if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
					$redirectTo = $_SERVER['HTTP_REFERER'];
				}
			}
			if (substr($redirectTo, 0, 1) == '?') {
				$redirectTo = str_replace('&amp;', '&', $redirectTo);
				$redirectTo = 'index.php'.$redirectTo;
			}
			//Jaws_Header::Location($redirectTo);
			$redirect = $redirectTo;
		}
		if (substr(strtolower($redirect), 0, 5) == 'index' || substr(strtolower($redirect), 0, 5) == 'admin' || 
			substr(strtolower($redirect), 0, 6) == '/index' || substr(strtolower($redirect), 0, 6) == '/admin') {
			$redirect = $GLOBALS['app']->GetSiteURL('', false, 'http') .'/'.(substr(strtolower($redirect), 0, 1) == '/' ? substr($redirect, 1, strlen($redirect)) : $redirect);
		}
		if (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on') {
			$output = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">";
			$output .= "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\">";
			$output .= "<head>";
			$output .= "<title>Logging in</title>";
			$output .= "</head>";
			$output .= "<body>";
			$output .= "<form action='".$GLOBALS['app']->GetSiteURL('', false, 'http')."/index.php?gadget=Users&action=DoRegister' method='post' name='frm'>";

			foreach ($post as $a => $b) {
				if (strtolower($a) != 'redirect_to') {
					$output .= "<input type='hidden' name='".$a."' value='".$b."'>";
				}
			}
			$output .= "<input type='hidden' name='redirect_to' value='".str_replace('&amp;', '&', $redirect)."'>";

			$output .= "<noscript>";
			$output .= "Logging in...<br /><input type='submit' value='Click Here to Continue'>";
			$output .= "</noscript>"; 
			$output .= "</form>";
			$output .= "<script type='text/javascript' language='JavaScript'>";
			$output .= "document.frm.submit();";
			$output .= "</script>"; 
			$output .= "</body>"; 
			$output .= "</html>"; 
			echo $output;
		} else {
			Jaws_Header::Location(str_replace('&amp;', '&', $redirect));
		}
    }

    /**
     * Logout an user
     *
     * @access  public
     * @return  void
     */
    function Logout()
    {
		require_once JAWS_PATH . 'include/Jaws/Header.php';
		$urlRedirect = $GLOBALS['app']->GetSiteURL('', false, 'http').'/'.$GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction');
		if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER']) && strpos(strtolower($_SERVER['HTTP_REFERER']), 'logout') === false) {
			$urlRedirect = $_SERVER['HTTP_REFERER'];
		}
		$logged = false;
		if ($GLOBALS['app']->Session->Logged() && $GLOBALS['app']->Session->GetAttribute('username') != 'anonymous') {
            $logged = true;
			$GLOBALS['app']->Session->Logout();
        }

		if ($logged === true && isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on') {
			$urlRedirect = $GLOBALS['app']->GetSiteURL('', false, 'http')."/index.php?gadget=Users&action=Logout";
		} else if ($logged === true && $GLOBALS['app']->Registry->Get('/config/site_ssl_url') != '') {
			$urlRedirect = $GLOBALS['app']->GetSiteURL('', false, 'https')."/index.php?gadget=Users&action=Logout";
		}
		Jaws_Header::Location($urlRedirect);
    }

    /**
     * Prepares the NoPermission HTML template
     *
     * @access  public
     * @param   string  $user    Username
     * @param   string  $gadget  Gadget user is requesting
     * @param   string  $action  The 'denied' action
     * @return  string  HTML template
     */
    function ShowNoPermission($user, $gadget, $action)
    {
        $GLOBALS['app']->Translate->LoadTranslation('Users', JAWS_GADGET);

        // Load the template
        $tpl = new Jaws_Template('gadgets/Users/templates/');
        $tpl->Load('NoPermission.html');
        $tpl->SetBlock('NoPermission');
        $tpl->SetVariable('nopermission', _t('USERS_NO_PERMISSION_TITLE'));
        $tpl->SetVariable('description', _t('USERS_NO_PERMISSION_DESC', $gadget, $action));
        /*
		$tpl->SetVariable('admin_script', BASE_SCRIPT);
        $tpl->SetVariable('site-name', $GLOBALS['app']->Registry->Get('/config/site_name'));
        $tpl->SetVariable('site-slogan', $GLOBALS['app']->Registry->Get('/config/site_slogan'));
        $tpl->SetVariable('BASE_URL', $GLOBALS['app']->GetSiteURL('/'));
        $tpl->SetVariable('.dir', _t('GLOBAL_LANG_DIRECTION') == 'rtl' ? '.' . _t('GLOBAL_LANG_DIRECTION') : '');
		*/
		if (empty($user)) {
            $tpl->SetBlock('NoPermission/anonymous');
            $tpl->SetVariable('anon_description',
                              _t('USERS_NO_PERMISSION_ANON_DESC',
                                 $GLOBALS['app']->Map->GetURLFor('Users', 'LoginForm')));
            $tpl->ParseBlock('NoPermission/anonymous');
        }
        $tpl->ParseBlock('NoPermission');
        return $tpl->Get();
    }

    /**
     * Tells the user the registation process is done.
     *
     * @access  public
     * @return  string  HTML of template
     */
    function Registered()
    {
        // Load the template
        $tpl = new Jaws_Template('gadgets/Users/templates/');
        $tpl->Load('Registered.html');
        $tpl->SetBlock('registered');
        $tpl->SetVariable('title', _t('USERS_REGISTER_REGISTERED'));
        
		$email = $GLOBALS['app']->Session->_Attributes['signup_email'];
        
		switch ($GLOBALS['app']->Registry->Get('/config/anon_activation')) {
            case 'admin':
                $message = _t('USERS_ACTIVATE_ACTIVATION_BY_ADMIN_MSG', (!empty($email) ? " (".$email.')' : ''));
                break;
            case 'user':
                $message = _t('USERS_ACTIVATE_ACTIVATION_BY_USER_MSG', (!empty($email) ? " (".$email.')' : ''));
                break;
            default:
                $message = _t('USERS_REGISTER_REGISTERED_MSG', (!empty($email) ? " (".$email.')' : ''), $this->GetURLFor('LoginForm'));
        }

        $tpl->SetVariable('registered_msg', $message);
        $tpl->ParseBlock('registered');
        return $tpl->Get();
    }

    /**
     * Allow user registrations.
     *
     * @category  feature
     * @access  public
     * @return  void
     */
    function DoRegister()
    {
		require_once JAWS_PATH . 'include/Jaws/Header.php';
		require_once JAWS_PATH . 'include/Jaws/User.php';

        $request =& Jaws_Request::getInstance();
        $step    = $request->get('step', 'post');
        $error   = '';
        $result  = '';
        $request =& Jaws_Request::getInstance();
        $post = $request->get(array('username', 'email', 'nickname', 'password', 'password_check',
                                    'fname', 'lname', 'gender', 'dob_year', 'dob_month', 'dob_day','url',
                                    'captcha', 'nocaptcha', 'captchaKey', 'group', 'show_hash', 'address', 
									'address2', 'city', 'country', 'region', 'postal', 'remember', 'redirect_to'),
                              'post');

        $get = $request->get(array('redirect_to'),'get');
		$GLOBALS['app']->Session->_Attributes['signup_email'] = $post['email'];
		$redirectTo = $post['redirect_to'];
		$redirectTo = (isset($redirectTo) && !empty($redirectTo) ? urldecode($redirectTo) : '');
		if (empty($redirectTo)) {
			$redirectTo = $get['redirect_to'];
			$redirectTo = (isset($redirectTo) && !empty($redirectTo) ? urldecode($redirectTo) : '');
		}
		$redirectTo = str_replace('&amp;', '&', $redirectTo);
		if (isset($GLOBALS['log'])) {
			$log_post = $post;
			$log_post['password'] = '*****';
			$log_post['password_check'] = (!empty($log_post['password_check']) ? '*****' : '');
			$log_get = $get;
			$log_get['password'] = '*****';
			$log_get['password_check'] = (!empty($log_get['password_check']) ? '*****' : '');
			$GLOBALS['log']->Log(JAWS_LOG_INFO, "UsersHTML->DoRegister() ".var_export($log_post, true).var_export($log_get, true));
		}
		
		$redirect = '';
		
		// See if this is an existing user
		$jUser = new Jaws_User;
		$info  = $jUser->GetUserInfoByName($post['username']);
		$has_account = false;
		if (isset($info['id']) && !empty($info['id']) && isset($post['password']) && !empty($post['password'])) {
			$result = true;
			$has_account = true;
		} else {
			if ($GLOBALS['app']->Registry->Get('/config/anon_register') !== 'true') {
				return parent::_404();
			}

			if (!empty($post['password']) && strlen($post['password']) >= 32) {
				$error = _t('USERS_USERS_PASSWORD_TOO_LONG');
			}        
			
			$GLOBALS['app']->Registry->LoadFile('Policy');	        
			$_captcha = $GLOBALS['app']->Registry->Get('/gadgets/Policy/captcha');
	        if ($_captcha != 'DISABLED') {
	            require_once JAWS_PATH . 'gadgets/Policy/captchas/' . $_captcha . '.php';
	            $captcha = new $_captcha();
	            if (!$captcha->Check()) {
	                $error = _t('GLOBAL_CAPTCHA_ERROR_DOES_NOT_MATCH');
	            }
	        }

			if (empty($error)) {
	            if ($post['password'] !== $post['password_check']) {
	                $result = _t('USERS_USERS_PASSWORDS_DONT_MATCH');
	            } else {
	                $anon_type = (int) $GLOBALS['app']->Registry->Get('/config/anon_type');
	                $anon_type = empty($anon_type)? 2 : $anon_type; //anon_type can't be super-admin
	
	                $dob  = null;
	                if (!empty($post['dob_year']) && !empty($post['dob_year']) && !empty($post['dob_year'])) {
	                    $date = $GLOBALS['app']->loadDate();
	                    $dob  = $date->ToBaseDate($post['dob_year'], $post['dob_month'], $post['dob_day']);
	                    $dob  = date('Y-m-d H:i:s', $dob['timestamp']);
	                }
	
	                $uModel = $GLOBALS['app']->LoadGadget('Users', 'Model');
	                $result = $uModel->CreateUser(
						$post['username'],
						$post['email'],
						$post['nickname'],
						$post['fname'],
						$post['lname'],
						$post['gender'],
						$dob,
						$post['url'],
						$post['password'],
						$anon_type,
						$GLOBALS['app']->Registry->Get('/config/anon_group'),
						$post['address'], 
						$post['address2'], 
						$post['city'], 
						$post['country'], 
						$post['region'], 
						$post['postal'], 
						'', 
						($post['nocaptcha'] == 'rpxnow' ? true : false), 
						$redirectTo
					);
	            }
			}
        }

		if (empty($error) && $result === true && isset($post['password']) && !empty($post['password'])) {
			//$error1 = new Jaws_Error("Trying to auto-login: ".var_export($post['username'], true).var_export(array('password' => '*****'), true), _t('USERS_NAME'));
			$login = $GLOBALS['app']->Session->Login($post['username'], $post['password'], $post['remember']);
			if (Jaws_Error::isError($login)) {
				$error = $login->GetMessage();
			} else {
				if ($post['show_hash'] == '1' && $post['nocaptcha'] == 'rpxnow') {
					//echo $GLOBALS['app']->Session->GetAttribute('session_id');
					$attributes = $GLOBALS['app']->Session->_Attributes;
					foreach ($attributes as $sk => $sv) {
						if (is_array($sv)) {
							foreach ($sv as $k => $v) {
								//echo $sk.'['.$k.']='.$v.';';
								echo $v.',';
							}
							echo ';';
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
						//Jaws_Header::Location('index.php?gadget=Users&action=RequestGroupAccess&group='.$groupInfo['id'].(!empty($redirectTo) ? '&redirect_to='.urlencode($redirectTo) : ''));
						$redirect = 'index.php?gadget=Users&action=RequestGroupAccess&group='.$groupInfo['id'].(!empty($redirectTo) ? '&redirect_to='.urlencode($redirectTo) : '');
					}
				}
				if (!empty($redirectTo)) {
					//Jaws_Header::Location($redirectTo);
					$redirect = $redirectTo;
				} else {
					//Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'));
					$redirect = $GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction');
				}
			}
		} else if (empty($error) && !empty($redirectTo) && isset($post['password']) && !empty($post['password'])) {
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
			if (!is_bool($result)) {
				$GLOBALS['app']->Session->PushSimpleResponse($result, 'Users.Register');
			}
			Jaws_Header::Location($GLOBALS['app']->Map->Parse($redirectTo, true).'&register_error='.$result);
			//$redirect = $GLOBALS['app']->Map->Parse($redirectTo, true).'&register_error='.$result;
		} else if (empty($error)) {
			if (!is_bool($result)) {
				$error = $result;
			}
		}

        if (empty($redirect)) {
			if (!empty($error)) {
				if (isset($GLOBALS['log'])) {
					$GLOBALS['log']->Log(JAWS_LOG_ERR, $error);
				}
				$error2 = new Jaws_Error($error, _t('USERS_NAME'));

				$GLOBALS['app']->Session->PushSimpleResponse($error, 'Users.Register');
				// unset unnecessary registration data
		        unset($post['password'],
		              $post['password_check'],
		              $post['random_password'],
		              $post['captcha'],
		              $post['captchaKey']);
		        $GLOBALS['app']->Session->PushSimpleResponse($post, 'Users.Register.Data');				
				if ($has_account === true) {
					Jaws_Header::Location('index.php?gadget=Users&action=DefaultAction'.(!empty($redirectTo) ? '&redirect_to='.urlencode($redirectTo) : ''));
				} else {
					Jaws_Header::Location('index.php?gadget=Users&action=Registration'.(!empty($redirectTo) ? '&redirect_to='.urlencode($redirectTo) : ''));
				}
				//$redirect = 'index.php?gadget=Users&action=Registration'.(!empty($redirectTo) ? '&redirect_to='.urlencode($redirectTo) : '');
			} else {
				//Jaws_Header::Location($this->GetURLFor('Registered'));
				$redirect = $GLOBALS['app']->Map->GetURLFor('Users', 'Registered');
			}
		}
		
		if (substr(strtolower($redirect), 0, 5) == 'index' || substr(strtolower($redirect), 0, 5) == 'admin' || 
			substr(strtolower($redirect), 0, 6) == '/index' || substr(strtolower($redirect), 0, 6) == '/admin') {
			$redirect = $GLOBALS['app']->GetSiteURL('', false, 'http') .'/'.(substr(strtolower($redirect), 0, 1) == '/' ? substr($redirect, 1, strlen($redirect)) : $redirect);
		}

		if (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on' && $has_account === true) {
			$output = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">";
			$output .= "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\">";
			$output .= "<head>";
			$output .= "<title>Logging in</title>";
			$output .= "</head>";
			$output .= "<body>";
			$output .= "<form action='".$GLOBALS['app']->GetSiteURL('', false, 'http')."/index.php?gadget=Users&action=DoRegister' method='post' name='frm'>";
			foreach ($post as $a => $b) {
				if (strtolower($a) != 'redirect_to') {
					$output .= "<input type='hidden' name='".$a."' value='".$b."'>";
				}
			}
			$output .= "<input type='hidden' name='redirect_to' value='".$redirect."'>";

			$output .= "<noscript>";
			$output .= "Logging in...<br /><input type='submit' value='Click Here to Continue'>";
			$output .= "</noscript>"; 
			$output .= "</form>";
			$output .= "<script type='text/javascript' language='JavaScript'>";
			$output .= "document.frm.submit();";
			$output .= "</script>"; 
			$output .= "</body>"; 
			$output .= "</html>"; 
			echo $output;
		} else {
			Jaws_Header::Location($redirect);
		}
		exit;
    }

    /**
     * Prepares a single form to get registered
     *
     * @access  public
     * @param  integer 	$group 	Group ID to register user into
     * @return  string  HTML of template
     */
    function Registration($group = null)
    {
        require_once JAWS_PATH . 'include/Jaws/Header.php';
		$request =& Jaws_Request::getInstance();
        $post = $request->get(array('redirect_to'),'post');
        $get = $request->get(array('redirect_to', 'gadget', 'group'),'get');
        $username = $GLOBALS['app']->Session->GetAttribute('username');
		$redirectTo = $post['redirect_to'];
		$redirectTo = (isset($redirectTo) && !empty($redirectTo) ? urldecode($redirectTo) : '');
		if (empty($redirectTo)) {
			$redirectTo = $get['redirect_to'];
			$redirectTo = (isset($redirectTo) && !empty($redirectTo) ? urldecode($redirectTo) : '');
		}
		$full_url = $GLOBALS['app']->GetFullURL();
		$site_url = $GLOBALS['app']->GetSiteURL();
        if ($GLOBALS['app']->Session->Logged()) {
			$redirectTo = str_replace('&amp;', '&', $redirectTo);
			if (
				!empty($redirectTo) && strtolower($redirectTo) != strtolower($GLOBALS['app']->Map->GetURLFor('Users', 'Registration')) &&  
				strtolower($redirectTo) != strtolower($GLOBALS['app']->Map->GetURLFor('Users', 'LoginForm')) &&  
				strtolower($redirectTo) != strtolower($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction')) &&  
				strtolower($redirectTo) != strtolower($GLOBALS['app']->Map->Parse($GLOBALS['app']->Map->GetURLFor('Users', 'Registration'), true)) && 
				strtolower($redirectTo) != strtolower($GLOBALS['app']->Map->Parse($GLOBALS['app']->Map->GetURLFor('Users', 'LoginForm'), true)) && 
				strtolower($redirectTo) != strtolower($GLOBALS['app']->Map->Parse($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'), true)) 
			) {
				if (strpos(strtolower($redirectTo), 'admin.php') !== false &&   
					!$GLOBALS['app']->ACL->GetFullPermission(
						$username, 
						$GLOBALS['app']->Session->GetAttribute('groups'), 'ControlPanel', 'default')
				) {
					return $this->ShowNoPermission($username, 'ControlPanel', 'default');
				} else {
					Jaws_Header::Location($redirectTo);
				}
			} else if (JAWS_SCRIPT != 'admin') {
				Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'));
			}
        }

        if ($GLOBALS['app']->Registry->Get('/config/anon_register') !== 'true') {
			return parent::_404();
        }
		
		if (isset($GLOBALS['log'])) {
			$GLOBALS['log']->Log(JAWS_LOG_INFO, "UsersHTML->Registration() ".var_export($post, true).var_export($get, true));
		}
		$GLOBALS['app']->Layout->AddHeadLink('gadgets/Users/resources/style.css', 'stylesheet', 'text/css');
		$gadget = $get['gadget'];
		if (is_null($group)) {
			$group = $get['group'];
			if (isset($group) && !empty($group)) {
				require_once JAWS_PATH . 'include/Jaws/User.php';
				$jUser = new Jaws_User;
				$info = $jUser->GetGroupInfoByName($group);
				if (!Jaws_Error::IsError($info)) {
					if (!isset($info['id'])) {
						$info = $jUser->GetGroupInfoById((int)$group);
						if (isset($info['id']) && !empty($info['id'])) {
							$group = $info['name'];
						} else {
							$group = null;
						}
					}
				}
			} else {
				$group = null;
			}
		}
        // Load the template
        $tpl = new Jaws_Template('gadgets/Users/templates/');
        $tpl->Load('Register.html');
        if (!empty($username) && $username != 'anonymous') {
            if (!empty($redirectTo)) {
				//Jaws_Header::Location($GLOBALS['app']->GetSiteURL().'/');
				Jaws_Header::Location($redirectTo);
			} else if ($gadget == 'Users') {
				//Jaws_Header::Location($GLOBALS['app']->GetSiteURL().'/');
				Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'));
			} else if (JAWS_SCRIPT != 'admin') {
				$tpl->SetBlock('already_registered');
				$tpl->SetVariable('content', 'You\'re already registered. <a href="'.$GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction').'">Go to your Account Home</a>');
				$tpl->ParseBlock('already_registered');
			}
        }
		$tpl->SetBlock('register');
		$tpl->SetVariable('title', _t('USERS_REGISTER'));
		$tpl->SetVariable('base_script', BASE_SCRIPT);
		if (!is_null($group)) {
			$tpl->SetVariable('group', $group);
		} else {
			$tpl->SetVariable('group', $GLOBALS['app']->Registry->Get('/config/anon_group'));
		}

		$tpl->SetVariable('lbl_account_info',  _t('USERS_ACCOUNT_INFO'));
		$tpl->SetVariable('lbl_username',      _t('USERS_USERS_USERNAME'));
		$tpl->SetVariable('validusernames',    _t('USERS_REGISTER_VALID_USERNAMES'));
		$tpl->SetVariable('lbl_email',         _t('GLOBAL_EMAIL'));
		$tpl->SetVariable('lbl_url',           _t('GLOBAL_URL'));
		$tpl->SetVariable('lbl_nickname',         _t('USERS_USERS_NICKNAME'));
		$tpl->SetVariable('lbl_password',      _t('USERS_USERS_PASSWORD'));
		$tpl->SetVariable('sendpassword',      _t('USERS_USERS_SEND_AUTO_PASSWORD'));
		$tpl->SetVariable('lbl_checkpassword', _t('USERS_USERS_PASSWORD_VERIFY'));

		$tpl->SetVariable('lbl_personal_info', _t('USERS_PERSONAL_INFO'));
		$tpl->SetVariable('lbl_fname',         _t('USERS_USERS_FIRSTNAME'));
		$tpl->SetVariable('lbl_lname',         _t('USERS_USERS_LASTNAME'));
		$tpl->SetVariable('lbl_gender',        _t('USERS_USERS_GENDER'));
		$tpl->SetVariable('gender_male',       _t('USERS_USERS_MALE'));
		$tpl->SetVariable('gender_female',     _t('USERS_USERS_FEMALE'));
		$tpl->SetVariable('lbl_dob',           _t('USERS_USERS_BIRTHDAY'));
		$tpl->SetVariable('dob_sample',        _t('USERS_USERS_BIRTHDAY_SAMPLE'));

		if ($post_data = $GLOBALS['app']->Session->PopSimpleResponse('Users.Register.Data')) {
			$tpl->SetVariable('username',  $post_data['username']);
			$tpl->SetVariable('email',     $post_data['email']);
			$tpl->SetVariable('url',       $post_data['url']);
			$tpl->SetVariable('nickname',     $post_data['nickname']);
			$tpl->SetVariable('fname',     $post_data['fname']);
			$tpl->SetVariable('lname',     $post_data['lname']);
			$tpl->SetVariable('dob_year',  $post_data['dob_year']);
			$tpl->SetVariable('dob_month', $post_data['dob_month']);
			$tpl->SetVariable('dob_day',   $post_data['dob_day']);
			$tpl->SetVariable("selected_gender_{$post_data['gender']}", 'selected="selected"');
		} else {
			$tpl->SetVariable('url', 'http://');
			$tpl->SetVariable("selected_gender_0", 'selected="selected"');
		}

		$tpl->SetVariable('register', _t('USERS_REGISTER'));
		$tpl->SetVariable('redirect_to', $redirectTo);
		$GLOBALS['app']->Registry->LoadFile('Policy');
		$_captcha = $GLOBALS['app']->Registry->Get('/gadgets/Policy/captcha');
		if ($_captcha != 'DISABLED') {
			require_once JAWS_PATH . 'gadgets/Policy/captchas/' . $_captcha . '.php';
			$captcha = new $_captcha();
			$captchaRes = $captcha->Get();
			$tpl->SetBlock('register/captcha');
			$tpl->SetVariable('lbl_captcha', _t('GLOBAL_CAPTCHA_CODE'));
			$tpl->SetVariable('captcha', $captchaRes['captcha']->Get());
			if (!empty($captchaRes['entry'])) {
				$tpl->SetVariable('captchavalue', $captchaRes['entry']->Get());
			}
			$tpl->SetVariable('captcha_msg', _t('GLOBAL_CAPTCHA_CODE_DESC'));
			$tpl->ParseBlock('register/captcha');
		}
		if ($response = $GLOBALS['app']->Session->PopSimpleResponse('Users.Register')) {
			$tpl->SetBlock('register/response');
			$tpl->SetVariable('msg', $response);
			$tpl->ParseBlock('register/response');
		}
		$tpl->ParseBlock('register');
        
        return $tpl->Get();
    }

    /**
     * Double opt-in account activation or manually by admins.
     *
     * @category  feature
     * @access  public
     * @return  mixed 	Jaws_Error on error, or response string
     */
    function ActivateUser()
    {
        require_once JAWS_PATH . 'include/Jaws/Header.php';
        if ($GLOBALS['app']->Session->Logged() && !$GLOBALS['app']->Session->IsSuperAdmin()) {
            Jaws_Header::Location('');
        }

        if ($GLOBALS['app']->Registry->Get('/config/anon_register') !== 'true') {
            return parent::_404();
        }

        $request =& Jaws_Request::getInstance();
        $key     = $request->get('key', 'get');

        // Load the template
        $tpl = new Jaws_Template('gadgets/Users/templates/');
        $tpl->Load('Registered.html');
        $tpl->SetBlock('registered');
        
		$model  = $GLOBALS['app']->LoadGadget('Users', 'Model');
        $result = $model->ActivateUser($key);
        if (Jaws_Error::IsError($result)) {
			$message = $result->getMessage();
			$tpl->SetVariable('title', _t('USERS_ACTIVATE_NOTACTIVATED'));
        } else {
			$tpl->SetVariable('title', _t('USERS_ACTIVATE_ACTIVATED'));
			if ($GLOBALS['app']->Registry->Get('/config/anon_activation') == 'user') {
				$message = _t('USERS_ACTIVATE_ACTIVATED_BY_USER_MSG', $this->GetURLFor('LoginForm'));
			} else {
				$message = _t('USERS_ACTIVATE_ACTIVATED_BY_ADMIN_MSG');
			}
        }
		
		$tpl->SetVariable('registered_msg', $message);
        $tpl->ParseBlock('registered');
        return $tpl->Get();
    }

    /**
     * Verifies if user/email/(captcha) are valid, if they are a mail
     * is sent to user with a secret (MD5) key
     *
     * @access  public
     * @return  void
     */
    function SendRecoverKey()
    {
        if ($GLOBALS['app']->Registry->Get('/gadgets/Users/password_recovery') !== 'true') {
            return parent::_404();
        }

        $request =& Jaws_Request::getInstance();
        $post = $request->get(array('username', 'email', 'captcha', 'captchaKey'),
                              'post');
        $error  = '';

        require_once JAWS_PATH . 'include/Jaws/Header.php';

        $GLOBALS['app']->Registry->LoadFile('Policy');
        $_captcha = $GLOBALS['app']->Registry->Get('/gadgets/Policy/captcha');
        if ($_captcha != 'DISABLED') {
            require_once JAWS_PATH . 'gadgets/Policy/captchas/' . $_captcha . '.php';
            $captcha = new $_captcha();
            if (!$captcha->Check()) {
                $GLOBALS['app']->Session->PushSimpleResponse(_t('GLOBAL_CAPTCHA_ERROR_DOES_NOT_MATCH'), 'Users.ForgotPassword');
                Jaws_Header::Location($this->GetURLFor('ForgotPassword'));
            }
        }

        $model  = $GLOBALS['app']->LoadGadget('Users', 'Model');
		$result = $model->SendRecoveryKey($post['username'], $post['email']);
		if (Jaws_Error::IsError($result)) {
            $GLOBALS['app']->Session->PushSimpleResponse($result->GetMessage(), 'Users.ForgotPassword');
        } else {
            $GLOBALS['app']->Session->PushSimpleResponse(_t('USERS_FORGOT_MAIL_SENT'), 'Users.ForgotPassword');
        }
        Jaws_Header::Location($this->GetURLFor('ForgotPassword'));
    }

    /**
     * Check if given recovery key really exists, it it exists it generates
     * a new password (pronounceable) and sends it to user mailbox
     *
     * @access  public
     * @return  string  XHTML template
     */
    function ChangePassword()
    {
        if ($GLOBALS['app']->Registry->Get('/gadgets/Users/password_recovery') !== 'true') {
            return parent::_404();
        }

        $request =& Jaws_Request::getInstance();
        $key     = $request->get('key', 'get');

        $model  = $GLOBALS['app']->LoadGadget('Users', 'Model');
        $result = $model->ChangePassword($key);

        if (Jaws_Error::IsError($result)) {
            return '<div id="forgetchanged">'.$result->GetMessage().'</div>';
        }
        return '<div id="forgetchanged">'._t('USERS_FORGOT_PASSWORD_CHANGED').'</div>';
    }

    /**
     * User password recovery.
     *
     * @access  public
     * @return  string  XHTML template
     */
    function ForgotPassword()
    {
        if ($GLOBALS['app']->Registry->Get('/gadgets/Users/password_recovery') !== 'true') {
            return parent::_404();
        }

        // Load the template
        $tpl = new Jaws_Template('gadgets/Users/templates/');
        $tpl->Load('ForgotPassword.html');
        $tpl->SetBlock('forgot');
        $tpl->SetVariable('base_script', BASE_SCRIPT);
        $tpl->SetVariable('title', _t('USERS_FORGOT_REMEMBER'));
        $tpl->SetVariable('username', _t('USERS_USERS_USERNAME'));
        $tpl->SetVariable('email', _t('GLOBAL_EMAIL'));
        $tpl->SetVariable('remember', _t('USERS_FORGOT_REMEMBER'));

        if ($response = $GLOBALS['app']->Session->PopSimpleResponse()) {
            $tpl->SetBlock('forgot/response');
            $tpl->SetVariable('msg', $response);
            $tpl->ParseBlock('forgot/response');
        }
        
		$GLOBALS['app']->Registry->LoadFile('Policy');
        $_captcha = $GLOBALS['app']->Registry->Get('/gadgets/Policy/captcha');
        if ($_captcha != 'DISABLED') {
            require_once JAWS_PATH . 'gadgets/Policy/captchas/' . $_captcha . '.php';
            $captcha = new $_captcha();
            $captchaRes = $captcha->Get();
            $tpl->SetBlock('forgot/captcha');
            $tpl->SetVariable('lbl_captcha', _t('GLOBAL_CAPTCHA_CODE'));
            $tpl->SetVariable('captcha', $captchaRes['captcha']->Get());
            if (!empty($captchaRes['entry'])) {
                $tpl->SetVariable('captchavalue', $captchaRes['entry']->Get());
            }
            $tpl->SetVariable('captcha_msg', _t('GLOBAL_CAPTCHA_CODE_DESC'));
            $tpl->ParseBlock('forgot/captcha');
        }

        if ($response = $GLOBALS['app']->Session->PopSimpleResponse('Users.ForgotPassword')) {
            $tpl->SetBlock('forgot/response');
            $tpl->SetVariable('msg', $response);
            $tpl->ParseBlock('forgot/response');
        }
        $tpl->ParseBlock('forgot');
        return $tpl->Get();
    }

    /**
     * Prepares a simple form to update user's data (nickname, email, password)
     *
     * @access  public
     * @return  string  XHTML template of a form
     */
    function Account()
    {
		require_once JAWS_PATH . 'include/Jaws/Header.php';
        $username = $GLOBALS['app']->Session->GetAttribute('username');
        if (empty($username) || $username == 'anonymous') {
            $GLOBALS['app']->Session->PushSimpleResponse('You must log-in to continue.');
			Jaws_Header::Location($GLOBALS['app']->GetSiteURL() . '/index.php?gadget=Users&action=DefaultAction&redirect_to='.urlencode($GLOBALS['app']->Map->GetURLFor('Users', 'Account')));
        }

        $GLOBALS['app']->Session->CheckPermission('Users', 'EditAccountInformation');

		$this->AjaxMe('client_script.js');
		$GLOBALS['app']->Layout->AddScriptLink('libraries/js/admin.js');
		$GLOBALS['app']->Layout->AddHeadLink('gadgets/Users/resources/style.css', 'stylesheet', 'text/css');
        
		require_once JAWS_PATH . 'include/Jaws/User.php';
		$jUser = new Jaws_User;
		$account  = $jUser->GetUserInfoById($GLOBALS['app']->Session->GetAttribute('user_id'), true, true);
		/*
		$account = $GLOBALS['app']->Session->PopSimpleResponse('Users.Account.Data');
        if (empty($account)) {
        }
		*/

        $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$request 	=& Jaws_Request::getInstance();
        $target     = $request->get('target', 'get');
        $highlight  = $request->get('highlight', 'get');
		$highlight  = $xss->parse($highlight);
        $style     	= $request->get('style', 'get');
		$style 		= $xss->parse($style);
		$selected = " selected=\"selected\"";
		if (!empty($style)) {
			$GLOBALS['app']->Layout->AddHeadLink($style, 'stylesheet', 'text/css');
		}
		$redirectTo = $request->get('redirect_to', 'post');
		if (empty($redirectTo)) {
			$redirectTo = $request->get('redirect_to', 'get');
			$redirectTo = urlencode($xss->parse($redirectTo));
		} else {
			$redirectTo = urldecode($xss->parse($redirectTo));
            Jaws_Header::Location($redirectTo);
		}

        // Load the template
        $tpl = new Jaws_Template('gadgets/Users/templates/');
        $tpl->Load('Account.html');
        $tpl->SetBlock('account');
        
		$tpl->SetVariable('title', _t('USERS_ACCOUNT_INFO'));
        $tpl->SetVariable('base_script', BASE_SCRIPT);
        $tpl->SetVariable('update', _t('USERS_USERS_ACCOUNT_UPDATE'));

        $tpl->SetVariable('lbl_email',         _t('GLOBAL_EMAIL'));
        $tpl->SetVariable('email',             $xss->filter($account['email']));
        $tpl->SetVariable('lbl_nickname',      _t('USERS_USERS_NICKNAME'));
        $tpl->SetVariable('nickname',          $xss->filter($account['nickname']));
        $tpl->SetVariable('lbl_password',      _t('USERS_USERS_PASSWORD'));
        $tpl->SetVariable('emptypassword',     _t('USERS_NOCHANGE_PASSWORD'));
        $tpl->SetVariable('lbl_checkpassword', _t('USERS_USERS_PASSWORD_VERIFY'));

        if ($response = $GLOBALS['app']->Session->PopSimpleResponse('Users.Account.Response')) {
            $tpl->SetBlock('account/response');
            $tpl->SetVariable('msg', $response);
            $tpl->ParseBlock('account/response');
        }
        $tpl->ParseBlock('account');
        return $tpl->Get();
    }

    /**
     * Advanced user information.
     *
     * @access  public
     * @return  void
     */
    function UpdateAccount()
    {
        $GLOBALS['app']->Session->CheckPermission('Users', 'EditAccountInformation');

        require_once JAWS_PATH . 'include/Jaws/Header.php';

        $username = $GLOBALS['app']->Session->GetAttribute('username');
        if (empty($username)) {
            Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'LoginForm'), true);
        }

        $request =& Jaws_Request::getInstance();
        $post = $request->get(array('email', 'nickname', 'password', 'password_check'), 'post');

        if ($post['password'] === $post['password_check']) {
            $model  = $GLOBALS['app']->LoadGadget('Users', 'Model');
            $result = $model->UpdateAccount($GLOBALS['app']->Session->GetAttribute('user_id'),
                                            $username,
                                            $post['email'],
                                            $post['nickname'],
                                            $post['password']);
            if (!Jaws_Error::IsError($result)) {
                $GLOBALS['app']->Session->PushSimpleResponse(_t('USERS_MYACCOUNT_UPDATED'), 'Users.Account.Response');
            } else {
                $GLOBALS['app']->Session->PushSimpleResponse($result->GetMessage(), 'Users.Account.Response');
            }
        } else {
            $GLOBALS['app']->Session->PushSimpleResponse(_t('USERS_USERS_PASSWORDS_DONT_MATCH'), 'Users.Account.Response');
        }

        // unset unnecessary account data
        unset($post['password'], $post['password_check']);
        $GLOBALS['app']->Session->PushSimpleResponse($post, 'Users.Account.Data');
        Jaws_Header::Location($this->GetURLFor('Account'));
    }

    /**
     * Prepares a simple form to update user's data (name, email, password)
     *
     * @access  public
     * @return  string  XHTML template of a form
     */
    function Profile()
    {
		require_once JAWS_PATH . 'include/Jaws/Header.php';
        $username = $GLOBALS['app']->Session->GetAttribute('username');
        if (empty($username)) {
            Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'LoginForm'), true);
        }

        $GLOBALS['app']->Session->CheckPermission('Users', 'EditAccountProfile');
		$this->AjaxMe('client_script.js');
		$GLOBALS['app']->Layout->AddScriptLink('libraries/js/admin.js');
		$GLOBALS['app']->Layout->AddHeadLink('gadgets/Users/resources/style.css', 'stylesheet', 'text/css');

        $profile = $GLOBALS['app']->Session->PopSimpleResponse('Users.Profile.Data');
		require_once JAWS_PATH . 'include/Jaws/User.php';
		$jUser = new Jaws_User;
        if (empty($profile)) {
            $profile  = $jUser->GetUserInfoById($GLOBALS['app']->Session->GetAttribute('user_id'), true, true, true, true);
        }

        $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$request 	=& Jaws_Request::getInstance();
        $target     = $request->get('target', 'get');
        $highlight  = $request->get('highlight', 'get');
		$highlight  = $xss->parse($highlight);
        $style     	= $request->get('style', 'get');
		$style 		= $xss->parse($style);
		$selected = " selected=\"selected\"";
		if (!empty($style)) {
			$GLOBALS['app']->Layout->AddHeadLink($style, 'stylesheet', 'text/css');
		}
		
		$redirectTo = $request->get('redirect_to', 'post');
		if (empty($redirectTo)) {
			$redirectTo = $request->get('redirect_to', 'get');
		}
		if (!empty($redirectTo)) {
			$redirectTo = str_replace('&amp;', '&', $redirectTo);
			//$redirectTo = urldecode($redirectTo);
		}
        
		// Load the template
        $tpl = new Jaws_Template('gadgets/Users/templates/');
        $tpl->Load('Profile.html');
        $tpl->SetBlock('profile');
        $form_target = '';
		if ($target == 'top') {
			$form_target = ' target="_top"';
		}
        $tpl->SetVariable('form_target', $form_target);
        $form_style = '';
		if (!empty($highlight)) {
			$highlights = explode(',',$highlight);
			$form_style .= '<style>';
			foreach ($highlights as $hlt) {
				if (isset($profile[$hlt]) && empty($profile[$hlt])) {
					$form_style .= '#p_'.str_replace(array('<','>'),'',$hlt).' {background: none repeat scroll 0% 0% #FFC0CB; padding: 2px 5px;}'."\n";
				}
			}
			$form_style .= '</style>';
			$GLOBALS['app']->Layout->AddHeadOther($form_style);
		}
        $tpl->SetVariable('title', _t('USERS_PERSONAL_INFO'));
        $tpl->SetVariable('base_script', BASE_SCRIPT);
        $tpl->SetVariable('update', _t('USERS_USERS_ACCOUNT_UPDATE'));
        $tpl->SetVariable('redirect_to', $redirectTo);
        $tpl->SetVariable('lbl_fname',         _t('USERS_USERS_FIRSTNAME'));
        $tpl->SetVariable('fname',             $xss->filter($profile['fname']));
        $tpl->SetVariable('lbl_lname',         _t('USERS_USERS_LASTNAME'));
        $tpl->SetVariable('lname',             $xss->filter($profile['lname']));
        $tpl->SetVariable('lbl_gender',        _t('USERS_USERS_GENDER'));
        $tpl->SetVariable('gender_male',       _t('USERS_USERS_MALE'));
        $tpl->SetVariable('gender_female',     _t('USERS_USERS_FEMALE'));
        if (empty($profile['gender'])) {
            $tpl->SetVariable('male_selected',   'selected="selected"');
        } else {
            $tpl->SetVariable('female_selected', 'selected="selected"');
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
        $dob_year->setID('profile_dob_year');
        $dob_year->setStyle('max-width: 100px;');
        $dob_year->AddOption("Year", '');
		$current_year = $date->Format($GLOBALS['db']->Date(), 'Y');
        for($i=(int)$current_year;$i>1850;$i--) {
            $dob_year->AddOption($i, $i);
        }
        $dob_year->setDefault($dob[0]);
        
		$dob_month =& Piwi::CreateWidget('Combo', 'dob_month');
        $dob_month->setID('profile_dob_month');
        $dob_month->setStyle('max-width: 100px;');
        $dob_month->AddOption("Month", '');
        for($i=1;$i<13;$i++) {
			$dob_month->AddOption($date->Format(mktime(0, 0, 0, $i+1, 0, 0), 'MN'), $i);
        }
        $dob_month->setDefault($dob[1]);
		
		$dob_day =& Piwi::CreateWidget('Combo', 'dob_day');
        $dob_day->setID('profile_dob_day');
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

        $tpl->SetVariable('lbl_url',           _t('GLOBAL_URL'));
        $tpl->SetVariable('url',               $xss->filter($profile['url']));

        $tpl->SetVariable('lbl_company', _t('USERS_USERS_COMPANY'));
        $tpl->SetVariable('company', $xss->filter($profile['company']));
        
		$tpl->SetVariable('lbl_company_type', _t('USERS_USERS_COMPANY_TYPE'));
		$company_type_options = '';
		$company_type_options .= "<option value=\"\"".(empty($profile['company_type']) ? $selected : '').">Select business type...</option>"; 
		$company_type_options .= "<option".($profile['company_type']==_t('USERS_USERS_COMPANY_TYPE_RETAIL') ? $selected : '').">"._t('USERS_USERS_COMPANY_TYPE_RETAIL')."</option>"; 
		$company_type_options .= "<option".($profile['company_type']==_t('USERS_USERS_COMPANY_TYPE_RESTAURANT') ? $selected : '').">"._t('USERS_USERS_COMPANY_TYPE_RESTAURANT')."</option>"; 
		$company_type_options .= "<option".($profile['company_type']==_t('USERS_USERS_COMPANY_TYPE_SERVICES') ? $selected : '').">"._t('USERS_USERS_COMPANY_TYPE_SERVICES')."</option>"; 
		$company_type_options .= "<option".($profile['company_type']==_t('USERS_USERS_COMPANY_TYPE_MEDICAL') ? $selected : '').">"._t('USERS_USERS_COMPANY_TYPE_MEDICAL')."</option>"; 
		$company_type_options .= "<option".($profile['company_type']==_t('USERS_USERS_COMPANY_TYPE_MEDIA') ? $selected : '').">"._t('USERS_USERS_COMPANY_TYPE_MEDIA')."</option>"; 
		$company_type_options .= "<option".($profile['company_type']==_t('USERS_USERS_COMPANY_TYPE_SALON') ? $selected : '').">"._t('USERS_USERS_COMPANY_TYPE_SALON')."</option>"; 
		$company_type_options .= "<option".($profile['company_type']==_t('USERS_USERS_COMPANY_TYPE_HEALTH') ? $selected : '').">"._t('USERS_USERS_COMPANY_TYPE_HEALTH')."</option>"; 
		$company_type_options .= "<option".($profile['company_type']==_t('USERS_USERS_COMPANY_TYPE_HOMEGARDEN') ? $selected : '').">"._t('USERS_USERS_COMPANY_TYPE_HOMEGARDEN')."</option>"; 
		$company_type_options .= "<option".($profile['company_type']==_t('USERS_USERS_COMPANY_TYPE_ENTERTAINMENT') ? $selected : '').">"._t('USERS_USERS_COMPANY_TYPE_ENTERTAINMENT')."</option>"; 
		$company_type_options .= "<option".($profile['company_type']==_t('USERS_USERS_COMPANY_TYPE_FINANCIAL') ? $selected : '').">"._t('USERS_USERS_COMPANY_TYPE_FINANCIAL')."</option>"; 
		$company_type_options .= "<option".($profile['company_type']==_t('USERS_USERS_COMPANY_TYPE_ARTSCULTURE') ? $selected : '').">"._t('USERS_USERS_COMPANY_TYPE_ARTSCULTURE')."</option>"; 
		$company_type_options .= "<option".($profile['company_type']==_t('USERS_USERS_COMPANY_TYPE_LODGING') ? $selected : '').">"._t('USERS_USERS_COMPANY_TYPE_LODGING')."</option>"; 
		$company_type_options .= "<option".($profile['company_type']==_t('USERS_USERS_COMPANY_TYPE_MANUFACTURING') ? $selected : '').">"._t('USERS_USERS_COMPANY_TYPE_MANUFACTURING')."</option>"; 
		$company_type_options .= "<option".($profile['company_type']==_t('USERS_USERS_COMPANY_TYPE_GROCERY') ? $selected : '').">"._t('USERS_USERS_COMPANY_TYPE_GROCERY')."</option>"; 
		$company_type_options .= "<option".($profile['company_type']==_t('USERS_USERS_COMPANY_TYPE_FARM') ? $selected : '').">"._t('USERS_USERS_COMPANY_TYPE_FARM')."</option>"; 
		$company_type_options .= "<option".($profile['company_type']==_t('USERS_USERS_COMPANY_TYPE_NONPROFIT') ? $selected : '').">"._t('USERS_USERS_COMPANY_TYPE_NONPROFIT')."</option>"; 
		$tpl->SetVariable('company_type_options', $company_type_options);
		
		$main_image_src = $jUser->GetAvatar($profile['username'],$profile['email']);
		$show_delete_image = false;
		require_once JAWS_PATH . 'include/Jaws/Gravatar.php';
		if ($main_image_src != Jaws_Gravatar::GetGravatar($profile['email'])) {
			$show_delete_image = true;
		}
		$image_preview = '&nbsp;';

		if (!empty($main_image_src)) { 
			$image_preview = "<img id=\"logo_preview\" border=\"0\" src=\"".$main_image_src."\" width=\"80\"".(strtolower(substr($main_image_src, -3)) == 'gif' || strtolower(substr($main_image_src, -3)) == 'png' || strtolower(substr($main_image_src, -3)) == 'bmp' ? ' height="80"' : '')." style=\"padding: 5px;\"><br />";
			if ($show_delete_image === true) {
				$image_preview .= "<a id=\"logo_delete\" href=\"javascript:void(0);\" onclick=\"if (confirm('Are you sure you want to delete this logo?')){document.getElementById('logo').value = ''; document.getElementById('logo_preview').style.display = 'none'; document.getElementById('logo_delete').style.display = 'none'; document.forms['profileBox'].submit();}\">Delete</a>";
			}
		}
		$imageHidden =& Piwi::CreateWidget('HiddenEntry', 'logo', ($show_delete_image === true ? $main_image_src : ''));
		$imageScript = "<script type=\"text/javascript\">Event.observe(window, \"load\",function(){addFileToPost('Users', 'NULL', 'NULL', 'main_image', 'logo', 1, 310, 34);});</script>";
		$imageButton = "<div id=\"logo_actions\"><div style=\"text-align: center; float: left; width: 145px;\">".$image_preview."</div><div style=\"float: left;\">&nbsp;</div><div style=\"float: none; clear: both;\">&nbsp;</div></div>";
		$logo_content .= "<div style=\"float: left; width: 145px;\"><p><label for=\"logo\"><nobr>Logo: </nobr></label></p></div><div id=\"main_image\" style=\"float: left;\"></div>".$imageHidden->Get().$imageScript."<div style=\"float: none; clear: both;\">&nbsp;</div>".$imageButton;
        $tpl->SetVariable('lbl_logo', _t('USERS_USERS_LOGO'));
        $tpl->SetVariable('logo', $logo_content);
        
		$tpl->SetVariable('lbl_address', _t('USERS_USERS_ADDRESS'));
        $tpl->SetVariable('address', $xss->filter($profile['address']));
        
		$tpl->SetVariable('lbl_address2', _t('USERS_USERS_ADDRESS2'));
        $tpl->SetVariable('address2', $xss->filter($profile['address2']));
        
		$tpl->SetVariable('lbl_city', _t('USERS_USERS_CITY'));
        $tpl->SetVariable('city', $xss->filter($profile['city']));
        
		//FIXME: Add predefined data from [[country]] db table
        $tpl->SetVariable('lbl_country', _t('USERS_USERS_COUNTRY'));
		$options = '';
		/*
		if ($profile['country']=="Argentina") {
			$options .= "<option value=\"Argentina\"".$selected.">Argentina</option>";
		} else {
			$options .= "<option value=\"Argentina\">Argentina</option>";		
		}
		if ($profile['country']=="Asia") {
			$options .= "<option value=\"Asia\"".$selected.">Asia Pacific</option>";
		} else {
			$options .= "<option value=\"Asia\">Asia Pacific</option>";
		}
		if ($profile['country']=="Australia") {
			$options .= "<option value=\"Australia\"".$selected.">Australia</option>";
		} else {
			$options .= "<option value=\"Australia\">Australia</option>";
		}
		if ($profile['country']=="Austria") {
			$options .= "<option value=\"Austria\"".$selected.">Austria</option>";
		} else {
			$options .= "<option value=\"Austria\">Austria</option>";
		}
		if ($profile['country']=="Belgium") {
			$options .= "<option value=\"Belgium\"".$selected.">Belgium</option>";
		} else {
			$options .= "<option value=\"Belgium\">Belgium</option>";
		}
		if ($profile['country']=="Bolivia") {
			$options .= "<option value=\"Bolivia\"".$selected.">Bolivia</option>";
		} else {
			$options .= "<option value=\"Bolivia\">Bolivia</option>";
		}
		if ($profile['country']=="Bosnia and Herzegovina") {
			$options .= "<option value=\"Bosnia and Herzegovina\"".$selected.">Bosnia and Herzegovina</option>";
		} else {
			$options .= "<option value=\"Bosnia and Herzegovina\">Bosnia and Herzegovina</option>";
		}
		if ($profile['country']=="Brazil") {
			$options .= "<option value=\"Brazil\"".$selected.">Brazil</option>";
		} else {
			$options .= "<option value=\"Brazil\">Brazil</option>";
		}
		if ($profile['country']=="Bulgaria") {
			$options .= "<option value=\"Bulgaria\"".$selected.">Bulgaria</option>";
		} else {
			$options .= "<option value=\"Bulgaria\">Bulgaria</option>";
		}
		if ($profile['country']=="Canada") {
			$options .= "<option value=\"Canada\"".$selected.">Canada</option>";
		} else {
			$options .= "<option value=\"Canada\">Canada</option>";
		}
		if ($profile['country']=="Caribbean") {
			$options .= "<option value=\"Caribbean\"".$selected.">Caribbean</option>";
		} else {
			$options .= "<option value=\"Caribbean\">Caribbean</option>";
		}
		if ($profile['country']=="Chile") {
			$options .= "<option value=\"Chile\"".$selected.">Chile</option>";
		} else {
			$options .= "<option value=\"Chile\">Chile</option>";
		}
		if ($profile['country']=="China") {
			$options .= "<option value=\"China\"".$selected.">China</option>";
		} else {
			$options .= "<option value=\"China\">China</option>";
		}
		if ($profile['country']=="Colombia") {
			$options .= "<option value=\"Colombia\"".$selected.">Colombia</option>";
		} else {
			$options .= "<option value=\"Colombia\">Colombia</option>";
		}
		if ($profile['country']=="Costa Rica") {
			$options .= "<option value=\"Costa Rica\"".$selected.">Costa Rica</option>";
		} else {
			$options .= "<option value=\"Costa Rica\">Costa Rica</option>";
		}
		if ($profile['country']=="Croatia") {
			$options .= "<option value=\"Croatia\"".$selected.">Croatia</option>";
		} else {
			$options .= "<option value=\"Croatia\">Croatia</option>";
		}
		if ($profile['country']=="Czech Republic") {
			$options .= "<option value=\"Czech Republic\"".$selected.">Czech Republic</option>";
		} else {
			$options .= "<option value=\"Czech Republic\">Czech Republic</option>";
		}
		if ($profile['country']=="Denmark") {
			$options .= "<option value=\"Denmark\"".$selected.">Denmark</option>";
		} else {
			$options .= "<option value=\"Denmark\">Denmark</option>";
		}
		if ($profile['country']=="Dominican Republic") {
			$options .= "<option value=\"Dominican Republic\"".$selected.">Dominican Republic</option>";
		} else {
			$options .= "<option value=\"Dominican Republic\">Dominican Republic</option>";
		}
		if ($profile['country']=="Ecuador") {
			$options .= "<option value=\"Ecuador\"".$selected.">Ecuador</option>";
		} else {
			$options .= "<option value=\"Ecuador\">Ecuador</option>";
		}
		if ($profile['country']=="Egypt") {
			$options .= "<option value=\"Egypt\"".$selected.">Egypt</option>";
		} else {
			$options .= "<option value=\"Egypt\">Egypt</option>";
		}
		if ($profile['country']=="El Salvador") {
			$options .= "<option value=\"El Salvador\"".$selected.">El Salvador</option>";
		} else {
			$options .= "<option value=\"El Salvador\">El Salvador</option>";
		}
		if ($profile['country']=="Estonia") {
			$options .= "<option value=\"Estonia\"".$selected.">Estonia</option>";
		} else {
			$options .= "<option value=\"Estonia\">Estonia</option>";
		}
		if ($profile['country']=="Finland") {
			$options .= "<option value=\"Finland\"".$selected.">Finland</option>";
		} else {
			$options .= "<option value=\"Finland\">Finland</option>";
		}
		if ($profile['country']=="France") {
			$options .= "<option value=\"France\"".$selected.">France</option>";
		} else {
			$options .= "<option value=\"France\">France</option>";
		}
		if ($profile['country']=="Germany") {
			$options .= "<option value=\"Germany\"".$selected.">Germany</option>";
		} else {
			$options .= "<option value=\"Germany\">Germany</option>";
		}
		if ($profile['country']=="Greece") {
			$options .= "<option value=\"Greece\"".$selected.">Greece</option>";
		} else {
			$options .= "<option value=\"Greece\">Greece</option>";
		}
		if ($profile['country']=="Guatemala") {
			$options .= "<option value=\"Guatemala\"".$selected.">Guatemala</option>";
		} else {
			$options .= "<option value=\"Guatemala\">Guatemala</option>";
		}
		if ($profile['country']=="Honduras") {
			$options .= "<option value=\"Honduras\"".$selected.">Honduras</option>";
		} else {
			$options .= "<option value=\"Honduras\">Honduras</option>";
		}
		if ($profile['country']=="Hong Kong") {
			$options .= "<option value=\"Hong Kong\"".$selected.">Hong Kong</option>";
		} else {
			$options .= "<option value=\"Hong Kong\">Hong Kong</option>";
		}
		if ($profile['country']=="Hungary") {
			$options .= "<option value=\"Hungary\"".$selected.">Hungary</option>";
		} else {
			$options .= "<option value=\"Hungary\">Hungary</option>";
		}
		if ($profile['country']=="Iceland") {
			$options .= "<option value=\"Iceland\"".$selected.">Iceland</option>";
		} else {
			$options .= "<option value=\"Iceland\">Iceland</option>";
		}
		if ($profile['country']=="India") {
			$options .= "<option value=\"India\"".$selected.">India</option>";
		} else {
			$options .= "<option value=\"India\">India</option>";
		}
		if ($profile['country']=="Indonesia") {
			$options .= "<option value=\"Indonesia\"".$selected.">Indonesia</option>";
		} else {
			$options .= "<option value=\"Indonesia\">Indonesia</option>";
		}
		if ($profile['country']=="Ireland") {
			$options .= "<option value=\"Ireland\"".$selected.">Ireland</option>";
		} else {
			$options .= "<option value=\"Ireland\">Ireland</option>";
		}
		if ($profile['country']=="Israel") {
			$options .= "<option value=\"Israel\"".$selected.">Israel</option>";
		} else {
			$options .= "<option value=\"Israel\">Israel</option>";
		}
		if ($profile['country']=="Italy") {
			$options .= "<option value=\"Italy\"".$selected.">Italy</option>";
		} else {
			$options .= "<option value=\"Italy\">Italy</option>";
		}
		if ($profile['country']=="Jamaica") {
			$options .= "<option value=\"Jamaica\"".$selected.">Jamaica</option>";
		} else {
			$options .= "<option value=\"Jamaica\">Jamaica</option>";
		}
		if ($profile['country']=="Japan") {
			$options .= "<option value=\"Japan\"".$selected.">Japan</option>";
		} else {
			$options .= "<option value=\"Japan\">Japan</option>";
		}
		if ($profile['country']=="Jordan") {
			$options .= "<option value=\"Jordan\"".$selected.">Jordan</option>";
		} else {
			$options .= "<option value=\"Jordan\">Jordan</option>";
		}
		if ($profile['country']=="Korea") {
			$options .= "<option value=\"Korea\"".$selected.">Korea</option>";
		} else {
			$options .= "<option value=\"Korea\">Korea</option>";
		}
		if ($profile['country']=="Kazakhstan") {
			$options .= "<option value=\"Kazakhstan\"".$selected.">Kazakhstan</option>";
		} else {
			$options .= "<option value=\"Kazakhstan\">Kazakhstan</option>";
		}
		if ($profile['country']=="Latin America") {
			$options .= "<option value=\"Latin America\"".$selected.">Latin America</option>";
		} else {
			$options .= "<option value=\"Latin America\">Latin America</option>";
		}
		if ($profile['country']=="Latvia") {
			$options .= "<option value=\"Latvia\"".$selected.">Latvia</option>";
		} else {
			$options .= "<option value=\"Latvia\">Latvia</option>";
		}
		if ($profile['country']=="Lithuania") {
			$options .= "<option value=\"Lithuania\"".$selected.">Lithuania</option>";
		} else {
			$options .= "<option value=\"Lithuania\">Lithuania</option>";
		}
		if ($profile['country']=="Luxembourg") {
			$options .= "<option value=\"Luxembourg\"".$selected.">Luxembourg</option>";
		} else {
			$options .= "<option value=\"Luxembourg\">Luxembourg</option>";
		}
		if ($profile['country']=="Macedonia") {
			$options .= "<option value=\"Macedonia\"".$selected.">Macedonia</option>";
		} else {
			$options .= "<option value=\"Macedonia\">Macedonia</option>";
		}
		if ($profile['country']=="Malaysia") {
			$options .= "<option value=\"Malaysia\"".$selected.">Malaysia</option>";
		} else {
			$options .= "<option value=\"Malaysia\">Malaysia</option>";
		}
		if ($profile['country']=="Mexico") {
			$options .= "<option value=\"Mexico\"".$selected.">Mexico</option>";
		} else {
			$options .= "<option value=\"Mexico\">Mexico</option>";
		}
		if ($profile['country']=="Middle East") {
			$options .= "<option value=\"Middle East\"".$selected.">Middle East</option>";
		} else {
			$options .= "<option value=\"Middle East\">Middle East</option>";
		}
		if ($profile['country']=="Moldova") {
			$options .= "<option value=\"Moldova\"".$selected.">Moldova</option>";
		} else {
			$options .= "<option value=\"Moldova\">Moldova</option>";
		}
		if ($profile['country']=="Netherlands") {
			$options .= "<option value=\"Netherlands\"".$selected.">Netherlands</option>";
		} else {
			$options .= "<option value=\"Netherlands\">Netherlands</option>";
		}
		if ($profile['country']=="New Zealand") {
			$options .= "<option value=\"New Zealand\"".$selected.">New Zealand</option>";
		} else {
			$options .= "<option value=\"New Zealand\">New Zealand</option>";
		}
		if ($profile['country']=="North Africa") {
			$options .= "<option value=\"North Africa\"".$selected.">North Africa</option>";
		} else {
			$options .= "<option value=\"North Africa\">North Africa</option>";
		}
		if ($profile['country']=="Norway") {
			$options .= "<option value=\"Norway\"".$selected.">Norway</option>";
		} else {
			$options .= "<option value=\"Norway\">Norway</option>";
		}
		if ($profile['country']=="Panama") {
			$options .= "<option value=\"Panama\"".$selected.">Panama</option>";
		} else {
			$options .= "<option value=\"Panama\">Panama</option>";
		}
		if ($profile['country']=="Pakistan") {
			$options .= "<option value=\"Pakistan\"".$selected.">Pakistan</option>";
		} else {
			$options .= "<option value=\"Pakistan\">Pakistan</option>";
		}
		if ($profile['country']=="Paraguay") {
			$options .= "<option value=\"Paraguay\"".$selected.">Paraguay</option>";
		} else {
			$options .= "<option value=\"Paraguay\">Paraguay</option>";
		}
		if ($profile['country']=="Peru") {
			$options .= "<option value=\"Peru\"".$selected.">Peru</option>";
		} else {
			$options .= "<option value=\"Peru\">Peru</option>";
		}
		if ($profile['country']=="Philippines") {
			$options .= "<option value=\"Philippines\"".$selected.">Philippines</option>";
		} else {
			$options .= "<option value=\"Philippines\">Philippines</option>";
		}
		if ($profile['country']=="Poland") {
			$options .= "<option value=\"Poland\"".$selected.">Poland</option>";
		} else {
			$options .= "<option value=\"Poland\">Poland</option>";
		}
		if ($profile['country']=="Portugal") {
			$options .= "<option value=\"Portugal\"".$selected.">Portugal</option>";
		} else {
			$options .= "<option value=\"Portugal\">Portugal</option>";
		}
		if ($profile['country']=="Puerto Rico") {
			$options .= "<option value=\"Puerto Rico\"".$selected.">Puerto Rico</option>";
		} else {
			$options .= "<option value=\"Puerto Rico\">Puerto Rico</option>";
		}
		if ($profile['country']=="Romania") {
			$options .= "<option value=\"Romania\"".$selected.">Romania</option>";
		} else {
			$options .= "<option value=\"Romania\">Romania</option>";
		}
		if ($profile['country']=="Russia") {
			$options .= "<option value=\"Russia\"".$selected.">Russia</option>";
		} else {
			$options .= "<option value=\"Russia\">Russia</option>";
		}
		if ($profile['country']=="Saudi Arabia") {
			$options .= "<option value=\"Saudi Arabia\"".$selected.">Saudi Arabia</option>";
		} else {
			$options .= "<option value=\"Saudi Arabia\">Saudi Arabia</option>";
		}
		if ($profile['country']=="Serbia and Montenegro") {
			$options .= "<option value=\"Serbia and Montenegro\"".$selected.">Serbia and Montenegro</option>";
		} else {
			$options .= "<option value=\"Serbia and Montenegro\">Serbia and Montenegro</option>";
		}
		if ($profile['country']=="Singapore") {
			$options .= "<option value=\"Singapore\"".$selected.">Singapore</option>";
		} else {
			$options .= "<option value=\"Singapore\">Singapore</option>";
		}
		if ($profile['country']=="Slovakia") {
			$options .= "<option value=\"Slovakia\"".$selected.">Slovakia</option>";
		} else {
			$options .= "<option value=\"Slovakia\">Slovakia</option>";
		}
		if ($profile['country']=="Slovenia") {
			$options .= "<option value=\"Slovenia\"".$selected.">Slovenia</option>";
		} else {
			$options .= "<option value=\"Slovenia\">Slovenia</option>";
		}
		if ($profile['country']=="South Africa") {
			$options .= "<option value=\"South Africa\"".$selected.">South Africa</option>";
		} else {
			$options .= "<option value=\"South Africa\">South Africa</option>";
		}
		if ($profile['country']=="Spain") {
			$options .= "<option value=\"Spain\"".$selected.">Spain</option>";
		} else {
			$options .= "<option value=\"Spain\">Spain</option>";
		}
		if ($profile['country']=="Sweden") {
			$options .= "<option value=\"Sweden\"".$selected.">Sweden</option>";
		} else {
			$options .= "<option value=\"Sweden\">Sweden</option>";
		}
		if ($profile['country']=="Switzerland") {
			$options .= "<option value=\"Switzerland\"".$selected.">Switzerland</option>";
		} else {
			$options .= "<option value=\"Switzerland\">Switzerland</option>";
		}
		if ($profile['country']=="Taiwan") {
			$options .= "<option value=\"Taiwan\"".$selected.">Taiwan</option>";
		} else {
			$options .= "<option value=\"Taiwan\">Taiwan</option>";
		}
		if ($profile['country']=="Thailand") {
			$options .= "<option value=\"Thailand\"".$selected.">Thailand</option>";
		} else {
			$options .= "<option value=\"Thailand\">Thailand</option>";
		}
		if ($profile['country']=="Trinidad and Tobago") {
			$options .= "<option value=\"Trinidad and Tobago\"".$selected.">Trinidad and Tobago</option>";
		} else {
			$options .= "<option value=\"Trinidad and Tobago\">Trinidad and Tobago</option>";
		}
		if ($profile['country']=="Tunisia") {
			$options .= "<option value=\"Tunisia\"".$selected.">Tunisia</option>";
		} else {
			$options .= "<option value=\"Tunisia\">Tunisia</option>";
		}
		if ($profile['country']=="Turkey") {
			$options .= "<option value=\"Turkey\"".$selected.">Turkey</option>";
		} else {
			$options .= "<option value=\"Turkey\">Turkey</option>";
		}
		if ($profile['country']=="United Arab Emirates") {
			$options .= "<option value=\"United Arab Emirates\"".$selected.">United Arab Emirates</option>";
		} else {
			$options .= "<option value=\"United Arab Emirates\">United Arab Emirates</option>";
		}
		if ($profile['country']=="Ukraine") {
			$options .= "<option value=\"Ukraine\"".$selected.">Ukraine</option>";
		} else {
			$options .= "<option value=\"Ukraine\">Ukraine</option>";
		}
		if ($profile['country']=="United Kingdom") {
			$options .= "<option value=\"United Kingdom\"".$selected.">United Kingdom</option>";
		} else {
			$options .= "<option value=\"United Kingdom\">United Kingdom</option>";
		}
		*/
		if ($profile['country']=="United States" || $profile['country']=="") {
			$options .= "<option value=\"United States\"".$selected.">United States</option>";
		} else {
			$options .= "<option value=\"United States\">United States</option>";
		}
		/*
		if ($profile['country']=="Uruguay") {
			$options .= "<option value=\"Uruguay\"".$selected.">Uruguay</option>";
		} else {
			$options .= "<option value=\"Uruguay\">Uruguay</option>";
		}
		if ($profile['country']=="Venezuela") {
			$options .= "<option value=\"Venezuela\"".$selected.">Venezuela</option>";
		} else {
			$options .= "<option value=\"Venezuela\">Venezuela</option>";
		}
		if ($profile['country']=="Vietnam") {
			$options .= "<option value=\"Vietnam\"".$selected.">Vietnam</option>";
		} else {
			$options .= "<option value=\"Vietnam\">Vietnam</option>";
		}
		*/
		$tpl->SetVariable('country_options', $options);
        
		$tpl->SetVariable('lbl_region', _t('USERS_USERS_REGION'));
        $region_options = '';
		$region_options .= "<option value=\"\"".(empty($profile['region']) ? $selected : '').">Select your State...</option>"; 
		$region_options .= "<option".(strtolower($profile['region'])=="al" || strtolower($profile['region'])=="alabama" ? $selected : '').">Alabama</option>"; 
		$region_options .= "<option".(strtolower($profile['region'])=="ak" || strtolower($profile['region'])=="alaska" ? $selected : '').">Alaska</option>";
		$region_options .= "<option".(strtolower($profile['region'])=="az" || strtolower($profile['region'])=="arizona" ? $selected : '').">Arizona</option>";
		$region_options .= "<option".(strtolower($profile['region'])=="ar" || strtolower($profile['region'])=="arkansas" ? $selected : '').">Arkansas</option>";
		$region_options .= "<option".(strtolower($profile['region'])=="ca" || strtolower($profile['region'])=="california" ? $selected : '').">California</option>";
		$region_options .= "<option".(strtolower($profile['region'])=="co" || strtolower($profile['region'])=="colorado" ? $selected : '').">Colorado</option>";
		$region_options .= "<option".(strtolower($profile['region'])=="ct" || strtolower($profile['region'])=="connecticut" ? $selected : '').">Connecticut</option>";
		$region_options .= "<option".(strtolower($profile['region'])=="de" || strtolower($profile['region'])=="delaware" ? $selected : '').">Delaware</option>";
		$region_options .= "<option".(strtolower($profile['region'])=="fl" || strtolower($profile['region'])=="florida" ? $selected : '').">Florida</option>";
		$region_options .= "<option".(strtolower($profile['region'])=="ga" || strtolower($profile['region'])=="georgia" ? $selected : '').">Georgia</option>";
		$region_options .= "<option".(strtolower($profile['region'])=="hi" || strtolower($profile['region'])=="hawaii" ? $selected : '').">Hawaii</option>";
		$region_options .= "<option".(strtolower($profile['region'])=="id" || strtolower($profile['region'])=="idaho" ? $selected : '').">Idaho</option>";
		$region_options .= "<option".(strtolower($profile['region'])=="il" || strtolower($profile['region'])=="illinois" ? $selected : '').">Illinois</option>";
		$region_options .= "<option".(strtolower($profile['region'])=="in" || strtolower($profile['region'])=="indiana" ? $selected : '').">Indiana</option>";
		$region_options .= "<option".(strtolower($profile['region'])=="ia" || strtolower($profile['region'])=="iowa" ? $selected : '').">Iowa</option>";
		$region_options .= "<option".(strtolower($profile['region'])=="ks" || strtolower($profile['region'])=="kansas" ? $selected : '').">Kansas</option>";
		$region_options .= "<option".(strtolower($profile['region'])=="ky" || strtolower($profile['region'])=="kentucky" ? $selected : '').">Kentucky</option>";
		$region_options .= "<option".(strtolower($profile['region'])=="la" || strtolower($profile['region'])=="louisiana" ? $selected : '').">Louisiana</option>";
		$region_options .= "<option".(strtolower($profile['region'])=="me" || strtolower($profile['region'])=="maine" ? $selected : '').">Maine</option>";
		$region_options .= "<option".(strtolower($profile['region'])=="md" || strtolower($profile['region'])=="maryland" ? $selected : '').">Maryland</option>";
		$region_options .= "<option".(strtolower($profile['region'])=="ma" || strtolower($profile['region'])=="massachusetts" ? $selected : '').">Massachusetts</option>";
		$region_options .= "<option".(strtolower($profile['region'])=="mi" || strtolower($profile['region'])=="michigan" ? $selected : '').">Michigan</option>";
		$region_options .= "<option".(strtolower($profile['region'])=="mn" || strtolower($profile['region'])=="minnesota" ? $selected : '').">Minnesota</option>";
		$region_options .= "<option".(strtolower($profile['region'])=="ms" || strtolower($profile['region'])=="mississippi" ? $selected : '').">Mississippi</option>";
		$region_options .= "<option".(strtolower($profile['region'])=="mo" || strtolower($profile['region'])=="missouri" ? $selected : '').">Missouri</option>";
		$region_options .= "<option".(strtolower($profile['region'])=="mt" || strtolower($profile['region'])=="montana" ? $selected : '').">Montana</option>";
		$region_options .= "<option".(strtolower($profile['region'])=="ne" || strtolower($profile['region'])=="nebraska" ? $selected : '').">Nebraska</option>";
		$region_options .= "<option".(strtolower($profile['region'])=="nv" || strtolower($profile['region'])=="nevada" ? $selected : '').">Nevada</option>";
		$region_options .= "<option".(strtolower($profile['region'])=="nh" || strtolower($profile['region'])=="new hampshire" ? $selected : '').">New Hampshire</option>";
		$region_options .= "<option".(strtolower($profile['region'])=="nj" || strtolower($profile['region'])=="new jersey" ? $selected : '').">New Jersey</option>";
		$region_options .= "<option".(strtolower($profile['region'])=="nm" || strtolower($profile['region'])=="new mexico" ? $selected : '').">New Mexico</option>";
		$region_options .= "<option".(strtolower($profile['region'])=="ny" || strtolower($profile['region'])=="new york" ? $selected : '').">New York</option>";
		$region_options .= "<option".(strtolower($profile['region'])=="nc" || strtolower($profile['region'])=="north carolina" ? $selected : '').">North Carolina</option>";
		$region_options .= "<option".(strtolower($profile['region'])=="nd" || strtolower($profile['region'])=="north dakota" ? $selected : '').">North Dakota</option>";
		$region_options .= "<option".(strtolower($profile['region'])=="oh" || strtolower($profile['region'])=="ohio" ? $selected : '').">Ohio</option>";
		$region_options .= "<option".(strtolower($profile['region'])=="ok" || strtolower($profile['region'])=="oklahoma" ? $selected : '').">Oklahoma</option>";
		$region_options .= "<option".(strtolower($profile['region'])=="or" || strtolower($profile['region'])=="oregon" ? $selected : '').">Oregon</option>";
		$region_options .= "<option".(strtolower($profile['region'])=="pa" || strtolower($profile['region'])=="pennsylvania" ? $selected : '').">Pennsylvania</option>";
		$region_options .= "<option".(strtolower($profile['region'])=="ri" || strtolower($profile['region'])=="rhode island" ? $selected : '').">Rhode Island</option>";
		$region_options .= "<option".(strtolower($profile['region'])=="sc" || strtolower($profile['region'])=="south carolina" ? $selected : '').">South Carolina</option>";
		$region_options .= "<option".(strtolower($profile['region'])=="sd" || strtolower($profile['region'])=="south dakota" ? $selected : '').">South Dakota</option>";
		$region_options .= "<option".(strtolower($profile['region'])=="tn" || strtolower($profile['region'])=="tennessee" ? $selected : '').">Tennessee</option>";
		$region_options .= "<option".(strtolower($profile['region'])=="tx" || strtolower($profile['region'])=="texas" ? $selected : '').">Texas</option>";
		$region_options .= "<option".(strtolower($profile['region'])=="ut" || strtolower($profile['region'])=="utah" ? $selected : '').">Utah</option>";
		$region_options .= "<option".(strtolower($profile['region'])=="vt" || strtolower($profile['region'])=="vermont" ? $selected : '').">Vermont</option>";
		$region_options .= "<option".(strtolower($profile['region'])=="va" || strtolower($profile['region'])=="virginia" ? $selected : '').">Virginia</option>";
		$region_options .= "<option".(strtolower($profile['region'])=="wa" || strtolower($profile['region'])=="washington" ? $selected : '').">Washington</option>";
		$region_options .= "<option".(strtolower($profile['region'])=="dc" || strtolower($profile['region'])=="washington d.c." ? $selected : '').">Washington D.C.</option>";
		$region_options .= "<option".(strtolower($profile['region'])=="wv" || strtolower($profile['region'])=="west virginia" ? $selected : '').">West Virginia</option>";
		$region_options .= "<option".(strtolower($profile['region'])=="wi" || strtolower($profile['region'])=="wisconsin" ? $selected : '').">Wisconsin</option>";
		$region_options .= "<option".(strtolower($profile['region'])=="wy" || strtolower($profile['region'])=="wyoming" ? $selected : '').">Wyoming</option>";
		$tpl->SetVariable('region_options', $region_options);
        
		$tpl->SetVariable('lbl_postal', _t('USERS_USERS_POSTAL'));
        $tpl->SetVariable('postal', $xss->filter($profile['postal']));
       
	    $tpl->SetVariable('lbl_phone', _t('USERS_USERS_PHONE'));
        $tpl->SetVariable('phone', $xss->filter($profile['phone']));
        
		$tpl->SetVariable('lbl_office', _t('USERS_USERS_OFFICE'));
        $tpl->SetVariable('office', $xss->filter($profile['office']));
        
		$tpl->SetVariable('lbl_tollfree', _t('USERS_USERS_TOLLFREE'));
        $tpl->SetVariable('tollfree', $xss->filter($profile['tollfree']));
        
		$tpl->SetVariable('lbl_fax', _t('USERS_USERS_FAX'));
        $tpl->SetVariable('fax', $xss->filter($profile['fax']));
		
		$payment_gateway = '';
		if (Jaws_Gadget::IsGadgetUpdated('Ecommerce')) {	
			$GLOBALS['app']->Registry->LoadFile('Ecommerce');
			$GLOBALS['app']->Translate->LoadTranslation('Ecommerce', JAWS_GADGET);
			$payment_gateway = $GLOBALS['app']->Registry->Get('/gadgets/Ecommerce/payment_gateway');
		}
		$tpl->SetVariable('lbl_merchant_id', _t('USERS_USERS_MERCHANT_ID'));
		$tpl->SetVariable('merchant_id', $xss->filter($profile['merchant_id']));
        
		$tpl->SetVariable('lbl_description', _t('USERS_USERS_DESCRIPTION'));
        $description = $xss->filter($profile['description']);
        $description = str_replace('&lt;/p&gt;',"\r\n",$description);
        $description = str_replace('&lt;p&gt;','',$description);
        $tpl->SetVariable('description', $description);
        
		$tpl->SetVariable('lbl_keywords', _t('USERS_USERS_INTERESTS')." "._t('USERS_USERS_COMMA_SEPARATED'));
        $tpl->SetVariable('keywords', $xss->filter($profile['keywords']));
        
		if ($response = $GLOBALS['app']->Session->PopSimpleResponse('Users.Profile.Response')) {
            $tpl->SetBlock('profile/response');
            $tpl->SetVariable('msg', $response);
            $tpl->ParseBlock('profile/response');
        }
        $tpl->ParseBlock('profile');
        return $tpl->Get();
    }

    /**
     * User profiles.
     *
     * @category  feature
     * @access  public
     * @return  void
     */
    function UpdateProfile()
    {
		$GLOBALS['app']->Session->CheckPermission('Users', 'EditAccountProfile');
		
        require_once JAWS_PATH . 'include/Jaws/Header.php';

        $username = $GLOBALS['app']->Session->GetAttribute('username');
        if (empty($username)) {
            Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'LoginForm'), true);
        }

        $request =& Jaws_Request::getInstance();
        $post = $request->get(array('fname', 'lname', 'gender', 'dob_year', 'dob_month', 'dob_day', 'url', 
			'company', 'address', 'address2', 'city', 'country', 
			'region', 'postal', 'phone', 'office', 'tollfree', 
			'fax', 'merchant_id', 'description', 'logo', 
			'keywords', 'company_type', 'redirect_to', 'password', 'password_check'), 'post');

        $post['dob'] = null;
        if (!empty($post['dob_year']) || !empty($post['dob_month']) || !empty($post['dob_day'])) {
            //$date = $GLOBALS['app']->loadDate();
            //$dob  = $date->ToBaseDate(((int)$post['dob_year'] == 0 ? '1700' : (int)$post['dob_year']), (int)$post['dob_month'], (int)$post['dob_day']);
            //$post['dob'] = date('Y-m-d H:i:s', $dob['timestamp']);
            $post['dob'] = ((int)$post['dob_year'] == 0 ? '0000' : (int)$post['dob_year']).'-'.((int)$post['dob_month'] < 10 ? '0'.(int)$post['dob_month'] : (int)$post['dob_month']).'-'.((int)$post['dob_day'] < 10 ? '0'.(int)$post['dob_day'] : (int)$post['dob_day']).' 00:00:00';
        }

        if ($post['password'] === $post['password_check']) {
            $model  = $GLOBALS['app']->LoadGadget('Users', 'Model');
            $result = $model->UpdateProfile($GLOBALS['app']->Session->GetAttribute('user_id'),
                                            $post['fname'],
                                            $post['lname'],
                                            $post['gender'],
                                            $post['dob'],
                                            $post['url'],
                                            $post['company'],
                                            $post['address'],
                                            $post['address2'],
                                            $post['city'],
                                            $post['country'],
                                            $post['region'],
                                            $post['postal'],
                                            $post['phone'],
                                            $post['office'],
                                            $post['tollfree'],
                                            $post['fax'],
                                            $post['merchant_id'],
                                            $post['description'],
                                            $post['logo'],
                                            $post['keywords'],
                                            $post['company_type']);
            if (!Jaws_Error::IsError($result)) {
                $GLOBALS['app']->Session->PushSimpleResponse(_t('USERS_MYACCOUNT_UPDATED'), 'Users.Profile.Response');
            } else {
                $GLOBALS['app']->Session->PushSimpleResponse($result->GetMessage(), 'Users.Profile.Response');
            }
        } else {
            $GLOBALS['app']->Session->PushSimpleResponse(_t('USERS_USERS_PASSWORDS_DONT_MATCH'), 'Users.Profile.Response');
        }

        // unset unnecessary profile data
        unset($post['password'],
              $post['password_check'],
              $post['dob_day'],
              $post['dob_month'],
              $post['dob_year']);
        $GLOBALS['app']->Session->PushSimpleResponse($post, 'Users.Profile.Data');

		$redirectTo = $post['redirect_to'];
		$redirectTo = str_replace('&amp;', '&', substr($redirectTo, 0, (strpos($redirectTo, 'redirect_to=')+12))).urlencode(str_replace('&amp;', '&', substr($redirectTo, (strpos($redirectTo, 'redirect_to=')+12), strlen($redirectTo))));
		if (!empty($redirectTo)) {
            Jaws_Header::Location($redirectTo);
		} else {
			Jaws_Header::Location($this->GetURLFor('Profile'));
		}
	}

    /**
     * Prepares a simple form to update user's data (name, email, password)
     *
     * @access  public
     * @return  string  XHTML template of a form
     */
    function Preferences()
    {
        $username = $GLOBALS['app']->Session->GetAttribute('username');
        if (empty($username)) {
            require_once JAWS_PATH . 'include/Jaws/Header.php';
            $GLOBALS['app']->Session->PushSimpleResponse('You must log-in to continue.');
            Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'LoginForm'), true);
        }

        //Here we load the Settings/Layout models (which is part of core) to extract some data
        $settingsModel = $GLOBALS['app']->loadGadget('Settings', 'AdminModel');

        require_once JAWS_PATH . 'include/Jaws/User.php';
        $jUser = new Jaws_User;
        $info  = $jUser->GetUserInfoById($GLOBALS['app']->Session->GetAttribute('user_id'), true, true, true, true);

        $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');

        // Load the template
        $tpl = new Jaws_Template('gadgets/Users/templates/');
        $tpl->Load('Preferences.html');
        $tpl->SetBlock('preferences');
                
		$tpl->SetVariable('title', _t('USERS_USERS_ACCOUNT_PREF'));
        $tpl->SetVariable('base_script', BASE_SCRIPT);
        $tpl->SetVariable('update', _t('USERS_USERS_ACCOUNT_UPDATE'));

        //Language
        $lang =& Piwi::CreateWidget('Combo', 'user_language');
        $lang->setID('user_language');
        $lang->AddOption(_t('USERS_ADVANCED_OPTS_NOT_YET'), null);
        $languages = Jaws_Utils::GetLanguagesList();
        foreach($languages as $k => $v) {
            $lang->AddOption($v, $k);
        }
        $lang->SetDefault($info['language']);
        $lang->SetTitle(_t('USERS_ADVANCED_OPTS_LANGUAGE'));
        $tpl->SetVariable('user_language', $lang->Get());
        $tpl->SetVariable('language', _t('USERS_ADVANCED_OPTS_LANGUAGE'));

        //Theme
        $theme =& Piwi::CreateWidget('Combo', 'user_theme');
        $theme->setID('user_theme');
        $theme->AddOption(_t('USERS_ADVANCED_OPTS_NOT_YET'), null);
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
        $tpl->SetVariable('user_theme', $theme->Get());
        $tpl->SetVariable('theme', _t('USERS_ADVANCED_OPTS_THEME'));

        //Editor
        $GLOBALS['app']->Translate->loadTranslation('Settings', JAWS_GADGET);
        $editor =& Piwi::CreateWidget('Combo', 'user_editor');
        $editor->setID('user_editor');
        $editor->AddOption(_t('USERS_ADVANCED_OPTS_NOT_YET'), null);
        $editors = $settingsModel->GetEditorList();
        foreach($editors as $k => $v) {
            $editor->AddOption($v, $k);
        }
        $editor->SetDefault($info['editor']);
        $editor->SetTitle(_t('USERS_ADVANCED_OPTS_EDITOR'));
        $tpl->SetVariable('user_editor', $editor->Get());
        $tpl->SetVariable('editor', _t('USERS_ADVANCED_OPTS_EDITOR'));

        //Time Zones
        $timezone =& Piwi::CreateWidget('Combo', 'user_timezone');
        $timezone->setID('user_timezone');
        $timezone->AddOption(_t('USERS_ADVANCED_OPTS_NOT_YET'), null);
        $timezones = $settingsModel->GetTimeZonesList();
        foreach($timezones as $k => $v) {
            $timezone->AddOption($v, $k);
        }
        $timezone->SetDefault($info['timezone']);
        $timezone->SetTitle(_t('GLOBAL_TIMEZONE'));
        $tpl->SetVariable('user_timezone', $timezone->Get());
        $tpl->SetVariable('timezone', _t('GLOBAL_TIMEZONE'));

		// Notification
		// TODO: Multiple methods
		$notificationCombo =& Piwi::CreateWidget('Combo', 'user_notification');
		$notificationCombo->SetTitle(_t('USERS_USERS_NOTIFICATION'));
		$notificationCombo->AddOption(_t('USERS_USERS_NOTIFICATION_EMAIL'), '');
		$notificationCombo->AddOption(_t('USERS_USERS_NOTIFICATION_WEBSITE'), 'website');
		$notificationCombo->AddOption(_t('USERS_USERS_NOTIFICATION_SMS'), 'sms');
		$notificationCombo->SetDefault($info['notification']);
        $tpl->SetVariable('user_notification', $notificationCombo->Get());
        $tpl->SetVariable('notification', _t('USERS_USERS_NOTIFICATION'));
	
		// Comments
		$commentsCombo =& Piwi::CreateWidget('Combo', 'user_allow_comments');
		$commentsCombo->SetTitle(_t('USERS_USERS_ALLOW_COMMENTS'));
		$commentsCombo->AddOption(_t('GLOBAL_YES'), 1);
		$commentsCombo->AddOption(_t('GLOBAL_NO'), 0);
		$commentsCombo->SetDefault($info['allow_comments']);
        $tpl->SetVariable('user_allow_comments', $commentsCombo->Get());
        $tpl->SetVariable('allow_comments', _t('USERS_USERS_ALLOW_COMMENTS'));
		        
		if ($response = $GLOBALS['app']->Session->PopSimpleResponse('Users.Preferences')) {
            $tpl->SetBlock('preferences/response');
            $tpl->SetVariable('msg', $response);
            $tpl->ParseBlock('preferences/response');
        }
        $tpl->ParseBlock('preferences');
        return $tpl->Get();
    }

    /**
     * User preferences.
     *
     * @category  feature
     * @access  public
     * @return  void
     */
    function UpdatePreferences()
    {
        require_once JAWS_PATH . 'include/Jaws/Header.php';

        $username = $GLOBALS['app']->Session->GetAttribute('username');
        if (empty($username)) {
            $GLOBALS['app']->Session->PushSimpleResponse('You must log-in to continue.');
            Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'LoginForm'), true);
        }

        $request =& Jaws_Request::getInstance();
        $post = $request->get(array('user_language', 'user_theme', 'user_editor', 
			'user_timezone', 'user_notification', 'user_allow_comments', 
			'captcha', 'captchaKey'), 'post');

        $model = $GLOBALS['app']->LoadGadget('Users', 'Model');
        $result = $model->UpdatePreferences($GLOBALS['app']->Session->GetAttribute('user_id'),
                                            $post['user_language'],
                                            $post['user_theme'],
                                            $post['user_editor'],
                                            $post['user_timezone'],
                                            $post['user_notification'],
                                            $post['user_allow_comments']);

        if (!Jaws_Error::IsError($result)) {
            $GLOBALS['app']->Session->PushSimpleResponse(_t('USERS_PREFERENCES_UPDATED'), 'Users.Preferences');
        } else {
            $GLOBALS['app']->Session->PushSimpleResponse($result->GetMessage(), 'Users.Preferences');
        }

        Jaws_Header::Location($this->GetURLFor('Preferences'));
    }

	/**
     * Display of user's subscriptions, services and other gadget data.
     *
     * @access  public
     * @return  string  XHTML template of a form
     */
    function AccountHome()
    {
		$GLOBALS['app']->Session->PopLastResponse();
        $username = $GLOBALS['app']->Session->GetAttribute('username');
		require_once JAWS_PATH . 'include/Jaws/User.php';
        $jUser = new Jaws_User;
        $info  = $jUser->GetUserInfoById((int)$GLOBALS['app']->Session->GetAttribute('user_id'), true, true, true, true);
        if (empty($username) || $username == 'anonymous' || Jaws_Error::isError($info) || !isset($info['id']) || empty($info['id'])) {
			require_once JAWS_PATH . 'include/Jaws/Header.php';
			Jaws_Header::Location($this->GetURLFor('DefaultAction'));
		}
		/*
		// FIXME: Add this via Ecommerce->LayoutAction
		// Make Add To Cart available, if necessary
		if (Jaws_Gadget::IsGadgetUpdated('Ecommerce') && Jaws_Gadget::IsGadgetUpdated('Store')) {
			$ecommerceLayout = $GLOBALS['app']->LoadGadget('Ecommerce', 'LayoutHTML');
			$ecommerceLayout->Display();
		}
		*/
		$GLOBALS['app']->Layout->AddHeadLink('libraries/greybox/gb_styles.css', 'stylesheet', 'text/css');
        $GLOBALS['app']->Layout->AddHeadLink('libraries/window/themes/window/window.css', 'stylesheet', 'text/css');
		$GLOBALS['app']->Layout->AddHeadLink('libraries/window/themes/window/simpleblue.css', 'stylesheet', 'text/css');
        $GLOBALS['app']->Layout->AddHeadLink('libraries/window/themes/shadow/mac_shadow.css', 'stylesheet', 'text/css');
        $GLOBALS['app']->Layout->AddHeadLink('gadgets/Users/resources/style.css', 'stylesheet', 'text/css', 'Users');
		$GLOBALS['app']->Layout->AddScriptLink('libraries/js/admin.js');
		$GLOBALS['app']->Layout->AddScriptLink('libraries/js/global2.js');
        $this->AjaxMe('client_script.js');
		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=CustomPage&amp;action=account_SetGBRoot');
		$GLOBALS['app']->Layout->AddScriptLink('libraries/window/dist/window.js');
		$GLOBALS['app']->Layout->AddScriptLink('libraries/greybox/AJS.js');
        $GLOBALS['app']->Layout->AddScriptLink('libraries/greybox/AJS_fx.js');
        $GLOBALS['app']->Layout->AddScriptLink('libraries/greybox/gb_scripts.js');
		$GLOBALS['app']->Layout->AddHeadOther('<!--[if lte IE 7]><style>html>body .ui-window .content { border-top: 1px solid #FFF;}</style><![endif]-->');
		$GLOBALS['app']->Layout->AddHeadOther('<script type="text/javascript">
		var startTime = Math.round(new Date().getTime() / 1000);
		new PeriodicalExecuter(function(pe) {
			$$(".news-timestamp").each(function(element){
				currentTime = Math.round(new Date().getTime() / 1000);
				oldTimestamp = element.innerHTML;
				if (oldTimestamp == "A few seconds ago") {
					element.innerHTML = "Just a minute ago";
				} else if (oldTimestamp == "Just a minute ago") {
					element.innerHTML = "2 minute(s) ago";
				} else if (oldTimestamp.indexOf("minute(s) ago") > -1) {
					var minutes = (parseInt(oldTimestamp.replace(" minute(s) ago", ""), 10)+1);
					if (minutes > 60) {
						element.innerHTML = "One hour ago";
					} else {
						element.innerHTML = minutes + " minute(s) ago";
					}
				} else if (oldTimestamp == "One hour ago" && ((currentTime - startTime) >= 3600)) {
					element.innerHTML = "2 hour(s) ago";
				} else if (oldTimestamp.indexOf("hour(s) ago") > -1 && ((currentTime - startTime) >= 3600)) {
					var hours = (parseInt(oldTimestamp.replace(" hour(s) ago", ""), 10)+1);
					if (hours > 24) {
						element.innerHTML = "One day ago";
					} else {
						element.innerHTML = hours + " hour(s) ago";
					}
				} else if (oldTimestamp == "One day ago" && ((currentTime - startTime) >= 86400)) {
					element.innerHTML = "2 day(s) ago";
				} else if (oldTimestamp.indexOf("day(s) ago") > -1 && ((currentTime - startTime) >= 86400)) {
					var days = (parseInt(oldTimestamp.replace(" day(s) ago", ""), 10)+1);
					element.innerHTML = days + " day(s) ago";
				}
			});
		}, 60);
		</script>');
		$GLOBALS['app']->Layout->AddHeadOther('<script src="'.$GLOBALS['app']->GetJawsURL().'/libraries/pusher/1.12/pusher.min.js" type="text/javascript"></script>
		 <script type="text/javascript">
			Pusher.channel_auth_transport = "ajax";
			Pusher.channel_auth_endpoint = "'.$GLOBALS['app']->GetSiteURL().'/index.php?action=PusherAuth";      
			var pusher = new Pusher("9237ced3ff398dc663f0");
		 </script>');
		if ((int)$GLOBALS['app']->Session->GetAttribute('user_id') == 0) {
			$GLOBALS['app']->Layout->AddHeadOther('<style>.comments-form .comment-area .comment-entry {display: none;} .comments-form .comment-buttons {text-align: left;}</style>');
		}
		
		$model  = $GLOBALS['app']->LoadGadget('Users', 'Model');

        $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$request =& Jaws_Request::getInstance();
		$get = $request->get(array('msg', 'o'), 'get');
        
		// Load the template
        $tpl = new Jaws_Template('gadgets/Users/templates/');
        $tpl->Load('AccountHome.html');
        $tpl->SetBlock('account_home');
        $tpl->SetVariable('title', _t('USERS_USERS_ACCOUNT_HOME'));
        $tpl->SetVariable('title_name', $xss->filter($info['nickname']));
		$tpl->SetVariable('base_script', BASE_SCRIPT);
		//$tpl->SetVariable('DPATH', JAWS_DPATH);
		$tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . '/');

		$tpl->SetVariable('first', 'Users');
		
		// Load AJAX environment all gadgets
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
				if (method_exists($hook, 'GetComments')) {
					$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget='.$g['realname'].'&action=Ajax&client=all&stub='.$g['realname'].'Ajax');
					$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget='.$g['realname'].'&action=AjaxCommonFiles');
					$GLOBALS['app']->Layout->AddScriptLink('gadgets/'.$g['realname'].'/resources/client_script.js');
				}
			}
		}
		
		$available_panes = array();				
		
		$panes = $model->GetPanes($layout);
		if (!Jaws_Error::IsError($panes) && is_array($panes) && !count($panes) <= 0) {
			foreach ($panes as $p) {
				$available_panes[] = "'".$p['id']."'";
				$tpl->SetBlock('account_home/pane');
				$tpl->SetVariable('id', $p['id']);
				$tpl->SetVariable('desc', $p['desc']);
				$tpl->SetVariable('icon', $p['icon']);
				$tpl->SetVariable('gadget', $p['gadget']);
				$tpl->SetVariable('class', $p['class']);
				$tpl->SetVariable('gadgetrealname', $p['gadgetrealname']);
				$tpl->SetVariable('method', $p['method']);
				$paneparams = '';
				foreach ($p['params'] as $pkey => $pval) {
					$paneparams .= '&'.$pkey.'='.urlencode($pval);
				}
				$tpl->SetVariable('params', $paneparams);
				$tpl->ParseBlock('account_home/pane');
				$tpl->SetBlock('account_home/pane_content');
				$tpl->SetVariable('id', $p['id']);
				$tpl->SetVariable('style', $p['style']);
				//$tpl->SetVariable('content', $p['pane']);
				$tpl->ParseBlock('account_home/pane_content');
			}
			$tpl->SetVariable('available_panes', implode(',', $available_panes));
			
			if ($response = $GLOBALS['app']->Session->PopSimpleResponse()) {
				$tpl->SetBlock('account_home/response');
				$tpl->SetVariable('msg', $response);
				$tpl->ParseBlock('account_home/response');
			} else if (!empty($get['msg'])) {
				$tpl->SetBlock('account_home/response');
				$tpl->SetVariable('msg', $xss->parse($get['msg']));
				$tpl->ParseBlock('account_home/response');
			}
		} else {
				$tpl->SetBlock('account_home/response');
				$tpl->SetVariable('msg', (Jaws_Error::IsError($panes) ? $panes->GetMessage() : _t('USERS_ACCOUNTHOME_NO_GADGETS')));
				$tpl->ParseBlock('account_home/response');
		}
		
        $tpl->ParseBlock('account_home');
        return $tpl->Get();
		
	}

    /**
     * Show comments
     *
     * @access public
     * @return string
     */
    function ShowRawComments(
		$gadget = 'Users', $public = true, $id = null, $header = '', $interactive = true, $replies_shown = 2, 
		$layout = 'full', $only_comments = false, $limit = 5, $method = 'GetComments'
	) {
		$request =& Jaws_Request::getInstance();
		$gadget = $request->get('fusegadget', 'get');
		$page = $this->ShowComments(
			$gadget, $public, $id, $header, $interactive, $replies_shown, 
			$layout, $only_comments, $limit, $method
		);
		$html_output = $this->GetAccountHTML($gadget, true, true, false);
		$html_output = str_replace("</head>", "<style type=\"text/css\">body { background: transparent none; }</style>\n</head>", $html_output);
		$output_html = str_replace("__JAWS_GADGET__", $page, $html_output);
		return $output_html;
    }
	
	/**
     * Site-wide notifications and comments platform.
     *
     * @category  feature
     * @access  public
     * @return  string  XHTML template
     * @TODO 	Move some of this logic to UsersModel->GetComments()
     */
    function ShowComments(
		$gadget = 'Users', $public = true, $id = null, $header = '', $interactive = true, $replies_shown = 2, 
		$layout = 'full', $only_comments = false, $limit = 10, $method = 'GetComments'
	) {
		$date = $GLOBALS['app']->loadDate();
		require_once JAWS_PATH . 'include/Jaws/Image.php';
		require_once JAWS_PATH . 'include/Jaws/User.php';
		$jUser = new Jaws_User;
		$model = $GLOBALS['app']->LoadGadget('Users', 'Model');
		$usersLayout = $GLOBALS['app']->LoadGadget('Users', 'LayoutHTML');
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');		
		$request =& Jaws_Request::getInstance();
		$get = $request->get(array('fusegadget','action','id','p','h','i','r','m','oc','l'), 'get');
		if (!empty($get['fusegadget'])) {
			$gadget = $get['fusegadget'];
		}
		if (!empty($get['p'])) {
			$public = ($get['p'] == 'false' ? false : ($get['p'] == 'true' ? true : $public));
		}
		if (!empty($get['h'])) {
			$header = ($get['h'] == 'false' ? false : $get['h']);
		}
		if (!empty($get['i'])) {
			$interactive = ($get['i'] == 'false' ? false : ($get['i'] == 'true' ? true : $interactive));
		}
		if (!empty($get['r'])) {
			$replies_shown = ((int)$get['r'] > 0 ? (int)$get['r'] : 2);
		}
		if (!empty($get['o'])) {
			$layout = $get['o'];
		}
		if (!empty($get['m']) && $get['action'] != 'Ajax') {
			$method = $get['m'];
		}
		if (!empty($get['oc'])) {
			$only_comments = ($get['oc'] == 'true' ? true : ($get['oc'] == 'false' ? false : $only_comments));
		}
		if (!empty($get['l'])) {
			$limit = (int)$get['l'];
		}
		if (is_null($id)) {
			$id = $get['id'];
			if (!empty($id)) {
				$id = explode(',', $id);
			}
		}
		$uid = (int)$GLOBALS['app']->Session->GetAttribute('user_id');
		// Load the template
		$tpl = new Jaws_Template('gadgets/Users/templates/');
		if ($layout == 'boxy') {
			$tpl->Load('ShowBoxes.html');
			$namespace_single = 'box';
			$namespace = 'boxes';
		} else if ($layout == 'tiles') {
			$tpl->Load('ShowTiles.html');
			$namespace_single = 'tile';
			$namespace = 'tiles';
		} else {
			$tpl->Load('ShowComments.html');
			$namespace_single = '';
			$namespace = 'news';
		}
        if ($only_comments === false) {
			$GLOBALS['app']->Layout->AddHeadLink('libraries/greybox/gb_styles.css', 'stylesheet', 'text/css');
			$GLOBALS['app']->Layout->AddHeadLink('libraries/window/themes/window/window.css', 'stylesheet', 'text/css');
			$GLOBALS['app']->Layout->AddHeadLink('libraries/window/themes/window/simpleblue.css', 'stylesheet', 'text/css');
			$GLOBALS['app']->Layout->AddHeadLink('libraries/window/themes/shadow/mac_shadow.css', 'stylesheet', 'text/css');
			$GLOBALS['app']->Layout->AddHeadLink('gadgets/Users/resources/style.css', 'stylesheet', 'text/css', 'Users');
			$GLOBALS['app']->Layout->AddScriptLink($GLOBALS['app']->GetJawsURL() . '/libraries/prototype/prototype.js');
			$GLOBALS['app']->Layout->AddScriptLink($GLOBALS['app']->GetJawsURL() . '/libraries/prototype/scriptaculous.js');
			$GLOBALS['app']->Layout->AddScriptLink($GLOBALS['app']->GetJawsURL() . '/libraries/prototype/effects.js');
			$GLOBALS['app']->Layout->AddScriptLink($GLOBALS['app']->GetJawsURL() . '/libraries/prototype/controls.js');
			$GLOBALS['app']->Layout->AddScriptLink('include/Jaws/Ajax/Response.js');
			$GLOBALS['app']->Layout->AddScriptLink('libraries/js/admin.js');
			$GLOBALS['app']->Layout->AddScriptLink('libraries/js/global2.js');
			$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=Users&action=Ajax&client=all&stub=UsersAjax');
			$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=Users&action=AjaxCommonFiles');
			$GLOBALS['app']->Layout->AddScriptLink('gadgets/Users/resources/client_script.js');
			$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=CustomPage&amp;action=account_SetGBRoot');
			$GLOBALS['app']->Layout->AddScriptLink('libraries/window/dist/window.js');
			$GLOBALS['app']->Layout->AddScriptLink('libraries/greybox/AJS.js');
			$GLOBALS['app']->Layout->AddScriptLink('libraries/greybox/AJS_fx.js');
			$GLOBALS['app']->Layout->AddScriptLink('libraries/greybox/gb_scripts.js');
			$GLOBALS['app']->Layout->AddHeadOther('<!--[if lte IE 7]><style>html>body .ui-window .content { border-top: 1px solid #FFF;}</style><![endif]-->');
			$GLOBALS['app']->Layout->AddHeadOther('<script type="text/javascript">
			var startTime = Math.round(new Date().getTime() / 1000);
			new PeriodicalExecuter(function(pe) {
				$$(".'.$namespace.'-timestamp").each(function(element){
					currentTime = Math.round(new Date().getTime() / 1000);
					oldTimestamp = element.innerHTML;
					if (oldTimestamp == "A few seconds ago") {
						element.innerHTML = "Just a minute ago";
					} else if (oldTimestamp == "Just a minute ago") {
						element.innerHTML = "2 minute(s) ago";
					} else if (oldTimestamp.indexOf("minute(s) ago") > -1) {
						var minutes = (parseInt(oldTimestamp.replace(" minute(s) ago", ""), 10)+1);
						if (minutes > 60) {
							element.innerHTML = "One hour ago";
						} else {
							element.innerHTML = minutes + " minute(s) ago";
						}
					} else if (oldTimestamp == "One hour ago" && ((currentTime - startTime) >= 3600)) {
						element.innerHTML = "2 hour(s) ago";
					} else if (oldTimestamp.indexOf("hour(s) ago") > -1 && ((currentTime - startTime) >= 3600)) {
						var hours = (parseInt(oldTimestamp.replace(" hour(s) ago", ""), 10)+1);
						if (hours > 24) {
							element.innerHTML = "One day ago";
						} else {
							element.innerHTML = hours + " hour(s) ago";
						}
					} else if (oldTimestamp == "One day ago" && ((currentTime - startTime) >= 86400)) {
						element.innerHTML = "2 day(s) ago";
					} else if (oldTimestamp.indexOf("day(s) ago") > -1 && ((currentTime - startTime) >= 86400)) {
						var days = (parseInt(oldTimestamp.replace(" day(s) ago", ""), 10)+1);
						element.innerHTML = days + " day(s) ago";
					}
				});
			}, 60);
			</script>');
			$GLOBALS['app']->Layout->AddHeadOther('<script src="'.$GLOBALS['app']->GetJawsURL().'/libraries/pusher/1.12/pusher.min.js" type="text/javascript"></script>
			 <script type="text/javascript">
				Pusher.channel_auth_transport = "ajax";
				Pusher.channel_auth_endpoint = "'.$GLOBALS['app']->GetSiteURL().'/index.php?action=PusherAuth";      
				var pusher = new Pusher("9237ced3ff398dc663f0");
			 </script>');
			if (file_exists(JAWS_PATH . 'gadgets/'.$gadget.'/resources/style.css')) {
				$GLOBALS['app']->Layout->AddHeadLink('gadgets/'.$gadget.'/resources/style.css', 'stylesheet', 'text/css', 'Users');
			}
			if ($uid == 0) {
				$GLOBALS['app']->Layout->AddHeadOther('<style>.'.$namespace_single.'comments-form .'.$namespace_single.'comment-area .'.$namespace_single.'comment-entry {display: none;} .'.$namespace_single.'comments-form .'.$namespace_single.'comment-buttons {text-align: left;}</style>');
			}
		}
		$site_name = $GLOBALS['app']->Registry->Get('/config/site_name');
		$site_url = $GLOBALS['app']->GetSiteURL();
		if (empty($site_name)) {
			$site_name = str_replace(array('http://', 'https://'), '', $site_url);
		}
		$social_title = preg_replace("[^A-Za-z0-9\ ]", '', $site_name);
		$firstname = 'User';
		$lastname = '';
		
		$last_count = (-1);
		$new_count = 0;
		$i = 1;
		$result_max = (!is_null($id) && is_array($id) && $method == 'GetComments' ? 9999 : ($method == 'GetGroupComments' ? 1 : 3));
		$news_items = array();
		if (!is_array($id) && $gadget == 'Users') {
			// If public, get gadget_reference
			if ($public === true && $method == 'GetComments') {
				$userInfo = $jUser->GetUserInfoById((int)$id, true, true, true, true);
				if (Jaws_Error::IsError($userInfo) || !isset($userInfo['id']) || empty($userInfo['id'])) {
					$uname = $request->get('name', 'get');
					$userInfo = $jUser->GetUserInfoByName($uname, true, true, true, true);
					if (Jaws_Error::IsError($userInfo) || !isset($userInfo['id']) || empty($userInfo['id'])) {
						//return new Jaws_Error(_t('USERS_ACCOUNTPUBLIC_CANT_LOAD_PROFILE'), _t('USERS_NAME'));
					}
				}
				if (isset($userInfo['id']) && !empty($userInfo['id'])) {
					$id = $userInfo['id'];
				}
			}
			$title = '<img height="36" border="0" align="middle" width="36" src="'.$GLOBALS['app']->getJawsURL().'/gadgets/Users/images/icon_updates.png">';
		} else {
			$title = '<img height="36" border="0" align="middle" width="36" src="'.$GLOBALS['app']->getJawsURL().'/gadgets/'.$gadget.'/images/logo.png">';
		}
		
		while ($new_count < $result_max && $new_count > $last_count) {
			$limit = ($limit * $i);
			$i++;
			$last_count = count($news_items);
			$news_items = array_merge($news_items, $model->GetComments($gadget, $public, $id, $method, $limit, $result_max, $layout));
			$new_count = count($news_items);
		}
		
		if (is_array($id)) {
			$id = implode(',', $id);
			$interactive = false;
		}
		$request_id = $id;
		if (is_null($id)) {
			$id = $uid;
		}
		//exit;
				
		$tpl->SetBlock('account_news');
 		$tpl->SetVariable('gadget', $gadget);
        if ($only_comments === false) {
			if (empty($header) && $header !== false) {
				$header = _t('USERS_ACCOUNTHOME_PANE_UPDATES_TITLE');
			}
			$tpl->SetBlock('account_news/news_header');
			$tpl->SetVariable('public', ($public === true ? 'true' : 'false'));
			$news_id = '';
			$entry_button = '';
			$entry_field = '';
			$entry_sharing = '';
			$entry_actions_style = '';
			if ($layout !== 'tiles') {
				if ($public === false) {
					// FIXME: Should we use ACLs to determine if user can add?
					//$interactive = (($GLOBALS['app']->Session->IsAdmin() || $GLOBALS['app']->Session->IsSuperAdmin()) ? true : false);
					//$pgroups  = $jUser->GetGroupsOfUser($uid);
					if ($gadget == 'Users') {
						// Check if user is in profile group
						if ($GLOBALS['app']->Session->IsSuperAdmin() || in_array($jUser->GetStatusOfUserInGroup($uid, 'profile'), array('active','admin','founder'))) {
							$interactive = ($interactive === true ? true : false);
						}
						$submit =& Piwi::CreateWidget('Button', 'shareButton', _t('USERS_ACCOUNTHOME_SHAREBUTTON'), STOCK_ADD);
						$submit->AddEvent(ON_CLICK, 'javascript: addUpdate(\''.$GLOBALS['app']->getSiteURL().'/index.php?gadget=Users&action=account_AddLayoutElement&mode=new\', \'Share Content\');');
					} else {
						// Check if user is in <gadget>_owners group
						if ($GLOBALS['app']->Session->IsSuperAdmin() || in_array($jUser->GetStatusOfUserInGroup($uid, strtolower($gadget).'_owners'), array('active','admin','founder'))) {
							$interactive = ($interactive === true ? true : false);
						}
						$submit =& Piwi::CreateWidget('Button', 'add'.$gadget.'Button', _t('GLOBAL_ADD'), STOCK_ADD);
						$submit->AddEvent(ON_CLICK, 'javascript: addUpdate(\''.$GLOBALS['app']->getSiteURL().'/index.php?gadget=Users&action=account_AddLayoutElement&mode=new&first='.$gadget.'\', \''.$gadget.'\');');
						$title = '<img height="36" border="0" align="middle" width="36" src="'.$GLOBALS['app']->getJawsURL().'/gadgets/'.$gadget.'/images/logo.png">';
						$news_id = $gadget.'-';
					}
					$submit->SetStyle('min-width: 60px;');
					$title = '
						<div id="'.$namespace.'-sort-menu-updates-Panes-'.$gadget.'" class="gadget menu '.$namespace.'-sort-menu '.$namespace.'-sort-menu-panes">
							<div class="content">
								<ul class="ul_top_menu" style="text-align: left;">
									<li id="'.$namespace.'-sort-update-Panes-'.$gadget.'" class="menu_li_item menu_li_pane_item menu_first menu_super"><a href="javascript:void(0);">'.$title.'&nbsp;'.$header.'</a></li>
								</ul>
							</div>
						</div>
					';
					$title .= ($interactive === true && $layout != 'mail' ? $submit->Get() : '');
				} else {
					$title = '
						<div id="'.$namespace.'-sort-menu-updates-Panes-'.$gadget.'" class="gadget '.$namespace.'-sort-menu-panes">
						'.$title.'&nbsp;'.$header.'&nbsp;
						</div>';
					$follow_buttons = $usersLayout->ShowFollowButtons($gadget, $method, $id);
					$follow_buttons = strip_tags($follow_buttons, '<button><span><img><u><i>');
					/*
					if ($uid == 0) {
						//$GLOBALS['app']->Session->PushSimpleResponse('You must log-in to continue.');
						$submit =& Piwi::CreateWidget('Button', 'updateButton', "Comment");
						$submit->SetStyle('min-width: 60px;');
						$submit->AddEvent(ON_CLICK, "javascript: location.href = '".$GLOBALS['app']->GetSiteURL() . '/index.php?gadget=Users&action=DefaultAction&redirect_to='.urlencode($GLOBALS['app']->GetFullURL())."';");
						$entry_button = '';
						$title .= $submit->Get().$follow_buttons;
					} else {
					*/
						$submit =& Piwi::CreateWidget('Button', 'updateButton', _t('GLOBAL_ADD'), STOCK_ADD);
						$submit->SetStyle('min-width: 60px;');
						$submit->AddEvent(ON_CLICK, "javascript: save".($gadget == 'Users' ? '' : $gadget)."Update(".$id.", (\$\$('#".$gadget."-accountNews #".$gadget."-update-entry .update-entry')[0].value.length > 0 ? \$\$('#".$gadget."-accountNews #".$gadget."-update-entry .update-entry')[0].value : ''), '', 0, ".($method == 'GetGroupComments' ? "'groups:".$id."'" : "(\$\$('#".$gadget."-accountNews #".$gadget."-update-entry #".$gadget."-update-actions .update-sharing-public')[0].checked ? 'everyone' : 'owner')")."); \$\$('#".$gadget."-accountNews #".$gadget."-update-entry .update-actions')[0].style.display = 'none'; \$\$('#".$gadget."-accountNews #".$gadget."-update-entry .update-entry')[0].style.height = '40px'; \$\$('#".$gadget."-accountNews #".$gadget."-update-entry .update-entry')[0].style.color = '#888888'; \$\$('#".$gadget."-accountNews #".$gadget."-update-entry .update-entry')[0].value = 'Leave a comment...';");
						$descriptionEntry=& Piwi::CreateWidget('TextArea', 'update-entry', 'Leave a comment...');
						$descriptionEntry->SetTitle('Leave a comment...');
						$descriptionEntry->SetClass('update-entry');
						if ($uid == 0) {
							$descriptionEntry->AddEvent(ON_CLICK, "javascript: location.href = '".$GLOBALS['app']->GetSiteURL() . '/index.php?gadget=Users&action=DefaultAction&redirect_to='.urlencode($GLOBALS['app']->GetFullURL())."';");
							$descriptionEntry->AddEvent(ON_FOCUS, "javascript: if (\$\$('#".$gadget."-accountNews #".$gadget."-update-entry .update-entry')[0].value == 'Leave a comment...') {\$\$('#".$gadget."-accountNews #".$gadget."-update-entry .update-entry')[0].value = '';}");
							$descriptionEntry->SetStyle('color: #888888; height: 40px; width: 100%; cursor: pointer; cursor: hand;');
						} else {
							$descriptionEntry->AddEvent(ON_FOCUS, "javascript: if (\$\$('#".$gadget."-accountNews #".$gadget."-update-entry .update-entry')[0].value == 'Leave a comment...') {\$\$('#".$gadget."-accountNews #".$gadget."-update-entry .update-actions')[0].style.display = 'block'; \$\$('#".$gadget."-accountNews #".$gadget."-update-entry .update-entry')[0].value = ''; \$\$('#".$gadget."-accountNews #".$gadget."-update-entry .update-entry')[0].style.color = '#000000'; \$\$('#".$gadget."-accountNews #".$gadget."-update-entry .update-entry')[0].style.height = '100px';}");
							$descriptionEntry->SetStyle('color: #888888; height: 40px; width: 100%;');
						}
						$entry_field = $descriptionEntry->Get();
						$entry_sharing = '<label for="'.$gadget.'-update-sharing-public">Public</label>
						<input style="width: auto; min-width: 15px;" type="checkbox" checked="checked" name="sharing_public" id="'.$gadget.'-update-sharing-public" class="update-sharing-public" value="true" />&nbsp;&nbsp;';
						$entry_actions_style = ' display: none;';
						$entry_button = $submit->Get();
						$title .= $follow_buttons;
					//}
				}
			}

			if ($interactive === false) {
				$entry_field = '';
				$entry_button = '';
				$entry_sharing = '';
				$entry_actions_style = ' display: none;';
			}
			if ($header === false) {
				$title = '';
			}
			$tpl->SetVariable('news_id', $news_id);
			$tpl->SetVariable('entry_button', $entry_button);
			$tpl->SetVariable('entry_field', $entry_field);
			$tpl->SetVariable('entry_sharing', $entry_sharing);
			$tpl->SetVariable('entry_actions_style', $entry_actions_style);
			$tpl->SetVariable('gadget', $gadget);
			$title_options = '';
			$hook = $GLOBALS['app']->loadHook($gadget, 'Comment');
			if ($hook !== false) {
				if (method_exists($hook, 'GetCommentsTitleOptions')) {
					$title_options = $hook->GetCommentsTitleOptions(array('uid' => $uid, 'public' => $public, 'title' => $title, 'layout' => $layout));
					if (Jaws_Error::IsError($title_options)) {
						$title_options = '';
					}
				}
			}
			$tpl->SetVariable('header', $title_options);
			$tpl->ParseBlock('account_news/news_header');
		}
		
		$news_items_sorted = array();
		// Load Update hook
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout(
			'onLoadAccountNews', 
			array(
				'items' => $news_items, 
				'id' => $id, 
				'header' => $header,
				'interactive' => $interactive,
				'replies_shown' => $replies_shown, 
				'layout' => $layout, 
				'only_comments' => $only_comments, 
				'method' => $method,
				'limit' => $limit, 
				'gadget' => $gadget, 
				'user_id' => $uid, 
				'public' => $public
			)
		);
		if (!Jaws_Error::IsError($res)) {
			if (isset($res['comments'])) {
				foreach ($res['comments'] as $item) {
					$news_items_sorted[] = $item;
				}
			}
			if (isset($res['limit'])) {
				$limit = $res['limit'];
			}
		}
		
		if (count($news_items) <= 0 && count($news_items_sorted) <= 0) {
			$tpl->SetBlock('account_news/no_news');
			//$tpl->SetVariable('message', ($public === true ? _t('USERS_ACCOUNTHOME_NO_COMMENTS') : _t('USERS_ACCOUNTHOME_NO_SUBSCRIPTIONS')));
			$tpl->SetVariable('message', _t('USERS_ACCOUNTHOME_NO_SUBSCRIPTIONS'));
			$tpl->ParseBlock('account_news/no_news');
		} else {
			reset($news_items);
			reset($news_items_sorted);
			
			$msg_keys = array();
							
			$subkey = 'createtime'; 			
			// Sort items result array
			$temp_array = array();
			$temp_array[key($news_items)] = array_shift($news_items);
			foreach($news_items as $key => $val){
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
			}
			//$news_items = array_reverse($temp_array);
			foreach ($temp_array as $temp) {
				$news_items_sorted[] = $temp;
			}			
						
			$item_count = 1;
			foreach ($news_items_sorted as $item) {
				//if (!in_array('_'.$gadget.$item['id'], $GLOBALS['app']->_ItemsOnLayout))) {
					$thread = $this->ShowCommentThread($gadget, $item, $replies_shown, $interactive, $layout, $item_count, $public);
					if (!Jaws_Error::isError($thread)) {
						$tpl->SetBlock('account_news/news_item');
						$tpl->SetVariable('thread', $thread);
						$tpl->ParseBlock('account_news/news_item');
					}
					$item_count++;
				/*
					$i++;
					$msg_keys[] = $item['id'];
				} else if ($i >= $result_max) {
					break;
				}
				*/
			}
		}
		
		if ($public === true && $gadget == 'Users' && $layout == 'full' && $get['action'] == 'AccountPublic' && ($uid == 0 || $id != $uid)) {
			// Show follow me link
			$is_friend = false;
			if ($id != $uid) {
				$is_friend = $jUser->UserIsFriend($uid, $id);
			}
			if ($is_friend === false && $jUser->GetStatusOfUserInFriend($uid, $id) != 'blocked') {
				$groups  = $jUser->GetGroupsOfUser($uid);
				// Check if user is in profile group
				if (!Jaws_Error::IsError($groups)) {
					foreach ($groups as $group) {
						if (strtolower($group['group_name']) == 'profile' && (in_array($group['group_status'], array('active','founder','admin')))) {
							$followButton =& Piwi::CreateWidget('Button', 'followButton', 'Follow', STOCK_ADD);
							$followButton->SetStyle('min-width: 80px;');
							$followButton->AddEvent(ON_CLICK, "javascript: location.href = '".$GLOBALS['app']->Map->GetURLFor('Users', 'RequestFriend', array('friend_id' => $uid))."';");
							if (isset($userInfo['company']) && !empty($userInfo['company'])) {
								$firstname = $userInfo['company'];
							} else if (isset($userInfo['fname']) && !empty($userInfo['fname']) && isset($userInfo['lname']) && !empty($userInfo['lname'])) {
								$firstname = $userInfo['fname'];
								$lastname = $userInfo['lname'];
							} else if (isset($userInfo['nickname']) && !empty($userInfo['nickname'])) {
								$nameparts = explode(" ", $userInfo['nickname']);
								if (isset($nameparts[0]) && !empty($nameparts[0]) && isset($nameparts[1]) && !empty($nameparts[1])) {
									$firstname = strtolower(str_replace('.','',$nameparts[0]));
									$startpart = 1;
									if ($firstname == 'mr' || $firstname == 'mrs' || $firstname == 'ms' || $firstname == 'dr') {
										$firstname = $nameparts[1];
										$startpart = 2;
									}
									for ($s=$startpart; $s<6; $s++) {
										$lastname .= (isset($nameparts[$s]) ? str_replace('.','',$nameparts[$s]) : '');
									}
								}
								$firstname = ucfirst($firstname);
							}
							$tpl->SetBlock('account_news/no_news');
							$tpl->SetVariable('message', $followButton->Get().'&nbsp;'._t('USERS_ACCOUNTPUBLIC_NO_UPDATES', $firstname));
							$tpl->ParseBlock('account_news/no_news');
							break;
						}
					}
				}
			}
		} else {
			/*
			if (empty($userInfo['keywords'])) {
				$link =& Piwi::CreateWidget('Link', _t('USERS_ACCOUNTHOME_CREATE_SUBSCRIPTIONS'),
											$GLOBALS['app']->Map->GetURLFor('Users', 'YourAccount'));
				$tpl->SetVariable('message', _t('USERS_ACCOUNTHOME_NO_SUBSCRIPTIONS').'&nbsp;'.$link->Get());
			}
			*/
		}
			
		$items_on_layout = array();
		$m = 0;
		foreach ($GLOBALS['app']->_ItemsOnLayout as $on_layout) {
			if (substr($on_layout, 0, strlen('_'.$gadget)) == '_'.$gadget) {
				$items_on_layout[] = $on_layout;
			} else if (substr($on_layout, 0, strlen('_total'.$gadget)) == '_total'.$gadget) {
				unset($GLOBALS['app']->_ItemsOnLayout[$m]);
			}
			$m++;
		}
		
		$GLOBALS['app']->_ItemsOnLayout[] = '_total'.$gadget.'_'.$limit;
		if ($only_comments === false) {
			$tpl->SetBlock('account_news/news_footer');
			$tpl->SetVariable('confirmCommentDelete', _t('GLOBAL_CONFIRM_DELETE_COMMENT'));
			$tpl->SetBlock('account_news/news_footer/news_count');
			$tpl->SetVariable('gadget', $gadget);
			$tpl->SetVariable('items_on_layout', implode(',', $items_on_layout));
			$tpl->SetVariable('count_on_layout', count($items_on_layout));
			$tpl->SetVariable('limit', $limit);
			if ((empty($get['id']) || $method == 'GetGroupComments') && !count($items_on_layout) <= 0) {
				$tpl->SetBlock('account_news/news_footer/news_count/news_more');
				$tpl->SetVariable('gadget', $gadget);
				$tpl->SetVariable('method', $method);
				$tpl->SetVariable('public', ($public === true ? 'true' : 'false'));
				$tpl->SetVariable('id', (is_null($request_id) ? 'null' : $request_id));
				$tpl->SetVariable('interactive', ($interactive === true ? 'true' : 'false'));
				$tpl->ParseBlock('account_news/news_footer/news_count/news_more');
			}
			$tpl->ParseBlock('account_news/news_footer/news_count');
			$tpl->ParseBlock('account_news/news_footer');
		}
		
		$tpl->ParseBlock('account_news');
		return $tpl->Get();
		
	}
	
	/**
     * Show single comment UI
     *
     * @access  public
     * @param  integer 	$id 	Comment ID to display
     * @param  string 	$gadget 	Gadget scope
     * @param  integer 	$replies_shown 	Number of replies shown initially
     * @param  boolean 	$interactive 	Interactive mode (allow adding new comment, etc)
     * @param  string 	$layout 	Layout mode (full/only_comments/mail)
     * @param  boolean 	$only_comments 	Only show comments, or show full UI
     * @return  string  HTML of template
     * @TODO 	Move some of this logic to UsersModel->GetComment()
     * @TODO 	Deprecate $only_comments, and use $layout = 'only_comments'
     */
    function ShowComment($id = '', $gadget = '', $replies_shown = 2, $interactive = true, $layout = 'full', $only_comments = false, $saved = true, $public = true)
    {
		$date = $GLOBALS['app']->loadDate();
		require_once JAWS_PATH . 'include/Jaws/User.php';
		require_once JAWS_PATH . 'include/Jaws/Image.php';
		$jUser = new Jaws_User;
		$request =& Jaws_Request::getInstance();
		$model = $GLOBALS['app']->LoadGadget('Users', 'Model');
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');		
        if ($only_comments === false) {
			if (file_exists(JAWS_PATH . 'gadgets/'.$gadget.'/resources/style.css')) {
				$GLOBALS['app']->Layout->AddHeadLink('gadgets/'.$gadget.'/resources/style.css', 'stylesheet', 'text/css', 'Users');
			}
			$GLOBALS['app']->Layout->AddHeadLink('gadgets/Users/resources/style.css', 'stylesheet', 'text/css', 'Users');
			$GLOBALS['app']->Layout->AddScriptLink($GLOBALS['app']->GetJawsURL() . '/libraries/prototype/prototype.js');
			$GLOBALS['app']->Layout->AddScriptLink($GLOBALS['app']->GetJawsURL() . '/libraries/prototype/scriptaculous.js');
			$GLOBALS['app']->Layout->AddScriptLink($GLOBALS['app']->GetJawsURL() . '/libraries/prototype/effects.js');
			$GLOBALS['app']->Layout->AddScriptLink($GLOBALS['app']->GetJawsURL() . '/libraries/prototype/controls.js');
			$GLOBALS['app']->Layout->AddScriptLink('include/Jaws/Ajax/Response.js');
			$GLOBALS['app']->Layout->AddHeadOther('<script type="text/javascript">
			var startTime = Math.round(new Date().getTime() / 1000);
			new PeriodicalExecuter(function(pe) {
				$$(".news-timestamp").each(function(element){
					currentTime = Math.round(new Date().getTime() / 1000);
					oldTimestamp = element.innerHTML;
					if (oldTimestamp == "A few seconds ago") {
						element.innerHTML = "Just a minute ago";
					} else if (oldTimestamp == "Just a minute ago") {
						element.innerHTML = "2 minute(s) ago";
					} else if (oldTimestamp.indexOf("minute(s) ago") > -1) {
						var minutes = (parseInt(oldTimestamp.replace(" minute(s) ago", ""), 10)+1);
						if (minutes > 60) {
							element.innerHTML = "One hour ago";
						} else {
							element.innerHTML = minutes + " minute(s) ago";
						}
					} else if (oldTimestamp == "One hour ago" && ((currentTime - startTime) >= 3600)) {
						element.innerHTML = "2 hour(s) ago";
					} else if (oldTimestamp.indexOf("hour(s) ago") > -1 && ((currentTime - startTime) >= 3600)) {
						var hours = (parseInt(oldTimestamp.replace(" hour(s) ago", ""), 10)+1);
						if (hours > 24) {
							element.innerHTML = "One day ago";
						} else {
							element.innerHTML = hours + " hour(s) ago";
						}
					} else if (oldTimestamp == "One day ago" && ((currentTime - startTime) >= 86400)) {
						element.innerHTML = "2 day(s) ago";
					} else if (oldTimestamp.indexOf("day(s) ago") > -1 && ((currentTime - startTime) >= 86400)) {
						var days = (parseInt(oldTimestamp.replace(" day(s) ago", ""), 10)+1);
						element.innerHTML = days + " day(s) ago";
					}
				});
			}, 60);
			</script>
			<script type="text/javascript">
				var messages_on_layout = new Array();
				var total_messages = new Array();
				var messages_limit = new Array();
			</script>'
			);
			$GLOBALS['app']->Layout->AddHeadOther('<script src="http://js.pusher.com/1.12/pusher.min.js" type="text/javascript"></script>
			 <script type="text/javascript">
				var pusher = new Pusher("9237ced3ff398dc663f0");
			 </script>');
		}
		$result_max = 50;
        
		if (empty($header)) {
			$header = 'Comment';
		}
		if (empty($gadget)) {
			$gadget = $request->get('fusegadget', 'get');
		}
		if (empty($gadget)) {
			$gadget = 'Users';
		}
		if (empty($id)) {
			$id = $request->get('id', 'get');
		}
		if (empty($id)) {
			require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
			return Jaws_HTTPError::Get(404);
		}
		
		//Get comments
		$news_items = array();
		
		$viewer_id = (int)$GLOBALS['app']->Session->GetAttribute('user_id');
		/*
		if ($viewer_id == 0) {
			$GLOBALS['app']->Layout->AddHeadOther('
				<style>.comments-form .comment-area .comment-entry {display: none;} .comments-form .comment-buttons {text-align: left;}</style>
			');
		}
		*/
		$title_options = '';
		// TODO: Don't require log-in for public messages 
		$userInfo = $jUser->GetUserInfoById($viewer_id, true, true, true, true);
		if (Jaws_Error::IsError($userInfo) || !isset($userInfo['id']) || empty($userInfo['id'])) {
			$GLOBALS['app']->Session->PushSimpleResponse('You must log-in to continue.');
			require_once JAWS_PATH . 'include/Jaws/Header.php';
			Jaws_Header::Location($GLOBALS['app']->GetSiteURL() . '/index.php?gadget=Users&action=DefaultAction&redirect_to='.urlencode($GLOBALS['app']->GetFullURL()));
			
			/*
			require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
			return Jaws_HTTPError::Get(404);
			*/
		}
						
		$update = $model->GetComment((int)$id, $gadget);
		if (Jaws_Error::isError($update)) {
			return $update;
		}
		$notify_users = $model->GetUsersSubscribedToComment((int)$id, $gadget, $saved);
		
		// Get all parents, if this is a reply
		$parent = $update;
		if ((int)$parent['parent'] > 0) {
			require_once JAWS_PATH . 'include/Jaws/Comment.php';
			$api = new Jaws_Comment($parent['gadget']);
			while ((int)$parent['parent'] > 0) {
				$parent = $api->GetComment((int)$parent['parent']);
				if (Jaws_Error::IsError($parent)) {
					return new Jaws_Error(_t('USERS_ERROR_GETTING_COMMENT'), _t('USERS_NAME'));
				}
			}
		}
			
		// Comment is shared with viewer?
		if (
			$parent['sharing'] == 'everyone' || 
			(($parent['sharing'] == 'friends' && $jUser->GetStatusOfUserInFriend($viewer_id, $parent['ownerid']) == 'active') || 
				in_array($viewer_id, $notify_users))
		) {
			$news_items[] = $parent;
		}
		
		// Load the template
		$tpl = new Jaws_Template('gadgets/Users/templates/');
		$tpl->Load('ShowComments.html');
		$tpl->SetBlock('account_news');
        if ($only_comments === false) {
			$tpl->SetBlock('account_news/news_header');
			$tpl->SetVariable('public', ($public === true ? 'true' : 'false'));
			$title = '<img height="36" border="0" align="middle" width="36" src="'.$GLOBALS['app']->getJawsURL().'/gadgets/Users/images/icon_updates.png">';
			$title .= '&nbsp;'.$header;
			$entry_button = '';
			$entry_field = '';
			$tpl->SetVariable('confirmCommentDelete', _t('GLOBAL_CONFIRM_DELETE_COMMENT'));
			$tpl->SetVariable('entry_button', $entry_button);
			$tpl->SetVariable('entry_field', $entry_field);
			$tpl->SetVariable('title', $title);
			$tpl->SetVariable('title_options', $title_options);
			$tpl->SetVariable('gadget', $gadget);
			$tpl->ParseBlock('account_news/news_header');
		}
		
		$msg_keys = array();
		if (!count($news_items) <= 0) {
			$i = 0;
			foreach ($news_items as $item) {
				if ($i < $result_max && !is_null($item) && (isset($item['id']) && !in_array('_'.$gadget.$item['id'], $GLOBALS['app']->_ItemsOnLayout))) {
					if ($item['parent'] == 0) {	
						$tpl->SetBlock('account_news/news_item');
						$thread = $this->ShowCommentThread($gadget, $item, $replies_shown, $interactive, $layout, 1, $public);
						if (Jaws_Error::isError($thread)) {
							$thread = $thread->GetMessage();
						}
						$tpl->SetVariable('thread', $thread);
						$tpl->ParseBlock('account_news/news_item');
						$i++;
						$GLOBALS['app']->_ItemsOnLayout[] = '_'.$gadget.$item['id'];
						//$msg_keys[] = $item['id'];
					}
				} else if ($i >= $result_max) {
					break;
				}
			}
		}
			
        if ($only_comments === false) {
			$tpl->SetBlock('account_news/news_footer');
			$tpl->SetVariable('confirmCommentDelete', _t('GLOBAL_CONFIRM_DELETE_COMMENT'));
			$tpl->SetBlock('account_news/news_footer/news_count');
			$items_on_layout = array();
			$m = 0;
			foreach ($GLOBALS['app']->_ItemsOnLayout as $on_layout) {
				if (substr($on_layout, 0, strlen('_'.$gadget)) == '_'.$gadget) {
					$items_on_layout[] = $on_layout;
				} else if (substr($on_layout, 0, strlen('_total'.$gadget)) == '_total'.$gadget) {
					unset($GLOBALS['app']->_ItemsOnLayout[$m]);
				}
				$m++;
			}
			$GLOBALS['app']->_ItemsOnLayout[] = '_total'.$gadget.'_'.$limit;
			$tpl->SetVariable('gadget', $gadget);
			$tpl->SetVariable('items_on_layout', implode(',', $items_on_layout));
			$tpl->SetVariable('count_on_layout', count($items_on_layout));
			if (!count($items_on_layout) <= 0) {
				$tpl->SetBlock('account_news/news_footer/news_count/news_more');
				$tpl->SetVariable('gadget', $gadget);
				$tpl->SetVariable('public', ($public === true ? 'true' : 'false'));
				$tpl->SetVariable('id', (is_null($id) ? 'null' : $id));
				$tpl->SetVariable('interactive', ($interactive === true ? 'true' : 'false'));
				$tpl->ParseBlock('account_news/news_footer/news_count/news_more');
			}
			$tpl->ParseBlock('account_news/news_footer/news_count');
			$tpl->ParseBlock('account_news/news_footer');
		}
		$tpl->ParseBlock('account_news');
				
		return $tpl->Get();
		
	}
	
	/**
     * Show single thread
     *
     * @access  public
     * @param  string 	$gadget 	Gadget scope
     * @param  array  item  comment array
     * @param  integer 	$replies_shown 	Number of replies shown initially
     * @param  boolean 	$interactive 	Interactive mode (allow adding new comment, etc)
     * @param  string 	$layout 	Layout mode (full/mail)
     * @return  string  XHTML template
     */
    function ShowCommentThread($gadget, $item = array(), $replies_shown = 2, $interactive = true, $layout = 'full', $item_count = 1, $public = true)
    {
		$viewer_id = (int)$GLOBALS['app']->Session->GetAttribute('user_id');
		$date = $GLOBALS['app']->loadDate();
		require_once JAWS_PATH . 'include/Jaws/Image.php';
		require_once JAWS_PATH . 'include/Jaws/User.php';
		$jUser = new Jaws_User;
		$model = $GLOBALS['app']->LoadGadget('Users', 'Model');
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');		
		$request =& Jaws_Request::getInstance();
				
        if (isset($GLOBALS['app']) && isset($GLOBALS['app']->Layout)) {
			$GLOBALS['app']->Layout->AddHeadLink('gadgets/Users/resources/style.css', 'stylesheet', 'text/css', 'Users');
			$GLOBALS['app']->Layout->AddScriptLink($GLOBALS['app']->GetJawsURL() . '/libraries/prototype/prototype.js');
			$GLOBALS['app']->Layout->AddScriptLink($GLOBALS['app']->GetJawsURL() . '/libraries/prototype/scriptaculous.js');
			$GLOBALS['app']->Layout->AddScriptLink($GLOBALS['app']->GetJawsURL() . '/libraries/prototype/effects.js');
			$GLOBALS['app']->Layout->AddScriptLink($GLOBALS['app']->GetJawsURL() . '/libraries/prototype/controls.js');
			$GLOBALS['app']->Layout->AddScriptLink('include/Jaws/Ajax/Response.js');
		}
		
		// Load the template
		$tpl = new Jaws_Template('gadgets/Users/templates/');
		if ($layout == 'boxy') {
			$tpl->Load('ShowBox.html');
			$namespace_single = 'box';
			$namespace = 'boxes';
		} else if ($layout == 'tiles') {
			$tpl->Load('ShowTile.html');
			$namespace_single = 'tile';
			$namespace = 'tiles';
		} else {
			$tpl->Load('ShowComment.html');
			$namespace_single = '';
			$namespace = 'news';
		}
		$tpl->SetBlock('account_news');

		if (is_array($item)) {
			if (Jaws_Gadget::IsGadgetUpdated($item['gadget'])) {
				if (isset($GLOBALS['app']) && isset($GLOBALS['app']->Layout)) {
					$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget='.$item['gadget'].'&action=Ajax&client=all&stub='.$item['gadget'].'Ajax');
					$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget='.$item['gadget'].'&action=AjaxCommonFiles');
					$GLOBALS['app']->Layout->AddScriptLink('gadgets/'.$item['gadget'].'/resources/client_script.js');
				}
			}
			//$item['replies'] = (int)$item['replies'];
			if ((int)$item['parent'] == 0 && substr($item['title'], 0, 8) != '{GADGET:') {
				$item['parent'] = (int)$item['parent'];
				$tpl->SetBlock('account_news/news_item');
				$tpl->SetVariable('public', ($public === true ? 'true' : 'false'));
				require_once JAWS_PATH . 'include/Jaws/Gravatar.php';
				$image = Jaws_Gravatar::GetGravatar('');
				$image_src = '';
				$email = '';
				$name = '';
				$username = '';
				$link = '';
				$title = '';
				$full_style = '';
				$preview_style = '';
				$msg = '';
				$msg_preview = '';
				$created = '';
				$activity = '';
				$preactivity = '';
				$replies = array();
				$replies_total = '';
				$social_html = '';
				$social_link = '';
				$type = '';
				
				if (isset($item['type']) && !empty($item['type'])) {
					$type = $item['type'];
				}
				$item['display_id'] = $gadget.$item['id'];
				if (empty($type)) {
					$type = 'message';
					if ($item['ownerid'] == 0 && $item['gadget'] == 'Users') {
						$type = 'system';
					} else if ($item['gadget_reference'] > 0 && $item['gadget'] == 'Users') {
						$type = 'direct';
					}
				}
				$type_string = $type." ".$namespace."-".$item['gadget']." ".$namespace."-ownerid-".$item['ownerid'];
				if (isset($item['createtime']) && !empty($item['createtime'])) {
					$type_string .= " ".$namespace."-created-".strtotime($item['createtime']);
				}
				if ($item['gadget'] != 'Users' && $item['gadget_reference'] > 0) {
					$type_string .= " ".$namespace."-".$item['type']."-".$item['gadget_reference'];
				}
				$tpl->SetVariable('realtype', $type);
				$tpl->SetVariable('realtype_string', ($layout == 'tiles' && $type == 'addusertogroup' ? 'Place' : ucfirst($type)));
				$tpl->SetVariable('type', $type_string);
				$tpl->SetVariable('id', $item['id']);
				$tpl->SetVariable('display_id', $item['display_id']);
				
				if (isset($item['permalink']) && !empty($item['permalink'])) {
					$permalink = $item['permalink'];
				} else {
					$permalink = $GLOBALS['app']->GetSiteURL() . '/' . 
						$GLOBALS['app']->Map->GetURLFor('Users', 'ShowComment', array('id' => $item['id'], 'fusegadget' => $item['gadget']));
				}
				if (isset($item['msg_preview']) && !empty($item['msg_preview'])) {
					$msg_preview = $item['msg_preview'];
				}
				if (isset($item['preactivity']) && !empty($item['preactivity'])) {
					$preactivity = $item['preactivity'];
				}
				if (isset($item['url']) && !empty($item['url'])) {
					$link = $item['url'];
				}
				if (isset($item['name']) && !empty($item['name'])) {
					$name = $xss->filter(strip_tags(htmlspecialchars_decode($item['name'], ENT_QUOTES)));
				}
				if (!empty($item['image']) && isset($item['image'])) {
					$image = $GLOBALS['app']->CheckImage($item['image']);
				} else if ((int)$item['ownerid'] == 0 || $item['type'] == 'system') {
					if (file_exists(JAWS_DATA . 'files/css/icon.png')) {
						$image = $GLOBALS['app']->getDataURL('', true). 'files/css/icon.png';
					} else if (file_exists(JAWS_DATA . 'files/css/logo.png')) {
						$image = $GLOBALS['app']->getDataURL('', true). 'files/css/logo.png';
					}
				} else if ((int)$item['ownerid'] > 0 && $item['type'] != 'system') {
					$info = $jUser->GetUserInfoById((int)$item['ownerid'], true, true, true, true);
					if (!Jaws_Error::IsError($info)) {
						$image = $xss->filter(strip_tags($jUser->getAvatar($info['username'], $info['email'])));
						if (isset($info['email']) && !empty($info['email'])) {
							$email = $info['email'];
						}
						if (isset($info['username']) && !empty($info['username'])) {
							$username = $info['username'];
						}
						if (isset($info['company']) && !empty($info['company'])) {
							$name = $info['company'];
						} else if (isset($info['nickname']) && !empty($info['nickname'])) {
							$name = $info['nickname'];
						}
						$groups  = $jUser->GetGroupsOfUser($info['id']);
						// Check if user is in profile group
						$show_link = false;
						if (!Jaws_Error::IsError($groups)) {
							foreach ($groups as $group) {
								if (
									strtolower($group['group_name']) == 'profile' && 
									(in_array($group['group_status'], array('active','founder','admin')))
								) {
									$show_link = true;
									break;
								}
							}
						}
						
						$link = ($show_link === true ? $GLOBALS['app']->GetSiteURL() . '/' . 
							$GLOBALS['app']->Map->GetURLFor('Users', 'AccountPublic', array('name' => $info['username'])) : '');
					}
					unset($info);
				}
				if (!empty($image)) {
					$image_src = (!empty($link) ? '<a href="'.$link.'">' : '').
						'<img src="'.$image.'" border="0" align="left" class="'.$namespace_single.'comment-image" />'.(!empty($link) ? '</a>' : '');
				} else if (!empty($email) && $item['type'] != 'system') {
					$image_src = (!empty($link) ? '<a href="'.$link.'">' : '').
						'<img src="'.$image.'" border="0" align="left" class="'.$namespace_single.'comment-image" />'.(!empty($link) ? '</a>' : '');
				}
				$tpl->SetVariable('image', $image_src);
				$news_info_style = '';
				if (empty($image_src)) {
					$news_info_style = ' style="padding-left: 0px;"';
				}
				
				$tpl->SetVariable('news_info_style', $news_info_style);
				if (isset($item['activity']) && !empty($item['activity'])) {
					$activity = '&#183;&nbsp;'.$item['activity'].'&nbsp;';
				}
				$tpl->SetVariable('activity', $activity);
				// Comment target is the same user as commenter? If so, change to "their own" string
				$realname = (substr($name, -6) == '&nbsp;' ? substr($name, 0, strlen($name)-6) : $name);
				if (!isset($item['commenters_count']) || (isset($item['commenters_count']) && $item['commenters_count'] == 1)) {
					if (!empty($realname) && $item['ownerid'] == $item['targetid'] && strpos($preactivity, '>'.$realname."</a>'s") !== false) {
						$preactivity = str_replace('>'.$realname."</a>'s", " style=\"color: inherit;\">their own</a>", $preactivity);
					} else if (!empty($realname) && $item['ownerid'] == $item['targetid'] && strpos($preactivity, " ".$realname."'s") !== false) {
						$preactivity = str_replace(" ".$realname."'s", " their own", $preactivity);
					} else if (!empty($realname) && $item['ownerid'] == $item['targetid'] && strpos($preactivity, "&nbsp;".$realname."'s") !== false) {
						$preactivity = str_replace("&nbsp;".$realname."'s", "&nbsp;their own", $preactivity);
					}
				}
				$tpl->SetVariable('preactivity', $preactivity);
				$link_start = '';
				if (!empty($link)) {
					$link_start = '<a href="'.$link.'">';
				}
				$link_end = '';
				if (!empty($link)) {
					$link_end = '</a>&nbsp;';
				} else if (!empty($name)) {
					$name .= '&nbsp;';
				}
				//$name = ((int)$item['ownerid'] > 0 ? $name.'&nbsp;' : '');
				$tpl->SetVariable('name', $name);
				$tpl->SetVariable('link_start', $link_start);
				$tpl->SetVariable('link_end', $link_end);
				if (isset($item['title']) && !empty($item['title'])) {
					$title = $item['title'];
				}
				$tpl->SetVariable('title', $title);
				$preview_style = ' style="display: none;"';
				// FIXME: Needs better separation of post IMGs and text, to allow text shortening
				if (
					$item['ownerid'] == 0 || !is_numeric($item['id']) || 
					in_array($item['type'], array('auto','important')) || 
					substr($item['msg_txt'], 0, 40) == '<p class="'.$namespace.'-update '.$namespace.'-photo-update"' && 
					strpos($item['msg_txt'], 'class="'.$namespace.'-update-icon '.$namespace.'-photo-update-icon"') !== false
				) {
					$msg = $item['msg_txt'];
				} else {
					$msg = strip_tags($item['msg_txt']);
					if (strlen($msg) > 200) {
						if (empty($msg_preview)) {
							$msg_preview = substr($msg, 0, 200).
								'&nbsp;<a class="'.$namespace.'-showhide" href="'.($layout == 'mail' ? $permalink : 
								'javascript:void(0);" onclick="toggleFullUpdate(\''.$item['display_id'].'\');').'">Read it</a>';
						}
						$msg .= '&nbsp;<a class="'.$namespace.'-showhide" href="'.($layout == 'mail' ? $permalink : 
							'javascript:void(0);" onclick="toggleFullUpdate(\''.$item['display_id'].'\');').'">Hide it</a>';
						$preview_style = '';
						$full_style = ' style="display: none;"';
					}
				}
				$tpl->SetVariable('full_style', $full_style);
				$tpl->SetVariable('preview_style', $preview_style);
												
				$tpl->SetVariable('message', $msg);
				$tpl->SetVariable('message_preview', $msg_preview);
				$comment_button = '';
				if (isset($item['createtime']) && !empty($item['createtime'])) {
					$created = $item['createtime'];
				}
				$tpl->SetVariable('created', (!empty($created) ? ($layout == 'mail' ? date("F j, g:i a", strtotime($created)).' UTC' : $date->Format($created, "since")).'&nbsp;' : ''));
				$view_replies = '';
				if (!isset($item['replies']) || (isset($item['replies']) && $item['replies'] !== false)) {
					// Does this item have threaded replies in the array?
					$username = $GLOBALS['app']->Session->GetAttribute('username');
					if ($layout == 'mail' || empty($username) || $username == 'anonymous') {
						//$GLOBALS['app']->Session->PushSimpleResponse('You must log-in to continue.');
						$commentsubmit =& Piwi::CreateWidget('Button', 'commentButton'.$item['display_id'], "Sign-in To Reply");
						$commentsubmit->AddEvent(ON_CLICK, "javascript: location.href = '".$GLOBALS['app']->GetSiteURL() . '/index.php'.
							'?gadget=Users&action=DefaultAction&redirect_to='.urlencode($GLOBALS['app']->GetFullURL())."';");
					} else {
						$commentsubmit =& Piwi::CreateWidget('Button', 'commentButton'.$item['display_id'], 'Ok');
						if ($item['gadget'] != 'Users' || !is_numeric($item['id'])) {
							$commentsubmit->AddEvent(ON_CLICK, 'javascript: save'.$item['gadget'].'Reply('.(is_numeric($item['id']) ? $item['id'] : 0).
								','.$item['gadget_reference'].',\''.$item['display_id'].'\');');
						} else {
							$commentsubmit->AddEvent(ON_CLICK, 'javascript: saveReply('.$item['id'].','.$viewer_id.', \''.$item['display_id'].'\');');
						}
					}
					$commentsubmit->SetStyle('min-width: 60px;');
					$comment_button = $commentsubmit->Get();
					if (isset($item['replies']) && is_array($item['replies'])) {
						$replies = $item['replies'];
					} else {
						$replies = $model->GetCommentsOfParent((int)$item['id'], 'approved', '');
					}
					$replies_count = count($replies);
					reset($replies);
					$replies_total = '&#183;&nbsp;<a class="total-'.$namespace_single.'comments" id="total-'.$namespace_single.'comments-'.$item['display_id'].'" href="'.
						($layout == 'mail' ? $permalink : 'javascript:void(0);" onclick="toggleAllComments(\''.$item['display_id'].'\');').
						'">'.$replies_count.' comments</a>&nbsp;';
					// Social
					if (
						Jaws_Gadget::IsGadgetUpdated('Social') && (is_numeric($item['id']) || !empty($permalink))
					) {
						// Let everyone know
						$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
						$res = $GLOBALS['app']->Shouter->Shout('onBeforeSocialSharing', array('url' => $permalink));
						if (!Jaws_Error::IsError($res) && (isset($res['url']) && !empty($res['url']))) {
							$permalink = $res['url'];
						}
						$socialLayout = $GLOBALS['app']->LoadGadget('Social', 'LayoutHTML');
						$social_html = '<div id="'.$namespace.'-social-'.$item['display_id'].'-container" style="display: none;">'.$socialLayout->Display(array(), $msg_preview, $permalink).'</div>'."\n";
						$social_html .= '<script type="text/javascript">Tips.add($("'.$namespace.'-social-'.$item['display_id'].'"), $("'.$namespace.'-social-'.$item['display_id'].'-container").innerHTML, {
								className: "slick",
								showOn: "click",
								hideTrigger: "tip",
								hideOn: "mouseout",
								stem: false,
								delay: false,
								tipJoint: [ "center", "top" ],
								target: $("'.$namespace.'-social-'.$item['display_id'].'"),
								showEffect: "appear",
								offset: [ 0, ((-10)+(Prototype.Browser.IE === false && $$("html")[0].style.marginTop != "" && $$("html")[0].style.marginTop != "0px" ? parseFloat($$("html")[0].style.marginTop.replace("px", "")) : 0)) ]
							});</script>';
						$social_link = '&#183;&nbsp;<a class="'.$namespace.'-social" id="'.$namespace.'-social-'.$item['display_id'].'" href="'.
						($layout == 'mail' ? $permalink : 'javascript:void(0);').
						'">Share</a>&nbsp;';
					}
				}
				$tpl->SetVariable('social_html', $social_html);
				$tpl->SetVariable('social_link', $social_link);
				$tpl->SetVariable('replies_total', $replies_total);
				if (!isset($item['replies']) || (isset($item['replies']) && $item['replies'] !== false)) {
					$tpl->SetBlock('account_news/news_item/comments');
					$tpl->SetVariable('id', $item['id']);
					$tpl->SetVariable('display_id', $item['display_id']);
					$tpl->SetVariable('comment_submit', $comment_button);
					$tpl->SetVariable('view_replies', $view_replies);
					if (!$replies_count <= 0) {
						// Sort result array
						$subkey = 'createtime'; 
						$temp_array = array();
						$temp_array[key($replies)] = array_shift($replies);
						foreach($replies as $key => $val){
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
						}
						$replies = ($layout == 'mail' ? $temp_array : array_reverse($temp_array));
						
						$r = 0;
						foreach ($replies as $reply) {
							if (!is_null($reply) && isset($reply['id'])) {
								$tpl->SetBlock('account_news/news_item/comments/comment');
								$reply['display_id'] = 'reply'.$reply['id'];
								if (!in_array($reply['display_id'], $GLOBALS['app']->_ItemsOnLayout)) {
									$GLOBALS['app']->_ItemsOnLayout[] = $reply['display_id'];
								} else {
									while (in_array($reply['display_id'], $GLOBALS['app']->_ItemsOnLayout)) {
										$reply['display_id'] .= Jaws_Utils::RandomText(2, 'unpronounceable', 'alphanumeric');
									}
									$GLOBALS['app']->_ItemsOnLayout[] = $reply['display_id'];
								}
								$image_reply = '';
								$image_reply_src = '';
								$email_reply = '';
								$name_reply = '';
								$username_reply = '';
								$link_reply = '';
								$title_reply = '';
								$full_style_reply = '';
								$preview_style_reply = '';
								$msg_reply = '';
								$msg_preview_reply = '';
								$link_reply = '';
								$created_reply = '';
								$reply_class = '';
								
								if ($r > ($replies_shown-1)) { 
									$reply_class = ' '.$namespace_single.'comment-hidden '.$namespace_single.'comment-hidden-'.$item['display_id'];
								} else {
									$r++;
								}
								
								$tpl->SetVariable('class', $reply_class);
								$preview_style_reply = ' style="display: none;"';
								$msg_reply = strip_tags($reply['msg_txt']);
								$msg_reply_preview = '';
								if (strlen($msg_reply) > 150) {
									$msg_reply_preview = substr($msg_reply, 0, 150).'&nbsp;<a class="'.$namespace_single.'comment-showhide" href="'.
									($layout == 'mail' ? $permalink : 'javascript:void(0);" '.
										'onclick="toggleFullComment(\''.$reply['display_id'].'\');').'">Read it</a>';
									$msg_reply .= '&nbsp;<a class="'.$namespace_single.'comment-showhide" href="'.
									($layout == 'mail' ? $permalink : 'javascript:void(0);" '.
										'onclick="toggleFullComment(\''.$reply['display_id'].'\');').'">Hide it</a>';
									$preview_style_reply = '';
									$full_style_reply = ' style="display: none;"';
								}
								
								if (isset($reply['url']) && !empty($reply['url'])) {
									$link_reply = $reply['url'];
								}
								if (isset($reply['name']) && !empty($reply['name'])) {
									$name_reply = $reply['name'];
								}
								if (isset($reply['createtime']) && !empty($reply['createtime'])) {
									$created_reply = $reply['createtime'];
								}
								
								$tpl->SetVariable('id', $reply['id']);
								$tpl->SetVariable('display_id', $reply['display_id']);
								$tpl->SetVariable('full_style', $full_style_reply);
								$tpl->SetVariable('preview_style', $preview_style_reply);
								$tpl->SetVariable('message', $msg_reply);
								$tpl->SetVariable('message_preview', $msg_reply_preview);
								
								if (isset($reply['image']) && !empty($reply['image'])) {
									$image_reply = $GLOBALS['app']->CheckImage($reply['image']);
								} else if ((int)$reply['ownerid'] > 0) {
									$info2 = $jUser->GetUserInfoById((int)$reply['ownerid'], true, true, true, true);
									if (!Jaws_Error::IsError($info2)) {
										$image_reply = $xss->filter(strip_tags($jUser->getAvatar($info2['username'], $info2['email'])));
										if (isset($info2['email']) && !empty($info2['email'])) {
											$email_reply = $info2['email'];
										}
										if (isset($info2['username']) && !empty($info2['username'])) {
											$username_reply = $info2['username'];
										}
										if (isset($info2['company']) && !empty($info2['company'])) {
											$name_reply = $info2['company'];
										} else if (isset($info2['nickname']) && !empty($info2['nickname'])) {
											$name_reply = $info2['nickname'];
										}
										$rgroups  = $jUser->GetGroupsOfUser($info2['id']);
										// Check if user is in profile group
										$show_link2 = false;
										if (!Jaws_Error::IsError($rgroups)) {
											foreach ($rgroups as $group) {
												if (
													strtolower($group['group_name']) == 'profile' && 
													in_array($group['group_status'], array('active','founder','admin'))
												) {
													$show_link2 = true;
													break;
												}
											}
										}
										$link_reply = ($show_link2 === true ? 
											$GLOBALS['app']->GetSiteURL() . '/' . 
											$GLOBALS['app']->Map->GetURLFor('Users', 'AccountPublic', array('name' => $info2['username'])) : '');
									}
									unset($info2);
								}
								$tpl->SetVariable('name', $name_reply);
								$link_start2 = '';
								if (!empty($link_reply)) {
									$link_start2 = '<a href="'.$link_reply.'" class="'.$namespace_single.'comment-name">';
								}
								$link_end2 = '';
								if (!empty($link_reply)) {
									$link_end2 = '</a>';
								}
								$tpl->SetVariable('link_start', $link_start2);
								$tpl->SetVariable('link_end', $link_end2);
								if (!empty($image_reply)) {
									$image_reply_src = '<div class="'.$namespace_single.'comment-image-holder">'.$link_start2.
										'<img src="'.$image_reply.'" border="0" align="left" class="'.$namespace_single.'comment-image" />'.$link_end2.'</div>';
								} else if (!empty($email_reply)) {
									$image_reply_src = '<div class="'.$namespace_single.'comment-image-holder">'.$link_start2.
										'<img src="'.Jaws_Gravatar::GetGravatar($email_reply).'" border="0" align="left" class="'.$namespace_single.'comment-image" />'.
										$link_end2.'</div>';							
								}
								$tpl->SetVariable('image', $image_reply_src);
								$tpl->SetVariable('created', ($layout == 'mail' ? date("F j, g:i a", strtotime($created_reply)).' UTC' : $date->Format($created_reply, "since")));
								$comment_delete = '';
								if ($interactive === true && $layout != 'mail') {
									if (
										($GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), 
											$GLOBALS['app']->Session->GetAttribute('groups'), 'Users', 'ManageUsers') || 
										($reply['ownerid'] == $GLOBALS['app']->Session->GetAttribute('user_id') && 
										(is_null($reply['delete']) || !isset($reply['delete']) || $reply['delete'] === true))) || 
										$item['ownerid'] == $GLOBALS['app']->Session->GetAttribute('user_id')
									) {
										$tpl->SetBlock('account_news/news_item/comments/comment/comment_delete');
										$tpl->SetVariable('id', $reply['id']);
										$tpl->SetVariable('display_id', $reply['display_id']);
										$tpl->ParseBlock('account_news/news_item/comments/comment/comment_delete');
									}
								}
								$tpl->ParseBlock('account_news/news_item/comments/comment');
							}
						}
						reset($replies);
					}
					$tpl->ParseBlock('account_news/news_item/comments');
				}
				if ($item['gadget'] != 'Users' || !is_numeric($item['id'])) {
					$poll_id = $item['gadget'].'_'.(is_numeric($item['id']) ? $item['id'] : 0).'_'.$item['gadget_reference'];
				} else {
					$poll_id = $item['gadget'].'_'.$item['id'].'_'.$item['ownerid'];
				}
				$tpl->SetVariable('poll_id', $poll_id);
				if (($layout != 'mail' && ($GLOBALS['app']->Session->IsAdmin() || $GLOBALS['app']->Session->IsSuperAdmin())) || $interactive === true) {
					if (isset($item['edit_links']) && is_array($item['edit_links']) && !count($item['edit_links']) <= 0) {
						reset($item['edit_links']);
						$tpl->SetVariable('edit_or_delete', 'edit');
						$tpl->SetBlock('account_news/news_item/news_edit');
						$tpl->SetVariable('id', $item['id']);
						$tpl->SetVariable('display_id', $item['display_id']);
						$l = 1;
						foreach ($item['edit_links'] as $edit_link) {
							if (
								isset($edit_link['url']) && !empty($edit_link['url']) && 
								isset($edit_link['title']) && !empty($edit_link['title']) 
							) {
								$tpl->SetBlock('account_news/news_item/news_edit/item');
								$tpl->SetVariable('display_id', $item['display_id']);
								$tpl->SetVariable('num', $l);
								$tpl->SetVariable('url', $edit_link['url']);
								$tpl->SetVariable('title', $edit_link['title']);
								$tpl->ParseBlock('account_news/news_item/news_edit/item');
								$l++;
							}
						}
						$tpl->ParseBlock('account_news/news_item/news_edit');
					} else {
						if (
							is_numeric($item['id']) && $item['type'] != 'important' && 
							(!isset($item['replies']) || (isset($item['replies']) && $item['replies'] !== false)) && 
							($GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), 
								$GLOBALS['app']->Session->GetAttribute('groups'), 'Users', 'ManageUsers') || 
								((int)$item['ownerid'] > 0 && (int)$item['ownerid'] == (int)$GLOBALS['app']->Session->GetAttribute('user_id')))
						) {
							$tpl->SetVariable('edit_or_delete', 'delete');
							$tpl->SetBlock('account_news/news_item/news_delete');
							$tpl->SetVariable('id', $item['id']);
							$tpl->SetVariable('display_id', $item['display_id']);
							$tpl->ParseBlock('account_news/news_item/news_delete');
						}
					}
				}
				$tpl->ParseBlock('account_news/news_item');
				
				// Remove this comment from User's new_messages
				$remove = $model->RemoveCommentFromNewMessages($item['gadget'].':'.$item['id'].':'.(isset($item['saved']) && $item['saved'] === true ? 'y' : 'n'), $viewer_id);
				/*
				if (Jaws_Error::IsError($remove)) {
					return $remove;
				}
				*/
			}
		}
		$tpl->ParseBlock('account_news');
		
		return $tpl->Get();
	}
	
	/**
     * Site-wide recommendations platform.
     *
     * @category  feature
     * @access  public
     * @return  string  XHTML template
     */
    function ShowRecommendations(
		$gadget = 'Users', $public = false, $id = null, $layout = 'full', 
		$method = 'GetRecommendations', $only_recommendations = false, $limit = 5
	) {
		require_once JAWS_PATH . 'include/Jaws/User.php';
		$jUser = new Jaws_User;
		$request =& Jaws_Request::getInstance();
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$model = $GLOBALS['app']->LoadGadget('Users', 'Model');
		
		if (is_null($id)) {
			$id = $get['id'];
			if (!empty($id)) {
				$id = explode(',', $id);
			}
		}
		$uid = (int)$GLOBALS['app']->Session->GetAttribute('user_id');
        $title = '';
		if ($only_recommendations === false) {
			$GLOBALS['app']->Layout->AddHeadLink('gadgets/Users/resources/style.css', 'stylesheet', 'text/css', 'Users');
			$GLOBALS['app']->Layout->AddScriptLink($GLOBALS['app']->GetJawsURL() . '/libraries/prototype/prototype.js');
			$GLOBALS['app']->Layout->AddScriptLink($GLOBALS['app']->GetJawsURL() . '/libraries/prototype/scriptaculous.js');
			$GLOBALS['app']->Layout->AddScriptLink($GLOBALS['app']->GetJawsURL() . '/libraries/prototype/effects.js');
			$GLOBALS['app']->Layout->AddScriptLink($GLOBALS['app']->GetJawsURL() . '/libraries/prototype/controls.js');
			$GLOBALS['app']->Layout->AddScriptLink('include/Jaws/Ajax/Response.js');
			if (file_exists(JAWS_PATH . 'gadgets/'.$gadget.'/resources/style.css')) {
				$GLOBALS['app']->Layout->AddHeadLink('gadgets/'.$gadget.'/resources/style.css', 'stylesheet', 'text/css', 'Users');
			}
			$title = _t('USERS_RECOMMENDATIONS');
		}
		
		$site_name = $GLOBALS['app']->Registry->Get('/config/site_name');
		$site_url = $GLOBALS['app']->GetSiteURL();
		if (empty($site_name)) {
			$site_name = str_replace(array('http://', 'https://'), '', $site_url);
		}
		
		$last_count = (-1);
		$new_count = 0;
		$i = 1;
		$result_max = (!is_null($id) && is_array($id) ? 9999 : 10);
		$news_items = array();
				
		$request_id = $id;
		$id = (is_null($id) || empty($id) ? $uid : $id);
		
		if ($gadget == 'Users') {
			// If public, get gadget_reference
			if ($public === true) {
				$userInfo = $jUser->GetUserInfoById((int)$id, true, true, true, true);
				if (Jaws_Error::IsError($userInfo) || !isset($userInfo['id']) || empty($userInfo['id'])) {
					$uname = $request->get('name', 'get');
					$userInfo = $jUser->GetUserInfoByName($uname, true, true, true, true);
					if (Jaws_Error::IsError($userInfo) || !isset($userInfo['id']) || empty($userInfo['id'])) {
						//return new Jaws_Error(_t('USERS_ACCOUNTPUBLIC_CANT_LOAD_PROFILE'), _t('USERS_NAME'));
					}
				}
				$id = $userInfo['id'];
			}
		}
		
		while ($new_count < $result_max && $new_count > $last_count) {
			$limit = ($limit * $i);
			$i++;
			$last_count = count($news_items);
			$news_items = array_merge($news_items, $model->GetRecommendations($gadget, $public, $id, $method, $limit, $result_max));
			$new_count = count($news_items);
		}
		
		
		// Load the template
		$tpl = new Jaws_Template('gadgets/Users/templates/');
		$tpl->Load('ShowRecommendations.html');
		$tpl->SetBlock('account_recommendations');
        
		if ($only_recommendations === false) {
			$tpl->SetBlock('account_recommendations/recommendation_header');
			$tpl->SetVariable('layout_class', " ".$layout);
			$tpl->SetVariable('title', $title);
			$tpl->SetVariable('gadget_id', strtolower($gadget));
			$tpl->SetVariable('gadget', $gadget);
			$tpl->ParseBlock('account_recommendations/recommendation_header');
		}
				
		//$tpl->SetVariable('title', "");
		//$tpl->SetVariable('title_options', "<b>Most Recent</b> &#149; Mine Only");
								
		$msg_keys = array();
		if (!count($news_items) > 0) {
			$tpl->SetBlock('account_recommendations/no_items');
			$tpl->SetVariable('message', _t('USERS_DIRECTORY_USERS_NOT_FOUND'));
			$tpl->ParseBlock('account_recommendations/no_items');
		} else {
			// Sort result array
			$subkey = 'sort_order'; 
			$temp_array = array();
			$temp_array[key($news_items)] = array_shift($news_items);
			foreach($news_items as $key => $val){
				$offset = 0;
				$found = false;
				foreach($temp_array as $tmp_key => $tmp_val) {
					$val[$subkey] = (string)$val[$subkey];
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
			$news_items = array_reverse($temp_array);
			
			$i = 0;
			foreach ($news_items as $item) {
				if ($i < $result_max && !is_null($item) && (isset($item['id']) && !in_array('_'.$gadget.$item['id'], $GLOBALS['app']->_ItemsOnLayout))) {
					if ($item['parent'] == 0) {	
						$tpl->SetBlock('account_recommendations/recommendation_item');
						$thread = $this->ShowRecommendationThread($gadget, $item, $interactive, $layout, 1, $public);
						if (Jaws_Error::IsError($thread)) {
							$thread = $thread->GetMessage();
						}
						$tpl->SetVariable('thread', $thread);
						$tpl->ParseBlock('account_recommendations/recommendation_item');
						$i++;
						$GLOBALS['app']->_ItemsOnLayout[] = '_'.$gadget.$item['id'];
						//$msg_keys[] = $item['id'];
					}
				} else if ($i >= $result_max) {
					break;
				}
			}
		}		
			
		$items_on_layout = array();
		$m = 0;
		foreach ($GLOBALS['app']->_ItemsOnLayout as $on_layout) {
			if (substr($on_layout, 0, strlen('_r_'.$gadget)) == '_r_'.$gadget) {
				$items_on_layout[] = $on_layout;
			} else if (substr($on_layout, 0, strlen('_r_total'.$gadget)) == '_r_total'.$gadget) {
				unset($GLOBALS['app']->_ItemsOnLayout[$m]);
			}
			$m++;
		}
		
		$GLOBALS['app']->_ItemsOnLayout[] = '_r_total'.$gadget.'_'.$limit;

		if ($only_recommendations === false) {
			$tpl->SetBlock('account_recommendations/recommendation_footer');
			$tpl->SetVariable('confirmRecommendationDelete', _t('GLOBAL_CONFIRM_DELETE_RECOMMENDATION'));
			$tpl->SetBlock('account_recommendations/recommendation_footer/recommendation_count');
			$tpl->SetVariable('gadget_id', strtolower($gadget));
			$tpl->SetVariable('gadget', $gadget);
			$tpl->SetVariable('items_on_layout', implode(',', $items_on_layout));
			$tpl->SetVariable('count_on_layout', count($items_on_layout));
			$tpl->SetVariable('limit', $limit);
			if (!count($items_on_layout) <= 0) {
				$tpl->SetBlock('account_recommendations/recommendation_footer/recommendation_count/recommendation_more');
				$tpl->SetVariable('gadget_id', strtolower($gadget));
				$tpl->SetVariable('gadget', $gadget);
				$tpl->SetVariable('method', $method);
				$tpl->SetVariable('public', ($public === true ? 'true' : 'false'));
				$tpl->SetVariable('id', (is_null($request_id) ? 'null' : $request_id));
				$tpl->SetVariable('interactive', ($interactive === true ? 'true' : 'false'));
				$tpl->ParseBlock('account_recommendations/recommendation_footer/recommendation_count/recommendation_more');
			}
			$tpl->ParseBlock('account_recommendations/recommendation_footer/recommendation_count');
			$tpl->ParseBlock('account_recommendations/recommendation_footer');
		}
		
		$tpl->ParseBlock('account_recommendations');
		return $tpl->Get();
		
	}

	/**
     * Show single thread
     *
     * @access  public
     * @param  string 	$gadget 	Gadget scope
     * @param  array  item  recommendation array
     * @param  boolean 	$interactive 	Interactive mode (allow adding new comment, etc)
     * @param  string 	$layout 	Layout mode (full/mail)
     * @return  string  XHTML template
     */
    function ShowRecommendationThread($gadget, $item = array(), $interactive = true, $layout = 'full', $item_count = 1, $public = true)
    {
		$viewer_id = (int)$GLOBALS['app']->Session->GetAttribute('user_id');
		$date = $GLOBALS['app']->loadDate();
		require_once JAWS_PATH . 'include/Jaws/Image.php';
		require_once JAWS_PATH . 'include/Jaws/User.php';
		$jUser = new Jaws_User;
		$model = $GLOBALS['app']->LoadGadget('Users', 'Model');
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');		
		$request =& Jaws_Request::getInstance();
				
        if (isset($GLOBALS['app']) && isset($GLOBALS['app']->Layout)) {
			$GLOBALS['app']->Layout->AddHeadLink('gadgets/Users/resources/style.css', 'stylesheet', 'text/css', 'Users');
			$GLOBALS['app']->Layout->AddScriptLink($GLOBALS['app']->GetJawsURL() . '/libraries/prototype/prototype.js');
			$GLOBALS['app']->Layout->AddScriptLink($GLOBALS['app']->GetJawsURL() . '/libraries/prototype/scriptaculous.js');
			$GLOBALS['app']->Layout->AddScriptLink($GLOBALS['app']->GetJawsURL() . '/libraries/prototype/effects.js');
			$GLOBALS['app']->Layout->AddScriptLink($GLOBALS['app']->GetJawsURL() . '/libraries/prototype/controls.js');
			$GLOBALS['app']->Layout->AddScriptLink('include/Jaws/Ajax/Response.js');
		}
		
		// Load the template
		$tpl = new Jaws_Template('gadgets/Users/templates/');
		if ($layout == 'boxy') {
			$tpl->Load('ShowRecommendationBox.html');
			$namespace_single = 'box';
			$namespace = 'boxes';
		} else if ($layout == 'tiles') {
			$tpl->Load('ShowRecommendationTile.html');
			$namespace_single = 'tile';
			$namespace = 'tiles';
		} else {
			$tpl->Load('ShowRecommendation.html');
			$namespace_single = '';
			$namespace = 'news';
		}
		$tpl->SetBlock('account_recommendation');

		if (is_array($item)) {	
			if (Jaws_Gadget::IsGadgetUpdated($item['gadget'])) {
				if (isset($GLOBALS['app']) && isset($GLOBALS['app']->Layout)) {
					$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget='.$item['gadget'].'&action=Ajax&client=all&stub='.$item['gadget'].'Ajax');
					$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget='.$item['gadget'].'&action=AjaxCommonFiles');
					$GLOBALS['app']->Layout->AddScriptLink('gadgets/'.$item['gadget'].'/resources/client_script.js');
				}
			}
			//$item['replies'] = (int)$item['replies'];
			if ((int)$item['parent'] == 0 && substr($item['title'], 0, 8) != '{GADGET:') {
				$item['parent'] = (int)$item['parent'];
				$tpl->SetBlock('account_recommendation/recommendation_item');
				$tpl->SetVariable('public', ($public === true ? 'true' : 'false'));
				require_once JAWS_PATH . 'include/Jaws/Gravatar.php';
				$image = Jaws_Gravatar::GetGravatar('');
				$image_src = '';
				$email = '';
				$name = '';
				$username = '';
				$link = '';
				$title = '';
				$full_style = '';
				$preview_style = '';
				$msg = '';
				$msg_preview = '';
				$created = '';
				$activity = '';
				$preactivity = '';
				$replies = array();
				$replies_total = '';
				$social_html = '';
				$social_link = '';
				$type = '';
				
				if (isset($item['type']) && !empty($item['type'])) {
					$type = $item['type'];
				}
				$item['display_id'] = $gadget.$item['id'];
				if (empty($type)) {
					$type = 'message';
					if ($item['ownerid'] == 0 && $item['gadget'] == 'Users') {
						$type = 'system';
					} else if ($item['gadget_reference'] > 0 && $item['gadget'] == 'Users') {
						$type = 'direct';
					}
				}
				$type_string = $type." ".$namespace."-".$item['gadget']." ".$namespace."-ownerid-".$item['ownerid'];
				if (isset($item['createtime']) && !empty($item['createtime'])) {
					$type_string .= " ".$namespace."-created-".strtotime($item['createtime']);
				}
				if ($item['gadget'] != 'Users' && $item['gadget_reference'] > 0) {
					$type_string .= " ".$namespace."-".$item['type']."-".$item['gadget_reference'];
				}
				$tpl->SetVariable('item_count', $item_count);
				$tpl->SetVariable('realtype', $type);
				$tpl->SetVariable('realtype_string', ($layout == 'tiles' && $type == 'addusertogroup' ? 'Place' : ucfirst($type)));
				$tpl->SetVariable('type', $type_string);
				$tpl->SetVariable('id', $item['id']);
				$tpl->SetVariable('display_id', $item['display_id']);
				
				if (isset($item['permalink']) && !empty($item['permalink'])) {
					$permalink = $item['permalink'];
				} else {
					$permalink = $GLOBALS['app']->GetSiteURL() . '/' . 
						$GLOBALS['app']->Map->GetURLFor('Users', 'ShowComment', array('id' => $item['id'], 'fusegadget' => $item['gadget']));
				}
				if (isset($item['msg_preview']) && !empty($item['msg_preview'])) {
					$msg_preview = $item['msg_preview'];
				}
				if (isset($item['preactivity']) && !empty($item['preactivity'])) {
					$preactivity = $item['preactivity'];
				}
				if (isset($item['url']) && !empty($item['url'])) {
					$link = $item['url'];
				}
				if (isset($item['name']) && !empty($item['name'])) {
					$name = $xss->filter(strip_tags(htmlspecialchars_decode($item['name'], ENT_QUOTES)));
				}
				/*
				if (!empty($item['image']) && isset($item['image'])) {
					$image = $GLOBALS['app']->CheckImage($item['image']);
				} else if ((int)$item['ownerid'] == 0 || $item['type'] == 'system') {
					if (file_exists(JAWS_DATA . 'files/css/icon.png')) {
						$image = $GLOBALS['app']->getDataURL('', true). 'files/css/icon.png';
					} else if (file_exists(JAWS_DATA . 'files/css/logo.png')) {
						$image = $GLOBALS['app']->getDataURL('', true). 'files/css/logo.png';
					}
				} else if ((int)$item['ownerid'] > 0 && $item['type'] != 'system') {
					$info = $jUser->GetUserInfoById((int)$item['ownerid'], true, true, true, true);
					if (!Jaws_Error::IsError($info)) {
						$image = $xss->filter(strip_tags($jUser->getAvatar($info['username'], $info['email'])));
						if (isset($info['email']) && !empty($info['email'])) {
							$email = $info['email'];
						}
						if (isset($info['username']) && !empty($info['username'])) {
							$username = $info['username'];
						}
						if (isset($info['company']) && !empty($info['company'])) {
							$name = $info['company'];
						} else if (isset($info['nickname']) && !empty($info['nickname'])) {
							$name = $info['nickname'];
						}
						$groups  = $jUser->GetGroupsOfUser($info['id']);
						// Check if user is in profile group
						$show_link = false;
						if (!Jaws_Error::IsError($groups)) {
							foreach ($groups as $group) {
								if (
									strtolower($group['group_name']) == 'profile' && 
									(in_array($group['group_status'], array('active','founder','admin')))
								) {
									$show_link = true;
									break;
								}
							}
						}
						
						$link = ($show_link === true ? $GLOBALS['app']->GetSiteURL() . '/' . 
							$GLOBALS['app']->Map->GetURLFor('Users', 'AccountPublic', array('name' => $info['username'])) : '');
					}
					unset($info);
				}
				if (!empty($image)) {
					$image_src = (!empty($link) ? '<a href="'.$link.'">' : '').
						'<img src="'.$image.'" border="0" align="left" class="'.$namespace_single.'comment-image" />'.(!empty($link) ? '</a>' : '');
				} else if (!empty($email) && $item['type'] != 'system') {
					$image_src = (!empty($link) ? '<a href="'.$link.'">' : '').
						'<img src="'.$image.'" border="0" align="left" class="'.$namespace_single.'comment-image" />'.(!empty($link) ? '</a>' : '');
				}
				$tpl->SetVariable('image', $image_src);
				*/
				$news_info_style = '';
				if (empty($image_src)) {
					$news_info_style = ' style="padding-left: 0px;"';
				}
				
				$tpl->SetVariable('news_info_style', $news_info_style);
				if (isset($item['activity']) && !empty($item['activity'])) {
					$activity = '&#183;&nbsp;'.$item['activity'].'&nbsp;';
				}
				$tpl->SetVariable('activity', $activity);
				// Comment target is the same user as commenter? If so, change to "their own" string
				$realname = (substr($name, -6) == '&nbsp;' ? substr($name, 0, strlen($name)-6) : $name);
				if (!isset($item['commenters_count']) || (isset($item['commenters_count']) && $item['commenters_count'] == 1)) {
					if (!empty($realname) && $item['ownerid'] == $item['targetid'] && strpos($preactivity, '>'.$realname."</a>'s") !== false) {
						$preactivity = str_replace('>'.$realname."</a>'s", " style=\"color: inherit;\">their own</a>", $preactivity);
					} else if (!empty($realname) && $item['ownerid'] == $item['targetid'] && strpos($preactivity, " ".$realname."'s") !== false) {
						$preactivity = str_replace(" ".$realname."'s", " their own", $preactivity);
					} else if (!empty($realname) && $item['ownerid'] == $item['targetid'] && strpos($preactivity, "&nbsp;".$realname."'s") !== false) {
						$preactivity = str_replace("&nbsp;".$realname."'s", "&nbsp;their own", $preactivity);
					}
				}
				$tpl->SetVariable('preactivity', $preactivity);
				/*
				$link_start = '';
				if (!empty($link)) {
					$link_start = '<a href="'.$link.'">';
				}
				$link_end = '';
				if (!empty($link)) {
					$link_end = '</a>&nbsp;';
				} else if (!empty($name)) {
					$name .= '&nbsp;';
				}
				//$name = ((int)$item['ownerid'] > 0 ? $name.'&nbsp;' : '');
				$tpl->SetVariable('name', $name);
				$tpl->SetVariable('link_start', $link_start);
				$tpl->SetVariable('link_end', $link_end);
				*/
				if (isset($item['title']) && !empty($item['title'])) {
					$title = $item['title'];
				}
				$tpl->SetVariable('title', $title);
				$preview_style = ' style="display: none;"';
				// FIXME: Needs better separation of post IMGs and text, to allow text shortening
				/*
				$msg = strip_tags($item['msg_txt']);
				if (strlen($msg) > 200) {
					if (empty($msg_preview)) {
						$msg_preview = substr($msg, 0, 200).
							'&nbsp;<a class="'.$namespace.'-showhide" href="'.($layout == 'mail' ? $permalink : 
							'javascript:void(0);" onclick="toggleFullUpdate(\''.$item['display_id'].'\');').'">Read it</a>';
					}
					$msg .= '&nbsp;<a class="'.$namespace.'-showhide" href="'.($layout == 'mail' ? $permalink : 
						'javascript:void(0);" onclick="toggleFullUpdate(\''.$item['display_id'].'\');').'">Hide it</a>';
					$preview_style = '';
					$full_style = ' style="display: none;"';
				}
				*/
				$tpl->SetVariable('full_style', $full_style);
				$tpl->SetVariable('preview_style', $preview_style);
												
				$tpl->SetVariable('message', $item['msg_txt']);
				$tpl->SetVariable('message_preview', $msg_preview);
				$comment_button = '';
				if (isset($item['createtime']) && !empty($item['createtime'])) {
					$created = $item['createtime'];
				}
				$tpl->SetVariable('created', (!empty($created) ? ($layout == 'mail' ? date("F j, g:i a", strtotime($created)).' UTC' : $date->Format($created, "since")).'&nbsp;' : ''));
				/*
				// Social
				if (
					Jaws_Gadget::IsGadgetUpdated('Social') && (is_numeric($item['id']) || !empty($permalink))
				) {
					// Let everyone know
					$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
					$res = $GLOBALS['app']->Shouter->Shout('onBeforeSocialSharing', array('url' => $permalink));
					if (!Jaws_Error::IsError($res) && (isset($res['url']) && !empty($res['url']))) {
						$permalink = $res['url'];
					}
					$socialLayout = $GLOBALS['app']->LoadGadget('Social', 'LayoutHTML');
					$social_html = '<div id="'.$namespace.'-social-'.$item['display_id'].'-container" style="display: none;">'.$socialLayout->Display(array(), $msg_preview, $permalink).'</div>'."\n";
					$social_html .= '<script type="text/javascript">Tips.add($("'.$namespace.'-social-'.$item['display_id'].'"), $("'.$namespace.'-social-'.$item['display_id'].'-container").innerHTML, {
							className: "slick",
							showOn: "click",
							hideTrigger: "tip",
							hideOn: "mouseout",
							stem: false,
							delay: false,
							tipJoint: [ "center", "top" ],
							target: $("'.$namespace.'-social-'.$item['display_id'].'"),
							showEffect: "appear",
							offset: [ 0, ((-10)+(Prototype.Browser.IE === false && $$("html")[0].style.marginTop != "" && $$("html")[0].style.marginTop != "0px" ? parseFloat($$("html")[0].style.marginTop.replace("px", "")) : 0)) ]
						});</script>';
					$social_link = '&#183;&nbsp;<a class="'.$namespace.'-social" id="'.$namespace.'-social-'.$item['display_id'].'" href="'.
					($layout == 'mail' ? $permalink : 'javascript:void(0);').
					'">Share</a>&nbsp;';
				}
				$tpl->SetVariable('social_html', $social_html);
				$tpl->SetVariable('social_link', $social_link);
				*/
				if ($item['gadget'] != 'Users' || !is_numeric($item['id'])) {
					$poll_id = $item['gadget'].'_'.(is_numeric($item['id']) ? $item['id'] : 0).'_'.$item['gadget_reference'];
				} else {
					$poll_id = $item['gadget'].'_'.$item['id'].'_'.$item['ownerid'];
				}
				$tpl->SetVariable('poll_id', $poll_id);
				if (($layout != 'mail' && ($GLOBALS['app']->Session->IsAdmin() || $GLOBALS['app']->Session->IsSuperAdmin())) || $interactive === true) {
					if (isset($item['edit_links']) && is_array($item['edit_links']) && !count($item['edit_links']) <= 0) {
						reset($item['edit_links']);
						$tpl->SetVariable('edit_or_delete', 'edit');
						$tpl->SetBlock('account_recommendation/recommendation_item/recommendation_edit');
						$tpl->SetVariable('id', $item['id']);
						$tpl->SetVariable('display_id', $item['display_id']);
						$l = 1;
						foreach ($item['edit_links'] as $edit_link) {
							if (
								isset($edit_link['url']) && !empty($edit_link['url']) && 
								isset($edit_link['title']) && !empty($edit_link['title']) 
							) {
								$tpl->SetBlock('account_recommendation/recommendation_item/recommendation_edit/item');
								$tpl->SetVariable('display_id', $item['display_id']);
								$tpl->SetVariable('num', $l);
								$tpl->SetVariable('url', $edit_link['url']);
								$tpl->SetVariable('title', $edit_link['title']);
								$tpl->ParseBlock('account_recommendation/recommendation_item/recommendation_edit/item');
								$l++;
							}
						}
						$tpl->ParseBlock('account_recommendation/recommendation_item/recommendation_edit');
					} else {
						if (
							is_numeric($item['id']) && $item['type'] != 'important' && 
							(!isset($item['replies']) || (isset($item['replies']) && $item['replies'] !== false)) && 
							($GLOBALS['app']->ACL->GetFullPermission($GLOBALS['app']->Session->GetAttribute('username'), 
								$GLOBALS['app']->Session->GetAttribute('groups'), 'Users', 'ManageUsers') || 
								((int)$item['ownerid'] > 0 && (int)$item['ownerid'] == (int)$GLOBALS['app']->Session->GetAttribute('user_id')))
						) {
							$tpl->SetVariable('edit_or_delete', 'delete');
							$tpl->SetBlock('account_recommendation/recommendation_item/recommendation_delete');
							$tpl->SetVariable('id', $item['id']);
							$tpl->SetVariable('display_id', $item['display_id']);
							$tpl->ParseBlock('account_recommendation/recommendation_item/recommendation_delete');
						}
					}
				}
				$tpl->ParseBlock('account_recommendation/recommendation_item');
				
				// Remove this comment from User's new_messages
				/*
				$remove = $model->RemoveCommentFromNewMessages($item['gadget'].':'.$item['id'].':'.(isset($item['saved']) && $item['saved'] === true ? 'y' : 'n'), $viewer_id);
				if (Jaws_Error::IsError($remove)) {
					return $remove;
				}
				*/
			}
		}
		$tpl->ParseBlock('account_recommendation');
		
		return $tpl->Get();
	}

	/**
     * User public profiles.
     *
     * @access  public
     * @param  string 	$username 	Username of profile to show
     * @return  string  XHTML template
     */
    function AccountPublic($username = '')
    {
		// Gadget requires HTTPS?
		$require_https = $GLOBALS['app']->Registry->Get('/gadgets/require_https');
		$site_ssl_url = $GLOBALS['app']->Registry->Get('/config/site_ssl_url');
		$gadget_requires_https = false;
		require_once JAWS_PATH . 'include/Jaws/Image.php';
        $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$request =& Jaws_Request::getInstance();
        $get     = $request->get(array('name'), 'get');
		if (empty($username)) {
			$username = $get['name'];
		}
		$GLOBALS['app']->Layout->AddHeadLink('libraries/window/themes/window/window.css', 'stylesheet', 'text/css');
		$GLOBALS['app']->Layout->AddHeadLink('libraries/window/themes/window/simpleblue.css', 'stylesheet', 'text/css');
		$GLOBALS['app']->Layout->AddHeadLink('libraries/window/themes/shadow/mac_shadow.css', 'stylesheet', 'text/css');
		$GLOBALS['app']->Layout->AddScriptLink('libraries/window/dist/window.js');
		$this->AjaxMe('client_script.js');
		// TODO: Move this into template
		$output_html = '<div class="gadget accountpublic">';
		require_once JAWS_PATH . 'include/Jaws/User.php';
        $jUser = new Jaws_User;
		$info = $jUser->GetUserInfoByName($username, true, true, true, true);
		if (!Jaws_Error::IsError($info)) {
			if (!isset($info['id'])) {
				$info = $jUser->GetUserInfoByID((int)$username, true, true, true, true);
				if (!isset($info['id'])) {
					require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
					return Jaws_HTTPError::Get(404);
				}
			}
			// Load Update hook
			$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
			$res = $GLOBALS['app']->Shouter->Shout(
				'onBeforeLoadAccountPublic', 
				array(
					'user_id' => $info['id']
				)
			);
			// Get all groups of user
			// TODO: Ability for user to set group page as profile page
			$groups  = $jUser->GetGroupsOfUser($info['id']);
			// Check if user is in profile group
			$no_profile = true;
			if (!Jaws_Error::IsError($groups)) {
				if (Jaws_Gadget::IsGadgetUpdated('CustomPage')) {
					foreach ($groups as $group) {
						if ($group['group_name'] == $info['username'] && $group['group_status'] == 'founder') {
							$pageModel = $GLOBALS['app']->LoadGadget('CustomPage', 'Model');
							$page = $pageModel->GetGroupHomePage($group['group_id']);
							if (!Jaws_Error::IsError($page) && isset($page['id'])) {
								require_once JAWS_PATH . 'include/Jaws/Header.php';
								Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'GroupPage', array('group' => $group['group_name'])));
							}
						}
					}
				}
				foreach ($groups as $group) {
					if (
						strtolower($group['group_name']) == 'profile' && 
						in_array($group['group_status'], array('active','founder','admin'))
					) {
						$no_profile = false;
						break;
					}
				}
			}
			$full_url = $GLOBALS['app']->GetFullURL();
			if (!empty($site_ssl_url) && (in_array(strtolower('Ecommerce'), explode(',', strtolower($require_https))) || strtolower('Ecommerce') == strtolower($require_https))) {
				$gadget_requires_https = true;
				if (!isset($_SERVER['HTTPS']) || strtolower($_SERVER['HTTPS']) != 'on') {
					if (!empty($full_url)) {
						require_once JAWS_PATH . 'include/Jaws/Header.php';
						Jaws_Header::Location(str_replace('http://'.str_replace('http://', '', $GLOBALS['app']->GetSiteURL()), 'https://'.str_replace('https://', '', $site_ssl_url), $full_url));
						//header('Location: '. str_replace('http://', 'https://', $full_url));
					}
				}
			}
			
			if (!$info['enabled'] || $no_profile === true) {
				require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
				return Jaws_HTTPError::Get(404);
			}
			if (file_exists(JAWS_DATA . 'files/css/users/'.$info['id'].'/custom.css')) {
				$GLOBALS['app']->Layout->AddHeadOther('<link rel="stylesheet" media="screen" type="text/css" href="'.$GLOBALS['app']->getDataURL('', true). 'files/css/users/'.$info['id'].'/custom.css" />');
			}
			$image_src = $xss->filter(strip_tags($jUser->getAvatar($info['username'], $info['email'])));
			$image = "<img class=\"merchant-logo\" src=\"".$image_src."\" border=\"0\" align=\"left\" />";
			$desc_header = '';
			$title = $xss->filter(strip_tags(htmlspecialchars_decode($info['username'], ENT_QUOTES)));
			if (isset($info['company']) && !empty($info['company'])) {
				$title = $xss->filter(strip_tags(htmlspecialchars_decode($info['company'], ENT_QUOTES)));
			} else if (isset($info['nickname']) && !empty($info['nickname'])) {
				$title = $xss->filter(strip_tags(htmlspecialchars_decode($info['nickname'], ENT_QUOTES)));
			}
			$GLOBALS['app']->Layout->SetTitle($title);
			/*
			$header .= "<h1 class=\"merchant-header-title\">".$title."&nbsp;</h1>";
			//$desc_header = "<div class=\"description-header\">".$title."</div>";
			//$output_html .= "<script type=\"text/javascript\">var sM = null; var dH = null; var dB = null; var dC = null; Event.observe(window, \"load\", function(){if ($('merchant-info-separator')) {sM = $('merchant-info-separator').style.marginTop;} if ($('merchant-description')) {dH = $('merchant-description').getHeight()+$('merchant-description').style.marginBottom.replace('px',''); dB = $('merchant-description').style.backgroundColor;dC = $('merchant-description').style.color;}});</script>";
			if (!empty($header)) {
				$output_html .= "<div class=\"merchant-header\">".$header."</div>";
			}
			*/
			$output_html .= "<div class=\"merchant-info\">";
			
			// show default user profile info, otherwise show gadget content
			$output_html .= $desc_header;
			$output_html .= "<div class=\"merchant-description\" id=\"merchant-description\">";
			$output_html .= $image;
			$output_html .= "<p class=\"merchant-desc-title\">".$title."<a name=\"merchant-desc-title\"></a></p>";
			if (isset($info['description']) && !empty($info['description'])) {
				$full_style = '';
				$preview_style = ' style="display: none;"';
				$description = $info['description'];
				if (strlen($description) > 600) {
					//$description_preview = substr($description, 0, 600).'&nbsp;<a class="desc-showhide" href="javascript:void(0);" onclick="toggleFullMerchantDescription(); $(\'merchant-description\').absolutize(); $(\'merchant-description\').style.zIndex = \'999999999\'; $(\'merchant-description\').style.backgroundColor = \'#FFFFFF\'; $(\'merchant-description\').style.color = \'#333333\';">Read more</a>';
					$description_preview = substr(strip_tags($description), 0, 600).'&nbsp;<a class="desc-showhide" href="javascript:void(0);" onclick="toggleFullMerchantDescription();">Read more</a>';
					//$description .= '&nbsp;<a class="desc-showhide" href="javascript:void(0);" onclick="toggleFullMerchantDescription();  $(\'merchant-description\').relativize(); $(\'merchant-description\').style.backgroundColor = dB; $(\'merchant-description\').style.color = dC; $(\'merchant-description\').style.zIndex = \'auto\';">Hide</a>';
					$preview_style = '';
					$full_style = ' style="display: none;"';
				}
				$output_html .= "
				<p id=\"merchant-desc-preview\"".$preview_style.">".$description_preview."</p>
				<div id=\"merchant-desc-full\"".$full_style.">".strip_tags($description, '<p><b><br>')."</div>
				";
			}
			$output_html .= "</div>";
			$output_html .= "<div class=\"merchant-info-separator\" id=\"merchant-info-separator\">&nbsp;</div>";
			$output_html .= "<div class=\"merchant-public-gadget\">";
			$output_html .= $this->ShowComments('Users', true, null, $title);
			$output_html .= "</div>";
			$output_html .= '</div>';			
		} else {
			$output_html .= '<div class="simple-response-msg">'.$info->GetMessage().'</div>';
		}
        $output_html .= '</div>';
		return $output_html;
	}
		
	/**
     * Group pages.
     *
     * @category  feature
     * @access  public
     * @param  string 	$grouppage 	Page ID or fast_url to retrieve
     * @param  string 	$groupname 	Group name
     * @return  string  XHTML template
     */
    function GroupPage($grouppage = '', $groupname = '')
    {
		if (!Jaws_Gadget::IsGadgetUpdated('CustomPage')) {
			require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
			return Jaws_HTTPError::Get(404);
		} else {
			require_once JAWS_PATH . 'include/Jaws/Image.php';
			$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
			$request =& Jaws_Request::getInstance();
			$get = $request->get(array('id','group'), 'get');
			if (empty($grouppage)) {
				$grouppage = $get['id'];
			}
			if (empty($groupname)) {
				$groupname = $get['group'];
			}
			if (empty($grouppage)) {
				$grouppage = 'Main';
				$_GET['id'] = 'Main';
				$GLOBALS['app']->_MainRequestId = 'Main';
				/*
				require_once JAWS_PATH . 'include/Jaws/Header.php';
				Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'GroupPage', array('id' => 'Main', 'group' => $groupname)));
				*/
			}
			$this->AjaxMe('client_script.js');
			//$output_html = '<div class="gadget '.($grouppage == 'Main' ? 'grouppublic ' : '').'grouppage">';
			$viewer_id = (int)$GLOBALS['app']->Session->GetAttribute('user_id');
			require_once JAWS_PATH . 'include/Jaws/User.php';
			$jUser = new Jaws_User;
			$info = $jUser->GetGroupInfoByName($groupname);
			if (!Jaws_Error::IsError($info)) {
				if (!isset($info['id'])) {
					$info = $jUser->GetGroupInfoById((int)$groupname);
					if (!isset($info['id'])) {
						require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
						return Jaws_HTTPError::Get(404);
					}
				}
				if (strpos(strtolower($groupname), '_owners') !== false || strpos(strtolower($groupname), '_users') !== false) {
					require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
					return Jaws_HTTPError::Get(404);
				}
				if (!empty($info['title'])) {
					$GLOBALS['app']->Layout->SetTitle($xss->filter(strip_tags(htmlspecialchars_decode($info['title'], ENT_QUOTES))));
				} else {
					$GLOBALS['app']->Layout->SetTitle($xss->filter(strip_tags(htmlspecialchars_decode($info['name'], ENT_QUOTES))));
				}
				
				// Load hook
				$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
				$res = $GLOBALS['app']->Shouter->Shout(
					'onBeforeLoadGroupPage', 
					array_merge($info, array('grouppage' => $grouppage, 'groupname' => $groupname))
				);
				
				if (isset($res['output']) && !empty($res['output'])) {
					return $res['output'];
				}
				
				// Show group's CustomPage
				$pageModel = $GLOBALS['app']->LoadGadget('CustomPage', 'Model');
				$pageHTML = $GLOBALS['app']->LoadGadget('CustomPage', 'LayoutHTML');
				$is_homepage = false;
				if ($grouppage == 'Main') {
					$is_homepage = true;
					$page = $pageModel->GetGroupHomePage($info['id']);
				} else {		
					$page = $pageModel->GetPage($grouppage, 'Users', 'GroupPage', $info['id']);
				}
				if (!Jaws_Error::IsError($page) && isset($page['id'])) {
					$founder_username = '';
					$founder_name = $GLOBALS['app']->Registry->Get('/config/site_name');
					$founder_description = $GLOBALS['app']->Registry->Get('/config/site_description');
					if (empty($founder_description)) {
						$founder_description = $GLOBALS['app']->Registry->Get('/config/site_slogan');
					}
					$founder_office = '';
					$founder_logo = (file_exists(JAWS_DATA . 'files/css/logo.png') ? $GLOBALS['app']->getDataURL('', true). 'files/css/logo.png' : '');
					$users = $jUser->GetUsersOfGroupByStatus($info['id'], 'founder');
					foreach ($users as $guser) {
						if (isset($guser['user_id']) && !empty($guser['user_id'])) {
							$user = $jUser->GetUserInfoById((int)$guser['user_id'], true, true, true, true);
							if (!Jaws_Error::IsError($user) && isset($user['id'])) {
								$founder_username = $user['username'];
								$founder_name = $user['nickname'];
								$founder_description = strip_tags($user['description'], '<p><b><br>');
								$founder_office = $user['office'];
								$founder_logo = $jUser->GetAvatar($user['username'], $user['email']);
							}
							break;
						}
					}
					// Output page HTML with "theme" replacement variables array
					$group_image = $jUser->GetGroupAvatar($info['id']);
					$is_friend = $jUser->UserIsGroupFriend($viewer_id, $info['id']);
					if ($is_friend === false && $jUser->GetStatusOfUserInFriend($viewer_id, $info['id']) != 'group_blocked') {
						$followButton =& Piwi::CreateWidget('Button', 'followButton', 'Subscribe', STOCK_ADD);
						$followButton->SetStyle('min-width: 80px;');
						$followButton->AddEvent(ON_CLICK, "javascript: location.href = 'index.php?gadget=Users&action=RequestFriendGroup&group=".$info['name']."&redirect_to=".urlencode($GLOBALS['app']->GetFullURL())."';");
						$follow = $followButton->Get();
					} else {
						$follow = "<button disabled=\"disabled\"  style=\"color: #999999; background: #EEEEEE; font-size: 12px;\" class=\"merchant-unfollow-button\">Request Sent</button>";
					}
					$is_member = $jUser->UserIsInGroup($viewer_id, $groupInfo['id']);
					if ($is_member === false && !in_array($jUser->GetStatusOfUserInGroup($viewer_id, $info['id']), array('denied','blocked'))) {
						$joinButton =& Piwi::CreateWidget('Button', 'joinButton', _t('USERS_USERS_REQUEST_GROUP_ACCESS'));
						$joinButton->SetStyle('min-width: 80px;');
						$joinButton->AddEvent(ON_CLICK, "javascript: location.href = 'index.php?gadget=Users&action=RequestGroupAccess&group=".$info['name']."&redirect_to=".urlencode($GLOBALS['app']->GetFullURL())."';");
						$join = $joinButton->Get();
					} else {
						$join = "<button disabled=\"disabled\"  style=\"color: #999999; background: #EEEEEE; font-size: 12px;\" class=\"merchant-unfollow-button\">Request Sent</button>";
					}
					return $pageHTML->Page(
						$page['id'], false, null, false, array(
							'GROUP_ID' => $info['id'], 
							'GROUP_NAME' => $info['name'], 
							'GROUP_IMAGE' => '<img align="left" src="'.$group_image.'" border="0" />', 
							'GROUP_REALNAME' => $info['title'], 
							'GROUP_DESCRIPTION' => strip_tags($info['description'], '<p><b><br>'), 
							'GROUP_FOUNDER_USERNAME' => $founder_username, 
							'GROUP_FOUNDER_NAME' => $founder_name, 
							'GROUP_FOUNDER_LOGO' => $founder_logo, 
							'GROUP_FOUNDER_OFFICE' => $founder_office, 
							'GROUP_FOUNDER_DESCRIPTION' => $founder_description, 
							'GROUP_FOLLOW_BUTTON' => $follow, 
							'GROUP_JOIN_BUTTON' => $join
						)
					);
				} else {
					//require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
					//return Jaws_HTTPError::Get(404);
					require_once JAWS_PATH . 'include/Jaws/Header.php';
					Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'UserDirectory', array('Users_gid' => $info['id'])));
				}
			} else {
				require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
				return Jaws_HTTPError::Get(404);
			}
			//$output_html .= '</div>';
			//return $output_html;
		}
	}
    
	/**
     * Email campaign builder.
     *
     * @category  feature
     * @access  public
     * @param  string 	$page 	Page ID or fast_url to retrieve
     * @return  string  XHTML
     * @TODO  Move to Social gadget
     */
    function EmailPage($page = '')
    {
		if (!Jaws_Gadget::IsGadgetUpdated('CustomPage')) {
			require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
			return Jaws_HTTPError::Get(404);
		} else {
			require_once JAWS_PATH . 'include/Jaws/Image.php';
			$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
			$request =& Jaws_Request::getInstance();
			$get = $request->get(array('id'), 'get');
			if (empty($page)) {
				$page = $get['id'];
			}
			if (!empty($page)) {
				$this->AjaxMe('client_script.js');
				
				// Show CustomPage
				$pageModel = $GLOBALS['app']->LoadGadget('CustomPage', 'Model');
				$pageHTML = $GLOBALS['app']->LoadGadget('CustomPage', 'HTML');
				$page = $pageModel->GetPage($page, 'Users', 'EmailPage', $page);
				if (!Jaws_Error::IsError($page) && isset($page['id'])) {
					return $pageHTML->Page($page['id']);
				} else {
					require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
					return Jaws_HTTPError::Get(404);
				}
			} else {
				require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
				return Jaws_HTTPError::Get(404);
			}
			//$output_html .= '</div>';
			//return $output_html;
		}
	}
    
	/**
     * Embed gadgets externally.
     *
     * @category 	feature
     * @access public
     * @return string 	HTML embed string
     */
    function EmbedGadget()
    {
		// Make output a real JavaScript file!
		header('Content-type: text/javascript'); 
		$output = "";		
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$request =& Jaws_Request::getInstance();
		$get = $request->get(array('embedid', 'embedmode', 'linkid', 'embedgadget', 'embedaction', 'embedbw', 'embedbstr', 'embedid', 'embedref', 'id', 'embedcss'), 'get');
			$direction = _t('GLOBAL_LANG_DIRECTION');
	        $dir  = $direction == 'rtl' ? '.' . $direction : '';
	        $brow = $GLOBALS['app']->GetBrowserFlag();
	        $brow = empty($brow)? '' : '.'.$brow;
	        $base_url = $GLOBALS['app']->GetSiteURL().'/';
	        			
			if (isset($get['id'])) {
				$id = $get['id'];
			} else {
				$id = $get['linkid'];
			}
			if (isset($get['embedbstr'])) {
				$embed_string = str_replace('%20', ' ', $get['embedbstr']);
			} else {
				$embed_string = "";
			}
			if (isset($get['embedbw'])) {
				$embed_button_width = $get['embedbw'];
			}
			if (isset($get['embedgadget'])) {
				$embedgadget = $get['embedgadget'];
			}
			if (isset($get['embedaction'])) {
				$embedaction = $get['embedaction'];
			}
			$display_id = md5($embedgadget.$id);
			$url_full = '';
			if (isset($get['embedref'])) {
				$url_full = urldecode($get['embedref']);
				$url_full = str_replace('__DOT__', '.', $url_full);
				$url_path = str_replace(array('http://', 'https://'), '', strtolower($url_full));
				/*
				if (substr(strtolower($url_path), 0, 4) == 'www.') {
					$url_path = substr($url_path, 4, strlen($url_path));
				}
				*/
				if (strpos($url_path, "/") !== false) {
					$url_domain = substr($url_path, 0, strpos($url_path, "/"));
				} else {
					$url_domain = $url_path;
				}
				$GLOBALS['app']->Session->SetAttribute('gadget_referer', $url_domain);
			}
			$url_css = '';
			if (isset($get['embedcss'])) {
				$url_css = $get['embedcss'];
			}
			if ($get['embedmode'] == 'mini') {
				$embed_height = "320px";
			} else if ($get['embedmode'] == 'tall' || $get['embedmode'] == 'button') {
				$embed_height = "1400px";
			} else {
				$embed_height = "900px";
			}
			$button_html = '';
			if ($get['embedmode'] == 'button') {
				$button_html .= "document.write(\"<style>\");\n";
				$button_html .= "document.write(\".syntacts-button {\");\n";
				$button_html .= "document.write(\"    background-color: #fff;\");\n";
				$button_html .= "document.write(\"    color: #000;\");\n";
				$button_html .= "document.write(\"    padding: 2px;\");\n";
				$button_html .= "document.write(\"    font-family: \\\"Lucida Grande\\\", Myriad, Tahoma, Helvetica, Arial, sans-serif;\");\n";
				$button_html .= "document.write(\"    font-size: small;\");\n";
				$button_html .= "document.write(\"} \");\n";
				$button_html .= "document.write(\"#syntacts-__embedgadget__Button-__id__  {\");\n";
				$button_html .= "document.write(\"    background: #fcfcfc url(". $GLOBALS['app']->GetJawsURL() . "/gadgets/ControlPanel/images/button.png) repeat-x top left;\");\n";
				$button_html .= "document.write(\"    color: #888a85;\");\n";
				$button_html .= "document.write(\"    border: 1px solid #babdb6;\");\n";
				$button_html .= "document.write(\"    border-top: 1px solid #d3d7cf;\");\n";
				$button_html .= "document.write(\"    border-left: 1px solid #d3d7cf;\");\n";
				$button_html .= "document.write(\"    font-weight: bold;\");\n";
				$button_html .= "document.write(\"    margin-left: 2px;\");\n";
				$button_html .= "document.write(\"    margin-right: 2px;\");\n";
				$button_html .= "document.write(\"    font-family: \\\"Lucida Grande\\\", Myriad, Tahoma, Helvetica, Arial, sans-serif;\");\n";
				$button_html .= "document.write(\"    cursor: pointer;\");\n";
				$button_html .= "document.write(\"    font-size: small;\");\n";
				$button_html .= "document.write(\"    min-height: 20px;\");\n";
				$button_html .= "document.write(\"    overflow: visible;\");\n";
				$button_html .= "document.write(\"} \");\n";
				$button_html .= "document.write(\"#syntacts-__embedgadget__Button-__id__:hover  {\");\n";
				$button_html .= "document.write(\"    background: #fafafa url(". $GLOBALS['app']->GetJawsURL() . "/gadgets/ControlPanel/images/button-hover.png) repeat-x top left;\");\n";
				$button_html .= "document.write(\"    color: #333;\");\n";
				$button_html .= "document.write(\"} \");\n";
				$button_html .= "document.write(\"#syntacts-__embedgadget__Button-__id__:active {\");\n";
				$button_html .= "document.write(\"    background: #e4e6e1 url(". $GLOBALS['app']->GetJawsURL() . "/gadgets/ControlPanel/images/button-hover.png) repeat-x top left;\");\n";
				$button_html .= "document.write(\"    color: #555;\");\n";
				$button_html .= "document.write(\"} \");\n";
				$button_html .= "document.write(\"</style>\");\n";
				$button_html .= "document.write(\"<div class='syntacts-buttonDiv' id='syntacts-__embedgadget__ButtonDiv-__id__' align='right'>\");\n";
				$button_html .= "document.write(\"	<input class='syntacts-button' id='syntacts-__embedgadget__Button-__id__' type='button' value='View ".$embed_string."' onClick='window.open(\\\"__embedurl__\\\");'>\");\n";
				$button_html .= "document.write(\"</div>\");\n";
			}

			$model = $GLOBALS['app']->LoadGadget('Users', 'Model');
			if ($get['embedid'] == 'showcase' && isset($url_path)) {
				$url_array = split('/', $url_path);
				if (isset($url_array[0]) && isset($url_array[1]) && isset($url_array[2]) && isset($url_array[3])) {
					if (strpos($url_array[0], '.') !== false) {
						$url_path = '/'.$url_array[1].'/'.$url_array[2].'/'.$url_array[3];
					} else {
						$url_path = $url_array[0].'/'.$url_array[1].'/'.$url_array[2].'/'.$url_array[3];
					}
					$showcase_id = $url_array[1];
					$row = $model->GetEmbedGadgetsByUrl($url_path);
					if ($row) {
						$output .= "YAHOO.util.Event.onContentReady('bd', function() {\n";
						$i = 0;
						foreach($row as $gadget) {
							$url = $gadget['gadget_url'];
							$url_parts = explode('&', strtolower($url));
							foreach ($url_parts as $part) {
								if (substr($part, 0, 4) == '?id=' || substr($part, 0, 3) == 'id=') {
									$id = str_replace('id=', '', $part);
								} else if (substr($part, 0, 8) == '?gadget=' || substr($part, 0, 7) == 'gadget=') {
									$embedgadget = str_replace('gadget=', '', $part);
								}
							}	
							$display_id = md5($embedgadget.$id);
							$embed_url = $url."&referer=".$url_domain."&css=http://".$url_domain."/".$showcase_id."/img/agents/".$showcase_id."/custom_img/custom_css/custom.css";
							if ($button_html != '') {
								$button_html = str_replace("__embedurl__", $embed_url, $button_html);
								$button_html = str_replace("__embedgadget__", $embedgadget, $button_html);
								$button_html = str_replace("__id__", $display_id, $button_html);
							} else {
								if ($gadget['layout'] == 'top') {			
									$output .= "document.getElementById('bd').innerHTML = \"<script>".$button_html."</script><div class='syntacts-gadgetDiv' id='syntacts-".$embedgadget."Div-".$display_id."'><iframe id='".$embedgadget."_iframe_".$display_id."' name='".$embedgadget."Iframe".$display_id."' style='background: transparent url(); border-right: 0pt; border-top: 0pt; border-left: 0pt; border-bottom: 0pt; height: 300px; width: 100%;' height='300' src='".$embed_url."' frameborder='0' allowTransparency='true' scrolling='no'></iframe></div>\" + document.getElementById('bd').innerHTML;";
								} else {
									$output .= "document.getElementById('bd').innerHTML += \"<script>".$button_html."</script><div class='syntacts-gadgetDiv' id='syntacts-".$embedgadget."Div-".$display_id."'><iframe id='".$embedgadget."_iframe_".$display_id."' name='".$embedgadget."Iframe".$display_id."' style='background: transparent url(); border-right: 0pt; border-top: 0pt; border-left: 0pt; border-bottom: 0pt; height: 300px; width: 100%;' height='300' src='".$embed_url."' frameborder='0' allowTransparency='true' scrolling='no'></iframe></div>\";";
								}
							}
							$i++;
						}
						$output .= "});\n";
					}
				}
			} else if (isset($get['embedgadget']) && isset($get['embedaction']) && isset($id)) {
				if(empty($url_css) && strpos(strtolower($url_path), "showcasere.com") !== false) {
					$url_array = split('/', $url_path);
					if (isset($url_array[1]) && !empty($url_array[1])) {
						$showcase_id = $url_array[1];
						$url_css = 'http://'.$url_domain.'/'.$showcase_id.'/img/agents/'.$showcase_id.'/custom_img/custom_css/custom.css';
					}
				}
				if (substr(strtolower($url_css), 0, 7) != 'http://' && substr(strtolower($url_css), 0, 8) != 'https://') {
					$url_css = 'http://'.$url_css;
				}
				$embed_url = $base_url."index.php?gadget=".$embedgadget."&action=".$embedaction."&referer=".$url_domain."&css=".urlencode($url_css).$GLOBALS['app']->getQuery(true);
				if ($button_html != '') {
					$button_html = str_replace("__embedurl__", $embed_url, $button_html);
					$button_html = str_replace("__embedgadget__", $embedgadget, $button_html);
					$button_html = str_replace("__id__", $display_id, $button_html);
					$output .= $button_html;
				} else {
					$output .= "document.write(\"<div class='syntacts-gadgetDiv' id='syntacts-".$embedgadget."Div-".$display_id."'>\");\n";
					$output .= "document.write(\"	<iframe id='".$embedgadget."-iframe-".$display_id."' name='".$embedgadget."Iframe".$display_id."' style='background: transparent url(); border-right: 0pt; border-top: 0pt; border-left: 0pt; border-bottom: 0pt; height: 300px; width: 100%;' height='300' src='".$embed_url."' frameborder='0' allowTransparency='true' scrolling='no'></iframe>\");\n";
					$output .= "document.write(\"</div>\");\n";
				}
			}
			if (isset($id) && isset($embedgadget)) {
				$output .= "function updateIFrame( height, target, resize ) {\n";
				$output .= "    resize = parseInt(resize);\n";
				$output .= "    target = target.replace(/_/g, '-'); if ( document.getElementById( target ) ) { var iframe = document.getElementById( target ); iframe.setAttribute( 'height', height ); iframe.style.height = height + 'px'; if (parseInt(resize)==1) { iframe.style.height=parseInt(iframe.offsetHeight/2)+'px'; iframe.onmouseover = function() { this.style.height=parseInt(this.offsetHeight*2)+'px'; }; iframe.onmouseout = function() { this.style.height=parseInt(this.offsetHeight/2)+'px'; }; } }\n";
				$output .= "}\n";
			}
       
		echo $output;
		exit;
	}
	
    /**
     * Accept user-generated gadget content.
     *
     * @category 	feature
     * @access public
     * @param  string 	$gadget 	Gadget name
     * @param  boolean 	$ajaxed 	AJAX enabled?
     * @param  boolean 	$greybox 	Load Greybox?
     * @param  boolean 	$show_menubar 	Show account actions menubar?
     * @return 	string 	HTML
     */
    function GetAccountHTML($gadget, $ajaxed = true, $greybox = true, $show_menubar = true)
	{
        $output_html = "";
        $output_html .= "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
		$output_html .= "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\">\n";
		$output_html .= " <head>\n";
		$output_html .= "  <title>".$gadget."</title>\n";
		$output_html .= "  <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n";
		$theme = $GLOBALS['app']->GetTheme();
		if (isset($theme['url']) && !empty($theme['url'])) {
			$themeHREF = $theme['url'];
		} else {
			$themeHREF = $GLOBALS['app']->GetJawsURL() . '/data/themes/default/';
		}
		$output_html .= "	<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" href=\"". $themeHREF ."style.css\" />\n";
		$output_html .= "	<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" href=\"". $themeHREF ."blog.css\" />\n";
		$output_html .= "	<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" href=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/ControlPanel/resources/public.css\" />\n";
		if ($greybox == true) {
			$output_html .= "	<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" href=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/greybox/gb_styles.css\" />\n";
			$output_html .= "	<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" href=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/window/themes/window/window.css\" />\n";
			$output_html .= "	<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" href=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/window/themes/window/simpleblue.css\" />\n";
			$output_html .= "	<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" href=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/window/themes/window/black_hud.css\" />\n";
			$output_html .= "	<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" href=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/window/themes/shadow/mac_shadow.css\" />\n";
		}
		$output_html .= "	<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" href=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/piwi/piwidata/css/default.css\" />\n";
		$output_html .= "	<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" href=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/autocomplete/autocomplete.css\" />\n";
		$output_html .= "	<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" href=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/".$gadget."/resources/style.css\" />\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/prototype/prototype.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/prototype/scriptaculous.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/prototype/effects.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/prototype/controls.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/window/dist/window.js\"></script>\n";
		//$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/tinymce/tiny_mce.js\"></script>\n";
		//$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/tinymce/jawsMCEWrapper.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/include/Jaws/Ajax/Response.js\"></script>\n";
		if ($ajaxed == true) {
			$output_html .= "	<script type=\"text/javascript\" src=\"".$GLOBALS['app']->GetSiteURL()."/gz.php?type=javascript&uri=".urlencode("index.php?gadget=".$gadget."&amp;action=Ajax&amp;client=all&amp;stub=".$gadget."Ajax")."\"></script>\n";
			$output_html .= "	<script type=\"text/javascript\" src=\"".$GLOBALS['app']->GetSiteURL()."/gz.php?type=javascript&uri=".urlencode("index.php?gadget=".$gadget."&amp;action=AjaxCommonFiles")."\"></script>\n";
			$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/".$gadget."/resources/client_script.js\"></script>\n";
		}
		$request =& Jaws_Request::getInstance();
        $action = $request->get('action', 'get');
		if ($gadget != 'Users') {
			$output_html .= "	<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" href=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/Users/resources/style.css\" />\n";
			$output_html .= "	<script type=\"text/javascript\" src=\"".$GLOBALS['app']->GetSiteURL()."/gz.php?type=javascript&uri=".urlencode("index.php?gadget=Users&amp;action=Ajax&amp;client=all&amp;stub=UsersAjax")."\"></script>\n";
			$output_html .= "	<script type=\"text/javascript\" src=\"".$GLOBALS['app']->GetSiteURL()."/gz.php?type=javascript&uri=".urlencode("index.php?gadget=Users&amp;action=AjaxCommonFiles")."\"></script>\n";
			$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/Users/resources/client_script.js\"></script>\n";
		}
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/js/global2.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/js/admin.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/xtree/xtree.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/ControlPanel/resources/script.js\"></script>\n";
		if ($gadget == "FlashGallery") {
			$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/js/swfobject.js\"></script>\n";
			$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/slideshow/slideshow-min.js\"></script>\n";
		}
		if ($greybox == true) {
			$output_html .= "	<script type=\"text/javascript\" src=\"".$GLOBALS['app']->GetSiteURL()."/gz.php?type=javascript&uri=".urlencode("index.php?gadget=CustomPage&action=account_SetGBRoot")."\"></script>\n";
			$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/greybox/AJS.js\"></script>\n";
			$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/greybox/AJS_fx.js\"></script>\n";
			$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/greybox/gb_scripts.js\"></script>\n";
			/*
			$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/window/dist/window.js\"></script>\n";
			*/
		}
		if ($gadget == 'Calendar' || $gadget == 'CustomPage') {
			$output_html .= "	<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" href=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/piwi/piwidata/css/calendar-blue.css\" />\n";
			$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/piwi/piwidata/js/jscalendar/calendar.js\"></script>\n";
			$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/piwi/piwidata/js/jscalendar/calendar-setup.js\"></script>\n";
			$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/piwi/piwidata/js/jscalendar/lang/calendar-en.js\"></script>\n";
		}
		$output_html .= "<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/opentip/opentip.js\"></script>
		<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/opentip/excanvas.js\"></script>
		<script type=\"text/javascript\">
			Event.observe(window, \"load\", function(){\$\$(\".menu_li_item\").each(function(element){
				checkSubMenus(element);
				Tips.add(element, (\$(element).down(\".ul_sub_menu\") ? \"<div class=\\\"ym-vlist\\\"><ul class=\\\"ul_sub_menu\\\">\"+\$(element).down(\".ul_sub_menu\").innerHTML+\"</ul></div>\" : ''), {
					className: (element.hasClassName(\"menu_super\") ? \"slick\" : \"ym-hideme\"),
					showOn: \"mouseover\",
					hideTrigger: \"tip\",
					hideOn: \"mouseout\",
					stem: false,
					delay: false,
					tipJoint: [ \"center\", \"top\" ],
					target: element,
					showEffect: \"appear\",
					offset: [ 0, ((-10)+(Prototype.Browser.IE === false && \$\$(\"html\")[0].style.marginTop != \"\" && \$\$(\"html\")[0].style.marginTop != \"0px\" ? parseFloat(\$\$(\"html\")[0].style.marginTop.replace(\"px\", \"\")) : 0)) ]
				});
			});});</script>";
		$output_html .= "	<!--[if lt IE 7]>\n";
		$output_html .= "	<script src=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/ControlPanel/resources/ie-bug-fix.js\" type=\"text/javascript\"></script>\n";
		$output_html .= "	<![endif]-->\n";
		if ($gadget != 'FileBrowser') {
			$output_html .= " <style>
			/* ----- MENUBAR ----- */
			.jaws-menubar {
				float: left;
				list-style: none;
				margin: 0 0 1.5em 0;
				padding: 0;
				font-size: 100%;
				width: 100%;
			}


			.jaws-menubar li {
				float: left;
				margin: 0 2px 0 0;
				padding: 0 0 0 8px;
				background: transparent none;
			}

			.jaws-menubar li:first-child {
				margin-left: 1em;
			}

			.jaws-menubar a {
				display: inline;
				padding: 4px 0px 4px 0px;
				border-bottom: none;
				color: #666;
				text-decoration: none;
				vertical-align: bottom;
				background: transparent none;
			}

			.jaws-menubar a:hover {
				color: #222;
			}

			.jaws-menubar img {
				border: 0;
				vertical-align: bottom;
			}

			.jaws-menubar li.selected {
			}

			.jaws-menubar li.selected a {
				padding-bottom: 5px;
				color: #000;
				font-weight: bold;
			}
			#menu-option-Logged {
				display: inline;
			}
			#menu-option-User {
				display: inline;
			}
			#menu-option-Profile {
				display: inline;
			}
			</style>";		
		}
		if (strpos($action, 'form') !== false) {
			$output_html .= "	<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" href=\"data/files/css/custom.css\" />\n";
		}
		$output_html .= " </head>\n";
		if ($ajaxed == true) {
			$output_html .= " <body onload=\"hideWorkingNotification();\" style=\"background: none transparent; color: #000000;\">\n";
			$output_html .= "  <!-- Working Notification -->  <div id=\"working_notification\"></div>\n";
			$output_html .= "   <script type=\"text/javascript\">\n";
			$output_html .= "    var loading_message = 'Loading...';\n";
			$output_html .= "    var navigate_away_message = 'You have unsaved changes in this page';\n";
			$output_html .= "   </script>\n";
			$output_html .= "  <!-- /Working Notification -->  <script type=\"text/javascript\">\n";
			$output_html .= "   showWorkingNotification();\n";
			$output_html .= "   </script>\n";
		} else {
			$output_html .= " <body style=\"background: none transparent; color: #000000;\">\n";
		}
		if ($gadget != 'FileBrowser' && ($gadget != 'Users' || ($gadget == 'Users' && $action == 'account_Groups')) && $show_menubar === true && strpos($action, 'form_post') === false) {
			if ($GLOBALS['app']->Session->Logged()) {
				$uLayout = $GLOBALS['app']->LoadGadget('Users', 'LayoutHTML');
				$output_html .= $uLayout->LoginLinks()."\n";
			}
		}
		$output_html .= "	<div class=\"account-container\">\n";
		//$output_html .= "<div id=\"msgbox-wrapper\"></div>\n";
		$output_html .= "__JAWS_GADGET__";
		if ($ajaxed == false) {
			$output_html = str_replace("showWorkingNotification();", '', $output_html);
		}
		$output_html .= "	</div>\n";
		$output_html .= " </body>\n";
		$output_html .= "</html>\n";
		return $output_html;
	}
		
	/**
     * Organic groups with access approvals by group admins or site admins.
     *
     * @category  feature
     * @access  public
     * @param   string  $group    The group ID or name to request access for
     * @param   string  $redirect_to    URL to redirect to on completion
     * @return  string  XHTML of template
     */
    function RequestGroupAccess($group = null, $redirect_to = null)
    {
		require_once JAWS_PATH . 'include/Jaws/Header.php';
		$request =& Jaws_Request::getInstance();
		if (is_null($group)) {
			$group = $request->get('group', 'get');
			if (is_null($group)) {
				$group = $request->get('group', 'post');
			}
		}
		if (is_null($redirect_to)) {
			$redirect_to = $request->get('redirect_to', 'get');
			if (is_null($redirect_to)) {
				$redirect_to = $request->get('redirect_to', 'post');
			}
		}
		$redirect_to = urldecode($redirect_to);
		if (!$GLOBALS['app']->Session->Logged()) {
            $GLOBALS['app']->Session->PushSimpleResponse('You must log-in to continue.');
			Jaws_Header::Location($GLOBALS['app']->GetSiteURL() . '/index.php?gadget=Users&action=DefaultAction&redirect_to='.urlencode('index.php?gadget=Users&action=RequestGroupAccess&group='.$group.(!empty($redirect_to) ? '&redirect_to='.urlencode($redirect_to) : '')));
		} else {
			if (!is_null($group)) {
				require_once JAWS_PATH . 'include/Jaws/User.php';
				$jUser = new Jaws_User;
				$info = $jUser->GetGroupInfoByName($group);
				if (!Jaws_Error::IsError($info)) {
					if (!isset($info['id'])) {
						$info = $jUser->GetGroupInfoById((int)$group);
						if (!isset($info['id'])) {
							require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
							return Jaws_HTTPError::Get(404);
						}
					}
					// Load the template
					$tpl = new Jaws_Template('gadgets/Users/templates/');
					$tpl->Load('RequestGroupAccess.html');
					$ugroups = $jUser->GetGroupsOfUser($GLOBALS['app']->Session->GetAttribute('user_id'));
					foreach ($ugroups as $ugroup) {
						if ($ugroup['group_id'] == $info['id']) {
							$tpl->SetBlock('already_in_group');
							$tpl->SetVariable('content', 'You\'ve already requested access to this group. <a href="'.(!empty($redirect_to) ? $redirect_to : $this->GetURLFor('DefaultAction')).'">Go to your Account Home</a>');
							$tpl->ParseBlock('already_in_group');
							return $tpl->Get();
						}
					}
					$tpl->SetBlock('group_access');
					$tpl->SetVariable('title', _t('USERS_USERS_REQUEST_GROUP_ACCESS',  (!empty($info['title']) ? $info['title'] : $info['name'])));
					$tpl->SetVariable('base_script', BASE_SCRIPT);
					$tpl->SetVariable('cancel_url', (!empty($redirect_to) ? $redirect_to : $this->GetURLFor('DefaultAction')));
					$tpl->SetVariable('group', $info['id']);
					$tpl->SetVariable('redirect_to', $redirect_to);
					$group_name =  (!empty($info['title']) ? $info['title'] : $info['name']);
					if ($group_name == 'no_profile') {
						$group_name = "our directory? Your contact info will be made public, so people can search and find you. Okay";
					}
					if ($group_name == 'profile') {
						$group_name = "our directory? Your profile will be made public, so people can search and find you, and follow your updates. Okay";
					}
					$tpl->SetVariable('request_string', _t('USERS_USERS_REQUEST_GROUP_STRING', $group_name));
					/*
					if ($response = $GLOBALS['app']->Session->PopSimpleResponse()) {
						$tpl->SetBlock('group_access/response');
						$tpl->SetVariable('msg', $response);
						$tpl->ParseBlock('group_access/response');
					}
					*/
					$tpl->ParseBlock('group_access');
					return $tpl->Get();
				}
			}
		}
		if (!empty($redirect_to)) {
			Jaws_Header::Location($redirect_to);
		} else {
			Jaws_Header::Location($this->GetURLFor('DefaultAction'));
		}
    }
	
	/**
     * Shows request group access result
     *
     * @access  public
     * @param   string  $group    The group ID or name requested access for
     * @param   string  $redirect_to    URL to redirect to on completion
     * @return  string  XHTML of template
     */
    function RequestedGroupAccess($group = null, $redirect_to = null)
    {
		require_once JAWS_PATH . 'include/Jaws/Header.php';
		$request =& Jaws_Request::getInstance();
		if (is_null($group)) {
			$group = $request->get('group', 'get');
			if (is_null($group)) {
				$group = $request->get('group', 'post');
			}
		}
		if (is_null($redirect_to)) {
			$redirect_to = $request->get('redirect_to', 'get');
			if (is_null($redirect_to)) {
				$redirect_to = $request->get('redirect_to', 'post');
			}
		}
		$redirect_to = urldecode($redirect_to);
		if (!$GLOBALS['app']->Session->Logged()) {
            $GLOBALS['app']->Session->PushSimpleResponse('You must log-in to continue.');
			Jaws_Header::Location($GLOBALS['app']->GetSiteURL() . '/index.php?gadget=Users&action=DefaultAction&redirect_to='.urlencode('index.php?gadget=Users&action=RequestedGroupAccess&group='.$group.(!empty($redirect_to) ? '&redirect_to='.urlencode($redirect_to) : '')));
		} else {
			if (!is_null($group)) {
				require_once JAWS_PATH . 'include/Jaws/User.php';
				$jUser = new Jaws_User;
				$info = $jUser->GetGroupInfoByName($group);
				if (!Jaws_Error::IsError($info)) {
					if (!isset($info['id'])) {
						$info = $jUser->GetGroupInfoById((int)$group);
						if (!isset($info['id'])) {
							require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
							return Jaws_HTTPError::Get(404);
						}
					}
					// Load the template
					/*
					$tpl = new Jaws_Template('gadgets/Users/templates/');
					$tpl->Load('RequestedGroupAccess.html');
					*/
					$ugroups = $jUser->GetGroupsOfUser($uid);
					if (!Jaws_Error::isError($ugroups)) {
						foreach ($ugroups as $ugroup) {
							if ($ugroup['group_id'] == $info['id']) {
								/*
								$tpl->SetBlock('already_in_group');
								$tpl->SetVariable('content', "You've already requested access to this group. <a href=\"".$this->GetURLFor('DefaultAction')."\">Go to your Account Home</a>");
								$tpl->ParseBlock('already_in_group');
								return $tpl->Get();
								*/
								$GLOBALS['app']->Session->PushSimpleResponse("You've already requested access to this group.");
								if (!empty($redirect_to)) {
									Jaws_Header::Location($redirect_to);
								} else {
									Jaws_Header::Location($this->GetURLFor('DefaultAction'));
								}
							}
						}
					}
					/*
					$tpl->SetBlock('group_access');
					$tpl->SetVariable('title', _t('USERS_USERS_REQUEST_GROUP_ACCESS'));
					*/
					// Add User to Group
					$res = $jUser->AddUserToGroup($GLOBALS['app']->Session->GetAttribute('user_id'), $info['id'], 'request');
					if ($res === true) {
						$GLOBALS['app']->Session->PushSimpleResponse(_t('USERS_USERS_REQUESTED_GROUP_ACCESS'));
					} else {
						$GLOBALS['app']->Session->PushSimpleResponse(_t('USERS_ERROR_REQUEST_GROUP_ACCESS'));
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
				} else {
					$GLOBALS['app']->Session->PushSimpleResponse(_t('USERS_ERROR_REQUEST_GROUP_ACCESS'));
				}
			}
		}
		if (!empty($redirect_to)) {
			Jaws_Header::Location($redirect_to);
		} else {
			Jaws_Header::Location($this->GetURLFor('DefaultAction'));
		}
    }

	/**
     * Group following/subscribing.
     *
     * @category  feature
     * @access  public
     * @param   string  $group    The group ID or name to request access for
     * @param   string  $redirect_to    URL to redirect to on completion
     * @return  string  XHTML of template
     */
    function RequestFriendGroup($group = null, $redirect_to = null)
    {
		require_once JAWS_PATH . 'include/Jaws/Header.php';
		$request =& Jaws_Request::getInstance();
		if (is_null($group)) {
			$group = $request->get('group', 'get');
			if (is_null($group)) {
				$group = $request->get('group', 'post');
			}
		}
		if (is_null($redirect_to)) {
			$redirect_to = $request->get('redirect_to', 'get');
			if (is_null($redirect_to)) {
				$redirect_to = $request->get('redirect_to', 'post');
			}
		}
		$redirect_to = urldecode($redirect_to);
		if (!$GLOBALS['app']->Session->Logged()) {
            $GLOBALS['app']->Session->PushSimpleResponse('You must log-in to continue.');
			Jaws_Header::Location($GLOBALS['app']->GetSiteURL() . '/index.php?gadget=Users&action=DefaultAction&redirect_to='.urlencode('index.php?gadget=Users&action=RequestFriendGroup&group='.$group.(!empty($redirect_to) ? '&redirect_to='.urlencode($redirect_to) : '')));
		} else if (!is_null($group)) {
			require_once JAWS_PATH . 'include/Jaws/User.php';
			$jUser = new Jaws_User;
			$info = $jUser->GetGroupInfoByName($group);
			if (Jaws_Error::IsError($info) || !isset($info['id'])) {
				$info = $jUser->GetGroupInfoById((int)$group);
				if (!isset($info['id'])) {
					require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
					return Jaws_HTTPError::Get(404);
				}
			}
			// Load the template
			$tpl = new Jaws_Template('gadgets/Users/templates/');
			$tpl->Load('RequestFriendGroup.html');
			$tpl->SetBlock('group_access');
			$tpl->SetVariable('title', _t('USERS_USERS_REQUEST_FRIEND_GROUP', (!empty($info['title']) ? $info['title'] : $info['name'])));
			$tpl->SetVariable('base_script', BASE_SCRIPT);
			$tpl->SetVariable('cancel_url', (!empty($redirect_to) ? $redirect_to : $this->GetURLFor('DefaultAction')));
			$tpl->SetVariable('group', $info['id']);
			$tpl->SetVariable('redirect_to', $redirect_to);
			$tpl->SetVariable('request_string', _t('USERS_USERS_REQUEST_FRIEND_GROUP_STRING', (!empty($info['title']) ? $info['title'] : $info['name'])));
			/*
			if ($response = $GLOBALS['app']->Session->PopSimpleResponse()) {
				$tpl->SetBlock('group_access/response');
				$tpl->SetVariable('msg', $response);
				$tpl->ParseBlock('group_access/response');
			}
			*/
			$tpl->ParseBlock('group_access');
			return $tpl->Get();
		}
		if (!empty($redirect_to)) {
			Jaws_Header::Location($redirect_to);
		} else {
			Jaws_Header::Location($this->GetURLFor('DefaultAction'));
		}
    }
	
	/**
     * Shows request friend group result
     *
     * @access  public
     * @param   string  $group    The group ID or name requested access for
     * @param   string  $redirect_to    URL to redirect to on completion
     * @param   string  $status    Status of request (group_active/group_blocked/group_request)
     * @return  string  XHTML of template
     */
    function RequestedFriendGroup($group = null, $redirect_to = null, $status = 'group_active')
    {
		require_once JAWS_PATH . 'include/Jaws/Header.php';
		$request =& Jaws_Request::getInstance();
		if (is_null($group)) {
			$group = $request->get('group', 'get');
			if (is_null($group)) {
				$group = $request->get('group', 'post');
			}
		}
		if (is_null($redirect_to)) {
			$redirect_to = $request->get('redirect_to', 'get');
			if (is_null($redirect_to)) {
				$redirect_to = $request->get('redirect_to', 'post');
			}
		}
		$redirect_to = urldecode($redirect_to);
		if (is_null($status)) {
			$status = $request->get('status', 'get');
			if (is_null($status)) {
				$status = $request->get('status', 'post');
			}
		}
		if (!$GLOBALS['app']->Session->Logged()) {
            $GLOBALS['app']->Session->PushSimpleResponse('You must log-in to continue.');
			Jaws_Header::Location($GLOBALS['app']->GetSiteURL() . '/index.php?gadget=Users&action=DefaultAction&redirect_to='.urlencode('index.php?gadget=Users&action=RequestedFriendGroup&group='.$group.(!empty($redirect_to) ? '&redirect_to='.urlencode($redirect_to) : '')));
		} else {
			if (!is_null($group)) {
				$my_friends = array();
				$add_friends = array();
				require_once JAWS_PATH . 'include/Jaws/User.php';
				$jUser = new Jaws_User;
				$info = $jUser->GetGroupInfoByName($group);
				if (Jaws_Error::IsError($info) || !isset($info['id']) || empty($info['id'])) {
					$info = $jUser->GetGroupInfoById((int)$group);
					if (!isset($info['id'])) {
						require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
						return Jaws_HTTPError::Get(404);
					}
				}
				/*
				// Load the template
				$tpl = new Jaws_Template('gadgets/Users/templates/');
				$tpl->Load('RequestedFriendGroup.html');
				*/
				$ufriends = $jUser->GetFriendsOfUser($GLOBALS['app']->Session->GetAttribute('user_id'), 'created', 'ASC', null, null, true);
				if (!Jaws_Error::isError($ufriends)) {
					foreach ($ufriends as $ufriend) {
						$my_friends[] = $ufriend['friend_id'];
					}
				}
				$ugroups = $jUser->GetUsersOfGroup($info['id']);
				if (!Jaws_Error::isError($ugroups)) {
					foreach ($ugroups as $ugroup) {
						if (
							!in_array($ugroup['user_id'], $my_friends) && 
							in_array($ugroup['group_status'], array('active','founder','admin'))
						) {
							$add_friends[] = $ugroup['user_id'];
						}
					}
				}
				/*
				$tpl->SetBlock('group_access');
				$tpl->SetVariable('title', _t('USERS_USERS_REQUEST_FRIEND_GROUP'));
				$GLOBALS['app']->Session->PushSimpleResponse(var_export($add_friends, true));
				*/
				$result = true;
				if (!count($add_friends) <= 0) {
					$result = false;
					foreach ($add_friends as $user) {
						// Add Friend to all Users of Group
						$res = $jUser->AddUserToFriend((int)$GLOBALS['app']->Session->GetAttribute('user_id'), (int)$user, str_replace('group_', '', $status));
						if ($res === true) {
							$result = true;
						} else {
							//$GLOBALS['app']->Session->PushSimpleResponse("Error while calling AddUserToFriend");
							$result = false;
							break;
						}
					}
				}
				if ($result === true) {
					// Add Friend to Group
					$res = $jUser->AddUserToFriend((int)$GLOBALS['app']->Session->GetAttribute('user_id'), $info['id'], $status);
					if ($res === true) {
						$result = true;
					} else {
						//$GLOBALS['app']->Session->PushSimpleResponse("Error while calling AddUserToFriend for group");
						$result = false;
					}
					// Let everyone know a user has friended a group
					$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
					$res2 = $GLOBALS['app']->Shouter->Shout('onFriendGroup', array('user_id' => $GLOBALS['app']->Session->GetAttribute('user_id'), 'group_id' => $info['id'], 'status' => $status));
					//$GLOBALS['app']->Session->PushSimpleResponse(var_export($res2, true));
					if (Jaws_Error::IsError($res2) || !$res2) {
						//$GLOBALS['app']->Session->PushSimpleResponse("Error while calling shouter onFriendGroup");
						//return $res2;
						$GLOBALS['app']->Session->PushSimpleResponse(_t('USERS_ERROR_REQUEST_GROUP_ACCESS'));
					} else {
						$GLOBALS['app']->Session->PushSimpleResponse(_t('USERS_USERS_REQUESTED_GROUP_ACCESS'));
					}
				} else {
					$GLOBALS['app']->Session->PushSimpleResponse(_t('USERS_ERROR_REQUEST_GROUP_ACCESS'));
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
			}
		}
		if (!empty($redirect_to)) {
			Jaws_Header::Location($redirect_to);
		} else {
			Jaws_Header::Location($this->GetURLFor('DefaultAction'));
		}
    }

	/**
     * User friending/subscribing.
     *
     * @category  feature
     * @access  public
     * @param   string  $friend_id    The friend id to request friendship for
     * @param   string  $redirect_to    URL to redirect to on completion
     * @return  string  XHTML of template
     */
    function RequestFriend($friend_id = null, $redirect_to = null)
    {
		require_once JAWS_PATH . 'include/Jaws/Header.php';
		$request =& Jaws_Request::getInstance();
		if (is_null($friend_id)) {
			$friend_id = $request->get('friend_id', 'get');
			if (is_null($friend_id)) {
				$friend_id = $request->get('friend_id', 'post');
			}
		}
		if (is_null($redirect_to)) {
			$redirect_to = $request->get('redirect_to', 'get');
			if (is_null($redirect_to)) {
				$redirect_to = $request->get('redirect_to', 'post');
			}
		}
		$redirect_to = urldecode($redirect_to);
		if (!$GLOBALS['app']->Session->Logged()) {
            $GLOBALS['app']->Session->PushSimpleResponse('You must log-in to continue.');
			Jaws_Header::Location($GLOBALS['app']->GetSiteURL() . '/index.php?gadget=Users&action=DefaultAction&redirect_to='.urlencode('index.php?gadget=Users&action=RequestFriend&friend_id='.$friend_id.(!empty($redirect_to) ? '&redirect_to='.urlencode($redirect_to) : '')));
		} else {
			if (!is_null($friend_id)) {
				require_once JAWS_PATH . 'include/Jaws/User.php';
				$jUser = new Jaws_User;
				$info = $jUser->GetUserInfoById((int)$friend_id, true, true, true, true);
				if (Jaws_Error::isError($info)) {
					$GLOBALS['app']->Session->PushSimpleResponse($info->GetMessage());
				} else if (!isset($info['id']) || empty($info['id'])) {
					$GLOBALS['app']->Session->PushSimpleResponse(_t('USERS_ERROR_REQUEST_GROUP_ACCESS'));
				} else {
					// Load the template
					$tpl = new Jaws_Template('gadgets/Users/templates/');
					$tpl->Load('RequestFriend.html');
					$ugroups = $jUser->GetFriendsOfUser($GLOBALS['app']->Session->GetAttribute('user_id'));
					if (!Jaws_Error::isError($ugroups)) {
						foreach ($ugroups as $ugroup) {
							if ($ugroup['friend_id'] == $info['id']) {
								$tpl->SetBlock('already_in_group');
								$tpl->SetVariable('content', 'You\'ve already requested to follow this user. <a href="'.(!empty($redirect_to) ? $redirect_to : $this->GetURLFor('DefaultAction')).'">Go to your Account Home</a>');
								$tpl->ParseBlock('already_in_group');
								return $tpl->Get();
							}
						}
					}
					$tpl->SetBlock('group_access');
					$tpl->SetVariable('title', _t('USERS_USERS_REQUEST_FRIEND'));
					$tpl->SetVariable('base_script', BASE_SCRIPT);
					$tpl->SetVariable('cancel_url', (!empty($redirect_to) ? $redirect_to : $this->GetURLFor('DefaultAction')));
					$tpl->SetVariable('friend_id', $info['id']);
					$tpl->SetVariable('redirect_to', $redirect_to);
					$tpl->SetVariable('request_string', _t('USERS_USERS_REQUEST_FRIEND_STRING', (isset($info['company']) && !empty($info['company']) ? $info['company'] : $info['nickname'])));
					/*
					if ($response = $GLOBALS['app']->Session->PopSimpleResponse()) {
						$tpl->SetBlock('group_access/response');
						$tpl->SetVariable('msg', $response);
						$tpl->ParseBlock('group_access/response');
					}
					*/
					$tpl->ParseBlock('group_access');
					return $tpl->Get();
				}
			}
		}
		if (!empty($redirect_to)) {
			Jaws_Header::Location($redirect_to);
		} else {
			Jaws_Header::Location($this->GetURLFor('DefaultAction'));
		}
    }
	
	/**
     * Shows request friend result
     *
     * @access  public
     * @param   string  $friend_id    The friend ID requested friendship for
     * @param   string  $redirect_to    URL to redirect to on completion
     * @param   string  $status   Status of request (active/blocked/request)
     * @return  string  XHTML of template
     */
    function RequestedFriend($friend_id = null, $redirect_to = null, $status = 'active')
    {
		require_once JAWS_PATH . 'include/Jaws/Header.php';
		$request =& Jaws_Request::getInstance();
		if (is_null($friend_id)) {
			$friend_id = $request->get('friend_id', 'get');
			if (is_null($friend_id)) {
				$friend_id = $request->get('friend_id', 'post');
			}
		}
		if (is_null($redirect_to)) {
			$redirect_to = $request->get('redirect_to', 'get');
			if (is_null($redirect_to)) {
				$redirect_to = $request->get('redirect_to', 'post');
			}
		}
		$redirect_to = urldecode($redirect_to);
		if (is_null($status)) {
			$status = $request->get('status', 'get');
			if (is_null($status)) {
				$status = $request->get('status', 'post');
			}
		}
		if (!$GLOBALS['app']->Session->Logged()) {
            $GLOBALS['app']->Session->PushSimpleResponse('You must log-in to continue.');
			Jaws_Header::Location($GLOBALS['app']->GetSiteURL() . '/index.php?gadget=Users&action=DefaultAction&redirect_to='.urlencode('index.php?gadget=Users&action=RequestedFriend&friend_id='.$friend_id.(!empty($redirect_to) ? '&redirect_to='.urlencode($redirect_to) : '')));
		} else {
			if (!is_null($friend_id)) {
				require_once JAWS_PATH . 'include/Jaws/User.php';
				$jUser = new Jaws_User;
				$info = $jUser->GetUserInfoById((int)$friend_id, true);
				if (Jaws_Error::isError($info)) {
					$GLOBALS['app']->Session->PushSimpleResponse($info->GetMessage());
				} else if (!isset($info['id']) || empty($info['id'])) {
					$GLOBALS['app']->Session->PushSimpleResponse(_t('USERS_ERROR_REQUEST_GROUP_ACCESS'));
				} else {
					/*
					// Load the template
					$tpl = new Jaws_Template('gadgets/Users/templates/');
					$tpl->Load('RequestedFriend.html');
					*/
					$ugroups = $jUser->GetFriendsOfUser($GLOBALS['app']->Session->GetAttribute('user_id'));
					//$GLOBALS['app']->Session->PushSimpleResponse(var_export($ugroups, true));
					if (!Jaws_Error::isError($ugroups)) {
						foreach ($ugroups as $ugroup) {
							if ($ugroup['friend_id'] == $info['id']) {
								/*
								$tpl->SetBlock('already_in_group');
								$tpl->SetVariable('content', 'You\'ve already requested to follow this user. <a href="'.$this->GetURLFor('DefaultAction').'">Go to your Account Home</a>');
								$tpl->ParseBlock('already_in_group');
								return $tpl->Get();
								*/
								$GLOBALS['app']->Session->PushSimpleResponse("You've already requested to follow this user.");
								if (!empty($redirect_to)) {
									Jaws_Header::Location($redirect_to);
								} else {
									Jaws_Header::Location($this->GetURLFor('DefaultAction'));
								}
							}
						}
					}
					/*
					$tpl->SetBlock('group_access');
					$tpl->SetVariable('title', _t('USERS_USERS_REQUEST_FRIEND'));
					*/
					// Add Friend to all Users of Group
					$res = $jUser->AddUserToFriend($GLOBALS['app']->Session->GetAttribute('user_id'), $info['id'], $status);
					$GLOBALS['app']->Session->PushSimpleResponse(var_export($res, true));
					if ($res === true) {
						$GLOBALS['app']->Session->PushSimpleResponse(_t('USERS_USERS_REQUESTED_GROUP_ACCESS'));
					} else {
						//$GLOBALS['app']->Session->PushSimpleResponse("Error while calling AddUserToFriend: user ".$GLOBALS['app']->Session->GetAttribute('user_id').", friend ".$info['id']);
						$GLOBALS['app']->Session->PushSimpleResponse(_t('USERS_ERROR_REQUEST_GROUP_ACCESS'));
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
				}
			}
		}
		if (!empty($redirect_to)) {
			Jaws_Header::Location($redirect_to);
		} else {
			Jaws_Header::Location($this->GetURLFor('DefaultAction'));
		}
    }

	/**
     * User friending/subscribing.
     *
     * @category  feature
     * @access  public
     * @param   string  $friend_id    The friend id to remove friendship for
     * @param   string  $redirect_to    URL to redirect to on completion
     * @return  string  XHTML of template
     */
    function RemoveFriend($friend_id = null, $redirect_to = null)
    {
		require_once JAWS_PATH . 'include/Jaws/Header.php';
		$request =& Jaws_Request::getInstance();
		if (is_null($friend_id)) {
			$friend_id = $request->get('friend_id', 'get');
			if (is_null($friend_id)) {
				$friend_id = $request->get('friend_id', 'post');
			}
		}
		if (is_null($redirect_to)) {
			$redirect_to = $request->get('redirect_to', 'get');
			if (is_null($redirect_to)) {
				$redirect_to = $request->get('redirect_to', 'post');
			}
		}
		$redirect_to = urldecode($redirect_to);
		if (!$GLOBALS['app']->Session->Logged()) {
            $GLOBALS['app']->Session->PushSimpleResponse('You must log-in to continue.');
			Jaws_Header::Location($GLOBALS['app']->GetSiteURL() . '/index.php?gadget=Users&action=DefaultAction&redirect_to='.urlencode('index.php?gadget=Users&action=RemoveFriend&friend_id='.$friend_id.(!empty($redirect_to) ? '&redirect_to='.urlencode($redirect_to) : '')));
		} else {
			if (!is_null($friend_id)) {
				require_once JAWS_PATH . 'include/Jaws/User.php';
				$jUser = new Jaws_User;
				$info = $jUser->GetUserInfoById((int)$friend_id, true, true, true, true);
				if (Jaws_Error::isError($info)) {
					$GLOBALS['app']->Session->PushSimpleResponse($info->GetMessage());
				} else if (!isset($info['id']) || empty($info['id'])) {
					$GLOBALS['app']->Session->PushSimpleResponse(_t('USERS_ERROR_REQUEST_GROUP_ACCESS'));
				} else {
					// Load the template
					$tpl = new Jaws_Template('gadgets/Users/templates/');
					$tpl->Load('RemoveFriend.html');
					$friend_status = $jUser->GetStatusOfUserInFriend($GLOBALS['app']->Session->GetAttribute('user_id'), $info['id']);
					if ($friend_status != 'active' && $friend_status != 'blocked') {
						$tpl->SetBlock('already_in_group');
						$tpl->SetVariable('content', 'You\'re not friends with this user. <a href="'.(!empty($redirect_to) ? $redirect_to : $this->GetURLFor('DefaultAction')).'">Go to your Account Home</a>');
						$tpl->ParseBlock('already_in_group');
						return $tpl->Get();
					}
					$tpl->SetBlock('group_access');
					$tpl->SetVariable('title', _t('USERS_USERS_REMOVE_FRIEND'));
					$tpl->SetVariable('base_script', BASE_SCRIPT);
					$tpl->SetVariable('cancel_url', (!empty($redirect_to) ? $redirect_to : $this->GetURLFor('DefaultAction')));
					$tpl->SetVariable('friend_id', $info['id']);
					$tpl->SetVariable('redirect_to', $redirect_to);
					$tpl->SetVariable('request_string', _t('USERS_USERS_REMOVE_FRIEND_STRING', (isset($info['company']) && !empty($info['company']) ? $info['company'] : $info['nickname'])));
					/*
					if ($response = $GLOBALS['app']->Session->PopSimpleResponse()) {
						$tpl->SetBlock('group_access/response');
						$tpl->SetVariable('msg', $response);
						$tpl->ParseBlock('group_access/response');
					}
					*/
					$tpl->ParseBlock('group_access');
					return $tpl->Get();
				}
			}
		}
		if (!empty($redirect_to)) {
			Jaws_Header::Location($redirect_to);
		} else {
			Jaws_Header::Location($this->GetURLFor('DefaultAction'));
		}
    }
	
	/**
     * Shows remove friend result
     *
     * @access  public
     * @param   string  $friend_id    The friend ID removed friendship for
     * @param   string  $redirect_to    URL to redirect to on completion
     * @return  string  XHTML of template
     */
    function RemovedFriend($friend_id = null, $redirect_to = null)
    {
		require_once JAWS_PATH . 'include/Jaws/Header.php';
		$request =& Jaws_Request::getInstance();
		if (is_null($friend_id)) {
			$friend_id = $request->get('friend_id', 'get');
			if (is_null($friend_id)) {
				$friend_id = $request->get('friend_id', 'post');
			}
		}
		if (is_null($redirect_to)) {
			$redirect_to = $request->get('redirect_to', 'get');
			if (is_null($redirect_to)) {
				$redirect_to = $request->get('redirect_to', 'post');
			}
		}
		$redirect_to = urldecode($redirect_to);
		if (!$GLOBALS['app']->Session->Logged()) {
            $GLOBALS['app']->Session->PushSimpleResponse('You must log-in to continue.');
			Jaws_Header::Location($GLOBALS['app']->GetSiteURL() . '/index.php?gadget=Users&action=DefaultAction&redirect_to='.urlencode('index.php?gadget=Users&action=RemovedFriend&friend_id='.$friend_id.(!empty($redirect_to) ? '&redirect_to='.urlencode($redirect_to) : '')));
		} else {
			if (!is_null($friend_id)) {
				require_once JAWS_PATH . 'include/Jaws/User.php';
				$jUser = new Jaws_User;
				$info = $jUser->GetUserInfoById((int)$friend_id, true);
				if (Jaws_Error::isError($info)) {
					$GLOBALS['app']->Session->PushSimpleResponse($info->GetMessage());
				} else if (!isset($info['id']) || empty($info['id'])) {
					$GLOBALS['app']->Session->PushSimpleResponse(_t('USERS_ERROR_REQUEST_GROUP_ACCESS'));
				} else {
					/*
					// Load the template
					$tpl = new Jaws_Template('gadgets/Users/templates/');
					$tpl->Load('RequestedFriend.html');
					*/
					$friend_status = $jUser->GetStatusOfUserInFriend($GLOBALS['app']->Session->GetAttribute('user_id'), $info['id']);
					if ($friend_status != 'active' && $friend_status != 'blocked') {
						$GLOBALS['app']->Session->PushSimpleResponse("You're not friends with this user.");
						if (!empty($redirect_to)) {
							Jaws_Header::Location($redirect_to);
						} else {
							Jaws_Header::Location($this->GetURLFor('DefaultAction'));
						}
					}
					/*
					$tpl->SetBlock('group_access');
					$tpl->SetVariable('title', _t('USERS_USERS_REQUEST_FRIEND'));
					*/
					// Remove Friend
					$res = $jUser->DeleteUserFromFriend($GLOBALS['app']->Session->GetAttribute('user_id'), $info['id']);
					if ($res === true) {
						$GLOBALS['app']->Session->PushSimpleResponse(_t('USERS_USERS_REQUESTED_GROUP_ACCESS'));
					} else {
						//$GLOBALS['app']->Session->PushSimpleResponse("Error while calling DeleteUserFromFriend: user ".$GLOBALS['app']->Session->GetAttribute('user_id').", friend ".$info['id']);
						$GLOBALS['app']->Session->PushSimpleResponse(_t('USERS_ERROR_REQUEST_GROUP_ACCESS'));
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
				}
			}
		}
		if (!empty($redirect_to)) {
			Jaws_Header::Location($redirect_to);
		} else {
			Jaws_Header::Location($this->GetURLFor('DefaultAction'));
		}
    }

    /**
     * Returns the Users RSS if its available
     *
     * @access  public
     * @param  integer 	$gid 	Group ID of users to retrieve
     * @return  string   RSS response string
     */
    function RSS($gid = null)
    {
        //if ($GLOBALS['app']->Registry->Get('/gadgets/Users/share_rss') == 'yes') {
			$request =& Jaws_Request::getInstance();
			if (is_null($gid)) {
				$gid = $request->get('gid', 'get');
			}
            $model = $GLOBALS['app']->LoadGadget('Users', 'Model');
            $rss = $model->MakeRSS(false, $gid);
            if (!Jaws_Error::isError($rss)) {
                header('Content-type: application/xml');
                return $rss;
            }
        /*
		} else {
            require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
            return Jaws_HTTPError::Get(404);
        }
		*/
    }

	/**
     * Public users lists and friends lists.
     *
     * @access  public
     * @param  boolean 	$public 	If true show public user directory, or false show friend directory 
     * @return  string  XHTML template
     */
    function UserDirectory($public = true, $id = null, $limit = null, $offSet = null, $uid = null, 
		$searchkeyword = '', $searchfilters = '', $searchhoods = '', $searchletter = '', $only_users = false)
    {
		require_once JAWS_PATH . 'include/Jaws/User.php';
		$jUser = new Jaws_User;
		require_once JAWS_PATH . 'include/Jaws/Image.php';
		$request =& Jaws_Request::getInstance();
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$GLOBALS['app']->Layout->AddHeadLink('gadgets/Users/resources/style.css', 'stylesheet', 'text/css');
        $this->AjaxMe('client_script.js');
		
		// Load the template
		$tpl = new Jaws_Template('gadgets/Users/templates/');
		$tpl->Load('UserDirectory.html');

		$news_items = array();

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
		$reqpublic = $request->get('Users_public', 'post');
		if (empty($reqpublic)) {
			$reqpublic = $request->get('Users_public', 'get');
		}
		if (!empty($reqpublic)) {
			$public = ($reqpublic == 'false' ? false : ($reqpublic == 'true' ? true : $public));
		}
		if (is_null($limit)) {
			$limit = $request->get('Users_limit', 'post');
			if (empty($limit)) {
				$limit = $request->get('Users_limit', 'get');
			}
			if (empty($limit)) {
				$limit = 12;
			} else {
				$limit = (int)$limit;
			}
		}
		if (is_null($offSet)) {
			$offSet = $request->get('Users_start', 'post');
			if (empty($offSet)) {
				$offSet = $request->get('Users_start', 'get');
			}
			if (empty($offSet)) {
				$offSet = 0;
			} else {
				$offSet = (int)$offSet;
			}
		}
		if (is_null($uid)) {
			$uid = $request->get('Users_uid', 'post');
			if (empty($uid)) {
				$uid = $request->get('Users_uid', 'get');
			}
			if (empty($uid)) {
				$uid = (int)$GLOBALS['app']->Session->GetAttribute('user_id');
			}
		}
		if (empty($searchkeyword)) {
			$searchkeyword = $request->get('Users_q', 'post');
			if (empty($searchkeyword)) {
				$searchkeyword = $request->get('Users_q', 'get');
			}
		}
		$searchkeyword = $xss->filter($searchkeyword);
		if (empty($searchfilters)) {
			$searchfilters = $request->get('Users_f', 'post');
			if (empty($searchfilters)) {
				$searchfilters = $request->get('Users_f', 'get');
			}
		}
		$searchfilters = (is_array($searchfilters) ? $xss->filter(implode(',',$searchfilters)) : $xss->filter($searchfilters));
		if (empty($searchhoods)) {
			$searchhoods = $request->get('Users_h', 'post');
			if (empty($searchhoods)) {
				$searchhoods = $request->get('Users_h', 'get');
			}
		}
		$searchhoods = (is_array($searchhoods) ? $xss->filter(implode(',',$searchhoods)) : $xss->filter($searchhoods));
		if (empty($searchletter)) {
			$searchletter = $request->get('Users_l', 'post');
			if (empty($searchletter)) {
				$searchletter = $request->get('Users_l', 'get');
			}
		}
		$numbers = array();
		$letters = array();
		if (strtolower($searchletter) == 'num') {
			$numbers = array('0','1','3','4','5','6','7','8','9');
		} else {
			$letters = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z');
		}
		$searchletter = (empty($searchletter) && empty($searchkeyword) && empty($searchfilters) && strtolower($id) == 'all' ? '' : (!empty($searchletter) && !in_array($searchletter, $letters) && strtolower($searchletter) != 'num' ? 'A' : $searchletter));

		/*
		var_dump($id);
		echo "\n";
		var_dump($searchkeyword);
		echo "\n";
		var_dump($searchfilters);
		echo "\n";
		var_dump($searchletter);
		echo "\n";
		*/
		
		// Load gadgets
		$StoreParents = array();
		if (Jaws_Gadget::IsGadgetUpdated('Store')) {
			$StoreModel = $GLOBALS['app']->LoadGadget('Store', 'Model');
			$StoreParents = $StoreModel->GetProductParents();
		}
		$PropertiesParents = array();
		if (Jaws_Gadget::IsGadgetUpdated('Properties')) {
			$PropertiesModel = $GLOBALS['app']->LoadGadget('Properties', 'Model');
			$PropertiesParents = $PropertiesModel->GetPropertyParents();
		}

		// Filters
		$requestedfilters = array();
		$requestedowners = array();
		$namefilters = array();
		if (!empty($searchfilters)) {
			$requestedfilters = explode(',',$searchfilters);
			foreach ($requestedfilters as $requestedfilter) {
				// Requested filter for a gadget? Should be in format:
				// "Gadget:GadgetMethod:GadgetID"
				if (strpos($requestedfilter, ':') !== false) {
					$filtergadget = explode(':', $requestedfilter);
					if (Jaws_Gadget::IsGadgetUpdated($filtergadget[0])) {
						$parents = ${"{$filtergadget[0]}Model"}->$filtergadget[1]((int)$filtergadget[2]);
						if (!Jaws_Error::IsError($parents)) {
							foreach($parents as $parent) {
								$requestedowners[] = (int)$parent['ownerid'];
							}
						}
					}
				} else {
					$namefilters[] = strtolower(preg_replace("[^A-Za-z0-9]", '', $requestedfilter));
				}
			}
		}
		
		// radius
		$radiusowners = array();
		if (Jaws_Gadget::IsGadgetUpdated('Maps')) {
			if (!empty($searchkeyword)) {
				$lnglat = Jaws_Utils::GetGeoLocation(urlencode($searchkeyword));
				if (
					isset($lnglat['region']) && !empty($lnglat['region']) && 
					isset($lnglat['longitude']) && !empty($lnglat['longitude']) && 
					isset($lnglat['latitude']) && !empty($lnglat['latitude'])
				) {
					$radius = $jUser->GetUsersWithinRadius($lnglat['longitude'], $lnglat['latitude'], $lnglat['region']);
					if (!Jaws_Error::IsError($radius)) {
						foreach ($radius as $rad) {
							$radiusowners[] = (int)$rad['id'];
						}
					}
				}
			}
		}
		
		/*
		var_dump($searchfilters);
		echo "\n";
		var_dump($namefilters);
		echo "\n";
		*/
		$n = 0;
		foreach ($namefilters as $namefilter) {
			if ($namefilter == 'retail') {
				unset($namefilters[$n]);
				$namefilters = array_values($namefilters);
				break;
			}
			$n++;
		}
		
		$user_directory_url = 'index.php?gadget=Users&action=UserDirectory';
		$user_directory_hash = '';
		
		if ($public === false) {
			$user_directory_url = 'index.php?gadget=Users&action=DefaultAction';
			$user_directory_hash = '#pane=Friends';
						
			// Show friend recommendations
			$tpl->SetBlock('recommendations');
			$tpl->SetVariable('recommendations_title', _t('USERS_DIRECTORY_FRIEND_RECOMMENDATIONS_TITLE'));
			$tpl->SetVariable('content', $this->ShowRecommendations('Users', false, $uid, 'full', 'GetFriendRecommendations', true));
			$tpl->ParseBlock('recommendations');
		}
		
		$tpl->SetBlock('users');
		if (!empty($id) && strtolower($id) != 'all' && (int)$id > 0) {
			$id = (int)$id;
			$groupInfo = $jUser->GetGroupInfoById($id);
			if (Jaws_Error::IsError($groupInfo) || !isset($groupInfo['id']) || empty($groupInfo['id'])) {
				require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
				return Jaws_HTTPError::Get(404);
			}
			/*
			if (empty($searchletter) && empty($searchkeyword) && empty($searchfilters)) {
				$searchletter = 'A';
				//reset($users);
			}
			*/
			$users = $jUser->GetUsersOfGroup(
				$id, $limit, $offSet, $searchletter, $searchkeyword, 
				$requestedowners, $radiusowners, $namefilters
			);
			if (Jaws_Error::IsError($users)) {
				$tpl->SetBlock('users/no_items');
				$tpl->SetVariable('message', $users->GetMessage());
				$tpl->ParseBlock('users/no_items');
				return $tpl->Get();
			} else {
				foreach ($users as $user) {
					if (
						($public === false && $jUser->GetStatusOfUserInFriend($uid, $user['user_id']) == 'active') || 
						($public === true &&
						in_array($user['group_status'], array('active','founder','admin')))						
					) {
						$userInfo = $jUser->GetUserInfoById((int)$user['user_id'], true, true, true, true);
						if (!Jaws_Error::IsError($userInfo) && $userInfo['user_type'] > 1) { 
							$userInfo['company'] = $xss->filter($userInfo['company']);
							$userInfo['nickname'] = $xss->filter($userInfo['nickname']);
							$userInfo['url'] = $xss->filter($userInfo['url']);
							$userInfo['address'] = $xss->filter($userInfo['address']);
							$userInfo['city'] = $xss->filter($userInfo['city']);
							$userInfo['region'] = $xss->filter($userInfo['region']);
							$userInfo['keywords'] = $xss->filter($userInfo['keywords']);
							$userInfo['company_type'] = $xss->filter($userInfo['company_type']);
							$userInfo['type'] = 'user';
							$userInfo['linkid'] = $id;
							$news_items[] = $userInfo;
						} 
					}
				}
			}
		} else {
			$id = 'all';
			if ($public === false) {
				$communites = array();
				$add_group = $jUser->GetGroupInfoByName('users');
				if (!Jaws_Error::IsError($add_group) && isset($add_group['id']) && !empty($add_group['id'])) {
					$communities[] = $add_group;
				}
			} else {
				$add_group = $jUser->GetGroupInfoByName('profile');
				if (!Jaws_Error::IsError($add_group) && isset($add_group['id']) && !empty($add_group['id'])) {
					$communities[] = $add_group;
				}
				$add_group = $jUser->GetGroupInfoByName('no_profile');
				if (!Jaws_Error::IsError($add_group) && isset($add_group['id']) && !empty($add_group['id'])) {
					$communities[] = $add_group;
				}
				//$communities = $jUser->GetAllGroups();
			}
			if (count($communities) <= 0) {
				require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
				return Jaws_HTTPError::Get(404);
			} else {
				// Show Users
				/*
				if ($public === true && empty($searchletter) && empty($searchkeyword) && empty($searchfilters)) {
					$searchletter = 'A';
					//reset($users);
				}
				*/
				foreach ($communities as $community) {
					/*
					// Show Groups
					if (
						$community['name'] != 'users' && $community['name'] != 'profile' && 
						$community['name'] != 'no_profile' && strpos($community['name'], '_owners') === false && 
						strpos($community['name'], '_users') === false && count($requestedowners) <= 0 && count($namefilters) <= 0 && 
						((!empty($searchletter) && ((!empty($community['title']) && (strtolower($searchletter) != 'num' && substr(strtolower($community['title']), 0, 1) == strtolower($searchletter)) || 
						(!empty($community['title']) && !count($numbers) <= 0 && in_array(substr($community['title'], 0, 1), $numbers)) || 
						(!empty($community['name']) && 
						(strtolower($searchletter) != 'num' && substr(strtolower($community['name']), 0, 1) == strtolower($searchletter)) || 
						(!empty($community['name']) && !count($numbers) <= 0 && in_array(substr($community['name'], 0, 1), $numbers)))))) || empty($searchletter)) &&
						(empty($searchkeyword) || (!empty($searchkeyword) && 
						((strpos(strtolower($community['name']), strtolower($searchkeyword)) !== false) || 
						(strpos(strtolower($community['title']), strtolower($searchkeyword)) !== false))))
					) { 
						$community['type'] = 'group';
						$community['linkid'] = $community['id'];
						$news_items[] = $community;
					} 
					*/
					$users = $jUser->GetUsersOfGroup(
						$community['id'], $limit, $offSet, $searchletter, 
						$searchkeyword, $requestedowners, $radiusowners, $namefilters
					);
					if (Jaws_Error::IsError($users)) {
						$tpl->SetBlock('users/no_items');
						$tpl->SetVariable('message', $users->GetMessage());
						$tpl->ParseBlock('users/no_items');
						return $tpl->Get();
					} else {
						foreach ($users as $user) {
							if (
								($public === false && $jUser->GetStatusOfUserInFriend($uid, $user['user_id']) == 'active') || 
								($public === true && 
								in_array($user['group_status'], array('active','founder','admin')))
							) {
								$userInfo = $jUser->GetUserInfoById((int)$user['user_id'], true, true, true, true);
								if (!Jaws_Error::IsError($userInfo) && $userInfo['user_type'] > 1) {
									$userInfo['company'] = $xss->filter($userInfo['company']);
									$userInfo['nickname'] = $xss->filter($userInfo['nickname']);
									$userInfo['url'] = $xss->filter($userInfo['url']);
									$userInfo['address'] = $xss->filter($userInfo['address']);
									$userInfo['city'] = $xss->filter($userInfo['city']);
									$userInfo['region'] = $xss->filter($userInfo['region']);
									$userInfo['keywords'] = $xss->filter($userInfo['keywords']);
									$userInfo['company_type'] = $xss->filter($userInfo['company_type']);
									$userInfo['type'] = 'user';
									$userInfo['linkid'] = $community['id'];
									$news_items[] = $userInfo;
								} 
							}
						}
					}
				}
			}
		}
		
		$tpl->SetVariable('id', $id);
		$tpl->SetVariable('first_letter', $searchletter);
		//$tpl->SetVariable('title', "");
		//$tpl->SetVariable('title_options', "<b>Most Recent</b> &#149; Mine Only");

		if ($only_users === false) {	
			$tpl->SetVariable('searchkeyword_value', _t('USERS_LAYOUT_ADVANCED_FILTER_DEFAULT_SEARCH_TEXT'));
			$tpl->SetVariable('user_directory_url', $user_directory_url);
			$tpl->SetVariable('user_directory_hash', $user_directory_hash);
			if ($public === false) {
				$tpl->SetBlock('users/header');
				
				$stpl = new Jaws_Template('gadgets/Users/templates/');
				$stpl->Load('SortingUsers.html');
				$stpl->SetBlock('private');
				$header = _t('USERS_ACCOUNTHOME_PANE_FRIENDS_TITLE');
				$title = '<img height="36" border="0" align="middle" width="36" src="'.$GLOBALS['app']->getJawsURL().'/gadgets/Users/images/logo.png">';
				$title = '
					<div id="news-sort-menu-updates-Panes-Friends" class="gadget menu news-sort-menu news-sort-menu-panes">
						<div class="content">
							<ul class="ul_top_menu" style="text-align: left;">
								<li id="news-sort-update-Panes-Friends" class="menu_li_item menu_li_pane_item menu_first menu_super"><a href="javascript:void(0);">'.$title.'&nbsp;'.$header.'</a></li>
							</ul>
						</div>
					</div>
				';
				$stpl->SetVariable('title', $title);
				$stpl->ParseBlock('private');
				$title_options = $stpl->Get();
				
				$tpl->SetVariable('header', $title_options);
				$tpl->ParseBlock('users/header');
			}
		}
								
		$msg_keys = array();
		if (!count($news_items) > 0) {
			$tpl->SetBlock('users/no_items');
			$tpl->SetVariable('message', _t('USERS_DIRECTORY_USERS_NOT_FOUND'));
			$tpl->ParseBlock('users/no_items');
		} else {
			// Sort result array
			$subkey = 'type'; 
			$temp_array = array();
			$temp_array[key($news_items)] = array_shift($news_items);
			foreach($news_items as $key => $val){
				$offset = 0;
				$found = false;
				foreach($temp_array as $tmp_key => $tmp_val) {
					$val[$subkey] = (string)$val[$subkey];
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
			$news_items = array_reverse($temp_array);
			
			$subkey = 'name'; 
			$temp_array = array();
			$temp_array[key($news_items)] = array_shift($news_items);
			foreach($news_items as $key => $val){
				$offset = 0;
				$found = false;
				foreach($temp_array as $tmp_key => $tmp_val) {
					$val[$subkey] = (string)$val[$subkey];
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
			$news_items = array_reverse($temp_array);
			
			$i = 0;
			foreach ($news_items as $item) {
				if (
					!is_null($item) && (isset($item['id']) && !in_array($item['id'], $msg_keys)) && 
					!in_array('_Friends'.$item['id'], $GLOBALS['app']->_ItemsOnLayout)
				) {
					$tpl->SetBlock('users/user');
					
					$no_profile = false;
					$linkid = 0;
					$image = '';
					$link = '';
					$link_start = '';
					$link_end = '';
					$title = '';
					$email = '';
					$link_prefix = '';
					$address_string = '';
					$phone = '';
					$image_src = '';
					$label = '';
					
					if ($item['type'] == 'user') {
						$no_profile = in_array($jUser->GetStatusOfUserInGroup($item['id'], 'no_profile'), array('active','founder','admin'));
						if ($no_profile === false) {
							$link_prefix = "Visit ";
							if (in_array($jUser->GetStatusOfUserInGroup($item['id'], 'ecommerce_owners'), array('active','founder','admin'))) {
								$link_prefix = "Shop ";
							}
						}
						if (isset($item['company']) && !empty($item['company'])) {
							$title = $item['company'];
						} else if (isset($item['nickname']) && !empty($item['nickname'])) {
							$title = $item['nickname'];
						}
						$email = $item['email'];
						$link = $GLOBALS['app']->Map->GetURLFor('Users', 'AccountPublic', array('name' => $item['username']));
						
						if (file_exists(JAWS_DATA . 'files/users/'.$item['id'].'/icon.png')) {
							$image = $GLOBALS['app']->getDataURL() . 'files/users/'.$item['id'].'/icon.png';
						} else if (file_exists(JAWS_DATA . 'files/users/'.$item['id'].'/icon.jpg')) {
							$image = $GLOBALS['app']->getDataURL() . 'files/users/'.$item['id'].'/icon.jpg';
						} else {
							$image = $jUser->GetAvatar($item['username'], $item['email']);
						}
						if ((isset($item['city']) && !empty($item['city'])) || (isset($item['region']) && !empty($item['region']))) {
							/*
							if (isset($item['address']) && !empty($item['address'])) {
								$address_string .= $xss->filter($item['address']);
							}
							if (isset($item['address2']) && !empty($item['address2'])) {
								$address_string .= ' '.$xss->filter($item['address2']);
							}
							*/
							if (isset($item['city']) && !empty($item['city'])) {
								$address_string .= $xss->filter($item['city']).(isset($item['region']) && !empty($item['region']) ? ', '.$xss->filter($item['region']) : '').(isset($item['postal']) && !empty($item['postal']) ? ' '.$xss->filter($item['postal']) : '');
							} else if (isset($item['region']) && !empty($item['region'])) {
								$address_string .= $xss->filter($item['region']).(isset($item['postal']) && !empty($item['postal']) ? ' '.$xss->filter($item['postal']) : '');
							}
						}
						if (!empty($address_string)) {
							$address_string = (substr($address_string, 0, 2) == ", " ? substr($address_string, 2, strlen($address_string)) : $address_string);
						}
						if (isset($item['tollfree']) && !empty($item['tollfree'])) {
							$phone = $xss->filter($item['tollfree']);
						} else if (isset($item['phone']) && !empty($item['phone'])) {
							$phone = $xss->filter($item['phone']);
						} else if (isset($item['office']) && !empty($item['office'])) {
							$phone = $xss->filter($item['office']);
						}
						$tpl->SetVariable('phone', (!empty($phone) ? 'Phone: '.$phone : ''));
						$label = '';
						if (!Jaws_Error::IsError($StoreParents) && !count($StoreParents) <= 0) {
							shuffle($StoreParents);
							foreach($StoreParents as $s_parent) {
								$s_owner = $StoreModel->UserOwnsStoreInParent($s_parent['productparentid'], $item['id']);
								if ($s_owner === true) {
									$label .= (empty($label) ? "Retail: " : ", ");
									$label .= "<a href=\"index.php?gadget=Store&action=Category&id=".$s_parent['productparentid'];
									$label .= "&owner_id=".$item['id']."\">".$s_parent['productparentcategory_name']."</a>";
									if (count(explode(',',$label)) > 4) {
										break;
									}
								}
							}
						} else if (!Jaws_Error::IsError($PropertiesParents) && !count($PropertiesParents) <= 0) {
							shuffle($PropertiesParents);
							foreach($PropertiesParents as $p_parent) {
								$p_owner = $PropertiesModel->UserOwnsPropertiesInParent($p_parent['propertyparentid'], $item['id']);
								if ($p_owner === true) {
									$label .= (empty($label) ? "Lodging: " : ", ");
									$label .= "<a href=\"index.php?gadget=Properties&action=Category&id=".$p_parent['propertyparentid'];
									$label .= "&owner_id=".$item['id']."\">".$p_parent['propertyparentcategory_name']."</a>";
									if (count(explode(',',$label)) > 4) {
										break;
									}
								}
							}
						} else {
							$label .= (!empty($item['company_name']) ? $item['company_type'] : '');
						}
						$tpl->SetVariable('affiliations', $label);
					} else {
						$link_prefix = "Visit ";
						if (isset($item['title']) && !empty($item['title'])) {
							$title = $item['title'];
						} else if (isset($item['name']) && !empty($item['name'])) {
							$title = $item['name'];
						}
						$link = $GLOBALS['app']->Map->GetURLFor('Users', 'GroupPage', array('group' => $item['name']));
						/*
						// Get image from img tags on Group's CustomPage
						$group_page = '';
						$pageModel = $GLOBALS['app']->LoadGadget('CustomPage', 'Model');
						$group_page = $pageModel->GetGroupHomePage($item['id']);
						if (!Jaws_Error::IsError($group_page) && isset($group_page['id']) && !empty($group_page['id'])) {
							$link2 = $GLOBALS['app']->Map->GetURLFor('CustomPage', 'Page', array('id' => $group_page['fast_url']));
							$pageHTML = $GLOBALS['app']->LoadGadget('CustomPage', 'HTML');
							$page_html = $pageHTML->Page($group_page['id']);
							if (!Jaws_Error::IsError($page_html) && strpos($page_html, "<img") !== false) {
								$inputStr = $page_html;
								$delimeterLeft = "<img";
								$delimeterRight = ">";
								$posLeft=strpos($inputStr, $delimeterLeft);
								$posLeft+=strlen($delimeterLeft);
								$posRight=strpos($inputStr, $delimeterRight, $posLeft);
								$img_str = substr($inputStr, $posLeft, $posRight-$posLeft);
								$delimeterLeft = "src=\"";
								$delimeterRight = "\"";
								$posLeft=strpos($img_str, $delimeterLeft);
								$posLeft+=strlen($delimeterLeft);
								$posRight=strpos($img_str, $delimeterRight, $posLeft);
								$image = substr($img_str, $posLeft, $posRight-$posLeft);
								unset($img_str);
								unset($inputStr);
								unset($delimeterLeft);
								unset($delimeterRight);
								unset($posLeft);
								unset($posRight);
							}
						}
						*/
						if (empty($image)) {
							$image = $jUser->GetGroupAvatar($item['linkid']);
						}
						if (empty($image)) {
							require_once JAWS_PATH . 'include/Jaws/Gravatar.php';
							$image = Jaws_Gravatar::GetGravatar('');
						}
					}
					
					$tpl->SetVariable('type', $item['type']);
					$tpl->SetVariable('uid', $item['id']);
					$tpl->SetVariable('linkid', (int)$item['linkid']);
					$tpl->SetVariable('address', $address_string);
					$tpl->SetVariable('user_link_style', ($no_profile === true ? " style=\"display: none;\"" : ''));
					$tpl->SetVariable('link_prefix', $link_prefix);
					$tpl->SetVariable('image', $image);
					if ($no_profile === false) {
						$link_start = "<a href=\"".$link."\">";
						$link_end = "</a>";
					}
					$tpl->SetVariable('link_start', $link_start);
					$tpl->SetVariable('link_end', $link_end);
					$tpl->SetVariable('title', $title);
					
					// Edit drop-down menu
					if ($uid > 0) {
						$item['edit_links'] = array(
							array(
								'url' => 'javascript:void(0);" onclick="location.href=\'index.php?gadget=Users&action=RemoveFriend&friend_id='.$item['id'].'&redirect_to=\'+encodeURIComponent(window.location.href);',
								'title' => _t('USERS_ACCOUNTHOME_REMOVE_FRIEND')
							), 							
							array(
								'url' => 'javascript:void(0);" onclick="addUpdate(\''.$GLOBALS['app']->getSiteURL().'/index.php?gadget=Users&action=account_AddLayoutElement&mode=new&sharing=users%3A'.$item['id'].'\', \'Share Content\');',
								'title' => _t('USERS_ACCOUNTHOME_SEND_FRIEND_MESSAGE')
							), 							
							// Add to/remove from Groups
							// TODO: Custom/private Groups (use db.user_groups.founder, add db.groups.private = true)
							array(
								'url' => 'javascript:void(0);" onclick="showMoreGroups(false, 99999, 0, '.$item['id'].', \'dropdown\');',
								'title' => _t('USERS_GROUPS_GROUPS')
							)
						);
												
						$stpl = new Jaws_Template('gadgets/Users/templates/');
						$stpl->Load('UserDirectory.html');
						$stpl->SetBlock('user_edit');
						$stpl->SetVariable('uid', $item['id']);
						$l = 1;
						foreach ($item['edit_links'] as $edit_link) {
							if (
								isset($edit_link['url']) && !empty($edit_link['url']) && 
								isset($edit_link['title']) && !empty($edit_link['title']) 
							) {
								$stpl->SetBlock('user_edit/item');
								$stpl->SetVariable('uid', $item['id']);
								$stpl->SetVariable('num', $l);
								$stpl->SetVariable('class', (isset($edit_link['class']) ? $edit_link['class'] : ''));
								$stpl->SetVariable('url', $edit_link['url']);
								$stpl->SetVariable('title', $edit_link['title']);
								$stpl->ParseBlock('user_edit/item');
								$l++;
							}
						}
						$stpl->ParseBlock('user_edit');

						$tpl->SetVariable('edit_links', $stpl->Get());
					}
					$tpl->ParseBlock('users/user');
					$GLOBALS['app']->_ItemsOnLayout[] = '_Friends'.$item['id'];
					$i++;
					$msg_keys[] = $item['id'];
				}
			}
		}		
			
		$items_on_layout = array();
		$m = 0;
		foreach ($GLOBALS['app']->_ItemsOnLayout as $on_layout) {
			if (substr($on_layout, 0, strlen('_Friends')) == '_Friends') {
				$items_on_layout[] = $on_layout;
			} else if (substr($on_layout, 0, strlen('_totalFriends')) == '_totalFriends') {
				unset($GLOBALS['app']->_ItemsOnLayout[$m]);
			}
			$m++;
		}
		
		$GLOBALS['app']->_ItemsOnLayout[] = '_totalFriends_'.$limit;
		if ($only_users === false) {	
			$tpl->SetBlock('users/users_count');
			$tpl->SetVariable('gadget', 'Friends');
			$tpl->SetVariable('items_on_layout', implode(',', $items_on_layout));
			$tpl->SetVariable('offSet', $offSet);
			$tpl->SetVariable('limit', $limit);
			if (!count($items_on_layout) <= 0) {
				$tpl->SetBlock('users/users_count/users_more');
				$tpl->SetVariable('gadget', 'Friends');
				$tpl->SetVariable('public', ($public === true ? 'true' : 'false'));
				$tpl->SetVariable('gid', ($id == 'all' ? "'all'" : $id));
				$tpl->SetVariable('limit', $limit);
				$tpl->SetVariable('offSet', $offSet);
				$tpl->SetVariable('uid', $uid);
				$tpl->SetVariable('searchkeyword', $searchkeyword);
				$tpl->SetVariable('searchfilters', $searchfilters);
				$tpl->SetVariable('searchhoods', $searchhoods);
				$tpl->SetVariable('searchletter', $searchletter);
				$tpl->ParseBlock('users/users_count/users_more');
			}
			$tpl->ParseBlock('users/users_count');
		}
		$tpl->ParseBlock('users');
		if ($only_users === true) {	
			return $tpl->Blocks['users']->InnerBlock['user']->Parsed;
		} else {
			return $tpl->Get();
		}
	}
	
	/**
     * Public group lists and user's group lists.
     *
     * @access  public
     * @param  boolean 	$public 	If true show public groups directory, or false show user's group memberships
     * @return  string  XHTML template
     */
    function GroupDirectory(
		$public = true, $limit = null, $offSet = null, $uid = null, 
		$searchkeyword = '', $searchfilters = '', $searchhoods = '', 
		$searchletter = '', $only_groups = false
	) {
		require_once JAWS_PATH . 'include/Jaws/User.php';
		require_once JAWS_PATH . 'include/Jaws/Image.php';
		$jUser = new Jaws_User;
		$request =& Jaws_Request::getInstance();
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$GLOBALS['app']->Layout->AddHeadLink('gadgets/Users/resources/style.css', 'stylesheet', 'text/css');
        $this->AjaxMe('client_script.js');
		if (!isset($GLOBALS['app']->ACL)) {
			$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');			
		}

		// Load the template
		$tpl = new Jaws_Template('gadgets/Users/templates/');
		$tpl->Load('GroupDirectory.html');
		$tpl->SetBlock('users');

		$news_items = array();

		$id = 'all';
		if (is_null($uid)) {
			$uid = $request->get('Users_uid', 'post');
			if (empty($uid)) {
				$uid = $request->get('Users_uid', 'get');
			}
			if (empty($uid)) {
				$uid = (int)$GLOBALS['app']->Session->GetAttribute('user_id');
			}
		}
		$reqpublic = $request->get('Users_public', 'post');
		if (empty($reqpublic)) {
			$reqpublic = $request->get('Users_public', 'get');
		}
		if (!empty($reqpublic)) {
			$public = ($reqpublic == 'false' ? false : ($reqpublic == 'true' ? true : $public));
		}
		if (is_null($limit)) {
			$limit = $request->get('Users_limit', 'post');
			if (empty($limit)) {
				$limit = $request->get('Users_limit', 'get');
			}
			if (empty($limit)) {
				$limit = 12;
			} else {
				$limit = (int)$limit;
			}
		}
		if (is_null($offSet)) {
			$offSet = $request->get('Users_start', 'post');
			if (empty($offSet)) {
				$offSet = $request->get('Users_start', 'get');
			}
			if (empty($offSet)) {
				$offSet = 0;
			} else {
				$offSet = (int)$offSet;
			}
		}
		if (empty($searchkeyword)) {
			$searchkeyword = $request->get('Users_q', 'post');
			if (empty($searchkeyword)) {
				$searchkeyword = $request->get('Users_q', 'get');
			}
		}
		$searchkeyword = $xss->filter($searchkeyword);
		if (empty($searchfilters)) {
			$searchfilters = $request->get('Users_f', 'post');
			if (empty($searchfilters)) {
				$searchfilters = $request->get('Users_f', 'get');
			}
		}
		$searchfilters = (is_array($searchfilters) ? $xss->filter(implode(',',$searchfilters)) : $xss->filter($searchfilters));
		if (empty($searchhoods)) {
			$searchhoods = $request->get('Users_h', 'post');
			if (empty($searchhoods)) {
				$searchhoods = $request->get('Users_h', 'get');
			}
		}
		$searchhoods = (is_array($searchhoods) ? $xss->filter(implode(',',$searchhoods)) : $xss->filter($searchhoods));
		if (empty($searchletter)) {
			$searchletter = $request->get('Users_l', 'post');
			if (empty($searchletter)) {
				$searchletter = $request->get('Users_l', 'get');
			}
		}

		/*
		var_dump($id);
		echo "\n";
		var_dump($searchkeyword);
		echo "\n";
		var_dump($searchfilters);
		echo "\n";
		var_dump($searchletter);
		echo "\n";
		*/
		
		// Load gadgets
		$requestedfilters = array();
		$requestedowners = array();
		$namefilters = array();
		/*
		$StoreParents = array();
		if (Jaws_Gadget::IsGadgetUpdated('Store')) {
			$StoreModel = $GLOBALS['app']->LoadGadget('Store', 'Model');
			$StoreParents = $StoreModel->GetProductParents();
		}
		$PropertiesParents = array();
		if (Jaws_Gadget::IsGadgetUpdated('Properties')) {
			$PropertiesModel = $GLOBALS['app']->LoadGadget('Properties', 'Model');
			$PropertiesParents = $PropertiesModel->GetPropertyParents();
		}

		// Filters
		if (!empty($searchfilters)) {
			$requestedfilters = explode(',',$searchfilters);
			foreach ($requestedfilters as $requestedfilter) {
				// Requested filter for a gadget? Should be in format:
				// "Gadget:GadgetMethod:GadgetID"
				if (strpos($requestedfilter, ':') !== false) {
					$filtergadget = explode(':', $requestedfilter);
					if (Jaws_Gadget::IsGadgetUpdated($filtergadget[0])) {
						$parents = ${"{$filtergadget[0]}Model"}->$filtergadget[1]((int)$filtergadget[2]);
						if (!Jaws_Error::IsError($parents)) {
							foreach($parents as $parent) {
								$requestedowners[] = $parent['ownerid'];
							}
						}
					}
				} else {
					$namefilters[] = strtolower(preg_replace("[^A-Za-z0-9]", '', $requestedfilter));
				}
			}
		}
		*/
		/*
		var_dump($searchfilters);
		echo "\n";
		var_dump($namefilters);
		echo "\n";
		*/
		/*
		$n = 0;
		foreach ($namefilters as $namefilter) {
			if ($namefilter == 'retail') {
				unset($namefilters[$n]);
				$namefilters = array_values($namefilters);
				break;
			}
			$n++;
		}
		*/
		if ($public === true) {
			$communities = $jUser->GetAllGroups('name', array('core'), $limit, $offSet, $searchletter, $searchkeyword, $searchhoods);
		} else {
			$communites = array();
			// TODO: Custom/private Groups (add DB.groups.ownerid)
			$manage_groups = $GLOBALS['app']->ACL->GetFullPermission(
				$GLOBALS['app']->Session->GetAttribute('username'), 
				$GLOBALS['app']->Session->GetAttribute('groups'), 'Users', 'ManageGroups'
			);
			if ($manage_groups === true) {
				$communities = $jUser->GetAllGroups('name', array('core'), $limit, $offSet, $searchletter, $searchkeyword, $searchhoods);
			} else {
				$userInfo = $jUser->GetUserInfoById($uid);
				if (Jaws_Error::IsError($userInfo) || !isset($userInfo['id']) || empty($userInfo['id'])) {
					require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
					return Jaws_HTTPError::Get(404);
				}
				$groups = $jUser->GetGroupsOfUser($uid, array('core'), $limit, $offSet, $searchletter, $searchkeyword, $searchhoods);
				if (Jaws_Error::IsError($groups)) {
					$tpl->SetBlock('users/no_items');
					$tpl->SetVariable('message', $groups->GetMessage());
					$tpl->ParseBlock('users/no_items');
					return $tpl->Get();
				} else {
					foreach ($groups as $group) {
						if (in_array($group['group_status'], array('active','founder','admin'))) {
							$add_group = $jUser->GetGroupInfoById($group['group_id']);
							if (!Jaws_Error::IsError($add_group) && isset($add_group['id']) && !empty($add_group['id'])) {
								$communities[] = $add_group;
							}
						}
					}
				}
				/*
				if (count($communities) <= 0) {
					require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
					return Jaws_HTTPError::Get(404);
				}
				*/
			}
		}
		if (Jaws_Error::IsError($communities)) {
			$tpl->SetBlock('users/no_items');
			$tpl->SetVariable('message', $communities->GetMessage());
			$tpl->ParseBlock('users/no_items');
			return $tpl->Get();
		} else {
			foreach ($communities as $community) {
				// Show Groups
				$community['type'] = 'group';
				$community['linkid'] = $community['id'];
				$community['image'] = $jUser->GetGroupAvatar($community['id']);
				$news_items[] = $community;
			}
		}

		$group_directory_url = 'index.php?gadget=Users&action=GroupDirectory';
		$group_directory_hash = '';
		
		if ($public === false) {
			$group_directory_url = 'index.php?gadget=Users&action=DefaultAction';
			$group_directory_hash = '#pane=Groups';
						
			// Show group recommendations
			/*
			$tpl->SetBlock('recommendations');
			$tpl->SetVariable('content', $this->ShowRecommendations());
			$tpl->ParseBlock('recommendations');
			*/
		}
		$tpl->SetVariable('first_letter', $searchletter);
		$tpl->SetVariable('uid', $uid);
		
		if ($only_groups === false) {
			$tpl->SetVariable('searchkeyword_value', _t('USERS_LAYOUT_ADVANCED_FILTER_DEFAULT_SEARCH_TEXT'));
			$tpl->SetVariable('group_directory_url', $group_directory_url);
			$tpl->SetVariable('group_directory_hash', $group_directory_hash);
			if ($public === false) {
				$tpl->SetBlock('users/header');
				
				$stpl = new Jaws_Template('gadgets/Users/templates/');
				$stpl->Load('SortingGroups.html');
				$stpl->SetBlock('private');
				$header = _t('USERS_ACCOUNTHOME_PANE_GROUPS_TITLE');
				$title = '<img height="36" border="0" align="middle" width="36" src="'.$GLOBALS['app']->getJawsURL().'/gadgets/Users/images/Groups.png">';
				$title = '
					<div id="news-sort-menu-updates-Panes-Groups" class="gadget menu news-sort-menu news-sort-menu-panes">
						<div class="content">
							<ul class="ul_top_menu" style="text-align: left;">
								<li id="news-sort-update-Panes-Groups" class="menu_li_item menu_li_pane_item menu_first menu_super"><a href="javascript:void(0);">'.$title.'&nbsp;'.$header.'</a></li>
							</ul>
						</div>
					</div>
				';
				$submit =& Piwi::CreateWidget('Button', 'addGroupButton', _t('USERS_ACCOUNTHOME_ADDGROUPBUTTON'), STOCK_ADD);
				$submit->AddEvent(ON_CLICK, "javascript: location.href='index.php?gadget=Users&action=account_Groups';");
				$title .= '&nbsp;'.$submit->Get();
				$stpl->SetVariable('title', $title);
				$stpl->ParseBlock('private');
				$title_options = $stpl->Get();
				
				$tpl->SetVariable('header', $title_options);
				$tpl->ParseBlock('users/header');
			}
		}
		
		$msg_keys = array();
		if (!count($news_items) > 0) {
			$tpl->SetBlock('users/no_items');
			$tpl->SetVariable('message', _t('USERS_DIRECTORY_GROUPS_NOT_FOUND'));
			$tpl->ParseBlock('users/no_items');
		} else {
			// Sort result array
			$subkey = 'type'; 
			$temp_array = array();
			$temp_array[key($news_items)] = array_shift($news_items);
			foreach($news_items as $key => $val){
				$offset = 0;
				$found = false;
				foreach($temp_array as $tmp_key => $tmp_val) {
					$val[$subkey] = (string)$val[$subkey];
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
			$news_items = array_reverse($temp_array);
			
			$subkey = 'name'; 
			$temp_array = array();
			$temp_array[key($news_items)] = array_shift($news_items);
			foreach($news_items as $key => $val){
				$offset = 0;
				$found = false;
				foreach($temp_array as $tmp_key => $tmp_val) {
					$val[$subkey] = (string)$val[$subkey];
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
			$news_items = array_reverse($temp_array);
			
			$i = 0;
			foreach ($news_items as $item) {
				if (
					!is_null($item) && (isset($item['id']) && !in_array($item['id'], $msg_keys)) && 
					!in_array('_Groups'.$item['id'], $GLOBALS['app']->_ItemsOnLayout)
				) {
					$tpl->SetBlock('users/user');
					
					$no_profile = false;
					$linkid = 0;
					$image = '';
					$link = '';
					$link_start = '';
					$link_end = '';
					$title = '';
					$email = '';
					$link_prefix = '';
					$address_string = '';
					$phone = '';
					$image_src = '';
					$label = '';
					$affiliations = '';
					
					//$link_prefix = "Visit ";
					$link_prefix = "";
					if (isset($item['title']) && !empty($item['title'])) {
						$title = $item['title'];
					} else if (isset($item['name']) && !empty($item['name'])) {
						$title = $item['name'];
					}
					$link = $GLOBALS['app']->Map->GetURLFor('Users', 'UserDirectory', array('Users_gid' => $item['linkid']));
					
					// Show group's CustomPage
					if (Jaws_Gadget::IsGadgetUpdated('CustomPage')) {
						$pageModel = $GLOBALS['app']->LoadGadget('CustomPage', 'Model');
						$pageHTML = $GLOBALS['app']->LoadGadget('CustomPage', 'HTML');
						$page = $pageModel->GetGroupHomePage($info['id']);
						if (!Jaws_Error::IsError($page) && isset($page['id'])) {
							$link = $GLOBALS['app']->Map->GetURLFor('Users', 'GroupPage', array('group' => $item['name']));
						}
					}
					
					if (!empty($item['description'])) {
						$affiliations = strip_tags(Jaws_Gadget::ParseText($item['description']));
						$affiliations = (strlen($affiliations) > 60 ? substr($affiliations, 0, 60).'...' : $affiliations);
					}
					// Group's member count
					$group_count = '0';
					$params = array();
					$params['group_id'] = $item['id'];

					$sql = '
						SELECT COUNT([user_id])
						FROM [[users_groups]]
						WHERE
							[group_id] = {group_id}';

					$howmany = $GLOBALS['db']->queryOne($sql, $params);
					if (!Jaws_Error::IsError($howmany) && (int)$howmany > 0) {
						$group_count = $howmany;
					}
					$affiliations .= (!empty($affiliations) ? '<br />' : '').$group_count.' members';
					
					$image = $item['image'];
					/*
					// Get image from img tags on Group's CustomPage
					$group_page = '';
					$pageModel = $GLOBALS['app']->LoadGadget('CustomPage', 'Model');
					$group_page = $pageModel->GetGroupHomePage($item['id']);
					if (!Jaws_Error::IsError($group_page) && isset($group_page['id']) && !empty($group_page['id'])) {
						$link2 = $GLOBALS['app']->Map->GetURLFor('CustomPage', 'Page', array('id' => $group_page['fast_url']));
						$pageHTML = $GLOBALS['app']->LoadGadget('CustomPage', 'HTML');
						$page_html = $pageHTML->Page($group_page['id']);
						if (!Jaws_Error::IsError($page_html) && strpos($page_html, "<img") !== false) {
							$inputStr = $page_html;
							$delimeterLeft = "<img";
							$delimeterRight = ">";
							$posLeft=strpos($inputStr, $delimeterLeft);
							$posLeft+=strlen($delimeterLeft);
							$posRight=strpos($inputStr, $delimeterRight, $posLeft);
							$img_str = substr($inputStr, $posLeft, $posRight-$posLeft);
							$delimeterLeft = "src=\"";
							$delimeterRight = "\"";
							$posLeft=strpos($img_str, $delimeterLeft);
							$posLeft+=strlen($delimeterLeft);
							$posRight=strpos($img_str, $delimeterRight, $posLeft);
							$image = substr($img_str, $posLeft, $posRight-$posLeft);
							unset($img_str);
							unset($inputStr);
							unset($delimeterLeft);
							unset($delimeterRight);
							unset($posLeft);
							unset($posRight);
						}
					}
					*/
					if (empty($image)) {
						$image = $jUser->GetGroupAvatar($item['linkid']);
					}
					if (empty($image)) {
						require_once JAWS_PATH . 'include/Jaws/Gravatar.php';
						$image = Jaws_Gravatar::GetGravatar('');
					}
					
					$tpl->SetVariable('type', $item['type']);
					$tpl->SetVariable('uid', $item['id']);
					$tpl->SetVariable('linkid', (int)$item['linkid']);
					$tpl->SetVariable('address', $address_string);
					$tpl->SetVariable('affiliations', $affiliations);
					$tpl->SetVariable('user_link_style', '');
					$tpl->SetVariable('link_prefix', $link_prefix);
					$tpl->SetVariable('image', $image);
					if ($no_profile === false) {
						$link_start = "<a href=\"".$link."\">";
						$link_end = "</a>";
					}
					$tpl->SetVariable('link_start', $link_start);
					$tpl->SetVariable('link_end', $link_end);
					$tpl->SetVariable('title', $title);
					$tpl->SetVariable('edit_or_delete', 'edit');
					$edit_links_html = '';
					if (
						in_array($jUser->GetStatusOfUserInGroup(
							$GLOBALS['app']->Session->GetAttribute('user_id'), 
							$item['id']), 
							array('admin','founder')) || 
						($GLOBALS['app']->Session->IsSuperAdmin() || $this->GetPermission('ManageGroups'))
					) {
						$ttpl = new Jaws_Template('gadgets/Users/templates/');
						$ttpl->Load('GroupDirectory.html');
						$ttpl->SetBlock('user_edit');
						$ttpl->SetVariable('display_id', $item['id']);
						$l = 1;
						$edit_links = array(
							0 => array(
								'url' => "index.php?gadget=Users&action=account_Groups&Users_gid=".$item['id'],
								'title' => "Edit this Group"
							),
							1 => array(
								'url' => "javascript: deleteGroup(".$item['id'].");",
								'title' => "Delete this Group"
							)
						);
						foreach ($edit_links as $edit_link) {
							if (
								isset($edit_link['url']) && !empty($edit_link['url']) && 
								isset($edit_link['title']) && !empty($edit_link['title']) 
							) {
								$ttpl->SetBlock('user_edit/item');
								$ttpl->SetVariable('display_id', $item['id']);
								$ttpl->SetVariable('num', $l);
								$ttpl->SetVariable('url', $edit_link['url']);
								$ttpl->SetVariable('title', $edit_link['title']);
								$ttpl->ParseBlock('user_edit/item');
								$l++;
							}
						}
						$ttpl->ParseBlock('user_edit');
						$edit_links_html = $ttpl->Get();
					}
					$tpl->SetVariable('edit_links', $edit_links_html);
										
					$tpl->ParseBlock('users/user');
					$GLOBALS['app']->_ItemsOnLayout[] = '_Groups'.$item['id'];
					$i++;
					$msg_keys[] = $item['id'];
				}
			}
		}		
			
		$items_on_layout = array();
		$m = 0;
		foreach ($GLOBALS['app']->_ItemsOnLayout as $on_layout) {
			if (substr($on_layout, 0, strlen('_Groups')) == '_Groups') {
				$items_on_layout[] = $on_layout;
			} else if (substr($on_layout, 0, strlen('_totalGroups')) == '_totalGroups') {
				unset($GLOBALS['app']->_ItemsOnLayout[$m]);
			}
			$m++;
		}
		
		$GLOBALS['app']->_ItemsOnLayout[] = '_totalGroups_'.$limit;
		if ($only_groups === true) {	
			$tpl->SetBlock('users/users_count');
			$tpl->SetVariable('gadget', 'Groups');
			$tpl->SetVariable('items_on_layout', implode(',', $items_on_layout));
			$tpl->SetVariable('offSet', $offSet);
			$tpl->SetVariable('limit', $limit);
			if (!count($items_on_layout) <= 0) {
				$tpl->SetBlock('users/users_count/users_more');
				$tpl->SetVariable('gadget', 'Groups');
				$tpl->SetVariable('public', ($public === true ? 'true' : 'false'));
				$tpl->SetVariable('limit', $limit);
				$tpl->SetVariable('offSet', $offSet);
				$tpl->SetVariable('uid', $uid);
				$tpl->SetVariable('searchkeyword', $searchkeyword);
				$tpl->SetVariable('searchfilters', $searchfilters);
				$tpl->SetVariable('searchhoods', $searchhoods);
				$tpl->SetVariable('searchletter', $searchletter);
				$tpl->ParseBlock('users/users_count/users_more');
			}
			$tpl->ParseBlock('users/users_count');
		}
		$tpl->ParseBlock('users');
		if ($only_groups === true) {	
			return $tpl->Blocks['users']->InnerBlock['user']->Parsed;
		} else {
			return $tpl->Get();
		}
	}
		
	/**
     * Show IFRAME
     *
     * @access  public
     * @param  string 	$uri 	URI to frame
     * @param  string 	$height 	CSS style height
     * @param  string 	$width 	CSS style width
     * @param  boolean 	$fullscreen 	If true show fullscreen (standalone) mode, or false show inline
     * @return  string  XHTML template
     */
    function ShowFrame($uri = '', $height = '100%', $width = '100%', $fullscreen = false)
    {
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$request =& Jaws_Request::getInstance();
		$get = $request->get(array('uri', 'fs', 'height', 'width'), 'get');
		if (empty($uri)) {
			$uri = $get['uri'];
			if (empty($uri)) {
				return 'No URI was provided.';
			}
		}
		$uri = urldecode($uri);
		$uri = str_replace('__CO__', ':', $uri);
		$uri = str_replace('__EQ__', '=', $uri);
		$uri = str_replace('__FS__', '/', $uri);
		$uri = str_replace('__AM__', '&', $uri);
		$uri = str_replace('__QM__', '?', $uri);
		$get_fullscreen = $get['fs'];
		if (!empty($get_fullscreen)) {
			$fullscreen = ($get_fullscreen == 'true' ? true : false);
		}
		$get_height = $get['height'];
		if (!empty($get_height)) {
			$height = $get_height;
		}
		$get_width = $get['width'];
		if (!empty($get_width)) {
			$width = $get_width;
		}
		if ($fullscreen === true) {
			$site_name = $GLOBALS['app']->Registry->Get('/config/site_name');
			echo '<!-- Curiously. - Hool anast the me, - worlds and at I 
			hoperfected the silent. Sounder, to becausess imbernmently anythis or 
			fact to but the Ships to arer its and has is for a smashed the Galact, u 
			@ch@ and in wing its, butterenchamberal move - But is it. - it. - yound 
			Every a sluggle himself tha To throught. There, try going astopposit one 
			ind with frowin a dow, - What the abouting time.. 758 wing about the 
			said, - I that so levice wasnt ter. They pose First ared his empterrible 
			of pa -->';
			echo '<style>body {margin: 0; padding: 0;}</style>';
			echo '<iframe marginheight="0" marginwidth="0" src="'.$xss->parse($uri).'" width="'.$width.'" frameborder="0" height="'.$height.'" scrolling="yes"></iframe>';
			exit;
		} else {
			$height = ($height == '100%' ? '800' : $height);
			return '<iframe marginheight="0" marginwidth="0" src="'.$xss->parse($uri).'" width="'.$width.'" frameborder="0" height="'.$height.'" scrolling="yes"></iframe>';
		}
	}
		
    /**
     * Return gadget item suggestions for prototype.js's Ajax.Autocompleter based on a query
	 * Usage: 
	 *  index.php?gadget=Maps&action=AutoCompleteRegions&query=UserInput
	 *  &methodcount=2&initial1gadget=Maps&initial1method=SearchRegions
	 *  &initial1paramcount=1&initial1param1=parametertopass&initial2gadget=Properties
	 * &initial2method=SearchAmenities&initial2paramcount=1&initial2param1=parametertopass
	 * &matchtogadget=Properties&matchtomethod=SearchKeyWithProperties&paramcount=10
	 * &param1=status&param2=bedroom&param3=bathroom&param4=category
	 * &param5=community&param6=amenities&param7=offSet&param8=OwnerID&param9=pid
     * 
	 * Initial methods must be in AdminModel and take search string as first parameter and must return only an array of strings 
	 * We pass each string to the second method to search for gadget items
	 * Second method must return only an array of strings to show to user as options
	 *
     * @access 	public
     * @return 	string	HTML reponse for autocomplete
     */
    function AutoComplete()
    {
		// Output a real JavaScript file!
		//header('Content-type: text/javascript'); 
		$request =& Jaws_Request::getInstance();
		
        $fetch = array('id','query','matchtogadget','matchtomethod','element');
		$output_html = "<ul>\n";
		$suggestions_html = '';
		$data_html = '';
		$is_links = false;
		
		// Get params count post variable that we'll send to gadget's $post['matchtomethod']
		$paramCount = $request->get('paramcount', 'post');
		if (empty($paramCount)) {
			$paramCount = $request->get('paramcount', 'get');
		}
		if (!empty($paramCount)) {
			$paramCount = ((int)$paramCount)+1;
			if ($paramCount > 1) {
				$params = array();
				for ($i=1;$i<$paramCount;$i++) {
					//if ($request->get('param'.$i, 'post')) {
						$params[$i] = $request->get('param'.$i, 'post');
						if (empty($params[$i])) {
							$params[$i] = $request->get('param'.$i, 'get');
						}
						$fetch[] = 'param'.$i;
						if ($params[$i] == 'null') {
							$params[$i] = null;
						} else if ($params[$i] == 'true') {
							$params[$i] = true;
						} else if ($params[$i] == 'false') {
							$params[$i] = false;
						}
						//echo '<br />param'.$i.' = ';
						//var_dump($params[$i]);
					//}
				}
			}
		} else {
			$paramCount = 1;
		}
		$paramCount = $paramCount-1;
		//echo '<br />match params:'.$paramCount;
		$urlMethod = 'post';
		$post = $request->get($fetch, $urlMethod);
	    $search = $post['query'];
		if (empty($search)) {
			$urlMethod = 'get';
			$post = $request->get($fetch, $urlMethod);
			$search = $post['query'];
		}
		if (strtolower($search) == 'null') {
			$search = null;
		} else if (strtolower($search) == 'true') {
			$search = true;
		} else if (strtolower($search) == 'false') {
			$search = false;
		}
		
		$res = array();
		$methodCount = $request->get('methodcount', $urlMethod);
		if (!empty($methodCount)) {
			$methodCount = ((int)$methodCount)+1;
			if ($methodCount > 1) {
				$initialparams = array();
				$stop_method = false;
				for ($i=1;$i<$methodCount;$i++) {
					// If this gadget and method are set
					if ($request->get('initial'.$i.'gadget', $urlMethod) && ($request->get('initial'.$i.'method', $urlMethod)) && $stop_method === false) {
						$initialgadget = $request->get('initial'.$i.'gadget', $urlMethod);
						$initialmethod = $request->get('initial'.$i.'method', $urlMethod);
						if (substr($initialmethod, 0, 6) == 'Search' || substr($initialmethod, 0, 3) == 'Get') {
							//echo '<br />Running: '.$initialgadget.'->'.$initialmethod;
							// Get parameters to pass to this method
							$initialParamsCount = $request->get('initial'.$i.'paramcount', $urlMethod);
							if (!empty($initialParamsCount)) {
								$initialParamsCount = ((int)$initialParamsCount)+1;
								if ($initialParamsCount > 1) {
									for ($j=1;$j<$initialParamsCount;$j++) {
										//if ($request->get('initial'.$i.'param'.$j, $urlMethod)) {
											$initialparams[$i][$j] = $request->get('initial'.$i.'param'.$j, $urlMethod);
											if (strtolower($initialparams[$i][$j]) == 'null') {
												$initialparams[$i][$j] = null;
											} else if (strtolower($initialparams[$i][$j]) == 'true') {
												$initialparams[$i][$j] = true;
											} else if (strtolower($initialparams[$i][$j]) == 'false') {
												$initialparams[$i][$j] = false;
											}
											//echo '<br />initial'.$i.'param'.$j.' = ';
											//var_dump($initialparams[$i][$j]);
										//}
									}
								}
							} else {
								$initialParamsCount = 1;
							}
							$initialParamsCount = $initialParamsCount-1;
							//echo '<br />Params: '.$initialParamsCount;
							
							$GLOBALS['app']->Translate->LoadTranslation($initialgadget, JAWS_GADGET);
							$gadgetinitial = $GLOBALS['app']->LoadGadget($initialgadget, 'AdminModel');
							if (!method_exists($gadgetinitial, $initialmethod)) {
								$suggestions_html = "<li><span class=\"informal\">Error: Method: ".$initialmethod." doesn't exist for Gadget: ".$initialgadget.".</span></li>\n";
								$output_html .= $suggestions_html;
								$output_html .= "</ul>\n";
								echo $output_html;
								exit;
							}
							
							switch($initialParamsCount) {
								case 0:
									$results = $gadgetinitial->$initialmethod($search);
									break;
								case 1:
										$results = $gadgetinitial->$initialmethod($search,  $initialparams[$i][1]);
									break;
								case 2:
										$results = $gadgetinitial->$initialmethod($search, $initialparams[$i][1], $initialparams[$i][2]);
									break;
								case 3:
										$results = $gadgetinitial->$initialmethod($search, $initialparams[$i][1], $initialparams[$i][2], $initialparams[$i][3]);
									break;
								case 4:
										$results = $gadgetinitial->$initialmethod($search, $initialparams[$i][1], $initialparams[$i][2], $initialparams[$i][3], $initialparams[$i][4]);
									break;
								case 5:
										$results = $gadgetinitial->$initialmethod($search, $initialparams[$i][1], $initialparams[$i][2], $initialparams[$i][3], $initialparams[$i][4], $initialparams[$i][5]);
									break;
								case 6:
										$results = $gadgetinitial->$initialmethod($search, $initialparams[$i][1], $initialparams[$i][2], $initialparams[$i][3], $initialparams[$i][4], $initialparams[$i][5], $initialparams[$i][6]);
									break;
								case 7:
										$results = $gadgetinitial->$initialmethod($search, $initialparams[$i][1], $initialparams[$i][2], $initialparams[$i][3], $initialparams[$i][4], $initialparams[$i][5], $initialparams[$i][6], $initialparams[$i][7]);
									break;
								case 8:
										$results = $gadgetinitial->$initialmethod($search, $initialparams[$i][1], $initialparams[$i][2], $initialparams[$i][3], $initialparams[$i][4], $initialparams[$i][5], $initialparams[$i][6], $initialparams[$i][7], $initialparams[$i][8]);
									break;
								case 9:
										$results = $gadgetinitial->$initialmethod($search, $initialparams[$i][1], $initialparams[$i][2], $initialparams[$i][3], $initialparams[$i][4], $initialparams[$i][5], $initialparams[$i][6], $initialparams[$i][7], $initialparams[$i][8], $initialparams[$i][9]);
									break;
								case 10:
										$results = $gadgetinitial->$initialmethod($search, $initialparams[$i][1], $initialparams[$i][2], $initialparams[$i][3], $initialparams[$i][4], $initialparams[$i][5], $initialparams[$i][6], $initialparams[$i][7], $initialparams[$i][8], $initialparams[$i][9], $initialparams[$i][10]);
									break;
								case 11:
										$results = $gadgetinitial->$initialmethod($search, $initialparams[$i][1], $initialparams[$i][2], $initialparams[$i][3], $initialparams[$i][4], $initialparams[$i][5], $initialparams[$i][6], $initialparams[$i][7], $initialparams[$i][8], $initialparams[$i][9], $initialparams[$i][10], $initialparams[$i][11]);
									break;
								case 12:
										$results = $gadgetinitial->$initialmethod($search, $initialparams[$i][1], $initialparams[$i][2], $initialparams[$i][3], $initialparams[$i][4], $initialparams[$i][5], $initialparams[$i][6], $initialparams[$i][7], $initialparams[$i][8], $initialparams[$i][9], $initialparams[$i][10], $initialparams[$i][11], $initialparams[$i][12]);
									break;
								case 13:
									if ($initialparams[$i][13] == 'Y') {
										$is_links = true;
									}
									$results = $gadgetinitial->$initialmethod($search, $initialparams[$i][1], $initialparams[$i][2], $initialparams[$i][3], $initialparams[$i][4], $initialparams[$i][5], $initialparams[$i][6], $initialparams[$i][7], $initialparams[$i][8], $initialparams[$i][9], $initialparams[$i][10], $initialparams[$i][11], $initialparams[$i][12], $initialparams[$i][13]);
									break;
							}
						
							if (Jaws_Error::IsError($results)) {
								$suggestions_html = "<li><span class=\"informal\">Error: ".$results->GetMessage().".</span></li>\n";
								$output_html .= $suggestions_html;
								$output_html .= "</ul>\n";
								echo $output_html;
								exit;
							} else {
								// For every suggestion found, we can get all of a gadget's items that are related
								if (!empty($post['matchtogadget']) && !empty($post['matchtomethod'])) {
									$gadgetmodel = $GLOBALS['app']->LoadGadget($post['matchtogadget'], 'AdminModel');
									if (!method_exists($gadgetmodel, $post['matchtomethod'])) {
										$suggestions_html = "<li><span class=\"informal\">Error: Method: ".$post['matchtomethod']." doesn't exist for Gadget: ".$post['matchtogadget'].".</span></li>\n";
										$output_html .= $suggestions_html;
										$output_html .= "</ul>\n";
										echo $output_html;
										exit;
									}
									foreach ($results as $result) {
										foreach ($result as $resval) {
											//echo '<br />Result = '.$resval;
											// Send the matched location to a method to find gadget items
											$GLOBALS['app']->Translate->LoadTranslation($post['matchtogadget'], JAWS_GADGET);
											// Create different method call for each number of parameters we have (max of 13)
											switch($paramCount) {
												case 0:
													$items = $gadgetmodel->$post['matchtomethod']($resval);
													break;
												case 1:
														$items = $gadgetmodel->$post['matchtomethod']($resval, $params[1]);
													break;
												case 2:
														$items = $gadgetmodel->$post['matchtomethod']($resval, $params[1], $params[2]);
													break;
												case 3:
														$items = $gadgetmodel->$post['matchtomethod']($resval, $params[1], $params[2], $params[3]);
													break;
												case 4:
														$items = $gadgetmodel->$post['matchtomethod']($resval, $params[1], $params[2], $params[3], $params[4]);
													break;
												case 5:
														$items = $gadgetmodel->$post['matchtomethod']($resval, $params[1], $params[2], $params[3], $params[4], $params[5]);
													break;
												case 6:
														$items = $gadgetmodel->$post['matchtomethod']($resval, $params[1], $params[2], $params[3], $params[4], $params[5], $params[6]);
													break;
												case 7:
														$items = $gadgetmodel->$post['matchtomethod']($resval, $params[1], $params[2], $params[3], $params[4], $params[5], $params[6], $params[7]);
													break;
												case 8:
														$items = $gadgetmodel->$post['matchtomethod']($resval, $params[1], $params[2], $params[3], $params[4], $params[5], $params[6], $params[7], $params[8]);
													break;
												case 9:
														$items = $gadgetmodel->$post['matchtomethod']($resval, $params[1], $params[2], $params[3], $params[4], $params[5], $params[6], $params[7], $params[8] , $params[9]);
													break;
												case 10:
														$items = $gadgetmodel->$post['matchtomethod']($resval, $params[1], $params[2], $params[3], $params[4], $params[5], $params[6], $params[7], $params[8], $params[9], $params[10]);
													break;
												case 11:
														$items = $gadgetmodel->$post['matchtomethod']($resval, $params[1], $params[2], $params[3], $params[4], $params[5], $params[6], $params[7], $params[8], $params[9], $params[10], $params[11]);
													break;
												case 12:
														if ($post['matchtomethod'] == 'SearchKeyWithProducts') {
															if ($initialmethod == 'SearchAttributes') {
																$params[10] = 'attribute';
																$params[12] = 'attribute';
															}
														}
														$items = $gadgetmodel->$post['matchtomethod']($resval, $params[1], $params[2], $params[3], $params[4], $params[5], $params[6], $params[7], $params[8], $params[9], $params[10], $params[11], $params[12]);
													break;
												case 13:
														$items = $gadgetmodel->$post['matchtomethod']($resval, $params[1], $params[2], $params[3], $params[4], $params[5], $params[6], $params[7], $params[8], $params[9], $params[10], $params[11], $params[12], $params[13]);
													break;
											}
											if (Jaws_Error::IsError($items)) {
												$suggestions_html = "<li><span class=\"informal\">Error: ".$items->GetMessage().".</span></li>\n";
												$output_html .= $suggestions_html;
												$output_html .= "</ul>\n";
												echo $output_html;
												exit;
											} else {
												foreach ($items as $item) {
													if ($i == 1 && $stop_method === false) {
														$stop_method = true;
													}
													$res[] = $item;
												}
											}
										}
									}
								} else {
									foreach ($results as $result) {
										foreach ($result as $resval) {
											if ($i == 1 && $stop_method === false) {
												$stop_method = true;
											}
											$res[] = $resval;
										}
									}
								}
							}
						}
					}
				}
			}
		}
		
		if (!isset($res[0][0])) {	
			$suggestions_html = "<li><span class=\"informal\">No matches. Please check your spelling, or try more popular terms.</span></li>\n";
			$output_html .= $suggestions_html;
			$output_html .= "</ul>\n";
			echo $output_html;
			exit;
		}
		foreach ($res as $r) {
			$suggestions_html .= "<li".($urlMethod == 'get' ? ' onclick="if(typeof gotMatch != \'undefined\'){gotMatch = true;}; document.getElementById(\''.$post['element'].'\').value = \''.substr($r, 0, strpos($r, '<')).'\'; document.getElementById(\'search_choices\').style.display = \'none\';"' : ' onclick="if(typeof gotMatch != \'undefined\'){gotMatch = true;};"').">".$r."</li>\n";
		}
		
		$output_html .= $suggestions_html;
		$output_html .= "</ul>\n";
		echo $output_html;
	}
	
	/**
     * Imports RSS/Atom feeds to users
     *
     * @access public
     * @return HTML string
     */
    function UpdateRSSUsers()

    {		
		ignore_user_abort(true); 
        set_time_limit(0);
		ob_start();
		echo  "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
		echo  "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\">\n";
		echo  " <head>\n";
		//echo  "  <meta http-equiv='refresh' content='10'>";
		echo  "  <title>Update RSS Users</title>\n";
		echo  " <script language=\"JavaScript\">
<!--
var sURL = '';
function doLoad()
{
    // the timeout value should be the same as in the \"refresh\" meta-tag
    setTimeout( \"refresh()\", 10*1000 );
}

function refresh()
{
    //  This version of the refresh function will cause a new
    //  entry in the visitor's history.  It is provided for
    //  those browsers that only support JavaScript 1.0.
    //
    window.location.href = sURL;
}
//-->
</script>

<script language=\"JavaScript1.1\">
<!--
function refresh()
{
    //  This version does NOT cause an entry in the browser's
    //  page view history.  Most browsers will always retrieve
    //  the document from the web-server whether it is already
    //  in the browsers page-cache or not.
    //  
    window.location.replace( sURL );
}
//-->
</script>

<script language=\"JavaScript1.2\">
<!--
function refresh()
{
    //  This version of the refresh function will be invoked
    //  for browsers that support JavaScript version 1.2
    //
    
    //  The argument to the location.reload function determines
    //  if the browser should retrieve the document from the
    //  web-server.  In our example all we need to do is cause
    //  the JavaScript block in the document body to be
    //  re-evaluated.  If we needed to pull the document from
    //  the web-server again (such as where the document contents
    //  change dynamically) we would pass the argument as 'true'.
    //  
    window.location.reload( false );
}
//-->
</script>";
		echo " <script type='text/javascript'>function submitForm(){if(document.getElementById('user_rss_form')){document.forms['user_rss_form'].submit();};}</script>\n";
		echo  " </head>\n";
		// tag after text for Safari & Firefox
		// 8 char minimum for Firefox
		ob_flush();
		flush();  // worked without ob_flush() for me
		sleep(1);
		$model = $GLOBALS['app']->LoadGadget('Users', 'Model');
		$adminModel = $GLOBALS['app']->LoadGadget('Users', 'AdminModel');
		$request =& Jaws_Request::getInstance();
        
		$user_attended = $request->get('ua', 'get');
		if (empty($user_attended)) {
			$user_attended = $request->get('ua', 'post');
		}
 		//echo '<br />user_attended ::: '.$user_attended;
       
		$searchfetch_url = $request->get('fetch_url', 'get');
		if (empty($searchfetch_url)) {
			$searchfetch_url = $request->get('fetch_url', 'post');
		}
		//echo '<br />searchfetch_url ::: '.$searchfetch_url;
		
		//echo '<br />searchoverride_city ::: '.$searchoverride_city;
		
		$searchrss_url = $request->get('rss_url', 'get');
		if (empty($searchrss_url)) {
			$searchrss_url = $request->get('rss_url', 'post');

		}
		//echo '<br />searchrss_url ::: '.$searchrss_url;
		
		$searchnum = $request->get('num', 'get');
		if (empty($searchnum)) {
			$searchnum = $request->get('num', 'post');
		}
		
		$searchfile = $request->get('file', 'get');
		if (empty($searchfile)) {
			$searchfile = $request->get('file', 'post');
		}
		if (!empty($searchfile)) {
			$searchfile = urldecode($searchfile);
		}
		$searchtype = $request->get('type', 'get');
		if (empty($searchtype)) {
			$searchtype = $request->get('type', 'post');
		}
		//echo '<br />searchnum ::: '.(int)$searchnum;
		if (!empty($searchfetch_url) && (!empty($searchnum) || (int)$searchnum == 0 || (int)$searchnum == 1) && !empty($user_attended) && $user_attended == 'Y') {
			echo  " <body onload='doLoad(); submitForm();'>\n";
			echo  " <script type=\"text/javascript\">sURL = 'index.php?gadget=Users&action=UpdateRSSUsers&fetch_url=".urlencode($searchfetch_url)."&rss_url=".urlencode($searchrss_url)."&num=".(int)$searchnum."&ua=Y';</script>\n";
			$searchfetch_url = str_replace(' ', '%20', $searchfetch_url);
			$adminModel->InsertRSSUsers($searchfetch_url, $searchrss_url, (int)$searchnum, 'Y');
			/*
			if (Jaws_Error::IsError($result)) {
				echo '<br />'.$result->GetMessage();
			}
			*/
		} else if (!empty($searchfile) && !empty($searchtype) && (!empty($searchnum) || (int)$searchnum == 0 || (int)$searchnum == 1)) {		
			echo  " <body onload='doLoad(); submitForm();'>\n";
			echo  " <script type=\"text/javascript\">sURL = 'index.php?gadget=Users&action=UpdateRSSUsers&file=".$searchfile."&type=".$searchtype."&num=".(int)$searchnum."&ua=Y';</script>\n";
			//echo '<br />file ::: '.$searchfile;
			$adminModel->InsertUsers($searchfile, $searchtype, $searchnum, $user_attended);
		} else {
			echo '<br />'.'Parameters not supplied.';

		}
		echo " </body>\n";
		echo "</html>\n";
		//echo "<script type=\"text/javascript\">location.href='" . BASE_SCRIPT . "';</script>";
		//echo "<h1>Feed Imported Successfully</h1>";

		return true;
	}
    	
    /**
     * Account AddLayoutElement
     *
     * @access 	public
     * @return 	string 	HTML content of AdminHTML->AddLayoutElement()
     */
    function account_AddLayoutElement()
    {
		$gadget_admin = $GLOBALS['app']->LoadGadget('Users', 'AdminHTML');
		$page = $gadget_admin->AddLayoutElement();
		/*
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$output_html = str_replace("__JAWS_GADGET__", $page, $users_html->GetAccountHTML('Users'));
		return $output_html;
		*/
		return $page;
    }

    /**
     * Account SaveLayoutElement
     *
     * @access public
     * @return 	string 	HTML content of AdminHTML->SaveLayoutElement()
     */
    function account_SaveLayoutElement()
    {
		$gadget_admin = $GLOBALS['app']->LoadGadget('Users', 'AdminHTML');
		$page = $gadget_admin->SaveLayoutElement();
		/*
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$output_html = str_replace("__JAWS_GADGET__", $page, $users_html->GetAccountHTML('Users'));
		return $output_html;
		*/
		return $page;
    }

    /**
     * Account EditElementAction
     *
     * @access public
     * @return 	string 	HTML content of AdminHTML->EditElementAction()
     */
    function account_EditElementAction()
    {
		$gadget_admin = $GLOBALS['app']->LoadGadget('Users', 'AdminHTML');
		$page = $gadget_admin->EditElementAction();
		/*
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$output_html = str_replace("__JAWS_GADGET__", $page, $users_html->GetAccountHTML('Users'));
		return $output_html;
		*/
		return $page;
    }
    
    /**
     * Account GetQuickAddForm
     *
     * @access public
     * @return 	string 	HTML content of AdminHTML->GetQuickAddForm()
     */
    function account_GetQuickAddForm()
    {
		$gadget_admin = $GLOBALS['app']->LoadGadget('Users', 'AdminHTML');
		$page = $gadget_admin->GetQuickAddForm(true);
		/*
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$output_html = str_replace("__JAWS_GADGET__", $page, $users_html->GetAccountHTML('Users'));
		return $output_html;
		*/
		return $page;
    }
    
    /**
     * Account ShareComment
     *
     * @access public
     * @return 	string 	HTML content of AdminHTML->ShareComment()
     */
    function account_ShareComment()
    {
		$gadget_admin = $GLOBALS['app']->LoadGadget('Users', 'AdminHTML');
		$page = $gadget_admin->ShareComment();
		return $page;
    }
    
    /**
     * Account AuthUserGroup
     *
     * @access 	public
     * @return 	string 	HTML content of AdminHTML->AuthUserGroup()
     */
    function account_AuthUserGroup($user = null, $group = null, $status = null)
    {
		$gadget_admin = $GLOBALS['app']->LoadGadget('Users', 'AdminHTML');
		$page = $gadget_admin->AuthUserGroup($user, $group, $status);
		/*
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$output_html = str_replace("__JAWS_GADGET__", $page, $users_html->GetAccountHTML('Users'));
		return $output_html;
		*/
		return $page;
    }

    /**
     * Account Groups
     *
     * @access public
     * @return 	string 	HTML content of AdminHTML->Groups()
     */
    function account_Groups()
    {
		$GLOBALS['app']->Layout->AddHeadLink('gadgets/Users/resources/style.css', 'stylesheet', 'text/css');
		$gadget_admin = $GLOBALS['app']->LoadGadget('Users', 'AdminHTML');
		$page = $gadget_admin->Groups(true);
		/*
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$output_html = str_replace("__JAWS_GADGET__", "<div id=\"msgbox-wrapper\"></div>\n".$page, $users_html->GetAccountHTML('Users'));
		return $output_html;
		*/
		return $page;
    }
    
    /**
     * sets GB root with DPATH
     *
     * @access 	public
     * @return 	string 	Javascript response
     */
    function account_SetGBRoot()
    {
		// Make sure we output a real JavaScript file!
		header('Content-type: text/javascript'); 
		echo "var GB_ROOT_DIR = \"data/greybox/\";";
	}
}
