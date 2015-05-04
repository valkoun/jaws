<?php
/**
 * WHMCS Gadget
 *
 * @category   Gadget
 * @package    WHMCS
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2012 Alan Valkoun
 */
class WHMCSHTML extends Jaws_GadgetHTML
{
    var $_Name = 'WHMCS';
    /**
     * Constructor
     *
     * @access public
     */
    function WHMCSHTML()
    {
        $this->Init('WHMCS');
    }

    /**
     * Excutes the default action, currently redirecting to index.
     *
     * @access public
     * @return string
     */
    function DefaultAction()
    {
	    //header("Location: ../../index.php");
        //return $this->GalleryXML();
	}

	/**
     * WHMCS API calls
     *
     * @access  public
     * @return  void
     */
    function API()
    {
		require_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Header.php';
		require_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'User.php';
		$jUser = new Jaws_User;
		$uid = $GLOBALS['app']->Session->GetAttribute('user_id');
		$userInfo = $jUser->GetUserInfoById((int)$uid, true, true, true, true);
		$request =& Jaws_Request::getInstance();
		$get = $request->get(array('fuseaction', 'fuseparams_keys', 'fuseparams_values', 'redirect_to', 'error_redirect_to', 'require_user_fields'), 'get');		
		$get['redirect_to'] = str_replace('&amp;', '&', urldecode($get['redirect_to']));
		$full_url = $GLOBALS['app']->GetFullURL();
		$error_redirect = $GLOBALS['app']->GetSiteURL() . '/index.php?gadget=WHMCS&action=API';
		$error_redirect .= '&fuseaction='.$get['fuseaction'].'&fuseparams_keys='.$get['fuseparams_keys'].'&fuseparams_values='.$get['fuseparams_values'];
		$error_redirect .= '&redirect_to='.(!empty($get['redirect_to']) ? urlencode($get['redirect_to']) : urlencode($full_url));
		$error_redirect = urlencode($error_redirect);	
		if (
			!$GLOBALS['app']->Session->Logged() || Jaws_Error::IsError($userInfo) || 
			!isset($userInfo['id']) || empty($userInfo['id'])
		) {
			$GLOBALS['app']->Session->PushSimpleResponse("You must log-in to view this page. If you don't have an account, you can <a href=\"index.php?gadget=Users&action=Registration&redirect_to=".$error_redirect."\">Create one</a>");
			Jaws_Header::Location($GLOBALS['app']->GetSiteURL() . '/index.php?gadget=Users&action=DefaultAction&redirect_to='.$error_redirect);
		}
		$require_user_fields = explode(',',$get['require_user_fields']);
		$fuseparams_keys = explode(',',$get['fuseparams_keys']);
		$fuseparams_values = explode(',',$get['fuseparams_values']);
		$api_param_keys = array();
		foreach ($fuseparams_keys as $fuse_k) {
			$api_param_keys[] = $fuse_k;
		}
		foreach ($fuseparams_values as $fuse_v) {
			$api_param_values[] = $fuse_v;
		}
		$fuseparams = array(
			'api_param_keys' => implode('__SYNTACTS__',$api_param_keys),
			'api_param_values' => implode('__SYNTACTS__',$api_param_values),
		);
						
		$model = $GLOBALS['app']->LoadGadget('WHMCS', 'Model');
		switch (strtolower($get['fuseaction'])) {
			case 'addclient':
				$api_call = $model->API('addclient', $fuseparams, 'html', $get['redirect_to'], $require_user_fields);			
				if (!Jaws_Error::IsError($api_call)) {
					return $api_call;
				} else {
					$error_message = $api_call->GetMessage();
					$GLOBALS['app']->Session->PushSimpleResponse($error_message, 'Users.Profile.Response');
					if (strpos($error_message, "Incomplete fields:") !== false) {
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
						if (is_array($require_user_fields) && !count($require_user_fields) <= 0) {
							foreach ($require_user_fields as $require_field) {
								if (isset($userInfo[$require_field]) && empty($userInfo[$require_field])) {
									Jaws_Header::Location($GLOBALS['app']->GetSiteURL() . '/index.php?gadget=Users&action=Profile&highlight='.implode(",",$require_user_fields).'&redirect_to='.$error_redirect);
								}
							}
						} else {
							Jaws_Header::Location($GLOBALS['app']->GetSiteURL() . '/index.php?gadget=Users&action=Profile&highlight=phone,address,city,region,postal&redirect_to='.$error_redirect);
						}
					} else {
						Jaws_Header::Location((!empty($get['error_redirect_to']) ? $get['error_redirect_to'] : $GLOBALS['app']->GetSiteURL() . '/index.php?gadget=Users&action=Profile&redirect_to='.urlencode($get['redirect_to'])));
					}
				}
				break;
			case 'addorder':
				$api_call = $model->API('addorder', $fuseparams, 'html', $get['redirect_to'], $require_user_fields);			
				if (!Jaws_Error::IsError($api_call)) {
					return $api_call;
				} else {
					$GLOBALS['app']->Session->PushSimpleResponse($api_call, 'Users.Profile.Response');
					Jaws_Header::Location((!empty($get['error_redirect_to']) ? urldecode($get['error_redirect_to']) : $GLOBALS['app']->GetSiteURL() . '/index.php?gadget=Users&action=Profile&redirect_to='.$get['redirect_to']));
				}
				break;
			case 'addbillableitem':
				$api_call = $model->API('addbillableitem', $fuseparams, 'html', $get['redirect_to'], $require_user_fields);			
				if (!Jaws_Error::IsError($api_call)) {
					return $api_call;
				} else {
					$GLOBALS['app']->Session->PushSimpleResponse($api_call, 'Users.Profile.Response');
					Jaws_Header::Location((!empty($get['error_redirect_to']) ? urldecode($get['error_redirect_to']) : $GLOBALS['app']->GetSiteURL() . '/index.php?gadget=Users&action=Profile&redirect_to='.$get['redirect_to']));
				}
				break;
		}
		Jaws_Header::Location($GLOBALS['app']->GetSiteURL() . '/index.php?gadget=Users&action=Profile&redirect_to='.$get['redirect_to']);
	}
}
