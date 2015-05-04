<?php
/**
 * TMS (Theme Management System) Gadget Normal view
 *
 * @category   GadgetAdmin
 * @package    TMS
 * @author     Pablo Fischer <pablo@pablo.com.mx>
 * @copyright  2007-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class TmsHTML extends Jaws_GadgetHTML
{
    /**
     * Gadget constructor
     *
     * @access public
     */
    function TmsHTML()
    {
        $this->Init('Tms');
    }

    /**
     * Share themes and browse shared themes.
     *
     * @category 	feature
     * @access  public
     * @return  string   RSS content or 404 string XHTML
     */
    function RSS()
    {
        if ($GLOBALS['app']->Registry->Get('/gadgets/Tms/share_mode') == 'yes') {
            $model = $GLOBALS['app']->LoadGadget('Tms', 'Model');
            $rss = $model->MakeRSS(false);
            if (!Jaws_Error::isError($rss)) {
                header('Content-type: application/xml');
                return $rss;
            }
        } else {
            require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
            return Jaws_HTTPError::Get(404);
        }
    }

    /**
     * Preview and install new themes on-the-fly.
     *
     * @category 	feature
     * @param	int 	$id  	Theme ID (optional)
     * @param	boolean 	$embedded 	Embedded mode
     * @param	string 	$referer 	Embedding referer
     * @access 	public
     * @return 	string 	HTML template content
     */
    function Preview($id = null, $embedded = false, $referer = null)
    {
		$request =& Jaws_Request::getInstance();
        $post = $request->get(array('id', 'linkid'), 'get');

        $GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $post['id'] = $xss->parse($post['id']);

        $model = $GLOBALS['app']->LoadGadget('Tms', 'Model');
        if (is_null($id)) {
			$id = $post['id'];
        }
        if (empty($id)) {
			$id = 'all';
        }
            
        $output_html = "";
        $output_html .= "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
		$output_html .= "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\">\n";
		$output_html .= " <head>\n";
		$output_html .= "  <title>Demo</title>\n";
		$output_html .= "  <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n";
		$output_html .= "	<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" href=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/greybox/gb_styles.css\" />\n";
		/*
		$output_html .= "	<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" href=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/window/themes/window/window.css\" />\n";
		$output_html .= "	<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" href=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/window/themes/window/black_hud.css\" />\n";
		$output_html .= "	<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" href=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/window/themes/shadow/mac_shadow.css\" />\n";
		*/
		$output_html .= "	<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" href=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/carousel/themes/carousel/prototype-ui.css\" />\n";
		$output_html .= "	<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" href=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/Tms/resources/style.css\" />\n";
		if (file_exists(JAWS_DATA  . 'files/css/custom.css')) {
			$output_html .= "	<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" href=\"". $GLOBALS['app']->GetDataURL('', true) ."files/css/custom.css\" />\n";
		}
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/prototype/prototype.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/prototype/scriptaculous.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/prototype/effects.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/prototype/controls.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/carousel/dist/carousel.js\"></script>\n";
		//$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/window/dist/window.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/include/Jaws/Ajax/Response.js\"></script>\n";
		/*
		$output_html .= "	<script type=\"text/javascript\" src=\"index.php?gadget=Tms&amp;action=Ajax&amp;client=all&amp;stub=TmsAjax\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"index.php?gadget=Tms&amp;action=AjaxCommonFiles\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/Tms/resources/client_script.js\"></script>\n";
		*/
		$output_html .= "	<script type=\"text/javascript\" src=\"index.php?gadget=CustomPage&action=account_SetGBRoot\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/greybox/AJS.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/greybox/AJS_fx.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/greybox/gb_scripts.js\"></script>\n";
		$output_html .= "	<!--[if lt IE 7]>\n";
		$output_html .= "	<script src=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/ControlPanel/resources/ie-bug-fix.js\" type=\"text/javascript\"></script>\n";
		$output_html .= "	<![endif]-->\n";
		$output_html .= '<style type="text/css">';
		if ($id != 'all') {
			$output_html .= '#demo-button, #demo-top {margin-top: -71px;} ';
			$output_html .= '#demo-content #demo-iframe {margin-top: 0px;}';
			$output_html .= '#demo-top-link {top: 10px;}';
		} else {
			$output_html .= '#demo-top-link {top: 71px;}';
		}
		$output_html .= '#demo-top-link {text-transform: uppercase; font-size: 10px; font-family: "Lucida Grande",Myriad,"Andale Sans","Luxi Sans","Bitstream Vera Sans",Tahoma,"Toga Sans",Helvetica,Arial,sans-serif; position: fixed; left: 10px; padding: 3px;  background: #222222; color: #CCCCCC;}';
		$output_html .= '</style>';
		$output_html .= '<script type="text/javascript">';
		$output_html .= 'function showDemoNav() {if ($("demo-button").style.marginTop == "0px") {';
		$output_html .= '$("demo-button").style.marginTop = "-71px"; $("demo-top").style.marginTop = "-71px";';
		$output_html .= '$("demo-iframe").style.marginTop = "0px"; $("demo-top-link").style.top = "10px";';
		$output_html .= '} else {';
		$output_html .= '$("demo-button").style.marginTop = "0px"; $("demo-top").style.marginTop = "0px";';
		$output_html .= '$("demo-iframe").style.marginTop = "71px"; $("demo-top-link").style.top = "71px";';
		$output_html .= '}}';
		$output_html .= '</script>';
		$output_html .= " </head>\n";
		$output_html .= " <body style=\"background: url(); margin: 0; padding: 0;\">\n";
		$output_html .= " <a href=\"javascript:void(0);\" id=\"demo-top-link\" onclick=\"showDemoNav();\">show/hide more</a>\n";

		$tpl = new Jaws_Template('gadgets/Tms/templates/');
		$tpl->Load('normal.html');
		
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
		
		$tpl->SetBlock('theme_preview');
		$tpl->SetVariable('site_url', $GLOBALS['app']->GetSiteURL());
		$tpl->SetVariable('site_title', 'Jaws');
		//$tpl->SetVariable('DPATH', JAWS_DPATH);
		$tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . "/");

		// get options for Themes			
		$tmsModel = $GLOBALS['app']->LoadGadget('Tms', 'Model');
		/*
		foreach($tmsModel->getRepositories() as $repository) {
			$themes = $tmsModel->getThemes($repository['id']);
		}
		*/
		
		$themes = $tmsModel->getThemes();
		if (isset($themes) && is_array($themes) && count($themes) > 0) {
			reset($themes);
			$i = 0;
			$first = '';
			$items = array();
			foreach ($themes as $theme) {
				if (isset($theme['desc']) && strpos(strtolower($theme['desc']), 'demo: http://') !== false) {
					$theme_image = (empty($theme['image']) ? $GLOBALS['app']->GetJawsURL() . '/gadgets/Tms/images/noexample.png' : $theme['image']);
					$theme_name = (empty($theme['name']) ? 'Theme '.$i : $theme['name']);
					$inputStr = strtolower($theme['desc']);
					$delimeterLeft = "demo: ";
					$delimeterRight = " ";
					$posLeft=strpos($inputStr, $delimeterLeft);
					$posLeft+=strlen($delimeterLeft);
					$posRight=strpos($inputStr, $delimeterRight, $posLeft);
					if ($posRight === false) {
						$output = substr($inputStr, $posLeft, strlen($inputStr));
					} else {
						$output = substr($inputStr, $posLeft, $posRight-$posLeft);
					}
					$theme_url = $output;
					$items[] = array($theme_image, $theme_name, $theme_url);
					//$theme_url = str_replace(' ', '', $theme_url);
					//$theme_url = str_replace('demo:', '', $theme_url);
					$i++;
				}
			}
			if (count($items) > 0) {
				$tpl->SetBlock('theme_preview/carousel');				
				$n = 0;
				foreach ($items as $item) {	
					if ($n == 0) {
						$first = $item[1];
						$first_url = $item[2];
					}
					if ($id != 'all') {
						if ($item[1] == $id) {
							$first = $item[1];
							$first_url = $item[2];
							$tpl->SetVariable('scrollTo', 'hCarousel.scrollTo('.$n.'); stopscroll();');
						}
					}
					$tpl->SetBlock('theme_preview/carousel/item');				
					$tpl->SetVariable('num', ($n+1));
					//$tpl->SetVariable('DPATH', JAWS_DPATH);
					$tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . "/");
					$tpl->SetVariable('image', $item[0]);
					$tpl->SetVariable('name', $item[1]);
					$tpl->SetVariable('url', $item[2]);
					$tpl->ParseBlock('theme_preview/carousel/item');
					$n++;
				}
				$tpl->ParseBlock('theme_preview/carousel');				
			}
			$tpl->SetBlock('theme_preview/frame');				
			$tpl->SetVariable('url', $first_url);
			$tpl->ParseBlock('theme_preview/frame');				
		} else {
			$tpl->SetBlock('theme_preview/no_items');				
			$tpl->SetVariable('message', _t('TMS_ERROR_REPOSITORY_DOES_NOT_EXISTS'));
			$tpl->ParseBlock('theme_preview/no_items');				
		}
			
		if ($embedded == true && !is_null($referer) && isset($page['id'])) {	
			$tpl->SetBlock('theme_preview/embedded');
			$tpl->SetVariable('id', $page['id']);		        
			if (strtolower($referer) == strtolower($_SERVER['SERVER_NAME'])) {
				$tpl->SetVariable('referer', $GLOBALS['app']->GetJawsURL() . "/libraries/iframes/domain1/iframetest_resize1.html");		        
			} else {	
				$tpl->SetVariable('referer', "http://".$referer."/19/img/agents/19/custom_img/common_files/iframetest_resize1.html");		        
			}
			$tpl->ParseBlock('theme_preview/embedded');
		} else {
			$tpl->SetBlock('theme_preview/not_embedded');
			$tpl->SetVariable('id', $page['id']);		        
			$tpl->ParseBlock('theme_preview/not_embedded');
		}
		// Statistics Code
		$tpl->SetBlock('theme_preview/stats');
		$GLOBALS['app']->Registry->LoadFile('CustomPage');
		$tpl->SetVariable('stats', html_entity_decode($GLOBALS['app']->Registry->Get('/gadgets/CustomPage/googleanalytics_code')));		        
		$tpl->ParseBlock('theme_preview/stats');

        $tpl->ParseBlock('theme_preview');

		$output_html .= $tpl->Get();
		
		$output_html .= " </body>\n";
		$output_html .= "</html>\n";
		return $output_html;
    }
	
}