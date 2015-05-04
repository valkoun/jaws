<?php
/**
 * Store Gadget
 *
 * @category   Gadget
 * @package    Store
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
ini_set("memory_limit","100M");
ini_set("post_max_size","25M");
ini_set("upload_max_filesize","2M");
ini_set("max_execution_time","5000");
class StoreHTML extends Jaws_GadgetHTML
{
    var $_Name = 'Store';
    /**
     * Constructor
     *
     * @access public
     */
    function StoreHTML()
    {
        $this->Init('Store');
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
     * View products by category.
     *
     * @category 	feature
     * @param	int 	$id 	Categories ID (optional)
     * @param	boolean 	$embedded 	Embedded mode
     * @param	string 	$referer 	Embedding referer
     * @param	string	$searchattributes	Comma delimited list of attributes to match against
     * @param	string	$searchsales	Comma delimited list of sales to match against
     * @param	string	$searchbrand	Comma delimited list of brands to match against
     * @param	string	$searchownerid	Owner IDs to match against
     * @access 	public
     * @return 	string 	HTML template content
     */
    function Category(
		$id = null, $embedded = false, $referer = null, $searchattributes = '', $searchsales = '', 
		$searchbrand = '', $searchownerid = ''
	) {
		require_once JAWS_PATH . 'include/Jaws/Image.php';
        
        $GLOBALS['app']->Layout->AddHeadLink('gadgets/Store/resources/style.css', 'stylesheet', 'text/css');
        $GLOBALS['app']->Layout->AddHeadLink('libraries/autocomplete/autocomplete.css', 'stylesheet', 'text/css', 'default');
        $GLOBALS['app']->Layout->AddScriptLink('libraries/js/global2.js');
        $this->AjaxMe('client_script.js');

		$request =& Jaws_Request::getInstance();
        $post = $request->get(array('id', 'Store_id', 'gadget', 'name'), 'get');

        $GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$pageconst = 12;
		
        $model = $GLOBALS['app']->LoadGadget('Store', 'Model');
        if (is_null($id)) {
			$id = $request->get('id', 'post');
			if (empty($id)) {
				$id = $request->get('id', 'get');
				if (empty($id)) {
					$id = $request->get('Store_id', 'get');
				}
			}
			if (substr($id, 0, 18) == 'searchattribute - ') {
				$searchattributes = str_replace('searchattribute - ', '', $id);
				$id = 'all';
			}
        }
		if (
			empty($id) || 
			(!is_numeric($id) && strtolower($id) == 'all') || 
			!empty($searchattributes) || !empty($searchownerid) || 
			!empty($searchsales) || !empty($searchbrand)
		) {
			$id = 'all';
		}
		if ($id != 'all') {
			$parent = $model->GetProductParent($id);
		}
        if (
			Jaws_Error::IsError($parent) || 
			($id != "all" && empty($searchownerid) && 
				empty($searchattributes) && empty($searchsales) && 
				empty($searchbrand) && !isset($parent['productparentid']))
		) {
			require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
            return Jaws_HTTPError::Get(404);
        } else {
            $tpl = new Jaws_Template('gadgets/Store/templates/');
            $tpl->Load('normal.html');
            $tpl->SetBlock('products');

            /*
			if ($page['productparentactive'] == 'N') {
                $this->SetTitle(_t('STORE_TITLE_NOT_FOUND'));
				$tpl->SetBlock('products/not_found');
                $tpl->SetVariable('content', _t('STORE_CONTENT_NOT_FOUND'));
                $tpl->SetVariable('title', _t('STORE_TITLE_NOT_FOUND'));
                $tpl->ParseBlock('products/not_found');
            } else {
			*/
			
			if (!empty($parent['productparentcategory_name'])) {
				$GLOBALS['app']->Layout->SetTitle($xss->defilter(preg_replace("[^A-Za-z0-9\ ]", '', $parent['productparentcategory_name'])));
			}
			$start = $request->get('start', 'post');
			if (empty($start)) {
				$start = $request->get('start', 'get');
			}
			if (empty($start)) {
				$start = 0;
			}
			$searchkeyword = $request->get('Store_keyword', 'post');
			if (empty($searchkeyword)) {
				$searchkeyword = $request->get('Store_keyword', 'get');
			}
			if (trim($searchbrand) == '') {
				$searchbrand = $request->get('Store_brand', 'post');
				if (empty($searchbrand)) {
					$searchbrand = $request->get('Store_brand', 'get');
				}
			}
			$searchcategory = $request->get('Store_category', 'post');
			if (empty($searchcategory)) {
				$searchcategory = $request->get('Store_category', 'get');
			}
			$preview = $request->get('preview', 'post');
			if (empty($preview)) {
				$preview = $request->get('preview', 'get');
			}
			$sortColumn = $request->get('Store_sortColumn', 'post');
			if (empty($sortColumn)) {
				$sortColumn = $request->get('Store_sortColumn', 'get');
			}
			$sortDir = $request->get('Store_sortDir', 'post');
			if (empty($sortDir)) {
				$sortDir = $request->get('Store_sortDir', 'get');
			}
			$searchgroup = $request->get('Store_group', 'post');
			if (empty($searchgroup)) {
				$searchgroup = $request->get('Store_group', 'get');
			}
			if (empty($sortColumn)) {
				$sortColumn = 'premium';
				$sortDir = 'DESC';
			}
			if (trim($searchattributes) == '') {
				$searchattributes = $request->get('Store_attributes', 'post');
				if (empty($searchattributes)) {
					$searchattributes = $request->get('Store_attributes', 'get');
				}
			}
			if (trim($searchsales) == '') {
				$searchsales = $request->get('Store_sales', 'post');
				if (empty($searchsales)) {
					$searchsales = $request->get('Store_sales', 'get');
				}
			}
			require_once JAWS_PATH . 'include/Jaws/User.php';
			$jUser = new Jaws_User;
			if (strtolower($post['gadget']) == 'users' && isset($post['name']) && !empty($post['name'])) {
				$info  = $jUser->GetUserInfoByName($post['name']);
				if (!isset($info['id'])) {
					$info  = $jUser->GetUserInfoByID((int)$post['name']);
				}
				$searchownerid = $info['id'];
			}
			if (empty($searchownerid)) {
				$searchownerid = $request->get('owner_id', 'post');
				if (empty($searchownerid)) {
					$searchownerid = $request->get('owner_id', 'get');
				}
			}
								
			$tpl->SetBlock('products/content');
			$tpl->SetVariable('pagetype', 'products');

			if ($GLOBALS['app']->Registry->Get('/gadgets/Store/default_display') == 'list') {
				$GLOBALS['app']->Layout->AddHeadOther('<style type="text/css">
				.product_featured_bkgnd, .product_featured_bkgnd_over, .product_bkgnd, .product_bkgnd_over {
					margin-right:15px;
					width:449px;
					float: left;
				}
				.product_cart {
					display: none;
				}
				.product_separator_odd, .product_separator_top_odd, .product_separator_bottom_odd  {
					clear: both;
				}
				.product_separator_odd {
					height: 1px;
				}
				.product_separator_even {
					display: none;
				}
				.product_separator_top_even {
					display: none;
				}
				.product_separator_bottom_even {
					display: none;
				}
				.product_listing_bkgnd {
					height:180px;
					overflow:hidden;
				}
				.product_bkgnd_primary {
					height:160px;
					overflow:hidden;
				}
				.product_highlights {
					height:100px;
					overflow:hidden;				
				}
				</style>');
			}
			
			$c = 0;
			$breadcrumb_start = '<span class="center_nav_font"><span id="center_nav_'.$c.'"><a href="'.$GLOBALS['app']->Map->GetURLFor('Store', 'Category', array('id' => 'all')).'" class="center_nav_link">All Categories</a>&nbsp;&nbsp;';
			if (!empty($searchownerid)) {
				require_once JAWS_PATH . 'include/Jaws/User.php';
				$jUser = new Jaws_User;
				$userInfo = $jUser->GetUserInfoById((int)$searchownerid, true, true, true, true);
				if (!Jaws_Error::isError($userInfo) && !empty($userInfo['id']) && isset($userInfo['id'])) {
					if (file_exists(JAWS_DATA . 'files/css/users/'.$userInfo['id'].'/custom.css')) {
						$GLOBALS['app']->Layout->AddHeadOther('<link rel="stylesheet" media="screen" type="text/css" href="'.$GLOBALS['app']->getDataURL('', true). 'files/css/users/'.$userInfo['id'].'/custom.css" />');
					}
					$c++;
					$name = (!empty($userInfo['company']) ? $userInfo['company'] : (!empty($userInfo['nickname']) ? $userInfo['nickname'] : 'Merchant ID: '.$userInfo['id']));
					$breadcrumb_start .= '>&nbsp;&nbsp;</span><span id="center_nav_'.$c.'"><a href="'.$GLOBALS['app']->getSiteURL().'/index.php?gadget=Store&action=Category&Store_id='.$id.'&Store_sortColumn='.$sortColumn.'&Store_sortDir='.$sortDir.'&Store_brand='.$searchbrand.'&Store_keyword='.$searchkeyword.'&Store_category='.$searchcategory.'&Store_attributes='.$searchattributes.'&start=0&owner_id='.$searchownerid.'" class="center_nav_link">'.$name.'</a>&nbsp;&nbsp;';
				}
			}
			$breadcrumbHTML = '';
			
			if ($id != "all") {
				$tpl->SetVariable('id', $parent['productparentid']);
				$tpl->SetVariable('productparentID', $xss->defilter($parent['productparentid']));
				$tpl->SetVariable('productparentParent', $xss->defilter($parent['productparentparent']));
				$tpl->SetVariable('productparentsort_order', $xss->defilter($parent['productparentsort_order']));
				$tpl->SetVariable('productparentCategory_Name', $xss->defilter(strip_tags($parent['productparentcategory_name'])));
				$main_image_src = '';
				if (!empty($parent['productparentimage']) && isset($parent['productparentimage'])) {
					$parent['productparentimage'] = $xss->parse(strip_tags($parent['productparentimage']));
					if (substr($parent['productparentimage'],0,7) == "GADGET:") {
						$main_image_src = $parent['productparentimage'];
					} else if (substr(strtolower($parent['productparentimage']), 0, 4) == "http") {
						$main_image_src = $parent['productparentimage'];
						if (substr(strtolower($parent['productparentimage']), 0, 7) == "http://") {
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
						if (strpos(strtolower($main_image_src), 'data/files/') !== false) {
							$main_image_src = 'image_thumb.php?uri='.urlencode($main_image_src);
						}
					} else {
						$thumb = Jaws_Image::GetThumbPath($parent['productparentimage']);
						$medium = Jaws_Image::GetMediumPath($parent['productparentimage']);
						if (file_exists(JAWS_DATA . 'files'.$thumb)) {
							$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$thumb;
						} else if (file_exists(JAWS_DATA . 'files'.$medium)) {
							$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$medium;
						} else if (file_exists(JAWS_DATA . 'files'.$parent['productparentimage'])) {
							$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$parent['productparentimage'];
						}						
					}
				}
				if (!empty($main_image_src) && empty($parent['productparentimage_code']) && strpos($main_image_src, 'GADGET:') === false) {
					$image_url = '<a href="javascript:void(0);" onclick="window.open(\''.$main_image_src.'\',\'\',\'scrollbars=no\')">';
					if ((isset($parent['productparenturl']) && !empty($parent['productparenturl'])) && (isset($parent['productparenturl_target']) && !empty($parent['productparenturl_target']))) {
						$image_url = '<a href="'.$xss->defilter($parent['productparenturl']).'" target="'.$xss->defilter($parent['productparenturl_target']).'">';
					}
					$image = '<img style="padding: 5px;" border="0" src="'.$main_image_src.'" width="100" '.(strtolower(substr($main_image_src, -3)) == "gif" || strtolower(substr($main_image_src, -3)) == "png" || strtolower(substr($main_image_src, -3)) == "bmp" ? 'height="100"' : '').' />';				
					$tpl->SetVariable('productparentImage', $image_url.$image.'</a>');
					$tpl->SetVariable('image_style', '');
				} else if (substr($main_image_src, 0, 7) == 'GADGET:' && empty($parent['productparentimage_code'])) {	
					$image_gadget = '';
					// Insert any requested Layout Actions
					$jms = $GLOBALS['app']->LoadGadget('Jms', 'AdminModel');
					$gadget_list = $jms->GetGadgetsList(null, true, true, true);
					$pageAdminModel = $GLOBALS['app']->LoadGadget('CustomPage', 'AdminModel');
					//Hold.. if we dont have a selected gadget?.. like no gadgets?
					if (!count($gadget_list) <= 0) {
						reset($gadget_list);
						foreach ($gadget_list as $gadget) {
							if (strpos($main_image_src, "GADGET:".$gadget['realname']) !== false) {
								$layoutGadget = $GLOBALS['app']->LoadGadget($gadget['realname'], 'LayoutHTML');
								$layoutActions = $pageAdminModel->GetGadgetActions($gadget['realname']);
								if (!Jaws_Error::isError($layoutGadget) && empty($image_gadget)) {
									foreach ($layoutActions as $lactions) {
										$GLOBALS['app']->Registry->LoadFile($gadget['realname']);
										if (strpos($lactions['action'], '(') === false) {
											if (isset($lactions['action'])) {
												if (method_exists($layoutGadget, $lactions['action'])) {
													if (strpos($main_image_src, "__GADGET:".$gadget['realname']."_ACTION:".$lactions['action']."__") !== false) {
														$image_gadget = $layoutGadget->$lactions['action']();
														break;
													}
												}
											} elseif (isset($GLOBALS['log'])) {
												$GLOBALS['log']->Log(JAWS_LOG_ERR, "Action $action in $gadget's LayoutHTML dosn't exist.");
											}
										} else {
											preg_match_all('/^([a-z0-9]+)\((.*?)\)$/i', $lactions['action'], $matches);
											if (isset($matches[1][0]) && isset($matches[2][0])) {
												if (isset($matches[1][0])) {
													if (method_exists($layoutGadget, $matches[1][0])) {
														if (strpos($main_image_src, "__GADGET:".$gadget['realname']."_ACTION:".$matches[1][0].'('.$matches[2][0].')__') !== false) {
															$image_gadget = $layoutGadget->$matches[1][0]($matches[2][0]);
															break;
														}
													}
												} elseif (isset($GLOBALS['log'])) {
													$GLOBALS['log']->Log(JAWS_LOG_ERR, "Action ".$matches[1][0]." in $gadget's LayoutHTML dosn't exist.");
												}
											}
										}
									}
								} else if (!empty($image_gadget)) {
									break;
								} else {
									if (isset($GLOBALS['log'])) {
										$GLOBALS['log']->Log(JAWS_LOG_ERR, $gadget ." is missing the LayoutHTML. Jaws can't execute Layout " .
															 "actions if the file doesn't exists");
									}
								}
								unset($layoutActions);
								unset($layoutGadget);
							}
						}
					}
					$tpl->SetVariable('productparentImage', $image_gadget);
					$tpl->SetVariable('image_style', 'padding: 5px;');
				} else if (!empty($parent['productparentimage_code'])) {	
					$image_code = $this->ParseText($parent['productparentimage_code'], 'Store');
					$image_code = htmlspecialchars_decode($image_code);
					$tpl->SetVariable('productparentImage', $image_code);
					$tpl->SetVariable('image_style', '');
				} else {
					$tpl->SetVariable('productparentImage', '');
					$tpl->SetVariable('image_style', '');
				}
				$tpl->SetVariable('productparentDescription', $this->ParseText($parent['productparentdescription'], 'Store'));
				$tpl->SetVariable('productparentActive', $xss->defilter($parent['productparentactive']));
				$tpl->SetVariable('productparentOwnerID', $xss->defilter($parent['productparentownerid']));
				$tpl->SetVariable('productparentCreated', $xss->defilter($parent['productparentcreated']));
				$tpl->SetVariable('productparentUpdated', $xss->defilter($parent['productparentupdated']));
				$tpl->SetVariable('productparentFeatured', $xss->defilter($parent['productparentfeatured']));
				$tpl->SetVariable('productparentFast_url', $xss->defilter($parent['productparentfast_url']));
				$tpl->SetVariable('productparentRss_url', $xss->defilter($parent['productparentrss_url']));
				$c++;
				$breadcrumbHTML .= '>&nbsp;&nbsp;</span><span id="center_nav_'.$c.'">'.$xss->defilter(strip_tags($parent['productparentcategory_name'])).'&nbsp;&nbsp;';
				/*
				$parentID = $parent['productparentparent'];
				while ($parentID > 0) {
					$grandparent = $model->GetProductParent((int)$parent['productparentparent']);
					if (!Jaws_Error::IsError($grandparent)) {
						$breadcrumbHTML = '>&nbsp;&nbsp;<a href="'.$GLOBALS['app']->Map->GetURLFor('Store', 'Category', array('id' => $grandparent['productparentfast_url'])).'">'.$xss->defilter(strip_tags($grandparent['productparentcategory_name'])).'</a>&nbsp;&nbsp;'.$breadcrumbHTML;
						$parentID = $grandparent['productparentparent'];
					}
				}
				*/
			} else {
				$tpl->SetVariable('id', 'all');
				$tpl->SetVariable('productparentDescription', '');
				$tpl->SetVariable('productparentImage', '');
				$tpl->SetVariable('image_style', '');
			}
			$brandname = '';
			if (!empty($searchbrand)) {
				$brand = $model->GetBrand((int)$searchbrand);
				if (!Jaws_Error::IsError($brand) && isset($brand['id']) && !empty($brand['id'])) {
					if (isset($brand['title']) && !empty($brand['title'])) {
						$brandname = $xss->defilter(strip_tags($brand['title']));
					}
					if (isset($brand['description']) && !empty($brand['description'])) {
						$tpl->SetVariable('productparentDescription', $this->ParseText($brand['description'], 'Store'));
					}
					$brand_image_src = '';
					if (!empty($brand['image']) && isset($brand['image'])) {
						$brand['image'] = $xss->parse(strip_tags($brand['image']));
						if (substr(strtolower($brand['image']), 0, 4) == "http") {
							$brand_image_src = $brand['image'];
							if (substr(strtolower($brand['image']), 0, 7) == "http://") {
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
							if (strpos(strtolower($brand_image_src), 'data/files/') !== false) {
								$brand_image_src = 'image_thumb.php?uri='.urlencode($brand_image_src);
							}
						} else if (substr($brand['image'],0,7) == "GADGET:") {
							$brand_image_src = $brand['image'];
						} else {
							$thumb = Jaws_Image::GetThumbPath($brand['image']);
							$medium = Jaws_Image::GetMediumPath($brand['image']);
							if (file_exists(JAWS_DATA . 'files'.$thumb)) {
								$brand_image_src = $GLOBALS['app']->getDataURL() . 'files'.$thumb;
							} else if (file_exists(JAWS_DATA . 'files'.$medium)) {
								$brand_image_src = $GLOBALS['app']->getDataURL() . 'files'.$medium;
							} else if (file_exists(JAWS_DATA . 'files'.$brand['image'])) {
								$brand_image_src = $GLOBALS['app']->getDataURL() . 'files'.$brand['image'];
							}
						}
					}
					if (!empty($brand_image_src) && empty($brand['image_code']) && strpos($brand_image_src, 'GADGET:') === false) {
						$image_url = '<a href="javascript:void(0);" onclick="window.open(\''.$brand_image_src.'\',\'\',\'scrollbars=no\')">';
						if ((isset($brand['url']) && !empty($brand['url'])) && (isset($brand['url_target']) && !empty($brand['url_target']))) {
							$image_url = '<a href="'.$xss->defilter($brand['url']).'" target="'.$xss->defilter($brand['url_target']).'">';
						}
						$image = '<img style="padding: 5px;" border="0" src="'.$brand_image_src.'" width="100" '.(strtolower(substr($brand_image_src, -3)) == "gif" || strtolower(substr($brand_image_src, -3)) == "png" || strtolower(substr($brand_image_src, -3)) == "bmp" ? 'height="100"' : '').' />';				
						$tpl->SetVariable('productparentImage', $image_url.$image.'</a>');
						$tpl->SetVariable('image_style', '');
					} else if (substr($brand_image_src, 0, 7) == 'GADGET:' && empty($brand['image_code'])) {	
						$image_gadget = '';
						// Insert any requested Layout Actions
						$jms = $GLOBALS['app']->LoadGadget('Jms', 'AdminModel');
						$gadget_list = $jms->GetGadgetsList(null, true, true, true);
						$pageAdminModel = $GLOBALS['app']->LoadGadget('CustomPage', 'AdminModel');
						//Hold.. if we dont have a selected gadget?.. like no gadgets?
						if (!count($gadget_list) <= 0) {
							reset($gadget_list);
							foreach ($gadget_list as $gadget) {
								if (strpos($brand_image_src, "GADGET:".$gadget['realname']) !== false) {
									$layoutGadget = $GLOBALS['app']->LoadGadget($gadget['realname'], 'LayoutHTML');
									$layoutActions = $pageAdminModel->GetGadgetActions($gadget['realname']);
									if (!Jaws_Error::isError($layoutGadget) && empty($image_gadget)) {
										foreach ($layoutActions as $lactions) {
											$GLOBALS['app']->Registry->LoadFile($gadget['realname']);
											if (strpos($lactions['action'], '(') === false) {
												//$this->_Template->SetVariable('ELEMENT', $goGadget->$action());
												if (isset($lactions['action'])) {
													if (method_exists($layoutGadget, $lactions['action'])) {
														if (strpos($brand_image_src, "__GADGET:".$gadget['realname']."_ACTION:".$lactions['action']."__") !== false) {
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
															if (strpos($brand_image_src, "__GADGET:".$gadget['realname']."_ACTION:".$matches[1][0].'('.$matches[2][0].')__') !== false) {
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
						}
						$tpl->SetVariable('productparentImage', $image_gadget);
						$tpl->SetVariable('image_style', 'padding: 5px;');
					} else if (!empty($brand['image_code'])) {	
						$image_code = $this->ParseText($brand['image_code'], 'Store');
						$image_code = htmlspecialchars_decode($image_code);
						$tpl->SetVariable('productparentImage', $image_code);
						$tpl->SetVariable('image_style', '');
					}
				}
			}
			if ($breadcrumbHTML == '') {
				$c++;
				$breadcrumbHTML = '>&nbsp;&nbsp;</span><span id="center_nav_'.$c.'">Searching products '.(!empty($brandname) ? 'that match the brand  <b>"'.$brandname.'"</b> ': '').(!empty($searchkeyword) ? (!empty($searchbrand) ? ' AND ' : ''). 'that match the keyword  <b>"'.str_replace(' - Attribute', '', $searchkeyword).'"</b> ' : '').(!empty($searchattributes) ? (!empty($searchbrand) || !empty($searchkeyword) ? ' AND ' : ''). 'that match attributes:  <b>"'.$searchattributes.'"</b> ' : '');
			}
			$breadcrumbHTML = $breadcrumb_start.$breadcrumbHTML."</span></span>";
			$tpl->SetVariable('BREADCRUMB', $breadcrumbHTML);
			//$tpl->SetVariable('DPATH',  JAWS_DPATH);
			$tpl->SetVariable('JAWS_URL',  $GLOBALS['app']->GetJawsURL() . '/');
			$tpl->SetVariable('HTTP_REFERER',  $GLOBALS['app']->GetSiteURL());
					
			// TODO: Update order 'Active' status via RevisedDate and only show Active
			// send Post records						
			$adminmodel = $GLOBALS['app']->LoadGadget('Store', 'AdminModel');
			$layoutHTML = $GLOBALS['app']->LoadGadget('Store', 'LayoutHTML');
			$searchkeyword = (is_null($searchkeyword) ? '' : $searchkeyword);
			$searchattributes = str_replace('--', ' ', $searchattributes);
			$searchsales = str_replace('--', ' ', $searchsales);
			$searchbrand = str_replace('--', ' ', $searchbrand);
						
			if (trim($searchkeyword) != '') {
				if (strpos($searchkeyword, ' - Attribute') !== false) {
					$searchattributes = str_replace(' - Attribute', '', $searchkeyword);
				}
				if (strpos($searchkeyword, ' - Sales') !== false) {
					$searchsales = str_replace(' - Sales', '', $searchkeyword);
				}
			}
			$sales_ids = '';
			if (trim($searchsales) != '') {
				$res = $adminmodel->SearchSales('Y', $searchsales, null, $OwnerID);
				if (!Jaws_Error::IsError($res)) {
					foreach ($res as $r) {
						$sales_ids .= (!empty($sales_ids) ? ',' : '').$r['id'];
					}
				}
			}
			$attributes_ids = '';
			if (trim($searchattributes) != '') {
				$res = $adminmodel->SearchAttributes($searchattributes, 'Y', null, $OwnerID);
				if (!Jaws_Error::IsError($res)) {
					foreach ($res as $r) {
						$attributes_ids .= (!empty($attributes_ids) ? ',' : '').$r['id'];
					}
				}
			}
			
			$page_cycle = '';
			$full_url = $GLOBALS['app']->GetFullURL();
			if (strpos($full_url, '&start=') !== false) {
				$full_url = substr($full_url, 0, strpos($full_url, '&start=')).substr($full_url, strpos($full_url, '&', strpos($full_url, '&start=')+8), strlen($full_url));
			}
			//if (($id != 'all' && isset($parent['propertyparentrandomize']) && $parent['propertyparentrandomize'] == 'Y') || $id == 'all') {
			$countPosts = 0;
			if (file_exists(JAWS_DATA . 'cache/apps/Store_index_'.md5($full_url))) {
				$countPosts = file_get_contents(JAWS_DATA . 'cache/apps/Store_index_'.md5($full_url));
				$countPosts = ((int)$countPosts)*2;
			}
			if ($countPosts == 0) {
				/*
				$session_id = $GLOBALS['app']->Session->GetAttribute('session_id');
				$string = $session_id;
				$string = preg_replace('#[^\d]+#', '', $string);
				while ((int)$string > $countPosts) {
					$string = substr($string, 0, (strlen($string)-1));
				}
				$seed = ((int)$string)/2;
				*/
				$countPosts = $adminmodel->GetTotalOfMultipleSearchProducts(
					$searchkeyword, $searchbrand, $sales_ids, $searchcategory, $attributes_ids, $searchgroup, $searchownerid, 
					(isset($parent['productparentid']) && !empty($parent['productparentid']) && $id != 'all' ? $parent['productparentid'] : null), 'Y', true
				);
				if (Jaws_Error::IsError($countPosts)) {
					return $countPosts;
				} 
				if (file_exists(JAWS_DATA . 'cache')) {
					if (Jaws_Utils::is_writable(JAWS_DATA . 'cache/apps')) {
						if (!file_put_contents(JAWS_DATA . "cache" . DIRECTORY_SEPARATOR . "apps" . DIRECTORY_SEPARATOR . 'Store_index_'.md5($full_url), ($countPosts/2))) {
							//Jaws_Error::Fatal("Couldn't create cache file.", __FILE__, __LINE__);
						}
					}
				}
			}
			//}
			$posts = $adminmodel->MultipleSearchProducts(
				$searchkeyword, $searchbrand, $sales_ids, $searchcategory, $attributes_ids, $searchgroup, $searchownerid, 
				(isset($parent['productparentid']) && !empty($parent['productparentid']) && $id != 'all' ? $parent['productparentid'] : null), 
				$sortColumn, $sortDir, 'Y', $pageconst, $start, ($GLOBALS['app']->Registry->Get('/gadgets/Store/randomize') == 'Y' ? ($countPosts/2) : ''),
				true
			);
			if (Jaws_Error::IsError($posts)) {
				return $posts;
			}
			if (!$countPosts <= 0) {
				// Pagination
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
							$page_cycle .= '<a href="'.$GLOBALS['app']->getSiteURL().'/index.php?gadget=Store&action=Category&Store_id='.$id.'&Store_sortColumn='.$sortColumn.'&Store_sortDir='.$sortDir.'&Store_brand='.$searchbrand.'&Store_keyword='.$searchkeyword.'&Store_category='.$searchcategory.'&Store_attributes='.$searchattributes.'&start='.($z*$pageconst).'&owner_id='.$searchownerid.'" style="text-decoration:underline;">';
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
				$tpl->SetVariable('PAGE_INDEX', $layoutHTML->Index((isset($parent['productparentid']) ? $parent['productparentid'] : 0)));
				$tpl->SetVariable('PAGE_CYCLE', $page_cycle);
				$tpl->SetVariable('PAGE_CYCLE2', $page_cycle);
				$tpl->SetVariable('NO_PRODUCTS', '');
				
				if ((($start+$pageconst)-1) <= $countPosts) {
					$endcount = ($start+$pageconst)-1;
				} else {
					$endcount = $countPosts;
				}
				
				$i = 1;
				//for ($i=$start;$i<$endcount;$i++) {
				$now = $GLOBALS['db']->Date();
				foreach($posts as $page) {		            
						if (($start % 2) == 0) {
							$evenodd = (($i % 2) == 0 ? 'even' : 'odd');
						} else {
							$evenodd = (($i % 2) == 0 ? 'odd' : 'even');
						}
						if ($GLOBALS['app']->Registry->Get('/gadgets/Store/default_display') == 'list') {
							$tpl->SetBlock('products/content/listproduct');
						} else {
							$tpl->SetBlock('products/content/gridproduct');
						}
						// TODO: Implement Preview mode (use cookie to store length of time the preview is available)				
						$hasDetails = false;
						$tpl->SetVariable('title', $xss->defilter(strip_tags($page['title'])));
						$safe_title = $xss->defilter(strip_tags(ereg_replace("[^A-Za-z0-9]", '', $page['title'])));
						$tpl->SetVariable('safe_title', $safe_title);
						$tpl->SetVariable('num', $i);
						$tpl->SetVariable('id', $page['id']);
						$tpl->SetVariable('LinkID', $id);
						//$product_link = $GLOBALS['app']->GetSiteURL().'/index.php?gadget=Store&action=Product&id='.$page['id'].'&linkid='.($id != 'all' ? $id : '');
						$product_link = $GLOBALS['app']->Map->GetURLFor('Store', 'Product', array('id' => $page['fast_url']));
						$tpl->SetVariable('product_link', $product_link);
						$tpl->SetVariable('sort_order', $xss->defilter($page['sort_order']));
						$category = '';
						if (isset($page['category']) && !empty($page['category'])) {
							$propCategories = explode(',', $page['category']);
							foreach($propCategories as $propCategory) {		            
								$catParent = $model->GetProductParent((int)$propCategory);
								if (!Jaws_Error::IsError($catParent)) {
									if (isset($parent['productparentcategory_name']) && ($parent['productparentcategory_name'] != $catParent['productparentcategory_name'])) {
										if ($hasDetails !== true) {
											$hasDetails = true;
										}
										if ($category != '') {
											$category .= ',';
										}
										$category .= '<A HREF="'.$GLOBALS['app']->Map->GetURLFor('Store', 'Category', array('id' => $catParent['productparentfast_url'])).'"><U><B>'.$catParent['productparentcategory_name'].'</B></U></A>';
									}
								}
							}
						}
						if ($category != '') {
							$category = 'Categories: '.$category;
						}
						$tpl->SetVariable('category', $category);
						$image_src = '';
						if (!empty($page['image']) && isset($page['image'])) {
							$tpl->SetVariable('image', $xss->parse($page['image']));
							$page['image'] = $xss->parse(strip_tags($page['image']));
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
								if (strpos(strtolower($image_src), 'data/files/') !== false) {
									$image_src = 'image_thumb.php?uri='.urlencode($image_src);
								}
							} else {
								$thumb = Jaws_Image::GetThumbPath($page['image']);
								$medium = Jaws_Image::GetMediumPath($page['image']);
								if (file_exists(JAWS_DATA . 'files'.$thumb)) {
									$image_src = $GLOBALS['app']->getDataURL() . 'files'.$thumb;
								} else if (file_exists(JAWS_DATA . 'files'.$medium)) {
									$image_src = $GLOBALS['app']->getDataURL() . 'files'.$medium;
								} else if (file_exists(JAWS_DATA . 'files'.$page['image'])) {
									$image_src = $GLOBALS['app']->getDataURL() . 'files'.$page['image'];
								}
							}
						}
						$sm_description = '';
						if (isset($page['sm_description']) && !empty($page['sm_description'])) {
							$sm_description = '<br /><img border="0" src="images/spacer.gif" height="3" width="1"><img border="0" src="images/ICON_sm_arrow_gray.gif"><img border="0" src="images/spacer.gif" height="3" width="1">'.$xss->defilter(strip_tags($page['sm_description']));
						}
						$tpl->SetVariable('sm_description', $sm_description);
						$product_brandHTML = '';
						if (!empty($page['brandid']) && ($page['brandid'] > 0)) {
							$brandParent = $model->GetBrand($page['brandid']);
							if (!Jaws_Error::IsError($brandParent)) {
								$product_brandHTML = '<br /><img border="0" src="images/spacer.gif" height="3" width="1"><img border="0" src="images/ICON_sm_arrow_gray.gif"><img border="0" src="images/spacer.gif" height="3" width="1">Brand: <a href="'.$GLOBALS['app']->getSiteURL().'/index.php?gadget=Store&action=Category&id=all&brand='.$brandParent['id'].'">'.$xss->defilter(strip_tags($brandParent['title'])).'</a>&nbsp;';
							}
						}
						$tpl->SetVariable('brand', $product_brandHTML);
						$price = 0;
						if (!empty($page['price']) && ($page['price'] > 0)) {
							$price = number_format($page['price'], 2, '.', '');
						}
						// sales
						$sale_price = $price;
						$sale_string = '';
						if (isset($page['sales']) && !empty($page['sales'])) {
							$propSales = explode(',', $page['sales']);
							$saleCount = 0;
							foreach($propSales as $propSale) {		            
								$saleParent = $model->GetSale((int)$propSale);
								if (!Jaws_Error::IsError($saleParent)) {
									if (
										empty($saleParent['coupon_code']) && $saleParent['active'] == 'Y' && 
										($now > $saleParent['startdate'] && $now < $saleParent['enddate'])
									) {
										if ($saleParent['discount_amount'] > 0) {
											$sale_price = number_format($sale_price - number_format($saleParent['discount_amount'], 2, '.', ''), 2, '.', '');
										} else if ($saleParent['discount_percent'] > 0) {
											$sale_price = number_format($sale_price - ($sale_price * ($saleParent['discount_percent'] * .01)), 2, '.', '');
										} else if ($saleParent['discount_newprice'] > 0) {
											$sale_price = number_format($saleParent['discount_newprice'], 2, '.', '');
										}
									}
								}
								$saleCount++;
							}
							if ($sale_price != $price) {
								$sale_string = '<span class="sale_string">SALE</span>&nbsp;$'.($sale_price > 0 ? number_format($sale_price, 2, '.', ',') : 0.00);
							}
						}
						$tpl->SetVariable('sale_price', '&nbsp;'.$xss->defilter($sale_string));
						$price_string = '';
						$sale_display = ' display: none;';
						if ($price > 0) {
							if ($sale_price != $price) {
								$price_string = '<del>$'.number_format($price, 2, '.', ',').'</del>';
								$sale_display = '';
							} else {
								$price_string = '$'.number_format($price, 2, '.', ',');
							}
						}
						$tpl->SetVariable('sale_display', $sale_display);
						$tpl->SetVariable('price', '&nbsp;'.$xss->defilter($price_string));
						$retail = '';
						if (isset($page['retail']) && $page['retail'] > 0) {
							$retail = '&nbsp;<del>$'.number_format($page['retail'], 2, '.', ',').'&nbsp;'._t('STORE_RETAIL').'</del>&nbsp;&nbsp;';
						}
						$tpl->SetVariable('retail', $retail);
						
						$setup_fee = '';
						if (isset($page['setup_fee']) && $page['setup_fee'] > 0) {
							$setup_fee = '&nbsp;'._t('STORE_SETUP_FEE').':&nbsp;$'.number_format($page['setup_fee'], 2, '.', ',').'&nbsp;&nbsp;';
						}
						$tpl->SetVariable('setup_fee', $setup_fee);
						
						$weight = '';
						/*
						if (isset($page['weight']) && $page['weight'] != 0) {
							$weight = '<br /><img border="0" src="images/spacer.gif" height="3" width="1"><img border="0" src="images/ICON_sm_arrow_gray.gif"><img border="0" src="images/spacer.gif" height="3" width="1">Weight: '.$xss->defilter(strip_tags($page['weight'])).'&nbsp;lbs.';
						}
						*/
						$tpl->SetVariable('weight', $weight);
						
						$unit = '';
						if (isset($page['unit']) && !empty($page['unit']) && $price > 0) {
							$unit = $xss->defilter(strip_tags($page['unit']));
						}
						$tpl->SetVariable('unit', $unit);
						
						$product_code = '';
						/*
						if (isset($page['product_code']) && !empty($page['product_code'])) {
							$product_code = '<br /><img border="0" src="images/spacer.gif" height="3" width="1"><img border="0" src="images/ICON_sm_arrow_gray.gif"><img border="0" src="images/spacer.gif" height="3" width="1">Product Code: '.$xss->defilter(strip_tags($page['product_code']));
						}
						*/
						$tpl->SetVariable('product_code', $product_code);
						
						$tpl->SetVariable('premium', $xss->defilter($page['premium']));
						$tpl->SetVariable('featured', $xss->defilter($page['featured']));
						$tpl->SetVariable('OwnerID', $xss->defilter($page['ownerid']));
						$tpl->SetVariable('Active', $xss->defilter($page['active']));
						$tpl->SetVariable('Created', $xss->defilter($page['created']));
						$tpl->SetVariable('Updated', $xss->defilter($page['updated']));
						$tpl->SetVariable('fast_url', $xss->defilter($page['fast_url']));
						$tpl->SetVariable('internal_productno', $xss->defilter(strip_tags($page['internal_productno'])));
						$tpl->SetVariable('rss_url', $xss->defilter(strip_tags($page['rss_url'])));
						
						// contact information
						$user_profile = '';
						// Owner ID details
						if ((int)$page['ownerid'] > 0) {
							$info = $jUser->GetUserInfoById((int)$page['ownerid'], true, true, true, true);
							if (!Jaws_Error::IsError($info) && isset($info['id'])) {
								if (isset($info['company']) && !empty($info['company'])) {
									$page['contact'] = $info['company'];
								} else if (isset($info['nickname']) && !empty($info['nickname'])) {
									$page['contact'] = $info['nickname'];
								}							
								if (isset($info['url']) && !empty($info['url'])) {
									$page['contact_website'] = $info['url'];
								}
								if (isset($info['office']) && !empty($info['office'])) {
									$page['contact_phone'] = $info['office'];
								} else if (isset($info['phone']) && !empty($info['phone'])) {
									$page['contact_phone'] = $info['phone'];
								} else if (isset($info['tollfree']) && !empty($info['tollfree'])) {
									$page['contact_phone'] = $info['tollfree'];
								}
								if (isset($info['logo']) && !empty($info['logo'])) {
									$page['company_logo'] = $info['logo'];
								}
								// has a public profile page with products?
								$gadget = $GLOBALS['app']->LoadGadget('Store', 'HTML');
								if (
									!Jaws_Error::IsError($gadget) && method_exists($gadget, 'account_profile') && 
									in_array('Store', explode(',', $GLOBALS['app']->Registry->Get('/gadgets/user_access_items')))
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
						if (isset($page['contact']) && !empty($page['contact'])) {
							$agent_html .= '<nobr>More from: <b>'.(!empty($user_profile) ? '<a href="'.$user_profile.'">' : ($page['ownerid'] > 0 ? '<a href="index.php?gadget=Store&action=Category&id=all&owner_id='.$page['ownerid'].'">' : '')).$xss->defilter(strip_tags($page['contact'])).(!empty($user_profile) || $page['ownerid'] > 0 ? '</a>' : '').'</b></nobr>';
						}
						$tpl->SetVariable('contact', $agent_html);

						$tpl->SetVariable('contact_email', $xss->defilter(strip_tags($page['contact_email'])));
						$tpl->SetVariable('contact_phone', $xss->defilter(strip_tags($page['contact_phone'])));
						$tpl->SetVariable('contact_website', $xss->defilter(strip_tags($page['contact_website'])));
						$tpl->SetVariable('contact_photo', $xss->defilter(strip_tags($page['contact_photo'])));
						$broker_html = '';
						if (isset($page['company']) && !empty($page['company'])) {
							$broker_html .= ($agent_html != '' ? '<nobr>&nbsp;of ' : '<nobr>&nbsp;<b>').$xss->defilter(strip_tags(str_replace('&nbsp;', ' ', $page['company']))).($agent_html != '' ? '' : '</b>').'</nobr>';
						}
						$tpl->SetVariable('company', $broker_html);

						$tpl->SetVariable('company_email', $xss->defilter(strip_tags($page['company_email'])));
						$tpl->SetVariable('company_phone', $xss->defilter(strip_tags($page['company_phone'])));
						$tpl->SetVariable('company_website', $xss->defilter(strip_tags($page['company_website'])));
						$broker_logo_src = '';
						if (!empty($page['company_logo']) && isset($page['company_logo'])) {
							$page['company_logo'] = $xss->defilter(strip_tags($page['company_logo']));
							if (strpos($page['company_logo'],".swf") !== false) {
								// Flash file not supported
							} else if (substr($page['company_logo'],0,7) == "GADGET:") {
								$broker_logo_src = $page['company_logo'];
							} else {
								$broker_logo_src = $page['company_logo'];
							}
						}
						if (!empty($page['contact_photo']) && isset($page['contact_photo'])) {
							$page['contact_photo'] = $xss->defilter(strip_tags($page['contact_photo']));
							if (strpos($page['contact_photo'],".swf") !== false) {
								// Flash file not supported
							} else if (substr($page['contact_photo'],0,7) == "GADGET:") {
								$broker_logo_src = $page['contact_photo'];
							} else {
								$broker_logo_src = $page['contact_photo'];
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
								if (strpos(strtolower($broker_logo), 'data/files/') !== false) {
									$broker_logo = 'image_thumb.php?uri='.urlencode($broker_logo);
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
								$broker_logo .= (!empty($user_profile) ? '<a href="'.$user_profile.'">' : ($page['ownerid'] > 0 ? '<a href="index.php?gadget=Store&action=Category&id=all&owner_id='.$page['ownerid'].'">' : '')).'<img style="padding-left: 5px; padding-bottom: 5px; align="right" border="0" src="'.$broker_logo_src.'" width="100" '.(strtolower(substr($broker_logo_src, -3)) == "gif" || strtolower(substr($broker_logo_src, -3)) == "png" || strtolower(substr($broker_logo_src, -3)) == "bmp" ? 'height="100"' : '').' />'.(!empty($user_profile) || $page['ownerid'] > 0 ? '</a>' : '');				
							}
						}
						$tpl->SetVariable('contact_photo', $broker_logo);
						
						$tpl->SetVariable('alink', $xss->defilter(strip_tags($page['alink'])));
						$tpl->SetVariable('alinkTitle', $xss->defilter(strip_tags($page['alinktitle'])));
						$tpl->SetVariable('alinkType', $xss->defilter(strip_tags($page['alinktype'])));
						$tpl->SetVariable('alink2', $xss->defilter(strip_tags($page['alink2'])));
						$tpl->SetVariable('alink2Title', $xss->defilter(strip_tags($page['alink2title'])));
						$tpl->SetVariable('alink2Type', $xss->defilter(strip_tags($page['alink2type'])));
						$tpl->SetVariable('alink3', $xss->defilter(strip_tags($page['alink3'])));
						$tpl->SetVariable('alink3Title', $xss->defilter(strip_tags($page['alink3title'])));
						$tpl->SetVariable('alink3type', $xss->defilter(strip_tags($page['alink3type'])));

						// Add to Cart
						$productDisabled = '';
						$product_cartHTML = '';
						/*
						if ($page['inventory'] == 'Y' && (($page['lowstock'] > $page['instock']) && $page['outstockbuy'] == 'N')) {
							$productDisabled = ' product_disabled';
							$product_cartHTML .= "<div class=\"outstockmsg\">";
							if (isset($page['outstockmsg']) && !empty($page['outstockmsg'])) {
								$product_cartHTML .= $page['outstockmsg'];
							} else {
								$product_cartHTML .= 'This product is sold out. Check back soon.';
							}
							$product_cartHTML .= "</div>";
						} else */if (Jaws_Gadget::IsGadgetUpdated('Ecommerce')) {
							$GLOBALS['app']->Registry->LoadFile('Ecommerce');
							$GLOBALS['app']->Translate->LoadTranslation('Ecommerce', JAWS_GADGET);
							$ecommerce_layout = $GLOBALS['app']->LoadGadget('Ecommerce', 'LayoutHTML');
							$cart_button = $ecommerce_layout->ShowSmallCartButton($page['id']);
							if (!Jaws_Error::IsError($cart_button)) {
								$product_cartHTML .= $cart_button;
							}
						}
						
						$tpl->SetVariable('ADD_TO_CART',  $product_cartHTML);

						// Product Header
						$product_headerHTML = '';
						if ($page['premium'] == 'Y') {
							$product_headerHTML = '<div align="center" id="product_featured_bkgnd_'.$page['id'].'" class="product_featured_bkgnd'.$productDisabled.' product_bkgnd_'.$evenodd.'" onMouseOver="this.className = \'product_featured_bkgnd_over'.$productDisabled.' product_bkgnd_'.$evenodd.'\';" onMouseOut="this.className = \'product_featured_bkgnd'.$productDisabled.' product_bkgnd_'.$evenodd.'\';"><center><table BORDER="0" cellpadding="3" cellspacing="0" width="100%"><tr><td valign="middle" width="0%"><img border="0" src="images/propnav_feat_spotlight.gif"></td><td width="100%"><div align="center" class="product_featured_listing_bkgnd">';
						} else {
							$product_headerHTML = '<div align="center" id="product_bkgnd_'.$page['id'].'" class="product_bkgnd'.$productDisabled.' product_bkgnd_'.$evenodd.'" onMouseOver="this.className = \'product_bkgnd_over'.$productDisabled.' product_bkgnd_'.$evenodd.'\';" onMouseOut="this.className = \'product_bkgnd'.$productDisabled.' product_bkgnd_'.$evenodd.'\';"><center><table border="0" cellpadding="3" cellspacing="0" width="100%"><tr><td width="100%"><div align="center">';
						}
						$tpl->SetVariable('product_header',  $product_headerHTML);
										
						//$tpl->SetVariable('DPATH',  JAWS_DPATH);
						$tpl->SetVariable('JAWS_URL',  $GLOBALS['app']->GetJawsURL() . '/');
						$tpl->SetVariable('HTTP_REFERER',  $GLOBALS['app']->GetSiteURL());
						
						$no_details = '';
						if (!empty($page['description']) || !empty($product_brandHTML) || !empty($page['product_code']) || !empty($page['sm_description']) || (!empty($page['weight']) && $page['weight'] != 0) || !empty($product_cartHTML)) {
							$hasDetails = true;
						}
						
						if ($hasDetails === false) {
							$GLOBALS['app']->Layout->AddHeadOther("<style>#product_highlights_".$page['id']." { display: none; }</style>");
							$no_details = "<div style=\"width: 100%; padding: 10px;\">"._t('STORE_NO_LISTING_DETAILS')."</div>";
						}
						$tpl->SetVariable('NO_LISTING_DETAILS',  $no_details);

						// attribute
						$amenity = '';
						if (isset($page['attribute']) && !empty($page['attribute'])) {
							$propAmenities = explode(',', $page['attribute']);
							$amenityCount = 0;
							foreach($propAmenities as $propAmenity) {		            
								if ($amenityCount < 8) {
									$amenityParent = $model->GetAttribute((int)$propAmenity);
									if (!Jaws_Error::IsError($amenityParent)) {
										$amenityType = $model->GetAttributeType($amenityParent['typeid']);
										if (!Jaws_Error::IsError($amenityType) && $amenityType['itype'] == 'Normal') {
											if ($amenity != '') {
												$amenity .= ' ';
											}
											$amenity .= ' <nobr><img border="0" style="padding-left: 10px;" src="images/ICON_chkbox.gif">&nbsp;<a href="'.$GLOBALS['app']->getSiteURL().'/index.php?gadget=Store&action=Category&Store_id=all&Store_attributes='.urlencode($GLOBALS['app']->UTF8->str_replace('"', '%22', $xss->filter(strip_tags($amenityParent['feature'])))).'">'.$amenityParent['feature'].'</a></nobr>';;
											$amenityCount++;
										}
									}
								}
							}
						}
						$tpl->SetVariable('attribute', $xss->defilter($amenity));
						
						// description
						$description = '';
						if (isset($page['description']) && !empty($page['description'])) {
							$description = strip_tags($this->ParseText($page['description'], 'Store'), '<img>');
						}
						$tpl->SetVariable('description', $description);
						
						$product_image = '';
						if (!empty($image_src)) {
							//if ($GLOBALS['app']->Registry->Get('/gadgets/Store/default_display') == 'list') {
								$product_image = '<div class="product_image"><a id="link_product_image_'.$page['id'].'" href="'.$product_link.'" title="'.$safe_title.'"><img id="product_image_'.$page['id'].'" style="padding-bottom: 5px;" border="0" alt="'.$safe_title.'" src="'.$image_src.'" width="200" '.(strtolower(substr($page['image'], -3)) == "gif" || strtolower(substr($page['image'], -3)) == "png" || strtolower(substr($page['image'], -3)) == "bmp" ? 'height="200"' : '').' /></a></div>';				
							/*
							} else {
								$product_image = '<div class="product_image" style="background: url('.$image_src.') scroll center center no-repeat;"><a href="'.$product_link.'" title="'.$safe_title.'"><img alt="'.$safe_title.'" border="0" src="images/blank.gif" width="200" height="200" /></a></div>';				
							}
							*/
						} else if ((!file_exists(JAWS_DATA . 'files'.$page['image'])) || (empty($page['image']) && strpos(strtolower($page['description']), "img") === false)) {
							$product_image = '<div class="product_no_image" onclick="location.href=\''.$product_link.'\';"><b>No Image</b></div>';
						}
						$tpl->SetVariable('product_image', $product_image);
						$tpl->SetVariable('image_src', $image_src);
						
						$tpl->SetVariable('evenodd', $evenodd);
										
						if ($GLOBALS['app']->Registry->Get('/gadgets/Store/default_display') == 'list') {
							$tpl->ParseBlock('products/content/listproduct');
						} else {
							$tpl->ParseBlock('products/content/gridproduct');
						}
						$i++;
				}
			} else {
				$tpl->SetVariable('PAGE_CYCLE', $page_cycle);
				$tpl->SetVariable('NO_PRODUCTS', '<div style="padding: 10px; display: block; float: none; clear: both;"><i>No products '.(!empty($searchkeyword) ? 'that match the keyword  <b>"'.$searchkeyword.'"</b> ' : '').(!empty($searchattributes) ? (!empty($searchkeyword) ? ' AND ' : ''). 'that match attributes:  <b>"'.$searchattributes.'"</b> ' : '').(!empty($searchsales) ? (!empty($searchkeyword) || !empty($searchattributes) ? ' AND ' : ''). 'that match sales:  <b>"'.$searchsales.'"</b> ' : '').(!empty($searchbrands) ? (!empty($searchkeyword) || !empty($searchattributes) || !empty($searchsales) ? ' AND ' : ''). 'that match brands:  <b>"'.$searchbrands.'"</b> ' : '').'were found.</i></div>');
			}
			
			/*
			// RSS feed? Parse it here and show as Posts
			$rss_html = "";
			if (isset($page['productparentrss_url']) && !is_null($page['productparentrss_url']) && !empty($page['productparentrss_url'])) {
				require_once(JAWS_PATH . 'libraries/magpierss-0.72/rss_fetch.inc');
				$rss = fetch_rss($page['productparentrss_url']);

				if ($rss) {
					$date = $GLOBALS['app']->loadDate();
					$hideRss = $model->GetHiddenRssOfProductParent($page['productparentid']);
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
							$submit_vars[SYNTACTS_DB ."3:0:$i"] = $xss->defilter($rss_title);
							$submit_vars[SYNTACTS_DB ."3:1:$i"] = $xss->defilter($rss_url);
							$submit_vars[SYNTACTS_DB ."3:2:$i"] = $xss->defilter($rss_image);
							$submit_vars[SYNTACTS_DB ."3:3:$i"] = $date->Format($rss_published);
							$submit_vars[SYNTACTS_DB ."3:4:$i"] = strip_tags($rss_description, '<img><br><hr>');
							//$submit_vars[SYNTACTS_DB ."3:4:$i"] = $this->ParseText($rss_description, 'Store');
							$i++;
						}
					}
					$submit_vars['3:rows'] = $i-1;
					//$rss_html .= "<div style=\"clear: all; padding: 15px; text-align:left\"><b>Source: <a href=\"". $rss->channel['link']. "\" target=\"_blank\">". $rss->channel['title']. "</a></b></div>\n";
				} else {
					//$rss_html .= "<div style=\"padding: 15px; text-align:left\"><p><b>There was a problem parsing the RSS feed for: ".$page['productparentrss_url'].".</b></p></div>\n";
				}
			}
			*/	
			
			$tpl->ParseBlock('products/content');
			
			if ($embedded == true && !is_null($referer)) {	
				$tpl->SetBlock('products/embedded');
				$tpl->SetVariable('id', (isset($parent['productparentid']) ? $parent['productparentid'] : 'all'));		        
				if (strtolower($referer) == strtolower($_SERVER['SERVER_NAME'])) {
					$tpl->SetVariable('referer', $GLOBALS['app']->GetJawsURL() . "/libraries/iframes/domain1/iframetest_resize1.html");		        
				} else {	
					$tpl->SetVariable('referer', "http://".$referer."/19/img/agents/19/custom_img/common_files/iframetest_resize1.html");		        
				}
				$tpl->ParseBlock('products/embedded');
			} else {
				$tpl->SetBlock('products/not_embedded');
				$tpl->SetVariable('id', (isset($parent['productparentid']) ? $parent['productparentid'] : 'all'));		        
				$tpl->ParseBlock('products/not_embedded');
			}
			// Statistics Code
			$tpl->SetBlock('products/stats');
			$GLOBALS['app']->Registry->LoadFile('CustomPage');
			$tpl->SetVariable('stats', html_entity_decode($GLOBALS['app']->Registry->Get('/gadgets/CustomPage/googleanalytics_code')));		        
			$tpl->ParseBlock('products/stats');

			$tpl->ParseBlock('products');
			
			return $tpl->Get();
		}
        //}
	}

    /**
     * View product details.
     *
     * @category 	feature
     * @param	int 	$id 	Products ID (optional)
     * @param	boolean 	$embedded 	Embedded mode
     * @param	string 	$referer 	Embedding referer
     * @access 	public
     * @return 	string 	HTML template content
     */
    function Product($id = null, $embedded = false, $referer = null)
    {
		require_once JAWS_PATH . 'include/Jaws/Image.php';
        
		$request =& Jaws_Request::getInstance();
        $post = $request->get(array('id', 'linkid', 'action'), 'get');
        $GLOBALS['app']->Layout->AddHeadLink('gadgets/Store/resources/style.css', 'stylesheet', 'text/css');
		$GLOBALS['app']->Layout->AddHeadLink('libraries/carousel/themes/carousel/prototype-ui.css', 'stylesheet', 'text/css');
		$GLOBALS['app']->Layout->AddScriptLink('libraries/carousel/dist/carousel.js');
        $GLOBALS['app']->Layout->AddScriptLink('libraries/js/global2.js');
        $this->AjaxMe('client_script.js');
        $GLOBALS['app']->loadClass('ACL', 'Jaws_ACL');
        $xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $post['id'] = $xss->defilter($post['id']);

        $model = $GLOBALS['app']->LoadGadget('Store', 'Model');
        if (is_null($id)) {
			$id = $post['id'];
        }
		$page = $model->GetProduct($id);

        if (Jaws_Error::IsError($page)) {
            require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
            return Jaws_HTTPError::Get(404);
        } else {
            $tpl = new Jaws_Template('gadgets/Store/templates/');
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
			
			$responses = $GLOBALS['app']->Session->PopLastResponse();
			if ($responses) {
				foreach ($responses as $msg_id => $response) {
					$tpl->SetBlock('response');
					$tpl->SetVariable('msg', $response['message']);
					$tpl->ParseBlock('response');
					break;
				}
			}
			
			$tpl->SetBlock('product');

            if (!isset($page['id']) || $page['active'] == 'N') {
                $this->SetTitle(_t('STORE_TITLE_NOT_FOUND'));
				$tpl->SetBlock('product/not_found');
                $tpl->SetVariable('content', _t('STORE_CONTENT_NOT_FOUND'));
                $tpl->SetVariable('title', _t('STORE_TITLE_NOT_FOUND'));
                $tpl->ParseBlock('product/not_found');
            } else {
                if ((int)$page['ownerid'] > 0) {
					require_once JAWS_PATH . 'include/Jaws/User.php';
					$jUser = new Jaws_User;
					$info = $jUser->GetUserInfoById((int)$page['ownerid'], true, true, true, true);
					if (!Jaws_Error::IsError($info) && isset($info['id']) && file_exists(JAWS_DATA . 'files/css/users/'.$info['id'].'/custom.css')) {
						$GLOBALS['app']->Layout->AddHeadOther('<link rel="stylesheet" media="screen" type="text/css" href="'.$GLOBALS['app']->getDataURL('', true). 'files/css/users/'.$info['id'].'/custom.css" />');
					}
				}
				$tpl->SetBlock('product/content');
				// TODO: Implement Preview mode (use cookie to store length of time the preview is available)				
				$hasDetails = false;
				if (!empty($page['title'])) {
					$GLOBALS['app']->Layout->SetTitle($xss->defilter(preg_replace("[^A-Za-z0-9\ ]", '', $page['title'])));
				}
				$tpl->SetVariable('pagetype', 'product');
				$tpl->SetVariable('title', $xss->defilter(strip_tags($page['title'])));
				$tpl->SetVariable('id', $page['id']);
				$tpl->SetVariable('LinkID', (isset($post['linkid']) && !empty($post['linkid']) ? $xss->defilter($post['linkid']) : ''));
				$tpl->SetVariable('sort_order', $xss->defilter($page['sort_order']));
				$category = '';
				if (isset($page['category']) && !empty($page['category'])) {
					$hasDetails = true;
					$propCategories = explode(',', $page['category']);
					foreach($propCategories as $propCategory) {		            
						$catParent = $model->GetProductParent((int)$propCategory);
						if (!Jaws_Error::IsError($catParent)) {
							if ($category != '') {
								$category .= ',';
							}
							$category .= '<A HREF="'.$GLOBALS['app']->Map->GetURLFor('Store', 'Category', array('id' => $catParent['productparentfast_url'])).'"><U><B>'.$catParent['productparentcategory_name'].'</B></U></A>';
						}
					}
				}
				if ($category != '') {
					$category = '<br /><img border="0" src="images/spacer.gif" height="3" width="1"><img border="0" src="images/ICON_sm_arrow_gray.gif"><img border="0" src="images/spacer.gif" height="3" width="1">This product belongs to these categories: '.$category;
				}
				$tpl->SetVariable('category', $category);
				$tpl->SetVariable('image', $xss->defilter($page['image']));
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
						if (strpos(strtolower($image_src), 'data/files/') !== false) {
							$image_src = 'image_thumb.php?uri='.urlencode($image_src);
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
				$price = 0;
				if (!empty($page['price']) && ($page['price'] > 0)) {
					$price = number_format($page['price'], 2, '.', '');
				}
				// sales
				$now = $GLOBALS['db']->Date();
				$sale_price = $price;
				if (isset($page['sales']) && !empty($page['sales'])) {
					$propSales = explode(',', $page['sales']);
					$saleCount = 0;
					foreach($propSales as $propSale) {		            
						$saleParent = $model->GetSale((int)$propSale);
						if (!Jaws_Error::IsError($saleParent)) {
							if (
								empty($saleParent['coupon_code']) && $saleParent['active'] == 'Y' && 
								($now > $saleParent['startdate'] && $now < $saleParent['enddate'])
							) {
								if ($saleParent['discount_amount'] > 0) {
									$sale_price = number_format($sale_price - number_format($saleParent['discount_amount'], 2, '.', ''), 2, '.', '');
								} else if ($saleParent['discount_percent'] > 0) {
									$sale_price = number_format($sale_price - ($sale_price * ($saleParent['discount_percent'] * .01)), 2, '.', '');
								} else if ($saleParent['discount_newprice'] > 0) {
									$sale_price = number_format($saleParent['discount_newprice'], 2, '.', '');
								}
							}
						}
						$saleCount++;
					}
					if ($sale_price != $price) {
						$sale_string = '<span class="sale_string">SALE</span>&nbsp;$'.($sale_price > 0 ? number_format($sale_price, 2, '.', ',') : 0.00);
						$tpl->SetVariable('sale_price', '&nbsp;'.$xss->defilter($sale_string));
					}
				}
				$price_string = '';
				$sale_display = ' display: none;';
				if ($price > 0) {
					if ($sale_price != $price) {
						$price_string = '<del>$'.number_format($page['price'], 2, '.', ',').'</del>';
						$sale_display = '';
					} else {
						$price_string = '$'.number_format($page['price'], 2, '.', ',');
					}
				}
				$tpl->SetVariable('sale_display', $sale_display);
				$tpl->SetVariable('price', '&nbsp;'.$xss->defilter($price_string));
				if (isset($page['retail']) && $page['retail'] > 0) {
					$tpl->SetVariable('retail', '&nbsp;<del>$'.number_format($page['retail'], 2, '.', ',').'&nbsp;'._t('STORE_RETAIL').'</del>&nbsp;&nbsp;');
				}
				if (isset($page['setup_fee']) && $page['setup_fee'] > 0) {
					$tpl->SetVariable('setup_fee', _t('STORE_SETUP_FEE').':&nbsp;$'.number_format($page['setup_fee'], 2, '.', ',').'&nbsp;&nbsp;');
				}
				if (isset($page['unit']) && !empty($page['unit']) && $price > 0) {
					$tpl->SetVariable('unit', $xss->defilter(strip_tags($page['unit'])));
				}
				if (isset($page['weight']) && !empty($page['weight']) && $page['weight'] > 1) {
					$tpl->SetVariable('weight', '<br /><img border="0" src="images/spacer.gif" height="3" width="1"><img border="0" src="images/ICON_sm_arrow_gray.gif"><img border="0" src="images/spacer.gif" height="3" width="1">Weight: '.$xss->defilter(strip_tags($page['weight'])).'&nbsp;lbs.');
				}
				if (isset($page['product_code']) && !empty($page['product_code'])) {
					$tpl->SetVariable('product_code', '<br /><img border="0" src="images/spacer.gif" height="3" width="1"><img border="0" src="images/ICON_sm_arrow_gray.gif"><img border="0" src="images/spacer.gif" height="3" width="1">Product Code: '.$xss->defilter(strip_tags($page['product_code'])));
				}
				if (isset($page['sm_description']) && !empty($page['sm_description'])) {
					$tpl->SetVariable('sm_description', '<br /><img border="0" src="images/spacer.gif" height="3" width="1"><img border="0" src="images/ICON_sm_arrow_gray.gif"><img border="0" src="images/spacer.gif" height="3" width="1">'.$xss->defilter(strip_tags($page['sm_description'])));
				}
				$product_brandHTML = '';
				if (!empty($page['brandid']) && ($page['brandid'] > 0)) {
					$brandParent = $model->GetBrand($page['brandid']);
					if (!Jaws_Error::IsError($brandParent)) {
						$product_brandHTML = '<br /><img border="0" src="images/spacer.gif" height="3" width="1"><img border="0" src="images/ICON_sm_arrow_gray.gif"><img border="0" src="images/spacer.gif" height="3" width="1">Brand: <a href="'.$GLOBALS['app']->getSiteURL().'/index.php?gadget=Store&action=Category&id=all&brand='.$brandParent['id'].'">'.$xss->defilter(strip_tags($brandParent['title'])).'</a>&nbsp;';
						$tpl->SetVariable('brand', $product_brandHTML);
					}
				}
				$tpl->SetVariable('premium', $xss->defilter($page['premium']));
				$tpl->SetVariable('featured', $xss->defilter($page['featured']));
				$tpl->SetVariable('OwnerID', $xss->defilter($page['ownerid']));
				$tpl->SetVariable('Active', $xss->defilter($page['active']));
				$tpl->SetVariable('Created', $xss->defilter($page['created']));
				$tpl->SetVariable('Updated', $xss->defilter($page['updated']));
				$tpl->SetVariable('fast_url', $xss->defilter($page['fast_url']));
				$tpl->SetVariable('internal_productno', $xss->defilter(strip_tags($page['internal_productno'])));
				$tpl->SetVariable('rss_url', $xss->defilter(strip_tags($page['rss_url'])));
				$tpl->SetVariable('alink', $xss->defilter(strip_tags($page['alink'])));
				$tpl->SetVariable('alinkTitle', $xss->defilter(strip_tags($page['alinktitle'])));
				$tpl->SetVariable('alinkType', $xss->defilter(strip_tags($page['alinktype'])));
				$tpl->SetVariable('alink2', $xss->defilter(strip_tags($page['alink2'])));
				$tpl->SetVariable('alink2Title', $xss->defilter(strip_tags($page['alink2title'])));
				$tpl->SetVariable('alink2Type', $xss->defilter(strip_tags($page['alink2type'])));
				$tpl->SetVariable('alink3', $xss->defilter(strip_tags($page['alink3'])));
				$tpl->SetVariable('alink3Title', $xss->defilter(strip_tags($page['alink3title'])));
				$tpl->SetVariable('alink3type', $xss->defilter(strip_tags($page['alink3type'])));

				$c = 0;
				$breadcrumb_start = '<span class="center_nav_font"><span id="center_nav_'.$c.'"><a href="'.$GLOBALS['app']->Map->GetURLFor('Store', 'Category', array('id' => 'all')).'" class="center_nav_link">All Categories</a>&nbsp;&nbsp;';
				if (isset($info['id']) && !empty($info['id'])) {
					$c++;
					$name = (!empty($info['company']) ? $info['company'] : (!empty($info['nickname']) ? $info['nickname'] : 'Merchant ID: '.$info['id']));
					$breadcrumb_start .= '>&nbsp;&nbsp;</span><span id="center_nav_'.$c.'"><a href="'.$GLOBALS['app']->getSiteURL().'/index.php?gadget=Store&action=Category&owner_id='.$info['id'].'" class="center_nav_link">'.$name.'</a>&nbsp;&nbsp;';
				}
				$breadcrumbHTML = '';
				
				if (isset($page['category']) && !empty($page['category'])) {
					$categories = explode(',', $page['category']);
					if ((int)$categories[0] > 0) {
					$parent = $model->GetProductParent((int)$categories[0]);
						if (!Jaws_Error::IsError($parent)) {
							$tpl->SetVariable('productparentID', $xss->defilter($parent['productparentid']));
							$tpl->SetVariable('productparentParent', $xss->defilter($parent['productparentparent']));
							$tpl->SetVariable('productparentsort_order', $xss->defilter($parent['productparentsort_order']));
							$tpl->SetVariable('productparentCategory_Name', $xss->defilter(strip_tags($parent['productparentcategory_name'])));
							$tpl->SetVariable('productparentImage', $xss->parse(strip_tags($parent['productparentimage'])));
							$tpl->SetVariable('productparentDescription', $this->ParseText($parent['productparentdescription'], 'Store'));
							$tpl->SetVariable('productparentActive', $xss->defilter($parent['productparentactive']));
							$tpl->SetVariable('productparentOwnerID', $xss->defilter($parent['productparentownerid']));
							$tpl->SetVariable('productparentCreated', $xss->defilter($parent['productparentcreated']));
							$tpl->SetVariable('productparentUpdated', $xss->defilter($parent['productparentupdated']));
							$tpl->SetVariable('productparentFeatured', $xss->defilter($parent['productparentfeatured']));
							$tpl->SetVariable('productparentFast_url', $xss->defilter($parent['productparentfast_url']));
							$tpl->SetVariable('productparentRss_url', $xss->defilter($parent['productparentrss_url']));
							$c++;
							$breadcrumbHTML .= '>&nbsp;&nbsp;</span><span id="center_nav_'.$c.'"><a href="'.$GLOBALS['app']->Map->GetURLFor('Store', 'Category', array('id' => $parent['productparentfast_url'])).'" class="center_nav_link">'.$xss->defilter(strip_tags($parent['productparentcategory_name'])).'</a>&nbsp;&nbsp;';
							/*
							$parentID = $parent['productparentparent'];
							while ($parentID > 0) {
								$grandparent = $model->GetProductParent((int)$parent['productparentparent']);
								if (!Jaws_Error::IsError($grandparent)) {
									$breadcrumbHTML = '>&nbsp;&nbsp;<a href="'.$GLOBALS['app']->Map->GetURLFor('Store', 'Category', array('id' => $grandparent['productparentfast_url'])).'">'.$xss->defilter(strip_tags($grandparent['productparentcategory_name'])).'</a>&nbsp;&nbsp;'.$breadcrumbHTML;
									$parentID = $grandparent['productparentparent'];
								}
							}
							*/
						}
					}
				}
				
				$c++;
				$breadcrumbHTML .= '>&nbsp;&nbsp;</span><span id="center_nav_'.$c.'">'.$xss->defilter(strip_tags($page['title'])).'&nbsp;&nbsp;</span>';
				$breadcrumbHTML = $breadcrumb_start.$breadcrumbHTML."</span>";
				$tpl->SetVariable('BREADCRUMB', $breadcrumbHTML);
				
				// Add to Cart
				$productDisabled = '';
				$product_cartHTML = '';
				if ($page['inventory'] == 'Y' && (($page['instock'] == 0) && $page['outstockbuy'] == 'N')) {
					$productDisabled = ' product_disabled';
					$product_cartHTML .= "<div class=\"outstockmsg\">";
					if (isset($page['outstockmsg']) && !empty($page['outstockmsg'])) {
						$product_cartHTML .= $page['outstockmsg'];
					} else {
						$product_cartHTML .= 'This product is sold out. Check back soon.';
					}
					$product_cartHTML .= "</div>";
				} else if (Jaws_Gadget::IsGadgetUpdated('Ecommerce')) {
					$GLOBALS['app']->Registry->LoadFile('Ecommerce');
					$GLOBALS['app']->Translate->LoadTranslation('Ecommerce', JAWS_GADGET);
					$ecommerce_layout = $GLOBALS['app']->LoadGadget('Ecommerce', 'LayoutHTML');
					$product_cartHTML .= $ecommerce_layout->ShowCartButton($page['id']);
				} else {
					$GLOBALS['app']->Layout->AddHeadLink('gadgets/Ecommerce/resources/style.css', 'stylesheet', 'text/css');					
					$product_title = $xss->defilter(strip_tags($page['title']));
					$sale_string2 = ($sale_price > 0 ? $sale_price : 0.00);
					$price_string2 = number_format($price, 2, '.', '');
					$product_price = ($sale_string2 != $price_string2 ? $sale_string2 : $price_string2);
					
					$product_setup_fee = '';
					if (isset($page['setup_fee']) && $page['setup_fee'] > 0) {
						$product_setup_fee = number_format($page['setup_fee'], 2, '.', '');
						$setup_price = ($product_setup_fee+$product_price);
						$setup_price = number_format($setup_price, 2, '.', ',');
						//$product_price = $setup_price;
						$product_setup_fee = '<span class="product-attr-setup-fee">'._t('STORE_SETUP_FEE').': $'.$product_setup_fee.'</span><br />';
					}
					
					$product_recurring = '';
					if (isset($page['recurring']) && !empty($page['recurring']) && $page['recurring'] == 'Y') {
						$product_recurring = $page['recurring'];
						$product_recurring = '<span class="product-attr-recurring">Is subscription: '.$product_recurring.'</span><br />';
					}
										
					$product_unit = '';
					if (isset($page['unit']) && !empty($page['unit'])) {
						$product_unit = $xss->defilter(strip_tags($page['unit']));
						$product_unit = '<span class="product-attr-unit">'.$product_unit.'</span>';
					}
					
					// attributes
					$amenity = '';
					$hidden_selects = '';
					if (isset($page['attribute']) && !empty($page['attribute'])) {
						$amenityTypes = $model->GetAttributeTypes();
						if (!Jaws_Error::IsError($amenityTypes)) {
							$propAmenities = explode(',', $page['attribute']);
							foreach($amenityTypes as $amenityType) {		            
								$amenity_header = '';
								$amenity_footer = '';
								$amenity_body = '';
								foreach($propAmenities as $propAmenity) {		            
									$amenityParent = $model->GetAttribute((int)$propAmenity);
									if (!Jaws_Error::IsError($amenityParent) && $amenityType['id'] == $amenityParent['typeid']) {
										if (empty($amenity_header)) {
											// ******************************************************
											// this loops through the question array and then
											// creates an appropriate answer device
											// ******************************************************
											if ($amenityType['itype'] != "HiddenField") {
												//if ($amenityType['required'] == "Y") {
												//	$amenity .= '<b>'.$xss->defilter($amenityType['title']).'</b> <i>(Required)</i>:<br />';
												//} else {
													$amenity_header .= '<div class="addtocart-options"><b>'.$xss->defilter($amenityType['title']).'</b>:<br />';
												//}

												// dropdown
												if ($amenityType['itype'] == "SelectBox") {
													//$amenity_header .= '<select class="product-attr-'.str_replace(' ', '-', $xss->defilter($amenityType['title'])).'" onchange="selected = this.options[this.selectedIndex].value.replace(/ /gi, \'\'); selected = selected.replace(/:/gi, \'-\'); if ($(\'select-\'+selected+\'-'.$page['id'].'\')){select_amount = parseFloat($(\'select-\'+selected+\'-'.$page['id'].'\').value); select_amount = number_format(select_amount, 2, \'.\', \',\'); changePrice(\''.$page['id'].'\', select_amount);};">';
													$amenity_header .= '<select name="select-attr-'.$amenityType['id'].'" class="product-attr-'.str_replace(' ', '-', $xss->defilter($amenityType['title'])).'" onchange="changePrice('.$page['id'].', '.$amenityType['id'].');">';
													$amenity_footer .= '</select>';
												} else if ($amenityType['itype'] == "TextBox") {
													$amenity_header .= '<input type="text" class="product-attr-'.str_replace(' ', '-', $xss->defilter($amenityType['title'])).'" size="20"><br />';
												} else if ($amenityType['itype'] == "TextArea") {
													$amenity_header .= '<textarea rows="3" class="product-attr-'.str_replace(' ', '-', $xss->defilter($amenityType['title'])).'" cols="28"></textarea><br />';
												}
												$amenity_footer .= '</div><br />';
											}
										}
							
										$add_amount = 0;
										$attr_price = $product_price;
										if ($amenityParent['add_amount'] > 0) {
											$attr_price = ((number_format($amenityParent['add_amount'], 2, '.', ''))+$product_price);
											$attr_price = number_format($attr_price, 2, '.', ',');
										} else if ($amenityParent['add_percent'] > 0) {
											$attr_price = ((($amenityParent['add_percent'] * .01) * ($product_price))+$product_price);
											$attr_price = number_format($attr_price, 2, '.', ',');
										} else if ($amenityParent['newprice'] > 0) {
											$attr_price = number_format($amenityParent['newprice'], 2, '.', ',');
										}
										if ($attr_price > $product_price) {
											$add_amount = number_format(($attr_price - $product_price), 2, '.', ',');
										} else {
											$add_amount = number_format(($product_price - $attr_price)*(-1), 2, '.', ',');
										}
								
										if ($amenityType['itype'] == 'HiddenField') {
											$amenity_body .= '<input '.(($attr_price != $product_price) ? $product_price = $attr_price : '').' type="hidden" class="product-attr-'.str_replace(' ', '-', $xss->defilter($amenityType['title'])).'" value="'.$xss->defilter($amenityType['title']).': '.$xss->defilter($amenityParent['feature']).'">';
										} else if ($amenityType['itype'] == 'RadioBtn') {
											//$amenity .= '<input '.(($attr_price != $product_price) ? 'googlecart-set-product-price="'.'$'.$attr_price.'"' : 'googlecart-set-product-price="'.'$'.$product_price.'"').' type="radio" class="product-attr-'.str_replace(' ', '-', $xss->defilter($amenityType['title'])).'" name="'.str_replace(' ', '-', $xss->defilter($amenityType['title'])).'" value="'.$xss->defilter($amenityType['title']).': '.$xss->defilter($amenityParent['feature']).'"> '.$amenityParent['feature'].(($attr_price != $product_price) ? '&nbsp;&nbsp;&nbsp;&nbsp;<b>('.($add_amount > 0 ? 'Adds $'.$add_amount.' to price' : 'Subtracts $'.$add_amount.' from price').')</b>' : '').'<br />';
											//$amenity .= '<script type="text/javascript">var checked_'.str_replace(' ', '', $xss->defilter($amenityType['title'])).'_'.str_replace(' ', '', $xss->defilter($amenityParent['feature'])).'_'.$page['id'].' = false;</script>';
											//$amenity .= '<input onclick="if(!checked_'.str_replace(' ', '', $xss->defilter($amenityType['title'])).'_'.str_replace(' ', '', $xss->defilter($amenityParent['feature'])).'_'.$page['id'].'){checked_'.str_replace(' ', '', $xss->defilter($amenityType['title'])).'_'.str_replace(' ', '', $xss->defilter($amenityParent['feature'])).'_'.$page['id'].' = true; changePrice('.$page['id'].', '.$add_amount.');};" type="radio" class="product-attr-'.str_replace(' ', '-', $xss->defilter($amenityType['title'])).'" name="'.str_replace(' ', '-', $xss->defilter($amenityType['title'])).'" value="'.$xss->defilter($amenityType['title']).': '.$xss->defilter($amenityParent['feature']).'"> '.$amenityParent['feature'].(($attr_price != $product_price) ? '&nbsp;&nbsp;&nbsp;&nbsp;<b>('.($add_amount > 0 ? 'Adds $'.$add_amount.' to price' : 'Subtracts $'.$add_amount.' from price').')</b>' : '').'<br />';
											$amenity_body .= '<input onclick="changePrice('.$page['id'].', '.$amenityType['id'].');" name="radio-attr-'.$amenityType['id'].'" id="attr-'.$amenityParent['id'].'" type="radio" class="product-attr-'.str_replace(' ', '-', $xss->defilter($amenityType['title'])).'" value="'.$xss->defilter($amenityType['title']).': '.$xss->defilter($amenityParent['feature']).'" checked> '.$amenityParent['feature'].(($attr_price != $product_price) ? ' &nbsp;<nobr><b>('.($add_amount > 0 ? 'Adds $'.$add_amount : 'Subtracts $'.$add_amount).')</b></nobr>' : '').'<br />';
											$hidden_selects .= '<input type="hidden" id="attr-'.str_replace(' ', '', $xss->defilter($amenityType['title'])).'-'.str_replace(' ', '', $xss->defilter($amenityParent['feature'])).'-'.$page['id'].'" value="'.$add_amount.'">';
										} else if ($amenityType['itype'] == 'CheckBox') {
											//$amenity .= '<input '.(($attr_price != $product_price) ? 'googlecart-set-product-price="'.'$'.$attr_price.'"' : '').' onclick="if(!this.checked){this.setAttribute(\'googlecart-set-product-price\', \'$'.$product_price.'\');$$(\'#addtocart-'.$page['id'].' .product .product-price\').each(function(item) {item.innerHTML = \'$'.$product_price.'\';});$$(\'#addtocart-copy-'.$page['id'].' .product .product-price\').each(function(item) {item.innerHTML = \'$'.$product_price.'\';});}else{this.setAttribute(\'googlecart-set-product-price\', \'$'.$attr_price.'\');};" type="checkbox" class="product-attr-'.str_replace(' ', '-', $xss->defilter($amenityType['title'])).'" name="'.str_replace(' ', '-', $xss->defilter($amenityType['title'])).'" value="'.$xss->defilter($amenityType['title']).': '.$xss->defilter($amenityParent['feature']).'"> '.$amenityParent['feature']. (($attr_price != $product_price) ? '&nbsp;&nbsp;&nbsp;&nbsp;<b>('.($add_amount > 0 ? 'Adds $'.$add_amount.' to price' : 'Subtracts $'.$add_amount.' from price').')</b>' : '').'<br />';
											//$amenity .= '<input onclick="if(!this.checked){changePrice('.$page['id'].', '.($add_amount*(-1)).');}else{changePrice('.$page['id'].', '.$add_amount.');};" type="checkbox" class="product-attr-'.str_replace(' ', '-', $xss->defilter($amenityType['title'])).'" name="'.str_replace(' ', '-', $xss->defilter($amenityType['title'])).'" value="'.$xss->defilter($amenityType['title']).': '.$xss->defilter($amenityParent['feature']).'"> '.$amenityParent['feature']. (($attr_price != $product_price) ? '&nbsp;&nbsp;&nbsp;&nbsp;<b>('.($add_amount > 0 ? 'Adds $'.$add_amount.' to price' : 'Subtracts $'.$add_amount.' from price').')</b>' : '').'<br />';
											$amenity_body .= '<input onclick="changePrice('.$page['id'].', '.$amenityType['id'].');" name="checkbox-attr-'.$amenityType['id'].'" id="attr-'.$amenityParent['id'].'" type="checkbox" class="product-attr-'.str_replace(' ', '-', $xss->defilter($amenityType['title'])).'" value="'.$xss->defilter($amenityType['title']).': '.$xss->defilter($amenityParent['feature']).'"> '.$amenityParent['feature']. (($attr_price != $product_price) ? ' &nbsp;<nobr><b>('.($add_amount > 0 ? 'Adds $'.$add_amount : 'Subtracts $'.$add_amount).')</b></nobr>' : '').'<br />';
											$hidden_selects .= '<input type="hidden" id="attr-'.str_replace(' ', '', $xss->defilter($amenityType['title'])).'-'.str_replace(' ', '', $xss->defilter($amenityParent['feature'])).'-'.$page['id'].'" value="'.$add_amount.'">';
										} else if ($amenityType['itype'] == 'SelectBox') {
											//$amenity .= '<option '.(($attr_price != $product_price) ? 'googlecart-set-product-price="'.'$'.$attr_price.'"' : 'googlecart-set-product-price="'.'$'.$product_price.'"').' value="'.$xss->defilter($amenityType['title']).': '.$xss->defilter($amenityParent['feature']).'">'.$amenityParent['feature'].(($attr_price != $product_price) ? '&nbsp;&nbsp;&nbsp;&nbsp;<b>('.($add_amount > 0 ? 'Adds $'.$add_amount.' to price' : 'Subtracts $'.$add_amount.' from price').')</b>' : '').'</option>';
											$amenity_body .= '<option id="attr-'.$amenityParent['id'].'" value="'.$xss->defilter($amenityType['title']).': '.$xss->defilter($amenityParent['feature']).'">'.$amenityParent['feature'].(($attr_price != $product_price) ? '&nbsp;&nbsp;('.($add_amount > 0 ? 'Adds $'.$add_amount : 'Subtracts $'.$add_amount).')' : '').'</option>';
											$hidden_selects .= '<input type="hidden" id="attr-'.str_replace(' ', '', $xss->defilter($amenityType['title'])).'-'.str_replace(' ', '', $xss->defilter($amenityParent['feature'])).'-'.$page['id'].'" value="'.$add_amount.'">';
										} else if ($amenityType['itype'] == 'Normal') {
											$amenity_body .= '<span '.(($attr_price != $product_price) ? $product_price = $attr_price : '').' class="product-attr-'.str_replace(' ', '-', $xss->defilter($amenityType['title'])).'">'.$amenityParent['feature'].'</span>'.(($attr_price != $product_price) ? ' &nbsp;<nobr><b>('.($add_amount > 0 ? 'Adds $'.$add_amount : 'Subtracts $'.$add_amount).')</b></nobr>' : '').'<br />';
										}
									}
								}
								$amenity .= $amenity_header.$amenity_body.$amenity_footer; 
							}
						}
					}
					$product_attribute = $amenity.$hidden_selects.'<br />';
										
					$cart_html .= '<div class="addtocart-holder" id="addtocart-'.$page['id'].'"><form id="addtocart-form-'.$page['id'].'" name="addtocart-form-'.$page['id'].'"><div class="product" style="text-align: left; width: 230px; line-height: 19px;">';
					$cart_html .= '<span class="product-price"'.($product_price == 0 ? ' style="display: none;"' : '').'><span style="font-size: 0.55em; color: #FFFFFF;">Price:</span> $'.$product_price.'</span> '.(!empty($product_unit) && $product_price > 0 ? $product_unit : '').'<br />';
					$cart_html .= $product_setup_fee;
					$cart_html .= $product_attribute;
					$cart_html .= $product_recurring;
					$cart_html .= $product_weight;
					$cart_html .= $product_code;
					//$cart_html .= '<div role="button" alt="Add to cart" tabindex="0" class="ecommerce-cart-button googlecart-add" id="ecommerce-cart-link-'.$page['id'].'" onclick="if ($(\'googlecart-widget\')) {$(\'googlecart-widget\').style.visibility = \'visible\';};"></div>';
					$cart_html .= "</div></form></div>\n";
					
					$script = "<script type=\"text/javascript\">
						var add_amount = 0.00;
						var select_name = 'select-';
						var radio_name = 'radio-';
						var checkbox_name = 'checkbox-';
						var oldprice = ".$product_price.";
						function changePrice(parent, unused) {
							add_amount = 0.00;
							$$(\"#addtocart-\"+parent+\" #addtocart-form-\"+parent).each(function(item) {
								var elem = item.elements; 
								for(var i = 0; i < elem.length; i++) { 
									if (elem[i].name.substr(0, 7) == select_name) {
										selected = elem[i].options[elem[i].selectedIndex].value.replace(/ /gi, ''); 
										selected = selected.replace(/:/gi, '-'); 
										if ($('attr-'+selected+'-'+parent)){
											new_amount = parseFloat($('attr-'+selected+'-'+parent).value); 
											add_amount = add_amount + new_amount;
										};
									} else if (elem[i].name.substr(0, 6) == radio_name || elem[i].name.substr(0, 9) == checkbox_name) {
										if (elem[i].checked) {
											selected = elem[i].value.replace(/ /gi, ''); 
											selected = selected.replace(/:/gi, '-'); 
											if ($('attr-'+selected+'-'+parent)){
												new_amount = parseFloat($('attr-'+selected+'-'+parent).value); 
												add_amount = add_amount + new_amount;
											};
										};								
									};
								};
							});
							$$(\"#addtocart-copy-\"+parent+\" #addtocart-form-\"+parent).each(function(item) {
								var elem = item.elements; 
								for(var i = 0; i < elem.length; i++) { 
									if (elem[i].name.substr(0, 7) == select_name) {
										selected = elem[i].options[elem[i].selectedIndex].value.replace(/ /gi, ''); 
										selected = selected.replace(/:/gi, '-'); 
										if ($('attr-'+selected+'-'+parent)){
											new_amount = parseFloat($('attr-'+selected+'-'+parent).value); 
											add_amount = add_amount + new_amount;
										};
									} else if (elem[i].name.substr(0, 6) == radio_name || elem[i].name.substr(0, 9) == checkbox_name) {
										if (elem[i].checked) {
											selected = elem[i].value.replace(/ /gi, ''); 
											selected = selected.replace(/:/gi, '-'); 
											if ($('attr-'+selected+'-'+parent)){
												new_amount = parseFloat($('attr-'+selected+'-'+parent).value); 
												add_amount = add_amount + new_amount;
											};
										};								
									};
								};
							});	
							add_amount = number_format(add_amount, 2, '.', ','); 
							$$(\"#addtocart-\"+parent+\" .product .product-price\").each(function(item) {
								/*
								var oldprice = item.innerHTML;
								oldprice = parseFloat(oldprice.substr(oldprice.indexOf('$')+1, oldprice.length));
								oldprice = number_format(oldprice, 2, '.', ',');
								*/
								newprice = number_format((parseFloat(oldprice)+parseFloat(add_amount)), 2, '.', ',');
								if (newprice > 0) {
									item.style.display = '';
									item.innerHTML = '<span style=\"font-size: 0.55em; color: #FFFFFF;\">Price:</span> $'+newprice;
								}
							});
							$$(\"#addtocart-copy-\"+parent+\" .product .product-price\").each(function(item) {
								/*
								var oldprice = item.innerHTML;
								oldprice = parseFloat(oldprice.substr(oldprice.indexOf('$')+1, oldprice.length));
								oldprice = number_format(oldprice, 2, '.', ',');
								*/
								newprice = number_format((parseFloat(oldprice)+parseFloat(add_amount)), 2, '.', ',');
								if (newprice > 0) {
									item.style.display = '';
									item.innerHTML = '<span style=\"font-size: 0.55em; color: #FFFFFF;\">Price:</span> $'+newprice;
								}
							});							
						}
					</script>
					\n";
					$script = str_replace("\r\n", '', $script);
					$script = str_replace("\n", '', $script);
					$cart_html .= $script;
					
					$product_cartHTML .= $cart_html;
				}
				
				$tpl->SetVariable('ADD_TO_CART',  $product_cartHTML);
				
				// Product Header
				if ($page['premium'] == 'Y') {
					$product_headerHTML = "<div align=\"center\" class=\"product_featured_bkgnd".$productDisabled."\"><table border=\"0\" cellpadding=\"3\" cellspacing=\"0\" width=\"100%\"><tr><td valign=\"top\" width=\"0%\"><img border=\"0\" src=\"images/propnav_feat_spotlight.gif\"></td><td width=\"100%\"><div align=\"center\" class=\"product_featured_listing_bkgnd\">";
				} else {
					$product_headerHTML = "<div align=\"center\" class=\"product_bkgnd".$productDisabled."\"><table border=\"0\" cellpadding=\"3\" cellspacing=\"0\" width=\"100%\"><tr><td width=\"100%\"><div align=\"center\">";
				}
				$tpl->SetVariable('product_header', $product_headerHTML);
								
				$emailDisabled = false;
				$tpl->SetVariable('emailDisabled', ($emailDisabled === true ? '_disabled' : ''));
				// TODO: Implement Saved Products / Users integration
				$saveDisabled = false;
				$tpl->SetVariable('saveDisabled', (($saveDisabled === true) ? '_disabled' : ''));
				
				/*
				// send Post records
				$calendarModel = $GLOBALS['app']->LoadGadget('Calendar', 'Model');
				$cids = $calendarModel->GetAllCalendarsOfProduct($page['id']);
				
				if ($cids) {
					foreach($cids as $cid) {		            
							$submit_vars[SYNTACTS_DB ."4:$j:$i"] = ($c == 'description') ? $this->ParseText($cv, 'Store') : $xss->defilter($cv);
					}
				}
				
				// send Post records
				$resrates = $model->GetAllResratesOfProduct($page['id']);
				
				if ($resrates) {
					foreach($resrates as $rate) {		            
							$submit_vars[SYNTACTS_DB ."5:$j:$i"] = ($r == 'description') ? $this->ParseText($rv, 'Store') : $xss->defilter($rv);
					}
				}
				*/
				
				//$tpl->SetVariable('DPATH',  JAWS_DPATH);
				$tpl->SetVariable('JAWS_URL',  $GLOBALS['app']->GetJawsURL() . '/');
				$tpl->SetVariable('HTTP_REFERER',  $GLOBALS['app']->GetSiteURL());
				
				// Comments
				$tpl->SetVariable('calendarDisabled', '');
				$product_calendar_anchor = $GLOBALS['app']->GetSiteURL().'/'.$GLOBALS['app']->Map->GetURLFor('Store', 'Product', array('id' => $page['fast_url'])).'#comments';
				$product_calendar_click = "if (document.getElementById('prodnav_calendar').className.indexOf('disabled') == -1) {if (document.getElementById('calendar')) {location.href = '".$product_calendar_anchor."';};};";
				$product_calendar_click_text = "Reviews";
				$tpl->SetVariable('CALENDAR_CLICK', $product_calendar_click);
				$tpl->SetVariable('CALENDAR_CLICK_TEXT', $product_calendar_click_text);
				$tpl->SetVariable('CALENDAR_ANCHOR', $product_calendar_anchor);
				$userHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
				$tpl->SetVariable('CALENDAR', $userHTML->ShowComments('Store', true, $page['id'], 'Reviews'));
												
				// Product E-mail Form
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
				$redirect = $GLOBALS['app']->Map->GetURLFor('Store', 'Product', array('id' => $page['fast_url']));
				$redirect = (substr($redirect, 0, 4) != 'http' ? $site_url.'/'.$redirect : $redirect);
				if (Jaws_Gadget::IsGadgetUpdated('Social')) {
					$GLOBALS['app']->Translate->LoadTranslation('Social', JAWS_GADGET);
					$socialLayout = $GLOBALS['app']->LoadGadget('Social', 'LayoutHTML');
					$product_email_form = $socialLayout->Display();
				} else {
					/*
					Custom Form implementation
					- Add "__REQUIRED__" to any question title to make the field required
					- Add "__EXTRA_RECIPIENT__" to add the field as a recipient 
					- Add "__REDIRECT__" to specify where we are coming from/return URL after form submission
					- Add "__MESSAGE__" to show as a message in the resultant e-mail
					*/	
					$product_email_form = $formsLayout->Display(null, true, array('id' => 'customProductEmail'.$page['id'], 'sort_order' => 0, 'title' => 'E-mail To A Friend', 
						'sm_description' => '', 'description' => "E-mail this product page to up to 5 of your friends.", 'clause' => '', 
						'image' => '', 'recipient' => '', 'parent' => 0, 'custom_action' => '', 'fast_url' => '', 'active' => 'Y', 
						'ownerid' => 0, 'created' => $now, 'updated' => $now, 
						'submit_content' => "<div style='margin-bottom: 10px;'>Thank you for taking the time to forward this product to your friends!</div><div><a href='".$redirect."'>Click here to return to the Product details page</a>.</div>",
						'checksum' => ''),
						array(array('id' => 9, 'sort_order' => 0, 'formid' => 'custom', 
						'title' => "__MESSAGE__", 'itype' => 'HiddenField', 'required' => 'N', 
						'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''),
						array('id' => 2, 'sort_order' => 1, 'formid' => 'custom', 
						'title' => '__FROM_EMAIL____REQUIRED__', 'itype' => 'TextBox', 'required' => 'N', 
						'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''),
						array('id' => 1, 'sort_order' => 2, 'formid' => 'custom', 
						'title' => '__FROM_NAME__', 'itype' => 'TextBox', 'required' => 'N', 
						'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''), 
						array('id' => 3, 'sort_order' => 3, 'formid' => 'custom', 
						'title' => "Friend's Email Address 1__EXTRA_RECIPIENT____REQUIRED__", 'itype' => 'TextBox', 'required' => 'N', 
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
						'formid' => 'custom', 'title' => "One of your friends thought you might be interested in a product featured on ".$site_name,
						'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => '')
						)
					);
				}
				// Product Inquiry Form
				/*
				Custom Form implementation
				- Add "__REQUIRED__" to any question title to make the field required
				- Add "__EXTRA_RECIPIENT__" to add the field as a recipient
				- Add "__REDIRECT__" to specify where we are coming from/return URL after form submission
				- Add "__MESSAGE__" to show as a message in the resultant e-mail
				*/	
				if (!Jaws_Error::IsError($info) && isset($info['id'])&& !empty($info['id'])) {
					// Show Vendor Tab
					$recipient = $info['email'];
					$name = $info['nickname'];
					$username = $info['username'];
					$product_vendor_click = "location.href='index.php?user/".$username.".html'";
					$product_vendor_click_text = "More From This Merchant";
					$tpl->SetVariable('VENDOR_CLICK',  $product_vendor_click);
					$tpl->SetVariable('VENDOR_CLICK_TEXT',  $product_vendor_click_text);
					$tpl->SetVariable('vendorDisabled', '');
				} else {
					$recipient = '';
					$tpl->SetVariable('vendorDisabled', '_disabled');
				}
				$product_inquiry_form = $formsLayout->Display(null, true, array('id' => 'customProductInquiry'.$page['id'], 'sort_order' => 0, 'title' => 'Product Inquiry', 
					'sm_description' => '', 'description' => "Send us your questions/comments about this product.", 'clause' => '', 
					'image' => '', 'recipient' => $recipient, 'parent' => 0, 'custom_action' => '', 'fast_url' => '', 'active' => 'Y', 
					'ownerid' => 0, 'created' => $now, 'updated' => $now, 
					'submit_content' => "<div style='margin-bottom: 10px;'>Thank you for taking the time to ask us about this product! We'll review your inquiry and get back to you when necessary.</div><div><a href='".$redirect."'>Click here to return to the Product details page</a>.</div>",
					'checksum' => ''),
					array(array('id' => 9, 'sort_order' => 0, 'formid' => 'custom', 
					'title' => "__MESSAGE__", 'itype' => 'HiddenField', 'required' => 'N', 
					'ownerid' => 0, 'created' => $now, 'updated' => $now, 'checksum' => ''),
					array('id' => 2, 'sort_order' => 1, 'formid' => 'custom', 
					'title' => '__FROM_EMAIL____REQUIRED__', 'itype' => 'TextBox', 'required' => 'N', 
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
					'formid' => 'custom', 'title' => "A message has been received for the following product: ".$page['title'],
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
				
				$tpl->SetVariable('PRODUCT_EMAIL_FORM',  $product_email_form);
				$tpl->SetVariable('PRODUCT_INQUIRY_FORM',  $product_inquiry_form);
				
				$no_details = '';
				if (!empty($product_brandHTML) || !empty($page['product_code']) || !empty($page['sm_description']) || !empty($page['weight']) || !empty($product_cartHTML)) {
					$hasDetails = true;
				}
				if ($hasDetails === false) {
					$no_details = "<style>.product_highlights_bkgnd { display: none; }</style><div style=\"width: 100%; padding: 10px;\">"._t('STORE_NO_LISTING_DETAILS')."</div>";
				}
				$tpl->SetVariable('NO_LISTING_DETAILS',  $no_details);

				// amenity
				$amenity = '';
				if (isset($page['attribute']) && !empty($page['attribute'])) {
					$propAmenities = explode(',', $page['attribute']);
					foreach($propAmenities as $propAmenity) {		            
							$amenityParent = $model->GetAttribute((int)$propAmenity);
							if (!Jaws_Error::IsError($amenityParent)) {
								$amenityType = $model->GetAttributeType($amenityParent['typeid']);
								if (!Jaws_Error::IsError($amenityType) && $amenityType['itype'] != 'HiddenField' && $amenityType['itype'] != 'TextBox' && $amenityType['itype'] != 'TextArea') {
									if ($amenity != '') {
										$amenity .= ' ';
									}
									$amenity .= ' <nobr><img border="0" style="padding-left: 10px;" src="images/ICON_chkbox.gif">&nbsp;<a href="'.$GLOBALS['app']->getSiteURL().'/index.php?gadget=Store&action=Category&Store_id=all&Store_attributes='.urlencode($GLOBALS['app']->UTF8->str_replace('"', '%22', $xss->filter(strip_tags($amenityParent['feature'])))).'">'.$xss->filter($amenityParent['feature']).'</a></nobr>';;
								}
							}
					}
					$tpl->SetBlock('product/content/attribute');
					$tpl->SetVariable('attribute', $amenity);
					$tpl->ParseBlock('product/content/attribute');
				}
				
				// description
				if (isset($page['description']) && !empty($page['description'])) {
					$tpl->SetBlock('product/content/description');
					$tpl->SetVariable('description', strip_tags($this->ParseText($page['description'], 'Store'), '<strong><li><ul><ol><em><i><p><b><a><img><br>'));
					$tpl->ParseBlock('product/content/description');
				}
				
				// Recurrent subscriptions chart
				if (isset($page['recurring']) && $page['recurring'] == 'Y') {
					$tpl->SetBlock('product/content/subscriptions_chart');
					$tpl->ParseBlock('product/content/subscriptions_chart');
				}

				// contact information
				$user_profile = '';
				if ((isset($page['contact']) && !empty($page['contact'])) || (isset($page['company']) && !empty($page['company'])) || $page['ownerid'] > 0) {
					// Owner ID details
					if ((int)$page['ownerid'] > 0) {
						if (isset($info['id'])) {
							if (isset($info['company']) && !empty($info['company'])) {
								$page['contact'] = $info['company'];
							} else if (isset($info['nickname']) && !empty($info['nickname'])) {
								$page['contact'] = $info['nickname'];
							}							
							if (isset($info['url']) && !empty($info['url'])) {
								$page['contact_website'] = $info['url'];
							}
							if (isset($info['office']) && !empty($info['office'])) {
								$page['contact_phone'] = $info['office'];
							} else if (isset($info['phone']) && !empty($info['phone'])) {
								$page['contact_phone'] = $info['phone'];
							} else if (isset($info['tollfree']) && !empty($info['tollfree'])) {
								$page['contact_phone'] = $info['tollfree'];
							}
							if (isset($info['logo']) && !empty($info['logo'])) {
								$page['company_logo'] = $info['logo'];
							}
							// has a public profile page with products?
							$gadget = $GLOBALS['app']->LoadGadget('Store', 'HTML');
							if (
								!Jaws_Error::IsError($gadget) && method_exists($gadget, 'account_profile') && 
								in_array('Store', explode(',', $GLOBALS['app']->Registry->Get('/gadgets/user_access_items')))
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
					
					$tpl->SetBlock('product/content/contact');
					
					$agent_html = '';
					if (isset($page['contact']) && !empty($page['contact'])) {
						$agent_html .= '<nobr>Listed by: <b>'.(!empty($user_profile) ? '<a href="'.$user_profile.'">' : ($page['ownerid'] > 0 ? '<a href="index.php?gadget=Store&action=Category&id=all&owner_id='.$page['ownerid'].'">' : '')).$xss->defilter(strip_tags($page['contact'])).(!empty($user_profile) || $page['ownerid'] > 0 ? '</a>' : '').'</b></nobr>';
					}
					$tpl->SetVariable('contact', $agent_html);
					
					$agent_website = '';
					$agent_website_html = '';
					if (isset($page['contact_website']) && !empty($page['contact_website'])) {
						$agent_website = $GLOBALS['app']->UTF8->str_replace('"', '%22', $xss->defilter(strip_tags($page['contact_website'])));
						$agent_website_html .= '<br /><nobr>Website: <a href="'.$agent_website.'" target="_blank">'.$agent_website.'</a></nobr>';
					} else if (isset($page['company_website']) && !empty($page['company_website'])) {
						$agent_website = $GLOBALS['app']->UTF8->str_replace('"', '%22', $xss->defilter(strip_tags($page['company_website'])));
						$agent_website_html .= '<br /><nobr>Website: <a href="'.$agent_website.'" target="_blank">'.$agent_website.'</a></nobr>';
					}
					$tpl->SetVariable('contact_website', $agent_website_html);
					
					$broker_html = '';
					if (isset($page['company']) && !empty($page['company'])) {
						//$broker_html .= ($agent_website != '' ? '<a href="'.$agent_website.'" target="_blank">' : '').$xss->defilter(strip_tags(str_replace('&nbsp;', ' ', $page['company']))).($agent_website != '' ? '</a>' : '');
						$broker_html .= '<br />'.($agent_html != '' ? '<nobr>of ' : '<nobr><b>').($agent_website != '' ? '<a href="'.$agent_website.'" target="_blank">' : '').$xss->defilter(strip_tags(str_replace('&nbsp;', ' ', $page['company']))).($agent_website != '' ? '</a>' : '').($agent_html != '' ? '' : '</b>').'</nobr>';
					}
					$tpl->SetVariable('company', $broker_html);
					
					$agent_phone_html = '';
					if (isset($page['agent_phone']) && !empty($page['contact_phone']) && strpos($page['contact_phone'], "://") === false) {
						$agent_phone_html .= '<br /><nobr>Phone: '.$xss->defilter(strip_tags($page['contact_phone'])).'</nobr>';
					} else if (isset($page['company_phone']) && !empty($page['company_phone']) && strpos($page['company_phone'], "://") === false) {
						$agent_phone_html .= '<br /><nobr>Phone: '.$xss->defilter(strip_tags($page['company_phone'])).'</nobr>';
					}
					$tpl->SetVariable('contact_phone', $agent_phone_html);
					
					$agent_email_html = '';
					if (isset($page['contact_email']) && !empty($page['contact_email'])) {
						$agent_email_html .= '<br /><nobr>E-mail: '.$xss->defilter(strip_tags($page['contact_email'])).'</nobr>';
					} else if (isset($page['company_email']) && !empty($page['company_email'])) {
						$agent_email_html .= '<br /><nobr>E-mail: '.$xss->defilter(strip_tags($page['company_email'])).'</nobr>';
					}
					$tpl->SetVariable('contact_email', $agent_email_html);
					
					$broker_logo_src = '';
					if (!empty($page['company_logo']) && isset($page['company_logo'])) {
						$page['company_logo'] = $xss->parse(strip_tags($page['company_logo']));
						if (strpos($page['company_logo'],".swf") !== false) {
							// Flash file not supported
						} else if (substr($page['company_logo'],0,7) == "GADGET:") {
							$broker_logo_src = $page['company_logo'];
						} else {
							$broker_logo_src = $page['company_logo'];
						}
					}
					if (!empty($page['contact_photo']) && isset($page['contact_photo'])) {
						$page['contact_photo'] = $xss->parse(strip_tags($page['contact_photo']));
						if (strpos($page['contact_photo'],".swf") !== false) {
							// Flash file not supported
						} else if (substr($page['contact_photo'],0,7) == "GADGET:") {
							$broker_logo_src = $page['contact_photo'];
						} else {
							$broker_logo_src = $page['contact_photo'];
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
							if (strpos(strtolower($broker_logo), 'data/files/') !== false) {
								$broker_logo = 'image_thumb.php?uri='.urlencode($broker_logo);
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
							$broker_logo = (!empty($user_profile) ? '<a href="'.$user_profile.'">' : ($page['ownerid'] > 0 ? '<a href="index.php?gadget=Store&action=Category&id=all&owner_id='.$page['ownerid'].'">' : '')).'<img style="padding-right: 10px; padding-bottom: 10px; align="left" border="0" src="'.$broker_logo.'" width="100" '.(strtolower(substr($broker_logo, -3)) == "gif" || strtolower(substr($broker_logo, -3)) == "png" || strtolower(substr($broker_logo, -3)) == "bmp" ? 'height="100"' : '').' />'.(!empty($user_profile) || $page['ownerid'] > 0 ? '</a>' : '');				
						}
					}
					$tpl->SetVariable('company_logo', $broker_logo);
					
					$tpl->ParseBlock('product/content/contact');
				}

				// send Post records
				$posts = $model->GetAllPostsOfProduct($page['id']);
				$carouselNav = '';
				
				if (!Jaws_Error::IsError($posts)) {
					if (!empty($image_src) && !count($posts) <= 0) {
						reset($posts);
						$carouselItems = '';
						if ($post['action'] == 'PrintProductDetails') {
							$tpl->SetBlock('product/content/image_grid');
							$n = 1;
							$mainImage = '<div class="carousel_item" id="carousel_item'.$n.'"><a href="javascript:void(0);" onclick="window.open(\''.$image_src.'\');"><img id="carousel_item'.$n.'Image" class="carousel_itemImage" border="0" src="'.$image_src.'" alt="'.(isset($page['title']) ? $page['title'] : '').'" title="'.(isset($page['title']) ? $page['title'] : '').'"></a></div>';						
							$tpl->SetVariable('mainImage', $mainImage);
							foreach($posts as $post) {		            
								/*
								$tpl->SetVariable('productparentImage', $xss->defilter(strip_tags($parent['productparentimage'])));
								$tpl->SetVariable('productparentDescription', strip_tags($this->ParseText($post['description'], 'Store')));
								$tpl->SetVariable('productparentActive', $xss->defilter($parent['productparentactive']));
								$submit_vars[SYNTACTS_DB ."2:$j:$i"] = ($e == 'description') ? $this->ParseText($ev, 'Store') : $xss->defilter($ev);
								*/
								$post_src = '';
								if (isset($post['image']) && !empty($post['image'])) {
									$post['image'] = $xss->parse(strip_tags($post['image']));
									if (substr(strtolower($post['image']), 0, 4) == "http") {
										$post_src = $post['image'];
										if (substr(strtolower($post['image']), 0, 7) == "http://") {
											$post_src = explode('http://', $post_src);
											foreach ($post_src as $img_src) {
												if (!empty($img_src)) {
													$post_src = 'http://'.$img_src;
													break;
												}
											}
										} else {
											$post_src = explode('https://', $post_src);
											foreach ($post_src as $img_src) {
												if (!empty($img_src)) {
													$post_src = 'https://'.$img_src;
													break;
												}
											}
										}
										if (strpos(strtolower($post_src), 'data/files/') !== false) {
											$post_src = 'image_thumb.php?uri='.urlencode($post_src);
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
										$carouselItems .= ' border="0" src="'.$post_src.'" '.(!empty($post['description']) ? 'alt="'.strip_tags($this->ParseText($post['description'], 'Store')).'" title="'.strip_tags($this->ParseText($post['description'], 'Store')).'"' : '').' /></a>'.(!empty($post['title']) ? '<div style="text-align: center;">'.$xss->defilter(strip_tags($post['title'])).'</div>' : '').'</div>';
										$n++;
									}
								}
							}
							$tpl->SetVariable('carouselItems', $carouselItems);
							$tpl->ParseBlock('product/content/image_grid');
						} else {
							$tpl->SetBlock('product/content/carousel');
							$n = 1;
							$mainImage = '<div class="carousel_item" id="carousel_item'.$n.'"><a href="javascript:void(0);" onclick="window.open(\''.$image_src.'\');"><img id="carousel_item'.$n.'Image" class="carousel_itemImage" border="0" src="'.$image_src.'" /></a></div>';						
							$tpl->SetVariable('mainImage', $mainImage);
							foreach($posts as $post) {		            
								/*
								$tpl->SetVariable('productparentImage', $xss->defilter(strip_tags($parent['productparentimage'])));
								$tpl->SetVariable('productparentDescription', strip_tags($this->ParseText($post['description'], 'Store')));
								$tpl->SetVariable('productparentActive', $xss->defilter($parent['productparentactive']));
								$submit_vars[SYNTACTS_DB ."2:$j:$i"] = ($e == 'description') ? $this->ParseText($ev, 'Store') : $xss->defilter($ev);
								*/
								$post_src = '';
								if (isset($post['image']) && !empty($post['image'])) {
									$post['image'] = $xss->defilter(strip_tags($post['image']));
									if (substr(strtolower($post['image']), 0, 4) == "http") {
										$post_src = $post['image'];
										if (substr(strtolower($post['image']), 0, 7) == "http://") {
											$post_src = explode('http://', $post_src);
											foreach ($post_src as $img_src) {
												if (!empty($img_src)) {
													$post_src = 'http://'.$img_src;
													break;
												}
											}
										} else {
											$post_src = explode('https://', $post_src);
											foreach ($post_src as $img_src) {
												if (!empty($img_src)) {
													$post_src = 'https://'.$img_src;
													break;
												}
											}
										}
										if (strpos(strtolower($post_src), 'data/files/') !== false) {
											$post_src = 'image_thumb.php?uri='.urlencode($post_src);
										}
									} else {
										$medium = Jaws_Image::GetMediumPath($post['image']);
										if (file_exists(JAWS_DATA . 'files'.$medium)) {
											$post_src = $GLOBALS['app']->getDataURL() . 'files'.$medium;
										} else if (file_exists(JAWS_DATA . 'files'.$post['image'])) {
											$post_src = $GLOBALS['app']->getDataURL() . 'files'.$post['image'];
										}
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
									$carouselItems .= ' border="0" src="'.$post_src.'" '.(!empty($post['description']) ? 'alt="'.strip_tags($this->ParseText($post['description'], 'Store')).'" title="'.strip_tags($this->ParseText($post['description'], 'Store')).'"' : '').' /></a>'.(!empty($post['title']) ? '<div style="text-align: center;">'.$xss->defilter(strip_tags($post['title'])).'</div>' : '').'</div>'."\n";
									$carouselNav .= '<a id="carousel_nav'.$n.'" href="javascript: void(0);" onclick="hCarousel.scrollTo('.$n.');" style="text-decoration: none;"><img src="images/carousel_nav_off.png" border="0" /></a>';
									$n++;
								}
							}
							$tpl->SetVariable('carouselItems', $carouselItems);
							/*
							if (strpos(strtolower($page['i360']), "tour.getmytour.com") === false) {
								$tpl->SetVariable('startScroll', "startscroll();");
							}
							*/
							$tpl->SetVariable('carouselNav', $carouselNav);
							$tpl->ParseBlock('product/content/carousel');
						}
					} else if (!empty($image_src)) {
						$tpl->SetBlock('product/content/image');
						$tpl->SetVariable('lg_imageSrc', $lg_image_src);
						$tpl->SetVariable('imageSrc', $image_src);
						$tpl->ParseBlock('product/content/image');
					} else {
						if (
							(!file_exists(JAWS_DATA . 'files'.$page['image'])) || 
							(empty($page['image']) && strpos(strtolower($page['description']), "img") === false)
						) {
							$tpl->SetBlock('product/content/no_image');
							$tpl->ParseBlock('product/content/no_image');
						}
					}
					
				} else {
					//$page_content = _t('STORE_ERROR_ASPPAGE_NOT_RETRIEVED', $posts->GetMessage())."\n";
					return new Jaws_Error(_t('STORE_ERROR_ASPPAGE_NOT_RETRIEVED', $posts->GetMessage()), _t('STORE_NAME'));
				}
				
				// external links
				if (!empty($page['alink']) || !empty($page['alink2']) || !empty($page['alink3'])) {
					$tpl->SetBlock('product/content/external_links');
					if (!empty($page['alink']) && !empty($page['alinktype'])) {
						$alink = '<br /><a href="'.($page['alinktype'] == 'M' ? 'mailto:' : 'http://').$GLOBALS['app']->UTF8->str_replace('"', '%22', $xss->defilter(strip_tags($page['alink']))).'" target="_blank">'.(!empty($page['alinktitle']) ? $xss->defilter(strip_tags($page['alinktitle'])) : $xss->defilter(strip_tags($page['alink']))).'</a>';
						$tpl->SetVariable('alink', $alink);
					}
					if (!empty($page['alink2']) && !empty($page['alink2type'])) {
						$alink2 = '<br /><a href="'.($page['alink2type'] == 'M' ? 'mailto:' : 'http://').$GLOBALS['app']->UTF8->str_replace('"', '%22', $xss->defilter(strip_tags($page['alink2']))).'" target="_blank">'.(!empty($page['alink2title']) ? $xss->defilter(strip_tags($page['alink2title'])) : $xss->defilter(strip_tags($page['alink2']))).'</a>';
						$tpl->SetVariable('alink2', $alink2);
					}
					if (!empty($page['alink3']) && !empty($page['alink3type'])) {
						$alink2 = '<br /><a href="'.($page['alink3type'] == 'M' ? 'mailto:' : 'http://').$GLOBALS['app']->UTF8->str_replace('"', '%22', $xss->defilter(strip_tags($page['alink3']))).'" target="_blank">'.(!empty($page['alink3title']) ? $xss->defilter(strip_tags($page['alink3title'])) : $xss->defilter(strip_tags($page['alink3']))).'</a>';
						$tpl->SetVariable('alink3', $alink3);
					}
					$tpl->ParseBlock('product/content/external_links');
				}
				
				$tpl->SetVariable('pagetype', 'product');
				$tpl->ParseBlock('product/content');
				
				if ($embedded == true && !is_null($referer) && isset($page['id'])) {	
					$tpl->SetBlock('product/embedded');
					$tpl->SetVariable('id', $page['id']);		        
					if (strtolower($referer) == strtolower($_SERVER['SERVER_NAME'])) {
						$tpl->SetVariable('referer', $GLOBALS['app']->GetJawsURL() . "/libraries/iframes/domain1/iframetest_resize1.html");		        
					} else {	
						$tpl->SetVariable('referer', "http://".$referer."/19/img/agents/19/custom_img/common_files/iframetest_resize1.html");		        
					}
					$tpl->ParseBlock('product/embedded');
				} else {
					$tpl->SetBlock('product/not_embedded');
					$tpl->SetVariable('id', $page['id']);		        
					$tpl->ParseBlock('product/not_embedded');
				}
			}
		}
		// Statistics Code
		$tpl->SetBlock('product/stats');
		$GLOBALS['app']->Registry->LoadFile('CustomPage');
		$tpl->SetVariable('stats', html_entity_decode($GLOBALS['app']->Registry->Get('/gadgets/CustomPage/googleanalytics_code')));		        
		$tpl->ParseBlock('product/stats');

        $tpl->ParseBlock('product');

        return $tpl->Get();
    }

    /**
     * Displays an index of available pages.
     *
     * @access public
     * @return string
     */
    function Index($pid = null)
    {
        $layoutGadget = $GLOBALS['app']->LoadGadget('Store', 'LayoutHTML');
        return $layoutGadget->Index($pid);
    }

    /**
     * Displays an index of available pages.
     *
     * @access public
     * @return string
     */
    function BrandIndex()
    {
        $layoutGadget = $GLOBALS['app']->LoadGadget('Store', 'LayoutHTML');
        return $layoutGadget->BrandIndex();
    }

    /**
     * Displays an XML file of product categories
     *
     * @access public
     * @return string
     */
    function CategoryRSS()
    {
		header("Content-type: text/xml");
		$output_xml = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>\n"; 
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');

		$request =& Jaws_Request::getInstance();
        $get = $request->get(array('id', 'Store_id', 'showcase_id'), 'get');

		$searchkeyword = $request->get('Store_keyword', 'post');
		if (empty($searchkeyword)) {
			$searchkeyword = $request->get('Store_keyword', 'get');
		}
		$searchbrand = $request->get('Store_brand', 'post');
		if (empty($searchbrand)) {
			$searchbrand = $request->get('Store_brand', 'get');
		}
		$searchcategory = $request->get('Store_category', 'post');
		if (empty($searchcategory)) {
			$searchcategory = $request->get('Store_category', 'get');
		}
		$preview = $request->get('preview', 'post');
		if (empty($preview)) {
			$preview = $request->get('preview', 'get');
		}
		$sortColumn = $request->get('Store_sortColumn', 'post');
		if (empty($sortColumn)) {
			$sortColumn = $request->get('Store_sortColumn', 'get');
		}
		$sortDir = $request->get('Store_sortDir', 'post');
		if (empty($sortDir)) {
			$sortDir = $request->get('Store_sortDir', 'get');
		}
		$searchattributes = $request->get('Store_attributes', 'post');
		if (empty($searchattributes)) {
			$searchattributes = $request->get('Store_attributes', 'get');
		}
        //$post['showcase_id'] = $xss->defilter($post['showcase_id']);

		//if(!empty($post['showcase_id'])) {
		//	$agentID = $post['showcase_id'];
		//}
		  
		if((!empty($get['id']) || !empty($get['Store_id'])) || !empty($searchattributes)) {
			$gid = (!empty($get['Store_id']) ? $get['Store_id'] : $get['id']);

	        $model = $GLOBALS['app']->LoadGadget('Store', 'Model');
			if (strtolower($gid) != 'all') {
				$parent = $model->GetProductParent((int)$gid);
				if (Jaws_Error::IsError($parent)) {
					require_once JAWS_PATH . 'include/Jaws/HTTPError.php';
					return Jaws_HTTPError::Get(404);
				}
			}
			$output_xml .= "<markers>\n";
			$adminmodel = $GLOBALS['app']->LoadGadget('Store', 'AdminModel');
			if (!empty($searchstatus) || !empty($searchkeyword) || !empty($searchbrand) || !empty($searchcategory) || !empty($searchattributes) || !empty($sortColumn) || !empty($sortDir) && !empty($parent['productparentid']) && strtolower($gid) != "all") {
				$galleryPosts = $adminmodel->MultipleSearchProducts($searchkeyword, $searchbrand, $searchcategory, $searchattributes, null, null, $gid, $sortColumn, $sortDir, 'Y');
			} else if (strtolower($gid) == "all") {
				$galleryPosts = $adminmodel->MultipleSearchProducts($searchkeyword, $searchbrand, $searchcategory, $searchattributes, null, null, null, $sortColumn, $sortDir, 'Y');
			} else {
				$galleryPosts = $model->GetAllProductsOfParent($gid, $sortColumn, $sortDir, 'Y');
			}
			if (!$galleryPosts || Jaws_Error::IsError($galleryPosts)) {
				if (isset($GLOBALS['log'])) {
					$GLOBALS['log']->Log(JAWS_LOG_ERR, "No locations were found for map ID: $gid");
				}
			} else {
				$i = 1;
				foreach($galleryPosts as $parents) {		            
					// Only show first 20 products on map
					if ((!empty($parents['address']) || (!empty($parents['city']) && !empty($parents['region']))) && !empty($parents['coordinates']) && $i < 21) {
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
							$description = $this->ParseText($parents['description'], 'Store');
							$description = trim(preg_replace('/\s*\[[^)]*\]/', '', $description));
						}
						if (isset($parents['image'])) {
							$image = $GLOBALS['app']->getDataURL() . 'files'.$xss->defilter($parents['image']);
							if (file_exists($image) && substr($image, -5) != "files") {
								$image_exists = "<img border=\"0\" src=\"".$image."\" width=\"150\" />";
								$image_style = "";
							}
						}
						$marker_html .= "<div style=\"".$image_style."clear: left;\">".$image_exists."</div>";
						$marker_html .= "<div style=\"clear: left;\"><b>".(isset($parents['title']) ? $parents['title'] : 'My Location')."</b><br />".$info_address."<hr /><br />".$description."</div>";
						$marker_html .= "<div style=\"clear: both;\">&nbsp;</div>";
						*/
						
						$output_xml .=  "	<marker address=\"".$parents['coordinates']."\" title=\"".$i."\" ext=\"".(isset($parents['title']) ? $xss->defilter($parents['title']) : 'My Location')."\" url=\"".$GLOBALS['app']->Map->GetURLFor('Store', 'Product', array('id' => $parents['fast_url']))."\" target=\"_self\" fs=\"10\" sfs=\"6\" bw=\"2\" ra=\"9\" fc=\"FFFFFF\" fg=\"666666\" bc=\"FFFFFF\" hfc=\"222222\" hfg=\"FFFFFF\" hbc=\"666666\"><![CDATA[ ".$marker_html." ]]></marker>\n";
						$i++;
					}
				}
				// reset xml output if no addresses were added
				/*
				if (!strpos($output_xml, "marker address")) {
					$output_xml = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>\n";
				}
				*/
			}
			$output_xml .= "</markers>\n";
		} else {
			if (isset($GLOBALS['log'])) {
				$GLOBALS['log']->Log(JAWS_LOG_ERR, "No products were found for this category.");
			}
		}
		return $output_xml;
	}
    
    /**
     * Displays an XML file of products
     *
     * @access public
     * @return string
     */
    function ProductRSS()
    {
		//header("Content-type: text/xml");
		$output_xml = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>\n"; 
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');

		$request =& Jaws_Request::getInstance();
        $get = $request->get(array('id', 'showcase_id'), 'get');

        //$post['showcase_id'] = $xss->defilter($post['showcase_id']);

		//if(!empty($post['showcase_id'])) {
		//	$agentID = $post['showcase_id'];
		//}
		  
		if(!empty($get['id'])) {
			$gid = $get['id'];

	        $model = $GLOBALS['app']->LoadGadget('Store', 'Model');
			$parents = $model->GetProduct($gid);
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
					$description = $this->ParseText(substr(strip_tags($parents['description']), 0, 250), 'Store');
					$description = trim(preg_replace('/\s*\[[^)]*\]/', '', $description));
				}
				$main_image_src = '';
				if (!empty($parents['image']) && isset($parents['image'])) {
					$parents['image'] = $xss->parse(strip_tags($parents['image']));
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
						if (strpos(strtolower($main_image_src), 'data/files/') !== false) {
							$main_image_src = 'image_thumb.php?uri='.urlencode($main_image_src);
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
					$key = "ABQIAAAAbHKtlYQg6w5AJ9d2_shprxRSdpoJbNiUEES6uLAQrtqeLOB2WBROsVk6Deve8XT-33SEgoVpReKw5Q";
					include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
					include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'XMLParser.php';
					// snoopy
					$snoopy = new Snoopy('Store');
					$snoopy->agent = "Jaws";
					$geocode_url = "http://maps.google.com/maps/geo?q=".urlencode($marker_address)."&output=xml&key=".$key;
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
							if ($xml_result[0][0]['CODE'] == '200' && isset($xml_result[0][$i]['COUNTRYNAMECODE']) && isset($xml_result[0][$i]['ADMINISTRATIVEAREANAME']) && isset($xml_result[0][$i]['LOCALITYNAME']) && isset($xml_result[0][$i]['ADDRESS']) && isset($xml_result[0][$i]['COORDINATES']) && empty($parents['coordinates'])) {
								//if (isset($xml_result[0][$i]['COORDINATES'])) {
									$parents['coordinates'] = $xml_result[0][$i]['COORDINATES'];
								//}
							}
						}
					}
				}
				
				$marker_html = "<div style=\"clear: left;\">".$image_exists."<b>".(isset($parents['title']) ? $parents['title'] : 'My Location')."</b><br />".$info_address."<hr /><br />".$description."</div>";
				$marker_html .= "<div style=\"clear: both;\">&nbsp;</div>";
				if (!empty($parents['coordinates'])) {
					$output_xml .=  "	<marker address=\"".$parents['coordinates']."\" ext=\"".(isset($parents['title']) ? $parents['title'] : 'My Location')."\" sub=\"".$marker_address."\" title=\"".(isset($parents['title']) ? $parents['title'] : 'My Location')."\" url=\"\" target=\"infowindow\" fs=\"10\" sfs=\"8\" bw=\"2\" ra=\"9\" fc=\"FFFFFF\" fg=\"666666\" bc=\"FFFFFF\" hfc=\"222222\" hfg=\"FFFFFF\" hbc=\"666666\"><![CDATA[ ".$marker_html." ]]></marker>\n";
				}
			}
			$output_xml .= "</markers>\n";
		} else {
			if (isset($GLOBALS['log'])) {
				$GLOBALS['log']->Log(JAWS_LOG_ERR, "Product could not be found.");
			}
		}
		return $output_xml;
	}

    /**
     * View products by Attribute.
     *
     * @param 	int 	$aid 	Atttribute ID (optional)
     * @access 	public
     * @return 	string
     */
    function Attribute($aid = null)
    {
		$request =& Jaws_Request::getInstance();
        $get = $request->get(array('id'), 'get');		
		if (is_null($aid)) {
			$aid = $get['id'];
		}
		return $this->Category(null, false, null, $aid);
    }

    /**
     * View products by Brand.
     *
     * @param 	int 	$bid 	Brand ID (optional)
     * @access 	public
     * @return 	string
     */
    function Brand($bid = null)
    {
		$request =& Jaws_Request::getInstance();
        $get = $request->get(array('id'), 'get');
		if (is_null($bid)) {
			$bid = $get['id'];
		}
        return $this->Category(null, false, null, '', '', $bid);
    }

    /**
     * View products by Sale.
     *
     * @param 	int 	$sid 	Sale ID (optional)
     * @access 	public
     * @return 	string
     */
    function Sale($sid = null)
    {
		$request =& Jaws_Request::getInstance();
        $get = $request->get(array('id'), 'get');
		if (is_null($sid)) {
			$sid = $get['id'];
		}
		return $this->Category(null, false, null, '', $sid);
    }

    /**
     * Embed products in external sites.
     *
     * @category 	feature
     * @access 	public
     * @return 	string
     */
    function EmbedProduct()
    {
		$request =& Jaws_Request::getInstance();
        $get = $request->get(array('id', 'mode', 'uid', 'referer', 'css'), 'get');
        $output_html = "";
		
        //$output_html .= "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
		$output_html .= "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\">\n";
		$output_html .= " <head>\n";
		$output_html .= "  <title>Products</title>\n";
		$output_html .= "  <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n";
        $theme = $GLOBALS['app']->Registry->Get('/config/theme');
		$themeHREF = (strpos($theme, 'http://') !== false ? $theme : $GLOBALS['app']->getDataURL('', true) . "themes/" . $theme);
		$output_html .= "	<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" href=\"". $themeHREF . "/style.css\" />\n";
		$output_html .= "	<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" href=\"". $GLOBALS['app']->getDataURL('', true) . "files/css/custom.css\" />\n";
		$output_html .= "	<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" href=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/Store/resources/style.css\" />\n";
		if (isset($get['css'])) {
			$output_html .= "	<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" href=\"".$get['css']."\" />\n";
		}
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/prototype/prototype.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/prototype/scriptaculous.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/prototype/effects.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/prototype/controls.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/include/Jaws/Ajax/Response.js\"></script>\n";
		//$output_html .= "	<script type=\"text/javascript\" src=\"index.php?gadget=Store&amp;action=Ajax&amp;client\"></script>\n";
		//$output_html .= "	<script type=\"text/javascript\" src=\"index.php?gadget=Store&amp;action=AjaxCommonFiles\"></script>\n";
		//$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/Store/resources/client_script.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/js/admin.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"http://yui.yahooapis.com/2.4.0/build/yahoo-dom-event/yahoo-dom-event.js\"></script>\n";
		$GLOBALS['app']->Registry->LoadFile('Maps');
		//$GLOBALS['app']->Registry->LoadFile('Store');
		$output_html .= " </head>\n";
		$display_id = md5($this->_Name.$get['id']);
		$output_html .= " <body style=\"background: url();\" onLoad=\"sizeFrame".$display_id."(); document.getElementById('".$this->_Name."-editDivStretch-".$display_id."').style.width = (parseInt(document.getElementById('".$this->_Name."-editDiv-".$display_id."').offsetWidth)-20)+'px';\">\n";
		if (isset($get['id']) && (isset($get['referer']) || $GLOBALS['app']->Session->GetAttribute('gadget_referer'))) {
			$referer = (isset($get['referer']) ? $get['referer'] : $GLOBALS['app']->Session->GetAttribute('gadget_referer'));
			$output_html .= " <style>\n";
			$output_html .= "   #".$this->_Name."-editDiv-".$display_id." { width: 100%; text-align: right; }\n";
			$output_html .= "   #".$this->_Name."-edit-".$display_id." { display: block; width:20px; height:20px; overflow:hidden; }\n";
			$output_html .= "   #".$this->_Name."-edit-".$display_id.":hover { width: 118px; }\n";
			$output_html .= " </style>\n";
			if ($get['mode'] == 'list') {
				$output_html .= " <div id=\"".$this->_Name."-editDiv-".$display_id."\"><div id=\"".$this->_Name."-editDivStretch-".$display_id."\" style=\"float: left;\">&nbsp;</div><div style=\"float: left;\"><a onMouseOver=\"document.getElementById('".$this->_Name."-editDivStretch-".$display_id."').style.width = (parseInt(document.getElementById('".$this->_Name."-editDiv-".$display_id."').offsetWidth)-118)+'px';\" onMouseOut=\"document.getElementById('".$this->_Name."-editDivStretch-".$display_id."').style.width = (parseInt(document.getElementById('".$this->_Name."-editDiv-".$display_id."').offsetWidth)-20)+'px';\" id=\"".$this->_Name."-edit-".$display_id."\" target=\"_blank\" href=\"".$GLOBALS['app']->Map->GetURLFor('Users', 'DefaultAction')."\" title=\"Edit This Gadget\"><img border=\"0\" src=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/Users/images/edit_gadget.png\" /></a></div><div style=\"font-size: 0.1em; clear: both;\">&nbsp;</div></div>";
				$layoutGadget = $GLOBALS['app']->LoadGadget('Store', 'LayoutHTML');
				if (isset($get['uid'])) {
					$output_html .= $layoutGadget->Index((int)$get['uid'], true, $referer);
				} else {
					$output_html .= $layoutGadget->Index(null, true, $referer);
				}
			} else {
				$output_html .= " <div id=\"".$this->_Name."-editDiv-".$display_id."\"><div id=\"".$this->_Name."-editDivStretch-".$display_id."\" style=\"float: left;\">&nbsp;</div><div style=\"float: left;\"><a onMouseOver=\"document.getElementById('".$this->_Name."-editDivStretch-".$display_id."').style.width = (parseInt(document.getElementById('".$this->_Name."-editDiv-".$display_id."').offsetWidth)-118)+'px';\" onMouseOut=\"document.getElementById('".$this->_Name."-editDivStretch-".$display_id."').style.width = (parseInt(document.getElementById('".$this->_Name."-editDiv-".$display_id."').offsetWidth)-20)+'px';\" id=\"".$this->_Name."-edit-".$display_id."\" target=\"_blank\" href=\"".$GLOBALS['app']->GetSiteURL()."/index.php?gadget=Store&action=account_A&id=".$get['id']."\" title=\"Edit This Gadget\"><img border=\"0\" src=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/Users/images/edit_gadget.png\" /></a></div><div style=\"font-size: 0.1em; clear: both;\">&nbsp;</div></div>";
				$output_html .= $this->Product((int)$get['id'], true, $referer);
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
     * Allow users (members) to create and subscribe to Products.
     *
     * @category 	feature
     * @param 	int  $user  User ID
     * @access 	public
     * @return 	string
     */
    function UserStore($user)
    {			
		require_once JAWS_PATH . 'include/Jaws/User.php';
        $jUser = new Jaws_User;
		
		require_once JAWS_PATH . 'include/Jaws/Template.php';
        $tpl = new Jaws_Template('gadgets/Store/templates/');
        $tpl->Load('users.html');
		$tpl->SetVariable('title', $this->_Name);
		$tpl->SetVariable('pane_id', str_replace(" ",'',$this->_Name));
		$tpl->SetBlock('pane/pane_item');
		$tpl->SetVariable('pane_id', str_replace(" ",'',$this->_Name));
		$tpl->SetVariable('pane', 'UserStore');
		$tpl->SetVariable('icon', $GLOBALS['app']->GetJawsURL() . '/gadgets/'.$this->_Name.'/images/logo.png');
        
		$stpl = new Jaws_Template('gadgets/Store/templates/');
        $stpl->Load('users.html');
        $stpl->SetBlock('UserStoreSubscriptions');
		$status = $jUser->GetStatusOfUserInGroup($GLOBALS['app']->Session->GetAttribute('user_id'), 'store_owners');
		$usersHTML = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$page = $usersHTML->ShowComments('Store', false, null, 'Store', (in_array($status, array('active','admin','founder')) ? true : false));
		if (!Jaws_Error::IsError($page)) {
			$stpl->SetVariable('element', $page);
		} else {
			$stpl->SetVariable('element', _t('GLOBAL_ERROR_GET_ACCOUNT_PANE'));
		}
        $stpl->ParseBlock('UserStoreSubscriptions');

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
		$gadget_admin = $GLOBALS['app']->LoadGadget('Store', 'AdminHTML');
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
		$gadget_admin = $GLOBALS['app']->LoadGadget('Store', 'AdminHTML');
		$page = $gadget_admin->form(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$html_output = $users_html->GetAccountHTML('Store');
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
		$gadget_admin = $GLOBALS['app']->LoadGadget('Store', 'AdminHTML');
		$page = $gadget_admin->form_post(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$output_html = str_replace("__JAWS_GADGET__", $page, $users_html->GetAccountHTML('Store'));
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
		$gadget_admin = $GLOBALS['app']->LoadGadget('Store', 'AdminHTML');
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
		$gadget_admin = $GLOBALS['app']->LoadGadget('Store', 'AdminHTML');
		$page = $gadget_admin->A_form(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$html_output = $users_html->GetAccountHTML('Store');
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
		$gadget_admin = $GLOBALS['app']->LoadGadget('Store', 'AdminHTML');
		$page = $gadget_admin->A_form_post(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$output_html = str_replace("__JAWS_GADGET__", $page, $users_html->GetAccountHTML('Store'));
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
		$gadget_admin = $GLOBALS['app']->LoadGadget('Store', 'AdminHTML');
		$page = $gadget_admin->A_form2(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$html_output = $users_html->GetAccountHTML('Store');
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
		$gadget_admin = $GLOBALS['app']->LoadGadget('Store', 'AdminHTML');
		$page = $gadget_admin->A_form_post2(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$output_html = str_replace("__JAWS_GADGET__", $page, $users_html->GetAccountHTML('Store'));
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
		$gadget_admin = $GLOBALS['app']->LoadGadget('Store', 'AdminHTML');
		$page = $gadget_admin->B(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$html_output = $users_html->GetAccountHTML('Store');
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
		$gadget_admin = $GLOBALS['app']->LoadGadget('Store', 'AdminHTML');
		$page = $gadget_admin->B_form(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$html_output = $users_html->GetAccountHTML('Store');
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
		$gadget_admin = $GLOBALS['app']->LoadGadget('Store', 'AdminHTML');
		$page = $gadget_admin->B_form_post(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$output_html = str_replace("__JAWS_GADGET__", $page, $users_html->GetAccountHTML('Store'));
		return $output_html;
    }
	
    /**
     * Account B2
     *
     * @access public
     * @return string
     */
    function account_B2()
    {
		$gadget_admin = $GLOBALS['app']->LoadGadget('Store', 'AdminHTML');
		$page = $gadget_admin->B2(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$html_output = $users_html->GetAccountHTML('Store');
		$html_output = str_replace("</head>", "<style type=\"text/css\">body { background: transparent none; }</style>\n</head>", $html_output);
		$output_html = str_replace("__JAWS_GADGET__", $page, $html_output);
		return $output_html;
    }

    /**
     * Account B_form2
     *
     * @access public
     * @return string
     */
    function account_B_form2()
    {
		$gadget_admin = $GLOBALS['app']->LoadGadget('Store', 'AdminHTML');
		$page = $gadget_admin->B_form2(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$html_output = $users_html->GetAccountHTML('Store');
		$html_output = str_replace("</head>", "<style type=\"text/css\">body { background: transparent none; }</style>\n</head>", $html_output);
		$output_html = str_replace("__JAWS_GADGET__", $page, $html_output);
		return $output_html;
    }

    /**
     * Account B_form_post2
     *
     * @access public
     * @return string
     */
    function account_B_form_post2()
    {
		$gadget_admin = $GLOBALS['app']->LoadGadget('Store', 'AdminHTML');
		$page = $gadget_admin->B_form_post2(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$output_html = str_replace("__JAWS_GADGET__", $page, $users_html->GetAccountHTML('Store'));
		return $output_html;
    }

    /**
     * Account C
     *
     * @access public
     * @return string
     */
    function account_C()
    {
		$gadget_admin = $GLOBALS['app']->LoadGadget('Store', 'AdminHTML');
		$page = $gadget_admin->C(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$html_output = $users_html->GetAccountHTML('Store');
		$html_output = str_replace("</head>", "<style type=\"text/css\">body { background: transparent none; }</style>\n</head>", $html_output);
		$output_html = str_replace("__JAWS_GADGET__", $page, $html_output);
		return $output_html;
    }

    /**
     * Account C_form
     *
     * @access public
     * @return string
     */
    function account_C_form()
    {
		$gadget_admin = $GLOBALS['app']->LoadGadget('Store', 'AdminHTML');
		$page = $gadget_admin->C_form(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$html_output = $users_html->GetAccountHTML('Store');
		$html_output = str_replace("</head>", "<style type=\"text/css\">body { background: transparent none; }</style>\n</head>", $html_output);
		$output_html = str_replace("__JAWS_GADGET__", $page, $html_output);
		return $output_html;
    }

    /**
     * Account C_form_post
     *
     * @access public
     * @return string
     */
    function account_C_form_post()
    {
		$gadget_admin = $GLOBALS['app']->LoadGadget('Store', 'AdminHTML');
		$page = $gadget_admin->C_form_post(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$output_html = str_replace("__JAWS_GADGET__", $page, $users_html->GetAccountHTML('Store'));
		return $output_html;
    }

    /**
     * Account D
     *
     * @access public
     * @return string
     */
    function account_D()
    {
		$gadget_admin = $GLOBALS['app']->LoadGadget('Store', 'AdminHTML');
		$page = $gadget_admin->D(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$html_output = $users_html->GetAccountHTML('Store');
		$html_output = str_replace("</head>", "<style type=\"text/css\">body { background: transparent none; }</style>\n</head>", $html_output);
		$output_html = str_replace("__JAWS_GADGET__", $page, $html_output);
		return $output_html;
    }

    /**
     * Account D_form
     *
     * @access public
     * @return string
     */
    function account_D_form()
    {
		$gadget_admin = $GLOBALS['app']->LoadGadget('Store', 'AdminHTML');
		$page = $gadget_admin->D_form(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$html_output = $users_html->GetAccountHTML('Store');
		$html_output = str_replace("</head>", "<style type=\"text/css\">body { background: transparent none; }</style>\n</head>", $html_output);
		$output_html = str_replace("__JAWS_GADGET__", $page, $html_output);
		return $output_html;
    }

    /**
     * Account D_form_post
     *
     * @access public
     * @return string
     */
    function account_D_form_post()
    {
		$gadget_admin = $GLOBALS['app']->LoadGadget('Store', 'AdminHTML');
		$page = $gadget_admin->D_form_post(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$output_html = str_replace("__JAWS_GADGET__", $page, $users_html->GetAccountHTML('Store'));
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
		$gadget_admin = $GLOBALS['app']->LoadGadget('Store', 'AdminHTML');
		$page = $gadget_admin->GetQuickAddForm(true);
		$users_html = $GLOBALS['app']->LoadGadget('Users', 'HTML');
		$output_html = str_replace("__JAWS_GADGET__", $page, $users_html->GetAccountHTML('Store'));
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
		return $user_admin->ShowEmbedWindow('Store', 'OwnProduct', true);
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
			$output_html .= $this->Category('all', false, null, '', '', '', $uid);
		} else {
            require_once JAWS_PATH . 'include/Jaws/Header.php';
            Jaws_Header::Location($GLOBALS['app']->GetSiteURL().'/');
		}
		
		return $output_html;
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
     * Printable product details.
     *
     * @category 	feature
     * @access 	public
     * @return 	string 	HTML content
     * @TODO 	Themable PDF output (of individual and categories)
     */
    function PrintProductDetails()
    {
		$request =& Jaws_Request::getInstance();
        $get = $request->get(array('id'), 'get');
		$output_html .= "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\">\n";
		$output_html .= " <head>\n";
		$output_html .= "  <title>Print Product Details</title>\n";
		$output_html .= "  <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n";
        $theme = $GLOBALS['app']->Registry->Get('/config/theme');
		$themeHREF = (strpos($theme, 'http://') !== false ? $theme : $GLOBALS['app']->getDataURL('', true) . "themes/" . $theme);
		$output_html .= "	<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" href=\"". $themeHREF . "/style.css\" />\n";
		$output_html .= "	<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" href=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/Store/resources/style.css\" />\n";
		$output_html .= "	<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" href=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/Ecommerce/resources/style.css\" />\n";
		$output_html .= "	<link rel=\"stylesheet\" media=\"print\" type=\"text/css\" href=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/Store/resources/print.css\" />\n";
		//$output_html .= "	<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" href=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/carousel/themes/carousel/prototype-ui.css\" />\n";
		$output_html .= "	<link rel=\"stylesheet\" media=\"screen\" type=\"text/css\" href=\"". $GLOBALS['app']->getDataURL('', true) . "files/css/custom.css\" />\n";
		//$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/prototype/prototype.js\"></script>\n";
		//$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/prototype/scriptaculous.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/prototype/prototype.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/prototype/scriptaculous.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/prototype/effects.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/prototype/controls.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/include/Jaws/Ajax/Response.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"index.php?gadget=Store&amp;action=Ajax&amp;client=all&amp;stub=StoreAjax\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"index.php?gadget=Store&action=AjaxCommonFiles\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/Store/resources/client_script.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/js/admin.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"http://yui.yahooapis.com/2.4.0/build/yahoo-dom-event/yahoo-dom-event.js\"></script>\n";
		//$output_html .= "	<script type=\"text/javascript\" src=\"index.php?gadget=Store&action=hideGoogleAPIAlerts\"></script>\n";
		//$GLOBALS['app']->Registry->LoadFile('Maps');
		//$output_html .= "	<script type=\"text/javascript\" src=\"http://maps.google.com/maps?file=api&v=2&key=".$GLOBALS['app']->Registry->Get('/gadgets/Maps/googlemaps_key')."\"></script>\n";
		//$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/js/progressbarcontrol.js\"></script>\n";
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
		#product_details_container {
			min-width: 0px;
		}
		#prodnav_print {
			display: none;
		}
		.product_featured_bkgnd, .product_featured_bkgnd_over, .product_bkgnd, .product_bkgnd_over {
		width:auto;
		}
		</style>\n";
		//$GLOBALS['app']->Registry->LoadFile('Store');
		$output_html .= " </head>\n";
		$output_html .= " <body style=\"background-image: url();\" onLoad=\"sizeFrame".$get['id']."();\">\n";
		if (isset($get['id']) && !empty($get['id'])) {
			$output_html .= $this->Product((int)$get['id']);
		}
		$output_html .= " </body>\n";
		$output_html .= "</html>\n";
		return $output_html;
	}	

	/**
     * Import RSS/Atom property feeds to products
     *
     * @access 	public
     * @return 	string 	HTML content
     */
    function UpdateRSSStore()

    {		
		ignore_user_abort(true); 
        set_time_limit(0);
		ob_start();
		echo  "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
		echo  "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\">\n";
		echo  " <head>\n";
		//echo  "  <meta http-equiv='refresh' content='10'>";
		echo  "  <title>Update RSS Products</title>\n";
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
			</script>
		";
		echo " <script type='text/javascript'>function submitForm(){if(document.getElementById('product_rss_form')){document.forms['product_rss_form'].submit();};}</script>\n";
		echo  " </head>\n";
		// tag after text for Safari & Firefox
		// 8 char minimum for Firefox
		ob_flush();
		flush();  // worked without ob_flush() for me
		sleep(1);
		$model = $GLOBALS['app']->LoadGadget('Store', 'Model');
		$adminModel = $GLOBALS['app']->LoadGadget('Store', 'AdminModel');
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
		
		$searchfile = $request->get('file', 'get');
		if (empty($searchfile)) {
			$searchfile = $request->get('file', 'post');
		}
		if (!empty($searchfile)) {
			$searchfile = urldecode($searchfile);
		}
		$searchtype = $request->get('type', 'get');
		if (empty($searchtype)) {
			$searchtype = $request->get('type', 'post');
		}
		//echo '<br />searchnum ::: '.(int)$searchnum;
		/*
		$output_html .= "  <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"https://ajax.googleapis.com/ajax/libs/prototype/1.6.1/prototype.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"https://ajax.googleapis.com/ajax/libs/scriptaculous/1.8/scriptaculous.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"https://ajax.googleapis.com/ajax/libs/scriptaculous/1.8/effects.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/include/Jaws/Ajax/Response.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"index.php?gadget=Store&action=Ajax&client\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"index.php?gadget=Store&action=AjaxCommonFiles\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/Store/resources/client_script.js\"></script>\n";
		$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/js/admin.js\"></script>\n";
		$output_html .= " </head>\n";
		$output_html .= " <body style=\"background: url();\">\n";
		$output_html .= " <div id=\"msgbox-wrapper\"></div>\n";
		*/
		if (!empty($searchcategory) && !empty($searchfetch_url) && (!empty($searchnum) || (int)$searchnum == 0 || (int)$searchnum == 1) && !empty($user_attended) && $user_attended == 'Y') {
			echo  " <body onload='doLoad(); submitForm();'>\n";
			echo  " <script type=\"text/javascript\">sURL = 'index.php?gadget=Store&action=UpdateRSSStore&category=".(int)$searchcategory."&fetch_url=".urlencode($searchfetch_url)."&rss_url=".urlencode($searchrss_url)."&OwnerID=".(int)$searchownerid."&num=".(int)$searchnum."&ua=Y';</script>\n";
			$searchfetch_url = str_replace(' ', '%20', $searchfetch_url);
			$adminModel->InsertRSSStore((int)$searchcategory, $searchfetch_url, $searchrss_url, (int)$searchownerid, (int)$searchnum, 'Y');
			/*
			if (Jaws_Error::IsError($result)) {
				echo '<br />'.$result->GetMessage();
			}
			*/
		} else if (!empty($id)) {		
			echo  " <body>\n";
			$parent = $model->GetProductParent((int)$id);
			if (!Jaws_Error::IsError($parent) && isset($parent['productparentid'])) {
				$parent['productparentrss_url'] = str_replace(' ', '%20', $parent['productparentrss_url']);
				/*
				echo '<br />category ::: '.$parent['productparentid'];
				echo '<br />fetch_url ::: '.urlencode($parent['productparentrss_url']);
				echo '<br />rss_url ::: '.urlencode($parent['productparentrss_url']);
				echo '<br />OwnerID ::: '.$parent['productparentownerid'];
				exit;
				*/


				$adminModel->InsertRSSStore($parent['productparentid'], $parent['productparentrss_url'], $parent['productparentrss_url'], $parent['productparentownerid']);
				//$output_html .= "	<div id=\"insert\"></div><script>Event.observe(window, \"load\",function(){insertRSS(".$parent['productparentid'].", '".$parent['productparentrss_url']."', '".$parent['productparentrss_url']."', ".$parent['productparentownerid'].");});</script>";
				/*
				if (Jaws_Error::IsError($result)) {
					echo '<br />'.$result->GetMessage();
				}
				*/
			} else {
				echo '<br />'.$parent->GetMessage();

			}
		} else if (!empty($searchfile) && !empty($searchtype) && (!empty($searchnum) || (int)$searchnum == 0 || (int)$searchnum == 1)) {		
			echo  " <body onload='doLoad(); submitForm();'>\n";
			echo  " <script type=\"text/javascript\">sURL = 'index.php?gadget=Store&action=UpdateRSSStore&file=".$searchfile."&type=".$searchtype."&num=".(int)$searchnum."&ua=Y';</script>\n";
			//echo '<br />file ::: '.$searchfile;
			$adminModel->InsertInventory($searchfile, $searchtype, $searchnum, $user_attended);
		} else {
			echo '<br />'.'Product Category not found.';

		}
		echo " </body>\n";
		echo "</html>\n";
		//echo "<script type=\"text/javascript\">location.href='" . BASE_SCRIPT . "';</script>";
		//echo "<h1>Feed Imported Successfully</h1>";

		return true;
	}

	/**
     * Imports RSS/Atom feeds to products
     *
     * @access public
     * @return HTML string
     */
    function SnoopRSSStore()

    {		
		$request =& Jaws_Request::getInstance();
        $id = $request->get('id', 'get');
		if (empty($id)) {
			$id = $request->get('id', 'post');
		}
		$output_html = 'Snoopy error';
		if (!empty($id)) {
			$model = $GLOBALS['app']->LoadGadget('Store', 'Model');
			$parent = $model->GetProductParent((int)$id);
			if (!Jaws_Error::IsError($parent) && isset($parent['productparentid'])) {
				$output_html = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
				$output_html .= "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\">\n";
				$output_html .= " <head>\n";
				$output_html .= "  <title>Update RSS Store</title>\n";
				$output_html .= "  <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n";
				$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/prototype/prototype.js\"></script>\n";
				$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/prototype/scriptaculous.js\"></script>\n";
				$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/prototype/effects.js\"></script>\n";
				$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/prototype/controls.js\"></script>\n";
				$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/include/Jaws/Ajax/Response.js\"></script>\n";
				$output_html .= "	<script type=\"text/javascript\" src=\"index.php?gadget=Store&amp;action=Ajax&amp;client=all&amp;stub=StoreAjax\"></script>\n";
				$output_html .= "	<script type=\"text/javascript\" src=\"index.php?gadget=Store&action=AjaxCommonFiles\"></script>\n";
				$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/gadgets/Store/resources/client_script.js\"></script>\n";
				$output_html .= "	<script type=\"text/javascript\" src=\"". $GLOBALS['app']->GetJawsURL() . "/libraries/js/admin.js\"></script>\n";
				$output_html .= " </head>\n";
				$output_html .= " <body style=\"background: url();\">\n";
				$output_html .= " <div id=\"msgbox-wrapper\"></div>\n";
				$parent['productparentrss_url'] = str_replace(' ', '%20', $parent['productparentrss_url']);
				//$adminModel = $GLOBALS['app']->LoadGadget('Store', 'AdminModel');
				//$result = $adminModel->InsertRSSStore($parent['productparentid'], $parent['productparentrss_url'], $parent['productparentrss_url'], $parent['productparentownerid']);
				$output_html .= "	<div id=\"insert\"></div><script>Event.observe(window, \"load\",function(){insertRSS(".$parent['productparentid'].", '".$parent['productparentrss_url']."', '".$parent['productparentrss_url']."', ".$parent['productparentownerid'].");});</script>";
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
     * Return gadget item suggestions for prototype.js's Ajax.Autocompleter based on a query
	 * Usage: 
	 *  index.php?gadget=Maps&action=AutoCompleteRegions&query=UserInput
	 *  &methodcount=2&initial1gadget=Maps&initial1method=SearchRegions
	 *  &initial1paramcount=1&initial1param1=parametertopass&initial2gadget=Properties
	 * &initial2method=SearchAmenities&initial2paramcount=1&initial2param1=parametertopass
	 * &matchtogadget=Properties&matchtomethod=SearchKeyWithProperties&paramcount=10
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
    function AutoComplete()
    {
		// Output a real JavaScript file!
		//header('Content-type: text/javascript'); 
		$request =& Jaws_Request::getInstance();
		
        $fetch = array('id','query','matchtogadget','matchtomethod','element');
		$output_html = "<ul>\n";
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
								$suggestions_html = "<li><span class=\"informal\">Error: Method: ".$initialmethod." doesn't exist for Gadget: ".$initialgadget.".</span></li>\n";
								$output_html .= $suggestions_html;
								$output_html .= "</ul>\n";
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
								$suggestions_html = "<li><span class=\"informal\">Error: ".$results->GetMessage().".</span></li>\n";
								$output_html .= $suggestions_html;
								$output_html .= "</ul>\n";
								echo $output_html;
								exit;
							} else {
								// For every suggestion found, we can get all of a gadget's items that are related
								if (!empty($post['matchtogadget']) && !empty($post['matchtomethod'])) {
									$gadgetmodel = $GLOBALS['app']->LoadGadget($post['matchtogadget'], 'AdminModel');
									if (!method_exists($gadgetmodel, $post['matchtomethod'])) {
										$suggestions_html = "<li><span class=\"informal\">Error: Method: ".$post['matchtomethod']." doesn't exist for Gadget: ".$post['matchtogadget'].".</span></li>\n";
										$output_html .= $suggestions_html;
										$output_html .= "</ul>\n";
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
														if ($post['matchtomethod'] == 'SearchKeyWithProducts') {
															if ($initialmethod == 'SearchAttributes') {
																$params[10] = 'attribute';
																$params[12] = 'attribute';
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
												$suggestions_html = "<li><span class=\"informal\">Error: ".$items->GetMessage().".</span></li>\n";
												$output_html .= $suggestions_html;
												$output_html .= "</ul>\n";
												echo $output_html;
												exit;
											} else {
												foreach ($items as $item) {
													if ($i == 1 && $stop_method === false) {
														$stop_method = true;
													}
													$res[] = $item;
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
											$res[] = $resval;
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
			$suggestions_html = "<li><span class=\"informal\">No matches. Please check your spelling, or try more popular terms.</span></li>\n";
			$output_html .= $suggestions_html;
			$output_html .= "</ul>\n";
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
			$suggestions_html .= "<li".($urlMethod == 'get' ? ' onclick="if(typeof Store_gotMatch != \'undefined\'){Store_gotMatch = true;}; document.getElementById(\''.$post['element'].'\').value = \''.substr($r, 0, strpos($r, '<')).'\'; document.getElementById(\'Store_search_choices\').style.display = \'none\';"' : ' onclick="if(typeof Store_gotMatch != \'undefined\'){Store_gotMatch = true;};"').">".$r."</li>\n";
		}
		
		$output_html .= $suggestions_html;
		$output_html .= "</ul>\n";
		echo $output_html;
	}	

	/**
     * Displays or writes a RSS feed for the Store
     *
     * @access       public
     * @param        boolean $save true to save RSS, false to display
     * @return       xml with RSS feed on display mode, nothing otherwise
     */
    function RSS($gid = null, $OwnerID = null, $pid = null)
    {
        $model = $GLOBALS['app']->LoadGadget('Store', 'Model');
		$request =& Jaws_Request::getInstance();
		if (is_null($gid)) {
			$gid = $request->get('gid', 'get');
		}
		if (is_null($pid)) {
			$pid = $request->get('pid', 'get');
		}
		if (is_null($OwnerID)) {
			$OwnerID = $request->get('OwnerID', 'get');
		}
        $rss = $model->MakeRSS(false, $gid, $OwnerID, $pid);
        if (!Jaws_Error::IsError($rss)) {
            header('Content-type: application/xml');
            return $rss;
        }
    }
		
	/**
     * Customizable product templates.
     *
     * @category 	feature 
     * @param 	string 	$template 	name of template 
     * @param 	string 	$type 	Type (product/attribute_types) 
     * @param 	string 	$style 	Main style (color, etc) 
     * @param 	string 	$section 	Sub style (front/back/sleeve, etc) 
     * @param 	string 	$attribute 	Attribute template to overlay 
     * @param 	string 	$size 	Template size (thumb/preview/print)
     * @param 	string 	$preview 	Preview CSS class to use (medium/large, etc) 
     * @param 	string 	$alt_style 	Alt Style (default) 
     * @param 	string 	$attr_output 	Attribute filetype output (SVG, PNG, etc)  
     * @param 	mixed 	$attr_variables 	Array (or serialized array) of template replacement variables (key => val)  
     * @param 	string 	$attr_opacity 	Opacity number  
     * @param 	string 	$attr_rotate 	Rotate number  
     * @param 	string 	$attr_skewx 	Skew X number  
     * @param 	string 	$attr_skewy 	Skew Y number  
     * @param 	string 	$attr_width 	Pixel width 
     * @param 	string 	$attr_height 	Pixel height 
     * @param 	string 	$attr_unit 	Unit of measurement (px, mm, etc) 
     * @access 	public
     * @return 	string	Template content (SVG or HTML)
     */
    function ShowTemplate(
		$template = null, $type = null, $style = null, $section = null, 
		$attribute = null, $size = null, $preview = null,  
		$alt_style = null, $attr_output = null, $attr_variables = array(), 
		$attr_opacity = null, $attr_rotate = null, $attr_skewx = null, 
		$attr_skewy = null, $attr_width = null, 
		$attr_height = null, $attr_unit = null
	) {
        $model = $GLOBALS['app']->LoadGadget('Store', 'Model');
		$request =& Jaws_Request::getInstance();
		$get = $request->get(array(
			't', 'type', 'st', 'sec', 'a', 'prev', 'size', 
			'a_st', 'a_output', 'a_o', 
			'a_r', 'a_skx', 'a_sky', 'a_w', 
			'a_h', 'a_u', 'a_v'
		), 'get');
		if (is_null($template)) {
			$template = $get['t'];
		}
		if (is_null($type) || empty($type)) {
			$type = $get['type'];
			if (is_null($type) || empty($type)) {
				$type = 'product';
			}
		}
		if (is_null($style)) {
			$style = $get['st'];
		}
		if (is_null($section)) {
			$section = $get['sec'];
		}
		if (
			(is_null($template) || empty($template)) || (is_null($style) || empty($style)) || (is_null($section) || empty($section)) || 
			!file_exists(JAWS_DATA . 'templates/Store/'.$type.'/'.$template.'/'.$template.'.html')
		) {
			return new Jaws_Error('Template file "'.JAWS_DATA . 'templates/Store/'.$type.'/'.$template.'/'.$template.'.html" doesn\'t exist', _t('STORE_NAME'));
		}
		
		$tpl = new Jaws_Template(JAWS_DATA . 'templates/Store/'.$type.'/'.$template.'/');
		$tpl->Load($template.'.html', false, false);
		
		// TODO: template_priority with drag and drop 
		$tpl->SetBlock($style);
		if (!$tpl->BlockExists($style.'/'.$section)) {
			return new Jaws_Error('Template block "'.$style.'/'.$section.'" doesn\'t exist', _t('STORE_NAME'));
		}
		$tpl->SetBlock($style.'/'.$section);
		if (is_null($size) || empty($size)) {
			$size = $get['size'];
			if (is_null($size) || empty($size)) {
				$size = 'screen';
			}
		}
		$tpl->SetBlock($style.'/'.$section.'/'.$size);
		if (is_null($preview) || empty($preview)) {
			$preview = $get['prev'];
			if (is_null($preview) || empty($preview)) {
				$preview = 'medium';
			}
		}
		$tpl->SetVariable('style', $style);
		$tpl->SetVariable('preview', $preview);
		$tpl->SetVariable('template_safe', strtolower(ereg_replace("[^A-Za-z0-9]", '', $template)));
		if (is_null($alt_style) || empty($alt_style)) {
			$alt_style = $get['a_st'];
			if (is_null($alt_style) || empty($alt_style)) {
				$alt_style = 'default';
			}
		}
		if (is_null($attr_output) || empty($attr_output)) {
			$attr_output = $get['a_output'];
			if (is_null($attr_output) || empty($attr_output)) {
				$attr_output = 'svg';
			}
		}
		if (is_null($attr_opacity) || empty($attr_opacity)) {
			$attr_opacity = $get['a_o'];
			if (is_null($attr_opacity) || empty($attr_opacity)) {
				$attr_opacity = '1';
			}
		}
		if (is_null($attr_rotate) || empty($attr_rotate)) {
			$attr_rotate = $get['a_r'];
			if (is_null($attr_rotate) || empty($attr_rotate)) {
				$attr_rotate = '0';
			}
		}
		if (is_null($attr_skewx) || empty($attr_skewx)) {
			$attr_skewx = $get['a_skx'];
			if (is_null($attr_skewx) || empty($attr_skewx)) {
				$attr_skewx = '0';
			}
		}
		if (is_null($attr_skewy) || empty($attr_skewy)) {
			$attr_skewy = $get['a_sky'];
			if (is_null($attr_skewy) || empty($attr_skewy)) {
				$attr_skewy = '0';
			}
		}
		if (is_null($attr_width) || empty($attr_width)) {
			$attr_width = $get['a_w'];
			if (is_null($attr_width) || empty($attr_width)) {
				$attr_width = '100';
			}
		}
		if (is_null($attr_height) || empty($attr_height)) {
			$attr_height = $get['a_h'];
			if (is_null($attr_height) || empty($attr_height)) {
				$attr_height = '100';
			}
		}
		if (is_null($attr_unit) || empty($attr_unit)) {
			$attr_unit = $get['a_u'];
			if (is_null($attr_unit) || empty($attr_unit)) {
				$attr_unit = 'px';
			}
		}
		if (count($attr_variables) <= 0) {
			$attr_variables = $get['a_v'];
			if (is_null($attr_variables) || empty($attr_variables)) {
				$attr_variables = array();
			} else if (substr($attr_variables, 0, 2) == 'a:') {
				$attr_variables = unserialize($attr_variables);
			}
		}
		if (is_null($attribute) || empty($attribute)) {
			$attribute = $get['a'];
		}
		if (!is_null($attribute) && !empty($attribute)) {
			if ($type == 'product') {
				$attribute_html = $this->ShowTemplate(
					$attribute, 'attribute_types', $alt_style, 'section1', 
					null, $size, $preview,  
					$style, $attr_output, $attr_variables, 
					$attr_opacity, $attr_rotate, $attr_skewx, 
					$attr_skewy, $attr_width, $attr_height, 
					$attr_unit
				);
				if (!Jaws_Error::IsError($attribute_html)) {
					$tpl->SetVariable('attribute', $attribute_html);
				}
			} else if ($type == 'attribute_types') {
				$GLOBALS['app']->Layout->AddHeadOther('<meta name="svg.render.forceflash" content="true" />');
				$GLOBALS['app']->Layout->AddHeadOther('<script src="'.$GLOBALS['app']->GetSiteURL().'/gz.php?type=javascript&uri='.urlencode($GLOBALS['app']->GetJawsURL()).'%2Flibraries%2Fsvgweb%2Fsrc%2Fsvg.js" data-path="'.$GLOBALS['app']->GetSiteURL().'/gz.php?type=javascript&uri='.urlencode($GLOBALS['app']->GetJawsURL()).'%2Flibraries%2Fsvgweb%2Fsrc"></script>');
				$tpl->SetVariable('timestamp', time());
				$tpl->SetVariable('style', $style);
				$tpl->SetVariable('preview', $preview);
				$tpl->SetVariable('alt_style', $alt_style);
				$tpl->SetVariable('attr_template', $template);
				$tpl->SetVariable('attr_output', $attr_output);
				$tpl->SetVariable('attr_opacity', $attr_opacity);
				$tpl->SetVariable('attr_rotate', $attr_rotate);
				$tpl->SetVariable('attr_skewx', $attr_skewx);
				$tpl->SetVariable('attr_skewy', $attr_skewy);
				$tpl->SetVariable('attr_height', $attr_height);
				$tpl->SetVariable('attr_width', $attr_width);
				$tpl->SetVariable('attr_unit', $attr_unit);
				$tpl->SetVariable('attr_variables', urlencode(serialize($attr_variables)));
			}
		}
		$tpl->ParseBlock($style.'/'.$section.'/'.$size);
		$tpl->ParseBlock($style.'/'.$section);
		$tpl->ParseBlock($style);
		return $tpl->Get();
    }
	
	/**
     * Displays product attribute image
     *
     * @param        string 	$template 	Name of template 
     * @param        string 	$style 	Main style (default, etc)
     * @param        string 	$output 	Type to output (svg) 
     * @param        mixed 	$variables 	Array (or serialized array) of template variables to replace (key => value) 
     * @param        decimal 	$opacity 	Decimal (0 to 1) 
     * @param        float 	$rotate 	Float (-180 to 180) 
     * @param        float 	$skewx 	Float (-100 to 100) 
     * @param        float 	$skewy 	Float (-100 to 100) 
     * @param        integer 	$width 	Pixel width 
     * @param        integer 	$height 	Pixel height 
     * @param        string 	$unit 	Unit of measurement (px, mm, etc) 
     * @access       public
     * @return       string 	Template content (SVG or HTML)
     */
    function ShowAttribute(
		$template = null, $style = null, $output = null, $variables = array(), $opacity = null, 
		$rotate = null, $skewx = null, $skewy = null, $width = null, $height = null, $unit = null
	) {
        $model = $GLOBALS['app']->LoadGadget('Store', 'Model');
		$request =& Jaws_Request::getInstance();
		$get = $request->get(array('t', 'st', 'output', 'o', 'r', 'skx', 'sky', 'w', 'h', 'u', 'v'), 'get');
		if (is_null($template)) {
			$template = $get['t'];
		}
		if (is_null($style) || empty($style)) {
			$style = $get['st'];
			if (is_null($style) || empty($style)) {
				$style = 'default';
			}
		}
		if (is_null($output)) {
			$output = $get['output'];
		}
		if ((is_null($output) || empty($output)) || (!is_null($output) && !in_array(strtolower($output), array('svg','png','jpg','gif')))) {
			$output = 'svg';
		}
		$output = strtolower($output);
		if (is_null($template) || empty($template) || !file_exists(JAWS_DATA . 'templates/Store/attribute_types/'.$template.'/'.$template.'.'.$output)) {
			return new Jaws_Error('Template file "'.JAWS_DATA . 'templates/Store/attribute_types/'.$template.'/'.$template.'.'.$output.'" doesn\'t exist', _t('STORE_NAME'));
		}
		if ($output == 'svg') {
			header('Content-type: image/svg+xml'); 
			$tpl = new Jaws_Template(JAWS_DATA . 'templates/Store/attribute_types/'.$template);
			$tpl->Load($template.'.'.$output, false, false);
			
			$tpl->SetBlock($style);
			if (is_null($opacity) || empty($opacity)) {
				$opacity = $get['o'];
				if (is_null($opacity) || empty($opacity)) {
					$opacity = '1';
				}
			}
			$tpl->SetVariable('opacity', $opacity);
			if (is_null($rotate) || empty($rotate)) {
				$rotate = $get['r'];
				if (is_null($rotate) || empty($rotate)) {
					$rotate = '0';
				}
			}
			$tpl->SetVariable('rotate', $rotate);
			if (is_null($skewx) || empty($skewx)) {
				$skewx = $get['skx'];
				if (is_null($skewx) || empty($skewx)) {
					$skewx = '0';
				}
			}
			$tpl->SetVariable('skewx', $skewx);
			if (is_null($skewy) || empty($skewy)) {
				$skewy = $get['sky'];
				if (is_null($skewy) || empty($skewy)) {
					$skewy = '0';
				}
			}
			$tpl->SetVariable('skewy', $skewy);
			if (is_null($width) || empty($width)) {
				$width = $get['w'];
				if (is_null($width) || empty($width)) {
					$width = '100';
				}
			}
			$tpl->SetVariable('width', $width);
			if (is_null($height) || empty($height)) {
				$height = $get['h'];
				if (is_null($height) || empty($height)) {
					$height = '100';
				}
			}
			$tpl->SetVariable('height', $height);
			if (is_null($unit) || empty($unit)) {
				$unit = $get['u'];
				if (is_null($unit) || empty($unit)) {
					$unit = 'px';
				}
			}
			$tpl->SetVariable('unit', $unit);
			if (count($variables) <= 0) {
				$variables = $get['v'];
				if (is_null($variables) || empty($variables)) {
					$variables = array();
				} else if (substr($variables, 0, 2) == 'a:') {
					$variables = unserialize($variables);
				}
			}
			foreach ($variables as $k => $v) {
				$tpl->SetVariable($k, $v);
			}
			$tpl->ParseBlock($style);
			return $tpl->Get();
		} else {
			include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
			header('Content-type: image/'.$output); 
			// snoopy
			$snoopy = new Snoopy('Store');
			$snoopy->agent = "Jaws";
			$url = $GLOBALS['app']->GetSiteURL().'/index.php?action=IncludeLibrary&path=libraries%2Fmakebanner%2Fshowban.php';
			$url .= '&imageBanner=0&generatedBanner=0&imageXpos=468&imageYpos=60&primBackgroundColor=FFE4C4&displayGradient=1';
			$url .= '&gradientDirection=horizontal&secBackgroundColor=D2691E&borderSize=3&borderColor=6495ED&';
			$url .= 'text[0]=Create_Your_Own_Banner!&text[1]=Multiple_Colors&text[2]=Multiple_lines_of_text';
			$url .= '&font[0]=15&font[1]=1&font[2]=1&font[3]=1&fontSize[0]=16&fontSize[1]=10&fontSize[2]=10&fontSize[3]=8';
			$url .= '&textColor[0]=00008B&textColor[1]=800000&textColor[2]=FFFAFA&textColor[3]=000000';
			$url .= '&textXpos[0]=30&textXpos[1]=308&textXpos[2]=300&textYpos[0]=38&textYpos[1]=24&textYpos[2]=42';
			$url .= '&textAngle[0]=0&textAngle[1]=0&textAngle[2]=0&textShadow[0]=1&';
			if($snoopy->fetch($url)) {
				return $snoopy->results;
			}
		}
    }
	
}
