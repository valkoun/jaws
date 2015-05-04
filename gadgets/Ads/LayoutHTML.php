<?php
/**
 * Ads Gadget (layout actions in client side)
 *
 * @category   GadgetLayout
 * @package    Ads
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class AdsLayoutHTML
{
    /**
     * Loads layout actions
     *
     * @access private
     */
    function LoadLayoutActions($limit = null, $offset = null)
    {
        $actions = array();
		if (is_null($offset) || $offset == 0) {
			$actions['ShowTwoButtons'] = array(
				'mode' => 'LayoutAction',
				'name' => _t('ADS_LAYOUT_TWOBUTTONS_TITLE'),
				'desc' => _t('ADS_LAYOUT_TWOBUTTONS_DESCRIPTION')
			);
			$actions['ShowFourButtons'] = array(
				'mode' => 'LayoutAction',
				'name' => _t('ADS_LAYOUT_FOURBUTTONS_TITLE'),
				'desc' => _t('ADS_LAYOUT_FOURBUTTONS_DESCRIPTION')
			);
			$actions['ShowTwoBlocks'] = array(
				'mode' => 'LayoutAction',
				'name' => _t('ADS_LAYOUT_TWOBLOCKS_TITLE'),
				'desc' => _t('ADS_LAYOUT_TWOBLOCKS_DESCRIPTION')
			);
			$actions['ShowFourBlocks'] = array(
				'mode' => 'LayoutAction',
				'name' => _t('ADS_LAYOUT_FOURBLOCKS_TITLE'),
				'desc' => _t('ADS_LAYOUT_FOURBLOCKS_DESCRIPTION')
			);
			$actions['ShowLeaderBoard'] = array(
				'mode' => 'LayoutAction',
				'name' =>  _t('ADS_LAYOUT_LEADERBOARD_TITLE'),
				'desc' => _t('ADS_LAYOUT_LEADERBOARD_DESCRIPTION')
			);
			$actions['ShowBanner'] = array(
				'mode' => 'LayoutAction',
				'name' =>  _t('ADS_LAYOUT_BANNER_TITLE'),
				'desc' => _t('ADS_LAYOUT_BANNER_DESCRIPTION')
			);
			$actions['DisplayDefault'] = array(
				'mode' => 'LayoutAction',
				'name' =>  _t('ADS_LAYOUT_DEFAULT_TITLE'),
				'desc' => _t('ADS_LAYOUT_DEFAULT_DESCRIPTION')
			);
		}
		
		$model = $GLOBALS['app']->LoadGadget('Ads', 'Model');
		//if ($GLOBALS['app']->Session->GetPermission('Ads', 'ManageAds')) {
			$galleries = $model->GetAds($limit, 'created', 'DESC', $offset, 0);
		/*
		} else {
			$galleries = $model->GetAdsOfUserID($GLOBALS['app']->Session->GetAttribute('user_id'));			
		}
		*/

		if (!Jaws_Error::isError($galleries)) {
            foreach ($galleries as $gallery) {
				if ($gallery['ownerid'] == 0) {
					$actions['Display(' . $gallery['id'] . ')'] = array(
						'mode' => 'LayoutAction',
						'name' => $gallery['title'],
						'desc' => _t('ADS_LAYOUT_AD_DESCRIPTION')
					);
				}
            }
        }
        return $actions;
    }

	/**
     * Displays an Advertisement.
     *
     * @param 	int 	$cid 	Ad ID
     * @param 	boolean 	$embedded 	Embedded mode
     * @param 	string 	$referer 	Embedding referer
     * @param 	boolean 	$single 	Single mode
     * @access 	public
     * @return 	string
     */
    function Display($cid = 1, $embedded = false, $referer = null, $single = true)
    {
		$GLOBALS['app']->Layout->AddScriptLink('include/Jaws/Ajax/Response.js');			
		/*
		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=Ads&amp;action=Ajax&amp;client=all&amp;stub=AdsAjax');
		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=Ads&action=AjaxCommonFiles');			
		$GLOBALS['app']->Layout->AddScriptLink('gadgets/Ads/resources/client_script.js');			
		*/
		$GLOBALS['app']->Layout->AddHeadLink('gadgets/Ads/resources/style.css', 'stylesheet', 'text/css');
		// for boxover on date highlighting
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $model = $GLOBALS['app']->LoadGadget('Ads', 'Model');
		// send calendarParent records
		$request =& Jaws_Request::getInstance();
		$fetch = array('gadget', 'id');
		$get  = $request->get($fetch, 'get');
		$get['gadget'] = $xss->filter($get['gadget']);
		$get['id'] = $xss->filter($get['id']);
		//if on a users home page, show their stuff
		/*
		if (strtolower($get['gadget']) == 'users' && !empty($get['id'])) {
			$ad = $model->GetSingleAdByUserID((int)$get['id'], $cid);
		} else {
		*/
			$ad = $model->GetAd($cid);
		//}
		if (isset($ad['id'])) {
			if ($single === true) {
				if (!in_array('ads_'.$ad['id'], $GLOBALS['app']->_ItemsOnLayout)) {
					array_push($GLOBALS['app']->_ItemsOnLayout, 'ads_'.$ad['id']);
				} else {
					return '';
				}
			}
			$tpl = new Jaws_Template('gadgets/Ads/templates/');
			$tpl->Load('normal.html');

			$tpl->SetBlock('layout');
			$tpl->SetVariable('actionName', 'Ad_' . $ad['id'] . '_');
			$tpl->SetVariable('layout_title', $ad['title']);
			if ($single === true) {
				$tpl->SetVariable('type', 'Display('.$ad['id'].')_');
			}
			
			$ad_title = $xss->filter(strip_tags($ad['title']));
			$ad_link = $xss->filter(strip_tags($ad['url']));
			$ad_link_encoded = urlencode($ad_link);
			$ad_link_compare = strtolower($ad_link);
			$ad_target = " target=\"_blank\"";
			$site_url = strtolower($GLOBALS['app']->getSiteURL('', false, 'http'));
			$site_ssl_url = strtolower($GLOBALS['app']->getSiteURL('', false, 'https'));
			if (
				(substr($ad_link_compare, 0, 4) == 'http' && 
				(substr($ad_link_compare, 0, strlen($site_url)) == $site_url || 
				substr($ad_link_compare, 0, strlen($site_ssl_url)) == $site_ssl_url)) || 
				substr($ad_link_compare, 0, 5) == 'index' || 
				substr($ad_link_compare, 0, 5) == 'admin'
			) {
				$ad_target = '';
			}					

			$tpl->SetBlock('layout/ad');
			$div_dimensions = '';
			if (isset($ad['image']) && !empty($ad['image'])) {
				$main_image_src = '';
				$ad['image'] = $xss->filter(strip_tags($ad['image']));
				if (substr(strtolower($ad['image']), 0, 4) == "http") {
					if (substr(strtolower($ad['image']), 0, 7) == "http://") {
						$main_image_src = explode('http://', $ad['image']);
						foreach ($main_image_src as $img_src) {
							if (!empty($img_src)) {
								$main_image_src = 'http://'.$img_src;
								break;
							}
						}
					} else {
						$main_image_src = explode('https://', $ad['image']);
						foreach ($main_image_src as $img_src) {
							if (!empty($img_src)) {
								$main_image_src = 'https://'.$img_src;
								break;
							}
						}
					}
				} else {
					if (file_exists(JAWS_DATA . 'files'.$ad['image'])) {
						$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$ad['image'];
					}
				}
				if (!empty($main_image_src)) {
					if (substr(strtolower($main_image_src), -4) == '.htm' || substr(strtolower($main_image_src), -5) == '.html') {
						$tpl->SetBlock('layout/ad/html');
						$tpl->SetVariable('id', $cid);
						//$tpl->SetVariable('base_url', JAWS_DPATH);
						$output_html = '';
						$string = file_get_contents(JAWS_DATA . 'files'.$ad['image']);
						$keys = $request->getKeys();
						foreach ($keys as $key => $val) {
							foreach ($val as $v) {
								$req = $request->get($v, $key);
								$string = str_replace('{' . $v . '}', $req, $string);
							}
						}
						$string = preg_replace('{([^>]*)}/i', '', $string);
						$output_html .= $string;
						$tpl->SetVariable('ad_content', $output_html);
					} else if (substr(strtolower($main_image_src), -4) == '.swf') {
						$GLOBALS['app']->Layout->AddScriptLink('libraries/js/swfobject.js');			
						$tpl->SetBlock('layout/ad/swf');
						$tpl->SetVariable('id', $cid);
						$tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL());
						$tpl->SetVariable('ad_image', $main_image_src);
						$gallery_dimensions = '';
						// build dimensions
						switch($ad['type']) {
							case "720": 
							$gallery_dimensions .= "var aWidth".$cid." = 720;\nvar aHeight".$cid." = 300;\n";
							break;
							case "728": 
							$gallery_dimensions .= "var aWidth".$cid." = 728;\nvar aHeight".$cid." = 90;\n";
							break;
							case "468": 
							$gallery_dimensions .= "var aWidth".$cid." = 468;\nvar aHeight".$cid." = 60;\n";
							break;
							case "125": 
							$gallery_dimensions .= "var aWidth".$cid." = 125;\nvar aHeight".$cid." = 125;\n";
							break;
							case "225": 
							$gallery_dimensions .= "var aWidth".$cid." = 225;\nvar aHeight".$cid." = 200;\n";
							break;
						}
						$tpl->SetVariable('swf_dimensions', $gallery_dimensions);
					} else {
						$tpl->SetBlock('layout/ad/image');
						$tpl->SetVariable('id', $cid);
						//$tpl->SetVariable('base_url', JAWS_DPATH);
						$tpl->SetVariable('ad_image', 'url(' . $main_image_src .') no-repeat top left');
						$image_dimensions = '';
						// build dimensions
						switch($ad['type']) {
							case "720": 
							$image_dimensions .= "width = \"720\" height = \"300\"";
							break;
							case "728": 
							$image_dimensions .= "width = \"728\" height = \"90\"";
							break;
							case "468": 
							$image_dimensions .= "width = \"468\" height = \"60\"";
							break;
							case "125": 
							$image_dimensions .= "width = \"125\" height = \"125\"";
							break;
							case "225": 
							$image_dimensions .= "width = \"225\" height = \"200\"";
							break;
						}
						$tpl->SetVariable('image_dimensions', $image_dimensions);
					}
					switch($ad['type']) {
						case "720": 
						$div_dimensions .= " width: 720px; height: 300px; max-width: 720px; max-height: 300px;";
						break;
						case "728": 
						$div_dimensions .= " width: 728px; height: 90px; max-width: 728px; max-height: 90px;";
						break;
						case "468": 
						$div_dimensions .= " width: 468px; height: 60px; max-width: 468px; max-height: 60px;";
						break;
						case "125": 
						$div_dimensions .= " width: 125px; height: 125px; max-width: 125px; max-height: 125px;";
						break;
						case "225": 
						$div_dimensions .= " width: 225px; height: 200px; max-width: 225px; max-height: 200px;";
						break;
					}
					$tpl->SetVariable('div_dimensions', $div_dimensions);
					//$tpl->SetVariable('ad_link', $ad_link);			
					$tpl->SetVariable('ad_target', $ad_target);			
					$tpl->SetVariable('ad_link_encoded', $ad_link_encoded);			
					$tpl->SetVariable('ad_title', $ad_title);			
					if (substr(strtolower($main_image_src), -4) == '.htm' || substr(strtolower($main_image_src), -5) == '.html') {
						$tpl->ParseBlock('layout/ad/html');
					} else if (substr(strtolower($main_image_src), -4) == '.swf') {
						$tpl->ParseBlock('layout/ad/swf');
					} else {
						$tpl->ParseBlock('layout/ad/image');
					}
				}
			} else {
				$par = $model->GetAdParent((int)$ad['linkid']);
				$main_image_src = '';
				if (!Jaws_Error::IsError($par)) {	
					if (!empty($par['adparentimage']) && isset($par['adparentimage'])) {
						$par['adparentimage'] = $xss->filter(strip_tags($par['adparentimage']));
						if (substr(strtolower($par['adparentimage']), 0, 4) == "http") {
							if (substr(strtolower($par['adparentimage']), 0, 7) == "http://") {
								$main_image_src = explode('http://', $par['adparentimage']);
								foreach ($main_image_src as $img_src) {
									if (!empty($img_src)) {
										$main_image_src = 'http://'.$img_src;
										break;
									}
								}
							} else {
								$main_image_src = explode('https://', $par['adparentimage']);
								foreach ($main_image_src as $img_src) {
									if (!empty($img_src)) {
										$main_image_src = 'https://'.$img_src;
										break;
									}
								}
							}
						} else {
							if (file_exists(JAWS_DATA . 'files'.$par['adparentimage'])) {
								$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$par['adparentimage'];
							}
						}
					}
				}
				
				if (!empty($main_image_src)) {
					$tpl->SetBlock('layout/ad/image');
					$tpl->SetVariable('id', $cid);
					//$tpl->SetVariable('base_url', JAWS_DPATH);
					$tpl->SetVariable('ad_image', 'url(' . $main_image_src .') no-repeat top left');
					$image_dimensions = '';
					// build dimensions
					$image_dimensions .= "width = \"200\" height = \"200\"";
					$tpl->SetVariable('image_dimensions', $image_dimensions);
					$div_dimensions .= " width: 200px; height: 200px;";
					$tpl->SetVariable('div_dimensions', $div_dimensions);
					$tpl->SetVariable('ad_target', $ad_target);			
					$tpl->SetVariable('ad_link_encoded', $ad_link_encoded);			
					$tpl->SetVariable('ad_title', $ad_title);			
					$tpl->ParseBlock('layout/ad/image');
				}
			}
			/*
			// Insert ad impression into DB
			$sql = "
				INSERT INTO [[ads_impressions]]
					([ad_id], [page], [created])
				VALUES
					({ad_id}, {page}, {now})";
			
			$params               			= array();
			$params['ad_id']         		= $parents['id'];
			$full_url = $_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'];
			if ($_SERVER['QUERY_STRING'] > ' ') { 
				$full_url .= '?'.$_SERVER['QUERY_STRING'];
			} else { 
				$full_url .=  '';
			}
			$params['page']         		= $full_url;
			$params['now']        			= $GLOBALS['db']->Date();
			
			$result = $GLOBALS['db']->query($sql, $params);
			if (Jaws_Error::IsError($result)) {
				//$GLOBALS['app']->Session->PushLastResponse(_t('ADS_ERROR_ADIMPRESSION_NOT_ADDED'), RESPONSE_ERROR);
				//return $result;
			}
			*/
					
			$tpl->ParseBlock('layout/ad');

			if ($single === true) {
				$display_id = md5('Ads'.$cid);
				// If this is embedded, we need to pass the javascript variable that has the IFRAME height to the session 
				if ($embedded == true && !is_null($referer)) {	
					$tpl->SetBlock('layout/embedded');
					$tpl->SetVariable('id', $display_id);		        
					if (strtolower($referer) == strtolower($_SERVER['SERVER_NAME'])) {
						$tpl->SetVariable('referer', $GLOBALS['app']->GetJawsURL() . "/libraries/iframes/domain1/iframetest_resize1.html");		        
					} else {	
						$tpl->SetVariable('referer', "http://".$referer."/19/img/agents/19/custom_img/common_files/iframetest_resize1.html");		        
					}
					$tpl->SetVariable('bool_resize', "0");		        
					$tpl->ParseBlock('layout/embedded');
				} else {
					$tpl->SetBlock('layout/not_embedded');
					$tpl->SetVariable('id', $display_id);		        
					$tpl->ParseBlock('layout/not_embedded');
				}
			}
			$tpl->ParseBlock('layout');

			return $tpl->Get();
		}
    }

	/**
     * Accept advertising inquiries, with "Advertise Here" ads.
     *
     * @category 	feature
     * @param 	string 	$type 	Ad type (125/225/468/728)
     * @param 	boolean 	$embedded 	Embedded mode
     * @param 	string 	$referer 	Embedding referer
     * @param 	boolean 	$single 	Single mode
     * @access 	public
     * @return 	string
     */
    function DisplayDefault($type = '728', $embedded = false, $referer = null, $single = false)
    {
		/*
		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=Ads&amp;action=Ajax&amp;client=all&amp;stub=AdsAjax');
		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=Ads&action=AjaxCommonFiles');			
		$GLOBALS['app']->Layout->AddScriptLink('gadgets/Ads/resources/client_script.js');			
		*/
		$GLOBALS['app']->Layout->AddScriptLink('libraries/js/swfobject.js');			
		$GLOBALS['app']->Layout->AddHeadLink('gadgets/Ads/resources/style.css', 'stylesheet', 'text/css');
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $model = $GLOBALS['app']->LoadGadget('Ads', 'Model');

		$request =& Jaws_Request::getInstance();
		$fetch = array('gadget', 'id');
		$get  = $request->get($fetch, 'get');
		$get['gadget'] = $xss->filter($get['gadget']);
		$get['id'] = (int)$xss->filter($get['id']);
		
			$tpl = new Jaws_Template('gadgets/Ads/templates/');
	        $tpl->Load('normal.html');

	        $tpl->SetBlock('layout');
			$tpl->SetVariable('actionName', 'Default_');
	        $tpl->SetVariable('layout_title', _t('ADS_LAYOUT_DEFAULT_TITLE'));

			$tpl->SetBlock('layout/default_'.$type);
			// set "gallery" swfobject variables
			//$tpl->SetVariable('base_url', JAWS_DPATH);
			$tpl->SetVariable('id', 'Default');
			$div_dimensions = '';
			$image_dimensions = '';
			switch($type) {
				case "728": 
				$tpl->SetVariable('spacer_image', $GLOBALS['app']->GetJawsURL() . "/gadgets/Ads/images/default_728.gif");
				$div_dimensions .= " width: 728px; height: 90px; margin: auto;";
				$image_dimensions .= "width = \"726\" height = \"88\"";
				break;
				case "468": 
				$tpl->SetVariable('spacer_image', $GLOBALS['app']->GetJawsURL() . "/gadgets/Ads/images/default_468.gif");
				$div_dimensions .= " width: 468px; height: 60px; margin: auto;";
				$image_dimensions .= "width = \"466\" height = \"58\"";
				break;
				case "125": 
				$tpl->SetVariable('spacer_image', $GLOBALS['app']->GetJawsURL() . "/gadgets/Ads/images/default_125.gif");
				$div_dimensions .= " width: 125px; height: 125px;";
				$image_dimensions .= "width = \"123\" height = \"123\"";
				break;
				case "225": 
				$tpl->SetVariable('spacer_image', $GLOBALS['app']->GetJawsURL() . "/gadgets/Ads/images/default_225.gif");
				$div_dimensions .= " width: 225px; height: 200px;";
				$image_dimensions .= "width = \"223\" height = \"198\"";
				break;
			}
			$tpl->SetVariable('div_dimensions', $div_dimensions);
			$tpl->SetVariable('image_dimensions', $image_dimensions);
			$tpl->SetVariable('ad_title', _t('ADS_LAYOUT_DEFAULT_TITLE'));
			$tpl->SetVariable('ad_text', _t('ADS_LAYOUT_DEFAULT_TEXT'));
			
			$form_found = false;
			if (Jaws_Gadget::IsGadgetUpdated('Forms')) {
				$forms_model = $GLOBALS['app']->LoadGadget('Forms', 'Model');
				$form = $forms_model->GetForm(1);
				if (isset($form['id'])) {
					$form_found = true;
				}
			}
			if ($form_found === true) {
				$tpl->SetVariable('ad_link', $GLOBALS['app']->Map->GetURLFor('Forms', 'Form', array('id' => 1)));
			} else {
				$tpl->SetVariable('ad_link', 'mailto:'.$GLOBALS['app']->Registry->Get('/network/site_email'));			
			}
	        
			$tpl->ParseBlock('layout/default_'.$type);

			if ($single === true) {
				$display_id = md5('Ads'.'Default');
				// If this is embedded, we need to pass the javascript variable that has the IFRAME height to the session 
				if ($embedded == true && !is_null($referer)) {	
					$tpl->SetBlock('layout/embedded');
					$tpl->SetVariable('id', $display_id);		        
					if (strtolower($referer) == strtolower($_SERVER['SERVER_NAME'])) {
						$tpl->SetVariable('referer', $GLOBALS['app']->GetJawsURL() . "/libraries/iframes/domain1/iframetest_resize1.html");		        
					} else {	
						$tpl->SetVariable('referer', "http://".$referer."/19/img/agents/19/custom_img/common_files/iframetest_resize1.html");		        
					}
					$tpl->SetVariable('bool_resize', "0");		        
					$tpl->ParseBlock('layout/embedded');
				} else {
					$tpl->SetBlock('layout/not_embedded');
					$tpl->SetVariable('id', $display_id);		        
					$tpl->ParseBlock('layout/not_embedded');
				}
			}
	        $tpl->ParseBlock('layout');

	        return $tpl->Get();
    }

	/**
     * 728px x 90px ads (Leaderboard).
     *
     * @category 	feature
     * @param 	boolean 	$embedded 	Embedded mode
     * @param 	string 	$referer 	Embedding referer
     * @param 	int 	$uid 	OwnerID
     * @access 	public
     * @return 	string
     */
    function ShowLeaderBoard($embedded = false, $referer = null, $uid = null)
    {
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $model = $GLOBALS['app']->LoadGadget('Ads', 'Model');
		
		$request =& Jaws_Request::getInstance();
		$fetch = array('gadget', 'id', 'action');
		$get  = $request->get($fetch, 'get');
		$get['gadget'] = $xss->filter($get['gadget']);
		$get['id'] = (int)$xss->filter($get['id']);
		$get['action'] = $xss->filter($get['action']);
		
		/*
		if (!is_null($uid)) {
			$parents = $model->GetAdsOfUserID($uid, '728');
		} else {
		*/
			$keyword = $GLOBALS['app']->GetCurrentKeyword();
			if ($keyword != '') {
				$parents = $model->GetAdsByKeyword($keyword, '728');
			} else {
				$parents = $model->GetSitewideAds('728');
			}
		//}
		$tpl = new Jaws_Template('gadgets/Ads/templates/');
		$tpl->Load('normal.html');

		$tpl->SetBlock('layout');
		$tpl->SetVariable('actionName', 'LeaderBoard');
		$tpl->SetVariable('layout_title', _t('ADS_LAYOUT_LEADERBOARD_TITLE'));

		$tpl->SetBlock('layout/728');
		//$tpl->SetVariable('base_url', JAWS_DPATH);
		$tpl->SetVariable('id', 'Banner');
		
		$i = 0;
		$ba = array();
		if (count($parents) > 0) {
			foreach($parents as $p) {		            
				if (isset($p['id'])) {					            
					$ba[$i] = $p['id'];
					$i++;
				}
			}
		}
		// Choose random IDs
		if (isset($ba[0])) {
			$a = 0;
			while (true) {
				$buttons_rand = array_rand($ba);
				if (!in_array('ads_'.$ba[$buttons_rand], $GLOBALS['app']->_ItemsOnLayout)) {
					array_push($GLOBALS['app']->_ItemsOnLayout, 'ads_'.$ba[$buttons_rand]);
					break;
				}
				$a++;
				if ($a > $i) {
					$tpl->SetBlock('layout/728/item');
					if (is_null($uid)) {
						$tpl->SetVariable('layout_content', $this->DisplayDefault('728', false, null, false));
					} else {
						$tpl->SetVariable('layout_content', '');
					}
					$tpl->ParseBlock('layout/728/item');
					break;
				}
			}
			if ($a <= $i) {
				$tpl->SetBlock('layout/728/item');
				$tpl->SetVariable('layout_content', $this->Display($ba[$buttons_rand], false, null, false));
				$tpl->ParseBlock('layout/728/item');
			}
		} else {
			$tpl->SetBlock('layout/728/item');
			if (is_null($uid)) {
				$tpl->SetVariable('layout_content', $this->DisplayDefault('728', false, null, false));
			} else {
				$tpl->SetVariable('layout_content', '');
			}
			$tpl->ParseBlock('layout/728/item');
		}
		$tpl->ParseBlock('layout/728');

		// If this is embedded, we need to pass the javascript variable that has the IFRAME height to the session 
		$display_id = md5('Ads'.'728');
		if ($embedded == true && !is_null($referer)) {	
			$tpl->SetBlock('layout/embedded');
			$tpl->SetVariable('id', $display_id);		        
			if (strtolower($referer) == strtolower($_SERVER['SERVER_NAME'])) {
				$tpl->SetVariable('referer', $GLOBALS['app']->GetJawsURL() . "/libraries/iframes/domain1/iframetest_resize1.html");		        
			} else {	
				$tpl->SetVariable('referer', "http://".$referer."/19/img/agents/19/custom_img/common_files/iframetest_resize1.html");		        
			}
			$tpl->SetVariable('bool_resize', "0");		        
			$tpl->ParseBlock('layout/embedded');
		} else {
			$tpl->SetBlock('layout/not_embedded');
			$tpl->SetVariable('id', $display_id);		        
			$tpl->ParseBlock('layout/not_embedded');
		}

		$tpl->ParseBlock('layout');

		return $tpl->Get();
    }

	/**
     * 468px x 60px ads (Banner).
     *
     * @category 	feature
     * @param 	boolean 	$embedded 	Embedded mode
     * @param 	string 	$referer 	Embedding referer
     * @param 	int 	$uid 	OwnerID
     * @access 	public
     * @return 	string
     */
    function ShowBanner($embedded = false, $referer = null, $uid = null)
    {
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $model = $GLOBALS['app']->LoadGadget('Ads', 'Model');
		
		$request =& Jaws_Request::getInstance();
		$fetch = array('gadget', 'id', 'action');
		$get  = $request->get($fetch, 'get');
		$get['gadget'] = $xss->filter($get['gadget']);
		$get['id'] = (int)$xss->filter($get['id']);
		$get['action'] = $xss->filter($get['action']);
		
		/*
		if (!is_null($uid)) {
			$parents = $model->GetAdsOfUserID($uid, '468');
		} else {
		*/
			$keyword = $GLOBALS['app']->GetCurrentKeyword();
			if ($keyword != '') {
				$parents = $model->GetAdsByKeyword($keyword, '468');
			} else {
				$parents = $model->GetSitewideAds('468');
			}
		//}
		$tpl = new Jaws_Template('gadgets/Ads/templates/');
		$tpl->Load('normal.html');

		$tpl->SetBlock('layout');
		$tpl->SetVariable('actionName', 'Banner');
		$tpl->SetVariable('layout_title', _t('ADS_LAYOUT_BANNER_TITLE'));

		$tpl->SetBlock('layout/468');
		//$tpl->SetVariable('base_url', JAWS_DPATH);
		$tpl->SetVariable('id', 'Banner');
		
		$i = 0;
		$ba = array();
		if (count($parents) > 0) {
			foreach($parents as $p) {		            
				if (isset($p['id'])) {					            
					$ba[$i] = $p['id'];
					$i++;
				}
			}
		}		
		// Choose random IDs
		if (isset($ba[0])) {
			$a = 0;
			while (true) {
				$buttons_rand = array_rand($ba);
				if (!in_array('ads_'.$ba[$buttons_rand], $GLOBALS['app']->_ItemsOnLayout)) {
					array_push($GLOBALS['app']->_ItemsOnLayout, 'ads_'.$ba[$buttons_rand]);
					break;
				}
				$a++;
				if ($a > $i) {
					$tpl->SetBlock('layout/468/item');
					if (is_null($uid)) {
						$tpl->SetVariable('layout_content', $this->DisplayDefault('468', false, null, false));
					} else {
						$tpl->SetVariable('layout_content', '');
					}
					$tpl->ParseBlock('layout/468/item');
					break;
				}
			}
			if ($a <= $i) {
				$tpl->SetBlock('layout/468/item');
				$tpl->SetVariable('layout_content', $this->Display($ba[$buttons_rand], false, null, false));
				$tpl->ParseBlock('layout/468/item');
			}
		} else {
			$tpl->SetBlock('layout/468/item');
			if (is_null($uid)) {
				$tpl->SetVariable('layout_content', $this->DisplayDefault('468', false, null, false));
			} else {
				$tpl->SetVariable('layout_content', '');
			}
			$tpl->ParseBlock('layout/468/item');
		}
		$tpl->ParseBlock('layout/468');

		// If this is embedded, we need to pass the javascript variable that has the IFRAME height to the session 
		$display_id = md5('Ads'.'468');
		if ($embedded == true && !is_null($referer)) {	
			$tpl->SetBlock('layout/embedded');
			$tpl->SetVariable('id', $display_id);		        
			if (strtolower($referer) == strtolower($_SERVER['SERVER_NAME'])) {
				$tpl->SetVariable('referer', $GLOBALS['app']->GetJawsURL() . "/libraries/iframes/domain1/iframetest_resize1.html");		        
			} else {	
				$tpl->SetVariable('referer', "http://".$referer."/19/img/agents/19/custom_img/common_files/iframetest_resize1.html");		        
			}
			$tpl->SetVariable('bool_resize', "0");		        
			$tpl->ParseBlock('layout/embedded');
		} else {
			$tpl->SetBlock('layout/not_embedded');
			$tpl->SetVariable('id', $display_id);		        
			$tpl->ParseBlock('layout/not_embedded');
		}

		$tpl->ParseBlock('layout');

		return $tpl->Get();
    }

	/**
     * 125px x 125px ads (Button).
     *
     * @category 	feature
     * @param 	boolean 	$embedded 	Embedded mode
     * @param 	string 	$referer 	Embedding referer
     * @param 	int 	$uid 	OwnerID
     * @access 	public
     * @return 	string
     */
    function ShowTwoButtons($embedded = false, $referer = null, $uid = null)
    {
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $model = $GLOBALS['app']->LoadGadget('Ads', 'Model');
		
		$request =& Jaws_Request::getInstance();
		$fetch = array('gadget', 'id', 'action');
		$get  = $request->get($fetch, 'get');
		$get['gadget'] = $xss->filter($get['gadget']);
		$get['id'] = (int)$xss->filter($get['id']);
		$get['action'] = $xss->filter($get['action']);

		/*
		if (!is_null($uid)) {
			$parents = $model->GetAdsOfUserID($uid, '125');
		} else {
		*/
			$keyword = $GLOBALS['app']->GetCurrentKeyword();
			if ($keyword != '') {
				$parents = $model->GetAdsByKeyword($keyword, '125');
			} else {
				$parents = $model->GetSitewideAds('125');
			}
		//}
		$tpl = new Jaws_Template('gadgets/Ads/templates/');
		$tpl->Load('normal.html');

		$tpl->SetBlock('layout');
		$tpl->SetVariable('actionName', 'TwoButtons');
		$tpl->SetVariable('layout_title', _t('ADS_LAYOUT_TWOBUTTONS_TITLE'));

		$tpl->SetBlock('layout/125');
		//$tpl->SetVariable('base_url', JAWS_DPATH);
		$tpl->SetVariable('id', 'TwoButtons');
		
		$i = 0;
		$ba = array();
		if (count($parents) > 0) {
			foreach($parents as $p) {		            
				if (isset($p['id'])) {					            
					$ba[$i] = $p['id'];
					$i++;
				}
			}
		}
		// Choose two random IDs
		$tpl->SetVariable('num', $i);
		if ($i > 1) {
			for ($b=0; $b<2; $b++) {
				$a = 0;
				while (true) {
					$buttons_rand = array_rand($ba);
					if (!in_array('ads_'.$ba[$buttons_rand], $GLOBALS['app']->_ItemsOnLayout)) {
						array_push($GLOBALS['app']->_ItemsOnLayout, 'ads_'.$ba[$buttons_rand]);
						break;
					}
					$a++;
					if ($a > $i) {
						break;
					}
				}
				if ($a <= $i) {
					$tpl->SetBlock('layout/125/item');
					$tpl->SetVariable('layout_content', $this->Display($ba[$buttons_rand], false, null, false));
					$tpl->ParseBlock('layout/125/item');
				}
			}
		} else if ($i > 0) {
			for ($b=0; $b<$i; $b++) {
				$a = 0;
				while (true) {
					$buttons_rand = array_rand($ba);
					if (!in_array('ads_'.$ba[$buttons_rand], $GLOBALS['app']->_ItemsOnLayout)) {
						array_push($GLOBALS['app']->_ItemsOnLayout, 'ads_'.$ba[$buttons_rand]);
						break;
					}
					$a++;
					if ($a > $i) {
						break;
					}
				}
				if ($a <= $i) {
					$tpl->SetBlock('layout/125/item');
					$tpl->SetVariable('layout_content', $this->Display($ba[$buttons_rand], false, null, false));
					$tpl->ParseBlock('layout/125/item');
				}
			}
		}
		for ($j=$i; $j<2; $j++) {
			$tpl->SetBlock('layout/125/item');
			if (is_null($uid)) {
				$tpl->SetVariable('layout_content', $this->DisplayDefault('125'));
			} else {
				$tpl->SetVariable('layout_content', '');
			}
			$tpl->ParseBlock('layout/125/item');
		}
		$tpl->ParseBlock('layout/125');

		// If this is embedded, we need to pass the javascript variable that has the IFRAME height to the session 
		$display_id = md5('Ads'.'TwoButtons');
		if ($embedded == true && !is_null($referer)) {	
			$tpl->SetBlock('layout/embedded');
			$tpl->SetVariable('id', $display_id);		        
			if (strtolower($referer) == strtolower($_SERVER['SERVER_NAME'])) {
				$tpl->SetVariable('referer', $GLOBALS['app']->GetJawsURL() . "/libraries/iframes/domain1/iframetest_resize1.html");		        
			} else {	
				$tpl->SetVariable('referer', "http://".$referer."/19/img/agents/19/custom_img/common_files/iframetest_resize1.html");		        
			}
			$tpl->SetVariable('bool_resize', "0");		        
			$tpl->ParseBlock('layout/embedded');
		} else {
			$tpl->SetBlock('layout/not_embedded');
			$tpl->SetVariable('id', $display_id);		        
			$tpl->ParseBlock('layout/not_embedded');
		}

		$tpl->ParseBlock('layout');

		return $tpl->Get();
		
    }

	/**
     * Displays four button-sized ads.
     *
     * @param 	boolean 	$embedded 	Embedded mode
     * @param 	string 	$referer 	Embedding referer
     * @param 	int 	$uid 	OwnerID
     * @access 	public
     * @return 	string
     */
    function ShowFourButtons($embedded = false, $referer = null, $uid = null)
    {
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $model = $GLOBALS['app']->LoadGadget('Ads', 'Model');
		
		$request =& Jaws_Request::getInstance();
		$fetch = array('gadget', 'id', 'action');
		$get  = $request->get($fetch, 'get');
		$get['gadget'] = $xss->filter($get['gadget']);
		$get['id'] = (int)$xss->filter($get['id']);
		$get['action'] = $xss->filter($get['action']);

		/*
		if (!is_null($uid)) {
			$parents = $model->GetAdsOfUserID($uid, '125');
		} else {
		*/
			$keyword = $GLOBALS['app']->GetCurrentKeyword();
			if ($keyword != '') {
				$parents = $model->GetAdsByKeyword($keyword, '125');
			} else {
				$parents = $model->GetSitewideAds('125');
			}
		//}
		$tpl = new Jaws_Template('gadgets/Ads/templates/');
		$tpl->Load('normal.html');

		$tpl->SetBlock('layout');
		$tpl->SetVariable('actionName', 'FourButtons');
		$tpl->SetVariable('layout_title', _t('ADS_LAYOUT_FOURBUTTONS_TITLE'));

		$tpl->SetBlock('layout/125');
		//$tpl->SetVariable('base_url', JAWS_DPATH);
		$tpl->SetVariable('id', 'FourButtons');
		
		$i = 0;
		$ba = array();
		if (count($parents) > 0) {
			foreach($parents as $p) {		            
				if (isset($p['id'])) {					            
					$ba[$i] = $p['id'];
					$i++;
				}
			}
		}
		$tpl->SetVariable('num', $i);
		// Choose four random IDs	
		if ($i > 3) {
			for ($b=0; $b<4; $b++) {
				$a = 0;
				while (true) {
					$buttons_rand = array_rand($ba);
					if (!in_array('ads_'.$ba[$buttons_rand], $GLOBALS['app']->_ItemsOnLayout)) {
						array_push($GLOBALS['app']->_ItemsOnLayout, 'ads_'.$ba[$buttons_rand]);
						break;
					}
					$a++;
					if ($a > $i) {
						break;
					}
				}
				if ($a <= $i) {
					$tpl->SetBlock('layout/125/item');
					$tpl->SetVariable('layout_content', $this->Display($ba[$buttons_rand], false, null, false));
					$tpl->ParseBlock('layout/125/item');
				}
			}
		} else if ($i > 0) {
			for ($b=0; $b<$i; $b++) {
				$a = 0;
				while (true) {
					$buttons_rand = array_rand($ba);
					if (!in_array('ads_'.$ba[$buttons_rand], $GLOBALS['app']->_ItemsOnLayout)) {
						array_push($GLOBALS['app']->_ItemsOnLayout, 'ads_'.$ba[$buttons_rand]);
						break;
					}
					$a++;
					if ($a > $i) {
						break;
					}
				}
				if ($a <= $i) {
					$tpl->SetBlock('layout/125/item');
					$tpl->SetVariable('layout_content', $this->Display($ba[$buttons_rand], false, null, false));
					$tpl->ParseBlock('layout/125/item');
				}
			}
		}
		for ($j=$i; $j<4; $j++) {
			$tpl->SetBlock('layout/125/item');
			if (is_null($uid)) {
				$tpl->SetVariable('layout_content', $this->DisplayDefault('125'));
			} else {
				$tpl->SetVariable('layout_content', '');
			}
			$tpl->ParseBlock('layout/125/item');
		}
		$tpl->ParseBlock('layout/125');

		// If this is embedded, we need to pass the javascript variable that has the IFRAME height to the session 
		$display_id = md5('Ads'.'FourButtons');
		if ($embedded == true && !is_null($referer)) {	
			$tpl->SetBlock('layout/embedded');
			$tpl->SetVariable('id', $display_id);		        
			if (strtolower($referer) == strtolower($_SERVER['SERVER_NAME'])) {
				$tpl->SetVariable('referer', $GLOBALS['app']->GetJawsURL() . "/libraries/iframes/domain1/iframetest_resize1.html");		        
			} else {	
				$tpl->SetVariable('referer', "http://".$referer."/19/img/agents/19/custom_img/common_files/iframetest_resize1.html");		        
			}
			$tpl->SetVariable('bool_resize', "0");		        
			$tpl->ParseBlock('layout/embedded');
		} else {
			$tpl->SetBlock('layout/not_embedded');
			$tpl->SetVariable('id', $display_id);		        
			$tpl->ParseBlock('layout/not_embedded');
		}

		$tpl->ParseBlock('layout');

		return $tpl->Get();
		
    }
	
	/**
     * 225px x 200px ads (Block).
     *
     * @category 	feature
     * @param 	boolean 	$embedded 	Embedded mode
     * @param 	string 	$referer 	Embedding referer
     * @param 	int 	$uid 	OwnerID
     * @access 	public
     * @return 	string
     */
    function ShowTwoBlocks($embedded = false, $referer = null, $uid = null)
    {
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $model = $GLOBALS['app']->LoadGadget('Ads', 'Model');
		
		/*
		if (!is_null($uid)) {
			$parents = $model->GetAdsOfUserID($uid, '225');
		} else {
		*/
			$keyword = $GLOBALS['app']->GetCurrentKeyword();
			if (trim($keyword) != '') {
				$parents = $model->GetAdsByKeyword($keyword, '225');
			} else {
				$parents = $model->GetSitewideAds('225');
			}
		//}
		$tpl = new Jaws_Template('gadgets/Ads/templates/');
		$tpl->Load('normal.html');

		$tpl->SetBlock('layout');
		$tpl->SetVariable('actionName', 'TwoBlocks');
		$tpl->SetVariable('layout_title', _t('ADS_LAYOUT_TWOBLOCKS_TITLE'));

		$tpl->SetBlock('layout/225');
		//$tpl->SetVariable('base_url', JAWS_DPATH);
		$tpl->SetVariable('id', 'TwoBlocks');
		
		$i = 0;
		$ba = array();
		if (count($parents) > 0) {
			foreach($parents as $p) {		            
				if (isset($p['id'])) {					            
					$ba[$i] = $p['id'];
					$i++;
				}
			}
		}
		// Choose two random IDs
		$tpl->SetVariable('num', $i);
		if ($i > 1) {
			for ($b=0; $b<2; $b++) {
				$a = 0;
				while (true) {
					$buttons_rand = array_rand($ba);
					if (!in_array('ads_'.$ba[$buttons_rand], $GLOBALS['app']->_ItemsOnLayout)) {
						array_push($GLOBALS['app']->_ItemsOnLayout, 'ads_'.$ba[$buttons_rand]);
						break;
					}
					$a++;
					if ($a > $i) {
						break;
					}
				}
				if ($a <= $i) {
					$tpl->SetBlock('layout/225/item');
					$tpl->SetVariable('layout_content', $this->Display($ba[$buttons_rand], false, null, false));
					$tpl->ParseBlock('layout/225/item');
				}
			}
		} else if ($i > 0) {
			for ($b=0; $b<$i; $b++) {
				$a = 0;
				while (true) {
					$buttons_rand = array_rand($ba);
					if (!in_array('ads_'.$ba[$buttons_rand], $GLOBALS['app']->_ItemsOnLayout)) {
						array_push($GLOBALS['app']->_ItemsOnLayout, 'ads_'.$ba[$buttons_rand]);
						break;
					}
					$a++;
					if ($a > $i) {
						break;
					}
				}
				if ($a <= $i) {
					$tpl->SetBlock('layout/225/item');
					$tpl->SetVariable('layout_content', $this->Display($ba[$buttons_rand], false, null, false));
					$tpl->ParseBlock('layout/225/item');
				}
			}
		}
		for ($j=$i; $j<2; $j++) {
			//var_dump($j);
			$tpl->SetBlock('layout/225/item');
			if (is_null($uid)) {
				$tpl->SetVariable('layout_content', $this->DisplayDefault('225'));
			} else {
				$tpl->SetVariable('layout_content', '');
			}
			$tpl->ParseBlock('layout/225/item');
		}
		
		$tpl->ParseBlock('layout/225');

		// If this is embedded, we need to pass the javascript variable that has the IFRAME height to the session 
		$display_id = md5('Ads'.'TwoButtons');
		if ($embedded == true && !is_null($referer)) {	
			$tpl->SetBlock('layout/embedded');
			$tpl->SetVariable('id', $display_id);		        
			if (strtolower($referer) == strtolower($_SERVER['SERVER_NAME'])) {
				$tpl->SetVariable('referer', $GLOBALS['app']->GetJawsURL() . "/libraries/iframes/domain1/iframetest_resize1.html");		        
			} else {	
				$tpl->SetVariable('referer', "http://".$referer."/19/img/agents/19/custom_img/common_files/iframetest_resize1.html");		        
			}
			$tpl->SetVariable('bool_resize', "0");		        
			$tpl->ParseBlock('layout/embedded');
		} else {
			$tpl->SetBlock('layout/not_embedded');
			$tpl->SetVariable('id', $display_id);		        
			$tpl->ParseBlock('layout/not_embedded');
		}

		$tpl->ParseBlock('layout');

		return $tpl->Get();
		
    }

	/**
     * Displays four 225x200 pixel ads.
     *
     * @param 	boolean 	$embedded 	Embedded mode
     * @param 	string 	$referer 	Embedding referer
     * @param 	int 	$uid 	OwnerID
     * @access 	public
     * @return 	string
     */
    function ShowFourBlocks($embedded = false, $referer = null, $uid = null)
    {
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $model = $GLOBALS['app']->LoadGadget('Ads', 'Model');
		
		/*
		if (!is_null($uid)) {
			$parents = $model->GetAdsOfUserID($uid, '225');
		} else {
		*/
			$keyword = $GLOBALS['app']->GetCurrentKeyword();
			if ($keyword != '') {
				$parents = $model->GetAdsByKeyword($keyword, '225');
			} else {
				$parents = $model->GetSitewideAds('225');
			}
		//}
		$tpl = new Jaws_Template('gadgets/Ads/templates/');
		$tpl->Load('normal.html');

		$tpl->SetBlock('layout');
		$tpl->SetVariable('actionName', 'FourButtons');
		$tpl->SetVariable('layout_title', _t('ADS_LAYOUT_FOURBUTTONS_TITLE'));

		$tpl->SetBlock('layout/225');
		//$tpl->SetVariable('base_url', JAWS_DPATH);
		$tpl->SetVariable('id', 'FourButtons');
		
		$i = 0;
		$ba = array();
		if (count($parents) > 0) {
			foreach($parents as $p) {		            
				if (isset($p['id'])) {					            
					$ba[$i] = $p['id'];
					$i++;
				}
			}
		}
		$tpl->SetVariable('num', $i);
		// Choose four random IDs	
		if ($i > 3) {
			for ($b=0; $b<4; $b++) {
				$a = 0;
				while (true) {
					$buttons_rand = array_rand($ba);
					if (!in_array('ads_'.$ba[$buttons_rand], $GLOBALS['app']->_ItemsOnLayout)) {
						array_push($GLOBALS['app']->_ItemsOnLayout, 'ads_'.$ba[$buttons_rand]);
						break;
					}
					$a++;
					if ($a > $i) {
						break;
					}
				}
				if ($a <= $i) {
					$tpl->SetBlock('layout/225/item');
					$tpl->SetVariable('layout_content', $this->Display($ba[$buttons_rand], false, null, false));
					$tpl->ParseBlock('layout/225/item');
				}
			}
		} else if ($i > 0) {
			for ($b=0; $b<$i; $b++) {
				$a = 0;
				while (true) {
					$buttons_rand = array_rand($ba);
					if (!in_array('ads_'.$ba[$buttons_rand], $GLOBALS['app']->_ItemsOnLayout)) {
						array_push($GLOBALS['app']->_ItemsOnLayout, 'ads_'.$ba[$buttons_rand]);
						break;
					}
					$a++;
					if ($a > $i) {
						break;
					}
				}
				if ($a <= $i) {
					$tpl->SetBlock('layout/225/item');
					$tpl->SetVariable('layout_content', $this->Display($ba[$buttons_rand], false, null, false));
					$tpl->ParseBlock('layout/225/item');
				}
			}
		}
		for ($j=$i; $j<4; $j++) {
			$tpl->SetBlock('layout/225/item');
			if (is_null($uid)) {
				$tpl->SetVariable('layout_content', $this->DisplayDefault('225'));
			} else {
				$tpl->SetVariable('layout_content', '');
			}
			$tpl->ParseBlock('layout/225/item');
		}
		$tpl->ParseBlock('layout/225');

		// If this is embedded, we need to pass the javascript variable that has the IFRAME height to the session 
		$display_id = md5('Ads'.'FourBlocks');
		if ($embedded == true && !is_null($referer)) {	
			$tpl->SetBlock('layout/embedded');
			$tpl->SetVariable('id', $display_id);		        
			if (strtolower($referer) == strtolower($_SERVER['SERVER_NAME'])) {
				$tpl->SetVariable('referer', $GLOBALS['app']->GetJawsURL() . "/libraries/iframes/domain1/iframetest_resize1.html");		        
			} else {	
				$tpl->SetVariable('referer', "http://".$referer."/19/img/agents/19/custom_img/common_files/iframetest_resize1.html");		        
			}
			$tpl->SetVariable('bool_resize', "0");		        
			$tpl->ParseBlock('layout/embedded');
		} else {
			$tpl->SetBlock('layout/not_embedded');
			$tpl->SetVariable('id', $display_id);		        
			$tpl->ParseBlock('layout/not_embedded');
		}

		$tpl->ParseBlock('layout');

		return $tpl->Get();
		
    }
}
