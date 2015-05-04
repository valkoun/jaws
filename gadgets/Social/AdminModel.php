<?php
/**
 * Social Gadget Model
 *
 * @category   GadgetModel
 * @package    Social
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2004-2009 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
require_once JAWS_PATH . 'gadgets/Social/Model.php';

class SocialAdminModel extends SocialModel
{
    var $_Name = 'Social';
    var $_newChecksums = array();
    var $_propCount = 1;
    var $_propTotal = 0;
   
	/**
     * Install the gadget
     *
     * @access  public
     * @return  boolean true on successful installation, Jaws_Error otherwise
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
        $GLOBALS['app']->Shouter->NewShouter('Social', 'onBeforeSocialSharing');
        
		$GLOBALS['app']->loadClass('Listener', 'Jaws_EventListener');
        $GLOBALS['app']->Listener->NewListener('Social', 'onDeleteUser', 'RemoveUserSocial');
		
		$GLOBALS['app']->Registry->NewKey('/gadgets/Social/webs', 'facebook');
        $GLOBALS['app']->Registry->NewKey('/gadgets/Social/iconset', 'normal');

		//Create Jaws_User groups
        require_once JAWS_PATH . 'include/Jaws/User.php';
        $userModel = new Jaws_User;
        $userModel->addGroup('social_owners', false); //Don't check if it returns true or false
        $group = $userModel->GetGroupInfoByName('social_owners');
		if (isset($group['id']) && !empty($group['id'])) {
			$GLOBALS['app']->ACL->NewKey('/ACL/groups/'.$group['id'].'/gadgets/Social/OwnSocial', 'true');
        }
        //$userModel->addGroup('social_users', false); //Don't check if it returns true or false
        
		return true;
    }

    /**
     * Uninstalls the gadget
     *
     * @access  public
     * @return  boolean  Success/Failure (Jaws_Error)
     */
    function UninstallGadget()
    {
        $tables = array('emails', 'social_users');
        foreach ($tables as $table) {
            $result = $GLOBALS['db']->dropTable($table);
            if (Jaws_Error::IsError($result)) {
                $gName  = _t('SOCIAL_NAME');
                $errMsg = _t('GLOBAL_ERROR_GADGET_NOT_UNINSTALLED', $gName);
                $GLOBALS['app']->Session->PushLastResponse($errMsg, RESPONSE_ERROR);
                return new Jaws_Error($errMsg, $gName);
            }
        }
        
		// Events
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
        $GLOBALS['app']->Shouter->DeleteShouter('Social', 'onBeforeSocialSharing');
        
		$GLOBALS['app']->loadClass('Listener', 'Jaws_EventListener');
        $GLOBALS['app']->Listener->DeleteListener('Social', 'RemoveUserSocial');
        
		// registry keys
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/Social/webs');
        $GLOBALS['app']->Registry->DeleteKey('/gadgets/Social/iconset');

		//Delete Jaws_User groups
        require_once JAWS_PATH . 'include/Jaws/User.php';
        $userModel = new Jaws_User;
        $group = $userModel->GetGroupInfoByName('social_owners');
		if (isset($group['id']) && !empty($group['id'])) {
			$result = $userModel->DeleteGroup($group['id']);
			$GLOBALS['app']->ACL->DeleteKey('/ACL/groups/'.$group['id'].'/gadgets/Social/OwnSocial');
            if (Jaws_Error::IsError($result)) {
				echo $result->getMessage();
			}
		}
        /*
		$group = $userModel->GetGroupInfoByName('social_users');
		if (isset($group['id']) && !empty($group['id'])) {
			$userModel->DeleteGroup($group['id']);
		}
        */
        
		return true;
    }

    /**
     * Update the gadget
     *
     * @access  public
     * @param   string  $old Current version (in registry)
     * @param   string  $new     New version (in the $gadgetInfo file)
     * @return  boolean  Success/Failure (JawsError)
     */
    function UpdateGadget($old, $new)
    {
        if (version_compare($old, '0.8.1', '<')) {			
			$result = $this->installSchema('schema.xml', '', '0.8.0.xml');
			if (Jaws_Error::IsError($result)) {
				return $result;
			}
			$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
			$GLOBALS['app']->Shouter->NewShouter('Social', 'onBeforeSocialSharing');
		}
        
		if (version_compare($old, '0.8.2', '<')) {			
			$result = $this->installSchema('schema.xml', '', '0.8.1.xml');
			if (Jaws_Error::IsError($result)) {
				return $result;
			}
		}
        
		return true;
    }

    /**
     * Update preferences
     *
     * @access  public
     * @param   array   $preferences_config
     * @return  array   Response (notice or error)
     */
    function UpdateSocial($social_config, $social_urls = array(), $social_ids = array())
    {
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$model = $GLOBALS['app']->LoadGadget('Social', 'Model');
		if (BASE_SCRIPT == 'index.php') {
			$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');
		} else {
			$OwnerID = 0;
		}
		$str = '';
		$comma = '';
		$social_exists = $model->GetSocialOfUserID($OwnerID);
		if (Jaws_Error::IsError($social_exists)) {
			$GLOBALS['app']->Session->PushLastResponse($social_exists->GetMessage(), RESPONSE_ERROR);
			return $social_exists;
		}
		
		$socials = parent::getSocialWebsites();
        foreach ($socials as $Key => $Value) {
            if (isset($social_config[$Key]) && $social_config[$Key] === true) {
				$existing = array();
				foreach ($social_exists as $exist) {
					if ($exist['social'] == $Key) {
						$existing = $exist;
						break;
					}
				}
				$insert = false;
				$checksum = '';
				$params = array();
				$social_url = (isset($social_urls[$Key]) && !empty($social_urls[$Key]) ? $xss->parse(strip_tags($social_urls[$Key])) : null);
				$social_id = (isset($social_ids[$Key][0]) && !empty($social_ids[$Key][0]) ? strip_tags($social_ids[$Key][0]) : null);
				$social_id2 = (isset($social_ids[$Key][1]) && !empty($social_ids[$Key][1]) ? strip_tags($social_ids[$Key][1]) : null);
				$social_id3 = (isset($social_ids[$Key][2]) && !empty($social_ids[$Key][2]) ? strip_tags($social_ids[$Key][2]) : null);
				
				$params['social'] 			= $Key;
				$params['social_url'] 		= $social_url;
				$params['social_id'] 		= $social_id;
				$params['social_id2'] 		= $social_id2;
				$params['social_id3'] 		= $social_id3;
				$params['OwnerID'] 			= (int)$OwnerID;
				$params['now'] 				= $GLOBALS['db']->Date();
				
				$sql = "
					UPDATE [[social_users]] SET
						[social_url] = {social_url},
						[social_id] = {social_id},
						[social_id2] = {social_id2},
						[social_id3] = {social_id3},
						[active] = {Active},
						[updated] = {now}
					WHERE [social] = {social} AND [ownerid] = {OwnerID}
				";
				
				if ((isset($social_urls[$Key]) && !empty($social_urls[$Key])) || (isset($social_ids[$Key]) && !empty($social_ids[$Key][0]))) {
					if (!isset($existing['id']) || empty($existing['id'])) {
						$insert = true;
						$sql = "
							INSERT INTO [[social_users]]
								([social], [social_url], [social_id], 
								[social_id2], [social_id3], [active], 
								[ownerid], [created], [updated])
							VALUES
								({social}, {social_url}, {social_id}, 
								{social_id2}, {social_id3}, {Active}, 
								{OwnerID}, {now}, {now})
						";
					}
					$params['Active'] = 'Y';
                } else {
					$params['Active'] = 'N';
				}
				
				$result = $GLOBALS['db']->query($sql, $params);
				if (Jaws_Error::IsError($result)) {
					$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
					return $result;
				}
				
				if ($insert === true && empty($checksum)) {
					$newid = $GLOBALS['db']->lastInsertID('social_users', 'id');
					
					// Update checksum
					$config_key = $GLOBALS['app']->Registry->Get('/config/key');		
					$params               	= array();
					$params['id'] 			= $newid;
					$params['checksum'] 	= $newid.':'.$config_key;
					
					$sql = '
						UPDATE [[social_users]] SET
							[checksum] = {checksum}
						WHERE [id] = {id}';

					$result = $GLOBALS['db']->query($sql, $params);
					if (Jaws_Error::IsError($result)) {
						$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
						return $result;
					}
				}
				
				$str .= $comma.$Key;
				$comma=",";
			}
		}
				
		$res = $GLOBALS['app']->Registry->Set("/gadgets/Social/webs",$str);
        if (!$res) {
        	$GLOBALS['app']->Session->PushLastResponse(_t('SOCIAL_ERROR_PROPERTIES_NOT_UPDATED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('SOCIAL_ERROR_PROPERTIES_NOT_UPDATED'), _t('SOCIAL_NAME'));
        }

        $GLOBALS['app']->Registry->Commit('core');
        $GLOBALS['app']->Registry->Commit('Social');

        $GLOBALS['app']->Session->PushLastResponse(_t('SOCIAL_PROPERTIES_UPDATED'), RESPONSE_NOTICE);
        return true;
    }

    /**
     * Creates a new email.
     *
     * @access  public
     * @return  ID of entered post 	    Success/failure
     */
    function AddEmail($email, $name, $address = '', $city = '', $country = '', $state = '', 
		$postal = '', $phone = '', $website = '', $company = '', $active = 'Y', $OwnerID = null, $checksum = '', $auto = false)
    {        
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$model = $GLOBALS['app']->LoadGadget('Social', 'Model');
		if (BASE_SCRIPT == 'index.php' && is_null($OwnerID)) {
			$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');
		} else {
			$OwnerID = (!is_null($OwnerID)) ? (int)$OwnerID : 0;
		}
		
			$sql = "
			INSERT INTO [[emails]]
				([name], [email], [createtime], [updatetime], 
				[company], [website], [address], [city], 
				[region], [postal], [country], [phone], [active],
				[ownerid], [checksum])
			VALUES
				({name}, {email}, {now}, {now}, 
				{company}, {website}, {address}, {city},
				{region}, {postal}, {country}, {phone}, {Active},
				{OwnerID}, {checksum})";

        $params               	= array();
		$params['id']         	= (int)$id;
        $params['name'] 		= $xss->parse($title);
        $params['email'] 		= $xss->parse($email);
        $params['website'] 		= $xss->parse($website);
        $params['company'] 		= $xss->parse($company);
        $params['address'] 		= $xss->parse($address);
        $params['city'] 		= $xss->parse($city);
        $params['region'] 		= $xss->parse($region);
        $params['postal'] 		= $xss->parse($postal);
        $params['phone'] 		= $xss->parse($phone);
        $params['Active'] 		= $xss->parse($active);
        $params['checksum'] 	= $xss->parse($checksum);
        $params['OwnerID'] 		= (int)$OwnerID;
        $params['now']        	= $GLOBALS['db']->Date();

		$result = $GLOBALS['db']->query($sql, $params);
		if (Jaws_Error::IsError($result)) {
			return new Jaws_Error(_t('SOCIAL_ERROR_EMAIL_NOT_ADDED'), _t('SOCIAL_NAME'));
		}
		$newid = $GLOBALS['db']->lastInsertID('emails', 'id');

		if (empty($checksum)) {
			// Update checksum
			$config_key = $GLOBALS['app']->Registry->Get('/config/key');		
			$params               	= array();
			$params['id'] 			= $newid;
			$params['checksum'] 	= $newid.':'.$config_key;
			
			$sql = '
				UPDATE [[emails]] SET
					[checksum] = {checksum}
				WHERE [id] = {id}';

			$result = $GLOBALS['db']->query($sql, $params);
			if (Jaws_Error::IsError($result)) {
				$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
				return false;
			}
		}
		
		// Let everyone know
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onAddSocialEmail', $newid);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}

		$GLOBALS['app']->Session->PushLastResponse(_t('SOCIAL_EMAIL_CREATED'), RESPONSE_NOTICE);
		return $newid;
    }

    /**
     * Updates a post.
     *
     * @access  public
     * @param   int	$id	The ID of the post to update.
     * @return  boolean Success/failure
     */
    function UpdateEmail($id, $email, $name, $address = '', $city = '', $country = '', $state = '', 
		$postal = '', $phone = '', $website = '', $company = '', $active = 'Y', $auto = false)
	{
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$model = $GLOBALS['app']->LoadGadget('Social', 'Model');
        $page = $model->GetEmail($id);
        if (Jaws_Error::isError($page)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('SOCIAL_ERROR_EMAIL_NOT_FOUND'), RESPONSE_ERROR);
            return new Jaws_Error(_t('SOCIAL_ERROR_EMAIL_NOT_FOUND'), _t('SOCIAL_NAME'));
        }
		
		$sql = '
            UPDATE [[emails]] SET
				[name] = {name},
				[email] = {email},
				[updatetime] = {now},
				[website] = {website},
				[company] = {company},
				[address] = {address},
				[city] = {city},
				[region] = {region},
				[postal] = {postal},
				[phone] = {phone},
				[active] = {Active}';
		if ($active	== 'N') {
				$sql .= ',
				[closetime] = {now}';
		}
		$sql .= '
			WHERE [id] = {id}';

       
        $params               	= array();
		$params['id']         	= (int)$id;
        $params['name'] 		= $xss->parse($title);
        $params['email'] 		= $xss->parse($email);
        $params['website'] 		= $xss->parse($website);
        $params['company'] 		= $xss->parse($company);
        $params['address'] 		= $xss->parse($address);
        $params['city'] 		= $xss->parse($city);
        $params['region'] 		= $xss->parse($region);
        $params['postal'] 		= $xss->parse($postal);
        $params['phone'] 		= $xss->parse($phone);
        $params['Active'] 		= $xss->parse($active);
        $params['now']        	= $GLOBALS['db']->Date();

        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('SOCIAL_ERROR_EMAIL_NOT_UPDATED'), _t('SOCIAL_NAME'));
        }

		// Let everyone know
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onUpdateSocialEmail', $id);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		
        if ($auto) {
            $GLOBALS['app']->Session->PushLastResponse(_t('SOCIAL_EMAIL_AUTOUPDATED',
                                                     date('H:i:s'),
                                                     (int)$id,
                                                     date('D, d')),
                                                  RESPONSE_NOTICE);
        } else {
            $GLOBALS['app']->Session->PushLastResponse(_t('SOCIAL_EMAIL_UPDATED'), RESPONSE_NOTICE);
        }
        return true;
    }


    /**
     * Deletes a post
     *
     * @access  public
     * @param   int     $id     The ID of the page to delete.
     * @return  bool    Success/failure
     */
    function DeleteEmail($id, $massive = false)
    {
		// Let everyone know
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onDeleteSocialEmail', $id);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		
        $sql = 'DELETE FROM [[emails]] WHERE [id] = {id}';
        $result = $GLOBALS['db']->query($sql, array('id' => $id));
        if (Jaws_Error::IsError($result)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('SOCIAL_ERROR_EMAIL_NOT_DELETED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('SOCIAL_ERROR_EMAIL_NOT_DELETED'), _t('SOCIAL_NAME'));
        }

		if ($massive === false) {
			$GLOBALS['app']->Session->PushLastResponse(_t('SOCIAL_EMAIL_DELETED'), RESPONSE_NOTICE);
        }
		return true;
    }
	
	/**
     * Imports Emails
     *
     * @access  public
     * @param   string  $file  file containing users to import
     * @return  bool	true on success/false on error
     */
    function InsertEmails($file, $type, $num, $user_attended = 'N')
    {		
		ignore_user_abort(true); 
        set_time_limit(0);
		echo " ";
		ob_flush();
		flush();
		$model = $GLOBALS['app']->LoadGadget('Social', 'Model');
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
							$this->InsertRSSEmails($rss->items[$num], $real_rss_url);
							if ($user_attended == 'Y') {
								echo '<form name="email_rss_form" id="email_rss_form" action="index.php?gadget=Social&action=UpdateRSSEmails" method="POST">'."\n";
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
								$this->InsertRSSEmails($item, $real_rss_url);
							
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
		} else if ($type == 'TabDelimited') {
			$output = '';
			//$result = array();
			if (trim($file) != '' && file_exists(JAWS_DATA.'files/'.$file) && strpos(strtolower($file), 'users/') === false) {
				$output .= '<br />File: '.$file;
				echo '<br />File: '.$file;
				// snoopy
				include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
				$snoopy = new Snoopy('Social');
				$fetch_url = $GLOBALS['app']->getDataURL('', true) . 'files/'.$xss->filter($file);
				
				if($snoopy->fetch($fetch_url)) {
					$inventoryContent = Jaws_Utils::split2D($snoopy->results);
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
						$GLOBALS['app']->Session->PushLastResponse("There was a problem parsing the Email List File: ".$fetch_url.". The file you are importing MUST contain the column 'Name'.", RESPONSE_ERROR);
						//return new Jaws_Error("There was a problem parsing the RSS feed for: ".$fetch_url.". Please make sure the RSS feed URL is entered correctly.", _t('USERS_NAME'));
						$output .= '<br />'."There was a problem parsing the Email List File: ".$fetch_url.". The file you are importing MUST contain the column 'Name'.";
						echo '<br />'."There was a problem parsing the Email List File: ".$fetch_url.". The file you are importing MUST contain the column 'Name'.";
						return false;
					}
					// E-mail	
					for ($i=0;$i<50;$i++) {
						if (trim(strtolower($inventoryContent[0][$i]), "\x22\x27 \t\n\r\0\x0B") == 'e-mail' || trim(strtolower($inventoryContent[0][$i]), "\x22\x27 \t\n\r\0\x0B") == 'email') {
							$email = $i;
							break;
						}
					}
					if (!isset($email)) {
						$GLOBALS['app']->Session->PushLastResponse("There was a problem parsing the Email List File: ".$fetch_url.". The file you are importing MUST contain the column 'E-mail'.", RESPONSE_ERROR);
						//return new Jaws_Error("There was a problem parsing the RSS feed for: ".$fetch_url.". Please make sure the RSS feed URL is entered correctly.", _t('USERS_NAME'));
						$output .= '<br />'."There was a problem parsing the Email List File: ".$fetch_url.". The file you are importing MUST contain the column 'E-mail'.";
						echo '<br />'."There was a problem parsing the Email List File: ".$fetch_url.". The file you are importing MUST contain the column 'E-mail'.";
						return false;
					}
					// Company	
					for ($i=0;$i<50;$i++) {
						if (trim(strtolower($inventoryContent[0][$i]), "\x22\x27 \t\n\r\0\x0B") == 'company') {
							$company = $i;
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
					
					// Active	
					for ($i=0;$i<50;$i++) {
						if (trim(strtolower($inventoryContent[0][$i]), "\x22\x27 \t\n\r\0\x0B") == 'active') {
							$active = $i;
							break;
						}
					}
										
					unset($inventoryContent[0]);
					array_unshift($inventoryContent, array_shift ($inventoryContent)); 					
					$this->_propTotal = count($inventoryContent);
					reset($inventoryContent);
					if ((isset($num) && !empty($num) || $num == 1) && $user_attended == 'Y') {
						if ($num <= $this->_propTotal) {
							sleep(1);
							echo " ";
							ob_flush();
							flush();
							$this->_propCount = ($num+1);
							$details = '<br />Num: '.$num;
							$details .= '<br />ADD User: '.trim($inventoryContent[$num][$name], "\x22\x27 \t\n\r\0\x0B");
							$details .= '<br />E-mail: '.trim($inventoryContent[$num][$email], "\x22\x27 \t\n\r\0\x0B");
							$details .= '<br />Company: '.trim($inventoryContent[$num][$company], "\x22\x27 \t\n\r\0\x0B");
							$details .= '<br />Address: '.trim($inventoryContent[$num][$address], "\x22\x27 \t\n\r\0\x0B");
							$details .= '<br />City: '.trim($inventoryContent[$num][$city], "\x22\x27 \t\n\r\0\x0B");
							$details .= '<br />Postal: '.trim($inventoryContent[$num][$postal], "\x22\x27 \t\n\r\0\x0B");
							$details .= '<br />State: '.trim($inventoryContent[$num][$state], "\x22\x27 \t\n\r\0\x0B");
							$details .= '<br />Phone: '.trim($inventoryContent[$num][$phone], "\x22\x27 \t\n\r\0\x0B");
							$details .= '<br />Website: '.trim($inventoryContent[$num][$website], "\x22\x27 \t\n\r\0\x0B");
							$details .= '<br />Active: '.trim($inventoryContent[$num][$active], "\x22\x27 \t\n\r\0\x0B");
							$this->InsertInventoryUsers(
								$xss->filter(trim($inventoryContent[$num][$name], "\x22\x27 \t\n\r\0\x0B")),
								$xss->filter(trim($inventoryContent[$num][$email], "\x22\x27 \t\n\r\0\x0B")),
								$xss->filter(trim($inventoryContent[$num][$company], "\x22\x27 \t\n\r\0\x0B")),
								$xss->filter(trim($inventoryContent[$num][$address], "\x22\x27 \t\n\r\0\x0B")),
								$xss->filter(trim($inventoryContent[$num][$city], "\x22\x27 \t\n\r\0\x0B")),
								$xss->filter(trim($inventoryContent[$num][$postal], "\x22\x27 \t\n\r\0\x0B")),
								$xss->filter(trim($inventoryContent[$num][$state], "\x22\x27 \t\n\r\0\x0B")),
								$xss->filter(trim($inventoryContent[$num][$phone], "\x22\x27 \t\n\r\0\x0B")),
								$xss->filter(trim($inventoryContent[$num][$website], "\x22\x27 \t\n\r\0\x0B")),
								$xss->filter(trim($inventoryContent[$num][$active], "\x22\x27 \t\n\r\0\x0B"))
							);
							$details .= '<form name="email_rss_form" id="email_rss_form" action="index.php?gadget=Social&action=UpdateRSSEmails" method="POST">'."\n";
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
								$this->InsertInventoryEmails(
									$xss->filter(trim($item[$name], "\x22\x27 \t\n\r\0\x0B")),
									$xss->filter(trim($item[$email], "\x22\x27 \t\n\r\0\x0B")),
									$xss->filter(trim($item[$company], "\x22\x27 \t\n\r\0\x0B")),
									$xss->filter(trim($item[$address], "\x22\x27 \t\n\r\0\x0B")),
									$xss->filter(trim($item[$city], "\x22\x27 \t\n\r\0\x0B")),
									$xss->filter(trim($item[$postal], "\x22\x27 \t\n\r\0\x0B")),
									$xss->filter(trim($item[$state], "\x22\x27 \t\n\r\0\x0B")),
									$xss->filter(trim($item[$phone], "\x22\x27 \t\n\r\0\x0B")),
									$xss->filter(trim($item[$website], "\x22\x27 \t\n\r\0\x0B")),
									$xss->filter(trim($item[$active], "\x22\x27 \t\n\r\0\x0B"))
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
					$GLOBALS['app']->Session->PushLastResponse("There was a problem parsing the Email List File: ".$fetch_url.". The file you are importing MUST be Tab-Delimited.", RESPONSE_ERROR);
					//return new Jaws_Error("There was a problem parsing the RSS feed for: ".$fetch_url.". Please make sure the RSS feed URL is entered correctly.", _t('USERS_NAME'));
					$output .= '<br />'."There was a problem parsing the Email List File: ".$fetch_url.". The file you are importing MUST be Tab-Delimited.";
					echo '<br />'."There was a problem parsing the Email List File: ".$fetch_url.". The file you are importing MUST be Tab-Delimited.";
					return false;
				}
				//echo $rss_html.'</table>';
			} else {
				//return new Jaws_Error("An RSS feed URL was not given.", _t('USERS_NAME'));
				$output .= '<br />'."An Email List File was not given.";
				echo '<br />'."An Email List File was not given.";
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
			$output .= "<h1>Email List File Type Not Supported</h1>";
			echo "<h1>Email List File Type Not Supported</h1>";
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
		if (file_exists(JAWS_DATA.'files/'.$file)) {
			if (!Jaws_Utils::Delete(JAWS_DATA.'files/'.$file, false)) {
				$output .= "<br />Couldn't Delete File During Cleanup";
			}
		}
		
		$output .= "<h1>Emails Imported Successfully</h1>";
		echo "<h1>Emails Imported Successfully</h1>";
		
        if (Jaws_Utils::is_writable(JAWS_DATA . 'logs/')) {
            $result = file_put_contents(JAWS_DATA . 'logs/emailimport.log', $output);
            if ($result === false) {
                return new Jaws_Error("Couldn't create emailimport.log file", _t('SOCIAL_NAME'));
                //return false;
			}
		}

		return true;
    }
	
	/**
     * Insert Inventory Emails
     *
     * @access  public
     * @param   integer  $item  array of info
     * @return  nothing
     */
    function InsertInventoryEmails(
		$name, $email, $company = '', $address = '', $city = '', $postal = '', 
		$state = '', $phone = '', $website = '', $active = 'Y')
    {
		ignore_user_abort(true); 
        set_time_limit(0);
		echo " ";
		ob_flush();
		flush();
		sleep(1);
		$model = $GLOBALS['app']->LoadGadget('Social', 'Model');
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');

		// Continue only if we have a name
		if (isset($name) && !empty($name)) {
			$rss_name = $name;
			$rss_email = $email;
			$rss_company = (isset($company) && !empty($company) ? $company : '');
			$rss_address = (isset($address) && !empty($address) ? $address : '');
			$rss_city = (isset($city) && !empty($city) ? $city : '');
			$rss_postal = (isset($postal) && !empty($postal) ? $postal : '');
			$rss_state = (isset($state) && !empty($state) ? $state : '');
			$rss_phone = (isset($phone) && !empty($phone) ? $phone : '');
			$rss_website = (isset($website) && !empty($website) ? $website : '');
			$rss_active = (isset($active) && !empty($active) ? $active : 'Y');
															
			$prop_checksum = md5($rss_name.', '.$rss_email.', '.$rss_address);
			$this->_newChecksums[] = $prop_checksum;
			if (!empty($rss_name) && !empty($prop_checksum)) {
				$params = array();
				$params['checksum'] = $prop_checksum;

				$sql = 'SELECT [id] FROM [[emails]] WHERE ([recovery_key] = {checksum})';
				$found = $GLOBALS['db']->queryOne($sql, $params);
										
				if (is_numeric($found)) {
					$page = $model->GetEmailById((int)$found);
					if (Jaws_Error::isError($page)) {
						$GLOBALS['app']->Session->PushLastResponse(_t('SOCIAL_EMAIL_NOT_FOUND'), RESPONSE_ERROR);
						//return new Jaws_Error(_t('SOCIAL_EMAIL_NOT_FOUND'), _t('USERS_NAME'));
						echo '<br />'._t('SOCIAL_EMAIL_NOT_FOUND');
					} else if (isset($page['id']) && !empty($page['id'])) {
						$params               	= array();
						$params['id']         	= $found;
						$params['name']       	= $rss_name;
						$params['email']      	= $rss_email;
						$params['website']    	= $rss_website;
						$params['company']    	= $rss_company;
						$params['address']    	= $rss_address;
						$params['city']    		= $rss_city;
						$params['region']    	= $rss_state;
						$params['postal']    	= $rss_postal;
						$params['phone']    	= $rss_phone;
						$params['Active']    	= $rss_active;
						$params['now'] 			= $GLOBALS['db']->Date();
						
						$sql = '
							UPDATE [[emails]] SET
								[name] = {name},
								[email] = {email},
								[updatetime] = {now},
								[website] = {website},
								[company] = {company},
								[address] = {address},
								[city] = {city},
								[region] = {region},
								[postal] = {postal},
								[phone] = {phone},
								[active] = {Active}
							WHERE [id] = {id}
						';

						$result = $GLOBALS['db']->query($sql, $params);
						if (Jaws_Error::IsError($result)) {
							echo '<br />'._t('SOCIAL_EMAIL_NOT_UPDATED');
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
					$result = $this->AddEmail(
						$rss_email, 
						$rss_name,
						$rss_address, 
						$rss_city, 
						"United States", 
						$rss_state, 
						$rss_postal,
						$rss_phone,
						$rss_website,
						$rss_company,
						$rss_active
					);

					if ($result === false || Jaws_Error::IsError($result)) {
						$GLOBALS['app']->Session->PushLastResponse(_t('SOCIAL_EMAIL_NOT_CREATED'), RESPONSE_ERROR);
						echo '<br />'._t('SOCIAL_EMAIL_NOT_CREATED');
						//$output_html .= "<br />ERROR: ".$result->getMessage();
								
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
				unset($found);
			
				$params = array();
				$params['checksum'] = $prop_checksum;
				$sql = 'SELECT [id] FROM [[emails]] WHERE ([recovery_key] = {checksum})';
				$found = $GLOBALS['db']->queryOne($sql, $params);
				if (Jaws_Error::IsError($found) || !is_numeric($found)) {
					$GLOBALS['app']->Session->PushLastResponse('Email Not Added', RESPONSE_ERROR);
					if (($this->_propCount-1) >= 1) {
						echo '<style>#prod_'.($this->_propCount-1).' {display: none;}</style>';
						ob_flush();
						flush();
					}
					echo '<div><br />Email <b>'.$this->_propCount.' of '.$this->_propTotal.'</b> Not Added</div>';
					ob_flush();
					flush();
				}
			} else {
				$GLOBALS['app']->Session->PushLastResponse(_t('SOCIAL_EMAIL_NOT_CREATED'), RESPONSE_ERROR);
				if (($this->_propCount-1) >= 1) {
					echo '<style>#prod_'.($this->_propCount-1).' {display: none;}</style>';
					ob_flush();
					flush();
				}
				echo '<div><br />Email <b>'.$this->_propCount.' of '.$this->_propTotal.'</b> Not Added</div>';
				ob_flush();
				flush();
			}
			unset($result);
			unset($prop_checksum);
			
			//ob_end_flush();
			//break;
		} else {
			$GLOBALS['app']->Session->PushLastResponse('Invalid Email Name', RESPONSE_ERROR);
			if (($this->_propCount-1) >= 1) {
				echo '<style>#prod_'.($this->_propCount-1).' {display: none;}</style>';
				ob_flush();
				flush();
			}
			echo '<div><br />Email <b>'.$this->_propCount.' of '.$this->_propTotal.'</b> could not be added</div>';
			ob_flush();
			flush();
		}
		
		unset($rss_name);
		unset($rss_email);
		unset($rss_company);
		unset($rss_address);
		unset($rss_city);
		unset($rss_postal);
		unset($rss_state);
		unset($rss_phone);
		unset($rss_website);
		unset($rss_active);
		unset($model);
	
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
     * Update access tokens
     *
     * @access  public
     * @param   integer   $social_id 	social_users ID
     * @param   string   $social_token 	Access Token
     * @return  array   Response (notice or error)
     */
    function UpdateAccessToken($social_id, $social_token, $expires = null, $OwnerID = 0, $active = 'Y', $checksum = '')
    {
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$model = $GLOBALS['app']->LoadGadget('Social', 'Model');
		if (BASE_SCRIPT == 'index.php') {
			$OwnerID = $GLOBALS['app']->Session->GetAttribute('user_id');
		}
		$social_exists = $model->GetAccessTokensOfUserID($OwnerID, $social_id);
		if (Jaws_Error::IsError($social_exists)) {
			$GLOBALS['app']->Session->PushLastResponse($social_exists->GetMessage(), RESPONSE_ERROR);
			return $social_exists;
		}
		$params = array();
		$params['social_id'] 		= $social_id;
		$params['social_token'] 	= $social_token;
		$params['OwnerID'] 	= $OwnerID;
		$params['expires'] = $GLOBALS['db']->Date($expires);
		$params['now'] 		= $GLOBALS['db']->Date();
		$insert = false;
		if (is_array($social_exists) && !count($social_exists) <= 0) {
			if ($active == 'Y') {
				$sql = "
					UPDATE [[social_tokens]] SET
						[social_token] = {social_token},
				";
				if (!is_null($expires)) {
					$sql .= "[expires] = {expires},";
				}
				$sql .= "
						[updated] = {now}
				";
			} else {
				$sql = "
					DELETE FROM [[social_tokens]]
				";
			}
			$sql .= "
				WHERE [social_id] = {social_id} AND [ownerid] = {OwnerID}
			";
		} else if ($active == 'Y') {
			$insert = true;
			$sql = "
				INSERT INTO [[social_tokens]]
					([social_id], [social_token], [expires], 
					[ownerid], [created], [updated])
				VALUES
					({social_id}, {social_token}, {expires}, 
					{OwnerID}, {now}, {now})
			";
		}
				
		$result = $GLOBALS['db']->query($sql, $params);
		if (Jaws_Error::IsError($result)) {
			$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
			return $result;
		}
				
		if ($insert === true && empty($checksum)) {
			$newid = $GLOBALS['db']->lastInsertID('social_tokens', 'id');
			
			// Update checksum
			$config_key = $GLOBALS['app']->Registry->Get('/config/key');		
			$params               	= array();
			$params['id'] 			= $newid;
			$params['checksum'] 	= $newid.':'.$config_key;
			
			$sql = '
				UPDATE [[social_tokens]] SET
					[checksum] = {checksum}
				WHERE [id] = {id}';

			$result = $GLOBALS['db']->query($sql, $params);
			if (Jaws_Error::IsError($result)) {
				$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
				return $result;
			}
		}
								
        $GLOBALS['app']->Session->PushLastResponse(_t('SOCIAL_ACCESS_TOKEN_UPDATED'), RESPONSE_NOTICE);
        return true;
    }

}
