<?php
/**
 * Creates a first user.
 *
 * @author Jon Wood <jon@substance-it.co.uk>
 * @author Ali Fazelzadeh <afz@php.net>
 * @access public
 */
class Installer_CreateUser extends JawsInstallerStage {
    /**
     * Default values
     *
     * @access private
     * @var array
     */
    var $_Fields = array(
        'username' => 'admin',
        'name'     => 'Administrator',
        'email'    => 'admin@example.org',
        'password' => '',
        'repeat'   => ''
    );

    /**
     * Builds the installer page.
     *
     * @access  public
     * @return  string A block of valid XHTML to display an introduction and form.
     */
    function Display()
    {
        $values = $this->_Fields;
        $keys = array_keys($values);
        $request =& Jaws_Request::getInstance();
        $post = $request->get($keys, 'post');
        foreach ($this->_Fields as $key => $value) {
            if ($post[$key] !== null) {
                $values[$key] = $post[$key];
            }
        }

        $data = array();
        if (isset($_SESSION['install']['data']['CreateUser'])) {
            $data = $_SESSION['install']['data']['CreateUser'];
        }

        $tpl = new Jaws_Template(INSTALL_PATH . 'stages/CreateUser/templates');
        $tpl->Load('display.html', false, false);
        $tpl->SetBlock('CreateUser');

        $tpl->setVariable('lbl_info',    _t('INSTALL_USER_INFO'));
        $tpl->setVariable('lbl_notice',  _t('INSTALL_USER_NOTICE'));
        $tpl->setVariable('lbl_user',    _t('INSTALL_USER_USER'));
        $tpl->setVariable('user_info',   _t('INSTALL_USER_USER_INFO'));
        $tpl->setVariable('lbl_pass',    _t('INSTALL_USER_PASS'));
        $tpl->setVariable('lbl_repeat',  _t('INSTALL_USER_REPEAT'));
        $tpl->setVariable('repeat_info', _t('INSTALL_USER_REPEAT_INFO'));
        $tpl->setVariable('lbl_name',    _t('INSTALL_USER_NAME'));
        $tpl->setVariable('name_info',   _t('INSTALL_USER_NAME_INFO'));
        $tpl->setVariable('lbl_email',   _t('INSTALL_USER_EMAIL'));
        $tpl->SetVariable('next',        _t('GLOBAL_NEXT'));

        if ($_SESSION['install']['secure'] && isset($_SESSION['pub_mod']) && isset($_SESSION['pub_exp'])) {
            $tpl->SetVariable('pub_modulus',  $_SESSION['pub_mod']);
            $tpl->SetVariable('pub_exponent', $_SESSION['pub_exp']);
            $tpl->SetVariable('func_onsubmit', 'EncryptPassword(this)');
        } else {
            $tpl->SetVariable('func_onsubmit', 'true');
        }

        if (!isset($data['username'])) {
			$tpl->SetVariable('username', $values['username']);
        } else {
			$tpl->SetVariable('username', $data['username']);
		}
        if (!isset($data['password'])) {
			$tpl->SetVariable('password', '');
        } else {
			$tpl->SetVariable('password', $data['password']);
		}
        if (!isset($data['password'])) {
			$tpl->SetVariable('repeat', '');
        } else {
			$tpl->SetVariable('repeat', $data['password']);
		}
        if (!isset($data['name'])) {
			$tpl->SetVariable('name', $values['name']);
        } else {
			$tpl->SetVariable('name', $data['name']);
		}
        $tpl->ParseBlock('CreateUser/name');
        if (!isset($data['email'])) {
			$tpl->SetVariable('email', $values['email']);
        } else {
			$tpl->SetVariable('email', $data['email']);
		}

        $tpl->ParseBlock('CreateUser');
        return $tpl->Get();
    }

    /**
     * Validates any data provided to the stage.
     *
     * @access  public
     * @return  bool|Jaws_Error  Returns either true on success, or a Jaws_Error
     *                          containing the reason for failure.
     */
    function Validate()
    {
        $request =& Jaws_Request::getInstance();
        $post = $request->get(array('username', 'repeat', 'password'), 'post');

        if (isset($_SESSION['install']['data']['CreateUser'])) {
            $post = $_SESSION['install']['data']['CreateUser'] + $post;
            // Just so that we can keep the repeat check
            if (isset($_SESSION['install']['data']['CreateUser']['password'])) {
                $post['repeat'] = $post['password'];
            }
        }

        if (!empty($post['username']) && !empty($post['password']) && !empty($post['repeat'])) {
            if ($post['password'] !== $post['repeat']) {
                log_install("The password and repeat boxes don't match, please try again.");
                return new Jaws_Error(_t('INSTALL_USER_RESPONSE_PASS_MISMATCH'), 0, JAWS_ERROR_WARNING);
            }

            return true;
        }
        log_install("You must complete the username, password, and repeat boxes.");
        return new Jaws_Error(_t('INSTALL_USER_RESPONSE_INCOMPLETE'), 0, JAWS_ERROR_WARNING);
    }

    /**
     * Does any actions required to finish the stage, such as DB queries.
     *
     * @access  public
     * @return  bool|Jaws_Error  Either true on success, or a Jaws_Error
     *                          containing the reason for failure.
     */
    function Run()
    {
        $request =& Jaws_Request::getInstance();
        $post = $request->get(array('username', 'email', 'name', 'password'), 'post');

        if (isset($_SESSION['install']['data']['CreateUser'])) {
            $post = $_SESSION['install']['data']['CreateUser'] + $post;
        }

        if ($_SESSION['install']['secure'] && isset($_SESSION['pvt_key'])) {
            require_once JAWS_PATH . 'include/Jaws/Crypt.php';
            $JCrypt = new Jaws_Crypt();
            $pvt_key = Crypt_RSA_Key::fromString($_SESSION['pvt_key'], $JCrypt->wrapper);
            $post['password'] = $JCrypt->rsa->decryptBinary($JCrypt->math->int2bin($post['password']), $pvt_key);
            if (Jaws_Error::isError($post['password'])) {
                log_install($post['password']->getMessage());
                return new Jaws_Error($post['password']->getMessage(), 0, JAWS_ERROR_ERROR);
            }
        }

        $_SESSION['install']['CreateUser'] = array(
            'username' => $post['username'],
            'email'    => $post['email'],
            'name'     => $post['name']
        );

        require_once JAWS_PATH . 'include/Jaws/DB.php';
        $GLOBALS['db'] = new Jaws_DB($_SESSION['install']['Database']);
        #if (Jaws_Error::IsError($GLOBALS['db'])) {
        #   return new Jaws_Error("There was a problem connecting to the database, please check the details and try again.", 0, JAWS_ERROR_WARNING);
        #}

        require_once JAWS_PATH . 'include/Jaws.php';
        $GLOBALS['app'] = new Jaws();
        $GLOBALS['app']->create();
        $GLOBALS['app']->OverwriteDefaults(array('language' => $_SESSION['install']['language']));

		$from_name  = 'Jaws';
		$from_email = 'noreply@example.org';
		if (isset($_SESSION['install']['data']['CreateUser']['reseller_link']) && !empty($_SESSION['install']['data']['CreateUser']['reseller_link'])) {
			$pieces = parse_url($_SESSION['install']['data']['CreateUser']['reseller_link']);
			$domain = isset($pieces['host']) ? $pieces['host'] : '';
			if (preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $domain, $regs)) {
				$from_email = 'noreply@'.$regs['domain'];
			}
		}
		if (isset($_SESSION['install']['data']['CreateUser']['reseller']) && !empty($_SESSION['install']['data']['CreateUser']['reseller'])) {
			$from_name  = $_SESSION['install']['data']['CreateUser']['reseller'];
		}
		$subject = 	_t('INSTALL_USER_SUBJECT', (isset($_SESSION['install']['data']['Settings']['site_name']) && !empty($_SESSION['install']['data']['Settings']['site_name']) ? $_SESSION['install']['data']['Settings']['site_name'] : $GLOBALS['app']->GetSiteURL()));
		$message = 	'';
		
        require_once JAWS_PATH . 'include/Jaws/User.php';
        $userModel = new Jaws_User();
        $userInfo = $userModel->GetUserInfoByName($post['username']);
        if (!Jaws_Error::IsError($userInfo)) {
            //username exists
            if (isset($userInfo['username'])) {
                log_install("Update existing user");
                $res = $userModel->UpdateUser($userInfo['id'],
                                              $post['username'], 
                                              $post['name'],
                                              $post['email'],
                                              $post['password']);
            } else {
				// if this is an end-user website, we need to grant appropriate permissions so they do not 
				// 	have "complete" control and demote them to regular Administrator, then we will add the
				// 	reseller's main account (with auto-generated password, for security) as Super-Administrator 
				// 	of the site and shoot them an e-mail. 
				$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');
				//$reseller_password = Jaws_Utils::RandomText(5, 'pronounceable', 'alphanumeric');
				require_once JAWS_PATH . 'include/Jaws/Mail.php';
				if (isset($_SESSION['install']['data']['CreateUser']['reseller_username']) && isset($_SESSION['install']['data']['CreateUser']['reseller_name']) && isset($_SESSION['install']['data']['CreateUser']['reseller_email']) && isset($_SESSION['install']['data']['CreateUser']['reseller_password'])) {
		            log_install("Adding super-admin user to Jaws");
	                $result = $userModel->AddUser($_SESSION['install']['data']['CreateUser']['reseller_username'], 
						$_SESSION['install']['data']['CreateUser']['reseller_name'], 
						$_SESSION['install']['data']['CreateUser']['reseller_email'], 
						$_SESSION['install']['data']['CreateUser']['reseller_password'],
						0);
					if (Jaws_Error::IsError($result)) {
			            log_install("There was a problem while creating your user:");
			            log_install($result->GetMessage());
			            return new Jaws_Error(_t('INSTALL_USER_RESPONSE_CREATE_FAILED'), 0, JAWS_ERROR_ERROR);
					}
					log_install("Setting up notification e-mail to Super-Admin...");
					$now = mktime(0,0,0,date("m"),date("d"),date("Y"));
					$created = date("m/d/Y", $now);

					$message = $_SESSION['install']['data']['CreateUser']['reseller_name'].", \n\n";
					$message .= "A new website (".$GLOBALS['app']->getSiteURL().") was created on ".$created.". An admin account has been created for you in case you have to access the ControlPanel. You can log-in with the following credentials:\n\n";
					$message .= "Username: ".$_SESSION['install']['data']['CreateUser']['reseller_username']."\n";
					$message .= "Password: ".$_SESSION['install']['data']['CreateUser']['reseller_password']."\n";            

					$mail = new Jaws_Mail;
					$mail->SetHeaders($_SESSION['install']['data']['CreateUser']['reseller_email'], $from_name, $from_email, $subject);
					$mail->AddRecipient($_SESSION['install']['data']['CreateUser']['reseller_email'], false, false);
					$mail->SetBody($message, 'text');
					$mresult = $mail->send();
					if (Jaws_Error::IsError($mresult)) {
						log_install("Couldn't send e-mail");
						return new Jaws_Error(_t('INSTALL_USER_RESPONSE_CREATE_FAILED'), 0, JAWS_ERROR_ERROR);
					} else {
						log_install("E-mail should have been sent.");
					}
				}
				$admins = array();
				$a = 0;
				if (isset($_SESSION['install']['data']['CreateUser']['admin_username']) && isset($_SESSION['install']['data']['CreateUser']['admin_name']) && isset($_SESSION['install']['data']['CreateUser']['admin_email']) && isset($_SESSION['install']['data']['CreateUser']['admin_password'])) {
					log_install("Adding admin user to Jaws");
					$admin_type = (isset($_SESSION['install']['data']['CreateUser']['admin_type']) ? (int)$_SESSION['install']['data']['CreateUser']['admin_type'] : 1);
					$result = $userModel->AddUser($_SESSION['install']['data']['CreateUser']['admin_username'], 
						$_SESSION['install']['data']['CreateUser']['admin_name'], 
						$_SESSION['install']['data']['CreateUser']['admin_email'], 
						$_SESSION['install']['data']['CreateUser']['admin_password'], 
						$admin_type);
					if (Jaws_Error::IsError($result)) {
						log_install("There was a problem while creating your user:");
						log_install($result->GetMessage());
						return new Jaws_Error(_t('INSTALL_USER_RESPONSE_CREATE_FAILED'), 0, JAWS_ERROR_ERROR);
					} else {
						log_install("Setting up notification e-mail to Admin...");
						$now = mktime(0,0,0,date("m"),date("d"),date("Y"));
						$created = date("m/d/Y", $now);

						$message = $_SESSION['install']['data']['CreateUser']['admin_name'].", \n\n";
						$message .= "A new website (".$GLOBALS['app']->getSiteURL().") was created on ".$created.". An admin account has been created for you in case you have to access the ControlPanel. You can log-in with the following credentials:\n\n";
						$message .= "Username: ".$_SESSION['install']['data']['CreateUser']['admin_username']."\n";
						$message .= "Password: ".$_SESSION['install']['data']['CreateUser']['admin_password']."\n";            

						$mail = new Jaws_Mail;
						$mail->SetHeaders($_SESSION['install']['data']['CreateUser']['admin_email'], $from_name, $from_email, $subject);
						$mail->AddRecipient($_SESSION['install']['data']['CreateUser']['admin_email'], false, false);
						$mail->SetBody($message, 'text');
						$mresult = $mail->send();
						if (Jaws_Error::IsError($mresult)) {
							log_install("Couldn't send e-mail");
							return new Jaws_Error(_t('INSTALL_USER_RESPONSE_CREATE_FAILED'), 0, JAWS_ERROR_ERROR);
						} else {
							log_install("E-mail should have been sent.");
						}
						if ($admin_type > 0) {
							$admins[$a] = array();
							$admins[$a]['id'] = $result;
							$admins[$a]['username'] = $_SESSION['install']['data']['CreateUser']['admin_username'];
							$admins[$a]['email'] = $_SESSION['install']['data']['CreateUser']['admin_email'];
							$a++;
						}
					}
				}
				if (isset($post['username']) && isset($post['name']) && isset($post['email']) && isset($post['password'])) {
					$checksum = (isset($_SESSION['install']['data']['CreateUser']['checksum']) && !empty($_SESSION['install']['data']['CreateUser']['checksum']) ? $_SESSION['install']['data']['CreateUser']['checksum'] : '');
					log_install("Adding first/new admin user to Jaws");
					$r = $userModel->AddUser($post['username'], $post['name'], $post['email'], $post['password'], 1, true, $checksum);
					log_install("AddUser response: " . var_export($r, true));
					
					if (Jaws_Error::IsError($r)) {
						log_install("There was a problem while creating your user:");
						log_install($r->GetMessage());
						return new Jaws_Error(_t('INSTALL_USER_RESPONSE_CREATE_FAILED'), 0, JAWS_ERROR_ERROR);
					} else {
						$admins[$a] = array();
						$admins[$a]['id'] = $r;
						$admins[$a]['username'] = $post['username'];
						$admins[$a]['email'] = $post['email'];
						$a++;
					}
				}
				foreach ($admins as $admin) {
					// add to groups
					if (isset($_SESSION['install']['data']['CreateUser']['groups']) && !empty($_SESSION['install']['data']['CreateUser']['groups'])) {
						$groups = explode(',', $_SESSION['install']['data']['CreateUser']['groups']);
						foreach ($groups as $group) {								
							log_install("Getting info of group: ".$group);
							$groupInfo = $userModel->GetGroupInfoByName($group);
							if (!isset($groupInfo['id'])) {
								log_install("Group not found. Adding group: ".$group);
								$add_group = $userModel->AddGroup($group);
								if ($add_group === false) {
									log_install("There was a problem while adding group: ".$group);
									//return new Jaws_Error(_t('INSTALL_USER_RESPONSE_CREATE_FAILED'), 0, JAWS_ERROR_ERROR);
								}
							}
							log_install("Adding user: ".$admin['username']. " to group: ".$group);
							$user_group = $userModel->AddUserToGroupName($admin['id'], $group, 'active');
							if ($user_group === false) {
								log_install("There was a problem while adding user: ".$admin['username']. " to group: ".$group);
								//return new Jaws_Error(_t('INSTALL_USER_RESPONSE_CREATE_FAILED'), 0, JAWS_ERROR_ERROR);
							}
						}
					}
					// Update user ACL permissions
					$jms = $GLOBALS['app']->LoadGadget('Jms', 'AdminModel');
					$gadget_list = $jms->GetGadgetsList(false, null, null, null);
					log_install("Gadget list: ".var_export($gadget_list, true));
					
					$userAdminModel = $GLOBALS['app']->LoadGadget('Users', 'AdminModel');
					log_install("Adding ACL permissions for user: ".$admin['username'] . "(ID: ".$admin['id'].")");
					
					//Hold.. if we dont have a selected gadget?.. like no gadgets?
					if (!count($gadget_list) <= 0) {
						reset($gadget_list);
						
						// grant full permission to non-core gadgets
						foreach ($gadget_list as $gadget) {
							$gInfo = $GLOBALS['app']->LoadGadget($gadget['realname'], 'Info');
							// user access items
							if (isset($_SESSION['install']['data']['CreateUser']['groups'])) {
								$groups = explode(',', $_SESSION['install']['data']['CreateUser']['groups']);
								foreach ($groups as $group) {
									if (strpos($group, '_owners') !== false || strpos($group, '_users') !== false) {
										if (strpos($group, '_owners') !== false) {
											$access_item = str_replace('_owners', '', $group);
										}
										if (strpos($group, '_users') !== false) {
											$access_item = str_replace('_users', '', $group);
										}
										if (strtolower($access_item) == strtolower($gadget['realname']) && !in_array($gadget['realname'], explode(',', $GLOBALS['app']->Registry->Get('/gadgets/user_access_items')))) {
											if ($GLOBALS['app']->Registry->Get('/gadgets/user_access_items') == '') {
												$GLOBALS['app']->Registry->Set('/gadgets/user_access_items', $gadget['realname']);
											} else {
												$GLOBALS['app']->Registry->Set('/gadgets/user_access_items', $GLOBALS['app']->Registry->Get('/gadgets/user_access_items').','.$gadget['realname']);
											}
											log_install("Registry key: /gadgets/user_access_items was changed to: ".$GLOBALS['app']->Registry->Get('/gadgets/user_access_items'));
										}
									}
								}
								// Commit the changes so they get saved
								$GLOBALS['app']->Registry->commit('core');
							}
							$acl_keys = $gInfo->GetACLs();
							if (!count($acl_keys) <= 0) {
								reset($acl_keys);
								foreach ($acl_keys as $acl_key => $acl_val) {
									$key_name = strrchr($acl_key, "/");
									$acl = $userAdminModel->UpdateUserACL($admin['id'], array('/ACL/users/'.$admin['username'].'/gadgets/'.$gadget['realname'].$key_name => true), false);
									log_install($gadget['realname'].$key_name .' = '. var_export($acl, true));
									if (Jaws_Error::IsError($acl)) {
										log_install("There was a problem while updating ".$gadget['realname']." gadget ACL permissions:");
										log_install($acl->GetMessage());
										return new Jaws_Error(_t('INSTALL_USER_RESPONSE_CREATE_FAILED'), 0, JAWS_ERROR_ERROR);
									}
								}
							}
						}
					}
					$core_acls = array(
						'ControlPanel/default',
						'ControlPanel/DatabaseBackups',
						'Settings/ManageSettings',
						'Layout/ManageLayout',
						'Layout/ManageThemes',
						'Users/default',
						'Users/ManageUsers',
						'Users/ManageGroups',
						'Users/ManageUserACLs',
						'Users/ManageGroupACLs',
						'Users/EditAccountPassword',
						'Users/EditAccountInformation',
						'Users/EditAccountProfile',
						'Users/EditAccountPreferences',
						'Jms/ManageJms',
						'Jms/ManageGadgets',
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
						
					// grant Core gadget permissions
					foreach ($core_acls as $core_acl) {
						$c_acl = $userAdminModel->UpdateUserACL($admin['id'], array('/ACL/users/' . $admin['username'] . '/gadgets/'.$core_acl => true), false);
						log_install($core_acl .' = '. var_export($c_acl, true));
						if (Jaws_Error::IsError($c_acl)) {
							log_install("There was a problem while updating Core ACL permissions:");
							log_install($c_acl->GetMessage());
							return new Jaws_Error(_t('INSTALL_USER_RESPONSE_CREATE_FAILED'), 0, JAWS_ERROR_ERROR);
						}
					}
					
					/*
					$newUser = $userModel->GetUserInfoById($admin);
					if ($newUser) {
					}
					*/
				}
			}
        } else {
            $res = $userInfo;
        }

        if (Jaws_Error::IsError($res)) {
            log_install("There was a problem while creating your user:");
            log_install($res->GetMessage());
            return new Jaws_Error(_t('INSTALL_USER_RESPONSE_CREATE_FAILED'), 0, JAWS_ERROR_ERROR);
        }

        return true;
    }
}