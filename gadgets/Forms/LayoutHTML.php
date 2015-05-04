<?php
/**
 * Forms Gadget (layout actions in client side)
 *
 * @category   GadgetLayout
 * @package    Forms
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class FormsLayoutHTML
{
    /**
     * Loads layout actions
     *
     * @access private
     */
    function LoadLayoutActions()
    {
        $actions = array();
        $model = $GLOBALS['app']->LoadGadget('Forms', 'Model');
        
		$pages = $model->GetForms();

        if (!Jaws_Error::isError($pages)) {
            foreach ($pages as $page) {
				if ($page['ownerid'] == 0) {
					$actions['Display(' . $page['id'] . ')'] = array(
						'mode' => 'LayoutAction',
						'name' => $page['title'],
						'desc' => _t('FORMS_LAYOUT_DISPLAY_DESCRIPTION')
					);
				}
            }
        }
        return $actions;
    }

	/**
     * Displays a Form.
     *
     * @access public
     * @return string
     */
    function Display($cid = 1, $custom = false, $form_array = null, $question_array = null, $answer_array = null)
    {
		// for boxover on date highlighting
		$GLOBALS['app']->Layout->AddHeadLink('gadgets/Forms/resources/style.css', 'stylesheet', 'text/css');
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $model = $GLOBALS['app']->LoadGadget('Forms', 'Model');
		// send calendarParent records
		$request =& Jaws_Request::getInstance();
		$fetch = array('gadget', 'id', 'action');
		$get  = $request->getRaw($fetch, 'get');
		$page_content = '';
		
		//if on a users home page, show their stuff
		if ($custom === false) {
			$form_array = $model->GetForm($cid);
		}
		
		if (isset($form_array['id']) || $custom === true) {
			$form_data = $GLOBALS['app']->Session->PopSimpleResponse('Forms.Form.'.$form_array['id'].'.Data');
			if ($custom === true) {
				if (isset($form_array['id']) && isset($form_array['sort_order']) && isset($form_array['title']) && 
					isset($form_array['sm_description']) && isset($form_array['description']) && 
					isset($form_array['clause']) && isset($form_array['image']) && isset($form_array['recipient']) && 
					isset($form_array['parent']) && isset($form_array['custom_action']) && isset($form_array['fast_url']) && 
					isset($form_array['active']) && isset($form_array['ownerid']) && isset($form_array['created']) && 
					isset($form_array['updated']) && isset($form_array['submit_content'])) {
					$form_id = $form_array['id'];
					$form_title = $form_array['title'];
				} else {
					return false;
				}
			} else {
				$form_id = $form_array['id'];
				$form_title = $xss->filter($form_array['title']);
			}
			$tpl =& new Jaws_Template('gadgets/Forms/templates/');
	        $tpl->Load('normal.html');

	        $tpl->SetBlock('layout');
			$tpl->SetVariable('actionName', 'Display_' . $form_id . '_');
	        $tpl->SetVariable('layout_title', $form_title);
			$tpl->SetVariable('id', $form_id);
			
			$tpl->SetBlock('layout/formlayout');
		
			$stpl =& new Jaws_Template('gadgets/Forms/templates/');
	        $stpl->Load('Form.html');

	        $stpl->SetBlock('layout');
			$stpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL());
			$stpl->SetVariable('form_id', $form_id);
			$stpl->SetVariable('form_title', $xss->filter(str_replace('"', "'", $form_array['title'])));
	        if (isset($form_array['custom_action']) && !empty($form_array['custom_action'])) {
				$stpl->SetVariable('form_action', $xss->filter($form_array['custom_action']));
	        } else {
				$stpl->SetVariable('form_action', 'index.php?gadget=Forms&action=Send&id='.$form_id);
			}
			$stpl->SetVariable('form_recipient', $xss->filter(str_replace('"', "'", $form_array['recipient'])));
			$stpl->SetVariable('form_description', Jaws_Gadget::ParseText($form_array['description'], 'Forms'));
			$stpl->SetVariable('form_clause', Jaws_Gadget::ParseText($form_array['clause'], 'Forms'));
			$stpl->SetVariable('form_submit_content', Jaws_Gadget::ParseText($form_array['submit_content'], 'Forms'));
			if (isset($form_array['image']) && !empty($form_array['image'])) {
				$image_src = '';
				$form_array['image'] = $xss->filter(strip_tags($form_array['image']));
				if (substr(strtolower($form_array['image']), 0, 4) == "http") {
					if (substr(strtolower($form_array['image']), 0, 7) == "http://") {
						$image_src = explode('http://', $form_array['image']);
						foreach ($image_src as $img_src) {
							if (!empty($img_src)) {
								$image_src = 'http://'.$img_src;
								break;
							}
						}
					} else {
						$image_src = explode('https://', $form_array['image']);
						foreach ($image_src as $img_src) {
							if (!empty($img_src)) {
								$image_src = 'https://'.$img_src;
								break;
							}
						}
					}
					if (strpos(strtolower($image_src), 'data/files/') !== false) {
						$image_src = 'image_thumb.php?uri='.urlencode($image_src);
					}
				} else {
					if (file_exists(JAWS_DATA . 'files'.$form_array['image'])) {
						$image_src = $GLOBALS['app']->getDataURL() . 'files'.$form_array['image'];
					}
				}
				if (!empty($image_src)) {
					$stpl->SetBlock('layout/image');
					$stpl->SetVariable('form_image', $image_src);
					$stpl->ParseBlock('layout/image');
				}
			}
	        if (isset($form_array['sm_description']) && !empty($form_array['sm_description'])) {
				$stpl->SetBlock('layout/summary');
				$stpl->SetVariable('form_summary', $xss->filter(str_replace('"', "'", $form_array['sm_description'])));
				$stpl->ParseBlock('layout/summary');
			}
			
			if ($custom === false) {
				$question_array = $model->GetAllPostsOfForm($form_id);
				$answer_array = $model->GetAllAnswersOfForm($form_id);
			}
			
			if (Jaws_Error::IsError($form_array) || !is_array($form_array) || is_null($form_array)) {
				$page_content = _t('FORMS_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403'))."\n";
			}
			if (Jaws_Error::IsError($question_array) || !is_array($question_array) || is_null($question_array)) {
				$page_content = _t('FORMS_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403'))."\n";
			}
			if (Jaws_Error::IsError($answer_array) || !is_array($answer_array) || is_null($answer_array)) {
				$page_content = _t('FORMS_ERROR_ASPPAGE_NOT_RETRIEVED', _t('GLOBAL_HTTP_ERROR_CONTENT_403'))."\n";
			}
			foreach($question_array as $post) {		
				$stpl->SetBlock('layout/question');
				
				$post['description'] = Jaws_Gadget::ParseText($post['description'], 'Forms');
				$post['title'] = $xss->filter(str_replace('"', "'", $post['title']));
				$post_title = str_replace(array('__EXTRA_RECIPIENT__', '__REQUIRED__'), '', $post['title']);
				$post_title = str_replace('__FROM_NAME__', "From Name", $post_title);
				$post_title = str_replace('__FROM_EMAIL__', "From Email", $post_title);
				$post['itype'] = $xss->filter($post['itype']);
				$extra = false;
				if (strpos($post['title'], '__EXTRA_RECIPIENT__') !== false) {
					$post['title'] = str_replace('__EXTRA_RECIPIENT__', '', $post['title']);
					$extra = true;
				}
				$required = false;
				if (strpos($post['title'], '__REQUIRED__') !== false) {
					$post['title'] = str_replace('__REQUIRED__', '', $post['title']);
					$required = true;
				}
				$fromname = false;
				if (strpos($post['title'], '__FROM_NAME__') !== false) {
					$post['title'] = str_replace('__FROM_NAME__', '', $post['title']);
					$fromname = true;
				}
				$fromemail = false;
				if (strpos($post['title'], '__FROM_EMAIL__') !== false) {
					$post['title'] = str_replace('__FROM_EMAIL__', '', $post['title']);
					$fromemail = true;
				}
				$post['title'] = ereg_replace("[^A-Za-z0-9\:\ ]", '', str_replace("\\", '', $post['title']));
				if (strlen($post['title']) > 30) {				
					$post['title'] = substr($post['title'], 0, 30);
				}
				$post['title'] .= ($extra === true ? '__EXTRA_RECIPIENT__' : '');
				$post['title'] .= ($required === true ? '__REQUIRED__' : '');
				$post['title'] .= ($fromname === true ? '__FROM_NAME__' : '');
				$post['title'] .= ($fromemail === true ? '__FROM_EMAIL__' : '');
				$safe_title = str_replace(" ", '_', $post['title']);
				$post_answers = array();
				foreach ($answer_array as $answer) {
					if ($answer['linkid'] == $post['id']) {
						$post_answers[] = $answer;
					}
				}
			
				if (!empty($post['title']) && $post['itype'] != 'HiddenField') {
					$stpl->SetBlock('layout/question/shown');
					$stpl->SetVariable('current_question', $post_title);
					$stpl->SetVariable('question_name', $post['title']);
					if ($post['required'] == 'Y' || strpos($post['title'], '__REQUIRED__') !== false) {	
						$stpl->SetBlock('layout/question/shown/required');
						$stpl->ParseBlock('layout/question/shown/required');
					}
					switch ($post['itype']) {
						case 'SelectBox':
							$stpl->SetBlock('layout/question/shown/selectbox');
							$stpl->SetVariable('question_name', $post['title']);
							foreach ($post_answers as $p_answer) {
								$stpl->SetBlock('layout/question/shown/selectbox/selectbox_option');
								$value_title = $xss->filter(str_replace('"', "'", $p_answer['title']));
								$selected = '';
								if (isset($form_data[$safe_title]) && !empty($form_data[$safe_title]) && $form_data[$safe_title] == $value_title) {
									$selected = ' selected="selected"';
								}
								$stpl->SetVariable('selected', $selected);
								$stpl->SetVariable('value_title', $value_title);
								$stpl->ParseBlock('layout/question/shown/selectbox/selectbox_option');
							}
							$stpl->ParseBlock('layout/question/shown/selectbox');
							break;
						case 'RadioBtn':
							$stpl->SetBlock('layout/question/shown/radiobtn');
							foreach ($post_answers as $p_answer) {
								$stpl->SetBlock('layout/question/shown/radiobtn/radiobtn_option');
								$value_title = $xss->filter(str_replace('"', "'", $p_answer['title']));
								$checked = '';
								if (isset($form_data[$safe_title]) && !empty($form_data[$safe_title]) && $form_data[$safe_title] == $value_title) {
									$checked = ' checked="checked"';
								}
								$stpl->SetVariable('checked', $checked);
								$stpl->SetVariable('value_title', $value_title);
								$stpl->SetVariable('question_name', $post['title']);
								$stpl->ParseBlock('layout/question/shown/radiobtn/radiobtn_option');
							}
							$stpl->ParseBlock('layout/question/shown/radiobtn');
							break;
						case 'CheckBox':
							$stpl->SetBlock('layout/question/shown/checkbox');
							foreach ($post_answers as $p_answer) {
								$stpl->SetBlock('layout/question/shown/checkbox/checkbox_option');
								$value_title = $xss->filter(str_replace('"', "'", $p_answer['title']));
								$checked = '';
								if (
									(isset($form_data[$safe_title]) && !empty($form_data[$safe_title]) && $form_data[$safe_title] == $value_title) ||
									(is_array($form_data[$safe_title]) && in_array($value_title, $form_data[$safe_title]))
								) {
									$checked = ' checked="checked"';
								}
								$stpl->SetVariable('checked', $checked);
								$stpl->SetVariable('value_title', $value_title);
								$stpl->SetVariable('question_name', $post['title']);
								$stpl->ParseBlock('layout/question/shown/checkbox/checkbox_option');
							}
							$stpl->ParseBlock('layout/question/shown/checkbox');
							break;
						case 'TextBox':
							$stpl->SetBlock('layout/question/shown/textbox');
							$value_title = $xss->filter(str_replace('"', "'", $p_answer['title']));
							$value_data = '';
							if (isset($form_data[$safe_title]) && !empty($form_data[$safe_title])) {
								$value_data = $form_data[$safe_title];
							}
							$stpl->SetVariable('value_data', $value_data);
							$stpl->SetVariable('question_name', $post['title']);
							$stpl->ParseBlock('layout/question/shown/textbox');
							break;
						case 'TextArea':
							$stpl->SetBlock('layout/question/shown/textarea');
							$value_data = '';
							if (isset($form_data[$safe_title]) && !empty($form_data[$safe_title])) {
								$value_data = $form_data[$safe_title];
							}
							$stpl->SetVariable('value_data', $value_data);
							$stpl->SetVariable('question_name', $post['title']);
							$stpl->ParseBlock('layout/question/shown/textarea');
							break;
					}
					$stpl->ParseBlock('layout/question/shown');
				} else if ($post['itype'] == 'HiddenField') {
					$stpl->SetBlock('layout/question/hidden');
					foreach ($post_answers as $p_answer) {
						$stpl->SetBlock('layout/question/hidden/hidden_option');
						$stpl->SetVariable('value_title', str_replace('"', "'", $p_answer['title']));
						$stpl->SetVariable('question_name', $post['title']);
						$stpl->ParseBlock('layout/question/hidden/hidden_option');
					}
					$stpl->ParseBlock('layout/question/hidden');
				}
				$stpl->ParseBlock('layout/question');
				
				if (
					$post['itype'] != 'HiddenField' && $post['itype'] != 'RadioBtn' && 
					($post['required'] == 'Y' || strpos($post['title'], '__REQUIRED__') !== false)
				) {	
					$stpl->SetBlock('layout/required_script');
					$stpl->SetVariable('question_name', $post['title']);
					if ($post['itype'] == 'TextBox' || $post['itype'] == 'TextArea') {
						$stpl->SetBlock('layout/required_script/input');
						$stpl->SetVariable('current_question', str_replace("'", "\'", $post_title));
						$stpl->ParseBlock('layout/required_script/input');
					} else {
						$stpl->SetBlock('layout/required_script/select');
						$stpl->SetVariable('current_question', str_replace("'", "\'", $post_title));
						$stpl->ParseBlock('layout/required_script/select');
					}
					$stpl->ParseBlock('layout/required_script');
				}
				
			}
			
			// Captcha
			$GLOBALS['app']->Registry->LoadFile('Policy');
			$_captcha = $GLOBALS['app']->Registry->Get('/gadgets/Policy/captcha');
			if ($_captcha != 'DISABLED') {
				require_once JAWS_PATH . 'gadgets/Policy/captchas/' . $_captcha . '.php';
				$captcha = new $_captcha();
				$captchaRes = $captcha->Get();
				$stpl->SetBlock('layout/captcha');
				$stpl->SetVariable('lbl_captcha', _t('GLOBAL_CAPTCHA_CODE'));
				$stpl->SetVariable('captcha', $captchaRes['captcha']->Get());
				if (!empty($captchaRes['entry'])) {
					$stpl->SetVariable('captchavalue', $captchaRes['entry']->Get());
				}
				$stpl->SetVariable('captcha_msg', _t('GLOBAL_CAPTCHA_CODE_DESC'));
				$stpl->ParseBlock('layout/captcha');
			}
			
			$stpl->ParseBlock('layout');
			
			if (empty($page_content)) {
				$page_content = $stpl->Get();
				if ($custom === false) {
					$site_name = $GLOBALS['app']->Registry->Get('/config/site_name');
					if (!empty($site_name)) {
						$page_content = str_replace("__SITE_NAME__", "<tr><td width=\"27%\" valign=\"top\">".$site_name."</td><td width=\"73%\" valign=\"top\">&nbsp;</td></tr>", $page_content);
					} else {
						$page_content = str_replace("__SITE_NAME__", "", $page_content);
					}
					$site_address = $GLOBALS['app']->Registry->Get('/gadgets/Forms/site_address');
					if (!empty($site_address)) {
						$page_content = str_replace("__SITE_ADDRESS__", "<tr><td width=\"27%\" valign=\"top\">Address:</td><td width=\"73%\" valign=\"top\">".$site_address.".</td></tr>", $page_content);
					} else {
						$page_content = str_replace("__SITE_ADDRESS__", "", $page_content);
					}
					$site_address2 = $GLOBALS['app']->Registry->Get('/gadgets/Forms/site_address2');
					if (!empty($site_address2)) {
						$page_content = str_replace("__SITE_ADDRESS2__", "<tr><td width=\"27%\" valign=\"top\">Address (cont'd):</td><td width=\"73%\" valign=\"top\">".$site_address2."</td></tr>", $page_content);
					} else {
						$page_content = str_replace("__SITE_ADDRESS2__", "", $page_content);
					}
					$site_office = $GLOBALS['app']->Registry->Get('/gadgets/Forms/site_office');
					if (!empty($site_office)) {
						$page_content = str_replace("__SITE_OFFICE__", "<tr><td width=\"27%\" valign=\"top\">Office Phone:</td><td width=\"73%\" valign=\"top\">".$site_office."</td></tr>", $page_content);
					} else {
						$page_content = str_replace("__SITE_OFFICE__", "", $page_content);
					}
					$site_tollfree = $GLOBALS['app']->Registry->Get('/gadgets/Forms/site_tollfree');
					if (!empty($site_tollfree)) {
						$page_content = str_replace("__SITE_TOLLFREE__", "<tr><td width=\"27%\" valign=\"top\">Toll-Free:</td><td width=\"73%\" valign=\"top\">".$site_tollfree."</td></tr>", $page_content);
					} else {
						$page_content = str_replace("__SITE_TOLLFREE__", "", $page_content);
					}
					$site_cell = $GLOBALS['app']->Registry->Get('/gadgets/Forms/site_cell');
					if (!empty($site_cell)) {
						$page_content = str_replace("__SITE_CELL__", "<tr><td width=\"27%\" valign=\"top\">Cell/Direct Phone:</td><td width=\"73%\" valign=\"top\">".$site_cell."</td></tr>", $page_content);
					} else {
						$page_content = str_replace("__SITE_CELL__", "", $page_content);
					}
					$site_fax = $GLOBALS['app']->Registry->Get('/gadgets/Forms/site_fax');
					if (!empty($site_fax)) {
						$page_content = str_replace("__SITE_FAX__", "<tr><td width=\"27%\" valign=\"top\">Fax:</td><td width=\"73%\" valign=\"top\">".$site_fax."</td></tr>", $page_content);
					} else {
						$page_content = str_replace("__SITE_FAX__", "", $page_content);
					}
					/*
					$site_email = $GLOBALS['app']->Registry->Get('/network/site_email');
					if (!empty($site_email)) {
						$GLOBALS['app']->Registry->LoadFile('Policy');
						$_obfuscator = $GLOBALS['app']->Registry->Get('/gadgets/Policy/obfuscator');
						if ($_obfuscator != 'DISABLED'){
							require_once JAWS_PATH . 'gadgets/Policy/obfuscators/' . $_obfuscator . '.php';
							$obf = new $_obfuscator();
							$site_email = $obf->Get($site_email, _t('GLOBAL_EMAIL'));
						}
						$page_content = str_replace("__SITE_EMAIL__", "<tr><td width=\"27%\" valign=\"top\">E-mail:</td><td width=\"73%\" valign=\"top\">".$site_email."</td></tr>", $page_content);
					} else {
					*/
						$page_content = str_replace("__SITE_EMAIL__", "", $page_content);
					//}
				}
			}

			$tpl->SetVariable('content', $page_content);
			$tpl->ParseBlock('layout/formlayout');
			
	        $tpl->ParseBlock('layout');

	        $output_html = $tpl->Get();
			/*
			$output_html = str_replace("<script type='text/javascript' src='http://free.7host.com/includes/adsense.js'></script>", '', $output_html);
			$output_html = str_replace('<a href="http://www.7host.com" style="color:#0000ff; font-size:12px;" target="_blank">Free Web Hosting</a>', '', $output_html);
			$output_html = str_replace('<a href="http://www.vitamincenter.it" target="_blank"><img src="http://free.7host.com/bannervc.gif" border="0"></a><br>', '', $output_html);
			$output_html = str_replace('<p align="center"><font face="Arial, Helvetica, sans-serif" size=6><a href="http://www.7host.com">7Host.com Free Web Hosting</a></font></p>', '', $output_html);
			$output_html = str_replace('Error: Unable to read footer file.', '', $output_html);
			if (strpos($output_html, '<!-- 7host begin code -->') !== false) {
				$output_html = preg_replace('|\<\!-- 7host begin code --\>.*?\<!-- 7host end code --\>|siu','',$output_html);
			}	
			*/
	        return $output_html;
		}
		
    }
}