<?php
/**
 * Maps Gadget
 *
 * @category   GadgetModel
 * @package    Maps
 * @author     Alan Valkoun	 <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class MapsModel extends Jaws_Model
{
    var $_Name = 'Maps';

    /**
     * Gets a single page by ID.
     *
     * @access  public
     * @param   int     $id     The ID of the page to get.
     * @return  array   An array containing the page information, or false if no page could be loaded.
     */
    function GetMap($id)
    {
		$sql = '
            SELECT [id], [title], [description], [custom_height],  
				[ownerid], [active], [created], [updated], [map_type], [checksum]
            FROM [[maps]] WHERE [id] = {id}';

        $types = array(
			'integer', 'text', 'text', 'integer', 'integer', 'text', 
			'timestamp', 'timestamp', 'text', 'text'
		);

        $params             = array();
        $params['id']       = $id;

        $row = $GLOBALS['db']->queryRow($sql, $params, $types);
        if (Jaws_Error::IsError($row)) {
            return new Jaws_Error(_t('MAPS_ERROR_MAP_NOT_FOUND'), _t('MAPS_NAME'));
        }
		
        if (isset($row['id'])) {
            return $row;
        }

        return new Jaws_Error(_t('MAPS_ERROR_MAP_NOT_FOUND'), _t('MAPS_NAME'));
    }
    
    /**
     * Gets an index of all the maps.
     *
     * @access  public
     * @param   int     $limit      The number of pages to return. Set to Null to return all pages.
     * @param   int     $sortType   One of the PAGES_SORT_* constants to set the sort field.
     * @param   int     $sortDir    Either PAGES_ASC or PAGES_DESC to set the sort direction.
     *
     * @return  array   An array containing the page information.
     */
    function GetMaps($limit = null, $sortColumn = 'title', $sortDir = 'ASC', $offSet = false, $OwnerID = null)
    {
        $fields     = array('ownerid', 'title', 'created', 'updated', 'active', 'map_type');
        $sortColumn = strtolower($sortColumn);
        if (!in_array($sortColumn, $fields)) {
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_WARNING, _t('MAPS_ERROR_UNKNOWN_COLUMN'));
            }
            $sortColumn = 'title';
        }

        $sortDir = strtoupper($sortDir);
        if ($sortDir == 'DESC') {
            $sortDir = 'DESC';
        } else {
            $sortDir = 'ASC';
        }

        $sql = "
            SELECT [id], [title], [description], [custom_height], 
				[ownerid], [active], [created], [updated], [map_type], [checksum]
            FROM [[maps]]
			";
		$params              = array();
		$params['owner_id'] = $GLOBALS['app']->Session->GetAttribute('user_id');

		if (!is_null($OwnerID)) {
			$sql .= " WHERE [ownerid] = {owner_id}";
		}
		
		$sql .= " ORDER BY [$sortColumn] $sortDir";

        if (is_numeric($offSet)) {
            $limit = is_null($limit) ? 10 : $limit;
            if (!is_null($limit)) {
                $result = $GLOBALS['db']->setLimit(10, $offSet);
                if (Jaws_Error::IsError($result)) {
                    return new Jaws_Error(_t('MAPS_ERROR_MAPS_NOT_RETRIEVED'), _t('MAPS_NAME'));
                }
            }
        } else {
            if (!is_null($limit)) {
                $result = $GLOBALS['db']->setLimit($limit);
                if (Jaws_Error::IsError($result)) {
                    return new Jaws_Error(_t('MAPS_ERROR_MAPS_NOT_RETRIEVED'), _t('MAPS_NAME'));
                }
            }
        }

        $types = array(
			'integer', 'text', 'text', 'integer', 'integer', 'text', 
			'timestamp', 'timestamp', 'text', 'text'
		);

        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('MAPS_ERROR_MAPS_NOT_RETRIEVED'), _t('MAPS_NAME'));
        }

        return $result;
    }
    
    /**
     * Gets a single post by ID.
     *
     * @access  public
     * @param   int     $id     The ID of the post to get.
     * @return  array   An array containing the post information, or false if no page could be loaded.
     */
    function GetPost($id)
    {		
		$sql = '
            SELECT [id], [sort_order], [linkid], [title], 
				[description], [image], [sm_description], 
				[address], [city], [region], [country_id], [prop_id], 
				[marker_font_size], [marker_font_color], 
				[marker_subfont_size], [marker_border_width], [marker_border_color], 
				[marker_radius], [marker_foreground], [marker_hover_font_color], 
				[marker_hover_foreground], [marker_hover_border_color], 
				[active], [ownerid], [created], [updated],
				[marker_url], [marker_url_target], [checksum]
			FROM [[maps_locations]] WHERE [id] = {id}';

        $types = array(
			'integer', 'integer', 'integer', 'text', 
			'text', 'text', 'text', 'text', 'text', 'text', 
			'integer', 'integer', 'integer', 'text', 'integer', 
			'integer', 'text', 'integer', 'text', 'text', 'text', 'text', 
			'text', 'integer','timestamp', 'timestamp', 'text', 
			'text', 'text'
		);

        $params             = array();
        $params['id']       = $id;

        $row = $GLOBALS['db']->queryRow($sql, $params, $types);
        if (Jaws_Error::IsError($row)) {
            return new Jaws_Error(_t('MAPS_ERROR_POST_NOT_FOUND'), _t('MAPS_NAME'));
        }

        if (isset($row['id'])) {
            return $row;
        }

        return new Jaws_Error(_t('MAPS_ERROR_POST_NOT_FOUND'), _t('MAPS_NAME'));
    }

    /**
     * Returns all posts that belongs to a page
     *
     * @access  public
     * @return  array  Array with all the post IDs or Jaws_Error on error
     */
    function GetAllPostsOfMap($id)
    {
	    $sql  = '
            SELECT [id], [sort_order], [linkid], [title], 
				[description], [image], [sm_description], 
				[address], [city], [region], [country_id], [prop_id], 
				[marker_font_size], [marker_font_color], 
				[marker_subfont_size], [marker_border_width], [marker_border_color], 
				[marker_radius], [marker_foreground], [marker_hover_font_color], 
				[marker_hover_foreground], [marker_hover_border_color], 
				[active], [ownerid], [created], [updated],
				[marker_url], [marker_url_target], [checksum]				
			FROM [[maps_locations]] WHERE [linkid] = {id}
			ORDER BY [sort_order] ASC, [title] ASC';

        $types = array(
			'integer', 'integer', 'integer', 'text', 
			'text', 'text', 'text', 'text', 'text', 'text', 
			'integer', 'integer', 'integer', 'text', 'integer', 
			'integer', 'text', 'integer', 'text', 'text', 'text', 'text', 
			'text', 'integer','timestamp', 'timestamp', 'text', 
			'text', 'text'
		);

		$result = $GLOBALS['db']->queryAll($sql, array('id' => $id), $types);
		
        if (Jaws_Error::IsError($result)) {
            //add language word for this
            return new Jaws_Error(_t('MAPS_ERROR_POST_NOT_FOUND'), _t('MAPS_NAME'));
        }

        return $result;
    }

    /**
     * Gets the users maps by ID
     *
     * @access  public
     * @param   int     $id  The user ID
     * @return  mixed   Returns an array with the maps and false on error
     */
    function GetMapsByUserID($id)
    {
		$params       = array();
        $params['id'] = $id;
		
		$sql = '
            SELECT [id], [title], [description], [custom_height],  
				[ownerid], [active], [created], [updated], [map_type], [checksum]
			FROM [[maps]]
            WHERE ([ownerid] = {id})';
		
        $types = array(
			'integer', 'text', 'text', 'integer', 'integer', 
			'text', 'timestamp', 'timestamp', 'text', 'text'
		);

        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('MAPS_ERROR_MAPS_NOT_RETRIEVED'), _t('MAPS_NAME'));
        }

        return $result;
    }

    /**
     * Gets a user map by ID
     *
     * @access  public
     * @param   int     $id  The user ID
     * @return  mixed   Returns an array with the maps and false on error
     */
    function GetSingleMapByUserID($id, $cid)
    {
		$params       = array();
        $params['id'] = $id;
        $params['cid'] = $cid;
		
		$sql = '
            SELECT [id], [title], [description], [custom_height], 
				[ownerid], [active], [created], [updated], [map_type], [checksum]
			FROM [[maps]]
            WHERE ([ownerid] = {id} AND [id] = {cid})';
		
        $types = array(
			'integer', 'text', 'text', 'integer', 'integer', 
			'text', 'timestamp', 'timestamp', 'text', 'text'
		);

        $result = $GLOBALS['db']->queryRow($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('MAPS_ERROR_MAPS_NOT_RETRIEVED'), _t('MAPS_NAME'));
        }

        return $result;
    }
    
	/**
     * Gets a region from country DB
     *
     * @access  public
     * @param   int     $id  The region ID
     * @return  mixed   Returns an array with the region row and false on error
     */
    function GetRegion($id)
    {
		$params       = array();
        $params['id'] = $id;
		
		$sql = '
            SELECT [id], [parent], [region], [ownerid], [is_country], 
			[country_iso_code], [latitude], [longitude], [population], [checksum]
			FROM [[country]]';
			
		if (is_numeric($id)) {
			$sql .= 'WHERE [id] = {id}';
		} else {
			$sql .= 'WHERE [region] = {id}';
		}
		
        $types = array(
			'integer', 'integer', 'text', 'integer', 'text', 
			'text', 'float', 'float', 'integer', 'text'
		);

        $result = $GLOBALS['db']->queryRow($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('MAPS_ERROR_REGIONS_NOT_RETRIEVED'), _t('MAPS_NAME'));
		}

        return $result;
    }

	/**
     * Gets a region from country DB
     *
     * @access  public
     * @param   int     $id  The region ID
     * @return  mixed   Returns an array with the region row and false on error
     */
    function GetRegionByChecksum($checksum)
    {
		$params       = array();
        $params['checksum'] = $checksum;
		
		$sql = '
            SELECT [id], [parent], [region], [ownerid], [is_country], 
			[country_iso_code], [latitude], [longitude], [population], [checksum]
			FROM [[country]]
			WHERE [checksum] = {checksum}';
		
        $types = array(
			'integer', 'integer', 'text', 'integer', 'text', 
			'text', 'float', 'float', 'integer', 'text'
		);

        $result = $GLOBALS['db']->queryRow($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('MAPS_ERROR_REGIONS_NOT_RETRIEVED'), _t('MAPS_NAME'));
		}

        return $result;
    }

	/**
     * Gets sub-regions of a region from country DB
     *
     * @access  public
     * @param   int     $id  The region ID
     * @return  mixed   Returns an array with the regions and false on error
     */
    function GetRegionsOfParent($id = 0)
    {
		$params       = array();
		
			$params['id'] = (int)$id;
			$sql = '
				SELECT [id], [parent], [region], [ownerid], [is_country], 
				[country_iso_code], [latitude], [longitude], [population], [checksum]
				FROM [[country]] WHERE [parent] = {id}';
			
			$types = array(
				'integer', 'integer', 'text', 'integer', 'text', 
				'text', 'float', 'float', 'integer', 'text'
			);

			$result = $GLOBALS['db']->queryAll($sql, $params, $types);
			if (Jaws_Error::IsError($result)) {
				return new Jaws_Error(_t('MAPS_ERROR_REGIONS_NOT_RETRIEVED'), _t('MAPS_NAME'));
			}

        return $result;
    }

	/**
     * Gets regions within a given radius (in miles) of a region from country DB
     *
     * @access  public
     * @param   int     $id  The region ID
     * @param   int     $radius  The radius (in miles) to search within 
     * @return  mixed   Returns an array with the regions and false on error
     */
    function GetRegionsWithinRadius($long, $lat, $radius = 150, $limit = 100, $pop = null)
    {
		$params       		= array();
		$params['long'] 	= $long;
		$params['lat'] 		= $lat;
		$params['radius'] 	= $radius;
		$params['zero'] 	= 0;

		$sql = "SELECT [id], [parent], [region], [ownerid], [is_country], 
			[country_iso_code], [latitude], [longitude], 
			(3959 * acos(cos(radians({lat})) * cos(radians([latitude])) * cos(radians([longitude]) - radians({long})) + sin(radians({lat})) * sin(radians([latitude]))))
			AS distance, [population], [checksum]
		FROM `country` 
		WHERE ([is_country] = 'N') AND ([latitude] != {zero}) AND ([longitude] != {zero})";
		if (!is_null($pop) && (int)$pop > 0) {
			$params['pop'] = (int)$pop;
			$sql .= ' AND ([population] > {pop})';
		}
		$sql .= " 
		HAVING distance < {radius} 
		ORDER BY distance";

		if (!is_null($limit)) {
			$results = $GLOBALS['db']->setLimit($limit);
			if (Jaws_Error::IsError($results)) {
				return new Jaws_Error(_t('MAPS_ERROR_REGIONS_NOT_RETRIEVED'), _t('MAPS_NAME'));
			}
		}
		
		$types = array(
			'integer', 'integer', 'text', 'integer', 'text', 
			'text', 'float', 'float', 'integer', 'integer', 'text'
		);

		$results = $GLOBALS['db']->queryAll($sql, $params, $types);
		if (Jaws_Error::IsError($results)) {
			return new Jaws_Error(_t('MAPS_ERROR_REGIONS_NOT_RETRIEVED'), _t('MAPS_NAME'));
		}
		return $results;
		
		//return true;
	}
	
}
