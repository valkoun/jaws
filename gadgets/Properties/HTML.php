<?php
/**
 * Properties Gadget
 *
 * @category   Gadget
 * @package    Properties
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
ini_set("memory_limit","100M");
ini_set("post_max_size","25M");
ini_set("upload_max_filesize","2M");
ini_set("max_execution_time","5000");
class PropertiesHTML extends Jaws_GadgetHTML
{
    var $_Name = 'Properties';
    /**
     * Constructor
     *
     * @access public
     */
    function PropertiesHTML()
    {
        $this->Init('Properties');
    }

    /**
     * Excutes the default action, currently displaying the default page.
     *
     * @access public
     * @return string
     */
    function DefaultAction()
    {
        return $this->Category('all');
    }

    /**
     * View properties by category.
     *
     * @param	int	$id	Categories ID (optional)
     * @param	boolean 	$embedded 	Embedded mode
     * @param	string 	$referer 	Embedding referer
     * @param	string 	$searchamenities	Comma delimited list of amenities to match against
     * @param	boolean 	$xml	XML output response
     * @param	string 	$searchownerid	OwnerIDs to match against
     * @access 	public
     * @return 	string 	HTML template content
     */
    function Category($id = null, $embedded = false, $referer = null, $searchamenities = '', $xml = false, $searchownerid = '')
    {
		require_once JAWS_PATH . 'include/Jaws/Image.php';
        
		if ($xml === false) {	
			$GLOBALS['app']->Layout->AddScriptLink('libraries/js/global2.js');
			$GLOBALS['app']->Layout->AddHeadLink('gadgets/Properties/resources/style.css', 'stylesheet', 'text/css');
			$GLOBALS['app']->Layout->AddHeadLink('libraries/autocomplete/autocomplete.css', 'stylesheet', 'text/css', 'default');
		}
        $model = $GLOBALS['app']->LoadGadget('Properties', 'Model');

        $GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$pageconst = 12;
		
		$request =& Jaws_Request::getInstance();
		$gadget = $request->get('gadget', 'get');
		$name = $request->get('name', 'get');
        $edit = $request->get('edit', 'get');
		$edit = ((!empty($edit) && $edit == 'true') ? true : false);
		if (is_null($id)) {
			$id = $request->get('id', 'post');
			if (empty($id)) {
				$id = $request->get('id', 'get');
			}
			if (substr($id, 0, 16) == 'searchamenity - ') {
				$searchamenities = str_replace('searchamenity - ', '', $id);
				$id = 'all';
			}
        }
		if (empty($id) || (!is_numeric($id) && strtolower($id) == 'all') || !empty($searchamenities) || !empty($searchownerid)) {
			$id = 'all';
		}

		if ($id != 'all') {
			$parent = $model->GetPropertyParent($id);
		}
        if (Jaws_Error::IsError($parent) && $id != "all" && empty($searchamenities) && empty($searchownerid) && !isset($parent['propertyparentid'])) {
			require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
            return Jaws_HTTPError::Get(404);
        } else {
			if ($xml === false) {	
				$tpl = new Jaws_Template('gadgets/Properties/templates/');
				$tpl->Load('normal.html');
				$tpl->SetBlock('properties');

				/*
				if ($page['propertyparentactive'] == 'N') {
					$this->SetTitle(_t('PROPERTIES_TITLE_NOT_FOUND'));
					$tpl->SetBlock('properties/not_found');
					$tpl->SetVariable('content', _t('PROPERTIES_CONTENT_NOT_FOUND'));
					$tpl->SetVariable('title', _t('PROPERTIES_TITLE_NOT_FOUND'));
					$tpl->ParseBlock('properties/not_found');
				} else {
				*/
				
				if (isset($parent['propertyparentcategory_name']) && !empty($parent['propertyparentcategory_name'])) {
					$GLOBALS['app']->Layout->SetTitle(strip_tags($parent['propertyparentcategory_name']));
				}
			} else {
				$output_xml = '';
			}
			$start = $request->get('start', 'post');
			if (empty($start)) {
				$start = $request->get('start', 'get');
			}
			if (empty($start)) {
				$start = 0;
			}
			$searchstatus = $request->get('status', 'post');
			if (empty($searchstatus)) {
				$searchstatus = $request->get('status', 'get');
			}
			$searchkeyword = $request->get('keyword', 'post');
			if (empty($searchkeyword)) {
				$searchkeyword = $request->get('keyword', 'get');
			}
			$searchbedroom = $request->get('bedroom', 'post');
			if (empty($searchbedroom)) {
				$searchbedroom = $request->get('bedroom', 'get');
			}
			$searchbathroom = $request->get('bathroom', 'post');
			if (empty($searchbathroom)) {
				$searchbathroom = $request->get('bathroom', 'get');
			}
			$searchcategory = $request->get('category', 'post');
			if (empty($searchcategory)) {
				$searchcategory = $request->get('category', 'get');
			}
			$searchcommunity = $request->get('community', 'get');
			if (empty($searchcommunity)) {
				$searchcommunity = $request->get('community', 'get');
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
			if (empty($searchamenities)) {
				$searchamenities = $request->get('amenities', 'post');
			}
			if (empty($searchamenities)) {
				$searchamenities = $request->get('amenities', 'get');
			}
			$searchcountryid = $request->get('country_id', 'post');
			if (empty($searchcountryid)) {
				$searchcountryid = $request->get('country_id', 'get');
			}
			require_once JAWS_PATH . 'include/Jaws/User.php';
			$jUser = new Jaws_User;
			if (strtolower($gadget) == 'users' && isset($name) && !empty($name)) {
				$info  = $jUser->GetUserInfoByName($name);
				if (!isset($info['id'])) {
					$info  = $jUser->GetUserInfoByID((int)$name);
				}
				$searchownerid = $info['id'];
			}
			if (empty($searchownerid)) {
				$searchownerid = $request->get('owner_id', 'post');
			}
			if (empty($searchownerid)) {
				$searchownerid = $request->get('owner_id', 'get');
			}
			$searchdate = $request->get('startdate', 'post');
			if (empty($searchdate)) {
				$searchdate = $request->get('startdate', 'get');
			}
				
			if ($edit === true) {
				$tpl->SetBlock('properties/edit');
				$GLOBALS['app']->Layout->AddHeadOther('<style>#jaws-menubar-menu {display: none;} #closeButton {display: none;} #filter_options {display: none;} #categoryMap {display: none;} #workarea {background: #FFFFFF; color: #333333; padding: 5px;}</style>');
				$adminHTML = $GLOBALS['app']->LoadGadget('Properties', 'AdminHTML');
				/*
				if ($page['ownerid'] > 0 && $page['ownerid'] == $GLOBALS['app']->Session->GetAttribute('user_id')) {
					$page_content = $adminHTML->view(true);
				} else */if ($GLOBALS['app']->Session->GetPermission('Properties', 'default')) {
					$page_content = $adminHTML->A();
				} else {
					require_once JAWS_PATH . 'include/Jaws/Header.php';
					Jaws_Header::Location('admin.php?gadget=Properties&action=Admin');
				}
				$tpl->SetVariable('pagetype', 'properties');
				$tpl->SetVariable('id', $id);
				if (isset($parent['propertyparentcategory_name']) && !empty($parent['propertyparentcategory_name'])) {
					$page_content = '<div id="edit-location">Editing: <b>'.$parent['propertyparentcategory_name'].'</b></div>'.$page_content;
				}
				$tpl->SetVariable('content', $page_content);
				$tpl->ParseBlock('properties/edit');
			} else {
				if ($xml === false) {	
					$tpl->SetBlock('properties/content');
					$tpl->SetVariable('pagetype', 'properties');
					
					$breadcrumb_start = '<span class="center_nav_font"><a href="'.$GLOBALS['app']->Map->GetURLFor('Properties', 'Category', array('id' => 'all')).'" class="center_nav_link">All Categories</a>&nbsp;&nbsp;';
				if (!empty($searchownerid)) {
					require_once JAWS_PATH . 'include/Jaws/User.php';
					$jUser = new Jaws_User;
					$userInfo = $jUser->GetUserInfoById((int)$searchownerid, true, true, true, true);
					if (!Jaws_Error::isError($userInfo) && !empty($userInfo['id']) && isset($userInfo['id'])) {
						$name = (!empty($userInfo['company']) ? $userInfo['company'] : (!empty($userInfo['nickname']) ? $userInfo['nickname'] : 'This User'));
						$breadcrumb_start .= '>&nbsp;&nbsp;<a href="'.$GLOBALS['app']->getSiteURL().'/index.php?gadget=Properties&action=Category&id='.$id.'&status='.$searchstatus.'&sortColumn='.$sortColumn.'&sortDir='.$sortDir.'&bedroom='.$searchbedroom.'&bathroom='.$searchbathroom.'&keyword='.$searchkeyword.'&category='.$searchcategory.'&community='.$searchcommunity.'&amenities='.$searchamenities.'&country_id='.($searchcountryid).'&start='.($z*$pageconst).'&owner_id='.$searchownerid.'" class="center_nav_link">'.$name.'</a>&nbsp;&nbsp;';
					}
				}
					$breadcrumbHTML = '';
					
					if ($id != "all") {
						$tpl->SetVariable('id', $parent['propertyparentid']);
						$tpl->SetVariable('propertyparentID', $parent['propertyparentid']);
						$tpl->SetVariable('propertyparentParent', $parent['propertyparentparent']);
						$tpl->SetVariable('propertyparentsort_order', $parent['propertyparentsort_order']);
						$tpl->SetVariable('propertyparentCategory_Name', strip_tags($parent['propertyparentcategory_name']));
						$main_image_src = '';
						if (!empty($parent['propertyparentimage']) && isset($parent['propertyparentimage'])) {
							$parent['propertyparentimage'] = $xss->filter(strip_tags($parent['propertyparentimage']));
							if (substr($parent['propertyparentimage'],0,7) == "GADGET:") {
								$main_image_src = $parent['propertyparentimage'];
							} else if (substr(strtolower($parent['propertyparentimage']), 0, 4) == "http") {
								$main_image_src = $parent['propertyparentimage'];
								if (substr(strtolower($parent['propertyparentimage']), 0, 7) == "http://") {
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
								$thumb = Jaws_Image::GetThumbPath($parent['propertyparentimage']);
								$medium = Jaws_Image::GetMediumPath($parent['propertyparentimage']);
								if (file_exists(JAWS_DATA . 'files'.$thumb)) {
									$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$thumb;
								} else if (file_exists(JAWS_DATA . 'files'.$medium)) {
									$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$medium;
								} else if (file_exists(JAWS_DATA . 'files'.$parent['propertyparentimage'])) {
									$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$parent['propertyparentimage'];
								}						
							}
						}
						//if (!empty($main_image_src) && empty($parent['propertyparentimage_code']) && strpos($main_image_src, 'GADGET:') === false) {
						if (!empty($main_image_src) && strpos($main_image_src, 'GADGET:') === false) {
							$image_url = '<a href="javascript:void(0);" onclick="window.open(\''.$main_image_src.'\',\'\',\'scrollbars=no\')">';
							if ((isset($parent['propertyparenturl']) && !empty($parent['propertyparenturl'])) && (isset($parent['propertyparenturl_target']) && !empty($parent['propertyparenturl_target']))) {
								$image_url = '<a href="'.$xss->filter($parent['propertyparenturl']).'" target="'.$xss->filter($parent['propertyparenturl_target']).'">';
							}
							$image = '<img style="padding: 5px;" border="0" src="'.$main_image_src.'" width="100" '.(strtolower(substr($main_image_src, -3)) == "gif" || strtolower(substr($main_image_src, -3)) == "png" || strtolower(substr($main_image_src, -3)) == "bmp" ? 'height="100"' : '').' />';				
							$tpl->SetVariable('propertyparentImage', $image_url.$image.'</a>');
							$tpl->SetVariable('image_style', '');
						/*
						} else if (substr($main_image_src, 0, 7) == 'GADGET:' && empty($parent['propertyparentimage_code'])) {	
							$image_gadget = '';
							// Insert any requested Layout Actions
							$jms = $GLOBALS['app']->LoadGadget('Jms', 'AdminModel');
							$gadget_list = $jms->GetGadgetsList(null, true, true, true);
							$pageAdminModel = $GLOBALS['app']->LoadGadget('CustomPage', 'AdminModel');
							//Hold.. if we dont have a selected gadget?.. like no gadgets?
							if (!count($gadget_list) <= 0) {
								reset($gadget_list);
								foreach ($gadget_list as $gadget) {
									$layoutGadget = $GLOBALS['app']->LoadGadget($gadget['realname'], 'LayoutHTML');
									$layoutActions = $pageAdminModel->GetGadgetActions($gadget['realname']);
									if (!Jaws_Error::isError($layoutGadget) && empty($image_gadget)) {
										foreach ($layoutActions as $lactions) {
											$GLOBALS['app']->Registry->LoadFile($gadget['realname']);
											if (strpos($lactions['action'], '(') === false) {
												//$this->_Template->SetVariable('ELEMENT', $goGadget->$action());
												if (isset($lactions['action'])) {
													if (method_exists($layoutGadget, $lactions['action'])) {
														if (strpos($main_image_src, "__GADGET:".$gadget['realname']."_ACTION:".$lactions['action']."__") !== false) {
															$image_gadget = $layoutGadget->$lactions['action']();
															break;
														}
													}
													//echo $layoutGadget->$lactions['action']();
												} elseif (isset($GLOBALS['log'])) {
													$GLOBALS['log']->Log(JAWS_LOG_ERR, "Action $action in $gadget's LayoutHTML dosn't exist.");
												}
											} else {
												preg_match_all('/^([a-z0-9]+)\((.*?)\)$/i', $lactions['action'], $matches);
												if (isset($matches[1][0]) && isset($matches[2][0])) {
													//$this->_Template->SetVariable('ELEMENT', $goGadget->$matches[1][0]($matches[2][0]));
													if (isset($matches[1][0])) {
														if (method_exists($layoutGadget, $matches[1][0])) {
															if (strpos($main_image_src, "__GADGET:".$gadget['realname']."_ACTION:".$matches[1][0].'('.$matches[2][0].')__') !== false) {
																$image_gadget = $layoutGadget->$matches[1][0]($matches[2][0]);
																break;
															}
														}
														//echo $layoutGadget->$matches[1][0]($matches[2][0]);
													} elseif (isset($GLOBALS['log'])) {
														$GLOBALS['log']->Log(JAWS_LOG_ERR, "Action ".$matches[1][0]." in $gadget's LayoutHTML dosn't exist.");
													}
												}
											}
										}
									} else if (!empty($image_gadget)) {
										break;
									} else {
										//$this->_Template->SetVariable('ELEMENT', '');
										if (isset($GLOBALS['log'])) {
											$GLOBALS['log']->Log(JAWS_LOG_ERR, $gadget ." is missing the LayoutHTML. Jaws can't execute Layout " .
																 "actions if the file doesn't exists");
										}
									}
									unset($layoutActions);
									unset($layoutGadget);
								}
							}
							$tpl->SetVariable('propertyparentImage', $image_gadget);
							$tpl->SetVariable('image_style', 'padding: 5px;');
						} else if (!empty($parent['propertyparentimage_code'])) {	
							$image_code = $this->ParseText($parent['propertyparentimage_code'], 'Store');
							$image_code = htmlspecialchars_decode($image_code);
							$tpl->SetVariable('propertyparentImage', $image_code);
							$tpl->SetVariable('image_style', '');
						*/
						} else {
							$tpl->SetVariable('propertyparentImage', '');
							$tpl->SetVariable('image_style', '');
						}
						$tpl->SetVariable('propertyparentDescription', $this->ParseText($parent['propertyparentdescription'], 'Properties'));
						$tpl->SetVariable('propertyparentActive', $parent['propertyparentactive']);
						$tpl->SetVariable('propertyparentOwnerID', $parent['propertyparentownerid']);
						$tpl->SetVariable('propertyparentCreated', $parent['propertyparentcreated']);
						$tpl->SetVariable('propertyparentUpdated', $parent['propertyparentupdated']);
						$tpl->SetVariable('propertyparentFeatured', $parent['propertyparentfeatured']);
						$tpl->SetVariable('propertyparentFast_url', $parent['propertyparentfast_url']);
						$tpl->SetVariable('propertyparentRss_url', $parent['propertyparentrss_url']);
						$tpl->SetVariable('propertyparentRegionID', $parent['propertyparentregionid']);
						$tpl->SetVariable('propertyparentRss_overridecity', $parent['propertyparentrss_overridecity']);
						$breadcrumbHTML .= '>&nbsp;&nbsp;'.strip_tags($parent['propertyparentcategory_name']).'&nbsp;&nbsp;';
						/*
						$parentID = $parent['propertyparentparent'];
						while ($parentID > 0) {
							$grandparent = $model->GetPropertyParent((int)$parent['propertyparentparent']);
							if (!Jaws_Error::IsError($grandparent)) {
								$breadcrumbHTML = '>&nbsp;&nbsp;<a href="'.$GLOBALS['app']->Map->GetURLFor('Properties', 'Category', array('id' => $grandparent['propertyparentfast_url'])).'">'.strip_tags($grandparent['propertyparentcategory_name']).'</a>&nbsp;&nbsp;'.$breadcrumbHTML;
								$parentID = $grandparent['propertyparentparent'];
							}
						}
						*/
					} else {
						$tpl->SetVariable('id', 'all');
					}
					
					if ($breadcrumbHTML == '') {
						$breadcrumbHTML = '>&nbsp;&nbsp;Searching properties '.(!empty($searchstatus) ? 'that match the status of  <b>"'.$searchstatus.'"</b> ': '').(!empty($searchkeyword) ? (!empty($searchstatus) ? ' AND ' : ''). 'that match the keyword  <b>"'.str_replace(' - Amenity', '', $searchkeyword).'"</b> ' : '').(!empty($searchamenities) ? (!empty($searchstatus) || !empty($searchkeyword) ? ' AND ' : ''). 'that match amenities:  <b>"'.$searchamenities.'"</b> ' : '').(!empty($searchdate) ? (!empty($searchamenities) || !empty($searchstatus) || !empty($searchkeyword) ? ' AND ' : ''). 'that have vacancy on:  <b>"'.$searchdate.'"</b> ' : '');
					}
					$breadcrumbHTML = $breadcrumb_start.$breadcrumbHTML."</span>";
					$tpl->SetVariable('BREADCRUMB', $breadcrumbHTML);
					//$tpl->SetVariable('DPATH',  JAWS_DPATH);
					$tpl->SetVariable('JAWS_URL',  $GLOBALS['app']->GetJawsURL() . '/');
					$tpl->SetVariable('HTTP_REFERER',  $GLOBALS['app']->GetSiteURL());
				}			
			
				// TODO: Update order 'Active' status via RevisedDate and only show Active
				// e.g. If (OwnerID != 0) {UsersModel->GetUserSubscribedByID(subscription_method, uid, item_id) }
				// send Post records						
				$adminmodel = $GLOBALS['app']->LoadGadget('Properties', 'AdminModel');
				$searchamenities = str_replace('--', ' ', $searchamenities);
				if (!empty($searchdate)) {
					$hook = $GLOBALS['app']->loadHook('Properties', 'Calendar');
					if ($hook !== false) {
						if (method_exists($hook, 'GetPropertiesByAvailabilityDate')) {
							$posts = $hook->GetPropertiesByAvailabilityDate(
								array(
									'date' => $searchdate,
									'active' => 'Y'
								)
							);
						}
					}
				} else if (!empty($searchownerid)) {
					$posts = $model->GetPropertiesOfUserID((int)$searchownerid, 'Y');
				} else if ((!empty($searchstatus) || !empty($searchkeyword) || !empty($searchbedroom) || !empty($searchbathroom) || !empty($searchcategory) || !empty($searchcommunity) || !empty($sortColumn) || !empty($sortDir) || !empty($searchamenities) || !empty($searchcountryid)) && !is_null($parent['propertyparentid']) && !empty($parent['propertyparentid']) && $id != 'all') {
					$posts = $adminmodel->MultipleSearchProperties($searchstatus, $searchkeyword, $searchbedroom, $searchbathroom, $searchcategory, $searchcommunity, $searchamenities, null, null, $parent['propertyparentid'], $sortColumn, $sortDir, 'Y', $searchcountryid);
				} else if ($id == "all") {
					$posts = $adminmodel->MultipleSearchProperties($searchstatus, $searchkeyword, $searchbedroom, $searchbathroom, $searchcategory, $searchcommunity, $searchamenities, null, null, null, $sortColumn, $sortDir, 'Y', $searchcountryid);
				} else {
					$posts = $model->GetAllPropertiesOfParent($parent['propertyparentid'], $sortColumn, $sortDir, 'Y');
				}
				
				if (!Jaws_Error::IsError($posts)) {
					/*
					echo '<pre>';
					var_dump($posts);
					echo '</pre>';
					exit;
					*/
					$page_cycle = '';
					$countPosts = count($posts);
					
					if ($GLOBALS['app']->Registry->Get('/gadgets/Properties/randomize') == 'Y') {
						if (($id != 'all' && isset($parent['propertyparentrandomize']) && $parent['propertyparentrandomize'] == 'Y') || $id == 'all') {
							if (CACHING_ENABLED && file_exists(JAWS_DATA . 'cache/apps/Properties_index')) {
								$index = file_get_contents(JAWS_DATA . 'cache/apps/Properties_index');
								$index = (int)$index;
								//exit;
							} else {
								$session_id = $GLOBALS['app']->Session->GetAttribute('session_id');
								$string = $session_id;
								/*
								echo '<pre>';
								print_r($posts);
								echo '</pre>';
								*/
								$string = preg_replace('#[^\d]+#', '', $string);
								while ((int)$string > $countPosts) {
									$string = substr($string, 0, (strlen($string)-1));
								}
								$index = ((int)$string)/2;
								if (CACHING_ENABLED) {
									if (file_exists(JAWS_DATA . 'cache')) {
										if (Jaws_Utils::is_writable(JAWS_DATA . 'cache/apps')) {
											if (!file_put_contents(JAWS_DATA . "cache" . DIRECTORY_SEPARATOR . "apps" . DIRECTORY_SEPARATOR . 'Properties_index', $index)) {
												//Jaws_Error::Fatal("Couldn't create cache file.", __FILE__, __LINE__);
											}
										}
									}
								}
							}
							
							/*
							Jaws_Utils::seoShuffle($posts,$string);

							echo '<pre>';
							print_r($posts);
							echo '</pre>';
							
							*/
						}
					}
					
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
										$page_cycle .= '<a href="'.$GLOBALS['app']->getSiteURL().'/index.php?gadget=Properties&action=Category&id='.$id.'&status='.$searchstatus.'&sortColumn='.$sortColumn.'&sortDir='.$sortDir.'&bedroom='.$searchbedroom.'&bathroom='.$searchbathroom.'&keyword='.$searchkeyword.'&category='.$searchcategory.'&community='.$searchcommunity.'&amenities='.$searchamenities.'&country_id='.($searchcountryid).'&start='.($z*$pageconst).'&owner_id='.$searchownerid.'" style="text-decoration:underline;">';
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
							if ($GLOBALS['app']->Registry->Get('/gadgets/Properties/showmap') == 'Y') {
								$propertyLayout = $GLOBALS['app']->LoadGadget('Properties', 'LayoutHTML');
								if ($id == "all") {
									$tpl->SetVariable('CATEGORY_MAP', $propertyLayout->CategoryMap('all', $searchamenities));
								} else {
									$tpl->SetVariable('CATEGORY_MAP', $propertyLayout->CategoryMap($parent['propertyparentid'], $searchamenities));
								}
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
					
								if ($xml === true) {	
									if (((!empty($posts[$i]['address']) || (!empty($posts[$i]['city'])) && !empty($posts[$i]['region']))) && !empty($posts[$i]['coordinates'])) {
										// build address
										$address_region = '';
										$address_city = '';
										$address_address = (isset($posts[$i]['address']) && !empty($posts[$i]['address']) ? $posts[$i]['address'] : '');
										
										$marker_address = $address_address;
										if (isset($posts[$i]['city']) && !empty($posts[$i]['city'])) {
											$address_city = (strpos($address_address, $posts[$i]['city']) === false ? " ".$posts[$i]['city'] : '');
										}
										$marker_address .= $address_city;
										if (isset($posts[$i]['region']) && !empty($posts[$i]['region'])) {
											$region = $model->GetRegion((int)$posts[$i]['region']);
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
										
										$marker_html = '';
										
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
											$image = $GLOBALS['app']->getDataURL() . 'files'.$posts[$i]['image'];
											if (file_exists($image) && substr($image, -5) != "files") {
												$image_exists = "<img border=\"0\" src=\"".$image."\" width=\"150\" />";
												$image_style = "";
											}
										}
										$marker_html .= "<div style=\"".$image_style."clear: left;\">".$image_exists."</div>";
										$marker_html .= "<div style=\"clear: left;\"><b>".(isset($posts[$i]['title']) ? $posts[$i]['title'] : 'My Location')."</b><br />".$info_address."<hr /><br />".$description."</div>";
										$marker_html .= "<div style=\"clear: both;\">&nbsp;</div>";
										*/
										
										$output_xml .=  "	<marker address=\"\" lnglat=\"".$posts[$i]['coordinates']."\" title=\"".($i+1)."\" ext=\"".(isset($posts[$i]['title']) && !empty($posts[$i]['title']) ? $xss->filter(strip_tags($posts[$i]['title'])) : 'My Location')."\" url=\"".urlencode($GLOBALS['app']->Map->GetURLFor('Properties', 'Property', array('id' => $posts[$i]['fast_url'])))."\" target=\"_self\" fs=\"10\" sfs=\"6\" bw=\"2\" ra=\"9\" fc=\"FFFFFF\" fg=\"666666\" bc=\"FFFFFF\" hfc=\"222222\" hfg=\"FFFFFF\" hbc=\"666666\"><![CDATA[ ".$marker_html." ]]></marker>\n";
									}
								} else {
									$tpl->SetBlock('properties/content/property');
									// TODO: Implement Preview mode (use cookie to store length of time the preview is available)				
									$hasDetails = false;
									$tpl->SetVariable('title', strip_tags($posts[$i]['title']));
									$tpl->SetVariable('id', $posts[$i]['id']);
									$tpl->SetVariable('LinkID', $id);
									//$property_link = $GLOBALS['app']->GetSiteURL().'/index.php?gadget=Properties&action=Property&id='.$posts[$i]['id'].'&linkid='.($id != 'all' ? $id : '');
									$property_link = $GLOBALS['app']->Map->GetURLFor('Properties', 'Property', array('id' => $posts[$i]['fast_url']));
									$tpl->SetVariable('property_link', $property_link);
									$tpl->SetVariable('sort_order', $posts[$i]['sort_order']);
									$category = '';
									if (isset($posts[$i]['category']) && !empty($posts[$i]['category'])) {
										$propCategories = explode(',', $posts[$i]['category']);
										foreach($propCategories as $propCategory) {		            
											$catParent = $model->GetPropertyParent((int)$propCategory);
											if (!Jaws_Error::IsError($catParent)) {
												if (isset($parent['propertyparentcategory_name']) && ($parent['propertyparentcategory_name'] != $catParent['propertyparentcategory_name'])) {
													if ($category != '') {
														$category .= ',';
													}
													$category .= '<A HREF="'.$GLOBALS['app']->Map->GetURLFor('Properties', 'Category', array('id' => $catParent['propertyparentfast_url'])).'"><U><B>'.$catParent['propertyparentcategory_name'].'</B></U></A>';
												}
											}
										}
									}
									if ($category != '') {
										$hasDetails = true;
										$category = 'This property belongs to these categories: '.$category;
									}
									$tpl->SetVariable('category', $category);
									$tpl->SetVariable('mls', strip_tags($posts[$i]['mls']));
									$tpl->SetVariable('image', $posts[$i]['image']);
									$image_src = '';
									if (!empty($posts[$i]['image']) && isset($posts[$i]['image'])) {
										$posts[$i]['image'] = $xss->parse(strip_tags($posts[$i]['image']));
										if (substr(strtolower($posts[$i]['image']), 0, 4) == "http") {
											$image_src = $posts[$i]['image'];
											if (substr(strtolower($posts[$i]['image']), 0, 7) == "http://") {
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
											$thumb = Jaws_Image::GetThumbPath($posts[$i]['image']);
											$medium = Jaws_Image::GetMediumPath($posts[$i]['image']);
											if (file_exists(JAWS_DATA . 'files'.$thumb)) {
												$image_src = $GLOBALS['app']->getDataURL() . 'files'.$thumb;
											} else if (file_exists(JAWS_DATA . 'files'.$medium)) {
												$image_src = $GLOBALS['app']->getDataURL() . 'files'.$medium;
											} else if (file_exists(JAWS_DATA . 'files'.$posts[$i]['image'])) {
												$image_src = $GLOBALS['app']->getDataURL() . 'files'.$posts[$i]['image'];
											}
										}
									}
									//$tpl->SetVariable('sm_description', strip_tags($posts[$i]['sm_description']));
									$address = '';
									if (isset($posts[$i]['address']) && !empty($posts[$i]['address'])) {
										$address = strip_tags($posts[$i]['address']);
									}
									$tpl->SetVariable('address', $address);
									if (isset($posts[$i]['region']) && !empty($posts[$i]['region'])) {
										$region = $model->GetRegion((int)$posts[$i]['region']);
										if (Jaws_Error::IsError($region)) {
											//return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTY_NOT_RETRIEVED'), _t('PROPERTIES_NAME'));
											return new Jaws_Error($region->GetMessage(), _t('PROPERTIES_NAME'));
										} else {
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
											if (isset($region['parent'])) {
												$country = $model->GetRegion($region['parent']);
												if (Jaws_Error::IsError($country)) {
													//return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTY_NOT_RETRIEVED'), _t('PROPERTIES_NAME'));
													return new Jaws_Error($country->GetMessage(), _t('PROPERTIES_NAME'));
												} else {
													if (isset($country['country_iso_code']) && !empty($country['country_iso_code'])) {
														$region['region'] .= ', '.$country['country_iso_code'];
													}
												}
											}
										}
									}
									$city = '';
									if (isset($posts[$i]['city']) && !empty($posts[$i]['city'])) {
										$city = '<img border="0" src="images/spacer.gif" height="3" width="1"><img border="0" src="images/ICON_sm_arrow_gray.gif"><img border="0" src="images/spacer.gif" height="3" width="1"><a href="'.$GLOBALS['app']->getSiteURL().'/index.php?gadget=Properties&action=Category&id=all&keyword='.urlencode(strip_tags($posts[$i]['city'])).'">'.strip_tags($posts[$i]['city']).(isset($region['region']) ? ', '.$region['region'] : '').'</a>&nbsp;<br />';
									}
									$tpl->SetVariable('city', $city);
									$tpl->SetVariable('region', $posts[$i]['region']);
									$tpl->SetVariable('postal_code', strip_tags($posts[$i]['postal_code']));
									$tpl->SetVariable('country_id', $posts[$i]['country_id']);
									$community = '';
									if (isset($posts[$i]['community']) && !empty($posts[$i]['community'])) {
										$community = '<img border="0" src="images/spacer.gif" height="3" width="1"><img border="0" src="images/ICON_sm_arrow_gray.gif"><img border="0" src="images/spacer.gif" height="3" width="1"><a href="'.$GLOBALS['app']->getSiteURL().'/index.php?gadget=Properties&action=Category&id=all&community='.urlencode(strip_tags($posts[$i]['community'])).'">'.strip_tags($posts[$i]['community']).'</a>&nbsp;<br />';
									}
									$tpl->SetVariable('community', $community);
									$tpl->SetVariable('phase', strip_tags($posts[$i]['phase']));
									$tpl->SetVariable('lotno', strip_tags($posts[$i]['lotno']));
									$price = '';
									$rentdy = '';
									$rentwk = '';
									$rentmo = '';
									if (!empty($posts[$i]['price']) && ($posts[$i]['price'] > 0)) {
										$price =  '&nbsp;$'.number_format($posts[$i]['price'], 2, '.', ',').'&nbsp;&nbsp;';
									}
									if (!empty($posts[$i]['rentdy']) || !empty($posts[$i]['rentwk']) || !empty($posts[$i]['rentmo'])) {
										if (!empty($posts[$i]['rentdy']) && ($posts[$i]['rentdy'] > 0)) {
											$rentdy = "&nbsp;Nightly From: $".number_format($posts[$i]['rentdy'], 2, '.', ',').'&nbsp;&nbsp;';
										}
										if (!empty($posts[$i]['rentwk']) && ($posts[$i]['rentwk'] > 0)) {
											$rentwk = "&nbsp;Weekly From: $".number_format($posts[$i]['rentwk'], 2, '.', ',').'&nbsp;&nbsp;';
										}
										if (!empty($posts[$i]['rentmo']) && ($posts[$i]['rentmo'] > 0)) {
											$rentmo = "&nbsp;Monthly From: $".number_format($posts[$i]['rentmo'], 2, '.', ',').'&nbsp;&nbsp;';
										}
									}
									$tpl->SetVariable('price', $price);
									$tpl->SetVariable('rentdy', $rentdy);
									$tpl->SetVariable('rentwk', $rentwk);
									$tpl->SetVariable('rentmo', $rentmo);
									$tpl->SetVariable('status', strip_tags($posts[$i]['status']));
									$rental = false;
									if ($posts[$i]['status'] == 'forrent' || $posts[$i]['status'] == 'forlease' || $posts[$i]['status'] == 'rented' || $posts[$i]['status'] == 'leased') {
										$rental = true;
									}
									$tpl->SetVariable('acreage', strip_tags($posts[$i]['acreage']));
									$tpl->SetVariable('sqft', strip_tags($posts[$i]['sqft']));
									$bedroom = '';
									$bathroom = '';
									if (!empty($posts[$i]['bedroom']) && ($posts[$i]['bedroom'] > 0)) {
										$bedroom = '<img border="0" src="images/spacer.gif" height="3" width="1"><img border="0" src="images/ICON_sm_arrow_gray.gif"><img border="0" src="images/spacer.gif" height="3" width="1">Bedrooms: '.strip_tags($posts[$i]['bedroom']).'&nbsp;<br />';
									}
									if (!empty($posts[$i]['bathroom']) && ($posts[$i]['bathroom'] > 0)) {
										$bathroom = '<img border="0" src="images/spacer.gif" height="3" width="1"><img border="0" src="images/ICON_sm_arrow_gray.gif"><img border="0" src="images/spacer.gif" height="3" width="1">Bathrooms: '.strip_tags($posts[$i]['bathroom']).'&nbsp;<br />';
									}
									$tpl->SetVariable('bedroom', $bedroom);
									$tpl->SetVariable('bathroom', $bathroom); 
									$tpl->SetVariable('i360', strip_tags($posts[$i]['i360']));
									$tpl->SetVariable('petstay', $posts[$i]['petstay']);
									$occupancy = '';
									$maxadultno = '';
									$maxchildno = '';
									$minstay = '';
									$roomcount = '';
									$maxcleanno = '';
									$options = '';
									if ($rental === true) {
										if (isset($posts[$i]['occupancy']) && !empty($posts[$i]['occupancy']) && $posts[$i]['occupancy'] > 0) {
											$occupancy = '<img border="0" src="images/spacer.gif" height="3" width="1"><img border="0" src="images/ICON_sm_arrow_gray.gif"><img border="0" src="images/spacer.gif" height="3" width="1">Maximum occupancy: '.strip_tags($posts[$i]['occupancy']).'&nbsp;<br />';
										}
										if (isset($posts[$i]['maxadultno']) && !empty($posts[$i]['maxadultno']) && $posts[$i]['maxadultno'] > 0) {
											$maxadultno = '<img border="0" src="images/spacer.gif" height="3" width="1"><img border="0" src="images/ICON_sm_arrow_gray.gif"><img border="0" src="images/spacer.gif" height="3" width="1">Maximum adults: '.strip_tags($posts[$i]['maxadultno']).'&nbsp;<br />';
										}
										if (isset($posts[$i]['maxchildno']) && !empty($posts[$i]['maxchildno']) && $posts[$i]['maxchildno'] > 0) {
											$maxchildno = '<img border="0" src="images/spacer.gif" height="3" width="1"><img border="0" src="images/ICON_sm_arrow_gray.gif"><img border="0" src="images/spacer.gif" height="3" width="1">Maximum children: '.strip_tags($posts[$i]['maxchildno']).'&nbsp;<br />';
										}
										if (isset($posts[$i]['minstay']) && !empty($posts[$i]['minstay']) && $posts[$i]['minstay'] > 0) {
											$minstay = '<img border="0" src="images/spacer.gif" height="3" width="1"><img border="0" src="images/ICON_sm_arrow_gray.gif"><img border="0" src="images/spacer.gif" height="3" width="1">Minimum number of nights to allow reservations: '.strip_tags($posts[$i]['minstay']).'&nbsp;<br />';
										}
										if (isset($posts[$i]['roomcount']) && !empty($posts[$i]['roomcount']) && $posts[$i]['roomcount'] > 0) {
											$roomcount = '<img border="0" src="images/spacer.gif" height="3" width="1"><img border="0" src="images/ICON_sm_arrow_gray.gif"><img border="0" src="images/spacer.gif" height="3" width="1">Number of rooms: '.strip_tags($posts[$i]['roomcount']).'&nbsp;<br />';
										}
										$maxcleanno = $posts[$i]['maxcleanno'];
										if (isset($posts[$i]['options']) && !empty($posts[$i]['options'])) {
											$options = '<img border="0" src="images/spacer.gif" height="3" width="1"><img border="0" src="images/ICON_sm_arrow_gray.gif"><img border="0" src="images/spacer.gif" height="3" width="1">Options: '.strip_tags($posts[$i]['options']).'&nbsp;<br />';
										}
									}
									$tpl->SetVariable('occupancy', $occupancy);
									$tpl->SetVariable('maxadultno', $maxadultno);
									$tpl->SetVariable('maxchildno', $maxchildno);
									$tpl->SetVariable('minstay', $minstay);
									$tpl->SetVariable('roomcount', $roomcount);
									$tpl->SetVariable('maxcleanno', $maxcleanno);
									$tpl->SetVariable('options', $options);
									$tpl->SetVariable('item1', strip_tags($posts[$i]['item1']));
									$tpl->SetVariable('item2', strip_tags($posts[$i]['item2']));
									$tpl->SetVariable('item3', strip_tags($posts[$i]['item3']));
									$tpl->SetVariable('item4', strip_tags($posts[$i]['item4']));
									$tpl->SetVariable('item5', strip_tags($posts[$i]['item5']));
									$tpl->SetVariable('premium', $posts[$i]['premium']);
									$tpl->SetVariable('ShowMap', $posts[$i]['showmap']);
									$tpl->SetVariable('featured', $posts[$i]['featured']);
									$tpl->SetVariable('OwnerID', $posts[$i]['ownerid']);
									$tpl->SetVariable('Active', $posts[$i]['active']);
									$tpl->SetVariable('Created', $posts[$i]['created']);
									$tpl->SetVariable('Updated', $posts[$i]['updated']);
									$tpl->SetVariable('fast_url', $posts[$i]['fast_url']);
									$tpl->SetVariable('propertyno', $posts[$i]['propertyno']);
									$tpl->SetVariable('internal_propertyno', strip_tags($posts[$i]['internal_propertyno']));
									$tpl->SetVariable('calendar_link', strip_tags($posts[$i]['calendar_link']));
									$tpl->SetVariable('year_built', strip_tags($posts[$i]['year']));
									$tpl->SetVariable('rss_url', strip_tags($posts[$i]['rss_url']));
									
									$user_profile = '';
									// Owner ID details
									if ((int)$posts[$i]['ownerid'] > 0) {
										$info = $jUser->GetUserInfoById((int)$posts[$i]['ownerid'], true, true, true, true);
										if (!Jaws_Error::IsError($info) && isset($info['id'])) {
											if (isset($info['company']) && !empty($info['company'])) {
												$posts[$i]['broker'] = $info['company'];
											}
											if (isset($info['nickname']) && !empty($info['nickname'])) {
												$posts[$i]['agent'] = $info['nickname'];
											}							
											if (isset($info['url']) && !empty($info['url'])) {
												$posts[$i]['agent_website'] = $info['url'];
											}
											if (isset($info['office']) && !empty($info['office'])) {
												$posts[$i]['broker_phone'] = $info['office'];
											}
											if (isset($info['phone']) && !empty($info['phone'])) {
												$posts[$i]['agent_phone'] = $info['phone'];
											} else if (isset($info['tollfree']) && !empty($info['tollfree'])) {
												$posts[$i]['agent_phone'] = $info['tollfree'];
											}
											if (isset($info['logo']) && !empty($info['logo'])) {
												$posts[$i]['agent_photo'] = $info['logo'];
											}
											// has a public profile page with properties?
											$gadget = $GLOBALS['app']->LoadGadget('Properties', 'HTML');
											if (
												!Jaws_Error::IsError($gadget) && method_exists($gadget, 'account_profile') && 
												in_array('Properties', explode(',', $GLOBALS['app']->Registry->Get('/gadgets/user_access_items')))
											) {
												// Get all groups of user
												$groups  = $jUser->GetGroupsOfUser($info['id']);
												// Check if user's groups match gadget
												$inGroup = false;
												foreach ($groups as $group) {
													if (
														$group['group_name'] == 'profile' && ($group['group_status'] == 'active' || 
														$group['group_status'] == 'founder' || $group['group_status'] == 'admin')
													) {
														$inGroup = true;
														break;
													}
												}
												if ($inGroup === true) {
													$user_profile = $GLOBALS['app']->Map->GetURLFor('Users', 'AccountPublic', array('name' => $info['username']));
												}
											}
										}
									}
									$agent_html = '';
									if (isset($posts[$i]['agent']) && !empty($posts[$i]['agent'])) {
										$agent_html .= '<nobr>Listed by: <b>'.(!empty($user_profile) ? '<a href="'.$user_profile.'">' : ($posts[$i]['ownerid'] > 0 ? '<a href="index.php?gadget=Properties&action=Category&id=all&owner_id='.$posts[$i]['ownerid'].'">' : '')).strip_tags($posts[$i]['agent']).(!empty($user_profile) || $posts[$i]['ownerid'] > 0 ? '</a>' : '').'</b></nobr>';
									}
									$tpl->SetVariable('agent', $agent_html);
									$tpl->SetVariable('agent_email', strip_tags($posts[$i]['agent_email']));
									$tpl->SetVariable('agent_phone', strip_tags($posts[$i]['agent_phone']));
									$tpl->SetVariable('agent_website', strip_tags($posts[$i]['agent_website']));
									$tpl->SetVariable('agent_photo', strip_tags($posts[$i]['agent_photo']));
									$broker_html = '';
									if (isset($posts[$i]['broker']) && !empty($posts[$i]['broker'])) {
										$broker_html .= '<br />'.($agent_html != '' ? '<nobr>of ' : '<nobr>Listed by: <b>').strip_tags(str_replace('&nbsp;', ' ', $posts[$i]['broker'])).($agent_html != '' ? '' : '</b>').'</nobr>';
									}
									$tpl->SetVariable('broker', $broker_html);
									$tpl->SetVariable('broker_email', strip_tags($posts[$i]['broker_email']));
									$tpl->SetVariable('broker_phone', strip_tags($posts[$i]['broker_phone']));
									$tpl->SetVariable('broker_website', strip_tags($posts[$i]['broker_website']));
									$broker_logo_src = '';
									if (!empty($posts[$i]['broker_logo']) && isset($posts[$i]['broker_logo'])) {
										$posts[$i]['broker_logo'] = $xss->parse(strip_tags($posts[$i]['broker_logo']));
										if (strpos($posts[$i]['broker_logo'],".swf") !== false) {
											// Flash file not supported
										} else if (substr($posts[$i]['broker_logo'],0,7) == "GADGET:") {
											$broker_logo_src = $posts[$i]['broker_logo'];
										} else {
											$broker_logo_src = $posts[$i]['broker_logo'];
										}
									}
									if (!empty($posts[$i]['agent_photo']) && isset($posts[$i]['agent_photo'])) {
										$posts[$i]['agent_photo'] = $xss->parse(strip_tags($posts[$i]['agent_photo']));
										if (strpos($posts[$i]['agent_photo'],".swf") !== false) {
											// Flash file not supported
										} else if (substr($posts[$i]['agent_photo'],0,7) == "GADGET:") {
											$broker_logo_src = $posts[$i]['agent_photo'];
										} else {
											$broker_logo_src = $posts[$i]['agent_photo'];
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
										if (!empty($boker_logo)) {
											$broker_logo .= (!empty($user_profile) ? '<a href="'.$user_profile.'">' : ($posts[$i]['ownerid'] > 0 ? '<a href="index.php?gadget=Properties&action=Category&id=all&owner_id='.$posts[$i]['ownerid'].'">' : '')).'<img style="padding-left: 5px; padding-bottom: 5px; align="right" border="0" src="'.$broker_logo_src.'" width="80" '.(strtolower(substr($broker_logo_src, -3)) == "gif" || strtolower(substr($broker_logo_src, -3)) == "png" || strtolower(substr($broker_logo_src, -3)) == "bmp" ? 'height="80"' : '').' />'.(!empty($user_profile) || $posts[$i]['ownerid'] > 0 ? '</a>' : '');				
										}
									}
									$tpl->SetVariable('broker_logo', $broker_logo);

									$tpl->SetVariable('alink', strip_tags($posts[$i]['alink']));
									$tpl->SetVariable('alinkTitle', strip_tags($posts[$i]['alinktitle']));
									$tpl->SetVariable('alinkType', strip_tags($posts[$i]['alinktype']));
									$tpl->SetVariable('alink2', strip_tags($posts[$i]['alink2']));
									$tpl->SetVariable('alink2Title', strip_tags($posts[$i]['alink2title']));
									$tpl->SetVariable('alink2Type', strip_tags($posts[$i]['alink2type']));
									$tpl->SetVariable('alink3', strip_tags($posts[$i]['alink3']));
									$tpl->SetVariable('alink3Title', strip_tags($posts[$i]['alink3title']));
									$tpl->SetVariable('alink3type', strip_tags($posts[$i]['alink3type']));
									
									// Property Header
									if ($posts[$i]['premium'] == 'Y') {
										$property_headerHTML = '<DIV ID="property_featured_bkgnd'.$posts[$i]['id'].'" ALIGN="center" CLASS="property_featured_bkgnd" onmouseover="document.getElementById(\'property_featured_bkgnd'.$posts[$i]['id'].'\').className = \'property_featured_bkgnd_over\';" onmouseout="document.getElementById(\'property_featured_bkgnd'.$posts[$i]['id'].'\').className = \'property_featured_bkgnd\';"><CENTER><TABLE BORDER="0" CELLPADDING="3" CELLSPACING="0" WIDTH="100%"><TR><TD VALIGN="middle" WIDTH="0%"><img border="0" src="images/propnav_feat_spotlight.gif"></td><TD WIDTH="100%"><DIV ALIGN="center" class="property_featured_listing_bkgnd">';
									} else {
										$property_headerHTML = '<DIV ID="property_bkgnd'.$posts[$i]['id'].'" ALIGN="center" CLASS="property_bkgnd" onmouseover="document.getElementById(\'property_bkgnd'.$posts[$i]['id'].'\').className = \'property_bkgnd_over\';" onmouseout="document.getElementById(\'property_bkgnd'.$posts[$i]['id'].'\').className = \'property_bkgnd\';"><CENTER><TABLE BORDER="0" CELLPADDING="3" CELLSPACING="0" WIDTH="100%"><TR><TD WIDTH="100%"><DIV ALIGN="center">';
									}
									$tpl->SetVariable('property_header',  $property_headerHTML);
													
									//$tpl->SetVariable('DPATH',  JAWS_DPATH);
									$tpl->SetVariable('JAWS_URL',  $GLOBALS['app']->GetJawsURL() . '/');
									$tpl->SetVariable('HTTP_REFERER',  $GLOBALS['app']->GetSiteURL());
									
									//$view_details = "<div style=\"text-align: right; width: 100%; padding-right: 20px;\"><a href=\"".$property_link."\">"._t('PROPERTIES_VIEW_DETAILS')."</a></div>";
									$view_details = '';
									$no_details = '';
									if (!empty($posts[$i]['community']) || !empty($posts[$i]['city']) || (!empty($posts[$i]['bedroom']) && $posts[$i]['bedroom'] > 0) || 
									(!empty($posts[$i]['bathroom']) && $posts[$i]['bathroom'] > 0) || (!empty($posts[$i]['bedroom']) && $posts[$i]['bedroom'] > 0)) {
										$hasDetails = true;
									}
									
									if ($hasDetails === false && !empty($posts[$i]['description'])) {
										$no_details = "<style>#property_highlights_".$posts[$i]['id']." { display: none; }</style>";
									} else if ($hasDetails === false) {
										$no_details = "<style>#property_highlights_".$posts[$i]['id']." { display: none; }</style><div style=\"width: 100%; padding: 10px;\">"._t('PROPERTIES_NO_LISTING_DETAILS')."</div>";
									}
									$tpl->SetVariable('NO_LISTING_DETAILS',  $no_details);
									$tpl->SetVariable('VIEW_DETAILS',  $view_details);
									
									// amenity
									$amenity = '';
									if (isset($posts[$i]['amenity']) && !empty($posts[$i]['amenity'])) {
										$propAmenities = explode(',', $posts[$i]['amenity']);
										$amenityCount = 0;
										foreach($propAmenities as $propAmenity) {		            
											if ($amenityCount < 8) {
												$amenityParent = $model->GetAmenity((int)$propAmenity);
												if (!Jaws_Error::IsError($amenityParent)) {
													if ($amenity != '') {
														$amenity .= ' ';
													}
													$amenity .= ' <nobr><img border="0" style="padding-left: 10px;" src="images/ICON_chkbox.gif">&nbsp;<a href="'.$GLOBALS['app']->getSiteURL().'/index.php?gadget=Properties&action=Category&id=all&amenities='.urlencode($GLOBALS['app']->UTF8->str_replace('"', '%22', strip_tags($amenityParent['feature']))).'">'.$amenityParent['feature'].'</a></nobr>';;
												}
												$amenityCount++;
											}
										}
									}
									$tpl->SetVariable('amenity', $amenity);
									
									// description
									$description = '';
									if (isset($posts[$i]['sm_description']) && !empty($posts[$i]['sm_description'])) {
										$description = strip_tags($this->ParseText($posts[$i]['sm_description'], 'Properties'));
									} else if (isset($posts[$i]['description']) && !empty($posts[$i]['description'])) {
										$description = substr(strip_tags($this->ParseText($posts[$i]['description'], 'Properties'), '<p><b><a><img><br>'), 0, 500).'... ';
									}
									$tpl->SetVariable('description', $description);
									
									$property_image = '';
									if (!empty($image_src)) {
										$property_image = '<div class="property_image"><A HREF="'.$property_link.'"><img border="0" src="'.$image_src.'" width="200" '.(strtolower(substr($posts[$i]['image'], -3)) == "gif" || strtolower(substr($posts[$i]['image'], -3)) == "png" || strtolower(substr($posts[$i]['image'], -3)) == "bmp" ? 'height="200"' : '').' /></A></div>';				
									} else if (empty($posts[$i]['image']) && strpos(strtolower($posts[$i]['description']), "img") === false) {
										$property_image = '<div class="property_no_image" onclick="location.href=\''.$property_link.'\';"><b>No Image</b></div>';
									}
									$tpl->SetVariable('property_image', $property_image);
													
									$tpl->ParseBlock('properties/content/property');
								}
						}
					} else {
						if ($xml === false) {	
							$tpl->SetVariable('PAGE_CYCLE', $page_cycle);
							$tpl->SetVariable('CATEGORY_MAP', '');
							$tpl->SetVariable('NO_PROPERTIES', '<div style="padding: 10px;"><i>No properties '.(!empty($searchstatus) ? 'that match the status of  <b>"'.$searchstatus.'"</b> ': '').(!empty($searchkeyword) ? (!empty($searchstatus) ? ' AND ' : ''). 'that match the keyword  <b>"'.str_replace(' - Amenity', '', $searchkeyword).'"</b> ' : '').(!empty($searchamenities) ? (!empty($searchstatus) || !empty($searchkeyword) ? ' AND ' : ''). 'that match amenities:  <b>"'.$searchamenities.'"</b> ' : '').'were found.</i></div>');
						}
					}
				} else {
					//$page_content = _t('PROPERTIES_ERROR_ASPPAGE_NOT_RETRIEVED', $posts->GetMessage())."\n";
					return new Jaws_Error(_t('PROPERTIES_ERROR_ASPPAGE_NOT_RETRIEVED', $posts->GetMessage()), _t('PROPERTIES_NAME'));
				}
			}
			/*
			// RSS feed? Parse it here and show as Posts
			$rss_html = "";
			if (isset($page['propertyparentrss_url']) && !is_null($page['propertyparentrss_url']) && !empty($page['propertyparentrss_url'])) {
				require_once(JAWS_PATH . 'libraries/magpierss-0.72/rss_fetch.inc');
				$rss = fetch_rss($page['propertyparentrss_url']);

				if ($rss) {
					$date = $GLOBALS['app']->loadDate();
					$hideRss = $model->GetHiddenRssOfPropertyParent($page['propertyparentid']);
					$i = 0;
					$j = 0;
					$submit_vars['3:cols'] = 4;
					foreach ($rss->items as $item) {
						$hidden = false;
						$rss_title = $item['title'];
						//$rss_title = str_replace($rss->items['source']['title'], '', $rss_title); 
						$rss_url = (strrpos($item['link'], "http://") > 7 ? substr($item['link'], 0, strrpos($item['link'], "http://")) : $item['link']);
						$rss_image = $item['image']['url'];
						$rss_published = (isset($item['date_timestamp']) ? $item['date_timestamp'] : $item['published']);
						$rss_description = (isset($item['description']) ? $item['description'] : $item['summary']);
						//$rss_description = htmlentities($rss_description);
						if (!Jaws_Error::IsError($hideRss)) {
							foreach($hideRss as $r) {		            
								if (htmlentities($rss_title) == $r['title'] && htmlentities($rss_url) == $r['url'] && htmlentities($rss_published) == $r['published']) {
									$hidden = true;
								}
							}
						}
						if (!$hidden) { 
							$submit_vars[SYNTACTS_DB ."3:0:$i"] = $rss_title;
							$submit_vars[SYNTACTS_DB ."3:1:$i"] = $rss_url;
							$submit_vars[SYNTACTS_DB ."3:2:$i"] = $rss_image;
							$submit_vars[SYNTACTS_DB ."3:3:$i"] = $date->Format($rss_published);
							$submit_vars[SYNTACTS_DB ."3:4:$i"] = strip_tags($rss_description, '<img><br><hr>');
							//$submit_vars[SYNTACTS_DB ."3:4:$i"] = $this->ParseText($rss_description, 'Properties');
							$i++;
						}
					}
					$submit_vars['3:rows'] = $i-1;
					//$rss_html .= "<div style=\"clear: all; padding: 15px; text-align:left\"><b>Source: <a href=\"". $rss->channel['link']. "\" target=\"_blank\">". $rss->channel['title']. "</a></b></div>\n";
				} else {
					//$rss_html .= "<div style=\"padding: 15px; text-align:left\"><p><b>There was a problem parsing the RSS feed for: ".$page['propertyparentrss_url'].".</b></p></div>\n";
				}
			}
			*/	
			
			if ($xml === false) {
				$display_id = md5($this->_Name.(isset($parent['propertyparentid']) ? $parent['propertyparentid'] : 'all'));
				$tpl->ParseBlock('properties/content');
				if ($embedded == true && !is_null($referer)) {	
					$tpl->SetBlock('properties/embedded');
					$tpl->SetVariable('id', $display_id);		        
					if (strtolower($referer) == strtolower($_SERVER['SERVER_NAME'])) {
						$tpl->SetVariable('referer', $GLOBALS['app']->GetJawsURL() . "/libraries/iframes/domain1/iframetest_resize1.html");		        
					} else {	
						$tpl->SetVariable('referer', "http://".$referer."/19/img/agents/19/custom_img/common_files/iframetest_resize1.html");		        
					}
					$tpl->ParseBlock('properties/embedded');
				} else {
					$tpl->SetBlock('properties/not_embedded');
					$tpl->SetVariable('id', $display_id);		        
					$tpl->ParseBlock('properties/not_embedded');
				}
				// Statistics Code
				$tpl->SetBlock('properties/stats');
				$GLOBALS['app']->Registry->LoadFile('CustomPage');
				$tpl->SetVariable('stats', html_entity_decode($GLOBALS['app']->Registry->Get('/gadgets/CustomPage/googleanalytics_code')));		        
				$tpl->ParseBlock('properties/stats');
				$tpl->ParseBlock('properties');
				return $tpl->Get();
			} else {
				return $output_xml;
			}
		}
        //}
	}

    /**
     * View property details.
     *
     * @category 	feature
     * @param 	int 	$id 	Properties ID (optional)
     * @param 	boolean 	$embedded 	Embedded mode
     * @param 	string 	$referer 	Embedding referer
     * @access 	public
     * @return 	string
     */
    function Property($id = null, $embedded = false, $referer = null)
    {
		require_once JAWS_PATH . 'include/Jaws/Image.php';
        
		$request =& Jaws_Request::getInstance();
        $post = $request->get(array('id', 'linkid', 'action'), 'get');
        $GLOBALS['app']->Layout->AddScriptLink('libraries/js/global2.js');
        $GLOBALS['app']->Layout->AddHeadLink('gadgets/Properties/resources/style.css', 'stylesheet', 'text/css');
		$GLOBALS['app']->Layout->AddScriptLink('libraries/carousel/dist/carousel.js');
		$GLOBALS['app']->Layout->AddHeadLink('libraries/carousel/themes/carousel/prototype-ui.css', 'stylesheet', 'text/css');
        $GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');
        
		$xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');

        $model = $GLOBALS['app']->LoadGadget('Properties', 'Model');
        if (is_null($id)) {
			$id = $post['id'];
        }
		$page = $model->GetProperty($id);

        if (Jaws_Error::IsError($page)) {
            require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
            return Jaws_HTTPError::Get(404);
        } else {
			if ((int)$page['ownerid'] > 0) {
				require_once JAWS_PATH . 'include/Jaws/User.php';
				$jUser = new Jaws_User;
				$info = $jUser->GetUserInfoById((int)$page['ownerid'], true, true, true, true);
				if (!Jaws_Error::IsError($info) && file_exists(JAWS_DATA . 'files/css/users/'.$info['id'].'/custom.css')) {
					$GLOBALS['app']->Layout->AddHeadOther('<link rel="stylesheet" media="screen" type="text/css" href="'.$GLOBALS['app']->getDataURL('', true). 'files/css/users/'.$info['id'].'/custom.css" />');
				}
			}
            $tpl = new Jaws_Template('gadgets/Properties/templates/');
            $tpl->Load('normal.html');
			
			/*
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
            */
			
			$tpl->SetBlock('property');

            if (!isset($page['id']) || $page['active'] == 'N') {
                $this->SetTitle(_t('PROPERTIES_TITLE_NOT_FOUND'));
				$tpl->SetBlock('property/not_found');
                $tpl->SetVariable('content', _t('PROPERTIES_CONTENT_NOT_FOUND'));
                $tpl->SetVariable('title', _t('PROPERTIES_TITLE_NOT_FOUND'));
                $tpl->ParseBlock('property/not_found');
            } else {
                $tpl->SetBlock('property/content');
				// TODO: Implement Preview mode (use cookie to store length of time the preview is available)				
				$hasDetails = false;
				if (!empty($page['title'])) {
					$GLOBALS['app']->Layout->SetTitle(strip_tags($page['title']));
				}
				$tpl->SetVariable('title', strip_tags($page['title']));
				$tpl->SetVariable('id', $page['id']);
				$tpl->SetVariable('LinkID', (isset($post['linkid']) && !empty($post['linkid']) ? $post['linkid'] : ''));
				$tpl->SetVariable('sort_order', $page['sort_order']);
				$category = '';
				if (isset($page['category']) && !empty($page['category'])) {
					$hasDetails = true;
					$propCategories = explode(',', $page['category']);
					foreach($propCategories as $propCategory) {		            
						$catParent = $model->GetPropertyParent((int)$propCategory);
						if (!Jaws_Error::IsError($catParent)) {
							if ($category != '') {
								$category .= ',';
							}
							$category .= '<A HREF="'.$GLOBALS['app']->Map->GetURLFor('Properties', 'Category', array('id' => $catParent['propertyparentfast_url'])).'"><U><B>'.$catParent['propertyparentcategory_name'].'</B></U></A>';
						}
					}
				}
				if ($category != '') {
					$category = '<br /><img border="0" src="images/spacer.gif" height="3" width="1"><img border="0" src="images/ICON_sm_arrow_gray.gif"><img border="0" src="images/spacer.gif" height="3" width="1">This property belongs to these categories: '.$category;
				}
				$tpl->SetVariable('category', $category);
				$tpl->SetVariable('mls', strip_tags($page['mls']));
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
									$lg_image_src = 'http://'.$img_src;
									break;
								}
							}
						} else {
							$image_src = explode('https://', $image_src);
							foreach ($image_src as $img_src) {
								if (!empty($img_src)) {
									$image_src = 'https://'.$img_src;
									$lg_image_src = 'https://'.$img_src;
									break;
								}
							}
						}
					} else {
						$medium = Jaws_Image::GetMediumPath($page['image']);
						if (file_exists(JAWS_DATA . 'files'.$medium)) {
							$image_src = $GLOBALS['app']->getDataURL() . 'files'.$medium;
						} else if (file_exists(JAWS_DATA . 'files'.$page['image'])) {
							$image_src = $GLOBALS['app']->getDataURL() . 'files'.$page['image'];
						}
						if (file_exists(JAWS_DATA . 'files'.$page['image'])) {
							$lg_image_src = $GLOBALS['app']->getDataURL() . 'files'.$page['image'];
						}
					}
				}
				$tpl->SetVariable('sm_description', strip_tags($page['sm_description']));
				$tpl->SetVariable('address', strip_tags($page['address']));
				if (isset($page['region']) && !empty($page['region'])) {
					$region = $model->GetRegion((int)$page['region']);
					if (Jaws_Error::IsError($region)) {
						//return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTY_NOT_RETRIEVED'), _t('PROPERTIES_NAME'));
						return new Jaws_Error($region->GetMessage(), _t('PROPERTIES_NAME'));
					} else {
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
						if (isset($region['parent'])) {
							$country = $model->GetRegion($region['parent']);
							if (Jaws_Error::IsError($country)) {
								//return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTY_NOT_RETRIEVED'), _t('PROPERTIES_NAME'));
								return new Jaws_Error($country->GetMessage(), _t('PROPERTIES_NAME'));
							} else {
								if (isset($country['country_iso_code']) && !empty($country['country_iso_code'])) {
									$region['region'] .= ', '.$country['country_iso_code'];
								}
							}
						}
					}
				}
				if (isset($page['city']) && !empty($page['city'])) {
					$tpl->SetVariable('city', '<br /><img border="0" src="images/spacer.gif" height="3" width="1"><img border="0" src="images/ICON_sm_arrow_gray.gif"><img border="0" src="images/spacer.gif" height="3" width="1"><a href="'.$GLOBALS['app']->getSiteURL().'/index.php?gadget=Properties&action=Category&id=all&keyword='.urlencode(strip_tags($page['city'])).'">'.strip_tags($page['city']).(isset($region['region']) ? ', '.$region['region'] : '').'</a>&nbsp;');
				}
				$tpl->SetVariable('region', $page['region']);
				$tpl->SetVariable('postal_code', strip_tags($page['postal_code']));
				$tpl->SetVariable('country_id', $page['country_id']);
				if (isset($page['community']) && !empty($page['community'])) {
					$tpl->SetVariable('community', '<br /><img border="0" src="images/spacer.gif" height="3" width="1"><img border="0" src="images/ICON_sm_arrow_gray.gif"><img border="0" src="images/spacer.gif" height="3" width="1"><a href="'.$GLOBALS['app']->getSiteURL().'/index.php?gadget=Properties&action=Category&id=all&community='.urlencode(strip_tags($page['community'])).'">'.strip_tags($page['community']).'</a>&nbsp;');
				}
				$tpl->SetVariable('phase', strip_tags($page['phase']));
				$tpl->SetVariable('lotno', strip_tags($page['lotno']));
				$price = '';
				$rentdy = '';
				$rentwk = '';
				$rentmo = '';
				if (!empty($page['price']) && ($page['price'] > 0)) {
					$price =  '$'.number_format($page['price'], 2, '.', ',');
				}
				if (!empty($page['rentdy']) || !empty($page['rentwk']) || !empty($page['rentmo'])) {
					if (!empty($page['rentdy']) && ($page['rentdy'] > 0)) {
						$rentdy = "Nightly From: $".number_format($page['rentdy'], 2, '.', ',');
					}
					if (!empty($page['rentwk']) && ($page['rentwk'] > 0)) {
						$rentwk = "Weekly From: $".number_format($page['rentwk'], 2, '.', ',');
					}
					if (!empty($page['rentmo']) && ($page['rentmo'] > 0)) {
						$rentmo = "Monthly From: $".number_format($page['rentmo'], 2, '.', ',');
					}
				}
				$tpl->SetVariable('price', $price);
				$tpl->SetVariable('rentdy', $rentdy);
				$tpl->SetVariable('rentwk', $rentwk);
				$tpl->SetVariable('rentmo', $rentmo);
				$tpl->SetVariable('status', strip_tags($page['status']));
				$rental = false;
				if ($page['status'] == 'forrent' || $page['status'] == 'forlease' || $page['status'] == 'rented' || $page['status'] == 'leased') {
					$rental = true;
				}
				$tpl->SetVariable('acreage', strip_tags($page['acreage']));
				$tpl->SetVariable('sqft', strip_tags($page['sqft']));
				if (!empty($page['bedroom']) && ($page['bedroom'] > 0)) {
					$tpl->SetVariable('bedroom', '<br /><img border="0" src="images/spacer.gif" height="3" width="1"><img border="0" src="images/ICON_sm_arrow_gray.gif"><img border="0" src="images/spacer.gif" height="3" width="1">Bedrooms: '.strip_tags($page['bedroom']).'&nbsp;');
				}
				if (!empty($page['bathroom']) && ($page['bathroom'] > 0)) {
					$tpl->SetVariable('bathroom', '<br /><img border="0" src="images/spacer.gif" height="3" width="1"><img border="0" src="images/ICON_sm_arrow_gray.gif"><img border="0" src="images/spacer.gif" height="3" width="1">Bathrooms: '.strip_tags($page['bathroom']).'&nbsp;');
				}
				$tpl->SetVariable('i360', strip_tags($page['i360']));
				$tpl->SetVariable('petstay', $page['petstay']);
				if ($rental === true) {
					if (isset($page['occupancy']) && !empty($page['occupancy']) && $page['occupancy'] > 0) {
						$tpl->SetVariable('occupancy', '<br /><img border="0" src="images/spacer.gif" height="3" width="1"><img border="0" src="images/ICON_sm_arrow_gray.gif"><img border="0" src="images/spacer.gif" height="3" width="1">Maximum occupancy: '.strip_tags($page['occupancy']).'&nbsp;');
					}
					if (isset($page['maxadultno']) && !empty($page['maxadultno']) && $page['maxadultno'] > 0) {
						$tpl->SetVariable('maxadultno', '<br /><img border="0" src="images/spacer.gif" height="3" width="1"><img border="0" src="images/ICON_sm_arrow_gray.gif"><img border="0" src="images/spacer.gif" height="3" width="1">Maximum adults: '.strip_tags($page['maxadultno']).'&nbsp;');
					}
					if (isset($page['maxchildno']) && !empty($page['maxchildno']) && $page['maxchildno'] > 0) {
						$tpl->SetVariable('maxchildno', '<br /><img border="0" src="images/spacer.gif" height="3" width="1"><img border="0" src="images/ICON_sm_arrow_gray.gif"><img border="0" src="images/spacer.gif" height="3" width="1">Maximum children: '.strip_tags($page['maxchildno']).'&nbsp;');
					}
					if (isset($page['minstay']) && !empty($page['minstay']) && $page['minstay'] > 0) {
						$tpl->SetVariable('minstay', '<br /><img border="0" src="images/spacer.gif" height="3" width="1"><img border="0" src="images/ICON_sm_arrow_gray.gif"><img border="0" src="images/spacer.gif" height="3" width="1">Minimum number of nights to allow reservations: '.strip_tags($page['minstay']).'&nbsp;');
					}
					if (isset($page['roomcount']) && !empty($page['roomcount']) && $page['roomcount'] > 0) {
						$tpl->SetVariable('roomcount', '<br /><img border="0" src="images/spacer.gif" height="3" width="1"><img border="0" src="images/ICON_sm_arrow_gray.gif"><img border="0" src="images/spacer.gif" height="3" width="1">Number of rooms: '.strip_tags($page['roomcount']).'&nbsp;');
					}
					$tpl->SetVariable('maxcleanno', $page['maxcleanno']);
					if (isset($page['options']) && !empty($page['options'])) {
						$tpl->SetVariable('options', '<br /><img border="0" src="images/spacer.gif" height="3" width="1"><img border="0" src="images/ICON_sm_arrow_gray.gif"><img border="0" src="images/spacer.gif" height="3" width="1">Options: '.strip_tags($page['options']).'&nbsp;');
					}
				}
				$tpl->SetVariable('item1', strip_tags($page['item1']));
				$tpl->SetVariable('item2', strip_tags($page['item2']));
				$tpl->SetVariable('item3', strip_tags($page['item3']));
				$tpl->SetVariable('item4', strip_tags($page['item4']));
				$tpl->SetVariable('item5', strip_tags($page['item5']));
				$tpl->SetVariable('premium', $page['premium']);
				$tpl->SetVariable('ShowMap', $page['showmap']);
				$tpl->SetVariable('featured', $page['featured']);
				$tpl->SetVariable('OwnerID', $page['ownerid']);
				$tpl->SetVariable('Active', $page['active']);
				$tpl->SetVariable('Created', $page['created']);
				$tpl->SetVariable('Updated', $page['updated']);
				$tpl->SetVariable('fast_url', $page['fast_url']);
				$tpl->SetVariable('propertyno', $page['propertyno']);
				$tpl->SetVariable('internal_propertyno', strip_tags($page['internal_propertyno']));
				$tpl->SetVariable('calendar_link', strip_tags($page['calendar_link']));
				$tpl->SetVariable('year_built', strip_tags($page['year']));
				$tpl->SetVariable('rss_url', strip_tags($page['rss_url']));
				$tpl->SetVariable('alink', strip_tags($page['alink']));
				$tpl->SetVariable('alinkTitle', strip_tags($page['alinktitle']));
				$tpl->SetVariable('alinkType', strip_tags($page['alinktype']));
				$tpl->SetVariable('alink2', strip_tags($page['alink2']));
				$tpl->SetVariable('alink2Title', strip_tags($page['alink2title']));
				$tpl->SetVariable('alink2Type', strip_tags($page['alink2type']));
				$tpl->SetVariable('alink3', strip_tags($page['alink3']));
				$tpl->SetVariable('alink3Title', strip_tags($page['alink3title']));
				$tpl->SetVariable('alink3type', strip_tags($page['alink3type']));

				$breadcrumb_start = '<span class="center_nav_font"><a href="'.$GLOBALS['app']->Map->GetURLFor('Properties', 'Category', array('id' => 'all')).'" class="center_nav_link">All Categories</a>&nbsp;&nbsp;';
				$breadcrumbHTML = '';
				
				if (isset($page['category']) && !empty($page['category'])) {
					$categories = explode(',', $page['category']);
					if ((int)$categories[0] > 0) {
						$parent = $model->GetPropertyParent((int)$categories[0]);
						if (!Jaws_Error::IsError($parent)) {
							$tpl->SetVariable('propertyparentID', $parent['propertyparentid']);
							$tpl->SetVariable('propertyparentParent', $parent['propertyparentparent']);
							$tpl->SetVariable('propertyparentsort_order', $parent['propertyparentsort_order']);
							$tpl->SetVariable('propertyparentCategory_Name', strip_tags($parent['propertyparentcategory_name']));
							$tpl->SetVariable('propertyparentImage', strip_tags($parent['propertyparentimage']));
							$tpl->SetVariable('propertyparentDescription', $this->ParseText($parent['propertyparentdescription'], 'Properties'));
							$tpl->SetVariable('propertyparentActive', $parent['propertyparentactive']);
							$tpl->SetVariable('propertyparentOwnerID', $parent['propertyparentownerid']);
							$tpl->SetVariable('propertyparentCreated', $parent['propertyparentcreated']);
							$tpl->SetVariable('propertyparentUpdated', $parent['propertyparentupdated']);
							$tpl->SetVariable('propertyparentFeatured', $parent['propertyparentfeatured']);
							$tpl->SetVariable('propertyparentFast_url', $parent['propertyparentfast_url']);
							$tpl->SetVariable('propertyparentRss_url', $parent['propertyparentrss_url']);
							$tpl->SetVariable('propertyparentRegionID', $parent['propertyparentregionid']);
							$tpl->SetVariable('propertyparentRss_overridecity', $parent['propertyparentrss_overridecity']);
							$breadcrumbHTML .= '>&nbsp;&nbsp;<a href="'.$GLOBALS['app']->Map->GetURLFor('Properties', 'Category', array('id' => $parent['propertyparentfast_url'])).'" class="center_nav_link">'.strip_tags($parent['propertyparentcategory_name']).'</a>&nbsp;&nbsp;';
							/*
							$parentID = $parent['propertyparentparent'];
							while ($parentID > 0) {
								$grandparent = $model->GetPropertyParent((int)$parent['propertyparentparent']);
								if (!Jaws_Error::IsError($grandparent)) {
									$breadcrumbHTML = '>&nbsp;&nbsp;<a href="'.$GLOBALS['app']->Map->GetURLFor('Properties', 'Category', array('id' => $grandparent['propertyparentfast_url'])).'">'.strip_tags($grandparent['propertyparentcategory_name']).'</a>&nbsp;&nbsp;'.$breadcrumbHTML;
									$parentID = $grandparent['propertyparentparent'];
								}
							}
							*/
						}
					}
				}
				
				$breadcrumbHTML .= '>&nbsp;&nbsp;'.strip_tags($page['title']).'&nbsp;&nbsp;';
				$breadcrumbHTML = $breadcrumb_start.$breadcrumbHTML."</span>";
				$tpl->SetVariable('BREADCRUMB', $breadcrumbHTML);
				
				// Property Header
				if ($page['premium'] == 'Y') {
					$property_headerHTML = "<div align=\"center\" class=\"property_featured_bkgnd\"><table border=\"0\" cellpadding=\"3\" cellspacing=\"0\" width=\"100%\"><tr><td valign=\"top\" width=\"0%\"><img border=\"0\" src=\"images/propnav_feat_spotlight.gif\"></td><td width=\"100%\"><div align=\"center\" class=\"property_featured_listing_bkgnd\">";
				} else {
					$property_headerHTML = "<div align=\"center\" class=\"property_bkgnd\"><table border=\"0\" cellpadding=\"3\" cellspacing=\"0\" width=\"100%\"><tr><td width=\"100%\"><div align=\"center\">";
				}
				$tpl->SetVariable('property_header', $property_headerHTML);
				$emailDisabled = false;
				$tpl->SetVariable('emailDisabled', ($emailDisabled === true ? '_disabled' : ''));
				// TODO: Implement Saved Properties / Users integration
				$saveDisabled = false;
				$tpl->SetVariable('saveDisabled', (($saveDisabled === true) ? '_disabled' : ''));
								
				//$tpl->SetVariable('DPATH',  JAWS_DPATH);
				$tpl->SetVariable('JAWS_URL',  $GLOBALS['app']->GetJawsURL() . '/');
				$tpl->SetVariable('HTTP_REFERER',  $GLOBALS['app']->GetSiteURL());
				
				$propertyLayout = $GLOBALS['app']->LoadGadget('Properties', 'LayoutHTML');
				
				// Map
				if ($GLOBALS['app']->Registry->Get('/gadgets/Properties/showmap') == 'Y' && $page['showmap'] == 'Y') {
					$tpl->SetVariable('PROPERTY_MAP',  $propertyLayout->CategoryMap($page['id'], '', '', false, null, 'HYBRID'));
					$tpl->SetVariable('PROPERTY_MAP_STYLE',  '');
				} else {
					$tpl->SetVariable('PROPERTY_MAP',  '');
					$tpl->SetVariable('PROPERTY_MAP_STYLE',  '#propnav_map {display: none;}'."\n");
				}
				
				// Calendar
				$calendar_html = '';
				$property_calendar_click_text = "Schedule A Visit";
				if ($rental === true) {
					$property_calendar_click_text = "Availability Calendar";
				}
				if ($GLOBALS['app']->Registry->Get('/gadgets/Properties/showcalendar') == 'Y') {
					$tpl->SetVariable('calendarDisabled', '');
					$property_calendar_click = "if (this.className.indexOf('disabled') == -1) {if (document.getElementById('media_map')) {document.getElementById('media_map').style.display = 'none';}; if (document.getElementById('email')) {document.getElementById('email').style.display = 'none';}; if (document.getElementById('tour')) {document.getElementById('tour').style.display = 'none';}; if (document.getElementById('save')) {document.getElementById('save').style.display = 'none';}; if (document.getElementById('calendar')) {var target = document.getElementById('calendar'); if (target.style.display == 'none') {target.style.display = '';} else {target.style.display = 'none';};};};";
					if (!empty($page['calendar_link'])) {
						$property_calendar_click = "window.open('".$page['calendar_link']."');";
					} else if ($rental === true) {
						$calendar_html = $propertyLayout->PropertyCalendar($page['id']);
					} else {
						$tpl->SetVariable('calendarDisabled', '_disabled');
					}
				} else {
					$tpl->SetVariable('calendarDisabled', '_disabled');
					$tpl->SetVariable('PROPERTY_CALENDAR_STYLE', '#propnav_calendar_disabled {display: none;}');
					$property_calendar_click = '';
				}
				$tpl->SetVariable('CALENDAR_CLICK',  $property_calendar_click);
				$tpl->SetVariable('CALENDAR_CLICK_TEXT',  $property_calendar_click_text);
				$tpl->SetVariable('PROPERTY_CALENDAR',  $calendar_html);
				
				// Virtual Tour
				if (!empty($page['i360'])) {
					$tpl->SetVariable('tourDisabled', '');
					// TODO: support more virtual tours
					if (strpos(strtolower($page['i360']), "tour.getmytour.com") !== false) {
						if (strpos(strtolower($page['i360']),"tour.getmytour.com/public/vtour") === false) {
							$page['i360'] = str_replace("tour.getmytour.com", "tour.getmytour.com/public/vtour/full", strtolower($page['i360']));
						} else {
							$page['i360'] = str_replace("tour.getmytour.com/public/vtour/display", "tour.getmytour.com/public/vtour/full", strtolower($page['i360']));
						}
						$property_tour_click = "if (this.className.indexOf('disabled') == -1) {if (document.getElementById('media_map')) {document.getElementById('media_map').style.display = 'none';}; if (document.getElementById('email')) {document.getElementById('email').style.display = 'none';}; if (document.getElementById('save')) {document.getElementById('save').style.display = 'none';}; if (document.getElementById('calendar')) {document.getElementById('calendar').style.display = 'none';}; if (document.getElementById('tour')) {var target = document.getElementById('tour'); if (target.style.display == 'none') {target.style.display = '';} else {target.style.display == 'none';};};};";
						$property_tour = '<div id="tour_embed" style="text-align:center;"><iframe id="tour_iframe" width="750" height="300" border="0" frameborder="0" src="'.$page['i360'].'"><br /><a href="'.$GLOBALS['app']->UTF8->str_replace('"', '%22', $page['i360']).'" target="_top">View this tour in a new window.</a><br /><br /><a href="http://getmytour.com" target="_top">Click Here for High Definition Virtual Tours</a><br /></iframe></div><script type="text/javascript">var dim = 750;if (($("tour_embed").parentNode.offsetWidth) && (parseInt($("tour_embed").parentNode.offsetWidth) > 0)) {dim = parseInt($("tour_embed").parentNode.offsetWidth);} else if (parseInt($("tour_embed").offsetWidth) > 0) {dim = parseInt($("tour_embed").offsetWidth);};$("tour_iframe").style.width = dim + "px";</script>';
					} else {
						$property_tour_click = "window.open('".$page['i360']."');";
						$property_tour = "";
					}
					$property_style = "";
				} else {
					$tpl->SetVariable('tourDisabled', '_disabled');
					$property_tour_click = "if (this.className.indexOf('disabled') == -1) {if (document.getElementById('email')) {document.getElementById('email').style.display = 'none';}; if (document.getElementById('media_map')) {document.getElementById('media_map').style.display = 'none';}; if (document.getElementById('save')) {document.getElementById('save').style.display = 'none';}; if (document.getElementById('calendar')) {document.getElementById('calendar').style.display = 'none';}; if (document.getElementById('tour')) {var target = document.getElementById('tour'); if (target.style.display == 'none') {target.style.display = '';} else {target.style.display = 'none';};};};";
					$property_tour = "";
					$property_style = " style=\"display: none;\"";
				}
				$tpl->SetVariable('PROPERTY_TOUR_CLICK',  $property_tour_click);
				$tpl->SetVariable('PROPERTY_TOUR',  $property_tour);
				$tpl->SetVariable('PROPERTY_STYLE',  $property_style);
				
				// Property E-mail Form
				$formsLayout = $GLOBALS['app']->LoadGadget('Forms', 'LayoutHTML');
				$now = $GLOBALS['db']->Date();
				if (strrpos($GLOBALS['app']->GetSiteURL(), "/") > 8) {
					$site_url = substr($GLOBALS['app']->GetSiteURL(), 0, strrpos($GLOBALS['app']->GetSiteURL(), "/"));
				} else {
					$site_url = $GLOBALS['app']->GetSiteURL();		
				}
				$site_name = $GLOBALS['app']->Registry->Get('/config/site_name');
				$site_name = (empty($site_name) ? str_replace('https://', '', str_replace('http://', '', $site_url)) : $site_name);
				//$redirect = $GLOBALS['app']->GetSiteURL() . "/index.php?gadget=".$this->_Name."&action=Product&id=".$page['id'];
				//$redirect = $GLOBALS['app']->GetSiteURL().'/'.$GLOBALS['app']->Map->GetURLFor('Properties', 'Property', array('id' => $page['fast_url']));
				$redirect = $GLOBALS['app']->Map->GetURLFor('Properties', 'Property', array('id' => $page['fast_url']));
				$redirect = (substr($redirect, 0, 4) != 'http' ? $site_url.'/'.$redirect : $redirect);
				/*
				Custom Form implementation
				- Add "__REQUIRED__" to any question title to make the field required
				- Add "__EXTRA_RECIPIENT__" to add the field as a recipient
				- Add "__REDIRECT__" to specify where we are coming from/return URL after form submission
				- Add "__MESSAGE__" to show as a message in the resultant e-mail
				*/	
				if (Jaws_Gadget::IsGadgetUpdated('Social')) {
					$GLOBALS['app']->Translate->LoadTranslation('Social', JAWS_GADGET);
					$socialLayout = $GLOBALS['app']->LoadGadget('Social', 'LayoutHTML');
					$property_email_form = $socialLayout->Display();
				} else {
					$property_email_form = $formsLayout->Display(null, true, array('id' => 'custom', 'sort_order' => 0, 'title' => 'E-mail To A Friend', 
						'sm_description' => '', 'description' => "E-mail this property page to up to 5 of your friends.", 'clause' => '', 
						'image' => '', 'recipient' => '', 'parent' => 0, 'custom_action' => '', 'fast_url' => '', 'active' => 'Y', 
						'ownerid' => 0, 'created' => $now, 'updated' => $now, 
						'submit_content' => "<div style='margin-bottom: 10px;'>Thank you for taking the time to forward this property to your friends!</div><div><a href='".$redirect."'>Click here to return to the Property details page</a>.</div>",
						'checksum' => ''),
						array(array('id' => 9, 'sort_order' => 0, 'formid' => 'custom', 
						'title' => "__MESSAGE__", 'itype' => 'HiddenField', 'required' => 'N', 
						'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''),
						array('id' => 2, 'sort_order' => 1, 'formid' => 'custom', 
						'title' => '__FROM_EMAIL____REQUIRED__', 'itype' => 'TextBox', 'required' => 'Y', 
						'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''),
						array('id' => 1, 'sort_order' => 2, 'formid' => 'custom', 
						'title' => '__FROM_NAME__', 'itype' => 'TextBox', 'required' => 'N', 
						'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''), 
						array('id' => 3, 'sort_order' => 3, 'formid' => 'custom', 
						'title' => "Friend's Email Address 1__EXTRA_RECIPIENT____REQUIRED__", 'itype' => 'TextBox', 'required' => 'Y', 
						'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''),
						array('id' => 4, 'sort_order' => 4, 'formid' => 'custom', 
						'title' => "Friend's Email Address 2__EXTRA_RECIPIENT__", 'itype' => 'TextBox', 'required' => 'N', 
						'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''),
						array('id' => 5, 'sort_order' => 5, 'formid' => 'custom', 
						'title' => "Friend's Email Address 3__EXTRA_RECIPIENT__", 'itype' => 'TextBox', 'required' => 'N', 
						'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''),
						array('id' => 6, 'sort_order' => 6, 'formid' => 'custom', 
						'title' => "Friend's Email Address 4__EXTRA_RECIPIENT__", 'itype' => 'TextBox', 'required' => 'N', 
						'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''),
						array('id' => 7, 'sort_order' => 7, 'formid' => 'custom', 
						'title' => "Friend's Email Address 5__EXTRA_RECIPIENT__", 'itype' => 'TextBox', 'required' => 'N', 
						'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''),
						array('id' => 8, 'sort_order' => 8, 'formid' => 'custom', 
						'title' => "__REDIRECT__", 'itype' => 'HiddenField', 'required' => 'N', 
						'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => '')
						), 
						array(array('id' => 1, 'sort_order' => 0, 'linkid' => 8, 
						'formid' => 'custom', 'title' => "<a href='".$redirect."'>".$redirect."</a>",
						'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''),
						array('id' => 2, 'sort_order' => 1, 'linkid' => 9, 
						'formid' => 'custom', 'title' => "One of your friends thought you might be interested in a property featured on ".$site_name,
						'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => '')
						)
					);
				}
				// Property Inquiry Form
				/*
				Custom Form implementation
				- Add "__REQUIRED__" to any question title to make the field required
				- Add "__EXTRA_RECIPIENT__" to add the field as a recipient
				- Add "__REDIRECT__" to specify where we are coming from/return URL after form submission
				- Add "__MESSAGE__" to show as a message in the resultant e-mail
				*/	
				if (!Jaws_Error::IsError($info) && isset($info['email']) && !empty($info['email'])) {
					$recipient = $info['email'];
				} else if(isset($page['agent_email']) && !empty($page['agent_email'])) {
					$recipient = strip_tags($page['agent_email']);
				} else if(isset($page['broker_email']) && !empty($page['broker_email'])) {
					$recipient = strip_tags($page['broker_email']);
				} else {
					$recipient = '';
				}
				$property_inquiry_form = $formsLayout->Display(null, true, array('id' => 'custom', 'sort_order' => 0, 'title' => 'Property Inquiry', 
					'sm_description' => '', 'description' => "Send us your questions/comments about this property.", 'clause' => '', 
					'image' => '', 'recipient' => $recipient, 'parent' => 0, 'custom_action' => '', 'fast_url' => '', 'active' => 'Y', 
					'ownerid' => 0, 'created' => $now, 'updated' => $now, 
					'submit_content' => "<div style='margin-bottom: 10px;'>Thank you for taking the time to ask us about this property! We'll review your inquiry and get back to you when necessary.</div><div><a href='".$redirect."'>Click here to return to the Property details page</a>.</div>",
					'checksum' => ''),
					array(array('id' => 9, 'sort_order' => 0, 'formid' => 'custom', 
					'title' => "__MESSAGE__", 'itype' => 'HiddenField', 'required' => 'N', 
					'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''),
					array('id' => 2, 'sort_order' => 1, 'formid' => 'custom', 
					'title' => '__FROM_EMAIL____REQUIRED__', 'itype' => 'TextBox', 'required' => 'Y', 
					'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''),
					array('id' => 1, 'sort_order' => 2, 'formid' => 'custom', 
					'title' => '__FROM_NAME__', 'itype' => 'TextBox', 'required' => 'N', 
					'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''), 
					array('id' => 3, 'sort_order' => 3, 'formid' => 'custom', 
					'title' => "Phone", 'itype' => 'TextBox', 'required' => 'N', 
					'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''),
					array('id' => 4, 'sort_order' => 4, 'formid' => 'custom', 
					'title' => "Address", 'itype' => 'TextBox', 'required' => 'N', 
					'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''),
					array('id' => 5, 'sort_order' => 5, 'formid' => 'custom', 
					'title' => "City", 'itype' => 'TextBox', 'required' => 'N', 
					'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''),
					array('id' => 6, 'sort_order' => 6, 'formid' => 'custom', 
					'title' => "State or Province", 'itype' => 'TextBox', 'required' => 'N', 
					'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''),
					array('id' => 7, 'sort_order' => 7, 'formid' => 'custom', 
					'title' => "Zip", 'itype' => 'TextBox', 'required' => 'N', 
					'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''),
					array('id' => 8, 'sort_order' => 8, 'formid' => 'custom', 
					'title' => "__REDIRECT__", 'itype' => 'HiddenField', 'required' => 'N', 
					'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''),
					array('id' => 10, 'sort_order' => 9, 'formid' => 'custom', 
					'title' => "Best Time To Reach", 'itype' => 'RadioBtn', 'required' => 'N', 
					'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''),
					array('id' => 11, 'sort_order' => 10, 'formid' => 'custom', 
					'title' => "Message", 'itype' => 'TextArea', 'required' => 'N', 
					'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => '')
					), 
					array(array('id' => 1, 'sort_order' => 0, 'linkid' => 8, 
					'formid' => 'custom', 'title' => "<a href='".$redirect."'>".$redirect."</a>",
					'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''),
					array('id' => 2, 'sort_order' => 0, 'linkid' => 9, 
					'formid' => 'custom', 'title' => "A message has been received for the following property: ".$page['title'],
					'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''),
					array('id' => 3, 'sort_order' => 0, 'linkid' => 10, 
					'formid' => 'custom', 'title' => "Any",
					'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''),
					array('id' => 4, 'sort_order' => 1, 'linkid' => 10, 
					'formid' => 'custom', 'title' => "Morning",
					'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''),
					array('id' => 5, 'sort_order' => 2, 'linkid' => 10, 
					'formid' => 'custom', 'title' => "Afternoon",
					'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''),
					array('id' => 6, 'sort_order' => 3, 'linkid' => 10, 
					'formid' => 'custom', 'title' => "Evening",
					'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => '')
					)
				);
				
				$tpl->SetVariable('PROPERTY_EMAIL_FORM',  $property_email_form);
				$tpl->SetVariable('PROPERTY_INQUIRY_FORM',  $property_inquiry_form);
				
				$no_details = '';
				if (!empty($page['community']) || !empty($page['city']) || (!empty($page['bedroom']) && $page['bedroom'] > 0) || 
				(!empty($page['bathroom']) && $page['bathroom'] > 0) || (!empty($page['bedroom']) && $page['bedroom'] > 0)) {
					$hasDetails = true;
				}
				if ($hasDetails === false) {
					$no_details = "<style>.property_highlights_bkgnd { display: none; }</style><div style=\"width: 100%; padding: 10px;\">"._t('PROPERTIES_NO_LISTING_DETAILS')."</div>";
				}
				$tpl->SetVariable('NO_LISTING_DETAILS',  $no_details);

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
							$amenity .= ' <nobr><img border="0" style="padding-left: 10px;" src="images/ICON_chkbox.gif">&nbsp;<a href="'.$GLOBALS['app']->getSiteURL().'/index.php?gadget=Properties&action=Category&id=all&amenities='.urlencode($GLOBALS['app']->UTF8->str_replace('"', '%22', strip_tags($amenityParent['feature']))).'">'.$amenityParent['feature'].'</a></nobr>';;
						}
					}
					$tpl->SetBlock('property/content/amenity');
					$tpl->SetVariable('amenity', $amenity);
					$tpl->ParseBlock('property/content/amenity');
				}
				
				// description
				if (isset($page['description']) && !empty($page['description'])) {
					$tpl->SetBlock('property/content/description');
					$tpl->SetVariable('description', strip_tags($this->ParseText($page['description'], 'Properties'), '<p><a><b><em><i><strong><div><table><tbody><thead><tr><td><span><font><hr><br><marquee><ul><li>'));
					$tpl->ParseBlock('property/content/description');
				}
				
				// reservation chart
				if ($rental === true && $calendarDisabled === false) {
					$tpl->SetBlock('property/content/reservation_chart');
					$tpl->ParseBlock('property/content/reservation_chart');
				}
				
				// contact information
				if ((isset($page['agent']) && !empty($page['agent'])) || (isset($page['broker']) && !empty($page['broker'])) || $page['ownerid'] > 0) {
					$user_profile = '';
					// Owner ID details
					if (!Jaws_Error::IsError($info) && isset($info['id'])) {
						if (isset($info['company']) && !empty($info['company'])) {
							$page['broker'] = $info['company'];
						}
						if (isset($info['nickname']) && !empty($info['nickname'])) {
							$page['agent'] = $info['nickname'];
						}							
						if (isset($info['url']) && !empty($info['url'])) {
							$page['agent_website'] = $info['url'];
						}
						if (isset($info['office']) && !empty($info['office'])) {
							$page['broker_phone'] = $info['office'];
						}
						if (isset($info['phone']) && !empty($info['phone'])) {
							$page['agent_phone'] = $info['phone'];
						} else if (isset($info['tollfree']) && !empty($info['tollfree'])) {
							$page['agent_phone'] = $info['tollfree'];
						}
						if (isset($info['logo']) && !empty($info['logo'])) {
							$page['agent_photo'] = $info['logo'];
						}
						// has a public profile page with properties?
						$gadget = $GLOBALS['app']->LoadGadget('Properties', 'HTML');
						if (
							!Jaws_Error::IsError($gadget) && method_exists($gadget, 'account_profile') && 
							in_array('Properties', explode(',', $GLOBALS['app']->Registry->Get('/gadgets/user_access_items')))
						) {
							// Get all groups of user
							$groups  = $jUser->GetGroupsOfUser($info['id']);
							// Check if user's groups match gadget
							$inGroup = false;
							foreach ($groups as $group) {
								if (
									$group['group_name'] == 'profile' && ($group['group_status'] == 'active' || 
									$group['group_status'] == 'founder' || $group['group_status'] == 'admin')
								) {
									$inGroup = true;
									break;
								}
							}
							if ($inGroup === true) {
								$user_profile = $GLOBALS['app']->Map->GetURLFor('Users', 'AccountPublic', array('name' => $info['username']));
							}
						}
					}
					$tpl->SetBlock('property/content/contact');
					
					$agent_html = '';
					if (isset($page['agent']) && !empty($page['agent'])) {
						$agent_html .= '<nobr>Listed by: <b>'.(!empty($user_profile) ? '<a href="'.$user_profile.'">' : ($page['ownerid'] > 0 ? '<a href="index.php?gadget=Properties&action=Category&id=all&owner_id='.$page['ownerid'].'">' : '')).strip_tags($page['agent']).(!empty($user_profile) || $page['ownerid'] > 0 ? '</a>' : '').'</b></nobr>';
					}
					$tpl->SetVariable('agent', $agent_html);
					
					$agent_website = '';
					$agent_website_html = '';
					if (isset($page['agent_website']) && !empty($page['agent_website'])) {
						$agent_website = $GLOBALS['app']->UTF8->str_replace('"', '%22', strip_tags($page['agent_website']));
						$agent_website_html .= '<br /><nobr>Website: <a href="'.$agent_website.'" target="_blank">'.$agent_website.'</a></nobr>';
					} else if (isset($page['broker_website']) && !empty($page['broker_website'])) {
						$agent_website = $GLOBALS['app']->UTF8->str_replace('"', '%22', strip_tags($page['broker_website']));
						$agent_website_html .= '<br /><nobr>Website: <a href="'.$agent_website.'" target="_blank">'.$agent_website.'</a></nobr>';
					}
					$tpl->SetVariable('agent_website', $agent_website_html);
					
					$broker_html = '';
					if (isset($page['broker']) && !empty($page['broker'])) {
						//$broker_html .= ($agent_website != '' ? '<a href="'.$agent_website.'" target="_blank">' : '').strip_tags(str_replace('&nbsp;', ' ', $page['broker'])).($agent_website != '' ? '</a>' : '');
						$broker_html .= '<br />'.($agent_html != '' ? '<nobr>of ' : '<nobr>Listed by: <b>').($agent_website != '' ? '<a href="'.$agent_website.'" target="_blank">' : '').strip_tags(str_replace('&nbsp;', ' ', $page['broker'])).($agent_website != '' ? '</a>' : '').($agent_html != '' ? '' : '</b>').'</nobr>';
					}
					$tpl->SetVariable('broker', $broker_html);
					
					$agent_phone_html = '';
					if (isset($page['agent_phone']) && !empty($page['agent_phone']) && strpos($page['agent_phone'], "://") === false) {
						$agent_phone_html .= '<br /><nobr>Phone: '.strip_tags($page['agent_phone']).'</nobr>';
					} else if (isset($page['broker_phone']) && !empty($page['broker_phone']) && strpos($page['broker_phone'], "://") === false) {
						$agent_phone_html .= '<br /><nobr>Phone: '.strip_tags($page['broker_phone']).'</nobr>';
					}
					$tpl->SetVariable('agent_phone', $agent_phone_html);
					
					$agent_email_html = '';
					if (isset($page['agent_email']) && !empty($page['agent_email'])) {
						$agent_email_html .= '<br /><nobr>E-mail: '.strip_tags($page['agent_email']).'</nobr>';
					} else if (isset($page['broker_email']) && !empty($page['broker_email'])) {
						$agent_email_html .= '<br /><nobr>E-mail: '.strip_tags($page['broker_email']).'</nobr>';
					}
					$tpl->SetVariable('agent_email', $agent_email_html);
					
					$broker_logo_src = '';
					if (!empty($page['broker_logo']) && isset($page['broker_logo'])) {
						$page['broker_logo'] = $xss->parse(strip_tags($page['broker_logo']));
						if (strpos($page['broker_logo'],".swf") !== false) {
							// Flash file not supported
						} else if (substr($page['broker_logo'],0,7) == "GADGET:") {
							$broker_logo_src = $page['broker_logo'];
						} else {
							$broker_logo_src = $page['broker_logo'];
						}
					}
					if (!empty($page['agent_photo']) && isset($page['agent_photo'])) {
						$page['agent_photo'] = $xss->parse(strip_tags($page['agent_photo']));
						if (strpos($page['agent_photo'],".swf") !== false) {
							// Flash file not supported
						} else if (substr($page['agent_photo'],0,7) == "GADGET:") {
							$broker_logo_src = $page['agent_photo'];
						} else {
							$broker_logo_src = $page['agent_photo'];
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
							$broker_logo .= (!empty($user_profile) ? '<a href="'.$user_profile.'">' : ($page['ownerid'] > 0 ? '<a href="index.php?gadget=Properties&action=Category&id=all&owner_id='.$page['ownerid'].'">' : '')).'<img style="padding-right: 10px; padding-bottom: 10px; align="left" border="0" src="'.$broker_logo_src.'" width="100" '.(strtolower(substr($broker_logo_src, -3)) == "gif" || strtolower(substr($broker_logo_src, -3)) == "png" || strtolower(substr($broker_logo_src, -3)) == "bmp" ? 'height="100"' : '').' />'.(!empty($user_profile) || $page['ownerid'] > 0 ? '</a>' : '');				
						}
					}
					$tpl->SetVariable('broker_logo', $broker_logo);
					
					$tpl->ParseBlock('property/content/contact');
				}

				// send Post records
				$posts = $model->GetAllPostsOfProperty($page['id']);
				$carouselNav = '';
				
				if (!Jaws_Error::IsError($posts)) {
					if (!empty($page['image']) && !count($posts) <= 0) {
						reset($posts);
						$carouselItems = '';
						if ($post['action'] == 'PrintPropertyDetails') {
							$tpl->SetBlock('property/content/image_grid');
							$n = 1;
							$mainImage = '<div class="carousel_item" id="carousel_item'.$n.'"><a href="javascript:void(0);" onclick="window.open(\''.$image_src.'\');"><img id="carousel_item'.$n.'Image" class="carousel_itemImage" border="0" src="'.$image_src.'" alt="'.(isset($page['title']) ? $page['title'] : '').'" title="'.(isset($page['title']) ? $page['title'] : '').'"></a></div>';						
							$tpl->SetVariable('mainImage', $mainImage);
							foreach($posts as $post) {		            
								$post_src = '';
								if (isset($post['image']) && !empty($post['image'])) {
									$post['image'] = $xss->parse(strip_tags($post['image']));
									if (substr(strtolower($post['image']), 0, 4) == "http") {
										if (substr(strtolower($post['image']), 0, 7) == "http://") {
											$post_src = explode('http://', $post_src);
											foreach ($post_src as $img_src) {
												if (!empty($img_src)) {
													$post_src = 'http://'.$img_src;
													break;
												}
											}
										} else {
											$post_src = explode('https://', $post['image']);
											foreach ($post_src as $img_src) {
												if (!empty($img_src)) {
													$post_src = 'https://'.$img_src;
													break;
												}
											}
										}
									} else {
										$medium = Jaws_Image::GetMediumPath($post['image']);
										if (file_exists(JAWS_DATA . 'files'.$medium)) {
											$post_src = $GLOBALS['app']->getDataURL() . 'files'.$medium;
										} else if (file_exists(JAWS_DATA . 'files'.$post['image'])) {
											$post_src = $GLOBALS['app']->getDataURL() . 'files'.$post['image'];
										}
									}
									if (!empty($post_src)) {
										$carouselItems .= '<div class="carousel_item" id="carousel_item'.($n+1).'">';
										$carouselItems .= '<a href="javascript:void(0);" onclick="window.open(\''.$post_src.'\');">';
										$carouselItems .= '<img id="carousel_item'.($n+1).'Image" class="carousel_itemImage"';
										if ($post['image_width'] > 0) {
											$carouselItems .= ' width="'.$post['image_width'].'"';
										} else if ($post['image_height'] > 0) {
											$carouselItems .= ' height="'.$post['image_height'].'"';
										}
										$carouselItems .= ' border="0" src="'.$post_src.'" '.(!empty($post['description']) ? 'alt="'.strip_tags($this->ParseText($post['description'], 'Properties')).'" title="'.strip_tags($this->ParseText($post['description'], 'Properties')).'"' : '').' /></a>'.(!empty($post['title']) ? '<div style="text-align: center;">'.$xss->parse(strip_tags($post['title'])).'</div>' : '').'</div>';
										$n++;
									}
								}
							}
							$tpl->SetVariable('carouselItems', $carouselItems);
							$tpl->ParseBlock('property/content/image_grid');
						} else {
							$tpl->SetBlock('property/content/carousel');
							$n = 1;
							$mainImage = '<div class="carousel_item" id="carousel_item'.$n.'"><a href="javascript:void(0);" onclick="window.open(\''.$image_src.'\');"><img id="carousel_item'.$n.'Image" class="carousel_itemImage" border="0" src="'.$image_src.'" alt="'.(isset($page['title']) ? $page['title'] : '').'" title="'.(isset($page['title']) ? $page['title'] : '').'"></a></div>';						
							$tpl->SetVariable('mainImage', $mainImage);
							foreach($posts as $post) {		            
								$post_src = '';
								if (isset($post['image']) && !empty($post['image'])) {
									$post['image'] = $xss->parse(strip_tags($post['image']));
									if (substr(strtolower($post['image']), 0, 4) == "http") {
										if (substr(strtolower($post['image']), 0, 7) == "http://") {
											$post_src = explode('http://', $post_src);
											foreach ($post_src as $img_src) {
												if (!empty($img_src)) {
													$post_src = 'http://'.$img_src;
													break;
												}
											}
										} else {
											$post_src = explode('https://', $post['image']);
											foreach ($post_src as $img_src) {
												if (!empty($img_src)) {
													$post_src = 'https://'.$img_src;
													break;
												}
											}
										}
									} else {
										$medium = Jaws_Image::GetMediumPath($post['image']);
										if (file_exists(JAWS_DATA . 'files'.$medium)) {
											$post_src = $GLOBALS['app']->getDataURL() . 'files'.$medium;
										} else if (file_exists(JAWS_DATA . 'files'.$post['image'])) {
											$post_src = $GLOBALS['app']->getDataURL() . 'files'.$post['image'];
										}
									}
									if (!empty($post_src)) {
										$carouselItems .= '<div class="carousel_item" id="carousel_item'.($n+1).'">';
										$carouselItems .= '<a href="javascript:void(0);" onclick="window.open(\''.$post_src.'\');">';
										$carouselItems .= '<img id="carousel_item'.($n+1).'Image" class="carousel_itemImage"';
										if ($post['image_width'] > 0) {
											$carouselItems .= ' width="'.$post['image_width'].'"';
										} else if ($post['image_height'] > 0) {
											$carouselItems .= ' height="'.$post['image_height'].'"';
										}
										$carouselItems .= ' border="0" src="'.$post_src.'" '.(!empty($post['description']) ? 'alt="'.strip_tags($this->ParseText($post['description'], 'Properties')).'" title="'.strip_tags($this->ParseText($post['description'], 'Properties')).'"' : '').' /></a>'.(!empty($post['title']) ? '<div style="text-align: center;">'.$xss->parse(strip_tags($post['title'])).'</div>' : '').'</div>';
										$carouselNav .= '<a id="carousel_nav'.$n.'" href="javascript: void(0);" onclick="hCarousel.scrollTo('.$n.');" style="text-decoration: none;"><img src="images/carousel_nav_off.png" border="0" /></a>';
										$n++;
									}
								}
							}
							if (strpos(strtolower($page['i360']), "tour.getmytour.com") === false) {
								$tpl->SetVariable('startScroll', "startscroll();");
							}
							$tpl->SetVariable('carouselNav', $carouselNav);
							$tpl->SetVariable('carouselItems', $carouselItems);
							$tpl->ParseBlock('property/content/carousel');
						}
					} else if (!empty($page['image'])) {
						$tpl->SetBlock('property/content/image');
						$tpl->SetVariable('lg_imageSrc', $lg_image_src);
						$tpl->SetVariable('imageSrc', $image_src);
						$tpl->ParseBlock('property/content/image');
					} else {
						if (empty($page['image']) && strpos(strtolower($page['description']), "img") === false) {
							$tpl->SetBlock('property/content/no_image');
							$tpl->ParseBlock('property/content/no_image');
						}
					}
					
				} else {
					//$page_content = _t('PROPERTIES_ERROR_ASPPAGE_NOT_RETRIEVED', $posts->GetMessage())."\n";
					return new Jaws_Error(_t('PROPERTIES_ERROR_ASPPAGE_NOT_RETRIEVED', $posts->GetMessage()), _t('PROPERTIES_NAME'));
				}
				
				// external links
				if (!empty($page['alink']) || !empty($page['alink2']) || !empty($page['alink3'])) {
					$tpl->SetBlock('property/content/external_links');
					if (!empty($page['alink']) && !empty($page['alinktype'])) {
						$alink = '<br /><a href="'.($page['alinktype'] == 'M' ? 'mailto:' : 'http://').$GLOBALS['app']->UTF8->str_replace('"', '%22', strip_tags($page['alink'])).'" target="_blank">'.(!empty($page['alinktitle']) ? strip_tags($page['alinktitle']) : strip_tags($page['alink'])).'</a>';
						$tpl->SetVariable('alink', $alink);
					}
					if (!empty($page['alink2']) && !empty($page['alink2type'])) {
						$alink2 = '<br /><a href="'.($page['alink2type'] == 'M' ? 'mailto:' : 'http://').$GLOBALS['app']->UTF8->str_replace('"', '%22', strip_tags($page['alink2'])).'" target="_blank">'.(!empty($page['alink2title']) ? strip_tags($page['alink2title']) : strip_tags($page['alink2'])).'</a>';
						$tpl->SetVariable('alink2', $alink2);
					}
					if (!empty($page['alink3']) && !empty($page['alink3type'])) {
						$alink2 = '<br /><a href="'.($page['alink3type'] == 'M' ? 'mailto:' : 'http://').$GLOBALS['app']->UTF8->str_replace('"', '%22', strip_tags($page['alink3'])).'" target="_blank">'.(!empty($page['alink3title']) ? strip_tags($page['alink3title']) : strip_tags($page['alink3'])).'</a>';
						$tpl->SetVariable('alink3', $alink3);
					}
					$tpl->ParseBlock('property/content/external_links');
				}
				
				$tpl->SetVariable('pagetype', 'property');
				$tpl->ParseBlock('property/content');
				
				$display_id = md5($this->_Name.$page['id']);
				if ($embedded == true && !is_null($referer)) {	
					$tpl->SetBlock('property/embedded');
					$tpl->SetVariable('id', $display_id);		        
					if (strtolower($referer) == strtolower($_SERVER['SERVER_NAME'])) {
						$tpl->SetVariable('referer', $GLOBALS['app']->GetJawsURL() . "/libraries/iframes/domain1/iframetest_resize1.html");		        
					} else {	
						$tpl->SetVariable('referer', "http://".$referer."/19/img/agents/19/custom_img/common_files/iframetest_resize1.html");		        
					}
					$tpl->ParseBlock('property/embedded');
				} else {
					$tpl->SetBlock('property/not_embedded');
					$tpl->SetVariable('id', $display_id);		        
					$tpl->ParseBlock('property/not_embedded');
				}
			}
		}
		// Statistics Code
		$tpl->SetBlock('property/stats');
		$GLOBALS['app']->Registry->LoadFile('CustomPage');
		$tpl->SetVariable('stats', html_entity_decode($GLOBALS['app']->Registry->Get('/gadgets/CustomPage/googleanalytics_code')));		        
		$tpl->ParseBlock('property/stats');

        $tpl->ParseBlock('property');

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
        $layoutGadget = $GLOBALS['app']->LoadGadget('Properties', 'LayoutHTML');
        return $layoutGadget->Index();
    }

    /**
     * Displays an XML file with the requested maps locations
     *
     * @access public
     * @return string
     */
    function RegionsMapXML($pid = 0)
    {
		header("Content-type: text/xml");
		$output_xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n"; 
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');

		$request =& Jaws_Request::getInstance();
        $get = $request->get(array('pid', 'id'), 'get');

		if(!empty($get['pid'])) {
			$pid = $get['pid'];
		}
		$id = (!empty($get['id']) && $get['id'] != '0' && is_numeric($get['id']) ? (int)$get['id'] : null);
		$searchamenities = $request->get('amenities', 'post');
		if (empty($searchamenities)) {
			$searchamenities = $request->get('amenities', 'get');
		}
		$searchownerid = $request->get('owner_id', 'post');
		if (empty($searchownerid)) {
			$searchownerid = $request->get('owner_id', 'get');
		}
		$searchownerid = (!empty($searchownerid) ? (int)$searchownerid : null);
		$model = $GLOBALS['app']->LoadGadget('Properties', 'Model');
		$mapsmodel = $GLOBALS['app']->LoadGadget('Maps', 'AdminModel');
		$adminmodel = $GLOBALS['app']->LoadGadget('Properties', 'AdminModel');
		$output_xml .= "<markers>\n";
		if ($pid > 11) {
			$properties = $model->GetPropertiesInRegion($pid, $id, $searchownerid);
			if (!Jaws_Error::IsError($properties)) {
				$i = 1;
				foreach ($properties as $parents) {	
					if ($parents['active'] == 'Y' && (!empty($parents['address']) || (!empty($parents['city']) && !empty($parents['region']))) && !empty($parents['coordinates']) && $i < 21) {
						// build address
						$address_region = '';
						$address_city = '';
						$address_address = (isset($parents['address']) ? $parents['address'] : '');
						
						$marker_address = $address_address;
						if (isset($parents['city'])) {
							$address_city = (strpos($address_address, $parents['city']) === false ? " ".$parents['city'] : '');
						}
						$marker_address .= $address_city;
						if (isset($parents['region'])) {
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
						
						$marker_html = '';
						
						/*
						$info_address = $address_address;
						$info_address .= '<br />'.$address_city;
						$info_address .= $address_region;

						$description = '';
						$image_exists = "";
						$image_style = "display: none; ";
						if (isset($parents['description'])) {
							$description = $this->ParseText($parents['description'], 'Properties');
							$description = trim(preg_replace('/\s*\[[^)]*\]/', '', $description));
						}
						if (isset($parents['image'])) {
							$image = $GLOBALS['app']->getDataURL() . 'files'.$parents['image'];
							if (file_exists($image) && substr($image, -5) != "files") {
								$image_exists = "<img border=\"0\" src=\"".$image."\" width=\"150\" />";
								$image_style = "";
							}
						}
						$marker_html .= "<div style=\"".$image_style."clear: left;\">".$image_exists."</div>";
						$marker_html .= "<div style=\"clear: left;\"><b>".(isset($parents['title']) ? $parents['title'] : 'My Location')."</b><br />".$info_address."<hr /><br />".$description."</div>";
						$marker_html .= "<div style=\"clear: both;\">&nbsp;</div>";
						*/
						
						$output_xml .=  "	<marker address=\"\" lnglat=\"".$parents['coordinates']."\" title=\"".$i."\" ext=\"".(isset($parents['title']) ? $parents['title'] : 'My Location')."\" url=\"".$GLOBALS['app']->Map->GetURLFor('Properties', 'Property', array('id' => $parents['fast_url']))."\" target=\"_self\" fs=\"10\" sfs=\"6\" bw=\"2\" ra=\"9\" fc=\"FFFFFF\" fg=\"666666\" bc=\"FFFFFF\" hfc=\"222222\" hfg=\"FFFFFF\" hbc=\"666666\"></marker>\n";
						$i++;
					}
				}
			}
		} else {
			$parents = $mapsmodel->SearchRegions('', $pid, 'country');
			if (Jaws_Error::IsError($parents)) {
				require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
				return Jaws_HTTPError::Get(404);
			}
			foreach ($parents as $parent) {	
				if (isset($parent['region'])) {
					$params2       = array();
					$params2['id'] = $parent['region'];
					$params2['parent'] = $pid;
		
					$sql2 = '
						SELECT [id], [parent], [region], [ownerid], [is_country], 
						[country_iso_code], [latitude], [longitude]
						FROM `country`
						WHERE [region] = {id} AND [parent] = {parent}';
						
					$types2 = array(
						'integer', 'integer', 'text', 'integer', 'text', 
						'text', 'float', 'float'
					);

					$thisRegion = $GLOBALS['db']->queryRow($sql2, $params2, $types2);
					if (!Jaws_Error::IsError($thisRegion)) {
						$params       = array();
						$params['id'] = $thisRegion['id'];
						$params['Active'] = 'Y';
						if ($pid == 0) {
							$sql = '
								SELECT COUNT([id]) FROM [[property]] WHERE [country_id] = {id} AND [active] = {Active}';
							if (!is_null($searchownerid)) {
								$sql .= ' AND [ownerid] = {OwnerID}';
								$params['OwnerID'] = (int)$searchownerid;
							}
							if (!is_null($id)) {
								$sql .= ' AND [linkid] = {LinkID}';
								$params['LinkID'] = (int)$id;
							}
							$country_code = $thisRegion['country_iso_code'];
							//$marker_target = "javascript:void(0);";
							//$marker_url = "xmlUrl='./index.php?gadget=Properties&action=RegionsMapXML&pid=".$thisRegion['id']."';downloadURL();";
						} else {
							/*
							$sql2 = '
								SELECT COUNT([id]) FROM `country` WHERE [parent] = {id}';
							$result2 = $GLOBALS['db']->queryOne($sql2, $params);
							$propCount2 = 0;
							if (!Jaws_Error::IsError($result2)) {
								$propCount2 = $result2;
							}
							if ($propCount2 > 0) {
								$marker_target = "javascript:void(0);";	
								$marker_url = "xmlUrl='./index.php?gadget=Properties&action=RegionsMapXML&pid=".$thisRegion['id']."';downloadURL();";
							} else {
								$marker_target = "_self";	
								$marker_url = "./index.php?gadget=Properties&action=Category&id=all&keyword=".$parent['region'];	
							}
							*/
							$sql = '
								SELECT COUNT([id]) FROM [[property]] WHERE [region] = {id} AND [active] = {Active}';
							$params2       = array();
							$params2['id'] = $pid;
							if (!is_null($searchownerid)) {
								$sql .= ' AND [ownerid] = {OwnerID}';
								$params2['OwnerID'] = (int)$searchownerid;
							}
							if (!is_null($id)) {
								$sql .= ' AND [linkid] = {LinkID}';
								$params2['LinkID'] = (int)$id;
							}
				
							$sql2 = '
								SELECT [id], [parent], [region], [ownerid], [is_country], 
								[country_iso_code], [latitude], [longitude]
								FROM `country`
								WHERE [id] = {id}';
								
							$types2 = array(
								'integer', 'integer', 'text', 'integer', 'text', 
								'text', 'float', 'float'
							);

							$country = $GLOBALS['db']->queryRow($sql2, $params2, $types2);
							if (!Jaws_Error::IsError($country)) {
								if ($thisRegion['parent'] < 12) {
									$country_code = $country['region'];
								} else {
									$country_code = $country['country_iso_code'];
								}
							}
						}
						$marker_target = "_self";	
						if ($pid < 12) {
							$marker_url = "./index.php?gadget=Properties&action=Category&id=".(!is_null($id) ? $id : 'all').(!is_null($searchownerid) ? '&owner_id='.$searchownerid : '')."&country_id=".$thisRegion['id'];	
						} else {
							$marker_url = "./index.php?gadget=Properties&action=Category&id=".(!is_null($id) ? $id : 'all').(!is_null($searchownerid) ? '&owner_id='.$searchownerid : '')."&keyword=".$parent['region'];	
						}
						$marker_url = urlencode($marker_url);
						$result = $GLOBALS['db']->queryOne($sql, $params);
						$propCount = 0;
						if (!Jaws_Error::IsError($result)) {
							$propCount = $result;
						}
						if (($pid == 0) || ($pid != 0) && $propCount > 0) {
							// build address
							if (strpos($parent['region'], " - US") !== false) {
								$parent['region'] = str_replace(" - US", '', $parent['region']);
							}
							if (strpos($parent['region'], " - British") !== false) {
								$parent['region'] = str_replace(" - British", '', $parent['region']);
							}
							if (strpos($parent['region'], " SAR") !== false) {
								$parent['region'] = str_replace(" SAR", '', $parent['region']);
							}
							if (strpos($parent['region'], " - Islas Malvinas") !== false) {
								$parent['region'] = str_replace(" - Islas Malvinas", '', $parent['region']);
							}
							$marker_address = $parent['region'];
							if ($parent['region'] == 'Central America') {
								$marker_address = 'Costa Rica';
							} else if ($parent['region'] == 'South Pacific') {
								$marker_address = 'Kupang';
							}
							if (isset($country_code) && $pid > 0) {
								$marker_address .= ', '.$country_code;
							}
							$description = '';
							//$marker_html = "<div style=\"clear: left;\"><b>".$parent['region']."</b><br />There are ".$propCount." properties in <b>".$parent['region']."</b>.<hr /><br />".$description."</div>";
							//$marker_html .= "<div style=\"clear: both;\">&nbsp;</div>";
							
							usleep(200000);
							
							// Google Maps v2 API Key
							//$key = "ABQIAAAAbHKtlYQg6w5AJ9d2_shprxRSdpoJbNiUEES6uLAQrtqeLOB2WBROsVk6Deve8XT-33SEgoVpReKw5Q";
							// Google Maps v3 API Key
							$key = "AIzaSyC-8bM6FDSqHfs3zEW8S839_3MG4kgh1lc";
							
							include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
							include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'XMLParser.php';
							$coordinates = '';
							
							// snoopy
							$snoopy = new Snoopy('Properties');
							$snoopy->agent = "Jaws";
			
							// Google Maps v2 Geocoding API
							//$geocode_url = "http://maps.google.com/maps/geo?q=".urlencode($marker_address)."&output=xml&key=".$key;
							// Google Maps v3 Geocoding API
							$geocode_url = "https://maps.googleapis.com/maps/api/geocode/xml?address=".urlencode($marker_address)."&sensor=false&key=".$key;
							
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
								$output_xml .=  "	<marker address=\"\" lnglat=\"".$coordinates."\" title=\"".$parent['region']."\" ext=\"".$parent['region']."\" sub=\"".$propCount."%20Properties\" url=\"".$marker_url."\" target=\"".$marker_target."\" fs=\"10\" sfs=\"8\" bw=\"2\" ra=\"9\" fc=\"FFFFFF\" fg=\"77B435\" bc=\"FFFFFF\" hfc=\"222222\" hfg=\"FFFFFF\" hbc=\"77B435\"><![CDATA[ ]]></marker>\n";
							}
						}
					}
				}
			}
		}
		$output_xml .= "</markers>\n";
		return $output_xml;
	}

    /**
     * Displays an XML file with the requested maps locations
     *
     * @access public
     * @return string
     */
    function CitiesMapXML()
    {
		header("Content-type: text/xml");
		$output_xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n"; 
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$model = $GLOBALS['app']->LoadGadget('Properties', 'Model');
		$mapsadmin = $GLOBALS['app']->LoadGadget('Maps', 'AdminModel');
		
		// Google Maps v2 API Key
		//$key = "ABQIAAAAbHKtlYQg6w5AJ9d2_shprxRSdpoJbNiUEES6uLAQrtqeLOB2WBROsVk6Deve8XT-33SEgoVpReKw5Q";
		// Google Maps v3 API Key
		$key = "AIzaSyC-8bM6FDSqHfs3zEW8S839_3MG4kgh1lc";
		
		include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
		include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'XMLParser.php';

		$request =& Jaws_Request::getInstance();
        $get = $request->get(array('pid', 'id'), 'get');
		$pid = $get['pid'];
		$id = (!empty($get['id']) && (int)$get['id'] != 0 && is_numeric($get['id']) ? (int)$get['id'] : null);
		$searchamenities = $request->get('amenities', 'post');
		if (empty($searchamenities)) {
			$searchamenities = $request->get('amenities', 'get');
		}
		$searchownerid = $request->get('owner_id', 'post');
		if (empty($searchownerid)) {
			$searchownerid = $request->get('owner_id', 'get');
		}
		$searchownerid = (!empty($searchownerid) ? (int)$searchownerid : null);
		$output_xml .= "<markers>\n";
		if(!empty($pid)) {
			$parents = $mapsadmin->SearchRegions('', $pid, 'country');
			if (Jaws_Error::IsError($parents)) {
				require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
				return Jaws_HTTPError::Get(404);
			} else {
				foreach ($parents as $parent) {	
					$params2       = array();
					$params2['id'] = $pid;
		
					$sql2 = '
						SELECT [id], [parent], [region], [ownerid], [is_country], 
						[country_iso_code], [latitude], [longitude]
						FROM `country`
						WHERE [id] = {id}';
						
					$types2 = array(
						'integer', 'integer', 'text', 'integer', 'text', 
						'text', 'float', 'float'
					);

					$thisRegion = $GLOBALS['db']->queryRow($sql2, $params2, $types2);
					if (!Jaws_Error::IsError($thisRegion)) {
						if ($thisRegion['parent'] < 12) {
							$country_code = $country['region'];
						} else {
							$country_code = $country['country_iso_code'];
						}
					}
					$params       = array();
					$params['city'] = $parent['region'];
					$params['Active'] = 'Y';
					$sql = '
						SELECT COUNT([id]) FROM [[property]] WHERE [city] = {city} AND [active] = {Active}';
					if (!is_null($searchownerid)) {
						$sql .= ' AND [ownerid] = {OwnerID}';
						$params['OwnerID'] = (int)$searchownerid;
					}
					if (!is_null($id)) {
						$sql .= ' AND [linkid] = {LinkID}';
						$params['LinkID'] = $id;
					}
					$result = $GLOBALS['db']->queryOne($sql, $params);
					$propCount = 0;
					if (!Jaws_Error::IsError($result)) {
						$propCount = $result;
					}
					if (($pid == 0 && isset($parent['region'])) || ($pid != 0 && isset($parent['region']) && $propCount > 0)) {
						// build address
						$marker_address = $parent['region'];
						$address_region = '';
						
						$params2       = array();
						$params2['id'] = $parent['region'];
						$params2['parent'] = $pid;
			
						$sql2 = '
							SELECT [id], [parent], [region], [ownerid], [is_country], 
							[country_iso_code], [latitude], [longitude]
							FROM `country`
							WHERE [region] = {id} AND [parent] = {parent}';
							
						$types2 = array(
							'integer', 'integer', 'text', 'integer', 'text', 
							'text', 'float', 'float'
						);

						$country = $GLOBALS['db']->queryRow($sql2, $params2, $types2);
						if (!Jaws_Error::IsError($country)) {
							if (strpos($country['region'], " - US") !== false) {
								$country['region'] = str_replace(" - US", '', $country['region']);
							}
							if (strpos($country['region'], " - British") !== false) {
								$country['region'] = str_replace(" - British", '', $country['region']);
							}
							if (strpos($country['region'], " SAR") !== false) {
								$country['region'] = str_replace(" SAR", '', $country['region']);
							}
							if (strpos($country['region'], " - Islas Malvinas") !== false) {
								$country['region'] = str_replace(" - Islas Malvinas", '', $country['region']);
							}
							$address_region = ', '.$country['region'];
						}
						$marker_address .= $address_region;
						if (isset($country_code)) {
							$marker_address .= ', '.$country_code;
						}
						$description = '';
						$marker_html = "<div style=\"clear: left;\"><b>".$parent['region']."</b><br />There are ".$propCount." properties in <b>".$parent['region']."</b>.<hr /><br />".$description."</div>";
						$marker_html .= "<div style=\"clear: both;\">&nbsp;</div>";
						$coordinates = '';
						usleep(200000);
						
						// snoopy
						$snoopy = new Snoopy('Properties');
						$snoopy->agent = "Jaws";
							
						// Google Maps v2 Geocoding API
						//$geocode_url = "http://maps.google.com/maps/geo?q=".urlencode($marker_address)."&output=xml&key=".$key;
						// Google Maps v3 Geocoding API
						$geocode_url = "https://maps.googleapis.com/maps/api/geocode/xml?address=".urlencode($marker_address)."&sensor=false&key=".$key;
						
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
							$output_xml .=  "	<marker address=\"\" lnglat=\"".$coordinates."\" title=\"".$parent['region']."\" ext=\"".$parent['region']."\" sub=\"".$propCount."%20Properties%20>>\" url=\"index.php?gadget=Properties&amp;action=Category&amp;id=".(!is_null($id) ? $id : 'all').(!is_null($searchownerid) ? '&amp;owner_id='.$searchownerid : '')."&amp;keyword=".urlencode($parent['region'])."\" target=\"_self\" fs=\"10\" sfs=\"8\" bw=\"2\" ra=\"9\" fc=\"FFFFFF\" fg=\"77B435\" bc=\"FFFFFF\" hfc=\"222222\" hfg=\"FFFFFF\" hbc=\"77B435\"><![CDATA[ ]]></marker>\n";
						}
					}
				}
			}
		} else {
			$adminmodel = $GLOBALS['app']->LoadGadget('Properties', 'AdminModel');
			$galleryPosts = $adminmodel->SearchKeyWithProperties('', '', '', '', '', '', '', $searchownerid, $id, false, 'city', 'ASC', 'city');
			foreach ($galleryPosts as $galleryPost) {	
				/*
				echo '<pre>';
				var_dump($galleryPost);
				echo '</pre>';
				$parent = $mapsadmin->SearchRegions($galleryPost, null, 'country');
				if (!Jaws_Error::IsError($parent) && isset($parent['id'])) {
					$country = $model->GetRegion($parent['parent']);
					if (!Jaws_Error::IsError($country)) {
						$country_code = $country['country_iso_code'];
						$params       = array();
						$params['city'] = $parent['region'];
						$params['region'] = $parent['parent'];
						$sql = '
							SELECT COUNT([id]) FROM [[property]] WHERE [city] = {city} AND [region] = {region}';
						$result = $GLOBALS['db']->queryOne($sql, $params);
						$propCount = 0;
						if (!Jaws_Error::IsError($result)) {
							$propCount = $result;
							if (($propCount > 0)) {
								// build address
								$marker_address = $parent['region'];
								$address_region = '';
								if (isset($country['region'])) {
									if (strpos($country['region'], " - US") !== false) {
										$country['region'] = str_replace(" - US", '', $country['region']);
									}
									if (strpos($country['region'], " - British") !== false) {
										$country['region'] = str_replace(" - British", '', $country['region']);
									}
									if (strpos($country['region'], " SAR") !== false) {
										$country['region'] = str_replace(" SAR", '', $country['region']);
									}
									if (strpos($country['region'], " - Islas Malvinas") !== false) {
										$country['region'] = str_replace(" - Islas Malvinas", '', $country['region']);
									}
									$address_region = ', '.$country['region'];
								}
								$marker_address .= $address_region;
								if (isset($country_code)) {
									$marker_address .= ' '.$country_code;
								}
								*/
								if (strpos($galleryPost[0], " - US") !== false) {
									$galleryPost[0] = str_replace(" - US", '', $galleryPost[0]);
								}
								if (strpos($galleryPost[0], " - British") !== false) {
									$galleryPost[0] = str_replace(" - British", '', $galleryPost[0]);
								}
								if (strpos($galleryPost[0], " SAR") !== false) {
									$galleryPost[0] = str_replace(" SAR", '', $galleryPost[0]);
								}
								if (strpos($galleryPost[0], " - Islas Malvinas") !== false) {
									$galleryPost[0] = str_replace(" - Islas Malvinas", '', $galleryPost[0]);
								}
								$params       = array();
								$params['city'] = substr($galleryPost[0], 0, strpos($galleryPost[0], ','));
								$params['Active'] = 'Y';
								$sql = '
									SELECT COUNT([id]) FROM [[property]] WHERE [city] = {city} AND [active] = {Active}';
								$result = $GLOBALS['db']->queryOne($sql, $params);
								$propCount = 0;
								if (!Jaws_Error::IsError($result)) {
									$propCount = $result;
									if (($propCount > 0)) {
										$marker_address = $galleryPost[0];
										$description = '';
										$marker_html = "<div style=\"clear: left;\"><b>".$marker_address."</b><br />There are ".$propCount." properties in <b>".$marker_address."</b>.<hr /><br />".$description."</div>";
										$marker_html .= "<div style=\"clear: both;\">&nbsp;</div>";
										usleep(200000);
										$coordinates = '';
										
										// snoopy
										$snoopy = new Snoopy('Properties');
										$snoopy->agent = "Jaws";
						
										// Google Maps v2 Geocoding API
										//$geocode_url = "http://maps.google.com/maps/geo?q=".urlencode($marker_address)."&output=xml&key=".$key;
										// Google Maps v3 Geocoding API
										$geocode_url = "https://maps.googleapis.com/maps/api/geocode/xml?address=".urlencode($marker_address)."&sensor=false&key=".$key;
										
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
											$output_xml .=  "	<marker address=\"\" lnglat=\"".$coordinates."\" title=\"".substr($marker_address, 0, strpos($marker_address, ','))."\" ext=\"".$marker_address."\" sub=\"".$propCount."%20Properties%20>>\" url=\"index.php?gadget=Properties&amp;action=Category&amp;id=".(!is_null($id) ? $id : 'all').(!is_null($searchownerid) ? '&amp;owner_id='.$searchownerid : '')."&amp;keyword=".urlencode(substr($marker_address, 0, strpos($marker_address, ',')))."\" target=\"_self\" fs=\"10\" sfs=\"8\" bw=\"2\" ra=\"9\" fc=\"FFFFFF\" fg=\"77B435\" bc=\"FFFFFF\" hfc=\"222222\" hfg=\"FFFFFF\" hbc=\"77B435\"><![CDATA[ ]]></marker>\n";
										}
									}
								}
							/*
					}
				}
				*/
			}
		}
		$output_xml .= "</markers>\n";
		return $output_xml;
	}

    /**
     * Displays an XML file of property categories
     *
     * @access public
     * @return string
     */
    function CategoryMapXML()
    {
		header("Content-type: text/xml");
		$output_xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n"; 
		$output_xml .= "<markers>\n";
		$output_xml .= $this->Category(null, false, null, '', true);
		$output_xml .= "</markers>\n";
		return $output_xml;
	}
    
    /**
     * Displays an XML file of properties
     *
     * @access public
     * @return string
     */
    function PropertyMapXML()
    {
		//header("Content-type: text/xml");
		$output_xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n"; 
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');

		$request =& Jaws_Request::getInstance();
        $get = $request->get(array('id', 'showcase_id'), 'get');

		//if(!empty($post['showcase_id'])) {
		//	$agentID = $post['showcase_id'];
		//}
		  
		if(!empty($get['id'])) {
			$gid = $get['id'];

	        $model = $GLOBALS['app']->LoadGadget('Properties', 'Model');
			$parents = $model->GetProperty($gid);
			if (Jaws_Error::IsError($parents)) {
				require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
				return Jaws_HTTPError::Get(404);
			}
			if (isset($parents['address']) || (isset($parents['city']) && isset($parents['region']))) {
				$output_xml .= "<markers>\n";
				// build address
				$address_region = '';
				$address_city = '';
				$address_address = (isset($parents['address']) ? $parents['address'] : '');
				$marker_address = $address_address;
				if (isset($parents['city'])) {
					$address_city = (strpos($address_address, $parents['city']) === false ? " ".$parents['city'] : '');
				}
				$marker_address .= $address_city;
				if (isset($parents['region'])) {
					$region = $model->GetRegion((int)$parents['region']);
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
					if (!Jaws_Error::IsError($region) && strpos($address_address, $region['region']) === false && strpos($address_address, $region['country_iso_code']) === false) {
						$address_region = ', '.$region['region'];
					}
				}
				$marker_address .= $address_region;
				$info_address = $address_address;
				$info_address .= '<br />'.$address_city;
				$info_address .= $address_region;
				// TODO: map country names to country_id if no other address info was supplied
				$description = '';
				if (isset($parents['description'])) {
					$description = $this->ParseText(substr(strip_tags($parents['description']), 0, 250), 'Properties');
					$description = trim(preg_replace('/\s*\[[^)]*\]/', '', $description));
				}
				
				$main_image_src = '';
				if (!empty($parents['image']) && isset($parents['image'])) {
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
				
				// Geocode if we have to
				if (empty($parents['coordinates'])) {
					// Google Maps v2 API Key
					//$key = "ABQIAAAAbHKtlYQg6w5AJ9d2_shprxRSdpoJbNiUEES6uLAQrtqeLOB2WBROsVk6Deve8XT-33SEgoVpReKw5Q";
					// Google Maps v3 API Key
					$key = "AIzaSyC-8bM6FDSqHfs3zEW8S839_3MG4kgh1lc";
					
					include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
					include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'XMLParser.php';
					
					// snoopy
					$snoopy = new Snoopy('Properties');
					$snoopy->agent = "Jaws";
					
					// Google Maps v2 Geocoding API
					//$geocode_url = "http://maps.google.com/maps/geo?q=".urlencode($marker_address)."&output=xml&key=".$key;
					// Google Maps v3 Geocoding API
					$geocode_url = "https://maps.googleapis.com/maps/api/geocode/xml?address=".urlencode($marker_address)."&sensor=false&key=".$key;
					
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
								if (empty($parents['coordinates']) && isset($xresult['LAT']) && isset($xresult['LNG'])) {
									$parents['coordinates'] = $xresult['LNG'] . ',' . $xresult['LAT'];
								}
							}
						}
					}
				}
				
				$marker_html = "<div style=\"clear: left;\">".$image_exists."<b>".(isset($parents['title']) ? $parents['title'] : 'My Location')."</b><br />".$info_address."<hr /><br />".$description."</div>";
				$marker_html .= "<div style=\"clear: both;\">&nbsp;</div>";
				if (!empty($parents['coordinates'])) {
					$output_xml .=  "	<marker address=\"\" lnglat=\"".$parents['coordinates']."\" ext=\"".(isset($parents['title']) ? $parents['title'] : 'My Location')."\" sub=\"".$marker_address."\" title=\"".(isset($parents['title']) ? $parents['title'] : 'My Location')."\" url=\"\" target=\"infowindow\" fs=\"10\" sfs=\"8\" bw=\"2\" ra=\"9\" fc=\"FFFFFF\" fg=\"666666\" bc=\"FFFFFF\" hfc=\"222222\" hfg=\"FFFFFF\" hbc=\"666666\"><![CDATA[ ".$marker_html." ]]></marker>\n";
				}
			}
			$output_xml .= "</markers>\n";
		} else {
			if (isset($GLOBALS['log'])) {
				$GLOBALS['log']->Log(JAWS_LOG_ERR, "No locations were found for this Properties map.");
			}
		}
		return $output_xml;
	}

    /**
     * Display properties by Amenity.
     *
     * @param 	int 	$aid 	Amenity ID (optional)
     * @access 	public
     * @return 	string
     */
    function Amenity($aid = null)
    {
		$request =& Jaws_Request::getInstance();
        if (is_null($aid)) {
			$get = $request->get(array('id'), 'get');
			$aid = $get['id'];
        }
		return $this->Category(null, false, null, $aid);
    }

    /**
     * Embed property listings in external sites.
     *
     * @category 	feature
     * @access 	public
     * @return 	string
     */
    function EmbedProperty()
    {
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$request =& Jaws_Request::getInstance();
        $get = $request->get(array('id', 'mode', 'uid', 'referer', 'css'), 'get');
		$id = (isset($get['id']) ? (int)$get['id'] : 'all');
		$ownerid = (isset($get['uid']) ? (int)$get['uid'] : '');
        $output_html = "";
		
        //$output_html .= "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
		$output_html .= "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\">\n";
		$output_html .= " <head>\n";
		$output_html .= "  <title>Properties</title>\n";
		$output_html .= "  <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n";
        $theme = $GLOBALS['app']->Registry->Get('/config/theme');
		$themeHREF = (strpos($theme, 'http://') !== false ? $theme : $GLOBALS['app']->getDataURL('', true) . "themes/" . $theme);
		$output_html .= "	<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" href=\"". $themeHREF . "/style.css\" />\n";
		$output_html .= "	<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" href=\"". $GLOBALS['app']->getDataURL('', true) . "files/css/custom.css\" />\n";
		$output_html .= "	<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" href=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/Properties/resources/style.css\" />\n";
		if (isset($get['css'])) {
			$output_html .= "	<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" href=\"".$get['css']."\" />\n";
		}
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/prototype/prototype.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/prototype/scriptaculous.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/prototype/effects.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/prototype/controls.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/include/Jaws/Ajax/Response.js\"></script>\n";
		//$output_html .= "	<script type=\"text/javascript\" src=\"index.php?gadget=Properties&amp;action=Ajax&amp;client\"></script>\n";
		//$output_html .= "	<script type=\"text/javascript\" src=\"index.php?gadget=Properties&amp;action=AjaxCommonFiles\"></script>\n";
		//$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/Properties/resources/client_script.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/js/admin.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"http://yui.yahooapis.com/2.4.0/build/yahoo-dom-event/yahoo-dom-event.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"index.php?gadget=Properties&action=hideGoogleAPIAlerts\"></script>\n";
		$GLOBALS['app']->Registry->LoadFile('Maps');
		//$output_html .= "	<script type=\"text/javascript\" src=\"http://maps.google.com/maps?file=api&v=2&key=".$GLOBALS['app']->Registry->Get('/gadgets/Maps/googlemaps_key')."\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"https://maps.googleapis.com/maps/api/js?v=3&sensor=false&key=".$GLOBALS['app']->Registry->Get('/gadgets/Maps/googlemaps_key')."\"></script>\n";			
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/js/progressbarcontrol.js\"></script>\n";
		//$GLOBALS['app']->Registry->LoadFile('Properties');
		$output_html .= " </head>\n";
		$display_id = md5($this->_Name.$id);
		$output_html .= " <body style=\"background: url();\" onLoad=\"sizeFrame".$display_id."(); document.getElementById('".$this->_Name."-editDivStretch-".$display_id."').style.width = (parseInt(document.getElementById('".$this->_Name."-editDiv-".$display_id."').offsetWidth)-20)+'px';\">\n";
		if ((isset($get['referer']) || $GLOBALS['app']->Session->GetAttribute('gadget_referer'))) {
			$layoutGadget = $GLOBALS['app']->LoadGadget('Properties', 'LayoutHTML');
			$referer = (isset($get['referer']) ? $get['referer'] : $GLOBALS['app']->Session->GetAttribute('gadget_referer'));
			$output_html .= " <style>\n";
			$output_html .= "   #".$this->_Name."-editDiv-".$display_id." { width: 100%; text-align: right; }\n";
			$output_html .= "   #".$this->_Name."-edit-".$display_id." { display: block; width:20px; height:20px; overflow:hidden; }\n";
			$output_html .= "   #".$this->_Name."-edit-".$display_id.":hover { width: 118px; }\n";
			$output_html .= " </style>\n";
			if ($get['mode'] == 'list') {
				$output_html .= " <div id=\"".$this->_Name."-editDiv-".$display_id."\"><div id=\"".$this->_Name."-editDivStretch-".$display_id."\" style=\"float: left;\">&nbsp;</div><div style=\"float: left;\"><a onMouseOver=\"document.getElementById('".$this->_Name."-editDivStretch-".$display_id."').style.width = (parseInt(document.getElementById('".$this->_Name."-editDiv-".$display_id."').offsetWidth)-118)+'px';\" onMouseOut=\"document.getElementById('".$this->_Name."-editDivStretch-".$display_id."').style.width = (parseInt(document.getElementById('".$this->_Name."-editDiv-".$display_id."').offsetWidth)-20)+'px';\" id=\"".$this->_Name."-edit-".$display_id."\" target=\"_blank\" href=\"".$GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction')."\" title=\"Edit This Gadget\"><img border=\"0\" src=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/Users/images/edit_gadget.png\" /></a></div><div style=\"font-size: 0.1em; clear: both;\">&nbsp;</div></div>";
				if (!empty($ownerid)) {
					$output_html .= $layoutGadget->Index($ownerid, true, $referer);
				} else {
					$output_html .= $layoutGadget->Index(null, true, $referer);
				}
			} else if ($get['mode'] == 'calendar') {
				$output_html .= " <div id=\"".$this->_Name."-editDiv-".$display_id."\"><div id=\"".$this->_Name."-editDivStretch-".$display_id."\" style=\"float: left;\">&nbsp;</div><div style=\"float: left;\"><a onMouseOver=\"document.getElementById('".$this->_Name."-editDivStretch-".$display_id."').style.width = (parseInt(document.getElementById('".$this->_Name."-editDiv-".$display_id."').offsetWidth)-118)+'px';\" onMouseOut=\"document.getElementById('".$this->_Name."-editDivStretch-".$display_id."').style.width = (parseInt(document.getElementById('".$this->_Name."-editDiv-".$display_id."').offsetWidth)-20)+'px';\" id=\"".$this->_Name."-edit-".$display_id."\" target=\"_blank\" href=\"".$GLOBALS['app']->GetSiteURL()."/".(!empty($ownerid) ? 'index' : 'admin').".php?gadget=Properties&action=".(!empty($ownerid) ? 'account_' : '')."A&id=".$id."\" title=\"Edit This Gadget\"><img border=\"0\" src=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/Users/images/edit_gadget.png\" /></a></div><div style=\"font-size: 0.1em; clear: both;\">&nbsp;</div></div>";
				$output_html .= $layoutGadget->PropertyCalendar($id, 'LayoutYear', true, $referer, $ownerid);
			} else if ($get['mode'] == 'reservation') {
				$output_html .= " <div id=\"".$this->_Name."-editDiv-".$display_id."\"><div id=\"".$this->_Name."-editDivStretch-".$display_id."\" style=\"float: left;\">&nbsp;</div><div style=\"float: left;\"><a onMouseOver=\"document.getElementById('".$this->_Name."-editDivStretch-".$display_id."').style.width = (parseInt(document.getElementById('".$this->_Name."-editDiv-".$display_id."').offsetWidth)-118)+'px';\" onMouseOut=\"document.getElementById('".$this->_Name."-editDivStretch-".$display_id."').style.width = (parseInt(document.getElementById('".$this->_Name."-editDiv-".$display_id."').offsetWidth)-20)+'px';\" id=\"".$this->_Name."-edit-".$display_id."\" target=\"_blank\" href=\"".$GLOBALS['app']->GetSiteURL()."/".(!empty($ownerid) ? 'index' : 'admin').".php?gadget=Properties&action=".(!empty($ownerid) ? 'account_' : '')."A&id=".$id."\" title=\"Edit This Gadget\"><img border=\"0\" src=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/Users/images/edit_gadget.png\" /></a></div><div style=\"font-size: 0.1em; clear: both;\">&nbsp;</div></div>";
				$output_html .= $layoutGadget->ReservationForm($id, true, $referer, $ownerid);
			} else if ($get['mode'] == 'search') {
				$output_html .= " <div id=\"".$this->_Name."-editDiv-".$display_id."\"><div id=\"".$this->_Name."-editDivStretch-".$display_id."\" style=\"float: left;\">&nbsp;</div><div style=\"float: left;\"><a onMouseOver=\"document.getElementById('".$this->_Name."-editDivStretch-".$display_id."').style.width = (parseInt(document.getElementById('".$this->_Name."-editDiv-".$display_id."').offsetWidth)-118)+'px';\" onMouseOut=\"document.getElementById('".$this->_Name."-editDivStretch-".$display_id."').style.width = (parseInt(document.getElementById('".$this->_Name."-editDiv-".$display_id."').offsetWidth)-20)+'px';\" id=\"".$this->_Name."-edit-".$display_id."\" target=\"_blank\" href=\"".$GLOBALS['app']->GetSiteURL()."/".(!empty($ownerid) ? 'index' : 'admin').".php?gadget=Properties&action=".(!empty($ownerid) ? 'account_' : '')."A&id=".$id."\" title=\"Edit This Gadget\"><img border=\"0\" src=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/Users/images/edit_gadget.png\" /></a></div><div style=\"font-size: 0.1em; clear: both;\">&nbsp;</div></div>";
				$output_html .= $layoutGadget->PropertySearch($id, true, $referer, $ownerid);
			} else if ($get['mode'] == 'globalmap') {
				$output_html .= " <div id=\"".$this->_Name."-editDiv-".$display_id."\"><div id=\"".$this->_Name."-editDivStretch-".$display_id."\" style=\"float: left;\">&nbsp;</div><div style=\"float: left;\"><a onMouseOver=\"document.getElementById('".$this->_Name."-editDivStretch-".$display_id."').style.width = (parseInt(document.getElementById('".$this->_Name."-editDiv-".$display_id."').offsetWidth)-118)+'px';\" onMouseOut=\"document.getElementById('".$this->_Name."-editDivStretch-".$display_id."').style.width = (parseInt(document.getElementById('".$this->_Name."-editDiv-".$display_id."').offsetWidth)-20)+'px';\" id=\"".$this->_Name."-edit-".$display_id."\" target=\"_blank\" href=\"".$GLOBALS['app']->GetSiteURL()."/".(!empty($ownerid) ? 'index' : 'admin').".php?gadget=Properties&action=".(!empty($ownerid) ? 'account_' : '')."A&id=".$id."\" title=\"Edit This Gadget\"><img border=\"0\" src=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/Users/images/edit_gadget.png\" /></a></div><div style=\"font-size: 0.1em; clear: both;\">&nbsp;</div></div>";
				$output_html .= $layoutGadget->GlobalMap($id, true, $referer, '', $ownerid);
			} else if ($get['mode'] == 'citiesmap') {
				$output_html .= " <div id=\"".$this->_Name."-editDiv-".$display_id."\"><div id=\"".$this->_Name."-editDivStretch-".$display_id."\" style=\"float: left;\">&nbsp;</div><div style=\"float: left;\"><a onMouseOver=\"document.getElementById('".$this->_Name."-editDivStretch-".$display_id."').style.width = (parseInt(document.getElementById('".$this->_Name."-editDiv-".$display_id."').offsetWidth)-118)+'px';\" onMouseOut=\"document.getElementById('".$this->_Name."-editDivStretch-".$display_id."').style.width = (parseInt(document.getElementById('".$this->_Name."-editDiv-".$display_id."').offsetWidth)-20)+'px';\" id=\"".$this->_Name."-edit-".$display_id."\" target=\"_blank\" href=\"".$GLOBALS['app']->GetSiteURL()."/".(!empty($ownerid) ? 'index' : 'admin').".php?gadget=Properties&action=".(!empty($ownerid) ? 'account_' : '')."A&id=".$id."\" title=\"Edit This Gadget\"><img border=\"0\" src=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/Users/images/edit_gadget.png\" /></a></div><div style=\"font-size: 0.1em; clear: both;\">&nbsp;</div></div>";
				$output_html .= $layoutGadget->CitiesMap($id, true, $referer, '', $ownerid);
			} else if ($get['mode'] == 'categorymap') {
				$output_html .= " <div id=\"".$this->_Name."-editDiv-".$display_id."\"><div id=\"".$this->_Name."-editDivStretch-".$display_id."\" style=\"float: left;\">&nbsp;</div><div style=\"float: left;\"><a onMouseOver=\"document.getElementById('".$this->_Name."-editDivStretch-".$display_id."').style.width = (parseInt(document.getElementById('".$this->_Name."-editDiv-".$display_id."').offsetWidth)-118)+'px';\" onMouseOut=\"document.getElementById('".$this->_Name."-editDivStretch-".$display_id."').style.width = (parseInt(document.getElementById('".$this->_Name."-editDiv-".$display_id."').offsetWidth)-20)+'px';\" id=\"".$this->_Name."-edit-".$display_id."\" target=\"_blank\" href=\"".$GLOBALS['app']->GetSiteURL()."/".(!empty($ownerid) ? 'index' : 'admin').".php?gadget=Properties&action=".(!empty($ownerid) ? 'account_' : '')."A&id=".$id."\" title=\"Edit This Gadget\"><img border=\"0\" src=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/Users/images/edit_gadget.png\" /></a></div><div style=\"font-size: 0.1em; clear: both;\">&nbsp;</div></div>";
				$output_html .= $layoutGadget->CategoryMap($id, '', $ownerid, true, $referer);
			} else if ($get['mode'] == 'slideshow') {
				$output_html .= " <div id=\"".$this->_Name."-editDiv-".$display_id."\"><div id=\"".$this->_Name."-editDivStretch-".$display_id."\" style=\"float: left;\">&nbsp;</div><div style=\"float: left;\"><a onMouseOver=\"document.getElementById('".$this->_Name."-editDivStretch-".$display_id."').style.width = (parseInt(document.getElementById('".$this->_Name."-editDiv-".$display_id."').offsetWidth)-118)+'px';\" onMouseOut=\"document.getElementById('".$this->_Name."-editDivStretch-".$display_id."').style.width = (parseInt(document.getElementById('".$this->_Name."-editDiv-".$display_id."').offsetWidth)-20)+'px';\" id=\"".$this->_Name."-edit-".$display_id."\" target=\"_blank\" href=\"".$GLOBALS['app']->GetSiteURL()."/".(!empty($ownerid) ? 'index' : 'admin').".php?gadget=Properties&action=".(!empty($ownerid) ? 'account_' : '')."A&id=".$id."\" title=\"Edit This Gadget\"><img border=\"0\" src=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/Users/images/edit_gadget.png\" /></a></div><div style=\"font-size: 0.1em; clear: both;\">&nbsp;</div></div>";
				$output_html .= $layoutGadget->CategorySlideshow($id, true, $referer, $ownerid);
			} else {
				$output_html .= " <div id=\"".$this->_Name."-editDiv-".$display_id."\"><div id=\"".$this->_Name."-editDivStretch-".$display_id."\" style=\"float: left;\">&nbsp;</div><div style=\"float: left;\"><a onMouseOver=\"document.getElementById('".$this->_Name."-editDivStretch-".$display_id."').style.width = (parseInt(document.getElementById('".$this->_Name."-editDiv-".$display_id."').offsetWidth)-118)+'px';\" onMouseOut=\"document.getElementById('".$this->_Name."-editDivStretch-".$display_id."').style.width = (parseInt(document.getElementById('".$this->_Name."-editDiv-".$display_id."').offsetWidth)-20)+'px';\" id=\"".$this->_Name."-edit-".$display_id."\" target=\"_blank\" href=\"".$GLOBALS['app']->GetSiteURL()."/".(!empty($ownerid) ? 'index' : 'admin').".php?gadget=Properties&action=".(!empty($ownerid) ? 'account_' : '')."A&id=".$id."\" title=\"Edit This Gadget\"><img border=\"0\" src=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/Users/images/edit_gadget.png\" /></a></div><div style=\"font-size: 0.1em; clear: both;\">&nbsp;</div></div>";
				$output_html .= $this->Category($id, true, $referer, '', false, $ownerid);
			}
		}
		$output_html .= " </body>\n";
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
					'name' => $this->_Name,
					'id' => str_replace(" ",'',$this->_Name),
					'method' => 'User'.ucfirst(str_replace('_','',str_replace(array('_owners','_users'),'',$group['group_name']))),
					'icon' => $GLOBALS['app']->GetJawsURL() . '/gadgets/'.$this->_Name.'/images/logo.png'
				);
			}
		}
		return $panes;
	}

    /**
     * Allow users (members) to create and subscribe to Properties.
     *
     * @category 	feature
     * @param 	int  $user  User ID
     * @access 	public
     * @return 	string
     */
    function UserProperties($user)
    {			
		if (!$GLOBALS['app']->Session->Logged()) {
			//require_once JAWS_PATH . 'include/Jaws/Header.php';
			//Jaws_Header::Location($GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction'));
			$GLOBALS['app']->Session->PushLastResponse("User not logged in.", RESPONSE_ERROR);
			$GLOBALS['app']->Session->CheckPermission('Users', 'default');
		}
		require_once JAWS_PATH . 'include/Jaws/User.php';
        $jUser = new Jaws_User;
		
		require_once JAWS_PATH . 'include/Jaws/Template.php';
		$tpl = new Jaws_Template('gadgets/Properties/templates/');
		$tpl->Load('users.html');
		$tpl->SetBlock('pane');
		$tpl->SetVariable('title', $this->_Name);
		$tpl->SetVariable('pane_id', str_replace(" ",'',$this->_Name));
		$tpl->SetBlock('pane/pane_item');
		$tpl->SetVariable('pane_id', str_replace(" ",'',$this->_Name));
		$tpl->SetVariable('pane', 'UserProperties');
		$tpl->SetVariable('icon', $GLOBALS['app']->GetJawsURL() . '/gadgets/'.$this->_Name.'/images/logo.png');

		$stpl = new Jaws_Template('gadgets/Properties/templates/');
		$stpl->Load('users.html');
        $stpl->SetBlock('UserPropertiesSubscriptions');
		$status = $jUser->GetStatusOfUserInGroup($GLOBALS['app']->Session->GetAttribute('user_id'), 'properties_owners');
		$usersHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$page = $usersHTML->ShowComments('Properties', false, null, 'Properties', (in_array($status, array('active','admin','founder')) ? true : false));
		if (!Jaws_Error::IsError($page)) {
			$stpl->SetVariable('element', $page);
		} else {
			$stpl->SetVariable('element', _t('GLOBAL_ERROR_GET_ACCOUNT_PANE'));
		}
        $stpl->ParseBlock('UserPropertiesSubscriptions');

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
		$gadget_admin = $GLOBALS['app']->LoadGadget('Properties', 'AdminHTML');
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
		$gadget_admin = $GLOBALS['app']->LoadGadget('Properties', 'AdminHTML');
		$page = $gadget_admin->form(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$html_output = $users_html->GetAccountHTML('Properties');
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
		$gadget_admin = $GLOBALS['app']->LoadGadget('Properties', 'AdminHTML');
		$page = $gadget_admin->form_post(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$output_html = str_replace("__JAWS_GADGET__", $page, $users_html->GetAccountHTML('Properties'));
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
		$gadget_admin = $GLOBALS['app']->LoadGadget('Properties', 'AdminHTML');
		$html_output = str_replace("&nbsp;<input type=\"button\" value=\"Cancel\" onclick=\"if (window.opener && !window.opener.closed) {window.opener.location.reload();window.close();};\">", '', $gadget_admin->A(true));
		//$output_html = str_replace("<td width=\"100%\" align=\"right\">", "<td width=\"50%\" align=\"right\">", $html_output);
		$output_html = $html_output;
		/*
		$page = $gadget_admin->A(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$html_output = str_replace("<body", "<body style=\"background: url();\"", $users_html->GetAccountHTML('Properties'));
		$html_output = str_replace("</head>", "<style type=\"text/css\">body { background: transparent none; }</style>\n</head>", $html_output);
		$output_html = str_replace("__JAWS_GADGET__", $page, $html_output);
		*/
		return $output_html."<style>#layout_CategoryMap_all__title {display: none;}</style>";
	}

    /**
     * Account A_form
     *
     * @access public
     * @return string
     */
    function account_A_form()
    {
		$gadget_admin = $GLOBALS['app']->LoadGadget('Properties', 'AdminHTML');
		$page = $gadget_admin->A_form(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$html_output = $users_html->GetAccountHTML('Properties');
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
		$gadget_admin = $GLOBALS['app']->LoadGadget('Properties', 'AdminHTML');
		$page = $gadget_admin->A_form_post(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$output_html = str_replace("__JAWS_GADGET__", $page, $users_html->GetAccountHTML('Properties'));
		return $output_html;
    }


    /**
     * Account A_form
     *
     * @access public
     * @return string
     */
    function account_A_form2()
    {
		$gadget_admin = $GLOBALS['app']->LoadGadget('Properties', 'AdminHTML');
		$page = $gadget_admin->A_form2(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$html_output = $users_html->GetAccountHTML('Properties');
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
    function account_A_form_post2()
    {
		$gadget_admin = $GLOBALS['app']->LoadGadget('Properties', 'AdminHTML');
		$page = $gadget_admin->A_form_post2(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$output_html = str_replace("__JAWS_GADGET__", $page, $users_html->GetAccountHTML('Properties'));
		return $output_html;
    }

    /**
     * Account A
     *
     * @access public
     * @return string
     */
    function account_B()
    {
		$gadget_admin = $GLOBALS['app']->LoadGadget('Properties', 'AdminHTML');
		$page = $gadget_admin->B(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$html_output = $users_html->GetAccountHTML('Properties');
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
    function account_B_form()
    {
		$gadget_admin = $GLOBALS['app']->LoadGadget('Properties', 'AdminHTML');
		$page = $gadget_admin->B_form(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$html_output = $users_html->GetAccountHTML('Properties');
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
    function account_B_form_post()
    {
		$gadget_admin = $GLOBALS['app']->LoadGadget('Properties', 'AdminHTML');
		$page = $gadget_admin->B_form_post(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$output_html = str_replace("__JAWS_GADGET__", $page, $users_html->GetAccountHTML('Properties'));
		return $output_html;
    }
	
    /**
     * Account A
     *
     * @access public
     * @return string
     */
    function account_B2()
    {
		$gadget_admin = $GLOBALS['app']->LoadGadget('Properties', 'AdminHTML');
		$page = $gadget_admin->B2(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$html_output = $users_html->GetAccountHTML('Properties');
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
    function account_B_form2()
    {
		$gadget_admin = $GLOBALS['app']->LoadGadget('Properties', 'AdminHTML');
		$page = $gadget_admin->B_form2(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$html_output = $users_html->GetAccountHTML('Properties');
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
    function account_B_form_post2()
    {
		$gadget_admin = $GLOBALS['app']->LoadGadget('Properties', 'AdminHTML');
		$page = $gadget_admin->B_form_post2(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$output_html = str_replace("__JAWS_GADGET__", $page, $users_html->GetAccountHTML('Properties'));
		return $output_html;
    }

    /**
     * Account A
     *
     * @access public
     * @return string
     */
    function account_C()
    {
		$gadget_admin = $GLOBALS['app']->LoadGadget('Properties', 'AdminHTML');
		$page = $gadget_admin->C(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$html_output = $users_html->GetAccountHTML('Properties');
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
    function account_C_form()
    {
		$gadget_admin = $GLOBALS['app']->LoadGadget('Properties', 'AdminHTML');
		$page = $gadget_admin->C_form(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$html_output = $users_html->GetAccountHTML('Properties');
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
    function account_C_form_post()
    {
		$gadget_admin = $GLOBALS['app']->LoadGadget('Properties', 'AdminHTML');
		$page = $gadget_admin->C_form_post(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$output_html = str_replace("__JAWS_GADGET__", $page, $users_html->GetAccountHTML('Properties'));
		return $output_html;
    }

    /**
     * Account A
     *
     * @access public
     * @return string
     */
    function account_C2()
    {
		$gadget_admin = $GLOBALS['app']->LoadGadget('Properties', 'AdminHTML');
		$page = $gadget_admin->C2(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$html_output = $users_html->GetAccountHTML('Properties');
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
    function account_C_form2()
    {
		$gadget_admin = $GLOBALS['app']->LoadGadget('Properties', 'AdminHTML');
		$page = $gadget_admin->C_form2(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$html_output = $users_html->GetAccountHTML('Properties');
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
    function account_C_form_post2()
    {
		$gadget_admin = $GLOBALS['app']->LoadGadget('Properties', 'AdminHTML');
		$page = $gadget_admin->C_form_post2(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$output_html = str_replace("__JAWS_GADGET__", $page, $users_html->GetAccountHTML('Properties'));
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
		$gadget_admin = $GLOBALS['app']->LoadGadget('Properties', 'AdminHTML');
		$page = $gadget_admin->GetQuickAddForm(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$output_html = str_replace("__JAWS_GADGET__", $page, $users_html->GetAccountHTML('Properties'));
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
		return $user_admin->ShowEmbedWindow('Properties', 'OwnProperty', true);
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
     * Printable property details.
     *
     * @category 	feature
     * @access 	public
     * @return 	string output html
     * @TODO 	Themable PDF output (of individual and categories)
     */
    function PrintPropertyDetails()
    {
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$request =& Jaws_Request::getInstance();
        $get = $request->get(array('id'), 'get');
		
		$output_html .= "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\">\n";
		$output_html .= " <head>\n";
		$output_html .= "  <title>Print Property Details</title>\n";
		$output_html .= "  <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n";
        $theme = $GLOBALS['app']->Registry->Get('/config/theme');
		$themeHREF = (strpos($theme, 'http://') !== false ? $theme : $GLOBALS['app']->getDataURL('', true) . "themes/" . $theme);
		$output_html .= "	<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" href=\"". $themeHREF . "/style.css\" />\n";
		$output_html .= "	<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" href=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/Properties/resources/style.css\" />\n";
		$output_html .= "	<link rel=\"stylesheet\" media=\"print\" type=\"text/css\" href=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/Properties/resources/print.css\" />\n";
		//$output_html .= "	<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" href=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/carousel/themes/carousel/prototype-ui.css\" />\n";
		$output_html .= "	<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" href=\"". $GLOBALS['app']->getDataURL('', true) . "files/css/custom.css\" />\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/prototype/prototype.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/prototype/scriptaculous.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/prototype/effects.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/prototype/controls.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/include/Jaws/Ajax/Response.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"index.php?gadget=Properties&amp;action=Ajax&amp;client=all&amp;stub=PropertiesAjax\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"index.php?gadget=Properties&action=AjaxCommonFiles\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/Properties/resources/client_script.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/js/admin.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"http://yui.yahooapis.com/2.4.0/build/yahoo-dom-event/yahoo-dom-event.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"index.php?gadget=Properties&action=hideGoogleAPIAlerts\"></script>\n";
		$GLOBALS['app']->Registry->LoadFile('Maps');
		//$output_html .= "	<script type=\"text/javascript\" src=\"http://maps.google.com/maps?file=api&v=2&key=".$GLOBALS['app']->Registry->Get('/gadgets/Maps/googlemaps_key')."\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"https://maps.googleapis.com/maps/api/js?v=3&sensor=false&key=".$GLOBALS['app']->Registry->Get('/gadgets/Maps/googlemaps_key')."\"></script>\n";			
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/js/progressbarcontrol.js\"></script>\n";
		//$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/carousel/dist/carousel.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/js/admin.js\"></script>\n";
		$output_html .= "	<style type=\"text/css\">
		#images #horizontal_carousel {
			width: auto;
			height: auto;
		}
		#images #horizontal_carousel .carousel_container {
			width: auto;
		}
		#images #horizontal_carousel div.carousel_holder {
			height: auto;
		}
		#images #horizontal_carousel div.carousel_holder div.carousel_item {
			visibility: visible;
			width: auto;
			height: auto;
			float: left;
		}
		.carousel_itemImage {
			height: 100px;
			padding: 10px;
		}
		#property_details_container {
			min-width: 0px;
		}
		#propnav_print {
			display: none;
		}
		</style>\n";
		//$GLOBALS['app']->Registry->LoadFile('Properties');
		$output_html .= " </head>\n";
		$output_html .= " <body style=\"background-image: url();\" onLoad=\"sizeFrame".$get['id']."();\">\n";
		if (isset($get['id']) && !empty($get['id'])) {
			$output_html .= $this->Property((int)$get['id']);
		}
		$output_html .= " </body>\n";
		$output_html .= "</html>\n";
		return $output_html;
	}	

	/**
     * Import RSS/Atom property feeds into Properties
     *
     * @category 	feature
	 * @example 	http://southernlakehome.com/index.php?gadget=Properties&action=UpdateRSSProperties&category=16&fetch_url=http%3A%2F%2Fwww.showcasere.com%2F3633%2Fdsp_agent_page.php%2F79614%3Fcommand%3DdspXML&override_city=&rss_url=http%3A%2F%2Fwww.showcasere.com%2F3633%2Fdsp_agent_page.php%2F79614%3Fcommand%3DdspXML&OwnerID=41&num=1&ua=Y
     * @access 	public
     * @return 	HTML string
     */
    function UpdateRSSProperties()
    {		
		ignore_user_abort(true); 
        set_time_limit(0);
		ob_start();
		echo  "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
		echo  "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\">\n";
		echo  " <head>\n";
		//echo  "  <meta http-equiv='refresh' content='10'>";
		echo  "  <title>Update RSS Properties</title>\n";
		echo  " <script language=\"JavaScript\">
<!--
var sURL = '';
function doLoad()
{
    // the timeout value should be the same as in the \"refresh\" meta-tag
    setTimeout( \"refresh()\", 10*1000 );
}

function refresh()
{
    //  This version of the refresh function will cause a new
    //  entry in the visitor's history.  It is provided for
    //  those browsers that only support JavaScript 1.0.
    //
    window.location.href = sURL;
}
//-->
</script>

<script language=\"JavaScript1.1\">
<!--
function refresh()
{
    //  This version does NOT cause an entry in the browser's
    //  page view history.  Most browsers will always retrieve
    //  the document from the web-server whether it is already
    //  in the browsers page-cache or not.
    //  
    window.location.replace( sURL );
}
//-->
</script>

<script language=\"JavaScript1.2\">
<!--
function refresh()
{
    //  This version of the refresh function will be invoked
    //  for browsers that support JavaScript version 1.2
    //
    
    //  The argument to the location.reload function determines
    //  if the browser should retrieve the document from the
    //  web-server.  In our example all we need to do is cause
    //  the JavaScript block in the document body to be
    //  re-evaluated.  If we needed to pull the document from
    //  the web-server again (such as where the document contents
    //  change dynamically) we would pass the argument as 'true'.
    //  
    window.location.reload( false );
}
//-->
</script>";
		echo " <script type='text/javascript'>function submitForm(){if(document.getElementById('property_rss_form')){document.forms['property_rss_form'].submit();};}</script>\n";
		echo  " </head>\n";
		// tag after text for Safari & Firefox
		// 8 char minimum for Firefox
		ob_flush();
		flush();  // worked without ob_flush() for me
		sleep(1);
		$model = $GLOBALS['app']->LoadGadget('Properties', 'Model');
		$adminModel = $GLOBALS['app']->LoadGadget('Properties', 'AdminModel');
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$request =& Jaws_Request::getInstance();
        $id = $request->get('id', 'get');
		if (empty($id)) {
			$id = $request->get('id', 'post');
		}
        
		$user_attended = $request->get('ua', 'get');
		if (empty($user_attended)) {
			$user_attended = $request->get('ua', 'post');
		}
 		//echo '<br />user_attended ::: '.$user_attended;
       
		$searchcategory = $request->get('category', 'get');
		if (empty($searchcategory)) {
			$searchcategory = $request->get('category', 'post');
		}
		//echo '<br />searchcategory ::: '.$searchcategory;
		
		$searchfetch_url = $request->get('fetch_url', 'get');
		if (empty($searchfetch_url)) {
			$searchfetch_url = $request->get('fetch_url', 'post');
		}
		//echo '<br />searchfetch_url ::: '.$searchfetch_url;
		
		$searchoverride_city = $request->get('override_city', 'get');
		if (empty($searchoverride_city)) {
			$searchoverride_city = $request->get('override_city', 'post');
		}
		//echo '<br />searchoverride_city ::: '.$searchoverride_city;
		
		$searchrss_url = $request->get('rss_url', 'get');
		if (empty($searchrss_url)) {
			$searchrss_url = $request->get('rss_url', 'post');
		}
		//echo '<br />searchrss_url ::: '.$searchrss_url;
		
		$searchownerid = $request->get('OwnerID', 'get');
		if (empty($searchownerid)) {
			$searchownerid = $request->get('OwnerID', 'post');
		}
		//echo '<br />searchownerid ::: '.(int)$searchownerid;
		
		$searchnum = $request->get('num', 'get');
		if (empty($searchnum)) {
			$searchnum = $request->get('num', 'post');
		}
		//echo '<br />searchnum ::: '.(int)$searchnum;
		/*
		$output_html .= "  <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"https://ajax.googleapis.com/ajax/libs/prototype/1.6.1/prototype.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"https://ajax.googleapis.com/ajax/libs/scriptaculous/1.8/scriptaculous.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"https://ajax.googleapis.com/ajax/libs/scriptaculous/1.8/effects.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/include/Jaws/Ajax/Response.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"index.php?gadget=Properties&action=Ajax&client\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"index.php?gadget=Properties&action=AjaxCommonFiles\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/Properties/resources/client_script.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/js/admin.js\"></script>\n";
		$output_html .= " </head>\n";
		$output_html .= " <body style=\"background: url();\">\n";
		$output_html .= " <div id=\"msgbox-wrapper\"></div>\n";
		*/
		if (!empty($searchcategory) && !empty($searchfetch_url) && (!empty($searchnum) || (int)$searchnum == 0 || (int)$searchnum == 1) && !empty($user_attended) && $user_attended == 'Y') {
			echo  " <body onload='doLoad(); submitForm();'>\n";
			echo  " <script type=\"text/javascript\">sURL = 'index.php?gadget=Properties&action=UpdateRSSProperties&category=".(int)$searchcategory."&fetch_url=".urlencode($searchfetch_url)."&override_city=".$searchoverride_city."&rss_url=".urlencode($searchrss_url)."&OwnerID=".(int)$searchownerid."&num=".(int)$searchnum."&ua=Y';</script>\n";
			$searchfetch_url = str_replace(' ', '%20', $searchfetch_url);
			$adminModel->InsertRSSProperties((int)$searchcategory, $searchfetch_url, $searchoverride_city, $searchrss_url, (int)$searchownerid, (int)$searchnum, 'Y');
			/*
			if (Jaws_Error::IsError($result)) {
				echo '<br />'.$result->GetMessage();
			}
			*/
		} else if (!empty($id)) {		
			echo  " <body>\n";
			$parent = $model->GetPropertyParent((int)$id);
			if (!Jaws_Error::IsError($parent) && isset($parent['propertyparentid'])) {
				$parent['propertyparentrss_url'] = str_replace(' ', '%20', $parent['propertyparentrss_url']);
				/*
				echo '<br />category ::: '.$parent['propertyparentid'];
				echo '<br />fetch_url ::: '.urlencode($parent['propertyparentrss_url']);
				echo '<br />override_city ::: '.$parent['propertyparentrss_overridecity'];
				echo '<br />rss_url ::: '.urlencode($parent['propertyparentrss_url']);
				echo '<br />OwnerID ::: '.$parent['propertyparentownerid'];
				exit;
				*/
				$adminModel->InsertRSSProperties($parent['propertyparentid'], $parent['propertyparentrss_url'], $parent['propertyparentrss_overridecity'], $parent['propertyparentrss_url'], $parent['propertyparentownerid']);
				//$output_html .= "	<div id=\"insert\"></div><script>Event.observe(window, \"load\",function(){insertRSS(".$parent['propertyparentid'].", '".$parent['propertyparentrss_url']."', '".$parent['propertyparentrss_overridecity']."', '".$parent['propertyparentrss_url']."', ".$parent['propertyparentownerid'].");});</script>";
				/*
				if (Jaws_Error::IsError($result)) {
					echo '<br />'.$result->GetMessage();
				}
				*/
			} else {
				echo '<br />'.$parent->GetMessage();
			}
		} else {
			echo '<br />'.'Property Category not found.';
		}
		echo " </body>\n";
		echo "</html>\n";
		//echo "<script type=\"text/javascript\">location.href='" . BASE_SCRIPT . "';</script>";
		//echo "<h1>Feed Imported Successfully</h1>";
		return true;
	}

	/**
     * Imports RSS/Atom feeds to properties
     *
     * @access public
     * @return HTML string
     */
    function SnoopRSSProperties()
    {		
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$request =& Jaws_Request::getInstance();
        $id = $request->get('id', 'get');
		if (empty($id)) {
			$id = $request->get('id', 'post');
		}
		$output_html = 'Snoopy error';
		if (!empty($id)) {
			$model = $GLOBALS['app']->LoadGadget('Properties', 'Model');
			$parent = $model->GetPropertyParent((int)$id);
			if (!Jaws_Error::IsError($parent) && isset($parent['propertyparentid'])) {
				$output_html = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
				$output_html .= "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\">\n";
				$output_html .= " <head>\n";
				$output_html .= "  <title>Update RSS Properties</title>\n";
				$output_html .= "  <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n";
				$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/prototype/prototype.js\"></script>\n";
				$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/prototype/scriptaculous.js\"></script>\n";
				$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/prototype/effects.js\"></script>\n";
				$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/prototype/controls.js\"></script>\n";
				$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/include/Jaws/Ajax/Response.js\"></script>\n";
				$output_html .= "	<script type=\"text/javascript\" src=\"index.php?gadget=Properties&amp;action=Ajax&amp;client=all&amp;stub=PropertiesAjax\"></script>\n";
				$output_html .= "	<script type=\"text/javascript\" src=\"index.php?gadget=Properties&action=AjaxCommonFiles\"></script>\n";
				$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/Properties/resources/client_script.js\"></script>\n";
				$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/js/admin.js\"></script>\n";
				$output_html .= " </head>\n";
				$output_html .= " <body style=\"background: url();\">\n";
				$output_html .= " <div id=\"msgbox-wrapper\"></div>\n";
				$parent['propertyparentrss_url'] = str_replace(' ', '%20', $parent['propertyparentrss_url']);
				//$adminModel = $GLOBALS['app']->LoadGadget('Properties', 'AdminModel');
				//$result = $adminModel->InsertRSSProperties($parent['propertyparentid'], $parent['propertyparentrss_url'], $parent['propertyparentrss_overridecity'], $parent['propertyparentrss_url'], $parent['propertyparentownerid']);
				$output_html .= "	<div id=\"insert\"></div><script>Event.observe(window, \"load\",function(){insertRSS(".$parent['propertyparentid'].", '".$parent['propertyparentrss_url']."', '".$parent['propertyparentrss_overridecity']."', '".$parent['propertyparentrss_url']."', ".$parent['propertyparentownerid'].");});</script>";
				/*
				if (Jaws_Error::IsError($result)) {
					$output_html = $result->GetMessage();
				} else {
					$output_html = "Feed imported successfully";
				}
				*/
				$output_html .= " </body>\n";
				$output_html .= "</html>\n";
			}
		}
		return $output_html;
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
