<?php
/**
 * Maps Gadget
 *
 * @category   GadgetModel
 * @package    Maps
 * @author     Alan Valkoun <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */

require_once JAWS_PATH . 'gadgets/Maps/Model.php';
class MapsAdminModel extends MapsModel
{
    var $_Name = 'Maps';
	
	/**
     * Install the gadget
     *
     * @access  public
     * @return  boolean  Success/failure
     */
    function InstallGadget()
    {

        $result = $this->installSchema('schema.xml');
        if (Jaws_Error::IsError($result)) {
            return $result;
        }
        
        if (file_exists(JAWS_PATH . 'gadgets/'.$this->_Name.'/schema/insert.xml')) {
			$variables = array();
			$variables['timestamp'] = $GLOBALS['db']->Date();

			$result = $this->installSchema('insert.xml', $variables, 'schema.xml', true);
			if (Jaws_Error::IsError($result)) {
				return $result;
			}
		}

        // Events
        $GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
        $GLOBALS['app']->Shouter->NewShouter('Maps', 'onAddMap');   		// trigger an action when we add a parent
        $GLOBALS['app']->Shouter->NewShouter('Maps', 'onDeleteMap');		// trigger an action when we delete a parent
        $GLOBALS['app']->Shouter->NewShouter('Maps', 'onUpdateMap');		// and when we update a parent..
        $GLOBALS['app']->Shouter->NewShouter('Maps', 'onAddMapPost');   	// trigger an action when we add a post
        $GLOBALS['app']->Shouter->NewShouter('Maps', 'onDeleteMapPost');	// trigger an action when we delete a post
        $GLOBALS['app']->Shouter->NewShouter('Maps', 'onUpdateMapPost');	// and when we update a post..

		$GLOBALS['app']->loadClass('Listener', 'Jaws_EventListener');
		$GLOBALS['app']->Listener->NewListener('Maps', 'onAfterEnablingGadget', 'InsertDefaultChecksums');
        
		$GLOBALS['app']->Registry->NewKey('/gadgets/Maps/googlemaps_key', '');
		if (!in_array('Maps', explode(',', $GLOBALS['app']->Registry->Get('/gadgets/plain_editor_items')))) {
			if ($GLOBALS['app']->Registry->Get('/gadgets/plain_editor_items') == '') {
				$GLOBALS['app']->Registry->Set('/gadgets/plain_editor_items', 'Maps');
			} else {
				$GLOBALS['app']->Registry->Set('/gadgets/plain_editor_items', $GLOBALS['app']->Registry->Get('/gadgets/plain_editor_items').',Maps');
			}
		}

        return true;
    }

    /**
     * Uninstalls the gadget
     *
     * @access  public
     * @return  boolean  Success/Failure (Jaws_Error)
     */
    function UninstallGadget()
    {
        $tables = array('maps',
                        'maps_locations',
						'country');
        foreach ($tables as $table) {
            $result = $GLOBALS['db']->dropTable($table);
            if (Jaws_Error::IsError($result)) {
                $gName  = _t('MAPS_NAME');
                $errMsg = _t('GLOBAL_ERROR_GADGET_NOT_UNINSTALLED', $gName);
                $GLOBALS['app']->Session->PushLastResponse($errMsg, RESPONSE_ERROR);
                return new Jaws_Error($errMsg, $gName);
            }
        }

        // Events
        $GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
        $GLOBALS['app']->Shouter->DeleteShouter('Maps', 'onAddMap');   		// trigger an action when we add a parent
        $GLOBALS['app']->Shouter->DeleteShouter('Maps', 'onDeleteMap');		// trigger an action when we delete a parent
        $GLOBALS['app']->Shouter->DeleteShouter('Maps', 'onUpdateMap');		// and when we update a parent..
        $GLOBALS['app']->Shouter->DeleteShouter('Maps', 'onAddMapPost');   	// trigger an action when we add a post
        $GLOBALS['app']->Shouter->DeleteShouter('Maps', 'onDeleteMapPost');	// trigger an action when we delete a post
        $GLOBALS['app']->Shouter->DeleteShouter('Maps', 'onUpdateMapPost');	// and when we update a post..

		$GLOBALS['app']->loadClass('Listener', 'Jaws_EventListener');
		$GLOBALS['app']->Listener->DeleteListener('Maps', 'InsertDefaultChecksums');
       
		$GLOBALS['app']->Registry->DeleteKey('/gadgets/Maps/googlemaps_key');
		if (!in_array('Maps', explode(',', $GLOBALS['app']->Registry->Get('/gadgets/plain_editor_items')))) {
			if ($GLOBALS['app']->Registry->Get('/gadgets/plain_editor_items') == 'Maps') {
				$GLOBALS['app']->Registry->Set('/gadgets/plain_editor_items', '');
			} else {
				$GLOBALS['app']->Registry->Set('/gadgets/plain_editor_items', str_replace(',Maps', '', $GLOBALS['app']->Registry->Get('/gadgets/plain_editor_items')));
			}
		}

        return true;
    }

    /**
     * Update the gadget
     *
     * @access  public
     * @param   string  $old Current version (in registry)
     * @param   string  $new     New version (in the $gadgetInfo file)
     * @return  boolean  Success/Failure (JawsError)
     */
    function UpdateGadget($old, $new)
    {
        if (version_compare($old, '0.1.1', '<')) {			
			$result = $this->installSchema('schema.xml', '', '0.1.0.xml');
			if (Jaws_Error::IsError($result)) {
				return $result;
			}
		}
		
        $currentClean = str_replace(array('.', ' '), '', $old);
        $newClean     = str_replace(array('.', ' '), '', $new);

        $funcName   = 'upgradeFrom' . $currentClean;
        $scriptFile = JAWS_PATH . 'gadgets/' . $this->_Name . '/upgradeScripts/' . $funcName . '.php';
        if (file_exists($scriptFile)) {
            require_once $scriptFile;
            //Ok.. append the funcName at the start
            $funcName = $this->_Name . '_' . $funcName;
            if (function_exists($funcName)) {
                $res = $funcName();
                return $res;
            }
        }
        return true;
    }
				
    /**
     * Creates a new map.
     *
     * @param   string  $title          The title of the map.
     * @param   string  $description    The description of map
     * @param   int  $custom_height    Height of the map (in pixels)
     * @param   string  $active         (Y/N) If the map is published or not
     * @param   int  $OwnerID         The poster's user ID
     * @param   string  $map_type 	Map type (terrain/street/hybrid)
     * @param   string 	$checksum 	Unique ID
     * @param   boolean 	$auto       		If it's auto saved or not
     * @access  public
     * @return  bool    Success/failure
     */
    function AddMap($title, $description, $custom_height, $active, $OwnerID, $map_type, $checksum = '', $auto = false)
    {
		$model = $GLOBALS['app']->LoadGadget('Maps', 'Model');
		if (empty($title)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('MAPS_ERROR_INVALID_TITLE'), RESPONSE_ERROR);
			require_once JAWS_PATH . 'include/Jaws/Header.php';
			Jaws_Header::Location(BASE_SCRIPT . '?gadget=Maps&action=form');
		}

        $sql = "
            INSERT INTO [[maps]]
                ([title], [description], [custom_height], [ownerid], 
				[active], [created], [updated], [map_type], [checksum])
            VALUES
                ({title}, {description}, {custom_height}, {OwnerID}, 
				{Active}, {now}, {now}, {map_type}, {checksum})";
		
		$OwnerID = (!is_null($OwnerID)) ? (int)$OwnerID : 0;
        $params               		= array();
        $params['title'] 			= $title;
        $params['description']   	= str_replace("\r\n", "\n", $description);
        $params['custom_height'] = (int)$custom_height;
		$params['OwnerID']         	= $OwnerID;
        $params['Active'] 			= $active;
        $params['checksum'] 		= $checksum;
        $params['now']        		= $GLOBALS['db']->Date();
        $params['map_type'] 		= $map_type;
		
		$result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('MAPS_ERROR_MAP_NOT_ADDED'), RESPONSE_ERROR);
            return $result;
        }
        $newid = $GLOBALS['db']->lastInsertID('maps', 'id');

		if (empty($checksum)) {
			// Update checksum
			$config_key = $GLOBALS['app']->Registry->Get('/config/key');		
			$params               	= array();
			$params['id'] 			= $newid;
			$params['checksum'] 	= $newid.':'.$config_key;
			
			$sql = '
				UPDATE [[maps]] SET
					[checksum] = {checksum}
				WHERE [id] = {id}';

			$result = $GLOBALS['db']->query($sql, $params);
			if (Jaws_Error::IsError($result)) {
				$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
				return false;
			}
		}
		
		// Let everyone know it has been added
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onAddMap', $newid);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		
        $GLOBALS['app']->Session->PushLastResponse(_t('MAPS_MAP_CREATED'), RESPONSE_NOTICE);
        return $newid;
    }

    /**
     * Updates a map.
     *
     * @access  public
     * @param   int     $id             The ID of the map to update.
     * @param   string  $title        The new title of the map.
     * @param   string  $description    The description of the map.
     * @param   int  $custom_height    Height of the map (in pixels).
     * @param   string  $active        (Y/N) If the map is published or not
     * @param   string  $map_type        Map type (terrain/street/hybrid)
     * @param   boolean $auto           If it's auto saved or not
     * @return  boolean Success/failure
     */
    function UpdateMap($id, $title, $description, $custom_height, $active, $map_type, $auto = false)
    {
		if (empty($title)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('MAPS_ERROR_INVALID_INVALID'), RESPONSE_ERROR);
			require_once JAWS_PATH . 'include/Jaws/Header.php';
			Jaws_Header::Location(BASE_SCRIPT . '?gadget=Maps&action=form');
		}
		$model = $GLOBALS['app']->LoadGadget('Maps', 'Model');
        $page = $model->GetMap($id);
        if (Jaws_Error::isError($page)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('MAPS_ERROR_MAP_NOT_FOUND'), RESPONSE_ERROR);
            return $page;
        }

        $sql = '
            UPDATE [[maps]] SET
				[title] = {title}, 
				[description] = {description}, 
				[custom_height] = {custom_height}, 
				[active] = {Active}, 
				[updated] = {now},
				[map_type] = {map_type}
			WHERE [id] = {id}';

        $params               		= array();
        $params['id']         		= (int)$id;
        $params['title'] 			= $title;
        $params['description']   	= str_replace("\r\n", "\n", $description);
        $params['custom_height'] 	= (int)$custom_height;
        $params['Active'] 			= $active;
        $params['now']        		= $GLOBALS['db']->Date();		
        $params['map_type']   		= $map_type;

        $result = $GLOBALS['db']->query($sql, $params);
        if (Jaws_Error::IsError($result)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('MAPS_ERROR_MAP_NOT_UPDATED'), RESPONSE_ERROR);
            return $result;
        }
				
		// Let everyone know
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onUpdateMap', $id);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		
		if ($auto) {
            $GLOBALS['app']->Session->PushLastResponse(_t('MAPS_MAP_AUTOUPDATED',
                                                     date('H:i:s'),
                                                     (int)$id,
                                                     date('D, d')),
                                                  RESPONSE_NOTICE);
        } else {
            $GLOBALS['app']->Session->PushLastResponse(_t('MAPS_MAP_UPDATED'), RESPONSE_NOTICE);
        }
        return true;
    }

	/**
     * Delete a map
     *
     * @param 	int 	$id 	Map ID
     * @access  public
     * @return  boolean True if query was successful and Jaws_Error on error
     */
    function DeleteMap($id)
    {
		$model = $GLOBALS['app']->LoadGadget('Maps', 'Model');
		$parent = $model->GetMap((int)$id);
		if (Jaws_Error::IsError($parent)) {
			$GLOBALS['app']->Session->PushLastResponse(_t('MAPS_ERROR_MAP_NOT_DELETED'), RESPONSE_ERROR);
			return new Jaws_Error(_t('MAPS_ERROR_MAP_NOT_DELETED'), _t('MAPS_NAME'));
		}

		if(!isset($parent['id'])) {
			$GLOBALS['app']->Session->PushLastResponse(_t('MAPS_ERROR_MAP_NOT_DELETED'), RESPONSE_ERROR);
			return new Jaws_Error(_t('MAPS_ERROR_MAP_NOT_DELETED'), _t('MAPS_NAME'));
		} else {
			// Let everyone know
			$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
			$res = $GLOBALS['app']->Shouter->Shout('onDeleteMap', $id);
			if (Jaws_Error::IsError($res) || !$res) {
				return $res;
			}
			
			$rids = $model->GetAllPostsOfMap($parent['id']);
			if (Jaws_Error::IsError($rids)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('MAPS_ERROR_MAP_NOT_DELETED'), RESPONSE_ERROR);
				return new Jaws_Error(_t('MAPS_ERROR_MAP_NOT_DELETED'), _t('MAPS_NAME'));
			}

			foreach ($rids as $rid) {
				if (!$this->DeletePost($rid['id'], true)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('MAPS_ERROR_POST_NOT_DELETED'), RESPONSE_ERROR);
					return new Jaws_Error(_t('MAPS_ERROR_POST_NOT_DELETED'), _t('MAPS_NAME'));
				}
			}
		
			$sql = 'DELETE FROM [[maps]] WHERE [id] = {id}';
			$res = $GLOBALS['db']->query($sql, array('id' => $id));
			if (Jaws_Error::IsError($res)) {
				$GLOBALS['app']->Session->PushLastResponse(_t('MAPS_ERROR_MAP_NOT_DELETED'), RESPONSE_ERROR);
				return new Jaws_Error(_t('MAPS_ERROR_MAP_NOT_DELETED'), _t('MAPS_NAME'));
			}
			
			// delete menu item for page
			$url = $GLOBALS['app']->Map->GetURLFor('Maps', 'Map', array('id' => $parent['id']));

			$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
			$res = $GLOBALS['app']->Shouter->Shout('onDeleteMenuItem', $url);
			if (Jaws_Error::IsError($res) || !$res) {
				return $res;
			}

		}

		$GLOBALS['app']->Session->PushLastResponse(_t('MAPS_MAP_DELETED'), RESPONSE_NOTICE);
        return true;
    }

    /**
     * Deletes a group of maps
     *
     * @access  public
     * @param   array   $maps  Array with the IDs of maps
     * @return  bool    Success/failure
     */
    function MassiveDelete($maps)
    {
        if (!is_array($maps)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('MAPS_ERROR_MAP_NOT_MASSIVE_DELETED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('MAPS_ERROR_MAP_NOT_MASSIVE_DELETED'), _t('MAPS_NAME'));
        }

        foreach ($maps as $page) {
            $res = $this->DeleteMap($page);
            if (Jaws_Error::IsError($res)) {
                $GLOBALS['app']->Session->PushLastResponse(_t('MAPS_ERROR_MAP_NOT_MASSIVE_DELETED'), RESPONSE_ERROR);
                return new Jaws_Error(_t('MAPS_ERROR_MAP_NOT_MASSIVE_DELETED'), _t('MAPS_NAME'));
            }
        }
        $GLOBALS['app']->Session->PushLastResponse(_t('MAPS_MAP_MASSIVE_DELETED'), RESPONSE_NOTICE);
        return true;
    }

    /**
     * Create custom markers (location pins) on Maps.
     *
     * @category 	feature
     * @param   integer  $sort_order 	The priority order
     * @param   integer  $LinkID 	ID of the map this marker belongs to.
     * @param   string  $title      		The title of the marker.
     * @param   string  $description    	The contents of the marker.
     * @param   string  $sm_description  The summary of the marker.
     * @param   string  $image   		An image to accompany the marker.
     * @param   string  $address   		Physical address of marker
     * @param   string  $city   		City of marker
     * @param   string  $region   		State/province/region of marker
     * @param   integer  $country_id   		Country ID of marker (country DB table)
     * @param   integer  $prop_id   		Property ID (properties DB table)
     * @param   string $active  		(Y/N) If the marker is published or not
     * @param   integer $OwnerID  		The poster's user ID
     * @param   string $checksum 	Unique ID
     * @access  public
     * @return  mixed 	ID of new marker or Jaws_Error on failure
     */
    function AddPost($sort_order, $LinkID, 
		$title, $description, $sm_description, $image, 
		$address, $city, $region, 
		$country_id = 1, $prop_id, $marker_font_size, $marker_font_color, 
		$marker_subfont_size, $marker_border_width, $marker_border_color, 
		$marker_radius, $marker_foreground, $marker_hover_font_color, 
		$marker_hover_foreground, $marker_hover_border_color, $active, $OwnerID,
		$marker_url, $marker_url_target = 'infowindow', $internal_marker_url, $checksum = '')
    {

		if ($marker_url_target == 'infowindow') {
			$marker_url = '';
		} else if ($marker_url_target == '_self' && !empty($internal_marker_url)) {
			$marker_url = $internal_marker_url;
		}
		
		$OwnerID = (!is_null($OwnerID)) ? (int)$OwnerID : 0;
		
		if (!empty($image)) {
			$image = $this->cleanImagePath($image);
			if (
				$OwnerID > 0 && 
				(substr(strtolower(trim($image)), 0, 4) == 'http' || 
				substr(strtolower(trim($image)), 0, 2) == '//' || 
				substr(strtolower(trim($image)), 0, 2) == '\\\\')
			) {
				$image = '';
			}
		}
		
		$sql = "
            INSERT INTO [[maps_locations]]
                ([sort_order], [linkid], [title], 
				[description], [image], [sm_description], 
				[address], [city], [region], [country_id], [prop_id], 
				[marker_font_size], [marker_font_color], 
				[marker_subfont_size], [marker_border_width], [marker_border_color], 
				[marker_radius], [marker_foreground], [marker_hover_font_color], 
				[marker_hover_foreground], [marker_hover_border_color], 
				[active], [ownerid], [created], [updated],
				[marker_url], [marker_url_target], [checksum])
            VALUES
                ({sort_order}, {LinkID}, {title}, 
				{description}, {image}, {sm_description}, 
				{address}, {city}, {region}, {country_id}, {prop_id}, 
				{marker_font_size}, {marker_font_color}, 
				{marker_subfont_size}, {marker_border_width}, {marker_border_color}, 
				{marker_radius}, {marker_foreground}, {marker_hover_font_color}, 
				{marker_hover_foreground}, {marker_hover_border_color}, 
				{Active}, {OwnerID}, {now}, {now},
				{marker_url}, {marker_url_target}, {checksum})";

        $params               					= array();
        $params['sort_order']       			= (int)$sort_order;
        $params['title'] 						= $title;
		$params['description']   				= str_replace("\r\n", "\n", $description);
		$params['sm_description']   			= $sm_description;
		$params['image'] 						= $image;
        $params['address'] 						= $address;
		$params['LinkID']         				= (int)$LinkID;
		$params['OwnerID']         				= $OwnerID;
		$params['marker_font_size']				= (int)$marker_font_size;
		$params['marker_font_color']			= $marker_font_color; 	
		$params['marker_subfont_size']			= (int)$marker_subfont_size;
		$params['marker_border_width']			= (int)$marker_border_width;
		$params['marker_border_color']			= $marker_border_color;
		$params['marker_radius']				= (int)$marker_radius;
		$params['marker_foreground']			= $marker_foreground;
		$params['marker_hover_font_color']		= $marker_hover_font_color; 
		$params['marker_hover_foreground']		= $marker_hover_foreground;
		$params['marker_hover_border_color']	= $marker_hover_border_color;
        $params['Active'] 						= $active;
        $params['city'] 						= $city;
        $params['region'] 						= $region;
        $params['country_id'] 					= (int)$country_id;
        $params['prop_id'] 						= !empty($prop_id) ? (int)$prop_id : null;
        $params['now']        					= $GLOBALS['db']->Date();
		$params['marker_url']					= $marker_url;
		$params['marker_url_target']			= $marker_url_target;
		$params['checksum']						= $checksum;

        $result = $GLOBALS['db']->query($sql, $params);
		if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('MAPS_ERROR_POST_NOT_ADDED'), _t('MAPS_NAME'));
        }
        $newid = $GLOBALS['db']->lastInsertID('maps_locations', 'id');

		if (empty($checksum)) {
			// Update checksum
			$config_key = $GLOBALS['app']->Registry->Get('/config/key');		
			$params               	= array();
			$params['id'] 			= $newid;
			$params['checksum'] 	= $newid.':'.$config_key;
			
			$sql = '
				UPDATE [[maps_locations]] SET
					[checksum] = {checksum}
				WHERE [id] = {id}';

			$result = $GLOBALS['db']->query($sql, $params);
			if (Jaws_Error::IsError($result)) {
				$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
				return false;
			}
		}
		
		$GLOBALS['app']->RebuildJawsCache(false);
		
		// Let everyone know it has been added
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onAddMapPost', $newid);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		
        $GLOBALS['app']->Session->PushLastResponse(_t('MAPS_POST_CREATED'), RESPONSE_NOTICE);
        return $newid;
    }

    /**
     * Updates a marker.
     *
     * @param   integer  $id 	The priority order
     * @param   integer  $sort_order 	The priority order
     * @param   string  $title      		The title of the marker.
     * @param   string  $description    	The contents of the marker.
     * @param   string  $sm_description  The summary of the marker.
     * @param   string  $image   		An image to accompany the marker.
     * @param   string  $address   		Physical address of marker
     * @param   string  $city   		City of marker
     * @param   string  $region   		State/province/region of marker
     * @param   integer  $country_id   		Country ID of marker (country DB table)
     * @param   integer  $prop_id   		Property ID (properties DB table)
     * @param   string 	$active  		(Y/N) If the marker is published or not
     * @access  public
     * @return  boolean    Success/failure (Jaws_Error)
     */
    function UpdatePost($id, $sort_order, 
		$title, $description, $sm_description, $image, 
		$address, $city, $region, 
		$country_id, $prop_id, $marker_font_size, $marker_font_color, 
		$marker_subfont_size, $marker_border_width, $marker_border_color, 
		$marker_radius, $marker_foreground, $marker_hover_font_color, 
		$marker_hover_foreground, $marker_hover_border_color, $active,
		$marker_url, $marker_url_target, $internal_marker_url)
	{

		if ($marker_url_target == 'infowindow') {
			$marker_url = '';
		} else if ($marker_url_target == '_self' && !empty($internal_marker_url)) {
			$marker_url = $internal_marker_url;
		}

		$model = $GLOBALS['app']->LoadGadget('Maps', 'Model');
        $page = $model->GetPost($id);
        if (Jaws_Error::isError($page)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('MAPS_ERROR_POST_NOT_FOUND'), RESPONSE_ERROR);
            return new Jaws_Error(_t('MAPS_ERROR_POST_NOT_FOUND'), _t('MAPS_NAME'));
        }

		if (!empty($image)) {
			$image = $this->cleanImagePath($image);
			if (
				$page['ownerid'] > 0 && 
				(substr(strtolower(trim($image)), 0, 4) == 'http' || 
				substr(strtolower(trim($image)), 0, 2) == '//' || 
				substr(strtolower(trim($image)), 0, 2) == '\\\\')
			) {
				$image = '';
			}
		}
		
        $sql = '
            UPDATE [[maps_locations]] SET
				[sort_order] = {sort_order}, 
				[title] = {title}, 
				[description] = {description}, 
				[sm_description] = {sm_description}, 
				[image] = {image}, 
				[address] = {address}, 
				[city] = {city}, 
				[region] = {region}, 
				[country_id] = {country_id}, 
				[prop_id] = {prop_id}, 
				[marker_font_size]		= {marker_font_size}, 
				[marker_font_color]	= {marker_font_color},  	
				[marker_subfont_size]	= {marker_subfont_size}, 
				[marker_border_width]	= {marker_border_width}, 
				[marker_border_color]	= {marker_border_color}, 
				[marker_radius]		= {marker_radius}, 
				[marker_foreground]	= {marker_foreground}, 
				[marker_hover_font_color]	= {marker_hover_font_color},  
				[marker_hover_foreground]	= {marker_hover_foreground}, 
				[marker_hover_border_color]	= {marker_hover_border_color}, 
				[active] = {Active}, 
				[updated] = {now},
				[marker_url]	= {marker_url}, 
				[marker_url_target]	= {marker_url_target} 
			WHERE [id] = {id}';
		
        $params               					= array();
        $params['id']         					= (int)$id;
        $params['sort_order'] 					= (int)$sort_order;
        $params['title'] 						= $title;
		$params['description']  				= str_replace("\r\n", "\n", $description);
        $params['sm_description']				= $sm_description;
        $params['image'] 						= $image;
        $params['address'] 						= $address;
        $params['city'] 						= $city;
        $params['region'] 						= $region;
        $params['country_id'] 					= $country_id;
        $params['prop_id'] 						= $prop_id;
		$params['marker_font_size']				= (int)$marker_font_size;
		$params['marker_font_color']			= $marker_font_color; 	
		$params['marker_subfont_size']			= (int)$marker_subfont_size;
		$params['marker_border_width']			= (int)$marker_border_width;
		$params['marker_border_color']			= $marker_border_color;
		$params['marker_radius']				= (int)$marker_radius;
		$params['marker_foreground']			= $marker_foreground;
		$params['marker_hover_font_color']		= $marker_hover_font_color; 
		$params['marker_hover_foreground']		= $marker_hover_foreground;
		$params['marker_hover_border_color']	= $marker_hover_border_color;
		$params['marker_url']					= $marker_url;
		$params['marker_url_target']			= $marker_url_target;
        $params['Active'] 						= $active;
        $params['now']        					= $GLOBALS['db']->Date();

        $result = $GLOBALS['db']->query($sql, $params);
		if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('MAPS_ERROR_POST_NOT_UPDATED'), _t('MAPS_NAME'));
        }

		$GLOBALS['app']->RebuildJawsCache(false);
		
		// Let everyone know
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onUpdateMapPost', $id);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		
        if ($auto) {
            $GLOBALS['app']->Session->PushLastResponse(_t('MAPS_POST_AUTOUPDATED',
                                                     date('H:i:s'),
                                                     (int)$id,
                                                     date('D, d')),
                                                  RESPONSE_NOTICE);
        } else {
            $GLOBALS['app']->Session->PushLastResponse(_t('MAPS_POST_UPDATED'), RESPONSE_NOTICE);
        }
        return true;
    }


    /**
     * Deletes a marker
     *
     * @access  public
     * @param   int     $id     The ID of the marker to delete.
     * @param   boolean     $massive     Is this part of a massive delete?
     * @return  boolean    Success/failure
     */
    function DeletePost($id, $massive = false)
    {
		// Let everyone know
		$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
		$res = $GLOBALS['app']->Shouter->Shout('onDeleteMapPost', $id);
		if (Jaws_Error::IsError($res) || !$res) {
			return $res;
		}
		
        $sql = 'DELETE FROM [[maps_locations]] WHERE [id] = {id}';
        $result = $GLOBALS['db']->query($sql, array('id' => $id));
        if (Jaws_Error::IsError($result)) {
            $GLOBALS['app']->Session->PushLastResponse(_t('MAPS_ERROR_POST_NOT_DELETED'), RESPONSE_ERROR);
            return new Jaws_Error(_t('MAPS_ERROR_POST_NOT_DELETED'), _t('MAPS_NAME'));
        }

		$GLOBALS['app']->RebuildJawsCache(false);
        
		if ($massive === false) {
			$GLOBALS['app']->Session->PushLastResponse(_t('MAPS_POST_DELETED'), RESPONSE_NOTICE);
        }
		return true;
    }

    /**
     * Re-sorts posts
     *
     * @access  public
     * @param   int     $pids     ',' separated values of IDs of the posts
     * @param   string     $newsorts     ',' separated values of new sort_orders
     * @return  bool    Success/failure
     */
    function SortItem($pids, $newsorts)
    {
		//$model = $GLOBALS['app']->LoadGadget('CustomPage', 'Model');
        $ids = explode(',', $pids);
        $sorts = explode(',', $newsorts);
        $i = 0;
		foreach ($ids as $pid) {
			if ((int)$pid != 0) {
				$new_sort = $sorts[$i];
				//$page = $model->GetPost($pid);
		        //if (Jaws_Error::isError($page)) {
		        //    $GLOBALS['app']->Session->PushLastResponse(_t('MAPS_ERROR_POST_NOT_SORTED'), RESPONSE_ERROR);
				//	return false;
		        //} else {
				$params               	= array();
				$params['pid']         	= (int)$pid;
				$params['new_sort'] 	= (int)$new_sort;
				
				$sql = '
					UPDATE [[maps_locations]] SET
						[sort_order] = {new_sort} 
					WHERE [id] = {pid}';

				$result1 = $GLOBALS['db']->query($sql, $params);
				if (Jaws_Error::IsError($result1)) {
					$GLOBALS['app']->Session->PushLastResponse(_t('MAPS_ERROR_POST_NOT_SORTED'), RESPONSE_ERROR);
					//$GLOBALS['app']->Session->PushLastResponse($result1->GetMessage(), RESPONSE_ERROR);
					return false;
				}
				$i++;
			}
		}
		$GLOBALS['app']->RebuildJawsCache(false);
		$GLOBALS['app']->Session->PushLastResponse(_t('MAPS_POST_UPDATED'), RESPONSE_NOTICE);
		return true;
    }

    /**
     * Search for maps that matches a status and/or a keyword
     * in the title or content
     *
     * @param   string  $status  Status of page(s) we want to display
     * @param   string  $search  Keyword (title/description) of pages we want to look for
     * @param   int     $offSet  Data limit
     * @param   int     $OwnerID  User's ID
     * @access  public
     * @return  array   Array of matches
     */
    function SearchMaps($status, $search, $offSet = null, $OwnerID = null)
    {
        $params = array();


        $sql = '
            SELECT [id], [title], [description], [custom_height], [ownerid] 
				[active], [created], [updated], [map_type], [checksum]
            FROM [[maps]]
			WHERE ([title] <> ""';

        if (trim($status) != '') {
            $sql .= ' AND [active] = {status}';
			$params['status'] = $status;
        }
        $sql .= ')';
        
		if (!is_null($OwnerID)) {
			$sql .= ' AND [ownerid] = {OwnerID}';
			$params['OwnerID'] = $OwnerID;
		}
		
		if (trim($search) != '') {
            $searchdata = explode(' ', $search);
            /**
             * This query needs more work, not use $v straight, should be
             * like rest of the param stuff.
             */
            $i = 0;
            foreach ($searchdata as $v) {
                $v = trim($v);
                $sql .= " AND ([title] LIKE {textLike_".$i."} OR [description] LIKE {textLike_".$i."} OR [map_type] LIKE {textLike_".$i."})";
                $params['textLike_'.$i] = '%'.$v.'%';
                $i++;
            }
        }


        if (is_numeric($offSet)) {
            $limit = 10;
            $result = $GLOBALS['db']->setLimit(10, $offSet);
            if (Jaws_Error::IsError($result)) {
                return new Jaws_Error(_t('MAPS_ERROR_MAPS_NOT_RETRIEVED'), _t('MAPS_NAME'));
            }
        }

        $sql.= ' ORDER BY [id] ASC';

        $types = array(
			'integer', 'text', 
			'text', 'integer', 'integer', 'text', 
			'timestamp', 'timestamp', 'text', 'text'
		);
	    
        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('MAPS_ERROR_MAPS_NOT_RETRIEVED'), _t('MAPS_NAME'));
        }
        //limit, sort, sortDirection, offset..
        return $result;
    }

	/**
     * Searches regions from country DB, or from hosted API
     *
     * @param   float     $long  The longitude to search around
     * @param   float     $lat  The latitude to search around
     * @param   int  $redius  Radius to search within
     * @param   int  $pop  Return regions with population greater than this number
     * @param   int  $limit  Data limit
     * @access  public
     * @return  mixed   Returns an array with the regions and false on error
     * @TODO  Use REST instead of XML-RPC for API call
     */
    function SearchRegionsWithinRadius($long, $lat, $radius = 150, $pop = null, $limit = 100)
    {
		$model = $GLOBALS['app']->LoadGadget('Maps', 'Model');
		$res = array();
		$results = $model->GetRegionsWithinRadius((double)$long, (double)$lat, (int)$radius, (int)$limit, (int)$pop);
		if (Jaws_Error::IsError($results)) {
			return new Jaws_Error($results->GetMessage(), _t('MAPS_NAME'));
		}
		$i = 0;
		foreach ($results as $r) {
			$res[$i]['region'] = $r['region']." (".$r['distance']." miles) (".$r['population']." people)";
			$i++;
		}

		return $res;
	}
	
	/**
     * Searches regions from country DB, or from hosted API
     *
     * @access  public
     * @param   string  $search  The search string
     * @param   int     $pid  The region's parent ID
     * @param   string  $table  The DB table to get child regions from
     * @return  mixed   Returns an array with the regions and false on error
     * @TODO  Use REST instead of XML-RPC for API call
     */
    function SearchRegions($search, $pid = null, $table = null)
    {
		$xss   = $GLOBALS['app']->loadClass('XSS', 'Jaws_XSS');
		$table = trim($table);
		$table = $xss->parse($table);
		
		$params = array();
		$sql = '
			SELECT [region]
			FROM '.(!is_null($table) && !empty($table) ? '`'.$table.'`' : '[[country]]').'
			WHERE ([region] <> "")';
		if (!is_null($pid)) {
			$sql .= " AND ([parent] = {id})";
			$params['id'] = (int)$pid;
		}
		
		if (trim($search) != '') {
			$sql .= " AND ([region] LIKE {textLike})";
			$params['textLike'] = $search.'%';
		}

		$sql.= ' ORDER BY [region] ASC';

		$types = array(
			'text'
		);
		
		$results = $GLOBALS['db']->queryAll($sql, $params, $types);
		if (Jaws_Error::IsError($results)) {
			return new Jaws_Error($results->GetMessage(), _t('MAPS_NAME'));
		}

		return $results;
	}

    /**
     * Saves the value of a key
     *
     * @access  public
     * @param   string  $key   Key name
     * @param   string  $value Key value
     * @return  array   Response
     */
    function SetRegistryKey($key, $value)
    {
        /*
		if (preg_match("#^/(gadgets|plugins\/parse_text)/(.*?)/(.*?)#i", $key, $matches)) {
            $GLOBALS['app']->Registry->LoadFile($matches[2]);
            $GLOBALS['app']->Registry->Set($key, $value);
            $GLOBALS['app']->Registry->Commit($matches[2]);
		}
		*/
		$GLOBALS['app']->Registry->LoadFile('Maps');
		$GLOBALS['app']->Registry->Set($key, $value);
		$GLOBALS['app']->Registry->Commit('Maps');
		if ($GLOBALS['app']->Registry->Get($key) == $value) {
			$GLOBALS['app']->Session->PushLastResponse(_t('MAPS_KEY_SAVED'), RESPONSE_NOTICE);
        } else {
			$GLOBALS['app']->Session->PushLastResponse(_t('MAPS_KEY_NOT_SAVED'), RESPONSE_ERROR);
			return false;
		}
		return true;
    }
		
	/**
     * Inserts checksums for default (insert.xml) content
     *
     * @access  public
     * @param   string  $gadget   Get gadget name from onAfterEnablingGadget shouter call
     * @return  array   Response
     */
    function InsertDefaultChecksums($gadget)
    {
		if ($gadget == 'Maps') {
			// Update checksum
			$config_key = $GLOBALS['app']->Registry->Get('/config/key');		
			
			$model = $GLOBALS['app']->LoadGadget('Maps', 'Model');
			$parents = $model->GetMaps();
			if (Jaws_Error::IsError($parents)) {
				return false;
			}
			foreach ($parents as $parent) {
				if (empty($parent['checksum']) || is_null($parent['checksum']) || strpos($parent['checksum'], ':') === false) {
					$params               	= array();
					$params['id'] 			= $parent['id'];
					$params['checksum'] 	= $parent['id'].':'.$config_key;
					
					$sql = '
						UPDATE [[maps]] SET
							[checksum] = {checksum}
						WHERE [id] = {id}';

					$result = $GLOBALS['db']->query($sql, $params);
					if (Jaws_Error::IsError($result)) {
						$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
						return false;
					}

					/*
					// Let everyone know it has been added
					$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
					$res = $GLOBALS['app']->Shouter->Shout('onAddMap', $parent['id']);
					if (Jaws_Error::IsError($res) || !$res) {
						return $res;
					}
					*/
				}
				$posts = $model->GetAllPostsOfMap($parent['id']);
				if (Jaws_Error::IsError($posts)) {
					return false;
				}
				foreach ($posts as $post) {
					if (empty($post['checksum']) || is_null($post['checksum']) || strpos($post['checksum'], ':') === false) {
						$params               	= array();
						$params['id'] 			= $post['id'];
						$params['checksum'] 	= $post['id'].':'.$config_key;
						
						$sql = '
							UPDATE [[maps_locations]] SET
								[checksum] = {checksum}
							WHERE [id] = {id}';

						$result = $GLOBALS['db']->query($sql, $params);
						if (Jaws_Error::IsError($result)) {
							$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
							return false;
						}

						/*
						// Let everyone know it has been added
						$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
						$res = $GLOBALS['app']->Shouter->Shout('onAddMapPost', $post['id']);
						if (Jaws_Error::IsError($res) || !$res) {
							return $res;
						}
						*/
					}
				}					
			}
			
			// Get all possible region parents
			for ($p = 0; $p < 400000; $p++) {
				$sql = "
					SELECT [id], [parent], [checksum] FROM [[country]] WHERE [checksum] IS NULL OR [checksum] = ''";

				$parents = $GLOBALS['db']->queryRow($sql);
				if (Jaws_Error::IsError($parents)) {
					$GLOBALS['app']->Session->PushLastResponse($parents->GetMessage(), RESPONSE_ERROR);
					return false;
				}
				if (!isset($parents['id']) || empty($parents['id'])) {
					break;
				} else {
				//if (empty($parents['checksum']) || is_null($parents['checksum']) || strpos($parents['checksum'], ':') === false) {
					$params               	= array();
					$params['id'] 			= $parents['id'];
					$params['checksum'] 	= $parents['id'].':'.$config_key;
					
					$sql = '
						UPDATE [[country]] SET
							[checksum] = {checksum}
						WHERE [id] = {id}';

					$result = $GLOBALS['db']->query($sql, $params);
					if (Jaws_Error::IsError($result)) {
						$GLOBALS['app']->Session->PushLastResponse($result->GetMessage(), RESPONSE_ERROR);
						return false;
					}
					echo '<br />'.var_export($result, true);
					// tag after text for Safari & Firefox
					// 8 char minimum for Firefox
					ob_flush();
					flush();  // worked without ob_flush() for me

					/*
					// Let everyone know it has been added
					$GLOBALS['app']->loadClass('Shouter', 'Jaws_EventShouter');
					$res = $GLOBALS['app']->Shouter->Shout('onAddMap', $parents['id']);
					if (Jaws_Error::IsError($res) || !$res) {
						return $res;
					}
					*/
				}
			}
		}
		return true;
    }
	
}