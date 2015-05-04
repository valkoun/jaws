<?php
/**
 * Social Gadget Admin
 *
 * @category   GadgetAdmin
 * @package    Social
 * @license    http://www.gnu.org/copyleft/gpl.html
 */
class SocialAdminHTML extends Jaws_GadgetHTML
{
    /**
     * Main Constructor
     *
     * @access      public
     */
    function SocialAdminHTML()
    {
        $this->Init('Social');
    }

    /**
     * Builds the menubar
     *
     * @access       public
     * @param        string  $selected Selected action
     * @return       string  The html menubar
     */
    function MenuBar($selected)
    {
        $actions = array('Admin','ImportEmails');
        if (!in_array($selected, $actions)) {
            $selected = 'Admin';
        }

        require_once JAWS_PATH . 'include/Jaws/Widgets/Menubar.php';
        $menubar = new Jaws_Widgets_Menubar();
		$menubar->AddOption('Admin', _t('SOCIAL_MENU_ADMIN'),
							'admin.php?gadget=Social&amp;action=Admin', STOCK_PREFERENCES);
        /*
		if ($GLOBALS['app']->Session->GetPermission('Social', 'UpdateProperties')) {
            $menubar->AddOption('ImportEmails', _t('SOCIAL_MENU_IMPORTEMAILS'),
                                'admin.php?gadget=Social&amp;action=ImportEmails', STOCK_OPEN);
        }
		*/

        $menubar->Activate($selected);

        return $menubar->Get();
    }
	
    /**
     * Admin of Gadget
     *
     * @access  public
     * @return  string HTML content of administration
     */
    function Admin($account = false)
    {
        if ($account === false) {
			$this->CheckPermission('default');
			$this->CheckPermission('UpdateProperties');
			$this->AjaxMe('script.js');
			$request =& Jaws_Request::getInstance();
			$get_iconset = $request->get('iconset', 'get');
						
			if (!empty($get_iconset)){
				$GLOBALS['app']->Registry->Set("/gadgets/Social/iconset",strtolower($get_iconset));
				$GLOBALS['app']->Registry->Commit('Social');
			}
		} else {
			$this->AjaxMe('client_script.js');
		}
		$model = $GLOBALS['app']->LoadGadget('Social', 'Model');
		$websites = $model->getSocialWebsites();
		
        $tpl = new Jaws_Template('gadgets/Social/templates/');
        $tpl->Load('AdminSocial.html');
        
		$tpl->SetBlock('social');
		
		//Menu bar
        $tpl->SetVariable('menubar', $this->MenuBar('Admin'));

		$form =& Piwi::CreateWidget('Form', BASE_SCRIPT, 'post');
		$form->Add(Piwi::CreateWidget('HiddenEntry', 'gadget', 'Social'));
		$form->Add(Piwi::CreateWidget('HiddenEntry', 'action', 'Admin'));
        
		$preferences =& Piwi::CreateWidget('VBox');
        $preferences->SetId('social');

        include_once JAWS_PATH . 'include/Jaws/Widgets/FieldSet.php';
        $fieldset = new Jaws_Widgets_FieldSet(_t('SOCIAL_WEBLIST'));
        $fieldset->SetDirection('vertical');

		$iconset = $GLOBALS['app']->Registry->Get('/gadgets/Social/iconset');
		
		if ($account === false) {
			$checked = $GLOBALS['app']->Registry->Get('/gadgets/Social/webs');
			$checked = explode(",",$checked);
			$social_exists = $model->GetSocialOfUserID(0);
		} else {
			$checked = array();
			$social_exists = $model->GetSocialOfUserID($GLOBALS['app']->Session->GetAttribute('user_id'));
		}
		
		if (Jaws_Error::IsError($social_exists)) {
			//$GLOBALS['app']->Session->PushLastResponse($social_exists->GetMessage(), RESPONSE_ERROR);
			return $social_exists;
		}
		
        foreach($websites as $key => $value){
			$existing = array();
			foreach ($social_exists as $exist) {
				if ($exist['social'] == $key) {
					$existing = $exist;
					break;
				}
			}
			if ($account === true && isset($existing['active']) && $existing['active'] == 'Y') {
				$checked[] = $key;
			}
			$website_fieldset = new Jaws_Widgets_FieldSet($value["realname"]);
			$website_fieldset->SetDirection('vertical');
			$checks =& Piwi::CreateWidget('CheckButtons', 'social_webs_'.$key,'vertical');
	        $checks->AddOption("<img src='". $GLOBALS['app']->GetJawsURL() . "/gadgets/Social/images/".$iconset."/".$key.".png' /> ".$value["realname"], $key, null, in_array($key,$checked));
			$website_fieldset->Add($checks);
			if (isset($value['url'])) {
				$social_urlField =& Piwi::CreateWidget('Entry', 'social_urls_'.$key, (isset($existing['social_url']) && !empty($existing['social_url']) ? $existing['social_url'] : ''));
				$social_urlField->SetTitle(_t('SOCIAL_SOCIAL_URL', $value['realname']));
				$website_fieldset->Add($social_urlField);
			}
			if (isset($value['id'])) {
				$social_idField =& Piwi::CreateWidget('Entry', 'social_ids_'.$key, (isset($existing['social_id']) && !empty($existing['social_id']) ? $existing['social_id'] : ''));
				$social_idField->SetTitle($value["id"]);
				$website_fieldset->Add($social_idField);
			}
			if (isset($value['id2'])) {
				$social_id2Field =& Piwi::CreateWidget('Entry', 'social_id2s_'.$key, (isset($existing['social_id2']) && !empty($existing['social_id2']) ? $existing['social_id2'] : ''));
				$social_id2Field->SetTitle($value["id2"]);
				$website_fieldset->Add($social_id2Field);
			}
			if (isset($value['id3'])) {
				$social_id3Field =& Piwi::CreateWidget('Entry', 'social_id3s_'.$key, (isset($existing['social_id3']) && !empty($existing['social_id3']) ? $existing['social_id3'] : ''));
				$social_id3Field->SetTitle($value["id3"]);
				$website_fieldset->Add($social_id3Field);
			}
			$fieldset->Add($website_fieldset);
        }
        
        $submit =& Piwi::CreateWidget('Button', 'save', _t('GLOBAL_UPDATE', _t('GLOBAL_SETTINGS')), STOCK_SAVE);
        $submit->SetStyle(_t('GLOBAL_LANG_DIRECTION')=='rtl'?'float: left;' : 'float: right;');
        $submit->AddEvent(ON_CLICK, 'updateSocial(this.form);');

        $preferences->Add($fieldset);
        $preferences->Add($submit);
		$form->Add($preferences);
        $tpl->SetVariable('social_config', $form->Get());

        if ($account === false) {
			$iconsetvb =& Piwi::CreateWidget('VBox');
			$iconsetvb->SetId('iconsetvb');
			$iconsetsl = new Jaws_Widgets_FieldSet(_t('SOCIAL_ICONSETS'));
			$iconsetsl->SetDirection('vertical');
			$iconsetscombo =& Piwi::CreateWidget('Combo', 'iconsets_combo');
			$iconsetscombo->SetID('iconsets_combo');

			foreach(glob(JAWS_PATH ."gadgets/Social/images/*",GLOB_ONLYDIR) as $dir){
				$value = str_replace(JAWS_PATH . "gadgets/Social/images/","",$dir);
				$iconsetscombo->AddOption(ucfirst($value),$value);
			}
			$iconsetscombo->SetDefault($iconset);
			$iconsetscombo->AddEvent(ON_CHANGE, "document.location='admin.php?gadget=Social&iconset='+this.value");
			
			$iconsetsl->Add($iconsetscombo);
			$iconsetvb->Add($iconsetsl);
			$tpl->SetVariable('iconset_config', $iconsetvb->Get());
		}
		
        $tpl->ParseBlock('social');

        return $tpl->Get();
    }
	
    /**
     * Import Email list (select type (RSS, Tab-Delimited)
     *
     * @access public
     * @return XHTML string
     */
    function ImportEmails()
    {
		require_once JAWS_PATH . 'include/Jaws/Header.php';
		Jaws_Header::Location(BASE_SCRIPT . '?gadget=Social');
		
		$GLOBALS['app']->Layout->AddHeadLink('gadgets/ControlPanel/resources/style.css', 'stylesheet', 'text/css');
        $this->AjaxMe('script.js');
        $tpl = new Jaws_Template('gadgets/Social/templates/');
        $tpl->Load('ImportEmails.html');
        $tpl->SetBlock('Properties');

		include_once JAWS_PATH . 'include/Jaws/Widgets/FieldSet.php';
        
		$form =& Piwi::CreateWidget('Form', BASE_SCRIPT, 'post');
        $form->Add(Piwi::CreateWidget('HiddenEntry', 'gadget', 'Social'));
        $form->Add(Piwi::CreateWidget('HiddenEntry', 'action', 'ImportFile'));

		$payment_gateway =& Piwi::CreateWidget('Combo', 'inventory_type');
		$payment_gateway->SetTitle(_t('SOCIAL_IMPORTEMAILS_TYPE'));
		$payment_gateway->AddOption(_t('SOCIAL_IMPORTEMAILS_TABDELIMITED'), 'TabDelimited');
		//$payment_gateway->AddOption(_t('SOCIAL_IMPORTEMAILS_COMMASEPARATED'), 'CSV');
		//$payment_gateway->AddOption(_t('SOCIAL_IMPORTEMAILS_RSSFEED'), 'RSS');
		$payment_gateway->SetDefault('TabDelimited');

		$gateway_fieldset = new Jaws_Widgets_FieldSet('');
		$gateway_fieldset->SetTitle('vertical');
		$gateway_fieldset->Add($payment_gateway);
		
		// Image
		$xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$image = '';
		$image_src = $GLOBALS['app']->getDataURL() . 'files'.$xss->filter($image);
		$image_preview = '';
		if ($image != '' && file_exists($image_src)) { 
			$image_preview .= "<br /><img border=\"0\" src=\"".$image_src."\" width=\"80\"".(strtolower(substr($image, -3)) == 'gif' || strtolower(substr($image, -3)) == 'png' || strtolower(substr($image, -3)) == 'bmp' ? ' height="80"' : '')." align=\"left\" style=\"padding: 5px;\" />";
		}
		$imageScript = "<script type=\"text/javascript\">Event.observe(window, \"load\",function(){addFileToPost('Users', 'NULL', 'NULL', 'main_image', 'inventory_file', 1, 500, 34, '', false, '', 'txt,csv');});</script>";
		$imageHidden =& Piwi::CreateWidget('HiddenEntry', 'inventory_file', $image);
		$imageButton = "&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"button\" value=\"Uploaded Files\" onclick=\"openUploadWindow('inventory_file')\" style=\"font-family: Arial; font-size: 10pt; font-weight: bold\" />";
		$imageEntry =& Piwi::CreateWidget('UploadEntry', 'inventory_file', _t('SOCIAL_IMPORTEMAILS_FILE'), $image_preview, $imageScript, $imageHidden->Get(), $imageButton);
		
		$gateway_fieldset->Add($imageEntry);
		
		$form->Add($gateway_fieldset);
				
		$buttons =& Piwi::CreateWidget('HBox');
		$buttons->SetStyle(_t('GLOBAL_LANG_DIRECTION')=='rtl'?'float: left;' : 'float: right;');

		$save =& Piwi::CreateWidget('Button', 'save', _t('GLOBAL_SAVE'), STOCK_SAVE);
		$save->SetStyle(_t('GLOBAL_LANG_DIRECTION')=='rtl'?'float: left;' : 'float: right;');
		$save->SetSubmit();

		$buttons->Add($save);
		$form->Add($buttons);

		$tpl->SetVariable('form', $form->Get());
        $tpl->SetVariable('menubar', $this->MenuBar('ImportEmails'));

        $tpl->ParseBlock('Properties');

        return $tpl->Get();
		
	}
	
    /**
     * Import inventory list (select type (RSS, Tab-Delimited)
     *
     * @access public
     * @return XHTML string
     */
    function ImportFile()
    {
		$request =& Jaws_Request::getInstance();
		$file = $request->get('inventory_file', 'post');
		$type = $request->get('inventory_type', 'post');
		$output_html = '<script src="http://yui.yahooapis.com/2.8.0r4/build/yahoo/yahoo-min.js"></script>';
		$output_html .= '<script src="http://yui.yahooapis.com/2.8.0r4/build/event/event-min.js"></script>';
		$output_html .= '<script src="http://yui.yahooapis.com/2.8.0r4/build/connection/connection_core-min.js"></script>';

		$output_html .= '<script>var spawnCallback = {';
		$output_html .= 'success: function(o) {';
		$output_html .= '},';
		$output_html .= 'failure: function(o) {';
		$output_html .= '},';
		$output_html .= 'timeout: 2000';
		$output_html .= '};';

		$output_html .= 'function spawnProcess() {';
		$output_html .= 'YAHOO.util.Connect.asyncRequest(\'GET\',\''.$GLOBALS['app']->getSiteURL().'/index.php?gadget=Social&action=UpdateRSSEmails&num=1&file='.urlencode($file).'&type='.$type.'&ua=N\',spawnCallback);';
		$output_html .= '}';
		$output_html .= 'spawnProcess(); location.href = "admin.php?gadget=Social";</script>';
		//exec ("/usr/local/bin/php /homepages/40/d298423861/htdocs/cli.php --id=$cmd >/dev/null &");
		//backgroundPost($GLOBALS['app']->getSiteURL().'/index.php?gadget=Properties&action=UpdateRSSProperties&id='.$cmd);
		return $output_html;
	}
	
}
