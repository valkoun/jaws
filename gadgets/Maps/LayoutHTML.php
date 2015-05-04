<?php
/**
 * Maps Gadget (layout actions in client side)
 *
 * @category   GadgetLayout
 * @package    Maps
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class MapsLayoutHTML
{
    /**
     * Loads layout actions
     *
     * @access private
     */
    function LoadLayoutActions()
    {
        $actions = array();
        $model = $GLOBALS['app']->LoadGadget('Maps', 'Model');
        
		$pages = $model->GetMaps();

        if (!Jaws_Error::isError($pages)) {
            foreach ($pages as $page) {
				if ($page['ownerid'] == 0) {
					$actions['Display(' . $page['id'] . ')'] = array(
						'mode' => 'LayoutAction',
						'name' => $page['title'],
						'desc' => _t('MAPS_LAYOUT_DISPLAY_DESCRIPTION')
					);
				}
            }
        }
        return $actions;
    }

	/**
     * Displays a Map.
     *
     * @param 	int 	$cid 	Map ID
     * @access 	public
     * @return 	string
     */
    function Display($cid = 1)
    {
		// for boxover on date highlighting
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $model = $GLOBALS['app']->LoadGadget('Maps', 'Model');
        //$mapsHTML = $GLOBALS['app']->LoadGadget('Maps', 'HTML');
		// send calendarParent records
		$request =& Jaws_Request::getInstance();
		$fetch = array('gadget', 'id', 'action');
		$get  = $request->getRaw($fetch, 'get');
		
		//if on a users home page, show their stuff
		if (strtolower($get['gadget']) == 'users' && !empty($get['id'])) {
			$parents = $model->GetSingleMapByUserID($get['id'], $cid);
		} else {
			$parents = $model->GetMap($cid);
		}
		if ($parents['id']) {
			//$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=Maps&action=hideGoogleAPIAlerts');			
			$GLOBALS['app']->Layout->AddScriptLink('https://maps.googleapis.com/maps/api/js?v=3&sensor=false&key='.$GLOBALS['app']->Registry->Get('/gadgets/Maps/googlemaps_key'));			
			$GLOBALS['app']->Layout->AddScriptLink('libraries/js/progressbarcontrol.js');			
			$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=Maps&amp;action=Ajax&amp;client=all&amp;stub=MapsAjax');
			$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=Maps&amp;action=AjaxCommonFiles');
			$GLOBALS['app']->Layout->AddScriptLink('gadgets/Maps/resources/client_script.js');

			$tpl = new Jaws_Template('gadgets/Maps/templates/');
	        $tpl->Load('normal.html');

	        $tpl->SetBlock('layout');
			$tpl->SetVariable('actionName', 'Display_' . $parents['id'] . '_');
	        $tpl->SetVariable('layout_title', $xss->filter($parents['title']));

	        $tpl->SetBlock('layout/maplayout');
			
			$tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . '/');
			//$tpl->SetVariable('base_url', JAWS_DPATH);
			$tpl->SetVariable('id', $cid);
			$xml_url = 'index.php?googlemapsxml/'.$cid;
			$tpl->SetVariable('map_xml_url', $xml_url);
			if (!in_array($parents['map_type'], array('HYBRID','ROADMAP','SATELLITE','TERRAIN'))) {
				$parents['map_type'] = 'ROADMAP';
			}
			$tpl->SetVariable('map_type', $parents['map_type']);
			// build dimensions
			if ($parents['custom_height']) {
				$map_height = $parents['custom_height'];
			} else {
				$map_height = "500";
			}
			$map_dimensions = "";
			//$map_width = "";
			/*
			if ($get['gadget'] == 'CustomPage' && JAWS_SCRIPT == "admin") {
				$map_width = "450";
				$map_dimensions = "<style>\n";
				$map_dimensions .= " 	#layout-maps-body {\n";
				$map_dimensions .= " 		width: 450px;\n";	
				$map_dimensions .= "	}\n";	
				$map_dimensions .= "</style>\n";	
				$map_dimensions .= "<script type=\"text/javascript\">\n";
				$map_dimensions .= " 	$('map-".$cid."').style.width = 450;\n";	
				$map_dimensions .= "</script>\n";
			} else {	
			*/
				
				//$map_width = "500";
				//$map_dimensions .= "<script type=\"text/javascript\">\n";
				//$map_dimensions .= "	document.getElementById('map-".$cid."').style.width = '1000px';\n";	
				//$map_dimensions .= "</script>\n";	
			//}
			$tpl->SetVariable('map_height', $map_height);
			//$tpl->SetVariable('map_width', $map_width);
			$tpl->SetVariable('map_zoom', 0);
			$tpl->SetVariable('map_dimensions', $map_dimensions);
			//$submit_vars[SYNTACTS_DB ."0:$j:0"] = $xss->filter($value);
					
	        $tpl->ParseBlock('layout/maplayout');

	        $tpl->ParseBlock('layout');

	        return $tpl->Get();
		}
		
    }
	
	/**
     * Displays a Map of physical address.
     *
     * @param 	string 	$address 	Physical address to geocode
     * @param 	string 	$title 	Title to show (for physical address)
     * @param 	int 	$map_height 	Height in pixels of map
     * @param 	int 	$map_zoom 	Initial map zoom level 
     * @access public
     * @return string
     */
    function DisplayMapOfAddress(
		$address = '', $title = '', $map_height = 500, $map_zoom = 15, $map_type = 'ROADMAP', 
		$maptype_position = 'TOP_RIGHT', $zoom_position = 'TOP_LEFT', $streetview_position = 'TOP_LEFT'
	){
		// for boxover on date highlighting
		$xss = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
        $model = $GLOBALS['app']->LoadGadget('Maps', 'Model');
        $mapsHTML = $GLOBALS['app']->LoadGadget('Maps', 'HTML');
		
		if (!empty($address)) {
			//$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=Maps&action=hideGoogleAPIAlerts');			
			$GLOBALS['app']->Layout->AddScriptLink('https://maps.googleapis.com/maps/api/js?v=3&sensor=false&key='.$GLOBALS['app']->Registry->Get('/gadgets/Maps/googlemaps_key'));			
			/*
			$GLOBALS['app']->Layout->AddScriptLink('libraries/js/progressbarcontrol.js');			
			$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=Maps&amp;action=Ajax&amp;client=all&amp;stub=MapsAjax');
			$GLOBALS['app']->Layout->AddScriptLink('index.php?gadget=Maps&amp;action=AjaxCommonFiles');
			$GLOBALS['app']->Layout->AddScriptLink('gadgets/Maps/resources/client_script.js');
			*/
			$mapsHTML->AjaxMe('client_script.js');
			$tpl = new Jaws_Template('gadgets/Maps/templates/');
	        $tpl->Load('normal.html');

	        $tpl->SetBlock('layout');
			$tpl->SetVariable('actionName', 'Display_Map_');
	        $tpl->SetVariable('layout_title', $title);

	        $tpl->SetBlock('layout/mapaddresslayout');
			
			$request =& Jaws_Request::getInstance();
			$get = $request->get(array('id', 'showcase_id'), 'get');
			
			//$post['showcase_id'] = $xss->defilter($post['showcase_id']);

			//if(!empty($post['showcase_id'])) {
			//	$agentID = $post['showcase_id'];
			//}
			$googlemaps_key = $GLOBALS['app']->Registry->Get('/gadgets/Maps/googlemaps_key');
			$key = (!empty($googlemaps_key) ? $googlemaps_key : "ABQIAAAAbHKtlYQg6w5AJ9d2_shprxRSdpoJbNiUEES6uLAQrtqeLOB2WBROsVk6Deve8XT-33SEgoVpReKw5Q");
			include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'Snoopy.php';
			include_once JAWS_PATH . 'include' . DIRECTORY_SEPARATOR . 'Jaws' . DIRECTORY_SEPARATOR . 'XMLParser.php';
				
			$tpl->SetVariable('JAWS_URL', $GLOBALS['app']->GetJawsURL() . '/');
			//$tpl->SetVariable('base_url', JAWS_DPATH);
			$tpl->SetVariable('id', 'Map');
			if (!in_array($map_type, array('HYBRID','ROADMAP','SATELLITE','TERRAIN'))) {
				$map_type = 'ROADMAP';
			}
			$tpl->SetVariable('map_type', $map_type);
			$coordinates = '';
			// snoopy
			$snoopy = new Snoopy('Maps');
			$snoopy->agent = "Jaws";
			$geocode_url = "http://maps.google.com/maps/geo?q=".urlencode($address)."&output=xml&key=".$key;
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
					if ($xml_result[0][0]['CODE'] == '200' && isset($xml_result[0][$i]['COORDINATES']) && empty($coordinates)) {
						//if (isset($xml_result[0][$i]['COORDINATES'])) {
							$coordinates = $xml_result[0][$i]['COORDINATES'];
						//}
					}
				}
			}
			$marker_html = "<div><b>".(!empty($title) ? $xss->defilter(urldecode($title)) : 'My Location')."</b><br />".$xss->defilter(urldecode($address))."</div>";
			$marker_html .= "<div style='clear: both;'>&nbsp;</div>";
			$map_address = '';
			$map_lnglat = '';
			if (!empty($coordinates)) {
				$map_address = '';
				$map_lnglat = $coordinates;
			} else {
				$map_address = htmlentities(str_replace('&', 'and', $xss->defilter(urldecode($address))));
				$map_lnglat = '';
			}
			$tpl->SetVariable('address', $map_address);
			$tpl->SetVariable('lnglat', $map_lnglat);
			$tpl->SetVariable('description', $marker_html);
			$tpl->SetVariable('target', 'infowindow');
			$tpl->SetVariable('fs', '10');
			$tpl->SetVariable('sfs', '6');
			$tpl->SetVariable('bw', '2');
			$tpl->SetVariable('ra', '9');
			$tpl->SetVariable('fc', 'FFFFFF');
			$tpl->SetVariable('fg', '666666');
			$tpl->SetVariable('hfc', '222222');
			$tpl->SetVariable('hfg', 'FFFFFF');
			$tpl->SetVariable('hbc', '666666');
			$tpl->SetVariable('url', '');
			$tpl->SetVariable('title', (!empty($title) ? htmlentities(str_replace('&', 'and', $xss->defilter(urldecode($title)))) : 'My Location'));
			// build dimensions
			$map_dimensions = "";
			//$map_width = "";
			$tpl->SetVariable('map_height', $map_height);
			//$tpl->SetVariable('map_width', $map_width);
			$tpl->SetVariable('map_zoom', $map_zoom);
			$tpl->SetVariable('map_dimensions', $map_dimensions);
			if (!in_array($maptype_position, 
				array('TOP_CENTER', 'TOP_LEFT', 'TOP_RIGHT', 'LEFT_TOP', 'RIGHT_TOP', 
					'LEFT_CENTER', 'RIGHT_CENTER', 'LEFT_BOTTOM', 'RIGHT_BOTTOM', 
					'BOTTOM_CENTER', 'BOTTOM_LEFT', 'BOTTOM_RIGHT'))) {
				$maptype_position = 'TOP_RIGHT';
			}
			$tpl->SetVariable('maptype_position', $maptype_position);
			if (!in_array($zoom_position, 
				array('TOP_CENTER', 'TOP_LEFT', 'TOP_RIGHT', 'LEFT_TOP', 'RIGHT_TOP', 
					'LEFT_CENTER', 'RIGHT_CENTER', 'LEFT_BOTTOM', 'RIGHT_BOTTOM', 
					'BOTTOM_CENTER', 'BOTTOM_LEFT', 'BOTTOM_RIGHT'))) {
				$zoom_position = 'TOP_LEFT';
			}
			$tpl->SetVariable('zoom_position', $zoom_position);
			if (!in_array($streetview_position, 
				array('TOP_CENTER', 'TOP_LEFT', 'TOP_RIGHT', 'LEFT_TOP', 'RIGHT_TOP', 
					'LEFT_CENTER', 'RIGHT_CENTER', 'LEFT_BOTTOM', 'RIGHT_BOTTOM', 
					'BOTTOM_CENTER', 'BOTTOM_LEFT', 'BOTTOM_RIGHT'))) {
				$streetview_position = 'TOP_LEFT';
			}
			$tpl->SetVariable('streetview_position', $streetview_position);
			//$submit_vars[SYNTACTS_DB ."0:$j:0"] = $xss->filter($value);
					
	        $tpl->ParseBlock('layout/mapaddresslayout');

	        $tpl->ParseBlock('layout');

	        return $tpl->Get();
		}
		
    }
}