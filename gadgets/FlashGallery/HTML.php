<?php
/**
 * FlashGallery Gadget
 *
 * @category   Gadget
 * @package    FlashGallery
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class FlashGalleryHTML extends Jaws_GadgetHTML
{
    var $_Name = 'FlashGallery';
    /**
     * Constructor
     *
     * @access public
     */
    function FlashGalleryHTML()
    {
        $this->Init('FlashGallery');
    }

    /**
     * Excutes the default action, currently redirecting to index.
     *
     * @access public
     * @return string
     */
    function DefaultAction()
    {
	    //header("Location: ../../index.php");
        return $this->Albums();
	}

    /**
     * Organize and display photos into Albums. 
     *
     * @access 	public
     * @return 	string
     */
    function Albums($embedded = false, $referer = null, $xml = false, $searchownerid = '')
    {
		require_once JAWS_PATH . 'include/Jaws/Image.php';
        
		if ($xml === false) {	
			$GLOBALS['app']->Layout->AddScriptLink('libraries/js/global2.js');
			$GLOBALS['app']->Layout->AddHeadLink('gadgets/Ads/resources/style.css', 'stylesheet', 'text/css');
			$GLOBALS['app']->Layout->AddHeadLink('libraries/autocomplete/autocomplete.css', 'stylesheet', 'text/css', 'default');
		}
		$request =& Jaws_Request::getInstance();
        $post = $request->get(array('id', 'gadget', 'name'), 'get');

        $GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$pageconst = 12;
		
        $model = $GLOBALS['app']->LoadGadget('Ads', 'Model');
		$adsLayout = $GLOBALS['app']->LoadGadget('Ads', 'LayoutHTML');

        if (is_null($id)) {
			$id = $request->get('id', 'post');
			if (empty($id)) {
				$id = $request->get('id', 'get');
			}
        }
		if (empty($id) || (!is_numeric($id) && strtolower($id) == 'all') || !empty($searchownerid) || !empty($searchbrand)) {
			$id = 'all';
		} else {
			$id = $xss->defilter($id);
		}

		if ($id != 'all') {
			$parent = $model->GetAdParent($id);
		}
        if (Jaws_Error::IsError($parent) && $id != "all" && empty($searchownerid) && empty($searchbrand) && !isset($parent['adparentid'])) {
			require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
            return Jaws_HTTPError::Get(404);
        } else {
			if ($xml === false) {	
				$tpl = new Jaws_Template('gadgets/Ads/templates/');
				$tpl->Load('normal.html');
				$tpl->SetBlock('ads');

			} else {
				$output_xml = '';
			}
			$start = $request->get('start', 'post');
			if (empty($start)) {
				$start = $request->get('start', 'get');
			}
			if (empty($start)) {
				$start = 0;
			} else {
				$start = (int)$start;
			}
			$searchstatus = $request->get('status', 'post');
			if (empty($searchstatus)) {
				$searchstatus = $request->get('status', 'get');
			}
			if (empty($searchstatus)) {
				$searchstatus = '720';
			}
			$searchkeyword = $request->get('keyword', 'post');
			if (empty($searchkeyword)) {
				$searchkeyword = $request->get('keyword', 'get');
			}
			if (trim($searchbrand) == '') {
				$searchbrand = $request->get('brand', 'post');
				if (empty($searchbrand)) {
					$searchbrand = $request->get('brand', 'get');
				}
			}
			$preview = $request->get('preview', 'post');
			if (empty($preview)) {
				$preview = $request->get('preview', 'get');
			}
			$sortColumn = $request->get('sortColumn', 'post');
			if (empty($sortColumn)) {
				$sortColumn = $request->get('sortColumn', 'get');
			}
			$sortDir = $request->get('sortDir', 'post');
			if (empty($sortDir)) {
				$sortDir = $request->get('sortDir', 'get');
			}
			if (strtolower($post['gadget']) == 'users' && isset($post['name']) && !empty($post['name'])) {
				require_once JAWS_PATH . 'include/Jaws/User.php';
				$jUser = new Jaws_User;
				$info  = $jUser->GetUserInfoByName($post['name'], true, true, true, true);
				$searchownerid = $info['id'];
			}
			if (empty($searchownerid)) {
				$searchownerid = $request->get('owner_id', 'post');
			}
			if (empty($searchownerid)) {
				$searchownerid = $request->get('owner_id', 'get');
			}
			if (!empty($searchownerid)) {
				$searchownerid = (int)$searchownerid;
			}
			
			$searchlocation = $request->get('location', 'post');
			if (empty($searchlocation)) {
				$searchlocation = $request->get('location', 'get');
			}
			if (empty($searchlocation)) {
				$remote_info = Jaws_Utils::GetRemoteGeoLocation();
				if (!empty($remote_info['longitude']) && !empty($remote_info['latitude'])) {
					$searchlocation = $remote_info['latitude'].','.$remote_info['longitude'];
				}
			}
			$searchradius = $request->get('radius', 'post');
			if (empty($searchradius)) {
				$searchradius = $request->get('radius', 'get');
			}
			if (empty($searchradius)) {
				$searchradius = 50;
			}
				
			if ($xml === false) {	
				$tpl->SetBlock('ads/content');
				$tpl->SetVariable('pagetype', 'ads');
				
				$breadcrumb_start = '<span class="center_nav_font"><a href="'.$GLOBALS['app']->Map->GetURLFor('Ads', 'Category', array('id' => 'all')).'" class="center_nav_link">View All</a>&nbsp;&nbsp;';
				$breadcrumbHTML = '';
				$adparentImage = '';
				
				if ($id != "all") {
					$tpl->SetVariable('id', (isset($parent['adparentid']) && !empty($parent['adparentid']) ? $xss->parse($parent['adparentid']) : urlencode($id)));
					$tpl->SetVariable('adparentID', $xss->parse($parent['adparentid']));
					$tpl->SetVariable('adparentParent', $xss->parse($parent['adparentparent']));
					$tpl->SetVariable('adparentsort_order', $xss->parse($parent['adparentsort_order']));
					$tpl->SetVariable('adparentCategory_Name', $xss->parse(strip_tags($parent['adparentcategory_name'])));
					$main_image_src = '';
					if (!empty($parent['adparentimage']) && isset($parent['adparentimage'])) {
						if (strpos($parent['adparentimage'],".swf") !== false) {
							// Flash file not supported
						} else if (substr($parent['adparentimage'],0,7) == "GADGET:") {
							$main_image_src = $xss->filter(strip_tags($parent['adparentimage']));
						} else {
							$main_image_src = $xss->filter(strip_tags($parent['adparentimage']));
							if (substr(strtolower($main_image_src), 0, 4) == "http") {
								if (substr(strtolower($main_image_src), 0, 7) == "http://") {
									$main_image_src = explode('http://', $main_image_src);
									foreach ($main_image_src as $img_src) {
										if (!empty($img_src)) {
											$main_image_src = 'http://'.$img_src;
											break;
										}
									}
								} else {
									$main_image_src = explode('https://', $main_image_src);
									foreach ($main_image_src as $img_src) {
										if (!empty($img_src)) {
											$main_image_src = 'https://'.$img_src;
											break;
										}
									}
								}
							} else {
								$thumb = Jaws_Image::GetThumbPath($main_image_src);
								$medium = Jaws_Image::GetMediumPath($main_image_src);
								if (file_exists(JAWS_DATA . 'files'.$thumb)) {
									$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$thumb;
								} else if (file_exists(JAWS_DATA . 'files'.$medium)) {
									$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$medium;
								} else if (file_exists(JAWS_DATA . 'files'.$main_image_src)) {
									$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$main_image_src;
								}
							}
						}
					}
					if (!empty($main_image_src) && empty($parent['adparentimage_code']) && strpos($main_image_src, 'GADGET:') === false) {
						$image_url = '<a href="javascript:void(0);" onclick="window.open(\''.$main_image_src.'\',\'\',\'scrollbars=no\')">';
						if ((isset($parent['adparenturl']) && !empty($parent['adparenturl'])) && (isset($parent['adparenturl_target']) && !empty($parent['adparenturl_target']))) {
							$image_url = '<a href="'.$xss->parse($parent['adparenturl']).'" target="'.$xss->parse($parent['adparenturl_target']).'">';
						}
						$image = '<img style="padding: 5px;" border="0" src="'.$main_image_src.'" width="100" '.(strtolower(substr($main_image_src, -3)) == "gif" || strtolower(substr($main_image_src, -3)) == "png" || strtolower(substr($main_image_src, -3)) == "bmp" ? 'height="100"' : '').' />';				
						$adparentImage = $image;
						$tpl->SetVariable('adparentImage', $image_url.$image.'</a>');
						$tpl->SetVariable('image_style', '');
					} else {
						$adparentImage = '';
						$tpl->SetVariable('adparentImage', '');
						$tpl->SetVariable('image_style', '');
					}
					$tpl->SetVariable('adparentDescription', $this->ParseText($parent['adparentdescription'], 'Store'));
					$tpl->SetVariable('adparentActive', $xss->parse($parent['adparentactive']));
					$tpl->SetVariable('adparentOwnerID', $xss->parse($parent['adparentownerid']));
					$tpl->SetVariable('adparentCreated', $xss->parse($parent['adparentcreated']));
					$tpl->SetVariable('adparentUpdated', $xss->parse($parent['adparentupdated']));
					$tpl->SetVariable('adparentFeatured', $xss->parse($parent['adparentfeatured']));
					$tpl->SetVariable('adparentFast_url', $xss->parse($parent['adparentfast_url']));
					$tpl->SetVariable('adparentRss_url', $xss->parse($parent['adparentrss_url']));
					$breadcrumbHTML .= '>&nbsp;&nbsp;'.$xss->parse(strip_tags($parent['adparentcategory_name'])).'&nbsp;&nbsp;';
					/*
					$parentID = $parent['adparentparent'];
					while ($parentID > 0) {
						$grandparent = $model->GetProductParent((int)$parent['adparentparent']);
						if (!Jaws_Error::IsError($grandparent)) {
							$breadcrumbHTML = '>&nbsp;&nbsp;<a href="'.$GLOBALS['app']->Map->GetURLFor('Store', 'Category', array('id' => $grandparent['adparentfast_url'])).'">'.$xss->parse(strip_tags($grandparent['adparentcategory_name'])).'</a>&nbsp;&nbsp;'.$breadcrumbHTML;
							$parentID = $grandparent['adparentparent'];
						}
					}
					*/
				} else {
					$tpl->SetVariable('id', 'Category');
					$tpl->SetVariable('adparentDescription', '');
					$adparentImage = '';
					$tpl->SetVariable('adparentImage', '');
					$tpl->SetVariable('image_style', '');
				}
				$brandname = '';
				if (!empty($searchbrand)) {
					$brand = $model->GetBrand((int)$searchbrand);
					if (!Jaws_Error::IsError($brand) && isset($brand['id']) && !empty($brand['id'])) {
						if (isset($brand['title']) && !empty($brand['title'])) {
							$brandname = $xss->parse(strip_tags($brand['title']));
						}
						if (isset($brand['description']) && !empty($brand['description'])) {
							$tpl->SetVariable('adparentDescription', $this->ParseText($brand['description'], 'Store'));
						}
						$brand_image_src = '';
						if (!empty($brand['image']) && isset($brand['image'])) {
							if (strpos($brand['image'],".swf") !== false) {
								// Flash file not supported
							} else if (substr($brand['image'],0,7) == "GADGET:") {
								$brand_image_src = $xss->parse(strip_tags($brand['image']));
							} else {
								$brand_image_src = $xss->parse(strip_tags($brand['image']));
								if (substr(strtolower($brand_image_src), 0, 4) == "http") {
									if (substr(strtolower($brand_image_src), 0, 7) == "http://") {
										$brand_image_src = explode('http://', $brand_image_src);
										foreach ($brand_image_src as $img_src) {
											if (!empty($img_src)) {
												$brand_image_src = 'http://'.$img_src;
												break;
											}
										}
									} else {
										$brand_image_src = explode('https://', $brand_image_src);
										foreach ($brand_image_src as $img_src) {
											if (!empty($img_src)) {
												$brand_image_src = 'https://'.$img_src;
												break;
											}
										}
									}
								} else {
									$thumb = Jaws_Image::GetThumbPath($brand_image_src);
									$medium = Jaws_Image::GetMediumPath($brand_image_src);
									if (file_exists(JAWS_DATA . 'files'.$thumb)) {
										$brand_image_src = $GLOBALS['app']->getDataURL() . 'files'.$thumb;
									} else if (file_exists(JAWS_DATA . 'files'.$medium)) {
										$brand_image_src = $GLOBALS['app']->getDataURL() . 'files'.$medium;
									} else if (file_exists(JAWS_DATA . 'files'.$brand_image_src)) {
										$brand_image_src = $GLOBALS['app']->getDataURL() . 'files'.$brand_image_src;
									}
								}
							}
						}
						if (!empty($brand_image_src) && empty($brand['image_code']) && strpos($brand_image_src, 'GADGET:') === false) {
							$image_url = '<a href="javascript:void(0);" onclick="window.open(\''.$brand_image_src.'\',\'\',\'scrollbars=no\')">';
							if ((isset($brand['url']) && !empty($brand['url'])) && (isset($brand['url_target']) && !empty($brand['url_target']))) {
								$image_url = '<a href="'.$xss->parse($brand['url']).'" target="'.$xss->parse($brand['url_target']).'">';
							}
							$image = '<img style="padding: 5px;" border="0" src="'.$brand_image_src.'" width="100" '.(strtolower(substr($brand_image_src, -3)) == "gif" || strtolower(substr($brand_image_src, -3)) == "png" || strtolower(substr($brand_image_src, -3)) == "bmp" ? 'height="100"' : '').' />';				
							$adparentImage = $image;
							$tpl->SetVariable('adparentImage', $image_url.$image.'</a>');
							$tpl->SetVariable('image_style', '');
						}
					}
				}
				
				
				if ($breadcrumbHTML == '') {
					$breadcrumbHTML = '>&nbsp;&nbsp;Searching ads '.(!empty($searchkeyword) ? 'that match the keyword  <b>"'.str_replace(' - Amenity', '', $searchkeyword).'"</b> ' : '').(!empty($brandname) ? (!empty($searchkeyword) ? ' AND ' : ''). 'that match the brand  <b>"'.$brandname.'"</b> ': '').(!empty($searchlocation) ? (!empty($searchkeyword) || !empty($searchbrand) ? ' AND ' : ''). 'near your location' : '');
				}
				$breadcrumbHTML = $breadcrumb_start.$breadcrumbHTML."</span>";
				$tpl->SetVariable('BREADCRUMB', $breadcrumbHTML);
				//$tpl->SetVariable('DPATH',  JAWS_DPATH);
				$tpl->SetVariable('HTTP_REFERER',  $GLOBALS['app']->GetSiteURL());
				$tpl->SetVariable('JAWS_URL',  $GLOBALS['app']->GetJawsURL() . "/");
			}
			
			// TODO: Update order 'Active' status via RevisedDate and only show Active
			// e.g. If (OwnerID != 0) {UsersModel->GetUserSubscribedByID(subscription_method, uid, item_id) }
			// send Post records						
			$adminmodel = $GLOBALS['app']->LoadGadget('Ads', 'AdminModel');
			if (!empty($searchlocation)) {
				$posts = $model->GetAdsByLocation($searchlocation, (int)$searchradius, $searchstatus);
			} else if (!empty($searchownerid)) {
				$posts = $model->GetAdsOfUserID($searchownerid, $searchstatus);
			} else if ((!empty($searchstatus) || !empty($searchkeyword) || !empty($searchbrand) || !empty($sortColumn) || !empty($sortDir)) && (isset($parent['adparentid']) && !empty($parent['adparentid'])) && $id != 'all') {
				$posts = $adminmodel->MultipleSearchAds($searchstatus, $searchkeyword, $searchbrand, '', $searchlocation, null, null, $parent['adparentid'], $sortColumn, $sortDir, 'Y');
			} else if ($id == 'all') {
				$posts = $adminmodel->MultipleSearchAds($searchstatus, $searchkeyword, $searchbrand, '', $searchlocation, null, null, null, $sortColumn, $sortDir, 'Y');
			} else {
				$posts = $model->GetAllAdsOfParent($parent['adparentid'], $sortColumn, $sortDir, 'Y');
			}
			
			if (!Jaws_Error::IsError($posts)) {
				/*
				echo '<pre>';
				var_dump($posts);
				echo '</pre>';
				exit;
				*/
				$page_cycle = '';
				
				if ($GLOBALS['app']->Registry->Get('/gadgets/Ads/randomize') == 'Y') {
					$session_id = $GLOBALS['app']->Session->GetAttribute('session_id');
					$string = $session_id;

					/*
					echo '<pre>';
					print_r($posts);
					echo '</pre>';
					*/

					Jaws_Utils::seoShuffle($posts,$string);

					/*
					echo '<pre>';
					print_r($posts);
					echo '</pre>';
					
					if ($start == 0 && !$GLOBALS['app']->Session->GetAttribute('show_properties') && $xml === false) {
						shuffle($posts);
						$GLOBALS['app']->Session->SetAttribute('show_properties', 'shown');
					}
					*/
				}
				
				$countPosts = count($posts);
				if (!$countPosts <= 0) {
					// Pagination
					if ($xml === false) {	
						$page_cycle .= '<div style="text-align: right;"><span style="font-size: small;">PAGE </span>';
						if (($countPosts % $pageconst) == 0 && $countPosts > 0) {
							$gonumtop = (int)(($countPosts - 1)/$pageconst) + 1;
						} else {
							$gonumtop = (int)($countPosts/$pageconst) + 1;
						}
						$a = 1;
						$max_cycle = 7;
						$max_shown = false;
						for ($z=0; $z<$gonumtop; $z++) {
							if (($z*$pageconst) == $start || $a <= ((($start/$pageconst)+1)+$max_cycle) && $a >= ((($start/$pageconst)+1)-$max_cycle) || $z == 0 || $z == ($gonumtop-1)) {
								$page_cycle .= '<b>';
							}
							 if (($z*$pageconst) == $start) {
								$page_cycle .= '&nbsp;&nbsp;[&nbsp;';
							 } else {
								if ($a <= ((($start/$pageconst)+1)+$max_cycle) && $a >= ((($start/$pageconst)+1)-$max_cycle) || $z == 0 || $z == ($gonumtop-1)) {
									$page_cycle .= '<a href="'.$GLOBALS['app']->getSiteURL().'/index.php?gadget=Ads&action=Category&id='.$id.'&status='.$searchstatus.'&sortColumn='.$sortColumn.'&sortDir='.$sortDir.'&keyword='.$searchkeyword.'&location='.$searchlocation.'&radius='.$searchradius.'&start='.($z*$pageconst).'" style="text-decoration:underline;">';
								}
							 }
							if ($a <= ((($start/$pageconst)+1)+$max_cycle) && $a >= ((($start/$pageconst)+1)-$max_cycle) || $z == 0 || $z == ($gonumtop-1)) {
								$page_cycle .= $a;
							} else {
								if ($max_shown === false) {
									$page_cycle .= '&nbsp;&nbsp;...&nbsp;&nbsp;';
									$max_shown = true;
								}
							}
							 if (($z*$pageconst) != $start) {
								if ($a <= ((($start/$pageconst)+1)+$max_cycle) && $a >= ((($start/$pageconst)+1)-$max_cycle) || $z == 0 || $z == ($gonumtop-1)) {
									$page_cycle .= '</a>';
								}
								if ($z != $gonumtop && (($z+1) != ($start/$pageconst)) && $a <= ((($start/$pageconst)+1)+$max_cycle) && $a >= ((($start/$pageconst)+1)-$max_cycle)) {
									$page_cycle .= '&nbsp;&nbsp;&nbsp;';
								}
							 } else {
							 $page_cycle .= '&nbsp;]&nbsp;&nbsp;';
							 }
							if (($z*$pageconst) == $start || $a <= ((($start/$pageconst)+1)+$max_cycle) && $a >= ((($start/$pageconst)+1)-$max_cycle) || $z == 0 || $z == ($gonumtop-1)) {
								$page_cycle .= '</b>';
							}
							if ($a >= $gonumtop) {
								break;
							}
							$a++;
						} 
						$page_cycle .= '</div>';
						$tpl->SetVariable('PAGE_CYCLE', $page_cycle);
						$tpl->SetVariable('PAGE_CYCLE2', $page_cycle);
					}
					// Category Map
					//reset($posts);
					if ($xml === false) {	
						if ($GLOBALS['app']->Registry->Get('/gadgets/Ads/showmap') == 'Y') {
							$tpl->SetVariable('CATEGORY_MAP', $adsLayout->CategoryMap('all'));
						} else {
							$tpl->SetVariable('CATEGORY_MAP', '');
						}
					}
					if ((($start+$pageconst)-1) <= $countPosts) {
						$endcount = ($start+$pageconst)-1;
					} else {
						$endcount = $countPosts;
					}

					for ($i=$start;$i<$endcount;$i++) {
					//foreach($posts as $page) {		            
						if ($posts[$i]['active'] == 'Y') {
							if ((int)$posts[$i]['ownerid'] > 0) {
								require_once JAWS_PATH . 'include/Jaws/User.php';
								$userModel = new Jaws_User;
								$info = $userModel->GetUserInfoByID((int)$posts[$i]['ownerid'], true, true, true, true);
								if (Jaws_Error::IsError($info)) {
									return $info;
								}
							}
							if ($xml === true) {	
								if (((isset($info['address']) && !empty($info['address'])) || (isset($info['city']) && !empty($info['city']))) && isset($info['region']) && !empty($info['region'])) {
									// build address
									$address_region = '';
									$address_city = '';
									$address_address = (isset($info['address']) ? $info['address'] : '');
									
									$marker_address = $address_address;
									if (isset($info['city']) && !empty($info['city'])) {
										$address_city = (strpos($address_address, $info['city']) === false ? " ".$info['city'] : '');
									}
									$marker_address .= $address_city;									
									$marker_address .= ', '.$info['region'];
									
									$marker_html = '';
									
									// FIXME: Save coordinates in user DB table
									$coordinates = '';
									$key = "ABQIAAAAbHKtlYQg6w5AJ9d2_shprxRSdpoJbNiUEES6uLAQrtqeLOB2WBROsVk6Deve8XT-33SEgoVpReKw5Q";
									include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
									include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'XMLParser.php';
									// snoopy
									$snoopy = new Snoopy('Ads');
									$snoopy->agent = "Jaws";
									$geocode_url = "http://maps.google.com/maps/geo?q=".urlencode($marker_address)."&output=xml&key=".$key;
									//$geocode_url = "http://maps.google.com/maps/geo?q=Dawsonville, GA&output=xml&key=ABQIAAAAbHKtlYQg6w5AJ9d2_shprxRSdpoJbNiUEES6uLAQrtqeLOB2WBROsVk6Deve8XT-33SEgoVpReKw5Q";
									//echo '<br />Google Geocoder: '.$geocode_url;
									if($snoopy->fetch($geocode_url)) {
										$xml_content = $snoopy->results;
									
										// XML Parser
										$xml_parser = new XMLParser;
										$xml_result = $xml_parser->parse($xml_content, array("STATUS", "PLACEMARK"));
										//echo '<pre>';
										//var_dump($xml_result);
										//echo '</pre>';
										for ($i=0;$i<$xml_result[1]; $i++) {
											//$is_totalResults = false;
											if ($xml_result[0][0]['CODE'] == '200' && isset($xml_result[0][$i]['COUNTRYNAMECODE']) && isset($xml_result[0][$i]['ADMINISTRATIVEAREANAME']) && isset($xml_result[0][$i]['LOCALITYNAME']) && isset($xml_result[0][$i]['ADDRESS']) && isset($xml_result[0][$i]['COORDINATES']) && empty($coordinates)) {
												//if (isset($xml_result[0][$i]['COORDINATES'])) {
													$coordinates = $xml_result[0][$i]['COORDINATES'];
												//}
											}
										}
									}

									/*
									$info_address = $address_address;
									$info_address .= '<br />'.$address_city;
									$info_address .= $address_region;

									$description = '';
									$image_exists = "";
									$image_style = "display: none; ";
									if (isset($posts[$i]['description'])) {
										$description = $this->ParseText($posts[$i]['description'], 'Properties');
										$description = trim(preg_replace('/\s*\[[^)]*\]/', '', $description));
									}
									if (isset($posts[$i]['image'])) {
										$image = $GLOBALS['app']->getDataURL() . 'files'.$xss->filter($posts[$i]['image']);
										if (file_exists($image) && substr($image, -5) != "files") {
											$image_exists = "<img border=\"0\" src=\"".$image."\" width=\"150\" />";
											$image_style = "";
										}
									}
									$marker_html .= "<div style=\"".$image_style."clear: left;\">".$image_exists."</div>";
									$marker_html .= "<div style=\"clear: left;\"><b>".(isset($posts[$i]['title']) ? $posts[$i]['title'] : 'My Location')."</b><br />".$info_address."<hr /><br />".$description."</div>";
									$marker_html .= "<div style=\"clear: both;\">&nbsp;</div>";
									*/
									
									$output_xml .=  "	<marker address=\"\" lnglat=\"".$coordinates."\" title=\"".($i+1)."\" ext=\"".(isset($info['company']) ? $xss->parse($info['company']) : 'Location')."\" url=\"".urlencode($GLOBALS['app']->Map->GetURLFor('Ads', 'Ad', array('id' => $posts[$i]['id'])))."\" target=\"_self\" fs=\"10\" sfs=\"6\" bw=\"2\" ra=\"9\" fc=\"FFFFFF\" fg=\"666666\" bc=\"FFFFFF\" hfc=\"222222\" hfg=\"FFFFFF\" hbc=\"666666\"><![CDATA[ ".$marker_html." ]]></marker>\n";
								}
							} else {
								$tpl->SetBlock('ads/content/ad');
								// TODO: Implement Preview mode (use cookie to store length of time the preview is available)				
								$hasDetails = false;
								$tpl->SetVariable('title', $xss->parse(strip_tags($posts[$i]['title'])));
								$tpl->SetVariable('id', (int)$posts[$i]['id']);
								$tpl->SetVariable('LinkID', $id);
								//$property_link = $GLOBALS['app']->GetSiteURL().'/index.php?gadget=Properties&action=Property&id='.$posts[$i]['id'].'&linkid='.($id != 'all' ? $id : '');
								$property_link = $GLOBALS['app']->Map->GetURLFor('Ads', 'Ad', array('id' => (int)$posts[$i]['id']));
								$tpl->SetVariable('ad_link', $property_link);
								$image_src = '';
								if (!empty($posts[$i]['image']) && isset($posts[$i]['image'])) {
									if (strpos($posts[$i]['image'],".swf") !== false) {
										// Flash file not supported
									} else if (substr($posts[$i]['image'],0,7) == "GADGET:") {
										$image_src = $xss->parse(strip_tags($posts[$i]['image']));
									} else {
										$image_src = $xss->parse(strip_tags($posts[$i]['image']));
										if (substr(strtolower($image_src), 0, 4) == "http") {
											if (substr(strtolower($image_src), 0, 7) == "http://") {
												$image_src = explode('http://', $image_src);
												foreach ($image_src as $img_src) {
													if (!empty($img_src)) {
														$image_src = 'http://'.$img_src;
														break;
													}
												}
											} else {
												$image_src = explode('https://', $image_src);
												foreach ($image_src as $img_src) {
													if (!empty($img_src)) {
														$image_src = 'https://'.$img_src;
														break;
													}
												}
											}
										} else {
											$thumb = Jaws_Image::GetThumbPath($image_src);
											$medium = Jaws_Image::GetMediumPath($image_src);
											if (file_exists(JAWS_DATA . 'files'.$thumb)) {
												$image_src = $GLOBALS['app']->getDataURL() . 'files'.$thumb;
											} else if (file_exists(JAWS_DATA . 'files'.$medium)) {
												$image_src = $GLOBALS['app']->getDataURL() . 'files'.$medium;
											} else if (file_exists(JAWS_DATA . 'files'.$image_src)) {
												$image_src = $GLOBALS['app']->getDataURL() . 'files'.$image_src;
											}
										}
									}
								}
								$tpl->SetVariable('AD', $adsLayout->Display((int)$posts[$i]['id']));

								if (isset($info['address']) && !empty($info['address'])) {
									$tpl->SetVariable('address', $xss->parse(strip_tags($info['address'])));
								}
								if (isset($info['region']) && !empty($info['region'])) {
									$tpl->SetVariable('region', $xss->parse($info['region']));
								}
								if (isset($info['city']) && !empty($info['city'])) {
									$tpl->SetVariable('city', '<img border="0" src="images/spacer.gif" height="3" width="1"><img border="0" src="images/icon_sm_arrow_gray.gif"><img border="0" src="images/spacer.gif" height="3" width="1"><a href="'.$GLOBALS['app']->getSiteURL().'/index.php?gadget=Ads&action=Category&id=all&keyword='.$xss->parse(strip_tags($info['city'])).'">'.$xss->parse(strip_tags($info['city'])).(isset($info['region']) && !empty($info['region']) ? ', '.$info['region'] : '').'</a>&nbsp;<br />');
								}
								$tpl->SetVariable('OwnerID', (int)$posts[$i]['ownerid']);
								$tpl->SetVariable('Active', $xss->parse($posts[$i]['active']));
								$tpl->SetVariable('Created', $xss->parse($posts[$i]['created']));
								$tpl->SetVariable('Updated', $xss->parse($posts[$i]['updated']));
								
								if ((isset($info['company']) && !empty($info['company'])) || (isset($info['nickname']) && !empty($info['nickname']))) {
									$agent_html = '';
									if (isset($info['company']) && !empty($info['company'])) {
										$agent_html .= '<nobr><b>'.($page['ownerid'] > 0 ? '<a href="index.php?gadget=Ads&action=Category&id=all&owner_id='.$page['ownerid'].'">' : '').$xss->parse(strip_tags($info['company'])).($page['ownerid'] > 0 ? '</a>' : '').'</b></nobr>';
									} else if (isset($info['nickname']) && !empty($info['nickname'])) {
										$agent_html .= '<nobr><b>'.($page['ownerid'] > 0 ? '<a href="index.php?gadget=Ads&action=Category&id=all&owner_id='.$page['ownerid'].'">' : '').$xss->parse(strip_tags($info['nickname'])).($page['ownerid'] > 0 ? '</a>' : '').'</b></nobr>';
									}
									$tpl->SetVariable('agent', $agent_html);
																																				
									$broker_logo_src = '';
									if (!empty($info['logo']) && isset($info['logo'])) {
										$info['logo'] = $xss->parse(strip_tags($info['logo']));
										if (strpos($info['logo'],".swf") !== false) {
											// Flash file not supported
										} else if (substr($info['logo'],0,7) == "GADGET:") {
											$broker_logo_src = $info['logo'];
										} else {
											$broker_logo_src = $info['logo'];
										}
									}
									if (!empty($info['image']) && isset($info['image'])) {
										$info['image'] = $xss->parse(strip_tags($info['image']));
										if (strpos($info['image'],".swf") !== false) {
											// Flash file not supported
										} else if (substr($info['image'],0,7) == "GADGET:") {
											$broker_logo_src = $info['image'];
										} else {
											$broker_logo_src = $info['image'];
										}
									}
									$broker_logo = '';
									if (!empty($broker_logo_src)) {
										if (substr(strtolower($broker_logo_src), 0, 4) == "http") {
											if (substr(strtolower($broker_logo_src), 0, 7) == "http://") {
												$broker_logo = explode('http://', $broker_logo_src);
												foreach ($broker_logo as $img_src) {
													if (!empty($img_src)) {
														$broker_logo = 'http://'.$img_src;
														break;
													}
												}
											} else {
												$broker_logo = explode('https://', $broker_logo_src);
												foreach ($broker_logo as $img_src) {
													if (!empty($img_src)) {
														$broker_logo = 'https://'.$img_src;
														break;
													}
												}
											}
										} else {
											$thumb = Jaws_Image::GetThumbPath($broker_logo_src);
											$medium = Jaws_Image::GetMediumPath($broker_logo_src);
											if (file_exists(JAWS_DATA . 'files'.$thumb)) {
												$broker_logo = $GLOBALS['app']->getDataURL() . 'files'.$thumb;
											} else if (file_exists(JAWS_DATA . 'files'.$medium)) {
												$broker_logo = $GLOBALS['app']->getDataURL() . 'files'.$medium;
											} else if (file_exists(JAWS_DATA . 'files'.$broker_logo_src)) {
												$broker_logo = $GLOBALS['app']->getDataURL() . 'files'.$broker_logo_src;
											}
										}
										if (!empty($broker_logo)) {
											$broker_logo .= ($page['ownerid'] > 0 ? '<a href="index.php?gadget=Ads&action=Category&id=all&owner_id='.$page['ownerid'].'">' : '').'<img style="padding-right: 10px; padding-bottom: 10px; align="left" border="0" src="'.$broker_logo_src.'" width="100" '.(strtolower(substr($broker_logo_src, -3)) == "gif" || strtolower(substr($broker_logo_src, -3)) == "png" || strtolower(substr($broker_logo_src, -3)) == "bmp" ? 'height="100"' : '').' />'.($page['ownerid'] > 0 ? '</a>' : '');				
										}
									}
									$tpl->SetVariable('broker_logo', $broker_logo);
								}
								
								// Property Header
								if ($posts[$i]['sitewide'] == 'Y') {
									$property_headerHTML = '<DIV ID="ad_featured_bkgnd'.$posts[$i]['id'].'" ALIGN="center" CLASS="ad_featured_bkgnd" onmouseover="document.getElementById(\'ad_featured_bkgnd'.$posts[$i]['id'].'\').className = \'ad_featured_bkgnd_over\';" onmouseout="document.getElementById(\'ad_featured_bkgnd'.$posts[$i]['id'].'\').className = \'ad_featured_bkgnd\';"><CENTER><TABLE BORDER="0" CELLPADDING="3" CELLSPACING="0" WIDTH="100%"><TR><TD VALIGN="middle" WIDTH="0%"><img border="0" src="images/propnav_feat_spotlight.gif"></td><TD WIDTH="100%"><DIV ALIGN="center" class="ad_featured_listing_bkgnd">';
								} else {
									$property_headerHTML = '<DIV ID="ad_bkgnd'.$posts[$i]['id'].'" ALIGN="center" CLASS="ad_bkgnd" onmouseover="document.getElementById(\'ad_bkgnd'.$posts[$i]['id'].'\').className = \'ad_bkgnd_over\';" onmouseout="document.getElementById(\'ad_bkgnd'.$posts[$i]['id'].'\').className = \'ad_bkgnd\';"><CENTER><TABLE BORDER="0" CELLPADDING="3" CELLSPACING="0" WIDTH="100%"><TR><TD WIDTH="100%"><DIV ALIGN="center">';
								}
								$tpl->SetVariable('ad_header',  $property_headerHTML);
												
								//$tpl->SetVariable('DPATH',  JAWS_DPATH);
								$tpl->SetVariable('HTTP_REFERER',  $GLOBALS['app']->GetSiteURL());
								$tpl->SetVariable('JAWS_URL',  $GLOBALS['app']->GetJawsURL() . "/");
																								
								// description
								/*
								if (isset($posts[$i]['sm_description']) && !empty($posts[$i]['sm_description'])) {
									$tpl->SetVariable('description', strip_tags($this->ParseText($posts[$i]['sm_description'], 'Properties')));
								} else if (isset($posts[$i]['description']) && !empty($posts[$i]['description'])) {
									$tpl->SetVariable('description', substr(strip_tags($this->ParseText($posts[$i]['description'], 'Properties'), '<p><b><a><img><br>'), 0, 500).'... ');
								}
								*/
								
								$property_image = '';
								if (!empty($image_src)) {
									//$property_image = '<div class="ad_image"><A HREF="'.$property_link.'"><img border="0" src="'.$image_src.'" width="200" '.(strtolower(substr($posts[$i]['image'], -3)) == "gif" || strtolower(substr($posts[$i]['image'], -3)) == "png" || strtolower(substr($posts[$i]['image'], -3)) == "bmp" ? 'height="200"' : '').' /></A></div>';				
									$property_image = '<div class="ad_image"><img border="0" src="'.$image_src.'" width="200" '.(strtolower(substr($posts[$i]['image'], -3)) == "gif" || strtolower(substr($posts[$i]['image'], -3)) == "png" || strtolower(substr($posts[$i]['image'], -3)) == "bmp" ? 'height="200"' : '').' /></div>';				
								} else if (empty($posts[$i]['image']) && strpos(strtolower($posts[$i]['description']), "img") === false) {
									//$property_image = '<div class="ad_no_image" onclick="location.href=\''.$property_link.'\';"><b>No Image</b></div>';
									$par = $model->GetAdParent((int)$posts[$i]['linkid']);
									if (!Jaws_Error::IsError($par)) {	
										$main_image_src = '';
										if (!empty($par['adparentimage']) && isset($par['adparentimage'])) {
											if (strpos($par['adparentimage'],".swf") !== false) {
												// Flash file not supported
											} else if (substr($par['adparentimage'],0,7) == "GADGET:") {
												$main_image_src = $xss->parse(strip_tags($par['adparentimage']));
											} else {
												$main_image_src = $xss->parse(strip_tags($par['adparentimage']));
												if (substr(strtolower($main_image_src), 0, 4) == "http") {
													if (substr(strtolower($main_image_src), 0, 7) == "http://") {
														$main_image_src = explode('http://', $main_image_src);
														foreach ($main_image_src as $img_src) {
															if (!empty($img_src)) {
																$main_image_src = 'http://'.$img_src;
																break;
															}
														}
													} else {
														$main_image_src = explode('https://', $main_image_src);
														foreach ($main_image_src as $img_src) {
															if (!empty($img_src)) {
																$main_image_src = 'https://'.$img_src;
																break;
															}
														}
													}
												} else {
													$thumb = Jaws_Image::GetThumbPath($main_image_src);
													$medium = Jaws_Image::GetMediumPath($main_image_src);
													if (file_exists(JAWS_DATA . 'files'.$thumb)) {
														$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$thumb;
													} else if (file_exists(JAWS_DATA . 'files'.$medium)) {
														$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$medium;
													} else if (file_exists(JAWS_DATA . 'files'.$main_image_src)) {
														$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$main_image_src;
													}
												}
											}
										}
										if (!empty($main_image_src) && empty($par['adparentimage_code']) && strpos($main_image_src, 'GADGET:') === false) {
											$image = '<img style="padding: 5px;" border="0" src="'.$main_image_src.'" width="200" '.(strtolower(substr($main_image_src, -3)) == "gif" || strtolower(substr($main_image_src, -3)) == "png" || strtolower(substr($main_image_src, -3)) == "bmp" ? 'height="200"' : '').' />';				
											$property_image = '<div class="ad_image">'.$image.'</div>';
										} else {
											$property_image = '<div class="ad_no_image"><b>No Image</b></div>';
										}
									} else {
										$property_image = '<div class="ad_no_image"><b>No Image</b></div>';
									}
								}
								$tpl->SetVariable('ad_image', $property_image);
												
								$tpl->ParseBlock('ads/content/ad');
							}
						}
					}
				} else {
					if ($xml === false) {	
						$tpl->SetVariable('PAGE_CYCLE', $page_cycle);
						$tpl->SetVariable('CATEGORY_MAP', '');
						$tpl->SetVariable('NO_ADS', '<div style="padding: 10px;"><i>No ads '.(!empty($searchkeyword) ? 'that match the keyword  <b>"'.str_replace(' - Amenity', '', $searchkeyword).'"</b> ' : '').(!empty($brandname) ? (!empty($searchkeyword) ? ' AND ' : ''). 'that match the brand  <b>"'.$brandname.'"</b> ': '').(!empty($searchlocation) ? (!empty($searchkeyword) || !empty($searchbrand) ? ' AND ' : ''). 'near your location ' : '').'were found.</i></div>');
					}
				}
			} else {
				//$page_content = _t('PROPERTIES_ERROR_ASPPAGE_NOT_RETRIEVED', $posts->GetMessage())."\n";
				return new Jaws_Error(_t('ADS_ERROR_ASPPAGE_NOT_RETRIEVED', $posts->GetMessage()), _t('ADS_NAME'));
			}
						
			if ($xml === false) {
				$tpl->ParseBlock('ads/content');
				if ($embedded == true && !is_null($referer)) {	
					$tpl->SetBlock('ads/embedded');
					$tpl->SetVariable('id', 'all');		        
					if (strtolower($referer) == strtolower($_SERVER['SERVER_NAME'])) {
						$tpl->SetVariable('referer', $GLOBALS['app']->GetJawsURL() . "/libraries/iframes/domain1/iframetest_resize1.html");		        
					} else {	
						$tpl->SetVariable('referer', "http://".$referer."/19/img/agents/19/custom_img/common_files/iframetest_resize1.html");		        
					}
					$tpl->ParseBlock('ads/embedded');
				} else {
					$tpl->SetBlock('ads/not_embedded');
					$tpl->SetVariable('id', 'all');		        
					$tpl->ParseBlock('ads/not_embedded');
				}
				// Statistics Code
				$tpl->SetBlock('ads/stats');
				$GLOBALS['app']->Registry->LoadFile('CustomPage');
				$tpl->SetVariable('stats', html_entity_decode($GLOBALS['app']->Registry->Get('/gadgets/CustomPage/googleanalytics_code')));		        
				$tpl->ParseBlock('ads/stats');
				$tpl->ParseBlock('ads');
				return $tpl->Get();
			} else {
				return $output_xml;
			}
		}
	}
	
    /**
     * Photo details, with description, comments, etc.
     *
     * @param 	int 	$id 	Gallery ID
     * @param 	boolean 	$embedded 	Embedded mode
     * @param 	string 	$referer 	Embedding referer
     * @access 	public
     * @return 	string
     */
    function Gallery($id = null, $embedded = false, $referer = null)
    {
		require_once JAWS_PATH . 'include/Jaws/Image.php';
        
        $GLOBALS['app']->Layout->AddHeadLink('gadgets/Ads/resources/style.css', 'stylesheet', 'text/css');
        $GLOBALS['app']->Layout->AddScriptLink('libraries/js/global2.js');
		$GLOBALS['app']->Layout->AddScriptLink('libraries/carousel/dist/carousel.js');
		$GLOBALS['app']->Layout->AddHeadLink('libraries/carousel/themes/carousel/prototype-ui.css', 'stylesheet', 'text/css');
		$request =& Jaws_Request::getInstance();
        $post = $request->get(array('id', 'linkid'), 'get');

        $GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $post['id'] = $xss->parse($post['id']);

        $model = $GLOBALS['app']->LoadGadget('Ads', 'Model');
		$adsLayout = $GLOBALS['app']->LoadGadget('Ads', 'LayoutHTML');
        if (is_null($id)) {
			$id = $post['id'];
        }
		$page = $model->GetAd($id);

        if (Jaws_Error::IsError($page) || $page['active'] == 'N') {
            require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
            return Jaws_HTTPError::Get(404);
        } else {
			if ($page['ownerid'] > 0) {
				require_once JAWS_PATH . 'include/Jaws/User.php';
				$jUser = new Jaws_User;
				$info = $jUser->GetUserInfoById((int)$page['ownerid'], true, true, true, true);
				if (!Jaws_Error::IsError($info) && file_exists(JAWS_DATA . 'files/css/users/'.$info['id'].'/custom.css')) {
					$GLOBALS['app']->Layout->AddHeadOther('<link rel="stylesheet" media="screen" type="text/css" href="'.$GLOBALS['app']->getDataURL('', true). 'files/css/users/'.$info['id'].'/custom.css" />');
				}
			}
            $tpl = new Jaws_Template('gadgets/Ads/templates/');
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
            
			$tpl->SetBlock('ad_detail');

            if (!isset($page['id']) || $page['active'] == 'N') {
                $this->SetTitle(_t('ADS_TITLE_NOT_FOUND'));
				$tpl->SetBlock('ad_detail/not_found');
                $tpl->SetVariable('content', _t('ADS_CONTENT_NOT_FOUND'));
                $tpl->SetVariable('title', _t('ADS_TITLE_NOT_FOUND'));
                $tpl->ParseBlock('ad_detail/not_found');
            } else {
                $tpl->SetBlock('ad_detail/content');
				// TODO: Implement Preview mode (use cookie to store length of time the preview is available)				
				$hasDetails = false;
				if (!empty($page['title'])) {
					$GLOBALS['app']->Layout->SetTitle($xss->parse(strip_tags($page['title'])));
				}
				$tpl->SetVariable('title', $xss->parse(strip_tags($page['title'])));
				$tpl->SetVariable('id', $page['id']);
				if (isset($info['address']) && !empty($info['address'])) {
					$tpl->SetVariable('address', $xss->parse(strip_tags($info['address'])));
				}
				if (isset($info['city']) && !empty($info['city'])) {
					$tpl->SetVariable('city', '<br /><img border="0" src="images/spacer.gif" height="3" width="1"><img border="0" src="images/icon_sm_arrow_gray.gif"><img border="0" src="images/spacer.gif" height="3" width="1"><a href="'.$GLOBALS['app']->getSiteURL().'/index.php?gadget=Ads&action=Category&id=all&keyword='.$xss->parse(strip_tags($info['city'])).'">'.$xss->parse(strip_tags($info['city'])).(isset($info['region']) ? ', '.$info['region'] : '').'</a>&nbsp;');
				}
				if (isset($info['postal']) && !empty($info['postal'])) {
					$tpl->SetVariable('postal_code', $xss->parse(strip_tags($info['postal'])));
				}
				if (isset($info['country']) && !empty($info['country'])) {
					$tpl->SetVariable('country_id', $xss->parse($info['country']));
				}

				$breadcrumb_start = '<span class="center_nav_font"><a href="'.$GLOBALS['app']->Map->GetURLFor('Ads', 'Category', array('id' => 'all')).'" class="center_nav_link">View All</a>&nbsp;&nbsp;';
				$breadcrumbHTML = '';
								
				$breadcrumbHTML .= '>&nbsp;&nbsp;'.$xss->parse(strip_tags($page['title'])).'&nbsp;&nbsp;';
				$breadcrumbHTML = $breadcrumb_start.$breadcrumbHTML."</span>";
				$tpl->SetVariable('BREADCRUMB', $breadcrumbHTML);
				
				// Property Header
				if ($page['premium'] == 'Y') {
					$property_headerHTML = "<div align=\"center\" class=\"ad_featured_bkgnd\"><table border=\"0\" cellpadding=\"3\" cellspacing=\"0\" width=\"100%\"><tr><td valign=\"top\" width=\"0%\"><img border=\"0\" src=\"images/propnav_feat_spotlight.gif\"></td><td width=\"100%\"><div align=\"center\" class=\"ad_featured_listing_bkgnd\">";
				} else {
					$property_headerHTML = "<div align=\"center\" class=\"ad_bkgnd\"><table border=\"0\" cellpadding=\"3\" cellspacing=\"0\" width=\"100%\"><tr><td width=\"100%\"><div align=\"center\">";
				}
				$tpl->SetVariable('ad_header', $property_headerHTML);
				$emailDisabled = false;
				$tpl->SetVariable('emailDisabled', ($emailDisabled === true ? '_disabled' : ''));
				// TODO: Implement Saved Properties / Users integration
				$saveDisabled = false;
				$tpl->SetVariable('saveDisabled', (($saveDisabled === true) ? '_disabled' : ''));
								
				//$tpl->SetVariable('DPATH',  JAWS_DPATH);
				$tpl->SetVariable('HTTP_REFERER',  $GLOBALS['app']->GetSiteURL());
				
				$adsLayout = $GLOBALS['app']->LoadGadget('Ads', 'LayoutHTML');
				
				// Map
				if ($GLOBALS['app']->Registry->Get('/gadgets/Ads/showmap') == 'Y') {
					$tpl->SetVariable('AD_MAP',  $adsLayout->CategoryMap($page['id']));
					$tpl->SetVariable('AD_MAP_STYLE',  '');
				} else {
					$tpl->SetVariable('AD_MAP',  '');
					$tpl->SetVariable('AD_MAP_STYLE',  '#adnav_map {display: none;}'."\n");
				}
												
				// Property E-mail Form
				$formsLayout = $GLOBALS['app']->LoadGadget('Forms', 'LayoutHTML');
				$now = $GLOBALS['db']->Date();
				if (strrpos($GLOBALS['app']->GetSiteURL(), "/") > 8) {
					$site_url = substr($GLOBALS['app']->GetSiteURL(), 0, strrpos($GLOBALS['app']->GetSiteURL(), "/"));
				} else {
					$site_url = $GLOBALS['app']->GetSiteURL();		
				}
				$redirect = $GLOBALS['app']->GetSiteURL().'/'.$GLOBALS['app']->Map->GetURLFor('Ads', 'Ad', array('id' => $page['id']));
				/*
				Custom Form implementation
				- Add "__REQUIRED__" to any question title to make the field required
				- Add "__EXTRA_RECIPIENT__" to add the field as a recipient
				- Add "__REDIRECT__" to specify where we are coming from/return URL after form submission
				- Add "__MESSAGE__" to show as a message in the resultant e-mail
				*/	
				$property_email_form = $formsLayout->Display(null, true, array('id' => 'custom', 'sort_order' => 0, 'title' => 'E-mail To A Friend', 
					'sm_description' => '', 'description' => "E-mail this page to up to 5 of your friends.", 'clause' => '', 
					'image' => '', 'recipient' => '', 'parent' => 0, 'custom_action' => '', 'fast_url' => '', 'active' => 'Y', 
					'ownerid' => 0, 'created' => $now, 'updated' => $now, 
					'submit_content' => "<div style='margin-bottom: 10px;'>Thank you for taking the time to forward this to your friends!</div><div><a href='".$redirect."'>Click here to return to the details page</a>.</div>"),
					array(array('id' => 9, 'sort_order' => 0, 'formid' => 'custom', 
					'title' => "__MESSAGE__", 'itype' => 'HiddenField', 'required' => 'N', 
					'ownerid' => 0, 'created' => $now, 'updated' => $now),
					array('id' => 2, 'sort_order' => 1, 'formid' => 'custom', 
					'title' => '__FROM_EMAIL____REQUIRED__', 'itype' => 'TextBox', 'required' => 'N', 
					'ownerid' => 0, 'created' => $now, 'updated' => $now),
					array('id' => 1, 'sort_order' => 2, 'formid' => 'custom', 
					'title' => '__FROM_NAME__', 'itype' => 'TextBox', 'required' => 'N', 
					'ownerid' => 0, 'created' => $now, 'updated' => $now), 
					array('id' => 3, 'sort_order' => 3, 'formid' => 'custom', 
					'title' => "Friend's Email Address 1__EXTRA_RECIPIENT____REQUIRED__", 'itype' => 'TextBox', 'required' => 'N', 
					'ownerid' => 0, 'created' => $now, 'updated' => $now),
					array('id' => 4, 'sort_order' => 4, 'formid' => 'custom', 
					'title' => "Friend's Email Address 2__EXTRA_RECIPIENT__", 'itype' => 'TextBox', 'required' => 'N', 
					'ownerid' => 0, 'created' => $now, 'updated' => $now),
					array('id' => 5, 'sort_order' => 5, 'formid' => 'custom', 
					'title' => "Friend's Email Address 3__EXTRA_RECIPIENT__", 'itype' => 'TextBox', 'required' => 'N', 
					'ownerid' => 0, 'created' => $now, 'updated' => $now),
					array('id' => 6, 'sort_order' => 6, 'formid' => 'custom', 
					'title' => "Friend's Email Address 4__EXTRA_RECIPIENT__", 'itype' => 'TextBox', 'required' => 'N', 
					'ownerid' => 0, 'created' => $now, 'updated' => $now),
					array('id' => 7, 'sort_order' => 7, 'formid' => 'custom', 
					'title' => "Friend's Email Address 5__EXTRA_RECIPIENT__", 'itype' => 'TextBox', 'required' => 'N', 
					'ownerid' => 0, 'created' => $now, 'updated' => $now),
					array('id' => 8, 'sort_order' => 8, 'formid' => 'custom', 
					'title' => "__REDIRECT__", 'itype' => 'HiddenField', 'required' => 'N', 
					'ownerid' => 0, 'created' => $now, 'updated' => $now)
					), 
					array(array('id' => 1, 'sort_order' => 0, 'linkid' => 8, 
					'formid' => 'custom', 'title' => "<a href='".$redirect."'>".$redirect."</a>",
					'ownerid' => 0, 'created' => $now, 'updated' => $now),
					array('id' => 2, 'sort_order' => 1, 'linkid' => 9, 
					'formid' => 'custom', 'title' => "One of your friends thought you might be interested in something featured on ".$site_url,
					'ownerid' => 0, 'created' => $now, 'updated' => $now)
					)
				);
				// Property Inquiry Form
				/*
				Custom Form implementation
				- Add "__REQUIRED__" to any question title to make the field required
				- Add "__EXTRA_RECIPIENT__" to add the field as a recipient
				- Add "__REDIRECT__" to specify where we are coming from/return URL after form submission
				- Add "__MESSAGE__" to show as a message in the resultant e-mail
				*/	
				if (isset($info['email']) && !empty($info['email'])) {
					$recipient = $info['email'];
				} else {
					$recipient = '';
				}
				$property_inquiry_form = $formsLayout->Display(null, true, array('id' => 'custom', 'sort_order' => 0, 'title' => 'Ad Inquiry', 
					'sm_description' => '', 'description' => "Send us your questions/comments about this.", 'clause' => '', 
					'image' => '', 'recipient' => $recipient, 'parent' => 0, 'custom_action' => '', 'fast_url' => '', 'active' => 'Y', 
					'ownerid' => 0, 'created' => $now, 'updated' => $now, 
					'submit_content' => "<div style='margin-bottom: 10px;'>Thank you for taking the time to ask us about this! We'll review your inquiry and get back to you when necessary.</div><div><a href='".$redirect."'>Click here to return to the details page</a>.</div>"),
					array(array('id' => 9, 'sort_order' => 0, 'formid' => 'custom', 
					'title' => "__MESSAGE__", 'itype' => 'HiddenField', 'required' => 'N', 
					'ownerid' => 0, 'created' => $now, 'updated' => $now),
					array('id' => 2, 'sort_order' => 1, 'formid' => 'custom', 
					'title' => '__FROM_EMAIL____REQUIRED__', 'itype' => 'TextBox', 'required' => 'N', 
					'ownerid' => 0, 'created' => $now, 'updated' => $now),
					array('id' => 1, 'sort_order' => 2, 'formid' => 'custom', 
					'title' => '__FROM_NAME__', 'itype' => 'TextBox', 'required' => 'N', 
					'ownerid' => 0, 'created' => $now, 'updated' => $now), 
					array('id' => 3, 'sort_order' => 3, 'formid' => 'custom', 
					'title' => "Phone", 'itype' => 'TextBox', 'required' => 'N', 
					'ownerid' => 0, 'created' => $now, 'updated' => $now),
					array('id' => 4, 'sort_order' => 4, 'formid' => 'custom', 
					'title' => "Address", 'itype' => 'TextBox', 'required' => 'N', 
					'ownerid' => 0, 'created' => $now, 'updated' => $now),
					array('id' => 5, 'sort_order' => 5, 'formid' => 'custom', 
					'title' => "City", 'itype' => 'TextBox', 'required' => 'N', 
					'ownerid' => 0, 'created' => $now, 'updated' => $now),
					array('id' => 6, 'sort_order' => 6, 'formid' => 'custom', 
					'title' => "State or Province", 'itype' => 'TextBox', 'required' => 'N', 
					'ownerid' => 0, 'created' => $now, 'updated' => $now),
					array('id' => 7, 'sort_order' => 7, 'formid' => 'custom', 
					'title' => "Zip", 'itype' => 'TextBox', 'required' => 'N', 
					'ownerid' => 0, 'created' => $now, 'updated' => $now),
					array('id' => 8, 'sort_order' => 8, 'formid' => 'custom', 
					'title' => "__REDIRECT__", 'itype' => 'HiddenField', 'required' => 'N', 
					'ownerid' => 0, 'created' => $now, 'updated' => $now),
					array('id' => 10, 'sort_order' => 9, 'formid' => 'custom', 
					'title' => "Best Time To Reach", 'itype' => 'RadioBtn', 'required' => 'N', 
					'ownerid' => 0, 'created' => $now, 'updated' => $now),
					array('id' => 11, 'sort_order' => 10, 'formid' => 'custom', 
					'title' => "Message", 'itype' => 'TextArea', 'required' => 'N', 
					'ownerid' => 0, 'created' => $now, 'updated' => $now)
					), 
					array(array('id' => 1, 'sort_order' => 0, 'linkid' => 8, 
					'formid' => 'custom', 'title' => "<a href='".$redirect."'>".$redirect."</a>",
					'ownerid' => 0, 'created' => $now, 'updated' => $now),
					array('id' => 2, 'sort_order' => 0, 'linkid' => 9, 
					'formid' => 'custom', 'title' => "A message has been received for the following ad: ".$page['title'],
					'ownerid' => 0, 'created' => $now, 'updated' => $now),
					array('id' => 3, 'sort_order' => 0, 'linkid' => 10, 
					'formid' => 'custom', 'title' => "Any",
					'ownerid' => 0, 'created' => $now, 'updated' => $now),
					array('id' => 4, 'sort_order' => 1, 'linkid' => 10, 
					'formid' => 'custom', 'title' => "Morning",
					'ownerid' => 0, 'created' => $now, 'updated' => $now),
					array('id' => 5, 'sort_order' => 2, 'linkid' => 10, 
					'formid' => 'custom', 'title' => "Afternoon",
					'ownerid' => 0, 'created' => $now, 'updated' => $now),
					array('id' => 6, 'sort_order' => 3, 'linkid' => 10, 
					'formid' => 'custom', 'title' => "Evening",
					'ownerid' => 0, 'created' => $now, 'updated' => $now)
					)
				);
				
				$tpl->SetVariable('AD_EMAIL_FORM',  $property_email_form);
				$tpl->SetVariable('AD_INQUIRY_FORM',  $property_inquiry_form);
				
				/*
				// amenity
				$amenity = '';
				if (isset($page['amenity']) && !empty($page['amenity'])) {
					$propAmenities = explode(',', $page['amenity']);
					foreach($propAmenities as $propAmenity) {		            
						$amenityParent = $model->GetAmenity((int)$propAmenity);
						if (!Jaws_Error::IsError($amenityParent)) {
							if ($amenity != '') {
								$amenity .= ' ';
							}
							$amenity .= ' <nobr><img border="0" style="padding-left: 10px;" src="images/ICON_chkbox.gif">&nbsp;<a href="'.$GLOBALS['app']->getSiteURL().'/index.php?gadget=Properties&action=Category&id=all&amenities='.$GLOBALS['app']->UTF8->str_replace('"', '%22', $xss->parse(strip_tags($amenityParent['feature']))).'">'.$amenityParent['feature'].'</a></nobr>';;
						}
					}
					$tpl->SetBlock('ad_detail/content/amenity');
					$tpl->SetVariable('amenity', $xss->parse($amenity));
					$tpl->ParseBlock('ad_detail/content/amenity');
				}
				*/
				
				// description
				if ((isset($page['description']) && !empty($page['description'])) || (isset($page['barcode']) && !empty($page['barcode']))) {
					$tpl->SetBlock('ad_detail/content/description');
					$barcode_src = '';
					if (isset($page['barcode_type']) && !empty($page['barcode_type']) && isset($page['barcode_data']) && !empty($page['barcode_data'])) {
						$barcode_type = strtoupper($xss->parse($page['barcode_type']));
						$barcode_data = urlencode($xss->parse($page['barcode_data']));
						$barcode_src = $GLOBALS['app']->GetJawsURL() . '/libraries/barcode/barcode.php?encode='.$barcode_type.'&bdata='.$barcode_data.'&height=100&scale=3&bgcolor=%23FFFFFF&color=%23444444&file=&type=png';
					}
					$tpl->SetVariable('barcode', $barcode_src);
					$tpl->SetVariable('description', strip_tags($this->ParseText($page['description'], 'Ads'), '<p><a><b><em><i><strong><div><table><tbody><thead><tr><td><span><font><hr><br><ul><li>'));
					$tpl->ParseBlock('ad_detail/content/description');
				}
								
				// contact information
				if ((isset($info['company']) && !empty($info['company'])) || (isset($info['nickname']) && !empty($info['nickname']))) {
					$tpl->SetBlock('ad_detail/content/contact');
					
					$agent_html = '';
					if (isset($info['company']) && !empty($info['company'])) {
						$agent_html .= '<nobr><b>'.($page['ownerid'] > 0 ? '<a href="index.php?gadget=Ads&action=Category&id=all&owner_id='.$page['ownerid'].'">' : '').$xss->parse(strip_tags($info['company'])).($page['ownerid'] > 0 ? '</a>' : '').'</b></nobr>';
					} else if (isset($info['nickname']) && !empty($info['nickname'])) {
						$agent_html .= '<nobr><b>'.($page['ownerid'] > 0 ? '<a href="index.php?gadget=Ads&action=Category&id=all&owner_id='.$page['ownerid'].'">' : '').$xss->parse(strip_tags($info['nickname'])).($page['ownerid'] > 0 ? '</a>' : '').'</b></nobr>';
					}
					$tpl->SetVariable('agent', $agent_html);
					
					$agent_website = '';
					$agent_website_html = '';
					if (isset($info['url']) && !empty($info['url'])) {
						$agent_website = $GLOBALS['app']->UTF8->str_replace('"', '%22', $xss->parse(strip_tags($info['url'])));
						$agent_website_html .= '<br /><nobr>Website: <a href="'.$agent_website.'" target="_blank">'.$agent_website.'</a></nobr>';
					}
					$tpl->SetVariable('agent_website', $agent_website_html);
					
					$broker_html = '';
					$tpl->SetVariable('broker', $broker_html);
					
					$agent_phone_html = '';
					if (isset($info['phone']) && !empty($info['phone'])) {
						$agent_phone_html .= '<br /><nobr>Phone: '.$xss->parse(strip_tags($info['phone'])).'</nobr>';
					} else if (isset($info['office']) && !empty($info['office'])) {
						$agent_phone_html .= '<br /><nobr>Phone: '.$xss->parse(strip_tags($info['office'])).'</nobr>';
					}
					$tpl->SetVariable('agent_phone', $agent_phone_html);
					
					$agent_email_html = '';
					if (isset($info['email']) && !empty($info['email'])) {
						$agent_email_html .= '<br /><nobr>E-mail: '.$xss->parse(strip_tags($info['email'])).'</nobr>';
					}
					$tpl->SetVariable('agent_email', $agent_email_html);
					
					$broker_logo_src = '';
					if (!empty($info['logo']) && isset($info['logo'])) {
						$info['logo'] = $xss->parse(strip_tags($info['logo']));
						if (strpos($info['logo'],".swf") !== false) {
							// Flash file not supported
						} else if (substr($info['logo'],0,7) == "GADGET:") {
							$broker_logo_src = $info['logo'];
						} else {
							$broker_logo_src = $info['logo'];
						}
					}
					if (!empty($info['image']) && isset($info['image'])) {
						$info['image'] = $xss->parse(strip_tags($info['image']));
						if (strpos($info['image'],".swf") !== false) {
							// Flash file not supported
						} else if (substr($info['image'],0,7) == "GADGET:") {
							$broker_logo_src = $info['image'];
						} else {
							$broker_logo_src = $info['image'];
						}
					}
					$broker_logo = '';
					if (!empty($broker_logo_src)) {
						if (substr(strtolower($broker_logo_src), 0, 4) == "http") {
							if (substr(strtolower($broker_logo_src), 0, 7) == "http://") {
								$broker_logo = explode('http://', $broker_logo_src);
								foreach ($broker_logo as $img_src) {
									if (!empty($img_src)) {
										$broker_logo = 'http://'.$img_src;
										break;
									}
								}
							} else {
								$broker_logo = explode('https://', $broker_logo_src);
								foreach ($broker_logo as $img_src) {
									if (!empty($img_src)) {
										$broker_logo = 'https://'.$img_src;
										break;
									}
								}
							}
						} else {
							$thumb = Jaws_Image::GetThumbPath($broker_logo_src);
							$medium = Jaws_Image::GetMediumPath($broker_logo_src);
							if (file_exists(JAWS_DATA . 'files'.$thumb)) {
								$broker_logo = $GLOBALS['app']->getDataURL() . 'files'.$thumb;
							} else if (file_exists(JAWS_DATA . 'files'.$medium)) {
								$broker_logo = $GLOBALS['app']->getDataURL() . 'files'.$medium;
							} else if (file_exists(JAWS_DATA . 'files'.$broker_logo_src)) {
								$broker_logo = $GLOBALS['app']->getDataURL() . 'files'.$broker_logo_src;
							}
						}
						if (!empty($broker_logo)) {
							$broker_logo .= ($page['ownerid'] > 0 ? '<a href="index.php?gadget=Ads&action=Category&id=all&owner_id='.$page['ownerid'].'">' : '').'<img style="padding-right: 10px; padding-bottom: 10px; align="left" border="0" src="'.$broker_logo_src.'" width="100" '.(strtolower(substr($broker_logo_src, -3)) == "gif" || strtolower(substr($broker_logo_src, -3)) == "png" || strtolower(substr($broker_logo_src, -3)) == "bmp" ? 'height="100"' : '').' />'.($page['ownerid'] > 0 ? '</a>' : '');				
						}
					}
					$tpl->SetVariable('broker_logo', $broker_logo);
					
					$tpl->ParseBlock('ad_detail/content/contact');
				}

				if (!empty($page['image'])) {
					$tpl->SetBlock('ad_detail/content/image');
					$tpl->SetVariable('AD', $adsLayout->Display($page['id'], false, null, true));
					$tpl->ParseBlock('ad_detail/content/image');
				} else {
					if (empty($page['image']) && strpos(strtolower($page['description']), "img") === false) {
						$tpl->SetBlock('ad_detail/content/no_image');
						$tpl->ParseBlock('ad_detail/content/no_image');
					}
				}
					
				// keywords
				$keyword_html = '';
				if (isset($page['keyword']) && !empty($page['keyword'])) {
					$keywords = explode(' ', $page['keyword']);
					foreach($keywords as $keyword) {		            
						if ($keyword_html != '') {
							$keyword_html .= ' ';
						}
						$keyword_html .= ' <nobr><img border="0" style="padding-left: 10px;" src="images/ICON_chkbox.gif">&nbsp;<a href="'.$GLOBALS['app']->getSiteURL().'/index.php?gadget=Ads&action=Category&id=all&keyword='.$GLOBALS['app']->UTF8->str_replace('"', '%22', $xss->parse(strip_tags($keyword))).'">'.$keyword.'</a></nobr>';;
					}
					$tpl->SetVariable('keyword', $xss->parse($keyword_html));
				}
				
				$tpl->SetVariable('pagetype', 'ad');
				$tpl->ParseBlock('ad_detail/content');
				
				if ($embedded == true && !is_null($referer) && isset($page['id'])) {	
					$tpl->SetBlock('ad_detail/embedded');
					$tpl->SetVariable('id', $page['id']);		        
					if (strtolower($referer) == strtolower($_SERVER['SERVER_NAME'])) {
						$tpl->SetVariable('referer', $GLOBALS['app']->GetJawsURL() . "/libraries/iframes/domain1/iframetest_resize1.html");		        
					} else {	
						$tpl->SetVariable('referer', "http://".$referer."/19/img/agents/19/custom_img/common_files/iframetest_resize1.html");		        
					}
					$tpl->ParseBlock('ad_detail/embedded');
				} else {
					$tpl->SetBlock('ad_detail/not_embedded');
					$tpl->SetVariable('id', $page['id']);		        
					$tpl->ParseBlock('ad_detail/not_embedded');
				}
			}
		}
		// Statistics Code
		$tpl->SetBlock('ad_detail/stats');
		$GLOBALS['app']->Registry->LoadFile('CustomPage');
		$tpl->SetVariable('stats', html_entity_decode($GLOBALS['app']->Registry->Get('/gadgets/CustomPage/googleanalytics_code')));		        
		$tpl->ParseBlock('ad_detail/stats');

        $tpl->ParseBlock('ad_detail');

        return $tpl->Get();
	}
	
    /**
     * Displays an XML file with the requested gallery's images
     *
     * @access public
     * @return string
     */
    function GalleryXML()
    {
		require_once JAWS_PATH . 'include/Jaws/Image.php';
        
		header("Content-type: text/xml");
		$output_xml = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>\n"; 
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');

		//setup variables
		$good_ext = array('.jpg', '.jpeg', '.swf');

		$request =& Jaws_Request::getInstance();
        $get = $request->get(array('id', 'showcase_id'), 'get');

        //$post['showcase_id'] = $xss->defilter($post['showcase_id']);

		//if(!empty($post['showcase_id'])) {
		//	$agentID = $post['showcase_id'];
		//}
		  
		if(!empty($get['id'])) {
			$gid = (int)$get['id'];

	        $model = $GLOBALS['app']->LoadGadget('FlashGallery', 'Model');
			$galleryParent = $model->GetFlashGallery($gid);
			$galleryPosts = $model->GetPostsOfFlashGallery($gid);
			
			if (!$galleryPosts || !$galleryParent) {
				if (isset($GLOBALS['log'])) {
					$GLOBALS['log']->Log(JAWS_LOG_ERR, "No images were found in flash gallery ID: $gid");
				}
				$output_xml .= "<gallery>\n";
				$output_xml .=  "	<image targeturl=\"\" target=\"\" path=\"images/gallery_no_images.jpg\" textLabel=\"No images have been added or activated for this gallery.\">No images have been added or activated for this gallery.</image>\n";
				$output_xml .= "</gallery>\n";
			} else {
				$image_found = false;
				$output_xml .= "<gallery>\n";
				foreach($galleryPosts as $parents) {		            
					if (isset($parents['image']) && !empty($parents['image']) && $parents['active'] == "Y") {
						$image_found = true;
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
						if (!empty($main_image_src)) {
							$ext = strrchr($main_image_src,".");  
							if(in_array(strtolower($ext),$good_ext)) { 
								if ($parents['url'] == 'javascript:void(0);') {
									$url = $main_image_src;
									$url_target = '_blank';
								} else {
									$url = $xss->filter($parents['url']);
									$url_target = $parents['url_target'];
								}
								if ($galleryParent['type'] == 'slideshow') {
									$label = $parents['description'];
									// here we need to format our text to Flash's basic HTML
									$label = str_replace("style=\"text-align: center;\"", "align=\"center\"", $label);
									$label = str_replace("<span style=\"font-family: ", "<font family=\"", $label);
									$label = str_replace("<span style=\"font-size: ", "<font size=\"", $label);
									$label = str_replace("<span style=\"color: ", "<font color=\"", $label);
									$label = str_replace("</span>", "</font>", $label);
									$label = str_replace(";\">", "\">", $label);
									$label = str_replace("<strong>", "<b>", $label);
									$label = str_replace("</strong>", "</b>", $label);
									$label = str_replace("<em>", "<i>", $label);
									$label = str_replace("</em>", "</i>", $label);
									$output_xml .=  "	<image targeturl=\"".$url."\" target=\"".$url_target."\" path=\"".$GLOBALS['app']->getSiteURL().'/'.$main_image_src."\"><![CDATA[ ".$label." ]]></image>\n"; 
								} else {
									$label = $parents['title'];
									$output_xml .=  "	<image targeturl=\"".$url."\" target=\"".$url_target."\" path=\"".$GLOBALS['app']->getSiteURL().'/'.$main_image_src."\" textLabel=\"".$label."\" />\n"; 
								}
							}
						}
					}
				}
				if ($image_found === false) {
					$output_xml .=  "	<image targeturl=\"\" target=\"\" path=\"images/gallery_no_images.jpg\" textLabel=\"No images have been added or activated for this gallery.\">No images have been added or activated for this gallery.</image>\n";
				}
			$output_xml .= "</gallery>\n";
			} 
			
		} else {
			if (isset($GLOBALS['log'])) {
				$GLOBALS['log']->Log(JAWS_LOG_ERR, "No images were found in gallery ID: $gid");
			}
			$output_xml .= "<gallery>\n";
			$output_xml .=  "	<image targeturl=\"\" target=\"\" path=\"images/gallery_error.jpg\">Error</image>\n";
			$output_xml .= "</gallery>\n";			
		}
		return $output_xml;
	}

    /**
     * Embed galleries into external sites.
     *
     * @category 	feature
     * @access 	public
     * @return 	string
     */
    function EmbedFlashGallery()
    {
		$request =& Jaws_Request::getInstance();
		$get = $request->get(array('id', 'mode', 'uid', 'referer', 'css'), 'get');
		$model = $GLOBALS['app']->LoadGadget('FlashGallery', 'Model');
		$gallery = $model->GetFlashGallery((int)$get['id']);
		$output_html = "";
		if (!Jaws_Error::IsError($gallery)) {
			//$output_html .= "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
			$output_html .= "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" style=\"background: url();\">\n";
			$output_html .= " <head>\n";
			$output_html .= "  <title>FlashGallery</title>\n";
			$output_html .= "  <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n";
			$theme = $GLOBALS['app']->Registry->Get('/config/theme');
			$themeHREF = (strpos($theme, 'http://') !== false ? $theme : $GLOBALS['app']->getDataURL('', true) . "themes/" . $theme);
			$output_html .= "	<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" href=\"". $themeHREF . "/style.css\" />\n";
			if ($gallery['type'] == 'gallery') {
				$output_html .= "	<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" href=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/carousel/themes/carousel/prototype-ui.css\" />\n";
			}
			$output_html .= "	<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" href=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/".$this->_Name."/resources/style.css\" />\n";
			$output_html .= "	<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" href=\"". $GLOBALS['app']->getDataURL('', true) . "files/css/custom.css\" />\n";
			if (isset($get['css'])) {
				$output_html .= "	<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" href=\"".$get['css']."\" />\n";
			}
			//$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/prototype/prototype.js\"></script>\n";
			//$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/prototype/scriptaculous.js?load=effects,controls\"></script>\n";
			$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/prototype/prototype.js\"></script>\n";
			$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/prototype/scriptaculous.js\"></script>\n";
			$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/prototype/effects.js\"></script>\n";
			$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/prototype/controls.js\"></script>\n";
			//$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/include/Jaws/Ajax/Response.js\"></script>\n";
			//$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetSiteURL() . "/index.php?gadget=".$this->_Name."&amp;action=Ajax&amp;client\"></script>\n";
			//$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetSiteURL() . "/index.php?gadget=".$this->_Name."&amp;action=AjaxCommonFiles\"></script>\n";
			//$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/".$this->_Name."/resources/client_script.js\"></script>\n";
			if ($gallery['type'] == 'gallery') {
				//$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/js/swfobject.js\"></script>\n";
				$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/carousel/dist/carousel.js\"></script>\n";
			} else {
				$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/slideshow/slideshow-min.js\"></script>\n";
			}
			$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/js/admin.js\"></script>\n";
			$output_html .= "	<script type=\"text/javascript\" src=\"http://yui.yahooapis.com/2.4.0/build/yahoo-dom-event/yahoo-dom-event.js\"></script>\n";
			//$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "//libraries/crossframe/cross-frame.js\"></script>\n";
			$output_html .= " </head>\n";
			$display_id = md5($this->_Name.$get['id']);
			$output_html .= " <body style=\"background: transparent url();\" onLoad=\"sizeFrame".$display_id."(); document.getElementById('".$this->_Name."-editDivStretch-".$display_id."').style.width = (parseInt(document.getElementById('".$this->_Name."-editDiv-".$display_id."').offsetWidth)-20)+'px';\">\n";
			if (isset($get['id']) && (isset($get['referer']) || $GLOBALS['app']->Session->GetAttribute('gadget_referer'))) {
				$output_html .= " <style>\n";
				$output_html .= "   #".$this->_Name."-editDiv-".$display_id." { width: 100%; text-align: right; }\n";
				$output_html .= "   #".$this->_Name."-edit-".$display_id." { display: block; width:20px; height:20px; overflow:hidden; }\n";
				$output_html .= "   #".$this->_Name."-edit-".$display_id.":hover { width: 118px; }\n";
				$output_html .= " </style>\n";
				$output_html .= " <div id=\"".$this->_Name."-editDiv-".$display_id."\"><div id=\"".$this->_Name."-editDivStretch-".$display_id."\" style=\"float: left;\">&nbsp;</div><div style=\"float: left;\"><a onMouseOver=\"document.getElementById('".$this->_Name."-editDivStretch-".$display_id."').style.width = (parseInt(document.getElementById('".$this->_Name."-editDiv-".$display_id."').offsetWidth)-118)+'px';\" onMouseOut=\"document.getElementById('".$this->_Name."-editDivStretch-".$display_id."').style.width = (parseInt(document.getElementById('".$this->_Name."-editDiv-".$display_id."').offsetWidth)-20)+'px';\" id=\"".$this->_Name."-edit-".$display_id."\" target=\"_blank\" href=\"".$GLOBALS['app']->GetSiteURL()."/index.php?gadget=FlashGallery&action=account_view&id=".$get['id']."\" title=\"Edit This Gadget\"><img border=\"0\" src=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/Users/images/edit_gadget.png\" /></a></div><div style=\"font-size: 0.1em; clear: both;\">&nbsp;</div></div>";
				$referer = (isset($get['referer']) ? $get['referer'] : $GLOBALS['app']->Session->GetAttribute('gadget_referer'));
				$layoutGadget = $GLOBALS['app']->LoadGadget('FlashGallery', 'LayoutHTML');
				if ($get['mode'] == 'single') {
					$output_html .= $layoutGadget->ShowOne($gallery['id'], true, $referer);
				} else {
					if ($gallery['type'] == 'gallery') {
						$output_html .= $layoutGadget->Gallery($gallery['id'], true, $referer);
					} else if ($gallery['type'] == 'slideshow') {
						$output_html .= $layoutGadget->Slideshow($gallery['id'], true, $referer);
					}
				}
			}
			$output_html .= " </body>\n";
			$output_html .= "</html>\n";
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
					'params' => array()
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
					'name' => 'Photos',
					'id' => 'Photos',
					'method' => 'User'.ucfirst(str_replace('_','',str_replace(array('_owners','_users'),'',$group['group_name']))),
					'icon' => $GLOBALS['app']->GetJawsURL() . '/gadgets/'.$this->_Name.'/images/logo.png'
				);
			}
		}
		return $panes;
	}

    /**
     * Allow users (members) to create and subscribe to Galleries.
     *
     * @category 	feature
     * @param 	int 	$user 	User ID
     * @access 	public
     * @return 	string
     */
    function UserFlashGallery($user)
    {			
		require_once JAWS_PATH . 'include/Jaws/User.php';
        $jUser = new Jaws_User;
		
		require_once JAWS_PATH . 'include/Jaws/Template.php';
        $tpl = new Jaws_Template('gadgets/FlashGallery/templates/');
        $tpl->Load('users.html');
		$tpl->SetBlock('pane');
		$tpl->SetVariable('title', $this->_Name);
		$tpl->SetVariable('pane_id', str_replace(" ",'',$this->_Name));
		$tpl->SetBlock('pane/pane_item');
		$tpl->SetVariable('pane_id', str_replace(" ",'',$this->_Name));
		$tpl->SetVariable('pane', 'UserFlashGallery');
		$tpl->SetVariable('icon', $GLOBALS['app']->GetJawsURL() . '/gadgets/'.$this->_Name.'/images/logo.png');
        
		$stpl = new Jaws_Template('gadgets/FlashGallery/templates/');
        $stpl->Load('users.html');
        $stpl->SetBlock('UserFlashGallerySubscriptions');
		$status = $jUser->GetStatusOfUserInGroup($GLOBALS['app']->Session->GetAttribute('user_id'), 'flashgallery_owners');
		$usersHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$page = $usersHTML->ShowComments('FlashGallery', false, null, 'FlashGallery', (in_array($status, array('active','admin','founder')) ? true : false));
		if (!Jaws_Error::IsError($page)) {
			$stpl->SetVariable('element', $page);
		} else {
			$stpl->SetVariable('element', _t('GLOBAL_ERROR_GET_ACCOUNT_PANE'));
		}
		$stpl->ParseBlock('UserFlashGallerySubscriptions');

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
		$gadget_admin = $GLOBALS['app']->LoadGadget('FlashGallery', 'AdminHTML');
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
		$gadget_admin = $GLOBALS['app']->LoadGadget('FlashGallery', 'AdminHTML');
		$page = $gadget_admin->form(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$html_output = $users_html->GetAccountHTML('FlashGallery');
		$html_output = str_replace("</head>", "<style type=\"text/css\">body { background: transparent none; }</style>\n</head>", $html_output);
		$output_html = str_replace("__JAWS_GADGET__", $page, $html_output);
		return $output_html;
    }

    /**
     * Account form_post
     *
     * @access public
     * @return string
     */
    function account_form_post()
    {
		$gadget_admin = $GLOBALS['app']->LoadGadget('FlashGallery', 'AdminHTML');
		$page = $gadget_admin->form_post(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$output_html = str_replace("__JAWS_GADGET__", $page, $users_html->GetAccountHTML('FlashGallery'));
		return $output_html;
    }

    /**
     * Account A
     *
     * @access public
     * @return string
     */
    function account_view()
    {
		$gadget_admin = $GLOBALS['app']->LoadGadget('FlashGallery', 'AdminHTML');
		$page = $gadget_admin->view(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$html_output = $users_html->GetAccountHTML('FlashGallery');
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
		$gadget_admin = $GLOBALS['app']->LoadGadget('FlashGallery', 'AdminHTML');
		$page = $gadget_admin->A_form(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$html_output = $users_html->GetAccountHTML('FlashGallery');
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
		$gadget_admin = $GLOBALS['app']->LoadGadget('FlashGallery', 'AdminHTML');
		$page = $gadget_admin->A_form_post(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$output_html = str_replace("__JAWS_GADGET__", $page, $users_html->GetAccountHTML('FlashGallery'));
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
		$gadget_admin = $GLOBALS['app']->LoadGadget('FlashGallery', 'AdminHTML');
		$page = $gadget_admin->GetQuickAddForm(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$output_html = str_replace("__JAWS_GADGET__", $page, $users_html->GetAccountHTML('FlashGallery'));
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
		return $user_admin->ShowEmbedWindow('FlashGallery', 'OwnFlashGallery', true);
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
		$layoutGadget = $GLOBALS['app']->LoadGadget('FlashGallery', 'LayoutHTML');
		$model = $GLOBALS['app']->LoadGadget('FlashGallery', 'Model');
		$output_html = '';
		if($uid > 0) {
			$galleries = $model->GetFlashGalleryOfUserID($uid);
			if (!Jaws_Error::IsError($galleries)) {
				foreach ($galleries as $gallery) {
					if ($gallery['type'] == 'gallery') {
						$output_html .= $layoutGadget->Gallery($gallery['id']);
					} else {
						$output_html .= $layoutGadget->Slideshow($gallery['id']);
					}
				}
			} else {
				$output_html .= '<div class="simple-response-msg">'.$galleries->GetMessage().'</div>';
			}
		} else {
            require_once JAWS_PATH . 'include/Jaws/Header.php';
            Jaws_Header::Location($GLOBALS['app']->GetSiteURL().'/');
		}
		
		return $output_html;
    }
}
