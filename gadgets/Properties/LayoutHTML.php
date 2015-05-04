<?php
/**
 * Properties Gadget (layout actions in client side)
 *
 * @category   GadgetLayout
 * @package    Properties
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class PropertiesLayoutHTML
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
				'name' => _t('PROPERTIES_TITLE_PROPERTY_INDEX'), 
				'desc' => _t('PROPERTIES_DESCRIPTION_PROPERTY_INDEX')
			);
			$actions['PropertySearch'] = array(
				'mode' => 'LayoutAction', 
				'name' => _t('PROPERTIES_LAYOUT_SEARCH'), 
				'desc' => _t('PROPERTIES_LAYOUT_SEARCH_DESCRIPTION')
			);
			$actions['GlobalMap'] = array(
				'mode' => 'LayoutAction', 
				'name' => _t('PROPERTIES_LAYOUT_GLOBALMAP'), 
				'desc' => _t('PROPERTIES_LAYOUT_GLOBALMAP_DESCRIPTION')
			);
			$actions['CitiesMap'] = array(
				'mode' => 'LayoutAction', 
				'name' => _t('PROPERTIES_LAYOUT_CITIESMAP'), 
				'desc' => _t('PROPERTIES_LAYOUT_CITIESMAP_DESCRIPTION')
			);
			$actions['PropertyCalendar'] = array(
				'mode' => 'LayoutAction', 
				'name' => _t('PROPERTIES_LAYOUT_CALENDAR'), 
				'desc' => _t('PROPERTIES_LAYOUT_CALENDAR_DESCRIPTION')
			);
			$actions['ReservationForm'] = array(
				'mode' => 'LayoutAction', 
				'name' => _t('PROPERTIES_LAYOUT_FORM'), 
				'desc' => _t('PROPERTIES_LAYOUT_FORM_DESCRIPTION')
			);
			$actions['ShowFiveProperties'] = array(
				'mode' => 'LayoutAction', 
				'name' => _t('PROPERTIES_LAYOUT_FIVEPROPERTIES'), 
				'desc' => _t('PROPERTIES_LAYOUT_FIVEPROPERTIES_DESCRIPTION')
			);
			$actions['ShowPremiumProperty'] = array(
				'mode' => 'LayoutAction',
				'name' => _t('PROPERTIES_LAYOUT_PREMIUM'),
				'desc' => _t('PROPERTIES_LAYOUT_PREMIUM_DESCRIPTION')
			);
		}
		
        $model = $GLOBALS['app']->LoadGadget('Properties', 'Model');
		$pages = $model->GetPropertyParents($limit, 'propertyparentcreated', 'DESC', $offset);
        if (!Jaws_Error::isError($pages)) {
            foreach ($pages as $page) {
				$actions['CategoryMap(' . $page['propertyparentid'] . ')'] = array(
					'mode' => 'LayoutAction',
					'name' => 'Show map of properties in "'. $page['propertyparentcategory_name'].'"',
					'desc' => _t('PROPERTIES_LAYOUT_CATEGORYMAP_DESCRIPTION')
				);
				$actions['CategorySlideshow(' . $page['propertyparentid'] . ')'] = array(
					'mode' => 'LayoutAction',
					'name' => 'Show slideshow of properties in "'. $page['propertyparentcategory_name'].'"',
					'desc' => _t('PROPERTIES_LAYOUT_SLIDESHOW_DESCRIPTION')
				);
				$actions['CategoryShowOne(' . $page['propertyparentid'] . ')'] = array(
					'mode' => 'LayoutAction',
					'name' => 'Show one property from "'.$page['propertyparentcategory_name'].'"',
					'desc' => _t('PROPERTIES_LAYOUT_SHOWONE_DESCRIPTION')
				);
            }
        }
		
		if (is_null($offset) || $offset == 0) {
			for ($i=0;$i<11;$i++) {
				$parent = $model->GetRegion($i);
				if (!Jaws_Error::isError($parent) && isset($parent['id'])) {
					$actions['RegionsMap(' . $parent['id'] . ')'] = array(
						'mode' => 'LayoutAction', 
						'name' =>  _t('PROPERTIES_LAYOUT_REGIONSMAP'),
						'desc' => 'Show map of regions in '.$parent['region'] .' that contain properties'
					);
				}
			}
		}
		
		require_once JAWS_PATH . 'include/Jaws/User.php';
		$uModel = new Jaws_User;
		$groups = $uModel->GetAllGroups('name', null, $limit, $offset);

		if ($groups) {
			foreach ($groups as $group) {
				$groupName = (strpos($group['name'], '_') !== false ? ucfirst(str_replace('_', ' ', $group['name'])) : ucfirst($group['name']));
				$actions['ShowFivePropertiesOfGroup(' . $group['id'] . ')'] = array(
					'mode' => 'LayoutAction',
					'name' => "Five Properties of ". $groupName,
					'desc' => _t('PROPERTIES_LAYOUT_FIVEPROPERTIESOFGROUP_DESCRIPTION', $groupName)
				);
				$actions['ShowPremiumPropertyOfGroup(' . $group['id'] . ')'] = array(
					'mode' => 'LayoutAction',
					'name' => "Premium Properties of ". $groupName,
					'desc' => _t('PROPERTIES_LAYOUT_PREMIUM_DESCRIPTION', $groupName)
				);
			}
		}
        return $actions;
    }

	/**
     * Property search forms.
     *
     * @category 	feature
     * @access 	public
     * @return 	string
     */
    function PropertySearch($category = 'all', $embedded = false, $referer = null, $OwnerID = '')
    {
        $GLOBALS['app']->Layout->AddHeadLink('libraries/autocomplete/autocomplete.css', 'stylesheet', 'text/css', 'default');
        $GLOBALS['app']->Layout->AddHeadLink('gadgets/Properties/resources/style.css', 'stylesheet', 'text/css');
		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=Properties&amp;action=Ajax&amp;client=all&amp;stub=PropertiesAjax');
		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=Properties&amp;action=AjaxCommonFiles');
		$GLOBALS['app']->Layout->AddScriptLink('gadgets/Properties/resources/client_script.js');
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $model = $GLOBALS['app']->LoadGadget('Properties', 'Model');
		$request =& Jaws_Request::getInstance();
		$fetch = array('gadget', 'id', 'action');
		$get  = $request->get($fetch, 'get');
		$cid = 'all';
		if (!empty($get['id']) && $get['gadget'] == 'Properties' && $get['action'] == 'Category') {
			$cid = $get['id'];
		}
		$OwnerID = (!empty($OwnerID) ? (int)$OwnerID : null);
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
		$searchcommunity = $request->get('community', 'post');
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
		$searchamenities = $request->get('amenities', 'post');
		if (empty($searchamenities)) {
			$searchamenities = $request->get('amenities', 'get');
		}
		if (is_null($OwnerID)) {
			$searchownerid = $request->get('owner_id', 'post');
		} else {
			$searchownerid = $OwnerID;
		}
		if (empty($searchownerid)) {
			$searchownerid = $request->get('owner_id', 'get');
		}
		$tpl = new Jaws_Template('gadgets/Properties/templates/');
		$tpl->Load('normal.html');

		$tpl->SetBlock('layout');
		$tpl->SetVariable('actionName', 'PropertySearch_');
		$tpl->SetVariable('layout_title', '');

		$tpl->SetBlock('layout/search');
		
		$tpl->SetVariable('action', 'index.php?gadget=Properties&action=Category');
		//$tpl->SetVariable('base_url', JAWS_DPATH);
		$tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . "/");
		$tpl->SetVariable('site_url', $GLOBALS['app']->getSiteURL());
		$tpl->SetVariable('id', $cid);
		$tpl->SetVariable('permalink', $GLOBALS['app']->getSiteURL().'/index.php?gadget=Properties&action=Category&id='.$cid.'&status='.$searchstatus.'&sortColumn='.$sortColumn.'&sortDir='.$sortDir.'&bedroom='.$searchbedroom.'&bathroom='.$searchbathroom.'&keyword='.$searchkeyword.'&category='.$searchcategory.'&community='.$searchcommunity.'&amenities='.$searchamenities.'&owner_id='.$searchownerid);
		$onclick = '';
		/*
		if ($embedded === true) {
			$onclick = ' onclick="window.open(\''.$GLOBALS['app']->getSiteURL().'/index.php?gadget=Properties&action=Category&all_properties=\'+$(\'all_properties\').value+\'&owner_id=\'+$(\'owner_id\').value+\'&id=\'+$(\'id\').value+\'&startdate=\'+$(\'startdate\').value);"';
		}				
		*/
		$tpl->SetVariable('onclick', $onclick);
		
		$tpl->SetVariable('searchkeyword_value', 'Enter City, Zip Code, Keyword');
		
		$category_options = '';
		$parents = $model->GetPropertyParents();
		if (!Jaws_Error::IsError($parents)) {
			foreach($parents as $parent) {
				if ($parent['propertyparentactive'] == 'Y') {
					if ($category != 'all') {
						if ($parent['propertyparentid'] == (int)$category) {
							$category_options .= '<option VALUE="'.$parent['propertyparentid'].'"'.($cid != 'all' && ((int)$cid == $parent['propertyparentid'] || $cid == $parent['propertyparentfast_url']) ? ' SELECTED' : '').'>'.$parent['propertyparentcategory_name'].'</option>';
							break;
						}
					} else {
						$category_options .= '<option VALUE="'.$parent['propertyparentid'].'"'.($cid != 'all' && (int)$cid == $parent['propertyparentid'] ? ' SELECTED' : '').'>'.$parent['propertyparentcategory_name'].'</option>';
					}
				}
			}
		}
		$tpl->SetVariable('category_default', ($cid == 'all' ? ' SELECTED' : ''));
		$tpl->SetVariable('category_options', $category_options);
		
		// send attribute records
		$amenities = $model->GetAmenityTypes();
		$amenitiesHTML = '';
		
		if (!Jaws_Error::IsError($amenities)) {
			$lastType = 0;
			$loopCount = 0;
			$amenitiesHTML .= '<option value=""></option>';
			$amenitiesHTML .= '<option value="">Properties with:</option>';
			foreach($amenities as $amenity) {		            
				if ($amenity['active'] == 'Y' && $amenity['itype'] != 'TextBox' && $amenity['itype'] != 'TextArea' && $amenity['itype'] != 'HiddenField') {
					$types = $model->GetAmenitiesOfType((int)$amenity['id']);
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
							if ($type['typeid'] != $lastType) {
								$lastType = $type['typeid'];
								$amenitiesHTML .= '<option value="">----------------------</option>';
								$amenitiesHTML .= '<option value="">'.$amenity['title'].'</option>';
								//$amenitiesHTML .= "<tr><td style=\"border:1pt solid #CCCCCC; padding: 5px; background: #EEEEEE;\" colspan=\"2\" width=\"100%\"><b>".$amenity['title']."</b>&nbsp;&nbsp;&nbsp;[<span style='font-size: 0.8em'><i>".$amenity['itype']."</i></span>]</td></tr>";
							}
							$amenitiesHTML .= '<option onclick="javascript: if(document.getElementById(\'searchkeyword\').value==\'Enter City, Zip Code, Keyword\')document.getElementById(\'searchkeyword\').value=\'\'; location.href = \'index.php?gadget=Properties&action=Category&id=all&status=\' + document.getElementById(\'searchstatus\').value + \'&sortColumn=\' + document.getElementById(\'sortColumn\').value + \'&sortDir=\' + document.getElementById(\'sortDir\').value + \'&bedroom=\' + document.getElementById(\'searchbedroom\').value + \'&bathroom=\' + document.getElementById(\'searchbathroom\').value + \'&keyword=\' + document.getElementById(\'searchkeyword\').value + \'&amenities='.urlencode($type['feature']).'\';" value="searchamenity - '.$type['feature'].'">';
							$amenitiesHTML .= "&nbsp;&nbsp;&nbsp;".$type['feature']."</option>";
						}
					}
				}
			}			
		}
		$tpl->SetVariable('amenity_options', $amenitiesHTML);

		$tpl->SetVariable('status_default', ($searchstatus == '' || empty($searchstatus) ? ' SELECTED' : ''));
		$status_options = '<option value="">Show All</option>';
		$status_options .= '<option VALUE="forsale"'.($searchstatus == 'forsale' ? ' SELECTED' : '').'>For Sale</option>';
 		$status_options .= '<option VALUE="forrent"'.($searchstatus == 'forrent' ? ' SELECTED' : '').'>For Rent</option>';
  		$status_options .= '<option VALUE="forlease"'.($searchstatus == 'forlease' ? ' SELECTED' : '').'>For Lease</option>';
 		$status_options .= '<option VALUE="undercontract"'.($searchstatus == 'undercontract' ? ' SELECTED' : '').'>Under Contract</option>';
 		$status_options .= '<option VALUE="sold"'.($searchstatus == 'sold' ? ' SELECTED' : '').'>Sold</option>';
 		$status_options .= '<option VALUE="rented"'.($searchstatus == 'rented' ? ' SELECTED' : '').'>Rented</option>';
 		$status_options .= '<option VALUE="leased"'.($searchstatus == 'leased' ? ' SELECTED' : '').'>Leased</option>';
		$tpl->SetVariable('status_options', $status_options);
		
		$tpl->SetVariable('bedroom_default', ($searchbedroom == '' || empty($searchbedroom) ? ' SELECTED' : ''));
		$bedroom_options = '<option value="">Any</option>';
		$bedroom_options .= '<option VALUE="1"'.($searchbedroom == '1' ? ' SELECTED' : '').'>1</option>';
		$bedroom_options .= '<option VALUE="2"'.($searchbedroom == '2' ? ' SELECTED' : '').'>2</option>';
		$bedroom_options .= '<option VALUE="3"'.($searchbedroom == '3' ? ' SELECTED' : '').'>3</option>';
		$bedroom_options .= '<option VALUE="4"'.($searchbedroom == '4' ? ' SELECTED' : '').'>4</option>';
		$bedroom_options .= '<option VALUE="5"'.($searchbedroom == '5' ? ' SELECTED' : '').'>5</option>';
		$bedroom_options .= '<option VALUE="6"'.($searchbedroom == '6' ? ' SELECTED' : '').'>6</option>';
		$bedroom_options .= '<option VALUE="7"'.($searchbedroom == '7' ? ' SELECTED' : '').'>7</option>';
		$bedroom_options .= '<option VALUE="8"'.($searchbedroom == '8' ? ' SELECTED' : '').'>8</option>';
		$bedroom_options .= '<option VALUE="9"'.($searchbedroom == '9' ? ' SELECTED' : '').'>9</option>';
		$bedroom_options .= '<option VALUE="10"'.($searchbedroom == '10' ? ' SELECTED' : '').'>10</option>';
		$bedroom_options .= '<option VALUE="11"'.($searchbedroom == '11' ? ' SELECTED' : '').'>11</option>';
		$bedroom_options .= '<option VALUE="12"'.($searchbedroom == '12' ? ' SELECTED' : '').'>12</option>';
		$bedroom_options .= '<option VALUE="13"'.($searchbedroom == '13' ? ' SELECTED' : '').'>13</option>';
		$bedroom_options .= '<option VALUE="14"'.($searchbedroom == '14' ? ' SELECTED' : '').'>14</option>';
		$bedroom_options .= '<option VALUE="15"'.($searchbedroom == '15' ? ' SELECTED' : '').'>15</option>';
		$bedroom_options .= '<option VALUE="16"'.($searchbedroom == '16' ? ' SELECTED' : '').'>16</option>';
		$bedroom_options .= '<option VALUE="17"'.($searchbedroom == '17' ? ' SELECTED' : '').'>17</option>';
		$bedroom_options .= '<option VALUE="18"'.($searchbedroom == '18' ? ' SELECTED' : '').'>18</option>';
		$bedroom_options .= '<option VALUE="19"'.($searchbedroom == '19' ? ' SELECTED' : '').'>19</option>';
		$bedroom_options .= '<option VALUE="20"'.($searchbedroom == '20' ? ' SELECTED' : '').'>20</option>';
		$bedroom_options .= '<option VALUE="21"'.($searchbedroom == '21' ? ' SELECTED' : '').'>21</option>';
		$bedroom_options .= '<option VALUE="22"'.($searchbedroom == '22' ? ' SELECTED' : '').'>22</option>';
		$bedroom_options .= '<option VALUE="23"'.($searchbedroom == '23' ? ' SELECTED' : '').'>23</option>';
		$bedroom_options .= '<option VALUE="24"'.($searchbedroom == '24' ? ' SELECTED' : '').'>24</option>';
		$bedroom_options .= '<option VALUE="25"'.($searchbedroom == '25' ? ' SELECTED' : '').'>25</option>';
		$bedroom_options .= '<option VALUE="26"'.($searchbedroom == '26' ? ' SELECTED' : '').'>26</option>';
		$bedroom_options .= '<option VALUE="27"'.($searchbedroom == '27' ? ' SELECTED' : '').'>27</option>';
		$bedroom_options .= '<option VALUE="28"'.($searchbedroom == '28' ? ' SELECTED' : '').'>28</option>';
		$bedroom_options .= '<option VALUE="29"'.($searchbedroom == '29' ? ' SELECTED' : '').'>29</option>';
		$bedroom_options .= '<option VALUE="30"'.($searchbedroom == '30' ? ' SELECTED' : '').'>30</option>';
		$tpl->SetVariable('bedroom_options', $bedroom_options);

		$tpl->SetVariable('bathroom_default', ($searchbathroom == '' || empty($searchbathroom) ? ' SELECTED' : ''));
		$bathroom_options = '<option value="">Any</option>';
		$bathroom_options .= '<option VALUE="0.5"'.($searchbathroom == '0.5' ? ' SELECTED' : '').'>.5</option>';
		$bathroom_options .= '<option VALUE="1.0"'.($searchbathroom == '1.0' ? ' SELECTED' : '').'>1</option>';
		$bathroom_options .= '<option VALUE="1.5"'.($searchbathroom == '1.5' ? ' SELECTED' : '').'>1.5</option>';
		$bathroom_options .= '<option VALUE="2.0"'.($searchbathroom == '2.0' ? ' SELECTED' : '').'>2</option>';
		$bathroom_options .= '<option VALUE="2.5"'.($searchbathroom == '2.5' ? ' SELECTED' : '').'>2.5</option>';
		$bathroom_options .= '<option VALUE="3.0"'.($searchbathroom == '3.0' ? ' SELECTED' : '').'>3</option>';
		$bathroom_options .= '<option VALUE="3.5"'.($searchbathroom == '3.5' ? ' SELECTED' : '').'>3.5</option>';
		$bathroom_options .= '<option VALUE="4.0"'.($searchbathroom == '4.0' ? ' SELECTED' : '').'>4</option>';
		$bathroom_options .= '<option VALUE="4.5"'.($searchbathroom == '4.5' ? ' SELECTED' : '').'>4.5</option>';
		$bathroom_options .= '<option VALUE="5.0"'.($searchbathroom == '5.0' ? ' SELECTED' : '').'>5</option>';
		$bathroom_options .= '<option VALUE="5.5"'.($searchbathroom == '5.5' ? ' SELECTED' : '').'>5.5</option>';
		$bathroom_options .= '<option VALUE="6.0"'.($searchbathroom == '6.0' ? ' SELECTED' : '').'>6</option>';
		$bathroom_options .= '<option VALUE="6.5"'.($searchbathroom == '6.5' ? ' SELECTED' : '').'>6.5</option>';
		$bathroom_options .= '<option VALUE="7.0"'.($searchbathroom == '7.0' ? ' SELECTED' : '').'>7</option>';
		$bathroom_options .= '<option VALUE="7.5"'.($searchbathroom == '7.5' ? ' SELECTED' : '').'>7.5</option>';
		$bathroom_options .= '<option VALUE="8.0"'.($searchbathroom == '8.0' ? ' SELECTED' : '').'>8</option>';
		$bathroom_options .= '<option VALUE="8.5"'.($searchbathroom == '8.5' ? ' SELECTED' : '').'>8.5</option>';
		$bathroom_options .= '<option VALUE="9.0"'.($searchbathroom == '9.0' ? ' SELECTED' : '').'>9</option>';
		$bathroom_options .= '<option VALUE="9.5"'.($searchbathroom == '9.5' ? ' SELECTED' : '').'>9.5</option>';
		$bathroom_options .= '<option VALUE="10.0"'.($searchbathroom == '10.0' ? ' SELECTED' : '').'>10</option>';
		$bathroom_options .= '<option VALUE="10.5"'.($searchbathroom == '10.5' ? ' SELECTED' : '').'>10.5</option>';
		$bathroom_options .= '<option VALUE="11.0"'.($searchbathroom == '11.0' ? ' SELECTED' : '').'>11</option>';
		$bathroom_options .= '<option VALUE="11.5"'.($searchbathroom == '11.5' ? ' SELECTED' : '').'>11.5</option>';
		$bathroom_options .= '<option VALUE="12.0"'.($searchbathroom == '12.0' ? ' SELECTED' : '').'>12</option>';
		$bathroom_options .= '<option VALUE="12.5"'.($searchbathroom == '12.5' ? ' SELECTED' : '').'>12.5</option>';
		$bathroom_options .= '<option VALUE="13.0"'.($searchbathroom == '13.0' ? ' SELECTED' : '').'>13</option>';
		$bathroom_options .= '<option VALUE="13.5"'.($searchbathroom == '13.5' ? ' SELECTED' : '').'>13.5</option>';
		$bathroom_options .= '<option VALUE="14.0"'.($searchbathroom == '14.0' ? ' SELECTED' : '').'>14</option>';
		$bathroom_options .= '<option VALUE="14.5"'.($searchbathroom == '14.5' ? ' SELECTED' : '').'>14.5</option>';
		$bathroom_options .= '<option VALUE="15.0"'.($searchbathroom == '15.0' ? ' SELECTED' : '').'>15</option>';
		$bathroom_options .= '<option VALUE="15.5"'.($searchbathroom == '15.5' ? ' SELECTED' : '').'>15.5</option>';
		$bathroom_options .= '<option VALUE="16.0"'.($searchbathroom == '16.0' ? ' SELECTED' : '').'>16</option>';
		$bathroom_options .= '<option VALUE="16.5"'.($searchbathroom == '16.5' ? ' SELECTED' : '').'>16.5</option>';
		$bathroom_options .= '<option VALUE="17.0"'.($searchbathroom == '17.0' ? ' SELECTED' : '').'>17</option>';
		$bathroom_options .= '<option VALUE="17.5"'.($searchbathroom == '17.5' ? ' SELECTED' : '').'>17.5</option>';
		$bathroom_options .= '<option VALUE="18.0"'.($searchbathroom == '18.0' ? ' SELECTED' : '').'>18</option>';
		$bathroom_options .= '<option VALUE="18.5"'.($searchbathroom == '18.5' ? ' SELECTED' : '').'>18.5</option>';
		$bathroom_options .= '<option VALUE="19.0"'.($searchbathroom == '19.0' ? ' SELECTED' : '').'>19</option>';
		$bathroom_options .= '<option VALUE="19.5"'.($searchbathroom == '19.5' ? ' SELECTED' : '').'>19.5</option>';
		$bathroom_options .= '<option VALUE="20.0"'.($searchbathroom == '20.0' ? ' SELECTED' : '').'>20</option>';
		$bathroom_options .= '<option VALUE="20.5"'.($searchbathroom == '20.5' ? ' SELECTED' : '').'>20.5</option>';
		$tpl->SetVariable('bathroom_options', $bathroom_options);

		$tpl->SetVariable('sort_default', ($sortColumn == '' || empty($searchstatus) || $sortColumn == 'sort_order' ? ' SELECTED' : ''));
		$sort_options = '<option value="">Featured Properties</option>';
		$sort_options .= '<option VALUE="title"'.($sortColumn == 'title' ? ' SELECTED' : '').'>Property Name</option>';
		$sort_options .= '<option VALUE="price"'.($sortColumn == 'price' ? ' SELECTED' : '').'>Price</option>';
		$sort_options .= '<option VALUE="Created"'.($sortColumn == 'Created' ? ' SELECTED' : '').'>Date Added</option>';
		$sort_options .= '<option VALUE="community"'.($sortColumn == 'community' ? ' SELECTED' : '').'>Community</option>';
		$tpl->SetVariable('sort_options', $sort_options);

		$tpl->SetVariable('sort_asc', ($sortDir == '' || empty($sortDir) || $sortDir == 'ASC' ? ' SELECTED' : ''));
		$tpl->SetVariable('sort_desc', ($sortDir == 'DESC' ? ' SELECTED' : ''));
		$tpl->SetVariable('owner_id', $searchownerid);
		
		$tpl->SetVariable('id_autocomplete', 'null');
		
		$tpl->ParseBlock('layout/search');
		
		// If this is embedded, we need to pass the javascript variable that has the IFRAME height to the session 
		$display_id = md5('Properties'.(!empty($cid) && is_numeric($cid) ? (int)$cid : 'all'));
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
     * Property reservation forms.
     *
     * @category 	feature
     * @access 	public
     * @return 	string
     */
    function ReservationForm($cid = null, $embedded = false, $referer = null, $searchownerid = '')
    {
        $GLOBALS['app']->Layout->AddHeadLink('gadgets/Properties/resources/style.css', 'stylesheet', 'text/css');
        $GLOBALS['app']->Layout->AddHeadLink('libraries/piwi/piwidata/css/calendar-blue.css', 'stylesheet', 'text/css');
		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=Properties&amp;action=Ajax&amp;client=all&amp;stub=PropertiesAjax');
		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=Properties&amp;action=AjaxCommonFiles');
		$GLOBALS['app']->Layout->AddScriptLink('gadgets/Properties/resources/client_script.js');
		$GLOBALS['app']->Layout->AddScriptLink('libraries/piwi/piwidata/js/jscalendar/calendar.js');
		$GLOBALS['app']->Layout->AddScriptLink('libraries/piwi/piwidata/js/jscalendar/calendar-setup.js');
		$GLOBALS['app']->Layout->AddScriptLink('libraries/piwi/piwidata/js/jscalendar/lang/calendar-en.js');

		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $model = $GLOBALS['app']->LoadGadget('Properties', 'Model');
		if (is_null($cid)) {
			$cid = (isset($get['id']) ? (int)$get['id'] : '');
		}
		$tpl = new Jaws_Template('gadgets/Properties/templates/');
		$tpl->Load('normal.html');

		$tpl->SetBlock('layout');
		$tpl->SetVariable('actionName', 'ReservationForm_');
		$tpl->SetVariable('layout_title', '');

		$tpl->SetBlock('layout/reservationform');
		
		$tpl->SetVariable('action', 'index.php?gadget=Properties&action=Category');
		$tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . "/");
		//$tpl->SetVariable('base_url', JAWS_DPATH);
		$tpl->SetVariable('site_url', $GLOBALS['app']->getSiteURL());
		$onclick = '';
		if ($embedded === true) {
			$onclick = ' onclick="window.open(\''.$GLOBALS['app']->getSiteURL().'/index.php?gadget=Properties&action=Category&all_properties=\'+$(\'all_properties\').value+\'&owner_id=\'+$(\'owner_id\').value+\'&id=\'+$(\'id\').value+\'&startdate=\'+$(\'startdate\').value);"';
		}				
		$tpl->SetVariable('onclick', $onclick);
		$category_options = '';
		$date = date("m/d/Y", time());
		
	  $category_options .= '<p>Arrival Date:&nbsp;<input type="text" NAME="startdate" ID="startdate" SIZE="10" VALUE="'.$date.'" maxlength="10">
		&nbsp;&nbsp;<button type="button" name="start_button" id="start_button">
		<img id="start_button_stockimage" src="'. $GLOBALS['app']->GetJawsURL() . '/libraries/piwi/piwidata/art/stock/apps/office-calendar.png" border="0" alt="" height="16" width="16" />
		</button>
		<input NAME="all_properties" ID="all_properties" type="hidden" VALUE="true">
		<input NAME="owner_id" ID="owner_id" type="hidden" VALUE="'.$searchownerid.'">
		<input NAME="id" ID="id" type="hidden" VALUE="'.$cid.'">
		</p>
		<script type="text/javascript">
		 Calendar.setup({
		  inputField: "startdate",
		  ifFormat: "%m/%d/%Y",
		  button: "start_button",
		  singleClick: true,
		  weekNumbers: false,
		  firstDay: 0,
		  date: "",
		  showsTime: false,
		  multiple: false});
		</script>
        ';
		/*
		$category_options .= '<b>Time: </b>&nbsp;
		<select size="1" name="iTimeHr">
			  <option value="12" selected>12</option>
			  <option value="1" >1</option>
			  <option value="2" >2</option>
			  <option value="3" >3</option>
			  <option value="4" >4</option>

			  <option value="5" >5</option>
			  <option value="6" >6</option>
			  <option value="7" >7</option>
			  <option value="8" >8</option>
			  <option value="9" >9</option>
			  <option value="10" >10</option>

			  <option value="11" >11</option>
		  </select>
		  <select size="1" name="iTimeMin">
			  <option value="00" selected>00</option>
			  <option value="01" >01</option>
			  <option value="02" >02</option>
			  <option value="03" >03</option>

			  <option value="04" >04</option>
			  <option value="05" >05</option>
			  <option value="06" >06</option>
			  <option value="07" >07</option>
			  <option value="08" >08</option>
			  <option value="09" >09</option>

			  <option value="10" >10</option>
			  <option value="11" >11</option>
			  <option value="12" >12</option>
			  <option value="13" >13</option>
			  <option value="14" >14</option>
			  <option value="15" >15</option>

			  <option value="16" >16</option>
			  <option value="17" >17</option>
			  <option value="18" >18</option>
			  <option value="19" >19</option>
			  <option value="20" >20</option>
			  <option value="21" >21</option>

			  <option value="22" >22</option>
			  <option value="23" >23</option>
			  <option value="24" >24</option>
			  <option value="25" >25</option>
			  <option value="26" >26</option>
			  <option value="27" >27</option>

			  <option value="28" >28</option>
			  <option value="29" >29</option>
			  <option value="30" >30</option>
			  <option value="31" >31</option>
			  <option value="32" >32</option>
			  <option value="33" >33</option>

			  <option value="34" >34</option>
			  <option value="35" >35</option>
			  <option value="36" >36</option>
			  <option value="37" >37</option>
			  <option value="38" >38</option>
			  <option value="39" >39</option>

			  <option value="40" >40</option>
			  <option value="41" >41</option>
			  <option value="42" >42</option>
			  <option value="43" >43</option>
			  <option value="44" >44</option>
			  <option value="45">45</option>

			  <option value="46" >46</option>
			  <option value="47" >47</option>
			  <option value="48" >48</option>
			  <option value="49" >49</option>
			  <option value="50" >50</option>
			  <option value="51" >51</option>

			  <option value="52" >52</option>
			  <option value="53" >53</option>
			  <option value="54" >54</option>
			  <option value="55" >55</option>
			  <option value="56" >56</option>
			  <option value="57" >57</option>

			  <option value="58" >58</option>
			  <option value="59" >59</option>
		  </select>
		  <select size="1" name="iTimeSuffix">
			  <option value="AM" >AM</option>
			  <option value="PM" selected>PM</option>
		  </select>';
		*/
		$tpl->SetVariable('content', $category_options);
		
		$tpl->ParseBlock('layout/reservationform');
			
		// If this is embedded, we need to pass the javascript variable that has the IFRAME height to the session 
		$display_id = md5('Properties'.(!empty($cid) && is_numeric($cid) ? (int)$cid : 'all'));
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
     * Display a map of all Properties.
     *
     * @param 	int 	$cid 	Property category (optional)
     * @param 	boolean 	$embedded 	Embedded mode
     * @param 	string 	$referer 	Embedding referer
     * @param 	string 	$searchamenities 	Comma separated list of amenities to match against
     * @param 	string 	$searchownerid 	OwnerID to match against
     * @access 	public
     * @return 	string
     */
    function GlobalMap($cid = null, $embedded = false, $referer = null, $searchamenities = '', $searchownerid = '')
    {
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $model = $GLOBALS['app']->LoadGadget('Properties', 'Model');
		$request =& Jaws_Request::getInstance();
		$fetch = array('gadget', 'id', 'action');
		$get  = $request->get($fetch, 'get');
		if (is_null($cid)) {
			$cid = (isset($get['id']) ? (int)$get['id'] : 'all');
		}

		$GLOBALS['app']->Registry->LoadFile('Maps');
		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=Properties&action=hideGoogleAPIAlerts');			
		$GLOBALS['app']->Layout->AddScriptLink('https://maps.googleapis.com/maps/api/js?v=3&sensor=false&key='.$GLOBALS['app']->Registry->Get('/gadgets/Maps/googlemaps_key'));			
		//$GLOBALS['app']->Layout->AddScriptLink('libraries/js/progressbarcontrol.js');			
		//$GLOBALS['app']->Layout->AddScriptLink('http://maps.google.com/maps?file=api&amp;v=2&amp;key=ABQIAAAAbHKtlYQg6w5AJ9d2_shprxRSdpoJbNiUEES6uLAQrtqeLOB2WBROsVk6Deve8XT-33SEgoVpReKw5Q');			
		$tpl = new Jaws_Template('gadgets/Properties/templates/');
		$tpl->Load('normal.html');

		$tpl->SetBlock('layout');
		$tpl->SetVariable('actionName', 'GlobalMap_');
		$tpl->SetVariable('layout_title', '');
		$tpl->SetVariable('link', '');

		$tpl->SetBlock('layout/map');
		
		$parentid = 'GlobalMap';
		//$tpl->SetVariable('base_url', JAWS_DPATH);
		$tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . "/");
		$tpl->SetVariable('id', $parentid);
		
		$xml_url = 'index.php?gadget=Properties&action=RegionsMapXML&pid=0&id='.$cid.'&amenities='.$searchamenities.'&owner_id='.$searchownerid;
		$tpl->SetVariable('properties_xml_url', $xml_url);
		// build dimensions
		$map_height = "300";
		if ($get['gadget'] == 'CustomPage' && JAWS_SCRIPT == "admin") {
			$map_width = "450";
			$map_dimensions .= "<style>\n";
			$map_dimensions .= " 	#layout-properties-body {\n";
			$map_dimensions .= " 		width: 450px;\n";	
			$map_dimensions .= "	}\n";	
			$map_dimensions .= "</style>\n";	
			$map_dimensions .= "<script type=\"text/javascript\">\n";
			$map_dimensions .= " 	$('properties-".$parentid."').style.width = '450px';\n";	
		} else {	
			$map_width = "750";
			$map_dimensions .= "<script type=\"text/javascript\">\n";
			$map_dimensions .= "if ($('properties-".$parentid."').parentNode) {\n";
			$map_dimensions .= " 	$('properties-".$parentid."').style.width = parseInt($('properties-".$parentid."').parentNode.offsetWidth) + 'px';\n";	
			$map_dimensions .= "} else {\n";	
			$map_dimensions .= " 	$('properties-".$parentid."').style.width = '750px';\n";	
			$map_dimensions .= "}\n";	
			/*
			$map_dimensions .= "Event.observe( window, 'load', function() {\n";
			$map_dimensions .= '	if(!$(\'properties-'.$parentid.'\').childNodes[1]){$(\'properties-'.$parentid.'\').style.display = \'none\';};'."\n";	
			$map_dimensions .= "} );\n";
			*/
		}
		$map_dimensions .= "</script>\n";	
		$tpl->SetVariable('properties_height', $map_height);
		$tpl->SetVariable('properties_width', $map_width);
		$tpl->SetVariable('properties_dimensions', $map_dimensions);
		$tpl->SetVariable('map_type','TERRAIN');
		
		//$submit_vars[SYNTACTS_DB ."0:$j:0"] = $xss->filter($value);
				
		$tpl->ParseBlock('layout/map');

		// If this is embedded, we need to pass the javascript variable that has the IFRAME height to the session 
		$display_id = md5('Properties'.(!empty($cid) && is_numeric($cid) ? (int)$cid : 'all'));
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
     * Display a map of all regions within given country containing Properties.
     *
     * @param 	int 	$pid 	Country ID
     * @access 	public
     * @return 	string
     */
    function RegionsMap($pid = 0)
    {
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $model = $GLOBALS['app']->LoadGadget('Properties', 'Model');
		$request =& Jaws_Request::getInstance();
		$fetch = array('gadget', 'id', 'action');
		$get  = $request->get($fetch, 'get');

		$GLOBALS['app']->Registry->LoadFile('Maps');
		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=Properties&action=hideGoogleAPIAlerts');			
		$GLOBALS['app']->Layout->AddScriptLink('https://maps.googleapis.com/maps/api/js?v=3&sensor=false&key='.$GLOBALS['app']->Registry->Get('/gadgets/Maps/googlemaps_key'));			
		//$GLOBALS['app']->Layout->AddScriptLink('libraries/js/progressbarcontrol.js');			
		//$GLOBALS['app']->Layout->AddScriptLink('http://maps.google.com/maps?file=api&amp;v=2&amp;key=ABQIAAAAbHKtlYQg6w5AJ9d2_shprxRSdpoJbNiUEES6uLAQrtqeLOB2WBROsVk6Deve8XT-33SEgoVpReKw5Q');			
		$tpl = new Jaws_Template('gadgets/Properties/templates/');
		$tpl->Load('normal.html');

		$tpl->SetBlock('layout');
		$tpl->SetVariable('actionName', 'RegionsMap_');
		$tpl->SetVariable('layout_title', '');
		$tpl->SetVariable('link', '');

		$tpl->SetBlock('layout/map');
		
		$parentid = 'RegionsMap';
		$tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . "/");
		//$tpl->SetVariable('base_url', JAWS_DPATH);
		$tpl->SetVariable('id', $parentid);
		
		$xml_url = 'index.php?gadget=Properties&action=RegionsMapXML&pid='.$pid;
		$tpl->SetVariable('properties_xml_url', $xml_url);
		// build dimensions
		$map_height = "300";
		if ($get['gadget'] == 'CustomPage' && JAWS_SCRIPT == "admin") {
			$map_width = "450";
			$map_dimensions .= "<style>\n";
			$map_dimensions .= " 	#layout-properties-body {\n";
			$map_dimensions .= " 		width: 450px;\n";	
			$map_dimensions .= "	}\n";	
			$map_dimensions .= "</style>\n";	
			$map_dimensions .= "<script type=\"text/javascript\">\n";
			$map_dimensions .= " 	$('properties-".$parentid."').style.width = '450px';\n";	
		} else {	
			$map_width = "750";
			$map_dimensions .= "<script type=\"text/javascript\">\n";
			$map_dimensions .= "if ($('properties-".$parentid."').parentNode) {\n";
			$map_dimensions .= " 	$('properties-".$parentid."').style.width = parseInt($('properties-".$parentid."').parentNode.offsetWidth) + 'px';\n";	
			$map_dimensions .= "} else {\n";	
			$map_dimensions .= " 	$('properties-".$parentid."').style.width = '750px';\n";	
			$map_dimensions .= "}\n";	
			/*
			$map_dimensions .= "Event.observe( window, 'load', function() {\n";
			$map_dimensions .= '	if(!$(\'properties-'.$parentid.'\').childNodes[1]){$(\'properties-'.$parentid.'\').style.display = \'none\';};'."\n";	
			$map_dimensions .= "} );\n";
			*/
		}
		$map_dimensions .= "</script>\n";	
		$tpl->SetVariable('properties_height', $map_height);
		$tpl->SetVariable('properties_width', $map_width);
		$tpl->SetVariable('properties_dimensions', $map_dimensions);
		//$submit_vars[SYNTACTS_DB ."0:$j:0"] = $xss->filter($value);
		$tpl->SetVariable('map_type','TERRAIN');
				
		$tpl->ParseBlock('layout/map');

		$tpl->ParseBlock('layout');

		return $tpl->Get();
		
    }

	/**
     * Display a map of all cities that contain Properties.
     *
     * @category 	feature
     * @param 	int 	$cid 	Property category (optional)
     * @param 	boolean 	$embedded 	Embedded mode
     * @param 	string 	$referer 	Embedding referer
     * @param 	string 	$searchamenities 	Comma separated list of amenities to match against
     * @param 	string 	$searchownerid 	OwnerID to match against
     * @access 	public
     * @return 	string
     */
    function CitiesMap($cid = null, $embedded = false, $referer = null, $searchamenities = '', $searchownerid = '')
    {
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $model = $GLOBALS['app']->LoadGadget('Properties', 'Model');
		$request =& Jaws_Request::getInstance();
		$fetch = array('gadget', 'id', 'action');
		$get  = $request->get($fetch, 'get');
		if (is_null($cid)) {
			$cid = (isset($get['id']) ? (int)$get['id'] : 'all');
		}

		$GLOBALS['app']->Registry->LoadFile('Maps');
		//$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=Properties&action=hideGoogleAPIAlerts');			
		$GLOBALS['app']->Layout->AddScriptLink('https://maps.googleapis.com/maps/api/js?v=3&sensor=false&key='.$GLOBALS['app']->Registry->Get('/gadgets/Maps/googlemaps_key'));			
		//$GLOBALS['app']->Layout->AddScriptLink('libraries/js/progressbarcontrol.js');			
		//$GLOBALS['app']->Layout->AddScriptLink('http://maps.google.com/maps?file=api&amp;v=2&amp;key=ABQIAAAAbHKtlYQg6w5AJ9d2_shprxRSdpoJbNiUEES6uLAQrtqeLOB2WBROsVk6Deve8XT-33SEgoVpReKw5Q');			
		$tpl = new Jaws_Template('gadgets/Properties/templates/');
		$tpl->Load('normal.html');

		$tpl->SetBlock('layout');
		$tpl->SetVariable('actionName', 'CitiesMap_');
		$tpl->SetVariable('layout_title', '');
		$tpl->SetVariable('link', '');

		$tpl->SetBlock('layout/map');
		
		$parentid = 'CitiesMap';
		$tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . "/");
		//$tpl->SetVariable('base_url', JAWS_DPATH);
		$tpl->SetVariable('id', $parentid);
		
		$xml_url = 'index.php?gadget=Properties&action=CitiesMapXML&id='.$cid.'&amenities='.$searchamenities.'&owner_id='.$searchownerid;
		$tpl->SetVariable('properties_xml_url', $xml_url);
		// build dimensions
		$map_height = "300";
		$map_dimensions = '';
		if ($get['gadget'] == 'CustomPage' && JAWS_SCRIPT == "admin") {
			$map_width = "450";
			$map_dimensions .= "<style>\n";
			$map_dimensions .= " 	#layout-properties-body {\n";
			$map_dimensions .= " 		width: 450px;\n";	
			$map_dimensions .= "	}\n";	
			$map_dimensions .= "</style>\n";	
			$map_dimensions .= "<script type=\"text/javascript\">\n";
			$map_dimensions .= " 	$('properties-".$parentid."').style.width = '450px';\n";	
		} else {	
			$map_width = "750";
			$map_dimensions .= "<script type=\"text/javascript\">\n";
			$map_dimensions .= "if ($('properties-".$parentid."').parentNode) {\n";
			$map_dimensions .= " 	$('properties-".$parentid."').style.width = parseInt($('properties-".$parentid."').parentNode.offsetWidth) + 'px';\n";	
			$map_dimensions .= "} else {\n";	
			$map_dimensions .= " 	$('properties-".$parentid."').style.width = '750px';\n";	
			$map_dimensions .= "}\n";	
			/*
			$map_dimensions .= "Event.observe( window, 'load', function() {\n";
			$map_dimensions .= '	if(!$(\'properties-'.$parentid.'\').childNodes[1]){$(\'properties-'.$parentid.'\').style.display = \'none\';};'."\n";	
			$map_dimensions .= "} );\n";	
			*/
		}
		$map_dimensions .= "</script>\n";	
		$tpl->SetVariable('properties_height', $map_height);
		$tpl->SetVariable('properties_width', $map_width);
		$tpl->SetVariable('properties_dimensions', $map_dimensions);
		//$submit_vars[SYNTACTS_DB ."0:$j:0"] = $xss->filter($value);
		$tpl->SetVariable('map_type','TERRAIN');
				
		$tpl->ParseBlock('layout/map');

		// If this is embedded, we need to pass the javascript variable that has the IFRAME height to the session 
		$display_id = md5('Properties'.(!empty($cid) && is_numeric($cid) ? (int)$cid : 'all'));
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
     * Display a map of all Properties in specific categories.
     *
     * @category 	feature
     * @param 	int 	$cid 	Property category (optional)
     * @param 	string 	$searchamenities 	Comma separated list of amenities to match against
     * @param 	string 	$searchownerid 	OwnerID to match against
     * @param 	boolean 	$embedded 	Embedded mode
     * @param 	string 	$referer 	Embedding referer
     * @access 	public
     * @return 	string
     */
    function CategoryMap($cid = null, $searchamenities = '', $searchownerid = '', $embedded = false, $referer = null)
    {
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $model = $GLOBALS['app']->LoadGadget('Properties', 'Model');
		$request =& Jaws_Request::getInstance();
		$fetch = array('gadget', 'id', 'action');
		$get  = $request->get($fetch, 'get');
		if (is_null($cid)) {
			$cid = (isset($get['id']) ? (int)$get['id'] : 'all');
		}
		$parentid = $cid;
		$parenttitle = "Property Map";
		if (strtolower($get['gadget']) == 'properties' && (strtolower($get['action']) == 'property' || strtolower($get['action']) == 'printpropertydetails') && $cid != 'all') {
			$mapType = "Property";
			$parents = $model->GetProperty($cid);
			if (!Jaws_Error::IsError($parents) && isset($parents['id'])) {
				$parentid = $parents['id'];
				$xml_url = 'index.php?gadget=Properties&action=PropertyMapXML&id='.$parentid;
				$parenttitle = $parents['title'];
			}
			/*
			if (isset($parents['address']) || (isset($parents['city']) && isset($parents['region']))) {
				$map_dimensions = '<script type="text/javascript">$(\'properties-'.$parentid.'\').style.display = \'none\';</script>'."\n";	
			}
			*/
		} else {
			$mapType = "Category";
			$map_dimensions = '';	
			$parentid = $cid;
			$parenttitle = "All Properties";
			if ($cid != 'all') {
				$parents = $model->GetPropertyParent($cid);
				if (!Jaws_Error::IsError($parents) && isset($parents['propertyparentid'])) {
					$parentid = $parents['propertyparentid'];
					$parenttitle = $parents['propertyparentcategory_name'];
				}
			}
			if (isset($parents['propertyparentid']) || $cid == "all" || !empty($searchamenities) || !empty($searchownerid)) {
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
				$searchcommunity = $request->get('community', 'post');
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
				if (empty($searchownerid)) {
					$searchownerid = $request->get('owner_id', 'post');
				}
				if (empty($searchownerid)) {
					$searchownerid = $request->get('owner_id', 'get');
				}
				$searchcountryid = $request->get('country_id', 'post');
				if (empty($searchcountryid)) {
					$searchcountryid = $request->get('country_id', 'get');
				}
				
				$xml_url = 'index.php?gadget=Properties&action=CategoryMapXML&id='.$parentid.'&status='.$searchstatus.'&sortColumn='.$sortColumn.'&sortDir='.$sortDir.'&bedroom='.$searchbedroom.'&bathroom='.$searchbathroom.'&keyword='.$searchkeyword.'&category='.$searchcategory.'&community='.$searchcommunity.'&amenities='.$searchamenities.'&owner_id='.$searchownerid.'&country_id='.$searchcountryid.'&start='.$start;
			}
		}
		
		$GLOBALS['app']->Registry->LoadFile('Maps');
		//$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=Properties&action=hideGoogleAPIAlerts');			
		$GLOBALS['app']->Layout->AddScriptLink('https://maps.googleapis.com/maps/api/js?v=3&sensor=false&key='.$GLOBALS['app']->Registry->Get('/gadgets/Maps/googlemaps_key'));			
		//$GLOBALS['app']->Layout->AddScriptLink('libraries/js/progressbarcontrol.js');			
		//$GLOBALS['app']->Layout->AddScriptLink('http://maps.google.com/maps?file=api&amp;v=2&amp;key=ABQIAAAAbHKtlYQg6w5AJ9d2_shprxRSdpoJbNiUEES6uLAQrtqeLOB2WBROsVk6Deve8XT-33SEgoVpReKw5Q');			
		$tpl = new Jaws_Template('gadgets/Properties/templates/');
		$tpl->Load('normal.html');

		$tpl->SetBlock('layout');
		$tpl->SetVariable('actionName', $mapType.'Map_' . $parentid . '_');
		$tpl->SetVariable('layout_title', $xss->filter($parenttitle));
		$tpl->SetVariable('link', $GLOBALS['app']->Map->GetURLFor('Properties', 'Category', array('id' => (isset($parents['propertyparentfast_url']) ? $parents['propertyparentfast_url'] : 'all'))));

		$tpl->SetBlock('layout/map');
		
		$tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . "/");
		//$tpl->SetVariable('base_url', JAWS_DPATH);
		$tpl->SetVariable('id', $parentid);
		
		$tpl->SetVariable('properties_xml_url', $xml_url);
		// build dimensions
		$map_height = "300";
		if ($get['gadget'] == 'CustomPage' && JAWS_SCRIPT == "admin") {
			$map_width = "450";
			$map_dimensions .= "<style>\n";
			$map_dimensions .= " 	#layout-properties-body {\n";
			$map_dimensions .= " 		width: 450px;\n";	
			$map_dimensions .= "	}\n";	
			$map_dimensions .= "</style>\n";	
			$map_dimensions .= "<script type=\"text/javascript\">\n";
			$map_dimensions .= " 	$('properties-".$parentid."').style.width = '450px';\n";	
		} else {	
			$map_width = "750";
			$map_dimensions .= "<script type=\"text/javascript\">\n";
			if ($mapType == "Property") {
				//$map_dimensions .= "$('properties-".$parentid."').style.width = '750px';\n";	
			} else {
				if (JAWS_SCRIPT == "admin") {
					$map_dimensions .= " 	$('properties-".$parentid."').style.width = '780px';\n";	
				} else {
					$map_dimensions .= "if ($('properties-".$parentid."').parentNode) {\n";
					$map_dimensions .= " 	$('properties-".$parentid."').style.width = parseInt($('properties-".$parentid."').parentNode.offsetWidth) + 'px';\n";	
					$map_dimensions .= "} else {\n";	
					$map_dimensions .= " 	$('properties-".$parentid."').style.width = '750px';\n";	
					$map_dimensions .= "}\n";	
				}
			}
			/*
			$map_dimensions .= "Event.observe( window, 'load', function() {\n";
			$map_dimensions .= '	if(!$(\'properties-'.$parentid.'\').childNodes[1]){$(\'properties-'.$parentid.'\').style.display = \'none\';};'."\n";	
			$map_dimensions .= "} );\n";		
			*/
		}
		$map_dimensions .= "</script>\n";	
		$tpl->SetVariable('properties_height', $map_height);
		$tpl->SetVariable('properties_width', $map_width);
		$tpl->SetVariable('properties_dimensions', $map_dimensions);
		//$submit_vars[SYNTACTS_DB ."0:$j:0"] = $xss->filter($value);
		$tpl->SetVariable('map_type','ROADMAP');
				
		$tpl->ParseBlock('layout/map');

		// If this is embedded, we need to pass the javascript variable that has the IFRAME height to the session 
		$display_id = md5('Properties'.(!empty($cid) && is_numeric($cid) ? (int)$cid : 'all'));
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
     * Display a slideshow of Property images.
     *
     * @category 	feature
     * @param 	int 	$cid 	Property category (optional)
     * @param 	boolean 	$embedded 	Embedded mode
     * @param 	string 	$referer 	Embedding referer
     * @param 	string 	$searchownerid 	OwnerID to match against
     * @access 	public
     * @return 	string
     */
    function CategorySlideshow($cid = null, $embedded = false, $referer = null, $searchownerid = '')
    {
		require_once JAWS_PATH . 'include/Jaws/Image.php';
		//$GLOBALS['app']->Layout->AddScriptLink('libraries/js/swfobject.js');			

		$GLOBALS['app']->Layout->AddScriptLink('https://ajax.googleapis.com/ajax/libs/scriptaculous/1.8/effects.js');
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $model = $GLOBALS['app']->LoadGadget('Properties', 'Model');
		// send Properties records
		$request =& Jaws_Request::getInstance();
		$fetch = array('gadget', 'id');
		$get  = $request->get($fetch, 'get');
		if (is_null($cid)) {
			$cid = (isset($get['id']) ? (int)$get['id'] : 'all');
		}
		
		if (empty($searchownerid) && strtolower($get['gadget']) == 'users') {
			$searchownerid = $get['id'];
		}
		$searchownerid = (!empty($searchownerid) ? (int)$searchownerid : null);
		
		if ($cid != 'all') {
			if (!empty($searchownerid)) {
				$parents = $model->GetSinglePropertyParentByUserID($searchownerid, $cid);
			} else {
				$parents = $model->GetPropertyParent($cid);
			}
		}
		
		if ((!Jaws_Error::IsError($parents) && isset($parents['propertyparentid']) && !empty($parents['propertyparentid'])) || $cid == 'all') {
			$good_ext = array('jpg', 'jpeg', 'swf', 'gif', 'png', 'tif', 'bmp');
			$wm_ext = array('jpg', 'jpeg', 'gif', 'png');
			require_once JAWS_PATH . 'include/Jaws/Template.php';
			$tpl = new Jaws_Template('gadgets/Properties/templates/');
	        $tpl->Load('normal.html');

	        $tpl->SetBlock('layout');
			if (isset($parents['propertyparentid']) && !empty($parents['propertyparentid']) && isset($parents['propertyparentcategory_name']) && !empty($parents['propertyparentcategory_name'])) {
				$parentid = $parents['propertyparentid'];
				$parentTitle = $parents['propertyparentcategory_name'];
				$parentFastUrl = $parents['propertyparentfast_url'];
			} else {
				$parentid = 'all';
				$parentTitle = 'All Properties';
				$parentFastUrl = $parentid;
			}
			$tpl->SetVariable('actionName', 'CategorySlideshow_' . $parentid . '_');
			$tpl->SetVariable('layout_title', $parentTitle);
	        $tpl->SetBlock('layout/slideshow');
			// set "slideshow" swfobject variables
			$tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . "/");
			//$tpl->SetVariable('base_url', JAWS_DPATH);
			$tpl->SetVariable('id', $cid);
			$tpl->SetVariable('slideshow_loading_image', '');
			$tpl->SetVariable('slideshow_background_color', ' ');
			// build dimensions
			$slideshow_dimensions = '';
			/*
			$slideshow_dimensions .= "if ($('properties-slideshow-".$cid."').parentNode) {\n";
			$slideshow_dimensions .= " 	$('properties-slideshow-".$cid."').style.width = parseInt($('properties-slideshow-".$cid."').parentNode.offsetWidth) + 'px';\n";	
			$slideshow_dimensions .= "}\n";	
			if ($parents['height'] != 'auto' && !empty($parents['custom_height'])) {
				$slideshow_dimensions .= "$('properties-slideshow-".$cid."').style.height = '".(int)$parents['custom_height']."px';\n";	
			} else {
				$slideshow_dimensions .= " 	$('properties-slideshow-".$cid."').style.height = parseInt(($('properties-slideshow-".$cid."').offsetWidth".$cid.")*(.75)) + 'px';\n";
			}
			*/
			$slideshow_dimensions .= "if ($('properties-slideshow-".$cid."').parentNode) {\n";
			$slideshow_dimensions .= " 	$('properties-slideshow-".$cid."').parentNode.style.display = 'block';\n";	
			$slideshow_dimensions .= "	$('properties-slideshow-".$cid."').parentNode.style.width = slideshow".$cid."_width + 'px';\n";
			$slideshow_dimensions .= "	$('properties-slideshow-".$cid."').parentNode.style.height = slideshow".$cid."_height + 'px';\n";
			$slideshow_dimensions .= "}\n";	
			$slideshow_dimensions .= "$('properties-slideshow_overlay".$cid."').style.width = slideshow".$cid."_width + 'px';\n";
			$slideshow_dimensions .= "$('properties-slideshow_overlay".$cid."').style.height = slideshow".$cid."_height + 'px';\n";
			$slideshow_dimensions .= "$('properties-slideshow-".$cid."').style.width = slideshow".$cid."_width + 'px';\n";
			$slideshow_dimensions .= "$('properties-slideshow-".$cid."').style.height = slideshow".$cid."_height + 'px';\n";
			$slideshow_dimensions .= "$('properties-PlayButton".$cid."').style.top = (slideshow".$cid."_height-50)*(-1)+'px';\n";
			$slideshow_dimensions .= "$('properties-PauseButton".$cid."').style.top = (slideshow".$cid."_height-50)*(-1)+'px';\n";
			$slideshow_dimensions .= "$('properties-slide-caption".$cid."').style.width = (slideshow".$cid."_width-90)+'px';\n";
			$slideshow_dimensions .= "$$('#properties-slideshow-".$cid." .fade-box').each(function(element){element.setStyle({width: slideshow".$cid."_width+'px'});});\n";
			$tpl->SetVariable('slideshow_title', $xss->filter($parentTitle));
			/*
			if (file_exists(JAWS_DATA . 'files/css/flash_featured_overlay.png')) {
				$tpl->SetVariable('slideshow_overlay_image', 'url('.$GLOBALS['app']->getDataURL('', true) . 'files/css/flash_featured_overlay.png)');
			}
			*/
			$tpl->SetVariable('slideshow_height', 299);
			$tpl->SetVariable('slideshow_width', 343);
			$tpl->SetVariable('slideshow_timer', '10000');
			$tpl->SetVariable('slideshow_dimensions', $slideshow_dimensions);
			$tpl->SetVariable('slideshow_textbar_bkgnd', "url(../../../images/transparent.png) 0 0;");
			if ($cid != 'all') {
				$posts = $model->GetAllPropertiesOfParent($cid);
			} else {
				$posts = $model->GetProperties(null, 'sort_order', 'ASC', false, $searchownerid, 'Y');
			}
			if (!Jaws_Error::IsError($posts)) {
				$image_found = false;
				$post_count = count($posts);
				if (!$post_count <= 0) {
					$tpl->SetVariable('slideshow_load_immediately', ($post_count > 1 ? 'true' : 'false'));
					$tpl->SetVariable('slideshow_total', $post_count);
					if ($post_count == 1) {
						$tpl->SetBlock('layout/slideshow/stop');
						$tpl->SetVariable('id', $cid);
						$tpl->SetVariable('slideshow_timer', 5000);
						$tpl->ParseBlock('layout/slideshow/stop');
					}
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
								$ext = end(explode('.', $image_src));  
								if(in_array(strtolower($ext),$good_ext)) { 
									$image = $xss->filter($GLOBALS['app']->UTF8->str_replace('#', '%23', $image_src));
									$image_found = true;
									$image_style = ' style="';
									if ($i > 0) {
										$image_style .= ' display: none;';
									}
									$image_style .= '"';
									$tpl->SetBlock('layout/slideshow/image');
									if (isset($post['fast_url']) && !empty($post['fast_url'])) {
										$fast_url = $post['fast_url'];
									} else {
										$fast_url = $post['id'];
									}
									$url = $GLOBALS['app']->Map->GetURLFor('Properties', 'Property', array('id' => $fast_url));
									$tpl->SetVariable('image_url', $url);
									
									$image_dimensions = "$('properties-img-".$cid."_".$i."').style.width = slideshow".$cid."_width + 'px';\n";
									$image_dimensions .= "$('properties-img-".$cid."_".$i."').style.height = slideshow".$cid."_height + 'px';\n";
									$tpl->SetVariable('image_dimensions', $image_dimensions);
									//$tpl->SetVariable('image_height', $image_height);
									$tpl->SetVariable('image_target', '');
									$tpl->SetVariable('image_style', $image_style);
									$tpl->SetVariable('image_id', $i);
									$tpl->SetVariable('image_src', $image);
									$tpl->SetVariable('replace_image', $GLOBALS['app']->GetJawsURL().'/images/blank.gif');
									$tpl->SetVariable('image_linkid', $cid);
									$tpl->SetVariable('image_alt', htmlentities(strip_tags((isset($post['description']) && !empty($post['description']) ? $post['description'] : $post['title']))));
									$tpl->SetVariable('image_caption', '<b>'.$post['title'].'</b>&nbsp;<br />'.(isset($post['description']) && !empty($post['description']) ? substr(strip_tags($post['description']), 0, 100).'...' : ' '));
									$tpl->SetVariable('image_count', $i);
									$tpl->ParseBlock('layout/slideshow/image');
									
									$i++;
									if ($post_count == 1) {
										$tpl->SetBlock('layout/slideshow/image');
										if (isset($post['fast_url']) && !empty($post['fast_url'])) {
											$fast_url = $post['fast_url'];
										} else {
											$fast_url = $post['id'];
										}
										$url = $GLOBALS['app']->Map->GetURLFor('Properties', 'Property', array('id' => $fast_url));
										$image_dimensions = "$('properties-img-".$cid."_".($i+1)."').style.width = slideshow".$cid."_width + 'px';\n";
										$image_dimensions .= "$('properties-img-".$cid."_".($i+1)."').style.height = slideshow".$cid."_height + 'px';\n";
										$tpl->SetVariable('image_dimensions', $image_dimensions);
										//$tpl->SetVariable('image_height', $image_height);
										
										$tpl->SetVariable('image_url', $url);
										$tpl->SetVariable('image_target', '');
										$tpl->SetVariable('image_style', ' style="display: none;"');
										$tpl->SetVariable('image_id', $i);
										$tpl->SetVariable('image_linkid', $cid);
										$tpl->SetVariable('image_src', $image);
										$tpl->SetVariable('replace_image', $GLOBALS['app']->GetJawsURL().'/images/blank.gif');
										$tpl->SetVariable('image_alt', htmlentities(strip_tags((isset($post['description']) && !empty($post['description']) ? $post['description'] : $post['title']))));
										$tpl->SetVariable('image_caption', '<b>'.$post['title'].'</b>&nbsp;<br />'.(isset($post['description']) && !empty($post['description']) ? substr(strip_tags($post['description']), 0, 100).'...' : ' '));
										$tpl->SetVariable('image_count', $i);
										$tpl->ParseBlock('layout/slideshow/image');
									}
								}
							}
						}
					}
				}
				if ($post_count <= 0 || $image_found === false) {
					$tpl->SetBlock('layout/slideshow/image');
					$tpl->SetVariable('image_id', '0');
					$tpl->SetVariable('image_linkid', $cid);
					$tpl->SetVariable('image_src', $GLOBALS['app']->GetJawsURL() . "/gadgets/FlashGallery/images/gallery_no_images.jpg");
					$tpl->SetVariable('replace_image', $GLOBALS['app']->GetJawsURL().'/images/blank.gif');
					$tpl->SetVariable('image_caption', "Currently there are no featured properties.");
					$tpl->SetVariable('image_url', $GLOBALS['app']->Map->GetURLFor('Properties', 'Category', array('id' => $parentFastUrl)));
					$tpl->ParseBlock('layout/slideshow/image');
				}
			}
			
			//$submit_vars[SYNTACTS_DB ."0:$j:0"] = $xss->filter($value);
					
			//$tpl->SetVariable('layout_content', _t('FLASHGALLERY_LAYOUT_SLIDESHOW_DESCRIPTION'));
	        $tpl->ParseBlock('layout/slideshow');

			// If this is embedded, we need to pass the javascript variable that has the IFRAME height to the session 
			$display_id = md5('Properties'.(!empty($cid) && is_numeric($cid) ? (int)$cid : 'all'));
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
     * Display random photos of Properties in given categories.
     *
     * @param 	int 	$cid 	Property category (optional)
     * @param 	boolean 	$embedded 	Embedded mode
     * @param 	string 	$referer 	Embedding referer
     * @access 	public
     * @return 	string
     */
    function CategoryShowOne($cid = 1, $embedded = false, $referer = null)
    {
		require_once JAWS_PATH . 'include/Jaws/Image.php';
        
		$GLOBALS['app']->Layout->AddScriptLink('libraries/slideshow/slideshow-min.js');
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $model = $GLOBALS['app']->LoadGadget('Properties', 'Model');
		// send Properties records
		$request =& Jaws_Request::getInstance();
		$fetch = array('gadget', 'id');
		$get  = $request->get($fetch, 'get');
		
		//if on a users home page, show their stuff
		if (strtolower($get['gadget']) == 'users' && !empty($get['id'])) {
			$parents = $model->GetSinglePropertyParentByUserID($get['id'], $cid);
		} else {
			$parents = $model->GetPropertyParent($cid);
		}
		if (!Jaws_Error::IsError($parents) && isset($parents['propertyparentid']) && !empty($parents['propertyparentid'])) {
			require_once JAWS_PATH . 'include/Jaws/Template.php';
			$tpl = new Jaws_Template('gadgets/Properties/templates/');
	        $tpl->Load('normal.html');

	        $tpl->SetBlock('layout');
			$tpl->SetVariable('actionName', 'CategoryShowOne_' . $parents['propertyparentid'] . '_');
	        $tpl->SetVariable('layout_title', $parents['propertyparentcategory_name']);

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
					if ($parents['height'] != 'auto' && !empty($parents['custom_height'])) {
						$slideshow_dimensions .= "$('properties-slideshow-".$cid."').style.height = '".(int)$parents['custom_height']."px';\n";	
					} else {
						$slideshow_dimensions .= " 	$('properties-slideshow-".$cid."').style.height = parseInt(($('properties-slideshow-".$cid."').offsetWidth".$cid.")*(.75)) + 'px';\n";
					}
					*/
					$slideshow_dimensions .= "if ($('properties-slideshow-".$cid."').parentNode) {\n";
					$slideshow_dimensions .= " 	$('properties-slideshow-".$cid."').parentNode.style.display = 'block';\n";	
					$slideshow_dimensions .= "	$('properties-slideshow-".$cid."').parentNode.style.width = slideshow".$cid."_width + 'px';\n";
					$slideshow_dimensions .= "	$('properties-slideshow-".$cid."').parentNode.style.height = slideshow".$cid."_height + 'px';\n";
					$slideshow_dimensions .= "}\n";	
					$slideshow_dimensions .= "$('properties-slideshow_overlay".$cid."').style.width = slideshow".$cid."_width + 'px';\n";
					$slideshow_dimensions .= "$('properties-slideshow_overlay".$cid."').style.height = slideshow".$cid."_height + 'px';\n";
					$slideshow_dimensions .= "$('properties-slideshow-".$cid."').style.width = slideshow".$cid."_width + 'px';\n";
					$slideshow_dimensions .= "$('properties-slideshow-".$cid."').style.height = slideshow".$cid."_height + 'px';\n";
					$slideshow_dimensions .= "$('properties-PlayButton".$cid."').style.top = (slideshow".$cid."_height-50)*(-1)+'px';\n";
					$slideshow_dimensions .= "$('properties-PauseButton".$cid."').style.top = (slideshow".$cid."_height-50)*(-1)+'px';\n";
					$slideshow_dimensions .= "$('properties-slide-caption".$cid."').style.width = (slideshow".$cid."_width-90)+'px';\n";
					$tpl->SetVariable('slideshow_title', $xss->filter($parents['propertyparentcategory_name']));
					//$tpl->SetVariable('slideshow_overlay_image', 'url('.$GLOBALS['app']->getDataURL('', true) . 'files'.$xss->filter($parents['overlay_image']).')');
					$tpl->SetVariable('slideshow_height', 299);
					$tpl->SetVariable('slideshow_width', 343);
					$tpl->SetVariable('slideshow_timer', '');
					$tpl->SetVariable('slideshow_load_immediately', 'true');
					$tpl->SetVariable('slideshow_dimensions', $slideshow_dimensions);
					$tpl->SetVariable('slideshow_textbar_bkgnd', "url(../../../images/transparent.png) 0 0;");
					$posts = $model->GetAllPropertiesOfParent($cid);
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
										$tpl->SetVariable('image_href', $GLOBALS['app']->Map->GetURLFor('Properties', 'Property', array('id' => $fast_url)));
										$image_dimensions = "$('properties-img-".$cid."_".$post['id']."').style.width = slideshow".$cid."_width + 'px';\n";
										$image_dimensions .= "$('properties-img-".$cid."_".$post['id']."').style.height = slideshow".$cid."_height + 'px';\n";
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
							$tpl->SetVariable('image_caption', "Currently there are no featured properties.");
							$tpl->SetVariable('image_count', 0);
							$tpl->SetVariable('image_href', $GLOBALS['app']->Map->GetURLFor('Properties', 'Category', array('id' => $parents['propertyparentfast_url'])));
							$tpl->ParseBlock('layout/slideshow/image');
						}
					}
					
					//$submit_vars[SYNTACTS_DB ."0:$j:0"] = $xss->filter($value);
			//}
					
			//$tpl->SetVariable('layout_content', _t('FLASHGALLERY_LAYOUT_SLIDESHOW_DESCRIPTION'));
	        $tpl->ParseBlock('layout/slideshow');

			// If this is embedded, we need to pass the javascript variable that has the IFRAME height to the session 
			$display_id = md5('Properties'.(!empty($cid) && is_numeric($cid) ? (int)$cid : 'all'));
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
     * Display Property reservation calendars.
     *
     * @category 	feature
     * @param 	int 	$pid 	Property ID (optional)
     * @param 	string 	$mode 	Calendar mode (LayoutYear/LayoutMonth/LayoutDay)
     * @param 	boolean 	$embedded 	Embedded mode
     * @param 	string 	$referer 	Embedding referer
     * @param 	string 	$searchownerid 	OwnerID to match against
     * @access 	public
     * @return 	string
     */
    function PropertyCalendar($pid = null, $mode = 'LayoutYear', $embedded = false, $referer = null, $searchownerid = '')
    {
        $GLOBALS['app']->Layout->AddHeadLink('libraries/autocomplete/autocomplete.css', 'stylesheet', 'text/css', 'default');
        $GLOBALS['app']->Layout->AddHeadLink('gadgets/Properties/resources/style.css', 'stylesheet', 'text/css');
		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=Properties&amp;action=Ajax&amp;client=all&amp;stub=PropertiesAjax');
		$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=Properties&amp;action=AjaxCommonFiles');
		$GLOBALS['app']->Layout->AddScriptLink('gadgets/Properties/resources/client_script.js');
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$request =& Jaws_Request::getInstance();
		$fetch = array('gadget', 'id', 'action');
		$get  = $request->get($fetch, 'get');
		if (is_null($pid)) {
			$pid = (isset($get['id']) ? (int)$get['id'] : 'all');
		}
		$searchownerid = (!empty($searchownerid) ? (int)$searchownerid : null);
		
		$tpl = new Jaws_Template('gadgets/Properties/templates/');
		$tpl->Load('normal.html');
		$tpl->SetBlock('layout');
		$tpl->SetVariable('actionName', 'PropertyCalendar_');
		$tpl->SetVariable('layout_title', '');
		
		$calendar_html = _t('GLOBAL_ERROR_GENERAL');
		
		// ShowCalendar hook
		$hook = $GLOBALS['app']->loadHook('Properties', 'Calendar');
		if ($hook !== false) {
			if (method_exists($hook, 'ShowCalendar')) {
				$calendar = $hook->ShowCalendar(
					array(
						'gadget_reference' => $pid, 
						'mode' => $mode, 
						'uid' => $searchownerid
					)
				);
				if (!Jaws_Error::isError($calendar) && !empty($calendar)) {
					$calendar_html = $calendar;
				}
			}
		}
		
		$tpl->SetBlock('layout/calendar');
		$tpl->SetVariable('content', $calendar_html);		        
		$tpl->ParseBlock('layout/calendar');

		// If this is embedded, we need to pass the javascript variable that has the IFRAME height to the session 
		$display_id = md5('Properties'.(!empty($pid) && is_numeric($pid) ? (int)$pid : 'all'));
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
     * Displays an index of Property categories.
     *
     * @access 	public
     * @return 	string
     */
    function Index($uid = null, $embedded = false, $referer = null)
    {
        $model = $GLOBALS['app']->LoadGadget('Properties', 'Model');
        if (!is_null($uid)) {
			$pages = $model->GetPropertyParentsByUserID($uid);
		} else {
			$pages = $model->GetPropertyParents();
        }
		if (Jaws_Error::IsError($pages)) {
            return _t('PROPERTIES_ERROR_INDEX_NOT_LOADED');
        }

		$request =& Jaws_Request::getInstance();
		$embed_id  = $request->get('id', 'get');

		$date = $GLOBALS['app']->loadDate();
		$xss  = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $tpl = new Jaws_Template('gadgets/Properties/templates/');
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
				$tpl->SetVariable('update_string',  _t('PROPERTIES_LAST_UPDATE') . ': ');
				$tpl->SetVariable('updated', $date->Format($page['propertyparentupdated']));
				//$tpl->SetVariable('desc', strip_tags($page['content'], '<p><a><b><em><i><strong><div><table><tbody><thead><tr><td><span><font><hr /><br />'));
				if ($embedded == false) {
					$param = array('id' => !empty($page['propertyparentfast_url']) ? $xss->filter($page['propertyparentfast_url']) : $page['propertyparentid']);
					$link = $GLOBALS['app']->Map->GetURLFor('Properties', 'Category', $param);
					$tpl->SetVariable('desc', (!empty($page['propertyparentdescription']) ? (strlen(strip_tags($page['propertyparentdescription'])) > 247 ? substr(strip_tags($page['propertyparentdescription']),0,247)."&nbsp;<a href=\"".$link."\">... Read More</a>&nbsp;&nbsp;&nbsp;&nbsp;" : strip_tags($page['propertyparentdescription'])."&nbsp;&nbsp;&nbsp;&nbsp;") : ''));
				} else {
					$base_url = $GLOBALS['app']->GetSiteURL().'/';
					$link = $base_url."index.php?gadget=Properties&action=EmbedPropertyParent&id=".$page['id']."&mode=category&referer=".(!is_null($referer) ? $referer : "");
					$tpl->SetVariable('desc', (!empty($page['propertyparentdescription']) ? (strlen(strip_tags($page['propertyparentdescription'])) > 247 ? substr(strip_tags($page['propertyparentdescription']),0,247)."&nbsp;<a href=\"".$link."\">... Read More</a>&nbsp;&nbsp;&nbsp;&nbsp;" : strip_tags($page['propertyparentdescription'])."&nbsp;&nbsp;&nbsp;&nbsp;") : ''));
				}
				$tpl->SetVariable('link',  $link);
				$tpl->ParseBlock('index/item');
			} else {
				if ($page['propertyparentactive'] == 'Y' || $embedded == true) {
	                $tpl->SetBlock('index/item');
	                $tpl->SetVariable('title', strip_tags($page['propertyparentcategory_name']));
					$tpl->SetVariable('update_string',  _t('PROPERTIES_LAST_UPDATE') . ': ');
					$tpl->SetVariable('updated', $date->Format($page['propertyparentupdated']));
					if ($embedded == false) {
		                $param = array('id' => !empty($page['propertyparentfast_url']) ? $xss->filter($page['propertyparentfast_url']) : $page['propertyparentid']);
						$link = $GLOBALS['app']->Map->GetURLFor('Properties', 'Category', $param);
						$tpl->SetVariable('desc', (!empty($page['propertyparentdescription']) ? (strlen(strip_tags($page['propertyparentdescription'])) > 247 ? substr(strip_tags($page['propertyparentdescription']),0,247)."&nbsp;<a href=\"".$link."\">... Read More</a>&nbsp;&nbsp;&nbsp;&nbsp;" : strip_tags($page['propertyparentdescription'])."&nbsp;&nbsp;&nbsp;&nbsp;") : ''));
					} else {
				        $base_url = $GLOBALS['app']->GetSiteURL().'/';
		                $link = $base_url."index.php?gadget=Properties&action=EmbedPropertyParent&id=".$page['id']."&mode=category&referer=".(!is_null($referer) ? $referer : "");
						$tpl->SetVariable('desc', (!empty($page['propertyparentdescription']) ? (strlen(strip_tags($page['propertyparentdescription'])) > 247 ? substr(strip_tags($page['propertyparentdescription']),0,247)."&nbsp;<a href=\"".$link."\">... Read More</a>&nbsp;&nbsp;&nbsp;&nbsp;" : strip_tags($page['propertyparentdescription'])."&nbsp;&nbsp;&nbsp;&nbsp;") : ''));
					}
					$tpl->SetVariable('link',  $link);
	                $tpl->ParseBlock('index/item');
	            }
			}
        }
		$display_id = md5('Properties'.(!empty($embed_id) && is_numeric($embed_id) ? (int)$embed_id : 'all'));
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
     * Displays five random properties of users in given group ID
     *
     * @param 	int 	$gid 	Group ID
     * @param 	boolean 	$embedded 	Embedded mode
     * @param 	string 	$referer 	Embedding referer
     * @access 	public
     * @return 	XHTML 
     */
    function ShowFivePropertiesOfGroup($gid = 1, $embedded = false, $referer = null)
    {
		require_once JAWS_PATH . 'include/Jaws/Image.php';
		require_once JAWS_PATH . 'include/Jaws/User.php';
		$uModel = new Jaws_User;
        $model = $GLOBALS['app']->LoadGadget('Store', 'Model');
       
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');

		$groupInfo = $uModel->GetGroupInfoById($gid);
		if (!Jaws_Error::IsError($groupInfo) && isset($groupInfo['id']) && !empty($groupInfo['id'])) {
			require_once JAWS_PATH . 'include/Jaws/Template.php';
			$tpl = new Jaws_Template('gadgets/Properties/templates/');
	        $tpl->Load('normal.html');

	        $tpl->SetBlock('layout');
			$tpl->SetVariable('actionName', 'FivePropertiesOfGroup_' . $groupInfo['id'] . '_');
	        $tpl->SetVariable('layout_title', "Featured Properties");
			$tpl->SetVariable('id', $groupInfo['id']);
	        $tpl->SetBlock('featuredproperties');
			$tpl->SetVariable('gid', $groupInfo['id']);
			
			$ba = array();
			$i = 0;
			$products = $model->GetPropertiesOfGroup($groupInfo['id'], 'sort_order', 'ASC', 'Y');
			if (!Jaws_Error::IsError($products)) {
				foreach($products as $p) {		            
					if (isset($p['id']) && !empty($p['title'])) {					            
						$ba[$i] = $p['id'];
						$i++;
					}
				}
		
				// Choose random IDs
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
							if (!in_array('property_'.$ba[$buttons_rand], $GLOBALS['app']->_ItemsOnLayout)) {
								array_push($GLOBALS['app']->_ItemsOnLayout, 'property_'.$ba[$buttons_rand]);
								break;
							} else {
								$buttons_rand = -1;
							}
							$r++;
						}
						$productInfo = $model->GetProperty($ba[$buttons_rand]);
						if (!Jaws_Error::IsError($productInfo) && isset($productInfo['id']) && !empty($productInfo['id'])) {
							$tpl->SetBlock('featuredproperties/item');
							$tpl->SetVariable('pid', $productInfo['id']);
							$title = '';
							$title = $xss->filter(strip_tags($productInfo['title']));
							$tpl->SetVariable('title', (strlen($title) > 25 ? substr($title, 0, 25) . '...' : $title));
							$href = '';
							$href = $GLOBALS['app']->Map->GetURLFor('Properties', 'Property', array('id' => $productInfo['fast_url']));
							$tpl->SetVariable('href', $href);
							
							$price = 0;
							if (!empty($productInfo['price']) && ($productInfo['price'] > 0)) {
								$price = number_format($productInfo['price'], 2, '.', '');
							}
							$price_string = '$'.number_format($price, 2, '.', ',');
							$tpl->SetVariable('price', '&nbsp;'.$xss->filter($price_string));
							$image = '';
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
							if (!empty($image)) {
								$tpl->SetBlock('featuredproperties/item/image');
								$tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . "/");
								//$tpl->SetVariable('base_url', JAWS_DPATH);
								$tpl->SetVariable('image_src', $image_src);
								$tpl->SetVariable('image_caption', $title);
								$tpl->SetVariable('image_href', $href);
								$tpl->ParseBlock('featuredproperties/item/image');
							} else {
								$tpl->SetBlock('featuredproperties/item/no_image');
								$tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . "/");
								//$tpl->SetVariable('base_url', JAWS_DPATH);
								$tpl->SetVariable('image_href', $href);
								$tpl->ParseBlock('featuredproperties/item/no_image');
							}
							$tpl->ParseBlock('featuredproperties/item');
						}
					}
				}
			}
	        $tpl->ParseBlock('featuredproperties');

			$display_id = md5('Properties'.'FivePropertiesOfGroup'.$gid);
			// If this is embedded, we need to pass the javascript variable that has the IFRAME height to the session 
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
     * Displays a random "premium" property
     *
     * @param 	boolean 	$embedded 	Embedded mode
     * @param 	string 	$referer 	Embedding referer
     * @access 	public
     * @return 	XHTML 
     */
    function ShowPremiumProperty($embedded = false, $referer = null)
    {
		require_once JAWS_PATH . 'include/Jaws/Image.php';
        $GLOBALS['app']->Layout->AddHeadLink('gadgets/Properties/resources/style.css', 'stylesheet', 'text/css');
        $model = $GLOBALS['app']->LoadGadget('Properties', 'Model');
       
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');

		require_once JAWS_PATH . 'include/Jaws/Template.php';
		$tpl = new Jaws_Template('gadgets/Properties/templates/');
		$tpl->Load('normal.html');

		$tpl->SetBlock('layout');
		$tpl->SetVariable('actionName', 'PremiumProperty_');
		$tpl->SetVariable('layout_title', "Featured Listing");
		$tpl->SetVariable('id', 'PremiumProperty');
		$tpl->SetBlock('layout/featuredproperties');
		$tpl->SetVariable('gid', 'PremiumProperty');
		
		$ba = array();
		$i = 0;
		// Load Update hook
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout(
			'onLoadShowPremiumProperty', true
		);
		if (isset($res['items'])) {
			$products = array();
			foreach ($res['items'] as $item) {
				$products[] = $item;
			}
		} else {
			$products = $model->GetProperties(null, 'premium', 'DESC', false, null, 'Y');
		}
		if (!Jaws_Error::IsError($products)) {
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
					if (!in_array('property_'.$ba[$buttons_rand], $GLOBALS['app']->_ItemsOnLayout)) {
						array_push($GLOBALS['app']->_ItemsOnLayout, 'property_'.$ba[$buttons_rand]);
						break;
					} else {
						$buttons_rand = -1;
					}
				}
				$productInfo = $model->GetProduct($ba[$buttons_rand]);
				if (!Jaws_Error::IsError($productInfo) && isset($productInfo['id']) && !empty($productInfo['id'])) {
					$tpl->SetBlock('featuredproperties/item');
					$tpl->SetVariable('pid', $productInfo['id']);
					$title = '';
					$title = $xss->filter(strip_tags($productInfo['title']));
					$tpl->SetVariable('title', (strlen($title) > 25 ? substr($title, 0, 25) . '...' : $title));
					$href = '';
					$href = $GLOBALS['app']->Map->GetURLFor('Properties', 'Property', array('id' => $productInfo['fast_url']));
					$tpl->SetVariable('href', $href);
					
					$price = 0;
					if (!empty($productInfo['price']) && ($productInfo['price'] > 0)) {
						$price = number_format($productInfo['price'], 2, '.', '');
					}
					$price_string = '$'.number_format($price, 2, '.', ',');
					$tpl->SetVariable('price', '&nbsp;'.$xss->filter($price_string));
					$image = '';
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
					if (!empty($image)) {
						$tpl->SetBlock('featuredproperties/item/image');
						$tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . "/");
						//$tpl->SetVariable('base_url', JAWS_DPATH);
						$tpl->SetVariable('image_src', $image_src);
						$tpl->SetVariable('image_caption', $title);
						$tpl->SetVariable('image_href', $href);
						$tpl->ParseBlock('featuredproperties/item/image');
					} else {
						$tpl->SetBlock('featuredproperties/item/no_image');
						$tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . "/");
						//$tpl->SetVariable('base_url', JAWS_DPATH);
						$tpl->SetVariable('image_href', $href);
						$tpl->ParseBlock('featuredproperties/item/no_image');
					}
					$tpl->ParseBlock('featuredproperties/item');
					$u++;
				}
			}
		}
		$tpl->ParseBlock('layout/featuredproperties');

		// If this is embedded, we need to pass the javascript variable that has the IFRAME height to the session 
		$display_id = md5('Properties'.'PremiumProperty');
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
     * Displays a random "premium" property of group
     *
     * @param 	int 	$group 	Group ID
     * @param 	boolean 	$embedded 	Embedded mode
     * @param 	string 	$referer 	Embedding referer
     * @access 	public
     * @return 	XHTML 
     */
    function ShowPremiumPropertyOfGroup($group, $embedded = false, $referer = null)
    {
		require_once JAWS_PATH . 'include/Jaws/Image.php';
        $GLOBALS['app']->Layout->AddHeadLink('gadgets/Properties/resources/style.css', 'stylesheet', 'text/css');
        $model = $GLOBALS['app']->LoadGadget('Properties', 'Model');
       
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');

		require_once JAWS_PATH . 'include/Jaws/Template.php';
		$tpl = new Jaws_Template('gadgets/Properties/templates/');
		$tpl->Load('normal.html');

		$tpl->SetBlock('layout');
		$tpl->SetVariable('actionName', 'PremiumPropertyOfGroup'.$group.'_');
		$tpl->SetVariable('layout_title', "Today's Deal");
		$tpl->SetVariable('id', 'PremiumPropertyOfGroup'.$group);
		$tpl->SetBlock('layout/featuredproperties');
		$tpl->SetVariable('gid', 'PremiumPropertyOfGroup'.$group);
		
		$ba = array();
		$i = 0;
		$products = $model->GetPropertiesOfGroup($group);
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
					if (!in_array('property_'.$ba[$buttons_rand], $GLOBALS['app']->_ItemsOnLayout)) {
						array_push($GLOBALS['app']->_ItemsOnLayout, 'property_'.$ba[$buttons_rand]);
						break;
					} else {
						$buttons_rand = -1;
					}
				}
				$productInfo = $model->GetProperty($ba[$buttons_rand]);
				if (!Jaws_Error::IsError($productInfo) && isset($productInfo['id']) && !empty($productInfo['id'])) {
					$tpl->SetBlock('featuredproperties/item');
					$tpl->SetVariable('pid', $productInfo['id']);
					$title = '';
					$title = $xss->filter(strip_tags($productInfo['title']));
					$tpl->SetVariable('title', (strlen($title) > 25 ? substr($title, 0, 25) . '...' : $title));
					$href = '';
					$href = $GLOBALS['app']->Map->GetURLFor('Properties', 'Property', array('id' => $productInfo['fast_url']));
					$tpl->SetVariable('href', $href);
					
					$price = 0;
					if (!empty($productInfo['price']) && ($productInfo['price'] > 0)) {
						$price = number_format($productInfo['price'], 2, '.', '');
					}
					$price_string = '$'.number_format($price, 2, '.', ',');
					$tpl->SetVariable('price', '&nbsp;'.$xss->filter($price_string));
					$image = '';
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
					if (!empty($image)) {
						$tpl->SetBlock('featuredproperties/item/image');
						$tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . "/");
						//$tpl->SetVariable('base_url', JAWS_DPATH);
						$tpl->SetVariable('image_src', $image_src);
						$tpl->SetVariable('image_caption', $title);
						$tpl->SetVariable('image_href', $href);
						$tpl->ParseBlock('featuredproperties/item/image');
					} else {
						$tpl->SetBlock('featuredproperties/item/no_image');
						$tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . "/");
						//$tpl->SetVariable('base_url', JAWS_DPATH);
						$tpl->SetVariable('image_href', $href);
						$tpl->ParseBlock('featuredproperties/item/no_image');
					}
					$tpl->ParseBlock('featuredproperties/item');
					$u++;
				}
			}
		}
		$tpl->ParseBlock('layout/featuredproperties');

		// If this is embedded, we need to pass the javascript variable that has the IFRAME height to the session 
		$display_id = md5('Properties'.'PremiumPropertyOfGroup'.$group);
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
     * Displays five random properties
     *
     * @param 	boolean 	$embedded 	Embedded mode
     * @param 	string 	$referer 	Embedding referer
     * @access 	public
     * @return 	XHTML 
     */
    function ShowFiveProperties($embedded = false, $referer = null)
    {
		require_once JAWS_PATH . 'include/Jaws/Image.php';
        $model = $GLOBALS['app']->LoadGadget('Store', 'Model');
       
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');

		require_once JAWS_PATH . 'include/Jaws/Template.php';
		$tpl = new Jaws_Template('gadgets/Properties/templates/');
		$tpl->Load('normal.html');

		$tpl->SetBlock('layout');
		$tpl->SetVariable('actionName', 'FiveProperties_');
		$tpl->SetVariable('layout_title', "Featured Properties");
		$tpl->SetVariable('id', 'FiveProperties');
		$tpl->SetBlock('featuredproperties');
		$tpl->SetVariable('gid', 'FiveProperties');
		
		$ba = array();
		$i = 0;
		// Load Update hook
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout(
			'onLoadShowFiveProperties', true
		);
		if (isset($res['items'])) {
			$products = array();
			foreach ($res['items'] as $item) {
				$products[] = $item;
			}
		} else {
			$products = $model->GetProperties(null, 'sort_order', 'ASC', false, null, 'Y');
		}
		if (!Jaws_Error::IsError($products)) {
			foreach($products as $p) {		            
				if (isset($p['id']) && !empty($p['title']) && !empty($p['image'])) {					            
					$ba[$i] = $p['id'];
					$i++;
				}
			}
	
			// Choose random IDs
			if (isset($ba[0])) {
				if ($i > 4) {
					$i = 4;
				}
				for ($b=0; $b<$i; $b++) {
					while (true) {
						$buttons_rand = array_rand($ba);
						if (!in_array('property_'.$ba[$buttons_rand], $GLOBALS['app']->_ItemsOnLayout)) {
							array_push($GLOBALS['app']->_ItemsOnLayout, 'property_'.$ba[$buttons_rand]);
							break;
						} else {
							$buttons_rand = -1;
						}
					}
					$productInfo = $model->GetProperty($ba[$buttons_rand]);
					if (!Jaws_Error::IsError($productInfo) && isset($productInfo['id']) && !empty($productInfo['id'])) {
						$tpl->SetBlock('featuredproperties/item');
						$tpl->SetVariable('pid', $productInfo['id']);
						$title = '';
						$title = $xss->filter(strip_tags($productInfo['title']));
						$tpl->SetVariable('title', (strlen($title) > 25 ? substr($title, 0, 25) . '...' : $title));
						$href = '';
						$href = $GLOBALS['app']->Map->GetURLFor('Properties', 'Property', array('id' => $productInfo['fast_url']));
						$tpl->SetVariable('href', $href);
						
						$price = 0;
						if (!empty($productInfo['price']) && ($productInfo['price'] > 0)) {
							$price = number_format($productInfo['price'], 2, '.', '');
						}
						$price_string = '$'.number_format($price, 2, '.', ',');
						$tpl->SetVariable('price', '&nbsp;'.$xss->filter($price_string));
						$image = '';
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
						if (!empty($image)) {
							$tpl->SetBlock('featuredproperties/item/image');
							$tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . "/");
							//$tpl->SetVariable('base_url', JAWS_DPATH);
							$tpl->SetVariable('image_src', $image_src);
							$tpl->SetVariable('image_caption', $title);
							$tpl->SetVariable('image_href', $href);
							$tpl->ParseBlock('featuredproperties/item/image');
						} else {
							$tpl->SetBlock('featuredproperties/item/no_image');
							$tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . "/");
							//$tpl->SetVariable('base_url', JAWS_DPATH);
							$tpl->SetVariable('image_href', $href);
							$tpl->ParseBlock('featuredproperties/item/no_image');
						}
						$tpl->ParseBlock('featuredproperties/item');
					}
				}
			}
		}
		$tpl->ParseBlock('featuredproperties');

		// If this is embedded, we need to pass the javascript variable that has the IFRAME height to the session 
		$display_id = md5('Properties'.'FiveProperties');
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