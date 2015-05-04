<?php
/**
 * CustomPage Gadget (layout actions in client side)
 *
 * @category   GadgetLayout
 * @package    CustomPage
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class CustomPageLayoutHTML
{
    /**
     * Loads layout actions
     *
     * @access private
     */
    function LoadLayoutActions($limit = null, $offset = null)
    {
        $actions = array();
        $model = $GLOBALS['app']->LoadGadget('CustomPage', 'Model');
		$pages = $model->GetPages($limit, 'sm_description', 'ASC', $offset, 0);
		if (!Jaws_Error::IsError($pages)) {
			foreach ($pages as $page) {
				$actions['Page(' . $page['id'] . ')'] = array(
					'mode' => 'LayoutAction',
					'name' => _t('CUSTOMPAGE_LAYOUT_PAGE', (!empty($page['title']) ? substr(trim(strip_tags($page['title'])), 0, 25) : substr(trim(strip_tags($page['sm_description'])), 0, 25))),
					'desc' => _t('CUSTOMPAGE_LAYOUT_PAGE_DESCRIPTION', (!empty($page['title']) ? substr(trim(strip_tags($page['title'])), 0, 60) : substr(trim(strip_tags($page['sm_description'])), 0, 60)))
				);
				$posts = $model->GetAllPostsOfPage($page['id'], null, false, false, 'Y');
				if (!Jaws_Error::IsError($posts)) {
					foreach ($posts as $post) {
						$actions['ShowPost(' . $post['id'] . ')'] = array(
							'mode' => 'LayoutAction',
							'name' => _t('CUSTOMPAGE_LAYOUT_SHOWPOST', (!empty($post['title']) ? substr(trim(strip_tags($post['title'])), 0, 25) : substr(trim(strip_tags($post['description'])), 0, 25))),
							'desc' => _t('CUSTOMPAGE_LAYOUT_SHOWPOST_DESCRIPTION', (!empty($post['title']) ? substr(trim(strip_tags($post['title'])), 0, 60) : substr(trim(strip_tags($post['description'])), 0, 60)))
						);
					}
				}
			}
		}
		
        return $actions;
	}
	
    /**
     * Displays a page.
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
		require_once JAWS_PATH . 'include/Jaws/Image.php';
		if (!isset($GLOBALS['app']->Layout)) {
			require_once JAWS_PATH . 'include/Jaws/Layout.php';
			$GLOBALS['app']->Layout = new Jaws_Layout();
		}
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');
		$model = $GLOBALS['app']->LoadGadget('CustomPage', 'Model');
		$adminModel = $GLOBALS['app']->LoadGadget('Layout', 'AdminModel');
        $customPageHTML = $GLOBALS['app']->LoadGadget('CustomPage', 'HTML');
		$date = $GLOBALS['app']->loadDate();
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		
		$request =& Jaws_Request::getInstance();
        $get = $request->get(array('id'), 'get');
		if (is_null($id)) {
			$id = $get['id'];
        }
        if (!is_null($id)) {
			$pageInfo = $model->GetPage($id);
			if (Jaws_Error::IsError($pageInfo) || !isset($pageInfo['id']) || empty($pageInfo['id'])) {
				return _t('CUSTOMPAGE_ERROR_PAGE_NOT_FOUND');
				//return new Jaws_Error(_t('CUSTOMPAGE_ERROR_PAGE_NOT_FOUND'), _t('CUSTOMPAGE_NAME'));		
			}
			$pageInfo['replacements'] = $replacements;
			$pageInfo['blog'] = $blog;
			$pageInfo['referer'] = $referer;
			$pageInfo['embedded'] = $embedded;
			if (empty($pageInfo['layout']) || strlen($pageInfo['layout']) <= 1) {
				$pageInfo['layout'] = null;
			}
			if (empty($pageInfo['theme']) || strlen($pageInfo['theme']) <= 1) {
				$pageInfo['theme'] = null;
			}
			if (empty($pageInfo['gadget'])) {
				$pageInfo['gadget'] = 'CustomPage';
			}
			if (empty($pageInfo['gadget_action'])) {
				$pageInfo['gadget_action'] = 'Page';
			}
			if (empty($pageInfo['linkid'])) {
				$pageInfo['linkid'] = $id;
			}
			// Is this page the index?
			$am_i_index = false;
			$full_url = $GLOBALS['app']->getFullURL();
			if (!empty($full_url)) {	
				$alias_page = '';
				$alias_pages = array();
				// Add complete query values to body class
				if (strpos($full_url, '=') !== false) {
					$fast_urls = false;
					$hook = $GLOBALS['app']->loadHook($pageInfo['gadget'], 'URLList');
					if ($hook !== false) {
						if (method_exists($hook, 'GetAllFastURLsOfRequest')) {
							$fast_urls = $hook->GetAllFastURLsOfRequest($pageInfo['gadget_action'], $pageInfo['linkid']);
						}
					}
					if (is_array($fast_urls) && !count($fast_urls) <= 0) {
						foreach ($fast_urls as $f_url) {
							$alias_pages[] = $GLOBALS['app']->Map->GetURLFor($pageInfo['gadget'], $pageInfo['gadget_action'], array('id' => $f_url));
						}
					} else {
						$alias_page = $GLOBALS['app']->Map->GetURLFor($pageInfo['gadget'], $pageInfo['gadget_action'], $_GET);
					}
				} else {
					$alias_page = $GLOBALS['app']->Map->Parse($full_url, true);
				}
				
				$config_home_page = $GLOBALS['app']->Registry->Get('/config/home_page');
				if (substr(strtolower($config_home_page), 0, 4) == 'http') {
					$config_home_page = str_replace(array(
														$GLOBALS['app']->getSiteURL('/', false, 'http'), 
														$GLOBALS['app']->getSiteURL('/', false, 'https')
													), '', $config_home_page);
				}
				if (
					($alias_page == $GLOBALS['app']->Registry->Get('/config/home_page')) || 
					(in_array($GLOBALS['app']->Registry->Get('/config/home_page'), $alias_pages)) || 
					strpos($full_url, $GLOBALS['app']->Registry->Get('/config/home_page')) !== false || 
					($GLOBALS['app']->getSiteURL('', false, 'http') .'/'.$config_home_page == $full_url) || 
					($GLOBALS['app']->getSiteURL('', false, 'https') .'/'.$config_home_page == $full_url) || 
					substr($full_url,-9) == "index.php"
				) {
					$am_i_index = true;
				}
			}
			$pageInfo['is_index'] = $am_i_index;
			
			$layout_replacements = $GLOBALS['app']->Layout->GetCustomReplacements();
			
			$GLOBALS['app']->Layout = new Jaws_Layout($embedded);
			$GLOBALS['app']->Layout->Load(true, $pageInfo['theme'], $pageInfo['layout']);
			$layoutContent = $GLOBALS['app']->Layout->_Template->Blocks['layout']->Content;
			$useLayoutMode = $GLOBALS['app']->Layout->_Template->VariableExists('layout-mode');
			
			$GLOBALS['app']->Layout->SetCustomReplacements($layout_replacements);
			
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
					
			// Page specific code
			if (!empty($pageInfo['image_code'])) {
				$pageInfo['image_code'] = $customPageHTML->ParseText(str_replace(array("\r","\n"), '', $pageInfo['image_code']), 'CustomPage');
				$pageInfo['image_code'] = htmlspecialchars_decode($pageInfo['image_code']);
				$layoutContent = str_replace('</body>', $pageInfo['image_code'].'</body>', $layoutContent);
			}
			
			$GLOBALS['app']->Layout->_Template->Blocks['layout']->Content = $layoutContent;
			$GLOBALS['app']->Layout->_Template->SetVariable('site-title', $site_name);
			//$GLOBALS['app']->Layout->_Template->SetVariable('site-slogan', $site_slogan);

			$GLOBALS['app']->Layout->AddScriptLink('libraries/carousel/dist/carousel.js');
			$GLOBALS['app']->Layout->AddScriptLink('libraries/slideshow/slideshow-min.js');
			$GLOBALS['app']->Layout->AddScriptLink('libraries/js/global2.js');
			$GLOBALS['app']->Layout->AddHeadLink('libraries/carousel/themes/carousel/prototype-ui.css', 'stylesheet', 'text/css');
			$GLOBALS['app']->Layout->AddHeadLink('gadgets/CustomPage/resources/style.css', 'stylesheet', 'text/css');
			/*
			$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=CustomPage&amp;action=Ajax&amp;client=all&amp;stub=CustomPageAjax');
			$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=CustomPage&amp;action=AjaxCommonFiles');
			$GLOBALS['app']->Layout->AddScriptLink('gadgets/CustomPage/resources/client_script.js');
			*/
			$GLOBALS['app']->Layout->AddScriptLink('libraries/window/dist/window.js');
			
			$GLOBALS['app']->Layout->AddHeadLink('libraries/window/themes/window/window.css', 'stylesheet', 'text/css');
			$GLOBALS['app']->Layout->AddHeadLink('libraries/window/themes/window/simpleblue.css', 'stylesheet', 'text/css');
			$GLOBALS['app']->Layout->AddHeadLink('libraries/window/themes/shadow/mac_shadow.css', 'stylesheet', 'text/css');
			
			require_once JAWS_PATH . 'include/Jaws/User.php';
			$jUser = new Jaws_User;
			if ((int)$pageInfo['ownerid'] > 0) {
				$info = $jUser->GetUserInfoById((int)$pageInfo['ownerid']);
				if (
					!Jaws_Error::IsError($info) && isset($info['id']) && !empty($info['id']) && 
					file_exists(JAWS_DATA . 'files/css/users/'.$info['id'].'/custom.css')
				) {
					$GLOBALS['app']->Layout->AddHeadOther('<link rel="stylesheet" media="screen" type="text/css" href="'.$GLOBALS['app']->getDataURL('', true). 'files/css/users/'.$info['id'].'/custom.css" />');
				}
			}
			if ($pageInfo['gadget'] == 'Users' && $pageInfo['gadget_action'] == 'GroupPage') {
				$info = $jUser->GetGroupInfoById((int)$pageInfo['linkid']);
				if (
					!Jaws_Error::IsError($info) && isset($info['id']) && !empty($info['id']) && 
					file_exists(JAWS_DATA . 'files/css/groups/'.$info['id'].'/custom.css')
				) {
					$GLOBALS['app']->Layout->AddHeadOther('<link rel="stylesheet" media="screen" type="text/css" href="'.$GLOBALS['app']->getDataURL('', true). 'files/css/groups/'.$info['id'].'/custom.css" />');
				}
			}
			
			/*
			if ($pageInfo['active'] == 'N' && $embedded === false) {
				return _t('CUSTOMPAGE_ERROR_PAGE_NOT_FOUND');
            } else {
			*/
				$head_scripts = '';
				if (!empty($pageInfo['sm_description'])) {
					$GLOBALS['app']->Layout->SetTitle(strip_tags($pageInfo['sm_description']));
				}
				if (!empty($pageInfo['description'])) {
					$GLOBALS['app']->Layout->AddHeadMeta("Description", strip_tags($pageInfo['description']));
                }
				if (!empty($pageInfo['keywords'])) {
					$GLOBALS['app']->Layout->AddToMetaKeywords(strip_tags($pageInfo['keywords']));
				}
				
				
				$display_id = md5($customPageHTML->_Name.$id);
				$pageInfo['display_id'] = $display_id;
				$id = $pageInfo['id'];
				
				if (!count($replacements) <= 0) {
					$GLOBALS['app']->Layout->AddToCustomReplacements($replacements);
				}
												
				if ($embedded === true) {
					$head_scripts .= '<style type="text/css">html body div #container {width: auto;} #first_header,#top_header,#header,#footer,#sub_footer{visibility: hidden;}</style>'."\n";
					$head_scripts .= '<script type="text/javascript">document.observe("dom:loaded",function(){
						document.getElementById(\'first_header\').parentNode.removeChild(document.getElementById(\'first_header\'));
						document.getElementById(\'top_header\').parentNode.removeChild(document.getElementById(\'top_header\'));
						document.getElementById(\'header\').parentNode.removeChild(document.getElementById(\'header\'));
						document.getElementById(\'footer\').parentNode.removeChild(document.getElementById(\'footer\'));
						document.getElementById(\'sub_footer\').parentNode.removeChild(document.getElementById(\'sub_footer\'));
					});
					Event.observe(window,"load",function(){
						sizeFrame'.$display_id.'();
					});</script>'."\n";
				}
				if ($blog === true) {
					$first_post = 0;
					$section_posts = array(0 => 0, 1 => 0, 2 => 0, 3 => 0);
					$posts = $model->GetAllPostsOfPage($pageInfo['id'], null, true, true, 'Y');
					if (!Jaws_Error::IsError($posts)) {
						foreach ($posts as $post) {
							if ($first_post == 0 && isset($post['id']) && !empty($post['id'])) {
								$first_post = $post['id'];
							}
							$section_posts[(int)$post['section_id']]++;
						}
					}
					if ($first_post > 0) {
						$head_scripts .= '<script type="text/javascript">document.observe("dom:loaded",function(){'."\n";
						if ($section_posts[0] == 0) {
							$head_scripts .= 'document.getElementById(\'custom_page-section0\').parentNode.removeChild(document.getElementById(\'custom_page-section0\'));'."\n";
						}
						if ($section_posts[1] == 0) {
							$head_scripts .= 'document.getElementById(\'custom_page-section1\').parentNode.removeChild(document.getElementById(\'custom_page-section1\'));'."\n";
						}
						if ($section_posts[2] == 0) {
							$head_scripts .= 'document.getElementById(\'custom_page-section2\').parentNode.removeChild(document.getElementById(\'custom_page-section2\'));'."\n";
						}
						if ($section_posts[3] == 0) {
							$head_scripts .= 'document.getElementById(\'custom_page-section3\').parentNode.removeChild(document.getElementById(\'custom_page-section3\'));'."\n";
						}
						$head_scripts .= '});</script>'."\n";
					}
					$head_scripts .= '<style type="text/css">.custom_page-post-holder{padding-bottom: 30px; padding-top: 30px; border-bottom: 1px solid #DDDDDD;}</style>'."\n";
				}
									
				$head_scripts .= "<script type=\"text/javascript\">"."\n";
				if ($embedded == true && !is_null($referer) && !is_null($id)) {	
					if (strtolower($referer) == strtolower($_SERVER['SERVER_NAME']) || strtolower($referer) == 'www.'.strtolower($_SERVER['SERVER_NAME'])) {
						$referer = $GLOBALS['app']->GetJawsURL() . "/libraries/iframes/domain1/iframetest_resize1.html";		        
					} else {	
						$referer = "http://".$referer."/19/img/agents/19/custom_img/common_files/iframetest_resize1.html";		        
					}
					$head_scripts .= "function sizeFrame".$display_id."() {
						var height = Math.max( document.body.offsetHeight, document.body.scrollHeight );	
						var fr".$display_id." = document.createElement('IFRAME');  
						fr".$display_id.".setAttribute('src', '".$referer."?height='+height+'&object=CustomPage_iframe_".$display_id."'); 
						fr".$display_id.".setAttribute('name', 'inneriframe".$display_id."');
						fr".$display_id.".setAttribute('id', 'inneriframe".$display_id."');
						fr".$display_id.".style.width = 1+'px';  
						fr".$display_id.".style.height = 1+'px';
						fr".$display_id.".style.display = 'none';
						document.body.appendChild(fr".$display_id.");	
					}";
				} else {
					$head_scripts .= "function sizeFrame".$display_id."() {
						return true;
					}";
				}
				$head_scripts .= "\n"."</script>"."\n";
				$GLOBALS['app']->Layout->AddHeadOther($head_scripts);
			//}
			foreach ($GLOBALS['app']->Layout->_Template->Blocks['layout']->InnerBlock as $name => $data) {
				if ($name == 'head') continue;
				$GLOBALS['app']->Layout->_Template->SetBlock('layout/'.$name);
				$gadgets = $adminModel->GetGadgetsInSection($name, $pageInfo['gadget'], $pageInfo['gadget_action'], $pageInfo['linkid'], $am_i_index);
				if (!is_array($gadgets)) continue;
				// Page-level layout_position overrides
				$page_sections = $model->GetAllSectionsOfPage($pageInfo['id'], $blog);
				$page_sort_orders = array();
				foreach ($page_sections as $page_section) {
					$page_sort_orders[$page_section['layout_id']] = $page_section['sort_order'];
				}
				foreach ($gadgets as $key => $val) {
					if (isset($page_sort_orders[$gadgets[$key]['id']])) {
						$gadgets[$key]['layout_position'] = $page_sort_orders[$gadgets[$key]['id']];
					}
				}
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
					$id = $gadget['id'];
					if (
						file_exists(JAWS_PATH . 'gadgets/'. $gadget['gadget']. '/'. 'LayoutHTML.php') ||
							file_exists(JAWS_PATH . 'gadgets/'. $gadget['gadget']. '/'. 'Actions.php')
					) {
						if ($GLOBALS['app']->Registry->Get('/gadgets/'.$gadget['gadget'].'/enabled') == 'true' &&
							$gadget['gadget'] != '[REQUESTEDGADGET]')
						{
							if (
								Jaws_Gadget::IsGadgetUpdated($gadget['gadget']) && 
								$GLOBALS['app']->Layout->IsDisplayable($pageInfo['gadget'], $pageInfo['gadget_action'], $gadget['display_when'], $am_i_index, $pageInfo['linkid'])
							) {
								$section_empty = false;
								$content .= $GLOBALS['app']->Layout->PutGadget($gadget['gadget'], $gadget['gadget_action'], $gadget['section']);
							}
						}
					}
				}
				$GLOBALS['app']->Layout->_Template->SetVariable('ELEMENT', $content);
				$GLOBALS['app']->Layout->_Template->ParseBlock('layout/'.$name);
			}
			// TODO: inline mode?
			$output = $GLOBALS['app']->Layout->Show(false, $embedded);
			
			$inputStr = str_replace("\n", '', $output);
			$delimeterLeft = "<head>";
			$delimeterRight = "</head>";
			$startLeft = strpos($inputStr, $delimeterLeft);
			$posLeft = ($startLeft+strlen($delimeterLeft));
			$posRight = strpos($inputStr, $delimeterRight, $posLeft);
			$existingHead = substr($inputStr, $posLeft, $posRight-$posLeft);
			$existingHead = str_replace('<meta', "\n".'<meta', $existingHead);
			$existingHead = str_replace('<script', "\n".'<script', $existingHead);
			$existingHead = str_replace('<base', "\n".'<base', $existingHead);
			$existingHead = str_replace('<title', "\n".'<title', $existingHead);
			$existingHead = str_replace('<link', "\n".'<link', $existingHead);
			$existingHead = str_replace('<style', "\n".'<style', $existingHead);
			$existingHead = str_replace('<!--', "\n".'<!--', $existingHead);
			
			$headContent = $GLOBALS['app']->Layout->GetHeaderContent($GLOBALS['app']->Layout->_HeadLink, $GLOBALS['app']->Layout->_ScriptLink, $GLOBALS['app']->Layout->_HeadMeta, $GLOBALS['app']->Layout->_HeadOther);
			
			$newHeadContent = str_replace("\n", '', $headContent);
			$newHeadContent = str_replace('<meta', "\n".'<meta', $newHeadContent);
			$newHeadContent = str_replace('<script', "\n".'<script', $newHeadContent);
			$newHeadContent = str_replace('<base', "\n".'<base', $newHeadContent);
			$newHeadContent = str_replace('<title', "\n".'<title', $newHeadContent);
			$newHeadContent = str_replace('<link', "\n".'<link', $newHeadContent);
			$newHeadContent = str_replace('<style', "\n".'<style', $newHeadContent);
			$newHeadContent = str_replace('<!--', "\n".'<!--', $newHeadContent);
			
			$replaceHeadContent = array_diff(explode("\n", $newHeadContent), explode("\n", $existingHead));
			$output = str_replace('</head>', implode("\n", $replaceHeadContent).'</head>', $output);
			return $output;
        }
		return _t('CUSTOMPAGE_ERROR_PAGE_NOT_FOUND');
	}
	
    /**
     * Displays a layout block of pages.
     *
     * @param	int 	$uid 	Owner ID (optional)
     * @param	boolean 	$embedded 	Embedded mode
     * @param	string 	$referer 	Embedding referer
     * @access 	public
     * @return 	string
     */
    function Display($uid = null, $embedded = false, $referer = null)
    {
        $model = $GLOBALS['app']->LoadGadget('CustomPage', 'Model');
        if (!is_null($uid)) {
			$pages = $model->GetCustomPageOfUserID($uid);
		} else {
			$pages = $model->GetPages(null, 'sm_description', 'ASC', false, 0);
        }
		if (Jaws_Error::IsError($pages)) {
            return _t('CUSTOMPAGE_ERROR_INDEX_NOT_LOADED');
        }

        //$xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$request =& Jaws_Request::getInstance();
		$embed_id  = time();

		$date = $GLOBALS['app']->loadDate();
        $tpl = new Jaws_Template('gadgets/CustomPage/templates/');
        $tpl->Load('normal.html');
        $tpl->SetBlock('index');
	    $tpl->SetVariable('actionName', 'Display');
        $tpl->SetVariable('title', '');
		if ($embedded == true && !is_null($referer) && isset($embed_id)) {
			$tpl->SetVariable('id', $embed_id);
		} else {
			$tpl->SetVariable('id', 'List');
		}
        //$tpl->SetVariable('link', $GLOBALS['app']->Map->GetURLFor('CustomPage', 'Index'));
        foreach ($pages as $page) {
            if (!is_null($uid)) {
				$tpl->SetBlock('index/item');
				$tpl->SetVariable('title', $page['sm_description']);
				$tpl->SetVariable('update_string',  _t('CUSTOMPAGE_LAST_UPDATE') . ': ');
				$tpl->SetVariable('updated', $date->Format($page['updated']));
				//$tpl->SetVariable('desc', strip_tags($page['content'], '<p><a><b><em><i><strong><div><table><tbody><thead><tr><td><span><font><hr /><br />'));
				if ($embedded == false) {
					$param = array('id' => !empty($page['fast_url']) ? $page['fast_url'] : $page['id']);
					$link = $GLOBALS['app']->Map->GetURLFor('CustomPage', 'Page', $param);
					$tpl->SetVariable('desc', (!empty($page['content']) ? (strlen(strip_tags($page['content'])) > 247 ? substr(strip_tags($page['content']),0,247)."&nbsp;<a href=\"".$link."\">... Read More</a>&nbsp;&nbsp;&nbsp;&nbsp;" : strip_tags($page['content'])."&nbsp;&nbsp;&nbsp;&nbsp;") : ''));
				} else {
					$base_url = $GLOBALS['app']->GetSiteURL().'/';
					$link = $base_url."index.php?gadget=CustomPage&action=EmbedCustomPage&id=".$page['id']."&mode=page&referer=".(!is_null($referer) ? $referer : "");
					$tpl->SetVariable('desc', (!empty($page['content']) ? (strlen(strip_tags($page['content'])) > 247 ? substr(strip_tags($page['content']),0,247)."&nbsp;<a href=\"".$link."\">... Read More</a>&nbsp;&nbsp;&nbsp;&nbsp;" : strip_tags($page['content'])."&nbsp;&nbsp;&nbsp;&nbsp;") : ''));
				}
				$tpl->SetVariable('link',  $link);
				$tpl->ParseBlock('index/item');
			} else {
				if ($page['active'] == 'Y' || $embedded == true) {
	                $tpl->SetBlock('index/item');
	                $tpl->SetVariable('title', $page['sm_description']);
					$tpl->SetVariable('update_string',  _t('CUSTOMPAGE_LAST_UPDATE') . ': ');
					$tpl->SetVariable('updated', $date->Format($page['updated']));
					if ($embedded == false) {
		                $param = array('id' => !empty($page['fast_url']) ? $page['fast_url'] : $page['id']);
						$link = $GLOBALS['app']->Map->GetURLFor('CustomPage', 'Page', $param);
						$tpl->SetVariable('desc', (!empty($page['content']) ? (strlen(strip_tags($page['content'])) > 247 ? substr(strip_tags($page['content']),0,247)."&nbsp;<a href=\"".$link."\">... Read More</a>&nbsp;&nbsp;&nbsp;&nbsp;" : strip_tags($page['content'])."&nbsp;&nbsp;&nbsp;&nbsp;") : ''));
					} else {
				        $base_url = $GLOBALS['app']->GetSiteURL().'/';
		                $link = $base_url."index.php?gadget=CustomPage&action=EmbedCustomPage&id=".$page['id']."&mode=page&referer=".(!is_null($referer) ? $referer : "");
						$tpl->SetVariable('desc', (!empty($page['content']) ? (strlen(strip_tags($page['content'])) > 247 ? substr(strip_tags($page['content']),0,247)."&nbsp;<a href=\"".$link."\">... Read More</a>&nbsp;&nbsp;&nbsp;&nbsp;" : strip_tags($page['content'])."&nbsp;&nbsp;&nbsp;&nbsp;") : ''));
					}
					$tpl->SetVariable('link',  $link);
	                $tpl->ParseBlock('index/item');
	            }
			}
        }
		if ($embedded == true && !is_null($referer) && isset($embed_id)) {	
			$tpl->SetBlock('index/embedded');
			$tpl->SetVariable('id', $embed_id);		        
			if (strtolower($referer) == strtolower($_SERVER['SERVER_NAME'])) {
				$tpl->SetVariable('referer', $GLOBALS['app']->GetJawsURL() . "/libraries/iframes/domain1/iframetest_resize1.html");		        
			} else {	
				$tpl->SetVariable('referer', "http://".$referer."/19/img/agents/19/custom_img/common_files/iframetest_resize1.html");		        
			}
			$tpl->ParseBlock('index/embedded');
		} else {
			$tpl->SetBlock('index/not_embedded');
			$tpl->SetVariable('id', $embed_id);		        
			$tpl->ParseBlock('index/not_embedded');
		}
        $tpl->ParseBlock('index');

        return $tpl->Get();
    }

    /**
     * Displays table of contents of page.
     *
     * @param	int 	$id 	Page ID (optional)
     * @param	boolean 	$embedded 	Embedded mode
     * @param	string 	$referer 	Embedding referer
     * @access 	public
     * @return 	string
     */
    function ShowTableOfContents($id = null, $embedded = false, $referer = null)
    {
        $model = $GLOBALS['app']->LoadGadget('CustomPage', 'Model');
		$output_html .= '<!-- START Table of Contents -->'."\n";
		$ShowTableContents = 0;
		if ($ShowTableContents == 1 || (isset($_GET["debug"]) && $_GET["debug"] == "1")) {
			$output_html .= '<table border="0" cellpadding="10" cellspacing="0" class="color_bkgnd_primary">
				<tr>
					<td align="center"><b>Contents</b>
					<br /><img src="images/blank.gif" width="250" height="1" border="0"></td>
				</tr>
				<tr>
					<td align="left">
			';
			$posts = $model->GetAllPostsOfPage($id);
			if (Jaws_Error::IsError($posts)) {
				return new Jaws_Error($posts->GetMessage(), _t('CUSTOMPAGE_NAME'));
			}
			$i = 0;
			foreach($posts as $post) {		            
				if ($i != 0) {
					$output_html .= '<br />';
				}
				if ($post['title'] != "") { 
					$output_html .= '<font size="1"><a href="#'.$GLOBALS['app']->UTF8->str_replace("'", "\\\'", strip_tags($post['title'])).'">'.($i+1).'.&nbsp;'.$post['title'].'</a></font>';
				}
				$i++;
			}
			
			$output_html .= '	</td>
				</tr>
			</table>'."\n";
		}

		$output_html .= '<!-- END Table of Contents -->'."\n";
		return $output_html;
	}
	
    /**
     * Displays more places of page.
     *
     * @param 	int	 $id	Page ID (optional)
     * @param	boolean 	$embedded 	Embedded mode
     * @param	string 	$referer 	Embedding referer
     * @access 	public
     * @return 	string
     */
    function ShowMorePlaces($id = null, $embedded = false, $referer = null)
    {
        $model = $GLOBALS['app']->LoadGadget('CustomPage', 'Model');
		// send children records
		$children = $model->GetAllSubPagesOfPage($id);
		if (Jaws_Error::IsError($children)) {
			return new Jaws_Error($children->GetMessage(), _t('CUSTOMPAGE_NAME'));
		}
		$TDStyle = '';
		$TDAlign = 'left';
		$output_html = '<!-- START More Places Links -->'."\n";
		$TOC_ShowPlaces = 0;
		// show places
		if ($TOC_ShowPlaces == 1) {

			$output_html .= '<table class="color_bkgnd_primary" border="0" cellspacing="0" cellpadding="0" width="100%" id="MorePlaces" style="display:none; margin-bottom: 20px;">
				<tr>
					<td colspan="100%" align="right" style="padding-bottom: 10px; padding-top: 3px;"> [ <b><font size="1" color="#3366CC"><a href="#ShowPlaces"  onMouseOver="this.style.cursor=\'hand\'" onClick="toggle(\'MorePlaces\');">Hide This</a></font></b> ]&nbsp;&nbsp;</td>
				</tr>'."\n";
						
			$ColCnt = 3;
			foreach($children as $child) {		            
				if (isset($child['id'])) {					            
					if ($ColCnt >= (int)$page['pagecol'] && $i != 2) {
						$ColCnt = 1;
						if ($i > 0) {
							$output_html .= '</tr>';
						}
						$output_html .= '<td Height="0%"><img border="0" src="images/blank.gif"></td>';
						if ($i < (count($children)-1)) {
							$output_html .= '<tr>';
						}
					} else {
						$ColCnt = ($ColCnt + 1);
					}
					if ($child['title'] != "") {
						$TDAlign="left";
							$output_html .= '<td align="'.$TDAlign.'" style="padding-left: 10px; '.$TDStyle.';">
								<img border="0" src="images/ICON_sm_arrow_gray.gif">&nbsp;<a href="index.php?page/'.$child['fast_url'].'.html">'.$child['title'].'</a>
							</td>'."\n";
					}
					if ($i == (count($children)-1)) {
						$output_html .= '</tr>';
					}
				}
				$i++;
			}
			$output_html .= '<tr>
					<td colspan="100%" align="right">&nbsp;&nbsp;</td>
				</tr>
			</table>'."\n";
		}
		$output_html .= '<!-- END More Places Links -->'."\n";
		return $output_html;
	}
	
    /**
     * Displays an individual post.
     *
     * @param	int 	$id 	Post ID (optional)
     * @param	boolean 	$embedded 	Embedded mode
     * @param	string 	$referer 	Embedding referer
     * @access 	public
     * @return 	string
     */
    function ShowPost($id = null, $embedded = false, $referer = null, $blog = false)
    {
		require_once JAWS_PATH . 'include/Jaws/Image.php';
		if (!isset($GLOBALS['app']->Layout)) {
			require_once JAWS_PATH . 'include/Jaws/Layout.php';
			$GLOBALS['app']->Layout = new Jaws_Layout();
		}
        
		$GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');
		
		$GLOBALS['app']->Layout->AddScriptLink('libraries/carousel/dist/carousel.js');
		$GLOBALS['app']->Layout->AddScriptLink('libraries/slideshow/slideshow-min.js');
		$GLOBALS['app']->Layout->AddScriptLink('libraries/js/global2.js');
		$GLOBALS['app']->Layout->AddHeadLink('libraries/carousel/themes/carousel/prototype-ui.css', 'stylesheet', 'text/css');
		$GLOBALS['app']->Layout->AddHeadLink('gadgets/CustomPage/resources/style.css', 'stylesheet', 'text/css');
		
		$date = $GLOBALS['app']->loadDate();
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $model = $GLOBALS['app']->LoadGadget('CustomPage', 'Model');
        $customPageHTML = $GLOBALS['app']->LoadGadget('CustomPage', 'HTML');
		
		$post = $model->GetPost($id, true);
		if (Jaws_Error::IsError($post)) {
            return $post;
        } else {
			// TODO: More advanced control over embedding (active/inactive) pages
			if (JAWS_SCRIPT == 'index' && $post['active'] == 'N' && $embedded === false && is_null($referer)) {
				return new Jaws_Error(_t('CUSTOMPAGE_ERROR_POST_NOT_FOUND'), _t('CUSTOMPAGE_NAME'));
			}
					
			$posttpl = new Jaws_Template('gadgets/CustomPage/templates/');
			$posttpl->Load('Post.html');
			$TDAlign = ($post['layout'] == 0 ? 'left' : 'right');
			$title = strip_tags($post['title']);
			$content = '';
			$post['description'] = $customPageHTML->ParseText(str_replace(array("\r","\n"), '', $post['description']), 'CustomPage');
			if (!empty($post['description'])) {
				$content = $post['description'];
			}
			$post['image_code'] = htmlspecialchars_decode($post['image_code']);
			$post['updated'] = $date->Format($post['updated']);
			$splash_html = '';
			$gadget_html = '';
			//$i++;
				
			if ($post['gadget'] == "text") {
				$posttpl->SetBlock('post_text');
				$posttpl->SetVariable('id', $post['id']);
				$posttpl->SetVariable('timestamp', $post['updated']);
				
				if ($post['image'] != "" || $post['image_code'] != "") {
					// FIXME: Put HTML in template
					// Splash Panels for each Post
					$splashInfo = $model->GetSplashPanelsOfPage($post['id']);
					if (!count($splashInfo) <= 0 && isset($splashInfo[0]['id'])) {
						reset($splashInfo);
						$GLOBALS['app']->Layout->AddScriptLink('libraries/carousel/dist/carousel.js');
						$GLOBALS['app']->Layout->AddHeadLink('libraries/carousel/themes/carousel/prototype-ui.css', 'stylesheet', 'text/css');

						$splash_html .= '<div class="carousel_navigation" id="carousel_navigation'.$post['id'].'" style="padding-bottom: 6px; font-size: 1.2em; font-weight: bold; text-align: center"></div>
						<div class="horizontal_carousel" id="horizontal_carousel'.$post['id'].'">
						  <script>
							$(\'horizontal_carousel'.$post['id'].'\').style.visibility = \'hidden\';
						  </script>
						  <div class="previous_button" id="previous_button'.$post['id'].'"></div>  
						  <div class="carousel_container" id="carousel_container'.$post['id'].'">
							<div class="carousel_holder" id="carousel_holder'.$post['id'].'">
								<div class="carousel_item" id="carousel_'.$post['id'].'item1">'."\n";
						if (strpos($post['image'],".swf") !== false) {
							// Flash file not supported
						} else if (substr($post['image'],0,7) == "GADGET:") {
							$splash_html .= '<div class="carousel_itemGadget" id="carousel_'.$post['id'].'item1Gadget">
							__'.$post['image'].'__
							</div>'."\n";
						} else if ($post['image_code'] != "") {
							$splash_html .= '<div class="carousel_itemCode" id="carousel_'.$post['id'].'item1Code">
							'.$post['image_code'].'
							</div>'."\n";
						} else if ($post['image'] != "") {
							$post['image'] = $xss->parse(strip_tags($post['image']));
							if (substr(strtolower($post['image']), 0, 4) == "http") {
								if (substr(strtolower($post['image']), 0, 7) == "http://") {
									$main_image_src = explode('http://', $post['image']);
									foreach ($main_image_src as $img_src) {
										if (!empty($img_src)) {
											$main_image_src = 'http://'.$img_src;
											break;
										}
									}
								} else {
									$main_image_src = explode('https://', $post['image']);
									foreach ($main_image_src as $img_src) {
										if (!empty($img_src)) {
											$main_image_src = 'https://'.$img_src;
											break;
										}
									}
								}
								if (strpos(strtolower($main_image_src), 'data/files/') !== false) {
									$main_image_src = 'image_thumb.php?uri='.urlencode($main_image_src);
								}
							} else {
								$medium = Jaws_Image::GetMediumPath($post['image']);
								if (file_exists(JAWS_DATA . 'files'.$medium)) {
									$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$medium;
								} else if (file_exists(JAWS_DATA . 'files'.$post['image'])) {
									$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$post['image'];
								}
							}
							if (!empty($main_image_src)) {
								$splash_html .= '<a href="javascript:void(0);" onclick="window.open(\''.$main_image_src.'\');">
								<img id="carousel_'.$post['id'].'item1Image" class="carousel_itemImage"';
								if ($post['image_width'] > 0) {
									$splash_html .= ' width="'.$post['image_width'].'"';
								} else if ($post['image_height'] > 0) { 
									$splash_html .= ' height="'.$post['image_height'].'"';
								}
								$splash_html .= ' border="0" src="'.$main_image_src.'" alt="'.htmlentities($title).'"></a>'."\n";
							}
						}
						$splash_html .= '</div>'."\n";
						$i2 = 0;
						foreach($splashInfo as $s) {		            
							$s['id'] = $s['id'];
							$s['sort_order'] = $s['sort_order'];
							$s['linkid'] = $s['linkid'];
							$s['image'] = $s['image'];
							$s['splash_width'] = $s['splash_width'];
							$s['splash_height'] = $s['splash_height'];
							$s['code'] = $customPageHTML->ParseText($s['code'], 'CustomPage');
							$s['code'] = htmlspecialchars_decode($s['code']);
							$splash_html .= '<div class="carousel_item" id="carousel_'.$post['id'].'item'.($i2+2).'">'."\n";
							if ($s['code'] != "") {
								$splash_html .= '<div class="carousel_itemCode" id="carousel_'.$post['id'].'item'.($i2+2).'Code">
								'.$s['code'].'
								</div>'."\n";
							}
							if (substr($s['image'], 0, 7) == "GADGET:") {
								$splash_html .= '<div class="carousel_itemGadget" id="carousel_'.$post['id'].'item'.($i2+2).'Gadget">
								__'.$s['image'].'__
								</div>'."\n";
								//Hold.. if we dont have a selected gadget?.. like no gadgets?
							} else if ($s['image'] != "") {
								$s['image'] = $xss->parse(strip_tags($s['image']));
								if (substr(strtolower($s['image']), 0, 4) == "http") {
									if (substr(strtolower($s['image']), 0, 7) == "http://") {
										$main_image_src = explode('http://', $s['image']);
										foreach ($main_image_src as $img_src) {
											if (!empty($img_src)) {
												$main_image_src = 'http://'.$img_src;
												break;
											}
										}
									} else {
										$main_image_src = explode('https://', $s['image']);
										foreach ($main_image_src as $img_src) {
											if (!empty($img_src)) {
												$main_image_src = 'https://'.$img_src;
												break;
											}
										}
									}
									if (strpos(strtolower($main_image_src), 'data/files/') !== false) {
										$main_image_src = 'image_thumb.php?uri='.urlencode($main_image_src);
									}
								} else {
									$medium = Jaws_Image::GetMediumPath($s['image']);
									if (file_exists(JAWS_DATA . 'files'.$medium)) {
										$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$medium;
									} else if (file_exists(JAWS_DATA . 'files'.$s['image'])) {
										$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$s['image'];
									}
								}
								if (!empty($main_image_src)) {
									$splash_html .= '<a href="javascript:void(0);" onclick="window.open(\''.$main_image_src.'\');">
									<img id="carousel_'.$post['id'].'item'.($i2+2).'Image" class="carousel_itemImage"';
									if ($s['splash_width'] > 0) {
										$splash_html .= ' width="'.$s['splash_width'].'"';
									} else if ($s['splash_height'] > 0) { 
										$splash_html .= ' height="'.$s['splash_height'].'"';
									}
									$splash_html .= ' border="0" src="'.$main_image_src.'"></a>'."\n";
								}
							}				
							$splash_html .= '</div>'."\n";
							$i2++;
						}
						$splash_html .= '	</div>
						  </div>
						  <div class="next_button" id="next_button'.$post['id'].'"></div>
						</div>
						<script type="text/javascript">
					// <![CDATA[
						var theEmbeds'.$post['id'].'= new Array();
						var n'.$post['id'].'= 0;
						var hCarousel'.$post['id'].'= null;
						var x'.$post['id'].'= null;
						var button_widths'.$post['id'].'= 0;
						document.observe("dom:loaded",function(){
							//var holder=$("carousel_holder'.$post['id'].'");
							//var thisChild=holder.firstChild;
							var prev_button_width=parseInt($("previous_button'.$post['id'].'").offsetWidth);
							var next_button_width=parseInt($("next_button'.$post['id'].'").offsetWidth);
							$("next_button'.$post['id'].'").onmouseover = function(){
								stopscroll'.$post['id'].'();
							};
							$("next_button'.$post['id'].'").onmouseout = function(){
								x'.$post['id'].'=setInterval("startscroll'.$post['id'].'()", 10000);
							};
							$("previous_button'.$post['id'].'").onmouseover = function(){
								stopscroll'.$post['id'].'();
							};
							$("previous_button'.$post['id'].'").onmouseout = function(){
								x'.$post['id'].'=setInterval("startscroll'.$post['id'].'()", 10000);
							};
							button_widths'.$post['id'].'=(prev_button_width+next_button_width)+30;
							var children = $A($("carousel_holder'.$post['id'].'").childNodes);
							children.each(function(thisChild) {
							//while(thisChild!=holder.lastChild){
								if(thisChild.nodeType==1){
									thisChild.onmouseover = function(){
										stopscroll'.$post['id'].'();
									};
									thisChild.onmouseout = function(){
										x'.$post['id'].'=setInterval("startscroll'.$post['id'].'()", 10000);
									};
									var thisEmbeds=new Array();
									thisEmbeds=thisChild.getElementsByTagName("iframe");
									if(thisEmbeds.length){
										theEmbeds'.$post['id'].'[n'.$post['id'].']=thisEmbeds[0].src;
										thisEmbeds[0].height=parseInt($("horizontal_carousel'.$post['id'].'").offsetHeight);
										thisEmbeds[0].style.height=parseInt($("horizontal_carousel'.$post['id'].'").offsetHeight)+"px";
										if(thisChild.id!="carousel_'.$post['id'].'item1"){
											thisChild.style.visibility="hidden";
											for(i=0;i<thisEmbeds.length;i++){
												thisEmbeds[i].width=0;
												thisEmbeds[i].style.width="0px";
												thisEmbeds[i].src="";
											};
										};
									};
									n'.$post['id'].'++
								};
								//thisChild=thisChild.nextSibling;
							});
							$("carousel_navigation'.$post['id'].'").innerHTML+=\'<img src="images/carousel_nav_left.png" border="0" /><a id="carousel_'.$post['id'].'nav0" href="javascript: void(0);" onclick="hCarousel'.$post['id'].'.scrollTo(0);" style="text-decoration: none;"><img src="images/carousel_nav_on.png" border="0" /></a>';
						for ($n = 0; $n < count($splashInfo); $n++) {
							$splash_html .= '<a id="carousel_'.$post['id'].'nav'.($n+1).'" href="javascript: void(0);" onclick="hCarousel'.$post['id'].'.scrollTo('.($n+1).');" style="text-decoration: none;"><img src="images/carousel_nav_off.png" border="0" /></a>';
						}
						$splash_html .= '<img src="images/carousel_nav_right.png" border="0" />\';
						});
						function startscroll'.$post['id'].'(){
							var nextItem=hCarousel'.$post['id'].'.currentIndex()+1;
							if(nextItem>'. '1' .'){
								hCarousel'.$post['id'].'.scrollTo(0);
							}else{
								hCarousel'.$post['id'].'.scrollTo(nextItem);
							};
						};
						function stopscroll'.$post['id'].'(){
							clearInterval(x'.$post['id'].');
						};
						function runTest'.$post['id'].'(){
							$("horizontal_carousel'.$post['id'].'").style.visibility="visible";
							updateCarouselSize'.$post['id'].'();
							hCarousel'.$post['id'].'=new UI.Carousel("horizontal_carousel'.$post['id'].'",{container:".carousel_container",scrollInc:"auto"});
							x'.$post['id'].'=setInterval("startscroll'.$post['id'].'()", 10000);
							new UI.Carousel("horizontal_carousel'.$post['id'].'").observe("scroll:started",function(){
								//var holder=$("carousel_holder'.$post['id'].'");
								//var thisChild=holder.firstChild;
								var children = $A($("carousel_holder'.$post['id'].'").childNodes);
								children.each(function(thisChild) {
								//while(thisChild!=holder.lastChild){
									if(thisChild.nodeType==1){
										var thisEmbeds=new Array();
										thisEmbeds=thisChild.getElementsByTagName("iframe");
										if(thisEmbeds.length){
											thisChild.style.visibility="hidden";
											for(i=0;i<thisEmbeds.length;i++){
												thisEmbeds[i].style.visibility="hidden";
												thisEmbeds[i].width=0;
												thisEmbeds[i].style.width="0px";
												thisEmbeds[i].src="";
											};
										} else {
											thisChild.style.visibility="visible";
										}
									};
									//thisChild=thisChild.nextSibling;
								});
							});
							new UI.Carousel("horizontal_carousel'.$post['id'].'").observe("scroll:ended",function(){
								for(t=0;t<n'.$post['id'].'+1;t++){
									if(t!=hCarousel'.$post['id'].'.currentIndex()){
										if ($("carousel_'.$post['id'].'item"+(t+1))){
											$("carousel_'.$post['id'].'item"+(t+1)).style.visibility="hidden";
										};
										if($("carousel_'.$post['id'].'nav"+t)){
											$("carousel_'.$post['id'].'nav"+t).src="images/carousel_nav"+t+"_off.gif";
										};
									}else{
										if ($("carousel_'.$post['id'].'item"+(t+1))){
											var thisEmbeds=new Array();
											thisEmbeds=$("carousel_'.$post['id'].'item"+(t+1)).getElementsByTagName("iframe");
											if(thisEmbeds.length){
												thisEmbeds[0].src=theEmbeds'.$post['id'].'[t];
												thisEmbeds[0].width=(parseInt($("horizontal_carousel'.$post['id'].'").parentNode.offsetWidth)-button_widths'.$post['id'].');
												thisEmbeds[0].style.width=(parseInt($("horizontal_carousel'.$post['id'].'").parentNode.offsetWidth)-button_widths'.$post['id'].')+"px";
												thisEmbeds[0].style.visibility="visible";
											};
											$("carousel_'.$post['id'].'item"+(t+1)).style.visibility="visible";
										};
										if($("carousel_'.$post['id'].'nav"+t)){
											$("carousel_'.$post['id'].'nav"+t).src="images/carousel_nav"+t+"_on.gif";
										};
									};
								};
							});
						};
						function resized'.$post['id'].'(){
							updateCarouselSize'.$post['id'].'();
							if(hCarousel'.$post['id'].'){
								hCarousel'.$post['id'].'.updateSize();
							}
						};
						function updateCarouselSize'.$post['id'].'(){
							if($("horizontal_carousel'.$post['id'].'").parentNode){
								var dim=parseInt($("horizontal_carousel'.$post['id'].'").parentNode.offsetWidth);
							}else{
								var dim=document.viewport.getDimensions();
								dim=dim.width;
							};
							$("horizontal_carousel'.$post['id'].'").style.width=dim+"px";
							$$("#horizontal_carousel'.$post['id'].' .carousel_container").first().style.width=(dim-button_widths'.$post['id'].')+"px";
							var height=parseInt($("horizontal_carousel'.$post['id'].'").offsetHeight);
							$("carousel_container'.$post['id'].'").style.height=height+"px";
							if($$(".carousel_'.$post['id'].'itemImage")){
								$$(".carousel_'.$post['id'].'itemImage").each(function(item){
									item.style.height=height+"px";
								});
							};
							if($$(".carousel_'.$post['id'].'itemCode")){
								$$(".carousel_'.$post['id'].'itemCode").each(function(item){
									item.style.height=height+"px";
									item.style.width=(dim-button_widths'.$post['id'].')+"px";
								});
							};
							//var holder=$("carousel_holder'.$post['id'].'");
							//var thisChild=holder.firstChild;
							var children = $A($("carousel_holder'.$post['id'].'").childNodes);
							children.each(function(thisChild) {
							//while(thisChild!=holder.lastChild){
								if(thisChild.nodeType==1){
									thisChild.width=(dim-button_widths'.$post['id'].');
									thisChild.style.width=(dim-button_widths'.$post['id'].')+"px";
									thisChild.height=height;thisChild.style.height=height+"px";
									thisChild.style.minHeight=height+"px";
									//secondChild=thisChild.firstChild;
									var secondChildren = $A(thisChild.childNodes);
									secondChildren.each(function(secondChild) {
									//while(secondChild!=thisChild.lastChild){
										if(secondChild.nodeType==1){
											secondChild.width=(dim-button_widths'.$post['id'].');
											secondChild.style.width=(dim-button_widths'.$post['id'].')+"px";
											secondChild.height=height;
											secondChild.style.height=height+"px";
											secondChild.style.minHeight=height+"px";
										};
										//secondChild=secondChild.nextSibling;
									});
								};
								//thisChild=thisChild.nextSibling;
							});
						};
						Event.observe(window,"load",runTest'.$post['id'].');Event.observe(window,"resize",resized'.$post['id'].');
					// ]]>
						</script>'."\n";
					} else {
						if (substr($post['image'], 0,7) == "GADGET:") { 
							$splash_html .= '__'.$post['image'].'__'."\n";
						} else if ($post['image'] != "") {
							$post['image'] = $xss->parse(strip_tags($post['image']));
							if (substr(strtolower($post['image']), 0, 4) == "http") {
								if (substr(strtolower($post['image']), 0, 7) == "http://") {
									$main_image_src = explode('http://', $post['image']);
									foreach ($main_image_src as $img_src) {
										if (!empty($img_src)) {
											$main_image_src = 'http://'.$img_src;
											break;
										}
									}
								} else {
									$main_image_src = explode('https://', $post['image']);
									foreach ($main_image_src as $img_src) {
										if (!empty($img_src)) {
											$main_image_src = 'https://'.$img_src;
											break;
										}
									}
								}
								if (strpos(strtolower($main_image_src), 'data/files/') !== false) {
									$main_image_src = 'image_thumb.php?uri='.urlencode($main_image_src);
								}
							} else {
								$medium = Jaws_Image::GetMediumPath($post['image']);
								if (file_exists(JAWS_DATA . 'files'.$medium)) {
									$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$medium;
								} else if (file_exists(JAWS_DATA . 'files'.$post['image'])) {
									$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$post['image'];
								}
							}
							if (!empty($main_image_src)) {
								$splash_html .= '<p align="left">';
								if ($post['url'] == "javascript:void(0);") {
									$splash_html .= '<a href="javascript:void(0);" onclick="window.open(\''.$main_image_src.'\',\'\',\'scrollbars=no\')">';
								} else {
									$splash_html .= '<a href="'.$post['url'].'" target="'.$post['url_target'].'">';
								}
								$splash_html .= '<img id="custom_page-post-image-'.$post['id'].'" class="custom_page-post-image"';
								if ($post['image_width'] > 0) {
									$splash_html .= ' width="'.$post['image_width'].'"';
								} else if ($post['image_height'] > 0) { 
									$splash_html .= ' height="'.$post['image_height'].'"';
								}
								$splash_html .= ' border="0" src="'.$main_image_src.'" alt="'.htmlentities($title).'" align="'.$TDAlign.'"></a>
								</p>'."\n";
							}
						} else {
							$splash_html .= $post['image_code'];
						}
					}
				}
				//if ($i == 1) {
					$posttpl->SetBlock('post_text/table_start');
					$posttpl->SetVariable('id', $post['id']);
					$posttpl->ParseBlock('post_text/table_start');
				//}
				if (!empty($title)) { 
					$posttpl->SetBlock('post_text/title');
					$posttpl->SetVariable('id', $post['id']);
					$posttpl->SetVariable('title', $title);
					$posttpl->SetVariable('timestamp', $post['updated']);
					$posttpl->ParseBlock('post_text/title');
				} 
				$posttpl->SetBlock('post_text/splash');
				$posttpl->SetVariable('splash_html', $splash_html);
				$posttpl->ParseBlock('post_text/splash');
				$posttpl->SetBlock('post_text/description');
				$posttpl->SetVariable('content', $content);
				$posttpl->ParseBlock('post_text/description');
				/*
				if ($section_pagecnt[$sect] > 1) {
					if ($ColCnt == $section_pagecnt[$sect]) {
						$posttpl->SetBlock('post_text/row_end');
						$posttpl->ParseBlock('post_text/row_end');
						$posttpl->SetBlock('post_text/anchor');
						$posttpl->SetVariable('anchor_title', $post['id']);
						$posttpl->ParseBlock('post_text/anchor');
						$ColCnt = 1;
					} else {
						if ($i == 1) {
							$posttpl->SetBlock('post_text/row_start');
							$posttpl->ParseBlock('post_text/row_start');
						} else {
							$ColCnt = $ColCnt + 1;
						}
					}
				} else {
				*/
					$ColCnt = 1;
					/*
					if ($i > 0 && $i != $section_posts[$sect]) {
						$posttpl->SetBlock('post_text/row_end');
						$posttpl->ParseBlock('post_text/row_end');
					}
					*/
					$posttpl->SetBlock('post_text/anchor');
					$posttpl->SetVariable('anchor_title', $post['id']);
					$posttpl->ParseBlock('post_text/anchor');
					if (!empty($title) || $post['description'] != "") { 
						if ($blog === true) {
							$posttpl->SetBlock('post_text/header');
							$posttpl->SetVariable('anchor_title', $GLOBALS['app']->UTF8->str_replace("'", "\\\'", $title));
							$posttpl->SetVariable('title', $post['title']);
							$posttpl->SetVariable('id', $post['id']);
							$posttpl->SetVariable('timestamp', $post['updated']);
							$posttpl->ParseBlock('post_text/header');
						}		
			
					}
					/*
					if ($i <= $section_posts[$sect]) {
						$posttpl->SetBlock('post_text/row_start');
						$posttpl->ParseBlock('post_text/row_start');
					}
				}
				if ($i == $section_posts[$sect]) {
					$posttpl->SetBlock('post_text/row_end');
					$posttpl->ParseBlock('post_text/row_end');
					*/
					$posttpl->SetBlock('post_text/table_end');
					$posttpl->ParseBlock('post_text/table_end');
				//}
				$posttpl->ParseBlock('post_text');
			} else {
				$image_a = $GLOBALS['app']->UTF8->str_replace(' ', '_', $post['image']);
				$image_a = $GLOBALS['app']->UTF8->str_replace('(', '_', $image_a);
				$image_a = $GLOBALS['app']->UTF8->str_replace(')', '_', $image_a);
				
				$posttpl->SetBlock('post_gadget');
				$posttpl->SetVariable('id', $post['id']);
				$posttpl->SetVariable('gadget_action', $image_a);
				$posttpl->SetVariable('type', 'gadget');
				$layout_html = '';
				$layoutGadget = $GLOBALS['app']->LoadGadget($post['gadget'], 'LayoutHTML');
				if (!Jaws_Error::isError($layoutGadget)) {
					$GLOBALS['app']->Registry->LoadFile($post['gadget']);
					if (strpos($post['image'], '(') === false) {
						if (method_exists($layoutGadget, $post['image'])) {
							$layout_html = $layoutGadget->$post['image']();
						} elseif (isset($GLOBALS['log'])) {
							$GLOBALS['log']->Log(JAWS_LOG_ERR, "Action ".$post['image']." in ".$post['gadget']."'s LayoutHTML doesn't exist.");
						}
					} else {
						preg_match_all('/^([a-z0-9]+)\((.*?)\)$/i', $post['image'], $matches);
						if (isset($matches[1][0]) && isset($matches[2][0])) {
							if (isset($matches[1][0])) {
								if (method_exists($layoutGadget, $matches[1][0])) {
									$layout_html = $layoutGadget->$matches[1][0]($matches[2][0]);
								} elseif (isset($GLOBALS['log'])) {
									$GLOBALS['log']->Log(JAWS_LOG_ERR, "Action ".$matches[1][0]." in ".$post['gadget']."'s LayoutHTML doesn't exist.");
								}
							}
						}
					}
				} else {
					if (isset($GLOBALS['log'])) {
						$GLOBALS['log']->Log(JAWS_LOG_ERR, $post['gadget'] ." is missing the LayoutHTML. Jaws can't execute Layout " .
											 "actions if the file doesn't exists");
					}
				}
				unset($layoutGadget);
				
				if (Jaws_Error::isError($layout_html)) {
					if (isset($GLOBALS['log'])) {
						$GLOBALS['log']->Log(JAWS_LOG_ERR, $layout_html->GetMessage());
					}
					$layout_html = '';
				}
				$posttpl->SetVariable('content', $layout_html);
				/*
				if ($i == 1) {
					$posttpl->SetBlock('post_gadget/table_start1');
					$posttpl->ParseBlock('post_gadget/table_start1');
				}
				$posttpl->SetBlock('post_gadget/table_end');
				$posttpl->ParseBlock('post_gadget/table_end');
				if ($i < $section_posts[$sect]) {
					$posttpl->SetBlock('post_gadget/table_start2');
					$posttpl->ParseBlock('post_gadget/table_start2');
				}
				*/
				$posttpl->ParseBlock('post_gadget');
			}
			if ($post['rss_url'] != "") {
				$posttpl->SetBlock('post_gadget');
				$posttpl->SetVariable('id', $post['id']);
				$posttpl->SetVariable('type', 'rss');
				$posttpl->SetVariable('content', '__RSS_ITEMS_POST_'.$post['id'].'__');
				/*
				if ($i == 1) {
					$posttpl->SetBlock('post_gadget/table_start1');
					$posttpl->ParseBlock('post_gadget/table_start1');
				}
				$posttpl->SetBlock('post_gadget/table_end');
				$posttpl->ParseBlock('post_gadget/table_end');
				if ($i < $section_posts[$sect]) {
					$posttpl->SetBlock('post_gadget/table_start2');
					$posttpl->ParseBlock('post_gadget/table_start2');
				}	
				*/
				$posttpl->ParseBlock('post_gadget');
			}
			return $posttpl->Get();
		}
    }
	
}