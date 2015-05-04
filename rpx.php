<?php
// Below is a very simple PHP 5 script that implements the RPX token URL processing.
// The code below assumes you have the CURL HTTP fetching library.
 
$rpxApiKey = '410a626be9029146de7ffe62197a9b2beef7c3d1';

require_once JAWS_PATH . 'include/Jaws/InitApplication.php';
require_once JAWS_PATH . 'include/Jaws/Header.php';
 
if(isset($_POST['token']) && isset($_POST['redirect']) && isset($_POST['d'])) {
 
	/* STEP 1: Extract token POST parameter */
	$token = $_POST['token'];
 
	/* STEP 2: Use the token to make the auth_info API call */
	$post_data = array('token' => $_POST['token'],
                     'apiKey' => $rpxApiKey,
                     'format' => 'json');
 
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_URL, 'https://rpxnow.com/api/v2/auth_info');
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
	curl_setopt($curl, CURLOPT_HEADER, false);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	$raw_json = curl_exec($curl);
	curl_close($curl);
 
 
	/* STEP 3: Parse the JSON auth_info response */
	if ( !function_exists('json_decode') ){
		function json_decode($content, $assoc=false){
					require_once 'Services/JSON.php';
					if ( $assoc ){
						$json = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
			} else {
						$json = new Services_JSON;
					}
			return $json->decode($content);
		}
	}

	$auth_info = json_decode($raw_json, true);
	$site_url = urldecode($_POST['d']);
 
	if ($auth_info['stat'] == 'ok') {
  
		/* STEP 3 Continued: Extract the 'identifier' from the response */
		$profile = $auth_info['profile'];
		$identifier = $profile['identifier'];
		$photo_url = '';
		$name = '';
		$username = substr(md5($identifier), 0, 8);
		$password = substr(md5($identifier), 0, 12);
		$email = '';
		
		if (isset($profile['photo']) && !empty($profile['photo'])) {
			$photo_url = $profile['photo'];
		}
	 
		if (isset($profile['email']) && !empty($profile['email'])) {
			$email = $profile['email'];
			$username = substr($email, 0, strpos($email, '@'));
		}
	 
		if (isset($profile['displayName']) && !empty($profile['displayName'])) {
			$name = $profile['displayName'];
			$username = $name;
		}
		$username = substr(preg_replace("[^A-Za-z0-9]", '', $username), 0, 12);
	 
		/* 
		STEP 4: Use the identifier as the unique key to sign the user into your system.
		This will depend on your website implementation, and you should add your own
		code here.
		*/
		$pieces = parse_url($site_url);
		$site_domain = isset($pieces['host']) ? $pieces['host'] : '';
		$site_path = isset($pieces['path']) ? $pieces['path'] : '';
		$site_scheme = isset($pieces['scheme']) ? $pieces['scheme'] : 'http';
		$redirect = urldecode($_POST['redirect']);
		$redirect = str_replace('__EQ__', '=', $redirect);
		$redirect = str_replace('__FS__', '/', $redirect);
		$redirect = str_replace('__AM__', '&', $redirect);
		$redirect = str_replace('__QM__', '?', $redirect);
		
		include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
		$snoopy = new Snoopy;
				
		// Check existing identifier?
		$path = (!empty($site_path) ? $site_path : '').'/data/xmlrpc/Users/XmlRpc.php';
		$method = 'GetUserInfoByIdentifier';
		$params = array(
			'Username', 'Password', substr(md5($identifier), 0, 32), true, true, true, true
		);
		$result = $GLOBALS['app']->XmlRpc($site_scheme.'://'.$site_domain, $path, $method, $params);
		if (
			!Jaws_Error::IsError($result) && 
			isset($result[0]['id']) && !empty($result[0]['id']) && 
			isset($result[0]['username']) && !empty($result[0]['username']) 
		) {
			$username = $result[0]['username'];
			$name = (isset($result[0]['nickname']) && !empty($result[0]['nickname']) ? $result[0]['nickname'] : $name);
			$email = (isset($result[0]['email']) && !empty($result[0]['email']) ? $result[0]['email'] : $email);
		}
		
		$submit_vars = array();
		$submit_vars['username'] = $username;
		$submit_vars['password'] = $password;
		$submit_vars['password_check'] = $password;
		$submit_vars['nickname'] = $name;
		if (!empty($email)) {
			$submit_vars['email'] = $email;
		} else {
			if (preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $site_domain, $regs)) {
				$submit_vars['email'] = strtolower($submit_vars['username']).'@'.$regs['domain'];
			} else {
				$submit_vars['email'] = strtolower($submit_vars['username']).'@example.org';
			}
		}
		$submit_vars['nocaptcha'] = 'rpxnow';
/*
		// Register new user (existing user returns true)
		$submit_url = $site_url.'/index.php?gadget=Users&action=DoRegister';
		if (substr(strtolower($site_url), 0, 5) == 'https') {
			$snoopy->curl_path = "/usr/bin/curl";
			$snoopy->port = 443;
		}
		if($snoopy->submit($submit_url,$submit_vars)) {
*/		
		// Add user via XMLRPC
		$method = 'AddUser';
		$params = array(
			'Username', 'Password', $submit_vars['username'], $submit_vars['nickname'], $submit_vars['email'], 
			$submit_vars['password'], 2, true, '', '', '', '', '', '', '', true, ''
		);
		$result = $GLOBALS['app']->XmlRpc($site_scheme.'://'.$site_domain, $path, $method, $params);
		// Increment username if there's a collision
		if (Jaws_Error::IsError($result)) {
			$i = 0;
			while (Jaws_Error::IsError($result) && strpos($result->GetMessage(), 'USERS_USERS_ALREADY_EXISTS') !== false) {
				$i++;
				$username = $submit_vars['username'].$i;
				$params = array(
					'Username', 'Password', $username, $submit_vars['nickname'], $submit_vars['email'], 
					$submit_vars['password'], 2, true, '', '', '', '', '', '', '', true, ''
				);
				$result = $GLOBALS['app']->XmlRpc($site_scheme.'://'.$site_domain, $path, $method, $params);
			}
		}
		if (!Jaws_Error::IsError($result)) {
			$submit_vars['username'] = $username;
			// Verify added user
			$method = 'GetUserInfoByName';
			$params = array(
				'Username', 'Password', $username, true, true, true, true
			);
			$result = $GLOBALS['app']->XmlRpc($site_scheme.'://'.$site_domain, $path, $method, $params);
			if (!Jaws_Error::IsError($result) && isset($result[0]['checksum']) && !empty($result[0]['checksum'])) {
/*
				// Update User to set additional user info
				$method2 = 'UpdateUser';
				$params2 = array(
					'Username', 'Password', $result[0]['checksum'], $result[0]['username'], $result[0]['nickname'], $result[0]['email'], 
					$result[0]['passwd'], (int)$result[0]['user_type'], (int)$result[0]['enabled']
				);
				$result2 = $GLOBALS['app']->XmlRpc($site_scheme.'://'.$site_domain, $path, $method2, $params2);
				if (!Jaws_Error::IsError($result2)) {
*/
				// Update User to set additional user info
				$method2 = 'UpdatePersonalInfo';
				$params2 = array(
					'Username', 'Password', $result[0]['checksum'], $result[0]['fname'], $result[0]['lname'], (int)$result[0]['gender'], 
					(is_null($result[0]['dob']) || empty($result[0]['dob']) ? 'null' : $result[0]['dob']), $result[0]['url'], 
					$result[0]['company'], $result[0]['address'], $result[0]['address2'], 
					$result[0]['city'], $result[0]['country'], $result[0]['region'], $result[0]['postal'], $result[0]['phone'],
					$result[0]['office'], $result[0]['tollfree'], $result[0]['fax'], $result[0]['merchant_id'], 
					$result[0]['description'], $photo_url, $result[0]['keywords'], $result[0]['company_type']
				);
				$result2 = $GLOBALS['app']->XmlRpc($site_scheme.'://'.$site_domain, $path, $method2, $params2);
				if (!Jaws_Error::IsError($result2)) {
				}
					// Update User to set additional user info
					$method3 = 'UpdateAdvancedOptions';
					$params3 = array(
						'Username', 'Password', $result[0]['checksum'], $result[0]['language'], $result[0]['theme'], $result[0]['editor'], 
						$result[0]['timezone'], $result[0]['notification'], (bool)$result[0]['allow_comments'], substr(md5($identifier), 0, 32)
					);
					$result3 = $GLOBALS['app']->XmlRpc($site_scheme.'://'.$site_domain, $path, $method3, $params3);
					if (!Jaws_Error::IsError($result3)) {
						// Create session for user (log-in)
						if ((bool)$result[0]['enabled'] === true) {
							$output = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">";
							$output .= "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\">";
							$output .= "<head>";
							$output .= "<title>Logging in</title>";
							$output .= "</head>";
							$output .= "<body>";
							$output .= "<form action='".$site_url."/index.php?gadget=Users&action=DoRegister' method='post' name='frm'>";
							foreach ($submit_vars as $a => $b) {
								$output .= "<input type='hidden' name='".$a."' value='".$b."'>";
							}
							$output .= "<input type='hidden' name='redirect_to' value='".$redirect."'>";
							$output .= "<input type='hidden' name='remember' value='false'>";

							$output .= "<noscript>";
							$output .= "Connecting your account.<br /><input type='submit' value='Click Here to Continue'>";
							$output .= "</noscript>"; 
							$output .= "</form>";
							$output .= "<script type='text/javascript' language='JavaScript'>";
							$output .= "document.write('Connecting your account. Please wait...');document.frm.submit();";
							$output .= "</script>"; 
							$output .= "</body>"; 
							$output .= "</html>"; 
							echo $output;
						} else {
							header('Location:'.$site_url."/index.php?gadget=Users&action=Registered");
						}
						//header('Location: '.$GLOBALS['app']->getSiteURL().'/index.php?gadget=Users&action=Login');
						//echo $snoopy->results;
					} else {
						$error = new Jaws_Error('An error occured: UpdateAdvancedOptions RPC call failed: ' . $result3->GetMessage(), 'RPX');
						$msg = $result3->GetMessage();
						if (strpos($msg, "'fs' => '") !== false) {
							$inputStr = $msg;
							$delimeterLeft = "'fs' => '";
							$delimeterRight = "*'";
							$posLeft = (strpos($inputStr, $delimeterLeft)+strlen($delimeterLeft));
							$posRight = strpos($inputStr, $delimeterRight, $posLeft);
							$msg = substr($inputStr, $posLeft, $posRight-$posLeft);
							$GLOBALS['app']->Registry->LoadFile('Users');
							$GLOBALS['app']->Translate->LoadTranslation('Users', JAWS_GADGET);
							$msg = _t($msg);
						}
						Jaws_Header::Location($site_url."/index.php?gadget=Users&action=DefaultAction&msg=".urlencode($msg));
						//echo 'An error occured: UpdateAdvancedOptions RPC call failed. <br /><br />More info: <br />'.$result3->GetMessage();
					}
/*
				} else {
					$error = new Jaws_Error('An error occured: UpdateUser RPC call failed: ' . $result2->GetMessage(), 'RPX');
					echo 'An error occured: UpdateUser RPC call failed.<br /><br />More info: <br />'.$result2->GetMessage();
				}
*/
			} else {
				$error = new Jaws_Error('An error occured: GetUserInfoByName RPC call failed: ' . 
				(!isset($result[0]['checksum']) || empty($result[0]['checksum']) ? var_export($result, true) : $result->GetMessage()), 'RPX');
				if (Jaws_Error::IsError($result)) {
					$msg = $result->GetMessage();
					if (strpos($msg, "'fs' => '") !== false) {
						$inputStr = $msg;
						$delimeterLeft = "'fs' => '";
						$delimeterRight = "*'";
						$posLeft = (strpos($inputStr, $delimeterLeft)+strlen($delimeterLeft));
						$posRight = strpos($inputStr, $delimeterRight, $posLeft);
						$msg = substr($inputStr, $posLeft, $posRight-$posLeft);
						$GLOBALS['app']->Registry->LoadFile('Users');
						$GLOBALS['app']->Translate->LoadTranslation('Users', JAWS_GADGET);
						$msg = _t($msg);
					}
					Jaws_Header::Location($site_url."/index.php?gadget=Users&action=DefaultAction&msg=".urlencode($msg));
					//echo 'An error occured: GetUserInfoByName RPC call failed. <br /><br />More info: <br />'.$result->GetMessage();
				} else {
					Jaws_Header::Location($site_url."/index.php?gadget=Users&action=DefaultAction&msg=".urlencode("An error occured: GetUserInfoByName RPC call failed."));
					//echo 'An error occured: GetUserInfoByName RPC call failed.';
				}
			}
		} else {
			$error = new Jaws_Error('An error occured: AddUser RPC call failed. Error: ' . var_export($result, true), 'RPX');
			$msg = $result->GetMessage();
			if (strpos($msg, "'fs' => '") !== false) {
				$inputStr = $msg;
				$delimeterLeft = "'fs' => '";
				$delimeterRight = "*'";
				$posLeft = (strpos($inputStr, $delimeterLeft)+strlen($delimeterLeft));
				$posRight = strpos($inputStr, $delimeterRight, $posLeft);
				$msg = substr($inputStr, $posLeft, $posRight-$posLeft);
				$GLOBALS['app']->Registry->LoadFile('Users');
				$GLOBALS['app']->Translate->LoadTranslation('Users', JAWS_GADGET);
				$msg = _t($msg);
			}
			Jaws_Header::Location($site_url."/index.php?gadget=Users&action=DefaultAction&msg=".urlencode($msg));
			//echo 'An error occured: AddUser RPC call failed. <br /><br />More info: <br />'.$result->GetMessage();
		}
 
	/* an error occurred */
	} else {
	  // gracefully handle the error. Hook this into your native error handling system.
		$error = new Jaws_Error('An error occured: ' . $auth_info['err']['msg'], 'RPX');
		Jaws_Header::Location($site_url."/index.php?gadget=Users&action=DefaultAction&msg=".urlencode("An error occured: " . $auth_info['err']['msg']));
		//echo 'An error occured: ' . $auth_info['err']['msg'];
	}
}
?>