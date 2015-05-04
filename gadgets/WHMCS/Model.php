<?php
/**
 * WHMCS Gadget
 *
 * @category   GadgetModel
 * @package    WHMCS
 * @author     Alan Valkoun	 <valkoun@gmail.com>
 * @copyright  2012 Alan Valkoun
 */

class WHMCSModel extends Jaws_Model
{
    var $_Name = 'WHMCS';
	
    /**
     * Gets a single page by ID.
     *
     * @access  public
     * @param   int     $id     The ID of the page to get.
     * @return  array   An array containing the page information, or false if no page could be loaded.
     */
    function GetClient($id)
    {
		$sql = '
            SELECT [id], [user_id], [whmcs_id], [created], [updated], [checksum]
            FROM [[users_whmcsclients]]
			WHERE [id] = {id}';
		
        $params = array();
		$params['id'] = (int)$id;

        $types = array(
			'integer', 'integer', 'integer', 'timestamp', 'timestamp', 'text'
		);

        // TODO: Merge WHMCS API client data with result set

        $row = $GLOBALS['db']->queryRow($sql, $params, $types);
		
        if (isset($row['id'])) {
            return $row;
        }

        return new Jaws_Error(_t('WHMCS_ERROR_WHMCS_USER_NOT_FOUND'), _t('WHMCS_NAME'));
    }
    
    /**
     * Gets a client by checksum.
     *
     * @access  public
     * @param   int     $checksum     The checksum of the page to get.
     * @return  array   An array containing the page information, or false if no page could be loaded.
     */
    function GetClientByChecksum($checksum)
    {
        $params 			= array();
		$params['checksum']	= $checksum;
		$sql = '
            SELECT [id], [user_id], [whmcs_id], [created], [updated], [checksum]
            FROM [[users_whmcsclients]]
            WHERE [checksum] = {checksum}';

        $types = array(
			'integer', 'integer', 'integer', 'timestamp', 'timestamp', 'text'
		);

        // TODO: Merge WHMCS API client data with result set

        $row = $GLOBALS['db']->queryRow($sql, $params, $types);
		
        if (isset($row['id'])) {
            return $row;
        }

        return new Jaws_Error(_t('WHMCS_ERROR_WHMCS_USER_NOT_FOUND'), _t('WHMCS_NAME'));
    }
    
    /**
     * Gets a client by ID
     *
     * @access  public
     * @param   int     $id  The user ID
     * @return  mixed   Returns an array with the product and false on error
     */
    function GetClientByUserID($cid)
    {
		$params       = array();
        $params['cid'] = $cid;
		
		$sql = '
            SELECT [id], [user_id], [whmcs_id], [created], [updated], [checksum]
            FROM [[users_whmcsclients]]
            WHERE ([user_id] = {cid})';
		
        // TODO: Merge WHMCS API client data with result set
        
		$types = array(
			'integer', 'integer', 'integer', 'timestamp', 'timestamp', 'text'
		);

        $row = $GLOBALS['db']->queryRow($sql, $params, $types);
		
		if (isset($row['id'])) {
            return $row;
        }

        return new Jaws_Error(_t('WHMCS_ERROR_WHMCS_USER_NOT_FOUND'), _t('WHMCS_NAME'));
    }

    /**
     * Gets a client by WHMCS ID
     *
     * @access  public
     * @param   int     $id  The WHMCS ID
     * @return  mixed   Returns an array with the product and false on error
     */
    function GetClientByWHMCSID($wid)
    {
		$params       = array();
        $params['wid'] = $wid;
		
		$sql = '
            SELECT [id], [user_id], [whmcs_id], [created], [updated], [checksum]
            FROM [[users_whmcsclients]]
            WHERE ([whmcs_id] = {wid})';
		
        // TODO: Merge WHMCS API client data with result set
        
		$types = array(
			'integer', 'integer', 'integer', 'timestamp', 'timestamp', 'text'
		);

        $row = $GLOBALS['db']->queryRow($sql, $params, $types);
		
		if (isset($row['id'])) {
            return $row;
        }

        return new Jaws_Error(_t('WHMCS_ERROR_WHMCS_USER_NOT_FOUND'), _t('WHMCS_NAME'));
    }

    /**
     * Gets an index of all the clients.
     *
     * @access  public
     * @param   int     $limit      The number of pages to return. Set to Null to return all pages.
     * @param   int     $sortType   One of the PAGES_SORT_* constants to set the sort field.
     * @param   int     $sortDir    Either PAGES_ASC or PAGES_DESC to set the sort direction.
     *
     * @return  array   An array containing the page information.
     */
    function GetClients($limit = null, $sortColumn = 'created', $sortDir = 'DESC', $offSet = false)
    {
        $fields     = array('user_id', 'whmcs_id', 'created', 'updated');
        $sortColumn = strtolower($sortColumn);
        if (!in_array($sortColumn, $fields)) {
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_WARNING, _t('WHMCS_ERROR_UNKNOWN_COLUMN'));
            }
            $sortColumn = 'created';
        }

        $sortDir = strtoupper($sortDir);
        if ($sortDir != 'ASC') {
            $sortDir = 'DESC';
        }

        $sql = "
            SELECT [id], [user_id], [whmcs_id], [created], [updated], [checksum]
            FROM [[users_whmcsclients]]
			ORDER BY [$sortColumn] $sortDir
		";

        if (is_numeric($offSet)) {
            $limit = is_null($limit) ? 10 : $limit;
            if (!is_null($limit)) {
                $result = $GLOBALS['db']->setLimit(10, $offSet);
                if (Jaws_Error::IsError($result)) {
                    return new Jaws_Error(_t('WHMCS_ERROR_WHMCS_USERS_NOT_RETRIEVED'), _t('WHMCS_NAME'));
                }
            }
        } else {
            if (!is_null($limit)) {
                $result = $GLOBALS['db']->setLimit($limit);
                if (Jaws_Error::IsError($result)) {
                    return new Jaws_Error(_t('WHMCS_ERROR_WHMCS_USERS_NOT_RETRIEVED'), _t('WHMCS_NAME'));
                }
            }
        }

        $types = array(
			'integer', 'integer', 'integer', 'timestamp', 'timestamp', 'text'
		);

        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('WHMCS_ERROR_WHMCS_USERS_NOT_RETRIEVED'), _t('WHMCS_NAME'));
        }

        return $result;
    }
    
	/**
     * WHMCS API calls
     *
     * @access  public
     * @return  string  XHTML
     */
    function API(
		$method, $fuseparams = array(), $return = 'array', $redirect_to = '', 
		$require_user_fields = array(), $uid = null
	) {
        $adminModel = $GLOBALS['app']->LoadGadget('WHMCS', 'AdminModel');
		$site_name = $GLOBALS['app']->Registry->Get('/config/site_name');
		require_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'User.php';
		$jUser = new Jaws_User;
		$uid = (!is_null($uid) ? $uid : $GLOBALS['app']->Session->GetAttribute('user_id'));
		$userInfo = $jUser->GetUserInfoById((int)$uid, true, true, true, true);
		$full_url = $GLOBALS['app']->GetFullURL();
		$error_redirect = $GLOBALS['app']->GetSiteURL() . '/index.php?gadget=WHMCS&action=API';
		$api_url = $GLOBALS['app']->Registry->Get('/gadgets/WHMCS/whmcs_url');
		$api = $GLOBALS['app']->Registry->Get('/gadgets/WHMCS/whmcs_api');
		$auth = $GLOBALS['app']->Registry->Get('/gadgets/WHMCS/whmcs_auth');
		if (is_null($api_url) || empty($api_url) || is_null($api) || empty($api) || is_null($auth) || empty($auth)) {
			return new Jaws_Error(_t('WHMCS_ERROR_WHMCS_API_CREDENTIALS_NOT_VALID'), _t('WHMCS_NAME'));
		}
		$api_param_keys = array();
		$api_param_values = array();
		if (
			isset($fuseparams['api_param_keys']) && !empty($fuseparams['api_param_keys']) &&
			isset($fuseparams['api_param_values']) && !empty($fuseparams['api_param_values'])
		) {
			$api_param_keys = explode('__SYNTACTS__',$fuseparams['api_param_keys']);
			$api_param_values = explode('__SYNTACTS__',$fuseparams['api_param_values']);
		}
		$error_redirect .= '&fuseaction='.$method.'&fuseparams_keys='.implode(',',$api_param_keys).'&fuseparams_values='.implode(',',$api_param_values);
		$error_redirect .= '&redirect_to='.(!empty($redirect_to) ? urlencode($redirect_to).'%26username%3D'.$userInfo['username'] : urlencode($full_url));
		$error_redirect = urlencode($error_redirect);	
		if (!$GLOBALS['app']->Session->Logged()) {
			return new Jaws_Error("You must log-in to view this page. If you don't have an account, you can <a href=\"index.php?gadget=Users&action=Registration&redirect_to=".$error_redirect."\">Create one</a>", _t('USERS_NAME'));
		} else {
			require_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
			require_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'XMLParser.php';
			
			// snoopy
			$snoopy = new Snoopy(false);
			$snoopy->curl_path = "/usr/bin/curl";
			$snoopy->agent = "Jaws";
			$url = $api_url; # URL to WHMCS API file
			$username = $api; # Admin username goes here
			$password = $auth; # Admin password goes here
			$postfields = array();
			$postfields["username"] = $username;
			$postfields["password"] = $password;
			$postfields["action"] = $method; #action performed by the API:Functions
			$postfields["responsetype"] = 'xml';
			if (!count($api_param_keys) <= 0) {
				for ($i=0;$i<count($api_param_keys);$i++) {
					$postfields[$api_param_keys[$i]] = $api_param_values[$i];
				}
			}
			$error = '';
			 
			if (Jaws_Error::IsError($userInfo)) {
				return new Jaws_Error($userInfo->GetMessage(), _t('USERS_NAME'));
			} else if (!isset($userInfo['id'])) {
				return new Jaws_Error("Could not get user details of user: ".var_export($uid, true).".", _t('USERS_NAME'));
			}
			switch (strtolower($method)) {
				case 'addclient':
					/*
					// WHMCS Add Client API Call
					// See: http://wiki.whmcs.com/API:Add_Client
					firstname
					lastname
					companyname - optional
					email
					address1
					address2 - optional
					city
					state
					postcode
					country - two letter ISO country code
					phonenumber
					password2 - password for the new user account
					currency - the ID of the currency to set the user to
					groupid - used to assign the client to a client group
					notes
					cctype - Visa, Mastercard, etc...
					cardnum
					expdate - in the format MMYY
					startdate
					issuenumber
					customfields - a base64 encoded serialized array of custom field values
					noemail - pass as true to surpress the client signup welcome email sending
					*/
					if (!in_array('address', $require_user_fields)) {
						$require_user_fields[] = 'address';
					}
					if (!in_array('city', $require_user_fields)) {
						$require_user_fields[] = 'city';
					}
					if (!in_array('region', $require_user_fields)) {
						$require_user_fields[] = 'region';
					}
					if (!in_array('postal', $require_user_fields)) {
						$require_user_fields[] = 'postal';
					}
					if (!in_array('phone', $require_user_fields)) {
						$require_user_fields[] = 'phone';
					}
					if (isset($userInfo['tollfree']) && !empty($userInfo['tollfree'])) {
						$userInfo['phone'] = $userInfo['tollfree'];
					} else if (isset($userInfo['office']) && !empty($userInfo['office'])) {
						$userInfo['phone'] = $userInfo['office'];
					}
					if (
						isset($userInfo['email']) && !empty($userInfo['email']) &&
						isset($userInfo['address']) && !empty($userInfo['address']) && 
						isset($userInfo['city']) && !empty($userInfo['city']) &&
						isset($userInfo['region']) && !empty($userInfo['region']) &&
						isset($userInfo['phone']) && !empty($userInfo['phone']) &&
						isset($userInfo['postal']) && !empty($userInfo['postal'])
					) {
						if (is_array($require_user_fields) && !count($require_user_fields) <= 0) {
							foreach ($require_user_fields as $require_field) {
								if (isset($userInfo[$require_field]) && empty($userInfo[$require_field])) {
									return new Jaws_Error("Incomplete fields: to continue, sign-up requires ".implode(", ",$require_user_fields)," to be filled out.", _t('USERS_NAME'));
								}
							}
						}
						$postfields["email"] = $userInfo['email'];
						$postfields["address1"] = $userInfo['address'];
						$postfields["city"] = $userInfo['city'];
						$postfields["state"] = $userInfo['region'];
						$postfields["postcode"] = $userInfo['postal'];
						$postfields["phonenumber"] = $userInfo['phone'];
					} else {
						if (is_array($require_user_fields) && !count($require_user_fields) <= 0) {
							foreach ($require_user_fields as $require_field) {
								if (isset($userInfo[$require_field]) && empty($userInfo[$require_field])) {
									return new Jaws_Error("Incomplete fields: to continue, sign-up requires ".implode(", ",$require_user_fields)." to be filled out.", _t('USERS_NAME'));
								}
							}
						} else {
							return new Jaws_Error("Incomplete fields: to continue, sign-up requires address, city, region, postal, phone to be filled out.", _t('USERS_NAME'));
						}
					}
					// Existing WHMCS client?
					$clientid = '';
					$clientInfo = $this->GetClientByUserID($userInfo['id']);
					if (!Jaws_Error::IsError($clientInfo) && isset($clientInfo['id']) && !empty($clientInfo['id'])) {
						$params = array(
							'api_param_keys' => 'clientid', 
							'api_param_values' => (string)$clientInfo['whmcs_id'], 
							'existing_password' => $userInfo['passwd']
						);
						$getclientsdetails = $this->API('getclientsdetails', $params);
						if(!Jaws_Error::IsError($getclientsdetails) && isset($getclientsdetails['userid'])) {
							// Update WHMCS client?
							$params = array(
								'api_param_keys' => 'clientid__SYNTACTS__password2', 
								'api_param_values' => $getclientsdetails['userid'].'__SYNTACTS__'.$userInfo['passwd']
							);
							$updateclient = $this->API('updateclient', $params);
							if(!Jaws_Error::IsError($updateclient) && isset($updateclient['clientid'])) {
								$clientid = $updateclient['clientid'];
							}
						}
					}
					if (empty($clientid)) {
						if (isset($userInfo['fname']) && !empty($userInfo['fname']) && isset($userInfo['lname']) && !empty($userInfo['lname'])) {
							$firstname = $userInfo['fname'];
							$lastname = $userInfo['lname'];
						} else if (isset($userInfo['nickname']) && !empty($userInfo['nickname'])) {
							$nameparts = explode(" ", $userInfo['nickname']);
							if (isset($nameparts[0]) && !empty($nameparts[0])) {
								$firstname = strtolower(str_replace('.','',$nameparts[0]));
								$lastname = '';
								if (isset($nameparts[1]) && !empty($nameparts[1])) {
									$startpart = 1;
									if ($firstname == 'mr' || $firstname == 'mrs' || $firstname == 'ms' || $firstname == 'dr') {
										$firstname = $nameparts[1];
										$startpart = 2;
									}
									for ($s=$startpart; $s<count($nameparts); $s++) {
										$lastname .= (isset($nameparts[$s]) ? str_replace('.','',$nameparts[$s]) : '');
									}
								}
							}
						}
						$firstname = (empty($firstname) ? 'User' : ucfirst($firstname));
						$lastname = (empty($lastname) ? "of ".(empty($site_name) ? 'eVision' : $site_name) : $lastname);
						$postfields["firstname"] = $firstname;
						$postfields["lastname"] = $lastname;
						$postfields["country"] = 'US';
						if (isset($userInfo['company']) && !empty($userInfo['company'])) {
							$postfields["company"] = $userInfo['company'];
						}
						if (isset($userInfo['address2']) && !empty($userInfo['address2'])) {
							$postfields["address2"] = $userInfo['address2'];
						}
						$postfields["password2"] = $userInfo['passwd'];
						$postfields["currency"] = '1';
						$postfields["noemail"] = 'true';
						if($snoopy->submit($url, $postfields)) {
							// XML Parser
							$xml_content = $snoopy->results;
							/*
							echo '<pre>';
							var_dump($xml_content);
							echo '</pre>';
							*/
							$xml_parser = new XMLParser;
							$xml_result = $xml_parser->parse($xml_content, array("WHMCSAPI"));
							for ($i=0;$i<$xml_result[1]; $i++) {
								if ($xml_result[0][0]['RESULT'] == 'success' && isset($xml_result[0][$i]['CLIENTID'])) {
									$clientid = $xml_result[0][$i]['CLIENTID'];
									$result = $adminModel->AddClient($userInfo['id'], (int)$clientid);
									if (Jaws_Error::IsError($result)) {
										$error2 = $result;
										return $result;
									}
									break;
								} else if ($xml_result[0][0]['RESULT'] == 'error') {
									$error = $xml_result[0][$i]['MESSAGE'];
									break;
								} else {
									$error = var_export($xml_result, true);
									break;
								}
							}
						} else {
							$error2 = new Jaws_Error("There was a problem using WHMCS API: ".var_export($snoopy, true)." from ".$GLOBALS['app']->GetSiteURL().". user: ".var_export($userInfo, true), _t('USERS_NAME'));
							return new Jaws_Error("There was a problem using WHMCS API: ".var_export($snoopy, true)." from ".$GLOBALS['app']->GetSiteURL().". user: ".var_export($userInfo, true), _t('USERS_NAME'));
						}
					}

					// validate login, and set WHMCS session
					if (!empty($clientid)) {
						if ($return == 'array') {
							return array('clientid' => $clientid);
						} else if (!empty($redirect_to) && $return == 'html') {
							// AutoAuth
							$timestamp = time(); # Get current timestamp
							$hash = sha1($userInfo['email'].$timestamp.$auth); # Generate Hash
							$login_url = 'https://manage.ucclouds.com/dologin.php?email='.$userInfo['email'].'&timestamp='.$timestamp.'&hash='.$hash;
							$redirect_url = urldecode($redirect_to).'&username='.$userInfo['username'];
							$output_html = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
							$output_html .= "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\">\n";
							$output_html .= " <head>\n";
							$output_html .= "  <title></title>\n";
							$output_html .= " </head>\n";
							$output_html .= " <body>\n";
							$output_html .= "   <iframe style=\"visibility: hidden;\" id=\"whmcsiframe\" name=\"iframe\" src=\"".$login_url."\" onload=\"location.href='".$redirect_url."';\"></iframe>\n";
							$output_html .= " </body>\n";
							$output_html .= "</html>\n";
							return $output_html;
						} else {
							return 'Action performed successfully.'.var_export($xml_result, true);
						}
					} else {
						if (!empty($error)) {
							$error2 = new Jaws_Error("Could not add client to WHMCS from ".$GLOBALS['app']->GetSiteURL()." (".$error."): ".var_export($xml_result, true).". user: ".var_export($userInfo, true), _t('USERS_NAME'));
						}
						return new Jaws_Error("Could not complete account sign-up, please try again later.", _t('USERS_NAME'));
					}
					break;
				case 'getclientsdetails':
					// Existing WHMCS client?
					$client_password = $fuseparams['existing_password'];
					if($snoopy->submit($url, $postfields)) {
						// XML Parser
						$xml_content = $snoopy->results;
						$xml_parser = new XMLParser;
						$xml_result = $xml_parser->parse($xml_content, array("WHMCSAPI"));
						for ($i=0;$i<$xml_result[1]; $i++) {
							if ($xml_result[0][0]['RESULT'] == 'success' && isset($xml_result[0][$i]['USERID'])) {
								/*
								$existing_password = explode(':',$xml_result[0][$i]['PASSWORD']);
								if ($existing_password[0] == md5($existing_password[1].$client_password)) {
								*/
								  $result = array();
								  foreach ($xml_result[0][$i] as $res => $val) {
									$result[strtolower($res)] = $val;
								  }
								  return $result;
								//}
								break;
							} else {
								$error = new Jaws_Error("Could not get WHMCS client details for :".var_export($postfields, true).". user: ".var_export($userInfo, true), _t('USERS_NAME'));
								return new Jaws_Error("Could not get WHMCS client details for :".var_export($postfields, true).".", _t('USERS_NAME'));
							}
						}
					} else {
						$error = new Jaws_Error("There was a problem using WHMCS API: ".var_export($snoopy, true)." from ".$GLOBALS['app']->GetSiteURL().". user: ".var_export($userInfo, true), _t('USERS_NAME'));
						return new Jaws_Error("There was a problem using WHMCS API: ".var_export($snoopy, true)." from ".$GLOBALS['app']->GetSiteURL().". user: ".var_export($userInfo, true), _t('USERS_NAME'));
					}
					break;
				case 'updateclient':
					// Update WHMCS client?
					if($snoopy->submit($url, $postfields)) {
						// XML Parser
						$xml_content = $snoopy->results;
						$xml_parser = new XMLParser;
						$xml_result = $xml_parser->parse($xml_content, array("WHMCSAPI"));
						/*
						echo '<pre>';
						var_dump($xml_result3);
						echo '</pre>';
						*/
						for ($i=0;$i<$xml_result[1]; $i++) {
							if ($xml_result[0][0]['RESULT'] == 'success' && isset($xml_result[0][$i]['CLIENTID'])) {
								$clientid = $xml_result[0][$i]['CLIENTID'];
								return array('clientid' => $clientid);
							} else {
								$error = new Jaws_Error("Could not update WHMCS client: ".$postfields['email'].": ".var_export($xml_result, true).". user: ".var_export($userInfo, true), _t('USERS_NAME'));
								return new Jaws_Error("Could not update WHMCS client details for :".$postfields['email'].".", _t('USERS_NAME'));
							}
						}
					} else {
						$error = new Jaws_Error("There was a problem using WHMCS API: ".var_export($snoopy, true)." from ".$GLOBALS['app']->GetSiteURL().". user: ".var_export($userInfo, true), _t('USERS_NAME'));
						return new Jaws_Error("There was a problem using WHMCS API: ".var_export($snoopy, true)." from ".$GLOBALS['app']->GetSiteURL().". user: ".var_export($userInfo, true), _t('USERS_NAME'));
					}
					break;
				case 'addorder':
					/*
					// WHMCS Add Order API Call
					// See: http://wiki.whmcs.com/API:Add_Order
					clientid - client id for order
					pid - product id
					domain - domain name
					billingcycle - onetime, monthly, quarterly, semiannually, etc..
					addons - comma seperated list of addon ids
					customfields - a base64 encoded serialized array of custom field values
					configoptions - a base64 encoded serialized array of configurable product options
					domaintype - set for domain registration - register or transfer
					regperiod = 1,2,3,etc...
					dnsmanagement - true to enable
					emailforwarding - true to enable
					idprotection - true to enable
					eppcode - if transfer
					nameserver1 - first nameserver (req for domain reg only)
					nameserver2 - second nameserver
					nameserver3 - third nameserver
					nameserver4 - fourth nameserver
					paymentmethod - paypal, authorize, etc...
					promocode - pass coupon code to apply to the order (optional)
					affid - affiliate ID if you want to assign the order to an affiliate (optional)
					noinvoice - set true to not generate an invoice for this order
					noemail - set true to surpress the order confirmation email
					clientip - can be used to pass the customers IP (optional)

					// Example usage
					$postfields["action"] = "addorder";
					$postfields["clientid"] = "1";
					$postfields["pid"] = "1";
					$postfields["domain"] = "whmcs.com";
					$postfields["billingcycle"] = "monthly";
					$postfields["addons"] = "1,3,9";
					$postfields["customfields"] = base64_encode(serialize(array("1"=>"Google")));
					$postfields["domaintype"] = "register";
					$postfields["regperiod"] = "1";
					$postfields["paymentmethod"] = "mailin";
					*/
					if($snoopy->submit($url, $postfields)) {
						// XML Parser
						$xml_content = $snoopy->results;
						
						/*
						echo '<pre>';
						var_dump($xml_content);
						echo '</pre>';
						*/

						$xml_parser = new XMLParser;
						$xml_result = $xml_parser->parse($xml_content, array("WHMCSAPI"));
						for ($i=0;$i<$xml_result[1]; $i++) {
							if ($xml_result[0][0]['RESULT'] == 'success' && isset($xml_result[0][$i]['ORDERID'])) {
								$orderid = $xml_result[0][$i]['ORDERID'];
								$invoiceid = (isset($xml_result[0][$i]['INVOICEID']) ? $xml_result[0][$i]['INVOICEID'] : '');
								$productids = (isset($xml_result[0][$i]['PRODUCTIDS']) ? $xml_result[0][$i]['PRODUCTIDS'] : '');
								$addonids = (isset($xml_result[0][$i]['ADDONIDS']) ? $xml_result[0][$i]['ADDONIDS'] : '');
								$domainids = (isset($xml_result[0][$i]['DOMAINIDS']) ? $xml_result[0][$i]['DOMAINIDS'] : '');
								break;
							} else if ($xml_result[0][0]['RESULT'] == 'error') {
								$error = $xml_result[0][$i]['MESSAGE'];
								break;
							} else {
								$error = var_export($xml_content, true);
								break;
							}
						}
					} else {
						$error = new Jaws_Error("There was a problem using WHMCS API: ".var_export($snoopy, true)." from ".$GLOBALS['app']->GetSiteURL().". user: ".var_export($userInfo, true), _t('USERS_NAME'));
						return new Jaws_Error("There was a problem using WHMCS API: ".var_export($snoopy, true)." from ".$GLOBALS['app']->GetSiteURL().". user: ".var_export($userInfo, true), _t('USERS_NAME'));
					}

					// validate login, and set WHMCS session
					if (!empty($orderid)) {
						if ($return == 'array') {
							return array(
								'orderid' => $orderid, 
								'invoiceid' => $invoiceid, 
								'productids' => $productids, 
								'addonids' => $addonids, 
								'domainids' => $domainids
							);
						} else {
							return 'Action performed successfully.'.var_export($xml_result, true);
						}
					} else {
						if (!empty($error)) {
							$error2 = new Jaws_Error("Could not add order to WHMCS from ".$GLOBALS['app']->GetSiteURL()." (".$error."): ".var_export($xml_result, true).". user: ".var_export($userInfo, true), _t('USERS_NAME'));
						}
						return new Jaws_Error("Could not complete account sign-up, please try again later.", _t('USERS_NAME'));
					}
					break;
				case 'getpaymentmethods':
					if($snoopy->submit($url, $postfields)) {
						// XML Parser
						$xml_content = $snoopy->results;
						echo '<pre>';
						var_dump($xml_content);
						echo '</pre>';
						exit;
						$xml_parser = new XMLParser;
						$xml_result = $xml_parser->parse($xml_content, array("WHMCSAPI"));
						for ($i=0;$i<$xml_result[1]; $i++) {
							if ($xml_result[0][0]['RESULT'] == 'success') {
							}
						}
					} else {
						$error = new Jaws_Error("There was a problem using WHMCS API: ".var_export($snoopy, true)." from ".$GLOBALS['app']->GetSiteURL().". user: ".var_export($userInfo, true), _t('USERS_NAME'));
						return new Jaws_Error("There was a problem using WHMCS API: ".var_export($snoopy, true)." from ".$GLOBALS['app']->GetSiteURL().". user: ".var_export($userInfo, true), _t('USERS_NAME'));
					}
					break;
				case 'addbillableitem':
					/*
					// WHMCS Add Billable Item API Call
					// See: http://docs.whmcs.com/API:Add_Billable_Item
					clientid - the User ID to assign the charge to
					description - the description to be shown to a customer when invoiced
					hours - number of hours/quantity (not required for single quantities)
					amount - the total amount to invoice for
					recur - frequency to recur - 1,2,3,etc... (optional)
					recurcycle - Days, Weeks, Months or Years (optional)
					recurfor - number of times to repeat (optional)
					invoiceaction - noinvoice, nextcron, nextinvoice, duedate, recur
					duedate - date the invoice should be due (only required for duedate & recur invoice actions)
					*/
					if($snoopy->submit($url, $postfields)) {
						// XML Parser
						$xml_content = $snoopy->results;
						/*
						echo '<pre>';
						var_dump($xml_content);
						echo '</pre>';
						exit;
						*/
						$xml_parser = new XMLParser;
						$xml_result = $xml_parser->parse($xml_content, array("WHMCSAPI"));
						for ($i=0;$i<$xml_result[1]; $i++) {
							if ($xml_result[0][0]['RESULT'] == 'success' && isset($xml_result[0][$i]['BILLABLEID'])) {
								$billableid = $xml_result[0][$i]['BILLABLEID'];
								return array('billableid' => $billableid);
							} else {
								$error = new Jaws_Error("There was a problem adding billable item for clientid: ".$postfields['clientid'].": ".var_export($xml_content, true)." from ".$GLOBALS['app']->GetSiteURL().". user: ".var_export($userInfo, true), _t('USERS_NAME'));
								return new Jaws_Error("There was a problem adding billable item for clientid: ".$postfields['clientid'].": ".var_export($xml_content, true)." from ".$GLOBALS['app']->GetSiteURL().". user: ".var_export($userInfo, true), _t('USERS_NAME'));
							}
						}
					} else {
						$error = new Jaws_Error("There was a problem using WHMCS API: ".var_export($snoopy, true)." from ".$GLOBALS['app']->GetSiteURL().". user: ".var_export($userInfo, true), _t('USERS_NAME'));
						return new Jaws_Error("There was a problem using WHMCS API: ".var_export($snoopy, true)." from ".$GLOBALS['app']->GetSiteURL().". user: ".var_export($userInfo, true), _t('USERS_NAME'));
					}
					break;
				case 'getinvoices':
					/*
					// WHMCS Get Invoices API Call
					// See: http://docs.whmcs.com/API:Get_Invoices
					userid - the client ID to retrieve invoices for
					status - the status to filter for, Paid, Unpaid, Cancelled, etc...
					limitstart - the offset number to start at when returning matches (optional, default 0)
					limitnum - the number of records to return (optional, default 25)
					*/
					if($snoopy->submit($url, $postfields)) {
						// XML Parser
						$xml_content = $snoopy->results;
						/*
						echo '<pre>';
						var_dump($xml_content);
						echo '</pre>';
						exit;
						*/
						$xml_parser = new XMLParser;
						$xml_result = $xml_parser->parse($xml_content, array("WHMCSAPI"));
						for ($i=0;$i<$xml_result[1]; $i++) {
							if ($xml_result[0][0]['RESULT'] == 'success' && isset($xml_result[0][$i]['INVOICES'])) {
								//foreach ($xml_result[0][$i]['INVOICES'] as $invoice) {
								//}
								return array(
									'totalresults' => $xml_result[0][$i]['TOTALRESULTS'],
									'startnumber' => $xml_result[0][$i]['STARTNUMBER'],
									'numreturned' => $xml_result[0][$i]['NUMRETURNED'],
									'invoices' => $xml_result[0][$i]['INVOICES']
								);
							} else {
								$error = new Jaws_Error("There was a problem getting invoices of clientid: ".$postfields['userid'].": ".var_export($xml_content, true)." from ".$GLOBALS['app']->GetSiteURL().". user: ".var_export($userInfo, true), _t('USERS_NAME'));
								return new Jaws_Error("There was a problem getting invoices of clientid: ".$postfields['userid'].": ".var_export($xml_content, true)." from ".$GLOBALS['app']->GetSiteURL().". user: ".var_export($userInfo, true), _t('USERS_NAME'));
							}
						}
					} else {
						$error = new Jaws_Error("There was a problem using WHMCS API: ".var_export($snoopy, true)." from ".$GLOBALS['app']->GetSiteURL().". user: ".var_export($userInfo, true), _t('USERS_NAME'));
						return new Jaws_Error("There was a problem using WHMCS API: ".var_export($snoopy, true)." from ".$GLOBALS['app']->GetSiteURL().". user: ".var_export($userInfo, true), _t('USERS_NAME'));
					}
					break;
				case 'getinvoice':
					/*
					// WHMCS Get Invoices API Call
					// See: http://docs.whmcs.com/API:Get_Invoice
					invoiceid - should be the invoice id you wish to retrieve
					*/
					if($snoopy->submit($url, $postfields)) {
						// XML Parser
						$xml_content = $snoopy->results;
						/*
						echo '<pre>';
						var_dump($xml_content);
						echo '</pre>';
						exit;
						*/
						$xml_parser = new XMLParser;
						$xml_result = $xml_parser->parse($xml_content, array("WHMCSAPI"));
						for ($i=0;$i<$xml_result[1]; $i++) {
							if ($xml_result[0][0]['RESULT'] == 'success' && isset($xml_result[0][$i]['INVOICEID']) && isset($xml_result[0][$i]['ITEMS'])) {
								return array(
									'invoiceid' => $xml_result[0][$i]['INVOICEID'],
									'invoicenum' => $xml_result[0][$i]['INVOICENUM'],
									'userid' => $xml_result[0][$i]['USERID'],
									'date' => $xml_result[0][$i]['DATE'],
									'duedate' => $xml_result[0][$i]['DUEDATE'],
									'datepaid' => $xml_result[0][$i]['DATEPAID'],
									'subtotal' => $xml_result[0][$i]['SUBTOTAL'],
									'credit' => $xml_result[0][$i]['CREDIT'],
									'tax' => $xml_result[0][$i]['TAX'],
									'tax2' => $xml_result[0][$i]['TAX2'],
									'total' => $xml_result[0][$i]['TOTAL'],
									'taxrate' => $xml_result[0][$i]['TAXRATE'],
									'taxrate2' => $xml_result[0][$i]['TAXRATE2'],
									'status' => $xml_result[0][$i]['STATUS'],
									'paymentmethod' => $xml_result[0][$i]['PAYMENTMETHOD'],
									'items' => $xml_result[0][$i]['ITEMS']
								);
							} else {
								$error = new Jaws_Error("There was a problem getting invoiceid: ".$postfields['invoiceid'].": ".var_export($xml_content, true)." from ".$GLOBALS['app']->GetSiteURL().". user: ".var_export($userInfo, true), _t('USERS_NAME'));
								return new Jaws_Error("There was a problem getting invoiceid: ".$postfields['invoiceid'].": ".var_export($xml_content, true)." from ".$GLOBALS['app']->GetSiteURL().". user: ".var_export($userInfo, true), _t('USERS_NAME'));
							}
						}
					} else {
						$error = new Jaws_Error("There was a problem using WHMCS API: ".var_export($snoopy, true)." from ".$GLOBALS['app']->GetSiteURL().". user: ".var_export($userInfo, true), _t('USERS_NAME'));
						return new Jaws_Error("There was a problem using WHMCS API: ".var_export($snoopy, true)." from ".$GLOBALS['app']->GetSiteURL().". user: ".var_export($userInfo, true), _t('USERS_NAME'));
					}
					break;
				case 'createinvoice':
					/*
					// WHMCS Create Invoice API Call
					// See: http://docs.whmcs.com/API:Create_Invoice
					userid - should contain the user id of the client you wish to create the invoice for
					date - the date the invoice is created in the format YYYYMMDD
					duedate - the date the invoice is due in the format YYYYMMDD
					taxrate - the rate of tax that should be charged
					paymentmethod - the payment method for the invoice eg. banktransfer
					notes - any additional notes the invoice should display to the customer
					sendinvoice - set to true to send the "Invoice Created" email to the customer
					autoapplycredit - pass as true to auto apply any available credit from the clients credit balance
					itemdescription1 - item 1 description
					itemamount1 - item 1 amount
					itemtaxed1 - set to true if item 1 should be taxed
					itemdescription2 - item 2 description
					itemamount2 - item 2 amount
					itemtaxed2 - set to true if item 2 should be taxed
					*/
					if($snoopy->submit($url, $postfields)) {
						// XML Parser
						$xml_content = $snoopy->results;
						/*
						echo '<pre>';
						var_dump($xml_content);
						echo '</pre>';
						exit;
						*/
						$xml_parser = new XMLParser;
						$xml_result = $xml_parser->parse($xml_content, array("WHMCSAPI"));
						for ($i=0;$i<$xml_result[1]; $i++) {
							if ($xml_result[0][0]['RESULT'] == 'success' && isset($xml_result[0][$i]['INVOICEID'])) {
								$invoiceid = $xml_result[0][$i]['INVOICEID'];
								return array('invoiceid' => $invoiceid);
							} else {
								$error = new Jaws_Error("There was a problem creating invoice: ".var_export($xml_content, true)." from ".$GLOBALS['app']->GetSiteURL().". user: ".var_export($userInfo, true), _t('USERS_NAME'));
								return new Jaws_Error("There was a problem creating invoice: ".var_export($xml_content, true)." from ".$GLOBALS['app']->GetSiteURL().". user: ".var_export($userInfo, true), _t('USERS_NAME'));
							}
						}
					} else {
						$error = new Jaws_Error("There was a problem using WHMCS API: ".var_export($snoopy, true)." from ".$GLOBALS['app']->GetSiteURL().". user: ".var_export($userInfo, true), _t('USERS_NAME'));
						return new Jaws_Error("There was a problem using WHMCS API: ".var_export($snoopy, true)." from ".$GLOBALS['app']->GetSiteURL().". user: ".var_export($userInfo, true), _t('USERS_NAME'));
					}
					break;
				case 'updateinvoice':
					/*
					// WHMCS Update Invoice API Call
					// See: http://docs.whmcs.com/API:Update_Invoice
					invoiceid - The ID of the invoice to update
					itemdescription - Array of existing line item descriptions to update. Line ID from database needed
					itemamount - Array of existing line item amounts to update
					itemtaxed - Array of existing line items taxed or not
					date - date of invoice format yyyymmdd
					duedate - duedate of invoice format yyyymmdd
					datepaid - date invoice was paid format yyyymmdd
					status - status of invoice. Unpaid, Paid, Cancelled, Collection, Refunded
					paymentmethod - payment method of invoice eg paypal, banktransfer
					notes - invoice notes					
					newitemdescription[1] - Array of new line item descriptipons to add
					newitemamount[1] - Array of new line item amounts
					newitemtaxed[1] - Array of new line items taxed or not
					newitemdescription[2] - Array of new line item descriptipons to add
					newitemamount[2] - Array of new line item amounts
					newitemtaxed[2] - Array of new line items taxed or not
					*/
					if($snoopy->submit($url, $postfields)) {
						// XML Parser
						$xml_content = $snoopy->results;
						/*
						echo '<pre>';
						var_dump($xml_content);
						echo '</pre>';
						exit;
						*/
						$xml_parser = new XMLParser;
						$xml_result = $xml_parser->parse($xml_content, array("WHMCSAPI"));
						for ($i=0;$i<$xml_result[1]; $i++) {
							if ($xml_result[0][0]['RESULT'] == 'success' && isset($xml_result[0][$i]['INVOICEID'])) {
								$invoiceid = $xml_result[0][$i]['INVOICEID'];
								return array('invoiceid' => $invoiceid);
							} else {
								$error = new Jaws_Error("There was a problem updating invoice: ".var_export($xml_content, true)." from ".$GLOBALS['app']->GetSiteURL().". user: ".var_export($userInfo, true), _t('USERS_NAME'));
								return new Jaws_Error("There was a problem updating invoice: ".var_export($xml_content, true)." from ".$GLOBALS['app']->GetSiteURL().". user: ".var_export($userInfo, true), _t('USERS_NAME'));
							}
						}
					} else {
						$error = new Jaws_Error("There was a problem using WHMCS API: ".var_export($snoopy, true)." from ".$GLOBALS['app']->GetSiteURL().". user: ".var_export($userInfo, true), _t('USERS_NAME'));
						return new Jaws_Error("There was a problem using WHMCS API: ".var_export($snoopy, true)." from ".$GLOBALS['app']->GetSiteURL().". user: ".var_export($userInfo, true), _t('USERS_NAME'));
					}
					break;
			}
		}
		return new Jaws_Error("Could not complete requested action, please try again later.", _t('USERS_NAME'));
	}
	
}
