<?php
/**
 * Websites Gadget
 *
 * @category   Gadget
 * @package    Websites
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class WebsitesHTML extends Jaws_GadgetHTML
{
    var $_Name = 'Websites';
    /**
     * Constructor
     *
     * @access public
     */
    function WebsitesHTML()
    {
        $this->Init('Websites');
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
        //return $this->GalleryXML();
	}

    /**
     * Displays an individual category.
     *
     * @var	int	$id	Categories ID (optional)
     * @var	string	$searchamenities	comma delimited list of amenities to match against
     * @access public
     * @return string
     */
    function Category($id = null, $embedded = false, $referer = null, $xml = false, $searchownerid = '', $searchbrand = '')
    {
		if ($xml === false) {	
			$GLOBALS['app']->Layout->AddScriptLink('libraries/js/global2.js');
			$GLOBALS['app']->Layout->AddHeadLink('gadgets/Websites/resources/style.css', 'stylesheet', 'text/css');
			$GLOBALS['app']->Layout->AddHeadLink('libraries/autocomplete/autocomplete.css', 'stylesheet', 'text/css', 'default');
		}
		$request =& Jaws_Request::getInstance();
        $post = $request->get(array('id', 'gadget', 'name'), 'get');

        $GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$pageconst = 12;
		
        $model = $GLOBALS['app']->LoadGadget('Websites', 'Model');
		$adsLayout = $GLOBALS['app']->LoadGadget('Websites', 'LayoutHTML');

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
			$parent = $model->GetWebsiteParent($id);
		}
        if (Jaws_Error::IsError($parent) && $id != "all" && empty($searchownerid) && empty($searchbrand) && !isset($parent['adparentid'])) {
			require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
            return Jaws_HTTPError::Get(404);
        } else {
			if ($xml === false) {	
				$tpl = new Jaws_Template('gadgets/Websites/templates/');
				$tpl->Load('normal.html');
				$tpl->SetBlock('websites');

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
				$info  = $jUser->GetUserInfoByName($post['name']);
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
				$tpl->SetBlock('websites/content');
				$tpl->SetVariable('pagetype', 'websites');
				
				$breadcrumb_start = '<span class="center_nav_font"><a href="'.$GLOBALS['app']->Map->GetURLFor('Websites', 'Category', array('id' => 'all')).'" class="center_nav_link">View All</a>&nbsp;&nbsp;';
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
						$parent['adparentimage'] = $xss->parse(strip_tags($parent['adparentimage']));
						if (strpos(strtolower($parent['adparentimage']), "://") !== false) {
							$main_image_src = $parent['adparentimage'];
						} else {
							if (substr(strtolower($parent['adparentimage']), -3) == 'jpg' || substr(strtolower($parent['adparentimage']), -4) == 'jpeg') {
								$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.substr($parent['adparentimage'], 0, strrpos($parent['adparentimage'], "/")).'/sm___'.substr($parent['adparentimage'], strrpos($parent['adparentimage'], "/")+1, strlen($parent['adparentimage']));
							} else if (strpos($parent['adparentimage'], 'GADGET:') === false) {
								$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$parent['adparentimage'];
							} else {
								$main_image_src = $parent['adparentimage'];
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
							$brand['image'] = $xss->parse(strip_tags($brand['image']));
							if (strpos(strtolower($brand['image']), "://") !== false) {
								$brand_image_src = $brand['image'];
							} else {
								if (substr(strtolower($brand['image']), -3) == 'jpg' || substr(strtolower($brand['image']), -4) == 'jpeg') {
									$brand_image_src = 'files'.substr($brand['image'], 0, strrpos($brand['image'], "/")).'/sm___'.substr($brand['image'], strrpos($brand['image'], "/")+1, strlen($brand['image']));
									if (!file_exists(JAWS_DATA . 'files'.substr($brand['image'], 0, strrpos($brand['image'], "/")).'/sm___'.substr($brand['image'], strrpos($brand['image'], "/")+1, strlen($brand['image'])))) {
										$brand_image_src = 'files'.$brand['image'];
									}
									$brand_image_src = $GLOBALS['app']->getDataURL() . $brand_image_src;
								} else if (file_exists($GLOBALS['app']->getDataURL() . 'files'.$brand['image'])) {
									$brand_image_src = $GLOBALS['app']->getDataURL() . 'files'.$brand['image'];
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
					$breadcrumbHTML = '>&nbsp;&nbsp;Searching websites '.(!empty($searchkeyword) ? 'that match the keyword  <b>"'.str_replace(' - Amenity', '', $searchkeyword).'"</b> ' : '').(!empty($brandname) ? (!empty($searchkeyword) ? ' AND ' : ''). 'that match the brand  <b>"'.$brandname.'"</b> ': '').(!empty($searchlocation) ? (!empty($searchkeyword) || !empty($searchbrand) ? ' AND ' : ''). 'near your location' : '');
				}
				$breadcrumbHTML = $breadcrumb_start.$breadcrumbHTML."</span>";
				$tpl->SetVariable('BREADCRUMB', $breadcrumbHTML);
				//$tpl->SetVariable('DPATH',  JAWS_DPATH);
				$tpl->SetVariable('JAWS_URL',  $GLOBALS['app']->GetJawsURL() . "/");
				$tpl->SetVariable('HTTP_REFERER',  $GLOBALS['app']->GetSiteURL());
			}
			
			// TODO: Update order 'Active' status via RevisedDate and only show Active
			// e.g. If (OwnerID != 0) {UsersModel->GetUserSubscribedByID(subscription_method, uid, item_id) }
			// send Post records						
			$adminmodel = $GLOBALS['app']->LoadGadget('Websites', 'AdminModel');
			if (!empty($searchownerid)) {
				$posts = $model->GetAllWebsitesByUserID($searchownerid, $searchstatus);
			} else if ((!empty($searchstatus) || !empty($searchkeyword) || !empty($searchbrand) || !empty($sortColumn) || !empty($sortDir)) && (isset($parent['adparentid']) && !empty($parent['adparentid'])) && $id != 'all') {
				$posts = $adminmodel->MultipleSearchWebsites($searchstatus, $searchkeyword, $searchbrand, '', $searchlocation, null, null, $parent['adparentid'], $sortColumn, $sortDir, 'Y');
			} else if ($id == 'all') {
				$posts = $adminmodel->MultipleSearchWebsites($searchstatus, $searchkeyword, $searchbrand, '', $searchlocation, null, null, null, $sortColumn, $sortDir, 'Y');
			} else {
				$posts = $model->GetAllWebsitesOfParent($parent['adparentid'], $sortColumn, $sortDir, 'Y');
			}
			
			if (!Jaws_Error::IsError($posts)) {
				/*
				echo '<pre>';
				var_dump($posts);
				echo '</pre>';
				exit;
				*/
				$page_cycle = '';
				
				if ($GLOBALS['app']->Registry->Get('/gadgets/Websites/randomize') == 'Y') {
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
									$page_cycle .= '<a href="'.$GLOBALS['app']->getSiteURL().'/index.php?gadget=Websites&action=Category&id='.$id.'&status='.$searchstatus.'&sortColumn='.$sortColumn.'&sortDir='.$sortDir.'&keyword='.$searchkeyword.'&location='.$searchlocation.'&radius='.$searchradius.'&start='.($z*$pageconst).'" style="text-decoration:underline;">';
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
									$snoopy = new Snoopy;
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
									
									$output_xml .=  "	<marker address=\"\" lnglat=\"".$coordinates."\" title=\"".($i+1)."\" ext=\"".(isset($info['company']) ? $xss->parse($info['company']) : 'Location')."\" url=\"".urlencode($GLOBALS['app']->Map->GetURLFor('Websites', 'Website', array('id' => $posts[$i]['id'])))."\" target=\"_self\" fs=\"10\" sfs=\"6\" bw=\"2\" ra=\"9\" fc=\"FFFFFF\" fg=\"666666\" bc=\"FFFFFF\" hfc=\"222222\" hfg=\"FFFFFF\" hbc=\"666666\"><![CDATA[ ".$marker_html." ]]></marker>\n";
								}
							} else {
								$tpl->SetBlock('websites/content/ad');
								// TODO: Implement Preview mode (use cookie to store length of time the preview is available)				
								$hasDetails = false;
								$tpl->SetVariable('title', $xss->parse(strip_tags($posts[$i]['title'])));
								$tpl->SetVariable('id', (int)$posts[$i]['id']);
								$tpl->SetVariable('LinkID', $id);
								//$property_link = $GLOBALS['app']->GetSiteURL().'/index.php?gadget=Properties&action=Property&id='.$posts[$i]['id'].'&linkid='.($id != 'all' ? $id : '');
								$property_link = $GLOBALS['app']->Map->GetURLFor('Websites', 'Website', array('id' => (int)$posts[$i]['id']));
								$tpl->SetVariable('ad_link', $property_link);
								$image_src = '';	
								if (isset($page['image']) && !empty($page['image'])) {
									$page['image'] = $xss->parse(strip_tags($page['image']));
									$tpl->SetVariable('image', $page['image']);
									if (substr(strtolower($page['image']), 0, 4) == "http") {
										$image_src = $page['image'];
										if (substr(strtolower($page['image']), 0, 7) == "http://") {
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
										if (substr(strtolower($page['image']), -3) == 'jpg' || substr(strtolower($page['image']), -4) == 'jpeg') {
											$image_src = 'files'.substr($page['image'], 0, strrpos($page['image'], "/")).'/sm___'.substr($page['image'], strrpos($page['image'], "/")+1, strlen($page['image']));
											if (!file_exists(JAWS_DATA . 'files'.substr($page['image'], 0, strrpos($page['image'], "/")).'/sm___'.substr($page['image'], strrpos($page['image'], "/")+1, strlen($page['image'])))) {
												$image_src = 'files'.$page['image'];
											}
											$image_src = $GLOBALS['app']->getDataURL() . $image_src;
										} else if (file_exists($GLOBALS['app']->getDataURL() . 'files'.$page['image'])) {
											$image_src = $GLOBALS['app']->getDataURL() . 'files'.$page['image'];
										}
									}
								}
								$tpl->SetVariable('WEBSITE', $adsLayout->Display((int)$posts[$i]['id']));

								if (isset($info['address']) && !empty($info['address'])) {
									$tpl->SetVariable('address', $xss->parse(strip_tags($info['address'])));
								}
								if (isset($info['region']) && !empty($info['region'])) {
									$tpl->SetVariable('region', $xss->parse($info['region']));
								}
								if (isset($info['city']) && !empty($info['city'])) {
									$tpl->SetVariable('city', '<img border="0" src="images/spacer.gif" height="3" width="1"><img border="0" src="images/icon_sm_arrow_gray.gif"><img border="0" src="images/spacer.gif" height="3" width="1"><a href="'.$GLOBALS['app']->getSiteURL().'/index.php?gadget=Websites&action=Category&id=all&keyword='.$xss->parse(strip_tags($info['city'])).'">'.$xss->parse(strip_tags($info['city'])).(isset($info['region']) && !empty($info['region']) ? ', '.$info['region'] : '').'</a>&nbsp;<br />');
								}
								$tpl->SetVariable('OwnerID', (int)$posts[$i]['ownerid']);
								$tpl->SetVariable('Active', $xss->parse($posts[$i]['active']));
								$tpl->SetVariable('Created', $xss->parse($posts[$i]['created']));
								$tpl->SetVariable('Updated', $xss->parse($posts[$i]['updated']));
								
								if ((isset($info['company']) && !empty($info['company'])) || (isset($info['nickname']) && !empty($info['nickname']))) {
									$agent_html = '';
									if (isset($info['company']) && !empty($info['company'])) {
										$agent_html .= '<nobr><b>'.($page['ownerid'] > 0 ? '<a href="index.php?gadget=Websites&action=Category&id=all&owner_id='.$page['ownerid'].'">' : '').$xss->parse(strip_tags($info['company'])).($page['ownerid'] > 0 ? '</a>' : '').'</b></nobr>';
									} else if (isset($info['nickname']) && !empty($info['nickname'])) {
										$agent_html .= '<nobr><b>'.($page['ownerid'] > 0 ? '<a href="index.php?gadget=Websites&action=Category&id=all&owner_id='.$page['ownerid'].'">' : '').$xss->parse(strip_tags($info['nickname'])).($page['ownerid'] > 0 ? '</a>' : '').'</b></nobr>';
									}
									$tpl->SetVariable('agent', $agent_html);
																																				
									$broker_logo_src = '';
									if (!empty($info['logo']) && isset($info['logo'])) {
										$info['logo'] = $xss->parse(strip_tags($info['logo']));
										if (strpos(strtolower($info['logo']), "://") !== false) {
											$broker_logo_src = $info['logo'];
										} else {
											if (substr(strtolower($info['logo']), -3) == 'jpg' || substr(strtolower($info['logo']), -4) == 'jpeg') {
												$broker_logo_src = 'files'.substr($info['logo'], 0, strrpos($info['logo'], "/")).'/sm___'.substr($info['logo'], strrpos($info['logo'], "/")+1, strlen($info['logo']));
												if (!file_exists(JAWS_DATA . 'files'.substr($info['logo'], 0, strrpos($info['logo'], "/")).'/sm___'.substr($info['logo'], strrpos($info['logo'], "/")+1, strlen($info['logo'])))) {
													$broker_logo_src = 'files'.$info['logo'];
												}
												$broker_logo_src = $GLOBALS['app']->getDataURL() . $broker_logo_src;
											} else if (file_exists($GLOBALS['app']->getDataURL() . 'files'.$info['logo'])) {
												$broker_logo_src = $GLOBALS['app']->getDataURL() . 'files'.$info['logo'];
											}
										}
									}
									if (!empty($info['image']) && isset($info['image'])) {
										$info['image'] = $xss->parse(strip_tags($info['image']));
										if (strpos(strtolower($info['image']), "://") !== false) {
											$broker_logo_src = $info['image'];
										} else {
											if (substr(strtolower($info['image']), -3) == 'jpg' || substr(strtolower($info['image']), -4) == 'jpeg') {
												$broker_logo_src = 'files'.substr($info['image'], 0, strrpos($info['image'], "/")).'/sm___'.substr($info['image'], strrpos($info['image'], "/")+1, strlen($info['image']));
												if (!file_exists(JAWS_DATA . 'files'.substr($info['image'], 0, strrpos($info['image'], "/")).'/sm___'.substr($info['image'], strrpos($info['image'], "/")+1, strlen($info['image'])))) {
													$broker_logo_src = 'files'.$info['image'];
												}
												$broker_logo_src = $GLOBALS['app']->getDataURL() . $broker_logo_src;
											} else if (file_exists($GLOBALS['app']->getDataURL() . 'files'.$info['image'])) {
												$broker_logo_src = $GLOBALS['app']->getDataURL() . 'files'.$info['image'];
											}
										}
									}
									$broker_logo = '';
									if (!empty($broker_logo_src)) {
										$broker_logo .= ($page['ownerid'] > 0 ? '<a href="index.php?gadget=Websites&action=Category&id=all&owner_id='.$page['ownerid'].'">' : '').'<img style="padding-right: 10px; padding-bottom: 10px; align="left" border="0" src="'.$broker_logo_src.'" width="100" '.(strtolower(substr($info['logo'], -3)) == "gif" || strtolower(substr($info['logo'], -3)) == "png" || strtolower(substr($info['logo'], -3)) == "bmp" ? 'height="100"' : '').' />'.($page['ownerid'] > 0 ? '</a>' : '');				
									}
									$tpl->SetVariable('broker_logo', $broker_logo);
								}
								
								// Property Header
								if ($posts[$i]['sitewide'] == 'Y') {
									$property_headerHTML = '<DIV ID="website_featured_bkgnd'.$posts[$i]['id'].'" ALIGN="center" CLASS="website_featured_bkgnd" onmouseover="document.getElementById(\'website_featured_bkgnd'.$posts[$i]['id'].'\').className = \'website_featured_bkgnd_over\';" onmouseout="document.getElementById(\'website_featured_bkgnd'.$posts[$i]['id'].'\').className = \'website_featured_bkgnd\';"><CENTER><TABLE BORDER="0" CELLPADDING="3" CELLSPACING="0" WIDTH="100%"><TR><TD VALIGN="middle" WIDTH="0%"><img border="0" src="images/propnav_feat_spotlight.gif"></td><TD WIDTH="100%"><DIV ALIGN="center" class="website_featured_listing_bkgnd">';
								} else {
									$property_headerHTML = '<DIV ID="website_bkgnd'.$posts[$i]['id'].'" ALIGN="center" CLASS="website_bkgnd" onmouseover="document.getElementById(\'website_bkgnd'.$posts[$i]['id'].'\').className = \'website_bkgnd_over\';" onmouseout="document.getElementById(\'website_bkgnd'.$posts[$i]['id'].'\').className = \'website_bkgnd\';"><CENTER><TABLE BORDER="0" CELLPADDING="3" CELLSPACING="0" WIDTH="100%"><TR><TD WIDTH="100%"><DIV ALIGN="center">';
								}
								$tpl->SetVariable('website_header',  $property_headerHTML);
												
								//$tpl->SetVariable('DPATH', JAWS_DPATH);
								$tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . "/");
								$tpl->SetVariable('HTTP_REFERER', $GLOBALS['app']->GetSiteURL());
																								
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
									//$property_image = '<div class="website_image"><A HREF="'.$property_link.'"><img border="0" src="'.$image_src.'" width="200" '.(strtolower(substr($posts[$i]['image'], -3)) == "gif" || strtolower(substr($posts[$i]['image'], -3)) == "png" || strtolower(substr($posts[$i]['image'], -3)) == "bmp" ? 'height="200"' : '').' /></A></div>';				
									$property_image = '<div class="website_image"><img border="0" src="'.$image_src.'" width="200" '.(strtolower(substr($posts[$i]['image'], -3)) == "gif" || strtolower(substr($posts[$i]['image'], -3)) == "png" || strtolower(substr($posts[$i]['image'], -3)) == "bmp" ? 'height="200"' : '').' /></div>';				
								} else if (empty($posts[$i]['image']) && strpos(strtolower($posts[$i]['description']), "img") === false) {
									//$property_image = '<div class="website_no_image" onclick="location.href=\''.$property_link.'\';"><b>No Image</b></div>';
									$par = $model->GetWebsiteParent((int)$posts[$i]['linkid']);
									if (!Jaws_Error::IsError($par)) {	
										$main_image_src = '';
										if (!empty($par['websiteparentimage']) && isset($par['websiteparentimage'])) {
											$par['websiteparentimage'] = $xss->parse(strip_tags($par['websiteparentimage']));
											if (strpos(strtolower($par['websiteparentimage']), "://") !== false) {
												$main_image_src = $par['websiteparentimage'];
											} else {
												if (substr(strtolower($par['websiteparentimage']), -3) == 'jpg' || substr(strtolower($par['websiteparentimage']), -4) == 'jpeg') {
													$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.substr($par['websiteparentimage'], 0, strrpos($par['websiteparentimage'], "/")).'/sm___'.substr($par['websiteparentimage'], strrpos($par['websiteparentimage'], "/")+1, strlen($par['websiteparentimage']));
												} else if (strpos($par['websiteparentimage'], 'GADGET:') === false) {
													$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$par['websiteparentimage'];
												} else {
													$main_image_src = $par['websiteparentimage'];
												}
											}
										}
										if (!empty($main_image_src) && empty($par['websiteparentimage_code']) && strpos($main_image_src, 'GADGET:') === false) {
											$image = '<img style="padding: 5px;" border="0" src="'.$main_image_src.'" width="200" '.(strtolower(substr($main_image_src, -3)) == "gif" || strtolower(substr($main_image_src, -3)) == "png" || strtolower(substr($main_image_src, -3)) == "bmp" ? 'height="200"' : '').' />';				
											$property_image = '<div class="website_image">'.$image.'</div>';
										} else {
											$property_image = '<div class="website_no_image"><b>No Image</b></div>';
										}
									} else {
										$property_image = '<div class="website_no_image"><b>No Image</b></div>';
									}
								}
								$tpl->SetVariable('website_image', $property_image);
												
								$tpl->ParseBlock('websites/content/website');
							}
						}
					}
				} else {
					if ($xml === false) {	
						$tpl->SetVariable('PAGE_CYCLE', $page_cycle);
						$tpl->SetVariable('CATEGORY_MAP', '');
						$tpl->SetVariable('NO_WEBSITES', '<div style="padding: 10px;"><i>No websites '.(!empty($searchkeyword) ? 'that match the keyword  <b>"'.str_replace(' - Amenity', '', $searchkeyword).'"</b> ' : '').(!empty($brandname) ? (!empty($searchkeyword) ? ' AND ' : ''). 'that match the brand  <b>"'.$brandname.'"</b> ': '').(!empty($searchlocation) ? (!empty($searchkeyword) || !empty($searchbrand) ? ' AND ' : ''). 'near your location ' : '').'were found.</i></div>');
					}
				}
			} else {
				//$page_content = _t('PROPERTIES_ERROR_ASPPAGE_NOT_RETRIEVED', $posts->GetMessage())."\n";
				return new Jaws_Error(_t('WEBSITES_ERROR_ASPPAGE_NOT_RETRIEVED', $posts->GetMessage()), _t('WEBSITES_NAME'));
			}
						
			if ($xml === false) {
				$tpl->ParseBlock('websites/content');
				if ($embedded == true && !is_null($referer)) {	
					$tpl->SetBlock('websites/embedded');
					$tpl->SetVariable('id', 'all');		        
					if (strtolower($referer) == strtolower($_SERVER['SERVER_NAME'])) {
						$tpl->SetVariable('referer', $GLOBALS['app']->GetJawsURL() . "/libraries/iframes/domain1/iframetest_resize1.html");		        
					} else {	
						$tpl->SetVariable('referer', "http://".$referer."/19/img/agents/19/custom_img/common_files/iframetest_resize1.html");		        
					}
					$tpl->ParseBlock('websites/embedded');
				} else {
					$tpl->SetBlock('websites/not_embedded');
					$tpl->SetVariable('id', 'all');		        
					$tpl->ParseBlock('websites/not_embedded');
				}
				// Statistics Code
				$tpl->SetBlock('websites/stats');
				$GLOBALS['app']->Registry->LoadFile('CustomPage');
				$tpl->SetVariable('stats', html_entity_decode($GLOBALS['app']->Registry->Get('/gadgets/CustomPage/googleanalytics_code')));		        
				$tpl->ParseBlock('websites/stats');
				$tpl->ParseBlock('websites');
				return $tpl->Get();
			} else {
				return $output_xml;
			}
		}
	}

    /**
     * Displays an individual ad.
     *
     * @var	int	$id	Properties ID (optional)
     * @access public
     * @return string
     */
    function Website($id = null, $embedded = false, $referer = null)
    {
        $GLOBALS['app']->Layout->AddScriptLink('libraries/js/global2.js');
        $GLOBALS['app']->Layout->AddHeadLink('gadgets/Websites/resources/style.css', 'stylesheet', 'text/css');
		$GLOBALS['app']->Layout->AddScriptLink('libraries/carousel/dist/carousel.js');
		$GLOBALS['app']->Layout->AddHeadLink('libraries/carousel/themes/carousel/prototype-ui.css', 'stylesheet', 'text/css');
		$request =& Jaws_Request::getInstance();
        $post = $request->get(array('id', 'linkid'), 'get');

        $GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $post['id'] = $xss->parse($post['id']);

        $model = $GLOBALS['app']->LoadGadget('Websites', 'Model');
		$adsLayout = $GLOBALS['app']->LoadGadget('Websites', 'LayoutHTML');
        if (is_null($id)) {
			$id = $post['id'];
        }
		$page = $model->GetWebsite($id);

        if (Jaws_Error::IsError($page) || $page['active'] == 'N') {
            require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
            return Jaws_HTTPError::Get(404);
        } else {
			if ((int)$page['ownerid'] > 0) {
				require_once JAWS_PATH . 'include/Jaws/User.php';
				$jUser = new Jaws_User;
				$info = $jUser->GetUserInfoById((int)$page['ownerid'], true, true, true, true);
				if (!Jaws_Error::IsError($info) && file_exists(JAWS_DATA . 'files/css/'.$info['username'].'/custom.css')) {
					$GLOBALS['app']->Layout->AddHeadOther('<link rel="stylesheet" media="screen" type="text/css" href="'.$GLOBALS['app']->getDataURL('', true). 'files/css/'.$info['username'].'/custom.css" />');
				}
			}
            $tpl = new Jaws_Template('gadgets/Websites/templates/');
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
            
			$tpl->SetBlock('website_detail');

            if (!isset($page['id']) || $page['active'] == 'N') {
                $this->SetTitle(_t('WEBSITES_TITLE_NOT_FOUND'));
				$tpl->SetBlock('website_detail/not_found');
                $tpl->SetVariable('content', _t('WEBSITES_CONTENT_NOT_FOUND'));
                $tpl->SetVariable('title', _t('WEBSITES_TITLE_NOT_FOUND'));
                $tpl->ParseBlock('website_detail/not_found');
            } else {
                $tpl->SetBlock('website_detail/content');
				// TODO: Implement Preview mode (use cookie to store length of time the preview is available)				
				$hasDetails = false;
				if (!empty($page['title'])) {
					$GLOBALS['app']->Layout->SetTitle($xss->parse(strip_tags($page['title'])));
				}
				$tpl->SetVariable('title', $xss->parse(strip_tags($page['title'])));
				$tpl->SetVariable('id', $page['id']);
				if ((int)$page['ownerid'] > 0) {
					require_once JAWS_PATH . 'include/Jaws/User.php';
					$userModel = new Jaws_User;
					$info = $userModel->GetUserInfoByID((int)$page['ownerid'], true, true, true, true);
					if (Jaws_Error::IsError($info)) {
						return $info;
					}
				}
				if (isset($info['address']) && !empty($info['address'])) {
					$tpl->SetVariable('address', $xss->parse(strip_tags($info['address'])));
				}
				if (isset($info['city']) && !empty($info['city'])) {
					$tpl->SetVariable('city', '<br /><img border="0" src="images/spacer.gif" height="3" width="1"><img border="0" src="images/icon_sm_arrow_gray.gif"><img border="0" src="images/spacer.gif" height="3" width="1"><a href="'.$GLOBALS['app']->getSiteURL().'/index.php?gadget=Websites&action=Category&id=all&keyword='.$xss->parse(strip_tags($info['city'])).'">'.$xss->parse(strip_tags($info['city'])).(isset($info['region']) ? ', '.$info['region'] : '').'</a>&nbsp;');
				}
				if (isset($info['postal']) && !empty($info['postal'])) {
					$tpl->SetVariable('postal_code', $xss->parse(strip_tags($info['postal'])));
				}
				if (isset($info['country']) && !empty($info['country'])) {
					$tpl->SetVariable('country_id', $xss->parse($info['country']));
				}

				$breadcrumb_start = '<span class="center_nav_font"><a href="'.$GLOBALS['app']->Map->GetURLFor('Websites', 'Category', array('id' => 'all')).'" class="center_nav_link">View All</a>&nbsp;&nbsp;';
				$breadcrumbHTML = '';
								
				$breadcrumbHTML .= '>&nbsp;&nbsp;'.$xss->parse(strip_tags($page['title'])).'&nbsp;&nbsp;';
				$breadcrumbHTML = $breadcrumb_start.$breadcrumbHTML."</span>";
				$tpl->SetVariable('BREADCRUMB', $breadcrumbHTML);
				
				// Property Header
				if ($page['premium'] == 'Y') {
					$property_headerHTML = "<div align=\"center\" class=\"website_featured_bkgnd\"><table border=\"0\" cellpadding=\"3\" cellspacing=\"0\" width=\"100%\"><tr><td valign=\"top\" width=\"0%\"><img border=\"0\" src=\"images/propnav_feat_spotlight.gif\"></td><td width=\"100%\"><div align=\"center\" class=\"website_featured_listing_bkgnd\">";
				} else {
					$property_headerHTML = "<div align=\"center\" class=\"website_bkgnd\"><table border=\"0\" cellpadding=\"3\" cellspacing=\"0\" width=\"100%\"><tr><td width=\"100%\"><div align=\"center\">";
				}
				$tpl->SetVariable('website_header', $property_headerHTML);
				$emailDisabled = false;
				$tpl->SetVariable('emailDisabled', ($emailDisabled === true ? '_disabled' : ''));
				// TODO: Implement Saved Properties / Users integration
				$saveDisabled = false;
				$tpl->SetVariable('saveDisabled', (($saveDisabled === true) ? '_disabled' : ''));
								
				//$tpl->SetVariable('DPATH',  JAWS_DPATH);
				$tpl->SetVariable('JAWS_URL',  $GLOBALS['app']->GetJawsURL() . "/");
				$tpl->SetVariable('HTTP_REFERER',  $GLOBALS['app']->GetSiteURL());
				
				$adsLayout = $GLOBALS['app']->LoadGadget('Websites', 'LayoutHTML');
																
				// Property E-mail Form
				$formsLayout = $GLOBALS['app']->LoadGadget('Forms', 'LayoutHTML');
				$now = $GLOBALS['db']->Date();
				if (strrpos($GLOBALS['app']->GetSiteURL(), "/") > 8) {
					$site_url = substr($GLOBALS['app']->GetSiteURL(), 0, strrpos($GLOBALS['app']->GetSiteURL(), "/"));
				} else {
					$site_url = $GLOBALS['app']->GetSiteURL();		
				}
				$redirect = $GLOBALS['app']->GetSiteURL().'/'.$GLOBALS['app']->Map->GetURLFor('Websites', 'Website', array('id' => $page['id']));
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
				$property_inquiry_form = $formsLayout->Display(null, true, array('id' => 'custom', 'sort_order' => 0, 'title' => 'Website Inquiry', 
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
					'formid' => 'custom', 'title' => "A message has been received for the following website: ".$page['title'],
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
				
				$tpl->SetVariable('WEBSITE_EMAIL_FORM',  $property_email_form);
				$tpl->SetVariable('WEBSITE_INQUIRY_FORM',  $property_inquiry_form);
				
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
					$tpl->SetBlock('website_detail/content/amenity');
					$tpl->SetVariable('amenity', $xss->parse($amenity));
					$tpl->ParseBlock('website_detail/content/amenity');
				}
				*/
				
				// description
				if ((isset($page['description']) && !empty($page['description'])) || (isset($page['barcode']) && !empty($page['barcode']))) {
					$tpl->SetBlock('website_detail/content/description');
					$barcode_src = '';
					if (isset($page['barcode_type']) && !empty($page['barcode_type']) && isset($page['barcode_data']) && !empty($page['barcode_data'])) {
						$barcode_type = strtoupper($xss->parse($page['barcode_type']));
						$barcode_data = urlencode($xss->parse($page['barcode_data']));
						$barcode_src = $GLOBALS['app']->GetJawsURL() . '/libraries/barcode/barcode.php?encode='.$barcode_type.'&bdata='.$barcode_data.'&height=100&scale=3&bgcolor=%23FFFFFF&color=%23444444&file=&type=png';
					}
					$tpl->SetVariable('barcode', $barcode_src);
					$tpl->SetVariable('description', strip_tags($this->ParseText($page['description'], 'Websites'), '<p><a><b><em><i><strong><div><table><tbody><thead><tr><td><span><font><hr><br><ul><li>'));
					$tpl->ParseBlock('website_detail/content/description');
				}
								
				// contact information
				if ((isset($info['company']) && !empty($info['company'])) || (isset($info['nickname']) && !empty($info['nickname']))) {
					$tpl->SetBlock('website_detail/content/contact');
					
					$agent_html = '';
					if (isset($info['company']) && !empty($info['company'])) {
						$agent_html .= '<nobr><b>'.($page['ownerid'] > 0 ? '<a href="index.php?gadget=Websites&action=Category&id=all&owner_id='.$page['ownerid'].'">' : '').$xss->parse(strip_tags($info['company'])).($page['ownerid'] > 0 ? '</a>' : '').'</b></nobr>';
					} else if (isset($info['nickname']) && !empty($info['nickname'])) {
						$agent_html .= '<nobr><b>'.($page['ownerid'] > 0 ? '<a href="index.php?gadget=Websites&action=Category&id=all&owner_id='.$page['ownerid'].'">' : '').$xss->parse(strip_tags($info['nickname'])).($page['ownerid'] > 0 ? '</a>' : '').'</b></nobr>';
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
						if (strpos(strtolower($info['logo']), "://") !== false) {
							$broker_logo_src = $info['logo'];
						} else {
							if (substr(strtolower($info['logo']), -3) == 'jpg' || substr(strtolower($info['logo']), -4) == 'jpeg') {
								$broker_logo_src = $GLOBALS['app']->getDataURL() . 'files'.substr($info['logo'], 0, strrpos($info['logo'], "/")).'/sm___'.substr($info['logo'], strrpos($info['logo'], "/")+1, strlen($info['logo']));
							} else {
								$broker_logo_src = $GLOBALS['app']->getDataURL() . 'files'.$info['logo'];
							}
						}
					}
					if (!empty($info['image']) && isset($info['image'])) {
						$info['image'] = $xss->parse(strip_tags($info['image']));
						if (strpos(strtolower($info['image']), "://") !== false) {
							$broker_logo_src = $info['image'];
						} else {
							if (substr(strtolower($info['image']), -3) == 'jpg' || substr(strtolower($info['image']), -4) == 'jpeg') {
								$broker_logo_src = $GLOBALS['app']->getDataURL() . 'files'.substr($info['image'], 0, strrpos($info['image'], "/")).'/sm___'.substr($info['image'], strrpos($info['image'], "/")+1, strlen($info['image']));
							} else {
								$broker_logo_src = $GLOBALS['app']->getDataURL() . 'files'.$info['image'];
							}
						}
					}
					$broker_logo = '';
					if (!empty($info['logo']) && $broker_logo_src != '') {
						$broker_logo .= ($page['ownerid'] > 0 ? '<a href="index.php?gadget=Websites&action=Category&id=all&owner_id='.$page['ownerid'].'">' : '').'<img style="padding-right: 10px; padding-bottom: 10px; align="left" border="0" src="'.$broker_logo_src.'" width="100" '.(strtolower(substr($info['logo'], -3)) == "gif" || strtolower(substr($info['logo'], -3)) == "png" || strtolower(substr($info['logo'], -3)) == "bmp" ? 'height="100"' : '').' />'.($page['ownerid'] > 0 ? '</a>' : '');				
					}
					$tpl->SetVariable('broker_logo', $broker_logo);
					
					$tpl->ParseBlock('website_detail/content/contact');
				}

				if (!empty($page['image'])) {
					$tpl->SetBlock('website_detail/content/image');
					$tpl->SetVariable('WEBSITE', $adsLayout->Display($page['id'], false, null, true));
					$tpl->ParseBlock('website_detail/content/image');
				} else {
					if (empty($page['image']) && strpos(strtolower($page['description']), "img") === false) {
						$tpl->SetBlock('website_detail/content/no_image');
						$tpl->ParseBlock('website_detail/content/no_image');
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
						$keyword_html .= ' <nobr><img border="0" style="padding-left: 10px;" src="images/ICON_chkbox.gif">&nbsp;<a href="'.$GLOBALS['app']->getSiteURL().'/index.php?gadget=Websites&action=Category&id=all&keyword='.$GLOBALS['app']->UTF8->str_replace('"', '%22', $xss->parse(strip_tags($keyword))).'">'.$keyword.'</a></nobr>';;
					}
					$tpl->SetVariable('keyword', $xss->parse($keyword_html));
				}
				
				$tpl->SetVariable('pagetype', 'website');
				$tpl->ParseBlock('website_detail/content');
				
				if ($embedded == true && !is_null($referer) && isset($page['id'])) {	
					$tpl->SetBlock('website_detail/embedded');
					$tpl->SetVariable('id', $page['id']);		        
					if (strtolower($referer) == strtolower($_SERVER['SERVER_NAME'])) {
						$tpl->SetVariable('referer', $GLOBALS['app']->GetJawsURL() . "/libraries/iframes/domain1/iframetest_resize1.html");		        
					} else {	
						$tpl->SetVariable('referer', "http://".$referer."/19/img/agents/19/custom_img/common_files/iframetest_resize1.html");		        
					}
					$tpl->ParseBlock('website_detail/embedded');
				} else {
					$tpl->SetBlock('website_detail/not_embedded');
					$tpl->SetVariable('id', $page['id']);		        
					$tpl->ParseBlock('website_detail/not_embedded');
				}
			}
		}
		// Statistics Code
		$tpl->SetBlock('website_detail/stats');
		$GLOBALS['app']->Registry->LoadFile('CustomPage');
		$tpl->SetVariable('stats', html_entity_decode($GLOBALS['app']->Registry->Get('/gadgets/CustomPage/googleanalytics_code')));		        
		$tpl->ParseBlock('website_detail/stats');

        $tpl->ParseBlock('website_detail');

        return $tpl->Get();
    }

    /**
     * Displays an XML file with the requested websites
     *
     * @access public
     * @return string
     */
    function WebsitesXML()
    {
		header("Content-type: text/xml");
		$output_xml = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>\n"; 
		/*
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');

		$request =& Jaws_Request::getInstance();
        $get = $request->get(array('id', 'showcase_id'), 'get');

        //$post['showcase_id'] = $xss->defilter($post['showcase_id']);

		//if(!empty($post['showcase_id'])) {
		//	$agentID = $post['showcase_id'];
		//}
		  
		if(!empty($get['id'])) {
			$gid = (int)$get['id'];

	        $model = $GLOBALS['app']->LoadGadget('Websites', 'Model');
			$galleryParent = $model->GetWebsite($gid);
			
			if (!$galleryParent) {
				if (isset($GLOBALS['log'])) {
					$GLOBALS['log']->Log(JAWS_LOG_ERR, "No images were found: $gid");
				}
				$output_xml .= "<gallery>\n";
				$output_xml .=  "	<image targeturl=\"\" target=\"\" path=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/Websites/images/gallery_no_images.jpg\" textLabel=\"No images have been added or activated for this gallery.\">No images have been added or activated for this gallery.</image>\n";
				$output_xml .= "</gallery>\n";
			} else {
				$image_found = false;
				$output_xml .= "<gallery>\n";
				$output_xml .=  "	<image targeturl=\"\" target=\"\" path=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/Websites/images/gallery_no_images.jpg\" textLabel=\"No images have been added or activated for this gallery.\">No images have been added or activated for this gallery.</image>\n";
				$output_xml .= "</gallery>\n";
			} 
			
		} else {
			if (isset($GLOBALS['log'])) {
				$GLOBALS['log']->Log(JAWS_LOG_ERR, "No images were found: $gid");
			}
			$output_xml .= "<gallery>\n";
			$output_xml .=  "	<image targeturl=\"\" target=\"\" path=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/Websites/images/gallery_error.jpg\">Error</image>\n";
			$output_xml .= "</gallery>\n";			
		}
		*/
		return $output_xml;
	}

    /**
     * Displays an individual calendar for embedding.
     *
     * @access public
     * @return string
     */
    function EmbedWebsites()
    {
		$request =& Jaws_Request::getInstance();
		$get = $request->get(array('id', 'mode', 'uid', 'referer', 'css'), 'get');
		$output_html = "";
		
		//$output_html .= "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
		$output_html .= "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" style=\"background: url();\">\n";
		$output_html .= " <head>\n";
		$output_html .= "  <title>Websites</title>\n";
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
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/include/Jaws/Ajax/Response.js\"></script>\n";
		//$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetSiteURL() . "/index.php?gadget=".$this->_Name."&amp;action=Ajax&amp;client\"></script>\n";
		//$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetSiteURL() . "/index.php?gadget=".$this->_Name."&amp;action=AjaxCommonFiles\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/".$this->_Name."/resources/client_script.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/js/swfobject.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/js/admin.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"http://yui.yahooapis.com/2.4.0/build/yahoo-dom-event/yahoo-dom-event.js\"></script>\n";
		//$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/crossframe/cross-frame.js\"></script>\n";
		$output_html .= " </head>\n";
		if (isset($get['id']) && (isset($get['referer']) || $GLOBALS['app']->Session->GetAttribute('gadget_referer'))) {
			$output_html .= " <body style=\"background: transparent url();\" onLoad=\"sizeFrame".$get['id']."(); document.getElementById('".$this->_Name."-editDivStretch-".$get['id']."').style.width = (parseInt(document.getElementById('".$this->_Name."-editDiv-".$get['id']."').offsetWidth)-20)+'px';\">\n";
			$output_html .= " <style>\n";
			$output_html .= "   #".$this->_Name."-editDiv-".$get['id']." { width: 100%; text-align: right; }\n";
			$output_html .= "   #".$this->_Name."-edit-".$get['id']." { display: block; width:20px; height:20px; overflow:hidden; }\n";
			$output_html .= "   #".$this->_Name."-edit-".$get['id'].":hover { width: 118px; }\n";
			$output_html .= " </style>\n";
			$output_html .= " <div id=\"".$this->_Name."-editDiv-".$get['id']."\"><div id=\"".$this->_Name."-editDivStretch-".$get['id']."\" style=\"float: left;\">&nbsp;</div><div style=\"float: left;\"><a onMouseOver=\"document.getElementById('".$this->_Name."-editDivStretch-".$get['id']."').style.width = (parseInt(document.getElementById('".$this->_Name."-editDiv-".$get['id']."').offsetWidth)-118)+'px';\" onMouseOut=\"document.getElementById('".$this->_Name."-editDivStretch-".$get['id']."').style.width = (parseInt(document.getElementById('".$this->_Name."-editDiv-".$get['id']."').offsetWidth)-20)+'px';\" id=\"".$this->_Name."-edit-".$get['id']."\" target=\"_blank\" href=\"".$GLOBALS['app']->GetSiteURL()."/index.php?gadget=Websites&action=account_view&id=".$get['id']."\" title=\"Edit This Gadget\"><img border=\"0\" src=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/Users/images/edit_gadget.png\" /></a></div><div style=\"font-size: 0.1em; clear: both;\">&nbsp;</div></div>";
			$referer = (isset($get['referer']) ? $get['referer'] : $GLOBALS['app']->Session->GetAttribute('gadget_referer'));
			$model = $GLOBALS['app']->LoadGadget('Websites', 'Model');
			$gallery = $model->GetWebsite((int)$get['id']);
			if (!Jaws_Error::IsError($gallery)) {
				$layoutGadget = $GLOBALS['app']->LoadGadget('Websites', 'LayoutHTML');
				$output_html .= $layoutGadget->Display($gallery['id'], true, $referer, true);
			} else {
				return $gallery;
			}
			$output_html .= " </body>\n";
		} else if (isset($get['uid']) && (isset($get['referer']) || $GLOBALS['app']->Session->GetAttribute('gadget_referer'))) {
			require_once JAWS_PATH . 'include/Jaws/User.php';
			$jUser = new Jaws_User;
			$info  = $jUser->GetUserInfoById((int)$get['uid'], true);
			if (!Jaws_Error::IsError($info)) {
				$referer = (isset($get['referer']) ? $get['referer'] : $GLOBALS['app']->Session->GetAttribute('gadget_referer'));
				$layoutGadget = $GLOBALS['app']->LoadGadget('Websites', 'LayoutHTML');
				$id = $get['mode'];
				$layoutGadgetHTML = ''; 
				/*
				if ($get['mode'] == 'TwoButtons') {
					if ($info['user_type'] > 1) {
						$layoutGadgetHTML = $layoutGadget->ShowTwoButtons(true, $referer, (int)$get['uid']);
					} else {
						$layoutGadgetHTML = $layoutGadget->ShowTwoButtons(true, $referer);
					}
				} else if ($get['mode'] == 'FourButtons') {
					if ($info['user_type'] > 1) {
						$layoutGadgetHTML = $layoutGadget->ShowFourButtons(true, $referer, (int)$get['uid']);
					} else {
						$layoutGadgetHTML = $layoutGadget->ShowFourButtons(true, $referer);
					}
				} else if ($get['mode'] == 'Banner') {
					if ($info['user_type'] > 1) {
						$layoutGadgetHTML = $layoutGadget->ShowBanner(true, $referer, (int)$get['uid']);
					} else {
						$layoutGadgetHTML = $layoutGadget->ShowBanner(true, $referer);
					}
				} else if ($get['mode'] == 'LeaderBoard') {
					if ($info['user_type'] > 1) {
						$layoutGadgetHTML = $layoutGadget->ShowLeaderBoard(true, $referer, (int)$get['uid']);
					} else {
						$layoutGadgetHTML = $layoutGadget->ShowLeaderBoard(true, $referer);
					}
				}
				*/
			} else {
				return $info;
			}
			$output_html .= " <body style=\"background: transparent url();\" onLoad=\"sizeFrame".$id."(); document.getElementById('".$this->_Name."-editDivStretch-".$id."').style.width = (parseInt(document.getElementById('".$this->_Name."-editDiv-".$id."').offsetWidth)-20)+'px';\">\n";
			$output_html .= " <style>\n";
			$output_html .= "   #".$this->_Name."-editDiv-".$id." { width: 100%; text-align: right; }\n";
			$output_html .= "   #".$this->_Name."-edit-".$id." { display: block; width:20px; height:20px; overflow:hidden; }\n";
			$output_html .= "   #".$this->_Name."-edit-".$id.":hover { width: 118px; }\n";
			$output_html .= " </style>\n";
			$output_html .= " <div id=\"".$this->_Name."-editDiv-".$id."\"><div id=\"".$this->_Name."-editDivStretch-".$id."\" style=\"float: left;\">&nbsp;</div><div style=\"float: left;\"><a onMouseOver=\"document.getElementById('".$this->_Name."-editDivStretch-".$id."').style.width = (parseInt(document.getElementById('".$this->_Name."-editDiv-".$id."').offsetWidth)-118)+'px';\" onMouseOut=\"document.getElementById('".$this->_Name."-editDivStretch-".$id."').style.width = (parseInt(document.getElementById('".$this->_Name."-editDiv-".$id."').offsetWidth)-20)+'px';\" id=\"".$this->_Name."-edit-".$id."\" target=\"_blank\" href=\"".$GLOBALS['app']->GetSiteURL()."/index.php?gadget=Websites&action=account_admin\" title=\"Edit This Gadget\"><img border=\"0\" src=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/Users/images/edit_gadget.png\" /></a></div><div style=\"font-size: 0.1em; clear: both;\">&nbsp;</div></div>";
			$output_html .= $layoutGadgetHTML;
			$output_html .= " </body>\n";
		}
		$output_html .= "</html>\n";
		
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
		$request =& Jaws_Request::getInstance();
        $get = $request->get(array('id'), 'get');
        if (is_null($info)) {
			$info = array();
			$info['id'] = $get['id'];
		}
        require_once JAWS_PATH . 'include/Jaws/Template.php';
        $tpl = new Jaws_Template('gadgets/Websites/templates/');
        $tpl->Load('users.html');

		$userModel  = $GLOBALS['app']->LoadGadget('Users', 'Model');
		
		$panes_found = false;

		$pane_status = $userModel->GetGadgetPaneInfoByUserID($this->_Name, $info['id']);
		if (!Jaws_Error::IsError($pane_status)) {
			$tpl->SetBlock('pane');
			$tpl->SetVariable('title', $this->_Name);
			$tpl->SetVariable('gadget', $this->_Name);
					
			$buttons = '';
			if ($pane_status['status'] == 'maximized') {
				$buttons = "<a href=\"javascript:void(0);\" id=\"".$this->_Name."_button1\"><img border=\"0\" src=\"images/btn_paneMin_on.png\" name=\"Collapse\" alt=\"Collapse\" title=\"Collapse\" onClick=\"minPane('".$this->_Name."', ".$info['id'].");\" onMouseover=\"this.src='images/btn_paneMin_off.png'\" onMouseOut=\"this.src='images/btn_paneMin_on.png'\" /></a>";
			} else if ($pane_status['status'] == 'minimized') {
				$buttons = "<a href=\"javascript:void(0);\" id=\"".$this->_Name."_button2\"><img border=\"0\" src=\"images/btn_paneMax_on.png\" name=\"Expand\" alt=\"Expand\" title=\"Expand\" onClick=\"maxPane('".$this->_Name."', ".$info['id'].");\" onMouseover=\"this.src='images/btn_paneMax_off.png'\" onMouseOut=\"this.src='images/btn_paneMax_on.png'\" /></a><style>#".$this->_Name."_pane { display: none; };</style>";
			}
			$tpl->SetVariable('gadget_pane_buttons', $buttons);

			//Construct panes for each available pane_method
			$panes = $this->GetUserAccountPanesInfo($groups);
			foreach ($panes as $pane_method => $pane_name) {
				$tpl->SetBlock('pane/pane_item');
				$tpl->SetVariable('gadget', $this->_Name);
				if ($panes_found != true) {
					$panes_found = true;
				}
				if (file_exists(JAWS_PATH . 'gadgets/'.$this->_Name.'/images/sm_'.$pane_method.'.gif')) {
					$tpl->SetVariable('icon', $GLOBALS['app']->GetJawsURL() . '/gadgets/'.$this->_Name.'/images/sm_'.$pane_method.'.gif');
				} else {
					$tpl->SetVariable('icon', $GLOBALS['app']->GetJawsURL() . '/gadgets/'.$this->_Name.'/images/logo.png');
				}
				$tpl->SetVariable('pane', $pane_method);
				$tpl->SetVariable('pane_name', $pane_name);
		        $addPage =& Piwi::CreateWidget('Button', 'add_website', _t('WEBSITES_ADD_WEBSITE'), STOCK_ADD);
				$addPage->AddEvent(ON_CLICK, "javascript: window.open('index.php?gadget=".$this->_Name."&amp;action=account_form');");
		        $tpl->SetVariable('add_button', $addPage->Get());
				$content = $this->$pane_method($info['id']);
				if ($content) {
					$tpl->SetVariable('gadget_pane', $content);
				} else {
					return _t('GLOBAL_ERROR_GET_ACCOUNT_PANE');
				}
				$tpl->ParseBlock('pane/pane_item');
			}

			$tpl->ParseBlock('pane');
		} else if (Jaws_Error::IsError($pane_status)) {
			return _t('GLOBAL_ERROR_GET_ACCOUNT_PANE');
		}

		return $tpl->Get();
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
			if (isset($group['group_name']) && ($group['group_name'] == strtolower($this->_Name).'_owners' || $group['group_name'] == strtolower($this->_Name).'_users')) {
				$panes['User'.ucfirst(str_replace('_','',$group['group_name']))] = ($group['group_name'] == strtolower($this->_Name).'_owners' ? 'My Websites' : 'Favorite Websites');
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
    function UserWebsitesowners($user)
    {			
		require_once JAWS_PATH . 'include/Jaws/Template.php';
        $tpl = new Jaws_Template('gadgets/Websites/templates/');
        $tpl->Load('users.html');

        $tpl->SetBlock('UserWebsitesSubscriptions');

		$page = $this->account_A();
		
		if (!Jaws_Error::IsError($page)) {
			$page = str_replace('id="main"', '', $page);
			$page = str_replace('id="SyntactsApp"', 'class="SyntactsApp"', $page);
			$tpl->SetVariable('element', $page);
		} else {
			//return new Jaws_Error(_t('GLOBAL_ERROR_GET_ACCOUNT_PANE'), $this->_Name);
			$tpl->SetVariable('element', _t('GLOBAL_ERROR_GET_ACCOUNT_PANE'));
		}

        $tpl->ParseBlock('UserWebsitesSubscriptions');

        return $tpl->Get();
    }
    
	/**
     * Display the pane content.
     *
     * @param int  $user  user id
     * @access public
     * @return string
     */
    function UserWebsitesusers($user)
    {			
		require_once JAWS_PATH . 'include/Jaws/Template.php';
        $tpl = new Jaws_Template('gadgets/Websites/templates/');
        $tpl->Load('users.html');

        $tpl->SetBlock('UserWebsitesSubscriptions');

		$page = $this->SavedWebsites();
		
		if (!Jaws_Error::IsError($page)) {
			$page = str_replace('id="main"', '', $page);
			$page = str_replace('id="SyntactsApp"', 'class="SyntactsApp"', $page);
			$tpl->SetVariable('element', $page);
		} else {
			//return new Jaws_Error(_t('GLOBAL_ERROR_GET_ACCOUNT_PANE'), $this->_Name);
			$tpl->SetVariable('element', _t('GLOBAL_ERROR_GET_ACCOUNT_PANE'));
		}

        $tpl->ParseBlock('UserWebsitesSubscriptions');

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
		//$gadget_admin = $GLOBALS['app']->LoadGadget('Websites', 'AdminHTML');
		return '';
    }
	

    /**
     * Account form
     *
     * @access public
     * @return string
     */
    function account_form()
    {
		$gadget_admin = $GLOBALS['app']->LoadGadget('Websites', 'AdminHTML');
		$page = $gadget_admin->form(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$html_output = str_replace("<body", "<body style=\"background: url();\"", $users_html->GetAccountHTML('Websites'));
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
		$gadget_admin = $GLOBALS['app']->LoadGadget('Websites', 'AdminHTML');
		$page = $gadget_admin->form_post(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$output_html = str_replace("__JAWS_GADGET__", $page, $users_html->GetAccountHTML('Websites'));
		return $output_html;
    }

    /**
     * Account A
     *
     * @access public
     * @return string
     */
    function account_A()
    {
		$gadget_admin = $GLOBALS['app']->LoadGadget('Websites', 'AdminHTML');
		$html_output = str_replace("&nbsp;<input type=\"button\" value=\"Cancel\" onclick=\"if (window.opener && !window.opener.closed) {window.opener.location.reload();window.close();};\">", '', $gadget_admin->A(true));
		//$output_html = str_replace("<td width=\"100%\" align=\"right\">", "<td width=\"50%\" align=\"right\">", $html_output);
		$output_html = $html_output;
		/*
		$page = $gadget_admin->A(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$html_output = str_replace("<body", "<body style=\"background: url();\"", $users_html->GetAccountHTML('Store'));
		$html_output = str_replace("</head>", "<style type=\"text/css\">body { background: transparent none; }</style>\n</head>", $html_output);
		$output_html = str_replace("__JAWS_GADGET__", $page, $html_output);
		*/
		return $output_html;
	}
    
	/**
     * SavedWebsites
     *
     * @access public
     * @return string
     */
    function SavedWebsites()
    {
		$gadget_admin = $GLOBALS['app']->LoadGadget('Websites', 'AdminHTML');
		$html_output = str_replace("&nbsp;<input type=\"button\" value=\"Cancel\" onclick=\"if (window.opener && !window.opener.closed) {window.opener.location.reload();window.close();};\">", '', $gadget_admin->SavedWebsites(true));
		//$output_html = str_replace("<td width=\"100%\" align=\"right\">", "<td width=\"50%\" align=\"right\">", $html_output);
		$output_html = $html_output;
		/*
		$page = $gadget_admin->A(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$html_output = str_replace("<body", "<body style=\"background: url();\"", $users_html->GetAccountHTML('Store'));
		$html_output = str_replace("</head>", "<style type=\"text/css\">body { background: transparent none; }</style>\n</head>", $html_output);
		$output_html = str_replace("__JAWS_GADGET__", $page, $html_output);
		*/
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
		$gadget_admin = $GLOBALS['app']->LoadGadget('Websites', 'AdminHTML');
		$page = $gadget_admin->view(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$html_output = str_replace("<body", "<body style=\"background: url();\"", $users_html->GetAccountHTML('Websites'));
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
		$gadget_admin = $GLOBALS['app']->LoadGadget('Websites', 'AdminHTML');
		$page = $gadget_admin->A_form(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$html_output = str_replace("<body", "<body style=\"background: url();\"", $users_html->GetAccountHTML('Websites'));
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
		$gadget_admin = $GLOBALS['app']->LoadGadget('Websites', 'AdminHTML');
		$page = $gadget_admin->A_form_post(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$output_html = str_replace("__JAWS_GADGET__", $page, $users_html->GetAccountHTML('Websites'));
		return $output_html;
    }
   
    /**
     * Account B
     *
     * @access public
     * @return string
     */
    function account_B()
    {
		$gadget_admin = $GLOBALS['app']->LoadGadget('Websites', 'AdminHTML');
		$page = $gadget_admin->B(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$html_output = str_replace("<body", "<body style=\"background: url();\"", $users_html->GetAccountHTML('Websites'));
		$html_output = str_replace("</head>", "<style type=\"text/css\">body { background: transparent none; }</style>\n</head>", $html_output);
		$output_html = str_replace("__JAWS_GADGET__", $page, $html_output);
		return $output_html;
    }

    /**
     * Account B_form
     *
     * @access public
     * @return string
     */
    function account_B_form()
    {
		$gadget_admin = $GLOBALS['app']->LoadGadget('Websites', 'AdminHTML');
		$page = $gadget_admin->B_form(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$html_output = str_replace("<body", "<body style=\"background: url();\"", $users_html->GetAccountHTML('Websites'));
		$html_output = str_replace("</head>", "<style type=\"text/css\">body { background: transparent none; }</style>\n</head>", $html_output);
		$output_html = str_replace("__JAWS_GADGET__", $page, $html_output);
		return $output_html;
    }

    /**
     * Account B_form_post
     *
     * @access public
     * @return string
     */
    function account_B_form_post()
    {
		$gadget_admin = $GLOBALS['app']->LoadGadget('Websites', 'AdminHTML');
		$page = $gadget_admin->B_form_post(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$output_html = str_replace("__JAWS_GADGET__", $page, $users_html->GetAccountHTML('Websites'));
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
		return $user_admin->ShowEmbedWindow('Websites', 'OwnWebsites', true);
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
		$output_html = '';
		if($uid > 0) {
			$output_html .= $this->Category();
		} else {
            require_once JAWS_PATH . 'include/Jaws/Header.php';
            Jaws_Header::Location($GLOBALS['app']->GetSiteURL().'/');
		}
		
		return $output_html;
    }
}
