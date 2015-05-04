<?php
/**
 * Show the Jaws Page not found message
 *
 * @category   JawsType
 * @package    Core
 * @author     Jonathan Hernandez <ion@suavizado.com>
 * @author     Ali Fazelzadeh <afz@php.net>
 * @copyright  2006-2010 Jaws Development Group
 * @license    http://www.gnu.org/copyleft/lesser.html
 */
class Jaws_HTTPError
{
    function Get($code, $title = null, $message = null)
    {
        $xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        switch ($code) {
            case 404:
                header($xss->filter($_SERVER['SERVER_PROTOCOL'])." 404 Not Found");
                $title = empty($title)? _t('GLOBAL_HTTP_ERROR_TITLE_404') : $title;
                if (empty($message)) {
                    if (isset($_SERVER['REQUEST_URI']) && !empty($_SERVER['REQUEST_URI'])) {
                        $uri = $_SERVER['REQUEST_URI'];
                    } elseif (isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING'])) {
                        $uri = $_SERVER['PHP_SELF'] . '?' .$_SERVER['QUERY_STRING'];
                    } else {
                        $uri = '';
                    }
                    $uri = $xss->filter(urldecode($uri));
                    $message = _t('GLOBAL_HTTP_ERROR_CONTENT_404', $uri);
                }
                break;

            case 403:
                header($xss->filter($_SERVER['SERVER_PROTOCOL'])." 403 Forbidden");
                $title   = empty($title)? _t('GLOBAL_HTTP_ERROR_TITLE_403') : $title;
                $message = empty($message)? _t('GLOBAL_HTTP_ERROR_CONTENT_403') : $message;
                break;

            case 500:
                header($xss->filter($_SERVER['SERVER_PROTOCOL'])." 500 Server Error");
                $title   = empty($title)? _t('GLOBAL_HTTP_ERROR_TITLE_500') : $title;
                $message = empty($message)? _t('GLOBAL_HTTP_ERROR_CONTENT_500') : $message;
                break;

            case 503:
                header($xss->filter($_SERVER['SERVER_PROTOCOL'])." 503 Service Unavailable");
                $title   = empty($title)? _t('GLOBAL_HTTP_ERROR_TITLE_503') : $title;
                $message = empty($message)? _t('GLOBAL_HTTP_ERROR_CONTENT_503') : $message;
                break;

            default:
                $title   = empty($title)? _t("GLOBAL_HTTP_ERROR_TITLE_$code") : $title;
                $message = empty($message)? _t("GLOBAL_HTTP_ERROR_CONTENT_$code") : $message;
        }

        // if current theme has a error code html file, return it, if not return the messages.
		/*
		if (isset($GLOBALS['app']->Layout)) {
			$GLOBALS['app']->Layout->AddToBodyClass("http-error http-error-".$code);
        }
		*/
		$template = "<h1>$title</h1><p>$message</p>";
        $theme = $GLOBALS['app']->GetTheme();
		if ($theme['exists']) {
			$site_name = $GLOBALS['app']->Registry->Get('/config/site_name');
			$tplFile = $theme['path'] ."/$code.html";
			if (substr(strtolower($tplFile), 0, 4) != 'http') {
				// snoopy
				include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
				$snoopy = new Snoopy;
				if (substr(strtolower($tplFile), 0, 5) == 'https') {
					$tplFile = $theme['name']."/$code.html";
				}
				$snoopy->fetch($tplFile);
				if ($snoopy->status == "200") {
					require_once JAWS_PATH . 'include/Jaws/Template.php';
					$tpl = new Jaws_Template();
					$tpl->LoadFromString($snoopy->results);
					$tpl->SetBlock($code);
					//set global site config
					$tpl->SetVariable('site-name',   $site_name);
					$tpl->SetVariable('site-title',  $site_name);
					$tpl->SetVariable('site-slogan', $GLOBALS['app']->Registry->Get('/config/site_slogan'));
					$tpl->SetVariable('site-author',      $GLOBALS['app']->Registry->Get('/config/site_author'));
					$tpl->SetVariable('site-copyright',   $GLOBALS['app']->Registry->Get('/config/copyright'));
					$tpl->SetVariable('site-description', $GLOBALS['app']->Registry->Get('/config/site_description'));
					$tpl->SetVariable('title',   $title);
					$tpl->SetVariable('content', $message);
					$tpl->ParseBlock($code);
					$template = $tpl->Get();
				}
			} else {
				if (file_exists(JAWS_DATA. "themes/$theme/$code.html")) {
					require_once JAWS_PATH . 'include/Jaws/Template.php';
					$tpl = new Jaws_Template();
					$tpl->Load("$code.html");
					$tpl->SetBlock($code);
					//set global site config
					$tpl->SetVariable('site-name',   $site_name);
					$tpl->SetVariable('site-title',  $site_name);
					$tpl->SetVariable('site-slogan', $GLOBALS['app']->Registry->Get('/config/site_slogan'));
					$tpl->SetVariable('site-author',      $GLOBALS['app']->Registry->Get('/config/site_author'));
					$tpl->SetVariable('site-copyright',   $GLOBALS['app']->Registry->Get('/config/copyright'));
					$tpl->SetVariable('site-description', $GLOBALS['app']->Registry->Get('/config/site_description'));	
					$tpl->SetVariable('title',   $title);
					$tpl->SetVariable('content', $message);
					$tpl->ParseBlock($code);
					$template = $tpl->Get();
				}
			}
		}
		if (!$GLOBALS['app']->IsStandAloneMode()) {
			return $template;
		} else {
			$adminModel = $GLOBALS['app']->LoadGadget('Layout', 'AdminModel');
			$GLOBALS['app']->Layout = new Jaws_Layout(false);
			$GLOBALS['app']->Layout->Load(true);
			$layoutContent = $GLOBALS['app']->Layout->_Template->Blocks['layout']->Content;
			$useLayoutMode = $GLOBALS['app']->Layout->_Template->VariableExists('layout-mode');
			
			$site_name = $GLOBALS['app']->Registry->Get('/config/site_name');
			$site_slogan = $GLOBALS['app']->Registry->Get('/config/site_slogan');
			$inputStr = $layoutContent;
			$delimeterLeft = "<div id=\"top_header\">";
			$delimeterRight = "</div>";
			$startLeft = strpos($inputStr, $delimeterLeft);
			$posLeft = ($startLeft+strlen($delimeterLeft));
			$posRight = strpos($inputStr, $delimeterRight, $posLeft);
			$top_header = substr($inputStr, $posLeft, $posRight-$posLeft);
			$layoutContent = str_replace($top_header, '<h1>'.$site_name.'</h1><p>'.$site_slogan.'</p>', $layoutContent);
					
			$GLOBALS['app']->Layout->_Template->Blocks['layout']->Content = $layoutContent;
			$GLOBALS['app']->Layout->_Template->SetVariable('site-title', $site_name);
			//$GLOBALS['app']->Layout->_Template->SetVariable('site-slogan', $site_slogan);

			$GLOBALS['app']->Layout->AddScriptLink('libraries/carousel/dist/carousel.js');
			$GLOBALS['app']->Layout->AddScriptLink('libraries/slideshow/slideshow-min.js');
			$GLOBALS['app']->Layout->AddScriptLink('libraries/js/global2.js');
			$GLOBALS['app']->Layout->AddHeadLink('libraries/carousel/themes/carousel/prototype-ui.css', 'stylesheet', 'text/css');
			$GLOBALS['app']->Layout->AddScriptLink('libraries/window/dist/window.js');
			
			$GLOBALS['app']->Layout->AddHeadLink('libraries/window/themes/window/window.css', 'stylesheet', 'text/css');
			$GLOBALS['app']->Layout->AddHeadLink('libraries/window/themes/window/simpleblue.css', 'stylesheet', 'text/css');
			$GLOBALS['app']->Layout->AddHeadLink('libraries/window/themes/shadow/mac_shadow.css', 'stylesheet', 'text/css');
			
			foreach ($GLOBALS['app']->Layout->_Template->Blocks['layout']->InnerBlock as $name => $data) {
				if ($name == 'head') continue;
				$GLOBALS['app']->Layout->_Template->SetBlock('layout/'.$name);
				$gadgets = $adminModel->GetGadgetsInSection($name);
				if (!is_array($gadgets)) continue;
				// Sort result array
				$subkey = 'layout_position'; 
				$temp_array = array();
				$temp_array[key($gadgets)] = array_shift($gadgets);
				foreach($gadgets as $key => $val){
					$offset = 0;
					$found = false;
					foreach($temp_array as $tmp_key => $tmp_val) {
						if(!$found and strtolower($val[$subkey]) > strtolower($tmp_val[$subkey])) {
							$temp_array = array_merge(    
								(array)array_slice($temp_array,0,$offset),
								array($key => $val),
								array_slice($temp_array,$offset)
							);
							$found = true;
						}
						$offset++;
					}
					if(!$found) $temp_array = array_merge($temp_array, array($key => $val));
				}
				$gadgets = array_reverse($temp_array);
				$highest_pos = 0;
				$section_empty = true;
				$content = '';
				foreach ($gadgets as $gadget) {
					if ($name == 'main' && $gadget['gadget'] == '[REQUESTEDGADGET]') {
						$section_empty = false;
						$content .= $template;
					}
					$id = $gadget['id'];
					if (
						file_exists(JAWS_PATH . 'gadgets/'. $gadget['gadget']. '/'. 'LayoutHTML.php') ||
							file_exists(JAWS_PATH . 'gadgets/'. $gadget['gadget']. '/'. 'Actions.php')
					) {
						if ($GLOBALS['app']->Registry->Get('/gadgets/'.$gadget['gadget'].'/enabled') == 'true' &&
							$gadget['gadget'] != '[REQUESTEDGADGET]')
						{
							if (Jaws_Gadget::IsGadgetUpdated($gadget['gadget']) && $gadget['display_when'] == '*') {
								$section_empty = false;
								$content .= $GLOBALS['app']->Layout->PutGadget($gadget['gadget'], $gadget['gadget_action'], $gadget['section']);
							}
						}
					}
				}
				$GLOBALS['app']->Layout->_Template->SetVariable('ELEMENT', $content);
				$GLOBALS['app']->Layout->_Template->ParseBlock('layout/'.$name);
			}
			//$GLOBALS['app']->Layout->AddToBodyClass("error http-error http-error-".$code);
			$output = $GLOBALS['app']->Layout->Show(false);
			return $output;
		}
    }
}