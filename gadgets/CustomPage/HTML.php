<?php
/**
 * CustomPage Gadget
 *
 * @category   Gadget
 * @package    CustomPage
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class CustomPageHTML extends Jaws_GadgetHTML
{
    var $_Name = 'CustomPage';
    /**
     * Constructor
     *
     * @access public
     */
    function CustomPageHTML()
    {
        $this->Init('CustomPage');
    }

    /**
     * Excutes the default action, currently displaying the default page.
     *
     * @access public
     * @return string
     */
    function DefaultAction()
    {
        return $this->Page($GLOBALS['app']->Registry->Get('/gadgets/CustomPage/default_page'));
    }

    /**
     * Displays an individual page.
     *
     * @param	int 	$id 	Page ID (optional)
     * @param	boolean 	$embedded 	Embedded mode
     * @param	string 	$referer 	Embedding referer
     * @param	boolean 	$blog 	Blog mode
     * @param	array 	$replacements 	Array of template replacement variables (key => value)  
     * @access 	public
     * @return 	string
     */
    function Page($id = null, $embedded = false, $referer = null, $blog = false, $replacements = array())
    {
        $model = $GLOBALS['app']->LoadGadget('CustomPage', 'Model');
		$request =& Jaws_Request::getInstance();
        $get = $request->get(array('id'), 'get');
		if (is_null($id)) {
			$id = $get['id'];
        }
		$page = $model->GetPage($id);
		if (Jaws_Error::IsError($page) || !isset($page['id']) || empty($page['id'])) {
			require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
            return Jaws_HTTPError::Get(404);
        } else {
			// TODO: More advanced control over embedding (active/inactive) pages
			if (!empty($get['id']) && $page['active'] == 'N' && $embedded === false && is_null($referer)) {
				require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
				return Jaws_HTTPError::Get(404);
			}

			if ($page['active'] == 'N' && $embedded == false) {
				require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
				return Jaws_HTTPError::Get(404);
            } else {
				$layoutHTML = $GLOBALS['app']->LoadGadget('CustomPage', 'LayoutHTML');
				return $layoutHTML->Page($page['id'], $embedded, $referer, $blog, $replacements);
			}
        }
	}

    /**
     * Displays an index of available pages.
     *
     * @access public
     * @return string
     */
    function Index()
    {
        $layoutGadget = $GLOBALS['app']->LoadGadget('CustomPage', 'LayoutHTML');
        return $layoutGadget->Display();
    }

    /**
     * Displays an XML sitemap in Google's format
     *
     * @access public
     * @return string
     */
    function GoogleSitemap()
    {
        $sitemap = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n"; 
		$sitemap .= "	<urlset xmlns=\"http://www.google.com/schemas/sitemap/0.84\">\n";
		//$sitemap .= "		<url>\n";
		//$sitemap .= "			<loc>".$menu['fast_url']."</loc>\n"; 
		//$sitemap .= "			<lastmod>".$menu['updated']."</lastmod>\n";
		//$sitemap .= "			<changefreq>weekly</changefreq>\n";
		//$sitemap .= "			<priority>".$priority."</priority>\n"; 
		//$sitemap .= "		</url>\n";
		$sitemap .= "	</urlset>\n";
		
        return $sitemap;
    }

    /**
     * Displays the Syntacts Update form.
     *
     * @access public
     * @return string
     */
    function SyntactsUpdates()
    {
        $GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		
		$request =& Jaws_Request::getInstance();
        $post = $request->get(array('id', 'd', 'u', 'a'), 'get');
        $post['id'] = $xss->defilter($post['id']);
        $post['d'] = $xss->defilter($post['d']);
        $post['u'] = $xss->defilter($post['u']);
        $post['a'] = $xss->defilter($post['a']);
		
		// Get WHMCS session UID and current eVision domain we're looking at
		$domain = $post['d'];
		$user = $post['u'];
		$admin = $post['a'];
		
		$error = '';		
									
		include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Template.php';
		$tpl = new Jaws_Template('gadgets/CustomPage/templates/');
		$tpl->Load('SyntactsUpdates.html');

		$tpl->SetBlock('form');
		
		// TODO: Show available gadgets, etc. on form that user has access to for current eVision domain
		
		$tpl->SetVariable('site_url', $GLOBALS['app']->GetSiteURL());
		$tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . '/');
		//$tpl->SetVariable('DPATH', JAWS_DPATH);
		$tpl->ParseBlock('form');
		return $tpl->Get();
	}
	
    /**
     * Embed pages in external sites.
     *
     * @category 	feature
     * @access 	public
     * @return 	string
     */
    function EmbedCustomPage()
    {
        //$xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$request =& Jaws_Request::getInstance();
        $get = $request->get(array('id', 'mode', 'uid', 'referer', 'css'), 'get');
		
		$output_html = "";
		if (isset($get['id']) && (isset($get['referer']) || $GLOBALS['app']->Session->GetAttribute('gadget_referer'))) {
			$layoutHTML = $GLOBALS['app']->LoadGadget('CustomPage', 'LayoutHTML');
			$display_id = md5($this->_Name.$get['id']);
			$referer = (isset($get['referer']) ? $get['referer'] : $GLOBALS['app']->Session->GetAttribute('gadget_referer'));
			if ($get['mode'] == 'list') {
				$output_html .= "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" style=\"background: url();\">\n";
				$output_html .= " <head>\n";
				$output_html .= "  <title>Custom Page</title>\n";
				$output_html .= "  <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n";
				$theme = $GLOBALS['app']->Registry->Get('/config/theme');
				$themeHREF = (strpos($theme, 'http://') !== false ? $theme : $GLOBALS['app']->getDataURL('', true) . "themes/" . $theme);
				$output_html .= "	<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" href=\"". $themeHREF . "/style.css\" />\n";
				$output_html .= "	<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" href=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/".$this->_Name."/resources/style.css\" />\n";
				$output_html .= "	<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" href=\"". $GLOBALS['app']->getDataURL('', true) . "files/css/custom.css\" />\n";
				if (isset($get['css'])) {
					$output_html .= "	<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" href=\"".$get['css']."\" />\n";
				}
				$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/prototype/prototype.js\"></script>\n";
				$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/prototype/scriptaculous.js\"></script>\n";
				$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/prototype/effects.js\"></script>\n";
				$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/prototype/controls.js\"></script>\n";
				$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/js/admin.js\"></script>\n";
				$output_html .= "	<script type=\"text/javascript\" src=\"http://yui.yahooapis.com/2.4.0/build/yahoo-dom-event/yahoo-dom-event.js\"></script>\n";
				$output_html .= " <style>\n";
				$output_html .= "   #".$this->_Name."-editDiv-".$display_id." { width: 100%; text-align: right; }\n";
				$output_html .= "   #".$this->_Name."-edit-".$display_id." { display: block; width:20px; height:20px; overflow:hidden; }\n";
				$output_html .= "   #".$this->_Name."-edit-".$display_id.":hover { width: 118px; }\n";
				$output_html .= " </style>\n";
				$output_html .= " </head>\n";
				$output_html .= " <body style=\"background: url();\">\n";
				$output_html .= " <div id=\"".$this->_Name."-editDiv-".$display_id."\"><div id=\"".$this->_Name."-editDivStretch-".$display_id."\" style=\"float: left;\">&nbsp;</div><div style=\"float: left;\"><a onMouseOver=\"document.getElementById('".$this->_Name."-editDivStretch-".$display_id."').style.width = (parseInt(document.getElementById('".$this->_Name."-editDiv-".$display_id."').offsetWidth)-118)+'px';\" onMouseOut=\"document.getElementById('".$this->_Name."-editDivStretch-".$display_id."').style.width = (parseInt(document.getElementById('".$this->_Name."-editDiv-".$display_id."').offsetWidth)-20)+'px';\" id=\"".$this->_Name."-edit-".$display_id."\" target=\"_blank\" href=\"".$GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction')."\" title=\"Edit This Gadget\"><img border=\"0\" src=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/Users/images/edit_gadget.png\" /></a></div><div style=\"font-size: 0.1em; clear: both;\">&nbsp;</div></div>";
				if (isset($get['uid'])) {
					$output_html .= $layoutHTML->Display((int)$get['uid'], true, $referer);
				} else {
					$output_html .= $layoutHTML->Display(null, true, $referer);
				}
				$output_html .= " <script type=\"text/javascript\">Event.observe(window,\"load\",function(){sizeFrame".$display_id."(); document.getElementById('".$this->_Name."-editDivStretch-".$display_id."').style.width = (parseInt(document.getElementById('".$this->_Name."-editDiv-".$display_id."').offsetWidth)-20)+'px';});</script>";
				$output_html .= " </body>\n";
				$output_html .= "</html>\n";
			} else {
				$output_html .= $layoutHTML->Page($get['id'], true, $referer, ($get['mode'] == 'blog' ? true : false));
				//var_dump($output_html);
				$output_html = eregi_replace('|\<body([^>]*)\>|i', '<body\1>' . " <div id=\"".$this->_Name."-editDiv-".$display_id."\"><div id=\"".$this->_Name."-editDivStretch-".$display_id."\" style=\"float: left;\">&nbsp;</div><div style=\"float: left;\"><a onMouseOver=\"document.getElementById('".$this->_Name."-editDivStretch-".$display_id."').style.width = (parseInt(document.getElementById('".$this->_Name."-editDiv-".$display_id."').offsetWidth)-118)+'px';\" onMouseOut=\"document.getElementById('".$this->_Name."-editDivStretch-".$display_id."').style.width = (parseInt(document.getElementById('".$this->_Name."-editDiv-".$display_id."').offsetWidth)-20)+'px';\" id=\"".$this->_Name."-edit-".$display_id."\" target=\"_blank\" href=\"".$GLOBALS['app']->GetSiteURL()."/index.php?gadget=CustomPage&action=account_view&id=".$get['id']."\" title=\"Edit This Gadget\"><img border=\"0\" src=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/Users/images/edit_gadget.png\" /></a></div><div style=\"font-size: 0.1em; clear: both;\">&nbsp;</div></div>", $output_html);
				$output_html = str_replace('</body>', "<script type=\"text/javascript\">Event.observe(window,\"load\",function(){sizeFrame".$display_id."(); document.getElementById('".$this->_Name."-editDivStretch-".$display_id."').style.width = (parseInt(document.getElementById('".$this->_Name."-editDiv-".$display_id."').offsetWidth)-20)+'px';});</script></body>", $output_html);
			}
		}
		return $output_html;
    }

	/**
     * Displays user account controls.
     *
     * @param array  $info  user information
     * @access public
     * @return string
     */
    function GetUserAccountControls($info, $groups)
    {
        if (!isset($info['id'])) {
			return new Jaws_Error(_t('GLOBAL_ERROR_GET_ACCOUNT_PANE'), $this->_Name);
		}
		//require_once JAWS_PATH . 'include/Jaws/User.php';
        //$jUser = new Jaws_User;
        //$info  = $jUser->GetUserInfoById((int)$GLOBALS['app']->Session->GetAttribute('user_id'));
		//$userModel  = $GLOBALS['app']->LoadGadget('Users', 'Model');
		$pane_groups = array();

		/*
		$pane_status = $userModel->GetGadgetPaneInfoByUserID($this->_Name, $info['id']);
		if (!Jaws_Error::IsError($pane_status) && isset($pane_status['status'])) {
		*/
			//Construct panes for each available pane_method
			$panes = $this->GetUserAccountPanesInfo($groups);
			foreach ($panes as $pane) {
				$pane_groups[] = array(
					'id' => $pane['id'],
					'icon' => $pane['icon'],
					'name' => $pane['name'],
					'method' => $pane['method'],
					'params' => array(),
				);
			}
		/*
		} else if (Jaws_Error::IsError($pane_status)) {
			return new Jaws_Error(_t('GLOBAL_ERROR_GET_ACCOUNT_PANE'), $this->_Name);
		}
		*/
		return $pane_groups;
    }

     /*
     * Define array of panes for this gadget's account controls.
     * (i.e. Store gadget has "My Products" and "Saved Products" panes) 
     * 
     * $panes array structured as follows:
     * 'AdminHTML->MethodName' => 'Pane Title'
     * 
     * @access public
     * @return array of pane names
     */
    function GetUserAccountPanesInfo($groups = array())
    {		
		$panes = array();
		foreach ($groups as $group) {
			if (
				isset($group['group_name']) && 
				($group['group_name'] == strtolower($this->_Name).'_owners' || $group['group_name'] == strtolower($this->_Name).'_users') && 
				($group['group_status'] == 'active' || $group['group_status'] == 'founder' || $group['group_status'] == 'admin')
			) {
				// FIXME: Add translation string for this
				$panes[] = array(
					'name' => 'Pages',
					'id' => 'Pages',
					'method' => 'User'.ucfirst(str_replace('_','',str_replace(array('_owners','_users'),'',$group['group_name']))),
					'icon' => $GLOBALS['app']->GetJawsURL() . '/gadgets/'.$this->_Name.'/images/logo.png'
				);
			}
		}
		return $panes;
	}
	
    /**
     * Display the pane content.
     *
     * @param int  $user  user id
     * @access public
     * @return string
     */
    function UserCustompage($user)
    {			
		if (!$GLOBALS['app']->Session->Logged()) {
			//require_once JAWS_PATH . 'include/Jaws/Header.php';
			//Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'));
			$GLOBALS['app']->Session->PushLastResponse("User not logged in.", RESPONSE_ERROR);
			$GLOBALS['app']->Session->CheckPermission('Users', 'default');
		}
		require_once JAWS_PATH . 'include/Jaws/Template.php';
        $tpl = new Jaws_Template('gadgets/CustomPage/templates/');
        $tpl->Load('users.html');
		$tpl->SetBlock('pane');
		$tpl->SetVariable('title', $this->_Name);
		$tpl->SetVariable('pane_id', str_replace(" ",'',$this->_Name));
		$tpl->SetBlock('pane/pane_item');
		$tpl->SetVariable('pane_id', str_replace(" ",'',$this->_Name));
		$addPage =& Piwi::CreateWidget('Button', 'add_custompage', _t('CUSTOMPAGE_ADD_PAGE'), STOCK_ADD);
		$addPage->AddEvent(ON_CLICK, "javascript: window.open('index.php?gadget=".$this->_Name."&amp;action=account_form');");
		$tpl->SetVariable('add_button', $addPage->Get());
		$tpl->SetVariable('pane', 'UserCustompage');
		$tpl->SetVariable('icon', $GLOBALS['app']->GetJawsURL() . '/gadgets/'.$this->_Name.'/images/logo.png');
				
        $stpl = new Jaws_Template('gadgets/CustomPage/templates/');
        $stpl->Load('users.html');
        $stpl->SetBlock('UserCustomPageSubscriptions');
		$page = $this->account_Admin();
		if (!Jaws_Error::IsError($page)) {
			$page = str_replace('id="main"', '', $page);
			$page = str_replace('id="SyntactsApp"', 'class="SyntactsApp"', $page);
			$stpl->SetVariable('element', $page);
		} else {
			return new Jaws_Error(_t('GLOBAL_ERROR_GET_ACCOUNT_PANE'), $this->_Name);
		}
        $stpl->ParseBlock('UserCustomPageSubscriptions');
		
		$tpl->SetVariable('gadget_pane', $stpl->Get());
		$tpl->ParseBlock('pane/pane_item');
		$tpl->ParseBlock('pane');
        return $tpl->Get();
    }
	
    /**
     * Account Admin
     *
     * @access public
     * @return string
     */
    function account_Admin()
    {
		$gadget_admin = $GLOBALS['app']->LoadGadget('CustomPage', 'AdminHTML');
		return $gadget_admin->Admin(true);
    }
	

    /**
     * Account form
     *
     * @access public
     * @return string
     */
    function account_form()
    {
		$GLOBALS['app']->Layout->AddHeadLink('gadgets/Users/resources/style.css', 'stylesheet', 'text/css');
		$gadget_admin = $GLOBALS['app']->LoadGadget('CustomPage', 'AdminHTML');
		$page = $gadget_admin->form(true);
		/*
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$html_output = $users_html->GetAccountHTML('CustomPage');
		$html_output = str_replace("</head>", "<style type=\"text/css\">body { background: transparent none; }</style>\n</head>", $html_output);
		$output_html = str_replace("__JAWS_GADGET__", $page, $html_output);
		return $output_html;
		*/
		return $page;
    }

    /**
     * Account form_post
     *
     * @access public
     * @return string
     */
    function account_form_post()
    {
		$gadget_admin = $GLOBALS['app']->LoadGadget('CustomPage', 'AdminHTML');
		$page = $gadget_admin->form_post(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$output_html = str_replace("__JAWS_GADGET__", $page, $users_html->GetAccountHTML('CustomPage'));
		return $output_html;
    }

    /**
     * Account view
     *
     * @access public
     * @return string
     */
    function account_view()
    {
		$gadget_admin = $GLOBALS['app']->LoadGadget('CustomPage', 'AdminHTML');
		$page = $gadget_admin->view(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$html_output = $users_html->GetAccountHTML('CustomPage');
		$html_output = str_replace("</head>", "<style type=\"text/css\">body { background: transparent none; }</style>\n</head>", $html_output);
		$output_html = str_replace("__JAWS_GADGET__", $page, $html_output);
		return $output_html;
    }

    /**
     * Account A_form
     *
     * @access public
     * @return string
     */
    function account_A_form()
    {
		$gadget_admin = $GLOBALS['app']->LoadGadget('CustomPage', 'AdminHTML');
		$page = $gadget_admin->A_form(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$html_output = $users_html->GetAccountHTML('CustomPage');
		$html_output = str_replace("</head>", "<style type=\"text/css\">body { background: transparent none; }</style>\n</head>", $html_output);
		$output_html = str_replace("__JAWS_GADGET__", $page, $html_output);
		return $output_html;
    }

    /**
     * Account A_form_post
     *
     * @access public
     * @return string
     */
    function account_A_form_post()
    {
		$gadget_admin = $GLOBALS['app']->LoadGadget('CustomPage', 'AdminHTML');
		$page = $gadget_admin->A_form_post(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$output_html = str_replace("__JAWS_GADGET__", $page, $users_html->GetAccountHTML('CustomPage'));
		return $output_html;
    }

    /**
     * Account ShowEmbedWindow
     *
     * @access public
     * @return string
     */
    function account_ShowEmbedWindow()
    {
		$user_admin = $GLOBALS['app']->LoadGadget('Users', 'AdminHTML');
		return $user_admin->ShowEmbedWindow('CustomPage', 'OwnPage', true);
    }

    /**
     * Account AddLayoutElement
     *
     * @access public
     * @return string
     */
    function account_AddLayoutElement()
    {
		$gadget_admin = $GLOBALS['app']->LoadGadget('CustomPage', 'AdminHTML');
		$page = $gadget_admin->AddLayoutElement();
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$output_html = str_replace("__JAWS_GADGET__", $page, $users_html->GetAccountHTML('CustomPage'));
		return $output_html;
    }

    /**
     * Account SaveLayoutElement
     *
     * @access public
     * @return string
     */
    function account_SaveLayoutElement()
    {
		$gadget_admin = $GLOBALS['app']->LoadGadget('CustomPage', 'AdminHTML');
		$page = $gadget_admin->SaveLayoutElement();
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$output_html = str_replace("__JAWS_GADGET__", $page, $users_html->GetAccountHTML('CustomPage'));
		return $output_html;
    }

    /**
     * Account EditElementAction
     *
     * @access public
     * @return string
     */
    function account_EditElementAction()
    {
		$gadget_admin = $GLOBALS['app']->LoadGadget('CustomPage', 'AdminHTML');
		$page = $gadget_admin->EditElementAction();
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$output_html = str_replace("__JAWS_GADGET__", $page, $users_html->GetAccountHTML('CustomPage'));
		return $output_html;
    }
    
    /**
     * Account GetQuickAddForm
     *
     * @access public
     * @return string
     */
    function account_GetQuickAddForm()
    {
		$gadget_admin = $GLOBALS['app']->LoadGadget('CustomPage', 'AdminHTML');
		$page = $gadget_admin->GetQuickAddForm(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$output_html = str_replace("__JAWS_GADGET__", $page, $users_html->GetAccountHTML('CustomPage'));
		return $output_html;
    }
    
    /**
     * sets GB root with DPATH
     *
     * @access public
     * @return javascript string
     */
    function account_SetGBRoot()
    {
		// Make output a real JavaScript file!
		header('Content-type: text/javascript'); 
		echo "var GB_ROOT_DIR = \"data/greybox/\";";
	}

    /**
     * Account Public Profile
     *
     * @access public
     * @return string
     */
    function account_profile($uid = 0)
    {
		$layoutGadget = $GLOBALS['app']->LoadGadget('CustomPage', 'LayoutHTML');
		$output_html = '';
		if($uid > 0) {
			$output_html .= $layoutGadget->Display($uid);
		} else {
            require_once JAWS_PATH . 'include/Jaws/Header.php';
            Jaws_Header::Location($GLOBALS['app']->GetSiteURL().'/');
		}
		
		return $output_html;
    }
}
