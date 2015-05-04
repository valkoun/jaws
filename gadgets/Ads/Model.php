<?php
/**
 * Ads Gadget
 *
 * @category   GadgetModel
 * @package    Ads
 * @author     Alan Valkoun	 <valkoun@gmail.com>
 * @copyright  2008 Alan Valkoun
 */

class AdsModel extends Jaws_Model
{
    var $_Name = 'Ads';
	
    /**
     * Gets a single page by ID.
     *
     * @access  public
     * @param   int     $id     The ID of the page to get.
     * @return  array   An array containing the page information, or false if no page could be loaded.
     */
    function GetAdParent($id)
    {
        $params = array();
		$sql = '
            SELECT [adparentid], [adparentparent], [adparentsort_order], [adparentcategory_name], 
				[adparentimage], [adparentdescription], [adparentactive], 
				[adparentownerid], [adparentcreated], [adparentupdated], 
				[adparentfeatured], [adparentfast_url], [adparentrss_url],
				[adparenturl],[adparenturl_target],[adparentimage_code],[adparentchecksum]
            FROM [[adparent]]';

        if (is_numeric($id)) {
            $sql .= ' WHERE [adparentid] = {id}';
			 $params['id']       = (int)$id;
       } else {
            $sql .= ' WHERE [adparentfast_url] = {id}';
			 $params['id']       = $id;
        }

        $types = array(
			'integer', 'integer', 'integer', 'text', 'text', 'text', 
			'text', 'integer', 'timestamp', 'timestamp', 
			'text', 'text', 'text', 'text', 'text', 'text', 'text'
		);

        //$params['language'] = $language;

        $row = $GLOBALS['db']->queryRow($sql, $params, $types);
		
        if (isset($row['adparentid'])) {
            return $row;
        }

        return new Jaws_Error(_t('ADS_ERROR_ADPARENT_NOT_FOUND'), _t('ADS_NAME'));
    }
    
    /**
     * Gets a single adparent by checksum.
     *
     * @access  public
     * @param   int     $checksum     The checksum of the page to get.
     * @return  array   An array containing the page information, or false if no page could be loaded.
     */
    function GetAdParentByChecksum($checksum)
    {
        $params 			= array();
		$params['checksum']	= $checksum;
		$sql = '
            SELECT [adparentid], [adparentparent], [adparentsort_order], [adparentcategory_name], 
				[adparentimage], [adparentdescription], [adparentactive], 
				[adparentownerid], [adparentcreated], [adparentupdated], 
				[adparentfeatured], [adparentfast_url], [adparentrss_url],
				[adparenturl],[adparenturl_target],[adparentimage_code],[adparentchecksum]
            FROM [[adparent]] WHERE [adparentchecksum] = {checksum}';

        $types = array(
			'integer', 'integer', 'integer', 'text', 'text', 'text', 
			'text', 'integer', 'timestamp', 'timestamp', 
			'text', 'text', 'text', 'text', 'text', 'text', 'text'
		);

        //$params['language'] = $language;

        $row = $GLOBALS['db']->queryRow($sql, $params, $types);
		
        if (isset($row['adparentid'])) {
            return $row;
        }

        return new Jaws_Error(_t('ADS_ERROR_ADPARENT_NOT_FOUND'), _t('ADS_NAME'));
    }
    
    /**
     * Gets a user adparent by ID
     *
     * @access  public
     * @param   int     $id  The user ID
     * @return  mixed   Returns an array with the product and false on error
     */
    function GetSingleAdParentByUserID($id, $cid)
    {
		$params       = array();
        $params['id'] = $id;
        $params['cid'] = $cid;
		
		$sql = '
            SELECT [adparentid], [adparentparent], [adparentsort_order], [adparentcategory_name], 
				[adparentimage], [adparentdescription], [adparentactive], 
				[adparentownerid], [adparentcreated], [adparentupdated], 
				[adparentfeatured], [adparentfast_url], [adparentrss_url],
				[adparenturl],[adparenturl_target],[adparentimage_code],[adparentchecksum]
			FROM [[adparent]]
            WHERE ([adparentownerid] = {id} AND [adparentid] = {cid})';
		
        $types = array(
			'integer', 'integer', 'integer', 'text', 'text', 'text', 
			'text', 'integer', 'timestamp', 'timestamp', 
			'text', 'text', 'text', 'text', 'text', 'text', 'text'
		);

        $row = $GLOBALS['db']->queryRow($sql, $params, $types);
		
		if (isset($row['id'])) {
            return $row;
        }

		return new Jaws_Error(_t('ADS_ERROR_ADPARENT_NOT_FOUND'), _t('ADS_NAME'));
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
    function GetAdParents($limit = null, $sortColumn = 'adparentsort_order', $sortDir = 'ASC', $offSet = false, $OwnerID = null)
    {
        $fields     = array('adparentsort_order', 'adparentownerid', 'adparentcategory_name', 'adparentcreated', 'adparentupdated', 'adparentactive');
        $sortColumn = strtolower($sortColumn);
        if (!in_array($sortColumn, $fields)) {
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_WARNING, _t('ADS_ERROR_UNKNOWN_COLUMN'));
            }
            $sortColumn = 'adparentsort_order';
        }

        $sortDir = strtoupper($sortDir);
        if ($sortDir != 'ASC') {
            $sortDir = 'DESC';
        }

        $sql = "
            SELECT [adparentid], [adparentparent], [adparentsort_order], [adparentcategory_name], 
				[adparentimage], [adparentdescription], [adparentactive], 
				[adparentownerid], [adparentcreated], [adparentupdated], 
				[adparentfeatured], [adparentfast_url], [adparentrss_url],
				[adparenturl],[adparenturl_target],[adparentimage_code],[adparentchecksum]
            FROM [[adparent]]
			";
		$params              = array();
		$params['owner_id'] = $GLOBALS['app']->Session->GetAttribute('user_id');

		if (!is_null($OwnerID)) {
			$sql .= " WHERE [adparentownerid] = {owner_id}";
		} else {
			$sql .= " WHERE [adparentownerid] = 0";
		}
		
		$sql .= " ORDER BY [$sortColumn] $sortDir";

        if (is_numeric($offSet)) {
            $limit = is_null($limit) ? 10 : $limit;
            if (!is_null($limit)) {
                $result = $GLOBALS['db']->setLimit(10, $offSet);
                if (Jaws_Error::IsError($result)) {
                    return new Jaws_Error(_t('ADS_ERROR_ADPARENTS_NOT_RETRIEVED'), _t('ADS_NAME'));
                }
            }
        } else {
            if (!is_null($limit)) {
                $result = $GLOBALS['db']->setLimit($limit);
                if (Jaws_Error::IsError($result)) {
                    return new Jaws_Error(_t('ADS_ERROR_ADPARENTS_NOT_RETRIEVED'), _t('ADS_NAME'));
                }
            }
        }

        $types = array(
			'integer', 'integer', 'integer', 'text', 'text', 'text', 
			'text', 'integer', 'timestamp', 'timestamp', 
			'text', 'text', 'text', 'text', 'text', 'text', 'text'
		);

        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('ADS_ERROR_ADPARENTS_NOT_RETRIEVED'), _t('ADS_NAME'));
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
            SELECT [adparentid], [adparentparent], [adparentsort_order], [adparentcategory_name], 
				[adparentimage], [adparentdescription], [adparentactive], 
				[adparentownerid], [adparentcreated], [adparentupdated], 
				[adparentfeatured], [adparentfast_url], [adparentrss_url],
				[adparenturl],[adparenturl_target],[adparentimage_code],[adparentchecksum]
			FROM [[adparent]] WHERE [adparentparent] = {id}
			ORDER BY [adparentsort_order] ASC, [adparentcategory_name] ASC';

        $types = array(
			'integer', 'integer', 'integer', 'text', 'text', 'text', 
			'text', 'integer', 'timestamp', 'timestamp', 
			'text', 'text', 'text', 'text', 'text', 'text', 'text'
		);

		$result = $GLOBALS['db']->queryAll($sql, array('id' => $id), $types);
		
        if (Jaws_Error::IsError($result)) {
            //add language word for this
            return new Jaws_Error(_t('ADS_ERROR_ADPARENT_NOT_FOUND'), _t('ADS_NAME'));
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
    function GetAdParentsOfUserID($id)
    {
		$params       = array();
        $params['id'] = $id;
		
		$sql = '
            SELECT [adparentid], [adparentparent], [adparentsort_order], [adparentcategory_name], 
				[adparentimage], [adparentdescription], [adparentactive], 
				[adparentownerid], [adparentcreated], [adparentupdated], 
				[adparentfeatured], [adparentfast_url], [adparentrss_url],
				[adparenturl],[adparenturl_target],[adparentimage_code],[adparentchecksum]
			FROM [[adparent]]
            WHERE ([adparentownerid] = {id})';
		
        $types = array(
			'integer', 'integer', 'integer', 'text', 'text', 'text', 
			'text', 'integer', 'timestamp', 'timestamp', 
			'text', 'text', 'text', 'text', 'text', 'text', 'text'
		);

        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('ADS_ERROR_ADPARENTS_NOT_RETRIEVED'), _t('ADS_NAME'));
        }

        return $result;
    }

    /**
     * Gets a single ad by ID.
     *
     * @access  public
     * @param   int     $id     The ID of the ad to get.
     * @return  array   An array containing the ad information, or false if no ad could be loaded.
     */
    function GetAd($id)
    {
        $sql = '
            SELECT [id], [type], [image], [url], [title], [keyword], [sitewide],
			[ownerid], [active], [created], [updated], [barcode_data], [barcode_type], 
			[description], [linkid], [brandid], [checksum]
            FROM [[ads]]
			WHERE [id] = {id}';

        $types = array(
			'integer', 'text', 'text', 'text', 'text', 'text', 'text', 
			'integer', 'text', 'timestamp', 'timestamp', 'text', 'text', 
			'text', 'integer', 'integer', 'text'
		);

        $params             = array();
        $params['id']       = $id;
        //$params['language'] = $language;

        $row = $GLOBALS['db']->queryRow($sql, $params, $types);
        if (Jaws_Error::IsError($row)) {
            return new Jaws_Error(_t('ADS_ERROR_AD_NOT_FOUND'), _t('ADS_NAME'));
        }
		
        if (isset($row['id'])) {
            return $row;
        }

        return new Jaws_Error(_t('ADS_ERROR_AD_NOT_FOUND'), _t('ADS_NAME'));
    }

    /**
     * Gets a single ad by checksum.
     *
     * @access  public
     * @param   int     $checksum     The checksum of the ad to get.
     * @return  array   An array containing the ad information, or false if no ad could be loaded.
     */
    function GetAdByChecksum($checksum)
    {
        $sql = '
            SELECT [id], [type], [image], [url], [title], [keyword], [sitewide],
			[ownerid], [active], [created], [updated], [barcode_data], [barcode_type], 
			[description], [linkid], [brandid], [checksum]
            FROM [[ads]]
			WHERE [checksum] = {checksum}';

        $types = array(
			'integer', 'text', 'text', 'text', 'text', 'text', 'text', 
			'integer', 'text', 'timestamp', 'timestamp', 'text', 'text', 
			'text', 'integer', 'integer', 'text'
		);

        $params             = array();
        $params['checksum']	= $checksum;
        //$params['language'] = $language;

        $row = $GLOBALS['db']->queryRow($sql, $params, $types);
        if (Jaws_Error::IsError($row)) {
            return new Jaws_Error(_t('ADS_ERROR_AD_NOT_FOUND'), _t('ADS_NAME'));
        }
		
        if (isset($row['id'])) {
            return $row;
        }

        return new Jaws_Error(_t('ADS_ERROR_AD_NOT_FOUND'), _t('ADS_NAME'));
    }

    /**
     * Gets an index of all the ads.
     *
     * @access  public
     * @param   int     $limit      The number of pages to return. Set to Null to return all pages.
     * @param   int     $sortType   One of the GALLERIES_SORT_* constants to set the sort field.
     * @param   int     $sortDir    Either GALLERIES_ASC or GALLERIES_DESC to set the sort direction.
     *
     * @return  array   An array containing the page information.
     */
    function GetAds($limit = null, $sortColumn = 'title', $sortDir = 'ASC', $offSet = false, $OwnerID = null, $active = null, $return = null, $search = '')
    {
        $fields     = array('ownerid', 'url', 'keyword', 'title', 'type', 'created', 'updated', 'active');
        $sortColumn = strtolower($sortColumn);
        if (!in_array($sortColumn, $fields)) {
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_WARNING, _t('ADS_ERROR_UNKNOWN_COLUMN'));
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
            SELECT [id], [type], [image], [url], [title], [keyword], [sitewide],
			[ownerid], [active], [created], [updated], [barcode_data], [barcode_type], 
			[description], [linkid], [brandid], [checksum]
            FROM [[ads]]
			";
		
		$params              = array();
		
		if (!is_null($OwnerID)) {
			$params['owner_id'] = (int)$OwnerID;
			$sql .= " WHERE [ownerid] = {owner_id}";
		}
		
		$sql .= " ORDER BY [$sortColumn] $sortDir";

        if (is_numeric($offSet)) {
            $limit = is_null($limit) ? 10 : $limit;
			$result = $GLOBALS['db']->setLimit(10, $offSet);
			if (Jaws_Error::IsError($result)) {
				return new Jaws_Error(_t('ADS_ERROR_ADS_NOT_RETRIEVED'), _t('ADS_NAME'));
			}
        } else if (!is_null($limit)) {
			$result = $GLOBALS['db']->setLimit($limit);
			if (Jaws_Error::IsError($result)) {
				return new Jaws_Error(_t('ADS_ERROR_ADS_NOT_RETRIEVED'), _t('ADS_NAME'));
			}
        }

        $types = array(
			'integer', 'text', 'text', 'text', 'text', 'text', 'text', 
			'integer', 'text', 'timestamp', 'timestamp', 'text', 'text', 
			'text', 'integer', 'integer', 'text'
		);

        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('ADS_ERROR_ADS_NOT_RETRIEVED'), _t('ADS_NAME'));
        }

        return $result;
    }
    
    /**
     * Gets all ads by LinkID
     *
     * @access  public
     * @param   int     $id  The user ID
     * @return  mixed   Returns an array with the ad info and false on error
     */
    function GetAllAdsOfParent($id, $sortColumn = 'created', $sortDir = 'ASC', $active = null)
    {
        $fields = array('type', 'keyword', 'url', 'ownerid', 'title', 'created', 'updated', 'active', 'brandid');
        $sortColumn = strtolower($sortColumn);
        if (!in_array($sortColumn, $fields)) {
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_WARNING, _t('ADS_ERROR_UNKNOWN_COLUMN'));
            }
            $sortColumn = 'Created';
        }

        $sortDir = strtoupper($sortDir);
        if ($sortDir == 'DESC') {
			$sort_ascending = false;
            //$sortDir = 'DESC';
        } else {
			$sort_ascending = true;
            //$sortDir = 'ASC';
        }

		$params       = array();
        $params['id'] = $id;
		
		$sql = '
            SELECT [id], [type], [image], [url], [title], [keyword], [sitewide],
			[ownerid], [active], [created], [updated], [barcode_data], [barcode_type], 
			[description], [linkid], [brandid], [checksum]
			FROM [[ads]]
            WHERE ([linkid] = {id}';
		
		if (!is_null($active)) {
			$params['Active'] = $active;
			$sql .= ' AND [active] = {Active}';
		}
		
		$sql .= ')
			ORDER BY [id] DESC';
		
        $types = array(
			'integer', 'text', 'text', 'text', 'text', 'text', 'text', 
			'integer', 'text', 'timestamp', 'timestamp', 'text', 'text', 
			'text', 'integer', 'integer', 'text'
		);

        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('ADS_ERROR_AD_NOT_RETRIEVED'), _t('ADS_NAME'));
        }

		if (count($result)) {
			// Sort result array
			$subkey = $sortColumn; 

			$temp_array = array();
			$temp_array[key($result)] = array_shift($result);
				
			foreach($result as $key => $val){
				$offset = 0;
				$found = false;
				foreach($temp_array as $tmp_key => $tmp_val)
				{
					if ($subkey == 'created') {
						$val[$subkey] = strtotime($val[$subkey]);
					}
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

			
			if ($sortDir != 'DESC' && $subkey != 'created') {
				$result = array_reverse($temp_array);
			} else {
				$result = $temp_array;
			}
		}
        return $result;
    }

    /**
     * Returns all button ads matching a keyword
     *
     * @access  public
     * @return  array  Array with all the post IDs or Jaws_Error on error
     */
    function GetAdsByKeyword($search, $type = null)
    {
	    $params = array();
		
		$sql  = '
            SELECT [id], [type], [image], [url], [title], [keyword], [sitewide],
			[ownerid], [active], [created], [updated], [barcode_data], [barcode_type], 
			[description], [linkid], [brandid], [checksum]
			FROM [[ads]] WHERE (';
		
		$type_select = '';
		if (!is_null($type)) {
			if ($type == '728') {			
				$params['type'] = '728';
			} else if ($type == '468') {			
				$params['type'] = '468';
			} else if ($type == '225') {			
				$params['type'] = '225';
			} else {
				$params['type'] = '125';
			}
			$type_select .= '[type] = {type}';
			$sql .= $type_select;
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
				$stop_words = array(
					"&",
					"&amp;",
					"a",
					"able",
					"about",
					"above",
					"abroad",
					"according",
					"accordingly",
					"across",
					"actually",
					"adj",
					"after",
					"afterwards",
					"again",
					"against",
					"ago",
					"ahead",
					"ain't",
					"all",
					"allow",
					"allows",
					"almost",
					"alone",
					"along",
					"alongside",
					"already",
					"also",
					"although",
					"always",
					"am",
					"amid",
					"amidst",
					"among",
					"amongst",
					"an",
					"and",
					"another",
					"any",
					"anybody",
					"anyhow",
					"anyone",
					"anything",
					"anyway",
					"anyways",
					"anywhere",
					"apart",
					"appear",
					"appreciate",
					"appropriate",
					"are",
					"aren't",
					"around",
					"as",
					"a's",
					"aside",
					"ask",
					"asking",
					"associated",
					"at",
					"available",
					"away",
					"awfully",
					"b",
					"back",
					"backward",
					"backwards",
					"be",
					"became",
					"because",
					"become",
					"becomes",
					"becoming",
					"been",
					"before",
					"beforehand",
					"begin",
					"behind",
					"being",
					"believe",
					"below",
					"beside",
					"besides",
					"best",
					"better",
					"between",
					"beyond",
					"both",
					"brief",
					"but",
					"by",
					"c",
					"came",
					"can",
					"cannot",
					"cant",
					"can't",
					"caption",
					"cause",
					"causes",
					"certain",
					"certainly",
					"changes",
					"clearly",
					"c'mon",
					"co",
					"co.",
					"com",
					"come",
					"comes",
					"concerning",
					"consequently",
					"consider",
					"considering",
					"contain",
					"containing",
					"contains",
					"corresponding",
					"could",
					"couldn't",
					"course",
					"c's",
					"currently",
					"d",
					"dare",
					"daren't",
					"definitely",
					"described",
					"despite",
					"did",
					"didn't",
					"different",
					"directly",
					"do",
					"does",
					"doesn't",
					"doing",
					"done",
					"don't",
					"down",
					"downwards",
					"during",
					"e",
					"each",
					"edu",
					"eg",
					"eight",
					"eighty",
					"either",
					"else",
					"elsewhere",
					"end",
					"ending",
					"enough",
					"entirely",
					"especially",
					"et",
					"etc",
					"even",
					"ever",
					"evermore",
					"every",
					"everybody",
					"everyone",
					"everything",
					"everywhere",
					"ex",
					"exactly",
					"example",
					"except",
					"f",
					"fairly",
					"far",
					"farther",
					"few",
					"fewer",
					"fifth",
					"first",
					"five",
					"followed",
					"following",
					"follows",
					"for",
					"forever",
					"former",
					"formerly",
					"forth",
					"forward",
					"found",
					"four",
					"from",
					"further",
					"furthermore",
					"g",
					"get",
					"gets",
					"getting",
					"given",
					"gives",
					"go",
					"goes",
					"going",
					"gone",
					"got",
					"gotten",
					"greetings",
					"h",
					"had",
					"hadn't",
					"half",
					"happens",
					"hardly",
					"has",
					"hasn't",
					"have",
					"haven't",
					"having",
					"he",
					"he'd",
					"he'll",
					"hello",
					"help",
					"hence",
					"her",
					"here",
					"hereafter",
					"hereby",
					"herein",
					"here's",
					"hereupon",
					"hers",
					"herself",
					"he's",
					"hi",
					"him",
					"himself",
					"his",
					"hither",
					"hopefully",
					"how",
					"howbeit",
					"however",
					"hundred",
					"i",
					"i'd",
					"ie",
					"if",
					"ignored",
					"i'll",
					"i'm",
					"immediate",
					"in",
					"inasmuch",
					"inc",
					"inc.",
					"indeed",
					"indicate",
					"indicated",
					"indicates",
					"info",
					"inner",
					"inside",
					"insofar",
					"instead",
					"into",
					"inward",
					"is",
					"isn't",
					"it",
					"it'd",
					"it'll",
					"its",
					"it's",
					"itself",
					"i've",
					"j",
					"just",
					"k",
					"keep",
					"keeps",
					"kept",
					"know",
					"known",
					"knows",
					"l",
					"last",
					"lately",
					"later",
					"latter",
					"latterly",
					"least",
					"less",
					"lest",
					"let",
					"let's",
					"like",
					"liked",
					"likely",
					"likewise",
					"little",
					"look",
					"looking",
					"looks",
					"low",
					"lower",
					"ltd",
					"m",
					"made",
					"mainly",
					"make",
					"makes",
					"many",
					"may",
					"maybe",
					"mayn't",
					"me",
					"mean",
					"meantime",
					"meanwhile",
					"merely",
					"might",
					"mightn't",
					"mine",
					"minus",
					"miss",
					"more",
					"moreover",
					"most",
					"mostly",
					"mr",
					"mrs",
					"much",
					"must",
					"mustn't",
					"my",
					"myself",
					"n",
					"name",
					"namely",
					"nd",
					"near",
					"nearly",
					"necessary",
					"need",
					"needn't",
					"needs",
					"neither",
					"never",
					"neverf",
					"neverless",
					"nevertheless",
					"new",
					"next",
					"nine",
					"ninety",
					"no",
					"nobody",
					"non",
					"none",
					"nonetheless",
					"noone",
					"no-one",
					"nor",
					"normally",
					"not",
					"nothing",
					"notwithstanding",
					"novel",
					"now",
					"nowhere",
					"o",
					"obviously",
					"of",
					"off",
					"often",
					"oh",
					"ok",
					"okay",
					"old",
					"on",
					"once",
					"one",
					"ones",
					"one's",
					"only",
					"onto",
					"opposite",
					"or",
					"other",
					"others",
					"otherwise",
					"ought",
					"oughtn't",
					"our",
					"ours",
					"ourselves",
					"out",
					"outside",
					"over",
					"overall",
					"own",
					"p",
					"particular",
					"particularly",
					"past",
					"per",
					"perhaps",
					"placed",
					"please",
					"plus",
					"possible",
					"presumably",
					"probably",
					"provided",
					"provides",
					"q",
					"que",
					"quite",
					"qv",
					"r",
					"rather",
					"rd",
					"re",
					"really",
					"reasonably",
					"recent",
					"recently",
					"regarding",
					"regardless",
					"regards",
					"relatively",
					"respectively",
					"right",
					"round",
					"s",
					"said",
					"same",
					"saw",
					"say",
					"saying",
					"says",
					"second",
					"secondly",
					"see",
					"seeing",
					"seem",
					"seemed",
					"seeming",
					"seems",
					"seen",
					"self",
					"selves",
					"sensible",
					"sent",
					"serious",
					"seriously",
					"seven",
					"several",
					"shall",
					"shan't",
					"she",
					"she'd",
					"she'll",
					"she's",
					"should",
					"shouldn't",
					"since",
					"six",
					"so",
					"some",
					"somebody",
					"someday",
					"somehow",
					"someone",
					"something",
					"sometime",
					"sometimes",
					"somewhat",
					"somewhere",
					"soon",
					"sorry",
					"specified",
					"specify",
					"specifying",
					"still",
					"sub",
					"such",
					"sup",
					"sure",
					"t",
					"take",
					"taken",
					"taking",
					"tell",
					"tends",
					"th",
					"than",
					"thank",
					"thanks",
					"thanx",
					"that",
					"that'll",
					"thats",
					"that's",
					"that've",
					"the",
					"their",
					"theirs",
					"them",
					"themselves",
					"then",
					"thence",
					"there",
					"thereafter",
					"thereby",
					"there'd",
					"therefore",
					"therein",
					"there'll",
					"there're",
					"theres",
					"there's",
					"thereupon",
					"there've",
					"these",
					"they",
					"they'd",
					"they'll",
					"they're",
					"they've",
					"thing",
					"things",
					"think",
					"third",
					"thirty",
					"this",
					"thorough",
					"thoroughly",
					"those",
					"though",
					"three",
					"through",
					"throughout",
					"thru",
					"thus",
					"till",
					"to",
					"together",
					"too",
					"took",
					"toward",
					"towards",
					"tried",
					"tries",
					"truly",
					"try",
					"trying",
					"t's",
					"twice",
					"two",
					"u",
					"un",
					"under",
					"underneath",
					"undoing",
					"unfortunately",
					"unless",
					"unlike",
					"unlikely",
					"until",
					"unto",
					"up",
					"upon",
					"upwards",
					"us",
					"use",
					"used",
					"useful",
					"uses",
					"using",
					"usually",
					"v",
					"value",
					"various",
					"versus",
					"very",
					"via",
					"viz",
					"vs",
					"w",
					"want",
					"wants",
					"was",
					"wasn't",
					"way",
					"we",
					"we'd",
					"welcome",
					"well",
					"we'll",
					"went",
					"were",
					"we're",
					"weren't",
					"we've",
					"what",
					"whatever",
					"what'll",
					"what's",
					"what've",
					"when",
					"whence",
					"whenever",
					"where",
					"whereafter",
					"whereas",
					"whereby",
					"wherein",
					"where's",
					"whereupon",
					"wherever",
					"whether",
					"which",
					"whichever",
					"while",
					"whilst",
					"whither",
					"who",
					"who'd",
					"whoever",
					"whole",
					"who'll",
					"whom",
					"whomever",
					"who's",
					"whose",
					"why",
					"will",
					"willing",
					"wish",
					"with",
					"within",
					"without",
					"wonder",
					"won't",
					"would",
					"wouldn't",
					"x",
					"y",
					"yes",
					"yet",
					"you",
					"you'd",
					"you'll",
					"your",
					"you're",
					"yours",
					"yourself",
					"yourselves",
					"you've",
					"z",
					"zero"
				);
				if (!in_array(strtolower($v), $stop_words)) {
					$sql .= " AND [keyword] LIKE {textLike_".$i."}";
					$params['textLike_'.$i] = '%'.$v.'%';
					$i++;
				}
			}
        }
		$params['sitewide'] = 'Y';
		$sql .= ') OR ('.(!empty($type_select) ? $type_select.' AND ' : '').'[sitewide] = {sitewide})';
		$sql .= ' ORDER BY [updated] DESC';

        $types = array(
			'integer', 'text', 'text', 'text', 'text', 'text', 'text', 
			'integer', 'text', 'timestamp', 'timestamp', 'text', 'text', 
			'text', 'integer', 'integer', 'text'
		);
		
		$res1 = $GLOBALS['db']->queryAll($sql, $params, $types);
		
        if (Jaws_Error::IsError($res1)) {
            //add language word for this
            return new Jaws_Error(_t('ADS_ERROR_AD_NOT_FOUND'), _t('ADS_NAME'));
        }
		
		return $res1;
    }
		
    /**
     * Gets a single ad by user ID
     *
     * @access  public
     * @param   int     $id  The user ID
     * @param   int     $gid  The ad ID
     * @return  mixed   Returns an array with the ad info and false on error
     */
    function GetSingleAdByUserID($id, $gid)
    {
		$params       = array();
        $params['id'] = $id;
        $params['gid'] = $gid;
		
		$sql = '
            SELECT [id], [type], [image], [url], [title], [keyword], [sitewide],
			[ownerid], [active], [created], [updated], [barcode_data], [barcode_type], 
			[description], [linkid], [brandid], [checksum]
			FROM [[ads]]
            WHERE ([id] = {gid} AND [ownerid] = {id})
			ORDER BY [updated] DESC';
		
        $types = array(
			'integer', 'text', 'text', 'text', 'text', 'text', 'text', 
			'integer', 'text', 'timestamp', 'timestamp', 'text', 'text', 
			'text', 'integer', 'integer', 'text'
		);

        $result = $GLOBALS['db']->queryRow($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('ADS_ERROR_AD_NOT_RETRIEVED'), _t('ADS_NAME'));
        }

        return $result;
    }

    /**
     * Gets all ads by user ID
     *
     * @access  public
     * @param   int     $id  The user ID
     * @param   string  $type  The ad type
     * @return  mixed   Returns an array with the ad info and false on error
     */
    function GetAdsOfUserID($id, $type = null)
    {
		$params       = array();
        $params['id'] = $id;
		
		$sql = '
            SELECT [id], [type], [image], [url], [title], [keyword], [sitewide],
			[ownerid], [active], [created], [updated], [barcode_data], [barcode_type], 
			[description], [linkid], [brandid], [checksum]
			FROM [[ads]]
            WHERE ([ownerid] = {id}';
		
		if (!is_null($type)) {
			if ($type == '728')	{		
				$params['type'] = '728';
			} else if ($type == '720')	{		
				$params['type'] = '720';
			} else if ($type == '468')	{		
				$params['type'] = '468';
			} else {
				$params['type'] = '125';
			}
			$sql .= ' AND [type] = {type}';
		}
		
		$sql .= ')
			ORDER BY [updated] DESC';
		
        $types = array(
			'integer', 'text', 'text', 'text', 'text', 'text', 'text', 
			'integer', 'text', 'timestamp', 'timestamp', 'text', 'text', 
			'text', 'integer', 'integer', 'text'
		);

        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('ADS_ERROR_AD_NOT_RETRIEVED'), _t('ADS_NAME'));
        }

        return $result;
    }

    /**
     * Gets all sitewide ads
     *
     * @access  public
     * @param   string  $type  The ad type
     * @return  mixed   Returns an array with the ad info and false on error
     */
    function GetSitewideAds($type = '125')
    {
		$params       = array();
        $params['type'] = $type;
        $params['sitewide'] = 'Y';
		
		$sql = '
            SELECT [id], [type], [image], [url], [title], [keyword], [sitewide],
			[ownerid], [active], [created], [updated], [barcode_data], [barcode_type], 
			[description], [linkid], [brandid], [checksum]
			FROM [[ads]]
            WHERE ([type] = {type} AND [sitewide] = {sitewide})
			ORDER BY [updated] DESC';
		
        $types = array(
			'integer', 'text', 'text', 'text', 'text', 'text', 'text', 
			'integer', 'text', 'timestamp', 'timestamp', 'text', 'text', 
			'text', 'integer', 'integer', 'text'
		);

        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('ADS_ERROR_ADS_NOT_RETRIEVED'), _t('ADS_NAME'));
        }

        return $result;
    }
	
    /**
     * Gets all ad owners (companies) within a radius (in miles) of given location
     *
     * @access  public
     * @param   string	$location	The longitude/latitude coordinates in format 'longitude;latitude'
     * @param   string  $radius	Radius to look in
     * @param   string  $type	The ad type
     * @return  mixed   Returns an array with the ad info and false on error
     */
    function GetAdsByLocation($location, $radius = 50, $type = null)
    {
		if (empty($location) || strpos($location, ',') === false) {
            return new Jaws_Error(_t('ADS_ERROR_ADS_NOT_RETRIEVED'), _t('ADS_NAME'));
		}
		$coordinates = explode(',', $location);
		$results = array();
		
		// Get all ads
		$ads = $this->GetAds();
        if (Jaws_Error::IsError($ads)) {
            return new Jaws_Error(_t('ADS_ERROR_ADS_NOT_RETRIEVED'), _t('ADS_NAME'));
		} else {
			foreach ($ads as $ad) {
				// Only show Owned ads?
				if ((int)$ad['ownerid'] > 0) {
					// Get Owner info
					require_once JAWS_PATH . 'include/Jaws/User.php';
					$userModel = new Jaws_User;
					$info = $userModel->GetUserInfoByID((int)$ad['ownerid'], true, true, true, true);
					if (Jaws_Error::IsError($info)) {
						return $info;
					}
					// Get Owner location info
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
						
						$address_info = Jaws_Utils::GetGeoLocation($marker_address);
						// Is Owner's location within given radius?
						if (isset($address_info['latitude']) && !empty($address_info['latitude']) && isset($address_info['longitude']) && !empty($address_info['longitude'])) {
							$distance = Jaws_Utils::haversine((float)$coordinates[0], (float)$coordinates[1], $address_info['latitude'], $address_info['longitude']); 
							if ((int)$distance < (int)$radius) {	
								if (!is_null($type)) {
									if ($ad['type'] == $type) {
										$ad['distance'] = (int)$distance;
										$results[] = $ad;
									}
								} else {
									$ad['distance'] = (int)$distance;
									$results[] = $ad;
								}
							}
						}
					}
				//} else {
				//	$results[] = $ad;
				}
			}
		}
		
		if (count($results)) {
			// Sort result array
			$subkey = 'distance'; 
			$temp_array = array();
			
			$temp_array[key($results)] = array_shift($results);

			foreach($results as $key => $val){
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

			if ($sortDir != 'DESC') {
				$results = array_reverse($temp_array);
			} else {
				$results = $temp_array;
			}
		}
        return $results;
	}

    /**
     * Gets a single brand by ID.
     *
     * @access  public
     * @param   int     $id     The ID of the post to get.
     * @return  array   An array containing the post information, or false if no page could be loaded.
     */
    function GetBrand($id)
    {		
		$sql = '
            SELECT [id], [title], 
				[description], [image], [image_width], [image_height], 
				[layout], [active], [ownerid], [created], [updated],
				[url], [url_target], [image_code], [checksum] 
			FROM [[adbrand]] WHERE [id] = {id}';

        $types = array(
			'integer', 'text', 
			'text', 'text', 'integer', 'integer', 'integer', 'text', 
			'integer', 'timestamp', 'timestamp', 'text', 'text', 'text', 'text'
		);

        $params             = array();
        $params['id']       = $id;

        $row = $GLOBALS['db']->queryRow($sql, $params, $types);

		if (isset($row['id'])) {
            return $row;
        }

        return new Jaws_Error(_t('ADS_ERROR_BRAND_NOT_FOUND'), _t('ADS_NAME'));
    }

    /**
     * Gets an index of all the sales.
     *
     * @access  public
     * @param   int     $limit      The number of pages to return. Set to Null to return all pages.
     * @param   int     $sortType   One of the PAGES_SORT_* constants to set the sort field.
     * @param   int     $sortDir    Either PAGES_ASC or PAGES_DESC to set the sort direction.
     *
     * @return  array   An array containing the page information.
     */
    function GetBrands($limit = null, $sortColumn = 'title', $sortDir = 'ASC', $offSet = false, $OwnerID = null)
    {
        $fields = array('title', 'active', 'ownerid', 'created', 'updated');
        $sortColumn = strtolower($sortColumn);
        if (!in_array($sortColumn, $fields)) {
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_WARNING, _t('ADS_ERROR_UNKNOWN_COLUMN'));
            }
            $sortColumn = 'title';
        }

        $sortDir = strtoupper($sortDir);
        if ($sortDir != 'ASC') {
            $sortDir = 'DESC';
        }

        $sql = "
            SELECT [id], [title], 
				[description], [image], [image_width], [image_height], 
				[layout], [active], [ownerid], [created], [updated],
				[url], [url_target], [image_code], [checksum]
            FROM [[adbrand]]
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
                    return new Jaws_Error(_t('ADS_ERROR_BRANDS_NOT_RETRIEVED'), _t('ADS_NAME'));
                }
            }
        } else {
            if (!is_null($limit)) {
                $result = $GLOBALS['db']->setLimit($limit);
                if (Jaws_Error::IsError($result)) {
                    return new Jaws_Error(_t('ADS_ERROR_BRANDS_NOT_RETRIEVED'), _t('ADS_NAME'));
                }
            }
        }

        $types = array(
			'integer', 'text', 
			'text', 'text', 'integer', 'integer', 'integer', 'text', 
			'integer', 'timestamp', 'timestamp', 'text', 'text', 'text', 'text'
		);

        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('ADS_ERROR_BRANDS_NOT_RETRIEVED'), _t('ADS_NAME'));
        }

        return $result;
    }

    /**
     * Gets all ad brands by user ID
     *
     * @access  public
     * @param   int     $id  The user ID
     * @return  mixed   Returns an array with the ad info and false on error
     */
    function GetAdBrandsOfUserID($id)
    {
		$params       = array();
        $params['id'] = $id;
		
		$sql = '
            SELECT [id], [title], 
				[description], [image], [image_width], [image_height], 
				[layout], [active], [ownerid], [created], [updated],
				[url], [url_target], [image_code], [checksum]
			FROM [[adbrand]]
            WHERE ([ownerid] = {id})
			ORDER BY [updated] DESC';
		
        $types = array(
			'integer', 'text', 
			'text', 'text', 'integer', 'integer', 'integer', 'text', 
			'integer', 'timestamp', 'timestamp', 'text', 'text', 'text', 'text'
		);

        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('ADS_ERROR_ADBRANDS_NOT_RETRIEVED'), _t('ADS_NAME'));
        }

        return $result;
    }

    /**
     * Gets a single ad by ID.
     *
     * @access  public
     * @param   int     $id     The ID of the ad to get.
     * @return  array   An array containing the ad information, or false if no ad could be loaded.
     */
    function GetSavedAd($id)
    {
        $sql = '
            SELECT [id], [ad_id], [status], [ownerid], [created], [updated], [description]
            FROM [[ads_subscribe]]
			WHERE [id] = {id}';

        $types = array(
			'integer', 'integer', 'text', 'integer', 'timestamp', 'timestamp', 'text'
		);

        $params             = array();
        $params['id']       = $id;
        //$params['language'] = $language;

        $row = $GLOBALS['db']->queryRow($sql, $params, $types);
        if (Jaws_Error::IsError($row)) {
            return new Jaws_Error(_t('ADS_ERROR_AD_NOT_FOUND'), _t('ADS_NAME'));
        }
		
        if (isset($row['id'])) {
            return $row;
        }

        return new Jaws_Error(_t('ADS_ERROR_AD_NOT_FOUND'), _t('ADS_NAME'));
    }

    /**
     * Gets an index of all the ads.
     *
     * @access  public
     * @param   int     $limit      The number of pages to return. Set to Null to return all pages.
     * @param   int     $sortType   One of the GALLERIES_SORT_* constants to set the sort field.
     * @param   int     $sortDir    Either GALLERIES_ASC or GALLERIES_DESC to set the sort direction.
     *
     * @return  array   An array containing the page information.
     */
    function GetSavedAds($limit = null, $sortColumn = 'ad_id', $sortDir = 'ASC', $offSet = false)
    {
        $fields     = array('ownerid', 'ad_id', 'status', 'description', 'created', 'updated');
        $sortColumn = strtolower($sortColumn);
        if (!in_array($sortColumn, $fields)) {
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_WARNING, _t('ADS_ERROR_UNKNOWN_COLUMN'));
            }
            $sortColumn = 'ad_id';
        }

        $sortDir = strtoupper($sortDir);
        if ($sortDir == 'DESC') {
            $sortDir = 'DESC';
        } else {
            $sortDir = 'ASC';
        }

        $sql = "
            SELECT [id], [ad_id], [status], [ownerid], [created], [updated], [description]
            FROM [[ads_subscribe]]
			";
		
		$params              = array();
		$params['owner_id'] = $GLOBALS['app']->Session->GetAttribute('user_id');
		
		$sql .= " WHERE [ownerid] = {owner_id} ORDER BY [$sortColumn] $sortDir";

        if (is_numeric($offSet)) {
            $limit = is_null($limit) ? 10 : $limit;
            if (!is_null($limit)) {
                $result = $GLOBALS['db']->setLimit(10, $offSet);
                if (Jaws_Error::IsError($result)) {
                    return new Jaws_Error(_t('ADS_ERROR_ADS_NOT_RETRIEVED'), _t('ADS_NAME'));
                }
            }
        } else {
            if (!is_null($limit)) {
                $result = $GLOBALS['db']->setLimit($limit);
                if (Jaws_Error::IsError($result)) {
                    return new Jaws_Error(_t('ADS_ERROR_ADS_NOT_RETRIEVED'), _t('ADS_NAME'));
                }
            }
        }

        $types = array(
			'integer', 'integer', 'text', 'integer', 'timestamp', 'timestamp', 'text'
		);

        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('ADS_ERROR_ADS_NOT_RETRIEVED'), _t('ADS_NAME'));
        }

        return $result;
    }
    
    /**
     * Gets all saved ads by ad_id
     *
     * @access  public
     * @param   int     $id  The ad ID
     * @return  mixed   Returns an array with the ad info and false on error
     */
    function GetAllSavedAdsOfAd($id, $sortColumn = 'created', $sortDir = 'ASC', $status = null)
    {
        $fields     = array('ownerid', 'ad_id', 'status', 'description', 'created', 'updated');
        $sortColumn = strtolower($sortColumn);
        if (!in_array($sortColumn, $fields)) {
            if (isset($GLOBALS['log'])) {
                $GLOBALS['log']->Log(JAWS_LOG_WARNING, _t('ADS_ERROR_UNKNOWN_COLUMN'));
            }
            $sortColumn = 'created';
        }

        $sortDir = strtoupper($sortDir);
        if ($sortDir == 'DESC') {
			$sort_ascending = false;
            //$sortDir = 'DESC';
        } else {
			$sort_ascending = true;
            //$sortDir = 'ASC';
        }

		$params       = array();
        $params['id'] = $id;
		
		$sql = '
            SELECT [id], [ad_id], [status], [ownerid], [created], [updated], [description]
            FROM [[ads_subscribe]]
            WHERE ([ad_id] = {id}';
		
		if (!is_null($active)) {
			$params['status'] = $status;
			$sql .= ' AND [status] = {status}';
		}
		
		$sql .= ')
			ORDER BY [id] DESC';
		
        $types = array(
			'integer', 'integer', 'text', 'integer', 'timestamp', 'timestamp', 'text'
		);

        $result = $GLOBALS['db']->queryAll($sql, $params, $types);
        if (Jaws_Error::IsError($result)) {
            return new Jaws_Error(_t('ADS_ERROR_AD_NOT_RETRIEVED'), _t('ADS_NAME'));
        }

		if (count($result)) {
			// Sort result array
			$subkey = $sortColumn; 

			$temp_array = array();
			$temp_array[key($result)] = array_shift($result);
				
			foreach($result as $key => $val){
				$offset = 0;
				$found = false;
				foreach($temp_array as $tmp_key => $tmp_val)
				{
					if ($subkey == 'created') {
						$val[$subkey] = strtotime($val[$subkey]);
					}
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

			
			if ($sortDir != 'DESC' && $subkey != 'created') {
				$result = array_reverse($temp_array);
			} else {
				$result = $temp_array;
			}
		}
        return $result;
    }
}
