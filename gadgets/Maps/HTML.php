<?php
/**
 * Maps Gadget
 *
 * @category   Gadget
 * @package    Maps
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class MapsHTML extends Jaws_GadgetHTML
{
    var $_Name = 'Maps';
    /**
     * Constructor
     *
     * @access public
     */
    function MapsHTML()
    {
        $this->Init('Maps');
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
     * Displays an individual map.
     *
     * @param 	int 	$id 	Map ID (optional)
     * @param 	string 	$address 	Physical address to geocode
     * @param 	string 	$title 	Title to show (for physical address)
     * @param 	int 	$map_height 	Height in pixels of map
     * @access public
     * @return string
     */
    function Map($id = null, $address = '', $title = '', $map_height = null)
    {
        $GLOBALS['app']->Layout->AddScriptLink('libraries/js/global2.js');
        $GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');
		$request =& Jaws_Request::getInstance();
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$mapLayout = $GLOBALS['app']->LoadGadget('Maps', 'LayoutHTML');
		$tpl = new Jaws_Template('gadgets/Maps/templates/');
		$tpl->Load('normal.html');
		$tpl->SetBlock('map');
        
		$post = $request->get(array('id', 'address', 'title', 'height'), 'get');
		$post['id'] = $xss->defilter($post['id']);
        $post['address'] = $xss->defilter(urldecode($post['address']));
        $post['title'] = $xss->defilter(urldecode($post['title']));
        $post['height'] = $xss->defilter($post['height']);

        if (is_null($id)) {
			$id = $post['id'];
        }
        if (empty($title)) {
			$title = $post['title'];
        }
        if (empty($address)) {
			$address = $post['address'];
        }
        if (is_null($map_height)) {
			$map_height = $post['height'];
        }
		if (!is_null($id)) {
			$model = $GLOBALS['app']->LoadGadget('Maps', 'Model');
			$page = $model->GetMap($id);
			if (Jaws_Error::IsError($page)) {
				require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
				return Jaws_HTTPError::Get(404);
			} else {

				if ($page['active'] == 'N') {
					$this->SetTitle(_t('MAPS_TITLE_NOT_FOUND'));
					$tpl->SetBlock('map/not_found');
					$tpl->SetVariable('content', _t('MAPS_CONTENT_NOT_FOUND'));
					$tpl->SetVariable('title', _t('MAPS_TITLE_NOT_FOUND'));
					$tpl->ParseBlock('map/not_found');
				} else {
					$tpl->SetBlock('map/content');
					$page_content = $mapLayout->Display($page['id']);

					$tpl->SetVariable('content', $page_content);
					$tpl->ParseBlock('map/content');
				}
			}
		} else if (!empty($address)) {
			$tpl->SetBlock('map/content');
			$map_height = ((int)$map_height > 0 ? (int)$map_height : 500);
			$map_zoom = 15;
			$page_content = $mapLayout->DisplayMapOfAddress($address, $title, $map_height, $map_zoom);

			$tpl->SetVariable('content', $page_content);
			$tpl->ParseBlock('map/content');
        } else {
			require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
			return Jaws_HTTPError::Get(404);
		}
		$tpl->ParseBlock('map');

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
        $model = $GLOBALS['app']->LoadGadget('Maps', 'Model');
        $pages = $model->GetMaps();
        if (Jaws_Error::IsError($pages)) {
            return _t('MAPS_ERROR_INDEX_NOT_LOADED');
        }

		$xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $tpl = new Jaws_Template('gadgets/Maps/templates/');
        $tpl->Load('normal.html');
        $tpl->SetBlock('index');
	    $tpl->SetVariable('actionName', 'Index');
        $tpl->SetVariable('title', _t('MAPS_TITLE_MAP_INDEX'));
        $tpl->SetVariable('link', $GLOBALS['app']->Map->GetURLFor('Maps', 'Index'));
        foreach ($pages as $page) {
            if ($page['active'] == 'Y') {
                $param = array('id' => $page['id']);
                $link = $GLOBALS['app']->Map->GetURLFor('Maps', 'Map', $param);
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
     * Displays an XML file with the requested maps locations
     *
     * @access public
     * @return string
     */
    function GoogleMapXML($address = '', $title = '')
    {
		require_once JAWS_PATH . 'include/Jaws/Image.php';
        
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');

		$request =& Jaws_Request::getInstance();
        $get = $request->get(array('id', 'showcase_id'), 'get');
		
        //$post['showcase_id'] = $xss->defilter($post['showcase_id']);

		//if(!empty($post['showcase_id'])) {
		//	$agentID = $post['showcase_id'];
		//}
		$googlemaps_key = $GLOBALS['app']->Registry->Get('/gadgets/Maps/googlemaps_key');
		$key = (!empty($googlemaps_key) ? $googlemaps_key : "AIzaSyC-8bM6FDSqHfs3zEW8S839_3MG4kgh1lc");

		include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
		include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'XMLParser.php';
		
		header("Content-type: text/xml");
		$output_xml = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>\n"; 
		$output_xml .= "<markers>\n";
		  
		if (!empty($get['id'])) {
			$gid = $get['id'];

	        $model = $GLOBALS['app']->LoadGadget('Maps', 'Model');
			$galleryPosts = $model->GetAllPostsOfMap($gid);
			if (count($galleryPosts) <= 0) {
				if (isset($GLOBALS['log'])) {
					$GLOBALS['log']->Log(JAWS_LOG_ERR, "No locations were found for map ID: $gid");
				}
			} else {
				reset($galleryPosts);
				foreach($galleryPosts as $parents) {		            
					if (
						(isset($parents['address']) && !empty($parents['address'])) || 
						(isset($parents['city']) && !empty($parents['city'])) || 
						(isset($parents['region']) && !empty($parents['region'])) || 
						(isset($parents['country_id']) && !empty($parents['country_id']))
					) {
						// build address
						$marker_target = isset($parents['marker_url_target']) && !empty($parents['marker_url_target']) ? $parents['marker_url_target'] : 'infowindow';
						if ($marker_target != 'infowindow') {
							$marker_url = isset($parents['marker_url']) && !empty($parents['marker_url']) ? $parents['marker_url'] : '#';
						} else {
							$marker_url = '';
						}
						$marker_address = isset($parents['address']) && !empty($parents['address']) ? $parents['address'] : '';
						$marker_address .= isset($parents['city']) && !empty($parents['city']) ? " ".$parents['city'] : '';
						$address_region = '';
						if (isset($parents['region']) && !empty($parents['region'])) {
							$region = $model->GetRegion((int)$parents['region']);
							if (!Jaws_Error::IsError($region)) {
								if (strpos($region['region'], " - US") !== false) {
									$region['region'] = str_replace(" - US", '', $region['region']);
								}
								if (strpos($region['region'], " - British") !== false) {
									$region['region'] = str_replace(" - British", '', $region['region']);
								}
								if (strpos($region['region'], " SAR") !== false) {
									$region['region'] = str_replace(" SAR", '', $region['region']);
								}
								if (strpos($region['region'], " - Islas Malvinas") !== false) {
									$region['region'] = str_replace(" - Islas Malvinas", '', $region['region']);
								}
								if (strpos($address_address, $region['region']) === false && strpos($address_address, $region['country_iso_code']) === false) {
									$address_region = ', '.$region['region'];
								}
							}
						}
						
						$marker_address .= $address_region;
						//$marker_address .= isset($parents['region']) ? ", ".$parents['region'] : '';
						$info_address = (isset($parents['address']) && !empty($parents['address']) ? $parents['address'] : '');
						$info_address .= '<br />'.(isset($parents['city']) && !empty($parents['city']) ? " ".$parents['city'] : '');
						$info_address .= (!empty($address_region) ? $address_region : '');
						// TODO: map country names to country_id if no other address info was supplied
						if (isset($parents['description']) && !empty($parents['description'])) {
							$description = $this->ParseText($parents['description'], 'Maps');
							$description = trim(preg_replace('/\s*\[[^)]*\]/', '', $description));
						} else {
							$description = '';
						}
						$main_image_src = '';
						if (isset($parents['image']) && !empty($parents['image'])) {
							$parents['image'] = $xss->filter(strip_tags($parents['image']));
							if (substr(strtolower($parents['image']), 0, 4) == "http") {
								if (substr(strtolower($parents['image']), 0, 7) == "http://") {
									$main_image_src = explode('http://', $parents['image']);
									foreach ($main_image_src as $img_src) {
										if (!empty($img_src)) {
											$main_image_src = 'http://'.$img_src;
											break;
										}
									}
								} else {
									$main_image_src = explode('https://', $parents['image']);
									foreach ($main_image_src as $img_src) {
										if (!empty($img_src)) {
											$main_image_src = 'https://'.$img_src;
											break;
										}
									}
								}
							} else {
								$thumb = Jaws_Image::GetThumbPath($parents['image']);
								$medium = Jaws_Image::GetMediumPath($parents['image']);
								if (file_exists(JAWS_DATA . 'files'.$thumb)) {
									$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$thumb;
								} else if (file_exists(JAWS_DATA . 'files'.$medium)) {
									$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$medium;
								} else if (file_exists(JAWS_DATA . 'files'.$parents['image'])) {
									$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$parents['image'];
								}
							}
						}
						if (!empty($main_image_src)) {
							$image_exists = "<img border=\"0\" src=\"".$main_image_src."\" width=\"150\" />";
							$image_style = "";
						} else {
							$image_exists = "";
							$image_style = "display: none; ";
						}
						$marker_title = "My Location";
						$marker_title = (isset($parents['address']) && !empty($parents['address']) ? $parents['address'] : (isset($parents['city']) && !empty($parents['city']) ? $parents['city'] : (!empty($address_region) ? $address_region : $marker_title)));
						$marker_title = (isset($parents['title']) && !empty($parents['title']) ? $parents['title'] : $marker_title);
						$marker_html = "<div style=\"".$image_style."clear: left;\">".$image_exists."</div>";
						$marker_html .= "<div style=\"clear: left;\"><b>".$marker_title."</b><br />".$info_address."<hr /><br />".$description."</div>";
						$marker_html .= "<div style=\"clear: both;\">&nbsp;</div>";
						
						$coordinates = '';
						// snoopy
						$snoopy = new Snoopy('Maps');
						$snoopy->agent = "Jaws";
						// Google Maps v2 Geocoding API
						//$geocode_url = "http://maps.google.com/maps/geo?q=".urlencode($marker_address)."&output=xml&key=".$key;
						// Google Maps v3 Geocoding API
						$geocode_url = "https://maps.googleapis.com/maps/api/geocode/xml?address=".urlencode(trim($marker_address))."&sensor=false&key=".$key;
						
						if($snoopy->fetch($geocode_url)) {
							$xml_content = $snoopy->results;
						
							// XML Parser
							$xml_parser = new XMLParser;
							$xml_result = $xml_parser->parse($xml_content, array("STATUS", "RESULT", "ADDRESS_COMPONENT", "TYPE", "LOCATION"));
							//echo '<pre>';
							//var_dump($xml_result);
							//echo '</pre>';
							
							if ($xml_result[0][0]['STATUS'] == 'OK') { 
								foreach ($xml_result[0] as $xresult) {
									// Coordinates
									if (empty($coordinates) && isset($xresult['LAT']) && isset($xresult['LNG'])) {
										$coordinates = $xresult['LNG'] . ',' . $xresult['LAT'];
									}
								}
							}
						}
						if (!empty($coordinates)) {
							$output_xml .=  "	<marker lnglat=\"".$coordinates."\" address=\"".htmlentities($marker_address)."\" title=\"".htmlentities($marker_title)."\" url=\"".$marker_url."\" target=\"".$marker_target."\" fs=\"".(isset($parents['marker_font_size']) ? $parents['marker_font_size'] : '10')."\" sfs=\"".(isset($parents['marker_subfont_size']) ? $parents['marker_subfont_size'] : '6')."\" bw=\"".(isset($parents['marker_border_width']) ? $parents['marker_border_width'] : '2')."\" ra=\"".(isset($parents['marker_radius']) ? $parents['marker_radius'] : '9')."\" fc=\"".(isset($parents['marker_font_color']) ? $parents['marker_font_color'] : 'FFFFFF')."\" fg=\"".(isset($parents['marker_foreground']) ? $parents['marker_foreground'] : '666666')."\" bc=\"".(isset($parents['marker_border_color']) ? $parents['marker_border_color'] : 'FFFFFF')."\" hfc=\"".(isset($parents['marker_hover_font_color']) ? $parents['marker_hover_font_color'] : '222222')."\" hfg=\"".(isset($parents['marker_hover_foreground']) ? $parents['marker_hover_foreground'] : 'FFFFFF')."\" hbc=\"".(isset($parents['marker_hover_border_color']) ? $parents['marker_hover_border_color'] : '666666')."\"><![CDATA[ ".$marker_html." ]]></marker>\n";
						}
					}
				}
				// reset xml output if no addresses were added
				/*
				if (!strpos($output_xml, "marker address")) {
					$output_xml = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>\n";
				}
				*/
			} 
		} else {
			if (isset($GLOBALS['log'])) {
				$GLOBALS['log']->Log(JAWS_LOG_ERR, "No locations were found for map");
			}
		}
		$output_xml .= "</markers>\n";
		echo $output_xml;
		exit;
	}
   
    /**
     * Hides Google API key alerts
     *
     * @access public
     * @return javascript string
     */
    function hideGoogleAPIAlerts()
    {
		// Make output a real JavaScript file!
		header('Content-type: text/javascript'); 
		echo "var KillAlerts = true; var realAlert = alert; var alert = new Function('a', 'if(!KillAlerts){realAlert(a)}');";
	}	
	
    /**
     * Displays an image
     *
     * @access public
     * @return string
     */
    function Rounded()
    {
		require_once JAWS_PATH . 'libraries/rounded_php/rounded.php';
    }
	
	/**
     * Return gadget item suggestions for prototype.js's Ajax.Autocompleter based on a query
	 * Usage: 
	 *  index.php?gadget=Maps&action=AutoCompleteRegions&query=UserInput
	 *  &methodcount=2&initial1gadget=Maps&initial1method=SearchRegions
	 *  &initial1paramcount=1&initial1param1=parametertopass&initial2gadget=Properties
	 * &initial2method=SearchAmenities&initial2paramcount=1&initial2param1=parametertopass
	 * &matchtogadget=Properties&matchtomethod=SearchCityWithProperties&paramcount=10
	 * &param1=status&param2=bedroom&param3=bathroom&param4=category
	 * &param5=community&param6=amenities&param7=offSet&param8=OwnerID&param9=pid
     * 
	 * Initial methods must be in AdminModel and take search string as first parameter and must return only an array of strings 
	 * We pass each string to the second method to search for gadget items
	 * Second method must return only an array of strings to show to user as options
	 *
     * @access public
     * @return html string
     */
    function AutoCompleteRegions()
    {
		// Output a real JavaScript file!
		//header('Content-type: text/javascript'); 
		
		$request =& Jaws_Request::getInstance();
		
		$return_format = $request->get('return', 'get');
		if (empty($return_format)) {
			$return_format = 'list';
		}
        $fetch = array('id','query','matchtogadget','matchtomethod','element');
		if ($return_format == 'list') {
			$output_html = "<ul>\n";
		} else {
			$output_html = '';
		}
		$suggestions_html = '';
		$data_html = '';
		$is_links = false;
		
		// Get params count post variable that we'll send to gadget's $post['matchtomethod']
		$paramCount = $request->get('paramcount', 'post');
		if (empty($paramCount)) {
			$paramCount = $request->get('paramcount', 'get');
		}
		if (!empty($paramCount)) {
			$paramCount = ((int)$paramCount)+1;
			if ($paramCount > 1) {
				$params = array();
				for ($i=1;$i<$paramCount;$i++) {
					//if ($request->get('param'.$i, 'post')) {
						$params[$i] = $request->get('param'.$i, 'post');
						if (empty($params[$i])) {
							$params[$i] = $request->get('param'.$i, 'get');
						}
						$fetch[] = 'param'.$i;
						if ($params[$i] == 'null') {
							$params[$i] = null;
						} else if ($params[$i] == 'true') {
							$params[$i] = true;
						} else if ($params[$i] == 'false') {
							$params[$i] = false;
						}
						//echo '<br />param'.$i.' = ';
						//var_dump($params[$i]);
					//}
				}
			}
		} else {
			$paramCount = 1;
		}
		$paramCount = $paramCount-1;
		//echo '<br />match params:'.$paramCount;
		$urlMethod = 'post';
		$post = $request->get($fetch, $urlMethod);
	    $search = $post['query'];
		if (empty($search)) {
			$urlMethod = 'get';
			$post = $request->get($fetch, $urlMethod);
			$search = $post['query'];
		}
		if (strtolower($search) == 'null') {
			$search = null;
		} else if (strtolower($search) == 'true') {
			$search = true;
		} else if (strtolower($search) == 'false') {
			$search = false;
		}
		//$id = (!empty($post['id']) ? $post['id'] : null);
		
		$res = array();
		$methodCount = $request->get('methodcount', $urlMethod);
		if (!empty($methodCount)) {
			$methodCount = ((int)$methodCount)+1;
			if ($methodCount > 1) {
				$initialparams = array();
				$stop_method = false;
				for ($i=1;$i<$methodCount;$i++) {
					// If this gadget and method are set
					if ($request->get('initial'.$i.'gadget', $urlMethod) && ($request->get('initial'.$i.'method', $urlMethod)) && $stop_method === false) {
						$initialgadget = $request->get('initial'.$i.'gadget', $urlMethod);
						$initialmethod = $request->get('initial'.$i.'method', $urlMethod);
						if (substr($initialmethod, 0, 6) == 'Search' || substr($initialmethod, 0, 3) == 'Get') {
							//echo '<br />Running: '.$initialgadget.'->'.$initialmethod;
							// Get parameters to pass to this method
							$initialParamsCount = $request->get('initial'.$i.'paramcount', $urlMethod);
							if (!empty($initialParamsCount)) {
								$initialParamsCount = ((int)$initialParamsCount)+1;
								if ($initialParamsCount > 1) {
									for ($j=1;$j<$initialParamsCount;$j++) {
										//if ($request->get('initial'.$i.'param'.$j, $urlMethod)) {
											$initialparams[$i][$j] = $request->get('initial'.$i.'param'.$j, $urlMethod);
											if (strtolower($initialparams[$i][$j]) == 'null') {
												$initialparams[$i][$j] = null;
											} else if (strtolower($initialparams[$i][$j]) == 'true') {
												$initialparams[$i][$j] = true;
											} else if (strtolower($initialparams[$i][$j]) == 'false') {
												$initialparams[$i][$j] = false;
											}
											//echo '<br />initial'.$i.'param'.$j.' = ';
											//var_dump($initialparams[$i][$j]);
										//}
									}
								}
							} else {
								$initialParamsCount = 1;
							}
							$initialParamsCount = $initialParamsCount-1;
							//echo '<br />Params: '.$initialParamsCount;
							
							$GLOBALS['app']->Translate->LoadTranslation($initialgadget, JAWS_GADGET);
							$gadgetinitial = $GLOBALS['app']->LoadGadget($initialgadget, 'AdminModel');
							if (!method_exists($gadgetinitial, $initialmethod)) {
								if ($return_format == 'list') {
									$suggestions_html = "<li><span class=\"informal\">Error: Method: ".$initialmethod." doesn't exist for Gadget: ".$initialgadget.".</span></li>\n";
								} else {
									//$suggestions_html = "Error: Method: ".$initialmethod." doesn't exist for Gadget: ".$initialgadget.";";
									$suggestions_html = "";
								}
								$output_html .= $suggestions_html;
								if ($return_format == 'list') {
									$output_html .= "</ul>\n";
								}
								echo $output_html;
								exit;
							}
							
							switch($initialParamsCount) {
								case 0:
									$results = $gadgetinitial->$initialmethod($search);
									break;
								case 1:
										$results = $gadgetinitial->$initialmethod($search,  $initialparams[$i][1]);
									break;
								case 2:
										$results = $gadgetinitial->$initialmethod($search, $initialparams[$i][1], $initialparams[$i][2]);
									break;
								case 3:
										$results = $gadgetinitial->$initialmethod($search, $initialparams[$i][1], $initialparams[$i][2], $initialparams[$i][3]);
									break;
								case 4:
										$results = $gadgetinitial->$initialmethod($search, $initialparams[$i][1], $initialparams[$i][2], $initialparams[$i][3], $initialparams[$i][4]);
									break;
								case 5:
										$results = $gadgetinitial->$initialmethod($search, $initialparams[$i][1], $initialparams[$i][2], $initialparams[$i][3], $initialparams[$i][4], $initialparams[$i][5]);
									break;
								case 6:
										$results = $gadgetinitial->$initialmethod($search, $initialparams[$i][1], $initialparams[$i][2], $initialparams[$i][3], $initialparams[$i][4], $initialparams[$i][5], $initialparams[$i][6]);
									break;
								case 7:
										$results = $gadgetinitial->$initialmethod($search, $initialparams[$i][1], $initialparams[$i][2], $initialparams[$i][3], $initialparams[$i][4], $initialparams[$i][5], $initialparams[$i][6], $initialparams[$i][7]);
									break;
								case 8:
									//if (isset($initialparams[$i][1]) && isset($initialparams[$i][2]) && isset($initialparams[$i][3]) && isset($initialparams[$i][4]) && isset($initialparams[$i][5]) && isset($initialparams[$i][6]) && isset($initialparams[$i][7]) && isset($initialparams[$i][8])) {
										$results = $gadgetinitial->$initialmethod($search, $initialparams[$i][1], $initialparams[$i][2], $initialparams[$i][3], $initialparams[$i][4], $initialparams[$i][5], $initialparams[$i][6], $initialparams[$i][7], $initialparams[$i][8]);
									/*
									} else {
										$paramSet = 0;
										if (isset($initialparams[$i][1])) {
											$paramSet++;
										}
										if (isset($initialparams[$i][2])) {
											$paramSet++;
										}
										if (isset($initialparams[$i][3])) {
											$paramSet++;
										}
										if (isset($initialparams[$i][4])) {
											$paramSet++;
										}
										if (isset($initialparams[$i][5])) {
											$paramSet++;
										}
										if (isset($initialparams[$i][6])) {
											$paramSet++;
										}
										if (isset($initialparams[$i][7])) {
											$paramSet++;
										}
										if (isset($initialparams[$i][8])) {
											$paramSet++;
										}
										$suggestions_html = "<li><span class=\"informal\">Error: ".$initialgadget."->".$initialmethod.": ".$paramSet." parameters set, 8 needed.</span></li>\n";
										$output_html .= $suggestions_html;
										$output_html .= "</ul>\n";
										echo $output_html;
										exit;
									}
									*/
									break;
								case 9:
										$results = $gadgetinitial->$initialmethod($search, $initialparams[$i][1], $initialparams[$i][2], $initialparams[$i][3], $initialparams[$i][4], $initialparams[$i][5], $initialparams[$i][6], $initialparams[$i][7], $initialparams[$i][8], $initialparams[$i][9]);
									break;
								case 10:
										$results = $gadgetinitial->$initialmethod($search, $initialparams[$i][1], $initialparams[$i][2], $initialparams[$i][3], $initialparams[$i][4], $initialparams[$i][5], $initialparams[$i][6], $initialparams[$i][7], $initialparams[$i][8], $initialparams[$i][9], $initialparams[$i][10]);
									break;
								case 11:
										$results = $gadgetinitial->$initialmethod($search, $initialparams[$i][1], $initialparams[$i][2], $initialparams[$i][3], $initialparams[$i][4], $initialparams[$i][5], $initialparams[$i][6], $initialparams[$i][7], $initialparams[$i][8], $initialparams[$i][9], $initialparams[$i][10], $initialparams[$i][11]);
									break;
								case 12:
										$results = $gadgetinitial->$initialmethod($search, $initialparams[$i][1], $initialparams[$i][2], $initialparams[$i][3], $initialparams[$i][4], $initialparams[$i][5], $initialparams[$i][6], $initialparams[$i][7], $initialparams[$i][8], $initialparams[$i][9], $initialparams[$i][10], $initialparams[$i][11], $initialparams[$i][12]);
									break;
								case 13:
									if ($initialparams[$i][13] == 'Y') {
										$is_links = true;
									}
									$results = $gadgetinitial->$initialmethod($search, $initialparams[$i][1], $initialparams[$i][2], $initialparams[$i][3], $initialparams[$i][4], $initialparams[$i][5], $initialparams[$i][6], $initialparams[$i][7], $initialparams[$i][8], $initialparams[$i][9], $initialparams[$i][10], $initialparams[$i][11], $initialparams[$i][12], $initialparams[$i][13]);
									break;
							}
						
							if (Jaws_Error::IsError($results)) {
								if ($return_format == 'list') {
									$suggestions_html = "<li><span class=\"informal\">Error: ".$results->GetMessage().".</span></li>\n";
								} else {
									//$suggestions_html = "Error: ".$results->GetMessage().";";
									$suggestions_html = "";
								}
								$output_html .= $suggestions_html;
								if ($return_format == 'list') {
									$output_html .= "</ul>\n";
								}
								echo $output_html;
								exit;
							} else {
								// For every suggestion found, we can get all of a gadget's items that are related
								if (!empty($post['matchtogadget']) && !empty($post['matchtomethod'])) {
									$gadgetmodel = $GLOBALS['app']->LoadGadget($post['matchtogadget'], 'AdminModel');
									if (!method_exists($gadgetmodel, $post['matchtomethod'])) {
										if ($return_format == 'list') {
											$suggestions_html = "<li><span class=\"informal\">Error: Method: ".$post['matchtomethod']." doesn't exist for Gadget: ".$post['matchtogadget'].".</span></li>\n";
										} else {
											//$suggestions_html = "Error: Method: ".$post['matchtomethod']." doesn't exist for Gadget: ".$post['matchtogadget'].";";
											$suggestions_html = "";
										}
										$output_html .= $suggestions_html;
										if ($return_format == 'list') {
											$output_html .= "</ul>\n";
										}
										echo $output_html;
										exit;
									}
									foreach ($results as $result) {
										foreach ($result as $resval) {
											//echo '<br />Result = '.$resval;
											// Send the matched location to a method to find gadget items
											$GLOBALS['app']->Translate->LoadTranslation($post['matchtogadget'], JAWS_GADGET);
											// Create different method call for each number of parameters we have (max of 13)
											switch($paramCount) {
												case 0:
													$items = $gadgetmodel->$post['matchtomethod']($resval);
													break;
												case 1:
														$items = $gadgetmodel->$post['matchtomethod']($resval, $params[1]);
													break;
												case 2:
														$items = $gadgetmodel->$post['matchtomethod']($resval, $params[1], $params[2]);
													break;
												case 3:
														$items = $gadgetmodel->$post['matchtomethod']($resval, $params[1], $params[2], $params[3]);
													break;
												case 4:
														$items = $gadgetmodel->$post['matchtomethod']($resval, $params[1], $params[2], $params[3], $params[4]);
													break;
												case 5:
														$items = $gadgetmodel->$post['matchtomethod']($resval, $params[1], $params[2], $params[3], $params[4], $params[5]);
													break;
												case 6:
														$items = $gadgetmodel->$post['matchtomethod']($resval, $params[1], $params[2], $params[3], $params[4], $params[5], $params[6]);
													break;
												case 7:
														$items = $gadgetmodel->$post['matchtomethod']($resval, $params[1], $params[2], $params[3], $params[4], $params[5], $params[6], $params[7]);
													break;
												case 8:
														$items = $gadgetmodel->$post['matchtomethod']($resval, $params[1], $params[2], $params[3], $params[4], $params[5], $params[6], $params[7], $params[8]);
													break;
												case 9:
														$items = $gadgetmodel->$post['matchtomethod']($resval, $params[1], $params[2], $params[3], $params[4], $params[5], $params[6], $params[7], $params[8] , $params[9]);
													break;
												case 10:
														$items = $gadgetmodel->$post['matchtomethod']($resval, $params[1], $params[2], $params[3], $params[4], $params[5], $params[6], $params[7], $params[8], $params[9], $params[10]);
													break;
												case 11:
														$items = $gadgetmodel->$post['matchtomethod']($resval, $params[1], $params[2], $params[3], $params[4], $params[5], $params[6], $params[7], $params[8], $params[9], $params[10], $params[11]);
													break;
												case 12:
														if ($post['matchtomethod'] == 'SearchKeyWithProperties') {
															if ($initialmethod == 'SearchRegions') {
																$params[10] = 'city';
																$params[12] = 'city';
															} else if ($initialmethod == 'SearchAmenities') {
																$params[10] = 'amenity';
																$params[12] = 'amenity';
															}
														}
														$items = $gadgetmodel->$post['matchtomethod']($resval, $params[1], $params[2], $params[3], $params[4], $params[5], $params[6], $params[7], $params[8], $params[9], $params[10], $params[11], $params[12]);
													break;
												case 13:
													//if (isset($params[1]) && isset($params[2]) && isset($params[3]) && isset($params[4]) && isset($params[5]) && isset($params[6]) && isset($params[7]) && isset($params[8]) && isset($params[9]) && isset($params[10]) && isset($params[11]) && isset($params[12]) && isset($params[13])) {
														$items = $gadgetmodel->$post['matchtomethod']($resval, $params[1], $params[2], $params[3], $params[4], $params[5], $params[6], $params[7], $params[8], $params[9], $params[10], $params[11], $params[12], $params[13]);
													/*
													} else {
														$paramSet = 0;
														if (isset($params[1])) {
															$paramSet++;
														}
														if (isset($params[2])) {
															$paramSet++;
														}
														if (isset($params[3])) {
															$paramSet++;
														}
														if (isset($params[4])) {
															$paramSet++;
														}
														if (isset($params[5])) {
															$paramSet++;
														}
														if (isset($params[6])) {
															$paramSet++;
														}
														if (isset($params[7])) {
															$paramSet++;
														}
														if (isset($params[8])) {
															$paramSet++;
														}
														if (isset($params[9])) {
															$paramSet++;
														}
														if (isset($params[10])) {
															$paramSet++;
														}
														if (isset($params[11])) {
															$paramSet++;
														}
														if (isset($params[12])) {
															$paramSet++;
														}
														if (isset($params[13])) {
															$paramSet++;
														}
														$suggestions_html = "<li><span class=\"informal\">Error: ".$post['matchtogadget']."->".$post['matchtomethod'].": ".$paramSet." parameters set, 13 needed.</span></li>\n";
														$output_html .= $suggestions_html;
														$output_html .= "</ul>\n";
														echo $output_html;
														exit;
													}
													*/
													break;
											}
											if (Jaws_Error::IsError($items)) {
												//$GLOBALS['app']->Session->PushLastResponse($items->getMessage(), RESPONSE_ERROR);
												if ($return_format == 'list') {
													$suggestions_html = "<li><span class=\"informal\">Error: ".$items->GetMessage().".</span></li>\n";
												} else {
													//$suggestions_html = "Error: ".$items->GetMessage().";";
													$suggestions_html = "";
												}
												$output_html .= $suggestions_html;
												if ($return_format == 'list') {
													$output_html .= "</ul>\n";
												}
												echo $output_html;
												exit;
											} else {
												foreach ($items as $item) {
													if ($i == 1 && $stop_method === false) {
														$stop_method = true;
													}
													if (!in_array($item, $res)) {
														$res[] = $item;
													}
												}
											}
										}
									}
								} else {
									foreach ($results as $result) {
										foreach ($result as $resval) {
											if ($i == 1 && $stop_method === false) {
												$stop_method = true;
											}
											if (!in_array($resval, $res)) {
												$res[] = $resval;
											}
										}
									}
								}
							}
						}
					} else {
						/*
						$suggestions_html = "<li><span class=\"informal\">Error: Method: ".$i." requested, but method or gadget not set.</span></li>\n";
						$output_html .= $suggestions_html;
						$output_html .= "</ul>\n";
						echo $output_html;
						exit;
						*/
					}
				}
			}
		}
		
		if (!isset($res[0][0])) {	
			if ($return_format == 'list') {
				$suggestions_html = "<li><span class=\"informal\">No matches. Please check your spelling, or try more popular terms.</span></li>\n";
			} else {
				//$suggestions_html = "No matches. Please check your spelling, or try more popular terms;";
				$suggestions_html = "";
			}
			$output_html .= $suggestions_html;
			if ($return_format == 'list') {
				$output_html .= "</ul>\n";
			}
			echo $output_html;
			exit;
		}
		/*
		if (count($res) && $is_links === false) {
			// Sort result array
			$subkey = 0; 
			$temp_array = array();
			
			$temp_array[key($res)] = array_shift($res);

			foreach($res as $key => $val){
				$offset = 0;
				$found = false;
				foreach($temp_array as $tmp_key => $tmp_val)
				{
					if(!$found and strtolower($val[$subkey]) > strtolower($tmp_val[$subkey]))
					{
						$temp_array = array_merge(    (array)array_slice($temp_array,0,$offset),
													array($key => $val),
													array_slice($temp_array,$offset)
												  );
						$found = true;
					}
					$offset++;
				}
				if(!$found) $temp_array = array_merge($temp_array, array($key => $val));
			}

			$res = array_reverse($temp_array);
			//$exact = $temp_array;
		}
		*/
		foreach ($res as $r) {
			if ($return_format == 'list') {
				$suggestions_html .= "<li".($urlMethod == 'get' ? ' onclick="if(typeof gotMatch != \'undefined\'){gotMatch = true;}; document.getElementById(\''.$post['element'].'\').value = \''.substr($r, 0, strpos($r, '<')).'\'; document.getElementById(\'search_choices\').style.display = \'none\';"' : ' onclick="if(typeof gotMatch != \'undefined\'){gotMatch = true;};"').">".$r."</li>\n";
			} else {
				$suggestions_html .= $r.";";
			}
		}
		
		$output_html .= $suggestions_html;
		if ($return_format == 'list') {
			$output_html .= "</ul>\n";
		}
		echo $output_html;
	}	
	
}
