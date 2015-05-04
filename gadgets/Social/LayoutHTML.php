<?php
/**
 * Social Gadget (layout client side)
 *
 * @category   GadgetLayout
 * @package    Social
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2004-2009 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/gpl.html
 * @TODO    Add SocialSignOn action, which generates RPX SocialSignOn
 */
class SocialLayoutHTML
{
    /**
     * Display social icons (Facebook, Twitter, Email) for sharing content. 
     *
     * @category 	feature
     * @access      public
     * @return      object   The template of the Social gadget
     */
    function Display($websites = array(), $title = '', $url = '')
    {
        $tpl = new Jaws_Template('gadgets/Social/templates/');
        $tpl->Load('Social.html');
        $tpl->SetBlock('social');

        $tpl->SetVariable('base_script', BASE_SCRIPT);
        $tpl->SetVariable('title', _t('SOCIAL_ACTION_TITLE'));
        
        $checked = $GLOBALS['app']->Registry->Get('/gadgets/Social/webs');
		$checked = explode(",",$checked);
    	$model = $GLOBALS['app']->LoadGadget('Social', 'Model');
		$social_exists = array();
		
        if (!count($websites) <= 0) {
			reset($websites);
		} else {
			$websites = $model->getSocialWebsites($title, $url);
			$social_exists = $model->GetSocialOfUserID(0);
			if (Jaws_Error::IsError($social_exists)) {
				//$GLOBALS['app']->Session->PushLastResponse($social_exists->GetMessage(), RESPONSE_ERROR);
				//return $social_exists;
			}
		}
		
        $iconset = $GLOBALS['app']->Registry->Get('/gadgets/Social/iconset');
        $show_email = false;
		
        foreach($websites as $key => $value){
			foreach ($social_exists as $exist) {
				if ($exist['social'] == $key) {
					switch ($key) {
						case 'facebook':
							$GLOBALS['app']->Layout->AddHeadOther('<meta property="fb:app_id" content="'.$exist['social_id'].'" />');
							break;
					}
					if (!is_null($exist['social_url']) && !empty($exist['social_url'])) {
						$websites[$key]["sendurl"] = $exist['social_url'];
						break;
					}
				}
			}
			
	        if (in_array($key,$checked)){
				$tpl->SetBlock('social/item');
				$tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . "/");
				$tpl->SetVariable('id', 'social_icon_'.$key);
				$tpl->SetVariable('iconset', $iconset);
				$tpl->SetVariable('stitle', $websites[$key]["realname"]);
				$tpl->SetVariable('key', $key);
				$tpl->SetVariable('onclick', (substr(strtolower($websites[$key]["sendurl"]), 0, 11) == 'javascript:' ? $websites[$key]["sendurl"] : "javascript:window.open('".$websites[$key]["sendurl"]."');"));
				$tpl->SetVariable('sendto', _t('SOCIAL_SENDTO'));
	            $tpl->ParseBlock('social/item');	   
				if ($key == 'email') {
					$show_email = true;
				}
	        }
        }
		
		if ($show_email === true) {
			$tpl->SetBlock('social/email_form');
			// E-mail Form
			$formsLayout = $GLOBALS['app']->LoadGadget('Forms', 'LayoutHTML');
			$now = $GLOBALS['db']->Date();
			if (strrpos($GLOBALS['app']->GetSiteURL(), "/") > 8) {
				$site_url = substr($GLOBALS['app']->GetSiteURL(), 0, strrpos($GLOBALS['app']->GetSiteURL(), "/"));
			} else {
				$site_url = $GLOBALS['app']->GetSiteURL();		
			}
			$site_name = $GLOBALS['app']->Registry->Get('/config/site_name');
			$site_name = (empty($site_name) ? str_replace('https://', '', str_replace('http://', '', $site_url)) : $site_name);
			//$redirect = $GLOBALS['app']->GetSiteURL() . "/index.php?gadget=".$this->_Name."&action=Product&id=".$page['id'];
			//$redirect = $GLOBALS['app']->GetSiteURL().'/'.$GLOBALS['app']->Map->GetURLFor('Properties', 'Property', array('id' => $page['fast_url']));
			$redirect = $GLOBALS['app']->GetFullURL();
			$redirect = (substr($redirect, 0, 4) != 'http' ? $site_url.'/'.$redirect : $redirect);
			/*
			Custom Form implementation
			- Add "__REQUIRED__" to any question title to make the field required
			- Add "__EXTRA_RECIPIENT__" to add the field as a recipient
			- Add "__REDIRECT__" to specify where we are coming from/return URL after form submission
			- Add "__MESSAGE__" to show as a message in the resultant e-mail
			*/	
			$email_form = $formsLayout->Display(null, true, array('id' => 'custom', 'sort_order' => 0, 'title' => 'E-mail To Friends', 
				'sm_description' => '', 'description' => "E-mail this to up to 5 of your friends.", 'clause' => '', 
				'image' => '', 'recipient' => '', 'parent' => 0, 'custom_action' => '', 'fast_url' => '', 'active' => 'Y', 
				'ownerid' => 0, 'created' => $now, 'updated' => $now, 
				'submit_content' => "<div style='margin-bottom: 10px;'>Thank you for taking the time to forward this to your friends!</div><div><a href='".$redirect."'>Click here to return to the previous page</a>.</div>",
				'checksum' => ''),
				array(array('id' => 9, 'sort_order' => 0, 'formid' => 'custom', 
				'title' => "__MESSAGE__", 'itype' => 'HiddenField', 'required' => 'N', 
				'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''),
				array('id' => 2, 'sort_order' => 1, 'formid' => 'custom', 
				'title' => '__FROM_EMAIL____REQUIRED__', 'itype' => 'TextBox', 'required' => 'N', 
				'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''),
				array('id' => 1, 'sort_order' => 2, 'formid' => 'custom', 
				'title' => '__FROM_NAME__', 'itype' => 'TextBox', 'required' => 'N', 
				'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''), 
				array('id' => 3, 'sort_order' => 3, 'formid' => 'custom', 
				'title' => "Friend's Email Address 1__EXTRA_RECIPIENT____REQUIRED__", 'itype' => 'TextBox', 'required' => 'N', 
				'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''),
				array('id' => 4, 'sort_order' => 4, 'formid' => 'custom', 
				'title' => "Friend's Email Address 2__EXTRA_RECIPIENT__", 'itype' => 'TextBox', 'required' => 'N', 
				'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''),
				array('id' => 5, 'sort_order' => 5, 'formid' => 'custom', 
				'title' => "Friend's Email Address 3__EXTRA_RECIPIENT__", 'itype' => 'TextBox', 'required' => 'N', 
				'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''),
				array('id' => 6, 'sort_order' => 6, 'formid' => 'custom', 
				'title' => "Friend's Email Address 4__EXTRA_RECIPIENT__", 'itype' => 'TextBox', 'required' => 'N', 
				'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''),
				array('id' => 7, 'sort_order' => 7, 'formid' => 'custom', 
				'title' => "Friend's Email Address 5__EXTRA_RECIPIENT__", 'itype' => 'TextBox', 'required' => 'N', 
				'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''),
				array('id' => 8, 'sort_order' => 8, 'formid' => 'custom', 
				'title' => "__REDIRECT__", 'itype' => 'HiddenField', 'required' => 'N', 
				'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => '')
				), 
				array(array('id' => 1, 'sort_order' => 0, 'linkid' => 8, 
				'formid' => 'custom', 'title' => "<a href='".$redirect."'>".$redirect."</a>",
				'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''),
				array('id' => 2, 'sort_order' => 1, 'linkid' => 9, 
				'formid' => 'custom', 'title' => "One of your friends thought you might be interested in something on ".$site_name,
				'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => '')
				)
			);
			
			$email_form .= "\n".'<style type="text/css">#social_email_form_holder {max-height: 300px; height: 300px; overflow-y: scroll; overflow-x: hidden;} #social_email_form_holder .layout_head h2 {display: none;}</style>';
			$GLOBALS['app']->Layout->AddHeadOther('<script type="text/javascript">
				Event.observe(window, "load", function() {
					var socialEmailForm = $("social_email_form").innerHTML;
					var array = {"<h1>":"<h3>", "</h1>":"</h3>"};
					for (var val in array) {socialEmailForm = socialEmailForm.replace(new RegExp(val, "g"), array[val]);}
					var socialEmailFormHolder = document.createElement("div");
					socialEmailFormHolder.id = "social_email_form_holder";
					socialEmailFormHolder.innerHTML = "<div id=\"social_email_form\">"+socialEmailForm+"</div>";
					var buildSocialEmailForm = function(tip) {
						return socialEmailFormHolder;
					};
					Tips.add($("social_icon_email"), buildSocialEmailForm, {
						className: "slick",
						showOn: "mouseover",
						hideTrigger: "tip",
						hideOn: "mouseout",
						stem: false,
						delay: false,
						tipJoint: [ "center", "top" ],
						target: $("social_icon_email"),
						showEffect: "appear",
						offset: [ 0, ((-10)+(Prototype.Browser.IE === false && $$("html")[0].style.marginTop != "" && $$("html")[0].style.marginTop != "0px" ? parseFloat($$("html")[0].style.marginTop.replace("px", "")) : 0)) ]
					});
				});
			</script>');
			
			$tpl->SetVariable('SEND_TO_FRIENDS', $email_form);
			$tpl->ParseBlock('social/email_form');
		}
		
        $tpl->ParseBlock('social');
        return $tpl->Get();
    }
}
