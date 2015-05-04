<?php
/**
 * Social Gadget
 *
 * @category   Gadget
 * @package    Social
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @copyright  2004-2009 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 * @TODO    SubscribeButton action to generate button allowing subscribing to specific content through newsfeed.
 * @TODO    Subscribe action to allow subscribing to site-wide or gadget-wide (depending on scope) content through newsfeed.
 */
class SocialHTML extends Jaws_GadgetHTML
{
    /**
     * Main Constructor
     *
     * @access      public
     */
    function SocialHTML()
    {
        $this->Init('Social');
    }

    /**
     * Default Action
     *
     * @access      public
     * @return      string   HTML content of DefaultAction
     */
    function DefaultAction()
    {
        $layoutGadget = $GLOBALS['app']->LoadGadget('Social', 'LayoutHTML');
        return $layoutGadget->Display();
    }

	/**
     * Prepares a single form to unsubscribe e-mail, or user
     *
     * @access  public
     * @return  string  XHTML of template
     */
    function Unsubscribe()
    {
		require_once JAWS_PATH . 'include/Jaws/Header.php';
        $model = $GLOBALS['app']->LoadGadget('Social', 'Model');
		$request =& Jaws_Request::getInstance();
		$email = $request->get('email', 'get');
		if (is_null($email)) {
			$email = $request->get('email', 'post');
		}
		if (!is_null($email)) {
			$info = $model->GetEmail($email);
			if (!Jaws_Error::IsError($info)) {
				if (!isset($info['id'])) {
					require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
					return Jaws_HTTPError::Get(404);
				}
				if (isset($info['id']) && !empty($info['id'])) {
					// Load the template
					$tpl = new Jaws_Template('gadgets/Social/templates/');
					$tpl->Load('Unsubscribe.html');
					if ($info['active'] == 'N') {
						$tpl->SetBlock('already_unsubscribed');
						$tpl->SetVariable('content', 'You\'ve already unsubscribed from this list. <a href="'.$GLOBALS['app']->GetSiteURL().'">Click here to continue</a>.');
						$tpl->ParseBlock('already_unsubscribed');
						return $tpl->Get();
					}
					$tpl->SetBlock('unsubscribe');
					$tpl->SetVariable('title', _t('USERS_USERS_REQUEST_GROUP_ACCESS',  (!empty($info['realname']) ? $info['realname'] : $info['name'])));
					$tpl->SetVariable('base_script', BASE_SCRIPT);
					$tpl->SetVariable('cancel_url', $GLOBALS['app']->GetSiteURL());
					$tpl->SetVariable('email', $info['email']);
					$tpl->SetVariable('request_string', _t('SOCIAL_EMAIL_UNSUBSCRIBE_STRING'));
					$tpl->ParseBlock('unsubscribe');
					return $tpl->Get();
				}
			}
		}
        Jaws_Header::Location($GLOBALS['app']->GetSiteURL());
    }
	
    /**
     * Unsubscribed
     *
     * @access 	public
     * @return  string  XHTML of template
     */
    function Unsubscribed()
    {
        $model = $GLOBALS['app']->LoadGadget('Social', 'Model');
		$request =& Jaws_Request::getInstance();
		$email = $request->get('email', 'get');
		if (is_null($email)) {
			$email = $request->get('email', 'post');
		}

		if (!is_null($email)) {
			$tpl = new Jaws_Template('gadgets/Social/templates/');
			$tpl->Load('Unsubscribed.html');
			$tpl->SetBlock('unsubscribe');
		   
			$tpl->SetVariable('title', _t('SOCIAL_EMAIL_UNSUBSCRIBE_TITLE'));
			$tpl->SetVariable('content', _t('SOCIAL_EMAIL_UNSUBSCRIBE_MESSAGE', $GLOBALS['app']->GetSiteURL()));
			
			$unsubscribe = $model->UnSubscribeEmail($email);
			if (Jaws_Error::IsError($unsubscribe)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('SOCIAL_ERROR_EMAIL_NOT_UNSUBSCRIBED'), RESPONSE_ERROR);
			} else {
				$GLOBALS['app']->Session->PushLastResponse(_t('SOCIAL_EMAIL_UNSUBSCRIBED'), RESPONSE_ERROR);
			}
			
			if ($response = $GLOBALS['app']->Session->PopSimpleResponse()) {
				$tpl->SetBlock('unsubscribe/response');
				$tpl->SetVariable('msg', $response);
				$tpl->ParseBlock('unsubscribe/response');
			}
				
			$tpl->ParseBlock('unsubscribe');
			return $tpl->Get();
		}
		require_once JAWS_PATH . 'include/Jaws/Header.php';
        Jaws_Header::Location($GLOBALS['app']->GetSiteURL());
	}

	/**
     * Import email addresses via RSS feeds.
     *
     * @access public
     * @return HTML string
     */
    function UpdateRSSEmails()
    {		
		ignore_user_abort(true); 
        set_time_limit(0);
		ob_start();
		echo  "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
		echo  "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\">\n";
		echo  " <head>\n";
		//echo  "  <meta http-equiv='refresh' content='10'>";
		echo  "  <title>Update RSS Emails</title>\n";
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
		echo " <script type='text/javascript'>function submitForm(){if(document.getElementById('email_rss_form')){document.forms['email_rss_form'].submit();};}</script>\n";
		echo  " </head>\n";
		// tag after text for Safari & Firefox
		// 8 char minimum for Firefox
		ob_flush();
		flush();  // worked without ob_flush() for me
		sleep(1);
		$model = $GLOBALS['app']->LoadGadget('Social', 'Model');
		$adminModel = $GLOBALS['app']->LoadGadget('Social', 'AdminModel');
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
			echo  " <script type=\"text/javascript\">sURL = 'index.php?gadget=Social&action=UpdateRSSEmails&fetch_url=".urlencode($searchfetch_url)."&rss_url=".urlencode($searchrss_url)."&num=".(int)$searchnum."&ua=Y';</script>\n";
			$searchfetch_url = str_replace(' ', '%20', $searchfetch_url);
			$adminModel->InsertRSSEmails($searchfetch_url, $searchrss_url, (int)$searchnum, 'Y');
			/*
			if (Jaws_Error::IsError($result)) {
				echo '<br />'.$result->GetMessage();
			}
			*/
		} else if (!empty($searchfile) && !empty($searchtype) && (!empty($searchnum) || (int)$searchnum == 0 || (int)$searchnum == 1)) {		
			echo  " <body onload='doLoad(); submitForm();'>\n";
			echo  " <script type=\"text/javascript\">sURL = 'index.php?gadget=Social&action=UpdateRSSEmails&file=".$searchfile."&type=".$searchtype."&num=".(int)$searchnum."&ua=Y';</script>\n";
			//echo '<br />file ::: '.$searchfile;
			$adminModel->InsertEmails($searchfile, $searchtype, $searchnum, $user_attended);
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
     * Handle Facebook Auth Token Responses
     *
     * @access  public
     * @return  void
     */
    function FacebookResponse()
    {
		require_once JAWS_PATH . 'include/Jaws/Header.php';
        $model = $GLOBALS['app']->LoadGadget('Social', 'Model');
        $adminModel = $GLOBALS['app']->LoadGadget('Social', 'AdminModel');
		$request =& Jaws_Request::getInstance();
		$code = $request->get('code', 'get');
		if (is_null($code)) {
			$code = $request->get('code', 'post');
		}
		$OwnerID = $request->get('OwnerID', 'get');
		if (is_null($OwnerID)) {
			$OwnerID = $request->get('OwnerID', 'post');
		}
		if (empty($OwnerID)) {
			$OwnerID = 0;
		}
		if ($GLOBALS['app']->Session->Logged() && !empty($code)) {
			$info = $model->GetSocialOfUserID($OwnerID, 'facebook');
			if (Jaws_Error::IsError($info)) {
				$error = $info;
			} else if (
				!isset($info[0]['id']) || empty($info[0]['id']) || 
				!isset($info[0]['social_id']) || empty($info[0]['social_id']) || 
				!isset($info[0]['social_id2']) || empty($info[0]['social_id2'])
			) {
				$error = new Jaws_Error(_t('SOCIAL_ERROR_SOCIAL_NOT_RETRIEVED'), _t('SOCIAL_NAME'));
			} else {
				$facebook_url = 'https://graph.facebook.com/oauth/access_token?';
				$facebook_url .= 'client_id='.$info[0]['social_id'].'&redirect_uri='.urlencode($GLOBALS['app']->GetFullURL());
				$facebook_url .= '&client_secret='.$info[0]['social_id2'].'&code='.$code;
  
				require_once 'HTTP/Request.php';
				$httpRequest = new HTTP_Request($facebook_url);
				$httpRequest->setMethod(HTTP_REQUEST_METHOD_GET);
				$resRequest = $httpRequest->sendRequest();
				if (PEAR::isError($resRequest) || (int) $httpRequest->getResponseCode() <> 200) {
					$error = new Jaws_Error('ERROR REQUESTING URL: '.$facebook_url.' '."\n".'response_code: '.$httpRequest->getResponseCode(), _t('SOCIAL_NAME'));
				}
				$data = $httpRequest->getResponseBody();
				if (!empty($data)) {
					$data = parse_str($data, $output);
					if (isset($output['access_token']) && !empty($output['access_token']) && isset($output['expires']) && !empty($output['expires'])) {
						$result = $adminModel->UpdateAccessToken(
							$info[0]['id'], $output['access_token'], (time()+(int)$output['expires']), 
							(int)$GLOBALS['app']->Session->GetAttribute('user_id'), 'Y'
						);
						if (Jaws_Error::IsError($result)) {
							$error = $result;
						}
					} else {
						$error = new Jaws_Error('ERROR PARSING FACEBOOK RESPONSE: '.var_export($data, true), _t('SOCIAL_NAME'));
					}
				} else {
					$error = new Jaws_Error('ERROR WITH RESPONSE BODY: url: '.$facebook_url.' '."\n".'response_code: '.$httpRequest->getResponseCode().' '."\n".'response_body: '.var_export($data, true), _t('SOCIAL_NAME'));
				}
			}
		}
        Jaws_Header::Location($GLOBALS['app']->GetSiteURL().'/'.$GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'));
    }
	
	/**
     * Facebook Channel
     *
     * @access  public
     * @return  void
     */
    function fbchannel()
    {
	 $cache_expire = 60*60*24*365;
	 header("Pragma: public");
	 header("Cache-Control: max-age=".$cache_expire);
	 header('Expires: ' . gmdate('D, d M Y H:i:s', time()+$cache_expire) . ' GMT');
	 echo '<script src="//connect.facebook.net/en_US/all.js"></script>';
	}
	
}
