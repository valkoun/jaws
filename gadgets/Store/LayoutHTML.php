<?php
/**
 * Store Gadget (layout actions in client side)
 *
 * @category   GadgetLayout
 * @package    Store
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class StoreLayoutHTML
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
			$actions['Index'] = array(
				'mode' => 'LayoutAction', 
				'name' => _t('STORE_TITLE_PRODUCT_INDEX'), 
				'desc' => _t('STORE_DESCRIPTION_PRODUCT_INDEX')
			);
			$actions['ProductSearch'] = array(
				'mode' => 'LayoutAction', 
				'name' => _t('STORE_LAYOUT_SEARCH'), 
				'desc' => _t('STORE_LAYOUT_SEARCH_DESCRIPTION')
			);
			$actions['ShowFiveProducts'] = array(
				'mode' => 'LayoutAction',
				'name' => _t('STORE_LAYOUT_SHOWFIVE'),
				'desc' => _t('STORE_LAYOUT_SHOWFIVE_DESCRIPTION')
			);
			$actions['ShowTwoProducts'] = array(
				'mode' => 'LayoutAction',
				'name' => _t('STORE_LAYOUT_SHOWTWO'),
				'desc' => _t('STORE_LAYOUT_SHOWTWO_DESCRIPTION')
			);
			$actions['ShowPremiumProduct'] = array(
				'mode' => 'LayoutAction',
				'name' => _t('STORE_LAYOUT_PREMIUM'),
				'desc' => _t('STORE_LAYOUT_PREMIUM_DESCRIPTION')
			);
			$actions['ProductCustomization'] = array(
				'mode' => 'LayoutAction',
				'name' => _t('STORE_LAYOUT_CUSTOMIZE'),
				'desc' => _t('STORE_LAYOUT_CUSTOMIZE_DESCRIPTION')
			);
		}
		
        $model = $GLOBALS['app']->LoadGadget('Store', 'Model');
		$pages = $model->GetProductParents($limit, 'productparentcreated', 'DESC', $offset);
        if (!Jaws_Error::isError($pages)) {
            foreach ($pages as $page) {
				$actions['CategorySlideshow(' . $page['productparentid'] . ')'] = array(
					'mode' => 'LayoutAction',
					'name' => 'Show slideshow of products in "'. $page['productparentcategory_name'].'"',
					'desc' => _t('STORE_LAYOUT_SLIDESHOW_DESCRIPTION')
				);
				$actions['CategoryShowOne(' . $page['productparentid'] . ')'] = array(
					'mode' => 'LayoutAction',
					'name' => 'Show one product from "'.$page['productparentcategory_name'].'"',
					'desc' => _t('STORE_LAYOUT_SHOWONE_DESCRIPTION')
				);
				/*
				$actions['CategoryShowFiveProducts(' . $page['productparentid'] . ')'] = array(
					'mode' => 'LayoutAction',
					'name' => 'Five Products of "'.$page['productparentcategory_name'].'"',
					'desc' => _t('STORE_LAYOUT_FIVEPRODUCTSOFCATEGORY_DESCRIPTION', $page['productparentcategory_name'])
				);
				$actions['CategoryShowTwoProducts(' . $page['productparentid'] . ')'] = array(
					'mode' => 'LayoutAction',
					'name' => 'Two Products of "'.$page['productparentcategory_name'].'"',
					'desc' => _t('STORE_LAYOUT_TWOPRODUCTSOFCATEGORY_DESCRIPTION', $page['productparentcategory_name'])
				);
				*/
            }
        }
		require_once JAWS_PATH . 'include/Jaws/User.php';
		$uModel = new Jaws_User;
		$groups = $uModel->GetAllGroups('name', null, $limit, $offset);

		if ($groups) {
			foreach ($groups as $group) {
				$groupName = (strpos($group['name'], '_') !== false ? ucfirst(str_replace('_', ' ', $group['name'])) : ucfirst($group['name']));
				$actions['ShowFiveProductsOfGroup(' . $group['id'] . ')'] = array(
					'mode' => 'LayoutAction',
					'name' => "Five Products of ". $groupName,
					'desc' => _t('STORE_LAYOUT_FIVEPRODUCTSOFGROUP_DESCRIPTION', $groupName)
				);
				$actions['ShowPremiumProductOfGroup(' . $group['id'] . ')'] = array(
					'mode' => 'LayoutAction',
					'name' => "Premium Products of ". $groupName,
					'desc' => _t('STORE_LAYOUT_PREMIUM_DESCRIPTION')
				);
			}
		}
		
        return $actions;
    }

	/** 
	 * Displays Product Customization UI.
     *
     * @access public
     * @return string
     */
    function ProductCustomization()
    {
        $GLOBALS['app']->Layout->AddHeadLink('libraries/autocomplete/autocomplete.css', 'stylesheet', 'text/css', 'default');
        $GLOBALS['app']->Layout->AddHeadLink('gadgets/Store/resources/style.css', 'stylesheet', 'text/css');
		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=Store&amp;action=Ajax&amp;client=all&amp;stub=StoreAjax');
		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=Store&amp;action=AjaxCommonFiles');
		$GLOBALS['app']->Layout->AddScriptLink('gadgets/Store/resources/client_script.js');
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $model = $GLOBALS['app']->LoadGadget('Store', 'Model');

        $t_item = new Jaws_Template('gadgets/Store/templates/');
        $t_item->Load('ProductCustomization.html');

        $t_item->SetBlock('drag_drop');
        $t_item->SetVariable('empty_section',    _t('LAYOUT_SECTION_EMPTY'));
        $t_item->SetVariable('display_always',   _t('LAYOUT_ALWAYS'));
        $t_item->SetVariable('display_never',    _t('LAYOUT_NEVER'));
        $t_item->SetVariable('displayWhenTitle', _t('LAYOUT_CHANGE_DW'));
        $t_item->SetVariable('actionsTitle',     _t('LAYOUT_ACTIONS'));
        $dragdrop = $t_item->ParseBlock('drag_drop');
        $t_item->Blocks['drag_drop']->Parsed = '';

        $templatepath = null;
		$templatefile = null;
		$fakeLayout = new Jaws_Layout();
        $fakeLayout->Load(true, $templatepath, $templatefile);
        $layoutContent = $fakeLayout->_Template->Blocks['layout']->Content;
        
        $layoutContent = $layoutContent . $dragdrop;
        $fakeLayout->_Template->Blocks['layout']->Content = $layoutContent;

        $fakeLayout->_Template->SetVariable('site-title', $GLOBALS['app']->Registry->Get('/config/site_name'));
		$fakeLayout->AddScriptLink('index.php?gadget=CustomPage&amp;action=account_SetGBRoot');
        $fakeLayout->AddScriptLink('libraries/greybox/AJS.js');
        $fakeLayout->AddScriptLink('libraries/greybox/AJS_fx.js');
        $fakeLayout->AddScriptLink('libraries/greybox/gb_scripts.js');
        
        $fakeLayout->AddScriptLink('libraries/window/dist/window.js');
        $fakeLayout->AddHeadLink('libraries/window/themes/window/window.css', 'stylesheet', 'text/css');
        $fakeLayout->AddHeadLink('libraries/window/themes/window/simpleblue.css', 'stylesheet', 'text/css');
        $fakeLayout->AddHeadLink('libraries/window/themes/shadow/mac_shadow.css', 'stylesheet', 'text/css');
        
        $fakeLayout->AddHeadLink(PIWI_URL . 'piwidata/css/default.css', 'stylesheet', 'text/css', 'default');
        $fakeLayout->AddHeadLink('libraries/greybox/gb_styles.css', 'stylesheet', 'text/css');

        $fakeLayout->addHeadOther(
                    '<!--[if lt IE 7]>'."\n".
                    '<script src="'. $GLOBALS['app']->GetJawsURL() . '/gadgets/ControlPanel/resources/ie-bug-fix.js" type="text/javascript"></script>'."\n".
                    '<![endif]-->');

        foreach ($fakeLayout->_Template->Blocks['layout']->InnerBlock as $name => $data) {
            if ($name == 'head') continue;
            $fakeLayout->_Template->SetBlock('layout/'.$name);
            $js_section_array = '<script type="text/javascript">Event.observe(window, "load",function(){items[\''.$name.'\'] = new Array(); sections.push(\''.$name.'\');});</script>';
/*
            $gadgets = $model->GetGadgetsInSection($name);
            if (!is_array($gadgets)) continue;
            foreach ($gadgets as $gadget) {
                $id = $gadget['id'];
                if (file_exists(JAWS_PATH . 'gadgets/'. $gadget['gadget']. '/'. 'LayoutHTML.php') ||
                     file_exists(JAWS_PATH . 'gadgets/'. $gadget['gadget']. '/'. 'Actions.php') ||
                    ($gadget['gadget'] == '[REQUESTEDGADGET]'))
                {
                    if (($GLOBALS['app']->Registry->Get('/gadgets/'.$gadget['gadget'].'/enabled') == 'true') ||
                        ($gadget['gadget'] == '[REQUESTEDGADGET]'))
                    {
                        if ($gadget['gadget'] == '[REQUESTEDGADGET]') {
                            $section_empty = false;
                            $t_item->SetBlock('item');
                            $t_item->SetVariable('section_id', $name);
                            $t_item->SetVariable('item_id', $id);
                            $t_item->SetVariable('pos', $gadget['layout_position']);
                            $t_item->SetVariable('gadget', _t('LAYOUT_REQUESTED_GADGET'));
                            $t_item->SetVariable('action', '&nbsp;');
                            $t_item->SetVariable('icon', $GLOBALS['app']->GetJawsURL() . '/gadgets/Layout/images/requested-gadget.png');
                            $t_item->SetVariable('description', _t('LAYOUT_REQUESTED_GADGET_DESC'));
                            $t_item->SetVariable('lbl_display_when', _t('LAYOUT_DISPLAY_IN'));
                            $t_item->SetVariable('display_when', _t('GLOBAL_ALWAYS'));
                            $t_item->SetVariable('void_link', 'return;');
                            $t_item->SetVariable('section_name', $name);
                            $t_item->SetVariable('delete', 'void(0);');
                            $t_item->SetVariable('delete-img', $GLOBALS['app']->GetJawsURL() . '/gadgets/Layout/images/no-delete.gif');
                            $t_item->SetVariable('item_status', 'none');
                            $t_item->ParseBlock('item');
                        } else {
                            if (Jaws_Gadget::IsGadgetUpdated($gadget['gadget'])) {
                                $section_empty = false;
                                $controls = '';
                                $t_item->SetBlock('item');
                                $t_item->SetVariable('section_id', $name);
                                $delete_url = "javascript: deleteElement('".$gadget['id']."','"._t('LAYOUT_CONFIRM_DELETE')."');";

                                $actions = $GLOBALS['app']->GetGadgetActions($gadget['gadget']);
                                $actions = (isset($actions['LayoutAction'])) ? $actions['LayoutAction'] : array();
                                if (isset($actions)) {
                                    $info = $GLOBALS['app']->LoadGadget($gadget['gadget'], 'Info');
                                    $t_item->SetVariable('gadget', $info->GetName());
                                    if (isset($actions[$gadget['gadget_action']]['name'])) {
                                        $t_item->SetVariable('action', $actions[$gadget['gadget_action']]['name']);
                                    } else {
                                        $layoutGadget = $GLOBALS['app']->LoadGadget($gadget['gadget'], 'LayoutHTML');
                                        if (method_exists($layoutGadget, 'LoadLayoutActions')) {
                                            $actions = $layoutGadget->LoadLayoutActions();
                                            if (isset($actions[$gadget['gadget_action']]['name'])) {
                                                $t_item->SetVariable('action', $actions[$gadget['gadget_action']]['name']);
                                            } else {
                                                $t_item->SetVariable('action', $gadget['gadget_action']);
                                            }
                                        } else {
                                            $t_item->SetVariable('action', $gadget['gadget_action']);
                                        }
                                        unset($layoutGadget);
                                    }
                                    unset($info);
                                } else {
                                    $t_item->SetVariable('gadget', $gadget['gadget']);
                                    $t_item->SetVariable('action', _t('LAYOUT_ACTIONS'));
                                }
                                $t_item->SetVariable('pos', $gadget['layout_position']);
                                $t_item->SetVariable('item_id', $id);
                                $t_item->SetVariable('base_script_url', $GLOBALS['app']->GetSiteURL('/'.BASE_SCRIPT));
                                $t_item->SetVariable('icon', $GLOBALS['app']->GetJawsURL() . '/gadgets/'.$gadget['gadget'].'/images/logo.png');
                                $t_item->SetVariable('delete', 'deleteElement(\''.$gadget['id'].'\',\''._t('LAYOUT_CONFIRM_DELETE').'\');');
                                $t_item->SetVariable('delete-img', $GLOBALS['app']->GetJawsURL() . '/gadgets/Layout/images/delete-item.gif');
                                if (isset($actions[$gadget['gadget_action']])) {
                                    $t_item->SetVariable('description', $actions[$gadget['gadget_action']]['desc']);
                                    $t_item->SetVariable('item_status', 'none');
                                } else {
                                    $t_item->SetVariable('description', $gadget['gadget_action']);
                                    $t_item->SetVariable('item_status', 'line-through');
                                }
                                unset($actions);

                                $t_item->SetVariable('controls', $controls);
                                $t_item->SetVariable('void_link', '');
                                $t_item->SetVariable('lbl_display_when', _t('LAYOUT_DISPLAY_IN'));
                                if ($gadget['display_when'] == '*') {
                                    $t_item->SetVariable('display_when', _t('GLOBAL_ALWAYS'));
                                } elseif (empty($gadget['display_when'])) {
                                        $t_item->SetVariable('display_when', _t('LAYOUT_NEVER'));
                                } else {
                                    $t_item->SetVariable('display_when', str_replace(',', ', ', $gadget['display_when']));
                                }
                                $t_item->ParseBlock('item');
                            }
                        }
                    }
                }
            }
*/
            $fakeLayout->_Template->SetVariable('ELEMENT', '<div class="layout-section" id="layout_'.$name.'_drop" title="'.$name.'">
                                    <div id="layout_'.$name.'">'.$js_section_array.$t_item->Get().
                                    '</div></div>');

            $fakeLayout->_Template->ParseBlock('layout/'.$name);
            $t_item->Blocks['item']->Parsed = '';
        }

        return $fakeLayout->Show(false);
	}
	
	/**
     * Displays a Product Search Form.
     *
     * @access public
     * @return string
     */
    function ProductSearch()
    {
        $GLOBALS['app']->Layout->AddHeadLink('libraries/autocomplete/autocomplete.css', 'stylesheet', 'text/css');
        $GLOBALS['app']->Layout->AddHeadLink('gadgets/Store/resources/style.css', 'stylesheet', 'text/css');
		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=Store&amp;action=Ajax&amp;client=all&amp;stub=StoreAjax');
		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=Store&amp;action=AjaxCommonFiles');
		$GLOBALS['app']->Layout->AddScriptLink('gadgets/Store/resources/client_script.js');
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $model = $GLOBALS['app']->LoadGadget('Store', 'Model');
		$request =& Jaws_Request::getInstance();
		$fetch = array('gadget', 'id', 'Store_id', 'action');
		$get  = $request->get($fetch, 'get');
		$cid = 'all';
		if ((!empty($get['id']) || !empty($get['Store_id'])) && $get['gadget'] == 'Store' && $get['action'] == 'Category') {
			$cid = (!empty($get['Store_id']) ? $get['Store_id'] : $get['id']);
		}
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
		$preview = $request->get('Store_preview', 'post');
		if (empty($preview)) {
			$preview = $request->get('Store_preview', 'get');
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
		$searchsales = $request->get('Store_sales', 'post');
		if (empty($searchsales)) {
			$searchsales = $request->get('Store_sales', 'get');
		}
		$tpl = new Jaws_Template('gadgets/Store/templates/');
		$tpl->Load('normal.html');

		$tpl->SetBlock('layout');
		$tpl->SetVariable('actionName', 'ProductSearch_');
		$tpl->SetVariable('layout_title', '');

		$tpl->SetBlock('layout/search');
		
		$tpl->SetVariable('action', 'index.php?gadget=Store&action=Category');
		$tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . "/");
		//$tpl->SetVariable('base_url', JAWS_DPATH);
		$tpl->SetVariable('site_url', $GLOBALS['app']->getSiteURL());
		$tpl->SetVariable('id', $cid);
		$tpl->SetVariable('permalink', $GLOBALS['app']->getSiteURL().'/index.php?gadget=Store&action=Category&Store_id='.$cid.'&Store_sortColumn='.$sortColumn.'&Store_sortDir='.$sortDir.'&Store_brand='.$searchbrand.'&Store_keyword='.$searchkeyword.'&Store_category='.$searchcategory.'&Store_attributes='.$searchattributes.'&Store_sales='.$searchsales);
		
		$tpl->SetVariable('searchkeyword_value', 'Enter Keyword to Search');
				
		$category_options = '';
		$parents = $model->GetProductParents();
		if (!Jaws_Error::IsError($parents)) {
			foreach($parents as $parent) {
				if ($parent['productparentactive'] == 'Y') {
					$category_options .= '<option VALUE="'.$parent['productparentid'].'"'.($cid != 'all' && ((int)$cid == $parent['productparentid'] || $cid == $parent['productparentfast_url']) ? ' SELECTED' : '').'>'.$parent['productparentcategory_name'].'</option>';
				}
			}
		}
		$tpl->SetVariable('category_default', ($cid == 'all' ? ' SELECTED' : ''));
		$tpl->SetVariable('category_options', $category_options);
				
		// send attribute records
		$amenities = $model->GetAttributeTypes();
		$amenitiesHTML = '';
		$shown_features = array(); 
		
		if (!Jaws_Error::IsError($amenities)) {
			$lastType = 0;
			$loopCount = 0;
			foreach($amenities as $amenity) {		            
				if ($amenity['active'] == 'Y' && $amenity['itype'] != 'TextBox' && $amenity['itype'] != 'TextArea' && $amenity['itype'] != 'HiddenField') {
					$types = $model->GetAttributesOfType((int)$amenity['id']);
					if (!Jaws_Error::IsError($types)) {
						if (count($types)) {
							// Sort result array
							$subkey = 'feature'; 
							$temp_array = array();
							
							$temp_array[key($types)] = array_shift($types);

							foreach($types as $key => $val){
								//if ($key == 'active' && $val == 'Y') {
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
								//}
							}

							$types = array_reverse($temp_array);
						}
						foreach($types as $type) {		            
							if (!in_array(strtolower($type['feature']), $shown_features)) {
								$shown_features[] = strtolower($type['feature']);
								if ($type['typeid'] != $lastType) {
									$lastType = $type['typeid'];
									$amenitiesHTML .= '<option value="">----------------------</option>';
									$amenitiesHTML .= '<option value="">'.$amenity['title'].'</option>';
									//$amenitiesHTML .= "<tr><td style=\"border:1pt solid #CCCCCC; padding: 5px; background: #EEEEEE;\" colspan=\"2\" width=\"100%\"><b>".$amenity['title']."</b>&nbsp;&nbsp;&nbsp;[<span style='font-size: 0.8em'><i>".$amenity['itype']."</i></span>]</td></tr>";
								}
								$amenitiesHTML .= '<option onclick="if(document.getElementById(\'Store_searchkeyword\').value==\'Enter Keyword to Search\')document.getElementById(\'Store_searchkeyword\').value=\'\'; location.href=\'index.php?gadget=Store&action=Category&Store_id=all&Store_sortColumn=\' + document.getElementById(\'Store_sortColumn\').value + \'&Store_sortDir=\' + document.getElementById(\'Store_sortDir\').value + \'&Store_brand=\' + document.getElementById(\'Store_brand\').value + \'&Store_keyword=\' + document.getElementById(\'Store_searchkeyword\').value + \'&Store_attributes='.urlencode($type['feature']).'\';" value="searchattribute - '.$type['feature'].'">';
								$amenitiesHTML .= "&nbsp;&nbsp;&nbsp;".$type['feature']."</option>";
							}
						}
					}
				}
			}			
		}
		$tpl->SetVariable('attribute_options', $amenitiesHTML);

		$brand_options = '';
		$brands = $model->GetBrands();
		if (!Jaws_Error::IsError($brands)) {
			foreach($brands as $brand) {
				if ($brand['active'] == 'Y') {
					$brand_options .= '<option VALUE="'.$brand['id'].'"'.((int)$searchbrand == $brand['id'] ? ' SELECTED' : '').'>'.$brand['title'].'</option>';
				}
			}
		}
		$tpl->SetVariable('brand_default', (trim($searchbrand) == '' || empty($searchbrand) ? ' SELECTED' : ''));
		$tpl->SetVariable('brand_options', $brand_options);

		$tpl->SetVariable('sort_default', (trim($sortColumn) == '' || $sortColumn == 'premium' ? ' SELECTED' : ''));
		$sort_options = '<option value="premium"'.($sortColumn == 'premium' ? ' SELECTED' : '').'>Featured Products</option>';
		$sort_options .= '<option VALUE="title"'.($sortColumn == 'title' ? ' SELECTED' : '').'>Product Name</option>';
		$sort_options .= '<option VALUE="price"'.($sortColumn == 'price' ? ' SELECTED' : '').'>Price</option>';
		$sort_options .= '<option VALUE="Created"'.($sortColumn == 'Created' ? ' SELECTED' : '').'>Date Added</option>';
		$tpl->SetVariable('sort_options', $sort_options);

		$tpl->SetVariable('sort_asc', ($sortDir == 'ASC' ? ' SELECTED' : ''));
		$tpl->SetVariable('sort_desc', (trim($sortDir) == '' || empty($sortDir) || $sortDir == 'DESC' ? ' SELECTED' : ''));
		
		$tpl->SetVariable('id_autocomplete', 'null');
		
		$tpl->ParseBlock('layout/search');
		$tpl->ParseBlock('layout');

		return $tpl->Get();
		
    }
    
	/**
     * Displays a slideshow.
     *
     * @access public
     * @return string
     */
    function CategorySlideshow($cid = 1, $embedded = false, $referer = null)
    {
		require_once JAWS_PATH . 'include/Jaws/Image.php';
        
		// for boxover on date highlighting
        $GLOBALS['app']->Layout->AddHeadLink('gadgets/Store/resources/style.css', 'stylesheet', 'text/css');
		$GLOBALS['app']->Layout->AddScriptLink('libraries/slideshow/slideshow-min.js');
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $model = $GLOBALS['app']->LoadGadget('Store', 'Model');
		// send Products records
		$request =& Jaws_Request::getInstance();
		$fetch = array('gadget', 'id');
		$get  = $request->get($fetch, 'get');
		
		//if on a users home page, show their stuff
		if (strtolower($get['gadget']) == 'users' && !empty($get['id'])) {
			$parents = $model->GetSingleProductParentByUserID($get['id'], $cid);
		} else {
			$parents = $model->GetProductParent($cid);
		}
		if (!Jaws_Error::IsError($parents) && isset($parents['productparentid']) && !empty($parents['productparentid'])) {
			require_once JAWS_PATH . 'include/Jaws/Template.php';
			$tpl = new Jaws_Template('gadgets/Store/templates/');
	        $tpl->Load('normal.html');

	        $tpl->SetBlock('layout');
			$tpl->SetVariable('actionName', 'CategorySlideshow_' . $parents['productparentid'] . '_');
	        $tpl->SetVariable('layout_title', $parents['productparentcategory_name']);

	        $tpl->SetBlock('layout/slideshow');
			//foreach($galleryParent as $parents) {		            
					// set "slideshow" swfobject variables
					//$tpl->SetVariable('base_url', JAWS_DPATH);
					$tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . "/");
					$tpl->SetVariable('id', $cid);
					$tpl->SetVariable('slideshow_loading_image', '');
					$tpl->SetVariable('slideshow_background_color', ' ');
					// build dimensions
					$slideshow_dimensions = '';
					/*
					$slideshow_dimensions .= "if ($('store-slideshow-".$cid."').parentNode) {\n";
					$slideshow_dimensions .= " 	$('store-slideshow-".$cid."').style.width = parseInt($('store-slideshow-".$cid."').parentNode.offsetWidth) + 'px';\n";	
					$slideshow_dimensions .= "}\n";	
					if ($parents['height'] != 'auto' && !empty($parents['custom_height'])) {
						$slideshow_dimensions .= "$('store-slideshow-".$cid."').style.height = '".(int)$parents['custom_height']."px';\n";	
					} else {
						$slideshow_dimensions .= " 	$('store-slideshow-".$cid."').style.height = parseInt(($('store-slideshow-".$cid."').offsetWidth".$cid.")*(.75)) + 'px';\n";
					}
					*/
					$slideshow_dimensions .= "if ($('store-slideshow-".$cid."').parentNode) {\n";
					$slideshow_dimensions .= " 	$('store-slideshow-".$cid."').parentNode.style.display = 'block';\n";	
					$slideshow_dimensions .= "	$('store-slideshow-".$cid."').parentNode.style.width = slideshow".$cid."_width + 'px';\n";
					$slideshow_dimensions .= "	$('store-slideshow-".$cid."').parentNode.style.height = slideshow".$cid."_height + 'px';\n";
					$slideshow_dimensions .= "}\n";	
					$slideshow_dimensions .= "$('store-slideshow_overlay".$cid."').style.width = slideshow".$cid."_width + 'px';\n";
					$slideshow_dimensions .= "$('store-slideshow_overlay".$cid."').style.height = slideshow".$cid."_height + 'px';\n";
					$slideshow_dimensions .= "$('store-slideshow-".$cid."').style.width = slideshow".$cid."_width + 'px';\n";
					$slideshow_dimensions .= "$('store-slideshow-".$cid."').style.height = slideshow".$cid."_height + 'px';\n";
					$slideshow_dimensions .= "$('store-PlayButton".$cid."').style.top = (slideshow".$cid."_height-50)*(-1)+'px';\n";
					$slideshow_dimensions .= "$('store-PauseButton".$cid."').style.top = (slideshow".$cid."_height-50)*(-1)+'px';\n";
					$slideshow_dimensions .= "$('store-slide-caption".$cid."').style.width = (slideshow".$cid."_width-90)+'px';\n";
					$tpl->SetVariable('slideshow_title', $xss->filter($parents['productparentcategory_name']));
					//$tpl->SetVariable('slideshow_overlay_image', 'url('.$GLOBALS['app']->getDataURL('', true) . 'files'.$xss->filter($parents['overlay_image']).')');
					$tpl->SetVariable('slideshow_height', 299);
					$tpl->SetVariable('slideshow_width', 343);
					$tpl->SetVariable('slideshow_timer', 'wait:10000,');
					$tpl->SetVariable('slideshow_load_immediately', 'true');
					$tpl->SetVariable('slideshow_dimensions', $slideshow_dimensions);
					$tpl->SetVariable('slideshow_textbar_bkgnd', "url(../../../images/transparent.png) 0 0;");
					$posts = $model->GetAllProductsOfParent($cid);
					if (!Jaws_Error::IsError($posts)) {
						$image_found = false;
				        $post_count = count($posts);
						if (!$post_count <= 0) {
							$tpl->SetVariable('slideshow_total', $post_count);
							reset($posts);
							$i = 0;
							foreach($posts as $post) {		            
								if (isset($post['image']) && !empty($post['image']) && $post['active'] == "Y") {
									$image_src = '';	
									if (isset($post['image']) && !empty($post['image'])) {
										$post['image'] = $xss->parse(strip_tags($post['image']));
										$tpl->SetVariable('image', $post['image']);
										if (substr(strtolower($post['image']), 0, 4) == "http") {
											$image_src = $post['image'];
											if (substr(strtolower($post['image']), 0, 7) == "http://") {
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
											$medium = Jaws_Image::GetMediumPath($post['image']);
											if (file_exists(JAWS_DATA . 'files'.$medium)) {
												$image_src = $GLOBALS['app']->getDataURL() . 'files'.$medium;
											} else if (file_exists(JAWS_DATA . 'files'.$post['image'])) {
												$image_src = $GLOBALS['app']->getDataURL() . 'files'.$post['image'];
											}
										}
									}
									if (!empty($image_src)) {
										$image = $xss->filter($GLOBALS['app']->UTF8->str_replace('#', '%23', $image_src));
										$image_found = true;
										$image_style = '';
										if ($i > 0) {
											$image_style = 'style="display: none;" ';
										}
										$tpl->SetBlock('layout/slideshow/image');
										$tpl->SetVariable('image_style', $image_style);
										$tpl->SetVariable('image_id', $post['id']);
										$tpl->SetVariable('image_linkid', $cid);
										$tpl->SetVariable('image_src', $image);
										if (isset($post['fast_url']) && !empty($post['fast_url'])) {
											$fast_url = $post['fast_url'];
										} else {
											$fast_url = $post['id'];
										}
										$tpl->SetVariable('image_caption', '<b>'.$post['title'].'</b>&nbsp;<br />'.(isset($post['description']) && !empty($post['description']) ? substr(strip_tags($post['description']), 0, 100).'...' : ' '));
										$tpl->SetVariable('image_count', $i);
										$tpl->SetVariable('image_href', $GLOBALS['app']->Map->GetURLFor('Store', 'Product', array('id' => $fast_url)));
										$image_dimensions = "$('store-img-".$post['id']."').style.width = slideshow".$cid."_width + 'px';\n";
										$image_dimensions .= "$('store-img-".$post['id']."').style.height = slideshow".$cid."_height + 'px';\n";
										$tpl->SetVariable('image_dimensions', $image_dimensions);
										$tpl->ParseBlock('layout/slideshow/image');
										$i++;
									}
								}
							}
						}
						if ($post_count <= 0 || $image_found === false) {
							$tpl->SetBlock('layout/slideshow/image');
							$tpl->SetVariable('image_id', 0);
							$tpl->SetVariable('image_linkid', $cid);
							$tpl->SetVariable('image_src', $GLOBALS['app']->GetJawsURL() . "/gadgets/FlashGallery/images/gallery_no_images.jpg");
							$tpl->SetVariable('image_caption', "Currently there are no featured products.");
							$tpl->SetVariable('image_count', 0);
							$tpl->SetVariable('image_href', $GLOBALS['app']->Map->GetURLFor('Store', 'Category', array('id' => $parents['productparentfast_url'])));
							$tpl->ParseBlock('layout/slideshow/image');
						}
					}
					
					//$submit_vars[SYNTACTS_DB ."0:$j:0"] = $xss->filter($value);
			//}
					
			//$tpl->SetVariable('layout_content', _t('FLASHGALLERY_LAYOUT_SLIDESHOW_DESCRIPTION'));
	        $tpl->ParseBlock('layout/slideshow');

			// If this is embedded, we need to pass the javascript variable that has the IFRAME height to the session 
			$display_id = md5('Store'.$cid);
			if ($embedded == true && !is_null($referer)) {	
				$tpl->SetBlock('layout/embedded');
				$tpl->SetVariable('id', $display_id);		        
				if (strtolower($referer) == strtolower($_SERVER['SERVER_NAME'])) {
					$tpl->SetVariable('referer', $GLOBALS['app']->GetJawsURL() . "/libraries/iframes/domain1/iframetest_resize1.html");		        
				} else {	
					$tpl->SetVariable('referer', "http://".$referer."/19/img/agents/19/custom_img/common_files/iframetest_resize1.html");		        
				}
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

	/**
     * Displays a slideshow.
     *
     * @access public
     * @return string
     */
    function CategoryShowOne($cid = 1, $embedded = false, $referer = null)
    {
		require_once JAWS_PATH . 'include/Jaws/Image.php';
        
        $GLOBALS['app']->Layout->AddHeadLink('gadgets/Store/resources/style.css', 'stylesheet', 'text/css');
		$GLOBALS['app']->Layout->AddScriptLink('libraries/slideshow/slideshow-min.js');
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $model = $GLOBALS['app']->LoadGadget('Store', 'Model');
		// send Products records
		$request =& Jaws_Request::getInstance();
		$fetch = array('gadget', 'id');
		$get  = $request->get($fetch, 'get');
		
		//if on a users home page, show their stuff
		if (strtolower($get['gadget']) == 'users' && !empty($get['id'])) {
			$parents = $model->GetSingleProductParentByUserID($get['id'], $cid);
		} else {
			$parents = $model->GetProductParent($cid);
		}
		if (!Jaws_Error::IsError($parents) && isset($parents['productparentid']) && !empty($parents['productparentid'])) {
			require_once JAWS_PATH . 'include/Jaws/Template.php';
			$tpl = new Jaws_Template('gadgets/Store/templates/');
	        $tpl->Load('normal.html');

	        $tpl->SetBlock('layout');
			$tpl->SetVariable('actionName', 'CategoryShowOne_' . $parents['productparentid'] . '_');
	        $tpl->SetVariable('layout_title', $parents['productparentcategory_name']);

	        $tpl->SetBlock('layout/slideshow');
			//foreach($galleryParent as $parents) {		            
					// set "slideshow" swfobject variables
					$tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . "/");
					//$tpl->SetVariable('base_url', JAWS_DPATH);
					$tpl->SetVariable('id', $cid);
					$tpl->SetVariable('slideshow_loading_image', '');
					$tpl->SetVariable('slideshow_background_color', ' ');
					// build dimensions
					$slideshow_dimensions = '';
					/*
					if ($parents['height'] != 'auto' && !empty($parents['custom_height'])) {
						$slideshow_dimensions .= "$('store-slideshow-".$cid."').style.height = '".(int)$parents['custom_height']."px';\n";	
					} else {
						$slideshow_dimensions .= " 	$('store-slideshow-".$cid."').style.height = parseInt(($('store-slideshow-".$cid."').offsetWidth".$cid.")*(.75)) + 'px';\n";
					}
					*/
					$slideshow_dimensions .= "if ($('store-slideshow-".$cid."').parentNode) {\n";
					$slideshow_dimensions .= " 	$('store-slideshow-".$cid."').parentNode.style.display = 'block';\n";	
					$slideshow_dimensions .= "	$('store-slideshow-".$cid."').parentNode.style.width = slideshow".$cid."_width + 'px';\n";
					$slideshow_dimensions .= "	$('store-slideshow-".$cid."').parentNode.style.height = slideshow".$cid."_height + 'px';\n";
					$slideshow_dimensions .= "}\n";	
					$slideshow_dimensions .= "$('store-slideshow_overlay".$cid."').style.width = slideshow".$cid."_width + 'px';\n";
					$slideshow_dimensions .= "$('store-slideshow_overlay".$cid."').style.height = slideshow".$cid."_height + 'px';\n";
					$slideshow_dimensions .= "$('store-slideshow-".$cid."').style.width = slideshow".$cid."_width + 'px';\n";
					$slideshow_dimensions .= "$('store-slideshow-".$cid."').style.height = slideshow".$cid."_height + 'px';\n";
					$slideshow_dimensions .= "$('store-PlayButton".$cid."').style.top = (slideshow".$cid."_height-50)*(-1)+'px';\n";
					$slideshow_dimensions .= "$('store-PauseButton".$cid."').style.top = (slideshow".$cid."_height-50)*(-1)+'px';\n";
					$slideshow_dimensions .= "$('store-slide-caption".$cid."').style.width = (slideshow".$cid."_width-90)+'px';\n";
					$tpl->SetVariable('slideshow_title', $xss->filter($parents['productparentcategory_name']));
					//$tpl->SetVariable('slideshow_overlay_image', 'url('.$GLOBALS['app']->getDataURL('', true) . 'files'.$xss->filter($parents['overlay_image']).')');
					$tpl->SetVariable('slideshow_height', 299);
					$tpl->SetVariable('slideshow_width', 343);
					$tpl->SetVariable('slideshow_timer', '');
					$tpl->SetVariable('slideshow_load_immediately', 'true');
					$tpl->SetVariable('slideshow_dimensions', $slideshow_dimensions);
					$tpl->SetVariable('slideshow_textbar_bkgnd', "url(../../../images/transparent.png) 0 0;");
					$posts = $model->GetAllProductsOfParent($cid);
					if (!Jaws_Error::IsError($posts)) {
						$image_found = false;
				        $post_count = count($posts);
						if (!$post_count <= 0) {
							$rand_key = rand(0, $post_count);
							$tpl->SetVariable('slideshow_total', 1);
							reset($posts);
							$i = 0;
							if (isset($posts[$rand_key]) && !empty($posts[$rand_key])) {	
								if (isset($post['image']) && !empty($post['image']) && $post['active'] == "Y") {
									$image_src = '';	
									if (isset($post['image']) && !empty($post['image'])) {
										$post['image'] = $xss->parse(strip_tags($post['image']));
										$tpl->SetVariable('image', $post['image']);
										if (substr(strtolower($post['image']), 0, 4) == "http") {
											$image_src = $post['image'];
											if (substr(strtolower($post['image']), 0, 7) == "http://") {
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
											$medium = Jaws_Image::GetMediumPath($post['image']);
											if (file_exists(JAWS_DATA . 'files'.$medium)) {
												$image_src = $GLOBALS['app']->getDataURL() . 'files'.$medium;
											} else if (file_exists(JAWS_DATA . 'files'.$post['image'])) {
												$image_src = $GLOBALS['app']->getDataURL() . 'files'.$post['image'];
											}
										}
									}
									if (!empty($image_src)) {
										$image = $xss->filter($GLOBALS['app']->UTF8->str_replace('#', '%23', $image_src));
										$image_found = true;
										$image_style = '';
										if ($i > 0) {
											$image_style = 'style="display: none;" ';
										}
										$tpl->SetBlock('layout/slideshow/image');
										$tpl->SetVariable('image_style', $image_style);
										$tpl->SetVariable('image_id', $post['id']);
										$tpl->SetVariable('image_linkid', $cid);
										$tpl->SetVariable('image_src', $image);
										if (isset($post['fast_url']) && !empty($post['fast_url'])) {
											$fast_url = $post['fast_url'];
										} else {
											$fast_url = $post['id'];
										}
										$tpl->SetVariable('image_caption', '<b>'.$post['title'].'</b>&nbsp;<br />'.(isset($post['description']) && !empty($post['description']) ? substr(strip_tags($post['description']), 0, 100).'...' : ' '));
										$tpl->SetVariable('image_count', $i);
										$tpl->SetVariable('image_href', $GLOBALS['app']->Map->GetURLFor('Store', 'Product', array('id' => $fast_url)));
										$image_dimensions = "$('store-img-".$post['id']."').style.width = slideshow".$cid."_width + 'px';\n";
										$image_dimensions .= "$('store-img-".$post['id']."').style.height = slideshow".$cid."_height + 'px';\n";
										$tpl->SetVariable('image_dimensions', $image_dimensions);
										$tpl->ParseBlock('layout/slideshow/image');
										$i++;
									}
								}
							}
						}
						if ($post_count <= 0 || $image_found === false) {
							$tpl->SetBlock('layout/slideshow/image');
							$tpl->SetVariable('image_id', 0);
							$tpl->SetVariable('image_linkid', $cid);
							$tpl->SetVariable('image_src', $GLOBALS['app']->GetJawsURL() . "/gadgets/FlashGallery/images/gallery_no_images.jpg");
							$tpl->SetVariable('image_caption', "Currently there are no featured products.");
							$tpl->SetVariable('image_count', 0);
							$tpl->SetVariable('image_href', $GLOBALS['app']->Map->GetURLFor('Store', 'Category', array('id' => $parents['productparentfast_url'])));
							$tpl->ParseBlock('layout/slideshow/image');
						}
					}
					
					//$submit_vars[SYNTACTS_DB ."0:$j:0"] = $xss->filter($value);
			//}
					
			//$tpl->SetVariable('layout_content', _t('FLASHGALLERY_LAYOUT_SLIDESHOW_DESCRIPTION'));
	        $tpl->ParseBlock('layout/slideshow');

			// If this is embedded, we need to pass the javascript variable that has the IFRAME height to the session 
			$display_id = md5('Store'.$cid);
			if ($embedded == true && !is_null($referer)) {	
				$tpl->SetBlock('layout/embedded');
				$tpl->SetVariable('id', $display_id);		        
				if (strtolower($referer) == strtolower($_SERVER['SERVER_NAME'])) {
					$tpl->SetVariable('referer', $GLOBALS['app']->GetJawsURL() . "/libraries/iframes/domain1/iframetest_resize1.html");		        
				} else {	
					$tpl->SetVariable('referer', "http://".$referer."/19/img/agents/19/custom_img/common_files/iframetest_resize1.html");		        
				}
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

    /**
     * Displays a layout block of pages.
     *
     * @access public
     * @return string
     */
    function Index($pid = null, $layout = 'full', $uid = null, $embedded = false, $referer = null)
    {
        $GLOBALS['app']->Layout->AddHeadLink('gadgets/Store/resources/style.css', 'stylesheet', 'text/css');
        $model = $GLOBALS['app']->LoadGadget('Store', 'Model');
        $pid = (is_null($pid) ? 0 : $pid);
		if (!is_null($uid)) {
			$parents = $model->GetProductParentsByUserID($uid);
		} else {
			$parents = $model->GetAllSubCategoriesOfParent($pid);
        }
		if (Jaws_Error::IsError($parents)) {
            return _t('STORE_ERROR_INDEX_NOT_LOADED');
        }

		$request =& Jaws_Request::getInstance();
		$embed_id  = $request->get('id', 'get');

		$date = $GLOBALS['app']->loadDate();
		$xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $tpl = new Jaws_Template('gadgets/Store/templates/');
        $tpl->Load('normal.html');
        $tpl->SetBlock('index');
		$tpl->SetVariable('ulclass', ($layout == 'items' ? " custom_indexSubList" : ''));
        
		if ($layout == 'full') {
			$tpl->SetBlock('index/header');
			$tpl->SetVariable('actionName', 'Display');
			$tpl->SetVariable('title', '');
			if ($embedded == true && !is_null($referer) && isset($embed_id)) {
				$tpl->SetVariable('id', $embed_id);
			} else {
				$tpl->SetVariable('id', 'List');
			}
			//$tpl->SetVariable('link', $GLOBALS['app']->Map->GetURLFor('CustomPage', 'Index'));
			$tpl->ParseBlock('index/header');
        }
		$base_url = $GLOBALS['app']->GetSiteURL().'/';
		$liclass = ($layout == 'items' ? " custom_indexSubItem" : '');
		$count_string = " ". _t('STORE_PRODUCTS');
		$update_string = _t('STORE_LAST_UPDATE') . ': ';
        foreach ($parents as $page) {
			$total = $model->GetRecursiveTotalOfProductsOfParent($page['productparentid']);
			if (Jaws_Error::IsError($total) || !is_numeric($total) || (int)$total == 0) {
				continue;
			}
			$main_image_src = '';
			if (!empty($page['productparentimage']) && isset($page['productparentimage'])) {
				$page['productparentimage'] = $xss->filter(strip_tags($page['productparentimage']));
				if (substr(strtolower($page['productparentimage']), 0, 4) == "http") {
					if (substr(strtolower($page['productparentimage']), 0, 7) == "http://") {
						$main_image_src = explode('http://', $page['productparentimage']);
						foreach ($main_image_src as $img_src) {
							if (!empty($img_src)) {
								$main_image_src = 'http://'.$img_src;
								break;
							}
						}
					} else {
						$main_image_src = explode('https://', $page['productparentimage']);
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
					$thumb = Jaws_Image::GetThumbPath($page['productparentimage']);
					$medium = Jaws_Image::GetMediumPath($page['productparentimage']);
					if (file_exists(JAWS_DATA . 'files'.$thumb)) {
						$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$thumb;
					} else if (file_exists(JAWS_DATA . 'files'.$medium)) {
						$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$medium;
					} else if (file_exists(JAWS_DATA . 'files'.$page['productparentimage'])) {
						$main_image_src = $GLOBALS['app']->getDataURL() . 'files'.$page['productparentimage'];
					}
				}
			}
			$image_exists = "";
			if (!empty($main_image_src)) {
				$image_exists = "<img class=\"custom_indexImage\" border=\"0\" src=\"".$main_image_src."\" width=\"150\" />";
			}
			if ($page['productparentactive'] == 'Y' || $embedded == true) {
				$tpl->SetBlock('index/item');
				$tpl->SetVariable('title', strip_tags($page['productparentcategory_name']));
				$tpl->SetVariable('liclass', $liclass);
				$tpl->SetVariable('count_string',  $count_string);
				$tpl->SetVariable('count', $total);
				$tpl->SetVariable('update_string',  $update_string);
				$tpl->SetVariable('updated', $date->Format($page['productparentupdated']));
				if ($embedded == false) {
					$param = array('id' => !empty($page['productparentfast_url']) ? $xss->filter($page['productparentfast_url']) : $page['productparentid']);
					$link = $GLOBALS['app']->Map->GetURLFor('Store', 'Category', $param);
					$tpl->SetVariable('desc', (!empty($page['productparentdescription']) ? (strlen(strip_tags($page['productparentdescription'])) > 247 ? substr(strip_tags($page['productparentdescription']),0,247)."&nbsp;<a href=\"".$link."\">... Read More</a>&nbsp;&nbsp;&nbsp;&nbsp;" : strip_tags($page['productparentdescription'])."&nbsp;&nbsp;&nbsp;&nbsp;") : ''));
				} else {
					$link = $base_url."index.php?gadget=Store&action=EmbedProductParent&id=".$page['id']."&mode=category&referer=".(!is_null($referer) ? $referer : "");
					$tpl->SetVariable('desc', (!empty($page['productparentdescription']) ? (strlen(strip_tags($page['productparentdescription'])) > 247 ? substr(strip_tags($page['productparentdescription']),0,247)."&nbsp;<a href=\"".$link."\">... Read More</a>&nbsp;&nbsp;&nbsp;&nbsp;" : strip_tags($page['productparentdescription'])."&nbsp;&nbsp;&nbsp;&nbsp;") : ''));
				}
				$tpl->SetVariable('link',  $link);
				$tpl->SetVariable('image', '<a class="custom_indexImageLink" href="'.$link.'">'.$image_exists.'</a>');
				$indexHTML = $this->Index($page['productparentid'], 'items', $uid, $embedded, $referer);
				if ($indexHTML == _t('STORE_ERROR_INDEX_NOT_LOADED')) {
					$indexHTML = '';
				}
				$tpl->SetVariable('child_items', $indexHTML);
				$tpl->ParseBlock('index/item');
			}
        }
        if ($layout == 'full') {
			$tpl->SetBlock('index/footer');
			$display_id = md5('Store'.(!empty($embed_id) && is_numeric($embed_id) ? (int)$embed_id : 'all'));
			if ($embedded == true && !is_null($referer)) {	
				$tpl->SetBlock('index/footer/embedded');
				$tpl->SetVariable('id', $display_id);		        
				if (strtolower($referer) == strtolower($_SERVER['SERVER_NAME'])) {
					$tpl->SetVariable('referer', $GLOBALS['app']->GetJawsURL() . "/libraries/iframes/domain1/iframetest_resize1.html");		        
				} else {	
					$tpl->SetVariable('referer', "http://".$referer."/19/img/agents/19/custom_img/common_files/iframetest_resize1.html");		        
				}
				$tpl->ParseBlock('index/footer/embedded');
			} else {
				$tpl->SetBlock('index/footer/not_embedded');
				$tpl->SetVariable('id', $display_id);		        
				$tpl->ParseBlock('index/footer/not_embedded');
			}
			$tpl->ParseBlock('index/footer');
        }
		$tpl->ParseBlock('index');

        return $tpl->Get();
    }
    
	/**
     * Displays a layout block of brands.
     *
     * @access public
     * @return string
     */
    function BrandIndex($embedded = false, $referer = null)
    {
        $GLOBALS['app']->Layout->AddHeadLink('gadgets/Store/resources/style.css', 'stylesheet', 'text/css');
        $model = $GLOBALS['app']->LoadGadget('Store', 'Model');
		$pages = $model->GetBrands();
		if (Jaws_Error::IsError($pages)) {
            return _t('STORE_ERROR_BRANDINDEX_NOT_LOADED');
        }

		$request =& Jaws_Request::getInstance();
		$embed_id  = $request->get('id', 'get');

		$date = $GLOBALS['app']->loadDate();
		$xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $tpl = new Jaws_Template('gadgets/Store/templates/');
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
			if ($page['active'] == 'Y' || $embedded == true) {
				$tpl->SetBlock('index/item');
				$tpl->SetVariable('title', strip_tags($page['title']));
				$tpl->SetVariable('update_string',  _t('STORE_LAST_UPDATE') . ': ');
				$tpl->SetVariable('updated', $date->Format($page['updated']));
				if ($embedded == false) {
					$param = array('id' => $page['id']);
					$link = $GLOBALS['app']->Map->GetURLFor('Store', 'Brand', $param);
					$tpl->SetVariable('desc', (!empty($page['description']) ? (strlen(strip_tags($page['description'])) > 247 ? substr(strip_tags($page['description']),0,247)."&nbsp;<a href=\"".$link."\">... Read More</a>&nbsp;&nbsp;&nbsp;&nbsp;" : strip_tags($page['description'])."&nbsp;&nbsp;&nbsp;&nbsp;") : ''));
				} else {
					$base_url = $GLOBALS['app']->GetSiteURL().'/';
					$link = $base_url."index.php?gadget=Store&action=EmbedBrand&id=".$page['id']."&mode=brand&referer=".(!is_null($referer) ? $referer : "");
					$tpl->SetVariable('desc', (!empty($page['description']) ? (strlen(strip_tags($page['description'])) > 247 ? substr(strip_tags($page['description']),0,247)."&nbsp;<a href=\"".$link."\">... Read More</a>&nbsp;&nbsp;&nbsp;&nbsp;" : strip_tags($page['description'])."&nbsp;&nbsp;&nbsp;&nbsp;") : ''));
				}
				$tpl->SetVariable('link',  $link);
				$tpl->ParseBlock('index/item');
			}
        }
		$display_id = md5('Store'.(!empty($embed_id) && is_numeric($embed_id) ? (int)$embed_id : 'all'));
		if ($embedded == true && !is_null($referer) && isset($embed_id)) {	
			$tpl->SetBlock('index/embedded');
			$tpl->SetVariable('id', $display_id);		        
			if (strtolower($referer) == strtolower($_SERVER['SERVER_NAME'])) {
				$tpl->SetVariable('referer', $GLOBALS['app']->GetJawsURL() . "/libraries/iframes/domain1/iframetest_resize1.html");		        
			} else {	
				$tpl->SetVariable('referer', "http://".$referer."/19/img/agents/19/custom_img/common_files/iframetest_resize1.html");		        
			}
			$tpl->ParseBlock('index/embedded');
		} else {
			$tpl->SetBlock('index/not_embedded');
			$tpl->SetVariable('id', $display_id);		        
			$tpl->ParseBlock('index/not_embedded');
		}
        $tpl->ParseBlock('index');

        return $tpl->Get();
    }
	
	/**
     * Displays five random products of users in given group ID
     *
     * @access public
     * @return XHTML 
     */
    function ShowFiveProductsOfGroup($gid = 1, $embedded = false, $referer = null)
    {
		require_once JAWS_PATH . 'include/Jaws/Image.php';
		require_once JAWS_PATH . 'include/Jaws/User.php';
        $GLOBALS['app']->Layout->AddHeadLink('gadgets/Store/resources/style.css', 'stylesheet', 'text/css');
		$uModel = new Jaws_User;
        $model = $GLOBALS['app']->LoadGadget('Store', 'Model');
       
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');

		$groupInfo = $uModel->GetGroupInfoById($gid);
		if (!Jaws_Error::IsError($groupInfo) && isset($groupInfo['id']) && !empty($groupInfo['id'])) {
			require_once JAWS_PATH . 'include/Jaws/Template.php';
			$tpl = new Jaws_Template('gadgets/Store/templates/');
	        $tpl->Load('normal.html');

	        $tpl->SetBlock('layout');
			$tpl->SetVariable('actionName', 'FiveProductsOfGroup_' . $groupInfo['id'] . '_');
	        $tpl->SetVariable('layout_title', "Featured Products");
			$tpl->SetVariable('id', 'FiveProductsOfGroup');
	        $tpl->SetBlock('layout/featuredproducts');
			$tpl->SetVariable('gid', 'FiveProductsOfGroup');
			
			$ba = array();
			$i = 0;
			$products = $model->GetStoreOfGroup($groupInfo['id'], 'sort_order', 'ASC', 'Y');
			if (!Jaws_Error::IsError($products)) {
				foreach($products as $p) {		            
					if (isset($p['id']) && !empty($p['title']) && !empty($p['image']) && $p['featured'] == 'Y') {					            
						$ba[$i] = $p['id'];
						$i++;
					}
				}
		
				// Choose random IDs
				if (isset($ba[0])) {
					$total = $i;
					if ($i > 4) {
						$i = 5;
					}
					for ($b=0; $b<$i; $b++) {
						$r = 0;
						while (true && $r <= $total) {
							$buttons_rand = array_rand($ba);
							if (!in_array('product_'.$ba[$buttons_rand], $GLOBALS['app']->_ItemsOnLayout)) {
								array_push($GLOBALS['app']->_ItemsOnLayout, 'product_'.$ba[$buttons_rand]);
								break;
							} else {
								$buttons_rand = -1;
							}
							$r++;
						}
						$productInfo = $model->GetProduct($ba[$buttons_rand]);
						if (!Jaws_Error::IsError($productInfo) && isset($productInfo['id']) && !empty($productInfo['id'])) {
							$tpl->SetBlock('layout/featuredproducts/item');
							$tpl->SetVariable('pid', $productInfo['id']);
							$title = '';
							$title = $xss->filter(strip_tags(str_replace('"', "'",($productInfo['title']))));
							//$tpl->SetVariable('title', (strlen($title) > 15 ? substr(htmlspecialchars_decode($title), 0, 15) . '...' : $title));
							$tpl->SetVariable('title', $title);
							$tpl->SetVariable('caption', $title);
							$href = '';
							$href = $GLOBALS['app']->Map->GetURLFor('Store', 'Product', array('id' => $productInfo['fast_url']));
							$tpl->SetVariable('href', $href);
							
							$price = 0;
							if (!empty($productInfo['price']) && ($productInfo['price'] > 0)) {
								$price = number_format($productInfo['price'], 2, '.', '');
							}
							// sales
							$now = $GLOBALS['db']->Date();
							$sale_price = $price;
							$price_string = '$'.number_format($price, 2, '.', ',');
							if (isset($productInfo['sales']) && !empty($productInfo['sales'])) {
								$propSales = explode(',', $productInfo['sales']);
								$saleCount = 0;
								foreach($propSales as $propSale) {		            
									$saleParent = $model->GetSale((int)$propSale);
									if (!Jaws_Error::IsError($saleParent)) {
										if ($saleParent['active'] == 'Y' && ($now > $saleParent['startdate'] && $now < $saleParent['enddate'])) {
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
									$price_string = '$'.($sale_price > 0 ? number_format($sale_price, 2, '.', ',') : 0.00);
								}
							}
							$tpl->SetVariable('price', $xss->filter($price_string));
							$image_src = '';
							if (isset($productInfo['image']) && !empty($productInfo['image'])) {
								$productInfo['image'] = $xss->filter(strip_tags($productInfo['image']));
								if (substr(strtolower($productInfo['image']), 0, 4) == "http") {
									$image_src = $productInfo['image'];
									if (substr(strtolower($productInfo['image']), 0, 7) == "http://") {
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
									$thumb = Jaws_Image::GetThumbPath($productInfo['image']);
									$medium = Jaws_Image::GetMediumPath($productInfo['image']);
									if (file_exists(JAWS_DATA . 'files'.$thumb)) {
										$image_src = $GLOBALS['app']->getDataURL() . 'files'.$thumb;
									} else if (file_exists(JAWS_DATA . 'files'.$medium)) {
										$image_src = $GLOBALS['app']->getDataURL() . 'files'.$medium;
									} else if (file_exists(JAWS_DATA . 'files'.$productInfo['image'])) {
										$image_src = $GLOBALS['app']->getDataURL() . 'files'.$productInfo['image'];
									}
								}
							}
							if (!empty($image_src)) {
								$tpl->SetBlock('layout/featuredproducts/item/image');
								//$tpl->SetVariable('base_url', JAWS_DPATH);
								$tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . "/");
								$tpl->SetVariable('image_src', $image_src);
								$tpl->SetVariable('image_caption', $title);
								$tpl->SetVariable('image_href', $href);
								$tpl->ParseBlock('layout/featuredproducts/item/image');
							} else {
								$tpl->SetBlock('layout/featuredproducts/item/no_image');
								$tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . "/");
								//$tpl->SetVariable('base_url', JAWS_DPATH);
								$tpl->SetVariable('image_href', $href);
								$tpl->ParseBlock('layout/featuredproducts/item/no_image');
							}
							$tpl->ParseBlock('layout/featuredproducts/item');
						}
					}
				}
			}
	        $tpl->ParseBlock('layout/featuredproducts');

			// If this is embedded, we need to pass the javascript variable that has the IFRAME height to the session 
			$display_id = md5('Store'.'FiveProductsOfGroup'.$gid);
			if ($embedded == true && !is_null($referer)) {	
				$tpl->SetBlock('layout/embedded');
				$tpl->SetVariable('id', $display_id);		        
				if (strtolower($referer) == strtolower($_SERVER['SERVER_NAME'])) {
					$tpl->SetVariable('referer', $GLOBALS['app']->GetJawsURL() . "/libraries/iframes/domain1/iframetest_resize1.html");		        
				} else {	
					$tpl->SetVariable('referer', "http://".$referer."/19/img/agents/19/custom_img/common_files/iframetest_resize1.html");		        
				}
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

	/**
     * Displays five random products
     *
     * @access public
     * @return XHTML 
     */
    function ShowTwoProducts($embedded = false, $referer = null)
    {
		require_once JAWS_PATH . 'include/Jaws/Image.php';
        $GLOBALS['app']->Layout->AddHeadLink('gadgets/Store/resources/style.css', 'stylesheet', 'text/css');
        $model = $GLOBALS['app']->LoadGadget('Store', 'Model');
       
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');

		require_once JAWS_PATH . 'include/Jaws/Template.php';
		$tpl = new Jaws_Template('gadgets/Store/templates/');
		$tpl->Load('normal.html');

		$tpl->SetBlock('layout');
		$tpl->SetVariable('actionName', 'TwoProducts_');
		$tpl->SetVariable('layout_title', "Featured Products");
		$tpl->SetVariable('id', 'TwoProducts');
		$tpl->SetBlock('layout/featuredproducts');
		$tpl->SetVariable('gid', 'TwoProducts');
		
		$ba = array();
		$i = 0;
		// Load Update hook
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout(
			'onLoadShowTwoProducts', true
		);
		if (isset($res['items'])) {
			$products = array();
			foreach ($res['items'] as $item) {
				$products[] = $item;
			}
		} else {
			$products = $model->GetProducts(null, 'sort_order', 'ASC', false, null, 'Y');
		}
		if (!Jaws_Error::IsError($products)) {
			foreach($products as $p) {		            
				if (isset($p['id']) && !empty($p['title']) && !empty($p['image']) && $p['featured'] == 'Y') {					            
					$ba[$i] = $p['id'];
					$i++;
				}
			}
	
			// Choose random IDs
			if (isset($ba[0])) {
				$total = $i;
				if ($i > 1) {
					$i = 2;
				}
				for ($b=0; $b<$i; $b++) {
					$r = 0;
					while (true && $r <= $total) {
						$buttons_rand = array_rand($ba);
						if (!in_array('product_'.$ba[$buttons_rand], $GLOBALS['app']->_ItemsOnLayout)) {
							array_push($GLOBALS['app']->_ItemsOnLayout, 'product_'.$ba[$buttons_rand]);
							break;
						} else {
							$buttons_rand = -1;
						}
						$r++;
					}
					$productInfo = $model->GetProduct($ba[$buttons_rand]);
					if (!Jaws_Error::IsError($productInfo) && isset($productInfo['id']) && !empty($productInfo['id'])) {
						$tpl->SetBlock('layout/featuredproducts/item');
						$tpl->SetVariable('pid', $productInfo['id']);
						$title = '';
						$title = $xss->filter(strip_tags(str_replace('"', "'",($productInfo['title']))));
						//$tpl->SetVariable('title', (strlen($title) > 15 ? substr(htmlspecialchars_decode($title), 0, 15) . '...' : $title));
						$tpl->SetVariable('title', $title);
						$tpl->SetVariable('caption', $title);
						$href = '';
						$href = $GLOBALS['app']->Map->GetURLFor('Store', 'Product', array('id' => $productInfo['fast_url']));
						$tpl->SetVariable('href', $href);
						
						$price = 0;
						if (!empty($productInfo['price']) && ($productInfo['price'] > 0)) {
							$price = number_format($productInfo['price'], 2, '.', '');
						}
						// sales
						$now = $GLOBALS['db']->Date();
						$sale_price = $price;
						$price_string = '$'.number_format($price, 2, '.', ',');
						if (isset($productInfo['sales']) && !empty($productInfo['sales'])) {
							$propSales = explode(',', $productInfo['sales']);
							$saleCount = 0;
							foreach($propSales as $propSale) {		            
								$saleParent = $model->GetSale((int)$propSale);
								if (!Jaws_Error::IsError($saleParent)) {
									if ($saleParent['active'] == 'Y' && ($now > $saleParent['startdate'] && $now < $saleParent['enddate'])) {
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
								$price_string = '$'.($sale_price > 0 ? number_format($sale_price, 2, '.', ',') : 0.00);
							}
						}
						$tpl->SetVariable('price', $xss->filter($price_string));
						$image_src = '';
						if (isset($productInfo['image']) && !empty($productInfo['image'])) {
							$productInfo['image'] = $xss->filter(strip_tags($productInfo['image']));
							if (substr(strtolower($productInfo['image']), 0, 4) == "http") {
								$image_src = $productInfo['image'];
								if (substr(strtolower($productInfo['image']), 0, 7) == "http://") {
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
								$thumb = Jaws_Image::GetThumbPath($productInfo['image']);
								$medium = Jaws_Image::GetMediumPath($productInfo['image']);
								if (file_exists(JAWS_DATA . 'files'.$thumb)) {
									$image_src = $GLOBALS['app']->getDataURL() . 'files'.$thumb;
								} else if (file_exists(JAWS_DATA . 'files'.$medium)) {
									$image_src = $GLOBALS['app']->getDataURL() . 'files'.$medium;
								} else if (file_exists(JAWS_DATA . 'files'.$productInfo['image'])) {
									$image_src = $GLOBALS['app']->getDataURL() . 'files'.$productInfo['image'];
								}
							}
						}
						if (!empty($image_src)) {
							$tpl->SetBlock('layout/featuredproducts/item/image');
							$tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . "/");
							//$tpl->SetVariable('base_url', JAWS_DPATH);
							$tpl->SetVariable('image_src', $image_src);
							$tpl->SetVariable('image_caption', $title);
							$tpl->SetVariable('image_href', $href);
							$tpl->ParseBlock('layout/featuredproducts/item/image');
						} else {
							$tpl->SetBlock('layout/featuredproducts/item/no_image');
							$tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . "/");
							//$tpl->SetVariable('base_url', JAWS_DPATH);
							$tpl->SetVariable('image_href', $href);
							$tpl->ParseBlock('layout/featuredproducts/item/no_image');
						}
						$tpl->ParseBlock('layout/featuredproducts/item');
					}
				}
			}
		}
		$tpl->ParseBlock('layout/featuredproducts');

		// If this is embedded, we need to pass the javascript variable that has the IFRAME height to the session 
		$display_id = md5('Store'.'TwoProducts');
		if ($embedded == true && !is_null($referer)) {	
			$tpl->SetBlock('layout/embedded');
			$tpl->SetVariable('id', $display_id);		        
			if (strtolower($referer) == strtolower($_SERVER['SERVER_NAME'])) {
				$tpl->SetVariable('referer', $GLOBALS['app']->GetJawsURL() . "/libraries/iframes/domain1/iframetest_resize1.html");		        
			} else {	
				$tpl->SetVariable('referer', "http://".$referer."/19/img/agents/19/custom_img/common_files/iframetest_resize1.html");		        
			}
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
     * Displays five random products
     *
     * @access public
     * @return XHTML 
     */
    function ShowFiveProducts($embedded = false, $referer = null)
    {
		require_once JAWS_PATH . 'include/Jaws/Image.php';
        $GLOBALS['app']->Layout->AddHeadLink('gadgets/Store/resources/style.css', 'stylesheet', 'text/css');
        $model = $GLOBALS['app']->LoadGadget('Store', 'Model');
       
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');

		require_once JAWS_PATH . 'include/Jaws/Template.php';
		$tpl = new Jaws_Template('gadgets/Store/templates/');
		$tpl->Load('normal.html');

		$tpl->SetBlock('layout');
		$tpl->SetVariable('actionName', 'FiveProducts_');
		$tpl->SetVariable('layout_title', "Featured Products");
		$tpl->SetVariable('id', 'FiveProducts');
		$tpl->SetBlock('layout/featuredproducts');
		$tpl->SetVariable('gid', 'FiveProducts');
		
		$ba = array();
		$i = 0;
		// Load Update hook
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout(
			'onLoadShowFiveProducts', true
		);
		if (isset($res['items'])) {
			$products = array();
			foreach ($res['items'] as $item) {
				$products[] = $item;
			}
		} else {
			$products = $model->GetProducts(null, 'sort_order', 'ASC', false, null, 'Y');
		}
		if (!Jaws_Error::IsError($products)) {
			foreach($products as $p) {		            
				if (isset($p['id']) && !empty($p['title']) && !empty($p['image']) && $p['featured'] == 'Y') {					            
					$ba[$i] = $p['id'];
					$i++;
				}
			}
	
			// Choose random IDs
			if (isset($ba[0])) {
				if ($i > 4) {
					$i = 5;
				}
				for ($b=0; $b<$i; $b++) {
					while (true) {
						$buttons_rand = array_rand($ba);
						if (!in_array('product_'.$ba[$buttons_rand], $GLOBALS['app']->_ItemsOnLayout)) {
							array_push($GLOBALS['app']->_ItemsOnLayout, 'product_'.$ba[$buttons_rand]);
							break;
						} else {
							$buttons_rand = -1;
						}
					}
					$productInfo = $model->GetProduct($ba[$buttons_rand]);
					if (!Jaws_Error::IsError($productInfo) && isset($productInfo['id']) && !empty($productInfo['id'])) {
						$tpl->SetBlock('layout/featuredproducts/item');
						$tpl->SetVariable('pid', $productInfo['id']);
						$title = '';
						$title = $xss->filter(strip_tags(str_replace('"', "'",($productInfo['title']))));
						//$tpl->SetVariable('title', (strlen($title) > 15 ? substr(htmlspecialchars_decode($title), 0, 15) . '...' : $title));
						$tpl->SetVariable('title', $title);
						$tpl->SetVariable('caption', $title);
						$href = '';
						$href = $GLOBALS['app']->Map->GetURLFor('Store', 'Product', array('id' => $productInfo['fast_url']));
						$tpl->SetVariable('href', $href);
						
						$price = 0;
						if (!empty($productInfo['price']) && ($productInfo['price'] > 0)) {
							$price = number_format($productInfo['price'], 2, '.', '');
						}
						// sales
						$now = $GLOBALS['db']->Date();
						$sale_price = $price;
						$price_string = '$'.number_format($price, 2, '.', ',');
						if (isset($productInfo['sales']) && !empty($productInfo['sales'])) {
							$propSales = explode(',', $productInfo['sales']);
							$saleCount = 0;
							foreach($propSales as $propSale) {		            
								$saleParent = $model->GetSale((int)$propSale);
								if (!Jaws_Error::IsError($saleParent)) {
									if ($saleParent['active'] == 'Y' && ($now > $saleParent['startdate'] && $now < $saleParent['enddate'])) {
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
								$price_string = '$'.($sale_price > 0 ? number_format($sale_price, 2, '.', ',') : 0.00);
							}
						}
						$tpl->SetVariable('price', $xss->filter($price_string));
						$image_src = '';
						if (isset($productInfo['image']) && !empty($productInfo['image'])) {
							$productInfo['image'] = $xss->filter(strip_tags($productInfo['image']));
							if (substr(strtolower($productInfo['image']), 0, 4) == "http") {
								$image_src = $productInfo['image'];
								if (substr(strtolower($productInfo['image']), 0, 7) == "http://") {
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
								$thumb = Jaws_Image::GetThumbPath($productInfo['image']);
								$medium = Jaws_Image::GetMediumPath($productInfo['image']);
								if (file_exists(JAWS_DATA . 'files'.$thumb)) {
									$image_src = $GLOBALS['app']->getDataURL() . 'files'.$thumb;
								} else if (file_exists(JAWS_DATA . 'files'.$medium)) {
									$image_src = $GLOBALS['app']->getDataURL() . 'files'.$medium;
								} else if (file_exists(JAWS_DATA . 'files'.$productInfo['image'])) {
									$image_src = $GLOBALS['app']->getDataURL() . 'files'.$productInfo['image'];
								}
							}
						}
						if (!empty($image_src)) {
							$tpl->SetBlock('layout/featuredproducts/item/image');
							$tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . "/");
							//$tpl->SetVariable('base_url', JAWS_DPATH);
							$tpl->SetVariable('image_src', $image_src);
							$tpl->SetVariable('image_caption', $title);
							$tpl->SetVariable('image_href', $href);
							$tpl->ParseBlock('layout/featuredproducts/item/image');
						} else {
							$tpl->SetBlock('layout/featuredproducts/item/no_image');
							$tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . "/");
							//$tpl->SetVariable('base_url', JAWS_DPATH);
							$tpl->SetVariable('image_href', $href);
							$tpl->ParseBlock('layout/featuredproducts/item/no_image');
						}
						$tpl->ParseBlock('layout/featuredproducts/item');
					}
				}
			}
		}
		$tpl->ParseBlock('layout/featuredproducts');

		// If this is embedded, we need to pass the javascript variable that has the IFRAME height to the session 
		$display_id = md5('Store'.'FiveProducts');
		if ($embedded == true && !is_null($referer)) {	
			$tpl->SetBlock('layout/embedded');
			$tpl->SetVariable('id', $display_id);		        
			if (strtolower($referer) == strtolower($_SERVER['SERVER_NAME'])) {
				$tpl->SetVariable('referer', $GLOBALS['app']->GetJawsURL() . "/libraries/iframes/domain1/iframetest_resize1.html");		        
			} else {	
				$tpl->SetVariable('referer', "http://".$referer."/19/img/agents/19/custom_img/common_files/iframetest_resize1.html");		        
			}
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
     * Displays a random "premium" product
     *
     * @access public
     * @return XHTML 
     */
    function ShowPremiumProduct($embedded = false, $referer = null)
    {
		require_once JAWS_PATH . 'include/Jaws/Image.php';
        $GLOBALS['app']->Layout->AddHeadLink('gadgets/Store/resources/style.css', 'stylesheet', 'text/css');
        $model = $GLOBALS['app']->LoadGadget('Store', 'Model');
       
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');

		require_once JAWS_PATH . 'include/Jaws/Template.php';
		$tpl = new Jaws_Template('gadgets/Store/templates/');
		$tpl->Load('normal.html');

		$tpl->SetBlock('layout');
		$tpl->SetVariable('actionName', 'PremiumProduct_');
		$tpl->SetVariable('layout_title', "Today's Deal");
		$tpl->SetVariable('id', 'PremiumProduct');
		$tpl->SetBlock('layout/featuredproducts');
		$tpl->SetVariable('gid', 'PremiumProduct');
		
		$ba = array();
		$i = 0;
		// Load Update hook
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout(
			'onLoadShowPremiumProduct', true
		);
		if (isset($res['items'])) {
			$products = array();
			foreach ($res['items'] as $item) {
				$products[] = $item;
			}
		} else {
			$products = $model->GetProducts(null, 'premium', 'DESC', false, null, 'Y');
		}
		if (!Jaws_Error::IsError($products) && !count($products) <= 0) {
			foreach($products as $p) {		            
				if (isset($p['id']) && !empty($p['title']) && ($p['featured'] == 'Y' || $p['premium'] == 'Y')) {					            
					$ba[$i] = $p['id'];
					$i++;
				}
			}
	
			// Choose random IDs
			if (isset($ba[0])) {
				while (true) {
					$buttons_rand = array_rand($ba);
					if (!in_array('product_'.$ba[$buttons_rand], $GLOBALS['app']->_ItemsOnLayout)) {
						array_push($GLOBALS['app']->_ItemsOnLayout, 'product_'.$ba[$buttons_rand]);
						break;
					} else {
						$buttons_rand = -1;
					}
				}
				$productInfo = $model->GetProduct($ba[$buttons_rand]);
				if (!Jaws_Error::IsError($productInfo) && isset($productInfo['id']) && !empty($productInfo['id'])) {
					$tpl->SetBlock('layout/featuredproducts/item');
					$tpl->SetVariable('pid', $productInfo['id']);
					$title = '';
					$title = $xss->filter(strip_tags(str_replace('"', "'",($productInfo['title']))));
					//$tpl->SetVariable('title', (strlen($title) > 15 ? substr(htmlspecialchars_decode($title), 0, 15) . '...' : $title));
					$tpl->SetVariable('title', $title);
					$tpl->SetVariable('caption', $title);
					$href = '';
					$href = $GLOBALS['app']->Map->GetURLFor('Store', 'Product', array('id' => $productInfo['fast_url']));
					$tpl->SetVariable('href', $href);
					
					$price = 0;
					if (!empty($productInfo['price']) && ($productInfo['price'] > 0)) {
						$price = number_format($productInfo['price'], 2, '.', '');
					}
					// sales
					$now = $GLOBALS['db']->Date();
					$sale_price = $price;
					$price_string = '$'.number_format($price, 2, '.', ',');
					if (isset($productInfo['sales']) && !empty($productInfo['sales'])) {
						$propSales = explode(',', $productInfo['sales']);
						$saleCount = 0;
						foreach($propSales as $propSale) {		            
							$saleParent = $model->GetSale((int)$propSale);
							if (!Jaws_Error::IsError($saleParent)) {
								if ($saleParent['active'] == 'Y' && ($now > $saleParent['startdate'] && $now < $saleParent['enddate'])) {
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
							$price_string = '$'.($sale_price > 0 ? number_format($sale_price, 2, '.', ',') : 0.00);
						}
					}
					$tpl->SetVariable('price', $xss->filter($price_string));
					$image_src = '';
					if (isset($productInfo['image']) && !empty($productInfo['image'])) {
						$productInfo['image'] = $xss->filter(strip_tags($productInfo['image']));
						if (substr(strtolower($productInfo['image']), 0, 4) == "http") {
							$image_src = $productInfo['image'];
							if (substr(strtolower($productInfo['image']), 0, 7) == "http://") {
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
							$thumb = Jaws_Image::GetThumbPath($productInfo['image']);
							$medium = Jaws_Image::GetMediumPath($productInfo['image']);
							if (file_exists(JAWS_DATA . 'files'.$thumb)) {
								$image_src = $GLOBALS['app']->getDataURL() . 'files'.$thumb;
							} else if (file_exists(JAWS_DATA . 'files'.$medium)) {
								$image_src = $GLOBALS['app']->getDataURL() . 'files'.$medium;
							} else if (file_exists(JAWS_DATA . 'files'.$productInfo['image'])) {
								$image_src = $GLOBALS['app']->getDataURL() . 'files'.$productInfo['image'];
							}
						}
					}
					if (!empty($image_src)) {
						$tpl->SetBlock('layout/featuredproducts/item/image');
						$tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . "/");
						//$tpl->SetVariable('base_url', JAWS_DPATH);
						$tpl->SetVariable('image_src', $image_src);
						$tpl->SetVariable('image_caption', $title);
						$tpl->SetVariable('image_href', $href);
						$tpl->ParseBlock('layout/featuredproducts/item/image');
					} else {
						$tpl->SetBlock('layout/featuredproducts/item/no_image');
						$tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . "/");
						//$tpl->SetVariable('base_url', JAWS_DPATH);
						$tpl->SetVariable('image_href', $href);
						$tpl->ParseBlock('layout/featuredproducts/item/no_image');
					}
					$tpl->ParseBlock('layout/featuredproducts/item');
				}
			}
		}
		$tpl->ParseBlock('layout/featuredproducts');

		// If this is embedded, we need to pass the javascript variable that has the IFRAME height to the session 
		$display_id = md5('Store'.'PremiumProduct');
		if ($embedded == true && !is_null($referer)) {	
			$tpl->SetBlock('layout/embedded');
			$tpl->SetVariable('id', $display_id);		        
			if (strtolower($referer) == strtolower($_SERVER['SERVER_NAME'])) {
				$tpl->SetVariable('referer', $GLOBALS['app']->GetJawsURL() . "/libraries/iframes/domain1/iframetest_resize1.html");		        
			} else {	
				$tpl->SetVariable('referer', "http://".$referer."/19/img/agents/19/custom_img/common_files/iframetest_resize1.html");		        
			}
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
     * Displays a random "premium" product of group
     *
     * @access public
     * @return XHTML 
     */
    function ShowPremiumProductOfGroup($group, $embedded = false, $referer = null)
    {
		require_once JAWS_PATH . 'include/Jaws/Image.php';
        $GLOBALS['app']->Layout->AddHeadLink('gadgets/Store/resources/style.css', 'stylesheet', 'text/css');
        $model = $GLOBALS['app']->LoadGadget('Store', 'Model');
       
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');

		require_once JAWS_PATH . 'include/Jaws/Template.php';
		$tpl = new Jaws_Template('gadgets/Store/templates/');
		$tpl->Load('normal.html');

		$tpl->SetBlock('layout');
		$tpl->SetVariable('actionName', 'PremiumProductOfGroup'.$group.'_');
		$tpl->SetVariable('layout_title', "Today's Deal");
		$tpl->SetVariable('id', 'PremiumProductOfGroup'.$group);
		$tpl->SetBlock('layout/featuredproducts');
		$tpl->SetVariable('gid', 'PremiumProductOfGroup'.$group);
		
		$ba = array();
		$i = 0;
		$products = $model->GetStoreOfGroup($group);
		if (!Jaws_Error::IsError($products)) {
			foreach($products as $p) {		            
				if (isset($p['id']) && !empty($p['title']) && $p['premium'] == 'Y') {					            
					$ba[$i] = $p['id'];
					$i++;
				}
			}
	
			// Choose random IDs
			if (isset($ba[0])) {
				while (true) {
					$buttons_rand = array_rand($ba);
					if (!in_array('product_'.$ba[$buttons_rand], $GLOBALS['app']->_ItemsOnLayout)) {
						array_push($GLOBALS['app']->_ItemsOnLayout, 'product_'.$ba[$buttons_rand]);
						break;
					} else {
						$buttons_rand = -1;
					}
				}
				$productInfo = $model->GetProduct($ba[$buttons_rand]);
				if (!Jaws_Error::IsError($productInfo) && isset($productInfo['id']) && !empty($productInfo['id'])) {
					$tpl->SetBlock('layout/featuredproducts/item');
					$tpl->SetVariable('pid', $productInfo['id']);
					$title = '';
					$title = $xss->filter(strip_tags(str_replace('"', "'",($productInfo['title']))));
					//$tpl->SetVariable('title', (strlen($title) > 15 ? substr(htmlspecialchars_decode($title), 0, 15) . '...' : $title));
					$tpl->SetVariable('title', $title);
					$tpl->SetVariable('caption', $title);
					$href = '';
					$href = $GLOBALS['app']->Map->GetURLFor('Store', 'Product', array('id' => $productInfo['fast_url']));
					$tpl->SetVariable('href', $href);
					
					$price = 0;
					if (!empty($productInfo['price']) && ($productInfo['price'] > 0)) {
						$price = number_format($productInfo['price'], 2, '.', '');
					}
					// sales
					$now = $GLOBALS['db']->Date();
					$sale_price = $price;
					$price_string = '$'.number_format($price, 2, '.', ',');
					if (isset($productInfo['sales']) && !empty($productInfo['sales'])) {
						$propSales = explode(',', $productInfo['sales']);
						$saleCount = 0;
						foreach($propSales as $propSale) {		            
							$saleParent = $model->GetSale((int)$propSale);
							if (!Jaws_Error::IsError($saleParent)) {
								if ($saleParent['active'] == 'Y' && ($now > $saleParent['startdate'] && $now < $saleParent['enddate'])) {
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
							$price_string = '$'.($sale_price > 0 ? number_format($sale_price, 2, '.', ',') : 0.00);
						}
					}
					$tpl->SetVariable('price', $xss->filter($price_string));
					$image_src = '';
					if (isset($productInfo['image']) && !empty($productInfo['image'])) {
						$productInfo['image'] = $xss->filter(strip_tags($productInfo['image']));
						if (substr(strtolower($productInfo['image']), 0, 4) == "http") {
							$image_src = $productInfo['image'];
							if (substr(strtolower($productInfo['image']), 0, 7) == "http://") {
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
							$thumb = Jaws_Image::GetThumbPath($productInfo['image']);
							$medium = Jaws_Image::GetMediumPath($productInfo['image']);
							if (file_exists(JAWS_DATA . 'files'.$thumb)) {
								$image_src = $GLOBALS['app']->getDataURL() . 'files'.$thumb;
							} else if (file_exists(JAWS_DATA . 'files'.$medium)) {
								$image_src = $GLOBALS['app']->getDataURL() . 'files'.$medium;
							} else if (file_exists(JAWS_DATA . 'files'.$productInfo['image'])) {
								$image_src = $GLOBALS['app']->getDataURL() . 'files'.$productInfo['image'];
							}
						}
					}
					if (!empty($image_src)) {
						$tpl->SetBlock('layout/featuredproducts/item/image');
						$tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . "/");
						//$tpl->SetVariable('base_url', JAWS_DPATH);
						$tpl->SetVariable('image_src', $image_src);
						$tpl->SetVariable('image_caption', $title);
						$tpl->SetVariable('image_href', $href);
						$tpl->ParseBlock('layout/featuredproducts/item/image');
					} else {
						$tpl->SetBlock('layout/featuredproducts/item/no_image');
						$tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . "/");
						//$tpl->SetVariable('base_url', JAWS_DPATH);
						$tpl->SetVariable('image_href', $href);
						$tpl->ParseBlock('layout/featuredproducts/item/no_image');
					}
					$tpl->ParseBlock('layout/featuredproducts/item');
				}
			}
		}
		$tpl->ParseBlock('layout/featuredproducts');

		// If this is embedded, we need to pass the javascript variable that has the IFRAME height to the session 
		$display_id = md5('Store'.'PremiumProductOfGroup'.$group);
		if ($embedded == true && !is_null($referer)) {	
			$tpl->SetBlock('layout/embedded');
			$tpl->SetVariable('id', $display_id);		        
			if (strtolower($referer) == strtolower($_SERVER['SERVER_NAME'])) {
				$tpl->SetVariable('referer', $GLOBALS['app']->GetJawsURL() . "/libraries/iframes/domain1/iframetest_resize1.html");		        
			} else {	
				$tpl->SetVariable('referer', "http://".$referer."/19/img/agents/19/custom_img/common_files/iframetest_resize1.html");		        
			}
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