<?php
/**
 * Properties Gadget
 *
 * @category   GadgetModel
 * @package    Properties
 * @author     Alan Valkoun	 <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */
class PropertiesModel extends Jaws_Model
{
    var $_Name = 'Properties';

    /**
     * Gets a single page by ID.
     *
     * @access  public
     * @param   int     $id     The ID of the page to get.
     * @return  array   An array containing the page information, or false if no page could be loaded.
     */
    function GetPropertyParent($id)
    {
        $params             = array();
		$sql = '
            SELECT [propertyparentid], [propertyparentparent], [propertyparentsort_order], [propertyparentcategory_name], 
				[propertyparentimage], [propertyparentdescription], [propertyparentactive], 
				[propertyparentownerid], [propertyparentcreated], [propertyparentupdated], 
				[propertyparentfeatured], [propertyparentfast_url], [propertyparentrss_url], [propertyparentregionid],
				[propertyparentrss_overridecity], [propertyparentrandomize], [propertyparentchecksum]
            FROM [[propertyparent]]';

		if (is_numeric($id)) {
			$sql .= ' WHERE [propertyparentid] = {id}';
			 $params['id']       = (int)$id;
		} else {
			$sql .= ' WHERE [propertyparentfast_url] = {id}';
			 $params['id']       = $id;
		}

        $types = array(
			'integer', 'integer', 'integer', 'text', 'text', 'text', 
			'text', 'integer', 'timestamp', 'timestamp', 
			'text', 'text', 'text', 'integer', 'text', 'text', 'text'
		);

        //$params['language'] = $language;

        $row = $GLOBALS['db']->queryRow($sql, $params, $types);
        if (Jaws_Error::IsError($row)) {
            return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTYPARENT_NOT_FOUND'), _t('PROPERTIES_NAME'));
        }
		
        if (isset($row['propertyparentid'])) {
            return $row;
        }

        return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTYPARENT_NOT_FOUND'), _t('PROPERTIES_NAME'));
    }
    
    /**
     * Gets a user property by ID
     *
     * @access  public
     * @param   int     $id  The user ID
     * @return  mixed   Returns an array with the maps and false on error
     */
    function GetSinglePropertyParentByUserID($id, $cid)
    {
		$params       = array();
        $params['id'] = $id;
        $params['cid'] = $cid;
		
		$sql = '
            SELECT [propertyparentid], [propertyparentparent], [propertyparentsort_order], [propertyparentcategory_name], 
				[propertyparentimage], [propertyparentdescription], [propertyparentactive], 
				[propertyparentownerid], [propertyparentcreated], [propertyparentupdated], 
				[propertyparentfeatured], [propertyparentfast_url], [propertyparentrss_url], [propertyparentregionid],
				[propertyparentrss_overridecity], [propertyparentrandomize], [propertyparentchecksum]
			FROM [[propertyparent]]
            WHERE ([propertyparentownerid] = {id} AND [propertyparentid] = {cid})';
		
        $types = array(
			'integer', 'integer', 'integer', 'text', 'text', 'text', 
			'text', 'integer', 'timestamp', 'timestamp', 
			'text', 'text', 'text', 'integer', 'text', 'text', 'text'
		);

        $row = $GLOBALS['db']->queryRow($sql, $params, $types);

        if (isset($row['propertyparentid'])) {
            return $row;
        }

        return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTYPARENT_NOT_FOUND'), _t('PROPERTIES_NAME'));
    }

    /**
     * Gets an index of all the property parents.
     *
     * @access  public
     * @param   int     $limit      The number of pages to return. Set to Null to return all pages.
     * @param   int     $sortType   One of the PAGES_SORT_* constants to set the sort field.
     * @param   int     $sortDir    Either PAGES_ASC or PAGES_DESC to set the sort direction.
     *
     * @return  array   An array containing the page information.
     */
    function GetPropertyParents($limit = null, $sortColumn = 'propertyparentsort_order', $sortDir = 'ASC', $offSet = false, $OwnerID = null)
    {
        $fields     = array('propertyparentsort_order', 'propertyparentownerid', 'propertyparentcategory_name', 'propertyparentcreated', 'propertyparentupdated', 'propertyparentactive');
        $sortColumn = strtolower($sortColumn);
        if (!in_array($sortColumn, $fields)) {
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_WARNING, _t('PROPERTIES_ERROR_UNKNOWN_COLUMN'));
            }
            $sortColumn = 'propertyparentsort_order';
        }

        $sortDir = strtoupper($sortDir);
        if ($sortDir == 'DESC') {
            $sortDir = 'DESC';
        } else {
            $sortDir = 'ASC';
        }

        $sql = "
            SELECT [propertyparentid], [propertyparentparent], [propertyparentsort_order], [propertyparentcategory_name], 
				[propertyparentimage], [propertyparentdescription], [propertyparentactive], 
				[propertyparentownerid], [propertyparentcreated], [propertyparentupdated], 
				[propertyparentfeatured], [propertyparentfast_url], [propertyparentrss_url], [propertyparentregionid],
				[propertyparentrss_overridecity], [propertyparentrandomize], [propertyparentchecksum]
            FROM [[propertyparent]]
			";
		$params              = array();

		if (!is_null($OwnerID)) {
			$params['owner_id'] = $GLOBALS['app']->Session->GetAttribute('user_id');
			$sql .= " WHERE [propertyparentownerid] = {owner_id}";
		} else {
			$sql .= " WHERE [propertyparentownerid] = 0";
		}
		
		$sql .= " ORDER BY [$sortColumn] $sortDir";

        if (is_numeric($offSet)) {
            $limit = is_null($limit) ? 10 : $limit;
			$result = $GLOBALS['db']->setLimit(10, $offSet);
			if (Jaws_Error::IsError($result)) {
				return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTYPARENTS_NOT_RETRIEVED'), _t('PROPERTIES_NAME'));
			}
        } else if (!is_null($limit)) {
			$result = $GLOBALS['db']->setLimit($limit);
			if (Jaws_Error::IsError($result)) {
				return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTYPARENTS_NOT_RETRIEVED'), _t('PROPERTIES_NAME'));
			}
        }

        $types = array(
			'integer', 'integer', 'integer', 'text', 'text', 'text', 
			'text', 'integer', 'timestamp', 'timestamp', 
			'text', 'text', 'text', 'integer', 'text', 'text', 'text'
		);

        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTYPARENTS_NOT_RETRIEVED'), _t('PROPERTIES_NAME'));
        }

        return $result;
    }
    
    /**
     * Returns all propertyparents with the given parent ID
     *
     * @access  public
     * @return  array  Array with all the propertyparent IDs or Jaws_Error on error
     */
    function GetAllSubCategoriesOfParent($id)
    {
	    $sql  = '
            SELECT [propertyparentid], [propertyparentparent], [propertyparentsort_order], [propertyparentcategory_name], 
				[propertyparentimage], [propertyparentdescription], [propertyparentactive], 
				[propertyparentownerid], [propertyparentcreated], [propertyparentupdated], 
				[propertyparentfeatured], [propertyparentfast_url], [propertyparentrss_url], [propertyparentregionid],
				[propertyparentrss_overridecity], [propertyparentrandomize], [propertyparentchecksum]
			FROM [[propertyparent]] WHERE [propertyparentparent] = {id}
			ORDER BY [propertyparentsort_order] ASC, [propertyparentcategory_name] ASC';

        $types = array(
			'integer', 'integer', 'integer', 'text', 'text', 'text', 
			'text', 'integer', 'timestamp', 'timestamp', 
			'text', 'text', 'text', 'integer', 'text', 'text', 'text'
		);

		$result = $GLOBALS['db']->queryAll($sql, array('id' => $id), $types);
		
        if (Jaws_Error::IsError($result)) {
            //add language word for this
            return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTYPARENT_NOT_FOUND'), _t('PROPERTIES_NAME'));
        }

        return $result;
    }

    /**
     * Gets the users property parents by ID
     *
     * @access  public
     * @param   int     $id  The user ID
     * @return  mixed   Returns an array with the maps and false on error
     */
    function GetPropertyParentsByUserID($id)
    {
		$params       = array();
        $params['id'] = $id;
		
		$sql = '
            SELECT [propertyparentid], [propertyparentparent], [propertyparentsort_order], [propertyparentcategory_name], 
				[propertyparentimage], [propertyparentdescription], [propertyparentactive], 
				[propertyparentownerid], [propertyparentcreated], [propertyparentupdated], 
				[propertyparentfeatured], [propertyparentfast_url], [propertyparentrss_url], [propertyparentregionid],
				[propertyparentrss_overridecity], [propertyparentrandomize], [propertyparentchecksum]
			FROM [[propertyparent]]
            WHERE ([propertyparentownerid] = {id})';
		
        $types = array(
			'integer', 'integer', 'integer', 'text', 'text', 'text', 
			'text', 'integer', 'timestamp', 'timestamp', 
			'text', 'text', 'text', 'integer', 'text', 'text', 'text'
		);

        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTYPARENTS_NOT_RETRIEVED'), _t('PROPERTIES_NAME'));
        }

        return $result;
    }

    /**
     * Gets a single property by ID.
     *
     * @access  public
     * @param   int     $id     The ID of the page to get.
     * @return  array   An array containing the page information, or false if no page could be loaded.
     */
    function GetProperty($id)
    {
        $params             = array();

		$sql = '
            SELECT [id], [linkid], [sort_order], [category], [mls], [title], [image], 
				[sm_description], [description], [address], [city], [region], [postal_code], [country_id], 
				[community], [phase], [lotno], [price], [rentdy], [rentwk], [rentmo], [status], [acreage], 
				[sqft], [bedroom], [bathroom], [amenity], [i360], [maxchildno], [maxadultno], [petstay], 
				[occupancy], [maxcleanno], [roomcount], [minstay], [options], [item1], [item2], [item3], 
				[item4], [item5], [premium], [showmap], [featured], [ownerid], [active], [created], [updated], 
				[fast_url], [propertyno], [internal_propertyno], [alink], [alinktitle], [alinktype], [alink2], [alink2title], 
				[alink2type], [alink3], [alink3title], [alink3type], [calendar_link], [year], [rss_url], 
				[agent], [agent_email], [agent_phone], [agent_website], [agent_photo], [broker], 
				[broker_email], [broker_phone], [broker_website], [broker_logo], [coordinates]
            FROM [[property]]';

        if (is_numeric($id)) {
            $sql .= ' WHERE [id] = {id}';
			$params['id']       = (int)$id;
        } else {
            $sql .= ' WHERE [fast_url] = {id}';
			$params['id']       = $id;
        }

        $types = array(
			'integer', 'integer', 'integer', 'text', 'text', 
			'text', 'text', 'text', 'text', 'text', 'text', 
			'text', 'text', 'integer', 'text', 'text', 'text', 
			'decimal', 'decimal', 'decimal', 'decimal', 'text', 'text', 
			'text', 'decimal', 'decimal', 'text', 'text', 'integer', 
			'integer', 'text', 'integer', 'integer', 'integer', 'integer', 
			'text', 'text', 'text', 'text', 'text', 'text', 
			'text', 'text', 'text', 'integer', 'text', 
			'timestamp', 'timestamp', 'text', 'integer', 'integer', 'text', 
			'text', 'text', 'text', 'text', 'text', 'text', 
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text',
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text'
		);


        $row = $GLOBALS['db']->queryRow($sql, $params, $types);
		
        if (isset($row['id'])) {
            return $row;
        }

        return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTY_NOT_FOUND'), _t('PROPERTIES_NAME'));
    }

    /**
     * Gets a user property by ID
     *
     * @access  public
     * @param   int     $id  The user ID
     * @return  mixed   Returns an array with the maps and false on error
     */
    function GetSinglePropertyByUserID($id, $cid)
    {
		$params       = array();
        $params['id'] = (int)$id;
        $params['cid'] = (int)$cid;
		
		$sql = '
            SELECT [id], [linkid], [sort_order], [category], [mls], [title], [image], 
				[sm_description], [description], [address], [city], [region], [postal_code], [country_id], 
				[community], [phase], [lotno], [price], [rentdy], [rentwk], [rentmo], [status], [acreage], 
				[sqft], [bedroom], [bathroom], [amenity], [i360], [maxchildno], [maxadultno], [petstay], 
				[occupancy], [maxcleanno], [roomcount], [minstay], [options], [item1], [item2], [item3], 
				[item4], [item5], [premium], [showmap], [featured], [ownerid], [active], [created], [updated], 
				[fast_url], [propertyno], [internal_propertyno], [alink], [alinktitle], [alinktype], [alink2], [alink2title], 
				[alink2type], [alink3], [alink3title], [alink3type], [calendar_link], [year], [rss_url], 
				[agent], [agent_email], [agent_phone], [agent_website], [agent_photo], [broker], 
				[broker_email], [broker_phone], [broker_website], [broker_logo], [coordinates]
			FROM [[property]]
            WHERE ([ownerid] = {id} AND [id] = {cid})';
		
        $types = array(
			'integer', 'integer', 'integer', 'text', 'text', 
			'text', 'text', 'text', 'text', 'text', 'text', 
			'text', 'text', 'integer', 'text', 'text', 'text', 
			'decimal', 'decimal', 'decimal', 'decimal', 'text', 'text', 
			'text', 'decimal', 'decimal', 'text', 'text', 'integer', 
			'integer', 'text', 'integer', 'integer', 'integer', 'integer', 
			'text', 'text', 'text', 'text', 'text', 'text', 
			'text', 'text', 'text', 'integer', 'text', 
			'timestamp', 'timestamp', 'text', 'integer', 'integer', 'text', 
			'text', 'text', 'text', 'text', 'text', 'text', 
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text',
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text'
		);

        $row = $GLOBALS['db']->queryRow($sql, $params, $types);
		
        if (isset($row['id'])) {
            return $row;
        }
		
		return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTY_NOT_FOUND'), _t('PROPERTIES_NAME'));
    }

    /**
     * Gets an index of all the property parents.
     *
     * @access  public
     * @param   int     $limit      The number of pages to return. Set to Null to return all pages.
     * @param   int     $sortType   One of the PAGES_SORT_* constants to set the sort field.
     * @param   int     $sortDir    Either PAGES_ASC or PAGES_DESC to set the sort direction.
     *
     * @return  array   An array containing the page information.
     */
    function GetProperties($limit = null, $sortColumn = 'sort_order', $sortDir = 'ASC', $offSet = false, 
		$OwnerID = null, $active = null, $return = null, $search = '')
    {
        $fields = array('sort_order', 'premium', 'price', 'community', 'featured', 'ownerid', 'title', 'created', 'updated', 'active', 'amenity', 'city');
        $sortColumn = strtolower($sortColumn);
        if (!in_array($sortColumn, $fields)) {
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_WARNING, _t('PROPERTIES_ERROR_UNKNOWN_COLUMN'));
            }
            $sortColumn = 'sort_order';
        }

        $sortDir = strtoupper($sortDir);
        if ($sortDir == 'DESC') {
            $sortDir = 'DESC';
        } else {
            $sortDir = 'ASC';
        }

        $sql = "
            SELECT [id], [linkid], [sort_order], [category], [mls], [title], [image], 
				[sm_description], [description], [address], [city], [region], [postal_code], [country_id], 
				[community], [phase], [lotno], [price], [rentdy], [rentwk], [rentmo], [status], [acreage], 
				[sqft], [bedroom], [bathroom], [amenity], [i360], [maxchildno], [maxadultno], [petstay], 
				[occupancy], [maxcleanno], [roomcount], [minstay], [options], [item1], [item2], [item3], 
				[item4], [item5], [premium], [showmap], [featured], [ownerid], [active], [created], [updated], 
				[fast_url], [propertyno], [internal_propertyno], [alink], [alinktitle], [alinktype], [alink2], [alink2title], 
				[alink2type], [alink3], [alink3title], [alink3type], [calendar_link], [year], [rss_url], 
				[agent], [agent_email], [agent_phone], [agent_website], [agent_photo], [broker], 
				[broker_email], [broker_phone], [broker_website], [broker_logo], [coordinates]
            FROM [[property]]
			WHERE [title] != ''
			";
		$params              = array();

		if (!is_null($OwnerID)) {
			$sql .= " AND ([ownerid] = {owner_id})";
			if ($OwnerID == 0) {
				$params['owner_id'] = 0;
			} else {
				$params['owner_id'] = $GLOBALS['app']->Session->GetAttribute('user_id');
			}
		}
	
		if (!is_null($active)) {
			$sql .=  " AND ([active] = {active})";
			$params['active'] = $active;
		}
				
		if (!is_null($return) && trim($search) != '') {
			$return = strtolower($return);
			if (strlen(trim($search)) == 5 && is_numeric(trim($search))) {
				$sql .=  " AND ([$return] LIKE {search}) OR ([title] LIKE {titlesearch}) OR ([address] LIKE {addresssearch})";
				$params['addresssearch'] = '%'.$search.'%';
			} else {
				$sql .=  " AND ([$return] LIKE {search}) OR ([title] LIKE {titlesearch})";
			}
			$params['search'] = $search.'%';
			$params['titlesearch'] = '%'.$search.'%';
		}
		$sql .= " ORDER BY [$sortColumn] $sortDir, [premium] DESC";

        if (is_numeric($offSet)) {
            $limit = is_null($limit) ? 10 : $limit;
            if (!is_null($limit)) {
                $result = $GLOBALS['db']->setLimit(10, $offSet);
                if (Jaws_Error::IsError($result)) {
                    //return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTIES_NOT_RETRIEVED'), _t('PROPERTIES_NAME'));
					return new Jaws_Error($result->GetMessage(), _t('PROPERTIES_NAME'));
                }
            }
        } else {
            if (!is_null($limit)) {
                $result = $GLOBALS['db']->setLimit($limit);
                if (Jaws_Error::IsError($result)) {
                    //return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTIES_NOT_RETRIEVED'), _t('PROPERTIES_NAME'));
					return new Jaws_Error($result->GetMessage(), _t('PROPERTIES_NAME'));
                }
            }
        }

		$types = array(
			'integer', 'integer', 'integer', 'text', 'text', 
			'text', 'text', 'text', 'text', 'text', 'text', 
			'text', 'text', 'integer', 'text', 'text', 'text', 
			'decimal', 'decimal', 'decimal', 'decimal', 'text', 'text', 
			'text', 'decimal', 'decimal', 'text', 'text', 'integer', 
			'integer', 'text', 'integer', 'integer', 'integer', 'integer', 
			'text', 'text', 'text', 'text', 'text', 'text', 
			'text', 'text', 'text', 'integer', 'text', 
			'timestamp', 'timestamp', 'text', 'integer', 'integer', 'text', 
			'text', 'text', 'text', 'text', 'text', 'text', 
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text',
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text'
		);

        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            //return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTIES_NOT_RETRIEVED'), _t('PROPERTIES_NAME'));
            return new Jaws_Error($result->GetMessage(), _t('PROPERTIES_NAME'));
        }

        return $result;
    }

    /**
     * Returns all properties that belongs to a parent
     *
     * @access  public
     * @return  array  Array with all the post IDs or Jaws_Error on error
     */
    function GetAllPropertiesOfParent($id, $sortColumn = 'sort_order', $sortDir = 'ASC', $active = null, $OwnerID = null)
    {
        if (!is_numeric($id)) {
			$parent = $this->GetPropertyParent($id);
			if (Jaws_Error::IsError($parent)) {
				return new Jaws_Error($parent->GetMessage(), _t('PROPERTIES_NAME'));
			} else if (!isset($parent['id']) || empty($parent['id'])) {
				return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTIES_NOT_RETRIEVED'), _t('PROPERTIES_NAME'));
			} else {
				$id = $parent['id'];
			}
		}
		$fields = array('sort_order', 'premium', 'price', 'community', 'featured', 'ownerid', 'title', 'created', 'updated', 'active');
        $sortColumn = strtolower($sortColumn);
        if (!in_array($sortColumn, $fields)) {
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_WARNING, _t('PROPERTIES_ERROR_UNKNOWN_COLUMN'));
            }
            $sortColumn = 'sort_order';
        }

        $sortDir = strtoupper($sortDir);
        /*
		if ($sortDir == 'DESC') {
			$sort_ascending = false;
            //$sortDir = 'DESC';
        } else {
			$sort_ascending = true;
            //$sortDir = 'ASC';
        }
		*/

		$sql  = '
            SELECT [id], [linkid], [sort_order], [category], [mls], [title], [image], 
				[sm_description], [description], [address], [city], [region], [postal_code], [country_id], 
				[community], [phase], [lotno], [price], [rentdy], [rentwk], [rentmo], [status], [acreage], 
				[sqft], [bedroom], [bathroom], [amenity], [i360], [maxchildno], [maxadultno], [petstay], 
				[occupancy], [maxcleanno], [roomcount], [minstay], [options], [item1], [item2], [item3], 
				[item4], [item5], [premium], [showmap], [featured], [ownerid], [active], [[property]].[created], [[property]].[updated], 
				[fast_url], [propertyno], [internal_propertyno], [alink], [alinktitle], [alinktype], [alink2], [alink2title], 
				[alink2type], [alink3], [alink3title], [alink3type], [calendar_link], [year], [rss_url], 
				[agent], [agent_email], [agent_phone], [agent_website], [agent_photo], [broker], 
				[broker_email], [broker_phone], [broker_website], [broker_logo], [coordinates]
			FROM [[properties_parents]]
            INNER JOIN [[property]] ON [[properties_parents]].[prop_id] = [[property]].[id]
			WHERE ([[properties_parents]].[parent_id] = {id})
		';

		$params = array();
		$params['id'] = (int)$id;
		if (!is_null($active)) {
			$sql .=  " AND ([active] = {active})";
			$params['active'] = $active;
		}
		if (!is_null($OwnerID)) {
			$sql .=  " AND ([ownerid] = {OwnerID})";
			$params['OwnerID'] = (int)$OwnerID;
		}
		$sql .= " ORDER BY [$sortColumn] $sortDir".($sortColumn == 'sort_order' ? ", [image] DESC" : '').", [premium] DESC";
        
		$types = array(
			'integer', 'integer', 'integer', 'text', 'text', 
			'text', 'text', 'text', 'text', 'text', 'text', 
			'text', 'text', 'integer', 'text', 'text', 'text', 
			'decimal', 'decimal', 'decimal', 'decimal', 'text', 'text', 
			'text', 'decimal', 'decimal', 'text', 'text', 'integer', 
			'integer', 'text', 'integer', 'integer', 'integer', 'integer', 
			'text', 'text', 'text', 'text', 'text', 'text', 
			'text', 'text', 'text', 'integer', 'text', 
			'timestamp', 'timestamp', 'text', 'integer', 'integer', 'text', 
			'text', 'text', 'text', 'text', 'text', 'text', 
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text',
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text'
		);

		$properties = $GLOBALS['db']->queryAll($sql, $params, $types);
		
        if (Jaws_Error::IsError($properties)) {
            //add language word for this
            //return new Jaws_Error(_t('STORE_ERROR_STORE_NOT_RETRIEVED'), _t('STORE_NAME'));
            return new Jaws_Error($properties->GetMessage(), _t('STORE_NAME'));
        }	
		if (count($properties) <= 0) {
			return array();
		}
        				
		return $properties;
    }

	/**
     * Returns all property owners of given property parent
     *
     * @access  public
     * @return  array  Array with all the post IDs or Jaws_Error on error
     */
    function GetPropertyOwnersOfParent($pid)
    {
        if (!is_numeric($pid)) {
			$parent = $this->GetPropertyParent($pid);
			if (Jaws_Error::IsError($parent)) {
				return new Jaws_Error($parent->GetMessage(), _t('PROPERTIES_NAME'));
			} else if (!isset($parent['propertyparentid']) || empty($parent['propertyparentid'])) {
				return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTIES_NOT_RETRIEVED'), _t('PROPERTIES_NAME'));
			} else {
				$pid = $parent['propertyparentid'];
			}
		}

		$sql  = '
            SELECT DISTINCT [ownerid]
			FROM [[properties_parents]]
            INNER JOIN [[property]] ON [[properties_parents]].[prod_id] = [[property]].[id]
            INNER JOIN [[users]] ON [[users]].[id] = [[property]].[ownerid]
			WHERE ([[properties_parents]].[parent_id] = {id})
		';

		$params = array();
		$params['id'] = (int)$pid;
        
        $types = array(
			'integer'
		);

		$properties = $GLOBALS['db']->queryAll($sql, $params, $types);
		
        if (Jaws_Error::IsError($properties)) {
            //add language word for this
            //return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTIES_NOT_RETRIEVED'), _t('PROPERTIES_NAME'));
            return new Jaws_Error($properties->GetMessage(), _t('PROPERTIES_NAME'));
        }	
		if (count($properties) <= 0) {
			return array();
		}
		
		return $properties;
    }
    
	/**
     * Returns true/false if a user owns a property in given parent
     *
     * @access  public
     * @return  boolean  true/false
     */
    function UserOwnsPropertiesInParent($pid)
    {
        if (!is_numeric($pid)) {
			$parent = $this->GetPropertyParent($pid);
			if (!Jaws_Error::IsError($parent) && isset($parent['propertyparentid']) && !empty($parent['propertyparentid'])) {
				$pid = $parent['propertyparentid'];
			} else {
				return false;
			}
		}

		$sql  = '
            SELECT COUNT([[property]].[id])
			FROM [[property]]
            INNER JOIN [[properties_parents]] ON [[property]].[id] = [[properties_parents]].[prop_id] 
            INNER JOIN [[users]] ON [[property]].[ownerid] = [[users]].[id]
			WHERE ([[properties_parents]].[parent_id] = {id}) AND ([[property]].[ownerid] = {uid})
		';

		$params = array();
		$params['id'] = (int)$pid;
		$params['uid'] = (int)$uid;
        
        $howmany = $GLOBALS['db']->queryOne($sql, $params);
        if (Jaws_Error::IsError($howmany)) {
            return false;
        }

        return ($howmany == '0') ? false : true;
    }
    
	/**
     * Gets the users properties by ID
     *
     * @access  public
     * @param   int     $id  The user ID
     * @return  mixed   Returns an array with the maps and false on error
     */
    function GetPropertiesOfUserID($id, $active = null)
    {
		$params       = array();
        $params['id'] = (int)$id;
		$sql = '
            SELECT [id], [linkid], [sort_order], [category], [mls], [title], [image], 
				[sm_description], [description], [address], [city], [region], [postal_code], [country_id], 
				[community], [phase], [lotno], [price], [rentdy], [rentwk], [rentmo], [status], [acreage], 
				[sqft], [bedroom], [bathroom], [amenity], [i360], [maxchildno], [maxadultno], [petstay], 
				[occupancy], [maxcleanno], [roomcount], [minstay], [options], [item1], [item2], [item3], 
				[item4], [item5], [premium], [showmap], [featured], [ownerid], [active], [created], [updated], 
				[fast_url], [propertyno], [internal_propertyno], [alink], [alinktitle], [alinktype], [alink2], [alink2title], 
				[alink2type], [alink3], [alink3title], [alink3type], [calendar_link], [year], [rss_url], 
				[agent], [agent_email], [agent_phone], [agent_website], [agent_photo], [broker], 
				[broker_email], [broker_phone], [broker_website], [broker_logo], [coordinates]
			FROM [[property]]
            WHERE ([ownerid] = {id})';
        if (!is_null($active)) {
			$params['Active'] = 'Y';
			$sql .= ' AND ([active] = {Active})';
		}
		
        $types = array(
			'integer', 'integer', 'integer', 'text', 'text', 
			'text', 'text', 'text', 'text', 'text', 'text', 
			'text', 'text', 'integer', 'text', 'text', 'text', 
			'decimal', 'decimal', 'decimal', 'decimal', 'text', 'text', 
			'text', 'decimal', 'decimal', 'text', 'text', 'integer', 
			'integer', 'text', 'integer', 'integer', 'integer', 'integer', 
			'text', 'text', 'text', 'text', 'text', 'text', 
			'text', 'text', 'text', 'integer', 'text', 
			'timestamp', 'timestamp', 'text', 'integer', 'integer', 'text', 
			'text', 'text', 'text', 'text', 'text', 'text', 
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text',
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text'
		);

        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTIES_NOT_RETRIEVED'), _t('PROPERTIES_NAME'));
        }

        return $result;
    }
    
    /**
     * Gets the users properties by ID
     *
     * @access  public
     * @param   int     $id  The region ID
     * @return  mixed   Returns an array with the maps and false on error
     */
    function GetPropertiesInRegion($id, $cid = null, $OwnerID = null)
    {
		$params       = array();
        $params['id'] = $id;
		
		$sql = '
            SELECT [id], [linkid], [sort_order], [category], [mls], [title], [image], 
				[sm_description], [description], [address], [city], [region], [postal_code], [country_id], 
				[community], [phase], [lotno], [price], [rentdy], [rentwk], [rentmo], [status], [acreage], 
				[sqft], [bedroom], [bathroom], [amenity], [i360], [maxchildno], [maxadultno], [petstay], 
				[occupancy], [maxcleanno], [roomcount], [minstay], [options], [item1], [item2], [item3], 
				[item4], [item5], [premium], [showmap], [featured], [ownerid], [active], [created], [updated], 
				[fast_url], [propertyno], [internal_propertyno], [alink], [alinktitle], [alinktype], [alink2], [alink2title], 
				[alink2type], [alink3], [alink3title], [alink3type], [calendar_link], [year], [rss_url], 
				[agent], [agent_email], [agent_phone], [agent_website], [agent_photo], [broker], 
				[broker_email], [broker_phone], [broker_website], [broker_logo], [coordinates]
			FROM [[property]]
            WHERE ([region] = {id})';
		if (!is_null($cid)) {
			$sql .= ' AND ([linkid] = {LinkID})';
			$params['LinkID'] = $cid;
		}
		if (!is_null($OwnerID)) {
			$sql .= ' AND ([ownerid] = {OwnerID})';
			$params['OwnerID'] = $OwnerID;
		}
		
        $types = array(
			'integer', 'integer', 'integer', 'text', 'text', 
			'text', 'text', 'text', 'text', 'text', 'text', 
			'text', 'text', 'integer', 'text', 'text', 'text', 
			'decimal', 'decimal', 'decimal', 'decimal', 'text', 'text', 
			'text', 'decimal', 'decimal', 'text', 'text', 'integer', 
			'integer', 'text', 'integer', 'integer', 'integer', 'integer', 
			'text', 'text', 'text', 'text', 'text', 'text', 
			'text', 'text', 'text', 'integer', 'text', 
			'timestamp', 'timestamp', 'text', 'integer', 'integer', 'text', 
			'text', 'text', 'text', 'text', 'text', 'text', 
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text',
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text'
		);

        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTIES_NOT_RETRIEVED'), _t('PROPERTIES_NAME'));
        }

        return $result;
    }

    /**
     * Returns all properties that belongs to users of given Group ID
     *
     * @access  public
     * @return  array  Array with all the post IDs or Jaws_Error on error
     */
    function GetPropertiesOfGroup($gid, $sortColumn = 'sort_order', $sortDir = 'ASC', $active = null)
    {
		$fields = array('sort_order', 'premium', 'price', 'community', 'featured', 'ownerid', 'title', 'created', 'updated', 'active');
        $sortColumn = strtolower($sortColumn);
        if (!in_array($sortColumn, $fields)) {
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_WARNING, _t('PROPERTIES_ERROR_UNKNOWN_COLUMN'));
            }
            $sortColumn = 'sort_order';
        }

        $sortDir = strtoupper($sortDir);

		$sql  = "
            SELECT [id], [linkid], [sort_order], [category], [mls], [title], [image], 
				[sm_description], [description], [address], [city], [region], [postal_code], [country_id], 
				[community], [phase], [lotno], [price], [rentdy], [rentwk], [rentmo], [status], [acreage], 
				[sqft], [bedroom], [bathroom], [amenity], [i360], [maxchildno], [maxadultno], [petstay], 
				[occupancy], [maxcleanno], [roomcount], [minstay], [options], [item1], [item2], [item3], 
				[item4], [item5], [premium], [showmap], [featured], [ownerid], [active], [[property]].[created], [[property]].[updated], 
				[fast_url], [propertyno], [internal_propertyno], [alink], [alinktitle], [alinktype], [alink2], [alink2title], 
				[alink2type], [alink3], [alink3title], [alink3type], [calendar_link], [year], [rss_url], 
				[agent], [agent_email], [agent_phone], [agent_website], [agent_photo], [broker], 
				[broker_email], [broker_phone], [broker_website], [broker_logo], [coordinates]
			FROM [[users_groups]]
            INNER JOIN [[property]] ON [[users_groups]].[user_id] = [[property]].[ownerid]
			WHERE ([[users_groups]].[group_id] = {id}) AND ([[users_groups]].[status] = 'active' OR [[users_groups]].[status] = 'admin')
		";

		$params = array();
		$params['id'] = $gid;
		if (!is_null($active)) {
			$sql .=  " AND ([active] = {active})";
			$params['active'] = $active;
		}
		$sql .= " ORDER BY [$sortColumn] $sortDir".($sortColumn == 'sort_order' ? ", [image] DESC" : '').", [premium] DESC";
        
        $types = array(
			'integer', 'integer', 'integer', 'text', 'text', 
			'text', 'text', 'text', 'text', 'text', 'text', 
			'text', 'text', 'integer', 'text', 'text', 'text', 
			'decimal', 'decimal', 'decimal', 'decimal', 'text', 'text', 
			'text', 'decimal', 'decimal', 'text', 'text', 'integer', 
			'integer', 'text', 'integer', 'integer', 'integer', 'integer', 
			'text', 'text', 'text', 'text', 'text', 'text', 
			'text', 'text', 'text', 'integer', 'text', 
			'timestamp', 'timestamp', 'text', 'integer', 'integer', 'text', 
			'text', 'text', 'text', 'text', 'text', 'text', 
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text',
			'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text'
		);

		$properties = $GLOBALS['db']->queryAll($sql, $params, $types);
		
        if (Jaws_Error::IsError($properties)) {
            //add language word for this
            //return new Jaws_Error(_t('STORE_ERROR_STORE_NOT_RETRIEVED'), _t('STORE_NAME'));
            return new Jaws_Error($properties->GetMessage(), _t('PROPERTIES_NAME'));
        }	
		if (count($properties) <= 0) {
			return array();
		}
		
		return $properties;
    }

    /**
     * Gets a single amenity by ID.
     *
     * @access  public
     * @param   int     $id     The ID of the page to get.
     * @return  array   An array containing the page information, or false if no page could be loaded.
     */
    function GetAmenity($id)
    {
		$sql = '
            SELECT [id], [feature], [typeid], [description], [ownerid], 
				[active], [created], [updated], [checksum]
            FROM [[propertyamenity]] WHERE [id] = {id} ORDER BY [typeid] ASC';

        $types = array(
			'integer', 'text', 'integer', 'text', 'integer', 'text', 
			'timestamp', 'timestamp', 'text'
		);

        $params             = array();
        $params['id']       = $id;
        //$params['language'] = $language;

        $row = $GLOBALS['db']->queryRow($sql, $params, $types);
		
        if (isset($row['id'])) {
            return $row;
        }

        return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTYAMENITY_NOT_FOUND'), _t('PROPERTIES_NAME'));
    }

    /**
     * Gets an index of all the amenities.
     *
     * @access  public
     * @param   int     $limit      The number of pages to return. Set to Null to return all pages.
     * @param   int     $sortType   One of the PAGES_SORT_* constants to set the sort field.
     * @param   int     $sortDir    Either PAGES_ASC or PAGES_DESC to set the sort direction.
     *
     * @return  array   An array containing the page information.
     */
    function GetPropertyAmenities($limit = null, $sortColumn = 'typeid', $sortDir = 'ASC', $offSet = false, $OwnerID = null)
    {
        $fields     = array('ownerid', 'feature', 'typeid', 'created', 'updated', 'active');
        $sortColumn = strtolower($sortColumn);
        if (!in_array($sortColumn, $fields)) {
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_WARNING, _t('PROPERTIES_ERROR_UNKNOWN_COLUMN'));
            }
            $sortColumn = 'typeid';
        }

        $sortDir = strtoupper($sortDir);
        if ($sortDir == 'DESC') {
            $sortDir = 'DESC';
        } else {
            $sortDir = 'ASC';
        }

        $sql = "
            SELECT [id], [feature], [typeid], [description], [ownerid], 
				[active], [created], [updated], [checksum]
            FROM [[propertyamenity]]
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
                    return new Jaws_Error(_t('PROPERTIES_ERROR_AMENITIES_NOT_RETRIEVED'), _t('PROPERTIES_NAME'));
                }
            }
        } else {
            if (!is_null($limit)) {
                $result = $GLOBALS['db']->setLimit($limit);
                if (Jaws_Error::IsError($result)) {
                    return new Jaws_Error(_t('PROPERTIES_ERROR_AMENITIES_NOT_RETRIEVED'), _t('PROPERTIES_NAME'));
                }
            }
        }

        $types = array(
			'integer', 'text', 'integer', 'text', 'integer', 'text', 
			'timestamp', 'timestamp', 'text'
		);

        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('PROPERTIES_ERROR_AMENITIES_NOT_RETRIEVED'), _t('PROPERTIES_NAME'));
        }

        return $result;
    }

    /**
     * Gets a single amenity type by ID.
     *
     * @access  public
     * @param   int     $id     The ID of the page to get.
     * @return  array   An array containing the page information, or false if no page could be loaded.
     */
    function GetAmenityType($id)
    {
		$sql = '
            SELECT [id], [title], [description], [ownerid], [active], 
			[created], [updated], [checksum]
            FROM [[amenity_types]] WHERE [id] = {id}';

        $types = array(
			'integer', 'text', 'text', 'integer', 'text', 
			'timestamp', 'timestamp', 'text'
		);

        $params             = array();
        $params['id']       = $id;
        //$params['language'] = $language;

        $row = $GLOBALS['db']->queryRow($sql, $params, $types);
		
        if (isset($row['id'])) {
            return $row;
        }

        return new Jaws_Error(_t('PROPERTIES_ERROR_AMENITYTYPE_NOT_FOUND'), _t('PROPERTIES_NAME'));
    }

    /**
     * Gets an index of all the amenity types.
     *
     * @access  public
     * @param   int     $limit      The number of pages to return. Set to Null to return all pages.
     * @param   int     $sortType   One of the PAGES_SORT_* constants to set the sort field.
     * @param   int     $sortDir    Either PAGES_ASC or PAGES_DESC to set the sort direction.
     *
     * @return  array   An array containing the page information.
     */
    function GetAmenityTypes($limit = null, $sortColumn = 'title', $sortDir = 'ASC', $offSet = false, $OwnerID = null)
    {
        $fields     = array('ownerid', 'title', 'created', 'updated', 'active');
        $sortColumn = strtolower($sortColumn);
        if (!in_array($sortColumn, $fields)) {
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_WARNING, _t('PROPERTIES_ERROR_UNKNOWN_COLUMN'));
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
            SELECT [id], [title], [description], [ownerid],
				[active], [created], [updated], [checksum]
            FROM [[amenity_types]]
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
                    return new Jaws_Error(_t('PROPERTIES_ERROR_AMENITYTYPES_NOT_RETRIEVED'), _t('PROPERTIES_NAME'));
                }
            }
        } else {
            if (!is_null($limit)) {
                $result = $GLOBALS['db']->setLimit($limit);
                if (Jaws_Error::IsError($result)) {
                    return new Jaws_Error(_t('PROPERTIES_ERROR_AMENITYTYPES_NOT_RETRIEVED'), _t('PROPERTIES_NAME'));
                }
            }
        }

        $types = array(
			'integer', 'text', 'text', 'integer', 'text', 
			'timestamp', 'timestamp', 'text'
		);

        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('PROPERTIES_ERROR_AMENITYTYPES_NOT_RETRIEVED'), _t('PROPERTIES_NAME'));
        }

        return $result;
    }

    /**
     * Gets an index of all the amenities.
     *
     * @access  public
     * @param   int     $id      ID of type.
     *
     * @return  array   An array containing the page information.
     */
    function GetAmenitiesOfType($id)
    {

        $sql = "
            SELECT [id], [feature], [typeid], [description], [ownerid], [active], 
				[created], [updated], [checksum]
            FROM [[propertyamenity]]
			WHERE [typeid] = {id}
			ORDER BY [typeid] ASC
			";
		
		$params              = array();
		$params['id'] = $id;

        $types = array(
			'integer', 'text', 'integer', 'text', 'decimal', 
			'integer', 'decimal', 'integer', 'text', 
			'timestamp', 'timestamp', 'text'
		);

        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('PROPERTIES_ERROR_PROPERTYAMENITIES_NOT_RETRIEVED'), _t('STORE_NAME'));
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
				[description], [image], [image_width], [image_height], 
				[layout], [active], [ownerid], [created], [updated],
				[url], [url_target], [checksum]
			FROM [[property_posts]] WHERE [id] = {id}';

        $types = array(
			'integer', 'integer', 'integer', 'text', 
			'text', 'text', 'integer', 'integer', 'integer', 'text', 
			'integer', 'timestamp', 'timestamp', 'text', 'text', 'text'
		);

        $params             = array();
        $params['id']       = $id;

        $row = $GLOBALS['db']->queryRow($sql, $params, $types);

        if (isset($row['id'])) {
            return $row;
        }

        return new Jaws_Error(_t('PROPERTIES_ERROR_POST_NOT_FOUND'), _t('PROPERTIES_NAME'));
    }

    /**
     * Returns all posts that belongs to a page
     *
     * @access  public
     * @return  array  Array with all the post IDs or Jaws_Error on error
     */
    function GetAllPostsOfProperty($id)
    {
	    $sql  = '
            SELECT [id], [sort_order], [linkid], [title], 
				[description], [image], [image_width], [image_height], 
				[layout], [active], [ownerid], [created], [updated],
				[url], [url_target], [checksum]
			FROM [[property_posts]] WHERE [linkid] = {id}
			ORDER BY [sort_order] ASC, [title] ASC';

        $types = array(
			'integer', 'integer', 'integer', 'text', 
			'text', 'text', 'integer', 'integer', 'integer', 'text', 
			'integer', 'timestamp', 'timestamp', 'text', 'text', 'text'
		);

		$result = $GLOBALS['db']->queryAll($sql, array('id' => $id), $types);
		
        if (Jaws_Error::IsError($result)) {
            //add language word for this
            return new Jaws_Error(_t('PROPERTIES_ERROR_POST_NOT_FOUND'), _t('PROPERTIES_NAME'));
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
		
		$sql = '
            SELECT [id], [parent], [region], [ownerid], [is_country], 
				[country_iso_code], [latitude], [longitude], [checksum]
			FROM [[country]]';

		if (is_numeric($id)) {
			$sql .= 'WHERE [id] = {id}';
			$params['id'] = (int)$id;
		} else {
			$sql .= 'WHERE [region] = {id}';
			$params['id'] = $id;
		}
			
        $types = array(
			'integer', 'integer', 'text', 'integer', 'text', 
			'text', 'float', 'float', 'text'
		);

        $result = $GLOBALS['db']->queryRow($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            //return new Jaws_Error(_t('PROPERTIES_ERROR_REGIONS_NOT_RETRIEVED'), _t('PROPERTIES_NAME'));
            return new Jaws_Error($result->GetMessage(), _t('PROPERTIES_NAME'));
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
    function GetRegionsOfParent($id)
    {
		$params       = array();
        $params['id'] = $id;
		
		$sql = '
            SELECT [id], [parent], [region], [ownerid], [is_country], 
				[country_iso_code], [latitude], [longitude], [checksum]
			FROM [[country]] WHERE [parent] = {id}';
		
        $types = array(
			'integer', 'integer', 'text', 'integer', 'text', 
			'text', 'float', 'float', 'text'
		);

        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('PROPERTIES_ERROR_REGIONS_NOT_RETRIEVED'), _t('PROPERTIES_NAME'));
        }

        return $result;
    }

    /**
     * Returns all rss items that should be hidden
     *
     * @access  public
     * @return  array  Array with all the rss info or Jaws_Error on error
     */
    function GetHiddenRssOfPropertyParent($id)
    {
	    $sql  = 'SELECT [id], [linkid], [title], [published], [url]
			FROM [[property_rss_hide]] WHERE [linkid] = {id}';

        $types = array(
			'integer', 'integer', 'text', 'text', 'text'
		);
		
		$result = $GLOBALS['db']->queryAll($sql, array('id' => $id), $types);
		
        if (Jaws_Error::IsError($result)) {
            //add language word for this
            return new Jaws_Error($result->GetMessage(), _t('PROPERTIES_NAME'));
        }

        return $result;
    }
    
	/**
     * Find a record related on fast url
     *
     * @access  public
     * @param   string  $fasturl    The FastURL string
     * @return  array   An array containing the page info and Jaws_Error on error
     */
    function GetFastURL($fasturl)
    {
        $params = array();
        $params['fasturl'] = $fasturl;

        $sql = '
            SELECT
                [propertyparentid], [propertyparentcategory_name], [propertyparentfast_url]
            FROM [[propertyparent]]
            WHERE [propertyparentfast_url] = {fasturl}';

        $types = array('integer', 'text', 'text');

        $res = $GLOBALS['db']->queryRow($sql, $params, $types);
        if (Jaws_Error::IsError($res)) {
            return false;
        }

        return $res;
    }
}
