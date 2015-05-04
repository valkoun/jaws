<?php
/**
 * Forms Gadget
 *
 * @category   Gadget
 * @package    Forms
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class FormsHTML extends Jaws_GadgetHTML
{
    var $_Name = 'Forms';
    /**
     * Constructor
     *
     * @access public
     */
    function FormsHTML()
    {
        $this->Init('Forms');
    }

    /**
     * Excutes the default action, currently displaying the default page.
     *
     * @access public
     * @return string
     */
    function DefaultAction()
    {
        return $this->Index;
    }

    /**
     * Displays an individual form.
     *
     * @var	int	$id	Form ID (optional)
     * @access public
     * @return string
     */
    function Form($id = null)
    {
		$GLOBALS['app']->Layout->AddHeadLink('gadgets/Forms/resources/style.css', 'stylesheet', 'text/css');
        $GLOBALS['app']->Layout->AddScriptLink('libraries/js/global2.js');
		$request =& Jaws_Request::getInstance();
        $post = $request->get(array('id'), 'get');

        $GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $post['id'] = $xss->defilter($post['id']);
		$tpl = new Jaws_Template('gadgets/Forms/templates/');
		$tpl->Load('normal.html');

        $model = $GLOBALS['app']->LoadGadget('Forms', 'Model');
        if (is_null($id)) {
			$id = $post['id'];
        }
		$page = $model->GetForm($id);

        if (Jaws_Error::IsError($page)) {
            require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
            return Jaws_HTTPError::Get(404);
        } else {
			if (!empty($page['title'])) {
				$GLOBALS['app']->Layout->SetTitle($xss->parse($page['title']));
			}
			if (!empty($page['description'])) {
					$meta_description = $page['description'];
					$site_name = $GLOBALS['app']->Registry->Get('/config/site_name');
					if (!empty($site_name)) {
						$meta_description = str_replace("__SITE_NAME__", $site_name, $meta_description);
					} else {
						$meta_description = str_replace("__SITE_NAME__", "", $meta_description);
					}
					$site_address = $GLOBALS['app']->Registry->Get('/gadgets/Forms/site_address');
					if (!empty($site_address)) {
						$meta_description = str_replace("__SITE_ADDRESS__", $site_address, $meta_description);
					} else {
						$meta_description = str_replace("__SITE_ADDRESS__", "", $meta_description);
					}
					$site_address2 = $GLOBALS['app']->Registry->Get('/gadgets/Forms/site_address2');
					if (!empty($site_address2)) {
						$meta_description = str_replace("__SITE_ADDRESS2__", $site_address2, $meta_description);
					} else {
						$meta_description = str_replace("__SITE_ADDRESS2__", "", $meta_description);
					}
					$site_office = $GLOBALS['app']->Registry->Get('/gadgets/Forms/site_office');
					if (!empty($site_office)) {
						$meta_description = str_replace("__SITE_OFFICE__", $site_office, $meta_description);
					} else {
						$meta_description = str_replace("__SITE_OFFICE__", "", $meta_description);
					}
					$site_tollfree = $GLOBALS['app']->Registry->Get('/gadgets/Forms/site_tollfree');
					if (!empty($site_tollfree)) {
						$meta_description = str_replace("__SITE_TOLLFREE__", $site_tollfree, $meta_description);
					} else {
						$meta_description = str_replace("__SITE_TOLLFREE__", "", $meta_description);
					}
					$site_cell = $GLOBALS['app']->Registry->Get('/gadgets/Forms/site_cell');
					if (!empty($site_cell)) {
						$meta_description = str_replace("__SITE_CELL__", $site_cell, $meta_description);
					} else {
						$meta_description = str_replace("__SITE_CELL__", "", $meta_description);
					}
					$site_fax = $GLOBALS['app']->Registry->Get('/gadgets/Forms/site_fax');
					if (!empty($site_fax)) {
						$meta_description = str_replace("__SITE_FAX__", $site_fax, $meta_description);
					} else {
						$meta_description = str_replace("__SITE_FAX__", "", $meta_description);
					}
					$meta_description = str_replace("__SITE_EMAIL__", "", $meta_description);
					$GLOBALS['app']->Layout->AddHeadMeta("Description", $xss->parse(strip_tags($meta_description)));
			}
	        $tpl->SetBlock('msgbox-wrapper');
	        $responses = $GLOBALS['app']->Session->PopLastResponse();
	        if ($responses) {
	            foreach ($responses as $msg_id => $response) {
	                $tpl->SetBlock('msgbox-wrapper/msgbox');
	                $tpl->SetVariable('msg-css', $response['css']);
	                $tpl->SetVariable('msg-txt', $response['message']);
	                $tpl->SetVariable('msg-id', $msg_id);
	                $tpl->ParseBlock('msgbox-wrapper/msgbox');
	            }
	        }
	        $tpl->ParseBlock('msgbox-wrapper');
            
            $tpl->SetBlock('form');

            /*
			if ($page['active'] == 'N') {
                $this->SetTitle(_t('FORMS_TITLE_NOT_FOUND'));
				$tpl->SetBlock('form/not_found');
                $tpl->SetVariable('content', _t('FORMS_CONTENT_NOT_FOUND'));
                $tpl->SetVariable('title', _t('FORMS_TITLE_NOT_FOUND'));
                $tpl->ParseBlock('form/not_found');
            } else {
			*/	
				$tpl->SetBlock('form/content');
				$formLayout = $GLOBALS['app']->LoadGadget('Forms', 'LayoutHTML');
				$page_content = "<style>#layout-forms-".$page['id']."-head {display: none;}</style>\n";
				$page_content .= $formLayout->Display($page['id']);
				$tpl->SetVariable('content', $page_content);
				
				$tpl->ParseBlock('form/content');
            //}
        }
        $tpl->ParseBlock('form');

        return $tpl->Get();
    }

    /**
     * Displays an index of available pages.
     *
     * @access public
     * @return string
     */
    function Index()
    {
        $model = $GLOBALS['app']->LoadGadget('Forms', 'Model');
        $pages = $model->GetForms();
        if (Jaws_Error::IsError($pages)) {
            return _t('FORMS_ERROR_INDEX_NOT_LOADED');
        }

		$xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $tpl = new Jaws_Template('gadgets/Forms/templates/');
        $tpl->Load('normal.html');
        $tpl->SetBlock('index');
	    $tpl->SetVariable('actionName', 'Index');
        $tpl->SetVariable('title', _t('FORMS_TITLE_FORM_INDEX'));
        $tpl->SetVariable('link', $GLOBALS['app']->Map->GetURLFor('Forms', 'Index'));
        foreach ($pages as $page) {
            if ($page['ownerid'] == 0) {
                $param = array('id' => $page['id']);
                $link = $GLOBALS['app']->Map->GetURLFor('Forms', 'Form', $param);
                $tpl->SetBlock('index/item');
                $tpl->SetVariable('title', $page['sm_description']);
                $tpl->SetVariable('link',  $link);
                $tpl->ParseBlock('index/item');
            }
        }
        $tpl->ParseBlock('index');

        return $tpl->Get();
    }

    /**
     * Forms can be sent to multiple recipient(s) and after submission, can show custom responses to the user.
     *
     * @category 	feature
     * @access 	public
     * @return 	string
     */
    function Send()
    {
		require_once JAWS_PATH . 'libraries/pear/Validate.php';
		require_once JAWS_PATH . 'include/Jaws/Header.php';
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$model = $GLOBALS['app']->LoadGadget('Forms', 'Model');
		$postData = array();
		$request =& Jaws_Request::getInstance();		
		$get  = $request->getRaw(array('linkid', 'id', 'fvar'), 'get');
		$keys = array(
			'syntacts_special', 'subject', 'subject_override', 'image', 'eName', 'Address', 'City', 'st', 
			'Zip', 'recipient', 'PropName', 'PropImage', 'PropComm',
			'MemEmail', 'DayIn', 'MonthIn', 'YearIn', 'DayOut', 'MonthOut', 
			'YearOut', 'extra_recipient1', 'extra_recipient1', 
			'extra_recipient2', 'extra_recipient3', 'extra_recipient4', 'extra_recipient5'
		);
		$rawkeys = array('submit_content', 'message');
		$postKeys = $request->get($keys, 'post');
		$postRawKeys = $request->getRaw($rawkeys, 'post');
		$error = false;
		foreach($_POST as $key => $value) {
			$postData[$key] = $value;
		}
		foreach($postKeys as $key => $value) {
			$postData[$key] = $value;
		}
		foreach($postRawData as $key => $value) {
			$postData[$key] = $value;
		}
        if (
			(isset($postData['REDIRECT']) && !empty($postData['REDIRECT'])) && 
			(!isset($postData['__REDIRECT__']) || empty($postData['__REDIRECT__']))
		) {
			$postData['__REDIRECT__'] = $postData['REDIRECT'];
		}
		// Save this in session
		$GLOBALS['app']->Session->PushSimpleResponse($postData, 'Forms.Form.'.$get['id'].'.Data');
		
		// TODO: Possibly blacklist the remote IP for evil spamming
		if (!empty($postData['syntacts_special'])) { 
			Jaws_Header::Location($GLOBALS['app']->GetSiteURL());
		}
		$GLOBALS['app']->Registry->LoadFile('Policy');	        
		$_captcha = $GLOBALS['app']->Registry->Get('/gadgets/Policy/captcha');
		if ($_captcha != 'DISABLED') {
			require_once JAWS_PATH . 'gadgets/Policy/captchas/' . $_captcha . '.php';
			$captcha = new $_captcha();
			if (!$captcha->Check()) {
				$error = true;
				$GLOBALS['app']->Session->PushLastResponse(_t('GLOBAL_CAPTCHA_ERROR_DOES_NOT_MATCH'), RESPONSE_ERROR);
				//$GLOBALS['app']->Session->PushSimpleResponse(_t('GLOBAL_CAPTCHA_ERROR_DOES_NOT_MATCH'), 'Forms.Form.'.$get['id'].'.Response');
			}
		}
		if (empty($postData['recipient'])) { 
			$default_recipient = $GLOBALS['app']->Registry->Get('/gadgets/Forms/default_recipient');
			if (!empty($default_recipient)) { 
				$recipient = $default_recipient;
			} else {
				$recipient = $GLOBALS['app']->Registry->Get('/network/site_email');
			}
		} else {
			$recipient = $postData['recipient'];
		}
		$subject = $xss->filter(strip_tags((!empty($postData['subject_override']) ? $postData['subject_override'] : $postData['subject'])));
		$submit_content = Jaws_Gadget::ParseText(html_entity_decode($postData['submit_content']), 'Forms');
		$from_name = '';
		$from_email = '';
		if (strrpos($GLOBALS['app']->GetSiteURL(), "/") > 9) {
			$site_url = substr($GLOBALS['app']->GetSiteURL(), 0, strrpos($GLOBALS['app']->GetSiteURL(), "/"));
		} else {
			$site_url = $GLOBALS['app']->GetSiteURL();		
		}
		if (!empty($get['id'])) {
			if (substr(strtolower($get['id']), 0, 6) == "custom") {
				foreach ($postData as $p => $pv) {
					if (strpos($p, "__REQUIRED__") !== false && trim($pv) == '') {
						$error = true;
						$GLOBALS['app']->Session->PushLastResponse(_t('FORMS_FIELD_REQUIRED', $p), RESPONSE_ERROR);
						//$GLOBALS['app']->Session->PushSimpleResponse(_t('FORMS_FIELD_REQUIRED', $p), 'Forms.Form.'.$get['id'].'.Response');
						break;
					}
				}
			} else {	
				$questions = $model->GetAllPostsOfForm((int)$get['id']);
				if (Jaws_Error::IsError($questions)) {
					return new Jaws_Error( _t('FORMS_ERROR_FORM_NOT_LOADED'), _t('FORMS_NAME'));
				} else {
					foreach ($questions as $question) {
						foreach ($postData as $p => $pv) {
							if (
								($p == $question['title'] || $p == $xss->filter(str_replace('"', "'", $question['title']))) && 
								$question['required'] == "Y" && trim($pv) == ''
							) {
								$error = true;
								$GLOBALS['app']->Session->PushLastResponse(_t('FORMS_FIELD_REQUIRED', $question['title']), RESPONSE_ERROR);
								//$GLOBALS['app']->Session->PushSimpleResponse(_t('FORMS_FIELD_REQUIRED', $question['title']), 'Forms.Form.'.$get['id'].'.Response');
								break;
							}
						}
						if ($error === true) {
							break;
						}
					}
				}
			}
		}

		// Form body
		if (empty($postData['message'])) {	
			
			$tpl = new Jaws_Template('gadgets/Users/templates/');
			$tpl->Load('Mail.html');
			$tpl->SetBlock('mail');
			$tpl->SetBlock('mail/header');
			$tpl->SetVariable('subject', $subject);
			$tpl->ParseBlock('mail/header');
			$tpl->SetVariable('title', "Form Information");
			$tpl->SetBlock('mail/body');
			$tpl->SetBlock('mail/body/table');
			
			// Form table
			$form_questions = array();
			foreach ($postData as $p => $pv) {
				$only_question = false;
				$name_found = false;
				if (!in_array(preg_replace("[^A-Za-z0-9\:\ \,]", '', str_replace('\\', '', $p)), $form_questions)) {
					if (preg_replace("[^A-Za-z0-9]", '', $p) == $p && strlen($p) >= 32) {
						continue;
					}
					$form_questions[] = preg_replace("[^A-Za-z0-9\:\ \,]", '', str_replace('\\', '', $p));
					$p = str_replace("__REQUIRED__", "", $p);
					if ($p == '__FROM_EMAIL__' && !empty($pv)) {
						$p = str_replace("__FROM_EMAIL__", "From E-mail:", $p);
						$from_email = $pv;
					}
					if (empty($from_email) && !empty($pv)) {
						$field = str_replace(array(" ",'%20','&nbsp;'), '_', strtolower($p));
						$field = str_replace(array('-',':'), '', $field);
						if ((strpos($field, "from_email") !== false) || ($field == "email") || (strpos($field, "email_address") !== false) || (strpos($field, "your_email_address") !== false) || (strpos($field, "your_e-mail_address") !== false) || ($field == "e-mail") || (strpos($field, "e-mail_address") !== false)) {
							if (Validate::email($pv, true) === false) {
								$error = true;
								$GLOBALS['app']->Session->PushLastResponse("Please supply a valid e-mail address.", RESPONSE_ERROR);
								//$GLOBALS['app']->Session->PushSimpleResponse("Please supply a valid e-mail address.", 'Forms.Form.'.$get['id'].'.Response');
								break;
							} else {
								$p = "From E-mail:";
								$from_email = $pv;
							}
						}
					}
					if ($p == '__FROM_NAME__' && !empty($pv)) {
						$name_found = true;
						$p = str_replace("__FROM_NAME__", "From:", $p);
						$from_name = $pv;
					}
					if (strpos(strtolower($p), "one of your friends") !== false) {
						$only_question = true;
						if (isset($postData['__FROM_NAME__']) && !empty($postData['__FROM_NAME__'])) {
							$p = str_replace("one of your friends", $postData['__FROM_NAME__'], strtolower($p));
						}
					}
					if (
						$p <> "recipient" && $p <> "syntacts_special" && $p <> "subject" && $p <> "subject_override" && 
						$p <> "image" && $p <> "id" && $p <> "gadget" && $p <> "action" && $p <> "submit_content" && 
						strpos($p, 'EXTRA_RECIPIENT') === false && strtolower($pv) != strtolower($recipient)
					) {
						if ($p == '__MESSAGE__') {
							$p = str_replace("__MESSAGE__", Jaws_Gadget::ParseText($pv, 'Forms'), $p);
							$pv = "&nbsp;";
						} else if ($p == '__REDIRECT__') {
							$p = str_replace("__REDIRECT__", "Click here to visit:", $p);
							$pv = strip_tags(str_replace("&amp;", "&", $pv), '<a>');
						} else {
							$p = preg_replace("[^A-Za-z0-9\:\ \,]", '', str_replace('\\', '', str_replace('_', " ", $p)));
							$pv = $xss->filter(strip_tags(str_replace("&amp;", "&", $pv)));
						}
						
						if ((trim($pv) != '' && $only_question === false) || $only_question === true) {
							$tpl->SetBlock('mail/body/table/row');
							// Form row
							$tpl->SetVariable('name', $p);
							if ($only_question === true) {
								$tpl->SetVariable('td_attr', ' colspan="2" width="100%"');
							} else {
								$tpl->SetVariable('td_attr', ' width="40%"');
								$tpl->SetBlock('mail/body/table/row/answer');
								$tpl->SetVariable('value', $pv);
								$tpl->ParseBlock('mail/body/table/row/answer');
							}
							$tpl->ParseBlock('mail/body/table/row');
						}
					}
				}
			}
			$tpl->ParseBlock('mail/body/table');
			
			// Form footer
			if (empty($postData['subject_override'])) {
				$tpl->SetBlock('mail/body/footer');
				$tpl->SetVariable('footer', '<span>Go To <a href="'.$GLOBALS['app']->GetSiteURL().'" target="_blank">'.$site_url.'</a></span>');
				$tpl->ParseBlock('mail/body/footer');
			}
			$tpl->ParseBlock('mail/body');
			$tpl->ParseBlock('mail');
			$message = $tpl->Get();
		} else {
			$message = $postData['message'];
		}
		if (empty($from_name)) {
			$from_name  = $GLOBALS['app']->Registry->Get('/network/email_name');
		}
		if (empty($from_email)) {
			$from_email = $GLOBALS['app']->Registry->Get('/network/site_email');
		} else {
			$from_email_domain = substr($from_email, strpos($from_email, '@')+1, strlen($from_email));
			if (strtolower($from_email_domain) == 'yahoo.com' || strtolower($from_email_domain) == 'ymail.com') {
				$recipient_domain = substr($recipient, strpos($recipient, '@')+1, strlen($recipient));
				if (strtolower($recipient_domain) == 'yahoo.com' || strtolower($recipient_domain) == 'ymail.com') {
					$pieces = parse_url($GLOBALS['app']->GetFullURL());
					$domain = isset($pieces['host']) ? $pieces['host'] : '';
					if (preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $domain, $regs)) {
						$from_email = 'noreply@'.$regs['domain'];
					}
				}
			}
		}
		if (!empty($postData['subject_override'])) {
			$subject = strip_tags($postData['subject_override']);
		} else {
			$subject = ucfirst(str_replace('http://', '', str_replace('https://', '', $site_url))) . " - " . strip_tags($postData['subject']);
		}
		require_once JAWS_PATH . 'include/Jaws/Mail.php';
		$mail = new Jaws_Mail(true);
        $mail->SetHeaders($recipient, $from_name, $from_email, $subject);
		$mail->AddRecipient($recipient, false, false);
		foreach ($postData as $p => $pv) {
		    $p = str_replace("__REQUIRED__", "", $p);
			$field = str_replace(' ', '_', strtolower($p));
			$field = str_replace('%20', '_', $field);
			$field = str_replace('&nbsp;', '_', $field);
			$field = str_replace(':', '', $field);
			if (
				!empty($pv) && (
					strpos(strtolower($field), 'extra_recipient') !== false || 
					(strpos($field, "from_email") !== false) || ($field == "email") || 
					(strpos($field, "email_address") !== false) || (strpos($field, "your_email_address") !== false) || 
					(strpos($field, "your_e-mail_address") !== false) || ($field == "e-mail") || 
					(strpos($field, "e-mail_address") !== false)
				)
			) {
				if (Validate::email($pv, true) === false) {
					$error = true;
					$GLOBALS['app']->Session->PushLastResponse("Please supply a valid e-mail address.", RESPONSE_ERROR);
					//$GLOBALS['app']->Session->PushSimpleResponse("Please supply a valid e-mail address.", 'Forms.Form.'.$get['id'].'.Response');
					break;
				} else {
					if (strpos(strtolower($field), 'extra_recipient') !== false) {
						$extra_recipient = $pv;
						$mail->AddRecipient($extra_recipient, false, false);
					}
				}
			}
		}
		
		$redirect = '';
		if (substr(strtolower($get['id']), 0, 6) != "custom") {
			if (!empty($get['fvar'])) {
				$redirect = BASE_SCRIPT . '?gadget=Forms&action=Form&id='.$get['id'].'&fvar='.$get['fvar'];
			} else {
				$redirect = BASE_SCRIPT . '?gadget=Forms&action=Form&id='.$get['id'];
			}
		} else {
			if (isset($postData['__REDIRECT__']) && !empty($postData['__REDIRECT__'])) {
				$redirect = urldecode($postData['__REDIRECT__']);
				if (substr(strtolower($redirect), 0, 9) == "<a href='") {
					$inputStr = $redirect;
					$delimeterLeft = "<a href='";
					$delimeterRight = "'>";
					$posLeft=strpos($inputStr, $delimeterLeft);
					$posLeft+=strlen($delimeterLeft);
					$posRight=strpos($inputStr, $delimeterRight, $posLeft);
					$redirect = substr($inputStr, $posLeft, $posRight-$posLeft);
				}
			}
		}
		if ($error === true || $subject == ucfirst(str_replace('http://', '', str_replace('https://', '', $site_url))) . " - ") {
			// FIXME: Log what is trying to be sent, here
			if (substr(strtolower($get['id']), 0, 6) != "custom") {
				Jaws_Header::Location($redirect);
			} else {
				if (!empty($redirect)) {
					Jaws_Header::Location($redirect);
				} else {
					$page_content = "<div><h1>Error</h1></div><div>&nbsp;</div><div>There was a problem while trying to send. <a href=\"javascript:history.go(-1);\">Click here to correct and re-submit the form</a>.</div>";
				}
			}
		}
		if ($error === false) {
			if ($subject != ucfirst(str_replace(array('http://', 'https://'), '', $site_url)) . " - ") {
				$mail->SetBody($message, 'html');
				$mresult = $mail->send();
				if (Jaws_Error::IsError($mresult)) {
					$error = true;
					$GLOBALS['app']->Session->PushLastResponse("Mail Send Error: ".$mresult->GetMessage(), RESPONSE_ERROR);
					//$GLOBALS['app']->Session->PushSimpleResponse("Mail Send Error: ".$mresult->GetMessage(), 'Forms.Form.'.$get['id'].'.Response');
				}
			}
		}
		$tpl = new Jaws_Template('gadgets/Forms/templates/');
		$tpl->Load('normal.html');
		$tpl->SetBlock('form');
		$tpl->SetBlock('form/content');

		if (!isset($page_content)) {
			$page_content = "<div><h1>Thank You!</h1></div><div>&nbsp;</div>";
			if (!empty($submit_content)) {
				$page_content .= $submit_content;
			} else {
				$page_content .= "<div>Your information has been sent to us. <a href=\"javascript:history.go(-1);\">Click here to go back.</a></div>";
			}
		}
		$tpl->SetVariable('content', $page_content);
		$tpl->ParseBlock('form/content');
		$tpl->ParseBlock('form');
		if (!empty($redirect) && strpos($redirect, 'Calendar') !== false) {
			echo $tpl->Get();
			exit;
		} else {
			return $tpl->Get();
		}
		
    }

	/**
     * Displays an RSS 2.0 file with the requested form's questions
     *
     * @access public
     * @return string
     */
    function RSS($id = null)
    {
		header("Content-type: text/xml");
		$output_xml = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>\n"; 
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');

		$request =& Jaws_Request::getInstance();
        $get = $request->get(array('id', 'showcase_id'), 'get');
		  
		if(is_null($id) && !empty($get['id'])) {
			$gid = $get['id'];

	        $model = $GLOBALS['app']->LoadGadget('Forms', 'Model');
			$galleryPosts = $model->GetAllPostsOfForm($gid);
			
			if (!$galleryPosts) {
				if (isset($GLOBALS['log'])) {
					$GLOBALS['log']->Log(JAWS_LOG_ERR, "No questions were found for form ID: $gid");
				}
			} else {
				$output_xml .= "<markers>\n";
				foreach($galleryPosts as $parents) {		            
						$output_xml .=  "	<marker address=\"".$marker_address."\" title=\"".(isset($parents['title']) ? $parents['title'] : 'My Location')."\" url=\"".$marker_url."\" target=\"".$marker_target."\" fs=\"".(isset($parents['marker_font_size']) ? $parents['marker_font_size'] : '10')."\" sfs=\"".(isset($parents['marker_subfont_size']) ? $parents['marker_subfont_size'] : '6')."\" bw=\"".(isset($parents['marker_border_width']) ? $parents['marker_border_width'] : '2')."\" ra=\"".(isset($parents['marker_radius']) ? $parents['marker_radius'] : '9')."\" fc=\"".(isset($parents['marker_font_color']) ? $parents['marker_font_color'] : 'FFFFFF')."\" fg=\"".(isset($parents['marker_foreground']) ? $parents['marker_foreground'] : '666666')."\" bc=\"".(isset($parents['marker_border_color']) ? $parents['marker_border_color'] : 'FFFFFF')."\" hfc=\"".(isset($parents['marker_hover_font_color']) ? $parents['marker_hover_font_color'] : '222222')."\" hfg=\"".(isset($parents['marker_hover_foreground']) ? $parents['marker_hover_foreground'] : 'FFFFFF')."\" hbc=\"".(isset($parents['marker_hover_border_color']) ? $parents['marker_hover_border_color'] : '666666')."\"><![CDATA[ ".$marker_html." ]]></marker>\n";
				}
				$output_xml .= "</markers>\n";
			} 
		} else {
			if (isset($GLOBALS['log'])) {
				$GLOBALS['log']->Log(JAWS_LOG_ERR, "No locations were found for form ID: $gid");
			}
		}
		return $output_xml;
	}
	
    /**
     * Account GetQuickAddForm
     *
     * @access public
     * @return string
     */
    function account_GetQuickAddForm()
    {
		$gadget_admin = $GLOBALS['app']->LoadGadget('Forms', 'AdminHTML');
		$page = $gadget_admin->GetQuickAddForm(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$output_html = str_replace("__JAWS_GADGET__", $page, $users_html->GetAccountHTML('Forms'));
		return $output_html;
    }
    
}

